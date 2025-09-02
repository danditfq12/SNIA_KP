<?php
  // path helper & checker seperti di audience
  $path = trim(service('uri')->getPath(), '/');
  function isActiveR($patterns, $path){
    foreach ((array)$patterns as $p){
      $p = trim($p, '/');
      if ($p === $path) return true;
      if (substr($p, -2) === '/*'){
        $prefix = rtrim($p, '/*');
        if ($prefix==='' || strpos($path, $prefix)===0) return true;
      } else {
        if (strpos($path, $p)===0) return true;
      }
    }
    return false;
  }
?>
<aside id="sidebar" class="sidebar p-3 shadow-sm">
  <div class="d-flex align-items-center justify-content-between mb-4">
    <div class="d-flex align-items-center gap-2">
      <div class="rounded-3 p-2 bg-primary-subtle text-primary"><i class="bi bi-sliders"></i></div>
      <span class="brand fw-bold">SNIA Reviewer</span>
    </div>
    <button class="btn btn-sm d-lg-none" onclick="toggleSidebar()" aria-label="Tutup menu">
      <i class="bi bi-x-lg"></i>
    </button>
  </div>

  <nav class="nav flex-column">
    <a class="nav-link py-2 px-3 rounded <?= isActiveR('reviewer/dashboard', $path) ? 'active' : '' ?>"
       href="<?= site_url('reviewer/dashboard') ?>"><i class="bi bi-speedometer2 me-2"></i>Dashboard</a>

    <a class="nav-link py-2 px-3 rounded <?= isActiveR(['reviewer/abstrak','reviewer/abstrak/*'], $path) ? 'active' : '' ?>"
       href="<?= site_url('reviewer/abstrak') ?>"><i class="bi bi-file-earmark-text me-2"></i>Abstrak</a>

    <a class="nav-link py-2 px-3 rounded <?= isActiveR('reviewer/riwayat', $path) ? 'active' : '' ?>"
       href="<?= site_url('reviewer/riwayat') ?>"><i class="bi bi-clock-history me-2"></i>Riwayat</a>
  </nav>
</aside>

<style>
  /* sama dengan audience */
  #sidebar{
    position:fixed;
    top:var(--topbar-h);
    left:0;
    width:var(--sidebar-w);
    background:#fff !important;
    border-right:1px solid var(--ring);
    height:calc(100vh - var(--topbar-h));
    overflow-y:auto;
    transition:transform .3s ease-in-out;
    z-index:1050 !important; /* di atas overlay */
    box-shadow:none !important;
  }

  .nav-link{ color:#444; transition:all .25s ease; }
  .nav-link:hover{ background:#f1f5f9; padding-left:1.5rem; }
  .nav-link.active{ background:#0d6efd; color:#fff !important; font-weight:600; }

  @media(min-width:992px){
    #content{ margin-left:var(--sidebar-w); transition:margin-left .3s ease; }
  }
  @media(max-width:991px){
    #sidebar{ top:0; height:100vh; transform:translateX(-100%); }
    #sidebar.show{ transform:translateX(0); }
    #content{ margin-left:0; }
  }
</style>

<script>
  // Tutup sidebar saat klik menu di HP (sinkron dengan overlay di footer)
  (function(){
    const mq = window.matchMedia('(max-width: 991px)');
    document.querySelectorAll('#sidebar .nav-link').forEach(a=>{
      a.addEventListener('click', function(){
        if (mq.matches) {
          const sb = document.getElementById('sidebar');
          const ov = document.getElementById('overlay');
          sb.classList.remove('show');
          ov?.classList.remove('show','delayed','visible');
          document.body.classList.remove('sb-open');
        }
      });
    });
  })();
</script>
