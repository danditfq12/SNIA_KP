<?php
  $title    = $title ?? 'Pembayaran';
  $payments = $payments ?? [];
  $stats    = $stats ?? ['total'=>0,'pending'=>0,'verified'=>0,'rejected'=>0,'canceled'=>0];
  $fmtDT = fn($s)=> $s ? date('d M Y H:i', strtotime($s)) : '-';
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <div class="abs-hero mb-3">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="abs-title">Pembayaran</div>
            <div class="abs-sub">Kelola pembayaran event sebagai Presenter (OFFLINE).</div>
          </div>
          <div class="d-none d-md-flex gap-2">
            <a href="<?= site_url('presenter/events') ?>" class="btn btn-primary">
              <i class="bi bi-calendar2-event me-1"></i>Daftar / Bayar Event
            </a>
          </div>
        </div>
      </div>

      <!-- KPI -->
      <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3"><div class="card kpi-card shadow-sm h-100"><div class="card-body d-flex align-items-center gap-3">
          <div class="kpi-icon bg-secondary-subtle text-secondary"><i class="bi bi-receipt"></i></div>
          <div><div class="text-muted small">Total Pembayaran</div><div class="fs-4 fw-semibold"><?= number_format($stats['total']) ?></div></div>
        </div></div></div>
        <div class="col-6 col-xl-3"><div class="card kpi-card shadow-sm h-100"><div class="card-body d-flex align-items-center gap-3">
          <div class="kpi-icon bg-warning-subtle text-warning"><i class="bi bi-hourglass-split"></i></div>
          <div><div class="text-muted small">Pending</div><div class="fs-4 fw-semibold"><?= number_format($stats['pending']) ?></div></div>
        </div></div></div>
        <div class="col-6 col-xl-3"><div class="card kpi-card shadow-sm h-100"><div class="card-body d-flex align-items-center gap-3">
          <div class="kpi-icon bg-success-subtle text-success"><i class="bi bi-check2-circle"></i></div>
          <div><div class="text-muted small">Verified</div><div class="fs-4 fw-semibold"><?= number_format($stats['verified']) ?></div></div>
        </div></div></div>
        <div class="col-6 col-xl-3"><div class="card kpi-card shadow-sm h-100"><div class="card-body d-flex align-items-center gap-3">
          <div class="kpi-icon bg-danger-subtle text-danger"><i class="bi bi-x-circle"></i></div>
          <div><div class="text-muted small">Rejected / Canceled</div><div class="fs-4 fw-semibold"><?= number_format($stats['rejected'] + $stats['canceled']) ?></div></div>
        </div></div></div>
      </div>

      <!-- List -->
      <div class="card shadow-sm border-0">
        <div class="card-body">
          <h5 class="card-title mb-3">Riwayat Pembayaran</h5>

          <?php if (!empty($payments)): ?>
            <div class="table-responsive">
              <table class="table table-hover align-middle">
                <thead class="table-light">
                  <tr>
                    <th>Event</th>
                    <th class="text-nowrap">Tanggal</th>
                    <th class="text-end">Jumlah</th>
                    <th>Status</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($payments as $p): ?>
                    <?php
                      $badge = match (strtolower((string)$p['status'])) {
                        'pending'  => 'bg-warning text-dark',
                        'verified' => 'bg-success',
                        'rejected' => 'bg-danger',
                        'canceled' => 'bg-secondary',
                        default    => 'bg-secondary'
                      };
                    ?>
                    <tr>
                      <td>
                        <div class="fw-semibold"><?= esc($p['title'] ?? 'Event') ?></div>
                        <div class="small text-muted"><?= esc(date('d M Y', strtotime($p['event_date'] ?? 'now'))) ?> · <?= esc($p['event_time'] ?? '-') ?></div>
                      </td>
                      <td class="text-nowrap"><?= esc($fmtDT($p['tanggal_bayar'])) ?></td>
                      <td class="text-end">Rp <?= number_format((float)$p['jumlah'], 0, ',', '.') ?></td>
                      <td><span class="badge <?= $badge ?>"><?= ucfirst($p['status']) ?></span></td>
                      <td class="text-end">
                        <a href="<?= site_url('presenter/pembayaran/detail/'.$p['id_pembayaran']) ?>" class="btn btn-sm btn-outline-primary">Detail</a>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php else: ?>
            <div class="p-4 text-center border rounded-3 bg-light-subtle">
              <div class="mb-2"><i class="bi bi-wallet2 fs-3 text-secondary"></i></div>
              <div class="fw-semibold">Belum ada pembayaran</div>
              <div class="text-muted small">Mulai dari daftar event presenter di menu Event.</div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<style>
.abs-hero{background:linear-gradient(90deg,#2563eb,#60a5fa);border-radius:16px;color:#fff;padding:14px 16px;box-shadow:0 6px 20px rgba(37,99,235,.18);}
.abs-title{font-weight:800;line-height:1.2;font-size:clamp(18px,4.2vw,24px);}
.abs-sub{opacity:.9;font-size:.95rem;}
.kpi-card{border:0;border-left:4px solid #e9ecef;}
.kpi-icon{width:44px;height:44px;border-radius:10px;display:grid;place-items:center;font-size:20px;}
</style>
