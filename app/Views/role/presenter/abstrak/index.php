<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Manajemen Abstrak - Presenter Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
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
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?= site_url('presenter/dashboard') ?>">
                <i class="fas fa-microphone me-2"></i>SNIA Presenter
            </a>
            <div class="navbar-nav ms-auto">
                <a href="<?= site_url('presenter/dashboard') ?>" class="nav-link">
                    <i class="fas fa-home me-1"></i>Dashboard
                </a>
                <a href="<?= site_url('presenter/abstrak/status') ?>" class="nav-link">
                    <i class="fas fa-chart-line me-1"></i>Status
                </a>
                <a href="<?= site_url('auth/logout') ?>" class="btn btn-outline-light btn-sm ms-2">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-4">
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
                        <button type="submit" class="btn btn-primary" id="submitBtn">
                            <i class="fas fa-upload me-1"></i>Upload Abstrak
                        </button>
                        <button type="reset" class="btn btn-outline-secondary ms-2">
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
                            <button class="btn btn-primary" onclick="scrollToSubmitForm()">
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

            function removeFile() {
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
                if (!document.getElementById('uploadForm').classList.contains('was-validated')) {
                    location.reload();
                }
            }
        }, 60000); // Check every 1 minute instead of 45 seconds

        // Make download links open in new tab for PDF viewing
        document.addEventListener('click', function(e) {
            if (e.target.closest('a[href*="/download/"]')) {
                e.target.closest('a').setAttribute('target', '_blank');
            }
        });
    </script>
</body>
</html>