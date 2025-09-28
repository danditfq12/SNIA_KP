<?php
/**
 * File: app/Views/role/admin/kelola_paper/presenter.php
 * Style diselaraskan dengan halaman "Upload Full Paper"
 * Semua CSS di-scope ke #kelola-presenter agar tidak bentrok global.
 */
$title      = $title ?? 'Presenter';
$eventId    = (int)($eventId ?? 0);
$presenters = $presenters ?? [];
$q          = trim((string)($_GET['q'] ?? ''));

$mapAbsStatus = function (?string $s): array {
  $sl = strtolower((string)$s);
  if ($sl === 'diterima') return ['Diterima','bg-success-subtle'];
  if ($sl === 'ditolak')  return ['Ditolak','bg-danger-subtle'];
  return ['—','bg-secondary-subtle'];
};
$mapFpStatus = function (?string $s): array {
  $su = strtoupper((string)$s);
  return match ($su) {
    'ACCEPTED' => ['Diterima','bg-success-subtle'],
    'REVISION' => ['Revisi','bg-warning-subtle'],
    'REJECTED' => ['Ditolak','bg-danger-subtle'],
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

      <!-- HERO (selaras) -->
      <div class="hero-blue mb-3 d-flex justify-content-between align-items-start gap-3">
        <div>
          <h3 class="hero-title mb-1">
            <i class="bi bi-people me-2"></i>Presenter — <?= esc($presenters[0]['event_title'] ?? ('Event #'.$eventId)) ?>
          </h3>
          <div class="text-white-75 small">Kelola abstrak, full paper, dan dokumen.</div>
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

      <!-- TABEL PRESENTER -->
      <div class="card shadow-soft card-glass-plain">
        <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
          <span class="badge bg-blue-soft"><i class="bi bi-list-task"></i></span>
          <h6 class="mb-0 fw-semibold text-blue-900">Daftar Presenter</h6>
        </div>
        <div class="card-body pt-2">
          <?php if (empty($presenters)): ?>
            <div class="text-muted">Belum ada presenter pada event ini.</div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table align-middle kp-table">
                <thead>
                  <tr>
                    <th>Author & Paper</th>
                    <th class="text-center">Reviewer Abstrak</th>
                    <th class="text-center">Reviewer Full Paper</th>
                    <th class="text-center">Status Abstrak</th>
                    <th class="text-center">Status Full Paper</th>
                    <th class="text-center">LoA</th>
                    <th class="text-end">Aksi</th>
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
                  $loaUrl     = $p['loa_url'] ?? site_url('admin/dokumen?event_id='.$eventId.'&user_id='.(int)($p['id_user'] ?? 0).'&tipe=loa');

                  $viewAbsUrl  = $p['view_abs_url']   ?? site_url('admin/kelola-paper/abstract/'.(int)($p['id_abstrak'] ?? 0));
                  $viewFpUrl   = $p['view_fp_url']    ?? site_url('admin/kelola-paper/full/'.(int)($p['id_user'] ?? 0).'/'.$eventId);
                  $assignAbs   = $p['assign_abs_url'] ?? site_url('admin/abstrak/detail/'.(int)($p['id_abstrak'] ?? 0));
                  $assignFp    = $p['assign_fp_url']  ?? site_url('admin/fullpaper');

                  $loaClass = $loaExists ? 'btn-outline-dark' : 'btn-outline-secondary';
                  $loaTitle = $loaExists ? 'Lihat/kelola LoA' : 'LoA belum ada — klik untuk unggah/generate';
                ?>
                  <tr class="<?= ($absLate||$fpLate)?'kp-row-overdue':'' ?>">
                    <!-- Author & Paper -->
                    <td class="kp-cell-author">
                      <div class="fw-semibold kp-clip-1"><?= esc($p['nama_lengkap'] ?? '-') ?></div>
                      <div class="small text-muted kp-clip-1"><?= esc($p['email'] ?? '-') ?></div>
                      <div class="mt-1 small text-blue-900 fw-semibold kp-clip-2">“<?= esc($p['judul'] ?? '-') ?>”</div>
                      <div class="small text-muted kp-clip-1"><?= esc($p['nama_kategori'] ?? '-') ?></div>
                      <?php if ($absLate || $fpLate): ?>
                        <div class="mt-1">
                          <?php if ($absLate): ?>
                            <span class="badge bg-danger-subtle me-1"><i class="bi bi-exclamation-triangle me-1"></i>Lewat batas abstrak (<?= esc($fmtDT($absDLRaw)) ?>)</span>
                          <?php endif; ?>
                          <?php if ($fpLate): ?>
                            <span class="badge bg-danger-subtle"><i class="bi bi-hourglass-split me-1"></i>Lewat batas full paper (<?= esc($fmtDT($fpDLRaw)) ?>)</span>
                          <?php endif; ?>
                        </div>
                      <?php endif; ?>
                    </td>

                    <!-- Reviewer Abstrak -->
                    <td class="text-center">
                      <span class="badge <?= $absAssigned>=1 ? 'bg-success-subtle' : 'bg-secondary-subtle' ?>">
                        <?= $absAssigned>=1 ? 'Sudah ditugaskan' : 'Belum ditugaskan' ?>
                      </span>
                    </td>

                    <!-- Reviewer Full Paper -->
                    <td class="text-center">
                      <?php if ($fpAssigned <= 0): ?>
                        <span class="badge bg-secondary-subtle">Belum ditugaskan (3)</span>
                      <?php else: ?>
                        <div class="d-flex flex-column align-items-center gap-1">
                          <span class="badge bg-primary-subtle"><?= $fpAssigned ?>/3 ditugaskan</span>
                          <?php if ($fpMissing > 0): ?>
                            <span class="badge bg-warning-subtle">Kurang <?= $fpMissing ?> reviewer</span>
                          <?php else: ?>
                            <span class="badge bg-success-subtle">Reviewer lengkap</span>
                          <?php endif; ?>
                          <span class="badge <?= $fpComplete>=3?'bg-success-subtle':'bg-warning-subtle' ?>">
                            Baru <?= min(3,$fpComplete) ?>/3 review
                          </span>
                        </div>
                      <?php endif; ?>
                    </td>

                    <!-- Status Abstrak -->
                    <td class="text-center">
                      <span class="badge <?= esc($absStat[1]) ?>"><?= esc($absStat[0]) ?></span>
                      <?php if(!$hasAbs): ?><div class="small text-muted">Belum upload</div><?php endif; ?>
                    </td>

                    <!-- Status Full Paper -->
                    <td class="text-center">
                      <div class="d-flex flex-column align-items-center gap-1">
                        <span class="badge <?= esc($fpStat[1]) ?>"><?= esc($fpStat[0]) ?></span>
                        <?php if(!$hasFull): ?><div class="small text-muted">Belum upload</div><?php endif; ?>
                      </div>
                    </td>

                    <!-- LOA -->
                    <td class="text-center">
                      <span class="badge <?= $loaExists?'bg-success-subtle':'bg-secondary-subtle' ?>">
                        <?= $loaExists ? 'Sudah' : 'Belum' ?>
                      </span>
                    </td>

                    <!-- Aksi -->
                    <td class="text-end">
                      <div class="d-flex flex-wrap justify-content-end gap-2">
                        <a class="btn btn-outline-dark btn-sm <?= $hasAbs?'':'disabled' ?>"
                           href="<?= esc($viewAbsUrl) ?>"
                           <?= $hasAbs?'':'aria-disabled="true" tabindex="-1" role="button"' ?>>
                          <i class="bi bi-download me-1"></i>Abstrak
                        </a>
                        <a class="btn btn-outline-dark btn-sm <?= $hasFull?'':'disabled' ?>"
                           href="<?= esc($viewFpUrl) ?>"
                           <?= $hasFull?'':'aria-disabled="true" tabindex="-1" role="button"' ?>>
                          <i class="bi bi-download me-1"></i>Full
                        </a>
                        <a class="btn <?= $loaClass ?> btn-sm" href="<?= esc($loaUrl) ?>" title="<?= esc($loaTitle) ?>">
                          <i class="bi bi-patch-check me-1"></i>LoA
                        </a>
                        <a class="btn btn-outline-secondary btn-sm" href="<?= esc($assignAbs) ?>">
                          <i class="bi bi-person-plus me-1"></i>Assign Abstrak
                        </a>
                        <a class="btn btn-outline-secondary btn-sm" href="<?= esc($assignFp) ?>">
                          <i class="bi bi-person-plus me-1"></i>Assign Full
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
/* ==== Samakan token & rasa UI dg halaman Upload Full Paper ==== */
#kelola-presenter{
  --blue-50:#eff6ff; --blue-100:#dbeafe; --blue-200:#bfdbfe;
  --blue-300:#93c5fd; --blue-400:#60a5fa; --blue-500:#3b82f6;
  --blue-600:#2563eb; --blue-700:#1d4ed8; --blue-800:#1e40af; --blue-900:#1e3a8a;
  --side-pad:1rem; --gutter-x:1rem;
}
#kelola-presenter .row.g-2, 
#kelola-presenter .row.g-3,
#kelola-presenter .row.g-4{ --bs-gutter-x:var(--gutter-x); --bs-gutter-y:var(--gutter-x); }

/* HERO */
#kelola-presenter .hero-blue{
  background:radial-gradient(1200px 400px at 10% -20%,var(--blue-600) 0,var(--blue-700) 40%,var(--blue-800) 100%) !important;
  color:#fff !important; border-radius:16px;
  border:1px solid rgba(255,255,255,.15); box-shadow:0 12px 28px rgba(30,64,175,.10);
  padding:1.6rem !important;
}
#kelola-presenter .hero-title{ font-weight:800; letter-spacing:.25px; font-size:1.45rem; }
#kelola-presenter .text-white-75{ color:rgba(255,255,255,.85)!important; }

/* Cards (selaras) */
#kelola-presenter .card-glass-plain{
  backdrop-filter:blur(6px); background:rgba(255,255,255,.94);
  border-radius:14px; border:1px solid rgba(30,64,175,.10);
}
#kelola-presenter .shadow-soft{ box-shadow:0 10px 24px rgba(30,64,175,.08); }
#kelola-presenter .card-header{ padding:1rem 1rem .45rem 1rem!important; }
#kelola-presenter .card-body{   padding:1.05rem!important; }

/* Badge & teks */
#kelola-presenter .bg-blue-soft{ background:var(--blue-200); color:var(--blue-800); border-radius:12px; padding:.4rem .6rem; font-weight:600; font-size:.85rem; }
#kelola-presenter .text-blue-900{ color:var(--blue-900)!important; }
#kelola-presenter .bg-success-subtle{   background:#d1fae5!important; color:#065f46!important; }
#kelola-presenter .bg-warning-subtle{   background:#fef3c7!important; color:#92400e!important; }
#kelola-presenter .bg-danger-subtle{    background:#fee2e2!important; color:#991b1b!important; }
#kelola-presenter .bg-secondary-subtle{ background:#f1f5f9!important; color:#475569!important; }
#kelola-presenter .bg-primary-subtle{   background:#dbeafe!important; color:var(--blue-700)!important; }

/* Form (search) */
#kelola-presenter .form-control-soft{ border-radius:12px; border:1px solid rgba(30,64,175,.12); background:#fff; }
#kelola-presenter .form-control-soft:focus{ border-color: var(--blue-400); box-shadow: 0 0 0 .2rem rgba(59,130,246,.12); }

/* Button sizing sama dengan halaman contoh */
#kelola-presenter .btn{ font-weight:600; letter-spacing:.25px; border-radius:10px; font-size:.95rem; padding:.55rem 1rem; }
#kelola-presenter .btn-primary{ background:var(--blue-600); border-color:var(--blue-600); box-shadow:0 4px 12px rgba(37,99,235,.2); }
#kelola-presenter .btn-primary:hover{ background:var(--blue-700); border-color:var(--blue-700); }

/* Table (rapat) */
#kelola-presenter .kp-table{ width:100%; table-layout:fixed; border-collapse:separate; border-spacing:0; }
#kelola-presenter .kp-table thead th{
  background-color:#f8fafc!important; border-bottom
