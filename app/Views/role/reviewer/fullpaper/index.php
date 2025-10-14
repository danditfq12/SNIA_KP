<?php
// File: app/Views/role/reviewer/fullpaper/index.php

$title        = $title ?? 'Tugas Full Paper';
$rows         = $rows  ?? [];
$eventOptions = $eventOptions ?? [];

$fmt = fn($d,$t=false)=> $d ? date($t?'d M Y H:i':'d M Y', strtotime($d)) : '—';
$badgeRev = fn($s)=> match(strtolower((string)$s)){
  'diterima' => 'success',
  'revisi'   => 'warning text-dark',
  'ditolak'  => 'danger',
  default    => 'secondary'
};

$isEmpty = empty($rows);
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_reviewer') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill page-wrap-blue">
    <div class="container-xxl px-3 px-md-4 py-4">

      <!-- HERO -->
      <div class="card-hero mb-3">
        <div class="hero-body">
          <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
              <h3 class="hero-title mb-1">
                <i class="bi bi-journal-text me-2"></i><?= esc($title) ?>
              </h3>
              <div class="text-white-70 small">Daftar penugasan full paper. Klik <b>Tinjau</b> untuk memberikan review.</div>
            </div>
            <div class="hero-tools flex-grow-1" style="max-width:620px;">
              <div class="input-group input-group-lg hero-search">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input id="searchInput" type="text" class="form-control" placeholder="Cari judul, penulis, kategori…">
                <button id="clearSearch" type="button" class="btn btn-light d-none">
                  <i class="bi bi-x-circle"></i>
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Filter bar -->
        <div class="hero-tabs">
          <div class="row g-2 g-md-3 align-items-center">
            <div class="col-6 col-md-3">
              <select id="eventFilter" class="form-select">
                <option value="">Semua Event</option>
                <?php foreach ($eventOptions as $id=>$name): ?>
                  <option value="<?= (int)$id ?>"><?= esc($name ?: ('Event #'.$id)) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-6 col-md-3">
              <select id="statusFilter" class="form-select">
                <option value="">Semua Status Review</option>
                <option value="menunggu">Menunggu</option>
                <option value="diterima">Diterima</option>
                <option value="revisi">Revisi</option>
                <option value="ditolak">Ditolak</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <?php if ($isEmpty): ?>
        <!-- EMPTY STATE -->
        <div class="card card-glass border-0">
          <div class="card-body text-center py-5">
            <div class="empty-icon mb-3"><i class="bi bi-inbox"></i></div>
            <div class="empty-title mb-1">Belum ada tugas full paper</div>
            <div class="empty-subtitle">Penugasan baru akan tampil otomatis di sini.</div>
          </div>
        </div>
      <?php else: ?>
        <!-- LIST -->
        <div class="card card-glass border-0">
          <div class="table-responsive">
            <table class="table align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th class="fw-semibold" style="width:44%;">Full Paper</th>
                  <th class="fw-semibold d-none d-md-table-cell" style="width:20%;">Event</th>
                  <th class="fw-semibold d-none d-lg-table-cell" style="width:18%;">Penulis</th>
                  <th class="fw-semibold d-none d-xl-table-cell" style="width:10%;">Kategori</th>
                  <th class="fw-semibold" style="width:8%;">Status Review</th>
                  <th class="fw-semibold text-end" style="width:10%;">Aksi</th>
                </tr>
              </thead>
              <tbody id="listBody">
                <?php foreach ($rows as $r): ?>
                  <?php
                    $id    = (int)($r['id'] ?? 0);
                    $judul = (string)($r['title'] ?? '—');

                    $nama  = (string)($r['nama_lengkap']  ?? '-');
                    $kat   = (string)($r['nama_kategori'] ?? '-');
                    $evtNm = (string)($r['event_title']   ?? '-');

                    $txt   = strtolower(trim($judul.' '.$nama.' '.$kat));
                    $eid   = (int)($r['event_id'] ?? 0);

                    $rev   = strtolower((string)($r['review_status'] ?? 'menunggu'));
                    $uploaded = $r['tanggal_upload'] ?? null;
                  ?>
                  <tr class="review-row"
                      data-search="<?= esc($txt) ?>"
                      data-event="<?= $eid ?>"
                      data-status="<?= esc($rev) ?>">
                    <td>
                      <div class="fw-semibold text-dark mb-1"><?= esc($judul) ?></div>
                      <div class="small text-muted d-flex flex-wrap gap-2">
                        <span><i class="bi bi-calendar2-plus me-1"></i><?= $fmt($uploaded, true) ?></span>
                        <span class="d-md-none">•</span>
                        <span class="d-md-none"><i class="bi bi-calendar-event me-1"></i><?= esc($evtNm) ?></span>
                        <span class="d-lg-none">•</span>
                        <span class="d-lg-none"><i class="bi bi-person me-1"></i><?= esc($nama) ?></span>
                      </div>
                    </td>
                    <td class="d-none d-md-table-cell">
                      <span class="badge bg-info-subtle text-info px-3 py-2">
                        <i class="bi bi-calendar-event me-1"></i><?= esc($evtNm ?: 'Event') ?>
                      </span>
                    </td>
                    <td class="d-none d-lg-table-cell"><?= esc($nama) ?></td>
                    <td class="d-none d-xl-table-cell text-muted"><?= esc($kat) ?></td>
                    <td>
                      <span class="badge bg-<?= $badgeRev($rev) ?> px-3 py-2"><?= esc(ucfirst($rev)) ?></span>
                    </td>
                    <td class="text-end">
                      <a href="<?= site_url('reviewer/fullpaper/'.$id) ?>" class="btn btn-primary btn-sm">
                        <i class="bi bi-eye me-1"></i>Tinjau
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
  </main>
</div>

<?= $this->include('partials/footer') ?>

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
.container-xxl{ max-width:min(100%, 1560px); padding-left:var(--side-pad)!important; padding-right:var(--side-pad)!important; margin-inline:auto; }
body{ font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size:15.5px; line-height:1.6; color:var(--ink); }
.page-wrap-blue{
  min-height:100vh; padding-top:72px;
  background:
    radial-gradient(1000px 380px at 10% -10%, rgba(59,130,246,.16), rgba(59,130,246,0) 60%),
    radial-gradient(1000px 380px at 90% 110%, rgba(59,130,246,.12), rgba(59,130,246,0) 70%),
    linear-gradient(180deg, var(--blue-50), #fff 40%);
}
.card-hero{ border:0; border-radius:var(--radius); overflow:hidden; box-shadow:0 12px 28px rgba(30,64,175,.18); }
.card-hero .hero-body{ background:linear-gradient(135deg,var(--blue-700),var(--blue-800)); color:#fff; padding:1.8rem 1.2rem; min-height:164px; }
.hero-title{ font-weight:800; }
.text-white-70{ color:rgba(255,255,255,.85)!important; }
.hero-tools .input-group .input-group-text{ background:#fff; border:0; }
.hero-tools .form-control{ border:0; }
.hero-tools .btn{ border:0; }
.hero-search{ border-radius:12px; overflow:hidden; }
.hero-tabs{ background:#fff; padding:.6rem .8rem; border:1px solid rgba(30,64,175,.18); border-top:0; }

.card-glass{
  backdrop-filter: blur(6px);
  background: var(--glass-bg);
  border: 1px solid var(--glass-bd);
  border-radius: 12px;
  box-shadow: var(--glass-shadow);
}
.table thead th{ background:#f8fafc; border-bottom:1px solid #e2e8f0; font-weight:600; color:#374151; padding:14px; }
.table tbody td{ padding:14px; vertical-align:middle; }

.bg-info-subtle{ background-color: rgba(6,182,212,.12) !important; }
.text-info{ color:#06b6d4 !important; }
.bg-success{ background-color:#10b981 !important; }
.bg-warning{ background-color:#f59e0b !important; }
.bg-danger{ background-color:#ef4444 !important; }
.bg-secondary{ background-color:#6b7280 !important; }
.btn{ border-radius:10px; font-weight:800; }
.btn-primary{ background-color:var(--blue-600); border-color:var(--blue-600); box-shadow:0 4px 12px rgba(37,99,235,.2); }

.empty-icon{ font-size:3rem; color:#94a3b8; }
.empty-title{ font-weight:700; color:#334155; }
.empty-subtitle{ color:#64748b; }

.form-control, .form-select{ border:2px solid #e2e8f0; border-radius:8px; }
.form-control:focus, .form-select:focus{ border-color:var(--blue-600); box-shadow:0 0 0 .2rem rgba(37,99,235,.1); }
.input-group-text{ border-radius:8px 0 0 8px; }

@media (max-width:767.98px){
  .hero-tools{ width:100%; max-width:none; }
  .card-hero .hero-body{ padding:1.4rem 1rem; min-height:150px; }
}
@media (max-width:575.98px){
  .container-xxl{ padding-left: calc(var(--side-pad) - .25rem) !important; padding-right: calc(var(--side-pad) - .25rem) !important; }
  td.text-end .btn{ min-width:110px; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const q   = document.getElementById('searchInput');
  const clr = document.getElementById('clearSearch');
  const ef  = document.getElementById('eventFilter');
  const sf  = document.getElementById('statusFilter');
  const body = document.getElementById('listBody');

  if (!body) return;
  const rows = Array.from(body.querySelectorAll('.review-row'));

  function ensureEmptyRow() {
    let empty = document.getElementById('emptyRow');
    if (!empty) {
      empty = document.createElement('tr');
      empty.id = 'emptyRow';
      empty.innerHTML = `<td colspan="6" class="py-5 text-center text-muted">
        <i class="bi bi-search fs-1 d-block mb-2"></i>Tidak ada hasil
      </td>`;
    }
    return empty;
  }

  function apply(){
    const qq = (q?.value || '').toLowerCase().trim();
    const ev = ef?.value || '';
    const st = (sf?.value || '').toLowerCase();

    let shown = 0;
    rows.forEach(tr=>{
      const s = (tr.dataset.search || '').toLowerCase();
      const e = tr.dataset.event  || '';
      const t = (tr.dataset.status || '').toLowerCase();
      const ok = (!qq || s.includes(qq)) && (!ev || ev===e) && (!st || st===t);
      tr.style.display = ok ? '' : 'none';
      if (ok) shown++;
    });

    const emptyRow = document.getElementById('emptyRow');
    if (!shown){
      if (!emptyRow) body.appendChild(ensureEmptyRow());
    } else {
      emptyRow?.remove();
    }
    if (clr) clr.classList.toggle('d-none', !qq);
  }

  q?.addEventListener('input', apply);
  clr?.addEventListener('click', ()=>{ q.value=''; apply(); q.focus(); });
  ef?.addEventListener('change', apply);
  sf?.addEventListener('change', apply);

  apply();
});
</script>
