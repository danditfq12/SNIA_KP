<?php
  $path = trim(service('uri')->getPath(), '/');
  function isActive($patterns, $path){
    foreach ((array)$patterns as $p){
      $p = trim($p, '/');
      if ($p === $path) return true;
      if (substr($p, -2) === '/*') { $prefix = rtrim($p, '/*'); if ($prefix==='' || strpos($path, $prefix)===0) return true; }
      else { if (strpos($path, $p)===0) return true; }
    }
    return false;
  }
?>
<aside id="sidebar" class="admin-like-sidebar">
  <div class="brand-wrap d-flex align-items-center gap-2">
    <i class="bi bi-people fs-5"></i>
    <div>
      <div class="title">SNIA Audience</div>
      <div class="sub">Panel</div>
    </div>
    <button class="btn btn-sm ms-auto d-lg-none text-white-50" type="button"
            aria-label="Tutup menu" onclick="window.toggleSidebar(false)">
      <i class="bi bi-x-lg"></i>
    </button>
  </div>

  <nav class="nav flex-column px-2 py-2">
    <a class="nav-link <?= isActive('audience/dashboard', $path) ? 'is-active':'' ?>"
       href="<?= site_url('audience/dashboard') ?>">
      <span class="ico"><i class="bi bi-speedometer2"></i></span><span>Dashboard</span>
    </a>

    <div class="menu-label">Aktivitas</div>
    <a class="nav-link <?= isActive(['audience/events','audience/events/*'], $path) ? 'is-active':'' ?>"
       href="<?= site_url('audience/events') ?>">
      <span class="ico"><i class="bi bi-calendar2-event"></i></span><span>Event</span>
    </a>
    <a class="nav-link <?= isActive(['audience/pembayaran','audience/pembayaran/*'], $path) ? 'is-active':'' ?>"
       href="<?= site_url('audience/pembayaran') ?>">
      <span class="ico"><i class="bi bi-wallet2"></i></span><span>Pembayaran</span>
    </a>
    <a class="nav-link <?= isActive(['audience/absensi','audience/absensi/*'], $path) ? 'is-active':'' ?>"
       href="<?= site_url('audience/absensi') ?>">
      <span class="ico"><i class="bi bi-qr-code-scan"></i></span><span>Absensi</span>
    </a>

    <div class="menu-label">Dokumen</div>
    <a class="nav-link <?= isActive(['audience/dokumen/sertifikat','audience/dokumen/*'], $path) ? 'is-active':'' ?>"
       href="<?= site_url('audience/dokumen/sertifikat') ?>">
      <span class="ico"><i class="bi bi-award"></i></span><span>Sertifikat</span>
    </a>
  </nav>
</aside>

<!-- Overlay (HP only) -->
<div id="sidebarOverlay" class="sb-overlay"></div>

<style>
  :root{
    --presenter-sidebar-w: 300px;
    --topbar-h: 80px;
  }

  /* Sidebar */
  .admin-like-sidebar{
    position:fixed; left:0; top:0; height:100vh; width:var(--presenter-sidebar-w);
    background: linear-gradient(180deg,#2563eb 0%,#1e40af 100%);
    /* tambah inner hairline di sisi kanan biar “garis” tegas seperti Presenter */
    box-shadow: 4px 0 22px rgba(0,0,0,.12), inset -1px 0 0 rgba(255,255,255,.10);
    z-index:1030; overflow-y:auto; color:#fff;
    transform: translateX(0); transition: transform .26s ease;
  }
  .admin-like-sidebar::-webkit-scrollbar{ width:10px }
  .admin-like-sidebar::-webkit-scrollbar-thumb{ background:rgba(255,255,255,.25); border-radius:8px }

  /* Brand header + hairline yang sama */
  .admin-like-sidebar .brand-wrap{
    position:relative;
    padding:14px 18px;
  }
  .admin-like-sidebar .brand-wrap .title{ font-weight:700; }
  .admin-like-sidebar .brand-wrap .sub{ color:rgba(255,255,255,.7); font-size:.85rem }
  /* garis bawahnya dibuat gradient supaya halus (sama dengan feel di Presenter) */
  .admin-like-sidebar .brand-wrap::after{
    content:""; position:absolute; left:0; right:0; bottom:0; height:1px;
    background:linear-gradient(90deg, rgba(255,255,255,.06), rgba(255,255,255,.28), rgba(255,255,255,.06));
  }

  .admin-like-sidebar .menu-label{
    color:rgba(255,255,255,.6); font-size:.75rem; text-transform:uppercase;
    letter-spacing:.06em; padding:10px 14px 6px; margin-top:8px
  }

  .admin-like-sidebar .nav-link{
    display:flex; align-items:center; gap:12px;
    color:rgba(255,255,255,.92); text-decoration:none;
    padding:11px 14px; margin:4px 8px; border-radius:12px;
    transition:.18s ease;
  }
  .admin-like-sidebar .nav-link .ico{
    width:28px; height:28px; display:grid; place-items:center;
    font-size:1.05rem; color:#fff; background:rgba(255,255,255,.14); border-radius:10px
  }
  .admin-like-sidebar .nav-link:hover{
    background:rgba(255,255,255,.12); color:#fff; transform:translateX(4px)
  }
  .admin-like-sidebar .nav-link.is-active{
    background:rgba(255,255,255,.22); color:#fff; box-shadow:0 6px 18px rgba(0,0,0,.12) inset
  }

  /* Geser konten saat desktop */
  @media(min-width:992px){
    #content{ margin-left: var(--presenter-sidebar-w); }
  }

  /* HP: sidebar hidden by default */
  @media(max-width:991.98px){
    .admin-like-sidebar{ transform: translateX(-100%); top:0; height:100vh; }
    .admin-like-sidebar.show{ transform: translateX(0); }
  }

  /* Overlay HP only */
  .sb-overlay{
    display:none; position:fixed; inset:0;
    background: rgba(0,0,0,.10);
    opacity:0; pointer-events:none;
    z-index: 1020; transition: opacity .22s ease;
  }
  @media(max-width:991.98px){
    .sb-overlay{ display:block; }
    .sb-overlay.show{ opacity:1; pointer-events:auto; }
  }
</style>

<script>
(function(){
  const isMobile = () => window.matchMedia('(max-width: 991.98px)').matches;

  window.toggleSidebar = function (show) {
    const sb = document.getElementById('sidebar');
    const ov = document.getElementById('sidebarOverlay');
    if (!sb) return;
    const willShow = (typeof show === 'boolean') ? show : !sb.classList.contains('show');

    if (willShow) {
      sb.classList.add('show');
      if (isMobile()) {
        ov && ov.classList.add('show');
        document.body.style.overflow = 'hidden';
      }
    } else {
      sb.classList.remove('show');
      ov && ov.classList.remove('show');
      document.body.style.overflow = '';
    }
  };

  // klik overlay = tutup (HP)
  document.getElementById('sidebarOverlay')?.addEventListener('click', ()=> window.toggleSidebar(false));

  // Tutup saat klik menu (HP)
  const mq = window.matchMedia('(max-width: 991.98px)');
  document.querySelectorAll('#sidebar .nav-link').forEach(a=>{
    a.addEventListener('click', function(){ if (mq.matches) window.toggleSidebar(false); });
  });

  // Bersihkan state saat resize ke desktop
  window.addEventListener('resize', ()=>{
    if (!mq.matches) {
      document.getElementById('sidebarOverlay')?.classList.remove('show');
      document.body.style.overflow = '';
    }
  });
})();
</script>
