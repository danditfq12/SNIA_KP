<?php
$title           = $title ?? 'Abstrak';
$availableEvents = $availableEvents ?? [];
$aktif           = $aktif ?? [];
$riwayat         = $riwayat ?? [];
$kpi             = $kpi ?? ['total'=>0,'menunggu'=>0,'sedang_direview'=>0,'revisi'=>0,'diterima'=>0,'ditolak'=>0];
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <!-- HEADER -->
      <div class="header-section header-blue d-flex justify-content-between align-items-center mb-3">
        <div>
          <h2 class="welcome-text mb-1"><i class="bi bi-file-earmark-text"></i> Abstrak</h2>
          <div class="text-white-50">Kirim, pantau status, dan riwayat abstrak Anda</div>
        </div>
      </div>

      <!-- KPI -->
      <div class="row g-3 mb-3">
        <?php
          $box = [
            ['Total', $kpi['total'] ?? 0, 'bi-collection'],
            ['Menunggu', $kpi['menunggu'] ?? 0, 'bi-hourglass-split'],
            ['Sedang direview', $kpi['sedang_direview'] ?? 0, 'bi-search'],
            ['Revisi', $kpi['revisi'] ?? 0, 'bi-arrow-counterclockwise'],
            ['Diterima', $kpi['diterima'] ?? 0, 'bi-check2-circle'],
            ['Ditolak', $kpi['ditolak'] ?? 0, 'bi-x-circle'],
          ];
        ?>
        <?php foreach ($box as [$label,$val,$icon]): ?>
          <div class="col-6 col-md-4 col-xl-2">
            <div class="stat-card shadow-sm h-100">
              <div class="d-flex align-items-center">
                <div class="stat-icon bg-primary"><i class="bi <?= $icon ?>"></i></div>
                <div class="ms-3">
                  <div class="stat-number"><?= number_format((int)$val) ?></div>
                  <div class="text-muted small"><?= esc($label) ?></div>
                </div>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>

      <!-- EVENT TERSEDIA UNTUK UPLOAD -->
      <div class="card shadow-sm mb-4">
        <div class="card-header bg-gradient-primary text-white">
          <strong><i class="bi bi-calendar-plus me-1"></i> Event tersedia untuk upload abstrak</strong>
        </div>
        <div class="card-body">
          <?php if (empty($availableEvents)): ?>
            <div class="text-muted">Tidak ada event yang membuka upload abstrak, atau Anda sudah mengirim abstrak aktif.</div>
          <?php else: ?>
            <div class="row g-3">
              <?php foreach ($availableEvents as $e): ?>
              <div class="col-12 col-md-6 col-xl-4">
                <div class="event-card">
                  <div class="d-flex justify-content-between align-items-start">
                    <h6 class="mb-1"><?= esc($e['title']) ?></h6>
                    <span class="badge bg-success">Open</span>
                  </div>
                  <div class="small text-muted mb-2">
                    Tgl Event: <?= date('d M Y', strtotime($e['event_date'])) ?><br>
                    Deadline Abstrak: <?= !empty($e['abstract_deadline']) ? date('d M Y', strtotime($e['abstract_deadline'])) : '-' ?>
                  </div>
                  <a class="btn btn-primary btn-sm w-100"
                     href="/presenter/abstrak/create/<?= (int)$e['id'] ?>">
                    <i class="bi bi-upload"></i> Upload Abstrak
                  </a>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- TABEL ABSTRAK AKTIF -->
      <div class="card shadow-sm mb-4">
        <div class="card-header bg-light">
          <strong><i class="bi bi-activity me-1"></i> Sedang Berjalan</strong>
          <div class="small text-muted">Abstrak yang sedang menunggu, direview, atau butuh revisi</div>
        </div>
        <div class="card-body p-0">
          <?php if (empty($aktif)): ?>
            <div class="p-3 text-muted">Belum ada abstrak aktif.</div>
          <?php else: ?>
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th>Event</th>
                  <th>Judul</th>
                  <th>Kategori</th>
                  <th>Status</th>
                  <th>Upload</th>
                  <th style="min-width:160px;">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($aktif as $a):
                  $st = strtolower($a['status']);
                  $badge = [
                    'menunggu'        => 'secondary',
                    'sedang_direview' => 'warning',
                    'revisi'          => 'info',
                  ][$st] ?? 'secondary';
                ?>
                <tr>
                  <td><?= esc($a['event_title'] ?? '-') ?></td>
                  <td class="text-break"><?= esc($a['judul'] ?? '-') ?></td>
                  <td><?= esc($a['nama_kategori'] ?? '-') ?></td>
                  <td><span class="badge bg-<?= $badge ?>"><?= strtoupper($a['status']) ?></span></td>
                  <td><?= !empty($a['tanggal_upload']) ? date('d M Y H:i', strtotime($a['tanggal_upload'])) : '-' ?></td>
                  <td class="d-flex flex-wrap gap-2">
                    <a href="/presenter/abstrak/detail/<?= (int)$a['id_abstrak'] ?>" class="btn btn-outline-primary btn-sm">
                      <i class="bi bi-eye"></i> Detail
                    </a>
                    <?php if ($st === 'revisi'): ?>
                      <a href="/presenter/abstrak/detail/<?= (int)$a['id_abstrak'] ?>" class="btn btn-warning btn-sm">
                        <i class="bi bi-upload"></i> Upload Revisi
                      </a>
                    <?php endif; ?>
                    <a href="/presenter/abstrak/download/<?= esc($a['file_abstrak'], 'attr') ?>" class="btn btn-outline-secondary btn-sm">
                      <i class="bi bi-download"></i> File
                    </a>
                  </td>
                </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- RIWAYAT (DITERIMA/DITOLAK) -->
      <div class="card shadow-sm">
        <div class="card-header bg-light">
          <strong><i class="bi bi-journal-text me-1"></i> Riwayat</strong>
          <div class="small text-muted">Masuk ke riwayat jika abstrak sudah DITERIMA atau DITOLAK</div>
        </div>
        <div class="card-body p-0">
          <?php if (empty($riwayat)): ?>
            <div class="p-3 text-muted">Belum ada riwayat.</div>
          <?php else: ?>
          <ul class="list-group list-group-flush">
            <?php foreach ($riwayat as $r):
              $st = strtolower($r['status']);
              $badge = $st==='diterima' ? 'success' : 'danger';
            ?>
            <li class="list-group-item d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-2">
              <div>
                <div class="fw-semibold"><?= esc($r['event_title'] ?? '-') ?></div>
                <div class="small text-muted text-break"><?= esc($r['judul'] ?? '-') ?></div>
              </div>
              <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="badge bg-<?= $badge ?>"><?= strtoupper($r['status']) ?></span>
                <a href="/presenter/abstrak/detail/<?= (int)$r['id_abstrak'] ?>" class="btn btn-outline-primary btn-sm">
                  <i class="bi bi-eye"></i> Detail
                </a>
                <a href="/presenter/abstrak/download/<?= esc($r['file_abstrak'], 'attr') ?>" class="btn btn-outline-secondary btn-sm">
                  <i class="bi bi-download"></i> File
                </a>
              </div>
            </li>
            <?php endforeach; ?>
          </ul>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<style>
  :root{
    --primary:#2563eb; --primary-deep:#1e40af;
    --muted:#64748b;
  }
  .header-section.header-blue{
    background:linear-gradient(135deg,var(--primary),var(--primary-deep));
    color:#fff; padding:20px; border-radius:16px; box-shadow:0 8px 28px rgba(0,0,0,.12);
  }
  .welcome-text{ font-weight:500; font-size:1.25rem; }
  .stat-card{ background:#fff; border-radius:14px; padding:16px; border-left:4px solid #e9ecef; }
  .stat-icon{ width:44px; height:44px; border-radius:10px; display:flex; align-items:center; justify-content:center; color:#fff; font-size:18px; }
  .stat-number{ font-size:1.4rem; font-weight:600; color:#0f172a; }
  .event-card{ background:#fff; border:1px solid #eef2f7; border-radius:14px; padding:14px; box-shadow:0 4px 14px rgba(15,23,42,.06); height:100%; }
  .card{ border-radius:14px; }
  .btn{ border-radius:10px; }
</style>