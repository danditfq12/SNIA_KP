<?php
  $title = 'Audience Dashboard';

// controller sebaiknya mengirim array berikut (boleh kosong):
// $eventsOpen:   daftar event tersedia (title, event_date, event_time, format, location)
// $attended:     daftar event yang pernah diikuti (title, event_date, event_time, mode_kehadiran)
// $upcomingPaid: daftar event "lunas" yang akan/berlangsung (title, event_date, event_time, mode_kehadiran)
  $eventsOpen   = $eventsOpen   ?? [];
  $attended     = $attended     ?? [];
  $upcomingPaid = $upcomingPaid ?? [];
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_audience') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <div class="mb-3">
        <h3 class="mb-0">Selamat Datang, <?= esc(session('nama') ?? 'Audience') ?></h3>
      </div>

      <div class="row g-3">
        <!-- Event Tersedia -->
        <div class="col-12">
          <div class="card shadow-sm">
            <div class="card-body">
              <h5 class="card-title mb-3">Event Tersedia</h5>

              <?php if (!empty($eventsOpen)): ?>
                <div class="row g-2 g-md-3">
                  <?php foreach ($eventsOpen as $e): ?>
                    <div class="col-12 col-md-6 col-lg-4">
                      <div class="p-3 border rounded-3 h-100 bg-white">
                        <div class="fw-semibold mb-1"><?= esc($e['title'] ?? 'Event') ?></div>
                        <div class="small text-muted">
                          <?= esc(isset($e['event_date']) ? date('d M Y', strtotime($e['event_date'])) : '-') ?>
                          · <?= esc($e['event_time'] ?? '-') ?>
                        </div>
                        <div class="small text-muted">
                          Format: <?= esc(strtoupper($e['format'] ?? '-')) ?> ·
                          Lokasi: <?= esc($e['location'] ?? '-') ?>
                        </div>
                        <div class="mt-2">
                          <span class="badge bg-info-subtle text-info">Tersedia</span>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php else: ?>
                <div class="p-4 text-center border rounded-3 bg-light-subtle">
                  <div class="mb-2"><i class="bi bi-calendar2-event fs-3 text-secondary"></i></div>
                  <div class="fw-semibold">Event belum tersedia</div>
                  <div class="text-muted small">Tunggu informasi berikutnya ya.</div>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Event Pernah Diikuti -->
        <div class="col-12">
          <div class="card shadow-sm">
            <div class="card-body">
              <h5 class="card-title mb-3">Event yang Pernah Diikuti</h5>

              <?php if (!empty($attended)): ?>
                <div class="list-group list-group-flush">
                  <?php foreach ($attended as $r): ?>
                    <div class="list-group-item px-0">
                      <div class="d-flex justify-content-between align-items-start">
                        <div>
                          <div class="fw-semibold"><?= esc($r['title'] ?? $r['event_title'] ?? 'Event') ?></div>
                          <div class="small text-muted">
                            <?= esc(isset($r['event_date']) ? date('d M Y', strtotime($r['event_date'])) : '-') ?>
                            · <?= esc($r['event_time'] ?? '-') ?> ·
                            Mode: <?= esc(strtoupper($r['mode_kehadiran'] ?? '-')) ?>
                          </div>
                        </div>
                        <span class="badge bg-secondary">Selesai</span>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php else: ?>
                <div class="p-4 text-center border rounded-3 bg-light-subtle">
                  <div class="mb-2"><i class="bi bi-clock-history fs-3 text-secondary"></i></div>
                  <div class="fw-semibold">Belum ada riwayat keikutsertaan</div>
                  <div class="text-muted small">Daftar event dulu ya 🙂</div>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Jadwal Event (dibayar / sedang diselenggarakan) -->
        <div class="col-12">
          <div class="card shadow-sm">
            <div class="card-body">
              <h5 class="card-title mb-3">Jadwal Event (Sudah Dibayar / Berjalan)</h5>

              <?php if (!empty($upcomingPaid)): ?>
                <div class="list-group list-group-flush">
                  <?php foreach ($upcomingPaid as $u): ?>
                    <?php
                      $tanggal = isset($u['event_date']) ? strtotime($u['event_date']) : null;
                      $isToday = $tanggal ? (date('Y-m-d', $tanggal) === date('Y-m-d')) : false;
                      $badge   = $isToday ? ['warning','Hari ini'] : ['success','Mendatang'];
                    ?>
                    <div class="list-group-item px-0">
                      <div class="d-flex justify-content-between align-items-start">
                        <div>
                          <div class="fw-semibold"><?= esc($u['title'] ?? $u['event_title'] ?? 'Event') ?></div>
                          <div class="small text-muted">
                            <?= esc(isset($u['event_date']) ? date('d M Y', strtotime($u['event_date'])) : '-') ?>
                            · <?= esc($u['event_time'] ?? '-') ?> ·
                            Mode: <?= esc(strtoupper($u['mode_kehadiran'] ?? '-')) ?>
                          </div>
                        </div>
                        <span class="badge bg-<?= $badge[0] ?>"><?= $badge[1] ?></span>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php else: ?>
                <div class="p-4 text-center border rounded-3 bg-light-subtle">
                  <div class="mb-2"><i class="bi bi-qr-code fs-3 text-secondary"></i></div>
                  <div class="fw-semibold">Belum ada jadwal aktif</div>
                  <div class="text-muted small">Event akan muncul di sini setelah pembayaran diverifikasi.</div>
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
