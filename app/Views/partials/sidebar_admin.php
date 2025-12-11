<?php
$uri   = service('uri');
$seg1  = strtolower($uri->getSegment(1) ?? '');
$seg2  = strtolower($uri->getSegment(2) ?? '');
$seg2  = $seg2 === '' ? 'dashboard' : $seg2; // /admin → dashboard

// Route yang dianggap satu grup dengan "Kelola Paper"
$kelolaPaperGroup = ['kelola-paper', 'fullpaper', 'abstrak'];

// Optional override dari view, contoh:
// $GLOBALS['activeMenu'] = 'kelola_paper' atau 'landing';
$activeMenu = $GLOBALS['activeMenu'] ?? null;

/**
 * Aturan aktif:
 * - 'dashboard'  → /admin/dashboard
 * - 'kelola-paper' → /admin/{kelola-paper|fullpaper|abstrak}/**
 * - 'landing'    → /admin/landing/**
 * - selain itu   → segmen ke-2 harus sama dengan slug
 * - override     → lewat $activeMenu
 */
$active = function (string $slug) use ($seg1, $seg2, $kelolaPaperGroup, $activeMenu): bool {
    if ($seg1 !== 'admin') return false;

    if ($slug === 'dashboard') {
        return in_array($seg2, ['dashboard'], true);
    }

    if ($slug === 'kelola-paper') {
        if (in_array($seg2, $kelolaPaperGroup, true)) return true;
        if ($activeMenu === 'kelola_paper') return true;
        return false;
    }

    if ($slug === 'landing') {
        if ($seg2 === 'landing') return true;
        if ($activeMenu === 'landing') return true;
        return false;
    }

    // default: cocokkan slug dengan segmen ke-2
    return $seg2 === strtolower($slug);
};
?>
<style>
  :root{ --admin-sidebar-w: 300px; }
  body{ padding-left: var(--admin-sidebar-w) !important; }
  .admin-sidebar{
    position:fixed; left:0; top:0; height:100vh; width:var(--admin-sidebar-w);
    background: linear-gradient(180deg,#2563eb 0%,#1e40af 100%);
    box-shadow: 4px 0 22px rgba(0,0,0,.12);
    z-index:1030; overflow-y:auto; color:#fff;
  }
  .admin-sidebar::-webkit-scrollbar{ width:10px }
  .admin-sidebar::-webkit-scrollbar-thumb{ background:rgba(255,255,255,.25); border-radius:8px }
  .admin-sidebar .brand-wrap{
    padding:14px 18px;
    border-bottom:1px solid rgba(255,255,255,.18);
  }
  .admin-sidebar .brand-wrap .title{ font-weight:700; }
  .admin-sidebar .brand-wrap .sub{ color:rgba(255,255,255,.7); font-size:.85rem }
  .admin-sidebar .menu-label{
    color:rgba(255,255,255,.6); font-size:.75rem; text-transform:uppercase;
    letter-spacing:.06em; padding:10px 14px 6px; margin-top:8px;
  }
  .admin-sidebar .nav-link{
    position:relative; display:flex; align-items:center; gap:12px;
    color:rgba(255,255,255,.92); text-decoration:none; padding:11px 14px; margin:4px 8px;
    border-radius:12px; transition:.18s ease;
  }
  .admin-sidebar .nav-link .ico{
    width:28px; height:28px; display:grid; place-items:center;
    font-size:1.05rem; color:#fff; background:rgba(255,255,255,.14); border-radius:10px;
  }
  .admin-sidebar .nav-link:hover{
    background:rgba(255,255,255,.12); color:#fff; transform:translateX(4px);
  }
  .admin-sidebar .nav-link.is-active{
    background:rgba(255,255,255,.22); color:#fff;
    box-shadow:0 6px 18px rgba(0,0,0,.12) inset;
  }
  .admin-sidebar .nav-link .active-pill,
  .admin-sidebar .nav-link::after{
    display:none !important; content:none !important;
  }
</style>

<aside class="admin-sidebar">
  <div class="brand-wrap d-flex align-items-center gap-2">
    <i class="bi bi-gear-fill fs-5"></i>
    <div>
      <div class="title">SNIA Admin</div>
      <div class="sub">Sistem Manajemen</div>
    </div>
  </div>

  <nav class="nav flex-column px-2 py-2">
    <!-- Dashboard -->
    <a class="nav-link <?= $active('dashboard') ? 'is-active':'' ?>" href="<?= site_url('admin/dashboard') ?>">
      <span class="ico"><i class="bi bi-speedometer2"></i></span><span>Dashboard</span>
    </a>

    <!-- Manajemen -->
    <div class="menu-label">Manajemen</div>
    <a class="nav-link <?= $active('users') ? 'is-active':'' ?>" href="<?= site_url('admin/users') ?>">
      <span class="ico"><i class="bi bi-people-fill"></i></span><span>Manajemen User</span>
    </a>
    <a class="nav-link <?= $active('kategori') ? 'is-active':'' ?>" href="<?= site_url('admin/kategori') ?>">
      <span class="ico"><i class="bi bi-tags-fill"></i></span><span>Kategori Abstrak</span>
    </a>
    <a class="nav-link <?= $active('pembayaran') ? 'is-active':'' ?>" href="<?= site_url('admin/pembayaran') ?>">
      <span class="ico"><i class="bi bi-credit-card"></i></span><span>Verifikasi Pembayaran</span>
    </a>
    <a class="nav-link <?= $active('reviewer') ? 'is-active':'' ?>" href="<?= site_url('admin/reviewer') ?>">
      <span class="ico"><i class="bi bi-person-check"></i></span><span>Manajemen Reviewer</span>
    </a>

    <!-- Kelola -->
    <div class="menu-label">Kelola</div>
    <a class="nav-link <?= $active('event') ? 'is-active':'' ?>" href="<?= site_url('admin/event') ?>">
      <span class="ico"><i class="bi bi-calendar2-event"></i></span><span>Kelola Event</span>
    </a>
    <a class="nav-link <?= $active('kelola-paper') ? 'is-active':'' ?>" href="<?= site_url('admin/kelola-paper') ?>">
      <span class="ico"><i class="bi bi-journal-richtext"></i></span><span>Kelola Paper</span>
    </a>
    <a class="nav-link <?= $active('absensi') ? 'is-active':'' ?>" href="<?= site_url('admin/absensi') ?>">
      <span class="ico"><i class="bi bi-qr-code-scan"></i></span><span>Kelola Absensi</span>
    </a>
    <a class="nav-link <?= $active('voucher') ? 'is-active':'' ?>" href="<?= site_url('admin/voucher') ?>">
      <span class="ico"><i class="bi bi-ticket-detailed"></i></span><span>Kelola Voucher</span>
    </a>
    <a class="nav-link <?= $active('dokumen') ? 'is-active':'' ?>" href="<?= site_url('admin/dokumen') ?>">
      <span class="ico"><i class="bi bi-folder2-open"></i></span><span>Kelola Dokumen</span>
    </a>

    <!-- Kelola Landing -->
    <a class="nav-link <?= $active('landing') ? 'is-active':'' ?>" href="<?= site_url('admin/landing') ?>">
      <span class="ico"><i class="bi bi-display"></i></span><span>Kelola Landing</span>
    </a>

    <!-- Fasilitas & Benefit -->
    <a class="nav-link <?= $active('fasilitas') ? 'is-active':'' ?>" href="<?= site_url('admin/fasilitas') ?>">
      <span class="ico"><i class="bi bi-star-fill"></i></span><span>Fasilitas &amp; Benefit</span>
    </a>

    <!-- Pelaporan -->
    <div class="menu-label">Pelaporan</div>
    <a class="nav-link <?= $active('laporan') ? 'is-active':'' ?>" href="<?= site_url('admin/laporan') ?>">
      <span class="ico"><i class="bi bi-graph-up-arrow"></i></span><span>Laporan</span>
    </a>
  </nav>
</aside>
