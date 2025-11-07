<?php
$stats           = $stats           ?? ['total_events'=>0,'total_certs'=>0];
$todayAbsensi    = $todayAbsensi    ?? [];
$registrations   = $registrations   ?? [];
$progressEvents  = $progressEvents  ?? [];
$todaySchedule   = $todaySchedule   ?? [];
$activities      = $activities      ?? [];
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_audience') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <!-- HEADER -->
      <div class="dashboard-header mb-4">
        <div class="header-content">
          <div class="header-left">
            <div class="header-icon">
              <i class="bi bi-grid-1x2"></i>
            </div>
            <div>
              <h1 class="header-title">Dashboard Audience</h1>
              <p class="header-subtitle">Kelola event dan aktivitas Anda</p>
            </div>
          </div>
          <div class="header-right">
            <div class="date-box">
              <div class="date-day"><?= date('d') ?></div>
              <div class="date-month"><?= date('M Y') ?></div>
            </div>
          </div>
        </div>
      </div>

      <!-- PROGRESS EVENT -->
      <div class="card-modern mb-4">
        <div class="card-header">
          <div class="card-header-left">
            <div class="header-icon-small bg-blue">
              <i class="bi bi-bar-chart-line"></i>
            </div>
            <div>
              <h3 class="card-title">Progress Event</h3>
              <p class="card-subtitle">Pantau tahapan event Anda</p>
            </div>
          </div>
        </div>
        <div class="card-body">
          <?php if (empty($progressEvents)): ?>
            <div class="empty-state">
              <i class="bi bi-check-circle-fill"></i>
              <p>Tidak ada progres yang perlu dilengkapi</p>
              <small>Semua event telah selesai</small>
            </div>
          <?php else: ?>
            <div class="progress-grid">
              <?php foreach ($progressEvents as $p): ?>
                <?php
                  $title     = (string)($p['title'] ?? '-');
                  $eventId   = (int)($p['event_id'] ?? 0);
                  $dateLabel = !empty($p['event_date']) ? date('d F Y', strtotime($p['event_date'])) : '';

                  $s1 = $p['steps']['daftar']     ?? 'todo';
                  $s2 = $p['steps']['bayar']      ?? 'todo';
                  $s3 = $p['steps']['verifikasi'] ?? 'todo';
                  $s4 = ($s3 === 'done') ? 'done' : 'todo';

                  $doneCount = 0; 
                  foreach ([$s1,$s2,$s3,$s4] as $s) if ($s==='done') $doneCount++;

                  $paySt = strtolower($p['labels']['pay_status'] ?? '');
                  $alertCls = 'alert-default';
                  $alertMsg = 'Lengkapi tahapan di atas untuk mengakses fitur event.';
                  $ctaHref  = "/audience/events/detail/{$eventId}";
                  $ctaText  = 'Lihat Detail Event';
                  $alertIcon = 'info-circle';

                  if ($paySt === 'verified') {
                      $alertCls = 'alert-success';
                      $alertMsg = 'Pembayaran terverifikasi: Semua fitur event tersedia.';
                      $ctaHref  = "/audience/absensi/event/{$eventId}";
                      $ctaText  = 'Akses Event';
                      $alertIcon = 'check-circle';
                  } elseif ($paySt === 'pending') {
                      $alertCls = 'alert-info';
                      $alertMsg = 'Pembayaran menunggu verifikasi.';
                      $ctaHref  = "/audience/pembayaran";
                      $ctaText  = 'Lihat Pembayaran';
                      $alertIcon = 'clock';
                  } elseif ($s2 !== 'done') {
                      $alertCls = 'alert-primary';
                      $alertMsg = 'Silakan lakukan pembayaran untuk melanjutkan.';
                      $ctaHref  = "/audience/pembayaran";
                      $ctaText  = 'Bayar Sekarang';
                      $alertIcon = 'credit-card';
                  }
                ?>

                <div class="progress-card">
                  <div class="progress-card-header">
                    <h4 class="progress-title"><?= esc($title) ?></h4>
                    <span class="progress-indicator"><?= $doneCount ?>/4</span>
                  </div>
                  
                  <?php if ($dateLabel): ?>
                    <div class="progress-date">
                      <i class="bi bi-calendar3"></i>
                      <?= esc($dateLabel) ?>
                    </div>
                  <?php endif; ?>

                  <!-- STEPPER -->
                  <div class="stepper">
                    <div class="step <?= $s1 ?>">
                      <div class="step-icon">
                        <?php if ($s1 === 'done'): ?>
                          <i class="bi bi-check-lg"></i>
                        <?php else: ?>
                          <span class="step-number">1</span>
                        <?php endif; ?>
                      </div>
                      <span class="step-text">Daftar</span>
                    </div>

                    <div class="step-line <?= ($s2==='done' || $s2==='current') ? 'active':'' ?>"></div>

                    <div class="step <?= $s2 ?>">
                      <div class="step-icon">
                        <?php if ($s2 === 'done'): ?>
                          <i class="bi bi-check-lg"></i>
                        <?php else: ?>
                          <span class="step-number">2</span>
                        <?php endif; ?>
                      </div>
                      <span class="step-text">Bayar</span>
                    </div>

                    <div class="step-line <?= ($s3==='done' || $s3==='current') ? 'active':'' ?>"></div>

                    <div class="step <?= $s3 ?>">
                      <div class="step-icon">
                        <?php if ($s3 === 'done'): ?>
                          <i class="bi bi-check-lg"></i>
                        <?php else: ?>
                          <span class="step-number">3</span>
                        <?php endif; ?>
                      </div>
                      <span class="step-text">Verifikasi</span>
                    </div>

                    <div class="step-line <?= ($s4==='done') ? 'active':'' ?>"></div>

                    <div class="step <?= $s4 ?>">
                      <div class="step-icon">
                        <?php if ($s4 === 'done'): ?>
                          <i class="bi bi-check-lg"></i>
                        <?php else: ?>
                          <span class="step-number">4</span>
                        <?php endif; ?>
                      </div>
                      <span class="step-text">Selesai</span>
                    </div>
                  </div>

                  <!-- ALERT & CTA -->
                  <div class="alert-box <?= $alertCls ?>">
                    <i class="bi bi-<?= $alertIcon ?>-fill"></i>
                    <span><?= $alertMsg ?></span>
                  </div>
                  
                  <a href="<?= esc($ctaHref) ?>" class="btn-primary">
                    <?= esc($ctaText) ?>
                    <i class="bi bi-arrow-right"></i>
                  </a>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- GRID: Jadwal & Aktivitas -->
      <div class="row g-4">
        <!-- Jadwal -->
        <div class="col-12 col-lg-5">
          <div class="card-modern h-100">
            <div class="card-header">
              <div class="card-header-left">
                <div class="header-icon-small bg-blue">
                  <i class="bi bi-calendar-check"></i>
                </div>
                <div>
                  <h3 class="card-title">Jadwal Hari Ini</h3>
                </div>
              </div>
            </div>
            <div class="card-body">
              <?php if (empty($todaySchedule)): ?>
                <div class="empty-state-small">
                  <i class="bi bi-calendar-x"></i>
                  <p>Tidak ada jadwal hari ini</p>
                </div>
              <?php else: ?>
                <div class="list-items">
                  <?php foreach ($todaySchedule as $s): ?>
                    <div class="schedule-item">
                      <div class="schedule-info">
                        <h5 class="schedule-name"><?= esc($s['title']) ?></h5>
                        <div class="schedule-details">
                          <span><i class="bi bi-clock"></i> <?= esc($s['start'] ?: '-') ?></span>
                          <span><i class="bi bi-geo-alt"></i> <?= esc($s['where'] ?: '-') ?></span>
                        </div>
                      </div>
                      <a href="<?= esc($s['link']) ?>" class="btn-small">Absen</a>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Aktivitas -->
        <div class="col-12 col-lg-7">
          <div class="card-modern h-100">
            <div class="card-header">
              <div class="card-header-left">
                <div class="header-icon-small bg-blue">
                  <i class="bi bi-bell"></i>
                </div>
                <div>
                  <h3 class="card-title">Aktivitas Terbaru</h3>
                </div>
              </div>
            </div>
            <div class="card-body">
              <?php if (empty($activities)): ?>
                <div class="empty-state-small">
                  <i class="bi bi-inbox"></i>
                  <p>Belum ada aktivitas</p>
                </div>
              <?php else: ?>
                <div class="list-items">
                  <?php foreach ($activities as $a): ?>
                    <div class="activity-item">
                      <div class="activity-icon bg-<?= esc($a['badge']) ?>">
                        <i class="bi <?= esc($a['icon']) ?>"></i>
                      </div>
                      <div class="activity-content">
                        <div class="activity-header">
                          <span class="activity-name"><?= esc($a['title']) ?></span>
                          <span class="activity-time"><?= $a['time'] ? date('d M H:i', (int)$a['time']) : '' ?></span>
                        </div>
                        <?php if (!empty($a['desc'])): ?>
                          <p class="activity-desc"><?= esc($a['desc']) ?></p>
                        <?php endif; ?>
                        <?php if (!empty($a['link'])): ?>
                          <a class="activity-link" href="<?= esc($a['link']) ?>">Lihat detail →</a>
                        <?php endif; ?>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Event Terdaftar -->
        <div class="col-12">
          <div class="card-modern">
            <div class="card-header">
              <div class="card-header-left">
                <div class="header-icon-small bg-blue">
                  <i class="bi bi-bookmark-check"></i>
                </div>
                <div>
                  <h3 class="card-title">Event Terdaftar</h3>
                </div>
              </div>
            </div>
            <div class="card-body">
              <?php if (empty($registrations)): ?>
                <div class="empty-state-small">
                  <i class="bi bi-calendar-plus"></i>
                  <p>Belum ada event terdaftar</p>
                </div>
              <?php else: ?>
                <div class="list-items">
                  <?php foreach ($registrations as $r): ?>
                    <?php
                      $dateStr = !empty($r['event_date'])
                        ? date('d M Y', strtotime($r['event_date'])) . (!empty($r['event_time']) ? ' · ' . $r['event_time'] : '')
                        : '-';
                      $badge = $r['badge'] ?? 'secondary';
                    ?>
                    <div class="registration-item">
                      <div class="registration-icon">
                        <i class="bi bi-calendar-event"></i>
                      </div>
                      <div class="registration-info">
                        <h5 class="registration-name"><?= esc($r['event_title'] ?? ($r['title'] ?? '-')) ?></h5>
                        <p class="registration-date"><?= esc($dateStr) ?></p>
                      </div>
                      <span class="badge badge-<?= esc($badge) ?>"><?= esc($r['status'] ?? '-') ?></span>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
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
    --primary-blue: #1d4ed8;
    --light-blue: #2563eb;
    --lighter-blue: #3b82f6;
    --pale-blue: #dbeafe;
    --bg-blue: #eff6ff;
    --dark-blue: #1e3a8a;
    --text-dark: #1e293b;
    --text-gray: #64748b;
    --text-light: #94a3b8;
    --white: #ffffff;
    --success: #10b981;
    --info: #0ea5e9;
    --warning: #f59e0b;
    --danger: #ef4444;
    --border: #e2e8f0;
    --shadow: rgba(15, 23, 42, 0.08);
  }

  body { 
    background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%); 
    min-height: 100vh; 
  }

  /* === HEADER === */
  .dashboard-header {
    background: linear-gradient(135deg, #2563eb 0%, #1d4ed8 100%);
    border-radius: 16px;
    padding: 2rem;
    box-shadow: 0 4px 6px rgba(37, 99, 235, 0.15);
    border: none;
  }

  .header-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 2rem;
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

  .header-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--white);
    margin: 0 0 0.25rem 0;
  }

  .header-subtitle {
    color: rgba(255, 255, 255, 0.9);
    margin: 0;
    font-size: 0.95rem;
  }

  .header-right {
    display: flex;
    align-items: center;
  }

  .date-box {
    text-align: center;
    background: rgba(255, 255, 255, 0.15);
    border-radius: 12px;
    padding: 0.75rem 1.25rem;
    border: 1px solid rgba(255, 255, 255, 0.2);
  }

  .date-day {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--white);
    line-height: 1;
  }

  .date-month {
    font-size: 0.75rem;
    color: rgba(255, 255, 255, 0.8);
    margin-top: 0.25rem;
    text-transform: uppercase;
    font-weight: 600;
  }

  /* === CARD === */
  .card-modern {
    background: var(--white);
    border-radius: 16px;
    box-shadow: 0 1px 3px var(--shadow);
    border: 1px solid var(--border);
  }

  .card-header {
    padding: 1.5rem;
    border-bottom: 1px solid var(--border);
  }

  .card-header-left {
    display: flex;
    align-items: center;
    gap: 1rem;
  }

  .header-icon-small {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--white);
    font-size: 1.1rem;
  }

  .header-icon-small.bg-blue {
    background: #2563eb;
  }

  .card-title {
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--text-dark);
    margin: 0;
  }

  .card-subtitle {
    font-size: 0.875rem;
    color: var(--text-gray);
    margin: 0.25rem 0 0 0;
  }

  .card-body {
    padding: 1.5rem;
  }

  /* === PROGRESS CARD === */
  .progress-grid {
    display: grid;
    gap: 1.5rem;
  }

  .progress-card {
    background: var(--bg-blue);
    border: 1px solid var(--pale-blue);
    border-radius: 12px;
    padding: 1.5rem;
  }

  .progress-card-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.75rem;
  }

  .progress-title {
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--text-dark);
    margin: 0;
  }

  .progress-indicator {
    background: var(--primary-blue);
    color: var(--white);
    padding: 0.375rem 0.875rem;
    border-radius: 20px;
    font-weight: 700;
    font-size: 0.875rem;
  }

  .progress-date {
    color: var(--text-gray);
    font-size: 0.875rem;
    margin-bottom: 1.25rem;
  }

  .progress-date i {
    color: var(--primary-blue);
    margin-right: 0.375rem;
  }

  /* === STEPPER === */
  .stepper {
    display: grid;
    grid-template-columns: auto 1fr auto 1fr auto 1fr auto;
    align-items: center;
    gap: 0.75rem;
    margin: 1.5rem 0;
  }

  .step {
    text-align: center;
  }

  .step-icon {
    width: 44px;
    height: 44px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 0.5rem;
    font-size: 1rem;
    background: var(--white);
    border: 2px solid var(--border);
    color: var(--text-light);
    font-weight: 700;
  }

  .step.done .step-icon {
    background: var(--success);
    border-color: var(--success);
    color: var(--white);
  }

  .step.current .step-icon {
    background: var(--primary-blue);
    border-color: var(--primary-blue);
    color: var(--white);
  }

  .step-number {
    font-size: 0.875rem;
  }

  .step-text {
    font-size: 0.8rem;
    color: var(--text-gray);
    font-weight: 600;
    display: block;
  }

  .step-line {
    height: 2px;
    background: var(--border);
  }

  .step-line.active {
    background: var(--success);
  }

  /* === ALERT === */
  .alert-box {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    border-radius: 10px;
    margin: 1.5rem 0 1rem 0;
    font-size: 0.875rem;
    border: 1px solid;
  }

  .alert-box i {
    font-size: 1.25rem;
    flex-shrink: 0;
  }

  .alert-success {
    background: #d1fae5;
    color: #065f46;
    border-color: #a7f3d0;
  }

  .alert-primary {
    background: var(--pale-blue);
    color: var(--dark-blue);
    border-color: #93c5fd;
  }

  .alert-info {
    background: #e0f2fe;
    color: #075985;
    border-color: #bae6fd;
  }

  .alert-default {
    background: #f1f5f9;
    color: var(--text-gray);
    border-color: var(--border);
  }

  /* === BUTTONS === */
  .btn-primary {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    background: var(--primary-blue);
    color: var(--white);
    padding: 0.75rem 1.5rem;
    border-radius: 10px;
    font-weight: 600;
    text-decoration: none;
    font-size: 0.9375rem;
    width: 100%;
  }

  .btn-primary:hover {
    background: var(--dark-blue);
    color: var(--white);
  }

  .btn-small {
    background: var(--primary-blue);
    color: var(--white);
    padding: 0.5rem 1rem;
    border-radius: 8px;
    text-decoration: none;
    font-weight: 600;
    font-size: 0.875rem;
  }

  .btn-small:hover {
    background: var(--dark-blue);
    color: var(--white);
  }

  /* === LIST ITEMS === */
  .list-items {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }

  /* Schedule */
  .schedule-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 1rem;
    background: var(--bg-blue);
    border-radius: 10px;
    border: 1px solid var(--pale-blue);
  }

  .schedule-name {
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--text-dark);
    margin: 0 0 0.375rem 0;
  }

  .schedule-details {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    font-size: 0.8125rem;
    color: var(--text-gray);
  }

  .schedule-details i {
    color: var(--primary-blue);
    margin-right: 0.25rem;
  }

  /* Activity */
  .activity-item {
    display: flex;
    gap: 1rem;
    padding: 0.75rem;
  }

  .activity-icon {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--white);
    flex-shrink: 0;
    font-size: 1.125rem;
  }

  .activity-icon.bg-primary { background: var(--primary-blue); }
  .activity-icon.bg-success { background: var(--success); }
  .activity-icon.bg-info { background: var(--info); }
  .activity-icon.bg-warning { background: var(--warning); }
  .activity-icon.bg-danger { background: var(--danger); }

  .activity-content {
    flex: 1;
  }

  .activity-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 0.25rem;
  }

  .activity-name {
    font-weight: 600;
    color: var(--text-dark);
    font-size: 0.9375rem;
  }

  .activity-time {
    font-size: 0.8125rem;
    color: var(--text-light);
  }

  .activity-desc {
    color: var(--text-gray);
    font-size: 0.875rem;
    margin: 0 0 0.5rem 0;
  }

  .activity-link {
    color: var(--primary-blue);
    font-size: 0.875rem;
    text-decoration: none;
    font-weight: 600;
  }

  .activity-link:hover {
    text-decoration: underline;
  }

  /* Registration */
  .registration-item {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: var(--bg-blue);
    border-radius: 10px;
    border: 1px solid var(--pale-blue);
  }

  .registration-icon {
    width: 48px;
    height: 48px;
    background: #2563eb;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: var(--white);
    font-size: 1.25rem;
    flex-shrink: 0;
  }

  .registration-info {
    flex: 1;
  }

  .registration-name {
    font-size: 0.9375rem;
    font-weight: 600;
    color: var(--text-dark);
    margin: 0 0 0.25rem 0;
  }

  .registration-date {
    font-size: 0.8125rem;
    color: var(--text-gray);
    margin: 0;
  }

  .badge {
    padding: 0.375rem 0.875rem;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.8125rem;
  }

  .badge-primary { background: var(--pale-blue); color: var(--dark-blue); }
  .badge-success { background: #d1fae5; color: #065f46; }
  .badge-warning { background: #fef3c7; color: #92400e; }
  .badge-info { background: #e0f2fe; color: #075985; }
  .badge-secondary { background: #f1f5f9; color: #475569; }

  /* === EMPTY STATE === */
  .empty-state, .empty-state-small {
    text-align: center;
    padding: 3rem 1rem;
    color: var(--text-light);
  }

  .empty-state-small {
    padding: 2rem 1rem;
  }

  .empty-state i, .empty-state-small i {
    font-size: 3rem;
    color: var(--text-light);
    display: block;
    margin-bottom: 1rem;
  }

  .empty-state p, .empty-state-small p {
    margin: 0.5rem 0 0 0;
    font-weight: 600;
    color: var(--text-gray);
  }

  .empty-state small {
    color: var(--text-light);
    font-size: 0.875rem;
  }

  /* === RESPONSIVE === */
  @media (max-width: 768px) {
    .dashboard-header {
      padding: 1.5rem;
    }

    .header-content {
      flex-direction: column;
      gap: 1rem;
    }

    .header-left {
      width: 100%;
    }

    .header-icon {
      width: 48px;
      height: 48px;
      font-size: 1.25rem;
    }

    .header-title {
      font-size: 1.5rem;
    }

    .date-box {
      width: 100%;
    }

    .stepper {
      gap: 0.5rem;
    }

    .step-icon {
      width: 36px;
      height: 36px;
      font-size: 0.875rem;
    }

    .step-text {
      font-size: 0.7rem;
    }

    .step-line {
      min-width: 20px;
    }

    .progress-card {
      padding: 1.25rem;
    }

    .progress-title {
      font-size: 1rem;
    }

    .progress-indicator {
      padding: 0.3rem 0.7rem;
      font-size: 0.8125rem;
    }

    .card-title {
      font-size: 1rem;
    }

    .schedule-item,
    .registration-item {
      flex-direction: column;
      align-items: flex-start;
      gap: 0.75rem;
    }

    .btn-small {
      width: 100%;
      text-align: center;
    }

    .badge {
      align-self: flex-start;
    }
  }

  /* === SCROLLBAR === */
  .list-items {
    max-height: 400px;
    overflow-y: auto;
  }

  .list-items::-webkit-scrollbar {
    width: 6px;
  }

  .list-items::-webkit-scrollbar-track {
    background: var(--bg-blue);
    border-radius: 10px;
  }

  .list-items::-webkit-scrollbar-thumb {
    background: var(--pale-blue);
    border-radius: 10px;
  }

  .list-items::-webkit-scrollbar-thumb:hover {
    background: var(--lighter-blue);
  }
</style>