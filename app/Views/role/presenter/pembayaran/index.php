<?php
$title   = $title ?? 'Pembayaran';
$eventsNeedingPayment = $eventsNeedingPayment ?? []; // TAGIHAN
$dueStats = $dueStats ?? ['count'=>0,'total'=>0,'total_formatted'=>'Rp 0'];
$allPayments = $allPayments ?? []; // RIWAYAT (SEMUA STATUS)
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <!-- Header -->
      <div class="header-section header-blue d-flex justify-content-between align-items-center mb-4">
        <div>
          <h3 class="welcome-text mb-1"><i class="bi bi-wallet2 me-2"></i>Pembayaran Saya</h3>
          <div class="text-white-50">Tagihan & riwayat pembayaran event</div>
        </div>
      </div>

      <!-- ===================== TAGIHAN (SELALU ADA BAGIAN INI) ===================== -->
      <div class="card shadow-sm mb-3 border-0">
        <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-2">
          <div class="d-flex align-items-center gap-3">
            <div class="stat-icon stat-due"><i class="bi bi-receipt"></i></div>
            <div>
              <div class="text-muted small">Total Tagihan</div>
              <div class="stat-number"><?= esc($dueStats['total_formatted']) ?></div>
            </div>
          </div>
          <div class="text-muted">
            <span class="badge bg-primary-subtle text-primary">
              <?= (int)$dueStats['count'] ?> tagihan
            </span>
          </div>
        </div>
      </div>

      <div class="card shadow-sm mb-4 border-0">
        <div class="card-header bg-gradient-primary text-white">
          <div class="d-flex align-items-center justify-content-between">
            <h5 class="mb-0"><i class="bi bi-receipt me-2"></i>Tagihan</h5>
            <span class="badge bg-light text-dark"><?= count($eventsNeedingPayment) ?></span>
          </div>
        </div>

        <div class="card-body">
          <?php if (empty($eventsNeedingPayment)): ?>
            <div class="text-muted text-center py-4">
              Tidak ada tagihan saat ini. Tagihan akan muncul otomatis setelah abstrak Anda <strong>diterima</strong>.
            </div>
          <?php else: ?>
            <div class="row g-3">
              <?php foreach ($eventsNeedingPayment as $bill): ?>
              <div class="col-12 col-md-6 col-xl-4">
                <div class="bill-card">
                  <div class="d-flex align-items-start justify-content-between">
                    <div class="me-2">
                      <h6 class="mb-1"><?= esc($bill['title']) ?></h6>
                      <div class="small text-muted">
                        Tanggal Event: <strong><?= esc($bill['event_date_fmt']) ?></strong>
                      </div>
                    </div>
                    <span class="badge bg-info">Abstrak Diterima</span>
                  </div>

                  <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="text-muted small">Jumlah</div>
                    <div class="fw-bold fs-6"><?= esc($bill['amount_formatted']) ?></div>
                  </div>

                  <a href="<?= esc($bill['pay_url']) ?>" class="btn btn-success w-100 mt-3">
                    <i class="bi bi-credit-card me-1"></i> Bayar
                  </a>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- ===================== RIWAYAT PEMBAYARAN (SELALU ADA BAGIAN INI) ===================== -->
      <div class="card shadow-sm border-0">
        <div class="card-header bg-gradient-secondary text-white">
          <div class="d-flex align-items-center justify-content-between">
            <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Riwayat Pembayaran</h5>
            <span class="badge bg-light text-dark"><?= count($allPayments) ?></span>
          </div>
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
                <table class="table table-hover align-middle mb-0">
                  <thead class="table-light">
                    <tr>
                      <th style="width:50px">#</th>
                      <th>Event</th>
                      <th style="width:140px">Metode</th>
                      <th style="width:120px">Jumlah</th>
                      <th style="width:140px">Tanggal</th>
                      <th style="width:110px">Status</th>
                      <th style="width:100px"></th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($allPayments as $i => $row): ?>
                    <tr>
                      <td><?= $i+1 ?></td>
                      <td>
                        <div class="fw-semibold text-truncate" style="max-width:320px;">
                          <?= esc($row['event_title']) ?>
                        </div>
                        <?php if (!empty($row['participation'])): ?>
                          <small class="text-muted"><?= esc($row['participation']) ?></small>
                        <?php endif; ?>
                      </td>
                      <td>
                        <span class="badge <?= esc($row['method_badge']) ?>">
                          <i class="<?= esc($row['method_icon']) ?>"></i> <?= esc($row['method_label']) ?>
                        </span>
                      </td>
                      <td class="fw-semibold"><?= esc($row['jumlah_formatted']) ?></td>
                      <td>
                        <div><?= esc($row['tanggal_date']) ?></div>
                        <small class="text-muted"><?= esc($row['tanggal_time']) ?></small>
                      </td>
                      <td><span class="badge <?= esc($row['status_badge']) ?>"><?= ucfirst(esc($row['status'])) ?></span></td>
                      <td>
                        <a class="btn btn-sm btn-outline-primary"
                           href="<?= site_url('presenter/pembayaran/detail/'.(int)$row['id_pembayaran']) ?>">
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
                  $statusClass = 'payment-other';
                  $st = strtolower($row['status'] ?? '');
                  if ($st === 'verified') $statusClass = 'payment-verified';
                  elseif ($st === 'canceled') $statusClass = 'payment-canceled';
                  elseif ($st === 'pending')  $statusClass = 'payment-pending';
                ?>
                <div class="col-12">
                  <div class="payment-card <?= esc($statusClass) ?>">
                    <div class="payment-card-header">
                      <div class="d-flex align-items-start justify-content-between">
                        <div class="flex-grow-1 me-2">
                          <h6 class="payment-event-title mb-1"><?= esc($row['event_title']) ?></h6>
                          <div class="payment-meta">
                            <span class="badge <?= esc($row['method_badge']) ?> me-2">
                              <i class="<?= esc($row['method_icon']) ?> me-1"></i><?= esc($row['method_label']) ?>
                            </span>
                            <?php if (!empty($row['participation'])): ?>
                            <span class="badge bg-light text-dark"><?= esc($row['participation']) ?></span>
                            <?php endif; ?>
                          </div>
                        </div>
                        <span class="badge <?= esc($row['status_badge']) ?>"><?= ucfirst(esc($row['status'])) ?></span>
                      </div>
                    </div>
                    <div class="payment-card-body">
                      <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small">Jumlah</span>
                        <span class="fw-bold"><?= esc($row['jumlah_formatted']) ?></span>
                      </div>
                      <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="text-muted small">Tanggal</span>
                        <span class="small"><?= esc($row['tanggal_date']) ?>, <?= esc($row['tanggal_time']) ?></span>
                      </div>
                      <a href="<?= site_url('presenter/pembayaran/detail/'.(int)$row['id_pembayaran']) ?>"
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
  :root{
    --primary-color:#2563eb; --info-color:#06b6d4; --success-color:#10b981;
    --warning-color:#f59e0b; --danger-color:#ef4444; --secondary:#64748b;
  }
  body{ background:#f8fafc; }
  .header-section.header-blue{
    background:linear-gradient(135deg,var(--primary-color),#1e40af);
    color:#fff; padding:24px; border-radius:16px; box-shadow:0 8px 28px rgba(0,0,0,.12);
  }
  .welcome-text{ font-weight:700; }
  .bg-gradient-primary{ background:linear-gradient(135deg,var(--primary-color),var(--info-color))!important; }
  .bg-gradient-secondary{ background:linear-gradient(135deg,var(--secondary),#475569)!important; }

  .stat-icon{ width:48px; height:48px; border-radius:10px; display:flex; align-items:center; justify-content:center; font-size:1.4rem; }
  .stat-due{ background:#dbeafe; color:#1d4ed8; }
  .stat-number{ font-size:1.6rem; font-weight:800; color:#111827; }

  .bill-card{
    background:#fff; border:1px solid #e5e7eb; border-radius:12px; padding:16px;
    box-shadow:0 2px 8px rgba(0,0,0,.06);
  }

  .payment-card { background:white; border-radius:12px; border:1px solid #e5e7eb; overflow:hidden; transition:.2s; }
  .payment-card:hover { box-shadow:0 4px 12px rgba(0,0,0,0.1); transform: translateY(-1px); }
  .payment-card-header { padding:16px 16px 0; }
  .payment-card-body { padding:0 16px 16px; }
  .payment-event-title { font-weight:600; color:#1f2937; line-height:1.3; margin:0; }
  .payment-meta { margin-top:8px; }

  .payment-pending  { border-left:4px solid var(--warning-color); }
  .payment-verified { border-left:4px solid var(--success-color); }
  .payment-canceled { border-left:4px solid var(--secondary); }
</style>