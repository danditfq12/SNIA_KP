<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Letter of Acceptance - SNIA Presenter</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --success-color: #10b981;
            --warning-color: #f59e0b;
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

        .header-section {
            background: white;
            padding: 24px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        .welcome-text {
            color: var(--primary-color);
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .card-custom {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            border: 1px solid rgba(255,255,255,0.2);
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .card-custom:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.15);
        }

        .card-header-custom {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--info-color) 100%);
            color: white;
            padding: 20px;
            font-weight: 600;
            border-bottom: none;
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

        .document-item {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid var(--success-color);
            transition: all 0.3s ease;
        }

        .document-item:hover {
            background: #f1f5f9;
            transform: translateX(5px);
        }

        .event-item {
            background: #fff7ed;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            border-left: 4px solid var(--warning-color);
            transition: all 0.3s ease;
        }

        .event-item:hover {
            background: #fef3c7;
            transform: translateX(5px);
        }

        .file-icon {
            font-size: 2rem;
            margin-right: 15px;
        }

        .status-badge {
            position: absolute;
            top: 10px;
            right: 10px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #64748b;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
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
                            <i class="fas fa-user-tie me-2"></i>
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
                        <a class="nav-link" href="<?= site_url('presenter/abstrak') ?>">
                            <i class="fas fa-file-alt me-2"></i> Abstrak
                        </a>
                        <a class="nav-link" href="<?= site_url('presenter/pembayaran') ?>">
                            <i class="fas fa-credit-card me-2"></i> Pembayaran
                        </a>
                        <a class="nav-link" href="<?= site_url('presenter/absensi') ?>">
                            <i class="fas fa-qrcode me-2"></i> Absensi
                        </a>
                        <div class="nav-item">
                            <a class="nav-link active" href="<?= site_url('presenter/dokumen/loa') ?>">
                                <i class="fas fa-file-import me-2"></i> LOA
                            </a>
                        </div>
                        <a class="nav-link" href="<?= site_url('presenter/dokumen/sertifikat') ?>">
                            <i class="fas fa-certificate me-2"></i> Sertifikat
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
                    <div class="header-section">
                        <div class="row align-items-center">
                            <div class="col">
                                <h1 class="welcome-text">
                                    <i class="fas fa-file-import me-3"></i>Letter of Acceptance (LOA)
                                </h1>
                                <p class="text-muted mb-0">
                                    Download LOA untuk event yang Anda ikuti sebagai presenter
                                </p>
                            </div>
                            <div class="col-auto">
                                <div class="text-end">
                                    <small class="text-muted d-block">Total LOA</small>
                                    <strong class="h4"><?= count($loa_documents) ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- LOA Documents -->
                        <div class="col-lg-8">
                            <div class="card-custom">
                                <div class="card-header-custom">
                                    <h5 class="mb-0">
                                        <i class="fas fa-folder-open me-2"></i>LOA Documents
                                    </h5>
                                </div>
                                <div class="card-body p-4">
                                    <?php if (empty($loa_documents)): ?>
                                        <div class="empty-state">
                                            <i class="fas fa-file-import"></i>
                                            <h5>Belum Ada LOA</h5>
                                            <p>LOA akan tersedia setelah abstrak Anda diterima dan pembayaran terverifikasi</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($loa_documents as $loa): ?>
                                            <div class="document-item position-relative">
                                                <div class="d-flex align-items-center">
                                                    <div class="me-3">
                                                        <i class="fas fa-file-pdf file-icon text-danger"></i>
                                                    </div>
                                                    <div class="flex-grow-1">
                                                        <h6 class="mb-1">
                                                            <?= $loa['event_title'] ? esc($loa['event_title']) : 'Event LOA' ?>
                                                        </h6>
                                                        <p class="text-muted mb-2">
                                                            <i class="fas fa-calendar me-1"></i>
                                                            <?= $loa['event_date'] ? date('d F Y', strtotime($loa['event_date'])) : '-' ?>
                                                            <?= $loa['event_time'] ? ' • ' . date('H:i', strtotime($loa['event_time'])) : '' ?>
                                                        </p>
                                                        <small class="text-muted">
                                                            <i class="fas fa-clock me-1"></i>
                                                            Uploaded: <?= date('d/m/Y H:i', strtotime($loa['uploaded_at'])) ?>
                                                        </small>
                                                    </div>
                                                    <div class="ms-3">
                                                        <a href="<?= site_url('presenter/dokumen/loa/download/' . basename($loa['file_path'])) ?>" 
                                                           class="btn btn-success btn-custom btn-sm">
                                                            <i class="fas fa-download me-1"></i>Download
                                                        </a>
                                                    </div>
                                                </div>
                                                <span class="badge bg-success status-badge">
                                                    <i class="fas fa-check me-1"></i>Available
                                                </span>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Pending Events -->
                        <div class="col-lg-4">
                            <div class="card-custom">
                                <div class="card-header-custom">
                                    <h5 class="mb-0">
                                        <i class="fas fa-hourglass-half me-2"></i>Menunggu LOA
                                    </h5>
                                </div>
                                <div class="card-body p-4">
                                    <?php if (empty($available_events)): ?>
                                        <div class="empty-state">
                                            <i class="fas fa-check-circle"></i>
                                            <h6>Semua LOA Tersedia</h6>
                                            <p class="small">Tidak ada event yang menunggu LOA</p>
                                        </div>
                                    <?php else: ?>
                                        <?php foreach ($available_events as $event): ?>
                                            <div class="event-item">
                                                <h6 class="mb-2"><?= esc($event['title']) ?></h6>
                                                <p class="text-muted mb-2">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    <?= date('d F Y', strtotime($event['event_date'])) ?>
                                                    <?= $event['event_time'] ? ' • ' . date('H:i', strtotime($event['event_time'])) : '' ?>
                                                </p>
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <span class="badge bg-warning">
                                                        <i class="fas fa-clock me-1"></i>Pending
                                                    </span>
                                                    <small class="text-muted">
                                                        LOA sedang diproses
                                                    </small>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- Information Card -->
                            <div class="card-custom mt-4">
                                <div class="card-header bg-info text-white">
                                    <h6 class="mb-0">
                                        <i class="fas fa-info-circle me-2"></i>Informasi LOA
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <ul class="list-unstyled mb-0">
                                        <li class="mb-2">
                                            <i class="fas fa-check text-success me-2"></i>
                                            LOA tersedia setelah pembayaran terverifikasi
                                        </li>
                                        <li class="mb-2">
                                            <i class="fas fa-check text-success me-2"></i>
                                            Download kapan saja setelah tersedia
                                        </li>
                                        <li class="mb-2">
                                            <i class="fas fa-check text-success me-2"></i>
                                            LOA diperlukan untuk presentasi
                                        </li>
                                        <li class="mb-0">
                                            <i class="fas fa-check text-success me-2"></i>
                                            Format PDF siap print
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.7.12/sweetalert2.min.js"></script>

    <script>
        $(document).ready(function() {
            // Show alerts for flash messages
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

            // Add hover effects
            $('.document-item, .event-item').hover(
                function() {
                    $(this).addClass('shadow-sm');
                },
                function() {
                    $(this).removeClass('shadow-sm');
                }
            );
        });
    </script>
</body>
</html>