<?php
  $title   = 'Detail Event';
  $event   = $event  ?? [];
  $isOpen  = $isOpen ?? false;
  $myReg   = $myReg  ?? null;
  $options = $options ?? [];
  $pricing = $pricing ?? []; // matrix: ['audience'=>['online'=>..., 'offline'=>...]]

  // === PATCH: pisahkan regId vs payId supaya link tidak salah ===
  $regId   = isset($myReg['id']) ? (int)$myReg['id'] : null;
  $payId   = isset($myReg['id_pembayaran']) ? (int)$myReg['id_pembayaran'] : null;
  $payStat = $myReg['payment_status'] ?? null;
  $regStatus = $myReg['status'] ?? null;

  // Cek apakah benar-benar terdaftar (bukan batal/rejected)
  $isRegistered = $myReg && !in_array($regStatus, ['batal', 'ditolak'], true);

  $fmtDate = function($d){ return $d ? date('d M Y', strtotime($d)) : '-'; };
  $rupiah  = function($n){ return ($n===null||$n==='') ? '—' : 'Rp '.number_format((float)$n,0,',','.'); };

  $priceOnline  = $pricing['audience']['online']  ?? null;
  $priceOffline = $pricing['audience']['offline'] ?? null;

  $isToday = isset($event['event_date']) && date('Y-m-d', strtotime($event['event_date'])) === date('Y-m-d');
  $eventFormat = strtolower($event['format'] ?? '');
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_audience') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <!-- Back Button -->
      <div class="mb-3">
        <a href="<?= site_url('audience/events') ?>" class="btn-back">
          <i class="bi bi-arrow-left"></i>
          <span>Kembali ke Daftar Event</span>
        </a>
      </div>

      <!-- HERO CARD -->
      <div class="hero-card mb-4">
        <div class="hero-background"></div>
        <div class="hero-content">
          <div class="hero-header">
            <div class="hero-badges">
              <div class="hero-badge">
                <i class="bi bi-calendar-event"></i>
                <span><?= esc($fmtDate($event['event_date'] ?? null)) ?></span>
              </div>
              <div class="hero-badge">
                <i class="bi bi-clock-fill"></i>
                <span><?= esc($event['event_time'] ?? '-') ?></span>
              </div>
              <div class="hero-badge badge-format">
                <?php if($eventFormat === 'online'): ?>
                  <i class="bi bi-wifi"></i>
                  <span>Online</span>
                <?php elseif($eventFormat === 'offline'): ?>
                  <i class="bi bi-geo-alt-fill"></i>
                  <span>Offline</span>
                <?php else: ?>
                  <i class="bi bi-arrow-left-right"></i>
                  <span>Hybrid</span>
                <?php endif; ?>
              </div>
              <?php if (!empty($event['location'])): ?>
                <div class="hero-badge">
                  <i class="bi bi-pin-map-fill"></i>
                  <span><?= esc($event['location']) ?></span>
                </div>
              <?php endif; ?>
              <?php if ($isToday): ?>
                <div class="hero-badge badge-today">
                  <i class="bi bi-star-fill"></i>
                  <span>Hari Ini</span>
                </div>
              <?php endif; ?>
            </div>
            
            <div class="hero-status">
              <?php if ($isRegistered): ?>
                <div class="status-badge status-registered">
                  <i class="bi bi-check-circle-fill"></i>
                  <span>Terdaftar</span>
                </div>
              <?php elseif ($isOpen): ?>
                <div class="status-badge status-open">
                  <i class="bi bi-unlock-fill"></i>
                  <span>Pendaftaran Dibuka</span>
                </div>
              <?php else: ?>
                <div class="status-badge status-closed">
                  <i class="bi bi-lock-fill"></i>
                  <span>Ditutup</span>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <h1 class="hero-title"><?= esc($event['title'] ?? 'Event') ?></h1>
        </div>
      </div>

      <!-- GRID CONTENT -->
      <div class="row g-4">
        <!-- Left Column: Description & Pricing -->
        <div class="col-12 col-lg-8">
          <!-- Description Card -->
          <div class="modern-card mb-4">
            <div class="card-header-modern">
              <div class="header-left">
                <i class="bi bi-file-text-fill"></i>
                <strong>Deskripsi Event</strong>
              </div>
            </div>
            <div class="card-body p-4">
              <?php if (!empty($event['description'])): ?>
                <div class="description-content"><?= nl2br(esc($event['description'])) ?></div>
              <?php else: ?>
                <div class="empty-state-small">
                  <i class="bi bi-file-text"></i>
                  <p>Belum ada deskripsi untuk event ini.</p>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Pricing Card -->
          <div class="modern-card">
            <div class="card-header-modern">
              <div class="header-left">
                <i class="bi bi-tags-fill"></i>
                <strong>Harga Tiket</strong>
              </div>
            </div>
            <div class="card-body p-4">
              <?php if ($isRegistered && !empty($myReg['mode_kehadiran'])): ?>
                <!-- Jika sudah terdaftar, tampilkan harga yang dipilih -->
                <div class="selected-price-card">
                  <div class="selected-badge">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>Pilihan Anda</span>
                  </div>
                  <div class="selected-price-content">
                    <?php 
                      $selectedMode = strtolower($myReg['mode_kehadiran']);
                      $paidAmount = isset($myReg['jumlah_bayar']) ? (float)$myReg['jumlah_bayar'] : 0;
                    ?>
                    <?php if ($selectedMode === 'online'): ?>
                      <div class="selected-icon icon-online">
                        <i class="bi bi-wifi"></i>
                      </div>
                      <div class="selected-info">
                        <div class="selected-label">Partisipasi Online</div>
                        <div class="selected-amount"><?= $rupiah($paidAmount) ?></div>
                      </div>
                    <?php else: ?>
                      <div class="selected-icon icon-offline">
                        <i class="bi bi-people-fill"></i>
                      </div>
                      <div class="selected-info">
                        <div class="selected-label">Partisipasi Offline</div>
                        <div class="selected-amount"><?= $rupiah($paidAmount) ?></div>
                      </div>
                    <?php endif; ?>
                  </div>
                  
                  <?php if ($regStatus === 'lunas'): ?>
                    <div class="payment-status-info status-paid">
                      <i class="bi bi-check-circle-fill"></i>
                      <span>Pembayaran Lunas</span>
                    </div>
                  <?php elseif ($regStatus === 'menunggu_pembayaran'): ?>
                    <div class="payment-status-info status-pending">
                      <i class="bi bi-clock-fill"></i>
                      <span>Menunggu Pembayaran</span>
                    </div>
                  <?php endif; ?>
                </div>
                
                <!-- Tampilkan juga harga lainnya untuk informasi (HANYA YANG ADA HARGANYA DAN > 0) -->
                <?php 
                  $hasOtherPrices = false;
                  if ($selectedMode !== 'online' && $priceOnline !== null && $priceOnline !== '' && (float)$priceOnline > 0) {
                    $hasOtherPrices = true;
                  }
                  if ($selectedMode !== 'offline' && $priceOffline !== null && $priceOffline !== '' && (float)$priceOffline > 0) {
                    $hasOtherPrices = true;
                  }
                ?>
                
                <?php if ($hasOtherPrices): ?>
                <div class="other-prices-section">
                  <div class="other-prices-title">Harga Paket Lainnya (Info)</div>
                  <div class="other-prices-grid">
                    <?php if ($selectedMode !== 'online' && $priceOnline !== null && $priceOnline !== '' && (float)$priceOnline > 0): ?>
                      <div class="other-price-item">
                        <i class="bi bi-wifi"></i>
                        <span class="other-label">Online:</span>
                        <span class="other-amount"><?= $rupiah($priceOnline) ?></span>
                      </div>
                    <?php endif; ?>
                    <?php if ($selectedMode !== 'offline' && $priceOffline !== null && $priceOffline !== '' && (float)$priceOffline > 0): ?>
                      <div class="other-price-item">
                        <i class="bi bi-people-fill"></i>
                        <span class="other-label">Offline:</span>
                        <span class="other-amount"><?= $rupiah($priceOffline) ?></span>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
                <?php endif; ?>
                
              <?php else: ?>
                <!-- Jika belum terdaftar, tampilkan harga yang tersedia (HANYA YANG > 0) -->
                <?php 
                  $hasOnlinePrice = $priceOnline !== null && $priceOnline !== '' && (float)$priceOnline > 0;
                  $hasOfflinePrice = $priceOffline !== null && $priceOffline !== '' && (float)$priceOffline > 0;
                ?>
                
                <?php if ($hasOnlinePrice || $hasOfflinePrice): ?>
                  <div class="pricing-grid">
                    <?php if ($hasOnlinePrice): ?>
                    <div class="price-card price-online">
                      <div class="price-icon">
                        <i class="bi bi-wifi"></i>
                      </div>
                      <div class="price-info">
                        <div class="price-label">Partisipasi Online</div>
                        <div class="price-amount"><?= $rupiah($priceOnline) ?></div>
                      </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($hasOfflinePrice): ?>
                    <div class="price-card price-offline">
                      <div class="price-icon">
                        <i class="bi bi-people-fill"></i>
                      </div>
                      <div class="price-info">
                        <div class="price-label">Partisipasi Offline</div>
                        <div class="price-amount"><?= $rupiah($priceOffline) ?></div>
                      </div>
                    </div>
                    <?php endif; ?>
                  </div>

                  <div class="pricing-note">
                    <i class="bi bi-info-circle-fill"></i>
                    <span>Pilih mode partisipasi saat mendaftar sesuai preferensi Anda</span>
                  </div>
                <?php else: ?>
                  <div class="empty-state-small">
                    <i class="bi bi-tags"></i>
                    <p>Informasi harga belum tersedia untuk event ini.</p>
                  </div>
                <?php endif; ?>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Right Column: Action Card -->
        <div class="col-12 col-lg-4">
          <div class="action-card">
            <div class="action-card-header">
              <i class="bi bi-lightning-charge-fill"></i>
              <h5>Panel Aksi</h5>
            </div>

            <div class="action-card-body">
              <?php if ($isRegistered): ?>
                <!-- Registered Section -->
                <div class="info-alert alert-success">
                  <i class="bi bi-check-circle-fill"></i>
                  <div>
                    <strong>Anda Sudah Terdaftar</strong>
                    <div class="small">
                      Mode: <strong><?= esc(strtoupper($myReg['mode_kehadiran'] ?? '-')) ?></strong>
                    </div>
                  </div>
                </div>

                <?php if ($payId && $payStat !== 'canceled'): ?>
                  <!-- Jika sudah punya pembayaran yang belum dibatalkan -->
                  <a class="btn-action btn-primary" 
                     href="<?= site_url('audience/pembayaran/detail/'.$payId) ?>">
                    <i class="bi bi-receipt"></i>
                    <span>Lihat Detail Pembayaran</span>
                  </a>
                <?php elseif ($regStatus === 'menunggu_pembayaran' && $regId): ?>
                  <!-- Belum ada pembayaran atau pembayaran dibatalkan -->
                  <a class="btn-action btn-primary" 
                     href="<?= site_url('audience/pembayaran/instruction/'.$regId) ?>">
                    <i class="bi bi-credit-card-fill"></i>
                    <span>Lanjutkan Pembayaran</span>
                  </a>
                <?php endif; ?>

                <?php if ($isToday && $regStatus === 'lunas'): ?>
                  <a class="btn-action btn-warning" 
                     href="<?= site_url('audience/absensi/event/'.($event['id'] ?? 0)) ?>">
                    <i class="bi bi-qr-code-scan"></i>
                    <span>Absen Sekarang</span>
                  </a>
                <?php endif; ?>

                <a class="btn-action btn-outline" 
                   href="<?= site_url('audience/events') ?>">
                  <i class="bi bi-arrow-left"></i>
                  <span>Kembali ke Event</span>
                </a>

              <?php elseif ($isOpen): ?>
                <!-- Registration Open Section -->
                <?php if ($myReg && in_array($regStatus, ['batal', 'ditolak'], true)): ?>
                  <div class="info-alert alert-warning">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <div>
                      <strong>Pendaftaran Sebelumnya Dibatalkan</strong>
                      <div class="small">Silakan daftar ulang untuk mengikuti event ini</div>
                    </div>
                  </div>
                <?php endif; ?>
                
                <button type="button" id="btnDaftar" class="btn-action btn-primary">
                  <i class="bi bi-calendar-check-fill"></i>
                  <span>Daftar Sekarang</span>
                </button>

                <a class="btn-action btn-outline" 
                   href="<?= site_url('audience/events') ?>">
                  <i class="bi bi-arrow-left"></i>
                  <span>Kembali ke Event</span>
                </a>

                <div class="action-info">
                  <i class="bi bi-lightbulb-fill"></i>
                  <p>Setelah mendaftar, pilih mode partisipasi (Online/Offline) dan lakukan pembayaran untuk konfirmasi</p>
                </div>

              <?php else: ?>
                <!-- Registration Closed Section -->
                <div class="info-alert alert-secondary">
                  <i class="bi bi-lock-fill"></i>
                  <div>
                    <strong>Pendaftaran Ditutup</strong>
                    <div class="small">Event ini tidak menerima pendaftaran baru</div>
                  </div>
                </div>

                <button class="btn-action btn-disabled" disabled>
                  <i class="bi bi-lock-fill"></i>
                  <span>Pendaftaran Ditutup</span>
                </button>

                <a class="btn-action btn-outline" 
                   href="<?= site_url('audience/events') ?>">
                  <i class="bi bi-arrow-left"></i>
                  <span>Kembali ke Event</span>
                </a>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

    </div>
  </main>
</div>

<style>
  :root {
    --blue-50: #eff6ff;
    --blue-100: #dbeafe;
    --blue-200: #bfdbfe;
    --blue-500: #3b82f6;
    --blue-600: #2563eb;
    --blue-700: #1d4ed8;
    --blue-900: #1e3a8a;
    --success: #10b981;
    --warning: #f59e0b;
    --gray-100: #f3f4f6;
    --gray-400: #9ca3af;
    --gray-600: #4b5563;
    --gray-700: #374151;
    --gray-900: #111827;
  }

  body { 
    background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); 
    min-height: 100vh; 
  }

  /* BACK BUTTON */
  .btn-back {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.65rem 1.25rem;
    background: white;
    color: var(--blue-600);
    border: 2px solid var(--blue-200);
    border-radius: 12px;
    text-decoration: none;
    font-weight: 600;
    transition: all 0.3s ease;
  }
  .btn-back:hover {
    background: var(--blue-50);
    border-color: var(--blue-500);
    transform: translateX(-4px);
    color: var(--blue-700);
  }

  /* HERO CARD */
  .hero-card {
    background: linear-gradient(135deg, var(--blue-600), var(--blue-700));
    border-radius: 20px;
    padding: 2.5rem;
    position: relative;
    overflow: hidden;
    box-shadow: 0 10px 40px rgba(37, 99, 235, 0.3);
  }
  .hero-background {
    position: absolute;
    top: -50%;
    right: -10%;
    width: 400px;
    height: 400px;
    background: rgba(255, 255, 255, 0.1);
    border-radius: 50%;
    pointer-events: none;
  }
  .hero-content {
    position: relative;
    z-index: 1;
  }
  .hero-header {
    display: flex;
    justify-content: space-between;
    align-items: start;
    margin-bottom: 1.5rem;
    flex-wrap: wrap;
    gap: 1rem;
  }
  .hero-badges {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
  }
  .hero-badge {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(10px);
    border: 1px solid rgba(255, 255, 255, 0.3);
    border-radius: 20px;
    color: white;
    font-size: 0.875rem;
    font-weight: 600;
  }
  .hero-badge i {
    font-size: 1rem;
  }
  .badge-format {
    background: rgba(255, 255, 255, 0.25);
    border-color: rgba(255, 255, 255, 0.4);
  }
  .badge-today {
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    border-color: #fbbf24;
  }
  .hero-status {
    display: flex;
    gap: 0.5rem;
  }
  .status-badge {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.65rem 1.25rem;
    border-radius: 20px;
    font-weight: 700;
    font-size: 0.9rem;
  }
  .status-registered {
    background: linear-gradient(135deg, var(--success), #059669);
    color: white;
    box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3);
  }
  .status-open {
    background: linear-gradient(135deg, #06b6d4, #0891b2);
    color: white;
    box-shadow: 0 4px 15px rgba(6, 182, 212, 0.3);
  }
  .status-closed {
    background: rgba(255, 255, 255, 0.2);
    color: white;
    border: 1px solid rgba(255, 255, 255, 0.3);
  }
  .hero-title {
    color: white;
    font-size: 2rem;
    font-weight: 800;
    margin: 0;
    line-height: 1.3;
  }

  /* MODERN CARD */
  .modern-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
    border: 1px solid rgba(37, 99, 235, 0.08);
    overflow: hidden;
  }
  .card-header-modern {
    padding: 1.25rem 1.5rem;
    background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
    border-bottom: 2px solid var(--gray-100);
  }
  .header-left {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    font-size: 1.05rem;
    color: var(--gray-900);
  }
  .header-left i {
    font-size: 1.3rem;
    color: var(--blue-600);
  }

  /* DESCRIPTION */
  .description-content {
    color: var(--gray-600);
    line-height: 1.8;
    font-size: 0.95rem;
  }

  /* PRICING GRID */
  .pricing-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
    gap: 1rem;
    margin-bottom: 1.5rem;
  }
  .price-card {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1.5rem;
    border-radius: 14px;
    border: 2px solid;
    transition: all 0.3s ease;
  }
  .price-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
  }
  .price-online {
    background: linear-gradient(135deg, #dbeafe, #bfdbfe);
    border-color: #93c5fd;
  }
  .price-offline {
    background: linear-gradient(135deg, #fef3c7, #fde68a);
    border-color: #fcd34d;
  }
  .price-icon {
    width: 56px;
    height: 56px;
    border-radius: 12px;
    display: grid;
    place-items: center;
    font-size: 1.75rem;
    flex-shrink: 0;
  }
  .price-online .price-icon {
    background: white;
    color: var(--blue-600);
  }
  .price-offline .price-icon {
    background: white;
    color: #d97706;
  }
  .price-info {
    flex: 1;
  }
  .price-label {
    font-size: 0.85rem;
    color: var(--gray-600);
    font-weight: 600;
    margin-bottom: 0.25rem;
  }
  .price-amount {
    font-size: 1.5rem;
    font-weight: 800;
    color: var(--gray-900);
  }
  .pricing-note {
    display: flex;
    align-items: start;
    gap: 0.75rem;
    padding: 1rem;
    background: var(--blue-50);
    border-radius: 12px;
    font-size: 0.875rem;
    color: var(--blue-700);
  }
  .pricing-note i {
    font-size: 1.1rem;
    flex-shrink: 0;
    margin-top: 0.1rem;
  }

  /* SELECTED PRICE CARD (untuk yang sudah terdaftar) */
  .selected-price-card {
    background: linear-gradient(135deg, #f0fdf4, #dcfce7);
    border: 3px solid #86efac;
    border-radius: 16px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
  }
  .selected-badge {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.5rem 1rem;
    background: linear-gradient(135deg, var(--success), #059669);
    color: white;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 700;
    margin-bottom: 1rem;
  }
  .selected-price-content {
    display: flex;
    align-items: center;
    gap: 1.25rem;
    padding: 1.5rem;
    background: white;
    border-radius: 12px;
    margin-bottom: 1rem;
  }
  .selected-icon {
    width: 64px;
    height: 64px;
    border-radius: 14px;
    display: grid;
    place-items: center;
    font-size: 2rem;
    flex-shrink: 0;
  }
  .icon-online {
    background: linear-gradient(135deg, var(--blue-100), #bfdbfe);
    color: var(--blue-700);
  }
  .icon-offline {
    background: linear-gradient(135deg, #fef3c7, #fde68a);
    color: #d97706;
  }
  .selected-info {
    flex: 1;
  }
  .selected-label {
    font-size: 0.9rem;
    color: var(--gray-600);
    font-weight: 600;
    margin-bottom: 0.5rem;
  }
  .selected-amount {
    font-size: 2rem;
    font-weight: 800;
    color: var(--gray-900);
  }
  .payment-status-info {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 1rem;
    border-radius: 12px;
    font-weight: 700;
    font-size: 0.9rem;
  }
  .status-paid {
    background: linear-gradient(135deg, #d1fae5, #a7f3d0);
    color: #065f46;
  }
  .status-paid i {
    font-size: 1.3rem;
  }
  .status-pending {
    background: linear-gradient(135deg, #fef3c7, #fde68a);
    color: #92400e;
  }
  .status-pending i {
    font-size: 1.3rem;
  }

  /* OTHER PRICES SECTION */
  .other-prices-section {
    padding: 1rem;
    background: var(--gray-100);
    border-radius: 12px;
  }
  .other-prices-title {
    font-size: 0.85rem;
    color: var(--gray-600);
    font-weight: 600;
    margin-bottom: 0.75rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
  }
  .other-prices-grid {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }
  .other-price-item {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    padding: 0.75rem;
    background: white;
    border-radius: 8px;
    font-size: 0.875rem;
  }
  .other-price-item i {
    color: var(--blue-500);
    font-size: 1.1rem;
  }
  .other-label {
    color: var(--gray-600);
    font-weight: 500;
  }
  .other-amount {
    margin-left: auto;
    font-weight: 700;
    color: var(--gray-900);
  }

  /* ACTION CARD */
  .action-card {
    background: white;
    border-radius: 16px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
    border: 1px solid rgba(37, 99, 235, 0.08);
    overflow: hidden;
    position: sticky;
    top: 90px;
  }
  .action-card-header {
    background: linear-gradient(135deg, var(--blue-600), var(--blue-700));
    color: white;
    padding: 1.5rem;
    display: flex;
    align-items: center;
    gap: 0.75rem;
  }
  .action-card-header i {
    font-size: 1.5rem;
  }
  .action-card-header h5 {
    margin: 0;
    font-weight: 700;
  }
  .action-card-body {
    padding: 1.5rem;
  }
  .info-alert {
    display: flex;
    align-items: start;
    gap: 0.75rem;
    padding: 1rem;
    border-radius: 12px;
    margin-bottom: 1rem;
    font-size: 0.875rem;
  }
  .info-alert i {
    font-size: 1.3rem;
    flex-shrink: 0;
    margin-top: 0.1rem;
  }
  .alert-success {
    background: linear-gradient(135deg, #d1fae5, #a7f3d0);
    color: #065f46;
  }
  .alert-warning {
    background: linear-gradient(135deg, #fef3c7, #fde68a);
    color: #92400e;
  }
  .alert-secondary {
    background: linear-gradient(135deg, #f3f4f6, #e5e7eb);
    color: var(--gray-700);
  }
  .btn-action {
    width: 100%;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
    padding: 0.875rem 1.5rem;
    border-radius: 12px;
    font-weight: 600;
    font-size: 0.95rem;
    text-decoration: none;
    transition: all 0.3s ease;
    margin-bottom: 0.75rem;
    border: none;
    cursor: pointer;
  }
  .btn-action i {
    font-size: 1.1rem;
  }
  .btn-action.btn-primary {
    background: linear-gradient(135deg, var(--blue-600), var(--blue-700));
    color: white;
  }
  .btn-action.btn-primary:hover {
    background: linear-gradient(135deg, var(--blue-700), var(--blue-900));
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(37, 99, 235, 0.4);
    color: white;
  }
  .btn-action.btn-warning {
    background: linear-gradient(135deg, #fbbf24, #f59e0b);
    color: white;
  }
  .btn-action.btn-warning:hover {
    background: linear-gradient(135deg, #f59e0b, #d97706);
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(245, 158, 11, 0.4);
    color: white;
  }
  .btn-action.btn-outline {
    background: white;
    color: var(--blue-600);
    border: 2px solid var(--blue-200);
  }
  .btn-action.btn-outline:hover {
    background: var(--blue-50);
    border-color: var(--blue-500);
    transform: translateY(-1px);
    color: var(--blue-700);
  }
  .btn-action.btn-disabled {
    background: var(--gray-100);
    color: var(--gray-600);
    cursor: not-allowed;
  }
  .action-info {
    display: flex;
    align-items: start;
    gap: 0.75rem;
    padding: 1rem;
    background: #fffbeb;
    border-radius: 12px;
    font-size: 0.8rem;
    color: #92400e;
    line-height: 1.5;
  }
  .action-info i {
    color: #f59e0b;
    font-size: 1.1rem;
    flex-shrink: 0;
    margin-top: 0.1rem;
  }
  .action-info p {
    margin: 0;
  }

  /* EMPTY STATE */
  .empty-state-small {
    text-align: center;
    padding: 2rem 1rem;
    color: var(--gray-600);
  }
  .empty-state-small i {
    font-size: 2.5rem;
    color: var(--gray-400);
    margin-bottom: 0.75rem;
    display: block;
  }
  .empty-state-small p {
    margin: 0;
    font-size: 0.95rem;
  }

  /* RESPONSIVE */
  @media (max-width: 991px) {
    .action-card {
      position: relative;
      top: 0;
    }
  }

  @media (max-width: 768px) {
    .hero-card {
      padding: 1.5rem;
    }
    .hero-title {
      font-size: 1.5rem;
    }
    .hero-header {
      flex-direction: column;
    }
    .hero-status {
      width: 100%;
    }
    .status-badge {
      flex: 1;
      justify-content: center;
    }
    .pricing-grid {
      grid-template-columns: 1fr;
    }
  }

  @media (max-width: 576px) {
    .hero-card {
      padding: 1.25rem;
    }
    .hero-title {
      font-size: 1.25rem;
    }
    .hero-badge {
      padding: 0.4rem 0.75rem;
      font-size: 0.8rem;
    }
    .price-card {
      padding: 1.25rem;
    }
    .price-amount {
      font-size: 1.25rem;
    }
    .selected-amount {
      font-size: 1.5rem;
    }
    .selected-price-content {
      flex-direction: column;
      text-align: center;
      gap: 1rem;
    }
    .selected-icon {
      margin: 0 auto;
    }
  }
</style>

<script>
document.getElementById('btnDaftar')?.addEventListener('click', function(){
  const go = ()=> location.href = "<?= site_url('audience/events/register/'.($event['id'] ?? 0)) ?>";
  if (window.Swal) {
    Swal.fire({
      title: 'Daftar Event Ini?',
      html: '<div style="margin:1rem 0;line-height:1.6;color:#6b7280;">Anda akan memilih mode partisipasi (Online/Offline) di langkah berikutnya sesuai ketersediaan.</div>',
      icon: 'question',
      iconColor: '#3b82f6',
      showCancelButton: true,
      confirmButtonText: '<i class="bi bi-check-circle me-2"></i>Ya, Lanjut Daftar',
      cancelButtonText: '<i class="bi bi-x-circle me-2"></i>Batal',
      confirmButtonColor: '#2563eb',
      cancelButtonColor: '#6b7280'
    }).then(r=>{ if(r.isConfirmed) go(); });
  } else {
    if (confirm('Yakin lanjut daftar?')) go();
  }
});
</script>

<?= $this->include('partials/footer') ?>