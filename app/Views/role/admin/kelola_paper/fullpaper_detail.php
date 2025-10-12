<?php
// ==== INPUT DARI CONTROLLER (tetap) ====
$submission       = $submission ?? [];
$author           = $author ?? ['name'=>null,'email'=>null];
$coauthors        = $coauthors ?? [];
$history          = $history ?? [];
$reviewers        = $reviewers ?? [];
$assigned         = $assignedReviewers ?? [];
$absReviewers     = $abstractReviewers ?? [];
$abstractStatus   = strtolower((string)($abstractStatus ?? ''));
$fpReviews        = $fpReviews ?? [];
$maxReviewer      = (int)($maxReviewer ?? 3);

// tambahan dari controller (kategori & people abstrak)
$absKategoriId    = $absKategoriId    ?? null;
$absKategoriName  = trim((string)($absKategoriName ?? ''));
$absContributors  = is_array($absContributors ?? null) ? $absContributors : [];
$absJudul         = trim((string)($absJudul ?? ''));

// ==== META STATUS FP ====
$status     = strtoupper($submission['full_paper_status'] ?? 'NONE');
$badgeMap   = ['NONE'=>'secondary','UPLOADED'=>'info','REVISION'=>'warning','ACCEPTED'=>'success','REJECTED'=>'danger'];
$statusText = ['NONE'=>'—','UPLOADED'=>'Diunggah','REVISION'=>'Revisi','ACCEPTED'=>'Diterima','REJECTED'=>'Ditolak'];
$badge      = $badgeMap[$status] ?? 'secondary';
$uploadedAt = !empty($submission['full_paper_uploaded_at']) ? date('d M Y H:i', strtotime($submission['full_paper_uploaded_at'])) : '—';
$revisiKe   = isset($submission['revisi_ke']) ? (int)$submission['revisi_ke'] : 0;

// ==== URL & FORMAT ====
$submissionId = (int)($submission['id'] ?? 0);
$eventId      = (int)($submission['event_id'] ?? 0);
$backUrl      = $eventId ? site_url('admin/kelola-paper/detail/'.$eventId) : site_url('admin/kelola-paper');

$fmtDT = fn($s)=> $s ? date('d M Y H:i', strtotime($s)) : '—';

// preview & download (pakai endpoint admin)
$downloadUrl = $submissionId ? site_url('admin/fullpaper/download/'.$submissionId) : '';
$previewUrl  = $submissionId ? site_url('admin/fullpaper/view/'.$submissionId) : '';
$gdocs       = $previewUrl ? ('https://docs.google.com/gview?embedded=1&url='.rawurlencode($previewUrl)) : '';

// mapping status review
$rvStatMap = [
  'diterima' => ['Diterima','success'],
  'accepted' => ['Diterima','success'],
  'rejected' => ['Ditolak','danger'],
  'ditolak'  => ['Ditolak','danger'],
  'revisi'   => ['Revisi','warning'],
  'revision' => ['Revisi','warning'],
  'uploaded' => ['Diunggah','info'],
  'pending'  => ['Pending','secondary'],
  'menunggu' => ['Menunggu','secondary'],
  'sedang_direview' => ['Sedang Ditinjau','info'],
  ''         => ['—','secondary'],
  null       => ['—','secondary'],
];

// ringkasan assigned
$assignedCount  = count($assigned);
$completedCount = 0;
foreach ($assigned as $ar) {
  $st = strtolower($ar['status'] ?? '');
  if (in_array($st, ['diterima','accepted','revisi','revision','ditolak','rejected'], true)) $completedCount++;
}
$quotaFull = $assignedCount >= $maxReviewer;

// helper badge assignment reviewer
$badgeForAssign = function($s){
  $s = strtolower((string)$s);
  return match ($s) {
    'accepted' => ['primary','Assigned'],
    'declined' => ['secondary','Declined'],
    default    => ['secondary','Pending'],
  };
};

// Fallback penulis dari abstrak/user
if (empty($author['name'])) {
  $author['name'] = $submission['nama_lengkap']
                  ?? $submission['user_name']
                  ?? ($absContributors[0] ?? null)
                  ?? '-';
}
if (empty($author['email'])) {
  $author['email'] = $submission['email'] ?? $submission['user_email'] ?? null;
}

// Tampilkan Riwayat Revisi HANYA setelah ada keputusan review
$hasReviewDecision = false;
foreach ($fpReviews as $rv) {
  $k = strtolower($rv['keputusan'] ?? '');
  if (in_array($k, ['accepted','diterima','revision','revisi','rejected','ditolak'], true)) {
    $hasReviewDecision = true; break;
  }
}
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_admin') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill page-wrap-blue">
    <div class="container-xxl px-3 px-md-4 py-4">

      <!-- HERO -->
      <div class="card-hero mb-4">
        <div class="hero-body">
          <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
            <div>
              <h3 class="hero-title mb-1">
                <i class="bi bi-file-earmark-text me-2"></i>Detail Full Paper
              </h3>
              <div class="text-white-70 small">
                <?= esc($submission['event_title'] ?? '-') ?>
                <?php if(!empty($submission['title'])): ?>
                  <span class="d-inline-block mx-2">•</span>
                  <span class="fw-semibold"><?= esc($submission['title']) ?></span>
                <?php endif; ?>
              </div>
              <div class="mt-2 d-flex flex-wrap align-items-center gap-2">
                <span class="badge bg-<?= $badge ?>"><?= esc($statusText[$status] ?? $status) ?></span>
                <?php if ($absKategoriName !== ''): ?>
                  <span class="badge bg-secondary-subtle"><i class="bi bi-tags me-1"></i><?= esc($absKategoriName) ?></span>
                <?php endif; ?>
                <?php if ($revisiKe): ?>
                  <span class="badge bg-secondary-subtle">Revisi ke-<?= (int)$revisiKe ?></span>
                <?php endif; ?>
                <?php if ($uploadedAt !== '—'): ?>
                  <span class="badge bg-secondary-subtle"><i class="bi bi-clock me-1"></i><?= esc($uploadedAt) ?></span>
                <?php endif; ?>
              </div>
            </div>
            <div class="d-flex gap-2 ms-auto">
              <a href="<?= esc($backUrl) ?>" class="btn btn-light btn-sm">
                <i class="bi bi-arrow-left"></i> Kembali
              </a>
              <button class="btn btn-light btn-sm" data-bs-toggle="modal" data-bs-target="#statusModal">
                <i class="bi bi-flag me-1"></i> Keputusan
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-3">
        <!-- KIRI -->
        <div class="col-12 col-xl-8">
          <!-- Info FP -->
          <div class="card shadow-soft card-glass-plain mb-3">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
              <span class="badge bg-blue-soft"><i class="bi bi-info-circle"></i></span>
              <h6 class="mb-0 fw-semibold text-blue-900">Informasi Full Paper</h6>
            </div>
            <div class="card-body">
              <ul class="meta-list mb-3">
                <li><span>Diunggah</span><strong class="text-blue-900"><?= esc($uploadedAt) ?></strong></li>
                <li><span>Judul</span><strong class="text-blue-900"><?= esc($submission['title'] ?? '-') ?></strong></li>
                <li><span>Penulis</span>
                  <strong class="text-blue-900">
                    <?= esc($author['name'] ?? '-') ?>
                    <?php if(!empty($author['email'])): ?>
                      <small class="text-muted ms-1">&lt;<?= esc($author['email']) ?>&gt;</small>
                    <?php endif; ?>
                  </strong>
                </li>
                <li><span>Kategori Abstrak</span>
                  <strong class="text-blue-900"><?= $absKategoriName !== '' ? esc($absKategoriName) : '—' ?></strong>
                </li>
              </ul>

              <?php if (!empty($absContributors)): ?>
                <div class="mt-1">
                  <div class="fw-semibold mb-1">Contributor</div>
                  <div class="chip-row">
                    <?php foreach ($absContributors as $nm): ?>
                      <span class="chip"><i class="bi bi-person me-1"></i><?= esc($nm) ?></span>
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Preview PDF -->
          <div class="card shadow-soft card-glass-plain mb-3">
            <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-blue-soft"><i class="bi bi-file-earmark-pdf"></i></span>
                <h6 class="mb-0 fw-semibold text-blue-900">Pratinjau Full Paper</h6>
              </div>
              <div class="btn-group btn-group-sm">
                <?php if ($downloadUrl): ?>
                  <a class="btn btn-outline-secondary" href="<?= esc($downloadUrl) ?>"><i class="bi bi-download"></i></a>
                <?php endif; ?>
                <?php if ($previewUrl): ?>
                  <a class="btn btn-outline-secondary" target="_blank" rel="noopener" href="<?= esc($previewUrl) ?>"><i class="bi bi-box-arrow-up-right"></i></a>
                <?php endif; ?>
                <button type="button" class="btn btn-outline-secondary" id="btnToggleSize" data-state="max"><i class="bi bi-arrows-fullscreen"></i></button>
              </div>
            </div>
            <div class="card-body">
              <?php if ($previewUrl): ?>
                <div class="pdf-wrap is-min" id="pdfWrap">
                  <iframe class="pdf-frame" title="Preview PDF" id="pdfFrame"
                          src="<?= esc($previewUrl) ?>#toolbar=1&navpanes=0"></iframe>
                </div>
                <div class="small text-muted mt-2">
                  Jika pratinjau kosong, klik ikon <b>↗</b> untuk membuka di tab baru
                  <?php if (!empty($gdocs)): ?>
                    atau gunakan <a target="_blank" rel="noopener" href="<?= esc($gdocs) ?>">Google Docs Viewer</a>.
                  <?php endif; ?>
                </div>
              <?php else: ?>
                <div class="empty-hint"><i class="bi bi-info-circle me-1"></i> Belum ada file untuk dipratinjau.</div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Riwayat Revisi -->
          <?php if ($hasReviewDecision): ?>
            <div class="card shadow-soft card-glass-plain">
              <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
                <span class="badge bg-blue-soft"><i class="bi bi-clock-history"></i></span>
                <h6 class="mb-0 fw-semibold text-blue-900">Riwayat Revisi</h6>
              </div>
              <div class="card-body pt-2">
                <?php if (!empty($history)): ?>
                  <div class="table-responsive">
                    <table class="table table-sm align-middle">
                      <thead class="table-light">
                        <tr><th>#</th><th>Revisi</th><th>Status</th><th>Waktu Upload</th><th>Aksi</th></tr>
                      </thead>
                      <tbody>
                        <?php $no=1; foreach ($history as $h):
                          $st = strtoupper($h['full_paper_status'] ?? 'NONE');
                          $cls = $badgeMap[$st] ?? 'secondary';
                          $ts  = !empty($h['full_paper_uploaded_at']) ? date('d M Y H:i', strtotime($h['full_paper_uploaded_at'])) : '—';
                        ?>
                          <tr>
                            <td><?= $no++ ?></td>
                            <td><?= (int)($h['revisi_ke'] ?? 0) ?></td>
                            <td><span class="badge bg-<?= $cls ?>"><?= esc($statusText[$st] ?? $st) ?></span></td>
                            <td><?= esc($ts) ?></td>
                            <td><a class="btn btn-ghost btn-xs" href="<?= site_url('admin/fullpaper/detail/'.(int)$h['id']) ?>"><i class="bi bi-eye me-1"></i>Lihat</a></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                <?php else: ?>
                  <div class="empty-hint"><i class="bi bi-info-circle me-1"></i> Belum ada riwayat revisi.</div>
                <?php endif; ?>
              </div>
            </div>
          <?php endif; ?>
        </div>

        <!-- KANAN -->
        <div class="col-12 col-xl-4">
          <!-- Status FP -->
          <div class="card shadow-soft card-glass-plain sticky-card mb-3">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
              <span class="badge bg-blue-soft"><i class="bi bi-flag"></i></span>
              <h6 class="mb-0 fw-semibold text-blue-900">Status</h6>
            </div>
            <div class="card-body">
              <?php if ($status === 'NONE'): ?>
                <div class="alert bg-blue-soft-2 text-blue-900 border-0 fw-semibold mb-0"><i class="bi bi-info-circle me-1"></i> Belum ada Full Paper yang diunggah.</div>
              <?php elseif ($status === 'UPLOADED'): ?>
                <div class="alert bg-blue-soft-2 text-blue-900 border-0 fw-semibold mb-0"><i class="bi bi-hourglass-split me-1"></i> Sedang direview.</div>
              <?php elseif ($status === 'REVISION'): ?>
                <div class="alert alert-warning fw-semibold mb-0"><i class="bi bi-arrow-repeat me-1"></i> Perlu revisi.</div>
              <?php elseif ($status === 'REJECTED'): ?>
                <div class="alert alert-danger fw-semibold mb-0"><i class="bi bi-x-octagon me-1"></i> Ditolak.</div>
              <?php elseif ($status === 'ACCEPTED'): ?>
                <div class="alert alert-success fw-semibold mb-0"><i class="bi bi-check2-circle me-1"></i> Diterima.</div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Reviewer -->
          <div class="card shadow-soft card-glass-plain sticky-card">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center justify-content-between">
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-blue-soft"><i class="bi bi-people"></i></span>
                <h6 class="mb-0 fw-semibold text-blue-900">Reviewer Ditugaskan</h6>
              </div>
              <span class="badge bg-light text-muted"><?= $assignedCount ?> / max <?= $maxReviewer ?><?= $quotaFull ? ' — Penuh' : '' ?></span>
            </div>
            <div class="card-body">
              <?php if (empty($assigned)): ?>
                <div class="empty-hint"><i class="bi bi-info-circle me-1"></i> Belum ada reviewer yang ditugaskan.</div>
              <?php else: ?>
                <div class="vstack gap-2">
                  <?php foreach ($assigned as $ar):
                    $k = strtolower($ar['status'] ?? 'pending');
                    $m = $rvStatMap[$k] ?? ['—','secondary'];
                    $stAt = !empty($ar['status_at']) ? $fmtDT($ar['status_at']) : '—';
                    $asAt = !empty($ar['assigned_at']) ? $fmtDT($ar['assigned_at']) : '—';
                    [$csa, $lsa] = $badgeForAssign($ar['assignment_status'] ?? null);
                  ?>
                  <div class="p-2 border rounded-3">
                    <div class="d-flex align-items-start justify-content-between">
                      <div>
                        <div class="fw-semibold text-blue-900">
                          <?= esc($ar['name'] ?? '-') ?>
                          <?php if (!empty($ar['email'])): ?><small class="text-muted ms-1">&lt;<?= esc($ar['email']) ?>&gt;</small><?php endif; ?>
                        </div>
                        <div class="small text-muted">Tugas: <span class="badge bg-<?= $csa ?>"><?= $lsa ?></span></div>
                      </div>
                      <div><span class="badge bg-<?= $m[1] ?>"><?= esc($m[0]) ?></span></div>
                    </div>
                    <div class="mt-1 small text-muted">
                      Update: <?= esc($stAt) ?> • Ditugaskan: <?= esc($asAt) ?>
                    </div>
                  </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>

              <!-- Tambah reviewer -->
              <div class="mt-3 p-2 rounded border bg-light">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <div class="fw-semibold">Tambah Reviewer</div>
                  <small class="text-muted">Review masuk: <?= (int)$completedCount ?></small>
                </div>
                <?php if (!$quotaFull): ?>
                  <form method="post" action="<?= site_url('admin/fullpaper/assign/'.$submissionId) ?>" class="row g-2">
                    <?= csrf_field() ?>
                    <div class="col-12">
                      <select name="reviewer_id" class="form-select form-select-sm" required>
                        <option value="">-- pilih --</option>
                        <?php if (!empty($reviewers)): ?>
                          <?php foreach ($reviewers as $rv): ?>
                            <option value="<?= (int)$rv['id'] ?>">
                              <?= esc(($rv['name'] ?? 'Reviewer').(!empty($rv['email']) ? ' — '.$rv['email'] : '')) ?>
                            </option>
                          <?php endforeach; ?>
                        <?php else: ?>
                          <option value="">Semua reviewer sudah ditugaskan</option>
                        <?php endif; ?>
                      </select>
                      <div class="form-text">Kandidat otomatis menyembunyikan reviewer yang sudah ditugaskan. Maksimal <?= $maxReviewer ?> orang.</div>
                    </div>
                    <div class="col-12 d-grid">
                      <button class="btn btn-primary btn-sm"><i class="bi bi-person-plus me-1"></i>Tugaskan</button>
                    </div>
                  </form>
                <?php else: ?>
                  <div class="alert alert-success-subtle border-0 mb-0">Kuota reviewer penuh.</div>
                <?php endif; ?>
              </div>

              <!-- Reviewer Abstrak -->
              <div class="mt-3 p-2 rounded border bg-light-subtle">
                <div class="fw-semibold mb-2"><i class="bi bi-people-fill me-1"></i>Reviewer Abstrak</div>
                <?php if (empty($absReviewers)): ?>
                  <div class="text-muted small">Tidak ada data reviewer abstrak.</div>
                <?php else: ?>
                  <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                      <thead class="table-light"><tr><th>#</th><th>Nama</th><th>Email</th><th>Status</th></tr></thead>
                      <tbody>
                        <?php $i=1; foreach ($absReviewers as $rv):
                          $k = strtolower($rv['status'] ?? '');
                          $m = $rvStatMap[$k] ?? ['—','secondary'];
                        ?>
                          <tr>
                            <td><?= $i++ ?></td>
                            <td><?= esc($rv['name'] ?? '-') ?></td>
                            <td><?= esc($rv['email'] ?? '-') ?></td>
                            <td><span class="badge bg-<?= $m[1] ?>"><?= esc($m[0]) ?></span></td>
                          </tr>
                        <?php endforeach; ?>
                      </tbody>
                    </table>
                  </div>
                  <div class="small text-muted mt-2">Info tahap abstrak; boleh menugaskan reviewer yang sama untuk full paper.</div>
                <?php endif; ?>
              </div>

            </div>
          </div>
        </div>
      </div>

    </div>
  </main>
</div>

<!-- Modal Keputusan -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="bi bi-flag me-2"></i>Keputusan Full Paper</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <form action="<?= site_url('admin/fullpaper/set-status/'.$submissionId) ?>" method="post">
        <?= csrf_field() ?>
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Status</label>
            <select class="form-select" name="status" required>
              <?php foreach (['UPLOADED','REVISION','ACCEPTED','REJECTED'] as $opt): ?>
                <option value="<?= $opt ?>" <?= $status===$opt?'selected':'' ?>><?= $statusText[$opt] ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="mb-0">
            <label class="form-label">Komentar (opsional)</label>
            <textarea class="form-control" name="komentar" rows="3" placeholder="Catatan untuk penulis atau internal…"></textarea>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Batal</button>
          <button class="btn btn-primary" type="submit"><i class="bi bi-save me-1"></i>Simpan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<?= $this->include('partials/footer') ?>

<style>
:root{
  --blue-50:#eff6ff; --blue-100:#dbeafe; --blue-200:#bfdbfe;
  --blue-300:#93c5fd; --blue-400:#60a5fa; --blue-500:#3b82f6;
  --blue-600:#2563eb; --blue-700:#1d4ed8; --blue-800:#1e40af; --blue-900:#1e3a8a;
  --muted:#6b7280;
  --side-pad: clamp(1rem, 2.3vw, 2.2rem);
}
.container-xxl{ max-width:min(100%, 1560px); padding-inline: var(--side-pad)!important; margin-inline:auto; }
body{ font-size:15.5px; line-height:1.6; }
.page-wrap-blue{
  min-height:100vh; padding-top:72px; background:
  radial-gradient(900px 300px at 20% -10%, rgba(59,130,246,.17), rgba(59,130,246,0) 60%),
  radial-gradient(900px 300px at 80% 110%, rgba(59,130,246,.14), rgba(59,130,246,0) 70%),
  linear-gradient(180deg, var(--blue-50), #fff 40%);
}
.card-hero{ border:0; border-radius:16px; overflow:hidden; box-shadow:0 12px 28px rgba(30,64,175,.10); }
.card-hero .hero-body{
  background:radial-gradient(1100px 360px at 10% -10%,var(--blue-600) 0,var(--blue-700) 45%,var(--blue-800) 100%);
  color:#fff; padding:1.8rem 1.2rem; min-height:176px;
}
.hero-title{ font-weight:800; letter-spacing:.25px; font-size:1.5rem; }
.text-white-70{ color:rgba(255,255,255,.85)!important; }

.card-glass-plain{ backdrop-filter:blur(6px); background:rgba(255,255,255,.96); border-radius:14px; border:1px solid rgba(30,64,175,.10); }
.shadow-soft{ box-shadow:0 10px 24px rgba(30,64,175,.08); }
.meta-list{ list-style:none; padding-left:0; margin:0; }
.meta-list li{ display:flex; align-items:center; justify-content:space-between; gap:.75rem; padding:.45rem 0; }
.meta-list li span{ color:var(--muted); }
.text-blue-900{ color:var(--blue-900)!important; }
.bg-blue-soft{ background:var(--blue-200); color:var(--blue-800); border-radius:12px; padding:.5rem .7rem; font-weight:600; font-size:.9rem; }
.bg-secondary-subtle{ background:#f1f5f9!important; color:#475569!important; }
.bg-blue-soft-2{ background:linear-gradient(180deg,#eef4ff,#eaf2ff); }
.btn{ font-weight:700; border-radius:10px; font-size:.98rem; padding:.62rem 1.05rem; }
.btn-sm{ padding:.5rem .9rem; font-size:.92rem; }
.btn-primary{ background:var(--blue-600); border-color:var(--blue-600); box-shadow:0 4px 12px rgba(37,99,235,.2); }
.sticky-card{ position:sticky; top:84px; }

.chip-row{ display:flex; flex-wrap:wrap; gap:.5rem; }
.chip{ display:inline-flex; align-items:center; padding:.28rem .6rem; font-size:.88rem; border-radius:999px; background:#eef3ff; color:#1e3a8a; border:1px solid rgba(30,64,175,.15); font-weight:600; }

.pdf-wrap{ border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; background:#f8fafc; transition:height .2s ease; }
.pdf-wrap.is-min{ height:52vh; }
.pdf-wrap.is-max{ height:82vh; }
.pdf-frame{ width:100%; height:100%; border:0; }

.empty-hint{ color:#567; background:#f6f9ff; border:1px dashed rgba(30,64,175,.18); border-radius:12px; padding:.8rem 1rem; font-weight:600; }

@media (max-width:767.98px){
  .card-hero .hero-body{ padding:1.4rem 1rem; min-height:165px; }
  .pdf-wrap.is-min{ height:46vh; }
}
@media (max-width:575.98px){
  .container-xxl{ padding-inline: calc(var(--side-pad) - .25rem)!important; }
  .hero-title{ font-size:1.35rem; }
  .pdf-wrap.is-min{ height:42vh; }
  .pdf-wrap.is-max{ height:75vh; }
}
</style>

<script>
(function(){
  const wrap = document.getElementById('pdfWrap');
  const btn  = document.getElementById('btnToggleSize');
  if(!wrap || !btn) return;
  function setState(state){
    if(state==='max'){
      wrap.classList.remove('is-min'); wrap.classList.add('is-max');
      btn.dataset.state = 'min';
      btn.innerHTML = '<i class="bi bi-arrows-angle-contract"></i>';
      btn.title = 'Minimize';
    }else{
      wrap.classList.remove('is-max'); wrap.classList.add('is-min');
      btn.dataset.state = 'max';
      btn.innerHTML = '<i class="bi bi-arrows-fullscreen"></i>';
      btn.title = 'Maximize';
    }
  }
  wrap.classList.add('is-min');
  btn.addEventListener('click', ()=> setState(btn.dataset.state));
})();
</script>