<?php
$title        = $title        ?? 'Abstrak';
$uploadEvents = $uploadEvents ?? [];
$history      = $history      ?? [];

$fmtDate = function($s){ return $s ? date('d M Y', strtotime($s)) : '-'; };
$fmtDT   = function($s){ return $s ? date('d M Y H:i', strtotime($s)) : '-'; };
$formatLabel = function($f){
  $f = strtolower((string)$f);
  return $f === 'both' ? 'Hybrid' : ucfirst($f ?: '-');
};
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill page-wrap-blue">
    <div class="container-xxl px-3 px-md-4 py-4">

      <div class="hero-blue card-glass mb-3 p-3 p-md-4 d-flex justify-content-between align-items-start gap-3">
        <div>
          <h3 class="hero-title mb-1"><i class="bi bi-journal-text me-2"></i>Abstrak</h3>
          <div class="text-white-75 small">Upload & pantau status abstrak</div>
        </div>
        <div class="d-none d-md-block text-end">
          <div class="text-white-75 small">Hari ini</div>
          <div class="fw-semibold text-white"><?= date('d M Y') ?></div>
        </div>
      </div>

      <!-- Abstrak perlu upload -->
      <div class="card shadow-soft card-glass-plain mb-4">
        <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
          <span class="badge bg-blue-soft"><i class="bi bi-upload"></i></span>
          <h5 class="mb-0 fw-semibold text-blue-900">Abstrak perlu upload</h5>
        </div>
        <div class="card-body">
          <?php if (empty($uploadEvents)): ?>
            <div class="text-muted">Tidak ada event yang membutuhkan upload abstrak saat ini.</div>
          <?php else: ?>
            <div class="row g-3">
              <?php foreach ($uploadEvents as $row): ?>
              <div class="col-12 col-md-6 col-xl-4">
                <div class="event-card h-100 p-3">
                  <div class="d-flex align-items-start justify-content-between mb-2">
                    <h6 class="mb-0 text-blue-900"><?= esc($row['title'] ?? '-') ?></h6>
                    <span class="badge bg-<?= esc($row['status_badge']) ?>-subtle text-<?= esc($row['status_badge']) ?>">
                      <?= esc($row['status_label']) ?>
                    </span>
                  </div>

                  <div class="small text-muted mb-2">
                    Tanggal Event: <strong class="text-blue-900"><?= esc($fmtDate($row['event_date'] ?? null)) ?></strong><br>
                    Deadline Abstrak: <strong class="text-blue-900"><?= esc($fmtDT($row['abstract_deadline'] ?? null)) ?></strong><br>
                    Format: <strong class="text-blue-900"><?= esc($formatLabel($row['format'] ?? '')) ?></strong>
                  </div>

                  <?php if (!empty($row['hint'])): ?>
                    <div class="text-muted small mb-2"><?= esc($row['hint']) ?></div>
                  <?php endif; ?>

                  <div class="d-flex gap-2">
                    <a class="btn btn-primary flex-fill" href="<?= site_url('presenter/abstrak/create/'.(int)$row['event_id']) ?>">
                      <i class="bi bi-upload"></i> Upload Abstrak
                    </a>
                    <?php if (!empty($row['last_abs_id'])): ?>
                      <a class="btn btn-outline-secondary flex-fill" href="<?= site_url('presenter/abstrak/detail/'.(int)$row['last_abs_id']) ?>">
                        <i class="bi bi-eye"></i> Lihat Terakhir
                      </a>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Riwayat Abstrak -->
      <div class="card shadow-soft card-glass-plain mb-4">
        <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
          <span class="badge bg-blue-soft"><i class="bi bi-clock-history"></i></span>
          <h5 class="mb-0 fw-semibold text-blue-900">Riwayat Abstrak</h5>
        </div>
        <div class="card-body">
          <?php if (empty($history)): ?>
            <div class="text-muted">Belum ada riwayat abstrak.</div>
          <?php else: ?>
            <div class="row g-3">
              <?php foreach ($history as $h): ?>
              <div class="col-12 col-md-6 col-xl-4">
                <div class="event-card h-100 p-3">
                  <div class="d-flex align-items-start justify-content-between mb-1">
                    <h6 class="mb-0 text-blue-900"><?= esc($h['judul']) ?></h6>
                    <span class="badge bg-<?= esc($h['status_badge']) ?>-subtle text-<?= esc($h['status_badge']) ?>">
                      <?= esc($h['status_label']) ?>
                    </span>
                  </div>

                  <div class="small text-muted mb-2"><?= esc($h['nama_kategori'] ?? '-') ?></div>

                  <div class="small text-muted mb-2">
                    Event: <strong class="text-blue-900"><?= esc($h['event_title'] ?? '-') ?></strong><br>
                    Tanggal Event: <strong class="text-blue-900"><?= esc($fmtDate($h['event_date'] ?? null)) ?></strong>
                  </div>

                  <?php if (!empty($h['status_hint'])): ?>
                    <?php
                      $cls = (($h['status'] ?? '') === 'diterima') ? 'text-success' :
                             ((($h['status'] ?? '') === 'ditolak') ? 'text-danger' : 'text-muted');
                    ?>
                    <div class="small mb-2 <?= $cls ?>"><?= esc($h['status_hint']) ?></div>
                  <?php endif; ?>

                  <div class="small text-muted">Dikirim: <strong class="text-blue-900"><?= esc($fmtDT($h['tanggal_upload'] ?? null)) ?></strong></div>

                  <div class="mt-2 d-grid gap-2">
                    <a class="btn btn-outline-primary btn-sm" href="<?= site_url('presenter/abstrak/detail/'.(int)$h['id_abstrak']) ?>">
                      <i class="bi bi-eye"></i> Detail
                    </a>

                    <?php if (($h['status'] ?? '') === 'ditolak'): ?>
                      <a class="btn btn-danger btn-sm" href="<?= site_url('presenter/abstrak/create/'.(int)$h['event_id']) ?>">
                        <i class="bi bi-upload"></i> Upload Ulang Abstrak
                      </a>
                    <?php endif; ?>

                    <!-- sesuai permintaan: TIDAK ADA tombol Batalkan di index -->
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
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

  .card-glass-plain{ backdrop-filter: blur(3px); background: rgba(255,255,255,.7); border-radius:16px; border:1px solid rgba(30,64,175,.06); }
  .shadow-soft{ box-shadow: 0 10px 24px rgba(30,64,175,.06); }
  .bg-blue-soft{ background: var(--blue-100); color: var(--blue-800); border-radius:12px; padding:.35rem .6rem; }
  .text-blue-900{ color: var(--blue-900)!important; }

  .event-card{
    background: linear-gradient(180deg,#fff, rgba(255,255,255,.9));
    border:1px solid rgba(30,64,175,.08); border-radius:14px;
    box-shadow: 0 10px 22px rgba(30,64,175,.06);
  }

  /* subtle badge variants */
  .bg-success-subtle{ background:#eafaf3 !important; color:#0f766e !important;}
  .bg-warning-subtle{ background:#fff7e6 !important; color:#a16207 !important;}
  .bg-danger-subtle{ background:#ffe9e9 !important; color:#b91c1c !important;}
  .bg-info-subtle{ background:#e6f9fc !important; color:#0369a1 !important;}
  .bg-secondary-subtle{ background:#f1f5f9 !important; color:#334155 !important;}

  .btn-primary{ background:var(--blue-600); border-color:var(--blue-600); }
  .btn-primary:hover{ background:var(--blue-700); border-color:var(--blue-700); }

  @media (max-width: 767.98px){
    .hero-blue{ border-radius:14px; }
    .container-xxl{ padding-left:.9rem; padding-right:.9rem; }
  }
</style>
