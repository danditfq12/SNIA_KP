<?php
// Aktifkan menu berdasar URI
$uri  = service('uri');
$seg1 = strtolower($uri->getSegment(1) ?? '');
$seg2 = strtolower($uri->getSegment(2) ?? '');
$active = function (string $slug) use ($seg1, $seg2): bool {
    if ($slug === 'dashboard') return ($seg1 === 'admin' && ($seg2 === '' || $seg2 === 'dashboard'));
    return $seg1 === 'admin' && $seg2 === strtolower($slug);
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
    padding:14px 18px; border-bottom:1px solid rgba(255,255,255,.18)
  }
  .admin-sidebar .brand-wrap .title{ font-weight:700; }
  .admin-sidebar .brand-wrap .sub{ color:rgba(255,255,255,.7); font-size:.85rem }

  .admin-sidebar .menu-label{
    color:rgba(255,255,255,.6); font-size:.75rem; text-transform:uppercase;
    letter-spacing:.06em; padding:10px 14px 6px; margin-top:8px
  }

  .admin-sidebar .nav-link{
    position:relative; display:flex; align-items:center; gap:12px;
    color:rgba(255,255,255,.92); text-decoration:none;
    padding:11px 14px; margin:4px 8px; border-radius:12px;
    transition:.18s ease;
  }
  .admin-sidebar .nav-link .ico{
    width:28px; height:28px; display:grid; place-items:center;
    font-size:1.05rem; color:#fff; background:rgba(255,255,255,.14); border-radius:10px
  }
  .admin-sidebar .nav-link:hover{ background:rgba(255,255,255,.12); color:#fff; transform:translateX(4px) }
  .admin-sidebar .nav-link.is-active{
    background:rgba(255,255,255,.22); color:#fff; box-shadow:0 6px 18px rgba(0,0,0,.12) inset
  }
  .admin-sidebar .nav-link .active-pill,
  .admin-sidebar .nav-link::after{ display:none !important; content:none !important; }
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
    <a class="nav-link <?= $active('dashboard') ? 'is-active':'' ?>" href="<?= site_url('admin/dashboard') ?>">
      <span class="ico"><i class="bi bi-speedometer2"></i></span><span>Dashboard</span>
    </a>

    <!-- Bagian Manajemen -->
    <div class="menu-label">Manajemen</div>
    <a class="nav-link <?= $active('users') ? 'is-active':'' ?>" href="<?= site_url('admin/users') ?>">
      <span class="ico"><i class="bi bi-people-fill"></i></span><span>Manajemen User</span>
    </a>
    <a class="nav-link <?= $active('abstrak') ? 'is-active':'' ?>" href="<?= site_url('admin/abstrak') ?>">
      <span class="ico"><i class="bi bi-file-earmark-text"></i></span><span>Manajemen Abstrak</span>
    </a>
    <a class="nav-link <?= $active('pembayaran') ? 'is-active':'' ?>" href="<?= site_url('admin/pembayaran') ?>">
      <span class="ico"><i class="bi bi-credit-card"></i></span><span>Verifikasi Pembayaran</span>
    </a>

    <!-- Bagian Kelola -->
    <div class="menu-label">Kelola</div>
    <a class="nav-link <?= $active('event') ? 'is-active':'' ?>" href="<?= site_url('admin/event') ?>">
      <span class="ico"><i class="bi bi-calendar2-event"></i></span><span>Kelola Event</span>
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

    <!-- Bagian Pelaporan -->
    <div class="menu-label">Pelaporan</div>
    <a class="nav-link <?= $active('laporan') ? 'is-active':'' ?>" href="<?= site_url('admin/laporan') ?>">
      <span class="ico"><i class="bi bi-graph-up-arrow"></i></span><span>Laporan</span>
    </a>
  </nav>
</aside>