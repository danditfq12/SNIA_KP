<?php
$title = $title ?? 'Event';
$q     = $q ?? '';

$available   = $available   ?? [];
$closed      = $closed      ?? [];
$statusIndex = $statusIndex ?? [];

// mapper warna badge untuk chip status
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

// helper render small meta tanggal
$fmtDate = function ($date, $withTime = false) {
  if (!$date) return '-';
  $ts = strtotime($date);
  return $withTime ? date('d M Y H:i', $ts) : date('d M Y', $ts);
};
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <!-- Header Biru -->
      <div class="header-section header-blue d-flex justify-content-between align-items-center mb-3">
        <div>
          <h3 class="welcome-text mb-1"><i class="bi bi-calendar3 me-2"></i>Event</h3>
          <div class="text-white-50">Daftar & ikuti alur presenter</div>
        </div>
        <div class="text-end d-none d-md-block">
          <small class="text-white-50 d-block">Hari ini</small>
          <strong class="text-white"><?= date('d M Y') ?></strong>
        </div>
      </div>

      <!-- Search Box (compact) -->
      <div class="card shadow-sm mb-3 border-0 overflow-hidden">
        <div class="card-header bg-gradient-primary text-white">
          <div class="d-flex align-items-center justify-content-between">
            <h6 class="mb-0"><i class="bi bi-search me-2"></i>Cari Event</h6>
          </div>
        </div>
        <div class="card-body search-compact">
          <form class="row g-2 align-items-center" method="get" action="/presenter/events">
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
      <div class="card shadow-sm mb-4 border-0 overflow-hidden">
        <div class="card-header bg-gradient-primary text-white">
          <h5 class="mb-0"><i class="bi bi-lightning-charge me-2"></i>Event Tersedia (Buka Pendaftaran)</h5>
        </div>
        <div class="card-body">
          <?php if (empty($available)): ?>
            <div class="text-muted">Tidak ada event yang membuka pendaftaran.</div>
          <?php else: ?>
            <div class="row g-3">
              <?php foreach ($available as $e):
                $evId  = (int)$e['id'];
                $ui    = $statusIndex[$evId]['ui'] ?? [];
                $state = $statusIndex[$evId]['state'] ?? 'belum_daftar';
                $label = $statusIndex[$evId]['label'] ?? '';
                $hint  = $statusIndex[$evId]['hint']  ?? '';
                $showChip  = (bool)($ui['show_chip'] ?? false);
                $chipClass = $ui['chip_class'] ?? ('bg-'.$stateBadge($state).'-subtle text-'.$stateBadge($state));
                $cta = $ui['cta'] ?? [
                  'visible' => false,
                  'label'   => 'Detail Event',
                  'url'     => site_url('presenter/events/detail/'.$evId),
                  'class'   => 'btn-outline-primary',
                  'disabled'=> false,
                ];
              ?>
              <div class="col-12 col-md-6 col-xl-4">
                <div class="event-card h-100 shadow-sm">
                  <div class="d-flex align-items-start justify-content-between mb-2">
                    <h5 class="mb-0"><?= esc($e['title']) ?></h5>
                    <span class="badge bg-success">Tersedia</span>
                  </div>

                  <div class="small text-muted mb-3">
                    Mulai: <strong><?= $fmtDate($e['event_date']) ?> <?= esc($e['event_time']) ?></strong><br>
                    Tutup Daftar: <strong><?= $fmtDate($e['registration_deadline'], true) ?></strong><br>
                    Batas Abstrak: <strong><?= $fmtDate($e['abstract_deadline'], true) ?></strong><br>
                    <?php if (!empty($e['full_paper_deadline'])): ?>
                      Batas Full Paper: <strong><?= $fmtDate($e['full_paper_deadline'], true) ?></strong>
                    <?php endif; ?>
                  </div>

                  <?php if ($showChip): ?>
                    <div class="mb-2">
                      <span class="badge rounded-pill <?= esc($chipClass) ?> fw-normal">
                        <?= esc($label) ?>
                      </span>
                      <?php if ($hint): ?>
                        <div class="text-muted small mt-1"><?= esc($hint) ?></div>
                      <?php endif; ?>
                    </div>
                  <?php endif; ?>

                  <div class="d-flex align-items-center justify-content-between gap-2">
                    <a
                      class="btn <?= esc($cta['class'] ?? 'btn-outline-primary') ?> flex-fill <?= !empty($cta['disabled']) ? 'disabled' : '' ?>"
                      href="<?= esc($cta['url'] ?? site_url('presenter/events/detail/'.$evId)) ?>"
                      <?php if (!empty($cta['disabled'])): ?> aria-disabled="true"<?php endif; ?>
                    >
                      <?php
                        $lbl = strtolower((string)($cta['label'] ?? ''));
                        $icon = 'bi-info-circle';
                        if (str_contains($lbl,'daftar')) $icon='bi-box-arrow-in-right';
                        elseif (str_contains($lbl,'kontributor')) $icon='bi-people';
                        elseif (str_contains($lbl,'upload')) $icon='bi-upload';
                        elseif (str_contains($lbl,'bayar')) $icon='bi-credit-card';
                        elseif (str_contains($lbl,'cek') || str_contains($lbl,'status')) $icon='bi-clock-history';
                        elseif (str_contains($lbl,'lihat')) $icon='bi-eye';
                      ?>
                      <i class="bi <?= $icon ?> me-1"></i><?= esc($cta['label'] ?? 'Detail') ?>
                    </a>

                    <a class="text-decoration-none small text-muted" href="/presenter/events/detail/<?= $evId ?>">
                      Detail
                      <i class="bi bi-chevron-right ms-1"></i>
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
      <div class="card shadow-sm mb-4 border-0 overflow-hidden">
        <div class="card-header bg-gradient-primary text-white">
          <h5 class="mb-0"><i class="bi bi-lock me-2"></i>Event Ditutup</h5>
        </div>
        <div class="card-body">
          <?php if (empty($closed)): ?>
            <div class="text-muted">Belum ada event yang ditutup.</div>
          <?php else: ?>
            <div class="row g-3">
              <?php foreach ($closed as $e):
                $evId  = (int)$e['id'];
                $ui    = $statusIndex[$evId]['ui'] ?? [];
                $state = $statusIndex[$evId]['state'] ?? 'belum_daftar';
                $label = $statusIndex[$evId]['label'] ?? '';
                $hint  = $statusIndex[$evId]['hint']  ?? '';
                $showChip  = $ui['show_chip'] ?? false;
                $chipClass = $ui['chip_class'] ?? ('bg-'.$stateBadge($state).'-subtle text-'.$stateBadge($state));
                $cta = $ui['cta'] ?? [
                  'visible' => true,
                  'label'   => 'Detail Event',
                  'url'     => site_url('presenter/events/detail/'.$evId),
                  'class'   => 'btn-outline-secondary',
                  'disabled'=> false,
                ];
              ?>
              <div class="col-12 col-md-6 col-xl-4">
                <div class="event-card h-100 shadow-sm opacity-90">
                  <div class="d-flex align-items-start justify-content-between mb-2">
                    <h5 class="mb-0"><?= esc($e['title']) ?></h5>
                    <span class="badge bg-secondary">Ditutup</span>
                  </div>

                  <div class="small text-muted mb-3">
                    Mulai: <strong><?= $fmtDate($e['event_date']) ?> <?= esc($e['event_time']) ?></strong><br>
                    Tutup Daftar: <strong><?= $fmtDate($e['registration_deadline'], true) ?></strong><br>
                    Batas Abstrak: <strong><?= $fmtDate($e['abstract_deadline'], true) ?></strong><br>
                    <?php if (!empty($e['full_paper_deadline'])): ?>
                      Batas Full Paper: <strong><?= $fmtDate($e['full_paper_deadline'], true) ?></strong>
                    <?php endif; ?>
                  </div>

                  <?php if ($showChip): ?>
                    <div class="mb-2">
                      <span class="badge rounded-pill <?= esc($chipClass) ?> fw-normal">
                        <?= esc($label) ?>
                      </span>
                      <?php if ($hint): ?>
                        <div class="text-muted small mt-1"><?= esc($hint) ?></div>
                      <?php endif; ?>
                    </div>
                  <?php endif; ?>

                  <div class="d-flex align-items-center justify-content-between gap-2">
                    <a
                      class="btn <?= esc($cta['class'] ?? 'btn-outline-secondary') ?> flex-fill <?= !empty($cta['disabled']) ? 'disabled' : '' ?>"
                      href="<?= esc($cta['url'] ?? site_url('presenter/events/detail/'.$evId)) ?>"
                      <?php if (!empty($cta['disabled'])): ?> aria-disabled="true"<?php endif; ?>
                    >
                      <?php
                        $lbl = strtolower((string)($cta['label'] ?? ''));
                        $icon = 'bi-info-circle';
                        if (str_contains($lbl,'kontributor')) $icon='bi-people';
                        elseif (str_contains($lbl,'upload')) $icon='bi-upload';
                        elseif (str_contains($lbl,'bayar')) $icon='bi-credit-card';
                        elseif (str_contains($lbl,'cek') || str_contains($lbl,'status')) $icon='bi-clock-history';
                        elseif (str_contains($lbl,'lihat')) $icon='bi-eye';
                      ?>
                      <i class="bi <?= $icon ?> me-1"></i><?= esc($cta['label'] ?? 'Detail') ?>
                    </a>

                    <a class="text-decoration-none small text-muted" href="/presenter/events/detail/<?= $evId ?>">
                      Detail
                      <i class="bi bi-chevron-right ms-1"></i>
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
  /* ===== Design Tokens (palette & radii) ===== */
  :root{
    --primary-50:#eff6ff; --primary-100:#dbeafe; --primary-200:#bfdbfe;
    --primary-300:#93c5fd; --primary-400:#60a5fa; --primary-500:#3b82f6;
    --primary-600:#2563eb; --primary-700:#1d4ed8; --primary-800:#1e40af; --primary-900:#1e3a8a;

    --teal-500:#06b6d4; --teal-600:#0891b2;
    --success-600:#10b981; --warning-600:#d97706; --danger-600:#ef4444; --slate-600:#475569;

    --card-r:16px; --chip-r:999px; --ring:0 0 0 .22rem rgba(37,99,235,.18);
  }

  /* ===== Page background ===== */
  body{
    background:
      radial-gradient(1200px 380px at 10% -20%, var(--primary-200) 0, #fff 45%) fixed;
  }

  /* ===== Hero / Header biru ===== */
  .header-section.header-blue{
    position:relative;
    background:
      radial-gradient(150% 120% at 10% -40%, var(--primary-600), var(--primary-700) 45%, var(--primary-800) 95%);
    color:#fff;
    padding:24px;
    border-radius:18px;
    border:1px solid rgba(255,255,255,.16);
    box-shadow:
      0 18px 40px rgba(29,78,216,.18),
      inset 0 0 50px rgba(255,255,255,.06);
    overflow:hidden;
  }
  .header-section.header-blue:after{
    content:"";
    position:absolute; inset:0;
    background:linear-gradient(90deg, transparent, rgba(255,255,255,.06), transparent 60%);
    transform:skewY(-6deg); pointer-events:none;
  }
  .welcome-text{ font-weight:800; letter-spacing:.2px; }

  /* ===== Gradient header on cards ===== */
  .bg-gradient-primary{
    background:linear-gradient(135deg, var(--primary-600), var(--teal-500))!important;
    color:#fff;
    border-bottom:1px solid rgba(255,255,255,.18);
    padding:12px 16px;
  }

  /* ===== Generic card polish ===== */
  .card{
    border-radius:var(--card-r);
    border:1px solid rgba(30,41,59,.06);
    box-shadow:0 10px 26px rgba(2,8,23,.06);
  }
  .card:hover{ box-shadow:0 14px 30px rgba(2,8,23,.10); transition:box-shadow .2s ease; }

  /* ===== Event card ===== */
  .event-card{
    background:linear-gradient(180deg,#fff, rgba(255,255,255,.96));
    border:1px solid rgba(30,41,59,.08);
    border-radius:18px;
    padding:16px;
    box-shadow:0 12px 26px rgba(2,8,23,.06);
    transition:transform .18s ease, box-shadow .18s ease, border-color .18s ease;
  }
  .event-card:hover{
    transform:translateY(-2px);
    border-color:rgba(37,99,235,.25);
    box-shadow:0 18px 36px rgba(2,8,23,.12);
  }
  .opacity-90{ opacity:.92; }

  /* ===== Search compact ===== */
  .search-compact .form-control,
  .search-compact .btn{
    height:44px; border-radius:12px; font-size:.96rem;
  }
  .search-compact .form-control{
    background:#fff; border:1px solid rgba(2,8,23,.12);
  }
  .search-compact .form-control:focus{
    border-color:var(--primary-400); box-shadow:var(--ring);
  }

  /* ===== Buttons ===== */
  .btn-primary{ background:var(--primary-600); border-color:var(--primary-600); }
  .btn-primary:hover{ background:var(--primary-700); border-color:var(--primary-700); }
  .btn-outline-primary{ color:var(--primary-700); border-color:var(--primary-200); }
  .btn-outline-primary:hover{ background:var(--primary-50); border-color:var(--primary-300); color:var(--primary-800); }
  .btn:focus{ box-shadow:var(--ring); }

  /* ===== Subtle badges (fallback untuk Bootstrap <5.3) ===== */
  .badge{ border-radius:10px; font-weight:600; letter-spacing:.1px; }
  .bg-primary-subtle{ background: #e9f1ff !important; color: var(--primary-700) !important; }
  .bg-info-subtle{ background:#e6fbff !important; color: var(--teal-600) !important; }
  .bg-success-subtle{ background:#eafaf3 !important; color:var(--success-600)!important; }
  .bg-warning-subtle{ background:#fff7e8 !important; color:var(--warning-600)!important; }
  .bg-danger-subtle{ background:#ffe9ec !important; color:var(--danger-600)!important; }
  .bg-secondary-subtle{ background:#f1f5f9 !important; color:var(--slate-600)!important; }

  /* ===== Chip style (untuk label state di card) ===== */
  .badge.rounded-pill{ border-radius:var(--chip-r)!important; padding:.35rem .6rem; }

  /* ===== Small helpers ===== */
  .text-blue-900{ color:var(--primary-900)!important; }
  .text-white-50{ color:rgba(255,255,255,.8)!important; }
  .ratio > iframe{ border-radius:12px; }

  /* ===== Responsive ===== */
  @media (max-width: 767.98px){
    .header-section.header-blue{ padding:18px; border-radius:16px; }
    .search-compact .form-control, .search-compact .btn{ height: 42px; }
  }
</style>