<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Status Abstrak - Presenter Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .review-card {
            border-left: 4px solid;
        }
        .review-accepted {
            border-left-color: #28a745;
        }
        .review-rejected {
            border-left-color: #dc3545;
        }
        .review-pending {
            border-left-color: #ffc107;
        }
        .timeline-item {
            border-left: 2px solid #dee2e6;
            padding-left: 20px;
            margin-bottom: 20px;
            position: relative;
        }
        .timeline-item::before {
            content: '';
            position: absolute;
            left: -8px;
            top: 0;
            width: 14px;
            height: 14px;
            border-radius: 50%;
            background-color: #dee2e6;
        }
        .timeline-item.accepted::before {
            background-color: #28a745;
        }
        .timeline-item.rejected::before {
            background-color: #dc3545;
        }
        .timeline-item.pending::before {
            background-color: #ffc107;
        }
    </style>
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="<?= site_url('presenter/dashboard') ?>">
                <i class="fas fa-microphone me-2"></i>SNIA Presenter
            </a>
            <div class="navbar-nav ms-auto">
                <a href="<?= site_url('presenter/dashboard') ?>" class="nav-link">
                    <i class="fas fa-home me-1"></i>Dashboard
                </a>
                <a href="<?= site_url('presenter/abstrak') ?>" class="nav-link">
                    <i class="fas fa-list me-1"></i>Abstrak
                </a>
                <a href="<?= site_url('auth/logout') ?>" class="btn btn-outline-light btn-sm ms-2">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-chart-line me-2"></i>Status & Review Abstrak</h2>
            <a href="<?= site_url('presenter/abstrak') ?>" class="btn btn-primary">
                <i class="fas fa-plus me-1"></i>Submit Abstrak Baru
            </a>
        </div>

        <?php if (!empty($abstraks)): ?>
            <?php foreach ($abstraks as $abstrak): ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-<?= $abstrak['status'] == 'diterima' ? 'success' : ($abstrak['status'] == 'ditolak' ? 'danger' : 'warning') ?> text-white">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <h6 class="mb-0"><?= $abstrak['judul'] ?></h6>
                                <small>
                                    <i class="fas fa-tag me-1"></i><?= $abstrak['nama_kategori'] ?? 'N/A' ?> |
                                    <i class="fas fa-calendar me-1"></i><?= $abstrak['event_title'] ?? 'No Event' ?>
                                </small>
                            </div>
                            <div class="col-md-4 text-end">
                                <span class="badge bg-light text-dark">
                                    Revisi ke-<?= $abstrak['revisi_ke'] ?>
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Abstract Info -->
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <small class="text-muted">
                                    <i class="fas fa-upload me-1"></i>
                                    Diupload: <?= date('d/m/Y H:i', strtotime($abstrak['tanggal_upload'])) ?>
                                </small>
                            </div>
                            <div class="col-md-6 text-end">
                                <a href="<?= site_url('presenter/abstrak/download/' . $abstrak['file_abstrak']) ?>" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-download me-1"></i>Download File
                                </a>
                            </div>
                        </div>

                        <!-- Review Timeline -->
                        <?php if (!empty($abstrak['reviews'])): ?>
                            <h6><i class="fas fa-comments me-2"></i>Timeline Review:</h6>
                            <div class="review-timeline">
                                <?php foreach ($abstrak['reviews'] as $review): ?>
                                    <div class="timeline-item <?= $review['keputusan'] ?>">
                                        <div class="review-card p-3 bg-light rounded">
                                            <div class="d-flex justify-content-between align-items-start">
                                                <div>
                                                    <h6 class="mb-1">
                                                        <span class="badge bg-<?= $review['keputusan'] == 'diterima' ? 'success' : ($review['keputusan'] == 'ditolak' ? 'danger' : 'info') ?>">
                                                            <?= ucfirst($review['keputusan']) ?>
                                                        </span>
                                                    </h6>
                                                    <small class="text-muted">
                                                        <i class="fas fa-user me-1"></i>Reviewer: <?= $review['reviewer_name'] ?? 'Anonymous' ?><br>
                                                        <i class="fas fa-clock me-1"></i><?= date('d/m/Y H:i', strtotime($review['tanggal_review'])) ?>
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="mt-2">
                                                <strong>Komentar:</strong>
                                                <p class="mb-0 mt-1"><?= nl2br(htmlspecialchars($review['komentar'])) ?></p>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="fas fa-hourglass-half me-2"></i>
                                Abstrak sedang menunggu review dari reviewer
                            </div>
                        <?php endif; ?>

                        <!-- Action Buttons -->
                        <div class="mt-3">
                            <?php if ($abstrak['status'] == 'ditolak'): ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-exclamation-triangle me-2"></i>
                                    <strong>Abstrak Ditolak</strong> - Silakan perbaiki sesuai komentar reviewer dan submit ulang
                                </div>
                                <a href="<?= site_url('presenter/abstrak') ?>" class="btn btn-warning">
                                    <i class="fas fa-redo me-1"></i>Submit Ulang Abstrak
                                </a>
                            <?php elseif ($abstrak['status'] == 'diterima'): ?>
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <strong>Abstrak Diterima</strong> - Silakan lanjutkan ke pembayaran registrasi
                                </div>
                                <a href="<?= site_url('presenter/pembayaran') ?>" class="btn btn-success">
                                    <i class="fas fa-credit-card me-1"></i>Lakukan Pembayaran
                                </a>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="fas fa-clock me-2"></i>
                                    <strong>Menunggu Review</strong> - Abstrak sedang dalam proses review
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="card shadow-sm">
                <div class="card-body text-center py-5">
                    <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                    <h5 class="text-muted">Belum Ada Abstrak</h5>
                    <p class="text-muted">Anda belum mengirim abstrak apapun</p>
                    <a href="<?= site_url('presenter/abstrak') ?>" class="btn btn-primary">
                        <i class="fas fa-plus me-1"></i>Submit Abstrak Pertama
                    </a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-refresh every minute for pending reviews
        setInterval(function() {
            const pendingBadges = document.querySelectorAll('.badge');
            let hasPending = false;
            pendingBadges.forEach(function(badge) {
                if (badge.textContent.includes('Menunggu')) {
                    hasPending = true;
                }
            });
            
            if (hasPending) {
                console.log('Auto-refreshing for review updates...');
                location.reload();
            }
        }, 60000); // Refresh every 1 minute
    </script>
</body>
</html>