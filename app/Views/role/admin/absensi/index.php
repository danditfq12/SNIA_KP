<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enhanced QR Attendance Management - SNIA Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcode/1.5.3/qrcode.min.js"></script>
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

        .header-section {
            background: white;
            padding: 24px;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }

        .qr-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }

        .qr-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            border: none;
            transition: all 0.3s ease;
            border-left: 4px solid var(--primary-color);
        }

        .qr-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.15);
        }

        .qr-card.priority-1 { border-left-color: #6366f1; }
        .qr-card.priority-2 { border-left-color: #8b5cf6; }
        .qr-card.priority-3 { border-left-color: #06b6d4; }
        .qr-card.priority-4 { border-left-color: #10b981; }

        .qr-code-container {
            text-align: center;
            padding: 15px;
            background: #f8fafc;
            border-radius: 8px;
            margin: 15px 0;
        }

        .qr-actions {
            display: flex;
            gap: 8px;
            flex-wrap: wrap;
            margin-top: 15px;
        }

        .btn-qr {
            flex: 1;
            min-width: 80px;
            border-radius: 8px;
            font-size: 0.75rem;
            padding: 6px 12px;
        }

        .qr-label {
            font-weight: 600;
            font-size: 1rem;
            margin-bottom: 5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .qr-description {
            font-size: 0.8rem;
            color: #6b7280;
            margin-bottom: 10px;
        }

        .qr-token {
            font-family: 'Courier New', monospace;
            font-size: 0.7rem;
            background: #f3f4f6;
            padding: 4px 8px;
            border-radius: 4px;
            word-break: break-all;
            margin-top: 8px;
        }

        .modal-qr-display {
            max-width: 90vw;
            max-height: 90vh;
        }

        .loading-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(255, 255, 255, 0.8);
            display: none;
            align-items: center;
            justify-content: center;
            border-radius: 16px;
        }

        .loading-overlay.show {
            display: flex;
        }

        .scanner-link {
            background: linear-gradient(45deg, #6366f1, #8b5cf6);
            color: white;
            padding: 15px 25px;
            border-radius: 12px;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
            transition: all 0.3s ease;
        }

        .scanner-link:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(99, 102, 241, 0.3);
            color: white;
        }

        .qr-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 15px;
            margin-bottom: 20px;
        }

        .stat-card {
            text-align: center;
            padding: 15px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: var(--primary-color);
        }

        .stat-label {
            font-size: 0.8rem;
            color: #6b7280;
            margin-top: 5px;
        }

        /* Event status badges */
        .event-status-badge {
            font-weight: 600;
            padding: 8px 16px;
            border-radius: 20px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
        }

        .event-status-badge.status-upcoming {
            background: #f3f4f6;
            color: #6b7280;
        }

        .event-status-badge.status-starting-soon {
            background: #fef3c7;
            color: #92400e;
        }

        .event-status-badge.status-ongoing {
            background: #d1fae5;
            color: #065f46;
            animation: pulse 2s infinite;
        }

        .event-status-badge.status-finished {
            background: #fee2e2;
            color: #991b1b;
        }

        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.7; }
        }

        @media print {
            .no-print { display: none !important; }
            .print-break { page-break-after: always; }
            body { background: white; }
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
                        <small class="text-white-50">Attendance System</small>
                    </div>
                    
                    <nav class="nav flex-column px-3">
                        <a class="nav-link" href="<?= base_url('admin/dashboard') ?>">
                            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                        </a>
                        <a class="nav-link" href="<?= base_url('admin/users') ?>">
                            <i class="fas fa-users me-2"></i> Manajemen User
                        </a>
                        <a class="nav-link" href="<?= base_url('admin/event') ?>">
                            <i class="fas fa-calendar-alt me-2"></i> Kelola Event
                        </a>
                        <a class="nav-link active" href="<?= base_url('admin/absensi') ?>">
                            <i class="fas fa-qrcode me-2"></i> QR Attendance
                        </a>
                        <a class="nav-link" href="<?= base_url('admin/pembayaran') ?>">
                            <i class="fas fa-credit-card me-2"></i> Verifikasi Pembayaran
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
                    <!-- Flash Messages -->
                    <?php if (session()->getFlashdata('success')): ?>
                        <div class="alert alert-success alert-dismissible fade show" role="alert">
                            <i class="fas fa-check-circle me-2"></i>
                            <?= session()->getFlashdata('success') ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <!-- Page Header -->
                    <div class="header-section">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h2 class="mb-2">
                                    <i class="fas fa-qrcode me-2"></i>Enhanced QR Attendance Management
                                </h2>
                                <p class="mb-0 opacity-90">
                                    Generate multiple QR codes for different roles and participation types with real-time status
                                </p>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="text-end">
                                    <small class="text-muted d-block">Current Time</small>
                                    <strong id="currentTime"><?= date('d F Y, H:i:s') ?></strong>
                                </div>
                                <a href="<?= site_url('qr/scanner') ?>" class="scanner-link mt-2" target="_blank">
                                    <i class="fas fa-camera me-2"></i>Open QR Scanner
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Event Selection -->
                    <div class="row mb-4">
                        <div class="col-md-8">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h5 class="mb-0">
                                        <i class="fas fa-calendar-check me-2"></i>Select Event for QR Generation
                                    </h5>
                                </div>
                                <div class="card-body">
                                    <form method="GET" action="<?= site_url('admin/absensi') ?>" class="mb-3">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <select name="event_id" id="eventSelect" class="form-select" onchange="this.form.submit()">
                                                    <option value="">-- Select Event --</option>
                                                    <?php if (!empty($events)): ?>
                                                        <?php foreach ($events as $event): ?>
                                                            <option value="<?= $event['id'] ?>" <?= ($selectedEventId == $event['id']) ? 'selected' : '' ?>>
                                                                <?= esc($event['title']) ?> - <?= date('d M Y', strtotime($event['event_date'])) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </select>
                                            </div>
                                            <div class="col-md-4">
                                                <button type="button" class="btn btn-info btn-custom w-100" onclick="generateMultipleQRCodes()" id="generateQRBtn" <?= !$selectedEventId ? 'disabled' : '' ?>>
                                                    <i class="fas fa-qrcode me-1"></i>Generate All QR Codes
                                                    <span class="loading-spinner ms-1" style="display: none;">
                                                        <i class="fas fa-spinner fa-spin"></i>
                                                    </span>
                                                </button>
                                            </div>
                                        </div>
                                    </form>

                                    <!-- Current Event Status Display -->
                                    <?php if ($currentEvent): ?>
                                        <div class="alert alert-info">
                                            <div class="row align-items-center">
                                                <div class="col-md-8">
                                                    <h6 class="mb-1">Selected Event: <?= esc($currentEvent['title']) ?></h6>
                                                    <small>
                                                        <i class="fas fa-calendar me-1"></i><?= date('d F Y', strtotime($currentEvent['event_date'])) ?>
                                                        <i class="fas fa-clock ms-3 me-1"></i><?= date('H:i', strtotime($currentEvent['event_time'])) ?> WIB
                                                    </small>
                                                </div>
                                                <div class="col-md-4 text-end">
                                                    <div id="eventStatusDisplay">
                                                        <?php 
                                                        $statusClass = 'status-upcoming';
                                                        $statusText = 'Belum Dimulai';
                                                        $statusIcon = 'fa-clock';
                                                        
                                                        if (!empty($eventStats)) {
                                                            switch($eventStats['event_status']) {
                                                                case 'Segera Dimulai':
                                                                    $statusClass = 'status-starting-soon';
                                                                    $statusIcon = 'fa-play-circle';
                                                                    break;
                                                                case 'Sedang Berlangsung':
                                                                    $statusClass = 'status-ongoing';
                                                                    $statusIcon = 'fa-broadcast-tower';
                                                                    break;
                                                                case 'Sudah Selesai':
                                                                    $statusClass = 'status-finished';
                                                                    $statusIcon = 'fa-check-circle';
                                                                    break;
                                                            }
                                                            $statusText = $eventStats['event_status'];
                                                        }
                                                        ?>
                                                        <div class="event-status-badge <?= $statusClass ?>">
                                                            <i class="fas <?= $statusIcon ?>"></i>
                                                            <span><?= $statusText ?></span>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-header bg-success text-white">
                                    <h6 class="mb-0">
                                        <i class="fas fa-chart-bar me-2"></i>Quick Stats
                                    </h6>
                                </div>
                                <div class="card-body">
                                    <div id="quickStats">
                                        <?php if (!empty($eventStats)): ?>
                                            <div class="row text-center">
                                                <div class="col-6 mb-2">
                                                    <div class="stat-number"><?= $eventStats['total_registered'] ?></div>
                                                    <div class="stat-label">Registered</div>
                                                </div>
                                                <div class="col-6 mb-2">
                                                    <div class="stat-number"><?= $eventStats['total_attended'] ?></div>
                                                    <div class="stat-label">Attended</div>
                                                </div>
                                                <div class="col-12">
                                                    <div class="stat-number"><?= $eventStats['attendance_rate'] ?>%</div>
                                                    <div class="stat-label">Attendance Rate</div>
                                                </div>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-center text-muted">
                                                Select an event to view statistics
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- QR Codes Display Area -->
                    <div id="qrCodesArea" style="display: none;">
                        <div class="card mb-4">
                            <div class="card-header bg-info text-white">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <h5 class="mb-0">
                                            <i class="fas fa-qrcode me-2"></i>Generated QR Codes
                                        </h5>
                                    </div>
                                    <div class="col-auto">
                                        <button class="btn btn-light btn-sm" onclick="printAllQRCodes()">
                                            <i class="fas fa-print me-1"></i>Print All
                                        </button>
                                        <button class="btn btn-outline-light btn-sm" onclick="downloadAllQRCodes()">
                                            <i class="fas fa-download me-1"></i>Download
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="card-body">
                                <div id="eventInfo" class="mb-3">
                                    <!-- Event info will be populated here -->
                                </div>
                                
                                <div class="qr-stats" id="qrStats">
                                    <!-- Stats will be populated here -->
                                </div>
                                
                                <div class="qr-grid" id="qrGrid">
                                    <!-- QR codes will be populated here -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($currentEvent): ?>
                        <!-- Attendance Management Section -->
                        <div class="row mb-4">
                            <div class="col-md-8">
                                <div class="card">
                                    <div class="card-header bg-warning text-white">
                                        <h5 class="mb-0">
                                            <i class="fas fa-users me-2"></i>Attendance Management
                                        </h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <button class="btn btn-outline-primary w-100 mb-2" onclick="showBulkMarkModal()">
                                                    <i class="fas fa-users-check me-1"></i>Bulk Mark Attendance
                                                </button>
                                            </div>
                                            <div class="col-md-4">
                                                <button class="btn btn-outline-success w-100 mb-2" onclick="exportAttendance()">
                                                    <i class="fas fa-file-excel me-1"></i>Export to Excel
                                                </button>
                                            </div>
                                            <div class="col-md-4">
                                                <button class="btn btn-outline-warning w-100 mb-2" onclick="showManualMarkModal()">
                                                    <i class="fas fa-user-plus me-1"></i>Manual Mark
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header bg-secondary text-white">
                                        <h6 class="mb-0">
                                            <i class="fas fa-sync-alt me-2"></i>Real-time Updates
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <button class="btn btn-primary w-100 mb-2" onclick="refreshAttendanceData()">
                                            <i class="fas fa-refresh me-1"></i>Refresh Data
                                        </button>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" type="checkbox" id="autoRefresh" onchange="toggleAutoRefresh()">
                                            <label class="form-check-label" for="autoRefresh">
                                                Auto-refresh (2 min)
                                            </label>
                                        </div>
                                        <small class="text-muted">Last update: <span id="lastUpdate"><?= date('H:i:s') ?></span></small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Attendance List -->
                        <div class="card">
                            <div class="card-header bg-secondary text-white">
                                <div class="row align-items-center">
                                    <div class="col">
                                        <h5 class="mb-0">
                                            <i class="fas fa-list me-2"></i>Attendance Records
                                        </h5>
                                    </div>
                                    <div class="col-auto">
                                        <input type="text" class="form-control form-control-sm" id="searchInput" placeholder="Search participants..." onkeyup="searchAttendance()">
                                    </div>
                                </div>
                            </div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table table-hover mb-0" id="attendanceTable">
                                        <thead>
                                            <tr>
                                                <th>No</th>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Role</th>
                                                <th>Participation</th>
                                                <th>Scan Time</th>
                                                <th>Status</th>
                                                <th>Marked By</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (!empty($absensiData)): ?>
                                                <?php foreach ($absensiData as $index => $attendance): ?>
                                                    <tr>
                                                        <td><?= $index + 1 ?></td>
                                                        <td>
                                                            <div class="fw-bold"><?= esc($attendance['nama_lengkap']) ?></div>
                                                            <?php if (!empty($attendance['institusi'])): ?>
                                                                <small class="text-muted"><?= esc($attendance['institusi']) ?></small>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td><?= esc($attendance['email']) ?></td>
                                                        <td>
                                                            <span class="badge bg-<?= $attendance['role'] == 'presenter' ? 'primary' : 'info' ?>">
                                                                <?= ucfirst($attendance['role']) ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-<?= ($attendance['participation_type'] ?? 'offline') == 'online' ? 'info' : 'success' ?>">
                                                                <?= ucfirst($attendance['participation_type'] ?? 'offline') ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <div><?= date('d M Y', strtotime($attendance['waktu_scan'])) ?></div>
                                                            <small class="text-muted"><?= date('H:i:s', strtotime($attendance['waktu_scan'])) ?></small>
                                                        </td>
                                                        <td>
                                                            <span class="badge bg-<?= $attendance['status'] == 'hadir' ? 'success' : 'danger' ?>">
                                                                <i class="fas fa-<?= $attendance['status'] == 'hadir' ? 'check' : 'times' ?> me-1"></i>
                                                                <?= ucfirst($attendance['status']) ?>
                                                            </span>
                                                        </td>
                                                        <td>
                                                            <?php if (!empty($attendance['marked_by_admin'])): ?>
                                                                <span class="badge bg-warning">
                                                                    <i class="fas fa-user-tie me-1"></i>Admin
                                                                </span>
                                                            <?php else: ?>
                                                                <span class="badge bg-secondary">
                                                                    <i class="fas fa-qrcode me-1"></i>QR Scan
                                                                </span>
                                                            <?php endif; ?>
                                                        </td>
                                                        <td>
                                                            <button class="btn btn-sm btn-outline-danger" onclick="removeAttendanceWithModal(<?= $attendance['id_absensi'] ?>, '<?= esc($attendance['nama_lengkap']) ?>')" title="Remove attendance">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="9" class="text-center py-4">
                                                        <i class="fas fa-users-slash fa-2x text-muted mb-2"></i>
                                                        <div class="text-muted">No attendance records yet</div>
                                                    </td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="card">
                            <div class="card-body text-center py-5">
                                <i class="fas fa-calendar-plus fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Select Event</h5>
                                <p class="text-muted">Choose an event from the dropdown above to manage QR codes and attendance.</p>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- QR Code Detail Modal -->
    <div class="modal fade" id="qrDetailModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-qrcode me-2"></i><span id="modalQRTitle">QR Code Details</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <div id="modalQRDisplay" class="modal-qr-display">
                        <!-- QR Code will be displayed here -->
                    </div>
                    <div class="mt-3">
                        <div class="qr-token" id="modalQRToken">
                            <!-- Token will be displayed here -->
                        </div>
                    </div>
                    <div class="mt-3">
                        <div class="alert alert-info">
                            <h6><i class="fas fa-info-circle me-2"></i>How to Use:</h6>
                            <ol class="text-start mb-0">
                                <li>Show this QR code to participants</li>
                                <li>Participants scan with any QR scanner or Google Lens</li>
                                <li>They will be redirected to attendance page</li>
                                <li>System automatically validates and records attendance</li>
                            </ol>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-info" onclick="copyQRToken()">
                        <i class="fas fa-copy me-1"></i>Copy Token
                    </button>
                    <button type="button" class="btn btn-success" onclick="copyQRURL()">
                        <i class="fas fa-link me-1"></i>Copy URL
                    </button>
                    <button type="button" class="btn btn-primary" onclick="printQRCode()">
                        <i class="fas fa-print me-1"></i>Print
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteConfirmModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-triangle me-2"></i>Confirm Delete
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="text-center">
                        <i class="fas fa-user-times fa-3x text-danger mb-3"></i>
                        <h6>Remove Attendance Record?</h6>
                        <p class="text-muted" id="deleteMessage">
                            <!-- Message will be populated here -->
                        </p>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            This action cannot be undone!
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-1"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                        <i class="fas fa-trash me-1"></i>Yes, Remove
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Global variables
        let currentEventId = <?= $selectedEventId ?? 'null' ?>;
        let currentQRCodes = [];
        let currentModalQR = null;
        let autoRefreshInterval = null;

        // Update current time every second
        function updateCurrentTime() {
            const now = new Date();
            document.getElementById('currentTime').textContent = now.toLocaleString('id-ID', {
                year: 'numeric',
                month: 'long',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit',
                second: '2-digit'
            });
        }

        // Generate multiple QR codes
        function generateMultipleQRCodes() {
            if (!currentEventId) {
                showAlert('Please select an event first', 'warning');
                return;
            }

            const btn = document.getElementById('generateQRBtn');
            const spinner = btn.querySelector('.loading-spinner');
            
            btn.disabled = true;
            spinner.style.display = 'inline-block';

            fetch('<?= site_url('admin/absensi/generateMultipleQRCodes') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'event_id=' + currentEventId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    currentQRCodes = data.qr_codes;
                    displayQRCodes(data);
                    showAlert('QR Codes generated successfully!', 'success');
                } else {
                    showAlert('Error: ' + (data.message || 'Unknown error'), 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Error generating QR codes: ' + error.message, 'danger');
            })
            .finally(() => {
                btn.disabled = false;
                spinner.style.display = 'none';
            });
        }

        // Calculate event status on frontend
        function calculateEventStatus(eventDate, eventTime) {
            const now = new Date();
            const eventDateTime = new Date(eventDate + ' ' + eventTime);
            
            const timeDiff = now.getTime() - eventDateTime.getTime();
            const hoursDiff = timeDiff / (1000 * 60 * 60);
            
            if (hoursDiff < -1) {
                return { 
                    status: 'Belum Dimulai', 
                    class: 'status-upcoming', 
                    ongoing: false, 
                    icon: 'fa-clock',
                    canScan: false
                };
            } else if (hoursDiff < 0) {
                return { 
                    status: 'Segera Dimulai', 
                    class: 'status-starting-soon', 
                    ongoing: false, 
                    icon: 'fa-play-circle',
                    canScan: true
                };
            } else if (hoursDiff <= 4) {
                return { 
                    status: 'Sedang Berlangsung', 
                    class: 'status-ongoing', 
                    ongoing: true, 
                    icon: 'fa-broadcast-tower',
                    canScan: true
                };
            } else {
                return { 
                    status: 'Sudah Selesai', 
                    class: 'status-finished', 
                    ongoing: false, 
                    icon: 'fa-check-circle',
                    canScan: false
                };
            }
        }

        // Display QR codes in grid
        function displayQRCodes(data) {
            const qrCodesArea = document.getElementById('qrCodesArea');
            const eventInfo = document.getElementById('eventInfo');
            const qrStats = document.getElementById('qrStats');
            const qrGrid = document.getElementById('qrGrid');

            // Calculate real-time status
            const eventStatus = calculateEventStatus(data.event_date, data.event_time);
            
            // Show event info with accurate status
            eventInfo.innerHTML = `
                <div class="row align-items-center">
                    <div class="col-md-8">
                        <h6 class="mb-1">${data.event_title}</h6>
                        <p class="mb-0 text-muted">
                            <i class="fas fa-calendar me-1"></i>${formatDate(data.event_date)}
                            <i class="fas fa-clock ms-3 me-1"></i>${formatTime(data.event_time)}
                        </p>
                    </div>
                    <div class="col-md-4 text-end">
                        <div class="event-status-badge ${eventStatus.class}">
                            <i class="fas ${eventStatus.icon}"></i>
                            <span>${eventStatus.status}</span>
                        </div>
                        <div class="mt-2">
                            <a href="${data.scanner_url}" target="_blank" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-camera me-1"></i>Open Scanner
                            </a>
                        </div>
                    </div>
                </div>
            `;

            // Set global variable
            window.isEventOngoing = eventStatus.ongoing;
            window.canScanQR = eventStatus.canScan;

            // Show stats
            qrStats.innerHTML = `
                <div class="stat-card">
                    <div class="stat-number">${data.qr_codes.length}</div>
                    <div class="stat-label">QR Codes</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="totalRegistered">-</div>
                    <div class="stat-label">Registered</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="totalAttended">-</div>
                    <div class="stat-label">Attended</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="attendanceRate">-%</div>
                    <div class="stat-label">Attendance Rate</div>
                </div>
            `;

            // Display QR codes
            qrGrid.innerHTML = '';
            data.qr_codes.forEach((qr, index) => {
                const qrCard = createQRCard(qr, index);
                qrGrid.appendChild(qrCard);
            });

            qrCodesArea.style.display = 'block';
            
            // Load live stats
            loadLiveStats();
        }

        // Create QR card element
        function createQRCard(qr, index) {
            const div = document.createElement('div');
            div.className = `qr-card priority-${qr.priority}`;
            
            div.innerHTML = `
                <div class="position-relative">
                    <div class="loading-overlay" id="loading-${index}">
                        <div class="spinner-border text-primary" role="status"></div>
                    </div>
                    
                    <div class="qr-label" style="color: ${qr.color}">
                        <i class="${qr.icon}"></i>
                        ${qr.label}
                    </div>
                    <div class="qr-description">${qr.description}</div>
                    
                    <div class="qr-code-container">
                        <canvas id="qr-canvas-${index}" width="200" height="200"></canvas>
                    </div>
                    
                    <div class="qr-actions">
                        <button class="btn btn-primary btn-qr" onclick="showQRDetail(${index})">
                            <i class="fas fa-expand-alt"></i> View
                        </button>
                        <button class="btn btn-success btn-qr" onclick="copyQRURL(${index})">
                            <i class="fas fa-link"></i> Copy URL
                        </button>
                        <button class="btn btn-info btn-qr" onclick="copyQRToken(${index})">
                            <i class="fas fa-copy"></i> Token
                        </button>
                        <button class="btn btn-warning btn-qr" onclick="printSingleQR(${index})">
                            <i class="fas fa-print"></i> Print
                        </button>
                    </div>
                    
                    <div class="qr-token">${qr.token}</div>
                </div>
            `;

            // Generate QR code
            setTimeout(() => {
                generateQRCode(`qr-canvas-${index}`, qr.url, qr.color);
                document.getElementById(`loading-${index}`).classList.remove('show');
            }, index * 200);

            return div;
        }

        // Generate QR code on canvas
        function generateQRCode(canvasId, text, color = '#2563eb') {
            const canvas = document.getElementById(canvasId);
            if (!canvas) return;

            if (typeof QRCode !== 'undefined' && QRCode.toCanvas) {
                QRCode.toCanvas(canvas, text, {
                    width: 200,
                    margin: 2,
                    color: {
                        dark: color,
                        light: '#ffffff'
                    }
                }, function(error) {
                    if (error) {
                        console.error('QR generation error:', error);
                        generateQRCodeFallback(canvas, text);
                    }
                });
            } else {
                generateQRCodeFallback(canvas, text);
            }
        }

        // Fallback QR generation using API
        function generateQRCodeFallback(canvas, text) {
            const img = new Image();
            img.crossOrigin = 'anonymous';
            img.onload = function() {
                const ctx = canvas.getContext('2d');
                ctx.drawImage(img, 0, 0, 200, 200);
            };
            img.onerror = function() {
                const ctx = canvas.getContext('2d');
                ctx.fillStyle = '#f3f4f6';
                ctx.fillRect(0, 0, 200, 200);
                ctx.fillStyle = '#6b7280';
                ctx.font = '14px Arial';
                ctx.textAlign = 'center';
                ctx.fillText('QR Code', 100, 90);
                ctx.fillText('Placeholder', 100, 110);
            };
            img.src = `https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=${encodeURIComponent(text)}`;
        }

        // Show QR detail modal
        function showQRDetail(index) {
            if (!currentQRCodes[index]) return;
            
            currentModalQR = currentQRCodes[index];
            
            document.getElementById('modalQRTitle').textContent = currentModalQR.label + ' - QR Code';
            document.getElementById('modalQRToken').textContent = currentModalQR.token;
            
            const display = document.getElementById('modalQRDisplay');
            display.innerHTML = '<canvas id="modalQRCanvas" width="300" height="300"></canvas>';
            
            generateQRCode('modalQRCanvas', currentModalQR.url, currentModalQR.color);
            
            new bootstrap.Modal(document.getElementById('qrDetailModal')).show();
        }

        // Copy QR URL
        function copyQRURL(index = null) {
            const qr = index !== null ? currentQRCodes[index] : currentModalQR;
            if (!qr) return;
            
            navigator.clipboard.writeText(qr.url).then(() => {
                showAlert('QR URL copied to clipboard!', 'success');
            }).catch(() => {
                showAlert('Failed to copy URL', 'danger');
            });
        }

        // Copy QR Token
        function copyQRToken(index = null) {
            const qr = index !== null ? currentQRCodes[index] : currentModalQR;
            if (!qr) return;
            
            navigator.clipboard.writeText(qr.token).then(() => {
                showAlert('QR token copied to clipboard!', 'success');
            }).catch(() => {
                showAlert('Failed to copy token', 'danger');
            });
        }

        // Print single QR code
        function printSingleQR(index) {
            if (!currentQRCodes[index]) return;
            
            const qr = currentQRCodes[index];
            const canvas = document.getElementById(`qr-canvas-${index}`);
            
            createPrintWindow([{
                qr: qr,
                canvas: canvas
            }]);
        }

        // Print all QR codes
        function printAllQRCodes() {
            const qrData = currentQRCodes.map((qr, index) => ({
                qr: qr,
                canvas: document.getElementById(`qr-canvas-${index}`)
            }));
            
            createPrintWindow(qrData);
        }

        // Create print window
        function createPrintWindow(qrData) {
            const printWindow = window.open('', '_blank');
            
            let content = `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>QR Codes - Attendance System</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
                        .print-page { page-break-after: always; text-align: center; }
                        .print-page:last-child { page-break-after: avoid; }
                        .qr-container { 
                            border: 3px solid #333; 
                            padding: 30px; 
                            margin: 20px auto; 
                            display: inline-block;
                            background: white;
                        }
                        .qr-title { font-size: 24px; font-weight: bold; margin-bottom: 10px; }
                        .qr-description { font-size: 16px; margin-bottom: 20px; color: #666; }
                        .qr-token { 
                            font-family: monospace; 
                            font-size: 12px; 
                            margin-top: 15px; 
                            word-break: break-all;
                            max-width: 300px;
                        }
                        .instructions {
                            margin-top: 30px;
                            font-size: 14px;
                            color: #666;
                            max-width: 400px;
                            margin-left: auto;
                            margin-right: auto;
                        }
                        @media print {
                            body { margin: 0; }
                            .print-page { padding: 40px; }
                        }
                    </style>
                </head>
                <body>
            `;

            qrData.forEach((item, index) => {
                content += `
                    <div class="print-page">
                        <div class="qr-title">${item.qr.label}</div>
                        <div class="qr-description">${item.qr.description}</div>
                        <div class="qr-container">
                            <img src="${item.canvas.toDataURL()}" alt="QR Code" />
                            <div class="qr-token">${item.qr.token}</div>
                        </div>
                        <div class="instructions">
                            <p><strong>Instructions:</strong></p>
                            <p>1. Show this QR code to participants<br>
                            2. Participants scan with any QR reader or Google Lens<br>
                            3. System will automatically validate and record attendance<br>
                            4. Participants must be logged in and have verified payment</p>
                        </div>
                    </div>
                `;
            });

            content += '</body></html>';
            
            printWindow.document.write(content);
            printWindow.document.close();
            
            printWindow.onload = function() {
                setTimeout(() => {
                    printWindow.focus();
                    printWindow.print();
                }, 500);
            };
        }

        // Load live statistics
        function loadLiveStats() {
            if (!currentEventId) return;
            
            fetch(`<?= site_url('admin/absensi/liveStats') ?>?event_id=${currentEventId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById('totalRegistered').textContent = data.stats.total_registered;
                    document.getElementById('totalAttended').textContent = data.stats.total_attended;
                    document.getElementById('attendanceRate').textContent = data.stats.attendance_rate + '%';
                    document.getElementById('lastUpdate').textContent = data.stats.last_updated;
                }
            })
            .catch(error => {
                console.error('Failed to load live stats:', error);
            });
        }

        // Update event status display
        function updateEventStatus() {
            if (!currentEventId) return;
            
            fetch(`<?= site_url('admin/absensi/getEventStatus') ?>?event_id=${currentEventId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const statusDisplay = document.getElementById('eventStatusDisplay');
                    if (statusDisplay) {
                        const badge = statusDisplay.querySelector('.event-status-badge');
                        if (badge) {
                            // Update badge class
                            badge.className = `event-status-badge ${data.badge_class.replace('bg-', 'status-')}`;
                            
                            // Update icon and text
                            const icon = badge.querySelector('i');
                            const text = badge.querySelector('span');
                            
                            if (icon && text) {
                                const iconClass = data.status === 'Segera Dimulai' ? 'fa-play-circle' :
                                                data.status === 'Sedang Berlangsung' ? 'fa-broadcast-tower' :
                                                data.status === 'Sudah Selesai' ? 'fa-check-circle' : 'fa-clock';
                                
                                icon.className = `fas ${iconClass}`;
                                text.textContent = data.status;
                            }
                        }
                    }
                }
            })
            .catch(error => {
                console.error('Failed to update event status:', error);
            });
        }

        // Remove attendance record with modal confirmation
        function removeAttendanceWithModal(attendanceId, participantName) {
            document.getElementById('deleteMessage').innerHTML = `
                Are you sure you want to remove the attendance record for 
                <strong>${participantName}</strong>?
            `;
            
            const confirmBtn = document.getElementById('confirmDeleteBtn');
            confirmBtn.onclick = function() {
                confirmRemoveAttendance(attendanceId);
            };
            
            new bootstrap.Modal(document.getElementById('deleteConfirmModal')).show();
        }

        // Confirm and execute removal
        function confirmRemoveAttendance(attendanceId) {
            const modal = bootstrap.Modal.getInstance(document.getElementById('deleteConfirmModal'));
            modal.hide();
            removeAttendance(attendanceId);
        }

        // Remove attendance record
        function removeAttendance(attendanceId) {
            if (!attendanceId) {
                showAlert('Invalid attendance ID', 'danger');
                return;
            }

            fetch('<?= site_url('admin/absensi/removeAttendance') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'attendance_id=' + attendanceId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showAlert('Attendance record removed successfully!', 'success');
                    const row = document.querySelector(`[onclick*="removeAttendanceWithModal(${attendanceId}"]`).closest('tr');
                    if (row) {
                        row.remove();
                    }
                    loadLiveStats();
                } else {
                    showAlert('Error: ' + (data.message || 'Failed to remove attendance record'), 'danger');
                }
            })
            .catch(error => {
                console.error('Error:', error);
                showAlert('Network error: Failed to remove attendance record', 'danger');
            });
        }

        // Auto refresh toggle
        function toggleAutoRefresh() {
            const checkbox = document.getElementById('autoRefresh');
            
            if (checkbox.checked) {
                autoRefreshInterval = setInterval(() => {
                    loadLiveStats();
                    updateEventStatus();
                    if (!document.querySelector('.modal.show')) {
                        refreshAttendanceData(true);
                    }
                }, 120000); // 2 minutes
                showAlert('Auto-refresh enabled', 'info');
            } else {
                if (autoRefreshInterval) {
                    clearInterval(autoRefreshInterval);
                    autoRefreshInterval = null;
                }
                showAlert('Auto-refresh disabled', 'info');
            }
        }

        // Manual refresh
        function refreshAttendanceData(silent = false) {
            if (!silent) {
                showAlert('Refreshing data...', 'info');
            }
            
            loadLiveStats();
            updateEventStatus();
            
            if (document.getElementById('attendanceTable') && !silent) {
                setTimeout(() => {
                    location.reload();
                }, 1000);
            }
        }

        // Search attendance records
        function searchAttendance() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('#attendanceTable tbody tr');

            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                if (cells.length <= 1) return;
                
                let found = false;
                for (let i = 1; i < 4; i++) {
                    if (cells[i] && cells[i].textContent.toLowerCase().includes(searchTerm)) {
                        found = true;
                        break;
                    }
                }
                row.style.display = found ? '' : 'none';
            });
        }

        // Utility functions
        function formatDate(dateStr) {
            return new Date(dateStr).toLocaleDateString('id-ID', {
                day: 'numeric',
                month: 'long',
                year: 'numeric'
            });
        }

        function formatTime(timeStr) {
            return timeStr.substring(0, 5) + ' WIB';
        }

        function showAlert(message, type = 'info') {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show position-fixed`;
            alertDiv.style.cssText = 'top: 20px; right: 20px; z-index: 10000; min-width: 300px;';
            
            const icons = {
                'success': 'fa-check-circle',
                'danger': 'fa-exclamation-circle',
                'warning': 'fa-exclamation-triangle',
                'info': 'fa-info-circle'
            };
            
            alertDiv.innerHTML = `
                <i class="fas ${icons[type] || 'fa-info-circle'} me-2"></i>${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            document.body.appendChild(alertDiv);
            
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.parentNode.removeChild(alertDiv);
                }
            }, 5000);
        }

        // Export attendance function
        function exportAttendance() {
            if (!currentEventId) {
                showAlert('Please select an event first', 'warning');
                return;
            }
            window.open(`<?= site_url('admin/absensi/export') ?>?event_id=${currentEventId}`, '_blank');
        }

        // Placeholder functions for future implementation
        function showBulkMarkModal() {
            showAlert('Bulk mark feature will be implemented soon', 'info');
        }

        function showManualMarkModal() {
            showAlert('Manual mark feature will be implemented soon', 'info');
        }

        function downloadAllQRCodes() {
            showAlert('Download feature will be implemented soon', 'info');
        }

        // Initialize on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Update time every second
            setInterval(updateCurrentTime, 1000);
            
            // Load initial stats if event is selected
            if (currentEventId) {
                loadLiveStats();
                
                // Update event status every 30 seconds
                setInterval(updateEventStatus, 30000);
            }
        });
    </script>
</body>
</html>