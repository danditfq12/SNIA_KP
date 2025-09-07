<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Dokumen - SNIA Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/datatables/1.13.4/css/dataTables.bootstrap5.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.7.12/sweetalert2.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
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
            transition: all 0.3s ease;
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

        .table-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            overflow: hidden;
            margin-top: 20px;
        }

        .table-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--info-color) 100%);
            color: white;
            padding: 20px;
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

        .filter-card {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
        }

        /* Loading overlay */
        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 9999;
            display: none;
        }

        .loading-spinner {
            border: 4px solid #f3f3f3;
            border-top: 4px solid var(--primary-color);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div id="loadingOverlay" class="loading-overlay">
        <div class="text-center text-white">
            <div class="loading-spinner"></div>
            <p class="mt-3">Memproses...</p>
        </div>
    </div>

    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 px-0">
                <div class="sidebar">
                    <div class="p-4 text-center">
                        <h4 class="text-white mb-0">
                            <i class="fas fa-cogs me-2"></i>SNIA Admin
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
                        <a class="nav-link" href="<?= site_url('admin/voucher') ?>">
                            <i class="fas fa-ticket-alt me-2"></i> Kelola Voucher
                        </a>
                        <a class="nav-link active" href="<?= site_url('admin/dokumen') ?>">
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
                    <div class="mb-4">
                        <h1 class="h3 text-primary">
                            <i class="fas fa-folder-open me-3"></i>Manajemen Dokumen
                        </h1>
                        <p class="text-muted">Kelola LOA dan Sertifikat untuk setiap event</p>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-3">
                            <div class="stat-card text-center">
                                <i class="fas fa-file-alt fa-2x text-primary mb-2"></i>
                                <div class="stat-number"><?= $stats['total_documents'] ?></div>
                                <div class="text-muted">Total Dokumen</div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="stat-card text-center">
                                <i class="fas fa-file-import fa-2x text-success mb-2"></i>
                                <div class="stat-number"><?= $stats['loa_count'] ?></div>
                                <div class="text-muted">LOA Documents</div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="stat-card text-center">
                                <i class="fas fa-certificate fa-2x text-warning mb-2"></i>
                                <div class="stat-number"><?= $stats['sertifikat_count'] ?></div>
                                <div class="text-muted">Sertifikat</div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="stat-card text-center">
                                <i class="fas fa-clock fa-2x text-info mb-2"></i>
                                <div class="stat-number"><?= $stats['recent_uploads'] ?></div>
                                <div class="text-muted">Upload Minggu Ini</div>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Section -->
                    <div class="filter-card">
                        <form method="GET" action="<?= site_url('admin/dokumen') ?>">
                            <div class="row g-3 align-items-end">
                                <div class="col-md-4">
                                    <label class="form-label">Filter Event</label>
                                    <select name="event_id" class="form-select">
                                        <option value="">-- Semua Event --</option>
                                        <?php foreach ($events as $event): ?>
                                            <option value="<?= $event['id'] ?>" <?= $current_event == $event['id'] ? 'selected' : '' ?>>
                                                <?= esc($event['title']) ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Filter Tipe</label>
                                    <select name="tipe" class="form-select">
                                        <option value="">-- Semua Tipe --</option>
                                        <option value="loa" <?= $current_tipe == 'loa' ? 'selected' : '' ?>>LOA</option>
                                        <option value="sertifikat" <?= $current_tipe == 'sertifikat' ? 'selected' : '' ?>>Sertifikat</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <button type="submit" class="btn btn-primary btn-custom me-2">
                                        <i class="fas fa-filter me-1"></i>Filter
                                    </button>
                                    <a href="<?= site_url('admin/dokumen') ?>" class="btn btn-secondary btn-custom">
                                        <i class="fas fa-times me-1"></i>Reset
                                    </a>
                                </div>
                            </div>
                        </form>
                    </div>

                    <!-- Action Buttons -->
                    <div class="row mb-4">
                        <div class="col">
                            <div class="d-flex flex-wrap gap-2">
                                <button class="btn btn-success btn-custom" data-bs-toggle="modal" data-bs-target="#uploadLoaModal">
                                    <i class="fas fa-upload me-1"></i>Upload LOA
                                </button>
                                <button class="btn btn-warning btn-custom" data-bs-toggle="modal" data-bs-target="#uploadSertifikatModal">
                                    <i class="fas fa-upload me-1"></i>Upload Sertifikat
                                </button>
                                <button class="btn btn-info btn-custom" data-bs-toggle="modal" data-bs-target="#bulkLoaModal">
                                    <i class="fas fa-magic me-1"></i>Generate Bulk LOA
                                </button>
                                <button class="btn btn-secondary btn-custom" data-bs-toggle="modal" data-bs-target="#bulkSertifikatModal">
                                    <i class="fas fa-magic me-1"></i>Generate Bulk Sertifikat
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Documents Table -->
                    <div class="table-container">
                        <div class="table-header">
                            <h5 class="mb-0">
                                <i class="fas fa-list me-2"></i>Daftar Dokumen
                                <span class="badge bg-light text-dark ms-2"><?= count($documents) ?> dokumen</span>
                            </h5>
                        </div>
                        <div class="p-4">
                            <div class="table-responsive">
                                <table id="documentsTable" class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Tipe</th>
                                            <th>User</th>
                                            <th>Event</th>
                                            <th>File</th>
                                            <th>Upload Date</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($documents)): ?>
                                            <tr>
                                                <td colspan="7" class="text-center py-4">
                                                    <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                                    <h5 class="text-muted">Belum Ada Dokumen</h5>
                                                    <p class="text-muted">Upload dokumen pertama Anda</p>
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php $no = 1; foreach ($documents as $doc): ?>
                                            <tr>
                                                <td><?= $no++ ?></td>
                                                <td>
                                                    <?php if ($doc['tipe'] == 'loa'): ?>
                                                        <span class="badge bg-success">
                                                            <i class="fas fa-file-import me-1"></i>LOA
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge bg-warning">
                                                            <i class="fas fa-certificate me-1"></i>Sertifikat
                                                        </span>
                                                    <?php endif; ?>
                                                </td>
                                                <td>
                                                    <div>
                                                        <strong><?= esc($doc['nama_lengkap'] ?? 'Unknown') ?></strong>
                                                        <br><small class="text-muted"><?= esc($doc['email'] ?? '') ?></small>
                                                        <?php if (isset($doc['role'])): ?>
                                                        <br><span class="badge bg-<?= $doc['role'] == 'presenter' ? 'primary' : 'secondary' ?>"><?= ucfirst($doc['role']) ?></span>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?= $doc['event_title'] ? '<strong>' . esc($doc['event_title']) . '</strong>' : '<span class="text-muted">-</span>' ?>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <?php
                                                        $ext = pathinfo($doc['file_path'], PATHINFO_EXTENSION);
                                                        $iconClass = 'file';
                                                        $iconColor = 'secondary';
                                                        
                                                        switch (strtolower($ext)) {
                                                            case 'pdf':
                                                                $iconClass = 'file-pdf';
                                                                $iconColor = 'danger';
                                                                break;
                                                            case 'doc':
                                                            case 'docx':
                                                                $iconClass = 'file-word';
                                                                $iconColor = 'primary';
                                                                break;
                                                            case 'jpg':
                                                            case 'jpeg':
                                                            case 'png':
                                                                $iconClass = 'file-image';
                                                                $iconColor = 'success';
                                                                break;
                                                            case 'html':
                                                                $iconClass = 'file-code';
                                                                $iconColor = 'info';
                                                                break;
                                                        }
                                                        ?>
                                                        <i class="fas fa-<?= $iconClass ?> text-<?= $iconColor ?> me-2" style="font-size: 1.5rem;"></i>
                                                        <div>
                                                            <div><?= basename($doc['file_path']) ?></div>
                                                            <small class="text-muted"><?= strtoupper($ext) ?></small>
                                                        </div>
                                                    </div>
                                                </td>
                                                <td>
                                                    <?= date('d/m/Y H:i', strtotime($doc['uploaded_at'])) ?>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="<?= site_url('admin/dokumen/download/' . $doc['id_dokumen']) ?>" 
                                                           class="btn btn-info btn-custom" title="Download">
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                        <button class="btn btn-danger btn-custom" 
                                                                onclick="deleteDocument(<?= $doc['id_dokumen'] ?>)" title="Hapus">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload LOA Modal -->
    <div class="modal fade" id="uploadLoaModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-upload me-2"></i>Upload LOA
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST" enctype="multipart/form-data" id="loaForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Event *</label>
                            <select class="form-select" name="event_id" id="loaEventId" required>
                                <option value="">-- Pilih Event --</option>
                                <?php foreach ($events as $event): ?>
                                    <option value="<?= $event['id'] ?>"><?= esc($event['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">User/Presenter *</label>
                            <select class="form-select" name="user_id" id="loaUserId" required>
                                <option value="">-- Pilih Event terlebih dahulu --</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">File LOA *</label>
                            <input type="file" class="form-control" name="loa_file" required 
                                   accept=".pdf,.doc,.docx">
                            <div class="form-text">Format: PDF, DOC, DOCX. Maksimal 5MB</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-upload me-2"></i>Upload LOA
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Upload Sertifikat Modal -->
    <div class="modal fade" id="uploadSertifikatModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title">
                        <i class="fas fa-upload me-2"></i>Upload Sertifikat
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="" method="POST" enctype="multipart/form-data" id="sertifikatForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Event *</label>
                            <select class="form-select" name="event_id" id="sertifikatEventId" required>
                                <option value="">-- Pilih Event --</option>
                                <?php foreach ($events as $event): ?>
                                    <option value="<?= $event['id'] ?>"><?= esc($event['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">User/Peserta *</label>
                            <select class="form-select" name="user_id" id="sertifikatUserId" required>
                                <option value="">-- Pilih Event terlebih dahulu --</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">File Sertifikat *</label>
                            <input type="file" class="form-control" name="sertifikat_file" required 
                                   accept=".pdf,.jpg,.jpeg,.png">
                            <div class="form-text">Format: PDF, JPG, PNG. Maksimal 5MB</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-upload me-2"></i>Upload Sertifikat
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bulk LOA Modal -->
    <div class="modal fade" id="bulkLoaModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-magic me-2"></i>Generate Bulk LOA
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="<?= site_url('admin/dokumen/generateBulkLOA') ?>" method="POST" id="bulkLoaForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Event *</label>
                            <select class="form-select" name="event_id" required>
                                <option value="">-- Pilih Event --</option>
                                <?php foreach ($events as $event): ?>
                                    <option value="<?= $event['id'] ?>"><?= esc($event['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            LOA akan di-generate untuk semua presenter yang sudah terverifikasi pembayarannya pada event yang dipilih.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-magic me-2"></i>Generate LOA
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Bulk Sertifikat Modal -->
    <div class="modal fade" id="bulkSertifikatModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-secondary text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-magic me-2"></i>Generate Bulk Sertifikat
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="<?= site_url('admin/dokumen/generateBulkSertifikat') ?>" method="POST" id="bulkSertifikatForm">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Event *</label>
                            <select class="form-select" name="event_id" required>
                                <option value="">-- Pilih Event --</option>
                                <?php foreach ($events as $event): ?>
                                    <option value="<?= $event['id'] ?>"><?= esc($event['title']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Sertifikat akan di-generate untuk semua peserta yang sudah hadir pada event yang dipilih.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-secondary">
                            <i class="fas fa-magic me-2"></i>Generate Sertifikat
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
            // Initialize DataTable
            $('#documentsTable').DataTable({
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.4/i18n/id.json'
                },
                order: [[5, 'desc']],
                pageLength: 25,
                responsive: true
            });

            // Show loading overlay function
            function showLoading() {
                $('#loadingOverlay').show();
            }

            // Hide loading overlay function
            function hideLoading() {
                $('#loadingOverlay').hide();
            }

            // Load users when event is selected for LOA
            $('#loaEventId').on('change', function() {
                const eventId = $(this).val();
                const userSelect = $('#loaUserId');
                
                userSelect.html('<option value="">Loading...</option>').prop('disabled', true);
                
                if (eventId) {
                    showLoading();
                    
                    $.get(`<?= site_url('admin/dokumen/getVerifiedPresenters/') ?>${eventId}`)
                        .done(function(response) {
                            hideLoading();
                            userSelect.prop('disabled', false);
                            
                            if (response.status === 'success') {
                                userSelect.html('<option value="">-- Pilih Presenter --</option>');
                                response.data.forEach(user => {
                                    userSelect.append(`<option value="${user.id_user}">${user.nama_lengkap} (${user.email})</option>`);
                                });
                                
                                if (response.data.length === 0) {
                                    userSelect.html('<option value="">Tidak ada presenter yang memenuhi syarat</option>');
                                }
                            } else {
                                userSelect.html('<option value="">Error loading users</option>');
                                showAlert('error', 'Error', response.message || 'Gagal memuat data presenter');
                            }
                        })
                        .fail(function(xhr) {
                            hideLoading();
                            userSelect.prop('disabled', false);
                            userSelect.html('<option value="">Error loading users</option>');
                            showAlert('error', 'Error', 'Gagal memuat data presenter');
                        });
                    
                    // Set form action
                    $('#loaForm').attr('action', `<?= site_url('admin/dokumen/uploadLoa/') ?>${eventId}`);
                } else {
                    userSelect.html('<option value="">-- Pilih Event terlebih dahulu --</option>').prop('disabled', false);
                    $('#loaForm').attr('action', '');
                }
            });

            // Load users when event is selected for Sertifikat
            $('#sertifikatEventId').on('change', function() {
                const eventId = $(this).val();
                const userSelect = $('#sertifikatUserId');
                
                userSelect.html('<option value="">Loading...</option>').prop('disabled', true);
                
                if (eventId) {
                    showLoading();
                    
                    $.get(`<?= site_url('admin/dokumen/getAttendees/') ?>${eventId}`)
                        .done(function(response) {
                            hideLoading();
                            userSelect.prop('disabled', false);
                            
                            if (response.status === 'success') {
                                userSelect.html('<option value="">-- Pilih Peserta --</option>');
                                response.data.forEach(user => {
                                    const role = user.role ? ` - ${user.role}` : '';
                                    userSelect.append(`<option value="${user.id_user}">${user.nama_lengkap} (${user.email})${role}</option>`);
                                });
                                
                                if (response.data.length === 0) {
                                    userSelect.html('<option value="">Tidak ada peserta yang memenuhi syarat</option>');
                                }
                            } else {
                                userSelect.html('<option value="">Error loading users</option>');
                                showAlert('error', 'Error', response.message || 'Gagal memuat data peserta');
                            }
                        })
                        .fail(function(xhr) {
                            hideLoading();
                            userSelect.prop('disabled', false);
                            userSelect.html('<option value="">Error loading users</option>');
                            showAlert('error', 'Error', 'Gagal memuat data peserta');
                        });
                    
                    // Set form action
                    $('#sertifikatForm').attr('action', `<?= site_url('admin/dokumen/uploadSertifikat/') ?>${eventId}`);
                } else {
                    userSelect.html('<option value="">-- Pilih Event terlebih dahulu --</option>').prop('disabled', false);
                    $('#sertifikatForm').attr('action', '');
                }
            });

            // Handle form submissions with loading
            $('#loaForm, #sertifikatForm, #bulkLoaForm, #bulkSertifikatForm').on('submit', function(e) {
                const form = $(this);
                const submitBtn = form.find('button[type="submit"]');
                const originalText = submitBtn.html();
                
                // Disable submit button and show loading
                submitBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Processing...');
                showLoading();
                
                // Re-enable after a delay (in case of redirect issues)
                setTimeout(function() {
                    submitBtn.prop('disabled', false).html(originalText);
                    hideLoading();
                }, 5000);
            });
        });

        // Show alert function
        function showAlert(type, title, text, timer = null) {
            const config = {
                icon: type,
                title: title,
                text: text,
                confirmButtonColor: type === 'success' ? '#10b981' : type === 'error' ? '#ef4444' : '#2563eb',
                showConfirmButton: !timer
            };
            
            if (timer) {
                config.timer = timer;
                config.timerProgressBar = true;
            }
            
            Swal.fire(config);
        }

        // Delete document function
        function deleteDocument(idDokumen) {
            Swal.fire({
                title: 'Hapus Dokumen?',
                text: 'File akan dihapus permanen dan tidak dapat dikembalikan!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: '<i class="fas fa-trash me-2"></i>Ya, Hapus!',
                cancelButtonText: '<i class="fas fa-times me-2"></i>Batal',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading
                    Swal.fire({
                        title: 'Menghapus...',
                        text: 'Mohon tunggu sebentar',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // Create and submit form
                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = `<?= site_url('admin/dokumen/delete/') ?>${idDokumen}`;
                    
                    // Add CSRF token if available
                    const csrfToken = $('meta[name="csrf-token"]').attr('content');
                    if (csrfToken) {
                        const tokenInput = document.createElement('input');
                        tokenInput.type = 'hidden';
                        tokenInput.name = 'csrf_token';
                        tokenInput.value = csrfToken;
                        form.appendChild(tokenInput);
                    }
                    
                    document.body.appendChild(form);
                    form.submit();
                }
            });
        }

        // Show flash messages as popups
        $(document).ready(function() {
            <?php if (session('success')): ?>
                showAlert('success', 'Berhasil!', '<?= addslashes(session('success')) ?>', 3000);
            <?php endif; ?>

            <?php if (session('error')): ?>
                showAlert('error', 'Error!', '<?= addslashes(session('error')) ?>');
            <?php endif; ?>

            <?php if (session('warning')): ?>
                showAlert('warning', 'Peringatan!', '<?= addslashes(session('warning')) ?>');
            <?php endif; ?>

            <?php if (session('info')): ?>
                showAlert('info', 'Informasi', '<?= addslashes(session('info')) ?>');
            <?php endif; ?>
        });
    </script>
</body>
</html>