<?php
$title = $title ?? 'Full Paper';
$rows  = $rows  ?? [];

/* Badge kecil untuk status FP */
$fpBadge = function(string $s) {
  $s = strtoupper($s);
  return match($s) {
    'UPLOADED' => ['label'=>'Diupload','badge'=>'info'],
    'REVISION' => ['label'=>'Revisi','badge'=>'warning'],
    'ACCEPTED' => ['label'=>'Diterima','badge'=>'success'],
    'REJECTED' => ['label'=>'Ditolak','badge'=>'danger'],
    default     => ['label'=>'Belum ada','badge'=>'secondary']
  };
};

/* Badge status abstrak (opsional) */
$absBadge = function($abs) {
  $s = strtolower((string)($abs['status'] ?? ''));
  return match($s) {
    'diterima'         => ['label'=>'Abstrak ACC','badge'=>'success'],
    'ditolak'          => ['label'=>'Abstrak Ditolak','badge'=>'danger'],
    'sedang_direview'  => ['label'=>'Abstrak Direview','badge'=>'info'],
    'menunggu'         => ['label'=>'Abstrak Menunggu','badge'=>'warning'],
    default            => ['label'=>'Belum upload abstrak','badge'=>'secondary'],
  };
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
          <div class="text-white-50">Unggah & pantau status full paper</div>
        </div>
        <div class="text-end d-none d-md-block">
          <small class="text-white-50 d-block">Hari ini</small>
          <strong class="text-white"><?= date('d M Y') ?></strong>
        </div>
      </div>

      <div class="card shadow-sm border-0">
        <div class="card-header bg-gradient-primary text-white">
          <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>Daftar Event Anda</h5>
        </div>
        <div class="card-body">
          <?php if (empty($rows)): ?>
            <div class="text-muted">Belum ada event yang Anda ikuti.</div>
          <?php else: ?>
            <div class="row g-3">
              <?php foreach ($rows as $row):
                $event = $row['event'] ?? [];
                $fp  = $fpBadge($row['fp_status'] ?? 'NONE');
                $abs = $absBadge($row['abs'] ?? null);
              ?>
              <div class="col-12 col-md-6 col-xl-4">
                <div class="event-card h-100 shadow-sm">
                  <div class="d-flex align-items-start justify-content-between mb-2">
                    <h5 class="mb-0"><?= esc($event['title'] ?? '-') ?></h5>
                    <span class="badge bg-<?= esc($row['fp_open'] ? 'success' : 'secondary') ?>">
                      <?= $row['fp_open'] ? 'Open' : 'Closed' ?>
                    </span>
                  </div>

                  <div class="small text-muted mb-2">
                    Tgl Event: <strong><?= !empty($event['event_date']) ? date('d M Y', strtotime($event['event_date'])) : '-' ?></strong><br>
                    Deadline Full Paper: <strong><?= !empty($event['full_paper_deadline']) ? date('d M Y H:i', strtotime($event['full_paper_deadline'])) : '-' ?></strong>
                  </div>

                  <!-- status abstrak -->
                  <div class="mb-2">
                    <span class="badge bg-<?= esc($abs['badge']) ?>"><?= esc($abs['label']) ?></span>
                  </div>

                  <!-- status FP -->
                  <div class="mb-2">
                    <span class="badge bg-<?= esc($fp['badge']) ?>">Full Paper: <?= esc($fp['label']) ?></span>
                  </div>

                  <?php if (!empty($row['abs']) && strtolower($row['abs']['status']) === 'ditolak'): ?>
                    <div class="alert alert-warning py-2 small mb-2">
                      Abstrak ditolak. Panitia menentukan apakah FP tetap diproses atau tidak.
                    </div>
                  <?php endif; ?>

                  <div class="d-flex gap-2">
                    <a class="btn btn-primary flex-fill <?= $row['fp_open'] ? '' : 'disabled' ?>"
                       href="<?= $row['fp_open'] ? site_url('presenter/fullpaper/create/'.(int)$row['event_id']) : '#' ?>">
                      <i class="bi bi-upload"></i> Upload Full Paper
                    </a>
                    <?php if (!empty($row['fp_path'])): ?>
                      <a class="btn btn-outline-secondary flex-fill"
                         href="<?= site_url('presenter/fullpaper/download/'.rawurlencode($row['fp_path'])) ?>">
                        <i class="bi bi-download"></i> Unduh
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
  :root{ --primary-color:#2563eb; --info-color:#06b6d4; }
  .header-section.header-blue{
    background:linear-gradient(135deg,var(--primary-color),#1e40af);
    color:#fff; padding:24px; border-radius:16px; box-shadow:0 8px 28px rgba(0,0,0,.12);
  }
  .bg-gradient-primary{ background:linear-gradient(135deg,var(--primary-color),var(--info-color))!important; }
  .event-card{ background:#fff; border-radius:14px; padding:16px; border:1px solid #eef2f7; }
</style>