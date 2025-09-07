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

        .event-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }

        .event-card.available::before { background: var(--success-color); }
        .event-card.participating::before { background: var(--primary-color); }
        .event-card.completed::before { background: var(--info-color); }
        .event-card.blocked::before { background: var(--secondary-color); }

        .event-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.15);
        }

        .status-badge {
            position: absolute;
            top: 16px;
            right: 16px;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            color: white;
        }

        .status-badge.need_abstract { background: var(--warning-color); }
        .status-badge.abstract_pending { background: var(--info-color); }
        .status-badge.abstract_revision { background: var(--warning-color); }
        .status-badge.abstract_rejected { background: var(--danger-color); }
        .status-badge.need_payment { background: var(--success-color); }
        .status-badge.payment_pending { background: var(--info-color); }
        .status-badge.payment_rejected { background: var(--danger-color); }
        .status-badge.completed { background: var(--success-color); }

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
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        }

        .filter-tab {
            background: transparent;
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            color: var(--secondary-color);
            transition: all 0.3s ease;
        }

        .filter-tab.active {
            background: var(--primary-color);
            color: white;
        }

        .filter-tab:hover:not(.active) {
            background: #f1f5f9;
        }

        .workflow-status {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 16px 0;
        }

        .workflow-dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: #e2e8f0;
        }

        .workflow-dot.completed { background: var(--success-color); }
        .workflow-dot.current { background: var(--primary-color); }
        .workflow-dot.blocked { background: var(--danger-color); }

        .workflow-line {
            flex: 1;
            height: 2px;
            background: #e2e8f0;
        }

        .workflow-line.completed { background: var(--success-color); }

        .event-meta {
            display: flex;
            gap: 20px;
            margin: 16px 0;
            flex-wrap: wrap;
        }

        .meta-item {
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--secondary-color);
            font-size: 14px;
        }

        .action-button {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--info-color) 100%);
            border: none;
            border-radius: 12px;
            padding: 12px 24px;
            color: white;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s ease;
        }

        .action-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.3);
            color: white;
        }

        .action-button.warning {
            background: linear-gradient(135deg, var(--warning-color) 0%, #d97706 100%);
        }

        .action-button.danger {
            background: linear-gradient(135deg, var(--danger-color) 0%, #dc2626 100%);
        }

        .action-button.success {
            background: linear-gradient(135deg, var(--success-color) 0%, #059669 100%);
        }

        .action-button.secondary {
            background: linear-gradient(135deg, var(--secondary-color) 0%, #475569 100%);
        }

        .action-button:disabled {
            background: #e2e8f0;
            color: #94a3b8;
            cursor: not-allowed;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: var(--secondary-color);
        }

        .deadline-warning {
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.3);
            border-radius: 8px;
            padding: 12px 16px;
            margin: 12px 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .deadline-critical {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .stats-bar {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 24px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
        }

        .stat-item {
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 4px;
        }

        .stat-label {
            color: var(--secondary-color);
            font-size: 14px;
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
                        <small class="text-white-50">Dashboard</small>
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
                                    <i class="fas fa-calendar-alt me-3"></i>Event Tersedia
                                </h1>
                                <p class="mb-0 opacity-75">
                                    Kelola partisipasi Anda dalam berbagai event SNIA.
                                </p>
                            </div>
                            <div class="col-auto">
                                <div class="text-end">
                                    <small class="opacity-75 d-block">Total Event</small>
                                    <strong class="h4"><?= $total_events ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Bar -->
                    <div class="stats-bar">
                        <div class="row">
                            <div class="col-3">
                                <div class="stat-item">
                                    <div class="stat-number text-success"><?= $available_count ?></div>
                                    <div class="stat-label">Tersedia</div>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="stat-item">
                                    <div class="stat-number text-primary"><?= $participating_count ?></div>
                                    <div class="stat-label">Sedang Proses</div>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="stat-item">
                                    <div class="stat-number text-info"><?= $completed_count ?></div>
                                    <div class="stat-label">Selesai</div>
                                </div>
                            </div>
                            <div class="col-3">
                                <div class="stat-item">
                                    <div class="stat-number text-secondary"><?= $total_events - $available_count - $participating_count - $completed_count ?></div>
                                    <div class="stat-label">Tidak Dapat</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filter Tabs -->
                    <div class="filter-tabs">
                        <button class="filter-tab active" data-filter="all">
                            Semua Event (<?= $total_events ?>)
                        </button>
                        <button class="filter-tab" data-filter="available">
                            Tersedia (<?= $available_count ?>)
                        </button>
                        <button class="filter-tab" data-filter="participating">
                            Sedang Proses (<?= $participating_count ?>)
                        </button>
                        <button class="filter-tab" data-filter="completed">
                            Selesai (<?= $completed_count ?>)
                        </button>
                    </div>

                    <!-- Events List -->
                    <div class="events-container">
                        <?php if (!empty($events)): ?>
                            <?php foreach ($events as $event): ?>
                                <div class="event-card <?= $event['is_completed'] ? 'completed' : ($event['can_participate'] ? 'available' : ($event['has_abstract'] || $event['has_payment'] ? 'participating' : 'blocked')) ?>" 
                                     data-filter-type="<?= $event['is_completed'] ? 'completed' : ($event['can_participate'] ? 'available' : ($event['has_abstract'] || $event['has_payment'] ? 'participating' : 'blocked')) ?>">
                                    
                                    <!-- Status Badge -->
                                    <div class="status-badge <?= $event['workflow_status'] ?>">
                                        <?php
                                        $statusText = [
                                            'need_abstract' => 'Perlu Abstrak',
                                            'abstract_pending' => 'Review Abstrak',
                                            'abstract_revision' => 'Perlu Revisi',
                                            'abstract_rejected' => 'Abstrak Ditolak',
                                            'need_payment' => 'Perlu Bayar',
                                            'payment_pending' => 'Tunggu Verifikasi',
                                            'payment_rejected' => 'Bayar Ditolak',
                                            'completed' => 'Selesai'
                                        ];
                                        echo $statusText[$event['workflow_status']] ?? 'Unknown';
                                        ?>
                                    </div>

                                    <!-- Event Header -->
                                    <div class="d-flex justify-content-between align-items-start mb-3">
                                        <div>
                                            <h5 class="mb-2">
                                                <a href="<?= site_url('presenter/events/detail/' . $event['id']) ?>" 
                                                   class="text-decoration-none text-dark">
                                                    <?= esc($event['title']) ?>
                                                </a>
                                            </h5>
                                            <div class="event-meta">
                                                <div class="meta-item">
                                                    <i class="fas fa-calendar"></i>
                                                    <span><?= $event['formatted_date'] ?></span>
                                                </div>
                                                <div class="meta-item">
                                                    <i class="fas fa-clock"></i>
                                                    <span><?= $event['formatted_time'] ?></span>
                                                </div>
                                                <div class="meta-item">
                                                    <i class="fas fa-map-marker-alt"></i>
                                                    <span><?= $event['format'] === 'offline' ? esc($event['location']) : ucfirst($event['format']) ?></span>
                                                </div>
                                                <div class="meta-item">
                                                    <i class="fas fa-money-bill-wave"></i>
                                                    <span>Rp <?= number_format($event['presenter_price'], 0, ',', '.') ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Event Description -->
                                    <?php if (!empty($event['description'])): ?>
                                        <p class="text-muted mb-3">
                                            <?= esc(substr($event['description'], 0, 150)) ?><?= strlen($event['description']) > 150 ? '...' : '' ?>
                                        </p>
                                    <?php endif; ?>

                                    <!-- Workflow Progress -->
                                    <div class="workflow-status">
                                        <?php
                                        $steps = ['need_abstract', 'abstract_pending', 'need_payment', 'payment_pending', 'completed'];
                                        $currentStepIndex = array_search($event['workflow_status'], $steps);
                                        if ($currentStepIndex === false) $currentStepIndex = 0;
                                        
                                        foreach ($steps as $index => $step):
                                            $isCompleted = $index < $currentStepIndex || $event['workflow_status'] === 'completed';
                                            $isCurrent = $index === $currentStepIndex && $event['workflow_status'] !== 'completed';
                                            $isBlocked = in_array($event['workflow_status'], ['abstract_rejected', 'payment_rejected']) && $index === $currentStepIndex;
                                        ?>
                                            <div class="workflow-dot <?= $isCompleted ? 'completed' : ($isCurrent ? 'current' : ($isBlocked ? 'blocked' : '')) ?>"></div>
                                            <?php if ($index < count($steps) - 1): ?>
                                                <div class="workflow-line <?= $isCompleted ? 'completed' : '' ?>"></div>
                                            <?php endif; ?>
                                        <?php endforeach; ?>
                                    </div>

                                    <!-- Deadline Warnings -->
                                    <?php if ($event['days_until'] <= 7 && $event['days_until'] > 0): ?>
                                        <div class="deadline-warning <?= $event['days_until'] <= 3 ? 'deadline-critical' : '' ?>">
                                            <i class="fas fa-exclamation-triangle text-warning"></i>
                                            <span>
                                                <strong>Event dimulai dalam <?= $event['days_until'] ?> hari!</strong>
                                                <?php if (!$event['abstract_submission_open']): ?>
                                                    - Submission abstrak sudah ditutup.
                                                <?php elseif (!$event['registration_open']): ?>
                                                    - Registrasi sudah ditutup.
                                                <?php endif; ?>
                                            </span>
                                        </div>
                                    <?php endif; ?>

                                    <!-- Actions -->
                                    <div class="d-flex gap-2 align-items-center">
                                        <a href="<?= site_url('presenter/events/detail/' . $event['id']) ?>" 
                                           class="btn btn-outline-primary">
                                            <i class="fas fa-eye me-2"></i>Detail
                                        </a>

                                        <?php if ($event['can_participate']): ?>
                                            <?php
                                            $actionClass = 'primary';
                                            $actionText = 'Lanjutkan';
                                            $actionIcon = 'fas fa-arrow-right';
                                            $actionUrl = site_url('presenter/events/detail/' . $event['id']);

                                            switch ($event['workflow_status']) {
                                                case 'need_abstract':
                                                    $actionText = 'Upload Abstrak';
                                                    $actionIcon = 'fas fa-upload';
                                                    $actionUrl = site_url('presenter/abstrak?event_id=' . $event['id']);
                                                    break;
                                                case 'abstract_revision':
                                                    $actionText = 'Revisi Abstrak';
                                                    $actionIcon = 'fas fa-edit';
                                                    $actionClass = 'warning';
                                                    $actionUrl = site_url('presenter/abstrak/detail/' . $event['abstract']['id_abstrak']);
                                                    break;
                                                case 'abstract_rejected':
                                                    $actionText = 'Submit Ulang';
                                                    $actionIcon = 'fas fa-redo';
                                                    $actionClass = 'danger';
                                                    $actionUrl = site_url('presenter/abstrak?event_id=' . $event['id']);
                                                    break;
                                                case 'need_payment':
                                                    $actionText = 'Bayar Sekarang';
                                                    $actionIcon = 'fas fa-credit-card';
                                                    $actionClass = 'success';
                                                    $actionUrl = site_url('presenter/pembayaran/create/' . $event['id']);
                                                    break;
                                                case 'payment_rejected':
                                                    $actionText = 'Bayar Ulang';
                                                    $actionIcon = 'fas fa-redo';
                                                    $actionClass = 'danger';
                                                    $actionUrl = site_url('presenter/pembayaran/create/' . $event['id']);
                                                    break;
                                            }
                                            ?>
                                            <a href="<?= $actionUrl ?>" class="action-button <?= $actionClass ?>">
                                                <i class="<?= $actionIcon ?> me-2"></i><?= $actionText ?>
                                            </a>
                                        <?php elseif ($event['is_completed']): ?>
                                            <a href="<?= site_url('presenter/absensi') ?>" class="action-button success">
                                                <i class="fas fa-star me-2"></i>Akses Fitur
                                            </a>
                                        <?php else: ?>
                                            <button class="action-button secondary" disabled>
                                                <i class="fas fa-lock me-2"></i>Tidak Tersedia
                                            </button>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="empty-state">
                                <i class="fas fa-calendar-times fa-4x mb-3"></i>
                                <h5>Belum Ada Event Tersedia</h5>
                                <p>Saat ini belum ada event yang dapat Anda ikuti sebagai presenter.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Filter functionality
            const filterTabs = document.querySelectorAll('.filter-tab');
            const eventCards = document.querySelectorAll('.event-card');

            filterTabs.forEach(tab => {
                tab.addEventListener('click', function() {
                    const filter = this.dataset.filter;
                    
                    // Update active tab
                    filterTabs.forEach(t => t.classList.remove('active'));
                    this.classList.add('active');
                    
                    // Filter events
                    eventCards.forEach(card => {
                        if (filter === 'all') {
                            card.style.display = 'block';
                        } else {
                            const cardType = card.dataset.filterType;
                            card.style.display = cardType === filter ? 'block' : 'none';
                        }
                    });
                });
            });

            // Animate cards on load
            eventCards.forEach((card, index) => {
                card.style.opacity = '0';
                card.style.transform = 'translateY(20px)';
                
                setTimeout(() => {
                    card.style.transition = 'all 0.3s ease';
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 100);
            });

            // Update countdown timers every minute
            setInterval(updateCountdowns, 60000);

            function updateCountdowns() {
                // Update any countdown elements if needed
                console.log('Updating countdowns...');
            }

            // Add hover effects
            eventCards.forEach(card => {
                card.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-3px)';
                });
                
                card.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                });
            });
        });

        // Utility function to show toast notifications
        function showToast(message, type = 'info') {
            // Create toast element
            const toast = document.createElement('div');
            toast.className = `toast align-items-center text-white bg-${type} border-0`;
            toast.setAttribute('role', 'alert');
            toast.innerHTML = `
                <div class="d-flex">
                    <div class="toast-body">${message}</div>
                    <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
                </div>
            `;
            
            // Add to page
            document.body.appendChild(toast);
            
            // Show toast
            const bsToast = new bootstrap.Toast(toast);
            bsToast.show();
            
            // Remove from DOM after hide
            toast.addEventListener('hidden.bs.toast', () => {
                document.body.removeChild(toast);
            });
        }

        // Check for flash messages
        const urlParams = new URLSearchParams(window.location.search);
        const success = urlParams.get('success');
        const error = urlParams.get('error');

        if (success) {
            showToast(success, 'success');
        }
        if (error) {
            showToast(error, 'danger');
        }
    </script>
</body>
</html>