<?php
$title              = $title ?? 'Instruksi Pembayaran';
$event              = $event ?? [];
$basePrice          = (int)($basePrice ?? 0);
$midtransClientKey  = $midtrans_client_key ?? '';
$isProduction       = $is_production ?? false;

$evTitle = $event['title'] ?? '-';
$evDate  = isset($event['event_date']) ? date('d M Y', strtotime($event['event_date'])) : '-';
$evTime  = $event['event_time'] ?? '-';
$amountF = number_format($basePrice, 0, ',', '.');
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill page-wrap-blue">
    <div class="container-xxl px-3 px-md-4 py-4">

      <!-- HERO seragam -->
      <div class="card-hero mb-3">
        <div class="hero-body">
          <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
              <h3 class="hero-title mb-1">
                <i class="bi bi-info-circle me-2"></i>Instruksi Pembayaran
              </h3>
              <div class="text-white-70 small"><?= esc($evTitle) ?></div>
            </div>
            <div class="text-start text-md-end">
              <small class="text-white-70 d-block">Total Saat Ini</small>
              <div class="price-bubble" id="priceTop">Rp <?= $amountF ?></div>
            </div>
          </div>
        </div>
        <div class="hero-tabs">
          <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="badge bg-info-subtle text-info px-3 py-2"><i class="bi bi-calendar-event me-1"></i><?= esc($evDate) ?></span>
            <span class="badge bg-primary-subtle text-primary px-3 py-2"><i class="bi bi-clock me-1"></i><?= esc($evTime) ?></span>
            <span class="badge bg-success-subtle text-success px-3 py-2"><i class="bi bi-person-video3 me-1"></i>Presenter • Offline</span>
          </div>
        </div>
      </div>

      <div class="row g-3">
        <!-- Kiri: Informasi -->
        <div class="col-12 col-lg-7">
          <div class="card card-glass border-0 mb-3">
            <div class="card-header d-flex align-items-center gap-2">
              <span class="badge bg-blue-soft"><i class="bi bi-calendar-event"></i></span>
              <h5 class="mb-0 fw-semibold text-ink">Informasi Event</h5>
            </div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-md-6">
                  <div class="mini-label text-muted">Tanggal Event</div>
                  <div class="fw-semibold text-ink"><?= esc($evDate) ?></div>
                </div>
                <div class="col-md-6">
                  <div class="mini-label text-muted">Waktu</div>
                  <div class="fw-semibold text-ink"><?= esc($evTime) ?></div>
                </div>
                <div class="col-md-6">
                  <div class="mini-label text-muted">Mode Kehadiran</div>
                  <div class="fw-semibold"><span class="badge bg-primary-subtle text-primary">Offline</span></div>
                </div>
                <div class="col-md-6">
                  <div class="mini-label text-muted">Role</div>
                  <div class="fw-semibold"><span class="badge bg-success-subtle text-success">Presenter</span></div>
                </div>
              </div>
            </div>
          </div>

          <!-- Voucher (auto-hide saat total 0) -->
          <div class="card card-glass border-0 mb-4" id="voucherSection" <?= $basePrice <= 0 ? 'style="display:none;"' : '' ?>>
            <div class="card-header d-flex align-items-center gap-2">
              <span class="badge bg-blue-soft"><i class="bi bi-tag"></i></span>
              <h6 class="mb-0 fw-semibold text-ink">Punya Kode Voucher?</h6>
            </div>
            <div class="card-body">
              <div class="row align-items-end g-2">
                <div class="col">
                  <input type="text" id="voucherCode" class="form-control form-control-soft"
                         placeholder="Masukkan kode voucher (opsional)"
                         maxlength="20" autocomplete="off">
                  <div class="form-text">Masukkan kode voucher jika Anda memilikinya</div>
                </div>
                <div class="col-auto">
                  <button type="button" id="btnCheckVoucher" class="btn btn-outline-primary">
                    <span class="spinner-border spinner-border-sm me-2 d-none" id="voucherSpinner"></span>
                    <span id="voucherBtnText">Cek Voucher</span>
                  </button>
                </div>
              </div>
              <div id="voucherResult" class="mt-2"></div>
            </div>
          </div>
        </div>

        <!-- Kanan: CTA -->
        <div class="col-12 col-lg-5">
          <div class="card card-glass border-0 sticky-lg-top" style="top:88px;">
            <div class="card-header d-flex align-items-center gap-2">
              <span class="badge bg-blue-soft"><i class="bi bi-wallet2"></i></span>
              <strong class="text-ink">Ringkasan Pembayaran</strong>
            </div>
            <div class="card-body">
              <div class="mb-2 d-flex justify-content-between">
                <span>Harga Presenter (Offline)</span>
                <span class="fw-semibold text-ink" id="basePrice">Rp <?= $amountF ?></span>
              </div>

              <div class="mb-2 d-flex justify-content-between text-success" id="discountRow" style="display:none;">
                <span>Diskon Voucher <span id="voucherCodeDisplay"></span></span>
                <span class="fw-semibold" id="discountAmount">- Rp 0</span>
              </div>

              <hr class="my-2">
              <div class="d-flex justify-content-between align-items-center">
                <div class="mini-label text-muted">Total Dibayar</div>
                <div class="fs-4 fw-bold text-ink" id="boxTotal">Rp <?= $amountF ?></div>
              </div>

              <p class="text-muted small mt-3 mb-3">
                Pembayaran menggunakan Midtrans untuk verifikasi otomatis.
              </p>

              <div class="d-flex flex-wrap gap-2">
                <button type="button" id="btnProceed" class="btn btn-success btn-lg flex-fill" <?= $basePrice <= 0 ? 'data-zero="1"' : '' ?>>
                  <i class="bi bi-credit-card me-1"></i>
                  <span id="btnProceedText"><?= $basePrice <= 0 ? 'Selesaikan (Total Rp 0)' : 'Lanjut ke Pembayaran Digital' ?></span>
                </button>
                <a class="btn btn-light border flex-fill" href="<?= site_url('presenter/pembayaran') ?>">
                  <i class="bi bi-arrow-left"></i> Kembali
                </a>
              </div>
            </div>
          </div>

          <!-- Mobile sticky -->
          <div class="mobile-sticky d-lg-none">
            <div class="mobile-sticky__inner">
              <div>
                <div class="mini-label text-muted mb-1">Total</div>
                <div class="mobile-total fw-bold" id="mobTotal">Rp <?= $amountF ?></div>
              </div>
              <button type="button" class="btn btn-success btn-lg flex-fill" id="btnProceedMobile" <?= $basePrice <= 0 ? 'data-zero="1"' : '' ?>>
                <span id="btnProceedMobileText"><?= $basePrice <= 0 ? 'Selesaikan (Rp 0)' : 'Bayar Digital' ?></span>
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="alert alert-info mt-3 card-glass border-0">
        <i class="bi bi-info-circle me-2"></i>
        <strong>Pembayaran Digital:</strong> Midtrans (aman & otomatis) — Kartu, E-Wallet, Transfer Bank, & QRIS.
      </div>

    </div>
  </main>
</div>

<!-- Hidden Data -->
<input type="hidden" id="csrfToken" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
<input type="hidden" id="eventId" value="<?= (int)($event['id'] ?? 0) ?>">
<input type="hidden" id="baseAmount" value="<?= $basePrice ?>">

<!-- Midtrans Snap Script -->
<script type="text/javascript"
        src="<?= $isProduction ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' ?>"
        data-client-key="<?= esc($midtransClientKey) ?>">
</script>

<?= $this->include('partials/footer') ?>

<style>
:root{
  /* Blue scale & ink (selaras halaman lain) */
  --blue-50:#eff6ff; --blue-100:#dbeafe; --blue-200:#bfdbfe; --blue-300:#93c5fd;
  --blue-400:#60a5fa; --blue-500:#3b82f6; --blue-600:#2563eb; --blue-700:#1d4ed8; --blue-800:#1e40af; --blue-900:#1e3a8a;
  --ink:#0f172a; --muted:#6b7280;

  /* Glass & radius */
  --radius:16px;
  --glass-bg: rgba(255,255,255,.94);
  --glass-bd: rgba(30,64,175,.14);
  --glass-shadow: 0 12px 28px rgba(2,6,23,.08);

  /* Subtle badges */
  --success:#10b981; --warning:#f59e0b; --danger:#ef4444;
}

body{ font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size:15px; color:var(--ink); }

/* Page wrap (seragam) */
.page-wrap-blue{
  min-height:100vh; padding-top:72px;
  background:
    radial-gradient(1000px 380px at 10% -10%, rgba(59,130,246,.16), rgba(59,130,246,0) 60%),
    radial-gradient(1000px 380px at 90% 110%, rgba(59,130,246,.12), rgba(59,130,246,0) 70%),
    linear-gradient(180deg, var(--blue-50), #fff 40%);
}
.container-xxl{ max-width:min(100%, 1560px); }

/* HERO */
.card-hero{ border:0; border-radius:var(--radius); overflow:hidden; box-shadow:0 12px 28px rgba(30,64,175,.18); }
.card-hero .hero-body{ background:linear-gradient(135deg,var(--blue-700),var(--blue-800)); color:#fff; padding:1.8rem 1.2rem; min-height:164px; }
.hero-title{ font-weight:800; }
.text-white-70{ color:rgba(255,255,255,.85)!important; }
.hero-tabs{ background:#fff; padding:.6rem .8rem; border:1px solid rgba(30,64,175,.18); border-top:0; }

/* GLASS card */
.card-glass{
  backdrop-filter: blur(6px);
  background: var(--glass-bg) !important;
  border: 1px solid var(--glass-bd) !important;
  border-radius: 12px !important;
  box-shadow: var(--glass-shadow);
}
.card-header{ background:#f8fafc; border-bottom:1px solid #e2e8f0; }

/* Helpers */
.text-ink{ color:var(--ink)!important; }
.bg-blue-soft{ background:var(--blue-200); color:var(--blue-800); border-radius:12px; padding:.45rem .7rem; font-weight:600; font-size:.85rem; }
.bg-success-subtle{ background:#d1fae5!important; color:#065f46!important; }
.bg-primary-subtle{ background:#dbeafe!important; color:var(--blue-700)!important; }

/* Inputs & buttons */
.form-control-soft{ border:2px solid #e2e8f0; border-radius:10px; }
.form-control-soft:focus{ border-color:var(--blue-600); box-shadow:0 0 0 .2rem rgba(37,99,235,.12); }
.btn{ border-radius:10px; font-weight:700; }
.btn-success{ box-shadow:0 6px 18px rgba(16,185,129,.18); }
.btn-outline-primary{ border-color:var(--blue-400); color:var(--blue-700); }
.btn-outline-primary:hover{ background:var(--blue-600); color:#fff; border-color:var(--blue-600); }

/* Price bubble + amount animation */
.price-bubble{ display:inline-block; padding:.35rem .75rem; background:rgba(255,255,255,.15); border:1px solid rgba(255,255,255,.3); border-radius:999px; font-weight:800; }
#boxTotal,#mobTotal,.price-bubble{ transition:all .3s ease; }
.amount-changed{ animation:amountPulse .6s ease-in-out; }
@keyframes amountPulse{ 0%{transform:scale(1);} 50%{transform:scale(1.05); color:#10b981;} 100%{transform:scale(1);} }

/* Mobile sticky CTA */
.mobile-sticky{ position:sticky; bottom:0; left:0; right:0; margin-top:12px; z-index:1030; }
.mobile-sticky__inner{
  display:flex; gap:.75rem; align-items:center; justify-content:space-between;
  background:#fff; border-top:1px solid #e5e7eb; padding:.75rem .9rem; box-shadow:0 -6px 18px rgba(0,0,0,.06);
}
.mobile-total{ font-size:1.25rem; }

/* Alerts */
.alert{ border-radius:10px; border:none; font-size:.9rem; }
.alert-success{ background:linear-gradient(135deg,#10b981 0%,#059669 100%); color:#fff; }
.alert-danger{  background:linear-gradient(135deg,#ef4444 0%,#dc2626 100%); color:#fff; }
.alert-warning{ background:linear-gradient(135deg,#f59e0b 0%,#d97706 100%); color:#fff; }
.alert-info{    background:#e0f2fe; color:#0c4a6e; }

/* Compact on mobile */
@media (max-width:575.98px){
  .card-hero .hero-body{ padding:1.4rem 1rem; min-height:150px; }
  .hero-title{ font-size:1.25rem; }
}
</style>

<script>
(function(){
  'use strict';

  const CONFIG = {
    eventId: parseInt(document.getElementById('eventId').value) || 0,
    baseAmount: parseFloat(document.getElementById('baseAmount').value) || 0,
    siteUrl: '<?= site_url() ?>',
    csrfTokenName: '<?= csrf_token() ?>'
  };

  let currentAmount   = CONFIG.baseAmount;
  let appliedVoucher  = null;
  let voucherValidated= false;
  let paying          = false;

  const els = {
    voucherCode: document.getElementById('voucherCode'),
    btnCheckVoucher: document.getElementById('btnCheckVoucher'),
    voucherSpinner: document.getElementById('voucherSpinner'),
    voucherResult: document.getElementById('voucherResult'),
    voucherBtnText: document.getElementById('voucherBtnText'),

    btnProceed: document.getElementById('btnProceed'),
    btnProceedText: document.getElementById('btnProceedText'),
    btnProceedMobile: document.getElementById('btnProceedMobile'),
    btnProceedMobileText: document.getElementById('btnProceedMobileText'),

    priceTop: document.getElementById('priceTop'),
    boxTotal: document.getElementById('boxTotal'),
    mobTotal: document.getElementById('mobTotal'),

    discountRow: document.getElementById('discountRow'),
    discountAmount: document.getElementById('discountAmount'),
    voucherCodeDisplay: document.getElementById('voucherCodeDisplay'),

    csrfToken: document.getElementById('csrfToken')
  };

  function fmt(a){ return 'Rp ' + (Math.max(0, Math.round(a))||0).toLocaleString('id-ID'); }
  function getCsrf(){ return els.csrfToken ? els.csrfToken.value : '<?= csrf_hash() ?>'; }
  function setCsrf(t){ if(t && els.csrfToken) els.csrfToken.value = t; }

  function renderAmount(){
    const f = fmt(currentAmount);
    [els.priceTop, els.boxTotal, els.mobTotal].forEach(el => {
      el.textContent = f;
      el.classList.add('amount-changed');
      setTimeout(()=>el.classList.remove('amount-changed'), 600);
    });

    const zero = currentAmount <= 0;
    if (zero){
      if (els.btnProceedText) els.btnProceedText.textContent = 'Selesaikan (Total Rp 0)';
      if (els.btnProceedMobileText) els.btnProceedMobileText.textContent = 'Selesaikan (Rp 0)';
      els.btnProceed?.setAttribute('data-zero','1');
      els.btnProceedMobile?.setAttribute('data-zero','1');
    } else {
      if (els.btnProceedText) els.btnProceedText.textContent = 'Lanjut ke Pembayaran Digital';
      if (els.btnProceedMobileText) els.btnProceedMobileText.textContent = 'Bayar Digital';
      els.btnProceed?.removeAttribute('data-zero');
      els.btnProceedMobile?.removeAttribute('data-zero');
    }
  }

  function setBtnLoading(on){
    paying = on;
    [els.btnProceed, els.btnProceedMobile].forEach(b=>{
      if(!b) return;
      b.disabled = on;
      b.classList.toggle('disabled', on);
    });
  }

  function showVoucherResult(msg, type='info', icon=''){
    const cls = {success:'alert-success',error:'alert-danger',danger:'alert-danger',warning:'alert-warning',info:'alert-info'}[type] || 'alert-info';
    const ic  = icon ? `<i class="bi bi-${icon} me-2"></i>` : '';
    els.voucherResult.innerHTML = `<div class="alert ${cls} mb-0">${ic}${msg}</div>`;
  }
  function clearVoucher(){ els.voucherResult.innerHTML=''; }

  function resetVoucher(){
    appliedVoucher = null; voucherValidated=false; currentAmount = CONFIG.baseAmount;
    els.discountRow.style.display='none';
    els.voucherBtnText.textContent='Cek Voucher';
    els.btnCheckVoucher.classList.remove('btn-success'); els.btnCheckVoucher.classList.add('btn-outline-primary');
    clearVoucher(); renderAmount();
  }

  async function validateVoucher(){
    if(!els.voucherCode) return;
    const code = (els.voucherCode.value||'').trim();
    if(!code){ showVoucherResult('Masukkan kode voucher terlebih dahulu.','warning','exclamation-triangle'); return; }

    els.voucherSpinner.classList.remove('d-none');
    els.btnCheckVoucher.disabled = true;
    els.voucherBtnText.textContent = 'Validating...';

    try{
      const fd = new FormData();
      fd.append('event_id', CONFIG.eventId);
      fd.append('kode_voucher', code);
      fd.append(CONFIG.csrfTokenName, getCsrf());

      const res = await fetch(CONFIG.siteUrl + '/presenter/pembayaran/validate-voucher', {
        method:'POST', body:fd, headers:{'X-Requested-With':'XMLHttpRequest'}
      });
      if(!res.ok) throw new Error(`HTTP ${res.status}`);

      const json = await res.json();
      setCsrf(json.token);

      if(json.ok){
        voucherValidated = true;
        appliedVoucher = { id: json.voucher_id, code: json.code };
        const final = Math.max(0, parseFloat(json.final_price)||CONFIG.baseAmount);
        const disc  = Math.max(0, CONFIG.baseAmount - final);
        currentAmount = final;

        els.discountAmount.textContent = '- ' + fmt(disc);
        els.voucherCodeDisplay.textContent = `(${json.code})`;
        els.discountRow.style.display='flex';

        els.voucherBtnText.textContent='Voucher Diterapkan';
        els.btnCheckVoucher.classList.remove('btn-outline-primary'); els.btnCheckVoucher.classList.add('btn-success');

        showVoucherResult(`Voucher berhasil diterapkan! Diskon: ${fmt(disc)}`,'success','check-circle');
        renderAmount();
      }else{
        resetVoucher();
        showVoucherResult(json.message || 'Voucher tidak valid','error','x-circle');
      }
    }catch(e){
      resetVoucher();
      showVoucherResult('Terjadi kesalahan saat memvalidasi voucher.','error','exclamation-triangle');
      console.error(e);
    }finally{
      els.voucherSpinner.classList.add('d-none');
      els.btnCheckVoucher.disabled = false;
      els.voucherBtnText.textContent = voucherValidated ? 'Voucher Diterapkan' : 'Cek Voucher';
    }
  }

  async function finalizeZeroPayment(){
    window.location.href = CONFIG.siteUrl + '/presenter/pembayaran/finish?order_id=FREE-'+Date.now()+'&status=success';
  }

  async function processPayment(){
    if(paying) return;
    setBtnLoading(true);
    try{
      if(currentAmount <= 0 || (this && this.getAttribute && this.getAttribute('data-zero') === '1')){
        await finalizeZeroPayment();
        return;
      }

      const fd = new FormData();
      fd.append('event_id', CONFIG.eventId);
      fd.append('voucher_code', appliedVoucher?.code || '');
      fd.append(CONFIG.csrfTokenName, getCsrf());

      const res = await fetch(CONFIG.siteUrl + '/presenter/pembayaran/process-payment', {
        method:'POST', body:fd, headers:{'X-Requested-With':'XMLHttpRequest'}
      });
      if(!res.ok) throw new Error(`HTTP ${res.status}`);

      const json = await res.json();
      if(!json.success) throw new Error(json.message || 'Gagal membuat transaksi');

      if(typeof snap === 'undefined') throw new Error('Midtrans Snap tidak tersedia.');
      if(!json.snap_token) throw new Error('Token pembayaran tidak ditemukan.');

      snap.pay(json.snap_token, {
        onSuccess: function(r){
          const oid = r.order_id || r.transaction_id;
          window.location.href = oid
            ? `${CONFIG.siteUrl}/presenter/pembayaran/finish?order_id=${oid}&status=success`
            : `${CONFIG.siteUrl}/presenter/pembayaran?success=1`;
        },
        onPending: function(r){
          const oid = r.order_id || r.transaction_id;
          window.location.href = oid
            ? `${CONFIG.siteUrl}/presenter/pembayaran/finish?order_id=${oid}&status=pending`
            : `${CONFIG.siteUrl}/presenter/pembayaran?pending=1`;
        },
        onError: function(r){
          const msg = r.status_message || r.error_message || 'Terjadi kesalahan dalam pembayaran';
          alert('Pembayaran gagal: ' + msg);
        },
        onClose: function(){ /* user menutup popup */ }
      });

    }catch(e){
      console.error(e);
      alert('Gagal memproses pembayaran: ' + e.message);
    }finally{
      setBtnLoading(false);
    }
  }

  function init(){
    // Voucher
    document.getElementById('btnCheckVoucher')?.addEventListener('click', validateVoucher);
    document.getElementById('voucherCode')?.addEventListener('input', function(){
      if(!this.value.trim() && voucherValidated) resetVoucher();
    });
    document.getElementById('voucherCode')?.addEventListener('keypress', e=>{
      if(e.key==='Enter'){ e.preventDefault(); validateVoucher(); }
    });

    // Payment
    els.btnProceed?.addEventListener('click', processPayment);
    els.btnProceedMobile?.addEventListener('click', processPayment);

    // Sanity Snap
    setTimeout(()=>{ if(typeof snap === 'undefined'){ console.warn('Midtrans Snap not loaded'); } }, 1200);

    renderAmount();
  }

  document.readyState === 'loading' ? document.addEventListener('DOMContentLoaded', init) : init();
})();
</script>
