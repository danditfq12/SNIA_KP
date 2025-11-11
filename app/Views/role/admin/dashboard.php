<?php
// ===== Defaults & utilities =====
$title = $title ?? 'Admin Dashboard';
$kpi = $kpi ?? [
  'pembayaran_pending'       => 0,
  'abstrak_unassigned'       => 0,
  'fullpaper_unassigned'     => 0,
  'pembayaran_terverifikasi' => 0,
];
$pendingPayments       = $pendingPayments       ?? [];
$recentActivities      = $recentActivities      ?? [];
$logs                  = $logs                  ?? [];
$unassigned_abstrak    = $unassigned_abstrak    ?? [];
$unassigned_fullpaper  = $unassigned_fullpaper  ?? [];
$MAX_ITEMS = 5;

helper('url');

if (!function_exists('fmtDate'))  { function fmtDate($s){ return $s ? date('d M Y, H:i', strtotime($s)) : '-'; } }
if (!function_exists('money_id')) { function money_id($n){ return 'Rp ' . number_format((float)$n, 0, ',', '.'); } }

if (!function_exists('pillClass')) {
  function pillClass(string $t): string {
    return match ($t) {
      'primary' => 'pill-primary',
      'info'    => 'pill-info',
      'success' => 'pill-success',
      'warn'    => 'pill-warn',
      'danger'  => 'pill-danger',
      default   => 'pill-muted',
    };
  }
}

if (!function_exists('renderActivity')) {
  function renderActivity(array $it): string {
    $kind = $it['kind'] ?? '';
    $map = [
      'payment'   => ['bi-cash-coin',        'Pembayaran ' . strtoupper($it['status'] ?? '-'), ($it['status']??'')==='verified' ? 'success' : 'warn'],
      'user'      => ['bi-person-plus',      'Pendaftaran User', 'info'],
      'abstract'  => ['bi-journal-text',     'Abstrak Masuk', 'primary'],
      'fullpaper' => ['bi-journal-richtext', 'Full Paper Masuk', 'primary'],
    ];
    [$icon,$title,$tone] = $map[$kind] ?? ['bi-activity','Aktivitas','muted'];

    $right = '<span class="status-pill '.pillClass($tone).'">'.esc(fmtDate($it['happened_at'] ?? '')).'</span>';

    if ($kind==='payment') {
      $desc = esc($it['nama_lengkap'] ?? '-') . ' • ' . esc($it['event_title'] ?? 'Event') . ' • <strong>'.esc(money_id($it['jumlah'] ?? 0)).'</strong>';
    } elseif ($kind==='user') {
      $desc = esc(($it['nama_lengkap'] ?? '-') . ' (' . ($it['role'] ?? '-') . ')');
    } else {
      $desc = esc($it['judul'] ?? '-') . ' • oleh ' . esc($it['nama_lengkap'] ?? '-');
    }

    return <<<HTML
      <li class="mb-2">
        <div class="activity-item">
          <span class="status-pill pill-soft me-2 icon-pill"><i class="bi {$icon}"></i></span>
          <div class="flex-fill">
            <div class="d-flex justify-content-between align-items-center gap-2">
              <div class="fw-semibold small text-truncate">{$title}</div>
              {$right}
            </div>
            <div class="text-muted xsmall mt-1 text-truncate-2">{$desc}</div>
          </div>
        </div>
      </li>
    HTML;
  }
}

if (!function_exists('renderLog')) {
  function renderLog(array $l): string {
    $name = esc($l['nama_lengkap'] ?? 'Pengguna');
    $role = esc($l['role'] ?? '-');
    $time = esc(fmtDate($l['waktu'] ?? ''));
    $act  = esc($l['aktivitas'] ?? '-');
    return <<<HTML
      <li class="mb-2">
        <div class="activity-item">
          <span class="status-pill pill-soft me-2 icon-pill"><i class="bi bi-clock-history"></i></span>
          <div class="flex-fill">
            <div class="d-flex justify-content-between align-items-center">
              <div class="fw-semibold small text-truncate">{$name} <span class="text-muted">• {$role}</span></div>
              <span class="status-pill pill-muted">{$time}</span>
            </div>
            <div class="text-muted xsmall mt-1 text-truncate-2">{$act}</div>
          </div>
        </div>
      </li>
    HTML;
  }
}

if (!function_exists('renderAssign')) {
  function renderAssign(array $row, string $href, string $idKey): string {
    $judul = esc($row['judul'] ?? '-');
    $nama  = esc($row['nama_lengkap'] ?? '-');
    $tgl   = esc(fmtDate($row['created_at'] ?? ''));
    $id    = (int)($row[$idKey] ?? 0);
    $link  = site_url($href.'/'.$id);
    return <<<HTML
      <li class="mb-2">
        <div class="activity-item">
          <span class="status-pill pill-warn me-2 icon-pill"><i class="bi bi-exclamation-triangle"></i></span>
          <div class="flex-fill">
            <div class="d-flex justify-content-between align-items-center gap-2">
              <div class="fw-semibold small text-truncate" title="{$judul}">{$judul}</div>
              <span class="text-muted xsmall">{$tgl}</span>
            </div>
            <div class="text-muted xsmall mt-1">oleh {$nama}</div>
          </div>
          <div class="ms-2">
            <a class="btn btn-sm btn-outline-danger" href="{$link}">Tugaskan</a>
          </div>
        </div>
      </li>
    HTML;
  }
}
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_admin') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill page-wrap-blue">
    <div class="container-xxl px-3 px-md-4 py-4">

      <!-- HERO (wide banner) -->
      <div class="card-hero mb-3">
        <div class="hero-body">
          <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
              <h3 class="hero-title mb-1"><i class="bi bi-speedometer2 me-2"></i><?= esc($title) ?></h3>
              <div class="text-white-70 small">Ringkasan sistem & antrean pekerjaan admin.</div>
            </div>
            <div class="text-white-70 small text-end">
              <div>Server Time</div>
              <div class="fw-bold"><?= date('d M Y, H:i') ?></div>
            </div>
          </div>
        </div>
      </div>

      <!-- 4 KPI (row kecil) -->
      <div class="row g-3 mb-3">
        <div class="col-6 col-lg-3">
          <div class="kpi-card">
            <div class="kpi-ico ico-warn"><i class="bi bi-hourglass-split"></i></div>
            <div>
              <div class="kpi-num" data-countup="<?= (int)$kpi['pembayaran_pending'] ?>"><?= (int)$kpi['pembayaran_pending'] ?></div>
              <div class="kpi-label">Pembayaran Pending</div>
            </div>
          </div>
        </div>
        <div class="col-6 col-lg-3">
          <div class="kpi-card">
            <div class="kpi-ico ico-danger"><i class="bi bi-file-earmark-text"></i></div>
            <div>
              <div class="kpi-num" data-countup="<?= (int)$kpi['abstrak_unassigned'] ?>"><?= (int)$kpi['abstrak_unassigned'] ?></div>
              <div class="kpi-label">Abstrak Belum Ditugaskan</div>
            </div>
          </div>
        </div>
        <div class="col-6 col-lg-3">
          <div class="kpi-card">
            <div class="kpi-ico ico-danger"><i class="bi bi-file-earmark-richtext"></i></div>
            <div>
              <div class="kpi-num" data-countup="<?= (int)$kpi['fullpaper_unassigned'] ?>"><?= (int)$kpi['fullpaper_unassigned'] ?></div>
              <div class="kpi-label">Full Paper Belum Ditugaskan</div>
            </div>
          </div>
        </div>
        <div class="col-6 col-lg-3">
          <div class="kpi-card">
            <div class="kpi-ico ico-success"><i class="bi bi-patch-check"></i></div>
            <div>
              <div class="kpi-num" data-countup="<?= (int)$kpi['pembayaran_terverifikasi'] ?>"><?= (int)$kpi['pembayaran_terverifikasi'] ?></div>
              <div class="kpi-label">Pembayaran Terverifikasi</div>
            </div>
          </div>
        </div>
      </div>

      <!-- GRID KONTEN: kiri besar (Aktivitas), kanan 2 tumpuk (Log, Penugasan) -->
      <div class="row g-3 mb-3">
        <!-- Kiri besar -->
        <div class="col-12 col-xl-7">
          <section class="panel-card h-100">
            <div class="panel-head">
              <div class="title"><i class="bi bi-bell me-1 text-primary"></i>Aktivitas Terbaru</div>
            </div>
            <div class="panel-body">
              <?php if (empty($recentActivities)): ?>
                <div class="empty-hint text-center"><i class="bi bi-inboxes me-1"></i>Belum ada aktivitas.</div>
              <?php else: ?>
                <ul class="list-unstyled mb-0">
                  <?php $i=0; foreach ($recentActivities as $a){ if ($i++ >= $MAX_ITEMS) break; echo renderActivity($a); } ?>
                </ul>
              <?php endif; ?>
            </div>
          </section>
        </div>

        <!-- Kanan atas: log -->
        <div class="col-12 col-xl-5">
          <section class="panel-card h-100">
            <div class="panel-head">
              <div class="title"><i class="bi bi-list-check me-1 text-secondary"></i>Log Aktivitas</div>
              <div class="d-flex gap-2">
                <a href="<?= site_url('admin/laporan/export') ?>" class="btn btn-xxs btn-outline-primary">Export</a>
              </div>
            </div>
            <div class="panel-body">
              <?php if (empty($logs)): ?>
                <div class="empty-hint text-center"><i class="bi bi-clock me-1"></i>Belum ada log aktivitas.</div>
              <?php else: ?>
                <ul class="list-unstyled mb-0">
                  <?php $i=0; foreach ($logs as $l){ if ($i++ >= $MAX_ITEMS) break; echo renderLog($l); } ?>
                </ul>
              <?php endif; ?>
            </div>
          </section>
        </div>

        <!-- Kanan bawah: penugasan -->
        <div class="col-12 col-xl-5">
          <section class="panel-card h-100">
            <div class="panel-head">
              <div class="title"><i class="bi bi-person-gear me-1 text-danger"></i>Perlu Penugasan</div>
            </div>
            <div class="panel-body">
              <ul class="nav nav-pills pills-compact mb-3" id="assignTabs" role="tablist">
                <li class="nav-item"><button class="nav-link active" data-target="#pane-abs" type="button">Abstrak</button></li>
                <li class="nav-item"><button class="nav-link" data-target="#pane-fp" type="button">Full Paper</button></li>
              </ul>

              <div id="pane-abs" class="assign-pane" role="tabpanel">
                <?php if (empty($unassigned_abstrak)): ?>
                  <div class="empty-hint text-center"><i class="bi bi-check2-all me-1"></i>Semua abstrak sudah ditugaskan.</div>
                <?php else: ?>
                  <ul class="list-unstyled mb-0">
                    <?php $i=0; foreach ($unassigned_abstrak as $a){ if ($i++ >= $MAX_ITEMS) break; echo renderAssign($a, 'admin/abstrak/detail', 'id_abstrak'); } ?>
                  </ul>
                <?php endif; ?>
              </div>

              <div id="pane-fp" class="assign-pane d-none" role="tabpanel">
                <?php if (empty($unassigned_fullpaper)): ?>
                  <div class="empty-hint text-center"><i class="bi bi-check2-all me-1"></i>Semua full paper sudah ditugaskan.</div>
                <?php else: ?>
                  <ul class="list-unstyled mb-0">
                    <?php $i=0; foreach ($unassigned_fullpaper as $f){ if ($i++ >= $MAX_ITEMS) break; echo renderAssign($f, 'admin/fullpaper/detail', 'id_fullpaper'); } ?>
                  </ul>
                <?php endif; ?>
              </div>
            </div>
          </section>
        </div>
      </div>

      <!-- Footer wide: Pembayaran Pending -->
      <section class="panel-card">
        <div class="panel-head">
          <div class="title"><i class="bi bi-cash-coin me-1 text-warning"></i>Pembayaran Pending</div>
          <a href="<?= site_url('admin/pembayaran') ?>" class="btn btn-xxs btn-outline-warning">Kelola</a>
        </div>
        <div class="panel-body">
          <?php if (empty($pendingPayments)): ?>
            <div class="empty-box text-center">Tidak ada pembayaran yang menunggu.</div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table table-sm align-middle mb-0">
                <thead class="table-light">
                  <tr>
                    <th>Nama</th>
                    <th class="d-none d-md-table-cell">Event</th>
                    <th class="text-end">Jumlah</th>
                    <th class="text-nowrap d-none d-lg-table-cell">Tanggal</th>
                    <th></th>
                  </tr>
                </thead>
                <tbody>
                  <?php $i=0; foreach ($pendingPayments as $p): if ($i++ >= $MAX_ITEMS) break; ?>
                    <tr>
                      <td>
                        <div class="fw-semibold"><?= esc($p['nama_lengkap'] ?? '-') ?></div>
                        <div class="small text-muted d-md-none"><?= esc($p['event_title'] ?? 'Event') ?></div>
                      </td>
                      <td class="d-none d-md-table-cell text-truncate" style="max-width:180px;"><?= esc($p['event_title'] ?? 'Event') ?></td>
                      <td class="text-end"><span class="status-pill pill-warn"><?= esc(money_id($p['jumlah'] ?? 0)) ?></span></td>
                      <td class="text-nowrap d-none d-lg-table-cell"><small class="text-muted"><?= esc(fmtDate($p['tanggal_bayar'] ?? null)) ?></small></td>
                      <td class="text-end"><a href="<?= site_url('admin/pembayaran/detail/'.(int)($p['id_pembayaran'] ?? 0)) ?>" class="btn btn-xxs btn-outline-primary">Detail</a></td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </section>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<style>
:root{
  --blue-700:#1d4ed8; --blue-800:#1e40af;
  --ink:#0f172a; --muted:#64748b;
  --radius:18px; --side-pad:clamp(1rem,2.3vw,2.2rem);
}
.container-xxl{ max-width:min(100%,1560px); padding-inline:var(--side-pad)!important; margin-inline:auto; }
.page-wrap-blue{ min-height:100vh; padding-top:72px; background:linear-gradient(180deg,#f5f7ff 0%,#fff 40%); }

/* HERO */
.card-hero{ border:0; border-radius:var(--radius); overflow:hidden; box-shadow:0 10px 26px rgba(30,64,175,.18); }
.card-hero .hero-body{ background:linear-gradient(135deg,var(--blue-700),var(--blue-800)); color:#fff; padding:1.1rem .95rem; }
.hero-title{ font-weight:900; letter-spacing:.2px; margin-bottom:0; font-size:1.25rem; }
.text-white-70{ color:rgba(255,255,255,.92)!important; }

/* KPI */
.kpi-card{ background:#fff; border:1px solid #eef1f6; border-radius:14px; padding:12px 14px; display:flex; align-items:center; gap:10px; box-shadow:0 6px 14px rgba(2,6,23,.06); }
.kpi-ico{ width:44px;height:44px;border-radius:10px;display:flex;align-items:center;justify-content:center;color:#fff;font-size:18px; }
.ico-warn{ background:#f59e0b; } .ico-danger{ background:#ef4444; } .ico-success{ background:#10b981; }
.kpi-num{ font-weight:900; font-size:22px; line-height:1; color:#0f172a; }
.kpi-label{ color:#475569; font-weight:700; letter-spacing:.15px; font-size:.82rem; }

/* CARD PANEL */
.panel-card{ background:#fff; border:1px solid #eef1f6; border-radius:14px; box-shadow:0 8px 20px rgba(2,6,23,.06); }
.panel-head{ display:flex; align-items:center; justify-content:space-between; padding:10px 12px; border-bottom:1px solid #f0f2f7; }
.panel-head .title{ font-weight:800; color:#0f172a; font-size:1rem; }
.panel-body{ padding:10px 12px; }
.btn-xxs{ padding:.25rem .5rem; font-size:.75rem; border-radius:6px; }

/* ITEMS */
.activity-item{ display:flex; gap:.6rem; align-items:flex-start; border:1px solid #eef1f6; border-radius:12px; padding:.55rem .6rem; background:#fff; box-shadow:0 3px 8px rgba(2,6,23,.04); }
.icon-pill{ min-width:30px; text-align:center; }

.status-pill{ font-weight:800; font-size:.78rem; padding:.22rem .55rem; border-radius:999px; border:1px solid transparent; white-space:nowrap; }
.pill-soft{ background:#eef2ff; color:#4338ca; border-color:#e6e8ff; }
.pill-primary{ background:#e0ecff; color:#1d4ed8; border-color:#d3e2ff; }
.pill-info{ background:#e8f1ff; color:#194bcb; border-color:#dbe7ff; }
.pill-success{ background:#d1fae5; color:#065f46; border-color:#a7f3d0; }
.pill-warn{ background:#fef3c7; color:#92400e; border-color:#fde68a; }
.pill-danger{ background:#fee2e2; color:#991b1b; border-color:#fecaca; }
.pill-muted{ background:#f3f4f6; color:#374151; border-color:#e5e7eb; }

.table td,.table th{ vertical-align:middle; }
.empty-hint{ color:#567; background:#f6f9ff; border:1px dashed #e3e9ff; border-radius:10px; padding:.7rem .8rem; font-weight:600; }
.empty-box{ border:1px dashed #e5e7eb; border-radius:10px; padding:14px; color:#6b7280; background:#fafafa; }

/* Tabs kecil */
.pills-compact .nav-link{
  font-size:.78rem; padding:.28rem .6rem; border-radius:999px; font-weight:700;
}
.pills-compact .nav-link.active{ background:#2563eb; color:#fff; }

/* multi-line clamp */
.text-truncate-2{ overflow:hidden; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; }

/* Responsive tweaks */
@media (max-width:767.98px){
  .hero-title{ font-size:1.05rem; }
  .kpi-num{ font-size:20px; }
  .panel-head .title{ font-size:.95rem; }
}
</style>

<script>
(function(){
  // Count-up KPI (respect reduced motion)
  const pr = matchMedia('(prefers-reduced-motion: reduce)').matches;
  document.querySelectorAll('[data-countup]').forEach(el=>{
    const target = parseInt(el.dataset.countup||'0',10);
    if (pr || isNaN(target)) { el.textContent = new Intl.NumberFormat('id-ID').format(target); return; }
    let now=0, step=Math.max(1, Math.ceil(target/40));
    const tick=()=>{ now=Math.min(now+step,target);
      el.textContent=new Intl.NumberFormat('id-ID').format(now);
      if(now<target) requestAnimationFrame(tick);
    }; requestAnimationFrame(tick);
  });

  // Tabs penugasan (tanpa Bootstrap JS)
  const tabs=document.querySelectorAll('#assignTabs .nav-link');
  const panes=document.querySelectorAll('.assign-pane');
  tabs.forEach(btn=>{
    btn.addEventListener('click',()=>{
      tabs.forEach(b=>b.classList.remove('active')); btn.classList.add('active');
      panes.forEach(p=>p.classList.add('d-none'));
      const target=document.querySelector(btn.dataset.target); if(target) target.classList.remove('d-none');
    });
  });
})();
</script>
