<?php
/**
 * @var array|null $event
 * @var array|null $attendance   // ['status','waktu_scan',...]
 * @var bool       $hasVerifiedPayment
 * @var array|null $timing       // ['can_attend' => bool, 'message' => string] (opsional)
 */

$event        = $event ?? [];
$title        = esc($event['title'] ?? 'Event');
$eventId      = (int)($event['id'] ?? 0);
$eventDate    = $event['event_date'] ?? null;
$eventTime    = $event['event_time'] ?? null;
$venue        = $event['venue'] ?? ($event['lokasi'] ?? '-');

// Presenter selalu offline
$presenterMode = 'Offline';

// Info mode audience (kalau ada di schema-mu)
$audienceMode = $event['audience_mode']
    ?? $event['mode_audience']
    ?? $event['audience_participation_type']
    ?? 'Tidak diketahui';

// Timing (fallback kalau controller belum ngasih $timing)
$canAttend       = $timing['can_attend'] ?? false;
$timingMessage   = $timing['message']    ?? ($eventDate === date('Y-m-d')
                        ? 'Absensi tersedia untuk event hari ini.'
                        : 'Absensi dibuka pada hari H acara.');

// Sudah absen?
$alreadyAttend = !empty($attendance);
$attStatus     = $attendance['status'] ?? null;
$attTime       = !empty($attendance['waktu_scan']) ? date('d M Y H:i', strtotime($attendance['waktu_scan'])) : null;

// Helper URL
$backUrl   = site_url('presenter/absensi');
$scanUrl   = site_url('qr/scanner');           // scanner universal (tab baru)
$ajaxScan  = site_url('presenter/absensi/scan'); // endpoint AJAX presenter
?>

<div class="container py-4" id="content">
  <div class="d-flex align-items-center justify-content-between mb-3">
    <div>
      <h4 class="mb-1"><?= $title ?></h4>
      <div class="text-muted small">
        <i class="bi bi-calendar2-week me-1"></i>
        <?= $eventDate ? date('d M Y', strtotime($eventDate)) : '-' ?>
        <?php if ($eventTime): ?>
          · <i class="bi bi-clock me-1"></i><?= esc($eventTime) ?>
        <?php endif; ?>
        · <i class="bi bi-geo-alt me-1"></i><?= esc($venue) ?>
      </div>
    </div>
    <div>
      <a href="<?= $backUrl ?>" class="btn btn-outline-secondary">
        <i class="bi bi-arrow-left-short me-1"></i>Kembali
      </a>
    </div>
  </div>

  <?php if (!$hasVerifiedPayment): ?>
    <div class="alert alert-danger d-flex align-items-start" role="alert">
      <i class="bi bi-shield-exclamation me-2 fs-5"></i>
      <div>
        <strong>Pembayaran belum terverifikasi.</strong><br>
        Fitur absensi akan aktif setelah pembayaran Anda diverifikasi. Silakan cek halaman
        <a href="<?= site_url('presenter/pembayaran') ?>" class="alert-link">Pembayaran</a>.
      </div>
    </div>
  <?php endif; ?>

  <?php if ($alreadyAttend): ?>
    <div class="alert alert-success d-flex align-items-start" role="alert">
      <i class="bi bi-check2-circle me-2 fs-5"></i>
      <div>
        <strong>Absensi tercatat.</strong><br>
        Status: <span class="badge bg-success"><?= strtoupper(esc($attStatus)) ?></span>
        <?php if ($attTime): ?> · Waktu: <?= $attTime ?><?php endif; ?>
      </div>
    </div>
  <?php endif; ?>

  <div class="row g-4">
    <!-- Kolom aksi absensi -->
    <div class="col-lg-7">
      <div class="card">
        <div class="card-body">
          <h5 class="card-title mb-3">
            <i class="bi bi-qr-code-scan me-2"></i>Absensi Presenter
          </h5>

          <div class="mb-3">
            <?php if ($hasVerifiedPayment): ?>
              <div class="alert <?= $canAttend ? 'alert-info' : 'alert-warning' ?> mb-3" role="alert">
                <i class="bi bi-info-circle me-1"></i><?= esc($timingMessage) ?>
              </div>
            <?php endif; ?>

            <ul class="small text-muted mb-3">
              <li>QR ini khusus <strong>Presenter</strong> (mode: Offline).</li>
              <li>Pastikan kamera/QR scanner berfungsi dengan baik.</li>
              <li>Jika QR bermasalah, gunakan input token di bawah.</li>
            </ul>
          </div>

          <div class="d-flex gap-2 flex-wrap mb-3">
            <a href="<?= $scanUrl ?>" target="_blank"
               class="btn btn-primary"
               <?= (!$hasVerifiedPayment || $alreadyAttend || !$canAttend) ? 'aria-disabled="true" tabindex="-1" onclick="return false;"' : '' ?>>
              <i class="bi bi-camera-video me-1"></i>Buka Scanner QR
            </a>

            <?php if ($alreadyAttend): ?>
              <span class="badge bg-success align-self-center">
                <i class="bi bi-check2 me-1"></i>Sudah Absen
              </span>
            <?php elseif (!$hasVerifiedPayment): ?>
              <span class="badge bg-danger align-self-center">
                <i class="bi bi-x-circle me-1"></i>Pembayaran Belum Terverifikasi
              </span>
            <?php elseif (!$canAttend): ?>
              <span class="badge bg-secondary align-self-center">
                <i class="bi bi-lock me-1"></i>Belum Waktunya
              </span>
            <?php endif; ?>
          </div>

          <!-- Fallback: Input token manual (pakai AJAX ke presenter/absensi/scan) -->
          <form id="tokenForm" class="row g-2" autocomplete="off">
            <?= csrf_field() ?>
            <input type="hidden" name="event_id" value="<?= $eventId ?>">
            <div class="col-md-8">
              <label for="qr_token" class="form-label small text-muted mb-1">Masukkan Token/QR Code (fallback)</label>
              <input type="text" name="qr_code" id="qr_token" class="form-control"
                     placeholder="EVENT_<?= $eventId ?>_PRESENTER_XXXX"
                     <?= (!$hasVerifiedPayment || $alreadyAttend || !$canAttend) ? 'disabled' : '' ?>>
            </div>
            <div class="col-md-4 d-flex align-items-end">
              <button type="submit" class="btn btn-outline-primary w-100"
                      <?= (!$hasVerifiedPayment || $alreadyAttend || !$canAttend) ? 'disabled' : '' ?>>
                <i class="bi bi-arrow-right-circle me-1"></i>Kirim Token
              </button>
            </div>
          </form>

          <div id="tokenAlert" class="mt-3" style="display:none;"></div>
        </div>
      </div>
    </div>

    <!-- Kolom info event -->
    <div class="col-lg-5">
      <div class="card">
        <div class="card-body">
          <h6 class="text-muted text-uppercase mb-3">Info Event</h6>

          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="text-muted">Tanggal</span>
            <strong><?= $eventDate ? date('d M Y', strtotime($eventDate)) : '-' ?></strong>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="text-muted">Waktu</span>
            <strong><?= $eventTime ? esc($eventTime) : '-' ?></strong>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="text-muted">Tempat</span>
            <strong><?= esc($venue) ?></strong>
          </div>
          <hr>
          <div class="d-flex justify-content-between align-items-center mb-2">
            <span class="text-muted">Mode Presenter</span>
            <span class="badge bg-primary"><?= $presenterMode ?></span>
          </div>
          <div class="d-flex justify-content-between align-items-center">
            <span class="text-muted">Mode Audience</span>
            <span class="badge bg-secondary"><?= ucfirst($audienceMode) ?></span>
          </div>
        </div>
      </div>

      <div class="card mt-3">
        <div class="card-body">
          <h6 class="text-muted text-uppercase mb-3">Panduan Cepat</h6>
          <ol class="small mb-0">
            <li>Buka <em>Scanner QR</em> atau masukkan token pada kotak di kiri.</li>
            <li>Pastikan Anda berada di lokasi event (offline).</li>
            <li>Setelah berhasil, status absensi akan berubah menjadi <strong>Hadir</strong>.</li>
          </ol>
        </div>
      </div>
    </div>
  </div>
</div>

<script>
(function(){
  const form   = document.getElementById('tokenForm');
  const alertB = document.getElementById('tokenAlert');

  if (!form) return;

  form.addEventListener('submit', async function(e){
    e.preventDefault();

    const fd = new FormData(form);
    // kirim hanya qr_code (endpoint presenter/absensi/scan cuma butuh itu)
    const payload = new URLSearchParams();
    payload.append('qr_code', fd.get('qr_code') || '');
    // CSRF (kalau aktif)
    <?php if (function_exists('csrf_token')): ?>
      payload.append('<?= csrf_token() ?>', '<?= csrf_hash() ?>');
    <?php endif; ?>

    alertB.style.display = 'none';
    alertB.className = '';
    alertB.innerHTML = '';

    try{
      const res = await fetch('<?= $ajaxScan ?>', {
        method: 'POST',
        headers: { 'X-Requested-With':'XMLHttpRequest', 'Content-Type':'application/x-www-form-urlencoded' },
        body: payload.toString()
      });
      const data = await res.json();

      if (data.success){
        alertB.className = 'alert alert-success';
        alertB.innerHTML = `<i class="bi bi-check2-circle me-1"></i>${data.message || 'Berhasil'}`
          + (data.data?.attendance_time ? ` · ${data.data.attendance_date} ${data.data.attendance_time}` : '');
        alertB.style.display = 'block';

        // disable input & tombol
        form.querySelector('#qr_token').setAttribute('disabled','disabled');
        form.querySelector('button[type="submit"]').setAttribute('disabled','disabled');

        // refresh halaman biar status "Sudah Absen" tampil
        setTimeout(()=> location.reload(), 1200);
      } else {
        alertB.className = 'alert alert-danger';
        alertB.innerHTML = `<i class="bi bi-x-circle me-1"></i>${data.message || 'Gagal memproses token.'}`;
        alertB.style.display = 'block';
      }
    } catch(err){
      alertB.className = 'alert alert-danger';
      alertB.innerHTML = `<i class="bi bi-x-circle me-1"></i>Terjadi kesalahan jaringan.`;
      alertB.style.display = 'block';
    }
  });
})();
</script>
