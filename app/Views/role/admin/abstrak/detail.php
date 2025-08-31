<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Abstrak - <?= esc($abstrak['judul']) ?> - SNIA Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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

        .detail-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            border: 1px solid rgba(255,255,255,0.2);
            margin-bottom: 24px;
            position: relative;
            overflow: hidden;
        }

        .detail-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--info-color));
        }

        .header-section {
            background: white;
            padding: 24px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        .status-badge {
            font-size: 14px;
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .info-item:last-child {
            border-bottom: none;
        }

        .info-label {
            font-weight: 600;
            color: #374151;
            min-width: 150px;
        }

        .info-value {
            color: #6b7280;
            text-align: right;
            flex: 1;
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

        .review-timeline {
            position: relative;
            padding-left: 30px;
        }

        .review-timeline::before {
            content: '';
            position: absolute;
            left: 15px;
            top: 0;
            bottom: 0;
            width: 2px;
            background: #e5e7eb;
        }

        .review-item {
            position: relative;
            margin-bottom: 24px;
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 2px 12px rgba(0,0,0,0.05);
        }

        .review-item::before {
            content: '';
            position: absolute;
            left: -23px;
            top: 24px;
            width: 12px;
            height: 12px;
            background: var(--primary-color);
            border-radius: 50%;
            border: 3px solid white;
            box-shadow: 0 0 0 2px #e5e7eb;
        }

        .review-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 12px;
        }

        .reviewer-name {
            font-weight: 600;
            color: #374151;
        }

        .review-date {
            font-size: 12px;
            color: #6b7280;
        }

        .review-decision {
            font-size: 12px;
            font-weight: 600;
            padding: 4px 8px;
            border-radius: 12px;
            text-transform: uppercase;
        }

        .file-preview {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
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

        .breadcrumb-item a {
            color: var(--primary-color);
            text-decoration: none;
        }

        .breadcrumb-item a:hover {
            text-decoration: underline;
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
                        <a class="nav-link" href="<?= site_url('admin/event') ?>">
                            <i class="fas fa-calendar-alt me-2"></i> Kelola Event
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
                    <!-- Breadcrumb -->
                    <nav aria-label="breadcrumb" class="mb-4">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item">
                                <a href="<?= site_url('admin/dashboard') ?>">Dashboard</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="<?= site_url('admin/abstrak') ?>">Manajemen Abstrak</a>
                            </li>
                            <li class="breadcrumb-item active">Detail Abstrak</li>
                        </ol>
                    </nav>

                    <!-- Header -->
                    <div class="header-section">
                        <div class="row align-items-center">
                            <div class="col">
                                <h1 class="mb-2">
                                    <i class="fas fa-file-alt me-3 text-primary"></i>Detail Abstrak
                                </h1>
                                <h4 class="text-muted fw-normal"><?= esc($abstrak['judul']) ?></h4>
                            </div>
                            <div class="col-auto">
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
                                <span class="status-badge bg-<?= $class ?> text-white">
                                    <?= ucfirst(str_replace('_', ' ', $abstrak['status'])) ?>
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Left Column - Main Details -->
                        <div class="col-lg-8">
                            <!-- Abstrak Information -->
                            <div class="detail-card">
                                <h5 class="mb-4">
                                    <i class="fas fa-info-circle me-2 text-primary"></i>
                                    Informasi Abstrak
                                </h5>
                                
                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="fas fa-heading me-2"></i>Judul
                                    </div>
                                    <div class="info-value">
                                        <strong><?= esc($abstrak['judul']) ?></strong>
                                    </div>
                                </div>

                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="fas fa-user me-2"></i>Penulis
                                    </div>
                                    <div class="info-value">
                                        <?= esc($abstrak['nama_lengkap']) ?>
                                        <br><small class="text-muted"><?= esc($abstrak['email']) ?></small>
                                    </div>
                                </div>

                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="fas fa-tags me-2"></i>Kategori
                                    </div>
                                    <div class="info-value">
                                        <span class="badge bg-info"><?= esc($abstrak['nama_kategori']) ?></span>
                                    </div>
                                </div>

                                <?php if (isset($abstrak['event_title']) && !empty($abstrak['event_title'])): ?>
                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="fas fa-calendar me-2"></i>Event
                                    </div>
                                    <div class="info-value">
                                        <?= esc($abstrak['event_title']) ?>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="fas fa-clock me-2"></i>Tanggal Upload
                                    </div>
                                    <div class="info-value">
                                        <?= date('d F Y, H:i', strtotime($abstrak['tanggal_upload'])) ?>
                                    </div>
                                </div>

                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="fas fa-redo me-2"></i>Revisi Ke
                                    </div>
                                    <div class="info-value">
                                        <span class="badge bg-secondary"><?= $abstrak['revisi_ke'] ?></span>
                                    </div>
                                </div>

                                <div class="info-item">
                                    <div class="info-label">
                                        <i class="fas fa-flag me-2"></i>Status
                                    </div>
                                    <div class="info-value">
                                        <span class="badge bg-<?= $class ?>"><?= ucfirst(str_replace('_', ' ', $abstrak['status'])) ?></span>
                                    </div>
                                </div>
                            </div>

                            <!-- File Preview -->
                            <div class="detail-card">
                                <h5 class="mb-4">
                                    <i class="fas fa-file-pdf me-2 text-danger"></i>
                                    File Abstrak
                                </h5>
                                
                                <div class="file-preview">
                                    <div class="mb-3">
                                        <i class="fas fa-file-pdf fa-4x text-danger"></i>
                                    </div>
                                    <h6><?= esc($abstrak['file_abstrak']) ?></h6>
                                    <p class="text-muted mb-3">Klik tombol di bawah untuk mengunduh file</p>
                                    
                                    <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                                        <a href="<?= site_url('admin/abstrak/download/' . $abstrak['id_abstrak']) ?>" 
                                           class="btn btn-primary btn-custom">
                                            <i class="fas fa-download me-2"></i>Download File
                                        </a>
                                        <button class="btn btn-outline-info btn-custom" onclick="previewFile()">
                                            <i class="fas fa-eye me-2"></i>Preview
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Review History -->
                            <div class="detail-card">
                                <h5 class="mb-4">
                                    <i class="fas fa-history me-2 text-info"></i>
                                    Riwayat Review
                                </h5>

                                <?php if (empty($reviews)): ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Belum ada review untuk abstrak ini</p>
                                    </div>
                                <?php else: ?>
                                    <div class="review-timeline">
                                        <?php foreach ($reviews as $review): ?>
                                        <div class="review-item">
                                            <div class="review-header">
                                                <div>
                                                    <div class="reviewer-name"><?= esc($review['reviewer_name']) ?></div>
                                                    <div class="review-date"><?= date('d F Y, H:i', strtotime($review['tanggal_review'])) ?></div>
                                                </div>
                                                <div>
                                                    <?php 
                                                    $decisionClass = [
                                                        'pending' => 'warning',
                                                        'diterima' => 'success',
                                                        'ditolak' => 'danger',
                                                        'revisi' => 'info'
                                                    ];
                                                    $decClass = $decisionClass[$review['keputusan']] ?? 'secondary';
                                                    ?>
                                                    <span class="review-decision bg-<?= $decClass ?> text-white">
                                                        <?= ucfirst($review['keputusan']) ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="review-comment">
                                                <p class="mb-0"><?= nl2br(esc($review['komentar'])) ?></p>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Right Column - Actions -->
                        <div class="col-lg-4">
                            <!-- Quick Actions -->
                            <div class="detail-card">
                                <h5 class="mb-4">
                                    <i class="fas fa-tools me-2 text-warning"></i>
                                    Aksi Cepat
                                </h5>
                                
                                <div class="d-grid gap-2">
                                    <button class="btn btn-primary btn-custom" onclick="updateStatus()">
                                        <i class="fas fa-edit me-2"></i>Update Status
                                    </button>
                                    
                                    <?php if ($abstrak['status'] === 'menunggu'): ?>
                                    <button class="btn btn-success btn-custom" onclick="assignReviewer()">
                                        <i class="fas fa-user-plus me-2"></i>Assign Reviewer
                                    </button>
                                    <?php endif; ?>
                                    
                                    <a href="<?= site_url('admin/abstrak/download/' . $abstrak['id_abstrak']) ?>" 
                                       class="btn btn-info btn-custom">
                                        <i class="fas fa-download me-2"></i>Download File
                                    </a>
                                    
                                    <button class="btn btn-warning btn-custom" onclick="sendMessage()">
                                        <i class="fas fa-envelope me-2"></i>Kirim Pesan
                                    </button>
                                    
                                    <hr>
                                    
                                    <button class="btn btn-danger btn-custom" onclick="deleteAbstrak()">
                                        <i class="fas fa-trash me-2"></i>Hapus Abstrak
                                    </button>
                                </div>
                            </div>

                            <!-- Statistics -->
                            <div class="detail-card">
                                <h5 class="mb-4">
                                    <i class="fas fa-chart-bar me-2 text-success"></i>
                                    Statistik
                                </h5>
                                
                                <div class="info-item">
                                    <div class="info-label">Total Review</div>
                                    <div class="info-value">
                                        <span class="badge bg-primary"><?= count($reviews) ?></span>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-label">Review Pending</div>
                                    <div class="info-value">
                                        <?php 
                                        $pendingReviews = array_filter($reviews, fn($r) => $r['keputusan'] === 'pending');
                                        ?>
                                        <span class="badge bg-warning"><?= count($pendingReviews) ?></span>
                                    </div>
                                </div>
                                
                                <div class="info-item">
                                    <div class="info-label">Waktu Upload</div>
                                    <div class="info-value">
                                        <?php 
                                        $uploadTime = strtotime($abstrak['tanggal_upload']);
                                        $daysPassed = floor((time() - $uploadTime) / 86400);
                                        ?>
                                        <span class="badge bg-info"><?= $daysPassed ?> hari lalu</span>
                                    </div>
                                </div>
                            </div>

                            <!-- Navigation -->
                            <div class="detail-card">
                                <h5 class="mb-4">
                                    <i class="fas fa-compass me-2 text-info"></i>
                                    Navigasi
                                </h5>
                                
                                <div class="d-grid gap-2">
                                    <a href="<?= site_url('admin/abstrak') ?>" class="btn btn-outline-secondary btn-custom">
                                        <i class="fas fa-arrow-left me-2"></i>Kembali ke Daftar
                                    </a>
                                    
                                    <a href="<?= site_url('admin/users/detail/' . $abstrak['id_user']) ?>" 
                                       class="btn btn-outline-info btn-custom">
                                        <i class="fas fa-user me-2"></i>Lihat Profil Penulis
                                    </a>
                                    
                                    <?php if (isset($abstrak['event_id']) && !empty($abstrak['event_id'])): ?>
                                    <a href="<?= site_url('admin/event/detail/' . $abstrak['event_id']) ?>" 
                                       class="btn btn-outline-success btn-custom">
                                        <i class="fas fa-calendar me-2"></i>Lihat Detail Event
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
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
                        <input type="hidden" id="abstrakId" value="<?= $abstrak['id_abstrak'] ?>">
                        <div class="mb-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" id="statusSelect" required>
                                <option value="menunggu" <?= $abstrak['status'] === 'menunggu' ? 'selected' : '' ?>>Menunggu</option>
                                <option value="sedang_direview" <?= $abstrak['status'] === 'sedang_direview' ? 'selected' : '' ?>>Sedang Review</option>
                                <option value="diterima" <?= $abstrak['status'] === 'diterima' ? 'selected' : '' ?>>Diterima</option>
                                <option value="ditolak" <?= $abstrak['status'] === 'ditolak' ? 'selected' : '' ?>>Ditolak</option>
                                <option value="revisi" <?= $abstrak['status'] === 'revisi' ? 'selected' : '' ?>>Perlu Revisi</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Komentar Admin</label>
                            <textarea class="form-control" id="statusKomentar" rows="4" placeholder="Tambahkan komentar atau catatan untuk perubahan status ini..."></textarea>
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
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.7.12/sweetalert2.min.js"></script>

    <script>
        function updateStatus() {
            $('#statusModal').modal('show');
        }

        function assignReviewer() {
            Swal.fire({
                title: 'Assign Reviewer',
                text: 'Fitur ini akan mengarahkan ke halaman assign reviewer',
                icon: 'info',
                confirmButtonText: 'OK'
            }).then(() => {
                window.location.href = '<?= site_url('admin/abstrak?assign=' . $abstrak['id_abstrak']) ?>';
            });
        }

        function sendMessage() {
            Swal.fire({
                title: 'Kirim Pesan',
                input: 'textarea',
                inputLabel: 'Pesan untuk penulis',
                inputPlaceholder: 'Tulis pesan Anda di sini...',
                inputAttributes: {
                    'aria-label': 'Tulis pesan Anda di sini',
                    'rows': '5'
                },
                showCancelButton: true,
                confirmButtonText: 'Kirim',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    // Implementasi kirim pesan
                    Swal.fire('Terkirim!', 'Pesan berhasil dikirim ke penulis', 'success');
                }
            });
        }

        function deleteAbstrak() {
            Swal.fire({
                title: 'Hapus Abstrak?',
                text: 'Data ini tidak dapat dikembalikan setelah dihapus!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '<?= site_url('admin/abstrak/delete/' . $abstrak['id_abstrak']) ?>';
                }
            });
        }

        function previewFile() {
            // Implementasi preview file (bisa menggunakan iframe atau external viewer)
            const url = '<?= site_url('admin/abstrak/download/' . $abstrak['id_abstrak']) ?>';
            window.open(url, '_blank');
        }

        // Handle status form submission
        $('#statusForm').on('submit', function(e) {
            e.preventDefault();
            
            $.ajax({
                url: '<?= site_url('admin/abstrak/update-status') ?>',
                method: 'POST',
                data: {
                    id_abstrak: $('#abstrakId').val(),
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

        // Show success/error messages
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