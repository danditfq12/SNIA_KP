<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="<?= csrf_hash() ?>">
    <title>Kelola Event - SNIA Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.7.12/sweetalert2.min.css" rel="stylesheet">
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
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border-left: 4px solid var(--primary-color);
            transition: transform 0.2s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            color: #1e293b;
        }

        .header-section {
            background: white;
            padding: 24px;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            margin-bottom: 24px;
        }

        .welcome-text {
            color: var(--primary-color);
            font-size: 1.8rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .event-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
            transition: all 0.2s ease;
            margin-bottom: 20px;
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
            border-radius: 8px;
            font-size: 0.75rem;
            font-weight: 600;
        }

        .modal-content {
            border-radius: 12px;
            border: none;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .modal-header {
            background: var(--primary-color);
            color: white;
            border-radius: 12px 12px 0 0;
        }

        .form-control, .form-select {
            border-radius: 6px;
            border: 1px solid #d1d5db;
            padding: 10px 12px;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.25);
        }

        .btn-custom {
            border-radius: 6px;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .btn-custom:hover {
            transform: translateY(-1px);
        }

        .price-display {
            background: #f8fafc;
            border-radius: 8px;
            padding: 12px;
            margin: 12px 0;
        }

        .revenue-display {
            background: var(--success-color);
            color: white;
            padding: 12px;
            border-radius: 8px;
            text-align: center;
        }

        .conditional-field {
            display: none;
        }

        .conditional-field.show {
            display: block;
        }

        .disabled-field {
            opacity: 0.5;
            pointer-events: none;
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
                        <a class="nav-link" href="<?= base_url('admin/dashboard') ?>">
                            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                        </a>
                        <a class="nav-link" href="<?= base_url('admin/users') ?>">
                            <i class="fas fa-users me-2"></i> Manajemen User
                        </a>
                        <a class="nav-link" href="<?= base_url('admin/abstrak') ?>">
                            <i class="fas fa-file-alt me-2"></i> Manajemen Abstrak
                        </a>
                        <a class="nav-link" href="<?= base_url('admin/reviewer') ?>">
                            <i class="fas fa-user-check me-2"></i> Kelola Reviewer
                        </a>
                        <a class="nav-link active" href="<?= base_url('admin/event') ?>">
                            <i class="fas fa-calendar-alt me-2"></i> Kelola Event
                        </a>
                        <a class="nav-link" href="<?= base_url('admin/pembayaran') ?>">
                            <i class="fas fa-credit-card me-2"></i> Verifikasi Pembayaran
                        </a>
                        <a class="nav-link" href="<?= base_url('admin/absensi') ?>">
                            <i class="fas fa-qrcode me-2"></i> Kelola Absensi
                        </a>
                        <a class="nav-link" href="<?= base_url('admin/voucher') ?>">
                            <i class="fas fa-ticket-alt me-2"></i> Kelola Voucher
                        </a>
                        <a class="nav-link" href="<?= base_url('admin/dokumen') ?>">
                            <i class="fas fa-folder-open me-2"></i> Dokumen
                        </a>
                        <a class="nav-link" href="<?= base_url('admin/laporan') ?>">
                            <i class="fas fa-chart-line me-2"></i> Laporan
                        </a>
                        <hr class="my-3" style="border-color: rgba(255,255,255,0.2);">
                        <a class="nav-link text-warning" href="<?= base_url('auth/logout') ?>">
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
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <h1 class="welcome-text">
                                    <i class="fas fa-calendar-alt me-3"></i>
                                    Kelola Event
                                </h1>
                                <p class="text-muted mb-0">Manajemen event dan aktivitas SNIA Conference</p>
                            </div>
                            <button class="btn btn-primary btn-custom" data-bs-toggle="modal" data-bs-target="#addEventModal">
                                <i class="fas fa-plus me-2"></i>
                                Tambah Event
                            </button>
                        </div>
                    </div>

                    <!-- Statistics -->
                    <div class="row mb-4">
                        <div class="col-md-3 mb-3">
                            <div class="stat-card">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h6 class="text-muted mb-1">Total Event</h6>
                                        <div class="stat-number"><?= $stats['total_events'] ?? 0 ?></div>
                                    </div>
                                    <i class="fas fa-calendar-alt fa-2x text-primary"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stat-card">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h6 class="text-muted mb-1">Event Aktif</h6>
                                        <div class="stat-number text-success"><?= $stats['active_events'] ?? 0 ?></div>
                                    </div>
                                    <i class="fas fa-check-circle fa-2x text-success"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stat-card">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h6 class="text-muted mb-1">Total Pendaftar</h6>
                                        <div class="stat-number text-info"><?= $stats['verified_registrations'] ?? 0 ?></div>
                                    </div>
                                    <i class="fas fa-users fa-2x text-info"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 mb-3">
                            <div class="stat-card">
                                <div class="d-flex align-items-center">
                                    <div class="flex-grow-1">
                                        <h6 class="text-muted mb-1">Revenue</h6>
                                        <div class="stat-number text-warning" style="font-size: 1.5rem;">
                                            Rp <?= number_format($stats['total_revenue'] ?? 0, 0, ',', '.') ?>
                                        </div>
                                    </div>
                                    <i class="fas fa-chart-line fa-2x text-warning"></i>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Events List -->
                    <div class="row">
                        <?php if (!empty($events)): ?>
                            <?php foreach ($events as $event): ?>
                                <div class="col-lg-6 mb-4">
                                    <div class="event-card">
                                        <div class="event-header">
                                            <div class="d-flex justify-content-between align-items-start mb-2">
                                                <h5 class="mb-0 text-primary"><?= esc($event['title']) ?></h5>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                                        <i class="fas fa-ellipsis-v"></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li><a class="dropdown-item" href="#" onclick="editEvent(<?= $event['id'] ?>)">
                                                            <i class="fas fa-edit me-2"></i>Edit
                                                        </a></li>
                                                        <li><a class="dropdown-item" href="#" onclick="toggleStatus(<?= $event['id'] ?>)">
                                                            <i class="fas fa-power-off me-2"></i>
                                                            <?= $event['is_active'] ? 'Nonaktifkan' : 'Aktifkan' ?>
                                                        </a></li>
                                                        <li><a class="dropdown-item" href="#" onclick="toggleRegistration(<?= $event['id'] ?>)">
                                                            <i class="fas fa-user-plus me-2"></i>
                                                            Reg: <?= $event['registration_active'] ? 'Tutup' : 'Buka' ?>
                                                        </a></li>
                                                        <li><hr class="dropdown-divider"></li>
                                                        <li><a class="dropdown-item text-danger" href="#" onclick="deleteEvent(<?= $event['id'] ?>)">
                                                            <i class="fas fa-trash me-2"></i>Hapus
                                                        </a></li>
                                                    </ul>
                                                </div>
                                            </div>

                                            <div class="d-flex gap-2 mb-2">
                                                <span class="format-badge <?= $event['format'] == 'online' ? 'bg-info text-white' : ($event['format'] == 'offline' ? 'bg-primary text-white' : 'bg-success text-white') ?>">
                                                    <?= ucfirst($event['format']) ?>
                                                </span>
                                                <span class="badge <?= $event['is_active'] ? 'bg-success' : 'bg-secondary' ?>">
                                                    <?= $event['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                                                </span>
                                            </div>

                                            <div class="text-muted small">
                                                <i class="fas fa-calendar me-1"></i>
                                                <?= date('d M Y', strtotime($event['event_date'])) ?> - 
                                                <i class="fas fa-clock ms-2 me-1"></i>
                                                <?= date('H:i', strtotime($event['event_time'])) ?>
                                            </div>
                                        </div>

                                        <div class="event-body">
                                            <!-- Pricing -->
                                            <div class="price-display">
                                                <h6 class="mb-2"><i class="fas fa-tag me-2"></i>Harga</h6>
                                                <div class="row g-2 small">
                                                    <div class="col-6">
                                                        <strong>Presenter:</strong><br>
                                                        Rp <?= number_format($event['presenter_fee_offline'], 0, ',', '.') ?>
                                                    </div>
                                                    <div class="col-6">
                                                        <strong>Audience:</strong><br>
                                                        <?php if ($event['format'] != 'offline'): ?>
                                                            Online: Rp <?= number_format($event['audience_fee_online'], 0, ',', '.') ?><br>
                                                        <?php endif; ?>
                                                        <?php if ($event['format'] != 'online'): ?>
                                                            Offline: Rp <?= number_format($event['audience_fee_offline'], 0, ',', '.') ?>
                                                        <?php endif; ?>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Statistics -->
                                            <div class="row g-2 mb-3">
                                                <div class="col-3 text-center">
                                                    <div class="fw-bold text-primary"><?= $event['total_registrations'] ?? 0 ?></div>
                                                    <small class="text-muted">Total</small>
                                                </div>
                                                <div class="col-3 text-center">
                                                    <div class="fw-bold text-success"><?= $event['verified_registrations'] ?? 0 ?></div>
                                                    <small class="text-muted">Verified</small>
                                                </div>
                                                <div class="col-3 text-center">
                                                    <div class="fw-bold text-info"><?= $event['total_abstracts'] ?? 0 ?></div>
                                                    <small class="text-muted">Abstrak</small>
                                                </div>
                                                <div class="col-3 text-center">
                                                    <div class="fw-bold text-warning"><?= $event['present_count'] ?? 0 ?></div>
                                                    <small class="text-muted">Hadir</small>
                                                </div>
                                            </div>

                                            <!-- Revenue -->
                                            <div class="revenue-display">
                                                <i class="fas fa-money-bill-wave me-2"></i>
                                                <strong>Revenue: Rp <?= number_format($event['total_revenue'] ?? 0, 0, ',', '.') ?></strong>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <div class="text-center py-5">
                                    <i class="fas fa-calendar-times fa-4x text-muted mb-3"></i>
                                    <h5 class="text-muted">Belum ada event</h5>
                                    <p class="text-muted">Klik "Tambah Event" untuk membuat event baru</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Event Modal -->
    <div class="modal fade" id="addEventModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-plus me-2"></i>
                        Tambah Event Baru
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="addEventForm">
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-8 mb-3">
                                <label class="form-label">Judul Event *</label>
                                <input type="text" class="form-control" name="title" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Format *</label>
                                <select class="form-select" name="format" id="eventFormat" required>
                                    <option value="both">Hybrid</option>
                                    <option value="online">Online</option>
                                    <option value="offline">Offline</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Deskripsi</label>
                            <textarea class="form-control" name="description" rows="3"></textarea>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Tanggal Event *</label>
                                <input type="date" class="form-control" name="event_date" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Waktu Event *</label>
                                <input type="time" class="form-control" name="event_time" required>
                            </div>
                        </div>

                        <!-- Location/Zoom fields -->
                        <div class="conditional-field" id="locationRow">
                            <div class="mb-3">
                                <label class="form-label">Lokasi <span class="text-danger" id="locationRequired">*</span></label>
                                <input type="text" class="form-control" name="location" id="locationInput">
                            </div>
                        </div>

                        <div class="conditional-field" id="zoomRow">
                            <div class="mb-3">
                                <label class="form-label">Link Zoom <span class="text-danger" id="zoomRequired">*</span></label>
                                <input type="url" class="form-control" name="zoom_link" id="zoomInput">
                            </div>
                        </div>

                        <!-- Pricing -->
                        <h6 class="border-bottom pb-2 mb-3">Struktur Harga</h6>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Presenter (Offline) *</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" name="presenter_fee_offline" value="0" required>
                                </div>
                                <small class="text-muted">Presenter hanya bisa offline</small>
                            </div>
                            <div class="col-md-4 mb-3" id="audienceOnlinePrice">
                                <label class="form-label">Audience (Online) *</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" name="audience_fee_online" value="0" required>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3" id="audienceOfflinePrice">
                                <label class="form-label">Audience (Offline) *</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" class="form-control" name="audience_fee_offline" value="0" required>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Settings -->
                        <h6 class="border-bottom pb-2 mb-3">Pengaturan Tambahan</h6>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Maksimal Peserta</label>
                                <input type="number" class="form-control" name="max_participants">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Batas Pendaftaran</label>
                                <input type="datetime-local" class="form-control" name="registration_deadline">
                            </div>
                        </div>

                        <!-- Status toggles -->
                        <div class="row">
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="is_active" checked>
                                    <label class="form-check-label">Event Aktif</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="registration_active" checked>
                                    <label class="form-check-label">Pendaftaran Aktif</label>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="abstract_submission_active" checked>
                                    <label class="form-check-label">Submit Abstrak Aktif</label>
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

    <!-- Edit Event Modal -->
    <div class="modal fade" id="editEventModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-edit me-2"></i>
                        Edit Event
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="editEventForm">
                    <div class="modal-body" id="editFormContent">
                        <!-- Content loaded dynamically -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save me-2"></i>Update Event
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.7.12/sweetalert2.min.js"></script>
    <script>
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

        document.addEventListener('DOMContentLoaded', function() {
            setupFormHandlers();
            handleFormatChange(); // Initial call
        });

        function setupFormHandlers() {
            // Add event form
            document.getElementById('addEventForm').addEventListener('submit', function(e) {
                e.preventDefault();
                submitForm(this, '<?= base_url("admin/event/store") ?>');
            });

            // Edit event form
            document.getElementById('editEventForm').addEventListener('submit', function(e) {
                e.preventDefault();
                const eventId = this.dataset.eventId;
                submitForm(this, `<?= base_url("admin/event/update") ?>/${eventId}`);
            });

            // Format change handler
            document.getElementById('eventFormat').addEventListener('change', handleFormatChange);
        }

        function handleFormatChange() {
            const format = document.getElementById('eventFormat').value;
            const locationRow = document.getElementById('locationRow');
            const zoomRow = document.getElementById('zoomRow');
            const locationInput = document.getElementById('locationInput');
            const zoomInput = document.getElementById('zoomInput');
            const locationRequired = document.getElementById('locationRequired');
            const zoomRequired = document.getElementById('zoomRequired');
            const audienceOnlinePrice = document.getElementById('audienceOnlinePrice');
            const audienceOfflinePrice = document.getElementById('audienceOfflinePrice');

            // Reset all fields
            locationRow.classList.remove('show');
            zoomRow.classList.remove('show');
            audienceOnlinePrice.classList.remove('disabled-field');
            audienceOfflinePrice.classList.remove('disabled-field');
            
            locationInput.removeAttribute('required');
            zoomInput.removeAttribute('required');
            locationRequired.style.display = 'none';
            zoomRequired.style.display = 'none';

            // Show/hide fields based on format
            switch(format) {
                case 'offline':
                    locationRow.classList.add('show');
                    locationInput.setAttribute('required', 'required');
                    locationRequired.style.display = 'inline';
                    
                    // Hide online price for audience
                    audienceOnlinePrice.classList.add('disabled-field');
                    audienceOnlinePrice.querySelector('input').value = 0;
                    break;
                    
                case 'online':
                    zoomRow.classList.add('show');
                    zoomInput.setAttribute('required', 'required');
                    zoomRequired.style.display = 'inline';
                    
                    // Hide offline price for audience
                    audienceOfflinePrice.classList.add('disabled-field');
                    audienceOfflinePrice.querySelector('input').value = 0;
                    break;
                    
                case 'both':
                    locationRow.classList.add('show');
                    zoomRow.classList.add('show');
                    locationInput.setAttribute('required', 'required');
                    zoomInput.setAttribute('required', 'required');
                    locationRequired.style.display = 'inline';
                    zoomRequired.style.display = 'inline';
                    break;
            }
        }

        function submitForm(form, url) {
            const formData = new FormData(form);
            const submitBtn = form.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            
            // Set disabled price fields to 0
            const format = formData.get('format');
            if (format === 'online') {
                formData.set('audience_fee_offline', '0');
            } else if (format === 'offline') {
                formData.set('audience_fee_online', '0');
            }
            
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Menyimpan...';
            submitBtn.disabled = true;

            fetch(url, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': csrfToken
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: data.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    throw new Error(data.message || 'Terjadi kesalahan');
                }
            })
            .catch(error => {
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: error.message
                });
            })
            .finally(() => {
                submitBtn.innerHTML = originalText;
                submitBtn.disabled = false;
            });
        }

        function editEvent(eventId) {
            fetch(`<?= base_url("admin/event/edit") ?>/${eventId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        populateEditForm(data.event);
                        document.getElementById('editEventForm').dataset.eventId = eventId;
                        new bootstrap.Modal(document.getElementById('editEventModal')).show();
                    }
                })
                .catch(error => {
                    Swal.fire('Error!', 'Gagal memuat data event', 'error');
                });
        }

        function populateEditForm(event) {
            const audienceOnlineDiv = event.format !== 'offline' ? `
                <div class="col-md-4 mb-3">
                    <label class="form-label">Audience (Online) *</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" class="form-control" name="audience_fee_online" value="${event.audience_fee_online}" required>
                    </div>
                </div>
            ` : '';

            const audienceOfflineDiv = event.format !== 'online' ? `
                <div class="col-md-4 mb-3">
                    <label class="form-label">Audience (Offline) *</label>
                    <div class="input-group">
                        <span class="input-group-text">Rp</span>
                        <input type="number" class="form-control" name="audience_fee_offline" value="${event.audience_fee_offline}" required>
                    </div>
                </div>
            ` : '';

            const locationDiv = (event.format === 'offline' || event.format === 'both') ? `
                <div class="mb-3">
                    <label class="form-label">Lokasi ${event.format === 'offline' ? '<span class="text-danger">*</span>' : ''}</label>
                    <input type="text" class="form-control" name="location" value="${event.location || ''}" ${event.format === 'offline' ? 'required' : ''}>
                </div>
            ` : '';

            const zoomDiv = (event.format === 'online' || event.format === 'both') ? `
                <div class="mb-3">
                    <label class="form-label">Link Zoom ${event.format === 'online' ? '<span class="text-danger">*</span>' : ''}</label>
                    <input type="url" class="form-control" name="zoom_link" value="${event.zoom_link || ''}" ${event.format === 'online' ? 'required' : ''}>
                </div>
            ` : '';

            const formContent = `
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label">Judul Event *</label>
                        <input type="text" class="form-control" name="title" value="${event.title}" required>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Format *</label>
                        <select class="form-select" name="format" required>
                            <option value="both" ${event.format === 'both' ? 'selected' : ''}>Hybrid</option>
                            <option value="online" ${event.format === 'online' ? 'selected' : ''}>Online</option>
                            <option value="offline" ${event.format === 'offline' ? 'selected' : ''}>Offline</option>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Deskripsi</label>
                    <textarea class="form-control" name="description" rows="3">${event.description || ''}</textarea>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tanggal Event *</label>
                        <input type="date" class="form-control" name="event_date" value="${event.event_date}" required>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Waktu Event *</label>
                        <input type="time" class="form-control" name="event_time" value="${event.event_time}" required>
                    </div>
                </div>
                ${locationDiv}
                ${zoomDiv}
                <h6 class="border-bottom pb-2 mb-3">Struktur Harga</h6>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Presenter (Offline) *</label>
                        <div class="input-group">
                            <span class="input-group-text">Rp</span>
                            <input type="number" class="form-control" name="presenter_fee_offline" value="${event.presenter_fee_offline}" required>
                        </div>
                        <small class="text-muted">Presenter hanya bisa offline</small>
                    </div>
                    ${audienceOnlineDiv}
                    ${audienceOfflineDiv}
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Maksimal Peserta</label>
                        <input type="number" class="form-control" name="max_participants" value="${event.max_participants || ''}">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Batas Pendaftaran</label>
                        <input type="datetime-local" class="form-control" name="registration_deadline" value="${event.registration_deadline ? event.registration_deadline.slice(0, 16) : ''}">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="is_active" ${event.is_active ? 'checked' : ''}>
                            <label class="form-check-label">Event Aktif</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="registration_active" ${event.registration_active ? 'checked' : ''}>
                            <label class="form-check-label">Pendaftaran Aktif</label>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox" name="abstract_submission_active" ${event.abstract_submission_active ? 'checked' : ''}>
                            <label class="form-check-label">Submit Abstrak Aktif</label>
                        </div>
                    </div>
                </div>
                <!-- Hidden inputs for disabled price fields -->
                ${event.format === 'online' ? '<input type="hidden" name="audience_fee_offline" value="0">' : ''}
                ${event.format === 'offline' ? '<input type="hidden" name="audience_fee_online" value="0">' : ''}
            `;
            
            document.getElementById('editFormContent').innerHTML = formContent;
        }

        function toggleStatus(eventId) {
            makeRequest(`<?= base_url("admin/event/toggle-status") ?>/${eventId}`, 'toggle status event');
        }

        function toggleRegistration(eventId) {
            makeRequest(`<?= base_url("admin/event/toggle-registration") ?>/${eventId}`, 'toggle pendaftaran');
        }

        function deleteEvent(eventId) {
            Swal.fire({
                title: 'Hapus Event?',
                text: 'Event yang dihapus tidak dapat dikembalikan!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    makeRequest(`<?= base_url("admin/event/delete") ?>/${eventId}`, 'hapus event');
                }
            });
        }

        function makeRequest(url, action) {
            fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': csrfToken,
                    'Content-Type': 'application/json'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    Swal.fire({
                        icon: 'success',
                        title: 'Berhasil!',
                        text: data.message,
                        timer: 1500,
                        showConfirmButton: false
                    }).then(() => {
                        location.reload();
                    });
                } else {
                    throw new Error(data.message);
                }
            })
            .catch(error => {
                Swal.fire('Error!', error.message, 'error');
            });
        }
    </script>
</body>
</html>