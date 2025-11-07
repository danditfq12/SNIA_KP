<?php
$title   = $title   ?? 'Upload Full Paper';
$event   = $event   ?? [];
$eventId = (int)($eventId ?? 0);
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill page-wrap-blue">
    <div class="container-xxl px-3 px-md-4 py-4">
      <div class="row g-3 justify-content-center">
        <div class="col-12 col-lg-8">
          <div class="card shadow-soft card-glass-plain">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center justify-content-between gap-2">
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-blue-soft"><i class="bi bi-upload"></i></span>
                <h6 class="mb-0 fw-semibold text-blue-900">Form Upload Full Paper</h6>
              </div>
              <a href="<?= site_url('presenter/fullpaper') ?>" class="btn btn-sm btn-outline-secondary">
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
  --blue-50:#eff6ff; --blue-100:#dbeafe; --blue-200:#bfdbfe; --blue-300:#93c5fd; --blue-400:#60a5fa; --blue-500:#3b82f6;
  --blue-600:#2563eb; --blue-700:#1d4ed8; --blue-800:#1e40af; --blue-900:#1e3a8a;
  --side-pad: clamp(1rem, 2.3vw, 2.2rem);
}
.page-wrap-blue{
  min-height:100vh; padding-top:72px;
  background:
    radial-gradient(1000px 380px at 10% -10%, rgba(59,130,246,.16), rgba(59,130,246,0) 60%),
    radial-gradient(1000px 380px at 90% 110%, rgba(59,130,246,.12), rgba(59,130,246,0) 70%),
    linear-gradient(180deg, var(--blue-50), #fff 40%);
}
.card-glass-plain{
  backdrop-filter:blur(6px);
  background:rgba(255,255,255,.96);
  border-radius:14px;
  border:1px solid rgba(30,64,175,.10);
}
.shadow-soft{ box-shadow:0 10px 24px rgba(30,64,175,.08); }
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
.btn{ font-weight:800; letter-spacing:.2px; border-radius:10px; font-size:.98rem; padding:.55rem 1rem; }
.btn-primary{ background:var(--blue-600); border-color:var(--blue-600); box-shadow:0 4px 12px rgba(37,99,235,.2); }
.btn-outline-secondary{ color:#334155; border-color:#e2e8f0; }
.btn-outline-secondary:hover{ background:#f8fafc; border-color:#cbd5e1; color:#0f172a; }
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
