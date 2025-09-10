<?= view('partials/header', ['title' => $title ?? 'Detail Abstrak']) ?>
<?= view('partials/sidebar_presenter', ['active' => 'abstrak']) ?>

<main id="content" class="p-4">
  <?= view('partials/alerts') ?>

  <?php
    $labelMap = [
      'menunggu'        => ['label'=>'menunggu','class'=>'warning'],
      'sedang_direview' => ['label'=>'sedang direview','class'=>'info'],
      'diterima'        => ['label'=>'diacc','class'=>'success'], // tampil "diacc"
      'ditolak'         => ['label'=>'ditolak','class'=>'danger'],
      'revisi'          => ['label'=>'revisi','class'=>'secondary'],
    ];
    $cur = $labelMap[$abstract['status']] ?? ['label'=>$abstract['status'],'class'=>'secondary'];
  ?>

  <div class="d-flex align-items-center justify-content-between mb-3">
    <div>
      <h3 class="mb-1"><?= esc($abstract['judul']) ?></h3>
      <div class="small text-muted">
        Event: <?= esc($abstract['event_title'] ?? '—') ?> ·
        Upload: <?= date('d M Y H:i', strtotime($abstract['tanggal_upload'])) ?> ·
        Kategori: <?= esc($abstract['nama_kategori'] ?? '-') ?>
      </div>
    </div>
    <div>
      <span class="badge bg-<?= $cur['class'] ?>"><?= esc($cur['label']) ?></span>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-body">
      <div class="d-flex gap-2 flex-wrap">
        <a class="btn btn-outline-secondary"
           href="<?= site_url('presenter/abstrak/download/'.$abstract['file_abstrak']) ?>">
          <i class="bi bi-download me-1"></i> Download File
        </a>

        <?php if ($can_revise): ?>
          <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#revModal">
            <i class="bi bi-pencil-square me-1"></i> Upload Revisi
          </button>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-lg-7">
      <div class="card">
        <div class="card-header bg-light"><strong>Ringkasan</strong></div>
        <div class="card-body">
          <dl class="row mb-0">
            <dt class="col-sm-4">Author</dt>
            <dd class="col-sm-8"><?= esc($abstract['author_name'] ?? '-') ?></dd>

            <dt class="col-sm-4">Revisi ke</dt>
            <dd class="col-sm-8"><?= (int)$abstract['revisi_ke'] ?></dd>

            <?php if (!empty($event['abstract_deadline'])): ?>
              <dt class="col-sm-4">Deadline Submission</dt>
              <dd class="col-sm-8"><?= date('d M Y H:i', strtotime($event['abstract_deadline'])) ?></dd>
            <?php endif; ?>
          </dl>
        </div>
      </div>
    </div>
    <div class="col-lg-5">
      <div class="card">
        <div class="card-header bg-light"><strong>Riwayat Review</strong></div>
        <div class="card-body">
          <?php if (!empty($reviews)): ?>
            <div class="vstack gap-3">
              <?php foreach ($reviews as $r): ?>
                <div class="border rounded p-2">
                  <div class="small text-muted">
                    <?= esc($r['reviewer_name'] ?? 'Reviewer') ?> ·
                    <?= date('d M Y H:i', strtotime($r['tanggal_review'])) ?>
                  </div>
                  <div><?= nl2br(esc($r['catatan'] ?? '-')) ?></div>
                  <?php if (!empty($r['status'])): ?>
                    <span class="badge bg-secondary mt-1"><?= esc($r['status']) ?></span>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="text-muted">Belum ada review.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

</main>

<?php if ($can_revise): ?>
<!-- Modal Revisi -->
<div class="modal fade" id="revModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" action="<?= site_url('presenter/abstrak/upload') ?>" method="post" enctype="multipart/form-data">
      <input type="hidden" name="revision_id" value="<?= (int)$abstract['id_abstrak'] ?>">
      <input type="hidden" name="event_id" value="<?= (int)$abstract['event_id'] ?>">
      <input type="hidden" name="id_kategori" value="<?= (int)$abstract['id_kategori'] ?>">

      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Upload Revisi</h5>
        <button class="btn-close" data-bs-dismiss="modal" type="button"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Judul (boleh diubah)</label>
          <input type="text" name="judul" class="form-control" value="<?= esc($abstract['judul']) ?>" maxlength="255" required>
        </div>
        <div class="mb-3">
          <label class="form-label">File Baru (PDF/DOC/DOCX · maks 10MB)</label>
          <input type="file" name="file_abstrak" class="form-control" accept=".pdf,.doc,.docx" required>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Batal</button>
        <button class="btn btn-warning" type="submit"><i class="bi bi-upload me-1"></i> Upload Revisi</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?= view('partials/footer') ?>
<?= view('partials/header', ['title' => $title ?? 'Detail Abstrak']) ?>
<?= view('partials/sidebar_presenter', ['active' => 'abstrak']) ?>

<main id="content" class="p-4">
  <?= view('partials/alerts') ?>

  <?php
    $labelMap = [
      'menunggu'        => ['label'=>'menunggu','class'=>'warning'],
      'sedang_direview' => ['label'=>'sedang direview','class'=>'info'],
      'diterima'        => ['label'=>'diacc','class'=>'success'], // tampil "diacc"
      'ditolak'         => ['label'=>'ditolak','class'=>'danger'],
      'revisi'          => ['label'=>'revisi','class'=>'secondary'],
    ];
    $cur = $labelMap[$abstract['status']] ?? ['label'=>$abstract['status'],'class'=>'secondary'];
  ?>

  <div class="d-flex align-items-center justify-content-between mb-3">
    <div>
      <h3 class="mb-1"><?= esc($abstract['judul']) ?></h3>
      <div class="small text-muted">
        Event: <?= esc($abstract['event_title'] ?? '—') ?> ·
        Upload: <?= date('d M Y H:i', strtotime($abstract['tanggal_upload'])) ?> ·
        Kategori: <?= esc($abstract['nama_kategori'] ?? '-') ?>
      </div>
    </div>
    <div>
      <span class="badge bg-<?= $cur['class'] ?>"><?= esc($cur['label']) ?></span>
    </div>
  </div>

  <div class="card mb-3">
    <div class="card-body">
      <div class="d-flex gap-2 flex-wrap">
        <a class="btn btn-outline-secondary"
           href="<?= site_url('presenter/abstrak/download/'.$abstract['file_abstrak']) ?>">
          <i class="bi bi-download me-1"></i> Download File
        </a>

        <?php if ($can_revise): ?>
          <button class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#revModal">
            <i class="bi bi-pencil-square me-1"></i> Upload Revisi
          </button>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <div class="row g-3">
    <div class="col-lg-7">
      <div class="card">
        <div class="card-header bg-light"><strong>Ringkasan</strong></div>
        <div class="card-body">
          <dl class="row mb-0">
            <dt class="col-sm-4">Author</dt>
            <dd class="col-sm-8"><?= esc($abstract['author_name'] ?? '-') ?></dd>

            <dt class="col-sm-4">Revisi ke</dt>
            <dd class="col-sm-8"><?= (int)$abstract['revisi_ke'] ?></dd>

            <?php if (!empty($event['abstract_deadline'])): ?>
              <dt class="col-sm-4">Deadline Submission</dt>
              <dd class="col-sm-8"><?= date('d M Y H:i', strtotime($event['abstract_deadline'])) ?></dd>
            <?php endif; ?>
          </dl>
        </div>
      </div>
    </div>
    <div class="col-lg-5">
      <div class="card">
        <div class="card-header bg-light"><strong>Riwayat Review</strong></div>
        <div class="card-body">
          <?php if (!empty($reviews)): ?>
            <div class="vstack gap-3">
              <?php foreach ($reviews as $r): ?>
                <div class="border rounded p-2">
                  <div class="small text-muted">
                    <?= esc($r['reviewer_name'] ?? 'Reviewer') ?> ·
                    <?= date('d M Y H:i', strtotime($r['tanggal_review'])) ?>
                  </div>
                  <div><?= nl2br(esc($r['catatan'] ?? '-')) ?></div>
                  <?php if (!empty($r['status'])): ?>
                    <span class="badge bg-secondary mt-1"><?= esc($r['status']) ?></span>
                  <?php endif; ?>
                </div>
              <?php endforeach; ?>
            </div>
          <?php else: ?>
            <div class="text-muted">Belum ada review.</div>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>

</main>

<?php if ($can_revise): ?>
<!-- Modal Revisi -->
<div class="modal fade" id="revModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" action="<?= site_url('presenter/abstrak/upload') ?>" method="post" enctype="multipart/form-data">
      <input type="hidden" name="revision_id" value="<?= (int)$abstract['id_abstrak'] ?>">
      <input type="hidden" name="event_id" value="<?= (int)$abstract['event_id'] ?>">
      <input type="hidden" name="id_kategori" value="<?= (int)$abstract['id_kategori'] ?>">

      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-pencil-square me-2"></i>Upload Revisi</h5>
        <button class="btn-close" data-bs-dismiss="modal" type="button"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Judul (boleh diubah)</label>
          <input type="text" name="judul" class="form-control" value="<?= esc($abstract['judul']) ?>" maxlength="255" required>
        </div>
        <div class="mb-3">
          <label class="form-label">File Baru (PDF/DOC/DOCX · maks 10MB)</label>
          <input type="file" name="file_abstrak" class="form-control" accept=".pdf,.doc,.docx" required>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Batal</button>
        <button class="btn btn-warning" type="submit"><i class="bi bi-upload me-1"></i> Upload Revisi</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?= view('partials/footer') ?>
