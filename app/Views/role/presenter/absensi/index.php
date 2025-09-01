<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - SNIA Presenter</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qr-scanner/1.4.2/qr-scanner.umd.min.js"></script>
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #64748b;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #06b6d4;
            --dark-color: #1e293b;
            --light-color: #f8fafc;
        }

        body {
            background: linear-gradient(135deg, var(--light-color) 0%, #e2e8f0 100%);
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }

        .sidebar {
            background: linear-gradient(180deg, var(--primary-color) 0%, #1e40af 100%);
            min-height: 100vh;
            box-shadow: 4px 0 20px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            z-index: 1000;
        }

        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            margin: 4px 0;
            border-radius: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .sidebar .nav-link:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            transform: translateX(5px);
        }

        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
            min-height: 100vh;
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--info-color));
            color: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(37, 99, 235, 0.3);
        }

        .card {
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            border: 1px solid rgba(255,255,255,0.2);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.15);
        }

        .card-header {
            padding: 20px;
            font-weight: 600;
        }

        .btn-custom {
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
        }

        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .btn-success-custom {
            background: linear-gradient(135deg, var(--success-color), #059669);
            color: white;
        }

        .btn-danger-custom {
            background: linear-gradient(135deg, var(--danger-color), #dc2626);
            color: white;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: 250px;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }

            .mobile-toggle {
                display: block;
                position: fixed;
                top: 20px;
                left: 20px;
                z-index: 1001;
            }
        }

        .mobile-toggle {
            display: none;
        }

        .qr-scanner-container {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            border: 2px dashed #e2e8f0;
            transition: all 0.3s ease;
        }

        .qr-scanner-container.active {
            border-color: var(--primary-color);
            background: rgba(37, 99, 235, 0.05);
        }

        .qr-scanner-container video {
            border-radius: 8px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .attendance-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 16px;
            border-radius: 12px;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .attendance-present {
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
            border: 2px solid rgba(16, 185, 129, 0.3);
        }

        .attendance-absent {
            background: rgba(251, 191, 36, 0.1);
            color: #d97706;
            border: 2px solid rgba(251, 191, 36, 0.3);
        }

        .qr-format-display {
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            border-radius: 8px;
            padding: 15px;
            border-left: 4px solid var(--primary-color);
        }

        .history-item {
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .history-item:hover {
            background: rgba(37, 99, 235, 0.05);
            margin: 0 -15px;
            padding: 12px 15px;
            border-radius: 8px;
        }

        .history-item:last-child {
            border-bottom: none;
        }

        .input-group .form-control {
            border-radius: 8px 0 0 8px;
        }

        .input-group .btn {
            border-radius: 0 8px 8px 0;
        }
    </style>
</head>
<body>
    <!-- Mobile Toggle Button -->
    <button class="btn btn-primary mobile-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="p-4 text-center">
            <h4 class="text-white mb-0">
                <i class="fas fa-microphone-alt me-2"></i>
                SNIA Presenter
            </h4>
            <small class="text-white-50">Dashboard</small>
        </div>
        
        <nav class="nav flex-column px-3">
            <a class="nav-link" href="<?= site_url('presenter/dashboard') ?>">
                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
            </a>
            <a class="nav-link" href="<?= site_url('presenter/events') ?>">
                <i class="fas fa-calendar me-2"></i> Events
            </a>
            <a class="nav-link" href="<?= site_url('presenter/abstrak') ?>">
                <i class="fas fa-file-alt me-2"></i> My Abstracts
            </a>
            <a class="nav-link" href="<?= site_url('presenter/pembayaran') ?>">
                <i class="fas fa-credit-card me-2"></i> Payments
            </a>
            <a class="nav-link active" href="<?= site_url('presenter/absensi') ?>">
                <i class="fas fa-qrcode me-2"></i> Attendance
            </a>
            <a class="nav-link" href="<?= site_url('presenter/dokumen/loa') ?>">
                <i class="fas fa-file-contract me-2"></i> LOA
            </a>
            <a class="nav-link" href="<?= site_url('presenter/dokumen/sertifikat') ?>">
                <i class="fas fa-certificate me-2"></i> Certificate
            </a>
            <hr class="my-3" style="border-color: rgba(255,255,255,0.2);">
            <a class="nav-link" href="<?= site_url('profile') ?>">
                <i class="fas fa-user me-2"></i> Profile
            </a>
            <a class="nav-link text-warning" href="<?= site_url('auth/logout') ?>">
                <i class="fas fa-sign-out-alt me-2"></i> Logout
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Page Header -->
        <div class="page-header animate__animated animate__fadeInDown">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="mb-2">
                        <i class="fas fa-qrcode me-2"></i>
                        Event Attendance
                    </h2>
                    <p class="mb-0 opacity-90">
                        Scan QR code to mark your attendance at SNIA events and track your participation.
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="text-white-50 small">Today's Status</div>
                    <div class="fw-bold">
                        <?php
                        $today = date('Y-m-d');
                        $todayAttendance = null;
                        if (isset($absensi)) {
                            foreach ($absensi as $abs) {
                                if (date('Y-m-d', strtotime($abs['waktu_scan'])) == $today) {
                                    $todayAttendance = $abs;
                                    break;
                                }
                            }
                        }
                        ?>
                        
                        <?php if ($todayAttendance): ?>
                            <i class="fas fa-check-circle text-success me-1"></i>Present
                        <?php else: ?>
                            <i class="fas fa-clock text-warning me-1"></i>Not Scanned
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Row -->
        <div class="row">
            <!-- QR Scanner Section -->
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-qrcode me-2"></i>Scan QR Code Absensi
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="qr-scanner-container text-center" id="qrScannerContainer">
                            <div id="qr-reader"></div>
                            <div id="qr-reader-results" class="mt-3"></div>
                        </div>
                        
                        <div class="text-center mb-4">
                            <button id="startScan" class="btn btn-success-custom btn-custom me-2">
                                <i class="fas fa-camera me-1"></i>Mulai Scan
                            </button>
                            <button id="stopScan" class="btn btn-danger-custom btn-custom" style="display: none;">
                                <i class="fas fa-stop me-1"></i>Stop Scan
                            </button>
                        </div>

                        <!-- Manual Input -->
                        <div class="mt-4">
                            <h6 class="mb-3">
                                <i class="fas fa-keyboard me-2"></i>Atau masukkan kode manual:
                            </h6>
                            <form id="manualForm">
                                <div class="input-group">
                                    <input type="text" id="manualQrCode" class="form-control" 
                                           placeholder="Masukkan kode QR (contoh: SNIA_20241201)">
                                    <button type="submit" class="btn btn-outline-primary">
                                        <i class="fas fa-check me-1"></i>Submit
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sidebar Info -->
            <div class="col-lg-4">
                <!-- Today's Attendance Status -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-calendar-day me-2"></i>Status Absensi Hari Ini
                        </h6>
                    </div>
                    <div class="card-body">
                        <div id="attendanceStatus">
                            <?php if ($todayAttendance): ?>
                                <div class="attendance-badge attendance-present">
                                    <i class="fas fa-check-circle"></i>
                                    <div>
                                        <div class="fw-bold">Sudah Absen</div>
                                        <small><?= date('H:i', strtotime($todayAttendance['waktu_scan'])) ?> WIB</small>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="attendance-badge attendance-absent">
                                    <i class="fas fa-clock"></i>
                                    <div>
                                        <div class="fw-bold">Belum Absen</div>
                                        <small>Silakan scan QR code</small>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mt-3">
                            <h6><i class="fas fa-info-circle me-1"></i>Format QR Code:</h6>
                            <div class="qr-format-display">
                                <code class="text-primary fw-bold">SNIA_<?= date('Ymd') ?></code>
                                <div class="mt-2">
                                    <small class="text-muted">
                                        <i class="fas fa-exclamation-triangle me-1"></i>
                                        QR code berubah setiap hari
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Attendance History -->
                <div class="card shadow-sm">
                    <div class="card-header bg-secondary text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-history me-2"></i>Riwayat Absensi
                        </h6>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($absensi)): ?>
                            <div class="overflow-auto" style="max-height: 300px;">
                                <?php foreach ($absensi as $abs): ?>
                                    <div class="history-item">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="fw-bold"><?= date('d/m/Y', strtotime($abs['waktu_scan'])) ?></div>
                                                <small class="text-muted">
                                                    <i class="fas fa-clock me-1"></i>
                                                    <?= date('H:i:s', strtotime($abs['waktu_scan'])) ?> WIB
                                                </small>
                                            </div>
                                            <span class="badge bg-<?= $abs['status'] == 'hadir' ? 'success' : 'danger' ?>">
                                                <i class="fas fa-<?= $abs['status'] == 'hadir' ? 'check' : 'times' ?> me-1"></i>
                                                <?= ucfirst($abs['status']) ?>
                                            </span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted">Belum Ada Riwayat</h6>
                                <p class="text-muted small">Riwayat absensi akan muncul di sini</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('show');
        }

        let qrScanner = null;
        const videoElem = document.createElement('video');

        document.getElementById('startScan').addEventListener('click', startScan);
        document.getElementById('stopScan').addEventListener('click', stopScan);
        document.getElementById('manualForm').addEventListener('submit', handleManualSubmit);

        function startScan() {
            const qrReaderDiv = document.getElementById('qr-reader');
            const container = document.getElementById('qrScannerContainer');
            
            qrReaderDiv.innerHTML = '<video id="qr-video" style="width: 100%; max-width: 400px;"></video>';
            container.classList.add('active');
            
            const video = document.getElementById('qr-video');
            
            qrScanner = new QrScanner(
                video,
                result => onScanSuccess(result.data),
                {
                    onDecodeError: err => {
                        console.log('Decode error:', err);
                    },
                    preferredCamera: 'environment',
                    highlightScanRegion: true,
                    highlightCodeOutline: true,
                }
            );

            qrScanner.start().then(() => {
                document.getElementById('startScan').style.display = 'none';
                document.getElementById('stopScan').style.display = 'inline-block';
                console.log('QR Scanner started');
            }).catch(err => {
                console.error('Error starting QR scanner:', err);
                showAlert('Error starting camera: ' + err.message, 'danger');
            });
        }

        function stopScan() {
            if (qrScanner) {
                qrScanner.stop();
                qrScanner.destroy();
                qrScanner = null;
            }
            
            const container = document.getElementById('qrScannerContainer');
            container.classList.remove('active');
            document.getElementById('qr-reader').innerHTML = '<p class="text-muted"><i class="fas fa-camera-retro fa-2x mb-2"></i><br>Scanner dihentikan</p>';
            document.getElementById('startScan').style.display = 'inline-block';
            document.getElementById('stopScan').style.display = 'none';
        }

        function onScanSuccess(decodedText) {
            console.log('QR Code detected:', decodedText);
            stopScan();
            processAbsensi(decodedText);
        }

        function handleManualSubmit(e) {
            e.preventDefault();
            const qrCode = document.getElementById('manualQrCode').value;
            if (qrCode) {
                processAbsensi(qrCode);
            }
        }

        function processAbsensi(qrCode) {
            showAlert('<i class="fas fa-spinner fa-spin me-2"></i>Memproses absensi...', 'info');
            
            fetch('<?= site_url('presenter/absensi/scan') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'qr_code=' + encodeURIComponent(qrCode)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('<i class="fas fa-check-circle me-2"></i>' + data.message, 'success');
                    updateAttendanceStatus();
                    document.getElementById('manualQrCode').value = '';
                } else {
                    showAlert('<i class="fas fa-exclamation-triangle me-2"></i>' + data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('<i class="fas fa-times-circle me-2"></i>Terjadi kesalahan saat memproses absensi', 'danger');
            });
        }

        function showAlert(message, type) {
            const resultsDiv = document.getElementById('qr-reader-results');
            resultsDiv.innerHTML = `
                <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            `;
            
            // Auto dismiss after 5 seconds
            setTimeout(() => {
                const alert = resultsDiv.querySelector('.alert');
                if (alert) {
                    alert.remove();
                }
            }, 5000);
        }

        function updateAttendanceStatus() {
            const statusDiv = document.getElementById('attendanceStatus');
            const now = new Date();
            const timeString = now.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
            
            statusDiv.innerHTML = `
                <div class="attendance-badge attendance-present">
                    <i class="fas fa-check-circle"></i>
                    <div>
                        <div class="fw-bold">Sudah Absen</div>
                        <small>${timeString} WIB</small>
                    </div>
                </div>
            `;
            
            // Refresh page after 2 seconds to update history
            setTimeout(() => {
                location.reload();
            }, 2000);
        }

        // Check camera permissions on page load
        window.addEventListener('load', function() {
            if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                console.log('Camera API supported');
            } else {
                showAlert('<i class="fas fa-exclamation-triangle me-2"></i>Browser Anda tidak mendukung akses kamera', 'warning');
            }
        });
    </script>
</body>
</html>