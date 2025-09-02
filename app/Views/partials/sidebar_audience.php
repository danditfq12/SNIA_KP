<?php $uri = service('uri')->getPath(); ?>
<aside id="sidebar" class="sidebar p-3 shadow-sm"
       style="position:fixed; top:60px; left:0; width:220px; background:#fff; border-right:1px solid #eef0f4; height:calc(100vh - 60px); overflow-y:auto; transition:transform .3s ease-in-out;">
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div class="d-flex align-items-center gap-2">
      <div class="rounded-3 p-2 bg-primary-subtle text-primary"><i class="bi bi-people"></i></div>
      <span class="brand fw-bold">SNIA Audience</span>
    </div>
    <button class="btn btn-sm d-lg-none" onclick="toggleSidebar()"><i class="bi bi-x-lg"></i></button>
  </div>

  <nav class="nav flex-column">
    <a class="nav-link py-2 px-3 rounded <?= ($uri==='audience/dashboard') ? 'active' : '' ?>"
       href="<?= site_url('audience/dashboard') ?>"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>

    <a class="nav-link py-2 px-3 rounded <?= ($uri==='audience/events' || str_starts_with($uri,'audience/events/')) ? 'active' : '' ?>"
       href="<?= site_url('audience/events') ?>"><i class="bi bi-calendar2-event me-2"></i>Event</a>

    <a class="nav-link py-2 px-3 rounded <?= (str_starts_with($uri,'audience/pembayaran')) ? 'active' : '' ?>"
       href="<?= site_url('audience/pembayaran') ?>"><i class="bi bi-wallet2 me-2"></i>Pembayaran</a>

    <a class="nav-link py-2 px-3 rounded <?= (str_starts_with($uri,'audience/absensi')) ? 'active' : '' ?>"
       href="<?= site_url('audience/absensi') ?>"><i class="bi bi-qr-code me-2"></i>Absensi</a>

    <a class="nav-link py-2 px-3 rounded <?= (str_starts_with($uri,'audience/dokumen')) ? 'active' : '' ?>"
       href="<?= site_url('audience/dokumen/sertifikat') ?>"><i class="bi bi-award me-2"></i>Sertifikat</a>

    <a class="nav-link py-2 px-3 rounded <?= ($uri==='profile') ? 'active' : '' ?>"
       href="<?= site_url('profile') ?>"><i class="bi bi-person-circle me-2"></i>Profil</a>
  </nav>
</aside>

<style>
  .nav-link { color:#444; transition:all .25s ease; }
  .nav-link:hover { background:#f1f5f9; padding-left:1.5rem; }
  .nav-link.active { background:#0d6efd; color:#fff !important; font-weight:600; }

  @media(min-width: 992px){
    #content { margin-left:220px; transition: margin-left .3s ease; }
  }

  /* Mobile-first: sidebar jadi overlay */
  @media(max-width: 991px){
    #sidebar { transform:translateX(-100%); z-index:1050; }
    #sidebar.show { transform:translateX(0); }
    #content { margin-left:0; }
  }
</style>

<script>
  function toggleSidebar(){
    const el = document.getElementById('sidebar');
    el.classList.toggle('show');
  }
</script>
