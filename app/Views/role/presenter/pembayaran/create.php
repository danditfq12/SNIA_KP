<?php
$title = $title ?? 'Upload Bukti Pembayaran';
$event = $event ?? [];
$price = (int)($price ?? 0);
$latestPay = $latestPay ?? null;
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <div class="header-section header-blue d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 class="welcome-text mb-1"><i class="bi bi-cash-coin"></i> Upload Bukti Pembayaran</h2>
          <div class="text-white-50"><?= esc($event['title'] ?? '-') ?></div>
        </div>
      </div>

      <div class="card shadow-sm">
        <div class="card-header bg-gradient-primary text-white">
          <strong>Total yang harus dibayar: Rp <?= number_format($price,0,',','.') ?></strong>
        </div>
        <div class="card-body">
          <form method="post" action="/presenter/pembayaran/store" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <input type="hidden" name="event_id" value="<?= (int)($event['id'] ?? 0) ?>">

            <div class="mb-3">
              <label class="form-label">Metode Pembayaran</label>
              <select name="metode" class="form-select" required>
                <option value="">— Pilih —</option>
                <option value="transfer_bank">Transfer Bank</option>
                <option value="ewallet">E-Wallet</option>
                <option value="lainnya">Lainnya</option>
              </select>
            </div>

            <div class="mb-3">
              <label class="form-label">Jumlah (Rp)</label>
              <input type="number" name="jumlah" class="form-control" min="1" value="<?= $price ?>" required>
            </div>

            <div class="mb-3">
              <label class="form-label">Kode Voucher (opsional)</label>
              <input type="text" name="kode_voucher" class="form-control" placeholder="KODEPROMO">
            </div>

            <div class="mb-3">
              <label class="form-label">Bukti Pembayaran (JPG/PNG/PDF)</label>
              <input type="file" name="bukti_bayar" class="form-control" accept=".jpg,.jpeg,.png,.pdf" required>
              <div class="form-text">Pastikan informasi transfer terlihat jelas.</div>
            </div>

            <div class="mb-3">
              <label class="form-label">Catatan (opsional)</label>
              <textarea name="keterangan" class="form-control" rows="3" placeholder="Tambahkan catatan jika perlu."></textarea>
            </div>

            <div class="d-flex gap-2">
              <a href="/presenter/events/detail/<?= (int)($event['id'] ?? 0) ?>" class="btn btn-outline-secondary">Kembali</a>
              <button class="btn btn-success"><i class="bi bi-upload"></i> Kirim Pembayaran</button>
            </div>
          </form>
        </div>
      </div>

      <?php if ($latestPay): ?>
      <div class="alert alert-info mt-3">
        <i class="bi bi-info-circle"></i>
        Anda memiliki pembayaran terakhir dengan status <strong><?= strtoupper($latestPay['status']) ?></strong>.
        Lihat <a href="/presenter/pembayaran/detail/<?= (int)$latestPay['id_pembayaran'] ?>" class="alert-link">detail pembayaran</a>.
      </div>
      <?php endif; ?>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<style>
  :root{ --primary:#2563eb; --primary-deep:#1e40af; }
  .header-section.header-blue{ background:linear-gradient(135deg,var(--primary),var(--primary-deep)); color:#fff; padding:20px; border-radius:16px; box-shadow:0 8px 28px rgba(0,0,0,.12); }
  .welcome-text{ font-weight:500; font-size:1.25rem; }
  .card{ border-radius:14px; }
  .btn{ border-radius:10px; }
</style>