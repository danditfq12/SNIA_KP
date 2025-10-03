document.addEventListener('DOMContentLoaded', function () {
  // ==== Toggle password (supports multiple fields) ====
  document.querySelectorAll('.toggle-password').forEach(function(btn){
    btn.addEventListener('click', function(){
      const wrap = this.closest('.input-group');
      if (!wrap) return;
      const input = wrap.querySelector('input[type="password"], input[type="text"]');
      if (!input) return;

      const icon = this.querySelector('i');
      const toType = input.type === 'password' ? 'text' : 'password';
      input.type = toType;

      if (icon) {
        icon.classList.toggle('fa-eye', toType === 'password');
        icon.classList.toggle('fa-eye-slash', toType === 'text');
      }
      input.focus();
    });
  });

  // ==== Utilities ====
  const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  function ensureFormAlertBox(form) {
    let box = form.querySelector('.__client-alert');
    if (!box) {
      box = document.createElement('div');
      box.className = 'alert alert-danger __client-alert';
      const firstGroup = form.querySelector('.form-group') || form.firstChild;
      form.insertBefore(box, firstGroup);
    }
    return box;
  }
  function removeFormAlertBox(form) {
    const box = form.querySelector('.__client-alert');
    if (box) box.remove();
  }
  function setFieldError(input, message) {
    input.classList.add('is-invalid');
    const group = input.closest('.form-group') || input.parentElement || input;
    let help = group.querySelector('.invalid-feedback[data-client="1"]');
    if (!help) {
      help = document.createElement('small');
      help.className = 'invalid-feedback';
      help.setAttribute('data-client', '1');
      group.appendChild(help);
    }
    help.textContent = message;
  }
  function clearFieldError(input) {
    input.classList.remove('is-invalid');
    const group = input.closest('.form-group') || input.parentElement || input;
    const help = group.querySelector('.invalid-feedback[data-client="1"]');
    if (help) help.remove();
  }

  // ==== Helper: form detector ====
  const isLoginForm = (form) => {
    const action = (form.getAttribute('action') || '').toLowerCase();
    return /\/auth\/login(\?|$|\/)?/.test(action) ||
           form.dataset.context === 'login' ||
           form.id === 'login-form';
  };
  const isRegisterForm = (form) => {
    const action = (form.getAttribute('action') || '').toLowerCase();
    return /\/auth\/register(\?|$|\/)?/.test(action) ||
           form.dataset.context === 'register' ||
           form.id === 'register-form';
  };

  // ==== LOGIN validation (only login forms) ====
  Array.from(document.querySelectorAll('.auth-form'))
    .filter(isLoginForm)
    .forEach(function(form){
      form.setAttribute('novalidate', 'novalidate');

      form.addEventListener('submit', function(e){
        const email = form.querySelector('input[name="email"], input[type="email"]');
        const pass  = form.querySelector('input[name="password"][type="password"], .input-group input[type="password"]');

        // clean previous client errors
        removeFormAlertBox(form);
        if (email) clearFieldError(email);
        if (pass)  clearFieldError(pass);

        const emailVal = (email?.value || '').trim();
        const passVal  = (pass?.value  || '').trim();

        // both empty → general box + highlight
        if (email && pass && emailVal === '' && passVal === '') {
          e.preventDefault();
          const box = ensureFormAlertBox(form);
          box.textContent = 'Silakan isi email dan kata sandi.';
          email.classList.add('is-invalid');
          pass.classList.add('is-invalid');
          return;
        }

        let ok = true;
        if (email) {
          if (emailVal === '') { setFieldError(email, 'Email wajib diisi.'); ok = false; }
          else if (!emailRe.test(emailVal)) { setFieldError(email, 'Format email tidak valid.'); ok = false; }
        }
        if (pass) {
          if (passVal === '') { setFieldError(pass, 'Kata sandi wajib diisi.'); ok = false; }
          else if (passVal.length < 6) { setFieldError(pass, 'Kata sandi harus minimal 6 karakter.'); ok = false; }
        }

        if (!ok) {
          e.preventDefault();
          const firstInvalid = form.querySelector('.is-invalid');
          if (firstInvalid) firstInvalid.focus();
        }
      });

      // live clear
      form.querySelectorAll('input').forEach(inp => {
        inp.addEventListener('input', () => clearFieldError(inp));
      });
    });

  // ==== REGISTER validation (only register forms) ====
  Array.from(document.querySelectorAll('.auth-form'))
    .filter(isRegisterForm)
    .forEach(function(form){
      form.setAttribute('novalidate', 'novalidate');

      form.addEventListener('submit', function(e){
        removeFormAlertBox(form);
        // bersihkan error client sebelumnya
        form.querySelectorAll('input, select').forEach(clearFieldError);

        // Ambil field umum; sesuaikan nama kalau beda
        const fullName = form.querySelector('input[name="nama_lengkap"], input[name="name"], input[name="nama"]');
        const email    = form.querySelector('input[name="email"], input[type="email"]');
        const pass     = form.querySelector('input[name="password"]');
        const pass2    = form.querySelector('input[name="password_confirm"], input[name="password_confirmation"], input[name="confirm_password"]');
        const roleSel  = form.querySelector('select[name="role"]'); // kalau ada

        const requiredInputs = [fullName, email, pass].filter(Boolean);
        if (roleSel && roleSel.hasAttribute('required')) requiredInputs.push(roleSel);
        if (pass2 && pass2.hasAttribute('required')) requiredInputs.push(pass2);

        // Cek semua required kosong?
        const empties = requiredInputs.filter(inp => (inp.value || '').trim() === '');
        if (empties.length === requiredInputs.length && requiredInputs.length > 0) {
          e.preventDefault();
          const box = ensureFormAlertBox(form);
          box.textContent = 'Harap lengkapi semua data yang wajib diisi.';
          empties.forEach(inp => inp.classList.add('is-invalid'));
          return;
        }

        let ok = true;

        // Per-field messages
        if (fullName && (fullName.value || '').trim() === '') { setFieldError(fullName, 'Nama lengkap wajib diisi.'); ok = false; }

        if (email) {
          const v = (email.value || '').trim();
          if (v === '') { setFieldError(email, 'Email wajib diisi.'); ok = false; }
          else if (!emailRe.test(v)) { setFieldError(email, 'Format email tidak valid.'); ok = false; }
        }

        if (pass) {
          const v = (pass.value || '').trim();
          if (v === '') { setFieldError(pass, 'Kata sandi wajib diisi.'); ok = false; }
          else if (v.length < 6) { setFieldError(pass, 'Kata sandi harus minimal 6 karakter.'); ok = false; }
        }

        if (pass && pass2) {
          const v1 = (pass.value || '').trim();
          const v2 = (pass2.value || '').trim();
          if (v2 === '') { setFieldError(pass2, 'Konfirmasi kata sandi wajib diisi.'); ok = false; }
          else if (v1 !== v2) { setFieldError(pass2, 'Konfirmasi kata sandi tidak cocok.'); ok = false; }
        }

        if (roleSel) {
          const v = (roleSel.value || '').trim();
          if (roleSel.hasAttribute('required') && (v === '' || v === '-')) {
            setFieldError(roleSel, 'Role wajib dipilih.');
            ok = false;
          }
        }

        // Jika ada error → tahan submit + fokuskan ke pertama
        if (!ok) {
          e.preventDefault();
          const firstInvalid = form.querySelector('.is-invalid');
          if (firstInvalid) firstInvalid.focus();
        }
      });

      // live clear untuk register
      form.querySelectorAll('input, select').forEach(inp => {
        inp.addEventListener('input', () => clearFieldError(inp));
        inp.addEventListener('change', () => clearFieldError(inp));
      });
    });

  // NOTE: form lain (verify, dll.) tidak divalidasi oleh JS ini → flow simpan/verify tetap aman.
});