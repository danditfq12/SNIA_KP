<?php
$title  = $title ?? 'Detail Abstrak';
$abs    = $abs ?? [];
$event  = $event ?? [];
$status = strtolower($abs['status'] ?? 'menunggu');

$badge = [
  'menunggu'        => 'warning',
  'sedang_direview' => 'info',
  'diterima'        => 'success',
  'ditolak'         => 'danger',
][$status] ?? 'secondary';

$showReuploadAbstract = ($status === 'ditolak');
$showCancel = ($status === 'menunggu');
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill page-wrap-blue">
    <div class="container-xxl px-3 px-md-4 py-4">

      <div class="hero-blue card-glass mb-3 p-3 p-md-4 d-flex justify-content-between align-items-start gap-3">
        <div>
          <h3 class="hero-title mb-1"><i class="bi bi-journal-text me-2"></i>Detail Abstrak</h3>
          <div class="text-white-75 small"><?= esc($event['title'] ?? '-') ?></div>
        </div>
        <div>
          <span class="badge bg-<?= $badge ?> fs-6"><?= ucfirst($status) ?></span>
        </div>
      </div>

      <div class="row g-3">
        <div class="col-12 col-lg-8">
          <div class="card shadow-soft card-glass-plain mb-3">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center justify-content-between">
              <div class="d-flex align-items-center gap-2">
                <span class="badge bg-blue-soft"><i class="bi bi-info-circle"></i></span>
                <h6 class="mb-0 fw-semibold text-blue-900">Informasi Abstrak</h6>
              </div>
              <a href="javascript:history.back()" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Kembali
              </a>
            </div>
            <div class="card-body">
              <div class="mb-3">
                <div class="text-muted small">Judul</div>
                <div class="fw-semibold text-blue-900"><?= esc($abs['judul'] ?? '-') ?></div>
              </div>
              <div class="mb-3">
                <div class="text-muted small">Kategori</div>
                <div class="fw-semibold text-blue-900"><?= esc($abs['nama_kategori'] ?? '-') ?></div>
              </div>
              <div class="mb-1">
                <div class="text-muted small">Tanggal Unggah</div>
                <div class="fw-semibold text-blue-900"><?= !empty($abs['tanggal_upload']) ? date('d M Y H:i', strtotime($abs['tanggal_upload'])) : '-' ?></div>
              </div>

              <div class="d-flex flex-wrap gap-2 mt-3">
                <?php if (!empty($abs['file_abstrak'])): ?>
                  <a class="btn btn-outline-secondary" href="<?= site_url('/presenter/abstrak/download/'.esc($abs['file_abstrak'])) ?>">
                    <i class="bi bi-download"></i> Unduh File
                  </a>
                <?php endif; ?>

                <?php if ($showReuploadAbstract): ?>
                  <a class="btn btn-danger" href="<?= site_url('/presenter/abstrak/create/'.(int)$abs['event_id']) ?>">
                    <i class="bi bi-upload"></i> Upload Ulang Abstrak
                  </a>
                <?php endif; ?>

                <?php if ($showCancel): ?>
                  <form id="cancelForm-<?= (int)$abs['id_abstrak'] ?>"
                        action="<?= site_url('/presenter/abstrak/cancel/'.(int)$abs['id_abstrak']) ?>"
                        method="POST" class="m-0">
                    <?= csrf_field() ?>
                    <button type="button"
                            class="btn btn-outline-danger"
                            data-bs-toggle="modal"
                            data-bs-target="#confirmCancelModal"
                            data-form-id="cancelForm-<?= (int)$abs['id_abstrak'] ?>"
                            data-message="Batalkan upload abstrak ini?">
                      <i class="bi bi-x-circle"></i> Batalkan Upload
                    </button>
                  </form>
                <?php endif; ?>
              </div>
            </div>
          </div>

          <div class="card shadow-soft card-glass-plain">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
              <span class="badge bg-blue-soft"><i class="bi bi-calendar-event"></i></span>
              <h6 class="mb-0 fw-semibold text-blue-900">Info Event</h6>
            </div>
            <div class="card-body">
              <div class="row g-3">
                <div class="col-6">
                  <div class="text-muted small">Tanggal Event</div>
                  <div class="fw-semibold text-blue-900">
                    <?= isset($event['event_date']) ? date('d M Y', strtotime($event['event_date'])) : '-' ?>
                  </div>
                </div>
                <div class="col-6">
                  <div class="text-muted small">Deadline Abstrak</div>
                  <div class="fw-semibold text-blue-900">
                    <?= !empty($event['abstract_deadline']) ? date('d M Y H:i', strtotime($event['abstract_deadline'])) : '-' ?>
                  </div>
                </div>
                <div class="col-6">
                  <div class="text-muted small">Deadline Full Paper</div>
                  <div class="fw-semibold text-blue-900">
                    <?= !empty($event['full_paper_deadline']) ? date('d M Y H:i', strtotime($event['full_paper_deadline'])) : '-' ?>
                  </div>
                </div>
              </div>
            </div>
          </div>

        </div>

        <div class="col-12 col-lg-4">
          <div class="card shadow-soft card-glass-plain">
            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
              <span class="badge bg-blue-soft"><i class="bi bi-flag"></i></span>
              <h6 class="mb-0 fw-semibold text-blue-900">Status</h6>
            </div>
            <div class="card-body">
              <?php if ($status === 'menunggu' || $status === 'sedang_direview'): ?>
                <div class="alert alert-info mb-0 bg-blue-soft-2 text-blue-900 border-0">
                  Abstrak Anda sedang diproses oleh reviewer.
                </div>
              <?php elseif ($status === 'ditolak'): ?>
                <div class="alert alert-danger mb-0">Abstrak ditolak. Silakan unggah ulang sesuai catatan revisi dari reviewer.</div>
              <?php elseif ($status === 'diterima'): ?>
                <div class="alert alert-success mb-0">Abstrak diterima.</div>
              <?php endif; ?>
            </div>
          </div>
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
    --blue-50:#eff6ff; --blue-100:#dbeafe; --blue-200:#bfdbfe;
    --blue-300:#93c5fd; --blue-400:#60a5fa; --blue-500:#3b82f6;
    --blue-600:#2563eb; --blue-700:#1d4ed8; --blue-800:#1e40af; --blue-900:#1e3a8a;
  }
  .page-wrap-blue{ background: linear-gradient(180deg, var(--blue-50), #fff 40%); min-height:100vh; padding-top:70px; }
  .hero-blue{
    background: radial-gradient(1200px 400px at 10% -20%, var(--blue-600) 0, var(--blue-700) 40%, var(--blue-800) 100%);
    color:#fff; border-radius:18px; border:1px solid rgba(255,255,255,.15);
    box-shadow: 0 10px 30px rgba(29,78,216,.25), inset 0 0 40px rgba(255,255,255,.06);
  }
  .hero-title{ font-weight:800; letter-spacing:.2px; }
  .text-white-75{ color:rgba(255,255,255,.85)!important; }

  .card-glass-plain{ backdrop-filter: blur(3px); background: rgba(255,255,255,.72); border-radius:16px; border:1px solid rgba(30,64,175,.06); }
  .shadow-soft{ box-shadow: 0 10px 24px rgba(30,64,175,.06); }
  .bg-blue-soft{ background: var(--blue-100); color: var(--blue-800); border-radius:12px; padding:.35rem .6rem; }
  .bg-blue-soft-2{ background: var(--blue-50); }
  .text-blue-900{ color: var(--blue-900)!important; }

  @media (max-width: 767.98px){
    .hero-blue{ border-radius:14px; }
    .container-xxl{ padding-left:.9rem; padding-right:.9rem; }
  }
</style>

<script>
(() => {
  // modal submit glue (reusable)
  let targetFormId = null;

  document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-bs-target="#confirmCancelModal"][data-form-id]');
    if (!btn) return;
    targetFormId = btn.getAttribute('data-form-id');
    const msg = btn.getAttribute('data-message') || 'Batalkan upload abstrak?';
    const textEl = document.getElementById('confirmCancelText');
    if (textEl) textEl.textContent = msg;
  });

  const confirmBtn = document.getElementById('confirmCancelBtn');
  confirmBtn?.addEventListener('click', () => {
    if (!targetFormId) return;
    const form = document.getElementById(targetFormId);
    if (form) {
      confirmBtn.setAttribute('disabled','disabled');
      form.submit();
    }
  });
})();
</script>
