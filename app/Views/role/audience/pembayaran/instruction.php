<?php
  // $reg, $amount
  $title = 'Instruksi Pembayaran';
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_audience') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <a href="<?= site_url('audience/events/detail/'.(int)$reg['id_event']) ?>" class="btn btn-sm btn-outline-secondary mb-3">
        <i class="bi bi-arrow-left"></i> Kembali ke Event
      </a>

      <div class="row g-3">
        <div class="col-12 col-lg-6">
          <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
              <h5 class="mb-2">Ringkasan Pendaftaran</h5>
              <div class="fw-semibold"><?= esc($reg['event_title'] ?? 'Event') ?></div>
              <div class="small text-muted">
                <?= isset($reg['event_date']) ? esc(date('d M Y', strtotime($reg['event_date']))) : '-' ?>
                · <?= esc($reg['event_time'] ?? '-') ?>
              </div>
              <div class="small text-muted">Mode: <?= esc(strtoupper($reg['mode_kehadiran'] ?? '-')) ?></div>
              <hr>
              <div class="small text-muted">Nominal</div>
              <div class="h4 mb-0">Rp <?= number_format((float)$amount, 0, ',', '.') ?></div>
            </div>
          </div>
        </div>

        <div class="col-12 col-lg-6">
          <div class="card shadow-sm border-0 h-100">
            <div class="card-body">
              <h5 class="mb-2">Rekening Panitia</h5>

              <!-- HARD-CODED -->
              <div class="p-3 border rounded-3 bg-light-subtle">
                <div class="mb-1">Bank: <b>BNI</b></div>
                <div class="mb-1">No. Rekening: <b class="user-select-all">1234567890</b></div>
                <div>Atas Nama: <b>Yayasan SNIA</b></div>
              </div>

              <div class="mt-3 small text-muted">
                Transfer sesuai nominal. Cantumkan berita:
                <code><?= 'SNIA-'.$reg['id_event'].'-'.$reg['id_user'] ?></code>.
              </div>

              <div class="d-grid mt-3">
                <a href="<?= site_url('audience/pembayaran/create/'.(int)$reg['id']) ?>" class="btn btn-primary">
                  Saya sudah transfer → Upload Bukti
                </a>
              </div>
              <div class="mt-2">
                <a href="<?= site_url('audience/pembayaran') ?>" class="btn btn-outline-secondary w-100">Nanti saja</a>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>
