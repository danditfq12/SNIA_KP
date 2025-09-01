<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Verifikasi OTP - SNIA</title>

  <!-- CSS aplikasi (gunakan css yang sudah ada) -->
  <link rel="stylesheet" href="<?= base_url('css/auth.css') ?>">

  <!-- Font (opsional) & Font Awesome jika mau icon -->
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    /* Override untuk card verifikasi agar cocok di tengah */
    .verify-wrap { min-height: 100vh; display:flex; align-items:center; justify-content:center; padding:28px; }
    .verify-card {
      width: 100%;
      max-width: 460px;
      background: #fff;
      border-radius: 12px;
      padding: 28px;
      box-shadow: 0 12px 36px rgba(8,18,63,0.12);
    }
    .verify-card h4 { margin:0 0 6px 0; text-align:center; color:#2c3e91; }
    .verify-card p { margin:0 0 14px 0; text-align:center; color:#666; font-size:14px; }

    .otp-row {
      display:flex;
      gap:10px;
      justify-content:space-between;
      margin-bottom:18px;
    }
    .otp-input {
      width: 56px;
      max-width: 14%;
      min-width: 46px;
      height: 56px;
      text-align:center;
      font-size:22px;
      border-radius:8px;
      border:1px solid #d6d6d6;
      box-shadow: inset 0 1px 0 rgba(0,0,0,0.02);
    }

    /* tombol resend disabled style */
    .resend-disabled {
      opacity: .55;
      pointer-events: none;
    }

    /* responsif: buat input lebih besar di hp */
    @media (max-width:480px) {
      .otp-row { gap:8px; }
      .otp-input { width: 42px; height:48px; font-size:18px; min-width:40px; }
      .verify-card { padding:20px; }
    }
  </style>
</head>
<body>
  <div class="verify-wrap">
    <div class="verify-card" role="dialog" aria-labelledby="verifyTitle">
      <h4 id="verifyTitle">Verifikasi Email</h4>
      <p class="text-muted">Kami telah mengirim kode OTP ke <strong><?= esc($email) ?></strong></p>

      <!-- Flash messages (server-side) -->
      <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
      <?php endif; ?>
      <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
      <?php endif; ?>
      <?php if (isset($validation)): ?>
        <div class="alert alert-danger"><?= $validation->listErrors() ?></div>
      <?php endif; ?>

      <form action="<?= site_url('auth/verify') ?>" method="post" id="verifyForm" autocomplete="off">
        <?= csrf_field() ?>
        <input type="hidden" name="email" value="<?= esc($email) ?>">

        <div class="otp-row" aria-label="Masukkan kode OTP">
          <?php for($i=0; $i<6; $i++): ?>
            <input
              inputmode="numeric"
              pattern="\d*"
              maxlength="1"
              name="code[]"
              class="otp-input"
              aria-label="Digit <?= $i+1 ?>"
              required>
          <?php endfor; ?>
        </div>

        <button type="submit" class="btn btn-primary" style="margin-bottom:12px;">Verifikasi</button>
      </form>

      <div style="display:flex; justify-content:space-between; align-items:center;">
        <small id="countdown" class="text-muted" data-remaining="<?= (int) ($remaining ?? 0) ?>">OTP berlaku: —</small>

        <!-- Kirim ulang; kita disable saat countdown -->
        <a id="resendLink"
           class="btn btn-link"
           href="<?= site_url('auth/resend?email='.urlencode($email)) ?>"
           style="text-decoration:none; padding:0;">
          Kirim Ulang OTP
        </a>
      </div>
    </div>
  </div>

  <!-- JS: toggle + otp behaviour (mandiri) -->
  <script>
    // Fokus otomatis & behaviour input OTP
    (function(){
      const inputs = Array.from(document.querySelectorAll('.otp-input'));
      if (!inputs.length) return;

      // focus ke input pertama saat load
      inputs[0].focus();

      inputs.forEach((el, idx) => {
        el.addEventListener('input', (e) => {
          // hanya digit, ambil 1 karakter
          el.value = el.value.replace(/\D/g, '').slice(0,1);
          if (el.value && idx < inputs.length - 1) {
            inputs[idx + 1].focus();
          }
        });

        el.addEventListener('keydown', (e) => {
          if (e.key === 'Backspace') {
            if (el.value === '' && idx > 0) {
              inputs[idx - 1].focus();
              inputs[idx - 1].value = '';
              e.preventDefault();
            } else {
              // biarkan default untuk hapus karakter
            }
          } else if (e.key === 'ArrowLeft' && idx > 0) {
            inputs[idx - 1].focus();
            e.preventDefault();
          } else if (e.key === 'ArrowRight' && idx < inputs.length - 1) {
            inputs[idx + 1].focus();
            e.preventDefault();
          } else if (/^[0-9]$/.test(e.key)) {
            // tulis digit => handled by input event
          } else if (e.key !== 'Tab') {
            // cegah karakter non-digit (kecuali Tab)
            // e.preventDefault();
          }
        });
      });

      // Pastikan form tidak terkirim jika ada input kosong
      const form = document.getElementById('verifyForm');
      form.addEventListener('submit', function(e){
        const values = inputs.map(i => i.value.trim());
        if (values.some(v => v === '')) {
          e.preventDefault();
          alert('Mohon masukkan semua 6 digit kode OTP.');
          const firstEmpty = inputs.find(i=>i.value.trim()==='');
          if (firstEmpty) firstEmpty.focus();
        }
      });
    })();

    // Countdown & disable resend sampai habis
    (function(){
      const cdEl = document.getElementById('countdown');
      const resend = document.getElementById('resendLink');
      let s = parseInt(cdEl.getAttribute('data-remaining') || '0', 10);

      function fmt(t){
        const m = Math.floor(t/60);
        const sec = t % 60;
        return (m>0 ? m + 'm ' : '') + (sec<10 ? '0' + sec : sec) + 's';
      }

      function setDisabled(flag){
        if (flag) {
          resend.classList.add('resend-disabled');
          resend.setAttribute('aria-disabled','true');
          resend.style.pointerEvents = 'none';
        } else {
          resend.classList.remove('resend-disabled');
          resend.removeAttribute('aria-disabled');
          resend.style.pointerEvents = 'auto';
        }
      }

      function tick(){
        if (s > 0) {
          cdEl.textContent = 'OTP berlaku: ' + fmt(s);
          setDisabled(true);
          s--;
          setTimeout(tick, 1000);
        } else {
          cdEl.textContent = 'OTP kedaluwarsa. Kirim ulang untuk mendapatkan kode baru.';
          setDisabled(false);
        }
      }
      tick();
    })();
  </script>
</body>
</html>
