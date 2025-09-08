<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Masuk - SNIA</title>

  <link rel="stylesheet" href="<?= base_url('assets/css/auth.css') ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
  <div class="auth-wrap">
    <div class="auth-box">
      <div class="panel-brand" aria-hidden="true">
        <div class="brand-inner">
          <h1>Welcome SNIA 2025</h1>
          <p>Gabung komunitas pembelajar & presenter. Akses materi, presentasi, dan diskusi.</p>
        </div>
      </div>

      <div class="form-panel">
        <h2>Login</h2>

        <!-- Flash message server-side -->
        <?php if(session()->getFlashdata('error')): ?>
          <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
        <?php endif; ?>
        <?php if(session()->getFlashdata('success')): ?>
          <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
        <?php endif; ?>
        <?php if (isset($validation)): ?>
          <div class="alert alert-danger"><?= $validation->listErrors() ?></div>
        <?php endif; ?>

        <form action="<?= site_url('auth/login') ?>" method="post" class="auth-form" autocomplete="on">
          <?= csrf_field() ?>

          <div class="form-group">
            <label for="email">Email</label>
            <input id="email" type="email" name="email" placeholder="Masukkan email" value="<?= old('email') ?>" required>
          </div>

          <div class="form-group">
            <label for="password">Kata Sandi</label>
            <div class="input-group">
              <input id="password" type="password" name="password" placeholder="Masukkan kata sandi" required>
              <button type="button" class="toggle-password" aria-label="Tampilkan kata sandi">
                <i class="fa fa-eye" aria-hidden="true"></i>
              </button>
            </div>
          </div>

          <button type="submit" class="btn btn-primary">Masuk</button>
        </form>

        <p class="redirect">Belum punya akun? <a href="<?= site_url('auth/register') ?>">Daftar</a></p>
      </div>
    </div>
  </div>

  <script src="<?= base_url('assets/js/auth.js') ?>"></script>
</body>
</html>
