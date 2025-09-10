<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pembayaran - SNIA Presenter</title>
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

        .detail-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            margin-bottom: 24px;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .detail-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.12);
        }

        .detail-header {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            padding: 20px 24px;
            border-bottom: 1px solid #e2e8f0;
        }

        .detail-body {
            padding: 24px;
        }

        .info-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: var(--secondary-color);
            min-width: 150px;
        }

        .info-value {
            flex: 1;
            text-align: right;
            font-weight: 500;
        }

        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-pending { background: #fef3c7; color: #92400e; }
        .status-verified { background: #d1fae5; color: #065f46; }
        .status-rejected { background: #fee2e2; color: #991b1b; }
        .status-cancelled { background: #f1f5f9; color: #475569; }

        .amount-display {
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
            border-radius: 16px;
            padding: 24px;
            text-align: center;
            margin: 20px 0;
        }

        .amount-final {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--success-color);
            margin-bottom: 8px;
        }

        .amount-original {
            font-size: 1.2rem;
            color: #6b7280;
            text-decoration: line-through;
            margin-bottom: 4px;
        }

        .amount-savings {
            font-size: 1rem;
            color: var(--success-color);
            font-weight: 600;
        }

        .payment-method-display {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            display: flex;
            align-items: center;
        }

        .method-icon {
            width: 60px;
            height: 60px;
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            margin-right: 20px;
            flex-shrink: 0;
        }

        .method-icon.transfer_bank { background: #dbeafe; color: var(--primary-color); }
        .method-icon.gopay { background: #dcfce7; color: var(--success-color); }
        .method-icon.ovo { background: #fef3c7; color: var(--warning-color); }
        .method-icon.dana { background: #e0f2fe; color: #0ea5e9; }
        .method-icon.shopeepay { background: #fed7e2; color: #e53e3e; }

        .bukti-container {
            background: #f8fafc;
            border-radius: 16px;
            padding: 30px;
            text-align: center;
            border: 2px dashed #e2e8f0;
            transition: all 0.3s ease;
        }

        .bukti-container:hover {
            border-color: var(--primary-color);
        }

        .bukti-preview {
            max-width: 100%;
            max-height: 400px;
            border-radius: 12px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.15);
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .bukti-preview:hover {
            transform: scale(1.05);
        }

        .no-bukti {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }

        .no-bukti i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .timeline {
            position: relative;
            padding-left: 30px;
        }

        .timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 3px;
            background: #e2e8f0;
            border-radius: 2px;
        }

        .timeline-item {
            position: relative;
            padding-bottom: 25px;
        }

        .timeline-item::before {
            content: '';
            position: absolute;
            left: -22px;
            top: 4px;
            width: 15px;
            height: 15px;
            border-radius: 50%;
            background: #e2e8f0;
            border: 3px solid white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
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

        .timeline-date {
            font-size: 12px;
            color: var(--secondary-color);
            margin-bottom: 5px;
            font-weight: 600;
        }

        .timeline-content {
            font-weight: 500;
            margin-bottom: 4px;
        }

        .timeline-description {
            font-size: 14px;
            color: var(--secondary-color);
        }

        .voucher-info {
            background: linear-gradient(135deg, var(--success-color), #059669);
            color: white;
            padding: 20px;
            border-radius: 12px;
            margin: 20px 0;
        }

        .btn-action {
            border-radius: 10px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
            margin-bottom: 10px;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .alert-custom {
            border: none;
            border-radius: 12px;
            padding: 16px;
            margin: 16px 0;
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

        .modal-content {
            border-radius: 16px;
            border: none;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .modal-header {
            background: linear-gradient(135deg, var(--purple-color), #7c3aed);
            color: white;
            border-radius: 16px 16px 0 0;
            border-bottom: none;
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
                        <small class="text-white-50">Payment Detail</small>
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
                            <li class="breadcrumb-item">
                                <a href="<?= site_url('presenter/pembayaran') ?>">Pembayaran</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">
                                Detail Pembayaran
                            </li>
                        </ol>
                    </nav>

                    <!-- Header -->
                    <div class="header-section">
                        <div class="row align-items-center">
                            <div class="col">
                                <h1 class="mb-2">
                                    <i class="fas fa-receipt me-3"></i>
                                    Detail Pembayaran
                                </h1>
                                <p class="mb-0 opacity-75">
                                    #PAY<?= str_pad($payment['id_pembayaran'], 3, '0', STR_PAD_LEFT) ?> - 
                                    <?= esc($event['title'] ?? 'Event Title') ?>
                                </p>
                            </div>
                            <div class="col-auto">
                                <a href="<?= site_url('presenter/pembayaran') ?>" class="btn btn-outline-light btn-action">
                                    <i class="fas fa-arrow-left me-2"></i>Kembali
                                </a>
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

                    <div class="row g-4">
                        <!-- Payment Information -->
                        <div class="col-lg-8">
                            <div class="detail-card">
                                <div class="detail-header">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">
                                            <i class="fas fa-money-bill-wave me-2"></i>Informasi Pembayaran
                                        </h5>
                                        <?php
                                        $statusClass = '';
                                        $statusText = '';
                                        switch($payment['status']) {
                                            case 'pending':
                                                $statusClass = 'status-pending';
                                                $statusText = 'Menunggu Verifikasi';
                                                break;
                                            case 'verified':
                                                $statusClass = 'status-verified';
                                                $statusText = 'Terverifikasi';
                                                break;
                                            case 'rejected':
                                                $statusClass = 'status-rejected';
                                                $statusText = 'Ditolak';
                                                break;
                                            case 'cancelled':
                                                $statusClass = 'status-cancelled';
                                                $statusText = 'Dibatalkan';
                                                break;
                                        }
                                        ?>
                                        <span class="status-badge <?= $statusClass ?>"><?= $statusText ?></span>
                                    </div>
                                </div>
                                <div class="detail-body">
                                    <!-- Amount Display -->
                                    <div class="amount-display">
                                        <?php if (isset($payment['original_amount']) && $payment['original_amount'] > $payment['jumlah']): ?>
                                            <div class="amount-original">
                                                Harga Asli: Rp <?= number_format($payment['original_amount'], 0, ',', '.') ?>
                                            </div>
                                            <div class="amount-savings">
                                                Hemat: Rp <?= number_format($payment['original_amount'] - $payment['jumlah'], 0, ',', '.') ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="amount-final">
                                            Rp <?= number_format($payment['jumlah'], 0, ',', '.') ?>
                                        </div>
                                        <small class="text-muted">Total Pembayaran</small>
                                    </div>

                                    <!-- Payment Details -->
                                    <div class="info-row">
                                        <span class="info-label">ID Pembayaran</span>
                                        <span class="info-value">#PAY<?= str_pad($payment['id_pembayaran'], 3, '0', STR_PAD_LEFT) ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Event</span>
                                        <span class="info-value"><?= esc($event['title'] ?? 'Event Title') ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Tanggal Event</span>
                                        <span class="info-value">
                                            <?= isset($event['event_date']) ? date('d F Y', strtotime($event['event_date'])) : '-' ?>
                                            <?= isset($event['event_time']) ? ' - ' . date('H:i', strtotime($event['event_time'])) : '' ?>
                                        </span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Lokasi</span>
                                        <span class="info-value"><?= esc($event['location'] ?? '-') ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Tanggal Pembayaran</span>
                                        <span class="info-value"><?= date('d F Y H:i', strtotime($payment['tanggal_bayar'])) ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Mode Partisipasi</span>
                                        <span class="info-value">
                                            <span class="badge bg-primary">Offline</span>
                                        </span>
                                    </div>
                                </div>
                            </div>

                            <!-- Payment Method -->
                            <div class="detail-card">
                                <div class="detail-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-wallet me-2"></i>Metode Pembayaran
                                    </h5>
                                </div>
                                <div class="detail-body">
                                    <div class="payment-method-display">
                                        <div class="method-icon <?= $payment['metode'] ?>">
                                            <i class="fas fa-<?= $payment['metode'] === 'transfer_bank' ? 'university' : ($payment['metode'] === 'qris' ? 'qrcode' : 'wallet') ?>"></i>
                                        </div>
                                        <div>
                                            <h6 class="mb-1"><?= ucfirst(str_replace('_', ' ', $payment['metode'])) ?></h6>
                                            <p class="text-muted mb-0">
                                                <?php
                                                $methodDesc = [
                                                    'transfer_bank' => 'Transfer melalui rekening bank',
                                                    'gopay' => 'Pembayaran via GoPay',
                                                    'ovo' => 'Pembayaran via OVO',
                                                    'dana' => 'Pembayaran via DANA',
                                                    'shopeepay' => 'Pembayaran via ShopeePay'
                                                ];
                                                echo $methodDesc[$payment['metode']] ?? 'Metode pembayaran digital';
                                                ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Voucher Information -->
                            <?php if ($voucher): ?>
                            <div class="voucher-info">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <h6 class="mb-1">
                                            <i class="fas fa-ticket-alt me-2"></i>Voucher Digunakan
                                        </h6>
                                        <div class="d-flex align-items-center">
                                            <strong class="me-3"><?= esc($voucher['kode_voucher']) ?></strong>
                                            <span class="badge bg-light text-dark">
                                                <?= $voucher['tipe'] == 'persentase' ? $voucher['nilai'] . '%' : 'Rp ' . number_format($voucher['nilai'], 0, ',', '.') ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <div class="text-end">
                                            <div><strong>Diskon Diperoleh</strong></div>
                                            <div class="fs-5">
                                                <?php
                                                if (isset($payment['discount_amount']) && $payment['discount_amount'] > 0) {
                                                    echo 'Rp ' . number_format($payment['discount_amount'], 0, ',', '.');
                                                } else if ($voucher['tipe'] == 'persentase') {
                                                    $discount = ($payment['jumlah'] * $voucher['nilai']) / (100 - $voucher['nilai']);
                                                    echo 'Rp ' . number_format($discount, 0, ',', '.');
                                                } else {
                                                    echo 'Rp ' . number_format($voucher['nilai'], 0, ',', '.');
                                                }
                                                ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Bukti Pembayaran -->
                            <div class="detail-card">
                                <div class="detail-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-image me-2"></i>Bukti Pembayaran
                                    </h5>
                                </div>
                                <div class="detail-body">
                                    <div class="bukti-container">
                                        <?php if (!empty($payment['bukti_bayar'])): ?>
                                            <?php
                                            $bukti_path = base_url('uploads/pembayaran/' . $payment['bukti_bayar']);
                                            $file_path = WRITEPATH . 'uploads/pembayaran/' . $payment['bukti_bayar'];
                                            ?>
                                            <?php if (file_exists($file_path)): ?>
                                                <img src="<?= $bukti_path ?>" 
                                                     class="bukti-preview" 
                                                     alt="Bukti Pembayaran"
                                                     onclick="viewFullImage('<?= $bukti_path ?>')">
                                                <div class="mt-3">
                                                    <p class="text-muted mb-2">
                                                        <i class="fas fa-file me-2"></i>
                                                        <?= esc($payment['bukti_bayar']) ?>
                                                    </p>
                                                    <p class="text-muted mb-3">
                                                        <i class="fas fa-calendar me-2"></i>
                                                        Diupload: <?= date('d M Y H:i', strtotime($payment['tanggal_bayar'])) ?>
                                                    </p>
                                                    <div class="d-flex gap-2 justify-content-center">
                                                        <button class="btn btn-outline-primary btn-sm" onclick="viewFullImage('<?= $bukti_path ?>')">
                                                            <i class="fas fa-search-plus me-1"></i>Perbesar
                                                        </button>
                                                        <a href="<?= site_url('presenter/pembayaran/downloadBukti/' . $payment['id_pembayaran']) ?>" 
                                                           class="btn btn-outline-success btn-sm">
                                                            <i class="fas fa-download me-1"></i>Download
                                                        </a>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <div class="no-bukti">
                                                    <i class="fas fa-exclamation-triangle text-warning"></i>
                                                    <h5>File Tidak Ditemukan</h5>
                                                    <p class="text-muted">File bukti pembayaran tidak dapat ditemukan di server.</p>
                                                </div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <div class="no-bukti">
                                                <i class="fas fa-image"></i>
                                                <h5>Bukti Pembayaran Tidak Tersedia</h5>
                                                <p class="text-muted">Belum ada bukti pembayaran yang diupload untuk transaksi ini.</p>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Timeline & Actions -->
                        <div class="col-lg-4">
                            <!-- Timeline -->
                            <div class="detail-card">
                                <div class="detail-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-history me-2"></i>Timeline Pembayaran
                                    </h5>
                                </div>
                                <div class="detail-body">
                                    <div class="timeline">
                                        <div class="timeline-item completed">
                                            <div class="timeline-date"><?= date('d M Y, H:i', strtotime($payment['tanggal_bayar'])) ?></div>
                                            <div class="timeline-content">Pembayaran Dibuat</div>
                                            <div class="timeline-description">Pembayaran berhasil disubmit</div>
                                        </div>
                                        
                                        <?php if (!empty($payment['bukti_bayar'])): ?>
                                        <div class="timeline-item completed">
                                            <div class="timeline-date"><?= date('d M Y, H:i', strtotime($payment['tanggal_bayar'])) ?></div>
                                            <div class="timeline-content">Bukti Diupload</div>
                                            <div class="timeline-description">Bukti pembayaran berhasil diunggah</div>
                                        </div>
                                        <?php endif; ?>
                                        
                                        <?php if ($payment['status'] == 'verified'): ?>
                                        <div class="timeline-item completed">
                                            <div class="timeline-date">
                                                <?= !empty($payment['verified_at']) ? date('d M Y, H:i', strtotime($payment['verified_at'])) : '-' ?>
                                            </div>
                                            <div class="timeline-content">Pembayaran Diverifikasi</div>
                                            <div class="timeline-description">
                                                <?= !empty($payment['verifier_name']) ? 'Oleh: ' . $payment['verifier_name'] : 'Diverifikasi oleh admin' ?>
                                            </div>
                                        </div>
                                        <?php elseif ($payment['status'] == 'rejected'): ?>
                                        <div class="timeline-item rejected">
                                            <div class="timeline-date">
                                                <?= !empty($payment['verified_at']) ? date('d M Y, H:i', strtotime($payment['verified_at'])) : '-' ?>
                                            </div>
                                            <div class="timeline-content">Pembayaran Ditolak</div>
                                            <div class="timeline-description">
                                                <?= !empty($payment['keterangan']) ? $payment['keterangan'] : 'Silakan hubungi admin untuk informasi lebih lanjut' ?>
                                            </div>
                                        </div>
                                        <?php elseif ($payment['status'] == 'cancelled'): ?>
                                        <div class="timeline-item rejected">
                                            <div class="timeline-date">
                                                <?= !empty($payment['updated_at']) ? date('d M Y, H:i', strtotime($payment['updated_at'])) : date('d M Y, H:i', strtotime($payment['tanggal_bayar'])) ?>
                                            </div>
                                            <div class="timeline-content">Pembayaran Dibatalkan</div>
                                            <div class="timeline-description">
                                                <?= !empty($payment['keterangan']) ? $payment['keterangan'] : 'Pembayaran dibatalkan oleh user' ?>
                                            </div>
                                        </div>
                                        <?php else: ?>
                                        <div class="timeline-item active">
                                            <div class="timeline-date">Sedang diproses</div>
                                            <div class="timeline-content">Menunggu Verifikasi Admin</div>
                                            <div class="timeline-description">Pembayaran sedang dalam proses verifikasi</div>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div class="detail-card">
                                <div class="detail-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-cog me-2"></i>Aksi
                                    </h5>
                                </div>
                                <div class="detail-body">
                                    <div class="d-grid gap-2">
                                        <?php if ($payment['status'] == 'pending'): ?>
                                            <?php if ($can_cancel ?? true): ?>
                                            <button type="button" class="btn btn-outline-danger btn-action" 
                                                    onclick="cancelPayment(<?= $payment['id_pembayaran'] ?>)">
                                                <i class="fas fa-times me-2"></i>Batalkan Pembayaran
                                            </button>
                                            <?php endif; ?>
                                            
                                            <div class="alert alert-custom" style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); color: #92400e;">
                                                <i class="fas fa-info-circle me-2"></i>
                                                <strong>Menunggu Verifikasi</strong><br>
                                                Pembayaran Anda sedang diverifikasi oleh admin. Proses ini biasanya memakan waktu 1-24 jam.
                                            </div>

                                        <?php elseif ($payment['status'] == 'verified'): ?>
                                            <a href="<?= site_url('presenter/events/detail/' . $payment['event_id']) ?>" 
                                               class="btn btn-success btn-action">
                                                <i class="fas fa-calendar-check me-2"></i>Lihat Event
                                            </a>
                                            
                                            <a href="<?= site_url('presenter/absensi') ?>" 
                                               class="btn btn-outline-primary btn-action">
                                                <i class="fas fa-qrcode me-2"></i>Kelola Absensi
                                            </a>

                                            <div class="alert alert-custom" style="background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); color: #065f46;">
                                                <i class="fas fa-check-circle me-2"></i>
                                                <strong>Pembayaran Terverifikasi</strong><br>
                                                Pembayaran Anda telah diverifikasi. Anda dapat mengikuti event sesuai jadwal.
                                            </div>

                                        <?php elseif ($payment['status'] == 'rejected'): ?>
                                            <a href="<?= site_url('presenter/pembayaran/create/' . $payment['event_id']) ?>" 
                                               class="btn btn-warning btn-action">
                                                <i class="fas fa-redo me-2"></i>Bayar Ulang
                                            </a>
                                            
                                            <div class="alert alert-custom" style="background: linear-gradient(135deg, #fee2e2 0%, #fecaca 100%); color: #991b1b;">
                                                <i class="fas fa-exclamation-circle me-2"></i>
                                                <strong>Pembayaran Ditolak</strong><br>
                                                <?= !empty($payment['keterangan']) ? $payment['keterangan'] : 'Silakan hubungi admin atau lakukan pembayaran ulang.' ?>
                                            </div>

                                        <?php elseif ($payment['status'] == 'cancelled'): ?>
                                            <a href="<?= site_url('presenter/pembayaran/create/' . $payment['event_id']) ?>" 
                                               class="btn btn-primary btn-action">
                                                <i class="fas fa-plus me-2"></i>Bayar Lagi
                                            </a>
                                            
                                            <div class="alert alert-custom" style="background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%); color: #475569;">
                                                <i class="fas fa-ban me-2"></i>
                                                <strong>Pembayaran Dibatalkan</strong><br>
                                                Pembayaran telah dibatalkan. Anda dapat melakukan pembayaran baru jika diperlukan.
                                            </div>
                                        <?php endif; ?>

                                        <hr>
                                        
                                        <?php if (!empty($payment['bukti_bayar'])): ?>
                                        <a href="<?= site_url('presenter/pembayaran/downloadBukti/' . $payment['id_pembayaran']) ?>" 
                                           class="btn btn-outline-success btn-action">
                                            <i class="fas fa-download me-2"></i>Download Bukti
                                        </a>
                                        <?php endif; ?>
                                        
                                        <a href="<?= site_url('presenter/pembayaran') ?>" 
                                           class="btn btn-outline-secondary btn-action">
                                            <i class="fas fa-list me-2"></i>Lihat Semua Pembayaran
                                        </a>
                                    </div>
                                </div>
                            </div>

                            <!-- Additional Information -->
                            <div class="detail-card">
                                <div class="detail-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-info-circle me-2"></i>Informasi Tambahan
                                    </h5>
                                </div>
                                <div class="detail-body">
                                    <div class="info-row">
                                        <span class="info-label">ID User</span>
                                        <span class="info-value"><?= $payment['id_user'] ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Event ID</span>
                                        <span class="info-value"><?= $payment['event_id'] ?? '-' ?></span>
                                    </div>
                                    <?php if (!empty($payment['id_voucher'])): ?>
                                    <div class="info-row">
                                        <span class="info-label">Voucher ID</span>
                                        <span class="info-value"><?= $payment['id_voucher'] ?></span>
                                    </div>
                                    <?php endif; ?>
                                    <div class="info-row">
                                        <span class="info-label">Created At</span>
                                        <span class="info-value"><?= date('d/m/Y H:i:s', strtotime($payment['tanggal_bayar'])) ?></span>
                                    </div>
                                    <?php if (!empty($payment['verified_at'])): ?>
                                    <div class="info-row">
                                        <span class="info-label">Verified At</span>
                                        <span class="info-value"><?= date('d/m/Y H:i:s', strtotime($payment['verified_at'])) ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Image Preview Modal -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-xl modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-image me-2"></i>Preview Bukti Pembayaran
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center p-4">
                    <img id="fullImage" src="" class="img-fluid" style="max-height: 70vh;" alt="Bukti Pembayaran">
                </div>
                <div class="modal-footer">
                    <a href="<?= site_url('presenter/pembayaran/downloadBukti/' . $payment['id_pembayaran']) ?>" 
                       class="btn btn-outline-success">
                        <i class="fas fa-download me-2"></i>Download
                    </a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
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
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center mb-4">
                        <i class="fas fa-exclamation-triangle text-warning" style="font-size: 4rem;"></i>
                    </div>
                    <p class="text-center mb-3">Apakah Anda yakin ingin membatalkan pembayaran ini?</p>
                    <div class="alert alert-warning">
                        <i class="fas fa-info-circle me-2"></i>
                        <strong>Perhatian:</strong> 
                        <ul class="mb-0 mt-2">
                            <li>Pembatalan tidak dapat dibatalkan</li>
                            <li>Anda harus melakukan pembayaran ulang untuk event ini</li>
                            <li>Voucher yang digunakan akan dikembalikan</li>
                        </ul>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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

            // Animation on load
            const cards = document.querySelectorAll('.detail-card');
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

            // Show success/error messages
            <?php if (session('success')): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: '<?= session('success') ?>',
                    timer: 3000,
                    showConfirmButton: false
                });
            <?php endif; ?>

            <?php if (session('error')): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: '<?= session('error') ?>',
                });
            <?php endif; ?>
        });

        function viewFullImage(src) {
            document.getElementById('fullImage').src = src;
            new bootstrap.Modal(document.getElementById('imageModal')).show();
        }

        function cancelPayment(paymentId) {
            const form = document.getElementById('cancelForm');
            form.action = '<?= site_url('presenter/pembayaran/cancel/') ?>' + paymentId;
            
            const modal = new bootstrap.Modal(document.getElementById('cancelModal'));
            modal.show();
        }

        // Handle form submission with loading state
        document.getElementById('cancelForm').addEventListener('submit', function(e) {
            const submitBtn = this.querySelector('button[type="submit"]');
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Membatalkan...';
            submitBtn.disabled = true;
        });

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

        // Auto-refresh for pending payments (check every 2 minutes)
        <?php if ($payment['status'] == 'pending'): ?>
        setInterval(() => {
            // Subtle indication that we're checking for updates
            console.log('Checking payment status...');
            // You could add an AJAX call here to check status updates
        }, 120000); // 2 minutes
        <?php endif; ?>
    </script>
</body>
</html>