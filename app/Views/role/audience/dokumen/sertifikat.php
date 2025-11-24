<?php
$title = $title ?? 'Sertifikat Saya';
$certs = $certs ?? [];
$pk    = $pk    ?? 'id_dokumen';

$fmtDT = fn($s)=> $s ? date('d M Y H:i', strtotime($s)) : '-';
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_audience') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/dokumen_audience.css'); ?>">

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">
      
      <!-- Header Section -->
      <div class="page-header mb-4">
        <div class="header-content">
          <div class="header-left">
            <div class="header-icon">
              <i class="bi bi-award"></i>
            </div>
            <div>
              <h1 class="page-title"><?= esc($title) ?></h1>
              <p class="page-subtitle">Kelola dan unduh sertifikat Anda</p>
            </div>
          </div>
          <div class="header-right d-none d-md-flex">
            <div class="stat-badge">
              <div class="stat-number"><?= count($certs) ?></div>
              <div class="stat-label">Sertifikat</div>
            </div>
          </div>
        </div>
      </div>

      <!-- Alert Messages -->
      <?php if (session('error')): ?>
        <div class="alert-modern alert-danger">
          <i class="bi bi-exclamation-circle-fill"></i>
          <span><?= esc(session('error')) ?></span>
          <button type="button" class="alert-close" onclick="this.parentElement.remove()">
            <i class="bi bi-x"></i>
          </button>
        </div>
      <?php endif; ?>
      
      <?php if (session('success')): ?>
        <div class="alert-modern alert-success">
          <i class="bi bi-check-circle-fill"></i>
          <span><?= esc(session('success')) ?></span>
          <button type="button" class="alert-close" onclick="this.parentElement.remove()">
            <i class="bi bi-x"></i>
          </button>
        </div>
      <?php endif; ?>

      <!-- Mobile Stats -->
      <div class="d-md-none mb-3">
        <div class="mobile-stat">
          <span class="text-muted">Total Sertifikat:</span>
          <strong class="ms-2"><?= count($certs) ?></strong>
        </div>
      </div>

      <!-- Certificates Content -->
      <?php if (empty($certs)): ?>
        <!-- Empty State -->
        <div class="empty-state-container">
          <div class="empty-state-card">
            <div class="empty-icon">
              <i class="bi bi-award"></i>
            </div>
            <h4 class="empty-title">Belum ada sertifikat</h4>
            <p class="empty-text">
              Sertifikat akan muncul di sini setelah Anda mengikuti event dan dokumen diverifikasi oleh panitia.
            </p>
            <div class="empty-info">
              <i class="bi bi-info-circle me-2"></i>
              Pastikan Anda telah melakukan pembayaran dan mengikuti event hingga selesai
            </div>
          </div>
        </div>
      <?php else: ?>
        <!-- Certificates Grid -->
        <div class="certificates-grid">
          <?php foreach ($certs as $c): ?>
            <?php
              $id   = (int)($c[$pk] ?? 0);
              $name = basename((string)($c['file_path'] ?? 'sertifikat.pdf'));
              $dl   = site_url('audience/dokumen/sertifikat/download/'.$id);
              $pv   = $dl.'?preview=1';
              $ev   = trim((string)($c['event_title'] ?? ''));
              $uploadDate = $c['uploaded_at'] ?? null;
              
              $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
              $iconClass = match($ext) {
                'pdf' => 'bi-file-pdf text-danger',
                'jpg', 'jpeg' => 'bi-file-image text-primary',
                'png' => 'bi-file-image text-info',
                default => 'bi-file-earmark text-secondary'
              };
            ?>
            <div class="cert-card">
              <div class="cert-card-body">
                <!-- File Icon -->
                <div class="cert-icon-wrapper">
                  <div class="cert-icon">
                    <i class="<?= $iconClass ?>"></i>
                  </div>
                  <div class="cert-type"><?= strtoupper($ext) ?></div>
                </div>

                <!-- Certificate Name -->
                <div class="cert-name" title="<?= esc($name) ?>">
                  <?= esc($name) ?>
                </div>

                <!-- Event Badge -->
                <?php if ($ev !== ''): ?>
                  <div class="cert-event">
                    <i class="bi bi-calendar-event"></i>
                    <span><?= esc($ev) ?></span>
                  </div>
                <?php else: ?>
                  <div class="cert-event no-event">
                    <i class="bi bi-question-circle"></i>
                    <span>Event tidak diketahui</span>
                  </div>
                <?php endif; ?>

                <!-- Upload Date -->
                <div class="cert-date">
                  <i class="bi bi-clock"></i>
                  <?= esc($fmtDT($uploadDate)) ?>
                </div>

                <!-- Action Buttons -->
                <div class="cert-actions">
                  <a class="btn-cert btn-preview" href="<?= $pv ?>" target="_blank" rel="noopener">
                    <i class="bi bi-eye"></i>
                    <span>Preview</span>
                  </a>
                  <a class="btn-cert btn-download" href="<?= $dl ?>">
                    <i class="bi bi-download"></i>
                    <span>Download</span>
                  </a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Summary Stats -->
        <div class="summary-stats d-none d-md-block mt-4">
          <div class="stat-item">
            <div class="stat-value"><?= count($certs) ?></div>
            <div class="stat-title">Total Sertifikat</div>
          </div>
          <div class="stat-item">
            <div class="stat-value">
              <?= count(array_filter($certs, fn($c) => !empty($c['event_title']))) ?>
            </div>
            <div class="stat-title">Dengan Info Event</div>
          </div>
          <div class="stat-item">
            <div class="stat-value">
              <?= count(array_filter($certs, fn($c) => strtotime($c['uploaded_at'] ?? '') > strtotime('-1 month'))) ?>
            </div>
            <div class="stat-title">Bulan Ini</div>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
  // Auto-dismiss alerts after 5 seconds
  setTimeout(function() {
    const alerts = document.querySelectorAll('.alert-modern');
    alerts.forEach(function(alert) {
      alert.style.transition = 'opacity 0.3s';
      alert.style.opacity = '0';
      setTimeout(() => alert.remove(), 300);
    });
  }, 5000);

  // Add loading state for download buttons
  document.querySelectorAll('.btn-download').forEach(function(btn) {
    btn.addEventListener('click', function() {
      const icon = this.querySelector('i');
      const originalClass = icon.className;
      icon.className = 'bi bi-hourglass-split';
      
      setTimeout(function() {
        icon.className = originalClass;
      }, 2000);
    });
  });
});
</script>