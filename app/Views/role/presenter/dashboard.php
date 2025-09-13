<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <!-- HEADER -->
      <div class="header-section header-blue mb-4">
        <h2 class="welcome-text mb-1"><i class="bi bi-speedometer2"></i> Dashboard Presenter</h2>
        <div class="text-white-50">Ringkasan aktivitas Anda hari ini</div>
      </div>

      <!-- STAT CARDS -->
      <div class="row g-3 mb-4">
        <div class="col-6 col-xl-3">
          <div class="stat-card shadow-sm">
            <div class="d-flex align-items-center">
              <div class="stat-icon bg-primary"><i class="bi bi-calendar-event"></i></div>
              <div class="ms-3">
                <div class="stat-number"><?= $stats['total_events'] ?></div>
                <div class="stat-label">Event</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-6 col-xl-3">
          <div class="stat-card shadow-sm">
            <div class="d-flex align-items-center">
              <div class="stat-icon bg-success"><i class="bi bi-file-earmark-text"></i></div>
              <div class="ms-3">
                <div class="stat-number"><?= $stats['total_abstrak'] ?></div>
                <div class="stat-label">Abstrak</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-6 col-xl-3">
          <div class="stat-card shadow-sm">
            <div class="d-flex align-items-center">
              <div class="stat-icon bg-warning"><i class="bi bi-cash-stack"></i></div>
              <div class="ms-3">
                <div class="stat-number"><?= count($pendingPayments) ?></div>
                <div class="stat-label">Pembayaran Pending</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-6 col-xl-3">
          <div class="stat-card shadow-sm">
            <div class="d-flex align-items-center">
              <div class="stat-icon bg-info"><i class="bi bi-clipboard-check"></i></div>
              <div class="ms-3">
                <div class="stat-number"><?= count($todayAbsensi) ?></div>
                <div class="stat-label">Absen Hari Ini</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- BOXES -->
      <div class="row g-4">
        <!-- Event -->
        <div class="col-md-6">
          <div class="card shadow-sm h-100">
            <div class="card-header bg-light"><strong>Event Terdaftar</strong></div>
            <div class="card-body">
              <?php if ($registrations): ?>
                <ul class="list-group list-group-flush">
                  <?php foreach ($registrations as $r): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                      <span><i class="bi bi-calendar-event me-2"></i><?= esc($r['event_title']) ?></span>
                      <span class="badge bg-secondary"><?= $r['status'] ?></span>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php else: ?>
                <p class="text-muted">Belum ada event terdaftar.</p>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Dokumen -->
        <div class="col-md-6">
          <div class="card shadow-sm h-100">
            <div class="card-header bg-light"><strong>Dokumen (LOA & Sertifikat)</strong></div>
            <div class="card-body">
              <?php if ($docs): ?>
                <ul class="list-group list-group-flush">
                  <?php foreach ($docs as $d): ?>
                    <li class="list-group-item">
                      <i class="bi bi-file-earmark-check me-2"></i>
                      <?= strtoupper($d['tipe']) ?> - <?= esc($d['event_title']) ?>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php else: ?>
                <p class="text-muted">Belum ada dokumen tersedia.</p>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Abstrak -->
        <div class="col-md-12">
          <div class="card shadow-sm">
            <div class="card-header bg-light"><strong>Abstrak Saya</strong></div>
            <div class="card-body">
              <?php if ($abstrak): ?>
                <ul class="list-group list-group-flush">
                  <?php foreach ($abstrak as $a): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                      <span><i class="bi bi-file-earmark-text me-2"></i><?= esc($a['judul']) ?></span>
                      <span class="badge bg-<?= $a['status']==='diterima'?'success':($a['status']==='revisi'?'warning':'secondary') ?>">
                        <?= $a['status'] ?>
                      </span>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php else: ?>
                <p class="text-muted">Belum ada abstrak.</p>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<!-- CSS khusus Dashboard -->
<style>
  body {
    font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif;
    font-weight: 400;
    background: #f9fafb;
  }

  .header-section.header-blue {
    background: linear-gradient(135deg, #2563eb, #1e40af);
    color: #fff;
    padding: 20px;
    border-radius: 12px;
    box-shadow: 0 4px 12px rgba(0,0,0,.1);
  }
  .header-section .welcome-text {
    font-size: 1.4rem;
    font-weight: 500; /* medium */
  }

  .stat-card {
    background: #fff;
    border-radius: 12px;
    padding: 14px;
    display: flex;
    align-items: center;
    box-shadow: 0 2px 6px rgba(0,0,0,.06);
    transition: .2s;
  }
  .stat-card:hover { transform: translateY(-2px); }
  .stat-icon {
    width: 44px; height: 44px;
    border-radius: 10px;
    display: flex; align-items: center; justify-content: center;
    font-size: 18px; color: #fff;
  }
  .stat-number {
    font-size: 1.4rem;
    font-weight: 500;
    color: #1e293b;
  }
  .stat-label {
    font-size: 0.85rem;
    color: #6b7280;
  }

  .card-header {
    font-weight: 500;
    background: #f8fafc;
  }
  .list-group-item {
    font-size: 0.9rem;
    font-weight: 400;
  }

  /* Mobile */
  @media (max-width: 768px) {
    .stat-card { padding: 12px; }
    .stat-number { font-size: 1.2rem; }
    .welcome-text { font-size: 1.2rem; }
  }
</style>