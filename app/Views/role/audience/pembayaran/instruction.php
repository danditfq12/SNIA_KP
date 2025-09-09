<?php
  $title  = 'Instruksi Pembayaran';

  // data dari controller
  $reg      = $reg ?? [];                 // minimal: ['id' => ...]
  $amount   = (float) ($amount ?? 0);     // nominal yang harus ditransfer
  $event    = $event ?? null;             // opsional: ['title'=>..., 'event_date'=>..., 'event_time'=>...]
  $deadline = $reg['deadline'] ?? null;   // opsional: string datetime

  // detail rekening (bisa pindah ke .env / config)
  $bankName  = 'Bank BNI';
  $atasNama  = 'Panitia SNIA';
  $rekNumber = '1234567890';

  $amountFmt = 'Rp ' . number_format($amount, 0, ',', '.');
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_audience') ?>
<?= $this->include('partials/alerts') ?>

<style>
  /* --- Scoped styles (blue theme) --- */
  .pay-hero{
    /* dari ungu -> biru */
    background: linear-gradient(90deg,#2563eb,#60a5fa); /* blue-600 -> blue-300 */
    border-radius: 18px; color:#fff; padding:18px 20px;
    box-shadow: 0 6px 20px rgba(37,99,235,.20); /* bayangan kebiruan */
  }
  .pay-tags{ display:flex; gap:.5rem; flex-wrap:wrap; margin-bottom:.25rem; }
  .pay-tag{
    background: rgba(255,255,255,.18);
    border:1px solid rgba(255,255,255,.22);
    color:#fff; border-radius:999px; padding:.35rem .65rem; font-size:.875rem;
    display:inline-flex; align-items:center; gap:.4rem;
  }
  .pay-amount{ font-weight:700; line-height:1.2; font-size: clamp(22px, 4.6vw, 36px); }
  .copy-wrap .form-control{ font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, "Liberation Mono", monospace; }
  .copy-wrap .btn{ white-space:nowrap; }
</style>


<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <!-- HERO -->
      <div class="pay-hero mb-3">
        <div class="pay-tags">
          <?php if ($event): ?>
            <span class="pay-tag"><i class="bi bi-calendar2-event"></i><?= esc(date('d M Y', strtotime($event['event_date'] ?? date('Y-m-d')))) ?></span>
            <span class="pay-tag"><i class="bi bi-clock"></i><?= esc($event['event_time'] ?? '-') ?></span>
          <?php endif; ?>
          <span class="pay-tag"><i class="bi bi-bank"></i><?= esc($bankName) ?></span>
          <span class="pay-tag"><i class="bi bi-person-badge"></i>a.n. <?= esc($atasNama) ?></span>
        </div>
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
          <h1 class="pay-amount mb-0"><?= $amountFmt ?></h1>
          <span class="badge bg-info text-dark px-3 py-2">
            <i class="bi bi-info-circle me-1"></i>Transfer tepat sesuai nominal
          </span>
        </div>
      </div>

      <div class="row g-3">
        <!-- Kolom kiri: detail & copy -->
        <div class="col-12 col-lg-7">
          <div class="card shadow-sm h-100">
            <div class="card-body">
              <h5 class="card-title mb-3">Nomor Rekening</h5>

              <div class="row g-2 align-items-end copy-wrap">
                <div class="col-12">
                  <label class="form-label text-muted small mb-1">Nama Bank</label>
                  <input class="form-control" value="<?= esc($bankName) ?>" readonly>
                </div>
                <div class="col-12">
                  <label class="form-label text-muted small mb-1">Atas Nama</label>
                  <input class="form-control" value="<?= esc($atasNama) ?>" readonly>
                </div>
                <div class="col-12">
                  <label class="form-label text-muted small mb-1">No. Rekening</label>
                  <div class="input-group">
                    <input type="text" id="rekNumber" class="form-control" value="<?= esc($rekNumber) ?>" readonly>
                    <button class="btn btn-outline-secondary" id="btnCopyRek" type="button" title="Salin nomor rekening">
                      <i class="bi bi-clipboard"></i> Salin
                    </button>
                  </div>
                  <div class="form-text">Yang akan disalin hanyalah angka rekening di atas.</div>
                </div>
              </div>

              <hr class="my-4">

              <h6 class="mb-2">Langkah Pembayaran</h6>
              <ol class="mb-0">
                <li>Transfer ke rekening di atas tepat sebesar <b><?= $amountFmt ?></b>.</li>
                <li>Setelah transfer, klik tombol <b>Upload Bukti Pembayaran</b>.</li>
                <li>Tunggu verifikasi panitia (notifikasi akan muncul di dashboard).</li>
              </ol>
            </div>
          </div>
        </div>

        <!-- Kolom kanan: ringkasan & aksi -->
        <div class="col-12 col-lg-5">
          <div class="card shadow-sm h-100">
            <div class="card-body">
              <h5 class="card-title mb-3">Ringkasan</h5>

              <div class="mb-3">
                <div class="d-flex justify-content-between">
                  <span class="text-muted">Nominal</span>
                  <span class="fw-semibold"><?= $amountFmt ?></span>
                </div>
                <?php if ($event): ?>
                <div class="d-flex justify-content-between">
                  <span class="text-muted">Event</span>
                  <span class="fw-semibold text-end ms-2"><?= esc($event['title'] ?? 'Event') ?></span>
                </div>
                <?php endif; ?>
                <?php if ($deadline): ?>
                <div class="d-flex justify-content-between">
                  <span class="text-muted">Batas bayar</span>
                  <span class="fw-semibold"><?= esc(date('d M Y H:i', strtotime($deadline))) ?></span>
                </div>
                <?php endif; ?>
              </div>

              <div class="d-grid gap-2">
                <a href="<?= site_url('audience/pembayaran/create/'.($reg['id'] ?? 0)) ?>"
                   class="btn btn-success">
                  <i class="bi bi-cloud-arrow-up me-1"></i>Upload Bukti Pembayaran
                </a>
                <?php if (!empty($reg['event_id'])): ?>
                <a href="<?= site_url('audience/events/detail/'.$reg['event_id']) ?>" class="btn btn-outline-secondary">
                  Kembali ke Detail Event
                </a>
                <?php endif; ?>
              </div>

              <div class="alert alert-warning mt-3 mb-0 small">
                <i class="bi bi-exclamation-triangle me-1"></i>
                Simpan bukti transfer yang jelas (nama pengirim & nominal terbaca).
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </main>
</div>

<script>
(function(){
  async function copyText(text){
    if (navigator.clipboard && window.isSecureContext) {
      await navigator.clipboard.writeText(text);
      return true;
    }
    // fallback (HTTP / browser lama)
    const ta = document.createElement('textarea');
    ta.value = text;
    ta.style.position = 'fixed';
    ta.style.left = '-9999px';
    document.body.appendChild(ta);
    ta.focus(); ta.select();
    const ok = document.execCommand('copy');
    document.body.removeChild(ta);
    return ok;
  }

  const btn = document.getElementById('btnCopyRek');
  btn?.addEventListener('click', async ()=>{
    const num = document.getElementById('rekNumber')?.value || '';
    try{
      const ok = await copyText(num);
      if (ok) {
        if (window.Swal) Swal.fire({icon:'success', title:'Nomor rekening tersalin', timer:1200, showConfirmButton:false});
        else alert('Nomor rekening tersalin: ' + num);
      } else throw new Error('copy failed');
    }catch(_){
      if (window.Swal) Swal.fire({icon:'error', title:'Gagal menyalin', text:num});
      else prompt('Salin manual nomor rekening berikut:', num);
    }
  });
})();
</script>

<?= $this->include('partials/footer') ?>