<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Verifikasi OTP - SNIA</title>

  <link rel="stylesheet" href="<?= base_url('assets/css/auth.css') ?>">
  <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

  <style>
    .verify-wrap { min-height: 100vh; display:flex; align-items:center; justify-content:center; padding:28px; }
    .verify-card {
      width: 100%;
      max-width: 520px;
      background: #fff;
      border-radius: 12px;
      padding: 28px;
      box-shadow: 0 12px 36px rgba(8,18,63,0.12);
    }
    .verify-card h4 { margin:0 0 6px 0; text-align:center; color:#2c3e91; }
    .verify-card p { margin:0 0 20px 0; text-align:center; color:#666; font-size:14px; }

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
      background: #f8f9fa;
      transition: all 0.2s;
    }
    .otp-input.filled {
      background: #fff;
      border-color: #667eea;
      color: #2c3e91;
      font-weight: 600;
    }
    .otp-input:focus {
      outline: none;
      border-color: #667eea;
      box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
    }

    .resend-disabled { opacity: .55; pointer-events: none; }

    @media (max-width:480px) {
      .otp-row { gap:8px; }
      .otp-input { width: 42px; height:48px; font-size:18px; min-width:40px; }
      .verify-card { padding:20px; }
    }
  </style>
</head>
<body>
  <div class="verify-wrap">
    <div class="verify-card">
      <h4>Verifikasi Email</h4>
      <p class="text-muted">Kami telah mengirim kode OTP ke <strong><?= esc($email) ?></strong></p>

      <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
      <?php endif; ?>
      <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
      <?php endif; ?>

      <form action="<?= site_url('auth/verify') ?>" method="post" id="verifyForm" autocomplete="off">
        <?= csrf_field() ?>
        <input type="hidden" name="email" value="<?= esc($email) ?>">
        
        <!-- Hidden input untuk auto-fill, tidak ditampilkan ke user -->
        <input type="hidden" id="hiddenOTP" value="<?= isset($otp_code) ? esc($otp_code) : '' ?>">

        <div class="otp-row">
          <?php for($i=0; $i<6; $i++): ?>
            <input inputmode="numeric" pattern="\d*" maxlength="1" name="code[]" class="otp-input" required>
          <?php endfor; ?>
        </div>

        <button type="submit" class="btn btn-primary" style="margin-bottom:12px;">Verifikasi</button>
      </form>

      <div style="display:flex; justify-content:space-between; align-items:center;">
        <small id="countdown" data-remaining="<?= (int) ($remaining ?? 0) ?>">OTP berlaku: —</small>
        <a id="resendLink" href="<?= site_url('auth/resend?email='.urlencode($email)) ?>" style="text-decoration:none;">
          Kirim Ulang OTP
        </a>
      </div>
    </div>
  </div>

  <script>
    // ✅ AUTO-FILL OTP SAAT HALAMAN LOAD (TANPA TAMPILAN VISUAL BOX)
    window.addEventListener('DOMContentLoaded', function() {
      const hiddenOTP = document.getElementById('hiddenOTP').value;
      if (hiddenOTP && hiddenOTP.length === 6) {
        autoFillOTP(hiddenOTP);
      }
    });

    function autoFillOTP(code) {
      const inputs = document.querySelectorAll('.otp-input');
      const digits = code.split('');
      inputs.forEach((input, index) => {
        if (digits[index]) {
          input.value = digits[index];
          input.classList.add('filled');
        }
      });
      // Focus ke input terakhir
      if (inputs.length > 0) {
        inputs[inputs.length - 1].focus();
      }
    }

    // OTP Input Behaviour
    (function(){
      const inputs = Array.from(document.querySelectorAll('.otp-input'));
      if (!inputs.length) return;

      inputs.forEach((el, idx) => {
        el.addEventListener('input', (e) => {
          el.value = el.value.replace(/\D/g, '').slice(0,1);
          el.value ? el.classList.add('filled') : el.classList.remove('filled');
          if (el.value && idx < inputs.length - 1) {
            inputs[idx + 1].focus();
          }
        });

        el.addEventListener('keydown', (e) => {
          if (e.key === 'Backspace') {
            if (el.value === '' && idx > 0) {
              inputs[idx - 1].focus();
              inputs[idx - 1].value = '';
              inputs[idx - 1].classList.remove('filled');
              e.preventDefault();
            } else {
              el.classList.remove('filled');
            }
          } else if (e.key === 'ArrowLeft' && idx > 0) {
            inputs[idx - 1].focus();
            e.preventDefault();
          } else if (e.key === 'ArrowRight' && idx < inputs.length - 1) {
            inputs[idx + 1].focus();
            e.preventDefault();
          }
        });

        // Handle paste event
        el.addEventListener('paste', (e) => {
          e.preventDefault();
          const pastedData = e.clipboardData.getData('text').replace(/\D/g, '');
          const digits = pastedData.split('').slice(0, 6);
          inputs.forEach((input, index) => {
            if (digits[index]) {
              input.value = digits[index];
              input.classList.add('filled');
            }
          });
          const lastFilledIndex = Math.min(digits.length - 1, inputs.length - 1);
          if (lastFilledIndex >= 0) {
            inputs[lastFilledIndex].focus();
          }
        });
      });

      // Form validation
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

    // Countdown Timer
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
          resend.style.pointerEvents = 'none';
        } else {
          resend.classList.remove('resend-disabled');
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
          cdEl.textContent = 'OTP kedaluwarsa. Kirim ulang kode baru.';
          setDisabled(false);
        }
      }
      tick();
    })();
  </script>
</body>
</html>