<?php
  $title  = 'Detail Event';
  $event  = $event  ?? [];
  $isOpen = $isOpen ?? false;
  $myReg  = $myReg  ?? null;
  $options= $options ?? [];
  $pricing= $pricing ?? [];
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_audience') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <div class="card shadow-sm">
        <div class="card-body">
          <h4 class="mb-1"><?= esc($event['title'] ?? 'Event') ?></h4>
          <div class="text-muted small mb-3">
            <?= esc(date('d M Y', strtotime($event['event_date'] ?? date('Y-m-d')))) ?>
            · <?= esc($event['event_time'] ?? '-') ?> · Format: <?= esc(strtoupper($event['format'] ?? '-')) ?>
            <?php if (!empty($event['location'])): ?> · Lokasi: <?= esc($event['location']) ?><?php endif; ?>
          </div>

          <?php if ($myReg): ?>
            <div class="alert alert-info mb-0">
              Kamu sudah terdaftar (mode: <b><?= esc(strtoupper($myReg['mode_kehadiran'] ?? '-')) ?></b>).
              <?php if (($myReg['status'] ?? '') === 'menunggu_pembayaran'): ?>
                <a href="<?= site_url('audience/pembayaran/instruction/'.$myReg['id']) ?>" class="alert-link">Lanjutkan pembayaran</a>.
              <?php endif; ?>
            </div>
          <?php elseif ($isOpen): ?>
            <button type="button" class="btn btn-primary" id="btnDaftar">
              Daftar Sekarang
            </button>
          <?php else: ?>
            <span class="badge bg-secondary">Pendaftaran Ditutup</span>
          <?php endif; ?>

          <?php if (!empty($pricing)): ?>
            <hr>
            <div class="small text-muted">Ringkasan harga (audience):</div>
            <ul class="small">
              <?php foreach ($pricing as $row): ?>
                <?php if (($row['role'] ?? '') !== 'audience') continue; ?>
                <li><?= esc(ucfirst($row['participation_type'] ?? '-')) ?>:
                  Rp <?= number_format((float)($row['price'] ?? 0),0,',','.') ?></li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </main>
</div>

<script>
document.getElementById('btnDaftar')?.addEventListener('click', function(){
  const go = ()=> location.href = "<?= site_url('audience/events/register/'.($event['id'] ?? 0)) ?>";
  if (window.Swal) {
    Swal.fire({
      title: 'Lanjut daftar?',
      text: 'Kamu akan memilih mode (online/offline) sesuai ketersediaan.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Ya, lanjut',
      cancelButtonText: 'Batal'
    }).then(r=>{ if(r.isConfirmed) go(); });
  } else {
    if (confirm('Yakin lanjut daftar?')) go();
  }
});
</script>

<?= $this->include('partials/footer') ?>
