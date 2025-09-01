document.addEventListener('DOMContentLoaded', function () {
  // Toggle password (works for multiple toggles)
  document.querySelectorAll('.toggle-password').forEach(function(btn){
    btn.addEventListener('click', function(){
      const wrapper = this.closest('.input-group');
      if (!wrapper) return;
      const input = wrapper.querySelector('input');
      if (!input) return;
      const icon = this.querySelector('i');

      if (input.type === 'password') {
        input.type = 'text';
        if (icon) { icon.classList.remove('fa-eye'); icon.classList.add('fa-eye-slash'); }
      } else {
        input.type = 'password';
        if (icon) { icon.classList.remove('fa-eye-slash'); icon.classList.add('fa-eye'); }
      }
      input.focus();
    });
  });

  // Client-side validation for forms with class .auth-form
  document.querySelectorAll('.auth-form').forEach(function(form){
    form.addEventListener('submit', function(e){
      const email = form.querySelector('input[type="email"]');
      const pass  = form.querySelector('input[type="password"]');

      // Basic checks
      if (email) {
        const val = email.value.trim();
        if (val === '') {
          e.preventDefault();
          alert('Email wajib diisi.');
          email.focus();
          return;
        }
        // simple email regex
        const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!re.test(val)) {
          e.preventDefault();
          alert('Format email tidak valid.');
          email.focus();
          return;
        }
      }

      if (pass) {
        const pv = pass.value.trim();
        if (pv === '') {
          e.preventDefault();
          alert('Kata sandi wajib diisi.');
          pass.focus();
          return;
        }
      }
      // jika lolos, form akan submit ke server
    });
  });
});
