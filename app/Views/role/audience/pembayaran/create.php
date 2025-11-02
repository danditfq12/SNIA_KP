<?php
// app/Views/role/audience/pembayaran/create.php
// COMPLETE FIXED Audience Pembayaran Create View - MIDTRANS ONLY
// Includes: Enhanced voucher validation, proper Midtrans integration, error handling

$title = 'Pilihan Pembayaran';
$reg = $reg ?? [];
$amount = (float)($amount ?? 0);
$event = $event ?? [];
$user = $user ?? [];

$evTitle = $event['title'] ?? '-';
$evDate = isset($event['event_date']) ? date('d M Y', strtotime($event['event_date'])) : '-';
$evTime = $event['event_time'] ?? '-';
$mode = strtoupper($reg['mode_kehadiran'] ?? '-');
$amountF = number_format($amount, 0, ',', '.');

$midtransClientKey = $midtrans_client_key ?? '';
$isProduction = $is_production ?? false;
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_audience') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <!-- Back Button -->
      <a href="<?= site_url('audience/pembayaran/instruction/'.(int)($reg['id'] ?? 0)) ?>" class="btn btn-sm btn-outline-secondary mb-3">
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
                <i class="bi bi-broadcast"></i> <?= esc($mode) ?>
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

          <!-- Payment Method Section - Midtrans Only -->
          <h5 class="mb-3">
            <i class="bi bi-credit-card me-2"></i>
            Metode Pembayaran
          </h5>

          <div class="row g-3 mb-4">
            <!-- Midtrans Payment Option -->
            <div class="col-12">
              <div class="payment-method-card selected" id="midtransCard">
                <div class="payment-header">
                  <div class="payment-icon bg-primary">
                    <i class="bi bi-credit-card-2-front text-white"></i>
                  </div>
                  <div class="flex-grow-1">
                    <h6 class="mb-1">Pembayaran Digital</h6>
                    <small class="text-muted">Instant verification dengan berbagai metode pembayaran</small>
                  </div>
                  <div class="ms-auto">
                    <div class="payment-check-badge">
                      <i class="bi bi-check-circle-fill text-primary"></i>
                    </div>
                  </div>
                </div>
                <div class="payment-body">
                  <div class="payment-logos mb-3">
                    <img src="https://cdn.jsdelivr.net/gh/midtrans/midtrans-logo@main/source/png/logo.png" 
                         alt="Midtrans" height="24" class="me-2">
                    <small class="text-muted fw-semibold">Powered by Midtrans</small>
                  </div>
                  
                  <div class="row g-3 mb-3">
                    <div class="col-md-6">
                      <div class="payment-category">
                        <div class="category-title">
                          <i class="bi bi-credit-card text-primary me-2"></i>
                          Kartu Kredit/Debit
                        </div>
                        <div class="category-items">
                          <small class="text-muted d-block">
                            <i class="bi bi-check-circle text-success me-1"></i>
                            Visa, Mastercard, JCB, Amex
                          </small>
                        </div>
                      </div>
                    </div>
                    
                    <div class="col-md-6">
                      <div class="payment-category">
                        <div class="category-title">
                          <i class="bi bi-wallet2 text-success me-2"></i>
                          E-Wallet
                        </div>
                        <div class="category-items">
                          <small class="text-muted d-block">
                            <i class="bi bi-check-circle text-success me-1"></i>
                            GoPay, ShopeePay, OVO, DANA
                          </small>
                        </div>
                      </div>
                    </div>
                    
                    <div class="col-md-6">
                      <div class="payment-category">
                        <div class="category-title">
                          <i class="bi bi-bank text-info me-2"></i>
                          Transfer Bank
                        </div>
                        <div class="category-items">
                          <small class="text-muted d-block">
                            <i class="bi bi-check-circle text-success me-1"></i>
                            BCA, BNI, BRI, Mandiri, Permata
                          </small>
                        </div>
                      </div>
                    </div>
                    
                    <div class="col-md-6">
                      <div class="payment-category">
                        <div class="category-title">
                          <i class="bi bi-qr-code text-warning me-2"></i>
                          QRIS
                        </div>
                        <div class="category-items">
                          <small class="text-muted d-block">
                            <i class="bi bi-check-circle text-success me-1"></i>
                            Scan & Pay dengan semua e-wallet
                          </small>
                        </div>
                      </div>
                    </div>
                  </div>
                  
                  <div class="payment-benefits">
                    <div class="benefit-item">
                      <i class="bi bi-lightning-charge text-warning"></i>
                      <span>Verifikasi pembayaran otomatis dan instan</span>
                    </div>
                    <div class="benefit-item">
                      <i class="bi bi-shield-check text-success"></i>
                      <span>Transaksi aman dengan enkripsi SSL</span>
                    </div>
                    <div class="benefit-item">
                      <i class="bi bi-clock-history text-info"></i>
                      <span>Akses langsung ke event setelah pembayaran berhasil</span>
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
              <i class="bi bi-lock-fill me-2"></i>
              <span id="proceedBtnText">Lanjutkan ke Pembayaran</span>
            </button>
            <a href="<?= site_url('audience/events') ?>" class="btn btn-outline-secondary btn-lg">
              <i class="bi bi-arrow-left me-1"></i> Kembali ke Event
            </a>
          </div>

        </div>
      </div>

    </div>
  </main>
</div>

<!-- Hidden Form Data -->
<input type="hidden" id="csrfToken" name="<?= csrf_token() ?>" value="<?= csrf_hash() ?>">
<input type="hidden" id="regId" value="<?= (int)($reg['id'] ?? 0) ?>">
<input type="hidden" id="eventId" value="<?= (int)($reg['id_event'] ?? 0) ?>">
<input type="hidden" id="baseAmount" value="<?= $amount ?>">
<input type="hidden" id="participationMode" value="<?= esc($reg['mode_kehadiran'] ?? 'online') ?>">

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
}

.payment-method-card {
    border: 2px solid #3b82f6;
    border-radius: 12px;
    padding: 24px;
    background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
    box-shadow: 0 8px 25px rgba(59, 130, 246, 0.2);
    position: relative;
    overflow: hidden;
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

.payment-method-card.disabled {
    opacity: 0.6;
    cursor: not-allowed;
    background: #f3f4f6;
    border-color: #e5e7eb;
}

.payment-header {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 20px;
}

.payment-icon {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 28px;
    flex-shrink: 0;
}

.payment-check-badge {
    font-size: 28px;
    line-height: 1;
}

.payment-body {
    padding-left: 71px;
}

.payment-logos {
    display: flex;
    align-items: center;
}

.payment-logos img {
    transition: all 0.3s ease;
}

.payment-category {
    background: white;
    border-radius: 8px;
    padding: 12px;
    height: 100%;
    border: 1px solid #e5e7eb;
    transition: all 0.3s ease;
}

.payment-category:hover {
    border-color: #3b82f6;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.15);
    transform: translateY(-2px);
}

.category-title {
    font-weight: 600;
    font-size: 0.9rem;
    color: #1e293b;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
}

.category-items small {
    font-size: 0.8rem;
    line-height: 1.6;
}

.payment-benefits {
    margin-top: 16px;
    padding-top: 16px;
    border-top: 2px solid rgba(59, 130, 246, 0.2);
}

.benefit-item {
    display: flex;
    align-items: center;
    gap: 10px;
    font-size: 0.9rem;
    margin-bottom: 8px;
    color: #475569;
    font-weight: 500;
}

.benefit-item i {
    flex-shrink: 0;
    font-size: 1.1rem;
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
        padding: 20px;
    }
    
    .payment-body {
        padding-left: 0;
        margin-top: 16px;
    }
    
    .payment-header {
        flex-wrap: wrap;
    }
    
    .payment-category {
        margin-bottom: 0;
    }
}

/* Animation for amount change */
.payment-amount {
    transition: all 0.3s ease;
}

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
// Enhanced Payment Processing Script - Midtrans Only
(function() {
    'use strict';

    // Configuration from PHP
    const CONFIG = {
        regId: parseInt(document.getElementById('regId').value) || 0,
        eventId: parseInt(document.getElementById('eventId').value) || 0,
        baseAmount: parseFloat(document.getElementById('baseAmount').value) || 0,
        mode: document.getElementById('participationMode').value || 'online',
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
        csrfToken: document.getElementById('csrfToken'),
        
        midtransCard: document.getElementById('midtransCard')
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
        
        // Show user-friendly error
        let userMessage = message;
        if (message.includes('Network') || message.includes('CURL')) {
            userMessage = 'Koneksi bermasalah. Periksa internet Anda dan coba lagi.';
        } else if (message.includes('signature')) {
            userMessage = 'Terjadi kesalahan keamanan. Silakan refresh halaman.';
        }
        
        alert(userMessage);
        
        // Reset UI state
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
            formData.append('mode', CONFIG.mode);
            formData.append(CONFIG.csrfTokenName, getCurrentCsrfToken());

            const response = await fetch(CONFIG.siteUrl + '/audience/pembayaran/validate-voucher', {
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
                    code: result.code,
                    type: result.voucher_type || 'discount',
                    value: result.voucher_value || 0
                };
                
                const discount = CONFIG.baseAmount - result.final_price;
                voucherValidated = true;
                
                showVoucherResult(
                    `Voucher berhasil diterapkan! Diskon: ${formatCurrency(discount)}`, 
                    'success', 
                    'check-circle'
                );
                updateAmountDisplay();
                
                // Change button text to indicate applied
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

    // Enhanced Payment Processing - Midtrans Only
    async function processPayment() {
        if (isProcessing) return;

        isProcessing = true;
        setButtonLoading(elements.btnProceed, elements.proceedSpinner, elements.proceedBtnText, true, 'Memproses...');

        try {
            const formData = new FormData();
            formData.append('reg_id', CONFIG.regId);
            formData.append('payment_method', 'midtrans');
            formData.append('voucher_code', appliedVoucher?.code || '');
            formData.append(CONFIG.csrfTokenName, getCurrentCsrfToken());

            const response = await fetch(CONFIG.siteUrl + '/audience/pembayaran/process-payment', {
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
            // Verify Midtrans is available
            if (typeof snap === 'undefined') {
                throw new Error('Midtrans Snap tidak tersedia. Periksa koneksi internet Anda.');
            }

            if (!result.snap_token) {
                throw new Error('Token pembayaran tidak tersedia.');
            }

            console.log('Opening Midtrans Snap with token:', result.snap_token.substring(0, 20) + '...');

            // Enhanced Snap configuration
            const snapConfig = {
                onSuccess: function(result) {
                    console.log('Payment success:', result);
                    isProcessing = false;
                    
                    const orderId = result.order_id || result.transaction_id;
                    const redirectUrl = orderId ? 
                        `${CONFIG.siteUrl}/audience/pembayaran/finish?order_id=${orderId}&status=success` :
                        `${CONFIG.siteUrl}/audience/pembayaran?success=1`;
                    
                    window.location.href = redirectUrl;
                },
                onPending: function(result) {
                    console.log('Payment pending:', result);
                    isProcessing = false;
                    
                    const orderId = result.order_id || result.transaction_id;
                    const redirectUrl = orderId ? 
                        `${CONFIG.siteUrl}/audience/pembayaran/finish?order_id=${orderId}&status=pending` :
                        `${CONFIG.siteUrl}/audience/pembayaran?pending=1`;
                    
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
                    elements.proceedBtnText.textContent = 'Lanjutkan ke Pembayaran';
                }
            };

            // Open Midtrans Snap
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
        console.log('Initializing payment page with config:', CONFIG);

        // Validate configuration
        if (!CONFIG.regId || !CONFIG.eventId) {
            handleError('Data registrasi tidak lengkap. Silakan coba lagi.');
            return;
        }

        // Check Midtrans availability after a delay
        setTimeout(() => {
            if (typeof snap === 'undefined') {
                console.warn('Midtrans Snap not loaded properly');
                
                if (elements.midtransCard) {
                    elements.midtransCard.classList.add('disabled');
                    elements.btnProceed.disabled = true;
                    
                    // Show warning message
                    const warningMsg = document.createElement('div');
                    warningMsg.className = 'alert alert-warning mt-3 mb-0';
                    warningMsg.innerHTML = '<i class="bi bi-exclamation-triangle me-2"></i><strong>Perhatian:</strong> Sistem pembayaran sedang tidak tersedia. Silakan refresh halaman atau coba beberapa saat lagi.';
                    elements.midtransCard.querySelector('.payment-body').appendChild(warningMsg);
                }
            } else {
                console.log('Midtrans Snap loaded successfully');
            }
        }, 1000);

        // Initialize event listeners
        initEventListeners();
        
        // Set initial amount display
        updateAmountDisplay();
    }

    // Global error handler
    window.addEventListener('error', function(event) {
        console.error('Global error:', event.error);
        if (isProcessing) {
            handleError('Terjadi kesalahan tak terduga. Silakan refresh halaman dan coba lagi.');
        }
    });

    // Handle page visibility change (prevent issues when user switches tabs)
    document.addEventListener('visibilitychange', function() {
        if (document.hidden && isProcessing) {
            console.log('Page became hidden during payment processing');
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