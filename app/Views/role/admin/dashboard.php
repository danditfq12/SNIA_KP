<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Admin Dashboard - SNIA</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
  <div class="card shadow p-4">
    <h3>👨‍💼 Admin Dashboard</h3>
    <p>Selamat datang, <b><?= session('nama_lengkap') ?></b></p>

    <ul>
      <li><a href="<?= site_url('admin/users') ?>">Manajemen User</a></li>
      <li><a href="<?= site_url('admin/abstrak') ?>">Manajemen Abstrak</a></li>
      <li><a href="<?= site_url('admin/reviewer') ?>">Kelola Reviewer</a></li>
      <li><a href="<?= site_url('admin/pembayaran') ?>">Verifikasi Pembayaran</a></li>
      <li><a href="<?= site_url('admin/dokumen') ?>">Dokumen (LOA & Sertifikat)</a></li>
      <li><a href="<?= site_url('admin/laporan') ?>">Laporan</a></li>
    </ul>

    <a href="<?= site_url('auth/logout') ?>" class="btn btn-danger">Logout</a>
  </div>
</div>
</body>
</html>
