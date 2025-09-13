<?php
// View: Detail Event untuk Presenter
$event         = $event ?? [];
$myReg         = $myReg ?? null;
$myAbs         = $myAbs ?? null;
$canUpload     = $canUploadAbstract ?? false;
$canPay        = $canPay ?? false;
$latestPayment = $latestPayment ?? null;

helper(['number']);
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <div class="header-section header-blue d-flex justify-content-between align-items-center mb-3">
        <div>
          <h3 class="welcome-text mb-1"><i class="bi bi-easel2 me-2"></i><?= esc($event['title']) ?></h3>
          <div class="text-muted">Detail event & progress pendaftaran presenter</div>
        </div>
        <div class="text-end d-none d-md-block">
          <small class="text-muted d-block">Tanggal</small>
          <strong><?= date('d M Y, H:i', strtotime($event['event_date'].' '.$event['event_time'])) ?></strong>
        </div>
      </div>

      <div class="row g-3">
        <div class="col-12 col-lg-8">
          <div class="card shadow-sm">
            <div class="card-header bg-white">
              <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Informasi Event</h6>
            </div>
            <div class="card-body">
              <div class="row g-3 small">
                <div class="col-6 col-md-4"><strong>Format</strong><br>
                  <span class="badge <?= $event['format']==='both'?'bg-success':($event['format']==='online'?'bg-info':'bg-primary') ?>">
                    <?= $event['format']==='both'?'Hybrid':ucfirst($event['format']) ?>
                  </span>
                </div>
                <div class="col-6 col-md-4"><strong>Tanggal</strong><br><?= date('d M Y', strtotime($event['event_date'])) ?></div>
                <div class="col-6 col-md-4"><strong>Waktu</strong><br><?= date('H:i', strtotime($event['event_time'])) ?> WIB</div>

                <?php if ($event['format']!=='online'): ?>
                  <div class="col-12"><strong>Lokasi</strong><br><span class="text-muted"><?= esc($event['location'] ?: '-') ?></span></div>
                <?php endif; ?>

                <?php if ($event['format']!=='offline'): ?>
                  <div class="col-12"><strong>Link</strong><br>
                    <?php if (!empty($event['zoom_link'])): ?>
                      <a href="<?= esc($event['zoom_link']) ?>" target="_blank" class="link-primary">Buka Link</a>
                    <?php else: ?>
                      <span class="text-muted">-</span>
                    <?php endif; ?>
                  </div>
                <?php endif; ?>

                <div class="col-12"><strong>Deskripsi</strong><br>
                  <div class="text-muted"><?= nl2br(esc($event['description'] ?? '-')) ?></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Progress saya -->
        <div class="col-12 col-lg-4">
          <div class="card shadow-sm h-100">
            <div class="card-header bg-white">
              <h6 class="mb-0"><i class="bi bi-person-badge me-2"></i>Status Saya</h6>
            </div>
            <div class="card-body">
              <div class="mb-2">
                <small class="text-muted d-block">Pendaftaran</small>
                <?php if ($myReg): ?>
                  <span class="badge bg-secondary"><?= strtoupper(str_replace('_',' ',$myReg['status'])) ?></span>
                <?php else: ?>
                  <span class="badge bg-secondary">BELUM TERDAFTAR</span>
                <?php endif; ?>
              </div>

              <div class="mb-2">
                <small class="text-muted d-block">Abstrak</small>
                <?php if ($myAbs): ?>
                  <?php
                    $map = ['menunggu'=>'warning','sedang_direview'=>'info','diterima'=>'success','ditolak'=>'danger','revisi'=>'secondary'];
                    $cls = $map[$myAbs['status']] ?? 'secondary';
                  ?>
                  <span class="badge bg-<?= $cls ?>"><?= ucfirst(str_replace('_',' ',$myAbs['status'])) ?></span>
                <?php else: ?>
                  <span class="badge bg-secondary">Belum Ada</span>
                <?php endif; ?>
              </div>

              <div class="mb-3">
                <small class="text-muted d-block">Pembayaran</small>
                <?php if ($latestPayment): ?>
                  <?php
                    $pmap = ['pending'=>'warning','verified'=>'success','rejected'=>'danger'];
                    $pcls = $pmap[$latestPayment['status']] ?? 'secondary';
                  ?>
                  <span class="badge bg-<?= $pcls ?>"><?= ucfirst($latestPayment['status']) ?></span>
                <?php else: ?>
                  <span class="badge bg-secondary">Belum Ada</span>
                <?php endif; ?>
              </div>

              <div class="d-grid gap-2">
                <?php if (!$myReg): ?>
                  <a class="btn btn-success" href="<?= site_url('presenter/events/register/'.$event['id']) ?>">
                    <i class="bi bi-person-plus me-1"></i>Daftar Presenter
                  </a>
                <?php endif; ?>

                <?php if ($canUpload && $myReg): ?>
                  <a class="btn btn-primary" href="<?= site_url('presenter/events/abstract/'.$myReg['id']) ?>">
                    <i class="bi bi-upload me-1"></i>Unggah Abstrak
                  </a>
                <?php endif; ?>

                <?php if ($canPay && $myReg): ?>
                  <a class="btn btn-info" href="<?= site_url('presenter/events/payment/'.$myReg['id']) ?>">
                    <i class="bi bi-credit-card me-1"></i>Lanjut Pembayaran
                  </a>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

      </div>

    </div>
  </main>
</div>

<style>
  :root{ --primary-color:#2563eb; --success-color:#10b981; --warning-color:#f59e0b; --danger-color:#ef4444; --info-color:#06b6d4; }
  body{ background:linear-gradient(135deg,#f8fafc 0%,#e2e8f0 100%); }
  .header-section.header-blue{ background:linear-gradient(135deg,var(--primary-color) 0%,#1e40af 100%); color:#fff; padding:28px 24px; border-radius:16px; box-shadow:0 8px 28px rgba(0,0,0,.12); }
  .header-section.header-blue .welcome-text{ color:#fff; font-weight:800; font-size:2rem; }
</style>
