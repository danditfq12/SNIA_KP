<?php
$title        = $title ?? 'Tugas Full Paper';
$rows         = $rows  ?? [];
$eventOptions = $eventOptions ?? [];

$fmt = fn($d,$t=false)=> $d ? date($t?'d M Y H:i':'d M Y', strtotime($d)) : '—';
$badgeRev = fn($s)=> match(strtolower((string)$s)){
  'diterima' => 'success',
  'revisi'   => 'warning text-dark',
  'ditolak'  => 'danger',
  default    => 'secondary'
};
$badgeAsg = fn($s)=> match(strtolower((string)$s)){
  'accepted' => 'success',
  'declined' => 'danger',
  default    => 'secondary'
};
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_reviewer') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid px-2 px-md-3 py-3">

      <div class="d-flex flex-wrap align-items-center justify-content-between bg-gradient rounded-3 p-3 p-md-4 mb-3"
           style="background:linear-gradient(135deg,#2563eb,#1e40af);color:#fff;">
        <div>
          <h3 class="m-0"><?= esc($title) ?></h3>
          <small class="text-white-50">Terima / Tolak penugasan langsung dari daftar</small>
        </div>
        <div class="d-none d-md-flex gap-2">
          <a href="<?= site_url('reviewer/dashboard') ?>" class="btn btn-light">
            <i class="bi bi-speedometer2 me-1"></i>Dashboard
          </a>
        </div>
      </div>

      <div class="card shadow-sm mb-3">
        <div class="card-body d-flex flex-wrap gap-2 align-items-center">
          <div class="input-group" style="max-width:360px;">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="text" class="form-control" id="q" placeholder="Cari judul, penulis, atau kategori...">
          </div>

          <div class="ms-0 ms-md-2">
            <select class="form-select" id="eventFilter" style="min-width:240px">
              <option value="">Semua Event</option>
              <?php foreach ($eventOptions as $id=>$name): ?>
                <option value="<?= (int)$id ?>"><?= esc($name) ?></option>
              <?php endforeach; ?>
            </select>
          </div>

          <div class="ms-0 ms-md-2">
            <select class="form-select" id="statusFilter" style="min-width:200px">
              <option value="">Semua Status Review</option>
              <option value="menunggu">Menunggu</option>
              <option value="diterima">Diterima</option>
              <option value="revisi">Revisi</option>
              <option value="ditolak">Ditolak</option>
            </select>
          </div>

          <div class="ms-auto d-flex gap-2">
            <button class="btn btn-outline-secondary" id="resetBtn">
              <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
            </button>
          </div>
        </div>
      </div>

      <div class="card shadow-sm">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h5 class="card-title m-0">
            <i class="bi bi-journal-text me-2 text-primary"></i>Daftar Tugas
          </h5>
          <span class="badge bg-primary-subtle text-primary" id="countBadge"><?= number_format(count($rows)) ?> item</span>
        </div>
        <div class="card-body p-0">
          <?php if (empty($rows)): ?>
            <div class="text-center text-muted p-5">
              <div class="fs-1 mb-2"><i class="bi bi-inbox"></i></div>
              <div class="fw-semibold">Belum ada tugas full paper</div>
              <div class="small">Penugasan baru akan tampil otomatis di sini.</div>
            </div>
          <?php else: ?>
            <div class="table-responsive">
              <table class="table align-middle mb-0" id="taskTable">
                <thead class="table-light">
                  <tr>
                    <th style="width: 40%;">Full Paper</th>
                    <th class="d-none d-md-table-cell">Event</th>
                    <th class="d-none d-lg-table-cell">Penulis</th>
                    <th class="d-none d-xl-table-cell">Kategori</th>
                    <th>Status Review</th>
                    <th>Status Tugas</th>
                    <th class="text-end" style="width:260px;">Aksi</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ($rows as $r): ?>
                    <?php
                      $id    = (int)($r['id'] ?? 0);
                      $judul = $r['title'] ?? '—';
                      $txt   = strtolower(trim(($r['title'] ?? '').' '.($r['nama_lengkap'] ?? '').' '.($r['nama_kategori'] ?? '')));
                      $eid   = (int)($r['event_id'] ?? 0);
                      $rev   = strtolower($r['review_status'] ?? 'menunggu');
                      $asg   = strtolower($r['asg_status_norm'] ?? 'pending');
                    ?>
                    <tr data-search="<?= esc($txt) ?>"
                        data-event="<?= $eid ?>"
                        data-status="<?= esc($rev) ?>">
                      <td>
                        <div class="fw-semibold"><?= esc($judul) ?></div>
                        <div class="small text-muted d-flex flex-wrap gap-2">
                          <span><i class="bi bi-calendar2-plus me-1"></i><?= $fmt($r['tanggal_upload'] ?? null, true) ?></span>
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
                      <td><span class="badge bg-<?= $badgeRev($rev) ?> px-3 py-2"><?= ucfirst($rev) ?></span></td>
                      <td><span class="badge bg-<?= $badgeAsg($asg) ?> px-3 py-2"><?= ucfirst($asg) ?></span></td>
                      <td class="text-end">
                        <a href="<?= site_url('reviewer/fullpaper/'.$id) ?>" class="btn btn-sm btn-primary">
                          <i class="bi bi-eye"></i> Tinjau
                        </a>

                        <?php if ($asg === 'pending'): ?>
                          <form class="d-inline" method="post" action="<?= site_url('reviewer/fullpaper/action') ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="id" value="<?= $id ?>">
                            <input type="hidden" name="action" value="accept">
                            <button class="btn btn-sm btn-success">
                              <i class="bi bi-check-lg me-1"></i>Terima
                            </button>
                          </form>

                          <button class="btn btn-sm btn-outline-danger btn-decline"
                                  data-id="<?= $id ?>"
                                  data-title="<?= esc($judul,'attr') ?>">
                            <i class="bi bi-x-lg me-1"></i>Tolak
                          </button>
                        <?php elseif ($asg === 'accepted'): ?>
                          <span class="text-success small"><i class="bi bi-check2-circle me-1"></i>Sudah diterima</span>
                        <?php elseif ($asg === 'declined'): ?>
                          <span class="text-danger small"><i class="bi bi-x-octagon me-1"></i>Ditolak</span>
                        <?php endif; ?>
                      </td>
                    </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
            </div>
          <?php endif; ?>
        </div>
      </div>

    </div>

    <!-- MODAL: Decline reason -->
    <div class="modal fade" id="declineModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <form method="post" action="<?= site_url('reviewer/fullpaper/action') ?>" class="modal-content" id="declineForm">
          <?= csrf_field() ?>
          <input type="hidden" name="id" id="declId">
          <input type="hidden" name="action" value="decline">
          <div class="modal-header">
            <h6 class="modal-title">
              <i class="bi bi-x-octagon text-danger me-2"></i>Tolak Penugasan
            </h6>
            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <div class="small text-muted mb-2" id="declTitle"></div>
            <label class="form-label">Alasan Penolakan <span class="text-danger">*</span></label>
            <textarea name="reason" id="declReason" rows="4" class="form-control"
                      required minlength="5"
                      placeholder="Tuliskan alasan penolakan secara singkat dan jelas..."></textarea>
            <div class="form-text">Penolakan tanpa alasan tidak diperbolehkan.</div>
          </div>
          <div class="modal-footer">
            <button class="btn btn-light" type="button" data-bs-dismiss="modal">Batal</button>
            <button class="btn btn-danger" type="submit">
              <i class="bi bi-x-lg me-1"></i>Tolak
            </button>
          </div>
        </form>
      </div>
    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<style>
.table td, .table th{ vertical-align: middle; }
.bg-primary-subtle{ background:#e8f0ff!important; color:#1e40af!important; }
</style>

<script>
(function(){
  // filter
  const q = document.getElementById('q');
  const ev = document.getElementById('eventFilter');
  const st = document.getElementById('statusFilter');
  const reset = document.getElementById('resetBtn');
  const rows = [...document.querySelectorAll('#taskTable tbody tr')];
  const badge = document.getElementById('countBadge');

  const apply = ()=>{
    const qv = (q?.value||'').trim().toLowerCase();
    const evv = (ev?.value||'').trim();
    const stv = (st?.value||'').trim();
    let shown = 0;
    rows.forEach(tr=>{
      const okQ = !qv || (tr.getAttribute('data-search')||'').includes(qv);
      const okE = !evv || tr.getAttribute('data-event') === evv;
      const okS = !stv || tr.getAttribute('data-status') === stv;
      const vis = okQ && okE && okS;
      tr.style.display = vis ? '' : 'none';
      if (vis) shown++;
    });
    if (badge) badge.textContent = shown + ' item';

    let empty = document.getElementById('emptyRow');
    if (!shown){
      if (!empty){
        empty = document.createElement('tr');
        empty.id = 'emptyRow';
        empty.innerHTML = `<td colspan="7" class="py-5 text-center text-muted">
          <i class="bi bi-search fs-1 d-block mb-2"></i>Tidak ada hasil
        </td>`;
        document.querySelector('#taskTable tbody').appendChild(empty);
      }
    } else {
      empty?.remove();
    }
  };
  [q,ev,st].forEach(el=>el?.addEventListener('input', apply));
  [ev,st].forEach(el=>el?.addEventListener('change', apply));
  reset?.addEventListener('click', ()=>{ q.value=''; ev.value=''; st.value=''; apply(); });
  apply();

  // decline modal
  const modal = new bootstrap.Modal(document.getElementById('declineModal'));
  document.querySelectorAll('.btn-decline').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      document.getElementById('declId').value = btn.getAttribute('data-id');
      document.getElementById('declTitle').textContent = 'Menolak penugasan: "' + (btn.getAttribute('data-title')||'') + '"';
      document.getElementById('declReason').value = '';
      modal.show();
    });
  });

  // validation
  document.getElementById('declineForm')?.addEventListener('submit', (e)=>{
    const reason = (document.getElementById('declReason').value || '').trim();
    if (reason.length < 5){
      e.preventDefault();
      alert('Alasan penolakan minimal 5 karakter.');
    }
  });
})();
</script>
