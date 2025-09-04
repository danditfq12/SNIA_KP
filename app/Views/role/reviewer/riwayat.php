<?php
  $title = 'Riwayat Review';
  $breadcrumb = 'Riwayat';
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_reviewer') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-4">
      <h3 class="mb-3">Riwayat Review</h3>

      <div class="table-responsive">
        <table class="table table-hover align-middle">
          <thead class="table-light">
            <tr>
              <th>Judul</th>
              <th>Penulis</th>
              <th>Kategori</th>
              <th>Keputusan</th>
              <th>Tanggal Review</th>
            </tr>
          </thead>
          <tbody>
            <?php if(!empty($riwayat)): ?>
              <?php foreach($riwayat as $r): ?>
                <tr>
                  <td><?= esc($r['judul']) ?></td>
                  <td><?= esc($r['nama_lengkap']) ?></td>
                  <td><?= esc($r['nama_kategori']) ?></td>
                  <td><span class="badge bg-success"><?= esc($r['keputusan']) ?></span></td>
                  <td><?= esc($r['tanggal_review']) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php else: ?>
              <tr><td colspan="5" class="text-center text-muted">Belum ada riwayat review.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>
