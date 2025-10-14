<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<?php
$stats             = $stats             ?? ['total_events'=>0,'total_loa'=>0];
$todaySchedule     = $todaySchedule     ?? [];
$progressEvents    = $progressEvents    ?? [];
$activities        = $activities        ?? [];
$monthEvents       = $monthEvents       ?? []; // key: 'YYYY-MM-DD' => array of items
$todayHasEvent     = !empty($todaySchedule);

/* ===== Kalender (bulan berjalan) ===== */
$y       = (int)date('Y');
$m       = (int)date('n');
$first   = mktime(0,0,0,$m,1,$y);
$startDow= (int)date('N',$first); // 1..7 (Sen..Min)
$days    = (int)date('t',$first);
$today   = (int)date('j');

$monthEventsByDay = [];
foreach ($monthEvents as $ymd => $items) {
  $d = (int)date('j', strtotime($ymd));
  $monthEventsByDay[$d] = $items;
}

/* ===== Helpers pill/status (seragam) ===== */
$absToPill = function($s){
  $s = strtolower((string)$s);
  return match (true) {
    $s === 'diterima'                         => 'pill-success',
    $s === 'ditolak'                          => 'pill-danger',
    in_array($s,['revisi','menunggu','sedang_direview','pending'], true) => 'pill-warn',
    default                                   => 'pill-muted',
  };
};
$fpToPill = function($s){
  $s = strtoupper((string)$s);
  return match ($s) {
    'ACCEPTED' => 'pill-success',
    'REJECTED' => 'pill-danger',
    'REVISION','UPLOADED' => 'pill-warn',
    'NONE' => 'pill-muted',
    default => 'pill-muted',
  };
};
$maxActivities = 4; // << tampilkan maksimal 4 item
?>

<div id="content">
  <main class="flex-fill page-wrap-blue">
    <div class="container-xxl px-3 px-md-4 py-4">

      <!-- HERO -->
      <div class="card-hero mb-4">
        <div class="hero-body">
          <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
              <h3 class="hero-title mb-1"><i class="bi bi-speedometer2 me-2"></i>Dashboard Presenter</h3>
              <div class="text-white-70 small">Ringkasan progres & aktivitas terbaru.</div>
            </div>
            <div class="hero-tools flex-grow-1" style="max-width:620px;">
              <div class="input-group input-group-lg hero-search">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input id="dashSearch" type="text" class="form-control" placeholder="Cari event / status…">
                <button id="dashClear" type="button" class="btn btn-light d-none">
                  <i class="bi bi-x-circle"></i>
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="hero-tabs">
          <ul class="nav nav-pills" id="dashTabs" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="tab-overview" data-bs-toggle="pill" data-bs-target="#pane-overview" type="button" role="tab">
                Overview
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="tab-calendar" data-bs-toggle="pill" data-bs-target="#pane-calendar" type="button" role="tab">
                Kalender
              </button>
            </li>
          </ul>
        </div>
      </div>

      <div class="tab-content">
        <!-- ===================== OVERVIEW ===================== -->
        <div class="tab-pane fade show active" id="pane-overview" role="tabpanel" aria-labelledby="tab-overview">
          <!-- KPI + ABSENSI -->
          <div class="row g-3 mb-3 align-items-stretch">
            <!-- KPI kiri -->
            <div class="col-12 col-lg-3">
              <div class="d-grid gap-3">
                <div class="fp-card p-3">
                  <div class="d-flex align-items-start gap-3">
                    <div class="status-pill pill-info d-inline-flex" style="min-width:44px;justify-content:center;">
                      <i class="bi bi-calendar2-event"></i>
                    </div>
                    <div>
                      <div class="text-muted small">Event Diikuti</div>
                      <div class="stat-number"><?= (int)($stats['total_events'] ?? 0) ?></div>
                    </div>
                  </div>
                </div>

                <div class="fp-card p-3">
                  <div class="d-flex align-items-start gap-3">
                    <div class="status-pill pill-success d-inline-flex" style="min-width:44px;justify-content:center;">
                      <i class="bi bi-patch-check"></i>
                    </div>
                    <div>
                      <div class="text-muted small">Total LOA</div>
                      <div class="stat-number"><?= (int)($stats['total_loa'] ?? 0) ?></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Absensi kanan -->
            <div class="col-12 col-lg-9">
              <div class="fp-card p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <div class="fw-semibold"><i class="bi bi-qr-code me-1"></i>Absensi Hari Ini</div>
                  <span class="badge bg-light text-secondary"><?= $todayHasEvent ? 'Tersedia' : 'Tidak ada' ?></span>
                </div>

                <?php if (!$todayHasEvent): ?>
                  <div class="empty-hint text-center">
                    <i class="bi bi-calendar-x me-1"></i>Tidak ada event yang diselenggarakan hari ini.
                  </div>
                <?php else: ?>
                  <ul class="list-unstyled mb-0">
                    <?php $limit=3; $shown=0; foreach ($todaySchedule as $s): if ($shown++ >= $limit) break; ?>
                      <li class="mb-2 js-row" data-search="<?= esc(strtolower(($s['title'] ?? '').' '.($s['where'] ?? '').' '.($s['start'] ?? ''))) ?>">
                        <a href="<?= esc($s['link']) ?>" class="activity-item">
                          <div class="fw-semibold text-truncate"><?= esc($s['title']) ?></div>
                          <div class="text-muted small"><?= esc($s['start'] ?: '-') ?> · <?= esc($s['where'] ?: '-') ?></div>
                        </a>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- AKTIVITAS & PROGRESS -->
          <div class="row g-3 mb-4">
            <!-- AKTIVITAS TERBARU (rapi, klik seluruh baris, max 4 item) -->
            <div class="col-12 col-lg-5">
              <div class="fp-card h-100 p-0">
                <div class="p-3 border-bottom small text-muted"><i class="bi bi-bell me-1"></i>Aktivitas Terbaru</div>
                <div class="p-2">
                  <?php if (empty($activities)): ?>
                    <div class="empty-hint text-center"><i class="bi bi-inboxes me-1"></i>Belum ada aktivitas.</div>
                  <?php else: ?>
                    <ul class="list-unstyled mb-0">
                      <?php
                        $i=0;
                        foreach ($activities as $a):
                          if (++$i > $maxActivities) break;
                          $badge = strtolower($a['badge'] ?? '');
                          $pill  = $badge==='success' ? 'pill-success' : ($badge==='warning' ? 'pill-warn' : ($badge==='danger' ? 'pill-danger' : 'pill-muted'));
                          $link  = !empty($a['link']) ? (string)$a['link'] : 'javascript:void(0)';
                      ?>
                        <li class="js-row" data-search="<?= esc(strtolower(($a['title'] ?? '').' '.($a['desc'] ?? ''))) ?>">
                          <a href="<?= esc($link) ?>" class="activity-item">
                            <span class="status-pill <?= esc($pill) ?> me-2 icon-pill">
                              <i class="bi <?= esc($a['icon'] ?? 'bi-dot') ?>"></i>
                            </span>
                            <div class="flex-fill">
                              <div class="d-flex justify-content-between align-items-center">
                                <div class="fw-semibold small text-truncate"><?= esc($a['title'] ?? '-') ?></div>
                                <small class="text-muted"><?= !empty($a['time']) ? date('d M Y H:i', (int)$a['time']) : '' ?></small>
                              </div>
                              <?php if (!empty($a['desc'])): ?>
                                <div class="text-muted xsmall mt-1 text-truncate-2"><?= esc($a['desc']) ?></div>
                              <?php endif; ?>
                            </div>
                          </a>
                        </li>
                      <?php endforeach; ?>
                    </ul>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <!-- PROGRESS EVENT -->
            <div class="col-12 col-lg-7" id="progress">
              <div class="fp-card h-100 p-0">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                  <div class="small text-muted"><i class="bi bi-flag me-1"></i>Progress Event</div>
                  <div class="small text-muted">Flow: Kontributor → Abstrak → Full Paper → Bayar → Selesai</div>
                </div>

                <div class="p-3">
                  <?php if (empty($progressEvents)): ?>
                    <div class="empty-hint text-center"><i class="bi bi-check2-circle me-1"></i>Tidak ada progres. Semua event sudah selesai.</div>
                  <?php else: ?>
                    <div class="scroll-area" style="max-height: 520px;">
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

                        $absPill = $absToPill($absSt);
                        $fpPill  = $fpToPill($fpSt);

                        $blockedPay = !($absSt === 'diterima' && $fpSt === 'ACCEPTED');

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
                        <div class="fp-card mb-3 js-row"
                             data-search="<?= esc(strtolower($title.' '.$absInfo.' '.$fpInfo)) ?>">
                          <div class="fp-head">
                            <h6 class="mb-0 fp-title text-truncate"><?= esc($title) ?></h6>
                            <span class="status-pill <?= $absPill ?>">Abstrak: <?= esc($absInfo) ?></span>
                          </div>

                          <div class="chip-row mb-2">
                            <span class="status-pill <?= $fpPill ?>">Full Paper: <?= esc($fpInfo) ?></span>
                          </div>

                          <ul class="meta-list">
                            <?php
                              $S = $p['steps'] ?? [];
                              $nodes = [
                                ['Kontributor',$S['kontributor'] ?? ''],
                                ['Abstrak',$S['abstrak'] ?? ''],
                                ['Full Paper', $S['fullpaper'] ?? 'disabled'],
                                ['Bayar', ($S['bayar'] ?? '').($blockedPay ? ' blocked' : '')],
                                ['Selesai',$S['verifikasi'] ?? '']
                              ];
                            ?>
                            <?php foreach ($nodes as $n):
                              $lbl=$n[0]; $st=$n[1]; $dot='pill-muted';
                              if (str_contains($st,'done'))        $dot='pill-success';
                              elseif (str_contains($st,'current'))  $dot='pill-info';
                              elseif (str_contains($st,'disabled')) $dot='pill-muted';
                              elseif (str_contains($st,'blocked'))  $dot='pill-warn';
                            ?>
                              <li>
                                <span><?= esc($lbl) ?></span>
                                <span class="status-pill <?= $dot ?>"><?= str_contains($st,'blocked') ? 'Terkunci' : (str_contains($st,'done')?'Selesai':(str_contains($st,'current')?'Berjalan':(str_contains($st,'disabled')?'Nonaktif':'Belum'))) ?></span>
                              </li>
                            <?php endforeach; ?>
                          </ul>

                          <div class="fp-foot">
                            <?php if ($blockedPay && $ctaText === 'Lanjut ke Pembayaran'): ?>
                              <button class="btn btn-success flex-fill" disabled title="Menunggu Abstrak diterima & Full Paper diterima">
                                <i class="bi bi-lock-fill me-1"></i>Lanjut ke Pembayaran
                              </button>
                            <?php else: ?>
                              <a href="<?= esc($ctaHref) ?>" class="btn btn-primary flex-fill">
                                <i class="bi bi-play-fill me-1"></i><?= esc($ctaText) ?>
                              </a>
                            <?php endif; ?>
                          </div>

                          <?php if ($blockedPay): ?>
                            <div class="mini-hint mt-2"><i class="bi bi-exclamation-triangle me-1"></i>
                              Pembayaran belum tersedia. Selesaikan tahap sebelumnya terlebih dahulu.
                            </div>
                          <?php endif; ?>
                        </div>
                      <?php endforeach; ?>
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- ===================== KALENDER ===================== -->
        <div class="tab-pane fade" id="pane-calendar" role="tabpanel" aria-labelledby="tab-calendar">
          <div class="row g-3 mb-4">
            <div class="col-12 col-xl-9">
              <div class="fp-card p-3">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <div class="fw-semibold"><i class="bi bi-calendar3 me-1"></i>Kalender</div>
                  <div class="text-muted small"><?= date('F Y') ?></div>
                </div>

                <div class="calendar-legend mb-2">
                  <span class="chip"><i class="bi bi-dot me-1"></i>Hari ini</span>
                  <span class="chip alt"><i class="bi bi-circle-fill me-1"></i>Ada event diikuti</span>
                </div>

                <div class="calendar-grid">
                  <div class="dow">Sen</div><div class="dow">Sel</div><div class="dow">Rab</div>
                  <div class="dow">Kam</div><div class="dow">Jum</div><div class="dow">Sab</div><div class="dow">Min</div>
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
                    <div class="<?= $cls ?>" data-day="<?= $d ?>" <?= $titleAttr ?>>
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
                      <a class="btn btn-primary btn-sm" href="<?= esc($s['link']) ?>">
                        <i class="bi bi-qr-code me-1"></i>Absen: <?= esc($s['title']) ?>
                      </a>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
              </div>
            </div>

            <div class="col-12 col-xl-3">
              <div class="fp-card p-3 h-100">
                <div class="fw-semibold mb-2"><i class="bi bi-lightbulb me-1"></i>Tips</div>
                <div class="text-muted small">Pastikan data kontributor lengkap sebelum mengirim abstrak/full paper.</div>
              </div>
            </div>
          </div>
        </div>
      </div><!-- /tab-content -->
    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<!-- ====== CSS (konsisten + diperlebar) ====== -->
<style>
:root{
  --blue-50:#eff6ff; --blue-100:#dbeafe; --blue-200:#bfdbfe; --blue-300:#93c5fd;
  --blue-400:#60a5fa; --blue-500:#3b82f6; --blue-600:#2563eb; --blue-700:#1d4ed8; --blue-800:#1e40af; --blue-900:#1e3a8a;
  --muted:#6b7280; --ink:#0f172a; --radius:16px;
  --card-min-h: 220px;
  --side-pad: clamp(1rem, 2.3vw, 2.2rem);
}

/* >>>> FIX: lebar container (samakan dgn halaman lain) <<<< */
.container-xxl{
  max-width: min(100%, 1560px); /* sebelumnya salah tulis: max_width */
  padding-left: var(--side-pad) !important;
  padding-right: var(--side-pad) !important;
  margin-inline: auto;
}

body{ font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size:15.5px; line-height:1.6; color:var(--ink); }
.page-wrap-blue{
  min-height:100vh; padding-top:72px;
  background:
    radial-gradient(1000px 380px at 10% -10%, rgba(59,130,246,.16), rgba(59,130,246,0) 60%),
    radial-gradient(1000px 380px at 90% 110%, rgba(59,130,246,.12), rgba(59,130,246,0) 70%),
    linear-gradient(180deg, var(--blue-50), #fff 40%);
}

/* HERO */
.card-hero{ border:0; border-radius:var(--radius); overflow:hidden; box-shadow:0 12px 28px rgba(30,64,175,.18); }
.card-hero .hero-body{ background:linear-gradient(135deg,var(--blue-700),var(--blue-800)); color:#fff; padding:1.8rem 1.2rem; min-height:176px; }
.hero-title{ font-weight:800; }
.text-white-70{ color:rgba(255,255,255,.85)!important; }
.hero-tools .input-group .input-group-text{ background:#fff; border:0; }
.hero-tools .form-control{ border:0; }
.hero-tools .btn{ border:0; }
.hero-search{ border-radius:12px; overflow:hidden; }
.hero-tabs{ background:#fff; padding:.6rem .8rem; border:1px solid rgba(30,64,175,.18); border-top:0; }
.hero-tabs .nav-link{ font-weight:700; border-radius:999px; padding:.45rem 1rem; }
.hero-tabs .nav-link.active{ background:var(--blue-600); color:#fff; }

/* Card seragam */
.fp-card{
  border:1px solid rgba(30,64,175,.12); border-radius:14px; background:#fff; box-shadow:0 10px 22px rgba(30,64,175,.10);
  padding:0.9rem; display:flex; flex-direction:column;
  transition:transform .18s ease, box-shadow .18s ease, border-color .18s ease;
}
.fp-card:hover{ transform: translateY(-2px); box-shadow:0 16px 28px rgba(30,64,175,.16); border-color: rgba(30,64,175,.22); }
.fp-head{ display:flex; align-items:center; justify-content:space-between; gap:.75rem; margin-bottom:.35rem; }
.fp-title{ line-height:1.35; max-width:72%; color:var(--blue-900); }

/* Pills & chips */
.status-pill{ font-weight:800; font-size:.82rem; padding:.28rem .6rem; border-radius:999px; border:1px solid rgba(0,0,0,.06); white-space:nowrap; }
.pill-info{ background:#e0f2fe; color:#075985; }
.pill-warn{ background:#fef3c7; color:#92400e; }
.pill-success{ background:#d1fae5; color:#065f46; }
.pill-danger{ background:#fee2e2; color:#991b1b; }
.pill-muted{ background:#f3f4f6; color:#374151; }
.chip-row{ display:flex; flex-wrap:wrap; gap:.5rem; }
.chip{ display:inline-flex; align-items:center; padding:.26rem .55rem; font-size:.86rem; border-radius:999px; background:#eef3ff; color:#1e3a8a; border:1px solid rgba(30,64,175,.15); font-weight:700; }
.chip.alt{ background:#f1f5ff; color:#244aa4; }

/* Meta list & hints */
.meta-list{ list-style:none; padding-left:0; margin:.4rem 0 .2rem 0; }
.meta-list li{ display:flex; align-items:center; justify-content:space-between; gap:.75rem; padding:.36rem 0; }
.meta-list li span{ color:var(--muted); }
.mini-hint{ color:#3a2a6a; background:#f6f9ff; border:1px dashed rgba(30,64,175,.18); border-radius:10px; padding:.5rem .6rem; font-weight:600; font-size:.86rem; }

/* Activity list item (klikable & rapi) */
.activity-item{
  display:flex; gap:.6rem; align-items:flex-start; text-decoration:none; color:inherit;
  border:1px solid rgba(2,6,23,.06); border-radius:12px; padding:.65rem .7rem; margin-bottom:.5rem;
  transition: background .15s ease, box-shadow .15s ease, border-color .15s ease;
}
.activity-item:hover{ background:#f8fbff; box-shadow:0 6px 16px rgba(30,64,175,.10); border-color:rgba(30,64,175,.18); }
.icon-pill{ min-width:34px; text-align:center; }

/* Footer action area */
.fp-foot{ display:flex; gap:.6rem; margin-top:auto; padding-top:.6rem; border-top:1px dashed rgba(30,64,175,.16); }
.btn{ font-weight:800; border-radius:10px; font-size:.98rem; padding:.55rem 1.0rem; }
.btn-primary{ background:var(--blue-600); border-color:var(--blue-600); box-shadow:0 4px 12px rgba(37,99,235,.2); }

/* Kalender */
.calendar-legend{ display:flex; gap:.6rem; align-items:center; }
.calendar-legend .chip{ padding:.22rem .6rem; border-radius:999px; font-weight:700; font-size:.8rem; background:#eef3ff; color:#223b94; border:1px solid rgba(34,59,148,.12); }
.calendar-legend .chip.alt{ background:#e8fbf2; color:#0b6640; border-color:rgba(11,102,64,.15); }
.calendar-grid{
  display:grid; grid-template-columns: repeat(7, minmax(0,1fr)); gap:6px;
  border-top:1px dashed rgba(2,6,23,.08); padding-top:.6rem;
}
.calendar-grid .dow{ font-size:.78rem; color:#64748b; text-align:center; font-weight:700; }
.calendar-grid .cell{
  position:relative; height:42px; background:#fff; border:1px solid rgba(2,6,23,.06); border-radius:10px;
  display:flex; align-items:center; justify-content:center; font-weight:700; color:#334155;
}
.calendar-grid .cell.empty{ background:transparent; border:0; }
.calendar-grid .cell.today{ outline:2px solid #2563eb; color:#2563eb; background:#eff6ff; }
.calendar-grid .cell.has-event{ border-color:#10b98166; background:#f0fdf4; }
.calendar-grid .cell.has-event.upcoming{ box-shadow: inset 0 0 0 2px #f59e0b55; }
.calendar-grid .cell .dot{ position:absolute; width:8px; height:8px; border-radius:50%; bottom:6px; left:6px; background:#10b981; }
.calendar-grid .cell.upcoming .dot{ background:#f59e0b; }
.calendar-grid .cell.today .dot{ background:#2563eb; }
.calendar-grid .cell .count{ position:absolute; right:6px; bottom:4px; font-size:.65rem; background:#065f4699; color:#fff; padding:0 6px; border-radius:6px; }

/* Responsive */
@media (max-width:767.98px){
  .hero-tools{ width:100%; max-width:none; }
  .card-hero .hero-body{ padding:1.4rem 1rem; min-height:165px; }
  .fp-title{ max-width:68%; }
}
@media (max-width:575.98px){
  .container-xxl{ padding-left: calc(var(--side-pad) - .25rem) !important; padding-right: calc(var(--side-pad) - .25rem) !important; }
}

/* util */
.text-truncate-2{
  overflow:hidden; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical;
}
</style>

<!-- ====== JS: filter ringan ====== -->
<script>
(function(){
  const q = document.getElementById('dashSearch');
  const clr = document.getElementById('dashClear');

  function apply(){
    const query = (q?.value || '').toLowerCase().trim();
    document.querySelectorAll('.js-row').forEach(el=>{
      const hay = (el.dataset.search || el.textContent || '').toLowerCase();
      el.style.display = (!query || hay.includes(query)) ? '' : 'none';
    });
    if (clr) clr.classList.toggle('d-none', !query);
  }
  q?.addEventListener('input', apply);
  clr?.addEventListener('click', ()=>{ q.value=''; apply(); q.focus(); });

  apply();
})();
</script>
