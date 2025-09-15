<?php
// File: app/Views/role/reviewer/dashboard.php
$title = 'Reviewer Dashboard';
$s = $stat ?? ['assigned'=>0,'pending'=>0,'reviewed'=>0,'due_today'=>0];
$recent = $recent ?? [];
$notifs = $notifs ?? [];

helper('number');
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_reviewer') ?>

<div id="content">
    <main class="flex-fill" style="padding-top:70px;">
        <div class="container-fluid p-3 p-md-4">

            <!-- HEADER -->
            <div class="header-section d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="welcome-text mb-1">
                        Hai, <?= esc(session('nama_lengkap') ?? session('nama') ?? 'Reviewer') ?>
                    </h3>
                    <small class="text-white-50">Kelola tugas review abstrak Anda</small>
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

            <!-- KPI CARDS -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-xl-3">
                    <div class="kpi-card shadow-sm h-100">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="kpi-icon bg-primary-subtle text-primary">
                                <i class="bi bi-clipboard-check"></i>
                            </div>
                            <div>
                                <div class="kpi-label">Tugas Dialokasikan</div>
                                <div class="kpi-number" data-count="<?= (int)$s['assigned'] ?>">
                                    <?= number_format((int)$s['assigned']) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-xl-3">
                    <div class="kpi-card shadow-sm h-100">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="kpi-icon bg-warning-subtle text-warning">
                                <i class="bi bi-hourglass-split"></i>
                            </div>
                            <div>
                                <div class="kpi-label">Menunggu Keputusan</div>
                                <div class="kpi-number" data-count="<?= (int)$s['pending'] ?>">
                                    <?= number_format((int)$s['pending']) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-xl-3">
                    <div class="kpi-card shadow-sm h-100">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="kpi-icon bg-success-subtle text-success">
                                <i class="bi bi-check2-circle"></i>
                            </div>
                            <div>
                                <div class="kpi-label">Sudah Direview</div>
                                <div class="kpi-number" data-count="<?= (int)$s['reviewed'] ?>">
                                    <?= number_format((int)$s['reviewed']) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-6 col-xl-3">
                    <div class="kpi-card shadow-sm h-100">
                        <div class="card-body d-flex align-items-center gap-3">
                            <div class="kpi-icon bg-info-subtle text-info">
                                <i class="bi bi-calendar-event"></i>
                            </div>
                            <div>
                                <div class="kpi-label">Jatuh Tempo Hari Ini</div>
                                <div class="kpi-number" data-count="<?= (int)$s['due_today'] ?>">
                                    <?= number_format((int)$s['due_today']) ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- MAIN CONTENT ROW -->
            <div class="row g-3">
                <!-- TUGAS TERBARU -->
                <div class="col-12 col-lg-8">
                    <div class="card shadow-sm h-100">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-list-task me-2 text-primary"></i>Tugas Terbaru
                            </h5>
                            <?php if (!empty($recent)): ?>
                                <span class="badge bg-primary-subtle text-primary">
                                    <?= count($recent) ?> tugas
                                </span>
                            <?php endif; ?>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($recent)): ?>
                                <div class="task-list">
                                    <?php foreach ($recent as $r): ?>
                                        <?php
                                            $status = strtolower($r['status'] ?? '');
                                            $badgeClass = match($status) {
                                                'diterima','accepted' => 'bg-success',
                                                'ditolak','rejected'  => 'bg-danger',
                                                'revisi','revision'   => 'bg-warning text-dark',
                                                'pending','menunggu'  => 'bg-warning text-dark',
                                                default               => 'bg-secondary'
                                            };
                                        ?>
                                        <div class="task-item">
                                            <div class="task-content">
                                                <div class="task-title">
                                                    <?= esc($r['judul'] ?? 'Tanpa judul') ?>
                                                </div>
                                                <div class="task-meta">
                                                    <span><i class="bi bi-person me-1"></i><?= esc($r['nama_lengkap'] ?? '-') ?></span>
                                                    <span><i class="bi bi-tag me-1"></i><?= esc($r['nama_kategori'] ?? '-') ?></span>
                                                    <?php if (!empty($r['tanggal_upload'])): ?>
                                                        <span><i class="bi bi-calendar me-1"></i><?= date('d M', strtotime($r['tanggal_upload'])) ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <span class="badge <?= $badgeClass ?> mt-2">
                                                    <?= esc(ucfirst($r['status'] ?? 'menunggu')) ?>
                                                </span>
                                            </div>
                                            <div class="task-action">
                                                <a href="<?= site_url('reviewer/abstrak/'.(int)($r['id_abstrak'] ?? 0)) ?>"
                                                   class="btn btn-sm btn-primary">
                                                    <i class="bi bi-eye me-1"></i>
                                                    <?= $status === 'pending' || $status === 'menunggu' ? 'Review' : 'Lihat' ?>
                                                </a>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="empty-state">
                                    <div class="empty-icon">
                                        <i class="bi bi-inbox"></i>
                                    </div>
                                    <div class="empty-title">Belum ada tugas terbaru</div>
                                    <div class="empty-subtitle">Tugas review baru akan tampil di sini</div>
                                    <a href="<?= site_url('reviewer/abstrak') ?>" class="btn btn-outline-primary btn-sm mt-2">
                                        <i class="bi bi-search me-1"></i> Lihat Semua Tugas
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- AKTIVITAS TERBARU -->
                <div class="col-12 col-lg-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-bell me-2 text-info"></i>Aktivitas Terbaru
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
                                            <div class="notification-item <?= !empty($n['read']) ? 'read' : 'unread' ?>">
                                                <div class="notification-icon">
                                                    <?php
                                                    $iconClass = match($n['type'] ?? 'info') {
                                                        'success' => 'bi-check-circle text-success',
                                                        'warning','pending' => 'bi-exclamation-triangle text-warning',
                                                        'error' => 'bi-x-circle text-danger',
                                                        'welcome' => 'bi-hand-thumbs-up text-info',
                                                        default => 'bi-info-circle text-primary'
                                                    };
                                                    ?>
                                                    <i class="bi <?= $iconClass ?>"></i>
                                                </div>
                                                <div class="notification-content">
                                                    <div class="notification-title">
                                                        <?= esc($n['title'] ?? '-') ?>
                                                    </div>
                                                    <?php if (!empty($n['message'])): ?>
                                                        <div class="notification-message">
                                                            <?= esc($n['message']) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                    <?php if (!empty($n['time'])): ?>
                                                        <div class="notification-time">
                                                            <i class="bi bi-clock me-1"></i><?= esc($n['time']) ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if (!empty($n['link'])): ?>
                                                    <div class="notification-action">
                                                        <a href="<?= esc($n['link']) ?>" class="btn btn-sm btn-outline-primary">
                                                            Buka
                                                        </a>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <div class="empty-icon">
                                            <i class="bi bi-bell-slash"></i>
                                        </div>
                                        <div class="empty-title">Tidak ada aktivitas</div>
                                        <div class="empty-subtitle">Update dan notifikasi akan muncul di sini</div>
                                    </div>
                                <?php endif; ?>
                            </div>
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
  --primary-color:#2563eb;
  --info-color:#06b6d4;
  --success-color:#10b981;
  --warning-color:#f59e0b;
  --danger-color:#ef4444;
  --secondary-color:#475569;
  --ring:#eef2f7;
}

/* latar lembut */
body{
  background:#f9fafb;
  font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* HEADER biru */
.header-section{
  background:linear-gradient(135deg,var(--primary-color),#1e40af);
  color:#fff;
  padding:24px;
  border-radius:16px;
  box-shadow:0 8px 28px rgba(0,0,0,.12);
}
.welcome-text{ font-weight:700; font-size:1.6rem; }

/* CARD umum */
.card{
  border:0;
  border-radius:16px;
  box-shadow:0 8px 24px rgba(0,0,0,.06);
}
.card-header{
  background:#f8fafc;
  border-bottom:1px solid var(--ring);
  border-radius:16px 16px 0 0 !important;
  padding:16px 20px;
}
.card-title{ font-weight:600; color:#111827; }

/* =======================
   KPI – dibesarkan
======================= */
.kpi-card{
  background:#fff;
  border:1px solid var(--ring);
  border-left:6px solid #e5e7eb;          /* lebih tebal */
  border-radius:16px;
  transition:.2s ease;
}
.kpi-card:hover{ transform:translateY(-2px); box-shadow:0 14px 30px rgba(0,0,0,.08); }
.kpi-card .card-body{
  padding:20px 22px;                       /* padding lebih luas */
}
.kpi-icon{
  width:60px; height:60px;                 /* ikon lebih besar */
  border-radius:14px;
  display:flex; align-items:center; justify-content:center;
  font-size:1.6rem;                        /* ukuran ikon */
  flex-shrink:0;
  box-shadow: inset 0 0 0 1px rgba(0,0,0,.04);
}
.kpi-label{
  color:#6b7280;
  font-size:1rem;                          /* label lebih besar */
  font-weight:600;
  line-height:1.1;
}
.kpi-number{
  font-size:2.6rem;                        /* angka lebih besar */
  font-weight:800;
  color:#0f172a;
  line-height:1;
  letter-spacing:-.5px;
}

/* Skala responsif */
@media (min-width: 1200px){
  .kpi-icon{ width:64px; height:64px; font-size:1.75rem; }
  .kpi-number{ font-size:2.8rem; }
}
@media (max-width: 991.98px){
  .kpi-card .card-body{ padding:18px; }
  .kpi-icon{ width:56px; height:56px; font-size:1.45rem; }
  .kpi-number{ font-size:2.3rem; }
}
@media (max-width: 575.98px){
  .kpi-icon{ width:52px; height:52px; font-size:1.35rem; }
  .kpi-number{ font-size:2rem; }
}

/* LIST TUGAS */
.task-list{ display:flex; flex-direction:column; gap:12px; }
.task-item{
  display:flex; align-items:flex-start; justify-content:space-between;
  gap:12px; padding:14px;
  background:#f3f4f6;
  border:1px solid #e5e7eb; border-radius:12px;
  transition:.16s ease;
}
.task-item:hover{ box-shadow:0 10px 20px rgba(0,0,0,.06); border-color:#dbe2ea; }
.task-content{ flex:1; }
.task-title{ font-weight:600; color:#1f2937; margin-bottom:4px; }
.task-meta{ display:flex; flex-wrap:wrap; gap:10px; color:#6b7280; font-size:.9rem; }

/* NOTIFIKASI */
.notification-list{ display:flex; flex-direction:column; gap:10px; }
.notification-item{
  display:flex; gap:12px; padding:12px; border-radius:12px;
  border:1px solid #e5e7eb; background:#fff; transition:.16s ease;
}
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

/* Loading */
.loading{ opacity:.6; pointer-events:none; position:relative; }
.loading::after{
  content:''; position:absolute; top:50%; left:50%;
  width:20px; height:20px; margin:-10px 0 0 -10px;
  border:2px solid #e5e7eb; border-top:2px solid #3498db; border-radius:50%;
  animation:spin 1s linear infinite;
}
@keyframes spin{ to{ transform:rotate(360deg); } }

/* Animasi angka KPI */
@keyframes countUp{ from{ opacity:0; transform:translateY(10px);} to{ opacity:1; transform:translateY(0);} }
.kpi-number{ animation:countUp .6s ease-out; }

/* Responsif tambahan */
@media (max-width: 768px){
  .header-section{ padding:18px; text-align:center; }
  .welcome-text{ font-size:1.3rem; }
  .task-item{ flex-direction:column; }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    animateKPINumbers();
    addButtonLoadingStates();
    setInterval(refreshNotifications, 300000);
    setTimeout(checkForUpdates, 2000);
});

function animateKPINumbers() {
    const kpiNumbers = document.querySelectorAll('.kpi-number[data-count]');
    kpiNumbers.forEach(element => {
        const target = parseInt(element.getAttribute('data-count')) || 0;
        let current = 0;
        const step = Math.max(1, Math.floor(target / 30));
        const updateNumber = () => {
            current += step;
            if (current >= target) current = target;
            element.textContent = new Intl.NumberFormat('id-ID').format(current);
            if (current < target) requestAnimationFrame(updateNumber);
        };
        setTimeout(updateNumber, 100);
    });
}

function addButtonLoadingStates() {
    const reviewButtons = document.querySelectorAll('.task-item .btn');
    reviewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Loading...';
            this.disabled = true;
            setTimeout(() => {
                this.innerHTML = originalText;
                this.disabled = false;
            }, 3000);
        });
    });
}

async function refreshNotifications() {
    const refreshBtn = document.querySelector('button[onclick="refreshNotifications()"] i');
    if (refreshBtn) refreshBtn.classList.add('spinning');
    try {
        const res = await fetch('<?= site_url("reviewer/notifications") ?>', {
            method: 'GET',
            headers: {'X-Requested-With':'XMLHttpRequest','Content-Type':'application/json'}
        });
        if (res.ok) {
            const data = await res.json();
            if (data.success && data.notifications) {
                updateNotificationDisplay(data.notifications);
                showToast('Notifikasi berhasil dimuat ulang','success');
            } else {
                showToast('Gagal memuat notifikasi','error');
            }
        } else {
            generateMockNotifications();
        }
    } catch (e) {
        generateMockNotifications();
        showToast('Menggunakan data lokal','info');
    }
    if (refreshBtn) refreshBtn.classList.remove('spinning');
}

function updateNotificationDisplay(notifications) {
    const container = document.getElementById('notification-container');
    if (!notifications || notifications.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <div class="empty-icon"><i class="bi bi-bell-slash"></i></div>
                <div class="empty-title">Tidak ada aktivitas</div>
                <div class="empty-subtitle">Update dan notifikasi akan muncul di sini</div>
            </div>`;
        return;
    }
    const html = notifications.map(n => {
        const iconClass = getNotificationIcon(n.type);
        return `
        <div class="notification-item ${n.read ? 'read':'unread'}">
            <div class="notification-icon"><i class="bi ${iconClass}"></i></div>
            <div class="notification-content">
                <div class="notification-title">${escapeHtml(n.title)}</div>
                ${n.message ? `<div class="notification-message">${escapeHtml(n.message)}</div>`:''}
                ${n.time ? `<div class="notification-time"><i class="bi bi-clock me-1"></i>${escapeHtml(n.time)}</div>`:''}
            </div>
            ${n.link ? `<div class="notification-action"><a href="${escapeHtml(n.link)}" class="btn btn-sm btn-outline-primary">Buka</a></div>`:''}
        </div>`;
    }).join('');
    container.innerHTML = `<div class="notification-list">${html}</div>`;
}

function generateMockNotifications() {
    const pendingCount  = parseInt(document.querySelectorAll('.kpi-number')[1]?.getAttribute('data-count')||0);
    const reviewedCount = parseInt(document.querySelectorAll('.kpi-number')[2]?.getAttribute('data-count')||0);
    const m = [];
    if (pendingCount > 0) m.push({title:`${pendingCount} tugas review menunggu`,message:'Ada abstrak yang perlu direview segera',time:'Hari ini',link:'<?= site_url("reviewer/abstrak") ?>',type:'pending',read:false});
    if (reviewedCount > 0) m.push({title:`${reviewedCount} review telah selesai`,message:'Terima kasih atas kontribusi Anda',time:'Minggu ini',link:'<?= site_url("reviewer/riwayat") ?>',type:'success',read:false});
    m.push({title:'Selamat datang, Reviewer!',message:'Sistem review online siap digunakan',time:'Info',link:'',type:'welcome',read:false});
    updateNotificationDisplay(m);
}

function getNotificationIcon(type){
    const map = {
        success:'bi-check-circle text-success',
        warning:'bi-exclamation-triangle text-warning',
        pending:'bi-exclamation-triangle text-warning',
        error:'bi-x-circle text-danger',
        welcome:'bi-hand-thumbs-up text-info',
        info:'bi-info-circle text-primary'
    };
    return map[type] || map.info;
}

function checkForUpdates(){ refreshNotifications(); }

function showToast(message,type='info'){
    const toast = document.createElement('div');
    toast.className = `toast-notification toast-${type}`;
    toast.innerHTML = `
        <div class="toast-content">
            <i class="bi bi-${type==='success'?'check-circle':type==='error'?'x-circle':'info-circle'} me-2"></i>
            ${escapeHtml(message)}
        </div>`;
    toast.style.cssText = `
        position:fixed; top:20px; right:20px; background:${type==='success'?'#10b981':type==='error'?'#ef4444':'#06b6d4'};
        color:#fff; padding:12px 20px; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,.15); z-index:9999; animation:slideInRight .3s ease;`;
    document.body.appendChild(toast);
    setTimeout(()=>{ toast.style.animation='slideOutRight .3s ease'; setTimeout(()=>document.body.removeChild(toast),300); },3000);
}

function escapeHtml(t){ if(!t) return ''; const d=document.createElement('div'); d.textContent=t; return d.innerHTML; }

const dynStyle=document.createElement('style');
dynStyle.textContent=`
    .spinning{ animation:spin 1s linear infinite; }
    @keyframes slideInRight{ from{ transform:translateX(100%); opacity:0;} to{ transform:translateX(0); opacity:1;} }
    @keyframes slideOutRight{ from{ transform:translateX(0); opacity:1;} to{ transform:translateX(100%); opacity:0;} }
`;
document.head.appendChild(dynStyle);
</script>