<?php
// =========================
//  Event Index (Audience)
// =========================
$title  = 'Event Tersedia';
$events = $events ?? [];
$myRegs = $myRegs ?? [];

$qRaw = $_GET['q'] ?? '';
$fmt  = $_GET['format'] ?? '';
$q    = strtolower(trim($qRaw));
$isSearching = ($q !== '') || ($fmt !== '');

$rupiah = function($n){ return 'Rp ' . number_format((float)$n, 0, ',', '.'); };

$isOpen = function(array $e): bool {
  $isActive  = !empty($e['is_active']);
  $regActive = !empty($e['registration_active']);
  if (!$isActive || !$regActive) return false;
  $now = time();
  if (!empty($e['registration_deadline']) && strtotime($e['registration_deadline']) < $now) return false;
  if (!empty($e['event_date'])) {
    $evtTs = strtotime($e['event_date'] . ' ' . ($e['event_time'] ?? '00:00'));
    if ($evtTs < $now) return false;
  }
  return true;
};

$filtered = array_values(array_filter($events, function($e) use($q,$fmt){
  if ($q !== '') {
    $hay = strtolower(($e['title'] ?? '') . ' ' . ($e['location'] ?? ''));
    if (!str_contains($hay, $q)) return false;
  }
  if ($fmt !== '') {
    $evFmt = $e['format'] ?? '';
    if ($fmt === 'online'  && !in_array($evFmt, ['online','both'], true))  return false;
    if ($fmt === 'offline' && !in_array($evFmt, ['offline','both'], true)) return false;
    if ($fmt === 'both'    && $evFmt !== 'both')                           return false;
  }
  return true;
}));

$list = $isSearching ? $filtered : $events;
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_audience') ?>

<div id="content">
  <main class="flex-fill">
    <div class="container-fluid p-3 p-md-4">

      <div class="d-flex align-items-center justify-content-between gap-2 mb-3">
        <h3 class="mb-0">Event</h3>
        <a href="<?= site_url('audience/dashboard') ?>" class="btn btn-sm btn-outline-secondary d-none d-md-inline-flex">
          <i class="bi bi-house me-1"></i> Dashboard
        </a>
      </div>

      <form class="mb-3" method="get" action="">
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
          <?php if (!$isSearching && !empty($events)): ?>
            <div class="col-12 col-lg text-muted small d-flex align-items-center justify-content-lg-end">
              Menampilkan <?= count($events) ?> event.
            </div>
          <?php elseif ($isSearching): ?>
            <div class="col-12 col-lg text-muted small d-flex align-items-center justify-content-lg-end">
              Hasil: <?= count($filtered) ?> event.
            </div>
          <?php endif; ?>
        </div>
      </form>

      <?php if (!empty($list)): ?>
        <div class="row g-3">
          <?php foreach ($list as $e): ?>
            <?php
              $fmtEvent   = strtolower($e['format'] ?? '');
              $onlineOK   = in_array($fmtEvent, ['online','both'], true);
              $offlineOK  = in_array($fmtEvent, ['offline','both'], true);
              $pOn        = (float)($e['audience_fee_online']  ?? 0);
              $pOff       = (float)($e['audience_fee_offline'] ?? 0);
              $open       = $isOpen($e);

              $regRaw        = $myRegs[$e['id']] ?? null;
              $regStatus     = is_array($regRaw) ? ($regRaw['status'] ?? null)         : $regRaw;
              $paymentId     = is_array($regRaw) ? ($regRaw['payment_id'] ?? null)     : null;
              $paymentStatus = is_array($regRaw) ? ($regRaw['payment_status'] ?? null) : null;
              $regId         = is_array($regRaw) ? ($regRaw['reg_id'] ?? null)         : null;

              // menunggu verifikasi setelah upload
              $isWaitingVerification = (
                $regStatus === 'menunggu_pembayaran' &&
                !empty($paymentId) &&
                in_array($paymentStatus, ['pending','uploaded'], true)
              );

              $isRegistered = ($regStatus !== null && $regStatus !== 'batal');

              $tgl = !empty($e['event_date']) ? date('d M Y', strtotime($e['event_date'])) : '-';
              $jam = $e['event_time'] ?? '-';
              $loc = $e['location'] ?? ($fmtEvent === 'online' ? '—' : '-');
            ?>
            <div class="col-12 col-md-6 col-lg-4">
              <div class="card h-100 shadow-sm border-0">
                <div class="card-body d-flex flex-column">
                  <div class="d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-1"><?= esc($e['title'] ?? 'Event') ?></h5>
                    <span class="badge bg-secondary-subtle text-secondary"><?= esc(strtoupper($e['format'] ?? '-')) ?></span>
                  </div>
                  <small class="text-muted"><?= esc($tgl) ?> · <?= esc($jam) ?></small>
                  <div class="mt-2 small text-muted">Lokasi: <?= esc($loc) ?></div>

                  <div class="mt-3">
                    <div class="small text-muted mb-1">Harga Audience</div>
                    <div class="d-flex flex-wrap gap-2">
                      <?php if ($onlineOK): ?>
                        <span class="badge bg-info-subtle text-info">Online: <?= $pOn>0 ? $rupiah($pOn) : 'Gratis' ?></span>
                      <?php endif; ?>
                      <?php if ($offlineOK): ?>
                        <span class="badge bg-primary-subtle text-primary">Offline: <?= $pOff>0 ? $rupiah($pOff) : 'Gratis' ?></span>
                      <?php endif; ?>
                    </div>
                  </div>

                  <div class="mt-3 d-flex flex-wrap gap-2">
                    <?php if ($isRegistered): ?>
                      <span class="badge bg-success-subtle text-success"><i class="bi bi-check2-circle me-1"></i> Anda sudah terdaftar</span>
                      <?php if ($isWaitingVerification): ?>
                        <span class="badge bg-warning-subtle text-warning">Menunggu verifikasi admin</span>
                      <?php elseif ($regStatus === 'menunggu_pembayaran'): ?>
                        <span class="badge bg-warning-subtle text-warning">Menunggu pembayaran</span>
                      <?php elseif ($regStatus === 'lunas'): ?>
                        <span class="badge bg-primary-subtle text-primary">Lunas</span>
                      <?php endif; ?>
                    <?php else: ?>
                      <?php if ($open): ?>
                        <span class="badge bg-success-subtle text-success"><i class="bi bi-unlock me-1"></i>Pendaftaran dibuka</span>
                      <?php else: ?>
                        <span class="badge bg-danger-subtle text-danger"><i class="bi bi-lock me-1"></i>Pendaftaran ditutup</span>
                      <?php endif; ?>
                    <?php endif; ?>
                  </div>

                  <!-- Aksi -->
                  <div class="mt-auto pt-3 d-flex flex-wrap gap-2">
                    <?php if (!$isWaitingVerification): ?>
                      <a href="<?= site_url('audience/events/detail/'.($e['id'] ?? 0)) ?>"
                         class="btn btn-sm btn-outline-secondary flex-fill">
                        <?= $isRegistered ? 'Lihat Status' : 'Detail' ?>
                      </a>
                    <?php endif; ?>

                    <?php if ($isRegistered): ?>
                      <?php if ($isWaitingVerification): ?>
                        <a href="<?= site_url('audience/pembayaran/detail/'.(int)$paymentId) ?>"
                           class="btn btn-sm btn-primary flex-fill">
                          Detail Pembayaran
                        </a>
                        <a href="<?= site_url('audience/pembayaran/detail/'.(int)$paymentId).'#unggah-ulang' ?>"
                           class="btn btn-sm btn-outline-primary flex-fill">
                          Ubah Bukti
                        </a>

                      <?php elseif ($regStatus === 'menunggu_pembayaran'): ?>
                        <a href="<?= $regId ? site_url('audience/pembayaran/instruction/'.$regId)
                                             : site_url('audience/pembayaran') ?>"
                           class="btn btn-sm btn-primary flex-fill js-go-pay"
                           data-title="<?= esc($e['title'] ?? 'Event') ?>">
                          Lanjutkan Pembayaran
                        </a>
                        <?php if (!empty($paymentId)): ?>
                          <a href="<?= site_url('audience/pembayaran/cancel/'.$paymentId) ?>"
                             class="btn btn-sm btn-outline-danger flex-fill js-cancel"
                             data-title="<?= esc($e['title'] ?? 'Event') ?>">
                            Batalkan
                          </a>
                        <?php endif; ?>
                      <?php else: ?>
                        <button class="btn btn-sm btn-primary flex-fill" disabled>Daftar</button>
                      <?php endif; ?>

                    <?php else: ?>
                      <a href="<?= site_url('audience/events/register/'.($e['id'] ?? 0)) ?>"
                         class="btn btn-sm btn-primary flex-fill js-register"
                         data-title="<?= esc($e['title'] ?? 'Event') ?>"
                         <?= $open ? '' : 'tabindex="-1" aria-disabled="true"' ?>
                         <?= $open ? '' : 'onclick="return false;" class="disabled btn btn-sm btn-primary flex-fill"' ?>>
                        Daftar
                      </a>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php else: ?>
        <?php if ($isSearching): ?>
          <div class="p-4 text-center border rounded-3 bg-light-subtle">
            <div class="mb-2"><i class="bi bi-calendar2-event fs-3 text-secondary"></i></div>
            <div class="fw-semibold">Belum ada event yang cocok</div>
            <div class="text-muted small">Coba ubah kata kunci atau format.</div>
          </div>
        <?php else: ?>
          <div class="p-4 text-center border rounded-3 bg-light-subtle">
            <div class="mb-2"><i class="bi bi-calendar-x fs-3 text-secondary"></i></div>
            <div class="fw-semibold">Event Belum Tersedia</div>
            <div class="text-muted small">Tunggu informasi berikutnya ya.</div>
          </div>
        <?php endif; ?>
      <?php endif; ?>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<script>
// konfirmasi
document.querySelectorAll('.js-register').forEach(a=>{
  a.addEventListener('click', (e)=>{
    const title = a.getAttribute('data-title') || 'Event';
    if (window.Swal){
      e.preventDefault();
      Swal.fire({
        title: 'Daftar ke event ini?',
        html: '<b>'+title+'</b><br><span class="text-muted">Kamu akan memilih mode (online/offline) di langkah berikutnya.</span>',
        icon: 'question', showCancelButton: true, confirmButtonText: 'Ya, lanjut', cancelButtonText: 'Batal'
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
