<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<?php
$stats          = $stats          ?? ['total_events'=>0,'total_abstrak'=>0];
$registrations  = $registrations  ?? [];
$progressEvents = $progressEvents ?? [];
$todaySchedule  = $todaySchedule  ?? [];
$activities     = $activities     ?? [];
$abstrak        = $abstrak        ?? [];
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

      <!-- ===== Progress Event (muncul hanya bila BELUM selesai) ===== -->
      <?php if (!empty($progressEvents)): ?>
      <div class="card shadow-sm mb-4">
        <div class="card-header bg-light d-flex align-items-center gap-2">
          <i class="bi bi-diagram-3"></i><strong>Progress Event</strong>
        </div>
        <div class="card-body">
          <div class="vstack gap-4">
            <?php foreach ($progressEvents as $p): ?>
              <div class="progress-line">
                <div class="mb-2 fw-semibold"><?= esc($p['title']) ?></div>
                <div class="steps">
                  <?php
                    $s = $p['steps'];
                    $items = [
                      ['Abstrak','bi-file-earmark-text',$s['abstrak']],
                      ['Review','bi-search',$s['review']],
                      ['Bayar','bi-credit-card',$s['bayar']],
                      ['Verifikasi','bi-check2-circle',$s['verifikasi']],
                      ['Selesai','bi-star-fill', ($s['abstrak'] && $s['verifikasi'])],
                    ];
                  ?>
                  <?php foreach ($items as $i): ?>
                    <div class="step">
                      <div class="dot <?= $i[2] ? 'done':'' ?>"><i class="bi <?= $i[1] ?>"></i></div>
                      <div class="label"><?= $i[0] ?></div>
                      <div class="bar <?= $i[2] ? 'done':'' ?>"></div>
                    </div>
                  <?php endforeach; ?>
                </div>
                <div class="small text-muted mt-1"><?= esc($p['hint']) ?></div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      </div>
      <?php endif; ?>

      <!-- ===== Row: Jadwal (40%) • Aktivitas (60%) ===== -->
      <div class="row g-4">
        <div class="col-12 col-lg-5">
          <div class="card shadow-sm h-100">
            <div class="card-header bg-light d-flex align-items-center gap-2">
              <i class="bi bi-calendar-check"></i><strong>Jadwal Hari Ini</strong>
            </div>
            <div class="card-body">
              <?php if (empty($todaySchedule)): ?>
                <div class="text-muted small">Tidak ada jadwal untuk hari ini.</div>
              <?php else: ?>
                <ul class="list-group list-group-flush small clip-3">
                  <?php foreach ($todaySchedule as $j): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                      <div class="me-2 text-truncate">
                        <div class="fw-semibold text-truncate"><?= esc($j['title']) ?></div>
                        <div class="text-muted"><?= esc($j['start'] ?: '-') ?> • <?= esc($j['where'] ?: '-') ?></div>
                      </div>
                      <a class="btn btn-sm btn-primary" href="<?= esc($j['link']) ?>">Absen</a>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <div class="col-12 col-lg-7">
          <div class="card shadow-sm h-100">
            <div class="card-header bg-white text-dark d-flex align-items-center gap-2">
              <i class="bi bi-bell"></i><strong>Aktivitas Terbaru</strong>
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

        <!-- Event Terdaftar (tetap ada) -->
        <div class="col-12">
          <div class="card shadow-sm">
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
                          <i class="bi bi-calendar-event me-2"></i><?= esc($r['event_title'] ?? $r['title'] ?? '-') ?>
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
                        <th class="d-none d-md-table-cell">Event</th>
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
                          <td class="text-truncate" style="max-width: 420px;"><?= esc($a['judul'] ?? '-') ?></td>
                          <td class="d-none d-md-table-cell text-truncate" style="max-width: 260px;"><?= esc($a['event_title'] ?? '-') ?></td>
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
    --ring:#eef2f7;
  }
  body{ background:#f9fafb; }

  .header-section.header-blue{
    background: linear-gradient(135deg, var(--primary), #1e40af);
    color:#fff; padding:22px; border-radius:16px; box-shadow:0 8px 28px rgba(0,0,0,.12);
  }
  .header-section .welcome-text{ font-size:1.4rem; font-weight:700; }

  .bg-gradient-primary{ background:linear-gradient(135deg,var(--primary),var(--info))!important; }

  /* ===== Progress line ===== */
  .progress-line .steps{ display:flex; align-items:center; gap:22px; }
  .progress-line .step{ display:flex; align-items:center; gap:10px; }
  .progress-line .dot{
    width:36px;height:36px;border-radius:50%;
    display:grid;place-items:center;color:#fff;background:#cbd5e1;
  }
  .progress-line .dot.done{ background:var(--success); }
  .progress-line .label{ font-size:.9rem; color:#111827; }
  .progress-line .bar{ width:90px; height:6px; border-radius:6px; background:#e5e7eb; }
  .progress-line .bar.done{ background:#86efac; }

  /* ===== Limiters + scrollbar ===== */
  .clip-3{ max-height: 220px; overflow: auto; }
  .clip-3::-webkit-scrollbar{ width:8px; height:8px; }
  .clip-3::-webkit-scrollbar-thumb{ background:#cbd5e1; border-radius:8px; }
  .clip-3-table{ max-height: 300px; overflow: auto; }
  .clip-3-table thead th{ position: sticky; top: 0; background: #f8fafc; z-index: 2; }

  /* Aktivitas compact */
  .activity-list.compact .list-group-item{
    border:0; border-bottom:1px solid var(--ring);
    padding:.55rem .6rem;
  }
  .activity-dot{
    width:30px; height:30px; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#fff; flex:0 0 30px;
    font-size:14px;
  }
  .xsmall{ font-size:.78rem; }

  /* Mobile */
  @media (max-width: 768px){
    .header-section.header-blue{ padding:18px; }
    .header-section .welcome-text{ font-size:1.2rem; }
    .clip-3{ max-height: 200px; }
    .clip-3-table{ max-height: 260px; }
    .progress-line .bar{ width:60px; }
  }
</style>
