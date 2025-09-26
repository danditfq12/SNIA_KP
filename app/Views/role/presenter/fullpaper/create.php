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

      <!-- Hero -->
      <div class="hero-blue card-glass mb-3 p-3 p-md-4 d-flex justify-content-between align-items-start gap-3">
        <div>
          <h3 class="hero-title mb-1"><i class="bi bi-file-earmark-text me-2"></i>Upload Full Paper</h3>
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
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
              <span class="badge bg-blue-soft"><i class="bi bi-upload"></i></span>
              <h6 class="mb-0 fw-semibold text-blue-900">Form Upload Full Paper</h6>
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
                  <a href="<?= site_url('presenter/fullpaper/detail/'.$eventId) ?>" class="btn btn-light border">
                    <i class="bi bi-arrow-left"></i> Kembali
                  </a>
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
    --blue-50:#eff6ff; --blue-100:#dbeafe; --blue-200:#bfdbfe;
    --blue-300:#93c5fd; --blue-400:#60a5fa; --blue-500:#3b82f6;
    --blue-600:#2563eb; --blue-700:#1d4ed8; --blue-800:#1e40af; --blue-900:#1e3a8a;
  }
  .page-wrap-blue{ background: linear-gradient(180deg, var(--blue-50), #fff 40%); min-height:100vh; padding-top:70px; }
  .hero-blue{
    background: radial-gradient(1200px 400px at 10% -20%, var(--blue-600) 0, var(--blue-700) 40%, var(--blue-800) 100%);
    color:#fff; border-radius:18px; border:1px solid rgba(255,255,255,.15);
    box-shadow: 0 10px 30px rgba(29,78,216,.25), inset 0 0 40px rgba(255,255,255,.06);
  }
  .hero-title{ font-weight:800; letter-spacing:.2px; }
  .text-white-75{ color:rgba(255,255,255,.85)!important; }
  .card-glass-plain{ backdrop-filter: blur(3px); background: rgba(255,255,255,.7); border-radius:16px; border:1px solid rgba(30,64,175,.06); }
  .shadow-soft{ box-shadow: 0 10px 24px rgba(30,64,175,.06); }
  .bg-blue-soft{ background: var(--blue-100); color: var(--blue-800); border-radius:12px; padding:.35rem .6rem; }
  .bg-blue-soft-2{ background: var(--blue-50); }
  .text-blue-900{ color: var(--blue-900)!important; }
  .form-control-soft{ border-radius:12px; border:1px solid rgba(30,64,175,.12); background:#fff; }
  .form-control-soft:focus{ border-color: var(--blue-400); box-shadow: 0 0 0 .2rem rgba(59,130,246,.12); }
  .btn-primary{ background:var(--blue-600); border-color:var(--blue-600); }
  .btn-primary:hover{ background:var(--blue-700); border-color:var(--blue-700); }
  @media (max-width: 767.98px){
    .hero-blue{ border-radius:14px; }
    .container-xxl{ padding-left:.9rem; padding-right:.9rem; }
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
