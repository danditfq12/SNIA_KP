<?php
$title = 'Detail Abstrak - Review';
$breadcrumb = 'Detail Abstrak';

// Default values untuk safety
$abstrak = $abstrak ?? [];
$fmtDate = fn($d) => $d ? date('d M Y H:i', strtotime($d)) : '-';
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_reviewer') ?>

<div id="content">
    <main class="flex-fill" style="padding-top:70px;">
        <div class="container-fluid p-3 p-md-4">

            <!-- Header with breadcrumb -->
            <div class="d-flex align-items-center gap-2 mb-3">
                <a href="<?= site_url('reviewer/abstrak') ?>" class="btn btn-light btn-sm">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
                <div class="text-muted small">
                    <i class="bi bi-house"></i> Dashboard > 
                    <a href="<?= site_url('reviewer/abstrak') ?>" class="text-decoration-none">Abstrak</a> > 
                    Detail
                </div>
            </div>

            <!-- Main content -->
            <div class="row g-4">
                <!-- Abstrak Info Card -->
                <div class="col-12 col-lg-8">
                    <div class="card shadow-sm border-0">
                        <div class="card-header bg-white border-bottom">
                            <h4 class="card-title mb-0">
                                <i class="bi bi-file-earmark-text me-2 text-primary"></i>
                                Detail Abstrak
                            </h4>
                        </div>
                        <div class="card-body">
                            <!-- Title -->
                            <div class="mb-4">
                                <h5 class="text-primary mb-2"><?= esc($abstrak['judul'] ?? 'Judul Tidak Tersedia') ?></h5>
                                <div class="small text-muted">
                                    Diupload: <?= esc($fmtDate($abstrak['tanggal_upload'] ?? null)) ?>
                                </div>
                            </div>

                            <!-- Metadata Grid -->
                            <div class="row g-3 mb-4">
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <i class="bi bi-person-circle text-muted me-2"></i>
                                        <strong>Penulis:</strong>
                                        <div class="ms-4">
                                            <?= esc($abstrak['nama_lengkap'] ?? '-') ?>
                                            <?php if (!empty($abstrak['email'])): ?>
                                                <br><small class="text-muted"><?= esc($abstrak['email']) ?></small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <i class="bi bi-tag text-muted me-2"></i>
                                        <strong>Kategori:</strong>
                                        <div class="ms-4">
                                            <span class="badge bg-info-subtle text-info">
                                                <?= esc($abstrak['nama_kategori'] ?? 'Tidak Dikategorikan') ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <i class="bi bi-flag text-muted me-2"></i>
                                        <strong>Status Saat Ini:</strong>
                                        <div class="ms-4">
                                            <?php
                                            $status = strtolower($abstrak['status'] ?? 'menunggu');
                                            $badgeClass = match($status) {
                                                'diterima' => 'bg-success',
                                                'ditolak' => 'bg-danger', 
                                                'revisi' => 'bg-warning text-dark',
                                                default => 'bg-secondary'
                                            };
                                            ?>
                                            <span class="badge <?= $badgeClass ?>">
                                                <?= ucfirst($status) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <?php if (!empty($abstrak['event_title'])): ?>
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <i class="bi bi-calendar-event text-muted me-2"></i>
                                        <strong>Event:</strong>
                                        <div class="ms-4">
                                            <?= esc($abstrak['event_title']) ?>
                                            <?php if (!empty($abstrak['event_date'])): ?>
                                                <br><small class="text-muted">
                                                    <?= date('d M Y', strtotime($abstrak['event_date'])) ?>
                                                    <?= !empty($abstrak['event_time']) ? ' - ' . $abstrak['event_time'] : '' ?>
                                                </small>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>

                            <!-- File Download -->
                            <?php if (!empty($abstrak['file_abstrak'])): ?>
                                <div class="alert alert-info border-0 bg-info-subtle">
                                    <div class="d-flex align-items-center">
                                        <i class="bi bi-download me-3 fs-4"></i>
                                        <div class="flex-fill">
                                            <div class="fw-semibold">File Abstrak</div>
                                            <div class="small">Klik untuk mengunduh dan membaca abstrak lengkap</div>
                                        </div>
                                        <a href="<?= base_url('uploads/abstrak/' . esc($abstrak['file_abstrak'])) ?>" 
                                           target="_blank" 
                                           class="btn btn-info">
                                            <i class="bi bi-file-earmark-pdf me-1"></i>
                                            Download
                                        </a>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Review Form Card -->
                <div class="col-12 col-lg-4">
                    <div class="card shadow-sm border-0 sticky-top" style="top: 80px;">
                        <div class="card-header bg-primary text-white">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-pencil-square me-2"></i>
                                Form Review
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="post" action="<?= site_url('reviewer/review/' . ($abstrak['id_abstrak'] ?? 0)) ?>" id="reviewForm">
                                <?= csrf_field() ?>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">
                                        <i class="bi bi-check-circle me-1"></i>
                                        Keputusan Review
                                        <span class="text-danger">*</span>
                                    </label>
                                    <select name="keputusan" class="form-select" required>
                                        <option value="">-- Pilih Keputusan --</option>
                                        <option value="Accepted">
                                            <i class="bi bi-check-lg"></i> Accepted (Diterima)
                                        </option>
                                        <option value="Rejected">
                                            <i class="bi bi-x-lg"></i> Rejected (Ditolak)
                                        </option>
                                        <option value="Revisi">
                                            <i class="bi bi-arrow-repeat"></i> Revisi (Perlu Perbaikan)
                                        </option>
                                    </select>
                                    <div class="form-text">
                                        Pilih keputusan berdasarkan kualitas abstrak
                                    </div>
                                </div>

                                <div class="mb-4">
                                    <label class="form-label fw-semibold">
                                        <i class="bi bi-chat-left-text me-1"></i>
                                        Komentar & Feedback
                                        <span class="text-danger">*</span>
                                    </label>
                                    <textarea name="komentar" 
                                              class="form-control" 
                                              rows="5" 
                                              placeholder="Berikan komentar konstruktif untuk penulis..."
                                              required
                                              minlength="10"
                                              maxlength="1000"></textarea>
                                    <div class="form-text">
                                        <span id="charCount">0</span>/1000 karakter (minimal 10)
                                    </div>
                                </div>

                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-success btn-lg">
                                        <i class="bi bi-check-lg me-2"></i>
                                        Simpan Review
                                    </button>
                                    <a href="<?= site_url('reviewer/abstrak') ?>" class="btn btn-outline-secondary">
                                        <i class="bi bi-x-lg me-2"></i>
                                        Batalkan
                                    </a>
                                </div>
                            </form>
                        </div>
                        <div class="card-footer bg-light-subtle text-center">
                            <small class="text-muted">
                                <i class="bi bi-info-circle me-1"></i>
                                Review yang disimpan tidak dapat diubah
                            </small>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </main>
</div>

<?= $this->include('partials/footer') ?>

<style>
.info-item {
    display: flex;
    align-items: flex-start;
    gap: 8px;
    margin-bottom: 16px;
}

.info-item strong {
    min-width: 80px;
    color: #374151;
}

.card {
    border-radius: 12px;
    overflow: hidden;
}

.card-header {
    border-radius: 12px 12px 0 0;
}

.form-select:focus,
.form-control:focus {
    border-color: #2563eb;
    box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.25);
}

.btn {
    border-radius: 8px;
}

.alert {
    border-radius: 10px;
}

@media (max-width: 991.98px) {
    .sticky-top {
        position: relative !important;
        top: auto !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Character counter for textarea
    const textarea = document.querySelector('textarea[name="komentar"]');
    const charCount = document.getElementById('charCount');
    
    if (textarea && charCount) {
        textarea.addEventListener('input', function() {
            const count = this.value.length;
            charCount.textContent = count;
            
            // Change color based on length
            if (count < 10) {
                charCount.style.color = '#dc3545'; // Red
            } else if (count > 900) {
                charCount.style.color = '#fd7e14'; // Orange
            } else {
                charCount.style.color = '#198754'; // Green
            }
        });
    }
    
    // Form submission confirmation
    const form = document.getElementById('reviewForm');
    if (form) {
        form.addEventListener('submit', function(e) {
            const keputusan = form.querySelector('select[name="keputusan"]').value;
            const komentar = form.querySelector('textarea[name="komentar"]').value.trim();
            
            if (!keputusan || !komentar) {
                e.preventDefault();
                alert('Mohon lengkapi keputusan dan komentar sebelum menyimpan review.');
                return false;
            }
            
            if (komentar.length < 10) {
                e.preventDefault();
                alert('Komentar minimal 10 karakter untuk memberikan feedback yang berguna.');
                return false;
            }
            
            const confirmMsg = `Apakah Anda yakin ingin menyimpan review dengan keputusan "${keputusan}"?\n\nReview yang disimpan tidak dapat diubah lagi.`;
            
            if (!confirm(confirmMsg)) {
                e.preventDefault();
                return false;
            }
        });
    }
});
</script>