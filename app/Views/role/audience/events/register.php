<?php
  $title   = 'Daftar Event';
  // Controller kirim: $event, $options (['online','offline']), $pricing (matrix)
  $event   = $event   ?? [];
  $options = $options ?? [];
  $pricing = $pricing ?? [];
  $fmt     = strtoupper($event['format'] ?? '-');
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_audience') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
        <a href="<?= site_url('audience/events/detail/'.($event['id'] ?? 0)) ?>" class="btn btn-sm btn-outline-secondary">
          <i class="bi bi-arrow-left"></i> Kembali
        </a>
        <span class="badge bg-secondary-subtle text-secondary"><?= esc($fmt) ?></span>
      </div>

      <div class="card shadow-sm border-0">
        <div class="card-body">
          <h4 class="mb-1"><?= esc($event['title'] ?? 'Event') ?></h4>
          <small class="text-muted">
            <?= esc(isset($event['event_date']) ? date('d M Y', strtotime($event['event_date'])) : '-') ?>
            · <?= esc($event['event_time'] ?? '-') ?>
          </small>

          <hr>

          <form action="<?= site_url('audience/events/register/'.($event['id'] ?? 0)) ?>" method="post" class="needs-validation" novalidate>
            <?= csrf_field() ?>

            <!-- Pilih mode -->
            <div class="mb-3">
              <label class="form-label">Pilih Mode Kehadiran</label>
              <div class="d-flex gap-3 flex-wrap">
                <?php foreach ($options as $opt): ?>
                  <div class="form-check">
                    <input class="form-check-input js-mode" type="radio" name="mode_kehadiran" id="mode_<?= esc($opt) ?>" value="<?= esc($opt) ?>" required>
                    <label class="form-check-label" for="mode_<?= esc($opt) ?>">
                      <?= strtoupper(esc($opt)) ?>
                    </label>
                  </div>
                <?php endforeach; ?>
              </div>
              <div class="invalid-feedback">Pilih salah satu.</div>
            </div>

            <!-- Voucher opsional -->
            <div class="mb-3">
              <label class="form-label">Kode Voucher (opsional)</label>
              <input type="text" name="voucher" id="voucher" class="form-control" placeholder="Masukkan kode jika ada">
            </div>

            <!-- Ringkasan harga -->
            <div class="mb-3">
              <div class="p-3 bg-light rounded-3 d-flex align-items-center justify-content-between">
                <div>
                  <div class="small text-muted">Total yang harus dibayar</div>
                  <div class="h5 mb-0" id="priceLabel">Rp 0</div>
                </div>
                <button type="button" class="btn btn-outline-secondary btn-sm" id="btnCalc">
                  <i class="bi bi-calculator"></i> Hitung
                </button>
              </div>
              <div class="form-text">
                Harga Audience — Online: <b>Rp <?= number_format((float)($pricing['audience']['online'] ?? 0),0,',','.') ?></b>,
                Offline: <b>Rp <?= number_format((float)($pricing['audience']['offline'] ?? 0),0,',','.') ?></b>.
              </div>
            </div>

            <div class="d-grid d-md-flex gap-2">
              <button type="submit" class="btn btn-primary">Lanjutkan Pembayaran</button>
              <a href="<?= site_url('audience/events') ?>" class="btn btn-outline-secondary">Batal</a>
            </div>
          </form>
        </div>
      </div>

    </div>
  </main>
</div>

<script>
  const btnCalc   = document.getElementById('btnCalc');
  const priceLbl  = document.getElementById('priceLabel');
  const voucherEl = document.getElementById('voucher');

  // hitung otomatis kalau user pilih radio
  document.querySelectorAll('.js-mode').forEach(r => {
    r.addEventListener('change', () => btnCalc?.click());
  });

  btnCalc?.addEventListener('click', async () => {
    const checked = document.querySelector('.js-mode:checked');
    if (!checked) { alert('Pilih mode terlebih dulu.'); return; }

    try {
      const res = await fetch('<?= site_url('audience/events/calculate-price') ?>', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({
          '<?= csrf_token() ?>': '<?= csrf_hash() ?>',
          id_event: '<?= (int)($event['id'] ?? 0) ?>',
          mode: checked.value,
          voucher: voucherEl?.value || ''
        })
      });
      const data = await res.json();
      if (data.ok) {
        priceLbl.textContent = 'Rp ' + new Intl.NumberFormat('id-ID').format(data.price || 0);
      } else {
        alert('Gagal menghitung harga');
      }
    } catch (e) {
      alert('Terjadi kesalahan saat menghitung harga.');
    }
  });
</script>

<?= $this->include('partials/footer') ?>
