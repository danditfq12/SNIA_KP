<?= view('partials/header', ['title' => $title ?? 'Abstrak Presenter']) ?>
<?= view('partials/sidebar_presenter', ['active' => 'abstrak']) ?>

<main id="content" class="p-4">
  <?= view('partials/alerts') ?>

  <div class="d-flex align-items-center justify-content-between mb-4">
    <div>
      <h2 class="mb-1"><i class="bi bi-file-earmark-text me-2"></i> Manajemen Abstrak</h2>
      <div class="text-muted">Upload dan kelola abstrak untuk partisipasi sebagai presenter.</div>
    </div>
    <?php if (!empty($available_events)): ?>
      <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
        <i class="bi bi-plus-lg me-1"></i> Upload Abstrak
      </button>
    <?php endif; ?>
  </div>

  <?php if (!empty($available_events)): ?>
    <div class="card mb-4">
      <div class="card-header bg-light">
        <strong>Event Tersedia untuk Submission Abstrak</strong>
      </div>
      <div class="card-body">
        <div class="row g-3">
          <?php foreach ($available_events as $ev): ?>
            <div class="col-md-6">
              <div class="border rounded p-3 h-100">
                <div class="d-flex justify-content-between">
                  <div>
                    <div class="fw-semibold"><?= esc($ev['title']) ?></div>
                    <small class="text-muted">
                      <?= date('d M Y', strtotime($ev['event_date'])) ?>
                      <?php if (!empty($ev['abstract_deadline'])): ?>
                        · Deadline: <?= date('d M Y', strtotime($ev['abstract_deadline'])) ?>
                      <?php endif; ?>
                    </small>
                  </div>
                  <div><span class="badge bg-success">Buka</span></div>
                </div>
                <div class="mt-3">
                  <button class="btn btn-outline-primary btn-sm"
                          data-bs-toggle="modal" data-bs-target="#uploadModal"
                          onclick="prefillEvent(<?= (int)$ev['id'] ?>)">
                    Upload Abstrak
                  </button>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  <?php else: ?>
    <div class="alert alert-info mb-4">
      Tidak ada event yang sedang membuka submission abstrak saat ini.
      <a class="alert-link" href="<?= site_url('presenter/events') ?>">Lihat daftar event</a>.
    </div>
  <?php endif; ?>

  <h5 class="mb-3"><i class="bi bi-list-ul me-2"></i> Daftar Abstrak Anda</h5>
  <?php if (!empty($abstracts)): ?>
    <?php
      $statusLabel = [
        'menunggu'        => 'menunggu',
        'sedang_direview' => 'sedang direview',
        'diterima'        => 'diacc',      // tampilkan “diacc”
        'ditolak'         => 'ditolak',
        'revisi'          => 'revisi',
      ];
      $badgeClass = [
        'menunggu'        => 'warning',
        'sedang_direview' => 'info',
        'diterima'        => 'success',
        'ditolak'         => 'danger',
        'revisi'          => 'secondary',
      ];
    ?>
    <div class="vstack gap-3">
      <?php foreach ($abstracts as $a): ?>
        <div class="card">
          <div class="card-body">
            <div class="d-flex justify-content-between">
              <div class="pe-3">
                <div class="d-flex align-items-center gap-2 mb-1">
                  <a class="fw-semibold text-decoration-none"
                     href="<?= site_url('presenter/abstrak/detail/'.$a['id_abstrak']) ?>">
                    <?= esc($a['judul']) ?>
                  </a>
                  <span class="badge bg-<?= $badgeClass[$a['status']] ?? 'secondary' ?>">
                    <?= esc($statusLabel[$a['status']] ?? $a['status']) ?>
                  </span>
                </div>
                <div class="small text-muted">
                  <i class="bi bi-calendar-event me-1"></i> <?= esc($a['event_title'] ?? '—') ?> ·
                  Upload <?= date('d M Y H:i', strtotime($a['tanggal_upload'])) ?> ·
                  Kategori: <?= esc($a['nama_kategori'] ?? '-') ?>
                  <?php if ((int)$a['revisi_ke'] > 0): ?>
                    · Revisi ke-<?= (int)$a['revisi_ke'] ?>
                  <?php endif; ?>
                  <?php if ((int)$a['review_count'] > 0): ?>
                    · <?= (int)$a['review_count'] ?> review
                  <?php endif; ?>
                </div>
              </div>
              <div class="text-nowrap">
                <a class="btn btn-outline-secondary btn-sm"
                   href="<?= site_url('presenter/abstrak/download/'.$a['file_abstrak']) ?>">
                  <i class="bi bi-download me-1"></i> Download
                </a>
                <a class="btn btn-outline-primary btn-sm"
                   href="<?= site_url('presenter/abstrak/detail/'.$a['id_abstrak']) ?>">
                  <i class="bi bi-eye me-1"></i> Detail
                </a>
              </div>
            </div>
            <?php if (!empty($a['abstract_submission_active']) && !empty($a['abstract_deadline'])): ?>
              <?php
                $deadline = strtotime($a['abstract_deadline']);
                $daysLeft = (int)ceil(($deadline - time()) / 86400);
              ?>
              <?php if ($daysLeft <= 3 && $daysLeft > 0): ?>
                <div class="alert alert-warning mt-3 mb-0 py-2 small">
                  <i class="bi bi-exclamation-triangle me-1"></i>
                  Deadline revisi/submission dalam <?= $daysLeft ?> hari.
                </div>
              <?php elseif ($daysLeft <= 0): ?>
                <div class="alert alert-danger mt-3 mb-0 py-2 small">
                  <i class="bi bi-x-circle me-1"></i> Deadline submission telah berakhir.
                </div>
              <?php endif; ?>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <div class="text-center py-5 border rounded">
      <div class="mb-2"><i class="bi bi-file-earmark-plus" style="font-size:2rem"></i></div>
      <div class="fw-semibold">Belum ada abstrak</div>
      <div class="text-muted mb-3">Mulai dengan mengupload abstrak pada event yang tersedia.</div>
      <?php if (!empty($available_events)): ?>
        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#uploadModal">
          <i class="bi bi-upload me-1"></i> Upload Abstrak Pertama
        </button>
      <?php endif; ?>
    </div>
  <?php endif; ?>

</main>

<!-- Modal Upload -->
<div class="modal fade" id="uploadModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <form class="modal-content" action="<?= site_url('presenter/abstrak/upload') ?>" method="post" enctype="multipart/form-data" id="uploadForm">
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-upload me-2"></i>Upload Abstrak</h5>
        <button class="btn-close" data-bs-dismiss="modal" type="button"></button>
      </div>
      <div class="modal-body">
        <div class="row g-3">
          <div class="col-md-6">
            <label class="form-label">Pilih Event</label>
            <select name="event_id" id="eventSelect" class="form-select" required>
              <option value="">-- Pilih --</option>
              <?php foreach ($available_events as $ev): ?>
                <option value="<?= (int)$ev['id'] ?>"
                  <?= !empty($selected_event_id) && (int)$selected_event_id === (int)$ev['id'] ? 'selected' : '' ?>>
                  <?= esc($ev['title']) ?> (<?= date('d M Y', strtotime($ev['event_date'])) ?>)
                </option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-md-6">
            <label class="form-label">Kategori Abstrak</label>
            <select name="id_kategori" class="form-select" required>
              <option value="">-- Pilih --</option>
              <?php foreach ($categories as $c): ?>
                <option value="<?= (int)$c['id_kategori'] ?>"><?= esc($c['nama_kategori']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="col-12">
            <label class="form-label">Judul</label>
            <input type="text" name="judul" class="form-control" maxlength="255" required placeholder="Judul abstrak">
          </div>
          <div class="col-12">
            <label class="form-label">File (PDF/DOC/DOCX · maks 10MB)</label>
            <input type="file" name="file_abstrak" class="form-control" accept=".pdf,.doc,.docx" required>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" data-bs-dismiss="modal" type="button">Batal</button>
        <button class="btn btn-primary" type="submit">
          <i class="bi bi-upload me-1"></i> Upload
        </button>
      </div>
    </form>
  </div>
</div>

<script>
function prefillEvent(id){
  const sel = document.getElementById('eventSelect');
  if (!sel) return;
  [...sel.options].forEach(o => o.selected = (parseInt(o.value,10) === parseInt(id,10)));
}
document.getElementById('uploadForm')?.addEventListener('submit', function(e){
  // optional: gunakan AJAX biar UX mulus
});
</script>

<?= view('partials/footer') ?>
