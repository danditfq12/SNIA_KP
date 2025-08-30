<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Voucher - SNIA Admin</title>
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

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1e293b;
            margin: 16px 0 8px 0;
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

        .table-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            overflow: hidden;
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

        .form-control, .form-select {
            border-radius: 8px;
            border: 2px solid #e2e8f0;
            padding: 12px 16px;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.25);
        }

        .voucher-code {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            font-size: 1.1rem;
            letter-spacing: 2px;
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
                        <a class="nav-link" href="<?= site_url('admin/event') ?>">
                            <i class="fas fa-calendar me-2"></i> Kelola Event
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
            <div class="col-md-9 col-lg-10">
                <div class="main-content p-4">
                    <!-- Header -->
                    <div class="header-section">
                        <div class="row align-items-center">
                            <div class="col">
                                <h1 class="welcome-text">
                                    <i class="fas fa-ticket-alt me-3"></i>Kelola Voucher
                                </h1>
                                <p class="text-muted mb-0">
                                    Kelola voucher diskon untuk pembayaran registrasi event
                                </p>
                            </div>
                            <div class="col-auto">
                                <div class="text-end">
                                    <small class="text-muted d-block">Terakhir update</small>
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
                                    <div class="me-3">
                                        <i class="fas fa-ticket-alt fa-2x text-primary"></i>
                                    </div>
                                    <div class="ms-3">
                                        <div class="stat-number"><?= $total_vouchers ?? 0 ?></div>
                                        <div class="text-muted">Total Voucher</div>
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
                                        <div class="stat-number"><?= $active_vouchers ?? 0 ?></div>
                                        <div class="text-muted">Voucher Aktif</div>
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
                                        <div class="stat-number"><?= $expired_vouchers ?? 0 ?></div>
                                        <div class="text-muted">Expired</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-xl-3">
                            <div class="stat-card">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="fas fa-ban fa-2x text-danger"></i>
                                    </div>
                                    <div class="ms-3">
                                        <div class="stat-number"><?= $used_vouchers ?? 0 ?></div>
                                        <div class="text-muted">Kuota Habis</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Content Table -->
                    <div class="table-container">
                        <div class="table-header">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h5 class="mb-0">
                                        <i class="fas fa-list me-2"></i>Daftar Voucher
                                    </h5>
                                </div>
                                <div class="col-auto">
                                    <button class="btn btn-light btn-custom" data-bs-toggle="modal" data-bs-target="#createModal">
                                        <i class="fas fa-plus me-1"></i>Tambah Voucher
                                    </button>
                                    <button class="btn btn-light btn-custom" onclick="generateCode()">
                                        <i class="fas fa-magic me-1"></i>Generate Kode
                                    </button>
                                    <button class="btn btn-light btn-custom" onclick="exportData()">
                                        <i class="fas fa-download me-1"></i>Export
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="p-4">
                            <div class="table-responsive">
                                <table id="voucherTable" class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Kode Voucher</th>
                                            <th>Tipe</th>
                                            <th>Nilai</th>
                                            <th>Kuota</th>
                                            <th>Digunakan</th>
                                            <th>Sisa</th>
                                            <th>Masa Berlaku</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $no = 1; foreach ($vouchers as $voucher): ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td>
                                                <span class="voucher-code badge bg-dark"><?= esc($voucher['kode_voucher']) ?></span>
                                            </td>
                                            <td>
                                                <?php if ($voucher['tipe'] === 'percentage'): ?>
                                                    <span class="badge bg-info">Persentase</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Fixed Amount</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($voucher['tipe'] === 'percentage'): ?>
                                                    <strong><?= $voucher['nilai'] ?>%</strong>
                                                <?php else: ?>
                                                    <strong>Rp <?= number_format($voucher['nilai'], 0, ',', '.') ?></strong>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= $voucher['kuota'] ?></td>
                                            <td><?= $voucher['used_count'] ?></td>
                                            <td>
                                                <?php $remaining = $voucher['remaining']; ?>
                                                <span class="badge bg-<?= $remaining > 0 ? 'success' : 'danger' ?>">
                                                    <?= $remaining ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?= date('d/m/Y', strtotime($voucher['masa_berlaku'])) ?>
                                                <?php if ($voucher['is_expired']): ?>
                                                    <br><small class="text-danger"><i class="fas fa-clock"></i> Expired</small>
                                                <?php endif; ?>
                                            </td>
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
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-info btn-custom" onclick="viewDetail(<?= $voucher['id_voucher'] ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-warning btn-custom" onclick="editVoucher(<?= $voucher['id_voucher'] ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <?php if (!in_array($voucher['status'], ['expired', 'habis'])): ?>
                                                    <button class="btn btn-<?= $voucher['status'] === 'aktif' ? 'secondary' : 'success' ?> btn-custom" 
                                                            onclick="toggleStatus(<?= $voucher['id_voucher'] ?>)">
                                                        <i class="fas fa-<?= $voucher['status'] === 'aktif' ? 'pause' : 'play' ?>"></i>
                                                    </button>
                                                    <?php endif; ?>
                                                    <button class="btn btn-danger btn-custom" onclick="deleteVoucher(<?= $voucher['id_voucher'] ?>)">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Voucher Modal -->
    <div class="modal fade" id="createModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Tambah Voucher Baru
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="/admin/voucher/store" method="POST">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Kode Voucher *</label>
                                    <div class="input-group">
                                        <input type="text" class="form-control" name="kode_voucher" id="createKode" 
                                               placeholder="Masukkan kode voucher" required maxlength="50">
                                        <button type="button" class="btn btn-outline-secondary" onclick="generateCreateCode()">
                                            <i class="fas fa-magic"></i>
                                        </button>
                                    </div>
                                    <div class="form-text">Kode harus unik, huruf besar</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Tipe Diskon *</label>
                                    <select class="form-select" name="tipe" id="createTipe" required>
                                        <option value="">-- Pilih Tipe --</option>
                                        <option value="percentage">Persentase (%)</option>
                                        <option value="fixed">Fixed Amount (Rp)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nilai Diskon *</label>
                                    <div class="input-group">
                                        <span class="input-group-text" id="createPrefix">%</span>
                                        <input type="number" class="form-control" name="nilai" id="createNilai" 
                                               placeholder="0" required min="1">
                                    </div>
                                    <div class="form-text" id="createHelp">Maksimal 100 untuk persentase</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Kuota Penggunaan *</label>
                                    <input type="number" class="form-control" name="kuota" placeholder="100" required min="1">
                                    <div class="form-text">Jumlah maksimal penggunaan</div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Masa Berlaku *</label>
                            <input type="date" class="form-control" name="masa_berlaku" required min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                            <div class="form-text">Tanggal terakhir voucher dapat digunakan</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save me-2"></i>Simpan Voucher
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Edit Voucher Modal -->
    <div class="modal fade" id="editModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Edit Voucher
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST" id="editForm">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Kode Voucher *</label>
                                    <input type="text" class="form-control" name="kode_voucher" id="editKode" required maxlength="50">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Tipe Diskon *</label>
                                    <select class="form-select" name="tipe" id="editTipe" required>
                                        <option value="percentage">Persentase (%)</option>
                                        <option value="fixed">Fixed Amount (Rp)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nilai Diskon *</label>
                                    <div class="input-group">
                                        <span class="input-group-text" id="editPrefix">%</span>
                                        <input type="number" class="form-control" name="nilai" id="editNilai" required min="1">
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Kuota Penggunaan *</label>
                                    <input type="number" class="form-control" name="kuota" id="editKuota" required min="1">
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Masa Berlaku *</label>
                                    <input type="date" class="form-control" name="masa_berlaku" id="editMasaBerlaku" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Status *</label>
                                    <select class="form-select" name="status" id="editStatus" required>
                                        <option value="aktif">Aktif</option>
                                        <option value="nonaktif">Nonaktif</option>
                                        <option value="expired">Expired</option>
                                        <option value="habis">Habis</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-save me-2"></i>Update Voucher
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.13.4/js/jquery.dataTables.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.13.4/js/dataTables.bootstrap5.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.7.12/sweetalert2.min.js"></script>

    <script>
        $(document).ready(function() {
            $('#voucherTable').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/id.json'
                },
                order: [[7, 'desc']],
                pageLength: 25,
                responsive: true
            });

            // Handle tipe change
            $('#createTipe, #editTipe').on('change', function() {
                const isCreate = $(this).attr('id') === 'createTipe';
                const prefix = isCreate ? 'create' : 'edit';
                const tipe = $(this).val();
                
                if (tipe === 'percentage') {
                    $('#' + prefix + 'Prefix').text('%');
                    $('#' + prefix + 'Nilai').attr('max', '100');
                    if (isCreate) $('#' + prefix + 'Help').text('Maksimal 100 untuk persentase');
                } else if (tipe === 'fixed') {
                    $('#' + prefix + 'Prefix').text('Rp');
                    $('#' + prefix + 'Nilai').removeAttr('max');
                    if (isCreate) $('#' + prefix + 'Help').text('Nominal dalam rupiah');
                }
            });

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
        });

        function generateCreateCode() {
            $.get('/admin/voucher/generate-code', function(data) {
                $('#createKode').val(data.code);
            });
        }

        function generateCode() {
            Swal.fire({
                title: 'Generate Kode Voucher',
                text: 'Kode akan otomatis dibuat secara random',
                icon: 'info',
                showCancelButton: true,
                confirmButtonText: 'Generate',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.get('/admin/voucher/generate-code', function(data) {
                        Swal.fire('Berhasil!', 'Kode: ' + data.code, 'success');
                    });
                }
            });
        }

        function viewDetail(idVoucher) {
            window.open('/admin/voucher/detail/' + idVoucher, '_blank');
        }

        function editVoucher(idVoucher) {
            $.get('/admin/voucher/edit/' + idVoucher, function(data) {
                $('#editKode').val(data.kode_voucher);
                $('#editTipe').val(data.tipe).trigger('change');
                $('#editNilai').val(data.nilai);
                $('#editKuota').val(data.kuota);
                $('#editMasaBerlaku').val(data.masa_berlaku);
                $('#editStatus').val(data.status);
                $('#editForm').attr('action', '/admin/voucher/update/' + idVoucher);
                $('#editModal').modal('show');
            });
        }

        function toggleStatus(idVoucher) {
            Swal.fire({
                title: 'Ubah Status Voucher?',
                text: 'Status voucher akan diubah',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Ya, Ubah!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '/admin/voucher/toggle-status/' + idVoucher;
                }
            });
        }

        function deleteVoucher(idVoucher) {
            Swal.fire({
                title: 'Hapus Voucher?',
                text: 'Data tidak dapat dikembalikan setelah dihapus!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '/admin/voucher/delete/' + idVoucher;
                }
            });
        }

        function exportData() {
            window.open('/admin/voucher/export', '_blank');
        }

        // Form validation
        $('input[name="kode_voucher"]').on('input', function() {
            $(this).val($(this).val().toUpperCase());
        });

        // Show alerts for flash messages
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
    </script>
</body>
</html>