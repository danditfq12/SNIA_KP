<nav class="navbar navbar-expand-lg navbar-dark fixed-top">
  <div class="container">
    <a class="navbar-brand" href="<?= base_url('/') ?>">
      <i class="fas fa-graduation-cap me-2"></i>SNIA
    </a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav mx-auto">
        <li class="nav-item"><a class="nav-link" href="<?= base_url('/') ?>#home">Home</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= base_url('/') ?>#poster">Poster</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= base_url('/') ?>#register">Paket</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= base_url('/') ?>#speakers">Pembicara</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= base_url('/') ?>#sponsors">Sponsor</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= base_url('/') ?>#location">Lokasi</a></li>
        <li class="nav-item"><a class="nav-link" href="<?= base_url('about') ?>">About Us</a></li>
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
