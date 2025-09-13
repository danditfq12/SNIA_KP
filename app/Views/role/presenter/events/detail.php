<?php
$event   = $event ?? [];
$reg     = $reg ?? null;
$abstrak = $abstrak ?? null;
$payment = $payment ?? null;
$flow    = $flow ?? ['state'=>'belum_daftar','label'=>'Belum terdaftar','hint'=>''];
$price   = (int)($price ?? 0);
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
        <!-- Kiri: Info Event -->
        <div class="col-12 col-lg-7">
          <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-gradient-primary text-white">
              <h5 class="mb-0"><i class="bi bi-calendar-event me-2"></i>Informasi Event</h5>
            </div>
            <div class="card-body">
              <div class="mb-2"><strong>Format:</strong> <?= strtoupper($event['format']) ?></div>
              <div class="mb-2"><strong>Lokasi:</strong> <?= esc($event['location'] ?: '-') ?></div>
              <?php if (!empty($event['zoom_link'])): ?>
                <div class="mb-2"><strong>Zoom:</strong> <a href="<?= esc($event['zoom_link']) ?>" target="_blank">Link</a></div>
              <?php endif; ?>
              <div class="mb-2"><strong>Harga Presenter:</strong> Rp <?= number_format($price,0,',','.') ?></div>
              <hr>
              <div class="mb-2 small text-muted">
                Tutup Pendaftaran: <strong><?= $event['registration_deadline'] ? date('d M Y H:i', strtotime($event['registration_deadline'])) : '-' ?></strong><br>
                Batas Abstrak: <strong><?= $event['abstract_deadline'] ? date('d M Y H:i', strtotime($event['abstract_deadline'])) : '-' ?></strong>
              </div>
              <p class="mb-0"><?= esc($event['description'] ?? '') ?></p>
            </div>
          </div>
        </div>

        <!-- Kanan: Status & Aksi -->
        <div class="col-12 col-lg-5">
          <div class="card shadow-sm border-0 mb-3">
            <div class="card-header bg-gradient-primary text-white">
              <h5 class="mb-0"><i class="bi bi-flag me-2"></i>Status</h5>
            </div>
            <div class="card-body">
              <div class="mb-2">
                <span class="badge rounded-pill bg-primary-subtle text-primary fw-normal"><?= esc($flow['label']) ?></span>
                <?php if (!empty($flow['hint'])): ?>
                  <div class="small text-muted mt-1"><?= esc($flow['hint']) ?></div>
                <?php endif; ?>
              </div>

              <ul class="list-group list-group-flush mb-3">
                <li class="list-group-item d-flex justify-content-between">
                  <span>Pendaftaran</span>
                  <strong><?= $reg ? 'Terdaftar' : 'Belum' ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                  <span>Abstrak</span>
                  <strong><?= $abstrak['status'] ?? 'Belum' ?></strong>
                </li>
                <li class="list-group-item d-flex justify-content-between">
                  <span>Pembayaran</span>
                  <strong><?= $payment['status'] ?? 'Belum' ?></strong>
                </li>
              </ul>

              <div class="d-grid gap-2">
                <?php if (!$reg): ?>
                  <button class="btn btn-primary" onclick="confirmRegister(<?= (int)$event['id'] ?>)">
                    <i class="bi bi-box-arrow-in-right"></i> Daftar
                  </button>
                <?php endif; ?>

                <?php if ($reg && ($flow['can']['cancel'] ?? false)): ?>
                  <button class="btn btn-outline-danger" onclick="confirmCancel(<?= (int)$event['id'] ?>)">
                    <i class="bi bi-x-circle"></i> Batalkan Pendaftaran
                  </button>
                <?php endif; ?>

                <?php if ($reg && ($flow['can']['upload'] ?? false)): ?>
                  <a class="btn btn-primary" href="/presenter/abstrak?event=<?= (int)$event['id'] ?>">
                    <i class="bi bi-upload"></i> Upload Abstrak
                  </a>
                <?php endif; ?>

                <?php if ($reg && ($flow['can']['reupload'] ?? false)): ?>
                  <a class="btn btn-warning" href="/presenter/abstrak?event=<?= (int)$event['id'] ?>">
                    <i class="bi bi-arrow-repeat"></i> Unggah Ulang Revisi
                  </a>
                <?php endif; ?>

                <?php if ($reg && ($flow['can']['pay'] ?? false)): ?>
                  <a class="btn btn-success" href="/presenter/pembayaran/create/<?= (int)$event['id'] ?>">
                    <i class="bi bi-cash-coin"></i> Lanjut Bayar
                  </a>
                <?php endif; ?>

                <?php if ($reg && (($flow['can']['pay_detail'] ?? false) || ($flow['can']['pay_reupload'] ?? false))): ?>
                  <a class="btn btn-outline-success" href="/presenter/pembayaran/detail/<?= (int)($payment['id_pembayaran'] ?? 0) ?>">
                    <i class="bi bi-receipt"></i> Lihat Pembayaran
                  </a>
                <?php endif; ?>

                <?php if ($reg && ($flow['can']['absen'] ?? false)): ?>
                  <a class="btn btn-info" href="/presenter/absensi">
                    <i class="bi bi-qr-code-scan"></i> Buka Halaman Absensi
                  </a>
                <?php endif; ?>
              </div>

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
    --primary-color:#2563eb; --info-color:#06b6d4;
  }
  .header-section.header-blue{
    background:linear-gradient(135deg,var(--primary-color),#1e40af);
    color:#fff; padding:24px; border-radius:16px; box-shadow:0 8px 28px rgba(0,0,0,.12);
  }
  .bg-gradient-primary{ background:linear-gradient(135deg,var(--primary-color),var(--info-color))!important; }
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function confirmRegister(id){
  Swal.fire({
    title:'Daftar event?',
    text:'Anda akan tercatat sebagai presenter event ini.',
    icon:'question', showCancelButton:true,
    confirmButtonText:'Ya, daftar', cancelButtonText:'Batal'
  }).then(r=>{ if(r.isConfirmed){ location.href='/presenter/events/register/'+id; }});
}
function confirmCancel(id){
  Swal.fire({
    title:'Batalkan pendaftaran?',
    text:'Pendaftaran akan dihapus. Tindakan ini tidak bisa dibatalkan.',
    icon:'warning', showCancelButton:true,
    confirmButtonText:'Ya, batalkan', cancelButtonText:'Tidak'
  }).then(r=>{ if(r.isConfirmed){ location.href='/presenter/events/cancel/'+id; }});
}
</script>