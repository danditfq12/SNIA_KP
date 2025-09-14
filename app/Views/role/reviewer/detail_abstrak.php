<?php
$title = 'Detail Abstrak - Review';
$breadcrumb = 'Detail Abstrak';

// Default values untuk safety
$abstrak = $abstrak ?? [];
$review = $review ?? null;
$fmtDate = fn($d) => $d ? date('d M Y H:i', strtotime($d)) : '-';

// Check review status
$isAlreadyReviewed = false;
$currentReviewStatus = 'pending';
$reviewData = null;

// Cek apakah ada data review untuk reviewer ini
if ($review) {
    $reviewData = $review;
    if (isset($review['keputusan']) && $review['keputusan'] !== 'pending' && $review['keputusan'] !== null) {
        $isAlreadyReviewed = true;
        $currentReviewStatus = strtolower($review['keputusan']);
    }
}

// Fallback cek dari status abstrak
if (!$isAlreadyReviewed && isset($abstrak['status']) && in_array(strtolower($abstrak['status']), ['diterima', 'ditolak', 'revisi'])) {
    $isAlreadyReviewed = true;
    $currentReviewStatus = strtolower($abstrak['status']);
}
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_reviewer') ?>

<div id="content">
    <main class="flex-fill" style="padding-top:70px;">
        <div class="container-fluid p-3 p-md-4">

            <!-- Breadcrumb & Header -->
            <div class="d-flex align-items-center gap-2 mb-4">
                <a href="<?= site_url('reviewer/abstrak') ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
                <div class="vr"></div>
                <h3 class="mb-0">Detail Abstrak untuk Review</h3>
                <?php if ($isAlreadyReviewed): ?>
                    <?php 
                    $badgeClass = match($currentReviewStatus) {
                        'diterima' => 'bg-success',
                        'ditolak' => 'bg-danger', 
                        'revisi' => 'bg-warning text-dark',
                        default => 'bg-info'
                    };
                    $statusText = match($currentReviewStatus) {
                        'diterima' => 'Diterima',
                        'ditolak' => 'Ditolak', 
                        'revisi' => 'Perlu Revisi',
                        default => 'Sudah Direview'
                    };
                    ?>
                    <span class="badge <?= $badgeClass ?> ms-2 fs-6">
                        <i class="bi bi-check-circle me-1"></i>
                        <?= $statusText ?>
                    </span>
                <?php else: ?>
                    <span class="badge bg-warning text-dark ms-2 fs-6">
                        <i class="bi bi-clock me-1"></i>
                        Menunggu Review
                    </span>
                <?php endif; ?>
            </div>

            <!-- Flash Messages -->
            <?php if (session()->getFlashdata('success')): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle me-2"></i>
                    <?= session()->getFlashdata('success') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <?php if (session()->getFlashdata('error')): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-circle me-2"></i>
                    <?= session()->getFlashdata('error') ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            <?php endif; ?>

            <div class="row g-4">
                <!-- Main Content -->
                <div class="col-12 col-xl-8">
                    <!-- Abstract Information Card -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-file-earmark-text me-2"></i>
                                Informasi Abstrak
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Abstract Title -->
                            <div class="mb-4">
                                <h4 class="fw-bold text-primary mb-2">
                                    <?= esc($abstrak['judul'] ?? 'Judul Tidak Tersedia') ?>
                                </h4>
                                <div class="text-muted small">
                                    <i class="bi bi-calendar-plus me-1"></i>
                                    Diupload: <?= $fmtDate($abstrak['tanggal_upload'] ?? null) ?>
                                </div>
                            </div>

                            <!-- Metadata Grid -->
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label class="form-label fw-semibold text-muted">Penulis</label>
                                        <div class="info-value d-flex align-items-center gap-2">
                                            <div class="user-avatar bg-primary-subtle text-primary">
                                                <i class="bi bi-person"></i>
                                            </div>
                                            <div>
                                                <div class="fw-medium"><?= esc($abstrak['nama_lengkap'] ?? '-') ?></div>
                                                <?php if (!empty($abstrak['email'])): ?>
                                                    <div class="small text-muted"><?= esc($abstrak['email']) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label class="form-label fw-semibold text-muted">Kategori</label>
                                        <div class="info-value">
                                            <span class="badge bg-info-subtle text-info px-3 py-2">
                                                <i class="bi bi-tag-fill me-1"></i>
                                                <?= esc($abstrak['nama_kategori'] ?? 'Tidak Dikategorikan') ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>

                                <?php if (!empty($abstrak['event_title'])): ?>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label class="form-label fw-semibold text-muted">Event</label>
                                        <div class="info-value">
                                            <div class="fw-medium text-dark">
                                                <i class="bi bi-calendar-event me-1 text-primary"></i>
                                                <?= esc($abstrak['event_title']) ?>
                                            </div>
                                            <?php if (!empty($abstrak['event_date'])): ?>
                                                <div class="small text-muted">
                                                    <?= date('d M Y', strtotime($abstrak['event_date'])) ?>
                                                    <?= !empty($abstrak['event_time']) ? ' • ' . esc($abstrak['event_time']) : '' ?>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label class="form-label fw-semibold text-muted">Status Review</label>
                                        <div class="info-value">
                                            <?php if ($isAlreadyReviewed): ?>
                                                <span class="badge <?= $badgeClass ?> px-3 py-2">
                                                    <i class="bi bi-check-circle me-1"></i>
                                                    <?= $statusText ?>
                                                </span>
                                                <?php if ($reviewData && $reviewData['tanggal_review']): ?>
                                                    <div class="small text-muted mt-1">
                                                        Direview: <?= $fmtDate($reviewData['tanggal_review']) ?>
                                                    </div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span class="badge bg-warning text-dark px-3 py-2">
                                                    <i class="bi bi-hourglass-split me-1"></i>
                                                    Menunggu Review Anda
                                                </span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- File Abstract -->
                            <?php if (!empty($abstrak['file_abstrak'])): ?>
                                <div class="alert alert-primary border-0">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-file-earmark-pdf fs-1 me-3 text-primary"></i>
                                        <div class="flex-fill">
                                            <h6 class="alert-heading mb-1">File Abstrak</h6>
                                            <p class="mb-2">Klik untuk membuka atau mengunduh file abstrak lengkap untuk review</p>
                                            <div class="small text-muted">
                                                <i class="bi bi-paperclip me-1"></i>
                                                <?= esc($abstrak['file_abstrak']) ?>
                                            </div>
                                        </div>
                                        <div class="d-flex flex-column gap-2">
                                            <a href="<?= site_url('reviewer/review/file/' . urlencode($abstrak['file_abstrak'])) ?>" 
                                               target="_blank" 
                                               class="btn btn-primary">
                                                <i class="bi bi-eye me-1"></i>
                                                Buka File
                                            </a>
                                            <a href="<?= site_url('reviewer/review/download/' . urlencode($abstrak['file_abstrak'])) ?>" 
                                               class="btn btn-outline-primary">
                                                <i class="bi bi-download me-1"></i>
                                                Download
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning border-0">
                                    <i class="bi bi-exclamation-triangle me-2"></i>
                                    <strong>Perhatian:</strong> File abstrak tidak tersedia atau belum diupload.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Review Section -->
                    <?php if ($isAlreadyReviewed && $reviewData): ?>
                        <!-- Completed Review Display -->
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-success text-white">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-check-circle me-2"></i>
                                    Review Telah Selesai
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Keputusan Review</label>
                                        <div>
                                            <span class="badge <?= $badgeClass ?> fs-6 px-3 py-2">
                                                <?= match($currentReviewStatus) {
                                                    'diterima' => '✓ Diterima (Accepted)',
                                                    'ditolak' => '✗ Ditolak (Rejected)', 
                                                    'revisi' => '⚠ Perlu Revisi',
                                                    default => ucfirst($currentReviewStatus)
                                                } ?>
                                            </span>
                                        </div>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold">Tanggal Review</label>
                                        <div class="d-flex align-items-center text-dark">
                                            <i class="bi bi-calendar-check me-2 text-success"></i>
                                            <?= $fmtDate($reviewData['tanggal_review']) ?>
                                        </div>
                                    </div>
                                    
                                    <div class="col-12">
                                        <label class="form-label fw-semibold">Komentar & Saran</label>
                                        <div class="review-comment p-3 bg-light rounded border-start border-4 border-primary">
                                            <?= nl2br(esc($reviewData['komentar'] ?? 'Tidak ada komentar')) ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <hr class="my-4">
                                
                                <div class="d-flex gap-2 flex-wrap">
                                    <a href="<?= site_url('reviewer/abstrak') ?>" class="btn btn-primary">
                                        <i class="bi bi-arrow-left me-1"></i>
                                        Kembali ke Daftar
                                    </a>
                                    <a href="<?= site_url('reviewer/riwayat') ?>" class="btn btn-outline-secondary">
                                        <i class="bi bi-clock-history me-1"></i>
                                        Lihat Riwayat
                                    </a>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- Review Form -->
                        <div class="card shadow-sm border-0">
                            <div class="card-header bg-warning text-dark">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-pencil-square me-2"></i>
                                    Form Review Abstrak
                                </h5>
                            </div>
                            <div class="card-body">
                                <form action="<?= site_url('reviewer/review/' . ($abstrak['id_abstrak'] ?? 0)) ?>" 
                                      method="post" 
                                      id="reviewForm">
                                    <?= csrf_field() ?>

                                    <div class="mb-4">
                                        <label for="keputusan" class="form-label fw-semibold">
                                            <i class="bi bi-clipboard-check me-1 text-primary"></i>
                                            Keputusan Review
                                            <span class="text-danger">*</span>
                                        </label>
                                        <select name="keputusan" id="keputusan" class="form-select form-select-lg" required>
                                            <option value="">-- Pilih Keputusan Review --</option>
                                            <option value="diterima" <?= old('keputusan') === 'diterima' ? 'selected' : '' ?>>
                                                ✓ Diterima (Accepted) - Abstrak memenuhi semua kriteria
                                            </option>
                                            <option value="revisi" <?= old('keputusan') === 'revisi' ? 'selected' : '' ?>>
                                                ⚠ Perlu Revisi - Ada bagian yang perlu diperbaiki
                                            </option>
                                            <option value="ditolak" <?= old('keputusan') === 'ditolak' ? 'selected' : '' ?>>
                                                ✗ Ditolak (Rejected) - Tidak memenuhi kriteria
                                            </option>
                                        </select>
                                        <div class="form-text">Pilih keputusan berdasarkan evaluasi menyeluruh terhadap abstrak</div>
                                        <?php if (isset($errors['keputusan'])): ?>
                                            <div class="invalid-feedback d-block"><?= $errors['keputusan'] ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="mb-4">
                                        <label for="komentar" class="form-label fw-semibold">
                                            <i class="bi bi-chat-left-text me-1 text-primary"></i>
                                            Komentar & Saran Perbaikan
                                            <span class="text-danger">*</span>
                                        </label>
                                        <textarea name="komentar" 
                                                  id="komentar" 
                                                  rows="6" 
                                                  class="form-control" 
                                                  placeholder="Berikan komentar konstruktif dan saran perbaikan yang spesifik untuk membantu penulis memahami keputusan review Anda...

Contoh komentar yang baik:
- Untuk Diterima: 'Abstrak ini menunjukkan metodologi yang solid dan hasil yang jelas. Kontribusi penelitian terhadap bidang X sangat relevan.'
- Untuk Revisi: 'Abstrak memiliki potensi baik, namun perlu memperjelas metodologi pada bagian Y dan menambahkan referensi terkini.'
- Untuk Ditolak: 'Abstrak tidak sesuai dengan tema conference dan metodologi yang digunakan kurang sesuai untuk menjawab research question.'"
                                                  required
                                                  minlength="10"
                                                  maxlength="1000"><?= old('komentar') ?></textarea>
                                        <div class="form-text d-flex justify-content-between">
                                            <span>Berikan feedback yang membantu penulis untuk perbaikan</span>
                                            <span><span id="charCount">0</span>/1000 karakter (min: 10)</span>
                                        </div>
                                        <?php if (isset($errors['komentar'])): ?>
                                            <div class="invalid-feedback d-block"><?= $errors['komentar'] ?></div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="alert alert-info border-0 mb-4">
                                        <i class="bi bi-info-circle me-2"></i>
                                        <strong>Catatan Penting:</strong> 
                                        Setelah review disimpan, keputusan tidak dapat diubah lagi. Pastikan Anda telah membaca abstrak dengan teliti dan memberikan evaluasi yang objektif.
                                    </div>

                                    <div class="d-flex gap-2 flex-wrap">
                                        <button type="submit" class="btn btn-success btn-lg">
                                            <i class="bi bi-check-circle me-2"></i>
                                            Simpan Review
                                        </button>
                                        <a href="<?= site_url('reviewer/abstrak') ?>" class="btn btn-outline-secondary btn-lg">
                                            <i class="bi bi-x-circle me-2"></i>
                                            Batalkan
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Sidebar -->
                <div class="col-12 col-xl-4">
                    <!-- Review Guidelines -->
                    <div class="card shadow-sm border-0 mb-4">
                        <div class="card-header bg-info text-white">
                            <h6 class="card-title mb-0">
                                <i class="bi bi-lightbulb me-2"></i>
                                Panduan Review
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="guideline-item mb-3">
                                <h6 class="fw-semibold text-success mb-1">
                                    <i class="bi bi-check-circle me-1"></i>
                                    Diterima (Accepted)
                                </h6>
                                <p class="small text-muted mb-0">
                                    Abstrak memenuhi semua kriteria akademik, metodologi jelas dan valid, hasil penelitian signifikan, dan sesuai dengan tema event.
                                </p>
                            </div>
                            
                            <div class="guideline-item mb-3">
                                <h6 class="fw-semibold text-warning mb-1">
                                    <i class="bi bi-exclamation-triangle me-1"></i>
                                    Perlu Revisi
                                </h6>
                                <p class="small text-muted mb-0">
                                    Abstrak memiliki potensi baik namun perlu perbaikan pada aspek tertentu seperti metodologi, referensi, penyajian hasil, atau kejelasan tujuan penelitian.
                                </p>
                            </div>
                            
                            <div class="guideline-item mb-3">
                                <h6 class="fw-semibold text-danger mb-1">
                                    <i class="bi bi-x-circle me-1"></i>
                                    Ditolak (Rejected)
                                </h6>
                                <p class="small text-muted mb-0">
                                    Abstrak tidak memenuhi kriteria akademik, tidak sesuai tema, metodologi tidak valid, atau memiliki masalah fundamental yang tidak dapat diperbaikan dengan revisi.
                                </p>
                            </div>

                            <hr class="my-3">
                            
                            <div class="alert alert-light border mb-0">
                                <h6 class="fw-semibold mb-2">
                                    <i class="bi bi-star me-1"></i>
                                    Tips Memberikan Review yang Berkualitas
                                </h6>
                                <ul class="small mb-0 ps-3">
                                    <li>Baca abstrak secara menyeluruh minimal 2 kali</li>
                                    <li>Evaluasi originalitas dan kontribusi penelitian</li>
                                    <li>Periksa metodologi dan validitas pendekatan</li>
                                    <li>Berikan saran konstruktif yang spesifik</li>
                                    <li>Gunakan bahasa yang profesional dan objektif</li>
                                    <li>Fokus pada kualitas akademik, bukan preferensi personal</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Review Info -->
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-secondary text-white">
                            <h6 class="card-title mb-0">
                                <i class="bi bi-person-badge me-2"></i>
                                Informasi Reviewer
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="reviewer-info">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="user-avatar bg-secondary-subtle text-secondary me-3">
                                        <i class="bi bi-person"></i>
                                    </div>
                                    <div>
                                        <div class="fw-semibold"><?= esc(session('nama_lengkap') ?? session('nama')) ?></div>
                                        <div class="small text-muted">Reviewer</div>
                                    </div>
                                </div>
                                
                                <div class="info-list">
                                    <div class="info-row">
                                        <span class="text-muted">Tanggal:</span>
                                        <span class="fw-medium"><?= date('d M Y') ?></span>
                                    </div>
                                    <div class="info-row">
                                        <span class="text-muted">Status:</span>
                                        <span class="fw-medium">
                                            <?= $isAlreadyReviewed ? 'Selesai' : 'Sedang Review' ?>
                                        </span>
                                    </div>
                                    <?php if ($reviewData && $reviewData['tanggal_review']): ?>
                                    <div class="info-row">
                                        <span class="text-muted">Review pada:</span>
                                        <span class="fw-medium small"><?= $fmtDate($reviewData['tanggal_review']) ?></span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                                
                                <hr class="my-3">
                                
                                <div class="text-center">
                                    <?php if ($isAlreadyReviewed): ?>
                                        <div class="text-success small">
                                            <i class="bi bi-check-circle me-1"></i>
                                            Review telah diselesaikan dengan baik
                                        </div>
                                    <?php else: ?>
                                        <div class="text-warning small">
                                            <i class="bi bi-exclamation-triangle me-1"></i>
                                            Review yang disimpan tidak dapat diubah
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<?= $this->include('partials/footer') ?>

<style>
body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
}

.card {
    border: none;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.12);
}

.card-header {
    border-radius: 12px 12px 0 0 !important;
    font-weight: 600;
}

.info-item {
    padding: 12px 0;
    border-bottom: 1px solid #f1f5f9;
}

.info-item:last-child {
    border-bottom: none;
}

.info-value {
    margin-top: 4px;
}

.user-avatar {
    width: 40px;
    height: 40px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.1rem;
    flex-shrink: 0;
}

.badge {
    font-weight: 500;
    border-radius: 8px;
}

.form-control, .form-select {
    border: 2px solid #e2e8f0;
    border-radius: 10px;
    transition: all 0.2s ease;
}

.form-control:focus, .form-select:focus {
    border-color: #3b82f6;
    box-shadow: 0 0 0 0.2rem rgba(59, 130, 246, 0.1);
}

.btn {
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.btn:hover {
    transform: translateY(-1px);
}

.review-comment {
    line-height: 1.6;
    font-size: 0.95rem;
}

.guideline-item {
    padding: 12px;
    background: #f8fafc;
    border-radius: 8px;
    border-left: 3px solid currentColor;
}

.info-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 4px 0;
    font-size: 0.9rem;
}

.bg-primary-subtle {
    background-color: rgba(13, 110, 253, 0.1) !important;
}

.bg-info-subtle {
    background-color: rgba(13, 202, 240, 0.1) !important;
}

.bg-secondary-subtle {
    background-color: rgba(108, 117, 125, 0.1) !important;
}

.alert {
    border-radius: 10px;
}

.form-select-lg {
    font-size: 1rem;
    padding: 12px 16px;
}

.btn-lg {
    padding: 12px 24px;
    font-size: 1.1rem;
}

/* Enhanced textarea styling */
#komentar {
    line-height: 1.6;
    resize: vertical;
    min-height: 120px;
}

#komentar::placeholder {
    color: #9ca3af;
    font-size: 0.9rem;
    line-height: 1.5;
}

/* Animation enhancements */
.form-control.is-invalid {
    border-color: #dc3545;
    animation: shake 0.3s ease-in-out;
}

.form-control.is-valid {
    border-color: #198754;
}

@keyframes shake {
    0%, 100% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
}

/* Custom Popup Styles */
.custom-popup {
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    border-radius: 15px;
    box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
    z-index: 10001;
    min-width: 350px;
    max-width: 500px;
    animation: popupSlideIn 0.3s ease-out;
}

.custom-backdrop {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0, 0, 0, 0.6);
    z-index: 10000;
    animation: fadeIn 0.3s ease-out;
}

.popup-header {
    padding: 20px 24px 16px;
    border-bottom: 1px solid #e5e7eb;
    border-radius: 15px 15px 0 0;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.popup-body {
    padding: 20px 24px 24px;
}

.popup-close {
    background: none;
    border: none;
    font-size: 1.5rem;
    color: #6b7280;
    cursor: pointer;
    padding: 0;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 50%;
    transition: all 0.2s;
}

.popup-close:hover {
    background: #f3f4f6;
    color: #374151;
}

/* Enhanced popup animations */
@keyframes popupSlideIn {
    from { 
        opacity: 0; 
        transform: translate(-50%, -60%) scale(0.9); 
    }
    to { 
        opacity: 1; 
        transform: translate(-50%, -50%) scale(1); 
    }
}

@keyframes fadeIn {
    from { opacity: 0; }
    to { opacity: 1; }
}

@keyframes slideInRight {
    from { 
        opacity: 0; 
        transform: translateX(100%); 
    }
    to { 
        opacity: 1; 
        transform: translateX(0); 
    }
}

@keyframes slideOutRight {
    from { 
        opacity: 1; 
        transform: translateX(0); 
    }
    to { 
        opacity: 0; 
        transform: translateX(100%); 
    }
}

/* Success notification */
.success-notification {
    position: fixed;
    top: 20px;
    right: 20px;
    z-index: 10002;
    min-width: 300px;
    max-width: 400px;
    animation: slideInRight 0.4s ease-out;
    background: white;
    border-radius: 12px;
    box-shadow: 0 10px 40px rgba(0, 0, 0, 0.15);
    overflow: hidden;
}

/* Enhanced loading overlay */
.loading-overlay {
    backdrop-filter: blur(3px);
    transition: all 0.3s ease;
}

.loading-overlay .spinner-border {
    width: 3rem !important;
    height: 3rem !important;
    border-width: 0.3em;
}

/* Responsive improvements */
@media (max-width: 768px) {
    .container-fluid {
        padding: 1rem !important;
    }
    
    .card-body {
        padding: 1rem;
    }
    
    .info-item {
        padding: 8px 0;
    }
    
    .user-avatar {
        width: 32px;
        height: 32px;
        font-size: 1rem;
    }
    
    .btn-lg {
        padding: 10px 20px;
        font-size: 1rem;
    }
    
    .custom-popup {
        min-width: 300px;
        margin: 0 20px;
        max-width: calc(100% - 40px);
    }
}

/* Badge size adjustments */
.badge.fs-6 {
    font-size: 0.9rem !important;
    padding: 0.375rem 0.75rem;
}

/* File section styling */
.alert-primary {
    background-color: rgba(13, 110, 253, 0.1);
    border-color: rgba(13, 110, 253, 0.2);
}

.alert-warning {
    background-color: rgba(255, 193, 7, 0.1);
    border-color: rgba(255, 193, 7, 0.2);
}

/* Loading state */
.btn:disabled {
    opacity: 0.6;
    transform: none !important;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Character counter with enhanced functionality
    const textarea = document.getElementById('komentar');
    const charCount = document.getElementById('charCount');
    
    if (textarea && charCount) {
        function updateCharCount() {
            const count = textarea.value.length;
            charCount.textContent = count;
            
            // Enhanced color feedback with smooth transitions
            if (count < 10) {
                charCount.style.color = '#dc3545';
                charCount.parentElement.className = 'form-text d-flex justify-content-between text-danger';
                textarea.classList.add('is-invalid');
                textarea.classList.remove('is-valid');
            } else if (count > 900) {
                charCount.style.color = '#fd7e14';
                charCount.parentElement.className = 'form-text d-flex justify-content-between text-warning';
                textarea.classList.remove('is-invalid', 'is-valid');
            } else {
                charCount.style.color = '#198754';
                charCount.parentElement.className = 'form-text d-flex justify-content-between text-success';
                textarea.classList.remove('is-invalid');
                textarea.classList.add('is-valid');
            }
        }
        
        textarea.addEventListener('input', updateCharCount);
        updateCharCount(); // Initial count
        
        // Auto-resize textarea with limits
        function autoResize() {
            textarea.style.height = 'auto';
            const newHeight = Math.min(Math.max(textarea.scrollHeight, 120), 300);
            textarea.style.height = newHeight + 'px';
        }
        
        textarea.addEventListener('input', autoResize);
        textarea.addEventListener('paste', () => setTimeout(autoResize, 10));
    }

    // Fixed form validation with proper popup handling
    const form = document.getElementById('reviewForm');
    if (form) {
        // Track if form is being submitted to avoid double submission
        let isSubmitting = false;
        
        form.addEventListener('submit', function(e) {
            e.preventDefault(); // Always prevent default first
            
            // Prevent double submission
            if (isSubmitting) {
                console.log('Form already being submitted, ignoring...');
                return false;
            }
            
            const keputusan = document.getElementById('keputusan').value;
            const komentar = textarea.value.trim();
            
            // Comprehensive validation
            if (!keputusan) {
                showValidationPopup('Mohon pilih keputusan review terlebih dahulu.');
                document.getElementById('keputusan').focus();
                return false;
            }
            
            if (komentar.length < 10) {
                showValidationPopup('Komentar minimal 10 karakter untuk memberikan feedback yang berguna.');
                textarea.focus();
                return false;
            }

            if (komentar.length > 1000) {
                showValidationPopup('Komentar tidak boleh lebih dari 1000 karakter.');
                textarea.focus();
                return false;
            }
            
            // Show confirmation popup
            const keputusanText = keputusan.charAt(0).toUpperCase() + keputusan.slice(1);
            showConfirmationPopup(keputusanText, komentar, form);
        });
    }

    // Custom validation popup
    function showValidationPopup(message) {
        // Remove existing popups
        removeExistingPopups();
        
        const backdrop = document.createElement('div');
        backdrop.className = 'custom-backdrop';
        
        const popup = document.createElement('div');
        popup.className = 'custom-popup';
        popup.innerHTML = `
            <div class="popup-header bg-danger text-white">
                <div class="d-flex align-items-center">
                    <i class="bi bi-exclamation-triangle-fill me-2 fs-5"></i>
                    <span class="fw-semibold">Validasi Error</span>
                </div>
                <button class="popup-close text-white" onclick="closePopup(this)">
                    <i class="bi bi-x"></i>
                </button>
            </div>
            <div class="popup-body">
                <p class="mb-3">${message}</p>
                <div class="d-flex gap-2 justify-content-end">
                    <button class="btn btn-primary" onclick="closePopup(this)">
                        <i class="bi bi-check me-1"></i>Mengerti
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(backdrop);
        document.body.appendChild(popup);
        
        // Close handlers
        backdrop.onclick = () => closePopup(popup);
        
        // Auto close after 8 seconds
        setTimeout(() => {
            if (document.body.contains(popup)) {
                closePopup(popup);
            }
        }, 8000);
    }

    // Custom confirmation popup
    function showConfirmationPopup(keputusan, komentar, form) {
        // Remove existing popups
        removeExistingPopups();
        
        const badgeColor = keputusan.toLowerCase() === 'diterima' ? 'success' : 
                          keputusan.toLowerCase() === 'revisi' ? 'warning' : 'danger';
        
        const backdrop = document.createElement('div');
        backdrop.className = 'custom-backdrop';
        
        const popup = document.createElement('div');
        popup.className = 'custom-popup';
        popup.innerHTML = `
            <div class="popup-header bg-primary text-white">
                <div class="d-flex align-items-center">
                    <i class="bi bi-clipboard-check-fill me-2 fs-5"></i>
                    <span class="fw-semibold">Konfirmasi Review</span>
                </div>
                <button class="popup-close text-white" onclick="closePopup(this)">
                    <i class="bi bi-x"></i>
                </button>
            </div>
            <div class="popup-body">
                <div class="mb-3">
                    <h6 class="fw-semibold mb-3">Ringkasan Review:</h6>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="text-muted small">Keputusan:</span>
                        <span class="badge bg-${badgeColor} px-3 py-1">
                            ${keputusan}
                        </span>
                    </div>
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <span class="text-muted small">Panjang komentar:</span>
                        <span class="fw-medium">${komentar.length} karakter</span>
                    </div>
                    <div class="alert alert-warning border-0 mb-3">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>PERHATIAN:</strong> Review yang disimpan tidak dapat diubah lagi!
                    </div>
                    <p class="text-muted mb-0">Pastikan keputusan dan komentar Anda sudah tepat sebelum menyimpan.</p>
                </div>
                <div class="d-flex gap-2 justify-content-end">
                    <button class="btn btn-secondary" onclick="closePopup(this)">
                        <i class="bi bi-x me-1"></i>Batal
                    </button>
                    <button class="btn btn-success" onclick="confirmReview('${form.id}')">
                        <i class="bi bi-check-circle me-1"></i>Ya, Simpan Review
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(backdrop);
        document.body.appendChild(popup);
        
        // Close handlers
        backdrop.onclick = () => closePopup(popup);
    }

    // Success notification
    function showSuccessNotification(message) {
        const notification = document.createElement('div');
        notification.className = 'success-notification';
        notification.innerHTML = `
            <div class="d-flex align-items-center bg-success text-white p-3">
                <i class="bi bi-check-circle-fill me-2 fs-5"></i>
                <span class="fw-semibold flex-fill">Review Berhasil!</span>
                <button class="popup-close text-white" onclick="closeNotification(this)">
                    <i class="bi bi-x"></i>
                </button>
            </div>
            <div class="p-3">
                <p class="mb-0">${message}</p>
            </div>
        `;
        
        document.body.appendChild(notification);
        
        // Auto remove after 5 seconds
        setTimeout(() => {
            if (document.body.contains(notification)) {
                notification.style.animation = 'slideOutRight 0.3s ease-in';
                setTimeout(() => {
                    if (document.body.contains(notification)) {
                        notification.remove();
                    }
                }, 300);
            }
        }, 5000);
    }

    // Enhanced loading state
    function showLoadingState(form) {
        const submitBtn = form.querySelector('button[type="submit"]');
        const cancelBtn = form.querySelector('a[href*="abstrak"]');
        
        if (submitBtn) {
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Menyimpan Review...';
        }
        
        if (cancelBtn) {
            cancelBtn.style.pointerEvents = 'none';
            cancelBtn.classList.add('disabled');
        }
        
        // Create enhanced loading overlay
        const overlay = document.createElement('div');
        overlay.className = 'position-fixed top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center loading-overlay';
        overlay.style.cssText = `
            background: rgba(0,0,0,0.6);
            z-index: 10003;
            backdrop-filter: blur(3px);
        `;
        overlay.innerHTML = `
            <div class="text-center text-white p-4 rounded-3" style="background: rgba(0,0,0,0.8);">
                <div class="spinner-border mb-3" role="status" style="width: 3rem; height: 3rem;"></div>
                <h5>Menyimpan Review</h5>
                <p class="mb-0 small">Harap tunggu, review sedang diproses...</p>
            </div>
        `;
        document.body.appendChild(overlay);
        
        // Fallback removal
        setTimeout(() => {
            if (document.body.contains(overlay)) {
                document.body.removeChild(overlay);
            }
        }, 15000);
    }

    // Global helper functions
    window.closePopup = function(element) {
        const popup = element.closest('.custom-popup') || element;
        const backdrop = document.querySelector('.custom-backdrop');
        if (popup) popup.remove();
        if (backdrop) backdrop.remove();
    };

    window.closeNotification = function(element) {
        const notification = element.closest('.success-notification');
        if (notification) {
            notification.style.animation = 'slideOutRight 0.3s ease-in';
            setTimeout(() => notification.remove(), 300);
        }
    };

    // FIXED: Simplified form submission that actually works
    window.confirmReview = function(formId) {
        const form = document.getElementById(formId);
        if (form && !form.dataset.submitting) {
            // Mark form as being submitted
            form.dataset.submitting = 'true';
            
            // Close popup first
            removeExistingPopups();
            
            // Show loading state
            showLoadingState(form);
            
            // Simple and reliable form submission after a short delay
            setTimeout(() => {
                console.log('Submitting form to:', form.action);
                form.submit();
            }, 500);
        }
    };

    function removeExistingPopups() {
        const existingPopups = document.querySelectorAll('.custom-popup, .custom-backdrop');
        existingPopups.forEach(popup => popup.remove());
    }

    // Enhanced select validation
    const keputusanSelect = document.getElementById('keputusan');
    if (keputusanSelect) {
        keputusanSelect.addEventListener('change', function() {
            if (this.value) {
                this.classList.remove('is-invalid');
                this.classList.add('is-valid');
            } else {
                this.classList.remove('is-valid');
            }
        });
    }

    // Auto-dismiss alerts with fade effect
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        setTimeout(() => {
            if (alert && alert.parentNode) {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(() => {
                    if (alert.parentNode) {
                        alert.remove();
                    }
                }, 300);
            }
        }, 5000);
    });

    // File link interaction enhancements
    const fileLinks = document.querySelectorAll('a[href*="review/file"], a[href*="review/download"]');
    fileLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            const icon = this.querySelector('i');
            if (icon) {
                const originalClass = icon.className;
                icon.className = 'bi bi-hourglass-split me-1';
                this.classList.add('disabled');
                
                setTimeout(() => {
                    icon.className = originalClass;
                    this.classList.remove('disabled');
                }, 1500);
            }
        });
    });

    // Enhanced keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Escape to close popups
        if (e.key === 'Escape') {
            const popup = document.querySelector('.custom-popup');
            if (popup) {
                closePopup(popup);
                return;
            }
        }
        
        // Ctrl+Enter to submit (with validation)
        if ((e.ctrlKey || e.metaKey) && e.key === 'Enter' && form) {
            const submitBtn = form.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                e.preventDefault();
                form.dispatchEvent(new Event('submit', { bubbles: true, cancelable: true }));
            }
        }
    });

    // Form change tracking for unsaved changes warning
    if (form) {
        let formChanged = false;
        const formElements = [keputusanSelect, textarea];
        
        formElements.forEach(element => {
            if (element) {
                ['change', 'input'].forEach(eventType => {
                    element.addEventListener(eventType, () => formChanged = true);
                });
            }
        });

        window.addEventListener('beforeunload', function(e) {
            if (formChanged && !form.querySelector('button[type="submit"]').disabled) {
                e.preventDefault();
                e.returnValue = 'Anda memiliki perubahan yang belum disimpan. Yakin ingin meninggalkan halaman?';
                return e.returnValue;
            }
        });

        form.addEventListener('submit', () => formChanged = false);
    }

    // Check for success message and show notification
    const successAlert = document.querySelector('.alert-success');
    if (successAlert) {
        const message = successAlert.textContent.replace(/×/g, '').trim();
        if (message) {
            showSuccessNotification(message);
        }
    }

    // Debug mode - remove after testing
    if (window.location.search.includes('debug=1')) {
        console.log('Debug mode enabled');
        window.debugReview = function() {
            console.log('Form:', document.getElementById('reviewForm'));
            console.log('Keputusan:', document.getElementById('keputusan').value);
            console.log('Komentar length:', document.getElementById('komentar').value.length);
        };
    }
});
</script>