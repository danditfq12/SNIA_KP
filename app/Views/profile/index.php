<?php
  $title = $title ?? 'Profil Saya';
  $user  = $user  ?? [];
?>

<?= $this->include('partials/header') ?>

<div class="container" style="padding-top:80px;">
  <div class="d-flex justify-content-between align-items-center mb-3">
    <h3 class="mb-0"><?= esc($title) ?></h3>
    <a href="<?= base_url('dashboard') ?>" class="btn btn-secondary">← Kembali</a>
  </div>

  <?php if(session()->getFlashdata('success')): ?>
    <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
  <?php endif; ?>
  <?php if(session()->getFlashdata('error')): ?>
    <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
  <?php endif; ?>
  <?php if(session()->getFlashdata('warning')): ?>
    <div class="alert alert-warning"><?= esc(session()->getFlashdata('warning')) ?></div>
  <?php endif; ?>
  <?php if($errors = session()->getFlashdata('errors')): ?>
    <div class="alert alert-danger mb-3">
      <ul class="mb-0">
        <?php foreach ((array)$errors as $e): ?>
          <li><?= esc($e) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <div class="row">
    <!-- Foto & ringkas profil -->
    <div class="col-md-4 text-center mb-3">
      <div class="d-inline-block rounded-circle shadow"
           style="width:150px;height:150px;overflow:hidden;">
        <img
          src="<?= base_url('uploads/profile/' . ($user['foto'] ?? 'default.png')) ?>"
          alt="Foto Profil"
          style="width:100%;height:100%;object-fit:cover;"
          onerror="this.src='<?= base_url('uploads/profile/default.png') ?>';"
        >
      </div>
      <h5 class="mt-3 mb-1"><?= esc($user['nama_lengkap'] ?? '-') ?></h5>
      <div class="text-muted small"><?= esc(ucfirst($user['role'] ?? '-')) ?></div>
      <div class="text-muted small"><?= esc($user['email'] ?? '-') ?></div>
    </div>

    <!-- Tab content -->
    <div class="col-md-8">
      <ul class="nav nav-tabs" id="profileTab" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-edit" type="button">Edit Profil</button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-password" type="button">Ganti Password</button>
        </li>
      </ul>

      <div class="tab-content p-3 border bg-light rounded-bottom shadow-sm">
        <!-- Edit Profil -->
        <div class="tab-pane fade show active" id="tab-edit">
          <form action="<?= base_url('profile/update') ?>" method="post" enctype="multipart/form-data" novalidate>
            <?= csrf_field() ?>

            <div class="row g-3">
              <div class="col-md-6">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" name="nama_lengkap" class="form-control"
                       value="<?= esc($user['nama_lengkap'] ?? '') ?>" required>
              </div>

              <div class="col-md-6">
                <label class="form-label">NIM (opsional)</label>
                <input type="text" name="nim" class="form-control"
                       value="<?= esc($user['nim'] ?? '') ?>">
              </div>

              <div class="col-md-6">
                <label class="form-label">No. HP</label>
                <input type="text" name="no_hp" class="form-control"
                       value="<?= esc($user['no_hp'] ?? '') ?>">
              </div>

              <div class="col-md-6">
                <label class="form-label">Institusi</label>
                <input type="text" name="institusi" class="form-control"
                       value="<?= esc($user['institusi'] ?? '') ?>">
              </div>

              <div class="col-12">
                <label class="form-label">Alamat</label>
                <textarea name="alamat" class="form-control" rows="3"><?= esc($user['alamat'] ?? '') ?></textarea>
              </div>

              <div class="col-md-8">
                <label class="form-label">Foto Profil (JPG/PNG/WebP, maks 2MB)</label>
                <input type="file" name="foto" class="form-control" accept=".jpg,.jpeg,.png,.webp" id="fotoInput">
                <div class="form-text">Foto akan dipotong otomatis menjadi kotak 400×400.</div>
              </div>

              <div class="col-md-4 text-center">
                <div class="border rounded p-2 bg-white">
                  <div class="small text-muted mb-1">Pratinjau</div>
                  <div class="mx-auto rounded"
                       style="width:100px;height:100px;overflow:hidden;">
                    <img id="previewFoto"
                         src="<?= base_url('uploads/profile/' . ($user['foto'] ?? 'default.png')) ?>"
                         alt="Preview"
                         style="width:100%;height:100%;object-fit:cover;"
                         onerror="this.src='<?= base_url('uploads/profile/default.png') ?>';">
                  </div>
                </div>
              </div>
            </div>

            <div class="mt-3">
              <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
            </div>
          </form>
        </div>

        <!-- Ganti Password -->
        <div class="tab-pane fade" id="tab-password">
          <form action="<?= base_url('profile/change-password') ?>" method="post" novalidate>
            <?= csrf_field() ?>

            <div class="mb-3">
              <label class="form-label">Password Lama</label>
              <input type="password" name="old_password" class="form-control" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Password Baru</label>
              <input type="password" name="new_password" class="form-control" minlength="6" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Konfirmasi Password Baru</label>
              <input type="password" name="confirm_password" class="form-control" minlength="6" required>
            </div>

            <button type="submit" class="btn btn-warning">Ganti Password</button>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
  // preview foto di sisi klien
  const input = document.getElementById('fotoInput');
  const prev  = document.getElementById('previewFoto');
  if (input) {
    input.addEventListener('change', (e) => {
      const f = e.target.files?.[0];
      if (!f) return;
      const reader = new FileReader();
      reader.onload = ev => prev.src = ev.target.result;
      reader.readAsDataURL(f);
    });
  }
</script>

<?= $this->include('partials/footer') ?>
