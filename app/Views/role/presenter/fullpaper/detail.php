<?php
$title       = $title       ?? 'Detail Full Paper';
$event       = $event       ?? [];
$abs         = $abs         ?? [];
$status      = strtoupper($status ?? 'NONE');
$meta        = $status_meta ?? ['badge'=>'secondary','label'=>'-','hint'=>'-'];
$path        = trim((string)($path ?? ''));
$createdAt   = $created_at  ?? null;
$reviewedAt  = $reviewed_at ?? null;   // (tetap diparsing, tapi tidak ditampilkan di Info FP)
$decisionAt  = $decision_at ?? null;   // (tetap diparsing, tapi tidak ditampilkan di Info FP)
$notes       = trim((string)($notes ?? ''));
$absMeta     = $abs_meta    ?? ['badge'=>'secondary','label'=>'-'];
$isOpen      = (bool)($is_open ?? false);
$canReupload = (bool)($can_reupload ?? false);
$canUpload   = (bool)($can_upload ?? false);
$eventId     = (int)($event_id ?? 0);

$fmt     = fn($s)=> $s ? date('d M Y H:i', strtotime($s)) : '-';
$fmtDate = fn($s)=> $s ? date('d M Y', strtotime($s)) : '-';
$fmtDT   = fn($s)=> $s ? date('d M Y H:i', strtotime($s)) : '-';
$formatLabel = function($f){
  $f = strtolower((string)$f);
  return $f === 'both' ? 'Hybrid' : ucfirst($f ?: '-');
};

$downloadUrl = $path !== '' ? site_url('presenter/fullpaper/download/'.rawurlencode($path)) : '';

// ===== Komentar reviewer (dipindah ke kanan)
$possibleCommentKeys = [
  'notes','komentar_reviewer','catatan_reviewer','reviewer_comment',
  'reviewer_comments','review_note','review_notes','alasan_ditolak'
];
$reviewComments = [];
foreach ($possibleCommentKeys as $k) {
  $val = $$k ?? ($abs[$k] ?? null);
  if (!empty($val)) {
    if (is_array($val)) {
      foreach ($val as $c) { if (trim((string)$c) !== '') $reviewComments[] = (string)$c; }
    } else {
      $parts = preg_split('/\r\n|\r|\n/', (string)$val);
      foreach ($parts as $c) { if (trim($c) !== '') $reviewComments[] = $c; }
    }
  }
}
$reviewComments = array_values(array_unique(array_map('trim', $reviewComments)));

// ===== Kategori abstrak (fallback agar tidak kosong)
$absKategori = $abs['nama_kategori']
  ?? $abs['kategori']
  ?? $abs['kategori_nama']
  ?? $abs['category_name']
  ?? $abs['kategori_abstrak']
  ?? '-';
$absKategori = (trim((string)$absKategori) === '') ? '-' : $absKategori;
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill page-wrap-blue">
    <div class="container-xxl px-3 px-md-4 py-4">

      <!-- HERO -->
      <div class="hero-blue card-glass-plain mb-3 p-3 p-md-4 d-flex justify-content-between align-items-start gap-3">
        <div>
          <h3 class="hero-title mb-1">
            <i class="bi bi-file-earmark-text me-2"></i>Detail Full Paper
          </h3>
          <div class="text-white-75 small">
            <?= esc($event['title'] ?? '-') ?>
            <?php if(!empty($abs['judul'])): ?>
              <span class="d-inline-block ms-2 opacity-90">•</span>
              <span class="fw-semibold opacity-90 ms-1"><?= esc($abs['judul']) ?></span>
            <?php endif; ?>
          </div>
        </div>
        <div class="text-end">
          <a href="javascript:history.back()" class="btn btn-light btn-sm shadow-sm">
            <i class="bi bi-arrow-left"></i> Kembali
          </a>
        </div>
      </div>

      <div class="row g-3">
        <!-- KIRI -->
        <div class="col-12 col-xl-8">
          <!-- Informasi Full Paper (tanpa Status/Direview/Keputusan) -->
          <div class="card shadow-soft card-glass-plain mb-3 card-accent">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
              <span class="badge bg-blue-soft"><i class="bi bi-info-circle"></i></span>
              <h6 class="mb-0 fw-semibold text-blue-900">Informasi Full Paper</h6>
            </div>
            <div class="card-body">
              <ul class="meta-list mb-3">
                <li>
                  <span>Diunggah</span>
                  <strong class="text-blue-900"><?= esc($fmt($createdAt)) ?></strong>
                </li>
              </ul>

              <div class="pt-3 d-flex flex-wrap gap-2 border-top subtle-divider">
                <?php if ($downloadUrl): ?>
                  <a class="btn btn-outline-secondary" href="<?= $downloadUrl ?>">
                    <i class="bi bi-download"></i> Unduh File
                  </a>
                <?php endif; ?>

                <?php if ($canReupload): ?>
                  <a class="btn btn-danger" href="<?= site_url('presenter/fullpaper/create/'.$eventId) ?>">
                    <i class="bi bi-upload"></i> Upload Ulang (Revisi)
                  </a>
                <?php elseif ($canUpload): ?>
                  <a class="btn btn-primary" href="<?= site_url('presenter/fullpaper/create/'.$eventId) ?>">
                    <i class="bi bi-upload"></i> Upload Full Paper
                  </a>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- Informasi Abstrak di Event Ini -->
          <div class="card shadow-soft card-glass-plain mb-3">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center justify-content-between">
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-blue-soft"><i class="bi bi-journal-text"></i></span>
                <h6 class="mb-0 fw-semibold text-blue-900">Informasi Abstrak di Event Ini</h6>
              </div>
              <span class="badge bg-<?= esc($absMeta['badge']) ?>"><?= esc($absMeta['label']) ?></span>
            </div>
            <div class="card-body">
              <?php if (empty($abs)): ?>
                <div class="empty-hint"><i class="bi bi-info-circle me-1"></i> Belum ada data abstrak.</div>
              <?php else: ?>
                <ul class="meta-list mb-3">
                  <li><span>Judul</span><strong class="text-blue-900"><?= esc($abs['judul'] ?? '-') ?></strong></li>
                  <li><span>Kategori</span><strong class="text-blue-900"><?= esc($absKategori) ?></strong></li>
                  <li><span>Tanggal Unggah</span><strong class="text-blue-900"><?= esc($fmtDT($abs['tanggal_upload'] ?? null)) ?></strong></li>
                </ul>
                <div class="d-flex gap-2 pt-2 border-top subtle-divider">
                  <a class="btn btn-outline-primary" href="<?= site_url('presenter/abstrak/detail/'.(int)($abs['id_abstrak'] ?? 0)) ?>">
                    <i class="bi bi-eye"></i> Lihat Detail Abstrak
                  </a>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Info Event -->
          <div class="card shadow-soft card-glass-plain card-accent">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
              <span class="badge bg-blue-soft"><i class="bi bi-calendar-week"></i></span>
              <h6 class="mb-0 fw-semibold text-blue-900">Info Event</h6>
            </div>
            <div class="card-body">
              <ul class="meta-list">
                <li><span>Tanggal Event</span><strong class="text-blue-900"><?= esc($fmtDate($event['event_date'] ?? null)) ?></strong></li>
                <li><span>Deadline Full Paper</span><strong class="text-blue-900"><?= esc($fmtDT($event['full_paper_deadline'] ?? null)) ?></strong></li>
                <li><span>Lokasi</span><strong class="text-blue-900"><?= esc($event['location'] ?? '-') ?></strong></li>
                <li><span>Format</span><strong class="text-blue-900"><?= esc($formatLabel($event['format'] ?? '')) ?></strong></li>
              </ul>
              <div class="mt-3">
                <?php if ($isOpen): ?>
                  <span class="badge bg-success">Pengumpulan FP: OPEN</span>
                <?php else: ?>
                  <span class="badge bg-secondary">Pengumpulan FP: Closed</span>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

        <!-- KANAN -->
        <div class="col-12 col-xl-4">
          <!-- Status (sticky) -->
          <div class="card shadow-soft card-glass-plain sticky-card mb-3">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
              <span class="badge bg-blue-soft"><i class="bi bi-flag"></i></span>
              <h6 class="mb-0 fw-semibold text-blue-900">Status</h6>
            </div>
            <div class="card-body">
              <?php if ($status === 'NONE'): ?>
                <div class="alert bg-blue-soft-2 text-blue-900 border-0 fw-semibold mb-0">
                  <i class="bi bi-info-circle me-1"></i> Belum ada Full Paper yang diunggah.
                </div>
              <?php elseif ($status === 'UPLOADED'): ?>
                <div class="alert bg-blue-soft-2 text-blue-900 border-0 fw-semibold mb-0">
                  <i class="bi bi-hourglass-split me-1"></i> Full Paper Anda sedang diproses oleh reviewer.
                </div>
              <?php elseif ($status === 'REVISION'): ?>
                <div class="alert alert-warning fw-semibold mb-0">
                  <i class="bi bi-arrow-repeat me-1"></i> Perlu revisi. Silakan unggah ulang sesuai catatan reviewer.
                </div>
              <?php elseif ($status === 'REJECTED'): ?>
                <div class="alert alert-danger fw-semibold mb-0">
                  <i class="bi bi-x-octagon me-1"></i> Full Paper ditolak.
                </div>
              <?php elseif ($status === 'ACCEPTED'): ?>
                <div class="alert alert-success fw-semibold mb-0">
                  <i class="bi bi-check2-circle me-1"></i> Full Paper diterima.
                </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Komentar Reviewer (dipindah ke kanan) -->
          <div class="card shadow-soft card-glass-plain sticky-card">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
              <span class="badge bg-blue-soft"><i class="bi bi-chat-left-dots"></i></span>
              <h6 class="mb-0 fw-semibold text-blue-900">Komentar Reviewer</h6>
            </div>
            <div class="card-body">
              <?php if (!empty($reviewComments)): ?>
                <ul class="list-unstyled m-0">
                  <?php foreach ($reviewComments as $c): if (trim($c)==='') continue; ?>
                    <li class="mb-2 d-flex align-items-start">
                      <i class="bi bi-dot fs-4 me-1 opacity-75"></i>
                      <span><?= nl2br(esc($c)) ?></span>
                    </li>
                  <?php endforeach; ?>
                </ul>
              <?php else: ?>
                <?php if ($status === 'UPLOADED'): ?>
                  <div class="empty-hint">
                    <i class="bi bi-hourglass-split me-1"></i> Belum ada komentar dari reviewer.
                  </div>
                <?php elseif ($status === 'REJECTED'): ?>
                  <div class="empty-hint">
                    <i class="bi bi-info-circle me-1"></i> Ditolak, namun komentar tidak tersedia.
                  </div>
                <?php else: ?>
                  <div class="empty-hint">
                    <i class="bi bi-info-circle me-1"></i> Tidak ada komentar tersimpan.
                  </div>
                <?php endif; ?>
              <?php endif; ?>
            </div>
          </div>
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
  --side-pad: clamp(1rem, 2.3vw, 2.2rem);
  --gutter-x: 1.05rem;
}

body{ font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size:15.5px; line-height:1.6; }

.page-wrap-blue{
  min-height:100vh; padding-top:72px; position:relative;
  background:
    radial-gradient(900px 300px at 20% -10%, rgba(59,130,246,.17), rgba(59,130,246,0) 60%),
    radial-gradient(900px 300px at 80% 110%, rgba(59,130,246,.14), rgba(59,130,246,0) 70%),
    linear-gradient(180deg, var(--blue-50), #fff 40%);
}
.page-wrap-blue::after{
  content:""; position:absolute; inset:0; pointer-events:none; opacity:.18;
  background-image: radial-gradient(#93c5fd 1px, transparent 1px), radial-gradient(#bfdbfe 1px, transparent 1px);
  background-position: 0 0, 20px 20px; background-size: 40px 40px, 40px 40px;
}

.container-xxl{ max-width:min(100%, 1560px); padding-left:var(--side-pad)!important; padding-right:var(--side-pad)!important; margin-inline:auto; }
.row.g-3, .row.g-4{ --bs-gutter-x: var(--gutter-x); --bs-gutter-y: var(--gutter-x); }

.hero-blue{
  background:radial-gradient(1100px 360px at 10% -10%,var(--blue-600) 0,var(--blue-700) 45%,var(--blue-800) 100%) !important;
  color:#fff!important; border-radius:16px; border:1px solid rgba(255,255,255,.15);
  box-shadow:0 12px 28px rgba(30,64,175,.10);
}
.hero-title{ font-weight:800; letter-spacing:.25px; font-size:1.5rem; }
.text-white-75{ color:rgba(255,255,255,.85)!important; }

.card-glass-plain{ backdrop-filter:blur(6px); background:rgba(255,255,255,.96); border-radius:14px; border:1px solid rgba(30,64,175,.10); }
.shadow-soft{ box-shadow:0 10px 24px rgba(30,64,175,.08); }
.card-accent{ position:relative; overflow:hidden; }
.card-accent::before{ content:""; position:absolute; left:0; top:0; bottom:0; width:4px; border-radius:4px 0 0 4px; background:linear-gradient(180deg,var(--blue-500),var(--blue-700)); opacity:.75; }
.subtle-divider{ border-color: rgba(30,64,175,.12)!important; }

.meta-list{ list-style:none; padding-left:0; margin:0; }
.meta-list li{ display:flex; align-items:center; justify-content:space-between; gap:.75rem; padding:.45rem 0; }
.meta-list li span{ color:#6b7280; }
.text-blue-900{ color:var(--blue-900)!important; }

.chip{ display:inline-flex; align-items:center; padding:.28rem .6rem; font-size:.88rem; border-radius:999px; background:#eef3ff; color:#1e3a8a; border:1px solid rgba(30,64,175,.15); font-weight:600; }
.chip.alt{ background:#f1f5ff; color:#244aa4; }

.bg-blue-soft{ background:var(--blue-200); color:var(--blue-800); border-radius:12px; padding:.5rem .7rem; font-weight:600; font-size:.9rem; }

.btn{ font-weight:700; border-radius:10px; font-size:.98rem; padding:.62rem 1.05rem; }
.btn-sm{ padding:.5rem .9rem; font-size:.92rem; }
.btn-primary{ background:var(--blue-600); border-color:var(--blue-600); box-shadow:0 4px 12px rgba(37,99,235,.2); }

.bg-blue-soft-2{ background:linear-gradient(180deg,#eef4ff,#eaf2ff); }

.sticky-card{ position:sticky; top:84px; }

@media (max-width:575.98px){
  .container-xxl{ padding-left:1rem!important; padding-right:1rem!important; }
  .hero-title{ font-size:1.3rem; }
}
</style>
