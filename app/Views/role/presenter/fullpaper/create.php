<?php
$title   = $title   ?? 'Upload Full Paper';
$event   = $event   ?? [];
$eventId = (int)($eventId ?? 0);
$abs     = $abs     ?? null;

$fmtDate = fn($s)=> $s ? date('d M Y', strtotime($s)) : '-';
$fmtDT   = fn($s)=> $s ? date('d M Y H:i', strtotime($s)) : '-';
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

      <!-- HERO (seragam) -->
      <div class="hero-blue card-glass mb-3 p-3 p-md-4 d-flex justify-content-between align-items-start gap-3">
        <div>
          <h3 class="hero-title mb-1">
            <i class="bi bi-file-earmark-text me-2"></i>Upload Full Paper
          </h3>
          <div class="text-white-75 small">Event: <?= esc($event['title'] ?? '-') ?></div>
        </div>
        <div class="d-none d-md-block text-end">
          <div class="text-white-75 small">Deadline Full Paper</div>
          <div class="fw-semibold text-white">
            <?= !empty($event['full_paper_deadline']) ? esc($fmtDT($event['full_paper_deadline'])) : '-' ?>
          </div>
        </div>
      </div>

      <div class="row g-3">
        <!-- Info Event -->
        <div class="col-12 col-lg-4">
          <div class="card shadow-soft card-glass-plain h-100">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
              <span class="badge bg-blue-soft"><i class="bi bi-info-circle"></i></span>
              <h6 class="mb-0 fw-semibold text-blue-900">Info Event</h6>
            </div>
            <div class="card-body small">
              <dl class="mb-0">
                <dt class="text-muted">Tanggal Event</dt>
                <dd class="fw-semibold mb-2"><?= esc($fmtDate($event['event_date'] ?? null)) ?></dd>

                <dt class="text-muted">Waktu</dt>
                <dd class="fw-semibold mb-2"><?= esc($event['event_time'] ?? '-') ?></dd>

                <dt class="text-muted">Lokasi</dt>
                <dd class="fw-semibold mb-2"><?= esc($event['location'] ?? '-') ?></dd>

                <dt class="text-muted">Format</dt>
                <dd class="fw-semibold mb-2"><?= esc($formatLabel($event['format'] ?? '')) ?></dd>

                <hr>

                <dt class="text-muted">Deadline Full Paper</dt>
                <dd class="fw-semibold mb-0"><?= esc($fmtDT($event['full_paper_deadline'] ?? null)) ?></dd>
              </dl>

              <div class="alert alert-info mt-3 mb-0 shadow-none border-0 bg-blue-soft-2 text-blue-900">
                Pastikan file <strong>PDF</strong> sudah final atau sesuai instruksi revisi.
              </div>
            </div>
          </div>
        </div>

        <!-- Form -->
        <div class="col-12 col-lg-8">
          <div class="card shadow-soft card-glass-plain">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center justify-content-between gap-2">
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-blue-soft"><i class="bi bi-upload"></i></span>
                <h6 class="mb-0 fw-semibold text-blue-900">Form Upload Full Paper</h6>
              </div>
              <a href="javascript:history.back()" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Kembali
              </a>
            </div>
            <div class="card-body">
              <form action="<?= site_url('presenter/fullpaper/store') ?>" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="event_id" value="<?= (int)$eventId ?>">

                <div class="mb-3">
                  <label class="form-label">File Full Paper (PDF) <span class="text-danger">*</span></label>
                  <input type="file" class="form-control form-control-soft" name="full_paper" accept=".pdf,application/pdf" required>
                  <div class="form-text">Format PDF, maksimal 20MB.</div>
                  <div class="invalid-feedback">File PDF wajib diunggah.</div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                  <button type="submit" class="btn btn-primary">
                    <i class="bi bi-send me-1"></i>Kirim Full Paper
                  </button>
                </div>
              </form>
            </div>
          </div>

        </div>
      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<style>
:root{
  /* Palet & spacing seragam */
  --blue-50:#eff6ff; --blue-100:#dbeafe; --blue-200:#bfdbfe;
  --blue-300:#93c5fd; --blue-400:#60a5fa; --blue-500:#3b82f6;
  --blue-600:#2563eb; --blue-700:#1d4ed8; --blue-800:#1e40af; --blue-900:#1e3a8a;

  --side-pad: clamp(1rem, 2.3vw, 2.2rem);   /* kiri–kanan LEBAR sama */
  --gutter-x: 1rem;                         /* jarak grid */
}

/* Base */
body{
  font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  font-size:15.5px; line-height:1.6; color:#0f172a;
}

/* Layout background */
.page-wrap-blue{
  min-height:100vh; padding-top:72px;
  background:
    radial-gradient(1000px 380px at 10% -10%, rgba(59,130,246,.16), rgba(59,130,246,0) 60%),
    radial-gradient(1000px 380px at 90% 110%, rgba(59,130,246,.12), rgba(59,130,246,0) 70%),
    linear-gradient(180deg, var(--blue-50), #fff 40%);
}

/* Container lebar 1560px */
.container-xxl{
  max-width:min(100%, 1560px);
  padding-left: var(--side-pad) !important;
  padding-right: var(--side-pad) !important;
  margin-inline:auto;
}
.row.g-3, .row.g-4{ --bs-gutter-x: var(--gutter-x); --bs-gutter-y: var(--gutter-x); }

/* HERO biru (seragam) */
.hero-blue{
  background:radial-gradient(1200px 400px at 10% -20%,var(--blue-600) 0,var(--blue-700) 40%,var(--blue-800) 100%) !important;
  color:#fff !important;
  border-radius:16px;
  border:1px solid rgba(255,255,255,.15);
  box-shadow:0 12px 28px rgba(30,64,175,.10);
}
.hero-blue.card-glass{ backdrop-filter:none !important; }
.hero-title{ font-weight:800; letter-spacing:.25px; font-size:1.45rem; }
.text-white-75{ color:rgba(255,255,255,.85)!important; }

/* Cards & glass */
.card-glass-plain{
  backdrop-filter:blur(6px);
  background:rgba(255,255,255,.96);
  border-radius:14px;
  border:1px solid rgba(30,64,175,.10);
}
.shadow-soft{ box-shadow:0 10px 24px rgba(30,64,175,.08); }
.card-header{ padding:1rem 1rem .45rem 1rem !important; }
.card-body{   padding:1.05rem !important; }

/* Text & badges */
.text-blue-900{ color:var(--blue-900)!important; }
.bg-blue-soft{ background:var(--blue-200); color:var(--blue-800); border-radius:12px; padding:.4rem .6rem; font-weight:700; font-size:.85rem; }
.bg-blue-soft-2{ background:#eef6ff; }

/* Form styles (soft) */
.form-label{ font-weight:800; color:#334155; }
.form-control-soft{
  border:1px solid rgba(2,6,23,.12);
  background:#fff;
  border-radius:12px;
  height:44px; font-size:.98rem;
}
.form-control-soft:focus{
  border-color: var(--blue-400) !important;
  box-shadow:0 0 0 .2rem rgba(59,130,246,.12) !important;
}

/* Buttons (seragam) */
.btn{ font-weight:800; letter-spacing:.2px; border-radius:10px; font-size:.98rem; padding:.55rem 1rem; }
.btn-sm{ padding:.4rem .8rem; font-size:.85rem; }
.btn-primary{ background:var(--blue-600); border-color:var(--blue-600); box-shadow:0 4px 12px rgba(37,99,235,.2); }
.btn-primary:hover{ background:var(--blue-700); border-color:var(--blue-700); }
.btn-outline-secondary{ color:#334155; border-color:#e2e8f0; }
.btn-outline-secondary:hover{ background:#f8fafc; border-color:#cbd5e1; color:#0f172a; }

/* Responsive padding */
@media (max-width:575.98px){
  .container-xxl{ padding-left: calc(var(--side-pad) - .25rem) !important; padding-right: calc(var(--side-pad) - .25rem) !important; }
  .hero-title{ font-size:1.25rem; }
  .card-body{  padding:.9rem !important; }
  .card-header{ padding:.9rem .9rem .4rem .9rem !important; }
}
@media (min-width:992px){
  .hero-blue{ padding:1.7rem !important; }
}
</style>

<script>
(() => {
  'use strict';
  const forms = document.querySelectorAll('.needs-validation');
  Array.from(forms).forEach(form => {
    form.addEventListener('submit', e => {
      if (!form.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
      form.classList.add('was-validated');
    }, false);
  });
})();
</script>
