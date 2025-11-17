<?php

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
<link rel="stylesheet" href="<?= base_url('assets/css/pembayaran_index_audience.css'); ?>">

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
