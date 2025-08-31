<?php
  $title = 'Daftar Abstrak';
  $breadcrumb = 'Abstrak';
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_reviewer') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-4">
      <h3 class="mb-3">Daftar Abstrak</h3>

      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>Judul</th>
              <th>Penulis</th>
              <th>Kategori</th>
              <th>Status</th>
              <th>Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php if(!empty($abstrak)): ?>
              <?php foreach($abstrak as $a): ?>
                <tr>
                  <td><?= esc($a['judul']) ?></td>
                  <td><?= esc($a['nama_lengkap']) ?></td>
                  <td><?= esc($a['nama_kategori']) ?></td>
                  <td><span class="badge bg-secondary"><?= esc($a['status']) ?></span></td>
                  <td>
                    <a href="<?= site_url('reviewer/abstrak/'.$a['id_abstrak']) ?>" class="btn btn-sm btn-primary">Detail</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="5" class="text-center text-muted">Belum ada abstrak yang ditugaskan.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>
