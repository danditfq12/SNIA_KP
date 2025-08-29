<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Reviewer Dashboard - SNIA</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container py-5">
  <div class="card shadow p-4">
    <h3>📝 Reviewer Dashboard</h3>
    <p>Halo, <b><?= session('nama_lengkap') ?></b>. Berikut tugas review Anda:</p>

    <ul>
      <li><a href="<?= site_url('reviewer/abstrak') ?>">Daftar Abstrak untuk Direview</a></li>
      <li><a href="<?= site_url('reviewer/riwayat') ?>">Riwayat Review</a></li>
    </ul>

    <a href="<?= site_url('auth/logout') ?>" class="btn btn-danger">Logout</a>
  </div>
</div>
</body>
</html>
