<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran - SNIA Presenter</title>
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
            --purple-color: #8b5cf6;
        }

        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }

        .sidebar {
            background: linear-gradient(180deg, var(--purple-color) 0%, #7c3aed 100%);
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

        .header-section {
            background: linear-gradient(135deg, var(--purple-color) 0%, #7c3aed 100%);
            color: white;
            padding: 30px;
            border-radius: 16px;
            margin-bottom: 30px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            border-left: 4px solid var(--primary-color);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.12);
        }

        .stat-card.success { border-left-color: var(--success-color); }
        .stat-card.warning { border-left-color: var(--warning-color); }
        .stat-card.info { border-left-color: var(--info-color); }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 8px;
            color: #1e293b;
        }

        .stat-label {
            color: #64748b;
            font-size: 14px;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-bottom: 16px;
        }

        .stat-icon.primary { background: #dbeafe; color: var(--primary-color); }
        .stat-icon.success { background: #dcfce7; color: var(--success-color); }
        .stat-icon.warning { background: #fef3c7; color: var(--warning-color); }
        .stat-icon.info { background: #e0f2fe; color: var(--info-color); }

        .filter-tabs {
            background: white;
            border-radius: 12px;
            padding: 8px;
            margin-bottom: 24px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.05);
        }

        .filter-tab {
            padding: 12px 24px;
            border-radius: 8px;
            border: none;
            background: transparent;
            color: #64748b;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .filter-tab.active {
            background: var(--primary-color);
            color: white;
        }

        .filter-tab:hover:not(.active) {
            background: #f1f5f9;
        }

        .payment-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            margin-bottom: 24px;
            overflow: hidden;
            transition: all 0.3s ease;
            position: relative;
        }

        .payment-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }

        .payment-card.pending::before { background: var(--warning-color); }
        .payment-card.verified::before { background: var(--success-color); }
        .payment-card.rejected::before { background: var(--danger-color); }
        .payment-card.cancelled::before { background: var(--secondary-color); }

        .payment-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.15);
        }

        .payment-header {
            padding: 24px 24px 0 24px;
        }

        .payment-body {
            padding: 24px;
        }

        .payment-status {
            position: absolute;
            top: 16px;
            right: 16px;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending { background: #fef3c7; color: #92400e; }
        .status-verified { background: #d1fae5; color: #065f46; }
        .status-rejected { background: #fee2e2; color: #991b1b; }
        .status-cancelled { background: #f1f5f9; color: #475569; }

        .amount-display {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin: 16px 0;
        }

        .amount-final {
            font-size: 1.8rem;
            font-weight: 700;
            color: var(--success-color);
            margin-bottom: 4px;
        }

        .amount-original {
            font-size: 1rem;
            color: #6b7280;
            text-decoration: line-through;
        }

        .amount-savings {
            font-size: 0.9rem;
            color: var(--success-color);
            font-weight: 600;
        }

        .payment-method {
            background: #f8fafc;
            border-radius: 12px;
            padding: 16px;
            margin: 16px 0;
            display: flex;
            align-items: center;
        }

        .method-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            margin-right: 16px;
            flex-shrink: 0;
        }

        .method-icon.transfer_bank { background: #dbeafe; color: var(--primary-color); }
        .method-icon.gopay { background: #dcfce7; color: var(--success-color); }
        .method-icon.ovo { background: #fef3c7; color: var(--warning-color); }
        .method-icon.dana { background: #e0f2fe; color: #0ea5e9; }
        .method-icon.shopeepay { background: #fed7e2; color: #e53e3e; }

        .timeline {
            position: relative;
            padding-left: 30px;
            margin-top: 20px;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e2e8f0;
        }

        .timeline-item {
            position: relative;
            padding-bottom: 20px;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -22px;
            top: 4px;
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #e2e8f0;
        }

        .timeline-item.completed::before {
            background: var(--success-color);
        }

        .timeline-item.active::before {
            background: var(--warning-color);
        }

        .timeline-item.rejected::before {
            background: var(--danger-color);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #64748b;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .alert-custom {
            border-radius: 12px;
            padding: 16px;
            margin: 16px 0;
            border: none;
        }

        .alert-custom.info {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e40af;
        }

        .alert-custom.warning {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
        }

        .btn-action {
            border-radius: 10px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-action:hover {
            transform: translateY(-1px);
        }

        .voucher-badge {
            background: linear-gradient(135deg, #f3e8ff 0%, #e9d5ff 100%);
            color: var(--purple-color);
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .breadcrumb-custom {
            background: none;
            padding: 0;
            margin-bottom: 20px;
        }

        .breadcrumb-custom .breadcrumb-item {
            color: #64748b;
        }

        .breadcrumb-custom .breadcrumb-item.active {
            color: var(--primary-color);
            font-weight: 600;
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
                            <i class="fas fa-chalkboard-teacher me-2"></i>
                            SNIA Presenter
                        </h4>
                        <small class="text-white-50">Payment Management</small>
                    </div>
                    
                    <nav class="nav flex-column px-3">
                        <a class="nav-link" href="<?= site_url('presenter/dashboard') ?>">
                            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                        </a>
                        <a class="nav-link" href="<?= site_url('presenter/events') ?>">
                            <i class="fas fa-calendar-alt me-2"></i> Event
                        </a>
                        <a class="nav-link" href="<?= site_url('presenter/abstrak') ?>">
                            <i class="fas fa-file-alt me-2"></i> Abstrak
                        </a>
                        <a class="nav-link active" href="<?= site_url('presenter/pembayaran') ?>">
                            <i class="fas fa-credit-card me-2"></i> Pembayaran
                        </a>
                        <a class="nav-link" href="<?= site_url('presenter/absensi') ?>">
                            <i class="fas fa-qrcode me-2"></i> Absensi
                        </a>
                        <a class="nav-link" href="<?= site_url('presenter/dokumen/loa') ?>">
                            <i class="fas fa-certificate me-2"></i> Dokumen
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
                    <!-- Breadcrumb -->
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb breadcrumb-custom">
                            <li class="breadcrumb-item">
                                <a href="<?= site_url('presenter/dashboard') ?>">Dashboard</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">
                                Pembayaran
                            </li>
                        </ol>
                    </nav>

                    <!-- Header -->
                    <div class="header-section">
                        <div class="row align-items-center">
                            <div class="col">
                                <h1 class="mb-2">
                                    <i class="fas fa-credit-card me-3"></i>
                                    Riwayat Pembayaran
                                </h1>
                                <p class="mb-0 opacity-75">
                                    Kelola pembayaran registrasi event dan pantau status verifikasi
                                </p>
                            </div>
                            <div class="col-auto">
                                <div class="text-end">
                                    <div class="d-flex align-items-center text-white-50">
                                        <i class="fas fa-shield-alt me-2"></i>
                                        <span>Secure Payment</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Flash Messages -->
                    <?php if (session('success')): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i>
                        <?= session('success') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <?php if (session('error')): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?= session('error') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <!-- Statistics -->
                    <div class="stats-grid">
                        <div class="stat-card primary">
                            <div class="stat-icon primary">
                                <i class="fas fa-receipt"></i>
                            </div>
                            <div class="stat-number"><?= $stats['total_payments'] ?? 0 ?></div>
                            <div class="stat-label">Total Pembayaran</div>
                        </div>

                        <div class="stat-card success">
                            <div class="stat-icon success">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-number"><?= $stats['verified_payments'] ?? 0 ?></div>
                            <div class="stat-label">Terverifikasi</div>
                        </div>

                        <div class="stat-card warning">
                            <div class="stat-icon warning">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-number"><?= $stats['pending_payments'] ?? 0 ?></div>
                            <div class="stat-label">Menunggu Verifikasi</div>
                        </div>

                        <div class="stat-card info">
                            <div class="stat-icon info">
                                <i class="fas fa-money-bill-wave"></i>
                            </div>
                            <div class="stat-number">Rp <?= number_format($stats['total_amount_paid'] ?? 0, 0, ',', '.') ?></div>
                            <div class="stat-label">Total Dibayar</div>
                        </div>
                    </div>

                    <!-- Pending Actions Alert -->
                    <?php if (!empty($stats['rejected_payments'])): ?>
                    <div class="alert-custom warning">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-exclamation-triangle me-3 fs-4"></i>
                            <div>
                                <h6 class="mb-1">Perhatian! Ada Pembayaran yang Ditolak</h6>
                                <p class="mb-0">Anda memiliki <?= $stats['rejected_payments'] ?> pembayaran yang ditolak dan perlu dilakukan ulang.</p>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Filter Tabs -->
                    <div class="filter-tabs">
                        <div class="d-flex flex-wrap gap-2">
                            <button class="filter-tab active" onclick="filterPayments('all', this)">
                                <i class="fas fa-list me-2"></i>Semua Pembayaran
                            </button>
                            <button class="filter-tab" onclick="filterPayments('verified', this)">
                                <i class="fas fa-check me-2"></i>Terverifikasi
                            </button>
                            <button class="filter-tab" onclick="filterPayments('pending', this)">
                                <i class="fas fa-clock me-2"></i>Menunggu
                            </button>
                            <button class="filter-tab" onclick="filterPayments('rejected', this)">
                                <i class="fas fa-times me-2"></i>Ditolak
                            </button>
                        </div>
                    </div>

                    <!-- Payments List -->
                    <div id="paymentsContainer">
                        <?php if (!empty($payments)): ?>
                            <?php foreach ($payments as $payment): ?>
                            <div class="payment-card <?= $payment['status'] ?>" data-status="<?= $payment['status'] ?>">
                                <!-- Status Badge -->
                                <div class="payment-status status-<?= $payment['status'] ?>">
                                    <?php
                                    $statusLabels = [
                                        'pending' => 'Menunggu',
                                        'verified' => 'Terverifikasi',
                                        'rejected' => 'Ditolak',
                                        'cancelled' => 'Dibatalkan'
                                    ];
                                    echo $statusLabels[$payment['status']] ?? ucfirst($payment['status']);
                                    ?>
                                </div>

                                <div class="payment-header">
                                    <div class="row align-items-start">
                                        <div class="col-md-8">
                                            <h5 class="mb-2"><?= esc($payment['event_title'] ?? 'Event Title') ?></h5>
                                            <div class="text-muted mb-2">
                                                <i class="fas fa-calendar me-2"></i>
                                                Event: <?= date('d F Y', strtotime($payment['event_date'] ?? $payment['tanggal_bayar'])) ?>
                                                <span class="ms-3">
                                                    <i class="fas fa-clock me-2"></i>
                                                    Dibayar: <?= date('d M Y H:i', strtotime($payment['tanggal_bayar'])) ?>
                                                </span>
                                            </div>
                                            
                                            <!-- Voucher Info -->
                                            <?php if (!empty($payment['kode_voucher'])): ?>
                                            <div class="voucher-badge">
                                                <i class="fas fa-ticket-alt me-1"></i>
                                                Voucher: <?= esc($payment['kode_voucher']) ?>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="col-md-4">
                                            <div class="amount-display">
                                                <?php if (!empty($payment['original_amount']) && $payment['original_amount'] > $payment['jumlah']): ?>
                                                    <div class="amount-original">
                                                        Rp <?= number_format($payment['original_amount'], 0, ',', '.') ?>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <div class="amount-final">
                                                    Rp <?= number_format($payment['jumlah'], 0, ',', '.') ?>
                                                </div>
                                                
                                                <?php if (!empty($payment['discount_amount']) && $payment['discount_amount'] > 0): ?>
                                                    <div class="amount-savings">
                                                        Hemat Rp <?= number_format($payment['discount_amount'], 0, ',', '.') ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="payment-body">
                                    <div class="row">
                                        <div class="col-md-8">
                                            <!-- Payment Method -->
                                            <div class="payment-method">
                                                <div class="method-icon <?= $payment['metode'] ?>">
                                                    <i class="fas fa-<?= $payment['metode'] === 'transfer_bank' ? 'university' : ($payment['metode'] === 'qris' ? 'qrcode' : 'wallet') ?>"></i>
                                                </div>
                                                <div>
                                                    <strong><?= ucfirst(str_replace('_', ' ', $payment['metode'])) ?></strong>
                                                    <br>
                                                    <small class="text-muted">Mode Partisipasi: Offline</small>
                                                </div>
                                            </div>

                                            <!-- Payment Timeline -->
                                            <div class="timeline">
                                                <div class="timeline-item completed">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <strong>Pembayaran Dibuat</strong>
                                                            <br>
                                                            <small class="text-muted">Bukti pembayaran diupload</small>
                                                        </div>
                                                        <small class="text-muted">
                                                            <?= date('d M Y H:i', strtotime($payment['tanggal_bayar'])) ?>
                                                        </small>
                                                    </div>
                                                </div>
                                                
                                                <?php if ($payment['status'] === 'verified'): ?>
                                                <div class="timeline-item completed">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <strong>Pembayaran Diverifikasi</strong>
                                                            <br>
                                                            <small class="text-muted">
                                                                <?= !empty($payment['verifier_name']) ? 'Oleh: ' . $payment['verifier_name'] : 'Oleh Admin' ?>
                                                            </small>
                                                        </div>
                                                        <small class="text-muted">
                                                            <?= !empty($payment['verified_at']) ? date('d M Y H:i', strtotime($payment['verified_at'])) : '-' ?>
                                                        </small>
                                                    </div>
                                                </div>
                                                <?php elseif ($payment['status'] === 'pending'): ?>
                                                <div class="timeline-item active">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <strong>Menunggu Verifikasi Admin</strong>
                                                            <br>
                                                            <small class="text-muted">Mohon tunggu proses verifikasi</small>
                                                        </div>
                                                        <small class="text-muted">Dalam proses</small>
                                                    </div>
                                                </div>
                                                <?php elseif ($payment['status'] === 'rejected'): ?>
                                                <div class="timeline-item rejected">
                                                    <div class="d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <strong>Pembayaran Ditolak</strong>
                                                            <br>
                                                            <small class="text-muted">
                                                                <?= !empty($payment['keterangan']) ? $payment['keterangan'] : 'Silakan hubungi admin untuk info lebih lanjut' ?>
                                                            </small>
                                                        </div>
                                                        <small class="text-muted">
                                                            <?= !empty($payment['verified_at']) ? date('d M Y H:i', strtotime($payment['verified_at'])) : '-' ?>
                                                        </small>
                                                    </div>
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="col-md-4">
                                            <div class="d-grid gap-2">
                                                <a href="<?= site_url('presenter/pembayaran/detail/' . $payment['id_pembayaran']) ?>" 
                                                   class="btn btn-outline-primary btn-action">
                                                    <i class="fas fa-eye me-2"></i>Lihat Detail
                                                </a>
                                                
                                                <?php if (!empty($payment['bukti_bayar'])): ?>
                                                <a href="<?= site_url('presenter/pembayaran/download-bukti/' . $payment['id_pembayaran']) ?>" 
                                                   class="btn btn-outline-success btn-action">
                                                    <i class="fas fa-download me-2"></i>Download Bukti
                                                </a>
                                                <?php endif; ?>

                                                <?php if ($payment['status'] === 'pending'): ?>
                                                <button type="button" class="btn btn-outline-danger btn-action" 
                                                        onclick="cancelPayment(<?= $payment['id_pembayaran'] ?>)">
                                                    <i class="fas fa-times me-2"></i>Batalkan
                                                </button>
                                                <?php elseif ($payment['status'] === 'rejected'): ?>
                                                <a href="<?= site_url('presenter/pembayaran/create/' . $payment['event_id']) ?>" 
                                                   class="btn btn-warning btn-action">
                                                    <i class="fas fa-redo me-2"></i>Bayar Ulang
                                                </a>
                                                <?php elseif ($payment['status'] === 'verified'): ?>
                                                <a href="<?= site_url('presenter/events/detail/' . $payment['event_id']) ?>" 
                                                   class="btn btn-success btn-action">
                                                    <i class="fas fa-calendar me-2"></i>Lihat Event
                                                </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                        <div class="empty-state">
                            <i class="fas fa-credit-card"></i>
                            <h5 class="text-muted">Belum Ada Pembayaran</h5>
                            <p class="text-muted">
                                Pembayaran akan muncul di sini setelah Anda mendaftar event dan melakukan pembayaran
                            </p>
                            <a href="<?= site_url('presenter/events') ?>" class="btn btn-primary btn-action">
                                <i class="fas fa-calendar-alt me-2"></i>Lihat Event Tersedia
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Cancel Payment Modal -->
    <div class="modal fade" id="cancelModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle text-warning me-2"></i>
                        Konfirmasi Pembatalan
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3">Apakah Anda yakin ingin membatalkan pembayaran ini?</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Perhatian:</strong> Pembatalan tidak dapat dibatalkan. Anda harus melakukan pembayaran ulang untuk event ini.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-arrow-left me-2"></i>Batal
                    </button>
                    <form id="cancelForm" method="post" style="display: inline;">
                        <button type="submit" class="btn btn-danger">
                            <i class="fas fa-times me-2"></i>Ya, Batalkan Pembayaran
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container position-fixed top-0 end-0 p-3" id="toastContainer"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-hide alerts after 5 seconds
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert-dismissible');
                alerts.forEach(alert => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 5000);

            // Auto refresh pending payments status every 60 seconds
            setInterval(() => {
                const pendingCards = document.querySelectorAll('.payment-card.pending');
                if (pendingCards.length > 0) {
                    console.log('Refreshing payment status...');
                    // Optional: Add subtle refresh indicator
                }
            }, 60000);
        });

        function filterPayments(status, button) {
            // Update active tab
            document.querySelectorAll('.filter-tab').forEach(tab => {
                tab.classList.remove('active');
            });
            button.classList.add('active');

            // Filter payment cards
            const payments = document.querySelectorAll('.payment-card');
            let visibleCount = 0;

            payments.forEach(paymentCard => {
                const cardStatus = paymentCard.getAttribute('data-status');
                
                if (status === 'all' || cardStatus === status) {
                    paymentCard.style.display = 'block';
                    visibleCount++;
                } else {
                    paymentCard.style.display = 'none';
                }
            });

            // Show empty state if no payments visible
            const container = document.getElementById('paymentsContainer');
            let emptyState = container.querySelector('.empty-state-filter');
            
            if (visibleCount === 0 && status !== 'all') {
                if (!emptyState) {
                    emptyState = document.createElement('div');
                    emptyState.className = 'empty-state empty-state-filter';
                    emptyState.innerHTML = `
                        <i class="fas fa-filter"></i>
                        <h5 class="text-muted">Tidak Ada Pembayaran</h5>
                        <p class="text-muted">Tidak ada pembayaran dengan status "${getStatusLabel(status)}"</p>
                    `;
                    container.appendChild(emptyState);
                }
                emptyState.style.display = 'block';
            } else if (emptyState) {
                emptyState.style.display = 'none';
            }
        }

        function getStatusLabel(status) {
            const labels = {
                'verified': 'Terverifikasi',
                'pending': 'Menunggu',
                'rejected': 'Ditolak',
                'cancelled': 'Dibatalkan'
            };
            return labels[status] || status;
        }

        function cancelPayment(paymentId) {
            const form = document.getElementById('cancelForm');
            form.action = '<?= site_url('presenter/pembayaran/cancel/') ?>' + paymentId;
            
            const modal = new bootstrap.Modal(document.getElementById('cancelModal'));
            modal.show();
        }

        function showToast(title, message, type) {
            const toastHtml = `
                <div class="toast align-items-center text-white bg-${type} border-0" role="alert">
                    <div class="d-flex">
                        <div class="toast-body">
                            <strong>${title}</strong> ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `;
            
            const toastContainer = document.getElementById('toastContainer');
            toastContainer.insertAdjacentHTML('beforeend', toastHtml);
            
            const toastElement = toastContainer.querySelector('.toast:last-child');
            const toast = new bootstrap.Toast(toastElement, { autohide: true, delay: 5000 });
            toast.show();

            toastElement.addEventListener('hidden.bs.toast', function() {
                toastElement.remove();
            });
        }

        // Smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });
    </script>
</body>
</html>