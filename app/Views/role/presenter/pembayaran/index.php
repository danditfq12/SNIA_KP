<?php
$title   = $title ?? 'Pembayaran Saya';
$history = $history ?? [];
$pending = array_filter($history, fn($p) => strtolower($p['status'] ?? '') === 'pending');
$nonPending = array_filter($history, fn($p) => strtolower($p['status'] ?? '') !== 'pending');
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <div class="header-section header-blue d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 class="welcome-text mb-1"><i class="bi bi-wallet2"></i> Pembayaran</h2>
          <div class="text-white-50">Status & riwayat pembayaran event Anda</div>
        </div>
      </div>

      <!-- PENDING -->
      <div class="card shadow-sm mb-4">
        <div class="card-header bg-warning-subtle">
          <strong><i class="bi bi-hourglass-split me-1"></i> Menunggu Verifikasi</strong>
        </div>
        <div class="card-body p-0">
          <?php if (empty($pending)): ?>
            <div class="p-3 text-muted">Tidak ada transaksi yang menunggu.</div>
          <?php else: ?>
            <ul class="list-group list-group-flush">
              <?php foreach ($pending as $p): 
                $id = (int)($p['id_pembayaran'] ?? 0);
              ?>
              <li class="list-group-item">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                  <div>
                    <div class="fw-semibold"><?= esc($p['event_title'] ?? '-') ?></div>
                    <small class="text-muted">
                      <?= !empty($p['tanggal_bayar']) ? date('d M Y H:i', strtotime($p['tanggal_bayar'])) : '-' ?>
                      • Metode: <?= esc($p['metode'] ?? '-') ?>
                    </small>
                  </div>
                  <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge bg-warning">PENDING</span>
                    <a href="/presenter/pembayaran/detail/<?= $id ?>" class="btn btn-outline-primary btn-sm">
                      <i class="bi bi-eye"></i> Detail
                    </a>
                    <button class="btn btn-warning btn-sm"
                            data-bs-toggle="modal"
                            data-bs-target="#reuploadModal"
                            data-id="<?= $id ?>"
                            data-event="<?= esc($p['event_title'] ?? '-', 'attr') ?>">
                      <i class="bi bi-upload"></i> Kirim Ulang
                    </button>
                  </div>
                </div>
              </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </div>

      <!-- RIWAYAT -->
      <div class="card shadow-sm">
        <div class="card-header bg-light">
          <strong><i class="bi bi-clock-history me-1"></i> Riwayat Pembayaran</strong>
        </div>
        <div class="card-body p-0">
          <?php if (empty($nonPending)): ?>
            <div class="p-3 text-muted">Belum ada riwayat pembayaran.</div>
          <?php else: ?>
            <ul class="list-group list-group-flush">
              <?php foreach ($nonPending as $p):
                $id    = (int)($p['id_pembayaran'] ?? 0);
                $badge = [
                  'verified' => 'success',
                  'rejected' => 'danger',
                ][strtolower($p['status'] ?? '')] ?? 'secondary';
                $canReupload = strtolower($p['status'] ?? '') === 'rejected';
              ?>
              <li class="list-group-item">
                <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
                  <div class="me-2">
                    <div class="fw-semibold"><?= esc($p['event_title'] ?? '-') ?></div>
                    <small class="text-muted">
                      Metode: <?= esc($p['metode'] ?? '-') ?> •
                      <?= !empty($p['tanggal_bayar']) ? date('d M Y H:i', strtotime($p['tanggal_bayar'])) : '-' ?>
                    </small>
                  </div>
                  <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge bg-<?= $badge ?>"><?= strtoupper($p['status'] ?? '-') ?></span>
                    <a href="/presenter/pembayaran/detail/<?= $id ?>" class="btn btn-outline-primary btn-sm">
                      <i class="bi bi-eye"></i> Detail
                    </a>
                    <?php if ($canReupload): ?>
                      <button class="btn btn-warning btn-sm"
                              data-bs-toggle="modal"
                              data-bs-target="#reuploadModal"
                              data-id="<?= $id ?>"
                              data-event="<?= esc($p['event_title'] ?? '-', 'attr') ?>">
                        <i class="bi bi-upload"></i> Kirim Ulang
                      </button>
                    <?php endif; ?>
                  </div>
                </div>
              </li>
              <?php endforeach; ?>
            </ul>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </main>
</div>

<!-- MODAL -->
<div class="modal fade" id="reuploadModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" method="post" action="#" id="reuploadForm" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-upload me-2"></i>Kirim Ulang Bukti</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2 text-muted"><small id="reuploadEventName"></small></div>
        <div class="mb-3">
          <label class="form-label">File bukti (JPG/PNG/PDF)</label>
          <input type="file" name="bukti_bayar" class="form-control" accept=".jpg,.jpeg,.png,.pdf" required>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-warning" type="submit"><i class="bi bi-upload"></i> Kirim Ulang</button>
      </div>
    </form>
  </div>
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

<script>
  const reuploadModal = document.getElementById('reuploadModal');
  reuploadModal?.addEventListener('show.bs.modal', function (ev) {
    const btn     = ev.relatedTarget;
    const id      = btn?.getAttribute('data-id');
    const evTitle = btn?.getAttribute('data-event') || '';
    document.getElementById('reuploadForm').setAttribute('action', `/presenter/pembayaran/reupload/${id}`);
    document.getElementById('reuploadEventName').innerText = evTitle ? `Event: ${evTitle}` : '';
  });
</script>