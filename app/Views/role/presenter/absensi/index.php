<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Absensi QR Scanner - SNIA Presenter</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #64748b;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #06b6d4;
            --purple-color: #8b5cf6;
        }

        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }

        .sidebar {
            background: linear-gradient(180deg, var(--purple-color) 0%, #7c3aed 100%);
            min-height: 100vh;
            box-shadow: 4px 0 20px rgba(0,0,0,0.1);
        }

        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            margin: 4px 0;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .sidebar .nav-link:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            transform: translateX(5px);
        }

        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: white;
        }

        .main-content {
            background: white;
            border-radius: 20px 0 0 0;
            min-height: 100vh;
            box-shadow: -4px 0 20px rgba(0,0,0,0.05);
        }

        .scanner-container {
            background: white;
            border-radius: 20px;
            padding: 30px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            margin: 20px 0;
            position: relative;
            overflow: hidden;
        }

        .scanner-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, var(--purple-color) 0%, var(--primary-color) 100%);
        }

        #qr-reader {
            width: 100%;
            max-width: 500px;
            margin: 0 auto;
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 8px 32px rgba(0,0,0,0.1);
        }

        .status-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            margin: 20px 0;
            transition: all 0.3s ease;
        }

        .status-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.12);
        }

        .event-card {
            background: linear-gradient(135deg, #f8fafc 0%, white 100%);
            border: 2px solid #e2e8f0;
            border-radius: 16px;
            padding: 20px;
            margin: 16px 0;
            transition: all 0.3s ease;
            position: relative;
        }

        .event-card.today {
            border-color: var(--success-color);
            background: linear-gradient(135deg, #ecfdf5 0%, white 100%);
        }

        .event-card.attended {
            border-color: var(--primary-color);
            background: linear-gradient(135deg, #eff6ff 0%, white 100%);
        }

        .live-indicator {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--danger-color);
            color: white;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        .scanner-status {
            text-align: center;
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
            font-weight: 600;
        }

        .scanner-status.ready {
            background: #ecfdf5;
            color: #059669;
            border: 2px solid #a7f3d0;
        }

        .scanner-status.scanning {
            background: #fef3c7;
            color: #d97706;
            border: 2px solid #fde68a;
        }

        .scanner-status.error {
            background: #fee2e2;
            color: #dc2626;
            border: 2px solid #fecaca;
        }

        .scanner-status.success {
            background: #dbeafe;
            color: #1d4ed8;
            border: 2px solid #bae6fd;
        }

        .attendance-item {
            background: white;
            border-radius: 12px;
            padding: 16px;
            margin: 12px 0;
            border-left: 4px solid var(--info-color);
            box-shadow: 0 4px 16px rgba(0,0,0,0.05);
            transition: all 0.2s ease;
        }

        .attendance-item:hover {
            transform: translateX(4px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.1);
        }

        .attendance-item.today {
            border-left-color: var(--success-color);
            background: linear-gradient(135deg, #f0fdf4 0%, white 100%);
        }

        .time-display {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--purple-color) 100%);
            color: white;
            padding: 16px;
            border-radius: 12px;
            text-align: center;
            font-size: 18px;
            font-weight: 600;
            margin: 20px 0;
        }

        .manual-input {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            border: 2px dashed #cbd5e1;
        }

        .btn-scan {
            background: linear-gradient(135deg, var(--success-color) 0%, #059669 100%);
            border: none;
            border-radius: 12px;
            padding: 12px 24px;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-scan:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
            color: white;
        }

        .notification-live {
            position: fixed;
            top: 20px;
            right: 20px;
            background: white;
            border-radius: 12px;
            padding: 16px 20px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.15);
            border-left: 4px solid var(--success-color);
            z-index: 1050;
            min-width: 300px;
            transform: translateX(350px);
            transition: all 0.3s ease;
        }

        .notification-live.show {
            transform: translateX(0);
        }

        .notification-live.error {
            border-left-color: var(--danger-color);
        }

        .notification-live.warning {
            border-left-color: var(--warning-color);
        }

        .refresh-indicator {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--info-color);
            color: white;
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            animation: fadeInOut 3s infinite;
        }

        @keyframes fadeInOut {
            0%, 100% { opacity: 0.7; }
            50% { opacity: 1; }
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin: 20px 0;
        }

        .stat-item {
            background: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 16px rgba(0,0,0,0.05);
            border-top: 4px solid var(--primary-color);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: var(--primary-color);
            margin-bottom: 8px;
        }

        .stat-label {
            color: #64748b;
            font-size: 14px;
        }

        .camera-controls {
            display: flex;
            justify-content: center;
            gap: 12px;
            margin: 20px 0;
        }

        .camera-controls button {
            border: none;
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary-color);
            border-radius: 8px;
            padding: 8px 16px;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .camera-controls button:hover {
            background: var(--primary-color);
            color: white;
        }

        .header-section {
            background: linear-gradient(135deg, var(--purple-color) 0%, #7c3aed 100%);
            color: white;
            padding: 30px;
            border-radius: 16px;
            margin-bottom: 30px;
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar">
                    <div class="p-4 text-center">
                        <h4 class="text-white mb-0">
                            <i class="fas fa-chalkboard-teacher me-2"></i>
                            SNIA Presenter
                        </h4>
                        <small class="text-white-50">QR Scanner</small>
                    </div>
                    
                    <nav class="nav flex-column px-3">
                        <a class="nav-link" href="<?= site_url('presenter/dashboard') ?>">
                            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                        </a>
                        <a class="nav-link" href="<?= site_url('presenter/events') ?>">
                            <i class="fas fa-calendar-alt me-2"></i> Event
                        </a>
                        <a class="nav-link" href="<?= site_url('presenter/abstrak') ?>">
                            <i class="fas fa-file-alt me-2"></i> Abstrak
                        </a>
                        <a class="nav-link" href="<?= site_url('presenter/pembayaran') ?>">
                            <i class="fas fa-credit-card me-2"></i> Pembayaran
                        </a>
                        <a class="nav-link active" href="<?= site_url('presenter/absensi') ?>">
                            <i class="fas fa-qrcode me-2"></i> Absensi
                        </a>
                        <a class="nav-link" href="<?= site_url('presenter/dokumen/loa') ?>">
                            <i class="fas fa-certificate me-2"></i> Dokumen
                        </a>
                        <hr class="my-3" style="border-color: rgba(255,255,255,0.2);">
                        <a class="nav-link text-warning" href="<?= site_url('auth/logout') ?>">
                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="main-content p-4">
                    <!-- Header -->
                    <div class="header-section">
                        <div class="row align-items-center">
                            <div class="col">
                                <h1 class="mb-2">
                                    <i class="fas fa-qrcode me-3"></i>Scanner Absensi QR
                                </h1>
                                <p class="mb-0 opacity-75">
                                    Scan QR code untuk mencatat kehadiran pada event
                                </p>
                            </div>
                            <div class="col-auto">
                                <div class="live-indicator">
                                    <div class="spinner-border spinner-border-sm" role="status"></div>
                                    <span>LIVE</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Real-time Clock -->
                    <div class="time-display">
                        <div id="current-time"><?= date('H:i:s') ?> WIB</div>
                        <div style="font-size: 14px; opacity: 0.8;">
                            <?= date('l, d F Y') ?>
                        </div>
                    </div>

                    <!-- Statistics -->
                    <div class="stats-grid">
                        <div class="stat-item">
                            <div class="stat-number" id="total-events"><?= count($currentEvents) ?></div>
                            <div class="stat-label">Total Event Terdaftar</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number" id="today-events"><?= count($todayEvents) ?></div>
                            <div class="stat-label">Event Hari Ini</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number" id="attended-today">
                                <?= count(array_filter($todayAttendance, function($att) { return $att !== null; })) ?>
                            </div>
                            <div class="stat-label">Sudah Absen Hari Ini</div>
                        </div>
                        <div class="stat-item">
                            <div class="stat-number" id="total-attendance"><?= count($attendanceHistory) ?></div>
                            <div class="stat-label">Total Kehadiran</div>
                        </div>
                    </div>

                    <!-- QR Scanner Section -->
                    <div class="scanner-container">
                        <div class="text-center mb-4">
                            <h3>
                                <i class="fas fa-camera me-2"></i>Scanner QR Code
                                <span class="refresh-indicator ms-3">
                                    <i class="fas fa-sync-alt"></i>
                                    Auto-refresh
                                </span>
                            </h3>
                        </div>

                        <!-- Scanner Status -->
                        <div id="scanner-status" class="scanner-status ready">
                            <i class="fas fa-camera me-2"></i>
                            Scanner siap digunakan. Arahkan QR code ke kamera.
                        </div>

                        <!-- QR Reader -->
                        <div id="qr-reader"></div>

                        <!-- Camera Controls -->
                        <div class="camera-controls">
                            <button id="start-scanner" class="btn-scan">
                                <i class="fas fa-play me-2"></i>Mulai Scanner
                            </button>
                            <button id="stop-scanner" style="display: none;">
                                <i class="fas fa-stop me-2"></i>Hentikan Scanner
                            </button>
                            <button id="switch-camera" style="display: none;">
                                <i class="fas fa-sync-alt me-2"></i>Ganti Kamera
                            </button>
                        </div>

                        <!-- Manual Input -->
                        <div class="manual-input">
                            <h5><i class="fas fa-keyboard me-2"></i>Input Manual QR Code</h5>
                            <div class="input-group">
                                <input type="text" id="manual-qr" class="form-control" 
                                       placeholder="Masukkan kode QR secara manual...">
                                <button class="btn btn-primary" id="manual-submit">
                                    <i class="fas fa-check me-2"></i>Submit
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Today's Events -->
                    <?php if (!empty($todayEvents)): ?>
                    <div class="status-card">
                        <h4 class="mb-3">
                            <i class="fas fa-calendar-day me-2 text-success"></i>
                            Event Hari Ini
                        </h4>
                        <?php foreach ($todayEvents as $event): ?>
                        <div class="event-card today <?= isset($todayAttendance[$event['id']]) && $todayAttendance[$event['id']] ? 'attended' : '' ?>" 
                             data-event-id="<?= $event['id'] ?>">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h5 class="mb-1"><?= esc($event['title']) ?></h5>
                                    <div class="text-muted">
                                        <i class="fas fa-clock me-1"></i>
                                        <?= date('H:i', strtotime($event['event_time'])) ?> WIB
                                        <span class="ms-3">
                                            <i class="fas fa-users me-1"></i>
                                            <?= ucfirst($event['participation_type']) ?>
                                        </span>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <?php if (isset($todayAttendance[$event['id']]) && $todayAttendance[$event['id']]): ?>
                                        <div class="badge bg-success fs-6 px-3 py-2">
                                            <i class="fas fa-check me-2"></i>Sudah Absen
                                            <div style="font-size: 11px;">
                                                <?= date('H:i', strtotime($todayAttendance[$event['id']]['waktu_scan'])) ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="badge bg-warning fs-6 px-3 py-2">
                                            <i class="fas fa-clock me-2"></i>Belum Absen
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- All Events -->
                    <div class="status-card">
                        <h4 class="mb-3">
                            <i class="fas fa-list me-2 text-primary"></i>
                            Semua Event Terdaftar
                        </h4>
                        <?php if (!empty($currentEvents)): ?>
                            <?php foreach ($currentEvents as $event): ?>
                            <div class="event-card" data-event-id="<?= $event['id'] ?>">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <h6 class="mb-1"><?= esc($event['title']) ?></h6>
                                        <div class="text-muted small">
                                            <i class="fas fa-calendar me-1"></i>
                                            <?= date('d M Y', strtotime($event['event_date'])) ?>
                                            <span class="ms-2">
                                                <i class="fas fa-clock me-1"></i>
                                                <?= date('H:i', strtotime($event['event_time'])) ?>
                                            </span>
                                            <span class="ms-2">
                                                <i class="fas fa-check me-1"></i>
                                                Verified: <?= date('d M', strtotime($event['verified_at'])) ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <div class="badge bg-info">
                                            <?= ucfirst($event['participation_type']) ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center text-muted py-4">
                                <i class="fas fa-calendar-times fa-2x mb-2"></i>
                                <div>Belum ada event yang terdaftar</div>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Attendance History -->
                    <div class="status-card">
                        <h4 class="mb-3">
                            <i class="fas fa-history me-2 text-info"></i>
                            Riwayat Kehadiran
                        </h4>
                        <div style="max-height: 400px; overflow-y: auto;">
                            <?php if (!empty($attendanceHistory)): ?>
                                <?php foreach ($attendanceHistory as $attendance): ?>
                                <div class="attendance-item <?= date('Y-m-d', strtotime($attendance['waktu_scan'])) === date('Y-m-d') ? 'today' : '' ?>">
                                    <div class="row align-items-center">
                                        <div class="col">
                                            <h6 class="mb-1"><?= esc($attendance['event_title'] ?? 'Event') ?></h6>
                                            <div class="text-muted small">
                                                <i class="fas fa-calendar me-1"></i>
                                                <?= date('d M Y', strtotime($attendance['waktu_scan'])) ?>
                                                <span class="ms-2">
                                                    <i class="fas fa-clock me-1"></i>
                                                    <?= date('H:i', strtotime($attendance['waktu_scan'])) ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="col-auto">
                                            <div class="badge bg-success">
                                                <i class="fas fa-check me-1"></i>
                                                <?= ucfirst($attendance['status']) ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2"></i>
                                    <div>Belum ada riwayat kehadiran</div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Live Notification -->
    <div id="live-notification" class="notification-live">
        <div class="d-flex align-items-center">
            <div class="me-3">
                <i class="fas fa-bell text-success"></i>
            </div>
            <div class="flex-grow-1">
                <div class="fw-bold" id="notif-title">Notification</div>
                <div class="small text-muted" id="notif-message">Message</div>
            </div>
            <div>
                <button class="btn-close" id="close-notification"></button>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://unpkg.com/html5-qrcode"></script>
    <script>
        // Global variables
        let html5QrcodeScanner = null;
        let isScanning = false;
        let refreshInterval = null;
        let cameras = [];
        let currentCameraIndex = 0;

        document.addEventListener('DOMContentLoaded', function() {
            initializeApp();
        });

        function initializeApp() {
            updateClock();
            setInterval(updateClock, 1000);
            
            // Auto-refresh data every 30 seconds
            refreshInterval = setInterval(refreshData, 30000);
            
            setupEventListeners();
            initializeQRScanner();
            
            showNotification('Scanner Ready', 'QR scanner siap digunakan', 'success');
        }

        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('id-ID', {
                timeZone: 'Asia/Jakarta',
                hour12: false
            });
            document.getElementById('current-time').textContent = timeString + ' WIB';
        }

        function setupEventListeners() {
            // Start scanner button
            document.getElementById('start-scanner').addEventListener('click', startScanner);
            
            // Stop scanner button
            document.getElementById('stop-scanner').addEventListener('click', stopScanner);
            
            // Switch camera button
            document.getElementById('switch-camera').addEventListener('click', switchCamera);
            
            // Manual QR input
            document.getElementById('manual-submit').addEventListener('click', submitManualQR);
            document.getElementById('manual-qr').addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    submitManualQR();
                }
            });

            // Close notification
            document.getElementById('close-notification').addEventListener('click', hideNotification);

            // Show flash messages
            showFlashMessages();
        }

        async function initializeQRScanner() {
            try {
                // Get available cameras
                cameras = await Html5Qrcode.getCameras();
                
                if (cameras && cameras.length > 1) {
                    document.getElementById('switch-camera').style.display = 'inline-block';
                }
                
                updateScannerStatus('ready', 'Scanner siap. Klik "Mulai Scanner" untuk memulai.');
                
            } catch (err) {
                console.error('Error initializing scanner:', err);
                updateScannerStatus('error', 'Gagal mengakses kamera. Pastikan browser memiliki izin kamera.');
            }
        }

        async function startScanner() {
            if (isScanning) return;

            try {
                const cameraId = cameras.length > 0 ? cameras[currentCameraIndex].id : undefined;
                
                html5QrcodeScanner = new Html5Qrcode("qr-reader");
                
                await html5QrcodeScanner.start(
                    cameraId,
                    {
                        fps: 10,
                        qrbox: { width: 250, height: 250 },
                        aspectRatio: 1.0
                    },
                    onScanSuccess,
                    onScanFailure
                );

                isScanning = true;
                updateScannerStatus('scanning', 'Scanner aktif. Arahkan QR code ke kamera.');
                
                document.getElementById('start-scanner').style.display = 'none';
                document.getElementById('stop-scanner').style.display = 'inline-block';
                
            } catch (err) {
                console.error('Error starting scanner:', err);
                updateScannerStatus('error', 'Gagal memulai scanner: ' + err);
                showNotification('Scanner Error', 'Gagal memulai scanner kamera', 'error');
            }
        }

        async function stopScanner() {
            if (!isScanning || !html5QrcodeScanner) return;

            try {
                await html5QrcodeScanner.stop();
                html5QrcodeScanner.clear();
                html5QrcodeScanner = null;
                
                isScanning = false;
                updateScannerStatus('ready', 'Scanner dihentikan. Klik "Mulai Scanner" untuk memulai lagi.');
                
                document.getElementById('start-scanner').style.display = 'inline-block';
                document.getElementById('stop-scanner').style.display = 'none';
                
            } catch (err) {
                console.error('Error stopping scanner:', err);
            }
        }

        async function switchCamera() {
            if (!isScanning || cameras.length <= 1) return;

            await stopScanner();
            
            currentCameraIndex = (currentCameraIndex + 1) % cameras.length;
            
            setTimeout(() => {
                startScanner();
                showNotification('Camera Switched', `Menggunakan kamera: ${cameras[currentCameraIndex].label}`, 'info');
            }, 500);
        }

        function onScanSuccess(decodedText, decodedResult) {
            // Stop scanner temporarily to prevent multiple scans
            stopScanner();
            
            updateScannerStatus('success', 'QR code terdeteksi! Memproses...');
            processQRCode(decodedText);
        }

        function onScanFailure(error) {
            // Silent handling - QR code not found is normal
        }

        function submitManualQR() {
            const qrCode = document.getElementById('manual-qr').value.trim();
            
            if (!qrCode) {
                showNotification('Input Error', 'Masukkan kode QR terlebih dahulu', 'warning');
                return;
            }
            
            updateScannerStatus('success', 'Memproses QR code manual...');
            processQRCode(qrCode);
        }

        async function processQRCode(qrCode) {
            try {
                showNotification('Processing', 'Memproses QR code...', 'info');
                
                // Client-side pre-validation for better UX
                const preValidation = validateQRCodeFormat(qrCode);
                if (!preValidation.valid) {
                    updateScannerStatus('error', preValidation.message);
                    showNotification('QR Code Tidak Valid', preValidation.message, 'error');
                    setTimeout(() => {
                        updateScannerStatus('ready', 'Scanner siap. Klik "Mulai Scanner" untuk scan lagi.');
                    }, 3000);
                    return;
                }
                
                const response = await fetch('<?= site_url('presenter/absensi/scan') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: `qr_code=${encodeURIComponent(qrCode)}`
                });

                const data = await response.json();

                if (data.success) {
                    updateScannerStatus('success', 'Absensi berhasil dicatat!');
                    showNotification('Berhasil!', data.message, 'success');
                    
                    // Update UI with new data
                    if (data.data) {
                        updateAttendanceUI(data.data);
                    }
                    
                    // Refresh data
                    setTimeout(refreshData, 2000);
                    
                    // Clear manual input
                    document.getElementById('manual-qr').value = '';
                    
                } else {
                    updateScannerStatus('error', data.message);
                    showNotification('Gagal', data.message, 'error');
                }

            } catch (error) {
                console.error('Error processing QR:', error);
                updateScannerStatus('error', 'Terjadi kesalahan saat memproses QR code');
                showNotification('Error', 'Terjadi kesalahan koneksi', 'error');
            }

            // Reset scanner after 3 seconds
            setTimeout(() => {
                updateScannerStatus('ready', 'Scanner siap. Klik "Mulai Scanner" untuk scan lagi.');
            }, 3000);
        }

        // Client-side QR code format validation for better UX
        function validateQRCodeFormat(qrCode) {
            qrCode = qrCode.trim();
            
            // Pattern 1: Standard format with role validation
            const standardPattern = /^EVENT_(\d+)_([a-z]+)_([a-z]+)_(\d{8})_([a-f0-9]+)$/i;
            if (standardPattern.test(qrCode)) {
                const matches = qrCode.match(standardPattern);
                const role = matches[2].toLowerCase();
                
                if (role === 'audience') {
                    return {
                        valid: false,
                        message: 'QR Code Audience terdeteksi. Presenter memerlukan QR code khusus Presenter atau Universal.'
                    };
                } else if (role === 'presenter') {
                    return {
                        valid: true,
                        message: 'QR Code Presenter terdeteksi - Valid'
                    };
                } else if (role === 'all' || role === 'universal') {
                    return {
                        valid: true,
                        message: 'QR Code Universal terdeteksi - Valid untuk semua role'
                    };
                }
            }
            
            // Pattern 2: Audience-specific patterns (reject immediately)
            const audiencePatterns = [
                /^EVENT_(\d+)_AUDIENCE_(online|offline)_(\d{8})_([a-f0-9]+)$/i,
                /^EVENT_(\d+)_audience_(online|offline)_(\d{8})_([a-f0-9]+)$/i
            ];
            
            for (let pattern of audiencePatterns) {
                if (pattern.test(qrCode)) {
                    const matches = qrCode.match(pattern);
                    const participationType = matches[2];
                    return {
                        valid: false,
                        message: `QR Code ini khusus untuk Audience (${participationType}). Presenter tidak dapat menggunakan QR code Audience.`
                    };
                }
            }
            
            // Pattern 3: Presenter-specific format
            const presenterPattern = /^EVENT_(\d+)_PRESENTER_([a-f0-9]+)$/i;
            if (presenterPattern.test(qrCode)) {
                return {
                    valid: true,
                    message: 'QR Code Presenter terdeteksi - Valid'
                };
            }
            
            // Pattern 4: Simple universal format
            const simplePattern = /^EVENT_(\d+)_(\d{8})$/i;
            if (simplePattern.test(qrCode)) {
                return {
                    valid: true,
                    message: 'QR Code Universal terdeteksi - Valid untuk semua role'
                };
            }
            
            // Pattern 5: Legacy numeric (allowed but with warning)
            if (/^\d+$/.test(qrCode)) {
                return {
                    valid: true,
                    message: 'Format legacy terdeteksi - Memproses sebagai Universal'
                };
            }
            
            // Pattern 6: Check for obvious audience indicators
            if (qrCode.toLowerCase().includes('audience')) {
                return {
                    valid: false,
                    message: 'QR Code Audience terdeteksi. Presenter memerlukan QR code khusus Presenter atau Universal.'
                };
            }
            
            // If no pattern matches, let server handle it
            return {
                valid: true,
                message: 'Format tidak dikenali - Memproses di server'
            };
        }

        function updateScannerStatus(type, message) {
            const statusElement = document.getElementById('scanner-status');
            statusElement.className = `scanner-status ${type}`;
            
            const icons = {
                'ready': 'fas fa-camera',
                'scanning': 'fas fa-spinner fa-spin',
                'success': 'fas fa-check-circle',
                'error': 'fas fa-exclamation-circle',
                'waiting': 'fas fa-clock'
            };
            
            statusElement.innerHTML = `<i class="${icons[type]} me-2"></i>${message}`;
        }

        // Function to check if current time allows scanning
        function checkScanningTime() {
            const now = new Date();
            const currentHour = now.getHours();
            const currentMinute = now.getMinutes();
            
            // Allow scanning from 6:00 AM to 11:59 PM (more flexible timing)
            if (currentHour >= 6 && currentHour < 24) {
                return {
                    allowed: true,
                    message: 'Scanner tersedia untuk event hari ini'
                };
            } else {
                const nextOpen = new Date();
                nextOpen.setHours(6, 0, 0, 0);
                if (currentHour >= 0 && currentHour < 6) {
                    // Same day
                } else {
                    // Next day
                    nextOpen.setDate(nextOpen.getDate() + 1);
                }
                
                const timeUntilOpen = nextOpen - now;
                const hoursRemaining = Math.floor(timeUntilOpen / (1000 * 60 * 60));
                const minutesRemaining = Math.floor((timeUntilOpen % (1000 * 60 * 60)) / (1000 * 60));
                
                return {
                    allowed: false,
                    message: `Scanner akan aktif pada ${nextOpen.toLocaleTimeString('id-ID', {hour: '2-digit', minute: '2-digit'})} (${hoursRemaining} jam ${minutesRemaining} menit lagi)`
                };
            }
        }

        function updateAttendanceUI(attendanceData) {
            // Update today's events status
            const eventCards = document.querySelectorAll('[data-event-id]');
            eventCards.forEach(card => {
                if (card.dataset.eventId == attendanceData.event_id) {
                    card.classList.add('attended');
                    
                    const badge = card.querySelector('.badge');
                    if (badge) {
                        badge.className = 'badge bg-success fs-6 px-3 py-2';
                        badge.innerHTML = `
                            <i class="fas fa-check me-2"></i>Sudah Absen
                            <div style="font-size: 11px;">${attendanceData.attendance_time}</div>
                        `;
                    }
                }
            });

            // Update statistics
            const attendedToday = document.getElementById('attended-today');
            if (attendedToday) {
                const currentCount = parseInt(attendedToday.textContent);
                attendedToday.textContent = currentCount + 1;
            }

            const totalAttendance = document.getElementById('total-attendance');
            if (totalAttendance) {
                const currentCount = parseInt(totalAttendance.textContent);
                totalAttendance.textContent = currentCount + 1;
            }
        }

        async function refreshData() {
            try {
                const response = await fetch('<?= site_url('presenter/absensi') ?>', {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (response.ok) {
                    console.log('Data refreshed successfully');
                    // Optional: Update specific UI elements without full page reload
                }
            } catch (error) {
                console.error('Error refreshing data:', error);
            }
        }

        function showNotification(title, message, type = 'success') {
            const notification = document.getElementById('live-notification');
            const titleElement = document.getElementById('notif-title');
            const messageElement = document.getElementById('notif-message');
            
            titleElement.textContent = title;
            messageElement.textContent = message;
            
            // Reset classes
            notification.className = 'notification-live show';
            if (type !== 'success') {
                notification.classList.add(type);
            }

            // Auto hide after 5 seconds
            setTimeout(hideNotification, 5000);
        }

        function hideNotification() {
            const notification = document.getElementById('live-notification');
            notification.classList.remove('show');
        }

        function showFlashMessages() {
            <?php if (session()->getFlashdata('success')): ?>
                showNotification('Berhasil!', '<?= esc(session()->getFlashdata('success')) ?>', 'success');
            <?php endif; ?>
            
            <?php if (session()->getFlashdata('error')): ?>
                showNotification('Error!', '<?= esc(session()->getFlashdata('error')) ?>', 'error');
            <?php endif; ?>
        }

        // Cleanup when page unloads
        window.addEventListener('beforeunload', function() {
            if (isScanning && html5QrcodeScanner) {
                html5QrcodeScanner.stop();
            }
            if (refreshInterval) {
                clearInterval(refreshInterval);
            }
        });

        // Handle visibility change (page focus/blur)
        document.addEventListener('visibilitychange', function() {
            if (document.hidden) {
                // Page is hidden, pause scanner
                if (isScanning) {
                    stopScanner();
                }
            } else {
                // Page is visible, resume if needed
                updateScannerStatus('ready', 'Halaman aktif. Scanner siap digunakan.');
            }
        });

        // Add keyboard shortcuts
        document.addEventListener('keydown', function(e) {
            if (e.ctrlKey && e.key === 's') {
                e.preventDefault();
                if (isScanning) {
                    stopScanner();
                } else {
                    startScanner();
                }
            }
            
            if (e.key === 'Escape' && isScanning) {
                stopScanner();
            }
        });
    </script>
</body>
</html>