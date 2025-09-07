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
            padding: 20px;
            background: #f8fafc;
            border-radius: 12px;
            border: 2px dashed #e2e8f0;
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
            padding: 40px;
            color: #6b7280;
        }

        .no-bukti i {
            font-size: 3rem;
            margin-bottom: 15px;
            opacity: 0.5;
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
                                            <?= strtoupper(substr($pembayaran['nama_lengkap'], 0, 1)) ?>
                                        </div>
                                        <div>
                                            <h5 class="mb-1"><?= esc($pembayaran['nama_lengkap']) ?></h5>
                                            <p class="text-muted mb-0"><?= esc($pembayaran['email']) ?></p>
                                            <span class="badge <?= $pembayaran['role'] == 'presenter' ? 'bg-primary' : 'bg-secondary' ?>">
                                                <?= ucfirst($pembayaran['role']) ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="info-row">
                                        <span class="info-label">Nomor Telepon</span>
                                        <span class="info-value"><?= esc($pembayaran['phone'] ?? '-') ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Institusi</span>
                                        <span class="info-value"><?= esc($pembayaran['institusi'] ?? '-') ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Tanggal Daftar</span>
                                        <span class="info-value"><?= date('d/m/Y', strtotime($pembayaran['tanggal_bayar'])) ?></span>
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
                                        <div class="amount-display">Rp <?= number_format($pembayaran['jumlah'], 0, ',', '.') ?></div>
                                        <?php
                                        $statusClass = '';
                                        $statusText = '';
                                        switch($pembayaran['status']) {
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
                                        }
                                        ?>
                                        <span class="status-badge <?= $statusClass ?>"><?= $statusText ?></span>
                                    </div>
                                    
                                    <div class="info-row">
                                        <span class="info-label">ID Pembayaran</span>
                                        <span class="info-value">#PAY<?= str_pad($pembayaran['id_pembayaran'], 3, '0', STR_PAD_LEFT) ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Metode</span>
                                        <span class="info-value"><?= esc($pembayaran['metode']) ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="info-label">Tanggal Bayar</span>
                                        <span class="info-value"><?= date('d/m/Y H:i', strtotime($pembayaran['tanggal_bayar'])) ?></span>
                                    </div>
                                    <?php if (!empty($pembayaran['verified_at'])): ?>
                                    <div class="info-row">
                                        <span class="info-label">Tanggal Verifikasi</span>
                                        <span class="info-value"><?= date('d/m/Y H:i', strtotime($pembayaran['verified_at'])) ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Voucher Information (if exists) -->
                        <?php if ($voucher): ?>
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
                                                <?php
                                                if ($voucher['tipe'] == 'percentage') {
                                                    $diskon = ($pembayaran['jumlah'] * $voucher['nilai']) / (100 - $voucher['nilai']);
                                                    echo 'Rp ' . number_format($diskon, 0, ',', '.');
                                                } else {
                                                    echo 'Rp ' . number_format($voucher['nilai'], 0, ',', '.');
                                                }
                                                ?>
                                            </strong></div>
                                        </div>
                                    </div>
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
                                    <div class="bukti-container">
                                        <?php if (!empty($pembayaran['bukti_bayar']) && isset($bukti_path) && $bukti_path): ?>
                                            <img src="<?= $bukti_path ?>" 
                                                 class="bukti-preview" 
                                                 alt="Bukti Pembayaran"
                                                 onclick="viewFullImage('<?= $bukti_path ?>')">
                                            <div class="mt-3">
                                                <p class="text-muted mb-2">File: <?= esc($pembayaran['bukti_bayar']) ?></p>
                                                <p class="text-muted mb-3">Diupload: <?= date('d M Y', strtotime($pembayaran['tanggal_bayar'])) ?></p>
                                                <div class="d-flex gap-2 justify-content-center">
                                                    <button class="btn btn-outline-primary btn-custom btn-sm" onclick="viewFullImage('<?= $bukti_path ?>')">
                                                        <i class="fas fa-search-plus me-1"></i>Perbesar
                                                    </button>
                                                    <a href="<?= site_url('admin/pembayaran/download-bukti/' . $pembayaran['id_pembayaran']) ?>" 
                                                       class="btn btn-outline-success btn-custom btn-sm">
                                                        <i class="fas fa-download me-1"></i>Download
                                                    </a>
                                                </div>
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
                                        <div class="timeline-date"><?= date('d M Y, H:i', strtotime($pembayaran['tanggal_bayar'])) ?></div>
                                        <div class="timeline-content">Pembayaran dibuat</div>
                                    </div>
                                    <?php if (!empty($pembayaran['bukti_bayar'])): ?>
                                    <div class="timeline-item">
                                        <div class="timeline-date"><?= date('d M Y, H:i', strtotime($pembayaran['tanggal_bayar'])) ?></div>
                                        <div class="timeline-content">Bukti pembayaran diupload</div>
                                    </div>
                                    <?php endif; ?>
                                    
                                    <?php if ($pembayaran['status'] == 'verified' && !empty($pembayaran['verified_at'])): ?>
                                    <div class="timeline-item">
                                        <div class="timeline-date"><?= date('d M Y, H:i', strtotime($pembayaran['verified_at'])) ?></div>
                                        <div class="timeline-content text-success">Pembayaran diverifikasi</div>
                                    </div>
                                    <?php elseif ($pembayaran['status'] == 'rejected' && !empty($pembayaran['verified_at'])): ?>
                                    <div class="timeline-item">
                                        <div class="timeline-date"><?= date('d M Y, H:i', strtotime($pembayaran['verified_at'])) ?></div>
                                        <div class="timeline-content text-danger">Pembayaran ditolak</div>
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
                            <?php if ($pembayaran['status'] == 'pending'): ?>
                            <div class="detail-card">
                                <div class="detail-header">
                                    <h5 class="mb-0">
                                        <i class="fas fa-cog me-2"></i>Aksi Verifikasi
                                    </h5>
                                </div>
                                <div class="detail-body">
                                    <div class="d-grid gap-2">
                                        <button class="btn btn-success btn-custom" onclick="verifikasiPembayaran(<?= $pembayaran['id_pembayaran'] ?>, 'verified')">
                                            <i class="fas fa-check me-2"></i>Verifikasi Pembayaran
                                        </button>
                                        <button class="btn btn-danger btn-custom" onclick="verifikasiPembayaran(<?= $pembayaran['id_pembayaran'] ?>, 'rejected')">
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
                                                <span class="info-value"><?= $pembayaran['id_user'] ?></span>
                                            </div>
                                            <div class="info-row">
                                                <span class="info-label">Event ID</span>
                                                <span class="info-value"><?= $pembayaran['event_id'] ?? '-' ?></span>
                                            </div>
                                            <div class="info-row">
                                                <span class="info-label">Created At</span>
                                                <span class="info-value"><?= date('d/m/Y H:i:s', strtotime($pembayaran['tanggal_bayar'])) ?></span>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <?php if ($verified_by): ?>
                                            <div class="info-row">
                                                <span class="info-label">Diverifikasi oleh</span>
                                                <span class="info-value"><?= esc($verified_by['nama_lengkap']) ?></span>
                                            </div>
                                            <?php endif; ?>
                                            <?php if (!empty($pembayaran['id_voucher'])): ?>
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
                                        </div>
                                    </div>
                                    
                                    <?php if (!empty($pembayaran['keterangan'])): ?>
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
                    <a href="<?= site_url('admin/pembayaran/download-bukti/' . $pembayaran['id_pembayaran']) ?>" 
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
        function viewFullImage(src) {
            document.getElementById('fullImage').src = src;
            new bootstrap.Modal(document.getElementById('imageModal')).show();
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

        // Show success/error messages
        document.addEventListener('DOMContentLoaded', function() {
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
    </script>
</body>
</html>