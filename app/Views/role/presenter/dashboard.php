<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<?php
$stats             = $stats             ?? ['total_events'=>0,'total_loa'=>0];
$todaySchedule     = $todaySchedule     ?? [];
$progressEvents    = $progressEvents    ?? [];
$activities        = $activities        ?? [];
$monthEvents       = $monthEvents       ?? [];
$todayHasEvent     = !empty($todaySchedule);

/* ===== Nama & sapaan ===== */
$rawName = '';
if (function_exists('user') && user()) {
  $rawName = (string) (user()->name ?? user()->username ?? user()->email ?? '');
}
if (!$rawName) {
  $rawName = (string) (session('nama_lengkap') ?? session('name') ?? session('username') ?? 'Presenter');
}
$firstName = trim(explode(' ', $rawName)[0]) ?: 'Presenter';

$hour   = (int)date('G');
$waktu  = ($hour>=5 && $hour<11) ? 'Pagi' : (($hour>=11 && $hour<15) ? 'Siang' : (($hour>=15 && $hour<18) ? 'Sore' : 'Malam'));

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
$mapAbs = function($s){
  $s = strtolower((string)$s);
  return match(true){
    $s === 'diterima' => 'done',
    $s === 'ditolak'  => 'danger',
    in_array($s, ['revisi','menunggu','sedang_direview','pending'], true) => 'warn',
    default           => 'muted',
  };
};
$mapFp = function($s){
  $s = strtoupper((string)$s);
  return match($s){
    'ACCEPTED'  => 'done',
    'REJECTED'  => 'danger',
    'REVISION','UPLOADED' => 'warn',
    'NONE'      => 'muted',
    default     => 'muted',
  };
};
?>

<div id="content">
  <main class="flex-fill page-wrap-blue">
    <div class="container-xxl px-3 px-md-4 py-4">

      <!-- HERO -->
      <div class="card-hero mb-4">
        <div class="hero-body">
          <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
              <!-- GANTI: sambutan user -->
              <h3 class="hero-title mb-1">Halo, <?= esc($firstName) ?> 👋</h3>
              <div class="text-white-70 small">Selamat <?= esc($waktu) ?> — semoga produktif! Ini ringkasan progres & aktivitas kamu.</div>
            </div>
          </div>
        </div>

        <div class="hero-tabs">
          <ul class="nav nav-pills" id="dashTabs" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="tab-overview" data-bs-toggle="pill" data-bs-target="#pane-overview" type="button" role="tab">
                Utama
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
            <div class="col-12 col-lg-3">
              <div class="d-grid gap-3">
                <!-- KPI 1 -->
                <div class="fp-card p-3">
                  <div class="d-flex align-items-center gap-3">
                    <div class="kpi-icon kpi-blue">
                      <i class="bi bi-calendar2-event"></i>
                    </div>
                    <div>
                      <div class="kpi-label">Event Diikuti</div>
                      <div class="stat-number"><?= (int)($stats['total_events'] ?? 0) ?></div>
                    </div>
                  </div>
                </div>
                <!-- KPI 2 -->
                <div class="fp-card p-3">
                  <div class="d-flex align-items-center gap-3">
                    <div class="kpi-icon kpi-green">
                      <i class="bi bi-patch-check"></i>
                    </div>
                    <div>
                      <div class="kpi-label">Total LOA</div>
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
                  <div class="empty-state">
                    <div class="empty-ico">
                      <i class="bi bi-calendar-x"></i>
                    </div>
                    <div class="empty-title">Tidak ada event yang diselenggarakan hari ini</div>
                    <div class="empty-desc">Cek kalender untuk melihat event mendatang atau detail event yang sudah kamu ikuti.</div>
                    <div class="mt-2">
                      <button class="btn btn-primary btn-sm" type="button" id="btnGoCalendar">
                        <i class="bi bi-calendar3 me-1"></i>Lihat Kalender
                      </button>
                    </div>
                  </div>
                <?php else: ?>
                  <ul class="list-unstyled mb-0">
                    <?php $limit=3; $shown=0; foreach ($todaySchedule as $s): if ($shown++ >= $limit) break; ?>
                      <li class="mb-2">
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
            <!-- AKTIVITAS TERBARU -->
            <div class="col-12 col-lg-5">
              <div class="fp-card h-100 p-0">
                <div class="p-3 border-bottom small text-muted"><i class="bi bi-bell me-1"></i>Aktivitas Terbaru</div>
                <div class="p-2">
                  <?php if (empty($activities)): ?>
                    <div class="empty-hint text-center"><i class="bi bi-inboxes me-1"></i>Belum ada aktivitas.</div>
                  <?php else: ?>
                    <div class="scroll-area scroll-area--activity">
                      <ul class="list-unstyled mb-0">
                        <?php foreach ($activities as $a):
                          $badge = strtolower($a['badge'] ?? '');
                          $pill  = $badge==='success' ? 'pill-success' : ($badge==='warning' ? 'pill-warn' : ($badge==='danger' ? 'pill-danger' : 'pill-muted'));
                          $link  = !empty($a['link']) ? (string)$a['link'] : 'javascript:void(0)';
                        ?>
                          <li>
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
                    </div>
                  <?php endif; ?>
                </div>
              </div>
            </div>

            <div class="col-12 col-lg-7" id="progress">
              <div class="fp-card h-100 p-0">
                <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
                  <div class="small text-muted"><i class="bi bi-flag me-1"></i>Progress Event</div>
                </div>

                <div class="p-3">
                  <?php if (empty($progressEvents)): ?>
                    <div class="empty-hint text-center"><i class="bi bi-check2-circle me-1"></i>Tidak ada progres. Semua event sudah selesai.</div>
                  <?php else: ?>
                    <div class="scroll-area scroll-area--progress progress-list">
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

                        // CTA utama (link tombol)
                        $blockedPay = true; // akan di-set ulang di bawah

                        $ctaHref = "/presenter/events/detail/$eventId"; $ctaText = "Lihat Detail";
                        if (!$absSt) {
                          $ctaHref = "/presenter/kontributor/start/$eventId"; $ctaText = "Lengkapi Kontributor";
                        } elseif ($absSt==='ditolak') {
                          // ❗ Abstrak ditolak: JANGAN suruh upload abstrak lagi
                          $ctaHref = "/presenter/events/detail/$eventId";     $ctaText = "Lihat Status Event";
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

                        $stKontrib  = !empty($p['steps']['kontributor']) && str_contains($p['steps']['kontributor'],'done')
                                      ? 'done'
                                      : (!empty($p['steps']['kontributor']) && str_contains($p['steps']['kontributor'],'current') ? 'current' : 'muted');

                        $absState   = $mapAbs($absSt);
                        $fpState    = $mapFp($fpSt);

                        /** ================== LOGIKA STEP SESUAI AKTIVITAS ==================
                         * Input data → Abstrak → Full paper → LOA → Pembayaran → Selesai
                         * - Full paper baru upload / menunggu / revisi: JANGAN maju ke LOA
                         * - LOA hanya ketika FP = ACCEPTED
                         * - Pembayaran hanya boleh kalau abstrak & full paper diterima
                         */

                        // Boleh lanjut pembayaran kalau abstrak & full paper diterima
                        $allowPay   = ($absSt === 'diterima' && $fpSt === 'ACCEPTED');
                        $blockedPay = !$allowPay;

                        // LOA:
                        // - FP ACCEPTED  => LOA done
                        // - FP REJECTED  => LOA danger
                        // - selain itu   => LOA muted (belum LOA)
                        if ($fpSt === 'ACCEPTED') {
                            $loaState = 'done';
                        } elseif ($fpSt === 'REJECTED') {
                            $loaState = 'danger';
                        } else {
                            $loaState = 'muted';
                        }

                        // Pembayaran & Selesai
                        $bayarStepMeta = (string)($p['steps']['bayar'] ?? '');
                        $verifStepMeta = (string)($p['steps']['verifikasi'] ?? '');

                        $bayarState  = 'muted';
                        $finishState = 'muted';

                        if ($allowPay) {
                            // sudah ada pembayaran?
                            if (str_contains($bayarStepMeta, 'done')) {
                                $bayarState = 'done';
                            } else {
                                $bayarState = 'current'; // step berikut setelah LOA
                            }

                            // verifikasi pembayaran => selesai
                            if (str_contains($verifStepMeta, 'done')) {
                                $finishState = 'done';
                                $bayarState  = 'done';
                            }
                        } else {
                            // kalau status sudah buntu (ditolak), tandai merah di bayar & selesai
                            if ($absState === 'danger' || $fpState === 'danger') {
                                $bayarState  = 'danger';
                                $finishState = 'danger';
                            }
                        }

                        // Step visual abstrak & full paper
                        $absStepClass = (
                            $absState === 'done'   ? 'done'   :
                            ($absState === 'warn'  ? 'warn'   :
                            ($absState === 'danger'? 'danger' : 'current'))
                        );

                        $fpStepClass  = (
                            $fpState === 'done'    ? 'done'   :
                            ($fpState === 'warn'   ? 'warn'   :
                            ($fpState === 'danger' ? 'danger' :
                                ($absState === 'done' ? 'current' : 'muted')))
                        );
                      ?>
                        <div class="fp-card mb-3">
                          <div class="fp-head">
                            <h6 class="mb-0 fp-title text-truncate"><?= esc($title) ?></h6>
                            <span class="status-pill <?= $absPill ?>">Abstrak: <?= esc($absInfo) ?></span>
                          </div>

                          <div class="chip-row mb-2">
                            <span class="status-pill <?= $fpPill ?>">Full Paper: <?= esc($fpInfo) ?></span>
                          </div>

                          <div class="stepper mb-2">
                            <div class="step <?= esc($stKontrib) ?>">
                              <div class="dot"><i class="bi bi-person-lines-fill"></i></div>
                              <div class="label">Input Data</div>
                            </div>

                            <div class="step <?= esc($absStepClass) ?>">
                              <div class="dot"><i class="bi bi-geo-alt-fill"></i></div>
                              <div class="label">Input Abstrak</div>
                            </div>

                            <div class="step <?= esc($fpStepClass) ?>">
                              <div class="dot"><i class="bi bi-file-earmark-arrow-up-fill"></i></div>
                              <div class="label">Upload Berkas</div>
                            </div>

                            <!-- STEP LOA -->
                            <div class="step <?= esc($loaState) ?>">
                              <div class="dot"><i class="bi bi-file-earmark-check-fill"></i></div>
                              <div class="label">LOA</div>
                            </div>

                            <div class="step <?= esc($bayarState) ?>">
                              <div class="dot"><i class="bi bi-cash-stack"></i></div>
                              <div class="label">Pembayaran</div>
                            </div>

                            <div class="step <?= esc($finishState) ?>">
                              <div class="dot"><i class="bi bi-patch-check-fill"></i></div>
                              <div class="label">Selesai</div>
                            </div>
                          </div>

                          <div class="fp-foot">
                            <?php if ($blockedPay && $ctaText === 'Lanjut ke Pembayaran'): ?>
                              <button class="btn btn-success flex-fill" disabled title="Menunggu Abstrak & Full Paper diterima">
                                <i class="bi bi-lock-fill me-1"></i>Lanjut ke Pembayaran
                              </button>
                            <?php else: ?>
                              <a href="<?= esc($ctaHref) ?>" class="btn btn-primary flex-fill">
                                <i class="bi bi-play-fill me-1"></i><?= esc($ctaText) ?>
                              </a>
                            <?php endif; ?>
                          </div>

                          <?php if ($absSt === 'ditolak'): ?>
                            <div class="mini-hint mt-2">
                              <i class="bi bi-x-octagon me-1"></i>
                              Abstrak Anda <b>ditolak</b>. Event ini tidak dapat dilanjutkan. Jangan upload abstrak baru untuk event ini, dan hubungi panitia jika ada kebijakan banding.
                            </div>
                          <?php elseif ($blockedPay): ?>
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
      </div>
    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>


<style>
:root{
  --blue-50:#eff6ff; --blue-100:#dbeafe; --blue-200:#bfdbfe; --blue-300:#93c5fd;
  --blue-400:#60a5fa; --blue-500:#3b82f6; --blue-600:#2563eb; --blue-700:#1d4ed8; --blue-800:#1e40af; --blue-900:#1e3a8a;
  --muted:#6b7280; --ink:#0f172a; --radius:16px;
  --side-pad: clamp(1rem, 2.3vw, 2.2rem);

  /* tinggi-tinggi area scroll */
  --activity-row-h: 74px;
  --activity-visible: 4.5;
  --progress-card-h: 240px;
  --progress-visible: 1.5;
}

/* Lebar container */
.container-xxl{
  max-width: min(100%, 1560px);
  padding-left: var(--side-pad) !important;
  padding-right: var(--side-pad) !important;
  margin-inline: auto;
}

body{
  font-family: ui-sans-serif, system-ui, -apple-system, "Segoe UI", Roboto, "Helvetica Neue", Arial, "Noto Sans", "Liberation Sans", sans-serif;
  font-size:15.75px; line-height:1.6; color:var(--ink); letter-spacing:.1px;
}
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
.hero-title{ font-weight:900; letter-spacing:.2px; }
.text-white-70{ color:rgba(255,255,255,.90)!important; }
.hero-tabs{ background:#fff; padding:.6rem .8rem; border:1px solid rgba(30,64,175,.18); border-top:0; }
.hero-tabs .nav-link{ font-weight:700; border-radius:999px; padding:.45rem 1rem; }
.hero-tabs .nav-link.active{ background:var(--blue-600); color:#fff; }

/* Card seragam */
.fp-card{
  border:1px solid rgba(30,64,175,.12);
  border-radius:16px;
  background:#fff;
  box-shadow:0 10px 22px rgba(30,64,175,.10);
  padding:0.9rem;
  display:flex;
  flex-direction:column;
}
.fp-head{ display:flex; align-items:center; justify-content:space-between; gap:.75rem; margin-bottom:.35rem; }
.fp-title{ line-height:1.35; max-width:72%; color:var(--blue-900); }

/* KPI: icon & teks */
.kpi-icon{
  width:42px; height:42px; border-radius:12px;
  display:flex; align-items:center; justify-content:center;
  background:#eef5ff; color:#1d4ed8; font-size:18px;
  box-shadow:0 8px 20px rgba(29,78,216,.18), inset 0 -2px 0 rgba(255,255,255,.7);
}
.kpi-blue{ background:#e8f0ff; color:#1b4fd6; }
.kpi-green{ background:#e9fff5; color:#0f8a5b; }

.kpi-label{
  color:#475569; font-weight:700; letter-spacing:.15px;
}
.stat-number{
  font-size:28px; font-weight:900; line-height:1.15; letter-spacing:.25px; margin-top:2px; color:#0f172a;
}

/* Pills & chips */
.status-pill{ font-weight:800; font-size:.82rem; padding:.28rem .6rem; border-radius:999px; border:1px solid rgba(0,0,0,.06); white-space:nowrap; }
.pill-warn{ background:#fef3c7; color:#92400e; }
.pill-success{ background:#d1fae5; color:#065f46; }
.pill-danger{ background:#fee2e2; color:#991b1b; }
.pill-muted{ background:#f3f4f6; color:#374151; }
.chip-row{ display:flex; flex-wrap:wrap; gap:.5rem; }
.chip{ display:inline-flex; align-items:center; padding:.26rem .55rem; font-size:.86rem; border-radius:999px; background:#eef3ff; color:#1e3a8a; border:1px solid rgba(30,64,175,.15); font-weight:700; }
.chip.alt{ background:#f1f5ff; color:#244aa4; }

/* Meta / hints */
.mini-hint{ color:#3a2a6a; background:#f6f9ff; border:1px dashed rgba(30,64,175,.18); border-radius:10px; padding:.5rem .6rem; font-weight:600; font-size:.86rem; }
.empty-hint{ color:#567; background:#f6f9ff; border:1px dashed rgba(30,64,175,.18); border-radius:12px; padding:.8rem 1rem; font-weight:600; }

/* Empty state khusus Absensi */
.empty-state{
  text-align:center; padding:1.2rem; border:1px dashed rgba(30,64,175,.22);
  border-radius:12px; background:linear-gradient(180deg,#f8fbff, #ffffff);
}
.empty-ico{
  width:60px; height:60px; border-radius:14px; display:flex; align-items:center; justify-content:center;
  margin:0 auto .6rem; background:#eef2ff; color:#3730a3; font-size:28px;
  box-shadow:0 6px 16px rgba(55,48,163,.12);
}
.empty-title{ font-weight:800; color:#1e3a8a; }
.empty-desc{ color:#566; font-size:.95rem; }

/* Activity item */
.activity-item{
  display:flex; gap:.6rem; align-items:flex-start; text-decoration:none; color:inherit;
  border:1px solid rgba(2,6,23,.06); border-radius:12px; padding:.65rem .7rem; margin-bottom:.5rem;
}
.icon-pill{ min-width:34px; text-align:center; }

/* Footer action */
.fp-foot{ display:flex; gap:.6rem; margin-top:auto; padding-top:.6rem; border-top:1px dashed rgba(30,64,175,.16); }
.btn{ font-weight:800; border-radius:10px; font-size:.98rem; padding:.55rem 1.0rem; }
.btn-primary{ background:var(--blue-600); border-color:var(--blue-600); box-shadow:0 4px 12px rgba(37,99,235,.2); }

/* ===== Scroll area ===== */
.scroll-area{ overflow:auto; padding-right:4px; scrollbar-width:thin; scrollbar-color:#9db7ff #f3f4f6; }
.scroll-area::-webkit-scrollbar{ width:8px; }
.scroll-area::-webkit-scrollbar-thumb{ background:#9db7ff; border-radius:6px; }
.scroll-area::-webkit-scrollbar-track{ background:#f3f4f6; border-radius:6px; }

.scroll-area--activity{ max-height: calc(var(--activity-row-h) * var(--activity-visible)); }
/* sekitar 1–1.5 kartu terlihat */
.progress-list .fp-card{ min-height: var(--progress-card-h); }
.scroll-area--progress{ max-height: calc(var(--progress-card-h) * var(--progress-visible)); }

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

/* Stepper */
.stepper{ position:relative; display:flex; justify-content:space-between; gap:12px; padding:18px 14px; border:1px solid rgba(2,6,23,.06); border-radius:12px; background:#fff; }
.step{ position:relative; flex:1 1 0; text-align:center; min-width:0; }
.step .dot{ width:36px; height:36px; border-radius:50%; margin:0 auto 6px; display:flex; align-items:center; justify-content:center; font-size:18px; color:#fff; background:#16a34a; box-shadow:0 0 0 3px #e8f5eb; }
.step .label{ font-weight:700; font-size:.85rem; color:#14532d; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.step::after{ content:""; position:absolute; top:18px; right:-50%; height:4px; width:100%; background:#16a34a; }
.step:last-child::after{ display:none; }
.step.done .dot{ background:#16a34a; } .step.done .label{ color:#14532d; }
.step.current .dot{ background:#2563eb; } .step.current .label{ color:#1e3a8a; } .step.current::after{ background:#2563eb; }
.step.warn .dot{ background:#f59e0b; } .step.warn .label{ color:#92400e; } .step.warn::after{ background:#f59e0b; }
.step.danger .dot{ background:#ef4444; } .step.danger .label{ color:#7f1d1d; } .step.danger::after{ background:#ef4444; }
.step.muted .dot{ background:#9ca3af; } .step.muted .label{ color:#475569; } .step.muted::after{ background:#9ca3af; }
.step .bi{ line-height:1; }

/* Responsive */
@media (max-width:767.98px){
  .card-hero .hero-body{ padding:1.4rem 1rem; min-height:165px; }
  .fp-title{ max-width:68%; }
  :root{ --activity-visible: 3.6; --progress-visible: 1.2; }
}
@media (max-width:576px){
  .container-xxl{ padding-left: calc(var(--side-pad) - .25rem) !important; padding-right: calc(var(--side-pad) - .25rem) !important; }
  .step .label{ font-size:.78rem; }
  .step .dot{ width:32px; height:32px; font-size:16px; }
}

/* util */
.text-truncate-2{
  overflow:hidden; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical;
}
</style>

<script>
/* Tombol "Lihat Kalender" saat absensi kosong -> pindah ke tab Kalender */
document.addEventListener('DOMContentLoaded', function(){
  const btn = document.getElementById('btnGoCalendar');
  if (!btn) return;
  btn.addEventListener('click', function(){
    const tabBtn = document.getElementById('tab-calendar');
    if (tabBtn) {
      const t = new bootstrap.Tab(tabBtn);
      t.show();
      window.scrollTo({ top: 0, behavior: 'smooth' });
    }
  });
});
</script>
