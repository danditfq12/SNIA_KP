<?php
$title    = $title ?? 'Kelola Paper';
$aktif    = $aktif ?? [];
$berakhir = $berakhir ?? [];

/** helper tanggal */
$fmtDate = function ($date, $withTime = false) {
  if (!$date) return '-';
  $ts = strtotime((string)$date);
  return $withTime ? date('d M Y H:i', $ts) : date('d M Y', $ts);
};
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_admin') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill page-wrap-blue">
    <div class="container-xxl px-3 px-md-4 py-4">

      <!-- Hero -->
      <div class="hero-blue card-glass mb-3 p-3 p-md-4 d-flex justify-content-between align-items-start gap-3">
        <div>
          <h3 class="hero-title mb-1"><i class="bi bi-journal-text me-2"></i>Kelola Paper</h3>
          <div class="text-white-75 small">Jembatan untuk menugaskan reviewer Abstrak & Full Paper per event</div>
        </div>
        <div class="d-none d-md-block text-end">
          <div class="text-white-75 small">Hari ini</div>
          <div class="fw-semibold text-white"><?= date('d M Y') ?></div>
        </div>
      </div>

      <!-- Event Aktif/Mendatang -->
      <div class="card shadow-soft card-glass-plain mb-4">
        <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
          <span class="badge bg-blue-soft"><i class="bi bi-lightning-charge"></i></span>
          <h5 class="mb-0 fw-semibold text-blue-900">Event Aktif / Mendatang</h5>
        </div>
        <div class="card-body">
          <?php if (empty($aktif)): ?>
            <div class="text-muted">Tidak ada event aktif/mendatang.</div>
          <?php else: ?>
            <div class="row g-3">
              <?php foreach ($aktif as $e): ?>
              <?php
                $id  = (int)($e['id'] ?? 0);
                $cntPresenter  = (int)($e['presenter_count'] ?? 0);
                $cntAbsUnasg   = (int)($e['abs_unassigned'] ?? 0);
                $cntFpUnasg    = (int)($e['fp_unassigned'] ?? 0);
              ?>
              <div class="col-12 col-md-6 col-xl-4">
                <div class="event-card h-100 p-3">
                  <div class="d-flex align-items-start justify-content-between mb-2">
                    <h6 class="mb-0 text-blue-900"><?= esc($e['title'] ?? '-') ?></h6>
                    <span class="badge bg-success-subtle">Aktif</span>
                  </div>

                  <div class="small text-muted mb-2">
                    Tanggal Event:
                    <strong class="text-blue-900"><?= esc($fmtDate($e['event_date'] ?? null)) ?> <?= esc($e['event_time'] ?? '') ?></strong><br>
                    Batas Abstrak:
                    <strong class="text-blue-900"><?= esc($fmtDate($e['abstract_deadline'] ?? null, true)) ?></strong><br>
                    Batas Full Paper:
                    <strong class="text-blue-900"><?= esc($fmtDate($e['full_paper_deadline'] ?? null, true)) ?></strong>
                  </div>

                  <!-- Metrik ringkas -->
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
                    <a class="btn btn-outline-primary flex-fill"
                       href="<?= site_url('admin/kelola-paper/event/'.$id) ?>">
                      <i class="bi bi-people me-1"></i>Lihat Presenter
                    </a>
                  </div>
                </div>
              </div>
              <?php endforeach; ?>
            </div>
          <?php endif; ?>
        </div>
      </div>

      <!-- Event Berakhir -->
      <div class="card shadow-soft card-glass-plain mb-4">
        <div class="card-header bg-transparent border-0 pb-0 d-flex align-items-center gap-2">
          <span class="badge bg-blue-soft"><i class="bi bi-lock"></i></span>
          <h5 class="mb-0 fw-semibold text-blue-900">Event Berakhir</h5>
        </div>
        <div class="card-body">
          <?php if (empty($berakhir)): ?>
            <div class="text-muted">Belum ada event berakhir.</div>
          <?php else: ?>
            <div class="row g-3">
              <?php foreach ($berakhir as $e): ?>
              <?php
                $id  = (int)($e['id'] ?? 0);
                $cntPresenter  = (int)($e['presenter_count'] ?? 0);
                $cntAbsUnasg   = (int)($e['abs_unassigned'] ?? 0);
                $cntFpUnasg    = (int)($e['fp_unassigned'] ?? 0);
              ?>
              <div class="col-12 col-md-6 col-xl-4">
                <div class="event-card h-100 p-3 opacity-90">
                  <div class="d-flex align-items-start justify-content-between mb-2">
                    <h6 class="mb-0 text-blue-900"><?= esc($e['title'] ?? '-') ?></h6>
                    <span class="badge bg-secondary-subtle">Selesai</span>
                  </div>

                  <div class="small text-muted mb-2">
                    Tanggal Event:
                    <strong class="text-blue-900"><?= esc($fmtDate($e['event_date'] ?? null)) ?> <?= esc($e['event_time'] ?? '') ?></strong><br>
                    Batas Abstrak:
                    <strong class="text-blue-900"><?= esc($fmtDate($e['abstract_deadline'] ?? null, true)) ?></strong><br>
                    Batas Full Paper:
                    <strong class="text-blue-900"><?= esc($fmtDate($e['full_paper_deadline'] ?? null, true)) ?></strong>
                  </div>

                  <!-- Metrik ringkas -->
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
                       href="<?= site_url('admin/kelola-paper/event/'.$id) ?>">
                      <i class="bi bi-people me-1"></i>Lihat Presenter
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
/* === gaya ringkas (selaras) === */
:root{
  --blue-50:#eff6ff; --blue-200:#bfdbfe; --blue-600:#2563eb;
  --blue-700:#1d4ed8; --blue-800:#1e40af; --blue-900:#1e3a8a;
}
.page-wrap-blue{ background:linear-gradient(180deg,var(--blue-50),#fff 40%); min-height:100vh; padding-top:72px; }
.container-xxl{ max-width:1400px; }
.hero-blue{
  background:radial-gradient(1200px 400px at 10% -20%,var(--blue-600) 0,var(--blue-700) 40%,var(--blue-800) 100%)!important;
  color:#fff!important; border-radius:16px; border:1px solid rgba(255,255,255,.15);
  box-shadow:0 12px 28px rgba(30,64,175,.10);
}
.text-white-75{ color:rgba(255,255,255,.85)!important; }
.card-glass-plain{ backdrop-filter:blur(6px); background:rgba(255,255,255,.94); border-radius:14px; border:1px solid rgba(30,64,175,.10); }
.shadow-soft{ box-shadow:0 10px 24px rgba(30,64,175,.08); }
.bg-blue-soft{ background:var(--blue-200); color:var(--blue-900); border-radius:12px; padding:.4rem .6rem; font-weight:600; font-size:.85rem; }
.bg-success-subtle{ background:#d1fae5!important; color:#065f46!important; }
.bg-secondary-subtle{ background:#f1f5f9!important; color:#475569!important; }
.text-blue-900{ color:var(--blue-900)!important; }
.event-card{ background:#fff; border:1px solid rgba(30,64,175,.10); border-radius:14px; box-shadow:0 10px 22px rgba(30,64,175,.08); }
.opacity-90{ opacity:.92; }
.btn{ border-radius:10px; }
.btn-outline-primary{ border-color:var(--blue-600); color:var(--blue-600); }
.btn-outline-primary:hover{ background:var(--blue-600); color:#fff; }
.btn-outline-secondary{ border-color:#475569; color:#475569; }
.btn-outline-secondary:hover{ background:#475569; color:#fff; }

/* metric pills */
.metrics-wrap{
  display:grid; grid-template-columns:repeat(3,1fr); gap:.5rem;
}
.metric-pill{
  border:1px solid rgba(30,64,175,.12);
  background:linear-gradient(180deg,#fff,rgba(255,255,255,.96));
  border-radius:10px; padding:.55rem .6rem;
  display:flex; flex-direction:column; align-items:flex-start; justify-content:center;
  min-height:64px;
}
.metric-pill .label{ font-size:.78rem; color:#64748b; font-weight:600; }
.metric-pill .value{ font-size:1.05rem; font-weight:800; color:var(--blue-900); line-height:1.2; }
.metric-pill.warn .value{ color:#b45309; }     /* amber-700 */
.metric-pill.danger .value{ color:#b91c1c; }   /* red-700 */

@media (max-width:575.98px){
  .metrics-wrap{ grid-template-columns:1fr 1fr; }
}
</style>