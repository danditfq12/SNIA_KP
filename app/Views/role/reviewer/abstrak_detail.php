<?php
  $title = 'Detail Abstrak';
  $breadcrumb = 'Detail Abstrak';
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_reviewer') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-4">
      <h3 class="mb-3"><?= esc($abstrak['judul']) ?></h3>
      <p><strong>Penulis:</strong> <?= esc($abstrak['nama_lengkap']) ?> (<?= esc($abstrak['email']) ?>)</p>
      <p><strong>Kategori:</strong> <?= esc($abstrak['nama_kategori']) ?></p>
      <p><strong>Status:</strong> <span class="badge bg-secondary"><?= esc($abstrak['status']) ?></span></p>
      <p><a href="<?= base_url('uploads/abstrak/'.$abstrak['file_abstrak']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">Download Abstrak</a></p>

      <hr>
      <h5>Form Review</h5>
      <form method="post" action="<?= site_url('reviewer/review/'.$abstrak['id_abstrak']) ?>">
        <div class="mb-3">
          <label class="form-label">Keputusan</label>
          <select name="keputusan" class="form-select" required>
            <option value="">-- Pilih --</option>
            <option value="Accepted">Accepted</option>
            <option value="Rejected">Rejected</option>
            <option value="Revisi">Revisi</option>
          </select>
        </div>
        <div class="mb-3">
          <label class="form-label">Komentar</label>
          <textarea name="komentar" class="form-control" rows="4"></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Simpan Review</button>
      </form>
    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>
