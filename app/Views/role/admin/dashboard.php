<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SNIA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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
            background: linear-gradient(90deg, var(--primary-color), var(--info-color));
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.15);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            color: white;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1e293b;
            margin: 16px 0 8px 0;
        }

        .recent-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            border: 1px solid rgba(255,255,255,0.2);
            overflow: hidden;
        }

        .recent-card-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--info-color) 100%);
            color: white;
            padding: 20px;
            font-weight: 600;
        }

        .list-item {
            padding: 16px 20px;
            border-bottom: 1px solid #e2e8f0;
            transition: all 0.2s ease;
        }

        .list-item:hover {
            background: #f8fafc;
        }

        .list-item:last-child {
            border-bottom: none;
        }

        .badge-custom {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .header-section {
            background: white;
            padding: 24px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        .welcome-text {
            color: var(--primary-color);
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .chart-container {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            margin-top: 30px;
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
                        <a class="nav-link active" href="<?= site_url('admin/dashboard') ?>">
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
                        <a class="nav-link" href="<?= site_url('admin/laporan') ?>">
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
                    <div class="header-section">
                        <div class="row align-items-center">
                            <div class="col">
                                <h1 class="welcome-text">
                                    <i class="fas fa-chart-pie me-3"></i>Dashboard Admin
                                </h1>
                                <p class="text-muted mb-0">
                                    Selamat datang kembali, <strong><?= session('nama_lengkap') ?? 'Admin' ?></strong>! 
                                    Kelola sistem SNIA dengan mudah dari sini.
                                </p>
                            </div>
                            <div class="col-auto">
                                <div class="text-end">
                                    <small class="text-muted d-block">Terakhir login</small>
                                    <strong><?= date('d F Y, H:i') ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-6 col-xl-3">
                            <div class="stat-card">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon" style="background: linear-gradient(135deg, var(--primary-color), var(--info-color));">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <div class="ms-3">
                                        <div class="stat-number"><?= $total_users ?? 0 ?></div>
                                        <div class="text-muted">Total User</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-xl-3">
                            <div class="stat-card">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon" style="background: linear-gradient(135deg, var(--success-color), #059669);">
                                        <i class="fas fa-file-alt"></i>
                                    </div>
                                    <div class="ms-3">
                                        <div class="stat-number"><?= $total_abstrak ?? 0 ?></div>
                                        <div class="text-muted">Total Abstrak</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-xl-3">
                            <div class="stat-card">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon" style="background: linear-gradient(135deg, var(--warning-color), #d97706);">
                                        <i class="fas fa-credit-card"></i>
                                    </div>
                                    <div class="ms-3">
                                        <div class="stat-number"><?= $pembayaran_pending ?? 0 ?></div>
                                        <div class="text-muted">Pembayaran Pending</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-xl-3">
                            <div class="stat-card">
                                <div class="d-flex align-items-center">
                                    <div class="stat-icon" style="background: linear-gradient(135deg, var(--info-color), #0891b2);">
                                        <i class="fas fa-user-check"></i>
                                    </div>
                                    <div class="ms-3">
                                        <div class="stat-number"><?= $total_reviewer ?? 0 ?></div>
                                        <div class="text-muted">Total Reviewer</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Role Statistics -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-4">
                            <div class="stat-card text-center">
                                <div class="stat-icon mx-auto mb-3" style="background: linear-gradient(135deg, #8b5cf6, #7c3aed);">
                                    <i class="fas fa-microphone"></i>
                                </div>
                                <div class="stat-number"><?= $total_presenter ?? 0 ?></div>
                                <div class="text-muted">Presenter</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card text-center">
                                <div class="stat-icon mx-auto mb-3" style="background: linear-gradient(135deg, #06b6d4, #0891b2);">
                                    <i class="fas fa-users"></i>
                                </div>
                                <div class="stat-number"><?= $total_audience ?? 0 ?></div>
                                <div class="text-muted">Audience</div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="stat-card text-center">
                                <div class="stat-icon mx-auto mb-3" style="background: linear-gradient(135deg, #10b981, #059669);">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <div class="stat-number"><?= $pembayaran_verified ?? 0 ?></div>
                                <div class="text-muted">Pembayaran Verified</div>
                            </div>
                        </div>
                    </div>

                    <!-- Recent Activities -->
                    <div class="row g-4">
                        <div class="col-lg-6">
                            <div class="recent-card">
                                <div class="recent-card-header">
                                    <i class="fas fa-user-plus me-2"></i>
                                    User Terbaru
                                </div>
                                <div class="card-body p-0">
                                    <?php if (!empty($recent_users)): ?>
                                        <?php foreach ($recent_users as $user): ?>
                                            <div class="list-item">
                                                <div class="d-flex align-items-center">
                                                    <div class="flex-shrink-0">
                                                        <div class="bg-primary rounded-circle p-2" style="width: 40px; height: 40px;">
                                                            <i class="fas fa-user text-white"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <div class="fw-semibold"><?= esc($user['nama_lengkap']) ?></div>
                                                        <div class="text-muted small"><?= esc($user['email']) ?></div>
                                                    </div>
                                                    <div class="flex-shrink-0">
                                                        <span class="badge-custom <?= $user['role'] == 'presenter' ? 'bg-success' : ($user['role'] == 'reviewer' ? 'bg-info' : 'bg-secondary') ?>">
                                                            <?= ucfirst($user['role']) ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="list-item text-center text-muted">
                                            <i class="fas fa-inbox fa-2x mb-2"></i>
                                            <div>Belum ada user terbaru</div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <div class="recent-card">
                                <div class="recent-card-header">
                                    <i class="fas fa-file-alt me-2"></i>
                                    Abstrak Terbaru
                                </div>
                                <div class="card-body p-0">
                                    <?php if (!empty($recent_abstrak)): ?>
                                        <?php foreach ($recent_abstrak as $abstrak): ?>
                                            <div class="list-item">
                                                <div class="d-flex align-items-start">
                                                    <div class="flex-shrink-0">
                                                        <div class="bg-success rounded-circle p-2" style="width: 40px; height: 40px;">
                                                            <i class="fas fa-file text-white"></i>
                                                        </div>
                                                    </div>
                                                    <div class="flex-grow-1 ms-3">
                                                        <div class="fw-semibold"><?= esc(substr($abstrak['judul'], 0, 50)) ?>...</div>
                                                        <div class="text-muted small">
                                                            oleh <?= esc($abstrak['nama_lengkap']) ?>
                                                        </div>
                                                    </div>
                                                    <div class="flex-shrink-0">
                                                        <?php
                                                        $statusClass = '';
                                                        switch($abstrak['status']) {
                                                            case 'menunggu': $statusClass = 'bg-warning'; break;
                                                            case 'diterima': $statusClass = 'bg-success'; break;
                                                            case 'ditolak': $statusClass = 'bg-danger'; break;
                                                            default: $statusClass = 'bg-secondary';
                                                        }
                                                        ?>
                                                        <span class="badge-custom <?= $statusClass ?>">
                                                            <?= ucfirst($abstrak['status']) ?>
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="list-item text-center text-muted">
                                            <i class="fas fa-inbox fa-2x mb-2"></i>
                                            <div>Belum ada abstrak</div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Status Overview Chart -->
                    <div class="chart-container">
                        <h5 class="mb-4">
                            <i class="fas fa-chart-pie me-2 text-primary"></i>
                            Status Abstrak Overview
                        </h5>
                        <div class="row">
                            <div class="col-md-3 text-center">
                                <div class="p-3">
                                    <div class="display-6 text-warning"><?= $abstrak_pending ?? 0 ?></div>
                                    <div class="text-muted">Menunggu Review</div>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="p-3">
                                    <div class="display-6 text-success"><?= $abstrak_diterima ?? 0 ?></div>
                                    <div class="text-muted">Diterima</div>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="p-3">
                                    <div class="display-6 text-danger"><?= $abstrak_ditolak ?? 0 ?></div>
                                    <div class="text-muted">Ditolak</div>
                                </div>
                            </div>
                            <div class="col-md-3 text-center">
                                <div class="p-3">
                                    <div class="display-6 text-info"><?= $total_abstrak ?? 0 ?></div>
                                    <div class="text-muted">Total</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add some interactive features
        document.addEventListener('DOMContentLoaded', function() {
            // Animate numbers on page load
            const numbers = document.querySelectorAll('.stat-number');
            numbers.forEach(number => {
                const finalNumber = parseInt(number.textContent);
                let currentNumber = 0;
                const increment = finalNumber / 50;
                
                const timer = setInterval(() => {
                    currentNumber += increment;
                    if (currentNumber >= finalNumber) {
                        number.textContent = finalNumber;
                        clearInterval(timer);
                    } else {
                        number.textContent = Math.floor(currentNumber);
                    }
                }, 20);
            });

            // Add hover effects to cards
            const cards = document.querySelectorAll('.stat-card, .recent-card');
            cards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-5px)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });
    </script>
</body>
</html>