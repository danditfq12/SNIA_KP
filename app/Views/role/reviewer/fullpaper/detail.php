<?php
// INPUT dari controller:
// 'submission' => [id,title,full_paper_status,full_paper_uploaded_at,file_available,file_path,nama_lengkap,nama_kategori,event_title,event_date,event_time,(opsional) revisi_ke, abstrak_status]
// 'taskStatus' => 'accepted'|'pending'|'declined'
// 'taskReason' => string|null
// 'myReview'   => ['keputusan','komentar','tanggal_review'] | null
// 'reviewers'  => array (tiap item: order,id,nama,tugas_status,keputusan,tanggal,is_me)

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
    in_array($s,['accepted','diterima','acc','approved']) => 'bg-success',
    in_array($s,['revision','revisi'])                    => 'bg-warning text-dark',
    in_array($s,['rejected','ditolak','reject'])          => 'bg-danger',
    default => 'bg-secondary'
  };
};
$taskBadge = fn($st)=> $st==='accepted' ? 'bg-success' : ($st==='declined'?'bg-danger':'bg-secondary');
$canReview = ($taskStatus === 'accepted');
$hasFile   = !empty($S['file_available']);
$blobUrl   = site_url('reviewer/fullpaper/blob/'.(int)($S['id'] ?? 0));
$revNo     = isset($S['revisi_ke']) ? (int)$S['revisi_ke'] : null;

// Status Abstrak
$absStatus = strtolower(trim((string)($S['abstrak_status'] ?? '')));
$absBadge  = function($s){
  $s = strtolower((string)$s);
  return match(true){
    in_array($s,['accepted','diterima','acc','approved']) => 'bg-success',
    in_array($s,['revision','revisi'])                    => 'bg-warning text-dark',
    in_array($s,['rejected','ditolak','reject'])          => 'bg-danger',
    default => 'bg-secondary'
  };
};
$absLabel = $absStatus ? strtoupper($absStatus) : 'PENDING';

/* ==== Hitung mayoritas panel + opsional revisi ==== */
$acc=$rev=$rej=$done=0;
$total = count($reviewers);
foreach ($reviewers as $rv) {
  $k = strtolower((string)($rv['keputusan'] ?? ''));
  if ($k==='') continue;
  $done++;
  if (in_array($k,['accepted','diterima','accept','acc','approved'])) $acc++;
  elseif (in_array($k,['revision','revisi'])) $rev++;
  elseif (in_array($k,['rejected','reject','ditolak'])) $rej++;
}
$panel = 'PENDING';
$optional = false;
if ($rej >= 2) { $panel = 'REJECTED'; }
elseif ($acc >= 2) { $panel = 'ACCEPTED'; $optional = ($rev >= 1); }
elseif ($rev >= 2) { $panel = 'REVISION'; }
else { $panel = ($done>0 ? 'UPLOADED' : 'PENDING'); }

$panelBadge = function($p){
  $p = strtoupper((string)$p);
  return match($p){
    'ACCEPTED' => 'bg-success',
    'REVISION' => 'bg-warning text-dark',
    'REJECTED' => 'bg-danger',
    'UPLOADED','PENDING' => 'bg-secondary',
    default => 'bg-secondary'
  };
};
$pct = function(int $n, int $d): int { $d = max(1,$d); return (int)round(($n/$d)*100); };

// nilai untuk "Review Anda"
$myKpt = strtolower($my['keputusan'] ?? '');
$myCmt = trim((string)($my['komentar'] ?? ''));
$hasMy = !empty($my);
$btnText = $hasMy ? 'Ubah' : 'Kirim';
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_reviewer') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill page-wrap-blue">
    <div class="container-xxl px-3 px-md-4 py-4">

      <!-- HERO -->
      <div class="card-hero mb-3">
        <div class="hero-body">
          <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
            <div class="me-2">
              <h3 class="hero-title mb-1">
                <i class="bi bi-file-earmark-text me-2"></i>Detail Full Paper
              </h3>
              <div class="text-white-70 small">
                <?= esc($S['event_title'] ?? '-') ?>
                <?php if(!empty($S['title'])): ?>
                  <span class="d-inline-block mx-2">•</span>
                  <span class="fw-semibold"><?= esc($S['title']) ?></span>
                <?php endif; ?>
              </div>

              <!-- Badge revisi + panel -->
              <div class="mt-2 d-flex flex-wrap align-items-center gap-2">
                <?php if ($revNo !== null): ?>
                  <span class="badge bg-light text-dark fw-semibold">
                    <i class="bi bi-arrow-repeat me-1"></i>Revisi ke-<?= (int)$revNo ?>
                  </span>
                <?php endif; ?>
                <span class="badge <?= $panelBadge($panel) ?>">
                  <i class="bi bi-flag me-1"></i>Panel: <?= $panel ?>
                </span>
                <?php if ($panel==='ACCEPTED' && $optional): ?>
                  <span class="badge bg-warning text-dark">
                    <i class="bi bi-stars me-1"></i>Revisi Opsional diperbolehkan
                  </span>
                <?php endif; ?>
              </div>
            </div>

            <div class="d-flex flex-wrap align-items-center gap-2 status-fixed-wrap">
              <span class="chip-fixed <?= $taskBadge($taskStatus) ?>">
                <i class="bi bi-person-check me-1"></i><?= ucfirst($taskStatus) ?> tugas
              </span>
              <?php if (!empty($S['full_paper_status'])): ?>
                <span class="chip-fixed <?= $badgeMap($S['full_paper_status']) ?>">
                  Status FP: <?= strtoupper($S['full_paper_status']) ?>
                </span>
              <?php endif; ?>
              <a href="<?= site_url('reviewer/fullpaper') ?>" class="btn btn-light btn-sm">
                <i class="bi bi-arrow-left"></i> Kembali
              </a>
            </div>
          </div>
        </div>

        <!-- Pesan pending accept -->
        <?php if ($taskStatus === 'pending'): ?>
          <div class="hero-tabs">
            <div class="alert alert-warning d-flex flex-wrap align-items-center justify-content-between gap-2 mb-0">
              <div><i class="bi bi-hourglass-split me-1"></i>Anda belum menerima penugasan ini. Terima untuk mulai meninjau.</div>
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
          </div>
        <?php endif; ?>
      </div>

      <div class="row g-3">
        <!-- KIRI -->
        <div class="col-12 col-xl-8">
          <!-- Informasi Full Paper -->
          <div class="card card-glass border-0 mb-3">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
              <span class="badge bg-blue-soft"><i class="bi bi-info-circle"></i></span>
              <h6 class="mb-0 fw-semibold text-blue-900">Informasi Full Paper</h6>
            </div>
            <div class="card-body">
              <div class="kv-row mb-3">
                <div class="kv-label">Judul</div>
                <div class="kv-value"><?= esc($S['title'] ?? '-') ?></div>
              </div>
              <div class="row g-3">
                <div class="col-12 col-md-6">
                  <div class="kv-row">
                    <div class="kv-label">Penulis</div>
                    <div class="kv-value"><?= esc($S['nama_lengkap'] ?? '-') ?></div>
                  </div>
                </div>
                <div class="col-12 col-md-6">
                  <div class="kv-row">
                    <div class="kv-label">Kategori</div>
                    <div class="kv-value"><?= esc($S['nama_kategori'] ?? '-') ?></div>
                  </div>
                </div>
                <?php if (!empty($S['full_paper_uploaded_at'])): ?>
                <div class="col-12 col-md-6">
                  <div class="kv-row">
                    <div class="kv-label">Diunggah</div>
                    <div class="kv-value"><?= $fmt($S['full_paper_uploaded_at'], true) ?></div>
                  </div>
                </div>
                <?php endif; ?>
                <?php if ($revNo !== null): ?>
                <div class="col-12 col-md-6">
                  <div class="kv-row">
                    <div class="kv-label">Revisi ke-</div>
                    <div class="kv-value"><?= (int)$revNo ?></div>
                  </div>
                </div>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- PDF Viewer -->
          <div class="card card-glass border-0 mb-3">
            <div class="card-header d-flex align-items-center justify-content-between bg-transparent border-0 pb-0">
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-blue-soft"><i class="bi bi-file-earmark-pdf"></i></span>
                <h6 class="mb-0 fw-semibold text-blue-900">Pratinjau Full Paper</h6>
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
        </div>

        <!-- KANAN (empat kartu terpisah sesuai urutan) -->
        <div class="col-12 col-xl-4">
          <!-- 1) Informasi Event -->
          <div class="card card-glass border-0 mb-3">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
              <span class="badge bg-blue-soft"><i class="bi bi-calendar-week"></i></span>
              <h6 class="mb-0 fw-semibold text-blue-900">Informasi Event</h6>
            </div>
            <div class="card-body">
              <div class="kv-row">
                <div class="kv-label">Nama Event</div>
                <div class="kv-value"><?= esc($S['event_title'] ?? '-') ?></div>
              </div>
              <?php if (!empty($S['event_date'])): ?>
                <div class="kv-row mt-2">
                  <div class="kv-label">Tanggal</div>
                  <div class="kv-value"><?= $fmt($S['event_date']) ?> <?= esc($S['event_time'] ?? '') ?></div>
                </div>
              <?php endif; ?>
            </div>
          </div>

          <!-- 2) Status Abstrak -->
          <div class="card card-glass border-0 mb-3">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
              <span class="badge bg-blue-soft"><i class="bi bi-journal-check"></i></span>
              <h6 class="mb-0 fw-semibold text-blue-900">Status Abstrak</h6>
            </div>
            <div class="card-body">
              <div class="d-flex align-items-center justify-content-between">
                <div class="text-muted">Status</div>
                <span class="badge <?= $absBadge($absStatus) ?>"><?= $absLabel ?></span>
              </div>
              <?php if (!$absStatus): ?>
                <div class="small text-muted mt-2">Belum ada data status abstrak atau kolom status tidak tersedia.</div>
              <?php endif; ?>
            </div>
          </div>

          <!-- 3) Review Anda -->
          <div class="card card-glass border-0 mb-3">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center justify-content-between">
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-blue-soft"><i class="bi bi-person-check"></i></span>
                <h6 class="mb-0 fw-semibold text-blue-900">Review Anda</h6>
              </div>
              <button
                class="btn btn-primary btn-sm"
                data-bs-toggle="modal" data-bs-target="#reviewModal"
                <?= ($canReview && $hasFile) ? '' : 'disabled' ?>>
                <i class="bi bi-pencil-square me-1"></i><?= $btnText ?>
              </button>
            </div>
            <div class="card-body">
              <?php if (!$hasMy): ?>
                <div class="text-muted">Belum ada review yang Anda kirim.</div>
              <?php else: ?>
                <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                  <span class="badge <?= $badgeMap($myKpt) ?>"><?= strtoupper($myKpt ?: 'PENDING') ?></span>
                  <?php if (!empty($my['tanggal_review'])): ?>
                    <small class="text-muted">Terakhir dikirim: <?= $fmt($my['tanggal_review'], true) ?></small>
                  <?php endif; ?>
                </div>
                <?php if ($myCmt !== ''): ?>
                  <div class="border rounded p-2 bg-light">
                    <div class="small text-muted mb-1">Komentar:</div>
                    <div><?= nl2br(esc($myCmt)) ?></div>
                  </div>
                <?php else: ?>
                  <div class="text-muted">Tidak ada komentar.</div>
                <?php endif; ?>
              <?php endif; ?>

              <?php if (!$canReview): ?>
                <div class="alert alert-info mt-3 mb-0"><i class="bi bi-info-circle me-1"></i>Terima penugasan terlebih dahulu untuk membuka form review.</div>
              <?php elseif (!$hasFile): ?>
                <div class="alert alert-warning mt-3 mb-0"><i class="bi bi-exclamation-triangle me-1"></i>File belum tersedia, form review dinonaktifkan.</div>
              <?php endif; ?>
            </div>
          </div>

          <!-- 4) Status & Reviewer -->
          <div class="card card-glass border-0">
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

              <!-- Ringkasan Panel -->
              <div class="p-2 border rounded-3 mb-3">
                <div class="d-flex align-items-center justify-content-between">
                  <div class="fw-semibold">Hasil Panel</div>
                  <span class="badge <?= $panelBadge($panel) ?>"><i class="bi bi-flag me-1"></i><?= $panel ?></span>
                </div>
                <div class="mt-2">
                  <div class="progress" style="height:12px;">
                    <div class="progress-bar bg-success" style="width: <?= $pct($acc, max(1,$total)) ?>%"></div>
                    <div class="progress-bar bg-warning text-dark" style="width: <?= $pct($rev, max(1,$total)) ?>%"></div>
                    <div class="progress-bar bg-danger" style="width: <?= $pct($rej, max(1,$total)) ?>%"></div>
                  </div>
                  <div class="d-flex flex-wrap gap-2 mt-2 small">
                    <span class="badge bg-success-subtle text-success-emphasis"><i class="bi bi-check2-circle me-1"></i>ACC <?= $acc ?></span>
                    <span class="badge bg-warning-subtle text-warning-emphasis"><i class="bi bi-arrow-repeat me-1"></i>Revisi <?= $rev ?></span>
                    <span class="badge bg-danger-subtle text-danger-emphasis"><i class="bi bi-x-octagon me-1"></i>Reject <?= $rej ?></span>
                    <span class="ms-auto text-muted">Selesai <?= $done ?>/<?= $total ?></span>
                  </div>
                </div>
              </div>

              <div>
                <div class="fw-semibold mb-2">Daftar Reviewer</div>
                <?php if (empty($reviewers)): ?>
                  <div class="text-muted">Belum ada data reviewer.</div>
                <?php else: foreach ($reviewers as $rv):
                  $tsBadge = $rv['tugas_status']==='accepted' ? 'bg-success'
                            : ($rv['tugas_status']==='declined' ? 'bg-danger' : 'bg-secondary'); ?>
                  <div class="border rounded p-2 mb-2">
                    <div class="d-flex justify-content-between">
                      <div class="fw-semibold">Reviewer <?= (int)$rv['order'] ?> <?= $rv['is_me'] ? '<span class="text-primary">(Anda)</span>' : '' ?></div>
                      <span class="badge <?= $tsBadge ?>"><?= ucfirst($rv['tugas_status']) ?></span>
                    </div>
                    <div class="small text-muted mb-1"><?= esc($rv['nama']) ?></div>
                    <div class="d-flex align-items-center justify-content-between">
                      <div><span class="badge <?= $badgeMap($rv['keputusan']) ?>"><?= ucfirst($rv['keputusan']) ?></span></div>
                      <div class="small text-muted"><?= $rv['tanggal'] ? $fmt($rv['tanggal'], true) : '' ?></div>
                    </div>
                  </div>
                <?php endforeach; endif; ?>
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
        <h6 class="modal-title"><i class="bi bi-chat-left-text me-2"></i><?= $hasMy ? 'Ubah Review' : 'Kirim Review' ?></h6>
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
          <div class="form-text">Komentar <b>wajib diisi</b>. Jika memilih <b>Ditolak</b>, minimal 10 karakter.</div>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-light" type="button" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-primary" type="submit" <?= !$canReview || !$hasFile ? 'disabled' : '' ?> id="btnSubmitReview">
          <i class="bi bi-save me-1"></i><?= $hasMy ? 'Perbarui' : 'Kirim' ?>
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
        <textarea name="reason" id="declReason" rows="4" class="form-control" required minlength="5" placeholder="Tuliskan alasan penolakan secara singkat dan jelas..."></textarea>
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
  --side-pad: clamp(1rem, 2.3vw, 2.2rem);
  --glass-bg: rgba(255,255,255,.96);
  --glass-bd: rgba(30,64,175,.14);
  --glass-shadow: 0 10px 24px rgba(2,6,23,.08);
}
.container-xxl{ max-width:min(100%, 1560px); padding-left:var(--side-pad)!important; padding-right:var(--side-pad)!important; margin-inline:auto; }
body{ font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size:15.5px; line-height:1.6; color:var(--ink); }
.page-wrap-blue{
  min-height:100vh; padding-top:72px;
  background:
    radial-gradient(1000px 380px at 10% -10%, rgba(59,130,246,.16), rgba(59,130,246,0) 60%),
    radial-gradient(1000px 380px at 90% 110%, rgba(59,130,246,.12), rgba(59,130,246,0) 70%),
    linear-gradient(180deg, var(--blue-50), #fff 40%);
}

/* HERO & tools */
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

/* PDF */
.pdf-wrap{ border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; background:#f8fafc; transition:height .2s ease; }
.pdf-wrap.is-min{ height:52vh; }
.pdf-wrap.is-max{ height:82vh; }
.pdf-frame{ width:100%; height:100%; border:0; }

/* key-value rows */
.kv-row{ display:flex; gap:1rem; align-items:flex-start; }
.kv-label{ flex:0 0 120px; color:#64748b; font-weight:600; }
.kv-value{ flex:1 1 auto; font-weight:700; color:#0f172a; text-align:left; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }

/* colors for badges */
.bg-success{ background-color:#10b981 !important; }
.bg-warning{ background-color:#f59e0b !important; }
.bg-danger{ background-color:#ef4444 !important; }
.bg-secondary{ background-color:#6b7280 !important; }

/* HSL subtle badges (fallback untuk bootstrap <5.3) */
.bg-success-subtle{ background:#eaf7ef!important; }
.bg-warning-subtle{ background:#fff6db!important; }
.bg-danger-subtle { background:#fde2e1!important; }
.text-success-emphasis{ color:#157347!important; }
.text-warning-emphasis{ color:#946200!important; }
.text-danger-emphasis { color:#b02a37!important; }

/* hints */
.empty-hint{ color:#567; background:#f6f9ff; border:1px dashed rgba(30,64,175,.18); border-radius:12px; padding:.8rem 1rem; font-weight:600; }

@media (max-width: 575.98px){
  .container-xxl{ padding-left: calc(var(--side-pad) - .25rem)!important; padding-right: calc(var(--side-pad) - .25rem)!important; }
  .card-hero .hero-body{ padding:1.4rem 1rem; min-height:150px; }
  .pdf-wrap.is-min{ height:46vh; }
  .kv-label{ flex-basis:90px; }
  .kv-value{ white-space:normal; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
  // Toggle PDF size (half <-> full)
  const wrap = document.getElementById('pdfWrap');
  const btn  = document.getElementById('btnTogglePdf');
  if (wrap && btn){
    wrap.classList.add('is-min'); // default setengah
    btn.addEventListener('click', ()=>{
      wrap.classList.toggle('is-max');
      wrap.classList.toggle('is-min');
    });
  }

  // Validasi review minimal: komentar wajib, kalau 'rejected' ≥ 10
  const form   = document.getElementById('reviewForm');
  const select = form?.querySelector('select[name="keputusan"]');
  const text   = form?.querySelector('textarea[name="komentar"]');
  form?.addEventListener('submit', (e) => {
    const decision = (select?.value || '').toLowerCase();
    const comment  = (text?.value || '').trim();
    if (comment.length < 1) {
      e.preventDefault();
      alert('Komentar wajib diisi.');
      text?.focus();
      return;
    }
    if (decision === 'rejected' && comment.length < 10) {
      e.preventDefault();
      alert('Jika menolak, komentar minimal 10 karakter.');
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
