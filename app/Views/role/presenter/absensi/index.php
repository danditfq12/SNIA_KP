<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance - SNIA Enhanced Scanner</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/animate.css/4.1.1/animate.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #64748b;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --info-color: #06b6d4;
            --dark-color: #1e293b;
            --light-color: #f8fafc;
        }

        body {
            background: linear-gradient(135deg, var(--light-color) 0%, #e2e8f0 100%);
            font-family: 'Inter', 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }

        .sidebar {
            background: linear-gradient(180deg, var(--primary-color) 0%, #1e40af 100%);
            min-height: 100vh;
            box-shadow: 4px 0 20px rgba(0,0,0,0.1);
            position: fixed;
            top: 0;
            left: 0;
            width: 250px;
            z-index: 1000;
        }

        .sidebar .nav-link {
            color: rgba(255,255,255,0.8);
            padding: 12px 20px;
            margin: 4px 0;
            border-radius: 8px;
            transition: all 0.3s ease;
            text-decoration: none;
        }

        .sidebar .nav-link:hover {
            background: rgba(255,255,255,0.1);
            color: white;
            transform: translateX(5px);
        }

        .sidebar .nav-link.active {
            background: rgba(255,255,255,0.2);
            color: white;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        .main-content {
            margin-left: 250px;
            padding: 20px;
            min-height: 100vh;
        }

        .page-header {
            background: linear-gradient(135deg, var(--primary-color), var(--info-color));
            color: white;
            border-radius: 20px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(37, 99, 235, 0.3);
        }

        .card {
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            border: 1px solid rgba(255,255,255,0.2);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.15);
        }

        .card-header {
            padding: 20px;
            font-weight: 600;
        }

        .btn-custom {
            border-radius: 8px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
        }

        .btn-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .btn-success-custom {
            background: linear-gradient(135deg, var(--success-color), #059669);
            color: white;
        }

        .btn-danger-custom {
            background: linear-gradient(135deg, var(--danger-color), #dc2626);
            color: white;
        }

        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
                width: 250px;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
            }

            .mobile-toggle {
                display: block;
                position: fixed;
                top: 20px;
                left: 20px;
                z-index: 1001;
            }
        }

        .mobile-toggle {
            display: none;
        }

        .qr-scanner-container {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            border: 2px dashed #e2e8f0;
            transition: all 0.3s ease;
            min-height: 350px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .qr-scanner-container.active {
            border-color: var(--primary-color);
            background: rgba(37, 99, 235, 0.05);
        }

        .camera-container {
            position: relative;
            background: #000;
            border-radius: 12px;
            overflow: hidden;
            width: 100%;
            max-width: 400px;
            min-height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .camera-container video {
            width: 100%;
            height: auto;
            border-radius: 8px;
        }

        .camera-container canvas {
            display: none;
        }

        .scanner-overlay {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 200px;
            height: 200px;
            border: 2px solid #00ff00;
            border-radius: 12px;
            box-shadow: 0 0 20px rgba(0, 255, 0, 0.3);
            animation: scannerPulse 2s infinite;
            pointer-events: none;
        }

        @keyframes scannerPulse {
            0%, 100% { opacity: 1; transform: translate(-50%, -50%) scale(1); }
            50% { opacity: 0.7; transform: translate(-50%, -50%) scale(1.05); }
        }

        .scanner-corners {
            position: absolute;
            width: 20px;
            height: 20px;
            border: 3px solid #00ff00;
        }

        .corner-tl { top: 10px; left: 10px; border-right: none; border-bottom: none; }
        .corner-tr { top: 10px; right: 10px; border-left: none; border-bottom: none; }
        .corner-bl { bottom: 10px; left: 10px; border-right: none; border-top: none; }
        .corner-br { bottom: 10px; right: 10px; border-left: none; border-top: none; }

        .qr-scanner-placeholder {
            text-align: center;
            color: #6b7280;
        }

        .qr-scanner-placeholder i {
            font-size: 3rem;
            margin-bottom: 1rem;
            color: #cbd5e0;
        }

        .camera-controls {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            flex-wrap: wrap;
        }

        .camera-select {
            flex: 1;
            min-width: 200px;
        }

        .status-indicator {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            display: none;
            font-weight: 500;
            animation: fadeIn 0.3s ease-in;
        }

        .status-success {
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.1), rgba(16, 185, 129, 0.05));
            color: #059669;
            border: 1px solid rgba(16, 185, 129, 0.3);
        }

        .status-error {
            background: linear-gradient(135deg, rgba(239, 68, 68, 0.1), rgba(239, 68, 68, 0.05));
            color: #dc2626;
            border: 1px solid rgba(239, 68, 68, 0.3);
        }

        .status-warning {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.1), rgba(245, 158, 11, 0.05));
            color: #d97706;
            border: 1px solid rgba(245, 158, 11, 0.3);
        }

        .status-info {
            background: linear-gradient(135deg, rgba(6, 182, 212, 0.1), rgba(6, 182, 212, 0.05));
            color: #0891b2;
            border: 1px solid rgba(6, 182, 212, 0.3);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .detected-qr {
            background: linear-gradient(135deg, #e7f3ff, #f0f9ff);
            border: 2px solid #2563eb;
            border-radius: 8px;
            padding: 15px;
            margin: 10px 0;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            word-break: break-all;
            animation: detectSuccess 0.5s ease-in;
        }

        @keyframes detectSuccess {
            0% { opacity: 0; transform: translateY(-10px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        .attendance-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 12px 16px;
            border-radius: 12px;
            font-weight: 600;
            margin-bottom: 15px;
        }

        .attendance-present {
            background: rgba(16, 185, 129, 0.1);
            color: #059669;
            border: 2px solid rgba(16, 185, 129, 0.3);
        }

        .attendance-absent {
            background: rgba(251, 191, 36, 0.1);
            color: #d97706;
            border: 2px solid rgba(251, 191, 36, 0.3);
        }

        .qr-format-display {
            background: linear-gradient(135deg, #f8fafc, #e2e8f0);
            border-radius: 8px;
            padding: 15px;
            border-left: 4px solid var(--primary-color);
        }

        .history-item {
            padding: 12px 0;
            border-bottom: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .history-item:hover {
            background: rgba(37, 99, 235, 0.05);
            margin: 0 -15px;
            padding: 12px 15px;
            border-radius: 8px;
        }

        .history-item:last-child {
            border-bottom: none;
        }

        .input-group .form-control {
            border-radius: 8px 0 0 8px;
        }

        .input-group .btn {
            border-radius: 0 8px 8px 0;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }

        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 5px;
        }

        .stat-label {
            color: #6b7280;
            font-size: 0.9rem;
        }

        .upcoming-event {
            background: white;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 10px;
            border-left: 4px solid var(--info-color);
            transition: all 0.3s ease;
        }

        .upcoming-event:hover {
            transform: translateX(5px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .loading-spinner {
            display: none;
        }

        .loading-spinner.show {
            display: inline-block;
        }

        .btn-camera {
            border-radius: 8px;
            padding: 8px 12px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            min-width: 44px;
        }

        .btn-camera:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        }

        .scan-result {
            background: white;
            border-radius: 12px;
            padding: 20px;
            margin: 15px 0;
            border-left: 4px solid var(--success-color);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        .qr-debug {
            background: #f1f5f9;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 10px;
            margin: 10px 0;
            font-family: monospace;
            font-size: 0.8rem;
        }
    </style>
</head>
<body>
    <!-- Mobile Toggle Button -->
    <button class="btn btn-primary mobile-toggle" onclick="toggleSidebar()">
        <i class="fas fa-bars"></i>
    </button>

    <!-- Sidebar -->
    <div class="sidebar">
        <div class="p-4 text-center">
            <h4 class="text-white mb-0">
                <i class="fas fa-microphone-alt me-2"></i>
                SNIA Enhanced
            </h4>
            <small class="text-white-50">Scanner Dashboard</small>
        </div>
        
        <nav class="nav flex-column px-3">
            <a class="nav-link" href="#dashboard">
                <i class="fas fa-tachometer-alt me-2"></i> Dashboard
            </a>
            <a class="nav-link" href="#events">
                <i class="fas fa-calendar me-2"></i> Events
            </a>
            <a class="nav-link" href="#abstracts">
                <i class="fas fa-file-alt me-2"></i> My Abstracts
            </a>
            <a class="nav-link" href="#payments">
                <i class="fas fa-credit-card me-2"></i> Payments
            </a>
            <a class="nav-link active" href="#attendance">
                <i class="fas fa-qrcode me-2"></i> Attendance
            </a>
            <a class="nav-link" href="#loa">
                <i class="fas fa-file-contract me-2"></i> LOA
            </a>
            <a class="nav-link" href="#certificate">
                <i class="fas fa-certificate me-2"></i> Certificate
            </a>
            <hr class="my-3" style="border-color: rgba(255,255,255,0.2);">
            <a class="nav-link" href="#profile">
                <i class="fas fa-user me-2"></i> Profile
            </a>
            <a class="nav-link text-warning" href="#logout">
                <i class="fas fa-sign-out-alt me-2"></i> Logout
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Flash Messages -->
        <div id="alertContainer"></div>

        <!-- Page Header -->
        <div class="page-header animate__animated animate__fadeInDown">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="mb-2">
                        <i class="fas fa-qrcode me-2"></i>
                        Enhanced Attendance Scanner
                    </h2>
                    <p class="mb-0 opacity-90">
                        Advanced QR code scanning with improved detection, validation, and processing capabilities.
                    </p>
                </div>
                <div class="col-md-4 text-end">
                    <div class="text-white-50 small">Scanner Status</div>
                    <div class="fw-bold">
                        <i class="fas fa-circle text-success me-1"></i>Ready
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number" id="totalScans">0</div>
                    <div class="stat-label">Total Scans</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number" id="successfulScans">0</div>
                    <div class="stat-label">Successful</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number" id="todayScans">0</div>
                    <div class="stat-label">Today's Scans</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number" id="scanAccuracy">0%</div>
                    <div class="stat-label">Success Rate</div>
                </div>
            </div>
        </div>

        <!-- Main Content Row -->
        <div class="row">
            <!-- QR Scanner Section -->
            <div class="col-lg-8">
                <div class="card shadow-sm animate__animated animate__fadeInLeft">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-qrcode me-2"></i>Enhanced QR Scanner v2.0
                        </h5>
                    </div>
                    <div class="card-body">
                        <!-- Status Indicator -->
                        <div id="statusIndicator" class="status-indicator">
                            <i class="fas fa-info-circle me-2"></i>
                            <span id="statusMessage">Initializing enhanced scanner...</span>
                        </div>

                        <!-- Camera Controls -->
                        <div class="camera-controls">
                            <select id="cameraSelect" class="form-select camera-select">
                                <option value="">Select Camera...</option>
                            </select>
                            <button id="startCamera" class="btn btn-success btn-camera" title="Start Camera">
                                <i class="fas fa-camera"></i>
                            </button>
                            <button id="stopCamera" class="btn btn-danger btn-camera" style="display: none;" title="Stop Camera">
                                <i class="fas fa-stop"></i>
                            </button>
                            <button id="switchCamera" class="btn btn-info btn-camera" style="display: none;" title="Switch Camera">
                                <i class="fas fa-sync"></i>
                            </button>
                            <button id="toggleFlash" class="btn btn-warning btn-camera" style="display: none;" title="Toggle Flash">
                                <i class="fas fa-flash"></i>
                            </button>
                            <button id="scanSettings" class="btn btn-secondary btn-camera" title="Scan Settings">
                                <i class="fas fa-cog"></i>
                            </button>
                        </div>
                        
                        <div class="qr-scanner-container" id="qrScannerContainer">
                            <div class="camera-container" id="cameraContainer">
                                <video id="video" autoplay muted playsinline style="display: none;"></video>
                                <canvas id="canvas" style="display: none;"></canvas>
                                
                                <!-- Scanner Overlay -->
                                <div class="scanner-overlay" id="scannerOverlay" style="display: none;">
                                    <div class="scanner-corners corner-tl"></div>
                                    <div class="scanner-corners corner-tr"></div>
                                    <div class="scanner-corners corner-bl"></div>
                                    <div class="scanner-corners corner-br"></div>
                                </div>

                                <!-- Placeholder when no camera -->
                                <div id="cameraPlaceholder" class="qr-scanner-placeholder">
                                    <i class="fas fa-camera-retro"></i>
                                    <h5>Enhanced QR Scanner v2.0</h5>
                                    <p>Advanced scanning with improved detection algorithms</p>
                                    <small>Select camera and click start to begin enhanced scanning</small>
                                </div>
                            </div>
                        </div>

                        <!-- Scan Settings Panel -->
                        <div id="scanSettingsPanel" style="display: none;" class="mt-3">
                            <div class="card bg-light">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="fas fa-cog me-2"></i>Scan Settings</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="continuousMode" checked>
                                                <label class="form-check-label" for="continuousMode">
                                                    Continuous Scanning
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="autoProcess">
                                                <label class="form-check-label" for="autoProcess">
                                                    Auto Process Valid QR
                                                </label>
                                            </div>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" id="soundEnabled" checked>
                                                <label class="form-check-label" for="soundEnabled">
                                                    Sound Notifications
                                                </label>
                                            </div>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="scanSensitivity" class="form-label">Detection Sensitivity</label>
                                            <input type="range" class="form-range" id="scanSensitivity" min="1" max="10" value="5">
                                            <div class="d-flex justify-content-between">
                                                <small>Low</small>
                                                <small>High</small>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Detected QR Display -->
                        <div id="detectedQR" class="detected-qr" style="display: none;">
                            <div class="fw-bold mb-2">
                                <i class="fas fa-check-circle text-success me-2"></i>QR Code Detected:
                            </div>
                            <div id="qrContent"></div>
                            <div class="mt-2">
                                <small class="text-muted" id="qrDetails"></small>
                            </div>
                            <div id="qrValidation" class="mt-2"></div>
                        </div>

                        <!-- QR Debug Info -->
                        <div id="qrDebugInfo" class="qr-debug" style="display: none;">
                            <div class="fw-bold mb-2">Debug Information:</div>
                            <div id="debugContent"></div>
                        </div>

                        <!-- Process Button -->
                        <div id="processSection" style="display: none;">
                            <form id="attendanceForm">
                                <input type="hidden" id="qrToken" name="qr_token" value="">
                                <input type="hidden" id="securityToken" name="security_token" value="">
                                
                                <button type="submit" class="btn btn-success w-100 py-2" id="processBtn">
                                    <i class="fas fa-check-circle me-2"></i>
                                    Process Attendance
                                    <span class="loading-spinner ms-2">
                                        <i class="fas fa-spinner fa-spin"></i>
                                    </span>
                                </button>
                            </form>
                        </div>

                        <!-- Manual Input -->
                        <div class="mt-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0">
                                    <i class="fas fa-keyboard me-2"></i>Manual Input:
                                </h6>
                                <button class="btn btn-outline-primary btn-sm" onclick="toggleManualInput()">
                                    <i class="fas fa-keyboard me-1"></i>Toggle
                                </button>
                            </div>
                            
                            <div id="manualInput" style="display: none;">
                                <form id="manualForm">
                                    <div class="input-group">
                                        <input type="text" id="manualQrCode" class="form-control" 
                                               placeholder="Enter QR code, URL, or event ID...">
                                        <button type="submit" class="btn btn-outline-primary">
                                            <i class="fas fa-check me-1"></i>Validate
                                            <span class="loading-spinner ms-1">
                                                <i class="fas fa-spinner fa-spin"></i>
                                            </span>
                                        </button>
                                    </div>
                                    <div class="form-text">
                                        <i class="fas fa-info-circle me-1"></i>
                                        Supports: QR tokens, URLs, or simple event IDs
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Results Area -->
                        <div id="scanResults" class="mt-3"></div>
                    </div>
                </div>
            </div>

            <!-- Sidebar Info -->
            <div class="col-lg-4">
                <!-- Scanner Status -->
                <div class="card shadow-sm mb-4 animate__animated animate__fadeInRight">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-heartbeat me-2"></i>Scanner Status
                        </h6>
                    </div>
                    <div class="card-body">
                        <div id="scannerStatus">
                            <div class="attendance-badge attendance-absent">
                                <i class="fas fa-power-off"></i>
                                <div>
                                    <div class="fw-bold">Scanner Idle</div>
                                    <small>Ready to start scanning</small>
                                </div>
                            </div>
                        </div>

                        <div class="mt-3">
                            <h6><i class="fas fa-info-circle me-1"></i>Enhanced Features:</h6>
                            <div class="qr-format-display">
                                <ul class="list-unstyled mb-0 small">
                                    <li><i class="fas fa-check text-success me-1"></i> Multi-format QR support</li>
                                    <li><i class="fas fa-check text-success me-1"></i> Real-time validation</li>
                                    <li><i class="fas fa-check text-success me-1"></i> Enhanced error handling</li>
                                    <li><i class="fas fa-check text-success me-1"></i> Debug mode available</li>
                                    <li><i class="fas fa-check text-success me-1"></i> Auto-processing option</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Scanner Performance -->
                <div class="card shadow-sm mb-4 animate__animated animate__fadeInRight animate__delay-1s">
                    <div class="card-header bg-success text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-chart-line me-2"></i>Performance Metrics
                        </h6>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <div class="list-group-item border-0 px-0 d-flex justify-content-between">
                                <span><i class="fas fa-camera text-primary me-2"></i>Camera FPS:</span>
                                <span id="cameraFPS">0</span>
                            </div>
                            <div class="list-group-item border-0 px-0 d-flex justify-content-between">
                                <span><i class="fas fa-clock text-info me-2"></i>Scan Time:</span>
                                <span id="avgScanTime">0ms</span>
                            </div>
                            <div class="list-group-item border-0 px-0 d-flex justify-content-between">
                                <span><i class="fas fa-memory text-warning me-2"></i>Memory Usage:</span>
                                <span id="memoryUsage">0MB</span>
                            </div>
                            <div class="list-group-item border-0 px-0 d-flex justify-content-between">
                                <span><i class="fas fa-battery-half text-success me-2"></i>Battery Impact:</span>
                                <span id="batteryImpact">Low</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Scans History -->
                <div class="card shadow-sm animate__animated animate__fadeInRight animate__delay-2s">
                    <div class="card-header bg-secondary text-white d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">
                            <i class="fas fa-history me-2"></i>Recent Scans
                        </h6>
                        <div class="text-end">
                            <small><span id="currentTime"></span></small>
                        </div>
                    </div>
                    <div class="card-body">
                        <div id="scanHistory">
                            <div class="text-center py-4">
                                <i class="fas fa-qrcode fa-3x text-muted mb-3"></i>
                                <h6 class="text-muted">No Scans Yet</h6>
                                <p class="text-muted small">Your scan history will appear here</p>
                            </div>
                        </div>
                        <div class="mt-3 d-grid">
                            <button class="btn btn-outline-secondary btn-sm" onclick="clearHistory()">
                                <i class="fas fa-trash me-1"></i>Clear History
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
   // Enhanced Presenter Attendance Scanner JavaScript
// Integrated with database and route system
// Global variables
let video = null;
let canvas = null;
let ctx = null;
let scanningInterval = null;
let currentStream = null;
let availableCameras = [];
let currentCameraIndex = 0;
let scanStats = {
    totalScans: 0,
    successfulScans: 0,
    todayScans: 0,
    scanTimes: []
};
let isProcessing = false;
let continuousMode = true;
let autoProcess = false;
let soundEnabled = true;

// Initialize on page load
document.addEventListener('DOMContentLoaded', function() {
    initializeScanner();
    updateCurrentTime();
    setInterval(updateCurrentTime, 1000);
    loadScanHistory();
    updateStats();
    
    // Setup event listeners
    setupEventListeners();
    
    // Check camera permissions
    checkCameraPermissions();
});

// Setup all event listeners
function setupEventListeners() {
    // Camera controls
    document.getElementById('startCamera').addEventListener('click', startCamera);
    document.getElementById('stopCamera').addEventListener('click', stopCamera);
    document.getElementById('switchCamera').addEventListener('click', switchCamera);
    document.getElementById('scanSettings').addEventListener('click', toggleScanSettings);
    
    // Manual form
    document.getElementById('manualForm').addEventListener('submit', handleManualSubmit);
    document.getElementById('attendanceForm').addEventListener('submit', handleAttendanceSubmit);
    
    // Scan settings
    document.getElementById('continuousMode').addEventListener('change', function(e) {
        continuousMode = e.target.checked;
        showStatus('Continuous mode ' + (continuousMode ? 'enabled' : 'disabled'), 'info');
    });
    
    document.getElementById('autoProcess').addEventListener('change', function(e) {
        autoProcess = e.target.checked;
        showStatus('Auto process ' + (autoProcess ? 'enabled' : 'disabled'), 'info');
    });
    
    document.getElementById('soundEnabled').addEventListener('change', function(e) {
        soundEnabled = e.target.checked;
        showStatus('Sound notifications ' + (soundEnabled ? 'enabled' : 'disabled'), 'info');
    });
}

// Initialize scanner components
function initializeScanner() {
    video = document.getElementById('video');
    canvas = document.getElementById('canvas');
    
    if (canvas) {
        ctx = canvas.getContext('2d');
    }
    
    // Get available cameras
    getCameras();
    
    showStatus('Enhanced scanner initialized. Ready to scan!', 'success');
}

// Check camera permissions
async function checkCameraPermissions() {
    try {
        const permission = await navigator.permissions.query({ name: 'camera' });
        
        if (permission.state === 'denied') {
            showStatus('Camera access denied. Please enable camera permissions.', 'error');
        } else if (permission.state === 'prompt') {
            showStatus('Camera permission will be requested when starting scanner.', 'warning');
        }
    } catch (error) {
        console.log('Permission API not supported');
    }
}

// Get available cameras
async function getCameras() {
    try {
        const devices = await navigator.mediaDevices.enumerateDevices();
        availableCameras = devices.filter(device => device.kind === 'videoinput');
        
        const cameraSelect = document.getElementById('cameraSelect');
        cameraSelect.innerHTML = '<option value="">Select Camera...</option>';
        
        availableCameras.forEach((camera, index) => {
            const option = document.createElement('option');
            option.value = index;
            option.textContent = camera.label || `Camera ${index + 1}`;
            cameraSelect.appendChild(option);
        });
        
        if (availableCameras.length === 0) {
            showStatus('No cameras found on this device.', 'warning');
        }
        
    } catch (error) {
        console.error('Error getting cameras:', error);
        showStatus('Error detecting cameras: ' + error.message, 'error');
    }
}

// Start camera
async function startCamera() {
    try {
        const cameraSelect = document.getElementById('cameraSelect');
        const selectedIndex = cameraSelect.value;
        
        if (!selectedIndex && availableCameras.length > 0) {
            currentCameraIndex = 0;
        } else {
            currentCameraIndex = parseInt(selectedIndex);
        }
        
        const constraints = {
            video: {
                deviceId: availableCameras[currentCameraIndex]?.deviceId,
                width: { ideal: 640 },
                height: { ideal: 480 },
                facingMode: 'environment' // Prefer back camera
            }
        };
        
        currentStream = await navigator.mediaDevices.getUserMedia(constraints);
        video.srcObject = currentStream;
        
        video.onloadedmetadata = () => {
            video.play();
            canvas.width = video.videoWidth;
            canvas.height = video.videoHeight;
            
            // Show video and controls
            video.style.display = 'block';
            document.getElementById('cameraPlaceholder').style.display = 'none';
            document.getElementById('scannerOverlay').style.display = 'block';
            document.getElementById('stopCamera').style.display = 'inline-block';
            document.getElementById('switchCamera').style.display = 'inline-block';
            document.getElementById('startCamera').style.display = 'none';
            
            // Start scanning
            startScanning();
            
            showStatus('Camera started successfully. Scanning for QR codes...', 'success');
            updateScannerStatus('scanning');
        };
        
    } catch (error) {
        console.error('Error starting camera:', error);
        showStatus('Error starting camera: ' + error.message, 'error');
        
        if (error.name === 'NotAllowedError') {
            showStatus('Camera access denied. Please allow camera permissions and try again.', 'error');
        } else if (error.name === 'NotFoundError') {
            showStatus('No camera found. Please connect a camera and try again.', 'error');
        }
    }
}

// Stop camera
function stopCamera() {
    if (currentStream) {
        currentStream.getTracks().forEach(track => track.stop());
        currentStream = null;
    }
    
    if (scanningInterval) {
        clearInterval(scanningInterval);
        scanningInterval = null;
    }
    
    video.style.display = 'none';
    document.getElementById('cameraPlaceholder').style.display = 'block';
    document.getElementById('scannerOverlay').style.display = 'none';
    document.getElementById('stopCamera').style.display = 'none';
    document.getElementById('switchCamera').style.display = 'none';
    document.getElementById('startCamera').style.display = 'inline-block';
    
    showStatus('Camera stopped.', 'info');
    updateScannerStatus('idle');
}

// Switch camera
async function switchCamera() {
    if (availableCameras.length <= 1) {
        showStatus('Only one camera available.', 'warning');
        return;
    }
    
    stopCamera();
    
    currentCameraIndex = (currentCameraIndex + 1) % availableCameras.length;
    
    setTimeout(() => {
        startCamera();
    }, 500);
}

// Start scanning process
function startScanning() {
    if (scanningInterval) {
        clearInterval(scanningInterval);
    }
    
    scanningInterval = setInterval(() => {
        if (video.readyState === video.HAVE_ENOUGH_DATA && !isProcessing) {
            scanForQRCode();
        }
    }, 100); // Scan every 100ms
}

// Scan for QR code
function scanForQRCode() {
    try {
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        const imageData = ctx.getImageData(0, 0, canvas.width, canvas.height);
        
        const startTime = performance.now();
        const code = jsQR(imageData.data, imageData.width, imageData.height);
        const scanTime = performance.now() - startTime;
        
        // Update performance metrics
        scanStats.scanTimes.push(scanTime);
        if (scanStats.scanTimes.length > 100) {
            scanStats.scanTimes.shift(); // Keep only last 100 scan times
        }
        
        updatePerformanceMetrics();
        
        if (code) {
            handleQRCodeDetected(code.data);
        }
        
    } catch (error) {
        console.error('Error during QR scanning:', error);
    }
}

// Handle QR code detection
function handleQRCodeDetected(qrData) {
    if (isProcessing && continuousMode) {
        return; // Avoid multiple processing of same code
    }
    
    scanStats.totalScans++;
    updateStats();
    
    // Play sound if enabled
    if (soundEnabled) {
        playBeepSound();
    }
    
    // Display detected QR
    displayDetectedQR(qrData);
    
    // Validate QR code
    validateQRCode(qrData);
    
    // Add to scan history
    addToScanHistory(qrData, 'detected');
    
    showStatus('QR Code detected! Validating...', 'info');
}

// Display detected QR code
function displayDetectedQR(qrData) {
    const detectedQR = document.getElementById('detectedQR');
    const qrContent = document.getElementById('qrContent');
    const qrDetails = document.getElementById('qrDetails');
    
    qrContent.textContent = qrData;
    qrDetails.textContent = `Length: ${qrData.length} characters | Detected at: ${new Date().toLocaleTimeString()}`;
    
    detectedQR.style.display = 'block';
    
    // Auto-hide after 10 seconds if continuous mode
    if (continuousMode) {
        setTimeout(() => {
            if (!isProcessing) {
                detectedQR.style.display = 'none';
            }
        }, 10000);
    }
}

// Validate QR code
async function validateQRCode(qrData) {
    try {
        isProcessing = true;
        updateScannerStatus('processing');
        
        const response = await fetch('<?= site_url('presenter/absensi/scan') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'qr_code=' + encodeURIComponent(qrData)
        });
        
        const data = await response.json();
        
        displayValidationResult(data, qrData);
        
        if (data.success) {
            scanStats.successfulScans++;
            scanStats.todayScans++;
            addToScanHistory(qrData, 'success', data.data);
            
            if (autoProcess) {
                // Auto process successful attendance
                setTimeout(() => {
                    processAttendance(qrData, data);
                }, 1000);
            } else {
                // Show process button
                setupProcessButton(qrData, data);
            }
        } else {
            addToScanHistory(qrData, 'error', { message: data.message });
        }
        
        updateStats();
        
    } catch (error) {
        console.error('Validation error:', error);
        showStatus('Network error during validation: ' + error.message, 'error');
        addToScanHistory(qrData, 'error', { message: error.message });
    } finally {
        isProcessing = false;
        updateScannerStatus('scanning');
    }
}

// Display validation result
function displayValidationResult(data, qrData) {
    const qrValidation = document.getElementById('qrValidation');
    
    if (data.success) {
        qrValidation.innerHTML = `
            <div class="alert alert-success">
                <i class="fas fa-check-circle me-2"></i>
                <strong>Valid QR Code!</strong><br>
                Event: ${data.data.event_title}<br>
                Role: ${data.data.role}<br>
                Participation: ${data.data.participation_type}
            </div>
        `;
        showStatus('QR Code validated successfully!', 'success');
    } else {
        qrValidation.innerHTML = `
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle me-2"></i>
                <strong>Invalid QR Code</strong><br>
                ${data.message}
            </div>
        `;
        showStatus('QR Code validation failed: ' + data.message, 'error');
    }
}

// Setup process button
function setupProcessButton(qrData, validationData) {
    const processSection = document.getElementById('processSection');
    const qrTokenInput = document.getElementById('qrToken');
    
    qrTokenInput.value = qrData;
    processSection.style.display = 'block';
    
    // Auto-hide process button after 30 seconds
    setTimeout(() => {
        if (!isProcessing) {
            processSection.style.display = 'none';
        }
    }, 30000);
}

// Handle attendance form submission
async function handleAttendanceSubmit(e) {
    e.preventDefault();
    
    const formData = new FormData(e.target);
    const qrToken = formData.get('qr_token');
    
    if (!qrToken) {
        showStatus('No QR code data to process', 'error');
        return;
    }
    
    await processAttendance(qrToken);
}

// Process attendance
async function processAttendance(qrToken, existingData = null) {
    try {
        isProcessing = true;
        
        const processBtn = document.getElementById('processBtn');
        const loadingSpinner = processBtn.querySelector('.loading-spinner');
        
        processBtn.disabled = true;
        loadingSpinner.classList.add('show');
        
        showStatus('Processing attendance...', 'info');
        updateScannerStatus('processing');
        
        // Use existing validation data or re-validate
        const response = await fetch('<?= site_url('presenter/absensi/scan') ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'qr_code=' + encodeURIComponent(qrToken)
        });
        
        const data = await response.json();
        
        displayAttendanceResult(data);
        
        if (data.success) {
            // Update scan history
            addToScanHistory(qrToken, 'processed', data.data);
            
            // Hide process section
            document.getElementById('processSection').style.display = 'none';
            document.getElementById('detectedQR').style.display = 'none';
            
            // Play success sound
            if (soundEnabled) {
                playSuccessSound();
            }
        }
        
    } catch (error) {
        console.error('Process error:', error);
        showStatus('Error processing attendance: ' + error.message, 'error');
        displayAttendanceResult({
            success: false,
            message: 'Network error: ' + error.message
        });
    } finally {
        isProcessing = false;
        
        const processBtn = document.getElementById('processBtn');
        const loadingSpinner = processBtn.querySelector('.loading-spinner');
        
        processBtn.disabled = false;
        loadingSpinner.classList.remove('show');
        
        updateScannerStatus('scanning');
    }
}

// Display attendance result
function displayAttendanceResult(data) {
    const scanResults = document.getElementById('scanResults');
    
    if (data.success) {
        scanResults.innerHTML = `
            <div class="scan-result animate__animated animate__fadeInUp">
                <div class="d-flex align-items-center mb-3">
                    <div class="bg-success rounded-circle p-3 me-3">
                        <i class="fas fa-check text-white fa-2x"></i>
                    </div>
                    <div>
                        <h5 class="mb-1 text-success">Attendance Recorded!</h5>
                        <small class="text-muted">Successfully processed at ${new Date().toLocaleString()}</small>
                    </div>
                </div>
                
                <div class="row text-center">
                    <div class="col-md-3">
                        <div class="fw-bold text-primary">${data.data.event_title}</div>
                        <small class="text-muted">Event</small>
                    </div>
                    <div class="col-md-3">
                        <div class="fw-bold text-info">${data.data.role}</div>
                        <small class="text-muted">Role</small>
                    </div>
                    <div class="col-md-3">
                        <div class="fw-bold text-success">${data.data.participation_type}</div>
                        <small class="text-muted">Type</small>
                    </div>
                    <div class="col-md-3">
                        <div class="fw-bold text-warning">${data.data.attendance_time}</div>
                        <small class="text-muted">Time</small>
                    </div>
                </div>
            </div>
        `;
        
        showStatus('Attendance recorded successfully!', 'success');
    } else {
        scanResults.innerHTML = `
            <div class="alert alert-danger animate__animated animate__fadeInUp">
                <div class="d-flex align-items-center">
                    <i class="fas fa-exclamation-circle fa-2x me-3"></i>
                    <div>
                        <h6 class="mb-1">Attendance Failed</h6>
                        <div>${data.message}</div>
                    </div>
                </div>
            </div>
        `;
        
        showStatus('Attendance processing failed: ' + data.message, 'error');
    }
    
    // Auto-clear result after 10 seconds
    setTimeout(() => {
        scanResults.innerHTML = '';
    }, 10000);
}

// Handle manual form submission
async function handleManualSubmit(e) {
    e.preventDefault();
    
    const manualInput = document.getElementById('manualQrCode');
    const qrData = manualInput.value.trim();
    
    if (!qrData) {
        showStatus('Please enter QR code data', 'warning');
        return;
    }
    
    const loadingSpinner = e.target.querySelector('.loading-spinner');
    const submitBtn = e.target.querySelector('button[type="submit"]');
    
    loadingSpinner.classList.add('show');
    submitBtn.disabled = true;
    
    try {
        await validateQRCode(qrData);
    } finally {
        loadingSpinner.classList.remove('show');
        submitBtn.disabled = false;
        manualInput.value = '';
    }
}

// Toggle manual input
function toggleManualInput() {
    const manualInput = document.getElementById('manualInput');
    const isHidden = manualInput.style.display === 'none';
    
    manualInput.style.display = isHidden ? 'block' : 'none';
    
    if (isHidden) {
        document.getElementById('manualQrCode').focus();
    }
}

// Toggle scan settings
function toggleScanSettings() {
    const settingsPanel = document.getElementById('scanSettingsPanel');
    const isHidden = settingsPanel.style.display === 'none';
    
    settingsPanel.style.display = isHidden ? 'block' : 'none';
}

// Update scanner status
function updateScannerStatus(status) {
    const scannerStatus = document.getElementById('scannerStatus');
    
    let badgeClass, icon, title, description;
    
    switch (status) {
        case 'scanning':
            badgeClass = 'attendance-present';
            icon = 'fa-camera';
            title = 'Scanning Active';
            description = 'Looking for QR codes...';
            break;
        case 'processing':
            badgeClass = 'attendance-badge';
            icon = 'fa-spinner fa-spin';
            title = 'Processing';
            description = 'Validating QR code...';
            break;
        default:
            badgeClass = 'attendance-absent';
            icon = 'fa-power-off';
            title = 'Scanner Idle';
            description = 'Ready to start scanning';
    }
    
    scannerStatus.innerHTML = `
        <div class="${badgeClass}">
            <i class="fas ${icon}"></i>
            <div>
                <div class="fw-bold">${title}</div>
                <small>${description}</small>
            </div>
        </div>
    `;
}

// Update performance metrics
function updatePerformanceMetrics() {
    const avgScanTime = scanStats.scanTimes.length > 0 
        ? Math.round(scanStats.scanTimes.reduce((a, b) => a + b) / scanStats.scanTimes.length)
        : 0;
    
    document.getElementById('avgScanTime').textContent = avgScanTime + 'ms';
    document.getElementById('cameraFPS').textContent = Math.round(1000 / (avgScanTime || 100));
    
    // Estimate memory usage (rough calculation)
    const memUsage = Math.round((scanStats.scanTimes.length * 8) / 1024); // Rough estimate in KB
    document.getElementById('memoryUsage').textContent = memUsage + 'KB';
    
    // Battery impact based on scan frequency
    const batteryImpact = avgScanTime > 50 ? 'Medium' : 'Low';
    document.getElementById('batteryImpact').textContent = batteryImpact;
}

// Update statistics
function updateStats() {
    document.getElementById('totalScans').textContent = scanStats.totalScans;
    document.getElementById('successfulScans').textContent = scanStats.successfulScans;
    document.getElementById('todayScans').textContent = scanStats.todayScans;
    
    const accuracy = scanStats.totalScans > 0 
        ? Math.round((scanStats.successfulScans / scanStats.totalScans) * 100)
        : 0;
    document.getElementById('scanAccuracy').textContent = accuracy + '%';
}

// Add to scan history
function addToScanHistory(qrData, status, data = null) {
    const scanHistory = document.getElementById('scanHistory');
    const historyItem = document.createElement('div');
    historyItem.className = 'history-item';
    
    let statusIcon, statusColor, statusText, details = '';
    
    switch (status) {
        case 'detected':
            statusIcon = 'fa-qrcode';
            statusColor = 'text-info';
            statusText = 'Detected';
            break;
        case 'success':
            statusIcon = 'fa-check-circle';
            statusColor = 'text-success';
            statusText = 'Valid';
            if (data) {
                details = `<small class="text-muted d-block">Event: ${data.event_title}</small>`;
            }
            break;
        case 'processed':
            statusIcon = 'fa-check';
            statusColor = 'text-success';
            statusText = 'Processed';
            if (data) {
                details = `<small class="text-muted d-block">Attendance: ${data.attendance_time}</small>`;
            }
            break;
        case 'error':
            statusIcon = 'fa-exclamation-circle';
            statusColor = 'text-danger';
            statusText = 'Error';
            if (data && data.message) {
                details = `<small class="text-danger d-block">${data.message}</small>`;
            }
            break;
    }
    
    historyItem.innerHTML = `
        <div class="d-flex align-items-center">
            <i class="fas ${statusIcon} ${statusColor} me-2"></i>
            <div class="flex-grow-1">
                <div class="small fw-bold">${statusText}</div>
                <div class="small text-muted">${new Date().toLocaleTimeString()}</div>
                ${details}
            </div>
        </div>
    `;
    
    // Insert at the top
    if (scanHistory.firstChild && !scanHistory.firstChild.classList.contains('text-center')) {
        scanHistory.insertBefore(historyItem, scanHistory.firstChild);
    } else {
        scanHistory.innerHTML = '';
        scanHistory.appendChild(historyItem);
    }
    
    // Keep only last 10 items
    while (scanHistory.children.length > 10) {
        scanHistory.removeChild(scanHistory.lastChild);
    }
    
    // Save to localStorage for persistence
    saveToLocalStorage();
}

// Load scan history
function loadScanHistory() {
    try {
        const saved = localStorage.getItem('presenter_scan_history');
        if (saved) {
            const data = JSON.parse(saved);
            scanStats = { ...scanStats, ...data.stats };
            updateStats();
        }
    } catch (error) {
        console.log('No saved scan history');
    }
}

// Save to localStorage
function saveToLocalStorage() {
    try {
        const data = {
            stats: scanStats,
            timestamp: Date.now()
        };
        localStorage.setItem('presenter_scan_history', JSON.stringify(data));
    } catch (error) {
        console.log('Could not save scan history');
    }
}

// Clear history
function clearHistory() {
    if (confirm('Clear all scan history? This cannot be undone.')) {
        document.getElementById('scanHistory').innerHTML = `
            <div class="text-center py-4">
                <i class="fas fa-qrcode fa-3x text-muted mb-3"></i>
                <h6 class="text-muted">No Scans Yet</h6>
                <p class="text-muted small">Your scan history will appear here</p>
            </div>
        `;
        
        // Reset stats
        scanStats = {
            totalScans: 0,
            successfulScans: 0,
            todayScans: 0,
            scanTimes: []
        };
        
        updateStats();
        localStorage.removeItem('presenter_scan_history');
        showStatus('Scan history cleared', 'info');
    }
}

// Update current time
function updateCurrentTime() {
    document.getElementById('currentTime').textContent = new Date().toLocaleTimeString();
}

// Show status message
function showStatus(message, type = 'info') {
    const statusIndicator = document.getElementById('statusIndicator');
    const statusMessage = document.getElementById('statusMessage');
    
    statusIndicator.className = `status-indicator status-${type}`;
    statusMessage.textContent = message;
    statusIndicator.style.display = 'block';
    
    // Auto-hide after 5 seconds for non-error messages
    if (type !== 'error') {
        setTimeout(() => {
            statusIndicator.style.display = 'none';
        }, 5000);
    }
}

// Sound functions
function playBeepSound() {
    if (!soundEnabled) return;
    
    try {
        // Create audio context for beep sound
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        oscillator.frequency.setValueAtTime(800, audioContext.currentTime);
        oscillator.type = 'sine';
        
        gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.1);
        
        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.1);
    } catch (error) {
        console.log('Could not play sound');
    }
}

function playSuccessSound() {
    if (!soundEnabled) return;
    
    try {
        // Create success sound (higher pitch, longer duration)
        const audioContext = new (window.AudioContext || window.webkitAudioContext)();
        const oscillator = audioContext.createOscillator();
        const gainNode = audioContext.createGain();
        
        oscillator.connect(gainNode);
        gainNode.connect(audioContext.destination);
        
        oscillator.frequency.setValueAtTime(1200, audioContext.currentTime);
        oscillator.type = 'sine';
        
        gainNode.gain.setValueAtTime(0.3, audioContext.currentTime);
        gainNode.gain.exponentialRampToValueAtTime(0.01, audioContext.currentTime + 0.3);
        
        oscillator.start(audioContext.currentTime);
        oscillator.stop(audioContext.currentTime + 0.3);
    } catch (error) {
        console.log('Could not play success sound');
    }
}

// Mobile sidebar toggle
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    sidebar.classList.toggle('show');
}
    </script>
</body>
</html>