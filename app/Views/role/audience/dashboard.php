<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Audience Dashboard - SNIA</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
  <div class="card shadow p-4">
    <h3>🙋 Audience Dashboard</h3>
    <p>Halo, <b><?= session('nama_lengkap') ?></b>. Terima kasih sudah bergabung sebagai peserta.</p>

    <ul>
      <li><a href="<?= site_url('audience/pembayaran') ?>">Pembayaran</a></li>
      <li><a href="<?= site_url('audience/absensi') ?>">Absensi</a></li>
      <li><a href="<?= site_url('audience/dokumen/sertifikat') ?>">Unduh Sertifikat</a></li>
    </ul>

    <a href="<?= site_url('auth/logout') ?>" class="btn btn-danger">Logout</a>
  </div>
</div>
</body>
</html>
