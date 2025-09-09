<?php
  $title = 'Reviewer Dashboard';
  /** expects: $stat = ['assigned'=>..,'pending'=>..,'reviewed'=>..,'due_today'=>..]
   *            $recent (list tugas terbaru), $notifs (opsional)
   */
  $s = $stat ?? ['assigned'=>0,'pending'=>0,'reviewed'=>0,'due_today'=>0];

  helper('number');
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_reviewer') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:var(--topbar-h);">
    <div class="container-fluid p-3 p-md-4">

      <div class="d-flex justify-content-between align-items-center mb-3">
        <h3 class="mb-0">Hai, <?= esc(session('nama_lengkap') ?? session('nama') ?? 'Reviewer') ?></h3>
        <div class="d-none d-md-flex gap-2">
          <a href="<?= site_url('reviewer/abstrak') ?>" class="btn btn-primary">
            <i class="bi bi-journal-text me-1"></i> Abstrak
          </a>
          <a href="<?= site_url('reviewer/riwayat') ?>" class="btn btn-outline-primary">
            <i class="bi bi-clock-history me-1"></i> Riwayat
          </a>
        </div>
      </div>

      <!-- KPI cards -->
      <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
          <div class="card kpi-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
              <div class="kpi-ico bg-primary-subtle text-primary"><i class="bi bi-clipboard-check"></i></div>
              <div>
                <div class="text-muted small">Tugas Dialokasikan</div>
                <div class="fs-4 fw-semibold"><?= number_format((int)$s['assigned']) ?></div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-6 col-xl-3">
          <div class="card kpi-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
              <div class="kpi-ico bg-warning-subtle text-warning"><i class="bi bi-hourglass-split"></i></div>
              <div>
                <div class="text-muted small">Menunggu Keputusan</div>
                <div class="fs-4 fw-semibold"><?= number_format((int)$s['pending']) ?></div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-6 col-xl-3">
          <div class="card kpi-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
              <div class="kpi-ico bg-success-subtle text-success"><i class="bi bi-check2-circle"></i></div>
              <div>
                <div class="text-muted small">Sudah Direview</div>
                <div class="fs-4 fw-semibold"><?= number_format((int)$s['reviewed']) ?></div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-6 col-xl-3">
          <div class="card kpi-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
              <div class="kpi-ico bg-info-subtle text-info"><i class="bi bi-calendar-event"></i></div>
              <div>
                <div class="text-muted small">Jatuh Tempo Hari Ini</div>
                <div class="fs-4 fw-semibold"><?= number_format((int)$s['due_today']) ?></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- 2-column: kiri = Tugas Terbaru, kanan = Aktivitas -->
      <div class="row g-3">
        <!-- Tugas Terbaru -->
        <div class="col-12 col-lg-8">
          <div class="card shadow-sm h-100">
            <div class="card-body">
              <h5 class="card-title mb-3 d-flex align-items-center gap-2">
                <i class="bi bi-list-task"></i> Tugas Terbaru
              </h5>

              <?php if (!empty($recent)): ?>
                <div class="vstack gap-2">
                  <?php foreach ($recent as $r): ?>
                    <?php
                      $status = strtolower($r['status'] ?? '');
                      $badge  = 'bg-secondary';
                      if (in_array($status, ['diterima','accepted'])) $badge = 'bg-success';
                      elseif (in_array($status, ['ditolak','rejected'])) $badge = 'bg-danger';
                      elseif (in_array($status, ['revisi','revision'])) $badge = 'bg-warning text-dark';
                      elseif ($status==='' || $status==='menunggu') $badge = 'bg-secondary';
                    ?>
                    <div class="p-3 border rounded-3 bg-white d-flex justify-content-between align-items-start">
                      <div class="me-2">
                        <div class="fw-semibold"><?= esc($r['judul'] ?? 'Tanpa judul') ?></div>
                        <div class="small text-muted">
                          Penulis: <?= esc($r['nama_lengkap'] ?? '-') ?> ·
                          Kategori: <?= esc($r['nama_kategori'] ?? '-') ?>
                        </div>
                        <span class="badge <?= $badge ?> mt-1"><?= esc(ucfirst($r['status'] ?? 'menunggu')) ?></span>
                      </div>
                      <div class="text-nowrap">
                        <a href="<?= site_url('reviewer/abstrak/'.(int)($r['id_abstrak'] ?? 0)) ?>"
                           class="btn btn-sm btn-primary">
                          <i class="bi bi-search"></i> Review
                        </a>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php else: ?>
                <div class="p-4 text-center border rounded-3 bg-light-subtle">
                  <div class="mb-2"><i class="bi bi-inbox fs-3 text-secondary"></i></div>
                  <div class="fw-semibold">Belum ada tugas terbaru</div>
                  <div class="text-muted small">Tugas baru akan tampil di sini.</div>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Aktivitas Terbaru (opsional) -->
        <div class="col-12 col-lg-4">
          <div class="card shadow-sm h-100">
            <div class="card-body">
              <h5 class="card-title mb-3 d-flex align-items-center gap-2">
                <i class="bi bi-bell"></i> Aktivitas Terbaru
              </h5>

              <?php if (!empty($notifs)): ?>
                <div class="list-group list-group-flush">
                  <?php foreach ($notifs as $n): ?>
                    <div class="list-group-item px-0">
                      <div class="d-flex align-items-start gap-2">
                        <i class="bi bi-info-circle text-primary mt-1"></i>
                        <div class="flex-fill">
                          <div class="fw-semibold"><?= esc($n['title'] ?? '-') ?></div>
                          <?php if (!empty($n['time'])): ?>
                            <div class="small text-muted"><?= esc($n['time']) ?></div>
                          <?php endif; ?>
                        </div>
                        <?php if (!empty($n['link'])): ?>
                          <a class="btn btn-sm btn-outline-primary" href="<?= esc($n['link']) ?>">Buka</a>
                        <?php endif; ?>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php else: ?>
                <div class="p-4 text-center border rounded-3 bg-light-subtle">
                  <div class="mb-2"><i class="bi bi-bell-slash fs-3 text-secondary"></i></div>
                  <div class="fw-semibold">Tidak ada aktivitas</div>
                  <div class="text-muted small">Update akan muncul di sini.</div>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<style>
  /* KPI look & feel—selaras audience */
  .kpi-card .kpi-ico{
    width:44px; height:44px; border-radius:12px;
    display:inline-flex; align-items:center; justify-content:center;
    font-size:1.25rem;
  }
</style>
