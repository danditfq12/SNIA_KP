<?php
$title = 'Riwayat Review';

// Default variables untuk hindari notice
$riwayat = $riwayat ?? [];

// Helper functions
$badgeClass = function($keputusan) {
    $status = strtolower(trim((string)$keputusan));
    return match(true) {
        in_array($status, ['accepted', 'diterima']) => 'bg-success',
        in_array($status, ['revisi', 'revision']) => 'bg-warning text-dark',
        in_array($status, ['rejected', 'ditolak']) => 'bg-danger',
        default => 'bg-secondary'
    };
};

$formatDateTime = fn($datetime) => $datetime ? date('d M Y H:i', strtotime($datetime)) : '-';
$formatDate = fn($date) => $date ? date('d M Y', strtotime($date)) : '-';
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_reviewer') ?>

<div id="content">
    <main class="flex-fill" style="padding-top:70px;">
        <div class="container-fluid p-3 p-md-4">

            <!-- HEADER SECTION -->
            <div class="header-section d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h3 class="welcome-text mb-1">
                        <i class="bi bi-clock-history me-2"></i>Riwayat Review
                    </h3>
                    <small class="text-muted">Semua review yang pernah Anda kerjakan</small>
                </div>
                <div class="d-flex align-items-center gap-2">
                    <div class="badge bg-primary-subtle text-primary fs-6 px-3 py-2">
                        <i class="bi bi-list-check me-1"></i><?= count($riwayat) ?> Review
                    </div>
                    <a href="<?= site_url('reviewer/abstrak') ?>" class="btn btn-primary d-none d-md-block">
                        <i class="bi bi-journal-text me-1"></i> Tugas Baru
                    </a>
                </div>
            </div>

            <!-- FILTER TOOLBAR -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <div class="row g-3 align-items-center">
                        <div class="col-12 col-md-6">
                            <div class="input-group">
                                <span class="input-group-text bg-primary text-white border-0">
                                    <i class="bi bi-search"></i>
                                </span>
                                <input type="search" id="searchInput" class="form-control border-start-0" 
                                       placeholder="Cari judul, penulis, atau kategori...">
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <select id="statusFilter" class="form-select">
                                <option value="">Semua Keputusan</option>
                                <option value="diterima">Diterima</option>
                                <option value="revisi">Revisi</option>
                                <option value="ditolak">Ditolak</option>
                                <option value="pending">Pending</option>
                            </select>
                        </div>
                        <div class="col-6 col-md-3 text-end">
                            <button id="resetFilter" class="btn btn-outline-secondary">
                                <i class="bi bi-arrow-clockwise me-1"></i>Reset
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($riwayat)): ?>
                <!-- DESKTOP VIEW: Table -->
                <div class="d-none d-lg-block">
                    <div class="card shadow-sm border-0">
                        <div class="card-header d-flex align-items-center justify-content-between">
                            <h5 class="card-title mb-0">
                                <i class="bi bi-table me-2 text-primary"></i>Data Riwayat Review
                            </h5>
                            <span class="badge bg-info-subtle text-info">
                                <?= count($riwayat) ?> total
                            </span>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="reviewTable">
                                <thead class="table-light">
                                    <tr>
                                        <th class="fw-semibold">Judul Abstrak</th>
                                        <th class="fw-semibold">Penulis</th>
                                        <th class="fw-semibold">Kategori</th>
                                        <th class="fw-semibold">Keputusan</th>
                                        <th class="fw-semibold">Tanggal Review</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($riwayat as $review): ?>
                                        <?php
                                            $keputusan = strtolower(trim((string)($review['keputusan'] ?? '')));
                                            $searchText = strtolower(
                                                ($review['judul'] ?? '') . ' ' . 
                                                ($review['nama_lengkap'] ?? '') . ' ' . 
                                                ($review['nama_kategori'] ?? '')
                                            );
                                        ?>
                                        <tr class="review-row" 
                                            data-search="<?= esc($searchText) ?>"
                                            data-status="<?= esc($keputusan) ?>">
                                            <td>
                                                <div class="fw-semibold text-dark mb-1">
                                                    <?= esc($review['judul'] ?? '-') ?>
                                                </div>
                                                <div class="small text-muted">
                                                    <i class="bi bi-calendar-plus me-1"></i>Upload: <?= $formatDate($review['tanggal_upload'] ?? null) ?>
                                                </div>
                                            </td>
                                            <td>
                                                <div class="d-flex align-items-center gap-2">
                                                    <div class="user-avatar bg-primary-subtle text-primary">
                                                        <i class="bi bi-person"></i>
                                                    </div>
                                                    <div>
                                                        <div class="text-dark"><?= esc($review['nama_lengkap'] ?? '-') ?></div>
                                                        <div class="small text-muted"><?= esc($review['email'] ?? '') ?></div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-info-subtle text-info px-3 py-2">
                                                    <i class="bi bi-tag-fill me-1"></i><?= esc($review['nama_kategori'] ?? '-') ?>
                                                </span>
                                            </td>
                                            <td>
                                                <span class="badge <?= $badgeClass($keputusan) ?> px-3 py-2">
                                                    <?= esc(ucfirst($review['keputusan'] ?? 'Pending')) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="text-dark fw-medium"><?= $formatDateTime($review['tanggal_review'] ?? null) ?></div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- MOBILE VIEW: Cards -->
                <div class="d-block d-lg-none">
                    <div id="reviewCards" class="row g-3">
                        <?php foreach ($riwayat as $review): ?>
                            <?php
                                $keputusan = strtolower(trim((string)($review['keputusan'] ?? '')));
                                $searchText = strtolower(
                                    ($review['judul'] ?? '') . ' ' . 
                                    ($review['nama_lengkap'] ?? '') . ' ' . 
                                    ($review['nama_kategori'] ?? '')
                                );
                            ?>
                            <div class="col-12">
                                <div class="card shadow-sm border-0 review-card h-100" 
                                     data-search="<?= esc($searchText) ?>"
                                     data-status="<?= esc($keputusan) ?>">
                                    <div class="card-body">
                                        <div class="d-flex justify-content-between align-items-start mb-3">
                                            <div class="flex-grow-1 me-3">
                                                <h6 class="card-title text-dark mb-2">
                                                    <?= esc($review['judul'] ?? '-') ?>
                                                </h6>
                                                <div class="d-flex align-items-center gap-2 mb-2">
                                                    <div class="user-avatar-sm bg-primary-subtle text-primary">
                                                        <i class="bi bi-person"></i>
                                                    </div>
                                                    <div class="text-muted small">
                                                        <?= esc($review['nama_lengkap'] ?? '-') ?>
                                                    </div>
                                                </div>
                                            </div>
                                            <span class="badge <?= $badgeClass($keputusan) ?>">
                                                <?= esc(ucfirst($review['keputusan'] ?? 'Pending')) ?>
                                            </span>
                                        </div>
                                        
                                        <div class="row g-2 text-small mb-3">
                                            <div class="col-12">
                                                <div class="d-flex align-items-center text-muted">
                                                    <i class="bi bi-tag me-2"></i>
                                                    <span class="badge bg-info-subtle text-info me-2">
                                                        <?= esc($review['nama_kategori'] ?? '-') ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <div class="text-muted">
                                                    <i class="bi bi-calendar-check me-2"></i><?= $formatDateTime($review['tanggal_review'] ?? null) ?>
                                                </div>
                                            </div>
                                        </div>

                                        <?php if (!empty($review['komentar'])): ?>
                                            <div class="pt-2 border-top">
                                                <div class="small text-muted">
                                                    <i class="bi bi-chat-left-text me-1"></i>
                                                    <strong>Komentar:</strong> <?= esc(substr($review['komentar'], 0, 100)) ?>
                                                    <?= strlen($review['komentar']) > 100 ? '...' : '' ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- NO RESULTS MESSAGE -->
                <div id="noResults" class="text-center py-5 d-none">
                    <div class="empty-state">
                        <div class="empty-icon">
                            <i class="bi bi-search"></i>
                        </div>
                        <div class="empty-title">Tidak ada hasil ditemukan</div>
                        <div class="empty-subtitle">Coba ubah kata kunci atau filter pencarian Anda</div>
                    </div>
                </div>

            <?php else: ?>
                <!-- EMPTY STATE -->
                <div class="card shadow-sm border-0">
                    <div class="card-body">
                        <div class="empty-state text-center py-5">
                            <div class="empty-icon">
                                <i class="bi bi-journal-x"></i>
                            </div>
                            <div class="empty-title">Belum Ada Riwayat Review</div>
                            <div class="empty-subtitle mb-4">Anda belum menyelesaikan review apapun.</div>
                            <a href="<?= site_url('reviewer/abstrak') ?>" class="btn btn-primary">
                                <i class="bi bi-journal-text me-1"></i>Lihat Tugas Review
                            </a>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

        </div>
    </main>
</div>

<?= $this->include('partials/footer') ?>

<style>
:root {
    --primary-color: #2563eb;
    --success-color: #10b981;
    --warning-color: #f59e0b;
    --danger-color: #ef4444;
    --info-color: #06b6d4;
    --secondary-color: #6b7280;
}

body {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

/* Header Section - Same as Dashboard */
.header-section {
    background: linear-gradient(135deg, var(--primary-color) 0%, #1e40af 100%);
    color: #fff;
    padding: 24px;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(37, 99, 235, 0.15);
}

.welcome-text {
    color: #fff;
    font-weight: 700;
    font-size: 1.75rem;
    margin: 0;
}

/* Cards - Same as Dashboard */
.card {
    background: #fff;
    border: none;
    border-radius: 12px;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}

.card:hover {
    transform: translateY(-2px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.1);
}

.card-header {
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    border-radius: 12px 12px 0 0 !important;
    padding: 16px 20px;
}

.card-title {
    color: #1f2937;
    font-weight: 600;
}

/* Form Controls */
.form-control, .form-select {
    border: 2px solid #e2e8f0;
    border-radius: 8px;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.form-control:focus, .form-select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.1);
}

.input-group-text {
    border-radius: 8px 0 0 8px;
}

/* Table Styling */
.table {
    border-radius: 0 0 12px 12px;
    overflow: hidden;
}

.table thead th {
    background: #f8fafc;
    border-bottom: 1px solid #e2e8f0;
    font-weight: 600;
    color: #374151;
    padding: 16px;
}

.table tbody tr {
    transition: background-color 0.15s ease;
    border-bottom: 1px solid #f1f5f9;
}

.table tbody tr:hover {
    background-color: #f8fafc;
}

.table tbody td {
    padding: 16px;
    vertical-align: middle;
}

/* User Avatar */
.user-avatar {
    width: 36px;
    height: 36px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
    flex-shrink: 0;
}

.user-avatar-sm {
    width: 28px;
    height: 28px;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    flex-shrink: 0;
}

/* Badges - Improved contrast */
.badge {
    border-radius: 6px;
    font-weight: 500;
    font-size: 0.75rem;
}

.bg-primary-subtle {
    background-color: rgba(37, 99, 235, 0.1) !important;
}

.text-primary {
    color: var(--primary-color) !important;
}

.bg-info-subtle {
    background-color: rgba(6, 182, 212, 0.1) !important;
}

.text-info {
    color: var(--info-color) !important;
}

.bg-success {
    background-color: var(--success-color) !important;
}

.bg-warning {
    background-color: var(--warning-color) !important;
}

.bg-danger {
    background-color: var(--danger-color) !important;
}

.bg-secondary {
    background-color: var(--secondary-color) !important;
}

/* Review Cards */
.review-card {
    transition: all 0.2s ease;
    border-left: 4px solid transparent;
}

.review-card:hover {
    transform: translateY(-1px);
    border-left-color: var(--primary-color);
}

/* Empty State - Same as Dashboard */
.empty-state {
    padding: 3rem 2rem;
    color: #6b7280;
}

.empty-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}

.empty-title {
    font-size: 1.125rem;
    font-weight: 600;
    color: #4b5563;
    margin-bottom: 0.5rem;
}

.empty-subtitle {
    font-size: 0.875rem;
    color: #9ca3af;
}

/* Buttons */
.btn {
    border-radius: 8px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.btn:hover {
    transform: translateY(-1px);
}

.btn-primary {
    background-color: var(--primary-color);
    border-color: var(--primary-color);
}

.btn-outline-secondary {
    border-color: #d1d5db;
    color: #6b7280;
}

.btn-outline-secondary:hover {
    background-color: #f9fafb;
    border-color: #9ca3af;
    color: #374151;
}

/* Text utilities */
.text-small {
    font-size: 0.875rem;
}

/* Responsive improvements */
@media (max-width: 768px) {
    .header-section {
        padding: 16px;
    }
    
    .welcome-text {
        font-size: 1.5rem;
    }
    
    .user-avatar {
        width: 32px;
        height: 32px;
    }
    
    .user-avatar-sm {
        width: 24px;
        height: 24px;
    }
}

/* Animation */
@keyframes fadeIn {
    from { 
        opacity: 0; 
        transform: translateY(10px); 
    }
    to { 
        opacity: 1; 
        transform: translateY(0); 
    }
}

.review-row, .review-card {
    animation: fadeIn 0.3s ease-out;
}

/* Loading states */
.table tbody tr.loading {
    opacity: 0.6;
    pointer-events: none;
}

.review-card.loading {
    opacity: 0.6;
    pointer-events: none;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const resetFilter = document.getElementById('resetFilter');
    const noResults = document.getElementById('noResults');
    
    // Get all review items
    const tableRows = Array.from(document.querySelectorAll('.review-row'));
    const mobileCards = Array.from(document.querySelectorAll('.review-card'));
    
    // Filter function
    function applyFilters() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        const statusValue = statusFilter.value.toLowerCase().trim();
        
        let visibleCount = 0;
        
        // Filter table rows
        tableRows.forEach((row, index) => {
            const searchText = row.dataset.search || '';
            const status = row.dataset.status || '';
            
            const matchesSearch = !searchTerm || searchText.includes(searchTerm);
            const matchesStatus = !statusValue || status === statusValue;
            const isVisible = matchesSearch && matchesStatus;
            
            row.style.display = isVisible ? '' : 'none';
            if (isVisible) {
                visibleCount++;
                // Add staggered animation
                row.style.animationDelay = `${index * 0.05}s`;
            }
        });
        
        // Filter mobile cards
        mobileCards.forEach((card, index) => {
            const searchText = card.dataset.search || '';
            const status = card.dataset.status || '';
            
            const matchesSearch = !searchTerm || searchText.includes(searchTerm);
            const matchesStatus = !statusValue || status === statusValue;
            const isVisible = matchesSearch && matchesStatus;
            
            card.closest('.col-12').style.display = isVisible ? '' : 'none';
            if (isVisible && tableRows.length === 0) {
                visibleCount++;
                // Add staggered animation
                card.style.animationDelay = `${index * 0.05}s`;
            }
        });
        
        // Show/hide no results message
        if (noResults) {
            noResults.classList.toggle('d-none', visibleCount > 0);
        }
        
        // Update URL params for state persistence
        updateURL(searchTerm, statusValue);
    }
    
    // Update URL function
    function updateURL(searchTerm, statusValue) {
        const url = new URL(window.location);
        if (searchTerm) {
            url.searchParams.set('search', searchTerm);
        } else {
            url.searchParams.delete('search');
        }
        if (statusValue) {
            url.searchParams.set('status', statusValue);
        } else {
            url.searchParams.delete('status');
        }
        history.replaceState(null, '', url);
    }
    
    // Debounce function
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    // Event listeners
    if (searchInput) {
        searchInput.addEventListener('input', debounce(applyFilters, 300));
        
        // Add loading state while searching
        searchInput.addEventListener('input', function() {
            const isSearching = this.value.length > 0;
            document.body.classList.toggle('searching', isSearching);
        });
    }
    
    if (statusFilter) {
        statusFilter.addEventListener('change', applyFilters);
    }
    
    if (resetFilter) {
        resetFilter.addEventListener('click', function() {
            if (searchInput) searchInput.value = '';
            if (statusFilter) statusFilter.value = '';
            applyFilters();
            
            // Focus back to search input
            if (searchInput) {
                searchInput.focus();
            }
        });
    }
    
    // Initialize filters from URL params
    function initializeFromURL() {
        const url = new URL(window.location);
        const searchParam = url.searchParams.get('search');
        const statusParam = url.searchParams.get('status');
        
        if (searchParam && searchInput) {
            searchInput.value = searchParam;
        }
        if (statusParam && statusFilter) {
            statusFilter.value = statusParam;
        }
        
        if (searchParam || statusParam) {
            applyFilters();
        }
    }
    
    // Initialize
    initializeFromURL();
    
    // Add smooth animations for initial load
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry, index) => {
            if (entry.isIntersecting) {
                entry.target.style.animationDelay = `${index * 0.1}s`;
                entry.target.classList.add('animate-in');
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1
    });
    
    // Observe all review items
    [...tableRows, ...mobileCards].forEach(item => {
        observer.observe(item);
    });
    
    // Keyboard shortcuts
    document.addEventListener('keydown', function(e) {
        // Ctrl/Cmd + K to focus search
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            if (searchInput) {
                searchInput.focus();
                searchInput.select();
            }
        }
        
        // Escape to clear search
        if (e.key === 'Escape' && searchInput && searchInput === document.activeElement) {
            searchInput.value = '';
            applyFilters();
        }
    });
    
    // Add tooltips for better UX
    const badges = document.querySelectorAll('.badge');
    badges.forEach(badge => {
        badge.setAttribute('title', badge.textContent);
    });
});
</script>