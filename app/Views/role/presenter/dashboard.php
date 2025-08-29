<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Presenter Dashboard - SNIA</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
  <div class="card shadow p-4">
    <h3>🎤 Presenter Dashboard</h3>
    <p>Halo, <b><?= session('nama_lengkap') ?></b>. Silakan kelola data presentasi Anda.</p>

    <ul>
      <li><a href="<?= site_url('presenter/abstrak') ?>">Upload Abstrak</a></li>
      <li><a href="<?= site_url('presenter/abstrak/status') ?>">Status Abstrak</a></li>
      <li><a href="<?= site_url('presenter/pembayaran') ?>">Pembayaran</a></li>
      <li><a href="<?= site_url('presenter/absensi') ?>">Absensi</a></li>
      <li><a href="<?= site_url('presenter/dokumen/loa') ?>">Unduh LOA</a></li>
    </ul>

    <a href="<?= site_url('auth/logout') ?>" class="btn btn-danger">Logout</a>
  </div>
</div>
</body>
</html>
