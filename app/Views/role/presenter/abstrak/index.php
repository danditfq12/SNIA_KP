<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Manajemen Abstrak - Presenter Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
                        <i class="fas fa-plus me-2"></i>Submit Abstrak Baru
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
                            <label class="form-label">File Abstrak <span class="text-danger">*</span></label>
                            <input type="file" name="file_abstrak" class="form-control" accept=".pdf,.doc,.docx" required>
                            <small class="text-muted">Format: PDF, DOC, atau DOCX. Maksimal 5MB</small>
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
                                                <i class="fas fa-redo me-1"></i>Revisi ke: <?= $abstrak['revisi_ke'] ?>
                                            </small>
                                        </p>
                                    </div>
                                    <div class="col-md-4 text-end">
                                        <span class="badge bg-<?= $abstrak['status'] == 'diterima' ? 'success' : ($abstrak['status'] == 'ditolak' ? 'danger' : 'warning') ?> mb-2">
                                            <?= ucfirst($abstrak['status']) ?>
                                        </span>
                                        <br>
                                        <a href="<?= site_url('presenter/abstrak/download/' . $abstrak['file_abstrak']) ?>" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-download me-1"></i>Download
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
                        <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                        <h5 class="text-muted">Belum Ada Abstrak</h5>
                        <p class="text-muted">Anda belum mengirim abstrak apapun</p>
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

        // Form validation and submission handling
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('uploadForm');
            if (form) {
                form.addEventListener('submit', function(e) {
                    const fileInput = form.querySelector('input[type="file"]');
                    const submitBtn = document.getElementById('submitBtn');
                    
                    if (fileInput.files.length === 0) {
                        e.preventDefault();
                        alert('Silakan pilih file abstrak terlebih dahulu');
                        return false;
                    }
                    
                    // Check file size (5MB = 5 * 1024 * 1024 bytes)
                    if (fileInput.files[0].size > 5 * 1024 * 1024) {
                        e.preventDefault();
                        alert('Ukuran file terlalu besar. Maksimal 5MB');
                        return false;
                    }
                    
                    // Check file extension - allow multiple formats
                    const fileName = fileInput.files[0].name;
                    const fileExt = fileName.split('.').pop().toLowerCase();
                    const allowedExts = ['pdf', 'doc', 'docx'];
                    
                    if (!allowedExts.includes(fileExt)) {
                        e.preventDefault();
                        alert('File harus berformat PDF, DOC, atau DOCX. File Anda: ' + fileExt);
                        return false;
                    }
                    
                    // Show loading state
                    submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Uploading...';
                    submitBtn.disabled = true;
                });
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
                location.reload();
            }
        }, 45000);
    </script>
</body>
</html>