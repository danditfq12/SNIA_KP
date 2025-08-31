<?php helper('filesystem'); ?>
<!doctype html>
<html>
<head>
  <meta charset="utf-8">
  <title><?= isset($title) ? esc($title).' - SNIA' : 'SNIA' ?></title>
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body{background:#f5f7fb;margin:0;}
    .topbar{background:#fff;border-bottom:1px solid #eef0f4;position:fixed;top:0;left:0;width:100%;z-index:1000;}
    .btn-ghost{background:transparent;border:0;box-shadow:none}
    .avatar{width:36px;height:36px;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-size:.95rem}
    .nameblock .nm{font-weight:600;font-size:.95rem;line-height:1}
    .nameblock .rl{font-size:.78rem;color:#6b7280;line-height:1}
    .notif-badge{position:absolute;top:-6px;right:-6px;min-width:16px;height:16px;border-radius:999px;background:#ef4444;color:#fff;font-size:.65rem;line-height:16px;text-align:center}
  </style>
</head>
<body>
  <header class="topbar px-3 py-2 d-flex align-items-center justify-content-between">
    <div class="d-flex align-items-center gap-3">
      <!-- Tombol toggle sidebar di mobile -->
      <button class="btn btn-ghost d-lg-none" onclick="toggleSidebar()"><i class="bi bi-list fs-4"></i></button>
      <div class="small text-muted">Home / <span class="text-dark"><?= isset($breadcrumb) ? esc($breadcrumb) : 'Dashboard' ?></span></div>
    </div>

    <div class="d-flex align-items-center gap-3">
      <?php
        $notifs = $notifs ?? [];
        $unreadCount = count(array_filter($notifs, fn($n) => empty($n['read']) || $n['read'] == false));
        $nama = session('nama_lengkap') ?? 'User';
        $email = session('email') ?? '';
        $role = ucfirst(session('role') ?? '-');
      ?>

      <!-- Notifikasi -->
      <div class="dropdown">
        <button class="btn btn-ghost position-relative" data-bs-toggle="dropdown" aria-expanded="false" type="button">
          <i class="bi bi-bell fs-5"></i>
          <?php if($unreadCount>0): ?><span class="notif-badge"><?= $unreadCount ?></span><?php endif; ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end p-0" style="min-width:320px;">
          <li class="px-3 py-2 fw-semibold">Notifikasi</li>
          <li><hr class="dropdown-divider my-1"></li>
          <?php if(!empty($notifs)): ?>
            <?php foreach($notifs as $n): 
              $isRead = !empty($n['read']);
              $icon = ($n['type'] ?? '') === 'deadline' ? 'bi-exclamation-triangle text-warning' : 'bi-info-circle text-primary';
            ?>
              <li>
                <a class="dropdown-item d-flex align-items-start gap-2 <?= $isRead ? 'text-muted' : '' ?>"
                   href="<?= site_url($n['link'] ?? '#') ?>">
                  <i class="bi <?= $icon ?> mt-1"></i>
                  <div class="small">
                    <div class="fw-semibold"><?= esc($n['title'] ?? '-') ?></div>
                    <div class="text-muted"><?= esc($n['time'] ?? '') ?></div>
                  </div>
                </a>
              </li>
            <?php endforeach; ?>
          <?php else: ?>
            <li class="px-3 py-2 text-muted">Tidak ada notifikasi</li>
          <?php endif; ?>
          <li><hr class="dropdown-divider my-1"></li>
          <li class="text-center"><a class="dropdown-item small" href="<?= site_url('reviewer/notif/read-all') ?>">Tandai semua terbaca</a></li>
        </ul>
      </div>

      <!-- Profile dropdown -->
      <div class="dropdown">
        <button class="btn btn-ghost dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown" type="button">
          <div class="nameblock d-none d-sm-block text-end">
            <div class="nm"><?= esc($nama) ?></div>
            <div class="rl"><?= esc($role) ?></div>
          </div>
          <span class="avatar bg-primary text-white"><i class="bi bi-person"></i></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
          <li class="dropdown-header px-3">
            <div class="fw-semibold"><?= esc($nama) ?></div>
            <small class="text-muted"><?= esc($email) ?></small>
          </li>
          <li><hr class="dropdown-divider my-1"></li>
          <li><a class="dropdown-item" href="<?= site_url('profile') ?>"><i class="bi bi-person-circle me-2"></i>Profile</a></li>
          <li><a class="dropdown-item" href="<?= site_url('auth/logout') ?>"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
        </ul>
      </div>
    </div>
  </header>
