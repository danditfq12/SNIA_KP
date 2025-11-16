<?php
$title         = $title ?? 'Kelola Paper';
$aktif         = $aktif ?? [];
$berakhir      = $berakhir ?? [];
$reviewerLoads = $reviewerLoads ?? [];

$fmtDate = function ($date, $withTime = false) {
  if (!$date) return '-';
  $ts = strtotime((string)$date);
  return $withTime ? date('d M Y H:i', $ts) : date('d M Y', $ts);
};

$todayTs     = strtotime(date('Y-m-d'));
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
        <div class="d-flex flex-column gap-2">
          <h3 class="hero-title mb-0">
            <i class="bi bi-journal-text me-2"></i>Kelola Paper
          </h3>
          <div class="text-white-75 small">
            Pusat penugasan reviewer untuk Abstrak & Full Paper per event.
          </div>
          <div class="d-none d-md-flex">
            <button class="btn btn-light btn-sm fw-bold" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasReviewer">
              <i class="bi bi-people me-1"></i> Reviewer (<?= number_format(count($reviewerLoads)) ?>)
            </button>
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
              <div class="l"><i class="bi bi-lightning-charge me-1"></i>Aktif</div>
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

          <!-- Tombol buka panel reviewer (mobile) -->
          <button class="btn btn-primary btn-sm d-md-none ms-auto" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasReviewer">
            <i class="bi bi-people me-1"></i> Reviewer
          </button>
        </div>
      </div>

      <!-- TABS -->
      <ul class="nav nav-pills mb-3 gap-2" id="kpTabs" role="tablist">
        <li class="nav-item" role="presentation">
          <button class="nav-link active" id="tab-active-tab" data-bs-toggle="pill"
                  data-bs-target="#tab-active" type="button" role="tab" aria-controls="tab-active" aria-selected="true">
            <i class="bi bi-lightning-charge me-1"></i>Aktif
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
                      $eventTs     = strtotime((string)($e['event_date'] ?? ''));
                      $badgeLabel  = ($eventTs && $eventTs > $todayTs) ? 'Mendatang' : 'Aktif';
                      $absCls      = $cntAbsUnasg > 0 ? 'warn'   : 'ok';
                      $fpCls       = $cntFpUnasg  > 0 ? 'danger' : 'ok';
                    ?>
                    <div class="col">
                      <div class="event-card h-100 p-3 js-card card-accent" data-search="<?= esc($hay) ?>">
                        <div class="d-flex align-items-start justify-content-between mb-2">
                          <h6 class="mb-0 text-blue-900 me-2 line-clip-2"><?= esc($e['title'] ?? '-') ?></h6>
                          <span class="badge <?= $badgeLabel==='Aktif'?'bg-success-subtle':'bg-secondary-subtle' ?>"><?= $badgeLabel ?></span>
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
                          <div class="metric-pill <?= $absCls ?>">
                            <div class="label"><i class="bi bi-person-gear me-1"></i>Tugas Abstrak Masuk</div>
                            <div class="value"><?= number_format($cntAbsUnasg) ?></div>
                          </div>
                          <div class="metric-pill <?= $fpCls ?>">
                            <div class="label"><i class="bi bi-journal-x me-1"></i>Tugas Full Paper Masuk</div>
                            <div class="value"><?= number_format($cntFpUnasg) ?></div>
                          </div>
                        </div>

                        <a class="btn btn-primary w-100"
                           href="<?= site_url('admin/kelola-paper/detail/'.$id) ?>">
                          <i class="bi bi-layout-text-window me-1"></i>Kelola
                        </a>
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
                      $absCls      = $cntAbsUnasg > 0 ? 'warn'   : 'ok';
                      $fpCls       = $cntFpUnasg  > 0 ? 'danger' : 'ok';
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
                          <div class="metric-pill <?= $absCls ?>">
                            <div class="label"><i class="bi bi-person-gear me-1"></i>Tugas Abstrak Masuk</div>
                            <div class="value"><?= number_format($cntAbsUnasg) ?></div>
                          </div>
                          <div class="metric-pill <?= $fpCls ?>">
                            <div class="label"><i class="bi bi-journal-x me-1"></i>Tugas Full Paper Masuk</div>
                            <div class="value"><?= number_format($cntFpUnasg) ?></div>
                          </div>
                        </div>

                        <a class="btn btn-outline-secondary w-100"
                           href="<?= site_url('admin/kelola-paper/detail/'.$id) ?>">
                          <i class="bi bi-layout-text-window me-1"></i>Lihat Detail
                        </a>
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

    <!-- ====== OFFCANVAS: Reviewer & Beban (slide kanan) ====== -->
    <div class="offcanvas offcanvas-end offcanvas-blue" tabindex="-1" id="offcanvasReviewer" aria-labelledby="offcanvasReviewerLabel">
      <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title d-flex align-items-center gap-2" id="offcanvasReviewerLabel">
          <i class="bi bi-people"></i>
          Reviewer & Beban Saat Ini
          <span class="badge bg-light text-dark"><?= number_format(count($reviewerLoads)) ?></span>
        </h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div>
      <div class="offcanvas-body d-flex flex-column">
        <?php if (empty($reviewerLoads)): ?>
          <div class="empty-hint mt-2">
            <i class="bi bi-inboxes me-1"></i>Tidak ada data reviewer yang terdeteksi.
          </div>
        <?php else: ?>
          <div class="input-group input-group-sm mb-3">
            <span class="input-group-text bg-light"><i class="bi bi-search"></i></span>
            <input type="text" id="revSearch" class="form-control" placeholder="Cari reviewer…">
          </div>

          <div class="small text-muted mb-2">
            Menampilkan beban <em>aktif</em> (assignment <strong>diterima</strong> & belum ada keputusan <strong>final</strong>).
          </div>

          <div id="revList" class="rev-scroll flex-grow-1">
            <div class="row g-2">
              <?php foreach ($reviewerLoads as $rv): ?>
                <?php
                  $abs  = (int)($rv['active_abs'] ?? 0);
                  $fp   = (int)($rv['active_fp']  ?? 0);
                  $name = esc($rv['name'] ?? 'Reviewer');
                  $mail = esc($rv['email'] ?? '');
                  $hay  = strtolower($name.' '.$mail);
                  $total= max(0, $abs + $fp);
                  $absPct = $total>0 ? round(($abs/$total)*100) : 0;
                  $fpPct  = $total>0 ? (100 - $absPct) : 0;
                ?>
                <div class="col-12 js-rev" data-hay="<?= $hay ?>">
                  <div class="rev-item d-flex align-items-center justify-content-between">
                    <div class="d-flex align-items-center gap-3 min-w-0">
                      <div class="avatar-initial"><?= strtoupper(substr($name,0,1)) ?></div>
                      <div class="min-w-0">
                        <div class="fw-bold text-blue-900 text-truncate"><?= $name ?></div>
                        <?php if ($mail): ?><div class="small text-muted text-truncate"><?= $mail ?></div><?php endif; ?>
                      </div>
                    </div>
                    <div class="text-end d-flex align-items-center gap-2">
                      <span class="badge px-2 py-1 <?= $abs>0?'bg-warning-subtle text-warning-900':'bg-success-subtle text-success-900' ?>" title="Abstrak aktif">
                        Abs: <strong><?= $abs ?></strong>
                      </span>
                      <span class="badge px-2 py-1 <?= $fp>0?'bg-danger-subtle text-danger-900':'bg-success-subtle text-success-900' ?>" title="Full Paper aktif">
                        FP: <strong><?= $fp ?></strong>
                      </span>
                    </div>
                  </div>
                  <!-- Bar total (visual ringkas) -->
                  <div class="progress progress-thin mb-2">
                    <div class="progress-bar bg-abs" role="progressbar" style="width: <?= $absPct ?>%" aria-valuenow="<?= $absPct ?>" aria-valuemin="0" aria-valuemax="100" title="Abstrak aktif"></div>
                    <div class="progress-bar bg-fp"  role="progressbar" style="width: <?= $fpPct ?>%"  aria-valuenow="<?= $fpPct ?>"  aria-valuemin="0" aria-valuemax="100" title="Full Paper aktif"></div>
                  </div>
                </div>
              <?php endforeach; ?>
            </div>
          </div>
        <?php endif; ?>
      </div>
      <div class="offcanvas-footer border-top p-3 d-flex justify-content-between align-items-center">
        <div class="small text-muted">Geser panel untuk menutup • atau klik tombol tutup</div>
        <button class="btn btn-outline-secondary btn-sm" data-bs-dismiss="offcanvas"><i class="bi bi-x-lg me-1"></i>Tutup</button>
      </div>
    </div>
    <!-- ====== END OFFCANVAS ====== -->

    <!-- FAB Reviewer (mobile) -->
    <button class="btn btn-primary fab-rev d-md-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasReviewer" aria-controls="offcanvasReviewer" title="Daftar Reviewer">
      <i class="bi bi-people"></i>
    </button>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<style>
:root{
  --side-pad: clamp(1rem, 1.6vw, 1.6rem);
  --container-max: 1680px;
  --blue-50:#eff6ff; --blue-200:#bfdbfe; --blue-600:#2563eb;
  --blue-700:#1d4ed8; --blue-800:#1e40af; --blue-900:#1e3a8a;
  --success-900:#065f46; --warning-900:#92400e; --danger-900:#7f1d1d;
}
.page-wrap-blue{ background:linear-gradient(180deg,var(--blue-50),#fff 40%); min-height:100vh; padding-top:72px; }
.container-xxl{ max-width:min(100%, var(--container-max)); padding-left:var(--side-pad)!important; padding-right:var(--side-pad)!important; }
.hero-blue{ background:radial-gradient(1200px 400px at 10% -20%,var(--blue-600) 0,var(--blue-700) 40%,var(--blue-800) 100%)!important; color:#fff!important; border-radius:16px; border:1px solid rgba(255,255,255,.15); box-shadow:0 12px 28px rgba(30,64,175,.10); }
.hero-title{ font-weight:800; letter-spacing:.25px; font-size:1.6rem; }
.text-white-75{ color:rgba(255,255,255,.85)!important; }
.card-glass-plain{ backdrop-filter:blur(6px); background:rgba(255,255,255,.94); border-radius:14px; border:1px solid rgba(30,64,175,.10); }
.shadow-soft{ box-shadow:0 10px 24px rgba(30,64,175,.08); }
.stats-wrap{ display:flex; align-items:center; gap:1rem; }
.stat .k{ font-size:1.4rem; font-weight:800; color:var(--blue-900); line-height:1; }
.stat .l{ font-size:.92rem; color:#64748b; font-weight:600; }
.stats-wrap .sep{ width:1px; height:32px; background:#e5e7eb; }
.searchbar .form-control{ padding:.7rem .9rem; border-radius:12px; }
.searchbar .clear-btn{ position:absolute; right:6px; top:50%; transform:translateY(-50%); }
.nav-pills .nav-link{ border-radius:999px; font-weight:700; }
.nav-pills .nav-link.active{ background:var(--blue-600); }
.event-card{ background:#fff; border:1px solid rgba(30,64,175,.12); border-radius:14px; box-shadow:0 10px 22px rgba(30,64,175,.08); transition:transform .18s, box-shadow .18s, border-color .18s; position:relative; overflow:hidden; }
.card-accent::before{ content:""; position:absolute; left:0; top:0; bottom:0; width:4px; border-radius:4px 0 0 4px; background:linear-gradient(180deg, var(--blue-600), var(--blue-800)); opacity:.75; }
.event-card:hover{ transform: translateY(-2px); box-shadow:0 16px 28px rgba(30,64,175,.12); border-color:rgba(30,64,175,.22); }
.line-clip-2{ display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
.bg-success-subtle{ background:#d1fae5!important; color:var(--success-900)!important; }
.bg-secondary-subtle{ background:#f1f5f9!important; color:#475569!important; }
.text-blue-900{ color:var(--blue-900)!important; }
.metrics-wrap{ display:grid; grid-template-columns:repeat(3,1fr); gap:.6rem; }
.metric-pill{ border:1px solid rgba(30,64,175,.12); background:linear-gradient(180deg,#fff,rgba(255,255,255,.96)); border-radius:12px; padding:.6rem .7rem; display:flex; flex-direction:column; align-items:flex-start; justify-content:center; min-height:68px; }
.metric-pill .label{ font-size:.8rem; color:#64748b; font-weight:700; }
.metric-pill .value{ font-size:1.08rem; font-weight:800; color:var(--blue-900); line-height:1.2; }
.metric-pill.warn .value{ color:#b45309; }
.metric-pill.danger .value{ color:#b91c1c; }
.metric-pill.ok .value{ color: var(--success-900); } /* hijau jika aman */
.opacity-92{ opacity:.92; }
.btn{ border-radius:12px; font-weight:700; }
.btn-primary{ background:var(--blue-600); border-color:var(--blue-600); box-shadow:0 4px 12px rgba(37,99,235,.2); }
.btn-outline-secondary{ border-color:#475569; color:#475569; }
.btn-outline-secondary:hover{ background:#475569; color:#fff; }
.empty-hint{ color:#567; background:#f6f9ff; border:1px dashed rgba(30,64,175,.18); border-radius:12px; padding:.9rem 1.1rem; font-weight:600; }
.row.g-3{ --bs-gutter-x: 1.1rem; --bs-gutter-y: 1.1rem; }
.offcanvas-blue{ --bs-offcanvas-width: clamp(320px, 36vw, 420px); }
.offcanvas-blue .offcanvas-header{ background:#fff; }
.offcanvas-blue .offcanvas-footer{ background:#fff; }
.rev-scroll{ overflow:auto; }
.rev-item{ background:#fff; border:1px solid rgba(30,64,175,.12); border-radius:12px; padding:.65rem .75rem; display:flex; gap:.75rem; }
.avatar-initial{ width:36px; height:36px; border-radius:10px; display:grid; place-items:center; background:#bfdbfe; color:#1e3a8a; font-weight:800; }
.bg-warning-subtle{ background:#fef3c7!important; }
.bg-danger-subtle{ background:#fee2e2!important; }
.text-warning-900{ color:#92400e!important; }
.text-danger-900{ color:#7f1d1d!important; }
.text-success-900{ color:#065f46!important; }
.progress-thin{ height:6px; border-radius:999px; background:#eef2ff; }
.progress-thin .bg-abs{ background:#f59e0b; }
.progress-thin .bg-fp{ background:#ef4444; }
.fab-rev{ position:fixed; right:14px; bottom:14px; z-index:1050; border-radius:999px; width:52px; height:52px; display:grid; place-items:center; box-shadow:0 10px 20px rgba(37,99,235,.25); }
@media (max-width: 575.98px){
  .hero-title{ font-size:1.35rem; }
  .metrics-wrap{ grid-template-columns:1fr 1fr; }
}
</style>

<script>
(function(){
  // Filter card event
  const input    = document.getElementById('searchInput');
  const clearBtn = document.getElementById('clearSearch');
  if(input){
    const grids = Array.from(document.querySelectorAll('.js-card-grid'));
    const cards = Array.from(document.querySelectorAll('.js-card'));
    function applyFilter(query){
      const q = (query || '').trim().toLowerCase();
      cards.forEach(card => {
        const hay = (card.dataset.search || card.textContent).toLowerCase();
        const show = !q || hay.includes(q);
        card.parentElement.style.display = show ? '' : 'none';
      });
      clearBtn?.classList.toggle('d-none', !q);
      grids.forEach(grid => {
        const anyVisible = Array.from(grid.children).some(c => c.style.display !== 'none');
        grid.style.minHeight = anyVisible ? '' : '100px';
      });
    }
    input.addEventListener('input', e => applyFilter(e.target.value));
    clearBtn?.addEventListener('click', () => { input.value=''; applyFilter(''); input.focus(); });
  }

  // Pencarian reviewer di panel slide
  const revSearch = document.getElementById('revSearch');
  if(revSearch){
    const items = Array.from(document.querySelectorAll('#revList .js-rev'));
    revSearch.addEventListener('input', e => {
      const s = (e.target.value||'').toLowerCase();
      items.forEach(el=>{
        const hay = (el.dataset.hay||'') + ' ' + el.textContent.toLowerCase();
        el.style.display = !s || hay.includes(s) ? '' : 'none';
      });
    });
  }
})();
</script>
