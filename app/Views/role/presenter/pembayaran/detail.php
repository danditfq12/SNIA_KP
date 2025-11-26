<?php
$title   = $title ?? 'Detail Pembayaran';
$pay     = $pay ?? [];
$event   = $event ?? [];
$voucher = $voucher ?? null;

$status = strtolower($pay['status'] ?? 'pending');
/* mapping badge → subtle tone (seragam patokan) */
$badgeToneMap = [
  'pending'  => 'warning',
  'verified' => 'success',
  'canceled' => 'danger',
  'expired'  => 'secondary',
];
$tone  = $badgeToneMap[$status] ?? 'secondary';
$badge = 'bg-'.$tone.'-subtle text-'.($tone === 'secondary' ? 'slate-600' : $tone);

$isMidtrans     = ($pay['metode'] ?? '') === 'midtrans';
$hasOrderId     = !empty($pay['midtrans_order_id']);
$originalAmount = (int)($pay['original_amount'] ?? $pay['jumlah'] ?? 0);
$finalAmount    = (int)($pay['jumlah'] ?? 0);
$discount       = max(0, $originalAmount - $finalAmount);

/* PAYMENT TYPE DISPLAY */
$paymentType = strtolower(trim((string)($pay['midtrans_payment_type'] ?? '')));
if (empty($paymentType)) {
    $paymentType = strtolower((string)($pay['metode'] ?? ''));
}

// Helper function untuk payment type
function getPaymentTypeDisplay(string $type): array {
    if (empty($type) || $type === 'midtrans') {
        return [
            'label' => 'MIDTRANS',
            'icon' => 'bi-credit-card-2-front',
            'color' => 'text-primary'
        ];
    }

    // Bank Transfer dengan bank specific
    if (str_starts_with($type, 'bank_transfer_')) {
        $bank = strtoupper(str_replace('bank_transfer_', '', $type));
        return [
            'label' => "Virtual Account {$bank}",
            'icon' => 'bi-bank',
            'color' => 'text-success'
        ];
    }

    // Payment types lainnya
    $displays = [
        'gopay' => ['label' => 'GoPay', 'icon' => 'bi-wallet2', 'color' => 'text-info'],
        'qris' => ['label' => 'QRIS', 'icon' => 'bi-qr-code', 'color' => 'text-primary'],
        'shopeepay' => ['label' => 'ShopeePay', 'icon' => 'bi-wallet2', 'color' => 'text-warning'],
        'credit_card' => ['label' => 'Credit Card', 'icon' => 'bi-credit-card-2-front', 'color' => 'text-warning'],
        'cstore' => ['label' => 'Alfamart/Indomaret', 'icon' => 'bi-shop', 'color' => 'text-secondary'],
        'alfamart' => ['label' => 'Alfamart', 'icon' => 'bi-shop', 'color' => 'text-secondary'],
        'indomaret' => ['label' => 'Indomaret', 'icon' => 'bi-shop', 'color' => 'text-secondary'],
        'akulaku' => ['label' => 'Akulaku', 'icon' => 'bi-wallet2', 'color' => 'text-primary'],
        'mandiri_bill' => ['label' => 'Mandiri Bill Payment', 'icon' => 'bi-bank', 'color' => 'text-success'],
        'echannel' => ['label' => 'Mandiri Bill Payment', 'icon' => 'bi-bank', 'color' => 'text-success'],
    ];

    return $displays[$type] ?? [
        'label' => ucwords(str_replace('_', ' ', $type)),
        'icon' => 'bi-credit-card-2-front',
        'color' => 'text-primary'
    ];
}

$paymentDisplay = getPaymentTypeDisplay($paymentType);

/* helper tanggal */
$fmt = fn($s,$t=false)=> $s ? date($t?'d M Y H:i':'d M Y', strtotime($s)) : '-';

/* Mode kehadiran */
$participationType = $pay['participation_type'] ?? 'offline';
$modeLabel = ucfirst($participationType);
$modeBadge = $participationType === 'online' ? 'bg-info-subtle text-info' : 'bg-primary-subtle text-primary';
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill page-wrap-blue">
    <div class="container-xxl px-3 px-md-4 py-4">

      <!-- HERO: seragam card-hero + hero-body -->
      <div class="card-hero mb-4">
        <div class="hero-body">
          <div class="d-flex flex-wrap align-items-start align-items-md-center justify-content-between gap-3">
            <div class="d-flex align-items-center gap-2">
              <a href="<?= site_url('presenter/pembayaran') ?>" class="btn btn-light btn-sm border me-1">
                <i class="bi bi-arrow-left"></i>
              </a>
              <div>
                <h3 class="hero-title mb-1"><i class="bi bi-receipt me-2"></i>Detail Pembayaran</h3>
                <div class="text-white-70 small"><?= esc($event['title'] ?? '-') ?></div>
              </div>
            </div>
            <div class="d-flex align-items-center gap-2">
              <?php if ($isMidtrans && $hasOrderId): ?>
                <span class="badge bg-primary-subtle text-primary px-3 py-2">
                  Order ID: <code class="ms-1"><?= esc($pay['midtrans_order_id']) ?></code>
                </span>
              <?php endif; ?>
              <span class="badge <?= esc($badge) ?> fs-6 px-3 py-2"><?= strtoupper($status) ?></span>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-3">
        <!-- Kiri -->
        <div class="col-12 col-lg-8">

          <!-- Ringkasan Pembayaran -->
          <div class="card shadow-soft card-glass-plain mb-3">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
              <span class="badge bg-blue-soft"><i class="bi bi-credit-card"></i></span>
              <h5 class="mb-0 fw-semibold text-blue-900">Ringkasan Pembayaran</h5>
            </div>
            <div class="card-body">
              <div class="row g-3">
                <!-- METODE PEMBAYARAN - UPDATED -->
                <div class="col-12 col-md-6">
                  <div class="info-group">
                    <div class="info-label">Metode Pembayaran</div>
                    <div class="info-value">
                      <i class="bi <?= esc($paymentDisplay['icon']) ?> <?= esc($paymentDisplay['color']) ?> me-2"></i>
                      <span class="<?= esc($paymentDisplay['color']) ?>"><?= esc($paymentDisplay['label']) ?></span>
                    </div>
                  </div>
                </div>

                <!-- MODE KEHADIRAN -->
                <div class="col-12 col-md-6">
                  <div class="info-group">
                    <div class="info-label">Mode Kehadiran</div>
                    <div class="info-value">
                      <span class="badge <?= esc($modeBadge) ?>">
                        <i class="bi <?= $participationType === 'online' ? 'bi-wifi' : 'bi-building' ?> me-1"></i>
                        <?= esc($modeLabel) ?>
                      </span>
                    </div>
                  </div>
                </div>

                <div class="col-12 col-md-6">
                  <div class="info-group">
                    <div class="info-label">Tanggal Pembayaran</div>
                    <div class="info-value"><?= esc($fmt($pay['tanggal_bayar'] ?? null, true)) ?></div>
                  </div>
                </div>

                <?php if (!empty($pay['verified_at'])): ?>
                <div class="col-12 col-md-6">
                  <div class="info-group">
                    <div class="info-label">Tanggal Verifikasi</div>
                    <div class="info-value">
                      <?= esc($fmt($pay['verified_at'], true)) ?>
                      <?php if (!empty($pay['auto_verified'])): ?>
                        <span class="badge bg-success-subtle text-success ms-2">Auto</span>
                      <?php endif; ?>
                    </div>
                  </div>
                </div>
                <?php endif; ?>

                <!-- Breakdown Amount -->
                <div class="col-12">
                  <div class="amount-box p-3 rounded">
                    <div class="d-flex justify-content-between align-items-center mb-1">
                      <span class="text-muted">Subtotal</span>
                      <span class="fw-semibold">Rp <?= number_format($originalAmount, 0, ',', '.') ?></span>
                    </div>
                    <?php if ($discount > 0): ?>
                      <div class="d-flex justify-content-between align-items-center mb-1 text-success">
                        <span>Diskon Voucher</span>
                        <span>- Rp <?= number_format($discount, 0, ',', '.') ?></span>
                      </div>
                    <?php endif; ?>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between align-items-center">
                      <span class="text-muted">Total Dibayar</span>
                      <span class="fs-4 fw-bold text-blue-900">Rp <?= number_format($finalAmount, 0, ',', '.') ?></span>
                    </div>
                  </div>
                </div>

                <?php if (!empty($pay['keterangan'])): ?>
                <div class="col-12">
                  <div class="info-group">
                    <div class="info-label">Keterangan</div>
                    <div class="info-value"><?= nl2br(esc($pay['keterangan'])) ?></div>
                  </div>
                </div>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- Voucher -->
          <?php if ($voucher): ?>
          <div class="card shadow-soft card-glass-plain mb-3 card-glow">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
              <span class="badge bg-blue-soft"><i class="bi bi-tag"></i></span>
              <h6 class="mb-0 fw-semibold text-blue-900">Voucher Diterapkan</h6>
            </div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-md-6">
                  <div class="info-label">Kode Voucher</div>
                  <div class="info-value"><strong><?= esc($voucher['kode_voucher']) ?></strong></div>
                </div>
                <div class="col-md-6">
                  <div class="info-label">Nilai Diskon</div>
                  <div class="info-value text-success">- Rp <?= number_format($discount, 0, ',', '.') ?></div>
                </div>
              </div>
            </div>
          </div>
          <?php endif; ?>

          <!-- Info Event -->
          <div class="card shadow-soft card-glass-plain">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
              <span class="badge bg-blue-soft"><i class="bi bi-calendar-event"></i></span>
              <h6 class="mb-0 fw-semibold text-blue-900">Informasi Event</h6>
            </div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-md-6">
                  <div class="info-label">Tanggal Event</div>
                  <div class="info-value"><?= esc($fmt($event['event_date'] ?? null)) ?></div>
                </div>
                <div class="col-md-6">
                  <div class="info-label">Waktu</div>
                  <div class="info-value"><?= esc($event['event_time'] ?? '-') ?></div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Kanan -->
        <div class="col-12 col-lg-4">
          <?php
            $borderClass = $status === 'verified' ? 'border-success' : ($status === 'pending' ? 'border-warning' : 'border-danger');
          ?>
          <div class="card shadow-soft card-glass-plain mb-3 <?= esc($borderClass) ?>">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
              <span class="badge bg-blue-soft"><i class="bi bi-info-circle"></i></span>
              <h6 class="mb-0 fw-semibold text-blue-900">Status Pembayaran</h6>
            </div>
            <div class="card-body">
              <div class="text-center mb-3">
                <div class="status-icon bg-blue-100 text-blue-900 mb-3">
                  <?php if ($status === 'verified'): ?>
                    <i class="bi bi-check-circle"></i>
                  <?php elseif ($status === 'pending'): ?>
                    <i class="bi bi-hourglass-split"></i>
                  <?php elseif ($status === 'canceled'): ?>
                    <i class="bi bi-x-circle"></i>
                  <?php else: ?>
                    <i class="bi bi-clock"></i>
                  <?php endif; ?>
                </div>
                <h5 class="mb-2"><span class="badge <?= esc($badge) ?> px-3 py-2"><?= strtoupper($status) ?></span></h5>
              </div>

              <?php if ($status === 'verified'): ?>
                <div class="alert alert-success mb-0">
                  <i class="bi bi-check-circle me-2"></i><strong>Pembayaran Berhasil!</strong><br>
                  Anda telah resmi terdaftar sebagai presenter. Semua fitur presenter sudah aktif.
                </div>
              <?php elseif ($status === 'pending'): ?>
                <div class="alert alert-warning mb-3">
                  <i class="bi bi-hourglass-split me-2"></i><strong>Sedang Diproses</strong><br>
                  Pembayaran sedang diverifikasi. Status akan diperbarui otomatis.
                </div>
                <!-- Button Check Status untuk Pending -->
                <?php if ($isMidtrans && $hasOrderId): ?>
                  <a href="<?= site_url('presenter/pembayaran/checkStatus/'.$pay['id_pembayaran']) ?>" 
                     class="btn btn-primary w-100">
                    <i class="bi bi-arrow-clockwise me-2"></i>Cek Status Pembayaran
                  </a>
                <?php endif; ?>
              <?php elseif ($status === 'canceled'): ?>
                <div class="alert alert-danger mb-0">
                  <i class="bi bi-x-circle me-2"></i><strong>Pembayaran Dibatalkan</strong><br>
                  Pembayaran tidak berhasil atau dibatalkan.
                </div>
              <?php elseif ($status === 'expired'): ?>
                <div class="alert alert-secondary mb-0">
                  <i class="bi bi-clock me-2"></i><strong>Pembayaran Kedaluwarsa</strong><br>
                  Waktu pembayaran telah habis.
                </div>
              <?php endif; ?>
            </div>
          </div>

          <div class="card shadow-soft card-glass-plain mt-3 border-info">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
              <span class="badge bg-blue-soft"><i class="bi bi-shield-check"></i></span>
              <h6 class="mb-0 fw-semibold text-blue-900">Keamanan Pembayaran</h6>
            </div>
            <div class="card-body small text-muted">
              <div class="d-flex align-items-center mb-2">
                <i class="bi bi-check-circle text-success me-2"></i><span>Transaksi aman dengan enkripsi SSL</span>
              </div>
              <div class="d-flex align-items-center mb-2">
                <i class="bi bi-check-circle text-success me-2"></i><span>Verifikasi otomatis real-time</span>
              </div>
              <div class="d-flex align-items-center mb-2">
                <i class="bi bi-check-circle text-success me-2"></i><span>Mendukung berbagai metode pembayaran</span>
              </div>
              <div class="d-flex align-items-center">
                <i class="bi bi-check-circle text-success me-2"></i><span>Powered by Midtrans</span>
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
/* ===== Palette & scale — match patokan (Full Paper/Abstrak) ===== */
:root{
  --blue-50:#eff6ff; --blue-100:#dbeafe; --blue-200:#bfdbfe; --blue-300:#93c5fd;
  --blue-400:#60a5fa; --blue-500:#3b82f6; --blue-600:#2563eb; --blue-700:#1d4ed8; --blue-800:#1e40af; --blue-900:#1e3a8a;
  --muted:#6b7280; --ink:#0f172a; --radius:16px;
  --side-pad: clamp(1rem, 2.3vw, 2.2rem);
}

body{ font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size:15.5px; line-height:1.6; color:var(--ink); }

/* Layout background */
.page-wrap-blue{
  min-height:100vh; padding-top:72px;
  background:
    radial-gradient(1000px 380px at 10% -10%, rgba(59,130,246,.16), rgba(59,130,246,0) 60%),
    radial-gradient(1000px 380px at 90% 110%, rgba(59,130,246,.12), rgba(59,130,246,0) 70%),
    linear-gradient(180deg, var(--blue-50), #fff 40%);
}
.container-xxl{ max-width:min(100%, 1560px); padding-left:var(--side-pad)!important; padding-right:var(--side-pad)!important; margin-inline:auto; }
.row.g-3{ --bs-gutter-x: 1rem; --bs-gutter-y: 1rem; }

/* HERO */
.card-hero{ border:0; border-radius:var(--radius); overflow:hidden; box-shadow:0 12px 28px rgba(30,64,175,.18); }
.card-hero .hero-body{
  background:linear-gradient(135deg,var(--blue-700),var(--blue-800)); color:#fff;
  padding:1.8rem 1.2rem; min-height:176px;
}
.hero-title{ font-weight:800; }
.text-white-70{ color:rgba(255,255,255,.85)!important; }

/* Cards / glass */
.card-glass-plain{ backdrop-filter:blur(6px); background:rgba(255,255,255,.96); border-radius:14px; border:1px solid rgba(30,64,175,.10); }
.shadow-soft{ box-shadow:0 10px 24px rgba(30,64,175,.08); }
.card-header{ padding:1rem 1rem .45rem 1rem!important; }
.card-body{   padding:1.05rem!important; }

/* Badges & helpers */
.text-blue-900{ color:var(--blue-900)!important; }
.bg-blue-soft{ background:var(--blue-200); color:var(--blue-800); border-radius:12px; padding:.5rem .7rem; font-weight:600; font-size:.9rem; }
.card-glow{ box-shadow:0 8px 24px rgba(16,185,129,.18); border:1px solid rgba(16,185,129,.2); }

/* Info groups */
.info-group{ margin-bottom:4px; }
.info-label{ font-size:.85rem; font-weight:700; color:#64748b; margin-bottom:4px; }
.info-value{ font-size:1rem; color:#1e293b; font-weight:600; }

/* Amount box */
.amount-box{ background:#f8fafc; border:1px solid rgba(30,64,175,.10); border-radius:12px; }

/* Status icon */
.status-icon{ width:80px; height:80px; border-radius:50%; display:flex; align-items:center; justify-content:center; font-size:2rem; margin:0 auto; }
@media (max-width: 768px){ .status-icon{ width:60px; height:60px; font-size:1.5rem; } }

/* Buttons */
.btn{ font-weight:800; border-radius:10px; font-size:.98rem; padding:.6rem 1.05rem; }
.btn-primary{ background:var(--blue-600); border-color:var(--blue-600); box-shadow:0 4px 12px rgba(37,99,235,.2); }
.btn-success{ background:#059669; border-color:#059669; box-shadow:0 4px 12px rgba(5,150,105,.2); }
.btn-warning{ background:#d97706; border-color:#d97706; box-shadow:0 4px 12px rgba(217,119,6,.2); }
.btn-danger{  background:#dc2626; border-color:#dc2626; box-shadow:0 4px 12px rgba(220,38,38,.2); }
.btn-info{    background:#06b6d4; border-color:#06b6d4; box-shadow:0 4px 12px rgba(6,182,212,.2); }

/* Responsive */
@media (max-width:767.98px){
  .card-hero .hero-body{ padding:1.4rem 1rem; min-height:165px; }
}
@media (max-width:575.98px){
  .container-xxl{ padding-left: calc(var(--side-pad) - .25rem) !important; padding-right: calc(var(--side-pad) - .25rem) !important; }
  .card-header{ padding:.9rem .9rem .4rem .9rem!important; }
  .card-body{ padding:.9rem!important; }
  .badge{ padding:.35rem .55rem; font-size:.8rem; }
}
</style>

<script>
<?php if ($status === 'pending'): ?>
  setTimeout(()=>{ window.location.reload(); }, 30000);
  console.log('Pending payment: auto-refresh in 30s');
<?php endif; ?>
<?php if (session('success')): ?>console.log('Success: <?= esc(session('success')) ?>');<?php endif; ?>
<?php if (session('error')): ?>console.log('Error: <?= esc(session('error')) ?>');<?php endif; ?>
<?php if (session('info')): ?>console.log('Info: <?= esc(session('info')) ?>');<?php endif; ?>
</script>