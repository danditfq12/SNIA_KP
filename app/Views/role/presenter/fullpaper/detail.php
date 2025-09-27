<?php
$title       = $title       ?? 'Detail Full Paper';
$event       = $event       ?? [];
$abs         = $abs         ?? [];
$status      = strtoupper($status ?? 'NONE');
$meta        = $status_meta ?? ['badge'=>'secondary','label'=>'-','hint'=>'-'];
$path        = trim((string)($path ?? ''));
$createdAt   = $created_at  ?? null;
$reviewedAt  = $reviewed_at ?? null;
$decisionAt  = $decision_at ?? null;
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

// URL preview & unduh
$previewUrl  = $path !== '' ? site_url('presenter/fullpaper/download/'.rawurlencode($path).'?inline=1') : '';
$downloadUrl = $path !== '' ? site_url('presenter/fullpaper/download/'.rawurlencode($path)) : '';
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill page-wrap-blue">
    <div class="container-xxl px-3 px-md-4 py-4">

      <!-- HERO -->
      <div class="hero-blue card-glass mb-3 d-flex justify-content-between align-items-start gap-3">
        <div>
          <h3 class="hero-title mb-1">
            <i class="bi bi-file-earmark-text me-2"></i>Detail Full Paper
          </h3>
          <div class="text-white-75 small"><?= esc($event['title'] ?? '-') ?></div>
        </div>
        <div class="d-flex align-items-center gap-2">
          <span class="badge bg-<?= esc($meta['badge']) ?>"><?= esc($meta['label']) ?></span>
          <span class="badge bg-<?= esc($absMeta['badge']) ?>"><?= esc($absMeta['label']) ?></span>
        </div>
      </div>

      <div class="row g-3">
        <!-- LEFT -->
        <div class="col-12 col-lg-8">
          <!-- INFO FP -->
          <div class="card shadow-soft card-glass-plain mb-3">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center justify-content-between">
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-blue-soft"><i class="bi bi-info-circle"></i></span>
                <h6 class="mb-0 fw-semibold text-blue-900">Informasi Full Paper</h6>
              </div>
              <!-- hanya tombol kembali di kanan -->
              <a href="javascript:history.back()" class="btn btn-sm btn-outline-secondary rounded-3">
                <i class="bi bi-arrow-left me-1"></i> Kembali
              </a>
            </div>
            <div class="card-body">
              <div class="mb-2">
                <div class="text-muted small">Status</div>
                <span class="badge bg-<?= esc($meta['badge']) ?>"><?= esc($meta['label']) ?></span>
                <?php if (!empty($meta['hint'])): ?>
                  <span class="text-muted small ms-2"><?= esc($meta['hint']) ?></span>
                <?php endif; ?>
              </div>

              <div class="row g-2 mt-1 small">
                <div class="col-md-4">
                  <div class="text-muted">Diunggah</div>
                  <div class="fw-semibold"><?= esc($fmt($createdAt)) ?></div>
                </div>
                <div class="col-md-4">
                  <div class="text-muted">Direview</div>
                  <div class="fw-semibold"><?= esc($fmt($reviewedAt)) ?></div>
                </div>
                <div class="col-md-4">
                  <div class="text-muted">Keputusan</div>
                  <div class="fw-semibold"><?= esc($fmt($decisionAt)) ?></div>
                </div>
              </div>

              <?php if ($status === 'NONE'): ?>
                <div class="alert alert-primary-subtle mt-3 mb-0">
                  Belum ada Full Paper yang diunggah.
                </div>
              <?php endif; ?>

              <div class="mt-3 d-flex flex-wrap gap-2">
                <?php if ($downloadUrl): ?>
                  <a class="btn btn-outline-secondary" href="<?= $downloadUrl ?>">
                    <i class="bi bi-download"></i> Unduh File
                  </a>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- PREVIEW -->
          <div class="card shadow-soft card-glass-plain mb-3">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
              <span class="badge bg-blue-soft"><i class="bi bi-filetype-pdf"></i></span>
              <h6 class="mb-0 fw-semibold text-blue-900">Preview Dokumen</h6>
            </div>
            <div class="card-body">
              <?php if ($previewUrl): ?>
                <div class="ratio ratio-4x3 rounded overflow-hidden border" style="min-height:420px;">
                  <iframe
                    src="<?= $previewUrl ?>#toolbar=1&navpanes=0&scrollbar=1"
                    title="Preview Full Paper PDF"
                    loading="lazy"
                    style="width:100%; height:100%; border:0;"
                  ></iframe>
                </div>
                <div class="small text-muted mt-2">
                  Jika pratinjau tidak tampil, klik tombol <strong>Unduh File</strong> di atas.
                </div>
              <?php else: ?>
                <div class="alert alert-secondary mb-0">
                  Belum ada file untuk dipratinjau.
                </div>
              <?php endif; ?>
            </div>
          </div>

          <?php if ($notes !== ''): ?>
            <div class="card shadow-soft card-glass-plain mb-3">
              <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
                <span class="badge bg-blue-soft"><i class="bi bi-chat-dots"></i></span>
                <h6 class="mb-0 fw-semibold text-blue-900">Catatan Reviewer</h6>
              </div>
              <div class="card-body">
                <div class="alert <?= $status==='REVISION' ? 'alert-warning' : ($status==='REJECTED' ? 'alert-danger' : 'alert-info') ?> mb-0">
                  <div style="white-space:pre-wrap"><?= esc($notes) ?></div>
                </div>
              </div>
            </div>
          <?php endif; ?>
        </div>

        <!-- RIGHT -->
        <div class="col-12 col-lg-4">
          <div class="card shadow-soft card-glass-plain">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
              <span class="badge bg-blue-soft"><i class="bi bi-calendar-event"></i></span>
              <h6 class="mb-0 fw-semibold text-blue-900">Info Event</h6>
            </div>
            <div class="card-body small">
              <dl class="mb-0">
                <dt class="text-muted">Tanggal Event</dt>
                <dd class="fw-semibold mb-2"><?= esc($fmtDate($event['event_date'] ?? null)) ?></dd>

                <dt class="text-muted">Deadline Full Paper</dt>
                <dd class="fw-semibold mb-2"><?= esc($fmtDT($event['full_paper_deadline'] ?? null)) ?></dd>

                <dt class="text-muted">Lokasi</dt>
                <dd class="fw-semibold mb-2"><?= esc($event['location'] ?? '-') ?></dd>

                <dt class="text-muted">Format</dt>
                <dd class="fw-semibold mb-0"><?= esc($formatLabel($event['format'] ?? '')) ?></dd>
              </dl>
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

      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<style>
/* ===== Presenter Blue UI (unified) ===== */
:root{
  --blue-50:#eff6ff; --blue-100:#dbeafe; --blue-200:#bfdbfe;
  --blue-300:#93c5fd; --blue-400:#60a5fa; --blue-500:#3b82f6;
  --blue-600:#2563eb; --blue-700:#1d4ed8; --blue-800:#1e40af; --blue-900:#1e3a8a;
  --side-pad:1rem; --gutter-x:1rem;
}
.page-wrap-blue{ background:linear-gradient(180deg,var(--blue-50),#fff 40%); min-height:100vh; padding-top:72px; }
.container-xxl{ max-width:1400px; padding-left:var(--side-pad)!important; padding-right:var(--side-pad)!important; }
.row.g-3,.row.g-4{ --bs-gutter-x:var(--gutter-x); --bs-gutter-y:var(--gutter-x); }

.hero-blue{
  background:radial-gradient(1200px 400px at 10% -20%,var(--blue-600) 0,var(--blue-700) 40%,var(--blue-800) 100%) !important;
  color:#fff !important; border-radius:16px;
  border:1px solid rgba(255,255,255,.15); box-shadow:0 12px 28px rgba(30,64,175,.10);
  padding:1.6rem !important;
}
.hero-blue.card-glass,.hero-blue.card-glass-plain{ backdrop-filter:none!important; }
.hero-title{ font-weight:800; letter-spacing:.25px; font-size:1.45rem; }
.text-white-75{ color:rgba(255,255,255,.85)!important; }

.card-glass{
  backdrop-filter:blur(2px);
  background:linear-gradient(180deg,rgba(255,255,255,.18),rgba(255,255,255,.10));
  border-radius:16px; border:1px solid rgba(255,255,255,.28);
}
.card-glass-plain{
  backdrop-filter:blur(6px); background:rgba(255,255,255,.94);
  border-radius:14px; border:1px solid rgba(30,64,175,.10);
}
.shadow-soft{ box-shadow:0 10px 24px rgba(30,64,175,.08); }
.card-header{ padding:1rem 1rem .45rem 1rem!important; }
.card-body{   padding:1.05rem!important; }

.bg-blue-soft{ background:var(--blue-200); color:var(--blue-800); border-radius:12px; padding:.4rem .6rem; font-weight:600; font-size:.85rem; }
.text-blue-900{ color:var(--blue-900)!important; }

.btn{ font-weight:600; letter-spacing:.25px; border-radius:10px; font-size:.95rem; padding:.55rem 1rem; }
.btn-primary{ background:var(--blue-600); border-color:var(--blue-600); box-shadow:0 4px 12px rgba(37,99,235,.2); }
.btn-primary:hover{ background:var(--blue-700); border-color:var(--blue-700); }

@media (max-width:575.98px){
  .container-xxl{ padding-left:1rem!important; padding-right:1rem!important; }
  .hero-blue{ border-radius:14px; padding:1.25rem!important; }
  .hero-title{ font-size:1.25rem; }
  .card-body{  padding:.9rem!important; }
  .card-header{ padding:.9rem .9rem .4rem .9rem!important; }
}
@media (min-width:992px){
  .hero-blue{ padding:1.7rem!important; }
}
</style>
