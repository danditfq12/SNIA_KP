<?php
$title      = $title ?? 'Dokumen Saya';
$loa        = $loa ?? [];
$sertifikat = $sertifikat ?? [];
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <!-- Header -->
      <div class="header-section header-blue d-flex justify-content-between align-items-center mb-3">
        <div>
          <h3 class="welcome-text mb-1"><i class="bi bi-files me-2"></i>Dokumen</h3>
          <div class="text-white-50">Unduh LOA & Sertifikat Anda</div>
        </div>
        <div class="text-end d-none d-md-block">
          <small class="text-white-50 d-block">Hari ini</small>
          <strong class="text-white"><?= date('d M Y') ?></strong>
        </div>
      </div>

      <div class="card shadow-sm border-0 overflow-hidden">
        <div class="card-header bg-gradient-primary text-white">
          <div class="d-flex align-items-center justify-content-between">
            <h5 class="mb-0"><i class="bi bi-collection me-2"></i>Daftar Dokumen</h5>
          </div>
        </div>
        <div class="card-body">

          <ul class="nav nav-pills mb-3" id="docTabs" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="loa-tab" data-bs-toggle="pill" data-bs-target="#loa-pane" type="button" role="tab">LOA</button>
            </li>
            <li class="nav-item ms-2" role="presentation">
              <button class="nav-link" id="serti-tab" data-bs-toggle="pill" data-bs-target="#serti-pane" type="button" role="tab">Sertifikat</button>
            </li>
          </ul>

          <div class="tab-content" id="docTabsContent">
            <!-- LOA -->
            <div class="tab-pane fade show active" id="loa-pane" role="tabpanel" aria-labelledby="loa-tab">
              <?php if (empty($loa)): ?>
                <div class="text-muted">Belum ada LOA.</div>
              <?php else: ?>
                <div class="list-group list-group-flush">
                  <?php foreach ($loa as $d):
                    $file  = (string)($d['file_path'] ?? '');
                    $fname = $file !== '' ? basename($file) : '';
                  ?>
                  <div class="list-group-item d-flex flex-column flex-md-row align-items-md-center justify-content-between">
                    <div class="mb-2 mb-md-0">
                      <div class="fw-semibold"><?= esc($d['event_title'] ?? '—') ?></div>
                      <div class="small text-muted"><?= !empty($d['created_at']) ? date('d M Y H:i', strtotime($d['created_at'])) : '-' ?></div>
                    </div>
                    <div class="d-flex gap-2">
                      <?php if ($fname !== ''): ?>
                        <a class="btn btn-primary" href="/presenter/dokumen/loa/download/<?= rawurlencode($fname) ?>">
                          <i class="bi bi-download"></i> Unduh
                        </a>
                      <?php else: ?>
                        <span class="text-danger small">File tidak tersedia.</span>
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
                <div class="text-muted">Belum ada sertifikat.</div>
              <?php else: ?>
                <div class="list-group list-group-flush">
                  <?php foreach ($sertifikat as $d):
                    $file  = (string)($d['file_path'] ?? '');
                    $fname = $file !== '' ? basename($file) : '';
                  ?>
                  <div class="list-group-item d-flex flex-column flex-md-row align-items-md-center justify-content-between">
                    <div class="mb-2 mb-md-0">
                      <div class="fw-semibold"><?= esc($d['event_title'] ?? '—') ?></div>
                      <div class="small text-muted"><?= !empty($d['created_at']) ? date('d M Y H:i', strtotime($d['created_at'])) : '-' ?></div>
                    </div>
                    <div class="d-flex gap-2">
                      <?php if ($fname !== ''): ?>
                        <a class="btn btn-primary" href="/presenter/dokumen/sertifikat/download/<?= rawurlencode($fname) ?>">
                          <i class="bi bi-download"></i> Unduh
                        </a>
                      <?php else: ?>
                        <span class="text-danger small">File tidak tersedia.</span>
                      <?php endif; ?>
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
  :root{ --primary-color:#2563eb; --info-color:#06b6d4; }
  .header-section.header-blue{
    background:linear-gradient(135deg,var(--primary-color),#1e40af);
    color:#fff; padding:24px; border-radius:16px; box-shadow:0 8px 28px rgba(0,0,0,.12);
  }
  .welcome-text{ font-weight:700; }
  .bg-gradient-primary{ background:linear-gradient(135deg,var(--primary-color),var(--info-color))!important; }
</style>

<script>
  // aktifkan tab berdasar hash (#loa / #sertifikat)
  (function(){
    const hash = (location.hash||'').toLowerCase();
    const loaBtn   = document.getElementById('loa-tab');
    const sertiBtn = document.getElementById('serti-tab');
    if(hash === '#sertifikat' && sertiBtn){ new bootstrap.Tab(sertiBtn).show(); }
    if(hash === '#loa' && loaBtn){ new bootstrap.Tab(loaBtn).show(); }
  })();
</script>