<?php
$title       = $title       ?? 'Detail Full Paper';
$event       = $event       ?? [];
$abs         = $abs         ?? [];
$status      = strtoupper($status ?? 'NONE');
$meta        = $status_meta ?? ['badge'=>'secondary','label'=>'-','hint'=>'-'];
$path        = $path        ?? '';
$createdAt   = $created_at  ?? null;
$reviewedAt  = $reviewed_at ?? null;
$decisionAt  = $decision_at ?? null;
$notes       = trim((string)($notes ?? ''));
$absMeta     = $abs_meta    ?? ['badge'=>'secondary','label'=>'-'];
$isOpen      = (bool)($is_open ?? false);
$canReupload = (bool)($can_reupload ?? false);
$canUpload   = (bool)($can_upload ?? false);
$eventId     = (int)($event_id ?? 0);

$fmt = fn($s)=> $s ? date('d M Y H:i', strtotime($s)) : '-';
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <div class="header-section header-blue d-flex justify-content-between align-items-center mb-3">
        <div>
          <h3 class="welcome-text mb-1">
            <i class="bi bi-file-earmark-text me-2"></i>Detail Full Paper
          </h3>
          <div class="text-white-50"><?= esc($event['title'] ?? '-') ?></div>
        </div>
        <div class="d-flex align-items-center gap-2">
          <span class="badge bg-<?= esc($meta['badge']) ?> fs-6"><?= esc($meta['label']) ?></span>
          <span class="badge bg-<?= esc($absMeta['badge']) ?>"><?= esc($absMeta['label']) ?></span>
        </div>
      </div>

      <div class="d-flex gap-2 mb-3">
        <a href="javascript:history.back()" class="btn btn-outline-secondary">
          <i class="bi bi-arrow-left"></i> Kembali
        </a>
        <a href="<?= site_url('presenter/fullpaper') ?>" class="btn btn-outline-primary">
          <i class="bi bi-list-ul"></i> Daftar Full Paper
        </a>
      </div>

      <div class="row g-3">
        <!-- Kiri: Info & Aksi -->
        <div class="col-12 col-lg-8">
          <div class="card shadow-sm mb-3">
            <div class="card-header bg-light">
              <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Informasi Full Paper</h6>
            </div>
            <div class="card-body">
              <div class="mb-2"><span class="text-muted small">Status</span><br>
                <span class="badge bg-<?= esc($meta['badge']) ?>"><?= esc($meta['label']) ?></span>
                <span class="text-muted small ms-2"><?= esc($meta['hint'] ?? '') ?></span>
              </div>

              <div class="row g-2 mt-1">
                <div class="col-md-4">
                  <div class="text-muted small">Diunggah</div>
                  <div class="fw-semibold"><?= esc($fmt($createdAt)) ?></div>
                </div>
                <div class="col-md-4">
                  <div class="text-muted small">Direview</div>
                  <div class="fw-semibold"><?= esc($fmt($reviewedAt)) ?></div>
                </div>
                <div class="col-md-4">
                  <div class="text-muted small">Keputusan</div>
                  <div class="fw-semibold"><?= esc($fmt($decisionAt)) ?></div>
                </div>
              </div>

              <div class="mt-3 d-flex gap-2">
                <?php if (!empty($path)): ?>
                  <a class="btn btn-outline-secondary" href="<?= site_url('presenter/fullpaper/download/'.rawurlencode($path)) ?>">
                    <i class="bi bi-download"></i> Unduh File
                  </a>
                <?php endif; ?>

                <?php if ($canUpload): ?>
                  <a class="btn btn-primary" href="<?= site_url('presenter/fullpaper/create/'.$eventId) ?>">
                    <i class="bi bi-upload"></i> Upload Full Paper
                  </a>
                <?php elseif ($canReupload): ?>
                  <a class="btn btn-warning" href="<?= site_url('presenter/fullpaper/create/'.$eventId) ?>">
                    <i class="bi bi-upload"></i> Upload Ulang (Revisi)
                  </a>
                <?php else: ?>
                  <button class="btn btn-outline-secondary" disabled>
                    <i class="bi bi-hourglass-split"></i> Tidak ada aksi
                  </button>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <?php if ($notes !== ''): ?>
          <div class="card shadow-sm mb-3">
            <div class="card-header bg-light">
              <h6 class="mb-0"><i class="bi bi-chat-dots me-2"></i>Catatan Reviewer</h6>
            </div>
            <div class="card-body">
              <div class="alert <?= $status==='REVISION' ? 'alert-warning' : ($status==='REJECTED' ? 'alert-danger' : 'alert-info') ?> mb-0">
                <div style="white-space:pre-wrap"><?= esc($notes) ?></div>
              </div>
            </div>
          </div>
          <?php endif; ?>
        </div>

        <!-- Kanan: Info Event & Riwayat -->
        <div class="col-12 col-lg-4">
          <div class="card shadow-sm mb-3">
            <div class="card-header bg-light">
              <h6 class="mb-0"><i class="bi bi-calendar-event me-2"></i>Info Event</h6>
            </div>
            <div class="card-body">
              <div class="mb-2">
                <div class="text-muted small">Tanggal Event</div>
                <div class="fw-semibold">
                  <?= !empty($event['event_date']) ? date('d M Y', strtotime($event['event_date'])) : '-' ?>
                </div>
              </div>
              <div class="mb-2">
                <div class="text-muted small">Deadline Full Paper</div>
                <div class="fw-semibold">
                  <?= !empty($event['full_paper_deadline']) ? date('d M Y H:i', strtotime($event['full_paper_deadline'])) : '-' ?>
                </div>
              </div>
              <div class="mb-2">
                <div class="text-muted small">Status Abstrak</div>
                <span class="badge bg-<?= esc($absMeta['badge']) ?>"><?= esc($absMeta['label']) ?></span>
              </div>
              <div class="mt-2">
                <?php if ($isOpen): ?>
                  <span class="badge bg-success">Pengumpulan FP: OPEN</span>
                <?php else: ?>
                  <span class="badge bg-secondary">Pengumpulan FP: Closed</span>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <?php if (!empty($history)): ?>
          <div class="card shadow-sm">
            <div class="card-header bg-light">
              <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Riwayat Unggahan</h6>
            </div>
            <div class="card-body">
              <div class="small text-muted mb-2">Unggahan terbaru di atas.</div>
              <div class="list-group">
                <?php foreach ($history as $h):
                  $s = strtoupper($h['norm_status'] ?? 'NONE');
                  $badge = match($s){
                    'UPLOADED' => 'info', 'REVISION'=>'warning', 'ACCEPTED'=>'success', 'REJECTED'=>'danger', default=>'secondary'
                  };
                ?>
                  <div class="list-group-item d-flex justify-content-between align-items-start">
                    <div class="me-2">
                      <div class="fw-semibold"><?= !empty($h['created_at']) ? date('d M Y H:i', strtotime($h['created_at'])) : '-' ?></div>
                      <div class="small text-muted"><?= esc($h['file_name'] ?? ($h['file_path'] ?? '')) ?></div>
                    </div>
                    <span class="badge bg-<?= $badge ?>"><?= $s ?></span>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          </div>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<style>
  .header-section.header-blue{
    background:linear-gradient(135deg,#2563eb,#1e40af);
    color:#fff; padding:24px; border-radius:16px; box-shadow:0 8px 28px rgba(0,0,0,.12);
  }
  .welcome-text{ font-weight:700; font-size:1.4rem; }
</style>
