<?php
// File: app/Views/role/reviewer/dashboard.php
// Default variables untuk hindari notice
$title = 'Reviewer Dashboard';
$s = $stat ?? [
    'assigned' => 0,
    'pending' => 0, 
    'reviewed' => 0,
    'due_today' => 0
];
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
                                                'diterima', 'accepted' => 'bg-success',
                                                'ditolak', 'rejected' => 'bg-danger',
                                                'revisi', 'revision' => 'bg-warning text-dark',
                                                'pending', 'menunggu' => 'bg-warning text-dark',
                                                default => 'bg-secondary'
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
                                                        'warning', 'pending' => 'bi-exclamation-triangle text-warning', 
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
:root {
    --primary-color: #2563eb;
    --success-color: #10b981;
    --warning-color: #f59e0b;
    --danger-color: #ef4444;
    --info-color: #06b6d4;
    --secondary-color: #6b7280;
}

body {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.header-section {
    background: linear-gradient(135deg, var(--primary-color) 0%, #1e40af 100%);
    color: #fff;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(37, 99, 235, 0.15);
}

.welcome-text {
    color: #fff;
    font-weight: 700;
    font-size: 1.75rem;
    margin: 0;
}

.kpi-card {
    background: #fff;
    border: none;
    border-radius: 12px;
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.kpi-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.kpi-icon {
    width: 44px;
    height: 44px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
}

.kpi-label {
    color: #6b7280;
    font-size: 0.875rem;
    font-weight: 500;
}

.kpi-number {
    font-size: 1.875rem;
    font-weight: 700;
    color: #1f2937;
    line-height: 1;
}

.card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.card-header {
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    border-radius: 12px 12px 0 0 !important;
    padding: 16px 20px;
}

.card-title {
    color: #1f2937;
    font-weight: 600;
}

.task-list {
    display: flex;
    flex-direction: column;
    gap: 16px;
}

.task-item {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    padding: 16px;
    border: 1px solid #e2e8f0;
    border-radius: 8px;
    background: #fff;
    transition: all 0.2s ease;
}

.task-item:hover {
    border-color: var(--primary-color);
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.1);
}

.task-content {
    flex: 1;
    margin-right: 16px;
}

.task-title {
    font-weight: 600;
    color: #1f2937;
    margin-bottom: 4px;
}

.task-meta {
    display: flex;
    flex-wrap: wrap;
    gap: 12px;
    font-size: 0.875rem;
    color: #6b7280;
    margin-bottom: 8px;
}

.task-action {
    flex-shrink: 0;
}

.notification-list {
    display: flex;
    flex-direction: column;
    gap: 12px;
}

.notification-item {
    display: flex;
    align-items: flex-start;
    gap: 12px;
    padding: 12px;
    border-radius: 8px;
    transition: all 0.2s ease;
    border: 1px solid transparent;
}

.notification-item.unread {
    background: #f0f9ff;
    border-color: #0ea5e9;
}

.notification-item.read {
    background: #f8fafc;
    opacity: 0.8;
}

.notification-item:hover {
    transform: translateX(2px);
}

.notification-icon {
    flex-shrink: 0;
    margin-top: 2px;
}

.notification-content {
    flex: 1;
}

.notification-title {
    font-weight: 600;
    color: #1f2937;
    font-size: 0.875rem;
    margin-bottom: 2px;
}

.notification-message {
    font-size: 0.8rem;
    color: #6b7280;
    margin-bottom: 4px;
    line-height: 1.4;
}

.notification-time {
    font-size: 0.75rem;
    color: #9ca3af;
}

.notification-action {
    flex-shrink: 0;
}

.empty-state {
    text-align: center;
    padding: 3rem 2rem;
    color: #6b7280;
}

.empty-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.empty-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #4b5563;
    margin-bottom: 0.5rem;
}

.empty-subtitle {
    font-size: 0.875rem;
    color: #9ca3af;
    margin-bottom: 1rem;
}

/* Loading animation */
.loading {
    opacity: 0.6;
    pointer-events: none;
}

.loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 20px;
    height: 20px;
    margin: -10px 0 0 -10px;
    border: 2px solid #f3f3f3;
    border-top: 2px solid #3498db;
    border-radius: 50%;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Responsive improvements */
@media (max-width: 768px) {
    .header-section {
        padding: 16px;
        text-align: center;
    }
    
    .welcome-text {
        font-size: 1.5rem;
    }
    
    .task-item {
        flex-direction: column;
        gap: 12px;
    }
    
    .task-action {
        align-self: flex-end;
    }
    
    .kpi-number {
        font-size: 1.5rem;
    }
}

/* Animation for numbers */
@keyframes countUp {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}

.kpi-number {
    animation: countUp 0.6s ease-out;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Animate KPI numbers
    animateKPINumbers();
    
    // Add loading states for buttons
    addButtonLoadingStates();
    
    // Auto-refresh notifications every 5 minutes
    setInterval(refreshNotifications, 300000);
    
    // Check for updates on page load
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
            if (current >= target) {
                current = target;
            }
            
            element.textContent = new Intl.NumberFormat('id-ID').format(current);
            
            if (current < target) {
                requestAnimationFrame(updateNumber);
            }
        };
        
        // Start animation with a slight delay
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
            
            // Re-enable after navigation (fallback)
            setTimeout(() => {
                this.innerHTML = originalText;
                this.disabled = false;
            }, 3000);
        });
    });
}

async function refreshNotifications() {
    const container = document.getElementById('notification-container');
    const refreshBtn = document.querySelector('button[onclick="refreshNotifications()"] i');
    
    if (refreshBtn) {
        refreshBtn.classList.add('spinning');
    }
    
    try {
        const response = await fetch('<?= site_url("reviewer/notifications") ?>', {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Content-Type': 'application/json'
            }
        });
        
        if (response.ok) {
            const data = await response.json();
            
            if (data.success && data.notifications) {
                updateNotificationDisplay(data.notifications);
                showToast('Notifikasi berhasil dimuat ulang', 'success');
            } else {
                showToast('Gagal memuat notifikasi', 'error');
            }
        } else {
            // Fallback: generate some mock notifications
            generateMockNotifications();
        }
    } catch (error) {
        console.log('Using fallback notifications due to:', error);
        generateMockNotifications();
        showToast('Menggunakan data lokal', 'info');
    }
    
    if (refreshBtn) {
        refreshBtn.classList.remove('spinning');
    }
}

function updateNotificationDisplay(notifications) {
    const container = document.getElementById('notification-container');
    
    if (!notifications || notifications.length === 0) {
        container.innerHTML = `
            <div class="empty-state">
                <div class="empty-icon">
                    <i class="bi bi-bell-slash"></i>
                </div>
                <div class="empty-title">Tidak ada aktivitas</div>
                <div class="empty-subtitle">Update dan notifikasi akan muncul di sini</div>
            </div>
        `;
        return;
    }
    
    const notificationsHtml = notifications.map(notif => {
        const iconClass = getNotificationIcon(notif.type);
        return `
            <div class="notification-item ${notif.read ? 'read' : 'unread'}">
                <div class="notification-icon">
                    <i class="bi ${iconClass}"></i>
                </div>
                <div class="notification-content">
                    <div class="notification-title">
                        ${escapeHtml(notif.title)}
                    </div>
                    ${notif.message ? `<div class="notification-message">${escapeHtml(notif.message)}</div>` : ''}
                    ${notif.time ? `<div class="notification-time"><i class="bi bi-clock me-1"></i>${escapeHtml(notif.time)}</div>` : ''}
                </div>
                ${notif.link ? `<div class="notification-action"><a href="${escapeHtml(notif.link)}" class="btn btn-sm btn-outline-primary">Buka</a></div>` : ''}
            </div>
        `;
    }).join('');
    
    container.innerHTML = `<div class="notification-list">${notificationsHtml}</div>`;
}

function generateMockNotifications() {
    const pendingCount = parseInt(document.querySelector('.kpi-number[data-count]')?.getAttribute('data-count') || 0);
    const reviewedCount = parseInt(document.querySelectorAll('.kpi-number')[2]?.getAttribute('data-count') || 0);
    
    const mockNotifications = [];
    
    if (pendingCount > 0) {
        mockNotifications.push({
            title: `${pendingCount} tugas review menunggu`,
            message: 'Ada abstrak yang perlu direview segera',
            time: 'Hari ini',
            link: '<?= site_url("reviewer/abstrak") ?>',
            type: 'pending',
            read: false
        });
    }
    
    if (reviewedCount > 0) {
        mockNotifications.push({
            title: `${reviewedCount} review telah selesai`,
            message: 'Terima kasih atas kontribusi Anda',
            time: 'Minggu ini',
            link: '<?= site_url("reviewer/riwayat") ?>',
            type: 'success',
            read: false
        });
    }
    
    mockNotifications.push({
        title: 'Selamat datang, Reviewer!',
        message: 'Sistem review online siap digunakan',
        time: 'Info',
        link: '',
        type: 'welcome',
        read: false
    });
    
    updateNotificationDisplay(mockNotifications);
}

function getNotificationIcon(type) {
    const iconMap = {
        'success': 'bi-check-circle text-success',
        'warning': 'bi-exclamation-triangle text-warning',
        'pending': 'bi-exclamation-triangle text-warning',
        'error': 'bi-x-circle text-danger',
        'welcome': 'bi-hand-thumbs-up text-info',
        'info': 'bi-info-circle text-primary'
    };
    
    return iconMap[type] || iconMap['info'];
}

function checkForUpdates() {
    // Auto-check for updates without user interaction
    refreshNotifications();
}

function showToast(message, type = 'info') {
    // Create toast notification
    const toast = document.createElement('div');
    toast.className = `toast-notification toast-${type}`;
    toast.innerHTML = `
        <div class="toast-content">
            <i class="bi bi-${type === 'success' ? 'check-circle' : type === 'error' ? 'x-circle' : 'info-circle'} me-2"></i>
            ${escapeHtml(message)}
        </div>
    `;
    
    // Style the toast
    toast.style.cssText = `
        position: fixed;
        top: 20px;
        right: 20px;
        background: ${type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#06b6d4'};
        color: white;
        padding: 12px 20px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        z-index: 9999;
        animation: slideInRight 0.3s ease;
    `;
    
    document.body.appendChild(toast);
    
    // Remove after 3 seconds
    setTimeout(() => {
        toast.style.animation = 'slideOutRight 0.3s ease';
        setTimeout(() => document.body.removeChild(toast), 300);
    }, 3000);
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Add CSS animations
const style = document.createElement('style');
style.textContent = `
    .spinning {
        animation: spin 1s linear infinite;
    }
    
    @keyframes slideInRight {
        from { transform: translateX(100%); opacity: 0; }
        to { transform: translateX(0); opacity: 1; }
    }
    
    @keyframes slideOutRight {
        from { transform: translateX(0); opacity: 1; }
        to { transform: translateX(100%); opacity: 0; }
    }
`;
document.head.appendChild(style);
</script>