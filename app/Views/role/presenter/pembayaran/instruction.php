<?php
$title     = $title ?? 'Instruksi Pembayaran';
$event     = $event ?? [];
$basePrice = (int)($basePrice ?? 0);
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
<main class="flex-fill" style="padding-top:70px;">
<div class="container-fluid p-3 p-md-4">

  <!-- Header Biru -->
  <div class="header-section header-blue d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3">
    <div class="mb-2 mb-md-0">
      <h3 class="welcome-text mb-1"><i class="bi bi-info-circle me-2"></i>Instruksi Pembayaran</h3>
      <div class="text-white-50 small"><?= esc($event['title'] ?? '-') ?></div>
    </div>
    <div class="text-start text-md-end">
      <small class="text-white-50 d-block">Total Saat Ini</small>
      <div class="price-bubble" id="priceTop">Rp <?= number_format($basePrice,0,',','.') ?></div>
    </div>
  </div>

  <div class="row g-3">
    <!-- Kiri: Rincian -->
    <div class="col-12 col-lg-7">
      <div class="card shadow-sm mb-3">
        <div class="card-header bg-gradient-primary text-white py-3">
          <h5 class="mb-0 d-flex align-items-center"><i class="bi bi-wallet2 me-2"></i>Rincian Pembayaran</h5>
        </div>
        <div class="card-body p-3 p-md-4">

          <div class="row g-3 mb-2">
            <div class="col-12 col-md-6">
              <div class="mini-label text-muted">Harga Presenter (Offline)</div>
              <div class="fs-5 fw-semibold" id="basePrice">Rp <?= number_format($basePrice,0,',','.') ?></div>
            </div>
            <div class="col-12 col-md-6">
              <div class="mini-label text-muted">Harga Setelah Voucher</div>
              <div class="fs-5 fw-bold text-success" id="finalPrice">Rp <?= number_format($basePrice,0,',','.') ?></div>
            </div>
          </div>

          <!-- Breakdown -->
          <div class="breakdown border rounded-3 p-3 mb-3 bg-light-subtle">
            <div class="d-flex justify-content-between">
              <span>Harga Dasar</span>
              <span id="bdBase">Rp <?= number_format($basePrice,0,',','.') ?></span>
            </div>
            <div class="d-flex justify-content-between text-danger">
              <span>Diskon Voucher</span>
              <span id="bdDisc">Rp 0</span>
            </div>
            <hr class="my-2">
            <div class="d-flex justify-content-between fw-semibold">
              <span>Total Dibayar</span>
              <span id="bdTotal">Rp <?= number_format($basePrice,0,',','.') ?></span>
            </div>
          </div>

          <!-- Voucher -->
          <div class="mb-2">
            <label class="form-label mb-1">Kode Voucher (opsional)</label>
            <div class="input-group input-group-lg">
              <span class="input-group-text"><i class="bi bi-ticket-perforated"></i></span>
              <input type="text" class="form-control" id="kodeVoucher" placeholder="KODEPROMO" autocomplete="off">
              <button class="btn btn-outline-primary" id="btnCek" disabled>
                <i class="bi bi-check2-circle me-1"></i>Cek
              </button>
            </div>
            <div class="form-text">Jika tidak punya, biarkan kosong.</div>
            <div class="mt-2" id="voucherMsg" role="alert"></div>
          </div>

          <div class="mt-3 small text-muted">
            Transfer ke: <strong>Bank ABC — 123456789 a.n. Panitia Event</strong><br>
            Berita transfer: <em><?= 'EVT'.str_pad((string)($event['id'] ?? 0), 4, '0', STR_PAD_LEFT) ?> — <?= esc($event['title'] ?? 'Event') ?></em>
          </div>
        </div>
      </div>
    </div>

    <!-- Kanan: CTA -->
    <div class="col-12 col-lg-5">
      <div class="card shadow-sm sticky-lg-top" style="top:84px;">
        <div class="card-body p-3 p-md-4">
          <div class="d-flex align-items-center justify-content-between mb-3">
            <div>
              <div class="mini-label text-muted">Total Yang Dibayar</div>
              <div class="fs-3 fw-bold mb-0" id="boxTotal">Rp <?= number_format($basePrice,0,',','.') ?></div>
            </div>
            <div class="text-end">
              <span class="badge bg-primary-subtle text-primary">Offline</span>
            </div>
          </div>

          <p class="text-muted small">
            Pastikan nominal transfer sesuai total. Setelah itu, lanjut ke halaman upload bukti pembayaran.
          </p>

          <div class="d-grid gap-2">
            <a class="btn btn-success btn-lg" id="btnLanjut" href="/presenter/pembayaran/create/<?= (int)($event['id'] ?? 0) ?>">
              <i class="bi bi-upload me-1"></i> Lanjut ke Upload Bukti
            </a>
            <a class="btn btn-outline-secondary" href="/presenter/abstrak">
              Kembali ke Abstrak
            </a>
          </div>
        </div>
      </div>

      <!-- Mobile sticky -->
      <div class="mobile-sticky d-lg-none">
        <div class="mobile-sticky__inner">
          <div>
            <div class="mini-label text-muted mb-1">Total</div>
            <div class="mobile-total fw-bold" id="mobTotal">Rp <?= number_format($basePrice,0,',','.') ?></div>
          </div>
          <a class="btn btn-success btn-lg flex-fill" id="btnLanjutMob" href="/presenter/pembayaran/create/<?= (int)($event['id'] ?? 0) ?>">
            Upload Bukti
          </a>
        </div>
      </div>
    </div>
  </div>

  <div class="alert alert-info mt-3">
    Cek voucher (jika ada), lalu klik <strong>Lanjut ke Upload Bukti</strong>. Jumlah akhir akan dicek ulang di server.
  </div>

</div>
</main>
</div>

<?= $this->include('partials/footer') ?>

<style>
  :root{ --primary:#2563eb; --primary-deep:#1e40af; --info:#06b6d4; }
  /* Header biru */
  .header-section.header-blue{
    background:linear-gradient(135deg,var(--primary),var(--primary-deep));
    color:#fff; padding:20px; border-radius:16px; box-shadow:0 8px 28px rgba(0,0,0,.12);
  }
  .welcome-text{ font-weight:700; font-size:1.25rem; }
  .price-bubble{
    display:inline-block; padding:.35rem .75rem; background:rgba(255,255,255,.15);
    border:1px solid rgba(255,255,255,.3); border-radius:999px; font-weight:700;
  }

  .card{ border-radius:14px; }
  .bg-gradient-primary{ background:linear-gradient(135deg,var(--primary),var(--info))!important; }

  .mini-label{ font-size:.85rem; }
  .breakdown.bg-light-subtle{ background:#f8fafc!important; }

  /* Sticky CTA mobile */
  .mobile-sticky{ position:sticky; bottom:0; left:0; right:0; margin-top:12px; z-index:1030; }
  .mobile-sticky__inner{
    display:flex; gap:.75rem; align-items:center; justify-content:space-between;
    background:#ffffff; border-top:1px solid #e5e7eb; padding:.75rem .9rem;
    box-shadow:0 -6px 18px rgba(0,0,0,.06);
  }
  .mobile-total{ font-size:1.25rem; }

  .btn{ border-radius:12px; }
  .btn-success{ box-shadow:0 6px 18px rgba(16,185,129,.18); }
  .btn-outline-secondary{ border-color:#cbd5e1; }
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script>
(function(){
  const eventId = <?= (int)($event['id'] ?? 0) ?>;
  const csrfName = '<?= csrf_token() ?>';
  const csrfHash = '<?= csrf_hash() ?>';
  const basePrice = <?= (int)$basePrice ?>;

  const $code = $('#kodeVoucher');
  const $btnCek = $('#btnCek');
  const $msg = $('#voucherMsg');

  const fmt  = (x)=> new Intl.NumberFormat('id-ID').format(x || 0);
  const toRp = (n)=> 'Rp ' + fmt(n);

  function setPrice(base, disc){
    const total = Math.max(0, (base|0) - (disc|0));
    $('#finalPrice').text(toRp(total));
    $('#priceTop').text(toRp(total));
    $('#bdBase').text(toRp(base));
    $('#bdDisc').text('- ' + toRp(disc));
    $('#bdTotal').text(toRp(total));
    $('#boxTotal').text(toRp(total));
    $('#mobTotal').text(toRp(total));
  }

  function resetPrice(){
    setPrice(basePrice, 0);
    $('#btnLanjut, #btnLanjutMob').attr('href','/presenter/pembayaran/create/'+eventId);
    $msg.removeClass().empty();
  }

  // Enable/disable tombol cek otomatis
  function toggleCekBtn(){
    const hasVal = $code.val().trim().length > 0;
    $btnCek.prop('disabled', !hasVal);
    if(!hasVal) resetPrice();
  }

  $code.on('input', toggleCekBtn);
  toggleCekBtn(); // init

  $btnCek.on('click', function(e){
    const code = $code.val().trim();
    if(code === ''){
      e.preventDefault();
      $msg.removeClass().addClass('alert alert-warning py-2 px-3')
         .text('Masukkan kode voucher terlebih dahulu.');
      return;
    }

    const payload = { event_id: eventId, kode_voucher: code };
    payload[csrfName] = csrfHash;

    $msg.removeClass().addClass('alert alert-info py-2 px-3').text('Memeriksa voucher...');
    $.post('/presenter/pembayaran/validate-voucher', payload)
      .done(function(resp){
        if(resp && resp.ok){
          const total = +resp.final_price || 0;
          const disc  = Math.max(0, basePrice - total);

          setPrice(basePrice, disc);
          $msg.removeClass().addClass('alert alert-success py-2 px-3').text(resp.message || 'Voucher diterapkan.');

          const href = '/presenter/pembayaran/create/' + eventId + '?v=' + encodeURIComponent(code);
          $('#btnLanjut, #btnLanjutMob').attr('href', href);
        } else {
          resetPrice();
          $msg.removeClass().addClass('alert alert-danger py-2 px-3')
             .text((resp && resp.message) ? resp.message : 'Voucher tidak valid.');
        }
      })
      .fail(function(){
        resetPrice();
        $msg.removeClass().addClass('alert alert-danger py-2 px-3').text('Gagal memeriksa voucher.');
      });
  });
})();
</script>