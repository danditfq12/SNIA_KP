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
<link rel="stylesheet" href="<?= base_url('assets/css/absensi_index_audience.css'); ?>">

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
