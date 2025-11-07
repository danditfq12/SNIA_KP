<?php
/** @var array $todo @var array $history */
$title   = $title   ?? 'Full Paper';
$todo    = $todo    ?? [];
$history = $history ?? [];

$fmtDate = fn($s)=> $s ? date('d M Y', strtotime($s)) : '-';
$fmtDT   = fn($s)=> $s ? date('d M Y H:i', strtotime($s)) : '-';
$formatLabel = function($f){
  $f = strtolower((string)$f);
  return $f === 'both' ? 'Hybrid' : ucfirst($f ?: '-');
};
$badgeToPill = function($badge){
  $key = strtolower((string)($badge ?? 'secondary'));
  return [
    'success'=>'pill-success','danger'=>'pill-danger',
    'warning'=>'pill-warn','info'=>'pill-info','secondary'=>'pill-muted','primary'=>'pill-primary'
  ][$key] ?? 'pill-muted';
};
$absTitle   = fn(array $row) => trim((string)($row['abs_title'] ?? $row['abstract_title'] ?? $row['paper_title'] ?? ''));
$eventTitle = fn(array $row) => trim((string)($row['event_title'] ?? $row['title'] ?? '-'));
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_presenter') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill page-wrap-blue">
    <div class="container-xxl px-3 px-md-4 py-4">

      <!-- HERO -->
      <div class="card-hero mb-4">
        <div class="hero-body">
          <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div>
              <h3 class="hero-title mb-1"><i class="bi bi-file-earmark-text me-2"></i>Full Paper</h3>
              <div class="text-white-70 small">Kelola unggahan & pantau progres penilaian.</div>
            </div>
            <div class="hero-tools flex-grow-1" style="max-width:620px;">
              <div class="input-group input-group-lg hero-search">
                <span class="input-group-text"><i class="bi bi-search"></i></span>
                <input id="searchInput" type="text" class="form-control" placeholder="Cari judul abstrak, event, status…">
                <button id="clearSearch" type="button" class="btn btn-light d-none"><i class="bi bi-x-circle"></i></button>
              </div>
            </div>
          </div>
        </div>

        <!-- Tabs -->
        <div class="hero-tabs">
          <ul class="nav nav-pills" id="fpTabs" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="tab-todo" data-bs-toggle="pill" data-bs-target="#pane-todo" type="button" role="tab">
                To-Do <span class="badge bg-light text-primary ms-1"><?= count($todo) ?></span>
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="tab-history" data-bs-toggle="pill" data-bs-target="#pane-history" type="button" role="tab">
                Riwayat <span class="badge bg-light text-secondary ms-1"><?= count($history) ?></span>
              </button>
            </li>
          </ul>
        </div>
      </div>

      <div class="tab-content">
        <!-- TO-DO -->
        <div class="tab-pane fade show active" id="pane-todo" role="tabpanel" aria-labelledby="tab-todo">
          <div class="row g-3 row-cols-1 row-cols-md-2 row-cols-xl-3 row-cols-xxl-4 js-grid">
            <?php
            $hasTodo = false;
            foreach ($todo as $row):
              if (empty($row['has_abstract'])) { continue; } // hanya tampil jika abstrak SUDAH diupload
              $hasTodo = true;

              $titleAbs   = $absTitle($row);
              $titleEvent = $eventTitle($row);
              $pill       = $badgeToPill($row['status_badge'] ?? 'secondary');

              $searchable = strtolower(trim(
                ($titleAbs ?: '') . ' ' . ($titleEvent ?: '') . ' ' .
                ($row['status_label'] ?? '') . ' ' .
                ($row['format'] ?? '') . ' ' .
                $fmtDate($row['event_date'] ?? null) . ' ' .
                $fmtDT($row['full_paper_deadline'] ?? null)
              ));
            ?>
              <div class="col js-col">
                <div class="fp-card js-card" data-search="<?= esc($searchable) ?>">
                  <div class="fp-head">
                    <div class="fp-title-wrap">
                      <h6 class="mb-0 fp-title"><?= esc($titleAbs !== '' ? $titleAbs : ($titleEvent ?: '-')) ?></h6>
                      <?php if ($titleEvent && $titleEvent !== '-'): ?>
                        <div class="fp-subtitle text-muted small">
                          <i class="bi bi-calendar-event me-1"></i><?= esc($titleEvent) ?>
                        </div>
                      <?php endif; ?>
                    </div>
                    <span class="status-pill <?= $pill ?>">
                      <?= esc(($row['status_label'] ?? '-')) ?>
                    </span>
                  </div>

                  <div class="chip-row mb-2">
                    <span class="chip"><i class="bi bi-laptop me-1"></i><?= esc($formatLabel($row['format'] ?? '')) ?></span>
                    <?php if (!empty($row['full_paper_deadline'])): ?>
                      <span class="chip alt"><i class="bi bi-clock-history me-1"></i><?= esc($fmtDT($row['full_paper_deadline'])) ?></span>
                    <?php endif; ?>
                  </div>

                  <ul class="meta-list">
                    <li><span>Tanggal Event</span><strong><?= esc($fmtDate($row['event_date'] ?? null)) ?></strong></li>
                    <li><span>Deadline FP</span><strong><?= esc($fmtDT($row['full_paper_deadline'] ?? null)) ?></strong></li>
                  </ul>

                  <?php if (!empty($row['auto_rejected'])): ?>
                    <div class="mini-alert mini-alert-danger mt-2">
                      <i class="bi bi-x-octagon me-1"></i>Lewat batas waktu revisi — ditolak otomatis.
                    </div>
                  <?php endif; ?>

                  <div class="fp-foot">
                    <?php if (empty($row['has_fullpaper'])): ?>
                      <!-- belum ada FP => hanya tombol Upload -->
                      <a class="btn btn-primary flex-fill" href="<?= site_url('presenter/fullpaper/create/'.(int)$row['event_id']) ?>">
                        <i class="bi bi-upload me-1"></i><?= esc($row['primary_btn_label'] ?? 'Upload Full Paper') ?>
                      </a>
                    <?php else: ?>
                      <!-- sudah ada FP -->
                      <?php if (!empty($row['can_upload'])): ?>
                        <a class="btn btn-primary flex-fill" href="<?= site_url('presenter/fullpaper/create/'.(int)$row['event_id']) ?>">
                          <i class="bi bi-arrow-repeat me-1"></i>Upload Revisi
                        </a>
                      <?php endif; ?>
                      <a class="btn btn-outline-secondary flex-fill" href="<?= site_url('presenter/fullpaper/detail/'.(int)$row['event_id']) ?>">
                        <i class="bi bi-eye me-1"></i>Detail
                      </a>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            <?php endforeach; if (!$hasTodo): ?>
              <div class="col">
                <div class="empty-hint"><i class="bi bi-check2-circle me-1"></i>Tidak ada pekerjaan. Mantap!</div>
              </div>
            <?php endif; ?>
          </div>
        </div>

        <!-- RIWAYAT -->
        <div class="tab-pane fade" id="pane-history" role="tabpanel" aria-labelledby="tab-history">
          <div class="row g-3 row-cols-1 row-cols-md-2 row-cols-xl-3 row-cols-xxl-4 js-grid">
            <?php
            $hasHist = false;
            foreach ($history as $h):
              if (empty($h['has_abstract'])) { continue; }
              $hasHist = true;

              $titleAbsH   = $absTitle($h);
              $titleEventH = $eventTitle($h);
              $pillH       = $badgeToPill($h['status_badge'] ?? 'secondary');

              $searchableH = strtolower(trim(
                ($titleAbsH ?: '') . ' ' . ($titleEventH ?: '') . ' ' .
                ($h['status_label'] ?? '') . ' ' .
                ($h['format'] ?? '') . ' ' .
                $fmtDate($h['event_date'] ?? null) . ' ' .
                $fmtDT($h['uploaded_at'] ?? null)
              ));
            ?>
              <div class="col js-col">
                <div class="fp-card js-card" data-search="<?= esc($searchableH) ?>">
                  <div class="fp-head">
                    <div class="fp-title-wrap">
                      <h6 class="mb-0 fp-title"><?= esc($titleAbsH !== '' ? $titleAbsH : ($titleEventH ?: '-')) ?></h6>
                      <?php if ($titleEventH && $titleEventH !== '-'): ?>
                        <div class="fp-subtitle text-muted small">
                          <i class="bi bi-calendar-event me-1"></i><?= esc($titleEventH) ?>
                        </div>
                      <?php endif; ?>
                    </div>
                    <span class="status-pill <?= $pillH ?>">
                      <?= esc(($h['status_label'] ?? '-')) ?>
                    </span>
                  </div>

                  <div class="chip-row mb-2">
                    <span class="chip alt"><i class="bi bi-laptop me-1"></i><?= esc($formatLabel($h['format'] ?? '')) ?></span>
                    <?php if (!empty($h['uploaded_at'])): ?>
                      <span class="chip"><i class="bi bi-cloud-arrow-up me-1"></i><?= esc($fmtDT($h['uploaded_at'])) ?></span>
                    <?php endif; ?>
                  </div>

                  <ul class="meta-list">
                    <li><span>Tanggal Event</span><strong><?= esc($fmtDate($h['event_date'] ?? null)) ?></strong></li>
                    <li><span>Deadline FP</span><strong><?= esc($fmtDT($h['full_paper_deadline'] ?? null)) ?></strong></li>
                  </ul>

                  <?php if (!empty($h['auto_rejected'])): ?>
                    <div class="mini-alert mini-alert-danger mt-2"><i class="bi bi-x-octagon me-1"></i>Lewat batas waktu revisi — ditolak otomatis.</div>
                  <?php endif; ?>

                  <div class="fp-foot">
                    <a class="btn btn-outline-primary btn-sm w-100" href="<?= site_url('presenter/fullpaper/detail/'.(int)$h['event_id']) ?>">
                      <i class="bi bi-eye me-1"></i>Detail
                    </a>
                  </div>
                </div>
              </div>
            <?php endforeach; if (!$hasHist): ?>
              <div class="col">
                <div class="empty-hint"><i class="bi bi-inboxes me-1"></i>Belum ada riwayat final.</div>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </div>

    </div>
  </main>
</div>

<?= $this->include('partials/footer') ?>

<style>
:root{
  --blue-50:#eff6ff; --blue-100:#dbeafe; --blue-200:#bfdbfe; --blue-300:#93c5fd;
  --blue-400:#60a5fa; --blue-500:#3b82f6; --blue-600:#2563eb; --blue-700:#1d4ed8; --blue-800:#1e40af; --blue-900:#1e3a8a;
  --muted:#6b7280; --ink:#0f172a; --radius:16px;
  --card-min-h: 320px;
  --side-pad: clamp(1rem, 2.3vw, 2.2rem);
}
.container-xxl{ max-width:min(100%, 1560px); padding-left: var(--side-pad) !important; padding-right: var(--side-pad) !important; margin-inline:auto; }
body{ font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size:15.5px; line-height:1.6; color:var(--ink); }
.page-wrap-blue{
  min-height:100vh; padding-top:72px;
  background:
    radial-gradient(1000px 380px at 10% -10%, rgba(59,130,246,.16), rgba(59,130,246,0) 60%),
    radial-gradient(1000px 380px at 90% 110%, rgba(59,130,246,.12), rgba(59,130,246,0) 70%),
    linear-gradient(180deg, var(--blue-50), #fff 40%);
}
.card-hero{ border:0; border-radius:var(--radius); overflow:hidden; box-shadow:0 12px 28px rgba(30,64,175,.18); }
.card-hero .hero-body{ background:linear-gradient(135deg,var(--blue-700),var(--blue-800)); color:#fff; padding:1.8rem 1.2rem; min-height:176px; }
.hero-title{ font-weight:800; }
.text-white-70{ color:rgba(255,255,255,.85)!important; }
.hero-tools .input-group .input-group-text{ background:#fff; border:0; }
.hero-tools .form-control{ border:0; }
.hero-tools .btn{ border:0; }
.hero-search{ border-radius:12px; overflow:hidden; }
.hero-tabs{ background:#fff; padding:.6rem .8rem; border:1px solid rgba(30,64,175,.18); border-top:0; }
.hero-tabs .nav-link.active{ background:var(--blue-600); color:#fff; }
.hero-tabs .nav-link{ font-weight:700; border-radius:999px; padding:.45rem 1rem; }

.fp-card{ border:1px solid rgba(30,64,175,.12); border-radius:14px; background:#fff; box-shadow:0 10px 22px rgba(30,64,175,.10);
  padding:0.9rem; display:flex; flex-direction:column; min-height:var(--card-min-h);
  transition:transform .18s ease, box-shadow .18s ease, border-color .18s ease; }
.fp-card:hover{ transform: translateY(-2px); box-shadow:0 16px 28px rgba(30,64,175,.16); border-color: rgba(30,64,175,.22); }
.fp-head{ display:flex; align-items:flex-start; justify-content:space-between; gap:.75rem; margin-bottom:.25rem; }
.fp-title-wrap{ max-width:72%; }
.fp-title{ line-height:1.35; color:var(--blue-900); }
.fp-subtitle{ margin-top:2px; }

.status-pill{ font-weight:800; font-size:.82rem; padding:.28rem .6rem; border-radius:999px; border:1px solid rgba(0,0,0,.06); white-space:nowrap; }
.pill-info{ background:#e0f2fe; color:#075985; }
.pill-warn{ background:#fef3c7; color:#92400e; }
.pill-success{ background:#d1fae5; color:#065f46; }
.pill-danger{ background:#fee2e2; color:#991b1b; }
.pill-muted{ background:#f3f4f6; color:#374151; }

.chip-row{ display:flex; flex-wrap:wrap; gap:.5rem; }
.chip{ display:inline-flex; align-items:center; padding:.26rem .55rem; font-size:.86rem; border-radius:999px; background:#eef3ff; color:#1e3a8a; border:1px solid rgba(30,64,175,.15); font-weight:700; }
.chip.alt{ background:#f1f5ff; color:#244aa4; }

.meta-list{ list-style:none; padding-left:0; margin:.4rem 0 .2rem 0; }
.meta-list li{ display:flex; align-items:center; justify-content:space-between; gap:.75rem; padding:.36rem 0; }
.meta-list li span{ color:#6b7280; }

.mini-alert{ border-radius:10px; padding:.5rem .6rem; font-weight:700; font-size:.86rem; }
.mini-alert-danger{ background:#fff1f2; color:#7f1d1d; border:1px solid #fecaca; }

.fp-foot{ display:flex; gap:.6rem; margin-top:auto; padding-top:.6rem; border-top:1px dashed rgba(30,64,175,.16); }
.btn{ font-weight:800; border-radius:10px; font-size:.98rem; padding:.6rem 1.05rem; }
.btn-primary{ background:var(--blue-600); border-color:var(--blue-600); box-shadow:0 4px 12px rgba(37,99,235,.2); }

@media (max-width:767.98px){
  .hero-tools{ width:100%; max-width:none; }
  .card-hero .hero-body{ padding:1.4rem 1rem; min-height:165px; }
  .fp-title-wrap{ max-width:68%; }
  :root{ --card-min-h: 300px; }
}
@media (max-width:575.98px){
  .container-xxl{ padding-left: calc(var(--side-pad) - .25rem) !important; padding-right: calc(var(--side-pad) - .25rem) !important; }
}
</style>

<script>
(function(){
  const input = document.getElementById('searchInput');
  const clearBtn = document.getElementById('clearSearch');
  const cards = Array.from(document.querySelectorAll('.js-card'));
  function applyFilter(q){
    const query = (q||'').trim().toLowerCase();
    cards.forEach(card=>{
      const hay = (card.dataset.search || card.textContent).toLowerCase();
      card.closest('.js-col').style.display = (!query || hay.includes(query)) ? '' : 'none';
    });
    if(clearBtn) clearBtn.classList.toggle('d-none', !query);
  }
  if(input){ input.addEventListener('input', e=>applyFilter(e.target.value)); }
  if(clearBtn){ clearBtn.addEventListener('click', ()=>{ input.value=''; applyFilter(''); input.focus(); }); }
})();
</script>
