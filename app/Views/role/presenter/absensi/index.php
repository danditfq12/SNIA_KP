<?php
$title      = $title ?? 'Absensi';
$kpi        = $kpi ?? [];            // tidak terpakai lagi, aman dibiarkan
$boxesToday = $boxesToday ?? [];
$boxesNext  = $boxesNext ?? [];

$fmtDate = fn($s)=> $s ? date('d M Y', strtotime($s)) : '-';
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill page-wrap-blue">
    <div class="container-xxl px-3 px-md-4 py-4">

      <!-- HERO -->
      <div class="hero-blue card-glass mb-3 p-3 p-md-4 d-flex justify-content-between align-items-start gap-3">
        <div>
          <h3 class="hero-title mb-1"><i class="bi bi-qr-code-scan me-2"></i>Absensi</h3>
          <div class="text-white-75 small">Pilih event, lalu lakukan absen di halaman detail.</div>
        </div>
        <div class="d-none d-md-block text-end">
          <div class="text-white-75 small">Hari ini</div>
          <div class="fw-semibold text-white"><?= date('d M Y') ?></div>
        </div>
      </div>

      <!-- HARI INI -->
      <div class="card shadow-soft card-glass-plain mb-4">
        <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
          <span class="badge bg-blue-soft"><i class="bi bi-sun"></i></span>
          <h6 class="mb-0 fw-semibold text-blue-900">Event Hari Ini</h6>
        </div>
        <div class="card-body">
          <?php if (!empty($boxesToday)): ?>
            <div class="row g-3">
              <?php foreach ($boxesToday as $e): ?>
              <?php
                $eid      = (int)($e['event_id'] ?? 0);
                $title    = (string)($e['title'] ?? '-');
                $date     = $fmtDate($e['date'] ?? null);
                $time     = (string)($e['time'] ?? '-');
                $loc      = (string)($e['location'] ?? '');
                $attended = (bool)($e['attended'] ?? false);
                $win      = (array)($e['window'] ?? []);
                $isOpen   = (bool)($win['is_open'] ?? false);
                $reason   = trim((string)($win['reason'] ?? 'Tertutup'));
                $detailUrl= site_url('presenter/absensi/event/'.$eid);
              ?>
              <div class="col-12 col-md-6 col-xl-4">
                <div class="event-card h-100 p-3">
                  <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="mb-0 text-blue-900"><?= esc($title) ?></h6>
                    <?php if ($attended): ?>
                      <span class="badge bg-success-subtle text-success">Sudah Absen</span>
                    <?php endif; ?>
                  </div>

                  <div class="small text-muted mb-2">
                    <i class="bi bi-calendar-event"></i> <?= esc($date) ?>,
                    <i class="bi bi-clock ms-1"></i> <?= esc($time) ?>
                  </div>
                  <?php if ($loc !== ''): ?>
                    <div class="small text-muted mb-3">
                      <i class="bi bi-geo-alt"></i> <?= esc($loc) ?>
                    </div>
                  <?php endif; ?>

                  <div class="d-grid gap-2">
                    <?php if ($isOpen): ?>
                      <a href="<?= $detailUrl ?>" class="btn btn-primary">
                        <i class="bi bi-check2-circle me-1"></i> Absen Sekarang
                      </a>
                    <?php else: ?>
                      <a href="<?= $detailUrl ?>" class="btn btn-outline-secondary">
                        <i class="bi bi-info-circle me-1"></i> Lihat Detail
                      </a>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="empty-state">
              <div class="empty-icon bg-primary-subtle text-primary"><i class="bi bi-inbox"></i></div>
              <div class="empty-text">Tidak ada event hari ini.</div>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- MENDATANG -->
      <div class="card shadow-soft card-glass-plain">
        <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
          <span class="badge bg-blue-soft"><i class="bi bi-calendar2-week"></i></span>
          <h6 class="mb-0 fw-semibold text-blue-900">Event Mendatang</h6>
        </div>
        <div class="card-body">
          <?php if (!empty($boxesNext)): ?>
            <div class="row g-3">
              <?php foreach ($boxesNext as $e): ?>
              <?php
                $eid      = (int)($e['event_id'] ?? 0);
                $title    = (string)($e['title'] ?? '-');
                $date     = $fmtDate($e['date'] ?? null);
                $time     = (string)($e['time'] ?? '-');
                $loc      = (string)($e['location'] ?? '');
                $detailUrl= site_url('presenter/absensi/event/'.$eid);
              ?>
              <div class="col-12 col-md-6 col-xl-4">
                <div class="event-card h-100 p-3">
                  <div class="d-flex justify-content-between align-items-start mb-2">
                    <h6 class="mb-0 text-blue-900"><?= esc($title) ?></h6>
                    <span class="badge bg-secondary-subtle text-secondary">Mendatang</span>
                  </div>
                  <div class="small text-muted mb-2">
                    <i class="bi bi-calendar-event"></i> <?= esc($date) ?>,
                    <i class="bi bi-clock ms-1"></i> <?= esc($time) ?>
                  </div>
                  <?php if ($loc !== ''): ?>
                    <div class="small text-muted mb-3">
                      <i class="bi bi-geo-alt"></i> <?= esc($loc) ?>
                    </div>
                  <?php endif; ?>

                  <div class="d-grid gap-2">
                    <a href="<?= $detailUrl ?>" class="btn btn-outline-secondary">
                      <i class="bi bi-info-circle me-1"></i> Lihat Detail
                    </a>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="empty-state">
              <div class="empty-icon bg-primary-subtle text-primary"><i class="bi bi-inbox"></i></div>
              <div class="empty-text">Belum ada event mendatang.</div>
            </div>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<style>
/* ===== Presenter Blue UI (patokan seragam) ===== */
:root{
  --blue-50:#eff6ff; --blue-100:#dbeafe; --blue-200:#bfdbfe; --blue-300:#93c5fd;
  --blue-400:#60a5fa; --blue-500:#3b82f6; --blue-600:#2563eb; --blue-700:#1d4ed8; --blue-800:#1e40af; --blue-900:#1e3a8a;
  --side-pad: clamp(1rem, 2.3vw, 2.2rem);
  --gutter: 1rem;
  --ink:#0f172a;
}

body{ font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size:15.5px; line-height:1.6; color:var(--ink); }

/* Layout background seperti patokan */
.page-wrap-blue{
  min-height:100vh; padding-top:72px;
  background:
    radial-gradient(1000px 380px at 10% -10%, rgba(59,130,246,.16), rgba(59,130,246,0) 60%),
    radial-gradient(1000px 380px at 90% 110%, rgba(59,130,246,.12), rgba(59,130,246,0) 70%),
    linear-gradient(180deg, var(--blue-50), #fff 40%);
}
.container-xxl{ max-width:min(100%, 1560px); padding-left:var(--side-pad)!important; padding-right:var(--side-pad)!important; margin-inline:auto; }
.row.g-3{ --bs-gutter-x: var(--gutter); --bs-gutter-y: var(--gutter); }

/* Hero (override glass) */
.hero-blue{
  background:radial-gradient(1200px 400px at 10% -20%,var(--blue-600) 0,var(--blue-700) 40%,var(--blue-800) 100%) !important;
  color:#fff !important; border-radius:16px;
  border:1px solid rgba(255,255,255,.15); box-shadow:0 12px 28px rgba(30,64,175,.10);
}
.hero-blue.card-glass,.hero-blue.card-glass-plain{ backdrop-filter:none !important; }
.hero-title{ font-weight:800; letter-spacing:.25px; font-size:1.45rem; }
.text-white-75{ color:rgba(255,255,255,.85)!important; }

/* Cards */
.card-glass-plain{ backdrop-filter:blur(6px); background:rgba(255,255,255,.96); border-radius:14px; border:1px solid rgba(30,64,175,.10); }
.shadow-soft{ box-shadow:0 10px 24px rgba(30,64,175,.08); }
.card-header{ padding:1rem 1rem .45rem 1rem !important; }
.card-body{   padding:1.05rem !important; }

/* Badges subtle */
.bg-blue-soft{ background:var(--blue-200); color:var(--blue-800); border-radius:12px; padding:.4rem .6rem; font-weight:600; font-size:.85rem; }
.bg-success-subtle{   background:#d1fae5!important; color:#065f46!important; font-size:.8rem; padding:.35rem .65rem; }
.bg-warning-subtle{   background:#fef3c7!important; color:#92400e!important; font-size:.8rem; padding:.35rem .65rem; }
.bg-secondary-subtle{ background:#f1f5f9!important; color:#475569!important; font-size:.8rem; padding:.35rem .65rem; }
.bg-primary-subtle{   background:#dbeafe!important; color:var(--blue-700)!important; }
.text-blue-900{ color:var(--blue-900)!important; }

/* Custom Alert untuk status */
.alert-warning-custom{
  background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
  border: 1px solid #fbbf24;
  border-radius: 10px;
  color: #78350f;
  font-weight: 500;
}
.alert-warning-custom i{ color: #d97706; }

/* Event card */
.event-card{
  background:linear-gradient(180deg,#fff,rgba(255,255,255,.96));
  border:1px solid rgba(30,64,175,.10);
  border-radius:14px;
  box-shadow:0 10px 22px rgba(30,64,175,.08);
  padding:14px;
  transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.event-card:hover{
  transform: translateY(-2px);
  box-shadow:0 14px 28px rgba(30,64,175,.12);
}
.event-card .btn{ border-radius:10px; font-weight:600; }

/* Empty state */
.empty-state{ padding:1.25rem; text-align:center; color:#64748b; }
.empty-icon{
  width:52px; height:52px; border-radius:12px; display:inline-flex; align-items:center; justify-content:center;
  font-size:1.35rem; margin-bottom:.5rem;
}
.empty-text{ font-weight:700; }

/* Buttons */
.btn{ font-weight:700; border-radius:10px; font-size:.95rem; padding:.6rem 1.05rem; transition: all 0.2s ease; }
.btn-primary{ 
  background:var(--blue-600); 
  border-color:var(--blue-600); 
  box-shadow:0 4px 12px rgba(37,99,235,.2); 
}
.btn-primary:hover{
  background:var(--blue-700);
  border-color:var(--blue-700);
  box-shadow:0 6px 16px rgba(37,99,235,.3);
  transform: translateY(-1px);
}
.btn-outline-secondary{ 
  border-color:#cbd5e1; 
  color:#475569;
}
.btn-outline-secondary:hover{
  background:#f1f5f9;
  border-color:#94a3b8;
  transform: translateY(-1px);
}

/* Responsive */
@media (max-width:575.98px){
  .container-xxl{ padding-left: calc(var(--side-pad) - .25rem) !important; padding-right: calc(var(--side-pad) - .25rem) !important; }
  .hero-blue{ border-radius:14px; padding:1.25rem!important; margin-bottom:1rem!important; }
  .hero-title{ font-size:1.25rem; }
  .card-header{ padding:.9rem .9rem .4rem .9rem!important; }
  .card-body{ padding:.9rem!important; }
  .event-card{ padding:12px; }
  .alert-warning-custom{ font-size:.85rem; }
}
</style>