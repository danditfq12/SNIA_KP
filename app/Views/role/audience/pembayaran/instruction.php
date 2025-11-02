<?php
// Enhanced Pembayaran Instruction (Audience) - Midtrans Only
$title = $title ?? 'Instruksi Pembayaran';
$reg   = $reg   ?? [];
$event = $event ?? [];
$user  = $user  ?? [];
$amount = $amount ?? 0;

// Check if continuing existing payment
$existingPayment = $existing_payment ?? null;
$snapToken = $snap_token ?? null;
$orderId = $order_id ?? null;
$isContinuing = !empty($existingPayment);

$regId = (int)($reg['id'] ?? 0);
$eventTitle = $event['title'] ?? 'Event';
$eventDate  = isset($event['event_date']) ? date('d M Y', strtotime($event['event_date'])) : '-';
$eventTime  = $event['event_time'] ?? '-';
$mode = $reg['mode_kehadiran'] ?? 'online';

$userName = $user['nama_lengkap'] ?? 'User';
$userEmail = $user['email'] ?? '';
$userPhone = $user['no_hp'] ?? '';

$midtransClientKey = $midtrans_client_key ?? '';
$isProduction = $is_production ?? false;
$snapScriptUrl = $isProduction 
    ? 'https://app.midtrans.com/snap/snap.js' 
    : 'https://app.sandbox.midtrans.com/snap/snap.js';
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_audience') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <a href="<?= site_url('audience/events') ?>" class="btn btn-sm btn-outline-secondary mb-3">
        <i class="bi bi-arrow-left"></i> Kembali ke Event
      </a>

      <?php if (session('message')): ?>
        <div class="alert alert-success alert-dismissible fade show">
          <?= esc(session('message')) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>
      <?php if (session('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show">
          <?= esc(session('error')) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>
      <?php if (session('warning')): ?>
        <div class="alert alert-warning alert-dismissible fade show">
          <?= esc(session('warning')) ?>
          <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
      <?php endif; ?>

      <div class="card shadow-sm border-0">
        <div class="card-body p-4">

          <!-- HERO Section -->
          <div class="payment-hero mb-4">
            <div class="row align-items-center">
              <div class="col-md-8">
                <div class="hero-badge mb-2">
                  <i class="bi bi-credit-card me-2"></i>
                  <?= $isContinuing ? 'Lanjutkan Pembayaran' : 'Pembayaran Digital' ?>
                </div>
                <h3 class="hero-title mb-2"><?= esc($eventTitle) ?></h3>
                <div class="hero-meta">
                  <span><i class="bi bi-calendar-event me-1"></i><?= esc($eventDate) ?></span>
                  <span><i class="bi bi-clock me-1"></i><?= esc($eventTime) ?></span>
                  <span><i class="bi bi-geo-alt me-1"></i><?= ucfirst($mode) ?></span>
                </div>
              </div>
              <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <div class="amount-box">
                  <div class="amount-label">Total Pembayaran</div>
                  <div class="amount-value">Rp <?= number_format($amount, 0, ',', '.') ?></div>
                </div>
              </div>
            </div>
          </div>

          <?php if ($isContinuing): ?>
          <!-- Continue Existing Payment -->
          <div class="alert alert-info mb-4">
            <div class="d-flex align-items-start gap-2">
              <i class="bi bi-info-circle-fill fs-5"></i>
              <div>
                <strong>Melanjutkan Pembayaran</strong>
                <p class="mb-0 mt-1">Anda memiliki transaksi pembayaran yang belum diselesaikan. Klik tombol di bawah untuk melanjutkan.</p>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col-lg-8 mx-auto">
              <div class="card bg-light border-0">
                <div class="card-body text-center p-4">
                  <div class="mb-3">
                    <i class="bi bi-credit-card text-primary" style="font-size: 3rem;"></i>
                  </div>
                  <h5 class="mb-3">Siap untuk melanjutkan pembayaran?</h5>
                  <p class="text-muted mb-4">Order ID: <strong class="font-monospace"><?= esc($orderId) ?></strong></p>
                  
                  <?php if ($snapToken): ?>
                  <button type="button" id="continuePaymentBtn" class="btn btn-primary btn-lg px-5">
                    <i class="bi bi-credit-card me-2"></i>Lanjutkan Pembayaran
                  </button>
                  <?php else: ?>
                  <div class="alert alert-warning">
                    Snap token tidak ditemukan. Silakan <a href="<?= site_url('audience/pembayaran/detail/' . ($existingPayment['id_pembayaran'] ?? 0)) ?>">batalkan pembayaran ini</a> dan buat pembayaran baru.
                  </div>
                  <?php endif; ?>

                  <div class="mt-3">
                    <a href="<?= site_url('audience/pembayaran/detail/' . ($existingPayment['id_pembayaran'] ?? 0)) ?>" class="btn btn-outline-secondary">
                      <i class="bi bi-arrow-left me-1"></i>Kembali ke Detail
                    </a>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <?php else: ?>
          <!-- New Payment -->
          <div class="row g-4">
            <!-- Left Column - Instructions -->
            <div class="col-lg-7">
              <div class="instruction-box">
                <h5 class="section-title mb-3">
                  <i class="bi bi-list-check me-2"></i>Petunjuk Pembayaran
                </h5>
                
                <div class="steps">
                  <div class="step-item">
                    <div class="step-number">1</div>
                    <div class="step-content">
                      <div class="step-title">Klik Tombol Bayar Sekarang</div>
                      <div class="step-desc">Anda akan diarahkan ke halaman pembayaran Midtrans yang aman</div>
                    </div>
                  </div>

                  <div class="step-item">
                    <div class="step-number">2</div>
                    <div class="step-content">
                      <div class="step-title">Pilih Metode Pembayaran</div>
                      <div class="step-desc">Tersedia berbagai pilihan: Transfer Bank, E-Wallet, Kartu Kredit, dll</div>
                    </div>
                  </div>

                  <div class="step-item">
                    <div class="step-number">3</div>
                    <div class="step-content">
                      <div class="step-title">Selesaikan Pembayaran</div>
                      <div class="step-desc">Ikuti instruksi pembayaran sesuai metode yang dipilih</div>
                    </div>
                  </div>

                  <div class="step-item">
                    <div class="step-number">4</div>
                    <div class="step-content">
                      <div class="step-title">Verifikasi Otomatis</div>
                      <div class="step-desc">Pembayaran akan diverifikasi otomatis dan Anda akan menerima konfirmasi</div>
                    </div>
                  </div>
                </div>

                <div class="payment-features mt-4">
                  <h6 class="small fw-semibold text-muted mb-2">Keunggulan Pembayaran Digital:</h6>
                  <div class="features-grid">
                    <div class="feature-item">
                      <i class="bi bi-shield-check text-success"></i>
                      <span>Keamanan Terjamin</span>
                    </div>
                    <div class="feature-item">
                      <i class="bi bi-lightning-charge text-warning"></i>
                      <span>Verifikasi Instan</span>
                    </div>
                    <div class="feature-item">
                      <i class="bi bi-wallet2 text-primary"></i>
                      <span>Banyak Pilihan</span>
                    </div>
                    <div class="feature-item">
                      <i class="bi bi-headset text-info"></i>
                      <span>Support 24/7</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Right Column - Payment Summary -->
            <div class="col-lg-5">
              <div class="summary-box">
                <h5 class="section-title mb-3">
                  <i class="bi bi-receipt me-2"></i>Ringkasan Pembayaran
                </h5>

                <div class="summary-item">
                  <span class="summary-label">Event</span>
                  <span class="summary-value"><?= esc($eventTitle) ?></span>
                </div>

                <div class="summary-item">
                  <span class="summary-label">Tanggal</span>
                  <span class="summary-value"><?= esc($eventDate) ?> · <?= esc($eventTime) ?></span>
                </div>

                <div class="summary-item">
                  <span class="summary-label">Mode Kehadiran</span>
                  <span class="summary-value">
                    <span class="badge bg-primary"><?= ucfirst($mode) ?></span>
                  </span>
                </div>

                <div class="summary-item">
                  <span class="summary-label">Nama Peserta</span>
                  <span class="summary-value"><?= esc($userName) ?></span>
                </div>

                <hr class="my-3">

                <div class="summary-item total">
                  <span class="summary-label">Total Pembayaran</span>
                  <span class="summary-value">Rp <?= number_format($amount, 0, ',', '.') ?></span>
                </div>

                <!-- Voucher Section (Optional - can be added later) -->
                <div class="voucher-section mt-3">
                  <button type="button" class="btn btn-sm btn-outline-secondary w-100" data-bs-toggle="collapse" data-bs-target="#voucherForm">
                    <i class="bi bi-tag me-1"></i>Punya Kode Voucher?
                  </button>
                  <div class="collapse mt-2" id="voucherForm">
                    <div class="input-group input-group-sm">
                      <input type="text" class="form-control" id="voucherInput" placeholder="Masukkan kode voucher">
                      <button class="btn btn-primary" type="button" id="applyVoucherBtn">Terapkan</button>
                    </div>
                    <div id="voucherMessage" class="small mt-1"></div>
                  </div>
                </div>

                <div class="payment-action mt-4">
                  <button type="button" id="payButton" class="btn btn-primary btn-lg w-100 mb-2">
                    <i class="bi bi-credit-card me-2"></i>Bayar Sekarang
                  </button>
                  <a href="<?= site_url('audience/events/detail/' . ($event['id'] ?? 0)) ?>" class="btn btn-outline-secondary w-100">
                    <i class="bi bi-arrow-left me-1"></i>Kembali ke Detail Event
                  </a>
                </div>

                <div class="security-note mt-3">
                  <div class="d-flex align-items-start gap-2">
                    <i class="bi bi-shield-lock-fill text-success"></i>
                    <small class="text-muted">
                      Pembayaran Anda dilindungi dengan enkripsi tingkat bank dan diproses melalui Midtrans Payment Gateway yang tersertifikasi PCI-DSS.
                    </small>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <?php endif; ?>

        </div>
      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<!-- Midtrans Snap JS -->
<script src="<?= $snapScriptUrl ?>" data-client-key="<?= esc($midtransClientKey) ?>"></script>

<style>
  .payment-hero {
    background: linear-gradient(135deg, #2563eb, #60a5fa);
    border-radius: 16px;
    padding: 24px;
    color: white;
  }

  .hero-badge {
    display: inline-block;
    background: rgba(255,255,255,0.2);
    padding: 6px 12px;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 500;
  }

  .hero-title {
    font-weight: 700;
    font-size: 1.75rem;
    margin: 0;
  }

  .hero-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 16px;
    font-size: 0.875rem;
    opacity: 0.95;
  }

  .amount-box {
    background: rgba(255,255,255,0.15);
    border-radius: 12px;
    padding: 16px;
    text-align: center;
  }

  .amount-label {
    font-size: 0.875rem;
    opacity: 0.9;
    margin-bottom: 4px;
  }

  .amount-value {
    font-size: 1.75rem;
    font-weight: 700;
  }

  .instruction-box, .summary-box {
    background: #f9fafb;
    border-radius: 12px;
    padding: 24px;
  }

  .section-title {
    font-weight: 600;
    color: #1f2937;
  }

  .steps {
    display: flex;
    flex-direction: column;
    gap: 20px;
  }

  .step-item {
    display: flex;
    gap: 16px;
    align-items: flex-start;
  }

  .step-number {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, #2563eb, #3b82f6);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    flex-shrink: 0;
  }

  .step-title {
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 4px;
  }

  .step-desc {
    font-size: 0.875rem;
    color: #6b7280;
  }

  .features-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 12px;
  }

  .feature-item {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 0.875rem;
  }

  .feature-item i {
    font-size: 1.25rem;
  }

  .summary-item {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #e5e7eb;
  }

  .summary-item.total {
    border-bottom: none;
    padding-top: 16px;
    font-weight: 600;
    font-size: 1.125rem;
  }

  .summary-label {
    color: #6b7280;
  }

  .summary-value {
    color: #1f2937;
    font-weight: 500;
    text-align: right;
  }

  .security-note {
    padding: 12px;
    background: #f0fdf4;
    border-radius: 8px;
  }

  @media (max-width: 991.98px) {
    .payment-hero {
      padding: 20px;
    }
    .hero-title {
      font-size: 1.5rem;
    }
    .amount-value {
      font-size: 1.5rem;
    }
  }
</style>

<script>
// Global variables
let snapToken = '<?= $snapToken ?? '' ?>';
let regId = <?= $regId ?>;
let voucherApplied = false;
let voucherId = null;
let originalAmount = <?= $amount ?>;
let finalAmount = <?= $amount ?>;

<?php if ($isContinuing && $snapToken): ?>
// Continue existing payment
document.getElementById('continuePaymentBtn')?.addEventListener('click', function() {
  if (!snapToken) {
    alert('Token pembayaran tidak valid');
    return;
  }

  window.snap.pay(snapToken, {
    onSuccess: function(result) {
      console.log('Payment success:', result);
      window.location.href = '<?= site_url('audience/pembayaran/finish') ?>?order_id=' + result.order_id + '&status=success';
    },
    onPending: function(result) {
      console.log('Payment pending:', result);
      window.location.href = '<?= site_url('audience/pembayaran/finish') ?>?order_id=' + result.order_id + '&status=pending';
    },
    onError: function(result) {
      console.log('Payment error:', result);
      alert('Pembayaran gagal. Silakan coba lagi.');
    },
    onClose: function() {
      console.log('Payment popup closed');
      alert('Anda menutup popup pembayaran sebelum menyelesaikan transaksi.');
    }
  });
});

<?php else: ?>
// New payment
document.getElementById('applyVoucherBtn')?.addEventListener('click', function() {
  const code = document.getElementById('voucherInput').value.trim();
  const messageEl = document.getElementById('voucherMessage');
  
  if (!code) {
    messageEl.className = 'small mt-1 text-danger';
    messageEl.textContent = 'Masukkan kode voucher';
    return;
  }

  messageEl.className = 'small mt-1 text-muted';
  messageEl.textContent = 'Memvalidasi...';

  fetch('<?= site_url('audience/pembayaran/validate-voucher') ?>', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
      'X-Requested-With': 'XMLHttpRequest'
    },
    body: new URLSearchParams({
      '<?= csrf_token() ?>': '<?= csrf_hash() ?>',
      'event_id': '<?= $reg['id_event'] ?? 0 ?>',
      'mode': '<?= $mode ?>',
      'kode_voucher': code
    })
  })
  .then(res => res.json())
  .then(data => {
    if (data.ok) {
      voucherApplied = true;
      voucherId = data.voucher_id;
      finalAmount = data.final_price;
      
      messageEl.className = 'small mt-1 text-success';
      messageEl.textContent = '✓ ' + data.message;
      
      // Update display
      document.querySelector('.summary-item.total .summary-value').innerHTML = 
        'Rp ' + new Intl.NumberFormat('id-ID').format(finalAmount);
      
      // Show discount
      const discountAmount = originalAmount - finalAmount;
      if (discountAmount > 0) {
        const discountHtml = `
          <div class="summary-item text-success">
            <span class="summary-label">Diskon Voucher</span>
            <span class="summary-value">-Rp ${new Intl.NumberFormat('id-ID').format(discountAmount)}</span>
          </div>
        `;
        document.querySelector('.summary-item.total').insertAdjacentHTML('beforebegin', discountHtml);
      }
      
      document.getElementById('voucherInput').disabled = true;
      document.getElementById('applyVoucherBtn').disabled = true;
    } else {
      messageEl.className = 'small mt-1 text-danger';
      messageEl.textContent = '✗ ' + data.message;
    }
  })
  .catch(err => {
    messageEl.className = 'small mt-1 text-danger';
    messageEl.textContent = 'Terjadi kesalahan. Coba lagi.';
    console.error(err);
  });
});

document.getElementById('payButton')?.addEventListener('click', function() {
  const btn = this;
  btn.disabled = true;
  btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Memproses...';

  const formData = new URLSearchParams({
    '<?= csrf_token() ?>': '<?= csrf_hash() ?>',
    'reg_id': regId,
    'payment_method': 'midtrans',
    'voucher_code': voucherApplied ? document.getElementById('voucherInput').value : ''
  });

  fetch('<?= site_url('audience/pembayaran/process-payment') ?>', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded',
      'X-Requested-With': 'XMLHttpRequest'
    },
    body: formData
  })
  .then(res => res.json())
  .then(data => {
    if (data.success && data.snap_token) {
      snapToken = data.snap_token;
      
      window.snap.pay(snapToken, {
        onSuccess: function(result) {
          console.log('Payment success:', result);
          window.location.href = '<?= site_url('audience/pembayaran/finish') ?>?order_id=' + result.order_id + '&status=success';
        },
        onPending: function(result) {
          console.log('Payment pending:', result);
          window.location.href = '<?= site_url('audience/pembayaran/finish') ?>?order_id=' + result.order_id + '&status=pending';
        },
        onError: function(result) {
          console.log('Payment error:', result);
          btn.disabled = false;
          btn.innerHTML = '<i class="bi bi-credit-card me-2"></i>Bayar Sekarang';
          alert('Pembayaran gagal. Silakan coba lagi.');
        },
        onClose: function() {
          console.log('Payment popup closed');
          btn.disabled = false;
          btn.innerHTML = '<i class="bi bi-credit-card me-2"></i>Bayar Sekarang';
        }
      });
    } else {
      btn.disabled = false;
      btn.innerHTML = '<i class="bi bi-credit-card me-2"></i>Bayar Sekarang';
      
      if (data.redirect_url) {
        if (window.Swal) {
          Swal.fire({
            icon: 'warning',
            title: 'Perhatian',
            text: data.message || 'Anda sudah memiliki pembayaran pending',
            confirmButtonText: 'Lihat Detail'
          }).then(() => {
            window.location.href = data.redirect_url;
          });
        } else {
          alert(data.message || 'Terjadi kesalahan');
          window.location.href = data.redirect_url;
        }
      } else {
        alert(data.message || 'Terjadi kesalahan');
      }
    }
  })
  .catch(err => {
    console.error('Error:', err);
    btn.disabled = false;
    btn.innerHTML = '<i class="bi bi-credit-card me-2"></i>Bayar Sekarang';
    alert('Terjadi kesalahan. Silakan coba lagi.');
  });
});
<?php endif; ?>
</script>