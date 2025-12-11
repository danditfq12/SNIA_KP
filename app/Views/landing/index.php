<?php
/**
 * @var array|null $activeEvent
 * @var array      $activeVouchers
 * @var array      $speakers
 * @var array      $sponsors
 * @var string     $title
 * @var string|null $posterPath
 */

$title          = $title ?? 'SNIA - Seminar Nasional Informatika';
$activeEvent    = $activeEvent ?? null;
$activeVouchers = $activeVouchers ?? [];
$speakers       = $speakers ?? [];
$sponsors       = $sponsors ?? [];
$posterPath     = $posterPath ?? null;

// fallback poster kalau controller saat ini masih pakai kolom "poster" di events
if (!$posterPath && !empty($activeEvent['poster'])) {
    $posterPath = 'uploads/poster/' . $activeEvent['poster'];
}

/**
 * Pisah antara S P O N S O R vs P A R T N E R.
 * - Partner = type === 'partner'
 * - Sponsor = selain itu (sponsor, main, gold, silver, media, dsb)
 */
$sponsorLogos = [];
$partnerLogos = [];

foreach ($sponsors as $sp) {
    $type = strtolower($sp['type'] ?? 'sponsor');
    if ($type === 'partner') {
        $partnerLogos[] = $sp;
    } else {
        $sponsorLogos[] = $sp;
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($title) ?></title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.2/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="<?= base_url('assets/css/landing.css') ?>" rel="stylesheet">
    <style>
        /* kecil saja di sini biar sponsor / partner kelihatan bisa di-hover */
        .sponsor-card[data-bs-toggle="tooltip"] {
            cursor: pointer;
        }
    </style>
</head>
<body>

<?= $this->include('partials/navbar') ?>

<!-- Hero Section -->
<section class="hero-section" id="home">
    <div class="hero-content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8 col-xl-7">
                    <div class="animate-on-scroll text-center">
                        <?php if ($activeEvent): ?>
                            <h1 class="hero-title">
                                <?= esc($activeEvent['title'] ?? 'Seminar Nasional Informatika dan Aplikasinya') ?>
                            </h1>

                            <?php if (!empty($activeEvent['event_date'])): ?>
                                <div class="hero-year">
                                    <?= date('Y', strtotime($activeEvent['event_date'])) ?>
                                </div>
                            <?php else: ?>
                                <div class="hero-year">(SNIA) <?= date('Y') ?></div>
                            <?php endif; ?>

                            <p class="hero-description">
                                <?= esc($activeEvent['description'] ?? 'Seminar nasional informatika dan aplikasinya.') ?>
                            </p>
                        <?php else: ?>
                            <h1 class="hero-title">Seminar Nasional Informatika dan Aplikasinya</h1>
                            <div class="hero-year">(SNIA) <?= date('Y') ?></div>
                            <p class="hero-description">
                                Diselenggarakan oleh Jurusan Informatika Universitas Jenderal Achmad Yani (UNJANI),
                                acara dua tahunan yang mempertemukan akademisi, peneliti, dan praktisi untuk berbagi
                                pengetahuan dan inovasi terdepan di bidang teknologi informasi.
                            </p>
                        <?php endif; ?>

                        <a href="<?= base_url('auth/login') ?>" class="hero-cta">DAFTAR SEKARANG</a>

                        <?php if ($activeEvent && !$posterPath): ?>
                            <div class="alert alert-warning d-inline-block mt-4 px-3 py-2 small">
                                <i class="fas fa-image me-2"></i>
                                Poster event belum diatur. Silakan hubungi panitia atau tunggu informasi selanjutnya.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Poster Section (hanya jika ada event & poster sudah diinput di admin) -->
<?php if ($activeEvent && $posterPath): ?>
<section class="poster-section py-5" id="poster">
    <div class="container">
        <div class="text-center mb-4">
            <h2 class="section-title animate-on-scroll">Poster Event</h2>
        </div>
        <div class="row justify-content-center">
            <div class="col-lg-8 col-md-10">
                <div class="poster-card animate-on-scroll">
                    <img
                        src="<?= base_url($posterPath) ?>"
                        alt="Poster SNIA <?= !empty($activeEvent['event_date']) ? date('Y', strtotime($activeEvent['event_date'])) : date('Y') ?>"
                        class="poster-image"
                    >
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
                                <i class="fas fa-fire me-2"></i>
                                Early Bird Berakhir: <?= esc($activeEvent['early_bird_deadline_formatted']) ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($activeEvent['event_date_formatted'])): ?>
                        <div class="deadline-info animate-on-scroll">
                            <div class="deadline-badge" style="background: #2196f3;">
                                <i class="fas fa-calendar-alt me-2"></i>
                                Tanggal Event: <?= esc($activeEvent['event_date_formatted']) ?>
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
                        <div class="price">
                            Rp <?= number_format((float) ($activeEvent['early_bird_presenter_fee'] ?? 0), 0, ',', '.') ?>
                        </div>
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
                        <div class="price">
                            Rp <?= number_format((float) ($activeEvent['early_bird_audience_fee'] ?? 0), 0, ',', '.') ?>
                        </div>
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
                    <strong>Catatan Penting:</strong>
                    Harga Early Bird berlaku untuk partisipasi <strong>offline</strong>.
                    Daftarkan diri Anda sekarang sebelum periode Early Bird berakhir dan dapatkan harga terbaik!
                </div>
            </div>
        </div>
    </section>

    <!-- Speaker Section (dinamis dari admin) -->
    <section class="speaker-section" id="speakers">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title animate-on-scroll">Pembicara & Narasumber</h2>
                <p class="lead animate-on-scroll">Para ahli dan praktisi di bidang informatika</p>
            </div>

            <?php if (!empty($speakers)): ?>
                <div class="row g-4">
                    <?php foreach ($speakers as $sp): ?>
                        <div class="col-md-6 col-lg-3">
                            <div class="speaker-card animate-on-scroll">
                                <?php if (!empty($sp['photo_path'])): ?>
                                    <img
                                        src="<?= base_url($sp['photo_path']) ?>"
                                        alt="<?= esc($sp['name']) ?>"
                                        class="speaker-img"
                                    >
                                <?php else: ?>
                                    <div class="speaker-img d-flex align-items-center justify-content-center bg-light text-primary">
                                        <i class="fas fa-user-tie fa-3x"></i>
                                    </div>
                                <?php endif; ?>

                                <h5 class="speaker-name"><?= esc($sp['name']) ?></h5>
                                <?php if (!empty($sp['role']) || !empty($sp['affiliation'])): ?>
                                    <p class="speaker-title">
                                        <?= esc($sp['role'] ?? '') ?>
                                        <?php if (!empty($sp['affiliation'])): ?>
                                            <?= $sp['role'] ? ' - ' : '' ?><?= esc($sp['affiliation']) ?>
                                        <?php endif; ?>
                                    </p>
                                <?php endif; ?>
                                <?php if (!empty($sp['bio'])): ?>
                                    <p class="speaker-bio"><?= esc($sp['bio']) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="text-center">
                    <div class="alert alert-secondary d-inline-block animate-on-scroll">
                        <i class="fas fa-user-clock me-2"></i>
                        Informasi pembicara akan diumumkan setelah ditetapkan oleh panitia.
                    </div>
                </div>
            <?php endif; ?>
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
                            <?php if (($voucher['tipe'] ?? '') === 'percentage'): ?>
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
                            <strong class="<?= ($voucher['remaining'] ?? 0) <= 5 ? 'text-warning' : '' ?>">
                                <?= (int) ($voucher['remaining'] ?? 0) ?> / <?= (int) $voucher['kuota'] ?>
                            </strong>
                        </div>

                        <?php if (($voucher['remaining'] ?? 0) <= 5): ?>
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

<?php endif; ?> <!-- akhir blok if ($activeEvent) utama -->

<!-- Sponsor & Partner Section (dinamis dari admin) -->
<section class="sponsor-section" id="sponsors">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title animate-on-scroll">Sponsor & Partner</h2>
            <p class="lead animate-on-scroll">
                Terima kasih kepada para sponsor dan partner yang mendukung SNIA
            </p>
        </div>

        <?php if (!empty($sponsorLogos) || !empty($partnerLogos)): ?>

            <!-- BLOK SPONSOR (paling utama di atas) -->
            <?php if (!empty($sponsorLogos)): ?>
                <div class="text-center mb-4">
                    <span class="sponsor-tier-title animate-on-scroll">
                        <i class="fas fa-hand-holding-heart me-2"></i>Sponsor
                    </span>
                </div>
                <div class="row g-4 justify-content-center mb-4">
                    <?php foreach ($sponsorLogos as $sp): ?>
                        <div class="col-md-3 col-sm-4 col-6">
                            <div class="sponsor-card animate-on-scroll"
                                 data-bs-toggle="tooltip"
                                 data-bs-placement="top"
                                 title="<?= esc($sp['name']) ?>">
                                <?php if (!empty($sp['logo_path'])): ?>
                                    <img
                                        src="<?= base_url($sp['logo_path']) ?>"
                                        alt="<?= esc($sp['name']) ?>"
                                        class="sponsor-logo"
                                        style="max-height: 80px; object-fit: contain;">
                                <?php else: ?>
                                    <span class="fw-bold text-primary small"><?= esc($sp['name']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- BLOK PARTNER (di bawah sponsor) -->
            <?php if (!empty($partnerLogos)): ?>
                <div class="text-center mb-4">
                    <span class="sponsor-tier-title animate-on-scroll">
                        <i class="fas fa-people-group me-2"></i>Partner
                    </span>
                </div>
                <div class="row g-4 justify-content-center mb-4">
                    <?php foreach ($partnerLogos as $sp): ?>
                        <div class="col-md-3 col-sm-4 col-6">
                            <div class="sponsor-card animate-on-scroll"
                                 data-bs-toggle="tooltip"
                                 data-bs-placement="top"
                                 title="<?= esc($sp['name']) ?>">
                                <?php if (!empty($sp['logo_path'])): ?>
                                    <img
                                        src="<?= base_url($sp['logo_path']) ?>"
                                        alt="<?= esc($sp['name']) ?>"
                                        class="sponsor-logo"
                                        style="max-height: 70px; object-fit: contain;">
                                <?php else: ?>
                                    <span class="fw-bold text-primary small"><?= esc($sp['name']) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="text-center mb-4">
                <div class="alert alert-secondary d-inline-block animate-on-scroll">
                    <i class="fas fa-handshake-slash me-2"></i>
                    Sponsor dan partner belum diatur. Informasi akan diumumkan kemudian.
                </div>
            </div>
        <?php endif; ?>

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
                        <p class="text-muted ms-4">
                            Jl. Terusan Jend. Sudirman, Cimahi Utara, Kota Cimahi, Jawa Barat 40285
                        </p>
                    </div>
                    <?php if ($activeEvent && !empty($activeEvent['event_date'])): ?>
                    <div class="mb-4">
                        <h5><i class="fas fa-calendar text-primary me-2"></i>Tanggal & Waktu</h5>
                        <p class="text-muted ms-4">
                            <?= esc($activeEvent['event_date_formatted'] ?? $activeEvent['event_date']) ?><br>
                            <?php if (!empty($activeEvent['event_time'])): ?>
                                <?= date('H:i', strtotime($activeEvent['event_time'])) ?> WIB
                            <?php endif; ?>
                        </p>
                    </div>
                    <?php if (!empty($activeEvent['location'])): ?>
                    <div class="mb-4">
                        <h5><i class="fas fa-building text-primary me-2"></i>Tempat</h5>
                        <p class="text-muted ms-4"><?= esc($activeEvent['location']) ?></p>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>

                    <div class="mb-4">
                        <h5><i class="fas fa-car text-primary me-2"></i>Transportasi</h5>
                        <p class="text-muted ms-4">
                            Tersedia shuttle bus dari Stasiun Cimahi dan area parkir yang luas untuk peserta.
                        </p>
                    </div>
                    <a href="https://www.google.com/maps/dir//Unjani+(Universitas+Jenderal+Achmad+Yani),+Jl.+Terusan+Jend.+Sudirman,+Cimahi+Utara,+Kota+Cimahi,+Jawa+Barat+40285/@-6.8792936,107.5291975,17z/data=!4m8!4m7!1m0!1m5!1m1!1s0x2e68e569e4b3b5a5:0x401e8f1fc28b750!2m2!1d107.5317724!2d-6.8792936"
                       class="btn btn-primary"
                       target="_blank"
                       rel="noopener noreferrer">
                        <i class="fas fa-directions me-2"></i>Lihat Rute
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section (hanya jika ada event) -->
<?php if ($activeEvent): ?>
<section class="py-5" style="background: var(--primary-blue);">
    <div class="container text-center text-white">
        <div class="animate-on-scroll">
            <h2 class="fw-bold mb-4">
                Siap Bergabung dengan SNIA
                <?= !empty($activeEvent['event_date']) ? date('Y', strtotime($activeEvent['event_date'])) : date('Y') ?>?
            </h2>
            <p class="lead mb-4">
                Jangan lewatkan kesempatan untuk berbagi pengetahuan dan bernetworking dengan para ahli informatika terkemuka.
            </p>
            <?php if (!empty($activeEvent['early_bird_deadline_formatted'])): ?>
            <div class="alert alert-warning d-inline-block mb-4">
                <i class="fas fa-fire me-2"></i>
                <strong>Early Bird Berakhir:</strong> <?= esc($activeEvent['early_bird_deadline_formatted']) ?>
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
                <h5 class="fw-bold mb-3">
                    SNIA <?= $activeEvent && !empty($activeEvent['event_date']) ? date('Y', strtotime($activeEvent['event_date'])) : date('Y') ?>
                </h5>
                <p class="text-light">
                    Seminar Nasional Informatika & Aplikasi yang diselenggarakan oleh Jurusan Informatika UNJANI
                    Cimahi. Kegiatan dua tahunan untuk membahas perkembangan informatika dan aplikasinya.
                </p>
            </div>
            <div class="col-lg-4 mb-4">
                <h5 class="fw-bold mb-3">Penyelenggara</h5>
                <p class="text-light mb-2">
                    <i class="fas fa-university me-2"></i>Jurusan Informatika UNJANI
                </p>
                <p class="text-light mb-2">
                    <i class="fas fa-map-marker-alt me-2"></i>Universitas Jenderal Achmad Yani, Cimahi
                </p>
                <p class="text-light">
                    <i class="fas fa-calendar me-2"></i>Setiap 2 tahun sekali
                </p>
            </div>
            <div class="col-lg-4 mb-4">
                <h5 class="fw-bold mb-3">Kontak Informasi</h5>
                <p class="text-light mb-2">
                    <i class="fas fa-envelope me-2"></i>snia@unjani.ac.id
                </p>
                <p class="text-light mb-2">
                    <i class="fas fa-phone me-2"></i>+62 22 6656 186
                </p>
                <p class="text-light">
                    <i class="fas fa-globe me-2"></i>www.unjani.ac.id
                </p>
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
            const targetSelector = this.getAttribute('href');
            if (!targetSelector || targetSelector === '#') return;

            const target = document.querySelector(targetSelector);
            if (!target) return;

            e.preventDefault();
            const offsetTop = target.offsetTop - 80;
            window.scrollTo({
                top: offsetTop,
                behavior: 'smooth'
            });
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

    // Navbar background on scroll
    window.addEventListener('scroll', () => {
        const navbar = document.querySelector('.navbar');
        if (!navbar) return;
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
    });

    // Auto-close navbar on mobile when clicking a link
    const navLinks       = document.querySelectorAll('.navbar-nav .nav-link');
    const navbarCollapse = document.querySelector('.navbar-collapse');

    navLinks.forEach(link => {
        link.addEventListener('click', () => {
            if (window.innerWidth < 992 && navbarCollapse && navbarCollapse.classList.contains('show')) {
                const bsCollapse = new bootstrap.Collapse(navbarCollapse, {toggle: false});
                bsCollapse.hide();
            }
        });
    });

    // ===== INIT BOOTSTRAP TOOLTIP UNTUK HOVER SPONSOR/PARTNER =====
    document.addEventListener('DOMContentLoaded', function () {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.forEach(function (tooltipTriggerEl) {
            new bootstrap.Tooltip(tooltipTriggerEl);
        });
    });
</script>
</body>
</html>
