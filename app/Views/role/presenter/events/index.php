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

      <!-- HERO + SEARCH (dipindah ke hero) -->
      <div class="card-hero mb-4">
        <div class="hero-body">
          <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
              <h3 class="hero-title mb-1"><i class="bi bi-calendar3 me-2"></i>Event</h3>
              <div class="text-white-70 small">Daftar & ikuti alur presenter.</div>
            </div>

            <!-- Search di dalam hero -->
            <div class="hero-tools flex-grow-1" style="max-width:620px;">
              <form class="input-group input-group-lg hero-search" method="get" action="<?= site_url('presenter/events') ?>">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input name="q" id="searchInput" type="text" class="form-control" value="<?= esc($q) ?>" placeholder="Cari event, status, format…">
                <button id="clearSearch" type="button" class="btn btn-light <?= $q ? '' : 'd-none' ?>"><i class="bi bi-x-circle"></i></button>
              </form>
            </div>
          </div>
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
            <div class="empty-hint"><i class="bi bi-inbox me-1"></i>Tidak ada event yang membuka pendaftaran.</div>
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
            <div class="empty-hint"><i class="bi bi-inboxes me-1"></i>Belum ada event yang ditutup.</div>
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
  /* sama dengan patokan */
  --blue-50:#eff6ff; --blue-100:#dbeafe; --blue-200:#bfdbfe; --blue-300:#93c5fd;
  --blue-400:#60a5fa; --blue-500:#3b82f6; --blue-600:#2563eb; --blue-700:#1d4ed8; --blue-800:#1e40af; --blue-900:#1e3a8a;
  --muted:#6b7280; --ink:#0f172a; --radius:16px;
  --side-pad: clamp(1rem, 2.3vw, 2.2rem);
}

.container-xxl{ max-width:min(100%, 1560px); padding-left:var(--side-pad)!important; padding-right:var(--side-pad)!important; margin-inline:auto; }
body{ font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size:15.5px; line-height:1.6; color:var(--ink); }

/* Background halaman */
.page-wrap-blue{
  min-height:100vh; padding-top:72px;
  background:
    radial-gradient(1000px 380px at 10% -10%, rgba(59,130,246,.16), rgba(59,130,246,0) 60%),
    radial-gradient(1000px 380px at 90% 110%, rgba(59,130,246,.12), rgba(59,130,246,0) 70%),
    linear-gradient(180deg, var(--blue-50), #fff 40%);
}

/* HERO (persis patokan) */
.card-hero{ border:0; border-radius:var(--radius); overflow:hidden; box-shadow:0 12px 28px rgba(30,64,175,.18); }
.card-hero .hero-body{
  background:linear-gradient(135deg,var(--blue-700),var(--blue-800)); color:#fff;
  padding:1.8rem 1.2rem; min-height:176px;
}
.hero-title{ font-weight:800; }
.text-white-70{ color:rgba(255,255,255,.85)!important; }

/* Search di hero (sama kaya patokan FP) */
.hero-tools .input-group .input-group-text{ background:#fff; border:0; }
.hero-tools .form-control{ border:0; }
.hero-tools .btn{ border:0; }
.hero-search{ border-radius:12px; overflow:hidden; }

/* Card / Glass */
.card-glass-plain{ backdrop-filter:blur(6px); background:rgba(255,255,255,.96); border-radius:14px; border:1px solid rgba(30,64,175,.10); }
.shadow-soft{ box-shadow:0 10px 24px rgba(30,64,175,.08); }

/* Badge & pill halus */
.bg-blue-soft{ background:var(--blue-200); color:var(--blue-800); border-radius:12px; padding:.5rem .7rem; font-weight:600; font-size:.9rem; }
.bg-success-subtle{   background:#d1fae5!important; color:#065f46!important; }
.bg-warning-subtle{   background:#fef3c7!important; color:#92400e!important; }
.bg-danger-subtle{    background:#fee2e2!important; color:#991b1b!important; }
.bg-info-subtle{      background:#e0f2fe!important; color:#075985!important; }
.bg-secondary-subtle{ background:#f1f5f9!important; color:#475569!important; }
.bg-primary-subtle{   background:#dbeafe!important; color:#1d4ed8!important; }

.text-blue-900{ color:var(--blue-900)!important; }

/* Event card */
.event-card{
  background:linear-gradient(180deg,#fff,rgba(255,255,255,.97));
  border:1px solid rgba(30,64,175,.12); border-radius:14px; box-shadow:0 10px 22px rgba(30,64,175,.10);
  padding:16px; transition:transform .18s ease, box-shadow .18s ease, border-color .18s ease;
}
.event-card:hover{ transform: translateY(-2px); box-shadow:0 16px 28px rgba(30,64,175,.16); border-color: rgba(30,64,175,.22); }
.event-card h6{ font-size:1.12rem; margin-bottom:.25rem; }
.event-card .small{ font-size:.95rem; }
.event-card .badge{ padding:.35rem .6rem; font-size:.78rem; border-radius:10px; }
.event-card .btn{ border-radius:10px; padding:.6rem 1.05rem; font-size:.98rem; }
.opacity-90{ opacity:.92; }

/* Empty hint */
.empty-hint{ color:#567; background:#f6f9ff; border:1px dashed rgba(30,64,175,.18); border-radius:12px; padding:.8rem 1rem; font-weight:600; }

/* Buttons */
.btn{ font-weight:800; border-radius:10px; font-size:.98rem; padding:.6rem 1.05rem; }
.btn-primary{ background:var(--blue-600); border-color:var(--blue-600); box-shadow:0 4px 12px rgba(37,99,235,.2); }

/* Responsive */
@media (max-width:767.98px){
  .card-hero .hero-body{ padding:1.4rem 1rem; min-height:165px; }
}
@media (max-width:575.98px){
  .container-xxl{ padding-left: calc(var(--side-pad) - .25rem) !important; padding-right: calc(var(--side-pad) - .25rem) !important; }
  .event-card{ padding:14px; }
}
</style>

<script>
(function(){
  const input = document.getElementById('searchInput');
  const clear = document.getElementById('clearSearch');
  if(!input || !clear) return;
  const toggle = () => clear.classList.toggle('d-none', !input.value.trim());
  input.addEventListener('input', toggle);
  clear.addEventListener('click', ()=>{ input.value=''; input.form.submit ? input.form.submit() : toggle(); });
  toggle();
})();
</script>
