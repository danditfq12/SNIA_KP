<?php
  /**
   * Variabel dari controller:
   * $event   : array event (id, title, description, event_date, event_time, format, location, zoom_link, ...)
   * $options : array opsi partisipasi untuk audience, contoh: ['online','offline'] (bisa kosong)
   * $pricing : matrix harga (audience online/offline)
   * $isOpen  : bool pendaftaran masih dibuka
   * $myReg   : array|null registrasi saya (id, id_event, id_user, status, mode_kehadiran, ...)
   */
  $title = 'Detail Event';
  $formatBadge = [
    'both'   => ['label' => 'ONLINE & OFFLINE', 'class' => 'bg-primary-subtle text-primary'],
    'online' => ['label' => 'ONLINE',            'class' => 'bg-info-subtle text-info'],
    'offline'=> ['label' => 'OFFLINE',           'class' => 'bg-success-subtle text-success'],
  ];
  $fmt = strtolower($event['format'] ?? 'both');
  $fmtBadge = $formatBadge[$fmt] ?? $formatBadge['both'];
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_audience') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <!-- Flash messages -->
      <?php if (session('message')): ?>
        <div class="alert alert-success"><?= esc(session('message')) ?></div>
      <?php endif; ?>
      <?php if (session('warning')): ?>
        <div class="alert alert-warning"><?= esc(session('warning')) ?></div>
      <?php endif; ?>
      <?php if (session('error')): ?>
        <div class="alert alert-danger"><?= esc(session('error')) ?></div>
      <?php endif; ?>

      <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
        <a href="<?= site_url('audience/events') ?>" class="btn btn-sm btn-outline-secondary">
          <i class="bi bi-arrow-left"></i> Kembali
        </a>
        <span class="badge <?= $fmtBadge['class'] ?>"><?= esc($fmtBadge['label']) ?></span>
      </div>

      <div class="row g-3">
        <!-- Info utama event -->
        <div class="col-12 col-lg-8">
          <div class="card shadow-sm border-0">
            <div class="card-body">
              <h3 class="mb-1"><?= esc($event['title'] ?? 'Event') ?></h3>
              <div class="text-muted small">
                <i class="bi bi-calendar-event"></i>
                <?= isset($event['event_date']) ? esc(date('l, d M Y', strtotime($event['event_date']))) : '-' ?>
                &middot;
                <i class="bi bi-clock"></i> <?= esc($event['event_time'] ?? '-') ?>
              </div>

              <hr>

              <div class="row g-3">
                <div class="col-12 col-md-6">
                  <div class="p-3 bg-light rounded-3 h-100">
                    <div class="small text-muted mb-1">Lokasi</div>
                    <?php if (in_array($fmt, ['both','offline'], true)): ?>
                      <div><i class="bi bi-geo-alt"></i> <?= esc($event['location'] ?: 'Akan diumumkan') ?></div>
                    <?php else: ?>
                      <div class="text-muted">—</div>
                    <?php endif; ?>
                  </div>
                </div>
                <div class="col-12 col-md-6">
                  <div class="p-3 bg-light rounded-3 h-100">
                    <div class="small text-muted mb-1">Link Online</div>
                    <?php if (in_array($fmt, ['both','online'], true)): ?>
                      <?php if (!empty($event['zoom_link'])): ?>
                        <div class="text-truncate"><i class="bi bi-camera-video"></i> <?= esc($event['zoom_link']) ?></div>
                      <?php else: ?>
                        <div class="text-muted">Akan dibagikan setelah pembayaran terverifikasi</div>
                      <?php endif; ?>
                    <?php else: ?>
                      <div class="text-muted">—</div>
                    <?php endif; ?>
                  </div>
                </div>
              </div>

              <?php if (!empty($event['description'])): ?>
                <hr>
                <div>
                  <h6 class="mb-2">Deskripsi</h6>
                  <div class="text-secondary" style="white-space:pre-line"><?= esc($event['description']) ?></div>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Panel harga & aksi -->
        <div class="col-12 col-lg-4">
          <div class="card shadow-sm border-0">
            <div class="card-body">
              <h5 class="mb-3">Harga Audience</h5>
              <div class="d-flex flex-wrap gap-2">
                <?php if (isset($pricing['audience']['online'])): ?>
                  <span class="badge bg-info-subtle text-info">
                    Online: Rp <?= number_format((float)($pricing['audience']['online'] ?? 0), 0, ',', '.') ?>
                  </span>
                <?php endif; ?>
                <?php if (isset($pricing['audience']['offline'])): ?>
                  <span class="badge bg-primary-subtle text-primary">
                    Offline: Rp <?= number_format((float)($pricing['audience']['offline'] ?? 0), 0, ',', '.') ?>
                  </span>
                <?php endif; ?>
              </div>

              <hr>

              <?php if (!empty($myReg)): ?>
                <!-- Sudah terdaftar -->
                <div class="alert alert-info">
                  Kamu sudah terdaftar sebagai
                  <b><?= strtoupper(esc($myReg['mode_kehadiran'] ?? '-')) ?></b>
                  (status: <b><?= esc($myReg['status'] ?? '-') ?></b>).
                </div>

                <?php if (($myReg['status'] ?? '') === 'menunggu_pembayaran'): ?>
                  <div class="d-grid gap-2">
                    <a href="<?= site_url('audience/pembayaran/instruction/'.(int)$myReg['id']) ?>" class="btn btn-primary">
                      Lanjutkan ke Instruksi Pembayaran
                    </a>
                    <a href="<?= site_url('audience/pembayaran/create/'.(int)$myReg['id']) ?>" class="btn btn-outline-primary">
                      Upload Bukti Pembayaran
                    </a>
                  </div>
                <?php elseif (($myReg['status'] ?? '') === 'lunas'): ?>
                  <?php if (($myReg['mode_kehadiran'] ?? '') === 'online' && !empty($event['zoom_link'])): ?>
                    <a href="<?= esc($event['zoom_link']) ?>" target="_blank" class="btn btn-success w-100">
                      <i class="bi bi-camera-video"></i> Masuk ke Ruang Online
                    </a>
                  <?php else: ?>
                    <div class="text-muted small">Tunjukkan tiket/QR saat hadir offline (jika ada fitur absensi).</div>
                  <?php endif; ?>
                <?php else: ?>
                  <div class="text-muted small">Silakan cek status pembayaran kamu di menu Pembayaran.</div>
                <?php endif; ?>

              <?php else: ?>
                <!-- Belum terdaftar -->
                <?php if (!$isOpen): ?>
                  <div class="alert alert-secondary">Pendaftaran event ini telah ditutup.</div>
                <?php elseif (empty($options)): ?>
                  <div class="alert alert-warning">Tidak ada opsi kehadiran yang tersedia untuk event ini.</div>
                <?php else: ?>
                  <a href="<?= site_url('audience/events/register/'.(int)$event['id']) ?>" class="btn btn-primary w-100">
                    Daftar Sekarang
                  </a>
                <?php endif; ?>
              <?php endif; ?>

              <div class="mt-3">
                <a href="<?= site_url('audience/events') ?>" class="btn btn-outline-secondary w-100">Lihat Event Lain</a>
              </div>
            </div>
          </div>

          <?php if (!empty($event['registration_deadline'])): ?>
            <div class="card shadow-sm border-0 mt-3">
              <div class="card-body">
                <div class="small text-muted mb-1">Batas Pendaftaran</div>
                <div><i class="bi bi-hourglass-split"></i>
                  <?= esc(date('d M Y H:i', strtotime($event['registration_deadline']))) ?>
                </div>
              </div>
            </div>
          <?php endif; ?>

        </div>
      </div>
    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>
