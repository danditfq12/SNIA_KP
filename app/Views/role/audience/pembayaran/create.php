<?php
  $title = 'Kirim Pembayaran';
  // Controller kirim: $reg (join event), $amount
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
        <div class="col-12 col-lg-7">
          <div class="card shadow-sm border-0">
            <div class="card-body">
              <h5 class="mb-3">Informasi Event</h5>
              <div class="mb-1 fw-semibold"><?= esc($reg['event_title'] ?? 'Event') ?></div>
              <div class="small text-muted">
                <?= esc(isset($reg['event_date']) ? date('d M Y', strtotime($reg['event_date'])) : '-') ?>
                · <?= esc($reg['event_time'] ?? '-') ?>
              </div>
              <div class="small text-muted">Mode: <?= esc(strtoupper($reg['mode_kehadiran'] ?? '-')) ?></div>
              <div class="small text-muted">Lokasi: <?= esc($reg['location'] ?? '-') ?></div>
              <?php if (!empty($reg['zoom_link'])): ?>
                <div class="small text-muted">Link Online: <code><?= esc($reg['zoom_link']) ?></code></div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="col-12 col-lg-5">
          <div class="card shadow-sm border-0">
            <div class="card-body">
              <h5 class="mb-3">Kirim Pembayaran</h5>

              <form action="<?= site_url('audience/pembayaran/store') ?>" method="post" enctype="multipart/form-data" class="needs-validation" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="id_reg" value="<?= (int)$reg['id'] ?>">
                <input type="hidden" name="jumlah" value="<?= (float)$amount ?>">

                <div class="mb-3">
                  <div class="small text-muted">Total</div>
                  <div class="h4 mb-0">Rp <?= number_format((float)$amount, 0, ',', '.') ?></div>
                </div>

                <div class="mb-3">
                  <label class="form-label">Metode</label>
                  <div class="d-flex gap-3">
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="metode" id="m_transfer" value="transfer" checked>
                      <label class="form-check-label" for="m_transfer">Transfer / Upload Bukti</label>
                    </div>
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="metode" id="m_gateway" value="gateway">
                      <label class="form-check-label" for="m_gateway">Payment Gateway</label>
                    </div>
                  </div>
                </div>

                <div class="mb-3" id="wrapBukti">
                  <label class="form-label">Bukti Pembayaran (jpg/png/pdf)</label>
                  <input type="file" class="form-control" name="bukti_bayar" accept=".jpg,.jpeg,.png,.pdf" required>
                  <div class="form-text">Upload bukti transfer ke rekening panitia.</div>
                </div>

                <div class="d-grid">
                  <button class="btn btn-primary">Kirim Pembayaran</button>
                </div>
              </form>

              <script>
                const wrapBukti = document.getElementById('wrapBukti');
                document.querySelectorAll('input[name="metode"]').forEach(r => {
                  r.addEventListener('change', () => {
                    if (r.value === 'gateway' && r.checked) {
                      wrapBukti.style.display = 'none';
                      wrapBukti.querySelector('input').required = false;
                    }
                    if (r.value === 'transfer' && r.checked) {
                      wrapBukti.style.display = '';
                      wrapBukti.querySelector('input').required = true;
                    }
                  });
                });
              </script>
            </div>
          </div>
        </div>
      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>
