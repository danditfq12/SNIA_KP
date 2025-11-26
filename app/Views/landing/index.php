<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SNIA - Seminar Nasional Informatika</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/landing.css') ?>" rel="stylesheet">
</head>
<?= $this->include('partials/navbar') ?>
<body>
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
    <!-- Registration Section with Early Bird -->
    <section class="registration-section" id="register">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title animate-on-scroll">Paket Registrasi Early Bird</h2>
                <p class="lead animate-on-scroll">Dapatkan harga terbaik dengan mendaftar sekarang!</p>
                
                <div class="d-flex flex-wrap justify-content-center gap-3 mt-3">
                    <?php if (!empty($activeEvent['early_bird_deadline_formatted'])): ?>
                    <div class="deadline-info animate-on-scroll">
                        <div class="deadline-badge" style="background: linear-gradient(135deg, #ff5722, #ff9800);">
                            <i class="fas fa-fire me-2"></i>Early Bird Berakhir: <?= $activeEvent['early_bird_deadline_formatted'] ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($activeEvent['event_date_formatted'])): ?>
                    <div class="deadline-info animate-on-scroll">
                        <div class="deadline-badge" style="background: #2196f3;">
                            <i class="fas fa-calendar-alt me-2"></i>Tanggal Event: <?= $activeEvent['event_date_formatted'] ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="pricing-container mb-5">
                <!-- Presenter Early Bird -->
                <div class="price-card featured animate-on-scroll">
                    <div>
                        <div class="feature-icon mb-3">
                            <i class="fas fa-microphone"></i>
                        </div>
                        <h4 class="fw-bold">Presenter</h4>
                        <div class="badge bg-warning text-dark mb-2 px-3 py-2">
                            <i class="fas fa-star me-1"></i> Early Bird Price
                        </div>
                        <div class="price">Rp <?= number_format($activeEvent['early_bird_presenter_fee'], 0, ',', '.') ?></div>
                        <small class="text-muted d-block mb-3">Harga khusus untuk pendaftar awal</small>
                    </div>
                    <ul class="list-unstyled mb-4">
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Submit Abstract</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Present Paper (Offline)</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Certificate</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Lunch & Coffee Break</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Conference Kit</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Networking Session</li>
                    </ul>
                </div>

                <!-- Audience Early Bird -->
                <div class="price-card featured animate-on-scroll">
                    <div>
                        <div class="feature-icon mb-3">
                            <i class="fas fa-users"></i>
                        </div>
                        <h4 class="fw-bold">Peserta / Audience</h4>
                        <div class="badge bg-warning text-dark mb-2 px-3 py-2">
                            <i class="fas fa-star me-1"></i> Early Bird Price
                        </div>
                        <div class="price">Rp <?= number_format($activeEvent['early_bird_audience_fee'], 0, ',', '.') ?></div>
                        <small class="text-muted d-block mb-3">Harga khusus untuk pendaftar awal</small>
                    </div>
                    <ul class="list-unstyled mb-4">
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Attend Seminar (Offline)</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Certificate</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Lunch & Coffee Break</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Networking Session</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Conference Kit</li>
                        <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Q&A with Speakers</li>
                    </ul>
                </div>
            </div>

            <!-- Early Bird Info Box -->
            <div class="text-center mt-4">
                <div class="alert alert-info d-inline-block animate-on-scroll" style="max-width: 700px;">
                    <i class="fas fa-info-circle me-2"></i>
                    <strong>Catatan Penting:</strong> Harga Early Bird berlaku untuk partisipasi <strong>offline</strong>. 
                    Daftarkan diri Anda sekarang sebelum periode Early Bird berakhir dan dapatkan harga terbaik!
                </div>
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

    <!-- About Us Section -->
    <section class="about-section py-5" id="about">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title animate-on-scroll">Tentang SNIA</h2>
                <p class="lead animate-on-scroll">Seminar Nasional Informatika dan Aplikasinya</p>
            </div>
            
            <div class="row g-5 align-items-center mb-5">
                <div class="col-lg-6">
                    <div class="about-image-wrapper animate-on-scroll">
                        <img src="<?= base_url('assets/img/sponsors/unjani.png') ?>" alt="Universitas Jenderal Achmad Yani" class="img-fluid rounded shadow-lg">
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="animate-on-scroll">
                        <h3 class="fw-bold mb-4 text-primary">Tentang Seminar</h3>
                        <p class="text-muted mb-3">
                            <strong>SNIA (Seminar Nasional Informatika dan Aplikasinya)</strong> merupakan kegiatan seminar nasional yang diselenggarakan secara berkala setiap <strong>2 tahun sekali</strong> oleh Jurusan Informatika, Fakultas MIPA, Universitas Jenderal Achmad Yani (UNJANI) Cimahi.
                        </p>
                        <p class="text-muted mb-3">
                            Seminar ini bertujuan untuk menjadi wadah bagi akademisi, peneliti, praktisi, dan mahasiswa dalam berbagi pengetahuan, pengalaman, serta hasil penelitian terkini di bidang informatika dan aplikasinya.
                        </p>
                        <p class="text-muted mb-4">
                            Melalui SNIA, kami berkomitmen untuk mendorong perkembangan ilmu pengetahuan dan teknologi informasi di Indonesia, serta membangun kolaborasi yang berkelanjutan antar institusi pendidikan, industri, dan pemerintah.
                        </p>
                        
                        <div class="row g-3">
                            <div class="col-6">
                                <div class="feature-box p-3 bg-light rounded text-center">
                                    <i class="fas fa-users text-primary fs-2 mb-2"></i>
                                    <h4 class="mb-1 fw-bold text-primary">500+</h4>
                                    <small class="text-muted">Peserta</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="feature-box p-3 bg-light rounded text-center">
                                    <i class="fas fa-file-alt text-primary fs-2 mb-2"></i>
                                    <h4 class="mb-1 fw-bold text-primary">100+</h4>
                                    <small class="text-muted">Paper Presented</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="feature-box p-3 bg-light rounded text-center">
                                    <i class="fas fa-university text-primary fs-2 mb-2"></i>
                                    <h4 class="mb-1 fw-bold text-primary">50+</h4>
                                    <small class="text-muted">Institusi</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="feature-box p-3 bg-light rounded text-center">
                                    <i class="fas fa-award text-primary fs-2 mb-2"></i>
                                    <h4 class="mb-1 fw-bold text-primary">10+</h4>
                                    <small class="text-muted">Tahun Pengalaman</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Vision & Mission -->
            <div class="row g-4 mt-5">
                <div class="col-md-6">
                    <div class="vision-mission-card animate-on-scroll">
                        <div class="card-header-custom bg-primary text-white">
                            <i class="fas fa-eye me-2"></i>
                            <h4 class="mb-0">Visi</h4>
                        </div>
                        <div class="card-body-custom">
                            <p class="mb-0">
                                Menjadi forum nasional terdepan dalam pengembangan dan penerapan ilmu informatika yang inovatif, berkualitas, dan berdampak nyata bagi kemajuan teknologi informasi di Indonesia.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="vision-mission-card animate-on-scroll">
                        <div class="card-header-custom bg-primary text-white">
                            <i class="fas fa-bullseye me-2"></i>
                            <h4 class="mb-0">Misi</h4>
                        </div>
                        <div class="card-body-custom">
                            <ul class="mb-0 ps-3">
                                <li class="mb-2">Menyediakan platform untuk publikasi dan diskusi penelitian informatika terkini</li>
                                <li class="mb-2">Memfasilitasi kolaborasi antar akademisi, peneliti, dan praktisi</li>
                                <li class="mb-2">Mendorong inovasi dan pengembangan teknologi informasi</li>
                                <li>Meningkatkan kualitas pendidikan dan penelitian di bidang informatika</li>
                            </ul>
                        </div>
                    </div>
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
                <?php if (!empty($activeEvent['early_bird_deadline_formatted'])): ?>
                <div class="alert alert-warning d-inline-block mb-4">
                    <i class="fas fa-fire me-2"></i><strong>Early Bird Berakhir:</strong> <?= $activeEvent['early_bird_deadline_formatted'] ?>
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
                    <h5 class="fw-bold mb-3">SNIA <?= $activeEvent ? date('Y', strtotime($activeEvent['event_date'])) : date('Y') ?></h5>
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