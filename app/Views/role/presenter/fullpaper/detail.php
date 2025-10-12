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

$submissionId = $submission_id ?? null;
$reviewers    = $reviewers ?? [];
$panel        = $panel ?? ['panel'=>'PENDING','counts'=>['acc'=>0,'rev'=>0,'rej'=>0,'done'=>0,'total'=>0],'decided'=>false];

$fmt   = fn($s)=> $s ? date('d M Y H:i', strtotime($s)) : '-';
$fmtDT = $fmt;

$downloadUrl = $path !== '' ? site_url('presenter/fullpaper/download/'.rawurlencode(basename($path))) : '';
$previewUrl  = $downloadUrl ? ($downloadUrl.(str_contains($downloadUrl,'?')?'&':'?').'inline=1') : '';

$absPreview  = '';
if ($previewUrl) {
  $absPreview = (str_starts_with($previewUrl,'http://') || str_starts_with($previewUrl,'https://'))
    ? $previewUrl : base_url(trim($previewUrl,'/'));
  $gdocs = 'https://docs.google.com/gview?embedded=1&url='.rawurlencode($absPreview);
}

$badgeForDecision = function($k){
  $k = strtolower((string)$k);
  return match (true) {
    in_array($k,['accepted','accept','diterima','acc','approved']) => ['success','Accepted'],
    in_array($k,['revision','revisi'])                              => ['warning','Revision'],
    in_array($k,['rejected','reject','ditolak'])                    => ['danger','Rejected'],
    default                                                         => ['secondary', $k ? ucfirst($k) : '—'],
  };
};
$badgeForAssign = function($s){
  $s = strtolower((string)$s);
  return match ($s) {
    'accepted' => ['primary','Assigned'],
    'declined' => ['secondary','Declined'],
    default    => ['secondary','Pending'],
  };
};
$panelBadge = function($p){
  $p = strtoupper((string)$p);
  return match($p){
    'ACCEPTED' => 'success',
    'REVISION' => 'warning text-dark',
    'REJECTED' => 'danger',
    'PENDING'  => 'secondary',
    default    => 'secondary'
  };
};

function fp_extract_contributors(array $abs): array {
  $candidates = [
    'contributors','contributor','authors','author_list','co_authors','coauthor','coauthors',
    'penulis','penulis_lain','daftar_penulis','nama_penulis'
  ];
  $values = null;
  foreach ($candidates as $k) if (!empty($abs[$k])) { $values = $abs[$k]; break; }
  if ($values === null) return [];
  $names = is_array($values) ? $values : preg_split('/\r\n|\r|\n|;|,/', (string)$values);
  $names = array_filter(array_map(fn($x)=>trim((string)$x),(array)$names), fn($x)=>$x!=='');
  return array_values(array_unique($names));
}
$contributors = fp_extract_contributors($abs);

$absKategori = $abs['nama_kategori'] ?? $abs['kategori'] ?? $abs['kategori_nama'] ?? $abs['category_name'] ?? $abs['kategori_abstrak'] ?? '-';
$absKategori = (trim((string)$absKategori) === '') ? '-' : $absKategori;
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill page-wrap-blue">
    <div class="container-xxl px-3 px-md-4 py-4">

      <!-- HERO (match index look & closer side padding) -->
      <div class="card-hero mb-4">
        <div class="hero-body">
          <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
            <div>
              <h3 class="hero-title mb-1"><i class="bi bi-file-earmark-text me-2"></i>Detail Full Paper</h3>
              <div class="text-white-70 small">
                <?= esc($event['title'] ?? '-') ?>
                <?php if(!empty($abs['judul'])): ?>
                  <span class="d-inline-block mx-2">•</span>
                  <span class="fw-semibold"><?= esc($abs['judul']) ?></span>
                <?php endif; ?>
              </div>
            </div>
            <div class="d-flex gap-2 ms-auto">
              <?php if ($canUpload): ?>
                <a class="btn btn-light btn-sm" href="<?= site_url('presenter/fullpaper/create/'.$eventId) ?>">
                  <i class="bi bi-upload me-1"></i>Upload Full Paper
                </a>
              <?php elseif ($canReupload): ?>
                <a class="btn btn-light btn-sm" href="<?= site_url('presenter/fullpaper/create/'.$eventId) ?>">
                  <i class="bi bi-arrow-repeat me-1"></i>Upload Revisi
                </a>
              <?php endif; ?>
              <a href="<?= site_url('presenter/fullpaper') ?>" class="btn btn-light btn-sm">
                <i class="bi bi-arrow-left"></i> Kembali
              </a>
            </div>
          </div>
        </div>
      </div>

      <div class="row g-3">
        <!-- KIRI -->
        <div class="col-12 col-xl-8">
          <!-- Info FP -->
          <div class="card shadow-soft card-glass-plain mb-3">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
              <span class="badge bg-blue-soft"><i class="bi bi-info-circle"></i></span>
              <h6 class="mb-0 fw-semibold text-blue-900">Informasi Full Paper</h6>
            </div>
            <div class="card-body">
              <ul class="meta-list mb-3">
                <li><span>Diunggah</span><strong class="text-blue-900"><?= esc($fmtDT($createdAt)) ?></strong></li>
                <?php if (!empty($event['full_paper_deadline'])): ?>
                  <li><span>Deadline FP</span><strong class="text-blue-900"><?= esc($fmtDT($event['full_paper_deadline'])) ?></strong></li>
                <?php endif; ?>
              </ul>
              <div class="pt-3 d-flex flex-wrap gap-2 border-top subtle-divider">
                <?php if ($canReupload): ?>
                  <a class="btn btn-danger" href="<?= site_url('presenter/fullpaper/create/'.$eventId) ?>"><i class="bi bi-upload"></i> Upload Ulang (Revisi)</a>
                <?php elseif ($canUpload): ?>
                  <a class="btn btn-primary" href="<?= site_url('presenter/fullpaper/create/'.$eventId) ?>"><i class="bi bi-upload"></i> Upload Full Paper</a>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- Preview PDF -->
          <div class="card shadow-soft card-glass-plain mb-3">
            <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-blue-soft"><i class="bi bi-file-earmark-pdf"></i></span>
                <h6 class="mb-0 fw-semibold text-blue-900">Pratinjau Full Paper</h6>
              </div>
              <div class="btn-group btn-group-sm">
                <?php if ($downloadUrl): ?>
                  <a class="btn btn-outline-secondary" href="<?= $downloadUrl ?>"><i class="bi bi-download"></i></a>
                <?php endif; ?>
                <?php if (!empty($absPreview)): ?>
                  <a class="btn btn-outline-secondary" target="_blank" rel="noopener" href="<?= esc($absPreview) ?>"><i class="bi bi-box-arrow-up-right"></i></a>
                <?php endif; ?>
                <button type="button" class="btn btn-outline-secondary" id="btnToggleSize" data-state="max"><i class="bi bi-arrows-fullscreen"></i></button>
              </div>
            </div>
            <div class="card-body">
              <?php if ($previewUrl): ?>
                <div class="pdf-wrap is-min" id="pdfWrap">
                  <iframe class="pdf-frame" title="Preview PDF" id="pdfFrame"
                          src="<?= esc($previewUrl) ?>#toolbar=1&navpanes=0"></iframe>
                </div>
                <div class="small text-muted mt-2">
                  Jika pratinjau kosong, klik ikon <b>↗</b> untuk membuka di tab baru
                  <?php if (!empty($gdocs)): ?>
                    atau gunakan <a target="_blank" rel="noopener" href="<?= esc($gdocs) ?>">Google Docs Viewer</a>.
                  <?php endif; ?>
                </div>
              <?php else: ?>
                <div class="empty-hint"><i class="bi bi-info-circle me-1"></i> Belum ada file untuk dipratinjau.</div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- KANAN -->
        <div class="col-12 col-xl-4">
          <!-- Abstrak -->
          <div class="card shadow-soft card-glass-plain mb-3">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center justify-content-between">
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-blue-soft"><i class="bi bi-journal-text"></i></span>
                <h6 class="mb-0 fw-semibold text-blue-900">Informasi Abstrak</h6>
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
                </ul>
                <div>
                  <div class="fw-semibold mb-1">Contributor</div>
                  <?php if (!empty($contributors)): ?>
                    <div class="chip-row">
                      <?php foreach ($contributors as $c): ?>
                        <span class="chip alt"><i class="bi bi-person me-1"></i><?= esc($c) ?></span>
                      <?php endforeach; ?>
                    </div>
                  <?php else: ?>
                    <div class="text-muted small">– Tidak ada data contributor –</div>
                  <?php endif; ?>
                </div>
                <div class="d-flex gap-2 pt-3 border-top subtle-divider">
                  <a class="btn btn-outline-primary" href="<?= site_url('presenter/abstrak/detail/'.(int)($abs['id_abstrak'] ?? 0)) ?>">
                    <i class="bi bi-eye"></i> Lihat Detail Abstrak
                  </a>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Status -->
          <div class="card shadow-soft card-glass-plain sticky-card mb-3">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
              <span class="badge bg-blue-soft"><i class="bi bi-flag"></i></span>
              <h6 class="mb-0 fw-semibold text-blue-900">Status</h6>
            </div>
            <div class="card-body">
              <?php if ($status === 'NONE'): ?>
                <div class="alert bg-blue-soft-2 text-blue-900 border-0 fw-semibold mb-0"><i class="bi bi-info-circle me-1"></i> Belum ada Full Paper yang diunggah.</div>
              <?php elseif ($status === 'UPLOADED'): ?>
                <div class="alert bg-blue-soft-2 text-blue-900 border-0 fw-semibold mb-0"><i class="bi bi-hourglass-split me-1"></i> Sedang direview.</div>
              <?php elseif ($status === 'REVISION'): ?>
                <div class="alert alert-warning fw-semibold mb-0"><i class="bi bi-arrow-repeat me-1"></i> Perlu revisi.</div>
              <?php elseif ($status === 'REJECTED'): ?>
                <div class="alert alert-danger fw-semibold mb-0"><i class="bi bi-x-octagon me-1"></i> Ditolak.</div>
              <?php elseif ($status === 'ACCEPTED'): ?>
                <div class="alert alert-success fw-semibold mb-0"><i class="bi bi-check2-circle me-1"></i> Diterima.</div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Reviewer & Panel -->
          <div class="card shadow-soft card-glass-plain sticky-card">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center justify-content-between">
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-blue-soft"><i class="bi bi-people"></i></span>
                <h6 class="mb-0 fw-semibold text-blue-900">Status Reviewer</h6>
              </div>
              <?php if ($submissionId): ?><span class="badge bg-light text-muted">#<?= (int)$submissionId ?></span><?php endif; ?>
            </div>
            <div class="card-body">
              <?php if (empty($reviewers)): ?>
                <div class="empty-hint"><i class="bi bi-info-circle me-1"></i> Belum ada reviewer yang ditugaskan.</div>
              <?php else: ?>
                <div class="vstack gap-3">
                  <?php foreach ($reviewers as $r):
                    [$cls, $lbl] = $badgeForDecision($r['keputusan'] ?? null);
                    [$csa, $lsa] = $badgeForAssign($r['assignment_status'] ?? null);
                  ?>
                  <div class="p-2 border rounded-3">
                    <div class="d-flex align-items-start justify-content-between">
                      <div>
                        <div class="fw-semibold text-blue-900">
                          <?= esc($r['name'] ?? 'Reviewer') ?>
                          <?php if (!empty($r['email'])): ?><small class="text-muted ms-1">&lt;<?= esc($r['email']) ?>&gt;</small><?php endif; ?>
                        </div>
                        <div class="small text-muted">Tugas: <span class="badge bg-<?= $csa ?>"><?= $lsa ?></span></div>
                      </div>
                      <div><span class="badge bg-<?= $cls ?>"><?= $lbl ?></span></div>
                    </div>
                    <?php if (!empty($r['tanggal_review']) || !empty($r['komentar'])): ?>
                      <div class="mt-2 small">
                        <?php if (!empty($r['tanggal_review'])): ?>
                          <div class="text-muted mb-1"><i class="bi bi-clock"></i> <?= esc($fmtDT($r['tanggal_review'])) ?></div>
                        <?php endif; ?>
                        <?php if (!empty($r['komentar'])): ?>
                          <div class="px-2 py-2 bg-light rounded"><?= nl2br(esc($r['komentar'])) ?></div>
                        <?php endif; ?>
                      </div>
                    <?php endif; ?>
                  </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>

              <div class="mt-3 p-2 rounded border bg-light">
                <div class="mb-1 fw-semibold">Ringkasan Panel</div>
                <div class="d-flex flex-wrap gap-2 align-items-center">
                  <span class="badge bg-<?= $panelBadge($panel['panel']) ?>">Panel: <?= strtoupper($panel['panel']) ?></span>
                  <small class="text-muted">ACC <?= (int)$panel['counts']['acc'] ?> • Revisi <?= (int)$panel['counts']['rev'] ?> • Reject <?= (int)$panel['counts']['rej'] ?></small>
                </div>
                <?php if ($panel['panel']==='REVISION' && $isOpen): ?>
                  <div class="mt-2 alert alert-warning mb-0"><i class="bi bi-exclamation-triangle me-1"></i> Mayoritas revisi — silakan unggah revisi.</div>
                <?php elseif ($panel['panel']==='ACCEPTED'): ?>
                  <div class="mt-2 alert alert-success mb-0"><i class="bi bi-check2-circle me-1"></i> Mayoritas ACC — naskah diterima.</div>
                <?php elseif ($panel['panel']==='REJECTED'): ?>
                  <div class="mt-2 alert alert-danger mb-0"><i class="bi bi-x-octagon me-1"></i> Mayoritas reject — hubungi panitia bila perlu.</div>
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
:root{
  --blue-50:#eff6ff; --blue-100:#dbeafe; --blue-200:#bfdbfe;
  --blue-300:#93c5fd; --blue-400:#60a5fa; --blue-500:#3b82f6;
  --blue-600:#2563eb; --blue-700:#1d4ed8; --blue-800:#1e40af; --blue-900:#1e3a8a;
  --muted:#6b7280;
  --side-pad: clamp(1rem, 2.3vw, 2.2rem); /* kiri-kanan rapat seperti index */
}

/* Container spacing (match index) */
.container-xxl{
  max-width:min(100%, 1560px);
  padding-left: var(--side-pad) !important;
  padding-right: var(--side-pad) !important;
  margin-inline:auto;
}

body{ font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size:15.5px; line-height:1.6; }
.page-wrap-blue{ min-height:100vh; padding-top:72px; background:
  radial-gradient(900px 300px at 20% -10%, rgba(59,130,246,.17), rgba(59,130,246,0) 60%),
  radial-gradient(900px 300px at 80% 110%, rgba(59,130,246,.14), rgba(59,130,246,0) 70%),
  linear-gradient(180deg, var(--blue-50), #fff 40%); }

/* HERO (tinggi & konsisten) */
.card-hero{ border:0; border-radius:16px; overflow:hidden; box-shadow:0 12px 28px rgba(30,64,175,.10); }
.card-hero .hero-body{
  background:radial-gradient(1100px 360px at 10% -10%,var(--blue-600) 0,var(--blue-700) 45%,var(--blue-800) 100%);
  color:#fff; padding:1.8rem 1.2rem; min-height:176px;
}
.hero-title{ font-weight:800; letter-spacing:.25px; font-size:1.5rem; }
.text-white-70{ color:rgba(255,255,255,.85)!important; }

/* Reuse from index look */
.card-glass-plain{ backdrop-filter:blur(6px); background:rgba(255,255,255,.96); border-radius:14px; border:1px solid rgba(30,64,175,.10); }
.shadow-soft{ box-shadow:0 10px 24px rgba(30,64,175,.08); }
.subtle-divider{ border-color: rgba(30,64,175,.12)!important; }
.meta-list{ list-style:none; padding-left:0; margin:0; }
.meta-list li{ display:flex; align-items:center; justify-content:space-between; gap:.75rem; padding:.45rem 0; }
.meta-list li span{ color:var(--muted); }
.text-blue-900{ color:var(--blue-900)!important; }
.bg-blue-soft{ background:var(--blue-200); color:var(--blue-800); border-radius:12px; padding:.5rem .7rem; font-weight:600; font-size:.9rem; }
.btn{ font-weight:700; border-radius:10px; font-size:.98rem; padding:.62rem 1.05rem; }
.btn-sm{ padding:.5rem .9rem; font-size:.92rem; }
.btn-primary{ background:var(--blue-600); border-color:var(--blue-600); box-shadow:0 4px 12px rgba(37,99,235,.2); }
.bg-blue-soft-2{ background:linear-gradient(180deg,#eef4ff,#eaf2ff); }
.sticky-card{ position:sticky; top:84px; }

/* chips (match index) */
.chip-row{ display:flex; flex-wrap:wrap; gap:.5rem; }
.chip{ display:inline-flex; align-items:center; padding:.28rem .6rem; font-size:.88rem; border-radius:999px; background:#eef3ff; color:#1e3a8a; border:1px solid rgba(30,64,175,.15); font-weight:600; }
.chip.alt{ background:#f1f5ff; color:#244aa4; }

/* PDF viewer */
.pdf-wrap{ border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; background:#f8fafc; transition:height .2s ease; }
.pdf-wrap.is-min{ height:52vh; }
.pdf-wrap.is-max{ height:82vh; }
.pdf-frame{ width:100%; height:100%; border:0; }

.empty-hint{ color:#567; background:#f6f9ff; border:1px dashed rgba(30,64,175,.18); border-radius:12px; padding:.8rem 1rem; font-weight:600; }

@media (max-width:767.98px){
  .card-hero .hero-body{ padding:1.4rem 1rem; min-height:165px; }
  .pdf-wrap.is-min{ height:46vh; }
}
@media (max-width:575.98px){
  .container-xxl{ padding-left: calc(var(--side-pad) - .25rem)!important; padding-right: calc(var(--side-pad) - .25rem)!important; }
  .hero-title{ font-size:1.35rem; }
  .pdf-wrap.is-min{ height:42vh; }
  .pdf-wrap.is-max{ height:75vh; }
}
</style>

<script>
(function(){
  const wrap = document.getElementById('pdfWrap');
  const btn  = document.getElementById('btnToggleSize');
  if(!wrap || !btn) return;
  function setState(state){
    if(state==='max'){
      wrap.classList.remove('is-min'); wrap.classList.add('is-max');
      btn.dataset.state = 'min';
      btn.innerHTML = '<i class="bi bi-arrows-angle-contract"></i>';
      btn.title = 'Minimize';
    }else{
      wrap.classList.remove('is-max'); wrap.classList.add('is-min');
      btn.dataset.state = 'max';
      btn.innerHTML = '<i class="bi bi-arrows-fullscreen"></i>';
      btn.title = 'Maximize';
    }
  }
  // default: minimized
  wrap.classList.add('is-min');
  btn.addEventListener('click', ()=> setState(btn.dataset.state));
})();
</script>
