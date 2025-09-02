<?php
  $title = 'Detail Pembayaran';
  // Controller kirim: $p
  $status = strtolower($p['status'] ?? 'pending');
  $badge  = ['pending'=>'warning','verified'=>'success','canceled'=>'secondary'][$status] ?? 'secondary';
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
          <div class="d-flex align-items-start justify-content-between">
            <div>
              <div class="small text-muted">Invoice</div>
              <div class="h5 mb-1"><?= esc($p['invoice_display'] ?? '-') ?></div>
              <div class="small">Event: <b><?= esc($p['event_title'] ?? ('#'.$p['event_id'])) ?></b></div>
            </div>
            <span class="badge bg-<?= $badge ?> align-self-start"><?= strtoupper(esc($status)) ?></span>
          </div>

          <hr>

          <div class="row g-3">
            <div class="col-12 col-md-6">
              <div class="p-3 border rounded-3 bg-white h-100">
                <div class="fw-semibold mb-2">Ringkasan</div>
                <div class="d-flex justify-content-between"><span class="text-muted">Jumlah</span><span>Rp <?= number_format((float)($p['jumlah'] ?? 0), 0, ',', '.') ?></span></div>
                <div class="d-flex justify-content-between"><span class="text-muted">Metode</span><span><?= esc(ucfirst($p['metode'] ?? '-')) ?></span></div>
                <div class="d-flex justify-content-between"><span class="text-muted">Mode Kehadiran</span><span><?= esc(strtoupper($p['participation_type'] ?? '-')) ?></span></div>
                <div class="d-flex justify-content-between"><span class="text-muted">Tanggal</span><span><?= esc($p['tanggal_bayar'] ?? '-') ?></span></div>
                <?php if (!empty($p['verified_at'])): ?>
                  <div class="d-flex justify-content-between"><span class="text-muted">Diverifikasi</span><span><?= esc($p['verified_at']) ?></span></div>
                <?php endif; ?>
              </div>
            </div>

            <div class="col-12 col-md-6">
              <div class="p-3 border rounded-3 bg-white h-100">
                <div class="fw-semibold mb-2">Bukti Pembayaran</div>
                <?php if (!empty($p['bukti_bayar'])): ?>
                  <p class="mb-2 text-muted small"><?= esc($p['bukti_bayar']) ?></p>
                  <a class="btn btn-sm btn-outline-primary" href="<?= site_url('audience/pembayaran/download-bukti/'.(int)$p['id_pembayaran']) ?>">
                    <i class="bi bi-download me-1"></i> Download
                  </a>
                <?php else: ?>
                  <div class="text-muted">Tidak ada bukti yang diunggah.</div>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <?php if ($status === 'pending'): ?>
            <div class="mt-3">
              <a href="<?= site_url('audience/pembayaran/cancel/'.(int)$p['id_pembayaran']) ?>" class="btn btn-outline-danger btn-sm"
                 onclick="return confirm('Batalkan pembayaran ini?');">
                Batalkan Pembayaran
              </a>
            </div>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>
