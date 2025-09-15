<?php
$title   = $title ?? 'Pembayaran';
$history = $history ?? [];

// Split data
$waiting  = array_values(array_filter($history, fn($r) => in_array(strtolower($r['status']), ['pending','rejected'], true)));
$approved = array_values(array_filter($history, fn($r) => strtolower($r['status']) === 'verified'));
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <!-- Header Biru -->
      <div class="header-section header-blue d-flex justify-content-between align-items-center mb-3">
        <div>
          <h3 class="welcome-text mb-1"><i class="bi bi-receipt me-2"></i>Pembayaran</h3>
          <div class="text-white-50">Upload bukti & pantau status verifikasi</div>
        </div>
        <div class="text-end d-none d-md-block">
          <small class="text-white-50 d-block">Hari ini</small>
          <strong class="text-white"><?= date('d M Y') ?></strong>
        </div>
      </div>

      <!-- MENUNGGU VERIFIKASI / PERLU TINDAKAN -->
      <div class="card shadow-sm mb-4 border-0 overflow-hidden">
        <div class="card-header bg-gradient-primary text-white">
          <h5 class="mb-0"><i class="bi bi-hourglass-split me-2"></i>List Pembayaran</h5>
        </div>
        <div class="card-body">
          <?php if (empty($waiting)): ?>
            <div class="text-muted">Tidak ada pembayaran yang menunggu verifikasi.</div>
          <?php else: ?>
            <div class="row g-3">
              <?php foreach ($waiting as $r): ?>
              <div class="col-12 col-md-6 col-xl-4">
                <div class="pay-card h-100 shadow-sm">
                  <div class="d-flex align-items-start justify-content-between mb-2">
                    <h5 class="mb-0"><?= esc($r['title'] ?? '-') ?></h5>
                    <span class="badge bg-<?= esc($r['badge']) ?>"><?= strtoupper($r['status']) ?></span>
                  </div>
                  <div class="small text-muted mb-2">
                    Tanggal Bayar:
                    <strong><?= !empty($r['tanggal_bayar']) ? date('d M Y H:i', strtotime($r['tanggal_bayar'])) : '-' ?></strong><br>
                    Harga:
                    <strong>Rp <?= number_format((int)($r['jumlah'] ?? 0),0,',','.') ?></strong><br>
                    Format Event:
                    <strong><?= esc($r['formatLabel'] ?? '-') ?></strong>
                  </div>
                  <?php if (!empty($r['hint'])): ?>
                    <div class="text-muted small mb-2"><?= esc($r['hint']) ?></div>
                  <?php endif; ?>

                  <div class="d-flex gap-2">
                    <a class="btn btn-primary flex-fill" href="/presenter/pembayaran/detail/<?= (int)$r['id_pembayaran'] ?>">
                      <i class="bi bi-eye"></i> Detail
                    </a>
                    <a class="btn btn-outline-warning flex-fill" href="/presenter/pembayaran/detail/<?= (int)$r['id_pembayaran'] ?>">
                      <i class="bi bi-upload"></i> Re-upload
                    </a>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- RIWAYAT DISETUJUI -->
      <div class="card shadow-sm mb-4 border-0 overflow-hidden">
        <div class="card-header bg-gradient-primary text-white">
          <h5 class="mb-0"><i class="bi bi-check2-circle me-2"></i>Riwayat Pembayaran</h5>
        </div>
        <div class="card-body">
          <?php if (empty($approved)): ?>
            <div class="text-muted">Belum ada pembayaran yang disetujui.</div>
          <?php else: ?>
            <div class="row g-3">
              <?php foreach ($approved as $r): ?>
              <div class="col-12 col-md-6 col-xl-4">
                <div class="pay-card h-100 shadow-sm">
                  <div class="d-flex align-items-start justify-content-between mb-2">
                    <h5 class="mb-0"><?= esc($r['title'] ?? '-') ?></h5>
                    <span class="badge bg-success">VERIFIED</span>
                  </div>
                  <div class="small text-muted mb-2">
                    Tanggal Bayar:
                    <strong><?= !empty($r['tanggal_bayar']) ? date('d M Y H:i', strtotime($r['tanggal_bayar'])) : '-' ?></strong><br>
                    Harga:
                    <strong>Rp <?= number_format((int)($r['jumlah'] ?? 0),0,',','.') ?></strong><br>
                    Format Event:
                    <strong><?= esc($r['formatLabel'] ?? '-') ?></strong>
                  </div>
                  <div class="text-success small mb-2">Pembayaran terverifikasi. Anda resmi terdaftar.</div>
                  <a class="btn btn-outline-primary w-100" href="/presenter/pembayaran/detail/<?= (int)$r['id_pembayaran'] ?>">
                    <i class="bi bi-eye"></i> Detail
                  </a>
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
  }
  .header-section.header-blue{
    background:linear-gradient(135deg,var(--primary-color),#1e40af);
    color:#fff; padding:24px; border-radius:16px; box-shadow:0 8px 28px rgba(0,0,0,.12);
  }
  .welcome-text{ font-weight:700; }
  .bg-gradient-primary{ background:linear-gradient(135deg,var(--primary-color),var(--info-color))!important; }
  .pay-card{
    background:#fff;
    border-radius:14px;
    padding:16px;
    border:1px solid #eef2f7;
  }
  @media (max-width: 767.98px){
    .pay-card{ padding:14px; }
    .header-section.header-blue{ padding:18px; }
  }
</style>