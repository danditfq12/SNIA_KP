<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Verifikasi Reset - SNIA</title>
  <link rel="stylesheet" href="<?= base_url('assets/css/auth.css') ?>">
</head>
<body>
<div class="auth-wrap">
  <div class="auth-box">
    <!-- Panel brand (opsional, hapus bila tak dipakai) -->
    <div class="panel-brand" aria-hidden="true">
      <div class="brand-inner">
        <h1>SNIA 2025</h1>
        <p>Masukkan kode OTP yang kami kirim ke email Anda untuk melanjutkan reset password.</p>
      </div>
    </div>

    <div class="form-panel">
      <h2>Verifikasi OTP</h2>

      <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
      <?php endif; ?>
      <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
      <?php endif; ?>

      <form action="<?= site_url('auth/reset/verify') ?>" method="post" class="auth-form" id="verify-form" novalidate>
        <?= csrf_field() ?>

        <!-- Email disisipkan dari session / query param, JANGAN diubah oleh user -->
        <input type="hidden" name="email" value="<?= esc($email ?? (session()->get('reset_email') ?? '')) ?>">

        <div class="form-group">
          <label for="otp">Kode Verifikasi (6 digit)</label>
          <input
            id="otp"
            type="text"
            name="otp"
            placeholder="Contoh: 123456"
            maxlength="6"
            inputmode="numeric"
            pattern="\d{6}"
            required
          >
        </div>

        <button type="submit" class="btn btn-primary">Verifikasi</button>

        <p class="redirect" style="margin-top: 12px;">
          Tidak menerima kode? <a href="<?= site_url('auth/forgot-password') ?>">Kirim ulang</a>
        </p>
        <p class="redirect" style="margin-top: 6px;">
          <a href="<?= site_url('auth/login') ?>">Kembali ke Login</a>
        </p>
      </form>
    </div>
  </div>
</div>

<script src="<?= base_url('assets/js/auth.js') ?>"></script>
</body>
</html>
