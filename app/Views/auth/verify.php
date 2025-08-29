<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>Verifikasi OTP - SNIA</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
<div class="container d-flex justify-content-center align-items-center vh-100">
  <div class="card shadow-lg p-4" style="width:420px;">
    <h4 class="mb-2 text-center">Verifikasi Email</h4>
    <p class="text-muted text-center mb-3">Kami telah mengirim kode OTP ke <b><?= esc($email) ?></b></p>

    <?php if(session()->getFlashdata('success')): ?>
      <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>
    <?php if(session()->getFlashdata('error')): ?>
      <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
    <?php endif; ?>

    <form action="<?= site_url('auth/verify') ?>" method="post">
      <?= csrf_field() ?>
      <input type="hidden" name="email" value="<?= esc($email) ?>">

      <div class="d-flex justify-content-between mb-3">
        <?php for($i=0; $i<6; $i++): ?>
          <input type="text"
                 name="code[]"
                 class="form-control otp-input mx-1 text-center fs-4"
                 maxlength="1"
                 inputmode="numeric"
                 pattern="\d"
                 required>
        <?php endfor; ?>
      </div>

      <button type="submit" class="btn btn-primary w-100">Verifikasi</button>
    </form>

    <div class="d-flex justify-content-between align-items-center mt-3">
      <small id="countdown" class="text-muted" data-remaining="<?= (int)$remaining ?>">
        OTP berlaku: —
      </small>
      <a id="resendLink" class="btn btn-link p-0"
         href="<?= site_url('auth/resend?email='.urlencode($email)) ?>">
         Kirim Ulang OTP
      </a>
    </div>
  </div>
</div>

<script>
// Auto focus & validasi digit
document.querySelectorAll('.otp-input').forEach((input, idx, arr) => {
  input.addEventListener('input', e => {
    e.target.value = e.target.value.replace(/\D/g,'').slice(0,1);
    if (e.target.value && idx < arr.length - 1) arr[idx+1].focus();
  });
  input.addEventListener('keydown', e => {
    if (e.key === 'Backspace' && !input.value && idx > 0) arr[idx-1].focus();
  });
});

// Countdown
(function(){
  const cd = document.getElementById('countdown');
  const resend = document.getElementById('resendLink');
  let s = parseInt(cd.getAttribute('data-remaining') || '0', 10);

  function fmt(t){
    const m = Math.floor(t/60), sec = t%60;
    return m + 'm ' + (sec<10?'0':'') + sec + 's';
  }

  function tick(){
    if (s > 0) {
      cd.textContent = 'OTP berlaku: ' + fmt(s);
      resend.classList.add('disabled');
      resend.style.pointerEvents = 'none';
      s--;
      setTimeout(tick, 1000);
    } else {
      cd.textContent = 'OTP kedaluwarsa. Kirim ulang untuk mendapatkan kode baru.';
      resend.classList.remove('disabled');
      resend.style.pointerEvents = 'auto';
    }
  }
  tick();
})();
</script>
</body>
</html>
