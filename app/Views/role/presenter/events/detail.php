<?php
// app/Views/role/presenter/events/detail.php
$event        = $event ?? [];
$reg          = $reg ?? null;
$abstrak      = $abstrak ?? null;
$payment      = $payment ?? null;
$contributors = $contributors ?? [];
$price        = $price ?? null;
$actions      = $actions ?? ['primary'=>null,'secondary'=>null];
$flow         = $flow ?? ['state'=>null,'label'=>null,'hint'=>null];

/* Utils */
$abStatus  = strtolower($abstrak['status'] ?? '');
$fpStatus  = strtolower($abstrak['full_paper_status'] ?? '');
$payStatus = strtolower($payment['status'] ?? '');
$hasFull   = !empty($abstrak['full_paper_path']) || !empty($abstrak['full_paper_status']);
$fmt = fn($s,$t=false)=> $s ? ($t?date('d M Y H:i',strtotime($s)):date('d M Y',strtotime($s))) : '-';
$formatLabel = function($f){ $f=strtolower((string)$f); return $f==='both'?'Hybrid':ucfirst($f?:'-'); };
$nice = function($v){
  if (!$v) return 'Belum';
  $v=strtolower((string)$v);
  return match($v){
    'diterima','accepted','acc','approved' => 'Diterima',
    'ditolak','rejected'                   => 'Ditolak',
    'revisi','revision'                    => 'Revisi',
    'menunggu','sedang_direview','uploaded','pending' => 'Menunggu',
    'verified'=>'Terverifikasi','canceled'=>'Dibatalkan','expired'=>'Kedaluwarsa',
    default=>ucfirst($v)
  };
};
$isReg = (bool)$reg;

/* progress: kontributor selesai? */
$kontributorDone = false;
if ($reg) {
  foreach (['contributor_done','kontributor_done','profile_completed','is_profile_completed'] as $f) {
    if (array_key_exists($f,$reg)) {
      $v = $reg[$f];
      $kontributorDone = ($v === true || $v === 1 || $v === '1' || $v === 't' || $v === 'true');
      if ($kontributorDone) break;
    }
  }
}

/* pastikan ada minimal 1 kontributor */
if ($isReg && empty($contributors)) {
  $contributors = [[
    'role' => 'Presenter Utama',
    'nama' => $reg['nama'] ?? ($reg['full_name'] ?? (session()->get('nama') ?? '')),
    'email'=> $reg['email'] ?? (session()->get('email') ?? ''),
    'afiliasi'=>$reg['afiliasi'] ?? ($reg['institution'] ?? ''),
  ]];
}

$eventId = (int)($event['id'] ?? 0);

/* map state flow ke warna alert */
$flowState   = strtolower((string)($flow['state'] ?? ''));
$flowLabel   = (string)($flow['label'] ?? '');
$flowHint    = (string)($flow['hint']  ?? '');
$flowAlertCl = 'alert-secondary';
if (in_array($flowState, ['lengkapi_kontributor','upload_abstrak','upload_fullpaper','bayar'], true)) {
  $flowAlertCl = 'alert-primary';
} elseif (in_array($flowState, ['menunggu_abstrak','fp_menunggu_review','pembayaran_pending','menunggu_loa'], true)) {
  $flowAlertCl = 'alert-warning';
} elseif (in_array($flowState, ['fp_perbaikan','abstrak_ditolak','pembayaran_ditolak'], true)) {
  $flowAlertCl = 'alert-danger';
} elseif (in_array($flowState, ['pembayaran_kedaluwarsa'], true)) {
  $flowAlertCl = 'alert-secondary';
} elseif (in_array($flowState, ['siap_absen','sudah_absen'], true)) {
  $flowAlertCl = 'alert-success';
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
              <div class="fw-bold">
                <?= esc($fmt($event['event_date'] ?? null)) ?>
                <?= !empty($event['event_time']) ? ' • '.esc($event['event_time']) : '' ?>
              </div>
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
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($contributors as $c): ?>
                        <tr>
                          <td><?= esc($c['role'] ?? '-') ?></td>
                          <td><?= esc($c['nama'] ?? ($c['name'] ?? '-')) ?></td>
                          <td><?= esc($c['email'] ?? '-') ?></td>
                          <td><?= esc($c['afiliasi'] ?? ($c['affiliation'] ?? '-')) ?></td>
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

              <!-- Status Flow dari controller -->
              <?php if ($flowState): ?>
                <div class="alert <?= esc($flowAlertCl) ?> small py-2 px-3 mb-3">
                  <div class="fw-semibold mb-1">
                    <?= esc($flowLabel ?: 'Status terkini') ?>
                    <?php if ($flowState === 'abstrak_ditolak'): ?>
                      <span class="badge bg-danger-subtle text-danger border border-danger border-opacity-25 ms-1">
                        Final
                      </span>
                    <?php endif; ?>
                  </div>
                  <div class="mb-0">
                    <?= esc($flowHint ?: 'Status alur pendaftaran event Anda.') ?>
                  </div>
                </div>
              <?php endif; ?>

              <!-- EXTRA khusus abstrak ditolak: tekankan tidak bisa lanjut -->
              <?php if ($abStatus === 'ditolak'): ?>
                <div class="alert alert-danger-soft small py-2 px-3 mb-3 border border-danger-subtle">
                  <div class="fw-semibold mb-1">
                    Abstrak ditolak — event ini tidak bisa diikuti lagi.
                  </div>
                  <div class="mb-0">
                    Anda tetap dapat melihat detail abstrak di menu <strong>Abstrak</strong>,
                    namun tidak dapat mengunggah ulang abstrak baru untuk event ini.
                    Silakan mengikuti event lain jika ingin mengirim abstrak lagi.
                  </div>
                </div>
              <?php endif; ?>

              <ul class="list-group list-group-flush mb-3">
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  <span>Terdaftar</span>
                  <strong class="text-blue-900"><?= $isReg ? 'Ya' : 'Belum' ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  <span>Kontributor</span>
                  <strong class="text-blue-900"><?= $isReg ? ($kontributorDone ? 'Lengkap' : 'Belum') : 'Belum' ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  <span>Abstrak</span>
                  <strong class="text-blue-900"><?= esc($nice($abStatus)) ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  <span>Full Paper</span>
                  <strong class="text-blue-900"><?= esc($nice($fpStatus ?: ($hasFull ? 'uploaded' : ''))) ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                  <span>Pembayaran</span>
                  <strong class="text-blue-900"><?= esc($nice($payStatus)) ?></strong>
                </li>
              </ul>

              <div class="d-grid gap-2">
                <?php if (!empty($actions['primary'])):
                  $p = $actions['primary'];
                  $lbl = strtolower((string)($p['label'] ?? ''));
                  $icon = 'bi-info-circle';
                  if (str_contains($lbl,'daftar')) $icon='bi-box-arrow-in-right';
                  elseif (str_contains($lbl,'kontributor')) $icon='bi-people';
                  elseif (str_contains($lbl,'upload')) $icon='bi-upload';
                  elseif (str_contains($lbl,'bayar')) $icon='bi-credit-card';
                  elseif (str_contains($lbl,'absen')) $icon='bi-qr-code-scan';
                ?>
                  <a class="btn <?= esc($p['class'] ?? 'btn-primary') ?> <?= !empty($p['confirm']) ? 'js-swal-confirm' : '' ?><?= !empty($p['disabled']) ? ' disabled' : '' ?>"
                     href="<?= esc($p['url'] ?? '#') ?>"
                     <?= !empty($p['confirm']) ? 'data-confirm="'.esc($p['confirm']).'"' : '' ?>>
                    <i class="bi <?= esc($icon) ?> me-1"></i><?= esc($p['label'] ?? '') ?>
                  </a>
                <?php endif; ?>

                <?php if (!empty($actions['secondary'])):
                  $s = $actions['secondary']; ?>
                  <a class="btn <?= esc($s['class'] ?? 'btn-outline-secondary') ?> <?= !empty($s['confirm']) ? 'js-swal-confirm' : '' ?>"
                     href="<?= esc($s['url'] ?? '#') ?>"
                     <?= !empty($s['confirm']) ? 'data-confirm="'.esc($s['confirm']).'"' : '' ?>>
                    <?= esc($s['label'] ?? '') ?>
                  </a>
                <?php endif; ?>
              </div>

              <?php if (empty($actions['primary']) && empty($actions['secondary'])): ?>
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

.alert-danger-soft{
  background:rgba(220,38,38,.04);
  color:#b91c1c;
}

.bg-danger-subtle{ background:rgba(248,113,113,.15)!important; }
.border-danger-subtle{ border-color:rgba(248,113,113,.55)!important; }

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

<script>
/* === SweetAlert2 Helpers === */
(function(){
  // Konfirmasi link dengan data-confirm
  document.addEventListener('click', function(e){
    const a = e.target.closest('a.js-swal-confirm');
    if(!a) return;
    const msg = a.getAttribute('data-confirm');
    if(!msg) return;
    e.preventDefault();
    if (typeof Swal === 'undefined') { // fallback jika Swal tidak tersedia
      if (confirm(msg)) window.location.href = a.href;
      return;
    }
    Swal.fire({
      title: 'Konfirmasi',
      text: msg,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Ya',
      cancelButtonText: 'Batal',
    }).then(res=>{
      if(res.isConfirmed){ window.location.href = a.href; }
    });
  });

  // Flash message CI4 -> SweetAlert2
  <?php
    $flashTypes = ['success','error','warning','info'];
    foreach ($flashTypes as $t):
      $msg = session()->getFlashdata($t);
      if ($msg):
        $title = [
          'success'=>'Berhasil',
          'error'=>'Gagal',
          'warning'=>'Perhatian',
          'info'=>'Info'
        ][$t];
  ?>
  if (typeof Swal !== 'undefined') {
    Swal.fire({icon:'<?= $t ?>', title:'<?= $title ?>', text: '<?= esc($msg) ?>'});
  }
  <?php
      endif;
    endforeach;
  ?>
})();
</script>
