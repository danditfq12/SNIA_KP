<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Letter of Acceptance (LOA) - SNIA Presenter</title>
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

        .header-section {
            background: linear-gradient(135deg, var(--purple-color) 0%, #7c3aed 100%);
            color: white;
            padding: 30px;
            border-radius: 16px;
            margin-bottom: 30px;
        }

        .document-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            border: 1px solid rgba(255,255,255,0.2);
            margin-bottom: 24px;
            transition: all 0.3s ease;
            position: relative;
        }

        .document-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.12);
        }

        .document-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, var(--success-color) 0%, #059669 100%);
            border-radius: 16px 16px 0 0;
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

        .badge-available {
            background: #d1fae5;
            color: #059669;
        }

        .badge-pending {
            background: #fef3c7;
            color: #d97706;
        }

        .badge-not-eligible {
            background: #fee2e2;
            color: #dc2626;
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

        .info-card {
            background: #f0f9ff;
            border: 1px solid #bae6fd;
            border-radius: 12px;
            padding: 20px;
            margin: 20px 0;
            color: #1e40af;
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

        .tab-navigation {
            background: white;
            border-radius: 12px;
            padding: 8px;
            margin-bottom: 24px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.05);
        }

        .tab-navigation .nav-link {
            border: none;
            border-radius: 8px;
            padding: 12px 20px;
            color: #64748b;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .tab-navigation .nav-link.active {
            background: var(--primary-color);
            color: white;
        }

        .document-meta {
            display: flex;
            gap: 20px;
            margin: 16px 0;
            flex-wrap: wrap;
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
                        <small class="text-white-50">Document Center</small>
                    </div>
                    
                    <nav class="nav flex-column px-3">
                        <a class="nav-link" href="<?= site_url('presenter/dashboard') ?>">
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
                        <a class="nav-link active" href="<?= site_url('presenter/dokumen/loa') ?>">
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
                                <a href="<?= site_url('presenter/dokumen/loa') ?>">Dokumen</a>
                            </li>
                            <li class="breadcrumb-item active" aria-current="page">
                                Letter of Acceptance
                            </li>
                        </ol>
                    </nav>

                    <!-- Header -->
                    <div class="header-section">
                        <div class="row align-items-center">
                            <div class="col">
                                <h1 class="mb-2">
                                    <i class="fas fa-certificate me-3"></i>Letter of Acceptance (LOA)
                                </h1>
                                <p class="mb-0 opacity-75">
                                    Kelola dan download dokumen Letter of Acceptance untuk event yang Anda ikuti.
                                </p>
                            </div>
                            <div class="col-auto">
                                <div class="text-end">
                                    <span class="badge bg-success bg-opacity-25 text-success fs-6 px-3 py-2">
                                        <i class="fas fa-user-tie me-2"></i>Presenter
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tab Navigation -->
                    <div class="tab-navigation">
                        <ul class="nav nav-pills justify-content-center">
                            <li class="nav-item">
                                <a class="nav-link active" href="<?= site_url('presenter/dokumen/loa') ?>">
                                    <i class="fas fa-certificate me-2"></i>Letter of Acceptance
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?= site_url('presenter/dokumen/sertifikat') ?>">
                                    <i class="fas fa-award me-2"></i>Sertifikat
                                </a>
                            </li>
                        </ul>
                    </div>

                    <!-- Information Card -->
                    <div class="info-card">
                        <h6 class="mb-2">
                            <i class="fas fa-info-circle me-2"></i>Tentang Letter of Acceptance (LOA)
                        </h6>
                        <p class="mb-0">
                            LOA adalah dokumen resmi yang menyatakan bahwa abstrak Anda telah diterima dan 
                            Anda dikonfirmasi sebagai presenter dalam event. LOA tersedia setelah abstrak 
                            diterima dan pembayaran terverifikasi.
                        </p>
                    </div>

                    <!-- LOA Documents Section -->
                    <div class="row">
                        <div class="col-12">
                            <h5 class="mb-3">
                                <i class="fas fa-folder-open me-2 text-primary"></i>
                                Dokumen LOA Tersedia
                            </h5>

                            <?php if (!empty($loa_documents)): ?>
                                <?php foreach ($loa_documents as $loa): ?>
                                <div class="document-card">
                                    <span class="status-badge badge-available">
                                        <i class="fas fa-check-circle me-1"></i>Tersedia
                                    </span>

                                    <div class="mb-3" style="margin-right: 120px;">
                                        <h6 class="mb-2"><?= esc($loa['event_title'] ?? 'Event Title') ?></h6>
                                        <p class="text-muted mb-0">Letter of Acceptance untuk partisipasi sebagai presenter</p>
                                    </div>

                                    <div class="document-meta">
                                        <div class="meta-item">
                                            <i class="fas fa-calendar"></i>
                                            <span>Tanggal Event: <?= date('d F Y', strtotime($loa['event_date'] ?? date('Y-m-d'))) ?></span>
                                        </div>
                                        <div class="meta-item">
                                            <i class="fas fa-clock"></i>
                                            <span>Uploaded: <?= date('d M Y H:i', strtotime($loa['uploaded_at'])) ?></span>
                                        </div>
                                        <div class="meta-item">
                                            <i class="fas fa-file-pdf"></i>
                                            <span>Format: PDF</span>
                                        </div>
                                    </div>

                                    <div class="mt-3">
                                        <a href="<?= site_url('presenter/dokumen/loa/download/' . basename($loa['file_path'])) ?>" 
                                           class="action-button">
                                            <i class="fas fa-download me-2"></i>Download LOA
                                        </a>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-file-contract"></i>
                                    <h5>Belum Ada LOA Tersedia</h5>
                                    <p>Anda belum memiliki dokumen LOA. LOA akan tersedia setelah abstrak diterima dan pembayaran terverifikasi.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Eligible Events Section -->
                    <div class="row mt-4">
                        <div class="col-12">
                            <h5 class="mb-3">
                                <i class="fas fa-clipboard-list me-2 text-warning"></i>
                                Event Yang Memenuhi Syarat LOA
                            </h5>

                            <?php if (!empty($eligible_events)): ?>
                                <?php foreach ($eligible_events as $event): ?>
                                <div class="document-card">
                                    <?php if (empty($event['existing_loa_id'])): ?>
                                        <span class="status-badge badge-pending">
                                            <i class="fas fa-clock me-1"></i>Dapat Diminta
                                        </span>
                                    <?php else: ?>
                                        <span class="status-badge badge-available">
                                            <i class="fas fa-check-circle me-1"></i>LOA Tersedia
                                        </span>
                                    <?php endif; ?>

                                    <div class="mb-3" style="margin-right: 120px;">
                                        <h6 class="mb-2"><?= esc($event['title']) ?></h6>
                                        <p class="text-muted mb-1">
                                            <strong>Abstrak:</strong> <?= esc($event['abstract_title'] ?? 'N/A') ?>
                                        </p>
                                        <p class="text-muted mb-0">
                                            <strong>Status:</strong> 
                                            <span class="badge bg-success">Abstrak Diterima</span>
                                            <span class="badge bg-primary">Pembayaran Verified</span>
                                        </p>
                                    </div>

                                    <div class="document-meta">
                                        <div class="meta-item">
                                            <i class="fas fa-calendar"></i>
                                            <span><?= date('d F Y', strtotime($event['event_date'])) ?></span>
                                        </div>
                                        <div class="meta-item">
                                            <i class="fas fa-check-circle"></i>
                                            <span>Pembayaran Verified: <?= date('d M Y', strtotime($event['payment_verified_at'])) ?></span>
                                        </div>
                                    </div>

                                    <div class="mt-3">
                                        <?php if (empty($event['existing_loa_id'])): ?>
                                            <button type="button" class="action-button" onclick="requestLoa(<?= $event['id'] ?>)">
                                                <i class="fas fa-paper-plane me-2"></i>Minta LOA
                                            </button>
                                        <?php else: ?>
                                            <span class="text-success">
                                                <i class="fas fa-check-circle me-2"></i>
                                                LOA sudah tersedia di bagian atas
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="empty-state">
                                    <i class="fas fa-exclamation-circle"></i>
                                    <h5>Tidak Ada Event Yang Memenuhi Syarat</h5>
                                    <p>Saat ini tidak ada event yang memenuhi syarat untuk mendapatkan LOA. 
                                       Pastikan abstrak Anda sudah diterima dan pembayaran sudah terverifikasi.</p>
                                    <a href="<?= site_url('presenter/events') ?>" class="action-button">
                                        <i class="fas fa-calendar me-2"></i>Lihat Event
                                    </a>
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
            // Show flash messages
            showFlashMessages();
        });

        function requestLoa(eventId) {
            if (!confirm('Apakah Anda yakin ingin meminta LOA untuk event ini?')) {
                return;
            }

            // Show loading
            const btn = event.target;
            const originalText = btn.innerHTML;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Memproses...';
            btn.disabled = true;

            fetch('<?= site_url('presenter/dokumen/requestLoa') ?>', {
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
                    showToast('Berhasil!', data.message, 'success');
                    // Refresh page after delay
                    setTimeout(() => {
                        location.reload();
                    }, 2000);
                } else {
                    showToast('Error!', data.message, 'danger');
                    // Reset button
                    btn.innerHTML = originalText;
                    btn.disabled = false;
                }
            })
            .catch(error => {
                console.error('Request LOA error:', error);
                showToast('Error!', 'Terjadi kesalahan saat meminta LOA', 'danger');
                // Reset button
                btn.innerHTML = originalText;
                btn.disabled = false;
            });
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
    </script>
</body>
</html>