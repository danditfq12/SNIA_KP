<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event - SNIA Presenter</title>
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

        .event-card {
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

        .event-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 48px rgba(0,0,0,0.12);
        }

        .event-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, var(--purple-color) 0%, var(--primary-color) 100%);
        }

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

        .status-not_registered {
            background: #f1f5f9;
            color: #64748b;
        }

        .status-registered {
            background: #dbeafe;
            color: #1d4ed8;
        }

        .status-abstract_pending {
            background: #fef3c7;
            color: #d97706;
        }

        .status-abstract_accepted {
            background: #d1fae5;
            color: #059669;
        }

        .status-payment_pending {
            background: #fde68a;
            color: #d97706;
        }

        .status-payment_verified {
            background: #a7f3d0;
            color: #047857;
        }

        .event-meta {
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

        .price-badge {
            background: linear-gradient(135deg, var(--success-color) 0%, #059669 100%);
            color: white;
            padding: 12px 20px;
            border-radius: 12px;
            font-weight: 600;
            font-size: 18px;
            text-align: center;
            margin: 16px 0;
        }

        .free-badge {
            background: linear-gradient(135deg, var(--info-color) 0%, #0891b2 100%);
        }

        .deadline-warning {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #dc2626;
            padding: 12px;
            border-radius: 8px;
            margin: 16px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .deadline-info {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            color: #0369a1;
            padding: 12px;
            border-radius: 8px;
            margin: 16px 0;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .action-buttons {
            display: flex;
            gap: 12px;
            flex-wrap: wrap;
            margin-top: 20px;
        }

        .btn-primary-gradient {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--info-color) 100%);
            border: none;
            border-radius: 10px;
            padding: 12px 24px;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .btn-primary-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.3);
            color: white;
        }

        .header-section {
            background: linear-gradient(135deg, var(--purple-color) 0%, #7c3aed 100%);
            color: white;
            padding: 30px;
            border-radius: 16px;
            margin-bottom: 30px;
        }

        .filter-tabs {
            background: white;
            border-radius: 12px;
            padding: 8px;
            margin-bottom: 24px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.05);
        }

        .filter-tabs .nav-link {
            border: none;
            border-radius: 8px;
            padding: 12px 20px;
            color: #64748b;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .filter-tabs .nav-link.active {
            background: var(--primary-color);
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

        .workflow-progress {
            display: flex;
            align-items: center;
            margin: 16px 0;
            padding: 16px;
            background: #f8fafc;
            border-radius: 12px;
        }

        .progress-step {
            width: 32px;
            height: 32px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #e2e8f0;
            color: #64748b;
            font-size: 12px;
            position: relative;
            z-index: 2;
        }

        .progress-step.active {
            background: var(--primary-color);
            color: white;
        }

        .progress-step.completed {
            background: var(--success-color);
            color: white;
        }

        .progress-line {
            flex: 1;
            height: 2px;
            background: #e2e8f0;
            margin: 0 8px;
        }

        .progress-line.completed {
            background: var(--success-color);
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
                        <small class="text-white-50">Event Management</small>
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
                    <!-- Header -->
                    <div class="header-section">
                        <div class="row align-items-center">
                            <div class="col">
                                <h1 class="mb-2">
                                    <i class="fas fa-calendar-alt me-3"></i>Event Presenter
                                </h1>
                                <p class="mb-0 opacity-75">
                                    Temukan dan ikuti event yang sesuai dengan bidang keahlian Anda.
                                </p>
                            </div>
                            <div class="col-auto">
                                <div class="text-end">
                                    <div class="d-flex align-items-center text-white-50">
                                        <i class="fas fa-clock me-2"></i>
                                        <span id="currentTime"><?= date('H:i:s') ?> WIB</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Tabs -->
                    <div class="filter-tabs">
                        <ul class="nav nav-pills justify-content-center" id="eventTabs">
                            <li class="nav-item">
                                <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#all-events">
                                    <i class="fas fa-list me-2"></i>Semua Event
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#open-registration">
                                    <i class="fas fa-door-open me-2"></i>Pendaftaran Terbuka
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#my-events">
                                    <i class="fas fa-user-check me-2"></i>Event Saya
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#upcoming">
                                    <i class="fas fa-calendar-day me-2"></i>Mendatang
                                </button>
                            </li>
                        </ul>
                    </div>

                    <!-- Tab Content -->
                    <div class="tab-content" id="eventTabsContent">
                        <!-- All Events -->
                        <div class="tab-pane fade show active" id="all-events">
                            <?php if (!empty($events)): ?>
                                <?php foreach ($events as $event): ?>
                                <div class="event-card" data-event-id="<?= $event['id'] ?>" data-status="<?= $event['user_status'] ?>">
                                    <!-- Status Badge -->
                                    <span class="status-badge status-<?= $event['user_status'] ?>">
                                        <?= ucfirst(str_replace('_', ' ', $event['user_status'])) ?>
                                    </span>

                                    <!-- Event Title & Description -->
                                    <div class="mb-3" style="margin-right: 120px;">
                                        <h4 class="mb-2"><?= esc($event['title']) ?></h4>
                                        <?php if (!empty($event['description'])): ?>
                                        <p class="text-muted mb-0"><?= esc(substr($event['description'], 0, 150)) ?><?= strlen($event['description']) > 150 ? '...' : '' ?></p>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Event Meta Information -->
                                    <div class="event-meta">
                                        <div class="meta-item">
                                            <i class="fas fa-calendar"></i>
                                            <span><?= $event['formatted_times']['event_date'] ?? date('d F Y', strtotime($event['event_date'])) ?></span>
                                        </div>
                                        <div class="meta-item">
                                            <i class="fas fa-clock"></i>
                                            <span><?= $event['formatted_times']['event_time'] ?? date('H:i', strtotime($event['event_time'])) ?> WIB</span>
                                        </div>
                                        <div class="meta-item">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <span><?= $event['format'] === 'online' ? 'Online' : ($event['format'] === 'offline' ? ($event['location'] ?: 'TBA') : 'Hybrid') ?></span>
                                        </div>
                                        <div class="meta-item">
                                            <i class="fas fa-users"></i>
                                            <span>Presenter Only</span>
                                        </div>
                                    </div>

                                    <!-- Pricing -->
                                    <div class="price-badge <?= $event['presenter_price'] == 0 ? 'free-badge' : '' ?>">
                                        <?php if ($event['presenter_price'] == 0): ?>
                                            <i class="fas fa-gift me-2"></i>GRATIS
                                        <?php else: ?>
                                            <i class="fas fa-tag me-2"></i>Rp <?= number_format($event['presenter_price'], 0, ',', '.') ?>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Workflow Progress for Registered Events -->
                                    <?php if ($event['user_registered']): ?>
                                    <div class="workflow-progress">
                                        <div class="progress-step <?= !empty($event['abstract_data']) ? 'completed' : 'active' ?>">
                                            <i class="fas fa-file-alt"></i>
                                        </div>
                                        <div class="progress-line <?= !empty($event['abstract_data']) && $event['abstract_data']['status'] === 'diterima' ? 'completed' : '' ?>"></div>
                                        
                                        <div class="progress-step <?= !empty($event['abstract_data']) && $event['abstract_data']['status'] === 'diterima' ? 'completed' : (!empty($event['abstract_data']) ? 'active' : '') ?>">
                                            <i class="fas fa-search"></i>
                                        </div>
                                        <div class="progress-line <?= !empty($event['payment_data']) ? 'completed' : '' ?>"></div>
                                        
                                        <div class="progress-step <?= !empty($event['payment_data']) && $event['payment_data']['status'] === 'verified' ? 'completed' : (!empty($event['payment_data']) ? 'active' : '') ?>">
                                            <i class="fas fa-credit-card"></i>
                                        </div>
                                        <div class="progress-line <?= !empty($event['payment_data']) && $event['payment_data']['status'] === 'verified' ? 'completed' : '' ?>"></div>
                                        
                                        <div class="progress-step <?= !empty($event['payment_data']) && $event['payment_data']['status'] === 'verified' ? 'completed' : '' ?>">
                                            <i class="fas fa-check"></i>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <!-- Deadlines & Warnings -->
                                    <?php if (isset($event['time_info'])): ?>
                                        <?php if ($event['time_info']['days_until_registration_deadline'] !== null && $event['time_info']['days_until_registration_deadline'] <= 3 && $event['time_info']['days_until_registration_deadline'] >= 0): ?>
                                        <div class="deadline-warning">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            <span>Pendaftaran berakhir dalam <?= $event['time_info']['days_until_registration_deadline'] ?> hari</span>
                                        </div>
                                        <?php endif; ?>

                                        <?php if ($event['time_info']['days_until_abstract_deadline'] !== null && $event['time_info']['days_until_abstract_deadline'] <= 7 && $event['time_info']['days_until_abstract_deadline'] >= 0): ?>
                                        <div class="deadline-info">
                                            <i class="fas fa-info-circle"></i>
                                            <span>Deadline abstrak dalam <?= $event['time_info']['days_until_abstract_deadline'] ?> hari</span>
                                        </div>
                                        <?php endif; ?>
                                    <?php endif; ?>

                                    <!-- Action Buttons -->
                                    <div class="action-buttons">
                                        <a href="<?= site_url('presenter/events/detail/' . $event['id']) ?>" 
                                           class="btn btn-outline-primary">
                                            <i class="fas fa-eye me-2"></i>Lihat Detail
                                        </a>

                                        <?php if ($event['user_status'] === 'not_registered' && $event['registration_open']): ?>
                                            <a href="<?= site_url('presenter/events/register/' . $event['id']) ?>" 
                                               class="btn-primary-gradient">
                                                <i class="fas fa-user-plus me-2"></i>Daftar Sekarang
                                            </a>
                                        <?php elseif ($event['user_status'] === 'registered'): ?>
                                            <a href="<?= site_url('presenter/abstrak?event_id=' . $event['id']) ?>" 
                                               class="btn-primary-gradient">
                                                <i class="fas fa-file-upload me-2"></i>Upload Abstrak
                                            </a>
                                        <?php elseif ($event['user_status'] === 'abstract_accepted'): ?>
                                            <a href="<?= site_url('presenter/pembayaran/create/' . $event['id']) ?>" 
                                               class="btn-primary-gradient">
                                                <i class="fas fa-credit-card me-2"></i>Lakukan Pembayaran
                                            </a>
                                        <?php elseif ($event['user_status'] === 'payment_verified'): ?>
                                            <a href="<?= site_url('presenter/absensi') ?>" 
                                               class="btn btn-success">
                                                <i class="fas fa-qrcode me-2"></i>Akses QR Scanner
                                            </a>
                                        <?php endif; ?>

                                        <?php if ($event['user_registered']): ?>
                                            <a href="<?= site_url('presenter/dashboard') ?>" 
                                               class="btn btn-outline-secondary">
                                                <i class="fas fa-chart-line me-2"></i>Lihat Progress
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-calendar-times"></i>
                                    <h4>Belum Ada Event Tersedia</h4>
                                    <p>Saat ini belum ada event yang dapat diikuti. Silakan cek kembali nanti.</p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Open Registration -->
                        <div class="tab-pane fade" id="open-registration">
                            <?php 
                            $openEvents = array_filter($events ?? [], function($event) {
                                return $event['registration_open'] && $event['user_status'] === 'not_registered';
                            });
                            ?>
                            
                            <?php if (!empty($openEvents)): ?>
                                <?php foreach ($openEvents as $event): ?>
                                <!-- Same event card structure as above -->
                                <div class="event-card">
                                    <!-- Abbreviated version for open registration -->
                                    <h4><?= esc($event['title']) ?></h4>
                                    <div class="event-meta">
                                        <div class="meta-item">
                                            <i class="fas fa-calendar"></i>
                                            <span><?= date('d F Y', strtotime($event['event_date'])) ?></span>
                                        </div>
                                        <div class="meta-item">
                                            <i class="fas fa-tag"></i>
                                            <span>Rp <?= number_format($event['presenter_price'], 0, ',', '.') ?></span>
                                        </div>
                                    </div>
                                    <div class="action-buttons">
                                        <a href="<?= site_url('presenter/events/detail/' . $event['id']) ?>" 
                                           class="btn btn-outline-primary">Detail</a>
                                        <a href="<?= site_url('presenter/events/register/' . $event['id']) ?>" 
                                           class="btn-primary-gradient">Daftar</a>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-door-closed"></i>
                                    <h4>Tidak Ada Pendaftaran Terbuka</h4>
                                    <p>Saat ini tidak ada event dengan pendaftaran terbuka.</p>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- My Events -->
                        <div class="tab-pane fade" id="my-events">
                            <?php 
                            $myEvents = array_filter($events ?? [], function($event) {
                                return $event['user_registered'];
                            });
                            ?>
                            
                            <?php if (!empty($myEvents)): ?>
                                <?php foreach ($myEvents as $event): ?>
                                <!-- Show registered events with progress -->
                                <div class="event-card">
                                    <span class="status-badge status-<?= $event['user_status'] ?>">
                                        <?= ucfirst(str_replace('_', ' ', $event['user_status'])) ?>
                                    </span>
                                    
                                    <h4 style="margin-right: 120px;"><?= esc($event['title']) ?></h4>
                                    
                                    <div class="workflow-progress">
                                        <!-- Progress steps as shown above -->
                                    </div>
                                    
                                    <div class="action-buttons">
                                        <a href="<?= site_url('presenter/events/detail/' . $event['id']) ?>" 
                                           class="btn btn-outline-primary">Detail</a>
                                        <a href="<?= site_url('presenter/dashboard') ?>" 
                                           class="btn-primary-gradient">Kelola</a>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-user-times"></i>
                                    <h4>Belum Ada Event Terdaftar</h4>
                                    <p>Anda belum terdaftar di event manapun. Daftar sekarang untuk mulai berpartisipasi!</p>
                                    <a href="#all-events" data-bs-toggle="pill" class="btn-primary-gradient">
                                        Lihat Event Tersedia
                                    </a>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Upcoming Events -->
                        <div class="tab-pane fade" id="upcoming">
                            <?php 
                            $upcomingEvents = array_filter($events ?? [], function($event) {
                                return strtotime($event['event_date']) > time() && 
                                       strtotime($event['event_date']) <= strtotime('+30 days');
                            });
                            ?>
                            
                            <?php if (!empty($upcomingEvents)): ?>
                                <?php foreach ($upcomingEvents as $event): ?>
                                <!-- Show upcoming events -->
                                <div class="event-card">
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <h4><?= esc($event['title']) ?></h4>
                                        <div class="text-end">
                                            <?php 
                                            $daysUntil = ceil((strtotime($event['event_date']) - time()) / (60 * 60 * 24));
                                            ?>
                                            <div class="badge bg-info text-dark">
                                                <?= $daysUntil ?> hari lagi
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="event-meta">
                                        <div class="meta-item">
                                            <i class="fas fa-calendar"></i>
                                            <span><?= date('d F Y', strtotime($event['event_date'])) ?></span>
                                        </div>
                                        <div class="meta-item">
                                            <i class="fas fa-clock"></i>
                                            <span><?= date('H:i', strtotime($event['event_time'])) ?> WIB</span>
                                        </div>
                                    </div>
                                    
                                    <div class="action-buttons">
                                        <a href="<?= site_url('presenter/events/detail/' . $event['id']) ?>" 
                                           class="btn-primary-gradient">Lihat Detail</a>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-calendar-check"></i>
                                    <h4>Tidak Ada Event Mendatang</h4>
                                    <p>Tidak ada event dalam 30 hari ke depan.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container position-fixed top-0 end-0 p-3" id="toastContainer"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Update clock
            updateClock();
            setInterval(updateClock, 1000);

            // Show flash messages
            showFlashMessages();

            // Add event listeners for tab switching
            setupTabFiltering();

            // Setup real-time event status updates
            setInterval(updateEventStatuses, 60000);

            // Add hover effects and interactions
            setupEventCardInteractions();
        });

        function updateClock() {
            const now = new Date();
            const timeString = now.toLocaleTimeString('id-ID', {
                timeZone: 'Asia/Jakarta',
                hour12: false
            });
            document.getElementById('currentTime').textContent = timeString + ' WIB';
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

        function setupTabFiltering() {
            const tabs = document.querySelectorAll('#eventTabs button');
            tabs.forEach(tab => {
                tab.addEventListener('shown.bs.tab', function(e) {
                    const targetTab = e.target.dataset.bsTarget;
                    console.log('Switched to tab:', targetTab);
                    // Additional filtering logic can be added here if needed
                });
            });
        }

        function updateEventStatuses() {
            // Refresh event statuses via AJAX
            fetch('<?= site_url('presenter/events/refreshStatuses') ?>', {
                method: 'GET',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update event cards with new status information
                    updateEventCards(data.events);
                }
            })
            .catch(error => {
                console.log('Status update error:', error);
            });
        }

        function updateEventCards(events) {
            events.forEach(event => {
                const card = document.querySelector(`[data-event-id="${event.id}"]`);
                if (card) {
                    // Update status badge
                    const statusBadge = card.querySelector('.status-badge');
                    if (statusBadge) {
                        statusBadge.className = `status-badge status-${event.user_status}`;
                        statusBadge.textContent = event.user_status.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase());
                    }

                    // Update action buttons if needed
                    updateActionButtons(card, event);
                }
            });
        }

        function updateActionButtons(card, event) {
            const actionButtons = card.querySelector('.action-buttons');
            if (!actionButtons) return;

            // This would contain logic to update buttons based on event status
            // Implementation depends on specific business rules
        }

        function setupEventCardInteractions() {
            const eventCards = document.querySelectorAll('.event-card');
            
            eventCards.forEach(card => {
                // Add click to view detail functionality
                card.addEventListener('click', function(e) {
                    // Only trigger if not clicking on a button or link
                    if (!e.target.closest('a, button')) {
                        const eventId = card.dataset.eventId;
                        if (eventId) {
                            window.location.href = `<?= site_url('presenter/events/detail/') ?>${eventId}`;
                        }
                    }
                });

                // Add hover effects
                card.addEventListener('mouseenter', function() {
                    card.style.cursor = 'pointer';
                });
            });
        }

        // Utility functions
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('id-ID', {
                day: 'numeric',
                month: 'long',
                year: 'numeric'
            });
        }

        function formatTime(timeString) {
            const time = new Date('1970-01-01T' + timeString);
            return time.toLocaleTimeString('id-ID', {
                hour: '2-digit',
                minute: '2-digit',
                hour12: false
            });
        }

        // Add smooth scrolling for better UX
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
    </script>
</body>
</html>