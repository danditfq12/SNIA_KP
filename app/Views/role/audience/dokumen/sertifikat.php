<?php
$title = $title ?? 'Sertifikat & Dokumen Saya';
$docsByEvent = $docsByEvent ?? [];
$stats = $stats ?? ['total_sertifikat' => 0, 'total_lainnya' => 0, 'total_documents' => 0, 'total_events' => 0];
$pk = $pk ?? 'id_dokumen';

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
              <p class="page-subtitle">Kelola sertifikat dan dokumen pendukung event Anda</p>
            </div>
          </div>
          <div class="header-right d-none d-md-flex">
            <div class="stat-badge">
              <div class="stat-number"><?= $stats['total_documents'] ?></div>
              <div class="stat-label">Total Dokumen</div>
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
          <span class="text-muted">Total Dokumen:</span>
          <strong class="ms-2"><?= $stats['total_documents'] ?></strong>
        </div>
      </div>

      <!-- Statistics Summary -->
      <?php if (!empty($docsByEvent)): ?>
        <div class="summary-stats mb-4">
          <div class="stat-item">
            <div class="stat-value"><?= $stats['total_sertifikat'] ?></div>
            <div class="stat-title">Sertifikat</div>
          </div>
          <div class="stat-item">
            <div class="stat-value"><?= $stats['total_lainnya'] ?></div>
            <div class="stat-title">Dokumen Lainnya</div>
          </div>
          <div class="stat-item">
            <div class="stat-value"><?= $stats['total_events'] ?></div>
            <div class="stat-title">Event Diikuti</div>
          </div>
        </div>
      <?php endif; ?>

      <!-- Documents Content -->
      <?php if (empty($docsByEvent)): ?>
        <!-- Empty State -->
        <div class="empty-state-container">
          <div class="empty-state-card">
            <div class="empty-icon">
              <i class="bi bi-inbox"></i>
            </div>
            <h4 class="empty-title">Belum ada dokumen</h4>
            <p class="empty-text">
              Dokumen akan muncul di sini setelah Anda mengikuti event dan dokumen diupload oleh panitia.
            </p>
            <div class="empty-info">
              <i class="bi bi-info-circle me-2"></i>
              Pastikan Anda telah melakukan pembayaran dan mengikuti event hingga selesai
            </div>
          </div>
        </div>
      <?php else: ?>
        <!-- Documents Grouped by Event -->
        <?php foreach ($docsByEvent as $eventData): ?>
          <div class="event-section mb-4">
            <!-- Event Header -->
            <div class="event-header">
              <div class="event-header-left">
                <i class="bi bi-calendar-event"></i>
                <div>
                  <h5 class="event-title"><?= esc($eventData['event_title']) ?></h5>
                  <?php if ($eventData['event_date']): ?>
                    <small class="event-date-small">
                      <i class="bi bi-clock me-1"></i>
                      <?= date('d F Y', strtotime($eventData['event_date'])) ?>
                    </small>
                  <?php endif; ?>
                </div>
              </div>
              <span class="event-badge">
                <?= count($eventData['sertifikat']) + count($eventData['lainnya']) ?> dokumen
              </span>
            </div>

            <!-- Event Body -->
            <div class="event-body">
              
              <!-- Sertifikat Section -->
              <?php if (!empty($eventData['sertifikat'])): ?>
                <div class="document-section">
                  <h6 class="section-title section-title-success">
                    <i class="bi bi-award me-2"></i>Sertifikat (<?= count($eventData['sertifikat']) ?>)
                  </h6>
                  <div class="certificates-grid">
                    <?php foreach ($eventData['sertifikat'] as $cert): ?>
                      <?php
                        $id = (int)($cert[$pk] ?? 0);
                        $name = basename((string)($cert['file_path'] ?? 'sertifikat.pdf'));
                        $dl = site_url('audience/dokumen/sertifikat/download/'.$id);
                        $pv = $dl.'?preview=1';
                        $uploadDate = $cert['uploaded_at'] ?? null;
                        
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
                          <div class="cert-icon-wrapper">
                            <div class="cert-icon">
                              <i class="<?= $iconClass ?>"></i>
                            </div>
                            <div class="cert-type"><?= strtoupper($ext) ?></div>
                          </div>
                          <div class="cert-name" title="<?= esc($name) ?>">
                            <?= esc($name) ?>
                          </div>
                          <div class="cert-date">
                            <i class="bi bi-clock"></i>
                            <?= esc($fmtDT($uploadDate)) ?>
                          </div>
                          <div class="cert-actions">
                            <?php if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'])): ?>
                              <a class="btn-cert btn-preview" href="<?= $pv ?>" target="_blank" rel="noopener">
                                <i class="bi bi-eye"></i>
                                <span>Preview</span>
                              </a>
                            <?php endif; ?>
                            <a class="btn-cert btn-download" href="<?= $dl ?>">
                              <i class="bi bi-download"></i>
                              <span>Download</span>
                            </a>
                          </div>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php endif; ?>

              <!-- Dokumen Lainnya Section -->
              <?php if (!empty($eventData['lainnya'])): ?>
                <div class="document-section">
                  <h6 class="section-title section-title-info">
                    <i class="bi bi-folder me-2"></i>Dokumen Pendukung (<?= count($eventData['lainnya']) ?>)
                  </h6>
                  <div class="certificates-grid">
                    <?php foreach ($eventData['lainnya'] as $doc): ?>
                      <?php
                        $id = (int)($doc[$pk] ?? 0);
                        $name = $doc['syarat'] ?? basename((string)($doc['file_path'] ?? 'document.pdf'));
                        $fileName = basename((string)($doc['file_path'] ?? ''));
                        $dl = site_url('audience/dokumen/sertifikat/download/'.$id);
                        $pv = $dl.'?preview=1';
                        $uploadDate = $doc['uploaded_at'] ?? null;
                        
                        $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
                        $iconClass = match($ext) {
                          'pdf' => 'bi-file-pdf text-danger',
                          'doc', 'docx' => 'bi-file-word text-primary',
                          'ppt', 'pptx' => 'bi-file-easel text-warning',
                          'xls', 'xlsx' => 'bi-file-excel text-success',
                          'jpg', 'jpeg', 'png' => 'bi-file-image text-info',
                          'zip', 'rar' => 'bi-file-zip text-secondary',
                          default => 'bi-file-earmark text-secondary'
                        };
                      ?>
                      <div class="cert-card">
                        <div class="cert-card-body">
                          <div class="cert-icon-wrapper">
                            <div class="cert-icon">
                              <i class="<?= $iconClass ?>"></i>
                            </div>
                            <div class="cert-type"><?= strtoupper($ext) ?></div>
                          </div>
                          <div class="cert-name" title="<?= esc($name) ?>">
                            <?= esc($name) ?>
                          </div>
                          <div class="cert-date">
                            <i class="bi bi-clock"></i>
                            <?= esc($fmtDT($uploadDate)) ?>
                          </div>
                          <div class="cert-actions">
                            <?php if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png'])): ?>
                              <a class="btn-cert btn-preview" href="<?= $pv ?>" target="_blank" rel="noopener">
                                <i class="bi bi-eye"></i>
                                <span>Preview</span>
                              </a>
                            <?php endif; ?>
                            <a class="btn-cert btn-download" href="<?= $dl ?>">
                              <i class="bi bi-download"></i>
                              <span>Download</span>
                            </a>
                          </div>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php endif; ?>

            </div>
          </div>
        <?php endforeach; ?>
      <?php endif; ?>
    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<style>
/* === EVENT SECTION === */
.event-section {
  background: var(--white);
  border: 1px solid var(--border);
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 2px 8px var(--shadow);
}

.event-header {
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
  padding: 1.5rem;
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 1rem;
}

.event-header-left {
  display: flex;
  align-items: center;
  gap: 1rem;
  color: var(--white);
}

.event-header-left > i {
  font-size: 1.5rem;
  flex-shrink: 0;
}

.event-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--white);
  margin: 0;
}

.event-date-small {
  color: rgba(255, 255, 255, 0.9);
  font-size: 0.875rem;
}

.event-badge {
  background: rgba(255, 255, 255, 0.2);
  border: 1px solid rgba(255, 255, 255, 0.3);
  color: var(--white);
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-size: 0.875rem;
  font-weight: 600;
}

.event-body {
  padding: 2rem;
}

.document-section {
  margin-bottom: 2rem;
}

.document-section:last-child {
  margin-bottom: 0;
}

.section-title {
  font-size: 1rem;
  font-weight: 700;
  margin-bottom: 1rem;
  display: flex;
  align-items: center;
}

.section-title-success {
  color: var(--success);
}

.section-title-info {
  color: #0ea5e9;
}

/* === RESPONSIVE === */
@media (max-width: 768px) {
  .event-header {
    padding: 1rem;
  }

  .event-header-left > i {
    font-size: 1.25rem;
  }

  .event-title {
    font-size: 1rem;
  }

  .event-body {
    padding: 1.5rem;
  }

  .summary-stats {
    grid-template-columns: 1fr;
    gap: 1rem;
    padding: 1.5rem;
  }
}

@media (min-width: 768px) and (max-width: 991px) {
  .summary-stats {
    grid-template-columns: repeat(3, 1fr);
  }
}
</style>

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