<?php
  $title = 'Detail Abstrak';
  $breadcrumb = 'Detail Abstrak';
?>
<?= $this->include('partials/header') ?>

<div>
  <?= $this->include('partials/sidebar_reviewer') ?>

  <main class="flex-fill" style="margin-left:220px; padding-top:70px;">
    <div class="container-fluid p-4">
      <h3 class="mb-3">Detail Abstrak</h3>

      <div class="card shadow-sm mb-4">
        <div class="card-body">
          <h5><?= esc($abstrak['judul']) ?></h5>
          <p><strong>Penulis:</strong> <?= esc($abstrak['nama_lengkap']) ?></p>
          <p><strong>Kategori:</strong> <?= esc($abstrak['nama_kategori']) ?></p>
          <p><strong>Status:</strong> <span class="badge bg-secondary"><?= esc($abstrak['status']) ?></span></p>
          <a href="<?= base_url('uploads/abstrak/'.$abstrak['file_abstrak']) ?>" target="_blank" class="btn btn-outline-primary btn-sm">
            <i class="bi bi-file-earmark-text"></i> Lihat Abstrak
          </a>
        </div>
      </div>

      <!-- Form Review -->
      <div class="card shadow-sm">
        <div class="card-header">Form Review</div>
        <div class="card-body">
          <form action="<?= site_url('reviewer/abstrak/review/save') ?>" method="post">
            <input type="hidden" name="id_abstrak" value="<?= $abstrak['id_abstrak'] ?>">

            <div class="mb-3">
              <label class="form-label">Keputusan</label>
              <select name="keputusan" class="form-select" required>
                <option value="">-- Pilih --</option>
                <option value="Accepted">Accepted</option>
                <option value="Revisi">Revisi</option>
                <option value="Rejected">Rejected</option>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">Komentar</label>
              <textarea name="komentar" rows="4" class="form-control" required></textarea>
            </div>

            <button type="submit" class="btn btn-success">Simpan Review</button>
            <a href="<?= site_url('reviewer/abstrak') ?>" class="btn btn-secondary">Kembali</a>
          </form>
        </div>
      </div>
    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>
