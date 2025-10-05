<?php
$title  = $title ?? 'Detail Abstrak';
$abs    = $abs ?? [];
$event  = $event ?? [];
$status = strtolower($status ?? ($abs['status'] ?? 'menunggu'));
$badge  = $badge ?? 'secondary';

$showReuploadAbstract = (bool)($showReuploadAbstract ?? false);
$showCancel           = (bool)($showCancel ?? false);

$assignedReviewer = $assignedReviewer ?? ['name'=>null,'email'=>null,'org'=>null];
$reviewComments   = $reviewComments   ?? [];
$contributors     = $contributors     ?? [];
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill page-wrap-blue">
    <div class="container-xxl px-3 px-md-4 py-4">

      <!-- HERO: TANPA STATUS -->
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
                <?php if (!empty($abs['file_abstrak'])): ?>
                  <a class="btn btn-outline-secondary" href="<?= site_url('/presenter/abstrak/download/'.esc($abs['file_abstrak'])) ?>">
                    <i class="bi bi-download"></i> Unduh File
                  </a>
                <?php endif; ?>

                <?php if ($showReuploadAbstract): ?>
                  <a class="btn btn-danger" href="<?= site_url('/presenter/abstrak/create/'.(int)$abs['event_id']) ?>">
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

          <!-- Data Kontributor (mengacu patokan Kontributor) -->
          <div class="card shadow-soft card-glass-plain mb-3">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center justify-content-between">
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-blue-soft"><i class="bi bi-people"></i></span>
                <h6 class="mb-0 fw-semibold text-blue-900">Data Kontributor</h6>
              </div>
              <?php if (!empty($contributors)): ?>
                <span class="badge bg-primary-subtle text-blue-900 fw-semibold"><?= count($contributors) ?> orang</span>
              <?php endif; ?>
            </div>
            <div class="card-body">
              <?php if (empty($contributors)): ?>
                <div class="empty-hint"><i class="bi bi-info-circle me-1"></i> Data kontributor belum tersedia.</div>
              <?php else: ?>
                <ul class="list-unstyled m-0">
                  <?php foreach ($contributors as $c): ?>
                    <li class="contrib-item">
                      <div class="avatar-badge avatar-sm">
                        <span><?= esc(strtoupper(mb_substr($c['name'] ?? 'K', 0, 1))) ?></span>
                      </div>
                      <div class="flex-fill">
                        <div class="d-flex align-items-center gap-2">
                          <span class="fw-bold text-blue-900"><?= esc($c['name'] ?? '—') ?></span>
                          <?php if (!empty($c['presenter'])): ?>
                            <span class="chip">Presenter</span>
                          <?php elseif (!empty($c['role'])): ?>
                            <span class="chip alt"><?= esc($c['role']) ?></span>
                          <?php endif; ?>
                        </div>
                        <?php if (!empty($c['affiliation'])): ?>
                          <div class="text-muted small"><i class="bi bi-buildings me-1"></i><?= esc($c['affiliation']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($c['email'])): ?>
                          <div class="small">
                            <i class="bi bi-envelope me-1"></i>
                            <a class="text-primary" href="mailto:<?= esc($c['email']) ?>"><?= esc($c['email']) ?></a>
                          </div>
                        <?php endif; ?>
                      </div>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>
            </div>
          </div>

          <!-- Komentar Reviewer -->
          <div class="card shadow-soft card-glass-plain mb-3">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
              <span class="badge bg-blue-soft"><i class="bi bi-chat-left-dots"></i></span>
              <h6 class="mb-0 fw-semibold text-blue-900">Komentar Reviewer</h6>
            </div>
            <div class="card-body">
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
                  <strong class="text-blue-900">
                    <?= isset($event['event_date']) ? date('d M Y', strtotime($event['event_date'])) : '-' ?>
                  </strong>
                </li>
                <li>
                  <span>Deadline Abstrak</span>
                  <strong class="text-blue-900">
                    <?= !empty($event['abstract_deadline']) ? date('d M Y H:i', strtotime($event['abstract_deadline'])) : '-' ?>
                  </strong>
                </li>
                <li>
                  <span>Deadline Full Paper</span>
                  <strong class="text-blue-900">
                    <?= !empty($event['full_paper_deadline']) ? date('d M Y H:i', strtotime($event['full_paper_deadline'])) : '-' ?>
                  </strong>
                </li>
              </ul>
            </div>
          </div>
        </div>

        <!-- KANAN -->
        <div class="col-12 col-xl-4">
          <!-- Status (sticky) -->
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
              <?php elseif ($status === 'ditolak'): ?>
                <div class="alert alert-danger mb-0 fw-semibold">
                  <i class="bi bi-x-octagon me-1"></i> Abstrak ditolak. Silakan unggah ulang sesuai catatan revisi.
                </div>
              <?php elseif ($status === 'diterima'): ?>
                <div class="alert alert-success mb-0 fw-semibold">
                  <i class="bi bi-check2-circle me-1"></i> Abstrak diterima.
                </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Reviewer Ditugaskan -->
          <div class="card shadow-soft card-glass-plain sticky-card">
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

/* BG konsisten */
.page-wrap-blue{
  min-height:100vh; padding-top:72px; position:relative;
  background:
    radial-gradient(900px 300px at 20% -10%, rgba(59,130,246,.17), rgba(59,130,246,0) 60%),
    radial-gradient(900px 300px at 80% 110%, rgba(59,130,246,.14), rgba(59,130,246,0) 70%),
    linear-gradient(180deg, var(--blue-50), #fff 40%);
}
.page-wrap-blue::after{
  content:""; position:absolute; inset:0; pointer-events:none; opacity:.18;
  background-image: radial-gradient(#93c5fd 1px, transparent 1px), radial-gradient(#bfdbfe 1px, transparent 1px);
  background-position: 0 0, 20px 20px; background-size: 40px 40px, 40px 40px;
}

/* Container lebar */
.container-xxl{ max-width:min(100%, 1560px); padding-left:var(--side-pad)!important; padding-right:var(--side-pad)!important; margin-inline:auto; }

/* Grid gutter */
.row.g-3, .row.g-4{ --bs-gutter-x: var(--gutter-x); --bs-gutter-y: var(--gutter-x); }

/* HERO */
.hero-blue{
  background:radial-gradient(1100px 360px at 10% -10%,var(--blue-600) 0,var(--blue-700) 45%,var(--blue-800) 100%) !important;
  color:#fff!important; border-radius:16px; border:1px solid rgba(255,255,255,.15);
  box-shadow:0 12px 28px rgba(30,64,175,.10);
}
.hero-title{ font-weight:800; letter-spacing:.25px; font-size:1.5rem; }
.text-white-75{ color:rgba(255,255,255,.85)!important; }

/* Cards & accent */
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

/* Badge kecil lembut */
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

/* Avatar reviewer + contributor */
.avatar-badge{
  width:44px; height:44px; border-radius:50%;
  display:inline-flex; align-items:center; justify-content:center;
  background:linear-gradient(180deg,#eaf2ff,#e3edff);
  color:#274690; font-weight:800; border:1px solid rgba(39,70,144,.15);
}
.avatar-sm{ width:38px; height:38px; font-size:.95rem; }

/* Kontributor list */
.contrib-item{
  display:flex; gap:.75rem; align-items:flex-start;
  padding:.65rem 0; border-bottom:1px dashed rgba(30,64,175,.12);
}
.contrib-item:last-child{ border-bottom:none; }

/* Empty hint */
.empty-hint{ color:#567; background:#f6f9ff; border:1px dashed rgba(30,64,175,.18); border-radius:12px; padding:.75rem 1rem; font-weight:600; }

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
    if (textEl) textContent = msg;
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
})();
</script>