<?php
  $title = $title ?? 'Absensi Presenter';
  $todayEvents       = $todayEvents       ?? [];
  $currentEvents     = $currentEvents     ?? [];
  $attendanceHistory = $attendanceHistory ?? [];
  helper(['number','form']);
  $fmtDate = fn($s)=> $s ? date('d M Y', strtotime($s)) : '-';
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Absensi Presenter</h3>
        <div class="d-none d-md-flex gap-2">
          <a href="<?= site_url('presenter/events') ?>" class="btn btn-outline-primary">
            <i class="bi bi-calendar2-event me-1"></i>Event
          </a>
          <a href="<?= site_url('qr/scanner') ?>" class="btn btn-primary">
            <i class="bi bi-qr-code-scan me-1"></i>Scan QR
          </a>
        </div>
      </div>

      <!-- Hari Ini -->
      <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0">Absen Hari Ini</h5>
            <span class="badge bg-warning text-dark"><?= count($todayEvents) ?></span>
          </div>
          <hr class="my-3">
          <?php if (!empty($todayEvents)): ?>
            <div class="row g-2 g-md-3">
              <?php foreach ($todayEvents as $e): ?>
                <?php
                  $mode    = strtoupper($e['format'] ?? $e['mode_kehadiran'] ?? 'OFFLINE');
                  $can     = (bool)($e['can_scan'] ?? false);
                  $badge   = $e['badge_class'] ?? 'bg-secondary';
                  $label   = $e['event_status'] ?? '—';
                  $already = !empty($e['already_attended']);
                ?>
                <div class="col-12 col-md-6 col-lg-4">
                  <div class="p-3 border rounded-3 h-100 bg-white">
                    <div class="d-flex align-items-start justify-content-between">
                      <div class="fw-semibold me-2 text-truncate" title="<?= esc($e['title'] ?? 'Event') ?>">
                        <i class="bi bi-qr-code-scan me-1"></i><?= esc($e['title'] ?? 'Event') ?>
                      </div>
                      <span class="badge <?= esc($badge) ?>"><?= esc($label) ?></span>
                    </div>
                    <div class="small text-muted mt-1">
                      <?= esc($fmtDate($e['event_date'] ?? null)) ?> · <?= esc($e['event_time'] ?? '-') ?> · Mode Event: <?= esc($mode) ?>
                    </div>
                    <div class="mt-2 d-flex flex-wrap gap-2 justify-content-end">
                      <?php if ($already): ?>
                        <span class="badge bg-success-subtle text-success">Sudah Absen</span>
                      <?php endif; ?>
                      <a class="btn btn-sm btn-outline-warning <?= $can && !$already ? '' : 'disabled' ?>"
                         href="<?= site_url('presenter/absensi/event/'.(int)($e['id'] ?? 0)) ?>"
                         aria-disabled="<?= $can && !$already ? 'false' : 'true' ?>">
                        Absen Sekarang
                      </a>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="p-4 text-center border rounded-3 bg-light-subtle">
              <div class="mb-2"><i class="bi bi-qr-code fs-3 text-secondary"></i></div>
              <div class="fw-semibold">Tidak ada event untuk diabsen hari ini</div>
              <div class="text-muted small">Event yang diverifikasi akan tampil di sini pada hari H.</div>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Riwayat -->
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <h5 class="card-title mb-3">Riwayat Absensi</h5>
          <?php if (!empty($attendanceHistory)): ?>
            <div class="table-responsive">
              <table class="table table-sm align-middle">
                <thead class="table-light">
                  <tr>
                    <th>Event</th>
                    <th>Tanggal</th>
                    <th>Waktu</th>
                    <th>Status</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                <?php foreach ($attendanceHistory as $a): ?>
                  <?php $ts = $a['waktu_scan'] ?? $a['created_at'] ?? null; ?>
                  <tr>
                    <td class="fw-semibold"><?= esc($a['event_title'] ?? $a['title'] ?? 'Event') ?></td>
                    <td><?= $ts ? date('d M Y', strtotime($ts)) : '-' ?></td>
                    <td><?= $ts ? date('H:i',    strtotime($ts)) : '-' ?></td>
                    <td>
                      <span class="badge <?= ($a['status'] ?? '')==='hadir' ? 'bg-success' : 'bg-secondary' ?>">
                        <?= esc(ucfirst($a['status'] ?? '')) ?>
                      </span>
                    </td>
                    <td class="text-end">
                      <?php if (!empty($a['event_id'])): ?>
                        <a href="<?= site_url('presenter/absensi/event/'.$a['event_id']) ?>" class="btn btn-sm btn-outline-secondary">Detail</a>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="p-4 text-center border rounded-3 bg-light-subtle">
              <div class="mb-2"><i class="bi bi-clock-history fs-3 text-secondary"></i></div>
              <div class="fw-semibold">Belum ada riwayat absensi</div>
              <div class="text-muted small">Riwayat akan muncul setelah Anda melakukan scan QR.</div>
            </div>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>
