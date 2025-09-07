<?php
  $title = 'Absensi - Audience';

/** Controller mengirim:
 * $yourEvents = [
 *   ['id','title','event_date','event_time','format','location',
 *    'participation_type','event_status','badge_class','can_scan']
 * ];
 * $history = [
 *   ['event_title','event_date','event_time','waktu_scan','status','qr_code']
 * ];
 */
  $yourEvents = $yourEvents ?? [];
  $history    = $history ?? [];
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_audience') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <div class="mb-3">
        <h3 class="mb-0">Absensi - Audience</h3>
        <small class="text-muted">Event yang sudah kamu bayar akan muncul di sini.</small>
      </div>

      <div class="row g-3">

        <!-- Event Tersedia (berbayar/aktif) -->
        <div class="col-12">
          <div class="card shadow-sm">
            <div class="card-body">
              <h5 class="card-title mb-3">Event Tersedia</h5>

              <?php if (!empty($yourEvents)): ?>
                <div class="row g-2 g-md-3">
                  <?php foreach ($yourEvents as $e): ?>
                    <?php
                      $tgl = isset($e['event_date']) ? date('d M Y', strtotime($e['event_date'])) : '-';
                      $jam = $e['event_time'] ?? '-';
                    ?>
                    <div class="col-12 col-md-6 col-lg-4">
                      <a href="<?= site_url('audience/absensi/event/'.$e['id']) ?>" class="text-decoration-none text-reset">
                        <div class="p-3 border rounded-3 h-100 bg-white">
                          <div class="d-flex justify-content-between align-items-start">
                            <div class="fw-semibold me-2"><?= esc($e['title']) ?></div>
                            <span class="badge <?= esc($e['badge_class']) ?>">
                              <?= esc($e['event_status']) ?>
                            </span>
                          </div>
                          <div class="small text-muted mt-1">
                            <?= esc($tgl) ?> · <?= esc($jam) ?>
                          </div>
                          <div class="small text-muted">
                            Format: <?= esc(strtoupper($e['format'] ?? '-')) ?>
                            <?php if (!empty($e['location'])): ?>
                              · Lokasi: <?= esc($e['location']) ?>
                            <?php endif; ?>
                            <?php if (!empty($e['participation_type'])): ?>
                              · Mode: <?= esc(strtoupper($e['participation_type'])) ?>
                            <?php endif; ?>
                          </div>
                          <div class="mt-2">
                            <span class="badge bg-primary-subtle text-primary">
                              Klik untuk absensi
                            </span>
                            <?php if (!$e['can_scan']): ?>
                              <span class="badge bg-secondary ms-1">Belum Bisa Absen</span>
                            <?php endif; ?>
                          </div>
                        </div>
                      </a>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php else: ?>
                <div class="p-4 text-center border rounded-3 bg-light-subtle">
                  <div class="mb-2"><i class="bi bi-calendar2-event fs-3 text-secondary"></i></div>
                  <div class="fw-semibold">Belum ada event yang membuka pendaftaran</div>
                  <div class="text-muted small">Tunggu informasi berikutnya ya.</div>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Riwayat Absensi -->
        <div class="col-12">
          <div class="card shadow-sm">
            <div class="card-body">
              <h5 class="card-title mb-3">Riwayat Absensi</h5>

              <?php if (!empty($history)): ?>
                <div class="list-group list-group-flush">
                  <?php foreach ($history as $h): ?>
                    <div class="list-group-item px-0">
                      <div class="d-flex justify-content-between align-items-start">
                        <div>
                          <div class="fw-semibold"><?= esc($h['event_title'] ?? 'Event') ?></div>
                          <div class="small text-muted">
                            <?php if (!empty($h['waktu_scan'])): ?>
                              Absen: <?= esc(date('d M Y H:i', strtotime($h['waktu_scan']))) ?>
                            <?php else: ?>
                              Tanggal: <?= esc(isset($h['event_date']) ? date('d M Y', strtotime($h['event_date'])) : '-') ?>
                              · <?= esc($h['event_time'] ?? '-') ?>
                            <?php endif; ?>
                          </div>
                        </div>
                        <span class="badge bg-success">
                          <?= esc(ucfirst($h['status'] ?? 'hadir')) ?>
                        </span>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php else: ?>
                <div class="p-4 text-center border rounded-3 bg-light-subtle">
                  <div class="mb-2"><i class="bi bi-clock-history fs-3 text-secondary"></i></div>
                  <div class="fw-semibold">Belum ada riwayat absensi</div>
                  <div class="text-muted small">Nanti catatan kehadiranmu akan muncul di sini.</div>
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
