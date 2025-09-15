<?php
// =========================================
//  Pembayaran - Index (Audience)
//  Controller mengirim: $aktif, $riwayat, $eventMap, $badgeMap
// =========================================
$title    = $title    ?? 'Pembayaran Saya';
$eventMap = $eventMap ?? [];
$badgeMap = $badgeMap ?? ['pending'=>'warning','verified'=>'success','rejected'=>'danger','canceled'=>'secondary'];
$aktif    = $aktif    ?? [];
$riwayat  = $riwayat  ?? [];

$fmtDate = function($s){ return $s ? date('d M Y, H:i', strtotime($s)) : '-'; };
$fmtRp   = fn($n)=> 'Rp ' . number_format((float)$n, 0, ',', '.');
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_audience') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <!-- Header Biru (selaras dengan halaman Abstrak/Presenter) -->
      <div class="header-section header-blue d-flex justify-content-between align-items-center mb-3">
        <div>
          <h3 class="welcome-text mb-1"><i class="bi bi-wallet2 me-2"></i>Pembayaran</h3>
          <div class="text-white-50">Kelola & pantau status pembayaranmu</div>
        </div>
        <div class="text-end d-none d-md-block">
          <small class="text-white-50 d-block">Hari ini</small>
          <strong class="text-white"><?= date('d M Y') ?></strong>
        </div>
      </div>

      <?php if (empty($aktif) && empty($riwayat)): ?>
        <div class="p-4 text-center border rounded-3 bg-light-subtle">
          <div class="mb-2"><i class="bi bi-wallet2 fs-3 text-secondary"></i></div>
          <div class="fw-semibold">Belum ada pembayaran</div>
          <div class="text-muted small">Pembayaranmu akan muncul di sini setelah mengunggah bukti.</div>
          <a href="<?= site_url('audience/events') ?>" class="btn btn-primary btn-sm mt-2">
            <i class="bi bi-calendar2-event me-1"></i> Lihat Event
          </a>
        </div>
      <?php else: ?>

        <!-- ========== AKTIF (Pending) ========== -->
        <div class="card shadow-sm mb-4 border-0 overflow-hidden">
          <div class="card-header bg-gradient-primary text-white">
            <div class="d-flex align-items-center justify-content-between">
              <h5 class="mb-0"><i class="bi bi-hourglass-split me-2"></i>Menunggu Verifikasi</h5>
              <span class="badge bg-warning text-dark"><?= count($aktif) ?></span>
            </div>
          </div>
          <div class="card-body">
            <?php if (empty($aktif)): ?>
              <div class="text-muted small">Tidak ada pembayaran pending.</div>
            <?php else: ?>

              <!-- Desktop table -->
              <div class="d-none d-md-block">
                <div class="table-responsive clip-x">
                  <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                      <tr>
                        <th style="width:56px">No</th>
                        <th>Event</th>
                        <th style="width:180px">Jumlah</th>
                        <th style="width:180px">Tanggal</th>
                        <th style="width:140px">Status</th>
                        <th style="width:120px"></th>
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
                    <div class="event-card shadow-sm">
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
                  <?php endforeach; ?>
                </div>
              </div>

            <?php endif; ?>
          </div>
        </div>

        <!-- ========== RIWAYAT (Verified/Rejected/Canceled) ========== -->
        <div class="card shadow-sm mb-2 border-0 overflow-hidden">
          <div class="card-header bg-gradient-primary text-white">
            <div class="d-flex align-items-center justify-content-between">
              <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Riwayat</h5>
              <span class="badge bg-light text-dark"><?= count($riwayat) ?></span>
            </div>
          </div>
          <div class="card-body">
            <?php if (empty($riwayat)): ?>
              <div class="text-muted small">Belum ada riwayat pembayaran.</div>
            <?php else: ?>

              <!-- Desktop table -->
              <div class="d-none d-md-block">
                <div class="table-responsive clip-x">
                  <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                      <tr>
                        <th style="width:56px">No</th>
                        <th>Event</th>
                        <th style="width:180px">Jumlah</th>
                        <th style="width:180px">Tanggal</th>
                        <th style="width:140px">Status</th>
                        <th style="width:120px"></th>
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
                    <div class="event-card shadow-sm">
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

<style>
  :root{
    --primary-color:#2563eb; --info-color:#06b6d4; --success-color:#10b981; --secondary:#475569;
    --ring:#eef2f7;
  }
  body{ background:#f9fafb; }
  .header-section.header-blue{
    background:linear-gradient(135deg,var(--primary-color),#1e40af);
    color:#fff; padding:24px; border-radius:16px; box-shadow:0 8px 28px rgba(0,0,0,.12);
  }
  .welcome-text{ font-weight:700; }
  .bg-gradient-primary{ background:linear-gradient(135deg,var(--primary-color),var(--info-color))!important; }
  .clip-x{ overflow:auto; -webkit-overflow-scrolling:touch; }
  .event-card{
    background:#f3f4f6;
    border-radius:14px;
    padding:16px;
    border:1px solid #e5e7eb;
  }
  @media (max-width: 767.98px){
    .header-section.header-blue{ padding:18px; }
    .event-card{ padding:14px; }
  }
</style>