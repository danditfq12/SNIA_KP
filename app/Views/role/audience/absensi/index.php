<?php
  $title = 'Absensi - Audience';
  /** $yourEvents, $history */
  $yourEvents = $yourEvents ?? [];
  $history    = $history ?? [];

  $fmtDate = fn($s)=> $s ? date('d M Y', strtotime($s)) : '-';
  $fmtDT   = fn($s)=> $s ? date('d M Y H:i', strtotime($s)) : '-';
  $isToday = fn($d)=> !empty($d) && date('Y-m-d', strtotime($d)) === date('Y-m-d');
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_audience') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <!-- HEADER -->
      <div class="absensi-header mb-4">
        <div class="header-content-absensi">
          <div class="header-left-absensi">
            <div class="header-icon-absensi">
              <i class="bi bi-clipboard-check"></i>
            </div>
            <div>
              <h2 class="header-title-absensi">Absensi Event</h2>
              <p class="header-subtitle-absensi">Kelola kehadiran event yang sudah terdaftar</p>
            </div>
          </div>
          <div class="header-date-absensi">
            <i class="bi bi-calendar-day"></i>
            <span><?= date('d M Y') ?></span>
          </div>
        </div>
      </div>

      <!-- EVENT TERSEDIA -->
      <div class="content-section-absensi mb-4">
        <div class="section-header-absensi">
          <div class="section-title-absensi">
            <i class="bi bi-calendar-event"></i>
            <span>Event Tersedia</span>
          </div>
          <span class="count-badge-absensi"><?= count($yourEvents) ?></span>
        </div>
        <div class="section-body-absensi">
          <?php if (!empty($yourEvents)): ?>
            <div class="row g-3">
              <?php foreach ($yourEvents as $e): ?>
                <?php
                  $tgl   = $fmtDate($e['event_date'] ?? null);
                  $jam   = $e['event_time'] ?? '-';
                  $today = $isToday($e['event_date'] ?? null);
                  $already      = (bool)($e['already_attend'] ?? false);
                  $attendanceAt = $e['attendance_at'] ?? ($e['waktu_scan'] ?? null);
                  $canScan = !$already && !empty($e['can_scan']);
                ?>
                <div class="col-12 col-md-6 col-lg-4">
                  <div class="event-card-absensi <?= $already ? 'event-attended' : '' ?>">
                    <?php if ($today): ?>
                      <div class="event-badge-today">
                        <i class="bi bi-star-fill"></i>
                        <span>Hari Ini</span>
                      </div>
                    <?php endif; ?>
                    
                    <div class="event-card-header-absensi">
                      <h5 class="event-title-absensi"><?= esc($e['title']) ?></h5>
                      <div class="event-status-group">
                        <?php if (!empty($e['event_status'])): ?>
                          <span class="status-badge-absensi status-<?= esc($e['badge_class'] ?? 'secondary') ?>">
                            <?= esc($e['event_status']) ?>
                          </span>
                        <?php endif; ?>
                        <?php if ($already): ?>
                          <span class="status-badge-absensi status-success">
                            <i class="bi bi-check-circle-fill"></i>
                            Hadir
                          </span>
                        <?php endif; ?>
                      </div>
                    </div>

                    <div class="event-info-grid">
                      <div class="event-info-item">
                        <i class="bi bi-calendar3"></i>
                        <span><?= esc($tgl) ?></span>
                      </div>
                      <div class="event-info-item">
                        <i class="bi bi-clock"></i>
                        <span><?= esc($jam) ?></span>
                      </div>
                      <?php if (!empty($e['location'])): ?>
                      <div class="event-info-item">
                        <i class="bi bi-geo-alt"></i>
                        <span><?= esc($e['location']) ?></span>
                      </div>
                      <?php endif; ?>
                      <?php if (!empty($e['participation_type'])): ?>
                      <div class="event-info-item">
                        <i class="bi bi-diagram-3"></i>
                        <span><?= esc(strtoupper($e['participation_type'])) ?></span>
                      </div>
                      <?php endif; ?>
                    </div>

                    <?php if (!empty($e['participation_type']) && strtolower($e['participation_type'])==='online' && !empty($e['zoom_link'])): ?>
                    <a href="<?= esc($e['zoom_link']) ?>" target="_blank" class="zoom-link-absensi">
                      <i class="bi bi-camera-video"></i>
                      <span>Buka Link Meeting</span>
                    </a>
                    <?php endif; ?>

                    <div class="event-card-footer">
                      <?php if ($already): ?>
                        <button class="btn-event-absensi btn-attended" disabled>
                          <i class="bi bi-check-circle-fill"></i>
                          <span>Sudah Absen</span>
                        </button>
                        <?php if ($attendanceAt): ?>
                        <div class="event-note-absensi">
                          Tercatat pada <?= esc($fmtDT($attendanceAt)) ?>
                        </div>
                        <?php endif; ?>
                      <?php else: ?>
                        <a href="<?= site_url('audience/absensi/event/'.$e['id']) ?>" 
                           class="btn-event-absensi btn-primary-absensi">
                          <i class="bi bi-clipboard-check"></i>
                          <span><?= $canScan ? 'Absen Sekarang' : 'Detail Event' ?></span>
                        </a>
                        <div class="event-note-absensi">
                          <?= $canScan ? 'Siap untuk absensi' : 'Absen dibuka saat event dimulai' ?>
                        </div>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="empty-state-absensi">
              <div class="empty-icon-absensi">
                <i class="bi bi-calendar-event"></i>
              </div>
              <h5 class="empty-title-absensi">Belum Ada Event</h5>
              <p class="empty-text-absensi">Event akan muncul setelah pembayaran terverifikasi</p>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- RIWAYAT ABSENSI -->
      <div class="content-section-absensi">
        <div class="section-header-absensi">
          <div class="section-title-absensi">
            <i class="bi bi-clock-history"></i>
            <span>Riwayat Absensi</span>
          </div>
          <span class="count-badge-absensi count-secondary"><?= count($history) ?></span>
        </div>
        <div class="section-body-absensi">
          <?php if (!empty($history)): ?>
            <div class="timeline-absensi">
              <?php foreach ($history as $h): ?>
                <div class="timeline-item-absensi">
                  <div class="timeline-dot-absensi"></div>
                  <div class="timeline-content-absensi">
                    <div class="timeline-header-absensi">
                      <h6 class="timeline-title-absensi"><?= esc($h['event_title'] ?? 'Event') ?></h6>
                      <span class="timeline-badge-absensi">
                        <i class="bi bi-check-circle-fill"></i>
                        <?= esc(ucfirst($h['status'] ?? 'hadir')) ?>
                      </span>
                    </div>
                    <div class="timeline-info-absensi">
                      <?php if (!empty($h['waktu_scan'])): ?>
                        <i class="bi bi-clock"></i>
                        <span>Absen: <?= esc($fmtDT($h['waktu_scan'])) ?></span>
                      <?php else: ?>
                        <i class="bi bi-calendar"></i>
                        <span>Jadwal: <?= esc($fmtDate($h['event_date'] ?? null)) ?> · <?= esc($h['event_time'] ?? '-') ?></span>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="empty-state-absensi">
              <div class="empty-icon-absensi">
                <i class="bi bi-clock-history"></i>
              </div>
              <h5 class="empty-title-absensi">Belum Ada Riwayat</h5>
              <p class="empty-text-absensi">Kehadiran akan terekam setelah melakukan absensi</p>
            </div>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<style>
  :root {
    --primary-blue: #2563eb;
    --light-blue: #eff6ff;
    --medium-blue: #dbeafe;
    --dark-blue: #1e40af;
    --success: #10b981;
    --warning: #f59e0b;
    --danger: #ef4444;
    --info: #06b6d4;
    --white: #ffffff;
    --gray-50: #f9fafb;
    --gray-100: #f3f4f6;
    --gray-200: #e5e7eb;
    --gray-400: #9ca3af;
    --gray-600: #4b5563;
    --gray-900: #111827;
  }

  body {
    background: var(--light-blue);
    font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
  }

  /* HEADER */
  .absensi-header {
    background: var(--white);
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  }
  .header-content-absensi {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
    flex-wrap: wrap;
  }
  .header-left-absensi {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex: 1;
  }
  .header-icon-absensi {
    width: 48px;
    height: 48px;
    background: var(--primary-blue);
    border-radius: 10px;
    display: grid;
    place-items: center;
    font-size: 1.5rem;
    color: var(--white);
    flex-shrink: 0;
  }
  .header-title-absensi {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--gray-900);
    margin: 0 0 0.25rem 0;
  }
  .header-subtitle-absensi {
    color: var(--gray-600);
    margin: 0;
    font-size: 0.875rem;
  }
  .header-date-absensi {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    background: var(--medium-blue);
    padding: 0.5rem 1rem;
    border-radius: 8px;
    color: var(--dark-blue);
    font-weight: 600;
    font-size: 0.875rem;
  }

  /* CONTENT SECTION */
  .content-section-absensi {
    background: var(--white);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  }
  .section-header-absensi {
    background: var(--gray-50);
    padding: 1rem 1.25rem;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--gray-200);
  }
  .section-title-absensi {
    display: flex;
    align-items: center;
    gap: 0.625rem;
    font-weight: 600;
    color: var(--gray-900);
    font-size: 1rem;
  }
  .section-title-absensi i {
    font-size: 1.125rem;
    color: var(--primary-blue);
  }
  .count-badge-absensi {
    background: var(--primary-blue);
    color: var(--white);
    padding: 0.25rem 0.75rem;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.813rem;
  }
  .count-badge-absensi.count-secondary {
    background: var(--gray-400);
  }
  .section-body-absensi {
    padding: 1.25rem;
  }

  /* EVENT CARD */
  .event-card-absensi {
    background: var(--white);
    border: 1px solid var(--gray-200);
    border-radius: 10px;
    padding: 1.25rem;
    height: 100%;
    display: flex;
    flex-direction: column;
    position: relative;
    overflow: hidden;
  }
  .event-card-absensi.event-attended {
    background: #f0fdf4;
    border-color: #86efac;
  }
  .event-badge-today {
    position: absolute;
    top: 0;
    right: 0;
    background: var(--warning);
    color: var(--white);
    padding: 0.375rem 0.75rem;
    border-bottom-left-radius: 10px;
    font-size: 0.75rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.313rem;
  }
  .event-card-header-absensi {
    margin-bottom: 1rem;
  }
  .event-title-absensi {
    font-size: 1rem;
    font-weight: 700;
    color: var(--gray-900);
    margin: 0 0 0.75rem 0;
    line-height: 1.3;
  }
  .event-status-group {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
  }
  .status-badge-absensi {
    display: inline-flex;
    align-items: center;
    gap: 0.313rem;
    padding: 0.313rem 0.625rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
  }
  .status-badge-absensi.status-bg-primary {
    background: var(--primary-blue);
    color: var(--white);
  }
  .status-badge-absensi.status-bg-success {
    background: var(--success);
    color: var(--white);
  }
  .status-badge-absensi.status-success {
    background: #d1fae5;
    color: #065f46;
  }
  .event-info-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 0.75rem;
    margin-bottom: 1rem;
  }
  .event-info-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.813rem;
    color: var(--gray-600);
  }
  .event-info-item i {
    color: var(--primary-blue);
    font-size: 0.938rem;
  }
  .zoom-link-absensi {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 0.875rem;
    background: var(--medium-blue);
    color: var(--dark-blue);
    border-radius: 8px;
    text-decoration: none;
    font-size: 0.813rem;
    font-weight: 600;
    margin-bottom: 1rem;
  }
  .zoom-link-absensi:hover {
    background: #bfdbfe;
  }
  .event-card-footer {
    margin-top: auto;
  }
  .btn-event-absensi {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    width: 100%;
    padding: 0.625rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.875rem;
    text-decoration: none;
    border: none;
    cursor: pointer;
  }
  .btn-primary-absensi {
    background: var(--primary-blue);
    color: var(--white);
  }
  .btn-attended {
    background: #d1fae5;
    color: #065f46;
    cursor: not-allowed;
  }
  .event-note-absensi {
    text-align: center;
    font-size: 0.75rem;
    color: var(--gray-600);
    margin-top: 0.5rem;
  }

  /* TIMELINE */
  .timeline-absensi {
    position: relative;
  }
  .timeline-item-absensi {
    position: relative;
    padding-left: 2rem;
    margin-bottom: 1.5rem;
  }
  .timeline-item-absensi:last-child {
    margin-bottom: 0;
  }
  .timeline-item-absensi::before {
    content: '';
    position: absolute;
    left: 0.5rem;
    top: 1.5rem;
    bottom: -1rem;
    width: 2px;
    background: var(--gray-200);
  }
  .timeline-item-absensi:last-child::before {
    display: none;
  }
  .timeline-dot-absensi {
    position: absolute;
    left: 0;
    top: 0.375rem;
    width: 12px;
    height: 12px;
    background: var(--primary-blue);
    border-radius: 50%;
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.15);
  }
  .timeline-content-absensi {
    background: var(--gray-50);
    border-radius: 8px;
    padding: 1rem;
  }
  .timeline-header-absensi {
    display: flex;
    justify-content: space-between;
    align-items: start;
    gap: 0.75rem;
    margin-bottom: 0.5rem;
  }
  .timeline-title-absensi {
    font-weight: 600;
    color: var(--gray-900);
    font-size: 0.938rem;
    margin: 0;
    flex: 1;
  }
  .timeline-badge-absensi {
    background: #d1fae5;
    color: #065f46;
    padding: 0.25rem 0.625rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 0.313rem;
    white-space: nowrap;
  }
  .timeline-info-absensi {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 0.813rem;
    color: var(--gray-600);
  }
  .timeline-info-absensi i {
    color: var(--primary-blue);
  }

  /* EMPTY STATE */
  .empty-state-absensi {
    text-align: center;
    padding: 3rem 2rem;
  }
  .empty-icon-absensi {
    width: 80px;
    height: 80px;
    background: var(--medium-blue);
    border-radius: 50%;
    display: grid;
    place-items: center;
    margin: 0 auto 1rem;
    font-size: 2rem;
    color: var(--primary-blue);
  }
  .empty-title-absensi {
    font-weight: 700;
    color: var(--gray-900);
    margin-bottom: 0.5rem;
    font-size: 1.125rem;
  }
  .empty-text-absensi {
    color: var(--gray-600);
    margin: 0;
    font-size: 0.875rem;
  }

  /* RESPONSIVE */
  @media (max-width: 768px) {
    .absensi-header {
      padding: 1.25rem;
    }
    .header-left-absensi {
      flex-direction: column;
      align-items: flex-start;
    }
    .header-icon-absensi {
      width: 40px;
      height: 40px;
      font-size: 1.25rem;
    }
    .header-title-absensi {
      font-size: 1.25rem;
    }
    .header-date-absensi {
      width: 100%;
      justify-content: center;
    }
    
    .section-body-absensi {
      padding: 1rem;
    }
    
    .event-info-grid {
      grid-template-columns: 1fr;
      gap: 0.5rem;
    }
  }

  @media (max-width: 576px) {
    .absensi-header {
      padding: 1rem;
    }
    .header-icon-absensi {
      width: 36px;
      height: 36px;
      font-size: 1.125rem;
    }
    .header-title-absensi {
      font-size: 1.125rem;
    }
    
    .empty-state-absensi {
      padding: 2.5rem 1.5rem;
    }
    .empty-icon-absensi {
      width: 70px;
      height: 70px;
      font-size: 1.75rem;
    }
  }
</style>