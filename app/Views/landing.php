<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SNIA - Seminar Nasional Informatika</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-blue: #1e88e5;
            --light-blue: #e3f2fd;
            --dark-blue: #0d47a1;
            --accent-blue: #2196f3;
            --white: #ffffff;
            --text-dark: #263238;
            --text-muted: #607d8b;
            --shadow-light: 0 10px 30px rgba(30, 136, 229, 0.1);
            --shadow-medium: 0 20px 40px rgba(30, 136, 229, 0.15);
            --shadow-heavy: 0 25px 50px rgba(30, 136, 229, 0.2);
            --border-radius: 15px;
            --border-radius-small: 25px;
            --border-radius-large: 50px;
            --transition: all 0.3s ease;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--text-dark);
        }

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

        .hero-section {
            background: linear-gradient(rgba(0, 61, 130, 0.7), rgba(0, 86, 179, 0.7)), 
                        url('<?= base_url('assets/img/Background.jpg') ?>');
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

        .poster-section {
            margin-top: 0;
            padding-top: 80px !important;
            background: var(--white);
        }

        .poster-card {
            background: var(--white);
            border-radius: var(--border-radius);
            padding: 20px;
            box-shadow: var(--shadow-medium);
            transition: var(--transition);
            overflow: hidden;
        }

        .poster-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-heavy);
        }

        .poster-image {
            width: 100%;
            height: auto;
            border-radius: 10px;
            display: block;
        }

        .features-section {
            margin-top: -80px;
            position: relative;
            z-index: 3;
            padding: 0 0 80px 0;
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

        .registration-section {
            background: var(--light-blue);
            padding: 80px 0;
        }

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

        .speaker-section {
            background: var(--white);
            padding: 80px 0;
            border-top: 3px solid var(--light-blue);
            border-bottom: 3px solid var(--light-blue);
        }

        .speaker-card {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--accent-blue) 100%);
            border-radius: var(--border-radius);
            padding: 30px;
            box-shadow: var(--shadow-medium);
            transition: var(--transition);
            text-align: center;
            height: 100%;
            color: var(--white);
        }

        .speaker-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-heavy);
        }

        .speaker-img {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            margin: 0 auto 20px;
            border: 5px solid var(--white);
            display: block;
        }

        .speaker-name {
            font-size: 1.3rem;
            font-weight: 700;
            color: var(--white);
            margin-bottom: 10px;
        }

        .speaker-title {
            font-size: 1rem;
            color: rgba(255, 255, 255, 0.9);
            margin-bottom: 15px;
        }

        .speaker-bio {
            font-size: 0.9rem;
            line-height: 1.6;
            color: rgba(255, 255, 255, 0.85);
        }

        .voucher-section {
            background: var(--light-blue);
            padding: 80px 0;
        }

        .voucher-card {
            background: linear-gradient(135deg, var(--primary-blue) 0%, var(--accent-blue) 100%);
            border-radius: var(--border-radius);
            padding: 25px;
            box-shadow: var(--shadow-medium);
            transition: var(--transition);
            position: relative;
            overflow: hidden;
            color: var(--white);
        }

        .voucher-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-heavy);
        }

        .voucher-badge {
            position: absolute;
            top: 15px;
            right: 15px;
            background: #ff5252;
            color: var(--white);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .voucher-code {
            font-size: 1.8rem;
            font-weight: 800;
            color: var(--white);
            letter-spacing: 2px;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
            background: rgba(255, 255, 255, 0.2);
            padding: 10px;
            border-radius: 10px;
        }

        .voucher-discount {
            font-size: 2rem;
            font-weight: 700;
            color: #FFD54F;
            margin: 10px 0;
        }

        .sponsor-section {
            background: var(--white);
            padding: 80px 0;
        }

        .sponsor-card {
            background: var(--white);
            border: 2px solid var(--light-blue);
            border-radius: var(--border-radius);
            padding: 30px;
            text-align: center;
            box-shadow: var(--shadow-light);
            transition: var(--transition);
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .sponsor-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-medium);
            border-color: var(--primary-blue);
        }

        .sponsor-logo {
            max-width: 100%;
            height: auto;
            max-height: 100px;
            filter: grayscale(100%);
            transition: var(--transition);
        }

        .sponsor-card:hover .sponsor-logo {
            filter: grayscale(0%);
        }

        .sponsor-tier-title {
            background: linear-gradient(135deg, var(--primary-blue), var(--accent-blue));
            color: var(--white);
            padding: 10px 30px;
            border-radius: 30px;
            display: inline-block;
            font-weight: 600;
            margin-bottom: 30px;
        }

        .deadline-info {
            background: var(--light-blue);
            padding: 15px;
            border-radius: 10px;
            margin-top: 20px;
        }

        .deadline-badge {
            display: inline-block;
            background: #ff9800;
            color: var(--white);
            padding: 8px 15px;
            border-radius: 20px;
            font-weight: 600;
            margin-bottom: 10px;
        }

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

        .section-title.text-white::after {
            background: var(--white);
        }

        .footer {
            background: var(--dark-blue);
            color: var(--white);
            padding: 50px 0 30px;
        }

        .animate-on-scroll {
            opacity: 0;
            transform: translateY(30px);
            transition: all 0.6s ease;
        }

        .animate-on-scroll.animated {
            opacity: 1;
            transform: translateY(0);
        }

        .no-event-notice {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: var(--white);
            padding: 60px 30px;
            border-radius: var(--border-radius);
            text-align: center;
            margin: 80px 0;
        }

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
                margin-top: -50px;
                padding: 0 0 50px 0;
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

            .poster-section {
                padding-top: 50px !important;
            }
        }

        @media (min-width: 1200px) {
            .pricing-container {
                max-width: 1100px;
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
                        <a class="nav-link" href="#poster">Poster</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#features">Fitur</a>
                    </li>
                    <?php if ($activeEvent): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="#register">Paket</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#speakers">Pembicara</a>
                    </li>
                    <?php if (!empty($activeVouchers)): ?>
                    <li class="nav-item">
                        <a class="nav-link" href="#vouchers">Voucher</a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="#sponsors">Sponsor</a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link" href="#location">Lokasi</a>
                    </li>
                </ul>
                <div class="d-flex align-items-center">
                    <a href="<?= base_url('auth/login') ?>" class="btn btn-primary">Masuk</a>
                    <a href="mailto:snia@unjani.ac.id" class="contact-email ms-3">
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
                                <div class="hero-year">(SNIA) <?= date('Y') ?></div>
                                <p class="hero-description">Diselenggarakan oleh Jurusan Informatika Universitas Jenderal Achmad Yani (UNJANI), acara dua tahunan yang mempertemukan akademisi, peneliti, dan praktisi untuk berbagi pengetahuan dan inovasi terdepan di bidang teknologi informasi.</p>
                                <a href="<?= base_url('auth/login') ?>" class="hero-cta">DAFTAR SEKARANG</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Poster Section -->
    <?php if ($activeEvent): ?>
    <section class="poster-section py-5" id="poster">
        <div class="container">
            <div class="text-center mb-4">
                <h2 class="section-title animate-on-scroll">Poster Event</h2>
                <p class="lead animate-on-scroll">Informasi lengkap tentang SNIA <?= date('Y', strtotime($activeEvent['event_date'])) ?></p>
            </div>
            <div class="row justify-content-center">
                <div class="col-lg-8 col-md-10">
                    <div class="poster-card animate-on-scroll">
                        <img src="<?= base_url('assets/img/poster.png') ?>" alt="Poster SNIA <?= date('Y', strtotime($activeEvent['event_date'])) ?>" class="poster-image">
                    </div>
                </div>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <?php if ($activeEvent): ?>
    <!-- Registration Section -->
    <section class="registration-section" id="register">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title animate-on-scroll">Paket Registrasi</h2>
                <p class="lead animate-on-scroll">Pilih paket yang sesuai dengan kebutuhan Anda</p>
                <?php if ($activeEvent['event_date']): ?>
                <div class="deadline-info animate-on-scroll d-inline-block">
                    <div class="deadline-badge">
                        <i class="fas fa-calendar-alt me-2"></i>Tanggal Event: <?= $activeEvent['event_date_formatted'] ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            
            <div class="pricing-container mb-5">
                <?php if ($activeEvent['format'] === 'both' || $activeEvent['format'] === 'offline'): ?>
                <!-- Presenter -->
                <div class="price-card animate-on-scroll">
                    <div>
                        <div class="feature-icon mb-3">
                            <i class="fas fa-microphone"></i>
                        </div>
                        <h4 class="fw-bold">Presenter</h4>
                        <div class="price">Rp <?= number_format($activeEvent['presenter_fee_offline'], 0, ',', '.') ?></div>
                    </div>
                    <ul class="list-unstyled mb-4">
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Submit Abstract</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Present Paper</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Certificate</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Lunch & Coffee</li>
                    </ul>
                </div>

                <!-- Audience Offline -->
                <div class="price-card <?= ($activeEvent['format'] === 'both') ? 'featured' : '' ?> animate-on-scroll">
                    <div>
                        <div class="feature-icon mb-3">
                            <i class="fas fa-users"></i>
                        </div>
                        <h4 class="fw-bold">Audience Offline</h4>
                        <div class="price">Rp <?= number_format($activeEvent['audience_fee_offline'], 0, ',', '.') ?></div>
                    </div>
                    <ul class="list-unstyled mb-4">
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Attend Seminar</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Certificate</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Lunch & Coffee</li>
                       <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Networking</li>
                    </ul>
                </div>
                <?php endif; ?>
                
                <?php if ($activeEvent['format'] === 'both' || $activeEvent['format'] === 'online'): ?>
                <!-- Audience Online -->
                <div class="price-card <?= ($activeEvent['format'] === 'online') ? 'featured' : '' ?> animate-on-scroll">
                    <div>
                        <div class="feature-icon mb-3">
                            <i class="fas fa-laptop"></i>
                        </div>
                        <h4 class="fw-bold">Audience Online</h4>
                        <div class="price">Rp <?= number_format($activeEvent['audience_fee_online'], 0, ',', '.') ?></div>
                    </div>
                    <ul class="list-unstyled mb-4">
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Live Streaming</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Digital Certificate</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Recording Access</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Q&A Session</li>
                    </ul>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- Speaker Section -->
    <section class="speaker-section" id="speakers">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title animate-on-scroll">Pembicara & Narasumber</h2>
                <p class="lead animate-on-scroll">Para ahli dan praktisi terkemuka di bidang informatika</p>
            </div>
            
            <div class="row g-4">
                <!-- Speaker 1 -->
                <div class="col-md-6 col-lg-3">
                    <div class="speaker-card animate-on-scroll">
                        <img src="<?= base_url('assets/img/speakers/speaker1.jpg') ?>" alt="Dr. Ahmad Ridwan" class="speaker-img">
                        <h5 class="speaker-name">Dr. Ahmad Ridwan</h5>
                        <p class="speaker-title">Keynote Speaker</p>
                        <p class="speaker-bio">Pakar AI & Machine Learning dari Institut Teknologi Bandung dengan pengalaman riset lebih dari 15 tahun.</p>
                    </div>
                </div>

                <!-- Speaker 2 -->
                <div class="col-md-6 col-lg-3">
                    <div class="speaker-card animate-on-scroll">
                        <img src="<?= base_url('assets/img/speakers/speaker2.jpg') ?>" alt="Prof. Siti Nurhaliza" class="speaker-img">
                        <h5 class="speaker-name">Prof. Siti Nurhaliza</h5>
                        <p class="speaker-title">Expert Speaker</p>
                        <p class="speaker-bio">Profesor Cyber Security dari Universitas Indonesia, spesialis keamanan jaringan dan kriptografi.</p>
                    </div>
                </div>

                <!-- Speaker 3 -->
                <div class="col-md-6 col-lg-3">
                    <div class="speaker-card animate-on-scroll">
                        <img src="<?= base_url('assets/img/speakers/speaker3.jpg') ?>" alt="Dr. Budi Santoso" class="speaker-img">
                        <h5 class="speaker-name">Dr. Budi Santoso</h5>
                        <p class="speaker-title">Technology Leader</p>
                        <p class="speaker-bio">CTO PT. Telkom Indonesia, pionir dalam implementasi IoT dan Smart City di Indonesia.</p>
                    </div>
                </div>

                <!-- Speaker 4 -->
                <div class="col-md-6 col-lg-3">
                    <div class="speaker-card animate-on-scroll">
                        <img src="<?= base_url('assets/img/speakers/speaker4.jpg') ?>" alt="Dr. Maya Anggraini" class="speaker-img">
                        <h5 class="speaker-name">Dr. Maya Anggraini</h5>
                        <p class="speaker-title">Data Scientist</p>
                        <p class="speaker-bio">Lead Data Scientist di Gojek, expert dalam Big Data Analytics dan Business Intelligence.</p>
                    </div>
                </div>
            </div>

            <div class="text-center mt-5">
                <p class="mb-0 text-muted">
                    <i class="fas fa-info-circle me-2"></i>
                    Dan masih banyak pembicara tamu lainnya yang akan hadir
                </p>
            </div>
        </div>
    </section>

    <!-- Voucher Section -->
    <?php if (!empty($activeVouchers)): ?>
    <section class="voucher-section" id="vouchers">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title animate-on-scroll">Voucher Diskon Tersedia!</h2>
                <p class="lead animate-on-scroll">Dapatkan potongan harga dengan menggunakan voucher berikut</p>
            </div>
            
            <div class="row g-4">
                <?php foreach ($activeVouchers as $voucher): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="voucher-card animate-on-scroll">
                        <div class="voucher-badge">
                            <i class="fas fa-fire me-1"></i>Terbatas
                        </div>
                        
                        <div class="text-center mb-3">
                            <i class="fas fa-ticket-alt" style="font-size: 3rem;"></i>
                        </div>
                        
                        <div class="voucher-code text-center">
                            <?= esc($voucher['kode_voucher']) ?>
                        </div>
                        
                        <div class="voucher-discount text-center">
                            <?php if ($voucher['tipe'] === 'percentage'): ?>
                                Diskon <?= number_format($voucher['nilai'], 0) ?>%
                            <?php else: ?>
                                Diskon Rp <?= number_format($voucher['nilai'], 0, ',', '.') ?>
                            <?php endif; ?>
                        </div>
                        
                        <hr style="border-color: rgba(255,255,255,0.3);">
                        
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span><i class="fas fa-calendar-check me-2"></i>Berlaku Hingga:</span>
                            <strong><?= date('d/m/Y', strtotime($voucher['masa_berlaku'])) ?></strong>
                        </div>
                        
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <span><i class="fas fa-users me-2"></i>Kuota Tersisa:</span>
                            <strong class="<?= $voucher['remaining'] <= 5 ? 'text-warning' : '' ?>">
                                <?= $voucher['remaining'] ?> / <?= $voucher['kuota'] ?>
                            </strong>
                        </div>
                        
                        <?php if ($voucher['remaining'] <= 5): ?>
                        <div class="alert alert-warning mt-3 mb-0 py-2">
                            <i class="fas fa-exclamation-triangle me-1"></i> Segera habis!
                        </div>
                        <?php endif; ?>
                        
                        <div class="text-center mt-3">
                            <small>Gunakan kode ini saat pendaftaran</small>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center mt-5">
                <p class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    Voucher dapat digunakan saat melakukan pembayaran. Pastikan Anda login terlebih dahulu.
                </p>
            </div>
        </div>
    </section>
    <?php endif; ?>
    <?php else: ?>
    <!-- No Event Notice -->
    <section class="py-5">
        <div class="container">
            <div class="no-event-notice animate-on-scroll">
                <i class="fas fa-calendar-times" style="font-size: 5rem; margin-bottom: 30px;"></i>
                <h2 class="fw-bold mb-4">Belum Ada Event Aktif</h2>
                <p class="lead mb-4">Saat ini belum ada event yang dibuka untuk pendaftaran. Silakan cek kembali nanti atau hubungi kami untuk informasi lebih lanjut.</p>
                <a href="mailto:snia@unjani.ac.id" class="btn btn-light btn-lg">
                    <i class="fas fa-envelope me-2"></i>Hubungi Kami
                </a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Sponsor Section -->
    <section class="sponsor-section" id="sponsors">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title animate-on-scroll">Sponsor & Partner</h2>
                <p class="lead animate-on-scroll">Terima kasih kepada para sponsor dan partner yang mendukung SNIA</p>
            </div>
            
            <!-- Main Sponsor -->
            <div class="text-center mb-4">
                <span class="sponsor-tier-title animate-on-scroll">
                    <i class="fas fa-crown me-2"></i>Main Sponsor
                </span>
            </div>
            <div class="row g-4 justify-content-center mb-5">
                <div class="col-md-4">
                    <div class="sponsor-card animate-on-scroll">
                        <img src="<?= base_url('assets/img/sponsors/BCA.png') ?>" alt="Main Sponsor" class="sponsor-logo">
                    </div>
                </div>
            </div>

            <!-- Gold Sponsor -->
            <div class="text-center mb-4">
                <span class="sponsor-tier-title animate-on-scroll">
                    <i class="fas fa-medal me-2"></i>Gold Sponsor
                </span>
            </div>
            <div class="row g-4 justify-content-center mb-5">
                <div class="col-md-3 col-sm-6">
                    <div class="sponsor-card animate-on-scroll">
                        <img src="<?= base_url('assets/img/sponsors/BNI.png') ?>" alt="Gold Sponsor 1" class="sponsor-logo">
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="sponsor-card animate-on-scroll">
                        <img src="<?= base_url('assets/img/sponsors/BCA.png') ?>" alt="Gold Sponsor 2" class="sponsor-logo">
                    </div>
                </div>
                <div class="col-md-3 col-sm-6">
                    <div class="sponsor-card animate-on-scroll">
                        <img src="<?= base_url('assets/img/sponsors/DANA.jpg') ?>" alt="Gold Sponsor 3" class="sponsor-logo">
                    </div>
                </div>
            </div>

            <!-- Silver Sponsor -->
            <div class="text-center mb-4">
                <span class="sponsor-tier-title animate-on-scroll">
                    <i class="fas fa-certificate me-2"></i>Silver Sponsor
                </span>
            </div>
            <div class="row g-4 justify-content-center mb-5">
                <div class="col-md-2 col-sm-4 col-6">
                    <div class="sponsor-card animate-on-scroll">
                        <img src="<?= base_url('assets/img/sponsors/unjani.png') ?>" alt="Silver Sponsor 1" class="sponsor-logo" style="max-height: 80px;">
                    </div>
                </div>
                <div class="col-md-2 col-sm-4 col-6">
                    <div class="sponsor-card animate-on-scroll">
                        <img src="<?= base_url('assets/img/sponsors/unjani.png') ?>" alt="Silver Sponsor 2" class="sponsor-logo" style="max-height: 80px;">
                    </div>
                </div>
                <div class="col-md-2 col-sm-4 col-6">
                    <div class="sponsor-card animate-on-scroll">
                        <img src="<?= base_url('assets/img/sponsors/unjani.png') ?>" alt="Silver Sponsor 3" class="sponsor-logo" style="max-height: 80px;">
                    </div>
                </div>
                <div class="col-md-2 col-sm-4 col-6">
                    <div class="sponsor-card animate-on-scroll">
                        <img src="<?= base_url('assets/img/sponsors/unjani.png') ?>" alt="Silver Sponsor 4" class="sponsor-logo" style="max-height: 80px;">
                    </div>
                </div>
                <div class="col-md-2 col-sm-4 col-6">
                    <div class="sponsor-card animate-on-scroll">
                        <img src="<?= base_url('assets/img/sponsors/unjani.png') ?>" alt="Silver Sponsor 5" class="sponsor-logo" style="max-height: 80px;">
                    </div>
                </div>
            </div>

            <!-- Media Partner -->
            <div class="text-center mb-4">
                <span class="sponsor-tier-title animate-on-scroll">
                    <i class="fas fa-bullhorn me-2"></i>Media Partner
                </span>
            </div>
            <div class="row g-4 justify-content-center">
                <div class="col-md-2 col-sm-4 col-6">
                    <div class="sponsor-card animate-on-scroll">
                        <img src="<?= base_url('assets/img/sponsors/BERITA.png') ?>" alt="Media Partner 1" class="sponsor-logo" style="max-height: 60px;">
                    </div>
                </div>
                <div class="col-md-2 col-sm-4 col-6">
                    <div class="sponsor-card animate-on-scroll">
                        <img src="<?= base_url('assets/img/sponsors/BERITA.png') ?>" alt="Media Partner 2" class="sponsor-logo" style="max-height: 60px;">
                    </div>
                </div>
                <div class="col-md-2 col-sm-4 col-6">
                    <div class="sponsor-card animate-on-scroll">
                        <img src="<?= base_url('assets/img/sponsors/BERITA.png') ?>" alt="Media Partner 3" class="sponsor-logo" style="max-height: 60px;">
                    </div>
                </div>
                <div class="col-md-2 col-sm-4 col-6">
                    <div class="sponsor-card animate-on-scroll">
                        <img src="<?= base_url('assets/img/sponsors/BERITA.png') ?>" alt="Media Partner 4" class="sponsor-logo" style="max-height: 60px;">
                    </div>
                </div>
            </div>

            <div class="text-center mt-5">
                <p class="text-muted">
                    <i class="fas fa-handshake me-2"></i>
                    Tertarik menjadi sponsor? Hubungi kami di <a href="mailto:sponsor@snia.unjani.ac.id" class="text-primary">sponsor@snia.unjani.ac.id</a>
                </p>
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
                            src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!3d3961.0244864384695!2d107.5291975147727!3d-6.8792935950655935!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e68e569e4b3b5a5%3A0x401e8f1fc28b750!2sUnjani%20(Universitas%20Jenderal%20Achmad%20Yani)!5e0!3m2!1sen!2sid!4v1640234567890!5m2!1sen!2sid"
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
                        <?php if ($activeEvent && $activeEvent['event_date']): ?>
                        <div class="mb-4">
                            <h5><i class="fas fa-calendar text-primary me-2"></i>Tanggal & Waktu</h5>
                            <p class="text-muted ms-4">
                                <?= $activeEvent['event_date_formatted'] ?><br>
                                <?= date('H:i', strtotime($activeEvent['event_time'])) ?> WIB
                            </p>
                        </div>
                        <?php if ($activeEvent['location']): ?>
                        <div class="mb-4">
                            <h5><i class="fas fa-building text-primary me-2"></i>Tempat</h5>
                            <p class="text-muted ms-4"><?= esc($activeEvent['location']) ?></p>
                        </div>
                        <?php endif; ?>
                        <?php if ($activeEvent['zoom_link'] && ($activeEvent['format'] === 'online' || $activeEvent['format'] === 'both')): ?>
                        <div class="mb-4">
                            <h5><i class="fas fa-video text-primary me-2"></i>Link Online</h5>
                            <p class="text-muted ms-4">Link akan dikirim setelah pembayaran terverifikasi</p>
                        </div>
                        <?php endif; ?>
                        <?php endif; ?>
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

    <!-- CTA Section -->
    <?php if ($activeEvent): ?>
    <section class="py-5" style="background: var(--primary-blue);">
        <div class="container text-center text-white">
            <div class="animate-on-scroll">
                <h2 class="fw-bold mb-4">Siap Bergabung dengan SNIA <?= date('Y', strtotime($activeEvent['event_date'])) ?>?</h2>
                <p class="lead mb-4">Jangan lewatkan kesempatan untuk berbagi pengetahuan dan bernetworking dengan para ahli informatika terkemuka.</p>
                <?php if ($activeEvent['registration_deadline']): ?>
                <div class="alert alert-warning d-inline-block mb-4">
                    <i class="fas fa-clock me-2"></i><strong>Pendaftaran Ditutup:</strong> <?= $activeEvent['registration_deadline_formatted'] ?>
                </div>
                <br>
                <?php endif; ?>
                <a href="<?= base_url('auth/register') ?>" class="btn btn-light btn-lg px-5 py-3 rounded-pill">
                    <i class="fas fa-user-plus me-2"></i>Daftar Sekarang
                </a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="footer">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h5 class="fw-bold mb-3">SNIA <?= $activeEvent ? date('Y', strtotime($activeEvent['event_date'])) : '2025' ?></h5>
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
                <p class="mb-0 text-light">&copy; <?= date('Y') ?> SNIA - Jurusan Informatika UNJANI</p>
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

        // Auto-close navbar on mobile when clicking a link
        const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
        const navbarCollapse = document.querySelector('.navbar-collapse');
        
        navLinks.forEach(link => {
            link.addEventListener('click', () => {
                if (window.innerWidth < 992) {
                    const bsCollapse = new bootstrap.Collapse(navbarCollapse, {
                        toggle: false
                    });
                    bsCollapse.hide();
                }
            });
        });
    </script>
</body>
</html>