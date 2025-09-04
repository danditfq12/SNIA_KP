<?php
  // $reg, $amount
  $title = 'Upload Bukti Pembayaran';
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_audience') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <a href="<?= site_url('audience/pembayaran/instruction/'.(int)$reg['id']) ?>" class="btn btn-sm btn-outline-secondary mb-3">
        <i class="bi bi-arrow-left"></i> Kembali
      </a>

      <div class="card shadow-sm border-0">
        <div class="card-body">
          <h5 class="mb-3">Upload Bukti Pembayaran</h5>

          <div class="mb-3">
            <div class="small text-muted">Event</div>
            <div class="fw-semibold"><?= esc($reg['event_title'] ?? '-') ?></div>
            <div class="small text-muted">
              <?= isset($reg['event_date']) ? esc(date('d M Y', strtotime($reg['event_date']))) : '-' ?>
              · <?= esc($reg['event_time'] ?? '-') ?> ·
              Mode: <?= esc(strtoupper($reg['mode_kehadiran'] ?? '-')) ?>
            </div>
            <div class="mt-2">Nominal: <b>Rp <?= number_format((float)$amount, 0, ',', '.') ?></b></div>
          </div>

          <?php if (session('errors')): ?>
            <div class="alert alert-danger">
              <?php foreach (session('errors') as $e): ?>
                <div><?= esc($e) ?></div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <form action="<?= site_url('audience/pembayaran/store') ?>" method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="id_reg" value="<?= (int)$reg['id'] ?>">

            <div class="mb-3">
              <label class="form-label">Bukti Pembayaran (JPG/PNG/PDF, maks 5MB)</label>
              <input type="file" name="bukti_bayar" class="form-control" accept=".jpg,.jpeg,.png,.pdf" required>
            </div>

            <div class="d-grid d-md-flex gap-2">
              <button class="btn btn-primary" type="submit">Kirim</button>
              <a href="<?= site_url('audience/pembayaran') ?>" class="btn btn-outline-secondary">Batal</a>
            </div>
          </form>
        </div>
      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>
