<?php
// app/Views/role/presenter/event/detail.php
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Details - <?= esc($event['title']) ?></title>
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

        .detail-container {
            max-width: 1000px;
            margin: 0 auto;
        }

        .event-hero {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--info-color) 100%);
            color: white;
            border-radius: 20px;
            padding: 40px;
            margin-bottom: 30px;
            box-shadow: 0 15px 35px rgba(37, 99, 235, 0.3);
            position: relative;
            overflow: hidden;
        }

        .event-hero::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100%;
            height: 200%;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.1"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
            opacity: 0.3;
        }

        .event-hero .content {
            position: relative;
            z-index: 2;
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
            background: linear-gradient(135deg, rgba(37, 99, 235, 0.1) 0%, rgba(6, 182, 212, 0.1) 100%);
            padding: 24px;
            border-bottom: 1px solid #e2e8f0;
        }

        .event-meta {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .meta-item {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border-left: 4px solid var(--primary-color);
        }

        .meta-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }

        .meta-item i {
            font-size: 1.5rem;
            margin-bottom: 10px;
            display: block;
        }

        .meta-item h6 {
            color: var(--secondary-color);
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 5px;
        }

        .meta-item .value {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--dark-color);
        }

        .price-display {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1) 0%, rgba(5, 150, 105, 0.1) 100%);
            border: 2px solid rgba(16, 185, 129, 0.2);
            border-radius: 16px;
            padding: 25px;
            text-align: center;
            margin: 20px 0;
        }

        .price-amount {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--success-color);
            margin-bottom: 10px;
        }

        .price-label {
            color: var(--secondary-color);
            font-size: 0.9rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        .status-badge {
            padding: 10px 20px;
            border-radius: 25px;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 15px;
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

        .offline-badge {
            background: rgba(245, 158, 11, 0.1);
            color: #d97706;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            display: inline-block;
        }

        .btn-custom {
            border-radius: 12px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
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

        .btn-outline-custom {
            background: transparent;
            border: 2px solid var(--primary-color);
            color: var(--primary-color);
        }

        .btn-outline-custom:hover {
            background: var(--primary-color);
            color: white;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }

        .info-section {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
        }

        .info-section h5 {
            color: var(--primary-color);
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .timeline {
            position: relative;
            padding-left: 30px;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 8px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(135deg, var(--primary-color), var(--info-color));
        }

        .timeline-item {
            position: relative;
            margin-bottom: 25px;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -26px;
            top: 5px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: var(--primary-color);
            border: 3px solid white;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3);
        }

        .timeline-item h6 {
            color: var(--dark-color);
            margin-bottom: 5px;
        }

        .timeline-item p {
            color: var(--secondary-color);
            font-size: 0.9rem;
            margin: 0;
        }

        .registration-info {
            background: rgba(6, 182, 212, 0.1);
            border: 1px solid rgba(6, 182, 212, 0.3);
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
        }

        .registration-info h6 {
            color: var(--info-color);
            margin-bottom: 10px;
        }

        .deadline-warning {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
        }

        .deadline-warning h6 {
            color: var(--danger-color);
            margin-bottom: 10px;
        }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
            margin: 20px 0;
        }

        .stat-item {
            text-align: center;
            padding: 15px;
            background: rgba(37, 99, 235, 0.05);
            border-radius: 10px;
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
            display: block;
        }

        .stat-label {
            font-size: 0.8rem;
            color: var(--secondary-color);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        @media (max-width: 768px) {
            .main-content {
                margin-left: 0;
                padding: 10px;
            }
            
            .sidebar {
                transform: translateX(-100%);
            }
            
            .event-hero {
                padding: 25px;
            }
            
            .event-meta {
                grid-template-columns: 1fr;
            }
            
            .info-grid {
                grid-template-columns: 1fr;
            }
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
        <div class="detail-container">
            <!-- Event Hero Section -->
            <div class="event-hero">
                <div class="content">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h1 class="mb-3"><?= esc($event['title']) ?></h1>
                            <p class="mb-4 opacity-90" style="font-size: 1.1rem;">
                                <?= esc($event['description'] ?? 'Detailed event information and registration details') ?>
                            </p>
                            
                            <!-- Registration Status -->
                            <?php
                            $registrationStatus = 'not_registered';
                            $userRegistration = null;
                            
                            if (!empty($event['user_registration'])) {
                                $userRegistration = $event['user_registration'];
                                $registrationStatus = $userRegistration['status'];
                            }
                            ?>
                            
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
                            
                            <div class="mt-3">
                                <span class="offline-badge">
                                    <i class="fas fa-map-marker-alt me-1"></i>Offline Participation Only
                                </span>
                            </div>
                        </div>
                        <div class="col-md-4 text-end">
                            <a href="<?= site_url('presenter/events') ?>" class="btn btn-outline-light btn-custom me-2">
                                <i class="fas fa-arrow-left"></i>Back to Events
                            </a>
                            <button class="btn btn-outline-light btn-custom" onclick="window.print()">
                                <i class="fas fa-print"></i>Print
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Event Meta Information -->
            <div class="event-meta">
                <div class="meta-item">
                    <i class="fas fa-calendar text-primary"></i>
                    <h6>Event Date</h6>
                    <div class="value"><?= date('d F Y', strtotime($event['event_date'])) ?></div>
                </div>
                
                <div class="meta-item">
                    <i class="fas fa-clock text-info"></i>
                    <h6>Event Time</h6>
                    <div class="value"><?= date('H:i', strtotime($event['event_time'])) ?> WIB</div>
                </div>
                
                <?php if ($event['location']): ?>
                    <div class="meta-item">
                        <i class="fas fa-map-marker-alt text-warning"></i>
                        <h6>Location</h6>
                        <div class="value"><?= esc($event['location']) ?></div>
                    </div>
                <?php endif; ?>
                
                <?php if ($event['registration_deadline']): ?>
                    <div class="meta-item">
                        <i class="fas fa-hourglass-end text-danger"></i>
                        <h6>Registration Deadline</h6>
                        <div class="value"><?= date('d M Y, H:i', strtotime($event['registration_deadline'])) ?></div>
                    </div>
                <?php endif; ?>
            </div>

            <!-- Price Information -->
            <div class="content-card">
                <div class="content-header">
                    <h4 class="mb-0">
                        <i class="fas fa-money-bill-wave me-2"></i>
                        Presenter Fee Information
                    </h4>
                </div>
                <div class="p-4">
                    <div class="row align-items-center">
                        <div class="col-md-6">
                            <div class="price-display">
                                <div class="price-amount">
                                    Rp <?= number_format($event['presenter_fee_offline'] ?? 0, 0, ',', '.') ?>
                                </div>
                                <div class="price-label">Offline Presenter Fee</div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="alert alert-info">
                                <h6 class="alert-heading">
                                    <i class="fas fa-info-circle me-2"></i>Fee Information
                                </h6>
                                <ul class="mb-0 small">
                                    <li>Fee is for offline participation only</li>
                                    <li>Includes presentation slot and materials</li>
                                    <li>Payment verification required within 24 hours</li>
                                    <li>Non-refundable after approval</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Action Buttons -->
            <div class="content-card">
                <div class="p-4">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <?php if ($userRegistration): ?>
                                <div class="registration-info">
                                    <h6>
                                        <i class="fas fa-info-circle me-2"></i>Your Registration Information
                                    </h6>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <strong>Payment Amount:</strong><br>
                                            Rp <?= number_format($userRegistration['jumlah'] ?? 0, 0, ',', '.') ?>
                                        </div>
                                        <div class="col-md-4">
                                            <strong>Submitted:</strong><br>
                                            <?= date('d M Y, H:i', strtotime($userRegistration['tanggal_bayar'])) ?>
                                        </div>
                                        <div class="col-md-4">
                                            <strong>Status:</strong><br>
                                            <span class="badge bg-<?= $registrationStatus === 'verified' ? 'success' : ($registrationStatus === 'pending' ? 'warning' : 'danger') ?>">
                                                <?= ucfirst($registrationStatus) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                            
                            <!-- Deadline Warning -->
                            <?php if ($event['registration_deadline']): ?>
                                <?php
                                $deadlineTime = strtotime($event['registration_deadline']);
                                $daysLeft = ceil(($deadlineTime - time()) / (60 * 60 * 24));
                                ?>
                                
                                <?php if ($daysLeft <= 3 && $daysLeft > 0): ?>
                                    <div class="deadline-warning">
                                        <h6>
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            Registration Deadline Approaching!
                                        </h6>
                                        <p class="mb-0">
                                            Registration closes in <strong><?= $daysLeft ?> day(s)</strong>!<br>
                                            Deadline: <?= date('d M Y, H:i', $deadlineTime) ?>
                                        </p>
                                    </div>
                                <?php elseif ($daysLeft <= 0): ?>
                                    <div class="deadline-warning">
                                        <h6>
                                            <i class="fas fa-times-circle me-2"></i>
                                            Registration Closed
                                        </h6>
                                        <p class="mb-0">
                                            Registration deadline has passed on <?= date('d M Y, H:i', $deadlineTime) ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-4 text-end">
                            <div class="d-grid gap-2">
                                <!-- Main Action Button -->
                                <?php if ($registrationStatus === 'not_registered'): ?>
                                    <?php if (isset($this->eventModel) && $this->eventModel->isRegistrationOpen($event['id'])): ?>
                                        <a href="<?= site_url('presenter/events/register/' . $event['id']) ?>" 
                                           class="btn btn-primary-custom btn-custom btn-lg">
                                            <i class="fas fa-user-plus"></i>Register Now
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-secondary btn-custom btn-lg" disabled>
                                            <i class="fas fa-lock"></i>Registration Closed
                                        </button>
                                    <?php endif; ?>
                                <?php elseif ($registrationStatus === 'verified'): ?>
                                    <a href="<?= site_url('presenter/abstrak') ?>?event=<?= $event['id'] ?>" 
                                       class="btn btn-success-custom btn-custom btn-lg">
                                        <i class="fas fa-file-upload"></i>Submit Abstract
                                    </a>
                                <?php elseif ($registrationStatus === 'pending'): ?>
                                    <a href="<?= site_url('presenter/pembayaran') ?>" 
                                       class="btn btn-warning-custom btn-custom btn-lg">
                                        <i class="fas fa-clock"></i>View Payment Status
                                    </a>
                                <?php else: ?>
                                    <a href="<?= site_url('presenter/events/register/' . $event['id']) ?>" 
                                       class="btn btn-primary-custom btn-custom btn-lg">
                                        <i class="fas fa-redo"></i>Register Again
                                    </a>
                                <?php endif; ?>
                                
                                <!-- Secondary Actions -->
                                <a href="<?= site_url('presenter/pembayaran') ?>" 
                                   class="btn btn-outline-custom btn-custom">
                                    <i class="fas fa-credit-card"></i>Payment History
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Additional Information Grid -->
            <div class="info-grid">
                <!-- Event Statistics -->
                <?php if (isset($event['stats'])): ?>
                    <div class="info-section">
                        <h5>
                            <i class="fas fa-chart-bar"></i>Event Statistics
                        </h5>
                        <div class="stats-row">
                            <div class="stat-item">
                                <span class="stat-number"><?= $event['stats']['total_registrations'] ?? 0 ?></span>
                                <span class="stat-label">Registrations</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?= $event['stats']['total_abstracts'] ?? 0 ?></span>
                                <span class="stat-label">Abstracts</span>
                            </div>
                            <div class="stat-item">
                                <span class="stat-number"><?= $event['stats']['total_presenters'] ?? 0 ?></span>
                                <span class="stat-label">Presenters</span>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Event Timeline -->
                <div class="info-section">
                    <h5>
                        <i class="fas fa-timeline"></i>Event Timeline
                    </h5>
                    <div class="timeline">
                        <div class="timeline-item">
                            <h6>Registration Opens</h6>
                            <p><?= date('d M Y', strtotime($event['created_at'] ?? 'now')) ?></p>
                        </div>
                        <?php if ($event['registration_deadline']): ?>
                            <div class="timeline-item">
                                <h6>Registration Deadline</h6>
                                <p><?= date('d M Y, H:i', strtotime($event['registration_deadline'])) ?></p>
                            </div>
                        <?php endif; ?>
                        <div class="timeline-item">
                            <h6>Event Day</h6>
                            <p><?= date('d M Y, H:i', strtotime($event['event_date'] . ' ' . $event['event_time'])) ?></p>
                        </div>
                        <div class="timeline-item">
                            <h6>Certificate Distribution</h6>
                            <p>After event completion</p>
                        </div>
                    </div>
                </div>

                <!-- Requirements & Guidelines -->
                <div class="info-section">
                    <h5>
                        <i class="fas fa-clipboard-check"></i>Presenter Guidelines
                    </h5>
                    <div class="alert alert-warning">
                        <h6 class="alert-heading">
                            <i class="fas fa-exclamation-triangle me-2"></i>Important Requirements
                        </h6>
                        <ul class="mb-0 small">
                            <li><strong>Physical Attendance:</strong> Offline participation is mandatory</li>
                            <li><strong>Abstract Submission:</strong> Required after registration approval</li>
                            <li><strong>Presentation Duration:</strong> Typically 15-20 minutes + Q&A</li>
                            <li><strong>Technical Requirements:</strong> Prepare your own presentation materials</li>
                            <li><strong>Professional Conduct:</strong> Business casual dress code expected</li>
                        </ul>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="info-section">
                    <h5>
                        <i class="fas fa-headset"></i>Need Help?
                    </h5>
                    <div class="alert alert-info">
                        <h6 class="alert-heading">
                            <i class="fas fa-question-circle me-2"></i>Contact Support
                        </h6>
                        <p class="mb-0 small">
                            If you have questions about the event, registration process, or technical issues, 
                            please contact our support team:
                        </p>
                        <div class="mt-3">
                            <div class="row">
                                <div class="col-md-6">
                                    <strong>Email:</strong><br>
                                    <a href="mailto:support@snia.org" class="text-decoration-none">support@snia.org</a>
                                </div>
                                <div class="col-md-6">
                                    <strong>Phone:</strong><br>
                                    <a href="tel:+6221-123-4567" class="text-decoration-none">+62 21-123-4567</a>
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
        document.addEventListener('DOMContentLoaded', function() {
            // Show success/error messages
            <?php if (session('success')): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: '<?= addslashes(session('success')) ?>',
                    timer: 3000,
                    showConfirmButton: false
                });
            <?php endif; ?>

            <?php if (session('error')): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: '<?= addslashes(session('error')) ?>',
                });
            <?php endif; ?>

            <?php if (session('info')): ?>
                Swal.fire({
                    icon: 'info',
                    title: 'Information',
                    text: '<?= addslashes(session('info')) ?>',
                });
            <?php endif; ?>

            // Animate elements on scroll
            const observer = new IntersectionObserver((entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.style.opacity = '1';
                        entry.target.style.transform = 'translateY(0)';
                    }
                });
            });

            // Observe all cards
            document.querySelectorAll('.content-card, .info-section, .meta-item').forEach((el) => {
                el.style.opacity = '0';
                el.style.transform = 'translateY(20px)';
                el.style.transition = 'all 0.6s ease';
                observer.observe(el);
            });

            // Mobile sidebar toggle
            const toggleSidebar = () => {
                const sidebar = document.querySelector('.sidebar');
                sidebar.classList.toggle('show');
            };

            // Add mobile menu button if needed
            if (window.innerWidth <= 768) {
                const heroSection = document.querySelector('.event-hero .content .row');
                const mobileMenuBtn = document.createElement('button');
                mobileMenuBtn.className = 'btn btn-outline-light btn-custom d-md-none mb-3';
                mobileMenuBtn.innerHTML = '<i class="fas fa-bars me-2"></i>Menu';
                mobileMenuBtn.onclick = toggleSidebar;
                heroSection.insertBefore(mobileMenuBtn, heroSection.firstChild);
            }

            // Print functionality
            window.addEventListener('beforeprint', () => {
                document.body.classList.add('printing');
            });

            window.addEventListener('afterprint', () => {
                document.body.classList.remove('printing');
            });
        });

        // Auto-refresh registration status every 30 seconds if pending
        <?php if ($registrationStatus === 'pending'): ?>
            setInterval(() => {
                fetch('<?= site_url('presenter/events/check-status/' . $event['id']) ?>', {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.status !== 'pending') {
                        location.reload();
                    }
                })
                .catch(error => {
                    console.log('Status check failed:', error);
                });
            }, 30000);
        <?php endif; ?>

        // Countdown timer for registration deadline
        <?php if ($event['registration_deadline'] && isset($daysLeft) && $daysLeft > 0): ?>
            const countdownTimer = () => {
                const deadline = new Date('<?= date('Y-m-d H:i:s', strtotime($event['registration_deadline'])) ?>').getTime();
                const now = new Date().getTime();
                const timeLeft = deadline - now;

                if (timeLeft > 0) {
                    const days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
                    const hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
                    const minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
                    const seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);

                    const countdownElement = document.querySelector('.countdown-timer');
                    if (countdownElement) {
                        countdownElement.innerHTML = `${days}d ${hours}h ${minutes}m ${seconds}s`;
                    }
                } else {
                    // Deadline passed, reload page
                    location.reload();
                }
            };

            // Add countdown display to deadline warning
            const deadlineWarning = document.querySelector('.deadline-warning');
            if (deadlineWarning) {
                const countdownDiv = document.createElement('div');
                countdownDiv.className = 'mt-2';
                countdownDiv.innerHTML = '<small><strong>Time remaining: <span class="countdown-timer"></span></strong></small>';
                deadlineWarning.appendChild(countdownDiv);
                
                // Update countdown every second
                setInterval(countdownTimer, 1000);
                countdownTimer(); // Initial call
            }
        <?php endif; ?>
    </script>

    <style>
        @media print {
            .sidebar,
            .btn-custom,
            .alert {
                display: none !important;
            }
            
            .main-content {
                margin-left: 0 !important;
            }
            
            .event-hero {
                background: #f8f9fa !important;
                color: #000 !important;
                box-shadow: none !important;
            }
            
            .content-card {
                box-shadow: none !important;
                border: 1px solid #dee2e6 !important;
            }
        }

        .sidebar.show {
            transform: translateX(0) !important;
        }

        @media (max-width: 768px) {
            .sidebar.show {
                position: fixed;
                z-index: 1050;
            }
            
            .sidebar.show::before {
                content: '';
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background: rgba(0,0,0,0.5);
                z-index: -1;
            }
        }

        .countdown-timer {
            color: var(--danger-color);
            font-weight: 600;
        }
    </style>
</body>
</html>