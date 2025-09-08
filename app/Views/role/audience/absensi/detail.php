<?php
/** Detail Absensi Event */
$title = 'Detail Absensi Event';

$e   = $event ?? [];
$tgl = !empty($e['event_date']) ? date('d M Y', strtotime($e['event_date'])) : '-';
$jam = $e['event_time'] ?? '-';

$can         = (bool)($e['can_scan'] ?? false);
$badgeClass  = $e['badge_class']  ?? 'bg-secondary';
$eventStatus = $e['event_status'] ?? '-';

$already      = (bool)($already_attend ?? false);
$attendanceAt = $attendance_at ?? null;

// Jika sudah absen, paksa nonaktifkan tombol
if ($already) { $can = false; }
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_audience') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <div class="d-flex align-items-center mb-3">
        <a href="<?= site_url('audience/absensi') ?>" class="btn btn-light me-2"><i class="bi bi-arrow-left"></i></a>
        <div>
          <h3 class="mb-0"><?= esc($e['title'] ?? 'Event') ?></h3>
          <small class="text-muted">Kelola absensi event ini.</small>
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
                      <?php if (!empty($e['participation_type'])): ?>
                        <span class="abs-tag"><i class="bi bi-broadcast"></i> <?= esc(strtoupper($e['participation_type'])) ?></span>
                      <?php endif; ?>
                    </div>
                  </div>
                  <div class="text-md-end">
                    <div class="small opacity-75">Status</div>
                    <span class="badge <?= esc($badgeClass) ?> text-uppercase"><?= esc($eventStatus) ?></span>
                  </div>
                </div>
              </div>

              <?php if (session('error')): ?>
                <div class="alert alert-danger"><?= esc(session('error')) ?></div>
              <?php endif; ?>
              <?php if (session('success')): ?>
                <div class="alert alert-success"><?= esc(session('success')) ?></div>
              <?php endif; ?>

              <?php if ($already): ?>
                <div class="alert alert-success d-flex align-items-center">
                  <i class="bi bi-check2-circle me-2"></i>
                  <div>
                    Anda sudah absen<?= $attendanceAt ? ' pada ' . esc(date('d M Y H:i', strtotime($attendanceAt))) : '' ?>.
                  </div>
                </div>
              <?php elseif (!$can): ?>
                <div class="alert alert-warning d-flex align-items-center">
                  <i class="bi bi-exclamation-triangle me-2"></i>
                  <div>
                    <?= $eventStatus === 'Dihentikan'
                      ? 'Absensi telah dihentikan oleh panitia.'
                      : ($eventStatus === 'Belum Dimulai'
                          ? 'Absensi belum dibuka. Coba lagi setelah event dimulai.'
                          : 'Absensi tidak tersedia saat ini.') ?>
                  </div>
                </div>
              <?php endif; ?>

              <!-- CTA -->
              <div class="d-flex flex-wrap gap-2">
                <a class="btn btn-primary <?= $can ? '' : 'disabled' ?>"
                   href="<?= $can ? site_url('qr') : 'javascript:void(0)' ?>"
                   <?= $can ? '' : 'tabindex="-1" aria-disabled="true"' ?>>
                  <i class="bi bi-qr-code-scan me-1"></i>
                  <?= $already ? 'Sudah Absen' : 'Scan QR' ?>
                </a>

                <button class="btn btn-outline-secondary <?= $can ? '' : 'disabled' ?>"
                        type="button"
                        <?= $can ? 'data-bs-toggle="collapse" data-bs-target="#tokenForm" aria-expanded="false" aria-controls="tokenForm"' : 'tabindex="-1" aria-disabled="true"' ?>>
                  <i class="bi bi-key me-1"></i> Input Token
                </button>
              </div>

              <!-- Form Token -->
              <div class="collapse mt-3" id="tokenForm">
                <form action="<?= site_url('audience/absensi/scan') ?>" method="post" class="row g-2">
                  <?= csrf_field() ?>
                  <input type="hidden" name="event_id" value="<?= (int)($e['id'] ?? 0) ?>">
                  <div class="col-12 col-md-8">
                    <input type="text" name="token" class="form-control"
                           placeholder="Tempel token dari panitia di sini..." required <?= $can ? '' : 'disabled' ?>>
                  </div>
                  <div class="col-12 col-md-auto">
                    <button type="submit" class="btn btn-secondary" <?= $can ? '' : 'disabled' ?>>
                      <i class="bi bi-box-arrow-in-right me-1"></i> Submit Token
                    </button>
                  </div>
                </form>
                <div class="form-text">Token hanya bisa digunakan saat absensi dibuka.</div>
              </div>

              <div class="mt-3 small text-muted">
                Absen dibuka sejak jam mulai hingga ±4 jam setelahnya (atau sesuai kebijakan panitia).
              </div>

            </div>
          </div>
        </div>

        <div class="col-12 col-lg-4">
          <div class="card shadow-sm border-0">
            <div class="card-body">
              <div class="fw-semibold mb-2">Tips Absen</div>
              <ul class="small mb-0">
                <li>Pastikan kamera aktif & terfokus saat scan QR.</li>
                <li>Jika koneksi lemah, gunakan mode <em>Input Token</em>.</li>
                <li>Datang tepat waktu agar tidak melewati batas absensi.</li>
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
