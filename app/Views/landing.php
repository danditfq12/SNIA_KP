<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SNIA - Seminar Nasional Informatika</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        /* ===== CSS VARIABLES ===== */
        :root {
            --primary-blue: #0056b3;
            --light-blue: #e3f2fd;
            --dark-blue: #003d82;
            --accent-blue: #1976d2;
            --white: #ffffff;
            --text-dark: #333333;
            --text-muted: #6c757d;
            --shadow-light: 0 10px 30px rgba(0, 86, 179, 0.1);
            --shadow-medium: 0 20px 40px rgba(0, 86, 179, 0.2);
            --shadow-heavy: 0 25px 50px rgba(0, 86, 179, 0.2);
            --border-radius: 15px;
            --border-radius-small: 25px;
            --border-radius-large: 50px;
            --transition: all 0.3s ease;
        }

        /* ===== BASE STYLES ===== */
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--text-dark);
        }

        /* ===== NAVIGATION ===== */
        .navbar {
            background: rgba(255, 255, 255, 0.1) !important;
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .navbar.scrolled {
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            box-shadow: 0 2px 20px rgba(0, 0, 0, 0.1);
        }

        .navbar-brand {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--white) !important;
            transition: var(--transition);
        }

        .navbar.scrolled .navbar-brand {
            color: var(--primary-blue) !important;
        }

        .navbar-nav .nav-link {
            color: var(--white) !important;
            font-weight: 500;
            margin: 0 10px;
            transition: var(--transition);
        }

        .navbar.scrolled .navbar-nav .nav-link {
            color: var(--text-dark) !important;
        }

        .navbar-nav .nav-link:hover {
            color: var(--accent-blue) !important;
        }

        .navbar .btn-primary {
            background: var(--primary-blue);
            border: 2px solid var(--primary-blue);
            color: var(--white);
            border-radius: 20px;
            padding: 8px 20px;
            font-weight: 600;
            transition: var(--transition);
            margin-right: 10px;
        }

        .navbar .btn-primary:hover {
            background: var(--dark-blue);
            border-color: var(--dark-blue);
            color: var(--white);
        }

        .navbar .contact-email {
            color: var(--white) !important;
            text-decoration: none;
            font-weight: 500;
            transition: var(--transition);
        }

        .navbar.scrolled .contact-email {
            color: var(--primary-blue) !important;
        }

        .navbar .contact-email:hover {
            color: var(--accent-blue) !important;
        }

        /* ===== HERO SECTION ===== */
        .hero-section {
            background: linear-gradient(rgba(0, 61, 130, 0.7), rgba(0, 86, 179, 0.7)), 
                        url('assets/img/Background.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: var(--white);
            padding: 120px 0 100px 0;
            position: relative;
            overflow: hidden;
            min-height: 100vh;
            display: flex;
            align-items: center;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1000 1000"><polygon fill="rgba(255,255,255,0.05)" points="0,1000 1000,800 1000,1000"/></svg>');
            background-size: cover;
        }

        .hero-content {
            position: relative;
            z-index: 2;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
            line-height: 1.1;
        }

        .hero-subtitle {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 1rem;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
            line-height: 1.2;
        }

        .hero-year {
            font-size: 3rem;
            font-weight: 800;
            color: #FFC107;
            margin-bottom: 2rem;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.4);
        }

        .hero-description {
            font-size: 1.1rem;
            font-weight: 300;
            margin-bottom: 2.5rem;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.3);
            opacity: 0.9;
            max-width: 600px;
        }

        .hero-cta {
            background: rgba(255, 255, 255, 0.95);
            color: var(--primary-blue);
            border: none;
            padding: 12px 30px;
            border-radius: var(--border-radius-small);
            font-weight: 600;
            font-size: 1rem;
            transition: var(--transition);
            text-decoration: none;
            display: inline-block;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .hero-cta:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.3);
            color: var(--primary-blue);
        }

        /* ===== FEATURE CARDS ===== */
        .features-section {
            margin-top: 80px;
            position: relative;
            z-index: 3;
            padding: 80px 0;
        }

        .feature-card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 30px;
            text-align: center;
            box-shadow: var(--shadow-light);
            transition: var(--transition);
            height: 100%;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-medium);
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            background: var(--light-blue);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: var(--primary-blue);
            font-size: 2rem;
        }

        /* ===== REGISTRATION SECTION ===== */
        .registration-section {
            background: var(--light-blue);
            padding: 80px 0;
        }

        /* ===== PRICE CARDS - FIXED FOR 3 CARDS LAYOUT ===== */
        .pricing-container {
            display: flex;
            justify-content: center;
            align-items: stretch;
            gap: 30px;
            flex-wrap: wrap;
        }

        .price-card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 40px 30px;
            text-align: center;
            box-shadow: var(--shadow-light);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            cursor: default;
            flex: 0 0 300px;
            max-width: 350px;
            min-height: 400px;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .price-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-heavy);
        }

        .price-card.featured {
            border: 3px solid var(--primary-blue);
            transform: scale(1.05);
        }

        .price-card.featured:hover {
            transform: scale(1.05) translateY(-5px);
        }

        .price-card.featured::before {
            content: 'POPULAR';
            position: absolute;
            top: 20px;
            right: -30px;
            background: var(--primary-blue);
            color: var(--white);
            padding: 5px 50px;
            transform: rotate(45deg);
            font-size: 0.8rem;
            font-weight: bold;
        }

        .price {
            font-size: 2.5rem;
            font-weight: bold;
            color: var(--primary-blue);
            margin: 20px 0;
        }

        .price-card .feature-icon {
            margin-bottom: 20px;
        }

        .price-card h4 {
            margin-bottom: 15px;
        }

        .price-card .list-unstyled {
            flex-grow: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        /* ===== LAYOUT COMPONENTS ===== */
        .map-container {
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--shadow-light);
            height: 400px;
        }

        .section-title {
            color: var(--dark-blue);
            font-weight: 700;
            margin-bottom: 50px;
            position: relative;
            padding-bottom: 15px;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: 0;
            left: 50%;
            transform: translateX(-50%);
            width: 80px;
            height: 4px;
            background: var(--primary-blue);
            border-radius: 2px;
        }

        /* ===== FOOTER ===== */
        .footer {
            background: var(--dark-blue);
            color: var(--white);
            padding: 50px 0 30px;
        }

        /* ===== ANIMATIONS ===== */
        .animate-on-scroll {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease;
        }

        .animate-on-scroll.animated {
            opacity: 1;
            transform: translateY(0);
        }

        /* ===== RESPONSIVE DESIGN ===== */
        @media (max-width: 992px) {
            .pricing-container {
                flex-direction: column;
                align-items: center;
            }

            .price-card {
                flex: none;
                width: 100%;
                max-width: 400px;
                margin-bottom: 30px;
            }

            .price-card.featured {
                transform: none;
                margin: 0 0 30px 0;
            }

            .price-card.featured:hover {
                transform: translateY(-5px);
            }
        }

        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.2rem;
            }
            
            .hero-subtitle {
                font-size: 1.8rem;
            }

            .hero-year {
                font-size: 2rem;
            }

            .hero-description {
                font-size: 1rem;
            }
            
            .feature-card {
                margin-bottom: 30px;
            }

            .features-section {
                margin-top: 50px;
                padding: 50px 0;
            }

            .navbar-nav {
                text-align: center;
                margin-top: 1rem;
            }

            .animate-on-scroll {
                padding-left: 0 !important;
            }

            .navbar .d-flex {
                flex-direction: column;
                gap: 10px;
                margin-top: 1rem;
            }

            .pricing-container {
                gap: 20px;
            }

            .price-card {
                padding: 30px 20px;
                min-height: 350px;
            }

            .price {
                font-size: 2rem;
            }
        }

        @media (min-width: 1200px) {
            .pricing-container {
                max-width: 1000px;
                margin: 0 auto;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark fixed-top">
        <div class="container">
            <a class="navbar-brand" href="#home"><i class="fas fa-graduation-cap me-2"></i>SNIA</a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav mx-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#home">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Fitur</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#register">Paket</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#location">Lokasi</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <a href="mailto:snia@unjani.ac.id" class="contact-email">
                        <i class="fas fa-envelope me-1"></i>snia@unjani.ac.id
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section" id="home">
        <div class="hero-content">
            <div class="container">
                <div class="row justify-content-center">
                    <div class="col-lg-8 col-xl-7">
                        <div class="animate-on-scroll" style="padding-left: 0;">
                            <h1 class="hero-title">Seminar Nasional Informatika dan Aplikasinya</h1>
                            <div class="hero-year">(SNIA) 2025</div>
                            <p class="hero-description">Diselenggarakan oleh Jurusan Informatika Universitas Jenderal Achmad Yani (UNJANI), acara dua tahunan yang mempertemukan akademisi, peneliti, dan praktisi untuk berbagi pengetahuan dan inovasi terdepan di bidang teknologi informasi.</p>
                            <a href="auth/login" class="hero-cta">MASUK SEKARANG</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section" id="features">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title animate-on-scroll">Fitur Platform Digital</h2>
                <p class="lead animate-on-scroll">Teknologi terdepan untuk mendukung seminar nasional modern</p>
            </div>
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="feature-card animate-on-scroll">
                        <div class="feature-icon">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Abstrak & Paper</h4>
                        <p class="text-muted">Upload abstrak & full paper dengan mudah, pantau status review secara real-time dengan sistem yang terintegrasi.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card animate-on-scroll">
                        <div class="feature-icon">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Pembayaran Digital</h4>
                        <p class="text-muted">Integrasi payment gateway lengkap & sistem voucher, pembayaran lebih cepat, aman, dan mudah.</p>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="feature-card animate-on-scroll">
                        <div class="feature-icon">
                            <i class="fas fa-certificate"></i>
                        </div>
                        <h4 class="fw-bold mb-3">Sertifikat Digital</h4>
                        <p class="text-muted">Dapatkan LOA & sertifikat kehadiran otomatis dengan QR code verification setelah seminar selesai.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Registration Section -->
    <section class="registration-section" id="register">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title animate-on-scroll">Paket Registrasi</h2>
                <p class="lead animate-on-scroll">Pilih paket yang sesuai dengan kebutuhan Anda</p>
            </div>
            
            <div class="pricing-container mb-5">
                <div class="price-card animate-on-scroll">
                    <div>
                        <div class="feature-icon mb-3">
                            <i class="fas fa-microphone"></i>
                        </div>
                        <h4 class="fw-bold">Presenter</h4>
                        <div class="price">Rp 350K</div>
                    </div>
                    <ul class="list-unstyled mb-4">
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Submit Abstract</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Present Paper</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Certificate</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Lunch & Coffee</li>
                    </ul>
                </div>
                
                <div class="price-card featured animate-on-scroll">
                    <div>
                        <div class="feature-icon mb-3">
                            <i class="fas fa-users"></i>
                        </div>
                        <h4 class="fw-bold">Audience Offline</h4>
                        <div class="price">Rp 150K</div>
                    </div>
                    <ul class="list-unstyled mb-4">
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Attend Seminar</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Certificate</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Lunch & Coffee</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Networking</li>
                    </ul>
                </div>
                
                <div class="price-card animate-on-scroll">
                    <div>
                        <div class="feature-icon mb-3">
                            <i class="fas fa-laptop"></i>
                        </div>
                        <h4 class="fw-bold">Audience Online</h4>
                        <div class="price">Rp 75K</div>
                    </div>
                    <ul class="list-unstyled mb-4">
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Live Streaming</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Digital Certificate</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Recording Access</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Q&A Session</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Location Section -->
    <section class="py-5" id="location">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title animate-on-scroll">Lokasi Acara</h2>
                <p class="lead animate-on-scroll">Universitas Jenderal Achmad Yani, Cimahi - Jawa Barat</p>
            </div>
            
            <div class="row g-5 align-items-center">
                <div class="col-lg-6">
                    <div class="map-container animate-on-scroll">
                        <iframe 
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3961.0244864384695!2d107.5291975147727!3d-6.8792935950655935!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e68e569e4b3b5a5%3A0x401e8f1fc28b750!2sUnjani%20(Universitas%20Jenderal%20Achmad%20Yani)!5e0!3m2!1sen!2sid!4v1640234567890!5m2!1sen!2sid"
                            width="100%" 
                            height="400" 
                            style="border:0;" 
                            allowfullscreen="" 
                            loading="lazy">
                        </iframe>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="animate-on-scroll">
                        <h3 class="fw-bold mb-4">Detail Lokasi</h3>
                        <div class="mb-4">
                            <h5><i class="fas fa-map-marker-alt text-primary me-2"></i>Alamat</h5>
                            <p class="text-muted ms-4">Jl. Terusan Jend. Sudirman, Cimahi Utara, Kota Cimahi, Jawa Barat 40285</p>
                        </div>
                        <div class="mb-4">
                            <h5><i class="fas fa-calendar text-primary me-2"></i>Tanggal & Waktu</h5>
                            <p class="text-muted ms-4">15 Desember 2025<br>08:00 - 17:00 WIB</p>
                        </div>
                        <div class="mb-4">
                            <h5><i class="fas fa-car text-primary me-2"></i>Transportasi</h5>
                            <p class="text-muted ms-4">Tersedia shuttle bus dari Stasiun Cimahi dan area parkir yang luas untuk peserta.</p>
                        </div>
                        <a href="https://www.google.com/maps/dir//Unjani+(Universitas+Jenderal+Achmad+Yani),+Jl.+Terusan+Jend.+Sudirman,+Cimahi+Utara,+Kota+Cimahi,+Jawa+Barat+40285/@-6.8792936,107.5291975,17z/data=!4m8!4m7!1m0!1m5!1m1!1s0x2e68e569e4b3b5a5:0x401e8f1fc28b750!2m2!1d107.5317724!2d-6.8792936" class="btn btn-primary" target="_blank" rel="noopener noreferrer">
                            <i class="fas fa-directions me-2"></i>Lihat Rute
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h5 class="fw-bold mb-3">SNIA 2025</h5>
                    <p class="text-light">Seminar Nasional Informatika & Aplikasi yang diselenggarakan oleh Jurusan Informatika UNJANI Cimahi. Kegiatan dua tahunan untuk membahas perkembangan informatika dan aplikasinya.</p>
                </div>
                <div class="col-lg-4 mb-4">
                    <h5 class="fw-bold mb-3">Penyelenggara</h5>
                    <p class="text-light mb-2"><i class="fas fa-university me-2"></i>Jurusan Informatika UNJANI</p>
                    <p class="text-light mb-2"><i class="fas fa-map-marker-alt me-2"></i>Universitas Jenderal Achmad Yani, Cimahi</p>
                    <p class="text-light"><i class="fas fa-calendar me-2"></i>Setiap 2 tahun sekali</p>
                </div>
                <div class="col-lg-4 mb-4">
                    <h5 class="fw-bold mb-3">Kontak Informasi</h5>
                    <p class="text-light mb-2"><i class="fas fa-envelope me-2"></i>snia@unjani.ac.id</p>
                    <p class="text-light mb-2"><i class="fas fa-phone me-2"></i>+62 22 6656 186</p>
                    <p class="text-light"><i class="fas fa-globe me-2"></i>www.unjani.ac.id</p>
                    <div class="d-flex gap-3 mt-3">
                        <a href="#" class="text-light fs-4"><i class="fab fa-facebook"></i></a>
                        <a href="#" class="text-light fs-4"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-light fs-4"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-light fs-4"><i class="fab fa-linkedin"></i></a>
                    </div>
                </div>
            </div>
            <hr class="my-4" style="border-color: rgba(255,255,255,0.2);">
            <div class="text-center">
                <p class="mb-0 text-light">© 2025 SNIA - Jurusan Informatika UNJANI</p>
            </div>
        </div>
    </footer>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/js/bootstrap.bundle.min.js"></script>
    <script>
        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    const offsetTop = target.offsetTop - 80;
                    window.scrollTo({
                        top: offsetTop,
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Animation on scroll
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animated');
                }
            });
        }, observerOptions);

        document.querySelectorAll('.animate-on-scroll').forEach(el => {
            observer.observe(el);
        });

        // Enhanced navbar background on scroll with glass effect
        window.addEventListener('scroll', () => {
            const navbar = document.querySelector('.navbar');
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    </script>
</body>
</html>