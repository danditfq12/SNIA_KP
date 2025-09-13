<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<?php
$stats           = $stats           ?? ['total_events'=>0,'total_abstrak'=>0];
$pendingPayments = $pendingPayments ?? []; // (tidak dipakai di UI sesuai revisi)
$todayAbsensi    = $todayAbsensi    ?? [];
$registrations   = $registrations   ?? [];
$activities      = $activities      ?? [];
$abstrak         = $abstrak         ?? [];
?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <!-- HEADER -->
      <div class="header-section header-blue mb-4 d-flex justify-content-between align-items-center">
        <div>
          <h2 class="welcome-text mb-1"><i class="bi bi-speedometer2"></i> Dashboard Presenter</h2>
          <div class="text-white-50">Ringkasan & aktivitas terbaru</div>
        </div>
        <div class="text-end d-none d-md-block">
          <small class="text-white-50 d-block">Hari ini</small>
          <strong class="text-white"><?= date('d M Y') ?></strong>
        </div>
      </div>
      
      <!-- GRID -->
      <div class="row g-4">
        <!-- AKTIVITAS TERBARU -->
        <div class="col-12 col-lg-8">
          <div class="card shadow-sm h-100">
            <div class="card-header bg-gradient-primary text-white">
              <h5 class="mb-0"><i class="bi bi-bell me-2"></i>Aktivitas Terbaru</h5>
            </div>
            <div class="card-body">
              <?php if (empty($activities)): ?>
                <div class="text-muted small">Belum ada aktivitas terbaru.</div>
              <?php else: ?>
                <div class="clip-3">
                  <ul class="list-group list-group-flush activity-list compact">
                  <?php foreach ($activities as $a): ?>
                    <li class="list-group-item d-flex align-items-start">
                      <div class="activity-dot bg-<?= esc($a['badge']) ?>">
                        <i class="bi <?= esc($a['icon']) ?>"></i>
                      </div>
                      <div class="ms-2 flex-fill">
                        <div class="d-flex justify-content-between align-items-center">
                          <div class="fw-semibold small"><?= esc($a['title']) ?></div>
                          <small class="text-muted"><?= date('d M Y H:i', (int)$a['time']) ?></small>
                        </div>
                        <?php if (!empty($a['desc'])): ?>
                          <div class="text-muted xsmall mt-1"><?= esc($a['desc']) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($a['link'])): ?>
                          <a class="btn btn-xs btn-outline-primary mt-2" href="<?= esc($a['link']) ?>">Lihat</a>
                        <?php endif; ?>
                      </div>
                    </li>
                  <?php endforeach; ?>
                  </ul>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Event Terdaftar (JANGAN DIHAPUS) -->
        <div class="col-12 col-lg-4">
          <div class="card shadow-sm h-100">
            <div class="card-header bg-light"><strong>Event Terdaftar</strong></div>
            <div class="card-body">
              <?php if (empty($registrations)): ?>
                <p class="text-muted mb-0 small">Belum ada event terdaftar.</p>
              <?php else: ?>
                <div class="clip-3">
                  <ul class="list-group list-group-flush small">
                    <?php foreach ($registrations as $r): ?>
                      <li class="list-group-item d-flex justify-content-between align-items-center py-2">
                        <span class="text-truncate">
                          <i class="bi bi-calendar-event me-2"></i>
                          <?= esc($r['event_title'] ?? ($r['title'] ?? '-')) ?>
                        </span>
                        <span class="badge bg-secondary ms-2"><?= esc($r['status'] ?? '-') ?></span>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Abstrak Saya -->
        <div class="col-12">
          <div class="card shadow-sm">
            <div class="card-header bg-light"><strong>Abstrak Saya</strong></div>
            <div class="card-body">
              <?php if (empty($abstrak)): ?>
                <p class="text-muted mb-0 small">Belum ada abstrak.</p>
              <?php else: ?>
                <div class="table-responsive clip-3-table">
                  <table class="table table-hover align-middle mb-0">
                    <thead>
                      <tr>
                        <th>Judul</th>
                        <th class="d-none d-md-table-cell">Tanggal</th>
                        <th>Status</th>
                        <th style="width:120px;"></th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($abstrak as $a):
                        $st = strtolower($a['status'] ?? 'menunggu');
                        $badge = [
                          'diterima'         => 'success',
                          'revisi'           => 'warning',
                          'ditolak'          => 'danger',
                          'menunggu'         => 'secondary',
                          'sedang_direview'  => 'info',
                        ][$st] ?? 'secondary';
                      ?>
                        <tr>
                          <td class="text-truncate" style="max-width: 520px;"><?= esc($a['judul'] ?? '-') ?></td>
                          <td class="d-none d-md-table-cell">
                            <?= !empty($a['tanggal_upload']) ? date('d M Y H:i', strtotime($a['tanggal_upload'])) : '-' ?>
                          </td>
                          <td><span class="badge bg-<?= $badge ?>"><?= ucfirst($st) ?></span></td>
                          <td>
                            <a class="btn btn-sm btn-outline-primary w-100" href="/presenter/abstrak/detail/<?= (int)($a['id_abstrak'] ?? 0) ?>">Detail</a>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
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
  :root{
    --primary:#2563eb; --info:#06b6d4; --success:#10b981; --warning:#f59e0b; --danger:#ef4444;
  }
  body{ background:#f9fafb; }

  .header-section.header-blue{
    background: linear-gradient(135deg, var(--primary), #1e40af);
    color:#fff; padding:22px; border-radius:16px; box-shadow:0 8px 28px rgba(0,0,0,.12);
  }
  .header-section .welcome-text{ font-size:1.4rem; font-weight:700; }

  .stat-card{
    background:#fff; border-radius:14px; padding:14px;
    box-shadow:0 2px 8px rgba(0,0,0,.06); transition:.2s;
  }
  .stat-card:hover{ transform:translateY(-2px); }
  .stat-icon{
    width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:18px;
  }
  .stat-number{ font-size:1.35rem; font-weight:700; color:#1f2937; line-height:1; }
  .stat-label{ font-size:.86rem; color:#6b7280; }

  .bg-gradient-primary{ background:linear-gradient(135deg,var(--primary),var(--info))!important; }

  /* ====== Limit 3 items + scroll ====== */
  .clip-3{ max-height: 200px; overflow: auto; }
  .clip-3::-webkit-scrollbar{ width:8px; height:8px; }
  .clip-3::-webkit-scrollbar-thumb{ background:#cbd5e1; border-radius:8px; }
  .clip-3::-webkit-scrollbar-track{ background:transparent; }

  /* Aktivitas list — versi compact */
  .activity-list.compact .list-group-item{
    border:0; border-bottom:1px solid #eef2f7;
    padding:.55rem .6rem; /* lebih kecil */
  }
  .activity-dot{
    width:30px; height:30px; border-radius:8px;
    display:flex; align-items:center; justify-content:center; color:#fff; flex:0 0 30px;
    font-size:14px;
  }
  .xsmall{ font-size: .78rem; }

  /* Table clip (≈ 3–4 baris) + sticky header */
  .clip-3-table{ max-height: 280px; overflow: auto; }
  .clip-3-table thead th{ position: sticky; top: 0; background: #f8fafc; z-index: 2; }
  .clip-3-table::-webkit-scrollbar{ width:8px; height:8px; }
  .clip-3-table::-webkit-scrollbar-thumb{ background:#cbd5e1; border-radius:8px; }
  .clip-3-table::-webkit-scrollbar-track{ background:transparent; }

  /* Mobile tweaks */
  @media (max-width: 768px){
    .header-section.header-blue{ padding:18px; }
    .header-section .welcome-text{ font-size:1.2rem; }
    .stat-number{ font-size:1.1rem; }
    .clip-3{ max-height: 190px; }
    .clip-3-table{ max-height: 260px; }
  }
  /* iOS inertia scroll feel */
  .clip-3, .clip-3-table{ -webkit-overflow-scrolling: touch; }
</style>