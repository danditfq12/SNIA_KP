<?php
// ====== DEFAULT VARS ======
$title           = $title ?? 'Detail Voucher';
$voucher         = $voucher ?? [];
$total_used      = (int)($total_used      ?? 0);     // penggunaan terverifikasi
$remaining       = (int)($remaining       ?? 0);
$total_discount  = (int)($total_discount  ?? 0);     // hanya yang verified
$usage_history   = $usage_history ?? [];             // riwayat detail (semua status)
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_admin') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <!-- HEADER -->
      <div class="header-section header-blue d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex align-items-center gap-3">
          <a href="<?= site_url('admin/voucher') ?>" class="btn btn-light btn-sm rounded-pill px-3">
            <i class="bi bi-arrow-left me-1"></i>Kembali
          </a>
          <div>
            <h3 class="welcome-text mb-1">
              <i class="bi bi-ticket-perforated me-2"></i>Detail Voucher
            </h3>
            <div class="text-white-50 small">
              Kode: <span class="badge bg-dark align-middle"><?= esc($voucher['kode_voucher'] ?? '-') ?></span>
            </div>
          </div>
        </div>
        <div class="text-end d-none d-md-block">
          <small class="text-white-50 d-block">Terakhir update</small>
          <strong><?= date('d M Y, H:i') ?></strong>
        </div>
      </div>

      <!-- KPI -->
      <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
          <div class="stat-card shadow-sm h-100">
            <div class="d-flex align-items-center">
              <div class="stat-icon bg-primary"><i class="bi bi-people"></i></div>
              <div class="ms-3">
                <div class="stat-number"><?= number_format((int)($voucher['kuota'] ?? 0)) ?></div>
                <div class="text-muted">Total Kuota</div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-6 col-xl-3">
          <div class="stat-card shadow-sm h-100">
            <div class="d-flex align-items-center">
              <div class="stat-icon bg-success"><i class="bi bi-check2-circle"></i></div>
              <div class="ms-3">
                <div class="stat-number"><?= number_format($total_used) ?></div>
                <div class="text-muted">Sudah Digunakan</div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-6 col-xl-3">
          <div class="stat-card shadow-sm h-100">
            <div class="d-flex align-items-center">
              <div class="stat-icon bg-warning"><i class="bi bi-hourglass-split"></i></div>
              <div class="ms-3">
                <div class="stat-number"><?= number_format($remaining) ?></div>
                <div class="text-muted">Sisa Kuota</div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-6 col-xl-3">
          <div class="stat-card shadow-sm h-100">
            <div class="d-flex align-items-center">
              <div class="stat-icon bg-info"><i class="bi bi-cash-coin"></i></div>
              <div class="ms-3">
                <div class="stat-number">Rp <?= number_format($total_discount, 0, ',', '.') ?></div>
                <div class="text-muted">Total Diskon (Verified)</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- DETAIL + PROGRESS -->
      <div class="row g-3 mb-3">
        <div class="col-lg-6">
          <div class="card shadow-sm h-100">
            <div class="card-header bg-primary text-white">
              <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Informasi Voucher</h6>
            </div>
            <div class="card-body">
              <div class="table-responsive">
                <table class="table table-borderless align-middle mb-0">
                  <tr>
                    <td class="text-muted" style="width:38%">Kode Voucher</td>
                    <td><span class="badge bg-dark"><?= esc($voucher['kode_voucher'] ?? '-') ?></span></td>
                  </tr>
                  <tr>
                    <td class="text-muted">Tipe Diskon</td>
                    <td>
                      <?php $isPct = ($voucher['tipe'] ?? '') === 'percentage'; ?>
                      <span class="badge bg-<?= $isPct ? 'info' : 'secondary' ?>">
                        <?= $isPct ? 'Persentase' : 'Fixed Amount' ?>
                      </span>
                    </td>
                  </tr>
                  <tr>
                    <td class="text-muted">Nilai Diskon</td>
                    <td class="fw-semibold">
                      <?php if ($isPct): ?>
                        <?= (int)($voucher['nilai'] ?? 0) ?>%
                      <?php else: ?>
                        Rp <?= number_format((int)($voucher['nilai'] ?? 0), 0, ',', '.') ?>
                      <?php endif; ?>
                    </td>
                  </tr>
                  <tr>
                    <td class="text-muted">Masa Berlaku</td>
                    <td><?= !empty($voucher['masa_berlaku']) ? date('d F Y', strtotime($voucher['masa_berlaku'])) : '-' ?></td>
                  </tr>
                  <tr>
                    <td class="text-muted">Status</td>
                    <td>
                      <?php
                        $status = strtolower($voucher['status'] ?? 'nonaktif');
                        $map = ['aktif'=>'success','nonaktif'=>'secondary','expired'=>'warning','habis'=>'danger'];
                        $cls = $map[$status] ?? 'secondary';
                      ?>
                      <span class="badge bg-<?= $cls ?>"><?= ucfirst($status) ?></span>
                    </td>
                  </tr>
                </table>
              </div>
            </div>
          </div>
        </div>

        <div class="col-lg-6">
          <div class="card shadow-sm h-100">
            <div class="card-header bg-success text-white">
              <h6 class="mb-0"><i class="bi bi-graph-up-arrow me-2"></i>Progress Penggunaan</h6>
            </div>
            <div class="card-body">
              <?php
                $kuota = max(0, (int)($voucher['kuota'] ?? 0));
                $pct   = $kuota > 0 ? round(($total_used / $kuota) * 100, 1) : 0;
              ?>
              <div class="mb-2 d-flex justify-content-between">
                <span class="text-muted">Penggunaan</span>
                <span><?= $total_used ?> / <?= $kuota ?></span>
              </div>
              <div class="progress" style="height:10px;">
                <div class="progress-bar bg-success" role="progressbar" style="width: <?= $pct ?>%;"></div>
              </div>
              <div class="row text-center mt-3">
                <div class="col-4">
                  <div class="fw-bold text-success"><?= $pct ?>%</div>
                  <small class="text-muted">Terpakai</small>
                </div>
                <div class="col-4">
                  <div class="fw-bold text-primary"><?= number_format($remaining) ?></div>
                  <small class="text-muted">Sisa</small>
                </div>
                <div class="col-4">
                  <div class="fw-bold text-info">Rp <?= number_format($total_discount, 0, ',', '.') ?></div>
                  <small class="text-muted">Total Diskon</small>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- RIWAYAT PENGGUNAAN -->
      <div class="card shadow-sm">
        <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
          <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Riwayat Penggunaan</h6>
          <span class="badge bg-light text-dark"><?= count($usage_history) ?> transaksi</span>
        </div>
        <div class="card-body">
          <?php if (empty($usage_history)): ?>
            <div class="p-4 text-center border rounded-3 bg-light-subtle">
              <div class="mb-2"><i class="bi bi-inbox fs-3 text-secondary"></i></div>
              <div class="fw-semibold">Belum Ada Penggunaan</div>
              <div class="text-muted small">Voucher ini belum pernah digunakan.</div>
            </div>
          <?php else: ?>
            <div class="table-responsive">
              <table id="usageTable" class="table table-hover align-middle">
                <thead>
                  <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>User</th>
                    <th>Event</th>
                    <th>Total Bayar</th>
                    <th>Diskon</th>
                    <th>Final</th>
                    <th>Status</th>
                  </tr>
                </thead>
                <tbody>
                  <?php $no = 1; foreach ($usage_history as $u): ?>
                    <tr>
                      <td><?= $no++ ?></td>
                      <td><?= !empty($u['tanggal_bayar']) ? date('d/m/Y H:i', strtotime($u['tanggal_bayar'])) : '-' ?></td>
                      <td>
                        <div class="fw-semibold"><?= esc($u['nama_lengkap'] ?? '-') ?></div>
                        <small class="text-muted"><?= esc($u['email'] ?? '-') ?></small>
                      </td>
                      <td><?= !empty($u['event_title']) ? esc($u['event_title']) : '<span class="text-muted">-</span>' ?></td>
                      <td>Rp <?= number_format((int)($u['jumlah'] ?? 0) + (int)($u['discount_amount'] ?? 0), 0, ',', '.') ?></td>
                      <td><span class="text-success">-Rp <?= number_format((int)($u['discount_amount'] ?? 0), 0, ',', '.') ?></span></td>
                      <td><strong>Rp <?= number_format((int)($u['jumlah'] ?? 0), 0, ',', '.') ?></strong></td>
                      <td>
                        <?php
                          $st = strtolower($u['status'] ?? 'pending');
                          $mapSt = ['verified'=>'success','pending'=>'warning','rejected'=>'danger'];
                          $clsSt = $mapSt[$st] ?? 'secondary';
                        ?>
                        <span class="badge bg-<?= $clsSt ?>"><?= ucfirst($st) ?></span>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<!-- (opsional) DataTables jika belum dimuat global -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.13.4/js/jquery.dataTables.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.13.4/js/dataTables.bootstrap5.min.js"></script>
<script>
  $(function(){
    $('#usageTable').DataTable({
      language:{ url:'https://cdn.datatables.net/plug-ins/1.13.4/i18n/id.json' },
      order:[[1,'desc']],
      pageLength:25,
      responsive:true
    });
  });
</script>

<style>
  :root{
    --primary-color:#2563eb; --info-color:#06b6d4; --success-color:#10b981; --warning-color:#f59e0b;
  }
  .header-section.header-blue{
    background: linear-gradient(135deg, var(--primary-color) 0%, #1e40af 100%);
    color:#fff; padding:28px 24px; border-radius:16px; box-shadow:0 8px 28px rgba(0,0,0,.12);
  }
  .welcome-text{ color:#fff; font-weight:800; font-size:1.6rem; }
  .stat-card{
    background:#fff; border-radius:14px; padding:20px; box-shadow:0 8px 28px rgba(0,0,0,.08);
    border-left:4px solid #e9ecef; position:relative; overflow:hidden;
  }
  .stat-card:before{
    content:''; position:absolute; left:0; top:0; height:4px; width:100%;
    background:linear-gradient(90deg,var(--primary-color),var(--info-color));
  }
  .stat-icon{ width:52px; height:52px; border-radius:12px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:22px; }
  .stat-number{ font-size:1.6rem; font-weight:800; color:#1e293b; line-height:1; }
</style>