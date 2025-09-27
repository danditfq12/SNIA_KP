<?php
$title                = $title ?? 'Pembayaran';
$eventsNeedingPayment = $eventsNeedingPayment ?? []; // TAGIHAN
$dueStats             = $dueStats ?? ['count'=>0,'total'=>0,'total_formatted'=>'Rp 0'];
$allPayments          = $allPayments ?? []; // RIWAYAT (SEMUA STATUS)

/* helper kecil untuk label partisipasi (seragam palette subtle) */
function participation_badge(?string $type): string {
  $t = strtolower((string)$type);
  if ($t === 'offline') return '<span class="badge bg-primary-subtle text-primary">Offline</span>';
  if ($t === 'online')  return '<span class="badge bg-info-subtle">Online</span>';
  return '';
}
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill page-wrap-blue">
    <div class="container-xxl px-3 px-md-4 py-4">

      <!-- HERO (seragam seperti halaman lain) -->
      <div class="hero-blue card-glass mb-3 p-3 p-md-4 d-flex justify-content-between align-items-start gap-3">
        <div>
          <h3 class="hero-title mb-1"><i class="bi bi-wallet2 me-2"></i>Pembayaran Saya</h3>
          <div class="text-white-75 small">Tagihan & riwayat pembayaran event</div>
        </div>
        <div class="d-none d-md-block text-end">
          <div class="text-white-75 small">Total Tagihan</div>
          <div class="fw-semibold text-white"><?= esc($dueStats['total_formatted'] ?? 'Rp 0') ?> • <?= (int)($dueStats['count'] ?? 0) ?> item</div>
        </div>
      </div>

      <!-- ===================== RINGKASAN TAGIHAN ===================== -->
      <div class="card shadow-soft card-glass-plain mb-3">
        <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
          <div class="d-flex align-items-center gap-3">
            <div class="stat-icon stat-due"><i class="bi bi-receipt"></i></div>
            <div>
              <div class="text-muted small">Total Tagihan</div>
              <div class="stat-number"><?= esc($dueStats['total_formatted'] ?? 'Rp 0') ?></div>
            </div>
          </div>
          <span class="badge bg-primary-subtle text-primary fw-semibold">
            <?= (int)($dueStats['count'] ?? 0) ?> tagihan
          </span>
        </div>
      </div>

      <!-- ===================== DAFTAR TAGIHAN ===================== -->
      <div class="card shadow-soft card-glass-plain mb-4">
        <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <span class="badge bg-blue-soft"><i class="bi bi-receipt"></i></span>
            <h5 class="mb-0 fw-semibold text-blue-900">Tagihan</h5>
          </div>
          <span class="badge bg-secondary-subtle"><?= count($eventsNeedingPayment) ?></span>
        </div>

        <div class="card-body">
          <?php if (empty($eventsNeedingPayment)): ?>
            <div class="text-muted text-center py-4">
              Tidak ada tagihan saat ini. Tagihan akan muncul otomatis setelah <strong>Full Paper</strong> Anda diterima.
            </div>
          <?php else: ?>
            <div class="row g-3">
              <?php foreach ($eventsNeedingPayment as $bill): ?>
              <div class="col-12 col-md-6 col-xl-4">
                <div class="bill-card event-card h-100 p-3">
                  <div class="d-flex align-items-start justify-content-between mb-2">
                    <div class="me-2">
                      <h6 class="mb-1 text-blue-900"><?= esc($bill['title'] ?? '-') ?></h6>
                      <div class="small text-muted">
                        Tanggal Event: <strong class="text-blue-900"><?= esc($bill['event_date_fmt'] ?? '-') ?></strong>
                      </div>
                    </div>
                  </div>

                  <div class="d-flex justify-content-between align-items-center mt-2">
                    <div class="text-muted small">Jumlah</div>
                    <div class="fw-bold fs-6 text-blue-900"><?= esc($bill['amount_formatted'] ?? 'Rp 0') ?></div>
                  </div>

                  <a href="<?= esc($bill['pay_url'] ?? '#') ?>" class="btn btn-success w-100 mt-3">
                    <i class="bi bi-credit-card me-1"></i> Bayar
                  </a>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- ===================== RIWAYAT PEMBAYARAN ===================== -->
      <div class="card shadow-soft card-glass-plain">
        <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <span class="badge bg-blue-soft"><i class="bi bi-clock-history"></i></span>
            <h5 class="mb-0 fw-semibold text-blue-900">Riwayat Pembayaran</h5>
          </div>
          <span class="badge bg-secondary-subtle"><?= count($allPayments) ?></span>
        </div>

        <?php if (empty($allPayments)): ?>
          <div class="card-body">
            <div class="text-muted text-center py-4">Belum ada riwayat pembayaran.</div>
          </div>
        <?php else: ?>
          <div class="card-body p-0">
            <!-- Desktop -->
            <div class="d-none d-lg-block">
              <div class="table-responsive">
                <table class="table align-middle mb-0">
                  <thead>
                    <tr>
                      <th style="width:50px">#</th>
                      <th>Event</th>
                      <th style="width:160px">Metode</th>
                      <th style="width:140px">Jumlah</th>
                      <th style="width:160px">Tanggal</th>
                      <th style="width:110px">Status</th>
                      <th style="width:100px"></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($allPayments as $i => $row): ?>
                    <?php
                      $eventTitle   = $row['event_title'] ?? ($row['event']['title'] ?? '-');
                      $methodLabel  = $row['method_label'] ?? strtoupper($row['metode'] ?? '-');
                      $methodIcon   = $row['method_icon']  ?? 'bi bi-wallet2';
                      $methodBadge  = $row['method_badge'] ?? 'bg-secondary-subtle';
                      $amountFmt    = $row['jumlah_formatted'] ?? ('Rp ' . number_format((int)($row['jumlah'] ?? 0), 0, ',', '.'));
                      $dateStr      = $row['tanggal_date'] ?? '-';
                      $timeStr      = $row['tanggal_time'] ?? '';
                      $statusText   = ucfirst((string)($row['status'] ?? ''));
                      $statusBadge  = $row['status_badge'] ?? 'bg-secondary-subtle';
                      $participType = $row['participation_type'] ?? null;
                    ?>
                    <tr>
                      <td><?= (int)$i + 1 ?></td>
                      <td>
                        <div class="fw-semibold text-truncate" style="max-width:340px;">
                          <?= esc($eventTitle) ?>
                        </div>
                        <div class="mt-1"><?= participation_badge($participType) ?></div>
                      </td>
                      <td>
                        <span class="badge <?= esc($methodBadge) ?>">
                          <i class="<?= esc($methodIcon) ?>"></i> <?= esc($methodLabel) ?>
                        </span>
                      </td>
                      <td class="fw-semibold text-blue-900"><?= esc($amountFmt) ?></td>
                      <td>
                        <div><?= esc($dateStr) ?></div>
                        <?php if ($timeStr): ?><small class="text-muted"><?= esc($timeStr) ?></small><?php endif; ?>
                      </td>
                      <td><span class="badge <?= esc($statusBadge) ?>"><?= esc($statusText) ?></span></td>
                      <td>
                        <a class="btn btn-sm btn-outline-primary"
                           href="<?= site_url('presenter/pembayaran/detail/'.(int)($row['id_pembayaran'] ?? 0)) ?>">
                           Detail
                        </a>
                      </td>
                    </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Mobile -->
            <div class="d-block d-lg-none p-3">
              <div class="row g-3">
                <?php foreach ($allPayments as $row): ?>
                <?php
                  $eventTitle   = $row['event_title'] ?? ($row['event']['title'] ?? '-');
                  $methodLabel  = $row['method_label'] ?? strtoupper($row['metode'] ?? '-');
                  $methodIcon   = $row['method_icon']  ?? 'bi bi-wallet2';
                  $methodBadge  = $row['method_badge'] ?? 'bg-secondary-subtle';
                  $amountFmt    = $row['jumlah_formatted'] ?? ('Rp ' . number_format((int)($row['jumlah'] ?? 0), 0, ',', '.'));
                  $dateStr      = $row['tanggal_date'] ?? '-';
                  $timeStr      = $row['tanggal_time'] ?? '';
                  $statusText   = ucfirst((string)($row['status'] ?? ''));
                  $statusBadge  = $row['status_badge'] ?? 'bg-secondary-subtle';
                  $participType = $row['participation_type'] ?? null;

                  $statusClass = 'payment-other';
                  $st = strtolower((string)($row['status'] ?? ''));
                  if ($st === 'verified')     $statusClass = 'payment-verified';
                  elseif ($st === 'canceled') $statusClass = 'payment-canceled';
                  elseif ($st === 'pending')  $statusClass = 'payment-pending';
                ?>
                <div class="col-12">
                  <div class="payment-card <?= esc($statusClass) ?>">
                    <div class="payment-card-header">
                      <div class="d-flex align-items-start justify-content-between">
                        <div class="flex-grow-1 me-2">
                          <h6 class="payment-event-title mb-1"><?= esc($eventTitle) ?></h6>
                          <div class="payment-meta">
                            <span class="badge <?= esc($methodBadge) ?> me-2">
                              <i class="<?= esc($methodIcon) ?> me-1"></i><?= esc($methodLabel) ?>
                            </span>
                            <?= participation_badge($participType) ?>
                          </div>
                        </div>
                        <span class="badge <?= esc($statusBadge) ?>"><?= esc($statusText) ?></span>
                      </div>
                    </div>
                    <div class="payment-card-body">
                      <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small">Jumlah</span>
                        <span class="fw-bold text-blue-900"><?= esc($amountFmt) ?></span>
                      </div>
                      <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted small">Tanggal</span>
                        <span class="small"><?= esc($dateStr) ?><?= $timeStr ? ', '.esc($timeStr) : '' ?></span>
                      </div>
                      <a href="<?= site_url('presenter/pembayaran/detail/'.(int)($row['id_pembayaran'] ?? 0)) ?>"
                         class="btn btn-outline-primary btn-sm w-100">
                        <i class="bi bi-eye me-1"></i>Lihat Detail
                      </a>
                    </div>
                  </div>
                </div>
                <?php endforeach; ?>
              </div>
            </div>

          </div>
        <?php endif; ?>
      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<style>
/* ===== Palette & scale — sama dengan halaman lain ===== */
:root{
  --blue-50:#eff6ff; --blue-100:#dbeafe; --blue-200:#bfdbfe;
  --blue-300:#93c5fd; --blue-400:#60a5fa; --blue-500:#3b82f6;
  --blue-600:#2563eb; --blue-700:#1d4ed8; --blue-800:#1e40af; --blue-900:#1e3a8a;

  --side-pad: 1rem;
  --gutter:   1rem;
}

body{
  font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  font-size:14.6px;
  line-height:1.5;
}

/* ===== Layout ===== */
.page-wrap-blue{
  background:linear-gradient(180deg,var(--blue-50),#fff 40%);
  min-height:100vh;
  padding-top:72px;
}
.container-xxl{
  max-width:1400px;
  padding-left:var(--side-pad) !important;
  padding-right:var(--side-pad) !important;
}
.row.g-3{ --bs-gutter-x: var(--gutter); --bs-gutter-y: var(--gutter); }

/* ===== Hero (non-glass override sama seperti lain) ===== */
.hero-blue{
  background:radial-gradient(1200px 400px at 10% -20%,var(--blue-600) 0,var(--blue-700) 40%,var(--blue-800) 100%) !important;
  color:#fff !important;
  border-radius:16px;
  border:1px solid rgba(255,255,255,.15);
  box-shadow:0 12px 28px rgba(30,64,175,.10);
  padding:1.6rem !important;
  margin-bottom:1.25rem !important;
}
.hero-blue.card-glass,
.hero-blue.card-glass-plain{
  backdrop-filter:none !important;
  background:radial-gradient(1200px 400px at 10% -20%,var(--blue-600) 0,var(--blue-700) 40%,var(--blue-800) 100%) !important;
  border:1px solid rgba(255,255,255,.15) !important;
  box-shadow:0 12px 28px rgba(30,64,175,.10) !important;
}
.hero-title{ font-weight:800; letter-spacing:.25px; font-size:1.45rem; }
.text-white-75{ color:rgba(255,255,255,.85)!important; }

/* ===== Cards ===== */
.card-glass-plain{
  backdrop-filter:blur(6px);
  background:rgba(255,255,255,.94);
  border-radius:14px;
  border:1px solid rgba(30,64,175,.10);
}
.shadow-soft{ box-shadow:0 10px 24px rgba(30,64,175,.08); }

.card-header{ padding:1rem 1rem .45rem 1rem !important; }
.card-body{   padding:1.05rem !important; }

/* ===== Badges & text ===== */
.bg-blue-soft{ background:var(--blue-200); color:var(--blue-800); border-radius:12px; padding:.4rem .6rem; font-weight:600; font-size:.85rem; }
.bg-success-subtle{   background:#d1fae5!important; color:#065f46!important; }
.bg-warning-subtle{   background:#fef3c7!important; color:#92400e!important; }
.bg-danger-subtle{    background:#fee2e2!important; color:#991b1b!important; }
.bg-info-subtle{      background:#e0f2fe!important; color:#0c4a6e!important; }
.bg-secondary-subtle{ background:#f1f5f9!important; color:#475569!important; }
.bg-primary-subtle{   background:#dbeafe!important; color:var(--blue-700)!important; }
.text-blue-900{ color:var(--blue-900)!important; }

/* ===== Event/bill cards ===== */
.event-card{
  background:linear-gradient(180deg,#fff,rgba(255,255,255,.96));
  border:1px solid rgba(30,64,175,.10);
  border-radius:14px;
  box-shadow:0 10px 22px rgba(30,64,175,.08);
}
.bill-card .btn{ border-radius:10px; }

/* ===== Table ===== */
.table{ font-size:.95rem; margin-bottom:0; }
.table thead th{
  background-color:var(--blue-50)!important;
  border-bottom:1px solid var(--blue-200);
  font-weight:600; color:var(--blue-900);
  font-size:.85rem; text-transform:uppercase; letter-spacing:.4px;
  padding:.75rem 1rem;
}
.table tbody tr{ border-bottom:1px solid rgba(30,64,175,.08); }
.table tbody td{
  padding:.8rem 1rem; vertical-align:middle; border-top:none; font-size:.95rem;
}
.table-responsive{ border:1px solid rgba(30,64,175,.08); border-radius:12px; overflow:hidden; }

/* ===== Payment cards (mobile) ===== */
.payment-card{ background:#fff; border-radius:12px; border:1px solid rgba(30,64,175,.12); overflow:hidden; transition:.2s; }
.payment-card:hover{ box-shadow:0 4px 12px rgba(30,64,175,.12); transform: translateY(-1px); }
.payment-card-header{ padding:16px 16px 0; }
.payment-card-body{ padding:0 16px 16px; }
.payment-event-title{ font-weight:600; color:#1f2937; line-height:1.3; margin:0; }
.payment-meta{ margin-top:8px; }
.payment-pending  { border-left:4px solid #d97706; }  /* warning */
.payment-verified { border-left:4px solid #059669; }  /* success */
.payment-canceled { border-left:4px solid #94a3b8; }  /* secondary */
.payment-other    { border-left:4px solid #cbd5e1; }

/* ===== Stats ===== */
.stat-icon{ width:48px; height:48px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.4rem; }
.stat-due{ background:var(--blue-200); color:var(--blue-800); }
.stat-number{ font-size:1.6rem; font-weight:800; color:#111827; }

/* ===== Buttons ===== */
.btn{
  font-weight:600; letter-spacing:.25px;
  border-radius:10px; font-size:.95rem; padding:.55rem 1rem;
}
.btn-sm{ padding:.42rem .8rem; font-size:.86rem; }
.btn-lg{ padding:.85rem 1.5rem; font-size:1.06rem; }
.btn-primary{ background:var(--blue-600); border-color:var(--blue-600); box-shadow:0 4px 12px rgba(37,99,235,.2); }
.btn-info{    background:#06b6d4; border-color:#06b6d4; box-shadow:0 4px 12px rgba(6,182,212,.2); }
.btn-success{ background:#059669; border-color:#059669; box-shadow:0 4px 12px rgba(5,150,105,.2); }
.btn-warning{ background:#d97706; border-color:#d97706; box-shadow:0 4px 12px rgba(217,119,6,.2); }
.btn-danger{  background:#dc2626; border-color:#dc2626; box-shadow:0 4px 12px rgba(220,38,38,.2); }

/* ===== Helpers ===== */
.text-muted{ color:#6b7280!important; font-weight:500; font-size:.9rem; }
.text-muted.small{ font-size:.84rem!important; }
.fw-semibold{ font-weight:600!important; }
.fw-medium{   font-weight:500!important; }
a{ text-decoration:none; }

/* ===== Responsive ===== */
@media (max-width:575.98px){
  .container-xxl{ padding-left:1rem !important; padding-right:1rem !important; }
  .hero-blue{ border-radius:14px; padding:1.25rem!important; margin-bottom:1rem!important; }
  .hero-title{ font-size:1.25rem; }
  .card-body{  padding:.9rem!important; }
  .card-header{ padding:.9rem .9rem .4rem .9rem!important; }
  .badge{ padding:.35rem .55rem; font-size:.7rem; }
}
@media (min-width:576px) and (max-width:767.98px){
  .container-xxl{ padding-left:1rem !important; padding-right:1rem !important; }
  .hero-title{ font-size:1.35rem; }
  .card-body{  padding:1rem!important; }
}
@media (min-width:768px) and (max-width:991.98px){
  .container-xxl{ padding-left:1rem !important; padding-right:1rem !important; }
  .hero-title{ font-size:1.45rem; }
}
@media (min-width:992px){
  .hero-blue{ padding:1.7rem!important; }
  .card-body{  padding:1.05rem!important; }
  .card-header{ padding:1rem 1rem .45rem 1rem!important; }
}
</style>
