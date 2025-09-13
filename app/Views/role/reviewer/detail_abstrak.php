<?php
// Default variables
$title = 'Detail Abstrak';
$abstrak = $abstrak ?? [];

// Helper functions
$fmtDate = fn($d) => $d ? date('d M Y H:i', strtotime($d)) : '-';
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_reviewer') ?>

<div id="content">
    <main class="flex-fill" style="padding-top:70px;">
        <div class="container-fluid p-3 p-md-4">

            <!-- Breadcrumb & Header -->
            <div class="d-flex align-items-center gap-2 mb-3">
                <a href="<?= site_url('reviewer/abstrak') ?>" class="btn btn-outline-secondary btn-sm">
                    <i class="bi bi-arrow-left"></i> Kembali
                </a>
                <div class="vr"></div>
                <h3 class="mb-0">Detail Abstrak</h3>
            </div>

            <div class="row g-3">
                <!-- Main Content -->
                <div class="col-12 col-lg-8">
                    <!-- Abstrak Info -->
                    <div class="card shadow-sm mb-4">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-file-earmark-text me-2 text-primary"></i>
                                Informasi Abstrak
                            </h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-12">
                                    <h4 class="fw-bold text-primary mb-3">
                                        <?= esc($abstrak['judul'] ?? 'Tanpa Judul') ?>
                                    </h4>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label class="form-label fw-semibold">Penulis</label>
                                        <div class="info-value">
                                            <i class="bi bi-person-circle me-2 text-muted"></i>
                                            <?= esc($abstrak['nama_lengkap'] ?? '-') ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label class="form-label fw-semibold">Kategori</label>
                                        <div class="info-value">
                                            <i class="bi bi-tags me-2 text-muted"></i>
                                            <?= esc($abstrak['nama_kategori'] ?? '-') ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label class="form-label fw-semibold">Event</label>
                                        <div class="info-value">
                                            <i class="bi bi-calendar-event me-2 text-muted"></i>
                                            <?= esc($abstrak['event_title'] ?? '-') ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label class="form-label fw-semibold">Tanggal Upload</label>
                                        <div class="info-value">
                                            <i class="bi bi-clock me-2 text-muted"></i>
                                            <?= $fmtDate($abstrak['tanggal_upload'] ?? null) ?>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label class="form-label fw-semibold">Status Saat Ini</label>
                                        <div class="info-value">
                                            <?php 
                                            $status = strtolower($abstrak['status'] ?? '');
                                            $badgeClass = match($status) {
                                                'diterima' => 'bg-success',
                                                'ditolak' => 'bg-danger', 
                                                'revisi' => 'bg-warning text-dark',
                                                default => 'bg-secondary'
                                            };
                                            ?>
                                            <span class="badge <?= $badgeClass ?>">
                                                <?= esc(ucfirst($abstrak['status'] ?? 'Pending')) ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="info-item">
                                        <label class="form-label fw-semibold">File Abstrak</label>
                                        <div class="info-value">
                                            <?php if (!empty($abstrak['file_abstrak'])): ?>
                                                <a href="<?= base_url('uploads/abstrak/' . $abstrak['file_abstrak']) ?>" 
                                                   target="_blank" 
                                                   class="btn btn-outline-primary btn-sm">
                                                    <i class="bi bi-file-earmark-pdf me-1"></i>
                                                    Lihat File
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">File tidak tersedia</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Review -->
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-clipboard-check me-2 text-success"></i>
                                Form Review
                            </h5>
                        </div>
                        <div class="card-body">
                            <form action="<?= site_url('reviewer/abstrak/review/save') ?>" method="post" id="reviewForm">
                                <?= csrf_field() ?>
                                <input type="hidden" name="id_abstrak" value="<?= (int)($abstrak['id_abstrak'] ?? 0) ?>">

                                <div class="mb-4">
                                    <label for="keputusan" class="form-label fw-semibold">
                                        Keputusan Review <span class="text-danger">*</span>
                                    </label>
                                    <select name="keputusan" id="keputusan" class="form-select" required>
                                        <option value="">-- Pilih Keputusan --</option>
                                        <option value="diterima">✓ Diterima (Accepted)</option>
                                        <option value="revisi">⚠ Perlu Revisi</option>
                                        <option value="ditolak">✗ Ditolak (Rejected)</option>
                                    </select>
                                    <div class="form-text">Pilih keputusan review berdasarkan evaluasi Anda</div>
                                </div>

                                <div class="mb-4">
                                    <label for="komentar" class="form-label fw-semibold">
                                        Komentar & Saran <span class="text-danger">*</span>
                                    </label>
                                    <textarea name="komentar" 
                                              id="komentar" 
                                              rows="6" 
                                              class="form-control" 
                                              placeholder="Berikan komentar detail mengenai abstrak ini, termasuk kelebihan, kekurangan, dan saran perbaikan..."
                                              required></textarea>
                                    <div class="form-text">
                                        Minimal 20 karakter. Berikan feedback konstruktif untuk membantu penulis.
                                    </div>
                                </div>

                                <div class="d-flex gap-2 flex-wrap">
                                    <button type="submit" class="btn btn-success">
                                        <i class="bi bi-check-circle me-1"></i>
                                        Simpan Review
                                    </button>
                                    <a href="<?= site_url('reviewer/abstrak') ?>" class="btn btn-outline-secondary">
                                        <i class="bi bi-arrow-left me-1"></i>
                                        Batal
                                    </a>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Sidebar Info -->
                <div class="col-12 col-lg-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-white">
                            <h6 class="card-title mb-0">
                                <i class="bi bi-info-circle me-2 text-info"></i>
                                Panduan Review
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="small">
                                <div class="mb-3">
                                    <h6 class="fw-semibold text-success">
                                        <i class="bi bi-check-circle me-1"></i>
                                        Diterima
                                    </h6>
                                    <p class="mb-0 text-muted">
                                        Abstrak memenuhi semua kriteria dan siap untuk dipresentasikan.
                                    </p>
                                </div>
                                
                                <div class="mb-3">
                                    <h6 class="fw-semibold text-warning">
                                        <i class="bi bi-exclamation-triangle me-1"></i>
                                        Perlu Revisi
                                    </h6>
                                    <p class="mb-0 text-muted">
                                        Abstrak memiliki potensi tapi perlu perbaikan pada beberapa aspek.
                                    </p>
                                </div>
                                
                                <div class="mb-3">
                                    <h6 class="fw-semibold text-danger">
                                        <i class="bi bi-x-circle me-1"></i>
                                        Ditolak
                                    </h6>
                                    <p class="mb-0 text-muted">
                                        Abstrak tidak memenuhi kriteria atau tidak sesuai dengan tema.
                                    </p>
                                </div>

                                <hr class="my-3">
                                
                                <div class="alert alert-info">
                                    <i class="bi bi-lightbulb me-2"></i>
                                    <strong>Tips:</strong> Berikan komentar yang konstruktif dan spesifik untuk membantu penulis memahami keputusan Anda.
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
.info-item {
    padding: 12px 0;
    border-bottom: 1px solid #f1f5f9;
}

.info-item:last-child {
    border-bottom: none;
}

.info-item .form-label {
    margin-bottom: 4px;
    color: #64748b;
    font-size: 0.875rem;
}

.info-value {
    color: #1e293b;
    font-weight: 500;
}

.card-header {
    border-bottom: 1px solid #f1f5f9;
}

#reviewForm .form-control:focus,
#reviewForm .form-select:focus {
    border-color: #10b981;
    box-shadow: 0 0 0 0.2rem rgba(16, 185, 129, 0.25);
}

.alert-info {
    background-color: #f0f9ff;
    border-color: #0ea5e9;
    color: #0369a1;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('reviewForm');
    const keputusanSelect = document.getElementById('keputusan');
    const komentarTextarea = document.getElementById('komentar');

    // Form validation
    form.addEventListener('submit', function(e) {
        const komentar = komentarTextarea.value.trim();
        
        if (komentar.length < 20) {
            e.preventDefault();
            alert('Komentar harus minimal 20 karakter untuk memberikan feedback yang bermakna.');
            komentarTextarea.focus();
            return false;
        }

        // Confirm before submit
        const keputusan = keputusanSelect.value;
        const keputusanText = keputusanSelect.options[keputusanSelect.selectedIndex].text;
        
        if (!confirm(`Apakah Anda yakin dengan keputusan: "${keputusanText}"?\n\nSetelah disimpan, review tidak dapat diubah.`)) {
            e.preventDefault();
            return false;
        }
    });

    // Character counter for comment
    komentarTextarea.addEventListener('input', function() {
        const length = this.value.length;
        const minLength = 20;
        
        if (length < minLength) {
            this.classList.add('is-invalid');
        } else {
            this.classList.remove('is-invalid');
            this.classList.add('is-valid');
        }
    });

    // Auto-resize textarea
    komentarTextarea.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
});
</script>