<?php
$title       = $title ?? 'Upload Bukti Pembayaran';
$event       = $event ?? [];
$basePrice   = (int)($basePrice ?? ($event['presenter_fee_offline'] ?? 0));
$price       = (int)($price ?? $basePrice);
$voucherInfo = $voucherInfo ?? null;
$discount    = max(0, $basePrice - $price);
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <!-- Header Biru -->
      <div class="header-section header-blue d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center mb-3">
        <div class="mb-2 mb-md-0">
          <h3 class="welcome-text mb-1"><i class="bi bi-cash-coin me-2"></i>Upload Bukti Pembayaran</h3>
          <div class="text-white-50 small"><?= esc($event['title'] ?? '-') ?></div>
        </div>
        <div class="text-start text-md-end">
          <small class="text-white-50 d-block">Total Dibayar</small>
          <div class="price-bubble" id="priceTop">Rp <?= number_format($price,0,',','.') ?></div>
        </div>
      </div>

      <div class="row g-3">
        <!-- Kiri: Form -->
        <div class="col-12 col-lg-7">
          <div class="card shadow-sm mb-3">
            <div class="card-header bg-gradient-primary text-white py-3">
              <h5 class="mb-0 d-flex align-items-center"><i class="bi bi-upload me-2"></i>Form Upload</h5>
            </div>
            <div class="card-body p-3 p-md-4">

              <?php if ($voucherInfo): ?>
                <div class="alert alert-success d-flex align-items-center" role="alert">
                  <i class="bi bi-ticket-perforated me-2"></i>
                  <div>Voucher <strong><?= esc($voucherInfo['code']) ?></strong> diterapkan. Total sudah disesuaikan.</div>
                </div>
              <?php endif; ?>

              <!-- Penting: berikan ID agar tombol sticky bisa submit POST ke route -->
              <form id="payForm" method="post" action="/presenter/pembayaran/store" enctype="multipart/form-data">
                <?= csrf_field() ?>
                <input type="hidden" name="event_id" value="<?= (int)($event['id'] ?? 0) ?>">
                <?php if ($voucherInfo): ?>
                  <input type="hidden" name="kode_voucher" value="<?= esc($voucherInfo['code']) ?>">
                <?php endif; ?>

                <div class="mb-3">
                  <label class="form-label">Metode Pembayaran</label>
                  <select name="metode" class="form-select form-select-lg" required>
                    <option value="">— Pilih —</option>
                    <option value="transfer_bank">Transfer Bank</option>
                    <option value="ewallet">E-Wallet</option>
                    <option value="lainnya">Lainnya</option>
                  </select>
                </div>

                <div class="mb-3">
                  <label class="form-label">Bukti Pembayaran (JPG/PNG/PDF)</label>
                  <input type="file" name="bukti_bayar" class="form-control" accept=".jpg,.jpeg,.png,.pdf" required>
                  <div class="form-text">Pastikan informasi transfer terlihat jelas.</div>
                </div>

                <!-- Tombol submit DESKTOP (di dalam form, langsung POST) -->
                <div class="d-none d-md-flex gap-2">
                  <a href="/presenter/pembayaran/instruction/<?= (int)($event['id'] ?? 0) ?>" class="btn btn-outline-secondary flex-fill">
                    Kembali ke Instruksi
                  </a>
                  <button type="submit" class="btn btn-success flex-fill">
                    <i class="bi bi-cloud-arrow-up me-1"></i>Kirim Bukti Pembayaran
                  </button>
                </div>

                <!-- Tombol submit TABLET (>=md) fallback single-column -->
                <div class="d-flex d-md-none gap-2 mt-2">
                  <a href="/presenter/pembayaran/instruction/<?= (int)($event['id'] ?? 0) ?>" class="btn btn-outline-secondary flex-fill">
                    Instruksi
                  </a>
                  <button type="submit" class="btn btn-success flex-fill">
                    Upload
                  </button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <!-- Kanan: Ringkasan -->
        <div class="col-12 col-lg-5">
          <div class="card shadow-sm sticky-lg-top" style="top:84px;">
            <div class="card-header bg-light">
              <strong>Ringkasan Pembayaran</strong>
            </div>
            <div class="card-body p-3 p-md-4">
              <div class="mb-2 d-flex justify-content-between">
                <span>Harga Dasar</span>
                <span class="fw-semibold">Rp <?= number_format($basePrice,0,',','.') ?></span>
              </div>
              <div class="mb-2 d-flex justify-content-between <?= $discount>0?'text-success':'' ?>">
                <span>Diskon Voucher <?= $voucherInfo ? '('.esc($voucherInfo['code']).')' : '' ?></span>
                <span class="fw-semibold">- Rp <?= number_format($discount,0,',','.') ?></span>
              </div>
              <hr class="my-2">
              <div class="d-flex justify-content-between align-items-center">
                <div class="mini-label text-muted">Total Dibayar</div>
                <div class="fs-4 fw-bold" id="boxTotal">Rp <?= number_format($price,0,',','.') ?></div>
              </div>
              <div class="mt-3 small text-muted">
                Transfer ke: <strong>Bank ABC — 123456789 a.n. Panitia Event</strong><br>
                Berita transfer: <em><?= 'EVT'.str_pad((string)($event['id'] ?? 0), 4, '0', STR_PAD_LEFT) ?> — <?= esc($event['title'] ?? 'Event') ?></em>
              </div>
            </div>
          </div>

          <!-- Mobile sticky: tombol di luar form -> submit ke #payForm -->
          <div class="mobile-sticky d-lg-none">
            <div class="mobile-sticky__inner">
              <div>
                <div class="mini-label text-muted mb-1">Total</div>
                <div class="mobile-total fw-bold" id="mobTotal">Rp <?= number_format($price,0,',','.') ?></div>
              </div>
              <button type="button" class="btn btn-success btn-lg flex-fill" onclick="document.getElementById('payForm').submit();">
                Upload Bukti
              </button>
            </div>
          </div>
        </div>
      </div>

      <div class="alert alert-info mt-3">
        Setelah diunggah, status pembayaran akan menjadi <strong>pending</strong> dan menunggu verifikasi panitia.
      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<style>
  :root{ --primary:#2563eb; --primary-deep:#1e40af; --info:#06b6d4; }
  .header-section.header-blue{
    background:linear-gradient(135deg,var(--primary),var(--primary-deep));
    color:#fff; padding:20px; border-radius:16px; box-shadow:0 8px 28px rgba(0,0,0,.12);
  }
  .welcome-text{ font-weight:700; font-size:1.25rem; }
  .price-bubble{
    display:inline-block; padding:.35rem .75rem; background:rgba(255,255,255,.15);
    border:1px solid rgba(255,255,255,.3); border-radius:999px; font-weight:700;
  }
  .card{ border-radius:14px; }
  .bg-gradient-primary{ background:linear-gradient(135deg,var(--primary),var(--info))!important; }
  .mini-label{ font-size:.85rem; }
  .btn{ border-radius:12px; }
  .btn-success{ box-shadow:0 6px 18px rgba(16,185,129,.18); }
  .btn-outline-secondary{ border-color:#cbd5e1; }

  /* Mobile sticky CTA */
  .mobile-sticky{ position:sticky; bottom:0; left:0; right:0; margin-top:12px; z-index:1030; }
  .mobile-sticky__inner{
    display:flex; gap:.75rem; align-items:center; justify-content:space-between;
    background:#ffffff; border-top:1px solid #e5e7eb; padding:.75rem .9rem;
    box-shadow:0 -6px 18px rgba(0,0,0,.06);
  }
  .mobile-total{ font-size:1.25rem; }
</style>