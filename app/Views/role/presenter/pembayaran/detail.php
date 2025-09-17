<?php
$title = $title ?? 'Detail Pembayaran';
$pay = $pay ?? [];
$event = $event ?? [];
$voucher = $voucher ?? null;

$status = strtolower($pay['status'] ?? 'pending');
$badgeMap = [
    'pending' => 'warning',
    'verified' => 'success',
    'canceled' => 'danger',
    'expired' => 'dark'
];
$badge = $badgeMap[$status] ?? 'secondary';

$isMidtrans = ($pay['metode'] ?? '') === 'midtrans';
$hasOrderId = !empty($pay['midtrans_order_id']);
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <!-- Header -->
      <div class="header-section header-blue d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 class="welcome-text mb-1">
            <i class="bi bi-receipt"></i> Detail Pembayaran
          </h2>
          <div class="text-white-50"><?= esc($event['title'] ?? '-') ?></div>
        </div>
        <div>
          <span class="badge bg-<?= esc($badge) ?> fs-6 px-3 py-2">
            <?= strtoupper($status) ?>
          </span>
        </div>
      </div>

      <div class="row g-4">
        <!-- Kiri: Info Pembayaran -->
        <div class="col-12 col-lg-8">
          <!-- Payment Details Card -->
          <div class="card shadow-sm mb-4">
            <div class="card-header bg-light">
              <h5 class="mb-0">
                <i class="bi bi-credit-card me-2"></i>
                Informasi Pembayaran
              </h5>
            </div>
            <div class="card-body">
              <div class="row g-4">
                <div class="col-md-6">
                  <div class="info-group">
                    <label class="info-label">Metode Pembayaran</label>
                    <div class="info-value">
                      <i class="bi bi-credit-card-2-front text-primary me-2"></i>
                      Pembayaran Digital (Midtrans)
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="info-group">
                    <label class="info-label">Jumlah Pembayaran</label>
                    <div class="info-value payment-amount">
                      Rp <?= number_format((int)($pay['jumlah'] ?? 0), 0, ',', '.') ?>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="info-group">
                    <label class="info-label">Tanggal Pembayaran</label>
                    <div class="info-value">
                      <?= !empty($pay['tanggal_bayar']) ? date('d M Y H:i', strtotime($pay['tanggal_bayar'])) : '-' ?>
                    </div>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="info-group">
                    <label class="info-label">Mode Kehadiran</label>
                    <div class="info-value">
                      <span class="badge bg-primary">Presenter (Offline)</span>
                    </div>
                  </div>
                </div>
                
                <?php if ($hasOrderId): ?>
                <div class="col-md-6">
                  <div class="info-group">
                    <label class="info-label">Order ID</label>
                    <div class="info-value">
                      <code class="text-primary"><?= esc($pay['midtrans_order_id']) ?></code>
                    </div>
                  </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($pay['verified_at'])): ?>
                <div class="col-md-6">
                  <div class="info-group">
                    <label class="info-label">Tanggal Verifikasi</label>
                    <div class="info-value">
                      <?= date('d M Y H:i', strtotime($pay['verified_at'])) ?>
                      <?php if (!empty($pay['auto_verified'])): ?>
                        <span class="badge bg-success ms-2">Auto</span>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
                <?php endif; ?>
                
                <?php if (!empty($pay['keterangan'])): ?>
                <div class="col-12">
                  <div class="info-group">
                    <label class="info-label">Keterangan</label>
                    <div class="info-value">
                      <?= esc($pay['keterangan']) ?>
                    </div>
                  </div>
                </div>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- Voucher Info -->
          <?php if ($voucher): ?>
          <div class="card shadow-sm mb-4 border-success">
            <div class="card-header bg-success text-white">
              <h6 class="mb-0">
                <i class="bi bi-tag me-2"></i>
                Voucher Diterapkan
              </h6>
            </div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="info-label">Kode Voucher</label>
                  <div class="info-value">
                    <strong><?= esc($voucher['kode_voucher']) ?></strong>
                  </div>
                </div>
                <div class="col-md-6">
                  <label class="info-label">Diskon</label>
                  <div class="info-value text-success">
                    <?php
                    $originalAmount = (int)($pay['original_amount'] ?? $pay['jumlah']);
                    $finalAmount = (int)($pay['jumlah'] ?? 0);
                    $discount = $originalAmount - $finalAmount;
                    ?>
                    - Rp <?= number_format($discount, 0, ',', '.') ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <?php endif; ?>

          <!-- Event Info -->
          <div class="card shadow-sm">
            <div class="card-header bg-light">
              <h6 class="mb-0">
                <i class="bi bi-calendar-event me-2"></i>
                Informasi Event
              </h6>
            </div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="info-label">Tanggal Event</label>
                  <div class="info-value">
                    <?= !empty($event['event_date']) ? date('d M Y', strtotime($event['event_date'])) : '-' ?>
                  </div>
                </div>
                <div class="col-md-6">
                  <label class="info-label">Waktu</label>
                  <div class="info-value">
                    <?= esc($event['event_time'] ?? '-') ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Kanan: Status & Actions -->
        <div class="col-12 col-lg-4">
          <!-- Status Card -->
          <div class="card shadow-sm mb-4 <?= $status === 'verified' ? 'border-success' : ($status === 'pending' ? 'border-warning' : 'border-danger') ?>">
            <div class="card-header bg-<?= $badge ?> text-white">
              <h6 class="mb-0">
                <i class="bi bi-info-circle me-2"></i>
                Status Pembayaran
              </h6>
            </div>
            <div class="card-body">
              <div class="text-center mb-3">
                <div class="status-icon bg-<?= $badge ?> text-white mb-3">
                  <?php if ($status === 'verified'): ?>
                    <i class="bi bi-check-circle"></i>
                  <?php elseif ($status === 'pending'): ?>
                    <i class="bi bi-hourglass-split"></i>
                  <?php elseif ($status === 'canceled'): ?>
                    <i class="bi bi-x-circle"></i>
                  <?php else: ?>
                    <i class="bi bi-clock"></i>
                  <?php endif; ?>
                </div>
                <h5 class="text-<?= $badge ?> mb-2"><?= strtoupper($status) ?></h5>
              </div>

              <div class="status-description">
                <?php if ($status === 'verified'): ?>
                  <div class="alert alert-success mb-0">
                    <i class="bi bi-check-circle me-2"></i>
                    <strong>Pembayaran Berhasil!</strong><br>
                    Anda telah resmi terdaftar sebagai presenter. Semua fitur presenter sudah aktif.
                  </div>
                <?php elseif ($status === 'pending'): ?>
                  <div class="alert alert-warning mb-0">
                    <i class="bi bi-hourglass-split me-2"></i>
                    <strong>Sedang Diproses</strong><br>
                    Pembayaran sedang diverifikasi. Status akan diperbarui otomatis.
                  </div>
                <?php elseif ($status === 'canceled'): ?>
                  <div class="alert alert-danger mb-0">
                    <i class="bi bi-x-circle me-2"></i>
                    <strong>Pembayaran Dibatalkan</strong><br>
                    Pembayaran tidak berhasil atau dibatalkan.
                  </div>
                <?php elseif ($status === 'expired'): ?>
                  <div class="alert alert-dark mb-0">
                    <i class="bi bi-clock me-2"></i>
                    <strong>Pembayaran Kedaluwarsa</strong><br>
                    Waktu pembayaran telah habis.
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- Actions Card -->
          <div class="card shadow-sm">
            <div class="card-header bg-light">
              <h6 class="mb-0">
                <i class="bi bi-gear me-2"></i>
                Aksi
              </h6>
            </div>
            <div class="card-body">
              <div class="d-grid gap-2">
                <?php if ($status === 'pending' && $hasOrderId): ?>
                  <a href="<?= site_url('presenter/pembayaran/check-status/' . (int)$pay['id_pembayaran']) ?>" 
                     class="btn btn-primary">
                    <i class="bi bi-arrow-clockwise me-1"></i>
                    Refresh Status
                  </a>
                <?php endif; ?>

                <?php if ($status === 'pending'): ?>
                  <button type="button" class="btn btn-outline-danger" onclick="cancelPayment()">
                    <i class="bi bi-x-circle me-1"></i>
                    Batalkan Pembayaran
                  </button>
                <?php endif; ?>

                <a href="<?= site_url('presenter/pembayaran') ?>" class="btn btn-outline-secondary">
                  <i class="bi bi-arrow-left me-1"></i>
                  Kembali
                </a>
              </div>
            </div>
          </div>

          <!-- Payment Method Info -->
          <div class="card shadow-sm mt-4 border-info">
            <div class="card-header bg-info text-white">
              <h6 class="mb-0">
                <i class="bi bi-shield-check me-2"></i>
                Keamanan Pembayaran
              </h6>
            </div>
            <div class="card-body">
              <div class="small text-muted">
                <div class="d-flex align-items-center mb-2">
                  <i class="bi bi-check-circle text-success me-2"></i>
                  <span>Transaksi aman dengan enkripsi SSL</span>
                </div>
                <div class="d-flex align-items-center mb-2">
                  <i class="bi bi-check-circle text-success me-2"></i>
                  <span>Verifikasi otomatis real-time</span>
                </div>
                <div class="d-flex align-items-center mb-2">
                  <i class="bi bi-check-circle text-success me-2"></i>
                  <span>Mendukung berbagai metode pembayaran</span>
                </div>
                <div class="d-flex align-items-center">
                  <i class="bi bi-check-circle text-success me-2"></i>
                  <span>Powered by Midtrans</span>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<style>
:root {
  --primary-color: #2563eb;
  --primary-deep: #1e40af;
}

.header-section.header-blue {
  background: linear-gradient(135deg, var(--primary-color), var(--primary-deep));
  color: #fff;
  padding: 24px;
  border-radius: 16px;
  box-shadow: 0 8px 28px rgba(0, 0, 0, .12);
}

.welcome-text {
  font-weight: 700;
}

.info-group {
  margin-bottom: 16px;
}

.info-label {
  font-size: 0.875rem;
  font-weight: 600;
  color: #64748b;
  margin-bottom: 4px;
  display: block;
}

.info-value {
  font-size: 1rem;
  color: #1e293b;
  font-weight: 500;
}

.payment-amount {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--primary-color);
}

.status-icon {
  width: 80px;
  height: 80px;
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 2rem;
  margin: 0 auto;
}

.card {
  border-radius: 12px;
  border: 1px solid #e2e8f0;
}

.btn {
  border-radius: 8px;
  font-weight: 600;
  padding: 0.5rem 1rem;
}

code {
  background: #f1f5f9;
  padding: 2px 6px;
  border-radius: 4px;
  font-size: 0.875rem;
}

@media (max-width: 768px) {
  .header-section.header-blue {
    padding: 16px;
  }
  
  .status-icon {
    width: 60px;
    height: 60px;
    font-size: 1.5rem;
  }
}
</style>

<script>
// Cancel Payment Function
function cancelPayment() {
    if (confirm('Yakin ingin membatalkan pembayaran ini? Aksi ini tidak dapat dibatalkan.')) {
        // Create form and submit
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = '<?= site_url('presenter/pembayaran/cancel/' . (int)$pay['id_pembayaran']) ?>';
        
        // Add CSRF token
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '<?= csrf_token() ?>';
        csrfInput.value = '<?= csrf_hash() ?>';
        form.appendChild(csrfInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}

// Auto refresh for pending payments
<?php if ($status === 'pending'): ?>
(function() {
    // Auto refresh every 30 seconds for pending payments
    setTimeout(() => {
        window.location.reload();
    }, 30000);
    
    console.log('Pending payment detected. Page will auto-refresh in 30 seconds.');
})();
<?php endif; ?>

// Flash messages
<?php if (session('success')): ?>
    console.log('Success: <?= esc(session('success')) ?>');
<?php endif; ?>

<?php if (session('error')): ?>
    console.log('Error: <?= esc(session('error')) ?>');
<?php endif; ?>

<?php if (session('info')): ?>
    console.log('Info: <?= esc(session('info')) ?>');
<?php endif; ?>
</script>