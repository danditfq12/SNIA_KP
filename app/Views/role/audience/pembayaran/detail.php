<?php
// =========================================
//  Enhanced Pembayaran Detail (Audience) - Midtrans Only
//  Removed: Manual upload, file proof display, reupload functionality
//  Enhanced: Midtrans transaction details, status checking, auto-verification info
// =========================================
$title = $title ?? 'Detail Pembayaran';
$pay   = $pay   ?? [];
$event = $event ?? [];
$voucher = $voucher ?? null;

$amount   = (float)($pay['jumlah'] ?? 0);
$originalAmount = (float)($pay['original_amount'] ?? $amount);
$discountAmount = (float)($pay['discount_amount'] ?? 0);
$status   = (string)($pay['status'] ?? '-');
$tanggal  = $pay['tanggal_bayar'] ?? null;
$verifiedAt = $pay['verified_at'] ?? null;
$autoVerified = (bool)($pay['auto_verified'] ?? false);
$keterangan = $pay['keterangan'] ?? null;
$orderId = $pay['midtrans_order_id'] ?? null;
$transactionId = $pay['midtrans_transaction_id'] ?? null;
$paymentType = $pay['midtrans_payment_type'] ?? null;
$settlementTime = $pay['midtrans_settlement_time'] ?? null;

$payId = (int)($pay['id_pembayaran'] ?? 0);

$badge = match($status) {
    'pending' => 'warning',
    'verified' => 'success', 
    'rejected' => 'danger',
    'canceled' => 'secondary',
    'expired' => 'dark',
    default => 'secondary'
};

$evTitle = $event['title'] ?? 'Event';
$evDate  = isset($event['event_date']) ? date('d M Y', strtotime($event['event_date'])) : '-';
$evTime  = $event['event_time'] ?? '-';

$canRetry = in_array($status, ['canceled', 'expired', 'rejected'], true);
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_audience') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <a href="<?= site_url('audience/pembayaran') ?>" class="btn btn-sm btn-outline-secondary mb-3">
        <i class="bi bi-arrow-left"></i> Kembali
      </a>

      <?php if (session('message')): ?>
        <div class="alert alert-success"><?= esc(session('message')) ?></div>
      <?php endif; ?>
      <?php if (session('error')): ?>
        <div class="alert alert-danger"><?= esc(session('error')) ?></div>
      <?php endif; ?>
      <?php if (session('success')): ?>
        <div class="alert alert-success"><?= esc(session('success')) ?></div>
      <?php endif; ?>
      <?php if (session('info')): ?>
        <div class="alert alert-info"><?= esc(session('info')) ?></div>
      <?php endif; ?>
      <?php if (session('warning')): ?>
        <div class="alert alert-warning"><?= esc(session('warning')) ?></div>
      <?php endif; ?>

      <div class="card shadow-sm border-0">
        <div class="card-body">

          <!-- HERO dengan info status -->
          <div class="pay-hero mb-4">
            <div class="d-flex flex-column flex-md-row align-items-md-start justify-content-between gap-3">
              <div>
                <div class="pay-title mb-1"><?= esc($evTitle) ?></div>
                <div class="pay-tags">
                  <span class="pay-tag"><i class="bi bi-calendar-event"></i> <?= esc($evDate) ?></span>
                  <span class="pay-tag"><i class="bi bi-clock"></i> <?= esc($evTime) ?></span>
                  <span class="pay-tag"><i class="bi bi-credit-card"></i> Digital Payment</span>
                </div>
              </div>
              <div class="text-md-end">
                <div class="small opacity-75 mb-1">Status Pembayaran</div>
                <span class="badge text-uppercase bg-<?= $badge ?> fs-6 px-3 py-2">
                  <?php if ($autoVerified && $status === 'verified'): ?>
                  <i class="bi bi-lightning-charge me-1"></i>
                  <?php endif; ?>
                  <?= esc($status) ?>
                </span>
                <?php if ($autoVerified && $status === 'verified'): ?>
                <div class="small mt-1 opacity-75">Auto-verified</div>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <div class="row g-4">
            <!-- Payment Information -->
            <div class="col-12 col-lg-8">
              <div class="card border-0 bg-light h-100">
                <div class="card-body">
                  <h6 class="card-title mb-3">
                    <i class="bi bi-info-circle me-2"></i>Informasi Pembayaran
                  </h6>

                  <div class="row g-3">
                    <!-- Payment Details -->
                    <div class="col-12 col-md-6">
                      <div class="detail-group">
                        <label class="detail-label">ID Pembayaran</label>
                        <div class="detail-value">#<?= str_pad($payId, 6, '0', STR_PAD_LEFT) ?></div>
                      </div>
                      
                      <div class="detail-group">
                        <label class="detail-label">Metode Pembayaran</label>
                        <div class="detail-value">
                          <span class="badge bg-primary">
                            <i class="bi bi-credit-card me-1"></i>Midtrans Digital
                          </span>
                        </div>
                      </div>

                      <?php if ($orderId): ?>
                      <div class="detail-group">
                        <label class="detail-label">Order ID</label>
                        <div class="detail-value font-monospace"><?= esc($orderId) ?></div>
                      </div>
                      <?php endif; ?>

                      <?php if ($transactionId): ?>
                      <div class="detail-group">
                        <label class="detail-label">Transaction ID</label>
                        <div class="detail-value font-monospace"><?= esc($transactionId) ?></div>
                      </div>
                      <?php endif; ?>

                      <?php if ($paymentType): ?>
                      <div class="detail-group">
                        <label class="detail-label">Payment Type</label>
                        <div class="detail-value"><?= esc(ucwords(str_replace('_', ' ', $paymentType))) ?></div>
                      </div>
                      <?php endif; ?>
                    </div>

                    <!-- Amount Details -->
                    <div class="col-12 col-md-6">
                      <?php if ($discountAmount > 0): ?>
                      <div class="detail-group">
                        <label class="detail-label">Harga Asli</label>
                        <div class="detail-value">Rp <?= number_format($originalAmount, 0, ',', '.') ?></div>
                      </div>
                      
                      <div class="detail-group">
                        <label class="detail-label">Diskon</label>
                        <div class="detail-value text-success">-Rp <?= number_format($discountAmount, 0, ',', '.') ?></div>
                      </div>
                      <?php endif; ?>
                      
                      <div class="detail-group">
                        <label class="detail-label">Total Dibayar</label>
                        <div class="detail-value fw-bold fs-5">Rp <?= number_format($amount, 0, ',', '.') ?></div>
                      </div>
                    </div>

                    <!-- Timestamps -->
                    <div class="col-12">
                      <hr class="my-3">
                      <div class="row">
                        <div class="col-12 col-md-6">
                          <div class="detail-group">
                            <label class="detail-label">Tanggal Pembayaran</label>
                            <div class="detail-value">
                              <?= $tanggal ? esc(date('d M Y, H:i', strtotime($tanggal))) : '-' ?>
                            </div>
                          </div>
                        </div>
                        
                        <?php if ($verifiedAt): ?>
                        <div class="col-12 col-md-6">
                          <div class="detail-group">
                            <label class="detail-label">Tanggal Verifikasi</label>
                            <div class="detail-value">
                              <?= esc(date('d M Y, H:i', strtotime($verifiedAt))) ?>
                            </div>
                          </div>
                        </div>
                        <?php endif; ?>

                        <?php if ($settlementTime): ?>
                        <div class="col-12 col-md-6">
                          <div class="detail-group">
                            <label class="detail-label">Settlement Time</label>
                            <div class="detail-value">
                              <?= esc(date('d M Y, H:i', strtotime($settlementTime))) ?>
                            </div>
                          </div>
                        </div>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>

                  <!-- Voucher Information -->
                  <?php if ($voucher): ?>
                  <div class="voucher-info mt-3">
                    <div class="d-flex align-items-center gap-2 mb-2">
                      <i class="bi bi-tag-fill text-success"></i>
                      <span class="fw-semibold">Voucher Digunakan</span>
                    </div>
                    <div class="voucher-card">
                      <div class="voucher-code"><?= esc($voucher['kode_voucher']) ?></div>
                      <div class="voucher-details">
                        <span class="voucher-type"><?= ucfirst($voucher['tipe']) ?></span>
                        <span class="voucher-value">
                          <?php if (strtolower($voucher['tipe']) === 'percentage'): ?>
                          <?= $voucher['nilai'] ?>%
                          <?php else: ?>
                          Rp <?= number_format($voucher['nilai'], 0, ',', '.') ?>
                          <?php endif; ?>
                        </span>
                      </div>
                    </div>
                  </div>
                  <?php endif; ?>

                  <!-- Status Information -->
                  <div class="status-info mt-3">
                    <?php if ($status === 'pending'): ?>
                      <div class="alert alert-warning mb-0">
                        <i class="bi bi-clock me-2"></i>
                        Pembayaran sedang diproses. Status akan terupdate otomatis setelah transaksi berhasil.
                        <?php if ($orderId): ?>
                        <div class="mt-2">
                          <a href="<?= site_url('audience/pembayaran/check-status/'.$payId) ?>" class="btn btn-sm btn-outline-warning">
                            <i class="bi bi-arrow-clockwise me-1"></i>Refresh Status
                          </a>
                        </div>
                        <?php endif; ?>
                      </div>
                    <?php elseif ($status === 'verified'): ?>
                      <div class="alert alert-success mb-0">
                        <i class="bi bi-check-circle me-2"></i>
                        Pembayaran telah berhasil dan diverifikasi. Anda resmi terdaftar dalam event ini.
                        <?php if ($autoVerified): ?>
                        <small class="d-block mt-1 opacity-75">Verifikasi dilakukan otomatis oleh sistem pembayaran digital.</small>
                        <?php endif; ?>
                      </div>
                    <?php elseif ($status === 'canceled'): ?>
                      <div class="alert alert-secondary mb-0">
                        <i class="bi bi-x-circle me-2"></i>
                        Pembayaran dibatalkan atau gagal.
                        <?php if ($keterangan): ?>
                        <strong>Keterangan:</strong> <?= esc($keterangan) ?>
                        <?php endif; ?>
                        <?php if ($canRetry): ?>
                        <div class="mt-2">
                          <a href="<?= site_url('audience/events/detail/'.(int)($event['id'] ?? 0)) ?>" class="btn btn-sm btn-primary">
                            <i class="bi bi-arrow-repeat me-1"></i>Coba Bayar Lagi
                          </a>
                        </div>
                        <?php endif; ?>
                      </div>
                    <?php elseif ($status === 'expired'): ?>
                      <div class="alert alert-dark mb-0">
                        <i class="bi bi-clock-history me-2"></i>
                        Pembayaran kedaluwarsa. Silakan lakukan pembayaran baru.
                        <?php if ($canRetry): ?>
                        <div class="mt-2">
                          <a href="<?= site_url('audience/events/detail/'.(int)($event['id'] ?? 0)) ?>" class="btn btn-sm btn-primary">
                            <i class="bi bi-arrow-repeat me-1"></i>Bayar Ulang
                          </a>
                        </div>
                        <?php endif; ?>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>

            <!-- Midtrans Payment Info -->
            <div class="col-12 col-lg-4">
              <div class="card border-0 h-100">
                <div class="card-body">
                  <h6 class="card-title mb-3">
                    <i class="bi bi-credit-card me-2"></i>Pembayaran Digital
                  </h6>
                  
                  <div class="midtrans-info">
                    <div class="payment-provider">
                      <img src="https://cdn.jsdelivr.net/gh/midtrans/midtrans-logo@main/source/png/logo.png" alt="Midtrans" height="32" class="mb-2">
                      <div class="small text-muted">Secure Payment Gateway</div>
                    </div>
                    
                    <div class="payment-benefits mt-3">
                      <div class="benefit-item">
                        <i class="bi bi-shield-check text-success"></i>
                        <span>Keamanan tingkat bank</span>
                      </div>
                      <div class="benefit-item">
                        <i class="bi bi-lightning-charge text-warning"></i>
                        <span>Verifikasi otomatis</span>
                      </div>
                      <div class="benefit-item">
                        <i class="bi bi-clock text-info"></i>
                        <span>Proses real-time</span>
                      </div>
                      <div class="benefit-item">
                        <i class="bi bi-headset text-primary"></i>
                        <span>24/7 Customer support</span>
                      </div>
                    </div>
                    
                    <!-- Transaction Timeline -->
                    <?php if ($orderId): ?>
                    <div class="transaction-timeline mt-4">
                      <h6 class="small fw-semibold text-muted mb-2">Timeline Transaksi</h6>
                      <div class="timeline-item <?= $tanggal ? 'completed' : '' ?>">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                          <div class="timeline-title">Pembayaran Dibuat</div>
                          <?php if ($tanggal): ?>
                          <div class="timeline-time"><?= date('d M, H:i', strtotime($tanggal)) ?></div>
                          <?php endif; ?>
                        </div>
                      </div>
                      
                      <div class="timeline-item <?= $status === 'verified' ? 'completed' : ($status === 'pending' ? 'current' : '') ?>">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                          <div class="timeline-title">Proses Verifikasi</div>
                          <?php if ($status === 'pending'): ?>
                          <div class="timeline-time">Sedang diproses...</div>
                          <?php elseif ($verifiedAt): ?>
                          <div class="timeline-time"><?= date('d M, H:i', strtotime($verifiedAt)) ?></div>
                          <?php endif; ?>
                        </div>
                      </div>
                      
                      <div class="timeline-item <?= $status === 'verified' ? 'completed' : '' ?>">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                          <div class="timeline-title">Pembayaran Selesai</div>
                          <?php if ($status === 'verified' && $settlementTime): ?>
                          <div class="timeline-time"><?= date('d M, H:i', strtotime($settlementTime)) ?></div>
                          <?php endif; ?>
                        </div>
                      </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($status === 'pending'): ?>
                    <div class="alert alert-info mt-3 mb-0 small">
                      <i class="bi bi-info-circle me-1"></i>
                      Pembayaran sedang diproses. Jangan menutup browser jika masih ada proses yang berjalan.
                    </div>
                    <?php endif; ?>

                    <?php if ($status === 'verified'): ?>
                    <div class="alert alert-success mt-3 mb-0 small">
                      <i class="bi bi-check-circle me-1"></i>
                      Pembayaran berhasil! Anda dapat mengakses fitur event sekarang.
                    </div>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="mt-4 d-flex flex-wrap gap-2">
            <a href="<?= site_url('audience/events') ?>" class="btn btn-outline-secondary">
              <i class="bi bi-arrow-left me-1"></i>Lihat Event Lain
            </a>
            
            <?php if ($status === 'verified'): ?>
            <a href="<?= site_url('audience/events/detail/'.(int)($event['id'] ?? 0)) ?>" class="btn btn-success">
              <i class="bi bi-calendar-check me-1"></i>Detail Event
            </a>
            <?php endif; ?>

            <?php if ($status === 'pending' && $orderId): ?>
            <a href="<?= site_url('audience/pembayaran/check-status/'.$payId) ?>" class="btn btn-outline-primary">
              <i class="bi bi-arrow-clockwise me-1"></i>Refresh Status
            </a>
            <?php endif; ?>

            <?php if ($canRetry): ?>
            <a href="<?= site_url('audience/events/detail/'.(int)($event['id'] ?? 0)) ?>" class="btn btn-primary">
              <i class="bi bi-arrow-repeat me-1"></i>Coba Lagi
            </a>
            <?php endif; ?>
          </div>

        </div>
      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<style>
  .pay-hero{
    background: linear-gradient(90deg,#2563eb,#60a5fa);
    border-radius: 16px; color:#fff; padding: 16px 20px;
    box-shadow: 0 6px 20px rgba(37,99,235,.18);
  }
  .pay-title{ font-weight:800; line-height:1.25; font-size: clamp(18px, 4.2vw, 24px); }
  .pay-tags{ display:flex; gap:.4rem; flex-wrap:wrap; margin-top:.2rem; }
  .pay-tag{
    background: rgba(255,255,255,.16); border:1px solid rgba(255,255,255,.22);
    color:#fff; border-radius:999px; padding:.28rem .6rem; font-size: .85rem;
    display:inline-flex; align-items:center; gap:.45rem;
  }

  .detail-group {
    margin-bottom: 1rem;
  }

  .detail-label {
    font-size: 0.875rem;
    color: #6b7280;
    margin-bottom: 0.25rem;
    display: block;
  }

  .detail-value {
    font-weight: 500;
    color: #374151;
  }

  .voucher-card {
    background: linear-gradient(135deg, #059669, #10b981);
    color: white;
    padding: 12px 16px;
    border-radius: 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
  }

  .voucher-code {
    font-family: 'Courier New', monospace;
    font-weight: 700;
    font-size: 1.1rem;
  }

  .voucher-details {
    text-align: right;
    font-size: 0.875rem;
  }

  .voucher-type {
    display: block;
    opacity: 0.8;
    text-transform: capitalize;
  }

  .voucher-value {
    font-weight: 700;
  }

  .midtrans-info {
    text-align: center;
  }

  .payment-provider img {
    filter: grayscale(0.1);
  }

  .payment-benefits {
    text-align: left;
  }

  .benefit-item {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 8px;
    font-size: 0.875rem;
  }

  /* Transaction Timeline */
  .transaction-timeline {
    text-align: left;
    border-top: 1px solid #e5e7eb;
    padding-top: 16px;
  }

  .timeline-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    margin-bottom: 16px;
    position: relative;
  }

  .timeline-item:last-child {
    margin-bottom: 0;
  }

  .timeline-marker {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    border: 2px solid #d1d5db;
    background: white;
    flex-shrink: 0;
    margin-top: 2px;
    position: relative;
    z-index: 2;
  }

  .timeline-item.completed .timeline-marker {
    background: #10b981;
    border-color: #10b981;
  }

  .timeline-item.current .timeline-marker {
    background: #f59e0b;
    border-color: #f59e0b;
    animation: pulse 2s infinite;
  }

  @keyframes pulse {
    0%, 100% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.1); opacity: 0.8; }
  }

  .timeline-item:not(:last-child)::after {
    content: '';
    position: absolute;
    left: 5px;
    top: 20px;
    width: 2px;
    height: calc(100% + 4px);
    background: #e5e7eb;
    z-index: 1;
  }

  .timeline-item.completed:not(:last-child)::after {
    background: #10b981;
  }

  .timeline-content {
    flex-grow: 1;
  }

  .timeline-title {
    font-size: 0.875rem;
    font-weight: 500;
    color: #374151;
    margin-bottom: 2px;
  }

  .timeline-time {
    font-size: 0.75rem;
    color: #6b7280;
  }

  .timeline-item.completed .timeline-title {
    color: #065f46;
  }

  .timeline-item.current .timeline-title {
    color: #92400e;
    font-weight: 600;
  }

  @media (min-width: 576px){
    .pay-hero{ padding: 20px 24px; border-radius:18px; }
  }
</style>