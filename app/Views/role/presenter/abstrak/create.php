<?php
$title        = $title        ?? 'Kirim Abstrak';
$event        = $event        ?? [];
$eventId      = isset($eventId) ? (int)$eventId : (int)($event['id'] ?? 0);
$kategoriList = $kategoriList ?? [];

$oldKategori = old('id_kategori');
$oldJudul    = old('judul');

$fmtDate = fn($s) => $s ? date('d M Y', strtotime($s)) : '-';
$fmtDT   = fn($s) => $s ? date('d M Y H:i', strtotime($s)) : '-';
$formatLabel = function($f){
  $f = strtolower((string)$f);
  return $f === 'both' ? 'Hybrid' : ucfirst($f ?: '-');
};

$showFpCta      = $showFpCta ?? false;
$lastUploadedId = (int)($lastUploadedId ?? 0);
$formLocked     = (bool)($formLocked ?? false);
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill page-wrap-blue">
    <div class="container-xxl px-3 px-md-4 py-4">
      <div class="row g-3">
        <!-- Form (full width) -->
        <div class="col-12">

          <?php if ($showFpCta): ?>
            <div class="alert alert-success card-glow d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
              <div class="me-2">
                <div class="fw-semibold">Abstrak berhasil diunggah.</div>
                <div class="small mb-0">
                  Silakan <strong>menunggu konfirmasi reviewer</strong>.
                  <?php if (!empty($event['full_paper_deadline'])): ?>
                    Anda juga bisa lanjut ke <strong>Full Paper</strong> sekarang.
                  <?php endif; ?>
                </div>
              </div>
              <div class="d-flex gap-2">
                <a href="<?= site_url('/presenter/fullpaper/create/'.(int)$eventId) ?>" class="btn btn-success">
                  <i class="bi bi-arrow-right-circle me-1"></i> Lanjut ke Full Paper
                </a>
                <?php if ($lastUploadedId > 0): ?>
                  <form id="cancelForm-<?= (int)$lastUploadedId ?>"
                        action="<?= site_url('/presenter/abstrak/cancel/'.(int)$lastUploadedId) ?>"
                        method="POST" class="m-0">
                    <?= csrf_field() ?>
                    <button type="button"
                            class="btn btn-outline-danger"
                            data-bs-toggle="modal"
                            data-bs-target="#confirmCancelModal"
                            data-form-id="cancelForm-<?= (int)$lastUploadedId ?>"
                            data-message="Batalkan upload abstrak terakhir?">
                      <i class="bi bi-x-circle me-1"></i> Batalkan Upload
                    </button>
                  </form>
                <?php endif; ?>
              </div>
            </div>
          <?php endif; ?>

          <div class="card shadow-soft card-glass-plain position-relative">
            <?php if ($formLocked): ?>
              <div class="form-lock-overlay d-flex align-items-center justify-content-center">
                <div class="text-center px-3">
                  <div class="mb-2 fw-semibold text-blue-900">Form Dikunci</div>
                  <div class="small text-muted">Tidak bisa upload lagi sampai unggahan terakhir dibatalkan.</div>
                </div>
              </div>
            <?php endif; ?>

            <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
              <span class="badge bg-blue-soft"><i class="bi bi-upload"></i></span>
              <h6 class="mb-0 fw-semibold text-blue-900">Form Abstrak</h6>
            </div>
            <div class="card-body">
              <form action="<?= site_url('/presenter/abstrak/store') ?>" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                <?= csrf_field() ?>
                <input type="hidden" name="event_id" value="<?= (int)$eventId ?>">

                <!-- Kategori (dropdown scrollable) -->
                <div class="mb-3">
                  <label class="form-label">Kategori <span class="text-danger">*</span></label>

                  <!-- Hidden value tetap dipakai untuk submit -->
                  <input type="hidden" name="id_kategori" id="kategoriValue" value="<?= esc($oldKategori) ?>">

                  <div class="dropdown w-100">
                    <button
                      class="btn btn-light border w-100 d-flex justify-content-between align-items-center form-control-soft"
                      type="button"
                      id="kategoriDropdown"
                      data-bs-toggle="dropdown"
                      aria-expanded="false"
                      <?= $formLocked ? 'disabled' : '' ?>
                      style="height:44px; text-align:left;"
                    >
                      <span id="kategoriLabel" class="text-truncate">
                        <?= $oldKategori ? esc(array_values(array_filter($kategoriList, fn($k)=> (string)$k['id_kategori']===(string)$oldKategori))[0]['nama_kategori'] ?? 'Pilih Kategori') : '— Pilih Kategori —' ?>
                      </span>
                      <i class="bi bi-chevron-down ms-2"></i>
                    </button>
                    <ul class="dropdown-menu w-100 shadow" aria-labelledby="kategoriDropdown"
                        style="max-height: 260px; overflow-y: auto;">
                      <li>
                        <button class="dropdown-item" type="button" data-value="">
                          — Pilih Kategori —
                        </button>
                      </li>
                      <?php foreach ($kategoriList as $k): ?>
                        <li>
                          <button class="dropdown-item" type="button" data-value="<?= (int)$k['id_kategori'] ?>">
                            <?= esc($k['nama_kategori']) ?>
                          </button>
                        </li>
                      <?php endforeach; ?>
                    </ul>
                  </div>
                </div>

                <!-- Judul -->
                <div class="mb-3">
                  <div class="d-flex justify-content-between align-items-center">
                    <label class="form-label mb-0">Judul Abstrak <span class="text-danger">*</span></label>
                    <small class="text-muted"><span id="titleCount">0</span>/200</small>
                  </div>
                  <input
                    type="text"
                    class="form-control form-control-soft"
                    name="judul"
                    id="judulInput"
                    maxlength="200"
                    value="<?= esc($oldJudul) ?>"
                    placeholder="Tulis judul abstrak (maks. 200 karakter)"
                    <?= $formLocked ? 'disabled' : '' ?>
                    required
                  >
                  <div class="invalid-feedback">Judul wajib diisi.</div>
                </div>

                <!-- File -->
                <div class="mb-3">
                  <label class="form-label">File Abstrak (PDF) <span class="text-danger">*</span></label>
                  <input type="file" class="form-control form-control-soft" name="file_abstrak" accept=".pdf,application/pdf" <?= $formLocked ? 'disabled' : '' ?> required>
                  <div class="form-text">Format PDF, maksimal 5MB.</div>
                  <div class="invalid-feedback">File PDF wajib diunggah.</div>
                </div>

                <!-- Actions -->
                <div class="d-flex flex-wrap gap-2">
                  <a href="<?= site_url('/presenter/kontributor/start/'.(int)$eventId) ?>" class="btn btn-light border">
                    <i class="bi bi-arrow-left"></i> Kembali (Kontributor)
                  </a>

                  <?php if ($formLocked): ?>
                    <button type="button" class="btn btn-primary" disabled>
                      <i class="bi bi-send me-1"></i>Kirim Abstrak
                    </button>
                  <?php else: ?>
                    <button type="submit" class="btn btn-primary">
                      <i class="bi bi-send me-1"></i>Kirim Abstrak
                    </button>
                  <?php endif; ?>
                </div>
              </form>
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
            <small class="text-muted">Tindakan ini akan menghapus file dan data unggahan terakhir Anda untuk event ini.</small>
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

  --side-pad: clamp(1rem, 2.3vw, 2.2rem);
  --gutter-x: 1rem;
}

body{
  font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
  font-size:15.5px;
  line-height:1.6;
  color:#0f172a;
}

/* ===== Layout wrapper ===== */
.page-wrap-blue{
  min-height:100vh; padding-top:72px;
  background:
    radial-gradient(1000px 380px at 10% -10%, rgba(59,130,246,.16), rgba(59,130,246,0) 60%),
    radial-gradient(1000px 380px at 90% 110%, rgba(59,130,246,.12), rgba(59,130,246,0) 70%),
    linear-gradient(180deg, var(--blue-50), #fff 40%);
}

/* ===== Container ===== */
.container-xxl{
  max-width: min(100%, 1560px);
  padding-left: var(--side-pad) !important;
  padding-right: var(--side-pad) !important;
  margin-inline:auto;
}

/* ===== Grid gutter ===== */
.row.g-3, .row.g-4{ --bs-gutter-x: var(--gutter-x); --bs-gutter-y: var(--gutter-x); }

/* ===== HERO ===== */
.hero-blue{
  background:radial-gradient(1200px 400px at 10% -20%,var(--blue-600) 0,var(--blue-700) 40%,var(--blue-800) 100%) !important;
  color:#fff !important;
  border-radius:16px;
  border:1px solid rgba(255,255,255,.15);
  box-shadow:0 12px 28px rgba(30,64,175,.10);
  padding:1.6rem !important;
  margin-bottom:1.25rem !important;
}
.hero-blue.card-glass,
.hero-blue.card-glass-plain{ backdrop-filter:none !important; }
.hero-title{ font-weight:800; letter-spacing:.25px; font-size:1.45rem; }
.text-white-75{ color:rgba(255,255,255,.85)!important; }

/* ===== Card / Glass ===== */
.card-glass{
  backdrop-filter: blur(2px);
  background: linear-gradient(180deg, rgba(255,255,255,.18), rgba(255,255,255,.10));
  border-radius:16px; border:1px solid rgba(255,255,255,.28);
}
.card-glass-plain{
  backdrop-filter:blur(6px);
  background:rgba(255,255,255,.96);
  border-radius:14px;
  border:1px solid rgba(30,64,175,.10);
}
.shadow-soft{ box-shadow:0 10px 24px rgba(30,64,175,.08); }

.card-header{ padding:1rem 1rem .45rem 1rem !important; }
.card-body{   padding:1.05rem !important; }

/* ===== Badge & text ===== */
.bg-blue-soft{ background:var(--blue-200); color:var(--blue-800); border-radius:12px; padding:.4rem .6rem; font-weight:700; font-size:.85rem; }
.text-blue-900{ color:var(--blue-900)!important; }
.bg-blue-soft-2{ background:#eef6ff; }

/* ===== Forms ===== */
.form-label{ font-weight:800; color:#334155; }
.form-control{ border-radius:10px; font-size:.98rem; }
.form-select{ border-radius:10px; }
.form-control-soft{
  border:1px solid rgba(2,6,23,.12);
  background:#fff;
  height:44px;
}
.form-control:focus, .form-select:focus{
  border-color: var(--blue-400) !important;
  box-shadow:0 0 0 .2rem rgba(59,130,246,.12) !important;
}

/* ===== Buttons ===== */
.btn{ font-weight:800; letter-spacing:.2px; border-radius:10px; }
.btn-sm{ padding:.38rem .75rem; font-size:.84rem; }
.btn-primary{ background:var(--blue-600); border-color:var(--blue-600); box-shadow:0 4px 12px rgba(37,99,235,.2); }
.btn-success{ background:#059669; border-color:#059669; box-shadow:0 4px 12px rgba(5,150,105,.2); }
.btn-outline-primary{ color:var(--blue-700); border-color:var(--blue-300); }
.btn-outline-primary:hover{ background:var(--blue-600); color:#fff; border-color:var(--blue-600); }

/* ===== Alert special (sukses glow) ===== */
.card-glow{ box-shadow:0 8px 24px rgba(16,185,129,.18); border:1px solid rgba(16,185,129,.2); }

/* ===== Overlay lock ===== */
.form-lock-overlay{
  position:absolute; inset:0;
  background: rgba(255,255,255,.65);
  border-radius:16px;
  border:1px dashed rgba(30,64,175,.25);
  z-index: 2; text-align:center;
  backdrop-filter: blur(2px);
  pointer-events: all;
}

/* ===== Table ===== */
.table{ font-size:.95rem; margin-bottom:0; }
.table thead th{
  background:#f8fbff!important;
  border-bottom:1px solid rgba(30,64,175,.18);
  font-weight:800; color:#1e3a8a;
  font-size:.84rem; text-transform:uppercase; letter-spacing:.4px;
  padding:.7rem .9rem;
}
.table tbody tr{ border-bottom:1px solid rgba(30,64,175,.08); }
.table tbody td{ padding:.8rem 1rem; vertical-align:middle; border-top:none; }

/* ===== Responsive ===== */
@media (max-width:575.98px){
  .container-xxl{ padding-left: calc(var(--side-pad) - .25rem) !important; padding-right: calc(var(--side-pad) - .25rem) !important; }
  .hero-blue{ border-radius:14px; padding:1.25rem!important; margin-bottom:1rem!important; }
  .hero-title{ font-size:1.25rem; }
  .card-body{  padding:.9rem!important; }
  .card-header{ padding:.9rem .9rem .4rem .9rem!important; }
  .btn{ font-size:.94rem; }
}
@media (min-width:576px) and (max-width:767.98px){
  .container-xxl{ padding-left: var(--side-pad) !important; padding-right: var(--side-pad) !important; }
}
</style>

<script>
(() => {
  'use strict';
  // Modal konfirmasi batalkan
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

  // Validasi form + counter judul
  const forms = document.querySelectorAll('.needs-validation');
  Array.from(forms).forEach(form => {
    form.addEventListener('submit', e => {
      // validasi kategori (karena hidden input tidak bisa required native)
      const katVal = document.getElementById('kategoriValue')?.value || '';
      const katInvalid = document.getElementById('kategoriInvalid');
      if (!katVal) {
        katInvalid.style.display = 'block';
        e.preventDefault(); e.stopPropagation();
      } else {
        katInvalid.style.display = 'none';
      }

      if (!form.checkValidity()) { e.preventDefault(); e.stopPropagation(); }
      form.classList.add('was-validated');
    }, false);
  });
  const titleInput = document.getElementById('judulInput');
  const titleCount = document.getElementById('titleCount');
  const updateCount = () => { titleCount.textContent = (titleInput?.value || '').length; };
  if (titleInput) { updateCount(); titleInput.addEventListener('input', updateCount); }

  // Dropdown kategori: set value + label
  const dropdown = document.querySelector('#kategoriDropdown');
  const list = document.querySelectorAll('.dropdown-menu [data-value]');
  const label = document.getElementById('kategoriLabel');
  const hidden = document.getElementById('kategoriValue');
  const katInvalid = document.getElementById('kategoriInvalid');

  list.forEach(item => {
    item.addEventListener('click', () => {
      const val = item.getAttribute('data-value') || '';
      const text = item.textContent.trim();
      hidden.value = val;
      label.textContent = text || '— Pilih Kategori —';
      katInvalid.style.display = val ? 'none' : 'block';
    });
  });
})();
</script>
