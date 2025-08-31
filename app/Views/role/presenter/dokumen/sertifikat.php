<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Sertifikat - Presenter Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
        <div class="card shadow-sm">
            <div class="card-header bg-warning text-dark">
                <h5 class="mb-0">
                    <i class="fas fa-certificate me-2"></i>Sertifikat Presenter
                </h5>
            </div>
            <div class="card-body">
                <?php if ($hasAttended): ?>
                    <?php if (!empty($sertifikat)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            Sertifikat Anda telah tersedia untuk diunduh
                        </div>

                        <div class="row">
                            <?php foreach ($sertifikat as $cert): ?>
                                <div class="col-md-6 mb-4">
                                    <div class="card border-warning h-100">
                                        <div class="card-body text-center">
                                            <i class="fas fa-certificate fa-4x text-warning mb-3"></i>
                                            <h6 class="card-title">Sertifikat Presenter</h6>
                                            <p class="card-text">
                                                <small class="text-muted">
                                                    Syarat: <?= $cert['syarat'] ?><br>
                                                    Diterbitkan: <?= date('d/m/Y', strtotime($cert['uploaded_at'])) ?>
                                                </small>
                                            </p>
                                            <a href="<?= site_url('presenter/dokumen/sertifikat/download/' . $cert['file_path']) ?>" 
                                               class="btn btn-warning">
                                                <i class="fas fa-download me-1"></i>Download Sertifikat
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Sertifikat sedang diproses dan akan tersedia segera
                        </div>

                        <div class="text-center py-4">
                            <i class="fas fa-cog fa-spin fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">Sertifikat Dalam Proses</h5>
                            <p class="text-muted">
                                Admin sedang memproses sertifikat Anda.<br>
                                Sertifikat akan muncul di sini setelah event selesai dan diproses.
                            </p>
                            <button class="btn btn-primary" onclick="location.reload()">
                                <i class="fas fa-refresh me-1"></i>Refresh Halaman
                            </button>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Anda harus hadir di event untuk mendapatkan sertifikat presenter
                    </div>
                    
                    <div class="text-center py-4">
                        <i class="fas fa-calendar-check fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Absensi Diperlukan</h5>
                        <p class="text-muted">
                            Untuk mendapatkan sertifikat presenter, Anda harus:<br>
                            1. Hadir di event<br>
                            2. Melakukan absensi dengan scan QR code<br>
                            3. Melakukan presentasi
                        </p>
                        <a href="<?= site_url('presenter/absensi') ?>" class="btn btn-primary">
                            <i class="fas fa-qrcode me-1"></i>Ke Halaman Absensi
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Certificate Information -->
        <div class="card shadow-sm mt-4">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>Informasi Sertifikat
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Persyaratan Sertifikat:</h6>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item">
                                <i class="fas fa-check text-success me-2"></i>
                                Abstrak diterima
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-check text-success me-2"></i>
                                Pembayaran terverifikasi
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-<?= $hasAttended ? 'check text-success' : 'times text-danger' ?> me-2"></i>
                                Hadir di event
                            </li>
                            <li class="list-group-item">
                                <i class="fas fa-microphone me-2"></i>
                                Melakukan presentasi
                            </li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6>Waktu Penerbitaan:</h6>
                        <p class="text-muted">
                            Sertifikat akan diterbitkan maksimal 7 hari kerja setelah event selesai.
                        </p>
                        
                        <h6>Format Sertifikat:</h6>
                        <p class="text-muted">
                            Sertifikat dalam format PDF dengan tanda tangan digital dan QR code verifikasi.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh every 5 minutes to check for new certificates
        setInterval(function() {
            <?php if (empty($sertifikat) && $hasAttended): ?>
                location.reload();
            <?php endif; ?>
        }, 300000);
    </script>
</body>
</html>