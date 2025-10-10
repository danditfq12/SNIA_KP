<?php
$title        = $title ?? 'Tugas Abstrak (Belum Direview)';
$abstrak      = $abstrak ?? [];
$eventOptions = $eventOptions ?? [];

$formatDate = fn($d) => $d ? date('d M Y', strtotime($d)) : '-';
$badgeRev   = fn($s) => match(strtolower((string)$s)) {
  'diterima' => 'success',
  'revisi'   => 'warning text-dark',
  'ditolak'  => 'danger',
  default    => 'secondary'
};

$isEmpty = empty($abstrak);
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_reviewer') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid px-2 px-md-3 py-3"><!-- rapet kiri-kanan -->

      <!-- FILTER BAR -->
      <div class="card card-glass border-0 mb-3">
        <div class="card-body">
          <div class="row g-3 align-items-center">
            <div class="col-12 col-md-6">
              <div class="input-group">
                <span class="input-group-text bg-primary text-white border-0">
                  <i class="bi bi-search"></i>
                </span>
                <input type="search" id="searchInput" class="form-control border-start-0"
                       placeholder="Cari judul, penulis, atau kategori...">
              </div>
            </div>
            <div class="col-6 col-md-3">
              <select id="eventFilter" class="form-select">
                <option value="">Semua Event</option>
                <?php foreach ($eventOptions as $eid => $etitle): ?>
                  <option value="<?= (int)$eid ?>"><?= esc($etitle) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-6 col-md-3">
              <select id="statusFilter" class="form-select">
                <option value="">Semua Status Review</option>
                <option value="menunggu">Menunggu</option>
                <option value="diterima">Diterima</option>
                <option value="revisi">Revisi</option>
                <option value="ditolak">Ditolak</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <?php if ($isEmpty): ?>
        <!-- EMPTY STATE SAAT DATA KOSONG DARI SERVER -->
        <div class="card card-glass border-0">
          <div class="card-body text-center py-5">
            <div class="empty-icon mb-3"><i class="bi bi-inbox"></i></div>
            <div class="empty-title mb-1">Belum ada tugas untuk direview</div>
            <div class="empty-subtitle mb-4">Tugas yang sudah Anda review dipindah ke halaman Riwayat.</div>
            <div class="d-flex justify-content-center">
              <a href="<?= site_url('reviewer/riwayat') ?>" class="btn btn-primary">
                <i class="bi bi-clock-history me-1"></i>Lihat Riwayat
              </a>
            </div>
          </div>
        </div>
      <?php else: ?>
        <!-- LIST -->
        <div class="card card-glass border-0">
          <div class="table-responsive">
            <table class="table align-middle mb-0">
              <thead class="table-light">
                <tr>
                  <th class="fw-semibold" style="width:44%;">Abstrak</th>
                  <th class="fw-semibold d-none d-md-table-cell" style="width:18%;">Event</th>
                  <th class="fw-semibold d-none d-lg-table-cell" style="width:18%;">Penulis</th>
                  <th class="fw-semibold d-none d-xl-table-cell" style="width:14%;">Kategori</th>
                  <th class="fw-semibold" style="width:10%;">Status</th>
                  <th class="fw-semibold text-end" style="width:16%;">Aksi</th>
                </tr>
              </thead>
              <tbody id="listBody">
                <?php foreach ($abstrak as $r):
                  $rev = strtolower((string)($r['review_status'] ?? 'menunggu'));
                  $txt = strtolower(trim(($r['judul'] ?? '').' '.($r['nama_lengkap'] ?? '').' '.($r['nama_kategori'] ?? '')));
                  $id  = (int)($r['id_abstrak'] ?? 0);
                ?>
                  <tr class="review-row"
                      data-search="<?= esc($txt) ?>"
                      data-event="<?= (int)($r['event_id'] ?? 0) ?>"
                      data-status="<?= esc($rev) ?>">
                    <td>
                      <div class="fw-semibold text-dark mb-1"><?= esc($r['judul'] ?? '—') ?></div>
                      <div class="small text-muted d-flex flex-wrap gap-2">
                        <span><i class="bi bi-calendar2-plus me-1"></i><?= $formatDate($r['tanggal_upload'] ?? null) ?></span>
                        <span class="d-md-none">•</span>
                        <span class="d-md-none"><i class="bi bi-calendar-event me-1"></i><?= esc($r['event_title'] ?? '-') ?></span>
                        <span class="d-lg-none">•</span>
                        <span class="d-lg-none"><i class="bi bi-person me-1"></i><?= esc($r['nama_lengkap'] ?? '-') ?></span>
                      </div>
                    </td>
                    <td class="d-none d-md-table-cell">
                      <span class="badge bg-info-subtle text-info px-3 py-2">
                        <i class="bi bi-calendar-event me-1"></i><?= esc($r['event_title'] ?? 'Event') ?>
                      </span>
                    </td>
                    <td class="d-none d-lg-table-cell"><?= esc($r['nama_lengkap'] ?? '—') ?></td>
                    <td class="d-none d-xl-table-cell text-muted"><?= esc($r['nama_kategori'] ?? '—') ?></td>
                    <td>
                      <span class="badge bg-<?= $badgeRev($rev) ?> px-3 py-2"><?= ucfirst($rev) ?></span>
                    </td>
                    <td class="text-end">
                      <a href="<?= site_url('reviewer/abstrak/'.$id) ?>" class="btn btn-primary btn-sm">
                        <i class="bi bi-eye me-1"></i>Tinjau
                      </a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        </div>
      <?php endif; ?>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<style>
:root{
  --primary-color:#2563eb;
  --success-color:#10b981;
  --warning-color:#f59e0b;
  --danger-color:#ef4444;
  --info-color:#06b6d4;
  --secondary-color:#6b7280;

  /* semi glass */
  --glass-bg: rgba(255,255,255,.88);
  --glass-bd: rgba(30,41,59,.12);
  --glass-shadow: 0 10px 24px rgba(2,6,23,.08);
}

/* Latar & font umum */
body{
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* Jika kamu pakai header-section, judulnya putih */
.header-section{ background: linear-gradient(135deg, var(--primary-color) 0%, #1e40af 100%); color:#fff; }
.welcome-text{ color:#fff; }

/* Semi-glass card */
.card-glass{
  backdrop-filter: blur(6px);
  background: var(--glass-bg);
  border: 1px solid var(--glass-bd);
  border-radius: 12px;
  box-shadow: var(--glass-shadow);
}

/* Tabel */
.table thead th{ background:#f8fafc; border-bottom:1px solid #e2e8f0; font-weight:600; color:#374151; padding:14px; }
.table tbody td{ padding:14px; vertical-align:middle; }

/* Kontrol form */
.form-control, .form-select{ border:2px solid #e2e8f0; border-radius:8px; }
.form-control:focus, .form-select:focus{ border-color:var(--primary-color); box-shadow:0 0 0 .2rem rgba(37,99,235,.1); }
.input-group-text{ border-radius:8px 0 0 8px; }

/* Badge warna */
.bg-info-subtle{ background-color: rgba(6,182,212,.12) !important; }
.text-info{ color: var(--info-color) !important; }
.bg-success{ background-color: var(--success-color) !important; }
.bg-warning{ background-color: var(--warning-color) !important; }
.bg-danger{ background-color: var(--danger-color) !important; }
.bg-secondary{ background-color: var(--secondary-color) !important; }

/* Tombol */
.btn{ border-radius:8px; font-weight:500; }
.btn-primary{ background-color:var(--primary-color); border-color:var(--primary-color); }
.btn-outline-secondary{ border-color:#d1d5db; color:#6b7280; }
.btn-outline-secondary:hover{ background-color:#f9fafb; border-color:#9ca3af; color:#374151; }

/* Empty state */
.empty-icon{ font-size:3rem; color:#94a3b8; }
.empty-title{ font-weight:700; color:#334155; }
.empty-subtitle{ color:#64748b; }

/* Kompak di mobile */
@media (max-width: 575.98px){
  .container-fluid{ padding-left:.5rem!important; padding-right:.5rem!important; }
  td.text-end .btn{ min-width:110px; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
  const q  = document.getElementById('searchInput');
  const ef = document.getElementById('eventFilter');
  const sf = document.getElementById('statusFilter');
  const body = document.getElementById('listBody');

  if (!body) return;

  const rows = Array.from(body.querySelectorAll('.review-row'));

  function ensureEmptyRow() {
    let empty = document.getElementById('emptyRow');
    if (!empty) {
      empty = document.createElement('tr');
      empty.id = 'emptyRow';
      empty.innerHTML = `<td colspan="6" class="py-5 text-center text-muted">
        <i class="bi bi-search fs-1 d-block mb-2"></i>Tidak ada hasil
      </td>`;
    }
    return empty;
  }

  function apply(){
    const qq = (q?.value || '').toLowerCase().trim();
    const ev = ef?.value || '';
    const st = (sf?.value || '').toLowerCase();

    let shown = 0;
    rows.forEach(tr=>{
      const s = tr.dataset.search || '';
      const e = tr.dataset.event  || '';
      const t = tr.dataset.status || '';
      const ok = (!qq || s.includes(qq)) && (!ev || ev===e) && (!st || st===t);
      tr.style.display = ok ? '' : 'none';
      if (ok) shown++;
    });

    const emptyRow = document.getElementById('emptyRow');
    if (!shown){
      if (!emptyRow) body.appendChild(ensureEmptyRow());
    } else {
      emptyRow?.remove();
    }
  }

  [q,ef,sf].forEach(el=> el?.addEventListener('input', apply));
  [ef,sf].forEach(el=> el?.addEventListener('change', apply));

  apply();
});
</script>
