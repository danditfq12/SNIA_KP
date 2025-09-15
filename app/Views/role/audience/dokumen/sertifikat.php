<?php
$title = $title ?? 'Sertifikat Saya';
$certs = $certs ?? [];
$pk    = $pk    ?? 'id_dokumen';

$fmtDT = fn($s)=> $s ? date('d M Y H:i', strtotime($s)) : '-';
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_audience') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">
      <!-- Header Section -->
      <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
          <h3 class="mb-1"><?= esc($title) ?></h3>
          <p class="text-muted mb-0">Kelola dan unduh sertifikat Anda</p>
        </div>
        <div class="d-none d-md-block">
          <span class="badge bg-info fs-6">
            <i class="bi bi-award me-1"></i>
            <?= count($certs) ?> Sertifikat
          </span>
        </div>
      </div>

      <!-- Alert Messages -->
      <?php if (session('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
          <i class="bi bi-exclamation-triangle me-2"></i>
          <?= esc(session('error')) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>
      
      <?php if (session('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
          <i class="bi bi-check-circle me-2"></i>
          <?= esc(session('success')) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <!-- Mobile Stats (visible on mobile only) -->
      <div class="d-md-none mb-3">
        <div class="card border-0 bg-light">
          <div class="card-body text-center py-2">
            <small class="text-muted">Total Sertifikat: </small>
            <strong><?= count($certs) ?></strong>
          </div>
        </div>
      </div>

      <!-- Certificates Content -->
      <?php if (empty($certs)): ?>
        <!-- Empty State -->
        <div class="row justify-content-center">
          <div class="col-12 col-md-8 col-lg-6">
            <div class="text-center p-4 p-md-5 border rounded-3 bg-light-subtle">
              <div class="mb-3">
                <i class="bi bi-award display-1 text-secondary opacity-50"></i>
              </div>
              <h5 class="fw-semibold mb-2">Belum ada sertifikat</h5>
              <p class="text-muted mb-3">
                Sertifikat akan muncul di sini setelah Anda mengikuti event dan dokumen diverifikasi oleh panitia.
              </p>
              <div class="small text-muted">
                <i class="bi bi-info-circle me-1"></i>
                Pastikan Anda telah melakukan pembayaran dan mengikuti event hingga selesai
              </div>
            </div>
          </div>
        </div>
      <?php else: ?>
        <!-- Certificates Grid -->
        <div class="row g-3 g-md-4">
          <?php foreach ($certs as $c): ?>
            <?php
              $id   = (int)($c[$pk] ?? 0);
              $name = basename((string)($c['file_path'] ?? 'sertifikat.pdf'));
              $dl   = site_url('audience/dokumen/sertifikat/download/'.$id);
              $pv   = $dl.'?preview=1';
              $ev   = trim((string)($c['event_title'] ?? ''));
              $uploadDate = $c['uploaded_at'] ?? null;
              
              // Determine file extension for icon
              $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
              $iconClass = match($ext) {
                'pdf' => 'bi-filetype-pdf text-danger',
                'jpg', 'jpeg' => 'bi-filetype-jpg text-primary',
                'png' => 'bi-filetype-png text-info',
                default => 'bi-file-earmark text-secondary'
              };
            ?>
            <div class="col-12 col-sm-6 col-lg-4 col-xl-3">
              <div class="card h-100 shadow-sm border-0 certificate-card">
                <div class="card-body d-flex flex-column p-3">
                  <!-- File Icon and Type -->
                  <div class="text-center mb-3">
                    <i class="<?= $iconClass ?> display-6"></i>
                    <div class="small text-muted mt-1"><?= strtoupper($ext) ?></div>
                  </div>

                  <!-- Certificate Name -->
                  <div class="fw-semibold mb-2 text-center" style="font-size: 0.95rem;">
                    <div class="text-truncate" title="<?= esc($name) ?>">
                      <?= esc($name) ?>
                    </div>
                  </div>

                  <!-- Event Information -->
                  <?php if ($ev !== ''): ?>
                    <div class="mb-2">
                      <div class="badge bg-primary-subtle text-primary w-100 text-wrap py-2" style="font-size: 0.75rem;">
                        <i class="bi bi-calendar-event me-1"></i>
                        <?= esc($ev) ?>
                      </div>
                    </div>
                  <?php else: ?>
                    <div class="mb-2">
                      <div class="badge bg-secondary-subtle text-secondary w-100 py-2" style="font-size: 0.75rem;">
                        <i class="bi bi-question-circle me-1"></i>
                        Event tidak diketahui
                      </div>
                    </div>
                  <?php endif; ?>

                  <!-- Upload Date -->
                  <div class="small text-muted text-center mb-3">
                    <i class="bi bi-clock me-1"></i>
                    <?= esc($fmtDT($uploadDate)) ?>
                  </div>

                  <!-- Action Buttons -->
                  <div class="mt-auto">
                    <!-- Desktop Buttons -->
                    <div class="d-none d-sm-block">
                      <div class="d-grid gap-2">
                        <a class="btn btn-outline-primary btn-sm" href="<?= $pv ?>" target="_blank" rel="noopener">
                          <i class="bi bi-eye me-1"></i> Preview
                        </a>
                        <a class="btn btn-primary btn-sm" href="<?= $dl ?>">
                          <i class="bi bi-download me-1"></i> Download
                        </a>
                      </div>
                    </div>
                    
                    <!-- Mobile Buttons -->
                    <div class="d-sm-none">
                      <div class="row g-1">
                        <div class="col-6">
                          <a class="btn btn-outline-primary btn-sm w-100" href="<?= $pv ?>" target="_blank" rel="noopener">
                            <i class="bi bi-eye"></i>
                            <div class="small">Preview</div>
                          </a>
                        </div>
                        <div class="col-6">
                          <a class="btn btn-primary btn-sm w-100" href="<?= $dl ?>">
                            <i class="bi bi-download"></i>
                            <div class="small">Download</div>
                          </a>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Summary Info (Desktop) -->
        <div class="d-none d-md-block mt-4">
          <div class="row">
            <div class="col-12">
              <div class="card border-0 bg-light-subtle">
                <div class="card-body py-3">
                  <div class="row text-center">
                    <div class="col-4">
                      <div class="fw-semibold"><?= count($certs) ?></div>
                      <div class="small text-muted">Total Sertifikat</div>
                    </div>
                    <div class="col-4">
                      <div class="fw-semibold">
                        <?= count(array_filter($certs, fn($c) => !empty($c['event_title']))) ?>
                      </div>
                      <div class="small text-muted">Dengan Info Event</div>
                    </div>
                    <div class="col-4">
                      <div class="fw-semibold">
                        <?= count(array_filter($certs, fn($c) => strtotime($c['uploaded_at'] ?? '') > strtotime('-1 month'))) ?>
                      </div>
                      <div class="small text-muted">Bulan Ini</div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      <?php endif; ?>
    </div>
  </main>
</div>

<!-- Custom Styles -->
<style>
.certificate-card {
  transition: all 0.2s ease-in-out;
}

.certificate-card:hover {
  transform: translateY(-2px);
  box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15) !important;
}

@media (max-width: 576px) {
  .container-fluid {
    padding-left: 1rem !important;
    padding-right: 1rem !important;
  }
  
  .card-body {
    padding: 1rem !important;
  }
  
  h3 {
    font-size: 1.5rem;
  }
}

/* Ensure equal height cards */
.certificate-card {
  height: 100%;
}

/* Better mobile spacing */
@media (max-width: 767px) {
  main {
    padding-top: 60px !important;
  }
  
  .row.g-3 {
    --bs-gutter-x: 0.75rem;
    --bs-gutter-y: 0.75rem;
  }
}

/* Improve text readability */
.badge.text-wrap {
  white-space: normal;
  line-height: 1.2;
}

/* Loading state for images */
.certificate-card img {
  transition: opacity 0.3s ease;
}
</style>

<?= $this->include('partials/footer') ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add loading state for download links
    document.querySelectorAll('a[href*="download"]').forEach(function(link) {
        link.addEventListener('click', function() {
            const icon = this.querySelector('i');
            const originalClass = icon.className;
            icon.className = 'bi bi-hourglass-split me-1';
            
            setTimeout(function() {
                icon.className = originalClass;
            }, 2000);
        });
    });
    
    // Auto-dismiss alerts after 5 seconds
    setTimeout(function() {
        const alerts = document.querySelectorAll('.alert-dismissible');
        alerts.forEach(function(alert) {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        });
    }, 5000);
    
    // Add tooltips for better UX
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
    const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>