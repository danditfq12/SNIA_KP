<?php

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
                <div class="payment-amount">Rp <?= $amountF ?></div>
              </div>
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
              <div class="payment-method-card selected">
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
}
</style>

<script>
(function() {
    'use strict';

    // Configuration
    const CONFIG = {
        regId: parseInt(document.getElementById('regId').value) || 0,
        eventId: parseInt(document.getElementById('eventId').value) || 0,
        baseAmount: parseFloat(document.getElementById('baseAmount').value) || 0,
        mode: document.getElementById('participationMode').value || 'online',
        siteUrl: '<?= site_url() ?>',
        csrfTokenName: '<?= csrf_token() ?>'
    };

    // State
    let isProcessing = false;

    // DOM Elements
    const elements = {
        btnProceed: document.getElementById('btnProceed'),
        proceedSpinner: document.getElementById('proceedSpinner'),
        proceedBtnText: document.getElementById('proceedBtnText'),
        csrfToken: document.getElementById('csrfToken')
    };

    function setButtonLoading(loading) {
        if (loading) {
            elements.proceedSpinner.classList.remove('d-none');
            elements.proceedBtnText.textContent = 'Memproses...';
            elements.btnProceed.disabled = true;
        } else {
            elements.proceedSpinner.classList.add('d-none');
            elements.proceedBtnText.textContent = 'Lanjutkan ke Pembayaran';
            elements.btnProceed.disabled = false;
        }
    }

    function handleError(message) {
        console.error('Payment Error:', message);
        alert(message);
        setButtonLoading(false);
        isProcessing = false;
    }

    async function processPayment() {
        if (isProcessing) return;
        
        isProcessing = true;
        setButtonLoading(true);

        try {
            const formData = new FormData();
            formData.append('reg_id', CONFIG.regId);
            formData.append('payment_method', 'midtrans');
            formData.append(CONFIG.csrfTokenName, elements.csrfToken.value);

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
            handleError('Gagal memproses pembayaran: ' + error.message);
        }
    }

    async function handleMidtransPayment(result) {
        try {
            if (typeof snap === 'undefined') {
                throw new Error('Midtrans Snap tidak tersedia. Periksa koneksi internet Anda.');
            }

            if (!result.snap_token) {
                throw new Error('Token pembayaran tidak tersedia.');
            }

            snap.pay(result.snap_token, {
                onSuccess: function(result) {
                    isProcessing = false;
                    const orderId = result.order_id || result.transaction_id;
                    const redirectUrl = orderId ? 
                        `${CONFIG.siteUrl}/audience/pembayaran/finish?order_id=${orderId}&status=success` :
                        `${CONFIG.siteUrl}/audience/pembayaran?success=1`;
                    
                    window.location.href = redirectUrl;
                },
                onPending: function(result) {
                    isProcessing = false;
                    const orderId = result.order_id || result.transaction_id;
                    const redirectUrl = orderId ? 
                        `${CONFIG.siteUrl}/audience/pembayaran/finish?order_id=${orderId}&status=pending` :
                        `${CONFIG.siteUrl}/audience/pembayaran?pending=1`;
                    
                    window.location.href = redirectUrl;
                },
                onError: function(result) {
                    isProcessing = false;
                    const errorMsg = result.status_message || 
                                   result.error_message || 
                                   'Terjadi kesalahan dalam pembayaran';
                    
                    handleError('Pembayaran gagal: ' + errorMsg);
                },
                onClose: function() {
                    isProcessing = false;
                    setButtonLoading(false);
                }
            });

        } catch (error) {
            handleError('Gagal membuka halaman pembayaran: ' + error.message);
        }
    }

    // Event Listener
    elements.btnProceed.addEventListener('click', processPayment);

    // Check Midtrans availability
    setTimeout(() => {
        if (typeof snap === 'undefined') {
            console.warn('Midtrans Snap not loaded properly');
            elements.btnProceed.disabled = true;
            alert('Sistem pembayaran sedang tidak tersedia. Silakan refresh halaman.');
        }
    }, 1000);

})();
</script>