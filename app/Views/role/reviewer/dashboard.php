<?php
  $title = 'Reviewer Dashboard';
  $breadcrumb = 'Dashboard';
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_reviewer') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-4">
      <h3 class="mb-3">Reviewer Dashboard</h3>

      <!-- Statistik -->
      <div class="row mb-4">
        <div class="col-sm-6 col-xl-3">
          <div class="card p-3 shadow-sm">
            <small class="text-muted">Assigned</small>
            <div class="h4"><?= (int)($stat['assigned'] ?? 0) ?></div>
          </div>
        </div>
        <div class="col-sm-6 col-xl-3">
          <div class="card p-3 shadow-sm">
            <small class="text-muted">Pending</small>
            <div class="h4"><?= (int)($stat['pending'] ?? 0) ?></div>
          </div>
        </div>
        <div class="col-sm-6 col-xl-3">
          <div class="card p-3 shadow-sm">
            <small class="text-muted">Reviewed</small>
            <div class="h4"><?= (int)($stat['reviewed'] ?? 0) ?></div>
          </div>
        </div>
        <div class="col-sm-6 col-xl-3">
          <div class="card p-3 shadow-sm">
            <small class="text-muted">Due Today</small>
            <div class="h4"><?= (int)($stat['due_today'] ?? 0) ?></div>
          </div>
        </div>
      </div>

      <!-- Tugas terbaru -->
      <h5 class="mt-3">Tugas Terbaru</h5>
      <?php if (!empty($recent)): ?>
        <div class="table-responsive">
          <table class="table table-hover align-middle">
            <thead class="table-light">
              <tr>
                <th>Judul</th>
                <th>Author</th>
                <th>Kategori</th>
                <th>Status</th>
                <th>Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php foreach($recent as $r): ?>
                <tr>
                  <td><?= esc($r['judul']) ?></td>
                  <td><?= esc($r['nama_lengkap']) ?></td>
                  <td><?= esc($r['nama_kategori']) ?></td>
                  <td><span class="badge bg-secondary"><?= esc($r['status']) ?></span></td>
                  <td>
                    <a href="<?= site_url('reviewer/abstrak/'.$r['id_abstrak']) ?>" class="btn btn-sm btn-primary">Detail</a>
                  </td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      <?php else: ?>
        <p class="text-muted">Belum ada tugas terbaru.</p>
      <?php endif; ?>
    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>
