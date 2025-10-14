<?php
// Input dari controller (versi ABSTRAK):
// 'abstrak'   => [id_abstrak, judul, tanggal_upload, file_abstrak, nama_lengkap, nama_kategori, event_title, event_date?, event_time?]
// 'taskStatus'=> 'accepted'|'pending'|'declined'
// 'taskReason'=> string|null (opsional)
// 'myReview'  => ['keputusan','komentar','tanggal_review'] | null
// 'reviewers' => [ ... ]

$title       = $title ?? 'Detail Abstrak';
$A           = $abstrak ?? [];
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
$idAbs     = (int)($A['id_abstrak'] ?? 0);
$hasFile   = !empty($A['file_abstrak']);
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
            <i class="bi bi-file-earmark-text me-2"></i>Detail Abstrak
          </h3>
          <small class="text-muted">
            <?= esc($A['event_title'] ?? '-') ?>
            <?php if(!empty($A['judul'])): ?> • <span class="fw-semibold"><?= esc($A['judul']) ?></span><?php endif; ?>
          </small>
        </div>
        <div class="d-flex gap-2">
          <a href="<?= site_url('reviewer/abstrak') ?>" class="btn btn-light btn-sm">
            <i class="bi bi-arrow-left"></i> Kembali
          </a>
        </div>
      </div>

      <!-- BILAH AKSI (ACC/TOLAK) SAAT PENDING) -->
      <?php if ($taskStatus === 'pending'): ?>
        <div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
          <div class="me-2">
            <i class="bi bi-hourglass-split me-1"></i>
            Anda belum menerima penugasan ini. Silakan terima untuk mulai meninjau dan mengirim review.
          </div>
          <div class="d-flex gap-2">
            <!-- ACC -->
            <form method="post" action="<?= site_url('reviewer/abstrak/confirm/'.$idAbs) ?>">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="accept">
              <button class="btn btn-success btn-sm">
                <i class="bi bi-check-lg me-1"></i>Terima
              </button>
            </form>
            <!-- DECLINE -->
            <button class="btn btn-outline-danger btn-sm" id="btnOpenDecline"
                    data-id="<?= $idAbs ?>"
                    data-title="<?= esc($A['judul'] ?? '-', 'attr') ?>">
              <i class="bi bi-x-lg me-1"></i>Tolak
            </button>
          </div>
        </div>
      <?php endif; ?>

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
                    <div class="fw-semibold text-dark text-end ms-3"><?= esc($A['judul'] ?? '-') ?></div>
                  </div>
                </div>
                <div class="col-12 col-md-6">
                  <div class="d-flex justify-content-between">
                    <div class="text-muted">Penulis</div>
                    <div class="text-dark ms-3"><?= esc($A['nama_lengkap'] ?? '-') ?></div>
                  </div>
                </div>
                <div class="col-12 col-md-6">
                  <div class="d-flex justify-content-between">
                    <div class="text-muted">Kategori</div>
                    <div class="text-dark ms-3"><?= esc($A['nama_kategori'] ?? '-') ?></div>
                  </div>
                </div>
                <?php if (!empty($A['tanggal_upload'])): ?>
                  <div class="col-12">
                    <small class="text-muted"><i class="bi bi-upload me-1"></i>Unggah: <?= $fmt($A['tanggal_upload'], true) ?></small>
                  </div>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- PDF (collapsed by default) -->
          <div class="card shadow-sm border-0 mb-3">
            <div class="card-header d-flex align-items-center justify-content-between">
              <h5 class="card-title mb-0">
                <i class="bi bi-file-earmark-pdf text-primary me-2"></i>Pratinjau Abstrak
              </h5>
              <button class="btn btn-outline-primary btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#pdfWrap" aria-expanded="false" <?= ($canReview && $hasFile)?'':'disabled' ?>>
                Tampilkan / Sembunyikan
              </button>
            </div>
            <div id="pdfWrap" class="collapse">
              <div class="card-body">
                <?php if (!$canReview): ?>
                  <div class="alert alert-info mb-0"><i class="bi bi-info-circle me-2"></i>Terima penugasan terlebih dahulu dari Dashboard untuk mengakses berkas.</div>
                <?php elseif (!$hasFile): ?>
                  <div class="alert alert-warning mb-0"><i class="bi bi-exclamation-triangle me-2"></i>File belum tersedia. Hubungi panitia.</div>
                <?php else: ?>
                  <div class="pdf-wrap">
                    <iframe src="<?= site_url('reviewer/abstrak/blob/'.$idAbs) ?>#toolbar=1&navpanes=0" title="Preview PDF" class="pdf-frame"></iframe>
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
              <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#reviewModal" <?= $canReview?'':'disabled' ?>>
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
            </div>
          </div>
        </div>

        <!-- KANAN -->
        <div class="col-12 col-xl-4">
          <!-- Panel Status & Reviewer -->
          <div class="card shadow-sm border-0 mb-3">
            <div class="card-header d-flex align-items-center justify-content-between">
              <h5 class="card-title mb-0">
                <i class="bi bi-flag text-primary me-2"></i>Status & Reviewer
              </h5>
            </div>
            <div class="card-body">
              <!-- Status tugas saya -->
              <div class="mb-3">
                <div class="d-flex align-items-center justify-content-between">
                  <div class="text-muted">Status Tugas</div>
                  <span class="badge <?= $taskBadge($taskStatus) ?>"><?= ucfirst($taskStatus) ?></span>
                </div>
                <?php if ($taskStatus==='declined' && $taskReason): ?>
                  <div class="small text-muted mt-2">Alasan: <?= esc($taskReason) ?></div>
                <?php endif; ?>
              </div>

              <!-- Daftar reviewer -->
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

          <!-- Info Event -->
          <div class="card shadow-sm border-0">
            <div class="card-header d-flex align-items-center justify-content-between">
              <h5 class="card-title mb-0">
                <i class="bi bi-calendar-week text-primary me-2"></i>Info Event
              </h5>
            </div>
            <div class="card-body">
              <div class="d-flex justify-content-between">
                <div class="text-muted">Nama</div>
                <div class="text-dark ms-3"><?= esc($A['event_title'] ?? '-') ?></div>
              </div>
              <?php if (!empty($A['event_date'])): ?>
                <div class="d-flex justify-content-between mt-2">
                  <div class="text-muted">Tanggal</div>
                  <div class="text-dark ms-3"><?= $fmt($A['event_date']) ?> <?= esc($A['event_time'] ?? '') ?></div>
                </div>
              <?php endif; ?>
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
    <form method="post" action="<?= site_url('reviewer/abstrak/review/'.$idAbs) ?>" class="modal-content" id="reviewForm">
      <?= csrf_field() ?>
      <div class="modal-header">
        <h6 class="modal-title"><i class="bi bi-chat-left-text me-2"></i><?= $my ? 'Ubah Review' : 'Kirim Review' ?></h6>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <?php if (!$canReview): ?>
          <div class="alert alert-info">Silakan terima penugasan terlebih dahulu dari Dashboard.</div>
        <?php endif; ?>

        <div class="mb-3">
          <label class="form-label">Keputusan</label>
          <?php $val = strtolower((string)($my['keputusan'] ?? 'accepted')); ?>
          <select name="keputusan" class="form-select" <?= !$canReview?'disabled':'' ?> required>
            <option value="accepted" <?= $val==='accepted'?'selected':'' ?>>Diterima (Accept)</option>
            <option value="revision" <?= $val==='revision'?'selected':'' ?>>Revisi (Revision)</option>
            <option value="rejected" <?= $val==='rejected'?'selected':'' ?>>Ditolak (Reject)</option>
          </select>
        </div>
        <div>
          <label class="form-label">Komentar untuk Penulis</label>
          <textarea name="komentar" rows="6" class="form-control" <?= !$canReview?'disabled':'' ?> placeholder="Tulis komentar yang jelas dan konstruktif..."><?= esc($my['komentar'] ?? '') ?></textarea>
          <div class="form-text">Jika memilih <b>Ditolak</b>, komentar minimal 10 karakter.</div>
        </div>
      </div>

      <div class="modal-footer">
        <button class="btn btn-light" type="button" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-primary" type="submit" <?= !$canReview?'disabled':'' ?> id="btnSubmitReview">
          <i class="bi bi-save me-1"></i><?= $my ? 'Perbarui' : 'Kirim' ?>
        </button>
      </div>
    </form>
  </div>
</div>

<!-- Modal: Tolak Penugasan -->
<div class="modal fade" id="declineModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
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
/* ===== THEME TOKENS (patokan) ===== */
:root{
  --blue-50:#eff6ff; --blue-100:#dbeafe; --blue-200:#bfdbfe; --blue-300:#93c5fd;
  --blue-400:#60a5fa; --blue-500:#3b82f6; --blue-600:#2563eb; --blue-700:#1d4ed8; --blue-800:#1e40af; --blue-900:#1e3a8a;

  --ink:#0f172a; --muted:#6b7280; --radius:16px;

  --glass-bg: rgba(255,255,255,.92);
  --glass-bd: rgba(30,64,175,.14);
  --glass-shadow: 0 12px 28px rgba(30,64,175,.12);

  --success:#10b981; --warn:#f59e0b; --danger:#ef4444; --secondary:#6b7280;
}

/* ===== BASE ===== */
html,body{ height:100%; }
body{
  font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  font-size:15.5px; line-height:1.6; color:var(--ink);
  background:
    radial-gradient(1000px 380px at 10% -10%, rgba(59,130,246,.16), rgba(59,130,246,0) 60%),
    radial-gradient(1000px 380px at 90% 110%, rgba(59,130,246,.12), rgba(59,130,246,0) 70%),
    linear-gradient(180deg, var(--blue-50), #fff 40%);
}

.container-fluid{ max-width:min(100%, 1560px); margin-inline:auto; }

/* ===== HEADER (hero) ===== */
.header-section{
  background:linear-gradient(135deg,var(--blue-700),var(--blue-800));
  color:#fff; padding:1.25rem 1rem; border-radius:var(--radius);
  box-shadow:0 12px 28px rgba(30,64,175,.18);
}
.welcome-text{ color:#fff; font-weight:800; font-size:1.32rem; margin:0; }
.header-section small.text-muted{ color:rgba(255,255,255,.88)!important; }

/* ===== CARDS ===== */
.card{
  background:var(--glass-bg); border:1px solid var(--glass-bd);
  border-radius:14px; box-shadow:var(--glass-shadow);
}
.card-header{
  background:#fff; border-bottom:1px solid rgba(30,64,175,.14);
  border-radius:14px 14px 0 0 !important; padding:14px 18px;
}
.card-title{ font-weight:800; color:var(--blue-900); }

/* ===== FORM / BUTTON ===== */
.form-control, .form-select{
  border:2px solid #e2e8f0; border-radius:10px; transition:border-color .15s, box-shadow .15s;
}
.form-control:focus, .form-select:focus{
  border-color:var(--blue-600); box-shadow:0 0 0 .2rem rgba(37,99,235,.12);
}
.btn{ border-radius:10px; font-weight:800; }
.btn-primary{
  background:var(--blue-600); border-color:var(--blue-600);
  box-shadow:0 4px 12px rgba(37,99,235,.20);
}
.btn-light{ background:#fff; border:1px solid rgba(2,6,23,.08); }
.btn-outline-primary{ border-color:var(--blue-300); color:var(--blue-700); }
.btn-outline-primary:hover{ background:var(--blue-50); border-color:var(--blue-400); color:var(--blue-800); }
.btn-outline-secondary{ border-color:#d1d5db; color:#6b7280; }
.btn-outline-secondary:hover{ background-color:#f9fafb; border-color:#9ca3af; color:#374151; }
.btn-outline-danger{ border-color:#fecaca; color:#b91c1c; }
.btn-outline-danger:hover{ background:#fff1f2; border-color:#fca5a5; color:#7f1d1d; }

/* ===== BADGE ===== */
.badge{
  border-radius:999px; font-weight:800; font-size:.82rem;
  padding:.35rem .65rem; border:1px solid rgba(0,0,0,.05);
}
.bg-success{ background-color:rgba(16,185,129,.14)!important; color:#065f46; }
.bg-warning{ background-color:rgba(245,158,11,.18)!important; color:#92400e; }
.bg-danger{ background-color:rgba(239,68,68,.16)!important; color:#991b1b; }
.bg-secondary{ background-color:#f3f4f6!important; color:#374151; }

/* ===== PDF PREVIEW ===== */
.pdf-wrap{
  height:72vh; border:1px dashed rgba(30,64,175,.22);
  border-radius:12px; overflow:hidden; background:#f8fafc;
  box-shadow:inset 0 0 0 9999px rgba(59,130,246,.02);
}
.pdf-frame{ width:100%; height:100%; border:0; }

/* ===== INFO NASKAH — versi "biasa", tanpa hover ===== */
.card:has(.card-title .bi-info-circle) .card-body{
  background:#fff;
  padding: 1rem 1.1rem;
}
.card:has(.card-title .bi-info-circle) .card-body .row > [class^="col-"] > .d-flex.justify-content-between{
  /* list style */
  background: transparent;
  border: 0;
  border-bottom: 1px dashed rgba(30,64,175,.18);
  padding: .55rem 0;
  box-shadow: none;
}
.card:has(.card-title .bi-info-circle) .card-body .row > [class^="col-"]:last-child > .d-flex.justify-content-between{
  border-bottom: 0; /* baris terakhir */
}
.card:has(.card-title .bi-info-circle) .card-body .d-flex.justify-content-between > div:first-child{
  color: var(--muted);
  font-weight: 600;
  min-width: 110px; /* label lebar tetap */
  flex: 0 0 110px;
}
.card:has(.card-title .bi-info-circle) .card-body .d-flex.justify-content-between > div:last-child{
  color: var(--ink);
  font-weight: 700;
  text-align: left;      /* nilai rata kiri */
  margin-left: .8rem;
  white-space: normal;   /* biar multi-baris */
  word-break: break-word;
}
.card:has(.card-title .bi-info-circle) .card-body small.text-muted{
  background: transparent;
  border: 0;
  padding: 0;
  font-weight: 600;
  color: var(--muted) !important;
}

/* ===== UTIL ===== */
.text-muted{ color:var(--muted)!important; }
.bg-light{ background:#f8fafc!important; }

/* ===== RESPONSIVE ===== */
@media (max-width: 991.98px){
  .welcome-text{ font-size:1.2rem; }
}
@media (max-width: 575.98px){
  .container-fluid{ padding-left:.5rem!important; padding-right:.5rem!important; }
  .pdf-wrap{ height:60vh; }
  /* label sedikit lebih sempit di mobile */
  .card:has(.card-title .bi-info-circle) .card-body .d-flex.justify-content-between > div:first-child{
    min-width: 92px; flex-basis: 92px;
  }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
  // Validasi form review
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

  // Modal Decline (ACC/Tolak tugas)
  const declineBtn = document.getElementById('btnOpenDecline');
  if (declineBtn) {
    const modal = new bootstrap.Modal(document.getElementById('declineModal'));
    declineBtn.addEventListener('click', () => {
      document.getElementById('declTitle').textContent = 'Menolak penugasan: "' + (declineBtn.getAttribute('data-title')||'') + '"';
      document.getElementById('declReason').value = '';
      modal.show();
    });
  }
});
</script>
