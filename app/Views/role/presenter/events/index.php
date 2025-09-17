<?php
$title = $title ?? 'Event';
$q     = $q ?? '';
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <!-- Header Biru -->
      <div class="header-section header-blue d-flex justify-content-between align-items-center mb-3">
        <div>
          <h3 class="welcome-text mb-1"><i class="bi bi-calendar3 me-2"></i>Event</h3>
          <div class="text-white-50">Daftar & ikuti alur presenter</div>
        </div>
        <div class="text-end d-none d-md-block">
          <small class="text-white-50 d-block">Hari ini</small>
          <strong class="text-white"><?= date('d M Y') ?></strong>
        </div>
      </div>

      <!-- Search Box (kartu biru) - COMPACT -->
      <div class="card shadow-sm mb-3 border-0 overflow-hidden">
        <div class="card-header bg-gradient-primary text-white">
          <div class="d-flex align-items-center justify-content-between">
            <h6 class="mb-0"><i class="bi bi-search me-2"></i>Cari Event</h6>
          </div>
        </div>
        <div class="card-body search-compact">
          <form class="row g-2 align-items-center" method="get" action="/presenter/events">
            <div class="col-12 col-md-8">
              <input class="form-control" name="q" value="<?= esc($q) ?>" placeholder="Ketik judul/lokasi/kata kunci...">
            </div>
            <div class="col-12 col-md-4 d-grid">
              <button class="btn btn-primary">
                <i class="bi bi-search me-1"></i>Cari
              </button>
            </div>
          </form>
        </div>
      </div>

      <?php
      // helper kecil untuk warna badge berdasarkan state flow
      $stateBadge = function (string $state): string {
        return match ($state) {
          'upload_abstrak'        => 'primary',
          'menunggu_abstrak'      => 'info',
          'revisi_abstrak'        => 'warning',
          'abstrak_ditolak'       => 'danger',
          'bayar'                 => 'warning',
          'pembayaran_pending'    => 'secondary',
          'pembayaran_dibatalkan' => 'danger',
          'siap_absen', 'sudah_absen' => 'success',
          default                 => 'secondary',
        };
      };
      ?>

      <!-- Event Tersedia -->
      <div class="card shadow-sm mb-4 border-0 overflow-hidden">
        <div class="card-header bg-gradient-primary text-white">
          <h5 class="mb-0"><i class="bi bi-lightning-charge me-2"></i>Event Tersedia (Buka Pendaftaran)</h5>
        </div>
        <div class="card-body">
          <?php if (empty($available)): ?>
            <div class="text-muted">Tidak ada event yang membuka pendaftaran.</div>
          <?php else: ?>
            <div class="row g-3">
              <?php foreach ($available as $e):
                $st    = $statusIndex[(int)$e['id']] ?? [];
                $state = $st['state'] ?? 'belum_daftar';
                $label = $st['label'] ?? '';
                $hint  = $st['hint']  ?? '';

                // override hint khusus bila pembayaran dibatalkan → minta bayar lagi (sesuai permintaan)
                if ($state === 'pembayaran_dibatalkan') {
                  $hint = 'Lakukan pembayaran lagi.';
                }
              ?>
              <div class="col-12 col-md-6 col-xl-4">
                <div class="event-card h-100 shadow-sm">
                  <div class="d-flex align-items-start justify-content-between mb-2">
                    <h5 class="mb-0"><?= esc($e['title']) ?></h5>
                    <span class="badge bg-success">Tersedia</span>
                  </div>

                  <div class="small text-muted mb-2">
                    Mulai: <strong><?= date('d M Y', strtotime($e['event_date'])) ?> <?= esc($e['event_time']) ?></strong><br>
                    Tutup Daftar: <strong><?= $e['registration_deadline'] ? date('d M Y H:i', strtotime($e['registration_deadline'])) : '-' ?></strong><br>
                    Batas Abstrak: <strong><?= $e['abstract_deadline'] ? date('d M Y H:i', strtotime($e['abstract_deadline'])) : '-' ?></strong>
                  </div>

                  <!-- Status chip + hint: Sembunyikan kalau masih belum daftar -->
                  <?php if ($state !== 'belum_daftar'): ?>
                    <div class="mb-2">
                      <span class="badge rounded-pill bg-<?= $stateBadge($state) ?>-subtle text-<?= $stateBadge($state) ?> fw-normal">
                        <?= esc($label) ?>
                      </span>
                      <?php if ($hint): ?>
                        <div class="text-muted small mt-1"><?= esc($hint) ?></div>
                      <?php endif; ?>
                    </div>
                  <?php endif; ?>

                  <div class="d-flex gap-2">
                    <a class="btn btn-outline-primary flex-fill" href="/presenter/events/detail/<?= (int)$e['id'] ?>">
                      <i class="bi bi-eye"></i> Detail
                    </a>

                    <?php
                      // CTA utama per state
                      switch ($state):
                        case 'belum_daftar': ?>
                          <button class="btn btn-primary flex-fill" onclick="confirmRegister(<?= (int)$e['id'] ?>)">
                            <i class="bi bi-box-arrow-in-right"></i> Daftar
                          </button>
                          <?php break;

                        case 'upload_abstrak': ?>
                          <a class="btn btn-primary flex-fill" href="/presenter/abstrak/create/<?= (int)$e['id'] ?>">
                            <i class="bi bi-upload"></i> Upload Abstrak
                          </a>
                          <?php break;

                        case 'revisi_abstrak': ?>
                          <a class="btn btn-warning flex-fill" href="/presenter/abstrak/create/<?= (int)$e['id'] ?>">
                            <i class="bi bi-arrow-repeat"></i> Upload Ulang
                          </a>
                          <?php break;

                        case 'abstrak_ditolak': ?>
                          <a class="btn btn-outline-danger flex-fill" href="/presenter/abstrak/create/<?= (int)$e['id'] ?>">
                            <i class="bi bi-file-earmark-plus"></i> Kirim Baru
                          </a>
                          <?php break;

                        case 'menunggu_abstrak': ?>
                          <a class="btn btn-outline-secondary flex-fill" href="/presenter/abstrak">
                            <i class="bi bi-hourglass-split"></i> Lihat Abstrak
                          </a>
                          <?php break;

                        case 'bayar': ?>
                          <a class="btn btn-warning flex-fill" href="/presenter/pembayaran/instruction/<?= (int)$e['id'] ?>">
                            <i class="bi bi-credit-card"></i> Bayar Sekarang
                          </a>
                          <?php break;

                        case 'pembayaran_pending': ?>
                          <a class="btn btn-outline-secondary flex-fill" href="/presenter/pembayaran">
                            <i class="bi bi-clock-history"></i> Cek Status
                          </a>
                          <?php break;

                        case 'pembayaran_dibatalkan': ?>
                          <a class="btn btn-danger flex-fill" href="/presenter/pembayaran/instruction/<?= (int)$e['id'] ?>">
                            <i class="bi bi-arrow-repeat"></i> Bayar Ulang
                          </a>
                          <?php break;

                        case 'siap_absen':
                        case 'sudah_absen': ?>
                          <a class="btn btn-success flex-fill" href="/presenter/events/detail/<?= (int)$e['id'] ?>">
                            <i class="bi bi-calendar-check"></i> Lihat Event
                          </a>
                          <?php break;

                        default: ?>
                          <a class="btn btn-outline-secondary flex-fill" href="/presenter/events/detail/<?= (int)$e['id'] ?>">
                            <i class="bi bi-info-circle"></i> Detail
                          </a>
                          <?php break;
                      endswitch;
                    ?>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Event Ditutup -->
      <div class="card shadow-sm mb-4 border-0 overflow-hidden">
        <div class="card-header bg-gradient-primary text-white">
          <h5 class="mb-0"><i class="bi bi-lock me-2"></i>Event Ditutup</h5>
        </div>
        <div class="card-body">
          <?php if (empty($closed)): ?>
            <div class="text-muted">Belum ada event yang ditutup.</div>
          <?php else: ?>
            <div class="row g-3">
              <?php foreach ($closed as $e):
                $st    = $statusIndex[(int)$e['id']] ?? [];
                $state = $st['state'] ?? 'belum_daftar';
                $label = $st['label'] ?? '';
                $hint  = $st['hint']  ?? '';

                if ($state === 'pembayaran_dibatalkan') {
                  $hint = 'Lakukan pembayaran lagi.';
                }
              ?>
              <div class="col-12 col-md-6 col-xl-4">
                <div class="event-card h-100 shadow-sm opacity-90">
                  <div class="d-flex align-items-start justify-content-between mb-2">
                    <h5 class="mb-0"><?= esc($e['title']) ?></h5>
                    <span class="badge bg-secondary">Ditutup</span>
                  </div>
                  <div class="small text-muted mb-2">
                    Mulai: <strong><?= date('d M Y', strtotime($e['event_date'])) ?> <?= esc($e['event_time']) ?></strong><br>
                    Tutup Daftar: <strong><?= $e['registration_deadline'] ? date('d M Y H:i', strtotime($e['registration_deadline'])) : '-' ?></strong><br>
                    Batas Abstrak: <strong><?= $e['abstract_deadline'] ? date('d M Y H:i', strtotime($e['abstract_deadline'])) : '-' ?></strong>
                  </div>

                  <?php if ($state !== 'belum_daftar'): ?>
                    <div class="mb-2">
                      <span class="badge rounded-pill bg-<?= $stateBadge($state) ?>-subtle text-<?= $stateBadge($state) ?> fw-normal">
                        <?= esc($label) ?>
                      </span>
                      <?php if ($hint): ?>
                        <div class="text-muted small mt-1"><?= esc($hint) ?></div>
                      <?php endif; ?>
                    </div>
                  <?php endif; ?>

                  <div class="d-flex gap-2">
                    <a class="btn btn-outline-secondary flex-fill" href="/presenter/events/detail/<?= (int)$e['id'] ?>">
                      <i class="bi bi-eye"></i> Detail
                    </a>

                    <?php
                      // Pendaftaran ditutup → tidak ada tombol daftar / bayar baru dari sini
                      // Tampilkan CTA sesuai state yang masih relevan
                      switch ($state):
                        case 'upload_abstrak':
                        case 'revisi_abstrak':
                        case 'abstrak_ditolak': ?>
                          <a class="btn btn-outline-secondary flex-fill" href="/presenter/abstrak">
                            <i class="bi bi-file-earmark-text"></i> Lihat Abstrak
                          </a>
                          <?php break;

                        case 'menunggu_abstrak': ?>
                          <a class="btn btn-outline-secondary flex-fill" href="/presenter/abstrak">
                            <i class="bi bi-hourglass-split"></i> Lihat Abstrak
                          </a>
                          <?php break;

                        case 'pembayaran_pending': ?>
                          <a class="btn btn-outline-secondary flex-fill" href="/presenter/pembayaran">
                            <i class="bi bi-clock-history"></i> Cek Status
                          </a>
                          <?php break;

                        case 'siap_absen':
                        case 'sudah_absen': ?>
                          <a class="btn btn-outline-success flex-fill" href="/presenter/events/detail/<?= (int)$e['id'] ?>">
                            <i class="bi bi-calendar-check"></i> Lihat Event
                          </a>
                          <?php break;

                        case 'pembayaran_dibatalkan': ?>
                          <!-- Pendaftaran tutup: bayar ulang biasanya tidak bisa, jadi cukup arahkan ke detail -->
                          <a class="btn btn-outline-danger flex-fill" href="/presenter/events/detail/<?= (int)$e['id'] ?>">
                            <i class="bi bi-info-circle"></i> Lihat Detail
                          </a>
                          <?php break;

                        default: ?>
                          <!-- tidak ada CTA tambahan -->
                          <?php break;
                      endswitch;
                    ?>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

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
    background:#fff; border-radius:14px; padding:16px; border:1px solid #eef2f7;
  }
  .opacity-90{ opacity:.92; }

  /* Compact search */
  .search-compact .form-control,
  .search-compact .btn{
    height: 42px;
    border-radius: 10px;
    font-size: .95rem;
  }
  .search-compact .form-control{ padding: .45rem .75rem; }
  @media (max-width: 767.98px){
    .header-section.header-blue{ padding:18px; }
    .search-compact .form-control,
    .search-compact .btn{ height: 40px; font-size: .92rem; }
  }

  /* bootstrap utility -subtle fallback (kalau tema belum punya) */
  .bg-primary-subtle{ background:#e7f0ff !important; color:#2563eb !important;}
  .bg-info-subtle{ background:#e6f9fc !important; color:#06b6d4 !important;}
  .bg-warning-subtle{ background:#fff7e6 !important; color:#d97706 !important;}
  .bg-danger-subtle{ background:#ffe9e9 !important; color:#ef4444 !important;}
  .bg-secondary-subtle{ background:#f1f5f9 !important; color:#475569 !important;}
  .bg-success-subtle{ background:#eafaf3 !important; color:#0f766e !important;}
  .text-primary{ color:#2563eb !important;}
  .text-info{ color:#06b6d4 !important;}
  .text-warning{ color:#d97706 !important;}
  .text-danger{ color:#ef4444 !important;}
  .text-secondary{ color:#475569 !important;}
  .text-success{ color:#0f766e !important;}
</style>

<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
function confirmRegister(id){
  Swal.fire({
    title:'Daftar event?',
    text:'Anda akan tercatat sebagai presenter event ini.',
    icon:'question',
    showCancelButton:true,
    confirmButtonText:'Ya, daftar',
    cancelButtonText:'Batal'
  }).then(r=>{
    if(r.isConfirmed){
      window.location.href = '/presenter/events/register/'+id;
    }
  });
}
</script>