<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Daftar Event - <?= esc($event['title']) ?></title>
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

        .registration-container {
            max-width: 800px;
            margin: 0 auto;
        }

        .registration-header {
            background: linear-gradient(135deg, var(--purple-color) 0%, #7c3aed 100%);
            color: white;
            padding: 30px;
            border-radius: 16px;
            margin-bottom: 30px;
            text-align: center;
        }

        .event-summary {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            margin-bottom: 24px;
            border-left: 4px solid var(--primary-color);
        }

        .registration-form {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            margin-bottom: 24px;
        }

        .form-section {
            margin-bottom: 32px;
            padding-bottom: 24px;
            border-bottom: 1px solid #e2e8f0;
        }

        .form-section:last-child {
            border-bottom: none;
            margin-bottom: 0;
        }

        .section-title {
            color: #1e293b;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .section-title i {
            color: var(--primary-color);
            width: 24px;
        }

        .pricing-display {
            background: linear-gradient(135deg, var(--success-color) 0%, #059669 100%);
            color: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            margin: 20px 0;
        }

        .pricing-display.free {
            background: linear-gradient(135deg, var(--info-color) 0%, #0891b2 100%);
        }

        .price-amount {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .terms-section {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
        }

        .btn-register {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--info-color) 100%);
            border: none;
            border-radius: 12px;
            padding: 16px 40px;
            color: white;
            font-weight: 600;
            font-size: 16px;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(37, 99, 235, 0.3);
            color: white;
        }

        .btn-register:disabled {
            background: #9ca3af;
            transform: none;
            box-shadow: none;
        }

        .alert-info-custom {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            border: 1px solid #93c5fd;
            color: #1e40af;
            border-radius: 12px;
            padding: 16px;
            margin: 16px 0;
        }

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

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.25);
        }

        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .participation-mode {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 16px;
            text-align: center;
            transition: all 0.3s ease;
        }

        .participation-mode.selected {
            border-color: var(--primary-color);
            background: #dbeafe;
        }

        .step-indicator {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-bottom: 30px;
        }

        .step {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: #e2e8f0;
            color: #64748b;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            position: relative;
        }

        .step.active {
            background: var(--primary-color);
            color: white;
        }

        .step.completed {
            background: var(--success-color);
            color: white;
        }

        .step-line {
            width: 60px;
            height: 2px;
            background: #e2e8f0;
            margin: 0 10px;
        }

        .step-line.completed {
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
                        <small class="text-white-50">Event Registration</small>
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
                    <div class="registration-container">
                        <!-- Breadcrumb -->
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb breadcrumb-custom">
                                <li class="breadcrumb-item">
                                    <a href="<?= site_url('presenter/dashboard') ?>">Dashboard</a>
                                </li>
                                <li class="breadcrumb-item">
                                    <a href="<?= site_url('presenter/events') ?>">Event</a>
                                </li>
                                <li class="breadcrumb-item">
                                    <a href="<?= site_url('presenter/events/detail/' . $event['id']) ?>">
                                        <?= esc($event['title']) ?>
                                    </a>
                                </li>
                                <li class="breadcrumb-item active" aria-current="page">
                                    Pendaftaran
                                </li>
                            </ol>
                        </nav>

                        <!-- Registration Header -->
                        <div class="registration-header">
                            <h1 class="mb-3">
                                <i class="fas fa-user-plus me-3"></i>
                                Pendaftaran Event
                            </h1>
                            <p class="mb-0 opacity-90">
                                Lengkapi form pendaftaran untuk berpartisipasi sebagai presenter
                            </p>
                        </div>

                        <!-- Step Indicator -->
                        <div class="step-indicator">
                            <div class="step active">1</div>
                            <div class="step-line"></div>
                            <div class="step">2</div>
                            <div class="step-line"></div>
                            <div class="step">3</div>
                            <div class="step-line"></div>
                            <div class="step">4</div>
                            <div class="step-line"></div>
                            <div class="step">5</div>
                        </div>

                        <!-- Event Summary -->
                        <div class="event-summary">
                            <h5 class="mb-3">
                                <i class="fas fa-info-circle text-primary me-2"></i>
                                Ringkasan Event
                            </h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <h6><?= esc($event['title']) ?></h6>
                                    <p class="text-muted mb-2"><?= esc($event['description']) ?></p>
                                </div>
                                <div class="col-md-6">
                                    <div class="row g-2">
                                        <div class="col-12">
                                            <small class="text-muted d-block">Tanggal & Waktu</small>
                                            <strong>
                                                <i class="fas fa-calendar me-1 text-primary"></i>
                                                <?= date('d F Y', strtotime($event['event_date'])) ?> 
                                                <?= date('H:i', strtotime($event['event_time'])) ?> WIB
                                            </strong>
                                        </div>
                                        <div class="col-12">
                                            <small class="text-muted d-block">Format & Lokasi</small>
                                            <strong>
                                                <i class="fas fa-map-marker-alt me-1 text-danger"></i>
                                                Offline - <?= $event['location'] ?: 'TBA' ?>
                                            </strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Registration Form -->
                        <form id="registrationForm" action="<?= site_url('presenter/events/register/' . $event['id']) ?>" method="POST">
                            <div class="registration-form">
                                <!-- Personal Information Section -->
                                <div class="form-section">
                                    <h5 class="section-title">
                                        <i class="fas fa-user"></i>
                                        Informasi Peserta
                                    </h5>
                                    
                                    <div class="alert-info-custom">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Sebagai Presenter:</strong> Anda akan berpartisipasi secara offline dan diharapkan mempresentasikan materi/penelitian Anda.
                                    </div>

                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Nama Lengkap</label>
                                            <input type="text" class="form-control" value="<?= esc(session('nama_lengkap')) ?>" readonly>
                                            <small class="text-muted">Tidak dapat diubah. Hubungi admin jika ada kesalahan.</small>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Email</label>
                                            <input type="email" class="form-control" value="<?= esc(session('email')) ?>" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Role</label>
                                            <input type="text" class="form-control" value="Presenter" readonly>
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Mode Partisipasi</label>
                                            <input type="text" class="form-control" value="Offline" readonly>
                                            <input type="hidden" name="participation_type" value="<?= $participation_type ?>">
                                        </div>
                                    </div>
                                </div>

                                <!-- Participation Mode Section -->
                                <div class="form-section">
                                    <h5 class="section-title">
                                        <i class="fas fa-laptop"></i>
                                        Mode Partisipasi
                                    </h5>

                                    <div class="participation-mode selected">
                                        <div class="d-flex align-items-center justify-content-center">
                                            <div class="me-3">
                                                <i class="fas fa-chalkboard-teacher fa-2x text-primary"></i>
                                            </div>
                                            <div>
                                                <h6 class="mb-1">Partisipasi Offline</h6>
                                                <p class="mb-0 text-muted small">
                                                    Hadir secara fisik di lokasi event dan mempresentasikan materi
                                                </p>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="alert alert-info mt-3">
                                        <i class="fas fa-info-circle me-2"></i>
                                        <strong>Catatan:</strong> Sebagai presenter, Anda wajib hadir secara offline untuk mempresentasikan materi Anda.
                                    </div>
                                </div>

                                <!-- Pricing Section -->
                                <div class="form-section">
                                    <h5 class="section-title">
                                        <i class="fas fa-receipt"></i>
                                        Biaya Partisipasi
                                    </h5>

                                    <div class="pricing-display <?= $price == 0 ? 'free' : '' ?>">
                                        <div class="price-amount">
                                            <?php if ($price == 0): ?>
                                                <i class="fas fa-gift me-2"></i>GRATIS
                                            <?php else: ?>
                                                Rp <?= number_format($price, 0, ',', '.') ?>
                                            <?php endif; ?>
                                        </div>
                                        <div>Biaya Presenter (Offline)</div>
                                    </div>

                                    <?php if ($price > 0): ?>
                                    <div class="alert alert-warning">
                                        <i class="fas fa-exclamation-triangle me-2"></i>
                                        <strong>Pembayaran:</strong> Setelah mendaftar, Anda perlu melakukan pembayaran untuk mengkonfirmasi partisipasi.
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <!-- Terms and Conditions -->
                                <div class="form-section">
                                    <h5 class="section-title">
                                        <i class="fas fa-clipboard-check"></i>
                                        Syarat dan Ketentuan
                                    </h5>

                                    <div class="terms-section">
                                        <h6 class="mb-3">Ketentuan Presenter:</h6>
                                        <ul class="mb-3">
                                            <li>Menyiapkan dan mempresentasikan materi/penelitian sesuai tema event</li>
                                            <li>Hadir tepat waktu pada hari pelaksanaan event</li>
                                            <li>Upload abstrak dalam batas waktu yang ditentukan</li>
                                            <li>Menyelesaikan pembayaran jika ada biaya partisipasi</li>
                                            <li>Mengikuti protokol kesehatan yang berlaku</li>
                                            <li>Bersedia untuk sesi tanya jawab setelah presentasi</li>
                                        </ul>

                                        <h6 class="mb-3">Proses Setelah Pendaftaran:</h6>
                                        <ol class="mb-3">
                                            <li><strong>Upload Abstrak:</strong> Submit abstrak untuk review</li>
                                            <li><strong>Review Abstrak:</strong> Menunggu persetujuan dari reviewer</li>
                                            <li><strong>Pembayaran:</strong> Lakukan pembayaran jika abstrak diterima</li>
                                            <li><strong>Konfirmasi:</strong> Dapatkan akses penuh ke fitur event</li>
                                            <li><strong>Partisipasi:</strong> Hadiri event dan presentasikan materi</li>
                                        </ol>

                                        <div class="form-check mt-4">
                                            <input class="form-check-input" type="checkbox" id="agreeTerms" required>
                                            <label class="form-check-label" for="agreeTerms">
                                                <strong>Saya setuju dengan semua syarat dan ketentuan yang berlaku</strong>
                                            </label>
                                        </div>

                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" id="confirmPresenter" required>
                                            <label class="form-check-label" for="confirmPresenter">
                                                Saya berkomitmen untuk hadir dan mempresentasikan materi secara offline
                                            </label>
                                        </div>

                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" id="confirmAbstract" required>
                                            <label class="form-check-label" for="confirmAbstract">
                                                Saya siap untuk mengupload abstrak setelah mendaftar
                                            </label>
                                        </div>
                                    </div>
                                </div>

                                <!-- Submit Section -->
                                <div class="text-center">
                                    <button type="submit" class="btn-register" id="submitBtn" disabled>
                                        <i class="fas fa-user-plus me-2"></i>
                                        Daftar Sebagai Presenter
                                    </button>
                                    
                                    <div class="mt-3">
                                        <a href="<?= site_url('presenter/events/detail/' . $event['id']) ?>" 
                                           class="btn btn-outline-secondary">
                                            <i class="fas fa-arrow-left me-2"></i>
                                            Kembali ke Detail Event
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Confirmation Modal -->
    <div class="modal fade" id="confirmationModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-question-circle text-warning me-2"></i>
                        Konfirmasi Pendaftaran
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p><strong>Apakah Anda yakin ingin mendaftar sebagai presenter untuk event ini?</strong></p>
                    <div class="bg-light p-3 rounded mt-3">
                        <h6 class="mb-2">Event: <?= esc($event['title']) ?></h6>
                        <p class="mb-1"><i class="fas fa-user me-2"></i>Role: Presenter</p>
                        <p class="mb-1"><i class="fas fa-map-marker-alt me-2"></i>Mode: Offline</p>
                        <p class="mb-0"><i class="fas fa-tag me-2"></i>Biaya: 
                            <?php if ($price == 0): ?>
                                Gratis
                            <?php else: ?>
                                Rp <?= number_format($price, 0, ',', '.') ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div class="alert alert-info mt-3">
                        <i class="fas fa-info-circle me-2"></i>
                        Setelah mendaftar, Anda perlu mengupload abstrak untuk melanjutkan proses.
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Batal
                    </button>
                    <button type="button" class="btn btn-primary" id="confirmRegister">
                        <i class="fas fa-check me-2"></i>Ya, Daftar Sekarang
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Toast Container -->
    <div class="toast-container position-fixed top-0 end-0 p-3" id="toastContainer"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('registrationForm');
            const submitBtn = document.getElementById('submitBtn');
            const checkboxes = document.querySelectorAll('input[type="checkbox"]');
            const confirmationModal = new bootstrap.Modal(document.getElementById('confirmationModal'));
            const confirmBtn = document.getElementById('confirmRegister');

            // Enable/disable submit button based on checkboxes
            function updateSubmitButton() {
                const allChecked = Array.from(checkboxes).every(cb => cb.checked);
                submitBtn.disabled = !allChecked;
                
                if (allChecked) {
                    submitBtn.classList.remove('btn-secondary');
                    submitBtn.classList.add('btn-primary');
                } else {
                    submitBtn.classList.remove('btn-primary');
                    submitBtn.classList.add('btn-secondary');
                }
            }

            // Add event listeners to checkboxes
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', updateSubmitButton);
            });

            // Handle form submission
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                
                if (!submitBtn.disabled) {
                    confirmationModal.show();
                }
            });

            // Handle confirmation
            confirmBtn.addEventListener('click', function() {
                confirmationModal.hide();
                submitFormWithLoading();
            });

            function submitFormWithLoading() {
                // Show loading state
                const originalText = submitBtn.innerHTML;
                submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memproses Pendaftaran...';
                submitBtn.disabled = true;

                // Submit form via AJAX
                const formData = new FormData(form);
                
                fetch(form.action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast('Berhasil!', data.message, 'success');
                        
                        // Update step indicator
                        updateStepIndicator(2);
                        
                        // Redirect after delay
                        setTimeout(() => {
                            window.location.href = data.redirect_url;
                        }, 2000);
                    } else {
                        showToast('Error!', data.message, 'danger');
                        
                        // Reset button
                        submitBtn.innerHTML = originalText;
                        submitBtn.disabled = false;
                    }
                })
                .catch(error => {
                    console.error('Registration error:', error);
                    showToast('Error!', 'Terjadi kesalahan saat memproses pendaftaran', 'danger');
                    
                    // Reset button
                    submitBtn.innerHTML = originalText;
                    submitBtn.disabled = false;
                });
            }

            function updateStepIndicator(currentStep) {
                const steps = document.querySelectorAll('.step');
                const lines = document.querySelectorAll('.step-line');

                steps.forEach((step, index) => {
                    const stepNum = index + 1;
                    if (stepNum < currentStep) {
                        step.classList.add('completed');
                        step.classList.remove('active');
                    } else if (stepNum === currentStep) {
                        step.classList.add('active');
                        step.classList.remove('completed');
                    } else {
                        step.classList.remove('active', 'completed');
                    }
                });

                lines.forEach((line, index) => {
                    if (index + 1 < currentStep) {
                        line.classList.add('completed');
                    } else {
                        line.classList.remove('completed');
                    }
                });
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

            // Show flash messages
            <?php if (session()->getFlashdata('success')): ?>
                showToast('Berhasil!', '<?= esc(session()->getFlashdata('success')) ?>', 'success');
            <?php endif; ?>
            
            <?php if (session()->getFlashdata('error')): ?>
                showToast('Error!', '<?= esc(session()->getFlashdata('error')) ?>', 'danger');
            <?php endif; ?>

            // Initialize button state
            updateSubmitButton();
        });
    </script>
</body>
</html>