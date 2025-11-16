<?php
/**
 * Reviewer Dashboard (simple, schema-tolerant)
 */

$title     = $title ?? 'Reviewer Dashboard';
$cards     = $cards ?? ['total'=>0,'done'=>0];
$incoming  = $incoming ?? [];
$activities= $activities ?? [];

$total   = (int)($cards['total'] ?? 0);
$done    = (int)($cards['done']  ?? 0);
$masuk   = (int)count($incoming);
$belum   = max(0, $total - $done);
$selesai = $done;

$hasAssign    = (bool)($hasAssign    ?? false);
$hasAssignAbs = (bool)($hasAssignAbs ?? false);
$hasAssignFp  = (bool)($hasAssignFp  ?? false);

$fmt = function($d,$withTime=false){
  if (!$d) return '-';
  return date($withTime?'d M Y H:i':'d M Y', strtotime($d));
};

// sapaan singkat
$rawName   = (string) (session('nama_lengkap') ?? session('nama') ?? session('username') ?? 'Reviewer');
$firstName = trim(explode(' ', $rawName)[0]) ?: 'Reviewer';
$hour  = (int)date('G');
$waktu = ($hour>=5 && $hour<11) ? 'Pagi' : (($hour>=11 && $hour<15) ? 'Siang' : (($hour>=15 && $hour<18) ? 'Sore' : 'Malam'));
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_reviewer') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill page-wrap-blue">
    <div class="container-xxl px-3 px-md-4 py-4">

      <!-- HERO -->
      <div class="card-hero mb-4">
        <div class="hero-body">
          <h3 class="hero-title mb-1">Halo, <?= esc($firstName) ?> 👋</h3>
          <div class="text-white-70 small">Selamat <?= esc($waktu) ?> — ini ringkasan tugas & aktivitas Anda.</div>
        </div>
      </div>

      <!-- KPI -->
      <div class="row g-3 mb-4">
        <div class="col-12 col-xl-4">
          <div class="kpi-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
              <div class="kpi-icon kpi-blue"><i class="bi bi-hourglass-split"></i></div>
              <div>
                <div class="kpi-label">Tugas Belum Direview</div>
                <div class="kpi-number" data-count="<?= $belum ?>"><?= number_format($belum) ?></div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-12 col-xl-4">
          <div class="kpi-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
              <div class="kpi-icon kpi-purple"><i class="bi bi-inbox"></i></div>
              <div>
                <div class="kpi-label">Tugas Masuk</div>
                <div class="kpi-number" data-count="<?= $masuk ?>"><?= number_format($masuk) ?></div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-12 col-xl-4">
          <div class="kpi-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
              <div class="kpi-icon kpi-green"><i class="bi bi-check2-circle"></i></div>
              <div>
                <div class="kpi-label">Tugas Selesai</div>
                <div class="kpi-number" data-count="<?= $selesai ?>"><?= number_format($selesai) ?></div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- GRID: Konfirmasi kiri & Aktivitas kanan -->
      <div class="row g-3">
        <!-- LEFT: TUGAS MASUK GABUNGAN -->
        <div class="col-12 col-lg-8">
          <div class="card admin-card h-100" id="incoming">
            <div class="card-header d-flex align-items-center justify-content-between">
              <h5 class="mb-0"><i class="bi bi-clipboard-check me-2 text-primary"></i>Tugas Masuk (Perlu Konfirmasi)</h5>
              <span class="badge bg-primary-subtle text-primary fw-semibold"><?= (int)count($incoming) ?> item</span>
            </div>
            <div class="card-body p-0">
              <?php if (!$incoming): ?>
                <div class="empty-state my-4">
                  <div class="empty-icon"><i class="bi bi-check-circle"></i></div>
                  <div class="empty-title">Tidak ada tugas menunggu konfirmasi</div>
                  <div class="empty-subtitle">Tugas baru akan otomatis tampil di sini.</div>
                </div>
              <?php else: ?>

                <?php if (!$hasAssignAbs || !$hasAssignFp): ?>
                  <div class="alert alert-warning m-3">
                    <div class="d-flex align-items-start gap-2">
                      <i class="bi bi-exclamation-triangle-fill"></i>
                      <div>
                        <strong>Catatan skema:</strong>
                        <?php if (!$hasAssignAbs && !$hasAssignFp): ?>
                          Pivot abstrak & fullpaper belum memiliki kolom status konfirmasi.
                          Tombol tetap berfungsi (fallback), namun disarankan menambahkan
                          <code>assignment_status</code> (opsional: <code>accepted_at</code>, <code>declined_at</code>, <code>decline_reason</code>).
                        <?php elseif (!$hasAssignAbs): ?>
                          Pivot <em>abstrak</em> belum memiliki kolom status konfirmasi. Tombol tetap berfungsi (fallback).
                        <?php else: ?>
                          Pivot <em>full paper</em> belum memiliki kolom status konfirmasi. Tombol tetap berfungsi (fallback).
                        <?php endif; ?>
                      </div>
                    </div>
                  </div>
                <?php endif; ?>

                <div class="scroll-area" style="max-height:60vh;">
                  <div class="task-list p-3">
                    <?php foreach ($incoming as $t): ?>
                      <?php
                        $type     = strtolower($t['task_type'] ?? 'abstrak'); // abstrak|fullpaper
                        $title    = $t['judul'] ?? '(Tanpa judul)';
                        $event    = $t['event_title'] ?? '—';
                        $uploadAt = $fmt($t['tanggal_upload'] ?? null, true);
                        $id       = ($type === 'abstrak')
                                      ? (int)($t['id_abstrak'] ?? $t['id'] ?? 0)
                                      : (int)($t['id_submission'] ?? $t['id'] ?? 0);
                      ?>
                      <div class="task-item">
                        <div class="task-left">
                          <div class="task-title">
                            <span class="truncate-2"><?= esc($title) ?></span>

                            <!-- CHIP JENIS TUGAS -->
                            <span class="type-chip <?= $type==='abstrak' ? 'type-abs' : 'type-fp' ?>"
                                  title="<?= $type==='abstrak' ? 'Abstrak' : 'Full Paper' ?>">
                              <i class="bi <?= $type==='abstrak' ? 'bi-journal-text' : 'bi-file-earmark-text' ?> me-1"></i>
                              <strong><?= $type==='abstrak' ? 'Abstrak' : 'Full Paper' ?></strong>
                            </span>
                          </div>
                          <div class="task-meta">
                            <span><i class="bi bi-calendar-event me-1"></i><?= esc($event) ?></span>
                            <span><i class="bi bi-upload me-1"></i><?= esc($uploadAt) ?></span>
                          </div>
                        </div>
                        <div class="task-actions">
                          <form class="m-0" method="post" action="<?= site_url('reviewer/dashboard/confirm') ?>">
                            <?= csrf_field() ?>
                            <input type="hidden" name="type"   value="<?= esc($type) ?>">
                            <input type="hidden" name="id"     value="<?= (int)$id ?>">
                            <input type="hidden" name="action" value="accept">
                            <button class="btn btn-success btn-sm">
                              <i class="bi bi-check-lg me-1"></i>Terima
                            </button>
                          </form>

                          <button class="btn btn-outline-danger btn-sm btn-decline"
                                  data-type="<?= esc($type) ?>"
                                  data-id="<?= (int)$id ?>"
                                  data-title="<?= esc($title,'attr') ?>">
                            <i class="bi bi-x-lg me-1"></i>Tolak
                          </button>
                        </div>
                      </div>
                    <?php endforeach; ?>
                  </div>
                </div>
              <?php endif; ?>
            </div>
            <div class="card-footer small text-muted">
              <i class="bi bi-exclamation-triangle-fill text-warning me-1"></i>
              Penolakan <b>wajib</b> mencantumkan alasan yang jelas.
            </div>
          </div>
        </div>

        <!-- RIGHT: AKTIVITAS / NOTIFIKASI -->
        <div class="col-12 col-lg-4">
          <div class="card admin-card h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
              <h5 class="mb-0">
                <i class="bi bi-bell me-2 text-info"></i>Notifikasi & Pengingat
              </h5>
              <?php if (!empty($activities)): ?>
                <span class="badge bg-info-subtle text-info fw-semibold"><?= count($activities) ?></span>
              <?php endif; ?>
            </div>
            <div class="card-body p-0">
              <?php if (empty($activities)): ?>
                <div class="empty-state my-4">
                  <div class="empty-icon"><i class="bi bi-inboxes"></i></div>
                  <div class="empty-title">Belum ada notifikasi</div>
                  <div class="empty-subtitle">Pengingat deadline abstrak & full paper akan tampil di sini.</div>
                </div>
              <?php else: ?>
                <div class="scroll-area" style="max-height:60vh;">
                  <ul class="list-unstyled m-0 p-2 activity-list">
                    <?php foreach ($activities as $a):
                      // Struktur: title, desc, icon, badge, time (timestamp|text), link, type (opsional)
                      $badge = strtolower($a['badge'] ?? '');
                      $pill  = $badge==='success' ? 'pill-success'
                             :($badge==='warning' ? 'pill-warn'
                             :($badge==='danger'  ? 'pill-danger'
                             :($badge==='info'    ? 'pill-info' : 'pill-muted')));
                      $link  = !empty($a['link']) ? (string)$a['link'] : 'javascript:void(0)';
                      $timeV = $a['time'] ?? null;
                      $timeT = is_numeric($timeV) ? date('d M Y H:i', (int)$timeV)
                             : (is_string($timeV) ? $timeV : '');
                      $type  = strtolower($a['type'] ?? '');
                      // chip jenis notif
                      $chipLabel = $type==='abstrak_deadline'       ? 'Abstrak'
                                  :($type==='fullpaper_deadline'   ? 'Full Paper'
                                  :($type==='fullpaper_revision'   ? 'Revisi'
                                  : 'Info'));
                    ?>
                      <li class="notif-item">
                        <div class="notif-line <?= esc($pill) ?>">
                          <div class="notif-icon-wrap">
                            <span class="notif-icon">
                              <i class="bi <?= esc($a['icon'] ?? 'bi-bell') ?>"></i>
                            </span>
                          </div>
                          <div class="notif-content flex-fill">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                              <a href="<?= esc($link) ?>" class="notif-title text-decoration-none">
                                <?= esc($a['title'] ?? '-') ?>
                              </a>
                              <?php if ($chipLabel): ?>
                                <span class="notif-chip">
                                  <?= esc($chipLabel) ?>
                                </span>
                              <?php endif; ?>
                            </div>
                            <?php if (!empty($a['desc'])): ?>
                              <div class="notif-desc"><?= esc($a['desc']) ?></div>
                            <?php endif; ?>

                            <div class="d-flex justify-content-between align-items-center mt-1">
                              <?php if ($timeT): ?>
                                <small class="text-muted d-inline-flex align-items-center gap-1">
                                  <i class="bi bi-clock-history"></i>
                                  <span><?= esc($timeT) ?></span>
                                </small>
                              <?php else: ?>
                                <span></span>
                              <?php endif; ?>
                              <!-- urgency badge kecil di kanan -->
                              <small class="notif-urgency <?= esc($pill) ?>">
                                <?php if ($badge==='danger'): ?>
                                  <i class="bi bi-exclamation-octagon me-1"></i>Mendesak
                                <?php elseif ($badge==='warning'): ?>
                                  <i class="bi bi-exclamation-triangle me-1"></i>Segera
                                <?php elseif ($badge==='success'): ?>
                                  <i class="bi bi-check-circle me-1"></i>Info
                                <?php else: ?>
                                  <i class="bi bi-info-circle me-1"></i>Pengingat
                                <?php endif; ?>
                              </small>
                            </div>
                          </div>
                        </div>
                      </li>
                    <?php endforeach; ?>
                  </ul>
                </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- MODAL: Decline reason (wajib) -->
    <div class="modal fade" id="declineModal" tabindex="-1" aria-hidden="true">
      <div class="modal-dialog">
        <form method="post" action="<?= site_url('reviewer/dashboard/confirm') ?>" class="modal-content" id="declineForm">
          <?= csrf_field() ?>
          <input type="hidden" name="type"   id="declType">
          <input type="hidden" name="id"     id="declId">
          <input type="hidden" name="action" value="decline">
          <div class="modal-header">
            <h6 class="modal-title"><i class="bi bi-x-octagon text-danger me-2"></i>Tolak Tugas</h6>
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
            <button class="btn btn-danger" type="submit"><i class="bi bi-x-lg me-1"></i>Tolak</button>
          </div>
        </form>
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
  --side-pad: clamp(1rem, 2.3vw, 2.2rem);
}

/* Container */
.container-xxl{
  max-width: min(100%, 1480px);
  padding-left: var(--side-pad) !important;
  padding-right: var(--side-pad) !important;
  margin-inline: auto;
}

.page-wrap-blue{
  min-height:100vh; padding-top:72px;
  background:
    radial-gradient(900px 350px at 10% -10%, rgba(59,130,246,.14), rgba(59,130,246,0) 60%),
    radial-gradient(900px 350px at 90% 110%, rgba(59,130,246,.10), rgba(59,130,246,0) 70%),
    linear-gradient(180deg, var(--blue-50), #fff 40%);
}

/* HERO */
.card-hero{ border:0; border-radius:var(--radius); overflow:hidden; box-shadow:0 12px 28px rgba(30,64,175,.18); }
.card-hero .hero-body{ background:linear-gradient(135deg,var(--blue-700),var(--blue-800)); color:#fff; padding:1.8rem 1.2rem; min-height:148px; }
.hero-title{ font-weight:900; letter-spacing:.2px; }
.text-white-70{ color:rgba(255,255,255,.92)!important; }

/* Card seragam */
.card.admin-card{ border:1px solid rgba(30,64,175,.12); border-radius:16px; background:#fff; box-shadow:0 10px 22px rgba(30,64,175,.08); }
.card-header{ background:#f8fafc; border-bottom:1px solid #e9eef7; }

/* KPI */
.kpi-card{ background:#fff; border:1px solid #e9eef7; border-radius:16px; transition:.2s; }
.kpi-card:hover{ transform:translateY(-2px); box-shadow:0 14px 30px rgba(0,0,0,.08); }
.kpi-card .card-body{ padding:18px 20px; }
.kpi-icon{
  width:54px; height:54px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:20px;
  box-shadow:0 8px 20px rgba(29,78,216,.10), inset 0 -2px 0 rgba(255,255,255,.7);
}
.kpi-blue{ background:#e8f0ff; color:#1b4fd6; }
.kpi-purple{ background:#efe9ff; color:#5b34cf; }
.kpi-green{ background:#e9fff5; color:#0f8a5b; }
.kpi-label{ color:#475569; font-weight:700; letter-spacing:.15px; }
.kpi-number{ font-size:28px; font-weight:900; line-height:1.1; color:#0f172a; }

/* List tugas */
.task-list{ display:flex; flex-direction:column; gap:12px; }
.task-item{ display:flex; align-items:flex-start; justify-content:space-between; gap:12px; padding:14px; background:#f8fafc; border:1px solid #e5e7eb; border-radius:12px; transition:.16s; }
.task-item:hover{ box-shadow:0 10px 20px rgba(0,0,0,.06); border-color:#dbe2ea; }
.task-left{ flex:1; min-width:0; }
.task-title{ display:flex; align-items:center; gap:8px; font-weight:700; color:#1f2937; }
.truncate-2{ overflow:hidden; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; }
.task-meta{ display:flex; flex-wrap:wrap; gap:10px; color:#6b7280; font-size:.9rem; margin-top:4px; }
.task-actions{ display:flex; gap:8px; }

/* CHIP jenis tugas */
.type-chip{
  display:inline-flex; align-items:center; gap:.35rem;
  font-size:.78rem; font-weight:800; letter-spacing:.1px;
  padding:.24rem .6rem; border-radius:999px; border:1px solid transparent;
  line-height:1; user-select:none;
}
.type-abs{ /* Abstrak: biru */
  background:#e0f2fe; color:#075985; border-color:#7dd3fc;
}
.type-fp{  /* Full Paper: ungu */
  background:#f5f3ff; color:#4c1d95; border-color:#c4b5fd;
}

/* Pills (dipakai warna dasar) */
.status-pill,
.pill-warn,
.pill-success,
.pill-danger,
.pill-info,
.pill-muted{}

/* warna dasar */
.pill-warn{ background:#fef3c7; color:#92400e; }
.pill-success{ background:#d1fae5; color:#065f46; }
.pill-danger{ background:#fee2e2; color:#991b1b; }
.pill-info{ background:#e0f2fe; color:#0c4a6e; }
.pill-muted{ background:#f3f4f6; color:#374151; }

/* Empty state */
.empty-state{ text-align:center; padding:2rem 1.2rem; color:#6b7280; }
.empty-state .empty-icon{ font-size:2.2rem; opacity:.55; margin-bottom:.4rem; }
.empty-state .empty-title{ font-size:1.05rem; font-weight:700; color:#374151; }
.empty-state .empty-subtitle{ font-size:.88rem; color:#94a3b8; }

/* Scrollbar */
.scroll-area{ overflow:auto; scrollbar-width:thin; scrollbar-color:#9db7ff #f3f4f6; }
.scroll-area::-webkit-scrollbar{ width:8px; }
.scroll-area::-webkit-scrollbar-thumb{ background:#9db7ff; border-radius:6px; }
.scroll-area::-webkit-scrollbar-track{ background:#f3f4f6; border-radius:6px; }

/* Buttons */
.btn{ font-weight:800; border-radius:10px; font-size:.95rem; padding:.48rem .9rem; }

/* NOTIFIKASI (Aktivitas) */
.activity-list{ display:flex; flex-direction:column; gap:8px; }
.notif-item{ list-style:none; }
.notif-line{
  display:flex;
  gap:10px;
  padding:10px 10px;
  border-radius:14px;
  background:#f9fafb;
  border:1px solid #e5e7eb;
  position:relative;
  overflow:hidden;
}
/* garis warna di kiri */
.notif-line::before{
  content:'';
  position:absolute;
  left:0; top:0; bottom:0;
  width:4px;
  background:#e5e7eb;
}
.notif-line.pill-warn::before{ background:#f59e0b; }
.notif-line.pill-danger::before{ background:#dc2626; }
.notif-line.pill-success::before{ background:#16a34a; }
.notif-line.pill-info::before{ background:#0ea5e9; }

.notif-icon-wrap{
  padding-top:2px;
}
.notif-icon{
  width:32px; height:32px;
  border-radius:999px;
  display:flex; align-items:center; justify-content:center;
  background:#eff6ff;
  color:#1d4ed8;
  font-size:15px;
}
.notif-line.pill-warn .notif-icon{ background:#fffbeb; color:#92400e; }
.notif-line.pill-danger .notif-icon{ background:#fef2f2; color:#b91c1c; }
.notif-line.pill-success .notif-icon{ background:#ecfdf5; color:#047857; }
.notif-line.pill-info .notif-icon{ background:#e0f2fe; color:#0c4a6e; }

.notif-title{
  font-size:.9rem;
  font-weight:700;
  color:#111827;
}
.notif-desc{
  font-size:.78rem;
  color:#6b7280;
  margin-top:2px;
}
.notif-chip{
  font-size:.7rem;
  font-weight:700;
  padding:.15rem .5rem;
  border-radius:99px;
  background:#e5e7eb;
  color:#374151;
  white-space:nowrap;
}
.notif-urgency{
  font-size:.7rem;
  font-weight:700;
  padding:.18rem .5rem;
  border-radius:999px;
  border:1px solid transparent;
  background:#f3f4f6;
  color:#4b5563;
}
.notif-urgency.pill-warn{
  background:#fef3c7;
  color:#92400e;
  border-color:#fde68a;
}
.notif-urgency.pill-danger{
  background:#fee2e2;
  color:#991b1b;
  border-color:#fecaca;
}
.notif-urgency.pill-success{
  background:#dcfce7;
  color:#166534;
  border-color:#bbf7d0;
}
.notif-urgency.pill-info{
  background:#e0f2fe;
  color:#0c4a6e;
  border-color:#bfdbfe;
}

/* Responsive */
@media (max-width: 768px){
  .card-hero .hero-body{ min-height:130px; }
  .task-item{ flex-direction:column; }
}

/* kecil */
.xsmall{ font-size:.8rem; }
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
  animateKPINumbers();
  wireDeclineModal();
});

function animateKPINumbers(){
  document.querySelectorAll('.kpi-number[data-count]').forEach(el=>{
    const target = parseInt(el.getAttribute('data-count') || '0', 10);
    let cur = 0;
    const step = Math.max(1, Math.floor(target/30));
    const tick = () => {
      cur += step;
      if (cur >= target) cur = target;
      el.textContent = new Intl.NumberFormat('id-ID').format(cur);
      if (cur < target) requestAnimationFrame(tick);
    };
    setTimeout(tick, 80);
  });
}

function wireDeclineModal(){
  const modal = new bootstrap.Modal(document.getElementById('declineModal'));
  document.querySelectorAll('.btn-decline').forEach(btn=>{
    btn.addEventListener('click', ()=>{
      document.getElementById('declType').value = btn.getAttribute('data-type');
      document.getElementById('declId').value   = btn.getAttribute('data-id');
      document.getElementById('declTitle').textContent = 'Menolak tugas: "' + (btn.getAttribute('data-title')||'') + '"';
      document.getElementById('declReason').value = '';
      modal.show();
    });
  });

  const form = document.getElementById('declineForm');
  form?.addEventListener('submit', (e)=>{
    const reason = (document.getElementById('declReason').value || '').trim();
    if (reason.length < 5){
      e.preventDefault();
      alert('Alasan penolakan minimal 5 karakter.');
    }
  });
}
</script>
