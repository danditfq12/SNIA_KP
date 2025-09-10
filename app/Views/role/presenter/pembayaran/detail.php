<?php
  $title   = $title ?? 'Detail Pembayaran';
  $p       = $payment ?? [];
  $fmtDT   = fn($s)=> $s ? date('d M Y H:i', strtotime($s)) : '-';
  $badge   = match (strtolower((string)($p['status'] ?? ''))) {
    'pending'  => 'bg-warning text-dark',
    'verified' => 'bg-success',
    'rejected' => 'bg-danger',
    'canceled' => 'bg-secondary',
    default    => 'bg-secondary'
  };
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">
      <div class="abs-hero mb-3">
        <div class="d-flex justify-content-between align-items-center">
          <div>
            <div class="abs-title">Detail Pembayaran</div>
            <div class="abs-sub">Status & bukti pembayaranmu.</div>
          </div>
          <div>
            <span class="badge <?= $badge ?>"><?= ucfirst($p['status'] ?? '-') ?></span>
          </div>
        </div>
      </div>

      <div class="row g-3">
        <div class="col-12 col-lg-7">
          <div class="card shadow-sm h-100">
            <div class="card-body">
              <h5 class="card-title mb-2"><?= esc($p['title'] ?? 'Event') ?></h5>
              <div class="small text-muted mb-2">
                <?= esc(date('d M Y', strtotime($p['event_date'] ?? 'now'))) ?> · <?= esc($p['event_time'] ?? '-') ?>
                <?php if (!empty($p['location'])): ?> · Lokasi: <?= esc($p['location']) ?><?php endif; ?>
              </div>
              <hr>
              <div class="d-flex justify-content-between"><div>Jumlah</div><div class="fw-semibold">Rp <?= number_format((float)($p['jumlah'] ?? 0), 0, ',', '.') ?></div></div>
              <?php if (!empty($p['voucher_code'])): ?>
                <div class="d-flex justify-content-between"><div>Voucher</div><div class="text-success fw-semibold"><?= esc($p['voucher_code']) ?><?= isset($p['discount'])? ' (− Rp '.number_format((float)$p['discount'],0,',','.').')' : '' ?></div></div>
              <?php endif; ?>
              <div class="d-flex justify-content-between"><div>Tanggal</div><div><?= esc($fmtDT($p['tanggal_bayar'] ?? null)) ?></div></div>
            </div>
          </div>
        </div>

        <div class="col-12 col-lg-5">
          <div class="card shadow-sm h-100">
            <div class="card-body">
              <h5 class="card-title mb-2">Bukti Pembayaran</h5>
              <?php if (!empty($p['bukti'])): ?>
                <div class="ratio ratio-16x9 border rounded overflow-hidden mb-2 bg-light d-flex align-items-center justify-content-center">
                  <?php if (preg_match('~\.(jpg|jpeg|png|webp)$~i', $p['bukti'])): ?>
                    <img src="<?= site_url('writable/'.$p['bukti']) ?>" alt="Bukti" class="img-fluid">
                  <?php else: ?>
                    <div class="text-center p-4">
                      <i class="bi bi-file-earmark-pdf fs-1 text-danger"></i>
                      <div class="small text-muted mt-2">Bukti berupa PDF</div>
                    </div>
                  <?php endif; ?>
                </div>
                <div class="d-flex gap-2">
                  <a href="<?= site_url('presenter/pembayaran/download-bukti/'.$p['id_pembayaran']) ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-download me-1"></i>Download
                  </a>
                  <?php if (!in_array(strtolower((string)$p['status']), ['verified','canceled'], true)): ?>
                    <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#reuploadModal">
                      <i class="bi bi-arrow-repeat me-1"></i>Re-upload
                    </button>
                    <a href="<?= site_url('presenter/pembayaran/cancel/'.$p['id_pembayaran']) ?>"
                       class="btn btn-outline-danger btn-sm"
                       onclick="return confirm('Batalkan pembayaran ini?')">
                      <i class="bi bi-x-circle me-1"></i>Batalkan
                    </a>
                  <?php endif; ?>
                </div>
              <?php else: ?>
                <div class="p-4 text-center border rounded-3 bg-light-subtle">
                  <div class="mb-2"><i class="bi bi-file-earmark-arrow-up fs-3 text-secondary"></i></div>
                  <div class="fw-semibold">Bukti tidak tersedia</div>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

    </div>
  </main>
</div>

<!-- Modal reupload -->
<div class="modal fade" id="reuploadModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <form class="modal-content" method="post" action="<?= site_url('presenter/pembayaran/reupload/'.$p['id_pembayaran']) ?>" enctype="multipart/form-data">
      <?= csrf_field() ?>
      <div class="modal-header">
        <h6 class="modal-title">Re-upload Bukti Pembayaran</h6>
        <button class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="mb-2 small text-muted">Unggah bukti baru (JPG/PNG/WebP/PDF). Status akan kembali <strong>Pending</strong>.</div>
        <input type="file" name="bukti" class="form-control" accept=".jpg,.jpeg,.png,.webp,.pdf" required>
      </div>
      <div class="modal-footer">
        <button class="btn btn-light" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-primary" type="submit"><i class="bi bi-upload me-1"></i>Unggah</button>
      </div>
    </form>
  </div>
</div>

<?= $this->include('partials/footer') ?>

<style>
.abs-hero{background:linear-gradient(90deg,#2563eb,#60a5fa);border-radius:16px;color:#fff;padding:14px 16px;box-shadow:0 6px 20px rgba(37,99,235,.18);}
.abs-title{font-weight:800;line-height:1.2;font-size:clamp(18px,4.2vw,24px);}
.abs-sub{opacity:.9;font-size:.95rem;}
</style>
