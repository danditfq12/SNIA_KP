<?php
  // $p: record pembayaran + event_title
  $title = 'Detail Pembayaran';
  $badge = [
    'pending'  => 'warning',
    'verified' => 'success',
    'rejected' => 'danger',
    'canceled' => 'secondary',
];
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_audience') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <a href="<?= site_url('audience/pembayaran') ?>" class="btn btn-sm btn-outline-secondary mb-3">
        <i class="bi bi-arrow-left"></i> Kembali
      </a>

      <div class="card shadow-sm border-0">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <h5 class="mb-0">Invoice: <?= esc($p['invoice_display'] ?? ('#'.$p['id_pembayaran'])) ?></h5>
            <span class="badge bg-<?= $badge[$p['status']] ?? 'secondary' ?> text-uppercase">
              <?= esc($p['status']) ?>
            </span>
          </div>
          <hr>

          <div class="row g-3">
            <div class="col-12 col-lg-6">
              <div class="small text-muted">Event</div>
              <div class="fw-semibold"><?= esc($p['event_title'] ?? '-') ?></div>
              <div class="small text-muted">
                <?= isset($p['event_date']) ? esc(date('d M Y', strtotime($p['event_date']))) : '-' ?>
                · <?= esc($p['event_time'] ?? '-') ?>
              </div>
              <div class="mt-2">Jumlah: <b>Rp <?= number_format((float)($p['jumlah'] ?? 0), 0, ',', '.') ?></b></div>
              <div>Metode: <?= esc(ucfirst($p['metode'] ?? '-')) ?></div>
              <div>Tanggal Bayar: <?= esc(isset($p['tanggal_bayar']) ? date('d/m/Y H:i', strtotime($p['tanggal_bayar'])) : '-') ?></div>
            </div>

            <div class="col-12 col-lg-6">
              <div class="small text-muted mb-2">Bukti Pembayaran</div>
              <?php if (!empty($p['bukti_bayar'])): ?>
                <a class="btn btn-sm btn-outline-primary" href="<?= site_url('audience/pembayaran/download-bukti/'.(int)$p['id_pembayaran']) ?>">
                  Download Bukti
                </a>
              <?php else: ?>
                <div class="text-muted">Tidak ada file.</div>
              <?php endif; ?>

              <?php if (($p['status'] ?? '') === 'pending'): ?>
                <div class="mt-3">
                  <a href="<?= site_url('audience/pembayaran/cancel/'.(int)$p['id_pembayaran']) ?>" class="btn btn-outline-danger btn-sm"
                     onclick="return confirm('Batalkan pembayaran ini?');">Batalkan</a>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <hr class="my-3">
          <div class="small text-muted">
            Status akan berubah menjadi <b>verified</b> setelah diverifikasi admin.
            Jika ditolak, silakan upload ulang dengan bukti yang benar.
          </div>
        </div>
      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>
