<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Register - SNIA</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <style>
    :root {
      --primary-color: #667eea;
      --secondary-color: #764ba2;
      --accent-color: #f093fb;
    }

    body {
      margin: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      overflow-x: hidden;
    }

    .split-container {
      display: flex;
      min-height: 100vh;
    }

    .left-section {
      flex: 1;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem;
      position: relative;
      overflow: hidden;
    }

    .left-section::before {
      content: '';
      position: absolute;
      top: -50%;
      left: -50%;
      width: 200%;
      height: 200%;
      background: radial-gradient(circle, rgba(255,255,255,0.1) 1px, transparent 1px);
      background-size: 50px 50px;
      animation: float 20s ease-in-out infinite;
    }

    @keyframes float {
      0%, 100% { transform: translateY(0px) rotate(0deg); }
      50% { transform: translateY(-20px) rotate(2deg); }
    }

    .quote-container {
      text-align: center;
      color: white;
      z-index: 2;
      position: relative;
      max-width: 500px;
    }

    .quote-text {
      font-size: 2.5rem;
      font-weight: 700;
      margin-bottom: 2rem;
      line-height: 1.2;
      text-shadow: 0 4px 15px rgba(0,0,0,0.3);
      animation: fadeInUp 1s ease-out;
    }

    .floating-shapes {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      overflow: hidden;
      z-index: 1;
    }

    .shape {
      position: absolute;
      background: rgba(255,255,255,0.1);
      border-radius: 50%;
      animation: floatShapes 15s infinite linear;
    }

    .shape:nth-child(1) {
      width: 80px;
      height: 80px;
      top: 20%;
      left: 10%;
      animation-delay: 0s;
    }

    .shape:nth-child(2) {
      width: 120px;
      height: 120px;
      top: 60%;
      left: 80%;
      animation-delay: 5s;
    }

    .shape:nth-child(3) {
      width: 60px;
      height: 60px;
      top: 80%;
      left: 20%;
      animation-delay: 10s;
    }

    @keyframes floatShapes {
      0% { transform: translateY(0px) scale(1); opacity: 0.7; }
      50% { transform: translateY(-30px) scale(1.1); opacity: 1; }
      100% { transform: translateY(0px) scale(1); opacity: 0.7; }
    }

    @keyframes fadeInUp {
      from {
        opacity: 0;
        transform: translateY(30px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }

    .right-section {
      flex: 1;
      background: #f8f9fa;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem;
    }

    .form-container {
      background: white;
      border-radius: 20px;
      box-shadow: 0 20px 40px rgba(0,0,0,0.1);
      padding: 3rem;
      width: 100%;
      max-width: 450px;
      position: relative;
      animation: slideInRight 0.8s ease-out;
    }

    @keyframes slideInRight {
      from {
        opacity: 0;
        transform: translateX(50px);
      }
      to {
        opacity: 1;
        transform: translateX(0);
      }
    }

    .form-title {
      color: #2d3748;
      font-size: 2rem;
      font-weight: 700;
      text-align: center;
      margin-bottom: 2rem;
      position: relative;
    }

    .form-title::after {
      content: '';
      position: absolute;
      bottom: -10px;
      left: 50%;
      transform: translateX(-50%);
      width: 60px;
      height: 4px;
      background: linear-gradient(90deg, var(--primary-color), var(--accent-color));
      border-radius: 2px;
    }

    .form-label {
      color: #4a5568;
      font-weight: 600;
      margin-bottom: 0.5rem;
    }

    .form-control, .form-select {
      border: 2px solid #e2e8f0;
      border-radius: 12px;
      padding: 0.8rem 1rem;
      transition: all 0.3s ease;
      font-size: 1rem;
    }

    .form-control:focus, .form-select:focus {
      border-color: var(--primary-color);
      box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
      transform: translateY(-2px);
    }

    .btn-register {
      background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
      border: none;
      border-radius: 12px;
      padding: 1rem;
      font-size: 1.1rem;
      font-weight: 600;
      color: white;
      transition: all 0.3s ease;
      position: relative;
      overflow: hidden;
    }

    .btn-register::before {
      content: '';
      position: absolute;
      top: 0;
      left: -100%;
      width: 100%;
      height: 100%;
      background: linear-gradient(90deg, transparent, rgba(255,255,255,0.2), transparent);
      transition: left 0.5s;
    }

    .btn-register:hover::before {
      left: 100%;
    }

    .btn-register:hover {
      transform: translateY(-3px);
      box-shadow: 0 10px 25px rgba(102, 126, 234, 0.4);
    }

    .login-link {
      text-align: center;
      margin-top: 2rem;
      color: #718096;
    }

    .login-link a {
      color: var(--primary-color);
      text-decoration: none;
      font-weight: 600;
      transition: color 0.3s ease;
    }

    .login-link a:hover {
      color: var(--secondary-color);
    }

    .alert {
      border-radius: 12px;
      border: none;
      margin-bottom: 1.5rem;
    }

    .alert-success {
      background: linear-gradient(135deg, #48bb78, #38a169);
      color: white;
    }

    .alert-danger {
      background: linear-gradient(135deg, #f56565, #e53e3e);
      color: white;
    }

    @media (max-width: 768px) {
      .split-container {
        flex-direction: column;
      }
      
      .left-section {
        min-height: 40vh;
      }
      
      .quote-text {
        font-size: 1.8rem;
      }
      
      .form-container {
        padding: 2rem;
      }
    }

    /* Responsive animations */
    @media (prefers-reduced-motion: reduce) {
      * {
        animation: none !important;
        transition: none !important;
      }
    }
  </style>
</head>
<body>

<div class="split-container">
  <!-- Left Section - Quote Area -->
  <div class="left-section">
    <div class="floating-shapes">
      <div class="shape"></div>
      <div class="shape"></div>
      <div class="shape"></div>
    </div>
    
    <div class="quote-container">
      <h1 class="quote-text">Welcome SNIA 2025</h1>
    </div>
  </div>

  <!-- Right Section - Form -->
  <div class="right-section">
    <div class="form-container">
      <h3 class="form-title">Create Account</h3>

      <!-- Flash messages -->
      <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
      <?php endif; ?>
      <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
      <?php endif; ?>

      <form action="<?= site_url('auth/register') ?>" method="post" novalidate>
        <?= csrf_field() ?>

        <div class="mb-3">
          <label class="form-label">Nama Lengkap</label>
          <input
            type="text"
            name="nama_lengkap"
            class="form-control"
            placeholder="Masukkan nama lengkap Anda"
            value="<?= old('nama_lengkap') ?>"
            required>
        </div>

        <div class="mb-3">
          <label class="form-label">Email</label>
          <input
            type="email"
            name="email"
            class="form-control"
            placeholder="contoh@email.com"
            value="<?= old('email') ?>"
            required>
        </div>

        <div class="mb-3">
          <label class="form-label">Password</label>
          <input
            type="password"
            name="password"
            class="form-control"
            placeholder="Minimal 6 karakter"
            required>
        </div>

        <div class="mb-3">
          <label class="form-label">Konfirmasi Password</label>
          <input
            type="password"
            name="password2"
            class="form-control"
            placeholder="Ulangi password Anda"
            required>
        </div>

        <div class="mb-4">
          <label class="form-label">Daftar Sebagai</label>
          <select name="role" class="form-select" required>
            <option value="">-- Pilih Peran Anda --</option>
            <option value="presenter" <?= old('role')==='presenter'?'selected':''; ?>>Presenter</option>
            <option value="audience"  <?= old('role')==='audience'?'selected':''; ?>>Audience</option>
          </select>
        </div>

        <button type="submit" class="btn btn-register w-100">
          Daftar Sekarang
        </button>
      </form>

      <div class="login-link">
        <small>Sudah memiliki akun? <a href="<?= site_url('auth/login') ?>">Masuk di sini</a></small>
      </div>
    </div>
  </div>
</div>

<script>
// Add some interactive effects
document.addEventListener('DOMContentLoaded', function() {
  // Smooth focus transitions
  const inputs = document.querySelectorAll('.form-control, .form-select');
  inputs.forEach(input => {
    input.addEventListener('focus', function() {
      this.style.transform = 'translateY(-2px)';
    });
    
    input.addEventListener('blur', function() {
      this.style.transform = 'translateY(0)';
    });
  });
});
</script>

</body>
</html>