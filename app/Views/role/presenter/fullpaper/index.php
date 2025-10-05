<?php
$title      = $title      ?? 'Full Paper';
$needUpload = $needUpload ?? [];
$history    = $history    ?? [];

$fmtDate = fn($s)=> $s ? date('d M Y', strtotime($s)) : '-';
$fmtDT   = fn($s)=> $s ? date('d M Y H:i', strtotime($s)) : '-';
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

      <!-- Search Bar -->
      <div class="card card-glass-plain mb-4 card-ring">
        <div class="card-body py-3">
          <div class="d-flex align-items-center gap-2">
            <div class="position-relative flex-grow-1">
              <span class="position-absolute top-50 translate-middle-y ms-3 opacity-75">
                <i class="bi bi-search"></i>
              </span>
              <input id="searchInput" type="text" class="form-control ps-5"
                placeholder="Cari event, status, format, tanggal…">
            </div>
            <button id="clearSearch" type="button" class="btn btn-outline-secondary d-none">
              <i class="bi bi-x-circle"></i> Clear
            </button>
          </div>
        </div>
      </div>

      <!-- Perlu Upload -->
      <div class="card shadow-soft card-glass-plain mb-4 section-card">
        <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <span class="badge bg-blue-soft"><i class="bi bi-upload"></i></span>
            <h5 class="mb-0 fw-semibold text-blue-900">Full Paper perlu upload</h5>
          </div>
          <span class="count-badge"><?= count($needUpload) ?></span>
        </div>
        <div class="card-body pt-3">
          <?php if (empty($needUpload)): ?>
            <div class="empty-hint">
              <i class="bi bi-check2-circle me-1"></i>Tidak ada event yang membutuhkan upload Full Paper saat ini.
            </div>
          <?php else: ?>
            <div class="row g-3 row-cols-1 row-cols-md-2 row-cols-xl-3 row-cols-xxl-4 js-card-grid">
              <?php foreach ($needUpload as $row): ?>
              <div class="col">
                <div class="event-card h-100 p-3 js-card card-accent"
                     data-search="<?= esc(strtolower(
                       ($row['title'] ?? '') . ' ' .
                       ($row['status_label'] ?? '') . ' ' .
                       ($row['format'] ?? '') . ' ' .
                       ($fmtDate($row['event_date'] ?? null)) . ' ' .
                       ($fmtDT($row['full_paper_deadline'] ?? null))
                     )) ?>">

                  <div class="event-main">
                    <div class="d-flex align-items-start justify-content-between mb-2">
                      <h6 class="mb-0 text-blue-900"><?= esc($row['title'] ?? '-') ?></h6>
                      <span class="badge bg-<?= esc($row['status_badge']) ?>-subtle text-<?= esc($row['status_badge']) ?>">
                        <?= esc($row['status_label']) ?>
                      </span>
                    </div>

                    <div class="chip-row mb-2">
                      <span class="chip"><i class="bi bi-laptop me-1"></i><?= esc($formatLabel($row['format'] ?? '')) ?></span>
                    </div>

                    <ul class="meta-list mb-3">
                      <li><span>Tanggal Event</span><strong><?= esc($fmtDate($row['event_date'] ?? null)) ?></strong></li>
                      <li><span>Deadline Full Paper</span><strong><?= esc($fmtDT($row['full_paper_deadline'] ?? null)) ?></strong></li>
                    </ul>

                    <?php if (!empty($row['hint']) && ($row['fp_status'] ?? '') !== 'ACCEPTED'): // hindari dobel info ?>
                      <div class="text-muted small"><?= esc($row['hint']) ?></div>
                    <?php endif; ?>
                  </div>

                  <div class="event-footer d-flex gap-2 pt-2 border-top subtle-divider">
                    <a class="btn btn-primary flex-fill" href="<?= site_url('presenter/fullpaper/create/'.(int)$row['event_id']) ?>">
                      <i class="bi bi-upload"></i>
                      <?= in_array(($row['fp_status'] ?? ''), ['REVISION','REJECTED'], true) ? 'Upload Ulang (Revisi)' : 'Upload Full Paper' ?>
                    </a>
                    <a class="btn btn-outline-secondary flex-fill" href="<?= site_url('presenter/fullpaper/detail/'.(int)$row['event_id']) ?>">
                      <i class="bi bi-eye"></i> Detail
                    </a>
                  </div>

                </div>
              </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Riwayat -->
      <div class="card shadow-soft card-glass-plain mb-4 section-card">
        <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center justify-content-between">
          <div class="d-flex align-items-center gap-2">
            <span class="badge bg-blue-soft"><i class="bi bi-clock-history"></i></span>
            <h5 class="mb-0 fw-semibold text-blue-900">Riwayat Full Paper</h5>
          </div>
          <span class="count-badge"><?= count($history) ?></span>
        </div>
        <div class="card-body pt-3">
          <?php if (empty($history)): ?>
            <div class="empty-hint">
              <i class="bi bi-inboxes me-1"></i>Belum ada riwayat full paper.
            </div>
          <?php else: ?>
            <div class="row g-3 row-cols-1 row-cols-md-2 row-cols-xl-3 row-cols-xxl-4 js-card-grid">
              <?php foreach ($history as $h): ?>
              <div class="col">
                <div class="event-card h-100 p-3 js-card card-accent"
                     data-search="<?= esc(strtolower(
                       ($h['event_title'] ?? '') . ' ' .
                       ($h['status_label'] ?? '') . ' ' .
                       ($h['format'] ?? '') . ' ' .
                       ($fmtDate($h['event_date'] ?? null)) . ' ' .
                       ($fmtDT($h['uploaded_at'] ?? null))
                     )) ?>">

                  <div class="event-main">
                    <div class="d-flex align-items-start justify-content-between mb-2">
                      <h6 class="mb-0 text-blue-900"><?= esc($h['event_title'] ?? '-') ?></h6>
                      <span class="badge bg-<?= esc($h['status_badge']) ?>-subtle text-<?= esc($h['status_badge']) ?>">
                        <?= esc($h['status_label']) ?>
                      </span>
                    </div>

                    <div class="chip-row mb-2">
                      <span class="chip"><i class="bi bi-calendar-event me-1"></i><?= esc($fmtDate($h['event_date'] ?? null)) ?></span>
                      <span class="chip alt"><i class="bi bi-laptop me-1"></i><?= esc($formatLabel($h['format'] ?? '')) ?></span>
                    </div>

                    <ul class="meta-list mb-3">
                      <li><span>Unggahan Terakhir</span><strong><?= esc($fmtDT($h['uploaded_at'] ?? null)) ?></strong></li>
                    </ul>

                    <?php if (!empty($h['status_hint']) && ($h['fp_status'] ?? '') !== 'ACCEPTED'): ?>
                      <?php
                        $cls = (($h['fp_status'] ?? '') === 'REJECTED') ? 'text-danger' : 'text-muted';
                      ?>
                      <div class="small <?= $cls ?>"><?= esc($h['status_hint']) ?></div>
                    <?php endif; ?>
                  </div>

                  <div class="event-footer mt-2 d-grid gap-2 pt-2 border-top subtle-divider">
                    <a class="btn btn-outline-primary btn-sm" href="<?= site_url('presenter/fullpaper/detail/'.(int)$h['event_id']) ?>">
                      <i class="bi bi-eye"></i> Detail
                    </a>
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
  --side-pad: clamp(1rem, 2.3vw, 2.2rem);
  --gutter-x: 1.05rem;
}

body{ font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size:15.5px; line-height:1.6; }

.page-wrap-blue{
  min-height:100vh; padding-top:72px; position:relative;
  background:
    radial-gradient(900px 300px at 20% -10%, rgba(59,130,246,.17), rgba(59,130,246,0) 60%),
    radial-gradient(900px 300px at 80% 110%, rgba(59,130,246,.14), rgba(59,130,246,0) 70%),
    linear-gradient(180deg, var(--blue-50), #fff 40%);
}
.page-wrap-blue::after{
  content:""; position:absolute; inset:0; pointer-events:none; opacity:.18;
  background-image: radial-gradient(#93c5fd 1px, transparent 1px), radial-gradient(#bfdbfe 1px, transparent 1px);
  background-position: 0 0, 20px 20px; background-size: 40px 40px, 40px 40px;
}

.container-xxl{ max-width:min(100%, 1560px); padding-left:var(--side-pad)!important; padding-right:var(--side-pad)!important; margin-inline:auto; }
.row.g-3, .row.g-4{ --bs-gutter-x: var(--gutter-x); --bs-gutter-y: var(--gutter-x); }

.card-glass-plain{ backdrop-filter:blur(6px); background:rgba(255,255,255,.96); border-radius:14px; border:1px solid rgba(30,64,175,.10); }
.shadow-soft{ box-shadow:0 10px 24px rgba(30,64,175,.08); }
.card-ring{ position:relative; }
.card-ring::before{
  content:""; position:absolute; inset:-1px; border-radius:16px;
  background:linear-gradient(135deg, rgba(59,130,246,.25), rgba(255,255,255,0) 40%, rgba(59,130,246,.25));
  -webkit-mask:linear-gradient(#fff 0 0) content-box, linear-gradient(#fff 0 0);
  -webkit-mask-composite:xor; mask-composite:exclude; padding:1px; pointer-events:none;
}

/* Event cards */
.event-card{
  background:linear-gradient(180deg,#fff,rgba(255,255,255,.97));
  border:1px solid rgba(30,64,175,.12); border-radius:14px; box-shadow:0 10px 22px rgba(30,64,175,.06);
  padding:16px; transition:transform .18s ease, box-shadow .18s ease, border-color .18s ease;
  position:relative; overflow:hidden;
  display:flex; flex-direction:column; min-height:310px; /* seragam */
}
.card-accent::before{
  content:""; position:absolute; left:0; top:0; bottom:0; width:4px; border-radius:4px 0 0 4px;
  background:linear-gradient(180deg, var(--blue-500), var(--blue-700));
  opacity:.75;
}
.event-card:hover{ transform: translateY(-2px); box-shadow:0 16px 28px rgba(30,64,175,.12); border-color: rgba(30,64,175,.22); }
.event-main{ flex:1 1 auto; }
.event-footer{ margin-top:auto; }

.subtle-divider{ border-color: rgba(30,64,175,.12)!important; }
.meta-list{ list-style:none; padding-left:0; margin:0; }
.meta-list li{ display:flex; align-items:center; justify-content:space-between; gap:.75rem; padding:.35rem 0; }
.meta-list li span{ color:#6b7280; }

.chip-row{ display:flex; flex-wrap:wrap; gap:.5rem; }
.chip{ display:inline-flex; align-items:center; padding:.28rem .6rem; font-size:.88rem; border-radius:999px; background:#eef3ff; color:#1e3a8a; border:1px solid rgba(30,64,175,.15); font-weight:600; }
.chip.alt{ background:#f1f5ff; color:#244aa4; }

.section-card .count-badge{
  display:inline-flex; align-items:center; justify-content:center;
  min-width:32px; height:28px; padding:0 .4rem; border-radius:999px;
  background:linear-gradient(180deg,#eaf2ff,#e3edff); color:#274690; font-weight:700; border:1px solid rgba(39,70,144,.15);
}

.text-blue-900{ color:var(--blue-900)!important; }
.bg-blue-soft{ background:var(--blue-200); color:var(--blue-800); border-radius:12px; padding:.5rem .7rem; font-weight:600; font-size:.9rem; }
.btn{ font-weight:700; border-radius:10px; font-size:.98rem; padding:.62rem 1.05rem; }
.btn-sm{ padding:.5rem .9rem; font-size:.92rem; }
.btn-primary{ background:var(--blue-600); border-color:var(--blue-600); box-shadow:0 4px 12px rgba(37,99,235,.2); }
.form-control{ padding:.7rem .9rem; font-size:1rem; border-radius:12px; }
#searchInput{ height:46px; }

.empty-hint{ color:#567; background:#f6f9ff; border:1px dashed rgba(30,64,175,.18); border-radius:12px; padding:.8rem 1rem; font-weight:600; }

@media (max-width:575.98px){
  .container-xxl{ padding-left:1rem!important; padding-right:1rem!important; }
  .event-card{ padding:14px; min-height:280px; }
}
</style>

<script>
(function(){
  const input = document.getElementById('searchInput');
  const clearBtn = document.getElementById('clearSearch');
  if(!input) return;
  const cards = Array.from(document.querySelectorAll('.js-card'));
  const grids = Array.from(document.querySelectorAll('.js-card-grid'));

  function applyFilter(query){
    const q = (query || '').trim().toLowerCase();
    cards.forEach(card => {
      const hay = (card.dataset.search || card.textContent).toLowerCase();
      const show = !q || hay.includes(q);
      card.parentElement.style.display = show ? '' : 'none';
    });
    clearBtn.classList.toggle('d-none', !q);
    grids.forEach(grid => {
      const anyVisible = Array.from(grid.children).some(c => c.style.display !== 'none');
      grid.style.minHeight = anyVisible ? '' : '80px';
    });
  }
  input.addEventListener('input', e => applyFilter(e.target.value));
  clearBtn.addEventListener('click', () => { input.value=''; applyFilter(''); input.focus(); });
})();
</script>