<?php
// ===== Variable Initialization =====
$title               = $title               ?? 'Verifikasi Pembayaran';
$pembayarans         = $pembayarans         ?? [];
$pembayaran_pending  = (int)($pembayaran_pending  ?? 0);
$pembayaran_verified = (int)($pembayaran_verified ?? 0);
$pembayaran_rejected = (int)($pembayaran_rejected ?? 0);
$total_revenue       = (int)($total_revenue ?? 0);

// ===== HELPER FUNCTION: Get Payment Method Display =====
function getPaymentMethodDisplay($payment) {
    $metode = strtolower($payment['metode'] ?? '');
    $paymentType = strtolower($payment['midtrans_payment_type'] ?? '');
    
    // Jika metode Midtrans, tampilkan detail payment type
    if ($metode === 'midtrans' && !empty($paymentType)) {
        // Mapping payment type Midtrans ke display yang user-friendly
        $paymentMethodMap = [
            // Bank Transfer
            'bank_transfer' => ['icon' => 'bank', 'label' => 'Bank Transfer', 'color' => '#1e40af'],
            'bca_va' => ['icon' => 'building', 'label' => 'BCA Virtual Account', 'color' => '#003087'],
            'bni_va' => ['icon' => 'building', 'label' => 'BNI Virtual Account', 'color' => '#ed7203'],
            'bri_va' => ['icon' => 'building', 'label' => 'BRI Virtual Account', 'color' => '#003d7a'],
            'mandiri_va' => ['icon' => 'building', 'label' => 'Mandiri Virtual Account', 'color' => '#003d79'],
            'permata_va' => ['icon' => 'building', 'label' => 'Permata Virtual Account', 'color' => '#00a84f'],
            
            // E-Wallet
            'gopay' => ['icon' => 'wallet2', 'label' => 'GoPay', 'color' => '#00aa13'],
            'shopeepay' => ['icon' => 'wallet2', 'label' => 'ShopeePay', 'color' => '#ee4d2d'],
            'qris' => ['icon' => 'qr-code', 'label' => 'QRIS', 'color' => '#d32f2f'],
            'dana' => ['icon' => 'wallet2', 'label' => 'DANA', 'color' => '#118eea'],
            'linkaja' => ['icon' => 'wallet2', 'label' => 'LinkAja', 'color' => '#e31e24'],
            'ovo' => ['icon' => 'wallet2', 'label' => 'OVO', 'color' => '#4b2d83'],
            
            // Credit Card
            'credit_card' => ['icon' => 'credit-card', 'label' => 'Kartu Kredit', 'color' => '#6b7280'],
            'debit_card' => ['icon' => 'credit-card-2-front', 'label' => 'Kartu Debit', 'color' => '#6b7280'],
            
            // Convenience Store
            'cstore' => ['icon' => 'shop', 'label' => 'Indomaret/Alfamart', 'color' => '#0ea5e9'],
            'indomaret' => ['icon' => 'shop', 'label' => 'Indomaret', 'color' => '#d91e27'],
            'alfamart' => ['icon' => 'shop', 'label' => 'Alfamart', 'color' => '#ed1c24'],
            
            // Other
            'akulaku' => ['icon' => 'credit-card', 'label' => 'Akulaku', 'color' => '#00a3ff'],
        ];
        
        // Coba match dengan payment type
        $displayInfo = $paymentMethodMap[$paymentType] ?? null;
        
        // Jika tidak ada match exact, coba cari partial match
        if (!$displayInfo) {
            foreach ($paymentMethodMap as $key => $info) {
                if (strpos($paymentType, $key) !== false) {
                    $displayInfo = $info;
                    break;
                }
            }
        }
        
        // Fallback jika masih tidak ketemu
        if (!$displayInfo) {
            $displayInfo = [
                'icon' => 'credit-card', 
                'label' => ucwords(str_replace('_', ' ', $paymentType)),
                'color' => '#6b7280'
            ];
        }
        
        return [
            'icon' => $displayInfo['icon'],
            'label' => $displayInfo['label'],
            'color' => $displayInfo['color'],
            'badge' => 'Midtrans'
        ];
    }
    
    // Metode pembayaran manual/lainnya
    $manualMethodMap = [
        'transfer_bank' => ['icon' => 'bank', 'label' => 'Transfer Bank Manual', 'color' => '#059669'],
        'cash' => ['icon' => 'cash-coin', 'label' => 'Tunai', 'color' => '#10b981'],
        'other' => ['icon' => 'credit-card', 'label' => 'Lainnya', 'color' => '#6b7280'],
    ];
    
    $displayInfo = $manualMethodMap[$metode] ?? [
        'icon' => 'credit-card',
        'label' => ucfirst($metode),
        'color' => '#6b7280'
    ];
    
    return [
        'icon' => $displayInfo['icon'],
        'label' => $displayInfo['label'],
        'color' => $displayInfo['color'],
        'badge' => null
    ];
}

// ===== HELPER FUNCTION: Get Pricing Tier Display =====
function getPricingTierDisplay($pricingTier) {
    return match($pricingTier) {
        'early_bird' => ['label' => 'Early Bird', 'class' => 'tier-early', 'icon' => 'lightning-charge-fill'],
        'wave_1'     => ['label' => 'Gel 1', 'class' => 'tier-wave1', 'icon' => 'tag-fill'],
        'regular'    => ['label' => 'Gel 2', 'class' => 'tier-regular', 'icon' => 'tag-fill'],
        'on_site'    => ['label' => 'Gel 3', 'class' => 'tier-onsite', 'icon' => 'geo-alt-fill'],
        default      => null
    };
}
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_admin') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top: 70px;">
    <div class="container-fluid px-3 px-md-4 py-4">

      <!-- Header Section -->
      <div class="header-section d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 class="header-title mb-2">
            <i class="bi bi-credit-card me-3"></i><?= esc($title) ?>
          </h2>
          <p class="header-subtitle mb-0">Kelola dan verifikasi pembayaran dari peserta</p>
        </div>
        <div class="text-end">
          <small class="text-light d-block opacity-75">Terakhir update</small>
          <strong class="text-white"><?= date('d M Y, H:i') ?></strong>
        </div>
      </div>

      <!-- Statistics Cards -->
      <section aria-label="Ringkasan Pembayaran" class="mb-5">
        <div class="row g-4">
          <div class="col-6 col-lg-3">
            <div class="stat-card pending">
              <div class="stat-content">
                <div class="stat-icon bg-warning">
                  <i class="bi bi-clock"></i>
                </div>
                <div class="stat-info">
                  <div class="stat-number"><?= number_format($pembayaran_pending) ?></div>
                  <div class="stat-label">Pending</div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-6 col-lg-3">
            <div class="stat-card verified">
              <div class="stat-content">
                <div class="stat-icon bg-success">
                  <i class="bi bi-check2-circle"></i>
                </div>
                <div class="stat-info">
                  <div class="stat-number"><?= number_format($pembayaran_verified) ?></div>
                  <div class="stat-label">Terverifikasi</div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-6 col-lg-3">
            <div class="stat-card rejected">
              <div class="stat-content">
                <div class="stat-icon bg-danger">
                  <i class="bi bi-x-circle"></i>
                </div>
                <div class="stat-info">
                  <div class="stat-number"><?= number_format($pembayaran_rejected) ?></div>
                  <div class="stat-label">Ditolak</div>
                </div>
              </div>
            </div>
          </div>

          <div class="col-6 col-lg-3">
            <div class="stat-card revenue">
              <div class="stat-content">
                <div class="stat-icon bg-info">
                  <i class="bi bi-cash-coin"></i>
                </div>
                <div class="stat-info">
                  <div class="stat-number small-text">Rp <?= number_format($total_revenue, 0, ',', '.') ?></div>
                  <div class="stat-label">Total Revenue</div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Filter Section -->
      <section class="filter-section mb-4" aria-label="Filter Pembayaran">
        <div class="filter-card">
          <div class="filter-header">
            <h5 class="filter-title">
              <i class="bi bi-funnel me-2"></i>Filter & Pencarian
            </h5>
          </div>
          <div class="filter-body">
            <div class="row g-3 align-items-end">
              <div class="col-md-3">
                <label class="form-label">Pencarian</label>
                <div class="search-input-wrapper">
                  <input type="text" class="form-control search-input" id="searchInput" 
                         placeholder="Cari nama, email, atau metode...">
                  <i class="bi bi-search search-icon"></i>
                </div>
              </div>

              <div class="col-md-2">
                <label class="form-label">Status</label>
                <select class="form-select" id="statusFilter" aria-label="Filter status">
                  <option value="">Semua Status</option>
                  <option value="pending">Pending</option>
                  <option value="verified">Terverifikasi</option>
                  <option value="rejected">Ditolak</option>
                </select>
              </div>

              <div class="col-md-2">
                <label class="form-label">Role</label>
                <select class="form-select" id="roleFilter" aria-label="Filter role">
                  <option value="">Semua Role</option>
                  <option value="presenter">Presenter</option>
                  <option value="audience">Audience</option>
                </select>
              </div>

              <!-- ===== PRICING TIER FILTER ===== -->
              <div class="col-md-2">
                <label class="form-label">Pricing Tier</label>
                <select class="form-select" id="pricingFilter" aria-label="Filter pricing tier">
                  <option value="">Semua Tier</option>
                  <option value="early_bird">Early Bird (Offline)</option>
                  <option value="wave_1">Gelombang 1 (Online)</option>
                  <option value="regular">Gelombang 2</option>
                  <option value="on_site">Gelombang 3</option>
                </select>
              </div>

              <div class="col-md-2">
                <label class="form-label">Partisipasi</label>
                <select class="form-select" id="participationFilter" aria-label="Filter partisipasi">
                  <option value="">Semua</option>
                  <option value="online">Online</option>
                  <option value="offline">Offline</option>
                </select>
              </div>

              <div class="col-md-1">
                <button class="btn btn-outline-secondary w-100" id="btnResetFilter">
                  <i class="bi bi-arrow-counterclockwise"></i>
                </button>
              </div>
            </div>

            <div class="filter-results mt-3">
              <small id="resultCounter" class="result-counter"></small>
            </div>
          </div>
        </div>
      </section>

      <!-- Payment Cards Grid -->
      <section aria-label="Daftar Pembayaran">
        <div class="section-header mb-4">
          <h4 class="section-title">Daftar Pembayaran</h4>
        </div>

        <div class="row g-4" id="paymentContainer">
          <?php if (!empty($pembayarans)): ?>
            <?php foreach ($pembayarans as $p): 
              $status = $p['status'] ?? 'pending';
              $role   = $p['role'] ?? '';
              $part   = $p['participation_type'] ?? '';
              $name   = $p['nama_lengkap'] ?? '-';
              $email  = $p['email'] ?? '-';
              $amount = (int)($p['jumlah'] ?? 0);
              $evt    = $p['event_title'] ?? null;

              // ===== GET PRICING TIER INFO =====
              $pricingTier = $p['pricing_tier'] ?? null;
              $originalAmount = (int)($p['original_amount'] ?? $amount);
              $tierDisplay = getPricingTierDisplay($pricingTier);

              // ===== GET PAYMENT METHOD DISPLAY INFO =====
              $paymentDisplay = getPaymentMethodDisplay($p);

              $statusClass = match($status){
                'pending'  => 'status-pending',
                'verified' => 'status-verified',
                'rejected' => 'status-rejected',
                default    => 'status-default'
              };

              $statusText = match($status){
                'pending'  => 'Pending',
                'verified' => 'Terverifikasi',
                'rejected' => 'Ditolak',
                default    => ucfirst($status)
              };

              $searchStr = strtolower(($name.' '.$email.' '.$paymentDisplay['label']));
            ?>
              <div class="col-lg-6 col-xl-4"
                   data-status="<?= esc($status) ?>"
                   data-role="<?= esc($role) ?>"
                   data-participation="<?= esc($part) ?>"
                   data-pricing="<?= esc($pricingTier ?? '') ?>"
                   data-search="<?= esc($searchStr) ?>">
                
                <article class="payment-card" aria-label="Kartu pembayaran">
                  
                  <!-- Payment Header -->
                  <div class="payment-header">
                    <div class="user-info">
                      <div class="user-avatar">
                        <?= strtoupper(substr($name, 0, 1)) ?>
                      </div>
                      <div class="user-details">
                        <div class="user-name"><?= esc($name) ?></div>
                        <div class="user-email"><?= esc($email) ?></div>
                        <?php if (!empty($evt)): ?>
                          <div class="event-badge-wrapper">
                            <span class="event-badge"><?= esc($evt) ?></span>
                          </div>
                        <?php endif; ?>
                      </div>
                    </div>
                    <div class="status-badge <?= $statusClass ?>">
                      <?= $statusText ?>
                    </div>
                  </div>

                  <!-- Payment Body -->
                  <div class="payment-body">
                    
                    <!-- ===== NEW: PRICING TIER BADGE (if exists) ===== -->
                    <?php if ($tierDisplay): ?>
                      <div class="pricing-tier-mini">
                        <span class="tier-badge <?= $tierDisplay['class'] ?>">
                          <i class="bi bi-<?= $tierDisplay['icon'] ?> me-1"></i>
                          <?= $tierDisplay['label'] ?>
                        </span>
                        <?php if ($pricingTier === 'early_bird' && $originalAmount > $amount): ?>
                          <span class="savings-badge">
                            <i class="bi bi-piggy-bank me-1"></i>
                            Hemat Rp <?= number_format($originalAmount - $amount, 0, ',', '.') ?>
                          </span>
                        <?php endif; ?>
                      </div>
                    <?php endif; ?>
                    
                    <!-- Payment Details -->
                    <div class="payment-details">
                      <!-- Payment Method Display -->
                      <div class="detail-row mb-3">
                        <div class="detail-item">
                          <label class="detail-label">Metode Pembayaran</label>
                          <div class="payment-method-display">
                            <div class="payment-method-icon" style="background-color: <?= $paymentDisplay['color'] ?>;">
                              <i class="bi bi-<?= $paymentDisplay['icon'] ?>"></i>
                            </div>
                            <div class="payment-method-info">
                              <div class="payment-method-label"><?= esc($paymentDisplay['label']) ?></div>
                              <?php if ($paymentDisplay['badge']): ?>
                                <span class="payment-method-badge"><?= $paymentDisplay['badge'] ?></span>
                              <?php endif; ?>
                            </div>
                          </div>
                        </div>
                      </div>

                      <div class="detail-row">
                        <div class="detail-item">
                          <label class="detail-label">Jumlah Bayar</label>
                          <div class="detail-value amount">
                            Rp <?= number_format($amount, 0, ',', '.') ?>
                            <?php if ($originalAmount > $amount): ?>
                              <del class="original-price">Rp <?= number_format($originalAmount, 0, ',', '.') ?></del>
                            <?php endif; ?>
                          </div>
                        </div>
                        <div class="detail-item">
                          <label class="detail-label">Tanggal Bayar</label>
                          <div class="detail-value">
                            <?= !empty($p['tanggal_bayar']) ? date('d/m/Y H:i', strtotime($p['tanggal_bayar'])) : '-' ?>
                          </div>
                        </div>
                      </div>

                      <div class="detail-row">
                        <div class="detail-item">
                          <label class="detail-label">Role & Partisipasi</label>
                          <div class="badge-group">
                            <span class="role-badge <?= $role === 'presenter' ? 'role-presenter' : 'role-audience' ?>">
                              <?= ucfirst($role ?: '-') ?>
                            </span>
                            <?php if (!empty($part)): ?>
                              <span class="participation-badge"><?= ucfirst($part) ?></span>
                            <?php endif; ?>
                          </div>
                        </div>
                      </div>
                    </div>

                    <!-- Voucher Info -->
                    <?php if (!empty($p['voucher_info'])):
                      $v = $p['voucher_info'];
                      $pot = ($v['tipe'] ?? '') === 'percentage'
                          ? ($v['nilai'] ?? 0).'%'
                          : 'Rp '.number_format((int)($v['nilai'] ?? 0), 0, ',', '.');
                    ?>
                      <div class="voucher-info">
                        <div class="voucher-label">Voucher digunakan:</div>
                        <div class="voucher-details">
                          <?= esc($v['kode_voucher'] ?? '-') ?> (<?= $pot ?>)
                        </div>
                      </div>
                    <?php endif; ?>

                    <!-- Midtrans Transaction ID -->
                    <?php if (!empty($p['midtrans_transaction_id'])): ?>
                      <div class="transaction-id-info">
                        <small class="transaction-id-label">Transaction ID:</small>
                        <small class="transaction-id-value"><?= esc($p['midtrans_transaction_id']) ?></small>
                      </div>
                    <?php endif; ?>

                    <!-- Verification Info -->
                    <?php if (!empty($p['verified_at'])): ?>
                      <div class="verification-info">
                        <small class="verification-text">
                          Diverifikasi: <?= date('d/m/Y H:i', strtotime($p['verified_at'])) ?>
                          <?php if (!empty($p['auto_verified']) && $p['auto_verified']): ?>
                            <span class="auto-verified-badge">AUTO</span>
                          <?php endif; ?>
                        </small>
                      </div>
                    <?php endif; ?>
                  </div>

                  <!-- Payment Footer -->
                  <div class="payment-footer">
                    <div class="action-buttons">
                      
                      <!-- Primary Actions -->
                      <div class="primary-actions">
                        <a class="btn btn-outline-info btn-sm" 
                           href="<?= site_url('admin/pembayaran/detail/'.(int)$p['id_pembayaran']) ?>">
                          <i class="bi bi-eye me-1"></i>Detail
                        </a>
                        <?php if (!empty($p['bukti_bayar'])): ?>
                          <button class="btn btn-outline-secondary btn-sm btn-view-bukti"
                                  data-bukti-url="<?= site_url('admin/pembayaran/view-bukti/'.(int)$p['id_pembayaran']) ?>">
                            <i class="bi bi-image me-1"></i>Bukti
                          </button>
                        <?php endif; ?>
                      </div>

                      <!-- Verification Actions -->
                      <?php if ($status === 'pending'): ?>
                        <div class="verification-actions">
                          <button class="btn btn-success btn-sm btn-open-verif"
                                  data-id="<?= (int)$p['id_pembayaran'] ?>"
                                  data-status="verified">
                            <i class="bi bi-check2 me-1"></i>Verifikasi
                          </button>
                          <button class="btn btn-danger btn-sm btn-open-verif"
                                  data-id="<?= (int)$p['id_pembayaran'] ?>"
                                  data-status="rejected">
                            <i class="bi bi-x me-1"></i>Tolak
                          </button>
                        </div>
                      <?php endif; ?>
                    </div>
                  </div>
                </article>
              </div>
            <?php endforeach; ?>
          <?php else: ?>
            <div class="col-12">
              <div class="empty-state">
                <div class="empty-icon">
                  <i class="bi bi-credit-card"></i>
                </div>
                <h5 class="empty-title">Belum Ada Pembayaran</h5>
                <p class="empty-text">Belum ada pembayaran yang perlu diverifikasi saat ini.</p>
              </div>
            </div>
          <?php endif; ?>
        </div>

        <!-- Load More Button -->
        <?php if (!empty($pembayarans) && count($pembayarans) >= 50): ?>
          <div class="load-more-section">
            <button class="btn btn-outline-primary btn-lg" id="btnLoadMore">
              <i class="bi bi-plus-lg me-2"></i>Tampilkan Lebih Banyak
            </button>
          </div>
        <?php endif; ?>
      </section>

    </div>
  </main>
</div>

<!-- Bukti Modal -->
<div class="modal fade" id="buktiModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">
          <i class="bi bi-image me-2"></i>Bukti Pembayaran
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body text-center">
        <img id="buktiImage" src="" class="img-fluid rounded shadow-sm" alt="Bukti Pembayaran">
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>

<!-- Verifikasi Modal -->
<div class="modal fade" id="verifikasiModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="verifikasiTitle">
          <i class="bi bi-check2-circle me-2"></i>Verifikasi Pembayaran
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      
      <form id="verifikasiForm" method="POST">
        <?= csrf_field() ?>
        <div class="modal-body">
          <div class="alert alert-info">
            <i class="bi bi-info-circle me-2"></i>
            Pastikan bukti pembayaran sudah sesuai sebelum melakukan verifikasi.
          </div>
          
          <div class="form-group">
            <label class="form-label">Keterangan Verifikasi</label>
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

<style>
/* ========== Previous CSS (unchanged) ========== */
:root {
  --primary-color: #2563eb;
  --success-color: #10b981;
  --warning-color: #f59e0b;
  --danger-color: #ef4444;
  --info-color: #06b6d4;
  --secondary-color: #6b7280;
  
  --light-bg: #f8fafc;
  --border-color: #e2e8f0;
  --text-primary: #1e293b;
  --text-secondary: #64748b;
  
  --border-radius: 12px;
  --shadow-sm: 0 2px 8px rgba(0, 0, 0, 0.08);
  --shadow-md: 0 4px 16px rgba(0, 0, 0, 0.12);
  --shadow-lg: 0 8px 32px rgba(0, 0, 0, 0.16);
}

body {
  background: linear-gradient(135deg, var(--light-bg) 0%, #e2e8f0 100%);
  font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
  color: var(--text-primary);
}

#content main > .container-fluid {
  margin-top: 0;
}

/* ========== NEW: Pricing Tier Mini Badge ========== */
.pricing-tier-mini {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  margin-bottom: 1rem;
  padding: 0.75rem;
  background: linear-gradient(145deg, #f8fafc 0%, #f1f5f9 100%);
  border-radius: 8px;
  border: 1px solid var(--border-color);
}

.tier-badge {
  display: inline-flex;
  align-items: center;
  padding: 0.375rem 0.75rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.025em;
  flex-shrink: 0;
}

.tier-early {
  background: linear-gradient(135deg, #fbbf24 0%, #f59e0b 100%);
  color: #78350f;
  box-shadow: 0 2px 8px rgba(251, 191, 36, 0.3);
}

.tier-wave1 {
  background: linear-gradient(135deg, #a78bfa 0%, #8b5cf6 100%);
  color: #4c1d95;
  box-shadow: 0 2px 8px rgba(167, 139, 250, 0.3);
}

.tier-regular {
  background: linear-gradient(135deg, #60a5fa 0%, #3b82f6 100%);
  color: #1e3a8a;
  box-shadow: 0 2px 8px rgba(96, 165, 250, 0.3);
}

.tier-onsite {
  background: linear-gradient(135deg, #f472b6 0%, #ec4899 100%);
  color: #831843;
  box-shadow: 0 2px 8px rgba(244, 114, 182, 0.3);
}

.savings-badge {
  display: inline-flex;
  align-items: center;
  background: rgba(16, 185, 129, 0.1);
  color: var(--success-color);
  border: 1px solid rgba(16, 185, 129, 0.2);
  padding: 0.25rem 0.5rem;
  border-radius: 12px;
  font-size: 0.7rem;
  font-weight: 600;
  flex: 1;
  min-width: 0;
}

.original-price {
  display: block;
  font-size: 0.75rem;
  color: var(--text-secondary);
  margin-top: 0.25rem;
}

/* ========== Payment Method Display Styles ========== */
.payment-method-display {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  padding: 0.75rem;
  background: #f8fafc;
  border: 1px solid var(--border-color);
  border-radius: 8px;
}

.payment-method-icon {
  width: 2.5rem;
  height: 2.5rem;
  border-radius: 6px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1rem;
  flex-shrink: 0;
  box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.payment-method-info {
  flex: 1;
  min-width: 0;
}

.payment-method-label {
  font-weight: 600;
  color: var(--text-primary);
  font-size: 0.875rem;
  margin-bottom: 0.125rem;
}

.payment-method-badge {
  display: inline-block;
  background: rgba(37, 99, 235, 0.1);
  color: var(--primary-color);
  border: 1px solid rgba(37, 99, 235, 0.2);
  padding: 0.125rem 0.375rem;
  border-radius: 4px;
  font-size: 0.65rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.025em;
}

.transaction-id-info {
  margin-top: 0.75rem;
  padding-top: 0.75rem;
  border-top: 1px solid var(--border-color);
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.5rem;
}

.transaction-id-label {
  color: var(--text-secondary);
  font-size: 0.75rem;
  font-weight: 500;
}

.transaction-id-value {
  color: var(--text-primary);
  font-size: 0.7rem;
  font-family: 'Courier New', monospace;
  background: #f8fafc;
  padding: 0.25rem 0.5rem;
  border-radius: 4px;
  word-break: break-all;
}

.auto-verified-badge {
  display: inline-block;
  background: rgba(16, 185, 129, 0.1);
  color: var(--success-color);
  border: 1px solid rgba(16, 185, 129, 0.2);
  padding: 0.125rem 0.375rem;
  border-radius: 4px;
  font-size: 0.65rem;
  font-weight: 700;
  margin-left: 0.25rem;
}

/* ========== Previous Styles (unchanged) ========== */
.header-section {
  background: linear-gradient(135deg, var(--primary-color) 0%, #1e40af 100%);
  color: white;
  padding: 2rem 2rem 2.5rem;
  border-radius: var(--border-radius);
  box-shadow: var(--shadow-lg);
  position: relative;
  overflow: hidden;
}

.header-section::before {
  content: '';
  position: absolute;
  top: 0;
  right: 0;
  width: 200px;
  height: 200px;
  background: radial-gradient(circle, rgba(255,255,255,0.1) 0%, transparent 70%);
  transform: translate(50%, -50%);
}

.header-title {
  font-weight: 800;
  font-size: 2.25rem;
  margin-bottom: 0.5rem;
  position: relative;
}

.header-subtitle {
  color: rgba(255, 255, 255, 0.9);
  font-size: 1.1rem;
  font-weight: 400;
}

.stat-card {
  background: white;
  border-radius: var(--border-radius);
  padding: 1.5rem;
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--border-color);
  transition: all 0.3s ease;
  height: 100%;
}

.stat-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

.stat-content {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.stat-icon {
  width: 3.5rem;
  height: 3.5rem;
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: white;
  font-size: 1.5rem;
  flex-shrink: 0;
}

.stat-info {
  flex: 1;
  min-width: 0;
}

.stat-number {
  font-size: 2rem;
  font-weight: 800;
  color: var(--text-primary);
  line-height: 1;
  margin-bottom: 0.25rem;
}

.stat-number.small-text {
  font-size: 1.5rem;
}

.stat-label {
  color: var(--text-secondary);
  font-size: 0.875rem;
  font-weight: 500;
}

.filter-section {
  margin-bottom: 2rem;
}

.filter-card {
  background: white;
  border-radius: var(--border-radius);
  box-shadow: var(--shadow-sm);
  border: 1px solid var(--border-color);
  overflow: hidden;
}

.filter-header {
  background: linear-gradient(145deg, #f8fafc 0%, #f1f5f9 100%);
  padding: 1rem 1.5rem;
  border-bottom: 1px solid var(--border-color);
}

.filter-title {
  color: var(--text-primary);
  font-weight: 700;
  font-size: 1rem;
  margin: 0;
}

.filter-body {
  padding: 1.5rem;
}

.search-input-wrapper {
  position: relative;
}

.search-input {
  padding-left: 2.5rem;
}

.search-icon {
  position: absolute;
  left: 0.75rem;
  top: 50%;
  transform: translateY(-50%);
  color: var(--text-secondary);
  pointer-events: none;
}

.filter-results {
  padding-top: 1rem;
  border-top: 1px solid var(--border-color);
}

.result-counter {
  color: var(--text-secondary);
  font-weight: 500;
}

.section-title {
  color: var(--text-primary);
  font-weight: 700;
  font-size: 1.5rem;
  margin-bottom: 0;
}

.payment-card {
  background: white;
  border-radius: var(--border-radius);
  border: 1px solid var(--border-color);
  overflow: hidden;
  transition: all 0.3s ease;
  height: 100%;
  display: flex;
  flex-direction: column;
  box-shadow: var(--shadow-sm);
}

.payment-card:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-md);
}

.payment-header {
  padding: 1.5rem;
  border-bottom: 1px solid var(--border-color);
  background: linear-gradient(145deg, #f8fafc 0%, #f1f5f9 100%);
  display: flex;
  justify-content: space-between;
  align-items: flex-start;
}

.user-info {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.user-avatar {
  width: 3rem;
  height: 3rem;
  border-radius: 50%;
  background: linear-gradient(135deg, var(--primary-color), var(--info-color));
  display: flex;
  align-items: center;
  justify-content: center;
  font-weight: 700;
  color: white;
  font-size: 1.2rem;
  flex-shrink: 0;
}

.user-details {
  flex: 1;
  min-width: 0;
}

.user-name {
  font-weight: 700;
  color: var(--text-primary);
  font-size: 1rem;
  margin-bottom: 0.25rem;
}

.user-email {
  color: var(--text-secondary);
  font-size: 0.875rem;
  margin-bottom: 0.5rem;
}

.event-badge-wrapper {
  margin-top: 0.5rem;
}

.event-badge {
  background: rgba(6, 182, 212, 0.1);
  color: #0369a1;
  border: 1px solid rgba(6, 182, 212, 0.2);
  padding: 0.25rem 0.5rem;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 500;
}

.status-badge {
  padding: 0.5rem 1rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 700;
  text-transform: uppercase;
  letter-spacing: 0.025em;
  flex-shrink: 0;
}

.status-pending {
  background: #fef3c7;
  color: #92400e;
}

.status-verified {
  background: #d1fae5;
  color: #065f46;
}

.status-rejected {
  background: #fee2e2;
  color: #991b1b;
}

.status-default {
  background: #f3f4f6;
  color: #374151;
}

.payment-body {
  padding: 1.5rem;
  flex: 1;
}

.payment-details {
  margin-bottom: 1rem;
}

.detail-row {
  display: grid;
  grid-template-columns: 1fr 1fr;
  gap: 1rem;
  margin-bottom: 1rem;
}

.detail-row:last-child {
  margin-bottom: 0;
}

.detail-item {
  display: flex;
  flex-direction: column;
}

.detail-label {
  color: var(--text-secondary);
  font-size: 0.75rem;
  font-weight: 500;
  text-transform: uppercase;
  letter-spacing: 0.025em;
  margin-bottom: 0.25rem;
}

.detail-value {
  color: var(--text-primary);
  font-weight: 600;
  font-size: 0.875rem;
}

.detail-value.amount {
  color: var(--success-color);
  font-weight: 700;
  font-size: 1rem;
}

.badge-group {
  display: flex;
  flex-wrap: wrap;
  gap: 0.25rem;
}

.role-badge {
  padding: 0.25rem 0.5rem;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.025em;
}

.role-presenter {
  background: #dbeafe;
  color: #1e40af;
}

.role-audience {
  background: #f3f4f6;
  color: #374151;
}

.participation-badge {
  background: #f9fafb;
  color: var(--text-secondary);
  border: 1px solid var(--border-color);
  padding: 0.25rem 0.5rem;
  border-radius: 6px;
  font-size: 0.75rem;
  font-weight: 500;
}

.voucher-info {
  background: linear-gradient(145deg, #f0fdf4 0%, #ecfdf5 100%);
  border: 1px solid #bbf7d0;
  border-radius: 8px;
  padding: 0.75rem;
  margin-bottom: 1rem;
}

.voucher-label {
  color: var(--text-secondary);
  font-size: 0.75rem;
  margin-bottom: 0.25rem;
}

.voucher-details {
  color: var(--success-color);
  font-weight: 700;
  font-size: 0.875rem;
}

.verification-info {
  margin-top: 0.5rem;
  padding-top: 0.5rem;
  border-top: 1px solid var(--border-color);
}

.verification-text {
  color: var(--text-secondary);
  font-size: 0.75rem;
}

.payment-footer {
  padding: 1rem 1.5rem;
  background: #f8fafc;
  border-top: 1px solid var(--border-color);
  margin-top: auto;
}

.action-buttons {
  display: flex;
  justify-content: space-between;
  align-items: center;
  flex-wrap: wrap;
  gap: 0.75rem;
}

.primary-actions,
.verification-actions {
  display: flex;
  gap: 0.5rem;
}

.empty-state {
  text-align: center;
  padding: 4rem 2rem;
  background: white;
  border-radius: var(--border-radius);
  border: 2px dashed var(--border-color);
  margin: 2rem 0;
}

.empty-icon {
  font-size: 4rem;
  color: var(--text-secondary);
  margin-bottom: 1rem;
}

.empty-title {
  color: var(--text-primary);
  font-weight: 700;
  margin-bottom: 0.5rem;
}

.empty-text {
  color: var(--text-secondary);
  margin-bottom: 0;
}

.load-more-section {
  text-align: center;
  margin-top: 2rem;
  padding-top: 2rem;
  border-top: 1px solid var(--border-color);
}

.modal-header {
  background: linear-gradient(135deg, var(--primary-color) 0%, #1e40af 100%);
  color: white;
  border-bottom: none;
}

.modal-title {
  font-weight: 700;
}

.form-group {
  margin-bottom: 1rem;
}

.btn {
  font-weight: 600;
  border-radius: 8px;
  transition: all 0.3s ease;
}

.btn-sm {
  padding: 0.375rem 0.75rem;
  font-size: 0.875rem;
}

.btn-lg {
  padding: 0.75rem 1.5rem;
  font-size: 1.1rem;
}

.btn:hover {
  transform: translateY(-1px);
  box-shadow: var(--shadow-sm);
}

@media (max-width: 768px) {
  .header-section {
    padding: 1.5rem;
    text-align: center;
  }
  
  .header-title {
    font-size: 1.75rem;
  }
  
  .stat-content {
    flex-direction: column;
    text-align: center;
    gap: 0.75rem;
  }
  
  .detail-row {
    grid-template-columns: 1fr;
    gap: 0.75rem;
  }
  
  .detail-row.mb-3 {
    grid-template-columns: 1fr;
  }
  
  .action-buttons {
    justify-content: center;
    flex-direction: column;
  }
  
  .primary-actions,
  .verification-actions {
    width: 100%;
    justify-content: center;
  }
  
  .pricing-tier-mini {
    flex-direction: column;
    align-items: flex-start;
  }
  
  .savings-badge {
    width: 100%;
  }
}

@media (max-width: 576px) {
  .user-info {
    flex-direction: column;
    text-align: center;
    gap: 0.75rem;
  }
  
  .payment-method-display {
    flex-direction: column;
    text-align: center;
    gap: 0.5rem;
  }
  
  .transaction-id-value {
    font-size: 0.65rem;
  }
}
</style>

<script>
(function() {
  'use strict';

  const $ = (selector, context = document) => context.querySelector(selector);
  const $$ = (selector, context = document) => Array.from(context.querySelectorAll(selector));

  const searchInput = $('#searchInput');
  const statusFilter = $('#statusFilter');
  const roleFilter = $('#roleFilter');
  const pricingFilter = $('#pricingFilter'); // NEW
  const participationFilter = $('#participationFilter');
  const resetButton = $('#btnResetFilter');
  const resultCounter = $('#resultCounter');

  const buktiModal = new bootstrap.Modal($('#buktiModal'));
  const verifikasiModal = new bootstrap.Modal($('#verifikasiModal'));

  function applyFilters() {
    const searchQuery = (searchInput?.value || '').toLowerCase();
    const statusValue = statusFilter?.value || '';
    const roleValue = roleFilter?.value || '';
    const pricingValue = pricingFilter?.value || ''; // NEW
    const participationValue = participationFilter?.value || '';
    
    const paymentCards = $$('#paymentContainer > div');
    let visibleCount = 0;

    paymentCards.forEach(card => {
      const searchData = (card.getAttribute('data-search') || '').toLowerCase();
      const statusData = card.getAttribute('data-status') || '';
      const roleData = card.getAttribute('data-role') || '';
      const pricingData = card.getAttribute('data-pricing') || ''; // NEW
      const participationData = card.getAttribute('data-participation') || '';

      const matchesSearch = !searchQuery || searchData.includes(searchQuery);
      const matchesStatus = !statusValue || statusData === statusValue;
      const matchesRole = !roleValue || roleData === roleValue;
      const matchesPricing = !pricingValue || pricingData === pricingValue; // NEW
      const matchesParticipation = !participationValue || participationData === participationValue;

      const shouldShow = matchesSearch && matchesStatus && matchesRole && matchesPricing && matchesParticipation;
      
      card.style.display = shouldShow ? '' : 'none';
      if (shouldShow) visibleCount++;
    });

    if (resultCounter) {
      resultCounter.textContent = `Menampilkan ${visibleCount} dari ${paymentCards.length} pembayaran`;
    }
  }

  function resetFilters() {
    if (searchInput) searchInput.value = '';
    if (statusFilter) statusFilter.value = '';
    if (roleFilter) roleFilter.value = '';
    if (pricingFilter) pricingFilter.value = ''; // NEW
    if (participationFilter) participationFilter.value = '';
    applyFilters();
  }

  searchInput?.addEventListener('input', applyFilters);
  statusFilter?.addEventListener('change', applyFilters);
  roleFilter?.addEventListener('change', applyFilters);
  pricingFilter?.addEventListener('change', applyFilters); // NEW
  participationFilter?.addEventListener('change', applyFilters);
  resetButton?.addEventListener('click', resetFilters);

  $$('.btn-view-bukti').forEach(button => {
    button.addEventListener('click', () => {
      const buktiUrl = button.getAttribute('data-bukti-url');
      const buktiImage = $('#buktiImage');
      
      if (buktiImage && buktiUrl) {
        buktiImage.src = buktiUrl;
        buktiModal.show();
      }
    });
  });

  $$('.btn-open-verif').forEach(button => {
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
      if (statusField) {
        statusField.value = status;
      }

      const form = $('#verifikasiForm');
      if (form) {
        form.action = `<?= site_url('admin/pembayaran/verifikasi') ?>/${paymentId}`;
      }

      const submitButton = $('#verifikasiSubmit');
      if (submitButton) {
        submitButton.className = `btn btn-${isVerification ? 'success' : 'danger'}`;
        submitButton.innerHTML = `<i class="bi bi-save me-2"></i>${isVerification ? 'Verifikasi' : 'Tolak'}`;
      }

      verifikasiModal.show();
    });
  });

  function autoRefreshPendingPayments() {
    const hasPendingPayments = document.querySelector('[data-status="pending"]');
    
    if (!hasPendingPayments) return;

    fetch(window.location.href, {
      headers: { 'X-Requested-With': 'XMLHttpRequest' }
    })
    .then(response => response.text())
    .then(html => {
      const parser = new DOMParser();
      const doc = parser.parseFromString(html, 'text/html');
      const newContainer = doc.querySelector('#paymentContainer');
      
      if (newContainer) {
        const currentContainer = $('#paymentContainer');
        if (currentContainer) {
          currentContainer.innerHTML = newContainer.innerHTML;
          bindEventListeners();
          applyFilters();
        }
      }
    })
    .catch(error => {
      console.error('Auto refresh failed:', error);
    });
  }

  function bindEventListeners() {
    $$('.btn-view-bukti').forEach(button => {
      button.addEventListener('click', () => {
        const buktiUrl = button.getAttribute('data-bukti-url');
        const buktiImage = $('#buktiImage');
        
        if (buktiImage && buktiUrl) {
          buktiImage.src = buktiUrl;
          buktiModal.show();
        }
      });
    });

    $$('.btn-open-verif').forEach(button => {
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
        if (statusField) {
          statusField.value = status;
        }

        const form = $('#verifikasiForm');
        if (form) {
          form.action = `<?= site_url('admin/pembayaran/verifikasi') ?>/${paymentId}`;
        }

        const submitButton = $('#verifikasiSubmit');
        if (submitButton) {
          submitButton.className = `btn btn-${isVerification ? 'success' : 'danger'}`;
          submitButton.innerHTML = `<i class="bi bi-save me-2"></i>${isVerification ? 'Verifikasi' : 'Tolak'}`;
        }

        verifikasiModal.show();
      });
    });
  }

  const loadMoreButton = $('#btnLoadMore');
  if (loadMoreButton) {
    loadMoreButton.addEventListener('click', () => {
      console.log('Load more payments...');
    });
  }

  function initialize() {
    applyFilters();
    setInterval(autoRefreshPendingPayments, 30000);
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initialize);
  } else {
    initialize();
  }

})();
</script>