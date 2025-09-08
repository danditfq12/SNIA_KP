<?php
  // $reg (id, event_title, event_date, event_time, mode_kehadiran)
  // $amount (float)
  $title   = 'Upload Bukti Pembayaran';
  $evTitle = $reg['event_title'] ?? '-';
  $evDate  = isset($reg['event_date']) ? date('d M Y', strtotime($reg['event_date'])) : '-';
  $evTime  = $reg['event_time'] ?? '-';
  $mode    = strtoupper($reg['mode_kehadiran'] ?? '-');
  $amountF = number_format((float)($amount ?? 0), 0, ',', '.');
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_audience') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <a href="<?= site_url('audience/pembayaran/instruction/'.(int)($reg['id'] ?? 0)) ?>" class="btn btn-sm btn-outline-secondary mb-3">
        <i class="bi bi-arrow-left"></i> Kembali
      </a>

      <div class="card shadow-sm border-0">
        <div class="card-body">

          <!-- HERO biru (mobile-first) -->
          <div class="pay-hero mb-3">
            <div class="pay-tags">
              <span class="pay-tag"><i class="bi bi-calendar-event"></i> <?= esc($evDate) ?></span>
              <span class="pay-tag"><i class="bi bi-clock"></i> <?= esc($evTime) ?></span>
              <span class="pay-tag"><i class="bi bi-broadcast"></i> <?= esc($mode) ?></span>
            </div>
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
              <div class="pay-title mb-0"><?= esc($evTitle) ?></div>
              <div class="text-end">
                <div class="small opacity-75">Nominal</div>
                <div class="pay-amount">Rp <?= $amountF ?></div>
              </div>
            </div>
          </div>

          <h5 class="mb-1">Upload Bukti Pembayaran</h5>
          <p class="text-muted small mb-3">Terima format <b>JPG/PNG/PDF</b> • Maks <b>5&nbsp;MB</b>. Di HP kamu bisa langsung ambil foto dari kamera.</p>

          <?php if (session('errors')): ?>
            <div class="alert alert-danger">
              <?php foreach (session('errors') as $e): ?>
                <div><?= esc($e) ?></div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>

          <!-- ALERT validasi client-side -->
          <div id="clAlert" class="alert alert-danger d-none"></div>

          <form id="payForm" action="<?= site_url('audience/pembayaran/store') ?>" method="post" enctype="multipart/form-data" novalidate>
            <?= csrf_field() ?>
            <input type="hidden" name="id_reg" value="<?= (int)($reg['id'] ?? 0) ?>">

            <!-- Dropzone tap-friendly -->
            <div id="dropArea" class="upload-box mb-3" role="button" tabindex="0" aria-label="Unggah bukti pembayaran">
              <input
                id="fileInput"
                type="file"
                name="bukti_bayar"
                class="visually-hidden"
                accept="image/*,.pdf"
                capture="environment"
                required
              >
              <div class="up-inner text-center">
                <div class="up-icon"><i class="bi bi-cloud-arrow-up"></i></div>
                <div class="fw-semibold">Ketuk untuk pilih atau tarik & lepas file</div>
                <div class="text-muted xsmall">Foto struk/transfer juga boleh</div>

                <!-- Preview -->
                <div id="fileInfo" class="mt-3 d-none">
                  <div class="d-flex align-items-center justify-content-center gap-2 flex-wrap">
                    <img id="previewImg" class="rounded d-none" alt="Preview" />
                    <i id="previewPdf" class="bi bi-file-earmark-pdf fs-3 d-none"></i>
                    <span id="fileName" class="small text-break"></span>
                    <button type="button" class="btn btn-sm btn-outline-light" id="btnClear" aria-label="Hapus file"><i class="bi bi-x"></i></button>
                  </div>
                </div>
              </div>
            </div>

            <div class="d-grid d-md-flex gap-2">
              <button id="btnSubmit" class="btn btn-primary" type="submit">
                <span class="spinner-border spinner-border-sm me-2 d-none" id="spin"></span>
                Kirim
              </button>
              <a href="<?= site_url('audience/pembayaran') ?>" class="btn btn-outline-secondary">Batal</a>
            </div>
          </form>
        </div>
      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<style>
  /* ====== Mobile-first blue theme ====== */
  .pay-hero{
    background: linear-gradient(90deg,#2563eb,#60a5fa);
    border-radius: 16px; color:#fff; padding: 14px 16px;
    box-shadow: 0 6px 20px rgba(37,99,235,.18);
  }
  .pay-tags{ display:flex; gap:.4rem; flex-wrap:wrap; margin-bottom:.4rem; }
  .pay-tag{
    background: rgba(255,255,255,.16); border:1px solid rgba(255,255,255,.22);
    color:#fff; border-radius:999px; padding:.28rem .6rem; font-size: .85rem;
    display:inline-flex; align-items:center; gap:.45rem;
  }
  .pay-title{ font-weight:700; line-height:1.25; font-size: clamp(18px, 3.8vw, 22px); }
  .pay-amount{ font-weight:800; line-height:1.1; font-size: clamp(20px, 5.2vw, 32px); }

  .upload-box{
    background:#f8fafc; border:2px dashed #bfdbfe; border-radius:14px; padding:18px;
    transition:.15s ease; position:relative; cursor:pointer;
  }
  .upload-box.drag{ background:#eff6ff; border-color:#60a5fa; box-shadow: inset 0 0 0 2px #93c5fd; }
  .up-inner .up-icon{ font-size: clamp(28px, 8vw, 40px); color:#3b82f6; margin-bottom:.25rem; }
  .xsmall{ font-size: .85rem; }

  #previewImg{
    width: clamp(40px, 12vw, 64px); height: clamp(40px, 12vw, 64px);
    object-fit: cover;
  }

  /* Spacing tweak untuk layar kecil */
  @media (min-width: 576px){
    .pay-hero{ padding: 18px 20px; border-radius:18px; }
  }
</style>

<script>
(function(){
  const dropArea  = document.getElementById('dropArea');
  const fileInput = document.getElementById('fileInput');
  const btnClear  = document.getElementById('btnClear');
  const fileInfo  = document.getElementById('fileInfo');
  const imgPrev   = document.getElementById('previewImg');
  const pdfIcon   = document.getElementById('previewPdf');
  const nameSpan  = document.getElementById('fileName');
  const alertBox  = document.getElementById('clAlert');
  const form      = document.getElementById('payForm');
  const btnSubmit = document.getElementById('btnSubmit');
  const spin      = document.getElementById('spin');

  const MAX = 5 * 1024 * 1024; // 5MB
  const ALLOWED = ['image/jpeg','image/png','application/pdf'];

  // Helpers
  const showErr = (msg)=>{ alertBox.textContent = msg; alertBox.classList.remove('d-none'); };
  const clearErr= ()=>{ alertBox.textContent=''; alertBox.classList.add('d-none'); };

  function formatBytes(b){
    if (!b && b!==0) return '';
    const u = ['B','KB','MB','GB']; const i = Math.floor(Math.log(b)/Math.log(1024));
    return (b/Math.pow(1024,i)).toFixed(1)+' '+u[i];
  }

  function renderFile(f){
    fileInfo.classList.remove('d-none');
    nameSpan.textContent = (f?.name || 'file') + ' • ' + formatBytes(f.size);
    imgPrev.classList.add('d-none'); pdfIcon.classList.add('d-none');
    if (f && f.type && f.type.startsWith('image/')){
      const url = URL.createObjectURL(f);
      imgPrev.src = url; imgPrev.classList.remove('d-none');
    } else {
      pdfIcon.classList.remove('d-none');
    }
  }

  function resetFile(){
    fileInput.value = '';
    fileInfo.classList.add('d-none');
    imgPrev.classList.add('d-none'); pdfIcon.classList.add('d-none');
    nameSpan.textContent = '';
    clearErr();
  }

  function validate(f){
    if (!f){ showErr('Silakan pilih file bukti pembayaran.'); return false; }
    if (!ALLOWED.includes(f.type)){ showErr('Tipe file tidak didukung. Gunakan JPG, PNG, atau PDF.'); return false; }
    if (f.size > MAX){ showErr('Ukuran file melebihi 5 MB.'); return false; }
    clearErr(); return true;
  }

  // Tap pada box = buka picker
  dropArea.addEventListener('click', ()=> fileInput.click());
  dropArea.addEventListener('keypress', (e)=>{ if(e.key==='Enter' || e.key===' ') { e.preventDefault(); fileInput.click(); } });

  // Drag & drop (desktop)
  ['dragenter','dragover'].forEach(ev=> dropArea.addEventListener(ev, e=>{ e.preventDefault(); dropArea.classList.add('drag'); }));
  ['dragleave','drop'].forEach(ev=> dropArea.addEventListener(ev, e=>{ e.preventDefault(); dropArea.classList.remove('drag'); }));
  dropArea.addEventListener('drop', (e)=>{
    const f = e.dataTransfer.files?.[0]; if (!f) return;
    if (!validate(f)) { resetFile(); return; }
    fileInput.files = e.dataTransfer.files; renderFile(f);
  });

  // From picker
  fileInput.addEventListener('change', (e)=>{
    const f = e.target.files?.[0]; if (!f) return;
    if (!validate(f)) { resetFile(); return; }
    renderFile(f);
  });

  // Clear
  btnClear?.addEventListener('click', resetFile);

  // Submit guard
  form.addEventListener('submit', (e)=>{
    const f = fileInput.files?.[0];
    if (!validate(f)){ e.preventDefault(); return; }
    btnSubmit.disabled = true; spin.classList.remove('d-none');
  });
})();
</script>
