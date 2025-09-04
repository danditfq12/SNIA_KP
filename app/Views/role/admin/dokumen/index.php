<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manajemen Dokumen - SNIA Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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
            overflow: hidden;
        }

        .content-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--info-color) 100%);
            color: white;
            padding: 24px;
        }

        .document-section {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-bottom: 30px;
            overflow: hidden;
        }

        .section-header {
            padding: 20px 24px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .section-title {
            font-size: 1.25rem;
            font-weight: 600;
            color: #1e293b;
        }

        .document-item {
            padding: 16px 24px;
            border-bottom: 1px solid #f1f5f9;
            transition: all 0.2s ease;
        }

        .document-item:hover {
            background: #f8fafc;
        }

        .document-item:last-child {
            border-bottom: none;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--info-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            margin-right: 12px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border-left: 4px solid;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
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

        .form-control {
            border-radius: 8px;
            border: 2px solid #e2e8f0;
            padding: 12px 16px;
            transition: all 0.3s ease;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.25);
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .document-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 12px;
        }

        .document-icon.loa {
            background: linear-gradient(135deg, var(--success-color), #059669);
            color: white;
        }

        .document-icon.certificate {
            background: linear-gradient(135deg, var(--warning-color), #d97706);
            color: white;
        }

        .pending-item {
            background: #fef3c7;
            border-left: 4px solid var(--warning-color);
            border-radius: 8px;
            padding: 16px;
            margin-bottom: 12px;
        }

        .pending-item:last-child {
            margin-bottom: 0;
        }

        .bulk-actions {
            background: #f8fafc;
            padding: 16px 24px;
            border-top: 1px solid #e2e8f0;
        }

        .file-upload-area {
            border: 2px dashed #d1d5db;
            border-radius: 8px;
            padding: 40px;
            text-align: center;
            background: #f9fafb;
            transition: all 0.3s ease;
        }

        .file-upload-area:hover {
            border-color: var(--primary-color);
            background: #eff6ff;
        }

        .file-upload-area.dragover {
            border-color: var(--primary-color);
            background: #eff6ff;
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
                    <div class="content-card mb-4">
                        <div class="content-header">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h2 class="mb-2">
                                        <i class="fas fa-folder-open me-3"></i>Manajemen Dokumen
                                    </h2>
                                    <p class="mb-0 opacity-75">Kelola LOA dan Sertifikat untuk peserta</p>
                                </div>
                                <div class="col-auto">
                                    <div class="btn-group">
                                        <button class="btn btn-light btn-custom me-2" onclick="generateBulkLOA()">
                                            <i class="fas fa-magic me-2"></i>Generate Bulk LOA
                                        </button>
                                        <button class="btn btn-outline-light btn-custom" onclick="generateBulkSertifikat()">
                                            <i class="fas fa-certificate me-2"></i>Generate Bulk Sertifikat
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-3">
                            <div class="stat-card" style="border-left-color: var(--success-color);">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="fas fa-file-signature fa-2x text-success"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0"><?= $total_loa ?? 0 ?></h3>
                                        <small class="text-muted">LOA Issued</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card" style="border-left-color: var(--warning-color);">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="fas fa-certificate fa-2x text-warning"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0"><?= $total_sertifikat ?? 0 ?></h3>
                                        <small class="text-muted">Sertifikat Issued</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card" style="border-left-color: var(--danger-color);">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="fas fa-clock fa-2x text-danger"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0"><?= $pending_loa ?? 0 ?></h3>
                                        <small class="text-muted">LOA Pending</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card" style="border-left-color: var(--info-color);">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="fas fa-hourglass-half fa-2x text-info"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0"><?= $pending_sertifikat ?? 0 ?></h3>
                                        <small class="text-muted">Sertifikat Pending</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- LOA Section -->
                    <div class="document-section">
                        <div class="section-header">
                            <div class="section-title">
                                <i class="fas fa-file-signature me-2 text-success"></i>
                                Letter of Acceptance (LOA)
                            </div>
                            <span class="badge bg-success"><?= count($loa_documents ?? []) ?> Issued</span>
                        </div>

                        <!-- Pending LOA -->
                        <?php if (!empty($need_loa)): ?>
                            <div class="p-4 border-bottom bg-light">
                                <h6 class="text-warning mb-3">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Pending LOA (<?= count($need_loa) ?>)
                                </h6>
                                <div class="row g-3">
                                    <?php foreach (array_slice($need_loa, 0, 3) as $user): ?>
                                        <div class="col-md-4">
                                            <div class="pending-item">
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <div>
                                                        <div class="fw-semibold"><?= esc($user['nama_lengkap']) ?></div>
                                                        <small class="text-muted"><?= $user['total_accepted'] ?> abstrak diterima</small>
                                                    </div>
                                                    <button class="btn btn-sm btn-success" onclick="uploadLOA(<?= $user['id_user'] ?>, '<?= esc($user['nama_lengkap']) ?>')">
                                                        <i class="fas fa-upload"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if (count($need_loa) > 3): ?>
                                        <div class="col-12 text-center">
                                            <small class="text-muted">+<?= count($need_loa) - 3 ?> lainnya</small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- LOA Documents -->
                        <div class="p-0">
                            <?php if (!empty($loa_documents)): ?>
                                <?php foreach ($loa_documents as $doc): ?>
                                    <div class="document-item">
                                        <div class="d-flex align-items-center">
                                            <div class="document-icon loa">
                                                <i class="fas fa-file-signature"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="fw-semibold"><?= esc($doc['nama_lengkap']) ?></div>
                                                <div class="text-muted small">
                                                    <?= esc($doc['email']) ?> • <?= date('d M Y', strtotime($doc['uploaded_at'])) ?>
                                                </div>
                                            </div>
                                            <div class="d-flex gap-2">
                                                <a href="<?= site_url('admin/dokumen/download/' . $doc['id_dokumen']) ?>" 
                                                   class="btn btn-outline-primary btn-sm btn-custom">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                                <button class="btn btn-outline-danger btn-sm btn-custom" 
                                                        onclick="deleteDokumen(<?= $doc['id_dokumen'] ?>, 'LOA')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-file-signature"></i>
                                    <h5>Belum Ada LOA</h5>
                                    <p>LOA akan muncul setelah abstrak presenter diterima</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Sertifikat Section -->
                    <div class="document-section">
                        <div class="section-header">
                            <div class="section-title">
                                <i class="fas fa-certificate me-2 text-warning"></i>
                                Sertifikat Keikutsertaan
                            </div>
                            <span class="badge bg-warning"><?= count($sertifikat_documents ?? []) ?> Issued</span>
                        </div>

                        <!-- Pending Sertifikat -->
                        <?php if (!empty($need_sertifikat)): ?>
                            <div class="p-4 border-bottom bg-light">
                                <h6 class="text-warning mb-3">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Pending Sertifikat (<?= count($need_sertifikat) ?>)
                                </h6>
                                <div class="row g-3">
                                    <?php foreach (array_slice($need_sertifikat, 0, 3) as $user): ?>
                                        <div class="col-md-4">
                                            <div class="pending-item">
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <div>
                                                        <div class="fw-semibold"><?= esc($user['nama_lengkap']) ?></div>
                                                        <small class="text-muted"><?= ucfirst($user['role']) ?> • Pembayaran verified</small>
                                                    </div>
                                                    <button class="btn btn-sm btn-warning" onclick="uploadSertifikat(<?= $user['id_user'] ?>, '<?= esc($user['nama_lengkap']) ?>')">
                                                        <i class="fas fa-upload"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                    <?php if (count($need_sertifikat) > 3): ?>
                                        <div class="col-12 text-center">
                                            <small class="text-muted">+<?= count($need_sertifikat) - 3 ?> lainnya</small>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- Sertifikat Documents -->
                        <div class="p-0">
                            <?php if (!empty($sertifikat_documents)): ?>
                                <?php foreach ($sertifikat_documents as $doc): ?>
                                    <div class="document-item">
                                        <div class="d-flex align-items-center">
                                            <div class="document-icon certificate">
                                                <i class="fas fa-certificate"></i>
                                            </div>
                                            <div class="flex-grow-1">
                                                <div class="fw-semibold"><?= esc($doc['nama_lengkap']) ?></div>
                                                <div class="text-muted small">
                                                    <?= esc($doc['email']) ?> • <?= ucfirst($doc['role']) ?> • <?= date('d M Y', strtotime($doc['uploaded_at'])) ?>
                                                </div>
                                            </div>
                                            <div class="d-flex gap-2">
                                                <a href="<?= site_url('admin/dokumen/download/' . $doc['id_dokumen']) ?>" 
                                                   class="btn btn-outline-primary btn-sm btn-custom">
                                                    <i class="fas fa-download"></i>
                                                </a>
                                                <button class="btn btn-outline-danger btn-sm btn-custom" 
                                                        onclick="deleteDokumen(<?= $doc['id_dokumen'] ?>, 'Sertifikat')">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-certificate"></i>
                                    <h5>Belum Ada Sertifikat</h5>
                                    <p>Sertifikat akan muncul setelah pembayaran peserta terverifikasi</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload LOA Modal -->
    <div class="modal fade" id="uploadLOAModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-file-signature me-2"></i>Upload LOA
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="uploadLOAForm" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Presenter</label>
                            <input type="text" class="form-control" id="loaPresenterName" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Upload File LOA (PDF)</label>
                            <div class="file-upload-area" onclick="document.getElementById('loaFile').click()">
                                <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">Klik untuk pilih file atau drag & drop</p>
                                <small class="text-muted">PDF maksimal 5MB</small>
                            </div>
                            <input type="file" class="form-control d-none" name="loa_file" id="loaFile" accept=".pdf" required>
                        </div>
                        <div id="loaFileName" class="text-success small" style="display: none;"></div>
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
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-certificate me-2"></i>Upload Sertifikat
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="uploadSertifikatForm" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Peserta</label>
                            <input type="text" class="form-control" id="sertifikatUserName" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Upload File Sertifikat (PDF)</label>
                            <div class="file-upload-area" onclick="document.getElementById('sertifikatFile').click()">
                                <i class="fas fa-cloud-upload-alt fa-2x text-muted mb-2"></i>
                                <p class="text-muted mb-0">Klik untuk pilih file atau drag & drop</p>
                                <small class="text-muted">PDF maksimal 5MB</small>
                            </div>
                            <input type="file" class="form-control d-none" name="sertifikat_file" id="sertifikatFile" accept=".pdf" required>
                        </div>
                        <div id="sertifikatFileName" class="text-success small" style="display: none;"></div>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function uploadLOA(userId, userName) {
            document.getElementById('loaPresenterName').value = userName;
            document.getElementById('uploadLOAForm').action = `<?= site_url('admin/dokumen/uploadLoa') ?>/${userId}`;
            new bootstrap.Modal(document.getElementById('uploadLOAModal')).show();
        }

        function uploadSertifikat(userId, userName) {
            document.getElementById('sertifikatUserName').value = userName;
            document.getElementById('uploadSertifikatForm').action = `<?= site_url('admin/dokumen/uploadSertifikat') ?>/${userId}`;
            new bootstrap.Modal(document.getElementById('uploadSertifikatModal')).show();
        }

        function deleteDokumen(dokumenId, tipe) {
            Swal.fire({
                title: `Hapus ${tipe}?`,
                text: `Apakah Anda yakin ingin menghapus ${tipe} ini?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `<?= site_url('admin/dokumen/delete') ?>/${dokumenId}`;
                }
            });
        }

        function generateBulkLOA() {
            Swal.fire({
                title: 'Generate Bulk LOA?',
                text: 'Sistem akan membuat LOA untuk semua presenter dengan abstrak yang diterima',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Generate!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Generating LOA...',
                        html: 'Mohon tunggu sebentar',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    setTimeout(() => {
                        window.location.href = '<?= site_url('admin/dokumen/generateBulkLOA') ?>';
                    }, 1000);
                }
            });
        }

        function generateBulkSertifikat() {
            Swal.fire({
                title: 'Generate Bulk Sertifikat?',
                text: 'Sistem akan membuat sertifikat untuk semua peserta dengan pembayaran terverifikasi',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#f59e0b',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Generate!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'Generating Sertifikat...',
                        html: 'Mohon tunggu sebentar',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    setTimeout(() => {
                        window.location.href = '<?= site_url('admin/dokumen/generateBulkSertifikat') ?>';
                    }, 1000);
                }
            });
        }

        // File upload handling
        document.getElementById('loaFile').addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                document.getElementById('loaFileName').textContent = `File selected: ${file.name}`;
                document.getElementById('loaFileName').style.display = 'block';
            }
        });

        document.getElementById('sertifikatFile').addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                document.getElementById('sertifikatFileName').textContent = `File selected: ${file.name}`;
                document.getElementById('sertifikatFileName').style.display = 'block';
            }
        });

        // Drag and drop handling
        function setupDragDrop(uploadArea, fileInput) {
            uploadArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('dragover');
            });

            uploadArea.addEventListener('dragleave', function(e) {
                e.preventDefault();
                this.classList.remove('dragover');
            });

            uploadArea.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('dragover');
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    fileInput.files = files;
                    fileInput.dispatchEvent(new Event('change'));
                }
            });
        }

        // Setup drag and drop for both upload areas
        setupDragDrop(document.querySelector('#uploadLOAModal .file-upload-area'), document.getElementById('loaFile'));
        setupDragDrop(document.querySelector('#uploadSertifikatModal .file-upload-area'), document.getElementById('sertifikatFile'));

        // Show alerts
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

            <?php if (session('errors')): ?>
                let errorMessages = '';
                <?php foreach (session('errors') as $error): ?>
                    errorMessages += '<?= $error ?>\n';
                <?php endforeach; ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error!',
                    text: errorMessages,
                });
            <?php endif; ?>

            // Animate elements
            const elements = document.querySelectorAll('.document-section, .stat-card');
            elements.forEach((el, index) => {
                setTimeout(() => {
                    el.style.opacity = '0';
                    el.style.transform = 'translateY(20px)';
                    el.style.transition = 'all 0.5s ease';
                    
                    setTimeout(() => {
                        el.style.opacity = '1';
                        el.style.transform = 'translateY(0)';
                    }, 100);
                }, index * 100);
            });
        });
    </script>
</body>
</html>