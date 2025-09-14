<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<?php
$stats           = $stats           ?? ['total_events'=>0,'total_abstrak'=>0];
$todayAbsensi    = $todayAbsensi    ?? [];
$registrations   = $registrations   ?? [];
$progressEvents  = $progressEvents  ?? []; // <- sumber data progres
$todaySchedule   = $todaySchedule   ?? [];
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

      <!-- ===== PROGRESS EVENT (paling atas, gaya stepper linear) ===== -->
      <div class="card shadow-sm mb-4">
        <div class="card-header bg-light d-flex justify-content-between align-items-center">
          <strong>Progress Event</strong>
          <small class="text-muted">Pantau tahapan hingga Selesai</small>
        </div>
        <div class="card-body">
          <?php if (empty($progressEvents)): ?>
            <div class="text-muted text-center py-3">Tidak ada progres yang perlu dilengkapi. Semua event telah selesai</div>
          <?php else: ?>
            <div class="vstack gap-4">
              <?php foreach ($progressEvents as $p): ?>
                <?php
                  $title     = (string)($p['title'] ?? '-');
                  $eventId   = (int)($p['event_id'] ?? 0);
                  $dateLabel = !empty($p['event_date']) ? date('d F Y', strtotime($p['event_date'])) : '';

                  $absSt  = strtolower($p['labels']['abs_status'] ?? '');
                  $paySt  = strtolower($p['labels']['pay_status'] ?? '');

                  // --- Tentukan status tiap step (done/current/todo) ---
                  // S1 Abstrak = ada abstrak apapun statusnya
                  $s1 = ($absSt !== '') ? 'done' : 'current';

                  // S2 Review = diterima -> done; jika ada abstrak tapi belum diterima -> current; kalau belum ada abstrak -> todo
                  if ($absSt === 'diterima')       $s2 = 'done';
                  elseif ($absSt !== '')           $s2 = 'current';
                  else                             $s2 = 'todo';

                  // S3 Bayar = ada pembayaran (pending/verified/rejected) -> done
                  // jika abstrak diterima dan belum bayar -> current, lainnya -> todo
                  if (in_array($paySt, ['pending','verified','rejected'], true)) $s3 = 'done';
                  else $s3 = ($absSt === 'diterima') ? 'current' : 'todo';

                  // S4 Verifikasi = verified -> done, pending -> current, selain itu -> todo
                  if ($paySt === 'verified')       $s4 = 'done';
                  elseif ($paySt === 'pending')    $s4 = 'current';
                  else                              $s4 = 'todo';

                  // S5 Selesai = sama dengan verifikasi done
                  $s5 = ($paySt === 'verified') ? 'done' : 'todo';

                  $doneCount = 0;
                  foreach ([$s1,$s2,$s3,$s4,$s5] as $s) if ($s==='done') $doneCount++;

                  // Pesan & CTA
                  $alertCls = 'alert-secondary';
                  $alertMsg = 'Lengkapi tahapan di atas untuk mengakses fitur event.';
                  $ctaHref  = "/presenter/events/detail/{$eventId}";
                  $ctaText  = 'Lihat Detail Event';

                  if ($paySt === 'verified') {
                      $alertCls = 'alert-success';
                      $alertMsg = 'Pembayaran terverifikasi: Semua fitur event tersedia.';
                      $ctaText  = 'Akses Fitur Event';
                  } elseif ($paySt === 'pending') {
                      $alertCls = 'alert-info';
                      $alertMsg = 'Pembayaran menunggu verifikasi.';
                      $ctaHref  = "/presenter/pembayaran/detail/".(int)($p['labels']['payment_id'] ?? 0);
                      $ctaText  = 'Lihat Pembayaran';
                  } elseif ($absSt === 'revisi') {
                      $alertCls = 'alert-warning';
                      $alertMsg = 'Abstrak membutuhkan revisi.';
                      $ctaText  = 'Lihat Detail Event';
                  } elseif ($absSt === 'ditolak') {
                      $alertCls = 'alert-danger';
                      $alertMsg = 'Abstrak ditolak. Silakan konsultasikan revisi.';
                      $ctaText  = 'Lihat Detail Event';
                  } elseif ($absSt === 'diterima' && $paySt === '') {
                      $alertCls = 'alert-primary';
                      $alertMsg = 'Abstrak diterima. Lanjutkan ke pembayaran.';
                      $ctaHref  = "/presenter/pembayaran/instruction/{$eventId}";
                      $ctaText  = 'Instruksi Pembayaran';
                  }
                ?>

                <div class="progress-card p-3 p-md-4 border rounded-3">
                  <!-- Header judul + step badge -->
                  <div class="d-flex justify-content-between align-items-center mb-2">
                    <div class="fw-semibold"><?= esc($title) ?></div>
                    <span class="badge rounded-pill bg-success-subtle text-success">Step <?= $doneCount ?>/5</span>
                  </div>
                  <?php if ($dateLabel): ?>
                    <div class="text-muted small mb-3"><i class="bi bi-calendar-event me-1"></i><?= esc($dateLabel) ?></div>
                  <?php endif; ?>

                  <!-- STEPPER -->
                  <div class="stepper my-3">
                    <div class="step <?= $s1 ?>">
                      <span class="dot"><i class="bi bi-file-earmark"></i></span>
                      <div class="label">Abstrak</div>
                    </div>
                    <div class="bar <?= ($s2==='done' || $s2==='current') ? 'on':'' ?>"></div>

                    <div class="step <?= $s2 ?>">
                      <span class="dot"><i class="bi bi-search"></i></span>
                      <div class="label">Review</div>
                    </div>
                    <div class="bar <?= ($s3==='done' || $s3==='current') ? 'on':'' ?>"></div>

                    <div class="step <?= $s3 ?>">
                      <span class="dot"><i class="bi bi-credit-card"></i></span>
                      <div class="label">Bayar</div>
                    </div>
                    <div class="bar <?= ($s4==='done' || $s4==='current') ? 'on':'' ?>"></div>

                    <div class="step <?= $s4 ?>">
                      <span class="dot"><i class="bi bi-check2"></i></span>
                      <div class="label">Verifikasi</div>
                    </div>
                    <div class="bar <?= ($s5==='done') ? 'on':'' ?>"></div>

                    <div class="step <?= $s5 ?>">
                      <span class="dot"><i class="bi bi-star-fill"></i></span>
                      <div class="label">Selesai</div>
                    </div>
                  </div>

                  <!-- ALERT + CTA -->
                  <div class="alert <?= $alertCls ?> d-flex align-items-center mb-3" role="alert">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    <div><?= $alertMsg ?></div>
                  </div>
                  <div class="text-center">
                    <a href="<?= esc($ctaHref) ?>" class="btn btn-success">
                      <i class="bi bi-play-fill me-1"></i><?= esc($ctaText) ?>
                    </a>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- ===== GRID: Jadwal 40% vs Aktivitas 60% (tetap) ===== -->
      <div class="row g-4">
        <!-- Jadwal (kiri 40%) -->
        <div class="col-12 col-lg-5">
          <div class="card shadow-sm h-100">
            <div class="card-header bg-light"><strong>Jadwal Hari Ini</strong></div>
            <div class="card-body">
              <?php if (empty($todaySchedule)): ?>
                <p class="text-muted small mb-0">Tidak ada jadwal event hari ini.</p>
              <?php else: ?>
                <ul class="list-group list-group-flush small clip-3">
                  <?php foreach ($todaySchedule as $s): ?>
                    <li class="list-group-item d-flex justify-content-between align-items-start">
                      <div class="me-2">
                        <div class="fw-semibold text-truncate" style="max-width: 360px;"><?= esc($s['title']) ?></div>
                        <div class="text-muted"><?= esc($s['start'] ?: '-') ?> · <?= esc($s['where'] ?: '-') ?></div>
                      </div>
                      <a href="<?= esc($s['link']) ?>" class="btn btn-sm btn-primary">Absen</a>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Aktivitas (kanan 60%) -->
        <div class="col-12 col-lg-7">
          <div class="card shadow-sm h-100">
            <div class="card-header bg-white">
              <h5 class="mb-0 text-dark"><i class="bi bi-bell me-2"></i>Aktivitas Terbaru</h5>
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
                            <small class="text-muted"><?= $a['time'] ? date('d M Y H:i', (int)$a['time']) : '' ?></small>
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

       <!-- Event Terdaftar -->
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
              <?php
                $dateStr = !empty($r['event_date'])
                  ? date('d M Y', strtotime($r['event_date'])) . (!empty($r['event_time']) ? ' · ' . $r['event_time'] : '')
                  : '-';
                $badge = $r['badge'] ?? 'secondary';
              ?>
              <li class="list-group-item d-flex justify-content-between align-items-start py-2">
                <div class="me-2">
                  <div class="text-truncate">
                    <i class="bi bi-calendar-event me-2"></i><?= esc($r['event_title'] ?? ($r['title'] ?? '-')) ?>
                  </div>
                  <div class="text-muted"><?= esc($dateStr) ?></div>
                </div>
                <span class="badge bg-<?= esc($badge) ?> ms-2 align-self-center"><?= esc($r['status'] ?? '-') ?></span>
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
                <div class="table-responsive clip-x">
                  <table class="table table-hover align-middle mb-0 minw-700">
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
                          <td class="text-truncate" style="max-width:420px;"><?= esc($a['judul'] ?? '-') ?></td>
                          <td class="d-none d-md-table-cell text-truncate" style="max-width:260px;"><?= esc($a['event_title'] ?? '-') ?></td>
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
  :root{ --primary:#2563eb; --ring:#eef2f7; --ok:#10b981; --muted:#cbd5e1; }
  body{ background:#f9fafb; }

  .header-section.header-blue{
    background: linear-gradient(135deg, var(--primary), #1e40af);
    color:#fff; padding:22px; border-radius:16px; box-shadow:0 8px 28px rgba(0,0,0,.12);
  }
  .welcome-text{ font-weight:700; font-size:1.35rem; }

  .clip-3{ max-height: 200px; overflow:auto; -webkit-overflow-scrolling:touch; }
  .clip-x{ overflow:auto; -webkit-overflow-scrolling:touch; }
  .minw-700{ min-width:700px; }

  /* ====== STEPPER LINEAR ====== */
  .progress-card{ background:#fff; }
  .stepper{
    display:grid;
    grid-template-columns: repeat(9, 1fr); /* 5 steps + 4 bars */
    align-items:center; gap:8px;
  }
  .stepper .step{
    text-align:center;
  }
  .stepper .dot{
    width:44px; height:44px; border-radius:50%;
    display:grid; place-items:center;
    border:2px solid var(--muted);
    color:#64748b; background:#fff; margin:0 auto;
    transition:.2s;
  }
  .stepper .label{
    margin-top:6px; font-size:.85rem; color:#111827;
  }
  .stepper .bar{
    height:4px; background:var(--muted); border-radius:4px;
    transition:.2s;
  }
  .stepper .bar.on{ background:var(--ok); }

  .stepper .step.done .dot{
    background:rgba(16,185,129,.15); border-color:var(--ok); color:var(--ok);
  }
  .stepper .step.current .dot{
    background:rgba(37,99,235,.1); border-color:#2563eb; color:#2563eb;
  }
  .stepper .step.todo .dot{
    background:#fff; border-color:var(--muted); color:#94a3b8;
  }

  .activity-list.compact .list-group-item{
    border:0; border-bottom:1px solid var(--ring);
    padding:.55rem .6rem;
  }
  .activity-dot{
    width:30px; height:30px; border-radius:8px;
    display:flex; align-items:center; justify-content:center;
    color:#fff; flex:0 0 30px; font-size:14px;
  }

  .alert-success   { background:#d1fae5; color:#065f46; border:0; }
  .alert-primary   { background:#e0e7ff; color:#3730a3; border:0; }
  .alert-info      { background:#cffafe; color:#155e75; border:0; }
  .alert-warning   { background:#fef3c7; color:#92400e; border:0; }
  .alert-danger    { background:#fee2e2; color:#991b1b; border:0; }
  .alert-secondary { background:#f1f5f9; color:#334155; border:0; }

  @media (max-width: 768px){
    .header-section.header-blue{ padding:18px; }
    .welcome-text{ font-size:1.2rem; }
    .clip-3{ max-height:190px; }
    .stepper .dot{ width:40px; height:40px; }
    .stepper .label{ font-size:.8rem; }
  }
</style>