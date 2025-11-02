<?php
$title        = $title        ?? 'Event Tersedia';
$qRaw         = $q            ?? '';
$fmt          = $format       ?? '';
$isSearching  = $isSearching  ?? false;

$openEvents   = $openEvents   ?? [];
$closedEvents = $closedEvents ?? [];
$myRegs       = $myRegs       ?? [];

$totalAll     = $total_all    ?? (count($openEvents)+count($closedEvents));
$totalOpen    = $total_open   ?? count($openEvents);
$totalClosed  = $total_closed ?? count($closedEvents);

$rupiah = function($n){ return 'Rp ' . number_format((float)$n, 0, ',', '.'); };
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_audience') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <!-- Header Biru (samakan dengan Abstrak) -->
      <div class="header-section header-blue d-flex justify-content-between align-items-center mb-3">
        <div>
          <h3 class="welcome-text mb-1"><i class="bi bi-calendar2-event me-2"></i>Event</h3>
          <div class="text-white-50">Pilih & kelola pendaftaran event</div>
        </div>
        <div class="text-end d-none d-md-block">
          <small class="text-white-50 d-block">Hari ini</small>
          <strong class="text-white"><?= date('d M Y') ?></strong>
        </div>
      </div>

      <!-- Filter -->
      <div class="card shadow-sm mb-4 border-0 overflow-hidden">
        <div class="card-header bg-gradient-primary text-white">
          <h5 class="mb-0"><i class="bi bi-funnel me-2"></i>Filter</h5>
        </div>
        <div class="card-body">
          <form method="get" action="">
            <div class="row g-2 align-items-stretch">
              <div class="col-12 col-md-6 col-lg-4">
                <input type="text" name="q" value="<?= esc($qRaw) ?>" class="form-control" placeholder="Cari judul / lokasi...">
              </div>
              <div class="col-6 col-md-3 col-lg-2">
                <select name="format" class="form-select">
                  <option value="">Semua Format</option>
                  <option value="online"  <?= $fmt==='online'  ? 'selected':'' ?>>Online</option>
                  <option value="offline" <?= $fmt==='offline' ? 'selected':'' ?>>Offline</option>
                  <option value="both"    <?= $fmt==='both'    ? 'selected':'' ?>>Hybrid</option>
                </select>
              </div>
              <div class="col-6 col-md-3 col-lg-2 d-flex gap-2">
                <button class="btn btn-primary w-100"><i class="bi bi-search me-1"></i> Cari</button>
                <?php if ($isSearching): ?>
                  <a href="<?= current_url() ?>" class="btn btn-outline-secondary" title="Reset filter"><i class="bi bi-x-lg"></i></a>
                <?php endif; ?>
              </div>
              <div class="col-12 col-lg text-muted small d-flex align-items-center justify-content-lg-end">
                Menampilkan <?= (int)$totalAll ?> event (<?= (int)$totalOpen ?> terbuka, <?= (int)$totalClosed ?> ditutup).
              </div>
            </div>
          </form>
        </div>
      </div>

      <?php if ($totalAll > 0): ?>

        <!-- Pendaftaran Dibuka -->
        <div class="card shadow-sm mb-4 border-0 overflow-hidden">
          <div class="card-header bg-gradient-primary text-white d-flex align-items-center justify-content-between">
            <h5 class="mb-0"><i class="bi bi-unlock me-2"></i>Pendaftaran Dibuka</h5>
            <span class="badge bg-light text-dark"><?= (int)$totalOpen ?></span>
          </div>
          <div class="card-body">
            <?php if (!empty($openEvents)): ?>
              <div class="row g-3">
                <?php foreach ($openEvents as $e): ?>
                  <?php
                    $fmtEvent   = strtolower($e['format'] ?? '');
                    $onlineOK   = in_array($fmtEvent, ['online','both'], true);
                    $offlineOK  = in_array($fmtEvent, ['offline','both'], true);
                    $pOn        = (float)($e['audience_fee_online']  ?? 0);
                    $pOff       = (float)($e['audience_fee_offline'] ?? 0);

                    $regRaw        = $myRegs[$e['id']] ?? null;
                    $regStatus     = is_array($regRaw) ? ($regRaw['status'] ?? null)         : $regRaw;
                    $paymentId     = is_array($regRaw) ? ($regRaw['payment_id'] ?? null)     : null;
                    $paymentStatus = is_array($regRaw) ? ($regRaw['payment_status'] ?? null) : null;
                    $regId         = is_array($regRaw) ? ($regRaw['reg_id'] ?? null)         : null;

                    $isWaitingVerification = (
                      $regStatus === 'menunggu_pembayaran' &&
                      !empty($paymentId) &&
                      $paymentStatus === 'uploaded'
                    );
                    $isPendingPayment = (
                      $regStatus === 'menunggu_pembayaran' &&
                      !empty($paymentId) &&
                      $paymentStatus === 'pending'
                    );
                    $isRegistered = ($regStatus !== null && $regStatus !== 'batal');

                    $tgl = !empty($e['event_date']) ? date('d M Y', strtotime($e['event_date'])) : '-';
                    $jam = $e['event_time'] ?? '-';
                    $loc = $e['location'] ?? ($fmtEvent === 'online' ? '—' : '-');
                  ?>
                  <div class="col-12 col-md-6 col-xl-4">
                    <div class="event-card h-100 shadow-sm">
                      <div class="d-flex align-items-start justify-content-between mb-2">
                        <h5 class="mb-0"><?= esc($e['title'] ?? 'Event') ?></h5>
                        <span class="badge bg-secondary"><?= esc(strtoupper($e['format'] ?? '-')) ?></span>
                      </div>
                      <div class="small text-muted mb-2">
                        <i class="bi bi-calendar2-event me-1"></i><?= esc($tgl) ?> · <?= esc($jam) ?><br>
                        <i class="bi bi-geo me-1"></i>Lokasi: <strong><?= esc($loc) ?></strong>
                      </div>

                      <div class="small text-muted mb-1">Harga Audience</div>
                      <div class="d-flex flex-wrap gap-2 mb-2">
                        <?php if ($onlineOK): ?>
                          <span class="badge bg-info"><?= $pOn>0 ? $rupiah($pOn) : 'Gratis' ?> (Online)</span>
                        <?php endif; ?>
                        <?php if ($offlineOK): ?>
                          <span class="badge bg-primary"><?= $pOff>0 ? $rupiah($pOff) : 'Gratis' ?> (Offline)</span>
                        <?php endif; ?>
                      </div>

                      <div class="d-flex flex-wrap gap-2 mb-2">
                        <?php if ($isRegistered): ?>
                          <span class="badge bg-success"><i class="bi bi-check2-circle me-1"></i>Terdaftar</span>
                          <?php if ($isWaitingVerification): ?>
                            <span class="badge bg-warning text-dark">Menunggu verifikasi</span>
                          <?php elseif ($regStatus === 'menunggu_pembayaran'): ?>
                            <span class="badge bg-warning text-dark">Menunggu pembayaran</span>
                          <?php elseif ($regStatus === 'lunas'): ?>
                            <span class="badge bg-primary">Lunas</span>
                          <?php endif; ?>
                        <?php else: ?>
                          <span class="badge bg-success"><i class="bi bi-unlock me-1"></i>Dibuka</span>
                        <?php endif; ?>
                      </div>

                      <div class="d-flex gap-2">
                        <?php if (!$isWaitingVerification && !$isPendingPayment): ?>
                          <a href="<?= site_url('audience/events/detail/'.($e['id'] ?? 0)) ?>"
                             class="btn btn-outline-secondary flex-fill">
                            <?= $isRegistered ? 'Lihat Status' : 'Detail' ?>
                          </a>
                        <?php endif; ?>

                        <?php if ($isRegistered): ?>
                          <?php if ($isWaitingVerification): ?>
                            <a href="<?= site_url('audience/pembayaran/detail/'.(int)$paymentId) ?>"
                               class="btn btn-primary flex-fill">Detail Pembayaran</a>
                            <a href="<?= site_url('audience/pembayaran/detail/'.(int)$paymentId).'#unggah-ulang' ?>"
                               class="btn btn-outline-primary flex-fill">Ubah Bukti</a>
                          <?php elseif ($isPendingPayment): ?>
                            <!-- FIX: Gunakan regId untuk instruction -->
                            <a href="<?= site_url('audience/pembayaran/instruction/'.(int)$regId) ?>"
                               class="btn btn-primary flex-fill js-go-pay"
                               data-title="<?= esc($e['title'] ?? 'Event') ?>">Lanjutkan Pembayaran</a>
                            <a href="<?= site_url('audience/pembayaran/detail/'.(int)$paymentId) ?>"
                               class="btn btn-outline-primary flex-fill">Detail Pembayaran</a>
                          <?php elseif ($regStatus === 'menunggu_pembayaran'): ?>
                            <!-- Belum ada payment_id, berarti belum bayar sama sekali -->
                            <?php if (!empty($regId)): ?>
                            <a href="<?= site_url('audience/pembayaran/instruction/'.(int)$regId) ?>"
                               class="btn btn-primary flex-fill js-go-pay"
                               data-title="<?= esc($e['title'] ?? 'Event') ?>">Lanjutkan Pembayaran</a>
                            <?php else: ?>
                            <a href="<?= site_url('audience/pembayaran') ?>"
                               class="btn btn-primary flex-fill">Pembayaran</a>
                            <?php endif; ?>
                            <?php if (!empty($paymentId)): ?>
                              <a href="<?= site_url('audience/pembayaran/cancel/'.(int)$paymentId) ?>"
                                 class="btn btn-outline-danger flex-fill js-cancel"
                                 data-title="<?= esc($e['title'] ?? 'Event') ?>">Batalkan</a>
                            <?php endif; ?>
                          <?php else: ?>
                            <button class="btn btn-primary flex-fill" disabled>Daftar</button>
                          <?php endif; ?>
                        <?php else: ?>
                          <a href="<?= site_url('audience/events/register/'.($e['id'] ?? 0)) ?>"
                             class="btn btn-primary flex-fill js-register"
                             data-title="<?= esc($e['title'] ?? 'Event') ?>">Daftar</a>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <div class="text-muted">Tidak ada event dengan pendaftaran terbuka.</div>
            <?php endif; ?>
          </div>
        </div>

        <!-- Ditutup / Selesai -->
        <div class="card shadow-sm mb-4 border-0 overflow-hidden">
          <div class="card-header bg-gradient-primary text-white d-flex align-items-center justify-content-between">
            <h5 class="mb-0"><i class="bi bi-lock me-2"></i>Ditutup / Selesai</h5>
            <span class="badge bg-light text-dark"><?= (int)$totalClosed ?></span>
          </div>
          <div class="card-body">
            <?php if (!empty($closedEvents)): ?>
              <div class="row g-3">
                <?php foreach ($closedEvents as $e): ?>
                  <?php
                    $fmtEvent   = strtolower($e['format'] ?? '');
                    $onlineOK   = in_array($fmtEvent, ['online','both'], true);
                    $offlineOK  = in_array($fmtEvent, ['offline','both'], true);
                    $pOn        = (float)($e['audience_fee_online']  ?? 0);
                    $pOff       = (float)($e['audience_fee_offline'] ?? 0);

                    $regRaw        = $myRegs[$e['id']] ?? null;
                    $regStatus     = is_array($regRaw) ? ($regRaw['status'] ?? null) : $regRaw;
                    $paymentId     = is_array($regRaw) ? ($regRaw['payment_id'] ?? null) : null;
                    $regId         = is_array($regRaw) ? ($regRaw['reg_id'] ?? null) : null;

                    $isRegistered = ($regStatus !== null && $regStatus !== 'batal');

                    $tgl = !empty($e['event_date']) ? date('d M Y', strtotime($e['event_date'])) : '-';
                    $jam = $e['event_time'] ?? '-';
                    $loc = $e['location'] ?? ($fmtEvent === 'online' ? '—' : '-');
                  ?>
                  <div class="col-12 col-md-6 col-xl-4">
                    <div class="event-card h-100 shadow-sm">
                      <div class="d-flex align-items-start justify-content-between mb-2">
                        <h5 class="mb-0"><?= esc($e['title'] ?? 'Event') ?></h5>
                        <span class="badge bg-secondary"><?= esc(strtoupper($e['format'] ?? '-')) ?></span>
                      </div>
                      <div class="small text-muted mb-2">
                        <i class="bi bi-calendar2-event me-1"></i><?= esc($tgl) ?> · <?= esc($jam) ?><br>
                        <i class="bi bi-geo me-1"></i>Lokasi: <strong><?= esc($loc) ?></strong>
                      </div>

                      <div class="d-flex flex-wrap gap-2 mb-2">
                        <span class="badge bg-secondary"><i class="bi bi-lock me-1"></i>Ditutup</span>
                        <?php if ($isRegistered): ?>
                          <span class="badge bg-success">Terdaftar</span>
                        <?php endif; ?>
                      </div>

                      <div class="d-flex gap-2">
                        <a href="<?= site_url('audience/events/detail/'.($e['id'] ?? 0)) ?>"
                           class="btn btn-outline-secondary flex-fill">Detail</a>

                        <?php if ($isRegistered && $regStatus === 'menunggu_pembayaran'): ?>
                          <!-- FIX: Prioritaskan regId untuk instruction -->
                          <?php if (!empty($regId)): ?>
                          <a href="<?= site_url('audience/pembayaran/instruction/'.(int)$regId) ?>"
                             class="btn btn-primary flex-fill js-go-pay"
                             data-title="<?= esc($e['title'] ?? 'Event') ?>">Lanjutkan Pembayaran</a>
                          <?php elseif (!empty($paymentId)): ?>
                          <a href="<?= site_url('audience/pembayaran/detail/'.(int)$paymentId) ?>"
                             class="btn btn-primary flex-fill">Detail Pembayaran</a>
                          <?php else: ?>
                          <a href="<?= site_url('audience/pembayaran') ?>"
                             class="btn btn-primary flex-fill">Pembayaran</a>
                          <?php endif; ?>
                        <?php else: ?>
                          <button class="btn btn-primary flex-fill" disabled>Daftar</button>
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            <?php else: ?>
              <div class="text-muted">Tidak ada event yang ditutup.</div>
            <?php endif; ?>
          </div>
        </div>

      <?php else: ?>
        <?php if ($isSearching): ?>
          <div class="card shadow-sm border-0 overflow-hidden">
            <div class="card-body text-center">
              <div class="mb-2"><i class="bi bi-calendar2-event fs-3 text-secondary"></i></div>
              <div class="fw-semibold">Belum ada event yang cocok</div>
              <div class="text-muted small">Coba ubah kata kunci atau format.</div>
            </div>
          </div>
        <?php else: ?>
          <div class="card shadow-sm border-0 overflow-hidden">
            <div class="card-body text-center">
              <div class="mb-2"><i class="bi bi-calendar-x fs-3 text-secondary"></i></div>
              <div class="fw-semibold">Event Belum Tersedia</div>
              <div class="text-muted small">Tunggu informasi berikutnya ya.</div>
            </div>
          </div>
        <?php endif; ?>
      <?php endif; ?>

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
  .bg-gradient-primary{ background:linear-gradient(135deg,var(--primary-color),var(--info-color))!important; }
  .event-card{
    background:#f3f4f6;
    border-radius:14px;
    padding:16px;
    border:1px solid #e5e7eb;
  }
  @media (max-width: 767.98px){
    .event-card{ padding:14px; }
    .header-section.header-blue{ padding:18px; }
  }
</style>

<script>
document.querySelectorAll('.js-register').forEach(a=>{
  a.addEventListener('click', (e)=>{
    const title = a.getAttribute('data-title') || 'Event';
    if (window.Swal){
      e.preventDefault();
      Swal.fire({
        title:'Daftar ke event ini?',
        html:'<b>'+title+'</b><br><span class="text-muted">Kamu akan memilih mode (online/offline) di langkah berikutnya.</span>',
        icon:'question', showCancelButton:true, confirmButtonText:'Ya, lanjut', cancelButtonText:'Batal'
      }).then(r=>{ if(r.isConfirmed) location.href = a.href; });
    } else if(!confirm('Daftar ke "'+title+'"?')) e.preventDefault();
  });
});

document.querySelectorAll('.js-cancel').forEach(a=>{
  a.addEventListener('click', (e)=>{
    const title = a.getAttribute('data-title') || 'Event';
    if (window.Swal){
      e.preventDefault();
      Swal.fire({
        title: 'Batalkan pendaftaran?', html: '<b>'+title+'</b>',
        icon:'warning', showCancelButton:true, confirmButtonColor:'#dc3545',
        confirmButtonText:'Ya, batalkan', cancelButtonText:'Kembali'
      }).then(r=>{ if(r.isConfirmed) location.href = a.href; });
    } else if(!confirm('Batalkan pendaftaran "'+title+'"?')) e.preventDefault();
  });
});

document.querySelectorAll('.js-go-pay').forEach(a=>{
  a.addEventListener('click', (e)=>{
    const title = a.getAttribute('data-title') || 'Event';
    if (window.Swal){
      e.preventDefault();
      Swal.fire({
        title:'Lanjutkan pembayaran?', html:'<b>'+title+'</b>',
        icon:'question', showCancelButton:true, confirmButtonText:'Ya', cancelButtonText:'Batal'
      }).then(r=>{ if(r.isConfirmed) location.href = a.href; });
    }
  });
});
</script>