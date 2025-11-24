<?php
  $title     = 'Detail Event';
  $event     = $event  ?? [];
  $isOpen    = $isOpen ?? false;
  $myReg     = $myReg  ?? null;
  $options   = $options ?? [];
  $pricing   = $pricing ?? [];
  $waveInfo  = $waveInfo ?? null;
  $allWaves  = $allWaves ?? [];

  $regId     = isset($myReg['id']) ? (int)$myReg['id'] : null;
  $payId     = isset($myReg['id_pembayaran']) ? (int)$myReg['id_pembayaran'] : null;
  $payStat   = $myReg['payment_status'] ?? null;
  $regStatus = $myReg['status'] ?? null;

  $isRegistered = $myReg && !in_array($regStatus, ['batal', 'ditolak'], true);

  $fmtDate = function($d){ return $d ? date('d M Y', strtotime($d)) : '-'; };
  $rupiah  = function($n){ return ($n===null||$n==='') ? '—' : 'Rp '.number_format((float)$n,0,',','.'); };

  $getWaveName = function($waveNum) {
    if ($waveNum === 1) return 'Gelombang 1 (Early Bird)';
    if ($waveNum === 2) return 'Gelombang 2 (Normal)';
    if ($waveNum === 3) return 'Gelombang 3 (Last Call)';
    return 'Gelombang ' . $waveNum;
  };

  $priceOnline  = $pricing['audience']['online']  ?? null;
  $priceOffline = $pricing['audience']['offline'] ?? null;

  $isToday = isset($event['event_date']) && date('Y-m-d', strtotime($event['event_date'])) === date('Y-m-d');
  $eventFormat = strtolower($event['format'] ?? '');
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_audience') ?>
<?= $this->include('partials/alerts') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/events_detail_audience.css'); ?>">

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
              <?php if ($isOpen && $waveInfo && isset($waveInfo['wave_number'])): ?>
                <div class="hero-badge badge-wave-<?= (int)$waveInfo['wave_number'] ?>">
                  <i class="bi bi-lightning-charge-fill"></i>
                  <span><?= $getWaveName($waveInfo['wave_number']) ?></span>
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
          
          <?php if ($isOpen && $waveInfo && isset($waveInfo['deadline'])): ?>
            <div class="wave-deadline-info">
              <i class="bi bi-alarm-fill"></i>
              <span>Gelombang ini berakhir: <strong><?= date('d M Y, H:i', strtotime($waveInfo['deadline'])) ?></strong></span>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- GRID CONTENT -->
      <div class="row g-4">
        <!-- Left Column -->
        <div class="col-12 col-lg-8">
          
          <!-- Description -->
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

          <!-- Benefits -->
          <div class="modern-card mb-4">
            <div class="card-header-modern">
              <div class="header-left">
                <i class="bi bi-gift-fill"></i>
                <strong>Fasilitas & Benefit</strong>
              </div>
            </div>
            <div class="card-body p-4">
              <div class="benefits-grid">
                <!-- OFFLINE BENEFITS -->
                <div class="benefit-column benefit-offline">
                  <div class="benefit-header">
                    <div class="benefit-icon">
                      <i class="bi bi-people-fill"></i>
                    </div>
                    <div class="benefit-title">
                      <h5>Peserta Offline</h5>
                      <p>Hadir Langsung di Venue</p>
                    </div>
                  </div>
                  <div class="benefit-list">
                    <div class="benefit-item">
                      <i class="bi bi-check-circle-fill"></i>
                      <span>Menghadiri Seminar Seharian Penuh</span>
                    </div>
                    <div class="benefit-item">
                      <i class="bi bi-check-circle-fill"></i>
                      <span>Merchandise Eksklusif</span>
                    </div>
                    <div class="benefit-item">
                      <i class="bi bi-check-circle-fill"></i>
                      <span>Makan Siang & Snack (2x Coffee Break)</span>
                    </div>
                    <div class="benefit-item">
                      <i class="bi bi-check-circle-fill"></i>
                      <span>Sesi Networking dengan Pembicara</span>
                    </div>
                    <div class="benefit-item">
                      <i class="bi bi-check-circle-fill"></i>
                      <span>Materi Event</span>
                    </div>
                    <div class="benefit-item">
                      <i class="bi bi-check-circle-fill"></i>
                      <span>Dokumentasi Foto & Sertifikat Digital</span>
                    </div>
                    <div class="benefit-item">
                      <i class="bi bi-check-circle-fill"></i>
                      <span>Akses Rekaman Event </span>
                    </div>
                    <div class="benefit-item">
                      <i class="bi bi-check-circle-fill"></i>
                      <span>Grup WhatsApp Peserta</span>
                    </div>
                    <div class="benefit-item">
                      <i class="bi bi-check-circle-fill"></i>
                      <span>Parkir Gratis & Kesempatan Doorprize</span>
                    </div>
                  </div>
                </div>

                <!-- ONLINE BENEFITS -->
                <div class="benefit-column benefit-online">
                  <div class="benefit-header">
                    <div class="benefit-icon">
                      <i class="bi bi-wifi"></i>
                    </div>
                    <div class="benefit-title">
                      <h5>Peserta Online</h5>
                      <p>Ikuti dari Mana Saja</p>
                    </div>
                  </div>
                  <div class="benefit-list">
                    <div class="benefit-item">
                      <i class="bi bi-check-circle-fill"></i>
                      <span>Live Streaming Full HD (Zoom/YouTube)</span>
                    </div>
                    <div class="benefit-item">
                      <i class="bi bi-check-circle-fill"></i>
                      <span>Sertifikat Partisipasi Digital</span>
                    </div>
                    <div class="benefit-item">
                      <i class="bi bi-check-circle-fill"></i>
                      <span>Akses Rekaman Event</span>
                    </div>
                    <div class="benefit-item">
                      <i class="bi bi-check-circle-fill"></i>
                      <span>Sesi Tanya Jawab Interaktif dengan Pembicara</span>
                    </div>
                    <div class="benefit-item">
                      <i class="bi bi-check-circle-fill"></i>
                      <span>Materi Event Digital (PDF/PPT)</span>
                    </div>
                    <div class="benefit-item">
                      <i class="bi bi-check-circle-fill"></i>
                      <span>Grup WhatsApp Peserta Online</span>
                    </div>
                    <div class="benefit-item">
                      <i class="bi bi-check-circle-fill"></i>
                      <span>Fitur Live Chat & Polling</span>
                    </div>
                    <div class="benefit-item">
                      <i class="bi bi-check-circle-fill"></i>
                      <span>Diskusi Ruang Breakout</span>
                    </div>
                    <div class="benefit-item">
                      <i class="bi bi-check-circle-fill"></i>
                      <span>Forum Diskusi Pasca Event</span>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Pricing -->
          <div class="modern-card">
            <div class="card-header-modern">
              <div class="header-left">
                <i class="bi bi-tags-fill"></i>
                <strong>Harga Tiket</strong>
              </div>
              <?php if ($isOpen && $waveInfo && isset($waveInfo['wave_number'])): ?>
                <span class="wave-badge-small wave-<?= (int)$waveInfo['wave_number'] ?>">
                  <?= $getWaveName($waveInfo['wave_number']) ?>
                </span>
              <?php endif; ?>
            </div>
            <div class="card-body p-4">
              <?php if ($isRegistered && !empty($myReg['mode_kehadiran'])): ?>
                <!-- Already Registered -->
                <?php 
                  $selectedMode = strtolower($myReg['mode_kehadiran']);
                  $paidAmount = isset($myReg['jumlah_bayar']) ? (float)$myReg['jumlah_bayar'] : 0;
                ?>
                <div class="selected-price-card">
                  <div class="selected-badge">
                    <i class="bi bi-check-circle-fill"></i>
                    <span>Pilihan Anda</span>
                  </div>
                  <div class="selected-price-content">
                    <div class="selected-icon icon-<?= $selectedMode ?>">
                      <i class="bi bi-<?= $selectedMode === 'online' ? 'wifi' : 'people-fill' ?>"></i>
                    </div>
                    <div class="selected-info">
                      <div class="selected-label">Partisipasi <?= ucfirst($selectedMode) ?></div>
                      <div class="selected-amount"><?= $rupiah($paidAmount) ?></div>
                    </div>
                  </div>
                  
                  <?php if ($regStatus === 'lunas'): ?>
                    <div class="payment-status-badge status-paid">
                      <i class="bi bi-check-circle-fill"></i>
                      <span>Pembayaran Lunas</span>
                    </div>
                  <?php elseif ($regStatus === 'menunggu_pembayaran'): ?>
                    <div class="payment-status-badge status-pending">
                      <i class="bi bi-clock-fill"></i>
                      <span>Menunggu Pembayaran</span>
                    </div>
                  <?php endif; ?>
                </div>
                
              <?php else: ?>
                <!-- Not Registered Yet -->
                <?php 
                  $hasOnline = $priceOnline !== null && $priceOnline !== '' && (float)$priceOnline > 0;
                  $hasOffline = $priceOffline !== null && $priceOffline !== '' && (float)$priceOffline > 0;
                ?>
                
                <?php if ($hasOnline || $hasOffline): ?>
                  <?php if ($isOpen && $waveInfo): ?>
                    <div class="pricing-note-top">
                      <i class="bi bi-info-circle-fill"></i>
                      <span>Harga di bawah berlaku untuk <strong><?= $getWaveName($waveInfo['wave_number'] ?? 0) ?></strong></span>
                    </div>
                  <?php endif; ?>
                  
                  <div class="pricing-grid">
                    <?php if ($hasOnline): ?>
                    <div class="price-card price-online">
                      <div class="price-icon">
                        <i class="bi bi-wifi"></i>
                      </div>
                      <div class="price-info">
                        <div class="price-label">Online</div>
                        <div class="price-amount"><?= $rupiah($priceOnline) ?></div>
                      </div>
                    </div>
                    <?php endif; ?>

                    <?php if ($hasOffline): ?>
                    <div class="price-card price-offline">
                      <div class="price-icon">
                        <i class="bi bi-people-fill"></i>
                      </div>
                      <div class="price-info">
                        <div class="price-label">Offline</div>
                        <div class="price-amount"><?= $rupiah($priceOffline) ?></div>
                      </div>
                    </div>
                    <?php endif; ?>
                  </div>

                  <?php if ($isOpen && $waveInfo && $waveInfo['wave_number'] < 3): ?>
                    <div class="pricing-note">
                      <i class="bi bi-lightbulb-fill"></i>
                      <span>💡 <strong>Tips:</strong> Daftar sekarang untuk dapat harga terbaik!</span>
                    </div>
                  <?php endif; ?>
                <?php else: ?>
                  <div class="empty-state-small">
                    <i class="bi bi-tags"></i>
                    <p>Informasi harga belum tersedia</p>
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
                <!-- REGISTERED STATE -->
                <div class="info-alert alert-success">
                  <i class="bi bi-check-circle-fill"></i>
                  <div>
                    <strong>Anda Sudah Terdaftar</strong>
                    <div class="small">Mode: <strong><?= esc(strtoupper($myReg['mode_kehadiran'] ?? '-')) ?></strong></div>
                  </div>
                </div>

                <?php if ($payId && $payStat !== 'canceled'): ?>
                  <a class="btn-action btn-primary" href="<?= site_url('audience/pembayaran/detail/'.$payId) ?>">
                    <i class="bi bi-receipt"></i>
                    <span>Lihat Detail Pembayaran</span>
                  </a>
                <?php elseif ($regStatus === 'menunggu_pembayaran' && $regId): ?>
                  <a class="btn-action btn-primary" href="<?= site_url('audience/pembayaran/instruction/'.$regId) ?>">
                    <i class="bi bi-credit-card-fill"></i>
                    <span>Lanjutkan Pembayaran</span>
                  </a>
                <?php endif; ?>

                <?php if ($isToday && $regStatus === 'lunas'): ?>
                  <a class="btn-action btn-warning" href="<?= site_url('audience/absensi/event/'.($event['id'] ?? 0)) ?>">
                    <i class="bi bi-qr-code-scan"></i>
                    <span>Absen Sekarang</span>
                  </a>
                <?php endif; ?>

                <a class="btn-action btn-outline" href="<?= site_url('audience/events') ?>">
                  <i class="bi bi-arrow-left"></i>
                  <span>Kembali ke Event</span>
                </a>

              <?php elseif ($isOpen): ?>
                <!-- REGISTRATION OPEN STATE -->
                <?php if ($waveInfo && isset($waveInfo['wave_number'])): ?>
                  <div class="wave-info-card">
                    <div class="wave-info-header">
                      <i class="bi bi-lightning-fill"></i>
                      <span><?= $getWaveName($waveInfo['wave_number']) ?></span>
                    </div>
                    <div class="wave-info-body">
                      <div class="wave-info-item">
                        <i class="bi bi-calendar-check"></i>
                        <span>Dibuka: <?= date('d M, H:i', strtotime($waveInfo['start'])) ?></span>
                      </div>
                      <div class="wave-info-item">
                        <i class="bi bi-alarm"></i>
                        <span>Ditutup: <?= date('d M, H:i', strtotime($waveInfo['deadline'])) ?></span>
                      </div>
                    </div>
                  </div>
                <?php endif; ?>
                
                <button type="button" id="btnDaftar" class="btn-action btn-primary">
                  <i class="bi bi-calendar-check-fill"></i>
                  <span>Daftar Sekarang</span>
                </button>

                <a class="btn-action btn-outline" href="<?= site_url('audience/events') ?>">
                  <i class="bi bi-arrow-left"></i>
                  <span>Kembali ke Event</span>
                </a>

                <div class="action-info">
                  <i class="bi bi-lightbulb-fill"></i>
                  <p>Pilih mode partisipasi dan lakukan pembayaran untuk konfirmasi pendaftaran Anda.</p>
                </div>

              <?php else: ?>
                <!-- REGISTRATION CLOSED STATE -->
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

                <a class="btn-action btn-outline" href="<?= site_url('audience/events') ?>">
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

<script>
document.getElementById('btnDaftar')?.addEventListener('click', function(){
  const go = ()=> location.href = "<?= site_url('audience/events/register/'.($event['id'] ?? 0)) ?>";
  if (window.Swal) {
    Swal.fire({
      title: 'Daftar Event Ini?',
      html: '<div style="margin:1rem 0;line-height:1.6;color:#6b7280;">Anda akan memilih mode partisipasi (Online/Offline) di langkah berikutnya.</div>',
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