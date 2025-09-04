<?php
  $title = 'Sertifikat Saya';
  $certs = $certs ?? [];
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_audience') ?>

<div id="content">
  <main class="flex-fill">
    <div class="container-fluid p-3 p-md-4">

      <div class="d-flex align-items-center justify-content-between mb-3">
        <h4 class="mb-0">Sertifikat</h4>
        <a class="btn btn-sm btn-outline-secondary d-none d-md-inline-flex" href="<?= site_url('audience/dashboard') ?>">
          <i class="bi bi-house me-1"></i> Dashboard
        </a>
      </div>

      <?php if (session('error')): ?>
        <div class="alert alert-danger"><?= esc(session('error')) ?></div>
      <?php endif; ?>
      <?php if (session('message')): ?>
        <div class="alert alert-success"><?= esc(session('message')) ?></div>
      <?php endif; ?>

      <?php if (empty($certs)): ?>
        <div class="p-4 text-center border rounded-3 bg-light-subtle">
          <div class="mb-2"><i class="bi bi-award fs-3 text-secondary"></i></div>
          <div class="fw-semibold">Belum ada sertifikat</div>
          <div class="text-muted small">Sertifikat akan muncul setelah kehadiran diverifikasi oleh panitia.</div>
        </div>
      <?php else: ?>

        <!-- Desktop/grid -->
        <div class="row g-3 d-none d-md-flex">
          <?php foreach ($certs as $c): ?>
            <?php
              $id     = (int)($c['id_dokumen'] ?? $c['id'] ?? 0);
              $fn     = basename($c['file_path'] ?? '');
              $judul  = $c['event_title'] ?? ('Sertifikat - ' . ($fn ?: ('#'.$id)));
              $tanggal= isset($c['uploaded_at']) ? date('d M Y', strtotime($c['uploaded_at'])) : '';
              $prev   = site_url('audience/dokumen/sertifikat/download/'.urlencode($fn ?: (string)$id).'?preview=1');
              $down   = site_url('audience/dokumen/sertifikat/download/'.urlencode($fn ?: (string)$id));
            ?>
            <div class="col-12 col-lg-6 col-xl-4">
              <div class="card h-100 shadow-sm border-0">
                <div class="card-body d-flex flex-column">
                  <div class="d-flex justify-content-between">
                    <div class="fw-semibold mb-1 text-truncate" title="<?= esc($judul) ?>"><?= esc($judul) ?></div>
                    <span class="badge bg-success-subtle text-success">Siap</span>
                  </div>
                  <small class="text-muted"><?= esc($tanggal) ?></small>

                  <div class="mt-auto pt-3 d-flex gap-2">
                    <button class="btn btn-outline-secondary btn-sm flex-fill" onclick="openCertPreview('<?= esc($prev) ?>')">
                      <i class="bi bi-eye me-1"></i>Lihat
                    </button>
                    <a class="btn btn-primary btn-sm flex-fill" href="<?= $down ?>">
                      <i class="bi bi-download me-1"></i>Unduh
                    </a>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

        <!-- Mobile/list -->
        <div class="d-md-none vstack gap-2">
          <?php foreach ($certs as $c): ?>
            <?php
              $id     = (int)($c['id_dokumen'] ?? $c['id'] ?? 0);
              $fn     = basename($c['file_path'] ?? '');
              $judul  = $c['event_title'] ?? ('Sertifikat - ' . ($fn ?: ('#'.$id)));
              $tanggal= isset($c['uploaded_at']) ? date('d M Y', strtotime($c['uploaded_at'])) : '';
              $prev   = site_url('audience/dokumen/sertifikat/download/'.urlencode($fn ?: (string)$id).'?preview=1');
              $down   = site_url('audience/dokumen/sertifikat/download/'.urlencode($fn ?: (string)$id));
            ?>
            <div class="card shadow-sm border-0">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-start">
                  <div class="me-2">
                    <div class="fw-semibold text-truncate" style="max-width:220px;" title="<?= esc($judul) ?>">
                      <?= esc($judul) ?>
                    </div>
                    <small class="text-muted"><?= esc($tanggal) ?></small>
                  </div>
                  <span class="badge bg-success-subtle text-success">Siap</span>
                </div>

                <div class="mt-2 d-grid gap-2">
                  <button class="btn btn-outline-secondary btn-sm" onclick="openCertPreview('<?= esc($prev) ?>')">
                    <i class="bi bi-eye me-1"></i>Lihat
                  </button>
                  <a class="btn btn-primary btn-sm" href="<?= $down ?>">
                    <i class="bi bi-download me-1"></i>Unduh
                  </a>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>

      <?php endif; ?>

    </div>
  </main>
</div>

<!-- Modal Preview -->
<div class="modal fade" id="certPreviewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-fullscreen-md-down">
    <div class="modal-content">
      <div class="modal-header">
        <h6 class="modal-title"><i class="bi bi-file-earmark-richtext me-1"></i>Pratinjau Sertifikat</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
      </div>
      <div class="modal-body p-0" style="height:75vh;">
        <iframe id="certFrame" src="" title="Sertifikat" style="border:0;width:100%;height:100%;"></iframe>
      </div>
    </div>
  </div>
</div>

<script>
  let certModal=null;
  function openCertPreview(url){
    document.getElementById('certFrame').src = url;
    if(!certModal){ certModal = new bootstrap.Modal(document.getElementById('certPreviewModal')); }
    certModal.show();
  }
</script>

<?= $this->include('partials/footer') ?>
