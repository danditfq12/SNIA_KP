<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Verifikasi Email - SNIA</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    .otp-input {
      width: 50px;
      height: 50px;
      text-align: center;
      font-size: 20px;
      margin: 0 5px;
    }
  </style>
</head>
<body class="bg-light">

<div class="container d-flex justify-content-center align-items-center vh-100">
  <div class="card shadow-lg p-4" style="width: 400px;">
    <h4 class="text-center mb-3">Verifikasi Email</h4>
    <p class="text-muted text-center">Masukkan kode 6 digit yang telah kami kirim ke email kamu.</p>

    <?php if(session()->getFlashdata('error')): ?>
      <div class="alert alert-danger">
        <?= session()->getFlashdata('error') ?>
      </div>
    <?php endif; ?>

    <form action="<?= site_url('auth/verify') ?>" method="post">
      <?= csrf_field() ?>
      <div class="d-flex justify-content-center mb-3">
        <?php for($i=1; $i<=6; $i++): ?>
          <input type="text" maxlength="1" name="code[]" class="form-control otp-input" required>
        <?php endfor; ?>
      </div>

      <button type="submit" class="btn btn-primary w-100">Verifikasi</button>
    </form>

    <div class="text-center mt-3">
      <small>Tidak menerima kode? <a href="<?= site_url('auth/resend') ?>">Kirim ulang</a></small>
    </div>
  </div>
</div>

<script>
  // Auto focus antar input OTP
  document.querySelectorAll('.otp-input').forEach((input, index, inputs) => {
    input.addEventListener('input', () => {
      if (input.value && index < inputs.length - 1) {
        inputs[index + 1].focus();
      }
    });
  });
</script>

</body>
</html>
