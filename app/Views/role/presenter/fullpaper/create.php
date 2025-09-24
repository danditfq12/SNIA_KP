<?php
$title   = $title   ?? 'Upload Full Paper';
$event   = $event   ?? [];
$eventId = $eventId ?? 0;
$abs     = $abs     ?? null; // abstrak terbaru (boleh null)
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <div class="header-section header-blue d-flex justify-content-between align-items-center mb-3">
        <div>
          <h3 class="welcome-text mb-1"><i class="bi bi-file-earmark-plus me-2"></i>Upload Full Paper</h3>
          <div class="text-white-50">Event: <?= esc($event['title'] ?? '-') ?></div>
        </div>
        <div class="text-end d-none d-md-block">
          <small class="text-white-50 d-block">Deadline</small>
          <strong class="text-white">
            <?= !empty($event['full_paper_deadline']) ? date('d M Y H:i', strtotime($event['full_paper_deadline'])) : '-' ?>
          </strong>
        </div>
      </div>

      <div class="row g-3">
        <div class="col-12 col-lg-4">
          <div class="card shadow-sm h-100">
            <div class="card-header bg-light">
              <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Status Ringkas</h6>
            </div>
            <div class="card-body small">
              <div class="mb-2">
                <div class="text-muted">Tanggal Event</div>
                <div class="fw-semibold"><?= !empty($event['event_date']) ? date('d M Y', strtotime($event['event_date'])) : '-' ?></div>
              </div>
              <div class="mb-2">
                <div class="text-muted">Deadline Full Paper</div>
                <div class="fw-semibold"><?= !empty($event['full_paper_deadline']) ? date('d M Y H:i', strtotime($event['full_paper_deadline'])) : '-' ?></div>
              </div>
              <hr>
              <div class="mb-2">
                <div class="text-muted">Status Abstrak Terakhir</div>
                <div class="fw-semibold">
                  <?= $abs ? ucfirst(strtolower($abs['status'] ?? 'Belum')) : 'Belum upload abstrak' ?>
                </div>
                <div class="text-muted mt-1">
                  * Anda boleh upload Full Paper tanpa menunggu abstrak ACC.
                  Jika abstrak ditolak, panitia dapat memutuskan kelanjutan FP.
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-12 col-lg-8">
          <div class="card shadow-sm">
            <div class="card-header bg-gradient-primary text-white">
              <h6 class="mb-0"><i class="bi bi-upload me-2"></i>Form Upload</h6>
            </div>
            <div class="card-body">
              <form action="<?= site_url('presenter/fullpaper/store') ?>" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="event_id" value="<?= (int)$eventId ?>">

                <div class="mb-3">
                  <label class="form-label">File Full Paper (PDF) <span class="text-danger">*</span></label>
                  <input type="file" class="form-control" name="full_paper" accept=".pdf,application/pdf" required>
                  <div class="form-text">Format PDF, maksimal 20MB.</div>
                  <div class="invalid-feedback">File PDF wajib diunggah.</div>
                </div>

                <div class="d-flex gap-2">
                  <a href="<?= site_url('presenter/fullpaper') ?>" class="btn btn-light border">
                    <i class="bi bi-arrow-left"></i> Kembali
                  </a>
                  <button type="submit" class="btn btn-success">
                    <i class="bi bi-send"></i> Kirim Full Paper
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
  .header-section.header-blue{
    background: linear-gradient(135deg,#2563eb,#1e40af);
    color:#fff; padding:24px; border-radius:16px; box-shadow:0 8px 28px rgba(0,0,0,.12);
  }
  .welcome-text{ font-weight:700; font-size:1.4rem; }
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