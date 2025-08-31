<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Letter of Acceptance - Presenter Dashboard</title>
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
            <div class="card-header bg-success text-white">
                <h5 class="mb-0">
                    <i class="fas fa-file-alt me-2"></i>Letter of Acceptance (LOA)
                </h5>
            </div>
            <div class="card-body">
                <?php if ($hasVerifiedPayment): ?>
                    <?php if (!empty($loaDokumen)): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            LOA Anda telah tersedia untuk diunduh
                        </div>

                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Dokumen</th>
                                        <th>Syarat</th>
                                        <th>Tanggal Upload</th>
                                        <th>Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($loaDokumen as $dok): ?>
                                        <tr>
                                            <td>
                                                <i class="fas fa-file-pdf text-danger me-2"></i>
                                                Letter of Acceptance
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?= $dok['syarat'] ?></span>
                                            </td>
                                            <td><?= date('d/m/Y H:i', strtotime($dok['uploaded_at'])) ?></td>
                                            <td>
                                                <a href="<?= site_url('presenter/dokumen/loa/download/' . $dok['file_path']) ?>" 
                                                   class="btn btn-sm btn-success">
                                                    <i class="fas fa-download me-1"></i>Download
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            LOA sedang diproses oleh admin dan akan tersedia segera
                        </div>

                        <div class="text-center py-4">
                            <i class="fas fa-hourglass-half fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">LOA Dalam Proses</h5>
                            <p class="text-muted">
                                Admin sedang memproses Letter of Acceptance Anda.<br>
                                Dokumen akan muncul di sini setelah diproses.
                            </p>
                            <button class="btn btn-primary" onclick="location.reload()">
                                <i class="fas fa-refresh me-1"></i>Refresh Halaman
                            </button>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-lock me-2"></i>
                        Anda harus menyelesaikan pembayaran terlebih dahulu untuk mengakses LOA
                    </div>
                    <a href="<?= site_url('presenter/pembayaran') ?>" class="btn btn-warning">
                        <i class="fas fa-credit-card me-1"></i>Lakukan Pembayaran
                    </a>
                <?php endif; ?>
            </div>
        </div>

        <!-- Information about LOA -->
        <div class="card shadow-sm mt-4">
            <div class="card-header bg-info text-white">
                <h6 class="mb-0">
                    <i class="fas fa-question-circle me-2"></i>Tentang Letter of Acceptance
                </h6>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Apa itu LOA?</h6>
                        <p class="text-muted">
                            Letter of Acceptance adalah surat resmi yang menyatakan bahwa abstrak Anda 
                            telah diterima dan Anda terdaftar sebagai presenter di event SNIA.
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h6>Kegunaan LOA:</h6>
                        <ul class="text-muted">
                            <li>Bukti partisipasi sebagai presenter</li>
                            <li>Untuk keperluan administrasi institusi</li>
                            <li>Referensi untuk CV akademik</li>
                            <li>Dokumentasi presentasi ilmiah</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh every 2 minutes to check for new LOA
        setInterval(function() {
            <?php if (empty($loaDokumen) && $hasVerifiedPayment): ?>
                location.reload();
            <?php endif; ?>
        }, 120000);
    </script>
</body>
</html>