<?php
// File: app/Views/role/reviewer/dashboard.php

$title    = 'Reviewer Dashboard';
$cards    = $cards    ?? ['total'=>0,'done'=>0];
$incoming = $incoming ?? [];   // gabungan abstrak + fullpaper yang perlu konfirmasi
$notifs   = $notifs   ?? [];
$hasAssign= (bool)($hasAssign ?? false);

$fmtDate = fn($d,$withTime=false) => $d ? date($withTime?'d M Y H:i':'d M Y', strtotime($d)) : '-';
?>
<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_reviewer') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
  <main class="flex-fill" style="padding-top:70px;">
    <div class="container-fluid p-3 p-md-4">

      <!-- HEADER -->
      <div class="header-section d-flex justify-content-between align-items-center mb-4">
        <div>
          <h3 class="welcome-text mb-1">
            Hai, <?= esc(session('nama_lengkap') ?? session('nama') ?? 'Reviewer') ?>
          </h3>
          <small class="text-white-50">Kelola tugas review abstrak & full paper Anda</small>
        </div>
        <div class="d-none d-md-flex gap-2">
          <a href="<?= site_url('reviewer/abstrak') ?>" class="btn btn-light">
            <i class="bi bi-journal-text me-1"></i> Abstrak
          </a>
          <a href="<?= site_url('reviewer/riwayat') ?>" class="btn btn-outline-light">
            <i class="bi bi-clock-history me-1"></i> Riwayat
          </a>
        </div>
      </div>

      <!-- KPI: Total Tugas & Total Diselesaikan -->
      <div class="row g-3 mb-4">
        <div class="col-12 col-md-6">
          <div class="kpi-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
              <div class="kpi-icon bg-primary-subtle text-primary">
                <i class="bi bi-list-task"></i>
              </div>
              <div>
                <div class="kpi-label">Total Tugas</div>
                <div class="kpi-number" data-count="<?= (int)$cards['total'] ?>">
                  <?= number_format((int)$cards['total']) ?>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-12 col-md-6">
          <div class="kpi-card shadow-sm h-100">
            <div class="card-body d-flex align-items-center gap-3">
              <div class="kpi-icon bg-success-subtle text-success">
                <i class="bi bi-check2-circle"></i>
              </div>
              <div>
                <div class="kpi-label">Total Diselesaikan</div>
                <div class="kpi-number" data-count="<?= (int)$cards['done'] ?>">
                  <?= number_format((int)$cards['done']) ?>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- MAIN CONTENT ROW -->
      <div class="row g-3">
        <!-- TUGAS MASUK (PERLU KONFIRMASI) -->
        <div class="col-12 col-lg-8">
          <div class="card shadow-sm h-100" id="incoming">
            <div class="card-header d-flex align-items-center justify-content-between">
              <h5 class="card-title mb-0">
                <i class="bi bi-inbox me-2 text-primary"></i>Tugas Masuk (Perlu Konfirmasi)
              </h5>
              <span class="badge bg-primary-subtle text-primary"><?= count($incoming) ?> item</span>
            </div>
            <div class="card-body">
              <?php if (!$hasAssign): ?>
                <div class="empty-state">
                  <div class="empty-icon"><i class="bi bi-info-circle"></i></div>
                  <div class="empty-title">Fitur konfirmasi tugas belum aktif</div>
                  <div class="empty-subtitle">Tambahkan kolom status tugas pada tabel review (assignment)</div>
                </div>
              <?php elseif (empty($incoming)): ?>
                <div class="empty-state">
                  <div class="empty-icon"><i class="bi bi-check-circle"></i></div>
                  <div class="empty-title">Tidak ada tugas menunggu konfirmasi</div>
                  <div class="empty-subtitle">Tugas baru yang perlu konfirmasi akan muncul di sini</div>
                </div>
              <?php else: ?>
                <div class="task-list">
                  <?php foreach ($incoming as $t): ?>
                    <?php
                      $taskType = strtolower($t['task_type'] ?? 'abstrak'); // 'abstrak' | 'fullpaper'
                      $title    = $t['judul'] ?? '—';
                      $event    = $t['event_title'] ?? '—';
                      $uploadAt = $fmtDate($t['tanggal_upload'] ?? null, true);
                      $id = $taskType === 'abstrak'
                        ? (int)($t['id_abstrak'] ?? 0)
                        : (int)($t['id_submission'] ?? $t['submission_id'] ?? 0);
                    ?>
                    <div class="task-item">
                      <div class="task-content">
                        <div class="task-title d-flex align-items-center gap-2">
                          <?= esc($title) ?>
                          <span class="badge bg-info-subtle text-info">
                            <i class="bi bi-file-text me-1"></i><?= $taskType==='abstrak'?'Abstrak':'Full Paper' ?>
                          </span>
                        </div>
                        <div class="task-meta">
                          <span><i class="bi bi-calendar-event me-1"></i><?= esc($event) ?></span>
                          <span><i class="bi bi-upload me-1"></i><?= esc($uploadAt) ?></span>
                        </div>
                      </div>
                      <div class="task-action d-flex gap-2">
                        <!-- Accept -->
                        <form class="m-0" method="post" action="<?= site_url('reviewer/dashboard/confirm') ?>">
                          <?= csrf_field() ?>
                          <input type="hidden" name="type"   value="<?= esc($taskType) ?>">
                          <input type="hidden" name="id"     value="<?= (int)$id ?>">
                          <input type="hidden" name="action" value="accept">
                          <button class="btn btn-sm btn-success">
                            <i class="bi bi-check-lg me-1"></i>Terima
                          </button>
                        </form>

                        <!-- Decline (modal reason wajib) -->
                        <button class="btn btn-sm btn-outline-danger btn-decline"
                                data-type="<?= esc($taskType) ?>"
                                data-id="<?= (int)$id ?>"
                                data-title="<?= esc($title,'attr') ?>">
                          <i class="bi bi-x-lg me-1"></i>Tolak
                        </button>
                      </div>
                    </div>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
            </div>
            <div class="card-footer">
              <div class="small text-muted">
                <i class="bi bi-exclamation-triangle me-1 text-danger"></i>
                <strong>Catatan:</strong> Penolakan <u>wajib</u> mencantumkan alasan yang jelas.
              </div>
            </div>
          </div>
        </div>

        <!-- PANEL KANAN: HANYA TUGAS BARU MASUK -->
        <div class="col-12 col-lg-4">
          <div class="card shadow-sm h-100">
            <div class="card-header d-flex align-items-center justify-content-between">
              <h5 class="card-title mb-0">
                <i class="bi bi-bell me-2 text-info"></i>Tugas Baru Masuk
              </h5>
              <button class="btn btn-sm btn-outline-info" onclick="refreshNotifications()">
                <i class="bi bi-arrow-clockwise"></i>
              </button>
            </div>
            <div class="card-body">
              <div id="notification-container">
                <?php if (!empty($notifs)): ?>
                  <div class="notification-list">
                    <?php foreach ($notifs as $n): ?>
                      <?php $iconClass = 'bi-exclamation-triangle text-warning'; ?>
                      <div class="notification-item <?= !empty($n['read']) ? 'read' : 'unread' ?>">
                        <div class="notification-icon"><i class="bi <?= $iconClass ?>"></i></div>
                        <div class="notification-content">
                          <div class="notification-title"><?= esc($n['title'] ?? '-') ?></div>
                          <?php if (!empty($n['message'])): ?>
                            <div class="notification-message"><?= esc($n['message']) ?></div>
                          <?php endif; ?>
                          <?php if (!empty($n['time'])): ?>
                            <div class="notification-time"><i class="bi bi-clock me-1"></i><?= esc($n['time']) ?></div>
                          <?php endif; ?>
                        </div>
                        <?php if (!empty($n['link'])): ?>
                          <div class="notification-action">
                            <a class="btn btn-sm btn-outline-primary" href="<?= esc($n['link']) ?>">Buka</a>
                          </div>
                        <?php endif; ?>
                      </div>
                    <?php endforeach; ?>
                  </div>
                <?php else: ?>
                  <div class="empty-state">
                    <div class="empty-icon"><i class="bi bi-bell-slash"></i></div>
                    <div class="empty-title">Tidak ada tugas baru</div>
                    <div class="empty-subtitle">Jika ada penugasan baru, notifikasi akan muncul di sini</div>
                  </div>
                <?php endif; ?>
              </div>
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
            <h6 class="modal-title">
              <i class="bi bi-x-octagon text-danger me-2"></i>Tolak Tugas
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
:root{
  --primary-color:#2563eb;
  --ring:#eef2f7;
}
body{ background:#f9fafb; font-family:'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; }

/* HEADER */
.header-section{
  background:linear-gradient(135deg,var(--primary-color),#1e40af);
  color:#fff; padding:24px; border-radius:16px;
  box-shadow:0 8px 28px rgba(0,0,0,.12);
}
.welcome-text{ font-weight:700; font-size:1.6rem; }

/* CARD umum */
.card{ border:0; border-radius:16px; box-shadow:0 8px 24px rgba(0,0,0,.06); }
.card-header{ background:#f8fafc; border-bottom:1px solid var(--ring); border-radius:16px 16px 0 0!important; padding:16px 20px; }
.card-title{ font-weight:600; color:#111827; }

/* KPI */
.kpi-card{ background:#fff; border:1px solid var(--ring); border-left:6px solid #e5e7eb; border-radius:16px; transition:.2s; }
.kpi-card:hover{ transform:translateY(-2px); box-shadow:0 14px 30px rgba(0,0,0,.08); }
.kpi-card .card-body{ padding:20px 22px; }
.kpi-icon{ width:60px; height:60px; border-radius:14px; display:flex; align-items:center; justify-content:center; font-size:1.6rem; flex-shrink:0; box-shadow:inset 0 0 0 1px rgba(0,0,0,.04); }
.kpi-label{ color:#6b7280; font-size:1rem; font-weight:600; line-height:1.1; }
.kpi-number{ font-size:2.6rem; font-weight:800; color:#0f172a; line-height:1; letter-spacing:-.5px; }

/* TUGAS LIST */
.task-list{ display:flex; flex-direction:column; gap:12px; }
.task-item{ display:flex; align-items:flex-start; justify-content:space-between; gap:12px; padding:14px; background:#f3f4f6; border:1px solid #e5e7eb; border-radius:12px; transition:.16s; }
.task-item:hover{ box-shadow:0 10px 20px rgba(0,0,0,.06); border-color:#dbe2ea; }
.task-content{ flex:1; }
.task-title{ font-weight:600; color:#1f2937; }
.task-meta{ display:flex; flex-wrap:wrap; gap:10px; color:#6b7280; font-size:.9rem; }

/* NOTIFIKASI */
.notification-list{ display:flex; flex-direction:column; gap:10px; }
.notification-item{ display:flex; gap:12px; padding:12px; border-radius:12px; border:1px solid #e5e7eb; background:#fff; transition:.16s; }
.notification-item.unread{ background:#f0f9ff; border-color:#bae6fd; }
.notification-item.read{ opacity:.9; }
.notification-item:hover{ transform:translateX(2px); box-shadow:0 8px 18px rgba(0,0,0,.06); }
.notification-icon{ flex-shrink:0; font-size:1rem; margin-top:2px; }
.notification-title{ font-weight:600; color:#111827; font-size:.95rem; }
.notification-message{ color:#6b7280; font-size:.85rem; line-height:1.4; margin-top:2px; }
.notification-time{ color:#94a3b8; font-size:.78rem; margin-top:4px; }

/* EMPTY STATE */
.empty-state{ text-align:center; padding:2.4rem 1.6rem; color:#6b7280; }
.empty-icon{ font-size:2.6rem; opacity:.5; margin-bottom:.6rem; }
.empty-title{ font-size:1.05rem; font-weight:600; color:#374151; }
.empty-subtitle{ font-size:.88rem; color:#9ca3af; }

/* Responsive */
@media (max-width: 768px){
  .header-section{ padding:18px; text-align:center; }
  .welcome-text{ font-size:1.3rem; }
  .task-item{ flex-direction:column; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', () => {
  animateKPINumbers();
  wireDeclineModal();
  setInterval(refreshNotifications, 300000); // 5 menit
  refreshNotifications();
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

async function refreshNotifications(){
  const url = '<?= site_url("reviewer/notifications") ?>';
  try {
    const res = await fetch(url, {headers:{'X-Requested-With':'XMLHttpRequest'}});
    if (!res.ok) throw new Error('HTTP '+res.status);
    const data = await res.json();
    if (data && data.success && Array.isArray(data.notifications)){
      updateNotificationDisplay(data.notifications);
    } else {
      updateNotificationDisplay([]);
    }
  } catch(e){
    updateNotificationDisplay([]);
  }
}

function updateNotificationDisplay(items){
  const wrap = document.getElementById('notification-container');
  if (!items || items.length===0){
    wrap.innerHTML = `
      <div class="empty-state">
        <div class="empty-icon"><i class="bi bi-bell-slash"></i></div>
        <div class="empty-title">Tidak ada tugas baru</div>
        <div class="empty-subtitle">Jika ada penugasan baru, notifikasi akan muncul di sini</div>
      </div>`;
    return;
  }
  const esc = (t)=>{const d=document.createElement('div'); d.textContent=t??''; return d.innerHTML;};
  const html = items.map(n=>`
    <div class="notification-item ${n.read?'read':'unread'}">
      <div class="notification-icon"><i class="bi bi-exclamation-triangle text-warning"></i></div>
      <div class="notification-content">
        <div class="notification-title">${esc(n.title)}</div>
        ${n.message?`<div class="notification-message">${esc(n.message)}</div>`:''}
        ${n.time?`<div class="notification-time"><i class="bi bi-clock me-1"></i>${esc(n.time)}</div>`:''}
      </div>
      ${n.link?`<div class="notification-action"><a class="btn btn-sm btn-outline-primary" href="${esc(n.link)}">Buka</a></div>`:''}
    </div>
  `).join('');
  wrap.innerHTML = `<div class="notification-list">${html}</div>`;
}
</script>
