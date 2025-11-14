<?php
// =========================================
//  Enhanced Pembayaran - Index (Audience) with Midtrans Support
//  FIXED: Proper JavaScript DOM handling
// =========================================
$title = $title ?? 'Pembayaran Saya';
$eventMap = $eventMap ?? [];
$badgeMap = $badgeMap ?? ['pending'=>'warning','verified'=>'success','rejected'=>'danger','canceled'=>'secondary'];
$aktif = $aktif ?? [];
$riwayat = $riwayat ?? [];

$fmtDate = function($s){ return $s ? date('d M Y, H:i', strtotime($s)) : '-'; };
$fmtRp = fn($n)=> 'Rp ' . number_format((float)$n, 0, ',', '.');

// Helper untuk payment method display
$getPaymentMethodInfo = function($method, $paymentType = null) {
    if ($method === 'midtrans' && !empty($paymentType)) {
        $methodMap = [
            'dana' => ['icon' => 'wallet2', 'label' => 'DANA', 'color' => '#118eea'],
            'gopay' => ['icon' => 'wallet2', 'label' => 'GoPay', 'color' => '#00aa13'],
            'shopeepay' => ['icon' => 'wallet2', 'label' => 'ShopeePay', 'color' => '#ee4d2d'],
            'bca_va' => ['icon' => 'building', 'label' => 'BCA VA', 'color' => '#003087'],
            'bni_va' => ['icon' => 'building', 'label' => 'BNI VA', 'color' => '#ed7203'],
            'bri_va' => ['icon' => 'building', 'label' => 'BRI VA', 'color' => '#003d7a'],
            'qris' => ['icon' => 'qr-code', 'label' => 'QRIS', 'color' => '#d32f2f'],
        ];
        
        return $methodMap[$paymentType] ?? [
            'icon' => 'credit-card',
            'label' => 'Digital Payment',
            'color' => '#2563eb'
        ];
    }
    
    return [
        'icon' => 'credit-card',
        'label' => 'Digital Payment',
        'color' => '#2563eb'
    ];
};

$allPayments = array_merge($aktif, $riwayat);
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_audience') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <!-- Header Section -->
      <div class="page-header mb-4">
        <div class="header-content">
          <div class="header-left">
            <div class="header-icon">
              <i class="bi bi-wallet2"></i>
            </div>
            <div>
              <h1 class="page-title">Pembayaran Saya</h1>
              <p class="page-subtitle">Kelola & pantau status pembayaran event Anda</p>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Stats -->
      <?php if (!empty($allPayments)): ?>
      <div class="stats-grid mb-4">
        <div class="stat-card stat-pending">
          <div class="stat-icon">
            <i class="bi bi-hourglass-split"></i>
          </div>
          <div class="stat-content">
            <div class="stat-value"><?= count($aktif) ?></div>
            <div class="stat-label">Menunggu</div>
          </div>
        </div>
        <div class="stat-card stat-verified">
          <div class="stat-icon">
            <i class="bi bi-check-circle-fill"></i>
          </div>
          <div class="stat-content">
            <div class="stat-value">
              <?= count(array_filter($allPayments, fn($r)=> ($r['status'] ?? '') === 'verified')) ?>
            </div>
            <div class="stat-label">Terverifikasi</div>
          </div>
        </div>
        <div class="stat-card stat-digital">
          <div class="stat-icon">
            <i class="bi bi-credit-card-fill"></i>
          </div>
          <div class="stat-content">
            <div class="stat-value">
              <?= count(array_filter($allPayments, fn($r)=> ($r['metode'] ?? '') === 'midtrans')) ?>
            </div>
            <div class="stat-label">Digital</div>
          </div>
        </div>
        <div class="stat-card stat-total">
          <div class="stat-icon">
            <i class="bi bi-cash-stack"></i>
          </div>
          <div class="stat-content">
            <div class="stat-value stat-value-small">
              <?= $fmtRp(array_sum(array_column(array_filter($allPayments, fn($r)=> ($r['status'] ?? '') === 'verified'), 'jumlah'))) ?>
            </div>
            <div class="stat-label">Total Bayar</div>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <?php if (empty($allPayments)): ?>
        <!-- Empty State -->
        <div class="empty-state-container">
          <div class="empty-state-card">
            <div class="empty-icon">
              <i class="bi bi-wallet2"></i>
            </div>
            <h4 class="empty-title">Belum Ada Pembayaran</h4>
            <p class="empty-text">Pembayaran Anda akan muncul di sini setelah mendaftar event</p>
            <a href="<?= site_url('audience/events') ?>" class="btn-primary-custom">
              <i class="bi bi-calendar2-event"></i>
              <span>Lihat Event Tersedia</span>
            </a>
          </div>
        </div>
      <?php else: ?>

        <!-- PEMBAYARAN AKTIF (Pending) -->
        <?php if (!empty($aktif)): ?>
        <div class="content-card mb-4">
          <div class="card-header-custom">
            <div class="header-left">
              <div class="header-icon-small bg-warning">
                <i class="bi bi-hourglass-split"></i>
              </div>
              <span>Menunggu Verifikasi</span>
            </div>
            <span class="badge-count badge-warning"><?= count($aktif) ?></span>
          </div>
          <div class="card-body-custom">

            <!-- Desktop View -->
            <div class="d-none d-lg-block">
              <div class="table-wrapper">
                <table class="table-modern">
                  <thead>
                    <tr>
                      <th style="width:50px">#</th>
                      <th>Event</th>
                      <th style="width:160px">Metode</th>
                      <th style="width:140px">Jumlah</th>
                      <th style="width:160px">Tanggal</th>
                      <th style="width:120px">Status</th>
                      <th style="width:100px"></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($aktif as $i => $row): 
                      $methodInfo = $getPaymentMethodInfo($row['metode'] ?? 'manual', $row['midtrans_payment_type'] ?? null);
                    ?>
                    <tr>
                      <td><span class="row-number"><?= $i+1 ?></span></td>
                      <td>
                        <div class="event-info">
                          <div class="event-name"><?= esc($eventMap[(int)($row['event_id'] ?? 0)] ?? 'Event') ?></div>
                          <?php if (!empty($row['participation_type'])): ?>
                          <div class="event-type"><?= ucfirst($row['participation_type']) ?></div>
                          <?php endif; ?>
                        </div>
                      </td>
                      <td>
                        <span class="method-badge" style="background: <?= $methodInfo['color'] ?>; color: white;">
                          <i class="bi bi-<?= $methodInfo['icon'] ?>"></i>
                          <?= esc($methodInfo['label']) ?>
                        </span>
                      </td>
                      <td><span class="payment-amount"><?= $fmtRp($row['jumlah'] ?? 0) ?></span></td>
                      <td>
                        <div class="date-info">
                          <div class="date-main"><?= esc(date('d M Y', strtotime($row['tanggal_bayar'] ?? 'now'))) ?></div>
                          <div class="date-time"><?= esc(date('H:i', strtotime($row['tanggal_bayar'] ?? 'now'))) ?> WIB</div>
                        </div>
                      </td>
                      <td>
                        <span class="status-badge status-pending">
                          <i class="bi bi-clock"></i>
                          Pending
                        </span>
                      </td>
                      <td>
                        <a class="btn-action" 
                           href="<?= site_url('audience/pembayaran/detail/'.(int)($row['id_pembayaran'] ?? 0)) ?>">
                          <i class="bi bi-eye"></i>
                          Detail
                        </a>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Mobile View -->
            <div class="d-block d-lg-none">
              <div class="mobile-cards">
                <?php foreach ($aktif as $row): 
                  $methodInfo = $getPaymentMethodInfo($row['metode'] ?? 'manual', $row['midtrans_payment_type'] ?? null);
                ?>
                <div class="payment-mobile-card">
                  <div class="mobile-card-header">
                    <h6 class="mobile-card-title"><?= esc($eventMap[(int)($row['event_id'] ?? 0)] ?? 'Event') ?></h6>
                    <span class="mobile-status status-pending">
                      <i class="bi bi-clock"></i>
                      Pending
                    </span>
                  </div>
                  
                  <div class="mobile-info-list">
                    <div class="mobile-info-item">
                      <span class="mobile-label">
                        <i class="bi bi-credit-card"></i>
                        Metode
                      </span>
                      <span class="mobile-value"><?= esc($methodInfo['label']) ?></span>
                    </div>
                    <div class="mobile-info-item">
                      <span class="mobile-label">
                        <i class="bi bi-cash-stack"></i>
                        Jumlah
                      </span>
                      <span class="mobile-value"><?= $fmtRp($row['jumlah'] ?? 0) ?></span>
                    </div>
                    <div class="mobile-info-item">
                      <span class="mobile-label">
                        <i class="bi bi-calendar-event"></i>
                        Tanggal
                      </span>
                      <span class="mobile-value"><?= esc($fmtDate($row['tanggal_bayar'] ?? null)) ?></span>
                    </div>
                  </div>
                  
                  <a href="<?= site_url('audience/pembayaran/detail/'.(int)($row['id_pembayaran'] ?? 0)) ?>" 
                     class="btn-mobile-action">
                    <i class="bi bi-eye"></i>
                    Lihat Detail
                  </a>
                </div>
                <?php endforeach; ?>
              </div>
            </div>

          </div>
        </div>
        <?php endif; ?>

        <!-- RIWAYAT PEMBAYARAN -->
        <?php if (!empty($riwayat)): ?>
        <div class="content-card">
          <div class="card-header-custom">
            <div class="header-left">
              <div class="header-icon-small bg-secondary">
                <i class="bi bi-clock-history"></i>
              </div>
              <span>Riwayat Pembayaran</span>
            </div>
            <span class="badge-count badge-secondary"><?= count($riwayat) ?></span>
          </div>
          <div class="card-body-custom">

            <!-- Desktop View -->
            <div class="d-none d-lg-block">
              <div class="table-wrapper">
                <table class="table-modern">
                  <thead>
                    <tr>
                      <th style="width:50px">#</th>
                      <th>Event</th>
                      <th style="width:160px">Metode</th>
                      <th style="width:140px">Jumlah</th>
                      <th style="width:160px">Tanggal</th>
                      <th style="width:120px">Status</th>
                      <th style="width:100px"></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($riwayat as $i => $row): 
                      $methodInfo = $getPaymentMethodInfo($row['metode'] ?? 'manual', $row['midtrans_payment_type'] ?? null);
                      $status = $row['status'] ?? 'pending';
                      $statusClass = match($status) {
                        'verified' => 'status-verified',
                        'rejected' => 'status-rejected',
                        'canceled' => 'status-canceled',
                        default => 'status-other'
                      };
                      $statusIcon = match($status) {
                        'verified' => 'bi-check-circle-fill',
                        'rejected' => 'bi-x-circle-fill',
                        'canceled' => 'bi-dash-circle-fill',
                        default => 'bi-clock'
                      };
                      $statusLabel = match($status) {
                        'verified' => 'Verified',
                        'rejected' => 'Rejected',
                        'canceled' => 'Canceled',
                        default => ucfirst($status)
                      };
                    ?>
                    <tr>
                      <td><span class="row-number"><?= $i+1 ?></span></td>
                      <td>
                        <div class="event-info">
                          <div class="event-name"><?= esc($eventMap[(int)($row['event_id'] ?? 0)] ?? 'Event') ?></div>
                          <?php if (!empty($row['participation_type'])): ?>
                          <div class="event-type"><?= ucfirst($row['participation_type']) ?></div>
                          <?php endif; ?>
                        </div>
                      </td>
                      <td>
                        <span class="method-badge" style="background: <?= $methodInfo['color'] ?>; color: white;">
                          <i class="bi bi-<?= $methodInfo['icon'] ?>"></i>
                          <?= esc($methodInfo['label']) ?>
                        </span>
                      </td>
                      <td><span class="payment-amount"><?= $fmtRp($row['jumlah'] ?? 0) ?></span></td>
                      <td>
                        <div class="date-info">
                          <div class="date-main"><?= esc(date('d M Y', strtotime($row['tanggal_bayar'] ?? 'now'))) ?></div>
                          <div class="date-time"><?= esc(date('H:i', strtotime($row['tanggal_bayar'] ?? 'now'))) ?> WIB</div>
                        </div>
                      </td>
                      <td>
                        <span class="status-badge <?= $statusClass ?>">
                          <i class="<?= $statusIcon ?>"></i>
                          <?= $statusLabel ?>
                        </span>
                      </td>
                      <td>
                        <a class="btn-action btn-outline" 
                           href="<?= site_url('audience/pembayaran/detail/'.(int)($row['id_pembayaran'] ?? 0)) ?>">
                          <i class="bi bi-eye"></i>
                          Detail
                        </a>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Mobile View -->
            <div class="d-block d-lg-none">
              <div class="mobile-cards">
                <?php foreach ($riwayat as $row): 
                  $methodInfo = $getPaymentMethodInfo($row['metode'] ?? 'manual', $row['midtrans_payment_type'] ?? null);
                  $status = $row['status'] ?? 'pending';
                  $statusClass = match($status) {
                    'verified' => 'status-verified',
                    'rejected' => 'status-rejected',
                    'canceled' => 'status-canceled',
                    default => 'status-other'
                  };
                  $statusIcon = match($status) {
                    'verified' => 'bi-check-circle-fill',
                    'rejected' => 'bi-x-circle-fill',
                    'canceled' => 'bi-dash-circle-fill',
                    default => 'bi-clock'
                  };
                  $statusLabel = match($status) {
                    'verified' => 'Verified',
                    'rejected' => 'Rejected',
                    'canceled' => 'Canceled',
                    default => ucfirst($status)
                  };
                ?>
                <div class="payment-mobile-card">
                  <div class="mobile-card-header">
                    <h6 class="mobile-card-title"><?= esc($eventMap[(int)($row['event_id'] ?? 0)] ?? 'Event') ?></h6>
                    <span class="mobile-status <?= $statusClass ?>">
                      <i class="<?= $statusIcon ?>"></i>
                      <?= $statusLabel ?>
                    </span>
                  </div>
                  
                  <div class="mobile-info-list">
                    <div class="mobile-info-item">
                      <span class="mobile-label">
                        <i class="bi bi-credit-card"></i>
                        Metode
                      </span>
                      <span class="mobile-value"><?= esc($methodInfo['label']) ?></span>
                    </div>
                    <div class="mobile-info-item">
                      <span class="mobile-label">
                        <i class="bi bi-cash-stack"></i>
                        Jumlah
                      </span>
                      <span class="mobile-value"><?= $fmtRp($row['jumlah'] ?? 0) ?></span>
                    </div>
                    <div class="mobile-info-item">
                      <span class="mobile-label">
                        <i class="bi bi-calendar-event"></i>
                        Tanggal
                      </span>
                      <span class="mobile-value"><?= esc($fmtDate($row['tanggal_bayar'] ?? null)) ?></span>
                    </div>
                  </div>
                  
                  <a href="<?= site_url('audience/pembayaran/detail/'.(int)($row['id_pembayaran'] ?? 0)) ?>" 
                     class="btn-mobile-action btn-outline-mobile">
                    <i class="bi bi-eye"></i>
                    Lihat Detail
                  </a>
                </div>
                <?php endforeach; ?>
              </div>
            </div>

          </div>
        </div>
        <?php endif; ?>

      <?php endif; ?>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<style>
:root {
  --primary-blue: #2563eb;
  --light-blue: #3b82f6;
  --pale-blue: #dbeafe;
  --bg-blue: #eff6ff;
  --dark-blue: #1e3a8a;
  --text-dark: #1e293b;
  --text-gray: #64748b;
  --text-light: #94a3b8;
  --white: #ffffff;
  --success: #10b981;
  --warning: #f59e0b;
  --danger: #ef4444;
  --border: #e2e8f0;
  --shadow: rgba(15, 23, 42, 0.08);
}

body {
  background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
  min-height: 100vh;
}

.page-header {
  background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
  border-radius: 16px;
  padding: 2rem;
  box-shadow: 0 4px 6px rgba(37, 99, 235, 0.15);
}

.header-content {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.header-left {
  display: flex;
  align-items: center;
  gap: 1rem;
}

.header-icon {
  width: 56px;
  height: 56px;
  background: rgba(255, 255, 255, 0.2);
  border-radius: 12px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--white);
  font-size: 1.5rem;
}

.page-title {
  font-size: 1.75rem;
  font-weight: 700;
  color: var(--white);
  margin: 0 0 0.25rem 0;
}

.page-subtitle {
  color: rgba(255, 255, 255, 0.9);
  margin: 0;
  font-size: 0.95rem;
}

.stats-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
  gap: 1rem;
}

.stat-card {
  background: var(--white);
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 1.25rem;
  display: flex;
  align-items: center;
  gap: 1rem;
}

.stat-icon {
  width: 48px;
  height: 48px;
  border-radius: 10px;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 1.5rem;
  flex-shrink: 0;
}

.stat-content {
  flex: 1;
}

.stat-value {
  font-size: 1.5rem;
  font-weight: 700;
  line-height: 1;
  margin-bottom: 0.25rem;
}

.stat-value-small {
  font-size: 1.1rem;
}

.stat-label {
  font-size: 0.875rem;
  color: var(--text-gray);
  font-weight: 500;
}

.stat-pending .stat-icon {
  background: #fef3c7;
  color: #d97706;
}
.stat-pending .stat-value { color: #d97706; }

.stat-verified .stat-icon {
  background: #d1fae5;
  color: #059669;
}
.stat-verified .stat-value { color: #059669; }

.stat-digital .stat-icon {
  background: var(--pale-blue);
  color: var(--primary-blue);
}
.stat-digital .stat-value { color: var(--primary-blue); }

.stat-total .stat-icon {
  background: #ede9fe;
  color: #7c3aed;
}
.stat-total .stat-value { color: #7c3aed; }

.content-card {
  background: var(--white);
  border: 1px solid var(--border);
  border-radius: 16px;
  overflow: hidden;
  box-shadow: 0 2px 8px var(--shadow);
}

.card-header-custom {
  padding: 1.25rem 1.5rem;
  background: var(--bg-blue);
  border-bottom: 1px solid var(--border);
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.header-icon-small {
  width: 36px;
  height: 36px;
  border-radius: 8px;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--white);
  font-size: 1rem;
}

.header-icon-small.bg-warning {
  background: #f59e0b;
}

.header-icon-small.bg-secondary {
  background: var(--text-gray);
}

.header-left {
  display: flex;
  align-items: center;
  gap: 0.75rem;
  font-weight: 600;
  color: var(--text-dark);
  font-size: 1rem;
}

.badge-count {
  padding: 0.35rem 0.75rem;
  border-radius: 20px;
  font-weight: 600;
  font-size: 0.875rem;
}

.badge-warning { background: #fed7aa; color: #92400e; }
.badge-secondary { background: var(--border); color: #374151; }

.card-body-custom {
  padding: 1.5rem;
}

.table-wrapper {
  overflow-x: auto;
}

.table-modern {
  width: 100%;
  border-collapse: collapse;
}

.table-modern thead th {
  background: var(--bg-blue);
  color: var(--text-gray);
  font-weight: 600;
  font-size: 0.875rem;
  padding: 1rem;
  text-align: left;
  border-bottom: 1px solid var(--border);
}

.table-modern tbody td {
  padding: 1rem;
  border-bottom: 1px solid var(--border);
  vertical-align: middle;
}

.table-modern tbody tr:last-child td {
  border-bottom: none;
}

.row-number {
  display: inline-flex;
  width: 28px;
  height: 28px;
  align-items: center;
  justify-content: center;
  background: var(--pale-blue);
  color: var(--primary-blue);
  border-radius: 6px;
  font-weight: 600;
  font-size: 0.875rem;
}

.event-info .event-name {
  font-weight: 600;
  color: var(--text-dark);
  margin-bottom: 0.25rem;
}

.event-info .event-type {
  font-size: 0.813rem;
  color: var(--text-gray);
}

.method-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.375rem 0.75rem;
  border-radius: 8px;
  font-size: 0.813rem;
  font-weight: 600;
}

.payment-amount {
  font-weight: 700;
  color: var(--text-dark);
  font-size: 0.938rem;
}

.date-info .date-main {
  font-weight: 600;
  color: var(--text-dark);
  margin-bottom: 0.125rem;
  font-size: 0.875rem;
}

.date-info .date-time {
  font-size: 0.813rem;
  color: var(--text-gray);
}

.status-badge {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.375rem 0.75rem;
  border-radius: 20px;
  font-size: 0.813rem;
  font-weight: 600;
}

.status-badge.status-pending {
  background: #fef3c7;
  color: #92400e;
}

.status-badge.status-verified {
  background: #d1fae5;
  color: #065f46;
}

.status-badge.status-rejected {
  background: #fee2e2;
  color: #991b1b;
}

.status-badge.status-canceled,
.status-badge.status-other {
  background: var(--border);
  color: var(--text-gray);
}

.btn-action {
  display: inline-flex;
  align-items: center;
  gap: 0.375rem;
  padding: 0.5rem 1rem;
  border-radius: 8px;
  font-weight: 600;
  font-size: 0.875rem;
  text-decoration: none;
  background: var(--primary-blue);
  color: var(--white);
  transition: all 0.2s;
}

.btn-action.btn-outline {
  background: var(--white);
  color: var(--primary-blue);
  border: 1px solid var(--primary-blue);
}

.btn-action:hover {
  background: var(--dark-blue);
  color: var(--white);
}

.btn-action.btn-outline:hover {
  background: var(--bg-blue);
  color: var(--primary-blue);
}

.mobile-cards {
  display: flex;
  flex-direction: column;
  gap: 1rem;
}

.payment-mobile-card {
  background: var(--white);
  border: 1px solid var(--border);
  border-radius: 12px;
  overflow: hidden;
  border-left: 4px solid var(--primary-blue);
}

.mobile-card-header {
  padding: 1rem;
  display: flex;
  justify-content: space-between;
  align-items: start;
  gap: 0.75rem;
  background: var(--bg-blue);
  border-bottom: 1px solid var(--border);
}

.mobile-card-title {
  font-weight: 700;
  color: var(--text-dark);
  font-size: 0.938rem;
  margin: 0;
  flex: 1;
}

.mobile-status {
  display: inline-flex;
  align-items: center;
  gap: 0.313rem;
  padding: 0.375rem 0.625rem;
  border-radius: 20px;
  font-size: 0.75rem;
  font-weight: 700;
  white-space: nowrap;
}

.mobile-status.status-pending {
  background: #fef3c7;
  color: #92400e;
}

.mobile-status.status-verified {
  background: #d1fae5;
  color: #065f46;
}

.mobile-status.status-rejected {
  background: #fee2e2;
  color: #991b1b;
}

.mobile-status.status-canceled,
.mobile-status.status-other {
  background: var(--border);
  color: var(--text-gray);
}

.mobile-info-list {
  padding: 1rem;
  display: flex;
  flex-direction: column;
  gap: 0.75rem;
}

.mobile-info-item {
  display: flex;
  justify-content: space-between;
  align-items: center;
}

.mobile-label {
  display: flex;
  align-items: center;
  gap: 0.5rem;
  font-size: 0.875rem;
  color: var(--text-gray);
  font-weight: 500;
}

.mobile-label i {
  color: var(--primary-blue);
  font-size: 1rem;
}

.mobile-value {
  font-weight: 700;
  color: var(--text-dark);
  font-size: 0.875rem;
}

.btn-mobile-action {
  display: flex;
  align-items: center;
  justify-content: center;
  gap: 0.5rem;
  width: calc(100% - 2rem);
  margin: 0 1rem 1rem 1rem;
  padding: 0.75rem;
  background: var(--primary-blue);
  color: var(--white);
  border-radius: 8px;
  text-decoration: none;
  font-weight: 600;
  font-size: 0.875rem;
  transition: all 0.2s;
}

.btn-mobile-action.btn-outline-mobile {
  background: var(--white);
  color: var(--primary-blue);
  border: 1px solid var(--primary-blue);
}

.btn-mobile-action:hover {
  background: var(--dark-blue);
  color: var(--white);
}

.btn-mobile-action.btn-outline-mobile:hover {
  background: var(--bg-blue);
  color: var(--primary-blue);
}

.empty-state-container {
  display: flex;
  justify-content: center;
  padding: 2rem 0;
}

.empty-state-card {
  max-width: 500px;
  background: var(--white);
  border: 1px solid var(--border);
  border-radius: 16px;
  padding: 3rem 2rem;
  text-align: center;
}

.empty-icon {
  width: 80px;
  height: 80px;
  margin: 0 auto 1.5rem;
  background: var(--bg-blue);
  border-radius: 50%;
  display: flex;
  align-items: center;
  justify-content: center;
  color: var(--primary-blue);
  font-size: 2.5rem;
}

.empty-title {
  font-size: 1.25rem;
  font-weight: 700;
  color: var(--text-dark);
  margin-bottom: 0.75rem;
}

.empty-text {
  color: var(--text-gray);
  margin-bottom: 1.5rem;
  line-height: 1.6;
}

.btn-primary-custom {
  display: inline-flex;
  align-items: center;
  gap: 0.5rem;
  background: var(--primary-blue);
  color: var(--white);
  padding: 0.75rem 1.5rem;
  border-radius: 10px;
  font-weight: 600;
  text-decoration: none;
  font-size: 0.938rem;
  transition: all 0.2s;
}

.btn-primary-custom:hover {
  background: var(--dark-blue);
  color: var(--white);
}

@media (max-width: 992px) {
  .stats-grid {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 768px) {
  .page-header {
    padding: 1.5rem;
  }

  .header-icon {
    width: 48px;
    height: 48px;
    font-size: 1.25rem;
  }

  .page-title {
    font-size: 1.5rem;
  }

  .page-subtitle {
    font-size: 0.875rem;
  }

  .stat-card {
    padding: 1rem;
  }

  .stat-icon {
    width: 42px;
    height: 42px;
    font-size: 1.25rem;
  }

  .stat-value {
    font-size: 1.25rem;
  }

  .stat-label {
    font-size: 0.813rem;
  }

  .card-header-custom {
    padding: 1rem;
  }

  .card-body-custom {
    padding: 1rem;
  }

  .mobile-cards {
    gap: 0.75rem;
  }
}

@media (max-width: 576px) {
  .page-header {
    padding: 1.25rem;
  }

  .header-content {
    flex-direction: column;
    text-align: center;
  }

  .header-icon {
    width: 44px;
    height: 44px;
    font-size: 1.125rem;
  }

  .page-title {
    font-size: 1.25rem;
  }

  .page-subtitle {
    font-size: 0.813rem;
  }

  .stats-grid {
    grid-template-columns: 1fr;
    gap: 0.75rem;
  }

  .stat-card {
    padding: 0.875rem;
    gap: 0.75rem;
  }

  .stat-icon {
    width: 40px;
    height: 40px;
    font-size: 1.125rem;
  }

  .stat-value {
    font-size: 1.125rem;
  }

  .stat-value-small {
    font-size: 0.938rem;
  }

  .empty-state-card {
    padding: 2.5rem 1.5rem;
  }

  .empty-icon {
    width: 70px;
    height: 70px;
    font-size: 2rem;
  }

  .empty-title {
    font-size: 1.125rem;
  }

  .empty-text {
    font-size: 0.875rem;
  }
}

.table-wrapper::-webkit-scrollbar {
  height: 6px;
}

.table-wrapper::-webkit-scrollbar-track {
  background: var(--bg-blue);
  border-radius: 10px;
}

.table-wrapper::-webkit-scrollbar-thumb {
  background: var(--pale-blue);
  border-radius: 10px;
}

.table-wrapper::-webkit-scrollbar-thumb:hover {
  background: var(--light-blue);
}
</style>

<!-- ✅ NO PROBLEMATIC JAVASCRIPT - Pure server-side rendering -->