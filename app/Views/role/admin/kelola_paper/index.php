<?php
/**
 * File: app/Views/role/admin/kelola_paper/index.php
 * Expect: $aktif (array event aktif/mendatang), $berakhir (array event selesai)
 */
$title = $title ?? 'Kelola Paper';
$aktif = $aktif ?? [];
$berakhir = $berakhir ?? [];

$fmtDate = function ($date, $withTime = false) {
  if (!$date) return '-';
  $ts = strtotime((string)$date);
  return $withTime ? date('d M Y H:i', $ts) : date('d M Y', $ts);
};

$cntAktif    = count($aktif);
$cntBerakhir = count($berakhir);
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_admin') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill page-wrap-blue">
    <div class="container-xxl px-3 px-md-4 py-4">

      <!-- HERO -->
      <div class="hero-blue card-glass mb-3 p-3 p-md-4 d-flex justify-content-between align-items-start gap-3">
        <div>
          <h3 class="hero-title mb-1">
            <i class="bi bi-journal-text me-2"></i>Kelola Paper
          </h3>
          <div class="text-white-75 small">
            Pusat penugasan reviewer untuk Abstrak & Full Paper per event.
          </div>
        </div>
        <div class="text-end d-none d-md-block">
          <div class="text-white-75 small">Hari ini</div>
          <div class="fw-semibold text-white"><?= date('d M Y') ?></div>
        </div>
      </div>

      <!-- QUICK STATS + SEARCH -->
      <div class="card card-glass-plain shadow-soft mb-3">
        <div class="card-body d-flex flex-wrap gap-3 align-items-center justify-content-between">
          <div class="stats-wrap">
            <div class="stat">
              <div class="k"><?= number_format($cntAktif) ?></div>
              <div class="l"><i class="bi bi-lightning-charge me-1"></i>Aktif/Mendatang</div>
            </div>
            <div class="sep"></div>
            <div class="stat">
              <div class="k"><?= number_format($cntBerakhir) ?></div>
              <div class="l"><i class="bi bi-lock me-1"></i>Berakhir</div>
            </div>
          </div>

          <div class="searchbar flex-grow-1" style="max-width:560px">
            <div class="position-relative">
              <span class="position-absolute top-50 translate-middle-y ms-3 opacity-75">
                <i class="bi bi-search"></i>
              </span>
              <input id="searchInput" type="text" class="form-control ps-5"
                     placeholder="Cari event (judul, tanggal)…">
              <button id="clearSearch" type="button" class="btn btn-outline-secondary btn-sm clear-btn d-none">
                <i class="bi bi-x-circle"></i>
              </button>
            </div>
          </div>
        </div>
      </div>

      <!-- TABS -->
      <ul class="nav nav-pills mb-3 gap-2" id="kpTabs" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" id="tab-active-tab" data-bs-toggle="pill"
                  data-bs-target="#tab-active" type="button" role="tab" aria-controls="tab-active" aria-selected="true">
            <i class="bi bi-lightning-charge me-1"></i>Aktif / Mendatang
            <span class="badge bg-light text-dark ms-2"><?= number_format($cntAktif) ?></span>
          </button>
        </li>
        <li class="nav-item" role="presentation">
          <button class="nav-link" id="tab-done-tab" data-bs-toggle="pill"
                  data-bs-target="#tab-done" type="button" role="tab" aria-controls="tab-done" aria-selected="false">
            <i class="bi bi-lock me-1"></i>Berakhir
            <span class="badge bg-light text-dark ms-2"><?= number_format($cntBerakhir) ?></span>
          </button>
        </li>
      </ul>

      <div class="tab-content">
        <!-- AKTIF / MENDATANG -->
        <div class="tab-pane fade show active" id="tab-active" role="tabpanel" aria-labelledby="tab-active-tab">
          <div class="card shadow-soft card-glass-plain mb-4">
            <div class="card-body pt-3">
              <?php if (empty($aktif)): ?>
                <div class="empty-hint">
                  <i class="bi bi-inboxes me-1"></i>Tidak ada event aktif/mendatang.
                </div>
              <?php else: ?>
                <div class="row g-3 row-cols-1 row-cols-lg-2 row-cols-xxl-3 js-card-grid" id="gridAktif">
                  <?php foreach ($aktif as $e): ?>
                    <?php
                      $id  = (int)($e['id'] ?? 0);
                      $cntPresenter = (int)($e['presenter_count'] ?? 0);
                      $cntAbsUnasg  = (int)($e['abs_unassigned'] ?? 0);
                      $cntFpUnasg   = (int)($e['fp_unassigned'] ?? 0);
                      $hay = strtolower(
                        ($e['title'] ?? '') . ' ' .
                        $fmtDate($e['event_date'] ?? null) . ' ' .
                        ($e['event_time'] ?? '')
                      );
                    ?>
                    <div class="col">
                      <div class="event-card h-100 p-3 js-card card-accent" data-search="<?= esc($hay) ?>">
                        <div class="d-flex align-items-start justify-content-between mb-2">
                          <h6 class="mb-0 text-blue-900 me-2 line-clip-2"><?= esc($e['title'] ?? '-') ?></h6>
                          <span class="badge bg-success-subtle">Aktif</span>
                        </div>

                        <div class="small text-muted mb-3">
                          <div>Event: <strong class="text-blue-900"><?= esc($fmtDate($e['event_date'] ?? null)) ?> <?= esc($e['event_time'] ?? '') ?></strong></div>
                          <div>Deadline Abstrak: <strong class="text-blue-900"><?= esc($fmtDate($e['abstract_deadline'] ?? null, true)) ?></strong></div>
                          <div>Deadline Full Paper: <strong class="text-blue-900"><?= esc($fmtDate($e['full_paper_deadline'] ?? null, true)) ?></strong></div>
                        </div>

                        <div class="metrics-wrap mb-3">
                          <div class="metric-pill">
                            <div class="label"><i class="bi bi-people me-1"></i>Presenter</div>
                            <div class="value"><?= number_format($cntPresenter) ?></div>
                          </div>
                          <div class="metric-pill warn">
                            <div class="label"><i class="bi bi-person-gear me-1"></i>Abstrak blm assign</div>
                            <div class="value"><?= number_format($cntAbsUnasg) ?></div>
                          </div>
                          <div class="metric-pill danger">
                            <div class="label"><i class="bi bi-journal-x me-1"></i>Full paper blm assign</div>
                            <div class="value"><?= number_format($cntFpUnasg) ?></div>
                          </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-between gap-2">
                          <a class="btn btn-primary flex-fill"
                             href="<?= site_url('admin/kelola-paper/detail/'.$id) ?>">
                            <i class="bi bi-layout-text-window me-1"></i>Kelola
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

        <!-- BERAKHIR -->
        <div class="tab-pane fade" id="tab-done" role="tabpanel" aria-labelledby="tab-done-tab">
          <div class="card shadow-soft card-glass-plain mb-4">
            <div class="card-body pt-3">
              <?php if (empty($berakhir)): ?>
                <div class="empty-hint">
                  <i class="bi bi-inboxes me-1"></i>Belum ada event berakhir.
                </div>
              <?php else: ?>
                <div class="row g-3 row-cols-1 row-cols-lg-2 row-cols-xxl-3 js-card-grid" id="gridSelesai">
                  <?php foreach ($berakhir as $e): ?>
                    <?php
                      $id  = (int)($e['id'] ?? 0);
                      $cntPresenter = (int)($e['presenter_count'] ?? 0);
                      $cntAbsUnasg  = (int)($e['abs_unassigned'] ?? 0);
                      $cntFpUnasg   = (int)($e['fp_unassigned'] ?? 0);
                      $hay = strtolower(
                        ($e['title'] ?? '') . ' ' .
                        $fmtDate($e['event_date'] ?? null) . ' ' .
                        ($e['event_time'] ?? '')
                      );
                    ?>
                    <div class="col">
                      <div class="event-card h-100 p-3 js-card card-accent opacity-92" data-search="<?= esc($hay) ?>">
                        <div class="d-flex align-items-start justify-content-between mb-2">
                          <h6 class="mb-0 text-blue-900 me-2 line-clip-2"><?= esc($e['title'] ?? '-') ?></h6>
                          <span class="badge bg-secondary-subtle">Selesai</span>
                        </div>

                        <div class="small text-muted mb-3">
                          <div>Event: <strong class="text-blue-900"><?= esc($fmtDate($e['event_date'] ?? null)) ?> <?= esc($e['event_time'] ?? '') ?></strong></div>
                          <div>Deadline Abstrak: <strong class="text-blue-900"><?= esc($fmtDate($e['abstract_deadline'] ?? null, true)) ?></strong></div>
                          <div>Deadline Full Paper: <strong class="text-blue-900"><?= esc($fmtDate($e['full_paper_deadline'] ?? null, true)) ?></strong></div>
                        </div>

                        <div class="metrics-wrap mb-3">
                          <div class="metric-pill">
                            <div class="label"><i class="bi bi-people me-1"></i>Presenter</div>
                            <div class="value"><?= number_format($cntPresenter) ?></div>
                          </div>
                          <div class="metric-pill warn">
                            <div class="label"><i class="bi bi-person-gear me-1"></i>Abstrak blm assign</div>
                            <div class="value"><?= number_format($cntAbsUnasg) ?></div>
                          </div>
                          <div class="metric-pill danger">
                            <div class="label"><i class="bi bi-journal-x me-1"></i>Full paper blm assign</div>
                            <div class="value"><?= number_format($cntFpUnasg) ?></div>
                          </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-between gap-2">
                          <a class="btn btn-outline-secondary flex-fill"
                             href="<?= site_url('admin/kelola-paper/detail/'.$id) ?>">
                            <i class="bi bi-layout-text-window me-1"></i>Lihat Detail
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
      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<style>
:root{
  /* ——— Perlebar kanvas & kecilkan padding samping ——— */
  --side-pad: clamp(1rem, 1.6vw, 1.6rem);
  --container-max: 1680px;

  --blue-50:#eff6ff; --blue-200:#bfdbfe; --blue-600:#2563eb;
  --blue-700:#1d4ed8; --blue-800:#1e40af; --blue-900:#1e3a8a;
}

.page-wrap-blue{
  background:linear-gradient(180deg,var(--blue-50),#fff 40%);
  min-height:100vh; padding-top:72px;
}
.container-xxl{
  max-width:min(100%, var(--container-max));
  padding-left:var(--side-pad)!important;
  padding-right:var(--side-pad)!important;
}

/* HERO */
.hero-blue{
  background:radial-gradient(1200px 400px at 10% -20%,var(--blue-600) 0,var(--blue-700) 40%,var(--blue-800) 100%)!important;
  color:#fff!important; border-radius:16px; border:1px solid rgba(255,255,255,.15);
  box-shadow:0 12px 28px rgba(30,64,175,.10);
}
.hero-title{ font-weight:800; letter-spacing:.25px; font-size:1.6rem; }
.text-white-75{ color:rgba(255,255,255,.85)!important; }

/* Cards */
.card-glass-plain{ backdrop-filter:blur(6px); background:rgba(255,255,255,.94); border-radius:14px; border:1px solid rgba(30,64,175,.10); }
.shadow-soft{ box-shadow:0 10px 24px rgba(30,64,175,.08); }

/* Quick stats */
.stats-wrap{ display:flex; align-items:center; gap:1rem; }
.stat .k{ font-size:1.4rem; font-weight:800; color:var(--blue-900); line-height:1; }
.stat .l{ font-size:.92rem; color:#64748b; font-weight:600; }
.stats-wrap .sep{ width:1px; height:32px; background:#e5e7eb; }

/* Search */
.searchbar .form-control{ padding:.7rem .9rem; border-radius:12px; }
.searchbar .clear-btn{ position:absolute; right:6px; top:50%; transform:translateY(-50%); }

/* Tabs */
.nav-pills .nav-link{ border-radius:999px; font-weight:700; }
.nav-pills .nav-link.active{ background:var(--blue-600); }

/* Event card */
.event-card{
  background:#fff; border:1px solid rgba(30,64,175,.12); border-radius:14px; box-shadow:0 10px 22px rgba(30,64,175,.08);
  transition:transform .18s ease, box-shadow .18s ease, border-color .18s ease;
  position:relative; overflow:hidden;
}
.card-accent::before{
  content:""; position:absolute; left:0; top:0; bottom:0; width:4px; border-radius:4px 0 0 4px;
  background:linear-gradient(180deg, var(--blue-600), var(--blue-800)); opacity:.75;
}
.event-card:hover{ transform: translateY(-2px); box-shadow:0 16px 28px rgba(30,64,175,.12); border-color:rgba(30,64,175,.22); }
.line-clip-2{ display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }

/* Status badges */
.bg-success-subtle{ background:#d1fae5!important; color:#065f46!important; }
.bg-secondary-subtle{ background:#f1f5f9!important; color:#475569!important; }
.text-blue-900{ color:var(--blue-900)!important; }

/* Metrics */
.metrics-wrap{ display:grid; grid-template-columns:repeat(3,1fr); gap:.6rem; }
.metric-pill{
  border:1px solid rgba(30,64,175,.12);
  background:linear-gradient(180deg,#fff,rgba(255,255,255,.96));
  border-radius:12px; padding:.6rem .7rem;
  display:flex; flex-direction:column; align-items:flex-start; justify-content:center; min-height:68px;
}
.metric-pill .label{ font-size:.8rem; color:#64748b; font-weight:700; }
.metric-pill .value{ font-size:1.08rem; font-weight:800; color:var(--blue-900); line-height:1.2; }
.metric-pill.warn .value{ color:#b45309; }
.metric-pill.danger .value{ color:#b91c1c; }

.opacity-92{ opacity:.92; }

/* Buttons */
.btn{ border-radius:12px; font-weight:700; }
.btn-primary{ background:var(--blue-600); border-color:var(--blue-600); box-shadow:0 4px 12px rgba(37,99,235,.2); }
.btn-outline-secondary{ border-color:#475569; color:#475569; }
.btn-outline-secondary:hover{ background:#475569; color:#fff; }

.empty-hint{ color:#567; background:#f6f9ff; border:1px dashed rgba(30,64,175,.18); border-radius:12px; padding:.9rem 1.1rem; font-weight:600; }

/* Grid gutter sedikit diperlebar */
.row.g-3{ --bs-gutter-x: 1.1rem; --bs-gutter-y: 1.1rem; }

@media (max-width: 575.98px){
  .hero-title{ font-size:1.35rem; }
  .metrics-wrap{ grid-template-columns:1fr 1fr; }
}
</style>

<script>
(function(){
  const input    = document.getElementById('searchInput');
  const clearBtn = document.getElementById('clearSearch');
  if(!input) return;

  const grids = Array.from(document.querySelectorAll('.js-card-grid'));
  const cards = Array.from(document.querySelectorAll('.js-card'));

  function applyFilter(query){
    const q = (query || '').trim().toLowerCase();
    cards.forEach(card => {
      const hay = (card.dataset.search || card.textContent).toLowerCase();
      const show = !q || hay.includes(q);
      card.parentElement.style.display = show ? '' : 'none';
    });
    clearBtn.classList.toggle('d-none', !q);

    // sedikit estetika: tinggi minimal saat filter result kosong
    grids.forEach(grid => {
      const anyVisible = Array.from(grid.children).some(c => c.style.display !== 'none');
      grid.style.minHeight = anyVisible ? '' : '100px';
    });
  }

  input.addEventListener('input', e => applyFilter(e.target.value));
  clearBtn.addEventListener('click', () => { input.value=''; applyFilter(''); input.focus(); });
})();
</script>