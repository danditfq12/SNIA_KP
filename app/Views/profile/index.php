<?php helper('filesystem'); ?>
<?php
  $title   = $title ?? 'Profil Saya';
  $user    = $user  ?? [];

  // Role: ambil dari variabel, session, atau $user; fallback audience
  $roleRaw = $role ?? session('role') ?? ($user['role'] ?? 'audience');
  $role    = strtolower(trim((string)$roleRaw)) ?: 'audience';

  $avatar  = $avatar ?? '';
  $events  = (int)($events_count ?? 0);
  $loaCnt  = (int)($loa_count ?? 0);
  $certCnt = (int)($cert_count ?? 0);
  $docs    = $docs ?? ['loa'=>[], 'sertifikat'=>[]];

  // Data navbar
  $nama        = session('nama_lengkap') ?? session('nama') ?? ($user['nama_lengkap'] ?? 'User');
  $email       = session('email') ?? ($user['email'] ?? '');
  $foto        = session('foto') ?: ($user['foto'] ?? 'default.png');
  $fotoVer     = session('foto_ver') ?: time();
  $avatarUrlNav= base_url('uploads/profile/'.$foto).'?v='.$fotoVer;
?>
<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <title><?= esc($title) ?> - SNIA</title>
  <meta name="viewport" content="width=device-width, initial-scale=1">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    /* ===== Design Tokens (selaras dengan layout admin) ===== */
    :root{
      --topbar-h: 72px;
      --ring: #e9eef6;
      --bg: #f6f8fc;
      --text: #0f172a;
      --muted: #6b7280;
      --brand: #2563eb;
      --brand-2: #1e40af;
      --radius: 14px;
      --radius-lg: 18px;
      --shadow: 0 8px 20px rgba(15,23,42,.06);
      --shadow-in: inset 0 1px 0 rgba(255,255,255,.6);
      --space-1: .5rem;   /* 8px  */
      --space-2: .75rem;  /* 12px */
      --space-3: 1rem;    /* 16px */
      --space-4: 1.25rem; /* 20px */
      --space-5: 1.5rem;  /* 24px */
    }
    html,body{ background:var(--bg); color:var(--text); margin:0; }

    /* ===== Topbar ===== */
    .topbar{
      position:fixed; inset:0 0 auto 0; height:var(--topbar-h);
      background:#fff; border-bottom:1px solid var(--ring);
      display:flex; align-items:center; justify-content:space-between;
      padding:0 var(--space-4); z-index:1000;
    }
    .btn-ghost{ background:transparent; border:0; box-shadow:none; padding:0 var(--space-1); }
    .btn-ghost:focus{ outline:2px solid rgba(37,99,235,.3); outline-offset:2px; border-radius:10px; }
    .avatar{ width:42px; height:42px; border-radius:50%; object-fit:cover; }
    .dropdown-menu-prof{ min-width:260px; }
    #content{ padding-top:var(--topbar-h); }

    /* ===== Hero ===== */
    .cover{
      height:220px; border-radius:var(--radius-lg);
      background:
        radial-gradient(900px 200px at 15% 0, #dbeafe 0%, #e0e7ff 45%, #eef2ff 100%),
        linear-gradient(180deg, #fff 0, #fff0 100%);
      border:1px solid var(--ring);
      box-shadow: var(--shadow);
    }
    .hero-card{
      margin-top:-56px; border-radius:var(--radius-lg);
      box-shadow: var(--shadow); border:1px solid #eef2f7;
      background:#fff;
    }
    .avatar-lg{
      width:112px; height:112px; border-radius:50%; object-fit:cover;
      border:5px solid #fff; box-shadow: var(--shadow);
    }
    .stat .num{ font-weight:800; font-size:1.25rem; line-height:1; }
    .stat .lbl{ color:var(--muted); font-size:.86rem; }

    /* ===== Cards / Elements ===== */
    .card-soft{ border-radius:var(--radius); box-shadow: var(--shadow); border:1px solid #eef2f7; background:#fff; }
    .card-header{ background:#f9fafb !important; border-bottom:1px solid var(--ring); }
    .file-row{
      border:1px solid #eef2f7; border-radius:12px; padding:12px 14px; background:#fff;
      display:flex; align-items:center; justify-content:space-between; transition: .15s ease;
    }
    .file-row:hover{ transform: translateY(-1px); box-shadow:0 6px 16px rgba(0,0,0,.05); }
    .file-name{ display:flex; align-items:center; gap:.5rem; color:#111827; }
    .file-meta{ color:var(--muted); font-size:.85rem; }

    /* ===== Tabs ===== */
    .nav-tabs{
      gap:.25rem; border:0; position:sticky; top:calc(var(--topbar-h) - 1px); z-index: 900;
    }
    .nav-tabs .nav-link{
      border:1px solid transparent; border-bottom:3px solid transparent; border-radius:12px 12px 0 0;
      padding:.6rem 1rem; color:#334155; background:#fff; box-shadow: var(--shadow-in);
    }
    .nav-tabs .nav-link:hover{ color:#111827; }
    .nav-tabs .nav-link.active{
      color:#0f172a; border-color:transparent transparent var(--brand); background:#fff;
      font-weight:600;
    }

    /* ===== Buttons ===== */
    .btn-outline-primary{ border-color: rgba(37,99,235,.35); }
    .btn-outline-primary:hover{ background:rgba(37,99,235,.08); border-color:#2563eb; }
    .btn-warning{ --bs-btn-bg:#f59e0b; --bs-btn-border-color:#f59e0b; --bs-btn-hover-bg:#d97706; --bs-btn-hover-border-color:#d97706; }

    /* ===== Responsive ===== */
    @media (max-width: 576px){
      :root{ --topbar-h: 68px; }
      .dropdown-menu-prof{ min-width:220px; font-size:.95rem; }
      .nameblock{ display:none; }
      .avatar{ width:38px; height:38px; }
      .avatar-lg{ width:92px; height:92px; }
      .stat .num{ font-size:1.1rem; }
      .hero-card{ margin-top:-46px; }
    }
  </style>
</head>
<body>
  <!-- ===== NAVBAR ===== -->
  <header class="topbar">
    <div class="d-flex align-items-center gap-2">
      <button class="btn btn-ghost" type="button" id="btnBack" aria-label="Kembali" title="Kembali">
        <i class="bi bi-arrow-left fs-5"></i>
      </button>
      <div class="fw-semibold"><?= esc($title) ?></div>
    </div>

    <div class="d-flex align-items-center gap-3">
      <div class="dropdown">
        <button class="btn btn-ghost dropdown-toggle d-flex align-items-center gap-2"
                data-bs-toggle="dropdown" type="button" aria-label="Profil">
          <div class="nameblock d-none d-sm-block text-end">
            <div class="fw-semibold"><?= esc($nama) ?></div>
            <?php if($email): ?><small class="text-muted"><?= esc($email) ?></small><?php endif; ?>
          </div>
          <img src="<?= esc($avatarUrlNav) ?>" class="avatar" alt="Avatar"
               onerror="this.outerHTML='<span class=&quot;avatar bg-primary text-white d-inline-flex justify-content-center align-items-center&quot; style=&quot;width:42px;height:42px;border-radius:50%;&quot;><i class=&quot;bi bi-person&quot;></i></span>';"/>
        </button>
        <ul class="dropdown-menu dropdown-menu-end dropdown-menu-prof">
          <li class="dropdown-header px-3">
            <div class="fw-semibold"><?= esc($nama) ?></div>
            <?php if($email): ?><small class="text-muted"><?= esc($email) ?></small><?php endif; ?>
          </li>
          <li><hr class="dropdown-divider my-1"></li>
          <li><a class="dropdown-item" href="<?= site_url('profile') ?>"><i class="bi bi-person-circle me-2"></i>Profil</a></li>
          <li>
            <button class="dropdown-item js-logout" type="button">
              <i class="bi bi-box-arrow-right me-2"></i>Logout
            </button>
            <form id="logoutForm" action="<?= site_url('auth/logout') ?>" method="post" class="d-none">
              <?= csrf_field() ?>
            </form>
          </li>
        </ul>
      </div>
    </div>
  </header>

  <div id="content">
    <main class="py-4">
      <div class="container">

        <!-- ===== HERO ===== -->
        <div class="cover"></div>
        <div class="card hero-card p-3 p-md-4">
          <div class="row g-3 align-items-center">
            <div class="col-auto text-center">
              <img src="<?= esc($avatar ?: $avatarUrlNav) ?>" class="avatar-lg" alt="avatar"
                   onerror="this.src='<?= base_url('uploads/profile/default.png') ?>'">
            </div>
            <div class="col-md">
              <h4 class="mb-1"><?= esc($user['nama_lengkap'] ?? '-') ?></h4>
              <div class="text-muted small"><?= esc($user['email'] ?? '-') ?></div>
              <div class="mt-3">
                <form action="<?= site_url('profile/upload-photo') ?>" method="post" enctype="multipart/form-data" class="d-inline-block">
                  <?= csrf_field() ?>
                  <label class="btn btn-outline-primary btn-sm">
                    <i class="bi bi-camera me-1"></i> Ganti Foto
                    <input type="file" name="foto" accept=".jpg,.jpeg,.png,.webp" hidden onchange="this.form.submit()">
                  </label>
                </form>
              </div>
            </div>
            <div class="col-auto">
              <div class="d-flex gap-4">
                <div class="text-center stat">
                  <div class="num"><?= $events ?></div>
                  <div class="lbl">Event Diikuti</div>
                </div>
                <?php if ($role === 'presenter'): ?>
                  <div class="text-center stat">
                    <div class="num"><?= $loaCnt ?></div>
                    <div class="lbl">LOA Didapat</div>
                  </div>
                <?php else: ?>
                  <div class="text-center stat">
                    <div class="num"><?= $certCnt ?></div>
                    <div class="lbl">Sertifikat Didapat</div>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

        <!-- ===== TABS ===== -->
        <ul class="nav nav-tabs mt-4 mb-3" role="tablist">
          <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-berkas" type="button"><i class="bi bi-folder2-open me-1"></i>Berkas</button></li>
          <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-settings" type="button"><i class="bi bi-gear me-1"></i>Pengaturan</button></li>
          <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-password" type="button"><i class="bi bi-shield-lock me-1"></i>Kata Sandi</button></li>
        </ul>

        <!-- ===== Flash ===== -->
        <?php if (session('success')): ?>
          <div class="alert alert-success shadow-sm"><?= esc(session('success')) ?></div>
        <?php endif; ?>
        <?php if (session('error')): ?>
          <div class="alert alert-danger shadow-sm"><?= esc(session('error')) ?></div>
        <?php endif; ?>

        <!-- ===== TAB PANES ===== -->
        <div class="tab-content">
          <!-- BERKAS -->
          <div class="tab-pane fade show active" id="tab-berkas">
            <div class="row g-3">
              <?php if ($role === 'presenter'): ?>
                <div class="col-lg-6">
                  <div class="card card-soft">
                    <div class="card-header"><strong><i class="bi bi-award me-2"></i>LOA</strong></div>
                    <div class="card-body">
                      <?php if (!empty($docs['loa'])): foreach ($docs['loa'] as $f): ?>
                        <div class="file-row">
                          <div class="file-name">
                            <i class="bi bi-file-earmark-text"></i>
                            <span><?= esc($f['name']) ?></span>
                            <?php if(!empty($f['created_at'])): ?>
                              <span class="file-meta ms-2"><?= date('d M Y', strtotime($f['created_at'])) ?></span>
                            <?php endif; ?>
                          </div>
                          <div class="ms-3">
                            <a class="btn btn-sm btn-outline-primary" href="<?= site_url($f['path']) ?>" target="_blank" rel="noopener">
                              <i class="bi bi-download"></i>
                            </a>
                          </div>
                        </div>
                      <?php endforeach; else: ?>
                        <div class="text-muted">Belum ada LOA.</div>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
              <?php endif; ?>

              <div class="col-lg-6">
                <div class="card card-soft">
                  <div class="card-header"><strong><i class="bi bi-patch-check me-2"></i>Sertifikat</strong></div>
                  <div class="card-body">
                    <?php if (!empty($docs['sertifikat'])): foreach ($docs['sertifikat'] as $f): ?>
                      <div class="file-row">
                        <div class="file-name">
                          <i class="bi bi-file-earmark-text"></i>
                          <span><?= esc($f['name']) ?></span>
                          <?php if(!empty($f['created_at'])): ?>
                            <span class="file-meta ms-2"><?= date('d M Y', strtotime($f['created_at'])) ?></span>
                          <?php endif; ?>
                        </div>
                        <div class="ms-3">
                          <a class="btn btn-sm btn-outline-primary" href="<?= site_url($f['path']) ?>" target="_blank" rel="noopener">
                            <i class="bi bi-download"></i>
                          </a>
                        </div>
                      </div>
                    <?php endforeach; else: ?>
                      <div class="text-muted">Belum ada sertifikat.</div>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- PENGATURAN -->
          <div class="tab-pane fade" id="tab-settings">
            <div class="card card-soft">
              <div class="card-header"><strong>Pengaturan Profil</strong></div>
              <div class="card-body">
                <form action="<?= site_url('profile/update') ?>" method="post" enctype="multipart/form-data" class="row g-3">
                  <?= csrf_field() ?>
                  <div class="col-md-6">
                    <label class="form-label">Nama Lengkap</label>
                    <input type="text" name="nama_lengkap" class="form-control" value="<?= esc($user['nama_lengkap'] ?? '') ?>" required>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Email (tidak dapat diubah)</label>
                    <input type="email" class="form-control" value="<?= esc($user['email'] ?? '') ?>" disabled>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">NIM</label>
                    <input type="text" name="nim" class="form-control" value="<?= esc($user['nim'] ?? '') ?>">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Institusi</label>
                    <input type="text" name="institusi" class="form-control" value="<?= esc($user['institusi'] ?? '') ?>">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">No. HP</label>
                    <input type="text" name="no_hp" class="form-control" value="<?= esc($user['no_hp'] ?? '') ?>">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Foto (opsional)</label>
                    <input type="file" name="foto" accept=".jpg,.jpeg,.png,.webp" class="form-control">
                    <small class="text-muted">Maks 2MB</small>
                  </div>
                  <div class="col-12">
                    <button class="btn btn-primary">
                      <i class="bi bi-save me-1"></i> Simpan Perubahan
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>

          <!-- PASSWORD -->
          <div class="tab-pane fade" id="tab-password">
            <div class="card card-soft">
              <div class="card-header"><strong>Ubah Kata Sandi</strong></div>
              <div class="card-body">
                <form id="passwordForm" action="<?= site_url('profile/change-password') ?>" method="post" class="row g-3">
                  <?= csrf_field() ?>
                  <div class="col-md-4">
                    <label class="form-label">Password Lama</label>
                    <div class="input-group">
                      <input type="password" name="old_password" id="old_password" class="form-control" required>
                      <button class="btn btn-outline-secondary toggle-pass" type="button" data-target="old_password" aria-label="Tampilkan/Sembunyikan">
                        <i class="bi bi-eye"></i>
                      </button>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Password Baru</label>
                    <div class="input-group">
                      <input type="password" name="new_password" id="new_password" class="form-control" minlength="6" required>
                      <button class="btn btn-outline-secondary toggle-pass" type="button" data-target="new_password" aria-label="Tampilkan/Sembunyikan">
                        <i class="bi bi-eye"></i>
                      </button>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Konfirmasi Password</label>
                    <div class="input-group">
                      <input type="password" name="confirm_password" id="confirm_password" class="form-control" minlength="6" required>
                      <button class="btn btn-outline-secondary toggle-pass" type="button" data-target="confirm_password" aria-label="Tampilkan/Sembunyikan">
                        <i class="bi bi-eye"></i>
                      </button>
                    </div>
                  </div>
                  <div class="col-12">
                    <div class="text-danger small d-none" id="pwdError"></div>
                    <button class="btn btn-warning">
                      <i class="bi bi-key me-1"></i> Ubah Password
                    </button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div><!-- /tab-content -->

        <footer class="text-center text-muted small mt-4">&copy; <?= date('Y') ?> — SNIA</footer>
      </div>
    </main>
  </div>

  <script>
    // Back → fallback ke dashboard sesuai role
    document.getElementById('btnBack')?.addEventListener('click', function () {
      if (history.length > 1) {
        history.back();
      } else {
        const role = "<?= esc($role) ?>";
        let fallback = "<?= site_url('dashboard') ?>";
        if (role === 'presenter')  fallback = "<?= site_url('presenter/dashboard') ?>";
        if (role === 'audience')   fallback = "<?= site_url('audience/dashboard') ?>";
        if (role === 'admin')      fallback = "<?= site_url('admin/dashboard') ?>";
        location.href = fallback;
      }
    });

    // Toggle show/hide password
    document.querySelectorAll('.toggle-pass').forEach(btn=>{
      btn.addEventListener('click', ()=>{
        const id = btn.dataset.target;
        const el = document.getElementById(id);
        if(!el) return;
        el.type = (el.type === 'password') ? 'text' : 'password';
        const icon = btn.querySelector('i');
        icon.classList.toggle('bi-eye');
        icon.classList.toggle('bi-eye-slash');
      });
    });

    // Validasi ringan password
    document.getElementById('passwordForm')?.addEventListener('submit', (e)=>{
      const oldP = document.getElementById('old_password').value.trim();
      const newP = document.getElementById('new_password').value.trim();
      const cfmP = document.getElementById('confirm_password').value.trim();
      const err  = document.getElementById('pwdError');

      if (!oldP || !newP || !cfmP) {
        e.preventDefault(); err.textContent = 'Semua field password wajib diisi.'; err.classList.remove('d-none'); return;
      }
      if (newP.length < 6) {
        e.preventDefault(); err.textContent = 'Panjang password minimal 6 karakter.'; err.classList.remove('d-none'); return;
      }
      if (newP !== cfmP) {
        e.preventDefault(); err.textContent = 'Password baru dan konfirmasi harus sama.'; err.classList.remove('d-none'); return;
      }
      err.classList.add('d-none');
    });

    // Konfirmasi Logout (POST + CSRF) dengan SweetAlert (fallback auto-load)
    document.addEventListener('click', function(e){
      const btn = e.target.closest('.js-logout');
      if (!btn) return;

      e.preventDefault();
      const submitLogout = () => document.getElementById('logoutForm')?.submit();

      const showConfirm = () => Swal.fire({
        icon: 'question',
        title: 'Keluar dari akun?',
        text: 'Anda yakin ingin logout sekarang?',
        showCancelButton: true,
        confirmButtonText: 'Ya, logout',
        cancelButtonText: 'Batal',
        reverseButtons: true,
        focusCancel: true
      }).then(res => { if (res.isConfirmed) submitLogout(); });

      if (window.Swal) {
        showConfirm();
      } else {
        const s = document.createElement('script');
        s.src = 'https://cdn.jsdelivr.net/npm/sweetalert2@11';
        s.onload = showConfirm;
        document.head.appendChild(s);
      }
    });
  </script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>