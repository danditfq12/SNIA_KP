<?php
$event     = $event ?? [];
$reg       = $reg ?? null;
$abstrak   = $abstrak ?? null;
$payment   = $payment ?? null;
$fullpaper = $fullpaper ?? null;

// Normalisasi status
$abStatus  = strtolower($abstrak['status'] ?? '');
$fpStatus  = strtolower($fullpaper['status'] ?? '');
$payStatus = strtolower($payment['status'] ?? '');

// Helper label
$nice = function($v) {
  if (!$v) return 'Belum';
  $v = strtolower($v);
  return match($v){
    'diterima' => 'Diterima',
    'ditolak'  => 'Ditolak',
    'revisi'   => 'Revisi',
    'menunggu','sedang_direview' => 'Menunggu',
    'pending'  => 'Pending',
    'verified' => 'Terverifikasi',
    'rejected' => 'Ditolak',
    'canceled' => 'Dibatalkan',
    'expired'  => 'Kedaluwarsa',
    default    => ucfirst($v)
  };
};

// Tentukan tombol utama (1 tombol) + kasus khusus awal 2 tombol
$primaryBtn = null;   // ['label'=>..., 'url'=>..., 'class'=>...]
$secondaryBtn = null; // hanya dipakai untuk "Batalkan pendaftaran" pada kasus awal

$isReg = (bool)$reg;

// Kontributor selesai?
$kontributorDone = false;
if ($reg) {
  foreach (['contributor_done','kontributor_done','profile_completed','is_profile_completed'] as $f) {
    if (array_key_exists($f,$reg)) { $kontributorDone = (bool)$reg[$f]; break; }
  }
}

// Aturan tombol (flow tidak diubah)
if (!$isReg) {
  $primaryBtn = ['label'=>'Daftar', 'url'=>'/presenter/events/register/'.(int)$event['id'], 'class'=>'btn-primary'];
} else {
  if (!$kontributorDone) {
    $primaryBtn   = ['label'=>'Daftar Lanjutan (Kontributor)', 'url'=>'/presenter/kontributor/start/'.(int)$event['id'], 'class'=>'btn-primary'];
    $secondaryBtn = ['label'=>'Batalkan Pendaftaran', 'url'=>'/presenter/events/cancel/'.(int)$event['id'], 'class'=>'btn-outline-danger'];
  } else {
    // Sudah selesai kontributor
    if (!$abStatus) {
      $primaryBtn = ['label'=>'Upload Abstrak', 'url'=>'/presenter/abstrak/create/'.(int)$event['id'], 'class'=>'btn-primary'];
    } else {
      if ($abStatus === 'ditolak') {
        $primaryBtn = ['label'=>'Upload Ulang Abstrak', 'url'=>'/presenter/abstrak/create/'.(int)$event['id'], 'class'=>'btn-warning'];
      } elseif ($abStatus === 'diterima') {
        // Cek Full Paper
        if (!$fpStatus) {
          $primaryBtn = ['label'=>'Upload Full Paper', 'url'=>'/presenter/fullpaper/create/'.(int)$event['id'], 'class'=>'btn-primary'];
        } elseif ($fpStatus === 'revisi' || $fpStatus === 'ditolak') {
          $primaryBtn = ['label'=>'Upload Ulang Full Paper', 'url'=>'/presenter/fullpaper/create/'.(int)$event['id'], 'class'=>'btn-warning'];
        } elseif ($fpStatus === 'diterima') {
          // Keduanya ACC → pembayaran
          if (!$payStatus) {
            $primaryBtn = ['label'=>'Lanjutkan Pembayaran', 'url'=>'/presenter/pembayaran/instruction/'.(int)$event['id'], 'class'=>'btn-success'];
          } elseif ($payStatus === 'pending') {
            $primaryBtn = ['label'=>'Cek Status Pembayaran', 'url'=>'/presenter/pembayaran', 'class'=>'btn-outline-success'];
          } elseif (in_array($payStatus, ['rejected','ditolak','canceled','expired'])) {
            $primaryBtn = ['label'=>'Bayar Ulang', 'url'=>'/presenter/pembayaran/instruction/'.(int)$event['id'], 'class'=>'btn-danger'];
          } elseif ($payStatus === 'verified') {
            $primaryBtn = ['label'=>'Buka Halaman Absensi', 'url'=>'/presenter/absensi', 'class'=>'btn-info'];
          }
        } else {
          // fullpaper menunggu → tidak tampilkan tombol aksi khusus
          $primaryBtn = null;
        }
      } else {
        // Abstrak menunggu → tidak ada tombol
        $primaryBtn = null;
      }
    }
  }
}

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
          <strong class="text-white"><?= date('d M Y', strtotime($event['event_date'])) ?> • <?= esc($event['event_time']) ?></strong>
        </div>
      </div>

      <div class="row g-3">
        <!-- Info Event -->
        <div class="col-12 col-lg-7">
          <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-gradient-primary text-white">
              <h5 class="mb-0"><i class="bi bi-calendar-event me-2"></i>Informasi Event</h5>
            </div>
            <div class="card-body">
              <div class="mb-2"><strong>Format Event:</strong> <?= strtoupper($event['format']) ?></div>
              <div class="mb-2"><strong>Lokasi:</strong> <?= esc($event['location'] ?: '-') ?></div>
              <?php if (!empty($event['zoom_link'])): ?>
                <div class="mb-2"><strong>Zoom:</strong> <a href="<?= esc($event['zoom_link']) ?>" target="_blank" rel="noopener">Link</a></div>
              <?php endif; ?>
              <hr>
              <div class="mb-2 small text-muted">
                Tutup Pendaftaran: <strong><?= $event['registration_deadline'] ? date('d M Y H:i', strtotime($event['registration_deadline'])) : '-' ?></strong><br>
                Batas Abstrak: <strong><?= $event['abstract_deadline'] ? date('d M Y H:i', strtotime($event['abstract_deadline'])) : '-' ?></strong><br>
                <?php if (!empty($event['full_paper_deadline'])): ?>
                  Batas Full Paper: <strong><?= date('d M Y H:i', strtotime($event['full_paper_deadline'])) ?></strong>
                <?php endif; ?>
              </div>
              <p class="mb-0"><?= esc($event['description'] ?? '') ?></p>
            </div>
          </div>
        </div>

        <!-- Status & Aksi -->
        <div class="col-12 col-lg-5">
          <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-gradient-primary text-white">
              <h5 class="mb-0"><i class="bi bi-flag me-2"></i>Progress Pendaftaran</h5>
            </div>
            <div class="card-body">

              <!-- Info 4 langkah -->
              <ul class="list-group list-group-flush mb-3">
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  <span>Pendaftaran</span>
                  <strong><?= $isReg ? 'Terdaftar'.(!$kontributorDone ? ' (butuh daftar lanjutan)' : '') : 'Belum' ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  <span>Abstrak</span>
                  <strong><?= $nice($abStatus) ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  <span>Full Paper</span>
                  <strong><?= $nice($fpStatus) ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  <span>Pembayaran</span>
                  <strong><?= $nice($payStatus) ?></strong>
                </li>
              </ul>

              <!-- Tombol Aksi (1 tombol, kecuali kasus awal 2 tombol) -->
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