<?php
  $title = 'Riwayat Review';
  /** expects: $riwayat = [
   *   ['judul','nama_lengkap','nama_kategori','keputusan','tanggal_review', ...]
   * ]
   */
  $rows = $riwayat ?? [];

  // helper kecil untuk badge keputusan
  $badgeCls = function($k){
    $k = strtolower(trim((string)$k));
    return match(true){
      in_array($k,['accepted','diterima'])       => 'bg-success',
      in_array($k,['revisi','revision'])         => 'bg-warning text-dark',
      in_array($k,['rejected','ditolak'])        => 'bg-danger',
      default                                    => 'bg-secondary'
    };
  };
  $fmtDT = fn($s)=> $s ? date('d M Y H:i', strtotime($s)) : '-';
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_reviewer') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:var(--topbar-h);">
    <div class="container-fluid p-3 p-md-4">

      <!-- HERO -->
      <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-3">
        <div>
          <h3 class="mb-0 d-flex align-items-center gap-2">
            <i class="bi bi-journal-check text-primary"></i>
            Riwayat Review
          </h3>
          <small class="text-muted">Semua review yang pernah kamu kerjakan.</small>
        </div>
        <span class="badge bg-primary-subtle text-primary"><?= count($rows) ?> item</span>
      </div>

      <!-- Toolbar: search + filter -->
      <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
          <div class="row g-2 g-md-3 align-items-center">
            <div class="col-12 col-md-6">
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="search" id="q" class="form-control" placeholder="Cari judul / penulis / kategori...">
              </div>
            </div>
            <div class="col-6 col-md-3">
              <select id="fStatus" class="form-select">
                <option value="">Semua Keputusan</option>
                <option value="accepted">Accepted / Diterima</option>
                <option value="revisi">Revisi</option>
                <option value="rejected">Rejected / Ditolak</option>
                <option value="pending">Pending / Menunggu</option>
              </select>
            </div>
            <div class="col-6 col-md-3 text-md-end">
              <button id="btnReset" class="btn btn-light">
                <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- DESKTOP: Table -->
      <div class="d-none d-md-block">
        <div class="card shadow-sm border-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="tbl">
              <thead class="table-light">
                <tr>
                  <th style="width:40%;">Judul</th>
                  <th style="width:18%;">Penulis</th>
                  <th style="width:18%;">Kategori</th>
                  <th style="width:14%;">Keputusan</th>
                  <th style="width:14%;">Tgl Review</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($rows)): ?>
                  <?php foreach ($rows as $r): ?>
                    <?php
                      $k = strtolower(trim((string)($r['keputusan'] ?? '')));
                      $kKey = $k ?: 'pending';
                      $hay = strtolower(
                        ($r['judul'] ?? '').' '.($r['nama_lengkap'] ?? '').' '.($r['nama_kategori'] ?? '')
                      );
                    ?>
                    <tr
                      data-hay="<?= esc($hay, 'attr') ?>"
                      data-status="<?= esc($kKey, 'attr') ?>"
                    >
                      <td>
                        <div class="fw-semibold"><?= esc($r['judul'] ?? '-') ?></div>
                        <div class="small text-muted d-md-none"><?= esc($fmtDT($r['tanggal_review'] ?? null)) ?></div>
                      </td>
                      <td><?= esc($r['nama_lengkap'] ?? '-') ?></td>
                      <td><?= esc($r['nama_kategori'] ?? '-') ?></td>
                      <td><span class="badge <?= $badgeCls($k) ?>"><?= esc(ucfirst($r['keputusan'] ?? 'Pending')) ?></span></td>
                      <td><?= esc($fmtDT($r['tanggal_review'] ?? null)) ?></td>
                    </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr><td colspan="5" class="text-center text-muted py-4">Belum ada riwayat review.</td></tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- MOBILE: Cards -->
      <div class="d-block d-md-none">
        <?php if (!empty($rows)): ?>
          <div id="listCards" class="vstack gap-2">
            <?php foreach ($rows as $r): ?>
              <?php
                $k = strtolower(trim((string)($r['keputusan'] ?? '')));
                $kKey = $k ?: 'pending';
                $hay = strtolower(
                  ($r['judul'] ?? '').' '.($r['nama_lengkap'] ?? '').' '.($r['nama_kategori'] ?? '')
                );
              ?>
              <div class="card shadow-sm border-0"
                   data-hay="<?= esc($hay, 'attr') ?>"
                   data-status="<?= esc($kKey, 'attr') ?>">
                <div class="card-body">
                  <div class="d-flex justify-content-between align-items-start">
                    <div class="me-2">
                      <div class="fw-semibold"><?= esc($r['judul'] ?? '-') ?></div>
                      <div class="small text-muted">
                        <?= esc($r['nama_lengkap'] ?? '-') ?> · <?= esc($r['nama_kategori'] ?? '-') ?>
                      </div>
                    </div>
                    <span class="badge <?= $badgeCls($k) ?>"><?= esc(ucfirst($r['keputusan'] ?? 'Pending')) ?></span>
                  </div>
                  <div class="small text-muted mt-1">
                    <?= esc($fmtDT($r['tanggal_review'] ?? null)) ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="p-4 text-center border rounded-3 bg-light-subtle">
            <div class="mb-2"><i class="bi bi-inbox fs-3 text-secondary"></i></div>
            <div class="fw-semibold">Belum ada riwayat review</div>
          </div>
        <?php endif; ?>
      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<style>
  /* Sentuhan kecil biar konsisten */
  .table tbody tr:hover { background: #f8fafc; }
</style>

<script>
(function(){
  const q        = document.getElementById('q');
  const fStatus  = document.getElementById('fStatus');
  const btnReset = document.getElementById('btnReset');

  const rows = Array.from(document.querySelectorAll('#tbl tbody tr'));
  const cards= Array.from(document.querySelectorAll('#listCards .card'));

  function match(el, text, status){
    const hay = (el.getAttribute('data-hay')||'').toLowerCase();
    const st  = (el.getAttribute('data-status')||'').toLowerCase();
    const okText = !text || hay.includes(text);
    const okSt   = !status || st === status;
    return okText && okSt;
  }

  function applyFilter(){
    const text = (q?.value||'').trim().toLowerCase();
    const st   = (fStatus?.value||'').trim().toLowerCase();

    let anyRow = false, anyCard = false;

    rows.forEach(tr=>{
      const vis = match(tr, text, st);
      tr.style.display = vis ? '' : 'none';
      if (vis) anyRow = true;
    });

    cards.forEach(card=>{
      const vis = match(card, text, st);
      card.classList.toggle('d-none', !vis);
      if (vis) anyCard = true;
    });

    // jika semua tersembunyi di table, tampilkan baris kosong info (opsional)
    const tbody = document.querySelector('#tbl tbody');
    if (tbody && rows.length){
      let emptyRow = tbody.querySelector('tr.__empty');
      if (!anyRow){
        if (!emptyRow){
          emptyRow = document.createElement('tr');
          emptyRow.className = '__empty';
          emptyRow.innerHTML = `<td colspan="5" class="text-center text-muted py-4">Tidak ada hasil.</td>`;
          tbody.appendChild(emptyRow);
        }
      } else if (emptyRow){
        emptyRow.remove();
      }
    }
  }

  q?.addEventListener('input', applyFilter);
  fStatus?.addEventListener('change', applyFilter);
  btnReset?.addEventListener('click', ()=>{
    if (q) q.value = '';
    if (fStatus) fStatus.value = '';
    applyFilter();
  });

  // init
  applyFilter();
})();
</script>
