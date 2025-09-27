<?php
$title        = $title        ?? 'Abstrak';
$uploadEvents = $uploadEvents ?? [];
$history      = $history      ?? [];

$fmtDate = function($s){ return $s ? date('d M Y', strtotime($s)) : '-'; };
$fmtDT   = function($s){ return $s ? date('d M Y H:i', strtotime($s)) : '-'; };
$formatLabel = function($f){
  $f = strtolower((string)$f);
  return $f === 'both' ? 'Hybrid' : ucfirst($f ?: '-');
};
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill page-wrap-blue">
    <div class="container-xxl px-3 px-md-4 py-4">

      <div class="hero-blue card-glass mb-3 p-3 p-md-4 d-flex justify-content-between align-items-start gap-3">
        <div>
          <h3 class="hero-title mb-1"><i class="bi bi-journal-text me-2"></i>Abstrak</h3>
          <div class="text-white-75 small">Upload & pantau status abstrak</div>
        </div>
        <div class="d-none d-md-block text-end">
          <div class="text-white-75 small">Hari ini</div>
          <div class="fw-semibold text-white"><?= date('d M Y') ?></div>
        </div>
      </div>

      <!-- Abstrak perlu upload -->
      <div class="card shadow-soft card-glass-plain mb-4">
        <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
          <span class="badge bg-blue-soft"><i class="bi bi-upload"></i></span>
          <h5 class="mb-0 fw-semibold text-blue-900">Abstrak perlu upload</h5>
        </div>
        <div class="card-body">
          <?php if (empty($uploadEvents)): ?>
            <div class="text-muted">Tidak ada event yang membutuhkan upload abstrak saat ini.</div>
          <?php else: ?>
            <div class="row g-3">
              <?php foreach ($uploadEvents as $row): ?>
              <div class="col-12 col-md-6 col-xl-4">
                <div class="event-card h-100 p-3">
                  <div class="d-flex align-items-start justify-content-between mb-2">
                    <h6 class="mb-0 text-blue-900"><?= esc($row['title'] ?? '-') ?></h6>
                    <span class="badge bg-<?= esc($row['status_badge']) ?>-subtle text-<?= esc($row['status_badge']) ?>">
                      <?= esc($row['status_label']) ?>
                    </span>
                  </div>

                  <div class="small text-muted mb-2">
                    Tanggal Event: <strong class="text-blue-900"><?= esc($fmtDate($row['event_date'] ?? null)) ?></strong><br>
                    Deadline Abstrak: <strong class="text-blue-900"><?= esc($fmtDT($row['abstract_deadline'] ?? null)) ?></strong><br>
                    Format: <strong class="text-blue-900"><?= esc($formatLabel($row['format'] ?? '')) ?></strong>
                  </div>

                  <?php if (!empty($row['hint'])): ?>
                    <div class="text-muted small mb-2"><?= esc($row['hint']) ?></div>
                  <?php endif; ?>

                  <div class="d-flex gap-2">
                    <a class="btn btn-primary flex-fill" href="<?= site_url('presenter/abstrak/create/'.(int)$row['event_id']) ?>">
                      <i class="bi bi-upload"></i> Upload Abstrak
                    </a>
                    <?php if (!empty($row['last_abs_id'])): ?>
                      <a class="btn btn-outline-secondary flex-fill" href="<?= site_url('presenter/abstrak/detail/'.(int)$row['last_abs_id']) ?>">
                        <i class="bi bi-eye"></i> Lihat Terakhir
                      </a>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Riwayat Abstrak -->
      <div class="card shadow-soft card-glass-plain mb-4">
        <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
          <span class="badge bg-blue-soft"><i class="bi bi-clock-history"></i></span>
          <h5 class="mb-0 fw-semibold text-blue-900">Riwayat Abstrak</h5>
        </div>
        <div class="card-body">
          <?php if (empty($history)): ?>
            <div class="text-muted">Belum ada riwayat abstrak.</div>
          <?php else: ?>
            <div class="row g-3">
              <?php foreach ($history as $h): ?>
              <div class="col-12 col-md-6 col-xl-4">
                <div class="event-card h-100 p-3">
                  <div class="d-flex align-items-start justify-content-between mb-1">
                    <h6 class="mb-0 text-blue-900"><?= esc($h['judul']) ?></h6>
                    <span class="badge bg-<?= esc($h['status_badge']) ?>-subtle text-<?= esc($h['status_badge']) ?>">
                      <?= esc($h['status_label']) ?>
                    </span>
                  </div>

                  <div class="small text-muted mb-2"><?= esc($h['nama_kategori'] ?? '-') ?></div>

                  <div class="small text-muted mb-2">
                    Event: <strong class="text-blue-900"><?= esc($h['event_title'] ?? '-') ?></strong><br>
                    Tanggal Event: <strong class="text-blue-900"><?= esc($fmtDate($h['event_date'] ?? null)) ?></strong>
                  </div>

                  <?php if (!empty($h['status_hint'])): ?>
                    <?php
                      $cls = (($h['status'] ?? '') === 'diterima') ? 'text-success' :
                             ((($h['status'] ?? '') === 'ditolak') ? 'text-danger' : 'text-muted');
                    ?>
                    <div class="small mb-2 <?= $cls ?>"><?= esc($h['status_hint']) ?></div>
                  <?php endif; ?>

                  <div class="small text-muted">Dikirim: <strong class="text-blue-900"><?= esc($fmtDT($h['tanggal_upload'] ?? null)) ?></strong></div>

                  <div class="mt-2 d-grid gap-2">
                    <a class="btn btn-outline-primary btn-sm" href="<?= site_url('presenter/abstrak/detail/'.(int)$h['id_abstrak']) ?>">
                      <i class="bi bi-eye"></i> Detail
                    </a>

                    <?php if (($h['status'] ?? '') === 'ditolak'): ?>
                      <a class="btn btn-danger btn-sm" href="<?= site_url('presenter/abstrak/create/'.(int)$h['event_id']) ?>">
                        <i class="bi bi-upload"></i> Upload Ulang Abstrak
                      </a>
                    <?php endif; ?>

                    <!-- sesuai permintaan: TIDAK ADA tombol Batalkan di index -->
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<style>
:root{
  --blue-50:#eff6ff; --blue-100:#dbeafe; --blue-200:#bfdbfe;
  --blue-300:#93c5fd; --blue-400:#60a5fa; --blue-500:#3b82f6;
  --blue-600:#2563eb; --blue-700:#1d4ed8; --blue-800:#1e40af; --blue-900:#1e3a8a;

  --side-pad: 1rem;   /* padding kiri–kanan minimum */
  --gutter-x: 1rem;   /* jarak antar kolom */
}

body{
  font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  font-size:14.6px;       /* sedikit lebih kecil */
  line-height:1.5;
}

/* ===== Layout wrapper ===== */
.page-wrap-blue{
  background:linear-gradient(180deg,var(--blue-50),#fff 40%);
  min-height:100vh;
  padding-top:72px; /* seragam */
}

/* ===== Container — lebar tetap, padding kiri–kanan 1rem ===== */
.container-xxl{
  max-width:1400px;
  padding-left:var(--side-pad) !important;
  padding-right:var(--side-pad) !important;
}

/* ===== Gutter grid (seragam) ===== */
.row.g-3, .row.g-4{ --bs-gutter-x: var(--gutter-x); --bs-gutter-y: var(--gutter-x); }

/* ===== HERO (header biru) ===== */
.hero-blue{
  background:radial-gradient(1200px 400px at 10% -20%,var(--blue-600) 0,var(--blue-700) 40%,var(--blue-800) 100%) !important;
  color:#fff !important;
  border-radius:16px;
  border:1px solid rgba(255,255,255,.15);
  box-shadow:0 12px 28px rgba(30,64,175,.10);
  padding:1.6rem !important;
  margin-bottom:1.25rem !important;
}
/* Matikan efek glass jika hero terlanjur diberi .card-glass / .card-glass-plain */
.hero-blue.card-glass,
.hero-blue.card-glass-plain{
  backdrop-filter:none !important;
  background:radial-gradient(1200px 400px at 10% -20%,var(--blue-600) 0,var(--blue-700) 40%,var(--blue-800) 100%) !important;
  border:1px solid rgba(255,255,255,.15) !important;
  box-shadow:0 12px 28px rgba(30,64,175,.10) !important;
}
.hero-title{ font-weight:800; letter-spacing:.25px; font-size:1.45rem; }
.text-white-75{ color:rgba(255,255,255,.85)!important; }

/* ===== Card / Glass ===== */
.card-glass{
  backdrop-filter: blur(2px);
  background: linear-gradient(180deg, rgba(255,255,255,.18), rgba(255,255,255,.10));
  border-radius:16px; border:1px solid rgba(255,255,255,.28);
}
.card-glass-plain{
  backdrop-filter:blur(6px);
  background:rgba(255,255,255,.94);
  border-radius:14px;
  border:1px solid rgba(30,64,175,.10);
}
.shadow-soft{ box-shadow:0 10px 24px rgba(30,64,175,.08); }

.card-header{ padding:1rem 1rem .45rem 1rem !important; }
.card-body{   padding:1.05rem !important; }

/* ===== Badge kecil lembut ===== */
.bg-blue-soft{ background:var(--blue-200); color:var(--blue-800); border-radius:12px; padding:.4rem .6rem; font-weight:600; font-size:.85rem; }
.bg-success-subtle{   background:#d1fae5!important; color:#065f46!important; }
.bg-warning-subtle{   background:#fef3c7!important; color:#92400e!important; }
.bg-danger-subtle{    background:#fee2e2!important; color:#991b1b!important; }
.bg-info-subtle{      background:#e0f2fe!important; color:#0c4a6e!important; }
.bg-secondary-subtle{ background:#f1f5f9!important; color:#475569!important; }
.bg-primary-subtle{   background:#dbeafe!important; color:var(--blue-700)!important; }
.text-blue-900{ color:var(--blue-900)!important; }

/* ===== Event card (box on + compact) ===== */
.event-card{
  background:linear-gradient(180deg,#fff,rgba(255,255,255,.96));
  border:1px solid rgba(30,64,175,.10);
  border-radius:14px;
  box-shadow:0 10px 22px rgba(30,64,175,.08);
  padding:14px;
}
.event-card h6{ font-size:1.05rem; margin-bottom:.25rem; }
.event-card .small{ font-size:.92rem; }
.event-card .badge{ padding:.35rem .6rem; font-size:.78rem; border-radius:10px; }
.event-card .btn{ border-radius:10px; padding:.58rem 1rem; font-size:.95rem; }
.opacity-90{ opacity:.92; }

/* ===== Table (kontributor / umum) ===== */
.table{ font-size:.95rem; margin-bottom:0; }
.table thead th{
  background-color:var(--blue-50)!important;
  border-bottom:1px solid var(--blue-200);
  font-weight:600; color:var(--blue-900);
  font-size:.85rem; text-transform:uppercase; letter-spacing:.4px;
  padding:.75rem 1rem;
}
.table tbody tr{ border-bottom:1px solid rgba(30,64,175,.08); }
.table tbody td{
  padding:.8rem 1rem; vertical-align:middle; border-top:none; font-size:.95rem;
}
.table-responsive{ border:1px solid rgba(30,64,175,.08); border-radius:12px; overflow:hidden; }

/* ===== List (progress) ===== */
.list-group-item{
  background:transparent!important;
  border-left:none!important; border-right:none!important;
  font-size:1rem; padding:.8rem 0; font-weight:500;
}
.list-group-item:first-child{ border-top:none!important; }
.list-group-item:last-child{  border-bottom:none!important; }
.list-group-item strong{ font-weight:600; font-size:.98rem; }

/* ===== Buttons ===== */
.btn{
  font-weight:600; letter-spacing:.25px;
  border-radius:10px; font-size:.95rem; padding:.55rem 1rem;
}
.btn-sm{ padding:.42rem .8rem; font-size:.86rem; }
.btn-lg{ padding:.85rem 1.5rem; font-size:1.06rem; }
.btn-primary{ background:var(--blue-600); border-color:var(--blue-600); box-shadow:0 4px 12px rgba(37,99,235,.2); }
.btn-info{    background:#06b6d4; border-color:#06b6d4; box-shadow:0 4px 12px rgba(6,182,212,.2); }
.btn-success{ background:#059669; border-color:#059669; box-shadow:0 4px 12px rgba(5,150,105,.2); }
.btn-warning{ background:#d97706; border-color:#d97706; box-shadow:0 4px 12px rgba(217,119,6,.2); }
.btn-danger{  background:#dc2626; border-color:#dc2626; box-shadow:0 4px 12px rgba(220,38,38,.2); }

.d-grid.gap-2{ gap:1rem !important; }

/* ===== Helpers ===== */
.text-muted{ color:#6b7280!important; font-weight:500; font-size:.9rem; }
.text-muted.small{ font-size:.84rem!important; }
.fw-semibold{ font-weight:600!important; }
.fw-medium{   font-weight:500!important; }
a{ text-decoration:none; }
a.text-primary{ color:var(--blue-600)!important; font-weight:500; }

/* ===== Card glow (notif sukses) ===== */
.card-glow{ box-shadow:0 8px 24px rgba(16,185,129,.18); border:1px solid rgba(16,185,129,.2); }

/* ===== Overlay kunci form (dipakai di Kirim Abstrak) ===== */
.form-lock-overlay{
  position:absolute; inset:0;
  background: rgba(255,255,255,.6);
  border-radius:16px;
  border:1px dashed rgba(30,64,175,.25);
  z-index: 2; text-align:center;
}

/* ===== Responsive ===== */
@media (max-width:575.98px){
  .container-xxl{ padding-left:1rem !important; padding-right:1rem !important; }
  .hero-blue{ border-radius:14px; padding:1.25rem!important; margin-bottom:1rem!important; }
  .hero-title{ font-size:1.25rem; }
  .card-body{  padding:.9rem!important; }
  .card-header{ padding:.9rem .9rem .4rem .9rem!important; }
  .table thead th, .table tbody td{ padding:.6rem .75rem; font-size:.85rem; }
  .list-group-item{ font-size:.98rem; padding:.7rem 0; }
  .badge{ padding:.38rem .6rem; font-size:.74rem; }
  .event-card{ padding:12px; }
  .event-card h6{ font-size:1rem; }
  .event-card .small{ font-size:.9rem; }
}
@media (min-width:576px) and (max-width:767.98px){
  .container-xxl{ padding-left:1rem !important; padding-right:1rem !important; }
  .hero-title{ font-size:1.35rem; }
  .card-body{  padding:1rem!important; }
}
@media (min-width:768px) and (max-width:991.98px){
  .container-xxl{ padding-left:1rem !important; padding-right:1rem !important; }
  .hero-title{ font-size:1.45rem; }
}
@media (min-width:992px){
  .hero-blue{ padding:1.7rem!important; }
  .card-body{  padding:1.05rem!important; }
  .card-header{ padding:1rem 1rem .45rem 1rem!important; }
}
</style>