<?php
  $title  = 'Instruksi Pembayaran';
  $reg    = $reg ?? [];
  $amount = (float) ($amount ?? 0);
  $copyText = 'Bank BNI • a.n. Panitia SNIA • No.Rek 1234567890';
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_audience') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="mb-2">Instruksi Transfer</h5>
          <p class="mb-1">Nominal: <b>Rp <?= number_format($amount,0,',','.') ?></b></p>

          <div class="input-group mb-3">
            <input type="text" class="form-control" id="rekText" value="<?= esc($copyText) ?>" readonly>
            <button class="btn btn-outline-secondary" type="button" id="btnCopy">Salin</button>
          </div>

          <div class="text-muted small mb-3">
            Setelah transfer, klik tombol di bawah untuk mengunggah bukti pembayaran.
          </div>

          <a href="<?= site_url('audience/pembayaran/create/'.($reg['id'] ?? 0)) ?>" class="btn btn-success">
            Upload Bukti Pembayaran
          </a>
          <a href="<?= site_url('audience/pembayaran') ?>" class="btn btn-light">Lihat Riwayat</a>
        </div>
      </div>

    </div>
  </main>
</div>

<script>
document.getElementById('btnCopy')?.addEventListener('click', function(){
  const inp = document.getElementById('rekText');
  inp.select(); inp.setSelectionRange(0, 99999);
  document.execCommand('copy');
  if (window.Swal) Swal.fire({icon:'success', title:'Tersalin', timer:1200, showConfirmButton:false});
});
</script>

<?= $this->include('partials/footer') ?>
