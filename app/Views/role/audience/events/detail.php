<?php
  $title   = 'Detail Event';
  // Controller kirim: $event, $options (['online','offline']), $pricing (matrix dari EventModel)
  $event   = $event   ?? [];
  $options = $options ?? [];
  $pricing = $pricing ?? [];
  $fmt     = strtoupper($event['format'] ?? '-');
  $dl      = $event['registration_deadline'] ?? null;
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_audience') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
        <a href="<?= site_url('audience/events') ?>" class="btn btn-sm btn-outline-secondary">
          <i class="bi bi-arrow-left"></i> Kembali
        </a>
        <span class="badge bg-secondary-subtle text-secondary"><?= esc($fmt) ?></span>
      </div>

      <div class="card shadow-sm border-0">
        <div class="card-body">
          <h3 class="mb-1"><?= esc($event['title'] ?? 'Event') ?></h3>
          <small class="text-muted">
            <?= esc(isset($event['event_date']) ? date('d M Y', strtotime($event['event_date'])) : '-') ?>
            · <?= esc($event['event_time'] ?? '-') ?>
          </small>

          <?php if ($dl): ?>
            <div class="mt-1 small">
              <span class="badge bg-info-subtle text-info">
                Pendaftaran s.d. <?= esc(date('d M Y H:i', strtotime($dl))) ?>
              </span>
            </div>
          <?php endif; ?>

          <div class="row g-3 mt-3">
            <div class="col-12 col-lg-7">
              <div class="p-3 border rounded-3 bg-white">
                <div class="fw-semibold mb-2">Deskripsi</div>
                <div class="text-muted" style="white-space:pre-line;">
                  <?= esc($event['description'] ?? 'Belum ada deskripsi.') ?>
                </div>
              </div>
            </div>

            <div class="col-12 col-lg-5">
              <div class="p-3 border rounded-3 bg-white">
                <div class="fw-semibold mb-2">Informasi</div>
                <div class="small text-muted mb-1">Lokasi: <?= esc($event['location'] ?? '-') ?></div>
                <?php if (!empty($event['zoom_link'])): ?>
                  <div class="small text-muted mb-1">Link Online: <code><?= esc($event['zoom_link']) ?></code></div>
                <?php endif; ?>
                <div class="mt-3">
                  <div class="small text-muted">Harga Audience</div>
                  <div class="d-flex gap-2 flex-wrap mt-1">
                    <span class="badge bg-info-subtle text-info">
                      Online: Rp <?= number_format((float)($pricing['audience']['online'] ?? 0), 0, ',', '.') ?>
                    </span>
                    <span class="badge bg-primary-subtle text-primary">
                      Offline: Rp <?= number_format((float)($pricing['audience']['offline'] ?? 0), 0, ',', '.') ?>
                    </span>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="mt-4 d-flex gap-2">
            <a href="<?= site_url('audience/events/register/'.($event['id'] ?? 0)) ?>" class="btn btn-primary">
              Daftar Sekarang
            </a>
            <a href="<?= site_url('audience/events') ?>" class="btn btn-outline-secondary">Lihat Event Lain</a>
          </div>
        </div>
      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>
