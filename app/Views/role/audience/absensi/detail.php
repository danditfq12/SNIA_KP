<?php
  $title = 'Detail Absensi Event';

/** Controller mengirim:
 * $event = [
 *   'id','title','event_date','event_time','format','location',
 *   'participation_type','event_status','badge_class','can_scan'
 * ];
 */
  $e = $event ?? [];
  $tgl = isset($e['event_date']) ? date('d M Y', strtotime($e['event_date'])) : '-';
  $jam = $e['event_time'] ?? '-';
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_audience') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <div class="d-flex align-items-center mb-3">
        <a href="<?= site_url('audience/absensi') ?>" class="btn btn-light me-2">
          <i class="bi bi-arrow-left"></i>
        </a>
        <div>
          <h3 class="mb-0"><?= esc($e['title'] ?? 'Event') ?></h3>
          <small class="text-muted">Kelola absensi event ini.</small>
        </div>
      </div>

      <div class="row g-3">
        <div class="col-12 col-lg-8">
          <div class="card shadow-sm">
            <div class="card-body">

              <div class="d-flex justify-content-between align-items-start">
                <div>
                  <div class="mb-1">
                    <span class="badge <?= esc($e['badge_class'] ?? 'bg-secondary') ?>">
                      <?= esc($e['event_status'] ?? 'Unknown') ?>
                    </span>
                  </div>
                  <div class="fw-semibold"><?= esc($tgl) ?> · <?= esc($jam) ?></div>
                  <div class="small text-muted">
                    Format: <?= esc(strtoupper($e['format'] ?? '-')) ?>
                    <?php if (!empty($e['location'])): ?>
                      · Lokasi: <?= esc($e['location']) ?>
                    <?php endif; ?>
                    <?php if (!empty($e['participation_type'])): ?>
                      · Mode: <?= esc(strtoupper($e['participation_type'])) ?>
                    <?php endif; ?>
                  </div>
                </div>
              </div>

              <hr>

              <?php if (!empty(session('error'))): ?>
                <div class="alert alert-danger"><?= esc(session('error')) ?></div>
              <?php endif; ?>
              <?php if (!empty(session('success'))): ?>
                <div class="alert alert-success"><?= esc(session('success')) ?></div>
              <?php endif; ?>

              <?php if (!($e['can_scan'] ?? false)): ?>
                <div class="alert alert-warning d-flex align-items-center">
                  <i class="bi bi-exclamation-triangle me-2"></i>
                  <div>
                    Event belum dibuka untuk absensi. Coba lagi mendekati jam mulai.
                  </div>
                </div>
              <?php endif; ?>

              <div class="d-flex flex-wrap gap-2">
                <a
                  class="btn btn-primary <?= ($e['can_scan'] ?? false) ? '' : 'disabled' ?>"
                  href="<?= ($e['can_scan'] ?? false) ? site_url('qr') : 'javascript:void(0)'; ?>"
                >
                  <i class="bi bi-qr-code-scan me-1"></i> Scan QR
                </a>

                <button
                  class="btn btn-outline-secondary"
                  type="button"
                  data-bs-toggle="collapse"
                  data-bs-target="#tokenForm"
                  aria-expanded="false"
                  aria-controls="tokenForm"
                  <?= ($e['can_scan'] ?? false) ? '' : '' ?>
                >
                  <i class="bi bi-key me-1"></i> Input Token
                </button>
              </div>

              <div class="collapse mt-3" id="tokenForm">
                <form action="<?= site_url('audience/absensi/scan') ?>" method="post" class="row g-2">
                  <?= csrf_field() ?>
                  <div class="col-12 col-md-8">
                    <input
                      type="text"
                      name="token"
                      class="form-control"
                      placeholder="Tempel token dari panitia di sini..."
                      required
                    >
                  </div>
                  <div class="col-12 col-md-auto">
                    <button type="submit" class="btn btn-secondary">
                      <i class="bi bi-box-arrow-in-right me-1"></i> Submit Token
                    </button>
                  </div>
                </form>
                <div class="form-text">
                  Token bisa dikirim panitia via link/teks. Setelah submit kamu akan diarahkan ke halaman validasi.
                </div>
              </div>

            </div>
          </div>
        </div>

        <div class="col-12 col-lg-4">
          <div class="card shadow-sm">
            <div class="card-body">
              <div class="fw-semibold mb-2">Tips Absen</div>
              <ul class="small mb-0">
                <li>Absensi dibuka dari 1 jam sebelum hingga 4 jam setelah jam mulai.</li>
                <li>Gunakan tombol <em>Scan QR</em> bila hadir di lokasi.</li>
                <li>Gunakan <em>Input Token</em> bila panitia memberi token/link.</li>
              </ul>
            </div>
          </div>
        </div>

      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>
