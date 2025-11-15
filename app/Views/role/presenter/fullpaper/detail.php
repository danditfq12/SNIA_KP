<?php
$title       = $title       ?? 'Detail Full Paper';
$event       = $event       ?? [];
$abs         = $abs         ?? [];
$status      = strtoupper($status ?? 'NONE');
$meta        = $status_meta ?? ['badge'=>'secondary','label'=>'-','hint'=>'-'];
$path        = trim((string)($path ?? ''));
$createdAt   = $created_at  ?? null;
$notes       = trim((string)($notes ?? ''));
$absMeta     = $abs_meta    ?? ['badge'=>'secondary','label'=>'-'];
$isOpen      = (bool)($is_open ?? false);
$canReupload = (bool)($can_reupload ?? false);
$canUpload   = (bool)($can_upload ?? false);
$eventId     = (int)($event_id ?? 0);

$submissionId        = $submission_id ?? null;
$reviewers           = $reviewers ?? [];
$panel               = $panel ?? ['panel'=>'PENDING','counts'=>['acc'=>0,'rev'=>0,'rej'=>0,'done'=>0,'total'=>0],'decided'=>false];
$optionalRevision    = (bool)($optional_revision ?? false);
$canOptionalRevision = (bool)($can_optional_revision ?? false);
$canCancel           = (bool)($can_cancel ?? false);
$flow                = $flow ?? null;

$revisionInfo = $revision_info ?? ['revisi_ke'=>0,'last_upload_type'=>null,'revisi_opsional'=>null];
$revNo        = max(0, (int)($revisionInfo['revisi_ke'] ?? 0));
$lastType     = strtoupper((string)($revisionInfo['last_upload_type'] ?? ''));
$uploadKind   = strtoupper((string)($upload_kind ?? '')); // NEW | REVISION | OPTIONAL

$showOptionalBtn = $canOptionalRevision && ($lastType !== 'OPTIONAL');

$info = [
  'event'          => (string)($event['title'] ?? '-'),
  'judul'          => (string)($abs['judul'] ?? '-'),
  'kategori'       => (string)($abs['nama_kategori'] ?? '-'),
  'status_abstrak' => (string)($absMeta['label'] ?? '-'),
  'status_fp'      => (string)($meta['label'] ?? '-'),
  'fp_diunggah'    => $createdAt,
];

$fmt = fn($s)=> $s ? date('d M Y H:i', strtotime($s)) : '-';
$pct = function(int $n, int $d): int { $d = max(1,$d); return (int)round(($n/$d)*100); };

$downloadUrl = $path !== '' ? site_url('presenter/fullpaper/download/'.rawurlencode(basename($path))) : '';
$previewUrl  = $downloadUrl ? ($downloadUrl.(str_contains($downloadUrl,'?')?'&':'?').'inline=1') : '';

$absPreview  = '';
$gdocs = '';
if ($previewUrl) {
  $absPreview = (str_starts_with($previewUrl,'http://') || str_starts_with($previewUrl,'https://'))
    ? $previewUrl : base_url(trim($previewUrl,'/'));
  $gdocs = 'https://docs.google.com/gview?embedded=1&url='.rawurlencode($absPreview);
}

$badgeForDecision = function($k){
  $k = strtolower((string)$k);
  return match (true) {
    in_array($k,['accepted','accept','diterima','acc','approved']) => ['success','Diterima'],
    in_array($k,['revision','revisi'])                              => ['warning','Revisi'],
    in_array($k,['rejected','reject','ditolak'])                    => ['danger','Ditolak'],
    default                                                         => ['secondary', $k ? ucfirst($k) : '—'],
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
$panelLabel = function($p){
  return match(strtoupper((string)$p)){
    'ACCEPTED' => 'Diterima',
    'REVISION' => 'Revisi',
    'REJECTED' => 'Ditolak',
    'PENDING'  => 'Menunggu',
    default    => ucfirst(strtolower((string)$p ?: 'Menunggu')),
  };
};
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill page-wrap-blue">
    <div class="container-xxl px-3 px-md-4 py-4">

      <!-- HERO -->
      <div class="card-hero mb-4">
        <div class="hero-body">
          <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
            <div>
              <h3 class="hero-title mb-1">
                <i class="bi bi-file-earmark-text me-2"></i>Detail Full Paper
              </h3>

              <!-- hanya badge revisi -->
              <?php if ($revNo > 0): ?>
                <div class="d-flex flex-wrap align-items-center gap-2 mt-2">
                  <span class="badge bg-light text-dark fw-semibold">
                    <i class="bi bi-arrow-repeat me-1"></i>Revisi ke-<?= (int)$revNo ?>
                  </span>
                  <?php if ($lastType === 'OPTIONAL'): ?>
                    <span class="badge bg-warning text-dark">
                      <i class="bi bi-stars me-1"></i>Revisi Opsional diunggah
                    </span>
                  <?php elseif ($lastType === 'REVISION'): ?>
                    <span class="badge bg-primary">
                      <i class="bi bi-check2-circle me-1"></i>Revisi diunggah
                    </span>
                  <?php endif; ?>
                </div>
              <?php endif; ?>
            </div>

            <div class="d-flex gap-2 ms-auto align-items-center">
              <?php if ($showOptionalBtn): ?>
                <a class="btn btn-warning btn-sm text-dark" href="<?= site_url('presenter/fullpaper/create/'.$eventId) ?>">
                  <i class="bi bi-upload me-1"></i>Upload Revisi (Opsional)
                </a>
              <?php elseif ($canReupload): ?>
                <a class="btn btn-light btn-sm" href="<?= site_url('presenter/fullpaper/create/'.$eventId) ?>">
                  <i class="bi bi-arrow-repeat me-1"></i>Upload Revisi
                </a>
              <?php elseif ($canUpload): ?>
                <a class="btn btn-light btn-sm" href="<?= site_url('presenter/fullpaper/create/'.$eventId) ?>">
                  <i class="bi bi-upload me-1"></i>Upload Full Paper
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
          <!-- DATA INFORMASI -->
          <div class="card shadow-soft card-glass-plain mb-3">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
              <span class="badge bg-blue-soft"><i class="bi bi-info-circle"></i></span>
              <h6 class="mb-0 fw-semibold text-blue-900">Data Informasi</h6>
            </div>
            <div class="card-body">
              <ul class="meta-list mb-3">
                <li><span>Event</span><strong class="text-blue-900"><?= esc($info['event']) ?></strong></li>
                <li><span>Judul</span><strong class="text-blue-900"><?= esc($info['judul']) ?></strong></li>
                <li><span>Kategori</span><strong class="text-blue-900"><?= esc($info['kategori']) ?></strong></li>
                <li><span>Status Abstrak</span><strong class="text-blue-900"><?= esc($info['status_abstrak']) ?></strong></li>
                <li><span>Status Full Paper</span><strong class="text-blue-900"><?= esc($meta['label']) ?></strong></li>
                <li><span>Full paper diunggah</span><strong class="text-blue-900"><?= esc($fmt($info['fp_diunggah'])) ?></strong></li>
              </ul>

              <?php if ($notes !== ''): ?>
                <div class="mt-2 p-2 rounded bg-light border small">
                  <div class="fw-semibold mb-1"><i class="bi bi-chat-dots me-1"></i>Catatan</div>
                  <div><?= nl2br(esc($notes)) ?></div>
                </div>
              <?php endif; ?>

              <div class="pt-3 border-top subtle-divider d-flex flex-wrap gap-2 align-items-center">
                <?php if ($isOpen): ?>
                  <?php if ($showOptionalBtn): ?>
                    <a class="btn btn-warning text-dark" href="<?= site_url('presenter/fullpaper/create/'.$eventId) ?>">
                      <i class="bi bi-upload"></i> Upload Revisi (Opsional)
                    </a>
                  <?php elseif ($canReupload): ?>
                    <a class="btn btn-danger" href="<?= site_url('presenter/fullpaper/create/'.$eventId) ?>">
                      <i class="bi bi-upload"></i> Upload Ulang (Revisi)
                    </a>
                  <?php elseif ($canUpload): ?>
                    <a class="btn btn-primary" href="<?= site_url('presenter/fullpaper/create/'.$eventId) ?>">
                      <i class="bi bi-upload"></i> Upload Full Paper
                    </a>
                  <?php endif; ?>

                  <?php if ($canCancel): ?>
                    <form id="formCancelFP" action="<?= site_url('presenter/fullpaper/cancel/'.$eventId) ?>" method="post" class="d-inline">
                      <?= csrf_field() ?>
                      <button type="button" class="btn btn-outline-danger js-cancel-fp" data-form="#formCancelFP">
                        <i class="bi bi-x-circle"></i> Batalkan Unggahan
                      </button>
                    </form>
                    <div class="small text-muted">
                      <i class="bi bi-shield-exclamation"></i> Tombol ini hanya tersedia sebelum admin menambahkan reviewer.
                    </div>
                  <?php endif; ?>

                  <?php if (!$showOptionalBtn && !$canReupload && !$canUpload && !$canCancel): ?>
                    <div class="text-muted small fw-semibold">Tidak ada aksi yang tersedia saat ini.</div>
                  <?php endif; ?>
                <?php else: ?>
                  <?php if (in_array($status, ['NONE','REVISION'], true)): ?>
                    <div class="alert alert-warning fw-semibold mb-0 mt-2">
                      <i class="bi bi-lock me-1"></i> Pengumpulan full paper sudah ditutup.
                    </div>
                  <?php endif; ?>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- PREVIEW PDF -->
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
                <?php if ($absPreview): ?>
                  <a class="btn btn-outline-secondary" target="_blank" rel="noopener" href="<?= esc($absPreview) ?>"><i class="bi bi-box-arrow-up-right"></i></a>
                <?php endif; ?>
                <button type="button" class="btn btn-outline-secondary" id="btnToggleSize" data-state="max" title="Maximize">
                  <i class="bi bi-arrows-fullscreen"></i>
                </button>
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
                  <?php if ($gdocs): ?>
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
          <!-- STATUS REVIEWER + HASIL AKHIR -->
          <div class="card shadow-soft card-glass-plain sticky-card">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center justify-content-between">
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-blue-soft"><i class="bi bi-people"></i></span>
                <h6 class="mb-0 fw-semibold text-blue-900">Status Reviewer</h6>
              </div>
              <?php if ($submissionId): ?><span class="badge bg-light text-muted">#<?= (int)$submissionId ?></span><?php endif; ?>
            </div>
            <div class="card-body">
              <?php if (!$reviewers): ?>
                <div class="empty-hint"><i class="bi bi-info-circle me-1"></i> Belum ada reviewer yang ditugaskan.</div>
              <?php else: ?>
                <div class="vstack gap-3">
                  <?php foreach ($reviewers as $r):
                    [$cls, $lbl] = $badgeForDecision($r['keputusan'] ?? null);
                  ?>
                  <div class="p-2 border rounded-3">
                    <div class="d-flex align-items-start justify-content-between">
                      <div>
                        <div class="fw-semibold text-blue-900">
                          <?= esc($r['name'] ?? 'Reviewer') ?>
                          <?php if (!empty($r['email'])): ?>
                            <small class="text-muted ms-1">&lt;<?= esc($r['email']) ?>&gt;</small>
                          <?php endif; ?>
                        </div>
                      </div>
                      <div><span class="badge bg-<?= $cls ?>"><?= $lbl ?></span></div>
                    </div>
                    <?php if (!empty($r['tanggal_review']) || !empty($r['komentar'])): ?>
                      <div class="mt-2 small">
                        <?php if (!empty($r['tanggal_review'])): ?>
                          <div class="text-muted mb-1"><i class="bi bi-clock"></i> <?= esc($fmt($r['tanggal_review'])) ?></div>
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

              <?php
                $acc = (int)($panel['counts']['acc'] ?? 0);
                $rev = (int)($panel['counts']['rev'] ?? 0);
                $rej = (int)($panel['counts']['rej'] ?? 0);
                $done= (int)($panel['counts']['done']?? 0);
                $tot = (int)($panel['counts']['total']?? 0);
                if ($tot <= 0) $tot = count($reviewers);
                if ($tot <= 0) $tot = max($done, 3);
              ?>
              <div class="mt-3 p-0 rounded overflow-hidden result-card border">
                <div class="result-head d-flex align-items-center justify-content-between px-3 py-2">
                  <div class="d-flex align-items-center gap-2">
                    <span class="status-dot <?= $panelBadge($panel['panel']) ?>"></span>
                    <div class="fw-semibold">Hasil Akhir</div>
                  </div>
                  <span class="badge bg-<?= $panelBadge($panel['panel']) ?> px-3 py-2">
                    <i class="bi bi-flag me-1"></i><?= $panelLabel($panel['panel']) ?>
                  </span>
                </div>

                <?php if ($done > 0): ?>
                  <div class="px-3 pt-3">
                    <div class="progress" style="height: 12px;">
                      <div class="progress-bar bg-success" role="progressbar" style="width: <?= $pct($acc,$tot) ?>%" aria-label="Diterima"></div>
                      <div class="progress-bar bg-warning text-dark" role="progressbar" style="width: <?= $pct($rev,$tot) ?>%" aria-label="Revisi"></div>
                      <div class="progress-bar bg-danger" role="progressbar" style="width: <?= $pct($rej,$tot) ?>%" aria-label="Ditolak"></div>
                    </div>
                    <div class="d-flex flex-wrap gap-2 mt-2 small">
                      <span class="badge bg-success-subtle text-success-emphasis"><i class="bi bi-check2-circle me-1"></i>Diterima <?= $acc ?></span>
                      <span class="badge bg-warning-subtle text-warning-emphasis"><i class="bi bi-arrow-repeat me-1"></i>Revisi <?= $rev ?></span>
                      <span class="badge bg-danger-subtle text-danger-emphasis"><i class="bi bi-x-octagon me-1"></i>Ditolak <?= $rej ?></span>
                      <span class="ms-auto text-muted">Selesai <?= $done ?>/<?= $tot ?></span>
                    </div>
                  </div>

                  <div class="px-3 pb-3 pt-2">
                    <?php if ($panel['panel']==='REVISION' && $isOpen): ?>
                      <div class="alert alert-warning mb-0"><i class="bi bi-exclamation-triangle me-1"></i> Mayoritas revisi — silakan unggah revisi.</div>
                    <?php elseif ($panel['panel']==='ACCEPTED'): ?>
                      <div class="alert alert-success mb-0"><i class="bi bi-check2-circle me-1"></i> Mayoritas diterima — naskah diterima.</div>
                    <?php elseif ($panel['panel']==='REJECTED'): ?>
                      <div class="alert alert-danger mb-0"><i class="bi bi-x-octagon me-1"></i> Mayoritas ditolak — silakan hubungi panitia bila perlu.</div>
                    <?php else: ?>
                      <div class="alert alert-info mb-0"><i class="bi bi-hourglass-split me-1"></i> Menunggu keputusan panel.</div>
                    <?php endif; ?>
                  </div>
                <?php else: ?>
                  <div class="px-3 py-4 result-empty text-center">
                    <div class="empty-illustration mb-2">
                      <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div class="fw-semibold">Belum ada review yang masuk</div>
                    <div class="text-muted small">Menunggu reviewer menyelesaikan penilaian.</div>
                    <div class="d-flex justify-content-center gap-2 mt-3">
                      <span class="badge bg-secondary-subtle text-secondary-emphasis">Diterima 0</span>
                      <span class="badge bg-secondary-subtle text-secondary-emphasis">Revisi 0</span>
                      <span class="badge bg-secondary-subtle text-secondary-emphasis">Ditolak 0</span>
                    </div>
                  </div>
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
  --muted:#6b7280; --side-pad: clamp(1rem, 2.3vw, 2.2rem);
}
.container-xxl{ max-width:min(100%, 1560px); padding-left: var(--side-pad) !important; padding-right: var(--side-pad) !important; margin-inline:auto; }
body{ font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size:15.5px; line-height:1.6; }
.page-wrap-blue{ min-height:100vh; padding-top:72px; background:
  radial-gradient(900px 300px at 20% -10%, rgba(59,130,246,.17), rgba(59,130,246,0) 60%),
  radial-gradient(900px 300px at 80% 110%, rgba(59,130,246,.14), rgba(59,130,246,0) 70%),
  linear-gradient(180deg, var(--blue-50), #fff 40%); }
.card-hero{ border:0; border-radius:16px; overflow:hidden; box-shadow:0 12px 28px rgba(30,64,175,.10); }
.card-hero .hero-body{ background:radial-gradient(1100px 360px at 10% -10%,var(--blue-600) 0,var(--blue-700) 45%,var(--blue-800) 100%); color:#fff; padding:1.8rem 1.2rem; min-height:140px; }
.hero-title{ font-weight:800; letter-spacing:.25px; font-size:1.5rem; }
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
.sticky-card{ position:sticky; top:84px; }
.pdf-wrap{ border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; background:#f8fafc; transition:height .2s ease; }
.pdf-wrap.is-min{ height:52vh; }
.pdf-wrap.is-max{ height:82vh; }
.pdf-frame{ width:100%; height:100%; border:0; }
.empty-hint{ color:#567; background:#f6f9ff; border:1px dashed rgba(30,64,175,.18); border-radius:12px; padding:.8rem 1rem; font-weight:600; }

.result-card{ background:#fff; }
.result-head{
  background:linear-gradient(135deg, rgba(59,130,246,.12), rgba(59,130,246,.02));
  border-bottom:1px solid rgba(30,64,175,.12);
}
.status-dot{ width:10px;height:10px;border-radius:50%; display:inline-block; }
.status-dot.success{ background:#198754;}
.status-dot.warning{ background:#ffc107;}
.status-dot.danger{ background:#dc3545;}
.status-dot.secondary{ background:#6c757d;}
.result-empty{
  background:
    radial-gradient(400px 140px at 15% 10%, rgba(59,130,246,.08), rgba(59,130,246,0)),
    radial-gradient(500px 180px at 85% 90%, rgba(99,102,241,.08), rgba(99,102,241,0));
}
.empty-illustration i{
  font-size:32px; padding:.6rem; border-radius:12px;
  background:#eef2ff; color:#3730a3; display:inline-block;
}
.progress{ background:#f1f5f9; }

/* Fallback utk project yang belum Bootstrap 5.3 */
.bg-success-subtle{ background:#eaf7ef!important; }
.bg-warning-subtle{ background:#fff6db!important; }
.bg-danger-subtle { background:#fde2e1!important; }
.text-success-emphasis{ color:#157347!important; }
.text-warning-emphasis{ color:#946200!important; }
.text-danger-emphasis { color:#b02a37!important; }

/* Mini toast */
.mini-toast{
  position: fixed; right: 16px; bottom: 16px; z-index: 1080;
  background: #111827; color:#fff; padding:.75rem 1rem; border-radius:12px;
  box-shadow:0 10px 24px rgba(0,0,0,.2); display:none; align-items:center; gap:.5rem;
}
.mini-toast.show{ display:flex; }
.mini-toast .icon{ font-size:1.1rem; }
.mini-toast .close{ background:transparent; border:0; color:#fff; margin-left:.5rem; }
</style>

<div id="miniToast" class="mini-toast">
  <i class="bi icon"></i>
  <span class="msg"></span>
  <button class="close" aria-label="Close" onclick="document.getElementById('miniToast').classList.remove('show')">
    <i class="bi bi-x-lg"></i>
  </button>
</div>

<script>
document.addEventListener('DOMContentLoaded', function(){
  // toggle preview size
  const wrap = document.getElementById('pdfWrap');
  const btn  = document.getElementById('btnToggleSize');
  if (wrap && btn) {
    wrap.classList.add('is-min');
    btn.addEventListener('click', function(){
      const toMax = btn.dataset.state === 'max';
      wrap.classList.toggle('is-min', !toMax);
      wrap.classList.toggle('is-max',  toMax);
      btn.dataset.state = toMax ? 'min' : 'max';
      btn.innerHTML = toMax ? '<i class="bi bi-arrows-angle-contract"></i>'
                            : '<i class="bi bi-arrows-fullscreen"></i>';
      btn.title = toMax ? 'Minimize' : 'Maximize';
    });
  }

  // SweetAlert2 for Cancel
  document.querySelectorAll('.js-cancel-fp').forEach(function (b) {
    b.addEventListener('click', function(){
      const form = document.querySelector(b.dataset.form);
      if (typeof Swal !== 'undefined') {
        Swal.fire({
          title: 'Batalkan unggahan?',
          text: 'File akan dihapus dan status kembali kosong.',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Ya, batalkan',
          cancelButtonText: 'Batal',
          reverseButtons: true,
          focusCancel: true
        }).then((res) => {
          if (res.isConfirmed && form) form.submit();
        });
      } else {
        if (confirm('Batalkan unggahan? File akan dihapus dan status kembali kosong.') && form) form.submit();
      }
    });
  });

  // Mini toast untuk jenis upload (flashdata)
  const uploadKind = "<?= esc(strtoupper((string)($upload_kind ?? ''))) ?>";
  if (uploadKind) {
    const box = document.getElementById('miniToast');
    if (box) {
      const icon = box.querySelector('.icon');
      const msg  = box.querySelector('.msg');
      if (uploadKind === 'OPTIONAL') {
        icon.className = 'bi icon bi-stars';
        msg.textContent = 'Revisi opsional berhasil diunggah.';
      } else if (uploadKind === 'REVISION') {
        icon.className = 'bi icon bi-check2-circle';
        msg.textContent = 'Revisi full paper berhasil diunggah.';
      } else {
        icon.className = 'bi icon bi-upload';
        msg.textContent = 'Full paper berhasil diunggah.';
      }
      box.classList.add('show');
      setTimeout(() => box.classList.remove('show'), 5000);
    }
  }
});
</script>
