<?php
$title  = $title ?? 'Detail Abstrak';
$abs    = $abs ?? [];
$event  = $event ?? [];
$status = strtolower($abs['status'] ?? 'menunggu');

$badge = [
  'menunggu'        => 'warning',
  'sedang_direview' => 'info',
  'diterima'        => 'success',
  'ditolak'         => 'danger',
][$status] ?? 'secondary';
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <div class="header-section header-blue d-flex justify-content-between align-items-center mb-3">
        <div>
          <h3 class="welcome-text mb-1">
            <i class="bi bi-journal-text me-2"></i>Detail Abstrak
          </h3>
          <div class="text-white-50"><?= esc($event['title'] ?? '-') ?></div>
        </div>
        <div>
          <span class="badge bg-<?= $badge ?> fs-6"><?= ucfirst($status) ?></span>
        </div>
      </div>

      <?= $this->include('partials/alerts') ?>

      <div class="row g-3">
        <div class="col-12 col-lg-8">
          <div class="card shadow-sm mb-3">
            <div class="card-header bg-light">
              <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Informasi Abstrak</h6>
            </div>
            <div class="card-body">
              <div class="mb-2">
                <div class="text-muted small">Judul</div>
                <div class="fw-semibold"><?= esc($abs['judul'] ?? '-') ?></div>
              </div>
              <div class="mb-2">
                <div class="text-muted small">Kategori</div>
                <div class="fw-semibold"><?= esc($abs['nama_kategori'] ?? '-') ?></div>
              </div>
              <div class="mb-2">
                <div class="text-muted small">Tanggal Unggah</div>
                <div class="fw-semibold"><?= !empty($abs['tanggal_upload']) ? date('d M Y H:i', strtotime($abs['tanggal_upload'])) : '-' ?></div>
              </div>

              <div class="d-flex gap-2 mt-3">
                <a class="btn btn-outline-secondary" href="/presenter/abstrak/download/<?= esc($abs['file_abstrak']) ?>">
                  <i class="bi bi-download"></i> Unduh File
                </a>

                <?php if ($status === 'ditolak'): ?>
                  <a class="btn btn-danger" href="/presenter/abstrak/create/<?= (int)$abs['event_id'] ?>">
                    <i class="bi bi-upload"></i> Upload Ulang Abstrak
                  </a>
                <?php endif; ?>

                <!-- NEW: selalu ada -->
                <a class="btn btn-success" href="/presenter/fullpaper/create/<?= (int)$abs['event_id'] ?>">
                  <i class="bi bi-file-earmark-text"></i> Lanjut Full Paper
                </a>
              </div>
            </div>
          </div>

          <div class="card shadow-sm">
            <div class="card-header bg-light">
              <h6 class="mb-0"><i class="bi bi-calendar-event me-2"></i>Info Event</h6>
            </div>
            <div class="card-body">
              <div class="row g-2">
                <div class="col-6">
                  <div class="text-muted small">Tanggal Event</div>
                  <div class="fw-semibold"><?= isset($event['event_date']) ? date('d M Y', strtotime($event['event_date'])) : '-' ?></div>
                </div>
                <div class="col-6">
                  <div class="text-muted small">Deadline Abstrak</div>
                  <div class="fw-semibold"><?= !empty($event['abstract_deadline']) ? date('d M Y H:i', strtotime($event['abstract_deadline'])) : '-' ?></div>
                </div>
              </div>
            </div>
          </div>

        </div>

        <div class="col-12 col-lg-4">
          <div class="card shadow-sm">
            <div class="card-header bg-light">
              <h6 class="mb-0"><i class="bi bi-flag me-2"></i>Status & Arah Lanjut</h6>
            </div>
            <div class="card-body">
              <?php if ($status === 'menunggu' || $status === 'sedang_direview'): ?>
                <div class="alert alert-info">Abstrak Anda sedang diproses. Anda boleh lanjut unggah Full Paper sekarang.</div>
              <?php elseif ($status === 'ditolak'): ?>
                <div class="alert alert-danger">Abstrak ditolak. Silakan upload ulang abstrak. Full Paper (jika sudah diunggah) dapat ditahan sampai abstrak diterima.</div>
              <?php elseif ($status === 'diterima'): ?>
                <div class="alert alert-success">Abstrak diterima. Langkah selanjutnya: <strong>Upload Full Paper</strong>. Pembayaran dilakukan setelah Full Paper di-ACC.</div>
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
  .header-section.header-blue{
    background: linear-gradient(135deg,#2563eb,#1e40af);
    color:#fff; padding:24px; border-radius:16px; box-shadow:0 8px 28px rgba(0,0,0,.12);
  }
  .welcome-text{ font-weight:700; font-size:1.4rem; }
</style>