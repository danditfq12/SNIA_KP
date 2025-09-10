<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Abstrak - SNIA Presenter</title>
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

        .abstrak-header {
            background: linear-gradient(135deg, var(--purple-color) 0%, #7c3aed 100%);
            color: white;
            padding: 30px;
            border-radius: 16px;
            margin-bottom: 30px;
        }

        .abstrak-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            border: 1px solid rgba(255,255,255,0.2);
            margin-bottom: 24px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .abstrak-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.12);
        }

        .abstrak-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }

        .abstrak-card.menunggu::before { background: var(--warning-color); }
        .abstrak-card.sedang_direview::before { background: var(--info-color); }
        .abstrak-card.diterima::before { background: var(--success-color); }
        .abstrak-card.ditolak::before { background: var(--danger-color); }
        .abstrak-card.revisi::before { background: var(--secondary-color); }

        .status-badge {
            position: absolute;
            top: 16px;
            right: 16px;
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

        .upload-section {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            margin-bottom: 24px;
        }

        .upload-dropzone {
            border: 2px dashed #e2e8f0;
            border-radius: 12px;
            padding: 40px 20px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }

        .upload-dropzone:hover {
            border-color: var(--primary-color);
            background: #f8fafc;
        }

        .upload-dropzone.dragover {
            border-color: var(--primary-color);
            background: #dbeafe;
        }

        .file-input-hidden {
            position: absolute;
            width: 1px;
            height: 1px;
            opacity: 0;
            overflow: hidden;
        }

        .event-selector {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            margin-bottom: 24px;
        }

        .event-option {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 12px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .event-option:hover {
            border-color: var(--primary-color);
            background: #f8fafc;
        }

        .event-option.selected {
            border-color: var(--primary-color);
            background: #dbeafe;
        }

        .event-option input[type="radio"] {
            display: none;
        }

        .abstrak-meta {
            display: flex;
            flex-wrap: wrap;
            gap: 16px;
            margin: 16px 0;
            padding: 16px;
            background: #f8fafc;
            border-radius: 12px;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: #64748b;
            font-size: 14px;
        }

        .meta-item i {
            color: var(--primary-color);
        }

        .btn-action {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--info-color) 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 24px;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
            margin: 4px;
        }

        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.3);
            color: white;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #64748b;
        }

        .empty-state i {
            font-size: 64px;
            margin-bottom: 20px;
            opacity: 0.5;
        }

        .guidelines-section {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
        }

        .step-indicator {
            display: flex;
            align-items: center;
            margin: 20px 0;
        }

        .step {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            background: #e2e8f0;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 14px;
            font-weight: 600;
        }

        .step.active {
            background: var(--primary-color);
            color: white;
        }

        .step.completed {
            background: var(--success-color);
            color: white;
        }

        .step-line {
            flex: 1;
            height: 2px;
            background: #e2e8f0;
            margin: 0 12px;
        }

        .step-line.completed {
            background: var(--success-color);
        }

        .progress-info {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.05);
            margin: 20px 0;
        }

        .deadline-alert {
            background: #fef3c7;
            border: 1px solid #fbbf24;
            color: #92400e;
            padding: 12px 16px;
            border-radius: 8px;
            margin: 16px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .deadline-alert.danger {
            background: #fee2e2;
            border-color: #f87171;
            color: #dc2626;
        }

        .file-preview {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px;
            margin: 12px 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .file-icon {
            width: 40px;
            height: 40px;
            background: var(--primary-color);
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
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
                            <i class="fas fa-chalkboard-teacher me-2"></i>
                            SNIA Presenter
                        </h4>
                        <small class="text-white-50">Abstrak Management</small>
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
                    <!-- Header -->
                    <div class="abstrak-header">
                        <div class="row align-items-center">
                            <div class="col">
                                <h1 class="mb-2">
                                    <i class="fas fa-file-alt me-3"></i>Manajemen Abstrak
                                </h1>
                                <p class="mb-0 opacity-75">
                                    Upload dan kelola abstrak untuk partisipasi sebagai presenter
                                </p>
                            </div>
                            <div class="col-auto">
                                <?php if (!empty($available_events)): ?>
                                <button class="btn btn-light" data-bs-toggle="modal" data-bs-target="#uploadModal">
                                    <i class="fas fa-plus me-2"></i>Upload Abstrak Baru
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Quick Upload Section (if event_id is provided) -->
                    <?php if (isset($selected_event_id) && $selected_event_id): ?>
                    <div class="upload-section">
                        <h5 class="mb-3">
                            <i class="fas fa-upload text-primary me-2"></i>
                            Upload Abstrak untuk Event
                        </h5>
                        
                        <?php 
                        $selectedEvent = null;
                        foreach ($available_events as $event) {
                            if ($event['id'] == $selected_event_id) {
                                $selectedEvent = $event;
                                break;
                            }
                        }
                        ?>

                        <?php if ($selectedEvent): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            <strong>Event:</strong> <?= esc($selectedEvent['title']) ?> - 
                            <?= date('d F Y', strtotime($selectedEvent['event_date'])) ?>
                        </div>

                        <form id="quickUploadForm" action="<?= site_url('presenter/abstrak/upload') ?>" method="POST" enctype="multipart/form-data">
                            <input type="hidden" name="event_id" value="<?= $selected_event_id ?>">
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Kategori Abstrak</label>
                                    <select name="id_kategori" class="form-control" required>
                                        <option value="">Pilih Kategori</option>
                                        <?php foreach ($categories as $category): ?>
                                        <option value="<?= $category['id_kategori'] ?>">
                                            <?= esc($category['nama_kategori']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Judul Abstrak</label>
                                    <input type="text" name="judul" class="form-control" required 
                                           placeholder="Masukkan judul abstrak Anda" maxlength="255">
                                </div>
                                <div class="col-12">
                                    <label class="form-label">File Abstrak</label>
                                    <div class="upload-dropzone" onclick="document.getElementById('quickFileInput').click()">
                                        <i class="fas fa-cloud-upload-alt fa-3x text-primary mb-3"></i>
                                        <h6>Klik untuk upload file atau drag & drop</h6>
                                        <p class="text-muted mb-0">Format: PDF, DOC, DOCX (Maksimal 10MB)</p>
                                        <input type="file" name="file_abstrak" id="quickFileInput" 
                                               class="file-input-hidden" accept=".pdf,.doc,.docx" required>
                                    </div>
                                    <div id="quickFilePreview"></div>
                                </div>
                                <div class="col-12">
                                    <button type="submit" class="btn-action">
                                        <i class="fas fa-upload me-2"></i>Upload Abstrak
                                    </button>
                                </div>
                            </div>
                        </form>
                        <?php else: ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Event tidak ditemukan atau tidak tersedia untuk submission abstrak.
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Abstract List -->
                    <div class="mb-4">
                        <h5 class="mb-3">
                            <i class="fas fa-list text-info me-2"></i>
                            Daftar Abstrak Anda
                        </h5>

                        <?php if (!empty($abstracts)): ?>
                            <?php foreach ($abstracts as $abstract): ?>
                            <div class="abstrak-card <?= $abstract['status'] ?>" data-abstract-id="<?= $abstract['id_abstrak'] ?>">
                                <!-- Status Badge -->
                                <span class="status-badge status-<?= $abstract['status'] ?>">
                                    <?= ucfirst($abstract['status']) ?>
                                </span>

                                <!-- Abstract Header -->
                                <div class="mb-3" style="margin-right: 120px;">
                                    <h6 class="mb-2"><?= esc($abstract['judul']) ?></h6>
                                    <div class="text-muted">
                                        <strong>Event:</strong> <?= esc($abstract['event_title'] ?? 'Event tidak ditemukan') ?>
                                    </div>
                                </div>

                                <!-- Abstract Meta -->
                                <div class="abstrak-meta">
                                    <div class="meta-item">
                                        <i class="fas fa-calendar"></i>
                                        <span>Upload: <?= date('d M Y H:i', strtotime($abstract['tanggal_upload'])) ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-tag"></i>
                                        <span><?= esc($abstract['nama_kategori']) ?></span>
                                    </div>
                                    <div class="meta-item">
                                        <i class="fas fa-file"></i>
                                        <span><?= esc($abstract['file_abstrak']) ?></span>
                                    </div>
                                    <?php if ($abstract['revisi_ke'] > 0): ?>
                                    <div class="meta-item">
                                        <i class="fas fa-edit"></i>
                                        <span>Revisi ke-<?= $abstract['revisi_ke'] ?></span>
                                    </div>
                                    <?php endif; ?>
                                    <?php if ($abstract['review_count'] > 0): ?>
                                    <div class="meta-item">
                                        <i class="fas fa-comments"></i>
                                        <span><?= $abstract['review_count'] ?> Review</span>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Progress Indicator -->
                                <div class="step-indicator">
                                    <div class="step completed">
                                        <i class="fas fa-upload"></i>
                                    </div>
                                    <div class="step-line <?= in_array($abstract['status'], ['sedang_direview', 'diterima', 'ditolak']) ? 'completed' : '' ?>"></div>
                                    
                                    <div class="step <?= in_array($abstract['status'], ['sedang_direview', 'diterima', 'ditolak']) ? 'completed' : ($abstract['status'] === 'menunggu' ? 'active' : '') ?>">
                                        <i class="fas fa-search"></i>
                                    </div>
                                    <div class="step-line <?= $abstract['status'] === 'diterima' ? 'completed' : '' ?>"></div>
                                    
                                    <div class="step <?= $abstract['status'] === 'diterima' ? 'completed' : '' ?>">
                                        <i class="fas fa-check"></i>
                                    </div>
                                </div>

                                <!-- Status Info -->
                                <?php if ($abstract['status'] === 'menunggu'): ?>
                                <div class="alert alert-warning mb-3">
                                    <i class="fas fa-clock me-2"></i>
                                    Abstrak sedang menunggu untuk direview oleh tim reviewer.
                                </div>
                                <?php elseif ($abstract['status'] === 'sedang_direview'): ?>
                                <div class="alert alert-info mb-3">
                                    <i class="fas fa-eye me-2"></i>
                                    Abstrak sedang dalam proses review. Mohon tunggu hasilnya.
                                </div>
                                <?php elseif ($abstract['status'] === 'diterima'): ?>
                                <div class="alert alert-success mb-3">
                                    <i class="fas fa-check-circle me-2"></i>
                                    Selamat! Abstrak Anda telah diterima. Lanjutkan ke tahap pembayaran.
                                </div>
                                <?php elseif ($abstract['status'] === 'ditolak'): ?>
                                <div class="alert alert-danger mb-3">
                                    <i class="fas fa-times-circle me-2"></i>
                                    Abstrak tidak dapat diterima. Lihat feedback untuk perbaikan.
                                </div>
                                <?php elseif ($abstract['status'] === 'revisi'): ?>
                                <div class="alert alert-warning mb-3">
                                    <i class="fas fa-edit me-2"></i>
                                    Abstrak memerlukan revisi. Lihat feedback dan upload versi terbaru.
                                </div>
                                <?php endif; ?>

                                <!-- Deadline Alert -->
                                <?php if ($abstract['abstract_submission_active'] && $abstract['abstract_deadline']): ?>
                                    <?php 
                                    $deadline = strtotime($abstract['abstract_deadline']);
                                    $now = time();
                                    $daysLeft = ceil(($deadline - $now) / (60 * 60 * 24));
                                    ?>
                                    <?php if ($daysLeft <= 0): ?>
                                    <div class="deadline-alert danger">
                                        <i class="fas fa-exclamation-triangle"></i>
                                        <span>Deadline submission telah berakhir</span>
                                    </div>
                                    <?php elseif ($daysLeft <= 3): ?>
                                    <div class="deadline-alert">
                                        <i class="fas fa-clock"></i>
                                        <span>Deadline submission dalam <?= $daysLeft ?> hari</span>
                                    </div>
                                    <?php endif; ?>
                                <?php endif; ?>

                                <!-- Action Buttons -->
                                <div class="d-flex gap-2 flex-wrap">
                                    <a href="<?= site_url('presenter/abstrak/detail/' . $abstract['id_abstrak']) ?>" 
                                       class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-eye me-1"></i>Detail
                                    </a>
                                    
                                    <a href="<?= site_url('presenter/abstrak/download/' . $abstract['file_abstrak']) ?>" 
                                       class="btn btn-outline-secondary btn-sm" target="_blank">
                                        <i class="fas fa-download me-1"></i>Download
                                    </a>

                                    <?php if ($abstract['status'] === 'diterima'): ?>
                                        <?php 
                                        // Check if payment already exists
                                        $paymentExists = false; // This should be checked in controller
                                        ?>
                                        <?php if (!$paymentExists): ?>
                                        <a href="<?= site_url('presenter/pembayaran/create/' . $abstract['event_id']) ?>" 
                                           class="btn btn-success btn-sm">
                                            <i class="fas fa-credit-card me-1"></i>Lakukan Pembayaran
                                        </a>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <?php if (in_array($abstract['status'], ['revisi', 'ditolak']) && $abstract['abstract_submission_active']): ?>
                                    <button class="btn btn-warning btn-sm" onclick="openRevisionModal(<?= $abstract['id_abstrak'] ?>, '<?= esc($abstract['judul']) ?>')">
                                        <i class="fas fa-edit me-1"></i>Revisi
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-file-plus"></i>
                                <h4>Belum Ada Abstrak</h4>
                                <p>Anda belum mengupload abstrak untuk event manapun.</p>
                                <?php if (!empty($available_events)): ?>
                                <button class="btn-action" data-bs-toggle="modal" data-bs-target="#uploadModal">
                                    <i class="fas fa-plus me-2"></i>Upload Abstrak Pertama
                                </button>
                                <?php else: ?>
                                <p class="text-muted">Tidak ada event yang tersedia untuk submission abstrak saat ini.</p>
                                <a href="<?= site_url('presenter/events') ?>" class="btn btn-outline-primary">
                                    <i class="fas fa-calendar me-2"></i>Lihat Event Tersedia
                                </a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Guidelines Section -->
                    <div class="guidelines-section">
                        <h6 class="mb-3">
                            <i class="fas fa-lightbulb text-warning me-2"></i>
                            Panduan Upload Abstrak
                        </h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <h6>Format File:</h6>
                                <ul class="mb-0">
                                    <li>PDF, DOC, atau DOCX</li>
                                    <li>Maksimal 10MB</li>
                                    <li>Font minimal 11pt</li>
                                    <li>Margin minimal 2.5cm</li>
                                </ul>
                            </div>
                            <div class="col-md-6">
                                <h6>Struktur Abstrak:</h6>
                                <ul class="mb-0">
                                    <li>Judul (maksimal 20 kata)</li>
                                    <li>Latar belakang & tujuan</li>
                                    <li>Metodologi</li>
                                    <li>Hasil & kesimpulan</li>
                                    <li>Kata kunci (3-5 kata)</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Upload Modal -->
    <div class="modal fade" id="uploadModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-upload text-primary me-2"></i>
                        Upload Abstrak Baru
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="uploadForm" action="<?= site_url('presenter/abstrak/upload') ?>" method="POST" enctype="multipart/form-data">
                    <div class="modal-body">
                        <!-- Event Selection -->
                        <div class="mb-4">
                            <label class="form-label">Pilih Event</label>
                            <?php if (!empty($available_events)): ?>
                                <?php foreach ($available_events as $event): ?>
                                <div class="event-option" onclick="selectEvent(this, <?= $event['id'] ?>)">
                                    <input type="radio" name="event_id" value="<?= $event['id'] ?>" required>
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <h6 class="mb-1"><?= esc($event['title']) ?></h6>
                                            <small class="text-muted">
                                                <?= date('d F Y', strtotime($event['event_date'])) ?>
                                                <?php if ($event['abstract_deadline']): ?>
                                                    - Deadline: <?= date('d M Y', strtotime($event['abstract_deadline'])) ?>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                        <div class="text-end">
                                            <small class="badge bg-success">Buka</small>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Tidak ada event yang tersedia untuk submission abstrak saat ini.
                                </div>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($available_events)): ?>
                        <!-- Category Selection -->
                        <div class="mb-3">
                            <label class="form-label">Kategori Abstrak</label>
                            <select name="id_kategori" class="form-control" required>
                                <option value="">Pilih Kategori</option>
                                <?php foreach ($categories as $category): ?>
                                <option value="<?= $category['id_kategori'] ?>">
                                    <?= esc($category['nama_kategori']) ?>
                                    <?php if (!empty($category['deskripsi'])): ?>
                                        - <?= esc($category['deskripsi']) ?>
                                    <?php endif; ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Title Input -->
                        <div class="mb-3">
                            <label class="form-label">Judul Abstrak</label>
                            <input type="text" name="judul" class="form-control" required 
                                   placeholder="Masukkan judul abstrak yang menarik dan deskriptif" 
                                   maxlength="255">
                            <div class="form-text">Maksimal 255 karakter. Buatlah judul yang jelas dan menggambarkan isi abstrak.</div>
                        </div>

                        <!-- File Upload -->
                        <div class="mb-3">
                            <label class="form-label">File Abstrak</label>
                            <div class="upload-dropzone" onclick="document.getElementById('fileInput').click()">
                                <i class="fas fa-cloud-upload-alt fa-3x text-primary mb-3"></i>
                                <h6>Klik untuk upload file atau drag & drop</h6>
                                <p class="text-muted mb-0">Format: PDF, DOC, DOCX (Maksimal 10MB)</p>
                                <input type="file" name="file_abstrak" id="fileInput" 
                                       class="file-input-hidden" accept=".pdf,.doc,.docx" required>
                            </div>
                            <div id="filePreview"></div>
                        </div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="fas fa-times me-2"></i>Batal
                        </button>
                        <?php if (!empty($available_events)): ?>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-upload me-2"></i>Upload Abstrak
                        </button>
                        <?php endif; ?>
                    </div>
                </form>
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
                        Revisi Abstrak
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form id="revisionForm" action="<?= site_url('presenter/abstrak/upload') ?>" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="revision_id" id="revisionAbstractId">
                    <div class="modal-body">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Anda melakukan revisi untuk abstrak: <strong id="revisionTitle"></strong>
                        </div>

                        <!-- Keep same category and event -->
                        <div class="mb-3">
                            <label class="form-label">Judul Abstrak (Dapat diubah)</label>
                            <input type="text" name="judul" id="revisionTitleInput" class="form-control" required maxlength="255">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">File Abstrak Terbaru</label>
                            <div class="upload-dropzone" onclick="document.getElementById('revisionFileInput').click()">
                                <i class="fas fa-cloud-upload-alt fa-3x text-warning mb-3"></i>
                                <h6>Upload file abstrak yang sudah direvisi</h6>
                                <p class="text-muted mb-0">Format: PDF, DOC, DOCX (Maksimal 10MB)</p>
                                <input type="file" name="file_abstrak" id="revisionFileInput" 
                                       class="file-input-hidden" accept=".pdf,.doc,.docx" required>
                            </div>
                            <div id="revisionFilePreview"></div>
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
            // Initialize modals
            const uploadModal = new bootstrap.Modal(document.getElementById('uploadModal'));
            const revisionModal = new bootstrap.Modal(document.getElementById('revisionModal'));

            // Setup file upload handlers
            setupFileUpload('fileInput', 'filePreview');
            setupFileUpload('quickFileInput', 'quickFilePreview');
            setupFileUpload('revisionFileInput', 'revisionFilePreview');

            // Setup drag and drop
            setupDragAndDrop();

            // Setup form handlers
            setupFormHandlers();

            // Show flash messages
            showFlashMessages();

            // Auto-refresh status every 2 minutes
            setInterval(refreshAbstractStatuses, 120000);
        });

        function setupFileUpload(inputId, previewId) {
            const fileInput = document.getElementById(inputId);
            if (!fileInput) return;

            fileInput.addEventListener('change', function(e) {
                const file = e.target.files[0];
                if (file) {
                    showFilePreview(file, previewId);
                    validateFile(file);
                }
            });
        }

        function showFilePreview(file, previewId) {
            const preview = document.getElementById(previewId);
            if (!preview) return;

            const fileSize = (file.size / 1024 / 1024).toFixed(2);
            const fileIcon = getFileIcon(file.name);
            
            preview.innerHTML = `
                <div class="file-preview">
                    <div class="file-icon">
                        <i class="fas fa-${fileIcon}"></i>
                    </div>
                    <div class="flex-grow-1">
                        <div class="fw-bold">${file.name}</div>
                        <small class="text-muted">${fileSize} MB</small>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="clearFilePreview('${previewId}')">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
            `;
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

        function clearFilePreview(previewId) {
            document.getElementById(previewId).innerHTML = '';
            // Also clear the file input
            const inputs = document.querySelectorAll('input[type="file"]');
            inputs.forEach(input => {
                if (input.closest('.modal').querySelector('#' + previewId)) {
                    input.value = '';
                }
            });
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

        function setupDragAndDrop() {
            const dropzones = document.querySelectorAll('.upload-dropzone');
            
            dropzones.forEach(dropzone => {
                dropzone.addEventListener('dragover', function(e) {
                    e.preventDefault();
                    this.classList.add('dragover');
                });
                
                dropzone.addEventListener('dragleave', function(e) {
                    e.preventDefault();
                    this.classList.remove('dragover');
                });
                
                dropzone.addEventListener('drop', function(e) {
                    e.preventDefault();
                    this.classList.remove('dragover');
                    
                    const files = e.dataTransfer.files;
                    if (files.length > 0) {
                        const fileInput = this.querySelector('input[type="file"]');
                        if (fileInput) {
                            fileInput.files = files;
                            fileInput.dispatchEvent(new Event('change'));
                        }
                    }
                });
            });
        }

        function selectEvent(element, eventId) {
            // Remove selected class from all options
            document.querySelectorAll('.event-option').forEach(option => {
                option.classList.remove('selected');
            });
            
            // Add selected class to clicked option
            element.classList.add('selected');
            
            // Check the radio button
            element.querySelector('input[type="radio"]').checked = true;
        }

        function openRevisionModal(abstractId, title) {
            document.getElementById('revisionAbstractId').value = abstractId;
            document.getElementById('revisionTitle').textContent = title;
            document.getElementById('revisionTitleInput').value = title;
            
            const revisionModal = new bootstrap.Modal(document.getElementById('revisionModal'));
            revisionModal.show();
        }

        function setupFormHandlers() {
            // Upload form handler
            const uploadForm = document.getElementById('uploadForm');
            if (uploadForm) {
                uploadForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    submitForm(this, 'Mengupload abstrak...');
                });
            }

            // Quick upload form handler
            const quickUploadForm = document.getElementById('quickUploadForm');
            if (quickUploadForm) {
                quickUploadForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    submitForm(this, 'Mengupload abstrak...');
                });
            }

            // Revision form handler
            const revisionForm = document.getElementById('revisionForm');
            if (revisionForm) {
                revisionForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    submitForm(this, 'Mengupload revisi...');
                });
            }
        }

        function submitForm(form, loadingText) {
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Show loading state
            submitBtn.innerHTML = `<i class="fas fa-spinner fa-spin me-2"></i>${loadingText}`;
            submitBtn.disabled = true;

            // Create FormData
            const formData = new FormData(form);

            fetch(form.action, {
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
                    
                    // Close modals
                    const modals = bootstrap.Modal.getInstance(form.closest('.modal'));
                    if (modals) modals.hide();
                    
                    // Redirect or refresh
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
                console.error('Upload error:', error);
                showToast('Error!', 'Terjadi kesalahan saat mengupload', 'danger');
            })
            .finally(() => {
                // Reset button
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        }

        function refreshAbstractStatuses() {
            fetch('<?= site_url('presenter/abstrak/status') ?>', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    updateAbstractCards(data.data);
                }
            })
            .catch(error => {
                console.log('Status refresh error:', error);
            });
        }

        function updateAbstractCards(abstracts) {
            abstracts.forEach(abstract => {
                const card = document.querySelector(`[data-abstract-id="${abstract.id}"]`);
                if (card) {
                    // Update status badge
                    const statusBadge = card.querySelector('.status-badge');
                    if (statusBadge) {
                        statusBadge.className = `status-badge status-${abstract.status}`;
                        statusBadge.textContent = abstract.status.charAt(0).toUpperCase() + abstract.status.slice(1);
                    }

                    // Update card class
                    card.className = `abstrak-card ${abstract.status}`;
                }
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
    </script>
</body>
</html>