<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Abstrak - SNIA Admin</title>
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
                        <a class="nav-link active" href="<?= site_url('admin/abstrak') ?>">
                            <i class="fas fa-file-alt me-2"></i> Manajemen Abstrak
                        </a>
                        <a class="nav-link" href="<?= site_url('admin/reviewer') ?>">
                            <i class="fas fa-user-check me-2"></i> Kelola Reviewer
                        </a>
                        <a class="nav-link" href="<?= site_url('admin/pembayaran') ?>">
                            <i class="fas fa-credit-card me-2"></i> Verifikasi Pembayaran
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
                    <div class="header-section">
                        <div class="row align-items-center">
                            <div class="col">
                                <h1 class="welcome-text">
                                    <i class="fas fa-file-alt me-3"></i>Kelola Abstrak
                                </h1>
                                <p class="text-muted mb-0">
                                    Kelola dan review semua abstrak yang masuk ke sistem SNIA
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
                                        <i class="fas fa-file-alt fa-2x text-primary"></i>
                                    </div>
                                    <div class="ms-3">
                                        <div class="stat-number"><?= $total_abstrak ?? 0 ?></div>
                                        <div class="text-muted">Total Abstrak</div>
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
                                        <div class="stat-number"><?= $abstrak_pending ?? 0 ?></div>
                                        <div class="text-muted">Menunggu Review</div>
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
                                        <div class="stat-number"><?= $abstrak_diterima ?? 0 ?></div>
                                        <div class="text-muted">Diterima</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-xl-3">
                            <div class="stat-card">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="fas fa-times-circle fa-2x text-danger"></i>
                                    </div>
                                    <div class="ms-3">
                                        <div class="stat-number"><?= $abstrak_ditolak ?? 0 ?></div>
                                        <div class="text-muted">Ditolak</div>
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
                                        <i class="fas fa-list me-2"></i>Daftar Abstrak
                                    </h5>
                                </div>
                                <div class="col-auto">
                                    <button class="btn btn-light btn-custom" onclick="exportData()">
                                        <i class="fas fa-download me-1"></i>Export
                                    </button>
                                    <button class="btn btn-light btn-custom" onclick="refreshTable()">
                                        <i class="fas fa-refresh me-1"></i>Refresh
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="p-4">
                            <div class="table-responsive">
                                <table id="abstrakTable" class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Judul</th>
                                            <th>Penulis</th>
                                            <th>Kategori</th>
                                            <th>Status</th>
                                            <th>Tanggal Upload</th>
                                            <th>Reviewer</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $no = 1; foreach ($abstraks as $abstrak): ?>
                                        <tr>
                                            <td><?= $no++ ?></td>
                                            <td>
                                                <div class="fw-bold"><?= esc($abstrak['judul']) ?></div>
                                                <small class="text-muted">Revisi ke-<?= $abstrak['revisi_ke'] ?></small>
                                            </td>
                                            <td>
                                                <div><?= esc($abstrak['nama_lengkap']) ?></div>
                                                <small class="text-muted"><?= esc($abstrak['email']) ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-info"><?= esc($abstrak['nama_kategori']) ?></span>
                                            </td>
                                            <td>
                                                <?php 
                                                $statusClass = [
                                                    'menunggu' => 'warning',
                                                    'sedang_direview' => 'info', 
                                                    'diterima' => 'success',
                                                    'ditolak' => 'danger',
                                                    'revisi' => 'secondary'
                                                ];
                                                $class = $statusClass[$abstrak['status']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?= $class ?>"><?= ucfirst($abstrak['status']) ?></span>
                                            </td>
                                            <td><?= date('d/m/Y H:i', strtotime($abstrak['tanggal_upload'])) ?></td>
                                            <td>
                                                <?php if ($abstrak['status'] === 'menunggu'): ?>
                                                    <button class="btn btn-outline-primary btn-sm btn-custom" 
                                                            onclick="showAssignModal(<?= $abstrak['id_abstrak'] ?>, '<?= esc($abstrak['judul']) ?>', <?= $abstrak['id_kategori'] ?>)">
                                                        <i class="fas fa-user-plus me-1"></i>Assign
                                                    </button>
                                                <?php else: ?>
                                                    <small class="text-success"><i class="fas fa-check"></i> Assigned</small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-info btn-custom" onclick="viewDetail(<?= $abstrak['id_abstrak'] ?>)">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <button class="btn btn-primary btn-custom" onclick="downloadFile(<?= $abstrak['id_abstrak'] ?>)">
                                                        <i class="fas fa-download"></i>
                                                    </button>
                                                    <button class="btn btn-warning btn-custom" onclick="updateStatus(<?= $abstrak['id_abstrak'] ?>)">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-danger btn-custom" onclick="deleteAbstrak(<?= $abstrak['id_abstrak'] ?>)">
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

    <!-- Assign Reviewer Modal -->
    <div class="modal fade" id="assignModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-plus me-2"></i>Assign Reviewer
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST" id="assignForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Judul Abstrak</label>
                            <input type="text" class="form-control" id="abstrakTitle" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Pilih Reviewer</label>
                            <select class="form-select" name="id_reviewer" id="reviewerSelect" required>
                                <option value="">-- Pilih Reviewer --</option>
                                <?php foreach ($reviewers as $reviewer): ?>
                                <option value="<?= $reviewer['id_user'] ?>"><?= esc($reviewer['nama_lengkap']) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text">Hanya reviewer yang sesuai kategori akan ditampilkan.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Assign Reviewer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Update Status Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>Update Status Abstrak
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="statusForm">
                    <div class="modal-body">
                        <input type="hidden" id="statusAbstrakId">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" id="statusSelect" required>
                                <option value="menunggu">Menunggu</option>
                                <option value="sedang_direview">Sedang Review</option>
                                <option value="diterima">Diterima</option>
                                <option value="ditolak">Ditolak</option>
                                <option value="revisi">Perlu Revisi</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Komentar</label>
                            <textarea class="form-control" id="statusKomentar" rows="3" placeholder="Komentar admin (opsional)"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Status
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
            $('#abstrakTable').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/id.json'
                },
                order: [[5, 'desc']],
                pageLength: 25,
                responsive: true
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

        function showAssignModal(idAbstrak, judul, idKategori) {
            $('#abstrakTitle').val(judul);
            $('#assignForm').attr('action', '/admin/abstrak/assign/' + idAbstrak);
            
            // Filter reviewer berdasarkan kategori
            $.get('/admin/reviewer/by-category/' + idKategori, function(data) {
                let options = '<option value="">-- Pilih Reviewer --</option>';
                data.forEach(function(reviewer) {
                    options += `<option value="${reviewer.id_user}">${reviewer.nama_lengkap}</option>`;
                });
                $('#reviewerSelect').html(options);
            });
            
            $('#assignModal').modal('show');
        }

        function viewDetail(idAbstrak) {
            window.open('/admin/abstrak/detail/' + idAbstrak, '_blank');
        }

        function downloadFile(idAbstrak) {
            window.open('/admin/abstrak/download/' + idAbstrak, '_blank');
        }

        function updateStatus(idAbstrak) {
            $('#statusAbstrakId').val(idAbstrak);
            $('#statusModal').modal('show');
        }

        function deleteAbstrak(idAbstrak) {
            Swal.fire({
                title: 'Hapus Abstrak?',
                text: 'Data tidak dapat dikembalikan setelah dihapus!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '/admin/abstrak/delete/' + idAbstrak;
                }
            });
        }

        $('#statusForm').on('submit', function(e) {
            e.preventDefault();
            
            $.ajax({
                url: '/admin/abstrak/update-status',
                method: 'POST',
                data: {
                    id_abstrak: $('#statusAbstrakId').val(),
                    status: $('#statusSelect').val(),
                    komentar: $('#statusKomentar').val()
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire('Berhasil!', response.message, 'success').then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire('Gagal!', response.message, 'error');
                    }
                    $('#statusModal').modal('hide');
                },
                error: function() {
                    Swal.fire('Error!', 'Terjadi kesalahan sistem', 'error');
                }
            });
        });

        function refreshTable() {
            location.reload();
        }

        function exportData() {
            window.open('/admin/abstrak/export', '_blank');
        }

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