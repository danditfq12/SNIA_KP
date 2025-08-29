<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Dashboard Admin - SNIA</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
  <div class="container-fluid">
    <a class="navbar-brand" href="<?= base_url('/admin/dashboard') ?>">SNIA Admin</a>
    <div class="d-flex">
      <a href="<?= base_url('/auth/logout') ?>" class="btn btn-outline-light btn-sm">Logout</a>
    </div>
  </div>
</nav>

<div class="container mt-4">
  <h2 class="mb-4">Dashboard Admin</h2>

  <?php if(session()->getFlashdata('success')): ?>
    <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
  <?php endif; ?>
  <?php if(session()->getFlashdata('error')): ?>
    <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
  <?php endif; ?>

  <div class="row">
    <!-- Card User -->
    <div class="col-md-3 mb-3">
      <div class="card text-center shadow-sm">
        <div class="card-body">
          <h5 class="card-title">Users</h5>
          <p class="card-text">Kelola data pengguna</p>
          <a href="<?= base_url('/admin/users') ?>" class="btn btn-primary btn-sm">Kelola</a>
        </div>
      </div>
    </div>

    <!-- Card Abstrak -->
    <div class="col-md-3 mb-3">
      <div class="card text-center shadow-sm">
        <div class="card-body">
          <h5 class="card-title">Abstrak</h5>
          <p class="card-text">Manajemen abstrak presenter</p>
          <a href="<?= base_url('/admin/abstrak') ?>" class="btn btn-primary btn-sm">Kelola</a>
        </div>
      </div>
    </div>

    <!-- Card Pembayaran -->
    <div class="col-md-3 mb-3">
      <div class="card text-center shadow-sm">
        <div class="card-body">
          <h5 class="card-title">Pembayaran</h5>
          <p class="card-text">Verifikasi pembayaran</p>
          <a href="<?= base_url('/admin/pembayaran') ?>" class="btn btn-primary btn-sm">Kelola</a>
        </div>
      </div>
    </div>

    <!-- Card Absensi -->
    <div class="col-md-3 mb-3">
      <div class="card text-center shadow-sm">
        <div class="card-body">
          <h5 class="card-title">Absensi</h5>
          <p class="card-text">Data kehadiran peserta</p>
          <a href="<?= base_url('/admin/absensi') ?>" class="btn btn-primary btn-sm">Kelola</a>
        </div>
      </div>
    </div>

    <!-- Card Dokumen -->
    <div class="col-md-3 mb-3">
      <div class="card text-center shadow-sm">
        <div class="card-body">
          <h5 class="card-title">Dokumen</h5>
          <p class="card-text">LOA & Sertifikat</p>
          <a href="<?= base_url('/admin/dokumen') ?>" class="btn btn-primary btn-sm">Kelola</a>
        </div>
      </div>
    </div>

    <!-- Card Reviewer -->
    <div class="col-md-3 mb-3">
      <div class="card text-center shadow-sm">
        <div class="card-body">
          <h5 class="card-title">Reviewer</h5>
          <p class="card-text">Kelola reviewer & kategori</p>
          <a href="<?= base_url('/admin/reviewer') ?>" class="btn btn-primary btn-sm">Kelola</a>
        </div>
      </div>
    </div>

    <!-- Card Voucher -->
    <div class="col-md-3 mb-3">
      <div class="card text-center shadow-sm">
        <div class="card-body">
          <h5 class="card-title">Voucher</h5>
          <p class="card-text">Kelola kode diskon</p>
          <a href="<?= base_url('/admin/voucher') ?>" class="btn btn-primary btn-sm">Kelola</a>
        </div>
      </div>
    </div>

    <!-- Card Laporan -->
    <div class="col-md-3 mb-3">
      <div class="card text-center shadow-sm">
        <div class="card-body">
          <h5 class="card-title">Laporan</h5>
          <p class="card-text">Export laporan sistem</p>
          <a href="<?= base_url('/admin/laporan') ?>" class="btn btn-primary btn-sm">Lihat</a>
        </div>
      </div>
    </div>

  </div>
</div>

</body>
</html>
