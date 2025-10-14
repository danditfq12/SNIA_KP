<?php
$event        = $event ?? [];
$reg          = $reg ?? null;
$abstrak      = $abstrak ?? null;
$payment      = $payment ?? null;
$flow         = $flow ?? [];
$contributors = $contributors ?? [];
$price        = $price ?? null;

/* ================= Utils & Normalisasi ================= */
$abStatus  = strtolower($abstrak['status'] ?? '');
$fpStatus  = strtolower($abstrak['full_paper_status'] ?? '');
$payStatus = strtolower($payment['status'] ?? '');

$hasFullpaper = !empty($abstrak['full_paper_path']) || !empty($abstrak['full_paper_status']);

$fmt = function($s, $withTime=false){
  if (!$s) return '-';
  $ts = strtotime((string)$s);
  return $withTime ? date('d M Y H:i', $ts) : date('d M Y', $ts);
};

/* label format */
$formatLabel = function($f){
  $f = strtolower((string)$f);
  return $f === 'both' ? 'Hybrid' : ucfirst($f ?: '-');
};

/* nice status label */
$nice = function($v) {
  if (!$v) return 'Belum';
  $v = strtolower((string)$v);
  return match($v){
    'diterima','accepted','acc','approved'                      => 'Diterima',
    'ditolak','rejected'                                        => 'Ditolak',
    'revisi','revision'                                         => 'Revisi',
    'menunggu','sedang_direview','uploaded','pending'           => 'Menunggu',
    'verified'                                                  => 'Terverifikasi',
    'canceled'                                                  => 'Dibatalkan',
    'expired'                                                   => 'Kedaluwarsa',
    default                                                     => ucfirst($v)
  };
};

/* ====== CTA ====== */
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

$eventId = (int)($event['id'] ?? 0);

if (!$isReg) {
  $primaryBtn = ['label'=>'Daftar', 'url'=>site_url('presenter/events/register/'.$eventId), 'class'=>'btn-primary'];
} else {
  if (!$kontributorDone) {
    $primaryBtn   = ['label'=>'Lengkapi Kontributor', 'url'=>site_url('presenter/kontributor/start/'.$eventId), 'class'=>'btn-primary'];
    $secondaryBtn = ['label'=>'Batalkan Pendaftaran', 'url'=>site_url('presenter/events/cancel/'.$eventId), 'class'=>'btn-outline-danger'];
  } else {
    if (!$abStatus) {
      $primaryBtn = ['label'=>'Upload Abstrak', 'url'=>site_url('presenter/abstrak/create/'.$eventId), 'class'=>'btn-primary'];
    } else {
      if ($abStatus === 'ditolak') {
        $primaryBtn = ['label'=>'Upload Ulang Abstrak', 'url'=>site_url('presenter/abstrak/create/'.$eventId), 'class'=>'btn-warning'];
      } elseif ($abStatus === 'diterima') {
        if (!$hasFullpaper) {
          $primaryBtn = ['label'=>'Upload Full Paper', 'url'=>site_url('presenter/fullpaper/create/'.$eventId), 'class'=>'btn-primary'];
        } else {
          $isRevision = in_array($fpStatus, ['revisi','revision','ditolak','rejected'], true);
          $isAccepted = in_array($fpStatus, ['diterima','accepted','acc','approved'], true);
          $isWaiting  = !$isRevision && !$isAccepted;

          if ($isRevision) {
            $primaryBtn = ['label'=>'Upload Ulang Full Paper', 'url'=>site_url('presenter/fullpaper/create/'.$eventId), 'class'=>'btn-warning'];
          } elseif ($isAccepted) {
            if (!$payStatus) {
              $primaryBtn = ['label'=>'Lanjutkan Pembayaran', 'url'=>site_url('presenter/pembayaran/instruction/'.$eventId), 'class'=>'btn-success'];
            } elseif ($payStatus === 'pending') {
              $primaryBtn = ['label'=>'Cek Status Pembayaran', 'url'=>site_url('presenter/pembayaran'), 'class'=>'btn-outline-success'];
            } elseif (in_array($payStatus, ['rejected','ditolak','canceled','expired'], true)) {
              $primaryBtn = ['label'=>'Bayar Ulang', 'url'=>site_url('presenter/pembayaran/instruction/'.$eventId), 'class'=>'btn-danger'];
            } elseif ($payStatus === 'verified') {
              $primaryBtn = ['label'=>'Buka Halaman Absensi', 'url'=>site_url('presenter/absensi'), 'class'=>'btn-info'];
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
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill page-wrap-blue">
    <div class="container-xxl px-3 px-md-4 py-4">

      <!-- HERO -->
      <div class="card-hero mb-4">
        <div class="hero-body">
          <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
              <h3 class="hero-title mb-1">
                <i class="bi bi-info-circle me-2"></i><?= esc($event['title'] ?? 'Event') ?>
              </h3>
              <div class="text-white-70 small">Detail event & status pendaftaran.</div>
            </div>
            <div class="d-none d-md-block text-end">
              <div class="text-white-70 small">Tanggal Event</div>
              <div class="fw-bold"><?= esc($fmt($event['event_date'] ?? null)) ?><?= !empty($event['event_time']) ? ' • '.esc($event['event_time']) : '' ?></div>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-3">

        <!-- Informasi Event -->
        <div class="col-12 col-lg-7">
          <div class="card shadow-soft card-glass-plain mb-3">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
              <span class="badge bg-blue-soft"><i class="bi bi-calendar-event"></i></span>
              <h5 class="mb-0 fw-semibold text-blue-900">Informasi Event</h5>
            </div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-12 col-md-6">
                  <div class="text-muted small">Tanggal</div>
                  <div class="fw-semibold text-blue-900">
                    <?= esc($fmt($event['event_date'] ?? null)) ?><?= !empty($event['event_time']) ? ' • '.esc($event['event_time']) : '' ?>
                  </div>
                </div>
                <div class="col-12 col-md-6">
                  <div class="text-muted small">Format</div>
                  <div class="fw-semibold text-blue-900"><?= esc($formatLabel($event['format'] ?? '')) ?></div>
                </div>
                <div class="col-12">
                  <div class="text-muted small">Lokasi</div>
                  <div class="fw-semibold text-blue-900"><?= esc(($event['location'] ?? '') ?: '-') ?></div>
                </div>
                <?php if (!empty($event['zoom_link'])): ?>
                <div class="col-12">
                  <div class="text-muted small">Link Online</div>
                  <div class="fw-semibold">
                    <a href="<?= esc($event['zoom_link']) ?>" target="_blank" rel="noopener noreferrer">Buka Tautan</a>
                  </div>
                </div>
                <?php endif; ?>
                <div class="col-12 col-md-6">
                  <div class="text-muted small">Batas Pendaftaran</div>
                  <div class="fw-semibold text-blue-900"><?= esc($fmt($event['registration_deadline'] ?? null, true)) ?></div>
                </div>
                <div class="col-12 col-md-6">
                  <div class="text-muted small">Batas Abstrak</div>
                  <div class="fw-semibold text-blue-900"><?= esc($fmt($event['abstract_deadline'] ?? null, true)) ?></div>
                </div>
                <div class="col-12 col-md-6">
                  <div class="text-muted small">Batas Full Paper</div>
                  <div class="fw-semibold text-blue-900"><?= esc($fmt($event['full_paper_deadline'] ?? null, true)) ?></div>
                </div>
                <div class="col-12 col-md-6">
                  <div class="text-muted small">Harga (Presenter)</div>
                  <div class="fw-semibold text-blue-900">
                    <?= isset($price) && $price !== null ? 'Rp '.number_format((float)$price,0,',','.') : '-' ?>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Deskripsi Event -->
          <div class="card shadow-soft card-glass-plain mb-3">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
              <span class="badge bg-blue-soft"><i class="bi bi-file-text"></i></span>
              <h6 class="mb-0 fw-semibold text-blue-900">Deskripsi Event</h6>
            </div>
            <div class="card-body">
              <div class="text-muted"><?= nl2br(esc($event['description'] ?? '-')) ?></div>
            </div>
          </div>

          <!-- Daftar Kontributor -->
          <div class="card shadow-soft card-glass-plain">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center justify-content-between">
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-blue-soft"><i class="bi bi-people"></i></span>
                <h6 class="mb-0 fw-semibold text-blue-900">Daftar Kontributor</h6>
              </div>
              <?php if ($isReg): ?>
                <a href="<?= site_url('presenter/kontributor/start/'.$eventId) ?>" class="btn btn-sm btn-primary">
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
                        <th style="width:140px;">Peran</th>
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
                          <td><?= esc($c['nama'] ?? ($c['name'] ?? '-')) ?></td>
                          <td><?= esc($c['email'] ?? '-') ?></td>
                          <td><?= esc($c['afiliasi'] ?? ($c['affiliation'] ?? '-')) ?></td>
                          <td><?= esc($c['negara'] ?? ($c['country'] ?? '-')) ?></td>
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
          <div class="card shadow-soft card-glass-plain mb-3">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
              <span class="badge bg-blue-soft"><i class="bi bi-flag"></i></span>
              <h5 class="mb-0 fw-semibold text-blue-900">Progress Pendaftaran</h5>
            </div>
            <div class="card-body">

              <ul class="list-group list-group-flush mb-3">
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  <span>Pendaftaran</span>
                  <strong class="text-blue-900">
                    <?= $isReg ? 'Terdaftar'.(!$kontributorDone ? ' (lengkapi kontributor)' : '') : 'Belum' ?>
                  </strong>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  <span>Abstrak</span>
                  <strong class="text-blue-900"><?= esc($nice($abStatus)) ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  <span>Full Paper</span>
                  <strong class="text-blue-900"><?= esc($nice($fpStatus ?: ($hasFullpaper ? 'uploaded' : ''))) ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  <span>Pembayaran</span>
                  <strong class="text-blue-900"><?= esc($nice($payStatus)) ?></strong>
                </li>
              </ul>

              <div class="d-grid gap-2">
                <?php if ($primaryBtn):
                  $lbl = strtolower((string)($primaryBtn['label'] ?? ''));
                  $icon = 'bi-info-circle';
                  if (str_contains($lbl,'daftar')) $icon='bi-box-arrow-in-right';
                  elseif (str_contains($lbl,'kontributor')) $icon='bi-people';
                  elseif (str_contains($lbl,'upload')) $icon='bi-upload';
                  elseif (str_contains($lbl,'bayar')) $icon='bi-credit-card';
                  elseif (str_contains($lbl,'absen')) $icon='bi-qr-code-scan';
                  elseif (str_contains($lbl,'cek') || str_contains($lbl,'status')) $icon='bi-clock-history';
                ?>
                  <a class="btn <?= esc($primaryBtn['class']) ?>" href="<?= esc($primaryBtn['url']) ?>">
                    <i class="bi <?= esc($icon) ?> me-1"></i><?= esc($primaryBtn['label']) ?>
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
:root{
  /* konsisten dengan patokan */
  --blue-50:#eff6ff; --blue-100:#dbeafe; --blue-200:#bfdbfe; --blue-300:#93c5fd;
  --blue-400:#60a5fa; --blue-500:#3b82f6; --blue-600:#2563eb; --blue-700:#1d4ed8; --blue-800:#1e40af; --blue-900:#1e3a8a;
  --muted:#6b7280; --ink:#0f172a; --radius:16px;
  --side-pad: clamp(1rem, 2.3vw, 2.2rem);
}

.container-xxl{ max-width:min(100%, 1560px); padding-left:var(--side-pad)!important; padding-right:var(--side-pad)!important; margin-inline:auto; }
body{ font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size:15.5px; line-height:1.6; color:var(--ink); }

/* Background */
.page-wrap-blue{
  min-height:100vh; padding-top:72px;
  background:
    radial-gradient(1000px 380px at 10% -10%, rgba(59,130,246,.16), rgba(59,130,246,0) 60%),
    radial-gradient(1000px 380px at 90% 110%, rgba(59,130,246,.12), rgba(59,130,246,0) 70%),
    linear-gradient(180deg, var(--blue-50), #fff 40%);
}

/* HERO */
.card-hero{ border:0; border-radius:var(--radius); overflow:hidden; box-shadow:0 12px 28px rgba(30,64,175,.18); }
.card-hero .hero-body{
  background:linear-gradient(135deg,var(--blue-700),var(--blue-800)); color:#fff;
  padding:1.8rem 1.2rem; min-height:176px;
}
.hero-title{ font-weight:800; }
.text-white-70{ color:rgba(255,255,255,.85)!important; }

/* Glass cards */
.card-glass-plain{ backdrop-filter:blur(6px); background:rgba(255,255,255,.96); border-radius:14px; border:1px solid rgba(30,64,175,.10); }
.shadow-soft{ box-shadow:0 10px 24px rgba(30,64,175,.08); }
.card-header{ padding:1rem 1rem .45rem 1rem !important; }
.card-body{   padding:1.05rem !important; }

/* Badge kecil */
.bg-blue-soft{ background:var(--blue-200); color:var(--blue-800); border-radius:12px; padding:.5rem .7rem; font-weight:600; font-size:.9rem; }
.text-blue-900{ color:var(--blue-900)!important; }

/* Table (kontributor) */
.table{ font-size:.95rem; margin-bottom:0; }
.table thead th{
  background-color:var(--blue-50)!important;
  border-bottom:1px solid var(--blue-200);
  font-weight:600; color:var(--blue-900);
  font-size:.85rem; text-transform:uppercase; letter-spacing:.4px;
  padding:.75rem 1rem;
}
.table tbody tr{ border-bottom:1px solid rgba(30,64,175,.08); }
.table tbody td{
  padding:.75rem 1rem; vertical-align:middle; border-top:none; font-size:.95rem;
}
.table-responsive{ border:1px solid rgba(30,64,175,.08); border-radius:12px; overflow:hidden; }

/* Progress list */
.list-group-item{
  background:transparent!important;
  border-left:none!important; border-right:none!important;
  font-size:.95rem; padding:.7rem 0; font-weight:500;
}
.list-group-item:first-child{ border-top:none!important; }
.list-group-item:last-child{  border-bottom:none!important; }

/* Buttons */
.btn{ font-weight:800; border-radius:10px; font-size:.98rem; padding:.6rem 1.05rem; }
.btn-primary{ background:var(--blue-600); border-color:var(--blue-600); box-shadow:0 4px 12px rgba(37,99,235,.2); }
.btn-success{ background:#059669; border-color:#059669; box-shadow:0 4px 12px rgba(5,150,105,.2); }
.btn-warning{ background:#d97706; border-color:#d97706; box-shadow:0 4px 12px rgba(217,119,6,.2); }
.btn-danger{  background:#dc2626; border-color:#dc2626; box-shadow:0 4px 12px rgba(220,38,38,.2); }
.btn-info{    background:#06b6d4; border-color:#06b6d4; box-shadow:0 4px 12px rgba(6,182,212,.2); }

/* Responsive */
@media (max-width:767.98px){
  .card-hero .hero-body{ padding:1.4rem 1rem; min-height:165px; }
}
@media (max-width:575.98px){
  .container-xxl{ padding-left: calc(var(--side-pad) - .25rem) !important; padding-right: calc(var(--side-pad) - .25rem) !important; }
  .table thead th, .table tbody td{ padding:.6rem .7rem; font-size:.85rem; }
  .list-group-item{ padding:.6rem 0; }
}
</style>
