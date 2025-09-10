<?php
  $title = $title ?? 'Pembayaran Event';
  $event = $event ?? [];
  $price = (float)($price ?? 0);
  $fmtDate = fn($s)=> $s ? date('d M Y', strtotime($s)) : '-';
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">
      <div class="abs-hero mb-3">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="abs-title">Pembayaran Event</div>
            <div class="abs-sub">Upload bukti pembayaran untuk konfirmasi partisipasi.</div>
          </div>
        </div>
      </div>

      <div class="row g-3">
        <div class="col-12 col-lg-5">
          <div class="card shadow-sm h-100">
            <div class="card-body">
              <h5 class="card-title mb-2"><?= esc($event['title'] ?? 'Event') ?></h5>
              <div class="text-muted small mb-2">
                <?= esc($fmtDate($event['event_date'] ?? null)) ?> · <?= esc($event['event_time'] ?? '-') ?> · Lokasi: <?= esc($event['location'] ?? '-') ?><br>
                Format Event: <?= esc(strtoupper($event['format'] ?? '-')) ?> · Mode Presenter: <strong>OFFLINE</strong>
              </div>
              <hr>
              <div class="d-flex justify-content-between">
                <div class="fw-semibold">Biaya Presenter</div>
                <div class="fw-bold">Rp <?= number_format($price, 0, ',', '.') ?></div>
              </div>
              <div class="small text-muted mt-2">Kamu bisa memasukkan kode voucher kalau ada.</div>
            </div>
          </div>
        </div>

        <div class="col-12 col-lg-7">
          <div class="card shadow-sm h-100">
            <div class="card-body">
              <h5 class="card-title mb-3">Form Pembayaran</h5>
              <form method="post" action="<?= site_url('presenter/pembayaran/store') ?>" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="event_id" value="<?= (int)($event['id'] ?? 0) ?>">

                <div class="mb-3">
                  <label class="form-label">Kode Voucher (opsional)</label>
                  <div class="input-group">
                    <input type="text" class="form-control" name="voucher" id="voucherInput" placeholder="SNIA50" autocomplete="off">
                    <button class="btn btn-outline-secondary" type="button" id="btnCheckVoucher">Cek</button>
                  </div>
                  <div class="form-text" id="voucherHelp">Masukkan kode, lalu klik Cek.</div>
                </div>

                <div class="mb-3">
                  <label class="form-label">Bukti Pembayaran (JPG/PNG/WebP/PDF)</label>
                  <input type="file" name="bukti" class="form-control" accept=".jpg,.jpeg,.png,.webp,.pdf" required>
                </div>

                <div class="d-grid">
                  <button class="btn btn-primary" type="submit"><i class="bi bi-upload me-1"></i>Upload Pembayaran</button>
                </div>
              </form>

              <div class="alert alert-light border mt-3 mb-0">
                <div class="fw-semibold mb-1">Catatan:</div>
                <ul class="mb-0 small">
                  <li>Pastikan jumlah transfer sesuai dengan biaya di atas.</li>
                  <li>Setelah diunggah, status akan menjadi <strong>Pending</strong> sampai diverifikasi admin.</li>
                  <li>Kamu bisa membatalkan selama pembayaran belum terverifikasi.</li>
                </ul>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<style>
.abs-hero{background:linear-gradient(90deg,#2563eb,#60a5fa);border-radius:16px;color:#fff;padding:14px 16px;box-shadow:0 6px 20px rgba(37,99,235,.18);}
.abs-title{font-weight:800;line-height:1.2;font-size:clamp(18px,4.2vw,24px);}
.abs-sub{opacity:.9;font-size:.95rem;}
</style>

<script>
(function(){
  const btn = document.getElementById('btnCheckVoucher');
  const inp = document.getElementById('voucherInput');
  const help= document.getElementById('voucherHelp');
  btn?.addEventListener('click', async function(){
    const code = (inp.value || '').trim();
    if(!code){ help.textContent = 'Kode kosong.'; help.className='form-text text-danger'; return; }
    help.textContent = 'Memeriksa...'; help.className='form-text';
    try{
      const res = await fetch('<?= site_url('presenter/pembayaran/validate-voucher') ?>', {
        method:'POST',
        headers:{'Content-Type':'application/x-www-form-urlencoded','X-Requested-With':'XMLHttpRequest'},
        body: new URLSearchParams({code, event_id:'<?= (int)($event['id'] ?? 0) ?>'})
      });
      const j = await res.json();
      if(j.ok){
        help.textContent = 'Voucher OK. Diskon: Rp ' + (Math.round((j.discount||0))).toLocaleString('id-ID');
        help.className='form-text text-success';
      }else{
        help.textContent = j.message || 'Voucher tidak berlaku.';
        help.className='form-text text-danger';
      }
    }catch(e){
      help.textContent = 'Gagal memeriksa voucher.'; help.className='form-text text-danger';
    }
  });
})();
</script>