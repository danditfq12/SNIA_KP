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
                        <a class="nav-link" href="#about">Tentang</a>
                    </li>
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