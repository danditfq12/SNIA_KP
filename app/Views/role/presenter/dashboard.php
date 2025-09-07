<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Presenter - SNIA</title>
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

        .workflow-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            border: 1px solid rgba(255,255,255,0.2);
            margin-bottom: 24px;
            transition: all 0.3s ease;
        }

        .workflow-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.12);
        }

        .workflow-progress {
            display: flex;
            align-items: center;
            margin: 20px 0;
        }

        .progress-step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #e2e8f0;
            color: #64748b;
            font-weight: bold;
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
            height: 3px;
            background: #e2e8f0;
            margin: 0 10px;
            position: relative;
        }

        .progress-line.completed {
            background: var(--success-color);
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 20px;
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
        }

        .stat-card.primary::before { background: var(--primary-color); }
        .stat-card.success::before { background: var(--success-color); }
        .stat-card.warning::before { background: var(--warning-color); }
        .stat-card.info::before { background: var(--info-color); }

        .stat-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.15);
        }

        .notification-card {
            background: white;
            border-radius: 12px;
            border-left: 4px solid;
            padding: 16px;
            margin-bottom: 12px;
            transition: all 0.2s ease;
        }

        .notification-card:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .notification-card.warning { border-left-color: var(--warning-color); }
        .notification-card.danger { border-left-color: var(--danger-color); }
        .notification-card.success { border-left-color: var(--success-color); }
        .notification-card.info { border-left-color: var(--info-color); }

        .activity-item {
            padding: 16px;
            border-bottom: 1px solid #e2e8f0;
            transition: all 0.2s ease;
        }

        .activity-item:hover {
            background: #f8fafc;
        }

        .activity-item:last-child {
            border-bottom: none;
        }

        .badge-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .header-section {
            background: linear-gradient(135deg, var(--purple-color) 0%, #7c3aed 100%);
            color: white;
            padding: 30px;
            border-radius: 16px;
            margin-bottom: 30px;
        }

        .action-button {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--info-color) 100%);
            border: none;
            border-radius: 12px;
            padding: 12px 24px;
            color: white;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .action-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.3);
            color: white;
        }

        .event-status-badge {
            position: absolute;
            top: 16px;
            right: 16px;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
        }

        .step-label {
            font-size: 12px;
            text-align: center;
            margin-top: 8px;
            color: #64748b;
        }

        .step-label.active {
            color: var(--primary-color);
            font-weight: 600;
        }

        .step-label.completed {
            color: var(--success-color);
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
                            <i class="fas fa-chalkboard-teacher me-2"></i>
                            SNIA Presenter
                        </h4>
                        <small class="text-white-50">Dashboard</small>
                    </div>
                    
                    <nav class="nav flex-column px-3">
                        <a class="nav-link active" href="<?= site_url('presenter/dashboard') ?>">
                            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                        </a>
                        <a class="nav-link" href="<?= site_url('presenter/events') ?>">
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
                                    <i class="fas fa-chart-line me-3"></i>Dashboard Presenter
                                </h1>
                                <p class="mb-0 opacity-75">
                                    Selamat datang, <strong><?= session('nama_lengkap') ?? 'Presenter' ?></strong>! 
                                    Kelola partisipasi event Anda dengan mudah.
                                </p>
                            </div>
                            <div class="col-auto">
                                <div class="text-end">
                                    <small class="opacity-75 d-block">Terakhir login</small>
                                    <strong><?= date('d F Y, H:i') ?></strong>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notifications -->
                    <?php if (!empty($notifications)): ?>
                    <div class="mb-4">
                        <h5 class="mb-3">
                            <i class="fas fa-bell me-2"></i>Notifikasi Penting
                        </h5>
                        <?php foreach ($notifications as $notification): ?>
                        <div class="notification-card <?= $notification['type'] ?>">
                            <div class="d-flex align-items-start">
                                <div class="me-3">
                                    <i class="fas fa-<?= $notification['type'] === 'warning' ? 'exclamation-triangle' : ($notification['type'] === 'danger' ? 'times-circle' : ($notification['type'] === 'success' ? 'check-circle' : 'info-circle')) ?> text-<?= $notification['type'] ?>"></i>
                                </div>
                                <div class="flex-grow-1">
                                    <h6 class="mb-1"><?= esc($notification['title']) ?></h6>
                                    <p class="mb-2 text-muted"><?= esc($notification['message']) ?></p>
                                    <a href="<?= $notification['action_url'] ?>" class="btn btn-sm btn-outline-<?= $notification['type'] ?>">
                                        <?= esc($notification['action_text']) ?>
                                    </a>
                                </div>
                                <small class="text-muted"><?= date('d M', strtotime($notification['date'])) ?></small>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Event Workflow Progress -->
                    <div class="mb-4">
                        <h5 class="mb-3">
                            <i class="fas fa-tasks me-2"></i>Progress Event
                        </h5>

                        <?php if (!empty($workflow_data)): ?>
                            <?php foreach ($workflow_data as $workflow): ?>
                            <div class="workflow-card" data-event-id="<?= $workflow['event_id'] ?>">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div>
                                        <h6 class="mb-1"><?= esc($workflow['event_title']) ?></h6>
                                        <small class="text-muted">
                                            <i class="fas fa-calendar me-1"></i>
                                            <?= date('d F Y', strtotime($workflow['event_date'])) ?>
                                        </small>
                                    </div>
                                    <span class="event-status-badge bg-<?= $workflow['status'] === 'completed' ? 'success' : ($workflow['can_proceed'] ? 'primary' : 'secondary') ?>">
                                        Step <?= $workflow['step'] ?>/5
                                    </span>
                                </div>

                                <!-- Progress Bar -->
                                <div class="workflow-progress">
                                    <div class="text-center">
                                        <div class="progress-step <?= $workflow['step'] >= 1 ? 'completed' : '' ?>">
                                            <i class="fas fa-file-alt"></i>
                                        </div>
                                        <div class="step-label <?= $workflow['step'] >= 1 ? 'completed' : '' ?>">Abstrak</div>
                                    </div>
                                    <div class="progress-line <?= $workflow['step'] >= 2 ? 'completed' : '' ?>"></div>
                                    
                                    <div class="text-center">
                                        <div class="progress-step <?= $workflow['step'] >= 2 ? 'completed' : ($workflow['step'] === 2 ? 'active' : '') ?>">
                                            <i class="fas fa-search"></i>
                                        </div>
                                        <div class="step-label <?= $workflow['step'] >= 2 ? 'completed' : ($workflow['step'] === 2 ? 'active' : '') ?>">Review</div>
                                    </div>
                                    <div class="progress-line <?= $workflow['step'] >= 3 ? 'completed' : '' ?>"></div>
                                    
                                    <div class="text-center">
                                        <div class="progress-step <?= $workflow['step'] >= 3 ? 'completed' : ($workflow['step'] === 3 ? 'active' : '') ?>">
                                            <i class="fas fa-credit-card"></i>
                                        </div>
                                        <div class="step-label <?= $workflow['step'] >= 3 ? 'completed' : ($workflow['step'] === 3 ? 'active' : '') ?>">Bayar</div>
                                    </div>
                                    <div class="progress-line <?= $workflow['step'] >= 4 ? 'completed' : '' ?>"></div>
                                    
                                    <div class="text-center">
                                        <div class="progress-step <?= $workflow['step'] >= 4 ? 'completed' : ($workflow['step'] === 4 ? 'active' : '') ?>">
                                            <i class="fas fa-check"></i>
                                        </div>
                                        <div class="step-label <?= $workflow['step'] >= 4 ? 'completed' : ($workflow['step'] === 4 ? 'active' : '') ?>">Verifikasi</div>
                                    </div>
                                    <div class="progress-line <?= $workflow['step'] >= 5 ? 'completed' : '' ?>"></div>
                                    
                                    <div class="text-center">
                                        <div class="progress-step <?= $workflow['step'] >= 5 ? 'completed' : '' ?>">
                                            <i class="fas fa-star"></i>
                                        </div>
                                        <div class="step-label <?= $workflow['step'] >= 5 ? 'completed' : '' ?>">Selesai</div>
                                    </div>
                                </div>

                                <!-- Status Message -->
                                <div class="mb-3">
                                    <div class="alert alert-<?= $workflow['status'] === 'completed' ? 'success' : ($workflow['can_proceed'] ? 'primary' : 'warning') ?> mb-0">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <?= esc($workflow['message']) ?>
                                    </div>
                                </div>

                                <!-- Action Button -->
                                <?php if ($workflow['can_proceed'] && $workflow['status'] !== 'completed'): ?>
                                <div class="text-center">
                                    <a href="<?= $workflow['next_url'] ?>" class="action-button">
                                        <i class="fas fa-arrow-right me-2"></i>
                                        <?= esc($workflow['next_action']) ?>
                                    </a>
                                </div>
                                <?php elseif ($workflow['status'] === 'completed'): ?>
                                <div class="text-center">
                                    <a href="<?= $workflow['next_url'] ?>" class="btn btn-success btn-lg">
                                        <i class="fas fa-play me-2"></i>
                                        Akses Fitur Event
                                    </a>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="fas fa-calendar-plus fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">Belum Ada Event</h5>
                                <p class="text-muted">Silakan cek event yang tersedia untuk mulai berpartisipasi.</p>
                                <a href="<?= site_url('presenter/events') ?>" class="action-button">
                                    <i class="fas fa-search me-2"></i>Lihat Event
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Next Actions & Recent Activities -->
                    <div class="row g-4">
                        <!-- Next Actions -->
                        <div class="col-lg-6">
                            <div class="workflow-card">
                                <h5 class="mb-3">
                                    <i class="fas fa-tasks me-2 text-primary"></i>
                                    Aksi Selanjutnya
                                </h5>
                                <?php if (!empty($next_actions)): ?>
                                    <?php foreach ($next_actions as $action): ?>
                                    <div class="d-flex align-items-center mb-3 p-3 bg-light rounded">
                                        <div class="me-3">
                                            <div class="bg-primary rounded-circle p-2" style="width: 40px; height: 40px;">
                                                <i class="fas fa-arrow-right text-white"></i>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1"><?= esc($action['event_title']) ?></h6>
                                            <small class="text-muted"><?= esc($action['action']) ?></small>
                                        </div>
                                        <div>
                                            <a href="<?= $action['url'] ?>" class="btn btn-sm btn-primary">
                                                Lakukan
                                            </a>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center text-muted py-4">
                                        <i class="fas fa-check-circle fa-2x mb-2"></i>
                                        <div>Tidak ada aksi yang diperlukan</div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Recent Activities -->
                        <div class="col-lg-6">
                            <div class="workflow-card">
                                <h5 class="mb-3">
                                    <i class="fas fa-history me-2 text-info"></i>
                                    Aktivitas Terbaru
                                </h5>
                                <div style="max-height: 400px; overflow-y: auto;">
                                    <?php if (!empty($recent_activities)): ?>
                                        <?php foreach ($recent_activities as $activity): ?>
                                        <div class="activity-item">
                                            <div class="d-flex align-items-start">
                                                <div class="me-3">
                                                    <div class="bg-light rounded-circle p-2" style="width: 40px; height: 40px;">
                                                        <i class="<?= $activity['icon'] ?> text-muted"></i>
                                                    </div>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <h6 class="mb-1"><?= esc($activity['title']) ?></h6>
                                                    <p class="mb-1 text-muted small"><?= esc($activity['subtitle']) ?></p>
                                                    <small class="text-muted"><?= date('d M Y, H:i', strtotime($activity['date'])) ?></small>
                                                </div>
                                                <div>
                                                    <span class="badge-status <?= $activity['badge_class'] ?>">
                                                        <?= ucfirst($activity['status']) ?>
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php else: ?>
                                        <div class="text-center text-muted py-4">
                                            <i class="fas fa-inbox fa-2x mb-2"></i>
                                            <div>Belum ada aktivitas</div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-refresh workflow status setiap 30 detik
            setInterval(function() {
                refreshWorkflowStatus();
            }, 30000);

            // Add click handlers for workflow cards
            document.querySelectorAll('.workflow-card[data-event-id]').forEach(card => {
                const eventId = card.dataset.eventId;
                
                // Add refresh button functionality
                card.addEventListener('dblclick', function() {
                    refreshSingleWorkflow(eventId);
                });
            });

            // Animate statistics on load
            animateCounters();

            // Show success messages with auto-hide
            showFlashMessages();
        });

        function refreshWorkflowStatus() {
            const workflowCards = document.querySelectorAll('.workflow-card[data-event-id]');
            
            workflowCards.forEach(card => {
                const eventId = card.dataset.eventId;
                refreshSingleWorkflow(eventId);
            });
        }

        function refreshSingleWorkflow(eventId) {
            fetch('<?= site_url('presenter/dashboard/refreshWorkflowStatus') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: 'event_id=' + eventId
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update workflow UI based on new data
                    updateWorkflowUI(eventId, data.workflow);
                }
            })
            .catch(error => {
                console.log('Refresh error:', error);
            });
        }

        function updateWorkflowUI(eventId, workflow) {
            const card = document.querySelector(`[data-event-id="${eventId}"]`);
            if (!card) return;

            // Update step badge
            const stepBadge = card.querySelector('.event-status-badge');
            if (stepBadge) {
                stepBadge.textContent = `Step ${workflow.step}/5`;
                stepBadge.className = `event-status-badge bg-${workflow.status === 'completed' ? 'success' : (workflow.can_proceed ? 'primary' : 'secondary')}`;
            }

            // Update progress steps
            updateProgressSteps(card, workflow.step);

            // Update alert message
            const alert = card.querySelector('.alert');
            if (alert) {
                alert.className = `alert alert-${workflow.status === 'completed' ? 'success' : (workflow.can_proceed ? 'primary' : 'warning')} mb-0`;
                alert.innerHTML = `<i class="fas fa-info-circle me-2"></i>${workflow.message}`;
            }
        }

        function updateProgressSteps(card, currentStep) {
            const steps = card.querySelectorAll('.progress-step');
            const lines = card.querySelectorAll('.progress-line');
            const labels = card.querySelectorAll('.step-label');

            steps.forEach((step, index) => {
                const stepNum = index + 1;
                step.className = 'progress-step';
                labels[index].className = 'step-label';

                if (stepNum < currentStep) {
                    step.classList.add('completed');
                    labels[index].classList.add('completed');
                } else if (stepNum === currentStep) {
                    step.classList.add('active');
                    labels[index].classList.add('active');
                }
            });

            lines.forEach((line, index) => {
                line.className = 'progress-line';
                if (index + 1 < currentStep) {
                    line.classList.add('completed');
                }
            });
        }

        function animateCounters() {
            const counters = document.querySelectorAll('.h3');
            
            counters.forEach(counter => {
                const target = parseInt(counter.textContent);
                let current = 0;
                const increment = target / 50;
                
                const timer = setInterval(() => {
                    current += increment;
                    if (current >= target) {
                        counter.textContent = target;
                        clearInterval(timer);
                    } else {
                        counter.textContent = Math.floor(current);
                    }
                }, 20);
            });
        }

        function showFlashMessages() {
            // Show success/error messages from session
            const urlParams = new URLSearchParams(window.location.search);
            const success = urlParams.get('success');
            const error = urlParams.get('error');

            if (success) {
                showToast('Success!', success, 'success');
            }
            if (error) {
                showToast('Error!', error, 'danger');
            }
        }

        function showToast(title, message, type) {
            // Create and show a bootstrap toast
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
            
            // Add to page and show
            document.body.insertAdjacentHTML('beforeend', toastHtml);
            const toast = new bootstrap.Toast(document.querySelector('.toast:last-child'));
            toast.show();
        }

        // Utility function to show loading
        function showLoading() {
            document.getElementById('loadingOverlay').style.display = 'flex';
        }

        function hideLoading() {
            document.getElementById('loadingOverlay').style.display = 'none';
        }
    </script>
</body>
</html>