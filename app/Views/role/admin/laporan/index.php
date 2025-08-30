<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan - SNIA Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.js"></script>
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #64748b;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #06b6d4;
        }

        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }

        .sidebar {
            background: linear-gradient(180deg, var(--primary-color) 0%, #1e40af 100%);
            min-height: 100vh;
            box-shadow: 4px 0 20px rgba(0,0,0,0.1);
        }

        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            margin: 4px 0;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .sidebar .nav-link:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            transform: translateX(5px);
        }

        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: white;
        }

        .main-content {
            background: white;
            border-radius: 20px 0 0 0;
            min-height: 100vh;
            box-shadow: -4px 0 20px rgba(0,0,0,0.05);
        }

        .content-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            border: 1px solid rgba(255,255,255,0.2);
            overflow: hidden;
        }

        .content-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--info-color) 100%);
            color: white;
            padding: 24px;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            border: 1px solid rgba(255,255,255,0.2);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }

        .stat-card.users::before { background: linear-gradient(90deg, var(--primary-color), var(--info-color)); }
        .stat-card.abstraks::before { background: linear-gradient(90deg, var(--success-color), #059669); }
        .stat-card.payments::before { background: linear-gradient(90deg, var(--warning-color), #d97706); }
        .stat-card.revenue::before { background: linear-gradient(90deg, var(--danger-color), #dc2626); }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.15);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1e293b;
            margin-bottom: 8px;
        }

        .stat-label {
            color: #64748b;
            font-size: 0.9rem;
            font-weight: 500;
        }

        .chart-container {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }

        .chart-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 16px;
            border-bottom: 2px solid #f1f5f9;
        }

        .chart-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
        }

        .export-section {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            margin-top: 20px;
        }

        .export-card {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            border: 2px solid #e2e8f0;
            transition: all 0.3s ease;
            cursor: pointer;
            height: 100%;
        }

        .export-card:hover {
            border-color: var(--primary-color);
            background: #eff6ff;
            transform: translateY(-2px);
        }

        .recent-activity {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
        }

        .activity-item {
            padding: 16px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .activity-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 16px;
            color: white;
        }

        .btn-export {
            background: linear-gradient(135deg, var(--success-color), #059669);
            border: none;
            color: white;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-export:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
            color: white;
        }

        .summary-info {
            background: #f8fafc;
            border-radius: 8px;
            padding: 12px 16px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-left: 4px solid #e2e8f0;
        }

        .summary-info.warning { border-left-color: var(--warning-color); }
        .summary-info.success { border-left-color: var(--success-color); }
        .summary-info.danger { border-left-color: var(--danger-color); }
        .summary-info.info { border-left-color: var(--info-color); }

        .loading-spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .canvas-container {
            position: relative;
            height: 300px;
        }

        @media (max-width: 768px) {
            .stat-number {
                font-size: 2rem;
            }
            
            .chart-header {
                flex-direction: column;
                gap: 10px;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar">
                    <div class="p-4 text-center">
                        <h4 class="text-white mb-0">
                            <i class="fas fa-cogs me-2"></i>
                            SNIA Admin
                        </h4>
                        <small class="text-white-50">Sistem Manajemen</small>
                    </div>
                    
                    <nav class="nav flex-column px-3">
                        <a class="nav-link" href="<?= site_url('admin/dashboard') ?>">
                            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                        </a>
                        <a class="nav-link" href="<?= site_url('admin/users') ?>">
                            <i class="fas fa-users me-2"></i> Manajemen User
                        </a>
                        <a class="nav-link" href="<?= site_url('admin/abstrak') ?>">
                            <i class="fas fa-file-alt me-2"></i> Manajemen Abstrak
                        </a>
                        <a class="nav-link" href="<?= site_url('admin/reviewer') ?>">
                            <i class="fas fa-user-check me-2"></i> Kelola Reviewer
                        </a>
                        <a class="nav-link" href="<?= site_url('admin/pembayaran') ?>">
                            <i class="fas fa-credit-card me-2"></i> Verifikasi Pembayaran
                        </a>
                        <a class="nav-link" href="<?= site_url('admin/dokumen') ?>">
                            <i class="fas fa-folder-open me-2"></i> Dokumen
                        </a>
                        <a class="nav-link active" href="<?= site_url('admin/laporan') ?>">
                            <i class="fas fa-chart-line me-2"></i> Laporan
                        </a>
                        <hr class="my-3" style="border-color: rgba(255,255,255,0.2);">
                        <a class="nav-link text-warning" href="<?= site_url('auth/logout') ?>">
                            <i class="fas fa-sign-out-alt me-2"></i> Logout
                        </a>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <div class="main-content p-4">
                    <!-- Header -->
                    <div class="content-card mb-4">
                        <div class="content-header">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h2 class="mb-2">
                                        <i class="fas fa-chart-line me-3"></i>Laporan & Analitik
                                    </h2>
                                    <p class="mb-0 opacity-75">Pantau kinerja dan statistik sistem SNIA secara komprehensif</p>
                                </div>
                                <div class="col-auto">
                                    <div class="text-end">
                                        <small class="opacity-75 d-block">Terakhir update</small>
                                        <strong><?= date('d F Y, H:i') ?></strong>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Statistics -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-6 col-xl-3">
                            <div class="stat-card users">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <div class="stat-number"><?= $total_users ?? 0 ?></div>
                                        <div class="stat-label">Total Users</div>
                                    </div>
                                    <div>
                                        <i class="fas fa-users fa-2x text-primary"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-3">
                            <div class="stat-card abstraks">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <div class="stat-number"><?= $total_abstrak ?? 0 ?></div>
                                        <div class="stat-label">Total Abstrak</div>
                                    </div>
                                    <div>
                                        <i class="fas fa-file-alt fa-2x text-success"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-3">
                            <div class="stat-card payments">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <div class="stat-number"><?= $total_pembayaran ?? 0 ?></div>
                                        <div class="stat-label">Total Pembayaran</div>
                                    </div>
                                    <div>
                                        <i class="fas fa-credit-card fa-2x text-warning"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6 col-xl-3">
                            <div class="stat-card revenue">
                                <div class="d-flex align-items-center justify-content-between">
                                    <div>
                                        <div class="stat-number">Rp <?= number_format($total_revenue ?? 0, 0, ',', '.') ?></div>
                                        <div class="stat-label">Total Revenue</div>
                                    </div>
                                    <div>
                                        <i class="fas fa-money-bill-wave fa-2x text-danger"></i>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Charts Row -->
                    <div class="row g-4 mb-4">
                        <!-- Monthly Registration Chart -->
                        <div class="col-lg-6">
                            <div class="chart-container">
                                <div class="chart-header">
                                    <h5 class="chart-title">
                                        <i class="fas fa-user-plus me-2 text-primary"></i>
                                        Pendaftaran Bulanan
                                    </h5>
                                    <button class="btn btn-sm btn-outline-primary" onclick="refreshChart('users')">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </div>
                                <div class="canvas-container">
                                    <canvas id="monthlyUsersChart"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- Revenue Chart -->
                        <div class="col-lg-6">
                            <div class="chart-container">
                                <div class="chart-header">
                                    <h5 class="chart-title">
                                        <i class="fas fa-chart-area me-2 text-success"></i>
                                        Revenue Bulanan
                                    </h5>
                                    <button class="btn btn-sm btn-outline-success" onclick="refreshChart('revenue')">
                                        <i class="fas fa-sync-alt"></i>
                                    </button>
                                </div>
                                <div class="canvas-container">
                                    <canvas id="monthlyRevenueChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Status Distribution Charts -->
                    <div class="row g-4 mb-4">
                        <!-- Abstrak Status Chart -->
                        <div class="col-lg-6">
                            <div class="chart-container">
                                <div class="chart-header">
                                    <h5 class="chart-title">
                                        <i class="fas fa-chart-pie me-2 text-info"></i>
                                        Status Abstrak
                                    </h5>
                                </div>
                                <div class="mb-3">
                                    <div class="summary-info warning">
                                        <small class="text-muted">Menunggu Review</small>
                                        <div class="fw-bold text-warning"><?= $abstrak_by_status['menunggu'] ?? 0 ?></div>
                                    </div>
                                    <div class="summary-info info">
                                        <small class="text-muted">Sedang Review</small>
                                        <div class="fw-bold text-info"><?= $abstrak_by_status['sedang_direview'] ?? 0 ?></div>
                                    </div>
                                    <div class="summary-info success">
                                        <small class="text-muted">Diterima</small>
                                        <div class="fw-bold text-success"><?= $abstrak_by_status['diterima'] ?? 0 ?></div>
                                    </div>
                                    <div class="summary-info danger">
                                        <small class="text-muted">Ditolak</small>
                                        <div class="fw-bold text-danger"><?= $abstrak_by_status['ditolak'] ?? 0 ?></div>
                                    </div>
                                </div>
                                <div class="canvas-container">
                                    <canvas id="abstrakStatusChart"></canvas>
                                </div>
                            </div>
                        </div>

                        <!-- User Roles Chart -->
                        <div class="col-lg-6">
                            <div class="chart-container">
                                <div class="chart-header">
                                    <h5 class="chart-title">
                                        <i class="fas fa-users-cog me-2 text-secondary"></i>
                                        Distribusi Role User
                                    </h5>
                                </div>
                                <div class="mb-3">
                                    <div class="summary-info danger">
                                        <small class="text-muted">Admin</small>
                                        <div class="fw-bold text-danger"><?= $user_by_role['admin'] ?? 0 ?></div>
                                    </div>
                                    <div class="summary-info">
                                        <small class="text-muted">Presenter</small>
                                        <div class="fw-bold text-primary"><?= $user_by_role['presenter'] ?? 0 ?></div>
                                    </div>
                                    <div class="summary-info">
                                        <small class="text-muted">Audience</small>
                                        <div class="fw-bold text-secondary"><?= $user_by_role['audience'] ?? 0 ?></div>
                                    </div>
                                    <div class="summary-info success">
                                        <small class="text-muted">Reviewer</small>
                                        <div class="fw-bold text-success"><?= $user_by_role['reviewer'] ?? 0 ?></div>
                                    </div>
                                </div>
                                <div class="canvas-container">
                                    <canvas id="userRolesChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activities -->
                    <div class="row g-4 mb-4">
                        <div class="col-lg-4">
                            <div class="recent-activity">
                                <h5 class="mb-3">
                                    <i class="fas fa-clock me-2 text-primary"></i>
                                    Aktivitas Terbaru
                                </h5>
                                <?php if (!empty($recent_registrations)): ?>
                                    <?php foreach (array_slice($recent_registrations, 0, 3) as $user): ?>
                                        <div class="activity-item">
                                            <div class="activity-icon" style="background: linear-gradient(135deg, #2563eb, #06b6d4);">
                                                <i class="fas fa-user-plus"></i>
                                            </div>
                                            <div>
                                                <div class="fw-semibold"><?= esc($user['nama_lengkap']) ?></div>
                                                <small class="text-muted">Mendaftar sebagai <?= ucfirst($user['role']) ?></small>
                                                <div class="small text-muted"><?= timeAgo($user['created_at']) ?></div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center text-muted py-3">
                                        <i class="fas fa-inbox fa-2x mb-2"></i>
                                        <p>Belum ada aktivitas</p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="col-lg-8">
                            <!-- Monthly Trends Chart -->
                            <div class="chart-container">
                                <div class="chart-header">
                                    <h5 class="chart-title">
                                        <i class="fas fa-chart-line me-2 text-info"></i>
                                        Tren 6 Bulan Terakhir
                                    </h5>
                                </div>
                                <div class="canvas-container">
                                    <canvas id="monthlyTrendsChart"></canvas>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Export Section -->
                    <div class="export-section">
                        <h5 class="mb-4">
                            <i class="fas fa-download me-2 text-success"></i>
                            Export Laporan
                        </h5>
                        <div class="row g-3">
                            <div class="col-md-3">
                                <div class="export-card text-center" onclick="exportReport('users')">
                                    <i class="fas fa-users fa-2x text-primary mb-3"></i>
                                    <h6>Data Users</h6>
                                    <p class="small text-muted mb-0">Export semua data users</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="export-card text-center" onclick="exportReport('abstrak')">
                                    <i class="fas fa-file-alt fa-2x text-success mb-3"></i>
                                    <h6>Data Abstrak</h6>
                                    <p class="small text-muted mb-0">Export data abstrak & review</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="export-card text-center" onclick="exportReport('pembayaran')">
                                    <i class="fas fa-credit-card fa-2x text-warning mb-3"></i>
                                    <h6>Data Pembayaran</h6>
                                    <p class="small text-muted mb-0">Export transaksi pembayaran</p>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="export-card text-center" onclick="exportReport('comprehensive')">
                                    <i class="fas fa-chart-line fa-2x text-info mb-3"></i>
                                    <h6>Laporan Komprehensif</h6>
                                    <p class="small text-muted mb-0">Export laporan lengkap</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Chart instances
        let monthlyUsersChart, monthlyRevenueChart, abstrakStatusChart, userRolesChart, monthlyTrendsChart;

        document.addEventListener('DOMContentLoaded', function() {
            initializeCharts();
            animateNumbers();
        });

        function initializeCharts() {
            // Monthly Users Chart
            const usersCtx = document.getElementById('monthlyUsersChart').getContext('2d');
            monthlyUsersChart = new Chart(usersCtx, {
                type: 'line',
                data: {
                    labels: <?= json_encode(array_column($monthly_stats ?? [], 'month')) ?>,
                    datasets: [{
                        label: 'Pendaftaran User',
                        data: <?= json_encode(array_column($monthly_stats ?? [], 'users')) ?>,
                        borderColor: '#2563eb',
                        backgroundColor: 'rgba(37, 99, 235, 0.1)',
                        borderWidth: 3,
                        fill: true,
                        tension: 0.4,
                        pointBackgroundColor: '#2563eb',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 6
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            grid: {
                                color: 'rgba(0,0,0,0.05)'
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            }
                        }
                    }
                }
            });

            // Monthly Revenue Chart
            const revenueCtx = document.getElementById('monthlyRevenueChart').getContext('2d');
            monthlyRevenueChart = new Chart(revenueCtx, {
                type: 'bar',
                data: {
                    labels: <?= json_encode(array_column($monthly_stats ?? [], 'month')) ?>,
                    datasets: [{
                        label: 'Revenue',
                        data: <?= json_encode(array_column($monthly_stats ?? [], 'revenue')) ?>,
                        backgroundColor: 'rgba(16, 185, 129, 0.8)',
                        borderColor: '#10b981',
                        borderWidth: 2,
                        borderRadius: 8,
                        borderSkipped: false,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            display: false
                        }
                    },
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                callback: function(value) {
                                    return 'Rp ' + value.toLocaleString();
                                }
                            }
                        }
                    }
                }
            });

            // Abstrak Status Chart
            const abstrakCtx = document.getElementById('abstrakStatusChart').getContext('2d');
            abstrakStatusChart = new Chart(abstrakCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Menunggu', 'Sedang Review', 'Diterima', 'Ditolak', 'Revisi'],
                    datasets: [{
                        data: [
                            <?= $abstrak_by_status['menunggu'] ?? 0 ?>,
                            <?= $abstrak_by_status['sedang_direview'] ?? 0 ?>,
                            <?= $abstrak_by_status['diterima'] ?? 0 ?>,
                            <?= $abstrak_by_status['ditolak'] ?? 0 ?>,
                            <?= $abstrak_by_status['revisi'] ?? 0 ?>
                        ],
                        backgroundColor: [
                            '#f59e0b',
                            '#06b6d4',
                            '#10b981',
                            '#ef4444',
                            '#8b5cf6'
                        ],
                        borderWidth: 3,
                        borderColor: '#fff'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                usePointStyle: true
                            }
                        }
                    }
                }
            });

            // User Roles Chart
            const rolesCtx = document.getElementById('userRolesChart').getContext('2d');
            userRolesChart = new Chart(rolesCtx, {
                type: 'polarArea',
                data: {
                    labels: ['Admin', 'Presenter', 'Audience', 'Reviewer'],
                    datasets: [{
                        data: [
                            <?= $user_by_role['admin'] ?? 0 ?>,
                            <?= $user_by_role['presenter'] ?? 0 ?>,
                            <?= $user_by_role['audience'] ?? 0 ?>,
                            <?= $user_by_role['reviewer'] ?? 0 ?>
                        ],
                        backgroundColor: [
                            'rgba(239, 68, 68, 0.7)',
                            'rgba(37, 99, 235, 0.7)',
                            'rgba(100, 116, 139, 0.7)',
                            'rgba(16, 185, 129, 0.7)'
                        ],
                        borderColor: [
                            '#ef4444',
                            '#2563eb',
                            '#64748b',
                            '#10b981'
                        ],
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
                                padding: 20,
                                usePointStyle: true
                            }
                        }
                    }
                }
            });

            // Monthly Trends Chart (Combined)
            const trendsCtx = document.getElementById('monthlyTrendsChart').getContext('2d');
            monthlyTrendsChart = new Chart(trendsCtx, {
                type: 'line',
                data: {
                    labels: <?= json_encode(array_column($monthly_stats ?? [], 'month')) ?>,
                    datasets: [
                        {
                            label: 'Users',
                            data: <?= json_encode(array_column($monthly_stats ?? [], 'users')) ?>,
                            borderColor: '#2563eb',
                            backgroundColor: 'rgba(37, 99, 235, 0.1)',
                            yAxisID: 'y',
                            tension: 0.4
                        },
                        {
                            label: 'Abstrak',
                            data: <?= json_encode(array_column($monthly_stats ?? [], 'abstraks')) ?>,
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.1)',
                            yAxisID: 'y',
                            tension: 0.4
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: {
                        mode: 'index',
                        intersect: false,
                    },
                    plugins: {
                        legend: {
                            position: 'top',
                        }
                    },
                    scales: {
                        x: {
                            display: true,
                        },
                        y: {
                            type: 'linear',
                            display: true,
                            position: 'left',
                            beginAtZero: true
                        }
                    }
                }
            });
        }

        function animateNumbers() {
            const numbers = document.querySelectorAll('.stat-number');
            numbers.forEach(number => {
                const text = number.textContent;
                const isRupiah = text.includes('Rp');
                const finalNumber = parseInt(text.replace(/[^\d]/g, ''));
                let currentNumber = 0;
                const increment = finalNumber / 50;
                
                const timer = setInterval(() => {
                    currentNumber += increment;
                    if (currentNumber >= finalNumber) {
                        number.textContent = isRupiah ? 
                            'Rp ' + finalNumber.toLocaleString('id-ID') : finalNumber.toLocaleString('id-ID');
                        clearInterval(timer);
                    } else {
                        const displayNumber = Math.floor(currentNumber);
                        number.textContent = isRupiah ? 
                            'Rp ' + displayNumber.toLocaleString('id-ID') : displayNumber.toLocaleString('id-ID');
                    }
                }, 20);
            });
        }

        function refreshChart(type) {
            Swal.fire({
                title: 'Memuat data...',
                html: '<div class="loading-spinner"></div>',
                showConfirmButton: false,
                allowOutsideClick: false
            });

            setTimeout(() => {
                Swal.close();
                Swal.fire({
                    icon: 'success',
                    title: 'Data berhasil diperbarui!',
                    timer: 1500,
                    showConfirmButton: false
                });
            }, 1000);
        }

        function exportReport(type) {
            Swal.fire({
                title: 'Export Laporan',
                text: `Apakah Anda ingin mengunduh laporan ${type}?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Download!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Menyiapkan file...',
                        html: '<div class="loading-spinner"></div>',
                        showConfirmButton: false,
                        allowOutsideClick: false
                    });
                    
                    setTimeout(() => {
                        window.location.href = `<?= site_url('admin/laporan/export') ?>?type=${type}&format=csv`;
                        Swal.close();
                    }, 1000);
                }
            });
        }

        // Helper function for time ago (if needed)
        <?php
        function timeAgo($datetime) {
            $time = time() - strtotime($datetime);
            if ($time < 60) return 'Baru saja';
            if ($time < 3600) return floor($time/60) . ' menit yang lalu';
            if ($time < 86400) return floor($time/3600) . ' jam yang lalu';
            if ($time < 2592000) return floor($time/86400) . ' hari yang lalu';
            return date('d M Y', strtotime($datetime));
        }
        ?>

        // Smooth entrance animations
        const observerOptions = {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        };

        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.style.opacity = '1';
                    entry.target.style.transform = 'translateY(0)';
                }
            });
        }, observerOptions);

        document.querySelectorAll('.stat-card, .chart-container, .export-section, .recent-activity').forEach((el, index) => {
            el.style.opacity = '0';
            el.style.transform = 'translateY(20px)';
            el.style.transition = `all 0.6s ease ${index * 0.1}s`;
            observer.observe(el);
        });
    </script>
</body>
</html>