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

      <!-- Search Box (kartu biru) -->
      <div class="card shadow-sm mb-3 border-0 overflow-hidden">
        <div class="card-header bg-gradient-primary text-white">
          <div class="d-flex align-items-center justify-content-between">
            <h5 class="mb-0"><i class="bi bi-search me-2"></i>Cari Event</h5>
          </div>
        </div>
        <div class="card-body">
          <form class="row g-2" method="get" action="/presenter/events">
            <div class="col-12 col-md-9">
              <input class="form-control form-control-lg" name="q" value="<?= esc($q) ?>" placeholder="Ketik judul/lokasi/kata kunci...">
            </div>
            <div class="col-12 col-md-3 d-grid">
              <button class="btn btn-primary btn-lg"><i class="bi bi-search me-1"></i>Cari</button>
            </div>
          </form>
        </div>
      </div>

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
                $st = $statusIndex[(int)$e['id']] ?? null;
                $label = $st['label'] ?? 'Belum terdaftar';
                $hint  = $st['hint']  ?? '';
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
                  <div class="mb-2">
                    <span class="badge rounded-pill bg-primary-subtle text-primary fw-normal"><?= esc($label) ?></span>
                    <?php if ($hint): ?><div class="text-muted small mt-1"><?= esc($hint) ?></div><?php endif; ?>
                  </div>
                  <div class="d-flex gap-2">
                    <a class="btn btn-outline-primary flex-fill" href="/presenter/events/detail/<?= $e['id'] ?>">
                      <i class="bi bi-eye"></i> Detail
                    </a>
                    <?php if (($st['state'] ?? '') === 'belum_daftar'): ?>
                      <button class="btn btn-primary flex-fill" onclick="confirmRegister(<?= (int)$e['id'] ?>)">
                        <i class="bi bi-box-arrow-in-right"></i> Daftar
                      </button>
                    <?php else: ?>
                      <a class="btn btn-success flex-fill" href="/presenter/events/detail/<?= $e['id'] ?>">
                        <i class="bi bi-check2-circle"></i> Sudah Terdaftar
                      </a>
                    <?php endif; ?>
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
                $st = $statusIndex[(int)$e['id']] ?? null;
                $label = $st['label'] ?? '—';
                $hint  = $st['hint']  ?? '';
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
                  <div class="mb-2">
                    <span class="badge rounded-pill bg-secondary-subtle text-secondary fw-normal"><?= esc($label) ?></span>
                    <?php if ($hint): ?><div class="text-muted small mt-1"><?= esc($hint) ?></div><?php endif; ?>
                  </div>
                  <a class="btn btn-outline-secondary w-100" href="/presenter/events/detail/<?= $e['id'] ?>">
                    <i class="bi bi-eye"></i> Detail
                  </a>
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