<?php
$title = $title ?? 'Event';
$q     = $q ?? '';

$available   = $available   ?? [];
$closed      = $closed      ?? [];
$statusIndex = $statusIndex ?? [];

/** mapper warna badge untuk chip status */
$stateBadge = function (string $state): string {
  return match ($state) {
    'lengkapi_kontributor', 'upload_abstrak', 'upload_fullpaper', 'bayar' => 'primary',
    'menunggu_abstrak', 'fp_menunggu_review', 'pembayaran_pending'       => 'warning',
    'fp_perbaikan', 'abstrak_ditolak', 'pembayaran_ditolak'              => 'danger',
    'pembayaran_dibatalkan', 'pembayaran_kedaluwarsa'                    => 'secondary',
    'siap_absen', 'sudah_absen'                                          => 'success',
    default                                                               => 'secondary',
  };
};

/** helper tanggal (seragam dengan patokan) */
$fmtDate = function ($date, $withTime = false) {
  if (!$date) return '-';
  $ts = strtotime((string)$date);
  return $withTime ? date('d M Y H:i', $ts) : date('d M Y', $ts);
};
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill page-wrap-blue">
    <div class="container-xxl px-3 px-md-4 py-4">

      <!-- Hero -->
      <div class="hero-blue card-glass mb-3 p-3 p-md-4 d-flex justify-content-between align-items-start gap-3">
        <div>
          <h3 class="hero-title mb-1"><i class="bi bi-calendar3 me-2"></i>Event</h3>
          <div class="text-white-75 small">Daftar & ikuti alur presenter</div>
        </div>
        <div class="d-none d-md-block text-end">
          <div class="text-white-75 small">Hari ini</div>
          <div class="fw-semibold text-white"><?= date('d M Y') ?></div>
        </div>
      </div>

      <!-- Search Box -->
      <div class="card shadow-soft card-glass-plain mb-3">
        <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
          <span class="badge bg-blue-soft"><i class="bi bi-search"></i></span>
          <h6 class="mb-0 fw-semibold text-blue-900">Cari Event</h6>
        </div>
        <div class="card-body pt-3">
          <form class="row g-2 align-items-center" method="get" action="<?= site_url('presenter/events') ?>">
            <div class="col-12 col-md-8">
              <input class="form-control" name="q" value="<?= esc($q) ?>" placeholder="Ketik judul/lokasi/kata kunci...">
            </div>
            <div class="col-12 col-md-4 d-grid">
              <button class="btn btn-primary">
                <i class="bi bi-search me-1"></i>Cari
              </button>
            </div>
          </form>
        </div>
      </div>

      <!-- Event Tersedia -->
      <div class="card shadow-soft card-glass-plain mb-4">
        <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
          <span class="badge bg-blue-soft"><i class="bi bi-lightning-charge"></i></span>
          <h5 class="mb-0 fw-semibold text-blue-900">Event Tersedia (Buka Pendaftaran)</h5>
        </div>
        <div class="card-body">
          <?php if (empty($available)): ?>
            <div class="text-muted">Tidak ada event yang membuka pendaftaran.</div>
          <?php else: ?>
            <div class="row g-3">
              <?php foreach ($available as $e):
                $evId  = (int)($e['id'] ?? 0);
                $ui    = $statusIndex[$evId]['ui'] ?? [];
                $state = $statusIndex[$evId]['state'] ?? 'belum_daftar';
                $label = $statusIndex[$evId]['label'] ?? '';
                $hint  = $statusIndex[$evId]['hint']  ?? '';

                $showChip  = (bool)($ui['show_chip'] ?? false);
                $chipTone  = $stateBadge($state);
                $chipClass = $ui['chip_class'] ?? ('bg-'.$chipTone.'-subtle text-'.$chipTone);

                $cta = $ui['cta'] ?? [
                  'visible' => true,
                  'label'   => 'Detail Event',
                  'url'     => site_url('presenter/events/detail/'.$evId),
                  'class'   => 'btn-outline-primary',
                  'disabled'=> false,
                ];
              ?>
              <div class="col-12 col-md-6 col-xl-4">
                <div class="event-card h-100 p-3">
                  <div class="d-flex align-items-start justify-content-between mb-2">
                    <h6 class="mb-0 text-blue-900"><?= esc($e['title'] ?? '-') ?></h6>
                    <span class="badge bg-success-subtle">Tersedia</span>
                  </div>

                  <div class="small text-muted mb-2">
                    Tanggal Event:
                    <strong class="text-blue-900"><?= esc($fmtDate($e['event_date'] ?? null)) ?> <?= esc($e['event_time'] ?? '') ?></strong><br>
                    Batas Pendaftaran:
                    <strong class="text-blue-900"><?= esc($fmtDate($e['registration_deadline'] ?? null, true)) ?></strong>
                  </div>

                  <?php if ($showChip): ?>
                    <div class="mb-2">
                      <span class="badge rounded-pill <?= esc($chipClass) ?> fw-normal"><?= esc($label) ?></span>
                      <?php if ($hint): ?><div class="text-muted small mt-1"><?= esc($hint) ?></div><?php endif; ?>
                    </div>
                  <?php endif; ?>

                  <div class="d-flex align-items-center justify-content-between gap-2">
                    <?php
                      $lbl  = strtolower((string)($cta['label'] ?? ''));
                      $icon = 'bi-info-circle';
                      if (str_contains($lbl,'daftar')) $icon='bi-box-arrow-in-right';
                      elseif (str_contains($lbl,'kontributor')) $icon='bi-people';
                      elseif (str_contains($lbl,'upload')) $icon='bi-upload';
                      elseif (str_contains($lbl,'bayar')) $icon='bi-credit-card';
                      elseif (str_contains($lbl,'cek') || str_contains($lbl,'status')) $icon='bi-clock-history';
                      elseif (str_contains($lbl,'lihat')) $icon='bi-eye';
                    ?>
                    <a
                      class="btn <?= esc($cta['class'] ?? 'btn-outline-primary') ?> flex-fill <?= !empty($cta['disabled']) ? 'disabled' : '' ?>"
                      href="<?= esc($cta['url'] ?? site_url('presenter/events/detail/'.$evId)) ?>"
                      <?php if (!empty($cta['disabled'])): ?> aria-disabled="true"<?php endif; ?>
                    >
                      <i class="bi <?= esc($icon) ?> me-1"></i><?= esc($cta['label'] ?? 'Detail') ?>
                    </a>

                    <a class="text-decoration-none small text-muted" href="<?= site_url('presenter/events/detail/'.$evId) ?>">
                      Detail <i class="bi bi-chevron-right ms-1"></i>
                    </a>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Event Ditutup -->
      <div class="card shadow-soft card-glass-plain mb-4">
        <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
          <span class="badge bg-blue-soft"><i class="bi bi-lock"></i></span>
          <h5 class="mb-0 fw-semibold text-blue-900">Event Ditutup</h5>
        </div>
        <div class="card-body">
          <?php if (empty($closed)): ?>
            <div class="text-muted">Belum ada event yang ditutup.</div>
          <?php else: ?>
            <div class="row g-3">
              <?php foreach ($closed as $e):
                $evId  = (int)($e['id'] ?? 0);
                $ui    = $statusIndex[$evId]['ui'] ?? [];
                $state = $statusIndex[$evId]['state'] ?? 'belum_daftar';
                $label = $statusIndex[$evId]['label'] ?? '';
                $hint  = $statusIndex[$evId]['hint']  ?? '';

                $showChip  = (bool)($ui['show_chip'] ?? false);
                $chipTone  = $stateBadge($state);
                $chipClass = $ui['chip_class'] ?? ('bg-'.$chipTone.'-subtle text-'.$chipTone);

                $cta = $ui['cta'] ?? [
                  'visible' => true,
                  'label'   => 'Detail Event',
                  'url'     => site_url('presenter/events/detail/'.$evId),
                  'class'   => 'btn-outline-secondary',
                  'disabled'=> false,
                ];
              ?>
              <div class="col-12 col-md-6 col-xl-4">
                <div class="event-card h-100 p-3 opacity-90">
                  <div class="d-flex align-items-start justify-content-between mb-2">
                    <h6 class="mb-0 text-blue-900"><?= esc($e['title'] ?? '-') ?></h6>
                    <span class="badge bg-secondary-subtle">Ditutup</span>
                  </div>

                  <div class="small text-muted mb-2">
                    Tanggal Event:
                    <strong class="text-blue-900"><?= esc($fmtDate($e['event_date'] ?? null)) ?> <?= esc($e['event_time'] ?? '') ?></strong><br>
                    Batas Pendaftaran:
                    <strong class="text-blue-900"><?= esc($fmtDate($e['registration_deadline'] ?? null, true)) ?></strong>
                  </div>

                  <?php if ($showChip): ?>
                    <div class="mb-2">
                      <span class="badge rounded-pill <?= esc($chipClass) ?> fw-normal"><?= esc($label) ?></span>
                      <?php if ($hint): ?><div class="text-muted small mt-1"><?= esc($hint) ?></div><?php endif; ?>
                    </div>
                  <?php endif; ?>

                  <div class="d-flex align-items-center justify-content-between gap-2">
                    <?php
                      $lbl  = strtolower((string)($cta['label'] ?? ''));
                      $icon = 'bi-info-circle';
                      if (str_contains($lbl,'kontributor')) $icon='bi-people';
                      elseif (str_contains($lbl,'upload')) $icon='bi-upload';
                      elseif (str_contains($lbl,'bayar')) $icon='bi-credit-card';
                      elseif (str_contains($lbl,'cek') || str_contains($lbl,'status')) $icon='bi-clock-history';
                      elseif (str_contains($lbl,'lihat')) $icon='bi-eye';
                    ?>
                    <a
                      class="btn <?= esc($cta['class'] ?? 'btn-outline-secondary') ?> flex-fill <?= !empty($cta['disabled']) ? 'disabled' : '' ?>"
                      href="<?= esc($cta['url'] ?? site_url('presenter/events/detail/'.$evId)) ?>"
                      <?php if (!empty($cta['disabled'])): ?> aria-disabled="true"<?php endif; ?>
                    >
                      <i class="bi <?= esc($icon) ?> me-1"></i><?= esc($cta['label'] ?? 'Detail') ?>
                    </a>

                    <a class="text-decoration-none small text-muted" href="<?= site_url('presenter/events/detail/'.$evId) ?>">
                      Detail <i class="bi bi-chevron-right ms-1"></i>
                    </a>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<style>
:root{
  /* seragam dgn patokan */
  --blue-50:#eff6ff; --blue-100:#dbeafe; --blue-200:#bfdbfe;
  --blue-300:#93c5fd; --blue-400:#60a5fa; --blue-500:#3b82f6;
  --blue-600:#2563eb; --blue-700:#1d4ed8; --blue-800:#1e40af; --blue-900:#1e3a8a;

  --side-pad: 1rem;   /* kiri–kanan match patokan */
  --gutter:   1rem;   /* jarak antar kolom */
}

body{
  font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  font-size:14.6px;  /* match patokan */
  line-height:1.5;
}

/* ===== Layout ===== */
.page-wrap-blue{
  background:linear-gradient(180deg,var(--blue-50),#fff 40%);
  min-height:100vh;
  padding-top:72px; /* match patokan */
}

.container-xxl{
  max-width:1400px;
  padding-left:var(--side-pad) !important;
  padding-right:var(--side-pad) !important;
}

.row.g-3{ --bs-gutter-x: var(--gutter); --bs-gutter-y: var(--gutter); }

/* ===== HERO ===== */
.hero-blue{
  background:radial-gradient(1200px 400px at 10% -20%,var(--blue-600) 0,var(--blue-700) 40%,var(--blue-800) 100%) !important;
  color:#fff !important;
  border-radius:16px;
  border:1px solid rgba(255,255,255,.15);
  box-shadow:0 12px 28px rgba(30,64,175,.10);
  padding:1.6rem !important;
  margin-bottom:1.25rem !important;
}
/* matikan efek glass jika diberi .card-glass (sama seperti patokan) */
.hero-blue.card-glass,
.hero-blue.card-glass-plain{
  backdrop-filter:none !important;
  background:radial-gradient(1200px 400px at 10% -20%,var(--blue-600) 0,var(--blue-700) 40%,var(--blue-800) 100%) !important;
  border:1px solid rgba(255,255,255,.15) !important;
  box-shadow:0 12px 28px rgba(30,64,175,.10) !important;
}
.hero-title{ font-weight:800; letter-spacing:.25px; font-size:1.45rem; }
.text-white-75{ color:rgba(255,255,255,.85)!important; }

/* ===== Card / Glass ===== */
.card-glass-plain{
  backdrop-filter:blur(6px);
  background:rgba(255,255,255,.94);
  border-radius:14px;
  border:1px solid rgba(30,64,175,.10);
}
.shadow-soft{ box-shadow:0 10px 24px rgba(30,64,175,.08); }

.card-header{ padding:1rem 1rem .45rem 1rem !important; }
.card-body{   padding:1.05rem !important; }

/* ===== Badge kecil lembut ===== */
.bg-blue-soft{ background:var(--blue-200); color:var(--blue-800); border-radius:12px; padding:.4rem .6rem; font-weight:600; font-size:.85rem; }
.bg-success-subtle{   background:#d1fae5!important; color:#065f46!important; }
.bg-warning-subtle{   background:#fef3c7!important; color:#92400e!important; }
.bg-danger-subtle{    background:#fee2e2!important; color:#991b1b!important; }
.bg-info-subtle{      background:#e0f2fe!important; color:#0c4a6e!important; }
.bg-secondary-subtle{ background:#f1f5f9!important; color:#475569!important; }
.bg-primary-subtle{   background:#dbeafe!important; color:var(--blue-700)!important; }
.text-blue-900{ color:var(--blue-900)!important; }

/* ===== Event card ===== */
.event-card{
  background:linear-gradient(180deg,#fff,rgba(255,255,255,.96));
  border:1px solid rgba(30,64,175,.10);
  border-radius:14px;
  box-shadow:0 10px 22px rgba(30,64,175,.08);
  padding:14px;
}
.event-card h6{ font-size:1.05rem; margin-bottom:.25rem; }
.event-card .small{ font-size:.92rem; }
.event-card .badge{ padding:.35rem .6rem; font-size:.78rem; border-radius:10px; }
.event-card .btn{ border-radius:10px; padding:.58rem 1rem; font-size:.95rem; }
.opacity-90{ opacity:.92; }

/* ===== Table (kalau dibutuhkan) ===== */
.table{ font-size:.95rem; margin-bottom:0; }
.table thead th{
  background-color:var(--blue-50)!important;
  border-bottom:1px solid var(--blue-200);
  font-weight:600; color:var(--blue-900);
  font-size:.85rem; text-transform:uppercase; letter-spacing:.4px;
  padding:.75rem 1rem;
}
.table tbody tr{ border-bottom:1px solid rgba(30,64,175,.08); }
.table tbody td{
  padding:.8rem 1rem; vertical-align:middle; border-top:none; font-size:.95rem;
}
.table-responsive{ border:1px solid rgba(30,64,175,.08); border-radius:12px; overflow:hidden; }

/* ===== Buttons ===== */
.btn{
  font-weight:600; letter-spacing:.25px;
  border-radius:10px; font-size:.95rem; padding:.55rem 1rem;
}
.btn-sm{ padding:.42rem .8rem; font-size:.86rem; }
.btn-lg{ padding:.85rem 1.5rem; font-size:1.06rem; }
.btn-primary{ background:var(--blue-600); border-color:var(--blue-600); box-shadow:0 4px 12px rgba(37,99,235,.2); }
.btn-info{    background:#06b6d4; border-color:#06b6d4; box-shadow:0 4px 12px rgba(6,182,212,.2); }
.btn-success{ background:#059669; border-color:#059669; box-shadow:0 4px 12px rgba(5,150,105,.2); }
.btn-warning{ background:#d97706; border-color:#d97706; box-shadow:0 4px 12px rgba(217,119,6,.2); }
.btn-danger{  background:#dc2626; border-color:#dc2626; box-shadow:0 4px 12px rgba(220,38,38,.2); }

/* ===== Helpers ===== */
.text-muted{ color:#6b7280!important; font-weight:500; font-size:.9rem; }
.text-muted.small{ font-size:.84rem!important; }
.fw-semibold{ font-weight:600!important; }
.fw-medium{   font-weight:500!important; }
a{ text-decoration:none; }
a.text-primary{ color:var(--blue-600)!important; font-weight:500; }

/* ===== Responsive ===== */
@media (max-width:575.98px){
  .container-xxl{ padding-left:1rem !important; padding-right:1rem !important; }
  .hero-blue{ border-radius:14px; padding:1.25rem!important; margin-bottom:1rem!important; }
  .hero-title{ font-size:1.25rem; }
  .card-body{  padding:.9rem!important; }
  .card-header{ padding:.9rem .9rem .4rem .9rem!important; }
  .event-card{ padding:12px; }
  .event-card h6{ font-size:1rem; }
  .event-card .small{ font-size:.9rem; }
  .badge{ padding:.38rem .6rem; font-size:.74rem; }
}
@media (min-width:576px) and (max-width:767.98px){
  .container-xxl{ padding-left:1rem !important; padding-right:1rem !important; }
  .hero-title{ font-size:1.35rem; }
  .card-body{  padding:1rem!important; }
}
@media (min-width:768px) and (max-width:991.98px){
  .container-xxl{ padding-left:1rem !important; padding-right:1rem !important; }
  .hero-title{ font-size:1.45rem; }
}
@media (min-width:992px){
  .hero-blue{ padding:1.7rem!important; }
  .card-body{  padding:1.05rem!important; }
  .card-header{ padding:1rem 1rem .45rem 1rem!important; }
}
</style>
