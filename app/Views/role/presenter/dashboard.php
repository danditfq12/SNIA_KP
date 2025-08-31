<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Presenter - SNIA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #64748b;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #06b6d4;
            --dark-color: #1e293b;
            --light-color: #f8fafc;
        }

        body {
            background: linear-gradient(135deg, var(--light-color) 0%, #e2e8f0 100%);
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }

        .sidebar {
            background: linear-gradient(180deg, var(--primary-color) 0%, #1e40af 100%);
            min-height: 100vh;
            box-shadow: 4px 0 20px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            z-index: 1000;
        }

        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            margin: 4px 0;
            border-radius: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .sidebar .nav-link:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            transform: translateX(5px);
        }

        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
            min-height: 100vh;
        }

        .welcome-card {
            background: linear-gradient(135deg, var(--primary-color), var(--info-color));
            color: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(37, 99, 235, 0.3);
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
            height: 100%;
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

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--primary-color);
            margin: 16px 0 8px 0;
        }

        .stat-title {
            color: var(--secondary-color);
            font-size: 0.95rem;
            font-weight: 600;
        }

        .activity-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .activity-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--info-color) 100%);
            color: white;
            padding: 20px;
            font-weight: 600;
        }

        .event-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
            overflow: hidden;
            margin-bottom: 20px;
        }

        .event-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }

        .event-header {
            padding: 20px;
            border-bottom: 1px solid #f1f5f9;
        }

        .event-body {
            padding: 20px;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .btn-custom {
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
        }

        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, var(--primary-color), var(--info-color));
            color: white;
        }

        .btn-success-custom {
            background: linear-gradient(135deg, var(--success-color), #059669);
            color: white;
        }

        .btn-warning-custom {
            background: linear-gradient(135deg, var(--warning-color), #d97706);
            color: white;
        }

        .abstract-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-menunggu {
            background: rgba(251, 191, 36, 0.1);
            color: #d97706;
            border: 1px solid rgba(251, 191, 36, 0.3);
        }

        .status-sedang_direview {
            background: rgba(59, 130, 246, 0.1);
            color: #2563eb;
            border: 1px solid rgba(59, 130, 246, 0.3);
        }

        .status-diterima {
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .status-ditolak {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .status-revisi {
            background: rgba(245, 158, 11, 0.1);
            color: #d97706;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .payment-status {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 6px 12px;
            border-radius: 16px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .payment-pending {
            background: rgba(251, 191, 36, 0.1);
            color: #d97706;
        }

        .payment-verified {
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
        }

        .payment-rejected {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
        }

        .quick-stats {
            background: white;
            border-radius: 12px;
            padding: 15px;
            margin-bottom: 20px;
            border-left: 4px solid var(--primary-color);
        }

        .price-display {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--primary-color);
            background: rgba(37, 99, 235, 0.1);
            padding: 8px 16px;
            border-radius: 20px;
            display: inline-block;
        }

        .offline-indicator {
            color: var(--warning-color);
            font-size: 0.8rem;
            font-weight: 600;
            background: rgba(245, 158, 11, 0.1);
            padding: 4px 8px;
            border-radius: 12px;
            margin-left: 8px;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: 250px;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }
        }

        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: var(--secondary-color);
        }

        .empty-state i {
            font-size: 3rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .timeline-item {
            border-left: 3px solid var(--primary-color);
            padding-left: 20px;
            margin-bottom: 20px;
            position: relative;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -8px;
            top: 5px;
            width: 12px;
            height: 12px;
            background: var(--primary-color);
            border-radius: 50%;
        }

        .section-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar">
        <div class="p-4 text-center">
            <h4 class="text-white mb-0">
                <i class="fas fa-microphone-alt me-2"></i>
                SNIA Presenter
            </h4>
            <small class="text-white-50">Dashboard</small>
        </div>
        
        <nav class="nav flex-column px-3">
            <a class="nav-link active" href="<?= site_url('presenter/dashboard') ?>">
                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
            </a>
            <a class="nav-link" href="<?= site_url('presenter/events') ?>">
                <i class="fas fa-calendar me-2"></i> Events
            </a>
            <a class="nav-link" href="<?= site_url('presenter/abstrak') ?>">
                <i class="fas fa-file-alt me-2"></i> My Abstracts
            </a>
            <a class="nav-link" href="<?= site_url('presenter/pembayaran') ?>">
                <i class="fas fa-credit-card me-2"></i> Payments
            </a>
            <a class="nav-link" href="<?= site_url('presenter/absensi') ?>">
                <i class="fas fa-qrcode me-2"></i> Attendance
            </a>
            <a class="nav-link" href="<?= site_url('presenter/dokumen/loa') ?>">
                <i class="fas fa-file-contract me-2"></i> LOA
            </a>
            <a class="nav-link" href="<?= site_url('presenter/dokumen/sertifikat') ?>">
                <i class="fas fa-certificate me-2"></i> Certificate
            </a>
            <hr class="my-3" style="border-color: rgba(255,255,255,0.2);">
            <a class="nav-link" href="<?= site_url('profile') ?>">
                <i class="fas fa-user me-2"></i> Profile
            </a>
            <a class="nav-link text-warning" href="<?= site_url('auth/logout') ?>">
                <i class="fas fa-sign-out-alt me-2"></i> Logout
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Welcome Section -->
        <div class="welcome-card animate__animated animate__fadeInDown">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="mb-2">
                        <i class="fas fa-wave-square me-2"></i>
                        Welcome back, <?= esc(session('nama_lengkap') ?? 'Presenter') ?>!
                    </h2>
                    <p class="mb-0 opacity-90">
                        Manage your conference presentations, submissions, and track your progress.
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="text-white-50 small">Last login</div>
                    <div class="fw-bold"><?= date('d M Y, H:i') ?></div>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="stat-card animate__animated animate__fadeInUp">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-calendar-alt fa-2x text-primary"></i>
                        </div>
                        <div>
                            <div class="stat-number"><?= count($events ?? []) ?></div>
                            <div class="stat-title">Available Events</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card animate__animated animate__fadeInUp" style="animation-delay: 0.1s">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-file-alt fa-2x text-success"></i>
                        </div>
                        <div>
                            <div class="stat-number"><?= count($user_abstracts ?? []) ?></div>
                            <div class="stat-title">My Abstracts</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card animate__animated animate__fadeInUp" style="animation-delay: 0.2s">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-credit-card fa-2x text-warning"></i>
                        </div>
                        <div>
                            <div class="stat-number"><?= count($user_payments ?? []) ?></div>
                            <div class="stat-title">Payments</div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stat-card animate__animated animate__fadeInUp" style="animation-delay: 0.3s">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-check-circle fa-2x text-info"></i>
                        </div>
                        <div>
                            <div class="stat-number">
                                <?= count(array_filter($user_abstracts ?? [], fn($a) => $a['status'] === 'diterima')) ?>
                            </div>
                            <div class="stat-title">Accepted</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Dashboard Content -->
        <div class="row">
            <!-- Available Events -->
            <div class="col-lg-8">
                <div class="activity-card">
                    <div class="activity-header">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar-plus me-2"></i>
                            Available Events for Registration
                        </h5>
                    </div>
                    <div class="p-4">
                        <?php if (!empty($events)): ?>
                            <?php foreach ($events as $event): ?>
                                <div class="event-card">
                                    <div class="event-header">
                                        <div class="row align-items-center">
                                            <div class="col-md-8">
                                                <h6 class="mb-1 fw-bold text-dark"><?= esc($event['title']) ?></h6>
                                                <div class="text-muted small">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    <?= date('d F Y', strtotime($event['event_date'])) ?> 
                                                    <i class="fas fa-clock ms-3 me-1"></i>
                                                    <?= date('H:i', strtotime($event['event_time'])) ?>
                                                </div>
                                                <?php if (!empty($event['location'])): ?>
                                                    <div class="text-muted small mt-1">
                                                        <i class="fas fa-map-marker-alt me-1"></i>
                                                        <?= esc($event['location']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-4 text-end">
                                                <div class="price-display">
                                                    Rp <?= number_format($event['presenter_fee_offline'] ?? 0, 0, ',', '.') ?>
                                                    <span class="offline-indicator">
                                                        <i class="fas fa-map-marker-alt me-1"></i>Offline Only
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="event-body">
                                        <?php if (!empty($event['description'])): ?>
                                            <p class="text-muted small mb-3"><?= esc(substr($event['description'], 0, 150)) ?>...</p>
                                        <?php endif; ?>
                                        
                                        <div class="row align-items-center">
                                            <div class="col-md-6">
                                                <?php
                                                $registrationStatus = 'not_registered';
                                                $userRegistration = null;
                                                
                                                if (!empty($event['user_registration'])) {
                                                    $userRegistration = $event['user_registration'];
                                                    $registrationStatus = $userRegistration['status'];
                                                }
                                                ?>
                                                
                                                <?php if ($registrationStatus === 'not_registered'): ?>
                                                    <span class="status-badge bg-light text-dark">
                                                        <i class="fas fa-clock me-1"></i>Not Registered
                                                    </span>
                                                <?php else: ?>
                                                    <span class="payment-status payment-<?= $registrationStatus ?>">
                                                        <i class="fas fa-<?= $registrationStatus === 'verified' ? 'check-circle' : ($registrationStatus === 'pending' ? 'clock' : 'times-circle') ?> me-1"></i>
                                                        <?= ucfirst($registrationStatus) ?>
                                                    </span>
                                                <?php endif; ?>
                                                
                                                <!-- Registration deadline info -->
                                                <?php if (!empty($event['registration_deadline'])): ?>
                                                    <div class="text-muted small mt-1">
                                                        <i class="fas fa-exclamation-triangle me-1 text-warning"></i>
                                                        Deadline: <?= date('d M Y, H:i', strtotime($event['registration_deadline'])) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-6 text-end">
                                                <?php if ($registrationStatus === 'not_registered'): ?>
                                                    <a href="<?= site_url('presenter/events/register/' . $event['id']) ?>" 
                                                       class="btn btn-primary-custom btn-custom">
                                                        <i class="fas fa-user-plus me-1"></i>Register Now
                                                    </a>
                                                <?php elseif ($registrationStatus === 'verified'): ?>
                                                    <a href="<?= site_url('presenter/abstrak/submit/' . $event['id']) ?>" 
                                                       class="btn btn-success-custom btn-custom">
                                                        <i class="fas fa-upload me-1"></i>Submit Abstract
                                                    </a>
                                                <?php else: ?>
                                                    <button class="btn btn-secondary btn-custom" disabled>
                                                        <i class="fas fa-hourglass-half me-1"></i>Pending Verification
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <a href="<?= site_url('presenter/events/detail/' . $event['id']) ?>" 
                                                   class="btn btn-outline-info btn-custom ms-2">
                                                    <i class="fas fa-info-circle"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-calendar-times"></i>
                                <h5>No Events Available</h5>
                                <p>There are currently no events open for registration.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Sidebar Info -->
            <div class="col-lg-4">
                <!-- Recent Abstract Activity -->
                <div class="activity-card mb-4">
                    <div class="activity-header">
                        <h6 class="mb-0">
                            <i class="fas fa-file-alt me-2"></i>
                            Recent Abstracts
                        </h6>
                    </div>
                    <div class="p-3">
                        <?php if (!empty($user_abstracts)): ?>
                            <?php foreach (array_slice($user_abstracts, 0, 5) as $abstract): ?>
                                <div class="timeline-item">
                                    <div class="fw-bold small"><?= esc($abstract['judul']) ?></div>
                                    <div class="text-muted small mb-2">
                                        <?= date('d M Y', strtotime($abstract['tanggal_upload'])) ?>
                                    </div>
                                    <div class="abstract-status status-<?= $abstract['status'] ?>">
                                        <i class="fas fa-<?= $abstract['status'] === 'diterima' ? 'check' : ($abstract['status'] === 'ditolak' ? 'times' : 'clock') ?>"></i>
                                        <?= ucfirst(str_replace('_', ' ', $abstract['status'])) ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                            
                            <div class="text-center mt-3">
                                <a href="<?= site_url('presenter/abstrak') ?>" class="btn btn-outline-primary btn-sm">
                                    View All Abstracts
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-3">
                                <i class="fas fa-file-alt fa-2x text-muted mb-2"></i>
                                <div class="text-muted small">No abstracts submitted yet</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="activity-card mb-4">
                    <div class="activity-header">
                        <h6 class="mb-0">
                            <i class="fas fa-bolt me-2"></i>
                            Quick Actions
                        </h6>
                    </div>
                    <div class="p-3">
                        <div class="d-grid gap-2">
                            <a href="<?= site_url('presenter/events') ?>" class="btn btn-primary-custom btn-custom">
                                <i class="fas fa-calendar-plus me-2"></i>Browse Events
                            </a>
                            <a href="<?= site_url('presenter/abstrak') ?>" class="btn btn-success-custom btn-custom">
                                <i class="fas fa-file-upload me-2"></i>Manage Abstracts
                            </a>
                            <a href="<?= site_url('presenter/pembayaran') ?>" class="btn btn-warning-custom btn-custom">
                                <i class="fas fa-credit-card me-2"></i>View Payments
                            </a>
                            <a href="<?= site_url('presenter/dokumen/loa') ?>" class="btn btn-outline-info btn-custom">
                                <i class="fas fa-download me-2"></i>Download LOA
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Notifications -->
                <div class="activity-card">
                    <div class="activity-header">
                        <h6 class="mb-0">
                            <i class="fas fa-bell me-2"></i>
                            Notifications
                        </h6>
                    </div>
                    <div class="p-3">
                        <?php
                        $notifications = [];
                        
                        // Add notifications for pending payments
                        if (!empty($user_payments)) {
                            foreach ($user_payments as $payment) {
                                if ($payment['status'] === 'pending') {
                                    $notifications[] = [
                                        'type' => 'warning',
                                        'icon' => 'fas fa-credit-card',
                                        'message' => 'Payment verification pending',
                                        'time' => $payment['tanggal_bayar']
                                    ];
                                }
                            }
                        }
                        
                        // Add notifications for abstract status
                        if (!empty($user_abstracts)) {
                            foreach ($user_abstracts as $abstract) {
                                if ($abstract['status'] === 'revisi') {
                                    $notifications[] = [
                                        'type' => 'info',
                                        'icon' => 'fas fa-edit',
                                        'message' => 'Abstract revision required: ' . substr($abstract['judul'], 0, 30) . '...',
                                        'time' => $abstract['tanggal_upload']
                                    ];
                                }
                            }
                        }
                        
                        // Limit to 3 most recent notifications
                        $notifications = array_slice($notifications, 0, 3);
                        ?>
                        
                        <?php if (!empty($notifications)): ?>
                            <?php foreach ($notifications as $notification): ?>
                                <div class="alert alert-<?= $notification['type'] ?> alert-dismissible fade show small" role="alert">
                                    <i class="<?= $notification['icon'] ?> me-2"></i>
                                    <?= $notification['message'] ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-3">
                                <i class="fas fa-bell-slash fa-2x text-muted mb-2"></i>
                                <div class="text-muted small">No new notifications</div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional Information Section -->
        <div class="row mt-4">
            <div class="col-12">
                <div class="activity-card">
                    <div class="activity-header">
                        <h5 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>
                            Important Information for Presenters
                        </h5>
                    </div>
                    <div class="p-4">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="quick-stats">
                                    <h6 class="text-primary">
                                        <i class="fas fa-map-marker-alt me-2"></i>
                                        Participation Format
                                    </h6>
                                    <p class="mb-0 small">
                                        As a presenter, you can <strong>only participate offline</strong>. All presentations must be delivered in-person at the venue.
                                    </p>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="quick-stats">
                                    <h6 class="text-success">
                                        <i class="fas fa-clock me-2"></i>
                                        Registration Process
                                    </h6>
                                    <p class="mb-0 small">
                                        1. Register for event<br>
                                        2. Complete payment<br>
                                        3. Wait for verification<br>
                                        4. Submit abstract
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
    
    <script>
        $(document).ready(function() {
            // Animate numbers
            $('.stat-number').each(function() {
                const $this = $(this);
                const finalNumber = parseInt($this.text());
                let currentNumber = 0;
                const increment = Math.ceil(finalNumber / 20);
                
                const counter = setInterval(() => {
                    currentNumber += increment;
                    if (currentNumber >= finalNumber) {
                        $this.text(finalNumber);
                        clearInterval(counter);
                    } else {
                        $this.text(currentNumber);
                    }
                }, 50);
            });
            
            // Auto-dismiss alerts after 10 seconds
            setTimeout(() => {
                $('.alert').alert('close');
            }, 10000);
        });

        // Mobile sidebar toggle
        function toggleSidebar() {
            $('.sidebar').toggleClass('show');
        }
    </script>

    <?php if (session('success')): ?>
        <script>
            $(document).ready(function() {
                const alert = $('<div class="alert alert-success alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999;" role="alert">' +
                    '<i class="fas fa-check-circle me-2"></i>' +
                    '<?= addslashes(session('success')) ?>' +
                    '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                    '</div>');
                $('body').append(alert);
                
                setTimeout(() => {
                    alert.alert('close');
                }, 5000);
            });
        </script>
    <?php endif; ?>
    
    <?php if (session('error')): ?>
        <script>
            $(document).ready(function() {
                const alert = $('<div class="alert alert-danger alert-dismissible fade show position-fixed" style="top: 20px; right: 20px; z-index: 9999;" role="alert">' +
                    '<i class="fas fa-exclamation-circle me-2"></i>' +
                    '<?= addslashes(session('error')) ?>' +
                    '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                    '</div>');
                $('body').append(alert);
                
                setTimeout(() => {
                    alert.alert('close');
                }, 5000);
            });
        </script>
    <?php endif; ?>
</body>
</html>