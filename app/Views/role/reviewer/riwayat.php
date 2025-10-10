<?php
$title = $title ?? 'Riwayat Review';
$riwayatAbstrak    = $riwayatAbstrak ?? [];
$riwayatFullpaper  = $riwayatFullpaper ?? [];

$badge = function($k){
  $s = strtolower((string)$k);
  return match(true){
    in_array($s,['diterima']) => 'bg-success',
    in_array($s,['revisi'])   => 'bg-warning text-dark',
    in_array($s,['ditolak'])  => 'bg-danger',
    default                   => 'bg-secondary'
  };
};
$fmtDate = fn($d)=> $d? date('d M Y', strtotime($d)):'-';
$fmtDT   = fn($d)=> $d? date('d M Y H:i', strtotime($d)):'-';
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_reviewer') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <!-- HEADER -->
      <div class="header-section d-flex justify-content-between align-items-center mb-4">
        <div>
          <h3 class="welcome-text mb-1">
            <i class="bi bi-clock-history me-2"></i>Riwayat Review
          </h3>
          <small class="text-white-50">Dipisah antara Full Paper & Abstrak • dedup per presenter per event</small>
        </div>
        <div class="d-flex align-items-center gap-2">
          <span class="badge bg-primary-subtle text-primary fs-6 px-3 py-2">
            <i class="bi bi-collection me-1"></i><?= count($riwayatFullpaper) + count($riwayatAbstrak) ?> total
          </span>
          <a href="<?= site_url('reviewer/abstrak') ?>" class="btn btn-light d-none d-md-block">
            <i class="bi bi-journal-text me-1"></i>Tugas Aktif
          </a>
        </div>
      </div>

      <!-- FILTERS -->
      <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
          <div class="row g-3 align-items-center">
            <div class="col-12 col-md-6">
              <div class="input-group">
                <span class="input-group-text bg-primary text-white border-0">
                  <i class="bi bi-search"></i>
                </span>
                <input id="q" type="search" class="form-control border-start-0" placeholder="Cari judul, penulis, atau kategori...">
              </div>
            </div>
            <div class="col-6 col-md-3">
              <select id="status" class="form-select">
                <option value="">Semua Keputusan</option>
                <option value="diterima">Diterima</option>
                <option value="revisi">Revisi</option>
                <option value="ditolak">Ditolak</option>
                <option value="pending">Pending</option>
              </select>
            </div>
            <div class="col-6 col-md-3 text-end">
              <button id="reset" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-clockwise me-1"></i>Reset
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- TABS -->
      <ul class="nav nav-tabs mb-3" id="histTabs" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" id="fp-tab" data-bs-toggle="tab" data-bs-target="#fp-pane" type="button" role="tab">
            <i class="bi bi-file-earmark-text me-1"></i>Full Paper
            <span class="badge bg-primary-subtle text-primary ms-1"><?= count($riwayatFullpaper) ?></span>
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="ab-tab" data-bs-toggle="tab" data-bs-target="#ab-pane" type="button" role="tab">
            <i class="bi bi-journal-text me-1"></i>Abstrak
            <span class="badge bg-primary-subtle text-primary ms-1"><?= count($riwayatAbstrak) ?></span>
          </button>
        </li>
      </ul>

      <div class="tab-content">
        <!-- FULL PAPER -->
        <div class="tab-pane fade show active" id="fp-pane" role="tabpanel" aria-labelledby="fp-tab">
          <?php if (empty($riwayatFullpaper)): ?>
            <div class="card shadow-sm border-0">
              <div class="card-body text-center py-5">
                <div class="empty-icon"><i class="bi bi-journal-x"></i></div>
                <div class="empty-title">Belum ada riwayat Full Paper</div>
                <div class="empty-subtitle">Review yang sudah kamu ACC juga muncul di sini (sudah dedup per presenter per event).</div>
              </div>
            </div>
          <?php else: ?>
            <div class="card shadow-sm border-0">
              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tbl-fp">
                  <thead class="table-light">
                    <tr>
                      <th>Judul</th>
                      <th class="d-none d-lg-table-cell">Event</th>
                      <th>Presenter</th>
                      <th class="d-none d-xl-table-cell">Kategori</th>
                      <th>Keputusan</th>
                      <th>Tgl Review</th>
                      <th class="text-end">Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($riwayatFullpaper as $r):
                      $search = strtolower(trim(($r['judul'] ?? '').' '.($r['nama_lengkap'] ?? '').' '.($r['nama_kategori'] ?? '').' '.($r['event_title'] ?? '')));
                      $dec    = strtolower((string)($r['keputusan'] ?? 'pending'));
                      $sid    = (int)($r['submission_id'] ?? 0);
                      $detailUrl = $sid ? site_url('reviewer/fullpaper/'.$sid) : '#';
                    ?>
                      <tr class="row-item" data-kind="fp" data-search="<?= esc($search) ?>" data-status="<?= esc($dec) ?>">
                        <td>
                          <div class="fw-semibold text-dark"><?= esc($r['judul'] ?? '-') ?></div>
                          <div class="small text-muted d-lg-none"><i class="bi bi-calendar-event me-1"></i><?= esc($r['event_title'] ?? '-') ?></div>
                        </td>
                        <td class="d-none d-lg-table-cell">
                          <span class="badge bg-info-subtle text-info px-3 py-2">
                            <i class="bi bi-calendar-event me-1"></i><?= esc($r['event_title'] ?? '-') ?>
                          </span>
                        </td>
                        <td>
                          <div class="d-flex align-items-center gap-2">
                            <div class="user-avatar bg-primary-subtle text-primary"><i class="bi bi-person"></i></div>
                            <div class="text-dark"><?= esc($r['nama_lengkap'] ?? '-') ?></div>
                          </div>
                        </td>
                        <td class="d-none d-xl-table-cell text-muted"><?= esc($r['nama_kategori'] ?? '-') ?></td>
                        <td><span class="badge <?= $badge($r['keputusan'] ?? 'pending') ?> px-3 py-2"><?= ucfirst($r['keputusan'] ?? 'pending') ?></span></td>
                        <td><?= $fmtDT($r['tanggal_review'] ?? null) ?></td>
                        <td class="text-end">
                          <a href="<?= $detailUrl ?>"
                             class="btn btn-sm btn-primary <?= $sid ? '' : 'disabled' ?>"
                             <?= $sid ? '' : 'tabindex="-1" aria-disabled="true"' ?>>
                            <i class="bi bi-eye me-1"></i>Detail
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

        <!-- ABSTRAK -->
        <div class="tab-pane fade" id="ab-pane" role="tabpanel" aria-labelledby="ab-tab">
          <?php if (empty($riwayatAbstrak)): ?>
            <div class="card shadow-sm border-0">
              <div class="card-body text-center py-5">
                <div class="empty-icon"><i class="bi bi-journal-x"></i></div>
                <div class="empty-title">Belum ada riwayat Abstrak</div>
                <div class="empty-subtitle">Riwayat abstrak yang kamu review akan muncul di sini.</div>
              </div>
            </div>
          <?php else: ?>
            <div class="card shadow-sm border-0">
              <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" id="tbl-ab">
                  <thead class="table-light">
                    <tr>
                      <th>Judul</th>
                      <th class="d-none d-lg-table-cell">Event</th>
                      <th>Presenter</th>
                      <th class="d-none d-xl-table-cell">Kategori</th>
                      <th>Keputusan</th>
                      <th>Tgl Review</th>
                      <th class="text-end">Aksi</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php foreach ($riwayatAbstrak as $r):
                      $search = strtolower(trim(($r['judul'] ?? '').' '.($r['nama_lengkap'] ?? '').' '.($r['nama_kategori'] ?? '').' '.($r['event_title'] ?? '')));
                      $dec    = strtolower((string)($r['keputusan'] ?? 'pending'));
                      $aid    = (int)($r['id_abstrak'] ?? 0);
                      $detailUrl = $aid ? site_url('reviewer/abstrak/'.$aid) : '#';
                    ?>
                      <tr class="row-item" data-kind="ab" data-search="<?= esc($search) ?>" data-status="<?= esc($dec) ?>">
                        <td>
                          <div class="fw-semibold text-dark"><?= esc($r['judul'] ?? '-') ?></div>
                          <div class="small text-muted d-lg-none"><i class="bi bi-calendar-event me-1"></i><?= esc($r['event_title'] ?? '-') ?></div>
                        </td>
                        <td class="d-none d-lg-table-cell">
                          <span class="badge bg-info-subtle text-info px-3 py-2">
                            <i class="bi bi-calendar-event me-1"></i><?= esc($r['event_title'] ?? '-') ?>
                          </span>
                        </td>
                        <td>
                          <div class="d-flex align-items-center gap-2">
                            <div class="user-avatar bg-primary-subtle text-primary"><i class="bi bi-person"></i></div>
                            <div class="text-dark"><?= esc($r['nama_lengkap'] ?? '-') ?></div>
                          </div>
                        </td>
                        <td class="d-none d-xl-table-cell text-muted"><?= esc($r['nama_kategori'] ?? '-') ?></td>
                        <td><span class="badge <?= $badge($r['keputusan'] ?? 'pending') ?> px-3 py-2"><?= ucfirst($r['keputusan'] ?? 'pending') ?></span></td>
                        <td><?= $fmtDT($r['tanggal_review'] ?? null) ?></td>
                        <td class="text-end">
                          <a href="<?= $detailUrl ?>"
                             class="btn btn-sm btn-primary <?= $aid ? '' : 'disabled' ?>"
                             <?= $aid ? '' : 'tabindex="-1" aria-disabled="true"' ?>>
                            <i class="bi bi-eye me-1"></i>Detail
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
      </div>

      <!-- NO RESULT -->
      <div id="noResults" class="text-center py-5 d-none">
        <div class="empty-icon"><i class="bi bi-search"></i></div>
        <div class="empty-title">Tidak ada hasil ditemukan</div>
        <div class="empty-subtitle">Coba ubah kata kunci atau filter keputusan</div>
      </div>

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
}
body{
  background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
  font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}
.header-section{
  background: linear-gradient(135deg, var(--primary-color) 0%, #1e40af 100%);
  color:#fff; padding:24px; border-radius:12px;
  box-shadow:0 4px 20px rgba(37,99,235,.15);
}
.welcome-text{ color:#fff; font-weight:700; font-size:1.6rem; margin:0; }

.card{ background:#fff; border:none; border-radius:12px; box-shadow:0 4px 20px rgba(0,0,0,.08); }
.card-header{ background:#f8fafc; border-bottom:1px solid #e2e8f0; border-radius:12px 12px 0 0 !important; padding:14px 18px; }

.table{ border-radius:0 0 12px 12px; overflow:hidden; }
.table thead th{ background:#f8fafc; border-bottom:1px solid #e2e8f0; font-weight:600; color:#374151; padding:14px; }
.table tbody td{ padding:14px; vertical-align:middle; }
.badge{ border-radius:6px; font-weight:500; font-size:.8rem; }
.bg-primary-subtle{ background-color: rgba(37,99,235,.1) !important; }
.bg-info-subtle{ background-color: rgba(6,182,212,.12) !important; }
.text-info{ color: var(--info-color) !important; }
.bg-success{ background-color: var(--success-color) !important; }
.bg-warning{ background-color: var(--warning-color) !important; }
.bg-danger{ background-color: var(--danger-color) !important; }
.bg-secondary{ background-color: var(--secondary-color) !important; }

.user-avatar{ width:36px; height:36px; border-radius:8px; display:flex; align-items:center; justify-content:center; }

.nav-tabs .nav-link{ border:0; font-weight:600; }
.nav-tabs .nav-link.active{
  background:#fff; border-radius:10px 10px 0 0; border:1px solid #e5e7eb; border-bottom-color:#fff;
}

.empty-icon{ font-size:3rem; color:#9ca3af; }
.empty-title{ font-size:1.1rem; font-weight:600; color:#4b5563; }
.empty-subtitle{ color:#9ca3af; }

.form-control, .form-select{ border:2px solid #e2e8f0; border-radius:8px; }
.form-control:focus, .form-select:focus{ border-color:var(--primary-color); box-shadow:0 0 0 .2rem rgba(37,99,235,.1); }
.input-group-text{ border-radius:8px 0 0 8px; }

.btn{ border-radius:8px; font-weight:500; }
.btn-primary{ background-color:#2563eb; border-color:#2563eb; color:#fff; }
.btn-primary:hover{ filter:brightness(0.95); }
.btn-light{ background:#fff; color:#1f2937; }
.btn-outline-secondary{ border-color:#d1d5db; color:#6b7280; }
.btn-outline-secondary:hover{ background-color:#f9fafb; border-color:#9ca3af; color:#374151; }

@media (max-width: 575.98px){
  .container-fluid{ padding-left:.5rem!important; padding-right:.5rem!important; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function(){
  const q = document.getElementById('q');
  const st = document.getElementById('status');
  const reset = document.getElementById('reset');
  const noRes = document.getElementById('noResults');

  function apply(){
    const query = (q?.value || '').toLowerCase().trim();
    const stat  = (st?.value || '').toLowerCase().trim();

    const rows = Array.from(document.querySelectorAll('.row-item'));
    let shown = 0;
    rows.forEach(tr=>{
      const s = tr.dataset.search || '';
      const t = tr.dataset.status || '';
      const ok = (!query || s.includes(query)) && (!stat || t === stat);
      tr.style.display = ok ? '' : 'none';
      if (ok) shown++;
    });

    if (noRes) noRes.classList.toggle('d-none', shown > 0);
  }

  q?.addEventListener('input', apply);
  st?.addEventListener('change', apply);
  reset?.addEventListener('click', ()=>{
    if (q) q.value = '';
    if (st) st.value = '';
    apply();
    q?.focus();
  });

  apply();
});
</script>