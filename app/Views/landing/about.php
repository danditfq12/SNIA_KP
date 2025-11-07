<?= $this->extend('layouts/main') ?>

<?= $this->section('styles') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/landing.css?v=' . time()) ?>">
<style>
  .about-hero{padding:120px 0;background:linear-gradient(rgba(0,61,130,.7),rgba(0,86,179,.7)),url('<?= base_url('assets/img/Background.jpg') ?>') center/cover fixed;color:#fff;}
  .about-card{border-radius:15px;box-shadow:0 10px 30px rgba(30,136,229,.1);}
</style>
<?= $this->endSection() ?>

<?= $this->section('content') ?>
<section class="about-hero">
  <div class="container text-center">
    <h1 class="fw-bold mb-3">Tentang SNIA</h1>
    <p class="lead">Seminar dua tahunan yang mempertemukan akademisi, peneliti, dan praktisi di bidang informatika.</p>
  </div>
</section>

<section class="py-5">
  <div class="container">
    <div class="row g-4">
      <div class="col-lg-6">
        <div class="p-4 bg-white about-card">
          <h3 class="fw-bold mb-3">Visi & Misi</h3>
          <p class="mb-2">• Menjadi forum ilmiah nasional yang inklusif dan berdampak.</p>
          <p class="mb-0">• Mendorong kolaborasi riset dan aplikasi TI untuk kemaslahatan.</p>
        </div>
      </div>
      <div class="col-lg-6">
        <div class="p-4 bg-white about-card">
          <h3 class="fw-bold mb-3">Tentang Penyelenggara</h3>
          <p class="mb-0">Diselenggarakan oleh Jurusan Informatika Universitas Jenderal Achmad Yani (UNJANI) Cimahi.</p>
        </div>
      </div>
    </div>

    <h3 class="fw-bold mt-5 mb-4">Tim Penyelenggara</h3>
    <div class="row g-4">
      <?php foreach ($team as $m): ?>
      <div class="col-6 col-md-4">
        <div class="text-center p-4 bg-white about-card">
          <img src="<?= esc($m['photo']) ?>" alt="<?= esc($m['name']) ?>" class="rounded-circle mb-3" width="120" height="120" style="object-fit:cover;">
          <h6 class="mb-1"><?= esc($m['name']) ?></h6>
          <small class="text-muted"><?= esc($m['role']) ?></small>
        </div>
      </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?= $this->endSection() ?>

<?= $this->section('scripts') ?>
<script src="<?= base_url('assets/js/landing.js?v=' . time()) ?>"></script>
<?= $this->endSection() ?>
