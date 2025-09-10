<?php
  /** @var array $event, $abstract, $payment */
  $e = $event ?? [];
  $fmtDate = fn($s)=> $s ? date('d M Y', strtotime($s)) : '-';
  $tgl = $fmtDate($e['event_date'] ?? null);
  $jam = $e['event_time'] ?? '-';
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
          <h3 class="mb-0">Konfirmasi Pendaftaran</h3>
          <small class="text-muted">Presenter OFFLINE</small>
        </div>
      </div>

      <div class="card shadow-sm border-0">
        <div class="card-body">
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
            </div>
          </div>

          <div class="row g-3">
            <div class="col-12 col-lg-8">
              <div class="border rounded p-3">
                <div class="fw-semibold mb-2">Sebelum lanjut</div>
                <ul class="mb-0">
                  <li>Pendaftaran presenter dimulai dengan <strong>upload abstrak</strong>.</li>
                  <li>Jika abstrak <strong>Di-ACC</strong>, kamu akan diminta melakukan pembayaran.</li>
                  <li>Kamu <strong>bisa membatalkan</strong> pendaftaran kapan saja <em>selama belum ada pembayaran yang terverifikasi</em>.</li>
                  <li>Tidak ada biaya apapun sampai kamu melakukan pembayaran.</li>
                </ul>
              </div>
            </div>
            <div class="col-12 col-lg-4">
              <form method="post" action="<?= site_url('presenter/events/register/'.(int)($e['id'] ?? 0)) ?>">
                <?= csrf_field() ?>
                <div class="d-grid gap-2">
                  <button type="submit" class="btn btn-primary" onclick="return confirm('Mulai pendaftaran dan lanjut ke upload abstrak?')">
                    <i class="bi bi-upload me-1"></i> Mulai Upload Abstrak
                  </button>
                  <a href="<?= site_url('presenter/events/detail/'.(int)($e['id'] ?? 0)) ?>" class="btn btn-outline-secondary">
                    Lihat Detail Event
                  </a>
                </div>
              </form>
              <div class="form-text mt-2">
                Dengan melanjutkan, kamu setuju mengikuti alur pendaftaran presenter.
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
