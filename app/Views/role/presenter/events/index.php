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
                // chip
                $showChip  = (bool)($ui['show_chip'] ?? false);
                $chipClass = $ui['chip_class'] ?? ('bg-'.$stateBadge($state).'-subtle text-'.$stateBadge($state));
                // CTA utama (satu tombol)
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
                    <!-- Satu tombol utama -->
                    <a
                      class="btn <?= esc($cta['class'] ?? 'btn-outline-primary') ?> flex-fill <?= !empty($cta['disabled']) ? 'disabled' : '' ?>"
                      href="<?= esc($cta['url'] ?? site_url('presenter/events/detail/'.$evId)) ?>"
                      <?php if (!empty($cta['disabled'])): ?> aria-disabled="true"<?php endif; ?>
                    >
                      <?php
                        // ikon ringan sesuai label
                        $lbl = strtolower((string)($cta['label'] ?? ''));
                        $icon = 'bi-info-circle';
                        if (str_contains($lbl,'daftar')) $icon='bi-box-arrow-in-right';
                        elseif (str_contains($lbl,'upload')) $icon='bi-upload';
                        elseif (str_contains($lbl,'bayar')) $icon='bi-credit-card';
                        elseif (str_contains($lbl,'cek') || str_contains($lbl,'status')) $icon='bi-clock-history';
                        elseif (str_contains($lbl,'lihat')) $icon='bi-eye';
                      ?>
                      <i class="bi <?= $icon ?> me-1"></i><?= esc($cta['label'] ?? 'Detail') ?>
                    </a>

                    <!-- Link kecil ke detail (bukan tombol kedua) -->
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
                    <!-- Satu tombol utama -->
                    <a
                      class="btn <?= esc($cta['class'] ?? 'btn-outline-secondary') ?> flex-fill <?= !empty($cta['disabled']) ? 'disabled' : '' ?>"
                      href="<?= esc($cta['url'] ?? site_url('presenter/events/detail/'.$evId)) ?>"
                      <?php if (!empty($cta['disabled'])): ?> aria-disabled="true"<?php endif; ?>
                    >
                      <?php
                        $lbl = strtolower((string)($cta['label'] ?? ''));
                        $icon = 'bi-info-circle';
                        if (str_contains($lbl,'daftar')) $icon='bi-box-arrow-in-right';
                        elseif (str_contains($lbl,'upload')) $icon='bi-upload';
                        elseif (str_contains($lbl,'bayar')) $icon='bi-credit-card';
                        elseif (str_contains($lbl,'cek') || str_contains($lbl,'status')) $icon='bi-clock-history';
                        elseif (str_contains($lbl,'lihat')) $icon='bi-eye';
                      ?>
                      <i class="bi <?= $icon ?> me-1"></i><?= esc($cta['label'] ?? 'Detail') ?>
                    </a>

                    <!-- Link kecil ke detail -->
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
  :root{
    --primary-color:#2563eb; --info-color:#06b6d4; --success-color:#10b981; --secondary:#475569;
  }
  .header-section.header-blue{
    background:linear-gradient(135deg,var(--primary-color),#1e40af);
    color:#fff; padding:24px; border-radius:16px; box-shadow:0 8px 28px rgba(0,0,0,.12);
  }
  .welcome-text{ font-weight:700; }
  .bg-gradient-primary{
    background:linear-gradient(135deg,var(--primary-color),var(--info-color))!important;
  }
  .event-card{
    background:#fff; border-radius:14px; padding:16px; border:1px solid #eef2f7;
  }
  .opacity-90{ opacity:.92; }

  /* Compact search */
  .search-compact .form-control,
  .search-compact .btn{
    height: 42px;
    border-radius: 10px;
    font-size: .95rem;
  }
  .search-compact .form-control{ padding: .45rem .75rem; }

  /* Fallback -subtle */
  .bg-primary-subtle{ background:#e7f0ff !important; color:#2563eb !important;}
  .bg-info-subtle{ background:#e6f9fc !important; color:#06b6d4 !important;}
  .bg-warning-subtle{ background:#fff7e6 !important; color:#d97706 !important;}
  .bg-danger-subtle{ background:#ffe9e9 !important; color:#ef4444 !important;}
  .bg-secondary-subtle{ background:#f1f5f9 !important; color:#475569 !important;}
  .bg-success-subtle{ background:#eafaf3 !important; color:#0f766e !important;}
  .text-primary{ color:#2563eb !important;}
  .text-info{ color:#06b6d4 !important;}
  .text-warning{ color:#d97706 !important;}
  .text-danger{ color:#ef4444 !important;}
  .text-secondary{ color:#475569 !important;}
  .text-success{ color:#0f766e !important;}

  @media (max-width: 767.98px){
    .header-section.header-blue{ padding:18px; }
    .search-compact .form-control,
    .search-compact .btn{ height: 40px; font-size: .92rem; }
  }
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
