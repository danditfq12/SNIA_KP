<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Event - SNIA Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.7.12/sweetalert2.min.css" rel="stylesheet">
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

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            border: 1px solid rgba(255,255,255,0.2);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(90deg, var(--primary-color), var(--info-color));
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.15);
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            color: #1e293b;
            margin: 16px 0 8px 0;
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

        .table-container {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            overflow: hidden;
        }

        .table-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--info-color) 100%);
            color: white;
            padding: 20px;
            font-weight: 600;
        }

        .badge-custom {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
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

        .event-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
            transition: all 0.3s ease;
            overflow: hidden;
        }

        .event-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }

        .event-header {
            padding: 20px;
            border-bottom: 1px solid #f1f5f9;
        }

        .event-body {
            padding: 20px;
        }

        .format-badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 600;
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
                        <a class="nav-link active" href="<?= site_url('admin/event') ?>">
                            <i class="fas fa-calendar me-2"></i> Kelola Event
                        </a>
                        <a class="nav-link" href="<?= site_url('admin/abstrak') ?>">
                            <i class="fas fa-file-alt me-2"></i> Manajemen Abstrak
                        </a>
                        <a class="nav-link" href="<?= site_url('admin/reviewer') ?>">
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
                    <div class="header-section">
                        <div class="row align-items-center">
                            <div class="col">
                                <h1 class="welcome-text">
                                    <i class="fas fa-calendar me-3"></i>Kelola Event
                                </h1>
                                <p class="text-muted mb-0">
                                    Kelola semua event konferensi dan workshop SNIA
                                </p>
                            </div>
                            <div class="col-auto">
                                <div class="text-end">
                                    <small class="text-muted d-block">Terakhir update</small>
                                    <strong><?= date('d F Y, H:i') ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-6 col-xl-3">
                            <div class="stat-card">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="fas fa-calendar-alt fa-2x text-primary"></i>
                                    </div>
                                    <div class="ms-3">
                                        <div class="stat-number"><?= $total_events ?? 0 ?></div>
                                        <div class="text-muted">Total Event</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-xl-3">
                            <div class="stat-card">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="fas fa-play-circle fa-2x text-success"></i>
                                    </div>
                                    <div class="ms-3">
                                        <div class="stat-number"><?= $active_events ?? 0 ?></div>
                                        <div class="text-muted">Event Aktif</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-xl-3">
                            <div class="stat-card">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="fas fa-clock fa-2x text-warning"></i>
                                    </div>
                                    <div class="ms-3">
                                        <div class="stat-number"><?= $upcoming_events ?? 0 ?></div>
                                        <div class="text-muted">Event Mendatang</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-xl-3">
                            <div class="stat-card">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="fas fa-users fa-2x text-info"></i>
                                    </div>
                                    <div class="ms-3">
                                        <div class="stat-number"><?= array_sum(array_column($events ?? [], 'total_registrations')) ?></div>
                                        <div class="text-muted">Total Registrasi</div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Main Content -->
                    <div class="table-container">
                        <div class="table-header">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h5 class="mb-0">
                                        <i class="fas fa-list me-2"></i>Daftar Event
                                    </h5>
                                </div>
                                <div class="col-auto">
                                    <button class="btn btn-light btn-custom" data-bs-toggle="modal" data-bs-target="#createModal">
                                        <i class="fas fa-plus me-1"></i>Tambah Event
                                    </button>
                                    <button class="btn btn-light btn-custom" onclick="exportData()">
                                        <i class="fas fa-download me-1"></i>Export
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="p-4">
                            <!-- Event Cards -->
                            <div class="row g-4">
                                <?php if (!empty($events)): ?>
                                    <?php foreach ($events as $event): ?>
                                        <div class="col-lg-6 col-xl-4">
                                            <div class="event-card">
                                                <div class="event-header">
                                                    <div class="d-flex justify-content-between align-items-start">
                                                        <div class="flex-grow-1">
                                                            <h6 class="mb-1 fw-bold"><?= esc($event['title']) ?></h6>
                                                            <small class="text-muted">
                                                                <i class="fas fa-calendar me-1"></i>
                                                                <?= date('d M Y', strtotime($event['event_date'])) ?> • 
                                                                <?= date('H:i', strtotime($event['event_time'])) ?>
                                                            </small>
                                                        </div>
                                                        <span class="format-badge <?= $event['format'] == 'online' ? 'bg-info text-white' : 'bg-success text-white' ?>">
                                                            <i class="fas fa-<?= $event['format'] == 'online' ? 'video' : 'map-marker-alt' ?> me-1"></i>
                                                            <?= ucfirst($event['format']) ?>
                                                        </span>
                                                    </div>
                                                    <div class="mt-2">
                                                        <span class="badge <?= $event['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                                                            <?= $event['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                                                        </span>
                                                        <?php if ($event['registration_active']): ?>
                                                            <span class="badge bg-primary">Pendaftaran Buka</span>
                                                        <?php endif; ?>
                                                        <?php if ($event['abstract_submission_active']): ?>
                                                            <span class="badge bg-warning">Abstrak Buka</span>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                                
                                                <div class="event-body">
                                                    <p class="small text-muted mb-3"><?= esc(substr($event['description'] ?? '', 0, 100)) ?>...</p>
                                                    
                                                    <div class="row text-center mb-3">
                                                        <div class="col-4">
                                                            <div class="fw-bold text-primary"><?= $event['total_registrations'] ?? 0 ?></div>
                                                            <small class="text-muted">Registrasi</small>
                                                        </div>
                                                        <div class="col-4">
                                                            <div class="fw-bold text-success"><?= $event['verified_registrations'] ?? 0 ?></div>
                                                            <small class="text-muted">Verified</small>
                                                        </div>
                                                        <div class="col-4">
                                                            <div class="fw-bold text-info"><?= $event['total_abstracts'] ?? 0 ?></div>
                                                            <small class="text-muted">Abstrak</small>
                                                        </div>
                                                    </div>
                                                    
                                                    <div class="mb-3">
                                                        <small class="text-muted d-block">Biaya Registrasi:</small>
                                                        <strong class="text-success">Rp <?= number_format($event['registration_fee'], 0, ',', '.') ?></strong>
                                                    </div>
                                                    
                                                    <div class="d-flex gap-1">
                                                        <button class="btn btn-outline-info btn-sm btn-custom flex-fill" 
                                                                onclick="viewDetail(<?= $event['id'] ?>)">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button class="btn btn-outline-warning btn-sm btn-custom flex-fill" 
                                                                onclick="editEvent(<?= $event['id'] ?>)">
                                                            <i class="fas fa-edit"></i>
                                                        </button>
                                                        <button class="btn btn-outline-<?= $event['is_active'] ? 'secondary' : 'success' ?> btn-sm btn-custom flex-fill" 
                                                                onclick="toggleStatus(<?= $event['id'] ?>, <?= $event['is_active'] ? 'false' : 'true' ?>)">
                                                            <i class="fas fa-<?= $event['is_active'] ? 'pause' : 'play' ?>"></i>
                                                        </button>
                                                        <button class="btn btn-outline-danger btn-sm btn-custom flex-fill" 
                                                                onclick="deleteEvent(<?= $event['id'] ?>)">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="col-12">
                                        <div class="text-center py-5">
                                            <i class="fas fa-calendar-alt fa-3x text-muted mb-3"></i>
                                            <h5 class="text-muted">Belum Ada Event</h5>
                                            <p class="text-muted">Mulai dengan membuat event pertama untuk konferensi SNIA.</p>
                                            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createModal">
                                                <i class="fas fa-plus me-2"></i>Tambah Event
                                            </button>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Create Event Modal -->
    <div class="modal fade" id="createModal" tabindex="-1">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>Tambah Event Baru
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form action="<?= site_url('admin/event/store') ?>" method="POST">
                    <?= csrf_field() ?>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label class="form-label">Judul Event *</label>
                                    <input type="text" class="form-control" name="title" required maxlength="255">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Format Event *</label>
                                    <select class="form-select" name="format" id="eventFormat" required>
                                        <option value="">-- Pilih Format --</option>
                                        <option value="online">Online</option>
                                        <option value="offline">Offline</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Deskripsi Event</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Tanggal Event *</label>
                                    <input type="date" class="form-control" name="event_date" required min="<?= date('Y-m-d', strtotime('+1 day')) ?>">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Waktu Event *</label>
                                    <input type="time" class="form-control" name="event_time" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6" id="locationField" style="display: none;">
                                <div class="mb-3">
                                    <label class="form-label">Lokasi Event</label>
                                    <input type="text" class="form-control" name="location" maxlength="255">
                                </div>
                            </div>
                            <div class="col-md-6" id="zoomField" style="display: none;">
                                <div class="mb-3">
                                    <label class="form-label">Link Zoom</label>
                                    <input type="url" class="form-control" name="zoom_link" maxlength="500">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label class="form-label">Biaya Registrasi *</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" class="form-control" name="registration_fee" required min="0" value="0">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Maksimal Peserta</label>
                                    <input type="number" class="form-control" name="max_participants" min="1">
                                    <div class="form-text">Kosongkan untuk unlimited</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Batas Pendaftaran</label>
                                    <input type="datetime-local" class="form-control" name="registration_deadline">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label class="form-label">Batas Submit Abstrak</label>
                                    <input type="datetime-local" class="form-control" name="abstract_deadline">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="registration_active" value="1" checked>
                                    <label class="form-check-label">
                                        Aktifkan Pendaftaran
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="abstract_submission_active" value="1" checked>
                                    <label class="form-check-label">
                                        Aktifkan Submit Abstrak
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Simpan Event
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
        $(document).ready(function() {
            // Handle format change
            $('#eventFormat').on('change', function() {
                const format = $(this).val();
                if (format === 'online') {
                    $('#locationField').hide();
                    $('#zoomField').show();
                } else if (format === 'offline') {
                    $('#locationField').show();
                    $('#zoomField').hide();
                } else {
                    $('#locationField').hide();
                    $('#zoomField').hide();
                }
            });

            // Animate numbers on page load
            const numbers = document.querySelectorAll('.stat-number');
            numbers.forEach(number => {
                const finalNumber = parseInt(number.textContent);
                let currentNumber = 0;
                const increment = finalNumber / 50;
                
                const timer = setInterval(() => {
                    currentNumber += increment;
                    if (currentNumber >= finalNumber) {
                        number.textContent = finalNumber;
                        clearInterval(timer);
                    } else {
                        number.textContent = Math.floor(currentNumber);
                    }
                }, 20);
            });
        });

        function viewDetail(eventId) {
            window.open('<?= site_url('admin/event/detail') ?>/' + eventId, '_blank');
        }

        function editEvent(eventId) {
            // Implementation for edit event
            Swal.fire('Info', 'Fitur edit event akan segera tersedia', 'info');
        }

        function toggleStatus(eventId, newStatus) {
            const action = newStatus === 'true' ? 'aktifkan' : 'nonaktifkan';
            
            Swal.fire({
                title: `${action.charAt(0).toUpperCase() + action.slice(1)} Event?`,
                text: `Apakah Anda yakin ingin ${action} event ini?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: newStatus === 'true' ? '#10b981' : '#f59e0b',
                cancelButtonColor: '#6b7280',
                confirmButtonText: `Ya, ${action.charAt(0).toUpperCase() + action.slice(1)}!`,
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '<?= site_url('admin/event/toggle-status') ?>/' + eventId;
                }
            });
        }

        function deleteEvent(eventId) {
            Swal.fire({
                title: 'Hapus Event?',
                text: 'Data event dan semua registrasi terkait akan terhapus!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '<?= site_url('admin/event/delete') ?>/' + eventId;
                }
            });
        }

        function exportData() {
            window.open('<?= site_url('admin/event/export') ?>', '_blank');
        }

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

        <?php if (session('errors')): ?>
            let errorList = '';
            <?php foreach (session('errors') as $error): ?>
                errorList += '<?= $error ?>\n';
            <?php endforeach; ?>
            Swal.fire({
                icon: 'error',
                title: 'Validation Error!',
                text: errorList,
            });
        <?php endif; ?>
    </script>
</body>
</html>