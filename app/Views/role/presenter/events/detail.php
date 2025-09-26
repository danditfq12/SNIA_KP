<?php
$event       = $event ?? [];
$reg         = $reg ?? null;
$abstrak     = $abstrak ?? null;
$payment     = $payment ?? null;
$flow        = $flow ?? [];
$contributors= $contributors ?? [];

/* ================= Normalisasi status ================= */
$abStatus  = strtolower($abstrak['status'] ?? '');
$fpStatus  = strtolower($abstrak['full_paper_status'] ?? '');
$payStatus = strtolower($payment['status'] ?? '');

/* Ada full paper atau belum */
$hasFullpaper = !empty($abstrak['full_paper_path']) || !empty($abstrak['full_paper_status']);

/* helper label */
$nice = function($v) {
  if (!$v) return 'Belum';
  $v = strtolower($v);
  return match($v){
    'diterima','accepted','acc','approved'   => 'Diterima',
    'ditolak','rejected'                     => 'Ditolak',
    'revisi','revision'                      => 'Revisi',
    'menunggu','sedang_direview','uploaded','pending' => 'Menunggu',
    'verified'                               => 'Terverifikasi',
    'canceled'                               => 'Dibatalkan',
    'expired'                                => 'Kedaluwarsa',
    default                                  => ucfirst($v)
  };
};

/* Tentukan tombol utama */
$primaryBtn   = null;
$secondaryBtn = null;

$isReg = (bool)$reg;

/* Kontributor selesai? */
$kontributorDone = false;
if ($reg) {
  foreach (['contributor_done','kontributor_done','profile_completed','is_profile_completed'] as $f) {
    if (array_key_exists($f,$reg)) { $kontributorDone = (bool)$reg[$f]; break; }
  }
}

/* Aturan tombol */
if (!$isReg) {
  $primaryBtn = ['label'=>'Daftar', 'url'=>site_url('/presenter/events/register/'.(int)$event['id']), 'class'=>'btn-primary'];
} else {
  if (!$kontributorDone) {
    $primaryBtn   = ['label'=>'Lengkapi Kontributor', 'url'=>site_url('/presenter/kontributor/start/'.(int)$event['id']), 'class'=>'btn-primary'];
    $secondaryBtn = ['label'=>'Batalkan Pendaftaran', 'url'=>site_url('/presenter/events/cancel/'.(int)$event['id']), 'class'=>'btn-outline-danger'];
  } else {
    if (!$abStatus) {
      $primaryBtn = ['label'=>'Upload Abstrak', 'url'=>site_url('/presenter/abstrak/create/'.(int)$event['id']), 'class'=>'btn-primary'];
    } else {
      if ($abStatus === 'ditolak') {
        $primaryBtn = ['label'=>'Upload Ulang Abstrak', 'url'=>site_url('/presenter/abstrak/create/'.(int)$event['id']), 'class'=>'btn-warning'];
      } elseif ($abStatus === 'diterima') {
        if (!$hasFullpaper) {
          $primaryBtn = ['label'=>'Upload Full Paper', 'url'=>site_url('/presenter/fullpaper/create/'.(int)$event['id']), 'class'=>'btn-primary'];
        } else {
          $isRevision = in_array($fpStatus, ['revisi','revision','ditolak','rejected'], true);
          $isAccepted = in_array($fpStatus, ['diterima','accepted','acc','approved'], true);
          $isWaiting  = !$isRevision && !$isAccepted;

          if ($isRevision) {
            $primaryBtn = ['label'=>'Upload Ulang Full Paper', 'url'=>site_url('/presenter/fullpaper/create/'.(int)$event['id']), 'class'=>'btn-warning'];
          } elseif ($isAccepted) {
            if (!$payStatus) {
              $primaryBtn = ['label'=>'Lanjutkan Pembayaran', 'url'=>site_url('/presenter/pembayaran/instruction/'.(int)$event['id']), 'class'=>'btn-success'];
            } elseif ($payStatus === 'pending') {
              $primaryBtn = ['label'=>'Cek Status Pembayaran', 'url'=>site_url('/presenter/pembayaran'), 'class'=>'btn-outline-success'];
            } elseif (in_array($payStatus, ['rejected','ditolak','canceled','expired'], true)) {
              $primaryBtn = ['label'=>'Bayar Ulang', 'url'=>site_url('/presenter/pembayaran/instruction/'.(int)$event['id']), 'class'=>'btn-danger'];
            } elseif ($payStatus === 'verified') {
              $primaryBtn = ['label'=>'Buka Halaman Absensi', 'url'=>site_url('/presenter/absensi'), 'class'=>'btn-info'];
            }
          } elseif ($isWaiting) {
            $primaryBtn = null; // menunggu review FP
          }
        }
      } else {
        $primaryBtn = null; // abstrak menunggu
      }
    }
  }
}

// helper tanggal
$fmt = fn($s,$t=false)=> $s ? date($t?'d M Y H:i':'d M Y', strtotime($s)) : '-';
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <!-- Header -->
      <div class="header-section header-blue d-flex justify-content-between align-items-center mb-3">
        <div>
          <h3 class="welcome-text mb-1"><i class="bi bi-info-circle me-2"></i><?= esc($event['title'] ?? 'Event') ?></h3>
          <div class="text-white-50">Detail event & status pendaftaran</div>
        </div>
        <div class="text-end d-none d-md-block">
          <small class="text-white-50 d-block">Tanggal event</small>
          <strong class="text-white"><?= $fmt($event['event_date']) ?> • <?= esc($event['event_time']) ?></strong>
        </div>
      </div>

      <div class="row g-3">

        <!-- Informasi Event -->
        <div class="col-12 col-lg-7">
          <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-gradient-primary text-white">
              <h5 class="mb-0"><i class="bi bi-calendar-event me-2"></i>Informasi Event</h5>
            </div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-12 col-md-6">
                  <div class="text-muted small">Tanggal</div>
                  <div class="fw-semibold"><?= $fmt($event['event_date']) ?> <?= esc($event['event_time'] ? '• '.$event['event_time'] : '') ?></div>
                </div>
                <div class="col-12 col-md-6">
                  <div class="text-muted small">Format</div>
                  <div class="fw-semibold"><?= strtoupper($event['format'] ?? '-') ?></div>
                </div>
                <div class="col-12">
                  <div class="text-muted small">Lokasi</div>
                  <div class="fw-semibold"><?= esc($event['location'] ?: '-') ?></div>
                </div>
                <?php if (!empty($event['zoom_link'])): ?>
                <div class="col-12">
                  <div class="text-muted small">Link Online</div>
                  <div class="fw-semibold"><a href="<?= esc($event['zoom_link']) ?>" target="_blank" rel="noopener">Buka Tautan</a></div>
                </div>
                <?php endif; ?>
                <div class="col-12 col-md-6">
                  <div class="text-muted small">Batas Pendaftaran</div>
                  <div class="fw-semibold"><?= $fmt($event['registration_deadline'], true) ?></div>
                </div>
                <div class="col-12 col-md-6">
                  <div class="text-muted small">Batas Abstrak</div>
                  <div class="fw-semibold"><?= $fmt($event['abstract_deadline'], true) ?></div>
                </div>
                <div class="col-12 col-md-6">
                  <div class="text-muted small">Batas Full Paper</div>
                  <div class="fw-semibold"><?= $fmt($event['full_paper_deadline'], true) ?></div>
                </div>
                <div class="col-12 col-md-6">
                  <div class="text-muted small">Harga (Presenter)</div>
                  <div class="fw-semibold">
                    <?= isset($price) && $price !== null ? 'Rp '.number_format((float)$price,0,',','.') : '-' ?>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Deskripsi Event (dipisah) -->
          <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-light">
              <h6 class="mb-0"><i class="bi bi-file-text me-2"></i>Deskripsi Event</h6>
            </div>
            <div class="card-body">
              <div class="text-muted"><?= nl2br(esc($event['description'] ?? '-')) ?></div>
            </div>
          </div>

          <!-- Daftar Kontributor -->
          <div class="card shadow-sm border-0">
            <div class="card-header bg-light d-flex align-items-center justify-content-between">
              <h6 class="mb-0"><i class="bi bi-people me-2"></i>Daftar Kontributor</h6>
              <?php if ($isReg): ?>
                <a href="<?= site_url('/presenter/kontributor/start/'.(int)$event['id']) ?>" class="btn btn-sm btn-primary">
                  <i class="bi bi-pencil-square me-1"></i>Ubah
                </a>
              <?php endif; ?>
            </div>
            <div class="card-body p-0">
              <?php if (empty($contributors)): ?>
                <div class="p-3 text-muted">Belum ada data kontributor.</div>
              <?php else: ?>
                <div class="table-responsive">
                  <table class="table table-sm mb-0">
                    <thead class="table-light">
                      <tr>
                        <th style="width: 140px;">Peran</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Afiliasi</th>
                        <th>Negara</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($contributors as $c): ?>
                        <tr>
                          <td><?= esc($c['role'] ?? '-') ?></td>
                          <td><?= esc($c['nama'] ?? $c['name'] ?? '-') ?></td>
                          <td><?= esc($c['email'] ?? '-') ?></td>
                          <td><?= esc($c['afiliasi'] ?? $c['affiliation'] ?? '-') ?></td>
                          <td><?= esc($c['negara'] ?? $c['country'] ?? '-') ?></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php endif; ?>
            </div>
          </div>

        </div>

        <!-- Progress & Aksi -->
        <div class="col-12 col-lg-5">
          <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-gradient-primary text-white">
              <h5 class="mb-0"><i class="bi bi-flag me-2"></i>Progress Pendaftaran</h5>
            </div>
            <div class="card-body">

              <ul class="list-group list-group-flush mb-3">
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  <span>Pendaftaran</span>
                  <strong><?= $isReg ? 'Terdaftar'.(!$kontributorDone ? ' (lengkapi kontributor)' : '') : 'Belum' ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  <span>Abstrak</span>
                  <strong><?= $nice($abStatus) ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  <span>Full Paper</span>
                  <strong><?= $nice($fpStatus ?: ($hasFullpaper ? 'uploaded' : '')) ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  <span>Pembayaran</span>
                  <strong><?= $nice($payStatus) ?></strong>
                </li>
              </ul>

              <div class="d-grid gap-2">
                <?php if ($primaryBtn): ?>
                  <a class="btn <?= esc($primaryBtn['class']) ?>" href="<?= esc($primaryBtn['url']) ?>">
                    <?= esc($primaryBtn['label']) ?>
                  </a>
                <?php endif; ?>

                <?php if ($secondaryBtn): ?>
                  <a class="btn <?= esc($secondaryBtn['class']) ?>" href="<?= esc($secondaryBtn['url']) ?>">
                    <?= esc($secondaryBtn['label']) ?>
                  </a>
                <?php endif; ?>
              </div>

              <?php if (!$primaryBtn && !$secondaryBtn): ?>
                <div class="text-muted small mt-2">Tidak ada aksi yang perlu dilakukan saat ini.</div>
              <?php endif; ?>

            </div>
          </div>
        </div>

      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<style>
  :root{ --primary-color:#2563eb; --info-color:#06b6d4; }
  .header-section.header-blue{
    background:linear-gradient(135deg,var(--primary-color),#1e40af);
    color:#fff; padding:24px; border-radius:16px; box-shadow:0 8px 28px rgba(0,0,0,.12);
  }
  .bg-gradient-primary{ background:linear-gradient(135deg,var(--primary-color),var(--info-color))!important; }
</style> 