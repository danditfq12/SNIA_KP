<?php
$title      = $title ?? 'Absensi';
$kpi        = $kpi ?? ['count_today'=>0,'count_next'=>0,'count_hadir'=>0];
$boxesToday = $boxesToday ?? [];
$boxesNext  = $boxesNext ?? [];
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <!-- HEADER -->
      <div class="header-section header-blue d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 class="welcome-text mb-1"><i class="bi bi-qr-code-scan"></i> Absensi</h2>
          <div class="text-white-50">Silakan pilih event, lalu lakukan absen di halaman detail.</div>
        </div>
        <div class="text-end d-none d-md-block">
          <small class="text-white-50 d-block">Hari ini</small>
          <strong class="text-white"><?= date('d M Y') ?></strong>
        </div>
      </div>

      <!-- KPI -->
      <div class="row g-3 mb-3">
        <div class="col-4">
          <div class="stat-card shadow-sm h-100">
            <div class="d-flex align-items-center">
              <div class="stat-icon bg-primary"><i class="bi bi-sun"></i></div>
              <div class="ms-3">
                <div class="stat-number"><?= number_format($kpi['count_today']) ?></div>
                <div class="text-muted">Event Hari Ini</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-4">
          <div class="stat-card shadow-sm h-100">
            <div class="d-flex align-items-center">
              <div class="stat-icon bg-info"><i class="bi bi-calendar2-week"></i></div>
              <div class="ms-3">
                <div class="stat-number"><?= number_format($kpi['count_next']) ?></div>
                <div class="text-muted">Event Mendatang</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-4">
          <div class="stat-card shadow-sm h-100">
            <div class="d-flex align-items-center">
              <div class="stat-icon bg-success"><i class="bi bi-person-check"></i></div>
              <div class="ms-3">
                <div class="stat-number"><?= number_format($kpi['count_hadir']) ?></div>
                <div class="text-muted">Total Hadir</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- SECTION: Hari Ini -->
      <div class="card shadow-sm mb-4">
        <div class="card-header bg-gradient-primary text-white">
          <strong><i class="bi bi-sun"></i> Event Hari Ini</strong>
        </div>
        <div class="card-body">
          <?php if ($boxesToday): ?>
            <div class="row g-3">
              <?php foreach ($boxesToday as $e): ?>
              <div class="col-12 col-md-6 col-xl-4">
                <div class="event-card h-100">
                  <div class="d-flex justify-content-between">
                    <h5 class="mb-1"><?= esc($e['title']) ?></h5>
                    <?php if ($e['attended']): ?>
                      <span class="badge bg-success">Sudah Absen</span>
                    <?php endif; ?>
                  </div>
                  <div class="small text-muted mb-2">
                    <i class="bi bi-calendar-event"></i> <?= date('d M Y', strtotime($e['date'])) ?>,
                    <i class="bi bi-clock ms-1"></i> <?= esc($e['time']) ?>
                  </div>
                  <?php if (!empty($e['location'])): ?>
                    <div class="small text-muted mb-2"><i class="bi bi-geo-alt"></i> <?= esc($e['location']) ?></div>
                  <?php endif; ?>

                  <div class="d-flex align-items-center gap-2 mt-2">
                    <?php if ($e['window']['is_open']): ?>
                      <a href="/presenter/absensi/event/<?= (int)$e['event_id'] ?>" class="btn btn-primary btn-sm">
                        <i class="bi bi-check2-circle"></i> Absen
                      </a>
                    <?php else: ?>
                      <a href="/presenter/absensi/event/<?= (int)$e['event_id'] ?>" class="btn btn-outline-secondary btn-sm">
                        <i class="bi bi-info-circle"></i> Detail
                      </a>
                      <span class="badge bg-warning text-dark"><?= esc($e['window']['reason'] ?: 'Tertutup') ?></span>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="text-center text-muted py-2">Tidak ada event hari ini.</div>
          <?php endif; ?>
        </div>
      </div>

      <!-- SECTION: Mendatang -->
      <div class="card shadow-sm">
        <div class="card-header bg-gradient-primary text-white">
          <strong><i class="bi bi-calendar2-week"></i> Event Mendatang</strong>
        </div>
        <div class="card-body">
          <?php if ($boxesNext): ?>
            <div class="row g-3">
              <?php foreach ($boxesNext as $e): ?>
              <div class="col-12 col-md-6 col-xl-4">
                <div class="event-card h-100">
                  <div class="d-flex justify-content-between">
                    <h5 class="mb-1"><?= esc($e['title']) ?></h5>
                  </div>
                  <div class="small text-muted mb-2">
                    <i class="bi bi-calendar-event"></i> <?= date('d M Y', strtotime($e['date'])) ?>,
                    <i class="bi bi-clock ms-1"></i> <?= esc($e['time']) ?>
                  </div>
                  <?php if (!empty($e['location'])): ?>
                    <div class="small text-muted mb-2"><i class="bi bi-geo-alt"></i> <?= esc($e['location']) ?></div>
                  <?php endif; ?>

                  <div class="d-flex align-items-center gap-2 mt-2">
                    <a href="/presenter/absensi/event/<?= (int)$e['event_id'] ?>" class="btn btn-outline-secondary btn-sm">
                      <i class="bi bi-info-circle"></i> Detail
                    </a>
                    <span class="badge bg-secondary">Belum Dimulai</span>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="text-center text-muted py-2">Belum ada event mendatang.</div>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<style>
  :root{
    --primary-color:#2563eb; --info-color:#06b6d4; --success-color:#10b981;
  }
  body{ background:#f8fafc; font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }

  .header-section.header-blue{
    background: linear-gradient(135deg, var(--primary-color) 0%, #1e40af 100%);
    color:#fff; padding:22px; border-radius:14px; box-shadow:0 8px 28px rgba(0,0,0,.12);
  }
  .welcome-text{ font-size:1.35rem; font-weight:500; }

  .stat-card{
    background:#fff; border-radius:14px; padding:16px; box-shadow:0 8px 28px rgba(0,0,0,.08);
    border-left:4px solid #e9ecef; position:relative; overflow:hidden;
  }
  .stat-card:before{
    content:''; position:absolute; left:0; top:0; height:4px; width:100%;
    background:linear-gradient(90deg,var(--primary-color),var(--info-color));
  }
  .stat-icon{ width:48px; height:48px; border-radius:12px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:20px; }
  .stat-number{ font-size:1.3rem; font-weight:700; color:#1e293b; line-height:1; }

  .bg-gradient-primary{ background: linear-gradient(135deg, var(--primary-color), var(--info-color)); color:#fff; }

  .event-card{
    background:#fff; border-radius:14px; padding:16px; box-shadow:0 8px 24px rgba(15,23,42,.06);
    border:1px solid #eef2f7;
  }

  .btn{ border-radius:10px; }
  .badge{ border-radius:8px; }
</style>