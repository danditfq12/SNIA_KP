<?php
  /**
   * Variabel dari controller:
   * $event   : data event (id, title, event_date, event_time, format, ...)
   * $options : ['online','offline'] bergantung format event
   * $pricing : matrix harga (audience online/offline)
   */
  $title = 'Daftar Event';
  $fmt = strtoupper(esc($event['format'] ?? 'BOTH'));
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_audience') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <!-- Flash -->
      <?php if (session('warning')): ?>
        <div class="alert alert-warning"><?= esc(session('warning')) ?></div>
      <?php endif; ?>
      <?php if (session('error')): ?>
        <div class="alert alert-danger"><?= esc(session('error')) ?></div>
      <?php endif; ?>

      <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
        <a href="<?= site_url('audience/events/detail/'.($event['id'] ?? 0)) ?>" class="btn btn-sm btn-outline-secondary">
          <i class="bi bi-arrow-left"></i> Kembali
        </a>
        <span class="badge bg-secondary-subtle text-secondary"><?= $fmt ?></span>
      </div>

      <div class="card shadow-sm border-0">
        <div class="card-body">
          <h4 class="mb-1"><?= esc($event['title'] ?? 'Event') ?></h4>
          <small class="text-muted">
            <?= esc(isset($event['event_date']) ? date('d M Y', strtotime($event['event_date'])) : '-') ?>
            · <?= esc($event['event_time'] ?? '-') ?>
          </small>

          <hr>

          <?php if (empty($options)): ?>
            <div class="alert alert-warning mb-0">
              Tidak ada opsi kehadiran untuk event ini. Silakan kembali ke detail event.
            </div>
          <?php else: ?>
            <form action="<?= site_url('audience/events/register/'.($event['id'] ?? 0)) ?>" method="post" novalidate>
              <?= csrf_field() ?>

              <div class="mb-3">
                <label class="form-label">Pilih Mode Kehadiran</label>
                <div class="d-flex gap-3 flex-wrap">
                  <?php foreach ($options as $opt): ?>
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="mode_kehadiran" id="mode_<?= esc($opt) ?>" value="<?= esc($opt) ?>" required>
                      <label class="form-check-label" for="mode_<?= esc($opt) ?>">
                        <?= strtoupper(esc($opt)) ?>
                      </label>
                    </div>
                  <?php endforeach; ?>
                </div>
                <div class="form-text">Pilih salah satu sesuai preferensi kamu.</div>
              </div>

              <div class="mb-3">
                <div class="p-3 bg-light rounded-3">
                  <div class="small text-muted mb-1">Harga Audience</div>
                  <div class="d-flex flex-wrap gap-2">
                    <?php if (isset($pricing['audience']['online'])): ?>
                      <span class="badge bg-info-subtle text-info">
                        Online: Rp <?= number_format((float)$pricing['audience']['online'], 0, ',', '.') ?>
                      </span>
                    <?php endif; ?>
                    <?php if (isset($pricing['audience']['offline'])): ?>
                      <span class="badge bg-primary-subtle text-primary">
                        Offline: Rp <?= number_format((float)$pricing['audience']['offline'], 0, ',', '.') ?>
                      </span>
                    <?php endif; ?>
                  </div>
                  <div class="form-text mt-1">Nominal final dikalkulasi otomatis setelah submit.</div>
                </div>
              </div>

              <div class="d-grid d-md-flex gap-2">
                <button type="submit" class="btn btn-primary">Lanjutkan Pembayaran</button>
                <a href="<?= site_url('audience/events/detail/'.($event['id'] ?? 0)) ?>" class="btn btn-outline-secondary">Batal</a>
              </div>
            </form>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>
