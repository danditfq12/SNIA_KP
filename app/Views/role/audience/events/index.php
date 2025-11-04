<?php
$title        = $title        ?? 'Event Tersedia';
$qRaw         = $q            ?? '';
$fmt          = $format       ?? '';
$isSearching  = $isSearching  ?? false;

$openEvents   = $openEvents   ?? [];
$closedEvents = $closedEvents ?? [];
$myRegs       = $myRegs       ?? [];

$totalAll     = $total_all    ?? (count($openEvents)+count($closedEvents));
$totalOpen    = $total_open   ?? count($openEvents);
$totalClosed  = $total_closed ?? count($closedEvents);

$rupiah = function($n){ return 'Rp ' . number_format((float)$n, 0, ',', '.'); };
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_audience') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <!-- Header -->
      <div class="dashboard-header mb-4">
        <div class="header-content">
          <div class="header-title">
            <div class="icon-wrapper">
              <i class="bi bi-calendar2-event"></i>
            </div>
            <div>
              <h2 class="title-text">Event Tersedia</h2>
              <p class="subtitle-text">Pilih & kelola pendaftaran event</p>
            </div>
          </div>
          <div class="header-stats">
            <div class="stat-item">
              <div class="stat-label">Hari ini</div>
              <div class="stat-value"><?= date('d M Y') ?></div>
            </div>
          </div>
        </div>
      </div>

      <!-- Filter Card -->
      <div class="modern-card mb-4">
        <div class="card-header-modern header-filter">
          <div class="header-left">
            <i class="bi bi-funnel-fill"></i>
            <strong>Filter & Pencarian</strong>
          </div>
        </div>
        <div class="card-body p-3 p-md-4">
          <form method="get" action="">
            <div class="row g-3">
              <div class="col-12 col-md-6 col-lg-5">
                <div class="input-group-modern">
                  <i class="bi bi-search"></i>
                  <input type="text" name="q" value="<?= esc($qRaw) ?>" 
                         class="form-control-modern" placeholder="Cari judul event atau lokasi...">
                </div>
              </div>
              <div class="col-6 col-md-3 col-lg-2">
                <select name="format" class="form-select-modern">
                  <option value="">Semua Format</option>
                  <option value="online"  <?= $fmt==='online'  ? 'selected':'' ?>>🌐 Online</option>
                  <option value="offline" <?= $fmt==='offline' ? 'selected':'' ?>>📍 Offline</option>
                  <option value="both"    <?= $fmt==='both'    ? 'selected':'' ?>>🔄 Hybrid</option>
                </select>
              </div>
              <div class="col-6 col-md-3 col-lg-2">
                <div class="d-flex gap-2">
                  <button type="submit" class="btn-modern btn-search">
                    <i class="bi bi-search"></i>
                    <span>Cari</span>
                  </button>
                  <?php if ($isSearching): ?>
                    <a href="<?= current_url() ?>" class="btn-reset" title="Reset filter">
                      <i class="bi bi-arrow-counterclockwise"></i>
                    </a>
                  <?php endif; ?>
                </div>
              </div>
              <div class="col-12 col-lg-3">
                <div class="filter-info">
                  <i class="bi bi-info-circle"></i>
                  <span>
                    <strong><?= (int)$totalAll ?></strong> event
                    (<span class="text-success"><?= (int)$totalOpen ?> terbuka</span>, 
                    <span class="text-muted"><?= (int)$totalClosed ?> ditutup</span>)
                  </span>
                </div>
              </div>
            </div>
          </form>
        </div>
      </div>

      <?php if ($totalAll > 0): ?>

        <!-- Pendaftaran Dibuka -->
        <?php if (!empty($openEvents)): ?>
        <div class="modern-card mb-4">
          <div class="card-header-modern header-success">
            <div class="header-left">
              <i class="bi bi-unlock-fill"></i>
              <strong>Pendaftaran Dibuka</strong>
            </div>
            <span class="count-badge badge-success"><?= (int)$totalOpen ?></span>
          </div>
          <div class="card-body p-3 p-md-4">
            <div class="row g-4">
              <?php foreach ($openEvents as $e): ?>
                <?php
                  $fmtEvent   = strtolower($e['format'] ?? '');
                  $onlineOK   = in_array($fmtEvent, ['online','both'], true);
                  $offlineOK  = in_array($fmtEvent, ['offline','both'], true);
                  $pOn        = (float)($e['audience_fee_online']  ?? 0);
                  $pOff       = (float)($e['audience_fee_offline'] ?? 0);

                  $regRaw        = $myRegs[$e['id']] ?? null;
                  $regStatus     = is_array($regRaw) ? ($regRaw['status'] ?? null)         : $regRaw;
                  $paymentId     = is_array($regRaw) ? ($regRaw['payment_id'] ?? null)     : null;
                  $paymentStatus = is_array($regRaw) ? ($regRaw['payment_status'] ?? null) : null;
                  $regId         = is_array($regRaw) ? ($regRaw['reg_id'] ?? null)         : null;

                  $isWaitingVerification = (
                    $regStatus === 'menunggu_pembayaran' &&
                    !empty($paymentId) &&
                    $paymentStatus === 'uploaded'
                  );
                  $isPendingPayment = (
                    $regStatus === 'menunggu_pembayaran' &&
                    !empty($paymentId) &&
                    $paymentStatus === 'pending'
                  );
                  $isRegistered = ($regStatus !== null && $regStatus !== 'batal');

                  $tgl = !empty($e['event_date']) ? date('d M Y', strtotime($e['event_date'])) : '-';
                  $jam = $e['event_time'] ?? '-';
                  $loc = $e['location'] ?? ($fmtEvent === 'online' ? 'Platform Online' : '-');
                ?>
                <div class="col-12 col-md-6 col-xl-4">
                  <div class="event-card-modern event-open">
                    <!-- Header -->
                    <div class="event-header">
                      <div class="event-format-badge badge-<?= esc($fmtEvent) ?>">
                        <?php if($fmtEvent === 'online'): ?>
                          <i class="bi bi-wifi"></i> Online
                        <?php elseif($fmtEvent === 'offline'): ?>
                          <i class="bi bi-geo-alt-fill"></i> Offline
                        <?php else: ?>
                          <i class="bi bi-arrow-left-right"></i> Hybrid
                        <?php endif; ?>
                      </div>
                      <?php if ($isRegistered): ?>
                        <div class="registered-badge">
                          <i class="bi bi-check-circle-fill"></i>
                        </div>
                      <?php endif; ?>
                    </div>

                    <!-- Title -->
                    <h5 class="event-title"><?= esc($e['title'] ?? 'Event') ?></h5>

                    <!-- Info -->
                    <div class="event-info-group">
                      <div class="info-item">
                        <i class="bi bi-calendar-event"></i>
                        <span><?= esc($tgl) ?> · <?= esc($jam) ?></span>
                      </div>
                      <div class="info-item">
                        <i class="bi bi-geo-alt"></i>
                        <span><?= esc($loc) ?></span>
                      </div>
                    </div>

                    <!-- Price -->
                    <div class="price-section">
                      <div class="price-label">Harga Audience</div>
                      <div class="price-badges">
                        <?php if ($onlineOK): ?>
                          <div class="price-badge price-online">
                            <i class="bi bi-wifi"></i>
                            <span><?= $pOn>0 ? $rupiah($pOn) : 'Gratis' ?></span>
                          </div>
                        <?php endif; ?>
                        <?php if ($offlineOK): ?>
                          <div class="price-badge price-offline">
                            <i class="bi bi-geo-alt-fill"></i>
                            <span><?= $pOff>0 ? $rupiah($pOff) : 'Gratis' ?></span>
                          </div>
                        <?php endif; ?>
                      </div>
                    </div>

                    <!-- Status Badges -->
                    <?php if ($isRegistered): ?>
                      <div class="status-badges-group">
                        <?php if ($isWaitingVerification): ?>
                          <span class="mini-badge badge-warning">
                            <i class="bi bi-hourglass-split"></i>
                            Menunggu Verifikasi
                          </span>
                        <?php elseif ($regStatus === 'menunggu_pembayaran'): ?>
                          <span class="mini-badge badge-warning">
                            <i class="bi bi-credit-card"></i>
                            Menunggu Pembayaran
                          </span>
                        <?php elseif ($regStatus === 'lunas'): ?>
                          <span class="mini-badge badge-success">
                            <i class="bi bi-check-circle-fill"></i>
                            Lunas
                          </span>
                        <?php endif; ?>
                      </div>
                    <?php endif; ?>

                    <!-- Actions -->
                    <div class="event-actions">
                      <?php if ($isRegistered): ?>
                        <?php if ($isWaitingVerification): ?>
                          <a href="<?= site_url('audience/pembayaran/detail/'.(int)$paymentId) ?>"
                             class="btn-event btn-primary">
                            <i class="bi bi-file-earmark-text"></i>
                            Detail Pembayaran
                          </a>
                          <a href="<?= site_url('audience/pembayaran/detail/'.(int)$paymentId).'#unggah-ulang' ?>"
                             class="btn-event btn-outline">
                            <i class="bi bi-upload"></i>
                            Ubah Bukti
                          </a>
                        <?php elseif ($isPendingPayment): ?>
                          <a href="<?= site_url('audience/pembayaran/instruction/'.(int)$regId) ?>"
                             class="btn-event btn-primary js-go-pay"
                             data-title="<?= esc($e['title'] ?? 'Event') ?>">
                            <i class="bi bi-credit-card-fill"></i>
                            Lanjutkan Pembayaran
                          </a>
                          <a href="<?= site_url('audience/pembayaran/detail/'.(int)$paymentId) ?>"
                             class="btn-event btn-outline">
                            <i class="bi bi-eye"></i>
                            Detail
                          </a>
                        <?php elseif ($regStatus === 'menunggu_pembayaran'): ?>
                          <?php if (!empty($regId)): ?>
                          <a href="<?= site_url('audience/pembayaran/instruction/'.(int)$regId) ?>"
                             class="btn-event btn-primary js-go-pay"
                             data-title="<?= esc($e['title'] ?? 'Event') ?>">
                            <i class="bi bi-credit-card-fill"></i>
                            Lanjutkan Pembayaran
                          </a>
                          <?php else: ?>
                          <a href="<?= site_url('audience/pembayaran') ?>"
                             class="btn-event btn-primary">
                            <i class="bi bi-wallet2"></i>
                            Pembayaran
                          </a>
                          <?php endif; ?>
                          <a href="<?= site_url('audience/events/detail/'.($e['id'] ?? 0)) ?>"
                             class="btn-event btn-outline">
                            <i class="bi bi-eye"></i>
                            Lihat Status
                          </a>
                        <?php else: ?>
                          <a href="<?= site_url('audience/events/detail/'.($e['id'] ?? 0)) ?>"
                             class="btn-event btn-primary">
                            <i class="bi bi-eye"></i>
                            Lihat Status
                          </a>
                        <?php endif; ?>
                      <?php else: ?>
                        <a href="<?= site_url('audience/events/detail/'.($e['id'] ?? 0)) ?>"
                           class="btn-event btn-outline">
                          <i class="bi bi-info-circle"></i>
                          Detail Event
                        </a>
                        <a href="<?= site_url('audience/events/register/'.($e['id'] ?? 0)) ?>"
                           class="btn-event btn-primary js-register"
                           data-title="<?= esc($e['title'] ?? 'Event') ?>">
                          <i class="bi bi-calendar-check"></i>
                          Daftar Sekarang
                        </a>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <?php endif; ?>

        <!-- Ditutup / Selesai -->
        <?php if (!empty($closedEvents)): ?>
        <div class="modern-card">
          <div class="card-header-modern header-secondary">
            <div class="header-left">
              <i class="bi bi-lock-fill"></i>
              <strong>Ditutup / Selesai</strong>
            </div>
            <span class="count-badge badge-secondary"><?= (int)$totalClosed ?></span>
          </div>
          <div class="card-body p-3 p-md-4">
            <div class="row g-4">
              <?php foreach ($closedEvents as $e): ?>
                <?php
                  $fmtEvent   = strtolower($e['format'] ?? '');
                  $onlineOK   = in_array($fmtEvent, ['online','both'], true);
                  $offlineOK  = in_array($fmtEvent, ['offline','both'], true);
                  $pOn        = (float)($e['audience_fee_online']  ?? 0);
                  $pOff       = (float)($e['audience_fee_offline'] ?? 0);

                  $regRaw        = $myRegs[$e['id']] ?? null;
                  $regStatus     = is_array($regRaw) ? ($regRaw['status'] ?? null) : $regRaw;
                  $paymentId     = is_array($regRaw) ? ($regRaw['payment_id'] ?? null) : null;
                  $regId         = is_array($regRaw) ? ($regRaw['reg_id'] ?? null) : null;

                  $isRegistered = ($regStatus !== null && $regStatus !== 'batal');

                  $tgl = !empty($e['event_date']) ? date('d M Y', strtotime($e['event_date'])) : '-';
                  $jam = $e['event_time'] ?? '-';
                  $loc = $e['location'] ?? ($fmtEvent === 'online' ? 'Platform Online' : '-');
                ?>
                <div class="col-12 col-md-6 col-xl-4">
                  <div class="event-card-modern event-closed">
                    <!-- Header -->
                    <div class="event-header">
                      <div class="event-format-badge badge-secondary">
                        <i class="bi bi-lock-fill"></i> Ditutup
                      </div>
                      <?php if ($isRegistered): ?>
                        <div class="registered-badge">
                          <i class="bi bi-check-circle-fill"></i>
                        </div>
                      <?php endif; ?>
                    </div>

                    <!-- Title -->
                    <h5 class="event-title event-title-muted"><?= esc($e['title'] ?? 'Event') ?></h5>

                    <!-- Info -->
                    <div class="event-info-group">
                      <div class="info-item">
                        <i class="bi bi-calendar-event"></i>
                        <span><?= esc($tgl) ?> · <?= esc($jam) ?></span>
                      </div>
                      <div class="info-item">
                        <i class="bi bi-geo-alt"></i>
                        <span><?= esc($loc) ?></span>
                      </div>
                    </div>

                    <!-- Status -->
                    <?php if ($isRegistered): ?>
                      <div class="status-badges-group">
                        <span class="mini-badge badge-success">
                          <i class="bi bi-check-circle-fill"></i>
                          Terdaftar
                        </span>
                      </div>
                    <?php endif; ?>

                    <!-- Actions -->
                    <div class="event-actions">
                      <a href="<?= site_url('audience/events/detail/'.($e['id'] ?? 0)) ?>"
                         class="btn-event btn-outline">
                        <i class="bi bi-info-circle"></i>
                        Detail Event
                      </a>

                      <?php if ($isRegistered && $regStatus === 'menunggu_pembayaran'): ?>
                        <?php if (!empty($regId)): ?>
                        <a href="<?= site_url('audience/pembayaran/instruction/'.(int)$regId) ?>"
                           class="btn-event btn-primary js-go-pay"
                           data-title="<?= esc($e['title'] ?? 'Event') ?>">
                          <i class="bi bi-credit-card-fill"></i>
                          Bayar
                        </a>
                        <?php elseif (!empty($paymentId)): ?>
                        <a href="<?= site_url('audience/pembayaran/detail/'.(int)$paymentId) ?>"
                           class="btn-event btn-primary">
                          <i class="bi bi-wallet2"></i>
                          Pembayaran
                        </a>
                        <?php endif; ?>
                      <?php else: ?>
                        <button class="btn-event btn-disabled" disabled>
                          <i class="bi bi-lock"></i>
                          Pendaftaran Ditutup
                        </button>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        </div>
        <?php endif; ?>

      <?php else: ?>
        <!-- Empty State -->
        <div class="modern-empty-state">
          <div class="empty-icon-circle">
            <?php if ($isSearching): ?>
              <i class="bi bi-search"></i>
            <?php else: ?>
              <i class="bi bi-calendar-x"></i>
            <?php endif; ?>
          </div>
          <h4 class="empty-title">
            <?= $isSearching ? 'Tidak Ada Event yang Cocok' : 'Event Belum Tersedia' ?>
          </h4>
          <p class="empty-subtitle">
            <?= $isSearching 
              ? 'Coba ubah kata kunci pencarian atau filter format event.' 
              : 'Tunggu informasi event terbaru dari kami.' ?>
          </p>
          <?php if ($isSearching): ?>
            <a href="<?= current_url() ?>" class="btn-modern">
              <i class="bi bi-arrow-counterclockwise me-2"></i>Reset Filter
            </a>
          <?php endif; ?>
        </div>
      <?php endif; ?>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<style>
  :root {
    --blue-50: #eff6ff;
    --blue-100: #dbeafe;
    --blue-500: #3b82f6;
    --blue-600: #2563eb;
    --blue-700: #1d4ed8;
    --blue-900: #1e3a8a;
    --success: #10b981;
    --warning: #f59e0b;
    --gray-100: #f3f4f6;
    --gray-600: #4b5563;
    --gray-900: #111827;
  }

  body { 
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); 
    min-height: 100vh; 
  }

  /* HEADER */
  .dashboard-header {
    background: linear-gradient(135deg, var(--blue-600) 0%, var(--blue-700) 100%);
    border-radius: 20px;
    padding: 2rem;
    box-shadow: 0 10px 40px rgba(37, 99, 235, 0.2);
    position: relative;
    overflow: hidden;
  }
  .dashboard-header::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -10%;
    width: 300px;
    height: 300px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
  }
  .header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: relative;
    z-index: 1;
  }
  .header-title {
    display: flex;
    align-items: center;
    gap: 1rem;
  }
  .icon-wrapper {
    width: 60px;
    height: 60px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border-radius: 16px;
    display: grid;
    place-items: center;
    font-size: 1.8rem;
    color: white;
  }
  .title-text {
    font-size: 1.75rem;
    font-weight: 700;
    color: white;
    margin: 0;
  }
  .subtitle-text {
    color: rgba(255, 255, 255, 0.8);
    margin: 0;
    font-size: 0.95rem;
  }
  .header-stats {
    text-align: right;
  }
  .stat-item .stat-label {
    color: rgba(255, 255, 255, 0.7);
    font-size: 0.85rem;
    margin-bottom: 0.25rem;
  }
  .stat-item .stat-value {
    color: white;
    font-weight: 700;
    font-size: 1.1rem;
  }

  /* MODERN CARD */
  .modern-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
    border: 1px solid rgba(37, 99, 235, 0.08);
    overflow: hidden;
  }
  .card-header-modern {
    padding: 1.25rem 1.5rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 2px solid var(--gray-100);
  }
  .header-filter {
    background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
  }
  .header-success {
    background: linear-gradient(135deg, #d1fae5, #a7f3d0);
    border-bottom-color: #6ee7b7;
  }
  .header-secondary {
    background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
    border-bottom-color: #d1d5db;
  }
  .header-left {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 1.05rem;
    color: var(--gray-900);
  }
  .header-left i {
    font-size: 1.3rem;
  }
  .count-badge {
    padding: 0.4rem 0.9rem;
    border-radius: 20px;
    font-weight: 700;
    font-size: 0.9rem;
  }
  .badge-success { background: #6ee7b7; color: #065f46; }
  .badge-secondary { background: #d1d5db; color: #374151; }

  /* FILTER INPUT */
  .input-group-modern {
    position: relative;
  }
  .input-group-modern i {
    position: absolute;
    left: 1rem;
    top: 50%;
    transform: translateY(-50%);
    color: var(--gray-600);
    font-size: 1.1rem;
    z-index: 1;
  }
  .form-control-modern,
  .form-select-modern {
    width: 100%;
    padding: 0.75rem 1rem;
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    font-size: 0.95rem;
    transition: all 0.3s ease;
  }
  .form-control-modern {
    padding-left: 2.75rem;
  }
  .form-control-modern:focus,
  .form-select-modern:focus {
    outline: none;
    border-color: var(--blue-500);
    box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.1);
  }
  .btn-modern.btn-search {
    width: 100%;
    padding: 0.75rem 1.25rem;
  }
  .btn-reset {
    display: flex;
    align-items: center;
    justify-content: center;
    width: 45px;
    height: 45px;
    background: white;
    color: var(--gray-600);
    border: 2px solid #e5e7eb;
    border-radius: 12px;
    text-decoration: none;
    transition: all 0.3s ease;
    flex-shrink: 0;
  }
  .btn-reset:hover {
    background: var(--gray-100);
    border-color: var(--gray-600);
    color: var(--gray-900);
  }
  .filter-info {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    background: var(--blue-50);
    border-radius: 12px;
    font-size: 0.875rem;
    color: var(--gray-600);
    height: 100%;
  }
  .filter-info i {
    color: var(--blue-500);
    font-size: 1.1rem;
  }

  /* EVENT CARD */
  .event-card-modern {
    background: white;
    border-radius: 16px;
    padding: 1.5rem;
    border: 2px solid #e5e7eb;
    transition: all 0.3s ease;
    height: 100%;
    display: flex;
    flex-direction: column;
  }
  .event-card-modern:hover {
    transform: translateY(-4px);
    box-shadow: 0 12px 30px rgba(0, 0, 0, 0.12);
    border-color: var(--blue-500);
  }
  .event-open {
    border-left: 4px solid var(--success);
  }
  .event-closed {
    border-left: 4px solid var(--gray-600);
    opacity: 0.85;
  }
  .event-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
  }
  .event-format-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.4rem 0.85rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 700;
  }
  .badge-online {
    background: linear-gradient(135deg, #dbeafe, #bfdbfe);
    color: var(--blue-700);
  }
  .badge-offline {
    background: linear-gradient(135deg, #fef3c7, #fde68a);
    color: #92400e;
  }
  .badge-both {
    background: linear-gradient(135deg, #ddd6fe, #c4b5fd);
    color: #6d28d9;
  }
  .badge-secondary {
    background: #e5e7eb;
    color: var(--gray-700);
  }
  .registered-badge {
    width: 32px;
    height: 32px;
    background: linear-gradient(135deg, var(--success), #059669);
    border-radius: 50%;
    display: grid;
    place-items: center;
    color: white;
    font-size: 1rem;
  }
  .event-title {
    font-size: 1.15rem;
    font-weight: 700;
    color: var(--gray-900);
    margin: 0 0 1rem 0;
    line-height: 1.4;
  }
  .event-title-muted {
    color: var(--gray-600);
  }
  .event-info-group {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
    margin-bottom: 1rem;
    padding-bottom: 1rem;
    border-bottom: 1px solid #e5e7eb;
  }
  .info-item {
    display: flex;
    align-items: center;
    gap: 0.6rem;
    font-size: 0.875rem;
    color: var(--gray-600);
  }
  .info-item i {
    color: var(--blue-500);
    font-size: 1rem;
  }
  .price-section {
    margin-bottom: 1rem;
  }
  .price-label {
    font-size: 0.8rem;
    color: var(--gray-600);
    font-weight: 600;
    margin-bottom: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
  .price-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
  }
  .price-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.5rem 0.85rem;
    border-radius: 10px;
    font-size: 0.875rem;
    font-weight: 700;
  }
  .price-online {
    background: linear-gradient(135deg, #dbeafe, #bfdbfe);
    color: var(--blue-700);
    border: 2px solid #93c5fd;
  }
  .price-offline {
    background: linear-gradient(135deg, #fef3c7, #fde68a);
    color: #92400e;
    border: 2px solid #fcd34d;
  }
  .status-badges-group {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1rem;
  }
  .mini-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.4rem 0.75rem;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 600;
  }
  .badge-warning {
    background: #fef3c7;
    color: #92400e;
  }
  .badge-success {
    background: #d1fae5;
    color: #065f46;
  }
  .event-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-top: auto;
  }
  .btn-event {
    flex: 1;
    min-width: calc(50% - 0.25rem);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.75rem 1rem;
    border-radius: 10px;
    font-weight: 600;
    font-size: 0.875rem;
    text-decoration: none;
    transition: all 0.3s ease;
    border: none;
    text-align: center;
  }
  .btn-event.btn-primary {
    background: linear-gradient(135deg, var(--blue-600), var(--blue-700));
    color: white;
  }
  .btn-event.btn-primary:hover {
    background: linear-gradient(135deg, var(--blue-700), var(--blue-900));
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
    color: white;
  }
  .btn-event.btn-outline {
    background: white;
    color: var(--blue-600);
    border: 2px solid var(--blue-200);
  }
  .btn-event.btn-outline:hover {
    background: var(--blue-50);
    border-color: var(--blue-500);
    transform: translateY(-1px);
    color: var(--blue-700);
  }
  .btn-event.btn-disabled {
    background: var(--gray-100);
    color: var(--gray-600);
    cursor: not-allowed;
  }

  /* BUTTON MODERN */
  .btn-modern {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    background: linear-gradient(135deg, var(--blue-600), var(--blue-700));
    color: white;
    padding: 0.875rem 2rem;
    border-radius: 12px;
    font-weight: 600;
    text-decoration: none;
    box-shadow: 0 4px 15px rgba(37, 99, 235, 0.3);
    transition: all 0.3s ease;
    border: none;
  }
  .btn-modern:hover {
    background: linear-gradient(135deg, var(--blue-700), var(--blue-900));
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(37, 99, 235, 0.4);
    color: white;
  }

  /* EMPTY STATE */
  .modern-empty-state {
    background: white;
    border-radius: 20px;
    padding: 4rem 2rem;
    text-align: center;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
  }
  .empty-icon-circle {
    width: 100px;
    height: 100px;
    background: linear-gradient(135deg, var(--blue-100), var(--blue-200));
    border-radius: 50%;
    display: grid;
    place-items: center;
    margin: 0 auto 1.5rem;
    font-size: 2.5rem;
    color: var(--blue-600);
  }
  .empty-title {
    font-weight: 700;
    color: var(--gray-900);
    margin-bottom: 0.5rem;
  }
  .empty-subtitle {
    color: var(--gray-600);
    margin-bottom: 2rem;
    max-width: 500px;
    margin-left: auto;
    margin-right: auto;
    line-height: 1.6;
  }

  /* RESPONSIVE */
  @media (max-width: 991px) {
    .header-stats {
      display: none;
    }
  }

  @media (max-width: 768px) {
    .dashboard-header {
      padding: 1.5rem;
      border-radius: 16px;
    }
    .header-content {
      flex-direction: column;
      gap: 1rem;
      text-align: center;
    }
    .header-title {
      flex-direction: column;
      text-align: center;
    }
    .icon-wrapper {
      width: 50px;
      height: 50px;
      font-size: 1.5rem;
    }
    .title-text {
      font-size: 1.4rem;
    }
    
    .card-header-modern {
      padding: 1rem 1.25rem;
    }
    .header-left {
      font-size: 0.95rem;
    }
    .header-left i {
      font-size: 1.1rem;
    }
    
    .filter-info {
      font-size: 0.8rem;
    }
    
    .event-card-modern {
      padding: 1.25rem;
    }
    .event-title {
      font-size: 1.05rem;
    }
    
    .btn-event {
      min-width: 100%;
      font-size: 0.85rem;
    }
  }

  @media (max-width: 576px) {
    .dashboard-header {
      padding: 1.25rem;
    }
    .icon-wrapper {
      width: 45px;
      height: 45px;
      font-size: 1.3rem;
    }
    .title-text {
      font-size: 1.25rem;
    }
    .subtitle-text {
      font-size: 0.85rem;
    }
    
    .modern-card .card-body {
      padding: 1rem !important;
    }
    
    .event-card-modern {
      padding: 1rem;
    }
    .event-title {
      font-size: 1rem;
    }
    .info-item {
      font-size: 0.8rem;
    }
    
    .modern-empty-state {
      padding: 3rem 1.5rem;
    }
    .empty-icon-circle {
      width: 80px;
      height: 80px;
      font-size: 2rem;
    }
  }

  /* SCROLLBAR */
  ::-webkit-scrollbar {
    width: 8px;
    height: 8px;
  }
  ::-webkit-scrollbar-track {
    background: var(--gray-100);
    border-radius: 10px;
  }
  ::-webkit-scrollbar-thumb {
    background: #bfdbfe;
    border-radius: 10px;
  }
  ::-webkit-scrollbar-thumb:hover {
    background: var(--blue-500);
  }
</style>

<script>
document.querySelectorAll('.js-register').forEach(a=>{
  a.addEventListener('click', (e)=>{
    const title = a.getAttribute('data-title') || 'Event';
    if (window.Swal){
      e.preventDefault();
      Swal.fire({
        title:'Daftar ke Event Ini?',
        html:'<div style="font-size:1.1rem;font-weight:600;color:#2563eb;margin:1rem 0;">'+title+'</div><div style="color:#6b7280;">Kamu akan memilih mode partisipasi (online/offline) di langkah berikutnya.</div>',
        icon:'question',
        iconColor:'#3b82f6',
        showCancelButton:true,
        confirmButtonText:'<i class="bi bi-check-circle me-2"></i>Ya, Lanjut Daftar',
        cancelButtonText:'<i class="bi bi-x-circle me-2"></i>Batal',
        confirmButtonColor:'#2563eb',
        cancelButtonColor:'#6b7280',
        customClass: {
          confirmButton: 'btn-swal-confirm',
          cancelButton: 'btn-swal-cancel'
        }
      }).then(r=>{ if(r.isConfirmed) location.href = a.href; });
    } else if(!confirm('Daftar ke "'+title+'"?')) e.preventDefault();
  });
});

document.querySelectorAll('.js-cancel').forEach(a=>{
  a.addEventListener('click', (e)=>{
    const title = a.getAttribute('data-title') || 'Event';
    if (window.Swal){
      e.preventDefault();
      Swal.fire({
        title: 'Batalkan Pendaftaran?',
        html: '<div style="font-size:1.1rem;font-weight:600;color:#ef4444;margin:1rem 0;">'+title+'</div><div style="color:#6b7280;">Pembayaran yang sudah dilakukan tidak dapat dikembalikan.</div>',
        icon:'warning',
        iconColor:'#ef4444',
        showCancelButton:true,
        confirmButtonColor:'#ef4444',
        confirmButtonText:'<i class="bi bi-trash me-2"></i>Ya, Batalkan',
        cancelButtonText:'<i class="bi bi-arrow-left me-2"></i>Kembali',
        cancelButtonColor:'#6b7280'
      }).then(r=>{ if(r.isConfirmed) location.href = a.href; });
    } else if(!confirm('Batalkan pendaftaran "'+title+'"?')) e.preventDefault();
  });
});

document.querySelectorAll('.js-go-pay').forEach(a=>{
  a.addEventListener('click', (e)=>{
    const title = a.getAttribute('data-title') || 'Event';
    if (window.Swal){
      e.preventDefault();
      Swal.fire({
        title:'Lanjutkan Pembayaran?',
        html:'<div style="font-size:1.1rem;font-weight:600;color:#2563eb;margin:1rem 0;">'+title+'</div><div style="color:#6b7280;">Anda akan diarahkan ke halaman pembayaran.</div>',
        icon:'question',
        iconColor:'#3b82f6',
        showCancelButton:true,
        confirmButtonText:'<i class="bi bi-credit-card me-2"></i>Ya, Lanjutkan',
        cancelButtonText:'<i class="bi bi-x-circle me-2"></i>Batal',
        confirmButtonColor:'#2563eb',
        cancelButtonColor:'#6b7280'
      }).then(r=>{ if(r.isConfirmed) location.href = a.href; });
    }
  });
});
</script>