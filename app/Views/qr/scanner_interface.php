<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>QR Scanner - SNIA Attendance</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/jsqr@1.4.0/dist/jsQR.js"></script>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .scanner-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            max-width: 500px;
            margin: 0 auto;
        }

        .scanner-header {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
            color: white;
            padding: 20px;
            text-align: center;
            position: relative;
            overflow: hidden;
        }

        .scanner-header::before {
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

        .scanner-body {
            padding: 20px;
        }

        .camera-container {
            position: relative;
            background: #000;
            border-radius: 12px;
            overflow: hidden;
            margin-bottom: 20px;
            min-height: 300px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        #video {
            width: 100%;
            height: auto;
            max-height: 400px;
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

        .manual-input {
            display: none;
            margin-top: 15px;
        }

        .status-indicator {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 15px;
            display: none;
            font-weight: 500;
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

        .camera-controls {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
        }

        .camera-select {
            flex: 1;
        }

        .instructions {
            background: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
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

        .btn-camera {
            border-radius: 25px;
            padding: 8px 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
        }

        .btn-camera:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .btn-process {
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

        .btn-process:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
            color: white;
        }

        .btn-process:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
        }

        .loading-spinner {
            display: none;
        }

        .loading-spinner.show {
            display: inline-block;
        }

        .scan-history {
            background: #f8fafc;
            border-radius: 8px;
            padding: 15px;
            margin-top: 15px;
            max-height: 200px;
            overflow-y: auto;
        }

        .history-item {
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
            font-size: 0.85rem;
        }

        .history-item:last-child {
            border-bottom: none;
        }

        .user-info-card {
            background: linear-gradient(135deg, #f0f9ff, #e0f2fe);
            border: 1px solid #0ea5e9;
            border-radius: 12px;
            padding: 15px;
            margin: 15px 0;
        }

        @media (max-width: 768px) {
            .scanner-container {
                margin: 10px;
                border-radius: 15px;
            }
            
            .scanner-header {
                padding: 15px;
            }
            
            .scanner-body {
                padding: 15px;
            }
            
            .scanner-overlay {
                width: 150px;
                height: 150px;
            }
        }
    </style>
</head>
<body>
    <div class="container py-4">
        <div class="row justify-content-center">
            <div class="col-12">
                <div class="scanner-container">
                    <!-- Scanner Header -->
                    <div class="scanner-header">
                        <h4 class="mb-1" style="position: relative; z-index: 1;">
                            <i class="fas fa-qrcode me-2"></i>QR Code Scanner
                        </h4>
                        <p class="mb-0 opacity-90" style="position: relative; z-index: 1;">
                            Scan QR code untuk absensi event
                        </p>
                    </div>

                    <!-- Scanner Body -->
                    <div class="scanner-body">
                        <!-- User Info (if logged in) -->
                        <div id="userInfo" class="user-info-card" style="display: none;">
                            <div class="d-flex align-items-center">
                                <i class="fas fa-user-circle fa-2x text-primary me-3"></i>
                                <div>
                                    <div class="fw-bold" id="userName">Loading...</div>
                                    <small class="text-muted" id="userRole">Loading role...</small>
                                </div>
                            </div>
                        </div>

                        <!-- Status Indicator -->
                        <div id="statusIndicator" class="status-indicator">
                            <i class="fas fa-info-circle me-2"></i>
                            <span id="statusMessage">Initializing scanner...</span>
                        </div>

                        <!-- Camera Controls -->
                        <div class="camera-controls">
                            <select id="cameraSelect" class="form-select camera-select">
                                <option value="">Select Camera...</option>
                            </select>
                            <button id="startCamera" class="btn btn-success btn-camera">
                                <i class="fas fa-camera"></i>
                            </button>
                            <button id="stopCamera" class="btn btn-danger btn-camera" style="display: none;">
                                <i class="fas fa-stop"></i>
                            </button>
                            <button id="switchCamera" class="btn btn-info btn-camera" style="display: none;">
                                <i class="fas fa-sync"></i>
                            </button>
                        </div>

                        <!-- Camera Container -->
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
                            <div id="cameraPlaceholder" class="text-center text-white">
                                <i class="fas fa-camera fa-3x mb-3 opacity-50"></i>
                                <p class="mb-0">Click camera button to start scanning</p>
                                <small class="opacity-75">Make sure to allow camera access</small>
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
                        </div>

                        <!-- Process Button -->
                        <div id="processSection" style="display: none;">
                            <form id="attendanceForm">
                                <input type="hidden" id="qrToken" name="qr_token" value="">
                                <input type="hidden" id="securityToken" name="security_token" value="">
                                
                                <button type="submit" class="btn-process" id="processBtn">
                                    <i class="fas fa-check-circle me-2"></i>
                                    Process Attendance
                                    <span class="loading-spinner ms-2">
                                        <i class="fas fa-spinner fa-spin"></i>
                                    </span>
                                </button>
                            </form>
                        </div>

                        <!-- Manual Input Toggle -->
                        <div class="text-center mb-3">
                            <button class="btn btn-outline-primary btn-sm" onclick="toggleManualInput()">
                                <i class="fas fa-keyboard me-1"></i>Manual Input
                            </button>
                        </div>

                        <!-- Manual Input Form -->
                        <div id="manualInput" class="manual-input">
                            <form id="manualForm">
                                <div class="mb-3">
                                    <label for="manualQrToken" class="form-label">QR Token atau URL:</label>
                                    <input type="text" class="form-control" id="manualQrToken" 
                                           placeholder="Masukkan QR token atau URL...">
                                    <div class="form-text">
                                        Format: EVENT_1_20250903 atau URL lengkap
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="fas fa-check me-1"></i>Process Token
                                    <span class="loading-spinner ms-2">
                                        <i class="fas fa-spinner fa-spin"></i>
                                    </span>
                                </button>
                            </form>
                        </div>

                        <!-- Scan History -->
                        <div id="scanHistory" class="scan-history" style="display: none;">
                            <h6 class="mb-2">
                                <i class="fas fa-history me-2"></i>Recent Scans
                            </h6>
                            <div id="historyContent">
                                <!-- History items will be added here -->
                            </div>
                        </div>

                        <!-- Instructions -->
                        <div class="instructions">
                            <h6 class="mb-2"><i class="fas fa-info-circle me-2"></i>Instructions:</h6>
                            <ol class="mb-0 small">
                                <li>Login first if you haven't already</li>
                                <li>Select your camera and click start</li>
                                <li>Point camera at QR code within the green frame</li>
                                <li>Wait for automatic detection</li>
                                <li>Click "Process Attendance" when detected</li>
                                <li>Or use "Manual Input" if camera doesn't work</li>
                            </ol>
                        </div>

                        <!-- Action Links -->
                        <div class="row mt-3">
                            <div class="col-6">
                                <a href="<?= base_url('/') ?>" class="btn btn-outline-secondary btn-sm w-100">
                                    <i class="fas fa-home me-1"></i>Home
                                </a>
                            </div>
                            <div class="col-6">
                                <a href="<?= base_url('dashboard') ?>" class="btn btn-outline-primary btn-sm w-100">
                                    <i class="fas fa-tachometer-alt me-1"></i>Dashboard
                                </a>
                            </div>
                        </div>
                        
                        <!-- Current Time -->
                        <div class="text-center mt-3">
                            <small class="text-muted">
                                <i class="fas fa-clock me-1"></i>
                                <span id="currentTime"><?= date('d-m-Y H:i:s') ?></span>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Configuration
        const CONFIG = {
            BASE_URL: '<?= base_url() ?>',
            QR_PROCESS_URL: '<?= base_url('qr/process') ?>',
            SCAN_HISTORY_KEY: 'qr_scan_history',
            MAX_HISTORY_ITEMS: 5
        };

        class EnhancedQRScanner {
            constructor() {
                this.video = document.getElementById('video');
                this.canvas = document.getElementById('canvas');
                this.ctx = this.canvas.getContext('2d');
                this.cameraSelect = document.getElementById('cameraSelect');
                this.scanning = false;
                this.stream = null;
                this.animationFrame = null;
                this.detectedQR = null;
                this.cameras = [];
                this.currentCameraIndex = 0;
                this.scanHistory = this.loadScanHistory();
                
                this.init();
            }

            async init() {
                this.showStatus('Initializing...', 'warning');
                await this.checkUserStatus();
                await this.getCameras();
                this.setupEventListeners();
                this.updateScanHistory();
                this.startClock();
                this.showStatus('Ready to scan', 'success');
            }

            async checkUserStatus() {
                try {
                    // Check if user is logged in via API or session check
                    const response = await fetch(`${CONFIG.BASE_URL}api/v1/user/status`, {
                        headers: { 'X-Requested-With': 'XMLHttpRequest' }
                    });
                    
                    if (response.ok) {
                        const userData = await response.json();
                        if (userData.logged_in) {
                            this.showUserInfo(userData.user);
                        }
                    }
                } catch (error) {
                    console.log('User status check failed:', error);
                    // Not critical, continue without user info
                }
            }

            showUserInfo(user) {
                const userInfo = document.getElementById('userInfo');
                const userName = document.getElementById('userName');
                const userRole = document.getElementById('userRole');
                
                userName.textContent = user.nama_lengkap || user.name;
                userRole.textContent = `Role: ${user.role.charAt(0).toUpperCase() + user.role.slice(1)}`;
                userInfo.style.display = 'block';
            }

            async getCameras() {
                try {
                    await navigator.mediaDevices.getUserMedia({ video: true });
                    
                    const devices = await navigator.mediaDevices.enumerateDevices();
                    this.cameras = devices.filter(device => device.kind === 'videoinput');
                    
                    this.cameraSelect.innerHTML = '<option value="">Select Camera...</option>';
                    
                    this.cameras.forEach((device, index) => {
                        const option = document.createElement('option');
                        option.value = device.deviceId;
                        option.textContent = device.label || `Camera ${index + 1}`;
                        this.cameraSelect.appendChild(option);
                    });

                    if (this.cameras.length > 0) {
                        this.cameraSelect.value = this.cameras[0].deviceId;
                        this.showStatus('Cameras loaded. Click start to begin.', 'success');
                        
                        // Show switch camera button if multiple cameras
                        if (this.cameras.length > 1) {
                            document.getElementById('switchCamera').style.display = 'inline-block';
                        }
                    } else {
                        this.showStatus('No cameras found. Use manual input.', 'warning');
                    }

                } catch (error) {
                    console.error('Camera access error:', error);
                    this.showStatus('Camera access denied. Use manual input.', 'error');
                }
            }

            setupEventListeners() {
                // Camera controls
                document.getElementById('startCamera').addEventListener('click', () => {
                    this.startScanning();
                });

                document.getElementById('stopCamera').addEventListener('click', () => {
                    this.stopScanning();
                });

                document.getElementById('switchCamera').addEventListener('click', () => {
                    this.switchCamera();
                });

                // Forms
                document.getElementById('attendanceForm').addEventListener('submit', (e) => {
                    e.preventDefault();
                    this.processAttendance();
                });

                document.getElementById('manualForm').addEventListener('submit', (e) => {
                    e.preventDefault();
                    this.processManualInput();
                });

                // Auto-resize canvas
                window.addEventListener('resize', () => {
                    this.resizeCanvas();
                });

                // Handle page visibility change
                document.addEventListener('visibilitychange', () => {
                    if (document.hidden && this.scanning) {
                        this.stopScanning();
                    }
                });
            }

            async startScanning() {
                const selectedCamera = this.cameraSelect.value;
                
                if (!selectedCamera) {
                    this.showStatus('Please select a camera first.', 'warning');
                    return;
                }

                try {
                    const constraints = {
                        video: {
                            deviceId: selectedCamera,
                            facingMode: 'environment',
                            width: { ideal: 640 },
                            height: { ideal: 480 }
                        }
                    };

                    this.stream = await navigator.mediaDevices.getUserMedia(constraints);
                    this.video.srcObject = this.stream;
                    
                    this.video.onloadedmetadata = () => {
                        this.resizeCanvas();
                        this.video.style.display = 'block';
                        document.getElementById('cameraPlaceholder').style.display = 'none';
                        document.getElementById('scannerOverlay').style.display = 'block';
                        
                        document.getElementById('startCamera').style.display = 'none';
                        document.getElementById('stopCamera').style.display = 'inline-block';
                        
                        this.scanning = true;
                        this.scan();
                        
                        this.showStatus('Scanning... Point camera at QR code.', 'success');
                    };

                } catch (error) {
                    console.error('Camera start error:', error);
                    this.showStatus('Failed to start camera: ' + error.message, 'error');
                }
            }

            stopScanning() {
                this.scanning = false;
                
                if (this.animationFrame) {
                    cancelAnimationFrame(this.animationFrame);
                }

                if (this.stream) {
                    this.stream.getTracks().forEach(track => track.stop());
                    this.stream = null;
                }

                this.video.style.display = 'none';
                this.video.srcObject = null;
                
                document.getElementById('cameraPlaceholder').style.display = 'block';
                document.getElementById('scannerOverlay').style.display = 'none';
                
                document.getElementById('startCamera').style.display = 'inline-block';
                document.getElementById('stopCamera').style.display = 'none';
                
                this.showStatus('Camera stopped.', 'warning');
            }

            switchCamera() {
                if (this.cameras.length > 1) {
                    this.currentCameraIndex = (this.currentCameraIndex + 1) % this.cameras.length;
                    this.cameraSelect.value = this.cameras[this.currentCameraIndex].deviceId;
                    
                    if (this.scanning) {
                        this.stopScanning();
                        setTimeout(() => this.startScanning(), 500);
                    }
                }
            }

            resizeCanvas() {
                if (this.video.videoWidth && this.video.videoHeight) {
                    this.canvas.width = this.video.videoWidth;
                    this.canvas.height = this.video.videoHeight;
                }
            }

            scan() {
                if (!this.scanning) return;

                if (this.video.readyState === this.video.HAVE_ENOUGH_DATA) {
                    this.canvas.width = this.video.videoWidth;
                    this.canvas.height = this.video.videoHeight;
                    
                    this.ctx.drawImage(this.video, 0, 0, this.canvas.width, this.canvas.height);
                    
                    const imageData = this.ctx.getImageData(0, 0, this.canvas.width, this.canvas.height);
                    
                    if (typeof jsQR !== 'undefined') {
                        const code = jsQR(imageData.data, imageData.width, imageData.height);
                        
                        if (code) {
                            this.handleQRDetection(code.data);
                            return;
                        }
                    }
                }

                this.animationFrame = requestAnimationFrame(() => this.scan());
            }

            handleQRDetection(qrData) {
                console.log('QR Code detected:', qrData);
                
                if (this.validateQRFormat(qrData)) {
                    this.detectedQR = qrData;
                    this.showDetectedQR(qrData);
                    this.addToHistory(qrData);
                    this.stopScanning();
                    this.showStatus('QR Code detected successfully! Click Process Attendance.', 'success');
                } else {
                    this.showStatus('Invalid QR code format detected. Continuing scan...', 'warning');
                    this.animationFrame = requestAnimationFrame(() => this.scan());
                }
            }

            validateQRFormat(qrData) {
                // URL format
                if (qrData.includes('/qr/EVENT_') || qrData.includes('/qr/')) {
                    return true;
                }
                
                // Direct token formats
                const patterns = [
                    /^EVENT_\d+_[a-z]+_[a-z]+_\d{8}_[a-f0-9]+$/i, // Standard
                    /^EVENT_\d+_\d{8}$/i, // Simple
                    /^(ADMIN|MANUAL|BULK)_\d+_\d{8}/i, // Admin
                    /^\d+$/ // Numeric
                ];
                
                return patterns.some(pattern => pattern.test(qrData));
            }

            showDetectedQR(qrData) {
                const detectedQRDiv = document.getElementById('detectedQR');
                const qrContentDiv = document.getElementById('qrContent');
                const qrDetailsDiv = document.getElementById('qrDetails');
                const processSection = document.getElementById('processSection');
                
                // Extract token from URL if needed
                let displayData = qrData;
                let qrToken = qrData;
                
                if (qrData.includes('/qr/')) {
                    const urlParts = qrData.split('/qr/');
                    qrToken = urlParts[urlParts.length - 1];
                    displayData = qrToken;
                }
                
                qrContentDiv.textContent = displayData;
                
                // Parse and show details if possible
                const details = this.parseQRDetails(qrToken);
                qrDetailsDiv.textContent = details;
                
                // Set form values
                document.getElementById('qrToken').value = qrToken;
                document.getElementById('securityToken').value = this.generateSecurityToken();
                
                detectedQRDiv.style.display = 'block';
                processSection.style.display = 'block';
            }

            parseQRDetails(qrToken) {
                try {
                    if (qrToken.startsWith('EVENT_')) {
                        const parts = qrToken.split('_');
                        if (parts.length >= 3) {
                            const eventId = parts[1];
                            const date = parts[parts.length - 2];
                            
                            if (date && date.length === 8) {
                                const formattedDate = `${date.substring(6,8)}-${date.substring(4,6)}-${date.substring(0,4)}`;
                                return `Event ID: ${eventId}, Date: ${formattedDate}`;
                            }
                            return `Event ID: ${eventId}`;
                        }
                    }
                    return 'QR token detected';
                } catch (error) {
                    return 'QR token detected';
                }
            }

            generateSecurityToken() {
                const timestamp = Date.now();
                const random = Math.random().toString(36).substring(2);
                return btoa(`${timestamp}_${random}`);
            }

            hideDetectedQR() {
                document.getElementById('detectedQR').style.display = 'none';
                document.getElementById('processSection').style.display = 'none';
                this.detectedQR = null;
            }

            processManualInput() {
                const token = document.getElementById('manualQrToken').value.trim();
                
                if (!token) {
                    this.showStatus('Please enter a QR token.', 'warning');
                    return;
                }

                if (this.validateQRFormat(token)) {
                    let qrToken = token;
                    if (token.includes('/qr/')) {
                        const urlParts = token.split('/qr/');
                        qrToken = urlParts[urlParts.length - 1];
                    }
                    
                    this.detectedQR = qrToken;
                    this.showDetectedQR(token);
                    this.addToHistory(token);
                    
                    // Auto-process for manual input
                    setTimeout(() => this.processAttendance(), 500);
                } else {
                    this.showStatus('Invalid QR token format.', 'error');
                }
            }

            async processAttendance() {
                const processBtn = document.getElementById('processBtn');
                const spinner = processBtn.querySelector('.loading-spinner');
                
                processBtn.disabled = true;
                spinner.classList.add('show');
                
                try {
                    const formData = new FormData(document.getElementById('attendanceForm'));
                    
                    const response = await fetch(CONFIG.QR_PROCESS_URL, {
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
                        this.showSuccessModal(data);
                        this.hideDetectedQR();
                        this.showStatus('Attendance processed successfully!', 'success');
                    } else {
                        if (data.need_login) {
                            if (confirm('You need to login first. Redirect to login page?')) {
                                window.location.href = `${CONFIG.BASE_URL}auth/login?redirect=${encodeURIComponent(window.location.href)}`;
                            }
                            return;
                        }
                        
                        this.showErrorModal(data.message);
                        this.showStatus('Failed: ' + data.message, 'error');
                    }

                } catch (error) {
                    console.error('Attendance processing error:', error);
                    this.showErrorModal('Network error. Please check your connection and try again.');
                    this.showStatus('Network error occurred.', 'error');
                } finally {
                    processBtn.disabled = false;
                    spinner.classList.remove('show');
                }
            }

            showSuccessModal(data) {
                // Create and show success modal
                const modalHtml = `
                    <div class="modal fade" id="dynamicSuccessModal" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header bg-success text-white">
                                    <h5 class="modal-title">
                                        <i class="fas fa-check-circle me-2"></i>Success!
                                    </h5>
                                </div>
                                <div class="modal-body text-center">
                                    <i class="fas fa-check-circle fa-4x text-success mb-4"></i>
                                    <h4 class="text-success mb-4">Attendance Recorded!</h4>
                                    <div class="text-start">
                                        <div class="row mb-2">
                                            <div class="col-4"><strong>Name:</strong></div>
                                            <div class="col-8">${data.data?.participant_name || 'N/A'}</div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-4"><strong>Event:</strong></div>
                                            <div class="col-8">${data.data?.event_title || 'N/A'}</div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-4"><strong>Time:</strong></div>
                                            <div class="col-8">${data.data?.attendance_date || ''} ${data.data?.attendance_time || ''}</div>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-success" onclick="scanAnother()">Scan Another</button>
                                    <a href="${CONFIG.BASE_URL}dashboard" class="btn btn-outline-success">Dashboard</a>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                document.body.insertAdjacentHTML('beforeend', modalHtml);
                const modal = new bootstrap.Modal(document.getElementById('dynamicSuccessModal'));
                modal.show();
                
                // Remove modal from DOM when hidden
                document.getElementById('dynamicSuccessModal').addEventListener('hidden.bs.modal', function() {
                    this.remove();
                });
            }

            showErrorModal(message) {
                const modalHtml = `
                    <div class="modal fade" id="dynamicErrorModal" tabindex="-1">
                        <div class="modal-dialog modal-dialog-centered">
                            <div class="modal-content">
                                <div class="modal-header bg-danger text-white">
                                    <h5 class="modal-title">
                                        <i class="fas fa-exclamation-circle me-2"></i>Error
                                    </h5>
                                </div>
                                <div class="modal-body">
                                    <div class="text-center mb-3">
                                        <i class="fas fa-exclamation-circle fa-3x text-danger mb-3"></i>
                                    </div>
                                    <div class="alert alert-danger">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        ${message}
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-danger" data-bs-dismiss="modal">Close</button>
                                    <button type="button" class="btn btn-outline-danger" onclick="tryAgain()">Try Again</button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                
                document.body.insertAdjacentHTML('beforeend', modalHtml);
                const modal = new bootstrap.Modal(document.getElementById('dynamicErrorModal'));
                modal.show();
                
                document.getElementById('dynamicErrorModal').addEventListener('hidden.bs.modal', function() {
                    this.remove();
                });
            }

            showStatus(message, type) {
                const indicator = document.getElementById('statusIndicator');
                const messageSpan = document.getElementById('statusMessage');
                
                indicator.className = `status-indicator status-${type}`;
                messageSpan.textContent = message;
                indicator.style.display = 'block';
                
                if (type !== 'error') {
                    setTimeout(() => {
                        indicator.style.display = 'none';
                    }, 5000);
                }
            }

            addToHistory(qrData) {
                const historyItem = {
                    qr: qrData,
                    timestamp: new Date().toLocaleString('id-ID'),
                    time: Date.now()
                };
                
                this.scanHistory.unshift(historyItem);
                this.scanHistory = this.scanHistory.slice(0, CONFIG.MAX_HISTORY_ITEMS);
                
                this.saveScanHistory();
                this.updateScanHistory();
            }

            updateScanHistory() {
                const historyDiv = document.getElementById('scanHistory');
                const contentDiv = document.getElementById('historyContent');
                
                if (this.scanHistory.length > 0) {
                    contentDiv.innerHTML = this.scanHistory.map(item => `
                        <div class="history-item">
                            <div class="d-flex justify-content-between align-items-start">
                                <div style="flex: 1; min-width: 0;">
                                    <div style="font-family: monospace; font-size: 0.8rem; word-break: break-all;">
                                        ${item.qr.substring(0, 40)}${item.qr.length > 40 ? '...' : ''}
                                    </div>
                                    <small class="text-muted">${item.timestamp}</small>
                                </div>
                                <button class="btn btn-sm btn-outline-primary ms-2" onclick="reuseQR('${item.qr}')">
                                    <i class="fas fa-redo"></i>
                                </button>
                            </div>
                        </div>
                    `).join('');
                    
                    historyDiv.style.display = 'block';
                } else {
                    historyDiv.style.display = 'none';
                }
            }

            loadScanHistory() {
                try {
                    const stored = localStorage.getItem(CONFIG.SCAN_HISTORY_KEY);
                    return stored ? JSON.parse(stored) : [];
                } catch (error) {
                    return [];
                }
            }

            saveScanHistory() {
                try {
                    localStorage.setItem(CONFIG.SCAN_HISTORY_KEY, JSON.stringify(this.scanHistory));
                } catch (error) {
                    console.log('Failed to save scan history:', error);
                }
            }

            startClock() {
                const updateTime = () => {
                    const now = new Date();
                    const timeStr = now.toLocaleString('id-ID');
                    document.getElementById('currentTime').textContent = timeStr;
                };
                
                updateTime();
                setInterval(updateTime, 1000);
            }
        }

        // Global functions
        function toggleManualInput() {
            const manualInput = document.getElementById('manualInput');
            const isVisible = manualInput.style.display === 'block';
            
            manualInput.style.display = isVisible ? 'none' : 'block';
            
            if (!isVisible) {
                document.getElementById('manualQrToken').focus();
            }
        }

        function scanAnother() {
            if (window.enhancedScanner) {
                window.enhancedScanner.hideDetectedQR();
                document.getElementById('manualQrToken').value = '';
                window.enhancedScanner.showStatus('Ready for next scan.', 'success');
                
                // Close any open modals
                const modals = document.querySelectorAll('.modal.show');
                modals.forEach(modal => {
                    bootstrap.Modal.getInstance(modal).hide();
                });
            }
        }

        function tryAgain() {
            if (window.enhancedScanner) {
                window.enhancedScanner.hideDetectedQR();
                document.getElementById('manualQrToken').value = '';
                window.enhancedScanner.showStatus('Ready to try again.', 'success');
                
                const modals = document.querySelectorAll('.modal.show');
                modals.forEach(modal => {
                    bootstrap.Modal.getInstance(modal).hide();
                });
            }
        }

        function reuseQR(qrData) {
            if (window.enhancedScanner) {
                window.enhancedScanner.showDetectedQR(qrData);
                window.enhancedScanner.showStatus('QR from history loaded.', 'success');
            }
        }

        // Initialize scanner when page loads
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof jsQR === 'undefined') {
                console.warn('jsQR library not loaded');
                const statusIndicator = document.getElementById('statusIndicator');
                const statusMessage = document.getElementById('statusMessage');
                statusIndicator.style.display = 'block';
                statusMessage.textContent = 'QR scanning library not available. Use manual input.';
                statusIndicator.className = 'status-indicator status-warning';
            }
            
            window.enhancedScanner = new EnhancedQRScanner();
        });

        // Cleanup on page unload
        window.addEventListener('beforeunload', function() {
            if (window.enhancedScanner && window.enhancedScanner.scanning) {
                window.enhancedScanner.stopScanning();
            }
        });
    </script>
</body>
</html>