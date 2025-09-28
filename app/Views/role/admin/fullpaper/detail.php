<?php
/**
 * File: app/Views/role/admin/fullpaper/detail.php
 * Vars (dari controller):
 * - $submission, $author, $coauthors, $history
 * - $reviewers (kandidat — sudah difilter & exclude)
 * - $assignedReviewers (yang sudah ditugaskan di full paper)
 * - $abstractReviewers (yang ditugaskan di abstrak, read-only)
 * - $fpReviews (riwayat penilaian full paper)
 * - $title, $maxReviewer
 */
$submission  = $submission ?? [];
$author      = $author ?? ['name'=>null,'email'=>null];
$coauthors   = $coauthors ?? [];
$history     = $history ?? [];
$reviewers   = $reviewers ?? [];
$assigned    = $assignedReviewers ?? [];
$absReviewers= $abstractReviewers ?? [];
$fpReviews   = $fpReviews ?? [];
$maxReviewer = (int)($maxReviewer ?? 3);

$status = strtoupper($submission['full_paper_status'] ?? 'NONE');
$badgeMap   = ['NONE'=>'secondary','UPLOADED'=>'info','REVISION'=>'warning','ACCEPTED'=>'success','REJECTED'=>'danger'];
$statusText = ['NONE'=>'—','UPLOADED'=>'Diunggah','REVISION'=>'Revisi','ACCEPTED'=>'Diterima','REJECTED'=>'Ditolak'];
$badge      = $badgeMap[$status] ?? 'secondary';
$uploadedAt = !empty($submission['full_paper_uploaded_at']) ? date('d M Y H:i', strtotime($submission['full_paper_uploaded_at'])) : '—';
$revisiKe   = isset($submission['revisi_ke']) ? (int)$submission['revisi_ke'] : 0;

$eventId = (int)($submission['event_id'] ?? 0);
$backUrl = $eventId ? site_url('admin/kelola-paper/presenters/'.$eventId) : site_url('admin/kelola-paper');

$rvStatMap = [
  'diterima' => ['Diterima','success'],
  'rejected' => ['Ditolak','danger'],
  'ditolak'  => ['Ditolak','danger'],
  'revisi'   => ['Revisi','warning'],
  'pending'  => ['Pending','secondary'],
  ''         => ['—','secondary'],
  null       => ['—','secondary'],
];
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_admin') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill page-wrap-blue">
    <div class="container-xxl px-3 px-md-4 py-4">

      <!-- Top bar: Back + breadcrumb -->
      <div class="d-flex justify-content-between align-items-center mb-2">
        <div class="d-flex align-items-center gap-2">
          <a href="<?= esc($backUrl) ?>" class="btn btn-soft-dark btn-xs">
            <i class="bi bi-arrow-left"></i><span class="ms-1">Kembali</span>
          </a>
          <nav aria-label="breadcrumb" class="small">
            <ol class="breadcrumb mb-0">
              <li class="breadcrumb-item"><a href="<?= site_url('admin/kelola-paper') ?>">Kelola Paper</a></li>
              <?php if ($eventId): ?>
                <li class="breadcrumb-item">
                  <a href="<?= site_url('admin/kelola-paper/presenters/'.$eventId) ?>">
                    <?= esc($submission['event_title'] ?? 'Event') ?>
                  </a>
                </li>
              <?php endif; ?>
              <li class="breadcrumb-item active" aria-current="page">Detail Full Paper</li>
            </ol>
          </nav>
        </div>
      </div>

      <!-- HERO -->
      <div class="hero-blue card-glass mb-3 p-3 p-md-4 d-flex justify-content-between align-items-start gap-3">
        <div class="pe-3">
          <h3 class="hero-title mb-1">
            <i class="bi bi-journal-richtext me-2"></i><?= esc($title ?? 'Detail Full Paper') ?>
          </h3>
          <div class="text-white-75 small line-clip-2"><?= esc($submission['title'] ?? '-') ?></div>
        </div>
        <div class="text-end">
          <div class="mb-2"><span class="badge bg-<?= $badge ?>"><?= esc($statusText[$status] ?? $status) ?></span></div>
          <div class="text-white-75 small">Diunggah</div>
          <div class="fw-semibold text-white"><?= esc($uploadedAt) ?></div>
        </div>
      </div>

      <div class="row g-3">
        <!-- LEFT -->
        <div class="col-12 col-lg-8">
          <!-- Info -->
          <div class="card shadow-soft card-glass-plain mb-3">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
              <span class="badge bg-blue-soft"><i class="bi bi-info-circle"></i></span>
              <h6 class="mb-0 fw-semibold text-blue-900">Informasi Full Paper</h6>
            </div>
            <div class="card-body pt-2">
              <dl class="row mb-0">
                <dt class="col-md-3 text-muted">Diunggah</dt>
                <dd class="col-md-9"><?= esc($uploadedAt) ?></dd>

                <dt class="col-md-3 text-muted">Judul</dt>
                <dd class="col-md-9"><?= esc($submission['title'] ?? '-') ?></dd>

                <dt class="col-md-3 text-muted">Penulis</dt>
                <dd class="col-md-9">
                  <div class="fw-semibold"><?= esc($author['name'] ?? '-') ?></div>
                  <?php if (!empty($author['email'])): ?>
                    <small class="text-muted"><?= esc($author['email']) ?></small>
                  <?php endif; ?>
                </dd>

                <dt class="col-md-3 text-muted">Co-author</dt>
                <dd class="col-md-9">
                  <?php if (!empty($coauthors)): ?>
                    <div class="table-responsive">
                      <table class="table table-sm align-middle mb-0">
                        <thead class="table-light"><tr><th>Nama</th><th>Email</th><th>Afiliasi</th></tr></thead>
                        <tbody>
                          <?php foreach ($coauthors as $c): ?>
                            <tr>
                              <td><?= esc($c['nama'] ?? ($c['name'] ?? '-')) ?></td>
                              <td><?= esc($c['email'] ?? '-') ?></td>
                              <td><?= esc($c['afiliasi'] ?? ($c['affiliation'] ?? '-')) ?></td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                  <?php else: ?><span class="text-muted">—</span><?php endif; ?>
                </dd>

                <dt class="col-md-3 text-muted">Event</dt>
                <dd class="col-md-9"><?= esc($submission['event_title'] ?? '-') ?></dd>

                <dt class="col-md-3 text-muted">Revisi</dt>
                <dd class="col-md-9"><?= (int)$revisiKe ?></dd>

                <dt class="col-md-3 text-muted">Status</dt>
                <dd class="col-md-9"><span class="badge bg-<?= $badge ?>"><?= esc($statusText[$status] ?? $status) ?></span></dd>
              </dl>
            </div>
          </div>

          <!-- PDF Preview -->
          <div class="card shadow-soft card-glass-plain mb-3">
            <div class="card-header bg-transparent border-0 pb-0 d-flex justify-content-between align-items-center">
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-blue-soft"><i class="bi bi-file-earmark-pdf"></i></span>
                <h6 class="mb-0 fw-semibold text-blue-900">File Full Paper</h6>
              </div>
              <div class="d-flex gap-2">
                <a class="btn btn-soft-dark btn-xs" target="_blank"
                   href="<?= site_url('admin/fullpaper/download/'.(int)($submission['id'] ?? 0)) ?>">
                  <i class="bi bi-download me-1"></i>Download
                </a>
                <a class="btn btn-ghost btn-xs" target="_blank"
                   href="<?= site_url('admin/fullpaper/view/'.(int)($submission['id'] ?? 0)) ?>">
                  <i class="bi bi-box-arrow-up-right me-1"></i>Buka Tab
                </a>
              </div>
            </div>
            <div class="card-body pt-2">
              <div class="pdf-frame-wrap">
                <iframe id="pdfFrame" title="Preview Full Paper" class="pdf-frame" allow="fullscreen"></iframe>
              </div>
              <div id="pdfError" class="mt-2 small text-danger d-none">
                Gagal memuat preview.
                <a target="_blank" href="<?= site_url('admin/fullpaper/view/'.(int)($submission['id'] ?? 0)) ?>">Buka di tab baru</a>
                atau nonaktifkan IDM untuk situs ini.
              </div>
            </div>
          </div>

          <!-- Riwayat Penilaian (Full Paper) -->
          <div class="card shadow-soft card-glass-plain mb-3">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
              <span class="badge bg-blue-soft"><i class="bi bi-clipboard-check"></i></span>
              <h6 class="mb-0 fw-semibold text-blue-900">Riwayat Penilaian</h6>
            </div>
            <div class="card-body pt-2">
              <?php if (empty($fpReviews)): ?>
                <div class="p-4 text-center border rounded-3 bg-light-subtle">
                  <div class="mb-2"><i class="bi bi-inbox fs-3 text-secondary"></i></div>
                  <div class="text-muted">Belum ada penilaian.</div>
                </div>
              <?php else: ?>
                <div class="vstack gap-2">
                  <?php foreach ($fpReviews as $r):
                    $k = strtolower($r['keputusan'] ?? 'pending');
                    $m = $rvStatMap[$k] ?? ['—','secondary'];
                    $when = !empty($r['tanggal_review']) ? date('d M Y H:i', strtotime($r['tanggal_review'])) : '—';
                  ?>
                    <div class="p-3 border rounded-3 bg-white">
                      <div class="d-flex justify-content-between align-items-start">
                        <div>
                          <div class="fw-semibold"><?= esc($r['reviewer_name'] ?? '-') ?></div>
                          <div class="small text-muted"><?= esc($when) ?></div>
                        </div>
                        <span class="badge bg-<?= $m[1] ?>"><?= esc($m[0]) ?></span>
                      </div>
                      <?php if (!empty($r['komentar'])): ?>
                        <div class="mt-2 text-secondary"><?= nl2br(esc($r['komentar'])) ?></div>
                      <?php endif; ?>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Riwayat Revisi -->
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
                      <?php
                        $map = $badgeMap; $lab = $statusText; $no=1;
                        foreach ($history as $h):
                          $st = strtoupper($h['full_paper_status'] ?? 'NONE');
                          $cls = $map[$st] ?? 'secondary';
                          $ts  = !empty($h['full_paper_uploaded_at']) ? date('d M Y H:i', strtotime($h['full_paper_uploaded_at'])) : '—';
                      ?>
                        <tr>
                          <td><?= $no++ ?></td>
                          <td><?= (int)($h['revisi_ke'] ?? 0) ?></td>
                          <td><span class="badge bg-<?= $cls ?>"><?= esc($lab[$st] ?? $st) ?></span></td>
                          <td><?= esc($ts) ?></td>
                          <td>
                            <a class="btn btn-ghost btn-xs"
                               href="<?= site_url('admin/fullpaper/detail/'.(int)$h['id']) ?>">
                              <i class="bi bi-eye me-1"></i>Lihat
                            </a>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php else: ?>
                <div class="text-muted">Belum ada riwayat revisi.</div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- RIGHT -->
        <div class="col-12 col-lg-4">
          <!-- Reviewer Ditugaskan (Full Paper) -->
          <div class="card shadow-soft card-glass-plain mb-3">
            <div class="card-header bg-transparent border-0 pb-0 d-flex justify-content-between align-items-center">
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-blue-soft"><i class="bi bi-people"></i></span>
                <h6 class="mb-0 fw-semibold text-blue-900">Reviewer Ditugaskan</h6>
              </div>
              <span class="badge bg-secondary-subtle"><?= count($assigned) ?> / max <?= $maxReviewer ?></span>
            </div>
            <div class="card-body pt-2">
              <?php if (!empty($assigned)): ?>
                <div class="table-responsive mb-3">
                  <table class="table table-sm align-middle">
                    <thead class="table-light">
                      <tr>
                        <th>#</th><th>Nama</th><th>Email</th><th>Status</th><th>Update</th><th>Ditugaskan</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php $i=1; foreach ($assigned as $ar):
                        $k = strtolower($ar['status'] ?? 'pending');
                        $m = $rvStatMap[$k] ?? ['—','secondary'];
                        $stAt = !empty($ar['status_at']) ? date('d M Y H:i', strtotime($ar['status_at'])) : '—';
                        $asAt = !empty($ar['assigned_at']) ? date('d M Y H:i', strtotime($ar['assigned_at'])) : '—';
                      ?>
                        <tr>
                          <td><?= $i++ ?></td>
                          <td><?= esc($ar['name'] ?? '-') ?></td>
                          <td><?= esc($ar['email'] ?? '-') ?></td>
                          <td><span class="badge bg-<?= $m[1] ?>"><?= esc($m[0]) ?></span></td>
                          <td><?= esc($stAt) ?></td>
                          <td><?= esc($asAt) ?></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php else: ?>
                <div class="text-muted mb-2">Belum ada reviewer yang ditugaskan.</div>
              <?php endif; ?>

              <!-- Form tambah reviewer (kandidat sudah exclude assigned & reviewer abstrak) -->
              <form method="post" action="<?= site_url('admin/fullpaper/assign/'.(int)($submission['id'] ?? 0)) ?>" class="row g-2">
                <?= csrf_field() ?>
                <div class="col-12">
                  <label class="form-label">Tambah Reviewer</label>
                  <select name="reviewer_id" class="form-select" <?= count($assigned) >= $maxReviewer ? 'disabled' : '' ?> required>
                    <?php if (count($assigned) >= $maxReviewer): ?>
                      <option value="">Batas maksimum reviewer tercapai</option>
                    <?php else: ?>
                      <option value="">-- pilih --</option>
                      <?php if (!empty($reviewers)): ?>
                        <?php foreach ($reviewers as $rv): ?>
                          <option value="<?= (int)$rv['id'] ?>">
                            <?= esc(($rv['name'] ?? 'Reviewer').(!empty($rv['email']) ? ' — '.$rv['email'] : '')) ?>
                          </option>
                        <?php endforeach; ?>
                      <?php else: ?>
                        <option value="">Semua reviewer sudah ditugaskan / reviewer abstrak disembunyikan</option>
                      <?php endif; ?>
                    <?php endif; ?>
                  </select>
                  <div class="form-text">Kandidat otomatis menyembunyikan reviewer yang sudah meninjau abstrak dan yang sudah ditugaskan di full paper.</div>
                </div>
                <div class="col-12 d-grid">
                  <button class="btn btn-ghost" <?= count($assigned) >= $maxReviewer ? 'disabled' : '' ?>>
                    <i class="bi bi-person-plus me-1"></i>Tugaskan
                  </button>
                </div>
              </form>
              <small class="text-muted d-block mt-2">Maksimal <?= $maxReviewer ?> reviewer.</small>
            </div>
          </div>

          <!-- Reviewer Abstrak (read-only, sebagai info & sekaligus dijadikan pengecualian kandidat) -->
          <div class="card shadow-soft card-glass-plain mb-3">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
              <span class="badge bg-blue-soft"><i class="bi bi-people-fill"></i></span>
              <h6 class="mb-0 fw-semibold text-blue-900">Reviewer Abstrak (Informasi)</h6>
            </div>
            <div class="card-body pt-2">
              <?php if (empty($absReviewers)): ?>
                <div class="text-muted">Tidak ada data reviewer abstrak.</div>
              <?php else: ?>
                <div class="table-responsive">
                  <table class="table table-sm align-middle mb-0">
                    <thead class="table-light"><tr><th>#</th><th>Nama</th><th>Email</th></tr></thead>
                    <tbody>
                      <?php $i=1; foreach ($absReviewers as $rv): ?>
                        <tr>
                          <td><?= $i++ ?></td>
                          <td><?= esc($rv['name'] ?? '-') ?></td>
                          <td><?= esc($rv['email'] ?? '-') ?></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
                <div class="small text-muted mt-2">
                  Reviewer di atas tidak akan muncul pada kandidat penugasan full paper.
                </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Keputusan -->
          <div class="card shadow-soft card-glass-plain">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
              <span class="badge bg-blue-soft"><i class="bi bi-flag"></i></span>
              <h6 class="mb-0 fw-semibold text-blue-900">Keputusan</h6>
            </div>
            <div class="card-body pt-2">
              <form action="<?= site_url('admin/fullpaper/set-status/'.(int)($submission['id'] ?? 0)) ?>" method="post" class="vstack gap-2">
                <?= csrf_field() ?>
                <div>
                  <label class="form-label">Set Status</label>
                  <select class="form-select" name="status" required>
                    <?php foreach (['UPLOADED','REVISION','ACCEPTED','REJECTED'] as $opt): ?>
                      <option value="<?= $opt ?>" <?= $status===$opt?'selected':'' ?>><?= $statusText[$opt] ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <button class="btn btn-primary"><i class="bi bi-save me-1"></i>Simpan</button>
              </form>
              <small class="text-muted d-block mt-2">
                Status <b>Diterima</b> dapat memicu proses lanjutan sesuai kebutuhan sistem.
              </small>
            </div>
          </div>
        </div>
      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<style>
/* ====== Unified Blue UI (match “abstrak”) ====== */
:root{
  --blue-50:#eff6ff; --blue-200:#bfdbfe; --blue-600:#2563eb; --blue-700:#1d4ed8; --blue-800:#1e40af; --blue-900:#1e3a8a;
}
.page-wrap-blue{ background:linear-gradient(180deg,var(--blue-50),#fff 40%); min-height:100vh; padding-top:72px; }
.container-xxl{ max-width:1400px; }
.hero-blue{
  background:radial-gradient(1200px 400px at 10% -20%,var(--blue-600) 0,var(--blue-700) 40%,var(--blue-800) 100%)!important;
  color:#fff!important; border-radius:16px; border:1px solid rgba(255,255,255,.15);
  box-shadow:0 12px 28px rgba(30,64,175,.10);
}
.hero-title{ font-weight:800; letter-spacing:.25px; font-size:1.45rem; }
.text-white-75{ color:rgba(255,255,255,.85)!important; }
.card-glass-plain{ backdrop-filter:blur(6px); background:rgba(255,255,255,.94); border-radius:14px; border:1px solid rgba(30,64,175,.10); }
.shadow-soft{ box-shadow:0 10px 24px rgba(30,64,175,.08); }
.bg-blue-soft{ background:var(--blue-200); color:var(--blue-900); border-radius:12px; padding:.35rem .55rem; font-weight:600; font-size:.82rem; }
.bg-secondary-subtle{ background:#f1f5f9!important; color:#475569!important; }
.bg-primary-subtle{ background:#dbeafe!important; color:var(--blue-700)!important; }
.text-blue-900{ color:var(--blue-900)!important; }

.table{ width:100%; table-layout:fixed; border-collapse:separate; border-spacing:0; }
.table thead th{
  background-color:#f8fafc!important; border-bottom:1px solid #e5e7eb;
  font-weight:600; color:var(--blue-900); font-size:.82rem; text-transform:uppercase; letter-spacing:.3px;
  padding:.55rem .5rem; white-space:nowrap;
}
.table tbody td{ padding:.55rem .5rem; vertical-align:middle; border-top:none; word-break:break-word; white-space:normal; }
.line-clip-2{ display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }

.btn{ border-radius:10px; font-weight:600; }
.btn-xs{ padding:.32rem .55rem; font-size:.8rem; line-height:1; border-radius:8px; }
.btn-soft-dark{ background:#f1f5f9; color:#111827; border:1px solid #e2e8f0; transition:all .15s; }
.btn-soft-dark:hover{ background:#111827; color:#fff; border-color:#111827; }
.btn-ghost{ background:transparent; color:#334155; border:1px solid #cbd5e1; transition:all .15s; }
.btn-ghost:hover{ background:#0f172a; color:#fff; border-color:#0f172a; }

/* PDF frame */
.pdf-frame-wrap{ height:72vh; border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; }
.pdf-frame{ width:100%; height:100%; border:0; }

/* Breadcrumb separator (tanpa garis bawaan navbar) */
.breadcrumb .breadcrumb-item + .breadcrumb-item::before{ content: ">"; }

/* ====== Fix “garis” di navbar (hilangkan border/shadow bawaan) ====== */
.navbar, .app-navbar, header .navbar{
  border-bottom: none !important;
  box-shadow: none !important;
}
.navbar .dropdown-menu{ box-shadow:0 10px 24px rgba(30,64,175,.08); }

/* Responsive */
@media (max-width: 768px){
  .table thead th, .table tbody td{ padding:.48rem .42rem; font-size:.9rem; }
  .btn-xs{ padding:.3rem .5rem; font-size:.78rem; }
  .pdf-frame-wrap{ height:60vh; }
}
</style>

<script>
/* Preview anti-IDM: fetch → Blob → objectURL */
(function(){
  const iframe = document.getElementById('pdfFrame');
  const errBox = document.getElementById('pdfError');
  const url    = "<?= site_url('admin/fullpaper/blob/'.(int)($submission['id'] ?? 0)) ?>?t=" + Date.now();

  fetch(url, { credentials: 'same-origin' })
    .then(res => { if (!res.ok) throw new Error('HTTP ' + res.status); return res.arrayBuffer(); })
    .then(buf => {
      const blob = new Blob([buf], { type: 'application/pdf' });
      const blobUrl = URL.createObjectURL(blob);
      iframe.src = blobUrl + '#toolbar=1&navpanes=0';
    })
    .catch(e => { console.error('PDF preview error:', e); if (errBox) errBox.classList.remove('d-none'); });

  window.addEventListener('beforeunload', () => {
    try { URL.revokeObjectURL(iframe.src); } catch(_) {}
  });
})();
</script>
