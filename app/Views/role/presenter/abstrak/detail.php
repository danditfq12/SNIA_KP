<?php
$title        = $title ?? 'Detail Abstrak';
$abs          = $abs ?? [];
$event        = $event ?? [];
$status       = strtolower($status ?? ($abs['status'] ?? 'menunggu'));
$badge        = $badge ?? 'secondary';

$showReuploadAbstract = (bool)($showReuploadAbstract ?? false);
$showCancel           = (bool)($showCancel ?? false);

$assignedReviewer = $assignedReviewer ?? ['name'=>null,'email'=>null,'org'=>null];
$reviewComments   = $reviewComments   ?? [];   // fallback lama (string list)
$reviewList       = $reviewList       ?? [];   // ✅ NEW (kaya metadata)
$contributors     = $contributors     ?? [];

// ✅ optional gating dari controller; fallback: true kalau status 'revisi'
$canUploadRevision = (bool)($canUploadRevision ?? ($status === 'revisi'));

$fmtDate = fn($s) => $s ? date('d M Y', strtotime($s)) : '-';
$fmtDT   = fn($s) => $s ? date('d M Y H:i', strtotime($s)) : '-';
$formatLabel = function($f){
  $f = strtolower((string)$f);
  return $f === 'both' ? 'Hybrid' : ucfirst($f ?: '-');
};
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill page-wrap-blue">
    <div class="container-xxl px-3 px-md-4 py-4">

      <!-- HERO -->
      <div class="hero-blue card-glass-plain mb-3 p-3 p-md-4 d-flex justify-content-between align-items-start gap-3">
        <div>
          <h3 class="hero-title mb-1"><i class="bi bi-journal-text me-2"></i>Detail Abstrak</h3>
          <div class="text-white-75 small">
            <?= esc($event['title'] ?? '-') ?>
            <?php if(!empty($abs['judul'])): ?>
              <span class="d-inline-block ms-2 opacity-90">•</span>
              <span class="fw-semibold opacity-90 ms-1"><?= esc($abs['judul']) ?></span>
            <?php endif; ?>
          </div>
        </div>
        <div class="text-end">
          <a href="javascript:history.back()" class="btn btn-light btn-sm shadow-sm">
            <i class="bi bi-arrow-left"></i> Kembali
          </a>
        </div>
      </div>

      <div class="row g-3">
        <!-- KIRI -->
        <div class="col-12 col-xl-8">
          <!-- Informasi Abstrak -->
          <div class="card shadow-soft card-glass-plain mb-3 card-accent">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
              <span class="badge bg-blue-soft"><i class="bi bi-info-circle"></i></span>
              <h6 class="mb-0 fw-semibold text-blue-900">Informasi Abstrak</h6>
            </div>
            <div class="card-body">
              <ul class="meta-list mb-3">
                <li><span>Judul</span><strong class="text-blue-900"><?= esc($abs['judul'] ?? '-') ?></strong></li>
                <li><span>Kategori</span><strong class="text-blue-900"><?= esc($abs['nama_kategori'] ?? '-') ?></strong></li>
                <li>
                  <span>Tanggal Unggah</span>
                  <strong class="text-blue-900">
                    <?= !empty($abs['tanggal_upload']) ? date('d M Y H:i', strtotime($abs['tanggal_upload'])) : '-' ?>
                  </strong>
                </li>
              </ul>

              <div class="pt-3 d-flex flex-wrap gap-2 border-top subtle-divider">
                <?php /* Presenter tidak perlu tombol download file di sini */ ?>

                <?php if ($showReuploadAbstract && $status === 'ditolak'): ?>
                  <a class="btn btn-danger" href="<?= site_url('/presenter/abstrak/create/'.(int)($abs['event_id'] ?? 0)) ?>">
                    <i class="bi bi-upload"></i> Upload Ulang Abstrak
                  </a>
                <?php endif; ?>

                <?php if ($showCancel): ?>
                  <form id="cancelForm-<?= (int)$abs['id_abstrak'] ?>"
                        action="<?= site_url('/presenter/abstrak/cancel/'.(int)$abs['id_abstrak']) ?>"
                        method="POST" class="m-0">
                    <?= csrf_field() ?>
                    <button type="button"
                            class="btn btn-outline-danger"
                            data-bs-toggle="modal"
                            data-bs-target="#confirmCancelModal"
                            data-form-id="cancelForm-<?= (int)$abs['id_abstrak'] ?>"
                            data-message="Batalkan upload abstrak ini?">
                      <i class="bi bi-x-circle"></i> Batalkan Upload
                    </button>
                  </form>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- Upload Revisi (muncul hanya saat status revisi) -->
          <?php if ($status === 'revisi'): ?>
            <?php if ($canUploadRevision): ?>
              <div id="section-revisi" class="card shadow-soft card-glass-plain mb-3 card-accent">
                <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
                  <span class="badge bg-blue-soft"><i class="bi bi-arrow-repeat"></i></span>
                  <h6 class="mb-0 fw-semibold text-blue-900">Upload Revisi Abstrak</h6>
                </div>
                <div class="card-body">
                  <form action="<?= site_url('/presenter/abstrak/revisi/'.(int)($abs['id_abstrak'] ?? 0)) ?>"
                        method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <?= csrf_field() ?>
                    <div class="mb-3">
                      <label class="form-label">File Abstrak (PDF) <span class="text-danger">*</span></label>
                      <input type="file" class="form-control form-control-soft" name="file_abstrak" accept=".pdf,application/pdf" required>
                      <div class="form-text">Format PDF, maksimal 5MB.</div>
                      <div class="invalid-feedback">File PDF wajib diunggah.</div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                      <i class="bi bi-send me-1"></i> Kirim Revisi
                    </button>
                  </form>
                </div>
              </div>
            <?php else: ?>
              <div class="card shadow-soft card-glass-plain mb-3 card-accent">
                <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
                  <span class="badge bg-blue-soft"><i class="bi bi-lock"></i></span>
                  <h6 class="mb-0 fw-semibold text-blue-900">Revisi Ditutup</h6>
                </div>
                <div class="card-body">
                  <div class="alert alert-warning mb-0 fw-semibold">
                    <i class="bi bi-exclamation-triangle me-1"></i> Batas waktu pengumpulan revisi telah berakhir.
                  </div>
                </div>
              </div>
            <?php endif; ?>
          <?php endif; ?>

          <!-- Komentar Reviewer (TIMELINE) -->
          <div class="card shadow-soft card-glass-plain mb-3">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
              <span class="badge bg-blue-soft"><i class="bi bi-chat-left-dots"></i></span>
              <h6 class="mb-0 fw-semibold text-blue-900">Komentar Reviewer</h6>
            </div>
            <div class="card-body">
              <?php if (!empty($reviewList)): ?>
                <div class="review-timeline">
                  <?php foreach ($reviewList as $rv): ?>
                    <?php
                      $rk   = isset($rv['revisi_ke']) ? (int)$rv['revisi_ke'] : null;
                      $kep  = strtolower($rv['keputusan'] ?? 'pending');
                      $kLab = [
                        'diterima' => ['success','Diterima'],
                        'ditolak'  => ['danger','Ditolak'],
                        'revisi'   => ['primary','Revisi'],
                        'sedang_direview' => ['info','Sedang direview'],
                        'pending'  => ['secondary','Pending'],
                      ][$kep] ?? ['secondary', ucfirst($kep ?: 'pending')];

                      $revName  = trim((string)($rv['reviewer_name'] ?? ''));
                      $revEmail = trim((string)($rv['reviewer_email'] ?? ''));
                      $tgl      = $rv['tanggal_review'] ?? null;
                    ?>
                    <div class="review-item">
                      <div class="review-dot"></div>
                      <div class="review-card">
                        <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-1">
                          <div class="d-flex flex-wrap align-items-center gap-2">
                            <span class="badge rounded-pill bg-light text-blue-900 border">
                              Revisi: <strong class="ms-1"><?= $rk !== null ? (int)$rk : '—' ?></strong>
                            </span>
                            <span class="badge rounded-pill bg-<?= esc($kLab[0]) ?>-subtle text-<?= esc($kLab[0]) ?>">
                              <?= esc($kLab[1]) ?>
                            </span>
                          </div>
                          <div class="text-muted small"><?= $tgl ? date('d M Y H:i', strtotime($tgl)) : '-' ?></div>
                        </div>

                        <div class="d-flex align-items-start gap-2 mb-2">
                          <div class="avatar-badge sm">
                            <span><?= esc(strtoupper(mb_substr($revName !== '' ? $revName : 'R', 0, 1))) ?></span>
                          </div>
                          <div>
                            <div class="fw-semibold text-blue-900"><?= esc($revName !== '' ? $revName : 'Reviewer') ?></div>
                            <?php if ($revEmail !== ''): ?>
                              <div class="small"><i class="bi bi-envelope me-1"></i>
                                <a class="text-primary" href="mailto:<?= esc($revEmail) ?>"><?= esc($revEmail) ?></a>
                              </div>
                            <?php endif; ?>
                          </div>
                        </div>

                        <div class="review-text"><?= nl2br(esc($rv['komentar'] ?? '')) ?></div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php else: ?>
                <?php if (!empty($reviewComments)): ?>
                  <ul class="list-unstyled m-0">
                    <?php foreach ($reviewComments as $c): if (trim($c)==='') continue; ?>
                      <li class="mb-2 d-flex align-items-start">
                        <i class="bi bi-dot fs-4 me-1 opacity-75"></i>
                        <span><?= nl2br(esc($c)) ?></span>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                <?php else: ?>
                  <?php if ($status === 'sedang_direview' || $status === 'menunggu'): ?>
                    <div class="empty-hint">
                      <i class="bi bi-hourglass-split me-1"></i> Belum ada komentar dari reviewer.
                    </div>
                  <?php elseif ($status === 'ditolak'): ?>
                    <div class="empty-hint">
                      <i class="bi bi-info-circle me-1"></i> Abstrak ditolak, namun komentar tidak tersedia.
                    </div>
                  <?php else: ?>
                    <div class="empty-hint">
                      <i class="bi bi-info-circle me-1"></i> Tidak ada komentar tersimpan.
                    </div>
                  <?php endif; ?>
                <?php endif; ?>
              <?php endif; ?>
            </div>
          </div>

          <!-- Info Event -->
          <div class="card shadow-soft card-glass-plain card-accent">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
              <span class="badge bg-blue-soft"><i class="bi bi-calendar-week"></i></span>
              <h6 class="mb-0 fw-semibold text-blue-900">Info Event</h6>
            </div>
            <div class="card-body">
              <ul class="meta-list">
                <li>
                  <span>Tanggal Event</span>
                  <strong class="text-blue-900"><?= isset($event['event_date']) ? date('d M Y', strtotime($event['event_date'])) : '-' ?></strong>
                </li>
                <li>
                  <span>Deadline Abstrak</span>
                  <strong class="text-blue-900"><?= !empty($event['abstract_deadline']) ? date('d M Y H:i', strtotime($event['abstract_deadline'])) : '-' ?></strong>
                </li>
                <li>
                  <span>Deadline Full Paper</span>
                  <strong class="text-blue-900"><?= !empty($event['full_paper_deadline']) ? date('d M Y H:i', strtotime($event['full_paper_deadline'])) : '-' ?></strong>
                </li>
              </ul>
            </div>
          </div>
        </div>

        <!-- KANAN -->
        <div class="col-12 col-xl-4">
          <!-- Status -->
          <div class="card shadow-soft card-glass-plain sticky-card mb-3">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
              <span class="badge bg-blue-soft"><i class="bi bi-flag"></i></span>
              <h6 class="mb-0 fw-semibold text-blue-900">Status</h6>
            </div>
            <div class="card-body">
              <?php if ($status === 'menunggu' || $status === 'sedang_direview'): ?>
                <div class="alert mb-0 bg-blue-soft-2 text-blue-900 border-0 fw-semibold">
                  <i class="bi bi-hourglass-split me-1"></i> Abstrak Anda sedang diproses oleh reviewer.
                </div>
              <?php elseif ($status === 'revisi'): ?>
                <div class="alert alert-primary mb-0 fw-semibold">
                  <i class="bi bi-arrow-repeat me-1"></i> Perlu revisi. Silakan unggah file revisi pada form di bawah.
                </div>
              <?php elseif ($status === 'ditolak'): ?>
                <div class="alert alert-danger mb-0 fw-semibold">
                  <i class="bi bi-x-octagon me-1"></i> Abstrak ditolak. Silakan unggah ulang melalui halaman kirim abstrak.
                </div>
              <?php elseif ($status === 'diterima'): ?>
                <div class="alert alert-success mb-0 fw-semibold">
                  <i class="bi bi-check2-circle me-1"></i> Abstrak diterima.
                </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Reviewer Ditugaskan -->
          <div class="card shadow-soft card-glass-plain sticky-card mb-3">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
              <span class="badge bg-blue-soft"><i class="bi bi-person-badge"></i></span>
              <h6 class="mb-0 fw-semibold text-blue-900">Reviewer Ditugaskan</h6>
            </div>
            <div class="card-body">
              <?php if (!empty($assignedReviewer['name']) || !empty($assignedReviewer['email']) || !empty($assignedReviewer['org'])): ?>
                <div class="d-flex align-items-center gap-3 mb-2">
                  <div class="avatar-badge">
                    <span><?= esc(strtoupper(mb_substr($assignedReviewer['name'] ?? 'R', 0, 1))) ?></span>
                  </div>
                  <div class="flex-fill">
                    <div class="fw-bold text-blue-900"><?= esc($assignedReviewer['name'] ?? '—') ?></div>
                    <?php if(!empty($assignedReviewer['org'])): ?>
                      <div class="text-muted small"><?= esc($assignedReviewer['org']) ?></div>
                    <?php endif; ?>
                    <?php if(!empty($assignedReviewer['email'])): ?>
                      <div class="small"><i class="bi bi-envelope me-1"></i>
                        <a href="mailto:<?= esc($assignedReviewer['email']) ?>" class="text-primary">
                          <?= esc($assignedReviewer['email']) ?>
                        </a>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
              <?php else: ?>
                <div class="empty-hint"><i class="bi bi-info-circle me-1"></i> Reviewer belum ditetapkan.</div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Kontributor -->
          <?php if (!empty($contributors)): ?>
          <div class="card shadow-soft card-glass-plain sticky-card">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
              <span class="badge bg-blue-soft"><i class="bi bi-people"></i></span>
              <h6 class="mb-0 fw-semibold text-blue-900">Kontributor</h6>
            </div>
            <div class="card-body">
              <?php foreach ($contributors as $c): ?>
                <div class="contrib-item">
                  <div class="avatar-badge">
                    <span><?= esc(strtoupper(mb_substr($c['name'] ?? 'C', 0, 1))) ?></span>
                  </div>
                  <div>
                    <div class="fw-semibold text-blue-900"><?= esc($c['name'] ?? '—') ?></div>
                    <?php if (!empty($c['affiliation'])): ?>
                      <div class="text-muted small"><i class="bi bi-building me-1"></i><?= esc($c['affiliation']) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($c['email'])): ?>
                      <div class="small"><i class="bi bi-envelope me-1"></i><a class="text-primary" href="mailto:<?= esc($c['email']) ?>"><?= esc($c['email']) ?></a></div>
                    <?php endif; ?>
                    <?php if (!empty($c['presenter'])): ?>
                      <span class="badge bg-blue-soft mt-1">Presenter</span>
                    <?php endif; ?>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
          <?php endif; ?>
        </div>
      </div>

    </div>

    <!-- Modal Konfirmasi Batalkan Upload -->
    <div class="modal fade" id="confirmCancelModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
          <div class="modal-header bg-light">
            <h6 class="modal-title"><i class="bi bi-exclamation-triangle me-2 text-danger"></i>Konfirmasi</h6>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
          </div>
          <div class="modal-body">
            <p id="confirmCancelText" class="mb-0">Batalkan upload abstrak?</p>
            <small class="text-muted">Tindakan ini akan menghapus file dan data unggahan Anda.</small>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Tidak</button>
            <button id="confirmCancelBtn" type="button" class="btn btn-danger">
              <i class="bi bi-x-circle me-1"></i> Ya, Batalkan
            </button>
          </div>
        </div>
      </div>
    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<style>
:root{
  --blue-50:#eff6ff; --blue-100:#dbeafe; --blue-200:#bfdbfe;
  --blue-300:#93c5fd; --blue-400:#60a5fa; --blue-500:#3b82f6;
  --blue-600:#2563eb; --blue-700:#1d4ed8; --blue-800:#1e40af; --blue-900:#1e3a8a;
  --side-pad: clamp(1rem, 2.3vw, 2.2rem);
  --gutter-x: 1.05rem;
}

body{ font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size:15.5px; line-height:1.6; }

/* BG */
.page-wrap-blue{
  min-height:100vh; padding-top:72px; position:relative;
  background:
    radial-gradient(900px 300px at 20% -10%, rgba(59,130,246,.17), rgba(59,130,246,0) 60%),
    radial-gradient(900px 300px at 80% 110%, rgba(59,130,246,.14), rgba(59,130,246,0) 70%),
    linear-gradient(180deg, var(--blue-50), #fff 40%);
}

/* Container */
.container-xxl{ max-width:min(100%, 1560px); padding-left:var(--side-pad)!important; padding-right:var(--side-pad)!important; margin-inline:auto; }

/* Grid */
.row.g-3, .row.g-4{ --bs-gutter-x: var(--gutter-x); --bs-gutter-y: var(--gutter-x); }

/* HERO */
.hero-blue{
  background:radial-gradient(1100px 360px at 10% -10%,var(--blue-600) 0,var(--blue-700) 45%,var(--blue-800) 100%) !important;
  color:#fff!important; border-radius:16px; border:1px solid rgba(255,255,255,.15);
  box-shadow:0 12px 28px rgba(30,64,175,.10);
}
.hero-title{ font-weight:800; letter-spacing:.25px; font-size:1.5rem; }
.text-white-75{ color:rgba(255,255,255,.85)!important; }

/* Cards */
.card-glass-plain{ backdrop-filter:blur(6px); background:rgba(255,255,255,.96); border-radius:14px; border:1px solid rgba(30,64,175,.10); }
.shadow-soft{ box-shadow:0 10px 24px rgba(30,64,175,.08); }
.card-accent{ position:relative; overflow:hidden; }
.card-accent::before{ content:""; position:absolute; left:0; top:0; bottom:0; width:4px; border-radius:4px 0 0 4px; background:linear-gradient(180deg,var(--blue-500),var(--blue-700)); opacity:.75; }
.subtle-divider{ border-color: rgba(30,64,175,.12)!important; }

/* Meta & chips */
.meta-list{ list-style:none; padding-left:0; margin:0; }
.meta-list li{ display:flex; align-items:center; justify-content:space-between; gap:.75rem; padding:.45rem 0; }
.meta-list li span{ color:#6b7280; }
.chip{ display:inline-flex; align-items:center; padding:.28rem .6rem; font-size:.88rem; border-radius:999px; background:#eef3ff; color:#1e3a8a; border:1px solid rgba(30,64,175,.15); font-weight:600; }
.chip.alt{ background:#f1f5ff; color:#244aa4; }

/* Badge kecil */
.bg-blue-soft{ background:var(--blue-200); color:var(--blue-800); border-radius:12px; padding:.5rem .7rem; font-weight:600; font-size:.9rem; }
.text-blue-900{ color:var(--blue-900)!important; }

/* Buttons */
.btn{ font-weight:700; border-radius:10px; font-size:.98rem; padding:.62rem 1.05rem; }
.btn-sm{ padding:.5rem .9rem; font-size:.92rem; }
.btn-primary{ background:var(--blue-600); border-color:var(--blue-600); box-shadow:0 4px 12px rgba(37,99,235,.2); }

/* Alerts */
.bg-blue-soft-2{ background:linear-gradient(180deg,#eef4ff,#eaf2ff); }

/* Sticky di kanan */
.sticky-card{ position:sticky; top:84px; }

/* Avatar */
.avatar-badge{
  width:44px; height:44px; border-radius:50%;
  display:inline-flex; align-items:center; justify-content:center;
  background:linear-gradient(180deg,#eaf2ff,#e3edff);
  color:#274690; font-weight:800; border:1px solid rgba(39,70,144,.15);
}
.avatar-badge.sm{ width:36px; height:36px; font-size:.9rem; }

/* Kontributor list */
.contrib-item{
  display:flex; gap:.75rem; align-items:flex-start;
  padding:.65rem 0; border-bottom:1px dashed rgba(30,64,175,.12);
}
.contrib-item:last-child{ border-bottom:none; }

/* Empty hint */
.empty-hint{ color:#567; background:#f6f9ff; border:1px dashed rgba(30,64,175,.18); border-radius:12px; padding:.75rem 1rem; font-weight:600; }

/* ===== Review timeline ===== */
.review-timeline{ position:relative; padding-left:18px; }
.review-timeline::before{
  content:""; position:absolute; left:5px; top:0; bottom:0; width:2px; background:rgba(30,64,175,.18);
}
.review-item{ position:relative; margin-bottom:16px; }
.review-item:last-child{ margin-bottom:0; }
.review-dot{
  position:absolute; left:-1px; top:6px; width:12px; height:12px; border-radius:50%;
  background:var(--blue-500); box-shadow:0 0 0 4px rgba(59,130,246,.18);
}
.review-card{
  margin-left:14px; background:#fff; border:1px solid rgba(30,64,175,.12);
  border-radius:12px; padding:12px 14px; box-shadow:0 6px 16px rgba(30,64,175,.06);
}
.review-text{ white-space:pre-wrap; }

/* Responsive */
@media (max-width:575.98px){
  .container-xxl{ padding-left:1rem!important; padding-right:1rem!important; }
  .hero-title{ font-size:1.3rem; }
}
</style>

<script>
(() => {
  // modal confirm submit (reusable)
  let targetFormId = null;
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-bs-target="#confirmCancelModal"][data-form-id]');
    if (!btn) return;
    targetFormId = btn.getAttribute('data-form-id');
    const msg = btn.getAttribute('data-message') || 'Batalkan upload abstrak?';
    const textEl = document.getElementById('confirmCancelText');
    if (textEl) textEl.textContent = msg;
  });

  const confirmBtn = document.getElementById('confirmCancelBtn');
  confirmBtn?.addEventListener('click', () => {
    if (!targetFormId) return;
    const form = document.getElementById(targetFormId);
    if (form) {
      confirmBtn.setAttribute('disabled','disabled');
      form.submit();
    }
  });

  // bootstrap validation (untuk form revisi)
  const forms = document.querySelectorAll('.needs-validation');
  Array.from(forms).forEach(form => {
    form.addEventListener('submit', e => {
      if (!form.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
      form.classList.add('was-validated');
    }, false);
  });

  // jika ada hash #section-revisi saat load, scroll smooth
  if (location.hash === '#section-revisi') {
    const t = document.getElementById('section-revisi');
    if (t) setTimeout(() => t.scrollIntoView({behavior:'smooth', block:'start'}), 100);
  }
})();
</script>
