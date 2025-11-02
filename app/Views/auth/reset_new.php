<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Password Baru - SNIA</title>
  <link rel="stylesheet" href="<?= base_url('assets/css/auth.css') ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-box">
    <div class="form-panel">
      <h2>Password Baru</h2>

      <?php if(session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
      <?php endif; ?>
      <?php if(session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
      <?php endif; ?>

      <form action="<?= site_url('auth/reset/new') ?>" method="post" class="auth-form" id="reset-new-form" novalidate>
        <?= csrf_field() ?>

        <div class="form-group">
          <label for="password">Kata Sandi Baru</label>
          <div class="input-group">
            <input id="password" type="password" name="password" placeholder="Minimal 6 karakter" required>
            <button type="button" class="toggle-password" aria-label="Tampilkan kata sandi"><i class="fa fa-eye"></i></button>
          </div>
        </div>

        <div class="form-group">
          <label for="password2">Konfirmasi Kata Sandi</label>
          <div class="input-group">
            <input id="password2" type="password" name="password2" placeholder="Ulangi kata sandi" required>
            <button type="button" class="toggle-password" aria-label="Tampilkan kata sandi"><i class="fa fa-eye"></i></button>
          </div>
        </div>

        <button type="submit" class="btn btn-primary">Simpan Password</button>

        <p class="redirect" style="margin-top:10px;">
          Selesai? <a href="<?= site_url('auth/login') ?>">Kembali ke Login</a>
        </p>
      </form>
    </div>
  </div>
</div>
<script src="<?= base_url('assets/js/auth.js') ?>"></script>
</body>
</html>