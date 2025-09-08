<?php
  $title   = 'Detail Event';
  $event   = $event  ?? [];
  $isOpen  = $isOpen ?? false;
  $myReg   = $myReg  ?? null;
  $options = $options ?? [];
  $pricing = $pricing ?? []; // matrix: ['audience'=>['online'=>..., 'offline'=>...]]
  $payId   = $myReg['id_pembayaran'] ?? ($myReg['id'] ?? null);

  $fmtDate = function($d){ return $d ? date('d M Y', strtotime($d)) : '-'; };
  $rupiah  = function($n){ return ($n===null||$n==='') ? '—' : 'Rp '.number_format((float)$n,0,',','.'); };

  $priceOnline  = $pricing['audience']['online']  ?? null;
  $priceOffline = $pricing['audience']['offline'] ?? null;

  $isToday = isset($event['event_date']) && date('Y-m-d', strtotime($event['event_date'])) === date('Y-m-d');
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_audience') ?>
<?= $this->include('partials/alerts') ?>

<style>
  .hero{
    /* 💙 gradient biru */
    background: linear-gradient(135deg,#0ea5e9,#2563eb);
    color:#fff; border-radius:16px;
  }
  .hero .chip{
    background:rgba(255,255,255,.18);
    border:1px solid rgba(255,255,255,.25);
    border-radius:999px; padding:.35rem .7rem; font-size:.8rem
  }
  .info-tile{background:#fff; border:1px solid #eef0f4; border-radius:12px}
  .price-badge{font-weight:600}
  .muted{color:#6b7280}
</style>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <!-- HERO -->
      <div class="hero p-3 p-md-4 mb-3 shadow-sm">
        <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
          <div>
            <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
              <span class="chip"><i class="bi bi-calendar2-week me-1"></i><?= esc($fmtDate($event['event_date'] ?? null)) ?></span>
              <span class="chip"><i class="bi bi-clock me-1"></i><?= esc($event['event_time'] ?? '-') ?></span>
              <span class="chip"><i class="bi bi-broadcast-pin me-1"></i><?= esc(strtoupper($event['format'] ?? '-')) ?></span>
              <?php if (!empty($event['location'])): ?>
                <span class="chip"><i class="bi bi-geo-alt me-1"></i><?= esc($event['location']) ?></span>
              <?php endif; ?>
              <?php if ($isToday): ?>
                <span class="chip bg-warning text-dark border-0"><i class="bi bi-star me-1"></i>Hari ini</span>
              <?php endif; ?>
            </div>
            <h3 class="mb-0"><?= esc($event['title'] ?? 'Event') ?></h3>
          </div>

          <div class="d-flex align-items-center gap-2">
            <?php if ($myReg): ?>
              <span class="chip bg-success text-white border-0"><i class="bi bi-check2-circle me-1"></i>Sudah terdaftar</span>
            <?php elseif ($isOpen): ?>
              <span class="chip bg-info text-white border-0"><i class="bi bi-unlock me-1"></i>Pendaftaran dibuka</span>
            <?php else: ?>
              <span class="chip bg-secondary border-0"><i class="bi bi-lock me-1"></i>Pendaftaran ditutup</span>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <!-- GRID -->
      <div class="row g-3">
        <!-- Kiri: Deskripsi & Harga -->
        <div class="col-12 col-lg-8">
          <div class="info-tile p-3 p-md-4 shadow-sm mb-3">
            <h5 class="mb-2">Deskripsi</h5>
            <?php if (!empty($event['description'])): ?>
              <div class="muted"><?= nl2br(esc($event['description'])) ?></div>
            <?php else: ?>
              <div class="muted">Belum ada deskripsi.</div>
            <?php endif; ?>
          </div>

          <div class="info-tile p-3 p-md-4 shadow-sm">
            <h5 class="mb-3">Harga Audience</h5>
            <div class="d-flex flex-wrap gap-2">
              <span class="badge bg-info-subtle text-info price-badge">
                <i class="bi bi-wifi me-1"></i>Online: <?= $rupiah($priceOnline) ?>
              </span>
              <span class="badge bg-primary-subtle text-primary price-badge">
                <i class="bi bi-people me-1"></i>Offline: <?= $rupiah($priceOffline) ?>
              </span>
            </div>
          </div>
        </div>

        <!-- Kanan: CTA -->
        <div class="col-12 col-lg-4">
          <div class="info-tile p-3 p-md-4 shadow-sm">
            <h5 class="mb-3">Aksi</h5>

            <?php if ($myReg): ?>
              <div class="alert alert-info">
                Kamu sudah terdaftar (mode:
                <b><?= esc(strtoupper($myReg['mode_kehadiran'] ?? '-')) ?></b>).
              </div>

              <?php if (in_array(($myReg['status'] ?? ''), ['menunggu_pembayaran','pending'], true)): ?>
                <a class="btn btn-primary w-100 mb-2"
                   href="<?= site_url('audience/pembayaran/instruction/'.$payId) ?>">
                  <i class="bi bi-cash-coin me-1"></i>Lanjutkan Pembayaran
                </a>
              <?php else: ?>
                <a class="btn btn-outline-primary w-100 mb-2"
                   href="<?= site_url('audience/pembayaran/detail/'.$payId) ?>">
                  <i class="bi bi-receipt me-1"></i>Lihat Pembayaran
                </a>
                <?php if ($isToday): ?>
                  <a class="btn btn-warning w-100"
                     href="<?= site_url('audience/absensi/event/'.($event['id'] ?? 0)) ?>">
                    <i class="bi bi-qr-code-scan me-1"></i>Absen Hari Ini
                  </a>
                <?php endif; ?>
              <?php endif; ?>

            <?php elseif ($isOpen): ?>
              <button type="button" id="btnDaftar" class="btn btn-primary w-100">
                <i class="bi bi-check2-square me-1"></i>Daftar Sekarang
              </button>
            <?php else: ?>
              <button class="btn btn-secondary w-100" disabled>
                <i class="bi bi-lock me-1"></i>Pendaftaran Ditutup
              </button>
            <?php endif; ?>

            <a href="<?= site_url('audience/events') ?>" class="btn btn-light w-100 mt-2">
              <i class="bi bi-arrow-left me-1"></i>Kembali ke Event
            </a>
          </div>
        </div>
      </div>

    </div>
  </main>
</div>

<script>
document.getElementById('btnDaftar')?.addEventListener('click', function(){
  const go = ()=> location.href = "<?= site_url('audience/events/register/'.($event['id'] ?? 0)) ?>";
  if (window.Swal) {
    Swal.fire({
      title: 'Lanjut daftar?',
      text: 'Kamu akan memilih mode (online/offline) sesuai ketersediaan.',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Ya, lanjut',
      cancelButtonText: 'Batal'
    }).then(r=>{ if(r.isConfirmed) go(); });
  } else {
    if (confirm('Yakin lanjut daftar?')) go();
  }
});
</script>

<?= $this->include('partials/footer') ?>
