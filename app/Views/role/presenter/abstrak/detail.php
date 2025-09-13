<?php
$title = $title ?? 'Detail Abstrak';
$d     = $data  ?? [];
$badge = $badge ?? 'secondary';
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <div class="header-section header-blue d-flex justify-content-between align-items-center mb-3">
        <div>
          <h2 class="welcome-text mb-1"><i class="bi bi-file-text"></i> Detail Abstrak</h2>
          <div class="text-white-50"><?= esc($d['event_title'] ?? '-') ?></div>
        </div>
        <div>
          <span class="badge bg-<?= $badge ?>"><?= strtoupper($d['status'] ?? 'MENUNGGU') ?></span>
        </div>
      </div>

      <div class="card shadow-sm mb-3">
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6">
              <div class="mb-2"><strong>Judul</strong><br><span class="text-break"><?= esc($d['judul'] ?? '-') ?></span></div>
              <div class="mb-2"><strong>Kategori</strong><br><?= esc($d['nama_kategori'] ?? '-') ?></div>
              <div class="mb-2"><strong>Status</strong><br><span class="badge bg-<?= $badge ?>"><?= strtoupper($d['status'] ?? '-') ?></span></div>
            </div>
            <div class="col-md-6">
              <div class="mb-2"><strong>Tanggal Upload</strong><br><?= !empty($d['tanggal_upload']) ? date('d M Y H:i', strtotime($d['tanggal_upload'])) : '-' ?></div>
              <div class="mb-2"><strong>Revisi Ke</strong><br><?= (int)($d['revisi_ke'] ?? 0) ?></div>
              <div class="mb-2">
                <strong>File</strong><br>
                <?php if (!empty($d['file_abstrak'])): ?>
                  <a class="btn btn-outline-secondary btn-sm" href="/presenter/abstrak/download/<?= esc($d['file_abstrak'],'attr') ?>">
                    <i class="bi bi-download"></i> Unduh
                  </a>
                <?php else: ?>
                  <span class="text-muted">-</span>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <?php
            $st = strtolower($d['status'] ?? 'menunggu');
            if ($st === 'menunggu') {
              $info = ['secondary','Abstrak Anda sudah diterima sistem dan menunggu untuk direview.'];
            } elseif ($st === 'sedang_direview') {
              $info = ['warning','Abstrak sedang direview oleh reviewer. Mohon tunggu.'];
            } elseif ($st === 'revisi') {
              $info = ['info','Mohon upload file revisi Anda pada form di bawah ini.'];
            } elseif ($st === 'diterima') {
              $info = ['success','Selamat! Abstrak diterima. Silakan lanjutkan ke pembayaran.'];
            } else { // ditolak
              $info = ['danger','Maaf, abstrak ditolak. Anda bisa mengirim abstrak baru pada event lain.'];
            }
          ?>
          <div class="alert alert-<?= $info[0] ?> mt-2">
            <i class="bi bi-info-circle"></i> <?= $info[1] ?>
          </div>
        </div>
      </div>

      <?php if (strtolower($d['status'] ?? '') === 'revisi'): ?>
      <div class="card shadow-sm">
        <div class="card-header bg-light"><strong>Upload Revisi</strong></div>
        <div class="card-body">
          <form method="post" action="/presenter/abstrak/uploadRevisi/<?= (int)($d['id_abstrak'] ?? 0) ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="mb-3">
              <label class="form-label">File Abstrak (PDF/DOC/DOCX)</label>
              <input type="file" name="file_abstrak" class="form-control" required>
              <div class="form-text">Unggah file revisi Anda.</div>
            </div>
            <button class="btn btn-warning"><i class="bi bi-upload"></i> Kirim Revisi</button>
          </form>
        </div>
      </div>
      <?php endif; ?>

      <div class="mt-3">
        <a href="/presenter/abstrak" class="btn btn-outline-secondary">Kembali</a>
      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<style>
  :root{ --primary:#2563eb; --primary-deep:#1e40af; }
  .header-section.header-blue{
    background:linear-gradient(135deg,var(--primary),var(--primary-deep));
    color:#fff; padding:20px; border-radius:16px; box-shadow:0 8px 28px rgba(0,0,0,.12);
  }
  .welcome-text{ font-weight:500; font-size:1.25rem; }
  .card{ border-radius:14px; }
  .btn{ border-radius:10px; }
</style>