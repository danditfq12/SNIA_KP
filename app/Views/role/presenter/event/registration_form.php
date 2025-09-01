<?php
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Event - <?= esc($event['title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
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
            font-family: 'Inter', 'Segoe UI', sans-serif;
            min-height: 100vh;
        }

        .registration-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 0 20px;
        }

        .event-header {
            background: linear-gradient(135deg, var(--primary-color), var(--info-color));
            color: white;
            border-radius: 16px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(37, 99, 235, 0.3);
        }

        .registration-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            overflow: hidden;
            margin-bottom: 20px;
        }

        .card-header {
            background: linear-gradient(135deg, var(--primary-color), var(--info-color));
            color: white;
            padding: 20px;
            font-weight: 600;
        }

        .price-display {
            font-size: 2rem;
            font-weight: 700;
            color: var(--success-color);
            text-align: center;
            padding: 20px;
            background: rgba(16, 185, 129, 0.1);
            border-radius: 12px;
            margin: 20px 0;
        }

        .offline-badge {
            background: rgba(245, 158, 11, 0.1);
            color: #d97706;
            padding: 8px 16px;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
            display: inline-block;
            margin-top: 10px;
        }

        .form-section {
            padding: 30px;
        }

        .voucher-section {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            border: 2px dashed #e2e8f0;
        }

        .voucher-applied {
            background: rgba(16, 185, 129, 0.1);
            border-color: var(--success-color);
        }

        .btn-custom {
            border-radius: 8px;
            padding: 12px 24px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-primary-custom {
            background: linear-gradient(135deg, var(--primary-color), var(--info-color));
            border: none;
            color: white;
        }

        .btn-primary-custom:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.3);
        }

        .alert-custom {
            border-radius: 12px;
            border: none;
            padding: 16px 20px;
        }

        .file-upload-area {
            border: 2px dashed #e2e8f0;
            border-radius: 12px;
            padding: 30px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .file-upload-area:hover {
            border-color: var(--primary-color);
            background: rgba(37, 99, 235, 0.05);
        }

        .file-upload-area.dragover {
            border-color: var(--success-color);
            background: rgba(16, 185, 129, 0.05);
        }
    </style>
</head>
<body>
    <div class="registration-container">
        <!-- Event Header -->
        <div class="event-header">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h2 class="mb-2"><?= esc($event['title']) ?></h2>
                    <div class="mb-2">
                        <i class="fas fa-calendar me-2"></i>
                        <?= date('d F Y', strtotime($event['event_date'])) ?> 
                        <i class="fas fa-clock ms-3 me-2"></i>
                        <?= date('H:i', strtotime($event['event_time'])) ?>
                    </div>
                    <?php if ($event['location']): ?>
                        <div>
                            <i class="fas fa-map-marker-alt me-2"></i>
                            <?= esc($event['location']) ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="col-md-4 text-end">
                    <a href="<?= site_url('presenter/events') ?>" class="btn btn-outline-light">
                        <i class="fas fa-arrow-left me-2"></i>Back to Events
                    </a>
                </div>
            </div>
        </div>

        <?php if (session('errors')): ?>
            <div class="alert alert-danger alert-custom">
                <h6><i class="fas fa-exclamation-circle me-2"></i>Please fix the following errors:</h6>
                <ul class="mb-0">
                    <?php foreach (session('errors') as $error): ?>
                        <li><?= esc($error) ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <!-- Registration Form -->
        <form action="<?= site_url('presenter/events/register/' . $event['id']) ?>" method="POST" enctype="multipart/form-data" id="registrationForm">
            <?= csrf_field() ?>
            
            <!-- Event Details & Pricing -->
            <div class="registration-card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Registration Details
                    </h5>
                </div>
                <div class="form-section">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary mb-3">Event Information</h6>
                            <?php if ($event['description']): ?>
                                <p class="text-muted"><?= esc($event['description']) ?></p>
                            <?php endif; ?>
                            
                            <div class="alert alert-info alert-custom">
                                <h6 class="alert-heading">
                                    <i class="fas fa-user-tie me-2"></i>Presenter Information
                                </h6>
                                <p class="mb-0">
                                    As a presenter, you can <strong>only participate offline</strong>. 
                                    All presentations must be delivered in-person at the venue.
                                </p>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <h6 class="text-success mb-3">Registration Fee</h6>
                            <div class="price-display">
                                Rp <?= number_format($event['presenter_fee_offline'], 0, ',', '.') ?>
                            </div>
                            <div class="text-center">
                                <span class="offline-badge">
                                    <i class="fas fa-map-marker-alt me-1"></i>
                                    Offline Participation Only
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Voucher Section -->
            <div class="registration-card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-ticket-alt me-2"></i>
                        Voucher Code (Optional)
                    </h5>
                </div>
                <div class="form-section">
                    <div class="voucher-section" id="voucherSection">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <input type="text" 
                                       class="form-control" 
                                       name="voucher_code" 
                                       id="voucherCode"
                                       placeholder="Enter voucher code"
                                       style="text-transform: uppercase;">
                            </div>
                            <div class="col-md-6">
                                <button type="button" 
                                        class="btn btn-outline-primary btn-custom" 
                                        id="applyVoucher">
                                    <i class="fas fa-check me-2"></i>Apply Voucher
                                </button>
                            </div>
                        </div>
                        
                        <div id="voucherResult" class="mt-3" style="display: none;">
                            <!-- Voucher result will be displayed here -->
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Method -->
            <div class="registration-card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-credit-card me-2"></i>
                        Payment Method
                    </h5>
                </div>
                <div class="form-section">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="bankTransfer" value="bank_transfer" checked>
                                <label class="form-check-label" for="bankTransfer">
                                    <i class="fas fa-university me-2 text-primary"></i>
                                    Bank Transfer
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="eWallet" value="e_wallet">
                                <label class="form-check-label" for="eWallet">
                                    <i class="fas fa-mobile-alt me-2 text-success"></i>
                                    E-Wallet
                                </label>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="payment_method" id="creditCard" value="credit_card">
                                <label class="form-check-label" for="creditCard">
                                    <i class="fas fa-credit-card me-2 text-warning"></i>
                                    Credit Card
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Proof Upload -->
            <div class="registration-card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-file-upload me-2"></i>
                        Payment Proof
                    </h5>
                </div>
                <div class="form-section">
                    <div class="file-upload-area" id="fileUploadArea">
                        <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                        <h6 class="text-muted">Upload Payment Proof</h6>
                        <p class="text-muted small">
                            Drag and drop your file here or click to browse<br>
                            Supported formats: JPG, JPEG, PNG, PDF (Max: 5MB)
                        </p>
                        <input type="file" 
                               class="form-control" 
                               name="payment_proof" 
                               id="paymentProof"
                               accept=".jpg,.jpeg,.png,.pdf"
                               required
                               style="display: none;">
                        <button type="button" class="btn btn-outline-primary btn-custom" onclick="document.getElementById('paymentProof').click()">
                            <i class="fas fa-folder-open me-2"></i>Choose File
                        </button>
                    </div>
                    
                    <div id="filePreview" class="mt-3" style="display: none;">
                        <!-- File preview will be shown here -->
                    </div>
                </div>
            </div>

            <!-- Final Price Summary -->
            <div class="registration-card">
                <div class="card-header">
                    <h5 class="mb-0">
                        <i class="fas fa-calculator me-2"></i>
                        Price Summary
                    </h5>
                </div>
                <div class="form-section">
                    <div class="row">
                        <div class="col-md-8">
                            <div class="table-responsive">
                                <table class="table">
                                    <tr>
                                        <td>Base Price (Presenter Fee - Offline)</td>
                                        <td class="text-end">Rp <?= number_format($event['presenter_fee_offline'], 0, ',', '.') ?></td>
                                    </tr>
                                    <tr id="discountRow" style="display: none;">
                                        <td class="text-success">Discount Applied</td>
                                        <td class="text-end text-success" id="discountAmount">- Rp 0</td>
                                    </tr>
                                    <tr class="table-primary">
                                        <td><strong>Total Amount</strong></td>
                                        <td class="text-end"><strong id="totalAmount">Rp <?= number_format($event['presenter_fee_offline'], 0, ',', '.') ?></strong></td>
                                    </tr>
                                </table>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="alert alert-warning alert-custom">
                                <h6 class="alert-heading">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    Important Notes
                                </h6>
                                <ul class="small mb-0">
                                    <li>Payment must be completed before the deadline</li>
                                    <li>Upload clear payment proof</li>
                                    <li>Admin verification may take 1-2 business days</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="text-center mb-4">
                <button type="submit" class="btn btn-primary-custom btn-custom btn-lg">
                    <i class="fas fa-paper-plane me-2"></i>
                    Submit Registration
                </button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        let currentDiscount = 0;
        let basePrice = <?= $event['presenter_fee_offline'] ?>;

        // Voucher functionality
        document.getElementById('applyVoucher').addEventListener('click', function() {
            const voucherCode = document.getElementById('voucherCode').value.trim();
            
            if (!voucherCode) {
                Swal.fire('Error', 'Please enter a voucher code', 'error');
                return;
            }

            // Show loading
            this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Checking...';
            this.disabled = true;

            // AJAX call to validate voucher
            fetch('<?= site_url('presenter/pembayaran/validate-voucher') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    kode_voucher: voucherCode,
                    amount: basePrice
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Apply voucher
                    currentDiscount = data.discount;
                    updatePriceDisplay();
                    showVoucherResult(true, data);
                } else {
                    showVoucherResult(false, data);
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire('Error', 'Failed to validate voucher', 'error');
            })
            .finally(() => {
                // Reset button
                this.innerHTML = '<i class="fas fa-check me-2"></i>Apply Voucher';
                this.disabled = false;
            });
        });

        function showVoucherResult(success, data) {
            const resultDiv = document.getElementById('voucherResult');
            const voucherSection = document.getElementById('voucherSection');
            
            if (success) {
                voucherSection.classList.add('voucher-applied');
                resultDiv.innerHTML = `
                    <div class="alert alert-success">
                        <h6><i class="fas fa-check-circle me-2"></i>Voucher Applied Successfully!</h6>
                        <p class="mb-0">${data.message}</p>
                        <small class="text-muted">Discount: ${data.formatted_discount}</small>
                    </div>
                `;
            } else {
                voucherSection.classList.remove('voucher-applied');
                resultDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <h6><i class="fas fa-times-circle me-2"></i>Voucher Invalid</h6>
                        <p class="mb-0">${data.message}</p>
                    </div>
                `;
            }
            
            resultDiv.style.display = 'block';
        }

        function updatePriceDisplay() {
            const finalAmount = Math.max(0, basePrice - currentDiscount);
            
            if (currentDiscount > 0) {
                document.getElementById('discountRow').style.display = 'table-row';
                document.getElementById('discountAmount').textContent = '- Rp ' + currentDiscount.toLocaleString('id-ID');
            } else {
                document.getElementById('discountRow').style.display = 'none';
            }
            
            document.getElementById('totalAmount').textContent = 'Rp ' + finalAmount.toLocaleString('id-ID');
        }

        // File upload functionality
        const fileUploadArea = document.getElementById('fileUploadArea');
        const paymentProof = document.getElementById('paymentProof');
        const filePreview = document.getElementById('filePreview');

        // Drag and drop functionality
        ['dragenter', 'dragover', 'dragleave', 'drop'].forEach(eventName => {
            fileUploadArea.addEventListener(eventName, preventDefaults, false);
        });

        function preventDefaults(e) {
            e.preventDefault();
            e.stopPropagation();
        }

        ['dragenter', 'dragover'].forEach(eventName => {
            fileUploadArea.addEventListener(eventName, highlight, false);
        });

        ['dragleave', 'drop'].forEach(eventName => {
            fileUploadArea.addEventListener(eventName, unhighlight, false);
        });

        function highlight(e) {
            fileUploadArea.classList.add('dragover');
        }

        function unhighlight(e) {
            fileUploadArea.classList.remove('dragover');
        }

        fileUploadArea.addEventListener('drop', handleDrop, false);

        function handleDrop(e) {
            const dt = e.dataTransfer;
            const files = dt.files;
            
            if (files.length > 0) {
                paymentProof.files = files;
                handleFileSelect(files[0]);
            }
        }

        paymentProof.addEventListener('change', function(e) {
            if (e.target.files.length > 0) {
                handleFileSelect(e.target.files[0]);
            }
        });

        function handleFileSelect(file) {
            // Validate file
            const allowedTypes = ['image/jpeg', 'image/jpg', 'image/png', 'application/pdf'];
            const maxSize = 5 * 1024 * 1024; // 5MB

            if (!allowedTypes.includes(file.type)) {
                Swal.fire('Error', 'Please upload a valid file format (JPG, JPEG, PNG, PDF)', 'error');
                return;
            }

            if (file.size > maxSize) {
                Swal.fire('Error', 'File size must be less than 5MB', 'error');
                return;
            }

            // Show file preview
            showFilePreview(file);
        }

        function showFilePreview(file) {
            const reader = new FileReader();
            
            reader.onload = function(e) {
                let previewContent = '';
                
                if (file.type.startsWith('image/')) {
                    previewContent = `
                        <div class="text-center">
                            <img src="${e.target.result}" class="img-thumbnail" style="max-height: 200px;">
                            <div class="mt-2">
                                <strong>${file.name}</strong>
                                <br><small class="text-muted">${(file.size / 1024).toFixed(1)} KB</small>
                            </div>
                        </div>
                    `;
                } else {
                    previewContent = `
                        <div class="text-center">
                            <i class="fas fa-file-pdf fa-4x text-danger mb-2"></i>
                            <div>
                                <strong>${file.name}</strong>
                                <br><small class="text-muted">${(file.size / 1024).toFixed(1)} KB</small>
                            </div>
                        </div>
                    `;
                }
                
                filePreview.innerHTML = previewContent;
                filePreview.style.display = 'block';
            };
            
            if (file.type.startsWith('image/')) {
                reader.readAsDataURL(file);
            } else {
                reader.readAsText(file);
            }
        }

        // Form submission
        document.getElementById('registrationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            Swal.fire({
                title: 'Confirm Registration',
                text: 'Are you sure you want to register for this event?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#2563eb',
                cancelButtonColor: '#6b7280',
                confirmButtonText: 'Yes, Register!'
            }).then((result) => {
                if (result.isConfirmed) {
                    // Show loading
                    Swal.fire({
                        title: 'Processing...',
                        text: 'Submitting your registration',
                        allowOutsideClick: false,
                        didOpen: () => {
                            Swal.showLoading();
                        }
                    });
                    
                    // Submit form
                    this.submit();
                }
            });
        });
    </script>
</body>
</html>