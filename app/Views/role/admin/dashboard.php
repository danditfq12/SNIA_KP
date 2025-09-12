<?php
// ====== DEFAULT VARS (hindari notice) ======
$title               = $title ?? 'Admin Dashboard';
$pembayaran_pending  = (int)($pembayaran_pending ?? 0); // KPI
$abstrak_masuk       = (int)($abstrak_masuk ?? 0);      // KPI
$abstrak_unassigned  = (int)($abstrak_unassigned ?? 0); // KPI
$total_event         = (int)($total_event ?? 0);        // KPI

// list ringkasan/terbaru (opsional)
$pendingPayments = $pendingPayments ?? []; // id_pembayaran,nama_lengkap,event_title,jumlah,tanggal_bayar
$recent_abstrak  = $recent_abstrak  ?? []; // judul,nama_lengkap,status,created_at
$unassigned_list = $unassigned_list ?? []; // judul,nama_lengkap,created_at
$recent_events   = $recent_events   ?? []; // title,event_date,event_time,format,is_active

helper(['number','form']);
$fmtDate = fn($s)=> $s ? date('d M Y', strtotime($s)) : '-';
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_admin') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <!-- HEADER (seragam: header-blue) -->
      <div class="header-section header-blue d-flex justify-content-between align-items-center mb-3">
        <div>
          <h3 class="welcome-text mb-1">
            <i class="bi bi-speedometer2 me-2"></i>Dashboard Admin
          </h3>
          <div class="text-white-50">Ringkasan status sistem SNIA</div>
        </div>
        <div class="text-end d-none d-md-block">
          <small class="text-white-50 d-block">Terakhir login</small>
          <strong class="text-white"><?= date('d M Y, H:i') ?></strong>
        </div>
      </div>

      <!-- KPI 4 KOTAK -->
      <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
          <div class="stat-card shadow-sm h-100">
            <div class="d-flex align-items-center">
              <div class="stat-icon bg-warning"><i class="bi bi-cash-coin"></i></div>
              <div class="ms-3">
                <div class="stat-number" data-num="<?= $pembayaran_pending ?>"><?= number_format($pembayaran_pending) ?></div>
                <div class="text-muted">Pembayaran perlu ACC</div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-6 col-xl-3">
          <div class="stat-card shadow-sm h-100">
            <div class="d-flex align-items-center">
              <div class="stat-icon bg-primary"><i class="bi bi-file-earmark-text"></i></div>
              <div class="ms-3">
                <div class="stat-number" data-num="<?= $abstrak_masuk ?>"><?= number_format($abstrak_masuk) ?></div>
                <div class="text-muted">Abstrak masuk</div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-6 col-xl-3">
          <div class="stat-card shadow-sm h-100">
            <div class="d-flex align-items-center">
              <div class="stat-icon bg-danger"><i class="bi bi-person-gear"></i></div>
              <div class="ms-3">
                <div class="stat-number" data-num="<?= $abstrak_unassigned ?>"><?= number_format($abstrak_unassigned) ?></div>
                <div class="text-muted">Belum ditugaskan</div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-6 col-xl-3">
          <div class="stat-card shadow-sm h-100">
            <div class="d-flex align-items-center">
              <div class="stat-icon bg-success"><i class="bi bi-calendar2-event"></i></div>
              <div class="ms-3">
                <div class="stat-number" data-num="<?= $total_event ?>"><?= number_format($total_event) ?></div>
                <div class="text-muted">Total event dibuat</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- ROW 1: Pembayaran pending + Abstrak masuk -->
      <div class="row g-3 mb-3">
        <!-- Pembayaran pending -->
        <div class="col-12 col-xl-6">
          <div class="card shadow-sm h-100">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="card-title mb-0"><i class="bi bi-cash-coin me-2 text-warning"></i>Pembayaran menunggu verifikasi</h5>
                <a href="<?= site_url('admin/pembayaran') ?>" class="small">Kelola</a>
              </div>
              <?php if (!empty($pendingPayments)): ?>
                <div class="table-responsive">
                  <table class="table table-sm align-middle">
                    <thead class="table-light">
                      <tr>
                        <th>Nama</th>
                        <th>Event</th>
                        <th class="text-end">Jumlah</th>
                        <th class="text-nowrap">Tanggal</th>
                        <th></th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php foreach ($pendingPayments as $p): ?>
                        <tr>
                          <td><?= esc($p['nama_lengkap'] ?? '-') ?></td>
                          <td><?= esc($p['event_title'] ?? 'Event') ?></td>
                          <td class="text-end">Rp <?= number_format((float)($p['jumlah'] ?? 0), 0, ',', '.') ?></td>
                          <td class="text-nowrap"><?= esc($fmtDate($p['tanggal_bayar'] ?? null)) ?></td>
                          <td class="text-end">
                            <a href="<?= site_url('admin/pembayaran/detail/'.(int)($p['id_pembayaran'] ?? 0)) ?>" class="btn btn-sm btn-outline-primary">Detail</a>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php else: ?>
                <div class="p-4 text-center border rounded-3 bg-light-subtle">
                  <div class="mb-2"><i class="bi bi-wallet2 fs-3 text-secondary"></i></div>
                  <div class="fw-semibold">Tidak ada pembayaran pending</div>
                  <div class="text-muted small">Semua pembayaran telah diverifikasi.</div>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- Abstrak masuk terbaru -->
        <div class="col-12 col-xl-6">
          <div class="card shadow-sm h-100">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="card-title mb-0"><i class="bi bi-file-earmark-text me-2 text-primary"></i>Abstrak masuk terbaru</h5>
                <a href="<?= site_url('admin/abstrak') ?>" class="small">Kelola</a>
              </div>

              <div class="vstack gap-2 activities-scroll">
                <?php if (!empty($recent_abstrak)): ?>
                  <?php foreach ($recent_abstrak as $ab):
                    $st  = strtolower($ab['status'] ?? 'menunggu');
                    $cls = $st==='menunggu'?'bg-warning text-dark':($st==='diterima'?'bg-success':($st==='ditolak'?'bg-danger':'bg-secondary'));
                  ?>
                    <div class="notice">
                      <div class="d-flex align-items-start gap-2">
                        <i class="bi bi-journal-text text-primary mt-1"></i>
                        <div class="flex-fill">
                          <div class="title"><?= esc(mb_strimwidth($ab['judul'] ?? '-', 0, 70, '...')) ?></div>
                          <div class="meta">oleh <?= esc($ab['nama_lengkap'] ?? '-') ?> · <?= esc($fmtDate($ab['created_at'] ?? null)) ?></div>
                        </div>
                        <span class="badge <?= $cls ?>"><?= ucfirst($st) ?></span>
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <div class="p-4 text-center border rounded-3 bg-light-subtle">
                    <div class="mb-2"><i class="bi bi-inbox fs-3 text-secondary"></i></div>
                    <div class="fw-semibold">Belum ada abstrak</div>
                    <div class="text-muted small">Abstrak terbaru akan tampil di sini.</div>
                  </div>
                <?php endif; ?>
              </div>

            </div>
          </div>
        </div>
      </div>

      <!-- ROW 2: Abstrak belum ditugaskan + Event terbaru -->
      <div class="row g-3">
        <!-- Unassigned abstrak -->
        <div class="col-12 col-xl-6">
          <div class="card shadow-sm h-100">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="card-title mb-0"><i class="bi bi-person-gear me-2 text-danger"></i>Abstrak belum ditugaskan ke reviewer</h5>
                <a href="<?= site_url('admin/reviewer') ?>" class="small">Tugaskan</a>
              </div>

              <div class="vstack gap-2 activities-scroll">
                <?php if (!empty($unassigned_list)): ?>
                  <?php foreach ($unassigned_list as $ua): ?>
                    <div class="notice">
                      <div class="d-flex align-items-start gap-2">
                        <i class="bi bi-exclamation-triangle text-danger mt-1"></i>
                        <div class="flex-fill">
                          <div class="title"><?= esc(mb_strimwidth($ua['judul'] ?? '-', 0, 70, '...')) ?></div>
                          <div class="meta">oleh <?= esc($ua['nama_lengkap'] ?? '-') ?> · <?= esc($fmtDate($ua['created_at'] ?? null)) ?></div>
                        </div>
                        <a href="<?= site_url('admin/reviewer') ?>" class="btn btn-sm btn-outline-danger">Tugaskan</a>
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <div class="p-4 text-center border rounded-3 bg-light-subtle">
                    <div class="mb-2"><i class="bi bi-check2-circle fs-3 text-success"></i></div>
                    <div class="fw-semibold">Semua abstrak sudah ditugaskan</div>
                    <div class="text-muted small">Tidak ada antrian penugasan reviewer.</div>
                  </div>
                <?php endif; ?>
              </div>

            </div>
          </div>
        </div>

        <!-- Event terbaru -->
        <div class="col-12 col-xl-6">
          <div class="card shadow-sm h-100">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-2">
                <h5 class="card-title mb-0"><i class="bi bi-calendar-event me-2 text-success"></i>Event terbaru</h5>
                <a href="<?= site_url('admin/event') ?>" class="small">Kelola</a>
              </div>

              <div class="vstack gap-2 activities-scroll">
                <?php if (!empty($recent_events)): ?>
                  <?php foreach ($recent_events as $ev): ?>
                    <div class="notice">
                      <div class="d-flex align-items-start gap-2">
                        <i class="bi bi-calendar3 text-success mt-1"></i>
                        <div class="flex-fill">
                          <div class="title"><?= esc(mb_strimwidth($ev['title'] ?? 'Event', 0, 70, '...')) ?></div>
                          <div class="meta">
                            <?= esc($fmtDate($ev['event_date'] ?? null)) ?> ·
                            <?= esc($ev['event_time'] ?? '-') ?> ·
                            <?= esc(ucfirst($ev['format'] ?? '-')) ?>
                          </div>
                        </div>
                        <span class="badge <?= !empty($ev['is_active']) ? 'bg-success':'bg-secondary' ?>">
                          <?= !empty($ev['is_active']) ? 'Aktif' : 'Nonaktif' ?>
                        </span>
                      </div>
                    </div>
                  <?php endforeach; ?>
                <?php else: ?>
                  <div class="p-4 text-center border rounded-3 bg-light-subtle">
                    <div class="mb-2"><i class="bi bi-calendar-x fs-3 text-secondary"></i></div>
                    <div class="fw-semibold">Belum ada event</div>
                    <div class="text-muted small">Buat event baru di menu Kelola Event.</div>
                  </div>
                <?php endif; ?>
              </div>

            </div>
          </div>
        </div>
      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<!-- ====== STYLES (seragam dengan Voucher/Dokumen) ====== -->
<style>
  :root{
    --primary-color:#2563eb; --success-color:#10b981; --warning-color:#f59e0b; --danger-color:#ef4444; --info-color:#06b6d4;
  }
  body{ background:linear-gradient(135deg,#f8fafc 0%,#e2e8f0 100%); font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; }

  .header-section.header-blue{
    background: linear-gradient(135deg, var(--primary-color) 0%, #1e40af 100%);
    color:#fff; padding:28px 24px; border-radius:16px; box-shadow:0 8px 28px rgba(0,0,0,.12);
  }
  .header-section .welcome-text{ color:#fff; font-weight:800; font-size:2rem; }

  .stat-card{
    background:#fff; border-radius:14px; padding:20px; box-shadow:0 8px 28px rgba(0,0,0,.08);
    border-left:4px solid #e9ecef; position:relative; overflow:hidden;
  }
  .stat-card:before{
    content:''; position:absolute; left:0; top:0; height:4px; width:100%;
    background:linear-gradient(90deg,var(--primary-color),var(--info-color));
  }
  .stat-icon{ width:56px; height:56px; border-radius:12px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:22px; }
  .stat-number{ font-size:2rem; font-weight:800; color:#1e293b; line-height:1; }

  .activities-scroll{ max-height: 320px; overflow:auto; padding-right: 6px; }
  .activities-scroll::-webkit-scrollbar{ width:8px; }
  .activities-scroll::-webkit-scrollbar-thumb{ background:#ccd6e0; border-radius:8px; }

  .notice{ border:1px solid #eef2f6; border-radius:12px; padding:12px; background:#fff; transition:.15s ease; }
  .notice:hover{ box-shadow:0 8px 18px rgba(0,0,0,.06); }
  .notice .title{ font-weight:600; }
  .notice .meta{ font-size:.85rem; color:#e5e7eb; color:#6c757d; }

  /* jarak aman di bawah header global */
  #content main>.container-fluid{ margin-top:.25rem; }
</style>

<!-- ====== SCRIPTS (animasi angka KPI) ====== -->
<script>
  (function(){
    const els = document.querySelectorAll('.stat-number');
    els.forEach(el=>{
      const target = parseInt(el.getAttribute('data-num')||'0',10);
      let cur = 0; const step = Math.max(1, Math.round(target/40));
      const id = setInterval(()=>{
        cur += step;
        if(cur >= target){ cur = target; clearInterval(id); }
        el.textContent = new Intl.NumberFormat('id-ID').format(cur);
      }, 18);
    });
  })();
</script>
