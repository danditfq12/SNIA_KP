<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Abstrak - <?= esc($abstract['judul']) ?></title>
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
            --purple-color: #8b5cf6;
        }

        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }

        .sidebar {
            background: linear-gradient(180deg, var(--purple-color) 0%, #7c3aed 100%);
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

        .detail-header {
            background: linear-gradient(135deg, var(--purple-color) 0%, #7c3aed 100%);
            color: white;
            padding: 30px;
            border-radius: 16px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .detail-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            transform: translate(30px, -30px);
        }

        .info-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            margin-bottom: 24px;
            border-left: 4px solid var(--primary-color);
        }

        .status-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            margin-bottom: 24px;
            position: relative;
            overflow: hidden;
        }

        .status-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }

        .status-card.menunggu::before { background: var(--warning-color); }
        .status-card.sedang_direview::before { background: var(--info-color); }
        .status-card.diterima::before { background: var(--success-color); }
        .status-card.ditolak::before { background: var(--danger-color); }
        .status-card.revisi::before { background: var(--secondary-color); }

        .status-badge {
            display: inline-block;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .status-menunggu { background: #fef3c7; color: #d97706; }
        .status-sedang_direview { background: #dbeafe; color: #1d4ed8; }
        .status-diterima { background: #d1fae5; color: #059669; }
        .status-ditolak { background: #fee2e2; color: #dc2626; }
        .status-revisi { background: #f1f5f9; color: #64748b; }

        .timeline {
            position: relative;
            padding: 20px 0;
        }

        .timeline-item {
            display: flex;
            margin-bottom: 24px;
            position: relative;
        }

        .timeline-item:not(:last-child)::after {
            content: '';
            position: absolute;
            left: 20px;
            top: 50px;
            bottom: -24px;
            width: 2px;
            background: #e2e8f0;
        }

        .timeline-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 16px;
            flex-shrink: 0;
            position: relative;
            z-index: 2;
        }

        .timeline-icon.upload {
            background: var(--primary-color);
            color: white;
        }

        .timeline-icon.review {
            background: var(--info-color);
            color: white;
        }

        .timeline-icon.accepted {
            background: var(--success-color);
            color: white;
        }

        .timeline-icon.rejected {
            background: var(--danger-color);
            color: white;
        }

        .timeline-icon.revision {
            background: var(--warning-color);
            color: white;
        }

        .timeline-content {
            flex: 1;
            background: #f8fafc;
            padding: 16px;
            border-radius: 12px;
            position: relative;
        }

        .timeline-content::before {
            content: '';
            position: absolute;
            left: -8px;
            top: 20px;
            width: 0;
            height: 0;
            border-top: 8px solid transparent;
            border-bottom: 8px solid transparent;
            border-right: 8px solid #f8fafc;
        }

        .review-card {
            background: white;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
        }

        .reviewer-badge {
            background: var(--info-color);
            color: white;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .file-preview {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }

        .file-icon {
            width: 80px;
            height: 80px;
            background: var(--primary-color);
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 16px;
            color: white;
        }

        .action-section {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            margin-bottom: 24px;
            text-align: center;
        }

        .btn-action-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--info-color) 100%);
            border: none;
            border-radius: 12px;
            padding: 12px 24px;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            margin: 8px;
        }

        .btn-action-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.3);
            color: white;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin: 20px 0;
        }

        .meta-item {
            text-align: center;
            padding: 16px;
            background: #f8fafc;
            border-radius: 12px;
        }

        .meta-value {
            font-size: 18px;
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 4px;
        }

        .meta-label {
            font-size: 12px;
            color: #64748b;
            text-transform: uppercase;
            font-weight: 600;
        }

        .breadcrumb-custom {
            background: none;
            padding: 0;
            margin-bottom: 20px;
        }

        .breadcrumb-custom .breadcrumb-item {
            color: #64748b;
        }

        .breadcrumb-custom .breadcrumb-item.active {
            color: var(--primary-color);
            font-weight: 600;
        }

        .upload-dropzone {
            border: 2px dashed #e2e8f0;
            border-radius: 12px;
            padding: 30px 20px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            margin: 16px 0;
        }

        .upload-dropzone:hover {
            border-color: var(--primary-color);
            background: #f8fafc;
        }

        .file-input-hidden {
            position: absolute;
            width: 1px;
            height: 1px;
            opacity: 0;
            overflow: hidden;
        }

        .decision-badge {
            padding: 6px 12px;
            border-radius: 16px;
            font-size: 11px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .decision-diterima { background: #d1fae5; color: #059669; }
        .decision-ditolak { background: #fee2e2; color: #dc2626; }
        .decision-revisi { background: #fef3c7; color: #d97706; }
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
                            <i class="fas fa-chalkboard-teacher me-2"></i>
                            SNIA Presenter
                        </h4>
                        <small class="text-white-50">Abstrak Detail</small>
                    </div>
                    
                    <nav class="nav flex-column px-3">
                        <a class="nav-link" href="<?= site_url('presenter/dashboard') ?>">
                            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                        </a>
                        <a class="nav-link" href="<?= site_url('presenter/events') ?>">
                            <i class="fas fa-calendar-alt me-2"></i> Event
                        </a>
                        <a class="nav-link active" href="<?= site_url('presenter/abstrak') ?>">
                            <i class="fas fa-file-alt me-2"></i> Abstrak
                        </a>
                        <a class="nav-link" href="<?= site_url('presenter/pembayaran') ?>">
                            <i class="fas fa-credit-card me-2"></i> Pembayaran
                        </a>
                        <a class="nav-link" href="<?= site_url('presenter/absensi') ?>">
                            <i class="fas fa-qrcode me-2"></i> Absensi
                        </a>
                        <a class="nav-link" href="<?= site_url('presenter/dokumen/loa') ?>">
                            <i class="fas fa-certificate me-2"></i> Dokumen
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
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb breadcrumb-custom">
                            <li class="breadcrumb-item">
                                <a href="<?= site_url('presenter/dashboard') ?>">Dashboard</a>
                            </li>
                            <li class="breadcrumb-item">
                                <a href="<?= site_url('presenter/abstrak') ?>">Abstrak</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">
                                Detail Abstrak
                            </li>
                        </ol>
                    </nav>

                    <!-- Header -->
                    <div class="detail-header">
                        <div class="row align-items-center">
                            <div class="col">
                                <div class="d-flex align-items-center mb-3">
                                    <h1 class="mb-0 me-3">
                                        <i class="fas fa-file-alt me-3"></i>Detail Abstrak
                                    </h1>
                                    <span class="status-badge status-<?= $abstract['status'] ?>">
                                        <?= ucfirst($abstract['status']) ?>
                                    </span>
                                </div>
                                <h3 class="mb-2"><?= esc($abstract['judul']) ?></h3>
                                <p class="mb-0 opacity-75">
                                    <strong>Event:</strong> <?= esc($abstract['event_title'] ?? 'Event tidak ditemukan') ?>
                                </p>
                            </div>
                            <div class="col-auto">
                                <div class="text-end">
                                    <small class="opacity-75 d-block">Diupload pada</small>
                                    <strong><?= date('d F Y, H:i', strtotime($abstract['tanggal_upload'])) ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Abstract Information -->
                    <div class="info-card">
                        <h5 class="mb-3">
                            <i class="fas fa-info-circle text-primary me-2"></i>
                            Informasi Abstrak
                        </h5>
                        
                        <div class="meta-grid">
                            <div class="meta-item">
                                <div class="meta-value"><?= esc($abstract['nama_kategori'] ?? 'Tidak ada') ?></div>
                                <div class="meta-label">Kategori</div>
                            </div>
                            <div class="meta-item">
                                <div class="meta-value"><?= esc($abstract['author_name'] ?? session('nama_lengkap')) ?></div>
                                <div class="meta-label">Penulis</div>
                            </div>
                            <div class="meta-item">
                                <div class="meta-value"><?= date('d M Y', strtotime($abstract['tanggal_upload'])) ?></div>
                                <div class="meta-label">Tanggal Upload</div>
                            </div>
                            <div class="meta-item">
                                <div class="meta-value">Revisi ke-<?= $abstract['revisi_ke'] ?></div>
                                <div class="meta-label">Versi</div>
                            </div>
                        </div>

                        <!-- File Preview -->
                        <div class="file-preview">
                            <div class="file-icon">
                                <i class="fas fa-<?= getFileIcon($abstract['file_abstrak']) ?> fa-2x"></i>
                            </div>
                            <h6 class="mb-2"><?= esc($abstract['file_abstrak']) ?></h6>
                            <p class="text-muted mb-3">File abstrak yang telah diupload</p>
                            <a href="<?= site_url('presenter/abstrak/download/' . $abstract['file_abstrak']) ?>" 
                               class="btn btn-outline-primary" target="_blank">
                                <i class="fas fa-download me-2"></i>Download File
                            </a>
                        </div>
                    </div>

                    <!-- Status & Timeline -->
                    <div class="status-card <?= $abstract['status'] ?>">
                        <h5 class="mb-3">
                            <i class="fas fa-history text-info me-2"></i>
                            Status & Timeline
                        </h5>

                        <div class="timeline">
                            <!-- Upload Timeline -->
                            <div class="timeline-item">
                                <div class="timeline-icon upload">
                                    <i class="fas fa-upload"></i>
                                </div>
                                <div class="timeline-content">
                                    <h6 class="mb-1">Abstrak Diupload</h6>
                                    <p class="mb-1">Abstrak berhasil diupload ke sistem</p>
                                    <small class="text-muted">
                                        <?= date('d F Y, H:i', strtotime($abstract['tanggal_upload'])) ?> WIB
                                    </small>
                                </div>
                            </div>

                            <!-- Review Process -->
                            <?php if (in_array($abstract['status'], ['sedang_direview', 'diterima', 'ditolak', 'revisi'])): ?>
                            <div class="timeline-item">
                                <div class="timeline-icon review">
                                    <i class="fas fa-search"></i>
                                </div>
                                <div class="timeline-content">
                                    <h6 class="mb-1">Proses Review</h6>
                                    <p class="mb-1">Abstrak sedang/telah direview oleh tim reviewer</p>
                                    <small class="text-muted">
                                        <?php if (!empty($reviews)): ?>
                                            Review dimulai: <?= date('d F Y', strtotime($reviews[0]['tanggal_review'])) ?>
                                        <?php else: ?>
                                            Status: <?= ucfirst($abstract['status']) ?>
                                        <?php endif; ?>
                                    </small>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- Final Decision -->
                            <?php if (in_array($abstract['status'], ['diterima', 'ditolak', 'revisi'])): ?>
                            <div class="timeline-item">
                                <div class="timeline-icon <?= $abstract['status'] === 'diterima' ? 'accepted' : ($abstract['status'] === 'ditolak' ? 'rejected' : 'revision') ?>">
                                    <i class="fas fa-<?= $abstract['status'] === 'diterima' ? 'check' : ($abstract['status'] === 'ditolak' ? 'times' : 'edit') ?>"></i>
                                </div>
                                <div class="timeline-content">
                                    <h6 class="mb-1">
                                        <?php if ($abstract['status'] === 'diterima'): ?>
                                            Abstrak Diterima
                                        <?php elseif ($abstract['status'] === 'ditolak'): ?>
                                            Abstrak Ditolak
                                        <?php else: ?>
                                            Perlu Revisi
                                        <?php endif; ?>
                                    </h6>
                                    <p class="mb-1">
                                        <?php if ($abstract['status'] === 'diterima'): ?>
                                            Selamat! Abstrak Anda telah diterima dan dapat melanjutkan ke tahap pembayaran.
                                        <?php elseif ($abstract['status'] === 'ditolak'): ?>
                                            Mohon maaf, abstrak tidak dapat diterima. Lihat feedback untuk perbaikan di masa depan.
                                        <?php else: ?>
                                            Abstrak memerlukan perbaikan sesuai feedback reviewer.
                                        <?php endif; ?>
                                    </p>
                                    <?php if (!empty($reviews)): ?>
                                    <small class="text-muted">
                                        Keputusan final: <?= date('d F Y', strtotime($reviews[0]['tanggal_review'])) ?>
                                    </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Review Results -->
                    <?php if (!empty($reviews)): ?>
                    <div class="info-card">
                        <h5 class="mb-3">
                            <i class="fas fa-comments text-success me-2"></i>
                            Hasil Review (<?= count($reviews) ?> Review)
                        </h5>

                        <?php foreach ($reviews as $index => $review): ?>
                        <div class="review-card">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="d-flex align-items-center">
                                    <div class="reviewer-badge me-2">
                                        Reviewer <?= $index + 1 ?>
                                    </div>
                                    <span class="decision-badge decision-<?= $review['keputusan'] ?>">
                                        <?= ucfirst($review['keputusan']) ?>
                                    </span>
                                </div>
                                <small class="text-muted">
                                    <?= date('d M Y, H:i', strtotime($review['tanggal_review'])) ?>
                                </small>
                            </div>

                            <?php if (!empty($review['komentar'])): ?>
                            <div class="bg-light p-3 rounded">
                                <h6 class="mb-2">Komentar & Feedback:</h6>
                                <p class="mb-0"><?= nl2br(esc($review['komentar'])) ?></p>
                            </div>
                            <?php else: ?>
                            <div class="text-muted fst-italic">
                                Tidak ada komentar tambahan dari reviewer.
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Event Information -->
                    <?php if ($event): ?>
                    <div class="info-card">
                        <h5 class="mb-3">
                            <i class="fas fa-calendar text-warning me-2"></i>
                            Informasi Event
                        </h5>
                        
                        <div class="row g-3">
                            <div class="col-md-6">
                                <h6><?= esc($event['title']) ?></h6>
                                <?php if (!empty($event['description'])): ?>
                                <p class="text-muted"><?= esc($event['description']) ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="col-md-6">
                                <div class="meta-grid">
                                    <div class="meta-item">
                                        <div class="meta-value"><?= date('d M Y', strtotime($event['event_date'])) ?></div>
                                        <div class="meta-label">Tanggal Event</div>
                                    </div>
                                    <div class="meta-item">
                                        <div class="meta-value"><?= date('H:i', strtotime($event['event_time'])) ?></div>
                                        <div class="meta-label">Waktu</div>
                                    </div>
                                </div>
                                
                                <?php if ($event['abstract_deadline']): ?>
                                <div class="alert alert-info mt-3">
                                    <i class="fas fa-clock me-2"></i>
                                    <strong>Deadline Abstrak:</strong> 
                                    <?= date('d F Y, H:i', strtotime($event['abstract_deadline'])) ?> WIB
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Action Section -->
                    <div class="action-section">
                        <h5 class="mb-4">
                            <i class="fas fa-bolt text-warning me-2"></i>
                            Aksi Yang Tersedia
                        </h5>

                        <?php if ($abstract['status'] === 'diterima'): ?>
                            <!-- Abstract accepted - can proceed to payment -->
                            <div class="alert alert-success mb-4">
                                <i class="fas fa-check-circle me-2"></i>
                                <strong>Selamat!</strong> Abstrak Anda telah diterima. Silakan lakukan pembayaran untuk mengkonfirmasi partisipasi.
                            </div>
                            
                            <a href="<?= site_url('presenter/pembayaran/create/' . $abstract['event_id']) ?>" 
                               class="btn-action-primary">
                                <i class="fas fa-credit-card me-2"></i>
                                Lakukan Pembayaran
                            </a>
                            
                        <?php elseif (in_array($abstract['status'], ['revisi', 'ditolak']) && $can_revise): ?>
                            <!-- Can revise -->
                            <div class="alert alert-warning mb-4">
                                <i class="fas fa-edit me-2"></i>
                                <strong>Revisi Diperlukan:</strong> Perbaiki abstrak sesuai feedback reviewer dan upload versi terbaru.
                            </div>
                            
                            <button class="btn-action-primary" data-bs-toggle="modal" data-bs-target="#revisionModal">
                                <i class="fas fa-edit me-2"></i>
                                Upload Revisi
                            </button>
                            
                        <?php elseif ($abstract['status'] === 'menunggu'): ?>
                            <!-- Waiting for review -->
                            <div class="alert alert-info mb-4">
                                <i class="fas fa-clock me-2"></i>
                                <strong>Menunggu Review:</strong> Abstrak Anda sedang dalam antrian untuk direview oleh tim reviewer.
                            </div>
                            
                        <?php elseif ($abstract['status'] === 'sedang_direview'): ?>
                            <!-- Under review -->
                            <div class="alert alert-info mb-4">
                                <i class="fas fa-eye me-2"></i>
                                <strong>Sedang Direview:</strong> Tim reviewer sedang mengevaluasi abstrak Anda. Mohon tunggu hasilnya.
                            </div>
                            
                        <?php elseif ($abstract['status'] === 'ditolak'): ?>
                            <!-- Rejected -->
                            <div class="alert alert-danger mb-4">
                                <i class="fas fa-times-circle me-2"></i>
                                <strong>Abstrak Ditolak:</strong> Mohon maaf, abstrak tidak dapat diterima untuk event ini.
                            </div>
                            
                        <?php endif; ?>

                        <!-- Always available actions -->
                        <div class="d-flex gap-2 justify-content-center flex-wrap">
                            <a href="<?= site_url('presenter/abstrak/download/' . $abstract['file_abstrak']) ?>" 
                               class="btn btn-outline-primary" target="_blank">
                                <i class="fas fa-download me-2"></i>Download File
                            </a>
                            
                            <a href="<?= site_url('presenter/abstrak') ?>" 
                               class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left me-2"></i>Kembali ke Daftar
                            </a>
                            
                            <a href="<?= site_url('presenter/dashboard') ?>" 
                               class="btn btn-outline-info">
                                <i class="fas fa-chart-line me-2"></i>Dashboard
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Revision Modal -->
    <div class="modal fade" id="revisionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit text-warning me-2"></i>
                        Upload Revisi Abstrak
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="revisionForm" action="<?= site_url('presenter/abstrak/upload') ?>" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="event_id" value="<?= $abstract['event_id'] ?>">
                    <input type="hidden" name="id_kategori" value="<?= $abstract['id_kategori'] ?>">
                    
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Revisi untuk:</strong> <?= esc($abstract['judul']) ?>
                        </div>

                        <!-- Show review feedback -->
                        <?php if (!empty($reviews)): ?>
                        <div class="mb-4">
                            <h6>Feedback dari Reviewer:</h6>
                            <?php foreach ($reviews as $review): ?>
                                <?php if (!empty($review['komentar'])): ?>
                                <div class="bg-light p-3 rounded mb-2">
                                    <small class="text-muted d-block">Reviewer:</small>
                                    <p class="mb-0"><?= nl2br(esc($review['komentar'])) ?></p>
                                </div>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>

                        <!-- Title input (can be changed) -->
                        <div class="mb-3">
                            <label class="form-label">Judul Abstrak (dapat diubah)</label>
                            <input type="text" name="judul" class="form-control" 
                                   value="<?= esc($abstract['judul']) ?>" required maxlength="255">
                        </div>

                        <!-- File upload -->
                        <div class="mb-3">
                            <label class="form-label">File Abstrak yang Telah Direvisi</label>
                            <div class="upload-dropzone" onclick="document.getElementById('revisionFileInput').click()">
                                <i class="fas fa-cloud-upload-alt fa-3x text-warning mb-3"></i>
                                <h6>Upload file abstrak yang sudah diperbaiki</h6>
                                <p class="text-muted mb-0">Format: PDF, DOC, DOCX (Maksimal 10MB)</p>
                                <input type="file" name="file_abstrak" id="revisionFileInput" 
                                       class="file-input-hidden" accept=".pdf,.doc,.docx" required>
                            </div>
                            <div id="revisionFilePreview"></div>
                        </div>

                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <strong>Catatan:</strong> File baru akan menggantikan file sebelumnya. Pastikan semua feedback reviewer telah diperbaiki.
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Batal
                        </button>
                        <button type="submit" class="btn btn-warning">
                            <i class="fas fa-edit me-2"></i>Upload Revisi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container position-fixed top-0 end-0 p-3" id="toastContainer"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Setup file upload for revision
            setupFileUpload();
            
            // Setup form handler
            setupRevisionForm();
            
            // Show flash messages
            showFlashMessages();

            // Auto-refresh status every 2 minutes
            setInterval(refreshAbstractStatus, 120000);
        });

        function setupFileUpload() {
            const fileInput = document.getElementById('revisionFileInput');
            if (!fileInput) return;

            fileInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    if (validateFile(file)) {
                        showFilePreview(file);
                    } else {
                        this.value = '';
                        clearFilePreview();
                    }
                }
            });

            // Drag and drop
            const dropzone = document.querySelector('.upload-dropzone');
            if (dropzone) {
                dropzone.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    this.style.borderColor = 'var(--primary-color)';
                    this.style.background = '#f8fafc';
                });
                
                dropzone.addEventListener('dragleave', function(e) {
                    e.preventDefault();
                    this.style.borderColor = '#e2e8f0';
                    this.style.background = '';
                });
                
                dropzone.addEventListener('drop', function(e) {
                    e.preventDefault();
                    this.style.borderColor = '#e2e8f0';
                    this.style.background = '';
                    
                    const files = e.dataTransfer.files;
                    if (files.length > 0) {
                        fileInput.files = files;
                        fileInput.dispatchEvent(new Event('change'));
                    }
                });
            }
        }

        function validateFile(file) {
            const maxSize = 10 * 1024 * 1024; // 10MB
            const allowedTypes = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
            
            if (file.size > maxSize) {
                showToast('Error!', 'Ukuran file maksimal 10MB', 'danger');
                return false;
            }
            
            if (!allowedTypes.includes(file.type)) {
                showToast('Error!', 'Format file harus PDF, DOC, atau DOCX', 'danger');
                return false;
            }
            
            return true;
        }

        function showFilePreview(file) {
            const preview = document.getElementById('revisionFilePreview');
            if (!preview) return;

            const fileSize = (file.size / 1024 / 1024).toFixed(2);
            const fileIcon = getFileIcon(file.name);
            
            preview.innerHTML = `
                <div class="file-preview mt-3">
                    <div class="d-flex align-items-center">
                        <div class="file-icon me-3" style="width: 50px; height: 50px;">
                            <i class="fas fa-${fileIcon} fa-lg"></i>
                        </div>
                        <div class="flex-grow-1">
                            <div class="fw-bold">${file.name}</div>
                            <small class="text-muted">${fileSize} MB</small>
                        </div>
                        <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearFilePreview()">
                            <i class="fas fa-times"></i>
                        </button>
                    </div>
                </div>
            `;
        }

        function clearFilePreview() {
            const preview = document.getElementById('revisionFilePreview');
            const fileInput = document.getElementById('revisionFileInput');
            
            if (preview) preview.innerHTML = '';
            if (fileInput) fileInput.value = '';
        }

        function getFileIcon(filename) {
            const ext = filename.toLowerCase().split('.').pop();
            switch(ext) {
                case 'pdf': return 'file-pdf';
                case 'doc':
                case 'docx': return 'file-word';
                default: return 'file-alt';
            }
        }

        function setupRevisionForm() {
            const form = document.getElementById('revisionForm');
            if (!form) return;

            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                const submitBtn = this.querySelector('button[type="submit"]');
                const originalText = submitBtn.innerHTML;
                
                // Show loading state
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Mengupload revisi...';
                submitBtn.disabled = true;

                // Create FormData
                const formData = new FormData(this);

                fetch(this.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Berhasil!', data.message, 'success');
                        
                        // Close modal
                        const modal = bootstrap.Modal.getInstance(document.getElementById('revisionModal'));
                        modal.hide();
                        
                        // Redirect to detail page
                        setTimeout(() => {
                            if (data.redirect_url) {
                                window.location.href = data.redirect_url;
                            } else {
                                location.reload();
                            }
                        }, 1500);
                    } else {
                        showToast('Error!', data.message, 'danger');
                    }
                })
                .catch(error => {
                    console.error('Revision upload error:', error);
                    showToast('Error!', 'Terjadi kesalahan saat mengupload revisi', 'danger');
                })
                .finally(() => {
                    // Reset button
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                });
            });
        }

        function refreshAbstractStatus() {
            fetch('<?= site_url('presenter/abstrak/status') ?>', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const currentAbstract = data.data.find(a => a.id == <?= $abstract['id_abstrak'] ?>);
                    if (currentAbstract && currentAbstract.status !== '<?= $abstract['status'] ?>') {
                        // Status changed, reload page
                        location.reload();
                    }
                }
            })
            .catch(error => {
                console.log('Status refresh error:', error);
            });
        }

        function showFlashMessages() {
            <?php if (session()->getFlashdata('success')): ?>
                showToast('Berhasil!', '<?= esc(session()->getFlashdata('success')) ?>', 'success');
            <?php endif; ?>
            
            <?php if (session()->getFlashdata('error')): ?>
                showToast('Error!', '<?= esc(session()->getFlashdata('error')) ?>', 'danger');
            <?php endif; ?>
            
            <?php if (session()->getFlashdata('info')): ?>
                showToast('Info', '<?= esc(session()->getFlashdata('info')) ?>', 'info');
            <?php endif; ?>
        }

        function showToast(title, message, type) {
            const toastHtml = `
                <div class="toast align-items-center text-white bg-${type} border-0" role="alert">
                    <div class="d-flex">
                        <div class="toast-body">
                            <strong>${title}</strong> ${message}
                        </div>
                        <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                    </div>
                </div>
            `;
            
            const toastContainer = document.getElementById('toastContainer');
            toastContainer.insertAdjacentHTML('beforeend', toastHtml);
            
            const toastElement = toastContainer.querySelector('.toast:last-child');
            const toast = new bootstrap.Toast(toastElement, { autohide: true, delay: 5000 });
            toast.show();

            toastElement.addEventListener('hidden.bs.toast', function() {
                toastElement.remove();
            });
        }

        // Helper function for file icon (should be available globally)
        <?php
        function getFileIcon($filename) {
            $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
            switch($ext) {
                case 'pdf': return 'file-pdf';
                case 'doc':
                case 'docx': return 'file-word';
                default: return 'file-alt';
            }
        }
        ?>
    </script>
</body>
</html>