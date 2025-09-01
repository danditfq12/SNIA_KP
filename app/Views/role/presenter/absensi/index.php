<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Absensi - Presenter Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qr-scanner/1.4.2/qr-scanner.umd.min.js"></script>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?= site_url('presenter/dashboard') ?>">
                <i class="fas fa-microphone me-2"></i>SNIA Presenter
            </a>
            <div class="navbar-nav ms-auto">
                <a href="<?= site_url('presenter/dashboard') ?>" class="nav-link">
                    <i class="fas fa-home me-1"></i>Dashboard
                </a>
                <a href="<?= site_url('auth/logout') ?>" class="btn btn-outline-light btn-sm ms-2">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row">
            <div class="col-md-8">
                <!-- QR Scanner -->
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-qrcode me-2"></i>Scan QR Code Absensi
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center mb-4">
                            <div id="qr-reader" style="width: 100%; max-width: 500px; margin: 0 auto;"></div>
                            <div id="qr-reader-results" class="mt-3"></div>
                        </div>
                        
                        <div class="text-center">
                            <button id="startScan" class="btn btn-success me-2">
                                <i class="fas fa-camera me-1"></i>Mulai Scan
                            </button>
                            <button id="stopScan" class="btn btn-danger" style="display: none;">
                                <i class="fas fa-stop me-1"></i>Stop Scan
                            </button>
                        </div>

                        <!-- Manual Input -->
                        <div class="mt-4">
                            <h6>Atau masukkan kode manual:</h6>
                            <form id="manualForm">
                                <div class="input-group">
                                    <input type="text" id="manualQrCode" class="form-control" placeholder="Masukkan kode QR">
                                    <button type="submit" class="btn btn-outline-primary">
                                        <i class="fas fa-check me-1"></i>Submit
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Attendance Status -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>Status Absensi Hari Ini
                        </h6>
                    </div>
                    <div class="card-body">
                        <div id="attendanceStatus">
                            <?php
                            $today = date('Y-m-d');
                            $todayAttendance = null;
                            foreach ($absensi as $abs) {
                                if (date('Y-m-d', strtotime($abs['waktu_scan'])) == $today) {
                                    $todayAttendance = $abs;
                                    break;
                                }
                            }
                            ?>
                            
                            <?php if ($todayAttendance): ?>
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <strong>Sudah Absen</strong><br>
                                    <small>Waktu: <?= date('d/m/Y H:i', strtotime($todayAttendance['waktu_scan'])) ?></small>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-clock me-2"></i>
                                    <strong>Belum Absen</strong><br>
                                    <small>Silakan scan QR code untuk absensi</small>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="mt-3">
                            <h6>Format QR Code:</h6>
                            <div class="bg-light p-2 rounded">
                                <code>SNIA_<?= date('Ymd') ?></code>
                            </div>
                            <small class="text-muted">QR code berubah setiap hari</small>
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
                                    <div class="border-bottom pb-2 mb-2">
                                        <div class="d-flex justify-content-between">
                                            <strong><?= date('d/m/Y', strtotime($abs['waktu_scan'])) ?></strong>
                                            <span class="badge bg-<?= $abs['status'] == 'hadir' ? 'success' : 'danger' ?>">
                                                <?= ucfirst($abs['status']) ?>
                                            </span>
                                        </div>
                                        <small class="text-muted">
                                            <i class="fas fa-clock me-1"></i>
                                            <?= date('H:i:s', strtotime($abs['waktu_scan'])) ?>
                                        </small>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center text-muted">
                                <i class="fas fa-calendar-times fa-2x mb-2"></i>
                                <p>Belum ada riwayat absensi</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let qrScanner = null;
        const videoElem = document.createElement('video');

        document.getElementById('startScan').addEventListener('click', startScan);
        document.getElementById('stopScan').addEventListener('click', stopScan);
        document.getElementById('manualForm').addEventListener('submit', handleManualSubmit);

        function startScan() {
            const qrReaderDiv = document.getElementById('qr-reader');
            qrReaderDiv.innerHTML = '<video id="qr-video" style="width: 100%; max-width: 400px;"></video>';
            
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
            
            document.getElementById('qr-reader').innerHTML = '<p class="text-muted">Scanner dihentikan</p>';
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
            showAlert('Memproses absensi...', 'info');
            
            fetch('<?= site_url('presenter/absensi/scan') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'qr_code=' + encodeURIComponent(qrCode)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert(data.message, 'success');
                    // Update attendance status
                    updateAttendanceStatus();
                } else {
                    showAlert(data.message, 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Terjadi kesalahan saat memproses absensi', 'danger');
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
        }

        function updateAttendanceStatus() {
            const statusDiv = document.getElementById('attendanceStatus');
            statusDiv.innerHTML = `
                <div class="alert alert-success">
                    <i class="fas fa-check-circle me-2"></i>
                    <strong>Sudah Absen</strong><br>
                    <small>Waktu: ${new Date().toLocaleString('id-ID')}</small>
                </div>
            `;
        }

        // Check camera permissions on page load
        window.addEventListener('load', function() {
            if (navigator.mediaDevices && navigator.mediaDevices.getUserMedia) {
                console.log('Camera API supported');
            } else {
                showAlert('Browser Anda tidak mendukung akses kamera', 'warning');
            }
        });
    </script>
</body>
</html>