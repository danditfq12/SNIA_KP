<?php
  $title   = 'Pilih Mode Kehadiran';
  $event   = $event ?? [];
  $options = $options ?? [];    // contoh: ['online','offline']
  $pricing = $pricing ?? [];    // ['audience'=>['online'=>..., 'offline'=>...]]
  $rupiah  = function($n){ return ($n===null||$n==='') ? '—' : 'Rp '.number_format((float)$n,0,',','.'); };
  $priceOnline  = $pricing['audience']['online']  ?? null;
  $priceOffline = $pricing['audience']['offline'] ?? null;
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_audience') ?>
<?= $this->include('partials/alerts') ?>

<style>
  .option-card{
    border:1.5px solid #e5e7eb; border-radius:12px; padding:12px;
    display:flex; gap:12px; align-items:center; cursor:pointer; transition:.15s;
  }
  .option-card:hover{ border-color:#2563eb; background:#eff6ff }  /* biru */
  .option-card input{ margin-top:3px }
  .option-icon{
    width:38px;height:38px;border-radius:10px;display:inline-flex;align-items:center;justify-content:center;
    background:#e0f2fe;      /* biru muda */
    color:#0369a1;           /* teks ikon biru tua */
    font-size:1.1rem;
  }
  .muted{color:#6b7280}
</style>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <!-- ringkasan event -->
      <div class="card shadow-sm mb-3">
        <div class="card-body">
          <div class="d-flex flex-column flex-md-row justify-content-between gap-2">
            <div>
              <h5 class="mb-1"><?= esc($event['title'] ?? 'Event') ?></h5>
              <div class="muted small">
                <?= esc(isset($event['event_date']) ? date('d M Y', strtotime($event['event_date'])) : '-') ?> ·
                <?= esc($event['event_time'] ?? '-') ?> ·
                Format: <?= esc(strtoupper($event['format'] ?? '-')) ?>
                <?php if (!empty($event['location'])): ?> · Lokasi: <?= esc($event['location']) ?><?php endif; ?>
              </div>
            </div>
            <div class="text-md-end">
              <div class="small muted">Harga Audience</div>
              <div class="d-flex gap-2 justify-content-md-end">
                <span class="badge bg-info-subtle text-info"><i class="bi bi-wifi me-1"></i>Online: <?= $rupiah($priceOnline) ?></span>
                <span class="badge bg-primary-subtle text-primary"><i class="bi bi-people me-1"></i>Offline: <?= $rupiah($priceOffline) ?></span>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- pilihan mode -->
      <div class="card shadow-sm">
        <div class="card-body">
          <h5 class="mb-3">Pilih Mode Kehadiran</h5>

          <form id="regForm" action="<?= site_url('audience/events/register/'.($event['id'] ?? 0)) ?>" method="post">
            <?= csrf_field() ?>

            <?php if (empty($options)): ?>
              <div class="alert alert-warning mb-0">Mode kehadiran tidak tersedia untuk event ini.</div>
            <?php else: ?>

              <?php foreach ($options as $opt): 
                $price = $pricing['audience'][$opt] ?? 0;
                $icon  = $opt === 'online' ? 'bi-wifi' : 'bi-people';
              ?>
              <label class="option-card mb-2" for="mode_<?= esc($opt) ?>">
                <input class="form-check-input" type="radio" name="mode_kehadiran"
                       id="mode_<?= esc($opt) ?>" value="<?= esc($opt) ?>" required>
                <span class="option-icon"><i class="bi <?= $icon ?>"></i></span>
                <div>
                  <div class="fw-semibold text-uppercase"><?= esc($opt) ?></div>
                  <div class="muted small"><?= $rupiah($price) ?></div>
                </div>
              </label>
              <?php endforeach; ?>

              <div class="mt-3 d-flex gap-2">
                <button type="button" id="btnSubmit" class="btn btn-primary flex-fill">
                  <i class="bi bi-arrow-right-circle me-1"></i>Lanjut ke Pembayaran
                </button>
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
