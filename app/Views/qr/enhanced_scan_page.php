<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Attendance - <?= esc($event['title'] ?? 'SNIA Event') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    
    <!-- Add NGROK warning banner style -->
    <style>
        .ngrok-warning {
            background: linear-gradient(45deg, #ff6b6b, #ee5a52);
            color: white;
            padding: 8px 15px;
            font-size: 0.85rem;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 10000;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.2);
        }
        
        .ngrok-warning + .container {
            margin-top: 45px;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            padding: 20px 0;
        }

        .attendance-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            max-width: 500px;
            margin: 0 auto;
        }

        .event-header {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            padding: 25px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .event-header::before {
            content: '';
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: repeating-linear-gradient(
                45deg,
                transparent,
                transparent 10px,
                rgba(255,255,255,0.05) 10px,
                rgba(255,255,255,0.05) 20px
            );
            animation: shimmer 3s linear infinite;
        }

        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }

        .event-title {
            position: relative;
            z-index: 1;
            font-size: 1.25rem;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .event-details {
            position: relative;
            z-index: 1;
            opacity: 0.9;
        }

        .attendance-body {
            padding: 25px;
        }

        .status-card {
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 20px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .status-success {
            background: linear-gradient(135deg, #10b981, #059669);
            color: white;
        }

        .status-error {
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
        }

        .status-warning {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
        }

        .status-info {
            background: linear-gradient(135deg, #06b6d4, #0891b2);
            color: white;
        }

        .user-info {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            margin: 15px 0;
            border-left: 4px solid #6366f1;
        }

        .qr-info {
            background: #e7f3ff;
            border: 2px dashed #2563eb;
            border-radius: 12px;
            padding: 15px;
            margin: 15px 0;
            font-family: 'Courier New', monospace;
            font-size: 0.85rem;
            word-break: break-all;
        }

        .btn-attendance {
            background: linear-gradient(135deg, #10b981, #059669);
            border: none;
            border-radius: 25px;
            padding: 12px 30px;
            font-weight: 600;
            color: white;
            width: 100%;
            margin: 10px 0;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .btn-attendance:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
            color: white;
        }

        .btn-attendance:disabled {
            background: #9ca3af;
            cursor: not-allowed;
        }

        .btn-attendance .loading-spinner {
            display: none;
        }

        .btn-attendance.loading .loading-spinner {
            display: inline-block;
        }

        .btn-secondary-custom {
            background: linear-gradient(135deg, #6b7280, #4b5563);
            border: none;
            border-radius: 25px;
            padding: 10px 25px;
            font-weight: 500;
            color: white;
            transition: all 0.3s ease;
        }

        .btn-secondary-custom:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(107, 114, 128, 0.3);
            color: white;
        }

        .login-prompt {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin: 15px 0;
        }

        .event-status-badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin: 5px 0;
        }

        .status-ongoing {
            background: rgba(16, 185, 129, 0.2);
            color: #059669;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .status-upcoming {
            background: rgba(245, 158, 11, 0.2);
            color: #d97706;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .status-ended {
            background: rgba(156, 163, 175, 0.2);
            color: #6b7280;
            border: 1px solid rgba(156, 163, 175, 0.3);
        }

        .role-mismatch {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: white;
            border-radius: 12px;
            padding: 20px;
            margin: 15px 0;
            text-align: center;
        }

        .already-attended {
            background: linear-gradient(135deg, #06b6d4, #0891b2);
            color: white;
            border-radius: 12px;
            padding: 20px;
            margin: 15px 0;
            text-align: center;
        }

        .footer-links {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }

        .footer-links .btn {
            flex: 1;
        }

        @media (max-width: 768px) {
            .attendance-container {
                margin: 10px;
                border-radius: 15px;
            }
            
            .event-header, .attendance-body {
                padding: 20px;
            }
            
            .footer-links {
                flex-direction: column;
            }
        }

        .success-animation {
            animation: successPulse 2s ease-in-out infinite;
        }

        @keyframes successPulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }
    </style>
</head>
<body>
    <!-- NGROK Warning Banner -->
    <?php if (isset($ngrok_warning) && $ngrok_warning): ?>
    <div class="ngrok-warning">
        <i class="fas fa-exclamation-triangle me-2"></i>
        <strong>Development Mode:</strong> You're accessing this through NGROK tunnel. Some features may behave differently.
    </div>
    <?php endif; ?>

    <div class="container">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="attendance-container">
                    <!-- Event Header -->
                    <div class="event-header">
                        <div class="event-title"><?= esc($event['title'] ?? 'Event Title') ?></div>
                        <div class="event-details">
                            <div>
                                <i class="fas fa-calendar me-2"></i>
                                <?= isset($event['event_date']) ? date('d M Y', strtotime($event['event_date'])) : 'Date TBD' ?>
                            </div>
                            <div>
                                <i class="fas fa-clock me-2"></i>
                                <?= isset($event['event_time']) ? date('H:i', strtotime($event['event_time'])) . ' WIB' : 'Time TBD' ?>
                            </div>
                            <?php if (isset($event_status['status'])): ?>
                                <div class="event-status-badge status-<?= strtolower(str_replace(' ', '-', $event_status['status'])) ?>">
                                    <?= $event_status['status'] ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Attendance Body -->
                    <div class="attendance-body">
                        <?php if (!$is_logged_in): ?>
                            <!-- Not Logged In -->
                            <div class="login-prompt">
                                <i class="fas fa-sign-in-alt fa-2x mb-3"></i>
                                <h5>Login Required</h5>
                                <p class="mb-3">Please login to mark your attendance for this event.</p>
                                <a href="<?= $login_url ?>" class="btn btn-light btn-lg">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login Now
                                </a>
                            </div>

                        <?php elseif (isset($role_mismatch) && $role_mismatch): ?>
                            <!-- Role Mismatch -->
                            <div class="role-mismatch">
                                <i class="fas fa-user-times fa-2x mb-3"></i>
                                <h5>Access Restricted</h5>
                                <p class="mb-0"><?= $role_error ?></p>
                            </div>

                        <?php elseif (isset($already_attended) && $already_attended): ?>
                            <!-- Already Attended -->
                            <div class="already-attended success-animation">
                                <i class="fas fa-check-circle fa-3x mb-3"></i>
                                <h5>Already Attended</h5>
                                <p class="mb-0">You have already marked attendance for this event.</p>
                                <div class="mt-3">
                                    <small>
                                        <i class="fas fa-clock me-1"></i>
                                        Attended: <?= date('d M Y H:i', strtotime($attendance_time)) ?> WIB
                                    </small>
                                </div>
                            </div>

                        <?php elseif (!isset($has_payment) || !$has_payment): ?>
                            <!-- No Payment -->
                            <div class="status-card status-error">
                                <i class="fas fa-credit-card fa-2x mb-3"></i>
                                <h5>Payment Required</h5>
                                <p class="mb-0">
                                    <?= isset($payment_error) ? $payment_error : 'You need verified payment to attend this event.' ?>
                                </p>
                            </div>

                        <?php elseif (!$event_status['can_attend']): ?>
                            <!-- Event Not Available -->
                            <div class="status-card status-warning">
                                <i class="fas fa-clock fa-2x mb-3"></i>
                                <h5>Event Not Available</h5>
                                <p class="mb-0"><?= $event_status['message'] ?></p>
                            </div>

                        <?php else: ?>
                            <!-- Ready to Attend -->
                            <div class="status-card status-success">
                                <i class="fas fa-check-circle fa-2x mb-3"></i>
                                <h5>Ready to Mark Attendance</h5>
                                <p class="mb-0">Click the button below to confirm your attendance.</p>
                            </div>

                            <!-- User Info -->
                            <div class="user-info">
                                <h6><i class="fas fa-user me-2"></i>Participant Details</h6>
                                <div class="row">
                                    <div class="col-6">
                                        <small class="text-muted">Name:</small><br>
                                        <strong><?= esc($user_name) ?></strong>
                                    </div>
                                    <div class="col-6">
                                        <small class="text-muted">Role:</small><br>
                                        <span class="badge bg-<?= $user_role == 'presenter' ? 'primary' : 'info' ?>">
                                            <?= ucfirst($user_role) ?>
                                        </span>
                                    </div>
                                </div>
                                <?php if (isset($participation_type)): ?>
                                    <div class="mt-2">
                                        <small class="text-muted">Participation Type:</small><br>
                                        <span class="badge bg-<?= $participation_type == 'online' ? 'info' : 'success' ?>">
                                            <?= ucfirst($participation_type) ?>
                                        </span>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- QR Info -->
                            <div class="qr-info">
                                <div class="text-center mb-2">
                                    <small class="text-muted"><i class="fas fa-qrcode me-1"></i>QR Code Information</small>
                                </div>
                                <div>Token: <?= esc($qr_token) ?></div>
                                <?php if (isset($qr_data)): ?>
                                    <div class="mt-2">
                                        <small>Role: <?= ucfirst($qr_data['role']) ?></small> | 
                                        <small>Type: <?= ucfirst($qr_data['participation_type']) ?></small> |
                                        <small>Date: <?= $qr_data['date_formatted'] ?></small>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Attendance Button -->
                            <form id="attendanceForm">
                                <input type="hidden" name="qr_token" value="<?= esc($qr_token) ?>">
                                <input type="hidden" name="security_token" value="<?= esc($security_token) ?>">
                                
                                <button type="submit" class="btn-attendance" id="attendanceBtn">
                                    <i class="fas fa-check-circle me-2"></i>
                                    Mark My Attendance
                                    <span class="loading-spinner ms-2">
                                        <i class="fas fa-spinner fa-spin"></i>
                                    </span>
                                </button>
                            </form>
                        <?php endif; ?>

                        <!-- Footer Links -->
                        <div class="footer-links">
                            <a href="<?= base_url('/') ?>" class="btn btn-secondary-custom">
                                <i class="fas fa-home me-1"></i>Home
                            </a>
                            <?php if ($is_logged_in): ?>
                                <a href="<?= base_url('dashboard') ?>" class="btn btn-secondary-custom">
                                    <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                                </a>
                            <?php endif; ?>
                            <a href="<?= base_url('qr/scanner') ?>" class="btn btn-secondary-custom">
                                <i class="fas fa-qrcode me-1"></i>QR Scanner
                            </a>
                        </div>

                        <!-- Current Time -->
                        <div class="text-center mt-3">
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i>
                                Current Time: <?= $current_date ?> <?= $current_time ?>
                            </small>
                        </div>
                        
                        <!-- Debug Info (Development Mode) -->
                        <?php if (ENVIRONMENT === 'development'): ?>
                        <div class="mt-3 p-3 bg-light rounded">
                            <h6>Debug Information:</h6>
                            <small>
                                <strong>QR Token:</strong> <?= esc($qr_token) ?><br>
                                <strong>Event ID:</strong> <?= esc($event_id) ?><br>
                                <strong>Base URL:</strong> <?= base_url() ?><br>
                                <strong>Current URL:</strong> <?= current_url() ?><br>
                                <?php if (isset($ngrok_warning) && $ngrok_warning): ?>
                                    <strong>NGROK Mode:</strong> Active<br>
                                <?php endif; ?>
                            </small>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Success Modal -->
    <div class="modal fade" id="successModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-check-circle me-2"></i>Attendance Recorded!
                    </h5>
                </div>
                <div class="modal-body text-center">
                    <div id="successContent">
                        <!-- Success content will be populated here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="<?= base_url('dashboard') ?>" class="btn btn-success">Go to Dashboard</a>
                    <a href="<?= base_url('/') ?>" class="btn btn-outline-success">Back to Home</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Error Modal -->
    <div class="modal fade" id="errorModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title">
                        <i class="fas fa-exclamation-circle me-2"></i>Error
                    </h5>
                </div>
                <div class="modal-body">
                    <div id="errorContent">
                        <!-- Error content will be populated here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-outline-danger" onclick="window.location.reload()">Try Again</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Set base URL for AJAX requests
        const BASE_URL = '<?= base_url() ?>';
        const QR_PROCESS_URL = BASE_URL + 'qr/process';
        
        document.addEventListener('DOMContentLoaded', function() {
            const attendanceForm = document.getElementById('attendanceForm');
            
            if (attendanceForm) {
                attendanceForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    processAttendance();
                });
            }
        });

        async function processAttendance() {
            const btn = document.getElementById('attendanceBtn');
            const spinner = btn.querySelector('.loading-spinner');
            
            // Disable button and show loading
            btn.disabled = true;
            btn.classList.add('loading');
            
            try {
                const formData = new FormData(document.getElementById('attendanceForm'));
                
                const response = await fetch(QR_PROCESS_URL, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const data = await response.json();

                if (data.success) {
                    showSuccess(data.data);
                } else {
                    if (data.need_login) {
                        window.location.href = data.redirect;
                        return;
                    }
                    
                    showError(data.message);
                }

            } catch (error) {
                console.error('Attendance processing error:', error);
                showError('Network error. Please check your connection and try again. Error: ' + error.message);
            } finally {
                // Re-enable button
                btn.disabled = false;
                btn.classList.remove('loading');
            }
        }

        function showSuccess(data) {
            const modal = new bootstrap.Modal(document.getElementById('successModal'));
            const content = document.getElementById('successContent');
            
            content.innerHTML = `
                <div class="success-animation">
                    <i class="fas fa-check-circle fa-4x text-success mb-4"></i>
                    <h4 class="text-success">Attendance Recorded Successfully!</h4>
                </div>
                <div class="text-start mt-4">
                    <div class="row mb-2">
                        <div class="col-4"><strong>Name:</strong></div>
                        <div class="col-8">${data.participant_name}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-4"><strong>Role:</strong></div>
                        <div class="col-8">
                            <span class="badge bg-${data.participant_role.toLowerCase() === 'presenter' ? 'primary' : 'info'}">
                                ${data.participant_role}
                            </span>
                        </div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-4"><strong>Event:</strong></div>
                        <div class="col-8">${data.event_title}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-4"><strong>Date:</strong></div>
                        <div class="col-8">${data.attendance_date}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-4"><strong>Time:</strong></div>
                        <div class="col-8">${data.attendance_time}</div>
                    </div>
                    <div class="row mb-2">
                        <div class="col-4"><strong>Type:</strong></div>
                        <div class="col-8">
                            <span class="badge bg-${data.participation_type === 'online' ? 'info' : 'success'}">
                                ${data.participation_type.charAt(0).toUpperCase() + data.participation_type.slice(1)}
                            </span>
                        </div>
                    </div>
                    ${data.via_ngrok ? '<div class="row mb-2"><div class="col-12"><small class="text-muted">Processed via NGROK tunnel</small></div></div>' : ''}
                </div>
                <div class="alert alert-success mt-3 mb-0">
                    <i class="fas fa-info-circle me-2"></i>
                    Your attendance has been successfully recorded in the system.
                </div>
            `;
            
            modal.show();
            
            // Add confetti effect (optional)
            if (typeof confetti !== 'undefined') {
                confetti({
                    particleCount: 100,
                    spread: 70,
                    origin: { y: 0.6 }
                });
            }
        }

        function showError(message) {
            const modal = new bootstrap.Modal(document.getElementById('errorModal'));
            const content = document.getElementById('errorContent');
            
            content.innerHTML = `
                <div class="text-center mb-3">
                    <i class="fas fa-exclamation-circle fa-3x text-danger mb-3"></i>
                    <h5>Unable to Process Attendance</h5>
                </div>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    ${message}
                </div>
                <div class="mt-3">
                    <h6>Possible Solutions:</h6>
                    <ul class="small mb-0">
                        <li>Check your internet connection</li>
                        <li>Make sure your payment is verified</li>
                        <li>Ensure you're scanning the correct QR code</li>
                        <li>Try refreshing the page</li>
                        <li>Contact admin if the problem persists</li>
                    </ul>
                </div>
            `;
            
            modal.show();
        }

        // Auto-refresh page status every 30 seconds for ongoing events
        <?php if (isset($event_status['is_ongoing']) && $event_status['is_ongoing']): ?>
        setInterval(function() {
            // Only refresh if no modals are open
            if (!document.querySelector('.modal.show')) {
                const currentTime = new Date();
                const timeElement = document.querySelector('.text-muted small');
                if (timeElement && timeElement.innerHTML.includes('Current Time:')) {
                    const timeStr = currentTime.toLocaleString('id-ID');
                    timeElement.innerHTML = '<i class="fas fa-clock me-1"></i>Current Time: ' + timeStr;
                }
            }
        }, 30000);
        <?php endif; ?>

        // Handle browser navigation
        window.addEventListener('popstate', function(event) {
            window.location.reload();
        });

        // Prevent form resubmission on page refresh
        if (window.history.replaceState) {
            window.history.replaceState(null, null, window.location.href);
        }
    </script>
</body>
</html>