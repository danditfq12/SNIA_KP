<?php
$title = $title ?? 'Instruksi Pembayaran';
$event = $event ?? [];
$basePrice = (int)($basePrice ?? 0);
$midtransClientKey = $midtrans_client_key ?? '';
$isProduction = $is_production ?? false;

$evTitle = $event['title'] ?? '-';
$evDate = isset($event['event_date']) ? date('d M Y', strtotime($event['event_date'])) : '-';
$evTime = $event['event_time'] ?? '-';
$amountF = number_format($basePrice, 0, ',', '.');
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
          <div class="text-white-50 small"><?= esc($evTitle) ?></div>
        </div>
        <div class="text-start text-md-end">
          <small class="text-white-50 d-block">Total Saat Ini</small>
          <div class="price-bubble" id="priceTop">Rp <?= $amountF ?></div>
        </div>
      </div>

      <div class="row g-3">
        <!-- Kiri: Informasi -->
        <div class="col-12 col-lg-7">
          <div class="card shadow-sm mb-3">
            <div class="card-header bg-gradient-primary text-white py-3">
              <h5 class="mb-0 d-flex align-items-center"><i class="bi bi-info-circle me-2"></i>Informasi Event</h5>
            </div>
            <div class="card-body p-3 p-md-4">
              <div class="row g-3">
                <div class="col-md-6">
                  <div class="mini-label text-muted">Tanggal Event</div>
                  <div class="fs-6 fw-semibold"><?= esc($evDate) ?></div>
                </div>
                <div class="col-md-6">
                  <div class="mini-label text-muted">Waktu</div>
                  <div class="fs-6 fw-semibold"><?= esc($evTime) ?></div>
                </div>
                <div class="col-md-6">
                  <div class="mini-label text-muted">Mode Kehadiran</div>
                  <div class="fs-6 fw-semibold"><span class="badge bg-primary">Offline</span></div>
                </div>
                <div class="col-md-6">
                  <div class="mini-label text-muted">Role</div>
                  <div class="fs-6 fw-semibold"><span class="badge bg-success">Presenter</span></div>
                </div>
              </div>
            </div>
          </div>

          <!-- Voucher Section -->
          <div class="card border-primary mb-4" id="voucherSection">
            <div class="card-header bg-primary text-white">
              <h6 class="mb-0">
                <i class="bi bi-tag me-2"></i>
                Punya Kode Voucher?
              </h6>
            </div>
            <div class="card-body">
              <div class="row align-items-end g-2">
                <div class="col">
                  <input type="text" id="voucherCode" class="form-control" 
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
          <div class="card shadow-sm sticky-lg-top" style="top:84px;">
            <div class="card-header bg-light">
              <strong>Ringkasan Pembayaran</strong>
            </div>
            <div class="card-body p-3 p-md-4">
              <div class="mb-2 d-flex justify-content-between">
                <span>Harga Presenter (Offline)</span>
                <span class="fw-semibold" id="basePrice">Rp <?= $amountF ?></span>
              </div>
              <div class="mb-2 d-flex justify-content-between text-success" id="discountRow" style="display: none !important;">
                <span>Diskon Voucher <span id="voucherCodeDisplay"></span></span>
                <span class="fw-semibold" id="discountAmount">- Rp 0</span>
              </div>
              <hr class="my-2">
              <div class="d-flex justify-content-between align-items-center">
                <div class="mini-label text-muted">Total Dibayar</div>
                <div class="fs-4 fw-bold" id="boxTotal">Rp <?= $amountF ?></div>
              </div>

              <div class="mt-3">
                <p class="text-muted small">
                  Pembayaran menggunakan sistem digital Midtrans untuk verifikasi otomatis.
                </p>
              </div>

              <div class="d-grid gap-2">
                <button type="button" id="btnProceed" class="btn btn-success btn-lg">
                  <i class="bi bi-credit-card me-1"></i> Lanjut ke Pembayaran Digital
                </button>
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
                <div class="mobile-total fw-bold" id="mobTotal">Rp <?= $amountF ?></div>
              </div>
              <button type="button" class="btn btn-success btn-lg flex-fill" id="btnProceedMobile">
                Bayar Digital
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="alert alert-info mt-3">
        <i class="bi bi-info-circle me-2"></i>
        <strong>Pembayaran Digital:</strong> Menggunakan Midtrans untuk pembayaran yang aman dan verifikasi otomatis. 
        Mendukung berbagai metode: Credit Card, Debit Card, E-Wallet (GoPay, ShopeePay, OVO, DANA), Bank Transfer, dan QRIS.
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
:root { --primary:#2563eb; --primary-deep:#1e40af; --info:#06b6d4; }

.header-section.header-blue {
  background: linear-gradient(135deg, var(--primary), var(--primary-deep));
  color: #fff; 
  padding: 20px; 
  border-radius: 16px; 
  box-shadow: 0 8px 28px rgba(0,0,0,.12);
}

.welcome-text { 
  font-weight: 700; 
  font-size: 1.25rem; 
}

.price-bubble {
  display: inline-block; 
  padding: .35rem .75rem; 
  background: rgba(255,255,255,.15);
  border: 1px solid rgba(255,255,255,.3); 
  border-radius: 999px; 
  font-weight: 700;
}

.card { border-radius: 14px; }
.bg-gradient-primary { background: linear-gradient(135deg, var(--primary), var(--info))!important; }
.mini-label { font-size: .85rem; }

/* Mobile sticky CTA */
.mobile-sticky { 
  position: sticky; 
  bottom: 0; 
  left: 0; 
  right: 0; 
  margin-top: 12px; 
  z-index: 1030; 
}

.mobile-sticky__inner {
  display: flex; 
  gap: .75rem; 
  align-items: center; 
  justify-content: space-between;
  background: #ffffff; 
  border-top: 1px solid #e5e7eb; 
  padding: .75rem .9rem;
  box-shadow: 0 -6px 18px rgba(0,0,0,.06);
}

.mobile-total { font-size: 1.25rem; }

.btn { border-radius: 12px; }
.btn-success { box-shadow: 0 6px 18px rgba(16,185,129,.18); }
.btn-outline-secondary { border-color: #cbd5e1; }

/* Alert styles for voucher */
.alert {
  border-radius: 8px;
  border: none;
  font-size: 0.875rem;
}

.alert-success {
  background: linear-gradient(135deg, #10b981 0%, #059669 100%);
  color: white;
}

.alert-danger {
  background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
  color: white;
}

.alert-warning {
  background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
  color: white;
}

/* Animation for amount change */
.price-bubble, #boxTotal, #mobTotal {
  transition: all 0.3s ease;
}

.amount-changed {
  animation: amountPulse 0.6s ease-in-out;
}

@keyframes amountPulse {
  0% { transform: scale(1); }
  50% { transform: scale(1.05); color: #10b981; }
  100% { transform: scale(1); }
}
</style>

<script>
(function() {
  'use strict';

  // Configuration
  const CONFIG = {
    eventId: parseInt(document.getElementById('eventId').value) || 0,
    baseAmount: parseFloat(document.getElementById('baseAmount').value) || 0,
    midtransClientKey: '<?= esc($midtransClientKey) ?>',
    isProduction: <?= $isProduction ? 'true' : 'false' ?>,
    siteUrl: '<?= site_url() ?>',
    csrfTokenName: '<?= csrf_token() ?>'
  };

  // State
  let currentAmount = CONFIG.baseAmount;
  let appliedVoucher = null;
  let voucherValidated = false;

  // DOM Elements
  const elements = {
    voucherCode: document.getElementById('voucherCode'),
    btnCheckVoucher: document.getElementById('btnCheckVoucher'),
    voucherSpinner: document.getElementById('voucherSpinner'),
    voucherResult: document.getElementById('voucherResult'),
    voucherBtnText: document.getElementById('voucherBtnText'),
    
    btnProceed: document.getElementById('btnProceed'),
    btnProceedMobile: document.getElementById('btnProceedMobile'),
    
    basePrice: document.getElementById('basePrice'),
    discountRow: document.getElementById('discountRow'),
    discountAmount: document.getElementById('discountAmount'),
    voucherCodeDisplay: document.getElementById('voucherCodeDisplay'),
    
    priceTop: document.getElementById('priceTop'),
    boxTotal: document.getElementById('boxTotal'),
    mobTotal: document.getElementById('mobTotal'),
    
    csrfToken: document.getElementById('csrfToken')
  };

  // Utility Functions
  function formatCurrency(amount) {
    return 'Rp ' + amount.toLocaleString('id-ID');
  }

  function updateAmountDisplay() {
    const formatted = formatCurrency(currentAmount);
    
    elements.priceTop.textContent = formatted;
    elements.boxTotal.textContent = formatted;
    elements.mobTotal.textContent = formatted;
    
    // Add animation
    [elements.priceTop, elements.boxTotal, elements.mobTotal].forEach(el => {
      el.classList.add('amount-changed');
      setTimeout(() => el.classList.remove('amount-changed'), 600);
    });
  }

  function getCurrentCsrfToken() {
    return elements.csrfToken ? elements.csrfToken.value : '<?= csrf_hash() ?>';
  }

  function updateCsrfToken(newToken) {
    if (newToken && elements.csrfToken) {
      elements.csrfToken.value = newToken;
    }
  }

  function showVoucherResult(message, type = 'info', icon = '') {
    const alertClass = {
      'success': 'alert-success',
      'error': 'alert-danger', 
      'danger': 'alert-danger',
      'warning': 'alert-warning',
      'info': 'alert-info'
    }[type] || 'alert-info';

    const iconHtml = icon ? `<i class="bi bi-${icon} me-2"></i>` : '';
    
    elements.voucherResult.innerHTML = `
      <div class="alert ${alertClass} mb-0">
        ${iconHtml}${message}
      </div>
    `;
  }

  function clearVoucherResult() {
    elements.voucherResult.innerHTML = '';
  }

  function resetVoucherState() {
    appliedVoucher = null;
    currentAmount = CONFIG.baseAmount;
    voucherValidated = false;
    updateAmountDisplay();
    clearVoucherResult();
    
    // Hide discount row
    elements.discountRow.style.display = 'none';
    
    // Reset button
    elements.voucherBtnText.textContent = 'Cek Voucher';
    elements.btnCheckVoucher.classList.remove('btn-success');
    elements.btnCheckVoucher.classList.add('btn-outline-primary');
  }

  function setButtonLoading(button, spinner, textEl, loading, loadingText = 'Loading...') {
    if (loading) {
      spinner.classList.remove('d-none');
      textEl.textContent = loadingText;
      button.disabled = true;
    } else {
      spinner.classList.add('d-none');
      button.disabled = false;
    }
  }

  // Voucher Validation
  async function validateVoucher() {
    const code = elements.voucherCode.value.trim();
    
    if (!code) {
      showVoucherResult('Masukkan kode voucher terlebih dahulu.', 'warning', 'exclamation-triangle');
      return;
    }

    setButtonLoading(elements.btnCheckVoucher, elements.voucherSpinner, elements.voucherBtnText, true, 'Validating...');

    try {
      const formData = new FormData();
      formData.append('event_id', CONFIG.eventId);
      formData.append('kode_voucher', code);
      formData.append(CONFIG.csrfTokenName, getCurrentCsrfToken());

      const response = await fetch(CONFIG.siteUrl + '/presenter/pembayaran/validate-voucher', {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      const result = await response.json();
      updateCsrfToken(result.token);

      if (result.ok) {
        currentAmount = result.final_price;
        appliedVoucher = {
          id: result.voucher_id,
          code: result.code
        };
        
        const discount = CONFIG.baseAmount - result.final_price;
        voucherValidated = true;
        
        // Show discount
        elements.discountAmount.textContent = '- ' + formatCurrency(discount);
        elements.voucherCodeDisplay.textContent = `(${result.code})`;
        elements.discountRow.style.display = 'flex';
        
        showVoucherResult(
          `Voucher berhasil diterapkan! Diskon: ${formatCurrency(discount)}`, 
          'success', 
          'check-circle'
        );
        updateAmountDisplay();
        
        // Change button appearance
        elements.voucherBtnText.textContent = 'Voucher Diterapkan';
        elements.btnCheckVoucher.classList.remove('btn-outline-primary');
        elements.btnCheckVoucher.classList.add('btn-success');
        
      } else {
        resetVoucherState();
        showVoucherResult(result.message || 'Voucher tidak valid', 'error', 'x-circle');
      }

    } catch (error) {
      console.error('Voucher validation error:', error);
      resetVoucherState();
      showVoucherResult('Terjadi kesalahan saat memvalidasi voucher: ' + error.message, 'error', 'exclamation-triangle');
    } finally {
      setButtonLoading(elements.btnCheckVoucher, elements.voucherSpinner, elements.voucherBtnText, false);
      elements.voucherBtnText.textContent = voucherValidated ? 'Voucher Diterapkan' : 'Cek Voucher';
    }
  }

  // Payment Processing
  async function processPayment() {
    try {
      const formData = new FormData();
      formData.append('event_id', CONFIG.eventId);
      formData.append('voucher_code', appliedVoucher?.code || '');
      formData.append(CONFIG.csrfTokenName, getCurrentCsrfToken());

      const response = await fetch(CONFIG.siteUrl + '/presenter/pembayaran/process-payment', {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest'
        }
      });

      if (!response.ok) {
        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
      }

      const result = await response.json();

      if (result.success) {
        await handleMidtransPayment(result);
      } else {
        throw new Error(result.message || 'Terjadi kesalahan tidak dikenal');
      }

    } catch (error) {
      console.error('Payment processing error:', error);
      alert('Gagal memproses pembayaran: ' + error.message);
    }
  }

  // Midtrans Payment Handling
  async function handleMidtransPayment(result) {
    try {
      if (typeof snap === 'undefined') {
        throw new Error('Midtrans Snap tidak tersedia. Periksa koneksi internet Anda.');
      }

      if (!result.snap_token) {
        throw new Error('Token pembayaran tidak tersedia.');
      }

      const snapConfig = {
        onSuccess: function(result) {
          console.log('Payment success:', result);
          const orderId = result.order_id || result.transaction_id;
          const redirectUrl = orderId ? 
            `${CONFIG.siteUrl}/presenter/pembayaran/finish?order_id=${orderId}&status=success` :
            `${CONFIG.siteUrl}/presenter/pembayaran?success=1`;
          
          window.location.href = redirectUrl;
        },
        onPending: function(result) {
          console.log('Payment pending:', result);
          const orderId = result.order_id || result.transaction_id;
          const redirectUrl = orderId ? 
            `${CONFIG.siteUrl}/presenter/pembayaran/finish?order_id=${orderId}&status=pending` :
            `${CONFIG.siteUrl}/presenter/pembayaran?pending=1`;
          
          window.location.href = redirectUrl;
        },
        onError: function(result) {
          console.error('Payment error:', result);
          const errorMsg = result.status_message || 
                         result.error_message || 
                         'Terjadi kesalahan dalam pembayaran';
          
          alert('Pembayaran gagal: ' + errorMsg);
        },
        onClose: function() {
          console.log('Payment popup closed by user');
        }
      };

      snap.pay(result.snap_token, snapConfig);

    } catch (error) {
      console.error('Midtrans handling error:', error);
      alert('Gagal membuka halaman pembayaran: ' + error.message);
    }
  }

  // Event Listeners
  function initEventListeners() {
    // Voucher validation
    elements.btnCheckVoucher.addEventListener('click', validateVoucher);
    
    // Reset voucher on input change
    elements.voucherCode.addEventListener('input', function() {
      if (!this.value.trim() && voucherValidated) {
        resetVoucherState();
      }
    });

    // Enter key for voucher validation
    elements.voucherCode.addEventListener('keypress', function(e) {
      if (e.key === 'Enter') {
        e.preventDefault();
        validateVoucher();
      }
    });

    // Payment processing
    elements.btnProceed.addEventListener('click', processPayment);
    elements.btnProceedMobile.addEventListener('click', processPayment);
  }

  // Initialize
  function initializePage() {
    console.log('Initializing presenter payment page with config:', CONFIG);

    if (!CONFIG.eventId) {
      alert('Data event tidak lengkap. Silakan coba lagi.');
      return;
    }

    // Check Midtrans availability
    setTimeout(() => {
      if (typeof snap === 'undefined') {
        console.warn('Midtrans Snap not loaded properly');
        alert('Sistem pembayaran sedang bermasalah. Silakan coba lagi nanti.');
      } else {
        console.log('Midtrans Snap loaded successfully');
      }
    }, 1000);

    initEventListeners();
    updateAmountDisplay();
  }

  // Initialize when DOM is ready
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initializePage);
  } else {
    initializePage();
  }

})();
</script>