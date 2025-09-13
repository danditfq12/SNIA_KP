<?php
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
                    <small class="text-muted">Kelola tugas review abstrak Anda</small>
                </div>
                <div class="d-none d-md-flex gap-2">
                    <a href="<?= site_url('reviewer/abstrak') ?>" class="btn btn-primary">
                        <i class="bi bi-journal-text me-1"></i> Abstrak
                    </a>
                    <a href="<?= site_url('reviewer/riwayat') ?>" class="btn btn-outline-primary">
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
                                                </div>
                                                <span class="badge <?= $badgeClass ?> mt-2">
                                                    <?= esc(ucfirst($r['status'] ?? 'menunggu')) ?>
                                                </span>
                                            </div>
                                            <div class="task-action">
                                                <a href="<?= site_url('reviewer/abstrak/'.(int)($r['id_abstrak'] ?? 0)) ?>" 
                                                   class="btn btn-sm btn-primary">
                                                    <i class="bi bi-eye me-1"></i>Review
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
                                    <div class="empty-subtitle">Tugas baru akan tampil di sini</div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- AKTIVITAS TERBARU -->
                <div class="col-12 col-lg-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-bell me-2 text-info"></i>Aktivitas Terbaru
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($notifs)): ?>
                                <div class="notification-list">
                                    <?php foreach ($notifs as $n): ?>
                                        <div class="notification-item">
                                            <div class="notification-icon">
                                                <i class="bi bi-info-circle text-primary"></i>
                                            </div>
                                            <div class="notification-content">
                                                <div class="notification-title">
                                                    <?= esc($n['title'] ?? '-') ?>
                                                </div>
                                                <?php if (!empty($n['time'])): ?>
                                                    <div class="notification-time">
                                                        <?= esc($n['time']) ?>
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
                                    <div class="empty-subtitle">Update akan muncul di sini</div>
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
    padding: 12px 0;
    border-bottom: 1px solid #f1f5f9;
}

.notification-item:last-child {
    border-bottom: none;
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

.notification-time {
    font-size: 0.75rem;
    color: #6b7280;
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
}

/* Responsive improvements */
@media (max-width: 768px) {
    .header-section {
        padding: 16px;
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
    
    // Add loading states for buttons (optional)
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
            }, 2000);
        });
    });
});
</script>