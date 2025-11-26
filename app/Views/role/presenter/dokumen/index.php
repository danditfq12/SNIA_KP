<?php
$title      = $title ?? 'Dokumen Saya';
$loa        = $loa ?? [];
$sertifikat = $sertifikat ?? [];
$lainnya    = $lainnya ?? [];

/* helper tanggal */
$fmt = fn($s,$t=false)=> $s ? date($t?'d M Y H:i':'d M Y', strtotime($s)) : '-';
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>

<div id="content">
  <main class="flex-fill page-wrap-blue">
    <div class="container-xxl px-3 px-md-4 py-4">

      <!-- HERO seragam -->
      <div class="hero-blue card-glass mb-3 p-3 p-md-4 d-flex justify-content-between align-items-start gap-3">
        <div>
          <h3 class="hero-title mb-1"><i class="bi bi-files me-2"></i>Dokumen</h3>
          <div class="text-white-75 small">Unduh LOA, Sertifikat & Dokumen Event Anda</div>
        </div>
        <div class="d-none d-md-block text-end">
          <div class="text-white-75 small">Hari ini</div>
          <div class="fw-semibold text-white"><?= date('d M Y') ?></div>
        </div>
      </div>

      <!-- DAFTAR DOKUMEN -->
      <div class="card shadow-soft card-glass-plain overflow-hidden">
        <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
          <span class="badge bg-blue-soft"><i class="bi bi-collection"></i></span>
          <h5 class="mb-0 fw-semibold text-blue-900">Daftar Dokumen</h5>
        </div>
        <div class="card-body">

          <!-- Tabs -->
          <ul class="nav nav-pills mb-3 gap-2 doc-tabs" id="docTabs" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="loa-tab" data-bs-toggle="pill" data-bs-target="#loa-pane" type="button" role="tab">
                <i class="bi bi-journal-text me-1"></i> LOA
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="serti-tab" data-bs-toggle="pill" data-bs-target="#serti-pane" type="button" role="tab">
                <i class="bi bi-award me-1"></i> Sertifikat
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="lainnya-tab" data-bs-toggle="pill" data-bs-target="#lainnya-pane" type="button" role="tab">
                <i class="bi bi-folder-symlink me-1"></i> Dokumen Lainnya
                <?php if (!empty($lainnya)): ?>
                  <span class="badge bg-white text-primary ms-1"><?= count($lainnya) ?></span>
                <?php endif; ?>
              </button>
            </li>
          </ul>

          <div class="tab-content" id="docTabsContent">
            <!-- LOA -->
            <div class="tab-pane fade show active" id="loa-pane" role="tabpanel" aria-labelledby="loa-tab">
              <?php if (empty($loa)): ?>
                <div class="empty-state">
                  <div class="empty-icon bg-primary-subtle text-primary"><i class="bi bi-inbox"></i></div>
                  <div class="empty-text">Belum ada LOA.</div>
                </div>
              <?php else: ?>
                <div class="list-group list-group-flush">
                  <?php foreach ($loa as $d):
                    $file  = (string)($d['file_path'] ?? '');
                    $fname = $file !== '' ? basename($file) : '';
                  ?>
                  <div class="list-group-item d-flex flex-column flex-md-row align-items-md-center justify-content-between">
                    <div class="mb-2 mb-md-0">
                      <div class="fw-semibold text-blue-900"><?= esc($d['event_title'] ?? '—') ?></div>
                      <div class="small text-muted"><?= esc($fmt($d['created_at'] ?? null, true)) ?></div>
                    </div>
                    <div class="d-flex gap-2">
                      <?php if ($fname !== ''): ?>
                        <a class="btn btn-primary" href="/presenter/dokumen/loa/download/<?= rawurlencode($fname) ?>">
                          <i class="bi bi-download me-1"></i>Unduh
                        </a>
                      <?php else: ?>
                        <span class="badge bg-danger-subtle">File tidak tersedia</span>
                      <?php endif; ?>
                    </div>
                  </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>

            <!-- Sertifikat -->
            <div class="tab-pane fade" id="serti-pane" role="tabpanel" aria-labelledby="serti-tab">
              <?php if (empty($sertifikat)): ?>
                <div class="empty-state">
                  <div class="empty-icon bg-primary-subtle text-primary"><i class="bi bi-inbox"></i></div>
                  <div class="empty-text">Belum ada sertifikat.</div>
                </div>
              <?php else: ?>
                <div class="list-group list-group-flush">
                  <?php foreach ($sertifikat as $d):
                    $file  = (string)($d['file_path'] ?? '');
                    $fname = $file !== '' ? basename($file) : '';
                  ?>
                  <div class="list-group-item d-flex flex-column flex-md-row align-items-md-center justify-content-between">
                    <div class="mb-2 mb-md-0">
                      <div class="fw-semibold text-blue-900"><?= esc($d['event_title'] ?? '—') ?></div>
                      <div class="small text-muted"><?= esc($fmt($d['created_at'] ?? null, true)) ?></div>
                    </div>
                    <div class="d-flex gap-2">
                      <?php if ($fname !== ''): ?>
                        <a class="btn btn-primary" href="/presenter/dokumen/sertifikat/download/<?= rawurlencode($fname) ?>">
                          <i class="bi bi-download me-1"></i>Unduh
                        </a>
                      <?php else: ?>
                        <span class="badge bg-danger-subtle">File tidak tersedia</span>
                      <?php endif; ?>
                    </div>
                  </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>

            <!-- Dokumen Lainnya -->
            <div class="tab-pane fade" id="lainnya-pane" role="tabpanel" aria-labelledby="lainnya-tab">
              <?php if (empty($lainnya)): ?>
                <div class="empty-state">
                  <div class="empty-icon bg-primary-subtle text-primary"><i class="bi bi-inbox"></i></div>
                  <div class="empty-text">Belum ada dokumen lainnya.</div>
                  <div class="small text-muted mt-2">Dokumen event seperti rundown, materi, dan form akan muncul di sini.</div>
                </div>
              <?php else: ?>
                <div class="list-group list-group-flush">
                  <?php 
                  // Group by event
                  $byEvent = [];
                  foreach ($lainnya as $d) {
                    $eventId = $d['event_id'] ?? 0;
                    if (!isset($byEvent[$eventId])) {
                      $byEvent[$eventId] = [
                        'event_title' => $d['event_title'] ?? '—',
                        'docs' => []
                      ];
                    }
                    $byEvent[$eventId]['docs'][] = $d;
                  }
                  
                  foreach ($byEvent as $eventId => $eventData):
                  ?>
                    <div class="list-group-item">
                      <div class="fw-bold text-blue-900 mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-calendar-event"></i>
                        <span><?= esc($eventData['event_title']) ?></span>
                        <span class="badge bg-primary-subtle text-primary"><?= count($eventData['docs']) ?> dokumen</span>
                      </div>
                      <div class="row g-2">
                        <?php foreach ($eventData['docs'] as $d):
                          $file  = (string)($d['file_path'] ?? '');
                          $fname = $file !== '' ? basename($file) : '';
                          $syarat = $d['syarat'] ?? 'Dokumen';
                          $ext = strtoupper(pathinfo($fname, PATHINFO_EXTENSION));
                          
                          // Icon based on file type
                          $icon = 'bi-file-earmark';
                          $iconColor = 'text-secondary';
                          if ($ext === 'PDF') { $icon = 'bi-file-earmark-pdf'; $iconColor = 'text-danger'; }
                          elseif (in_array($ext, ['DOC','DOCX'])) { $icon = 'bi-file-earmark-word'; $iconColor = 'text-primary'; }
                          elseif (in_array($ext, ['PPT','PPTX'])) { $icon = 'bi-file-earmark-easel'; $iconColor = 'text-warning'; }
                          elseif (in_array($ext, ['XLS','XLSX'])) { $icon = 'bi-file-earmark-excel'; $iconColor = 'text-success'; }
                          elseif (in_array($ext, ['JPG','JPEG','PNG'])) { $icon = 'bi-file-earmark-image'; $iconColor = 'text-info'; }
                          elseif (in_array($ext, ['ZIP','RAR'])) { $icon = 'bi-file-earmark-zip'; $iconColor = 'text-secondary'; }
                        ?>
                        <div class="col-12 col-md-6">
                          <div class="doc-card">
                            <div class="d-flex align-items-center gap-3">
                              <i class="bi <?= $icon ?> fs-3 <?= $iconColor ?>"></i>
                              <div class="flex-grow-1 overflow-hidden">
                                <div class="fw-semibold text-truncate"><?= esc($syarat) ?></div>
                                <div class="small text-muted"><?= $ext ?> • <?= esc($fmt($d['created_at'] ?? null)) ?></div>
                              </div>
                              <?php if ($fname !== ''): ?>
                                <a class="btn btn-sm btn-primary" href="/presenter/dokumen/lainnya/download/<?= rawurlencode($fname) ?>" title="Download">
                                  <i class="bi bi-download"></i>
                                </a>
                              <?php endif; ?>
                            </div>
                          </div>
                        </div>
                        <?php endforeach; ?>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
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
/* ===== Global palette & layout — sesuai patokan terbaru ===== */
:root{
  --blue-50:#eff6ff; --blue-100:#dbeafe; --blue-200:#bfdbfe;
  --blue-300:#93c5fd; --blue-400:#60a5fa; --blue-500:#3b82f6;
  --blue-600:#2563eb; --blue-700:#1d4ed8; --blue-800:#1e40af; --blue-900:#1e3a8a;

  --side-pad: clamp(1rem, 2.3vw, 2.2rem);
  --gutter:   1rem;
  --ink:#0f172a;
}

body{
  font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  font-size:15.5px;
  line-height:1.6;
  color:var(--ink);
}

/* Background gradient (radial + linear) sesuai patokan */
.page-wrap-blue{
  min-height:100vh; padding-top:72px;
  background:
    radial-gradient(1000px 380px at 10% -10%, rgba(59,130,246,.16), rgba(59,130,246,0) 60%),
    radial-gradient(1000px 380px at 90% 110%, rgba(59,130,246,.12), rgba(59,130,246,0) 70%),
    linear-gradient(180deg, var(--blue-50), #fff 40%);
}
.container-xxl{
  max-width:min(100%, 1560px);
  padding-left:var(--side-pad)!important;
  padding-right:var(--side-pad)!important;
  margin-inline:auto;
}
.row.g-3{ --bs-gutter-x: var(--gutter); --bs-gutter-y: var(--gutter); }

/* ===== Hero ===== */
.hero-blue{
  background:radial-gradient(1200px 400px at 10% -20%,var(--blue-600) 0,var(--blue-700) 40%,var(--blue-800) 100%) !important;
  color:#fff !important; border-radius:16px;
  border:1px solid rgba(255,255,255,.15);
  box-shadow:0 12px 28px rgba(30,64,175,.10);
}
.hero-blue.card-glass,.hero-blue.card-glass-plain{ backdrop-filter:none !important; }
.hero-title{ font-weight:800; letter-spacing:.25px; font-size:1.45rem; }
.text-white-75{ color:rgba(255,255,255,.85)!important; }

/* ===== Cards & badges ===== */
.card-glass-plain{
  backdrop-filter:blur(6px);
  background:rgba(255,255,255,.96);
  border-radius:14px;
  border:1px solid rgba(30,64,175,.10);
}
.shadow-soft{ box-shadow:0 10px 24px rgba(30,64,175,.08); }

.card-header{ padding:1rem 1rem .45rem 1rem !important; }
.card-body{   padding:1.05rem !important; }

.bg-blue-soft{ background:var(--blue-200); color:var(--blue-800); border-radius:12px; padding:.45rem .7rem; font-weight:700; font-size:.85rem; }
.bg-danger-subtle{  background:#fee2e2!important; color:#991b1b!important; }
.bg-primary-subtle{ background:#dbeafe!important; color:var(--blue-700)!important; }
.text-blue-900{ color:var(--blue-900)!important; }

/* ===== Tabs (nav-pills) ===== */
.doc-tabs .nav-link{
  border-radius:999px;
  font-weight:800;
  padding:.45rem 1rem;
  color:#1e3a8a;
  background:#eef3ff;
  border:1px solid rgba(30,64,175,.15);
}
.doc-tabs .nav-link.active{
  background:var(--blue-600);
  color:#fff;
  border-color:var(--blue-600);
  box-shadow:0 4px 12px rgba(37,99,235,.18);
}

/* ===== List group look ===== */
.list-group-item{
  padding:.9rem 0;
  background:transparent;
  border:0;
}
.list-group-item + .list-group-item{ border-top:1px solid rgba(30,64,175,.10); }

/* ===== Doc Card (for Dokumen Lainnya) ===== */
.doc-card{
  background:rgba(255,255,255,.8);
  border:1px solid rgba(30,64,175,.12);
  border-radius:12px;
  padding:.75rem;
  transition:all .2s ease;
}
.doc-card:hover{
  background:#fff;
  border-color:var(--blue-300);
  box-shadow:0 4px 12px rgba(37,99,235,.1);
  transform:translateY(-2px);
}

/* ===== Empty state ===== */
.empty-state{ padding:2rem 1rem; text-align:center; color:#64748b; }
.empty-icon{
  width:52px; height:52px; border-radius:12px; display:inline-flex; align-items:center; justify-content:center;
  font-size:1.35rem; margin-bottom:.55rem;
  border:1px solid rgba(30,64,175,.15);
}
.empty-text{ font-weight:700; letter-spacing:.2px; }

/* ===== Buttons ===== */
.btn{ border-radius:10px; font-weight:800; font-size:.98rem; padding:.6rem 1.05rem; }
.btn-primary{ background:var(--blue-600); border-color:var(--blue-600); box-shadow:0 4px 12px rgba(37,99,235,.2); }
.btn-sm{ padding:.4rem .75rem; font-size:.875rem; }

/* ===== Responsive ===== */
@media (max-width:575.98px){
  .container-xxl{ padding-left: calc(var(--side-pad) - .25rem) !important; padding-right: calc(var(--side-pad) - .25rem) !important; }
  .hero-blue{ border-radius:14px; padding:1.25rem!important; margin-bottom:1rem!important; }
  .hero-title{ font-size:1.25rem; }
  .list-group-item{ padding:.85rem 0; }
  .doc-card{ padding:.6rem; }
}
</style>

<script>
  // aktifkan tab berdasar hash (#loa / #sertifikat / #lainnya)
  (function(){
    const hash = (location.hash||'').toLowerCase();
    const loaBtn     = document.getElementById('loa-tab');
    const sertiBtn   = document.getElementById('serti-tab');
    const lainnyaBtn = document.getElementById('lainnya-tab');
    
    if(hash === '#sertifikat' && sertiBtn){ new bootstrap.Tab(sertiBtn).show(); }
    else if(hash === '#lainnya' && lainnyaBtn){ new bootstrap.Tab(lainnyaBtn).show(); }
    else if(hash === '#loa' && loaBtn){ new bootstrap.Tab(loaBtn).show(); }
  })();
</script>