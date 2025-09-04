<?= $this->include('partials/header') ?>

<div class="container" style="padding-top:80px;">
    <div class="d-flex justify-content-between align-items-center mb-3">
        <h3><?= esc($title) ?></h3>
        <a href="<?= base_url('dashboard') ?>" class="btn btn-secondary">
            ← Kembali
        </a>
    </div>

    <?php if(session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= session()->getFlashdata('success') ?></div>
    <?php endif; ?>

    <?php if(session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= session()->getFlashdata('error') ?></div>
    <?php endif; ?>

    <div class="row">
        <!-- Profil Info -->
        <div class="col-md-4 text-center mb-3">
            <img src="<?= base_url('uploads/profile/' . ($user['foto'] ?? 'default.png')) ?>"
                 class="rounded-circle mb-3 shadow" width="150" height="150">
            <h5><?= esc($user['nama_lengkap']) ?></h5>
            <p class="text-muted"><?= esc($user['role']) ?></p>
        </div>

        <!-- Form Edit Profil -->
        <div class="col-md-8">
            <ul class="nav nav-tabs" id="profileTab">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#edit">Edit Profil</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#password">Ganti Password</a>
                </li>
            </ul>

            <div class="tab-content p-3 border bg-light rounded-bottom shadow-sm">
                <!-- Edit Profil -->
                <div class="tab-pane fade show active" id="edit">
                    <form action="<?= base_url('profile/update') ?>" method="post" enctype="multipart/form-data">
                        <div class="mb-3">
                            <label>Nama Lengkap</label>
                            <input type="text" name="nama_lengkap" class="form-control"
                                   value="<?= esc($user['nama_lengkap']) ?>" required>
                        </div>
                        <div class="mb-3">
                            <label>No HP</label>
                            <input type="text" name="no_hp" class="form-control"
                                   value="<?= esc($user['no_hp']) ?>">
                        </div>
                        <div class="mb-3">
                            <label>Instansi</label>
                            <input type="text" name="institusi" class="form-control"
                                   value="<?= esc($user['institusi']) ?>">
                        </div>
                        <div class="mb-3">
                            <label>Foto Profil</label>
                            <input type="file" name="foto" class="form-control">
                        </div>
                        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                    </form>
                </div>

                <!-- Ganti Password -->
                <div class="tab-pane fade" id="password">
                    <form action="<?= base_url('profile/changePassword') ?>" method="post">
                        <div class="mb-3">
                            <label>Password Lama</label>
                            <input type="password" name="old_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Password Baru</label>
                            <input type="password" name="new_password" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label>Konfirmasi Password Baru</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-warning">Ganti Password</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?= $this->include('partials/footer') ?>
