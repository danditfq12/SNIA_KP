<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Reviewer - SNIA Admin</title>
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

        .reviewer-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }

        .reviewer-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }

        .reviewer-header {
            padding: 20px;
            border-bottom: 1px solid #f1f5f9;
        }

        .reviewer-body {
            padding: 20px;
        }

        .reviewer-avatar {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--info-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 1.5rem;
            font-weight: bold;
        }

        .category-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
            margin: 2px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border-left: 4px solid;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
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

        .performance-bar {
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
        }

        .performance-fill {
            height: 100%;
            transition: width 0.3s ease;
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
                        <a class="nav-link" href="<?= site_url('admin/event') ?>">
                            <i class="fas fa-calendar me-2"></i> Kelola Event
                        </a>
                        <a class="nav-link" href="<?= site_url('admin/abstrak') ?>">
                            <i class="fas fa-file-alt me-2"></i> Manajemen Abstrak
                        </a>
                        <a class="nav-link active" href="<?= site_url('admin/reviewer') ?>">
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
                    <div class="content-card mb-4">
                        <div class="content-header">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h2 class="mb-2">
                                        <i class="fas fa-user-check me-3"></i>Kelola Reviewer
                                    </h2>
                                    <p class="mb-0 opacity-75">Manajemen reviewer untuk proses review abstrak</p>
                                </div>
                                <div class="col-auto">
                                    <button class="btn btn-light btn-custom" data-bs-toggle="modal" data-bs-target="#addReviewerModal">
                                        <i class="fas fa-plus me-2"></i>Tambah Reviewer
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-3">
                            <div class="stat-card" style="border-left-color: var(--primary-color);">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="fas fa-user-check fa-2x text-primary"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0"><?= $total_reviewers ?? 0 ?></h3>
                                        <small class="text-muted">Total Reviewer</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card" style="border-left-color: var(--success-color);">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="fas fa-check-circle fa-2x text-success"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0"><?= $active_reviewers ?? 0 ?></h3>
                                        <small class="text-muted">Reviewer Aktif</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card" style="border-left-color: var(--info-color);">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="fas fa-tags fa-2x text-info"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0"><?= $total_categories ?? 0 ?></h3>
                                        <small class="text-muted">Total Kategori</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card" style="border-left-color: var(--warning-color);">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="fas fa-percentage fa-2x text-warning"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0">
                                            <?= $total_reviewers > 0 ? round(($active_reviewers / $total_reviewers) * 100) : 0 ?>%
                                        </h3>
                                        <small class="text-muted">Tingkat Aktivasi</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Reviewers Grid -->
                    <div class="row g-4">
                        <?php if (!empty($reviewers)): ?>
                            <?php foreach ($reviewers as $reviewer): ?>
                                <div class="col-lg-6 col-xl-4">
                                    <div class="reviewer-card">
                                        <div class="reviewer-header">
                                            <div class="d-flex align-items-center">
                                                <div class="reviewer-avatar me-3">
                                                    <?= strtoupper(substr($reviewer['nama_lengkap'], 0, 1)) ?>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1 fw-bold"><?= esc($reviewer['nama_lengkap']) ?></h6>
                                                    <small class="text-muted"><?= esc($reviewer['email']) ?></small>
                                                    <div class="mt-2">
                                                        <span class="badge <?= $reviewer['status'] === 'aktif' ? 'bg-success' : 'bg-secondary' ?>">
                                                            <?= ucfirst($reviewer['status']) ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li><a class="dropdown-item" href="<?= site_url('admin/reviewer/detail/' . $reviewer['id_user']) ?>">
                                                            <i class="fas fa-eye me-2"></i>Detail
                                                        </a></li>
                                                        <li><a class="dropdown-item" href="#" onclick="toggleStatus(<?= $reviewer['id_user'] ?>, '<?= $reviewer['status'] ?>')">
                                                            <i class="fas fa-power-off me-2"></i><?= $reviewer['status'] === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' ?>
                                                        </a></li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li><a class="dropdown-item text-danger" href="#" onclick="deleteReviewer(<?= $reviewer['id_user'] ?>, '<?= esc($reviewer['nama_lengkap']) ?>')">
                                                            <i class="fas fa-trash me-2"></i>Hapus
                                                        </a></li>
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                        
                                        <div class="reviewer-body">
                                            <!-- Performance Stats -->
                                            <div class="row mb-3">
                                                <div class="col-4 text-center">
                                                    <div class="fw-bold text-primary"><?= $reviewer['total_reviews'] ?? 0 ?></div>
                                                    <small class="text-muted">Total Review</small>
                                                </div>
                                                <div class="col-4 text-center">
                                                    <div class="fw-bold text-warning"><?= $reviewer['pending_reviews'] ?? 0 ?></div>
                                                    <small class="text-muted">Pending</small>
                                                </div>
                                                <div class="col-4 text-center">
                                                    <div class="fw-bold text-success">
                                                        <?= $reviewer['total_reviews'] > 0 ? round((($reviewer['total_reviews'] - $reviewer['pending_reviews']) / $reviewer['total_reviews']) * 100) : 0 ?>%
                                                    </div>
                                                    <small class="text-muted">Selesai</small>
                                                </div>
                                            </div>

                                            <!-- Performance Bar -->
                                            <div class="performance-bar mb-3">
                                                <div class="performance-fill bg-success" style="width: <?= $reviewer['total_reviews'] > 0 ? round((($reviewer['total_reviews'] - $reviewer['pending_reviews']) / $reviewer['total_reviews']) * 100) : 0 ?>%"></div>
                                            </div>

                                            <!-- Categories -->
                                            <div class="mb-3">
                                                <label class="small text-muted fw-semibold mb-2">KATEGORI REVIEW:</label>
                                                <div>
                                                    <?php if (!empty($reviewer['categories'])): ?>
                                                        <?php foreach ($reviewer['categories'] as $category): ?>
                                                            <span class="category-badge bg-primary text-white">
                                                                <?= esc($category['nama_kategori']) ?>
                                                            </span>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <small class="text-muted">Belum ada kategori</small>
                                                    <?php endif; ?>
                                                </div>
                                            </div>

                                            <!-- Actions -->
                                            <div class="d-flex gap-2">
                                                <button class="btn btn-outline-info btn-custom btn-sm flex-fill" 
                                                        onclick="assignCategory(<?= $reviewer['id_user'] ?>, '<?= esc($reviewer['nama_lengkap']) ?>')">
                                                    <i class="fas fa-plus me-1"></i>Kategori
                                                </button>
                                                <a href="<?= site_url('admin/reviewer/detail/' . $reviewer['id_user']) ?>" 
                                                   class="btn btn-outline-primary btn-custom btn-sm flex-fill">
                                                    <i class="fas fa-chart-line me-1"></i>Performa
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <div class="empty-state">
                                    <i class="fas fa-user-check"></i>
                                    <h4>Belum Ada Reviewer</h4>
                                    <p>Mulai dengan menambahkan reviewer pertama untuk sistem review abstrak.</p>
                                    <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addReviewerModal">
                                        <i class="fas fa-plus me-2"></i>Tambah Reviewer
                                    </button>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Reviewer Modal -->
    <div class="modal fade" id="addReviewerModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-plus me-2"></i>Tambah Reviewer Baru
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="<?= site_url('admin/reviewer/store') ?>" method="POST">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Nama Lengkap</label>
                                    <input type="text" class="form-control" name="nama_lengkap" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" class="form-control" name="email" required>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Password</label>
                            <input type="password" class="form-control" name="password" required minlength="6">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Kategori Review</label>
                            <div class="row">
                                <?php if (!empty($categories)): ?>
                                    <?php foreach ($categories as $category): ?>
                                        <div class="col-md-6 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="categories[]" 
                                                       value="<?= $category['id_kategori'] ?>" id="cat_<?= $category['id_kategori'] ?>">
                                                <label class="form-check-label" for="cat_<?= $category['id_kategori'] ?>">
                                                    <?= esc($category['nama_kategori']) ?>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="col-12">
                                        <div class="alert alert-warning">
                                            <i class="fas fa-exclamation-triangle me-2"></i>
                                            Belum ada kategori yang tersedia. Silakan buat kategori terlebih dahulu.
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Simpan Reviewer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Assign Category Modal -->
    <div class="modal fade" id="assignCategoryModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-tags me-2"></i>Assign Kategori
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="<?= site_url('admin/reviewer/assignCategory') ?>" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="reviewer_id" id="assignReviewerId">
                        <div class="mb-3">
                            <label class="form-label">Reviewer</label>
                            <input type="text" class="form-control" id="assignReviewerName" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Pilih Kategori</label>
                            <select class="form-select" name="category_id" required>
                                <option value="">Pilih Kategori</option>
                                <?php if (!empty($categories)): ?>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category['id_kategori'] ?>">
                                            <?= esc($category['nama_kategori']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Assign
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function assignCategory(reviewerId, reviewerName) {
            document.getElementById('assignReviewerId').value = reviewerId;
            document.getElementById('assignReviewerName').value = reviewerName;
            new bootstrap.Modal(document.getElementById('assignCategoryModal')).show();
        }

        function toggleStatus(reviewerId, currentStatus) {
            const action = currentStatus === 'aktif' ? 'nonaktifkan' : 'aktifkan';
            
            Swal.fire({
                title: `${action.charAt(0).toUpperCase() + action.slice(1)} Reviewer?`,
                text: `Apakah Anda yakin ingin ${action} reviewer ini?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: currentStatus === 'aktif' ? '#f59e0b' : '#10b981',
                cancelButtonColor: '#6b7280',
                confirmButtonText: `Ya, ${action.charAt(0).toUpperCase() + action.slice(1)}!`,
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `<?= site_url('admin/reviewer/toggleStatus') ?>/${reviewerId}`;
                }
            });
        }

        function deleteReviewer(reviewerId, reviewerName) {
            Swal.fire({
                title: 'Hapus Reviewer?',
                text: `Apakah Anda yakin ingin menghapus reviewer "${reviewerName}"?`,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = `<?= site_url('admin/reviewer/delete') ?>/${reviewerId}`;
                }
            });
        }

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

            // Animate cards on load
            const cards = document.querySelectorAll('.reviewer-card, .stat-card');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    card.style.transition = 'all 0.5s ease';
                    
                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 100);
                }, index * 50);
            });

            // Performance bar animation
            setTimeout(() => {
                document.querySelectorAll('.performance-fill').forEach(fill => {
                    const width = fill.style.width;
                    fill.style.width = '0%';
                    setTimeout(() => {
                        fill.style.width = width;
                    }, 500);
                });
            }, 1000);
        });

        // Form validation
        document.querySelectorAll('form').forEach(form => {
            form.addEventListener('submit', function(e) {
                const requiredFields = form.querySelectorAll('[required]');
                let isValid = true;
                
                requiredFields.forEach(field => {
                    if (!field.value.trim()) {
                        field.classList.add('is-invalid');
                        isValid = false;
                    } else {
                        field.classList.remove('is-invalid');
                    }
                });

                // Check if at least one category is selected for new reviewer
                if (form.action.includes('/store')) {
                    const categories = form.querySelectorAll('input[name="categories[]"]:checked');
                    if (categories.length === 0) {
                        Swal.fire('Error!', 'Pilih setidaknya satu kategori review', 'error');
                        e.preventDefault();
                        return;
                    }
                }
                
                if (!isValid) {
                    e.preventDefault();
                    Swal.fire('Error!', 'Mohon lengkapi semua field yang diperlukan', 'error');
                }
            });
        });
    </script>
</body>
</html>