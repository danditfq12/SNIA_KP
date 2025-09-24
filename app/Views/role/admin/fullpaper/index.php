<?php
$title               = $title ?? 'Kelola Full Paper';
$rows                = $rows  ?? [];
$events              = $events ?? [];
$filter              = $filter ?? ['status'=>null,'event_id'=>null];

$total_fullpaper     = (int)($total_fullpaper ?? count($rows));
$fullpaper_uploaded  = (int)($fullpaper_uploaded ?? 0);
$fullpaper_accepted  = (int)($fullpaper_accepted ?? 0);
$fullpaper_rejected  = (int)($fullpaper_rejected ?? 0);

$badgeMap = [
  'NONE'     => 'secondary',
  'UPLOADED' => 'info',
  'REVISION' => 'warning',
  'ACCEPTED' => 'success',
  'REJECTED' => 'danger',
];
$statusLabel = [
  'NONE'     => '—',
  'UPLOADED' => 'Diunggah',
  'REVISION' => 'Revisi',
  'ACCEPTED' => 'Diterima',
  'REJECTED' => 'Ditolak',
];
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_admin') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <div class="header-section header-blue d-flex justify-content-between align-items-center mb-3">
        <div>
          <h3 class="welcome-text mb-1">
            <i class="bi bi-journal-richtext me-2"></i><?= esc($title) ?>
          </h3>
          <div class="text-white-50">Kelola dan review semua full paper yang masuk</div>
        </div>
        <div class="text-end d-none d-md-block">
          <small class="text-white-50 d-block">Terakhir update</small>
          <strong class="text-white"><?= date('d M Y, H:i') ?></strong>
        </div>
      </div>

      <!-- Filter -->
      <form method="get" class="card shadow-sm mb-3 border-0">
        <div class="card-body row g-2 align-items-end">
          <div class="col-12 col-md-4">
            <label class="form-label">Status</label>
            <select name="status" class="form-select">
              <option value="">Semua</option>
              <?php foreach (['UPLOADED','REVISION','ACCEPTED','REJECTED'] as $st): ?>
                <option value="<?= $st ?>" <?= ($filter['status']??'')===$st?'selected':'' ?>>
                  <?= $statusLabel[$st] ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12 col-md-4">
            <label class="form-label">Event</label>
            <select name="event_id" class="form-select">
              <option value="">Semua Event</option>
              <?php foreach ($events as $e): ?>
                <option value="<?= (int)$e['id'] ?>" <?= (string)($filter['event_id']??'')===(string)$e['id']?'selected':'' ?>>
                  <?= esc($e['title']) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12 col-md-4 d-flex gap-2">
            <button class="btn btn-primary"><i class="bi bi-funnel"></i> Terapkan</button>
            <a class="btn btn-outline-secondary" href="<?= site_url('admin/fullpaper') ?>">
              <i class="bi bi-x-circle"></i> Reset
            </a>
          </div>
        </div>
      </form>

      <!-- KPI -->
      <div class="row g-3 mb-3">
        <div class="col-6 col-xl-3">
          <div class="stat-card shadow-sm h-100">
            <div class="d-flex align-items-center">
              <div class="stat-icon bg-primary"><i class="bi bi-journal-richtext"></i></div>
              <div class="ms-3">
                <div class="stat-number"><?= number_format($total_fullpaper) ?></div>
                <div class="text-muted">Total Full Paper</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-6 col-xl-3">
          <div class="stat-card shadow-sm h-100">
            <div class="d-flex align-items-center">
              <div class="stat-icon bg-info"><i class="bi bi-upload"></i></div>
              <div class="ms-3">
                <div class="stat-number"><?= number_format($fullpaper_uploaded) ?></div>
                <div class="text-muted">Telah Diupload / Proses</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-6 col-xl-3">
          <div class="stat-card shadow-sm h-100">
            <div class="d-flex align-items-center">
              <div class="stat-icon bg-success"><i class="bi bi-check2-circle"></i></div>
              <div class="ms-3">
                <div class="stat-number"><?= number_format($fullpaper_accepted) ?></div>
                <div class="text-muted">Diterima</div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-6 col-xl-3">
          <div class="stat-card shadow-sm h-100">
            <div class="d-flex align-items-center">
              <div class="stat-icon bg-danger"><i class="bi bi-x-circle"></i></div>
              <div class="ms-3">
                <div class="stat-number"><?= number_format($fullpaper_rejected) ?></div>
                <div class="text-muted">Ditolak</div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Tabel -->
      <div class="card shadow-sm border-0">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
          <h6 class="mb-0"><i class="bi bi-list me-2"></i>Daftar Full Paper</h6>
          <button class="btn btn-outline-secondary btn-sm" onclick="location.reload()">
            <i class="bi bi-arrow-clockwise me-1"></i>Refresh
          </button>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table class="table table-hover align-middle">
              <thead class="table-light">
                <tr>
                  <th>No</th>
                  <th>Judul</th>
                  <th>Penulis</th>
                  <th>Event</th>
                  <th>Status</th>
                  <th>Diunggah</th>
                  <th class="text-end">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($rows)): ?>
                  <?php $no = 1; foreach ($rows as $r): ?>
                    <?php
                      $id    = (int)($r['id'] ?? 0);
                      $judul = $r['title'] ?? '-';

                      // fallback nama/email selengkap mungkin
                      $penNm = $r['penulis_nama']
                              ?? $r['nama_lengkap']
                              ?? $r['presenter_name']
                              ?? $r['author_name']
                              ?? $r['nama']
                              ?? '-';
                      $penEm = $r['penulis_email']
                              ?? $r['email']
                              ?? $r['presenter_email']
                              ?? $r['author_email']
                              ?? '';

                      $evt   = $r['event_title'] ?? (isset($r['event_id']) ? ('#'.$r['event_id']) : '-');
                      $st    = strtoupper($r['full_paper_status'] ?? 'NONE');
                      $cls   = $badgeMap[$st] ?? 'secondary';
                      $ts    = $r['full_paper_uploaded_at'] ?? null;
                      $revKe = (int)($r['revisi_ke'] ?? 0);
                      $hasFile = !empty($r['full_paper_path']);

                      $detailUrl   = site_url('admin/fullpaper/detail/'.$id);
                      $downloadUrl = site_url('admin/fullpaper/download/'.$id);
                    ?>
                    <tr>
                      <td><?= $no++ ?></td>
                      <td>
                        <div class="fw-semibold"><?= esc($judul) ?></div>
                        <?php if ($revKe>0): ?><small class="text-muted">Revisi ke-<?= $revKe ?></small><?php endif; ?>
                      </td>
                      <td>
                        <div><?= esc($penNm) ?></div>
                        <?php if ($penEm): ?><small class="text-muted"><?= esc($penEm) ?></small><?php endif; ?>
                      </td>
                      <td>
                        <?php if (!empty($evt) && $evt!=='#-'): ?>
                          <span class="badge bg-secondary"><?= esc($evt) ?></span>
                        <?php else: ?>
                          <small class="text-muted">-</small>
                        <?php endif; ?>
                      </td>
                      <td><span class="badge bg-<?= esc($cls) ?>"><?= esc($statusLabel[$st] ?? $st) ?></span></td>
                      <td><?= $ts ? date('d/m/Y H:i', strtotime($ts)) : '—' ?></td>
                      <td class="text-end">
                        <div class="d-inline-flex gap-1">
                          <a class="btn btn-sm btn-primary" href="<?= $detailUrl ?>">
                            <i class="bi bi-eye me-1"></i> Detail
                          </a>
                          <?php if ($hasFile): ?>
                            <a class="btn btn-sm btn-success" href="<?= $downloadUrl ?>" target="_blank">
                              <i class="bi bi-download me-1"></i> Unduh
                            </a>
                          <?php else: ?>
                            <button class="btn btn-sm btn-outline-secondary" disabled title="Belum ada file" aria-disabled="true">
                              <i class="bi bi-download"></i>
                            </button>
                          <?php endif; ?>
                        </div>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr><td colspan="7" class="text-center py-5 text-muted">Belum ada data.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<style>
  :root{ --primary-color:#2563eb; --info-color:#06b6d4; --success-color:#10b981; }
  .header-section.header-blue{
    background:linear-gradient(135deg,var(--primary-color),#1e40af);
    color:#fff; padding:24px; border-radius:16px; box-shadow:0 8px 28px rgba(0,0,0,.12);
  }
  .stat-card{
    background:#fff; border-radius:14px; padding:20px; box-shadow:0 8px 28px rgba(0,0,0,.08);
    border-left:4px solid #e5e7eb; position:relative; overflow:hidden;
  }
  .stat-icon{
    width:56px; height:56px; border-radius:12px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:22px;
  }
  .stat-number{ font-size:1.8rem; font-weight:800; }
</style>