<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Pembayaran - SNIA Admin</title>
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

        .detail-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
            overflow: hidden;
            margin-bottom: 20px;
        }

        .detail-header {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            padding: 16px 20px;
            border-bottom: 1px solid #e2e8f0;
        }

        .detail-body {
            padding: 20px;
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
        }

        .status-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: 600;
        }

        .user-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--info-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            font-size: 24px;
        }

        .amount-display {
            font-size: 2rem;
            font-weight: 700;
            color: var(--success-color);
        }

        .bukti-container {
            text-align: center;
            padding: 40px 20px;
            background: #f8fafc;
            border-radius: 12px;
            border: 2px dashed #e2e8f0;
            min-height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }

        .bukti-preview {
            max-width: 100%;
            max-height: 500px;
            border-radius: 8px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.15);
            cursor: pointer;
            transition: transform 0.3s ease;
        }

        .bukti-preview:hover {
            transform: scale(1.02);
        }

        .btn-custom {
            border-radius: 8px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-custom:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
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
            border-radius: 50%;
            background: var(--primary-color);
        }

        .timeline-date {
            font-size: 12px;
            color: var(--secondary-color);
            margin-bottom: 5px;
        }

        .timeline-content {
            font-weight: 500;
        }

        .modal-content {
            border-radius: 16px;
            border: none;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--info-color));
            color: white;
            border-radius: 16px 16px 0 0;
            border-bottom: none;
        }

        .voucher-info {
            background: linear-gradient(135deg, var(--success-color), #059669);
            color: white;
            padding: 16px;
            border-radius: 12px;
            margin: 16px 0;
        }

        .alert-custom {
            border: none;
            border-radius: 12px;
            padding: 16px;
        }

        .no-bukti {
            text-align: center;
            color: #6b7280;
        }

        .no-bukti i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.3;
        }

        .participation-badge {
            font-size: 12px;
            padding: 4px 8px;
            border-radius: 6px;
            margin-left: 5px;
        }

        .participation-online {
            background: #e3f2fd;
            color: #1976d2;
        }

        .participation-offline {
            background: #f3e5f5;
            color: #7b1fa2;
        }

        .event-info {
            background: linear-gradient(135deg, #e0f2fe, #b3e5fc);
            color: #01579b;
            padding: 16px;
            border-radius: 12px;
            margin: 16px 0;
        }

        .payment-features {
            background: #f8fafc;
            border-radius: 8px;
            padding: 12px;
            margin-top: 10px;
        }

        .feature-item {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }

        .feature-item:last-child {
            margin-bottom: 0;
        }

        .feature-icon {
            width: 20px;
            text-align: center;
            margin-right: 8px;
        }

        .loading-spinner {
            display: inline-block;
            width: 30px;
            height: 30px;
            border: 3px solid rgba(37, 99, 235, 0.3);
            border-radius: 50%;
            border-top-color: var(--primary-color);
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
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
                        <a class="nav-link" href="<?= site_url('admin/event') ?>">
                            <i class="fas fa-calendar-alt me-2"></i> Kelola Event
                        </a>
                        <a class="nav-link active" href="<?= site_url('admin/pembayaran') ?>">
                            <i class="fas fa-credit-card me-2"></i> Verifikasi Pembayaran
                        </a>
                        <a class="nav-link" href="<?= site_url('admin/absensi') ?>">
                            <i class="fas fa-qrcode me-2"></i> Kelola Absensi
                        </a>
                        <a class="nav-link" href="<?= site_url('admin/voucher') ?>">
                            <i class="fas fa-ticket-alt me-2"></i> Kelola Voucher
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
                    <div class="content-card mb-4">
                        <div class="content-header">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h2 class="mb-2">
                                        <i class="fas fa-credit-card me-3"></i>Detail Pembayaran
                                    </h2>
                                    <p class="mb-0 opacity-75">Informasi lengkap pembayaran dan verifikasi</p>
                                </div>
                                <div class="col-auto">
                                    <a href="<?= site_url('admin/pembayaran') ?>" class="btn btn-outline-light btn-custom">
                                        <i class="fas fa-arrow-left me-2"></i>Kembali
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4">
                        <!-- User Information -->
                        <div class="col-lg-6">
                            <div class="detail-card">
                                <div class="detail-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-user me-2"></i>Informasi Pengguna
                                    </h5>
                                </div>
                                <div class="detail-body">
                                    <div class="d-flex align-items-center mb-4">
                                        <div class="user-avatar me-3">
                                            <?= isset($pembayaran['nama_lengkap']) ? strtoupper(substr($pembayaran['nama_lengkap'], 0, 1)) : 'U' ?>
                                        </div>
                                        <div>
                                            <h5 class="mb-1"><?= isset($pembayaran['nama_lengkap']) ? esc($pembayaran['nama_lengkap']) : 'N/A' ?></h5>
                                            <p class="text-muted mb-0"><?= isset($pembayaran['email']) ? esc($pembayaran['email']) : 'N/A' ?></p>
                                            <span class="badge <?= (isset($pembayaran['role']) && $pembayaran['role'] == 'presenter') ? 'bg-primary' : 'bg-secondary' ?>">
                                                <?= isset($pembayaran['role']) ? ucfirst($pembayaran['role']) : 'Audience' ?>
                                            </span>
                                            <?php if (isset($pembayaran['participation_type']) && !empty($pembayaran['participation_type'])): ?>
                                                <span class="participation-badge participation-<?= $pembayaran['participation_type'] ?>">
                                                    <?= ucfirst($pembayaran['participation_type']) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <div class="info-row">
                                        <span class="info-label">Tanggal Registrasi</span>
                                        <span class="info-value">
                                            <?php 
                                            if (isset($pembayaran['created_at']) && !empty($pembayaran['created_at'])) {
                                                echo date('d/m/Y', strtotime($pembayaran['created_at']));
                                            } elseif (isset($pembayaran['tanggal_bayar']) && !empty($pembayaran['tanggal_bayar'])) {
                                                echo date('d/m/Y', strtotime($pembayaran['tanggal_bayar']));
                                            } else {
                                                echo '-';
                                            }
                                            ?>
                                        </span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Status User</span>
                                        <span class="info-value">
                                            <span class="badge <?= (isset($pembayaran['status_user']) && $pembayaran['status_user'] == 'aktif') ? 'bg-success' : 'bg-secondary' ?>">
                                                <?= isset($pembayaran['status_user']) ? ucfirst($pembayaran['status_user']) : 'Aktif' ?>
                                            </span>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Information -->
                        <div class="col-lg-6">
                            <div class="detail-card">
                                <div class="detail-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-money-bill-wave me-2"></i>Informasi Pembayaran
                                    </h5>
                                </div>
                                <div class="detail-body">
                                    <div class="text-center mb-4">
                                        <div class="amount-display">
                                            Rp <?= isset($pembayaran['jumlah']) ? number_format($pembayaran['jumlah'], 0, ',', '.') : '0' ?>
                                        </div>
                                        <?php
                                        $status = isset($pembayaran['status']) ? $pembayaran['status'] : 'pending';
                                        $statusClass = '';
                                        $statusText = '';
                                        switch($status) {
                                            case 'pending':
                                                $statusClass = 'bg-warning text-dark';
                                                $statusText = 'Pending';
                                                break;
                                            case 'verified':
                                                $statusClass = 'bg-success';
                                                $statusText = 'Terverifikasi';
                                                break;
                                            case 'rejected':
                                                $statusClass = 'bg-danger';
                                                $statusText = 'Ditolak';
                                                break;
                                            default:
                                                $statusClass = 'bg-secondary';
                                                $statusText = 'Unknown';
                                        }
                                        ?>
                                        <span class="status-badge <?= $statusClass ?>"><?= $statusText ?></span>
                                    </div>
                                    
                                    <div class="info-row">
                                        <span class="info-label">ID Pembayaran</span>
                                        <span class="info-value">#PAY<?= isset($pembayaran['id_pembayaran']) ? str_pad($pembayaran['id_pembayaran'], 4, '0', STR_PAD_LEFT) : '0000' ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Metode</span>
                                        <span class="info-value"><?= isset($pembayaran['metode']) ? esc($pembayaran['metode']) : 'N/A' ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Tanggal Bayar</span>
                                        <span class="info-value">
                                            <?= isset($pembayaran['tanggal_bayar']) && !empty($pembayaran['tanggal_bayar']) ? date('d/m/Y H:i', strtotime($pembayaran['tanggal_bayar'])) : '-' ?>
                                        </span>
                                    </div>
                                    <?php if (isset($pembayaran['verified_at']) && !empty($pembayaran['verified_at'])): ?>
                                    <div class="info-row">
                                        <span class="info-label">Tanggal Verifikasi</span>
                                        <span class="info-value"><?= date('d/m/Y H:i', strtotime($pembayaran['verified_at'])) ?></span>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($pembayaran['original_amount']) && isset($pembayaran['jumlah']) && $pembayaran['original_amount'] != $pembayaran['jumlah']): ?>
                                    <div class="info-row">
                                        <span class="info-label">Harga Asli</span>
                                        <span class="info-value text-muted">
                                            <del>Rp <?= number_format($pembayaran['original_amount'], 0, ',', '.') ?></del>
                                        </span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Diskon</span>
                                        <span class="info-value text-success">
                                            -Rp <?= number_format((isset($pembayaran['discount_amount']) ? $pembayaran['discount_amount'] : 0), 0, ',', '.') ?>
                                        </span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Event Information (if exists) -->
                        <?php if (isset($pembayaran['event_title']) && !empty($pembayaran['event_title'])): ?>
                        <div class="col-12">
                            <div class="event-info">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <h6 class="mb-1">
                                            <i class="fas fa-calendar-alt me-2"></i>Event Terdaftar
                                        </h6>
                                        <div class="d-flex align-items-center">
                                            <strong class="me-3"><?= esc($pembayaran['event_title']) ?></strong>
                                            <?php if (isset($pembayaran['event_date']) && !empty($pembayaran['event_date'])): ?>
                                                <span class="badge bg-light text-dark">
                                                    <?= date('d M Y', strtotime($pembayaran['event_date'])) ?>
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <?php if (isset($pembayaran['event_id']) && !empty($pembayaran['event_id'])): ?>
                                            <a href="<?= site_url('admin/event/detail/' . $pembayaran['event_id']) ?>" 
                                               class="btn btn-outline-primary btn-sm">
                                                <i class="fas fa-external-link-alt me-1"></i>Lihat Event
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Voucher Information (if exists) -->
                        <?php if (isset($voucher) && $voucher): ?>
                        <div class="col-12">
                            <div class="voucher-info">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <h6 class="mb-1">
                                            <i class="fas fa-ticket-alt me-2"></i>Voucher Digunakan
                                        </h6>
                                        <div class="d-flex align-items-center">
                                            <strong class="me-3"><?= esc($voucher['kode_voucher']) ?></strong>
                                            <span class="badge bg-light text-dark">
                                                <?= $voucher['tipe'] == 'percentage' ? $voucher['nilai'] . '%' : 'Rp ' . number_format($voucher['nilai'], 0, ',', '.') ?>
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-auto">
                                        <div class="text-end">
                                            <div>Diskon: <strong>
                                                Rp <?= number_format((isset($pembayaran['discount_amount']) ? $pembayaran['discount_amount'] : 0), 0, ',', '.') ?>
                                            </strong></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Payment Features (for presenters) -->
                        <?php if (isset($pembayaran['role']) && $pembayaran['role'] == 'presenter' && isset($pembayaran['status']) && $pembayaran['status'] == 'verified'): ?>
                        <div class="col-12">
                            <div class="detail-card">
                                <div class="detail-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-unlock me-2"></i>Fitur yang Dibuka
                                    </h5>
                                </div>
                                <div class="detail-body">
                                    <div class="payment-features">
                                        <div class="feature-item">
                                            <i class="fas fa-qrcode feature-icon text-success"></i>
                                            <span>QR Code Attendance Scanning</span>
                                        </div>
                                        <div class="feature-item">
                                            <i class="fas fa-download feature-icon text-info"></i>
                                            <span>Letter of Acceptance (LoA) Download</span>
                                        </div>
                                        <div class="feature-item">
                                            <i class="fas fa-tachometer-alt feature-icon text-primary"></i>
                                            <span>Presenter Dashboard Access</span>
                                        </div>
                                        <div class="feature-item">
                                            <i class="fas fa-certificate feature-icon text-warning"></i>
                                            <span>Certificate Generation</span>
                                        </div>
                                    </div>
                                    <?php if (isset($pembayaran['features_unlocked_at']) && !empty($pembayaran['features_unlocked_at'])): ?>
                                        <small class="text-muted">
                                            Fitur dibuka pada: <?= date('d/m/Y H:i', strtotime($pembayaran['features_unlocked_at'])) ?>
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- Bukti Pembayaran -->
                        <div class="col-lg-8">
                            <div class="detail-card">
                                <div class="detail-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-image me-2"></i>Bukti Pembayaran
                                    </h5>
                                </div>
                                <div class="detail-body">
                                    <div class="bukti-container" id="buktiContainer">
                                        <?php if (isset($pembayaran['bukti_bayar']) && !empty($pembayaran['bukti_bayar'])): ?>
                                            <div id="buktiLoading" style="display: block;">
                                                <div class="loading-spinner mb-3"></div>
                                                <p>Memuat gambar bukti pembayaran...</p>
                                            </div>
                                            <div id="buktiContent" style="display: none;">
                                                <img id="buktiImage" 
                                                     src="" 
                                                     class="bukti-preview" 
                                                     alt="Bukti Pembayaran"
                                                     onclick="viewFullImage(this.src)">
                                                <div id="buktiActions" class="mt-3">
                                                    <p class="text-muted mb-2">File: <?= esc($pembayaran['bukti_bayar']) ?></p>
                                                    <p class="text-muted mb-3">
                                                        Diupload: <?= isset($pembayaran['tanggal_bayar']) ? date('d M Y', strtotime($pembayaran['tanggal_bayar'])) : '-' ?>
                                                    </p>
                                                    <div class="d-flex gap-2 justify-content-center">
                                                        <button class="btn btn-outline-primary btn-custom btn-sm" onclick="viewFullImage(document.getElementById('buktiImage').src)">
                                                            <i class="fas fa-search-plus me-1"></i>Perbesar
                                                        </button>
                                                        <a href="<?= site_url('admin/pembayaran/download-bukti/' . (isset($pembayaran['id_pembayaran']) ? $pembayaran['id_pembayaran'] : 0)) ?>" 
                                                           class="btn btn-outline-success btn-custom btn-sm">
                                                            <i class="fas fa-download me-1"></i>Download
                                                        </a>
                                                    </div>
                                                </div>
                                            </div>
                                            <div id="buktiError" class="no-bukti" style="display: none;">
                                                <i class="fas fa-exclamation-triangle"></i>
                                                <h5>File Tidak Ditemukan</h5>
                                                <p class="text-muted">File bukti pembayaran tidak dapat dimuat.</p>
                                                <p class="text-muted mb-0">File: <?= esc($pembayaran['bukti_bayar']) ?></p>
                                            </div>
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
                            <div class="detail-card">
                                <div class="detail-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-history me-2"></i>Timeline
                                    </h5>
                                </div>
                                <div class="detail-body">
                                    <div class="timeline-item">
                                        <div class="timeline-date">
                                            <?= isset($pembayaran['tanggal_bayar']) && !empty($pembayaran['tanggal_bayar']) ? date('d M Y, H:i', strtotime($pembayaran['tanggal_bayar'])) : 'N/A' ?>
                                        </div>
                                        <div class="timeline-content">Pembayaran dibuat</div>
                                    </div>
                                    
                                    <?php if (isset($pembayaran['bukti_bayar']) && !empty($pembayaran['bukti_bayar'])): ?>
                                    <div class="timeline-item">
                                        <div class="timeline-date">
                                            <?= isset($pembayaran['tanggal_bayar']) && !empty($pembayaran['tanggal_bayar']) ? date('d M Y, H:i', strtotime($pembayaran['tanggal_bayar'])) : 'N/A' ?>
                                        </div>
                                        <div class="timeline-content">Bukti pembayaran diupload</div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if (isset($pembayaran['status']) && $pembayaran['status'] == 'verified' && isset($pembayaran['verified_at']) && !empty($pembayaran['verified_at'])): ?>
                                    <div class="timeline-item">
                                        <div class="timeline-date"><?= date('d M Y, H:i', strtotime($pembayaran['verified_at'])) ?></div>
                                        <div class="timeline-content text-success">
                                            Pembayaran diverifikasi
                                            <?php if (isset($verified_by) && $verified_by): ?>
                                                <br><small class="text-muted">oleh <?= esc($verified_by['nama_lengkap']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php if (isset($pembayaran['features_unlocked_at']) && !empty($pembayaran['features_unlocked_at']) && isset($pembayaran['role']) && $pembayaran['role'] == 'presenter'): ?>
                                    <div class="timeline-item">
                                        <div class="timeline-date"><?= date('d M Y, H:i', strtotime($pembayaran['features_unlocked_at'])) ?></div>
                                        <div class="timeline-content text-info">Fitur presenter dibuka</div>
                                    </div>
                                    <?php endif; ?>
                                    <?php elseif (isset($pembayaran['status']) && $pembayaran['status'] == 'rejected' && isset($pembayaran['verified_at']) && !empty($pembayaran['verified_at'])): ?>
                                    <div class="timeline-item">
                                        <div class="timeline-date"><?= date('d M Y, H:i', strtotime($pembayaran['verified_at'])) ?></div>
                                        <div class="timeline-content text-danger">
                                            Pembayaran ditolak
                                            <?php if (isset($verified_by) && $verified_by): ?>
                                                <br><small class="text-muted">oleh <?= esc($verified_by['nama_lengkap']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php else: ?>
                                    <div class="timeline-item">
                                        <div class="timeline-date">Menunggu</div>
                                        <div class="timeline-content text-warning">Menunggu verifikasi admin</div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <?php if (isset($pembayaran['status']) && $pembayaran['status'] == 'pending'): ?>
                            <div class="detail-card">
                                <div class="detail-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-cog me-2"></i>Aksi Verifikasi
                                    </h5>
                                </div>
                                <div class="detail-body">
                                    <div class="d-grid gap-2">
                                        <button class="btn btn-success btn-custom" onclick="verifikasiPembayaran(<?= isset($pembayaran['id_pembayaran']) ? $pembayaran['id_pembayaran'] : 0 ?>, 'verified')">
                                            <i class="fas fa-check me-2"></i>Verifikasi Pembayaran
                                        </button>
                                        <button class="btn btn-danger btn-custom" onclick="verifikasiPembayaran(<?= isset($pembayaran['id_pembayaran']) ? $pembayaran['id_pembayaran'] : 0 ?>, 'rejected')">
                                            <i class="fas fa-times me-2"></i>Tolak Pembayaran
                                        </button>
                                        <hr>
                                        <button class="btn btn-outline-info btn-custom" onclick="sendNotification()">
                                            <i class="fas fa-envelope me-2"></i>Kirim Notifikasi
                                        </button>
                                        <button class="btn btn-outline-secondary btn-custom" onclick="addNote()">
                                            <i class="fas fa-sticky-note me-2"></i>Tambah Catatan
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <!-- Additional Information -->
                        <div class="col-12">
                            <div class="detail-card">
                                <div class="detail-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-info-circle me-2"></i>Informasi Tambahan
                                    </h5>
                                </div>
                                <div class="detail-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="info-row">
                                                <span class="info-label">ID User</span>
                                                <span class="info-value"><?= isset($pembayaran['id_user']) ? $pembayaran['id_user'] : 'N/A' ?></span>
                                            </div>
                                            <div class="info-row">
                                                <span class="info-label">Event ID</span>
                                                <span class="info-value"><?= isset($pembayaran['event_id']) ? $pembayaran['event_id'] : '-' ?></span>
                                            </div>
                                            <div class="info-row">
                                                <span class="info-label">Created At</span>
                                                <span class="info-value">
                                                    <?= isset($pembayaran['tanggal_bayar']) && !empty($pembayaran['tanggal_bayar']) ? date('d/m/Y H:i:s', strtotime($pembayaran['tanggal_bayar'])) : 'N/A' ?>
                                                </span>
                                            </div>
                                            <?php if (isset($pembayaran['payment_reference']) && !empty($pembayaran['payment_reference'])): ?>
                                            <div class="info-row">
                                                <span class="info-label">Referensi</span>
                                                <span class="info-value"><?= esc($pembayaran['payment_reference']) ?></span>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="col-md-6">
                                            <?php if (isset($verified_by) && $verified_by): ?>
                                            <div class="info-row">
                                                <span class="info-label">Diverifikasi oleh</span>
                                                <span class="info-value"><?= esc($verified_by['nama_lengkap']) ?></span>
                                            </div>
                                            <?php endif; ?>
                                            <?php if (isset($pembayaran['id_voucher']) && !empty($pembayaran['id_voucher'])): ?>
                                            <div class="info-row">
                                                <span class="info-label">Voucher ID</span>
                                                <span class="info-value"><?= $pembayaran['id_voucher'] ?></span>
                                            </div>
                                            <?php endif; ?>
                                            <div class="info-row">
                                                <span class="info-label">Status Terakhir</span>
                                                <span class="info-value">
                                                    <span class="badge <?= $statusClass ?>"><?= $statusText ?></span>
                                                </span>
                                            </div>
                                            <?php if (isset($pembayaran['auto_verified']) && $pembayaran['auto_verified']): ?>
                                            <div class="info-row">
                                                <span class="info-label">Auto Verified</span>
                                                <span class="info-value">
                                                    <span class="badge bg-info">Ya</span>
                                                </span>
                                            </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <?php if (isset($pembayaran['keterangan']) && !empty($pembayaran['keterangan'])): ?>
                                    <div class="mt-3">
                                        <label class="form-label">Keterangan:</label>
                                        <div class="alert alert-custom alert-light">
                                            <?= nl2br(esc($pembayaran['keterangan'])) ?>
                                        </div>
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
    <div class="modal fade" id="imageModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
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
                    <a href="<?= site_url('admin/pembayaran/download-bukti/' . (isset($pembayaran['id_pembayaran']) ? $pembayaran['id_pembayaran'] : 0)) ?>" 
                       class="btn btn-outline-success">
                        <i class="fas fa-download me-2"></i>Download
                    </a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Verification Modal -->
    <div class="modal fade" id="verifikasiModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="verifikasiTitle">
                        <i class="fas fa-check-circle me-2"></i>Verifikasi Pembayaran
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="verifikasiForm" method="POST">
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Pastikan bukti pembayaran sudah sesuai sebelum melakukan verifikasi.
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Keterangan Verifikasi</label>
                            <textarea class="form-control" name="keterangan" rows="3" 
                                    placeholder="Tambahkan keterangan verifikasi (opsional)..."></textarea>
                        </div>
                        <input type="hidden" name="status" id="verifikasiStatus">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="verifikasiSubmit">
                            <i class="fas fa-save me-2"></i>Proses
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Initialize bukti pembayaran loading
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (isset($pembayaran['bukti_bayar']) && !empty($pembayaran['bukti_bayar'])): ?>
                // Try different possible paths for the image
                const possiblePaths = [
                    '<?= site_url('uploads/pembayaran/' . $pembayaran['bukti_bayar']) ?>',
                    '<?= site_url('uploads/bukti/' . $pembayaran['bukti_bayar']) ?>',
                    '<?= site_url('assets/uploads/pembayaran/' . $pembayaran['bukti_bayar']) ?>',
                    '<?= site_url('admin/pembayaran/view-bukti/' . (isset($pembayaran['id_pembayaran']) ? $pembayaran['id_pembayaran'] : 0)) ?>'
                ];
                
                let currentPathIndex = 0;
                
                function tryLoadImage() {
                    if (currentPathIndex >= possiblePaths.length) {
                        // All paths failed, show error
                        document.getElementById('buktiLoading').style.display = 'none';
                        document.getElementById('buktiError').style.display = 'block';
                        return;
                    }
                    
                    const img = new Image();
                    img.onload = function() {
                        const buktiImage = document.getElementById('buktiImage');
                        buktiImage.src = possiblePaths[currentPathIndex];
                        
                        document.getElementById('buktiLoading').style.display = 'none';
                        document.getElementById('buktiContent').style.display = 'block';
                        document.getElementById('buktiError').style.display = 'none';
                    };
                    img.onerror = function() {
                        currentPathIndex++;
                        tryLoadImage();
                    };
                    img.src = possiblePaths[currentPathIndex];
                }
                
                // Start loading attempt
                tryLoadImage();
            <?php endif; ?>
            
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
        });

        function viewFullImage(src) {
            if (src && src !== '') {
                document.getElementById('fullImage').src = src;
                new bootstrap.Modal(document.getElementById('imageModal')).show();
            }
        }

        function verifikasiPembayaran(id, status) {
            const isVerified = status === 'verified';
            const title = isVerified ? 'Verifikasi Pembayaran' : 'Tolak Pembayaran';
            const icon = isVerified ? 'fa-check-circle' : 'fa-times-circle';
            const btnClass = isVerified ? 'btn-success' : 'btn-danger';
            const btnText = isVerified ? 'Verifikasi' : 'Tolak';

            document.getElementById('verifikasiTitle').innerHTML = `<i class="fas ${icon} me-2"></i>${title}`;
            document.getElementById('verifikasiStatus').value = status;
            document.getElementById('verifikasiForm').action = `<?= site_url('admin/pembayaran/verifikasi') ?>/${id}`;
            document.getElementById('verifikasiSubmit').className = `btn ${btnClass}`;
            document.getElementById('verifikasiSubmit').innerHTML = `<i class="fas fa-save me-2"></i>${btnText}`;

            new bootstrap.Modal(document.getElementById('verifikasiModal')).show();
        }

        function sendNotification() {
            Swal.fire({
                title: 'Kirim Notifikasi',
                text: 'Notifikasi akan dikirim ke user terkait status pembayaran.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Kirim',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Here you would make an AJAX call to send notification
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'Notifikasi berhasil dikirim.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            });
        }

        function addNote() {
            Swal.fire({
                title: 'Tambah Catatan',
                input: 'textarea',
                inputPlaceholder: 'Masukkan catatan tambahan...',
                showCancelButton: true,
                confirmButtonText: 'Simpan',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    // Here you would make an AJAX call to save the note
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: 'Catatan berhasil ditambahkan.',
                        timer: 2000,
                        showConfirmButton: false
                    });
                }
            });
        }
    </script>
</body>
</html>