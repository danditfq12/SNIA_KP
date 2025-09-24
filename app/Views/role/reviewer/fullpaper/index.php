<?php
// ====== DEFAULT VARS ======
$title         = $title ?? 'Daftar Full Paper';
$rows          = $rows ?? [];       // dari controller FullPaper::index()
$byEvent       = $byEvent ?? [];    // opsional kalau mau group per event
$eventOptions  = $eventOptions ?? [];

// Helpers
$fmt = fn($d) => $d ? date('d M Y', strtotime($d)) : '-';
$badge = function($status) {
  $s = strtolower((string)$status);
  return match ($s) {
    'accepted' => 'success',
    'revision' => 'warning',
    'rejected' => 'danger',
    'uploaded' => 'info',
    default    => 'secondary',
  };
};

// Normalisasi baris agar aman dipakai di view
$items = array_map(function($r){
  return [
    'id'        => (int)($r['id'] ?? $r['submission_id'] ?? 0),
    'title'     => $r['title'] ?? 'Untitled',
    'event_id'  => $r['event_id'] ?? null,
    'status'    => strtoupper($r['full_paper_status'] ?? 'NONE'),
    'uploaded'  => $r['full_paper_uploaded_at'] ?? null,
    'path'      => $r['full_paper_path'] ?? null,
  ];
}, $rows);
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_reviewer') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <!-- HEADER SECTION -->
      <div class="header-section header-blue d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
        <div>
          <h3 class="welcome-text mb-1">
            <i class="bi bi-journal-text me-2"></i>Daftar Full Paper
          </h3>
          <div class="text-white-50">Kelola tugas review full paper yang ditugaskan kepada Anda</div>
        </div>

        <!-- Toggle tampilan -->
        <div class="btn-group" role="group" aria-label="View switch">
          <button class="btn btn-light btn-sm active" id="viewAllBtn" type="button">
            <i class="bi bi-table me-1"></i>Semua
          </button>
          <button class="btn btn-outline-light btn-sm" id="viewEventBtn" type="button">
            <i class="bi bi-collection me-1"></i>Per Event
          </button>
        </div>
      </div>

      <!-- FILTERS -->
      <div class="card shadow-sm border-0 mb-4">
        <div class="card-body">
          <div class="row g-3">
            <div class="col-12 col-md-4">
              <label class="form-label small fw-medium">Pencarian</label>
              <div class="input-group">
                <span class="input-group-text bg-light border-end-0">
                  <i class="bi bi-search text-muted"></i>
                </span>
                <input type="text" id="searchInput" class="form-control border-start-0" placeholder="Cari judul...">
              </div>
            </div>
            <div class="col-6 col-md-4">
              <label class="form-label small fw-medium">Event</label>
              <select id="filterEvent" class="form-select">
                <option value="">Semua Event</option>
                <?php foreach ($eventOptions as $eventId => $eventTitle): ?>
                  <option value="<?= esc($eventId) ?>"><?= esc($eventTitle) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="col-6 col-md-4">
              <label class="form-label small fw-medium">Status Full Paper</label>
              <select id="filterStatus" class="form-select">
                <option value="">Semua Status</option>
                <option value="uploaded">Uploaded</option>
                <option value="accepted">Accepted</option>
                <option value="revision">Revision</option>
                <option value="rejected">Rejected</option>
                <option value="none">None</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      <!-- VIEW: TABEL SEMUA -->
      <div id="viewAll" class="view-section">
        <div class="card shadow-sm border-0">
          <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" id="fpTable">
              <thead class="table-light">
                <tr>
                  <th style="width:40%;">Full Paper</th>
                  <th style="width:20%;">Event</th>
                  <th style="width:15%;">Status</th>
                  <th style="width:15%;">Uploaded</th>
                  <th style="width:10%;">Aksi</th>
                </tr>
              </thead>
              <tbody>
                <?php if (!empty($items)): ?>
                  <?php foreach ($items as $it): 
                    $status = strtolower($it['status']);
                    $search = strtolower($it['title']);
                    $badgeClass = $badge($status);
                  ?>
                  <tr class="table-row"
                      data-event-id="<?= (int)($it['event_id'] ?? 0) ?>"
                      data-status="<?= esc($status) ?>"
                      data-search="<?= esc($search) ?>">
                    <td>
                      <div class="fw-semibold text-dark mb-1"><?= esc($it['title']) ?></div>
                      <small class="text-muted">ID: #<?= (int)$it['id'] ?></small>
                    </td>
                    <td>
                      <?php if (!empty($eventOptions[$it['event_id'] ?? ''])): ?>
                        <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25">
                          <i class="bi bi-calendar-event me-1"></i><?= esc($eventOptions[$it['event_id']]) ?>
                        </span>
                      <?php else: ?>
                        <span class="text-muted">-</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <span class="badge bg-<?= $badgeClass ?>"><?= strtoupper($status ?: 'NONE') ?></span>
                    </td>
                    <td><?= esc($fmt($it['uploaded'])) ?></td>
                    <td>
                      <div class="btn-group btn-group-sm">
                        <a href="<?= site_url('reviewer/fullpaper/'.$it['id']) ?>"
                           class="btn btn-primary" title="Detail & Beri Keputusan">
                          <i class="bi bi-eye"></i>
                        </a>
                        <?php if (!empty($it['path'])): ?>
                          <a href="<?= site_url('reviewer/fullpaper/download/'.$it['id']) ?>"
                             class="btn btn-outline-secondary" title="Unduh Naskah">
                            <i class="bi bi-download"></i>
                          </a>
                        <?php endif; ?>
                      </div>
                    </td>
                  </tr>
                  <?php endforeach; ?>
                <?php else: ?>
                  <tr>
                    <td colspan="5" class="text-center py-5">
                      <div class="text-muted">
                        <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                        <div class="fw-medium">Belum ada penugasan full paper</div>
                        <small>Tugas review akan muncul di sini ketika admin menugaskannya.</small>
                      </div>
                    </td>
                  </tr>
                <?php endif; ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- VIEW: GROUP BY EVENT -->
      <div id="viewEvent" class="view-section d-none">
        <?php if (!empty($byEvent)): ?>
          <div class="accordion" id="eventAccordion">
            <?php $i=0; foreach ($byEvent as $ev): $i++; ?>
              <div class="accordion-item border-0 shadow-sm mb-3 rounded-3 overflow-hidden">
                <h2 class="accordion-header" id="h<?= $i ?>">
                  <button class="accordion-button <?= $i>1 ? 'collapsed' : '' ?> bg-light"
                          data-bs-toggle="collapse"
                          data-bs-target="#c<?= $i ?>"
                          aria-expanded="<?= $i===1 ? 'true' : 'false' ?>"
                          aria-controls="c<?= $i ?>">
                    <div class="w-100 d-flex justify-content-between align-items-center">
                      <div>
                        <div class="fw-semibold d-flex align-items-center gap-2">
                          <i class="bi bi-calendar3 text-primary"></i>
                          <?= esc($ev['event_title'] ?? 'Event') ?>
                        </div>
                        <small class="text-muted"><?= $fmt($ev['event_date'] ?? null) ?></small>
                      </div>
                      <?php
                        $sum = $ev['summary'] ?? [];
                        $total = is_countable($ev['items'] ?? null) ? count($ev['items']) : 0;
                      ?>
                      <div class="d-flex flex-wrap gap-1 me-3">
                        <span class="badge bg-secondary bg-opacity-10 text-secondary border">Total: <?= $total ?></span>
                        <?php foreach (['uploaded'=>'info','revision'=>'warning','accepted'=>'success','rejected'=>'danger'] as $k=>$cls): ?>
                          <?php if (!empty($sum[$k])): ?>
                            <span class="badge bg-<?= $cls ?>"><?= ucfirst($k) ?>: <?= (int)$sum[$k] ?></span>
                          <?php endif; ?>
                        <?php endforeach; ?>
                      </div>
                    </div>
                  </button>
                </h2>
                <div id="c<?= $i ?>" class="accordion-collapse collapse <?= $i===1 ? 'show' : '' ?>" data-bs-parent="#eventAccordion">
                  <div class="accordion-body">
                    <?php if (!empty($ev['items'])): ?>
                      <div class="vstack gap-2">
                        <?php foreach ($ev['items'] as $it):
                          $s = strtolower($it['status'] ?? 'none');
                          $bc = $badge($s);
                        ?>
                        <div class="notice">
                          <div class="d-flex align-items-start gap-2">
                            <i class="bi bi-file-earmark-richtext text-primary mt-1"></i>
                            <div class="flex-fill">
                              <div class="title"><?= esc($it['title'] ?? 'Untitled') ?></div>
                              <div class="meta">
                                Uploaded: <?= $fmt($it['uploaded'] ?? null) ?>
                              </div>
                            </div>
                            <span class="badge bg-<?= $bc ?>"><?= strtoupper($s ?: 'NONE') ?></span>
                            <a href="<?= site_url('reviewer/fullpaper/'.(int)($it['id'] ?? 0)) ?>"
                               class="btn btn-sm btn-outline-primary">
                              <i class="bi bi-eye me-1"></i>Detail
                            </a>
                          </div>
                        </div>
                        <?php endforeach; ?>
                      </div>
                    <?php else: ?>
                      <div class="text-center py-4 text-muted">
                        <i class="bi bi-journal-x fs-2 d-block mb-2"></i>
                        <div>Tidak ada full paper untuk event ini</div>
                      </div>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php else: ?>
          <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
              <i class="bi bi-journal-x fs-1 text-muted d-block mb-3"></i>
              <h5 class="text-muted">Belum ada penugasan per event</h5>
              <p class="text-muted mb-0">Tugas review akan dikelompokkan di sini jika tersedia.</p>
            </div>
          </div>
        <?php endif; ?>
      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<style>
:root{
  --primary-color:#2563eb; --success-color:#10b981; --warning-color:#f59e0b; --danger-color:#ef4444; --info-color:#06b6d4;
}
body{ background:linear-gradient(135deg,#f8fafc 0%,#e2e8f0 100%); font-family:'Segoe UI',Tahoma,Geneva,Verdana,sans-serif; }
.header-section.header-blue{
  background:linear-gradient(135deg,var(--primary-color) 0%,#1e40af 100%); color:#fff;
  padding:28px 24px; border-radius:16px; box-shadow:0 8px 28px rgba(0,0,0,.12);
}
.header-section .welcome-text{ color:#fff; font-weight:800; font-size:2rem; }
.view-section{ transition:opacity .3s ease; }
.table-row{ transition:all .2s ease; }
.table-row:hover{ background-color:#f8fafc; transform:translateX(2px); }
.accordion-button{ border-radius:12px!important; border:none; box-shadow:none; }
.accordion-button:not(.collapsed){ background:linear-gradient(135deg,#f8fafc 0%,#e2e8f0 100%); color:var(--primary-color); }
.notice{ border:1px solid #eef2f6; border-radius:12px; padding:12px; background:#fff; transition:.15s ease; }
.notice:hover{ box-shadow:0 8px 18px rgba(0,0,0,.06); }
.notice .title{ font-weight:600; }
.notice .meta{ font-size:.85rem; color:#6c757d; }
.card{ border-radius:12px; border:1px solid #e2e8f0; box-shadow:0 4px 20px rgba(0,0,0,.08); }
.table th{ font-weight:600; color:#374151; border-bottom:2px solid #e2e8f0; }
#content main>.container-fluid{ margin-top:.25rem; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function(){
  // Elements
  const viewAllBtn   = document.getElementById('viewAllBtn');
  const viewEventBtn = document.getElementById('viewEventBtn');
  const viewAll      = document.getElementById('viewAll');
  const viewEvent    = document.getElementById('viewEvent');

  const searchInput  = document.getElementById('searchInput');
  const filterEvent  = document.getElementById('filterEvent');
  const filterStatus = document.getElementById('filterStatus');
  const rows         = document.querySelectorAll('#fpTable .table-row');

  const KEY = 'reviewer_fullpaper_view';

  function switchView(v){
    if (v === 'event'){
      viewEvent.classList.remove('d-none');
      viewAll.classList.add('d-none');
      viewEventBtn.classList.add('active');
      viewAllBtn.classList.remove('active');
      localStorage.setItem(KEY, 'event');
    } else {
      viewAll.classList.remove('d-none');
      viewEvent.classList.add('d-none');
      viewAllBtn.classList.add('active');
      viewEventBtn.classList.remove('active');
      localStorage.setItem(KEY, 'all');
    }
  }

  viewAllBtn?.addEventListener('click', ()=> switchView('all'));
  viewEventBtn?.addEventListener('click', ()=> switchView('event'));
  switchView(localStorage.getItem(KEY) || 'all');

  function applyFilters(){
    const q   = (searchInput.value || '').toLowerCase().trim();
    const ev  = filterEvent.value || '';
    const st  = (filterStatus.value || '').toLowerCase();

    rows.forEach(tr=>{
      const txt = (tr.getAttribute('data-search')||'');
      const eId = (tr.getAttribute('data-event-id')||'');
      const s   = (tr.getAttribute('data-status')||'');
      const okQ  = !q  || txt.includes(q);
      const okE  = !ev || ev === eId;
      const okS  = !st || st === s;
      tr.style.display = (okQ && okE && okS) ? '' : 'none';
    });

    const tbody   = document.querySelector('#fpTable tbody');
    const visible = Array.from(rows).filter(r=> r.style.display !== 'none');
    let emptyRow  = tbody.querySelector('.empty-state-row');
    if (visible.length === 0 && rows.length > 0){
      if (!emptyRow){
        emptyRow = document.createElement('tr');
        emptyRow.className = 'empty-state-row';
        emptyRow.innerHTML = `
          <td colspan="5" class="text-center py-5">
            <div class="text-muted">
              <i class="bi bi-search fs-1 d-block mb-2"></i>
              <div class="fw-medium">Tidak ada hasil yang cocok</div>
              <small>Coba ubah filter pencarian Anda</small>
            </div>
          </td>`;
        tbody.appendChild(emptyRow);
      }
    } else if (emptyRow){
      emptyRow.remove();
    }
  }

  searchInput?.addEventListener('input', applyFilters);
  filterEvent?.addEventListener('change', applyFilters);
  filterStatus?.addEventListener('change', applyFilters);
  applyFilters();

  // UX kecil saat klik detail
  document.querySelectorAll('a[href*="/reviewer/fullpaper/"]').forEach(a=>{
    a.addEventListener('click', function(){
      const i = this.querySelector('i'); if (i) i.className = 'bi bi-hourglass-split';
      this.classList.add('disabled');
    });
  });
});
</script>