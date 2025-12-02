<?php
/** Detail Absensi Event - Enhanced for Audience */
$title = 'Detail Absensi Event';

$e = $event ?? [];
$payment = $payment ?? [];
$window = $window ?? ['is_open'=>false,'window_start_ts'=>null,'window_end_ts'=>null,'reason'=>'','current_time_wib'=>''];

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

$windowStartText = toWIBFormat($window['window_start_ts'] ?? null);
$windowEndText   = toWIBFormat($window['window_end_ts'] ?? null);

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

// Determine participation type display
$participationDisplay = ucfirst($participationType);
$participationBadgeClass = 'badge-primary';
if ($participationType === 'online') {
    $participationBadgeClass = 'badge-info';
} elseif ($participationType === 'offline') {
    $participationBadgeClass = 'badge-success';
}

// PERBAIKAN: Event times untuk display - GUNAKAN DATA DARI CONTROLLER
$eventStartText = '-';
$eventEndText = '-';
$eventStartTime = '-';
$eventEndTime = '-';

if (!empty($e['event_date']) && !empty($e['event_time'])) {
    try {
        // Event Start
        $eventStartDT = new DateTime($e['event_date'] . ' ' . $e['event_time'], new DateTimeZone('Asia/Jakarta'));
        $eventStartText = $eventStartDT->format('d M Y H:i');
        $eventStartTime = $eventStartDT->format('H:i');
        
        // Event End - PERBAIKAN: Gunakan end_time dari database atau dari window calculation
        if (!empty($e['end_time'])) {
            $eventEndDT = new DateTime($e['event_date'] . ' ' . $e['end_time'], new DateTimeZone('Asia/Jakarta'));
            $eventEndText = $eventEndDT->format('d M Y H:i');
            $eventEndTime = $eventEndDT->format('H:i');
        } elseif (!empty($window['event_end_ts'])) {
            // Fallback: gunakan dari window calculation
            $eventEndText = toWIBFormat($window['event_end_ts'], 'd M Y H:i');
            $eventEndTime = toWIBFormat($window['event_end_ts'], 'H:i');
        } else {
            // Default 8 jam setelah start
            $eventEndDT = clone $eventStartDT;
            $eventEndDT->modify('+8 hours');
            $eventEndText = $eventEndDT->format('d M Y H:i');
            $eventEndTime = $eventEndDT->format('H:i');
        }
    } catch (Exception $ex) {
        log_message('error', 'Error parsing event times: ' . $ex->getMessage());
    }
}

// Format untuk Info Box Event
$eventFullTimeRange = $eventStartText . ' - ' . $eventEndTime . ' WIB';
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_audience') ?>
<?= $this->include('partials/alerts') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/absensi_detail_audience.css'); ?>">

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
                  <div class="status-title-abs">Window Absensi Terbuka</div>
                  <div class="status-desc-abs">Silahkan lakukan absensi sekarang (2 jam terakhir sebelum event berakhir)</div>
                <?php else: ?>
                  <div class="status-title-abs">Window Absensi Belum/Sudah Ditutup</div>
                  <div class="status-desc-abs"><?= $window['reason'] ? esc($window['reason']) : 'Belum waktunya absensi' ?></div>
                <?php endif; ?>
              </div>
            </div>
            
            <div class="event-meta-abs">
              <div class="meta-item-abs">
                <i class="bi bi-geo-alt"></i>
                <span><?= esc($e['location'] ?? '-') ?></span>
              </div>
              <div class="meta-item-abs">
                <i class="bi bi-diagram-3"></i>
                <span><?= !empty($e['format']) ? strtoupper(esc($e['format'])) : 'N/A' ?></span>
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
          <span>Window Absensi - 2 Jam Terakhir (WIB)</span>
        </div>
        <div class="window-body-abs">
          <!-- Info Event Full Time -->
          <div class="alert alert-info mb-3" style="border-radius: 0.5rem; padding: 0.75rem;">
            <strong><i class="bi bi-info-circle"></i> Info Event:</strong><br>
            Event berlangsung: <strong><?= $eventStartText ?> - <?= $eventEndTime ?> WIB</strong><br>
            <small>Window absensi hanya dibuka di <strong>2 jam terakhir</strong> sebelum event berakhir</small>
          </div>
          
          <!-- Window Time Display -->
          <div class="window-time-abs">
            <div class="time-block-abs">
              <div class="time-label-small-abs">Window Dibuka</div>
              <div class="time-value-large-abs"><?= $windowStartText ?></div>
              <small class="text-muted">2 jam sebelum selesai</small>
            </div>
            <div class="time-separator-abs">
              <i class="bi bi-arrow-right"></i>
            </div>
            <div class="time-block-abs">
              <div class="time-label-small-abs">Window Ditutup</div>
              <div class="time-value-large-abs"><?= $windowEndText ?></div>
              <small class="text-muted">Saat event selesai</small>
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
                <div class="detail-item-abs">
                  <div class="detail-icon-abs">
                    <i class="bi bi-calendar3"></i>
                  </div>
                  <div class="detail-content-abs">
                    <div class="detail-label-abs">Tanggal</div>
                    <div class="detail-value-abs"><?= $tgl ?></div>
                  </div>
                </div>

                <div class="detail-item-abs">
                  <div class="detail-icon-abs">
                    <i class="bi bi-clock"></i>
                  </div>
                  <div class="detail-content-abs">
                    <div class="detail-label-abs">Waktu Event</div>
                    <div class="detail-value-abs"><?= $eventStartTime ?> - <?= $eventEndTime ?> WIB</div>
                  </div>
                </div>

                <div class="detail-item-abs">
                  <div class="detail-icon-abs">
                    <i class="bi bi-geo-alt-fill"></i>
                  </div>
                  <div class="detail-content-abs">
                    <div class="detail-label-abs">Lokasi</div>
                    <div class="detail-value-abs"><?= esc($e['location'] ?? '-') ?></div>
                  </div>
                </div>

                <?php if (!empty($e['zoom_link']) && ($participationType === 'online' || $participationType === 'all')): ?>
                <div class="detail-item-abs">
                  <div class="detail-icon-abs">
                    <i class="bi bi-camera-video"></i>
                  </div>
                  <div class="detail-content-abs">
                    <div class="detail-label-abs">Link Meeting</div>
                    <div class="detail-value-abs">
                      <a href="<?= esc($e['zoom_link']) ?>" target="_blank" class="link-abs">Buka Tautan</a>
                    </div>
                  </div>
                </div>
                <?php endif; ?>
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
          
          <div class="alert-abs alert-info-abs mb-3">
            <i class="bi bi-info-circle"></i>
            <div>
              <strong>Window Absensi Terbatas:</strong>
              <p class="mb-0 small">
                Absensi hanya dapat dilakukan di <strong>2 jam terakhir</strong> sebelum event berakhir
              </p>
            </div>
          </div>

          <div class="alert-abs alert-warning-abs mb-3">
            <i class="bi bi-shield-exclamation"></i>
            <div>
              <strong>Khusus Audience <?= $participationDisplay ?>:</strong>
              <p class="mb-0 small">
                Gunakan QR Code <strong>Audience</strong> atau <strong>Universal</strong> saja
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
                <?= $windowStartText ?> - <?= $windowEndText ?><br>
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
            <p class="mb-0 small">QR Code valid akan langsung disimpan ke database</p>
            <div class="small mt-1"><strong>Waktu:</strong> <span id="scanner-wib-time"><?= $currentWIBString ?></span> WIB</div>
          </div>
        </div>

        <!-- Window Info -->
        <div class="scanner-alert-abs scanner-info-abs mb-3">
          <i class="bi bi-clock-history"></i>
          <div>
            <strong>Window Absensi Terbatas</strong>
            <p class="mb-0 small">
              Hanya bisa absen di 2 jam terakhir: <strong><?= $windowStartText ?> - <?= $windowEndText ?></strong>
            </p>
          </div>
        </div>

        <!-- Participation Warning -->
        <div class="scanner-alert-abs scanner-warning-abs mb-3">
          <i class="bi bi-shield-exclamation"></i>
          <div>
            <strong>Validasi Audience</strong>
            <p class="mb-0 small">
              Terdaftar: <span class="badge-abs <?= $participationBadgeClass ?>"><?= $participationDisplay ?></span>
              · Gunakan QR <span class="badge-abs badge-warning">Audience</span> atau <span class="badge-abs badge-primary">Universal</span>
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

// WIB Time Helper
function getWIBTime() {
  const now = new Date();
  const wibOffset = 7 * 60;
  const localOffset = now.getTimezoneOffset();
  const totalOffset = wibOffset + localOffset;
  const wibTime = new Date(now.getTime() + (totalOffset * 60 * 1000));
  
  const year = wibTime.getFullYear();
  const month = String(wibTime.getMonth() + 1).padStart(2, '0');
  const day = String(wibTime.getDate()).padStart(2, '0');
  const hours = String(wibTime.getHours()).padStart(2, '0');
  const minutes = String(wibTime.getMinutes()).padStart(2, '0');
  const seconds = String(wibTime.getSeconds()).padStart(2, '0');
  
  return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
}

function updateWIBTimeDisplay() {
  const elements = ['current-time-display', 'scanner-wib-time'];
  elements.forEach(id => {
    const el = document.getElementById(id);
    if (el) el.textContent = getWIBTime();
  });
}

setInterval(updateWIBTimeDisplay, 1000);

// QR Code role detection - SIMPLIFIED untuk Audience
function detectQRRole(qrData) {
  const patterns = {
    universal: /EVENT_\d+_all_/i,
    audience: /EVENT_\d+_audience_/i,
    presenter: /EVENT_\d+_presenter_/i,
    simple: /EVENT_\d+_\d{8}$/i,
    numeric: /^\d+$/,
    admin: /^(ADMIN|MANUAL|BULK)_\d+/i
  };

  let qrType = 'unknown';
  let allowed = false;
  let message = '';
  let badge = 'badge-abs';

  if (patterns.universal.test(qrData) || patterns.simple.test(qrData) || patterns.numeric.test(qrData) || patterns.admin.test(qrData)) {
    qrType = 'universal';
    allowed = true;
    badge += ' badge-primary';
    message = 'Universal QR - Valid untuk Audience';
  } else if (patterns.audience.test(qrData)) {
    qrType = 'audience';
    allowed = true;
    badge += ' badge-warning';
    message = 'Audience QR - Valid';
  } else if (patterns.presenter.test(qrData)) {
    qrType = 'presenter';
    allowed = false;
    badge += ' badge-danger';
    message = 'QR Presenter - Tidak dapat digunakan oleh Audience';
  } else {
    qrType = 'unknown';
    allowed = false;
    badge += ' badge-secondary';
    message = 'Format QR tidak dikenali';
  }

  return { type: qrType, allowed, badge, text: message };
}

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
      isProcessing = false;
    }
  } catch (error) {
    console.error('Error:', error);
    Swal.fire({ icon: 'error', title: 'Error Koneksi', text: 'Gagal terhubung ke server' });
    isProcessing = false;
  }
}

function showError(message) {
  document.getElementById('error-message').textContent = message;
  document.getElementById('scanner-error').style.display = 'flex';
}

function continueScanning() {
  scanSuccessful = false;
  isProcessing = false;
  scannedResult = null;
  document.getElementById('scan-result').style.display = 'none';
  document.getElementById('success-result').style.display = 'none';
  document.getElementById('continueScanning').style.display = 'none';
  document.getElementById('scanner-error').style.display = 'none';
  startScanner();
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