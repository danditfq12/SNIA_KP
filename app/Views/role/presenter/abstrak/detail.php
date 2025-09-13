<?php
$title = $title ?? 'Detail Abstrak';
$abs   = $abs ?? [];
$event = $event ?? [];
$status = $abs['status'] ?? 'menunggu';
$badge = [
  'menunggu'        => 'warning',
  'sedang_direview' => 'info',
  'diterima'        => 'success',
  'ditolak'         => 'danger',
  'revisi'          => 'primary',
][$status] ?? 'secondary';
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <div class="header-section header-blue d-flex justify-content-between align-items-center mb-3">
        <div>
          <h3 class="welcome-text mb-1">
            <i class="bi bi-journal-text me-2"></i>Detail Abstrak
          </h3>
          <div class="text-white-50"><?= esc($event['title'] ?? '-') ?></div>
        </div>
        <div>
          <span class="badge bg-<?= $badge ?> fs-6"><?= ucfirst($status) ?></span>
        </div>
      </div>

      <?= $this->include('partials/alerts') ?>

      <div class="row g-3">
        <div class="col-12 col-lg-8">
          <div class="card shadow-sm mb-3">
            <div class="card-header bg-light">
              <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Informasi Abstrak</h6>
            </div>
            <div class="card-body">
              <div class="mb-2">
                <div class="text-muted small">Judul</div>
                <div class="fw-semibold"><?= esc($abs['judul'] ?? '-') ?></div>
              </div>
              <div class="mb-2">
                <div class="text-muted small">Kategori</div>
                <div class="fw-semibold"><?= esc($abs['nama_kategori'] ?? '-') ?></div>
              </div>
              <div class="mb-2">
                <div class="text-muted small">Tanggal Unggah</div>
                <div class="fw-semibold"><?= !empty($abs['tanggal_upload']) ? date('d M Y H:i', strtotime($abs['tanggal_upload'])) : '-' ?></div>
              </div>

              <div class="d-flex gap-2 mt-3">
                <a class="btn btn-outline-secondary" href="/presenter/abstrak/download/<?= esc($abs['file_abstrak']) ?>">
                  <i class="bi bi-download"></i> Unduh File
                </a>

                <?php if ($status === 'revisi'): ?>
                  <a class="btn btn-primary" href="/presenter/abstrak/create/<?= (int)$abs['event_id'] ?>">
                    <i class="bi bi-upload"></i> Unggah Revisi
                  </a>
                <?php endif; ?>

                <?php if ($status === 'diterima'): ?>
                  <!-- ARAHKAN KE INSTRUCTION -->
                  <a class="btn btn-success" href="/presenter/pembayaran/instruction/<?= (int)$abs['event_id'] ?>">
                    <i class="bi bi-credit-card"></i> Lanjut Pembayaran
                  </a>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <div class="card shadow-sm">
            <div class="card-header bg-light">
              <h6 class="mb-0"><i class="bi bi-calendar-event me-2"></i>Info Event</h6>
            </div>
            <div class="card-body">
              <div class="row g-2">
                <div class="col-6">
                  <div class="text-muted small">Tanggal Event</div>
                  <div class="fw-semibold"><?= isset($event['event_date']) ? date('d M Y', strtotime($event['event_date'])) : '-' ?></div>
                </div>
                <div class="col-6">
                  <div class="text-muted small">Deadline Abstrak</div>
                  <div class="fw-semibold"><?= !empty($event['abstract_deadline']) ? date('d M Y H:i', strtotime($event['abstract_deadline'])) : '-' ?></div>
                </div>
              </div>
            </div>
          </div>

        </div>

        <div class="col-12 col-lg-4">
          <div class="card shadow-sm">
            <div class="card-header bg-light">
              <h6 class="mb-0"><i class="bi bi-flag me-2"></i>Status & Arah Lanjut</h6>
            </div>
            <div class="card-body">
              <?php if ($status === 'menunggu' || $status === 'sedang_direview'): ?>
                <div class="alert alert-info">Abstrak Anda sedang diproses. Silakan pantau halaman ini untuk hasil review.</div>
              <?php elseif ($status === 'revisi'): ?>
                <div class="alert alert-warning">Revisi diminta. Silakan unggah revisi pada tombol di kiri.</div>
              <?php elseif ($status === 'diterima'): ?>
                <div class="alert alert-success">Abstrak diterima. Lanjutkan ke pembayaran agar terdaftar penuh.</div>
              <?php elseif ($status === 'ditolak'): ?>
                <div class="alert alert-danger">Abstrak ditolak. Anda dapat berkonsultasi dengan panitia atau mencoba event lain.</div>
              <?php endif; ?>
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