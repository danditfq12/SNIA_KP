<?php
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

$status     = strtoupper($submission['full_paper_status'] ?? 'NONE');
$badgeMap   = ['NONE'=>'secondary','UPLOADED'=>'info','REVISION'=>'warning','ACCEPTED'=>'success','REJECTED'=>'danger'];
$statusText = ['NONE'=>'—','UPLOADED'=>'Diunggah','REVISION'=>'Revisi','ACCEPTED'=>'Diterima','REJECTED'=>'Ditolak'];
$badge      = $badgeMap[$status] ?? 'secondary';
$uploadedAt = !empty($submission['full_paper_uploaded_at']) ? date('d M Y H:i', strtotime($submission['full_paper_uploaded_at'])) : '—';
$revisiKe   = isset($submission['revisi_ke']) ? (int)$submission['revisi_ke'] : 0;

$eventId = (int)($submission['event_id'] ?? 0);
$backUrl = $eventId ? site_url('admin/kelola-paper/detail/'.$eventId) : site_url('admin/kelola-paper');

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

$absBadgeMap = [
  'diterima'=>'success','ditolak'=>'danger','revisi'=>'warning',
  'menunggu'=>'secondary','sedang_direview'=>'info',''=>'secondary'
];

$assignedCount  = count($assigned);
$completedCount = 0;
foreach ($assigned as $ar) {
  $st = strtolower($ar['status'] ?? '');
  if (in_array($st, ['diterima','accepted','revisi','revision','ditolak','rejected'], true)) $completedCount++;
}
$quotaFull = $assignedCount >= $maxReviewer;

$submissionId = (int)($submission['id'] ?? 0);
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_admin') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill page-wrap-blue">
    <div class="container-xxl px-3 px-md-4 py-4">

      <div class="d-flex justify-content-between align-items-center mb-2">
        <a href="<?= esc($backUrl) ?>" class="btn btn-soft-dark btn-xs">
          <i class="bi bi-arrow-left"></i><span class="ms-1">Kembali</span>
        </a>
        <button class="btn btn-primary btn-xs" data-bs-toggle="modal" data-bs-target="#statusModal">
          <i class="bi bi-flag me-1"></i>Keputusan
        </button>
      </div>

      <div class="hero-blue card-glass mb-3 p-3 p-md-4 d-flex justify-content-between align-items-start gap-3">
        <div class="pe-3">
          <h3 class="hero-title mb-1">
            <i class="bi bi-journal-richtext me-2"></i><?= esc($title ?? 'Detail Full Paper') ?>
          </h3>
          <div class="text-white-75 small line-clip-2"><?= esc($submission['title'] ?? '-') ?></div>
        </div>
        <div class="text-end">
          <div class="mb-2">
            <span class="badge bg-<?= $badge ?>"><?= esc($statusText[$status] ?? $status) ?></span>
          </div>
          <div class="text-white-75 small">Diunggah</div>
          <div class="fw-semibold text-white"><?= esc($uploadedAt) ?></div>
        </div>
      </div>

      <div class="row g-3">
        <!-- LEFT -->
        <div class="col-12 col-lg-8">
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

          <div class="card shadow-soft card-glass-plain mb-3">
            <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
              <button class="btn btn-soft-dark btn-xs d-inline-flex align-items-center gap-2"
                      data-bs-toggle="collapse" data-bs-target="#fpCollapse" aria-expanded="false" aria-controls="fpCollapse" id="fpToggle">
                <i class="bi bi-caret-down-square"></i>
                <span>File Full Paper</span>
              </button>
              <div class="d-flex gap-2">
                <a class="btn btn-soft-dark btn-xs" target="_blank"
                   href="<?= site_url('admin/fullpaper/download/'.$submissionId) ?>">
                  <i class="bi bi-download me-1"></i>Download
                </a>
                <a class="btn btn-ghost btn-xs" target="_blank"
                   href="<?= site_url('admin/fullpaper/view/'.$submissionId) ?>">
                  <i class="bi bi-box-arrow-up-right me-1"></i>Buka Tab
                </a>
              </div>
            </div>
            <div id="fpCollapse" class="collapse" data-pdf-loaded="0">
              <div class="card-body pt-2">
                <div class="pdf-frame-wrap">
                  <iframe id="pdfFrame" title="Preview Full Paper" class="pdf-frame" allow="fullscreen"></iframe>
                </div>
                <div id="pdfError" class="mt-2 small text-danger d-none">
                  Gagal memuat preview.
                  <a target="_blank" href="<?= site_url('admin/fullpaper/view/'.$submissionId) ?>">Buka di tab baru</a>.
                </div>
              </div>
            </div>
          </div>

          <!-- Riwayat Penilaian (FP) -->
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
                <div class="text-muted">Belum ada riwayat revisi.</div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- RIGHT -->
        <div class="col-12 col-lg-4">
          <div class="card shadow-soft card-glass-plain mb-3">
            <div class="card-body">
              <div class="metrics-wrap">
                <div class="metric-pill">
                  <div class="label"><i class="bi bi-people me-1"></i>Ditugaskan</div>
                  <div class="value">
                    <?= number_format($assignedCount) ?> / <?= number_format($maxReviewer) ?>
                    <?php if ($quotaFull): ?><span class="badge bg-success-subtle ms-1">Penuh</span><?php endif; ?>
                  </div>
                </div>
                <div class="metric-pill">
                  <div class="label"><i class="bi bi-journal-check me-1"></i>Review masuk</div>
                  <div class="value"><?= number_format($completedCount) ?></div>
                </div>
              </div>
            </div>
          </div>

          <!-- Reviewer Ditugaskan -->
          <div class="card shadow-soft card-glass-plain mb-3">
            <div class="card-header bg-transparent border-0 pb-0 d-flex justify-content-between align-items-center">
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-blue-soft"><i class="bi bi-people"></i></span>
                <h6 class="mb-0 fw-semibold text-blue-900">Reviewer Ditugaskan</h6>
              </div>
              <span class="badge bg-secondary-subtle"><?= $assignedCount ?> / max <?= $maxReviewer ?><?= $quotaFull ? ' — Penuh' : '' ?></span>
            </div>
            <div class="card-body pt-2">
              <?php if (!empty($assigned)): ?>
                <div class="table-responsive mb-3">
                  <table class="table table-sm align-middle">
                    <thead class="table-light">
                      <tr><th>#</th><th>Nama</th><th>Email</th><th>Tugas</th><th>Status Review</th><th>Update</th><th>Ditugaskan</th></tr>
                    </thead>
                    <tbody>
                      <?php $i=1; foreach ($assigned as $ar):
                        $k = strtolower($ar['status'] ?? 'pending');
                        $m = $rvStatMap[$k] ?? ['—','secondary'];
                        $stAt = !empty($ar['status_at']) ? date('d M Y H:i', strtotime($ar['status_at'])) : '—';
                        $asAt = !empty($ar['assigned_at']) ? date('d M Y H:i', strtotime($ar['assigned_at'])) : '—';
                        $assign = strtolower((string)($ar['assignment_status'] ?? 'pending'));
                        $assignMap = [
                          'accepted'=>['Diterima','primary'],
                          'declined'=>['Menolak','secondary'],
                          'pending' =>['Pending','secondary'],
                        ];
                        $am = $assignMap[$assign] ?? ['Pending','secondary'];
                      ?>
                        <tr>
                          <td><?= $i++ ?></td>
                          <td><?= esc($ar['name'] ?? '-') ?></td>
                          <td><?= esc($ar['email'] ?? '-') ?></td>
                          <td><span class="badge bg-<?= $am[1] ?>"><?= esc($am[0]) ?></span></td>
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

              <?php if (!$quotaFull): ?>
                <form method="post" action="<?= site_url('admin/fullpaper/assign/'.$submissionId) ?>" class="row g-2">
                  <?= csrf_field() ?>
                  <div class="col-12">
                    <label class="form-label">Tambah Reviewer</label>
                    <select name="reviewer_id" class="form-select" required>
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
                    <div class="form-text">Kandidat otomatis menyembunyikan reviewer yang sudah ditugaskan.</div>
                  </div>
                  <div class="col-12 d-grid">
                    <button class="btn btn-primary"><i class="bi bi-person-plus me-1"></i>Tugaskan</button>
                  </div>
                </form>
                <small class="text-muted d-block mt-2">Maksimal <?= $maxReviewer ?> reviewer.</small>
              <?php else: ?>
                <div class="alert alert-success-subtle border-0 mt-2 mb-0">Kuota reviewer penuh.</div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Reviewer Abstrak -->
          <div class="card shadow-soft card-glass-plain">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
              <span class="badge bg-blue-soft"><i class="bi bi-people-fill"></i></span>
              <h6 class="mb-0 fw-semibold text-blue-900">Reviewer Abstrak</h6>
              <?php if ($abstractStatus !== ''): ?>
                <span class="ms-2 badge bg-<?= $absBadgeMap[$abstractStatus] ?? 'secondary' ?>">
                  Status Abstrak: <?= esc(ucfirst(str_replace('_',' ', $abstractStatus))) ?>
                </span>
              <?php endif; ?>
            </div>
            <div class="card-body pt-2">
              <?php if (empty($absReviewers)): ?>
                <div class="text-muted">Tidak ada data reviewer abstrak.</div>
              <?php else: ?>
                <div class="table-responsive">
                  <table class="table table-sm align-middle mb-0">
                    <thead class="table-light"><tr><th>#</th><th>Nama</th><th>Email</th><th>Status Review Abstrak</th></tr></thead>
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
                <div class="small text-muted mt-2">Informasi tahap abstrak; Anda boleh menugaskan reviewer yang sama di full paper.</div>
              <?php endif; ?>
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
  --side-pad: clamp(1rem, 1.6vw, 1.6rem);
  --container-max: 1680px;
  --blue-50:#eff6ff; --blue-200:#bfdbfe; --blue-600:#2563eb; --blue-700:#1d4ed8; --blue-800:#1e40af; --blue-900:#1e3a8a;
}
.page-wrap-blue{ background:linear-gradient(180deg,var(--blue-50),#fff 40%); min-height:100vh; padding-top:72px; }
.container-xxl{ max-width:min(100%, var(--container-max)); padding-left:var(--side-pad)!important; padding-right:var(--side-pad)!important; }

.hero-blue{
  background:radial-gradient(1200px 400px at 10% -20%,var(--blue-600) 0,var(--blue-700) 40%,var(--blue-800) 100%)!important;
  color:#fff!important; border-radius:16px; border:1px solid rgba(255,255,255,.15);
  box-shadow:0 12px 28px rgba(30,64,175,.10);
}
.hero-title{ font-weight:800; letter-spacing:.25px; font-size:1.6rem; }
.text-white-75{ color:rgba(255,255,255,.85)!important; }
.card-glass-plain{ backdrop-filter:blur(6px); background:rgba(255,255,255,.94); border-radius:14px; border:1px solid rgba(30,64,175,.10); }
.shadow-soft{ box-shadow:0 10px 24px rgba(30,64,175,.08); }
.bg-blue-soft{ background:var(--blue-200); color:var(--blue-900); border-radius:12px; padding:.35rem .55rem; font-weight:600; font-size:.82rem; }
.bg-secondary-subtle{ background:#f1f5f9!important; color:#475569!important; }
.bg-primary-subtle{ background:#dbeafe!important; color:var(--blue-700)!important; }
.text-blue-900{ color:var(--blue-900)!important; }
.line-clip-2{ display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }

.btn{ border-radius:12px; font-weight:700; }
.btn-xs{ padding:.32rem .55rem; font-size:.8rem; line-height:1; border-radius:8px; }
.btn-soft-dark{ background:#f1f5f9; color:#111827; border:1px solid #e2e8f0; transition:all .15s; }
.btn-soft-dark:hover{ background:#111827; color:#fff; border-color:#111827; }
.btn-ghost{ background:transparent; color:#334155; border:1px solid #cbd5e1; transition:all .15s; }
.btn-ghost:hover{ background:#0f172a; color:#fff; border-color:#0f172a; }
.btn-primary{ background:var(--blue-600); border-color:var(--blue-600); box-shadow:0 4px 12px rgba(37,99,235,.2); }

.table{ width:100%; table-layout:fixed; border-collapse:separate; border-spacing:0; }
.table thead th{
  background-color:#f8fafc!important; border-bottom:1px solid #e5e7eb;
  font-weight:700; color:var(--blue-900); font-size:.82rem; text-transform:uppercase; letter-spacing:.3px;
  padding:.55rem .5rem; white-space:nowrap;
}
.table tbody td{ padding:.55rem .5rem; vertical-align:middle; border-top:none; word-break:break-word; white-space:normal; }

.pdf-frame-wrap{ height:72vh; border:1px solid #e5e7eb; border-radius:12px; overflow:hidden; }
.pdf-frame{ width:100%; height:100%; border:0; }

.metrics-wrap{ display:grid; grid-template-columns:repeat(2,1fr); gap:.6rem; }
.metric-pill{
  border:1px solid rgba(30,64,175,.12);
  background:linear-gradient(180deg,#fff,rgba(255,255,255,.96));
  border-radius:12px; padding:.6rem .7rem;
  display:flex; flex-direction:column; align-items:flex-start; justify-content:center; min-height:68px;
}
.metric-pill .label{ font-size:.8rem; color:#64748b; font-weight:700; }
.metric-pill .value{ font-size:1.08rem; font-weight:800; color:var(--blue-900); line-height:1.2; }

@media (max-width: 768px){
  .hero-title{ font-size:1.35rem; }
  .table thead th, .table tbody td{ padding:.48rem .42rem; font-size:.9rem; }
  .btn-xs{ padding:.3rem .5rem; font-size:.78rem; }
  .pdf-frame-wrap{ height:60vh; }
}
</style>

<script>
(function(){
  const collapse = document.getElementById('fpCollapse');
  const iframe   = document.getElementById('pdfFrame');
  const errBox   = document.getElementById('pdfError');
  let blobUrl = null;

  collapse?.addEventListener('shown.bs.collapse', () => {
    const loaded = collapse.getAttribute('data-pdf-loaded') === '1';
    if (loaded) return;
    const url = "<?= site_url('admin/fullpaper/blob/'.$submissionId) ?>?t=" + Date.now();
    fetch(url, { credentials: 'same-origin' })
      .then(res => { if (!res.ok) throw new Error('HTTP ' + res.status); return res.arrayBuffer(); })
      .then(buf => {
        const blob = new Blob([buf], { type: 'application/pdf' });
        blobUrl = URL.createObjectURL(blob);
        iframe.src = blobUrl + '#toolbar=1&navpanes=0';
        collapse.setAttribute('data-pdf-loaded','1');
      })
      .catch(e => { console.error(e); errBox?.classList.remove('d-none'); });
  });

  collapse?.addEventListener('hidden.bs.collapse', () => {
    try { if (blobUrl) URL.revokeObjectURL(blobUrl); } catch(_) {}
    iframe.src = 'about:blank';
    collapse.setAttribute('data-pdf-loaded','0');
    blobUrl = null;
  });
})();
</script>
