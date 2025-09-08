<?php
// =========================================
//  Pembayaran - Index (Audience)
//  Memisahkan "pending" (aktif) vs "riwayat"
//  Controller mengirim: $payments (array), $eventMap (id => title)
// =========================================
$title     = $title ?? 'Pembayaran Saya';
$payments  = $payments ?? [];
$eventMap  = $eventMap ?? [];
$badgeMap  = ['pending'=>'warning','verified'=>'success','rejected'=>'danger','canceled'=>'secondary'];

$aktif   = array_values(array_filter($payments, fn($r)=> ($r['status'] ?? '') === 'pending'));
$riwayat = array_values(array_filter($payments, fn($r)=> ($r['status'] ?? '') !== 'pending'));

$fmtDate = function($s){
  return $s ? date('d M Y, H:i', strtotime($s)) : '-';
};
$fmtRp = fn($n)=> 'Rp ' . number_format((float)$n, 0, ',', '.');
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_audience') ?>

<div id="content">
  <main class="flex-fill">
    <div class="container-fluid p-3 p-md-4">

      <div class="d-flex align-items-center justify-content-between gap-2 mb-2">
        <h4 class="mb-0">Pembayaran</h4>
        <a href="<?= site_url('audience/events') ?>" class="btn btn-sm btn-outline-secondary d-none d-md-inline-flex">
          <i class="bi bi-calendar2-event me-1"></i> Event
        </a>
      </div>

      <?php if (session('message')): ?>
        <div class="alert alert-success"><?= esc(session('message')) ?></div>
      <?php endif; ?>
      <?php if (session('error')): ?>
        <div class="alert alert-danger"><?= esc(session('error')) ?></div>
      <?php endif; ?>

      <?php if (empty($payments)): ?>
        <div class="p-4 text-center border rounded-3 bg-light-subtle">
          <div class="mb-2"><i class="bi bi-wallet2 fs-3 text-secondary"></i></div>
          <div class="fw-semibold">Belum ada pembayaran</div>
          <div class="text-muted small">Pembayaranmu akan muncul di sini setelah mengunggah bukti.</div>
        </div>
      <?php else: ?>

        <!-- ========== AKTIF (Pending) ========== -->
        <div class="card shadow-sm border-0 mb-3">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between">
              <h5 class="card-title mb-0">Menunggu Verifikasi</h5>
              <span class="badge bg-warning-subtle text-warning"><?= count($aktif) ?></span>
            </div>
            <hr class="my-3">

            <?php if (empty($aktif)): ?>
              <div class="text-muted small">Tidak ada pembayaran pending.</div>
            <?php else: ?>

              <!-- Desktop table -->
              <div class="d-none d-md-block">
                <div class="table-responsive">
                  <table class="table table-hover align-middle">
                    <thead class="table-light">
                      <tr>
                        <th style="width:56px">No</th>
                        <th>Event</th>
                        <th style="width:180px">Jumlah</th>
                        <th style="width:160px">Tanggal</th>
                        <th style="width:120px">Status</th>
                        <th style="width:110px"></th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($aktif as $i => $row): ?>
                        <tr>
                          <td><?= $i+1 ?></td>
                          <td class="text-truncate" style="max-width:480px;">
                            <?= esc($eventMap[(int)$row['event_id']] ?? '-') ?>
                          </td>
                          <td><?= $fmtRp($row['jumlah'] ?? 0) ?></td>
                          <td><?= esc($fmtDate($row['tanggal_bayar'] ?? null)) ?></td>
                          <td>
                            <span class="badge bg-<?= $badgeMap[$row['status']] ?? 'secondary' ?>">
                              <?= ucfirst(esc($row['status'])) ?>
                            </span>
                          </td>
                          <td class="text-end">
                            <a class="btn btn-sm btn-primary"
                               href="<?= site_url('audience/pembayaran/detail/'.(int)$row['id_pembayaran']) ?>">
                              Detail
                            </a>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- Mobile cards -->
              <div class="d-block d-md-none">
                <div class="vstack gap-2">
                  <?php foreach ($aktif as $row): ?>
                    <div class="card shadow-sm border-0">
                      <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                          <div class="me-2">
                            <div class="fw-semibold text-truncate" style="max-width: 220px;">
                              <?= esc($eventMap[(int)$row['event_id']] ?? '-') ?>
                            </div>
                            <small class="text-muted"><?= esc($fmtDate($row['tanggal_bayar'] ?? null)) ?></small>
                          </div>
                          <span class="badge bg-<?= $badgeMap[$row['status']] ?? 'secondary' ?>">
                            <?= ucfirst(esc($row['status'])) ?>
                          </span>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-2">
                          <div class="small text-muted">Jumlah</div>
                          <div class="fw-semibold"><?= $fmtRp($row['jumlah'] ?? 0) ?></div>
                        </div>

                        <div class="mt-3 d-grid">
                          <a href="<?= site_url('audience/pembayaran/detail/'.(int)$row['id_pembayaran']) ?>"
                             class="btn btn-primary btn-sm">Detail</a>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>

            <?php endif; ?>
          </div>
        </div>

        <!-- ========== RIWAYAT (Verified/Rejected/Canceled) ========== -->
        <div class="card shadow-sm border-0">
          <div class="card-body">
            <div class="d-flex align-items-center justify-content-between">
              <h5 class="card-title mb-0">Riwayat</h5>
              <span class="badge bg-secondary-subtle text-secondary"><?= count($riwayat) ?></span>
            </div>
            <hr class="my-3">

            <?php if (empty($riwayat)): ?>
              <div class="text-muted small">Belum ada riwayat pembayaran.</div>
            <?php else: ?>

              <!-- Desktop table -->
              <div class="d-none d-md-block">
                <div class="table-responsive">
                  <table class="table table-hover align-middle">
                    <thead class="table-light">
                      <tr>
                        <th style="width:56px">No</th>
                        <th>Event</th>
                        <th style="width:180px">Jumlah</th>
                        <th style="width:160px">Tanggal</th>
                        <th style="width:120px">Status</th>
                        <th style="width:110px"></th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($riwayat as $i => $row): ?>
                        <tr>
                          <td><?= $i+1 ?></td>
                          <td class="text-truncate" style="max-width:480px;">
                            <?= esc($eventMap[(int)$row['event_id']] ?? '-') ?>
                          </td>
                          <td><?= $fmtRp($row['jumlah'] ?? 0) ?></td>
                          <td><?= esc($fmtDate($row['tanggal_bayar'] ?? null)) ?></td>
                          <td>
                            <span class="badge bg-<?= $badgeMap[$row['status']] ?? 'secondary' ?>">
                              <?= ucfirst(esc($row['status'])) ?>
                            </span>
                          </td>
                          <td class="text-end">
                            <a class="btn btn-sm btn-outline-primary"
                               href="<?= site_url('audience/pembayaran/detail/'.(int)$row['id_pembayaran']) ?>">
                              Detail
                            </a>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>

              <!-- Mobile cards -->
              <div class="d-block d-md-none">
                <div class="vstack gap-2">
                  <?php foreach ($riwayat as $row): ?>
                    <div class="card shadow-sm border-0">
                      <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-1">
                          <div class="me-2">
                            <div class="fw-semibold text-truncate" style="max-width: 220px;">
                              <?= esc($eventMap[(int)$row['event_id']] ?? '-') ?>
                            </div>
                            <small class="text-muted"><?= esc($fmtDate($row['tanggal_bayar'] ?? null)) ?></small>
                          </div>
                          <span class="badge bg-<?= $badgeMap[$row['status']] ?? 'secondary' ?>">
                            <?= ucfirst(esc($row['status'])) ?>
                          </span>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mt-2">
                          <div class="small text-muted">Jumlah</div>
                          <div class="fw-semibold"><?= $fmtRp($row['jumlah'] ?? 0) ?></div>
                        </div>

                        <div class="mt-3 d-grid">
                          <a href="<?= site_url('audience/pembayaran/detail/'.(int)$row['id_pembayaran']) ?>"
                             class="btn btn-outline-primary btn-sm">Detail</a>
                        </div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              </div>

            <?php endif; ?>
          </div>
        </div>

      <?php endif; ?>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>
