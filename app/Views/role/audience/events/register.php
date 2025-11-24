<?php

$title   = 'Pilih Mode Kehadiran';
$event   = $event ?? [];
$options = $options ?? [];
$pricing = $pricing ?? [];
$waveInfo = $waveInfo ?? null; // Wave info from controller
$rupiah  = function($n){ return ($n===null||$n==='') ? '—' : 'Rp '.number_format((float)$n,0,',','.'); };

$priceOnline  = $pricing['audience']['online']  ?? null;
$priceOffline = $pricing['audience']['offline'] ?? null;

$eventFormat = strtolower($event['format'] ?? '');
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_audience') ?>
<?= $this->include('partials/alerts') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/events_register_audience.css'); ?>">

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <!-- Back Button -->
      <div class="mb-3">
        <a href="<?= site_url('audience/events/detail/'.($event['id'] ?? 0)) ?>" class="btn-back">
          <i class="bi bi-arrow-left"></i>
          <span>Kembali ke Detail Event</span>
        </a>
      </div>

      <!-- ===== Wave Info Card ===== -->
      <?php if (!empty($waveInfo)): ?>
      <div class="wave-info-card mb-4">
        <div class="wave-badge <?= isset($waveInfo['wave_number']) && $waveInfo['wave_number'] == 1 ? 'badge-early' : 'badge-regular' ?>">
          <i class="bi bi-lightning-charge-fill"></i>
          <span>
            <?php if(isset($waveInfo['wave_number']) && $waveInfo['wave_number'] == 1): ?>
              EARLY BIRD - Wave <?= $waveInfo['wave_number'] ?>
            <?php else: ?>
              WAVE <?= $waveInfo['wave_number'] ?? '1' ?>
            <?php endif; ?>
          </span>
        </div>
        
        <div class="wave-content">
          <div class="wave-title">
            <i class="bi bi-hourglass-split"></i>
            <strong>Registrasi Ditutup:</strong>
            <?= date('d M Y, H:i', strtotime($waveInfo['deadline'])) ?>
          </div>
          
          <?php 
          $now = time();
          $deadline = strtotime($waveInfo['deadline']);
          $daysLeft = ceil(($deadline - $now) / 86400);
          if ($daysLeft > 0):
          ?>
          <div class="wave-countdown">
            <i class="bi bi-clock-fill"></i>
            <span class="countdown-text">
              Sisa waktu: <strong><?= $daysLeft ?> hari</strong>
            </span>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <?php endif; ?>

      <!-- Event Summary Card -->
      <div class="event-summary-card mb-4">
        <div class="summary-header">
          <i class="bi bi-info-circle-fill"></i>
          <h5>Ringkasan Event</h5>
        </div>
        <div class="summary-body">
          <div class="event-title">
            <h4><?= esc($event['title'] ?? 'Event') ?></h4>
          </div>
          
          <div class="event-meta">
            <div class="meta-item">
              <i class="bi bi-calendar-event"></i>
              <span><?= esc(isset($event['event_date']) ? date('d M Y', strtotime($event['event_date'])) : '-') ?></span>
            </div>
            <div class="meta-item">
              <i class="bi bi-clock-fill"></i>
              <span><?= esc($event['event_time'] ?? '-') ?></span>
            </div>
            <div class="meta-item meta-format">
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
              <div class="meta-item">
                <i class="bi bi-pin-map-fill"></i>
                <span><?= esc($event['location']) ?></span>
              </div>
            <?php endif; ?>
          </div>

          <div class="price-summary">
            <div class="price-label">Harga Tiket Audience</div>
            <div class="price-badges">
              <?php if($priceOnline !== null && $priceOnline !== '' && (float)$priceOnline > 0): ?>
              <div class="price-badge badge-online">
                <i class="bi bi-wifi"></i>
                <span class="badge-label">Online:</span>
                <span class="badge-price">
                  <strong><?= $rupiah($priceOnline) ?></strong>
                </span>
              </div>
              <?php endif; ?>
              
              <?php if($priceOffline !== null && $priceOffline !== '' && (float)$priceOffline > 0): ?>
              <div class="price-badge badge-offline">
                <i class="bi bi-people-fill"></i>
                <span class="badge-label">Offline:</span>
                <span class="badge-price">
                  <strong><?= $rupiah($priceOffline) ?></strong>
                </span>
              </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- Mode Selection Card -->
      <div class="mode-selection-card">
        <div class="selection-header">
          <i class="bi bi-check-square-fill"></i>
          <h5>Pilih Mode Kehadiran</h5>
        </div>
        <div class="selection-body">
          <form id="regForm" action="<?= site_url('audience/events/register/'.($event['id'] ?? 0)) ?>" method="post">
            <?= csrf_field() ?>

            <?php if (empty($options)): ?>
              <div class="alert-empty">
                <i class="bi bi-exclamation-triangle-fill"></i>
                <div>
                  <strong>Mode Tidak Tersedia</strong>
                  <p>Mode kehadiran tidak tersedia untuk event ini.</p>
                </div>
              </div>
            <?php else: ?>

              <div class="mode-options">
                <?php foreach ($options as $opt): 
                  $price = $pricing['audience'][$opt] ?? null;
                  
                  if ($price === null || $price === '' || (float)$price == 0) continue;
                  
                  $icon  = $opt === 'online' ? 'bi-wifi' : 'bi-people-fill';
                  $colorClass = $opt === 'online' ? 'option-online' : 'option-offline';
                ?>
                <label class="mode-option-card <?= $colorClass ?>" for="mode_<?= esc($opt) ?>">
                  <div class="option-radio">
                    <input class="form-check-input mode-radio" 
                           type="radio" 
                           name="mode_kehadiran"
                           id="mode_<?= esc($opt) ?>" 
                           value="<?= esc($opt) ?>" 
                           data-price="<?= $price ?>"
                           required>
                  </div>
                  
                  <div class="option-main">
                    <div class="option-icon">
                      <i class="bi <?= $icon ?>"></i>
                    </div>
                    
                    <div class="option-content">
                      <div class="option-title"><?= esc(ucfirst($opt)) ?></div>
                      <div class="option-price">
                        <span class="price-amount"><?= $rupiah($price) ?></span>
                      </div>
                      <div class="option-description">
                        <?php if($opt === 'online'): ?>
                          Ikuti event secara virtual dari mana saja
                        <?php else: ?>
                          Hadir langsung di lokasi event
                        <?php endif; ?>
                      </div>
                    </div>
                    
                    <div class="option-check">
                      <i class="bi bi-check-circle-fill"></i>
                    </div>
                  </div>

                  <!-- Benefits List -->
                  <div class="option-benefits">
                    <div class="benefits-title">
                      <i class="bi bi-gift-fill"></i>
                      <span>Apa yang Anda dapatkan:</span>
                    </div>
                    <ul class="benefits-list">
                      <?php if($opt === 'online'): ?>
                        <li>
                          <i class="bi bi-check2"></i>
                          <span><strong>Live Streaming</strong> - Akses streaming real-time</span>
                        </li>
                        <li>
                          <i class="bi bi-check2"></i>
                          <span><strong>Digital Certificate</strong> - E-sertifikat resmi</span>
                        </li>
                        <li>
                          <i class="bi bi-check2"></i>
                          <span><strong>Recording Access</strong> - Akses rekaman </span>
                        </li>
                        <li>
                          <i class="bi bi-check2"></i>
                          <span><strong>Q&A Session</strong> - Interaksi via chat langsung</span>
                        </li>
                      <?php else: ?>
                        <li>
                          <i class="bi bi-check2"></i>
                          <span><strong>Attend Seminar</strong> - Hadir langsung di venue acara</span>
                        </li>
                        <li>
                          <i class="bi bi-check2"></i>
                          <span><strong>Exclusive Merchandise</strong> - Merchandise Eksklusif</span>
                        </li>
                        <li>
                          <i class="bi bi-check2"></i>
                          <span><strong>Lunch & Coffee</strong> - Makan siang dan snack</span>
                        </li>
                        <li>
                          <i class="bi bi-check2"></i>
                          <span><strong>Networking</strong> - Bertemu langsung dengan peserta lain</span>
                        </li>
                      <?php endif; ?>
                    </ul>
                  </div>
                </label>
                <?php endforeach; ?>
              </div>

              <div class="form-actions">
                <button type="button" id="btnSubmit" class="btn-submit">
                  <i class="bi bi-arrow-right-circle-fill"></i>
                  <span>Lanjut ke Pembayaran</span>
                </button>
                <a href="<?= site_url('audience/events/detail/'.($event['id'] ?? 0)) ?>" class="btn-cancel">
                  <i class="bi bi-x-circle"></i>
                  <span>Batal</span>
                </a>
              </div>
            <?php endif; ?>
          </form>
        </div>
      </div>

    </div>
  </main>
</div>

<script>
// ===== SIMPLE JAVASCRIPT WITHOUT VOUCHER =====

let selectedPrice = 0;

// Update price display when mode is selected
document.querySelectorAll('.mode-radio').forEach(radio => {
  radio.addEventListener('change', function() {
    selectedPrice = parseFloat(this.dataset.price) || 0;
    console.log('Selected price:', selectedPrice);
  });
});

// Submit handler
document.getElementById('btnSubmit')?.addEventListener('click', function(){
  const f = document.getElementById('regForm');
  const selectedMode = f.querySelector('input[name="mode_kehadiran"]:checked');
  
  if (!selectedMode) {
    if (window.Swal) {
      Swal.fire({
        title: 'Pilih Mode Kehadiran',
        text: 'Silakan pilih mode kehadiran terlebih dahulu (Online atau Offline)',
        icon: 'warning',
        confirmButtonText: 'OK',
        confirmButtonColor: '#2563eb'
      });
    } else {
      alert('Silakan pilih mode kehadiran terlebih dahulu');
    }
    return;
  }

  const go = ()=> f.submit();
  const modeName = selectedMode.value.toUpperCase();
  const amount = parseFloat(selectedMode.dataset.price) || 0;
  
  function formatNumber(num) {
    return Math.round(num).toString().replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  }
  
  let confirmHtml = `<div style="margin:1rem 0;line-height:1.8;color:#6b7280;text-align:left;">
    <p style="margin-bottom:0.75rem;">
      <strong style="color:#2563eb;">Mode Kehadiran:</strong> ${modeName}
    </p>
    <p style="margin-bottom:0;padding-top:0.75rem;border-top:2px solid #e5e7eb;">
      <strong style="color:#2563eb;font-size:1.1rem;">Total Bayar:</strong> 
      <strong style="color:#2563eb;font-size:1.1rem;">Rp ${formatNumber(amount)}</strong>
    </p>
  </div>`;
  
  if (window.Swal) {
    Swal.fire({
      title: 'Konfirmasi Pendaftaran',
      html: confirmHtml,
      icon: 'question',
      iconColor: '#2563eb',
      showCancelButton: true,
      confirmButtonText: '<i class="bi bi-check-circle me-2"></i>Ya, Lanjutkan ke Pembayaran',
      cancelButtonText: '<i class="bi bi-x-circle me-2"></i>Batal',
      confirmButtonColor: '#2563eb',
      cancelButtonColor: '#6b7280',
      width: '500px'
    }).then(r=>{ if(r.isConfirmed) go(); });
  } else {
    if (confirm(`Mode: ${modeName}\nTotal: Rp ${formatNumber(amount)}\n\nLanjut ke pembayaran?`)) go();
  }
});

// Initialize
document.addEventListener('DOMContentLoaded', function() {
  // Select first option if only one available
  const modeRadios = document.querySelectorAll('.mode-radio');
  if (modeRadios.length === 1) {
    modeRadios[0].checked = true;
    selectedPrice = parseFloat(modeRadios[0].dataset.price) || 0;
  }
});
</script>

<?= $this->include('partials/footer') ?>