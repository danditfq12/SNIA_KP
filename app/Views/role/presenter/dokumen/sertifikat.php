<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate - SNIA Presenter</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
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

        .btn-primary-custom {
            background: linear-gradient(135deg, var(--primary-color), var(--info-color));
            color: white;
        }

        .btn-success-custom {
            background: linear-gradient(135deg, var(--success-color), #059669);
            color: white;
        }

        .btn-warning-custom {
            background: linear-gradient(135deg, var(--warning-color), #d97706);
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

        .certificate-card {
            background: white;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            margin-bottom: 20px;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }

        .certificate-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.15);
            border-color: var(--warning-color);
        }

        .certificate-available {
            border-color: var(--success-color);
        }

        .document-icon {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.8;
        }

        .status-indicator {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .status-available {
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .status-processing {
            background: rgba(59, 130, 246, 0.1);
            color: #2563eb;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }

        .status-locked {
            background: rgba(251, 191, 36, 0.1);
            color: #d97706;
            border: 1px solid rgba(251, 191, 36, 0.3);
        }

        .requirements-list {
            background: #f8fafc;
            border-left: 4px solid var(--primary-color);
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
        }

        .requirement-item {
            display: flex;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .requirement-item:last-child {
            border-bottom: none;
        }

        .requirement-item i {
            width: 20px;
            margin-right: 10px;
        }

        .list-group-item {
            border: none;
            padding: 10px 0;
            background: transparent;
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
            <a class="nav-link" href="<?= site_url('presenter/absensi') ?>">
                <i class="fas fa-qrcode me-2"></i> Attendance
            </a>
            <a class="nav-link" href="<?= site_url('presenter/dokumen/loa') ?>">
                <i class="fas fa-file-contract me-2"></i> LOA
            </a>
            <a class="nav-link active" href="<?= site_url('presenter/dokumen/sertifikat') ?>">
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
                        <i class="fas fa-certificate me-2"></i>
                        Certificate of Presentation
                    </h2>
                    <p class="mb-0 opacity-90">
                        Download your official certificate after completing your presentation.
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="text-white-50 small">Certificate Status</div>
                    <div class="fw-bold">
                        <?php if ($hasAttended): ?>
                            <?php if (!empty($sertifikat)): ?>
                                <i class="fas fa-check-circle text-success me-1"></i>Available
                            <?php else: ?>
                                <i class="fas fa-clock text-warning me-1"></i>Processing
                            <?php endif; ?>
                        <?php else: ?>
                            <i class="fas fa-calendar-times text-warning me-1"></i>Attendance Required
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Row -->
        <div class="row">
            <!-- Main Certificate Content -->
            <div class="col-lg-8">
                <div class="card shadow-sm">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="fas fa-certificate me-2"></i>Sertifikat Presenter
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($hasAttended): ?>
                            <?php if (!empty($sertifikat)): ?>
                                <div class="status-indicator status-available">
                                    <i class="fas fa-check-circle"></i>
                                    Sertifikat tersedia untuk diunduh
                                </div>

                                <div class="row">
                                    <?php foreach ($sertifikat as $cert): ?>
                                        <div class="col-md-6 mb-4">
                                            <div class="certificate-card certificate-available">
                                                <div class="document-icon">
                                                    <i class="fas fa-certificate text-warning"></i>
                                                </div>
                                                <h6 class="fw-bold text-dark">Sertifikat Presenter</h6>
                                                <div class="mb-3">
                                                    <small class="text-muted">
                                                        <i class="fas fa-tag me-1"></i>Syarat: <?= esc($cert['syarat']) ?><br>
                                                        <i class="fas fa-calendar me-1"></i>Diterbitkan: <?= date('d/m/Y', strtotime($cert['uploaded_at'])) ?>
                                                    </small>
                                                </div>
                                                <a href="<?= site_url('presenter/dokumen/sertifikat/download/' . $cert['file_path']) ?>" 
                                                   class="btn btn-warning-custom btn-custom">
                                                    <i class="fas fa-download me-1"></i>Download Sertifikat
                                                </a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="certificate-card">
                                    <div class="status-indicator status-processing">
                                        <i class="fas fa-cog fa-spin"></i>
                                        Sertifikat sedang diproses
                                    </div>
                                    
                                    <div class="document-icon">
                                        <i class="fas fa-cog fa-spin text-primary"></i>
                                    </div>
                                    <h5 class="text-primary">Sertifikat Dalam Proses</h5>
                                    <p class="text-muted mb-4">
                                        Admin sedang memproses sertifikat Anda.<br>
                                        Sertifikat akan muncul di sini setelah event selesai dan diproses.
                                    </p>
                                    <button class="btn btn-primary-custom btn-custom" onclick="location.reload()">
                                        <i class="fas fa-refresh me-1"></i>Refresh Halaman
                                    </button>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <div class="certificate-card">
                                <div class="status-indicator status-locked">
                                    <i class="fas fa-calendar-times"></i>
                                    Absensi diperlukan
                                </div>
                                
                                <div class="document-icon">
                                    <i class="fas fa-calendar-check text-warning"></i>
                                </div>
                                <h5 class="text-warning">Absensi Event Diperlukan</h5>
                                <p class="text-muted mb-4">
                                    Anda harus hadir di event dan melakukan presentasi untuk mendapatkan sertifikat presenter.
                                </p>
                                <div class="text-start text-muted small mb-4">
                                    <div class="mb-2">
                                        <i class="fas fa-check-circle me-2 text-primary"></i>1. Hadir di event
                                    </div>
                                    <div class="mb-2">
                                        <i class="fas fa-qrcode me-2 text-primary"></i>2. Melakukan absensi dengan scan QR code
                                    </div>
                                    <div>
                                        <i class="fas fa-microphone me-2 text-primary"></i>3. Melakukan presentasi
                                    </div>
                                </div>
                                <a href="<?= site_url('presenter/absensi') ?>" class="btn btn-primary-custom btn-custom">
                                    <i class="fas fa-qrcode me-1"></i>Ke Halaman Absensi
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar Information -->
            <div class="col-lg-4">
                <!-- Certificate Information -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>Informasi Sertifikat
                        </h6>
                    </div>
                    <div class="card-body">
                        <h6 class="text-primary">Waktu Penerbitaan:</h6>
                        <p class="text-muted small mb-3">
                            Sertifikat akan diterbitkan maksimal 7 hari kerja setelah event selesai.
                        </p>
                        
                        <h6 class="text-primary">Format Sertifikat:</h6>
                        <p class="text-muted small">
                            Sertifikat dalam format PDF dengan tanda tangan digital dan QR code verifikasi.
                        </p>
                    </div>
                </div>

                <!-- Certificate Requirements -->
                <div class="card shadow-sm">
                    <div class="card-header bg-warning text-dark">
                        <h6 class="mb-0">
                            <i class="fas fa-list-check me-2"></i>Persyaratan Sertifikat
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="requirements-list">
                            <div class="requirement-item">
                                <i class="fas fa-check text-success"></i>
                                <div>
                                    <div class="fw-bold small">Abstrak Diterima</div>
                                    <small class="text-muted">Harus disetujui oleh reviewer</small>
                                </div>
                            </div>
                            <div class="requirement-item">
                                <i class="fas fa-check text-success"></i>
                                <div>
                                    <div class="fw-bold small">Pembayaran Terverifikasi</div>
                                    <small class="text-muted">Biaya registrasi selesai</small>
                                </div>
                            </div>
                            <div class="requirement-item">
                                <i class="fas fa-<?= $hasAttended ? 'check text-success' : 'times text-danger' ?>"></i>
                                <div>
                                    <div class="fw-bold small">Kehadiran Event</div>
                                    <small class="text-muted">Harus check-in di event</small>
                                </div>
                            </div>
                            <div class="requirement-item">
                                <i class="fas fa-microphone text-primary"></i>
                                <div>
                                    <div class="fw-bold small">Selesaikan Presentasi</div>
                                    <small class="text-muted">Lakukan presentasi Anda</small>
                                </div>
                            </div>
                        </div>
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

        // Auto-refresh every 5 minutes to check for new certificates
        setInterval(function() {
            <?php if (empty($sertifikat) && $hasAttended): ?>
                console.log('Auto-refreshing for certificate updates...');
                location.reload();
            <?php endif; ?>
        }, 300000);
    </script>
</body>
</html>