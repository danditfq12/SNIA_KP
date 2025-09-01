<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Management - SNIA Presenter</title>
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

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
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
        }

        .stat-card.pending::before { background: var(--warning-color); }
        .stat-card.verified::before { background: var(--success-color); }
        .stat-card.rejected::before { background: var(--danger-color); }
        .stat-card.total::before { background: var(--info-color); }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }

        .payment-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            margin-bottom: 20px;
        }

        .payment-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        }

        .payment-header {
            padding: 16px 20px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .payment-body {
            padding: 20px;
        }

        .payment-footer {
            padding: 16px 20px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending {
            background: rgba(251, 191, 36, 0.1);
            color: #d97706;
            border: 1px solid rgba(251, 191, 36, 0.3);
        }

        .status-verified {
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .status-rejected {
            background: rgba(239, 68, 68, 0.1);
            color: #dc2626;
            border: 1px solid rgba(239, 68, 68, 0.3);
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

        .amount-display {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--success-color);
        }

        .event-info {
            background: #f8fafc;
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 16px;
        }

        .abstract-info {
            background: rgba(37, 99, 235, 0.05);
            border-left: 4px solid var(--primary-color);
            padding: 12px;
            margin-top: 12px;
            border-radius: 0 8px 8px 0;
        }

        .voucher-info {
            background: rgba(16, 185, 129, 0.05);
            border-left: 4px solid var(--success-color);
            padding: 12px;
            margin-top: 12px;
            border-radius: 0 8px 8px 0;
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

        .alert-custom {
            border-radius: 12px;
            border: none;
            padding: 16px 20px;
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
            <small class="text-white-50">Payment Management</small>
        </div>
        
        <nav class="nav flex-column px-3">
            <a class="nav-link" href="<?= site_url('presenter/dashboard') ?>">
                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
            </a>
            <a class="nav-link" href="<?= site_url('presenter/events') ?>">
                <i class="fas fa-calendar me-2"></i> Events
            </a>
            <a class="nav-link" href="<?= site_url('presenter/abstrak') ?>">
                <i class="fas fa-file-alt me-2"></i> My Abstracts
            </a>
            <a class="nav-link active" href="<?= site_url('presenter/pembayaran') ?>">
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
                            <i class="fas fa-credit-card me-3"></i>Payment Management
                        </h2>
                        <p class="mb-0 opacity-75">Manage your conference payments and track verification status</p>
                    </div>
                    <div class="col-auto">
                        <?php if (!empty($available_events)): ?>
                            <div class="dropdown">
                                <button class="btn btn-light btn-custom dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-plus me-2"></i>Make Payment
                                </button>
                                <ul class="dropdown-menu">
                                    <?php foreach ($available_events as $event): ?>
                                        <li>
                                            <a class="dropdown-item" href="<?= site_url('presenter/pembayaran/create/' . $event['event_id']) ?>">
                                                <strong><?= esc($event['event_title']) ?></strong><br>
                                                <small class="text-muted">Rp <?= number_format($event['presenter_fee_offline'] ?? 0, 0, ',', '.') ?></small>
                                            </a>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        <button class="btn btn-outline-light btn-custom ms-2" onclick="location.reload()">
                            <i class="fas fa-sync-alt me-2"></i>Refresh
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($accepted_abstracts)): ?>
            <!-- Statistics Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-3">
                    <div class="stat-card total">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="fas fa-receipt fa-2x text-info"></i>
                            </div>
                            <div>
                                <h3 class="mb-0"><?= $payment_stats['total_payments'] ?></h3>
                                <small class="text-muted">Total Payments</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card verified">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="fas fa-check-circle fa-2x text-success"></i>
                            </div>
                            <div>
                                <h3 class="mb-0"><?= $payment_stats['verified_payments'] ?></h3>
                                <small class="text-muted">Verified</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card pending">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="fas fa-clock fa-2x text-warning"></i>
                            </div>
                            <div>
                                <h3 class="mb-0"><?= $payment_stats['pending_payments'] ?></h3>
                                <small class="text-muted">Pending</small>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="stat-card rejected">
                        <div class="d-flex align-items-center">
                            <div class="me-3">
                                <i class="fas fa-money-bill-wave fa-2x text-success"></i>
                            </div>
                            <div>
                                <h3 class="mb-0">Rp <?= number_format($payment_stats['total_paid'], 0, ',', '.') ?></h3>
                                <small class="text-muted">Total Paid</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment History -->
            <div class="content-card">
                <div class="content-header">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2"></i>Payment History
                    </h5>
                </div>
                <div class="p-4">
                    <?php if (!empty($payments)): ?>
                        <?php foreach ($payments as $payment): ?>
                            <div class="payment-card">
                                <div class="payment-header">
                                    <div>
                                        <h6 class="mb-1">
                                            <i class="fas fa-calendar-alt me-2 text-primary"></i>
                                            <?= esc($payment['event_title'] ?? 'Event Payment') ?>
                                        </h6>
                                        <small class="text-muted">
                                            Payment ID: #<?= $payment['id_pembayaran'] ?>
                                        </small>
                                    </div>
                                    <div>
                                        <span class="status-badge status-<?= $payment['status'] ?>">
                                            <i class="fas fa-<?= $payment['status'] === 'verified' ? 'check-circle' : ($payment['status'] === 'pending' ? 'clock' : 'times-circle') ?> me-1"></i>
                                            <?= ucfirst($payment['status']) ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="payment-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="event-info">
                                                <h6 class="text-primary mb-2">
                                                    <i class="fas fa-info-circle me-2"></i>Event Details
                                                </h6>
                                                <?php if ($payment['event_date']): ?>
                                                    <div class="small mb-1">
                                                        <i class="fas fa-calendar me-1"></i>
                                                        <?= date('d F Y', strtotime($payment['event_date'])) ?>
                                                        <i class="fas fa-clock ms-2 me-1"></i>
                                                        <?= date('H:i', strtotime($payment['event_time'])) ?>
                                                    </div>
                                                <?php endif; ?>
                                                <?php if ($payment['location']): ?>
                                                    <div class="small">
                                                        <i class="fas fa-map-marker-alt me-1"></i>
                                                        <?= esc($payment['location']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        
                                        <div class="col-md-6">
                                            <div class="text-end">
                                                <div class="amount-display">
                                                    Rp <?= number_format($payment['jumlah'], 0, ',', '.') ?>
                                                </div>
                                                <div class="small text-muted">
                                                    <i class="fas fa-credit-card me-1"></i>
                                                    <?= ucfirst(str_replace('_', ' ', $payment['metode'])) ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="row mt-3">
                                        <div class="col-md-6">
                                            <small class="text-muted">Payment Date</small>
                                            <div class="fw-semibold">
                                                <?= date('d M Y, H:i', strtotime($payment['tanggal_bayar'])) ?>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <small class="text-muted">Participation Type</small>
                                            <div class="fw-semibold">
                                                <span class="badge bg-info">
                                                    <i class="fas fa-map-marker-alt me-1"></i>Offline Only
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if ($payment['kode_voucher']): ?>
                                        <div class="voucher-info">
                                            <h6 class="text-success mb-1">
                                                <i class="fas fa-ticket-alt me-2"></i>Voucher Applied
                                            </h6>
                                            <div class="small">
                                                Code: <strong><?= esc($payment['kode_voucher']) ?></strong>
                                                <?php if (isset($payment['voucher_type'])): ?>
                                                    - <?= $payment['voucher_type'] === 'percentage' ? $payment['voucher_value'] . '% discount' : 'Rp ' . number_format($payment['voucher_value'], 0, ',', '.') . ' discount' ?>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>

                                    <?php if ($payment['verified_at']): ?>
                                        <div class="mt-3 p-3 bg-success bg-opacity-10 rounded">
                                            <h6 class="text-success mb-1">
                                                <i class="fas fa-check-circle me-2"></i>Payment Verified
                                            </h6>
                                            <div class="small">
                                                Verified on: <?= date('d M Y, H:i', strtotime($payment['verified_at'])) ?>
                                                <?php if ($payment['verified_by_name']): ?>
                                                    by <?= esc($payment['verified_by_name']) ?>
                                                <?php endif; ?>
                                            </div>
                                            <?php if ($payment['keterangan']): ?>
                                                <div class="small mt-1">
                                                    <strong>Note:</strong> <?= esc($payment['keterangan']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php elseif ($payment['status'] === 'rejected'): ?>
                                        <div class="mt-3 p-3 bg-danger bg-opacity-10 rounded">
                                            <h6 class="text-danger mb-1">
                                                <i class="fas fa-times-circle me-2"></i>Payment Rejected
                                            </h6>
                                            <?php if ($payment['keterangan']): ?>
                                                <div class="small">
                                                    <strong>Reason:</strong> <?= esc($payment['keterangan']) ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <div class="mt-3 p-3 bg-warning bg-opacity-10 rounded">
                                            <h6 class="text-warning mb-1">
                                                <i class="fas fa-clock me-2"></i>Awaiting Verification
                                            </h6>
                                            <div class="small">
                                                Your payment is being reviewed by our admin team. You will be notified once verified.
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <div class="payment-footer">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <div>
                                            <a href="<?= site_url('presenter/pembayaran/detail/' . $payment['id_pembayaran']) ?>" 
                                               class="btn btn-outline-info btn-custom btn-sm">
                                                <i class="fas fa-eye me-1"></i>View Details
                                            </a>
                                            
                                            <?php if ($payment['bukti_bayar']): ?>
                                                <a href="<?= site_url('presenter/pembayaran/download-bukti/' . $payment['id_pembayaran']) ?>" 
                                                   class="btn btn-outline-secondary btn-custom btn-sm ms-2">
                                                    <i class="fas fa-download me-1"></i>Download Proof
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div>
                                            <?php if ($payment['status'] === 'verified'): ?>
                                                <span class="badge bg-success">
                                                    <i class="fas fa-unlock me-1"></i>Features Unlocked
                                                </span>
                                            <?php elseif ($payment['status'] === 'pending'): ?>
                                                <button class="btn btn-outline-danger btn-custom btn-sm" 
                                                        onclick="cancelPayment(<?= $payment['id_pembayaran'] ?>)">
                                                    <i class="fas fa-times me-1"></i>Cancel
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-credit-card"></i>
                            <h5>No Payments Yet</h5>
                            <p>You haven't made any payments yet. Once your abstract is accepted, you can make payment for the event.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

        <?php else: ?>
            <!-- No Accepted Abstracts -->
            <div class="content-card">
                <div class="p-5">
                    <div class="empty-state">
                        <i class="fas fa-file-times"></i>
                        <h4>No Accepted Abstracts</h4>
                        <p class="mb-4">You need to have at least one accepted abstract before you can make payments.</p>
                        
                        <div class="alert alert-custom alert-info">
                            <h6 class="alert-heading">
                                <i class="fas fa-info-circle me-2"></i>Next Steps:
                            </h6>
                            <ol class="mb-0 ps-3">
                                <li>Submit your abstract for review</li>
                                <li>Wait for acceptance from reviewers</li>
                                <li>Once accepted, payment option will become available</li>
                                <li>Complete payment to unlock presenter features</li>
                            </ol>
                        </div>
                        
                        <div class="mt-4">
                            <a href="<?= site_url('presenter/abstrak') ?>" class="btn btn-primary-custom btn-custom">
                                <i class="fas fa-file-upload me-2"></i>Manage Abstracts
                            </a>
                            <a href="<?= site_url('presenter/events') ?>" class="btn btn-outline-primary btn-custom ms-2">
                                <i class="fas fa-calendar me-2"></i>Browse Events
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Payment Process Info -->
        <div class="content-card">
            <div class="content-header">
                <h5 class="mb-0">
                    <i class="fas fa-question-circle me-2"></i>Payment Process Information
                </h5>
            </div>
            <div class="p-4">
                <div class="row">
                    <div class="col-md-6">
                        <h6 class="text-primary">
                            <i class="fas fa-map-marker-alt me-2"></i>Presenter Participation
                        </h6>
                        <p class="small mb-3">
                            As a presenter, you can only participate <strong>offline</strong>. All presentations must be delivered 
                            in-person at the venue. Payment is required after your abstract is accepted.
                        </p>
                        
                        <h6 class="text-success">
                            <i class="fas fa-unlock me-2"></i>Features Unlocked After Payment
                        </h6>
                        <ul class="small mb-0">
                            <li>QR Code attendance scanning</li>
                            <li>Letter of Acceptance (LOA) download</li>
                            <li>Certificate download (after event completion)</li>
                            <li>Full access to presenter dashboard</li>
                        </ul>
                    </div>
                    <div class="col-md-6">
                        <h6 class="text-warning">
                            <i class="fas fa-clock me-2"></i>Payment Timeline
                        </h6>
                        <div class="timeline-item">
                            <div class="small">
                                <strong>Step 1:</strong> Submit and get abstract accepted
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="small">
                                <strong>Step 2:</strong> Complete payment with proof
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="small">
                                <strong>Step 3:</strong> Wait for admin verification
                            </div>
                        </div>
                        <div class="timeline-item">
                            <div class="small">
                                <strong>Step 4:</strong> Access unlocked features
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cancel Payment Modal -->
    <div class="modal fade" id="cancelPaymentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-times-circle me-2"></i>Cancel Payment
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        <strong>Warning:</strong> This action cannot be undone. Your payment proof will be deleted 
                        and you'll need to upload it again if you want to make payment later.
                    </div>
                    <p>Are you sure you want to cancel this payment?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Keep Payment</button>
                    <button type="button" class="btn btn-danger" id="confirmCancelPayment">
                        <i class="fas fa-times me-2"></i>Yes, Cancel Payment
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        let paymentToCancel = null;

        function cancelPayment(paymentId) {
            paymentToCancel = paymentId;
            new bootstrap.Modal(document.getElementById('cancelPaymentModal')).show();
        }

        document.getElementById('confirmCancelPayment').addEventListener('click', function() {
            if (paymentToCancel) {
                window.location.href = `<?= site_url('presenter/pembayaran/cancel') ?>/${paymentToCancel}`;
            }
        });

        // Show success/error messages
        document.addEventListener('DOMContentLoaded', function() {
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

            // Animate cards on load
            const cards = document.querySelectorAll('.payment-card, .stat-card');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    card.style.transition = 'all 0.5s ease';
                    
                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 100);
                }, index * 100);
            });
        });
    </script>
</body>
</html>