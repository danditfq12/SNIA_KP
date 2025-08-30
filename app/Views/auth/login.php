<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login - SNIA</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
  <style>
    body {
      margin: 0;
      font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
      height: 100vh;
      overflow: hidden;
    }

    .login-container {
      display: flex;
      height: 100vh;
    }

    /* Left Panel - Login Form */
    .login-panel {
      flex: 1;
      background: #ffffff;
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 2rem;
      position: relative;
    }

    .login-form-wrapper {
      width: 100%;
      max-width: 400px;
      padding: 2rem;
    }

    .logo-section {
      text-align: center;
      margin-bottom: 2rem;
    }

    .logo {
      display: inline-flex;
      align-items: center;
      gap: 0.75rem;
      margin-bottom: 1rem;
    }

    .logo-icon {
      width: 40px;
      height: 40px;
      background: linear-gradient(135deg, #4285f4, #1976d2);
      border-radius: 8px;
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .logo-icon i {
      color: white;
      font-size: 1.2rem;
    }

    .logo-text {
      font-size: 1.8rem;
      font-weight: 700;
      color: #4285f4;
      margin: 0;
    }

    .welcome-text {
      text-align: center;
      margin-bottom: 2rem;
    }

    .welcome-text h2 {
      font-size: 1.75rem;
      font-weight: 600;
      color: #333;
      margin-bottom: 0.5rem;
    }

    .welcome-text p {
      color: #666;
      margin: 0;
      font-size: 0.95rem;
    }

    .form-group {
      margin-bottom: 1.5rem;
    }

    .form-group label {
      display: block;
      margin-bottom: 0.5rem;
      color: #555;
      font-weight: 500;
      font-size: 0.9rem;
    }

    .form-control {
      width: 100%;
      padding: 0.875rem 1rem;
      border: 1.5px solid #e1e5e9;
      border-radius: 8px;
      font-size: 1rem;
      transition: all 0.3s ease;
      background: #ffffff;
    }

    .form-control:focus {
      outline: none;
      border-color: #4285f4;
      box-shadow: 0 0 0 3px rgba(66, 133, 244, 0.1);
    }

    .form-options {
      display: flex;
      justify-content: flex-start;
      align-items: center;
      margin-bottom: 2rem;
    }

    .remember-me {
      display: flex;
      align-items: center;
      gap: 0.5rem;
      color: #555;
      font-size: 0.9rem;
    }

    .remember-me input[type="checkbox"] {
      width: 16px;
      height: 16px;
    }

    .btn-signin {
      width: 100%;
      padding: 1rem;
      background: linear-gradient(135deg, #4285f4, #1976d2);
      border: none;
      border-radius: 8px;
      color: white;
      font-size: 1rem;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
      margin-bottom: 2rem;
    }

    .btn-signin:hover {
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(66, 133, 244, 0.4);
    }

    .create-account {
      text-align: center;
      color: #666;
      font-size: 0.9rem;
    }

    .create-account a {
      color: #4285f4;
      text-decoration: none;
      font-weight: 600;
    }

    .create-account a:hover {
      text-decoration: underline;
    }

    /* Right Panel - Info Section */
    .info-panel {
      flex: 1;
      background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
      display: flex;
      align-items: center;
      justify-content: center;
      padding: 3rem;
      color: white;
      position: relative;
      overflow: hidden;
    }

    .info-panel::before {
      content: '';
      position: absolute;
      top: 0;
      left: 0;
      right: 0;
      bottom: 0;
      background: linear-gradient(135deg, rgba(102, 126, 234, 0.9), rgba(118, 75, 162, 0.9));
      z-index: 1;
    }

    .info-content {
      position: relative;
      z-index: 2;
      text-align: center;
      max-width: 450px;
    }

    .info-icon {
      width: 80px;
      height: 80px;
      background: rgba(255, 255, 255, 0.15);
      border-radius: 16px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 2rem;
      backdrop-filter: blur(10px);
      border: 1px solid rgba(255, 255, 255, 0.2);
    }

    .info-icon i {
      font-size: 2rem;
      color: white;
    }

    .info-content h3 {
      font-size: 2rem;
      font-weight: 700;
      margin-bottom: 1rem;
      line-height: 1.2;
    }

    .info-content p {
      font-size: 1.1rem;
      margin-bottom: 2rem;
      opacity: 0.9;
      line-height: 1.5;
    }

    .features-list {
      list-style: none;
      padding: 0;
      margin: 0;
    }

    .features-list li {
      display: flex;
      align-items: center;
      gap: 0.75rem;
      margin-bottom: 1rem;
      font-size: 1rem;
      opacity: 0.9;
    }

    .features-list li i {
      width: 20px;
      height: 20px;
      background: rgba(255, 255, 255, 0.2);
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 0.75rem;
      flex-shrink: 0;
    }

    /* Decorative elements */
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
      background: rgba(255, 255, 255, 0.1);
      border-radius: 50%;
      animation: float 15s infinite ease-in-out;
    }

    .shape:nth-child(1) {
      width: 120px;
      height: 120px;
      top: 10%;
      left: 10%;
      animation-delay: 0s;
    }

    .shape:nth-child(2) {
      width: 80px;
      height: 80px;
      top: 60%;
      right: 15%;
      animation-delay: -5s;
    }

    .shape:nth-child(3) {
      width: 60px;
      height: 60px;
      bottom: 20%;
      left: 20%;
      animation-delay: -10s;
    }

    @keyframes float {
      0%, 100% {
        transform: translateY(0px) rotate(0deg);
        opacity: 0.7;
      }
      50% {
        transform: translateY(-30px) rotate(180deg);
        opacity: 1;
      }
    }

    /* Alert styles */
    .alert {
      border-radius: 8px;
      border: none;
      margin-bottom: 1.5rem;
      padding: 1rem;
      display: flex;
      align-items: center;
      gap: 0.5rem;
    }

    .alert-danger {
      background: #ffeaea;
      color: #d63384;
    }

    .alert-success {
      background: #d1edff;
      color: #0f5132;
    }

    /* Responsive Design */
    @media (max-width: 768px) {
      .login-container {
        flex-direction: column;
      }

      .info-panel {
        order: -1;
        flex: 0 0 auto;
        min-height: 40vh;
        padding: 2rem;
      }

      .login-panel {
        flex: 1;
        padding: 1rem;
      }

      .info-content h3 {
        font-size: 1.5rem;
      }

      .info-content p {
        font-size: 1rem;
      }

      .features-list li {
        font-size: 0.9rem;
      }
    }

    @media (max-width: 480px) {
      .login-form-wrapper {
        padding: 1rem;
      }

      .welcome-text h2 {
        font-size: 1.5rem;
      }
    }
  </style>
</head>
<body>
  <div class="login-container">
    <!-- Left Panel - Login Form -->
    <div class="login-panel">
      <div class="login-form-wrapper">
        <div class="logo-section">
          <div class="logo">
            <div class="logo-icon">
              <i class="fas fa-graduation-cap"></i>
            </div>
            <h1 class="logo-text">SNIA</h1>
          </div>
        </div>

        <div class="welcome-text">
          <h2>Welcome Back</h2>
          <p>Sign in to your account</p>
        </div>

        <!-- Flash Messages -->
        <?php if(session()->getFlashdata('error')): ?>
          <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle"></i>
            <?= session()->getFlashdata('error') ?>
          </div>
        <?php endif; ?>

        <?php if(session()->getFlashdata('success')): ?>
          <div class="alert alert-success">
            <i class="fas fa-check-circle"></i>
            <?= session()->getFlashdata('success') ?>
          </div>
        <?php endif; ?>

        <form action="<?= site_url('auth/login') ?>" method="post">
          <?= csrf_field() ?>
          
          <div class="form-group">
            <label for="email">Email Address</label>
            <input type="email" name="email" id="email" class="form-control" placeholder="daus07022004@gmail.com" required>
          </div>

          <div class="form-group">
            <label for="password">Password</label>
            <input type="password" name="password" id="password" class="form-control" placeholder="••••••" required>
          </div>

          <div class="form-options">
            <label class="remember-me">
              <input type="checkbox" name="remember">
              <span>Remember me</span>
            </label>
          </div>

          <button type="submit" class="btn-signin">Sign In</button>
        </form>

        <div class="create-account">
          Don't have an account? <a href="<?= site_url('auth/register') ?>">Create one</a>
        </div>
      </div>
    </div>

    <!-- Right Panel - Conference Management Info -->
    <div class="info-panel">
      <div class="floating-shapes">
        <div class="shape"></div>
        <div class="shape"></div>
        <div class="shape"></div>
      </div>
      
      <div class="info-content">
        <div class="info-icon">
          <i class="fas fa-users-cog"></i>
        </div>
        
        <h3>Conference Management</h3>
        <p>Manage your academic conference experience with ease</p>
        
        <ul class="features-list">
          <li>
            <i class="fas fa-check"></i>
            <span>Submit and track abstracts</span>
          </li>
          <li>
            <i class="fas fa-check"></i>
            <span>Review submissions</span>
          </li>
          <li>
            <i class="fas fa-check"></i>
            <span>Register for events</span>
          </li>
          <li>
            <i class="fas fa-check"></i>
            <span>Manage presentations</span>
          </li>
        </ul>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    // Add smooth entrance animation
    document.addEventListener('DOMContentLoaded', function() {
      const loginPanel = document.querySelector('.login-panel');
      const infoPanel = document.querySelector('.info-panel');
      
      loginPanel.style.opacity = '0';
      loginPanel.style.transform = 'translateX(-30px)';
      infoPanel.style.opacity = '0';
      infoPanel.style.transform = 'translateX(30px)';
      
      setTimeout(() => {
        loginPanel.style.transition = 'all 0.8s ease';
        infoPanel.style.transition = 'all 0.8s ease';
        loginPanel.style.opacity = '1';
        loginPanel.style.transform = 'translateX(0)';
        infoPanel.style.opacity = '1';
        infoPanel.style.transform = 'translateX(0)';
      }, 100);

      // Enhanced form interactions
      const inputs = document.querySelectorAll('.form-control');
      inputs.forEach(input => {
        input.addEventListener('focus', function() {
          this.style.transform = 'translateY(-1px)';
          this.style.boxShadow = '0 4px 12px rgba(66, 133, 244, 0.15)';
        });
        
        input.addEventListener('blur', function() {
          this.style.transform = 'translateY(0)';
          this.style.boxShadow = 'none';
        });
      });

      // Button click effect
      const signInBtn = document.querySelector('.btn-signin');
      signInBtn.addEventListener('click', function(e) {
        const ripple = document.createElement('div');
        const rect = this.getBoundingClientRect();
        const size = Math.max(rect.width, rect.height);
        const x = e.clientX - rect.left - size / 2;
        const y = e.clientY - rect.top - size / 2;
        
        ripple.style.cssText = `
          position: absolute;
          left: ${x}px;
          top: ${y}px;
          width: ${size}px;
          height: ${size}px;
          background: rgba(255, 255, 255, 0.3);
          border-radius: 50%;
          transform: scale(0);
          animation: ripple 0.6s ease-out;
          pointer-events: none;
        `;
        
        this.appendChild(ripple);
        
        setTimeout(() => ripple.remove(), 600);
      });
    });

    // Add ripple animation
    const style = document.createElement('style');
    style.textContent = `
      @keyframes ripple {
        to {
          transform: scale(2);
          opacity: 0;
        }
      }
      
      .btn-signin {
        position: relative;
        overflow: hidden;
      }
    `;
    document.head.appendChild(style);
  </script>
</body>
</html>