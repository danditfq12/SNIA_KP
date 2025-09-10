<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= esc($event['title']) ?> - SNIA Presenter</title>
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

        .event-header {
            background: linear-gradient(135deg, var(--purple-color) 0%, #7c3aed 100%);
            color: white;
            padding: 40px;
            border-radius: 16px;
            margin-bottom: 30px;
            position: relative;
            overflow: hidden;
        }

        .event-header::before {
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

        .event-meta-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin: 30px 0;
        }

        .meta-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.05);
            border-left: 4px solid var(--primary-color);
            transition: all 0.3s ease;
        }

        .meta-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
        }

        .meta-card .meta-label {
            color: #64748b;
            font-size: 14px;
            margin-bottom: 8px;
            text-transform: uppercase;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .meta-card .meta-value {
            color: #1e293b;
            font-size: 18px;
            font-weight: 600;
        }

        .pricing-card {
            background: linear-gradient(135deg, var(--success-color) 0%, #059669 100%);
            color: white;
            border-radius: 16px;
            padding: 24px;
            text-align: center;
            margin: 24px 0;
            position: relative;
            overflow: hidden;
        }

        .pricing-card.free {
            background: linear-gradient(135deg, var(--info-color) 0%, #0891b2 100%);
        }

        .pricing-card::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -50%;
            width: 100px;
            height: 100px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            transform: rotate(45deg);
        }

        .pricing-amount {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .status-timeline {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            margin: 24px 0;
        }

        .timeline-step {
            display: flex;
            align-items: flex-start;
            padding: 16px 0;
            position: relative;
        }

        .timeline-step:not(:last-child)::after {
            content: '';
            position: absolute;
            left: 20px;
            top: 60px;
            bottom: -16px;
            width: 2px;
            background: #e2e8f0;
        }

        .timeline-step.completed::after {
            background: var(--success-color);
        }

        .timeline-step.active::after {
            background: var(--primary-color);
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

        .timeline-icon.pending {
            background: #f1f5f9;
            color: #64748b;
        }

        .timeline-icon.active {
            background: var(--primary-color);
            color: white;
        }

        .timeline-icon.completed {
            background: var(--success-color);
            color: white;
        }

        .timeline-content h6 {
            margin-bottom: 4px;
            color: #1e293b;
        }

        .timeline-content p {
            margin-bottom: 0;
            color: #64748b;
            font-size: 14px;
        }

        .action-section {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            margin: 24px 0;
            text-align: center;
        }

        .btn-action-primary {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--info-color) 100%);
            border: none;
            border-radius: 12px;
            padding: 16px 32px;
            color: white;
            font-weight: 600;
            font-size: 16px;
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

        .deadline-alert {
            border-radius: 12px;
            padding: 16px;
            margin: 16px 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .deadline-alert.warning {
            background: #fef3c7;
            border: 1px solid #fbbf24;
            color: #92400e;
        }

        .deadline-alert.danger {
            background: #fee2e2;
            border: 1px solid #f87171;
            color: #dc2626;
        }

        .deadline-alert.info {
            background: #dbeafe;
            border: 1px solid #60a5fa;
            color: #1d4ed8;
        }

        .info-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 24px;
            margin: 24px 0;
        }

        .info-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
        }

        .info-card h5 {
            color: #1e293b;
            margin-bottom: 16px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .badge-status {
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-completed { background: #d1fae5; color: #059669; }
        .badge-pending { background: #fef3c7; color: #d97706; }
        .badge-active { background: #dbeafe; color: #1d4ed8; }
        .badge-rejected { background: #fee2e2; color: #dc2626; }

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

        .breadcrumb-custom .breadcrumb-item + .breadcrumb-item::before {
            color: #cbd5e1;
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
                        <small class="text-white-50">Event Detail</small>
                    </div>
                    
                    <nav class="nav flex-column px-3">
                        <a class="nav-link" href="<?= site_url('presenter/dashboard') ?>">
                            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                        </a>
                        <a class="nav-link active" href="<?= site_url('presenter/events') ?>">
                            <i class="fas fa-calendar-alt me-2"></i> Event
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
                                <a href="<?= site_url('presenter/events') ?>">Event</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">
                                <?= esc($event['title']) ?>
                            </li>
                        </ol>
                    </nav>

                    <!-- Event Header -->
                    <div class="event-header">
                        <div class="row align-items-center">
                            <div class="col">
                                <h1 class="mb-3">
                                    <i class="fas fa-calendar-alt me-3"></i>
                                    <?= esc($event['title']) ?>
                                </h1>
                                <?php if (!empty($event['description'])): ?>
                                <p class="mb-0 opacity-90 fs-5">
                                    <?= esc($event['description']) ?>
                                </p>
                                <?php endif; ?>
                            </div>
                            <div class="col-auto">
                                <div class="text-end">
                                    <?php if (isset($event['is_active']) && $event['is_active']): ?>
                                        <div class="badge bg-success bg-opacity-25 text-success fs-6 px-3 py-2">
                                            <i class="fas fa-check-circle me-2"></i>Aktif
                                        </div>
                                    <?php else: ?>
                                        <div class="badge bg-secondary bg-opacity-25 text-secondary fs-6 px-3 py-2">
                                            <i class="fas fa-pause-circle me-2"></i>Non-Aktif
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Event Meta Information -->
                    <div class="event-meta-grid">
                        <div class="meta-card">
                            <div class="meta-label">Tanggal & Waktu</div>
                            <div class="meta-value">
                                <i class="fas fa-calendar me-2 text-primary"></i>
                                <?= date('d F Y', strtotime($event['event_date'])) ?>
                                <br>
                                <i class="fas fa-clock me-2 text-primary"></i>
                                <?= date('H:i', strtotime($event['event_time'])) ?> WIB
                            </div>
                        </div>

                        <div class="meta-card">
                            <div class="meta-label">Format & Lokasi</div>
                            <div class="meta-value">
                                <i class="fas fa-laptop me-2 text-info"></i>
                                <?= ucfirst($event['format']) ?>
                                <br>
                                <?php if ($event['format'] === 'offline' || $event['format'] === 'both'): ?>
                                    <i class="fas fa-map-marker-alt me-2 text-danger"></i>
                                    <?= $event['location'] ?: 'TBA' ?>
                                <?php endif; ?>
                                <?php if ($event['format'] === 'online' || $event['format'] === 'both'): ?>
                                    <i class="fas fa-video me-2 text-success"></i>
                                    Online Meeting
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="meta-card">
                            <div class="meta-label">Deadline Pendaftaran</div>
                            <div class="meta-value">
                                <?php if ($event['registration_deadline']): ?>
                                    <i class="fas fa-hourglass-half me-2 text-warning"></i>
                                    <?= date('d F Y H:i', strtotime($event['registration_deadline'])) ?>
                                <?php else: ?>
                                    <i class="fas fa-infinity me-2 text-success"></i>
                                    Tidak terbatas
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="meta-card">
                            <div class="meta-label">Deadline Abstrak</div>
                            <div class="meta-value">
                                <?php if ($event['abstract_deadline']): ?>
                                    <i class="fas fa-file-alt me-2 text-info"></i>
                                    <?= date('d F Y H:i', strtotime($event['abstract_deadline'])) ?>
                                <?php else: ?>
                                    <i class="fas fa-infinity me-2 text-success"></i>
                                    Tidak terbatas
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>

                    <!-- Pricing Information -->
                    <div class="pricing-card <?= $presenter_price == 0 ? 'free' : '' ?>">
                        <div class="pricing-amount">
                            <?php if ($presenter_price == 0): ?>
                                <i class="fas fa-gift me-2"></i>GRATIS
                            <?php else: ?>
                                Rp <?= number_format($presenter_price, 0, ',', '.') ?>
                            <?php endif; ?>
                        </div>
                        <div>Biaya Partisipasi Presenter (Offline)</div>
                    </div>

                    <!-- Deadline Alerts -->
                    <?php if (isset($event['time_info'])): ?>
                        <?php if ($event['time_info']['days_until_registration_deadline'] !== null): ?>
                            <?php if ($event['time_info']['days_until_registration_deadline'] <= 0): ?>
                                <div class="deadline-alert danger">
                                    <i class="fas fa-times-circle"></i>
                                    <span><strong>Pendaftaran telah ditutup</strong> pada <?= date('d F Y', strtotime($event['registration_deadline'])) ?></span>
                                </div>
                            <?php elseif ($event['time_info']['days_until_registration_deadline'] <= 3): ?>
                                <div class="deadline-alert warning">
                                    <i class="fas fa-exclamation-triangle"></i>
                                    <span><strong>Pendaftaran berakhir dalam <?= $event['time_info']['days_until_registration_deadline'] ?> hari</strong></span>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>

                        <?php if ($event['time_info']['days_until_abstract_deadline'] !== null): ?>
                            <?php if ($event['time_info']['days_until_abstract_deadline'] <= 0): ?>
                                <div class="deadline-alert danger">
                                    <i class="fas fa-times-circle"></i>
                                    <span><strong>Deadline abstrak telah berakhir</strong> pada <?= date('d F Y', strtotime($event['abstract_deadline'])) ?></span>
                                </div>
                            <?php elseif ($event['time_info']['days_until_abstract_deadline'] <= 7): ?>
                                <div class="deadline-alert info">
                                    <i class="fas fa-info-circle"></i>
                                    <span><strong>Deadline abstrak dalam <?= $event['time_info']['days_until_abstract_deadline'] ?> hari</strong></span>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php endif; ?>

                    <!-- Info Grid -->
                    <div class="info-grid">
                        <!-- Status Timeline -->
                        <div class="info-card">
                            <h5>
                                <i class="fas fa-tasks text-primary"></i>
                                Progress Partisipasi
                            </h5>
                            
                            <div class="status-timeline">
                                <!-- Step 1: Abstract Submission -->
                                <div class="timeline-step <?= !empty($abstract) ? 'completed' : ($can_submit_abstract ? 'active' : 'pending') ?>">
                                    <div class="timeline-icon <?= !empty($abstract) ? 'completed' : ($can_submit_abstract ? 'active' : 'pending') ?>">
                                        <i class="fas fa-file-alt"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <h6>Upload Abstrak</h6>
                                        <?php if (!empty($abstract)): ?>
                                            <p>Abstrak telah diupload - Status: 
                                                <span class="badge-status badge-<?= $abstract['status'] === 'diterima' ? 'completed' : ($abstract['status'] === 'menunggu' ? 'pending' : 'rejected') ?>">
                                                    <?= ucfirst($abstract['status']) ?>
                                                </span>
                                            </p>
                                        <?php elseif ($can_submit_abstract): ?>
                                            <p>Upload abstrak Anda untuk event ini</p>
                                        <?php else: ?>
                                            <p>Periode submission abstrak telah berakhir</p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Step 2: Abstract Review -->
                                <div class="timeline-step <?= !empty($abstract) && $abstract['status'] === 'diterima' ? 'completed' : (!empty($abstract) ? 'active' : 'pending') ?>">
                                    <div class="timeline-icon <?= !empty($abstract) && $abstract['status'] === 'diterima' ? 'completed' : (!empty($abstract) ? 'active' : 'pending') ?>">
                                        <i class="fas fa-search"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <h6>Review Abstrak</h6>
                                        <?php if (!empty($abstract) && $abstract['status'] === 'diterima'): ?>
                                            <p>Abstrak telah diterima dan direview</p>
                                        <?php elseif (!empty($abstract)): ?>
                                            <p>Abstrak sedang dalam proses review</p>
                                        <?php else: ?>
                                            <p>Menunggu submission abstrak</p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Step 3: Payment -->
                                <div class="timeline-step <?= !empty($payment) && $payment['status'] === 'verified' ? 'completed' : (!empty($payment) ? 'active' : 'pending') ?>">
                                    <div class="timeline-icon <?= !empty($payment) && $payment['status'] === 'verified' ? 'completed' : (!empty($payment) ? 'active' : 'pending') ?>">
                                        <i class="fas fa-credit-card"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <h6>Pembayaran</h6>
                                        <?php if (!empty($payment) && $payment['status'] === 'verified'): ?>
                                            <p>Pembayaran telah diverifikasi</p>
                                        <?php elseif (!empty($payment)): ?>
                                            <p>Pembayaran dalam proses verifikasi</p>
                                        <?php elseif (!empty($abstract) && $abstract['status'] === 'diterima'): ?>
                                            <p>Lakukan pembayaran untuk konfirmasi partisipasi</p>
                                        <?php else: ?>
                                            <p>Menunggu abstract diterima</p>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Step 4: Event Participation -->
                                <div class="timeline-step pending">
                                    <div class="timeline-icon pending">
                                        <i class="fas fa-qrcode"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <h6>Partisipasi Event</h6>
                                        <?php if (!empty($payment) && $payment['status'] === 'verified'): ?>
                                            <p>Siap untuk mengikuti event - Gunakan QR Scanner</p>
                                        <?php else: ?>
                                            <p>Menunggu pembayaran terverifikasi</p>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Current Status & Actions -->
                        <div class="info-card">
                            <h5>
                                <i class="fas fa-clipboard-check text-success"></i>
                                Status Saat Ini
                            </h5>
                            
                            <?php if (!$registration): ?>
                                <!-- Not Registered -->
                                <div class="text-center py-4">
                                    <i class="fas fa-user-plus fa-3x text-muted mb-3"></i>
                                    <h6>Belum Terdaftar</h6>
                                    <p class="text-muted">Anda belum terdaftar untuk event ini.</p>
                                </div>
                            <?php elseif (!$abstract): ?>
                                <!-- Registered but no abstract -->
                                <div class="text-center py-4">
                                    <i class="fas fa-file-upload fa-3x text-primary mb-3"></i>
                                    <h6>Upload Abstrak Diperlukan</h6>
                                    <p class="text-muted">Anda sudah terdaftar. Silakan upload abstrak untuk melanjutkan.</p>
                                </div>
                            <?php elseif ($abstract['status'] === 'menunggu'): ?>
                                <!-- Abstract pending review -->
                                <div class="text-center py-4">
                                    <i class="fas fa-hourglass-half fa-3x text-warning mb-3"></i>
                                    <h6>Menunggu Review</h6>
                                    <p class="text-muted">Abstrak sedang direview oleh tim reviewer.</p>
                                </div>
                            <?php elseif ($abstract['status'] === 'diterima' && !$payment): ?>
                                <!-- Abstract accepted, need payment -->
                                <div class="text-center py-4">
                                    <i class="fas fa-credit-card fa-3x text-success mb-3"></i>
                                    <h6>Abstrak Diterima!</h6>
                                    <p class="text-muted">Lakukan pembayaran untuk mengkonfirmasi partisipasi Anda.</p>
                                </div>
                            <?php elseif ($payment && $payment['status'] === 'pending'): ?>
                                <!-- Payment pending -->
                                <div class="text-center py-4">
                                    <i class="fas fa-clock fa-3x text-info mb-3"></i>
                                    <h6>Menunggu Verifikasi Pembayaran</h6>
                                    <p class="text-muted">Pembayaran Anda sedang diverifikasi oleh admin.</p>
                                </div>
                            <?php elseif ($payment && $payment['status'] === 'verified'): ?>
                                <!-- Ready to participate -->
                                <div class="text-center py-4">
                                    <i class="fas fa-check-circle fa-3x text-success mb-3"></i>
                                    <h6>Siap Berpartisipasi!</h6>
                                    <p class="text-muted">Semua persyaratan telah dipenuhi. Anda dapat mengakses fitur event.</p>
                                </div>
                            <?php endif; ?>

                            <!-- Registration Details if exists -->
                            <?php if ($registration): ?>
                                <div class="mt-3 p-3 bg-light rounded">
                                    <small class="text-muted">
                                        <strong>Terdaftar:</strong> <?= date('d F Y H:i', strtotime($registration['created_at'])) ?><br>
                                        <strong>Mode:</strong> <?= ucfirst($registration['mode_kehadiran']) ?><br>
                                        <strong>Status:</strong> 
                                        <span class="badge bg-<?= $registration['status'] === 'lunas' ? 'success' : 'warning' ?>">
                                            <?= ucfirst($registration['status']) ?>
                                        </span>
                                    </small>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Action Section -->
                    <div class="action-section">
                        <h5 class="mb-4">
                            <i class="fas fa-bolt text-warning me-2"></i>
                            Aksi Yang Tersedia
                        </h5>

                        <?php if (!$registration && $can_register): ?>
                            <!-- Can register -->
                            <a href="<?= site_url('presenter/events/register/' . $event['id']) ?>" 
                               class="btn-action-primary">
                                <i class="fas fa-user-plus me-2"></i>
                                Daftar Event Ini
                            </a>
                            <p class="text-muted mt-2">Mulai proses pendaftaran sebagai presenter</p>

                        <?php elseif (!$registration && !$can_register): ?>
                            <!-- Cannot register -->
                            <div class="alert alert-warning">
                                <i class="fas fa-lock me-2"></i>
                                Pendaftaran untuk event ini sudah ditutup.
                            </div>

                        <?php elseif ($registration && !$abstract && $can_submit_abstract): ?>
                            <!-- Need to submit abstract -->
                            <a href="<?= site_url('presenter/abstrak?event_id=' . $event['id']) ?>" 
                               class="btn-action-primary">
                                <i class="fas fa-file-upload me-2"></i>
                                Upload Abstrak
                            </a>
                            <p class="text-muted mt-2">Upload abstrak untuk melanjutkan proses pendaftaran</p>

                        <?php elseif ($abstract && $abstract['status'] === 'menunggu'): ?>
                            <!-- Abstract pending -->
                            <a href="<?= site_url('presenter/abstrak/detail/' . $abstract['id_abstrak']) ?>" 
                               class="btn btn-outline-primary">
                                <i class="fas fa-eye me-2"></i>
                                Lihat Status Abstrak
                            </a>
                            <p class="text-muted mt-2">Pantau status review abstrak Anda</p>

                        <?php elseif ($abstract && $abstract['status'] === 'revisi' && $can_submit_abstract): ?>
                            <!-- Abstract needs revision -->
                            <a href="<?= site_url('presenter/abstrak/detail/' . $abstract['id_abstrak']) ?>" 
                               class="btn-action-primary">
                                <i class="fas fa-edit me-2"></i>
                                Revisi Abstrak
                            </a>
                            <p class="text-muted mt-2">Perbaiki abstrak sesuai feedback reviewer</p>

                        <?php elseif ($abstract && $abstract['status'] === 'diterima' && !$payment): ?>
                            <!-- Can make payment -->
                            <a href="<?= site_url('presenter/pembayaran/create/' . $event['id']) ?>" 
                               class="btn-action-primary">
                                <i class="fas fa-credit-card me-2"></i>
                                Lakukan Pembayaran
                            </a>
                            <p class="text-muted mt-2">Bayar biaya partisipasi untuk mengkonfirmasi keikutsertaan</p>

                        <?php elseif ($payment && $payment['status'] === 'pending'): ?>
                            <!-- Payment pending -->
                            <a href="<?= site_url('presenter/pembayaran/detail/' . $payment['id_pembayaran']) ?>" 
                               class="btn btn-outline-info">
                                <i class="fas fa-receipt me-2"></i>
                                Lihat Status Pembayaran
                            </a>
                            <p class="text-muted mt-2">Pantau status verifikasi pembayaran Anda</p>

                        <?php elseif ($payment && $payment['status'] === 'verified'): ?>
                            <!-- Can access event features -->
                            <div class="row g-2 justify-content-center">
                                <div class="col-auto">
                                    <a href="<?= site_url('presenter/absensi') ?>" 
                                       class="btn-action-primary">
                                        <i class="fas fa-qrcode me-2"></i>
                                        QR Scanner
                                    </a>
                                </div>
                                <div class="col-auto">
                                    <a href="<?= site_url('presenter/dokumen/loa') ?>" 
                                       class="btn btn-outline-success">
                                        <i class="fas fa-certificate me-2"></i>
                                        LOA & Dokumen
                                    </a>
                                </div>
                                <div class="col-auto">
                                    <a href="<?= site_url('presenter/dashboard') ?>" 
                                       class="btn btn-outline-primary">
                                        <i class="fas fa-chart-line me-2"></i>
                                        Dashboard
                                    </a>
                                </div>
                            </div>
                            <p class="text-success mt-2">
                                <i class="fas fa-check-circle me-1"></i>
                                Semua fitur event tersedia untuk Anda!
                            </p>

                        <?php else: ?>
                            <!-- Default state -->
                            <div class="text-muted">
                                <i class="fas fa-info-circle me-2"></i>
                                Tidak ada aksi yang tersedia saat ini.
                            </div>
                        <?php endif; ?>

                        <!-- Always show back to events button -->
                        <hr class="my-4">
                        <a href="<?= site_url('presenter/events') ?>" 
                           class="btn btn-outline-secondary">
                            <i class="fas fa-arrow-left me-2"></i>
                            Kembali ke Daftar Event
                        </a>
                    </div>

                    <!-- Additional Information -->
                    <?php if (!empty($event['zoom_link']) && ($event['format'] === 'online' || $event['format'] === 'both')): ?>
                    <div class="info-card">
                        <h5>
                            <i class="fas fa-video text-success me-2"></i>
                            Informasi Online Meeting
                        </h5>
                        <?php if ($payment && $payment['status'] === 'verified'): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Link meeting akan dikirimkan melalui email sebelum event dimulai.
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-lock me-2"></i>
                                Link meeting hanya tersedia setelah pembayaran terverifikasi.
                            </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container position-fixed top-0 end-0 p-3" id="toastContainer"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Show flash messages
            showFlashMessages();

            // Add confirmation for important actions
            setupActionConfirmations();

            // Auto-refresh event status
            setInterval(refreshEventStatus, 60000);
        });

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

        function setupActionConfirmations() {
            // Add confirmation for registration
            const registerBtn = document.querySelector('a[href*="/register/"]');
            if (registerBtn) {
                registerBtn.addEventListener('click', function(e) {
                    if (!confirm('Apakah Anda yakin ingin mendaftar untuk event ini?')) {
                        e.preventDefault();
                    }
                });
            }

            // Add confirmation for payment
            const paymentBtn = document.querySelector('a[href*="/pembayaran/create/"]');
            if (paymentBtn) {
                paymentBtn.addEventListener('click', function(e) {
                    if (!confirm('Anda akan diarahkan ke halaman pembayaran. Pastikan Anda sudah siap untuk melakukan pembayaran.')) {
                        e.preventDefault();
                    }
                });
            }
        }

        function refreshEventStatus() {
            // Refresh event status via AJAX
            fetch(`<?= site_url('presenter/events/refreshStatus/' . $event['id']) ?>`, {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success && data.needsRefresh) {
                    // Refresh page if status changed significantly
                    location.reload();
                }
            })
            .catch(error => {
                console.log('Status refresh error:', error);
            });
        }

        // Add smooth scrolling for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                const target = document.querySelector(this.getAttribute('href'));
                if (target) {
                    target.scrollIntoView({
                        behavior: 'smooth'
                    });
                }
            });
        });

        // Add loading states for action buttons
        document.querySelectorAll('.btn-action-primary, .btn[href*="/register/"], .btn[href*="/pembayaran/"]').forEach(btn => {
            btn.addEventListener('click', function() {
                const originalText = this.innerHTML;
                this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memproses...';
                this.disabled = true;
                
                // Re-enable after 3 seconds (in case of navigation failure)
                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.disabled = false;
                }, 3000);
            });
        });
    </script>
</body>
</html>