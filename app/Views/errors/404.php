<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>404 - Halaman Tidak Ditemukan - SNIA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .error-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            max-width: 600px;
            width: 90%;
            margin: 20px;
            text-align: center;
        }

        .error-header {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            padding: 40px 20px;
            position: relative;
            overflow: hidden;
        }

        .error-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: repeating-linear-gradient(
                45deg,
                transparent,
                transparent 10px,
                rgba(255,255,255,0.05) 10px,
                rgba(255,255,255,0.05) 20px
            );
            animation: shimmer 3s linear infinite;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        .error-code {
            font-size: 6rem;
            font-weight: bold;
            margin: 0;
            position: relative;
            z-index: 1;
            text-shadow: 2px 2px 4px rgba(0,0,0,0.3);
        }

        .error-title {
            font-size: 1.5rem;
            margin: 10px 0 0 0;
            position: relative;
            z-index: 1;
            opacity: 0.9;
        }

        .error-body {
            padding: 40px 30px;
        }

        .error-description {
            font-size: 1.1rem;
            color: #6b7280;
            margin-bottom: 30px;
            line-height: 1.6;
        }

        .error-icon {
            font-size: 3rem;
            color: #ef4444;
            margin-bottom: 20px;
        }

        .action-buttons {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
            margin-top: 30px;
        }

        .btn-custom {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.3);
            color: white;
        }

        .btn-secondary-custom {
            background: linear-gradient(135deg, #6b7280, #4b5563);
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 500;
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-secondary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(107, 114, 128, 0.3);
            color: white;
        }

        .suggestions {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            border-left: 4px solid #6366f1;
        }

        .suggestions h6 {
            color: #374151;
            margin-bottom: 15px;
            font-weight: 600;
        }

        .suggestions ul {
            text-align: left;
            margin: 0;
            padding-left: 20px;
            color: #6b7280;
        }

        .suggestions li {
            margin-bottom: 8px;
        }

        .footer-info {
            background: #f9fafb;
            padding: 20px;
            color: #6b7280;
            font-size: 0.9rem;
            border-top: 1px solid #e5e7eb;
        }

        @media (max-width: 768px) {
            .error-code {
                font-size: 4rem;
            }
            
            .error-title {
                font-size: 1.2rem;
            }
            
            .error-header, .error-body {
                padding: 30px 20px;
            }
            
            .action-buttons {
                flex-direction: column;
                align-items: center;
            }
            
            .btn-custom, .btn-secondary-custom {
                width: 200px;
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <div class="error-container">
        <!-- Error Header -->
        <div class="error-header">
            <h1 class="error-code">404</h1>
            <p class="error-title">Halaman Tidak Ditemukan</p>
        </div>

        <!-- Error Body -->
        <div class="error-body">
            <div class="error-icon">
                <i class="fas fa-exclamation-triangle"></i>
            </div>
            
            <p class="error-description">
                Maaf, halaman yang Anda cari tidak dapat ditemukan. Halaman mungkin telah dipindahkan, 
                dihapus, atau Anda salah mengetikkan alamat URL.
            </p>

            <!-- Suggestions -->
            <div class="suggestions">
                <h6><i class="fas fa-lightbulb me-2"></i>Saran:</h6>
                <ul>
                    <li>Periksa kembali alamat URL yang Anda ketikkan</li>
                    <li>Kembali ke halaman sebelumnya dan coba lagi</li>
                    <li>Gunakan menu navigasi untuk mencari halaman yang Anda butuhkan</li>
                    <li>Hubungi administrator jika Anda yakin ini adalah kesalahan sistem</li>
                </ul>
            </div>

            <!-- Action Buttons -->
            <div class="action-buttons">
                <a href="<?= base_url('/') ?>" class="btn-custom">
                    <i class="fas fa-home"></i>
                    Kembali ke Beranda
                </a>
                <a href="javascript:history.back()" class="btn-secondary-custom">
                    <i class="fas fa-arrow-left"></i>
                    Halaman Sebelumnya
                </a>
            </div>

            <!-- Additional Links -->
            <div class="mt-4">
                <p class="mb-2"><strong>Link Berguna:</strong></p>
                <div class="d-flex justify-content-center gap-3 flex-wrap">
                    <?php if (session('id_user')): ?>
                        <a href="<?= base_url('dashboard') ?>" class="text-decoration-none">
                            <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                        </a>
                    <?php else: ?>
                        <a href="<?= base_url('auth/login') ?>" class="text-decoration-none">
                            <i class="fas fa-sign-in-alt me-1"></i>Login
                        </a>
                    <?php endif; ?>
                    
                    <a href="<?= base_url('qr/scanner') ?>" class="text-decoration-none">
                        <i class="fas fa-qrcode me-1"></i>QR Scanner
                    </a>
                    
                    <?php if (session('role') === 'admin'): ?>
                        <a href="<?= base_url('admin/dashboard') ?>" class="text-decoration-none">
                            <i class="fas fa-cog me-1"></i>Admin Panel
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Footer Info -->
        <div class="footer-info">
            <p class="mb-0">
                <i class="fas fa-info-circle me-2"></i>
                Jika masalah berlanjut, silakan hubungi administrator sistem.
            </p>
            <p class="mb-0 mt-2">
                <small>Error Code: 404 | Time: <?= date('Y-m-d H:i:s') ?></small>
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Auto redirect after 30 seconds if user inactive
        let redirectTimer;
        let countdown = 30;

        function startRedirectTimer() {
            redirectTimer = setInterval(() => {
                countdown--;
                if (countdown <= 0) {
                    window.location.href = '<?= base_url('/') ?>';
                }
            }, 1000);
        }

        // Reset timer on user activity
        function resetTimer() {
            clearInterval(redirectTimer);
            countdown = 30;
        }

        // Add event listeners for user activity
        document.addEventListener('mousemove', resetTimer);
        document.addEventListener('keypress', resetTimer);
        document.addEventListener('click', resetTimer);

        // Start the timer when page loads
        // setTimeout(startRedirectTimer, 5000); // Start after 5 seconds
    </script>
</body>
</html>