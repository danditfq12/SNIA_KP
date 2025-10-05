<?php
/**
 * File: app/Views/role/admin/kelola_paper/presenter.php
 * Diseragamkan style-nya dengan halaman Index Kelola Paper.
 * ACTIONS: HANYA penugasan (Assign Abstrak / Assign Full) — tanpa download.
 */
$title      = $title ?? 'Presenter';
$eventId    = (int)($eventId ?? 0);
$presenters = $presenters ?? [];
$q          = trim((string)($_GET['q'] ?? ''));

$mapAbsStatus = function (?string $s): array {
  $sl = strtolower((string)$s);
  if ($sl === 'diterima') return ['Diterima','bg-success-subtle'];
  if ($sl === 'ditolak')  return ['Ditolak','bg-danger-subtle'];
  if ($sl === 'revisi')   return ['Revisi','bg-warning-subtle'];
  if ($sl === 'sedang_direview') return ['Review berjalan','bg-primary-subtle'];
  return ['—','bg-secondary-subtle'];
};
$mapFpStatus = function (?string $s): array {
  $su = strtoupper((string)$s);
  return match ($su) {
    'ACCEPTED' => ['Diterima','bg-success-subtle'],
    'REVISION' => ['Revisi','bg-warning-subtle'],
    'REJECTED' => ['Ditolak','bg-danger-subtle'],
    'UPLOADED' => ['Menunggu review','bg-primary-subtle'],
    default    => ['—','bg-secondary-subtle'],
  };
};
$fmtDT = function($dt){
  if(!$dt) return '-';
  $t = strtotime((string)$dt);
  return date('d M Y H:i', $t);
};
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_admin') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill page-wrap-blue">
    <div class="container-xxl px-3 px-md-4 py-4" id="kelola-presenter">

      <!-- HERO (selaras index) -->
      <div class="hero-blue card-glass mb-3 p-3 p-md-4 d-flex justify-content-between align-items-start gap-3">
        <div>
          <h3 class="hero-title mb-1">
            <i class="bi bi-people me-2"></i>Presenter — <?= esc($presenters[0]['event_title'] ?? ('Event #'.$eventId)) ?>
          </h3>
          <div class="text-white-75 small">Kelola penugasan reviewer untuk Abstrak & Full Paper</div>
        </div>
        <a href="<?= site_url('admin/kelola-paper') ?>" class="btn btn-sm btn-outline-light rounded-3">
          <i class="bi bi-chevron-left me-1"></i> Daftar Event
        </a>
      </div>

      <!-- PENCARIAN -->
      <div class="card shadow-soft card-glass-plain mb-3">
        <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
          <span class="badge bg-blue-soft"><i class="bi bi-search"></i></span>
          <h6 class="mb-0 fw-semibold text-blue-900">Cari Presenter</h6>
        </div>
        <div class="card-body pt-3">
          <form class="row g-2" method="get">
            <div class="col-12 col-lg-9">
              <input class="form-control form-control-soft" name="q" value="<?= esc($q) ?>" placeholder="Ketik nama, email, atau judul…">
            </div>
            <div class="col-12 col-lg-3 d-grid">
              <button class="btn btn-primary"><i class="bi bi-search me-1"></i>Cari</button>
            </div>
          </form>
        </div>
      </div>

      <!-- DAFTAR PRESENTER -->
      <div class="card shadow-soft card-glass-plain">
        <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
          <span class="badge bg-blue-soft"><i class="bi bi-list-task"></i></span>
          <h6 class="mb-0 fw-semibold text-blue-900">Daftar Presenter</h6>
        </div>
        <div class="card-body pt-2">
          <?php if (empty($presenters)): ?>
            <div class="empty-hint">
              <i class="bi bi-inboxes me-1"></i>Belum ada presenter pada event ini.
            </div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table align-middle kp-table">
                <thead>
                  <tr>
                    <th style="width:34%">Author & Paper</th>
                    <th class="text-center" style="width:14%">Reviewer Abstrak</th>
                    <th class="text-center" style="width:18%">Reviewer Full Paper</th>
                    <th class="text-center" style="width:12%">Status Abstrak</th>
                    <th class="text-center" style="width:12%">Status Full</th>
                    <th class="text-center" style="width:6%">LoA</th>
                    <th class="text-end" style="width:14%">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                <?php foreach ($presenters as $p):
                  if ($q) {
                    $blob = strtolower(($p['nama_lengkap'] ?? '').' '.($p['email'] ?? '').' '.($p['judul'] ?? ''));
                    if (strpos($blob, strtolower($q)) === false) continue;
                  }

                  $hasAbs    = !empty($p['file_abstrak']);
                  $hasFull   = !empty($p['has_full']);
                  $absStat   = $mapAbsStatus($p['status'] ?? null);
                  $fpStat    = $mapFpStatus($p['full_paper_status'] ?? null);

                  $absAssigned = (int)($p['abs_assigned_count'] ?? 0);
                  $fpAssigned  = (int)($p['fp_assigned_count'] ?? 0);
                  $fpComplete  = (int)($p['fp_complete_count'] ?? 0);
                  $fpMissing   = max(0, 3 - $fpAssigned);

                  $absDLRaw = $p['abstract_deadline']   ?? null;
                  $fpDLRaw  = $p['full_paper_deadline'] ?? null;
                  $absLate  = ($absDLRaw && !$hasAbs  && time() > strtotime($absDLRaw));
                  $fpLate   = ($fpDLRaw  && !$hasFull && time() > strtotime($fpDLRaw));

                  $loaExists  = !empty($p['loa_exists']);

                  $assignAbs   = $p['assign_abs_url'] ?? site_url('admin/abstrak/detail/'.(int)($p['id_abstrak'] ?? 0));
                  $assignFp    = $p['assign_fp_url']  ?? site_url('admin/fullpaper');
                ?>
                  <tr class="kp-row <?= ($absLate||$fpLate)?'kp-row-overdue':'' ?>">
                    <!-- Author & Paper -->
                    <td class="kp-cell-author">
                      <div class="fw-semibold kp-clip-1"><?= esc($p['nama_lengkap'] ?? '-') ?></div>
                      <div class="small text-muted kp-clip-1"><?= esc($p['email'] ?? '-') ?></div>
                      <div class="mt-1 small text-blue-900 fw-semibold kp-clip-2">“<?= esc($p['judul'] ?? '-') ?>”</div>
                      <div class="small text-muted kp-clip-1"><?= esc($p['nama_kategori'] ?? '-') ?></div>
                      <?php if ($absLate || $fpLate): ?>
                        <div class="mt-1 d-flex flex-wrap gap-1">
                          <?php if ($absLate): ?>
                            <span class="badge bg-danger-subtle"><i class="bi bi-exclamation-triangle me-1"></i>Lewat batas abstrak (<?= esc($fmtDT($absDLRaw)) ?>)</span>
                          <?php endif; ?>
                          <?php if ($fpLate): ?>
                            <span class="badge bg-danger-subtle"><i class="bi bi-hourglass-split me-1"></i>Lewat batas full paper (<?= esc($fmtDT($fpDLRaw)) ?>)</span>
                          <?php endif; ?>
                        </div>
                      <?php endif; ?>
                    </td>

                    <!-- Reviewer Abstrak -->
                    <td class="text-center">
                      <div class="d-inline-flex flex-column align-items-center gap-1">
                        <span class="badge <?= $absAssigned>=1 ? 'bg-success-subtle' : 'bg-secondary-subtle' ?>">
                          <?= $absAssigned>=1 ? 'Sudah ditugaskan' : 'Belum ditugaskan' ?>
                        </span>
                        <?php if ($absAssigned < 1): ?>
                          <span class="tiny text-muted">butuh ≥1</span>
                        <?php endif; ?>
                      </div>
                    </td>

                    <!-- Reviewer Full Paper -->
                    <td class="text-center">
                      <?php if ($fpAssigned <= 0): ?>
                        <div class="d-inline-flex flex-column align-items-center gap-1">
                          <span class="badge bg-secondary-subtle">Belum ditugaskan</span>
                          <span class="badge bg-warning-subtle">Butuh 3 reviewer</span>
                        </div>
                      <?php else: ?>
                        <div class="d-inline-flex flex-column align-items-center gap-1">
                          <span class="badge bg-primary-subtle"><?= $fpAssigned ?>/3 ditugaskan</span>
                          <?php if ($fpMissing > 0): ?>
                            <span class="badge bg-warning-subtle">Kurang <?= $fpMissing ?></span>
                          <?php else: ?>
                            <span class="badge bg-success-subtle">Reviewer lengkap</span>
                          <?php endif; ?>
                          <span class="badge <?= $fpComplete>=3?'bg-success-subtle':'bg-warning-subtle' ?>">
                            <?= min(3,$fpComplete) ?>/3 review masuk
                          </span>
                        </div>
                      <?php endif; ?>
                    </td>

                    <!-- Status Abstrak -->
                    <td class="text-center">
                      <span class="badge <?= esc($absStat[1]) ?>"><?= esc($absStat[0]) ?></span>
                      <?php if(!$hasAbs): ?><div class="tiny text-muted">Belum upload</div><?php endif; ?>
                    </td>

                    <!-- Status Full -->
                    <td class="text-center">
                      <div class="d-inline-flex flex-column align-items-center gap-1">
                        <span class="badge <?= esc($fpStat[1]) ?>"><?= esc($fpStat[0]) ?></span>
                        <?php if(!$hasFull): ?><div class="tiny text-muted">Belum upload</div><?php endif; ?>
                      </div>
                    </td>

                    <!-- LOA -->
                    <td class="text-center">
                      <span class="badge <?= $loaExists?'bg-success-subtle':'bg-secondary-subtle' ?>">
                        <?= $loaExists ? 'Sudah' : 'Belum' ?>
                      </span>
                    </td>

                    <!-- Aksi (HANYA ASSIGN) -->
                    <td class="text-end">
                      <div class="d-flex flex-wrap justify-content-end gap-2">
                        <a class="btn btn-outline-primary btn-sm" href="<?= esc($assignAbs) ?>">
                          <i class="bi bi-person-plus me-1"></i>Assign Abstrak
                        </a>
                        <a class="btn btn-primary btn-sm" href="<?= esc($assignFp) ?>">
                          <i class="bi bi-people me-1"></i>Assign Full
                        </a>
                      </div>
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

<style>
/* ====== Selaraskan token & kanvas dengan INDEX ====== */
:root{
  --side-pad: clamp(1rem, 1.6vw, 1.6rem);
  --container-max: 1680px;

  --blue-50:#eff6ff; --blue-200:#bfdbfe; --blue-600:#2563eb;
  --blue-700:#1d4ed8; --blue-800:#1e40af; --blue-900:#1e3a8a;
}
.page-wrap-blue{ background:linear-gradient(180deg,var(--blue-50),#fff 40%); min-height:100vh; padding-top:72px; }
.container-xxl{ max-width:min(100%, var(--container-max)); padding-left:var(--side-pad)!important; padding-right:var(--side-pad)!important; }

/* HERO & Cards */
.hero-blue{
  background:radial-gradient(1200px 400px at 10% -20%,var(--blue-600) 0,var(--blue-700) 40%,var(--blue-800) 100%)!important;
  color:#fff!important; border-radius:16px; border:1px solid rgba(255,255,255,.15);
  box-shadow:0 12px 28px rgba(30,64,175,.10);
}
.hero-title{ font-weight:800; letter-spacing:.25px; font-size:1.6rem; }
.text-white-75{ color:rgba(255,255,255,.85)!important; }
.card-glass-plain{ backdrop-filter:blur(6px); background:rgba(255,255,255,.94); border-radius:14px; border:1px solid rgba(30,64,175,.10); }
.shadow-soft{ box-shadow:0 10px 24px rgba(30,64,175,.08); }

/* Badge & teks */
.bg-blue-soft{ background:var(--blue-200); color:var(--blue-900); border-radius:12px; padding:.4rem .6rem; font-weight:600; font-size:.85rem; }
.text-blue-900{ color:var(--blue-900)!important; }
.bg-success-subtle{   background:#d1fae5!important; color:#065f46!important; }
.bg-warning-subtle{   background:#fef3c7!important; color:#92400e!important; }
.bg-danger-subtle{    background:#fee2e2!important; color:#991b1b!important; }
.bg-secondary-subtle{ background:#f1f5f9!important; color:#475569!important; }
.bg-primary-subtle{   background:#dbeafe!important; color:var(--blue-700)!important; }
.tiny{ font-size:.78rem; }

/* Form (search) */
.form-control-soft{ border-radius:12px; border:1px solid rgba(30,64,175,.12); background:#fff; padding:.7rem .9rem; }
.form-control-soft:focus{ border-color: var(--blue-600); box-shadow: 0 0 0 .2rem rgba(37,99,235,.12); }

/* Buttons */
.btn{ border-radius:12px; font-weight:700; }
.btn-primary{ background:var(--blue-600); border-color:var(--blue-600); box-shadow:0 4px 12px rgba(37,99,235,.2); }
.btn-outline-primary{ border-color:var(--blue-600); color:var(--blue-600); }
.btn-outline-primary:hover{ background:var(--blue-600); color:#fff; }

/* ====== Table (diperlebar & diberi aksen) ====== */
.kp-table{ width:100%; table-layout:fixed; border-collapse:separate; border-spacing:0 8px; }
.kp-table thead th{
  background:#f8fafc!important; border:0; padding:.9rem .9rem; font-weight:800; color:#0f172a;
}
.kp-table tbody tr.kp-row{
  background:#fff; border:1px solid rgba(30,64,175,.12);
  box-shadow:0 8px 18px rgba(30,64,175,.08);
}
.kp-table tbody td{ padding:1rem .9rem; vertical-align:middle; }
.kp-table tbody tr.kp-row td:first-child{ border-radius:12px 0 0 12px; position:relative; }
.kp-table tbody tr.kp-row td:last-child{ border-radius:0 12px 12px 0; }

/* Left accent per-row */
.kp-table tbody tr.kp-row td:first-child::before{
  content:""; position:absolute; left:0; top:0; bottom:0; width:6px; border-radius:12px 0 0 12px;
  background:linear-gradient(180deg,var(--blue-600),var(--blue-800)); opacity:.8;
}

/* Overdue state warnain baris */
.kp-row-overdue td:first-child::before{ background:linear-gradient(180deg,#dc2626,#991b1b); opacity:.9; }
.kp-row-overdue{ border-color:rgba(220,38,38,.25)!important; }

/* Author cell & clamps */
.kp-cell-author .kp-clip-1{ white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.kp-cell-author .kp-clip-2{
  display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden;
  max-height:3rem;
}

/* Empty state */
.empty-hint{ color:#567; background:#f6f9ff; border:1px dashed rgba(30,64,175,.18); border-radius:12px; padding:.9rem 1.1rem; font-weight:600; }

/* Grid gutter sedikit diperlebar */
.row.g-2, .row.g-3{ --bs-gutter-x: 1.1rem; --bs-gutter-y: 1.1rem; }

@media (max-width: 767.98px){
  .hero-title{ font-size:1.35rem; }
  .kp-table thead{ display:none; }
  .kp-table tbody, .kp-table tr, .kp-table td{ display:block; width:100%; }
  .kp-table tbody tr.kp-row{ border-radius:12px; overflow:hidden; }
  .kp-table tbody td{ padding:.7rem .9rem; }
  .kp-table tbody tr.kp-row td:first-child::before{ width:4px; }
  .kp-table tbody tr.kp-row td + td{ border-top:1px dashed rgba(15,23,42,.07); }
  .text-end{ text-align:left!important; }
}
</style>