<?php
  $title  = $title ?? 'Event Presenter';
  $events = $events ?? [];
  $q      = $q ?? '';

  $fmtDate = fn($s)=> $s ? date('d M Y', strtotime($s)) : '-';
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <!-- HERO -->
      <div class="abs-hero mb-3">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
          <div>
            <div class="abs-title mb-1">Event</div>
            <div class="abs-sub">Alur presenter: upload abstrak → Di-ACC → bayar → verifikasi.</div>
          </div>
          <form class="d-flex" method="get" action="<?= site_url('presenter/events') ?>">
            <input type="search" name="q" value="<?= esc($q) ?>" class="form-control form-control-sm" placeholder="Cari judul / lokasi...">
          </form>
        </div>
      </div>

      <div class="card shadow-sm border-0">
        <div class="card-body">
          <div class="d-flex align-items-center justify-content-between">
            <h5 class="card-title mb-0">Event Aktif</h5>
            <span class="badge bg-primary-subtle text-primary"><?= count($events) ?></span>
          </div>
          <hr class="my-3">

          <?php if (!empty($events)): ?>
            <div class="row g-2 g-md-3">
              <?php foreach ($events as $e): ?>
                <?php
                  $s          = $e['state'] ?? [];
                  $tgl        = $fmtDate($e['event_date'] ?? null);
                  $jam        = $e['event_time'] ?? '-';
                  $registered = (bool)($s['registered'] ?? false);
                  $verified   = (bool)($s['verified'] ?? false);
                  $regOpen    = (bool)($e['reg_open'] ?? false);

                  // event format for audience (online/offline/both)
                  $fmtEvent   = strtoupper($e['format'] ?? '-');
                  if ($fmtEvent === 'BOTH') $fmtEvent = 'HYBRID';

                  $abLabel    = $registered ? ($e['abstract_badge'] ?? null) : null;
                  $payLbl     = $registered ? ($e['payment_badge'] ?? null)  : null;

                  $statusKey  = (string)($s['status_key'] ?? '');
                  $statusLab  = $registered ? ((string)($s['status_label'] ?? '')) : '';
                  $statusCls  = match ($statusKey) {
                    'abstract_pending','payment_pending'     => 'bg-info',
                    'payment_required'                       => 'bg-primary',
                    'payment_rejected','abstract_rejected'   => 'bg-danger',
                    'abstract_revision'                      => 'bg-warning',
                    'completed'                              => 'bg-success',
                    default                                  => 'bg-secondary'
                  };

                  // ribbon for registered
                  $ribbon = '';
                  if ($verified) $ribbon = '<span class="ribbon bg-success">Terverifikasi</span>';
                  elseif ($registered) $ribbon = '<span class="ribbon bg-primary">Terdaftar</span>';
                ?>
                <div class="col-12 col-md-6 col-lg-4">
                  <div class="card h-100 abs-card position-relative">
                    <?= $ribbon ?>
                    <div class="card-body d-flex flex-column">
                      <div class="d-flex justify-content-between align-items-start">
                        <div class="fw-semibold me-2 abs-card-title text-truncate" title="<?= esc($e['title']) ?>">
                          <?= esc($e['title']) ?>
                        </div>
                        <?php if ($registered && $statusLab): ?>
                          <span class="badge <?= $statusCls ?>"><?= esc($statusLab) ?></span>
                        <?php endif; ?>
                      </div>

                      <div class="mt-1 small text-muted">
                        <?= esc($tgl) ?> · <?= esc($jam) ?>
                        · Format Event: <?= esc($fmtEvent) ?>
                        <?php if (!empty($e['location'])): ?> · Lokasi: <?= esc($e['location']) ?><?php endif; ?>
                        · Mode Presenter: OFFLINE
                      </div>

                      <?php if ($registered): ?>
                        <div class="small mt-2">
                          <?php if ($abLabel): ?>
                            <span class="badge bg-secondary-subtle text-secondary me-1">Abstrak: <?= esc($abLabel) ?></span>
                          <?php endif; ?>
                          <?php if ($payLbl): ?>
                            <span class="badge bg-secondary-subtle text-secondary">Pembayaran: <?= esc(ucfirst($payLbl)) ?></span>
                          <?php endif; ?>
                        </div>
                      <?php endif; ?>

                      <div class="mt-auto pt-3 d-grid">
                        <?php if ($verified): ?>
                          <button class="btn btn-success" type="button" disabled>
                            <i class="bi bi-check2-circle me-1"></i> Sudah Terverifikasi
                          </button>

                        <?php elseif (!$regOpen): ?>
                          <button class="btn btn-secondary" type="button" disabled>
                            Pendaftaran Ditutup
                          </button>

                        <?php elseif ($registered): ?>
                          <a href="<?= site_url('presenter/events/detail/'.$e['id']) ?>" class="btn btn-outline-primary">
                            <i class="bi bi-arrow-right-circle me-1"></i> Lihat Detail
                          </a>

                        <?php else: ?>
                          <button
                            class="btn btn-primary btn-daftar"
                            type="button"
                            data-id="<?= (int)$e['id'] ?>"
                            data-title="<?= esc($e['title']) ?>"
                            data-date="<?= esc($tgl) ?>"
                            data-url="<?= site_url('presenter/events/register/'.(int)$e['id']) ?>"
                          >
                            <i class="bi bi-pencil-square me-1"></i>Daftar
                          </button>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="p-4 text-center border rounded-3 bg-light-subtle">
              <div class="mb-2"><i class="bi bi-calendar2-event fs-3 text-secondary"></i></div>
              <div class="fw-semibold">Belum ada event aktif</div>
              <div class="text-muted small">Nantikan informasi event terbaru.</div>
            </div>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </main>
</div>

<!-- Modal Konfirmasi -->
<div class="modal fade" id="confirmDaftarModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title">Konfirmasi Pendaftaran</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2">
          Kamu akan memulai pendaftaran sebagai <strong>Presenter (OFFLINE)</strong> untuk:
        </div>
        <div class="p-2 rounded bg-light mb-2">
          <div class="fw-semibold" id="cd-title">Event</div>
          <div class="small text-muted" id="cd-date">Tanggal</div>
        </div>
        <ul class="small mb-0">
          <li>Pendaftaran dimulai dengan <strong>upload abstrak</strong>.</li>
          <li>Jika abstrak <strong>Di-ACC</strong>, lanjut ke pembayaran.</li>
          <li>Kamu <strong>bisa membatalkan</strong> pendaftaran selama <em>belum ada pembayaran terverifikasi</em>.</li>
          <li>Tidak ada biaya sebelum melakukan pembayaran.</li>
        </ul>
      </div>
      <div class="modal-footer">
        <button class="btn btn-light" data-bs-dismiss="modal">Batal</button>
        <a id="cd-continue" href="#" class="btn btn-primary">Lanjutkan</a>
      </div>
    </div>
  </div>
</div>

<?= $this->include('partials/footer') ?>

<style>
  .abs-hero{
    background: linear-gradient(90deg,#2563eb,#60a5fa);
    border-radius:16px; color:#fff; padding:14px 16px;
    box-shadow: 0 6px 20px rgba(37,99,235,.18);
  }
  .abs-title{ font-weight:800; line-height:1.2; font-size: clamp(18px,4.2vw,24px); }
  .abs-sub{ opacity:.9; font-size:.95rem; }
  @media (min-width: 576px){
    .abs-hero{ padding:18px 20px; border-radius:18px; }
  }

  .abs-card{
    border:0; background:#fff; transition:.18s ease;
    box-shadow:0 6px 18px rgba(0,0,0,.06); border-radius:14px;
  }
  .abs-card:hover{ transform: translateY(-2px); box-shadow:0 10px 24px rgba(0,0,0,.08); }
  .abs-card-title{ max-width: 75%; }

  /* ribbon "Terdaftar/Terverifikasi" */
  .ribbon{
    position:absolute; top:12px; left:-8px;
    padding:.25rem .55rem; color:#fff; font-size:.75rem; font-weight:600;
    border-top-right-radius:6px; border-bottom-right-radius:6px;
    box-shadow:0 4px 10px rgba(0,0,0,.08);
  }
</style>

<script>
(function(){
  const modalEl = document.getElementById('confirmDaftarModal');
  const titleEl = document.getElementById('cd-title');
  const dateEl  = document.getElementById('cd-date');
  const contEl  = document.getElementById('cd-continue');
  const bsModal = new bootstrap.Modal(modalEl);

  // cegah modal muncul jika tombol disabled (mis. pendaftaran ditutup)
  document.querySelectorAll('.btn-daftar').forEach(btn=>{
    btn.addEventListener('click', function(){
      if (this.disabled || this.classList.contains('disabled')) return;
      titleEl.textContent = this.dataset.title || 'Event';
      dateEl.textContent  = this.dataset.date || '';
      contEl.setAttribute('href', this.dataset.url || '#');
      bsModal.show();
    });
  });
})();
</script>
