<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pembayaran Event - SNIA Presenter</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --success-color: #10b981;
            --warning-color: #f59e0b;
            --danger-color: #ef4444;
            --purple-color: #8b5cf6;
        }

        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }

        .main-content {
            background: white;
            border-radius: 20px;
            min-height: 100vh;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
        }

        .payment-form-card {
            background: white;
            border-radius: 16px;
            padding: 30px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            border: 1px solid rgba(255,255,255,0.2);
            margin-bottom: 24px;
        }

        .header-section {
            background: linear-gradient(135deg, var(--purple-color) 0%, #7c3aed 100%);
            color: white;
            padding: 30px;
            border-radius: 16px;
            margin-bottom: 30px;
        }

        .payment-method-card {
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 16px;
        }

        .payment-method-card:hover {
            border-color: var(--primary-color);
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.15);
        }

        .payment-method-card.selected {
            border-color: var(--primary-color);
            background: rgba(37, 99, 235, 0.05);
        }

        .payment-method-card input[type="radio"] {
            display: none;
        }

        .method-icon {
            width: 60px;
            height: 60px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 12px;
        }

        .event-summary-card {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border: 1px solid #0284c7;
            border-radius: 12px;
            padding: 24px;
            margin-bottom: 24px;
        }

        .price-breakdown {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
        }

        .price-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border-bottom: 1px solid #e2e8f0;
        }

        .price-row:last-child {
            border-bottom: none;
            font-weight: 600;
            font-size: 1.1rem;
            margin-top: 8px;
            padding-top: 16px;
            border-top: 2px solid #e2e8f0;
        }

        .voucher-section {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            border: 1px solid #f59e0b;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
        }

        .file-upload-area {
            border: 2px dashed #d1d5db;
            border-radius: 12px;
            padding: 40px 20px;
            text-align: center;
            transition: all 0.3s ease;
            cursor: pointer;
            background: #f9fafb;
        }

        .file-upload-area:hover {
            border-color: var(--primary-color);
            background: rgba(37, 99, 235, 0.05);
        }

        .file-upload-area.dragover {
            border-color: var(--success-color);
            background: rgba(16, 185, 129, 0.1);
        }

        .submit-button {
            background: linear-gradient(135deg, var(--success-color) 0%, #059669 100%);
            border: none;
            border-radius: 12px;
            padding: 16px 32px;
            color: white;
            font-weight: 600;
            font-size: 1.1rem;
            transition: all 0.3s ease;
        }

        .submit-button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.3);
        }

        .submit-button:disabled {
            background: #9ca3af;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .voucher-input-group {
            position: relative;
        }

        .voucher-check-icon {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            z-index: 10;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.25);
        }

        .method-details {
            background: rgba(0,0,0,0.02);
            border-radius: 8px;
            padding: 12px;
            margin-top: 12px;
            display: none;
        }

        .payment-method-card.selected .method-details {
            display: block;
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
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="main-content p-4">
                    <!-- Header -->
                    <div class="header-section">
                        <div class="d-flex align-items-center mb-3">
                            <a href="<?= site_url('presenter/pembayaran') ?>" class="btn btn-light btn-sm me-3">
                                <i class="fas fa-arrow-left"></i>
                            </a>
                            <div>
                                <h1 class="mb-0">
                                    <i class="fas fa-credit-card me-3"></i>Pembayaran Event
                                </h1>
                                <p class="mb-0 opacity-75">Lakukan pembayaran untuk mengakses fitur event</p>
                            </div>
                        </div>
                    </div>

                    <!-- Flash Messages -->
                    <?php if (session()->getFlashdata('error')): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <?= session()->getFlashdata('error') ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <?php if (session()->has('errors')): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <strong>Terjadi kesalahan:</strong>
                        <ul class="mb-0 mt-2">
                            <?php foreach (session('errors') as $error): ?>
                            <li><?= esc($error) ?></li>
                            <?php endforeach; ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <!-- Event Summary -->
                    <div class="event-summary-card">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h5 class="mb-2 text-primary">
                                    <i class="fas fa-calendar-alt me-2"></i>
                                    <?= esc($event['title']) ?>
                                </h5>
                                <div class="row text-muted">
                                    <div class="col-sm-6">
                                        <small>
                                            <i class="fas fa-calendar me-1"></i>
                                            <?= date('d F Y', strtotime($event['event_date'])) ?>
                                        </small>
                                    </div>
                                    <div class="col-sm-6">
                                        <small>
                                            <i class="fas fa-clock me-1"></i>
                                            <?= date('H:i', strtotime($event['event_time'])) ?> WIB
                                        </small>
                                    </div>
                                    <div class="col-sm-6 mt-2">
                                        <small>
                                            <i class="fas fa-map-marker-alt me-1"></i>
                                            <?= esc($event['location']) ?>
                                        </small>
                                    </div>
                                    <div class="col-sm-6 mt-2">
                                        <small>
                                            <i class="fas fa-user-tie me-1"></i>
                                            Presenter (Offline)
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 text-end">
                                <div class="badge bg-success mb-2">Abstrak Diterima</div>
                                <div class="h4 text-primary mb-0">
                                    Rp <?= number_format($base_price, 0, ',', '.') ?>
                                </div>
                                <small class="text-muted">Biaya Presenter</small>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Form -->
                    <form action="<?= site_url('presenter/pembayaran/store') ?>" method="POST" enctype="multipart/form-data" id="paymentForm">
                        <?= csrf_field() ?>
                        <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                        <input type="hidden" name="original_amount" value="<?= $base_price ?>" id="originalAmount">
                        <input type="hidden" name="jumlah" value="<?= $base_price ?>" id="finalAmount">

                        <!-- Voucher Section -->
                        <div class="payment-form-card">
                            <h5 class="mb-3">
                                <i class="fas fa-ticket-alt text-warning me-2"></i>
                                Kode Voucher (Opsional)
                            </h5>
                            
                            <div class="voucher-section">
                                <div class="row align-items-center">
                                    <div class="col-md-8">
                                        <div class="voucher-input-group">
                                            <input type="text" class="form-control" name="voucher_code" id="voucherCode" 
                                                   placeholder="Masukkan kode voucher" style="padding-right: 50px;">
                                            <div class="voucher-check-icon">
                                                <i class="fas fa-check-circle text-success" id="voucherValid" style="display: none;"></i>
                                                <i class="fas fa-times-circle text-danger" id="voucherInvalid" style="display: none;"></i>
                                                <div class="spinner-border spinner-border-sm text-primary" id="voucherLoading" style="display: none;"></div>
                                            </div>
                                        </div>
                                        <small class="text-muted">Gunakan voucher untuk mendapat diskon</small>
                                    </div>
                                    <div class="col-md-4">
                                        <button type="button" class="btn btn-outline-primary w-100" id="checkVoucherBtn">
                                            <i class="fas fa-search me-2"></i>Cek Voucher
                                        </button>
                                    </div>
                                </div>
                                
                                <div id="voucherInfo" class="mt-3" style="display: none;">
                                    <div class="alert alert-success mb-0">
                                        <div class="d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong id="voucherName"></strong>
                                                <div id="voucherDescription" class="small"></div>
                                            </div>
                                            <div class="text-end">
                                                <div class="text-success fw-bold" id="voucherDiscount"></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Price Breakdown -->
                        <div class="payment-form-card">
                            <h5 class="mb-3">
                                <i class="fas fa-calculator text-info me-2"></i>
                                Rincian Pembayaran
                            </h5>
                            
                            <div class="price-breakdown">
                                <div class="price-row">
                                    <span>Biaya Event (Presenter)</span>
                                    <span>Rp <span id="basePrice"><?= number_format($base_price, 0, ',', '.') ?></span></span>
                                </div>
                                <div class="price-row" id="discountRow" style="display: none;">
                                    <span class="text-success">Diskon Voucher</span>
                                    <span class="text-success">- Rp <span id="discountAmount">0</span></span>
                                </div>
                                <div class="price-row">
                                    <span class="text-primary fw-bold">Total Pembayaran</span>
                                    <span class="text-primary fw-bold">Rp <span id="totalPrice"><?= number_format($base_price, 0, ',', '.') ?></span></span>
                                </div>
                            </div>
                        </div>

                        <!-- Payment Method Selection -->
                        <div class="payment-form-card">
                            <h5 class="mb-3">
                                <i class="fas fa-credit-card text-primary me-2"></i>
                                Pilih Metode Pembayaran
                            </h5>

                            <?php foreach ($payment_methods as $method_key => $method): ?>
                            <div class="payment-method-card" data-method="<?= $method_key ?>">
                                <input type="radio" name="metode" value="<?= $method_key ?>" id="method_<?= $method_key ?>" required>
                                <label for="method_<?= $method_key ?>" class="w-100 cursor-pointer">
                                    <div class="row align-items-center">
                                        <div class="col-auto">
                                            <div class="method-icon bg-primary bg-opacity-10">
                                                <i class="<?= $method['icon'] ?> fa-2x text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="col">
                                            <h6 class="mb-1"><?= esc($method['name']) ?></h6>
                                            <p class="text-muted mb-0"><?= esc($method['description']) ?></p>
                                        </div>
                                        <div class="col-auto">
                                            <i class="fas fa-circle text-muted" id="radio_<?= $method_key ?>"></i>
                                        </div>
                                    </div>
                                    
                                    <div class="method-details">
                                        <strong class="d-block mb-2">Informasi Pembayaran:</strong>
                                        <?php foreach ($method['details'] as $detail): ?>
                                        <div class="mb-1">
                                            <i class="fas fa-info-circle text-primary me-2"></i>
                                            <?= esc($detail) ?>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- File Upload -->
                        <div class="payment-form-card">
                            <h5 class="mb-3">
                                <i class="fas fa-upload text-success me-2"></i>
                                Upload Bukti Pembayaran
                            </h5>
                            
                            <div class="file-upload-area" onclick="document.getElementById('buktiFile').click();">
                                <input type="file" name="bukti_bayar" id="buktiFile" accept=".jpg,.jpeg,.png,.pdf" required style="display: none;">
                                <div id="uploadContent">
                                    <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                    <h6 class="text-muted">Klik untuk upload atau drag & drop file</h6>
                                    <small class="text-muted">
                                        Format: JPG, PNG, atau PDF. Maksimal 5MB
                                    </small>
                                </div>
                                <div id="fileInfo" style="display: none;">
                                    <i class="fas fa-file fa-2x text-success mb-2"></i>
                                    <div class="fw-bold" id="fileName"></div>
                                    <small class="text-muted" id="fileSize"></small>
                                </div>
                            </div>
                            
                            <div class="mt-3">
                                <small class="text-muted">
                                    <i class="fas fa-info-circle me-1"></i>
                                    <strong>Tips:</strong> Pastikan bukti pembayaran jelas terbaca dengan informasi nominal dan waktu transfer yang sesuai.
                                </small>
                            </div>
                        </div>

                        <!-- User Information (readonly) -->
                        <div class="payment-form-card">
                            <h5 class="mb-3">
                                <i class="fas fa-user text-secondary me-2"></i>
                                Informasi Pembayar
                            </h5>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label text-muted">Nama Lengkap</label>
                                        <input type="text" class="form-control" value="<?= esc($user['nama_lengkap']) ?>" readonly>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label text-muted">Email</label>
                                        <input type="email" class="form-control" value="<?= esc($user['email']) ?>" readonly>
                                    </div>
                                </div>
                                <?php if (!empty($user['no_hp'])): ?>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label text-muted">No. HP</label>
                                        <input type="text" class="form-control" value="<?= esc($user['no_hp']) ?>" readonly>
                                    </div>
                                </div>
                                <?php endif; ?>
                                <?php if (!empty($user['institusi'])): ?>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label class="form-label text-muted">Institusi</label>
                                        <input type="text" class="form-control" value="<?= esc($user['institusi']) ?>" readonly>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Submit Button -->
                        <div class="payment-form-card">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">Total Pembayaran</h6>
                                    <div class="h4 text-primary mb-0">
                                        Rp <span id="finalTotal"><?= number_format($base_price, 0, ',', '.') ?></span>
                                    </div>
                                </div>
                                <div>
                                    <button type="submit" class="submit-button" id="submitBtn" disabled>
                                        <i class="fas fa-credit-card me-2"></i>
                                        Proses Pembayaran
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mt-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="agreementCheck" required>
                                    <label class="form-check-label text-muted" for="agreementCheck">
                                        Saya menyetujui bahwa informasi yang saya berikan sudah benar dan pembayaran akan diproses sesuai dengan ketentuan yang berlaku.
                                    </label>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="text-center text-white">
            <div class="spinner-border mb-3" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <div>Memproses pembayaran...</div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('paymentForm');
            const submitBtn = document.getElementById('submitBtn');
            const agreementCheck = document.getElementById('agreementCheck');
            const buktiFile = document.getElementById('buktiFile');
            const voucherCode = document.getElementById('voucherCode');
            
            let selectedMethod = null;
            let fileSelected = false;
            let voucherValid = false;
            let currentVoucher = null;

            // Payment method selection
            const methodCards = document.querySelectorAll('.payment-method-card');
            methodCards.forEach(card => {
                card.addEventListener('click', function() {
                    const method = this.dataset.method;
                    const radio = document.getElementById('method_' + method);
                    
                    // Unselect all
                    methodCards.forEach(c => {
                        c.classList.remove('selected');
                        const radioIcon = c.querySelector('[id^="radio_"]');
                        radioIcon.className = 'fas fa-circle text-muted';
                    });
                    
                    // Select current
                    this.classList.add('selected');
                    radio.checked = true;
                    const radioIcon = document.getElementById('radio_' + method);
                    radioIcon.className = 'fas fa-check-circle text-primary';
                    
                    selectedMethod = method;
                    checkFormValid();
                });
            });

            // File upload handling
            buktiFile.addEventListener('change', function() {
                const file = this.files[0];
                if (file) {
                    displayFileInfo(file);
                    fileSelected = true;
                } else {
                    hideFileInfo();
                    fileSelected = false;
                }
                checkFormValid();
            });

            // Drag and drop
            const uploadArea = document.querySelector('.file-upload-area');
            
            uploadArea.addEventListener('dragover', function(e) {
                e.preventDefault();
                this.classList.add('dragover');
            });
            
            uploadArea.addEventListener('dragleave', function() {
                this.classList.remove('dragover');
            });
            
            uploadArea.addEventListener('drop', function(e) {
                e.preventDefault();
                this.classList.remove('dragover');
                
                const files = e.dataTransfer.files;
                if (files.length > 0) {
                    buktiFile.files = files;
                    displayFileInfo(files[0]);
                    fileSelected = true;
                    checkFormValid();
                }
            });

            // Voucher validation
            document.getElementById('checkVoucherBtn').addEventListener('click', function() {
                const code = voucherCode.value.trim();
                if (!code) {
                    showVoucherError('Masukkan kode voucher terlebih dahulu');
                    return;
                }
                validateVoucher(code);
            });

            voucherCode.addEventListener('keypress', function(e) {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    document.getElementById('checkVoucherBtn').click();
                }
            });

            // Agreement check
            agreementCheck.addEventListener('change', function() {
                checkFormValid();
            });

            // Form submission
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                if (!selectedMethod || !fileSelected || !agreementCheck.checked) {
                    alert('Harap lengkapi semua field yang diperlukan');
                    return;
                }

                // Show loading
                showLoading();
                
                // Submit form
                this.submit();
            });

            function displayFileInfo(file) {
                const fileName = document.getElementById('fileName');
                const fileSize = document.getElementById('fileSize');
                const uploadContent = document.getElementById('uploadContent');
                const fileInfo = document.getElementById('fileInfo');

                fileName.textContent = file.name;
                fileSize.textContent = formatFileSize(file.size);
                
                uploadContent.style.display = 'none';
                fileInfo.style.display = 'block';
            }

            function hideFileInfo() {
                const uploadContent = document.getElementById('uploadContent');
                const fileInfo = document.getElementById('fileInfo');
                
                uploadContent.style.display = 'block';
                fileInfo.style.display = 'none';
            }

            function formatFileSize(bytes) {
                if (bytes === 0) return '0 B';
                const k = 1024;
                const sizes = ['B', 'KB', 'MB', 'GB'];
                const i = Math.floor(Math.log(bytes) / Math.log(k));
                return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
            }

            function validateVoucher(code) {
                showVoucherLoading(true);
                
                fetch('<?= site_url('presenter/pembayaran/validate-voucher') ?>', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                        'X-Requested-With': 'XMLHttpRequest'
                    },
                    body: `voucher_code=${encodeURIComponent(code)}&event_id=<?= $event['id'] ?>&<?= csrf_token() ?>=<?= csrf_hash() ?>`
                })
                .then(response => response.json())
                .then(data => {
                    showVoucherLoading(false);
                    
                    if (data.valid) {
                        showVoucherSuccess(data.voucher);
                        applyVoucherDiscount(data.voucher);
                        voucherValid = true;
                        currentVoucher = data.voucher;
                    } else {
                        showVoucherError(data.message);
                        removeVoucherDiscount();
                        voucherValid = false;
                        currentVoucher = null;
                    }
                })
                .catch(error => {
                    showVoucherLoading(false);
                    showVoucherError('Terjadi kesalahan saat validasi voucher');
                    console.error('Voucher validation error:', error);
                });
            }

            function showVoucherLoading(show) {
                const loading = document.getElementById('voucherLoading');
                const valid = document.getElementById('voucherValid');
                const invalid = document.getElementById('voucherInvalid');
                
                loading.style.display = show ? 'block' : 'none';
                valid.style.display = 'none';
                invalid.style.display = 'none';
            }

            function showVoucherSuccess(voucher) {
                const valid = document.getElementById('voucherValid');
                const invalid = document.getElementById('voucherInvalid');
                const info = document.getElementById('voucherInfo');
                const name = document.getElementById('voucherName');
                const description = document.getElementById('voucherDescription');
                const discount = document.getElementById('voucherDiscount');
                
                valid.style.display = 'block';
                invalid.style.display = 'none';
                info.style.display = 'block';
                
                name.textContent = voucher.kode_voucher;
                description.textContent = `Berlaku sampai ${new Date(voucher.masa_berlaku).toLocaleDateString('id-ID')}`;
                
                if (voucher.tipe === 'percentage') {
                    discount.textContent = `Diskon ${voucher.nilai}%`;
                } else {
                    discount.textContent = `Diskon Rp ${parseInt(voucher.nilai).toLocaleString('id-ID')}`;
                }
            }

            function showVoucherError(message) {
                const valid = document.getElementById('voucherValid');
                const invalid = document.getElementById('voucherInvalid');
                const info = document.getElementById('voucherInfo');
                
                valid.style.display = 'none';
                invalid.style.display = 'block';
                info.style.display = 'none';
                
                // Show error message
                alert(message);
            }

            function applyVoucherDiscount(voucher) {
                const basePrice = <?= $base_price ?>;
                let discountAmount = 0;
                
                if (voucher.tipe === 'percentage') {
                    discountAmount = (basePrice * voucher.nilai) / 100;
                } else {
                    discountAmount = parseInt(voucher.nilai);
                }
                
                const finalAmount = Math.max(0, basePrice - discountAmount);
                
                // Update UI
                document.getElementById('discountRow').style.display = 'flex';
                document.getElementById('discountAmount').textContent = discountAmount.toLocaleString('id-ID');
                document.getElementById('totalPrice').textContent = finalAmount.toLocaleString('id-ID');
                document.getElementById('finalTotal').textContent = finalAmount.toLocaleString('id-ID');
                
                // Update hidden input
                document.getElementById('finalAmount').value = finalAmount;
            }

            function removeVoucherDiscount() {
                const basePrice = <?= $base_price ?>;
                
                // Update UI
                document.getElementById('discountRow').style.display = 'none';
                document.getElementById('totalPrice').textContent = basePrice.toLocaleString('id-ID');
                document.getElementById('finalTotal').textContent = basePrice.toLocaleString('id-ID');
                
                // Update hidden input
                document.getElementById('finalAmount').value = basePrice;
                
                // Hide voucher info
                document.getElementById('voucherInfo').style.display = 'none';
                document.getElementById('voucherValid').style.display = 'none';
                document.getElementById('voucherInvalid').style.display = 'none';
            }

            function checkFormValid() {
                const isValid = selectedMethod && fileSelected && agreementCheck.checked;
                submitBtn.disabled = !isValid;
            }

            function showLoading() {
                document.getElementById('loadingOverlay').style.display = 'flex';
            }

            // Auto-hide flash messages
            setTimeout(function() {
                const alerts = document.querySelectorAll('.alert');
                alerts.forEach(alert => {
                    const bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                });
            }, 8000);
        });
    </script>
</body>
</html>