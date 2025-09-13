<?php
$title = $title ?? 'Detail Pembayaran';
$pay   = $pay   ?? [];
$event = $event ?? [];
$badge = $badge ?? 'secondary';

$hasFile   = !empty($pay['bukti_bayar']);
$fileName  = $hasFile ? (string)$pay['bukti_bayar'] : '';
$ext       = $hasFile ? strtolower(pathinfo($fileName, PATHINFO_EXTENSION)) : '';
$isImg     = in_array($ext, ['jpg','jpeg','png']);
$isPdf     = ($ext === 'pdf');
$previewUrl= $hasFile ? '/presenter/pembayaran/download-bukti/'.(int)$pay['id_pembayaran'].'?inline=1' : '#';
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <div class="header-section header-blue d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 class="welcome-text mb-1"><i class="bi bi-receipt"></i> Detail Pembayaran</h2>
          <div class="text-white-50"><?= esc($event['title'] ?? '-') ?></div>
        </div>
        <div>
          <span class="badge bg-<?= esc($badge) ?>"><?= strtoupper($pay['status'] ?? 'PENDING') ?></span>
        </div>
      </div>

      <div class="card shadow-sm mb-3">
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6"><div><strong>Metode</strong><br><?= esc($pay['metode'] ?? '-') ?></div></div>
            <div class="col-md-6"><div><strong>Jumlah</strong><br>Rp <?= number_format((int)($pay['jumlah'] ?? 0),0,',','.') ?></div></div>
            <div class="col-md-6"><div><strong>Tanggal Bayar</strong><br><?= !empty($pay['tanggal_bayar']) ? date('d M Y H:i', strtotime($pay['tanggal_bayar'])) : '-' ?></div></div>
            <div class="col-md-6"><div><strong>Status</strong><br><span class="badge bg-<?= esc($badge) ?>"><?= strtoupper($pay['status'] ?? 'PENDING') ?></span></div></div>
            <?php if (!empty($pay['keterangan'])): ?>
              <div class="col-12"><div><strong>Catatan</strong><br><?= esc($pay['keterangan']) ?></div></div>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="card shadow-sm">
        <div class="card-header bg-light d-flex justify-content-between align-items-center flex-wrap gap-2">
          <strong>Bukti Pembayaran</strong>
          <div class="d-flex gap-2">
            <?php if ($hasFile): ?>
              <a class="btn btn-outline-secondary btn-sm" href="/presenter/pembayaran/download-bukti/<?= (int)$pay['id_pembayaran'] ?>">
                <i class="bi bi-download"></i> Download
              </a>
              <button class="btn btn-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#buktiCollapse" aria-expanded="false" aria-controls="buktiCollapse" id="togglePreviewBtn">
                <i class="bi bi-eye"></i> Lihat Bukti
              </button>
            <?php endif; ?>
          </div>
        </div>
        <div class="card-body">
          <?php if ($hasFile): ?>
            <div class="collapse" id="buktiCollapse">
              <?php if ($isImg): ?>
                <img src="<?= esc($previewUrl) ?>" alt="Bukti Pembayaran" class="img-fluid rounded border" loading="lazy">
              <?php elseif ($isPdf): ?>
                <div class="ratio ratio-4x3 rounded border">
                  <iframe src="<?= esc($previewUrl) ?>" title="Bukti Pembayaran (PDF)" style="border:0;"></iframe>
                </div>
                <div class="form-text mt-2">Jika PDF tidak tampil, gunakan tombol <strong>Download</strong>.</div>
              <?php else: ?>
                <div class="text-muted">Format file tidak didukung untuk pratinjau. Silakan gunakan tombol Download.</div>
              <?php endif; ?>
            </div>
          <?php else: ?>
            <div class="text-muted">Belum ada bukti yang diunggah.</div>
          <?php endif; ?>
        </div>
      </div>

      <?php if (in_array(strtolower($pay['status'] ?? ''), ['pending','rejected'])): ?>
      <div class="card shadow-sm mt-3">
        <div class="card-header bg-light"><strong>Upload Ulang Bukti</strong></div>
        <div class="card-body">
          <form method="post" action="/presenter/pembayaran/reupload/<?= (int)$pay['id_pembayaran'] ?>" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <div class="mb-3">
              <input type="file" name="bukti_bayar" class="form-control" accept=".jpg,.jpeg,.png,.pdf" required>
            </div>
            <button class="btn btn-warning"><i class="bi bi-upload"></i> Upload Ulang</button>
          </form>
        </div>
      </div>
      <?php endif; ?>

      <div class="mt-3">
        <a href="/presenter/pembayaran" class="btn btn-outline-secondary">Kembali</a>
      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<style>
  :root{ --primary:#2563eb; --primary-deep:#1e40af; }
  .header-section.header-blue{
    background:linear-gradient(135deg,var(--primary),var(--primary-deep));
    color:#fff; padding:24px; border-radius:16px; box-shadow:0 8px 28px rgba(0,0,0,.12);
  }
  .welcome-text{ font-weight:700; }
</style>

<script>
  (function(){
    const btn = document.getElementById('togglePreviewBtn');
    const collapseEl = document.getElementById('buktiCollapse');
    if(btn && collapseEl){
      collapseEl.addEventListener('shown.bs.collapse', ()=>{ btn.innerHTML = '<i class="bi bi-eye-slash"></i> Sembunyikan Bukti'; });
      collapseEl.addEventListener('hidden.bs.collapse', ()=>{ btn.innerHTML = '<i class="bi bi-eye"></i> Lihat Bukti'; });
    }
  })();
</script>