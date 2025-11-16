<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/landing.css?v=' . time()) ?>">
<?= $this->endSection() ?>

<?= $this->section('content') ?>

<!-- Hero Section -->
<section class="hero-section" id="home">
  <div class="hero-content">
    <div class="container">
      <div class="row justify-content-center">
        <div class="col-lg-8 col-xl-7">
          <div>
            <h1 class="hero-title">Seminar Nasional Informatika dan Aplikasinya</h1>
            <div class="hero-year">(SNIA) <?= date('Y') ?></div>
            <p class="hero-description">
              Diselenggarakan oleh Jurusan Informatika Universitas Jenderal Achmad Yani (UNJANI), acara dua tahunan yang mempertemukan akademisi, peneliti, dan praktisi untuk berbagi pengetahuan dan inovasi terdepan di bidang teknologi informasi.
            </p>
            <a href="<?= base_url('auth/register') ?>" class="hero-cta">DAFTAR SEKARANG</a>
          </div>
        </div>
      </div>
    </div>
  </div>
</section>

<?php if ($activeEvent): ?>
<!-- Poster Section (diperkecil) -->
<section class="poster-section py-5" id="poster">
  <div class="container">
    <div class="text-center mb-4">
      <h2 class="section-title">Poster Event</h2>
      <p class="lead">Informasi lengkap tentang SNIA <?= date('Y', strtotime($activeEvent['event_date'])) ?></p>
    </div>
    <div class="row justify-content-center">
      <div class="col-12">
        <div class="poster-card poster-slim">
          <img src="<?= base_url('assets/img/poster.png') ?>" alt="Poster SNIA" class="poster-image">
        </div>
      </div>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if ($activeEvent): ?>
<!-- Registration Section (lebih kompak) -->
<section class="registration-section" id="register">
  <div class="container">
    <div class="text-center mb-4">
      <h2 class="section-title">Paket Registrasi</h2>
      <p class="lead">Pilih paket yang sesuai dengan kebutuhan Anda</p>
      <?php if ($activeEvent['event_date']): ?>
      <div class="deadline-info d-inline-block">
        <div class="deadline-badge">
          <i class="fas fa-calendar-alt me-2"></i>Tanggal Event: <?= $activeEvent['event_date_formatted'] ?>
        </div>
      </div>
      <?php endif; ?>
    </div>

    <div class="pricing-container">
      <?php if ($activeEvent['format'] === 'both' || $activeEvent['format'] === 'offline'): ?>
      <!-- Presenter -->
      <div class="price-card">
        <div>
          <div class="feature-icon mb-3"><i class="fas fa-microphone"></i></div>
          <h4 class="fw-bold">Presenter</h4>
          <div class="price">Rp <?= number_format($activeEvent['presenter_fee_offline'], 0, ',', '.') ?></div>
        </div>
        <ul class="list-unstyled mb-0">
          <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Submit Abstract</li>
          <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Present Paper</li>
          <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Certificate</li>
          <li class="mb-0"><i class="fas fa-check text-success me-2"></i>Lunch & Coffee</li>
        </ul>
      </div>

      <!-- Audience Offline -->
      <div class="price-card <?= ($activeEvent['format'] === 'both') ? 'featured' : '' ?>">
        <div>
          <div class="feature-icon mb-3"><i class="fas fa-users"></i></div>
          <h4 class="fw-bold">Audience Offline</h4>
          <div class="price">Rp <?= number_format($activeEvent['audience_fee_offline'], 0, ',', '.') ?></div>
        </div>
        <ul class="list-unstyled mb-0">
          <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Attend Seminar</li>
          <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Certificate</li>
          <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Lunch & Coffee</li>
          <li class="mb-0"><i class="fas fa-check text-success me-2"></i>Networking</li>
        </ul>
      </div>
      <?php endif; ?>

      <?php if ($activeEvent['format'] === 'both' || $activeEvent['format'] === 'online'): ?>
      <!-- Audience Online -->
      <div class="price-card <?= ($activeEvent['format'] === 'online') ? 'featured' : '' ?>">
        <div>
          <div class="feature-icon mb-3"><i class="fas fa-laptop"></i></div>
          <h4 class="fw-bold">Audience Online</h4>
          <div class="price">Rp <?= number_format($activeEvent['audience_fee_online'], 0, ',', '.') ?></div>
        </div>
        <ul class="list-unstyled mb-0">
          <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Live Streaming</li>
          <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Digital Certificate</li>
          <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Recording Access</li>
          <li class="mb-0"><i class="fas fa-check text-success me-2"></i>Q&A Session</li>
        </ul>
      </div>
      <?php endif; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- Speaker Section -->
<section class="speaker-section" id="speakers">
  <div class="container">
    <div class="text-center mb-5">
      <h2 class="section-title">Pembicara & Narasumber</h2>
      <p class="lead">Para ahli dan praktisi terkemuka di bidang informatika</p>
    </div>

    <div class="row g-4">
      <div class="col-md-6 col-lg-3">
        <div class="speaker-card">
          <img src="<?= base_url('assets/img/speakers/speaker1.jpg') ?>" alt="Dr. Ahmad Ridwan" class="speaker-img">
          <h5 class="speaker-name">Dr. Ahmad Ridwan</h5>
          <p class="speaker-title">Keynote Speaker</p>
          <p class="speaker-bio">Pakar AI & Machine Learning dari Institut Teknologi Bandung dengan pengalaman riset lebih dari 15 tahun.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="speaker-card">
          <img src="<?= base_url('assets/img/speakers/speaker2.jpg') ?>" alt="Prof. Siti Nurhaliza" class="speaker-img">
          <h5 class="speaker-name">Prof. Siti Nurhaliza</h5>
          <p class="speaker-title">Expert Speaker</p>
          <p class="speaker-bio">Profesor Cyber Security dari Universitas Indonesia, spesialis keamanan jaringan dan kriptografi.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="speaker-card">
          <img src="<?= base_url('assets/img/speakers/speaker3.jpg') ?>" alt="Dr. Budi Santoso" class="speaker-img">
          <h5 class="speaker-name">Dr. Budi Santoso</h5>
          <p class="speaker-title">Technology Leader</p>
          <p class="speaker-bio">CTO PT. Telkom Indonesia, pionir dalam implementasi IoT dan Smart City di Indonesia.</p>
        </div>
      </div>
      <div class="col-md-6 col-lg-3">
        <div class="speaker-card">
          <img src="<?= base_url('assets/img/speakers/speaker4.jpg') ?>" alt="Dr. Maya Anggraini" class="speaker-img">
          <h5 class="speaker-name">Dr. Maya Anggraini</h5>
          <p class="speaker-title">Data Scientist</p>
          <p class="speaker-bio">Lead Data Scientist di Gojek, expert dalam Big Data Analytics dan Business Intelligence.</p>
        </div>
      </div>
    </div>
  </div>
</section>

<?php if (!empty($activeVouchers)): ?>
<!-- Voucher Section -->
<section class="voucher-section" id="vouchers">
  <div class="container">
    <div class="text-center mb-5">
      <h2 class="section-title">Voucher Diskon Tersedia!</h2>
      <p class="lead">Dapatkan potongan harga dengan menggunakan voucher berikut</p>
    </div>

    <div class="row g-4">
      <?php foreach ($activeVouchers as $voucher): ?>
      <div class="col-md-6 col-lg-4">
        <div class="voucher-card">
          <div class="voucher-badge"><i class="fas fa-fire me-1"></i>Terbatas</div>
          <div class="text-center mb-3"><i class="fas fa-ticket-alt" style="font-size: 3rem;"></i></div>
          <div class="voucher-code text-center"><?= esc($voucher['kode_voucher']) ?></div>
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
          <div class="text-center mt-3"><small>Gunakan kode ini saat pendaftaran</small></div>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if (!$activeEvent): ?>
<!-- No Event Notice -->
<section class="py-5">
  <div class="container">
    <div class="no-event-notice">
      <i class="fas fa-calendar-times" style="font-size: 5rem; margin-bottom: 30px;"></i>
      <h2 class="fw-bold mb-4">Belum Ada Event Aktif</h2>
      <p class="lead mb-4">Saat ini belum ada event yang dibuka untuk pendaftaran. Silakan cek kembali nanti atau hubungi kami untuk informasi lebih lanjut.</p>
      <a href="mailto:snia@unjani.ac.id" class="btn btn-light btn-lg"><i class="fas fa-envelope me-2"></i>Hubungi Kami</a>
    </div>
  </div>
</section>
<?php endif; ?>

<!-- Sponsor Section: satu baris, bisa digeser -->
<section class="sponsor-section" id="sponsors">
  <div class="container">
    <div class="text-center mb-4">
      <h2 class="section-title">Sponsor & Partner</h2>
      <p class="lead">Terima kasih kepada para sponsor dan partner yang mendukung SNIA</p>
    </div>

    <div class="sponsor-row" aria-label="logo sponsor (geser ke kanan/kiri)">
      <img src="<?= base_url('assets/img/sponsors/BCA.png') ?>" alt="BCA">
      <img src="<?= base_url('assets/img/sponsors/BNI.png') ?>" alt="BNI">
      <img src="<?= base_url('assets/img/sponsors/DANA.jpg') ?>" alt="DANA">
      <img src="<?= base_url('assets/img/sponsors/unjani.png') ?>" alt="UNJANI">
      <img src="<?= base_url('assets/img/sponsors/BERITA.png') ?>" alt="Media Partner">
    </div>
    <!-- Catatan: call-to-action sponsor dipindah ke footer (bagian Kontak) -->
  </div>
</section>

<!-- Location Section -->
<section class="py-5" id="location">
  <div class="container">
    <div class="text-center mb-5">
      <h2 class="section-title">Lokasi Acara</h2>
    </div>

    <div class="row g-5 align-items-center">
      <div class="col-lg-6">
        <div class="map-container">
          <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!3d3961.0244864384695!2d107.5291975147727!3d-6.8792935950655935!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e68e569e4b3b5a5%3A0x401e8f1fc28b750!2sUnjani%20(Universitas%20Jenderal%20Achmad%20Yani)!5e0!3m2!1sen!2sid!4v1640234567890!5m2!1sen!2sid"
                  width="100%" height="400" style="border:0;" allowfullscreen loading="lazy"></iframe>
        </div>
      </div>
      <div class="col-lg-6">
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
        <a href="https://www.google.com/maps/dir//Unjani+(Universitas+Jenderal+Achmad+Yani)"
           class="btn btn-primary" target="_blank" rel="noopener noreferrer">
          <i class="fas fa-directions me-2"></i>Lihat Rute
        </a>
      </div>
    </div>
  </div>
</section>

<?php if ($activeEvent): ?>
<!-- CTA Section -->
<section class="py-5" style="background: var(--primary-blue);">
  <div class="container text-center text-white">
    <h2 class="fw-bold mb-4">Siap Bergabung dengan SNIA <?= date('Y', strtotime($activeEvent['event_date'])) ?>?</h2>
    <p class="lead mb-4">Jangan lewatkan kesempatan untuk berbagi pengetahuan dan bernetworking dengan para ahli informatika terkemuka.</p>
    <?php if ($activeEvent['registration_deadline']): ?>
    <div class="alert alert-warning d-inline-block mb-4">
      <i class="fas fa-clock me-2"></i><strong>Pendaftaran Ditutup:</strong> <?= $activeEvent['registration_deadline_formatted'] ?>
    </div><br>
    <?php endif; ?>
    <a href="<?= base_url('auth/register') ?>" class="btn btn-light btn-lg px-5 py-3 rounded-pill">
      <i class="fas fa-user-plus me-2"></i>Daftar Sekarang
    </a>
  </div>
</section>
<?php endif; ?>

<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/landing.js?v=' . time()) ?>"></script>
<?= $this->endSection() ?>
