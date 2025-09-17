<?php
$title    = $title ?? 'Detail Absensi';
$event    = $event ?? [];
$payment  = $payment ?? [];
$window   = $window ?? ['is_open'=>false,'start_ts'=>null,'end_ts'=>null,'reason'=>''];
$attended = $attended ?? false;

// Set timezone ke WIB untuk konsistensi
date_default_timezone_set('Asia/Jakarta');

// Helper function untuk konversi ke WIB
function toWIBFormat($timestamp, $format = 'd M Y H:i') {
    if (!$timestamp) return '-';
    $dt = new DateTime();
    $dt->setTimestamp($timestamp);
    $dt->setTimezone(new DateTimeZone('Asia/Jakarta'));
    return $dt->format($format);
}

// Format waktu dengan WIB
$startText = toWIBFormat($window['start_ts']);
$endText   = toWIBFormat($window['end_ts']);

// Ambil waktu WIB saat ini untuk display
$currentWIB = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
$currentWIBString = $currentWIB->format('Y-m-d H:i:s');
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <!-- HEADER -->
      <div class="header-section header-blue d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 class="welcome-text mb-1"><i class="bi bi-qr-code"></i> Detail Absensi</h2>
          <div class="text-white-50">Event: <?= esc($event['title'] ?? '-') ?></div>
          <div class="text-white-50 small">Waktu Sekarang (WIB): <?= $currentWIBString ?></div>
        </div>
        <div class="text-end d-none d-md-block">
          <small class="text-white-50 d-block">Window Absensi (WIB)</small>
          <strong class="text-white"><?= $startText ?> - <?= $endText ?></strong>
        </div>
      </div> 

      <!-- STATUS + AKSI -->
      <div class="card shadow-sm mb-4">
        <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
          <div class="d-flex align-items-center gap-3">
            <div class="status-dot <?= $attended ? 'bg-success' : ($window['is_open'] ? 'bg-primary' : 'bg-secondary') ?>"></div>
            <div>
              <div class="fw-semibold mb-1">Status</div>
              <?php if ($attended): ?>
                <div class="text-success"><i class="bi bi-check-circle"></i> Anda sudah tercatat hadir sebagai Presenter.</div>
              <?php elseif ($window['is_open']): ?>
                <div class="text-primary"><i class="bi bi-door-open"></i> Window absensi sedang dibuka untuk Presenter.</div>
              <?php else: ?>
                <div class="text-muted"><i class="bi bi-lock"></i> Window absensi tertutup <?= $window['reason'] ? '('.esc($window['reason']).')' : '' ?>.</div>
              <?php endif; ?>
              <div class="small text-muted mt-1">
                Lokasi: <?= esc($event['location'] ?? '-') ?>
                <?php if (!empty($event['format'])): ?> · Format: <?= strtoupper(esc($event['format'])) ?><?php endif; ?>
                · Role: <span class="badge bg-purple">Presenter</span>
                <?php if (isset($window['current_time_wib'])): ?>
                · Waktu WIB: <?= $window['current_time_wib'] ?>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <div class="d-flex flex-wrap gap-2">
            <?php if (!$attended && $window['is_open']): ?>
              <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tokenModal">
                <i class="bi bi-input-cursor-text"></i> Masukkan Token & Absen
              </button>
              <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#qrScannerModal">
                <i class="bi bi-qr-code-scan"></i> Scan QR Code Real-time
              </button>
            <?php else: ?>
              <button class="btn btn-outline-secondary" disabled>
                <i class="bi bi-input-cursor-text"></i> Masukkan Token
              </button>
              <button class="btn btn-outline-secondary" disabled>
                <i class="bi bi-qr-code-scan"></i> Scan QR Code
              </button>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- INFO EVENT & PAYMENT (unchanged) -->
      <div class="row g-3">
        <div class="col-12 col-lg-8">
          <div class="card shadow-sm h-100">
            <div class="card-header bg-light"><strong><i class="bi bi-info-circle"></i> Informasi Event</strong></div>
            <div class="card-body">
              <h5 class="mb-2"><?= esc($event['title'] ?? '-') ?></h5>
              <div class="text-muted small mb-3"><?= nl2br(esc($event['description'] ?? '-')) ?></div>
              <div class="row gy-2">
                <div class="col-12 col-md-6">
                  <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-calendar-event text-primary"></i>
                    <div>
                      <div class="small text-muted">Tanggal & Waktu (WIB)</div>
                      <div class="fw-semibold">
                        <?php if ($event['event_date']): ?>
                          <?php 
                          $eventDateTime = $event['event_date'] . ' ' . ($event['event_time'] ?? '00:00:00');
                          $eventDt = new DateTime($eventDateTime, new DateTimeZone('Asia/Jakarta'));
                          ?>
                          <?= $eventDt->format('d M Y') ?> · <?= $eventDt->format('H:i') ?> WIB
                        <?php else: ?>
                          -
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-12 col-md-6">
                  <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-geo-alt text-primary"></i>
                    <div>
                      <div class="small text-muted">Lokasi</div>
                      <div class="fw-semibold"><?= esc($event['location'] ?? '-') ?></div>
                    </div>
                  </div>
                </div>
                <?php if (!empty($event['zoom_link'])): ?>
                <div class="col-12">
                  <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-camera-video text-primary"></i>
                    <div>
                      <div class="small text-muted">Link Zoom</div>
                      <a href="<?= esc($event['zoom_link']) ?>" target="_blank" class="fw-semibold">Buka Tautan</a>
                    </div>
                  </div>
                </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

        <!-- INFO BAYAR -->
        <div class="col-12 col-lg-4">
          <div class="card shadow-sm h-100">
            <div class="card-header bg-light"><strong><i class="bi bi-receipt"></i> Status Pembayaran</strong></div>
            <div class="card-body">
              <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge bg-success">Verified</span>
                <div class="small text-muted">Anda berhak melakukan absensi sebagai Presenter.</div>
              </div>
              <div class="small text-muted">Metode</div>
              <div class="fw-semibold mb-2"><?= esc($payment['metode'] ?? '-') ?></div>
              <div class="small text-muted">Jumlah</div>
              <div class="fw-semibold">Rp <?= number_format((int)($payment['jumlah'] ?? 0), 0, ',', '.') ?></div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </main>
</div>

<!-- MODAL TOKEN MANUAL -->
<div class="modal fade" id="tokenModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form action="<?= site_url('presenter/absensi/scan') ?>" method="POST" id="tokenForm">
        <?= csrf_field() ?>
        <div class="modal-header">
          <h5 class="modal-title"><i class="bi bi-input-cursor-text me-2"></i> Masukkan Token</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="event_id" value="<?= (int)($event['id'] ?? 0) ?>">
          
          <div class="alert alert-warning small mb-3">
            <i class="bi bi-shield-exclamation me-2"></i>
            <strong>Khusus Presenter:</strong> Pastikan QR Code yang Anda scan adalah QR Code <strong>Presenter</strong> atau <strong>Universal</strong>. QR Code audience tidak dapat digunakan.
          </div>

          <div class="mb-2">
            <label class="form-label">Token Kehadiran <span class="text-danger">*</span></label>
            <input type="text" class="form-control" name="token" required placeholder="Masukkan token atau URL QR Code">
            <div class="form-text">Diberikan panitia saat sesi berlangsung. Dapat berupa token atau URL lengkap.</div>
          </div>
          
          <div class="alert alert-info small mb-0">
            <div class="d-flex justify-content-between align-items-center">
              <div>
                <strong>Window absensi (WIB):</strong><br>
                <?= $startText ?> - <?= $endText ?><br>
                <small class="text-muted">Waktu sekarang: <?= $currentWIBString ?> WIB</small>
              </div>
              <span class="badge bg-purple">Presenter</span>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Batal</button>
          <button class="btn btn-primary" type="submit"><i class="bi bi-check2-circle"></i> Konfirmasi & Absen</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL QR SCANNER - Real-time Database Integration -->
<div class="modal fade" id="qrScannerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-qr-code-scan me-2"></i> Scan QR Code - Real-time Presenter</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" id="closeScannerBtn"></button>
      </div>
      <div class="modal-body text-center">
        <!-- Real-time Status Alert -->
        <div class="alert alert-success mb-3" id="realtime-status">
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-wifi fs-5"></i>
            <div class="text-start">
              <strong>Mode Real-time Aktif (WIB)</strong>
              <div class="small mt-1">
                QR Code yang valid akan langsung disimpan ke database dengan timestamp WIB tanpa konfirmasi tambahan.
                <br><strong>Waktu sekarang:</strong> <span id="current-wib-time"><?= $currentWIBString ?></span> WIB
              </div>
            </div>
          </div>
        </div>

        <!-- Role restriction notice -->
        <div class="alert alert-warning mb-3">
          <div class="d-flex align-items-center gap-2">
            <i class="bi bi-shield-exclamation fs-5"></i>
            <div class="text-start">
              <strong>Validasi Role Presenter</strong>
              <div class="small mt-1">
                Scanner akan memvalidasi QR Code. Hanya QR Code <span class="badge bg-purple">Presenter</span> atau <span class="badge bg-primary">Universal</span> yang dapat digunakan.
              </div>
            </div>
          </div>
        </div>

        <!-- Scanner Container -->
        <div id="scanner-container" class="mb-3 position-relative">
          <div id="reader" style="width: 100%; max-width: 500px; margin: 0 auto;"></div>
        </div>
        
        <!-- Status & Controls -->
        <div id="scanner-status" class="mb-3">
          <div class="d-flex justify-content-center gap-2 mb-2">
            <button id="startScanBtn" class="btn btn-success btn-sm">
              <i class="bi bi-play-circle"></i> Mulai Scan
            </button>
            <button id="stopScanBtn" class="btn btn-danger btn-sm" style="display:none;">
              <i class="bi bi-stop-circle"></i> Stop Scan
            </button>
            <button id="switchCameraBtn" class="btn btn-info btn-sm">
              <i class="bi bi-arrow-repeat"></i> Ganti Kamera
            </button>
          </div>
          <div id="scanner-message" class="text-muted small">
            Klik "Mulai Scan" untuk mengaktifkan kamera
          </div>
        </div>

        <!-- Scanner Results -->
        <div id="scan-result" class="alert alert-info" style="display:none;">
          <strong>QR Code Terdeteksi:</strong>
          <div id="scanned-content" class="mt-2 font-monospace small"></div>
          <div id="qr-role-info" class="mt-2"></div>
          <div id="processing-status" class="mt-2"></div>
        </div>

        <!-- Success Result -->
        <div id="success-result" class="alert alert-success" style="display:none;">
          <i class="bi bi-check-circle-fill fs-4 text-success"></i>
          <h5 class="mt-2">Absensi Berhasil!</h5>
          <div id="success-details" class="small"></div>
        </div>

        <!-- Camera Permission Notice -->
        <div id="camera-permission" class="alert alert-warning" style="display:none;">
          <i class="bi bi-camera-fill me-2"></i>
          <strong>Izin Kamera Diperlukan</strong><br>
          <small>Harap izinkan akses kamera untuk menggunakan QR scanner</small>
        </div>

        <!-- Error Notice -->
        <div id="scanner-error" class="alert alert-danger" style="display:none;">
          <i class="bi bi-exclamation-triangle me-2"></i>
          <strong id="error-message">Terjadi kesalahan</strong>
        </div>

        <!-- Debug Info -->
        <div id="debug-info" class="alert alert-light small" style="display:none;">
          <strong>Debug Info:</strong>
          <div id="debug-content"></div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
        <button type="button" class="btn btn-success" id="continueScanning" style="display:none;">
          <i class="bi bi-arrow-repeat"></i> Scan Lagi
        </button>
      </div>
    </div>
  </div>
</div>

<!-- Scripts -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?= $this->include('partials/footer') ?>

<script>
let html5QrCode = null;
let isScanning = false;
let isProcessing = false;
let scannedResult = null;
let cameras = [];
let currentCameraIndex = 0;
let scanSuccessful = false;

// WIB Time Helper - Convert browser time to WIB display
function getWIBTime() {
  const now = new Date();
  // Convert to WIB (UTC+7)
  const wibTime = new Date(now.getTime() + (7 * 60 * 60 * 1000) - (now.getTimezoneOffset() * 60 * 1000));
  return wibTime.toISOString().slice(0, 19).replace('T', ' ');
}

// Update current WIB time display every second
function updateWIBTimeDisplay() {
  const wibTimeElement = document.getElementById('current-wib-time');
  if (wibTimeElement) {
    wibTimeElement.textContent = getWIBTime();
  }
}

// Start WIB time updater
setInterval(updateWIBTimeDisplay, 1000);

// QR Code role detection for presenter validation
function detectQRRole(qrData) {
  const patterns = {
    presenter: /EVENT_\d+_presenter_/i,
    universal: /EVENT_\d+_all_/i,
    audience: /EVENT_\d+_audience_/i,
    reviewer: /EVENT_\d+_reviewer_/i,
    simple: /EVENT_\d+_\d{8}$/i,
    numeric: /^\d+$/,
    admin: /^(ADMIN|MANUAL|BULK)_\d+/i
  };

  if (patterns.presenter.test(qrData)) {
    return { type: 'presenter', allowed: true, badge: 'bg-purple', text: 'Presenter QR - Valid untuk Anda' };
  } else if (patterns.universal.test(qrData)) {
    return { type: 'universal', allowed: true, badge: 'bg-primary', text: 'Universal QR - Valid untuk semua role' };
  } else if (patterns.audience.test(qrData)) {
    return { type: 'audience', allowed: false, badge: 'bg-warning', text: 'Audience QR - Tidak dapat digunakan Presenter' };
  } else if (patterns.reviewer.test(qrData)) {
    return { type: 'reviewer', allowed: false, badge: 'bg-info', text: 'Reviewer QR - Tidak dapat digunakan Presenter' };
  } else if (patterns.simple.test(qrData) || patterns.numeric.test(qrData) || patterns.admin.test(qrData)) {
    return { type: 'universal', allowed: true, badge: 'bg-primary', text: 'Universal QR - Valid untuk semua role' };
  }

  return { type: 'unknown', allowed: false, badge: 'bg-secondary', text: 'Format QR tidak dikenali' };
}

// Initialize cameras
async function initializeCameras() {
  try {
    cameras = await Html5Qrcode.getCameras();
    const debugDiv = document.getElementById('debug-content');
    debugDiv.innerHTML = `Found ${cameras.length} camera(s) - WIB: ${getWIBTime()}`;
    document.getElementById('debug-info').style.display = 'block';
    
    console.log('Available cameras:', cameras);
    return cameras.length > 0;
  } catch (error) {
    console.error('Error getting cameras:', error);
    showError('Tidak dapat mengakses daftar kamera: ' + error.message);
    return false;
  }
}

// Initialize QR scanner
async function initQRScanner() {
  try {
    if (html5QrCode && html5QrCode.getState() === Html5QrcodeScannerState.SCANNING) {
      try {
        await html5QrCode.stop();
      } catch (e) {
        console.log('Previous scanner already stopped');
      }
    }

    html5QrCode = new Html5Qrcode("reader");
    
    const hasCameras = await initializeCameras();
    if (!hasCameras) {
      showError('Tidak ada kamera yang tersedia pada perangkat ini');
      return false;
    }
    
    return true;
  } catch (error) {
    console.error('Failed to initialize scanner:', error);
    showError('Gagal menginisialisasi scanner: ' + error.message);
    return false;
  }
}

// Start scanner
async function startScanner() {
  const startBtn = document.getElementById('startScanBtn');
  const stopBtn = document.getElementById('stopScanBtn');
  const messageDiv = document.getElementById('scanner-message');

  if (isScanning) {
    console.log('Scanner already running');
    return;
  }

  messageDiv.innerHTML = '<span class="text-info"><i class="bi bi-camera-fill"></i> Memulai kamera...</span>';

  try {
    const cameraConfig = cameras.length > 0 ? 
      cameras[currentCameraIndex].id : 
      { facingMode: "environment" };

    const config = {
      fps: 10,
      qrbox: { width: 250, height: 250 },
      aspectRatio: 1.0
    };

    await html5QrCode.start(
      cameraConfig,
      config,
      (decodedText, decodedResult) => {
        console.log('QR Code detected:', decodedText);
        onScanSuccess(decodedText);
      },
      (errorMessage) => {
        // Normal scanning errors when no QR detected
      }
    );

    isScanning = true;
    startBtn.style.display = 'none';
    stopBtn.style.display = 'inline-block';
    messageDiv.innerHTML = '<span class="text-success"><i class="bi bi-camera-video"></i> Scanner aktif - Arahkan ke QR Code Presenter</span>';

  } catch (error) {
    console.error('Failed to start scanner:', error);
    isScanning = false;
    
    let errorText = 'Gagal memulai scanner';
    
    if (error.toString().includes('Permission')) {
      document.getElementById('camera-permission').style.display = 'block';
      errorText = 'Akses kamera ditolak. Harap izinkan akses kamera di browser.';
    } else if (error.toString().includes('NotFound')) {
      errorText = 'Kamera tidak ditemukan. Pastikan perangkat memiliki kamera.';
    } else if (error.toString().includes('NotReadable')) {
      errorText = 'Kamera sedang digunakan aplikasi lain. Tutup aplikasi lain yang menggunakan kamera.';
    } else {
      errorText = 'Gagal memulai scanner: ' + error.message;
    }
    
    showError(errorText);
    startBtn.style.display = 'inline-block';
    stopBtn.style.display = 'none';
  }
}

// Stop scanner
async function stopScanner() {
  const startBtn = document.getElementById('startScanBtn');
  const stopBtn = document.getElementById('stopScanBtn');
  const messageDiv = document.getElementById('scanner-message');

  if (!isScanning) {
    return;
  }

  try {
    if (html5QrCode && html5QrCode.getState() === Html5QrcodeScannerState.SCANNING) {
      await html5QrCode.stop();
    }
    isScanning = false;
    
    startBtn.style.display = 'inline-block';
    stopBtn.style.display = 'none';
    messageDiv.innerHTML = '<span class="text-muted"><i class="bi bi-pause-circle"></i> Scanner dihentikan</span>';
  } catch (error) {
    console.error('Error stopping scanner:', error);
  }
}

// Switch camera
async function switchCamera() {
  if (cameras.length <= 1) {
    Swal.fire({
      icon: 'info',
      title: 'Tidak dapat mengganti kamera',
      text: 'Hanya ada satu kamera tersedia'
    });
    return;
  }

  const wasScanning = isScanning;
  
  if (wasScanning) {
    await stopScanner();
  }
  
  currentCameraIndex = (currentCameraIndex + 1) % cameras.length;
  
  const messageDiv = document.getElementById('scanner-message');
  const cameraName = cameras[currentCameraIndex].label || `Camera ${currentCameraIndex + 1}`;
  messageDiv.innerHTML = `<span class="text-info">Beralih ke: ${cameraName}</span>`;
  
  if (wasScanning) {
    setTimeout(() => {
      startScanner();
    }, 1000);
  }
}

// Handle successful scan - Real-time processing with WIB timestamp
async function onScanSuccess(decodedText) {
  if (isProcessing || scanSuccessful) {
    return; // Prevent multiple processing
  }

  scannedResult = decodedText;
  isProcessing = true;
  
  const scannedContentDiv = document.getElementById('scanned-content');
  const qrRoleInfoDiv = document.getElementById('qr-role-info');
  const resultDiv = document.getElementById('scan-result');
  const processingDiv = document.getElementById('processing-status');
  const messageDiv = document.getElementById('scanner-message');
  
  scannedContentDiv.textContent = decodedText;
  
  // Detect and validate QR role for presenter
  const roleInfo = detectQRRole(decodedText);
  qrRoleInfoDiv.innerHTML = `<span class="badge ${roleInfo.badge}">${roleInfo.text}</span>`;
  
  resultDiv.style.display = 'block';
  
  if (!roleInfo.allowed) {
    processingDiv.innerHTML = '<span class="badge bg-danger">QR Code Ditolak</span>';
    messageDiv.innerHTML = '<span class="text-danger"><i class="bi bi-exclamation-triangle"></i> QR Code tidak dapat digunakan oleh Presenter</span>';
    
    // Stop scanner after invalid QR
    setTimeout(async () => {
      await stopScanner();
      isProcessing = false;
    }, 2000);
    
    Swal.fire({
      icon: 'warning',
      title: 'QR Code Tidak Sesuai Role',
      html: `QR Code ini adalah <strong>${roleInfo.type.toUpperCase()}</strong> yang tidak dapat digunakan oleh Presenter.<br><br>Silakan gunakan QR Code <strong>Presenter</strong> atau <strong>Universal</strong>.`,
      confirmButtonText: 'Mengerti'
    });
    return;
  }

  // Valid QR - Process immediately with WIB timestamp
  const currentWIBTime = getWIBTime();
  processingDiv.innerHTML = `<span class="badge bg-info"><i class="bi bi-hourglass-split"></i> Memproses ke Database... (WIB: ${currentWIBTime})</span>`;
  messageDiv.innerHTML = '<span class="text-info"><i class="bi bi-cloud-upload"></i> Menyimpan absensi dengan timestamp WIB...</span>';

  // Stop scanner immediately
  await stopScanner();

  try {
    // Send AJAX request to save to database (backend will handle WIB)
    const response = await fetch('<?= site_url('presenter/absensi/scan') ?>', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: new URLSearchParams({
        'event_id': '<?= (int)($event['id'] ?? 0) ?>',
        'token': decodedText,
        '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
      })
    });

    const result = await response.json();

    if (result.success) {
      // Success - show success UI with WIB timestamp
      scanSuccessful = true;
      resultDiv.style.display = 'none';
      
      const successDiv = document.getElementById('success-result');
      const successDetails = document.getElementById('success-details');
      
      // Display WIB timestamp from server response
      const attendanceTimeWIB = result.data.attendance_datetime_wib || currentWIBTime;
      
      successDetails.innerHTML = `
        <div class="row text-start">
          <div class="col-6"><strong>Nama:</strong><br>${result.data.participant_name}</div>
          <div class="col-6"><strong>Role:</strong><br><span class="badge bg-purple">${result.data.participant_role}</span></div>
          <div class="col-6 mt-2"><strong>Event:</strong><br>${result.data.event_title}</div>
          <div class="col-6 mt-2"><strong>Waktu WIB:</strong><br>${attendanceTimeWIB}</div>
          <div class="col-12 mt-2"><small class="text-muted">Absensi disimpan dengan timezone WIB (+07:00)</small></div>
        </div>
      `;
      
      successDiv.style.display = 'block';
      document.getElementById('continueScanning').style.display = 'inline-block';
      
      messageDiv.innerHTML = '<span class="text-success"><i class="bi bi-check-circle"></i> Absensi berhasil disimpan dengan timestamp WIB!</span>';

      // Show success notification
      Swal.fire({
        icon: 'success',
        title: 'Absensi Berhasil!',
        html: `Selamat datang <strong>${result.data.participant_name}</strong>!<br>Anda telah tercatat hadir sebagai <strong>Presenter</strong>.<br><small>Waktu: ${attendanceTimeWIB} WIB</small>`,
        timer: 4000,
        showConfirmButton: false
      });

      // Update page status after delay
      setTimeout(() => {
        location.reload(); // Refresh to show updated attendance status
      }, 4000);

    } else {
      // Error - show error message
      processingDiv.innerHTML = '<span class="badge bg-danger">Gagal Menyimpan</span>';
      messageDiv.innerHTML = `<span class="text-danger"><i class="bi bi-exclamation-triangle"></i> ${result.message}</span>`;
      
      Swal.fire({
        icon: 'error',
        title: 'Gagal Menyimpan Absensi',
        text: result.message,
        confirmButtonText: 'Coba Lagi'
      });
    }

  } catch (error) {
    console.error('Error processing attendance:', error);
    processingDiv.innerHTML = '<span class="badge bg-danger">Error Koneksi</span>';
    messageDiv.innerHTML = '<span class="text-danger"><i class="bi bi-wifi-off"></i> Gagal terhubung ke server</span>';
    
    Swal.fire({
      icon: 'error',
      title: 'Error Koneksi',
      text: 'Gagal terhubung ke server. Periksa koneksi internet Anda.',
      confirmButtonText: 'Mengerti'
    });
  } finally {
    isProcessing = false;
  }
}

// Show error message
function showError(message) {
  const errorDiv = document.getElementById('scanner-error');
  const errorMessage = document.getElementById('error-message');
  const messageDiv = document.getElementById('scanner-message');
  
  errorMessage.textContent = message;
  errorDiv.style.display = 'block';
  messageDiv.innerHTML = `<span class="text-danger"><i class="bi bi-exclamation-triangle"></i> ${message}</span>`;
}

// Continue scanning after success
function continueScanning() {
  scanSuccessful = false;
  isProcessing = false;
  scannedResult = null;
  
  // Reset UI
  document.getElementById('scan-result').style.display = 'none';
  document.getElementById('success-result').style.display = 'none';
  document.getElementById('continueScanning').style.display = 'none';
  document.getElementById('scanner-message').innerHTML = 'Klik "Mulai Scan" untuk scan QR Code berikutnya';
  
  // Clear any errors
  document.getElementById('scanner-error').style.display = 'none';
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
  // Start WIB time display update
  updateWIBTimeDisplay();

  // Initialize when modal is shown
  document.getElementById('qrScannerModal').addEventListener('shown.bs.modal', async function() {
    await initQRScanner();
    scanSuccessful = false;
    isProcessing = false;
    updateWIBTimeDisplay(); // Update time when modal opens
  });

  // Clean up when modal is hidden
  document.getElementById('qrScannerModal').addEventListener('hidden.bs.modal', async function() {
    if (isScanning) {
      await stopScanner();
    }
    
    // Reset all UI elements
    document.getElementById('startScanBtn').style.display = 'inline-block';
    document.getElementById('stopScanBtn').style.display = 'none';
    document.getElementById('scan-result').style.display = 'none';
    document.getElementById('success-result').style.display = 'none';
    document.getElementById('continueScanning').style.display = 'none';
    document.getElementById('camera-permission').style.display = 'none';
    document.getElementById('scanner-error').style.display = 'none';
    document.getElementById('debug-info').style.display = 'none';
    document.getElementById('scanner-message').innerHTML = 'Klik "Mulai Scan" untuk mengaktifkan kamera';
    
    // Reset state
    scannedResult = null;
    scanSuccessful = false;
    isProcessing = false;
  });

  // Button event listeners
  document.getElementById('startScanBtn').addEventListener('click', startScanner);
  document.getElementById('stopScanBtn').addEventListener('click', stopScanner);
  document.getElementById('switchCameraBtn').addEventListener('click', switchCamera);
  document.getElementById('continueScanning').addEventListener('click', continueScanning);

  // Token form confirmation
  document.getElementById('tokenForm')?.addEventListener('submit', function(e){
    e.preventDefault();
    Swal.fire({
      title: 'Konfirmasi Absensi Presenter',
      html: `Kirim token absensi untuk dicatat sebagai Presenter?<br><small>Waktu: ${getWIBTime()} WIB</small>`,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Ya, Kirim',
      cancelButtonText: 'Batal'
    }).then(r => { 
      if (r.isConfirmed) this.submit(); 
    });
  });
});

// Flash messages
<?php if (session('success')): ?>
  Swal.fire({ icon:'success', title:'Berhasil', text:'<?= esc(session('success')) ?>', timer:3000, showConfirmButton:false });
<?php endif; ?>
<?php if (session('error')): ?>
  Swal.fire({ icon:'error', title:'Gagal', text:'<?= esc(session('error')) ?>' });
<?php endif; ?>
<?php if (session('info')): ?>
  Swal.fire({ icon:'info', title:'Info', text:'<?= esc(session('info')) ?>' });
<?php endif; ?>
</script>

<style>
  :root{
    --primary-color:#2563eb; 
    --info-color:#06b6d4; 
    --success-color:#10b981; 
    --secondary:#64748b;
    --purple-color:#8b5cf6;
  }
  body{ background:#f8fafc; font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }

  .header-section.header-blue{
    background: linear-gradient(135deg, var(--primary-color) 0%, #1e40af 100%);
    color:#fff; padding:22px; border-radius:14px; box-shadow:0 8px 28px rgba(0,0,0,.12);
  }
  .welcome-text{ font-size:1.35rem; font-weight:500; }

  .status-dot{ width:12px; height:12px; border-radius:50%; }
  .status-dot.bg-success{ background:#16a34a; }
  .status-dot.bg-primary{ background:#2563eb; }
  .status-dot.bg-secondary{ background:#94a3b8; }

  .card { border-radius:14px; }
  .btn { border-radius:10px; }
  .badge{ border-radius:8px; }
  .bg-purple{ background-color: var(--purple-color) !important; }

  /* QR Scanner Styles */
  #reader {
    border: 2px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
  }
  
  #scanner-container {
    background: #f8f9fa;
    border-radius: 8px;
    padding: 20px;
  }

  .font-monospace {
    font-family: 'Courier New', monospace;
    word-break: break-all;
  }

  /* Success animation */
  #success-result {
    animation: slideIn 0.5s ease-out;
  }

  @keyframes slideIn {
    from { opacity: 0; transform: translateY(-20px); }
    to { opacity: 1; transform: translateY(0); }
  }

  /* Hide HTML5-QRCode default styling */
  #reader__dashboard_section {
    display: none !important;
  }
  
  #reader__header_message {
    display: none !important;
  }

  /* WIB Time highlight */
  #current-wib-time {
    font-weight: 600;
    color: #059669;
  }
</style>