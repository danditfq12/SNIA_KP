<?php
  $title   = 'Pilih Mode Kehadiran';
  $event   = $event ?? [];
  $options = $options ?? [];    // contoh: ['online','offline']
  $pricing = $pricing ?? [];    // dari EventModel::getPricingMatrix()

  $rupiah = function($n){
    if ($n === null || $n === '' ) return '—';
    return 'Rp ' . number_format((float)$n, 0, ',', '.');
  };
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_audience') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="mb-3">Pilih Mode Kehadiran</h5>

          <form id="regForm" action="<?= site_url('audience/events/register/'.($event['id'] ?? 0)) ?>" method="post">
            <?= csrf_field() ?>

            <?php if (empty($options)): ?>
              <div class="alert alert-warning mb-0">Mode kehadiran tidak tersedia.</div>
            <?php else: ?>
              <?php foreach ($options as $opt):
                // Ambil harga sesuai struktur matrix:
                // $pricing = [
                //   'presenter' => ['offline' => ...],
                //   'audience'  => ['online' => ..., 'offline' => ...]
                // ];
                $price = $pricing['audience'][$opt] ?? 0;
              ?>
                <div class="form-check mb-2">
                  <input class="form-check-input" type="radio" name="mode_kehadiran"
                         id="mode_<?= esc($opt) ?>" value="<?= esc($opt) ?>" required>
                  <label for="mode_<?= esc($opt) ?>" class="form-check-label">
                    <?= strtoupper(esc($opt)) ?> <span class="text-muted small">· <?= $rupiah($price) ?></span>
                  </label>
                </div>
              <?php endforeach; ?>

              <div class="mt-3">
                <button type="button" id="btnSubmit" class="btn btn-primary">Lanjut ke Pembayaran</button>
                <a href="<?= site_url('audience/events/detail/'.($event['id'] ?? 0)) ?>" class="btn btn-light">Batal</a>
              </div>
            <?php endif; ?>
          </form>
        </div>
      </div>

    </div>
  </main>
</div>

<script>
document.getElementById('btnSubmit')?.addEventListener('click', function(){
  const f = document.getElementById('regForm');
  const go = ()=> f.submit();
  if (window.Swal) {
    Swal.fire({
      title: 'Lanjut ke pembayaran?',
      text: 'Kamu akan melihat instruksi transfer dan dapat mengunggah bukti.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Ya, lanjut',
      cancelButtonText: 'Batal'
    }).then(r=>{ if(r.isConfirmed) go(); });
  } else {
    if (confirm('Lanjut ke pembayaran?')) go();
  }
});
</script>

<?= $this->include('partials/footer') ?>
