<?php
$title    = $title ?? 'Detail Absensi';
$event    = $event ?? [];
$payment  = $payment ?? [];
$window   = $window ?? ['is_open'=>false,'start_ts'=>null,'end_ts'=>null,'reason'=>''];
$attended = $attended ?? false;

$startText = $window['start_ts'] ? date('d M Y H:i', $window['start_ts']) : '-';
$endText   = $window['end_ts']   ? date('d M Y H:i', $window['end_ts'])   : '-';
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <!-- HEADER -->
      <div class="header-section header-blue d-flex justify-content-between align-items-center mb-4">
        <div>
          <h2 class="welcome-text mb-1"><i class="bi bi-qr-code"></i> Detail Absensi</h2>
          <div class="text-white-50">Event: <?= esc($event['title'] ?? '-') ?></div>
        </div>
        <div class="text-end d-none d-md-block">
          <small class="text-white-50 d-block">Window Absensi</small>
          <strong class="text-white"><?= $startText ?> - <?= $endText ?></strong>
        </div>
      </div>

      <!-- STATUS + AKSI -->
      <div class="card shadow-sm mb-4">
        <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
          <div class="d-flex align-items-center gap-3">
            <div class="status-dot <?= $attended ? 'bg-success' : ($window['is_open'] ? 'bg-primary' : 'bg-secondary') ?>"></div>
            <div>
              <div class="fw-semibold mb-1">Status</div>
              <?php if ($attended): ?>
                <div class="text-success"><i class="bi bi-check-circle"></i> Anda sudah tercatat hadir.</div>
              <?php elseif ($window['is_open']): ?>
                <div class="text-primary"><i class="bi bi-door-open"></i> Window absensi sedang dibuka.</div>
              <?php else: ?>
                <div class="text-muted"><i class="bi bi-lock"></i> Window absensi tertutup <?= $window['reason'] ? '('.esc($window['reason']).')' : '' ?>.</div>
              <?php endif; ?>
              <div class="small text-muted mt-1">
                Lokasi: <?= esc($event['location'] ?? '-') ?>
                <?php if (!empty($event['format'])): ?> · Format: <?= strtoupper(esc($event['format'])) ?><?php endif; ?>
              </div>
            </div>
          </div>

          <div class="d-flex flex-wrap gap-2">
            <?php if (!$attended && $window['is_open']): ?>
              <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tokenModal">
                <i class="bi bi-input-cursor-text"></i> Masukkan Token & Absen
              </button>
            <?php else: ?>
              <button class="btn btn-outline-secondary" disabled>
                <i class="bi bi-input-cursor-text"></i> Masukkan Token
              </button>
            <?php endif; ?>
            <a href="/qr" target="_blank" class="btn btn-outline-primary">
              <i class="bi bi-qr-code-scan"></i> Buka Scanner
            </a>
          </div>
        </div>
      </div>

      <!-- INFO EVENT -->
      <div class="row g-3">
        <div class="col-12 col-lg-8">
          <div class="card shadow-sm h-100">
            <div class="card-header bg-light"><strong><i class="bi bi-info-circle"></i> Informasi Event</strong></div>
            <div class="card-body">
              <h5 class="mb-2"><?= esc($event['title'] ?? '-') ?></h5>
              <div class="text-muted small mb-3"><?= nl2br(esc($event['description'] ?? '-')) ?></div>
              <div class="row gy-2">
                <div class="col-12 col-md-6">
                  <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-calendar-event text-primary"></i>
                    <div>
                      <div class="small text-muted">Tanggal</div>
                      <div class="fw-semibold">
                        <?= $event['event_date'] ? date('d M Y', strtotime($event['event_date'])) : '-' ?>
                        <?= $event['event_time'] ? ' · '.esc($event['event_time']) : '' ?>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-12 col-md-6">
                  <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-geo-alt text-primary"></i>
                    <div>
                      <div class="small text-muted">Lokasi</div>
                      <div class="fw-semibold"><?= esc($event['location'] ?? '-') ?></div>
                    </div>
                  </div>
                </div>
                <?php if (!empty($event['zoom_link'])): ?>
                <div class="col-12">
                  <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-camera-video text-primary"></i>
                    <div>
                      <div class="small text-muted">Link Zoom</div>
                      <a href="<?= esc($event['zoom_link']) ?>" target="_blank" class="fw-semibold">Buka Tautan</a>
                    </div>
                  </div>
                </div>
                <?php endif; ?>
              </div>
            </div>
          </div>
        </div>

        <!-- INFO BAYAR -->
        <div class="col-12 col-lg-4">
          <div class="card shadow-sm h-100">
            <div class="card-header bg-light"><strong><i class="bi bi-receipt"></i> Status Pembayaran</strong></div>
            <div class="card-body">
              <div class="d-flex align-items-center gap-2 mb-2">
                <span class="badge bg-success">Verified</span>
                <div class="small text-muted">Anda berhak melakukan absensi.</div>
              </div>
              <div class="small text-muted">Metode</div>
              <div class="fw-semibold mb-2"><?= esc($payment['metode'] ?? '-') ?></div>

              <div class="small text-muted">Jumlah</div>
              <div class="fw-semibold">Rp <?= number_format((int)($payment['jumlah'] ?? 0), 0, ',', '.') ?></div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </main>
</div>

<!-- MODAL TOKEN -->
<div class="modal fade" id="tokenModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content">
    <form action="/presenter/absensi/scan" method="POST" id="tokenForm">
      <?= csrf_field() ?>
      <div class="modal-header">
        <h5 class="modal-title"><i class="bi bi-input-cursor-text me-2"></i> Masukkan Token</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>
      <div class="modal-body">
        <input type="hidden" name="event_id" value="<?= (int)($event['id'] ?? 0) ?>">
        <div class="mb-2">
          <label class="form-label">Token Kehadiran <span class="text-danger">*</span></label>
          <input type="text" class="form-control" name="token" required placeholder="Masukkan token dari panitia">
          <div class="form-text">Diberikan panitia saat sesi berlangsung.</div>
        </div>
        <div class="alert alert-info small mb-0">
          Window absensi: <strong><?= $startText ?> - <?= $endText ?></strong>
        </div>
      </div>
      <div class="modal-footer">
        <button class="btn btn-secondary" type="button" data-bs-dismiss="modal">Batal</button>
        <button class="btn btn-primary" type="submit"><i class="bi bi-check2-circle"></i> Konfirmasi & Absen</button>
      </div>
    </form>
  </div></div>
</div>

<?= $this->include('partials/footer') ?>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
  // Konfirmasi sebelum submit token
  document.getElementById('tokenForm')?.addEventListener('submit', function(e){
    e.preventDefault();
    Swal.fire({
      title: 'Kirim token absensi?',
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Kirim',
      cancelButtonText: 'Batal'
    }).then(r=>{ if (r.isConfirmed) this.submit(); });
  });

  // Flash helper (opsional, kalau partials/alerts kamu belum SweetAlert)
  <?php if (session('success')): ?>
    Swal.fire({ icon:'success', title:'Berhasil', text:'<?= esc(session('success')) ?>', timer:2400, showConfirmButton:false });
  <?php endif; ?>
  <?php if (session('error')): ?>
    Swal.fire({ icon:'error', title:'Gagal', text:'<?= esc(session('error')) ?>' });
  <?php endif; ?>
  <?php if (session('info')): ?>
    Swal.fire({ icon:'info', title:'Info', text:'<?= esc(session('info')) ?>' });
  <?php endif; ?>
</script>

<style>
  :root{
    --primary-color:#2563eb; --info-color:#06b6d4; --success-color:#10b981; --secondary:#64748b;
  }
  body{ background:#f8fafc; font-family: system-ui, -apple-system, "Segoe UI", Roboto, sans-serif; }

  .header-section.header-blue{
    background: linear-gradient(135deg, var(--primary-color) 0%, #1e40af 100%);
    color:#fff; padding:22px; border-radius:14px; box-shadow:0 8px 28px rgba(0,0,0,.12);
  }
  .welcome-text{ font-size:1.35rem; font-weight:500; }

  .status-dot{ width:12px; height:12px; border-radius:50%; }
  .status-dot.bg-success{ background:#16a34a; }
  .status-dot.bg-primary{ background:#2563eb; }
  .status-dot.bg-secondary{ background:#94a3b8; }

  .card { border-radius:14px; }
  .btn { border-radius:10px; }
  .badge{ border-radius:8px; }
</style>