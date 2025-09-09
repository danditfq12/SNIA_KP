<?php
// app/Views/role/presenter/event/index.php
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Available Events - SNIA Presenter</title>
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
            --dark-color: #1e293b;
            --light-color: #f8fafc;
        }

        body {
            background: linear-gradient(135deg, var(--light-color) 0%, #e2e8f0 100%);
            font-family: 'Inter', 'Segoe UI', sans-serif;
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
            margin-left: 250px;
            padding: 20px;
            min-height: 100vh;
        }

        .content-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            border: 1px solid rgba(255,255,255,0.2);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .content-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--info-color) 100%);
            color: white;
            padding: 24px;
        }

        .event-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            margin-bottom: 20px;
        }

        .event-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.12);
        }

        .event-header {
            position: relative;
            padding: 25px;
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.1) 0%, rgba(6, 182, 212, 0.1) 100%);
            border-bottom: 1px solid #e2e8f0;
        }

        .event-body {
            padding: 25px;
        }

        .event-footer {
            padding: 20px 25px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
        }

        .event-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--dark-color);
            margin-bottom: 12px;
        }

        .event-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 16px;
            color: var(--secondary-color);
            font-size: 0.9rem;
        }

        .event-meta-item {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .price-display {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--success-color);
            background: rgba(16, 185, 129, 0.1);
            padding: 12px 20px;
            border-radius: 12px;
            text-align: center;
            margin-bottom: 16px;
        }

        .offline-badge {
            background: rgba(245, 158, 11, 0.1);
            color: #d97706;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
            margin-top: 8px;
        }

        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .status-not-registered {
            background: rgba(107, 114, 128, 0.1);
            color: #6b7280;
        }

        .status-pending {
            background: rgba(251, 191, 36, 0.1);
            color: #d97706;
        }

        .status-verified {
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
        }

        .status-rejected {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
        }

        .btn-custom {
            border-radius: 8px;
            padding: 12px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            text-decoration: none;
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

        .deadline-warning {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 8px;
            padding: 12px 16px;
            margin-top: 12px;
        }

        .deadline-info {
            background: rgba(6, 182, 212, 0.1);
            border: 1px solid rgba(6, 182, 212, 0.3);
            border-radius: 8px;
            padding: 12px 16px;
            margin-top: 12px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--secondary-color);
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .filter-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }

        .stats-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stats-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--info-color));
        }

        .stats-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }
    </style>
</head>
<body>
    <?php $eventM = $eventM ?? new \App\Models\EventModel(); ?>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="p-4 text-center">
            <h4 class="text-white mb-0">
                <i class="fas fa-microphone-alt me-2"></i>
                SNIA Presenter
            </h4>
            <small class="text-white-50">Event Management</small>
        </div>
        
        <nav class="nav flex-column px-3">
            <a class="nav-link" href="<?= site_url('presenter/dashboard') ?>">
                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
            </a>
            <a class="nav-link active" href="<?= site_url('presenter/events') ?>">
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
        <!-- Header -->
        <div class="content-card">
            <div class="content-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h2 class="mb-2">
                            <i class="fas fa-calendar-plus me-3"></i>Available Events
                        </h2>
                        <p class="mb-0 opacity-75">Register for events and manage your presentations</p>
                    </div>
                    <div class="col-auto">
                        <button class="btn btn-outline-light btn-custom" onclick="location.reload()">
                            <i class="fas fa-sync-alt me-2"></i>Refresh
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-calendar-alt fa-2x text-primary"></i>
                        </div>
                        <div>
                            <h4 class="mb-0"><?= count($events) ?></h4>
                            <small class="text-muted">Available Events</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-check-circle fa-2x text-success"></i>
                        </div>
                        <div>
                            <h4 class="mb-0">
                                <?= count(array_filter($events, fn($e) => isset($e['user_registration']) && $e['user_registration']['status'] === 'verified')) ?>
                            </h4>
                            <small class="text-muted">Registered</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-clock fa-2x text-warning"></i>
                        </div>
                        <div>
                            <h4 class="mb-0">
                                <?= count(array_filter($events, fn($e) => isset($e['user_registration']) && $e['user_registration']['status'] === 'pending')) ?>
                            </h4>
                            <small class="text-muted">Pending</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="col-md-3">
                <div class="stats-card">
                    <div class="d-flex align-items-center">
                        <div class="me-3">
                            <i class="fas fa-map-marker-alt fa-2x text-info"></i>
                        </div>
                        <div>
                            <h4 class="mb-0"><?= count($events) ?></h4>
                            <small class="text-muted">Offline Only</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Events List -->
        <?php if (!empty($events)): ?>
            <div class="row">
                <?php foreach ($events as $event): ?>
                    <div class="col-lg-6 mb-4">
                        <div class="event-card">
                            <!-- Event Header -->
                            <div class="event-header">
                                <h3 class="event-title"><?= esc($event['title']) ?></h3>
                                
                                <div class="event-meta">
                                    <div class="event-meta-item">
                                        <i class="fas fa-calendar text-primary"></i>
                                        <span><?= date('d F Y', strtotime($event['event_date'])) ?></span>
                                    </div>
                                    <div class="event-meta-item">
                                        <i class="fas fa-clock text-info"></i>
                                        <span><?= date('H:i', strtotime($event['event_time'])) ?></span>
                                    </div>
                                    <?php if ($event['location']): ?>
                                        <div class="event-meta-item">
                                            <i class="fas fa-map-marker-alt text-warning"></i>
                                            <span><?= esc($event['location']) ?></span>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Registration Status -->
                                <?php
                                $registrationStatus = 'not_registered';
                                $userRegistration = null;
                                
                                if (!empty($event['user_registration'])) {
                                    $userRegistration = $event['user_registration'];
                                    $registrationStatus = $userRegistration['status'];
                                }
                                ?>
                                
                                <div class="d-flex justify-content-between align-items-center">
                                    <?php if ($registrationStatus === 'not_registered'): ?>
                                        <span class="status-badge status-not-registered">
                                            <i class="fas fa-user-plus"></i>Not Registered
                                        </span>
                                    <?php elseif ($registrationStatus === 'pending'): ?>
                                        <span class="status-badge status-pending">
                                            <i class="fas fa-clock"></i>Pending Verification
                                        </span>
                                    <?php elseif ($registrationStatus === 'verified'): ?>
                                        <span class="status-badge status-verified">
                                            <i class="fas fa-check-circle"></i>Registered
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge status-rejected">
                                            <i class="fas fa-times-circle"></i>Rejected
                                        </span>
                                    <?php endif; ?>
                                    
                                    <span class="offline-badge">
                                        <i class="fas fa-map-marker-alt me-1"></i>Offline Only
                                    </span>
                                </div>
                            </div>

                            <!-- Event Body -->
                            <div class="event-body">
                                <?php if ($event['description']): ?>
                                    <p class="text-muted mb-3"><?= esc(substr($event['description'], 0, 150)) ?>...</p>
                                <?php endif; ?>

                                <!-- Pricing -->
                                <div class="price-display">
                                    Rp <?= number_format($event['presenter_fee_offline'] ?? 0, 0, ',', '.') ?>
                                    <div class="small text-muted mt-1">Presenter Fee (Offline)</div>
                                </div>

                                <!-- Event Stats -->
                                <?php if (isset($event['stats'])): ?>
                                    <div class="row text-center">
                                        <div class="col-4">
                                            <div class="small text-muted">Registrations</div>
                                            <div class="fw-bold text-primary"><?= $event['stats']['total_registrations'] ?? 0 ?></div>
                                        </div>
                                        <div class="col-4">
                                            <div class="small text-muted">Abstracts</div>
                                            <div class="fw-bold text-success"><?= $event['stats']['total_abstracts'] ?? 0 ?></div>
                                        </div>
                                        <div class="col-4">
                                            <div class="small text-muted">Revenue</div>
                                            <div class="fw-bold text-info">
                                                <?= 'Rp ' . number_format($event['stats']['total_revenue'] ?? 0, 0, ',', '.') ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <!-- Deadline Information -->
                                <?php if ($event['registration_deadline']): ?>
                                    <?php
                                    $deadlineTime = strtotime($event['registration_deadline']);
                                    $daysLeft = ceil(($deadlineTime - time()) / (60 * 60 * 24));
                                    ?>
                                    
                                    <?php if ($daysLeft <= 3 && $daysLeft > 0): ?>
                                        <div class="deadline-warning">
                                            <small class="text-danger">
                                                <i class="fas fa-exclamation-triangle me-1"></i>
                                                <strong>Registration closes in <?= $daysLeft ?> day(s)!</strong><br>
                                                Deadline: <?= date('d M Y, H:i', strtotime($event['registration_deadline'])) ?>
                                            </small>
                                        </div>
                                    <?php elseif ($daysLeft > 0): ?>
                                        <div class="deadline-info">
                                            <small class="text-info">
                                                <i class="fas fa-info-circle me-1"></i>
                                                Registration deadline: <?= date('d M Y, H:i', strtotime($event['registration_deadline'])) ?>
                                            </small>
                                        </div>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>

                            <!-- Event Footer -->
                            <div class="event-footer">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <!-- Payment Information -->
                                        <?php if ($userRegistration): ?>
                                            <div class="small text-muted">
                                                Payment: Rp <?= number_format($userRegistration['jumlah'], 0, ',', '.') ?>
                                                <br>
                                                Submitted: <?= date('d M Y', strtotime($userRegistration['tanggal_bayar'])) ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-auto">
                                        <div class="btn-group" role="group">
                                            <!-- Main Action Button -->
                                            <?php if ($registrationStatus === 'not_registered'): ?>
                                                <?php if ($eventM->isRegistrationOpen($event['id'])): ?>
                                                    <a href="<?= site_url('presenter/events/register/' . $event['id']) ?>" 
                                                       class="btn btn-primary-custom btn-custom">
                                                        <i class="fas fa-user-plus me-1"></i>Register Now
                                                    </a>
                                                <?php else: ?>
                                                    <button class="btn btn-secondary btn-custom" disabled>
                                                        <i class="fas fa-lock me-1"></i>Registration Closed
                                                    </button>
                                                <?php endif; ?>
                                            <?php elseif ($registrationStatus === 'verified'): ?>
                                                <a href="<?= site_url('presenter/abstrak') ?>?event=<?= $event['id'] ?>" 
                                                   class="btn btn-success-custom btn-custom">
                                                    <i class="fas fa-file-upload me-1"></i>Submit Abstract
                                                </a>
                                            <?php elseif ($registrationStatus === 'pending'): ?>
                                                <a href="<?= site_url('presenter/pembayaran') ?>" 
                                                   class="btn btn-warning-custom btn-custom">
                                                    <i class="fas fa-clock me-1"></i>View Payment
                                                </a>
                                            <?php else: ?>
                                                <a href="<?= site_url('presenter/events/register/' . $event['id']) ?>" 
                                                   class="btn btn-primary-custom btn-custom">
                                                    <i class="fas fa-redo me-1"></i>Register Again
                                                </a>
                                            <?php endif; ?>
                                            
                                            <!-- View Details Button -->
                                            <a href="<?= site_url('presenter/events/detail/' . $event['id']) ?>" 
                                               class="btn btn-outline-info btn-custom">
                                                <i class="fas fa-info-circle"></i>
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="content-card">
                <div class="p-5">
                    <div class="empty-state">
                        <i class="fas fa-calendar-times"></i>
                        <h4>No Events Available</h4>
                        <p>There are currently no events available for registration.</p>
                        <p class="text-muted small">
                            Events will appear here when registration is opened by the admin.
                        </p>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Information Card -->
        <div class="content-card mt-4">
            <div class="content-header">
                <h5 class="mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    Presenter Guidelines
                </h5>
            </div>
            <div class="p-4">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-primary mb-3">
                            <i class="fas fa-clipboard-list me-2"></i>Registration Process
                        </h6>
                        <ol class="list-group list-group-flush">
                            <li class="list-group-item border-0 px-0">
                                <strong>1. Register for Event</strong><br>
                                <small class="text-muted">Choose an event and complete registration form</small>
                            </li>
                            <li class="list-group-item border-0 px-0">
                                <strong>2. Make Payment</strong><br>
                                <small class="text-muted">Upload payment proof and wait for verification</small>
                            </li>
                            <li class="list-group-item border-0 px-0">
                                <strong>3. Submit Abstract</strong><br>
                                <small class="text-muted">Submit your presentation abstract for review</small>
                            </li>
                            <li class="list-group-item border-0 px-0">
                                <strong>4. Present at Event</strong><br>
                                <small class="text-muted">Deliver your presentation on the event day</small>
                            </li>
                        </ol>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-success mb-3">
                            <i class="fas fa-map-marker-alt me-2"></i>Important Notes
                        </h6>
                        <div class="alert alert-warning">
                            <h6 class="alert-heading">
                                <i class="fas fa-exclamation-triangle me-2"></i>Offline Participation Only
                            </h6>
                            <p class="mb-0 small">
                                As a presenter, you must participate <strong>offline</strong> (in-person). 
                                All presentations must be delivered at the event venue.
                            </p>
                        </div>
                        <div class="alert alert-info">
                            <h6 class="alert-heading">
                                <i class="fas fa-credit-card me-2"></i>Payment Verification
                            </h6>
                            <p class="mb-0 small">
                                Payment verification by admin may take 1-2 business days. 
                                You'll be notified once verified.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (session('success')): ?>
            Swal.fire({ icon: 'success', title: 'Success!', text: '<?= addslashes(session('success')) ?>', timer: 3000, showConfirmButton: false });
            <?php endif; ?>
            <?php if (session('error')): ?>
            Swal.fire({ icon: 'error', title: 'Error!', text: '<?= addslashes(session('error')) ?>' });
            <?php endif; ?>
            <?php if (session('info')): ?>
            Swal.fire({ icon: 'info', title: 'Information', text: '<?= addslashes(session('info')) ?>' });
            <?php endif; ?>
        });
    </script>
</body>
</html>