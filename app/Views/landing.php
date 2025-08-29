<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SNIA - Seminar Nasional Informatika</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="<?= base_url('/') ?>">SNIA</a>
            <div class="d-flex">
                <a href="<?= base_url('auth/login') ?>" class="btn btn-outline-light me-2">Login</a>
                <a href="<?= base_url('auth/register') ?>" class="btn btn-warning">Register</a>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="bg-light py-5 text-center">
        <div class="container">
            <h1 class="fw-bold">Seminar Nasional Informatika (SNIA)</h1>
            <p class="lead">Platform digital untuk registrasi, pengelolaan abstrak, pembayaran, absensi QR, hingga sertifikat.</p>
            <a href="<?= base_url('auth/register') ?>" class="btn btn-primary btn-lg mt-3">Daftar Sekarang</a>
        </div>
    </section>

    <!-- Info Section -->
    <section class="py-5">
        <div class="container">
            <div class="row text-center">
                <div class="col-md-4">
                    <h3>📑 Abstrak</h3>
                    <p>Upload abstrak & full paper dengan mudah, pantau status review secara real-time.</p>
                </div>
                <div class="col-md-4">
                    <h3>💳 Pembayaran</h3>
                    <p>Integrasi dengan payment gateway & voucher, pembayaran lebih cepat & aman.</p>
                </div>
                <div class="col-md-4">
                    <h3>🎓 Sertifikat</h3>
                    <p>Dapatkan LOA & sertifikat kehadiran otomatis setelah seminar selesai.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-light py-3 text-center">
        <p class="mb-0">© <?= date('Y') ?> SNIA. All rights reserved.</p>
    </footer>
</body>
</html>
