<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Reviewer - SNIA Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #06b6d4;
            --secondary-color: #6b7280;
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
            margin-bottom: 24px;
        }

        .content-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--info-color) 100%);
            color: white;
            padding: 24px;
        }

        .profile-card {
            background: linear-gradient(135deg, var(--primary-color), var(--info-color));
            color: white;
            border-radius: 16px;
            overflow: hidden;
        }

        .profile-avatar {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: rgba(255,255,255,0.2);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 3rem;
            font-weight: bold;
            margin: 0 auto 20px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border-left: 4px solid;
            transition: all 0.3s ease;
            height: 100%;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }

        .performance-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
        }

        .category-badge {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
            margin: 4px;
            display: inline-block;
        }

        .review-card {
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            padding: 20px;
            margin-bottom: 16px;
            transition: all 0.3s ease;
        }

        .review-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.1);
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .performance-meter {
            height: 12px;
            background: #e2e8f0;
            border-radius: 6px;
            overflow: hidden;
            margin: 8px 0;
        }

        .performance-fill {
            height: 100%;
            border-radius: 6px;
            transition: width 1s ease;
        }

        .back-btn {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            border-radius: 8px;
            padding: 8px 16px;
            text-decoration: none;
            transition: all 0.3s ease;
        }

        .back-btn:hover {
            background: rgba(255,255,255,0.3);
            color: white;
            transform: translateY(-1px);
        }

        .activity-timeline {
            position: relative;
            padding-left: 30px;
        }

        .activity-timeline::before {
            content: '';
            position: absolute;
            left: 8px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: linear-gradient(to bottom, var(--primary-color), var(--info-color));
        }

        .activity-item {
            position: relative;
            margin-bottom: 20px;
            padding: 16px;
            background: #f8fafc;
            border-radius: 8px;
            border-left: 3px solid var(--primary-color);
        }

        .activity-item::before {
            content: '';
            position: absolute;
            left: -25px;
            top: 20px;
            width: 12px;
            height: 12px;
            background: var(--primary-color);
            border-radius: 50%;
            border: 3px solid white;
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
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-custom:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        @media (max-width: 768px) {
            .sidebar {
                position: fixed;
                top: 0;
                left: -100%;
                width: 250px;
                transition: left 0.3s ease;
                z-index: 1000;
            }

            .sidebar.show {
                left: 0;
            }

            .main-content {
                border-radius: 0;
                margin-left: 0;
            }
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
                        <a class="nav-link active" href="<?= site_url('admin/reviewer') ?>">
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
                    <div class="content-card">
                        <div class="content-header">
                            <div class="row align-items-center">
                                <div class="col">
                                    <div class="d-flex align-items-center mb-3">
                                        <a href="<?= site_url('admin/reviewer') ?>" class="back-btn me-3">
                                            <i class="fas fa-arrow-left me-2"></i>Kembali
                                        </a>
                                        <div>
                                            <h2 class="mb-0">Detail Reviewer</h2>
                                            <p class="mb-0 opacity-75">Informasi lengkap dan performa reviewer</p>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-auto">
                                    <div class="d-flex gap-2">
                                        <button class="btn btn-light btn-custom" onclick="toggleStatus(<?= $reviewer['id_user'] ?? 0 ?>, '<?= $reviewer['status'] ?? 'nonaktif' ?>')">
                                            <i class="fas fa-power-off me-2"></i>
                                            <?= ($reviewer['status'] ?? 'nonaktif') === 'aktif' ? 'Nonaktifkan' : 'Aktifkan' ?>
                                        </button>
                                        <button class="btn btn-info btn-custom" data-bs-toggle="modal" data-bs-target="#assignCategoryModal">
                                            <i class="fas fa-plus me-2"></i>Assign Kategori
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row g-4">
                        <!-- Profile Card -->
                        <div class="col-lg-4">
                            <div class="profile-card">
                                <div class="p-4 text-center">
                                    <div class="profile-avatar">
                                        <?= strtoupper(substr($reviewer['nama_lengkap'] ?? 'R', 0, 1)) ?>
                                    </div>
                                    <h4 class="mb-2"><?= esc($reviewer['nama_lengkap'] ?? 'Reviewer') ?></h4>
                                    <p class="mb-3 opacity-75"><?= esc($reviewer['email'] ?? '') ?></p>
                                    <div class="mb-3">
                                        <span class="badge <?= ($reviewer['status'] ?? 'nonaktif') === 'aktif' ? 'bg-success' : 'bg-secondary' ?> fs-6">
                                            <?= ucfirst($reviewer['status'] ?? 'nonaktif') ?>
                                        </span>
                                    </div>
                                    <div class="row text-center">
                                        <div class="col-4">
                                            <div class="h4 mb-0"><?= $performance['total_reviews'] ?? 0 ?></div>
                                            <small class="opacity-75">Total Review</small>
                                        </div>
                                        <div class="col-4">
                                            <div class="h4 mb-0"><?= $performance['completed_reviews'] ?? 0 ?></div>
                                            <small class="opacity-75">Selesai</small>
                                        </div>
                                        <div class="col-4">
                                            <div class="h4 mb-0"><?= $performance['avg_review_time'] ?? 0 ?></div>
                                            <small class="opacity-75">Rata-rata Hari</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Categories Card -->
                            <div class="content-card mt-4">
                                <div class="p-4">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <h5 class="mb-0">
                                            <i class="fas fa-tags me-2 text-primary"></i>Kategori Review
                                        </h5>
                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#assignCategoryModal">
                                            <i class="fas fa-plus"></i>
                                        </button>
                                    </div>
                                    <div>
                                        <?php if (!empty($categories)): ?>
                                            <?php foreach ($categories as $category): ?>
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <span class="category-badge bg-primary text-white">
                                                        <?= esc($category['nama_kategori']) ?>
                                                    </span>
                                                    <button class="btn btn-sm btn-outline-danger" 
                                                            onclick="removeCategory(<?= $category['id'] ?>)"
                                                            title="Hapus kategori: <?= esc($category['nama_kategori']) ?>">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="text-center text-muted py-4">
                                                <i class="fas fa-tags fa-2x mb-3 opacity-50"></i>
                                                <p>Belum ada kategori yang ditugaskan</p>
                                                <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#assignCategoryModal">
                                                    Assign Kategori Pertama
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Performance Stats -->
                        <div class="col-lg-8">
                            <!-- Stats Cards Row -->
                            <div class="row g-3 mb-4">
                                <div class="col-md-3">
                                    <div class="stat-card" style="border-left-color: var(--primary-color);">
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <i class="fas fa-file-alt fa-2x text-primary"></i>
                                            </div>
                                            <div>
                                                <h4 class="mb-0"><?= $performance['total_reviews'] ?? 0 ?></h4>
                                                <small class="text-muted">Total Review</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stat-card" style="border-left-color: var(--warning-color);">
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <i class="fas fa-clock fa-2x text-warning"></i>
                                            </div>
                                            <div>
                                                <h4 class="mb-0"><?= $performance['pending_reviews'] ?? 0 ?></h4>
                                                <small class="text-muted">Pending</small>
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
                                                <h4 class="mb-0"><?= $performance['completion_rate'] ?? 0 ?>%</h4>
                                                <small class="text-muted">Completion Rate</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="stat-card" style="border-left-color: var(--info-color);">
                                        <div class="d-flex align-items-center">
                                            <div class="me-3">
                                                <i class="fas fa-thumbs-up fa-2x text-info"></i>
                                            </div>
                                            <div>
                                                <h4 class="mb-0"><?= $performance['acceptance_rate'] ?? 0 ?>%</h4>
                                                <small class="text-muted">Acceptance Rate</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Performance Metrics -->
                            <div class="performance-card">
                                <h5 class="mb-4">
                                    <i class="fas fa-chart-bar me-2 text-primary"></i>Distribusi Keputusan Review
                                </h5>
                                
                                <div class="row">
                                    <div class="col-md-4">
                                        <label class="small text-muted fw-semibold">DITERIMA</label>
                                        <div class="performance-meter">
                                            <div class="performance-fill bg-success" data-width="<?= $performance['accepted_reviews'] ?? 0 ?>"></div>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span class="fw-bold text-success"><?= $performance['accepted_reviews'] ?? 0 ?></span>
                                            <span class="text-muted">
                                                <?= ($performance['completed_reviews'] ?? 0) > 0 ? round((($performance['accepted_reviews'] ?? 0) / $performance['completed_reviews']) * 100) : 0 ?>%
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="small text-muted fw-semibold">REVISI</label>
                                        <div class="performance-meter">
                                            <div class="performance-fill bg-warning" data-width="<?= $performance['revision_reviews'] ?? 0 ?>"></div>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span class="fw-bold text-warning"><?= $performance['revision_reviews'] ?? 0 ?></span>
                                            <span class="text-muted">
                                                <?= ($performance['completed_reviews'] ?? 0) > 0 ? round((($performance['revision_reviews'] ?? 0) / $performance['completed_reviews']) * 100) : 0 ?>%
                                            </span>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="small text-muted fw-semibold">DITOLAK</label>
                                        <div class="performance-meter">
                                            <div class="performance-fill bg-danger" data-width="<?= $performance['rejected_reviews'] ?? 0 ?>"></div>
                                        </div>
                                        <div class="d-flex justify-content-between">
                                            <span class="fw-bold text-danger"><?= $performance['rejected_reviews'] ?? 0 ?></span>
                                            <span class="text-muted">
                                                <?= ($performance['completed_reviews'] ?? 0) > 0 ? round((($performance['rejected_reviews'] ?? 0) / $performance['completed_reviews']) * 100) : 0 ?>%
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Review History -->
                    <div class="content-card">
                        <div class="p-4 border-bottom">
                            <h5 class="mb-0">
                                <i class="fas fa-history me-2 text-primary"></i>Riwayat Review
                            </h5>
                        </div>
                        <div class="p-4">
                            <?php if (!empty($reviews)): ?>
                                <div class="activity-timeline">
                                    <?php foreach ($reviews as $review): ?>
                                        <div class="activity-item">
                                            <div class="row">
                                                <div class="col-md-8">
                                                    <h6 class="mb-2 fw-bold">
                                                        <?= esc($review['judul']) ?>
                                                    </h6>
                                                    <div class="mb-2">
                                                        <span class="badge bg-info text-white me-2">
                                                            <?= esc($review['nama_kategori']) ?>
                                                        </span>
                                                        <span class="status-badge <?= getReviewStatusClass($review['keputusan']) ?>">
                                                            <?= ucfirst($review['keputusan']) ?>
                                                        </span>
                                                    </div>
                                                    <p class="text-muted mb-2">
                                                        <strong>Author:</strong> <?= esc($review['author_name']) ?>
                                                    </p>
                                                    <?php if (!empty($review['komentar'])): ?>
                                                        <div class="mt-2">
                                                            <strong>Komentar:</strong>
                                                            <p class="text-muted mb-0"><?= esc($review['komentar']) ?></p>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="col-md-4 text-end">
                                                    <div class="text-muted">
                                                        <i class="fas fa-calendar me-1"></i>
                                                        <?= date('d M Y', strtotime($review['tanggal_review'])) ?>
                                                    </div>
                                                    <div class="text-muted mt-1">
                                                        <i class="fas fa-clock me-1"></i>
                                                        <?= date('H:i', strtotime($review['tanggal_review'])) ?>
                                                    </div>
                                                    <?php if (isset($review['tanggal_upload'])): ?>
                                                        <div class="text-muted mt-2">
                                                            <small>
                                                                Review time: 
                                                                <?= calculateReviewTime($review['tanggal_upload'], $review['tanggal_review']) ?> hari
                                                            </small>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-5">
                                    <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">Belum Ada Riwayat Review</h5>
                                    <p class="text-muted">Reviewer belum melakukan review apapun.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
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
                        <input type="hidden" name="reviewer_id" value="<?= $reviewer['id_user'] ?? 0 ?>">
                        <div class="mb-3">
                            <label class="form-label">Reviewer</label>
                            <input type="text" class="form-control" value="<?= esc($reviewer['nama_lengkap'] ?? '') ?>" readonly>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Pilih Kategori</label>
                            <select class="form-select" name="category_id" required>
                                <option value="">Pilih Kategori</option>
                                <?php if (!empty($available_categories)): ?>
                                    <?php foreach ($available_categories as $category): ?>
                                        <option value="<?= $category['id_kategori'] ?>">
                                            <?= esc($category['nama_kategori']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <option value="" disabled>Tidak ada kategori tersedia</option>
                                <?php endif; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary btn-custom">
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
        // Functions
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

        function removeCategory(categoryId) {
            console.log('removeCategory called with categoryId:', categoryId);
            console.log('typeof categoryId:', typeof categoryId);
            console.log('Base URL from site_url:', '<?= site_url('admin/reviewer/removeCategory') ?>');
            
            if (!categoryId || categoryId === 'undefined' || categoryId === null) {
                console.error('Invalid categoryId:', categoryId);
                Swal.fire('Error!', 'ID kategori tidak valid: ' + categoryId, 'error');
                return;
            }
            
            const finalUrl = `<?= site_url('admin/reviewer/removeCategory') ?>/${categoryId}`;
            console.log('Final URL will be:', finalUrl);
            
            Swal.fire({
                title: 'Hapus Assignment Kategori?',
                text: 'Apakah Anda yakin ingin menghapus assignment kategori ini dari reviewer?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal',
                reverseButtons: true
            }).then((result) => {
                if (result.isConfirmed) {
                    console.log('Redirecting to:', finalUrl);
                    
                    // Show loading state
                    Swal.fire({
                        title: 'Menghapus kategori...',
                        text: 'Mohon tunggu sebentar',
                        allowOutsideClick: false,
                        allowEscapeKey: false,
                        showConfirmButton: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // Redirect
                    setTimeout(() => {
                        window.location.href = finalUrl;
                    }, 500);
                } else {
                    console.log('User cancelled removal');
                }
            });
        }

        // Initialize animations and events
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Page loaded successfully');
            console.log('Categories data:', <?= json_encode($categories ?? []) ?>);
            console.log('Available categories:', <?= json_encode($available_categories ?? []) ?>);
            
            // Animate performance bars
            setTimeout(() => {
                const bars = document.querySelectorAll('.performance-fill');
                bars.forEach(bar => {
                    const width = bar.dataset.width;
                    const maxValue = Math.max(
                        <?= $performance['accepted_reviews'] ?? 0 ?>,
                        <?= $performance['revision_reviews'] ?? 0 ?>,
                        <?= $performance['rejected_reviews'] ?? 0 ?>
                    );
                    const percentage = maxValue > 0 ? (width / maxValue) * 100 : 0;
                    bar.style.width = Math.min(percentage, 100) + '%';
                });
            }, 1000);

            // Animate cards
            const cards = document.querySelectorAll('.stat-card, .content-card, .profile-card');
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

            // Show alerts
            <?php if (session('success')): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: '<?= addslashes(session('success')) ?>',
                    timer: 3000,
                    showConfirmButton: false
                });
            <?php endif; ?>

            <?php if (session('error')): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: '<?= addslashes(session('error')) ?>',
                });
            <?php endif; ?>

            <?php if (session('errors')): ?>
                let errorMessages = '';
                <?php foreach (session('errors') as $error): ?>
                    errorMessages += '<?= addslashes($error) ?>\n';
                <?php endforeach; ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Validation Error!',
                    text: errorMessages,
                });
            <?php endif; ?>
        });

        // Mobile sidebar toggle
        function toggleSidebar() {
            document.querySelector('.sidebar').classList.toggle('show');
        }
    </script>
</body>
</html>

<?php
// PHP Helper Functions
function getReviewStatusClass($status) {
    switch($status) {
        case 'diterima': return 'bg-success text-white';
        case 'ditolak': return 'bg-danger text-white';
        case 'revisi': return 'bg-warning text-dark';
        case 'pending': return 'bg-secondary text-white';
        default: return 'bg-light text-dark';
    }
}

function calculateReviewTime($uploadDate, $reviewDate) {
    if (!$uploadDate || !$reviewDate) return 0;
    
    $upload = strtotime($uploadDate);
    $review = strtotime($reviewDate);
    
    if ($review <= $upload) return 0;
    
    return ceil(($review - $upload) / (60 * 60 * 24));
}
?>