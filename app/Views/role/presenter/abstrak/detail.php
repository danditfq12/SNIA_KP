<?php
/** role/presenter/abstrak/detail.php **/

/** @var array $vm */
$title = $title ?? 'Detail Abstrak';

/* Normalisasi & helper ringan */
$info = [
  'event'    => (string)($vm['event_title'] ?? '-'),
  'judul'    => (string)($vm['judul'] ?? '-'),
  'kategori' => (string)($vm['kategori'] ?? '-'),
  'status'   => (string)($vm['chip']['label'] ?? '-'),
  'diunggah' => (string)($vm['uploaded_at'] ?? '-'),
];

$hasFile      = !empty($vm['file_exists']);
$streamUrl    = (string)($vm['file_stream_url'] ?? '');
$downloadUrl  = (string)($vm['file_download_url'] ?? '');
$gdocsUrl     = (string)($vm['gdocs_viewer_url'] ?? '');

$canReupload  = !empty($vm['can_reupload']) && !empty($vm['reupload_url']);
$reuploadUrl  = (string)($vm['reupload_url'] ?? '');

$showCancel   = !empty($vm['show_cancel']) && !empty($vm['cancel_action']);
$cancelAction = (string)($vm['cancel_action'] ?? '');

$statusRaw    = (string)($vm['status'] ?? '');
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill page-wrap-blue">
    <div class="container-xxl px-3 px-md-4 py-4">

      <!-- HERO SEDERHANA -->
      <div class="card-hero mb-4">
        <div class="hero-body">
          <div class="d-flex flex-wrap align-items-start justify-content-between gap-3">
            <div>
              <h3 class="hero-title mb-0">
                <i class="bi bi-file-earmark-text me-2"></i>Detail Abstrak
              </h3>
            </div>

            <div class="d-flex gap-2 ms-auto align-items-center">
              <?php if ($canReupload): ?>
                <a class="btn btn-danger btn-sm" href="<?= esc($reuploadUrl) ?>">
                  <i class="bi bi-upload me-1"></i>Upload Ulang
                </a>
              <?php endif; ?>
              <a href="javascript:history.back()" class="btn btn-light btn-sm">
                <i class="bi bi-arrow-left"></i> Kembali
              </a>
            </div>
          </div>
        </div>
      </div>

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
        <!-- KIRI -->
        <div class="col-12 col-xl-8">

          <!-- INFORMASI -->
          <div class="card shadow-soft card-glass-plain mb-3">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
              <span class="badge bg-blue-soft"><i class="bi bi-info-circle"></i></span>
              <h6 class="mb-0 fw-semibold text-blue-900">Informasi Abstrak</h6>
            </div>
            <div class="card-body">
              <ul class="meta-list mb-0">
                <li><span>Event</span><strong class="text-blue-900"><?= esc($info['event']) ?></strong></li>
                <li><span>Judul</span><strong class="text-blue-900"><?= esc($info['judul']) ?></strong></li>
                <li><span>Kategori</span><strong class="text-blue-900"><?= esc($info['kategori']) ?></strong></li>
                <li><span>Status</span><strong class="text-blue-900"><?= esc($info['status']) ?></strong></li>
                <li><span>Diunggah</span><strong class="text-blue-900"><?= esc($info['diunggah']) ?></strong></li>
              </ul>
            </div>
          </div>

          <!-- PREVIEW PDF -->
          <div class="card shadow-soft card-glass-plain mb-3">
            <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center">
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-blue-soft"><i class="bi bi-file-earmark-pdf"></i></span>
                <h6 class="mb-0 fw-semibold text-blue-900">Pratinjau Abstrak</h6>
              </div>
              <div class="btn-group btn-group-sm">
                <?php if ($downloadUrl): ?>
                  <a class="btn btn-outline-secondary" href="<?= esc($downloadUrl) ?>" title="Unduh">
                    <i class="bi bi-download"></i>
                  </a>
                <?php endif; ?>
                <?php if ($streamUrl): ?>
                  <a class="btn btn-outline-secondary" target="_blank" rel="noopener" href="<?= esc($streamUrl) ?>" title="Buka tab baru">
                    <i class="bi bi-box-arrow-up-right"></i>
                  </a>
                <?php endif; ?>
                <button type="button" class="btn btn-outline-secondary" id="btnToggleSize" data-state="max" title="Fullscreen">
                  <i class="bi bi-arrows-fullscreen"></i>
                </button>
              </div>
            </div>
            <div class="card-body">
              <?php if ($hasFile && $streamUrl): ?>
                <div class="pdf-wrap is-min" id="pdfWrap">
                  <iframe class="pdf-frame" title="Preview PDF" id="pdfFrame"
                          src="<?= esc($streamUrl) ?>#zoom=page-width&toolbar=1&navpanes=0"></iframe>
                </div>
                <div class="small text-muted mt-2">
                  Jika pratinjau kosong, klik ikon ↗ untuk membuka di tab baru
                  <?php if ($gdocsUrl): ?>
                    atau gunakan <a target="_blank" rel="noopener" href="<?= esc($gdocsUrl) ?>">Google Docs Viewer</a>.
                  <?php endif; ?>
                </div>
              <?php else: ?>
                <div class="empty-hint"><i class="bi bi-info-circle me-1"></i> Belum ada file untuk dipratinjau.</div>
              <?php endif; ?>
            </div>
          </div>

          <!-- (Bagian FORM REVISI DIHAPUS) -->

          <!-- KOMENTAR REVIEWER -->
          <div class="card shadow-soft card-glass-plain mb-3">
            <div class="card-header bg-transparent border-0 pb-0">
              <h6 class="mb-0 fw-semibold text-blue-900"><i class="bi bi-chat-left-dots me-1"></i>Komentar Reviewer</h6>
            </div>
            <div class="card-body">
              <?php if (!empty($vm['timeline'])): ?>
                <div class="timeline">
                  <?php foreach ($vm['timeline'] as $t): ?>
                    <div class="tl-item">
                      <div class="tl-dot"></div>
                      <div class="tl-card">
                        <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-2">
                          <div class="d-flex flex-wrap align-items-center gap-2">
                            <span class="badge rounded-pill bg-light text-blue-900 border">
                              Tahap: <b class="ms-1"><?= esc($t['revisi_ke'] ?? '—') ?></b>
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
                <div class="empty-hint"><i class="bi bi-info-circle me-1"></i>Tidak ada komentar tersimpan.</div>
              <?php endif; ?>
            </div>
          </div>
        </div>

        <!-- KANAN -->
        <div class="col-12 col-xl-4">
          <!-- STATUS -->
          <div class="card shadow-soft card-glass-plain mb-3">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
              <span class="badge bg-blue-soft"><i class="bi bi-flag"></i></span>
              <h6 class="mb-0 fw-semibold text-blue-900">Status</h6>
            </div>
            <div class="card-body">
              <?php if (in_array($statusRaw, ['menunggu','sedang_direview'], true)): ?>
                <div class="callout callout-info"><i class="bi bi-hourglass-split me-1"></i>Abstrak Anda sedang diproses oleh reviewer.</div>
              <?php elseif ($statusRaw === 'revisi'): ?>
                <!-- Tidak ada form revisi lagi, hanya informasi -->
                <div class="callout callout-warn"><i class="bi bi-arrow-repeat me-1"></i>Abstrak memerlukan penyesuaian. Silakan menunggu arahan/pengumuman panitia.</div>
              <?php elseif ($statusRaw === 'ditolak'): ?>
                <div class="callout callout-danger"><i class="bi bi-x-octagon me-1"></i>Abstrak ditolak.</div>
              <?php elseif ($statusRaw === 'diterima'): ?>
                <div class="callout callout-success"><i class="bi bi-check2-circle me-1"></i>Abstrak diterima.</div>
              <?php else: ?>
                <div class="callout callout-info"><i class="bi bi-info-circle me-1"></i>—</div>
              <?php endif; ?>

              <?php if ($showCancel): ?>
                <hr class="my-3">
                <form id="cancelForm" action="<?= esc($cancelAction) ?>" method="POST" class="d-none">
                  <?= csrf_field() ?>
                </form>
                <button type="button" class="btn btn-outline-danger btn-sm js-cancel-abs w-100">
                  <i class="bi bi-x-circle me-1"></i>Batalkan Upload
                </button>
              <?php endif; ?>
            </div>
          </div>

          <!-- REVIEWER -->
          <div class="card shadow-soft card-glass-plain mb-3">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
              <span class="badge bg-blue-soft"><i class="bi bi-person-badge"></i></span>
              <h6 class="mb-0 fw-semibold text-blue-900">Reviewer Ditugaskan</h6>
            </div>
            <div class="card-body">
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

          <!-- KONTRIBUTOR -->
          <?php if (!empty($vm['contributors'])): ?>
            <div class="card shadow-soft card-glass-plain mb-3">
              <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
                <span class="badge bg-blue-soft"><i class="bi bi-people"></i></span>
                <h6 class="mb-0 fw-semibold text-blue-900">Kontributor</h6>
              </div>
              <div class="card-body">
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

    <!-- Fallback modal (kalau SweetAlert tidak ada) -->
    <div class="modal fade" id="confirmCancelModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0">
          <div class="modal-header bg-light">
            <h6 class="modal-title"><i class="bi bi-exclamation-triangle me-2 text-danger"></i>Konfirmasi</h6>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
          </div>
          <div class="modal-body">
            <p class="mb-0">Batalkan upload abstrak?</p>
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

<!-- (opsional) SweetAlert2 via CDN -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<style>
:root{
  --blue-50:#eff6ff; --blue-100:#dbeafe; --blue-200:#bfdbfe; --blue-300:#93c5fd; --blue-400:#60a5fa;
  --blue-500:#3b82f6; --blue-600:#2563eb; --blue-700:#1d4ed8; --blue-800:#1e40af; --blue-900:#1e3a8a;
  --muted:#6b7280; --side-pad: clamp(1rem, 2.3vw, 2.2rem);
}
.container-xxl{ max-width:min(100%, 1560px); padding-inline:var(--side-pad)!important; margin-inline:auto; }
body{ font-family: ui-sans-serif,system-ui,-apple-system,"Segoe UI",Roboto,"Helvetica Neue",Arial,"Noto Sans",sans-serif; font-size:15.5px; line-height:1.6; }
.page-wrap-blue{ min-height:100vh; padding-top:72px; background:
  radial-gradient(900px 300px at 20% -10%, rgba(59,130,246,.17), rgba(59,130,246,0) 60%),
  radial-gradient(900px 300px at 80% 110%, rgba(59,130,246,.14), rgba(59,130,246,0) 70%),
  linear-gradient(180deg, var(--blue-50), #fff 40%); }

.card-hero{ border:0; border-radius:16px; overflow:hidden; box-shadow:0 12px 28px rgba(30,64,175,.10); }
.card-hero .hero-body{ background:linear-gradient(135deg,var(--blue-700),var(--blue-800)); color:#fff; padding:1.4rem 1.1rem; }
.hero-title{ font-weight:800; letter-spacing:.2px; font-size:1.25rem; }

.card-glass-plain{ background:#fff; border-radius:14px; border:1px solid rgba(30,64,175,.10); }
.shadow-soft{ box-shadow:0 10px 24px rgba(30,64,175,.08); }

.meta-list{ list-style:none; padding-left:0; margin:0; }
.meta-list li{ display:flex; align-items:center; justify-content:space-between; gap:.75rem; padding:.45rem 0; }
.meta-list li span{ color:var(--muted); }
.text-blue-900{ color:var(--blue-900)!important; }
.bg-blue-soft{ background:var(--blue-200); color:var(--blue-800); border-radius:10px; padding:.38rem .6rem; font-weight:600; font-size:.9rem; }

.callout{ border:1px dashed rgba(30,64,175,.18); border-radius:12px; padding:.7rem .9rem; font-weight:600; }
.callout-info{ background:#eef6ff; color:#124d9b; }
.callout-success{ background:#ecfdf5; color:#065f46; }
.callout-danger{ background:#fef2f2; color:#9f1239; }
.callout-warn{ background:#fff7ed; color:#92400e; }

.avatar-badge{ width:44px; height:44px; border-radius:50%; display:inline-flex; align-items:center; justify-content:center; background:linear-gradient(180deg,#eaf2ff,#e3edff); color:#274690; font-weight:800; border:1px solid rgba(39,70,144,.15); }
.avatar-badge.sm{ width:36px; height:36px; font-size:.9rem; }
.contrib-item{ display:flex; gap:.75rem; align-items:flex-start; padding:.65rem 0; border-bottom:1px dashed rgba(30,64,175,.12); }
.contrib-item:last-child{ border-bottom:none; }

.btn{ font-weight:800; border-radius:10px; font-size:.95rem; padding:.54rem .96rem; }
.btn-primary{ background:var(--blue-600); border-color:var(--blue-600); box-shadow:0 4px 12px rgba(37,99,235,.2); }

.pdf-wrap{ border:1px solid #e5e7eb; border-radius:10px; overflow:hidden; background:#f8fafc; transition:height .2s ease; }
.pdf-wrap.is-min{ height:52vh; }
.pdf-wrap.is-max{ height:82vh; }
.pdf-frame{ width:100%; height:100%; border:0; }

.empty-hint{ color:#567; background:#f6f9ff; border:1px dashed rgba(30,64,175,.18); border-radius:12px; padding:.8rem 1rem; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function(){
  // Toggle preview size
  const wrap = document.getElementById('pdfWrap');
  const btn  = document.getElementById('btnToggleSize');
  if (wrap && btn) {
    wrap.classList.add('is-min');
    btn.addEventListener('click', function(){
      const toMax = btn.dataset.state === 'max';
      wrap.classList.toggle('is-min', !toMax);
      wrap.classList.toggle('is-max',  toMax);
      btn.dataset.state = toMax ? 'min' : 'max';
      btn.innerHTML = toMax ? '<i class="bi bi-arrows-angle-contract"></i>'
                            : '<i class="bi bi-arrows-fullscreen"></i>';
      btn.title = toMax ? 'Minimize' : 'Fullscreen';
    });
  }

  // Batalkan upload (SweetAlert jika tersedia)
  const cancelBtn = document.querySelector('.js-cancel-abs');
  if (cancelBtn) {
    cancelBtn.addEventListener('click', function(){
      const form = document.getElementById('cancelForm');
      if (window.Swal) {
        Swal.fire({
          title: 'Batalkan upload abstrak?',
          text: 'File dan data unggahan akan dihapus.',
          icon: 'warning',
          showCancelButton: true,
          confirmButtonText: 'Ya, batalkan',
          cancelButtonText: 'Batal',
          confirmButtonColor: '#dc2626',
          reverseButtons: true,
          focusCancel: true
        }).then((res) => { if (res.isConfirmed && form) form.submit(); });
      } else {
        if (confirm('Batalkan upload abstrak? File dan data unggahan akan dihapus.') && form) form.submit();
      }
    });
  }

  // Fallback modal handler (jika memakai modal bootstrap)
  document.getElementById('confirmCancelBtn')?.addEventListener('click', () => {
    document.getElementById('cancelForm')?.submit();
  });
});
</script>
