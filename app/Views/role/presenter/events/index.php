<?php
// app/Views/role/presenter/events/index.php
$title         = $title         ?? 'Event';
$q             = $q             ?? '';
$todoEvents    = $todoEvents    ?? [];   // event yang masih perlu tindakan / sudah daftar (walau closed)
$historyEvents = $historyEvents ?? [];   // event selesai / tidak diikuti & pendaftaran berakhir

$fmtDate = fn($s)=> $s ? date('d M Y', strtotime($s)) : '-';
$fmtDT   = fn($s)=> $s ? date('d M Y H:i', strtotime($s)) : '-';
$formatLabel = function($f){
  $f = strtolower((string)$f);
  return $f === 'both' ? 'Hybrid' : ucfirst($f ?: '-');
};
$badgeToPill = function($b){
  return [
    'success'=>'pill-success','danger'=>'pill-danger',
    'warning'=>'pill-warn','info'=>'pill-info','secondary'=>'pill-muted'
  ][strtolower((string)$b) ?: 'secondary'] ?? 'pill-muted';
};
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
              <h3 class="hero-title mb-1"><i class="bi bi-calendar2-event me-2"></i>Event</h3>
              <div class="text-white-70 small">Ikuti event, lanjutkan progres, dan lihat riwayat.</div>
            </div>
            <div class="hero-tools flex-grow-1" style="max-width:620px;">
              <form method="get" class="w-100" action="<?= site_url('presenter/events') ?>">
                <div class="input-group input-group-lg hero-search">
                  <span class="input-group-text"><i class="bi bi-search"></i></span>
                  <input id="searchInput" name="q" type="text" class="form-control" placeholder="Cari event, status, format…" value="<?= esc($q) ?>">
                  <?php if (!empty($q)): ?>
                    <a class="btn btn-light" href="<?= site_url('presenter/events') ?>"><i class="bi bi-x-circle"></i></a>
                  <?php endif; ?>
                </div>
              </form>
            </div>
          </div>
        </div>

        <!-- Tabs -->
        <div class="hero-tabs">
          <ul class="nav nav-pills" id="evTabs" role="tablist">
            <li class="nav-item" role="presentation">
              <button class="nav-link active" id="tab-todo" data-bs-toggle="pill" data-bs-target="#pane-todo" type="button" role="tab">
                Utama <span class="badge bg-light text-primary ms-1"><?= count($todoEvents) ?></span>
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button class="nav-link" id="tab-history" data-bs-toggle="pill" data-bs-target="#pane-history" type="button" role="tab">
                Riwayat <span class="badge bg-light text-secondary ms-1"><?= count($historyEvents) ?></span>
              </button>
            </li>
          </ul>
        </div>
      </div>

      <div class="tab-content">
        <!-- ==================== TO-DO ==================== -->
        <div class="tab-pane fade show active" id="pane-todo" role="tabpanel" aria-labelledby="tab-todo">
          <div class="row g-3 row-cols-1 row-cols-md-2 row-cols-xl-3 row-cols-xxl-4 js-grid">
            <?php if (empty($todoEvents)): ?>
              <div class="col"><div class="empty-hint">
                <i class="bi bi-check2-circle me-1"></i>Tidak ada pekerjaan. Mantap!
              </div></div>
            <?php else: foreach ($todoEvents as $ev): ?>
              <?php
                $pill        = $badgeToPill($ev['status_badge'] ?? 'secondary');
                $statusLabel = $ev['status_label'] ?? (($ev['is_registered']??false) ? 'Terdaftar' : '—');
                $primaryUrl  = $ev['primary_url'] ?? site_url('presenter/events/detail/'.(int)($ev['id'] ?? 0));
                $primaryTxt  = $ev['primary_text'] ?? (($ev['is_registered']??false) ? 'Lanjutkan' : 'Detail Event');
                $primaryCls  = $ev['primary_class'] ?? 'btn-outline-primary';
                $primaryDis  = !empty($ev['primary_disabled']);
                $detailUrl   = $ev['detail_url']  ?? site_url('presenter/events/detail/'.(int)($ev['id'] ?? 0));
                $isClosed    = (bool)($ev['is_closed'] ?? false);
                $isReg       = (bool)($ev['is_registered'] ?? false);
                $needConfirm = stripos((string)$primaryTxt, 'daftar') !== false; // Alert konfirmasi untuk aksi "Daftar"
                // Gunakan confirm bawaan dari controller jika ada
                $confirmText = $ev['primary_confirm'] ?? ($needConfirm ? 'Daftar ke event ini sekarang?' : null);
              ?>
              <div class="col js-col">
                <div class="fp-card js-card"
                     data-search="<?= esc(strtolower(
                       ($ev['title'] ?? '') . ' ' .
                       ($statusLabel) . ' ' .
                       ($ev['format'] ?? '') . ' ' .
                       ($fmtDate($ev['event_date'] ?? null)) . ' ' .
                       ($fmtDT($ev['registration_deadline'] ?? null))
                     )) ?>">
                  <div class="fp-head">
                    <h6 class="mb-0 fp-title text-truncate" title="<?= esc($ev['title'] ?? '-') ?>">
                      <?= esc($ev['title'] ?? '-') ?>
                    </h6>
                    <span class="status-pill <?= $pill ?>"><?= esc($statusLabel) ?></span>
                  </div>

                  <div class="chip-row mb-2">
                    <span class="chip"><i class="bi bi-laptop me-1"></i><?= esc($formatLabel($ev['format'] ?? '')) ?></span>
                    <span class="chip alt"><i class="bi bi-calendar-event me-1"></i><?= esc($fmtDate($ev['event_date'] ?? null)) ?> <?= esc($ev['event_time'] ?? '') ?></span>
                    <?php if (!empty($ev['registration_deadline'])): ?>
                      <span class="chip ghost <?= $isClosed ? 'chip-muted' : 'chip-info' ?>">
                        <i class="bi bi-hourglass-split me-1"></i><?= esc($fmtDT($ev['registration_deadline'])) ?>
                      </span>
                    <?php endif; ?>
                    <span class="chip ghost <?= $isClosed ? 'chip-muted' : 'chip-info' ?>">
                      <i class="bi <?= $isClosed?'bi-lock':'bi-lightning-charge' ?> me-1"></i>
                      <?= $isClosed ? 'Pendaftaran Ditutup' : 'Pendaftaran Dibuka' ?>
                    </span>
                  </div>

                  <?php if (!empty($ev['hint'])): ?>
                    <div class="mini-hint mt-1"><i class="bi bi-info-circle me-1"></i><?= esc($ev['hint']) ?></div>
                  <?php endif; ?>

                  <div class="fp-foot">
                    <a class="btn <?= esc($primaryCls) ?> flex-fill <?= $primaryDis ? 'disabled' : '' ?> <?= $confirmText ? 'js-swal-confirm' : '' ?>"
                       href="<?= esc($primaryUrl) ?>"
                       <?= $primaryDis ? 'tabindex="-1" aria-disabled="true"' : '' ?>
                       <?= $confirmText ? 'data-confirm="'.esc($confirmText).'"' : '' ?>>
                      <i class="bi bi-play-fill me-1"></i><?= esc($primaryTxt) ?>
                    </a>
                    <a class="btn btn-outline-secondary flex-fill" href="<?= esc($detailUrl) ?>">
                      <i class="bi bi-eye me-1"></i>Detail
                    </a>
                  </div>
                </div>
              </div>
            <?php endforeach; endif; ?>
          </div>
        </div>

        <!-- ==================== RIWAYAT ==================== -->
        <div class="tab-pane fade" id="pane-history" role="tabpanel" aria-labelledby="tab-history">
          <div class="row g-3 row-cols-1 row-cols-md-2 row-cols-xl-3 row-cols-xxl-4 js-grid">
            <?php if (empty($historyEvents)): ?>
              <div class="col"><div class="empty-hint">
                <i class="bi bi-inboxes me-1"></i>Belum ada riwayat event.
              </div></div>
            <?php else: foreach ($historyEvents as $h): ?>
              <?php
                $pillH       = $badgeToPill($h['status_badge'] ?? 'secondary');
                $statusLabel = $h['status_label'] ?? (($h['is_over']??false) ? 'Selesai' : 'Ditutup');
                $detailUrl   = $h['detail_url'] ?? site_url('presenter/events/detail/'.(int)($h['id'] ?? 0));
                $endedText   = ($h['is_over']??false) ? 'Event Selesai' : 'Pendaftaran Berakhir';
              ?>
              <div class="col js-col">
                <div class="fp-card js-card"
                     data-search="<?= esc(strtolower(
                       ($h['title'] ?? '') . ' ' .
                       ($statusLabel) . ' ' .
                       ($h['format'] ?? '') . ' ' .
                       ($fmtDate($h['event_date'] ?? null))
                     )) ?>">
                  <div class="fp-head">
                    <h6 class="mb-0 fp-title text-truncate" title="<?= esc($h['title'] ?? '-') ?>">
                      <?= esc($h['title'] ?? '-') ?>
                    </h6>
                    <span class="status-pill <?= $pillH ?>"><?= esc($statusLabel) ?></span>
                  </div>

                  <div class="chip-row mb-2">
                    <span class="chip"><i class="bi bi-laptop me-1"></i><?= esc($formatLabel($h['format'] ?? '')) ?></span>
                    <?php if (!empty($h['event_date'])): ?>
                      <span class="chip alt"><i class="bi bi-calendar-event me-1"></i><?= esc($fmtDate($h['event_date'])) ?> <?= esc($h['event_time'] ?? '') ?></span>
                    <?php endif; ?>
                    <?php if (!empty($h['registration_deadline'])): ?>
                      <span class="chip ghost chip-muted"><i class="bi bi-hourglass-bottom me-1"></i><?= esc($fmtDT($h['registration_deadline'])) ?></span>
                    <?php endif; ?>
                    <span class="chip ghost chip-danger"><i class="bi bi-flag-fill me-1"></i><?= esc($endedText) ?></span>
                  </div>

                  <?php if (!empty($h['hint'])): ?>
                    <div class="mini-hint mt-1"><i class="bi bi-info-circle me-1"></i><?= esc($h['hint']) ?></div>
                  <?php endif; ?>

                  <div class="fp-foot">
                    <a class="btn btn-outline-primary btn-sm w-100" href="<?= esc($detailUrl) ?>">
                      <i class="bi bi-eye me-1"></i>Lihat Detail / Hasil
                    </a>
                  </div>
                </div>
              </div>
            <?php endforeach; endif; ?>
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
  --muted:#6b7280; --ink:#0f172a; --radius:14px;
  --card-min-h: 320px;
  --side-pad: clamp(1rem, 2.3vw, 2.2rem);
}

.container-xxl{ max-width:min(100%, 1560px); padding-left:var(--side-pad)!important; padding-right:var(--side-pad)!important; margin-inline:auto; }
body{ font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size:15.5px; line-height:1.6; color:var(--ink); }

.page-wrap-blue{
  min-height:100vh; padding-top:72px;
  background:
    radial-gradient(1000px 380px at 10% -10%, rgba(59,130,246,.16), rgba(59,130,246,0) 60%),
    radial-gradient(1000px 380px at 90% 110%, rgba(59,130,246,.12), rgba(59,130,246,0) 70%),
    linear-gradient(180deg, var(--blue-50), #fff 40%);
}

/* HERO */
.card-hero{ border:0; border-radius:var(--radius); overflow:hidden; box-shadow:0 12px 28px rgba(30,64,175,.16); }
.card-hero .hero-body{ background:linear-gradient(135deg,var(--blue-700),var(--blue-800)); color:#fff; padding:1.8rem 1.2rem; }
.hero-title{ font-weight:800; }
.text-white-70{ color:rgba(255,255,255,.85)!important; }
.hero-tools .input-group .input-group-text{ background:#fff; border:0; }
.hero-tools .form-control{ border:0; }
.hero-tools .btn{ border:0; }
.hero-search{ border-radius:12px; overflow:hidden; }
.hero-tabs{ background:#fff; padding:.6rem .8rem; border:1px solid rgba(30,64,175,.14); border-top:0; }
.hero-tabs .nav-link{ font-weight:700; border-radius:999px; padding:.45rem 1rem; }
.hero-tabs .nav-link.active{ background:var(--blue-600); color:#fff; }

/* CARD LIST */
.fp-card{
  border:1px solid rgba(30,64,175,.12); border-radius:14px; background:#fff;
  box-shadow:0 10px 22px rgba(30,64,175,.10);
  padding:.9rem; display:flex; flex-direction:column; min-height:var(--card-min-h);
  transition:transform .18s ease, box-shadow .18s ease, border-color .18s ease;
}
.fp-card:hover{ transform: translateY(-2px); box-shadow:0 16px 28px rgba(30,64,175,.16); border-color: rgba(30,64,175,.22); }
.fp-head{ display:flex; align-items:center; justify-content:space-between; gap:.75rem; margin-bottom:.25rem; }
.fp-title{ line-height:1.35; max-width:72%; color:var(--blue-900); }

/* status pill & chips */
.status-pill{ font-weight:800; font-size:.82rem; padding:.28rem .6rem; border-radius:999px; border:1px solid rgba(0,0,0,.06); white-space:nowrap; }
.pill-info{ background:#e0f2fe; color:#075985; }
.pill-warn{ background:#fef3c7; color:#92400e; }
.pill-success{ background:#d1fae5; color:#065f46; }
.pill-danger{ background:#fee2e2; color:#991b1b; }
.pill-muted{ background:#f3f4f6; color:#374151; }

/* chips */
.chip-row{ display:flex; flex-wrap:wrap; gap:.5rem; }
.chip{ display:inline-flex; align-items:center; padding:.26rem .55rem; font-size:.86rem; border-radius:999px; background:#eef3ff; color:#1e3a8a; border:1px solid rgba(30,64,175,.15); font-weight:700; }
.chip.alt{ background:#f8fafc; color:#0f172a; border-color:#e2e8f0; }
.chip.ghost{ background:#ffffff; color:#0f172a; border-style:dashed; }
.chip-info{ color:#1d4ed8; border-color:#bfdbfe; background:#eff6ff; }
.chip-muted{ color:#475569; border-color:#e2e8f0; background:#f8fafc; }
.chip-danger{ color:#7f1d1d; border-color:#fecaca; background:#fff1f2; }

/* Foot */
.mini-hint{ color:#334155; background:#f8fafc; border:1px dashed #e2e8f0; border-radius:8px; padding:.5rem .6rem; font-weight:600; font-size:.86rem; }
.fp-foot{ display:flex; gap:.6rem; margin-top:auto; padding-top:.6rem; border-top:1px dashed rgba(30,64,175,.16); }
.btn{ font-weight:800; border-radius:10px; font-size:.98rem; padding:.6rem 1.05rem; }
.btn-primary{ background:var(--blue-600); border-color:var(--blue-600); box-shadow:0 4px 12px rgba(37,99,235,.2); }

/* Empty hint */
.empty-hint{ color:#475569; background:#f8fafc; border:1px dashed #e2e8f0; border-radius:10px; padding:.9rem 1rem; font-weight:600; }

/* Responsive */
@media (max-width:767.98px){
  .hero-tools{ width:100%; max-width:none; }
  .card-hero .hero-body{ padding:1.4rem 1rem; }
  .fp-title{ max-width:68%; }
  :root{ --card-min-h: 300px; }
}
@media (max-width:575.98px){
  .container-xxl{ padding-left: calc(var(--side-pad) - .25rem) !important; padding-right: calc(var(--side-pad) - .25rem) !important; }
}
</style>

<script>
/* Optional client-side filter (kalau mau pakai tanpa submit form) */
(function(){
  const input = document.getElementById('searchInput');
  if(!input) return;
  const cards = Array.from(document.querySelectorAll('.js-card'));
  function applyFilter(q){
    const query = (q||'').trim().toLowerCase();
    cards.forEach(card=>{
      const hay = (card.dataset.search || card.textContent).toLowerCase();
      card.closest('.js-col').style.display = (!query || hay.includes(query)) ? '' : 'none';
    });
  }
  input.addEventListener('input', e=>applyFilter(e.target.value));
})();

/* === SweetAlert2 Helpers === */
(function(){
  // Konfirmasi link dengan data-confirm
  document.addEventListener('click', function(e){
    const a = e.target.closest('a.js-swal-confirm');
    if(!a) return;
    const msg = a.getAttribute('data-confirm');
    if(!msg) return; // tidak perlu swal
    e.preventDefault();
    if (typeof Swal === 'undefined') { // fallback bila Swal belum ada
      if (confirm(msg)) window.location.href = a.href;
      return;
    }
    Swal.fire({
      title: 'Konfirmasi',
      text: msg,
      icon: 'question',
      showCancelButton: true,
      confirmButtonText: 'Ya',
      cancelButtonText: 'Batal',
    }).then(res=>{
      if(res.isConfirmed){ window.location.href = a.href; }
    });
  });

  // Flash message CI4 -> SweetAlert2
  <?php
    $flashTypes = ['success','error','warning','info'];
    foreach ($flashTypes as $t):
      $msg = session()->getFlashdata($t);
      if ($msg):
        $title = [
          'success'=>'Berhasil',
          'error'=>'Gagal',
          'warning'=>'Perhatian',
          'info'=>'Info'
        ][$t];
  ?>
  if (typeof Swal !== 'undefined') {
    Swal.fire({icon:'<?= $t ?>', title:'<?= $title ?>', text: '<?= esc($msg) ?>'});
  }
  <?php
      endif;
    endforeach;
  ?>
})();
</script>
