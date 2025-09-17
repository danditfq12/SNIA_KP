<?php
// =========================================
// Audience Pembayaran Instruction - Midtrans Only
// =========================================
$title = 'Instruksi Pembayaran';
$reg = $reg ?? [];
$amount = (float)($amount ?? 0);
$event = $event ?? [];
$user = $user ?? [];

$evTitle = $event['title'] ?? '-';
$evDate = isset($event['event_date']) ? date('d M Y', strtotime($event['event_date'])) : '-';
$evTime = $event['event_time'] ?? '-';
$mode = strtoupper($reg['mode_kehadiran'] ?? '-');
$amountF = number_format($amount, 0, ',', '.');
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_audience') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <a href="<?= site_url('audience/events') ?>" class="btn btn-sm btn-outline-secondary mb-3">
        <i class="bi bi-arrow-left"></i> Kembali ke Event
      </a>

      <!-- HERO -->
      <div class="pay-hero mb-4">
        <div class="pay-tags">
          <span class="pay-tag"><i class="bi bi-calendar2-event"></i> <?= esc($evDate) ?></span>
          <span class="pay-tag"><i class="bi bi-clock"></i> <?= esc($evTime) ?></span>
          <span class="pay-tag"><i class="bi bi-broadcast"></i> <?= esc($mode) ?></span>
        </div>
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
          <div>
            <h1 class="pay-title mb-1"><?= esc($evTitle) ?></h1>
            <div class="pay-amount">Rp <?= $amountF ?></div>
          </div>
          <div class="text-end">
            <span class="badge bg-info text-dark px-3 py-2">
              <i class="bi bi-person me-1"></i><?= esc($user['nama_lengkap'] ?? 'User') ?>
            </span>
          </div>
        </div>
      </div>

      <div class="row g-4">
        <!-- Payment Process Card -->
        <div class="col-12">
          <div class="card shadow-sm border-0">
            <div class="card-body">
              <div class="text-center mb-4">
                <div class="payment-icon-large mb-3">
                  <i class="bi bi-credit-card-2-front"></i>
                </div>
                <h4 class="fw-bold text-primary mb-2">Pembayaran Digital</h4>
                <p class="text-muted">Sistem pembayaran otomatis yang aman dan terpercaya</p>
              </div>

              <!-- Benefits -->
              <div class="row g-3 mb-4">
                <div class="col-12 col-md-4">
                  <div class="benefit-card">
                    <div class="benefit-icon bg-success">
                      <i class="bi bi-lightning-charge text-white"></i>
                    </div>
                    <div class="benefit-content">
                      <h6 class="mb-1">Verifikasi Otomatis</h6>
                      <small class="text-muted">Status pembayaran langsung terupdate tanpa menunggu admin</small>
                    </div>
                  </div>
                </div>
                <div class="col-12 col-md-4">
                  <div class="benefit-card">
                    <div class="benefit-icon bg-primary">
                      <i class="bi bi-shield-check text-white"></i>
                    </div>
                    <div class="benefit-content">
                      <h6 class="mb-1">Keamanan Tingkat Bank</h6>
                      <small class="text-muted">Data kartu dan transaksi Anda dienkripsi dengan standar internasional</small>
                    </div>
                  </div>
                </div>
                <div class="col-12 col-md-4">
                  <div class="benefit-card">
                    <div class="benefit-icon bg-info">
                      <i class="bi bi-clock-fill text-white"></i>
                    </div>
                    <div class="benefit-content">
                      <h6 class="mb-1">Proses Real-time</h6>
                      <small class="text-muted">Pembayaran langsung diproses dalam hitungan detik</small>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Payment Methods -->
              <div class="payment-methods-section">
                <h6 class="mb-3 text-center">
                  <i class="bi bi-credit-card me-2"></i>Metode Pembayaran Tersedia
                </h6>
                <div class="payment-methods-grid">
                  <div class="payment-method-item">
                    <img src="https://logos-world.net/wp-content/uploads/2020/09/Visa-Logo.png" alt="Visa" class="method-logo">
                    <span class="method-label">Visa</span>
                  </div>
                  <div class="payment-method-item">
                    <img src="https://logos-world.net/wp-content/uploads/2020/09/Mastercard-Logo.png" alt="Mastercard" class="method-logo">
                    <span class="method-label">Mastercard</span>
                  </div>
                  <div class="payment-method-item">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/8/86/GoPayLogo.png" alt="GoPay" class="method-logo">
                    <span class="method-label">GoPay</span>
                  </div>
                  <div class="payment-method-item">
                    <img src="https://upload.wikimedia.org/wikipedia/commons/f/fe/Shopee.png" alt="ShopeePay" class="method-logo">
                    <span class="method-label">ShopeePay</span>
                  </div>
                  <div class="payment-method-item">
                    <div class="method-logo-text bg-primary">OVO</div>
                    <span class="method-label">OVO</span>
                  </div>
                  <div class="payment-method-item">
                    <div class="method-logo-text bg-info">DANA</div>
                    <span class="method-label">DANA</span>
                  </div>
                  <div class="payment-method-item">
                    <div class="method-logo-text bg-warning">QRIS</div>
                    <span class="method-label">QRIS</span>
                  </div>
                  <div class="payment-method-item">
                    <i class="bi bi-bank2 method-icon"></i>
                    <span class="method-label">Bank Transfer</span>
                  </div>
                </div>
              </div>

              <!-- CTA Button -->
              <div class="text-center mt-4">
                <a href="<?= site_url('audience/pembayaran/create/'.(int)($reg['id'] ?? 0)) ?>" 
                   class="btn btn-primary btn-lg px-5">
                  <i class="bi bi-credit-card me-2"></i>Lanjutkan Pembayaran
                </a>
                <div class="small text-muted mt-2">
                  <i class="bi bi-shield-check me-1"></i>
                  Powered by Midtrans - Payment Gateway Terpercaya
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- How it Works -->
        <div class="col-12">
          <div class="card shadow-sm border-0">
            <div class="card-body">
              <h5 class="card-title mb-4 text-center">
                <i class="bi bi-list-ol me-2"></i>Cara Pembayaran
              </h5>

              <div class="steps-container">
                <div class="step-item">
                  <div class="step-number">1</div>
                  <div class="step-content">
                    <h6 class="step-title">Klik Lanjutkan Pembayaran</h6>
                    <p class="step-description">Sistem akan mengarahkan Anda ke halaman pembayaran yang aman</p>
                  </div>
                </div>
                
                <div class="step-divider">
                  <i class="bi bi-arrow-down"></i>
                </div>

                <div class="step-item">
                  <div class="step-number">2</div>
                  <div class="step-content">
                    <h6 class="step-title">Pilih Metode Pembayaran</h6>
                    <p class="step-description">Pilih kartu kredit, e-wallet, atau bank transfer sesuai preferensi</p>
                  </div>
                </div>

                <div class="step-divider">
                  <i class="bi bi-arrow-down"></i>
                </div>

                <div class="step-item">
                  <div class="step-number">3</div>
                  <div class="step-content">
                    <h6 class="step-title">Selesaikan Pembayaran</h6>
                    <p class="step-description">Ikuti instruksi sesuai metode yang dipilih hingga pembayaran berhasil</p>
                  </div>
                </div>

                <div class="step-divider">
                  <i class="bi bi-arrow-down"></i>
                </div>

                <div class="step-item">
                  <div class="step-number">4</div>
                  <div class="step-content">
                    <h6 class="step-title">Verifikasi Otomatis</h6>
                    <p class="step-description">Status pendaftaran Anda langsung berubah menjadi "Lunas" tanpa menunggu</p>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Security Info -->
        <div class="col-12">
          <div class="alert alert-info border-0">
            <div class="d-flex align-items-start">
              <i class="bi bi-info-circle-fill me-3 mt-1"></i>
              <div>
                <h6 class="alert-heading mb-2">Informasi Keamanan</h6>
                <ul class="mb-0 small">
                  <li>Semua transaksi diproses melalui sistem pembayaran berstandar PCI DSS</li>
                  <li>Data kartu kredit tidak disimpan di server kami</li>
                  <li>Sistem menggunakan enkripsi SSL 256-bit untuk melindungi data Anda</li>
                  <li>Jika mengalami kendala, hubungi panitia melalui kontak yang tersedia</li>
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
  .pay-hero{
    background: linear-gradient(135deg,#2563eb,#60a5fa);
    border-radius: 20px; color:#fff; padding:24px 28px;
    box-shadow: 0 8px 32px rgba(37,99,235,.25);
    position: relative;
    overflow: hidden;
  }
  
  .pay-hero::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -50%;
    width: 200%;
    height: 200%;
    background: linear-gradient(45deg, transparent 40%, rgba(255,255,255,0.08) 50%, transparent 60%);
    transform: rotate(45deg);
    animation: shine 3s ease-in-out infinite;
  }

  @keyframes shine {
    0%, 100% { transform: translateX(-100%) translateY(-100%) rotate(45deg); }
    50% { transform: translateX(0%) translateY(0%) rotate(45deg); }
  }
  
  .pay-tags{ display:flex; gap:.6rem; flex-wrap:wrap; margin-bottom:.8rem; z-index: 1; position: relative; }
  .pay-tag{
    background: rgba(255,255,255,.2);
    border:1px solid rgba(255,255,255,.3);
    color:#fff; border-radius:20px; padding:.4rem .8rem; font-size:.875rem;
    display:inline-flex; align-items:center; gap:.5rem;
    backdrop-filter: blur(10px);
  }
  .pay-title{ font-weight:800; line-height:1.3; font-size: clamp(20px, 4vw, 28px); z-index: 1; position: relative; }
  .pay-amount{ font-weight:800; line-height:1.2; font-size: clamp(24px, 5.2vw, 36px); z-index: 1; position: relative; }

  .payment-icon-large {
    width: 80px;
    height: 80px;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    border-radius: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto;
    font-size: 2.5rem;
    color: white;
    box-shadow: 0 8px 24px rgba(59, 130, 246, 0.3);
  }

  .benefit-card {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 16px;
    display: flex;
    align-items: flex-start;
    gap: 12px;
    height: 100%;
    transition: all 0.2s ease;
  }

  .benefit-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
  }

  .benefit-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
  }

  .benefit-content h6 {
    color: #1f2937;
    margin: 0;
  }

  .payment-methods-section {
    background: #f8fafc;
    border: 1px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
  }

  .payment-methods-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
    gap: 16px;
    max-width: 600px;
    margin: 0 auto;
  }

  .payment-method-item {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 8px;
    padding: 12px;
    background: white;
    border-radius: 8px;
    border: 1px solid #e5e7eb;
    transition: all 0.2s ease;
  }

  .payment-method-item:hover {
    border-color: #3b82f6;
    box-shadow: 0 2px 8px rgba(59, 130, 246, 0.15);
  }

  .method-logo {
    height: 24px;
    max-width: 60px;
    object-fit: contain;
    filter: grayscale(0.3);
    transition: filter 0.2s ease;
  }

  .payment-method-item:hover .method-logo {
    filter: grayscale(0);
  }

  .method-logo-text {
    width: 50px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: 700;
    color: white;
    border-radius: 4px;
  }

  .method-icon {
    font-size: 24px;
    color: #6b7280;
  }

  .method-label {
    font-size: 0.75rem;
    color: #6b7280;
    font-weight: 500;
  }

  .steps-container {
    max-width: 500px;
    margin: 0 auto;
  }

  .step-item {
    display: flex;
    align-items: flex-start;
    gap: 16px;
    text-align: left;
  }

  .step-number {
    width: 40px;
    height: 40px;
    background: linear-gradient(135deg, #3b82f6, #1d4ed8);
    color: white;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    flex-shrink: 0;
    box-shadow: 0 4px 12px rgba(59, 130, 246, 0.3);
  }

  .step-content {
    flex-grow: 1;
    padding-top: 4px;
  }

  .step-title {
    color: #1f2937;
    margin: 0 0 4px 0;
    font-weight: 600;
  }

  .step-description {
    color: #6b7280;
    margin: 0;
    font-size: 0.875rem;
    line-height: 1.5;
  }

  .step-divider {
    text-align: center;
    margin: 16px 0;
    color: #9ca3af;
    font-size: 1.25rem;
  }

  @media (max-width: 767.98px) {
    .pay-hero { padding: 20px; border-radius: 16px; }
    .payment-methods-grid {
      grid-template-columns: repeat(auto-fit, minmax(80px, 1fr));
      gap: 12px;
    }
    .benefit-card { padding: 12px; }
  }

  @media (max-width: 575.98px) {
    .payment-methods-grid {
      grid-template-columns: repeat(2, 1fr);
    }
  }
</style>