<?php
// =========================================
//  Pembayaran - Detail (Audience)
//  - Download bukti selalu tersedia jika ada file
//  - "Ubah Bukti" hanya untuk pending/rejected
// =========================================
$title = $title ?? 'Detail Pembayaran';
$pay   = $pay   ?? [];
$event = $event ?? [];

$amount   = (float)($pay['jumlah'] ?? 0);
$status   = (string)($pay['status'] ?? '-');
$tanggal  = $pay['tanggal_bayar'] ?? null;

// kolom baru + fallback legacy
$proof    = $pay['bukti_bayar'] ?? ($pay['bukti'] ?? null);
$payId    = (int)($pay['id_pembayaran'] ?? 0);
$proofExt = strtolower(pathinfo((string)$proof, PATHINFO_EXTENSION));

$badge = 'secondary';
switch ($status) {
  case 'pending':   $badge = 'warning';  break;
  case 'verified':  $badge = 'success';  break;
  case 'rejected':  $badge = 'danger';   break;
  case 'canceled':  $badge = 'secondary';break;
}

$evTitle = $event['title'] ?? 'Event';
$evDate  = isset($event['event_date']) ? date('d M Y', strtotime($event['event_date'])) : '-';
$evTime  = $event['event_time'] ?? '-';

$canReupload = in_array($status, ['pending','rejected'], true);
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_audience') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <a href="<?= site_url('audience/pembayaran') ?>" class="btn btn-sm btn-outline-secondary mb-3">
        <i class="bi bi-arrow-left"></i> Kembali
      </a>

      <?php if (session('message')): ?>
        <div class="alert alert-success"><?= esc(session('message')) ?></div>
      <?php endif; ?>
      <?php if (session('error')): ?>
        <div class="alert alert-danger"><?= esc(session('error')) ?></div>
      <?php endif; ?>

      <div class="card shadow-sm border-0">
        <div class="card-body">

          <!-- HERO biru -->
          <div class="pay-hero mb-3">
            <div class="d-flex flex-column flex-md-row align-items-md-start justify-content-between gap-2">
              <div>
                <div class="pay-title mb-1"><?= esc($evTitle) ?></div>
                <div class="pay-tags">
                  <span class="pay-tag"><i class="bi bi-calendar-event"></i> <?= esc($evDate) ?></span>
                  <span class="pay-tag"><i class="bi bi-clock"></i> <?= esc($evTime) ?></span>
                </div>
              </div>
              <div class="text-md-end">
                <div class="small opacity-75">Status</div>
                <span class="badge text-uppercase bg-<?= $badge ?>"><?= esc($status) ?></span>
              </div>
            </div>
          </div>

          <div class="row g-3">
            <!-- Info jumlah & tanggal -->
            <div class="col-12 col-md-6">
              <div class="border rounded-3 p-3 h-100">
                <div class="text-muted small">Jumlah</div>
                <div class="fs-5 fw-semibold">Rp <?= number_format($amount, 0, ',', '.') ?></div>

                <div class="text-muted small mt-3">Tanggal</div>
                <div><?= $tanggal ? esc(date('d M Y H:i', strtotime($tanggal))) : '-' ?></div>

                <?php if ($status === 'pending'): ?>
                  <div class="alert alert-warning mt-3 mb-0">
                    Menunggu verifikasi panitia.
                  </div>
                <?php elseif ($status === 'rejected'): ?>
                  <div class="alert alert-danger mt-3 mb-0">
                    Bukti pembayaran <b>ditolak</b>. Unggah ulang bukti yang benar di panel kanan.
                  </div>
                <?php endif; ?>
              </div>
            </div>

            <!-- Bukti & Reupload -->
            <div class="col-12 col-md-6">
              <div class="border rounded-3 p-3 h-100">
                <div class="d-flex justify-content-between align-items-center mb-2">
                  <div class="text-muted small">Bukti Pembayaran</div>

                  <?php if ($canReupload): ?>
                    <a class="btn btn-sm btn-outline-primary" href="#unggah-ulang" id="btnShowReupload">
                      Ubah Bukti
                    </a>
                  <?php endif; ?>
                </div>

                <?php if ($proof): ?>
                  <div id="proofViewer" class="proof-viewer border rounded-3 bg-light d-flex align-items-center justify-content-center" style="min-height:220px;">
                    <div class="text-muted small">Memuat bukti…</div>
                  </div>
                  <div class="mt-2 d-flex flex-wrap gap-2">
                    <a class="btn btn-sm btn-outline-secondary"
                       href="<?= site_url('audience/pembayaran/download-bukti/'.$payId) ?>">
                      Unduh Bukti
                    </a>
                    <?php if ($canReupload): ?>
                      <a class="btn btn-sm btn-outline-primary" href="#unggah-ulang" id="btnShowReuploadTop">Ubah Bukti</a>
                    <?php endif; ?>
                  </div>

                  <script>
                    (function(){
                      const viewer = document.getElementById('proofViewer');
                      const url = "<?= site_url('audience/pembayaran/download-bukti/'.$payId) ?>";
                      const ext = "<?= $proofExt ?>";
                      fetch(url, {credentials: 'same-origin'})
                        .then(r => r.blob())
                        .then(blob => {
                          const blobUrl = URL.createObjectURL(blob);
                          let el;
                          if (['jpg','jpeg','png','gif','webp','bmp'].includes(ext)) {
                            el = document.createElement('img');
                            el.src = blobUrl;
                            el.alt = 'Bukti pembayaran';
                            el.className = 'img-fluid rounded';
                            el.style.maxHeight = '520px';
                          } else if (ext === 'pdf') {
                            el = document.createElement('iframe');
                            el.src = blobUrl;
                            el.className = 'w-100 rounded';
                            el.style.height = '520px';
                          } else {
                            el = document.createElement('a');
                            el.href = url;
                            el.textContent = 'Lihat / Unduh Bukti';
                            el.className = 'btn btn-sm btn-outline-secondary';
                          }
                          viewer.innerHTML = '';
                          viewer.appendChild(el);
                        })
                        .catch(() => {
                          viewer.innerHTML = '<div class="text-danger small">Gagal memuat preview. Gunakan tombol Unduh Bukti.</div>';
                        });

                      document.getElementById('btnShowReuploadTop')?.addEventListener('click', function(e){
                        e.preventDefault();
                        const box = document.getElementById('unggah-ulang');
                        box?.classList.remove('d-none');
                        box?.scrollIntoView({behavior:'smooth'});
                        history.replaceState(null,'','#unggah-ulang');
                      });
                    })();
                  </script>
                <?php else: ?>
                  <div class="text-muted">Belum ada file bukti.</div>
                <?php endif; ?>

                <?php if ($canReupload): ?>
                  <!-- Re-upload form (muncul hanya pending/rejected) -->
                  <div id="unggah-ulang" class="mt-3 d-none">
                    <hr>
                    <form action="<?= site_url('audience/pembayaran/reupload/'.$payId) ?>"
                          method="post" enctype="multipart/form-data" id="reForm" novalidate>
                      <?= csrf_field() ?>
                      <label class="form-label">Unggah ulang (JPG/PNG/PDF, maks 5MB)</label>
                      <input type="file" name="bukti_bayar" id="reFile" class="form-control"
                             accept=".jpg,.jpeg,.png,.pdf" required>
                      <div id="clAlert" class="alert alert-danger mt-2 d-none"></div>

                      <div class="mt-2 d-grid d-md-flex gap-2">
                        <button class="btn btn-primary" id="btnSave">
                          <span class="spinner-border spinner-border-sm me-2 d-none" id="spin"></span>
                          Simpan
                        </button>
                        <button type="button" class="btn btn-light" id="btnCancelRe">Batal</button>
                      </div>
                    </form>
                  </div>
                <?php endif; ?>

              </div>
            </div>
          </div>

          <div class="mt-3 d-flex flex-wrap gap-2">
            <a href="<?= site_url('audience/events') ?>" class="btn btn-outline-secondary">Lihat Event Lain</a>
          </div>

        </div>
      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<style>
  /* ====== Mobile-first blue hero ====== */
  .pay-hero{
    background: linear-gradient(90deg,#2563eb,#60a5fa);
    border-radius: 16px; color:#fff; padding: 14px 16px;
    box-shadow: 0 6px 20px rgba(37,99,235,.18);
  }
  .pay-title{ font-weight:800; line-height:1.25; font-size: clamp(18px, 4.2vw, 24px); }
  .pay-tags{ display:flex; gap:.4rem; flex-wrap:wrap; margin-top:.2rem; }
  .pay-tag{
    background: rgba(255,255,255,.16); border:1px solid rgba(255,255,255,.22);
    color:#fff; border-radius:999px; padding:.28rem .6rem; font-size: .85rem;
    display:inline-flex; align-items:center; gap:.45rem;
  }
  @media (min-width: 576px){
    .pay-hero{ padding: 18px 20px; border-radius:18px; }
  }
</style>

<script>
(function(){
  const box   = document.getElementById('unggah-ulang');
  const show  = ()=> box?.classList.remove('d-none');
  const hide  = ()=> box?.classList.add('d-none');
  const btn   = document.getElementById('btnShowReupload');
  const btnC  = document.getElementById('btnCancelRe');
  const form  = document.getElementById('reForm');
  const file  = document.getElementById('reFile');
  const alertB= document.getElementById('clAlert');
  const btnSv = document.getElementById('btnSave');
  const spin  = document.getElementById('spin');

  if (location.hash === '#unggah-ulang') show();
  btn?.addEventListener('click', (e)=>{ e.preventDefault(); show(); box.scrollIntoView({behavior:'smooth'}); history.replaceState(null,'','#unggah-ulang'); });
  btnC?.addEventListener('click', ()=>{ hide(); alertB?.classList.add('d-none'); history.replaceState(null,'',' '); });

  form?.addEventListener('submit', (e)=>{
    const MAX = 5*1024*1024;
    const OK  = ['image/jpeg','image/png','application/pdf'];
    const f = file?.files?.[0];

    const err = (m)=>{ alertB.textContent=m; alertB.classList.remove('d-none'); };
    const clr = ()=>{ alertB.textContent=''; alertB.classList.add('d-none'); };

    if (!f){ e.preventDefault(); return err('Silakan pilih file.'); }
    if (!OK.includes(f.type)){ e.preventDefault(); return err('Tipe tidak didukung. Gunakan JPG, PNG, atau PDF.'); }
    if (f.size > MAX){ e.preventDefault(); return err('Ukuran melebihi 5MB.'); }
    clr(); btnSv.disabled = true; spin.classList.remove('d-none');
  });
})();
</script>
