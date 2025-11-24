<?php
// ====== DEFAULT VARS (hindari notice) ======
$title               = $title ?? 'Admin Dashboard';
$pembayaran_pending  = (int)($pembayaran_pending ?? 0); // KPI
$abstrak_masuk       = (int)($abstrak_masuk ?? 0);      // KPI
$abstrak_unassigned  = (int)($abstrak_unassigned ?? 0); // KPI
$total_event         = (int)($total_event ?? 0);        // KPI

// list ringkasan/terbaru (opsional)
$pendingPayments = $pendingPayments ?? []; // id_pembayaran,nama_lengkap,event_title,jumlah,tanggal_bayar
$recent_abstrak  = $recent_abstrak  ?? []; // judul,nama_lengkap,status,created_at
$unassigned_list = $unassigned_list ?? []; // judul,nama_lengkap,created_at
$recent_events   = $recent_events   ?? []; // title,event_date,event_time,format,is_active

helper(['number','form']);
$fmtDate = fn($s)=> $s ? date('d M Y', strtotime($s)) : '-';
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_admin') ?>
<?= $this->include('partials/alerts') ?>
<link rel="stylesheet" href="<?= base_url('assets/css/dashboard_admin.css'); ?>">

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <!-- HEADER (seragam: header-blue) -->
      <div class="header-section header-blue d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-4">
        <div class="mb-2 mb-md-0">
          <h3 class="welcome-text mb-1">
            <i class="bi bi-speedometer2 me-2"></i>Dashboard Admin
          </h3>
          <div class="text-white-50">Ringkasan status sistem SNIA</div>
        </div>
        <div class="text-start text-md-end">
          <small class="text-white-50 d-block">Terakhir login</small>
          <strong class="text-white"><?= date('d M Y, H:i') ?></strong>
        </div>
      </div>

      <!-- KPI 4 KOTAK - Responsive Grid -->
      <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
          <div class="stat-card stat-warning shadow-sm h-100">
            <div class="d-flex align-items-center">
              <div class="stat-icon bg-warning"><i class="bi bi-cash-coin"></i></div>
              <div class="ms-3 flex-fill">
                <div class="stat-number" data-num="<?= $pembayaran_pending ?>"><?= number_format($pembayaran_pending) ?></div>
                <div class="stat-label text-muted">Pembayaran Pending</div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-6 col-lg-3">
          <div class="stat-card stat-primary shadow-sm h-100">
            <div class="d-flex align-items-center">
              <div class="stat-icon bg-primary"><i class="bi bi-file-earmark-text"></i></div>
              <div class="ms-3 flex-fill">
                <div class="stat-number" data-num="<?= $abstrak_masuk ?>"><?= number_format($abstrak_masuk) ?></div>
                <div class="stat-label text-muted">Abstrak Masuk</div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-6 col-lg-3">
          <div class="stat-card stat-danger shadow-sm h-100">
            <div class="d-flex align-items-center">
              <div class="stat-icon bg-danger"><i class="bi bi-person-gear"></i></div>
              <div class="ms-3 flex-fill">
                <div class="stat-number" data-num="<?= $abstrak_unassigned ?>"><?= number_format($abstrak_unassigned) ?></div>
                <div class="stat-label text-muted">Belum Ditugaskan</div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-6 col-lg-3">
          <div class="stat-card stat-success shadow-sm h-100">
            <div class="d-flex align-items-center">
              <div class="stat-icon bg-success"><i class="bi bi-calendar2-event"></i></div>
              <div class="ms-3 flex-fill">
                <div class="stat-number" data-num="<?= $total_event ?>"><?= number_format($total_event) ?></div>
                <div class="stat-label text-muted">Total Event</div>
              </div>
            </div>
          </div>
        </div>
      </div>



      <!-- Content Cards Row -->
      <div class="row g-3 mb-4">
        <!-- Pembayaran Pending -->
        <div class="col-12 col-xl-6">
          <div class="card shadow-sm h-100">
            <div class="card-header bg-transparent border-0 pb-0">
              <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                  <i class="bi bi-cash-coin me-2 text-warning"></i>Pembayaran Pending
                </h5>
                <div class="dropdown">
                  <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-three-dots-vertical"></i>
                  </button>
                  <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="<?= site_url('admin/pembayaran') ?>">Semua Pembayaran</a></li>
                    <li><a class="dropdown-item" href="<?= site_url('admin/pembayaran/export') ?>">Export Data</a></li>
                  </ul>
                </div>
              </div>
            </div>
            <div class="card-body pt-2">
              <?php if (!empty($pendingPayments)): ?>
                <div class="table-responsive">
                  <table class="table table-sm table-hover align-middle">
                    <thead class="table-light">
                      <tr>
                        <th>Nama</th>
                        <th class="d-none d-md-table-cell">Event</th>
                        <th class="text-end">Jumlah</th>
                        <th class="text-nowrap d-none d-sm-table-cell">Tanggal</th>
                        <th width="80"></th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach (array_slice($pendingPayments, 0, 5) as $p): ?>
                        <tr>
                          <td>
                            <div class="fw-semibold"><?= esc($p['nama_lengkap'] ?? '-') ?></div>
                            <div class="small text-muted d-md-none"><?= esc($p['event_title'] ?? 'Event') ?></div>
                          </td>
                          <td class="d-none d-md-table-cell">
                            <div class="text-truncate" style="max-width: 150px;">
                              <?= esc($p['event_title'] ?? 'Event') ?>
                            </div>
                          </td>
                          <td class="text-end">
                            <span class="badge bg-warning text-dark">
                              Rp <?= number_format((float)($p['jumlah'] ?? 0), 0, ',', '.') ?>
                            </span>
                          </td>
                          <td class="text-nowrap d-none d-sm-table-cell">
                            <small class="text-muted"><?= esc($fmtDate($p['tanggal_bayar'] ?? null)) ?></small>
                          </td>
                          <td class="text-end">
                            <a href="<?= site_url('admin/pembayaran/detail/'.(int)($p['id_pembayaran'] ?? 0)) ?>" 
                               class="btn btn-sm btn-outline-primary">
                              <i class="bi bi-eye d-md-none"></i>
                              <span class="d-none d-md-inline">Detail</span>
                            </a>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
                <?php if (count($pendingPayments) > 5): ?>
                  <div class="text-center pt-2 border-top">
                    <a href="<?= site_url('admin/pembayaran') ?>" class="btn btn-sm btn-outline-warning">
                      Lihat Semua (<?= count($pendingPayments) ?>)
                    </a>
                  </div>
                <?php endif; ?>
              <?php else: ?>
                <div class="text-center py-5">
                  <div class="mb-3"><i class="bi bi-check-circle fs-1 text-success"></i></div>
                  <h6 class="fw-semibold">Semua Pembayaran Terverifikasi</h6>
                  <p class="text-muted small mb-0">Tidak ada pembayaran yang menunggu verifikasi.</p>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Recent Abstracts -->
        <div class="col-12 col-xl-6">
          <div class="card shadow-sm h-100">
            <div class="card-header bg-transparent border-0 pb-0">
              <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                  <i class="bi bi-file-earmark-text me-2 text-primary"></i>Abstrak Terbaru
                </h5>
                <div class="dropdown">
                  <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-three-dots-vertical"></i>
                  </button>
                  <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="<?= site_url('admin/abstrak') ?>">Semua Abstrak</a></li>
                    <li><a class="dropdown-item" href="<?= site_url('admin/abstrak/export') ?>">Export Data</a></li>
                  </ul>
                </div>
              </div>
            </div>
            <div class="card-body pt-2">
              <div class="activities-scroll">
                <?php if (!empty($recent_abstrak)): ?>
                  <?php foreach (array_slice($recent_abstrak, 0, 6) as $ab):
                    $st  = strtolower($ab['status'] ?? 'menunggu');
                    $badgeClass = match($st) {
                      'menunggu' => 'bg-warning text-dark',
                      'sedang_direview' => 'bg-info',
                      'diterima' => 'bg-success',
                      'ditolak' => 'bg-danger',
                      'revisi' => 'bg-secondary',
                      default => 'bg-secondary'
                    };
                  ?>
                    <div class="notice mb-2">
                      <div class="d-flex align-items-start gap-3">
                        <div class="notice-icon">
                          <i class="bi bi-journal-text text-primary"></i>
                        </div>
                        <div class="flex-fill min-width-0">
                          <div class="notice-title fw-semibold text-truncate" title="<?= esc($ab['judul'] ?? '-') ?>">
                            <?= esc(mb_strimwidth($ab['judul'] ?? '-', 0, 50, '...')) ?>
                          </div>
                          <div class="notice-meta small text-muted">
                            oleh <?= esc($ab['nama_lengkap'] ?? '-') ?> • <?= esc($fmtDate($ab['created_at'] ?? null)) ?>
                          </div>
                        </div>
                        <div class="flex-shrink-0">
                          <span class="badge <?= $badgeClass ?>"><?= ucfirst($st) ?></span>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <div class="text-center py-5">
                    <div class="mb-3"><i class="bi bi-inbox fs-1 text-muted"></i></div>
                    <h6 class="fw-semibold">Belum Ada Abstrak</h6>
                    <p class="text-muted small mb-0">Abstrak yang dikirim akan tampil di sini.</p>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Bottom Row -->
      <div class="row g-3">
        <!-- Unassigned Abstracts -->
        <div class="col-12 col-xl-6">
          <div class="card shadow-sm h-100">
            <div class="card-header bg-transparent border-0 pb-0">
              <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                  <i class="bi bi-person-gear me-2 text-danger"></i>Perlu Penugasan Reviewer
                </h5>
                <a href="<?= site_url('admin/reviewer') ?>" class="btn btn-sm btn-outline-danger">
                  <i class="bi bi-arrow-right me-1"></i>Kelola
                </a>
              </div>
            </div>
            <div class="card-body pt-2">
              <div class="activities-scroll">
                <?php if (!empty($unassigned_list)): ?>
                  <?php foreach (array_slice($unassigned_list, 0, 5) as $ua): ?>
                    <div class="notice mb-2">
                      <div class="d-flex align-items-start gap-3">
                        <div class="notice-icon">
                          <i class="bi bi-exclamation-triangle text-danger"></i>
                        </div>
                        <div class="flex-fill min-width-0">
                          <div class="notice-title fw-semibold text-truncate" title="<?= esc($ua['judul'] ?? '-') ?>">
                            <?= esc(mb_strimwidth($ua['judul'] ?? '-', 0, 50, '...')) ?>
                          </div>
                          <div class="notice-meta small text-muted">
                            oleh <?= esc($ua['nama_lengkap'] ?? '-') ?> • <?= esc($fmtDate($ua['created_at'] ?? null)) ?>
                          </div>
                        </div>
                        <div class="flex-shrink-0">
                          <a href="<?= site_url('admin/abstrak/detail/'.(int)($ua['id_abstrak'] ?? 0)) ?>" 
                             class="btn btn-sm btn-outline-danger">
                            <i class="bi bi-person-plus d-md-none"></i>
                            <span class="d-none d-md-inline">Tugaskan</span>
                          </a>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <div class="text-center py-5">
                    <div class="mb-3"><i class="bi bi-check2-circle fs-1 text-success"></i></div>
                    <h6 class="fw-semibold">Semua Abstrak Ditugaskan</h6>
                    <p class="text-muted small mb-0">Tidak ada antrian penugasan reviewer.</p>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

        <!-- Recent Events -->
        <div class="col-12 col-xl-6">
          <div class="card shadow-sm h-100">
            <div class="card-header bg-transparent border-0 pb-0">
              <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title mb-0">
                  <i class="bi bi-calendar-event me-2 text-success"></i>Event Terbaru
                </h5>
                <a href="<?= site_url('admin/event') ?>" class="btn btn-sm btn-outline-success">
                  <i class="bi bi-arrow-right me-1"></i>Kelola
                </a>
              </div>
            </div>
            <div class="card-body pt-2">
              <div class="activities-scroll">
                <?php if (!empty($recent_events)): ?>
                  <?php foreach (array_slice($recent_events, 0, 5) as $ev): ?>
                    <div class="notice mb-2">
                      <div class="d-flex align-items-start gap-3">
                        <div class="notice-icon">
                          <i class="bi bi-calendar3 text-success"></i>
                        </div>
                        <div class="flex-fill min-width-0">
                          <div class="notice-title fw-semibold text-truncate" title="<?= esc($ev['title'] ?? 'Event') ?>">
                            <?= esc(mb_strimwidth($ev['title'] ?? 'Event', 0, 50, '...')) ?>
                          </div>
                          <div class="notice-meta small text-muted">
                            <?= esc($fmtDate($ev['event_date'] ?? null)) ?> • 
                            <?= esc($ev['event_time'] ?? '-') ?> • 
                            <?= esc(ucfirst($ev['format'] ?? '-')) ?>
                          </div>
                        </div>
                        <div class="flex-shrink-0">
                          <span class="badge <?= !empty($ev['is_active']) ? 'bg-success':'bg-secondary' ?>">
                            <?= !empty($ev['is_active']) ? 'Aktif' : 'Nonaktif' ?>
                          </span>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <div class="text-center py-5">
                    <div class="mb-3"><i class="bi bi-calendar-x fs-1 text-muted"></i></div>
                    <h6 class="fw-semibold">Belum Ada Event</h6>
                    <p class="text-muted small mb-0">
                      <a href="<?= site_url('admin/event') ?>" class="text-decoration-none">Buat event baru</a>
                    </p>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>


<script>
document.addEventListener('DOMContentLoaded', function() {
  // Animated counter for statistics
  const animateCounters = () => {
    const counters = document.querySelectorAll('.stat-number[data-num]');
    
    counters.forEach(counter => {
      const target = parseInt(counter.getAttribute('data-num') || '0', 10);
      const increment = Math.max(1, Math.ceil(target / 30));
      let current = 0;
      
      const updateCounter = () => {
        if (current < target) {
          current = Math.min(current + increment, target);
          counter.textContent = new Intl.NumberFormat('id-ID').format(current);
          requestAnimationFrame(updateCounter);
        } else {
          counter.textContent = new Intl.NumberFormat('id-ID').format(target);
        }
      };
      
      updateCounter();
    });
  };

  // Start counter animation
  setTimeout(animateCounters, 200);

  // Auto-refresh stats every 5 minutes
  setInterval(() => {
    fetch('<?= site_url("admin/dashboard/getStats") ?>')
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          // Update KPI values if needed
          console.log('Stats updated:', data.stats);
        }
      })
      .catch(error => console.log('Stats refresh failed:', error));
  }, 300000); // 5 minutes

  // Initialize tooltips if Bootstrap tooltips are available
  if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl));
  }

  // Smooth scroll for anchor links
  document.querySelectorAll('a[href^="#"]').forEach(anchor => {
    anchor.addEventListener('click', function (e) {
      e.preventDefault();
      const target = document.querySelector(this.getAttribute('href'));
      if (target) {
        target.scrollIntoView({
          behavior: 'smooth',
          block: 'start'
        });
      }
    });
  });
});
</script>