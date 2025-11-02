<?php
/**
 * View: Admin · Kelola Paper · Presenter per Event (spaced actions)
 */
$title      = $title ?? 'Presenter';
$eventId    = (int)($eventId ?? 0);
$presenters = $presenters ?? [];
$q          = trim((string)($_GET['q'] ?? ''));

$mapAbsStatus = function (?string $s): array {
  $sl = strtolower((string)$s);
  if ($sl === 'diterima')         return ['Diterima','success'];
  if ($sl === 'ditolak')          return ['Ditolak','danger'];
  if ($sl === 'revisi')           return ['Revisi','warning text-dark'];
  if ($sl === 'sedang_direview')  return ['Review','primary'];
  if ($sl === 'menunggu')         return ['Menunggu','secondary'];
  return ['—','secondary'];
};
$mapFpStatus = function (?string $s): array {
  $su = strtoupper((string)$s);
  return match ($su) {
    'ACCEPTED' => ['Diterima','success'],
    'REVISION' => ['Revisi','warning text-dark'],
    'REJECTED' => ['Ditolak','danger'],
    'UPLOADED' => ['Menunggu review','primary'],
    default    => ['—','secondary'],
  };
};
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_admin') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill page-wrap-blue">
    <div class="container-xxl px-3 px-md-4 py-4" id="kelola-presenter">

      <!-- HERO -->
      <div class="card-hero mb-3">
        <div class="hero-body">
          <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
              <h3 class="hero-title mb-1">
                <i class="bi bi-people me-2"></i>Presenter — <?= esc($presenters[0]['event_title'] ?? ('Event #'.$eventId)) ?>
              </h3>
              <div class="text-white-70 small">Ringkasan penugasan Abstrak & Full Paper untuk event ini.</div>
            </div>
            <div class="hero-tools flex-grow-1" style="max-width:620px;">
              <form method="get" class="input-group input-group-lg hero-search">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input id="searchInput" type="text" class="form-control" name="q"
                       value="<?= esc($q) ?>" placeholder="Cari nama, email, atau judul…">
                <button id="clearSearch" type="button" class="btn btn-light <?= $q===''?'d-none':'' ?>">
                  <i class="bi bi-x-circle"></i>
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>

      <!-- LIST -->
      <div class="card card-glass border-0">
        <div class="table-responsive">
          <?php if (empty($presenters)): ?>
            <div class="text-center py-5">
              <div class="empty-icon mb-3"><i class="bi bi-inboxes"></i></div>
              <div class="empty-title mb-1">Belum ada presenter</div>
              <div class="empty-subtitle">Data presenter untuk event ini belum tersedia.</div>
            </div>
          <?php else: ?>
            <table class="table table-kp align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th style="width:34%;">Author & Paper</th>
                  <th class="text-start" style="width:15%;">Reviewer Abstrak</th>
                  <th class="text-start" style="width:18%;">Reviewer Full Paper</th>
                  <th class="text-start" style="width:12%;">Status Abstrak</th>
                  <th class="text-start" style="width:11%;">Status Full</th>
                  <th class="text-center" style="width:6%;">LoA</th>
                  <th class="text-end" style="width:18%;">Aksi</th><!-- widened -->
                </tr>
              </thead>
              <tbody id="listBody">
              <?php foreach ($presenters as $p):
                $hay = strtolower(trim(
                  ($p['nama_lengkap'] ?? '').' '.($p['email'] ?? '').' '.($p['judul'] ?? '').' '.($p['nama_kategori'] ?? '')
                ));

                $hasAbs   = !empty($p['file_abstrak']);
                $hasFull  = !empty($p['has_full']);

                $absAssigned = (int)($p['abs_assigned_count'] ?? 0);
                $fpAssigned  = (int)($p['fp_assigned_count']  ?? 0);

                [$absText,$absBadge] = $mapAbsStatus($p['status'] ?? null);
                [$fpText ,$fpBadge ] = $mapFpStatus($p['full_paper_status'] ?? ($hasFull ? 'UPLOADED' : '-'));

                $assignAbs = $p['assign_abs_url'] ?? site_url('admin/abstrak/detail/'.(int)($p['id_abstrak'] ?? 0));
                $assignFp  = $p['assign_fp_url']  ?? site_url('admin/fullpaper');

                $loaExists = !empty($p['loa_exists']);
              ?>
                <tr class="row-soft" data-search="<?= esc($hay) ?>">
                  <td class="cell-author">
                    <div class="fw-semibold text-dark clamp-1"><?= esc($p['nama_lengkap'] ?? '-') ?></div>
                    <div class="small text-muted clamp-1"><?= esc($p['email'] ?? '-') ?></div>
                    <div class="mt-1 small text-blue-900 fw-semibold clamp-2">“<?= esc($p['judul'] ?? '-') ?>”</div>
                    <div class="small text-muted clamp-1"><?= esc($p['nama_kategori'] ?? '-') ?></div>
                  </td>

                  <td class="text-start">
                    <?php if ($absAssigned > 0): ?>
                      <span class="pill pill-success">Ditugaskan <b>(<?= $absAssigned ?>)</b></span>
                    <?php else: ?>
                      <span class="pill pill-muted">Belum ditugaskan</span>
                    <?php endif; ?>
                  </td>

                  <td class="text-start">
                    <span class="pill pill-info"><b><?= min(3,$fpAssigned) ?>/3</b> reviewer</span>
                  </td>

                  <td class="text-start">
                    <span class="badge bg-<?= esc($absBadge) ?> px-3 py-2"><?= esc($absText) ?></span>
                    <?php if(!$hasAbs): ?><div class="tiny text-muted mt-1">Belum unggah</div><?php endif; ?>
                  </td>

                  <td class="text-start">
                    <span class="badge bg-<?= esc($fpBadge) ?> px-3 py-2"><?= esc($fpText) ?></span>
                    <?php if(!$hasFull): ?><div class="tiny text-muted mt-1">Belum unggah</div><?php endif; ?>
                  </td>

                  <td class="text-center">
                    <span class="pill <?= $loaExists?'pill-success':'pill-muted' ?>">
                      <?= $loaExists ? 'Sudah' : 'Belum' ?>
                    </span>
                  </td>

                  <td class="text-end cell-actions">
                    <div class="actions">
                      <a class="btn btn-outline-primary btn-slim" href="<?= esc($assignAbs) ?>">
                        <i class="bi bi-person-plus me-1"></i><span>Tugaskan Abstrak</span>
                      </a>
                      <a class="btn btn-primary btn-slim" href="<?= esc($assignFp) ?>">
                        <i class="bi bi-people me-1"></i><span>Tugaskan Full Paper</span>
                      </a>
                    </div>
                  </td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
          <?php endif; ?>
        </div>
      </div>

      <div class="mt-3">
        <a href="<?= site_url('admin/kelola-paper') ?>" class="btn btn-outline-secondary btn-sm">
          <i class="bi bi-chevron-left me-1"></i> Daftar Event
        </a>
      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<style>
:root{
  --blue-50:#eff6ff; --blue-100:#dbeafe; --blue-200:#bfdbfe; --blue-300:#93c5fd;
  --blue-400:#60a5fa; --blue-500:#3b82f6; --blue-600:#2563eb; --blue-700:#1d4ed8; --blue-800:#1e40af; --blue-900:#1e3a8a;
  --muted:#6b7280; --ink:#0f172a; --radius:16px; --side-pad: clamp(1rem, 2.3vw, 2.2rem);
  --glass-bg: rgba(255,255,255,.92); --glass-bd: rgba(30,64,175,.14); --glass-shadow: 0 10px 24px rgba(2,6,23,.08);
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

/* Hero */
.card-hero{ border:0; border-radius:var(--radius); overflow:hidden; box-shadow:0 12px 28px rgba(30,64,175,.18); }
.card-hero .hero-body{ background:linear-gradient(135deg,var(--blue-700),var(--blue-800)); color:#fff; padding:1.8rem 1.2rem; min-height:164px; }
.hero-title{ font-weight:800; }
.text-white-70{ color:rgba(255,255,255,.85)!important; }
.hero-tools .input-group .input-group-text{ background:#fff; border:0; }
.hero-tools .form-control{ border:0; }
.hero-tools .btn{ border:0; }
.hero-search{ border-radius:12px; overflow:hidden; }

/* Card & table */
.card-glass{ backdrop-filter: blur(6px); background: var(--glass-bg); border: 1px solid var(--glass-bd); border-radius: 12px; box-shadow: var(--glass-shadow); }
.table-kp{ table-layout:fixed; border-collapse:separate; border-spacing:0 10px; }
.table-kp thead th{ background:#f8fafc; border:0; padding:.8rem .9rem; font-weight:800; color:#0f172a; }
.table-kp tbody tr.row-soft{ background:#fff; border:1px solid rgba(30,64,175,.10); box-shadow:0 8px 18px rgba(30,64,175,.07); }
.table-kp tbody td{ padding:14px; vertical-align:middle; }
.table-kp tbody tr.row-soft td:first-child{ border-radius:12px 0 0 12px; position:relative; }
.table-kp tbody tr.row-soft td:last-child{ border-radius:0 12px 12px 0; }
.table-kp tbody tr.row-soft td:first-child::before{
  content:""; position:absolute; left:0; top:0; bottom:0; width:4px; border-radius:12px 0 0 12px;
  background:linear-gradient(180deg,#2563eb,#1e40af); opacity:.75;
}

/* clamp util */
.clamp-1{ white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.clamp-2{ display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; max-height:3rem; }

/* Badges & pills */
.bg-success-subtle{   background:#d1fae5!important; color:#065f46!important; }
.bg-warning-subtle{   background:#fef3c7!important; color:#92400e!important; }
.bg-danger-subtle{    background:#fee2e2!important; color:#991b1b!important; }
.bg-secondary-subtle{ background:#f1f5f9!important; color:#475569!important; }
.bg-primary-subtle{   background:#dbeafe!important; color:#1d4ed8!important; }
.text-blue-900{ color:var(--blue-900)!important; }
.tiny{ font-size:.78rem; }
.pill{ display:inline-flex; align-items:center; gap:.35rem; padding:.28rem .55rem; border-radius:999px; font-weight:700; font-size:.82rem; border:1px solid rgba(2,6,23,.06); background:#f8fafc; color:#0f172a; }
.pill-success{ background:#ecfdf5; color:#065f46; border-color:#a7f3d0; }
.pill-info{    background:#eff6ff; color:#1d4ed8; border-color:#bfdbfe; }
.pill-muted{   background:#f1f5f9; color:#475569; border-color:#e2e8f0; }

/* —— ACTIONS: lebih renggang —— */
.cell-actions{ min-width: 260px; }
.cell-actions .actions{
  display:flex;
  justify-content:flex-end;
  align-items:center;
  gap:12px;                 /* jarak antar tombol */
  flex-wrap:wrap;           /* izinkan baris baru bila sempit */
}
.btn{ border-radius:10px; font-weight:800; }
.btn-slim{
  padding:.5rem .9rem;      /* tombol lebih “lega” */
  font-weight:800;
  border-radius:10px;
  font-size:.9rem;
  white-space:nowrap;
}
.btn-primary{ background:#2563eb; border-color:#2563eb; box-shadow:0 4px 12px rgba(37,99,235,.2); }
.btn-outline-primary{ border-color:#2563eb; color:#2563eb; }
.btn-outline-primary:hover{ background:#2563eb; color:#fff; }

/* Empty */
.empty-icon{ font-size:3rem; color:#94a3b8; }
.empty-title{ font-weight:700; color:#334155; }
.empty-subtitle{ color:#64748b; }

/* Responsive: stack tombol di layar lebih sempit */
@media (max-width: 1200px){
  .cell-actions .actions{
    justify-content:flex-start;
    flex-direction:column;  /* bertumpuk */
    align-items:stretch;
    gap:.5rem;
  }
  .btn-slim{ width:100%; }
}
@media (max-width: 767.98px){
  .hero-tools{ width:100%; max-width:none; }
  .card-hero .hero-body{ padding:1.4rem 1rem; min-height:150px; }
  .table-kp thead{ display:none; }
  .table-kp tbody, .table-kp tr, .table-kp td{ display:block; width:100%; }
  .table-kp tbody tr.row-soft{ border-radius:12px; overflow:hidden; }
  .table-kp tbody td{ padding:.7rem .9rem; }
  .table-kp tbody tr.row-soft td + td{ border-top:1px dashed rgba(15,23,42,.07); }
  .cell-actions .actions{ gap:.6rem; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const q   = document.getElementById('searchInput');
  const clr = document.getElementById('clearSearch');
  const body= document.getElementById('listBody');

  function apply(){
    const qq = (q?.value || '').toLowerCase().trim();
    let shown = 0;

    Array.from(body?.querySelectorAll('tr.row-soft') || []).forEach(tr => {
      const s = (tr.dataset.search || '').toLowerCase();
      const ok = !qq || s.includes(qq);
      tr.style.display = ok ? '' : 'none';
      if (ok) shown++;
    });

    let empty = body?.querySelector('.js-empty-row');
    if (shown === 0) {
      if (!empty) {
        empty = document.createElement('tr');
        empty.className = 'js-empty-row';
        empty.innerHTML = `<td colspan="7" class="py-5 text-center text-muted">
          <i class="bi bi-search fs-1 d-block mb-2"></i>Tidak ada hasil yang cocok
        </td>`;
        body?.appendChild(empty);
      }
    } else {
      empty?.remove();
    }

    clr?.classList.toggle('d-none', !qq);
  }

  q?.addEventListener('input', apply);
  clr?.addEventListener('click', ()=>{ q.value=''; apply(); q.focus(); });

  apply();
});
</script>
