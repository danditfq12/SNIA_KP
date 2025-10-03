<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Lupa Password - SNIA</title>
  <link rel="stylesheet" href="<?= base_url('assets/css/auth.css') ?>">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-box">
    <div class="form-panel">
      <h2>Lupa Password</h2>

      <?php if(session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
      <?php endif; ?>
      <?php if(session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
      <?php endif; ?>

      <form action="<?= site_url('auth/forgot-password') ?>" method="post" class="auth-form" id="forgot-form" novalidate>
        <?= csrf_field() ?>
        <div class="form-group">
          <label for="email">Masukkan Email Anda</label>
          <input id="email" type="email" name="email" placeholder="Email terdaftar" value="<?= old('email') ?>" required>
        </div>
        <button type="submit" class="btn btn-primary">Kirim Kode</button>
      </form>

      <p class="redirect">Kembali ke <a href="<?= site_url('auth/login') ?>">Login</a></p>
    </div>
  </div>
</div>
<script src="<?= base_url('assets/js/auth.js') ?>"></script>
</body>
</html>