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
    :root{ --topbar-h:64px; --sidebar-w:220px; --ring:#eef0f4; }
    html,body{ background:#f5f7fb; margin:0; }

    .topbar{
      background:#fff; border-bottom:1px solid var(--ring);
      position:fixed; inset:0 0 auto 0; height:var(--topbar-h); z-index:1000;
      display:flex; align-items:center; justify-content:space-between; padding:0 1rem;
    }
    .btn-ghost{ background:transparent; border:0; box-shadow:none; }
    .avatar{ width:36px; height:36px; border-radius:50%; object-fit:cover; }
    .notif-badge{ position:absolute; top:-6px; right:-6px; min-width:16px; height:16px; border-radius:999px; background:#ef4444; color:#fff; font-size:.65rem; line-height:16px; text-align:center }

    #content{ padding-top:var(--topbar-h) !important; }
    #content main{ padding-top:0 !important; }

    .dropdown-menu-notif{ min-width:320px; }
    .dropdown-menu-prof { min-width:240px; }
    @media (max-width:576px){
      .dropdown-menu-notif{ min-width:240px; font-size:.9rem; }
      .dropdown-menu-prof { min-width:200px; font-size:.9rem; }
      .nameblock{ display:none; }
    }
  </style>
</head>
<body>
  <header class="topbar">
    <div class="d-flex align-items-center gap-2">
      <button class="btn btn-ghost d-lg-none" onclick="toggleSidebar()" aria-label="Menu">
        <i class="bi bi-list fs-4"></i>
      </button>
      <div class="fw-semibold"><?= esc($title ?? 'SNIA') ?></div>
    </div>

    <div class="d-flex align-items-center gap-2">
      <?php
        $notifs = $notifs ?? [];
        $unreadCount = count(array_filter($notifs, fn($n) => empty($n['read']) || $n['read'] == false));
        $nama  = session('nama_lengkap') ?? session('nama') ?? 'User';
        $email = session('email') ?? '';
      ?>

      <!-- Notifikasi -->
      <div class="dropdown">
        <button id="btnNotif" class="btn btn-ghost position-relative" data-bs-toggle="dropdown" aria-expanded="false" type="button" aria-label="Notifikasi">
          <i class="bi bi-bell fs-5"></i>
          <span id="notifBadge" class="notif-badge" style="<?= $unreadCount>0?'':'display:none;' ?>"><?= $unreadCount ?></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end p-0 dropdown-menu-notif" id="notifMenu">
          <li class="px-3 py-2 fw-semibold">Notifikasi</li>
          <li><hr class="dropdown-divider my-1"></li>

          <div id="notifItems">
            <?php if (!empty($notifs)): ?>
              <?php foreach ($notifs as $n):
                $isRead = !empty($n['read']);
                $icon   = ($n['type'] ?? '') === 'deadline'
                          ? 'bi-exclamation-triangle text-warning'
                          : 'bi-info-circle text-primary';
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
              <li class="px-3 py-2 text-muted small">Tidak ada notifikasi</li>
            <?php endif; ?>
          </div>

          <li><hr class="dropdown-divider my-1"></li>
          <li class="text-center">
            <a href="#" class="dropdown-item small js-mark-all-read">Tandai semua terbaca</a>
          </li>
        </ul>
      </div>

      <!-- Profile -->
      <div class="dropdown">
        <button class="btn btn-ghost dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown" type="button" aria-label="Profil">
          <div class="nameblock d-none d-sm-block text-end">
            <div class="fw-semibold"><?= esc($nama) ?></div>
          </div>

          <?php
            $foto     = session('foto') ?: 'default.png';
            $fotoVer  = session('foto_ver') ?: time();
            $avatarUrl= base_url('uploads/profile/' . $foto) . '?v=' . $fotoVer;
          ?>
          <img src="<?= $avatarUrl ?>" class="avatar" alt="Avatar"
               onerror="this.outerHTML='<span class=&quot;avatar bg-primary text-white d-inline-flex justify-content-center align-items-center&quot; style=&quot;width:36px;height:36px;border-radius:50%;&quot;><i class=&quot;bi bi-person&quot;></i></span>';">
        </button>

        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-prof">
          <li class="dropdown-header px-3">
            <div class="fw-semibold"><?= esc($nama) ?></div>
            <?php if ($email): ?>
              <small class="text-muted"><?= esc($email) ?></small>
            <?php endif; ?>
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

  <script>
    async function refreshNotifUI() {
      try {
        const res = await fetch('<?= site_url('notif/recent?limit=10') ?>', {headers:{'X-Requested-With':'XMLHttpRequest'}});
        const json = await res.json();
        if (!json.ok) return;

        const items = json.items || [];
        const unread = items.filter(i => !i.read).length;

        // badge
        const badge = document.getElementById('notifBadge');
        if (badge){
          if (unread > 0){ badge.style.display = ''; badge.textContent = unread; }
          else { badge.style.display = 'none'; }
        }

        // list
        const wrap = document.getElementById('notifItems');
        if (!wrap) return;
        wrap.innerHTML = '';
        if (items.length === 0){
          wrap.innerHTML = '<li class="px-3 py-2 text-muted small">Tidak ada notifikasi</li>';
          return;
        }
        items.forEach(n => {
          const icon = (n.type === 'deadline')
            ? 'bi-exclamation-triangle text-warning'
            : 'bi-info-circle text-primary';
          const muted = n.read ? 'text-muted' : '';
          const link  = n.link ? n.link : '#';
          wrap.insertAdjacentHTML('beforeend', `
            <li>
              <a class="dropdown-item d-flex align-items-start gap-2 ${muted}" href="${link}">
                <i class="bi ${icon} mt-1"></i>
                <div class="small">
                  <div class="fw-semibold">${(n.title||'-')
                      .replace(/&/g,'&amp;').replace(/</g,'&lt;')}</div>
                  <div class="text-muted">${n.time||''}</div>
                </div>
              </a>
            </li>
          `);
        });
      } catch (e) {
        console.error('refreshNotifUI failed', e);
      }
    }

    document.addEventListener('click', async (e)=>{
      const a = e.target.closest('.js-mark-all-read');
      if (!a) return;

      e.preventDefault();

      try {
        // pakai POST kalau route sudah ditambahkan; kalau belum, GET juga oke.
        const res = await fetch('<?= site_url('notif/read-all') ?>', {
          method: 'POST',
          headers: {'X-Requested-With':'XMLHttpRequest'}
        });
        const j = await res.json().catch(()=>({ok:false}));

        if (j.ok){
          await refreshNotifUI();
        } else {
          // fallback: kalau server redirect (GET), tetap refresh list
          await refreshNotifUI();
        }
      } catch (err) {
        console.error(err);
      }
    });

    // Optional: refresh ketika dropdown dibuka
    document.getElementById('btnNotif')?.addEventListener('click', ()=> {
      setTimeout(refreshNotifUI, 50);
    });
  </script>
