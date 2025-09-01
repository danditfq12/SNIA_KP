<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Presenter Dashboard - SNIA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
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

        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--info-color));
            color: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(37, 99, 235, 0.3);
        }

        .card {
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            border: 1px solid rgba(255,255,255,0.2);
            overflow: hidden;
        }

        .card-header {
            padding: 20px;
            font-weight: 600;
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

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: 250px;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }

            .mobile-toggle {
                display: block;
                position: fixed;
                top: 20px;
                left: 20px;
                z-index: 1001;
            }
        }

        .mobile-toggle {
            display: none;
        }

        /* Existing styles for specific pages */
        .file-upload-area {
            border: 2px dashed #dee2e6;
            border-radius: 8px;
            padding: 2rem;
            text-align: center;
            transition: all 0.3s ease;
            background: #f8f9fa;
        }
        .file-upload-area:hover {
            border-color: #0d6efd;
            background: #e7f1ff;
        }
        .file-upload-area.dragover {
            border-color: #0d6efd;
            background: #e7f1ff;
        }
        .progress-container {
            display: none;
            margin-top: 1rem;
        }
        .file-info {
            display: none;
            margin-top: 1rem;
            padding: 1rem;
            background: #e8f5e8;
            border-radius: 6px;
            border-left: 4px solid #198754;
        }
        .error-info {
            display: none;
            margin-top: 1rem;
            padding: 1rem;
            background: #f8d7da;
            border-radius: 6px;
            border-left: 4px solid #dc3545;
        }

        .review-card {
            border-left: 4px solid;
        }
        .review-accepted {
            border-left-color: #28a745;
        }
        .review-rejected {
            border-left-color: #dc3545;
        }
        .review-pending {
            border-left-color: #ffc107;
        }
        .timeline-item {
            border-left: 2px solid #dee2e6;
            padding-left: 20px;
            margin-bottom: 20px;
            position: relative;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -8px;
            top: 0;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background-color: #dee2e6;
        }
        .timeline-item.accepted::before {
            background-color: #28a745;
        }
        .timeline-item.rejected::before {
            background-color: #dc3545;
        }
        .timeline-item.pending::before {
            background-color: #ffc107;
        }
    </style>
</head>
<body>
    <!-- Mobile Toggle Button -->
    <button class="btn btn-primary mobile-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="p-4 text-center">
            <h4 class="text-white mb-0">
                <i class="fas fa-microphone-alt me-2"></i>
                SNIA Presenter
            </h4>
            <small class="text-white-50">Dashboard</small>
        </div>
        
        <nav class="nav flex-column px-3">
            <a class="nav-link" href="<?= site_url('presenter/dashboard') ?>">
                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
            </a>
            <a class="nav-link" href="<?= site_url('presenter/events') ?>">
                <i class="fas fa-calendar me-2"></i> Events
            </a>
            <a class="nav-link active" href="<?= site_url('presenter/abstrak') ?>">
                <i class="fas fa-file-alt me-2"></i> My Abstracts
            </a>
            <a class="nav-link" href="<?= site_url('presenter/pembayaran') ?>">
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
        <!-- Page Header -->
        <div class="page-header animate__animated animate__fadeInDown">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="mb-2">
                        <i class="fas fa-file-alt me-2"></i>
                        Manajemen Abstrak
                    </h2>
                    <p class="mb-0 opacity-90">
                        Submit dan kelola abstrak presentasi Anda untuk berbagai event SNIA.
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <a href="<?= site_url('presenter/abstrak/status') ?>" class="btn btn-light">
                        <i class="fas fa-chart-line me-1"></i>Lihat Status
                    </a>
                </div>
            </div>
        </div>

        <!-- Display Flash Messages -->
        <?php if (session('success')): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle me-2"></i>
                <?= session('success') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (session('error')): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-circle me-2"></i>
                <?= session('error') ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if (session('errors')): ?>
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle me-2"></i>
                <strong>Validation Errors:</strong>
                <ul class="mb-0 mt-2">
                    <?php foreach (session('errors') as $error): ?>
                        <li><?= $error ?></li>
                    <?php endforeach; ?>
                </ul>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Submit New Abstract -->
        <?php if (!empty($activeEvents)): ?>
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-plus me-2"></i>Submit Abstrak Baru (PDF Only)
                    </h5>
                </div>
                <div class="card-body">
                    <form action="<?= site_url('presenter/abstrak/upload') ?>" method="post" enctype="multipart/form-data" id="uploadForm">
                        <?= csrf_field() ?>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Event <span class="text-danger">*</span></label>
                                    <select name="event_id" class="form-select" required>
                                        <option value="">Pilih Event</option>
                                        <?php foreach ($activeEvents as $event): ?>
                                            <option value="<?= $event['id'] ?>" <?= old('event_id') == $event['id'] ? 'selected' : '' ?>>
                                                <?= $event['title'] ?> 
                                                (<?= date('d/m/Y', strtotime($event['event_date'])) ?>)
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Kategori <span class="text-danger">*</span></label>
                                    <select name="id_kategori" class="form-select" required>
                                        <option value="">Pilih Kategori</option>
                                        <?php foreach ($kategori as $kat): ?>
                                            <option value="<?= $kat['id_kategori'] ?>" <?= old('id_kategori') == $kat['id_kategori'] ? 'selected' : '' ?>>
                                                <?= $kat['nama_kategori'] ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Judul Abstrak <span class="text-danger">*</span></label>
                            <input type="text" name="judul" class="form-control" required 
                                   placeholder="Masukkan judul abstrak Anda (minimal 10 karakter)"
                                   value="<?= old('judul') ?>" minlength="10" maxlength="255">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">File Abstrak (PDF Only) <span class="text-danger">*</span></label>
                            <div class="file-upload-area" id="fileUploadArea">
                                <i class="fas fa-file-pdf fa-3x text-danger mb-3"></i>
                                <h5>Drag & Drop PDF atau Klik untuk Upload</h5>
                                <p class="text-muted">Hanya file PDF yang diperbolehkan. Maksimal 5MB</p>
                                <input type="file" name="file_abstrak" class="form-control d-none" 
                                       id="fileInput" accept=".pdf" required>
                                <button type="button" class="btn btn-outline-primary" onclick="document.getElementById('fileInput').click()">
                                    <i class="fas fa-upload me-1"></i>Pilih File PDF
                                </button>
                            </div>
                            <div class="file-info" id="fileInfo">
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-file-pdf text-danger me-2"></i>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold" id="fileName"></div>
                                        <small class="text-muted" id="fileSize"></small>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeFile()">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="error-info" id="errorInfo">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                <span id="errorMessage"></span>
                            </div>
                            <div class="progress-container" id="progressContainer">
                                <div class="progress">
                                    <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                                </div>
                                <small class="text-muted mt-1">Uploading...</small>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary btn-custom" id="submitBtn">
                            <i class="fas fa-upload me-1"></i>Upload Abstrak
                        </button>
                        <button type="reset" class="btn btn-outline-secondary btn-custom ms-2">
                            <i class="fas fa-undo me-1"></i>Reset
                        </button>
                    </form>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i>
                Belum ada event aktif yang membuka submission abstrak.
            </div>
        <?php endif; ?>

        <!-- Abstract List -->
        <div class="card shadow-sm">
            <div class="card-header bg-info text-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">
                    <i class="fas fa-list me-2"></i>Daftar Abstrak Anda
                </h5>
                <a href="<?= site_url('presenter/abstrak/status') ?>" class="btn btn-light btn-sm">
                    <i class="fas fa-chart-line me-1"></i>Lihat Status Detail
                </a>
            </div>
            <div class="card-body">
                <?php if (!empty($abstraks)): ?>
                    <?php foreach ($abstraks as $abstrak): ?>
                        <div class="card mb-3 border-start border-4 border-<?= $abstrak['status'] == 'diterima' ? 'success' : ($abstrak['status'] == 'ditolak' ? 'danger' : 'warning') ?>">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <h6 class="card-title"><?= $abstrak['judul'] ?></h6>
                                        <p class="card-text">
                                            <small class="text-muted">
                                                <i class="fas fa-tag me-1"></i>Kategori: <?= $abstrak['nama_kategori'] ?? 'N/A' ?><br>
                                                <i class="fas fa-calendar me-1"></i>Event: <?= $abstrak['event_title'] ?? 'Tidak ada event' ?><br>
                                                <i class="fas fa-clock me-1"></i>Upload: <?= date('d/m/Y H:i', strtotime($abstrak['tanggal_upload'])) ?><br>
                                                <i class="fas fa-redo me-1"></i>Revisi ke: <?= $abstrak['revisi_ke'] ?><br>
                                                <i class="fas fa-file-pdf me-1 text-danger"></i>File: PDF
                                            </small>
                                        </p>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <span class="badge bg-<?= $abstrak['status'] == 'diterima' ? 'success' : ($abstrak['status'] == 'ditolak' ? 'danger' : 'warning') ?> mb-2">
                                            <?= ucfirst($abstrak['status']) ?>
                                        </span>
                                        <br>
                                        <a href="<?= site_url('presenter/abstrak/download/' . $abstrak['file_abstrak']) ?>" 
                                           class="btn btn-sm btn-outline-primary" target="_blank">
                                            <i class="fas fa-download me-1"></i>Download PDF
                                        </a>
                                        <a href="<?= site_url('presenter/abstrak/detail/' . $abstrak['id_abstrak']) ?>" 
                                           class="btn btn-sm btn-outline-info">
                                            <i class="fas fa-eye me-1"></i>Detail
                                        </a>
                                    </div>
                                </div>

                                <!-- Review Comments Preview -->
                                <?php if (!empty($abstrak['reviews'])): ?>
                                    <div class="mt-3">
                                        <small class="text-muted">
                                            <i class="fas fa-comments me-1"></i>
                                            <?= count($abstrak['reviews']) ?> komentar reviewer
                                        </small>
                                        <a href="<?= site_url('presenter/abstrak/status') ?>" class="btn btn-sm btn-outline-info ms-2">
                                            Lihat Detail
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="fas fa-file-pdf fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Belum Ada Abstrak</h5>
                        <p class="text-muted">Anda belum mengirim abstrak PDF apapun</p>
                        <?php if (!empty($activeEvents)): ?>
                            <button class="btn btn-primary btn-custom" onclick="scrollToSubmitForm()">
                                <i class="fas fa-plus me-1"></i>Submit Abstrak Pertama
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('show');
        }

        function scrollToSubmitForm() {
            const submitCard = document.querySelector('.card');
            if (submitCard) {
                submitCard.scrollIntoView({ behavior: 'smooth' });
            }
        }

        // File upload handling
        document.addEventListener('DOMContentLoaded', function() {
            const fileInput = document.getElementById('fileInput');
            const fileUploadArea = document.getElementById('fileUploadArea');
            const fileInfo = document.getElementById('fileInfo');
            const errorInfo = document.getElementById('errorInfo');
            const progressContainer = document.getElementById('progressContainer');
            const form = document.getElementById('uploadForm');
            const submitBtn = document.getElementById('submitBtn');

            if (fileInput && fileUploadArea) {
                // Drag and drop functionality
                fileUploadArea.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    fileUploadArea.classList.add('dragover');
                });

                fileUploadArea.addEventListener('dragleave', function(e) {
                    e.preventDefault();
                    fileUploadArea.classList.remove('dragover');
                });

                fileUploadArea.addEventListener('drop', function(e) {
                    e.preventDefault();
                    fileUploadArea.classList.remove('dragover');
                    const files = e.dataTransfer.files;
                    if (files.length > 0) {
                        handleFile(files[0]);
                    }
                });

                // File input change
                fileInput.addEventListener('change', function(e) {
                    if (e.target.files.length > 0) {
                        handleFile(e.target.files[0]);
                    }
                });

                function handleFile(file) {
                    hideMessages();

                    // Validate file type
                    if (file.type !== 'application/pdf') {
                        showError('File harus berformat PDF. File Anda: ' + file.name + ' (' + file.type + ')');
                        return;
                    }

                    // Validate file size (5MB)
                    if (file.size > 5 * 1024 * 1024) {
                        showError('Ukuran file terlalu besar: ' + (file.size / 1024 / 1024).toFixed(2) + 'MB. Maksimal 5MB');
                        return;
                    }

                    // Show file info
                    document.getElementById('fileName').textContent = file.name;
                    document.getElementById('fileSize').textContent = formatFileSize(file.size);
                    fileInfo.style.display = 'block';
                    fileUploadArea.style.display = 'none';
                }

                window.removeFile = function() {
                    fileInput.value = '';
                    hideMessages();
                    fileUploadArea.style.display = 'block';
                }

                function showError(message) {
                    document.getElementById('errorMessage').textContent = message;
                    errorInfo.style.display = 'block';
                    fileInput.value = '';
                }

                function hideMessages() {
                    fileInfo.style.display = 'none';
                    errorInfo.style.display = 'none';
                    progressContainer.style.display = 'none';
                }

                function formatFileSize(bytes) {
                    if (bytes === 0) return '0 Bytes';
                    const k = 1024;
                    const sizes = ['Bytes', 'KB', 'MB', 'GB'];
                    const i = Math.floor(Math.log(bytes) / Math.log(k));
                    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
                }

                // Form submission handling
                if (form) {
                    form.addEventListener('submit', function(e) {
                        if (!fileInput.files.length) {
                            e.preventDefault();
                            showError('Silakan pilih file PDF terlebih dahulu');
                            return false;
                        }

                        const file = fileInput.files[0];
                        
                        // Final validation before submit
                        if (file.type !== 'application/pdf') {
                            e.preventDefault();
                            showError('File harus berformat PDF');
                            return false;
                        }

                        if (file.size > 5 * 1024 * 1024) {
                            e.preventDefault();
                            showError('Ukuran file terlalu besar. Maksimal 5MB');
                            return false;
                        }

                        // Show loading state
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Uploading PDF...';
                        submitBtn.disabled = true;
                        progressContainer.style.display = 'block';
                    });

                    // Reset form handling
                    form.addEventListener('reset', function() {
                        hideMessages();
                        fileUploadArea.style.display = 'block';
                        submitBtn.innerHTML = '<i class="fas fa-upload me-1"></i>Upload Abstrak';
                        submitBtn.disabled = false;
                    });
                }
            }
        });

        // Auto-refresh for pending abstracts
        setInterval(function() {
            const pendingBadges = document.querySelectorAll('.badge');
            let hasPending = false;
            pendingBadges.forEach(function(badge) {
                if (badge.textContent.trim() === 'Menunggu') {
                    hasPending = true;
                }
            });
            
            if (hasPending) {
                console.log('Auto-refreshing due to pending abstracts...');
                // Only refresh if no form is being filled
                const uploadForm = document.getElementById('uploadForm');
                if (uploadForm && !uploadForm.classList.contains('was-validated')) {
                    location.reload();
                }
            }
        }, 60000);
    </script>
</body>
</html>