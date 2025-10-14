<?php
$title = $title ?? 'Riwayat Review';
$riwayatAbstrak    = $riwayatAbstrak ?? [];
$riwayatFullpaper  = $riwayatFullpaper ?? [];

$badge = function($k){
  $s = strtolower((string)$k);
  return match(true){
    in_array($s,['diterima']) => 'bg-success',
    in_array($s,['revisi'])   => 'bg-warning text-dark',
    in_array($s,['ditolak'])  => 'bg-danger',
    default                   => 'bg-secondary'
  };
};
$fmtDate = fn($d)=> $d? date('d M Y', strtotime($d)):'-';
$fmtDT   = fn($d)=> $d? date('d M Y H:i', strtotime($d)):'-';
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_reviewer') ?>

<div id="content">
  <main class="flex-fill page-wrap-blue">
    <div class="container-xxl px-3 px-md-4 py-4">

      <!-- HERO (selaras halaman Tugas Abstrak/Full Paper) -->
      <div class="card-hero mb-3">
        <div class="hero-body">
          <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
              <h3 class="hero-title mb-1">
                <i class="bi bi-clock-history me-2"></i>Riwayat Review
              </h3>
              <div class="text-white-70 small">Riwayat Abstrak & Full Paper — dedup per presenter per event.</div>
            </div>
            <div class="hero-tools flex-grow-1" style="max-width:620px;">
              <div class="input-group input-group-lg hero-search">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input id="searchInput" type="text" class="form-control" placeholder="Cari judul, penulis, kategori, atau event…">
                <button id="clearSearch" type="button" class="btn btn-light d-none">
                  <i class="bi bi-x-circle"></i>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Bar filter kecil di bawah hero -->
        <div class="hero-tabs">
          <div class="row g-2 g-md-3 align-items-center">
            <div class="col-12 col-md-3">
              <select id="status" class="form-select">
                <option value="">Semua Keputusan</option>
                <option value="diterima">Diterima</option>
                <option value="revisi">Revisi</option>
                <option value="ditolak">Ditolak</option>
                <option value="pending">Pending</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- TABS -->
      <ul class="nav nav-tabs mb-3" id="histTabs" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" id="ab-tab" data-bs-toggle="tab" data-bs-target="#ab-pane" type="button" role="tab">
            <i class="bi bi-journal-text me-1"></i>Abstrak
            <span class="badge bg-primary-subtle text-primary ms-1" id="count-ab"><?= count($riwayatAbstrak) ?></span>
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="fp-tab" data-bs-toggle="tab" data-bs-target="#fp-pane" type="button" role="tab">
            <i class="bi bi-file-earmark-text me-1"></i>Full Paper
            <span class="badge bg-primary-subtle text-primary ms-1" id="count-fp"><?= count($riwayatFullpaper) ?></span>
          </button>
        </li>
      </ul>

      <div class="tab-content">
        <!-- ABSTRAK -->
        <div class="tab-pane fade show active" id="ab-pane" role="tabpanel" aria-labelledby="ab-tab">
          <?php if (empty($riwayatAbstrak)): ?>
            <div class="card card-glass border-0">
              <div class="card-body text-center py-5">
                <div class="empty-icon"><i class="bi bi-journal-x"></i></div>
                <div class="empty-title">Belum ada riwayat Abstrak</div>
                <div class="empty-subtitle">Riwayat abstrak yang kamu review akan muncul di sini.</div>
              </div>
            </div>
          <?php else: ?>
            <div class="card card-glass border-0">
              <div class="table-responsive">
                <table class="table align-middle mb-0" id="tbl-ab">
                  <thead class="table-light">
                    <tr>
                      <th>Judul</th>
                      <th class="d-none d-lg-table-cell">Event</th>
                      <th>Presenter</th>
                      <th class="d-none d-xl-table-cell">Kategori</th>
                      <th>Keputusan</th>
                      <th>Tgl Review</th>
                      <th class="text-end">Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($riwayatAbstrak as $r):
                      $search = strtolower(trim(($r['judul'] ?? '').' '.($r['nama_lengkap'] ?? '').' '.($r['nama_kategori'] ?? '').' '.($r['event_title'] ?? '')));
                      $dec    = strtolower((string)($r['keputusan'] ?? 'pending'));
                      $aid    = (int)($r['id_abstrak'] ?? 0);
                      $detailUrl = $aid ? site_url('reviewer/abstrak/'.$aid) : '#';
                    ?>
                      <tr class="row-item" data-kind="ab" data-search="<?= esc($search) ?>" data-status="<?= esc($dec) ?>">
                        <td>
                          <div class="fw-semibold text-dark"><?= esc($r['judul'] ?? '-') ?></div>
                          <div class="small text-muted d-lg-none"><i class="bi bi-calendar-event me-1"></i><?= esc($r['event_title'] ?? '-') ?></div>
                        </td>
                        <td class="d-none d-lg-table-cell">
                          <span class="badge bg-info-subtle text-info px-3 py-2">
                            <i class="bi bi-calendar-event me-1"></i><?= esc($r['event_title'] ?? '-') ?>
                          </span>
                        </td>
                        <td>
                          <div class="d-flex align-items-center gap-2">
                            <div class="user-avatar bg-primary-subtle text-primary"><i class="bi bi-person"></i></div>
                            <div class="text-dark"><?= esc($r['nama_lengkap'] ?? '-') ?></div>
                          </div>
                        </td>
                        <td class="d-none d-xl-table-cell text-muted"><?= esc($r['nama_kategori'] ?? '-') ?></td>
                        <td><span class="badge <?= $badge($r['keputusan'] ?? 'pending') ?> px-3 py-2"><?= ucfirst($r['keputusan'] ?? 'pending') ?></span></td>
                        <td><?= $fmtDT($r['tanggal_review'] ?? null) ?></td>
                        <td class="text-end">
                          <a href="<?= $detailUrl ?>"
                             class="btn btn-sm btn-primary <?= $aid ? '' : 'disabled' ?>"
                             <?= $aid ? '' : 'tabindex="-1" aria-disabled="true"' ?>>
                            <i class="bi bi-eye me-1"></i>Detail
                          </a>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          <?php endif; ?>
        </div>

        <!-- FULL PAPER -->
        <div class="tab-pane fade" id="fp-pane" role="tabpanel" aria-labelledby="fp-tab">
          <?php if (empty($riwayatFullpaper)): ?>
            <div class="card card-glass border-0">
              <div class="card-body text-center py-5">
                <div class="empty-icon"><i class="bi bi-journal-x"></i></div>
                <div class="empty-title">Belum ada riwayat Full Paper</div>
                <div class="empty-subtitle">Review yang sudah kamu ACC juga muncul di sini.</div>
              </div>
            </div>
          <?php else: ?>
            <div class="card card-glass border-0">
              <div class="table-responsive">
                <table class="table align-middle mb-0" id="tbl-fp">
                  <thead class="table-light">
                    <tr>
                      <th>Judul</th>
                      <th class="d-none d-lg-table-cell">Event</th>
                      <th>Presenter</th>
                      <th class="d-none d-xl-table-cell">Kategori</th>
                      <th>Keputusan</th>
                      <th>Tgl Review</th>
                      <th class="text-end">Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($riwayatFullpaper as $r):
                      $search = strtolower(trim(($r['judul'] ?? '').' '.($r['nama_lengkap'] ?? '').' '.($r['nama_kategori'] ?? '').' '.($r['event_title'] ?? '')));
                      $dec    = strtolower((string)($r['keputusan'] ?? 'pending'));
                      $sid    = (int)($r['submission_id'] ?? 0);
                      $detailUrl = $sid ? site_url('reviewer/fullpaper/'.$sid) : '#';
                    ?>
                      <tr class="row-item" data-kind="fp" data-search="<?= esc($search) ?>" data-status="<?= esc($dec) ?>">
                        <td>
                          <div class="fw-semibold text-dark"><?= esc($r['judul'] ?? '-') ?></div>
                          <div class="small text-muted d-lg-none"><i class="bi bi-calendar-event me-1"></i><?= esc($r['event_title'] ?? '-') ?></div>
                        </td>
                        <td class="d-none d-lg-table-cell">
                          <span class="badge bg-info-subtle text-info px-3 py-2">
                            <i class="bi bi-calendar-event me-1"></i><?= esc($r['event_title'] ?? '-') ?>
                          </span>
                        </td>
                        <td>
                          <div class="d-flex align-items-center gap-2">
                            <div class="user-avatar bg-primary-subtle text-primary"><i class="bi bi-person"></i></div>
                            <div class="text-dark"><?= esc($r['nama_lengkap'] ?? '-') ?></div>
                          </div>
                        </td>
                        <td class="d-none d-xl-table-cell text-muted"><?= esc($r['nama_kategori'] ?? '-') ?></td>
                        <td><span class="badge <?= $badge($r['keputusan'] ?? 'pending') ?> px-3 py-2"><?= ucfirst($r['keputusan'] ?? 'pending') ?></span></td>
                        <td><?= $fmtDT($r['tanggal_review'] ?? null) ?></td>
                        <td class="text-end">
                          <a href="<?= $detailUrl ?>"
                             class="btn btn-sm btn-primary <?= $sid ? '' : 'disabled' ?>"
                             <?= $sid ? '' : 'tabindex="-1" aria-disabled="true"' ?>>
                            <i class="bi bi-eye me-1"></i>Detail
                          </a>
                        </td>
                      </tr>
                    <?php endforeach; ?>
                  </tbody>
                </table>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- NO RESULT -->
      <div id="noResults" class="text-center py-5 d-none">
        <div class="empty-icon"><i class="bi bi-search"></i></div>
        <div class="empty-title">Tidak ada hasil ditemukan</div>
        <div class="empty-subtitle">Coba ubah kata kunci atau filter keputusan</div>
      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<!-- ====== CSS: disamakan dengan halaman Abstrak/Full Paper ====== -->
<style>
:root{
  --blue-50:#eff6ff; --blue-100:#dbeafe; --blue-200:#bfdbfe; --blue-300:#93c5fd;
  --blue-400:#60a5fa; --blue-500:#3b82f6; --blue-600:#2563eb; --blue-700:#1d4ed8; --blue-800:#1e40af; --blue-900:#1e3a8a;
  --muted:#6b7280; --ink:#0f172a; --radius:16px;
  --side-pad: clamp(1rem, 2.3vw, 2.2rem);
  --glass-bg: rgba(255,255,255,.92);
  --glass-bd: rgba(30,64,175,.14);
  --glass-shadow: 0 10px 24px rgba(2,6,23,.08);
}

/* container & base */
.container-xxl{ max-width:min(100%, 1560px); padding-left:var(--side-pad)!important; padding-right:var(--side-pad)!important; margin-inline:auto; }
body{ font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size:15.5px; line-height:1.6; color:var(--ink); }
.page-wrap-blue{
  min-height:100vh; padding-top:72px;
  background:
    radial-gradient(1000px 380px at 10% -10%, rgba(59,130,246,.16), rgba(59,130,246,0) 60%),
    radial-gradient(1000px 380px at 90% 110%, rgba(59,130,246,.12), rgba(59,130,246,0) 70%),
    linear-gradient(180deg, var(--blue-50), #fff 40%);
}

/* HERO */
.card-hero{ border:0; border-radius:var(--radius); overflow:hidden; box-shadow:0 12px 28px rgba(30,64,175,.18); }
.card-hero .hero-body{ background:linear-gradient(135deg,var(--blue-700),var(--blue-800)); color:#fff; padding:1.8rem 1.2rem; min-height:164px; }
.hero-title{ font-weight:800; }
.text-white-70{ color:rgba(255,255,255,.85)!important; }
.hero-tools .input-group .input-group-text{ background:#fff; border:0; }
.hero-tools .form-control{ border:0; }
.hero-tools .btn{ border:0; }
.hero-search{ border-radius:12px; overflow:hidden; }
.hero-tabs{ background:#fff; padding:.6rem .8rem; border:1px solid rgba(30,64,175,.18); border-top:0; }

/* Glass card */
.card-glass{
  backdrop-filter: blur(6px);
  background: var(--glass-bg);
  border: 1px solid var(--glass-bd);
  border-radius: 12px;
  box-shadow: var(--glass-shadow);
}

/* Table */
.table thead th{ background:#f8fafc; border-bottom:1px solid #e2e8f0; font-weight:600; color:#374151; padding:14px; }
.table tbody td{ padding:14px; vertical-align:middle; }

/* Badges & buttons */
.bg-primary-subtle{ background-color: rgba(37,99,235,.12) !important; }
.bg-info-subtle{ background-color: rgba(6,182,212,.12) !important; }
.text-info{ color:#06b6d4 !important; }
.bg-success{ background-color:#10b981 !important; }
.bg-warning{ background-color:#f59e0b !important; }
.bg-danger{ background-color:#ef4444 !important; }
.bg-secondary{ background-color:#6b7280 !important; }

.btn{ border-radius:10px; font-weight:800; }
.btn-primary{ background-color:var(--blue-600); border-color:var(--blue-600); box-shadow:0 4px 12px rgba(37,99,235,.2); }
.btn-light{ background:#fff; }
.btn-outline-secondary{ border-color:#d1d5db; color:#6b7280; }
.btn-outline-secondary:hover{ background-color:#f9fafb; border-color:#9ca3af; color:#374151; }

/* Avatar kecil */
.user-avatar{ width:36px; height:36px; border-radius:8px; display:flex; align-items:center; justify-content:center; }

/* Tabs */
.nav-tabs .nav-link{ border:0; font-weight:700; color:#1e293b; }
.nav-tabs .nav-link.active{
  background:#fff; border-radius:10px 10px 0 0; border:1px solid #e5e7eb; border-bottom-color:#fff;
}

/* Empty */
.empty-icon{ font-size:3rem; color:#9ca3af; }
.empty-title{ font-size:1.1rem; font-weight:700; color:#334155; }
.empty-subtitle{ color:#64748b; }

/* Forms */
.form-control, .form-select{ border:2px solid #e2e8f0; border-radius:8px; }
.form-control:focus, .form-select:focus{ border-color:var(--blue-600); box-shadow:0 0 0 .2rem rgba(37,99,235,.1); }
.input-group-text{ border-radius:8px 0 0 8px; }

/* Responsive */
@media (max-width:575.98px){
  .container-xxl{ padding-left: calc(var(--side-pad) - .25rem) !important; padding-right: calc(var(--side-pad) - .25rem) !important; }
}
</style>

<!-- ====== JS: search langsung + filter keputusan (tanpa Enter), konsisten dengan halaman lain ====== -->
<script>
document.addEventListener('DOMContentLoaded', function(){
  const q = document.getElementById('searchInput');
  const clr = document.getElementById('clearSearch');
  const st = document.getElementById('status');
  const noRes = document.getElementById('noResults');
  const tabs = document.getElementById('histTabs');
  const countAb = document.getElementById('count-ab');
  const countFp = document.getElementById('count-fp');

  function activePaneId(){
    const activeBtn = tabs.querySelector('.nav-link.active');
    return activeBtn ? activeBtn.getAttribute('data-bs-target') : '#ab-pane';
  }

  function apply(){
    const query = (q?.value || '').toLowerCase().trim();
    const stat  = (st?.value || '').toLowerCase().trim();
    const paneSelector = activePaneId();
    const pane = document.querySelector(paneSelector);
    if (!pane) return;

    const rows = Array.from(pane.querySelectorAll('.row-item'));
    let shown = 0;
    rows.forEach(tr=>{
      const s = (tr.dataset.search || '').toLowerCase();
      const t = (tr.dataset.status || '').toLowerCase();
      const ok = (!query || s.includes(query)) && (!stat || t === stat);
      tr.style.display = ok ? '' : 'none';
      if (ok) shown++;
    });

    // toggle no results
    if (noRes) noRes.classList.toggle('d-none', shown > 0);

    // toggle clear button
    if (clr) clr.classList.toggle('d-none', !query);

    // update badge count per tab (yang aktif saja untuk feel responsif)
    if (paneSelector === '#ab-pane' && countAb) countAb.textContent = shown || 0;
    if (paneSelector === '#fp-pane' && countFp) countFp.textContent = shown || 0;
  }

  q?.addEventListener('input', apply);
  st?.addEventListener('change', apply);
  clr?.addEventListener('click', ()=>{ if (q){ q.value=''; q.focus(); } apply(); });

  // re-apply ketika pindah tab
  document.querySelectorAll('#histTabs .nav-link').forEach(btn=>{
    btn.addEventListener('shown.bs.tab', apply);
  });

  apply();
});
</script>
