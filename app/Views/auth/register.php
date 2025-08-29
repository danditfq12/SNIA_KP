<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register - SNIA</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">

<div class="container d-flex justify-content-center align-items-center vh-100">
  <div class="card shadow-lg p-4" style="width: 400px;">
    <h3 class="text-center mb-3">Register</h3>

    <!-- Flash messages -->
    <?php if (session()->getFlashdata('success')): ?>
      <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
      <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
    <?php endif; ?>

    <form action="<?= site_url('auth/register') ?>" method="post" novalidate>
      <?= csrf_field() ?>

      <div class="mb-3">
        <label class="form-label">Nama Lengkap</label>
        <input
          type="text"
          name="nama_lengkap"
          class="form-control"
          placeholder="Nama lengkap"
          value="<?= old('nama_lengkap') ?>"
          required>
      </div>

      <div class="mb-3">
        <label class="form-label">Email</label>
        <input
          type="email"
          name="email"
          class="form-control"
          placeholder="Masukkan email"
          value="<?= old('email') ?>"
          required>
      </div>

      <div class="mb-3">
        <label class="form-label">Password</label>
        <input
          type="password"
          name="password"
          class="form-control"
          placeholder="Minimal 6 karakter"
          required>
      </div>

      <div class="mb-3">
        <label class="form-label">Konfirmasi Password</label>
        <!-- Ganti ke name="password2" agar cocok dengan rules controller -->
        <input
          type="password"
          name="password2"
          class="form-control"
          placeholder="Ulangi password"
          required>
      </div>

      <div class="mb-3">
        <label class="form-label">Daftar Sebagai</label>
        <select name="role" class="form-select" required>
          <option value="">-- Pilih Role --</option>
          <option value="presenter" <?= old('role')==='presenter'?'selected':''; ?>>Presenter</option>
          <option value="audience"  <?= old('role')==='audience'?'selected':''; ?>>Audience</option>
        </select>
      </div>

      <button type="submit" class="btn btn-success w-100">Daftar</button>
    </form>

    <div class="text-center mt-3">
      <small>Sudah punya akun? <a href="<?= site_url('auth/login') ?>">Login</a></small>
    </div>
  </div>
</div>

</body>
</html>
