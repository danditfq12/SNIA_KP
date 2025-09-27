<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<?php
$stats             = $stats             ?? ['total_events'=>0,'total_loa'=>0];
$todayAbsensi      = $todayAbsensi      ?? [];
$progressEvents    = $progressEvents    ?? [];
$todaySchedule     = $todaySchedule     ?? [];
$monthEvents       = $monthEvents       ?? []; // key: 'YYYY-MM-DD' => [ [id,title,time,where,link], ... ]
$todayHasEvent     = !empty($todaySchedule);

// ====== Kalender (bulan berjalan) ======
$y       = (int)date('Y');
$m       = (int)date('n');
$first   = mktime(0,0,0,$m,1,$y);
$startDow= (int)date('N',$first); // 1..7 (Sen..Min)
$days    = (int)date('t',$first);
$today   = (int)date('j');

$monthEventsByDay = [];
foreach ($monthEvents as $ymd => $items) {
  $d = (int)date('j', strtotime($ymd));
  $monthEventsByDay[$d] = $items; // array of events in that day
}
?>

<div id="content">
  <main class="flex-fill page-wrap-blue">
    <div class="container-xxl px-3 px-md-4 py-3">

      <!-- HERO -->
      <div class="hero-blue mb-4 d-flex justify-content-between align-items-center p-3 p-md-4">
        <div>
          <h4 class="mb-1 fw-bold hero-title"><i class="bi bi-speedometer2 me-2"></i>Dashboard Presenter</h4>
          <div class="text-white-75">Ringkasan progres & aktivitas terbaru</div>
        </div>
        <div class="text-end d-none d-md-block">
          <div class="text-white-75 small">Hari ini</div>
          <div class="fw-semibold text-white"><?= date('d M Y') ?></div>
        </div>
      </div>

      <!-- KPI KIRI (ATAS–BAWAH) + ABSENSI HARI INI (KANAN, LEBAR) -->
      <div class="row g-3 mb-3 align-items-stretch">
        <!-- KPI kiri: ditumpuk (tidak center) -->
        <div class="col-12 col-lg-3">
          <div class="d-grid gap-3">
            <div class="card shadow-soft card-glass-plain p-3 kpi-card kpi-thick">
              <div class="kpi-row">
                <div class="stat-icon bg-blue-soft"><i class="bi bi-calendar2-event"></i></div>
                <div class="kpi-metrics">
                  <div class="kpi-label text-muted xsmall">Event Diikuti</div>
                  <div class="kpi-value"><?= (int)($stats['total_events'] ?? 0) ?></div>
                </div>
              </div>
            </div>

            <div class="card shadow-soft card-glass-plain p-3 kpi-card kpi-thick">
              <div class="kpi-row">
                <div class="stat-icon bg-success-subtle"><i class="bi bi-patch-check"></i></div>
                <div class="kpi-metrics">
                  <div class="kpi-label text-muted xsmall">Total LOA</div>
                  <div class="kpi-value"><?= (int)($stats['total_loa'] ?? 0) ?></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Absensi kanan: lebar -->
        <div class="col-12 col-lg-9">
          <div class="card shadow-soft card-glass-plain p-3 h-100 absensi-card">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <div class="fw-semibold"><i class="bi bi-qr-code me-1"></i>Absensi Hari Ini</div>
              <span class="badge rounded-pill bg-secondary-subtle text-secondary">
                <?= $todayHasEvent ? 'Tersedia' : 'Tidak ada' ?>
              </span>
            </div>

            <?php if (!$todayHasEvent): ?>
              <div class="empty-state">
                <div class="empty-icon bg-primary-subtle text-primary"><i class="bi bi-calendar-x"></i></div>
                <div class="empty-text">Tidak ada event yang diselenggarakan hari ini</div>
                <div class="text-muted small">Cek kalender atau lanjutkan progres event.</div>
                <div class="mt-2">
                  <a href="#kalender" class="btn btn-sm btn-primary me-2"><i class="bi bi-calendar3 me-1"></i>Lihat Kalender</a>
                  <a href="#progress" class="btn btn-sm btn-outline-secondary"><i class="bi bi-flag me-1"></i>Progres</a>
                </div>
              </div>
            <?php else: ?>
              <ul class="list-unstyled mb-0">
                <?php $limit=3; $shown=0; foreach ($todaySchedule as $s): if ($shown++ >= $limit) break; ?>
                  <li class="abs-item mb-2">
                    <div class="d-flex justify-content-between align-items-center">
                      <div class="me-2">
                        <div class="fw-semibold text-truncate"><?= esc($s['title']) ?></div>
                        <div class="text-muted small"><?= esc($s['start'] ?: '-') ?> · <?= esc($s['where'] ?: '-') ?></div>
                      </div>
                      <a href="<?= esc($s['link']) ?>" class="btn btn-sm btn-primary"><i class="bi bi-qr-code me-1"></i>Absen</a>
                    </div>
                  </li>
                <?php endforeach; ?>
              </ul>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- AKTIVITAS & PROGRESS -->
      <div class="row g-3 mb-4">
        <!-- AKTIVITAS TERBARU -->
        <div class="col-12 col-lg-5">
          <div class="card shadow-soft card-glass-plain h-100">
            <div class="p-3 border-bottom small text-muted"><i class="bi bi-bell me-1"></i>Aktivitas Terbaru</div>
            <div class="p-2">
              <?php if (empty($activities)): ?>
                <div class="text-muted small px-2">Belum ada aktivitas.</div>
              <?php else: ?>
                <div class="scroll-area activity-scroll">
                  <ul class="list-group list-group-flush activity-list compact">
                    <?php foreach ($activities as $a): ?>
                      <li class="list-group-item d-flex align-items-start">
                        <div class="activity-dot bg-<?= esc($a['badge']) ?>"><i class="bi <?= esc($a['icon']) ?>"></i></div>
                        <div class="ms-2 flex-fill">
                          <div class="d-flex justify-content-between align-items-center">
                            <div class="fw-semibold small text-truncate"><?= esc($a['title']) ?></div>
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

        <!-- PROGRESS EVENT -->
        <div class="col-12 col-lg-7" id="progress">
          <div class="card shadow-soft card-glass-plain h-100">
            <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
              <div class="small text-muted"><i class="bi bi-flag me-1"></i>Progress Event</div>
              <div class="small text-muted">Flow: Kontributor → Abstrak → Full Paper → Bayar → Selesai</div>
            </div>
            <div class="p-3">
              <?php if (empty($progressEvents)): ?>
                <div class="text-muted text-center py-3">Tidak ada progres. Semua event sudah selesai.</div>
              <?php else: ?>
                <div class="scroll-area progress-scroll">
                  <?php foreach ($progressEvents as $p):
                    $eventId = (int)$p['event_id'];
                    $title   = (string)$p['title'];
                    $absSt   = strtolower($p['labels']['abs_status'] ?? '');
                    $fpSt    = strtoupper($p['labels']['fp_status'] ?? 'NONE');
                    $fpOk    = (bool)($p['labels']['fp_eligible'] ?? false);

                    $absInfo = $absSt ? ucfirst($absSt) : 'Belum';
                    $fpInfo  = ($fpSt==='NONE' ? 'Belum' :
                               ($fpSt==='UPLOADED' ? 'Menunggu' :
                               ($fpSt==='REVISION' ? 'Revisi' :
                               ($fpSt==='ACCEPTED' ? 'Diterima' : 'Ditolak'))));

                    $ctaHref = "/presenter/events/detail/$eventId"; $ctaText = "Lihat Detail";
                    if (!$absSt) {
                      $ctaHref = "/presenter/kontributor/start/$eventId"; $ctaText = "Lengkapi Kontributor";
                    } elseif ($absSt==='ditolak') {
                      $ctaHref = "/presenter/abstrak/create/$eventId";     $ctaText = "Perbaiki Abstrak";
                    } elseif (in_array($absSt, ['menunggu','sedang_direview'], true)) {
                      $ctaHref = "/presenter/events/detail/$eventId";      $ctaText = "Cek Status Abstrak";
                    } elseif ($absSt==='revisi') {
                      $ctaHref = "/presenter/abstrak/create/$eventId";     $ctaText = "Upload Revisi Abstrak";
                    } elseif ($absSt==='diterima') {
                      if ($fpOk && $fpSt==='NONE')        { $ctaHref="/presenter/fullpaper/create/$eventId";  $ctaText="Upload Full Paper"; }
                      elseif ($fpSt==='UPLOADED')         { $ctaHref="/presenter/fullpaper/detail/$eventId";  $ctaText="Cek Status Full Paper"; }
                      elseif ($fpSt==='REVISION')         { $ctaHref="/presenter/fullpaper/create/$eventId";  $ctaText="Upload Revisi Full Paper"; }
                      elseif ($fpSt==='REJECTED')         { $ctaHref="/presenter/fullpaper/create/$eventId";  $ctaText="Upload Ulang Full Paper"; }
                      elseif ($fpSt==='ACCEPTED')         { $ctaHref="/presenter/pembayaran/instruction/$eventId"; $ctaText="Lanjut ke Pembayaran"; }
                    }
                  ?>
                    <div class="progress-card p-3 mb-3">
                      <div class="fw-semibold mb-2 text-truncate"><?= esc($title) ?></div>

                      <div class="stepper my-2">
                        <?php
                          $S = $p['steps'];
                          $nodes = [
                            ['Kontributor','bi-people',$S['kontributor']],
                            ['Abstrak','bi-file-earmark',$S['abstrak']],
                            ['Full Paper','bi-file-earmark-text', ($S['fullpaper'] ?? 'disabled')],
                            ['Bayar','bi-credit-card',$S['bayar']],
                            ['Selesai','bi-star-fill',$S['verifikasi']],
                          ];
                        ?>
                        <?php foreach ($nodes as $i=>$n): ?>
                          <?php if ($i>0): ?><div class="bar <?= in_array($nodes[$i-1][2],['done','current']) && in_array($n[2],['done','current']) ? 'on':'' ?>"></div><?php endif; ?>
                          <div class="step <?= esc($n[2]) ?>">
                            <span class="dot"><i class="bi <?= esc($n[1]) ?>"></i></span>
                            <div class="label"><?= esc($n[0]) ?></div>
                          </div>
                        <?php endforeach; ?>
                      </div>

                      <div class="d-flex flex-wrap gap-2 align-items-center">
                        <span class="badge bg-light text-muted">Abstrak: <strong class="ms-1"><?= esc(ucfirst($absInfo)) ?></strong></span>
                        <span class="badge bg-light text-muted">Full paper: <strong class="ms-1"><?= esc($fpInfo) ?></strong></span>
                        <a href="<?= esc($ctaHref) ?>" class="btn btn-success ms-auto">
                          <i class="bi bi-play-fill me-1"></i><?= esc($ctaText) ?>
                        </a>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

      <!-- KALENDER 3/4 -->
      <div class="row g-3 mb-4" id="kalender">
        <div class="col-12 col-xl-9">
          <div class="card shadow-soft card-glass-plain p-3">
            <div class="d-flex justify-content-between align-items-center mb-2">
              <div class="fw-semibold"><i class="bi bi-calendar3 me-1"></i>Kalender</div>
              <div class="text-muted small"><?= date('F Y') ?></div>
            </div>

            <div class="calendar-legend mb-2">
              <span class="leg leg-today"><i class="bi bi-dot"></i> Hari ini</span>
              <span class="leg leg-has"><i class="bi bi-circle-fill"></i> Ada event diikuti</span>
              <span class="leg leg-upcoming"><i class="bi bi-dot"></i> Mendatang</span>
            </div>

            <div class="calendar-grid">
              <div class="dow">Sen</div><div class="dow">Sel</div><div class="dow">Rab</div><div class="dow">Kam</div><div class="dow">Jum</div><div class="dow">Sab</div><div class="dow">Min</div>
              <?php for ($i=1; $i<$startDow; $i++): ?>
                <div class="cell empty"></div>
              <?php endfor; ?>

              <?php for ($d=1; $d<=$days; $d++):
                $isToday  = ($d === $today);
                $hasEvent = isset($monthEventsByDay[$d]) && !empty($monthEventsByDay[$d]);
                $dateTs   = mktime(0,0,0,$m,$d,$y);
                $isFuture = $dateTs >= strtotime('today');
                $cls = 'cell';
                if ($isToday)   $cls .= ' today';
                if ($hasEvent)  $cls .= ' has-event' . ($isFuture ? ' upcoming' : ' past');
                $titleAttr = '';
                if ($hasEvent) {
                  $names = array_map(fn($x) => (string)($x['title'] ?? '-'), $monthEventsByDay[$d]);
                  $titleAttr = 'title="'.esc(implode(' • ', $names)).'"';
                }
                $count = $hasEvent ? count($monthEventsByDay[$d]) : 0;
              ?>
                <div class="<?= $cls ?>" data-day="<?= $d ?>" <?= $titleAttr ?> >
                  <span class="num"><?= $d ?></span>
                  <?php if ($hasEvent): ?>
                    <span class="dot"></span>
                    <?php if ($count > 1): ?><span class="count">+<?= $count-1 ?></span><?php endif; ?>
                  <?php endif; ?>
                </div>
              <?php endfor; ?>
            </div>

            <?php if ($todayHasEvent): ?>
              <div class="mt-3 d-flex flex-wrap gap-2">
                <?php foreach ($todaySchedule as $s): ?>
                  <a class="btn btn-primary" href="<?= esc($s['link']) ?>">
                    <i class="bi bi-qr-code me-1"></i>Absen: <?= esc($s['title']) ?>
                  </a>
                <?php endforeach; ?>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <div class="col-12 col-xl-3">
          <div class="card shadow-soft card-glass-plain p-3 h-100">
            <div class="fw-semibold mb-2"><i class="bi bi-lightbulb me-1"></i>Tips</div>
            <div class="text-muted small">Pastikan data kontributor lengkap sebelum mengirim abstrak/full paper.</div>
          </div>
        </div>
      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  // Data event kalender (untuk popup)
  const MONTH_EVENTS = <?= json_encode($monthEventsByDay ?? [], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) ?>;

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.calendar-grid .cell.has-event').forEach(cell => {
      cell.addEventListener('click', () => {
        const day = cell.getAttribute('data-day');
        const items = MONTH_EVENTS[day] || [];
        if (!items.length) return;

        let html = '<div class="text-start">';
        items.forEach((it, idx) => {
          const t = it.title || '-';
          const w = it.where || '';
          const tm= it.time ? ` • ${it.time}` : '';
          const lk= it.link || '#';
          html += `<div class="mb-2"><strong>${idx+1}. ${t}</strong><br><small>${w}${tm}</small><br><a href="${lk}" class="btn btn-sm btn-primary mt-1"><i class="bi bi-info-circle me-1"></i>Detail / Absen</a></div>`;
        });
        html += '</div>';

        Swal.fire({
          icon: 'info',
          title: `Event tanggal ${day} <?= date('M Y') ?>`,
          html,
          width: 520
        });
      });
    });
  });
</script>

<style>
/* ===== Presenter Blue UI (seragam dengan halaman lain) ===== */
:root{
  --blue-50:#eff6ff; --blue-100:#dbeafe; --blue-200:#bfdbfe;
  --blue-300:#93c5fd; --blue-400:#60a5fa; --blue-500:#3b82f6;
  --blue-600:#2563eb; --blue-700:#1d4ed8; --blue-800:#1e40af; --blue-900:#1e3a8a;

  --side-pad:1rem; --gutter:1rem;
  --panel-shadow:0 16px 40px rgba(2,8,23,.10);
}
body{
  font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  font-size:14.6px; line-height:1.5;
  background: radial-gradient(1200px 420px at 10% -20%, var(--blue-200) 0, #fff 45%) fixed;
}

/* Layout */
.page-wrap-blue{ background:linear-gradient(180deg,var(--blue-50),#fff 40%); min-height:100vh; padding-top:72px; }
.container-xxl{ max-width:1400px; padding-left:var(--side-pad)!important; padding-right:var(--side-pad)!important; }
.row.g-3{ --bs-gutter-x:var(--gutter); --bs-gutter-y:var(--gutter); }

/* Hero */
.hero-blue{
  background:radial-gradient(1200px 400px at 10% -20%,var(--blue-600) 0,var(--blue-700) 40%,var(--blue-800) 100%) !important;
  color:#fff !important; border-radius:16px;
  border:1px solid rgba(255,255,255,.15);
  box-shadow:0 12px 28px rgba(30,64,175,.10);
}
.hero-title{ font-weight:800; letter-spacing:.25px; }
.text-white-75{ color:rgba(255,255,255,.85)!important; }

/* Cards */
.card-glass-plain{
  backdrop-filter:blur(6px);
  background:#fff;
  border-radius:14px;
  border:1px solid rgba(30,64,175,.10);
}
.shadow-soft{ box-shadow:0 10px 24px rgba(30,64,175,.08); }

/* KPI: tidak center (rata kiri & atas) */
.kpi-card.kpi-thick{ padding: 1.15rem 1.25rem !important; min-height: 120px; }
.kpi-row{ display:flex; gap:12px; align-items:flex-start; }
.kpi-metrics{ display:flex; flex-direction:column; align-items:flex-start; }
.kpi-label{ margin-top:2px; }
.kpi-value{ font-size: 2rem; font-weight: 800; line-height: 1.1; }

/* Ikon KPI */
.stat-icon{
  width:48px; height:48px; border-radius:12px;
  display:grid; place-items:center; font-size:20px; color:#1e3a8a;
}
.bg-blue-soft{ background:var(--blue-200)!important; }
.bg-success-subtle{ background:#d1fae5!important; color:#065f46!important; }

/* Empty state */
.empty-state{ text-align:center; padding:1.25rem; color:#64748b; background:#fff; border-radius:12px; border:1px dashed rgba(2,8,23,.12); }
.empty-icon{
  width:52px; height:52px; border-radius:12px; display:inline-flex; align-items:center; justify-content:center;
  font-size:1.35rem; margin-bottom:.5rem;
}
.empty-text{ font-weight:600; color:#334155; }

/* Activity */
.activity-list.compact .list-group-item{ border:0; border-bottom:1px solid rgba(2,8,23,.06); padding:.55rem .6rem; }
.activity-dot{ width:30px; height:30px; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#fff; flex:0 0 30px; font-size:14px; }

/* Scroll area (batasi tinggi) */
.scroll-area{ overflow:auto; padding-right:4px; }
.activity-scroll{ max-height: 280px; }       /* ~3–4 item terlihat */
.progress-scroll{ max-height: 480px; }       /* ~2–3 kartu terlihat */
@media (max-width: 991.98px){
  .activity-scroll{ max-height: 220px; }
  .progress-scroll{ max-height: 420px; }
}

/* Slim scrollbar (webkit) */
.scroll-area::-webkit-scrollbar{ width:8px; height:8px; }
.scroll-area::-webkit-scrollbar-thumb{ background:#cbd5e1; border-radius:8px; }
.scroll-area::-webkit-scrollbar-track{ background:transparent; }

/* Stepper */
.progress-card{ background:#fff; border:1px solid rgba(2,8,23,.06); border-radius:14px; }
.stepper{ display:grid; grid-template-columns: repeat(9, 1fr); align-items:center; gap:8px; }
.stepper .step{ text-align:center; }
.stepper .dot{ width:40px; height:40px; border-radius:50%; display:grid; place-items:center; border:2px solid #cbd5e1; color:#64748b; background:#fff; margin:0 auto; transition:.2s; }
.stepper .label{ margin-top:6px; font-size:.8rem; color:#111827; }
.stepper .bar{ height:4px; background:#cbd5e1; border-radius:4px; transition:.2s; }
.stepper .bar.on{ background:#10b981; }
.stepper .step.done    .dot{ background:rgba(16,185,129,.15); border-color:#10b981; color:#10b981; }
.stepper .step.current .dot{ background:rgba(37,99,235,.1);  border-color:#2563eb; color:#2563eb; }
.stepper .step.todo    .dot{ background:#fff;               border-color:#cbd5e1; color:#94a3b8; }
.stepper .step.disabled .dot{ background:#f8fafc; border-color:#e2e8f0; color:#cbd5e1; }
.badge.bg-light{ background:#f8fafc!important; }

/* Kalender */
.calendar-legend .leg{ display:inline-flex; align-items:center; gap:6px; font-size:.8rem; color:#64748b; margin-right:10px; }
.calendar-legend .leg i{ font-size:12px; }
.calendar-legend .leg-today i{ color:#2563eb; }
.calendar-legend .leg-has i{ color:#10b981; }
.calendar-legend .leg-upcoming i{ color:#f59e0b; }

.calendar-grid{
  display:grid; grid-template-columns: repeat(7, minmax(0,1fr)); gap:6px;
  border-top:1px dashed rgba(2,8,23,.08); padding-top:.6rem;
}
.calendar-grid .dow{ font-size:.75rem; color:#64748b; text-align:center; }
.calendar-grid .cell{
  position:relative;
  background:#fff; border:1px solid rgba(2,8,23,.06); border-radius:10px; height:42px;
  display:flex; align-items:center; justify-content:center; font-weight:700; color:#334155; cursor:default;
  transition:.12s;
}
.calendar-grid .cell.empty{ background:transparent; border:0; }
.calendar-grid .cell.today{ outline:2px solid #2563eb; color:#2563eb; background:#eff6ff; }
.calendar-grid .cell.has-event{ border-color:#10b98166; background:#f0fdf4; }
.calendar-grid .cell.has-event.upcoming{ box-shadow: inset 0 0 0 2px #f59e0b55; }
.calendar-grid .cell.has-event:hover{ transform:translateY(-1px); filter:brightness(1.02); cursor:pointer; }
.calendar-grid .cell .num{ position:relative; z-index:1; }
.calendar-grid .cell .dot{
  position:absolute; width:8px; height:8px; border-radius:50%; bottom:6px; left:6px; background:#10b981;
}
.calendar-grid .cell.upcoming .dot{ background:#f59e0b; }
.calendar-grid .cell.today .dot{ background:#2563eb; }
.calendar-grid .cell .count{
  position:absolute; right:6px; bottom:4px; font-size:.65rem; background:#065f4699; color:#fff; padding:0 5px; border-radius:6px;
}

/* Utility */
.xsmall{ font-size:.78rem; }
.btn-xs{ padding:.2rem .45rem; font-size:.72rem; line-height:1; border-radius:6px; }

/* Responsive */
@media (max-width:575.98px){
  .container-xxl{ padding-left:1rem!important; padding-right:1rem!important; }
  .hero-blue{ border-radius:14px; padding:1.25rem!important; margin-bottom:1rem!important; }
  .hero-title{ font-size:1.25rem; }
}
@media (max-width: 992px){
  .calendar-grid .cell{ height:36px; }
}
</style>
