<?php helper('filesystem'); ?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title><?= isset($title) ? esc($title).' - SNIA' : 'SNIA' ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <!-- CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    /* ==== SIZE OVERRIDES ONLY ==== */
    :root{
      /* lebih tinggi & lebih lebar */
      --topbar-h: 80px;           /* semula 64px */
      --sidebar-w: 260px;         /* semula 220px */
      --ring:#eef0f4;
    }
    html,body{ background:#f5f7fb; margin:0; }

    .topbar{
      background:#fff; border-bottom:1px solid var(--ring);
      position:fixed; inset:0 0 auto 0; height:var(--topbar-h); z-index:1000;
      display:flex; align-items:center; justify-content:space-between;
      padding:0 1.25rem;          /* sedikit lebih lebar */
    }
    .btn-ghost{ background:transparent; border:0; box-shadow:none; }
    .avatar{ width:42px; height:42px; border-radius:50%; object-fit:cover; } /* semula 36px */

    #content{ padding-top:var(--topbar-h) !important; }
    #content main{ padding-top:0 !important; }

    .dropdown-menu-prof { min-width:260px; } /* semula 240px */
    .topbar .fw-semibold{ font-size:1.1rem; } /* judul sedikit lebih besar */

    @media (max-width:576px){
      :root{ --topbar-h: 70px; }  /* hp tetap proporsional */
      .dropdown-menu-prof { min-width:200px; font-size:.95rem; }
      .nameblock{ display:none; }
      .avatar{ width:38px; height:38px; }
    }
  </style>
</head>
<body>
  <header class="topbar">
    <div class="d-flex align-items-center gap-2">
      <button class="btn btn-ghost d-lg-none" onclick="toggleSidebar && toggleSidebar()" aria-label="Menu">
        <i class="bi bi-list fs-3"></i>  <!-- icon sedikit lebih besar -->
      </button>
      <div class="fw-semibold"><?= esc($title ?? 'SNIA') ?></div>
    </div>

    <div class="d-flex align-items-center gap-3">
      <?php
        $nama  = session('nama_lengkap') ?? session('nama') ?? 'User';
        $email = session('email') ?? '';
        $foto     = session('foto') ?: 'default.png';
        $fotoVer  = session('foto_ver') ?: time();
        $avatarUrl= base_url('uploads/profile/' . $foto) . '?v=' . $fotoVer;
      ?>

      <div class="dropdown">
        <button class="btn btn-ghost dropdown-toggle d-flex align-items-center gap-2"
                data-bs-toggle="dropdown" type="button" aria-label="Profil">
          <div class="nameblock d-none d-sm-block text-end">
            <div class="fw-semibold"><?= esc($nama) ?></div>
          </div>

          <img src="<?= $avatarUrl ?>" class="avatar" alt="Avatar"
               onerror="this.outerHTML='<span class=&quot;avatar bg-primary text-white d-inline-flex justify-content-center align-items-center&quot; style=&quot;width:42px;height:42px;border-radius:50%;&quot;><i class=&quot;bi bi-person&quot;></i></span>';">
        </button>

        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-prof">
          <li class="dropdown-header px-3">
            <div class="fw-semibold"><?= esc($nama) ?></div>
            <?php if ($email): ?><small class="text-muted"><?= esc($email) ?></small><?php endif; ?>
          </li>
          <li><hr class="dropdown-divider my-1"></li>
          <li>
            <a class="dropdown-item" href="<?= site_url('profile') ?>">
              <i class="bi bi-person-circle me-2"></i>Profil
            </a>
          </li>
          <li>
            <a class="dropdown-item" href="<?= site_url('auth/logout') ?>">
              <i class="bi bi-box-arrow-right me-2"></i>Logout
            </a>
          </li>
        </ul>
      </div>
    </div>
  </header>
