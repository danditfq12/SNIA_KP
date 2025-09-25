<?php
$title        = $title        ?? 'Kirim Abstrak';
$event        = $event        ?? [];
$eventId      = isset($eventId) ? (int)$eventId : (int)($event['id'] ?? 0);
$kategoriList = $kategoriList ?? [];

$oldKategori = old('id_kategori');
$oldJudul    = old('judul');
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <div class="header-section header-blue d-flex justify-content-between align-items-center mb-3">
        <div>
          <h3 class="welcome-text mb-1"><i class="bi bi-file-earmark-plus me-2"></i>Kirim Abstrak</h3>
          <div class="text-white-50">Event: <?= esc($event['title'] ?? '-') ?></div>
        </div>
      </div>

      <div class="row g-3">
        <div class="col-12 col-lg-4">
          <div class="card shadow-sm h-100">
            <div class="card-header bg-light">
              <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Info Event</h6>
            </div>
            <div class="card-body">
              <div class="mb-1 small text-muted">Tanggal Event</div>
              <div class="fw-semibold mb-2">
                <?= isset($event['event_date']) ? date('d M Y', strtotime($event['event_date'])) : '-' ?>
                <?= !empty($event['event_time']) ? ' • '.esc($event['event_time']) : '' ?>
              </div>
              <div class="mb-1 small text-muted">Deadline Abstrak</div>
              <div class="fw-semibold">
                <?= !empty($event['abstract_deadline']) ? date('d M Y H:i', strtotime($event['abstract_deadline'])) : '-' ?>
              </div>
            </div>
          </div>
        </div>

        <div class="col-12 col-lg-8">
          <div class="card shadow-sm">
            <div class="card-header bg-gradient-primary text-white">
              <h6 class="mb-0"><i class="bi bi-upload me-2"></i>Form Abstrak</h6>
            </div>
            <div class="card-body">
              <form action="<?= site_url('/presenter/abstrak/store') ?>" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="event_id" value="<?= (int)$eventId ?>">

                <div class="mb-3">
                  <label class="form-label">Kategori <span class="text-danger">*</span></label>
                  <select name="id_kategori" class="form-select" required>
                    <option value="">-- Pilih Kategori --</option>
                    <?php foreach ($kategoriList as $k): ?>
                      <option value="<?= (int)$k['id_kategori'] ?>" <?= (string)$oldKategori === (string)$k['id_kategori'] ? 'selected' : '' ?>>
                        <?= esc($k['nama_kategori']) ?>
                      </option>
                    <?php endforeach; ?>
                  </select>
                  <div class="invalid-feedback">Kategori wajib dipilih.</div>
                </div>

                <div class="mb-3">
                  <label class="form-label">Judul Abstrak <span class="text-danger">*</span></label>
                  <input type="text" class="form-control" name="judul" value="<?= esc($oldJudul) ?>" placeholder="Tulis judul abstrak" required>
                  <div class="invalid-feedback">Judul wajib diisi.</div>
                </div>

                <div class="mb-3">
                  <label class="form-label">File Abstrak (PDF saja) <span class="text-danger">*</span></label>
                  <input type="file" class="form-control" name="file_abstrak" accept=".pdf,application/pdf" required>
                  <div class="form-text">Format PDF, maksimal 5MB.</div>
                  <div class="invalid-feedback">File PDF wajib diunggah.</div>
                </div>

                <div class="d-flex flex-wrap gap-2">
                  <a href="<?= site_url('/presenter/abstrak') ?>" class="btn btn-light border">
                    <i class="bi bi-arrow-left"></i> Kembali
                  </a>
                  <button type="submit" class="btn btn-success">
                    <i class="bi bi-send me-1"></i>Kirim Abstrak
                  </button>
                  <!-- ⛔️ Tombol “Kirim & Lanjut Full Paper” DIHAPUS -->
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