<?php
$title        = $title        ?? 'Kirim Abstrak';
$event        = $event        ?? [];
$eventId      = isset($eventId) ? (int)$eventId : (int)($event['id'] ?? 0);
$kategoriList = $kategoriList ?? [];

$oldKategori = old('id_kategori');
$oldJudul    = old('judul');

$fmtDate = fn($s) => $s ? date('d M Y', strtotime($s)) : '-';
$fmtDT   = fn($s) => $s ? date('d M Y H:i', strtotime($s)) : '-';
$formatLabel = function($f){
  $f = strtolower((string)$f);
  return $f === 'both' ? 'Hybrid' : ucfirst($f ?: '-');
};

$showFpCta      = $showFpCta ?? false;
$lastUploadedId = (int)($lastUploadedId ?? 0);
$formLocked     = (bool)($formLocked ?? false);
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill page-wrap-blue">
    <div class="container-xxl px-3 px-md-4 py-4">

      <!-- Header (identik dengan index) -->
      <div class="hero-blue card-glass mb-3 p-3 p-md-4 d-flex justify-content-between align-items-start gap-3">
        <div>
          <h3 class="hero-title mb-1"><i class="bi bi-file-earmark-plus me-2"></i>Kirim Abstrak</h3>
          <div class="text-white-75 small">Event: <?= esc($event['title'] ?? '-') ?></div>
        </div>
        <div class="d-none d-md-block text-end">
          <div class="text-white-75 small">Tanggal event</div>
          <div class="fw-semibold text-white">
            <?= $fmtDate($event['event_date'] ?? null) ?>
            <?= !empty($event['event_time']) ? ' • '.esc($event['event_time']) : '' ?>
          </div>
        </div>
      </div>

      <div class="row g-3">
        <!-- Info Event -->
        <div class="col-12 col-lg-4">
          <div class="card shadow-soft card-glass-plain h-100">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center justify-content-between gap-2">
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-blue-soft"><i class="bi bi-info-circle"></i></span>
                <h6 class="mb-0 fw-semibold text-blue-900">Info Event</h6>
              </div>
              <!-- NEW: Edit Kontributor -->
              <a href="<?= site_url('/presenter/kontributor/start/'.(int)$eventId) ?>" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-people me-1"></i> Edit Kontributor
              </a>
            </div>
            <div class="card-body small">
              <dl class="mb-0">
                <dt class="text-muted">Tanggal Event</dt>
                <dd class="fw-semibold mb-2"><?= $fmtDate($event['event_date'] ?? null) ?></dd>

                <dt class="text-muted">Waktu</dt>
                <dd class="fw-semibold mb-2"><?= esc($event['event_time'] ?? '-') ?></dd>

                <dt class="text-muted">Lokasi</dt>
                <dd class="fw-semibold mb-2"><?= esc($event['location'] ?? '-') ?></dd>

                <dt class="text-muted">Format</dt>
                <dd class="fw-semibold mb-2"><?= esc($formatLabel($event['format'] ?? '')) ?></dd>

                <hr>

                <dt class="text-muted">Deadline Abstrak</dt>
                <dd class="fw-semibold mb-2"><?= $fmtDT($event['abstract_deadline'] ?? null) ?></dd>

                <?php if (!empty($event['full_paper_deadline'])): ?>
                  <dt class="text-muted">Deadline Full Paper</dt>
                  <dd class="fw-semibold"><?= $fmtDT($event['full_paper_deadline']) ?></dd>
                <?php endif; ?>
              </dl>

              <div class="alert alert-info mt-3 mb-0 shadow-none border-0 bg-blue-soft-2 text-blue-900">
                Pastikan data <strong>kontributor</strong> sudah lengkap (minimal afiliasi).
              </div>
            </div>
          </div>
        </div>

        <!-- Form -->
        <div class="col-12 col-lg-8">

          <?php if ($showFpCta): ?>
            <div class="alert alert-success card-glow d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
              <div class="me-2">
                <div class="fw-semibold">Abstrak berhasil diunggah.</div>
                <div class="small mb-0">
                  Silakan <strong>menunggu konfirmasi reviewer</strong>.
                  <?php if (!empty($event['full_paper_deadline'])): ?>
                    Anda juga bisa lanjut ke <strong>Full Paper</strong> sekarang.
                  <?php endif; ?>
                </div>
              </div>
              <div class="d-flex gap-2">
                <a href="<?= site_url('/presenter/fullpaper/create/'.(int)$eventId) ?>" class="btn btn-success">
                  <i class="bi bi-arrow-right-circle me-1"></i> Lanjut ke Full Paper
                </a>
                <?php if ($lastUploadedId > 0): ?>
                  <form id="cancelForm-<?= (int)$lastUploadedId ?>"
                        action="<?= site_url('/presenter/abstrak/cancel/'.(int)$lastUploadedId) ?>"
                        method="POST" class="m-0">
                    <?= csrf_field() ?>
                    <button type="button"
                            class="btn btn-outline-danger"
                            data-bs-toggle="modal"
                            data-bs-target="#confirmCancelModal"
                            data-form-id="cancelForm-<?= (int)$lastUploadedId ?>"
                            data-message="Batalkan upload abstrak terakhir?">
                      <i class="bi bi-x-circle me-1"></i> Batalkan Upload
                    </button>
                  </form>
                <?php endif; ?>
              </div>
            </div>
          <?php endif; ?>

          <div class="card shadow-soft card-glass-plain position-relative">
            <?php if ($formLocked): ?>
              <div class="form-lock-overlay d-flex align-items-center justify-content-center">
                <div class="text-center px-3">
                  <div class="mb-2 fw-semibold text-blue-900">Form Dikunci</div>
                  <div class="small text-muted">Tidak bisa upload lagi sampai unggahan terakhir dibatalkan.</div>
                </div>
              </div>
            <?php endif; ?>

            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
              <span class="badge bg-blue-soft"><i class="bi bi-upload"></i></span>
              <h6 class="mb-0 fw-semibold text-blue-900">Form Abstrak</h6>
            </div>
            <div class="card-body">
              <form action="<?= site_url('/presenter/abstrak/store') ?>" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="event_id" value="<?= (int)$eventId ?>">

                <!-- Kategori -->
                <div class="mb-3">
                  <label class="form-label">Kategori <span class="text-danger">*</span></label>
                  <select name="id_kategori" class="form-select form-control-soft" <?= $formLocked ? 'disabled' : '' ?> required>
                    <option value="">-- Pilih Kategori --</option>
                    <?php foreach ($kategoriList as $k): ?>
                      <option value="<?= (int)$k['id_kategori'] ?>" <?= (string)$oldKategori === (string)$k['id_kategori'] ? 'selected' : '' ?>>
                        <?= esc($k['nama_kategori']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <div class="invalid-feedback">Kategori wajib dipilih.</div>
                </div>

                <!-- Judul -->
                <div class="mb-3">
                  <div class="d-flex justify-content-between align-items-center">
                    <label class="form-label mb-0">Judul Abstrak <span class="text-danger">*</span></label>
                    <small class="text-muted"><span id="titleCount">0</span>/200</small>
                  </div>
                  <input
                    type="text"
                    class="form-control form-control-soft"
                    name="judul"
                    id="judulInput"
                    maxlength="200"
                    value="<?= esc($oldJudul) ?>"
                    placeholder="Tulis judul abstrak (maks. 200 karakter)"
                    <?= $formLocked ? 'disabled' : '' ?>
                    required
                  >
                  <div class="invalid-feedback">Judul wajib diisi.</div>
                </div>

                <!-- File -->
                <div class="mb-3">
                  <label class="form-label">File Abstrak (PDF) <span class="text-danger">*</span></label>
                  <input type="file" class="form-control form-control-soft" name="file_abstrak" accept=".pdf,application/pdf" <?= $formLocked ? 'disabled' : '' ?> required>
                  <div class="form-text">Format PDF, maksimal 5MB.</div>
                  <div class="invalid-feedback">File PDF wajib diunggah.</div>
                </div>

                <!-- Actions -->
                <div class="d-flex flex-wrap gap-2">
                  <!-- CHANGED: tombol kembali ke halaman Kontributor -->
                  <a href="<?= site_url('/presenter/kontributor/start/'.(int)$eventId) ?>" class="btn btn-light border">
                    <i class="bi bi-arrow-left"></i> Kembali (Kontributor)
                  </a>

                  <?php if ($formLocked): ?>
                    <button type="button" class="btn btn-primary" disabled>
                      <i class="bi bi-send me-1"></i>Kirim Abstrak
                    </button>
                  <?php else: ?>
                    <button type="submit" class="btn btn-primary">
                      <i class="bi bi-send me-1"></i>Kirim Abstrak
                    </button>
                  <?php endif; ?>
                </div>
              </form>
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
            <small class="text-muted">Tindakan ini akan menghapus file dan data unggahan terakhir Anda untuk event ini.</small>
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
  'use strict';
  // Modal konfirmasi batalkan
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

  // Validasi form + counter judul
  const forms = document.querySelectorAll('.needs-validation');
  Array.from(forms).forEach(form => {
    form.addEventListener('submit', e => {
      if (!form.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
      form.classList.add('was-validated');
    }, false);
  });
  const titleInput = document.getElementById('judulInput');
  const titleCount = document.getElementById('titleCount');
  const updateCount = () => { titleCount.textContent = (titleInput?.value || '').length; };
  if (titleInput) { updateCount(); titleInput.addEventListener('input', updateCount); }
})();
</script>
