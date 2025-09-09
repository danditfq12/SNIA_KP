<?php
  $title = 'Daftar Abstrak';
  /** @var array $abstrak  -> list semua item
   *  @var array $byEvent  -> grouped per event
   *  @var array $eventOptions -> [event_id => event_title]
   */
  $abstrak      = $abstrak      ?? [];
  $byEvent      = $byEvent      ?? [];
  $eventOptions = $eventOptions ?? [];

  $fmtDate = fn($d)=> $d ? date('d M Y', strtotime($d)) : '-';
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_reviewer') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-4">

      <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <h3 class="mb-0">Daftar Abstrak</h3>

        <!-- Toggle view -->
        <div class="btn-group" role="group" aria-label="View switch">
          <button class="btn btn-outline-primary btn-sm active" id="viewAllBtn">Semua</button>
          <button class="btn btn-outline-primary btn-sm" id="viewEventBtn">Per Event</button>
        </div>
      </div>

      <!-- Filter bar (berlaku untuk tampilan "Semua") -->
      <div class="card shadow-sm border-0 mb-3">
        <div class="card-body">
          <div class="row g-2">
            <div class="col-12 col-md-4">
              <div class="input-group">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input type="text" id="q" class="form-control" placeholder="Cari judul / penulis...">
              </div>
            </div>
            <div class="col-6 col-md-4">
              <select id="filterEvent" class="form-select">
                <option value="">Semua Event</option>
                <?php foreach ($eventOptions as $eid => $etitle): ?>
                  <option value="<?= (int)$eid ?>"><?= esc($etitle) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-6 col-md-4">
              <select id="filterStatus" class="form-select">
                <option value="">Semua Status</option>
                <option value="pending">Pending</option>
                <option value="diterima">Diterima</option>
                <option value="ditolak">Ditolak</option>
                <option value="revisi">Revisi</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- VIEW: Semua -->
      <div id="viewAll">
        <div class="table-responsive">
          <table class="table table-hover align-middle" id="tblAll">
            <thead class="table-light">
              <tr>
                <th style="width:42%">Judul</th>
                <th style="width:22%">Event</th>
                <th style="width:14%">Penulis</th>
                <th style="width:12%">Kategori</th>
                <th style="width:10%">Status</th>
                <th style="width:90px">Aksi</th>
              </tr>
            </thead>
            <tbody>
              <?php if(!empty($abstrak)): ?>
                <?php foreach($abstrak as $a): ?>
                  <?php
                    $sid    = strtolower((string)($a['status'] ?? ''));
                    $badgeC = match ($sid) {
                      'diterima' => 'success',
                      'ditolak'  => 'danger',
                      'revisi'   => 'warning',
                      default    => 'secondary',
                    };
                  ?>
                  <tr
                    data-event-id="<?= (int)($a['event_id'] ?? 0) ?>"
                    data-status="<?= esc($sid) ?>"
                    data-text="<?= esc(strtolower(($a['judul'] ?? '').' '.$a['nama_lengkap'])) ?>"
                  >
                    <td class="text-truncate" style="max-width:420px;">
                      <div class="fw-semibold"><?= esc($a['judul']) ?></div>
                      <div class="small text-muted">Uploaded: <?= esc($a['tanggal_upload'] ? $fmtDate($a['tanggal_upload']) : '-') ?></div>
                    </td>
                    <td>
                      <span class="badge bg-info-subtle text-info">
                        <i class="bi bi-calendar-event me-1"></i><?= esc($a['event_title'] ?? 'Event') ?>
                      </span>
                    </td>
                    <td><?= esc($a['nama_lengkap']) ?></td>
                    <td><?= esc($a['nama_kategori'] ?? '-') ?></td>
                    <td><span class="badge bg-<?= $badgeC ?>"><?= ucfirst(esc($sid ?: '-')) ?></span></td>
                    <td>
                      <a href="<?= site_url('reviewer/abstrak/'.(int)$a['id_abstrak']) ?>" class="btn btn-sm btn-primary">Detail</a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php else: ?>
                <tr><td colspan="6" class="text-center text-muted">Belum ada abstrak yang ditugaskan.</td></tr>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <!-- VIEW: Per Event -->
      <div id="viewEvent" class="d-none">
        <?php if (!empty($byEvent)): ?>
          <div class="accordion" id="evAcc">
            <?php $i=0; foreach ($byEvent as $ev): $i++; ?>
              <div class="accordion-item shadow-sm mb-2 border-0 rounded">
                <h2 class="accordion-header" id="h<?= $i ?>">
                  <button class="accordion-button <?= $i>1?'collapsed':'' ?>" type="button"
                          data-bs-toggle="collapse" data-bs-target="#c<?= $i ?>" aria-expanded="<?= $i===1?'true':'false' ?>" aria-controls="c<?= $i ?>">
                    <div class="w-100 d-flex flex-wrap align-items-center justify-content-between gap-2">
                      <div class="fw-semibold">
                        <i class="bi bi-calendar3 me-1"></i><?= esc($ev['event_title'] ?? 'Event') ?>
                        <span class="text-muted small ms-2">
                          <?= esc($ev['event_date'] ? $fmtDate($ev['event_date']) : '-') ?> · <?= esc($ev['event_time'] ?? '-') ?>
                        </span>
                      </div>
                      <!-- ringkasan status -->
                      <div class="d-flex flex-wrap gap-1">
                        <span class="badge bg-secondary-subtle text-secondary">Total: <?= count($ev['items'] ?? []) ?></span>
                        <?php if (($ev['summary']['pending'] ?? 0) > 0): ?>
                          <span class="badge bg-secondary">Pending: <?= (int)$ev['summary']['pending'] ?></span>
                        <?php endif; ?>
                        <?php if (($ev['summary']['revisi'] ?? 0) > 0): ?>
                          <span class="badge bg-warning text-dark">Revisi: <?= (int)$ev['summary']['revisi'] ?></span>
                        <?php endif; ?>
                        <?php if (($ev['summary']['diterima'] ?? 0) > 0): ?>
                          <span class="badge bg-success">Diterima: <?= (int)$ev['summary']['diterima'] ?></span>
                        <?php endif; ?>
                        <?php if (($ev['summary']['ditolak'] ?? 0) > 0): ?>
                          <span class="badge bg-danger">Ditolak: <?= (int)$ev['summary']['ditolak'] ?></span>
                        <?php endif; ?>
                      </div>
                    </div>
                  </button>
                </h2>
                <div id="c<?= $i ?>" class="accordion-collapse collapse <?= $i===1?'show':'' ?>" aria-labelledby="h<?= $i ?>" data-bs-parent="#evAcc">
                  <div class="accordion-body">
                    <?php if (!empty($ev['items'])): ?>
                      <div class="list-group list-group-flush">
                        <?php foreach ($ev['items'] as $a): ?>
                          <?php
                            $sid    = strtolower((string)($a['status'] ?? ''));
                            $badgeC = match ($sid) {
                              'diterima' => 'success',
                              'ditolak'  => 'danger',
                              'revisi'   => 'warning',
                              default    => 'secondary',
                            };
                          ?>
                          <div class="list-group-item px-0">
                            <div class="d-flex justify-content-between align-items-start">
                              <div class="me-2">
                                <div class="fw-semibold"><?= esc($a['judul']) ?></div>
                                <div class="small text-muted">
                                  <?= esc($a['nama_lengkap']) ?> · <?= esc($a['nama_kategori'] ?? '-') ?> ·
                                  Upload: <?= esc($a['tanggal_upload'] ? $fmtDate($a['tanggal_upload']) : '-') ?>
                                </div>
                              </div>
                              <div class="d-flex align-items-center gap-2">
                                <span class="badge bg-<?= $badgeC ?>"><?= ucfirst(esc($sid ?: '-')) ?></span>
                                <a href="<?= site_url('reviewer/abstrak/'.(int)$a['id_abstrak']) ?>" class="btn btn-sm btn-outline-primary">Detail</a>
                              </div>
                            </div>
                          </div>
                        <?php endforeach; ?>
                      </div>
                    <?php else: ?>
                      <div class="text-muted">Tidak ada abstrak pada event ini.</div>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="p-4 text-center border rounded-3 bg-light-subtle">
            <div class="mb-2"><i class="bi bi-journal-x fs-3 text-secondary"></i></div>
            <div class="fw-semibold">Belum ada abstrak yang ditugaskan.</div>
          </div>
        <?php endif; ?>
      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<style>
  /* Minor polish */
  .accordion-item { border-radius: 12px; overflow: hidden; }
  .accordion-button:not(.collapsed){ background: #f8fafc; }
  #tblAll tbody tr{ transition: background .15s ease; }
  #tblAll tbody tr:hover{ background: #f9fbff; }
</style>

<script>
(function(){
  const viewAllBtn  = document.getElementById('viewAllBtn');
  const viewEventBtn= document.getElementById('viewEventBtn');
  const viewAll     = document.getElementById('viewAll');
  const viewEvent   = document.getElementById('viewEvent');

  const qInput      = document.getElementById('q');
  const fEvent      = document.getElementById('filterEvent');
  const fStatus     = document.getElementById('filterStatus');
  const rows        = Array.from(document.querySelectorAll('#tblAll tbody tr'));

  // Remember last view
  const KEY = 'rv_abstrak_view';
  function setView(v){
    if(v==='event'){
      viewEvent.classList.remove('d-none');
      viewAll.classList.add('d-none');
      viewEventBtn.classList.add('active');
      viewAllBtn.classList.remove('active');
      localStorage.setItem(KEY,'event');
    }else{
      viewAll.classList.remove('d-none');
      viewEvent.classList.add('d-none');
      viewAllBtn.classList.add('active');
      viewEventBtn.classList.remove('active');
      localStorage.setItem(KEY,'all');
    }
  }
  viewAllBtn?.addEventListener('click', ()=> setView('all'));
  viewEventBtn?.addEventListener('click', ()=> setView('event'));
  setView(localStorage.getItem(KEY) || 'all');

  function applyFilter(){
    const q   = (qInput?.value || '').trim().toLowerCase();
    const ev  = fEvent?.value || '';
    const sts = (fStatus?.value || '').toLowerCase();

    rows.forEach(tr=>{
      const txt = tr.getAttribute('data-text') || '';
      const eid = tr.getAttribute('data-event-id') || '';
      const s   = (tr.getAttribute('data-status') || '').toLowerCase();

      const passQ  = !q || txt.includes(q);
      const passE  = !ev || ev === eid;
      const passS  = !sts || sts === s;

      tr.style.display = (passQ && passE && passS) ? '' : 'none';
    });
  }
  qInput?.addEventListener('input', applyFilter);
  fEvent?.addEventListener('change', applyFilter);
  fStatus?.addEventListener('change', applyFilter);
})();
</script>
