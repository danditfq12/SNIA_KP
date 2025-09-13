<?php
// Helper function untuk waktu relatif
if (!function_exists('timeAgo')) {
    function timeAgo($datetime) {
        $time = time() - strtotime($datetime);
        if ($time < 60) return 'Baru saja';
        if ($time < 3600) return floor($time/60) . ' menit yang lalu';
        if ($time < 86400) return floor($time/3600) . ' jam yang lalu';
        if ($time < 2592000) return floor($time/86400) . ' hari yang lalu';
        return date('d M Y', strtotime($datetime));
    }
}

// Default variables untuk hindari notice
$title = $title ?? 'Laporan & Analitik';
$total_users = (int)($total_users ?? 0);
$total_abstrak = (int)($total_abstrak ?? 0);
$total_pembayaran = (int)($total_pembayaran ?? 0);
$total_revenue = (int)($total_revenue ?? 0);

$abstrak_by_status = $abstrak_by_status ?? [];
$user_by_role = $user_by_role ?? [];
$monthly_stats = $monthly_stats ?? [];
$recent_registrations = $recent_registrations ?? [];
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_admin') ?>
<?= $this->include('partials/alerts') ?>

<div id="content">
    <main class="flex-fill" style="padding-top:70px;">
        <div class="container-fluid p-3 p-md-4">

            <!-- HEADER -->
            <div class="header-section header-blue d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="welcome-text mb-1">
                        <i class="fas fa-chart-line me-2"></i>Laporan & Analitik
                    </h3>
                    <div class="text-white-50">Pantau kinerja & statistik SNIA secara komprehensif</div>
                </div>
                <div class="text-end d-none d-md-block">
                    <small class="text-white-50 d-block">Terakhir update</small>
                    <strong class="text-white"><?= date('d M Y, H:i') ?></strong>
                </div>
            </div>

            <!-- KPI CARDS -->
            <div class="row g-3 mb-4">
                <div class="col-6 col-xl-3">
                    <div class="stat-card shadow-sm h-100">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="stat-number" data-count="<?= $total_users ?>">0</div>
                                <div class="text-muted">Total Users</div>
                            </div>
                            <i class="fas fa-users fa-lg text-primary"></i>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card shadow-sm h-100">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="stat-number" data-count="<?= $total_abstrak ?>">0</div>
                                <div class="text-muted">Total Abstrak</div>
                            </div>
                            <i class="fas fa-file-alt fa-lg text-success"></i>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card shadow-sm h-100">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="stat-number" data-count="<?= $total_pembayaran ?>">0</div>
                                <div class="text-muted">Total Pembayaran</div>
                            </div>
                            <i class="fas fa-credit-card fa-lg text-warning"></i>
                        </div>
                    </div>
                </div>
                <div class="col-6 col-xl-3">
                    <div class="stat-card shadow-sm h-100">
                        <div class="d-flex align-items-center justify-content-between">
                            <div>
                                <div class="stat-number revenue" data-count="<?= $total_revenue ?>">Rp 0</div>
                                <div class="text-muted">Total Revenue</div>
                            </div>
                            <i class="fas fa-money-bill-wave fa-lg text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CHARTS ROW 1 -->
            <div class="row g-3 mb-4">
                <div class="col-lg-6">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-user-plus me-2 text-primary"></i>Pendaftaran Bulanan
                            </h5>
                            <button class="btn btn-sm btn-outline-primary" onclick="refreshChart('users')">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                        <div class="card-body" style="height:320px;">
                            <canvas id="monthlyUsersChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white d-flex justify-content-between align-items-center">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-chart-area me-2 text-success"></i>Revenue Bulanan
                            </h5>
                            <button class="btn btn-sm btn-outline-success" onclick="refreshChart('revenue')">
                                <i class="fas fa-sync-alt"></i>
                            </button>
                        </div>
                        <div class="card-body" style="height:320px;">
                            <canvas id="monthlyRevenueChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- CHARTS ROW 2 -->
            <div class="row g-3 mb-4">
                <div class="col-lg-6">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-chart-pie me-2 text-info"></i>Status Abstrak
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-2 mb-3">
                                <div class="col-6 col-md-3">
                                    <div class="summary-info warning">
                                        <small class="text-muted">Menunggu</small>
                                        <div class="fw-bold text-warning"><?= $abstrak_by_status['menunggu'] ?? 0 ?></div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="summary-info info">
                                        <small class="text-muted">Sedang Review</small>
                                        <div class="fw-bold text-info"><?= $abstrak_by_status['sedang_direview'] ?? 0 ?></div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="summary-info success">
                                        <small class="text-muted">Diterima</small>
                                        <div class="fw-bold text-success"><?= $abstrak_by_status['diterima'] ?? 0 ?></div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="summary-info danger">
                                        <small class="text-muted">Ditolak</small>
                                        <div class="fw-bold text-danger"><?= $abstrak_by_status['ditolak'] ?? 0 ?></div>
                                    </div>
                                </div>
                            </div>
                            <div style="height:300px;">
                                <canvas id="abstrakStatusChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-users-cog me-2 text-secondary"></i>Distribusi Role User
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-2 mb-3">
                                <div class="col-6 col-md-3">
                                    <div class="summary-info danger">
                                        <small class="text-muted">Admin</small>
                                        <div class="fw-bold text-danger"><?= $user_by_role['admin'] ?? 0 ?></div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="summary-info primary">
                                        <small class="text-muted">Presenter</small>
                                        <div class="fw-bold text-primary"><?= $user_by_role['presenter'] ?? 0 ?></div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="summary-info secondary">
                                        <small class="text-muted">Audience</small>
                                        <div class="fw-bold text-secondary"><?= $user_by_role['audience'] ?? 0 ?></div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="summary-info success">
                                        <small class="text-muted">Reviewer</small>
                                        <div class="fw-bold text-success"><?= $user_by_role['reviewer'] ?? 0 ?></div>
                                    </div>
                                </div>
                            </div>
                            <div style="height:300px;">
                                <canvas id="userRolesChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- AKTIVITAS & TREN -->
            <div class="row g-3 mb-4">
                <div class="col-lg-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-clock me-2 text-primary"></i>Aktivitas Terbaru
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="activities-scroll">
                                <?php if (!empty($recent_registrations)): ?>
                                    <?php foreach (array_slice($recent_registrations, 0, 5) as $user): ?>
                                        <div class="activity-item">
                                            <div class="activity-icon">
                                                <i class="fas fa-user-plus"></i>
                                            </div>
                                            <div class="activity-content">
                                                <div class="activity-title"><?= esc($user['nama_lengkap']) ?></div>
                                                <div class="activity-meta">
                                                    Mendaftar sebagai <?= ucfirst($user['role']) ?> · <?= timeAgo($user['created_at']) ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="empty-state">
                                        <i class="fas fa-inbox fa-2x mb-2"></i>
                                        <div>Belum ada aktivitas</div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-8">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-chart-line me-2 text-info"></i>Tren 6 Bulan Terakhir
                            </h5>
                        </div>
                        <div class="card-body" style="height:320px;">
                            <canvas id="monthlyTrendsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- EXPORT SECTION -->
            <div class="card shadow-sm">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-download me-2 text-success"></i>Export Laporan
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <button class="export-card w-100" onclick="exportReport('users')">
                                <i class="fas fa-users text-primary"></i>
                                <h6>Data Users</h6>
                                <small>Export semua data users</small>
                            </button>
                        </div>
                        <div class="col-md-3">
                            <button class="export-card w-100" onclick="exportReport('abstrak')">
                                <i class="fas fa-file-alt text-success"></i>
                                <h6>Data Abstrak</h6>
                                <small>Export data abstrak & review</small>
                            </button>
                        </div>
                        <div class="col-md-3">
                            <button class="export-card w-100" onclick="exportReport('pembayaran')">
                                <i class="fas fa-credit-card text-warning"></i>
                                <h6>Data Pembayaran</h6>
                                <small>Export transaksi pembayaran</small>
                            </button>
                        </div>
                        <div class="col-md-3">
                            <button class="export-card w-100" onclick="exportReport('comprehensive')">
                                <i class="fas fa-chart-line text-info"></i>
                                <h6>Laporan Komprehensif</h6>
                                <small>Export laporan lengkap</small>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<?= $this->include('partials/footer') ?>

<!-- External Libraries -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

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

.header-section.header-blue {
    background: linear-gradient(135deg, var(--primary-color) 0%, #1e40af 100%);
    color: #fff;
    padding: 28px 24px;
    border-radius: 16px;
    box-shadow: 0 8px 28px rgba(0, 0, 0, .12);
}

.welcome-text {
    color: #fff;
    font-weight: 800;
    font-size: 2rem;
}

.stat-card {
    background: #fff;
    border-radius: 14px;
    padding: 24px;
    box-shadow: 0 8px 28px rgba(0, 0, 0, .08);
    border-left: 4px solid var(--primary-color);
    transition: transform 0.2s ease;
}

.stat-card:hover {
    transform: translateY(-2px);
}

.stat-number {
    font-size: 2rem;
    font-weight: 800;
    color: #1e293b;
    line-height: 1;
}

.summary-info {
    background: #f8fafc;
    border-radius: 8px;
    padding: 12px;
    border-left: 4px solid #e2e8f0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}

.summary-info.warning { border-left-color: var(--warning-color); }
.summary-info.info { border-left-color: var(--info-color); }
.summary-info.success { border-left-color: var(--success-color); }
.summary-info.danger { border-left-color: var(--danger-color); }
.summary-info.primary { border-left-color: var(--primary-color); }
.summary-info.secondary { border-left-color: var(--secondary-color); }

.activities-scroll {
    max-height: 300px;
    overflow-y: auto;
    padding-right: 8px;
}

.activities-scroll::-webkit-scrollbar {
    width: 6px;
}

.activities-scroll::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 3px;
}

.activity-item {
    display: flex;
    align-items: center;
    padding: 12px 0;
    border-bottom: 1px solid #f1f5f9;
}

.activity-item:last-child {
    border-bottom: none;
}

.activity-icon {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    background: linear-gradient(135deg, var(--primary-color), var(--info-color));
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 12px;
    flex-shrink: 0;
}

.activity-title {
    font-weight: 600;
    color: #1e293b;
}

.activity-meta {
    font-size: 0.875rem;
    color: #64748b;
}

.empty-state {
    text-align: center;
    color: #64748b;
    padding: 2rem 0;
}

.export-card {
    background: #fff;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    padding: 20px;
    text-align: center;
    transition: all 0.2s ease;
    cursor: pointer;
}

.export-card:hover {
    border-color: var(--primary-color);
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(37, 99, 235, 0.15);
}

.export-card i {
    font-size: 2rem;
    margin-bottom: 8px;
}

.export-card h6 {
    margin: 8px 0 4px 0;
    font-weight: 600;
}

.export-card small {
    color: #64748b;
}

.card {
    border: none;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    border-radius: 12px;
}

.card-header {
    border-bottom: 1px solid #f1f5f9;
    border-radius: 12px 12px 0 0 !important;
    padding: 16px 20px;
}

.card-title {
    font-weight: 600;
    color: #1e293b;
}
</style>

<script>
// Data dari server untuk charts
const chartData = {
    months: <?= json_encode(array_column($monthly_stats, 'month')) ?>,
    users: <?= json_encode(array_map('intval', array_column($monthly_stats, 'users'))) ?>,
    revenue: <?= json_encode(array_map('intval', array_column($monthly_stats, 'revenue'))) ?>,
    abstraks: <?= json_encode(array_map('intval', array_column($monthly_stats, 'abstraks'))) ?>
};

const abstrakStatus = {
    menunggu: <?= (int)($abstrak_by_status['menunggu'] ?? 0) ?>,
    sedang_direview: <?= (int)($abstrak_by_status['sedang_direview'] ?? 0) ?>,
    diterima: <?= (int)($abstrak_by_status['diterima'] ?? 0) ?>,
    ditolak: <?= (int)($abstrak_by_status['ditolak'] ?? 0) ?>,
    revisi: <?= (int)($abstrak_by_status['revisi'] ?? 0) ?>
};

const userRoles = {
    admin: <?= (int)($user_by_role['admin'] ?? 0) ?>,
    presenter: <?= (int)($user_by_role['presenter'] ?? 0) ?>,
    audience: <?= (int)($user_by_role['audience'] ?? 0) ?>,
    reviewer: <?= (int)($user_by_role['reviewer'] ?? 0) ?>
};

let charts = {};

document.addEventListener('DOMContentLoaded', function() {
    initializeCharts();
    animateNumbers();
});

function initializeCharts() {
    // Monthly Users Chart
    charts.monthlyUsers = new Chart(document.getElementById('monthlyUsersChart'), {
        type: 'line',
        data: {
            labels: chartData.months,
            datasets: [{
                label: 'Pendaftaran User',
                data: chartData.users,
                borderColor: '#2563eb',
                backgroundColor: 'rgba(37, 99, 235, 0.1)',
                borderWidth: 3,
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointHoverRadius: 6
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    grid: { color: 'rgba(0, 0, 0, 0.05)' }
                },
                x: {
                    grid: { display: false }
                }
            }
        }
    });

    // Monthly Revenue Chart
    charts.monthlyRevenue = new Chart(document.getElementById('monthlyRevenueChart'), {
        type: 'bar',
        data: {
            labels: chartData.months,
            datasets: [{
                label: 'Revenue',
                data: chartData.revenue,
                backgroundColor: 'rgba(16, 185, 129, 0.8)',
                borderColor: '#10b981',
                borderWidth: 2,
                borderRadius: 8,
                borderSkipped: false
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return 'Rp ' + Number(value).toLocaleString('id-ID');
                        }
                    }
                }
            }
        }
    });

    // Abstrak Status Chart
    charts.abstrakStatus = new Chart(document.getElementById('abstrakStatusChart'), {
        type: 'doughnut',
        data: {
            labels: ['Menunggu', 'Sedang Review', 'Diterima', 'Ditolak', 'Revisi'],
            datasets: [{
                data: [abstrakStatus.menunggu, abstrakStatus.sedang_direview, abstrakStatus.diterima, abstrakStatus.ditolak, abstrakStatus.revisi],
                backgroundColor: ['#f59e0b', '#06b6d4', '#10b981', '#ef4444', '#8b5cf6'],
                borderColor: '#fff',
                borderWidth: 3
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 20
                    }
                }
            }
        }
    });

    // User Roles Chart
    charts.userRoles = new Chart(document.getElementById('userRolesChart'), {
        type: 'polarArea',
        data: {
            labels: ['Admin', 'Presenter', 'Audience', 'Reviewer'],
            datasets: [{
                data: [userRoles.admin, userRoles.presenter, userRoles.audience, userRoles.reviewer],
                backgroundColor: [
                    'rgba(239, 68, 68, 0.7)',
                    'rgba(37, 99, 235, 0.7)',
                    'rgba(107, 114, 128, 0.7)',
                    'rgba(16, 185, 129, 0.7)'
                ],
                borderColor: ['#ef4444', '#2563eb', '#6b7280', '#10b981'],
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 20
                    }
                }
            }
        }
    });

    // Monthly Trends Chart
    charts.monthlyTrends = new Chart(document.getElementById('monthlyTrendsChart'), {
        type: 'line',
        data: {
            labels: chartData.months,
            datasets: [
                {
                    label: 'Users',
                    data: chartData.users,
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.1)',
                    tension: 0.4
                },
                {
                    label: 'Abstrak',
                    data: chartData.abstraks,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    tension: 0.4
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                legend: {
                    position: 'top'
                }
            },
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}

function animateNumbers() {
    document.querySelectorAll('.stat-number').forEach(element => {
        const target = parseInt(element.getAttribute('data-count')) || 0;
        const isRevenue = element.classList.contains('revenue');
        let current = 0;
        const step = Math.max(1, Math.floor(target / 60));
        
        const updateNumber = () => {
            current += step;
            if (current >= target) {
                current = target;
            }
            
            if (isRevenue) {
                element.textContent = 'Rp ' + current.toLocaleString('id-ID');
            } else {
                element.textContent = current.toLocaleString('id-ID');
            }
            
            if (current < target) {
                requestAnimationFrame(updateNumber);
            }
        };
        
        updateNumber();
    });
}

function refreshChart(type) {
    Swal.fire({
        title: 'Memuat data...',
        html: '<div class="d-flex justify-content-center"><div class="spinner-border text-primary" role="status"></div></div>',
        showConfirmButton: false,
        allowOutsideClick: false
    });

    // Simulate API call - replace with actual endpoint when available
    setTimeout(() => {
        Swal.close();
        Swal.fire({
            icon: 'success',
            title: 'Data diperbarui!',
            timer: 1500,
            showConfirmButton: false
        });
    }, 1000);
}

function exportReport(type) {
    Swal.fire({
        title: 'Export Laporan',
        text: `Unduh laporan ${type} sekarang?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#6b7280',
        confirmButtonText: 'Ya, Download',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            // Show loading
            Swal.fire({
                title: 'Menyiapkan file...',
                html: '<div class="d-flex justify-content-center"><div class="spinner-border text-success" role="status"></div></div>',
                showConfirmButton: false,
                allowOutsideClick: false
            });
            
            // Redirect to export
            setTimeout(() => {
                window.location.href = `<?= site_url('admin/laporan/export') ?>?type=${encodeURIComponent(type)}&format=csv`;
                Swal.close();
            }, 1000);
        }
    });
}
</script>