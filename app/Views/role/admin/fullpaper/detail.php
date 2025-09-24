<?php
$title       = $title ?? 'Detail Full Paper';
$submission  = $submission ?? [];
$author      = $author ?? ['name'=>null,'email'=>null];
$coauthors   = $coauthors ?? [];
$history     = $history ?? [];
$reviewers   = $reviewers ?? [];
$assigned    = $assignedReviewers ?? [];

$status = strtoupper($submission['full_paper_status'] ?? 'NONE');

$badgeMap = [
  'NONE'     => 'secondary',
  'UPLOADED' => 'info',
  'REVISION' => 'warning',
  'ACCEPTED' => 'success',
  'REJECTED' => 'danger',
];
$statusLabel = [
  'NONE'     => '—',
  'UPLOADED' => 'Diunggah',
  'REVISION' => 'Revisi',
  'ACCEPTED' => 'Diterima',
  'REJECTED' => 'Ditolak',
];

$badge      = $badgeMap[$status] ?? 'secondary';
$uploadedAt = !empty($submission['full_paper_uploaded_at']) ? date('d M Y H:i', strtotime($submission['full_paper_uploaded_at'])) : '—';
$revisiKe   = isset($submission['revisi_ke']) ? (int)$submission['revisi_ke'] : 0;

// Fallback nama & email (jaga-jaga)
if (empty($author['name'])) {
  foreach (['penulis_nama','nama_lengkap','presenter_name','author_name','nama'] as $k) {
    if (!empty($submission[$k])) { $author['name'] = $submission[$k]; break; }
  }
}
if (empty($author['email'])) {
  foreach (['penulis_email','email','presenter_email','author_email'] as $k) {
    if (!empty($submission[$k])) { $author['email'] = $submission[$k]; break; }
  }
}
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_admin') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <div class="header-section header-blue d-flex justify-content-between align-items-center mb-3">
        <div>
          <h3 class="welcome-text mb-1">
            <i class="bi bi-journal-richtext me-2"></i><?= esc($title) ?>
          </h3>
          <div class="text-white-50"><?= esc($submission['title'] ?? '-') ?></div>
        </div>
        <div>
          <span class="badge bg-<?= $badge ?> fs-6"><?= esc($statusLabel[$status] ?? $status) ?></span>
        </div>
      </div>

      <div class="row g-3">
        <!-- Kolom kiri -->
        <div class="col-12 col-lg-8">

          <!-- Informasi -->
          <div class="card shadow-sm mb-3">
            <div class="card-header bg-light">
              <h6 class="mb-0"><i class="bi bi-info-circle me-2"></i>Informasi Full Paper</h6>
            </div>
            <div class="card-body">
              <dl class="row mb-0">
                <dt class="col-md-3">Di-upload pada</dt>
                <dd class="col-md-9"><?= esc($uploadedAt) ?></dd>

                <dt class="col-md-3">Judul</dt>
                <dd class="col-md-9"><?= esc($submission['title'] ?? '-') ?></dd>

                <dt class="col-md-3">Penulis</dt>
                <dd class="col-md-9">
                  <div><?= esc($author['name'] ?? '-') ?></div>
                  <?php if (!empty($author['email'])): ?>
                    <small class="text-muted"><?= esc($author['email']) ?></small>
                  <?php endif; ?>
                </dd>

                <dt class="col-md-3">Co-author</dt>
                <dd class="col-md-9">
                  <?php if (!empty($coauthors)): ?>
                    <div class="table-responsive">
                      <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                          <tr><th>Nama</th><th>Email</th><th>Afiliasi</th></tr>
                        </thead>
                        <tbody>
                          <?php foreach ($coauthors as $c): ?>
                            <tr>
                              <td><?= esc($c['nama'] ?? ($c['name'] ?? '-')) ?></td>
                              <td><?= esc($c['email'] ?? '-') ?></td>
                              <td><?= esc($c['afiliasi'] ?? ($c['affiliation'] ?? '-')) ?></td>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </div>
                  <?php else: ?>
                    <span class="text-muted">—</span>
                  <?php endif; ?>
                </dd>

                <dt class="col-md-3">Event</dt>
                <dd class="col-md-9"><?= esc($submission['event_title'] ?? '-') ?></dd>

                <dt class="col-md-3">Revisi ke</dt>
                <dd class="col-md-9"><?= (int)$revisiKe ?></dd>

                <dt class="col-md-3">Status</dt>
                <dd class="col-md-9"><span class="badge bg-<?= $badge ?>"><?= esc($statusLabel[$status] ?? $status) ?></span></dd>
              </dl>
            </div>
          </div>

          <!-- Preview PDF -->
          <div class="card shadow-sm mb-3">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
              <h6 class="mb-0"><i class="bi bi-file-earmark-pdf me-2"></i>File Full Paper</h6>
              <div class="d-flex gap-2">
                <a class="btn btn-outline-secondary btn-sm" target="_blank"
                   href="<?= site_url('admin/fullpaper/download/'.(int)$submission['id']) ?>">
                  <i class="bi bi-download"></i> Download
                </a>
              </div>
            </div>
            <div class="card-body" style="height:72vh;">
              <iframe
                src="<?= site_url('admin/fullpaper/view/'.(int)$submission['id']) ?>"
                title="Preview Full Paper"
                style="width:100%;height:100%;border:1px solid #e5e7eb;border-radius:8px;"
              ></iframe>
            </div>
          </div>

          <!-- Riwayat Revisi -->
          <div class="card shadow-sm mb-3">
            <div class="card-header bg-light">
              <h6 class="mb-0"><i class="bi bi-clock-history me-2"></i>Riwayat Revisi</h6>
            </div>
            <div class="card-body">
              <?php if (!empty($history)): ?>
                <div class="table-responsive">
                  <table class="table table-sm align-middle">
                    <thead class="table-light">
                      <tr>
                        <th>#</th>
                        <th>Revisi ke</th>
                        <th>Status</th>
                        <th>Tgl Upload</th>
                        <th>Aksi</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                        $map = ['NONE'=>'secondary','UPLOADED'=>'info','REVISION'=>'warning','ACCEPTED'=>'success','REJECTED'=>'danger'];
                        $lab = ['NONE'=>'—','UPLOADED'=>'Diunggah','REVISION'=>'Revisi','ACCEPTED'=>'Diterima','REJECTED'=>'Ditolak'];
                        $no=1;
                        foreach ($history as $h):
                          $st = strtoupper($h['full_paper_status'] ?? 'NONE');
                          $cls = $map[$st] ?? 'secondary';
                          $ts = !empty($h['full_paper_uploaded_at']) ? date('d/m/Y H:i', strtotime($h['full_paper_uploaded_at'])) : '—';
                      ?>
                        <tr>
                          <td><?= $no++ ?></td>
                          <td><?= (int)($h['revisi_ke'] ?? 0) ?></td>
                          <td><span class="badge bg-<?= $cls ?>"><?= esc($lab[$st] ?? $st) ?></span></td>
                          <td><?= esc($ts) ?></td>
                          <td>
                            <a class="btn btn-sm btn-outline-primary"
                               href="<?= site_url('admin/fullpaper/detail/'.(int)$h['id']) ?>">
                              <i class="bi bi-eye"></i> Lihat
                            </a>
                          </td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php else: ?>
                <div class="text-muted">Belum ada riwayat revisi.</div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Reviewer ditugaskan -->
          <div class="card shadow-sm mb-3">
            <div class="card-header bg-light d-flex justify-content-between align-items-center">
              <h6 class="mb-0"><i class="bi bi-people me-2"></i>Reviewer Ditugaskan</h6>
              <span class="badge bg-secondary"><?= count($assigned) ?> / min 3</span>
            </div>
            <div class="card-body">
              <?php if (!empty($assigned)): ?>
                <div class="table-responsive mb-3">
                  <table class="table table-sm align-middle">
                    <thead class="table-light">
                      <tr>
                        <th>#</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Assigned At</th>
                      </tr>
                    </thead>
                    <tbody>
                      <?php $i=1; foreach ($assigned as $ar): ?>
                        <tr>
                          <td><?= $i++ ?></td>
                          <td><?= esc($ar['name'] ?? '-') ?></td>
                          <td><?= esc($ar['email'] ?? '-') ?></td>
                          <td><?= !empty($ar['assigned_at']) ? date('d/m/Y H:i', strtotime($ar['assigned_at'])) : '—' ?></td>
                        </tr>
                      <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              <?php else: ?>
                <div class="text-muted mb-2">Belum ada reviewer yang ditugaskan.</div>
              <?php endif; ?>

              <form method="post" action="<?= site_url('admin/fullpaper/assign/'.(int)$submission['id']) ?>" class="row g-2">
                <?= csrf_field() ?>
                <div class="col-12 col-md-8">
                  <label class="form-label">Tambah Reviewer</label>
                  <select name="reviewer_id" class="form-select" required>
                    <option value="">-- pilih --</option>
                    <?php foreach ($reviewers as $rv): ?>
                      <option value="<?= (int)$rv['id'] ?>"><?= esc(($rv['name'] ?? 'Reviewer').' - '.($rv['email'] ?? '')) ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <div class="col-12 col-md-4 d-flex align-items-end">
                  <button class="btn btn-primary w-100"><i class="bi bi-person-plus"></i> Tugaskan</button>
                </div>
              </form>
              <small class="text-muted d-block mt-2">* Disarankan minimal 3 reviewer.</small>
            </div>
          </div>

        </div>

        <!-- Kolom kanan: keputusan -->
        <div class="col-12 col-lg-4">
          <div class="card shadow-sm">
            <div class="card-header bg-light">
              <h6 class="mb-0"><i class="bi bi-flag me-2"></i>Keputusan</h6>
            </div>
            <div class="card-body">
              <form action="<?= site_url('admin/fullpaper/set-status/'.(int)$submission['id']) ?>" method="post" class="d-grid gap-2">
                <?= csrf_field() ?>
                <div>
                  <label class="form-label">Set Status</label>
                  <select class="form-select" name="status" required>
                    <?php foreach (['UPLOADED','REVISION','ACCEPTED','REJECTED'] as $opt): ?>
                      <option value="<?= $opt ?>" <?= $status===$opt?'selected':'' ?>><?= $statusLabel[$opt] ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
                <button class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button>
              </form>
              <small class="text-muted d-block mt-2">
                Status <b>Diterima</b> akan mengaktifkan <i>eligible_to_pay</i> bila kolom tersebut tersedia.
              </small>
            </div>
          </div>
        </div>

      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<style>
  .header-section.header-blue{
    background: linear-gradient(135deg,#2563eb,#1e40af);
    color:#fff; padding:24px; border-radius:16px; box-shadow:0 8px 28px rgba(0,0,0,.12);
  }
  .welcome-text{ font-weight:700; font-size:1.3rem; }
</style>