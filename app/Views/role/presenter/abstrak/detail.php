<?php
$title  = $title ?? 'Detail Abstrak';
$abs    = $abs ?? [];
$event  = $event ?? [];
$status = strtolower($abs['status'] ?? 'menunggu');

$badge = [
  'menunggu'        => 'warning',
  'sedang_direview' => 'info',
  'diterima'        => 'success',
  'ditolak'         => 'danger',
][$status] ?? 'secondary';

$showReuploadAbstract = ($status === 'ditolak');
$showCancel = ($status === 'menunggu');
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill page-wrap-blue">
    <div class="container-xxl px-3 px-md-4 py-4">

      <div class="hero-blue card-glass mb-3 p-3 p-md-4 d-flex justify-content-between align-items-start gap-3">
        <div>
          <h3 class="hero-title mb-1"><i class="bi bi-journal-text me-2"></i>Detail Abstrak</h3>
          <div class="text-white-75 small"><?= esc($event['title'] ?? '-') ?></div>
        </div>
        <div>
          <span class="badge bg-<?= $badge ?> fs-6"><?= ucfirst($status) ?></span>
        </div>
      </div>

      <div class="row g-3">
        <div class="col-12 col-lg-8">
          <div class="card shadow-soft card-glass-plain mb-3">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center justify-content-between">
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-blue-soft"><i class="bi bi-info-circle"></i></span>
                <h6 class="mb-0 fw-semibold text-blue-900">Informasi Abstrak</h6>
              </div>
              <a href="javascript:history.back()" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Kembali
              </a>
            </div>
            <div class="card-body">
              <div class="mb-3">
                <div class="text-muted small">Judul</div>
                <div class="fw-semibold text-blue-900"><?= esc($abs['judul'] ?? '-') ?></div>
              </div>
              <div class="mb-3">
                <div class="text-muted small">Kategori</div>
                <div class="fw-semibold text-blue-900"><?= esc($abs['nama_kategori'] ?? '-') ?></div>
              </div>
              <div class="mb-1">
                <div class="text-muted small">Tanggal Unggah</div>
                <div class="fw-semibold text-blue-900"><?= !empty($abs['tanggal_upload']) ? date('d M Y H:i', strtotime($abs['tanggal_upload'])) : '-' ?></div>
              </div>

              <div class="d-flex flex-wrap gap-2 mt-3">
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

          <div class="card shadow-soft card-glass-plain">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
              <span class="badge bg-blue-soft"><i class="bi bi-calendar-event"></i></span>
              <h6 class="mb-0 fw-semibold text-blue-900">Info Event</h6>
            </div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-6">
                  <div class="text-muted small">Tanggal Event</div>
                  <div class="fw-semibold text-blue-900">
                    <?= isset($event['event_date']) ? date('d M Y', strtotime($event['event_date'])) : '-' ?>
                  </div>
                </div>
                <div class="col-6">
                  <div class="text-muted small">Deadline Abstrak</div>
                  <div class="fw-semibold text-blue-900">
                    <?= !empty($event['abstract_deadline']) ? date('d M Y H:i', strtotime($event['abstract_deadline'])) : '-' ?>
                  </div>
                </div>
                <div class="col-6">
                  <div class="text-muted small">Deadline Full Paper</div>
                  <div class="fw-semibold text-blue-900">
                    <?= !empty($event['full_paper_deadline']) ? date('d M Y H:i', strtotime($event['full_paper_deadline'])) : '-' ?>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>

        <div class="col-12 col-lg-4">
          <div class="card shadow-soft card-glass-plain">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
              <span class="badge bg-blue-soft"><i class="bi bi-flag"></i></span>
              <h6 class="mb-0 fw-semibold text-blue-900">Status</h6>
            </div>
            <div class="card-body">
              <?php if ($status === 'menunggu' || $status === 'sedang_direview'): ?>
                <div class="alert alert-info mb-0 bg-blue-soft-2 text-blue-900 border-0">
                  Abstrak Anda sedang diproses oleh reviewer.
                </div>
              <?php elseif ($status === 'ditolak'): ?>
                <div class="alert alert-danger mb-0">Abstrak ditolak. Silakan unggah ulang sesuai catatan revisi dari reviewer.</div>
              <?php elseif ($status === 'diterima'): ?>
                <div class="alert alert-success mb-0">Abstrak diterima.</div>
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

  --side-pad: 1rem;   /* padding kiri–kanan minimum */
  --gutter-x: 1rem;   /* jarak antar kolom */
}

body{
  font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  font-size:14.6px;       /* sedikit lebih kecil */
  line-height:1.5;
}

/* ===== Layout wrapper ===== */
.page-wrap-blue{
  background:linear-gradient(180deg,var(--blue-50),#fff 40%);
  min-height:100vh;
  padding-top:72px; /* seragam */
}

/* ===== Container — lebar tetap, padding kiri–kanan 1rem ===== */
.container-xxl{
  max-width:1400px;
  padding-left:var(--side-pad) !important;
  padding-right:var(--side-pad) !important;
}

/* ===== Gutter grid (seragam) ===== */
.row.g-3, .row.g-4{ --bs-gutter-x: var(--gutter-x); --bs-gutter-y: var(--gutter-x); }

/* ===== HERO (header biru) ===== */
.hero-blue{
  background:radial-gradient(1200px 400px at 10% -20%,var(--blue-600) 0,var(--blue-700) 40%,var(--blue-800) 100%) !important;
  color:#fff !important;
  border-radius:16px;
  border:1px solid rgba(255,255,255,.15);
  box-shadow:0 12px 28px rgba(30,64,175,.10);
  padding:1.6rem !important;
  margin-bottom:1.25rem !important;
}
/* Matikan efek glass jika hero terlanjur diberi .card-glass / .card-glass-plain */
.hero-blue.card-glass,
.hero-blue.card-glass-plain{
  backdrop-filter:none !important;
  background:radial-gradient(1200px 400px at 10% -20%,var(--blue-600) 0,var(--blue-700) 40%,var(--blue-800) 100%) !important;
  border:1px solid rgba(255,255,255,.15) !important;
  box-shadow:0 12px 28px rgba(30,64,175,.10) !important;
}
.hero-title{ font-weight:800; letter-spacing:.25px; font-size:1.45rem; }
.text-white-75{ color:rgba(255,255,255,.85)!important; }

/* ===== Card / Glass ===== */
.card-glass{
  backdrop-filter: blur(2px);
  background: linear-gradient(180deg, rgba(255,255,255,.18), rgba(255,255,255,.10));
  border-radius:16px; border:1px solid rgba(255,255,255,.28);
}
.card-glass-plain{
  backdrop-filter:blur(6px);
  background:rgba(255,255,255,.94);
  border-radius:14px;
  border:1px solid rgba(30,64,175,.10);
}
.shadow-soft{ box-shadow:0 10px 24px rgba(30,64,175,.08); }

.card-header{ padding:1rem 1rem .45rem 1rem !important; }
.card-body{   padding:1.05rem !important; }

/* ===== Badge kecil lembut ===== */
.bg-blue-soft{ background:var(--blue-200); color:var(--blue-800); border-radius:12px; padding:.4rem .6rem; font-weight:600; font-size:.85rem; }
.bg-success-subtle{   background:#d1fae5!important; color:#065f46!important; }
.bg-warning-subtle{   background:#fef3c7!important; color:#92400e!important; }
.bg-danger-subtle{    background:#fee2e2!important; color:#991b1b!important; }
.bg-info-subtle{      background:#e0f2fe!important; color:#0c4a6e!important; }
.bg-secondary-subtle{ background:#f1f5f9!important; color:#475569!important; }
.bg-primary-subtle{   background:#dbeafe!important; color:var(--blue-700)!important; }
.text-blue-900{ color:var(--blue-900)!important; }

/* ===== Event card (box on + compact) ===== */
.event-card{
  background:linear-gradient(180deg,#fff,rgba(255,255,255,.96));
  border:1px solid rgba(30,64,175,.10);
  border-radius:14px;
  box-shadow:0 10px 22px rgba(30,64,175,.08);
  padding:14px;
}
.event-card h6{ font-size:1.05rem; margin-bottom:.25rem; }
.event-card .small{ font-size:.92rem; }
.event-card .badge{ padding:.35rem .6rem; font-size:.78rem; border-radius:10px; }
.event-card .btn{ border-radius:10px; padding:.58rem 1rem; font-size:.95rem; }
.opacity-90{ opacity:.92; }

/* ===== Table (kontributor / umum) ===== */
.table{ font-size:.95rem; margin-bottom:0; }
.table thead th{
  background-color:var(--blue-50)!important;
  border-bottom:1px solid var(--blue-200);
  font-weight:600; color:var(--blue-900);
  font-size:.85rem; text-transform:uppercase; letter-spacing:.4px;
  padding:.75rem 1rem;
}
.table tbody tr{ border-bottom:1px solid rgba(30,64,175,.08); }
.table tbody td{
  padding:.8rem 1rem; vertical-align:middle; border-top:none; font-size:.95rem;
}
.table-responsive{ border:1px solid rgba(30,64,175,.08); border-radius:12px; overflow:hidden; }

/* ===== List (progress) ===== */
.list-group-item{
  background:transparent!important;
  border-left:none!important; border-right:none!important;
  font-size:1rem; padding:.8rem 0; font-weight:500;
}
.list-group-item:first-child{ border-top:none!important; }
.list-group-item:last-child{  border-bottom:none!important; }
.list-group-item strong{ font-weight:600; font-size:.98rem; }

/* ===== Buttons ===== */
.btn{
  font-weight:600; letter-spacing:.25px;
  border-radius:10px; font-size:.95rem; padding:.55rem 1rem;
}
.btn-sm{ padding:.42rem .8rem; font-size:.86rem; }
.btn-lg{ padding:.85rem 1.5rem; font-size:1.06rem; }
.btn-primary{ background:var(--blue-600); border-color:var(--blue-600); box-shadow:0 4px 12px rgba(37,99,235,.2); }
.btn-info{    background:#06b6d4; border-color:#06b6d4; box-shadow:0 4px 12px rgba(6,182,212,.2); }
.btn-success{ background:#059669; border-color:#059669; box-shadow:0 4px 12px rgba(5,150,105,.2); }
.btn-warning{ background:#d97706; border-color:#d97706; box-shadow:0 4px 12px rgba(217,119,6,.2); }
.btn-danger{  background:#dc2626; border-color:#dc2626; box-shadow:0 4px 12px rgba(220,38,38,.2); }

.d-grid.gap-2{ gap:1rem !important; }

/* ===== Helpers ===== */
.text-muted{ color:#6b7280!important; font-weight:500; font-size:.9rem; }
.text-muted.small{ font-size:.84rem!important; }
.fw-semibold{ font-weight:600!important; }
.fw-medium{   font-weight:500!important; }
a{ text-decoration:none; }
a.text-primary{ color:var(--blue-600)!important; font-weight:500; }

/* ===== Card glow (notif sukses) ===== */
.card-glow{ box-shadow:0 8px 24px rgba(16,185,129,.18); border:1px solid rgba(16,185,129,.2); }

/* ===== Overlay kunci form (dipakai di Kirim Abstrak) ===== */
.form-lock-overlay{
  position:absolute; inset:0;
  background: rgba(255,255,255,.6);
  border-radius:16px;
  border:1px dashed rgba(30,64,175,.25);
  z-index: 2; text-align:center;
}

/* ===== Responsive ===== */
@media (max-width:575.98px){
  .container-xxl{ padding-left:1rem !important; padding-right:1rem !important; }
  .hero-blue{ border-radius:14px; padding:1.25rem!important; margin-bottom:1rem!important; }
  .hero-title{ font-size:1.25rem; }
  .card-body{  padding:.9rem!important; }
  .card-header{ padding:.9rem .9rem .4rem .9rem!important; }
  .table thead th, .table tbody td{ padding:.6rem .75rem; font-size:.85rem; }
  .list-group-item{ font-size:.98rem; padding:.7rem 0; }
  .badge{ padding:.38rem .6rem; font-size:.74rem; }
  .event-card{ padding:12px; }
  .event-card h6{ font-size:1rem; }
  .event-card .small{ font-size:.9rem; }
}
@media (min-width:576px) and (max-width:767.98px){
  .container-xxl{ padding-left:1rem !important; padding-right:1rem !important; }
  .hero-title{ font-size:1.35rem; }
  .card-body{  padding:1rem!important; }
}
@media (min-width:768px) and (max-width:991.98px){
  .container-xxl{ padding-left:1rem !important; padding-right:1rem !important; }
  .hero-title{ font-size:1.45rem; }
}
@media (min-width:992px){
  .hero-blue{ padding:1.7rem!important; }
  .card-body{  padding:1.05rem!important; }
  .card-header{ padding:1rem 1rem .45rem 1rem!important; }
}
</style>

<script>
(() => {
  // modal submit glue (reusable)
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
})();
</script>
