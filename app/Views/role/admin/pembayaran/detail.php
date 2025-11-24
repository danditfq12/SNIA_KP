<?php
// ===== Variable Initialization =====
$title = $title ?? 'Detail Pembayaran';
$pembayaran = $pembayaran ?? [];
$voucher = $voucher ?? null;
$verified_by = $verified_by ?? null;

$amount = (int)($pembayaran['jumlah'] ?? 0);
$status = $pembayaran['status'] ?? 'pending';

// ===== Pricing Tier Information =====
$pricingTier = $pembayaran['pricing_tier'] ?? null;
$originalAmount = (int)($pembayaran['original_amount'] ?? $amount);

// ===== Status Mapping =====
$statusClass = match($status) {
  'pending' => 'status-pending',
  'verified' => 'status-verified',
  'rejected' => 'status-rejected',
  default => 'status-default'
};

$statusText = match($status) {
  'pending' => 'Pending',
  'verified' => 'Terverifikasi',
  'rejected' => 'Ditolak',
  default => 'Unknown'
};

// ===== Pricing Tier Display =====
$pricingTierDisplay = match($pricingTier) {
  'early_bird' => ['label' => 'Early Bird (Offline)', 'class' => 'tier-early-bird', 'icon' => 'lightning-charge'],
  'wave_1' => ['label' => 'Gelombang 1 (Online)', 'class' => 'tier-wave1', 'icon' => 'tag'],
  'regular' => ['label' => 'Gelombang 2', 'class' => 'tier-regular', 'icon' => 'tag'],
  'on_site' => ['label' => 'Gelombang 3', 'class' => 'tier-onsite', 'icon' => 'geo-alt'],
  default => ['label' => 'Standard', 'class' => 'tier-standard', 'icon' => 'cash']
};
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_admin') ?>
<?= $this->include('partials/alerts') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/pembayaran_detail_admin.css'); ?>">

<div id="content">
  <main class="flex-fill" style="padding-top: 70px;">
    <div class="container-fluid px-3 px-md-4 py-4">

      <!-- Header Section -->
      <div class="header-section d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 class="header-title mb-2">
            <i class="bi bi-credit-card me-3"></i><?= esc($title) ?>
          </h2>
          <p class="header-subtitle mb-0">Informasi lengkap pembayaran & proses verifikasi</p>
        </div>
        <div class="header-actions">
          <a href="<?= site_url('admin/pembayaran') ?>" class="btn btn-outline-light btn-lg">
            <i class="bi bi-arrow-left me-2"></i>Kembali
          </a>
        </div>
      </div>

      <div class="row g-4">

        <!-- User Information Card -->
        <div class="col-lg-6">
          <div class="info-card">
            <div class="info-card-header">
              <h5 class="info-card-title">
                <i class="bi bi-person me-2"></i>Informasi Pengguna
              </h5>
            </div>
            <div class="info-card-body">
              
              <!-- User Profile -->
              <div class="user-profile">
                <div class="user-avatar">
                  <?= strtoupper(substr(($pembayaran['nama_lengkap'] ?? 'U'), 0, 1)) ?>
                </div>
                <div class="user-details">
                  <h6 class="user-name"><?= esc($pembayaran['nama_lengkap'] ?? 'N/A') ?></h6>
                  <div class="user-email"><?= esc($pembayaran['email'] ?? 'N/A') ?></div>
                  
                  <!-- User Badges -->
                  <div class="user-badges">
                    <span class="role-badge <?= ($pembayaran['role'] ?? '') === 'presenter' ? 'role-presenter' : 'role-audience' ?>">
                      <?= ucfirst($pembayaran['role'] ?? 'audience') ?>
                    </span>
                    <?php if (!empty($pembayaran['participation_type'])): ?>
                      <span class="participation-badge">
                        <?= ucfirst($pembayaran['participation_type']) ?>
                      </span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>

              <!-- User Details Grid -->
              <div class="details-grid">
                <div class="detail-item">
                  <label class="detail-label">Tanggal Registrasi</label>
                  <div class="detail-value">
                    <?php
                      $reg = $pembayaran['created_at'] ?? $pembayaran['tanggal_bayar'] ?? null;
                      echo $reg ? date('d/m/Y', strtotime($reg)) : '-';
                    ?>
                  </div>
                </div>
                <div class="detail-item">
                  <label class="detail-label">ID User</label>
                  <div class="detail-value"><?= esc($pembayaran['id_user'] ?? 'N/A') ?></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Payment Information Card -->
        <div class="col-lg-6">
          <div class="info-card">
            <div class="info-card-header">
              <h5 class="info-card-title">
                <i class="bi bi-cash-coin me-2"></i>Informasi Pembayaran
              </h5>
            </div>
            <div class="info-card-body">
              
              <!-- Payment Summary -->
              <div class="payment-summary">
                <div class="payment-amount">Rp <?= number_format($amount, 0, ',', '.') ?></div>
                <span class="payment-status <?= $statusClass ?>"><?= $statusText ?></span>
              </div>

              <!-- Pricing Tier Badge -->
              <?php if ($pricingTier): ?>
                <div class="pricing-tier-section">
                  <div class="pricing-tier-badge <?= $pricingTierDisplay['class'] ?>">
                    <i class="bi bi-<?= $pricingTierDisplay['icon'] ?> me-2"></i>
                    <?= $pricingTierDisplay['label'] ?>
                  </div>
                  
                  <?php if ($pricingTier === 'early_bird' && $originalAmount > $amount): ?>
                    <div class="early-bird-savings">
                      <i class="bi bi-piggy-bank me-2"></i>
                      Hemat: Rp <?= number_format($originalAmount - $amount, 0, ',', '.') ?>
                    </div>
                  <?php endif; ?>
                </div>
              <?php endif; ?>

              <!-- Payment Details Grid -->
              <div class="details-grid">
                <div class="detail-item">
                  <label class="detail-label">ID Pembayaran</label>
                  <div class="detail-value">#PAY<?= str_pad((int)($pembayaran['id_pembayaran'] ?? 0), 4, '0', STR_PAD_LEFT) ?></div>
                </div>
                <div class="detail-item">
                  <label class="detail-label">Metode</label>
                  <div class="detail-value"><?= esc($pembayaran['metode'] ?? '-') ?></div>
                </div>
                
                <?php if ($pricingTier && $originalAmount != $amount): ?>
                  <div class="detail-item">
                    <label class="detail-label">Harga Asli</label>
                    <div class="detail-value original-amount">
                      <del>Rp <?= number_format($originalAmount, 0, ',', '.') ?></del>
                    </div>
                  </div>
                  <div class="detail-item">
                    <label class="detail-label">Diskon <?= $pricingTierDisplay['label'] ?></label>
                    <div class="detail-value discount-amount">
                      -Rp <?= number_format($originalAmount - $amount, 0, ',', '.') ?>
                    </div>
                  </div>
                <?php endif; ?>
                
                <div class="detail-item">
                  <label class="detail-label">Tanggal Bayar</label>
                  <div class="detail-value">
                    <?= !empty($pembayaran['tanggal_bayar']) ? date('d/m/Y H:i', strtotime($pembayaran['tanggal_bayar'])) : '-' ?>
                  </div>
                </div>
                
                <?php if (!empty($pembayaran['verified_at'])): ?>
                <div class="detail-item">
                  <label class="detail-label">Tanggal Verifikasi</label>
                  <div class="detail-value"><?= date('d/m/Y H:i', strtotime($pembayaran['verified_at'])) ?></div>
                </div>
                <?php endif; ?>

                <?php if (!empty($pembayaran['midtrans_transaction_id'])): ?>
                <div class="detail-item full-width">
                  <label class="detail-label">Transaction ID</label>
                  <div class="detail-value transaction-id">
                    <?= esc($pembayaran['midtrans_transaction_id']) ?>
                    <?php if (!empty($pembayaran['auto_verified'])): ?>
                      <span class="auto-badge">AUTO</span>
                    <?php endif; ?>
                  </div>
                </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

        <!-- Pricing Breakdown Card -->
        <?php if ($pricingTier): ?>
        <div class="col-12">
          <div class="pricing-breakdown-card">
            <div class="pricing-breakdown-header">
              <h5 class="pricing-breakdown-title">
                <i class="bi bi-receipt me-2"></i>Rincian Harga
              </h5>
            </div>
            <div class="pricing-breakdown-body">
              <div class="breakdown-items">
                <div class="breakdown-item">
                  <span class="breakdown-label">Harga Event (<?= $pricingTierDisplay['label'] ?>)</span>
                  <span class="breakdown-value">Rp <?= number_format($originalAmount, 0, ',', '.') ?></span>
                </div>
                
                <?php if ($pricingTier === 'early_bird' && $originalAmount > $amount): ?>
                  <div class="breakdown-item discount">
                    <span class="breakdown-label">
                      <i class="bi bi-lightning-charge me-1"></i>Diskon Early Bird
                    </span>
                    <span class="breakdown-value">-Rp <?= number_format($originalAmount - $amount, 0, ',', '.') ?></span>
                  </div>
                <?php endif; ?>
                
                <?php if ($voucher): ?>
                  <div class="breakdown-item discount">
                    <span class="breakdown-label">
                      <i class="bi bi-ticket-perforated me-1"></i>Voucher: <?= esc($voucher['kode_voucher']) ?>
                    </span>
                    <span class="breakdown-value">
                      -Rp <?= number_format((int)($pembayaran['discount_amount'] ?? 0), 0, ',', '.') ?>
                    </span>
                  </div>
                <?php endif; ?>
                
                <div class="breakdown-divider"></div>
                
                <div class="breakdown-item total">
                  <span class="breakdown-label">Total Pembayaran</span>
                  <span class="breakdown-value">Rp <?= number_format($amount, 0, ',', '.') ?></span>
                </div>
              </div>
              
              <!-- Pricing Tier Info -->
              <div class="tier-info-box">
                <div class="tier-info-icon">
                  <i class="bi bi-<?= $pricingTierDisplay['icon'] ?>"></i>
                </div>
                <div class="tier-info-content">
                  <div class="tier-info-title">Kategori Harga: <?= $pricingTierDisplay['label'] ?></div>
                  <div class="tier-info-description">
                    <?php
                    echo match($pricingTier) {
                      'early_bird' => 'User mendaftar offline dengan harga early bird dan mendapat diskon spesial (Gelombang 1)',
                      'wave_1' => 'User mendaftar online di Gelombang 1 (gelombang pertama)',
                      'regular' => 'User mendaftar di Gelombang 2 dengan harga regular',
                      'on_site' => 'User mendaftar di Gelombang 3 (on-site/hari H)',
                      default => 'User mendaftar dengan harga standar'
                    };
                    ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <!-- Event Information -->
        <?php if (!empty($pembayaran['event_title'])): ?>
        <div class="col-12">
          <div class="event-info-card">
            <div class="event-content">
              <div class="event-icon">
                <i class="bi bi-calendar-event"></i>
              </div>
              <div class="event-details">
                <h6 class="event-title"><?= esc($pembayaran['event_title']) ?></h6>
              </div>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <!-- Voucher Information -->
        <?php if ($voucher && $pricingTier !== 'early_bird'): ?>
        <div class="col-12">
          <div class="voucher-card">
            <div class="voucher-content">
              <div class="voucher-icon">
                <i class="bi bi-ticket-perforated"></i>
              </div>
              <div class="voucher-details">
                <h6 class="voucher-title">Voucher: <?= esc($voucher['kode_voucher']) ?></h6>
                <div class="voucher-value">
                  <?= ($voucher['tipe'] ?? '') === 'percentage' 
                      ? ($voucher['nilai'] ?? 0).'%' 
                      : 'Rp '.number_format((int)($voucher['nilai'] ?? 0), 0, ',', '.') ?>
                </div>
              </div>
            </div>
            <div class="voucher-discount">
              <label class="discount-label">Diskon:</label>
              <div class="discount-value">Rp <?= number_format((int)($pembayaran['discount_amount'] ?? 0), 0, ',', '.') ?></div>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <!-- Presenter Features -->
        <?php if (($pembayaran['role'] ?? '') === 'presenter' && $status === 'verified'): ?>
        <div class="col-12">
          <div class="features-card">
            <div class="features-header">
              <h5 class="features-title">
                <i class="bi bi-unlock me-2"></i>Fitur yang Dibuka
              </h5>
            </div>
            <div class="features-body">
              <div class="features-grid">
                <div class="feature-item">
                  <div class="feature-icon bg-success">
                    <i class="bi bi-qr-code-scan"></i>
                  </div>
                  <span class="feature-name">QR Attendance</span>
                </div>
                <div class="feature-item">
                  <div class="feature-icon bg-info">
                    <i class="bi bi-download"></i>
                  </div>
                  <span class="feature-name">Download LoA</span>
                </div>
                <div class="feature-item">
                  <div class="feature-icon bg-primary">
                    <i class="bi bi-speedometer2"></i>
                  </div>
                  <span class="feature-name">Dashboard Presenter</span>
                </div>
                <div class="feature-item">
                  <div class="feature-icon bg-warning">
                    <i class="bi bi-award"></i>
                  </div>
                  <span class="feature-name">Generate Sertifikat</span>
                </div>
              </div>
              <?php if (!empty($pembayaran['features_unlocked_at'])): ?>
                <div class="features-unlock-date">
                  Dibuka: <?= date('d/m/Y H:i', strtotime($pembayaran['features_unlocked_at'])) ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <!-- Timeline & Actions -->
        <div class="col-lg-8">
          <div class="timeline-card">
            <div class="timeline-header">
              <h5 class="timeline-title">
                <i class="bi bi-clock-history me-2"></i>Timeline Pembayaran
              </h5>
            </div>
            <div class="timeline-body">
              <div class="timeline">
                
                <!-- Payment Created -->
                <div class="timeline-item">
                  <div class="timeline-marker bg-primary"></div>
                  <div class="timeline-content">
                    <div class="timeline-time">
                      <?= !empty($pembayaran['tanggal_bayar']) ? date('d M Y, H:i', strtotime($pembayaran['tanggal_bayar'])) : 'N/A' ?>
                    </div>
                    <div class="timeline-title">Pembayaran dibuat</div>
                    <div class="timeline-description">
                      User membuat pembayaran 
                      <?php if ($pricingTier): ?>
                        dengan harga <strong><?= $pricingTierDisplay['label'] ?></strong>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>

                <!-- Verification Status -->
                <?php if ($status === 'verified' && !empty($pembayaran['verified_at'])): ?>
                <div class="timeline-item">
                  <div class="timeline-marker bg-success"></div>
                  <div class="timeline-content">
                    <div class="timeline-time">
                      <?= date('d M Y, H:i', strtotime($pembayaran['verified_at'])) ?>
                    </div>
                    <div class="timeline-title">Pembayaran diverifikasi</div>
                    <?php if ($verified_by): ?>
                      <div class="timeline-description">oleh <?= esc($verified_by['nama_lengkap']) ?></div>
                    <?php endif; ?>
                  </div>
                </div>
                <?php elseif ($status === 'rejected' && !empty($pembayaran['verified_at'])): ?>
                <div class="timeline-item">
                  <div class="timeline-marker bg-danger"></div>
                  <div class="timeline-content">
                    <div class="timeline-time">
                      <?= date('d M Y, H:i', strtotime($pembayaran['verified_at'])) ?>
                    </div>
                    <div class="timeline-title">Pembayaran ditolak</div>
                    <?php if ($verified_by): ?>
                      <div class="timeline-description">oleh <?= esc($verified_by['nama_lengkap']) ?></div>
                    <?php endif; ?>
                  </div>
                </div>
                <?php else: ?>
                <div class="timeline-item">
                  <div class="timeline-marker bg-warning"></div>
                  <div class="timeline-content">
                    <div class="timeline-time">Menunggu</div>
                    <div class="timeline-title">Menunggu verifikasi admin</div>
                    <div class="timeline-description">Pembayaran sedang dalam proses review</div>
                  </div>
                </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

        <!-- Action Panel -->
        <div class="col-lg-4">
          <?php if ($status === 'pending'): ?>
          <div class="action-card">
            <div class="action-header">
              <h5 class="action-title">
                <i class="bi bi-sliders me-2"></i>Aksi Verifikasi
              </h5>
            </div>
            <div class="action-body">
              <div class="action-buttons">
                <button class="btn btn-success btn-lg w-100 mb-3" 
                        data-open-verif 
                        data-id="<?= (int)($pembayaran['id_pembayaran'] ?? 0) ?>" 
                        data-status="verified">
                  <i class="bi bi-check2 me-2"></i>Verifikasi Pembayaran
                </button>
                
                <button class="btn btn-danger btn-lg w-100 mb-4" 
                        data-open-verif 
                        data-id="<?= (int)($pembayaran['id_pembayaran'] ?? 0) ?>" 
                        data-status="rejected">
                  <i class="bi bi-x me-2"></i>Tolak Pembayaran
                </button>

                <div class="divider"></div>

                <div class="secondary-actions">
                  <button class="btn btn-outline-info w-100 mb-2" id="btnNotify">
                    <i class="bi bi-envelope me-2"></i>Kirim Notifikasi
                  </button>
                  <button class="btn btn-outline-secondary w-100" id="btnAddNote">
                    <i class="bi bi-sticky me-2"></i>Tambah Catatan
                  </button>
                </div>
              </div>
            </div>
          </div>
          <?php endif; ?>

          <!-- Summary Card -->
          <div class="summary-card">
            <div class="summary-header">
              <h5 class="summary-title">
                <i class="bi bi-info-circle me-2"></i>Ringkasan
              </h5>
            </div>
            <div class="summary-body">
              <div class="summary-item">
                <span class="summary-label">Status</span>
                <span class="payment-status <?= $statusClass ?>"><?= $statusText ?></span>
              </div>
              
              <?php if ($pricingTier): ?>
              <div class="summary-item">
                <span class="summary-label">Tipe Harga</span>
                <span class="pricing-tier-badge-small <?= $pricingTierDisplay['class'] ?>">
                  <?= $pricingTierDisplay['label'] ?>
                </span>
              </div>
              <?php endif; ?>
              
              <div class="summary-item">
                <span class="summary-label">Total Bayar</span>
                <span class="summary-value">Rp <?= number_format($amount, 0, ',', '.') ?></span>
              </div>
              <div class="summary-item">
                <span class="summary-label">Metode</span>
                <span class="summary-value"><?= esc($pembayaran['metode'] ?? '-') ?></span>
              </div>
              <?php if ($verified_by): ?>
              <div class="summary-item">
                <span class="summary-label">Diverifikasi oleh</span>
                <span class="summary-value"><?= esc($verified_by['nama_lengkap']) ?></span>
              </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Additional Information -->
        <div class="col-12">
          <div class="additional-info-card">
            <div class="additional-info-header">
              <h5 class="additional-info-title">
                <i class="bi bi-gear me-2"></i>Informasi Sistem
              </h5>
            </div>
            <div class="additional-info-body">
              <div class="info-columns">
                <div class="info-column">
                  <div class="info-row">
                    <span class="info-label">Event ID</span>
                    <span class="info-value"><?= esc($pembayaran['event_id'] ?? '-') ?></span>
                  </div>
                  <div class="info-row">
                    <span class="info-label">Created At</span>
                    <span class="info-value">
                      <?= !empty($pembayaran['tanggal_bayar']) ? date('d/m/Y H:i:s', strtotime($pembayaran['tanggal_bayar'])) : 'N/A' ?>
                    </span>
                  </div>
                  <?php if (!empty($pembayaran['payment_reference'])): ?>
                  <div class="info-row">
                    <span class="info-label">Referensi</span>
                    <span class="info-value"><?= esc($pembayaran['payment_reference']) ?></span>
                  </div>
                  <?php endif; ?>
                </div>

                <div class="info-column">
                  <?php if ($pricingTier): ?>
                  <div class="info-row">
                    <span class="info-label">Pricing Tier</span>
                    <span class="info-value"><?= esc($pricingTier) ?></span>
                  </div>
                  <?php endif; ?>
                  
                  <?php if (!empty($pembayaran['id_voucher'])): ?>
                  <div class="info-row">
                    <span class="info-label">Voucher ID</span>
                    <span class="info-value"><?= esc($pembayaran['id_voucher']) ?></span>
                  </div>
                  <?php endif; ?>
                  
                  <?php if (!empty($pembayaran['auto_verified'])): ?>
                  <div class="info-row">
                    <span class="info-label">Auto Verified</span>
                    <span class="info-value">
                      <span class="badge bg-info">Ya</span>
                    </span>
                  </div>
                  <?php endif; ?>
                  
                  <div class="info-row">
                    <span class="info-label">Last Updated</span>
                    <span class="info-value">
                      <?= !empty($pembayaran['verified_at']) ? date('d/m/Y H:i', strtotime($pembayaran['verified_at'])) : 'N/A' ?>
                    </span>
                  </div>
                </div>
              </div>

              <!-- Notes Section -->
              <?php if (!empty($pembayaran['keterangan'])): ?>
              <div class="notes-section">
                <label class="notes-label">Keterangan:</label>
                <div class="notes-content"><?= nl2br(esc($pembayaran['keterangan'])) ?></div>
              </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

      </div>
    </div>
  </main>
</div>

<!-- Verification Modal -->
<div class="modal fade" id="verifikasiModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="verifikasiTitle">
          <i class="bi bi-check2-circle me-2"></i>Verifikasi Pembayaran
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      
      <form id="verifikasiForm" method="POST" action="#">
        <?= csrf_field() ?>
        <div class="modal-body">
          <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            Pastikan informasi pembayaran sudah sesuai sebelum melakukan verifikasi.
          </div>
          
          <div class="form-group">
            <label class="form-label">Keterangan</label>
            <textarea class="form-control" name="keterangan" rows="3" 
                      placeholder="Tambahkan keterangan (opsional)..."></textarea>
          </div>
          
          <input type="hidden" name="status" id="verifikasiStatus">
        </div>
        
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-primary" id="verifikasiSubmit">
            <i class="bi bi-save me-2"></i>Proses
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<?= $this->include('partials/footer') ?>

<script>
(function() {
  'use strict';

  const $ = (selector, context = document) => context.querySelector(selector);
  const $$ = (selector, context = document) => Array.from(context.querySelectorAll(selector));

  const verifikasiModal = new bootstrap.Modal($('#verifikasiModal'));

  $('[data-open-verif]').forEach(button => {
    button.addEventListener('click', () => {
      const paymentId = button.getAttribute('data-id');
      const status = button.getAttribute('data-status');
      const isVerification = status === 'verified';

      const title = $('#verifikasiTitle');
      if (title) {
        title.innerHTML = isVerification 
          ? '<i class="bi bi-check2-circle me-2"></i>Verifikasi Pembayaran'
          : '<i class="bi bi-x-circle me-2"></i>Tolak Pembayaran';
      }

      const statusField = $('#verifikasiStatus');
      if (statusField) statusField.value = status;

      const form = $('#verifikasiForm');
      if (form) form.action = `<?= site_url('admin/pembayaran/verifikasi') ?>/${paymentId}`;

      const submitButton = $('#verifikasiSubmit');
      if (submitButton) {
        submitButton.className = `btn btn-${isVerification ? 'success' : 'danger'}`;
        submitButton.innerHTML = `<i class="bi bi-save me-2"></i>${isVerification ? 'Verifikasi' : 'Tolak'}`;
      }

      verifikasiModal.show();
    });
  });

  const notifyButton = $('#btnNotify');
  if (notifyButton) {
    notifyButton.addEventListener('click', () => {
      Swal.fire({
        title: 'Kirim Notifikasi',
        text: 'Kirim email/pesan ke user terkait status pembayaran?',
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Kirim',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#6b7280'
      }).then(result => {
        if (result.isConfirmed) {
          Swal.fire({
            icon: 'success',
            title: 'Berhasil',
            text: 'Notifikasi berhasil dikirim',
            timer: 1800,
            showConfirmButton: false
          });
        }
      });
    });
  }

  const addNoteButton = $('#btnAddNote');
  if (addNoteButton) {
    addNoteButton.addEventListener('click', async () => {
      const { value: note } = await Swal.fire({
        title: 'Tambah Catatan',
        input: 'textarea',
        inputPlaceholder: 'Tulis catatan untuk pembayaran ini...',
        showCancelButton: true,
        confirmButtonText: 'Simpan',
        cancelButtonText: 'Batal',
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#6b7280',
        inputValidator: (value) => {
          if (!value) return 'Catatan tidak boleh kosong';
        }
      });

      if (note) {
        Swal.fire({
          icon: 'success',
          title: 'Catatan Tersimpan',
          text: 'Catatan berhasil ditambahkan',
          timer: 1500,
          showConfirmButton: false
        });
      }
    });
  }

  const verifikasiForm = $('#verifikasiForm');
  if (verifikasiForm) {
    verifikasiForm.addEventListener('submit', (e) => {
      const submitButton = $('#verifikasiSubmit');
      if (submitButton) {
        const originalContent = submitButton.innerHTML;
        submitButton.innerHTML = '<i class="bi bi-arrow-repeat me-2"></i>Memproses...';
        submitButton.disabled = true;

        setTimeout(() => {
          submitButton.innerHTML = originalContent;
          submitButton.disabled = false;
        }, 3000);
      }
    });
  }

})();
</script>