<!doctype html>
<html lang="id">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Daftar - SNIA</title>

  <link rel="stylesheet" href="<?= base_url('assets/css/auth.css') ?>">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<body>
  <div class="auth-wrap">
    <div class="auth-box">
      <div class="panel-brand" aria-hidden="true">
        <div class="brand-inner">
          <h1>Welcome SNIA 2025</h1>
          <p>Daftar sebagai presenter atau audience untuk mulai berpartisipasi.</p>
        </div>
      </div>

      <div class="form-panel">
        <h2>Daftar</h2>

        <?php if(session()->getFlashdata('error')): ?>
          <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
        <?php endif; ?>
        <?php if(session()->getFlashdata('success')): ?>
          <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
        <?php endif; ?>
        <?php if (isset($validation)): ?>
          <div class="alert alert-danger"><?= $validation->listErrors() ?></div>
        <?php endif; ?>

        <form action="<?= site_url('auth/register') ?>" method="post" novalidate class="auth-form" id="register-form" data-context="register">
          <?= csrf_field() ?>

          <div class="form-group">
            <label for="nama_lengkap">Nama Lengkap <span class="required">*</span></label>
            <input id="nama_lengkap" type="text" name="nama_lengkap" placeholder="Masukkan nama lengkap" value="<?= old('nama_lengkap') ?>" required>
          </div>

          <div class="form-group">
            <label for="email_reg">Email <span class="required">*</span></label>
            <input id="email_reg" type="email" name="email" placeholder="Masukkan email" value="<?= old('email') ?>" required>
          </div>

          <div class="form-group">
            <label for="no_hp">Nomor HP <span class="required">*</span></label>
            <input id="no_hp" type="tel" name="no_hp" placeholder="Contoh: 081234567890" value="<?= old('no_hp') ?>" required>
            <small class="form-hint">Minimal 10 digit, maksimal 15 digit</small>
          </div>

          <div class="form-group">
            <label for="institusi">Institusi <span class="required">*</span></label>
            <input id="institusi" type="text" name="institusi" placeholder="Nama universitas/instansi/perusahaan" value="<?= old('institusi') ?>" required>
          </div>

          <div class="form-group">
            <label for="jenis_peserta">Jenis Peserta <span class="required">*</span></label>
            <select id="jenis_peserta" name="jenis_peserta" required>
              <option value="">-- Pilih Jenis Peserta --</option>
              <option value="mahasiswa" <?= old('jenis_peserta')==='mahasiswa' ? 'selected' : '' ?>>Mahasiswa</option>
              <option value="dosen" <?= old('jenis_peserta')==='dosen' ? 'selected' : '' ?>>Dosen</option>
              <option value="peneliti" <?= old('jenis_peserta')==='peneliti' ? 'selected' : '' ?>>Peneliti</option>
              <option value="umum" <?= old('jenis_peserta')==='umum' ? 'selected' : '' ?>>Umum</option>
              <option value="lainnya" <?= old('jenis_peserta')==='lainnya' ? 'selected' : '' ?>>Lainnya</option>
            </select>
          </div>

          <div class="form-group">
            <label for="password">Kata Sandi <span class="required">*</span></label>
            <div class="input-group">
              <input id="password" type="password" name="password" placeholder="Minimal 6 karakter" required>
              <button type="button" class="toggle-password" aria-label="Tampilkan kata sandi"><i class="fa fa-eye"></i></button>
            </div>
          </div>

          <div class="form-group">
            <label for="password2">Konfirmasi Kata Sandi <span class="required">*</span></label>
            <div class="input-group">
              <input id="password2" type="password" name="password2" placeholder="Ulangi kata sandi" required>
              <button type="button" class="toggle-password" aria-label="Tampilkan kata sandi"><i class="fa fa-eye"></i></button>
            </div>
          </div>

          <div class="form-group">
            <label for="role">Daftar Sebagai <span class="required">*</span></label>
            <select id="role" name="role" required>
              <option value="">-- Pilih Peran Anda --</option>
              <option value="presenter" <?= old('role')==='presenter' ? 'selected' : '' ?>>Presenter</option>
              <option value="audience"  <?= old('role')==='audience'  ? 'selected' : '' ?>>Audience</option>
            </select>
          </div>

          <button type="submit" class="btn btn-primary">Daftar Sekarang</button>
        </form>

        <p class="redirect">Sudah punya akun? <a href="<?= site_url('auth/login') ?>">Masuk</a></p>
      </div>
    </div>
  </div>

  <script src="<?= base_url('assets/js/auth.js') ?>"></script>
</body>
</html>