<?php
// app/Views/role/presenter/pembayaran/create.php
// Presenter Payment Create View - Midtrans Only

$title = 'Pilihan Pembayaran';
$amount = (float)($amount ?? 0);
$event = $event ?? [];
$user = $user ?? [];

$evTitle = $event['title'] ?? '-';
$evDate = isset($event['event_date']) ? date('d M Y', strtotime($event['event_date'])) : '-';
$evTime = $event['event_time'] ?? '-';
$amountF = number_format($amount, 0, ',', '.');

$midtransClientKey = $midtrans_client_key ?? '';
$isProduction = $is_production ?? false;
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <!-- Back Button -->
      <a href="<?= site_url('presenter/pembayaran/instruction/'.(int)($event['id'] ?? 0)) ?>" class="btn btn-sm btn-outline-secondary mb-3">
        <i class="bi bi-arrow-left"></i> Kembali
      </a>

      <div class="card shadow-sm border-0">
        <div class="card-body">

          <!-- Event Summary Header -->
          <div class="payment-hero mb-4">
            <div class="payment-tags">
              <span class="payment-tag">
                <i class="bi bi-calendar-event"></i> <?= esc($evDate) ?>
              </span>
              <span class="payment-tag">
                <i class="bi bi-clock"></i> <?= esc($evTime) ?>
              </span>
              <span class="payment-tag">
                <i class="bi bi-person-badge"></i> Presenter
              </span>
              <span class="payment-tag">
                <i class="bi bi-geo-alt"></i> Offline
              </span>
            </div>
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
              <div class="payment-title mb-0"><?= esc($evTitle) ?></div>
              <div class="text-end">
                <div class="small opacity-75">Total Pembayaran</div>
                <div class="payment-amount" id="currentAmount">Rp <?= $amountF ?></div>
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

          <!-- Payment Method Info -->
          <h5 class="mb-3">
            <i class="bi bi-credit-card me-2"></i>
            Metode Pembayaran
          </h5>

          <div class="row g-3 mb-4">
            <div class="col-12">
              <div class="payment-method-card selected" data-method="midtrans">
                <div class="payment-header">
                  <div class="payment-icon bg-primary">
                    <i class="bi bi-credit-card-2-front text-white"></i>
                  </div>
                  <div class="flex-grow-1">
                    <h6 class="mb-1">Pembayaran Digital</h6>
                    <small class="text-muted">Instant verification untuk presenter</small>
                  </div>
                  <div class="ms-auto">
                    <div class="form-check">
                      <input class="form-check-input" type="radio" name="payment_method" 
                             id="midtrans" value="midtrans" checked>
                    </div>
                  </div>
                </div>
                <div class="payment-body">
                  <div class="payment-logos mb-2">
                    <img src="https://cdn.jsdelivr.net/gh/midtrans/midtrans-logo@main/source/png/logo.png" 
                         alt="Midtrans" height="20" class="me-2">
                    <small class="text-muted">Powered by Midtrans</small>
                  </div>
                  <div class="payment-methods mb-2">
                    <small class="text-muted d-block">
                      <i class="bi bi-check-circle text-success me-1"></i>
                      Credit Card, Debit Card
                    </small>
                    <small class="text-muted d-block">
                      <i class="bi bi-check-circle text-success me-1"></i>
                      GoPay, ShopeePay, OVO, DANA
                    </small>
                    <small class="text-muted d-block">
                      <i class="bi bi-check-circle text-success me-1"></i>
                      Bank Transfer, QRIS
                    </small>
                  </div>
                  <div class="payment-benefits">
                    <div class="benefit-item">
                      <i class="bi bi-lightning-charge text-warning"></i>
                      <span>Verifikasi otomatis</span>
                    </div>
                    <div class="benefit-item">
                      <i class="bi bi-shield-check text-success"></i>
                      <span>Aman & terenkripsi</span>
                    </div>
                    <div class="benefit-item">
                      <i class="bi bi-award text-primary"></i>
                      <span>Khusus untuk presenter</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Action Buttons -->
          <div class="d-grid d-md-flex gap-2">
            <button type="button" id="btnProceed" class="btn btn-primary btn-lg">
              <span class="spinner-border spinner-border-sm me-2 d-none" id="proceedSpinner"></span>
              <span id="proceedBtnText">Lanjutkan Pembayaran Digital</span>
            </button>
            <a href="<?= site_url('presenter/pembayaran/instruction/'.(int)($event['id'] ?? 0)) ?>" class="btn btn-outline-secondary btn-lg">
              <i class="bi bi-arrow-left me-1"></i> Kembali ke Instruksi
            </a>
          </div>

        </div>
      </div>

    </div>
  </main>
</div>

<!-- Hidden Form Data -->
<input type="hidden" id="csrfToken" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
<input type="hidden" id="eventId" value="<?= (int)($event['id'] ?? 0) ?>">
<input type="hidden" id="baseAmount" value="<?= $amount ?>">

<!-- Midtrans Snap Script -->
<script type="text/javascript" 
        src="<?= $isProduction ? 'https://app.midtrans.com/snap/snap.js' : 'https://app.sandbox.midtrans.com/snap/snap.js' ?>"
        data-client-key="<?= esc($midtransClientKey) ?>">
</script>

<?= $this->include('partials/footer') ?>

<style>
/* Enhanced Payment UI Styles */
.payment-hero {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 16px;
    color: white;
    padding: 20px;
    box-shadow: 0 8px 32px rgba(102, 126, 234, 0.3);
}

.payment-tags {
    display: flex;
    gap: 8px;
    flex-wrap: wrap;
    margin-bottom: 12px;
}

.payment-tag {
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    color: white;
    border-radius: 20px;
    padding: 4px 12px;
    font-size: 0.875rem;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.payment-title {
    font-weight: 700;
    font-size: clamp(1.1rem, 4vw, 1.5rem);
    line-height: 1.3;
}

.payment-amount {
    font-weight: 800;
    font-size: clamp(1.5rem, 6vw, 2rem);
    line-height: 1.2;
    transition: all 0.3s ease;
}

.payment-method-card {
    border: 2px solid #3b82f6;
    border-radius: 12px;
    padding: 20px;
    cursor: pointer;
    transition: all 0.3s ease;
    height: 100%;
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
    position: relative;
    overflow: hidden;
    box-shadow: 0 8px 25px rgba(59, 130, 246, 0.2);
}

.payment-method-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: -100%;
    width: 100%;
    height: 100%;
    background: linear-gradient(90deg, transparent, rgba(255,255,255,0.4), transparent);
    transition: left 0.5s;
}

.payment-method-card:hover::before {
    left: 100%;
}

.payment-method-card.selected {
    border-color: #3b82f6;
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
    box-shadow: 0 12px 35px rgba(59, 130, 246, 0.25);
    transform: translateY(-2px);
}

.payment-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 15px;
}

.payment-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 24px;
}

.payment-body {
    padding-left: 65px;
}

.payment-logos img {
    transition: all 0.3s ease;
    filter: grayscale(0);
    opacity: 1;
}

.payment-methods {
    margin: 8px 0;
}

.payment-benefits {
    margin-top: 10px;
}

.benefit-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.875rem;
    margin-bottom: 6px;
    color: #6b7280;
}

.benefit-item i {
    flex-shrink: 0;
}

/* Loading states */
.btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
}

.spinner-border-sm {
    width: 1rem;
    height: 1rem;
}

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

/* Responsive adjustments */
@media (max-width: 768px) {
    .payment-hero {
        padding: 16px;
    }
    
    .payment-method-card {
        padding: 16px;
    }
    
    .payment-body {
        padding-left: 0;
        margin-top: 12px;
    }
    
    .payment-header {
        flex-wrap: wrap;
    }
}

/* Animation for amount change */
.payment-amount.amount-changed {
    animation: amountPulse 0.6s ease-in-out;
}

@keyframes amountPulse {
    0% { transform: scale(1); }
    50% { transform: scale(1.05); color: #10b981; }
    100% { transform: scale(1); }
}
</style>

<script>
// Enhanced Payment Processing Script for Presenter
(function() {
    'use strict';

    // Configuration from PHP
    const CONFIG = {
        eventId: parseInt(document.getElementById('eventId').value) || 0,
        baseAmount: parseFloat(document.getElementById('baseAmount').value) || 0,
        midtransClientKey: '<?= esc($midtransClientKey) ?>',
        isProduction: <?= $isProduction ? 'true' : 'false' ?>,
        siteUrl: '<?= site_url() ?>',
        csrfTokenName: '<?= csrf_token() ?>'
    };

    // State management
    let currentAmount = CONFIG.baseAmount;
    let appliedVoucher = null;
    let isProcessing = false;
    let voucherValidated = false;

    // DOM Elements
    const elements = {
        voucherCode: document.getElementById('voucherCode'),
        btnCheckVoucher: document.getElementById('btnCheckVoucher'),
        voucherSpinner: document.getElementById('voucherSpinner'),
        voucherResult: document.getElementById('voucherResult'),
        voucherBtnText: document.getElementById('voucherBtnText'),
        
        btnProceed: document.getElementById('btnProceed'),
        proceedSpinner: document.getElementById('proceedSpinner'),
        proceedBtnText: document.getElementById('proceedBtnText'),
        
        currentAmount: document.getElementById('currentAmount'),
        csrfToken: document.getElementById('csrfToken')
    };

    // Utility Functions
    function formatCurrency(amount) {
        return 'Rp ' + amount.toLocaleString('id-ID');
    }

    function updateAmountDisplay() {
        const amountEl = elements.currentAmount;
        amountEl.textContent = formatCurrency(currentAmount);
        amountEl.classList.add('amount-changed');
        setTimeout(() => amountEl.classList.remove('amount-changed'), 600);
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

    // Enhanced Error Handling
    function handleError(message, error = null) {
        console.error('Payment Error:', message, error);
        
        let userMessage = message;
        if (message.includes('Network') || message.includes('CURL')) {
            userMessage = 'Koneksi bermasalah. Periksa internet Anda dan coba lagi.';
        } else if (message.includes('signature')) {
            userMessage = 'Terjadi kesalahan keamanan. Silakan refresh halaman.';
        }
        
        alert(userMessage);
        
        setButtonLoading(elements.btnProceed, elements.proceedSpinner, elements.proceedBtnText, false);
        isProcessing = false;
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
                
                showVoucherResult(
                    `Voucher berhasil diterapkan! Diskon: ${formatCurrency(discount)}`, 
                    'success', 
                    'check-circle'
                );
                updateAmountDisplay();
                
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

    // Enhanced Payment Processing
    async function processPayment() {
        if (isProcessing) return;
        
        isProcessing = true;
        setButtonLoading(elements.btnProceed, elements.proceedSpinner, elements.proceedBtnText, true, 'Processing...');

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
            handleError('Gagal memproses pembayaran: ' + error.message, error);
        }
    }

    // Enhanced Midtrans Payment Handling
    async function handleMidtransPayment(result) {
        try {
            if (typeof snap === 'undefined') {
                throw new Error('Midtrans Snap tidak tersedia. Periksa koneksi internet Anda.');
            }

            if (!result.snap_token) {
                throw new Error('Token pembayaran tidak tersedia.');
            }

            console.log('Opening Midtrans Snap with token:', result.snap_token.substring(0, 20) + '...');

            const snapConfig = {
                onSuccess: function(result) {
                    console.log('Payment success:', result);
                    isProcessing = false;
                    
                    const orderId = result.order_id || result.transaction_id;
                    const redirectUrl = orderId ? 
                        `${CONFIG.siteUrl}/presenter/pembayaran/finish?order_id=${orderId}&status=success` :
                        `${CONFIG.siteUrl}/presenter/pembayaran?success=1`;
                    
                    window.location.href = redirectUrl;
                },
                onPending: function(result) {
                    console.log('Payment pending:', result);
                    isProcessing = false;
                    
                    const orderId = result.order_id || result.transaction_id;
                    const redirectUrl = orderId ? 
                        `${CONFIG.siteUrl}/presenter/pembayaran/finish?order_id=${orderId}&status=pending` :
                        `${CONFIG.siteUrl}/presenter/pembayaran?pending=1`;
                    
                    window.location.href = redirectUrl;
                },
                onError: function(result) {
                    console.error('Payment error:', result);
                    isProcessing = false;
                    
                    const errorMsg = result.status_message || 
                                   result.error_message || 
                                   'Terjadi kesalahan dalam pembayaran';
                    
                    handleError('Pembayaran gagal: ' + errorMsg, result);
                },
                onClose: function() {
                    console.log('Payment popup closed by user');
                    isProcessing = false;
                    setButtonLoading(elements.btnProceed, elements.proceedSpinner, elements.proceedBtnText, false);
                    elements.proceedBtnText.textContent = 'Lanjutkan Pembayaran Digital';
                }
            };

            snap.pay(result.snap_token, snapConfig);

        } catch (error) {
            console.error('Midtrans handling error:', error);
            handleError('Gagal membuka halaman pembayaran: ' + error.message, error);
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
                elements.voucherBtnText.textContent = 'Cek Voucher';
                elements.btnCheckVoucher.classList.remove('btn-success');
                elements.btnCheckVoucher.classList.add('btn-outline-primary');
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
    }

    // Enhanced Page Initialization
    function initializePage() {
        console.log('Initializing presenter payment page with config:', CONFIG);

        if (!CONFIG.eventId) {
            handleError('Data event tidak lengkap. Silakan coba lagi.');
            return;
        }

        // Check Midtrans availability after a delay
        setTimeout(() => {
            if (typeof snap === 'undefined') {
                console.warn('Midtrans Snap not loaded properly');
                handleError('Sistem pembayaran tidak tersedia. Silakan coba lagi nanti.');
            } else {
                console.log('Midtrans Snap loaded successfully');
            }
        }, 1000);

        initEventListeners();
        updateAmountDisplay();
    }

    // Global error handler
    window.addEventListener('error', function(event) {
        console.error('Global error:', event.error);
        if (isProcessing) {
            handleError('Terjadi kesalahan tak terduga. Silakan refresh halaman dan coba lagi.');
        }
    });

    // Initialize when DOM is ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initializePage);
    } else {
        initializePage();
    }

})();
</script>