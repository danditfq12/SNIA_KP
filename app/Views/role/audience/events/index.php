<?php
$title        = $title        ?? 'Event Tersedia';
$qRaw         = $q            ?? '';
$fmt          = $format       ?? '';
$isSearching  = $isSearching  ?? false;

$openEvents     = $openEvents     ?? [];
$pendingEvents  = $pendingEvents  ?? [];
$closedEvents   = $closedEvents   ?? [];
$finishedEvents = $finishedEvents ?? [];
$myRegs         = $myRegs         ?? [];

$totalAll      = $total_all      ?? (count($openEvents)+count($pendingEvents)+count($closedEvents)+count($finishedEvents));
$totalOpen     = $total_open     ?? count($openEvents);
$totalPending  = $total_pending  ?? count($pendingEvents);
$totalClosed   = $total_closed   ?? count($closedEvents);
$totalFinished = $total_finished ?? count($finishedEvents);

$rupiah = function($n){ return 'Rp ' . number_format((float)$n, 0, ',', '.'); };

// Helper untuk nama gelombang
$getWaveName = function($waveNum) {
  if ($waveNum === 1) return 'Gelombang 1 (Early Bird)';
  if ($waveNum === 2) return 'Gelombang 2 (Normal)';
  if ($waveNum === 3) return 'Gelombang 3 (Last Call)';
  return 'Gelombang ' . $waveNum;
};
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_audience') ?>
<?= $this->include('partials/alerts') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/events_index_audience.css'); ?>">

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
                    <span class="text-warning"><?= (int)$totalPending ?> segera</span>, 
                    <span class="text-muted"><?= (int)$totalClosed ?> ditutup</span>, 
                    <span class="text-secondary"><?= (int)$totalFinished ?> selesai</span>)
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
                  
                  // Get prices from current wave
                  $pOn        = null;
                  $pOff       = null;
                  $waveNum    = null;
                  
                  if (!empty($e['active_wave'])) {
                    $wave = $e['active_wave'];
                    $pOn  = (float)($wave['audience_fee_online'] ?? 0);
                    $pOff = (float)($wave['audience_fee_offline'] ?? 0);
                    $waveNum = $wave['wave_number'] ?? null;
                  }

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

                    <!-- Wave Badge (NEW) -->
                    <?php if ($waveNum !== null): ?>
                      <div class="wave-indicator wave-<?= (int)$waveNum ?>">
                        <i class="bi bi-lightning-charge-fill"></i>
                        <span><?= $getWaveName($waveNum) ?></span>
                      </div>
                    <?php endif; ?>

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
                      <div class="price-label">Harga Audience (Gelombang Aktif)</div>
                      <div class="price-badges">
                        <?php if ($onlineOK && $pOn > 0): ?>
                          <div class="price-badge price-online">
                            <i class="bi bi-wifi"></i>
                            <span><?= $rupiah($pOn) ?></span>
                          </div>
                        <?php endif; ?>
                        <?php if ($offlineOK && $pOff > 0): ?>
                          <div class="price-badge price-offline">
                            <i class="bi bi-geo-alt-fill"></i>
                            <span><?= $rupiah($pOff) ?></span>
                          </div>
                        <?php endif; ?>
                        <?php if ((!$onlineOK || $pOn <= 0) && (!$offlineOK || $pOff <= 0)): ?>
                          <div class="price-badge price-na">
                            <i class="bi bi-info-circle"></i>
                            <span>Belum tersedia</span>
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

        <!-- Segera Dibuka (Pending) -->
        <?php if (!empty($pendingEvents)): ?>
        <div class="modern-card mb-4">
          <div class="card-header-modern header-warning">
            <div class="header-left">
              <i class="bi bi-clock-fill"></i>
              <strong>Segera Dibuka</strong>
            </div>
            <span class="count-badge badge-warning"><?= (int)$totalPending ?></span>
          </div>
          <div class="card-body p-3 p-md-4">
            <div class="row g-4">
              <?php foreach ($pendingEvents as $e): ?>
                <?php
                  $fmtEvent   = strtolower($e['format'] ?? '');
                  $nextStart  = $e['next_wave_start'] ?? null;
                  
                  $regRaw        = $myRegs[$e['id']] ?? null;
                  $regStatus     = is_array($regRaw) ? ($regRaw['status'] ?? null) : $regRaw;
                  $isRegistered  = ($regStatus !== null && $regStatus !== 'batal');

                  $tgl = !empty($e['event_date']) ? date('d M Y', strtotime($e['event_date'])) : '-';
                  $jam = $e['event_time'] ?? '-';
                  $loc = $e['location'] ?? ($fmtEvent === 'online' ? 'Platform Online' : '-');
                ?>
                <div class="col-12 col-md-6 col-xl-4">
                  <div class="event-card-modern event-pending">
                    <!-- Header -->
                    <div class="event-header">
                      <div class="event-format-badge badge-warning">
                        <i class="bi bi-clock-fill"></i> Segera Dibuka
                      </div>
                      <?php if ($isRegistered): ?>
                        <div class="registered-badge">
                          <i class="bi bi-check-circle-fill"></i>
                        </div>
                      <?php endif; ?>
                    </div>

                    <!-- Title -->
                    <h5 class="event-title"><?= esc($e['title'] ?? 'Event') ?></h5>

                    <!-- Countdown -->
                    <?php if ($nextStart): ?>
                      <div class="countdown-badge">
                        <i class="bi bi-alarm-fill"></i>
                        <span>Dibuka: <?= date('d M Y, H:i', strtotime($nextStart)) ?> WIB</span>
                      </div>
                    <?php endif; ?>

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
                      <button class="btn-event btn-disabled" disabled>
                        <i class="bi bi-clock"></i>
                        Belum Dibuka
                      </button>
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
                  
                  // Get prices from current wave (even if closed, may have active wave for display)
                  $pOn        = null;
                  $pOff       = null;
                  
                  if (!empty($e['active_wave'])) {
                    $wave = $e['active_wave'];
                    $pOn  = (float)($wave['audience_fee_online'] ?? 0);
                    $pOff = (float)($wave['audience_fee_offline'] ?? 0);
                  }

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