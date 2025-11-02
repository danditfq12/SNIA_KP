<?php
// Input dari controller (versi ABSTRAK):
// 'abstrak'         => [id_abstrak, judul, tanggal_upload, file_abstrak, nama_lengkap, nama_kategori, event_title, event_date?, event_time?]
// 'taskStatus'      => 'accepted'|'pending'|'declined'
// 'taskReason'      => string|null (opsional)
// 'myReview'        => ['keputusan','komentar','tanggal_review'] | null
// (opsional) 'existingReview' => sama struktur dengan myReview

$title       = $title ?? 'Detail Abstrak';
$A           = $abstrak ?? [];
$taskStatus  = strtolower($taskStatus ?? 'pending');
$taskReason  = $taskReason ?? null;
$my          = $myReview ?? ($existingReview ?? null);
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

$canReview   = ($taskStatus === 'accepted');
$idAbs       = (int)($A['id_abstrak'] ?? 0);
$hasFile     = !empty($A['file_abstrak']);

$kpt         = strtolower(trim((string)($my['keputusan'] ?? '')));
$comment     = trim((string)($my['komentar'] ?? ''));
$commentLen  = mb_strlen($comment);
// dianggap “sudah review” jika ada keputusan final DAN komentar valid (≥5)
$isFinal     = in_array($kpt, ['accepted','diterima','revision','revisi','rejected','ditolak'], true);
$isReviewed  = $isFinal && $commentLen >= 5;

$showLastSent = !empty($my['tanggal_review']) && $isReviewed;
$blobUrl      = site_url('reviewer/abstrak/blob/'.$idAbs);
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_reviewer') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill page-wrap-blue">
    <div class="container-xxl px-3 px-md-4 py-4">

      <!-- HEADER -->
      <div class="card-hero mb-3">
        <div class="hero-body">
          <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
            <div class="me-2">
              <h3 class="hero-title mb-1">
                <i class="bi bi-file-earmark-text me-2"></i>Detail Abstrak
              </h3>
              <div class="text-white-70 small">
                <?= esc($A['event_title'] ?? '-') ?>
                <?php if(!empty($A['judul'])): ?>
                  <span class="d-inline-block mx-2">•</span>
                  <span class="fw-semibold"><?= esc($A['judul']) ?></span>
                <?php endif; ?>
              </div>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-2 status-fixed-wrap">
              <span class="chip-fixed <?= $taskBadge($taskStatus) ?>">
                <i class="bi bi-person-check me-1"></i><?= ucfirst($taskStatus) ?> tugas
              </span>
              <a href="<?= site_url('reviewer/abstrak') ?>" class="btn btn-light btn-sm">
                <i class="bi bi-arrow-left"></i> Kembali
              </a>
            </div>
          </div>
        </div>

        <?php if ($taskStatus === 'pending'): ?>
          <div class="hero-tabs">
            <div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2 mb-0">
              <div><i class="bi bi-hourglass-split me-1"></i>Anda belum menerima penugasan ini. Terima untuk mulai meninjau.</div>
              <div class="d-flex gap-2">
                <form method="post" action="<?= site_url('reviewer/abstrak/confirm/'.$idAbs) ?>">
                  <?= csrf_field() ?>
                  <input type="hidden" name="action" value="accept">
                  <button class="btn btn-success btn-sm"><i class="bi bi-check-lg me-1"></i>Terima</button>
                </form>
                <button class="btn btn-outline-danger btn-sm" id="btnOpenDecline"
                        data-id="<?= $idAbs ?>" data-title="<?= esc($A['judul'] ?? '-', 'attr') ?>">
                  <i class="bi bi-x-lg me-1"></i>Tolak
                </button>
              </div>
            </div>
          </div>
        <?php endif; ?>
      </div>

      <div class="row g-3">
        <!-- KIRI -->
        <div class="col-12 col-xl-8">

          <!-- Info Naskah -->
          <div class="card card-glass border-0 mb-3">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
              <span class="badge bg-blue-soft"><i class="bi bi-info-circle"></i></span>
              <h6 class="mb-0 fw-semibold text-blue-900">Informasi Naskah</h6>
            </div>
            <div class="card-body">
              <div class="kv-row mb-3">
                <div class="kv-label">Judul</div>
                <div class="kv-value"><?= esc($A['judul'] ?? '-') ?></div>
              </div>
              <div class="row g-3">
                <div class="col-12 col-md-6">
                  <div class="kv-row">
                    <div class="kv-label">Penulis</div>
                    <div class="kv-value"><?= esc($A['nama_lengkap'] ?? '-') ?></div>
                  </div>
                </div>
                <div class="col-12 col-md-6">
                  <div class="kv-row">
                    <div class="kv-label">Kategori</div>
                    <div class="kv-value"><?= esc($A['nama_kategori'] ?? '-') ?></div>
                  </div>
                </div>
                <?php if (!empty($A['tanggal_upload'])): ?>
                <div class="col-12 col-md-6">
                  <div class="kv-row">
                    <div class="kv-label">Diunggah</div>
                    <div class="kv-value"><?= $fmt($A['tanggal_upload'], true) ?></div>
                  </div>
                </div>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- PDF half/full -->
          <div class="card card-glass border-0 mb-3">
            <div class="card-header d-flex align-items-center justify-content-between bg-transparent border-0 pb-0">
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-blue-soft"><i class="bi bi-file-earmark-pdf"></i></span>
                <h6 class="mb-0 fw-semibold text-blue-900">Pratinjau Abstrak</h6>
              </div>
              <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary btn-sm" type="button" id="btnTogglePdf" <?= ($canReview && $hasFile)?'':'disabled' ?>>
                  <i class="bi bi-arrows-fullscreen"></i> Toggle Ukuran
                </button>
                <?php if ($canReview && $hasFile): ?>
                  <a class="btn btn-outline-primary btn-sm" href="<?= $blobUrl ?>" target="_blank" rel="noopener">
                    <i class="bi bi-box-arrow-up-right"></i> Buka Tab Baru
                  </a>
                <?php endif; ?>
              </div>
            </div>
            <div class="card-body">
              <?php if (!$canReview): ?>
                <div class="empty-hint"><i class="bi bi-info-circle me-1"></i>Terima penugasan terlebih dahulu untuk mengakses berkas.</div>
              <?php elseif (!$hasFile): ?>
                <div class="empty-hint"><i class="bi bi-exclamation-triangle me-1"></i>File belum tersedia. Hubungi panitia.</div>
              <?php else: ?>
                <div class="pdf-wrap is-min" id="pdfWrap">
                  <iframe src="<?= $blobUrl ?>#toolbar=1&navpanes=0" title="Preview PDF" class="pdf-frame" loading="lazy" referrerpolicy="no-referrer"></iframe>
                </div>
                <div class="small text-muted mt-2">Default tampilan setengah layar. Gunakan <b>Toggle Ukuran</b> untuk fullscreen.</div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Review saya -->
          <div class="card card-glass border-0">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center justify-content-between">
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-blue-soft"><i class="bi bi-person-check"></i></span>
                <h6 class="mb-0 fw-semibold text-blue-900">Review Anda</h6>
              </div>
              <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#reviewModal" <?= $canReview ? '' : 'disabled' ?>>
                <i class="bi bi-pencil-square me-1"></i><?= $isReviewed ? 'Ubah' : 'Kirim Review' ?>
              </button>
            </div>
            <div class="card-body">
              <?php if (!$my): ?>
                <div class="text-muted">Belum ada review yang Anda kirim pada versi ini.</div>
              <?php else: ?>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                  <span class="badge <?= $badgeMap($kpt) ?>"><?= strtoupper($kpt ?: 'PENDING') ?></span>
                  <?php if ($showLastSent): ?>
                    <small class="text-muted">Terakhir dikirim: <?= $fmt($my['tanggal_review'], true) ?></small>
                  <?php endif; ?>
                </div>

                <?php if ($commentLen >= 5): ?>
                  <div class="border rounded p-2 bg-light">
                    <div class="small text-muted mb-1">Komentar:</div>
                    <div><?= nl2br(esc($comment)) ?></div>
                  </div>
                <?php else: ?>
                  <div class="alert alert-warning d-flex align-items-center gap-2 mb-0">
                    <i class="bi bi-exclamation-triangle"></i>
                    <div><b>Peringatan:</b> Anda belum memberikan komentar.</div>
                  </div>
                <?php endif; ?>
              <?php endif; ?>

              <?php if (!$canReview): ?>
                <div class="alert alert-info mt-3 mb-0"><i class="bi bi-info-circle me-1"></i>Terima penugasan terlebih dahulu untuk membuka form review.</div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- KANAN -->
        <div class="col-12 col-xl-4">
          <div class="card card-glass border-0 mb-3">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
              <span class="badge bg-blue-soft"><i class="bi bi-flag"></i></span>
              <h6 class="mb-0 fw-semibold text-blue-900">Status & Reviewer</h6>
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

              <?php if (!empty($reviewers)): ?>
                <div class="fw-semibold mb-2">Daftar Reviewer</div>
                <?php foreach ($reviewers as $rv):
                  $tsBadge = $rv['tugas_status']==='accepted' ? 'bg-success'
                            : ($rv['tugas_status']==='declined' ? 'bg-danger' : 'bg-secondary'); ?>
                  <div class="border rounded p-2 mb-2">
                    <div class="d-flex justify-content-between">
                      <div class="fw-semibold">
                        Reviewer <?= (int)($rv['order'] ?? 0) ?> <?= !empty($rv['is_me']) ? '<span class="text-primary">(Anda)</span>' : '' ?>
                      </div>
                      <span class="badge <?= $tsBadge ?>"><?= ucfirst($rv['tugas_status'] ?? 'pending') ?></span>
                    </div>
                    <div class="small text-muted mb-1"><?= esc($rv['nama'] ?? '-') ?></div>
                    <div class="d-flex align-items-center justify-content-between">
                      <div><span class="badge <?= $badgeMap($rv['keputusan'] ?? '') ?>"><?= ucfirst($rv['keputusan'] ?? 'menunggu') ?></span></div>
                      <div class="small text-muted"><?= !empty($rv['tanggal']) ? $fmt($rv['tanggal'], true) : '' ?></div>
                    </div>
                  </div>
                <?php endforeach; ?>
              <?php else: ?>
                <div class="text-muted">Belum ada data reviewer.</div>
              <?php endif; ?>
            </div>
          </div>

          <div class="card card-glass border-0">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
              <span class="badge bg-blue-soft"><i class="bi bi-calendar-week"></i></span>
              <h6 class="mb-0 fw-semibold text-blue-900">Info Event</h6>
            </div>
            <div class="card-body">
              <div class="kv-row">
                <div class="kv-label">Nama Event</div>
                <div class="kv-value"><?= esc($A['event_title'] ?? '-') ?></div>
              </div>
              <?php if (!empty($A['event_date'])): ?>
                <div class="kv-row mt-2">
                  <div class="kv-label">Tanggal</div>
                  <div class="kv-value"><?= $fmt($A['event_date']) ?> <?= esc($A['event_time'] ?? '') ?></div>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

    </div>
  </main>
</div>

<!-- Modal: Kirim/Ubah Review (centered) -->
<div class="modal fade" id="reviewModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form method="post" action="<?= site_url('reviewer/abstrak/review/'.$idAbs) ?>" class="modal-content" id="reviewForm">
      <?= csrf_field() ?>
      <div class="modal-header">
        <h6 class="modal-title"><i class="bi bi-chat-left-text me-2"></i><?= $isReviewed ? 'Ubah Review' : 'Kirim Review' ?></h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <?php if (!$canReview): ?>
          <div class="alert alert-info">Silakan terima penugasan terlebih dahulu dari Dashboard.</div>
        <?php endif; ?>

        <div class="mb-3">
          <label class="form-label">Keputusan</label>
          <?php $val = $isReviewed ? $kpt : 'accepted'; ?>
          <select name="keputusan" class="form-select" <?= !$canReview?'disabled':'' ?> required>
            <option value="accepted" <?= $val==='accepted' || $val==='diterima' ? 'selected':'' ?>>Diterima (Accept)</option>
            <option value="revision" <?= $val==='revision' || $val==='revisi' ? 'selected':'' ?>>Revisi (Revision)</option>
            <option value="rejected" <?= $val==='rejected' || $val==='ditolak' ? 'selected':'' ?>>Ditolak (Reject)</option>
          </select>
        </div>
        <div>
          <label class="form-label">Komentar untuk Penulis</label>
          <textarea name="komentar" rows="6" class="form-control" <?= !$canReview?'disabled':'' ?> placeholder="Tulis komentar yang jelas dan konstruktif..."><?= esc($comment) ?></textarea>
          <div class="form-text">Komentar <b>wajib</b> ≥ 5 karakter. Jika memilih <b>Ditolak</b>, minimal 10 karakter.</div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-light" type="button" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-primary" type="submit" <?= !$canReview?'disabled':'' ?> id="btnSubmitReview">
          <i class="bi bi-save me-1"></i><?= $isReviewed ? 'Perbarui' : 'Kirim' ?>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Tolak Penugasan (centered) -->
<div class="modal fade" id="declineModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form method="post" action="<?= site_url('reviewer/abstrak/confirm/'.$idAbs) ?>" class="modal-content" id="declineForm">
      <?= csrf_field() ?>
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
  --blue-50:#eff6ff; --blue-100:#dbeafe; --blue-200:#bfdbfe; --blue-300:#93c5fd;
  --blue-400:#60a5fa; --blue-500:#3b82f6; --blue-600:#2563eb; --blue-700:#1d4ed8; --blue-800:#1e40af; --blue-900:#1e3a8a;
  --muted:#6b7280; --ink:#0f172a; --radius:16px;
  --glass-bg: rgba(255,255,255,.96);
  --glass-bd: rgba(30,64,175,.14);
  --glass-shadow: 0 10px 24px rgba(2,6,23,.08);
}
.container-xxl{ max-width:min(100%, 1560px); padding-left:clamp(1rem, 2.3vw, 2.2rem)!important; padding-right:clamp(1rem, 2.3vw, 2.2rem)!important; margin-inline:auto; }
body{ font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size:15.5px; line-height:1.6; color:var(--ink); }
.page-wrap-blue{
  min-height:100vh; padding-top:72px;
  background:
    radial-gradient(1000px 380px at 10% -10%, rgba(59,130,246,.16), rgba(59,130,246,0) 60%),
    radial-gradient(1000px 380px at 90% 110%, rgba(59,130,246,.12), rgba(59,130,246,0) 70%),
    linear-gradient(180deg, var(--blue-50), #fff 40%);
}

/* HERO */
.card-hero{ border:0; border-radius:var(--radius); overflow:hidden; box-shadow:0 12px 28px rgba(30,64,175,.18); }
.card-hero .hero-body{ background:linear-gradient(135deg,var(--blue-700),var(--blue-800)); color:#fff; padding:1.8rem 1.2rem; min-height:164px; }
.hero-title{ font-weight:800; }
.text-white-70{ color:rgba(255,255,255,.85)!important; }
.hero-tabs{ background:#fff; padding:.6rem .8rem; border:1px solid rgba(30,64,175,.18); border-top:0; }

/* Status chips fixed width */
.status-fixed-wrap{ flex:0 0 auto; }
.chip-fixed{ display:inline-flex; align-items:center; gap:.4rem; padding:.5rem .7rem; border-radius:10px; color:#fff; font-weight:700; white-space:nowrap; min-width:170px; justify-content:center; }

/* Cards / Glass */
.card-glass{ backdrop-filter: blur(6px); background: var(--glass-bg); border: 1px solid var(--glass-bd); border-radius: 12px; box-shadow: var(--glass-shadow); }
.bg-blue-soft{ background:var(--blue-200); color:var(--blue-800); border-radius:12px; padding:.5rem .7rem; font-weight:600; font-size:.9rem; }
.text-blue-900{ color:var(--blue-900)!important; }

/* key-value rows */
.kv-row{ display:flex; gap:1rem; align-items:flex-start; }
.kv-label{ flex:0 0 120px; color:#64748b; font-weight:600; }
.kv-value{ flex:1 1 auto; font-weight:700; color:#0f172a; text-align:left; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }

/* PDF viewer */
.pdf-wrap{ border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; background:#f8fafc; transition:height .2s ease; }
.pdf-wrap.is-min{ height:52vh; }
.pdf-wrap.is-max{ height:82vh; }
.pdf-frame{ width:100%; height:100%; border:0; }

/* badges */
.bg-success{ background-color:#10b981 !important; }
.bg-warning{ background-color:#f59e0b !important; }
.bg-danger{ background-color:#ef4444 !important; }
.bg-secondary{ background-color:#6b7280 !important; }

/* hints */
.empty-hint{ color:#567; background:#f6f9ff; border:1px dashed rgba(30,64,175,.18); border-radius:12px; padding:.8rem 1rem; font-weight:600; }

@media (max-width: 575.98px){
  .pdf-wrap.is-min{ height:46vh; }
  .kv-label{ flex-basis:95px; }
  .kv-value{ white-space:normal; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
  // Toggle PDF size
  const wrap = document.getElementById('pdfWrap');
  const btn  = document.getElementById('btnTogglePdf');
  if (wrap && btn){
    wrap.classList.add('is-min');
    btn.addEventListener('click', ()=>{
      wrap.classList.toggle('is-max');
      wrap.classList.toggle('is-min');
    });
  }

  // ==== VALIDASI DENGAN SWEETALERT2 ====
  const form   = document.getElementById('reviewForm');
  const select = form?.querySelector('select[name="keputusan"]');
  const text   = form?.querySelector('textarea[name="komentar"]');

  form?.addEventListener('submit', async (e) => {
    const decision = (select?.value || '').toLowerCase();
    const comment  = (text?.value || '').trim();

    if (comment.length < 5) {
      e.preventDefault();
      await Swal.fire({
        icon: 'warning',
        title: 'Komentar kurang panjang',
        text: 'Komentar wajib diisi minimal 5 karakter.',
        confirmButtonText: 'OK'
      });
      text?.focus();
      return;
    }
    if (decision === 'rejected' && comment.length < 10) {
      e.preventDefault();
      await Swal.fire({
        icon: 'warning',
        title: 'Alasan penolakan kurang jelas',
        text: 'Jika memilih Ditolak, komentar minimal 10 karakter.',
        confirmButtonText: 'OK'
      });
      text?.focus();
    }
  });

  // Modal Tolak Penugasan
  const declineBtn = document.getElementById('btnOpenDecline');
  if (declineBtn) {
    const modal = new bootstrap.Modal(document.getElementById('declineModal'));
    declineBtn.addEventListener('click', () => {
      document.getElementById('declTitle').textContent =
        'Menolak penugasan: "' + (declineBtn.getAttribute('data-title')||'') + '"';
      document.getElementById('declReason').value = '';
      modal.show();
    });
  }

  // Validasi alasan penolakan (SweetAlert2)
  document.getElementById('declineForm')?.addEventListener('submit', async (e)=>{
    const reason = (document.getElementById('declReason').value || '').trim();
    if (reason.length < 5){
      e.preventDefault();
      await Swal.fire({
        icon: 'warning',
        title: 'Alasan penolakan wajib',
        text: 'Tuliskan alasan penolakan minimal 5 karakter.',
        confirmButtonText: 'OK'
      });
      document.getElementById('declReason').focus();
    }
  });
});
</script>
