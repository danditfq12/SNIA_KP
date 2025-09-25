<?php
$title      = $title      ?? 'Full Paper';
$needUpload = $needUpload ?? [];
$history    = $history    ?? [];

$fmtDate = function($s){ return $s ? date('d M Y', strtotime($s)) : '-'; };
$fmtDT   = function($s){ return $s ? date('d M Y H:i', strtotime($s)) : '-'; };
$formatLabel = function($f){
  $f = strtolower((string)$f);
  return $f === 'both' ? 'Hybrid' : ucfirst($f ?: '-');
};
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <div class="header-section header-blue d-flex justify-content-between align-items-center mb-3">
        <div>
          <h3 class="welcome-text mb-1"><i class="bi bi-file-earmark-text me-2"></i>Full Paper</h3>
          <div class="text-white-50">Upload & pantau status full paper</div>
        </div>
        <div class="text-end d-none d-md-block">
          <small class="text-white-50 d-block">Hari ini</small>
          <strong class="text-white"><?= date('d M Y') ?></strong>
        </div>
      </div>

      <!-- FP perlu upload -->
      <div class="card shadow-sm mb-4 border-0 overflow-hidden">
        <div class="card-header bg-gradient-primary text-white d-flex align-items-center justify-content-between">
          <h5 class="mb-0"><i class="bi bi-upload me-2"></i>Full Paper perlu upload</h5>
          <a href="javascript:history.back()" class="btn btn-sm btn-outline-light">
            <i class="bi bi-arrow-left"></i> Kembali
          </a>
        </div>
        <div class="card-body">
          <?php if (empty($needUpload)): ?>
            <div class="text-muted">Tidak ada event yang membutuhkan upload Full Paper saat ini.</div>
          <?php else: ?>
            <div class="row g-3">
              <?php foreach ($needUpload as $row): ?>
              <div class="col-12 col-md-6 col-xl-4">
                <div class="event-card h-100 shadow-sm">
                  <div class="d-flex align-items-start justify-content-between mb-2">
                    <h5 class="mb-0"><?= esc($row['title'] ?? '-') ?></h5>
                    <span class="badge bg-<?= esc($row['status_badge']) ?>">
                      <?= esc($row['status_label']) ?>
                    </span>
                  </div>

                  <div class="small text-muted mb-2">
                    Tanggal Event:
                    <strong><?= esc($fmtDate($row['event_date'] ?? null)) ?></strong><br>
                    Deadline Full Paper:
                    <strong><?= esc($fmtDT($row['full_paper_deadline'] ?? null)) ?></strong><br>
                    Format:
                    <strong><?= esc($formatLabel($row['format'] ?? '')) ?></strong>
                  </div>

                  <?php if (!empty($row['hint'])): ?>
                    <div class="text-muted small mb-2"><?= esc($row['hint']) ?></div>
                  <?php endif; ?>

                  <?php
                    $label = 'Upload Full Paper';
                    if (($row['fp_status'] ?? '') === 'REVISION' || ($row['fp_status'] ?? '') === 'REJECTED') {
                      $label = 'Upload Ulang (Revisi)';
                    }
                  ?>
                  <div class="d-grid gap-2">
                    <a class="btn btn-primary" href="/presenter/fullpaper/create/<?= (int)$row['event_id'] ?>">
                      <i class="bi bi-upload"></i> <?= esc($label) ?>
                    </a>
                    <a class="btn btn-outline-secondary" href="/presenter/fullpaper/detail/<?= (int)$row['event_id'] ?>">
                      <i class="bi bi-eye"></i> Detail
                    </a>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Riwayat Full Paper -->
      <div class="card shadow-sm mb-4 border-0 overflow-hidden">
        <div class="card-header bg-gradient-primary text-white">
          <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Riwayat Full Paper</h5>
        </div>
        <div class="card-body">
          <?php if (empty($history)): ?>
            <div class="text-muted">Belum ada riwayat full paper.</div>
          <?php else: ?>
            <div class="row g-3">
              <?php foreach ($history as $h): ?>
              <div class="col-12 col-md-6 col-xl-4">
                <div class="event-card h-100 shadow-sm">
                  <div class="d-flex align-items-start justify-content-between mb-1">
                    <h6 class="mb-0"><?= esc($h['event_title'] ?? '-') ?></h6>
                    <span class="badge bg-<?= esc($h['status_badge']) ?>">
                      <?= esc($h['status_label']) ?>
                    </span>
                  </div>

                  <div class="small text-muted mb-2">
                    <span class="badge bg-<?= esc($h['abs_badge']) ?>"><?= esc($h['abs_label']) ?></span>
                  </div>

                  <div class="small text-muted mb-2">
                    Tanggal Event: <strong><?= esc($fmtDate($h['event_date'] ?? null)) ?></strong>
                  </div>

                  <?php if (!empty($h['status_hint'])): ?>
                    <div class="small mb-2 
                      <?= $h['fp_status'] === 'ACCEPTED' ? 'text-success' : ($h['fp_status'] === 'REJECTED' ? 'text-danger' : 'text-muted') ?>">
                      <?= esc($h['status_hint']) ?>
                    </div>
                  <?php endif; ?>

                  <div class="small text-muted">
                    Unggah terakhir: <?= !empty($h['uploaded_at']) ? esc($fmtDT($h['uploaded_at'])) : '-' ?>
                  </div>

                  <div class="mt-2 d-grid gap-2">
                    <a class="btn btn-outline-secondary btn-sm" href="/presenter/fullpaper/detail/<?= (int)$h['event_id'] ?>">
                      <i class="bi bi-eye"></i> Detail
                    </a>

                    <?php if (!empty($h['fp_path'])): ?>
                      <a class="btn btn-outline-secondary btn-sm"
                         href="<?= site_url('presenter/fullpaper/download/'.rawurlencode($h['fp_path'])) ?>">
                        <i class="bi bi-download"></i> Unduh
                      </a>
                    <?php endif; ?>

                    <?php
                      $absAccepted = !empty($h['abs']) && strtolower($h['abs']['status'] ?? '') === 'diterima';
                      $canReupload = $h['is_open'] && $absAccepted && in_array(($h['fp_status'] ?? ''), ['REVISION','REJECTED'], true);
                    ?>
                    <?php if ($canReupload): ?>
                      <a class="btn btn-danger btn-sm" href="/presenter/fullpaper/create/<?= (int)$h['event_id'] ?>">
                        <i class="bi bi-upload"></i> Upload Ulang (Revisi)
                      </a>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<style>
  :root{
    --primary-color:#2563eb; --info-color:#06b6d4;
    --success-color:#10b981; --warning-color:#f59e0b;
    --danger-color:#ef4444;  --secondary:#475569;
  }
  .header-section.header-blue{
    background:linear-gradient(135deg,var(--primary-color),#1e40af);
    color:#fff; padding:24px; border-radius:16px; box-shadow:0 8px 28px rgba(0,0,0,.12);
  }
  .welcome-text{ font-weight:700; }
  .bg-gradient-primary{ background:linear-gradient(135deg,var(--primary-color),var(--info-color))!important; }
  .event-card{
    background:#f8fafc; border-radius:14px; padding:16px; border:1px solid #e5e7eb;
  }
  @media (max-width: 767.98px){
    .event-card{ padding:14px; }
    .header-section.header-blue{ padding:18px; }
  }
</style>
