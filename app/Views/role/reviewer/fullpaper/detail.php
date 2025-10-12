<?php
// INPUT dari controller:
// 'submission' => [id,title,full_paper_status,full_paper_uploaded_at,file_available,file_path,nama_lengkap,nama_kategori,event_title]
// 'taskStatus' => 'accepted'|'pending'|'declined'
// 'taskReason' => string|null
// 'myReview'   => ['keputusan','komentar','tanggal_review'] | null
// 'reviewers'  => array

$title       = $title ?? 'Detail Full Paper';
$S           = $submission ?? [];
$taskStatus  = strtolower($taskStatus ?? 'pending');
$taskReason  = $taskReason ?? null;
$my          = $myReview ?? null;
$reviewers   = $reviewers ?? [];

$fmt = fn($d,$h=false)=>$d ? date($h?'d M Y H:i':'d M Y', strtotime($d)) : '-';
$badgeMap = function($s){
  $s = strtolower((string)$s);
  return match(true){
    in_array($s,['accepted','diterima']) => 'bg-success',
    in_array($s,['revision','revisi'])   => 'bg-warning text-dark',
    in_array($s,['rejected','ditolak'])  => 'bg-danger',
    in_array($s,['pending','menunggu','none','']) => 'bg-secondary',
    default => 'bg-secondary'
  };
};
$taskBadge = fn($st)=> $st==='accepted' ? 'bg-success' : ($st==='declined'?'bg-danger':'bg-secondary');
$canReview = ($taskStatus === 'accepted');
$hasFile   = !empty($S['file_available']);

$blobUrl   = site_url('reviewer/fullpaper/blob/'.(int)$S['id']);
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_reviewer') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid px-2 px-md-3 py-3">

      <!-- HEADER -->
      <div class="header-section d-flex justify-content-between align-items-center mb-3">
        <div class="me-3">
          <h3 class="welcome-text mb-1">
            <i class="bi bi-file-earmark-text me-2"></i>Detail Full Paper
          </h3>
          <small class="text-muted">
            <?= esc($S['event_title'] ?? '-') ?>
            <?php if(!empty($S['title'])): ?> • <span class="fw-semibold"><?= esc($S['title']) ?></span><?php endif; ?>
          </small>
        </div>
        <div class="d-flex gap-2">
          <a href="<?= site_url('reviewer/fullpaper') ?>" class="btn btn-light btn-sm">
            <i class="bi bi-arrow-left"></i> Kembali
          </a>
        </div>
      </div>

      <!-- BILAH AKSI (ACC/TOLAK) SAAT PENDING -->
      <?php if ($taskStatus === 'pending'): ?>
        <div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
          <div class="me-2">
            <i class="bi bi-hourglass-split me-1"></i>
            Anda belum menerima penugasan ini. Silakan terima untuk mulai meninjau dan mengirim review.
          </div>
          <div class="d-flex gap-2">
            <form method="post" action="<?= site_url('reviewer/fullpaper/action') ?>">
              <?= csrf_field() ?>
              <input type="hidden" name="id" value="<?= (int)$S['id'] ?>">
              <input type="hidden" name="action" value="accept">
              <button class="btn btn-success btn-sm"><i class="bi bi-check-lg me-1"></i>Terima</button>
            </form>
            <button class="btn btn-outline-danger btn-sm" id="btnOpenDecline"
              data-id="<?= (int)$S['id'] ?>" data-title="<?= esc($S['title'] ?? '-', 'attr') ?>">
              <i class="bi bi-x-lg me-1"></i>Tolak
            </button>
          </div>
        </div>
      <?php endif; ?>

      <!-- STATUS BAR -->
      <div class="card shadow-sm border-0 mb-3">
        <div class="card-body d-flex flex-wrap justify-content-between align-items-center gap-2">
          <div class="d-flex flex-wrap align-items-center gap-2">
            <span class="badge <?= $taskBadge($taskStatus) ?>">
              <i class="bi bi-person-check me-1"></i><?= ucfirst($taskStatus) ?> tugas
            </span>
            <?php if ($taskStatus==='declined' && $taskReason): ?>
              <small class="text-muted">Alasan: <?= esc($taskReason) ?></small>
            <?php endif; ?>

            <?php if (!empty($S['full_paper_status'])): ?>
              <span class="badge <?= $badgeMap($S['full_paper_status']) ?>">
                Status FP: <?= strtoupper($S['full_paper_status']) ?>
              </span>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <div class="row g-3">
        <!-- KIRI -->
        <div class="col-12 col-xl-8">

          <!-- Info Naskah -->
          <div class="card shadow-sm border-0 mb-3">
            <div class="card-header d-flex align-items-center justify-content-between">
              <h5 class="card-title mb-0">
                <i class="bi bi-info-circle text-primary me-2"></i>Informasi Naskah
              </h5>
            </div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-12">
                  <div class="d-flex justify-content-between">
                    <div class="text-muted">Judul</div>
                    <div class="fw-semibold text-dark text-end ms-3"><?= esc($S['title'] ?? '-') ?></div>
                  </div>
                </div>
                <div class="col-12 col-md-6">
                  <div class="d-flex justify-content-between">
                    <div class="text-muted">Penulis</div>
                    <div class="text-dark ms-3"><?= esc($S['nama_lengkap'] ?? '-') ?></div>
                  </div>
                </div>
                <div class="col-12 col-md-6">
                  <div class="d-flex justify-content-between">
                    <div class="text-muted">Kategori</div>
                    <div class="text-dark ms-3"><?= esc($S['nama_kategori'] ?? '-') ?></div>
                  </div>
                </div>
                <?php if (!empty($S['full_paper_uploaded_at'])): ?>
                  <div class="col-12">
                    <small class="text-muted"><i class="bi bi-upload me-1"></i>Unggah: <?= $fmt($S['full_paper_uploaded_at'], true) ?></small>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- PDF (collapsed by default) -->
          <div class="card shadow-sm border-0 mb-3">
            <div class="card-header d-flex align-items-center justify-content-between">
              <h5 class="card-title mb-0">
                <i class="bi bi-file-earmark-pdf text-primary me-2"></i>Pratinjau Full Paper
              </h5>
              <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#pdfWrap" aria-expanded="false" <?= ($canReview && $hasFile)?'':'disabled' ?>>
                Tampilkan / Sembunyikan
              </button>
            </div>
            <div id="pdfWrap" class="collapse">
              <div class="card-body">
                <?php if (!$canReview): ?>
                  <div class="alert alert-info mb-0"><i class="bi bi-info-circle me-2"></i>Terima penugasan terlebih dahulu untuk mengakses berkas.</div>
                <?php elseif (!$hasFile): ?>
                  <div class="alert alert-warning mb-0"><i class="bi bi-exclamation-triangle me-2"></i>File belum tersedia. Hubungi panitia.</div>
                <?php else: ?>
                  <div class="pdf-wrap">
                    <iframe src="<?= $blobUrl ?>#toolbar=1&navpanes=0" title="Preview PDF" class="pdf-frame" loading="lazy" referrerpolicy="no-referrer"></iframe>
                  </div>
                  <div class="small text-muted mt-2">
                    Jika pratinjau tidak tampil, gunakan tombol <b>Unduh PDF</b> / <b>Buka di Tab Baru</b> di atas.
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- Review saya -->
          <div class="card shadow-sm border-0">
            <div class="card-header d-flex align-items-center justify-content-between">
              <h5 class="card-title mb-0">
                <i class="bi bi-person-check text-primary me-2"></i>Review Anda
              </h5>
              <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#reviewModal"
                      <?= $canReview && $hasFile ? '' : 'disabled' ?>>
                <i class="bi bi-pencil-square me-1"></i><?= $my ? 'Ubah' : 'Kirim' ?>
              </button>
            </div>
            <div class="card-body">
              <?php if (!$my): ?>
                <div class="text-muted">Belum ada review yang Anda kirim pada versi ini.</div>
              <?php else: ?>
                <?php $kpt = strtolower($my['keputusan'] ?? ''); ?>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                  <span class="badge <?= $badgeMap($kpt) ?>"><?= strtoupper($kpt ?: 'PENDING') ?></span>
                  <?php if (!empty($my['tanggal_review'])): ?>
                    <small class="text-muted">Terakhir dikirim: <?= $fmt($my['tanggal_review'], true) ?></small>
                  <?php endif; ?>
                </div>
                <?php if (!empty($my['komentar'])): ?>
                  <div class="border rounded p-2 bg-light">
                    <div class="small text-muted mb-1">Komentar:</div>
                    <div><?= nl2br(esc($my['komentar'])) ?></div>
                  </div>
                <?php else: ?>
                  <div class="text-muted">Tidak ada komentar.</div>
                <?php endif; ?>
              <?php endif; ?>

              <?php if (!$canReview): ?>
                <div class="alert alert-info mt-3 mb-0">
                  <i class="bi bi-info-circle me-1"></i>
                  Terima penugasan terlebih dahulu untuk membuka form review.
                </div>
              <?php elseif (!$hasFile): ?>
                <div class="alert alert-warning mt-3 mb-0">
                  <i class="bi bi-exclamation-triangle me-1"></i>
                  File belum tersedia, form review dinonaktifkan.
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- KANAN -->
        <div class="col-12 col-xl-4">
          <div class="card shadow-sm border-0 mb-3">
            <div class="card-header d-flex align-items-center justify-content-between">
              <h5 class="card-title mb-0">
                <i class="bi bi-flag text-primary me-2"></i>Status & Reviewer
              </h5>
            </div>
            <div class="card-body">
              <div class="mb-3">
                <div class="d-flex align-items-center justify-content-between">
                  <div class="text-muted">Status Tugas</div>
                  <span class="badge <?= $taskBadge($taskStatus) ?>"><?= ucfirst($taskStatus) ?></span>
                </div>
                <?php if ($taskStatus==='declined' && $taskReason): ?>
                  <div class="small text-muted mt-2">Alasan: <?= esc($taskReason) ?></div>
                <?php endif; ?>
              </div>

              <div>
                <div class="fw-semibold mb-2">Daftar Reviewer</div>
                <?php if (empty($reviewers)): ?>
                  <div class="text-muted">Belum ada data reviewer.</div>
                <?php else: foreach ($reviewers as $rv):
                  $tsBadge = $rv['tugas_status']==='accepted' ? 'bg-success'
                            : ($rv['tugas_status']==='declined' ? 'bg-danger' : 'bg-secondary');
                ?>
                  <div class="border rounded p-2 mb-2">
                    <div class="d-flex justify-content-between">
                      <div class="fw-semibold">
                        Reviewer <?= (int)$rv['order'] ?> <?= $rv['is_me'] ? '<span class="text-primary">(Anda)</span>' : '' ?>
                      </div>
                      <span class="badge <?= $tsBadge ?>"><?= ucfirst($rv['tugas_status']) ?></span>
                    </div>
                    <div class="small text-muted mb-1"><?= esc($rv['nama']) ?></div>
                    <div class="d-flex align-items-center justify-content-between">
                      <div>
                        <span class="badge <?= $badgeMap($rv['keputusan']) ?>"><?= ucfirst($rv['keputusan']) ?></span>
                      </div>
                      <div class="small text-muted"><?= $rv['tanggal'] ? $fmt($rv['tanggal'], true) : '' ?></div>
                    </div>
                  </div>
                <?php endforeach; endif; ?>
              </div>
            </div>
          </div>

          <div class="card shadow-sm border-0">
            <div class="card-header d-flex align-items-center justify-content-between">
              <h5 class="card-title mb-0">
                <i class="bi bi-calendar-week text-primary me-2"></i>Info Event
              </h5>
            </div>
            <div class="card-body">
              <div class="d-flex justify-content-between">
                <div class="text-muted">Nama</div>
                <div class="text-dark ms-3"><?= esc($S['event_title'] ?? '-') ?></div>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </main>
</div>

<!-- Modal: Kirim/Ubah Review -->
<div class="modal fade" id="reviewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="<?= site_url('reviewer/fullpaper/submit/'.(int)$S['id']) ?>" class="modal-content" id="reviewForm">
      <?= csrf_field() ?>
      <div class="modal-header">
        <h6 class="modal-title"><i class="bi bi-chat-left-text me-2"></i><?= $my ? 'Ubah Review' : 'Kirim Review' ?></h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <div class="mb-3">
          <label class="form-label">Keputusan</label>
          <?php $val = strtolower((string)($my['keputusan'] ?? 'accepted')); ?>
          <select name="keputusan" class="form-select" <?= !$canReview || !$hasFile ? 'disabled' : '' ?> required>
            <option value="accepted" <?= $val==='accepted'?'selected':'' ?>>Diterima (Accept)</option>
            <option value="revision" <?= $val==='revision'?'selected':'' ?>>Revisi (Revision)</option>
            <option value="rejected" <?= $val==='rejected'?'selected':'' ?>>Ditolak (Reject)</option>
          </select>
        </div>
        <div>
          <label class="form-label">Komentar untuk Penulis</label>
          <textarea name="komentar" rows="6" class="form-control" <?= !$canReview || !$hasFile ? 'disabled' : '' ?> placeholder="Tulis komentar yang jelas dan konstruktif..."><?= esc($my['komentar'] ?? '') ?></textarea>
          <div class="form-text">Jika memilih <b>Ditolak</b>, komentar minimal 10 karakter.</div>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-light" type="button" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-primary" type="submit" <?= !$canReview || !$hasFile ? 'disabled' : '' ?> id="btnSubmitReview">
          <i class="bi bi-save me-1"></i><?= $my ? 'Perbarui' : 'Kirim' ?>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Tolak Penugasan -->
<div class="modal fade" id="declineModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" action="<?= site_url('reviewer/fullpaper/action') ?>" class="modal-content" id="declineForm">
      <?= csrf_field() ?>
      <input type="hidden" name="id" id="declId">
      <input type="hidden" name="action" value="decline">
      <div class="modal-header">
        <h6 class="modal-title"><i class="bi bi-x-octagon text-danger me-2"></i>Tolak Penugasan</h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <div class="small text-muted mb-2" id="declTitle"></div>
        <label class="form-label">Alasan Penolakan <span class="text-danger">*</span></label>
        <textarea name="reason" id="declReason" rows="4" class="form-control" required minlength="5"
                  placeholder="Tuliskan alasan penolakan secara singkat dan jelas..."></textarea>
        <div class="form-text">Penolakan tanpa alasan tidak diperbolehkan.</div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-light" type="button" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-danger" type="submit"><i class="bi bi-x-lg me-1"></i>Tolak</button>
      </div>
    </form>
  </div>
</div>

<?= $this->include('partials/footer') ?>

<style>
:root{
  --primary-color:#2563eb; --success-color:#10b981; --warning-color:#f59e0b; --danger-color:#ef4444; --secondary-color:#6b7280;
}
body{ background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }
.header-section{ background: linear-gradient(135deg, var(--primary-color) 0%, #1e40af 100%); color:#fff; padding:20px; border-radius:12px; box-shadow:0 4px 20px rgba(37,99,235,.15); }
.welcome-text{ color:#fff; font-weight:700; font-size:1.35rem; margin:0; }
.card{ background:#fff; border:none; border-radius:12px; box-shadow:0 4px 20px rgba(0,0,0,.08); }
.card-header{ background:#f8fafc; border-bottom:1px solid #e2e8f0; border-radius:12px 12px 0 0 !important; padding:14px 18px; }
.form-control, .form-select{ border:2px solid #e2e8f0; border-radius:8px; }
.form-control:focus, .form-select:focus{ border-color:var(--primary-color); box-shadow:0 0 0 .2rem rgba(37,99,235,.1); }
.badge{ border-radius:6px; font-weight:500; font-size:.8rem; }
.bg-success{ background-color:var(--success-color) !important; }
.bg-warning{ background-color:var(--warning-color) !important; }
.bg-danger{ background-color:var(--danger-color) !important; }
.bg-secondary{ background-color:var(--secondary-color) !important; }
.pdf-wrap{height:70vh;border:1px solid #e5e7eb;border-radius:10px;overflow:hidden;background:#f8fafc}
.pdf-frame{width:100%;height:100%;border:0}
@media (max-width: 575.98px){
  .container-fluid{ padding-left:.5rem!important; padding-right:.5rem!important; }
  .pdf-wrap{ height:60vh; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
  // Validasi review (khusus penolakan wajib alasan ≥10)
  const form   = document.getElementById('reviewForm');
  const select = form?.querySelector('select[name="keputusan"]');
  const text   = form?.querySelector('textarea[name="komentar"]');

  form?.addEventListener('submit', (e) => {
    const decision = (select?.value || '').toLowerCase();
    const comment  = (text?.value || '').trim();
    if (decision === 'rejected' && comment.length < 10) {
      e.preventDefault();
      alert('Penolakan wajib disertai alasan minimal 10 karakter.');
      text?.focus();
    }
  });

  // Modal Tolak Penugasan
  const declineBtn = document.getElementById('btnOpenDecline');
  if (declineBtn) {
    const modal = new bootstrap.Modal(document.getElementById('declineModal'));
    declineBtn.addEventListener('click', () => {
      document.getElementById('declId').value = declineBtn.getAttribute('data-id');
      document.getElementById('declTitle').textContent = 'Menolak penugasan: "' + (declineBtn.getAttribute('data-title')||'') + '"';
      document.getElementById('declReason').value = '';
      modal.show();
    });
  }
  document.getElementById('declineForm')?.addEventListener('submit', (e)=>{
    const reason = (document.getElementById('declReason').value || '').trim();
    if (reason.length < 5){
      e.preventDefault();
      alert('Alasan penolakan minimal 5 karakter.');
    }
  });
});
</script>
