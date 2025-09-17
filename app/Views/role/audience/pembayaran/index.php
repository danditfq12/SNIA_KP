<?php
// =========================================
//  Enhanced Pembayaran - Index (Audience) with Midtrans Support
//  Controller mengirim: $payments, $eventMap, $badgeMap, $aktif, $riwayat
// =========================================
$title = $title ?? 'Pembayaran Saya';
$eventMap = $eventMap ?? [];
$badgeMap = $badgeMap ?? ['pending'=>'warning','verified'=>'success','rejected'=>'danger','canceled'=>'secondary'];
$aktif = $aktif ?? [];
$riwayat = $riwayat ?? [];

$fmtDate = function($s){ return $s ? date('d M Y, H:i', strtotime($s)) : '-'; };
$fmtRp = fn($n)=> 'Rp ' . number_format((float)$n, 0, ',', '.');

// Helper untuk payment method display
$getPaymentMethodInfo = function($method, $reference = null) {
    if ($method === 'midtrans') {
        return [
            'icon' => 'bi-credit-card',
            'label' => 'Digital Payment',
            'badge' => 'bg-primary',
            'description' => $reference ? "ID: " . substr($reference, -8) : 'Midtrans'
        ];
    }
    
    $methodLabels = [
        'transfer_bank' => 'Transfer Bank',
        'internet_banking' => 'Internet Banking', 
        'mobile_banking' => 'Mobile Banking',
        'atm' => 'ATM',
        'setor_tunai' => 'Setor Tunai',
        'lainnya' => 'Lainnya'
    ];
    
    return [
        'icon' => 'bi-bank',
        'label' => $methodLabels[$method] ?? ucfirst(str_replace('_', ' ', $method)),
        'badge' => 'bg-secondary',
        'description' => 'Manual Transfer'
    ];
};
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_audience') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <!-- Header Section -->
      <div class="header-section header-blue d-flex justify-content-between align-items-center mb-4">
        <div>
          <h3 class="welcome-text mb-1">
            <i class="bi bi-wallet2 me-2"></i>Pembayaran Saya
          </h3>
          <div class="text-white-50">Kelola & pantau status pembayaran event</div>
        </div>
        <div class="text-end d-none d-md-block">
          <small class="text-white-50 d-block">Total Pembayaran</small>
          <strong class="text-white"><?= count($aktif) + count($riwayat) ?></strong>
        </div>
      </div>

      <!-- Quick Stats -->
      <?php if (!empty($aktif) || !empty($riwayat)): ?>
      <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
          <div class="stat-card stat-pending">
            <div class="stat-icon">
              <i class="bi bi-hourglass-split"></i>
            </div>
            <div class="stat-content">
              <div class="stat-number"><?= count($aktif) ?></div>
              <div class="stat-label">Menunggu</div>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card stat-verified">
            <div class="stat-icon">
              <i class="bi bi-check-circle-fill"></i>
            </div>
            <div class="stat-content">
              <div class="stat-number">
                <?= count(array_filter($riwayat, fn($r) => $r['status'] === 'verified')) ?>
              </div>
              <div class="stat-label">Terverifikasi</div>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card stat-digital">
            <div class="stat-icon">
              <i class="bi bi-credit-card"></i>
            </div>
            <div class="stat-content">
              <div class="stat-number">
                <?= count(array_filter(array_merge($aktif, $riwayat), fn($r) => $r['metode'] === 'midtrans')) ?>
              </div>
              <div class="stat-label">Digital</div>
            </div>
          </div>
        </div>
        <div class="col-6 col-md-3">
          <div class="stat-card stat-total">
            <div class="stat-icon">
              <i class="bi bi-currency-dollar"></i>
            </div>
            <div class="stat-content">
              <div class="stat-number text-truncate" style="font-size: 0.9rem;">
                <?= $fmtRp(array_sum(array_column(array_filter(array_merge($aktif, $riwayat), fn($r) => $r['status'] === 'verified'), 'jumlah'))) ?>
              </div>
              <div class="stat-label">Total Bayar</div>
            </div>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <?php if (empty($aktif) && empty($riwayat)): ?>
        <!-- Empty State -->
        <div class="empty-state">
          <div class="empty-icon">
            <i class="bi bi-wallet2"></i>
          </div>
          <h5 class="empty-title">Belum Ada Pembayaran</h5>
          <p class="empty-subtitle">Pembayaran Anda akan muncul di sini setelah mendaftar event dan mengunggah bukti pembayaran</p>
          <a href="<?= site_url('audience/events') ?>" class="btn btn-primary">
            <i class="bi bi-calendar2-event me-2"></i>Lihat Event Tersedia
          </a>
        </div>
      <?php else: ?>

        <!-- ========== PEMBAYARAN AKTIF (Pending) ========== -->
        <?php if (!empty($aktif)): ?>
        <div class="card shadow-sm mb-4 border-0">
          <div class="card-header bg-gradient-primary text-white">
            <div class="d-flex align-items-center justify-content-between">
              <h5 class="mb-0">
                <i class="bi bi-hourglass-split me-2"></i>Menunggu Verifikasi
              </h5>
              <span class="badge bg-warning text-dark"><?= count($aktif) ?></span>
            </div>
          </div>
          <div class="card-body p-0">

            <!-- Desktop View -->
            <div class="d-none d-lg-block">
              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                  <thead class="table-light">
                    <tr>
                      <th style="width:50px">#</th>
                      <th>Event</th>
                      <th style="width:140px">Metode</th>
                      <th style="width:120px">Jumlah</th>
                      <th style="width:140px">Tanggal</th>
                      <th style="width:100px">Status</th>
                      <th style="width:100px"></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($aktif as $i => $row): 
                      $methodInfo = $getPaymentMethodInfo($row['metode'] ?? 'manual', $row['payment_reference'] ?? null);
                    ?>
                    <tr>
                      <td><?= $i+1 ?></td>
                      <td>
                        <div class="fw-semibold text-truncate" style="max-width:300px;">
                          <?= esc($eventMap[(int)$row['event_id']] ?? 'Event') ?>
                        </div>
                        <?php if (!empty($row['participation_type'])): ?>
                        <small class="text-muted"><?= ucfirst($row['participation_type']) ?></small>
                        <?php endif; ?>
                      </td>
                      <td>
                        <span class="badge <?= $methodInfo['badge'] ?> d-flex align-items-center gap-1">
                          <i class="<?= $methodInfo['icon'] ?>"></i>
                          <span class="d-none d-xl-inline"><?= $methodInfo['label'] ?></span>
                        </span>
                        <?php if ($methodInfo['description'] !== $methodInfo['label']): ?>
                        <div class="small text-muted mt-1"><?= $methodInfo['description'] ?></div>
                        <?php endif; ?>
                      </td>
                      <td class="fw-semibold"><?= $fmtRp($row['jumlah'] ?? 0) ?></td>
                      <td>
                        <div><?= esc(date('d M Y', strtotime($row['tanggal_bayar']))) ?></div>
                        <small class="text-muted"><?= esc(date('H:i', strtotime($row['tanggal_bayar']))) ?></small>
                      </td>
                      <td>
                        <span class="badge bg-<?= $badgeMap[$row['status']] ?? 'secondary' ?>">
                          <?= ucfirst(esc($row['status'])) ?>
                        </span>
                      </td>
                      <td>
                        <a class="btn btn-sm btn-primary" 
                           href="<?= site_url('audience/pembayaran/detail/'.(int)$row['id_pembayaran']) ?>">
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
            <div class="d-block d-lg-none p-3">
              <div class="row g-3">
                <?php foreach ($aktif as $row): 
                  $methodInfo = $getPaymentMethodInfo($row['metode'] ?? 'manual', $row['payment_reference'] ?? null);
                ?>
                <div class="col-12">
                  <div class="payment-card payment-pending">
                    <div class="payment-card-header">
                      <div class="d-flex align-items-start justify-content-between">
                        <div class="flex-grow-1 me-2">
                          <h6 class="payment-event-title mb-1">
                            <?= esc($eventMap[(int)$row['event_id']] ?? 'Event') ?>
                          </h6>
                          <div class="payment-meta">
                            <span class="badge <?= $methodInfo['badge'] ?> me-2">
                              <i class="<?= $methodInfo['icon'] ?> me-1"></i><?= $methodInfo['label'] ?>
                            </span>
                            <?php if (!empty($row['participation_type'])): ?>
                            <span class="badge bg-light text-dark">
                              <?= ucfirst($row['participation_type']) ?>
                            </span>
                            <?php endif; ?>
                          </div>
                        </div>
                        <span class="badge bg-<?= $badgeMap[$row['status']] ?? 'secondary' ?>">
                          <?= ucfirst(esc($row['status'])) ?>
                        </span>
                      </div>
                    </div>
                    
                    <div class="payment-card-body">
                      <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small">Jumlah</span>
                        <span class="fw-bold"><?= $fmtRp($row['jumlah'] ?? 0) ?></span>
                      </div>
                      <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted small">Tanggal</span>
                        <span class="small"><?= esc($fmtDate($row['tanggal_bayar'] ?? null)) ?></span>
                      </div>
                      
                      <a href="<?= site_url('audience/pembayaran/detail/'.(int)$row['id_pembayaran']) ?>" 
                         class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-eye me-1"></i>Lihat Detail
                      </a>
                    </div>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>

          </div>
        </div>
        <?php endif; ?>

        <!-- ========== RIWAYAT PEMBAYARAN ========== -->
        <?php if (!empty($riwayat)): ?>
        <div class="card shadow-sm border-0">
          <div class="card-header bg-gradient-secondary text-white">
            <div class="d-flex align-items-center justify-content-between">
              <h5 class="mb-0">
                <i class="bi bi-clock-history me-2"></i>Riwayat Pembayaran
              </h5>
              <span class="badge bg-light text-dark"><?= count($riwayat) ?></span>
            </div>
          </div>
          <div class="card-body p-0">

            <!-- Desktop View -->
            <div class="d-none d-lg-block">
              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                  <thead class="table-light">
                    <tr>
                      <th style="width:50px">#</th>
                      <th>Event</th>
                      <th style="width:140px">Metode</th>
                      <th style="width:120px">Jumlah</th>
                      <th style="width:140px">Tanggal</th>
                      <th style="width:100px">Status</th>
                      <th style="width:100px"></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($riwayat as $i => $row): 
                      $methodInfo = $getPaymentMethodInfo($row['metode'] ?? 'manual', $row['payment_reference'] ?? null);
                    ?>
                    <tr>
                      <td><?= $i+1 ?></td>
                      <td>
                        <div class="fw-semibold text-truncate" style="max-width:300px;">
                          <?= esc($eventMap[(int)$row['event_id']] ?? 'Event') ?>
                        </div>
                        <?php if (!empty($row['participation_type'])): ?>
                        <small class="text-muted"><?= ucfirst($row['participation_type']) ?></small>
                        <?php endif; ?>
                      </td>
                      <td>
                        <span class="badge <?= $methodInfo['badge'] ?> d-flex align-items-center gap-1">
                          <i class="<?= $methodInfo['icon'] ?>"></i>
                          <span class="d-none d-xl-inline"><?= $methodInfo['label'] ?></span>
                        </span>
                        <?php if ($methodInfo['description'] !== $methodInfo['label']): ?>
                        <div class="small text-muted mt-1"><?= $methodInfo['description'] ?></div>
                        <?php endif; ?>
                      </td>
                      <td class="fw-semibold"><?= $fmtRp($row['jumlah'] ?? 0) ?></td>
                      <td>
                        <div><?= esc(date('d M Y', strtotime($row['tanggal_bayar']))) ?></div>
                        <small class="text-muted"><?= esc(date('H:i', strtotime($row['tanggal_bayar']))) ?></small>
                      </td>
                      <td>
                        <span class="badge bg-<?= $badgeMap[$row['status']] ?? 'secondary' ?>">
                          <?= ucfirst(esc($row['status'])) ?>
                        </span>
                      </td>
                      <td>
                        <a class="btn btn-sm btn-outline-primary" 
                           href="<?= site_url('audience/pembayaran/detail/'.(int)$row['id_pembayaran']) ?>">
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
            <div class="d-block d-lg-none p-3">
              <div class="row g-3">
                <?php foreach ($riwayat as $row): 
                  $methodInfo = $getPaymentMethodInfo($row['metode'] ?? 'manual', $row['payment_reference'] ?? null);
                  $statusClass = match($row['status']) {
                    'verified' => 'payment-verified',
                    'rejected' => 'payment-rejected', 
                    'canceled' => 'payment-canceled',
                    default => 'payment-other'
                  };
                ?>
                <div class="col-12">
                  <div class="payment-card <?= $statusClass ?>">
                    <div class="payment-card-header">
                      <div class="d-flex align-items-start justify-content-between">
                        <div class="flex-grow-1 me-2">
                          <h6 class="payment-event-title mb-1">
                            <?= esc($eventMap[(int)$row['event_id']] ?? 'Event') ?>
                          </h6>
                          <div class="payment-meta">
                            <span class="badge <?= $methodInfo['badge'] ?> me-2">
                              <i class="<?= $methodInfo['icon'] ?> me-1"></i><?= $methodInfo['label'] ?>
                            </span>
                            <?php if (!empty($row['participation_type'])): ?>
                            <span class="badge bg-light text-dark">
                              <?= ucfirst($row['participation_type']) ?>
                            </span>
                            <?php endif; ?>
                          </div>
                        </div>
                        <span class="badge bg-<?= $badgeMap[$row['status']] ?? 'secondary' ?>">
                          <?= ucfirst(esc($row['status'])) ?>
                        </span>
                      </div>
                    </div>
                    
                    <div class="payment-card-body">
                      <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small">Jumlah</span>
                        <span class="fw-bold"><?= $fmtRp($row['jumlah'] ?? 0) ?></span>
                      </div>
                      <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted small">Tanggal</span>
                        <span class="small"><?= esc($fmtDate($row['tanggal_bayar'] ?? null)) ?></span>
                      </div>
                      
                      <a href="<?= site_url('audience/pembayaran/detail/'.(int)$row['id_pembayaran']) ?>" 
                         class="btn btn-outline-primary btn-sm w-100">
                        <i class="bi bi-eye me-1"></i>Lihat Detail
                      </a>
                    </div>
                  </div>
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
  :root{
    --primary-color:#2563eb; --info-color:#06b6d4; --success-color:#10b981; 
    --warning-color:#f59e0b; --danger-color:#ef4444; --secondary:#64748b;
  }
  
  body{ background:#f8fafc; }
  
  .header-section.header-blue{
    background:linear-gradient(135deg,var(--primary-color),#1e40af);
    color:#fff; padding:24px; border-radius:16px; 
    box-shadow:0 8px 28px rgba(0,0,0,.12);
  }
  .welcome-text{ font-weight:700; }
  
  .bg-gradient-primary{ 
    background:linear-gradient(135deg,var(--primary-color),var(--info-color))!important; 
  }
  .bg-gradient-secondary{ 
    background:linear-gradient(135deg,var(--secondary),#475569)!important; 
  }

  /* Stats Cards */
  .stat-card {
    background: white;
    border-radius: 12px;
    padding: 16px;
    display: flex;
    align-items: center;
    gap: 12px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
    border: 1px solid #f1f5f9;
    transition: all 0.2s ease;
  }
  
  .stat-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.12);
  }

  .stat-icon {
    width: 48px;
    height: 48px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
  }

  .stat-pending .stat-icon { background: #fef3c7; color: #d97706; }
  .stat-verified .stat-icon { background: #d1fae5; color: #059669; }
  .stat-digital .stat-icon { background: #dbeafe; color: #2563eb; }
  .stat-total .stat-icon { background: #f3e8ff; color: #7c3aed; }

  .stat-number {
    font-size: 1.5rem;
    font-weight: 700;
    line-height: 1;
    color: #1f2937;
  }

  .stat-label {
    font-size: 0.875rem;
    color: #6b7280;
    margin-top: 2px;
  }

  /* Empty State */
  .empty-state {
    text-align: center;
    padding: 60px 20px;
    background: white;
    border-radius: 16px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  }

  .empty-icon {
    width: 80px;
    height: 80px;
    background: #f3f4f6;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 24px;
    font-size: 2rem;
    color: #9ca3af;
  }

  .empty-title {
    color: #374151;
    margin-bottom: 8px;
  }

  .empty-subtitle {
    color: #6b7280;
    margin-bottom: 24px;
    max-width: 400px;
    margin-left: auto;
    margin-right: auto;
  }

  /* Payment Cards (Mobile) */
  .payment-card {
    background: white;
    border-radius: 12px;
    border: 1px solid #e5e7eb;
    overflow: hidden;
    transition: all 0.2s ease;
  }

  .payment-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-1px);
  }

  .payment-card-header {
    padding: 16px 16px 0;
  }

  .payment-card-body {
    padding: 0 16px 16px;
  }

  .payment-event-title {
    font-weight: 600;
    color: #1f2937;
    line-height: 1.3;
    margin: 0;
  }

  .payment-meta {
    margin-top: 8px;
  }

  /* Payment Status Colors */
  .payment-pending { border-left: 4px solid var(--warning-color); }
  .payment-verified { border-left: 4px solid var(--success-color); }
  .payment-rejected { border-left: 4px solid var(--danger-color); }
  .payment-canceled { border-left: 4px solid var(--secondary); }

  /* Responsive adjustments */
  @media (max-width: 767.98px){
    .header-section.header-blue{ padding: 20px; }
    .stat-card { padding: 14px; gap: 10px; }
    .stat-icon { width: 40px; height: 40px; font-size: 1.25rem; }
    .stat-number { font-size: 1.25rem; }
  }

  @media (max-width: 575.98px){
    .stat-card { padding: 12px; }
    .stat-icon { width: 36px; height: 36px; font-size: 1.1rem; }
    .empty-state { padding: 40px 16px; }
    .empty-icon { width: 64px; height: 64px; font-size: 1.75rem; }
  }
</style>