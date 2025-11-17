<?php
/** Detail Absensi Event - Enhanced for Audience with Fixed Event Info Display */
$title = 'Detail Absensi Event';

$e = $event ?? [];
$payment = $payment ?? [];
$window = $window ?? ['is_open'=>false,'start_ts'=>null,'end_ts'=>null,'reason'=>'','current_time_wib'=>''];

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

$tgl = !empty($e['event_date']) ? date('d M Y', strtotime($e['event_date'])) : '-';
$jam = $e['event_time'] ?? '-';

$can         = (bool)($e['can_scan'] ?? false);
$badgeClass  = $e['badge_class']  ?? 'bg-secondary';
$eventStatus = $e['event_status'] ?? '-';
$participationType = $e['participation_type'] ?? 'all';

$already      = (bool)($already_attend ?? false);
$attendanceAt = $attendance_at ?? null;

// Jika sudah absen, paksa nonaktifkan tombol
if ($already) { $can = false; }

// Convert BOTH to HYBRID for display
$eventFormat = strtoupper($e['format'] ?? 'HYBRID');
if ($eventFormat === 'BOTH') {
    $eventFormat = 'HYBRID';
}

// Determine participation type display
$participationDisplay = ucfirst($participationType);
$participationBadgeClass = 'badge-primary';
if ($participationType === 'online') {
    $participationBadgeClass = 'badge-info';
} elseif ($participationType === 'offline') {
    $participationBadgeClass = 'badge-success';
}

// Format event date time untuk display
$eventDateTime = null;
if ($e['event_date']) {
    $eventDateTimeString = $e['event_date'] . ' ' . ($e['event_time'] ?? '00:00:00');
    $eventDateTime = new DateTime($eventDateTimeString, new DateTimeZone('Asia/Jakarta'));
}
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_audience') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <!-- HEADER -->
      <div class="page-header-abs mb-4">
        <div class="header-content-abs">
          <div class="header-icon-abs">
            <i class="bi bi-qr-code"></i>
          </div>
          <div class="header-text-abs">
            <h2 class="header-title-abs">Detail Absensi Event</h2>
            <p class="header-subtitle-abs"><?= esc($e['title'] ?? '-') ?></p>
          </div>
        </div>
        <div class="header-time-abs">
          <div class="time-label-abs">Waktu Sekarang (WIB)</div>
          <div class="time-value-abs" id="current-time-display"><?= $currentWIBString ?></div>
        </div>
      </div>

      <!-- STATUS CARD -->
      <div class="status-card-abs mb-4">
        <div class="status-card-content">
          <div class="status-info-abs">
            <div class="status-indicator-abs <?= $already ? 'status-success' : ($window['is_open'] ? 'status-active' : 'status-closed') ?>">
              <div class="status-dot-abs"></div>
              <div class="status-text-abs">
                <?php if ($already): ?>
                  <div class="status-title-abs">Sudah Absen</div>
                  <div class="status-desc-abs">Anda telah tercatat hadir sebagai Audience <?= $participationDisplay ?></div>
                <?php elseif ($window['is_open']): ?>
                  <div class="status-title-abs">Absensi Dibuka</div>
                  <div class="status-desc-abs">Silahkan lakukan absensi sekarang</div>
                <?php else: ?>
                  <div class="status-title-abs">Absensi Ditutup</div>
                  <div class="status-desc-abs"><?= $window['reason'] ? esc($window['reason']) : 'Belum waktunya absensi' ?></div>
                <?php endif; ?>
              </div>
            </div>
            
            <div class="event-meta-abs">
              <div class="meta-item-abs">
                <i class="bi bi-geo-alt"></i>
                <span><?= $participationType === 'online' ? 'Online Event' : esc($e['location'] ?? '-') ?></span>
              </div>
              <div class="meta-item-abs">
                <i class="bi bi-diagram-3"></i>
                <span><?= $eventFormat ?></span>
              </div>
              <div class="meta-item-abs">
                <span class="badge-abs badge-warning">Audience</span>
                <span class="badge-abs <?= $participationBadgeClass ?>"><?= $participationDisplay ?></span>
              </div>
            </div>
          </div>

          <div class="action-buttons-abs">
            <?php if (!$already && $window['is_open']): ?>
              <button class="btn-abs btn-primary-abs" data-bs-toggle="modal" data-bs-target="#tokenModal">
                <i class="bi bi-input-cursor-text"></i>
                <span>Input Token</span>
              </button>
              <button class="btn-abs btn-success-abs" data-bs-toggle="modal" data-bs-target="#qrScannerModal">
                <i class="bi bi-qr-code-scan"></i>
                <span>Scan QR</span>
              </button>
            <?php else: ?>
              <button class="btn-abs btn-disabled-abs" disabled>
                <i class="bi bi-input-cursor-text"></i>
                <span>Input Token</span>
              </button>
              <button class="btn-abs btn-disabled-abs" disabled>
                <i class="bi bi-qr-code-scan"></i>
                <span>Scan QR</span>
              </button>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- WINDOW INFO -->
      <div class="window-card-abs mb-4">
        <div class="window-header-abs">
          <i class="bi bi-clock-history"></i>
          <span>Waktu Absensi (WIB)</span>
        </div>
        <div class="window-body-abs">
          <div class="window-time-abs">
            <div class="time-block-abs">
              <div class="time-label-small-abs">Mulai</div>
              <div class="time-value-large-abs"><?= $startText ?></div>
            </div>
            <div class="time-separator-abs">
              <i class="bi bi-arrow-right"></i>
            </div>
            <div class="time-block-abs">
              <div class="time-label-small-abs">Selesai</div>
              <div class="time-value-large-abs"><?= $endText ?></div>
            </div>
          </div>
        </div>
      </div>

      <!-- INFO CARDS -->
      <div class="row g-3 mb-4">
        <!-- EVENT INFO -->
        <div class="col-12 col-lg-8">
          <div class="info-card-abs">
            <div class="info-card-header-abs">
              <i class="bi bi-calendar-event"></i>
              <span>Informasi Event</span>
            </div>
            <div class="info-card-body-abs">
              <h5 class="event-title-abs"><?= esc($e['title'] ?? '-') ?></h5>
              <?php if (!empty($e['description'])): ?>
              <p class="event-desc-abs"><?= nl2br(esc($e['description'])) ?></p>
              <?php endif; ?>
              
              <div class="event-details-grid">
                <!-- TANGGAL - Selalu ditampilkan -->
                <div class="detail-item-abs">
                  <div class="detail-icon-abs">
                    <i class="bi bi-calendar3"></i>
                  </div>
                  <div class="detail-content-abs">
                    <div class="detail-label-abs">Tanggal</div>
                    <div class="detail-value-abs">
                      <?= $eventDateTime ? $eventDateTime->format('d M Y') : '-' ?>
                    </div>
                  </div>
                </div>

                <!-- WAKTU - Selalu ditampilkan -->
                <div class="detail-item-abs">
                  <div class="detail-icon-abs">
                    <i class="bi bi-clock"></i>
                  </div>
                  <div class="detail-content-abs">
                    <div class="detail-label-abs">Waktu</div>
                    <div class="detail-value-abs">
                      <?= $eventDateTime ? $eventDateTime->format('H:i') . ' WIB' : '-' ?>
                    </div>
                  </div>
                </div>

                <!-- LOKASI - Hanya untuk OFFLINE atau ALL -->
                <?php if ($participationType === 'offline' || $participationType === 'all'): ?>
                <div class="detail-item-abs">
                  <div class="detail-icon-abs">
                    <i class="bi bi-geo-alt-fill"></i>
                  </div>
                  <div class="detail-content-abs">
                    <div class="detail-label-abs">Lokasi</div>
                    <div class="detail-value-abs"><?= esc($e['location'] ?? '-') ?></div>
                  </div>
                </div>
                <?php endif; ?>

                <!-- LINK MEETING - Hanya untuk ONLINE atau ALL (jika ada zoom_link) -->
                <?php if (($participationType === 'online' || $participationType === 'all') && !empty($e['zoom_link'])): ?>
                <div class="detail-item-abs">
                  <div class="detail-icon-abs">
                    <i class="bi bi-camera-video"></i>
                  </div>
                  <div class="detail-content-abs">
                    <div class="detail-label-abs">Link Meeting</div>
                    <div class="detail-value-abs">
                      <a href="<?= esc($e['zoom_link']) ?>" target="_blank" class="link-abs">
                        <i class="bi bi-box-arrow-up-right me-1"></i>Buka Tautan
                      </a>
                    </div>
                  </div>
                </div>
                <?php endif; ?>

                <!-- FORMAT EVENT - Optional info -->
                <?php if (!empty($e['format'])): ?>
                <div class="detail-item-abs">
                  <div class="detail-icon-abs">
                    <i class="bi bi-diagram-3"></i>
                  </div>
                  <div class="detail-content-abs">
                    <div class="detail-label-abs">Format</div>
                    <div class="detail-value-abs"><?= $eventFormat ?></div>
                  </div>
                </div>
                <?php endif; ?>
              </div>

              <!-- PARTICIPATION TYPE INFO -->
              <div class="participation-info-abs mt-3">
                <div class="participation-banner-abs participation-<?= $participationType ?>">
                  <i class="bi bi-info-circle"></i>
                  <div>
                    <?php if ($participationType === 'online'): ?>
                      <strong>Partisipasi Online:</strong> Anda terdaftar untuk mengikuti event secara online. Gunakan link meeting di atas untuk bergabung.
                    <?php elseif ($participationType === 'offline'): ?>
                      <strong>Partisipasi Offline:</strong> Anda terdaftar untuk hadir secara fisik di lokasi. Pastikan datang tepat waktu.
                    <?php else: ?>
                      <strong>Partisipasi Hybrid:</strong> Anda dapat memilih untuk hadir secara online atau offline sesuai preferensi Anda.
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- PAYMENT INFO -->
        <div class="col-12 col-lg-4">
          <div class="info-card-abs">
            <div class="info-card-header-abs">
              <i class="bi bi-receipt"></i>
              <span>Status Pembayaran</span>
            </div>
            <div class="info-card-body-abs">
              <div class="payment-status-abs">
                <span class="badge-abs badge-success">
                  <i class="bi bi-check-circle-fill"></i>
                  Verified
                </span>
                <p class="payment-note-abs">Anda berhak melakukan absensi</p>
              </div>

              <div class="payment-details-abs">
                <div class="payment-row-abs">
                  <span class="payment-label-abs">Metode</span>
                  <span class="payment-value-abs"><?= esc($payment['metode'] ?? '-') ?></span>
                </div>
                <div class="payment-row-abs">
                  <span class="payment-label-abs">Jumlah</span>
                  <span class="payment-value-abs">Rp <?= number_format((int)($payment['jumlah'] ?? 0), 0, ',', '.') ?></span>
                </div>
                <div class="payment-row-abs">
                  <span class="payment-label-abs">Tipe</span>
                  <span class="payment-value-abs">
                    <span class="badge-abs <?= $participationBadgeClass ?>"><?= $participationDisplay ?></span>
                  </span>
                </div>
              </div>
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
    <div class="modal-content modal-abs">
      <form action="<?= site_url('audience/absensi/scan') ?>" method="POST" id="tokenForm">
        <?= csrf_field() ?>
        <div class="modal-header modal-header-abs">
          <h5 class="modal-title-abs">
            <i class="bi bi-input-cursor-text me-2"></i>
            Masukkan Token Absensi
          </h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body modal-body-abs">
          <input type="hidden" name="event_id" value="<?= (int)($e['id'] ?? 0) ?>">
          
          <div class="alert-abs alert-warning-abs mb-3">
            <i class="bi bi-shield-exclamation"></i>
            <div>
              <strong>Khusus Audience <?= $participationDisplay ?>:</strong>
              <p class="mb-0 small">
                <?php if ($participationType === 'online'): ?>
                  Pastikan QR Code adalah <strong>Audience Online</strong> atau <strong>Universal</strong>
                <?php elseif ($participationType === 'offline'): ?>
                  Pastikan QR Code adalah <strong>Audience Offline</strong> atau <strong>Universal</strong>
                <?php else: ?>
                  Pastikan QR Code adalah <strong>Audience</strong> atau <strong>Universal</strong>
                <?php endif; ?>
              </p>
            </div>
          </div>

          <div class="form-group-abs">
            <label class="form-label-abs">Token Kehadiran <span class="text-danger">*</span></label>
            <input type="text" class="form-control-abs" name="token" required placeholder="Masukkan token atau URL QR Code">
            <div class="form-hint-abs">Token diberikan panitia saat event berlangsung</div>
          </div>
          
          <div class="info-box-abs">
            <div class="info-box-content-abs">
              <div class="info-box-text-abs">
                <strong>Window Absensi (WIB):</strong><br>
                <?= $startText ?> - <?= $endText ?><br>
                <small>Sekarang: <?= $currentWIBString ?> WIB</small>
              </div>
              <div class="info-box-badges-abs">
                <span class="badge-abs badge-warning">Audience</span>
                <span class="badge-abs <?= $participationBadgeClass ?>"><?= $participationDisplay ?></span>
              </div>
            </div>
          </div>
        </div>
        <div class="modal-footer modal-footer-abs">
          <button class="btn-abs btn-secondary-abs" type="button" data-bs-dismiss="modal">Batal</button>
          <button class="btn-abs btn-primary-abs" type="submit">
            <i class="bi bi-check2-circle"></i>
            <span>Konfirmasi Absensi</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- MODAL QR SCANNER -->
<div class="modal fade" id="qrScannerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content modal-abs">
      <div class="modal-header modal-header-abs">
        <h5 class="modal-title-abs">
          <i class="bi bi-qr-code-scan me-2"></i>
          Scan QR Code Absensi
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" id="closeScannerBtn"></button>
      </div>
      <div class="modal-body modal-body-abs">
        
        <!-- Real-time Status -->
        <div class="scanner-alert-abs scanner-success-abs mb-3">
          <i class="bi bi-wifi"></i>
          <div>
            <strong>Mode Real-time Aktif</strong>
            <p class="mb-0 small">QR Code valid akan langsung disimpan</p>
            <div class="small mt-1"><strong>Waktu:</strong> <span id="scanner-wib-time"><?= $currentWIBString ?></span> WIB</div>
          </div>
        </div>

        <!-- Participation Warning -->
        <div class="scanner-alert-abs scanner-warning-abs mb-3">
          <i class="bi bi-shield-exclamation"></i>
          <div>
            <strong>Validasi Tipe Partisipasi</strong>
            <p class="mb-0 small">
              Terdaftar: <span class="badge-abs <?= $participationBadgeClass ?>"><?= $participationDisplay ?></span>
              <?php if ($participationType === 'online'): ?>
                · Hanya QR <span class="badge-abs badge-info">Online</span> atau <span class="badge-abs badge-primary">Universal</span>
              <?php elseif ($participationType === 'offline'): ?>
                · Hanya QR <span class="badge-abs badge-success">Offline</span> atau <span class="badge-abs badge-primary">Universal</span>
              <?php else: ?>
                · Semua QR Audience dapat digunakan
              <?php endif; ?>
            </p>
          </div>
        </div>

        <!-- Scanner Container -->
        <div class="scanner-container-abs">
          <div id="reader"></div>
        </div>
        
        <!-- Scanner Controls -->
        <div class="scanner-controls-abs">
          <button id="startScanBtn" class="btn-abs btn-success-abs">
            <i class="bi bi-play-circle"></i>
            <span>Mulai Scan</span>
          </button>
          <button id="stopScanBtn" class="btn-abs btn-danger-abs" style="display:none;">
            <i class="bi bi-stop-circle"></i>
            <span>Stop Scan</span>
          </button>
          <button id="switchCameraBtn" class="btn-abs btn-info-abs">
            <i class="bi bi-arrow-repeat"></i>
            <span>Ganti Kamera</span>
          </button>
        </div>

        <div id="scanner-message" class="scanner-message-abs">
          Klik "Mulai Scan" untuk mengaktifkan kamera
        </div>

        <!-- Scan Result -->
        <div id="scan-result" class="scan-result-abs" style="display:none;">
          <div class="scan-result-header-abs">QR Code Terdeteksi</div>
          <div id="scanned-content" class="scan-result-content-abs"></div>
          <div id="qr-role-info" class="scan-result-role-abs"></div>
          <div id="processing-status" class="scan-result-status-abs"></div>
        </div>

        <!-- Success Result -->
        <div id="success-result" class="success-result-abs" style="display:none;">
          <div class="success-icon-abs">
            <i class="bi bi-check-circle-fill"></i>
          </div>
          <h5 class="success-title-abs">Absensi Berhasil!</h5>
          <div id="success-details" class="success-details-abs"></div>
        </div>

        <!-- Alerts -->
        <div id="camera-permission" class="scanner-alert-abs scanner-warning-abs" style="display:none;">
          <i class="bi bi-camera-fill"></i>
          <div>
            <strong>Izin Kamera Diperlukan</strong>
            <p class="mb-0 small">Harap izinkan akses kamera untuk menggunakan scanner</p>
          </div>
        </div>

        <div id="scanner-error" class="scanner-alert-abs scanner-danger-abs" style="display:none;">
          <i class="bi bi-exclamation-triangle"></i>
          <div>
            <strong id="error-message">Terjadi kesalahan</strong>
          </div>
        </div>

      </div>
      <div class="modal-footer modal-footer-abs">
        <button type="button" class="btn-abs btn-secondary-abs" data-bs-dismiss="modal">Tutup</button>
        <button type="button" class="btn-abs btn-success-abs" id="continueScanning" style="display:none;">
          <i class="bi bi-arrow-repeat"></i>
          <span>Scan Lagi</span>
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

const userParticipationType = '<?= $participationType ?>';

// WIB Time Helper - Convert local time to WIB (UTC+7)
function getWIBTime() {
  const now = new Date();
  
  // Convert to WIB timezone (UTC+7)
  const wibOffset = 7 * 60; // WIB is UTC+7 in minutes
  const localOffset = now.getTimezoneOffset(); // Local timezone offset in minutes (negative for east of UTC)
  const totalOffset = wibOffset + localOffset; // Total difference in minutes
  
  const wibTime = new Date(now.getTime() + (totalOffset * 60 * 1000));
  
  // Format as YYYY-MM-DD HH:MM:SS
  const year = wibTime.getFullYear();
  const month = String(wibTime.getMonth() + 1).padStart(2, '0');
  const day = String(wibTime.getDate()).padStart(2, '0');
  const hours = String(wibTime.getHours()).padStart(2, '0');
  const minutes = String(wibTime.getMinutes()).padStart(2, '0');
  const seconds = String(wibTime.getSeconds()).padStart(2, '0');
  
  return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
}

// Update WIB time display
function updateWIBTimeDisplay() {
  const elements = ['current-time-display', 'scanner-wib-time'];
  elements.forEach(id => {
    const el = document.getElementById(id);
    if (el) el.textContent = getWIBTime();
  });
}

setInterval(updateWIBTimeDisplay, 1000);

// QR Code role detection
function detectQRRole(qrData) {
  const patterns = {
    audienceOnline: /EVENT_\d+_audience_online_/i,
    audienceOffline: /EVENT_\d+_audience_offline_/i,
    universal: /EVENT_\d+_all_/i,
    presenter: /EVENT_\d+_presenter_/i,
    reviewer: /EVENT_\d+_reviewer_/i,
    simple: /EVENT_\d+_\d{8}$/i,
    numeric: /^\d+$/,
    admin: /^(ADMIN|MANUAL|BULK)_\d+/i
  };

  let qrType = 'unknown';
  let qrParticipationType = 'all';
  
  if (patterns.audienceOnline.test(qrData)) {
    qrType = 'audience';
    qrParticipationType = 'online';
  } else if (patterns.audienceOffline.test(qrData)) {
    qrType = 'audience';
    qrParticipationType = 'offline';
  } else if (patterns.universal.test(qrData)) {
    qrType = 'universal';
    qrParticipationType = 'all';
  } else if (patterns.presenter.test(qrData)) {
    qrType = 'presenter';
    qrParticipationType = 'offline';
  } else if (patterns.reviewer.test(qrData)) {
    qrType = 'reviewer';
    qrParticipationType = 'all';
  } else if (patterns.simple.test(qrData) || patterns.numeric.test(qrData) || patterns.admin.test(qrData)) {
    qrType = 'universal';
    qrParticipationType = 'all';
  }

  let allowed = false;
  let message = '';
  let badge = 'badge-abs';

  if (qrType === 'universal') {
    allowed = true;
    badge += ' badge-primary';
    message = 'Universal QR - Valid';
  } else if (qrType === 'audience') {
    if (userParticipationType === 'all' || qrParticipationType === userParticipationType) {
      allowed = true;
      badge += qrParticipationType === 'online' ? ' badge-info' : ' badge-success';
      message = `Audience ${qrParticipationType.charAt(0).toUpperCase() + qrParticipationType.slice(1)} - Valid`;
    } else {
      allowed = false;
      badge += ' badge-warning';
      message = `Tidak cocok dengan tipe Anda`;
    }
  } else {
    allowed = false;
    badge += ' badge-warning';
    message = qrType === 'presenter' ? 'QR Presenter' : qrType === 'reviewer' ? 'QR Reviewer' : 'Format tidak dikenali';
  }

  return { type: qrType, participation_type: qrParticipationType, allowed, badge, text: message };
}

// Initialize cameras
async function initializeCameras() {
  try {
    cameras = await Html5Qrcode.getCameras();
    return cameras.length > 0;
  } catch (error) {
    console.error('Error getting cameras:', error);
    showError('Tidak dapat mengakses kamera: ' + error.message);
    return false;
  }
}

// Initialize QR scanner
async function initQRScanner() {
  try {
    if (html5QrCode && html5QrCode.getState() === Html5QrcodeScannerState.SCANNING) {
      await html5QrCode.stop();
    }
    html5QrCode = new Html5Qrcode("reader");
    const hasCameras = await initializeCameras();
    if (!hasCameras) {
      showError('Tidak ada kamera tersedia');
      return false;
    }
    return true;
  } catch (error) {
    console.error('Failed to initialize scanner:', error);
    showError('Gagal menginisialisasi scanner');
    return false;
  }
}

// Start scanner
async function startScanner() {
  if (isScanning) return;

  const messageDiv = document.getElementById('scanner-message');
  messageDiv.innerHTML = '<span style="color: var(--info);">Memulai kamera...</span>';

  try {
    const cameraConfig = cameras.length > 0 ? cameras[currentCameraIndex].id : { facingMode: "environment" };
    const config = { fps: 10, qrbox: { width: 250, height: 250 }, aspectRatio: 1.0 };

    await html5QrCode.start(cameraConfig, config, (decodedText) => {
      onScanSuccess(decodedText);
    }, () => {});

    isScanning = true;
    document.getElementById('startScanBtn').style.display = 'none';
    document.getElementById('stopScanBtn').style.display = 'inline-flex';
    messageDiv.innerHTML = '<span style="color: var(--success);">Scanner aktif - Arahkan ke QR Code</span>';
  } catch (error) {
    console.error('Failed to start scanner:', error);
    isScanning = false;
    
    let errorText = 'Gagal memulai scanner';
    if (error.toString().includes('Permission')) {
      document.getElementById('camera-permission').style.display = 'flex';
      errorText = 'Akses kamera ditolak';
    }
    showError(errorText);
    document.getElementById('startScanBtn').style.display = 'inline-flex';
    document.getElementById('stopScanBtn').style.display = 'none';
  }
}

// Stop scanner
async function stopScanner() {
  if (!isScanning) return;
  try {
    if (html5QrCode && html5QrCode.getState() === Html5QrcodeScannerState.SCANNING) {
      await html5QrCode.stop();
    }
    isScanning = false;
    document.getElementById('startScanBtn').style.display = 'inline-flex';
    document.getElementById('stopScanBtn').style.display = 'none';
    document.getElementById('scanner-message').innerHTML = 'Scanner dihentikan';
  } catch (error) {
    console.error('Error stopping scanner:', error);
  }
}

// Switch camera
async function switchCamera() {
  if (cameras.length <= 1) {
    Swal.fire({ icon: 'info', title: 'Hanya ada satu kamera tersedia' });
    return;
  }
  const wasScanning = isScanning;
  if (wasScanning) await stopScanner();
  currentCameraIndex = (currentCameraIndex + 1) % cameras.length;
  if (wasScanning) setTimeout(() => startScanner(), 1000);
}

// Handle scan success
async function onScanSuccess(decodedText) {
  if (isProcessing || scanSuccessful) return;

  scannedResult = decodedText;
  isProcessing = true;
  
  document.getElementById('scanned-content').textContent = decodedText;
  const roleInfo = detectQRRole(decodedText);
  document.getElementById('qr-role-info').innerHTML = `<span class="${roleInfo.badge}">${roleInfo.text}</span>`;
  document.getElementById('scan-result').style.display = 'block';
  
  if (!roleInfo.allowed) {
    document.getElementById('processing-status').innerHTML = '<span class="badge-abs" style="background: var(--danger); color: white;">QR Code Ditolak</span>';
    setTimeout(async () => {
      await stopScanner();
      isProcessing = false;
    }, 2000);
    
    Swal.fire({
      icon: 'warning',
      title: 'QR Code Tidak Sesuai',
      text: roleInfo.text,
      confirmButtonText: 'Mengerti'
    });
    return;
  }

  const currentWIBTime = getWIBTime();
  document.getElementById('processing-status').innerHTML = `<span class="badge-abs badge-info">Memproses... (${currentWIBTime})</span>`;
  await stopScanner();

  try {
    const response = await fetch('<?= site_url('audience/absensi/scan') ?>', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/x-www-form-urlencoded',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: new URLSearchParams({
        'event_id': '<?= (int)($e['id'] ?? 0) ?>',
        'token': decodedText,
        '<?= csrf_token() ?>': '<?= csrf_hash() ?>'
      })
    });

    const result = await response.json();

    if (result.success) {
      scanSuccessful = true;
      document.getElementById('scan-result').style.display = 'none';
      
      const attendanceTimeWIB = result.data.attendance_datetime_wib || currentWIBTime;
      const userParticipationDisplay = result.data.user_participation_type ? 
        result.data.user_participation_type.charAt(0).toUpperCase() + result.data.user_participation_type.slice(1) : 'Unknown';
      
      document.getElementById('success-details').innerHTML = `
        <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 0.75rem;">
          <div><strong>Nama:</strong><br>${result.data.participant_name}</div>
          <div><strong>Role:</strong><br><span class="badge-abs badge-warning">Audience</span></div>
          <div><strong>Event:</strong><br>${result.data.event_title}</div>
          <div><strong>Tipe:</strong><br><span class="badge-abs ${userParticipationType === 'online' ? 'badge-info' : 'badge-success'}">${userParticipationDisplay}</span></div>
          <div style="grid-column: 1 / -1;"><strong>Waktu WIB:</strong><br>${attendanceTimeWIB}</div>
        </div>
      `;
      
      document.getElementById('success-result').style.display = 'block';
      document.getElementById('continueScanning').style.display = 'inline-flex';

      Swal.fire({
        icon: 'success',
        title: 'Absensi Berhasil!',
        html: `Selamat datang <strong>${result.data.participant_name}</strong>!<br>Waktu: ${attendanceTimeWIB} WIB`,
        timer: 3000,
        showConfirmButton: false
      });

      setTimeout(() => location.reload(), 3000);
    } else {
      document.getElementById('processing-status').innerHTML = '<span class="badge-abs" style="background: var(--danger); color: white;">Gagal</span>';
      Swal.fire({ icon: 'error', title: 'Gagal', text: result.message });
    }
  } catch (error) {
    console.error('Error:', error);
    Swal.fire({ icon: 'error', title: 'Error Koneksi', text: 'Gagal terhubung ke server' });
  } finally {
    isProcessing = false;
  }
}

// Show error
function showError(message) {
  document.getElementById('error-message').textContent = message;
  document.getElementById('scanner-error').style.display = 'flex';
}

// Continue scanning
function continueScanning() {
  scanSuccessful = false;
  isProcessing = false;
  scannedResult = null;
  document.getElementById('scan-result').style.display = 'none';
  document.getElementById('success-result').style.display = 'none';
  document.getElementById('continueScanning').style.display = 'none';
  document.getElementById('scanner-error').style.display = 'none';
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
  updateWIBTimeDisplay();

  document.getElementById('qrScannerModal').addEventListener('shown.bs.modal', async function() {
    await initQRScanner();
    scanSuccessful = false;
    isProcessing = false;
    updateWIBTimeDisplay();
  });

  document.getElementById('qrScannerModal').addEventListener('hidden.bs.modal', async function() {
    if (isScanning) await stopScanner();
    document.getElementById('startScanBtn').style.display = 'inline-flex';
    document.getElementById('stopScanBtn').style.display = 'none';
    document.getElementById('scan-result').style.display = 'none';
    document.getElementById('success-result').style.display = 'none';
    document.getElementById('continueScanning').style.display = 'none';
    document.getElementById('camera-permission').style.display = 'none';
    document.getElementById('scanner-error').style.display = 'none';
    scannedResult = null;
    scanSuccessful = false;
    isProcessing = false;
  });

  document.getElementById('startScanBtn').addEventListener('click', startScanner);
  document.getElementById('stopScanBtn').addEventListener('click', stopScanner);
  document.getElementById('switchCameraBtn').addEventListener('click', switchCamera);
  document.getElementById('continueScanning').addEventListener('click', continueScanning);

  document.getElementById('tokenForm')?.addEventListener('submit', function(e){
    e.preventDefault();
    const participationText = userParticipationType === 'all' ? 'Audience' : `Audience ${userParticipationType.charAt(0).toUpperCase() + userParticipationType.slice(1)}`;
    Swal.fire({
      title: `Konfirmasi Absensi ${participationText}`,
      html: `Kirim token absensi?<br><small>Waktu: ${getWIBTime()} WIB</small>`,
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
  :root {
    --primary-blue: #2563eb;
    --light-blue: #eff6ff;
    --medium-blue: #dbeafe;
    --dark-blue: #1e40af;
    --success: #10b981;
    --warning: #f59e0b;
    --danger: #ef4444;
    --info: #06b6d4;
    --white: #ffffff;
    --gray-50: #f9fafb;
    --gray-100: #f3f4f6;
    --gray-200: #e5e7eb;
    --gray-400: #9ca3af;
    --gray-600: #4b5563;
    --gray-700: #374151;
    --gray-900: #111827;
  }

  body { 
    background: var(--light-blue);
    font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
  }

  /* PAGE HEADER */
  .page-header-abs {
    background: var(--white);
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1rem;
  }
  .header-content-abs {
    display: flex;
    align-items: center;
    gap: 1rem;
    flex: 1;
  }
  .header-icon-abs {
    width: 48px;
    height: 48px;
    background: var(--primary-blue);
    border-radius: 10px;
    display: grid;
    place-items: center;
    font-size: 1.5rem;
    color: var(--white);
    flex-shrink: 0;
  }
  .header-text-abs {
    flex: 1;
  }
  .header-title-abs {
    font-size: 1.5rem;
    font-weight: 700;
    color: var(--gray-900);
    margin: 0 0 0.25rem 0;
  }
  .header-subtitle-abs {
    color: var(--gray-600);
    margin: 0;
    font-size: 0.875rem;
  }
  .header-time-abs {
    text-align: right;
  }
  .time-label-abs {
    font-size: 0.75rem;
    color: var(--gray-600);
    margin-bottom: 0.25rem;
  }
  .time-value-abs {
    font-size: 0.875rem;
    font-weight: 600;
    color: var(--primary-blue);
  }

  /* STATUS CARD */
  .status-card-abs {
    background: var(--white);
    border-radius: 12px;
    padding: 1.5rem;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  }
  .status-card-content {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 1.5rem;
  }
  .status-info-abs {
    flex: 1;
    min-width: 250px;
  }
  .status-indicator-abs {
    display: flex;
    align-items: center;
    gap: 1rem;
    margin-bottom: 1rem;
  }
  .status-dot-abs {
    width: 12px;
    height: 12px;
    border-radius: 50%;
    flex-shrink: 0;
  }
  .status-indicator-abs.status-success .status-dot-abs {
    background: var(--success);
  }
  .status-indicator-abs.status-active .status-dot-abs {
    background: var(--primary-blue);
  }
  .status-indicator-abs.status-closed .status-dot-abs {
    background: var(--gray-400);
  }
  .status-title-abs {
    font-weight: 600;
    color: var(--gray-900);
    margin-bottom: 0.25rem;
  }
  .status-desc-abs {
    font-size: 0.875rem;
    color: var(--gray-600);
  }
  .event-meta-abs {
    display: flex;
    flex-wrap: wrap;
    gap: 1rem;
    align-items: center;
  }
  .meta-item-abs {
    display: flex;
    align-items: center;
    gap: 0.375rem;
    font-size: 0.813rem;
    color: var(--gray-600);
  }
  .meta-item-abs i {
    color: var(--primary-blue);
  }
  .action-buttons-abs {
    display: flex;
    flex-wrap: wrap;
    gap: 0.75rem;
  }

  /* BUTTONS */
  .btn-abs {
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.625rem 1.25rem;
    border-radius: 8px;
    font-weight: 600;
    font-size: 0.875rem;
    border: none;
    cursor: pointer;
    text-decoration: none;
    transition: all 0.2s;
  }
  .btn-primary-abs {
    background: var(--primary-blue);
    color: var(--white);
  }
  .btn-primary-abs:hover {
    background: var(--dark-blue);
  }
  .btn-success-abs {
    background: var(--success);
    color: var(--white);
  }
  .btn-success-abs:hover {
    background: #059669;
  }
  .btn-danger-abs {
    background: var(--danger);
    color: var(--white);
  }
  .btn-danger-abs:hover {
    background: #dc2626;
  }
  .btn-info-abs {
    background: var(--info);
    color: var(--white);
  }
  .btn-info-abs:hover {
    background: #0891b2;
  }
  .btn-secondary-abs {
    background: var(--gray-200);
    color: var(--gray-700);
  }
  .btn-secondary-abs:hover {
    background: var(--gray-300);
  }
  .btn-disabled-abs {
    background: var(--gray-100);
    color: var(--gray-400);
    cursor: not-allowed;
  }

  /* WINDOW CARD */
  .window-card-abs {
    background: var(--white);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
  }
  .window-header-abs {
    background: var(--medium-blue);
    padding: 0.875rem 1.25rem;
    display: flex;
    align-items: center;
    gap: 0.625rem;
    font-weight: 600;
    color: var(--dark-blue);
  }
  .window-body-abs {
    padding: 1.5rem;
  }
  .window-time-abs {
    display: flex;
    justify-content: center;
    align-items: center;
    gap: 2rem;
  }
  .time-block-abs {
    text-align: center;
  }
  .time-label-small-abs {
    font-size: 0.75rem;
    color: var(--gray-600);
    margin-bottom: 0.5rem;
  }
  .time-value-large-abs {
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--gray-900);
  }
  .time-separator-abs {
    color: var(--primary-blue);
    font-size: 1.5rem;
  }

  /* INFO CARD */
  .info-card-abs {
    background: var(--white);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    height: 100%;
  }
  .info-card-header-abs {
    background: var(--gray-50);
    padding: 1rem 1.25rem;
    display: flex;
    align-items: center;
    gap: 0.625rem;
    font-weight: 600;
    color: var(--gray-900);
    border-bottom: 1px solid var(--gray-200);
  }
  .info-card-body-abs {
    padding: 1.25rem;
  }
  .event-title-abs {
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--gray-900);
    margin-bottom: 0.75rem;
  }
  .event-desc-abs {
    font-size: 0.875rem;
    color: var(--gray-600);
    line-height: 1.6;
    margin-bottom: 1.25rem;
  }
  .event-details-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
  }
  .detail-item-abs {
    display: flex;
    gap: 0.75rem;
  }
  .detail-icon-abs {
    width: 36px;
    height: 36px;
    background: var(--medium-blue);
    border-radius: 8px;
    display: grid;
    place-items: center;
    color: var(--primary-blue);
    flex-shrink: 0;
  }
  .detail-content-abs {
    flex: 1;
  }
  .detail-label-abs {
    font-size: 0.75rem;
    color: var(--gray-600);
    margin-bottom: 0.25rem;
  }
  .detail-value-abs {
    font-weight: 600;
    color: var(--gray-900);
    font-size: 0.875rem;
  }
  .link-abs {
    color: var(--primary-blue);
    text-decoration: none;
    font-weight: 600;
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
  }
  .link-abs:hover {
    text-decoration: underline;
  }

  /* PARTICIPATION INFO BANNER */
  .participation-info-abs {
    border-top: 1px solid var(--gray-200);
    padding-top: 1rem;
  }
  .participation-banner-abs {
    display: flex;
    align-items: flex-start;
    gap: 0.75rem;
    padding: 1rem;
    border-radius: 8px;
    font-size: 0.875rem;
  }
  .participation-banner-abs i {
    font-size: 1.25rem;
    flex-shrink: 0;
    margin-top: 0.125rem;
  }
  .participation-online {
    background: #cffafe;
    color: #155e75;
    border: 1px solid #06b6d4;
  }
  .participation-online i {
    color: var(--info);
  }
  .participation-offline {
    background: #d1fae5;
    color: #065f46;
    border: 1px solid #10b981;
  }
  .participation-offline i {
    color: var(--success);
  }
  .participation-all {
    background: #dbeafe;
    color: #1e40af;
    border: 1px solid #2563eb;
  }
  .participation-all i {
    color: var(--primary-blue);
  }

  /* PAYMENT INFO */
  .payment-status-abs {
    text-align: center;
    padding: 1rem 0;
    margin-bottom: 1rem;
    border-bottom: 1px solid var(--gray-100);
  }
  .payment-note-abs {
    font-size: 0.813rem;
    color: var(--gray-600);
    margin: 0.5rem 0 0 0;
  }
  .payment-details-abs {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
  }
  .payment-row-abs {
    display: flex;
    justify-content: space-between;
    align-items: center;
  }
  .payment-label-abs {
    font-size: 0.813rem;
    color: var(--gray-600);
  }
  .payment-value-abs {
    font-weight: 600;
    color: var(--gray-900);
    font-size: 0.875rem;
  }

  /* BADGES */
  .badge-abs {
    display: inline-flex;
    align-items: center;
    gap: 0.313rem;
    padding: 0.313rem 0.625rem;
    border-radius: 6px;
    font-size: 0.75rem;
    font-weight: 600;
  }
  .badge-primary {
    background: var(--primary-blue);
    color: var(--white);
  }
  .badge-success {
    background: var(--success);
    color: var(--white);
  }
  .badge-warning {
    background: var(--warning);
    color: var(--white);
  }
  .badge-info {
    background: var(--info);
    color: var(--white);
  }

  /* MODAL */
  .modal-abs {
    border-radius: 12px;
    overflow: hidden;
  }
  .modal-header-abs {
    background: var(--gray-50);
    padding: 1.25rem;
    border-bottom: 1px solid var(--gray-200);
  }
  .modal-title-abs {
    font-size: 1.125rem;
    font-weight: 700;
    color: var(--gray-900);
    margin: 0;
    display: flex;
    align-items: center;
  }
  .modal-body-abs {
    padding: 1.5rem;
  }
  .modal-footer-abs {
    background: var(--gray-50);
    padding: 1rem 1.5rem;
    border-top: 1px solid var(--gray-200);
    display: flex;
    gap: 0.75rem;
    justify-content: flex-end;
  }

  /* FORM */
  .form-group-abs {
    margin-bottom: 1.25rem;
  }
  .form-label-abs {
    display: block;
    font-weight: 600;
    color: var(--gray-900);
    margin-bottom: 0.5rem;
    font-size: 0.875rem;
  }
  .form-control-abs {
    width: 100%;
    padding: 0.625rem 0.875rem;
    border: 1px solid var(--gray-200);
    border-radius: 8px;
    font-size: 0.875rem;
    color: var(--gray-900);
  }
  .form-control-abs:focus {
    outline: none;
    border-color: var(--primary-blue);
    box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
  }
  .form-hint-abs {
    font-size: 0.75rem;
    color: var(--gray-600);
    margin-top: 0.375rem;
  }

  /* ALERT */
  .alert-abs {
    display: flex;
    gap: 0.75rem;
    padding: 0.875rem 1rem;
    border-radius: 8px;
    font-size: 0.875rem;
  }
  .alert-abs i {
    font-size: 1.125rem;
    flex-shrink: 0;
  }
  .alert-warning-abs {
    background: #fef3c7;
    color: #92400e;
  }
  .alert-warning-abs i {
    color: #f59e0b;
  }

  /* INFO BOX */
  .info-box-abs {
    background: var(--medium-blue);
    border-radius: 8px;
    padding: 1rem;
  }
  .info-box-content-abs {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 1rem;
  }
  .info-box-text-abs {
    flex: 1;
    font-size: 0.813rem;
    color: var(--dark-blue);
  }
  .info-box-badges-abs {
    display: flex;
    flex-direction: column;
    gap: 0.375rem;
  }

  /* SCANNER */
  .scanner-alert-abs {
    display: flex;
    gap: 0.75rem;
    padding: 0.875rem 1rem;
    border-radius: 8px;
    font-size: 0.875rem;
  }
  .scanner-alert-abs i {
    font-size: 1.125rem;
    flex-shrink: 0;
  }
  .scanner-success-abs {
    background: #d1fae5;
    color: #065f46;
  }
  .scanner-success-abs i {
    color: var(--success);
  }
  .scanner-warning-abs {
    background: #fef3c7;
    color: #92400e;
  }
  .scanner-warning-abs i {
    color: var(--warning);
  }
  .scanner-danger-abs {
    background: #fee2e2;
    color: #991b1b;
  }
  .scanner-danger-abs i {
    color: var(--danger);
  }
  .scanner-container-abs {
    background: var(--gray-50);
    border-radius: 8px;
    padding: 1.25rem;
    margin-bottom: 1rem;
  }
  #reader {
    border: 2px solid var(--gray-200);
    border-radius: 8px;
    overflow: hidden;
    max-width: 500px;
    margin: 0 auto;
  }
  .scanner-controls-abs {
    display: flex;
    justify-content: center;
    gap: 0.75rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
  }
  .scanner-message-abs {
    text-align: center;
    font-size: 0.875rem;
    color: var(--gray-600);
    padding: 0.75rem;
    background: var(--gray-50);
    border-radius: 8px;
  }
  .scan-result-abs {
    background: var(--medium-blue);
    border-radius: 8px;
    padding: 1rem;
    margin-top: 1rem;
  }
  .scan-result-header-abs {
    font-weight: 600;
    color: var(--dark-blue);
    margin-bottom: 0.75rem;
  }
  .scan-result-content-abs {
    font-family: 'Courier New', monospace;
    font-size: 0.813rem;
    color: var(--gray-900);
    background: var(--white);
    padding: 0.625rem;
    border-radius: 6px;
    word-break: break-all;
    margin-bottom: 0.75rem;
  }
  .scan-result-role-abs {
    margin-bottom: 0.75rem;
  }
  .scan-result-status-abs {
    font-size: 0.875rem;
  }
  .success-result-abs {
    background: #d1fae5;
    border-radius: 8px;
    padding: 2rem 1rem;
    text-align: center;
    margin-top: 1rem;
  }
  .success-icon-abs {
    font-size: 3rem;
    color: var(--success);
    margin-bottom: 1rem;
  }
  .success-title-abs {
    font-size: 1.25rem;
    font-weight: 700;
    color: #065f46;
    margin-bottom: 1rem;
  }
  .success-details-abs {
    background: var(--white);
    border-radius: 8px;
    padding: 1rem;
    font-size: 0.875rem;
    text-align: left;
  }

  /* RESPONSIVE */
  @media (max-width: 768px) {
    .page-header-abs {
      padding: 1.25rem;
    }
    .header-content-abs {
      flex-direction: column;
      align-items: flex-start;
    }
    .header-icon-abs {
      width: 40px;
      height: 40px;
      font-size: 1.25rem;
    }
    .header-title-abs {
      font-size: 1.25rem;
    }
    .header-time-abs {
      text-align: left;
      width: 100%;
    }
    
    .status-card-abs {
      padding: 1.25rem;
    }
    .status-card-content {
      flex-direction: column;
      align-items: stretch;
    }
    .action-buttons-abs {
      width: 100%;
    }
    .btn-abs {
      flex: 1;
      justify-content: center;
    }
    
    .window-time-abs {
      flex-direction: column;
      gap: 1rem;
    }
    .time-separator-abs {
      transform: rotate(90deg);
    }
    
    .event-details-grid {
      grid-template-columns: 1fr;
    }
    
    .info-box-content-abs {
      flex-direction: column;
      align-items: flex-start;
    }
    
    .scanner-controls-abs {
      flex-direction: column;
    }
    .scanner-controls-abs .btn-abs {
      width: 100%;
    }
  }

  @media (max-width: 576px) {
    .page-header-abs {
      padding: 1rem;
    }
    .header-icon-abs {
      width: 36px;
      height: 36px;
      font-size: 1.125rem;
    }
    .header-title-abs {
      font-size: 1.125rem;
    }
    
    .status-card-abs {
      padding: 1rem;
    }
    .event-meta-abs {
      flex-direction: column;
      align-items: flex-start;
      gap: 0.5rem;
    }
    
    .window-body-abs {
      padding: 1rem;
    }
    .time-value-large-abs {
      font-size: 1rem;
    }
    
    .info-card-body-abs {
      padding: 1rem;
    }
    .event-title-abs {
      font-size: 1rem;
    }
    
    .modal-body-abs {
      padding: 1rem;
    }
    .modal-footer-abs {
      padding: 0.875rem 1rem;
      flex-direction: column;
    }
    .modal-footer-abs .btn-abs {
      width: 100%;
    }
  }

  /* HIDE HTML5-QRCode DEFAULT STYLING */
  #reader__dashboard_section {
    display: none !important;
  }
  #reader__header_message {
    display: none !important;
  }
</style>