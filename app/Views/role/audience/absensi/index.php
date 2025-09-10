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

      <!-- HERO -->
      <div class="abs-hero mb-3">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
          <div>
            <div class="abs-title mb-1">Absensi</div>
            <div class="abs-sub">Event yang sudah kamu bayar akan tampil di sini untuk absen.</div>
          </div>
          <div class="text-end">
            <span class="badge bg-light text-primary-emphasis">Hari ini: <?= date('d M Y') ?></span>
          </div>
        </div>
      </div>

      <div class="row g-3">
        <!-- Event Tersedia -->
        <div class="col-12">
          <div class="card shadow-sm border-0">
            <div class="card-body">
              <div class="d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">Event Tersedia</h5>
                <span class="badge bg-primary-subtle text-primary"><?= count($yourEvents) ?></span>
              </div>
              <hr class="my-3">

              <?php if (!empty($yourEvents)): ?>
                <div class="row g-2 g-md-3">
                  <?php foreach ($yourEvents as $e): ?>
                    <?php
                      $tgl   = $fmtDate($e['event_date'] ?? null);
                      $jam   = $e['event_time'] ?? '-';
                      $today = $isToday($e['event_date'] ?? null);

                      // === SAMA DENGAN DETAIL ===
                      $already      = (bool)($e['already_attend'] ?? false);
                      $attendanceAt = $e['attendance_at'] ?? ($e['waktu_scan'] ?? null);

                      // buka scan hanya jika belum absen dan can_scan = true
                      $canScan = !$already && !empty($e['can_scan']);
                    ?>
                    <div class="col-12 col-md-6 col-lg-4">
                      <div class="card h-100 abs-card">
                        <div class="card-body d-flex flex-column">
                          <div class="d-flex justify-content-between align-items-start">
                            <div class="fw-semibold me-2 abs-card-title text-truncate" title="<?= esc($e['title']) ?>">
                              <?= esc($e['title']) ?>
                            </div>
                            <div class="d-flex gap-1 flex-wrap justify-content-end">
                              <?php if (!empty($e['event_status'])): ?>
                                <span class="badge <?= esc($e['badge_class'] ?? 'bg-secondary') ?>">
                                  <?= esc($e['event_status']) ?>
                                </span>
                              <?php endif; ?>
                              <?php if ($already): ?>
                                <span class="badge bg-success-subtle text-success">
                                  <i class="bi bi-check2-circle me-1"></i>Sudah Absen
                                </span>
                              <?php endif; ?>
                            </div>
                          </div>

                          <div class="mt-1 small text-muted">
                            <?= esc($tgl) ?> · <?= esc($jam) ?>
                            <?php if ($today): ?>
                              <span class="badge bg-warning text-dark ms-1">Hari ini</span>
                            <?php endif; ?>
                          </div>

                          <div class="small text-muted mt-1">
                            Format: <?= esc(strtoupper($e['format'] ?? '-')) ?>
                            <?php if (!empty($e['location'])): ?>
                              · Lokasi: <?= esc($e['location']) ?>
                            <?php endif; ?>
                            <?php if (!empty($e['participation_type'])): ?>
                              · Mode: <?= esc(strtoupper($e['participation_type'])) ?>
                            <?php endif; ?>
                            <?php if (!empty($e['participation_type']) && strtolower($e['participation_type'])==='online' && !empty($e['zoom_link'])): ?>
                              · <a href="<?= esc($e['zoom_link']) ?>" target="_blank" rel="noopener">Link Zoom</a>
                            <?php endif; ?>
                          </div>

                          <div class="mt-auto pt-3 d-grid">
                            <?php if ($already): ?>
                              <button class="btn btn-success" type="button" disabled>
                                <i class="bi bi-check2-circle me-1"></i> Sudah Absen
                              </button>
                              <div class="form-text text-center mt-1 text-success">
                                Tercatat<?= $attendanceAt ? ' pada ' . esc($fmtDT($attendanceAt)) : '' ?>.
                              </div>
                            <?php else: ?>
                              <a href="<?= site_url('audience/absensi/event/'.$e['id']) ?>"
                                 class="btn btn-primary">
                                <i class="bi bi-clipboard-check me-1"></i>
                                <?= $canScan ? 'Absen Sekarang' : 'Detail Event' ?>
                              </a>
                              <?php if ($canScan): ?>
                                <div class="form-text text-center mt-1">Siap absen.</div>
                              <?php else: ?>
                                <div class="form-text text-center mt-1">Absen dibuka setelah event dimulai.</div>
                              <?php endif; ?>
                            <?php endif; ?>
                          </div>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php else: ?>
                <div class="p-4 text-center border rounded-3 bg-light-subtle">
                  <div class="mb-2"><i class="bi bi-calendar2-event fs-3 text-secondary"></i></div>
                  <div class="fw-semibold">Belum ada event untuk absensi</div>
                  <div class="text-muted small">Event akan muncul setelah pembayaranmu terverifikasi.</div>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Riwayat -->
        <div class="col-12">
          <div class="card shadow-sm border-0">
            <div class="card-body">
              <div class="d-flex align-items-center justify-content-between">
                <h5 class="card-title mb-0">Riwayat Absensi</h5>
                <span class="badge bg-secondary-subtle text-secondary"><?= count($history) ?></span>
              </div>
              <hr class="my-3">

              <?php if (!empty($history)): ?>
                <div class="abs-timeline">
                  <?php foreach ($history as $h): ?>
                    <div class="abs-tl-item">
                      <div class="abs-tl-dot"></div>
                      <div class="abs-tl-content">
                        <div class="d-flex justify-content-between align-items-start">
                          <div class="me-2">
                            <div class="fw-semibold"><?= esc($h['event_title'] ?? 'Event') ?></div>
                            <div class="small text-muted">
                              <?php if (!empty($h['waktu_scan'])): ?>
                                Absen: <?= esc($fmtDT($h['waktu_scan'])) ?>
                              <?php else: ?>
                                Jadwal: <?= esc($fmtDate($h['event_date'] ?? null)) ?> · <?= esc($h['event_time'] ?? '-') ?>
                              <?php endif; ?>
                            </div>
                          </div>
                          <span class="badge bg-success"><?= esc(ucfirst($h['status'] ?? 'hadir')) ?></span>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php else: ?>
                <div class="p-4 text-center border rounded-3 bg-light-subtle">
                  <div class="mb-2"><i class="bi bi-clock-history fs-3 text-secondary"></i></div>
                  <div class="fw-semibold">Belum ada riwayat</div>
                  <div class="text-muted small">Kehadiranmu akan terekam setelah absen.</div>
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
  .abs-hero{
    background: linear-gradient(90deg,#2563eb,#60a5fa);
    border-radius:16px; color:#fff; padding:14px 16px;
    box-shadow: 0 6px 20px rgba(37,99,235,.18);
  }
  .abs-title{ font-weight:800; line-height:1.2; font-size: clamp(18px,4.4vw,24px); }
  .abs-sub{ opacity:.9; font-size:.95rem; }
  @media (min-width: 576px){
    .abs-hero{ padding:18px 20px; border-radius:18px; }
  }

  .abs-card{
    border:0; background:#fff; transition:.18s ease;
    box-shadow:0 6px 18px rgba(0,0,0,.06); border-radius:14px;
  }
  .abs-card:hover{ transform: translateY(-2px); box-shadow:0 10px 24px rgba(0,0,0,.08); }
  .abs-card-title{ max-width: 75%; }

  .abs-timeline{ position: relative; margin-left: .5rem; }
  .abs-tl-item{ position: relative; padding-left: 1.5rem; margin-bottom: 1rem; }
  .abs-tl-item::before{
    content:''; position:absolute; left:.44rem; top:.6rem; bottom:-.6rem; width:2px; background:#e5e7eb;
  }
  .abs-tl-item:last-child::before{ display:none; }
  .abs-tl-dot{
    width:10px; height:10px; border-radius:999px; background:#2563eb;
    position:absolute; left:0; top:.35rem; box-shadow:0 0 0 3px rgba(37,99,235,.15);
  }
</style>
