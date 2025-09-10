<?php
  /** @var array $event, $state */
  $e = $event ?? [];
  $s = $state ?? [];

  $fmtDate = fn($s)=> $s ? date('d M Y', strtotime($s)) : '-';
  $tgl     = $fmtDate($e['event_date'] ?? null);
  $jam     = $e['event_time'] ?? '-';

  $step    = (int)($s['step'] ?? 1);
  $sKey    = (string)($s['status_key'] ?? '');
  $sLab    = (string)($s['status_label'] ?? '');
  $ctaTxt  = (string)($s['cta_text'] ?? 'Kembali');
  $ctaUrl  = (string)($s['cta_url'] ?? site_url('presenter/events'));

  $stepClass = match ($sKey) {
    'abstract_required','abstract_revision','abstract_rejected' => 'bg-warning',
    'abstract_pending','payment_pending'                       => 'bg-info',
    'payment_required'                                         => 'bg-primary',
    'payment_rejected'                                         => 'bg-danger',
    'completed'                                                => 'bg-success',
    default                                                    => 'bg-secondary'
  };

  $absBadge = $s['abstract_label'] ?? null;
  $payBadge = $s['payment_label'] ?? null;
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <div class="d-flex align-items-center mb-3">
        <a href="<?= site_url('presenter/events') ?>" class="btn btn-light me-2"><i class="bi bi-arrow-left"></i></a>
        <div>
          <h3 class="mb-0"><?= esc($e['title'] ?? 'Event') ?></h3>
          <small class="text-muted">Workflow presenter untuk event ini.</small>
        </div>
      </div>

      <div class="row g-3">
        <div class="col-12 col-lg-8">
          <div class="card shadow-sm border-0">
            <div class="card-body">

              <!-- HERO -->
              <div class="abs-hero mb-3">
                <div class="d-flex flex-column flex-md-row align-items-md-start justify-content-between gap-2">
                  <div>
                    <div class="abs-title mb-1"><?= esc($e['title'] ?? 'Event') ?></div>
                    <div class="abs-tags">
                      <span class="abs-tag"><i class="bi bi-calendar-event"></i> <?= esc($tgl) ?></span>
                      <span class="abs-tag"><i class="bi bi-clock"></i> <?= esc($jam) ?></span>
                      <span class="abs-tag"><i class="bi bi-geo-alt"></i> <?= esc($e['location'] ?? '-') ?></span>
                      <span class="abs-tag"><i class="bi bi-people"></i> OFFLINE</span>
                    </div>
                  </div>
                  <div class="text-md-end">
                    <div class="small opacity-75">Status</div>
                    <span class="badge <?= $stepClass ?> text-uppercase"><?= esc($sLab) ?></span>
                  </div>
                </div>
              </div>

              <!-- Ringkasan status -->
              <div class="d-flex flex-wrap gap-2 mb-3">
                <span class="badge bg-secondary-subtle text-secondary">
                  Abstrak: <?= esc($absBadge ?? 'Belum ada') ?>
                </span>
                <?php if ($payBadge): ?>
                  <span class="badge bg-secondary-subtle text-secondary">
                    Pembayaran: <?= esc(ucfirst($payBadge)) ?>
                  </span>
                <?php endif; ?>
              </div>

              <!-- CTA -->
              <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-primary" href="<?= esc($ctaUrl) ?>">
                  <i class="bi bi-arrow-right-circle me-1"></i><?= esc($ctaTxt) ?>
                </a>
                <?php if (($s['verified'] ?? false) === true): ?>
                  <a class="btn btn-outline-secondary" href="<?= site_url('presenter/absensi') ?>">
                    <i class="bi bi-qr-code-scan me-1"></i>Absensi
                  </a>
                  <a class="btn btn-outline-success" href="<?= site_url('presenter/dokumen/loa') ?>">
                    <i class="bi bi-file-earmark-text me-1"></i>LOA
                  </a>
                <?php endif; ?>
              </div>

              <div class="mt-3 small text-muted">
                Langkah: 1) Upload abstrak → 2) Review → 3) Pembayaran → 4) Verifikasi → 5) Ikut event.
              </div>

            </div>
          </div>
        </div>

        <div class="col-12 col-lg-4">
          <div class="card shadow-sm border-0">
            <div class="card-body">
              <div class="fw-semibold mb-2">Info</div>
              <ul class="small mb-0">
                <li>Presenter mengikuti event secara <strong>OFFLINE</strong>.</li>
                <li>Fitur pembayaran muncul setelah abstrak <strong>Di-ACC</strong>.</li>
                <li>Setelah pembayaran <em>verified</em>, absensi & LOA aktif.</li>
              </ul>
            </div>
          </div>
        </div>

      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<style>
  .abs-hero{
    background: linear-gradient(90deg,#2563eb,#60a5fa);
    border-radius:16px; color:#fff; padding:14px 16px;
    box-shadow: 0 6px 20px rgba(37,99,235,.18);
  }
  .abs-title{ font-weight:800; line-height:1.2; font-size: clamp(18px,4.2vw,24px); }
  .abs-tags{ display:flex; gap:.4rem; flex-wrap:wrap; margin-top:.25rem; }
  .abs-tag{
    background:rgba(255,255,255,.16); border:1px solid rgba(255,255,255,.22);
    color:#fff; border-radius:999px; padding:.28rem .6rem; font-size:.85rem;
    display:inline-flex; align-items:center; gap:.45rem;
  }
  @media (min-width:576px){
    .abs-hero{ padding:18px 20px; border-radius:18px; }
  }
</style>
