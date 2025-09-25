<?php helper('filesystem'); ?>
<?php
  $title   = $title ?? 'Profil Saya';
  $user    = $user  ?? [];
  $role    = $role  ?? 'audience';
  $avatar  = $avatar ?? '';
  $events  = (int)($events_count ?? 0);
  $loaCnt  = (int)($loa_count ?? 0);
  $certCnt = (int)($cert_count ?? 0);
  $docs    = $docs ?? ['loa'=>[], 'sertifikat'=>[]];

  // data navbar
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
    :root{
      --topbar-h:80px; --ring:#eef0f4; --primary:#4f46e5; --soft:#f7f8fb;
      --card-shadow:0 8px 24px rgba(0,0,0,.06); --radius:16px;
    }
    html,body{ background:var(--soft); margin:0; }

    /* Navbar */
    .topbar{
      background:#fff;border-bottom:1px solid var(--ring);
      position:fixed;inset:0 0 auto 0;height:var(--topbar-h);z-index:1000;
      display:flex;align-items:center;justify-content:space-between;padding:0 1.25rem;
    }
    .btn-ghost{ background:transparent;border:0;box-shadow:none; }
    .avatar{ width:42px;height:42px;border-radius:50%;object-fit:cover; }
    .dropdown-menu-prof{ min-width:260px; }
    #content{ padding-top:var(--topbar-h); }

    /* Page UI */
    .cover{
      height:220px;background:radial-gradient(1200px 300px at 10% 0,#dbeafe 0,#ede9fe 40%,#eef2ff 100%);
      border-radius:var(--radius);
    }
    .hero-card{ margin-top:-56px; }
    .avatar-lg{
      width:110px;height:110px;border-radius:50%;object-fit:cover;border:5px solid #fff;
      box-shadow:var(--card-shadow);
    }
    .stat .num{ font-weight:800;font-size:1.35rem; }
    .card-soft{ border-radius:14px; box-shadow:var(--card-shadow); border:1px solid #f0f2f7; }
    .file-row{
      border:1px solid #eef2f7;border-radius:12px;padding:12px 14px;background:#fff;
      display:flex;align-items:center;justify-content:space-between;
    }
    .file-row + .file-row{ margin-top:10px; }
    .nav-tabs .nav-link{ border:0;border-bottom:3px solid transparent; }
    .nav-tabs .nav-link.active{ color:var(--primary);border-color:var(--primary);background:transparent; }

    /* Mobile tweaks */
    @media (max-width:576px){
      :root{ --topbar-h:70px }
      .dropdown-menu-prof{min-width:200px;font-size:.95rem}
      .nameblock{display:none}
      .avatar{width:38px;height:38px}
      .stat .num{ font-size:1.1rem; }
      .avatar-lg{ width:90px;height:90px; }
    }
  </style>
</head>
<body>
  <!-- NAVBAR -->
<header class="topbar">
  <div class="d-flex align-items-center gap-2">
    <!-- Back only (burger dihapus) -->
    <button class="btn btn-ghost" type="button" id="btnBack" aria-label="Kembali">
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
        </div>
        <img src="<?= esc($avatarUrlNav) ?>" class="avatar" alt="Avatar"
             onerror="this.outerHTML='<span class=&quot;avatar bg-primary text-white d-inline-flex justify-content-center align-items-center&quot; style=&quot;width:42px;height:42px;border-radius:50%;&quot;><i class=&quot;bi bi-person&quot;></i></span>';">
      </button>
      <ul class="dropdown-menu dropdown-menu-end dropdown-menu-prof">
        <li class="dropdown-header px-3">
          <div class="fw-semibold"><?= esc($nama) ?></div>
          <?php if($email): ?><small class="text-muted"><?= esc($email) ?></small><?php endif; ?>
        </li>
        <li><hr class="dropdown-divider my-1"></li>
        <li><a class="dropdown-item" href="<?= site_url('profile') ?>"><i class="bi bi-person-circle me-2"></i>Profil</a></li>
        <li><a class="dropdown-item" href="<?= site_url('auth/logout') ?>"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
      </ul>
    </div>
  </div>
</header>

  <div id="content">
    <main class="py-4">
      <div class="container">

        <div class="cover"></div>

        <!-- HERO -->
        <div class="card card-soft hero-card p-3">
          <div class="row g-3 align-items-center">
            <div class="col-auto text-center">
              <img src="<?= esc($avatar ?: $avatarUrlNav) ?>" class="avatar-lg" alt="avatar"
                   onerror="this.src='<?= base_url('uploads/profile/default.png') ?>'">
            </div>
            <div class="col-md">
              <h4 class="mb-0"><?= esc($user['nama_lengkap'] ?? '-') ?></h4>
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
                  <div class="text-muted small">Event Diikuti</div>
                </div>

                <?php if ($role === 'presenter'): ?>
                  <div class="text-center stat">
                    <div class="num"><?= $loaCnt ?></div>
                    <div class="text-muted small">LOA Didapat</div>
                  </div>
                <?php else: ?>
                  <div class="text-center stat">
                    <div class="num"><?= $certCnt ?></div>
                    <div class="text-muted small">Sertifikat Didapat</div>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

        <!-- Tabs -->
        <ul class="nav nav-tabs mt-4 mb-3" role="tablist">
          <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-berkas" type="button"><i class="bi bi-folder2-open me-1"></i>Berkas</button></li>
          <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-settings" type="button"><i class="bi bi-gear me-1"></i>Pengaturan</button></li>
          <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-password" type="button"><i class="bi bi-shield-lock me-1"></i>Kata Sandi</button></li>
        </ul>

        <!-- Flash -->
        <?php if (session('success')): ?>
          <div class="alert alert-success shadow-sm"><?= esc(session('success')) ?></div>
        <?php endif; ?>
        <?php if (session('error')): ?>
          <div class="alert alert-danger shadow-sm"><?= esc(session('error')) ?></div>
        <?php endif; ?>

        <div class="tab-content">
          <!-- BERKAS -->
          <div class="tab-pane fade show active" id="tab-berkas">
            <div class="row g-3">
              <?php if ($role === 'presenter'): ?>
                <div class="col-lg-6">
                  <div class="card card-soft">
                    <div class="card-header bg-light"><strong><i class="bi bi-award me-2"></i>LOA</strong></div>
                    <div class="card-body">
                      <?php if (!empty($docs['loa'])): foreach ($docs['loa'] as $f): ?>
                        <div class="file-row">
                          <div class="me-3">
                            <i class="bi bi-file-earmark-text me-2"></i><?= esc($f['name']) ?>
                            <?php if(!empty($f['created_at'])): ?><small class="text-muted ms-2"><?= date('d M Y', strtotime($f['created_at'])) ?></small><?php endif; ?>
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
                  <div class="card-header bg-light"><strong><i class="bi bi-patch-check me-2"></i>Sertifikat</strong></div>
                  <div class="card-body">
                    <?php if (!empty($docs['sertifikat'])): foreach ($docs['sertifikat'] as $f): ?>
                      <div class="file-row">
                        <div class="me-3">
                          <i class="bi bi-file-earmark-text me-2"></i><?= esc($f['name']) ?>
                          <?php if(!empty($f['created_at'])): ?><small class="text-muted ms-2"><?= date('d M Y', strtotime($f['created_at'])) ?></small><?php endif; ?>
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
              <div class="card-header bg-light"><strong>Pengaturan Profil</strong></div>
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
              <div class="card-header bg-light"><strong>Ubah Kata Sandi</strong></div>
              <div class="card-body">
                <form id="passwordForm" action="<?= site_url('profile/change-password') ?>" method="post" class="row g-3">
                  <?= csrf_field() ?>
                  <div class="col-md-4">
                    <label class="form-label">Password Lama</label>
                    <div class="input-group">
                      <input type="password" name="old_password" id="old_password" class="form-control" required>
                      <button class="btn btn-outline-secondary toggle-pass" type="button" data-target="old_password">
                        <i class="bi bi-eye"></i>
                      </button>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Password Baru</label>
                    <div class="input-group">
                      <input type="password" name="new_password" id="new_password" class="form-control" minlength="6" required>
                      <button class="btn btn-outline-secondary toggle-pass" type="button" data-target="new_password">
                        <i class="bi bi-eye"></i>
                      </button>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Konfirmasi Password</label>
                    <div class="input-group">
                      <input type="password" name="confirm_password" id="confirm_password" class="form-control" minlength="6" required>
                      <button class="btn btn-outline-secondary toggle-pass" type="button" data-target="confirm_password">
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
        </div>

        <footer class="text-center text-muted small mt-4">&copy; <?= date('Y') ?> — SNIA</footer>
      </div>
    </main>
  </div>

  <script>
    // Back button
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

    // Client-side validation: wajib isi semua & harus sama
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
  </script>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>