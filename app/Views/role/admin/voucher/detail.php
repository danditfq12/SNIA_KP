<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Voucher - SNIA Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
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
            padding: 30px;
        }

        .voucher-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--info-color) 100%);
            color: white;
            padding: 30px;
            border-radius: 16px;
            margin-bottom: 30px;
        }

        .voucher-code {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            font-size: 2.5rem;
            letter-spacing: 3px;
            margin: 20px 0;
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

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1e293b;
            margin: 16px 0 8px 0;
        }

        .table-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            overflow: hidden;
            margin-top: 30px;
        }

        .table-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--info-color) 100%);
            color: white;
            padding: 20px;
            font-weight: 600;
        }

        .table th {
            background: #f8fafc;
            border: none;
            padding: 16px;
            font-weight: 600;
            color: #374151;
        }

        .table td {
            padding: 16px;
            vertical-align: middle;
            border-color: #f1f5f9;
        }

        .badge-custom {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .btn-custom {
            border-radius: 8px;
            padding: 8px 16px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-custom:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .progress-custom {
            height: 8px;
            border-radius: 4px;
            background: #e2e8f0;
        }

        .progress-bar-custom {
            border-radius: 4px;
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
                        <a class="nav-link" href="<?= site_url('admin/pembayaran') ?>">
                            <i class="fas fa-credit-card me-2"></i> Verifikasi Pembayaran
                        </a>
                        <a class="nav-link" href="<?= site_url('admin/absensi') ?>">
                            <i class="fas fa-qrcode me-2"></i> Kelola Absensi
                        </a>
                        <a class="nav-link active" href="<?= site_url('admin/voucher') ?>">
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
            <div class="col-md-9 col-lg-10 px-0">
                <div class="main-content">
                    <!-- Back Button -->
                    <div class="mb-4">
                        <a href="<?= site_url('admin/voucher') ?>" class="btn btn-secondary btn-custom">
                            <i class="fas fa-arrow-left me-2"></i>Kembali ke Daftar Voucher
                        </a>
                    </div>

                    <!-- Voucher Header -->
                    <div class="voucher-header">
                        <div class="row align-items-center">
                            <div class="col">
                                <h1 class="h3 mb-0">Detail Voucher</h1>
                                <div class="voucher-code"><?= esc($voucher['kode_voucher']) ?></div>
                                <p class="mb-0 opacity-75">
                                    <?= $voucher['tipe'] === 'percentage' ? 'Diskon Persentase' : 'Diskon Fixed Amount' ?>
                                    - Status: <?= ucfirst($voucher['status']) ?>
                                </p>
                            </div>
                            <div class="col-auto">
                                <div class="text-end">
                                    <div class="h2 mb-0">
                                        <?= $voucher['tipe'] === 'percentage' ? $voucher['nilai'] . '%' : 'Rp ' . number_format($voucher['nilai'], 0, ',', '.') ?>
                                    </div>
                                    <small>Nilai Diskon</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-6 col-xl-3">
                            <div class="stat-card">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="fas fa-users fa-2x text-primary"></i>
                                    </div>
                                    <div class="ms-3">
                                        <div class="stat-number"><?= $voucher['kuota'] ?></div>
                                        <div class="text-muted">Total Kuota</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-xl-3">
                            <div class="stat-card">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="fas fa-check-circle fa-2x text-success"></i>
                                    </div>
                                    <div class="ms-3">
                                        <div class="stat-number"><?= $total_used ?></div>
                                        <div class="text-muted">Sudah Digunakan</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-xl-3">
                            <div class="stat-card">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="fas fa-clock fa-2x text-warning"></i>
                                    </div>
                                    <div class="ms-3">
                                        <div class="stat-number"><?= $remaining ?></div>
                                        <div class="text-muted">Sisa Kuota</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-xl-3">
                            <div class="stat-card">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="fas fa-money-bill-wave fa-2x text-info"></i>
                                    </div>
                                    <div class="ms-3">
                                        <div class="stat-number">Rp <?= number_format($total_discount, 0, ',', '.') ?></div>
                                        <div class="text-muted">Total Diskon</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Voucher Details -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Informasi Voucher</h5>
                                </div>
                                <div class="card-body">
                                    <table class="table table-borderless">
                                        <tr>
                                            <td><strong>Kode Voucher:</strong></td>
                                            <td><span class="badge bg-dark"><?= esc($voucher['kode_voucher']) ?></span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Tipe Diskon:</strong></td>
                                            <td>
                                                <span class="badge bg-<?= $voucher['tipe'] === 'percentage' ? 'info' : 'secondary' ?>">
                                                    <?= $voucher['tipe'] === 'percentage' ? 'Persentase' : 'Fixed Amount' ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Nilai Diskon:</strong></td>
                                            <td>
                                                <strong>
                                                    <?= $voucher['tipe'] === 'percentage' ? $voucher['nilai'] . '%' : 'Rp ' . number_format($voucher['nilai'], 0, ',', '.') ?>
                                                </strong>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Masa Berlaku:</strong></td>
                                            <td><?= date('d F Y', strtotime($voucher['masa_berlaku'])) ?></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Status:</strong></td>
                                            <td>
                                                <?php 
                                                $statusClass = [
                                                    'aktif' => 'success',
                                                    'nonaktif' => 'secondary',
                                                    'expired' => 'warning',
                                                    'habis' => 'danger'
                                                ];
                                                $class = $statusClass[$voucher['status']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?= $class ?>"><?= ucfirst($voucher['status']) ?></span>
                                            </td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <h5 class="mb-0"><i class="fas fa-chart-pie me-2"></i>Progress Penggunaan</h5>
                                </div>
                                <div class="card-body">
                                    <div class="mb-3">
                                        <div class="d-flex justify-content-between mb-2">
                                            <span>Penggunaan</span>
                                            <span><?= $total_used ?> / <?= $voucher['kuota'] ?></span>
                                        </div>
                                        <div class="progress progress-custom">
                                            <div class="progress-bar progress-bar-custom bg-success" 
                                                 style="width: <?= $voucher['kuota'] > 0 ? ($total_used / $voucher['kuota']) * 100 : 0 ?>%">
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="row text-center">
                                        <div class="col-4">
                                            <div class="h4 text-success"><?= number_format(($voucher['kuota'] > 0 ? ($total_used / $voucher['kuota']) * 100 : 0), 1) ?>%</div>
                                            <small class="text-muted">Terpakai</small>
                                        </div>
                                        <div class="col-4">
                                            <div class="h4 text-primary"><?= $remaining ?></div>
                                            <small class="text-muted">Sisa</small>
                                        </div>
                                        <div class="col-4">
                                            <div class="h4 text-info">Rp <?= number_format($total_discount, 0, ',', '.') ?></div>
                                            <small class="text-muted">Saved</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Usage History -->
                    <div class="table-container">
                        <div class="table-header">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h5 class="mb-0">
                                        <i class="fas fa-history me-2"></i>Riwayat Penggunaan
                                    </h5>
                                </div>
                                <div class="col-auto">
                                    <span class="badge bg-light text-dark"><?= count($usage_history) ?> transaksi</span>
                                </div>
                            </div>
                        </div>
                        <div class="p-4">
                            <?php if (empty($usage_history)): ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">Belum Ada Penggunaan</h5>
                                    <p class="text-muted">Voucher ini belum pernah digunakan</p>
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table id="usageTable" class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Tanggal</th>
                                                <th>User</th>
                                                <th>Event</th>
                                                <th>Total Bayar</th>
                                                <th>Diskon</th>
                                                <th>Final</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php $no = 1; foreach ($usage_history as $usage): ?>
                                            <tr>
                                                <td><?= $no++ ?></td>
                                                <td><?= date('d/m/Y H:i', strtotime($usage['tanggal_bayar'])) ?></td>
                                                <td>
                                                    <div>
                                                        <strong><?= esc($usage['nama_lengkap']) ?></strong>
                                                        <br><small class="text-muted"><?= esc($usage['email']) ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?= $usage['event_title'] ? esc($usage['event_title']) : '<span class="text-muted">-</span>' ?>
                                                </td>
                                                <td>Rp <?= number_format($usage['jumlah'] + ($usage['discount_amount'] ?? 0), 0, ',', '.') ?></td>
                                                <td>
                                                    <span class="text-success">
                                                        -Rp <?= number_format($usage['discount_amount'] ?? 0, 0, ',', '.') ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <strong>Rp <?= number_format($usage['jumlah'], 0, ',', '.') ?></strong>
                                                </td>
                                                <td>
                                                    <?php 
                                                    $statusClass = [
                                                        'verified' => 'success',
                                                        'pending' => 'warning',
                                                        'rejected' => 'danger'
                                                    ];
                                                    $class = $statusClass[$usage['status']] ?? 'secondary';
                                                    ?>
                                                    <span class="badge bg-<?= $class ?>"><?= ucfirst($usage['status']) ?></span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.13.4/js/dataTables.bootstrap5.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#usageTable').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/id.json'
                },
                order: [[1, 'desc']],
                pageLength: 25,
                responsive: true
            });

            // Animate numbers on page load
            const numbers = document.querySelectorAll('.stat-number');
            numbers.forEach(number => {
                const finalNumber = parseInt(number.textContent.replace(/[^0-9]/g, ''));
                let currentNumber = 0;
                const increment = finalNumber / 50;
                
                const timer = setInterval(() => {
                    currentNumber += increment;
                    if (currentNumber >= finalNumber) {
                        if (number.textContent.includes('Rp')) {
                            number.textContent = 'Rp ' + finalNumber.toLocaleString('id-ID');
                        } else {
                            number.textContent = finalNumber;
                        }
                        clearInterval(timer);
                    } else {
                        if (number.textContent.includes('Rp')) {
                            number.textContent = 'Rp ' + Math.floor(currentNumber).toLocaleString('id-ID');
                        } else {
                            number.textContent = Math.floor(currentNumber);
                        }
                    }
                }, 20);
            });
        });
    </script>
</body>
</html>