<?php
$title = $title ?? 'Sertifikat Saya';
$certs = $certs ?? [];
$pk    = $pk    ?? 'id_dokumen';

$fmtDT = fn($s)=> $s ? date('d M Y H:i', strtotime($s)) : '-';
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_audience') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">
      <h3 class="mb-3"><?= esc($title) ?></h3>

      <?php if (session('error')): ?>
        <div class="alert alert-danger"><?= esc(session('error')) ?></div>
      <?php endif; ?>
      <?php if (session('success')): ?>
        <div class="alert alert-success"><?= esc(session('success')) ?></div>
      <?php endif; ?>

      <?php if (empty($certs)): ?>
        <div class="p-4 text-center border rounded-3 bg-light-subtle">
          <div class="mb-2"><i class="bi bi-award fs-3 text-secondary"></i></div>
          <div class="fw-semibold">Belum ada sertifikat</div>
          <div class="text-muted small">Sertifikat akan muncul setelah diverifikasi panitia.</div>
        </div>
      <?php else: ?>
        <div class="row g-3">
          <?php foreach ($certs as $c): ?>
            <?php
              $id   = (int)($c[$pk] ?? 0);
              $name = basename((string)($c['file_path'] ?? 'sertifikat.pdf'));
              $dl   = site_url('audience/dokumen/sertifikat/download/'.$id);
              $pv   = $dl.'?preview=1';
              $ev   = trim((string)($c['event_title'] ?? ''));
            ?>
            <div class="col-12 col-md-6 col-lg-4">
              <div class="card h-100 shadow-sm border-0">
                <div class="card-body d-flex flex-column">
                  <div class="fw-semibold mb-1 text-truncate" title="<?= esc($name) ?>">
                    <?= esc($name) ?>
                  </div>

                  <div class="small text-muted">
                    Diunggah: <?= esc($fmtDT($c['uploaded_at'] ?? null)) ?>
                  </div>

                  <div class="mt-1">
                    <span class="badge bg-primary-subtle text-primary">
                      <i class="bi bi-calendar-event me-1"></i>
                      Event: <?= $ev !== '' ? esc($ev) : '—' ?>
                    </span>
                  </div>

                  <div class="mt-auto d-grid gap-2 pt-2">
                    <a class="btn btn-outline-primary" href="<?= $pv ?>" target="_blank" rel="noopener">
                      <i class="bi bi-eye me-1"></i> Preview
                    </a>
                    <a class="btn btn-primary" href="<?= $dl ?>">
                      <i class="bi bi-download me-1"></i> Download
                    </a>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>
