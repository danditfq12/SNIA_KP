<?php
/** @var array $vm */
$title = $title ?? 'Detail Abstrak';
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill page-wrap-blue">
    <div class="container-xxl px-3 px-md-4 py-4">

      <!-- ===== Header / Hero ===== -->
      <div class="header-strip mb-3">
        <div class="h-left">
          <div class="eyebrow">Detail Abstrak</div>
          <h2 class="title"><?= esc($vm['judul'] ?? '—') ?></h2>
          <div class="meta">
            <span class="icontext"><i class="bi bi-calendar-event"></i><?= esc($vm['event_title'] ?? '—') ?></span>
            <span class="sep">•</span>
            <span class="icontext"><i class="bi bi-tag"></i><?= esc($vm['kategori'] ?? '—') ?></span>
            <span class="sep">•</span>
            <span class="icontext"><i class="bi bi-clock"></i><?= esc($vm['uploaded_at'] ?? '—') ?></span>
          </div>
        </div>
        <div class="h-right">
          <?php if (!empty($vm['chip'])): ?>
            <span class="chip <?= esc($vm['chip']['cls']) ?>">
              <i class="bi bi-flag me-1"></i><?= esc($vm['chip']['label']) ?>
            </span>
          <?php endif; ?>
          <a href="javascript:history.back()" class="btn btn-light btn-sm ms-2">
            <i class="bi bi-arrow-left"></i> Kembali
          </a>
        </div>
      </div>

      <!-- Auto Reject notice -->
      <?php if (!empty($vm['is_auto_reject'])): ?>
        <div class="alert alert-danger fw-semibold d-flex align-items-start gap-2">
          <i class="bi bi-x-octagon fs-5"></i>
          <div>
            <div>Abstrak <strong>ditolak otomatis</strong> karena melewati batas waktu.</div>
            <div class="small mt-1">
              <?php if(!empty($vm['auto_reason'])): ?>
                Alasan: <em><?= esc($vm['auto_reason']) ?></em>.
              <?php endif; ?>
              <?php if(!empty($vm['auto_at'])): ?>
                Waktu: <?= esc($vm['auto_at']) ?>.
              <?php endif; ?>
            </div>
          </div>
        </div>
      <?php endif; ?>

      <div class="row g-3">
        <!-- ===== KIRI ===== -->
        <div class="col-12 col-xl-8">
          <!-- Ringkasan & Aksi -->
          <div class="card card-plain mb-3">
            <div class="card-body p-3 p-md-4">
              <div class="row g-3">
                <div class="col-sm-6">
                  <div class="info-row"><span>Judul</span><b><?= esc($vm['judul'] ?? '—') ?></b></div>
                  <div class="info-row"><span>Kategori</span><b><?= esc($vm['kategori'] ?? '—') ?></b></div>
                </div>
                <div class="col-sm-6">
                  <div class="info-row"><span>Status</span><b><?= esc($vm['chip']['label'] ?? '—') ?></b></div>
                  <div class="info-row"><span>Event</span><b><?= esc($vm['event_title'] ?? '—') ?></b></div>
                </div>
              </div>

              <div class="mt-3 pt-3 border-top d-flex flex-wrap gap-2">
                <?php if (!empty($vm['can_reupload']) && !empty($vm['reupload_url'])): ?>
                  <a class="btn btn-danger" href="<?= esc($vm['reupload_url']) ?>">
                    <i class="bi bi-upload"></i> Upload Ulang Abstrak
                  </a>
                <?php endif; ?>

                <?php if (!empty($vm['show_cancel']) && !empty($vm['cancel_action'])): ?>
                  <form id="cancelForm" action="<?= esc($vm['cancel_action']) ?>" method="POST" class="m-0">
                    <?= csrf_field() ?>
                    <button type="button" class="btn btn-outline-danger"
                            data-bs-toggle="modal" data-bs-target="#confirmCancelModal"
                            data-form-id="cancelForm" data-message="Batalkan upload abstrak ini?">
                      <i class="bi bi-x-circle"></i> Batalkan Upload
                    </button>
                  </form>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <!-- ===== Pratinjau PDF (sesuai controller: file_exists + file_stream_url + file_download_url) ===== -->
          <?php if (!empty($vm['file_exists'])): ?>
            <div class="card card-plain mb-3" id="pdfCard">
              <div class="d-flex align-items-center justify-content-between px-3 pt-3">
                <div class="d-flex align-items-center gap-2">
                  <span class="badge bg-blue-soft"><i class="bi bi-file-earmark-pdf"></i></span>
                  <h6 class="mb-0 fw-semibold text-blue-900">Pratinjau Abstrak</h6>
                </div>
                <div class="d-flex align-items-center gap-2">
                  <?php if (!empty($vm['file_stream_url'])): ?>
                    <a class="btn btn-outline-secondary btn-sm" href="<?= esc($vm['file_stream_url']) ?>" target="_blank" rel="noopener" title="Buka di tab baru">
                      <i class="bi bi-box-arrow-up-right"></i>
                    </a>
                  <?php endif; ?>
                  <?php if (!empty($vm['file_download_url'])): ?>
                    <a class="btn btn-outline-secondary btn-sm" href="<?= esc($vm['file_download_url']) ?>" title="Unduh">
                      <i class="bi bi-download"></i>
                    </a>
                  <?php endif; ?>
                  <button type="button" class="btn btn-outline-secondary btn-sm" id="pdfFullscreenBtn" title="Fullscreen">
                    <i class="bi bi-arrows-fullscreen"></i>
                  </button>
                </div>
              </div>

              <div class="small text-muted px-3 pt-2">
                <i class="bi bi-paperclip me-1"></i>File PDF abstrak
              </div>

              <div class="mt-2 pdf-wrap" id="pdfWrap" style="--pdf-h:80vh;">
                <iframe class="pdf-frame"
                        src="<?= esc($vm['file_stream_url']) ?>#zoom=page-width&toolbar=1"
                        title="Preview PDF" loading="lazy"></iframe>
                <div class="pdf-hint small text-muted px-3 pb-3">
                  Jika pratinjau kosong, klik ikon <i class="bi bi-box-arrow-up-right"></i> untuk membuka di tab baru.
                  <?php if (!empty($vm['gdocs_viewer_url'])): ?>
                    Atau gunakan
                    <a href="<?= esc($vm['gdocs_viewer_url']) ?>" target="_blank" rel="noopener">
                      Google Docs Viewer
                    </a>.
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php else: ?>
            <div class="card card-plain mb-3">
              <div class="card-body p-3">
                <div class="empty-hint">
                  <i class="bi bi-info-circle me-1"></i>File abstrak belum tersedia untuk dipratinjau.
                </div>
              </div>
            </div>
          <?php endif; ?>

          <!-- ===== Form Revisi ===== -->
          <?php if (!empty($vm['show_revision'])): ?>
            <div id="section-revisi" class="card card-plain mb-3">
              <div class="card-header border-0 pb-0">
                <h6 class="mb-0"><i class="bi bi-arrow-repeat me-1"></i>Upload Revisi Abstrak</h6>
              </div>
              <div class="card-body p-3 p-md-4">
                <?php if(!empty($vm['rev_deadline'])): ?>
                  <div class="alert alert-info fw-semibold">
                    <i class="bi bi-clock me-1"></i> Batas pengumpulan revisi: <b><?= esc($vm['rev_deadline']) ?></b>
                  </div>
                <?php endif; ?>

                <?php if (!empty($vm['can_upload_revision'])): ?>
                  <form action="<?= esc($vm['revision_post']) ?>" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                    <?= csrf_field() ?>
                    <div class="mb-3">
                      <label class="form-label">File Abstrak (PDF) <span class="text-danger">*</span></label>
                      <input type="file" class="form-control form-control-soft" name="file_abstrak" accept=".pdf,application/pdf" required>
                      <div class="form-text">Format PDF, maksimal 5MB.</div>
                      <div class="invalid-feedback">File PDF wajib diunggah.</div>
                    </div>
                    <button type="submit" class="btn btn-primary">
                      <i class="bi bi-send me-1"></i>Kirim Revisi
                    </button>
                  </form>
                <?php else: ?>
                  <div class="alert alert-warning mb-0 fw-semibold">
                    <i class="bi bi-lock me-1"></i> Batas waktu pengumpulan revisi telah berakhir.
                  </div>
                <?php endif; ?>
              </div>
            </div>
          <?php endif; ?>

          <!-- ===== Komentar Reviewer ===== -->
          <div class="card card-plain mb-3">
            <div class="card-header border-0 pb-0">
              <h6 class="mb-0"><i class="bi bi-chat-left-dots me-1"></i>Komentar Reviewer</h6>
            </div>
            <div class="card-body p-3 p-md-4">
              <?php if (!empty($vm['timeline'])): ?>
                <div class="timeline">
                  <?php foreach ($vm['timeline'] as $t): ?>
                    <div class="tl-item">
                      <div class="tl-dot"></div>
                      <div class="tl-card">
                        <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-2">
                          <div class="d-flex flex-wrap align-items-center gap-2">
                            <span class="badge rounded-pill bg-light text-blue-900 border">
                              Revisi: <b class="ms-1"><?= esc($t['revisi_ke'] ?? '—') ?></b>
                            </span>
                            <span class="badge rounded-pill bg-<?= esc($t['badge'] ?? 'secondary') ?>-subtle text-<?= esc($t['badge'] ?? 'secondary') ?>">
                              <?= esc($t['badge_txt'] ?? 'Pending') ?>
                            </span>
                          </div>
                          <div class="text-muted small"><?= esc($t['waktu'] ?? '-') ?></div>
                        </div>
                        <div class="d-flex align-items-start gap-2 mb-2">
                          <div class="avatar-badge sm"><span><?= esc(strtoupper(mb_substr($t['reviewer'] ?? 'R',0,1))) ?></span></div>
                          <div>
                            <div class="fw-semibold text-blue-900"><?= esc($t['reviewer'] ?? 'Reviewer') ?></div>
                            <?php if (!empty($t['email'])): ?>
                              <div class="small">
                                <i class="bi bi-envelope me-1"></i>
                                <a class="text-primary" href="mailto:<?= esc($t['email']) ?>"><?= esc($t['email']) ?></a>
                              </div>
                            <?php endif; ?>
                          </div>
                        </div>
                        <div class="review-text"><?= nl2br(esc($t['komentar'] ?? '')) ?></div>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php else: ?>
                <div class="empty-hint">
                  <i class="bi bi-info-circle me-1"></i>Tidak ada komentar tersimpan.
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- ===== KANAN ===== -->
        <div class="col-12 col-xl-4">
          <!-- Status -->
          <div class="side-card sticky mb-3">
            <div class="side-head"><i class="bi bi-flag me-2"></i>Status</div>
            <div>
              <?php if (in_array($vm['status'] ?? '', ['menunggu','sedang_direview'], true)): ?>
                <div class="callout callout-info"><i class="bi bi-hourglass-split me-1"></i>Abstrak Anda sedang diproses oleh reviewer.</div>
              <?php elseif (($vm['status'] ?? '') === 'revisi'): ?>
                <div class="callout callout-warn"><i class="bi bi-arrow-repeat me-1"></i>Perlu revisi. Unggah file revisi pada form.</div>
                <?php if(!empty($vm['rev_deadline'])): ?>
                  <div class="small text-muted"><i class="bi bi-clock me-1"></i>Deadline revisi: <b><?= esc($vm['rev_deadline']) ?></b></div>
                <?php endif; ?>
              <?php elseif (($vm['status'] ?? '') === 'ditolak'): ?>
                <div class="callout callout-danger"><i class="bi bi-x-octagon me-1"></i>Abstrak ditolak.</div>
              <?php elseif (($vm['status'] ?? '') === 'diterima'): ?>
                <div class="callout callout-success"><i class="bi bi-check2-circle me-1"></i>Abstrak diterima.</div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Reviewer -->
          <div class="side-card mb-3">
            <div class="side-head"><i class="bi bi-person-badge me-2"></i>Reviewer Ditugaskan</div>
            <div>
              <?php if (!empty($vm['assigned_reviewer']['name']) || !empty($vm['assigned_reviewer']['email'])): ?>
                <div class="d-flex align-items-center gap-3">
                  <div class="avatar-badge"><span><?= esc(strtoupper(mb_substr($vm['assigned_reviewer']['name'] ?? 'R',0,1))) ?></span></div>
                  <div class="flex-fill">
                    <div class="fw-bold text-blue-900"><?= esc($vm['assigned_reviewer']['name'] ?? '—') ?></div>
                    <?php if(!empty($vm['assigned_reviewer']['email'])): ?>
                      <div class="small"><i class="bi bi-envelope me-1"></i>
                        <a href="mailto:<?= esc($vm['assigned_reviewer']['email']) ?>" class="text-primary">
                          <?= esc($vm['assigned_reviewer']['email']) ?>
                        </a>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
              <?php else: ?>
                <div class="empty-hint"><i class="bi bi-info-circle me-1"></i>Reviewer belum ditetapkan.</div>
              <?php endif; ?>
            </div>
          </div>

          <!-- Kontributor -->
          <?php if (!empty($vm['contributors'])): ?>
            <div class="side-card">
              <div class="side-head"><i class="bi bi-people me-2"></i>Kontributor</div>
              <div>
                <?php foreach ($vm['contributors'] as $c): ?>
                  <div class="contrib-item">
                    <div class="avatar-badge"><span><?= esc(strtoupper(mb_substr($c['name'] ?? 'C',0,1))) ?></span></div>
                    <div>
                      <div class="fw-semibold text-blue-900"><?= esc($c['name'] ?? '—') ?></div>
                      <?php if (!empty($c['affiliation'])): ?>
                        <div class="text-muted small"><i class="bi bi-building me-1"></i><?= esc($c['affiliation']) ?></div>
                      <?php endif; ?>
                      <?php if (!empty($c['email'])): ?>
                        <div class="small"><i class="bi bi-envelope me-1"></i>
                          <a class="text-primary" href="mailto:<?= esc($c['email']) ?>"><?= esc($c['email']) ?></a>
                        </div>
                      <?php endif; ?>
                      <?php if (!empty($c['presenter'])): ?>
                        <span class="chip chip-info mt-1">Presenter</span>
                      <?php endif; ?>
                    </div>
                  </div>
                <?php endforeach; ?>
              </div>
            </div>
          <?php endif; ?>
        </div>
      </div>
    </div>

    <!-- Modal Konfirmasi Batalkan Upload -->
    <div class="modal fade" id="confirmCancelModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
          <div class="modal-header bg-light">
            <h6 class="modal-title"><i class="bi bi-exclamation-triangle me-2 text-danger"></i>Konfirmasi</h6>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
          </div>
          <div class="modal-body">
            <p id="confirmCancelText" class="mb-0">Batalkan upload abstrak?</p>
            <small class="text-muted">Tindakan ini akan menghapus file dan data unggahan Anda.</small>
          </div>
          <div class="modal-footer">
            <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Tidak</button>
            <button id="confirmCancelBtn" type="button" class="btn btn-danger">
              <i class="bi bi-x-circle me-1"></i> Ya, Batalkan
            </button>
          </div>
        </div>
      </div>
    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<style>
:root{
  --blue-50:#eff6ff; --blue-100:#dbeafe; --blue-200:#bfdbfe; --blue-300:#93c5fd; --blue-400:#60a5fa;
  --blue-500:#3b82f6; --blue-600:#2563eb; --blue-700:#1d4ed8; --blue-800:#1e40af; --blue-900:#1e3a8a;
  --ink:#0f172a; --muted:#6b7280; --radius:16px; --side-pad:clamp(1rem,2.3vw,2.2rem);
}
body{ font-family: ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial,"Noto Sans",sans-serif; font-size:15.75px; line-height:1.6; color:var(--ink); }
.page-wrap-blue{
  min-height:100vh; padding-top:72px;
  background:
    radial-gradient(900px 300px at 20% -10%, rgba(59,130,246,.16), rgba(59,130,246,0) 60%),
    radial-gradient(900px 300px at 80% 110%, rgba(59,130,246,.12), rgba(59,130,246,0) 70%),
    linear-gradient(180deg, var(--blue-50), #fff 40%);
}
.container-xxl{ max-width:min(100%,1560px); padding-inline:var(--side-pad)!important; margin-inline:auto; }

/* Header strip */
.header-strip{
  display:flex; align-items:flex-start; justify-content:space-between; gap:12px;
  background:linear-gradient(135deg,var(--blue-700),var(--blue-800)); color:#fff;
  border-radius:16px; padding:18px 16px; box-shadow:0 12px 28px rgba(30,64,175,.12);
}
.header-strip .title{ margin:0; font-size:1.45rem; font-weight:900; letter-spacing:.2px; }
.header-strip .eyebrow{ font-weight:700; opacity:.92; }
.header-strip .meta{ font-size:.9rem; opacity:.95; display:flex; gap:.6rem; flex-wrap:wrap; }
.header-strip .icontext{ display:inline-flex; gap:.4rem; align-items:center; }
.header-strip .sep{ opacity:.6; }
.h-right{ display:flex; align-items:center; }

/* Chips */
.chip{ display:inline-flex; align-items:center; gap:.3rem; padding:.28rem .6rem; border-radius:999px; font-weight:800; font-size:.82rem; border:1px solid rgba(255,255,255,.25); color:#fff; }
.chip-success{ background:#10b981; } .chip-danger{ background:#ef4444; }
.chip-warn{ background:#f59e0b; } .chip-info{ background:#3b82f6; }
.chip-muted{ background:#64748b; }

/* Cards */
.card-plain{ border:1px solid rgba(30,64,175,.10); border-radius:16px; background:#fff; box-shadow:0 10px 24px rgba(30,64,175,.08); }
.card-plain .card-header h6{ font-weight:800; color:var(--blue-900); }

/* Info rows */
.info-row{ display:flex; align-items:center; justify-content:space-between; gap:.75rem; padding:.45rem 0; }
.info-row > span{ color:var(--muted); }

/* Callouts */
.callout{ border:1px dashed rgba(30,64,175,.18); border-radius:12px; padding:.7rem .9rem; font-weight:600; }
.callout-info{ background:#eef6ff; color:#124d9b; }
.callout-success{ background:#ecfdf5; color:#065f46; }
.callout-danger{ background:#fef2f2; color:#9f1239; }
.callout-warn{ background:#fff7ed; color:#92400e; }

/* Side panel */
.side-card{ border:1px solid rgba(30,64,175,.10); border-radius:16px; background:#fff; padding:14px 16px; box-shadow:0 10px 24px rgba(30,64,175,.08); }
.side-card .side-head{ font-weight:800; color:var(--blue-900); margin-bottom:.4rem; }
.sticky{ position:sticky; top:84px; }

/* Timeline */
.timeline{ position:relative; padding-left:18px; }
.timeline::before{ content:""; position:absolute; left:5px; top:0; bottom:0; width:2px; background:rgba(30,64,175,.18); }
.tl-item{ position:relative; margin-bottom:16px; }
.tl-item:last-child{ margin-bottom:0; }
.tl-dot{ position:absolute; left:-1px; top:6px; width:12px; height:12px; border-radius:50%; background:var(--blue-500); box-shadow:0 0 0 4px rgba(59,130,246,.18); }
.tl-card{ margin-left:14px; background:#fff; border:1px solid rgba(30,64,175,.12); border-radius:12px; padding:12px 14px; box-shadow:0 6px 16px rgba(30,64,175,.06); }
.review-text{ white-space:pre-wrap; }

/* Avatars & list */
.avatar-badge{ width:44px; height:44px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; background:linear-gradient(180deg,#eaf2ff,#e3edff); color:#274690; font-weight:800; border:1px solid rgba(39,70,144,.15); }
.avatar-badge.sm{ width:36px; height:36px; font-size:.9rem; }
.contrib-item{ display:flex; gap:.75rem; align-items:flex-start; padding:.65rem 0; border-bottom:1px dashed rgba(30,64,175,.12); }
.contrib-item:last-child{ border-bottom:none; }

/* Buttons */
.btn{ font-weight:800; border-radius:10px; font-size:.98rem; padding:.55rem 1rem; }
.btn-primary{ background:var(--blue-600); border-color:var(--blue-600); box-shadow:0 4px 12px rgba(37,99,235,.2); }

/* PDF Preview */
.bg-blue-soft{ background:#bfdbfe; color:#1e3a8a; }
.pdf-wrap{ background:#f8fafc; border-top:1px dashed rgba(30,64,175,.16); border-bottom-left-radius:16px; border-bottom-right-radius:16px; overflow:hidden; }
.pdf-frame{ width:100%; height:var(--pdf-h,80vh); border:0; display:block; background:#fff; }
.pdf-hint{ background:#fff; }

/* Empty hint */
.empty-hint{ color:#567; background:#f6f9ff; border:1px dashed rgba(30,64,175,.18); border-radius:12px; padding:.75rem 1rem; font-weight:600; }

@media (max-width:575.98px){
  .container-xxl{ padding-inline:1rem!important; }
  .header-strip{ border-radius:14px; }
  .header-strip .title{ font-size:1.25rem; }
  .pdf-frame{ height:70vh; }
}
</style>

<script>
(() => {
  // Modal confirm reusable
  let targetFormId = null;
  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-bs-target="#confirmCancelModal"][data-form-id]');
    if (!btn) return;
    targetFormId = btn.getAttribute('data-form-id');
    const msg = btn.getAttribute('data-message') || 'Batalkan upload abstrak?';
    const textEl = document.getElementById('confirmCancelText');
    if (textEl) textEl.textContent = msg;
  });
  document.getElementById('confirmCancelBtn')?.addEventListener('click', () => {
    if (!targetFormId) return;
    const form = document.getElementById(targetFormId);
    if (form) { document.getElementById('confirmCancelBtn').setAttribute('disabled','disabled'); form.submit(); }
  });

  // Fullscreen untuk PDF
  const fsBtn = document.getElementById('pdfFullscreenBtn');
  const wrap  = document.getElementById('pdfWrap');
  fsBtn?.addEventListener('click', () => {
    if (!wrap) return;
    if (!document.fullscreenElement) { wrap.requestFullscreen?.(); }
    else { document.exitFullscreen?.(); }
  });

  // Auto-scroll ke form revisi bila ada anchor
  if (location.hash === '#section-revisi') {
    const t = document.getElementById('section-revisi');
    if (t) setTimeout(() => t.scrollIntoView({behavior:'smooth', block:'start'}), 120);
  }
})();
</script>
