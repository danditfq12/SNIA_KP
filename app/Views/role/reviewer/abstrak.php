<?php
// Default variables
$title = 'Daftar Abstrak';
$abstrak = $abstrak ?? [];
$byEvent = $byEvent ?? [];
$eventOptions = $eventOptions ?? [];

// Helper functions
$formatDate = fn($date) => $date ? date('d M Y', strtotime($date)) : '-';
$getStatusBadge = fn($status) => match(strtolower($status)) {
    'diterima' => 'success',
    'ditolak' => 'danger',
    'revisi' => 'warning',
    default => 'secondary'
};
?>

<?= $this->include('partials/header') ?>
<?= $this->include('partials/sidebar_reviewer') ?>

<div id="content">
    <main class="flex-fill" style="padding-top: 70px;">
        <div class="container-fluid p-3 p-md-4">

            <!-- HEADER SECTION (sama style dengan dashboard) -->
            <div class="header-section header-blue d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
                <div>
                    <h3 class="welcome-text mb-1">
                        <i class="bi bi-journal-text me-2"></i>Daftar Abstrak
                    </h3>
                    <div class="text-white-50">Kelola tugas review abstrak yang ditugaskan kepada Anda</div>
                </div>
                
                <!-- View Toggle -->
                <div class="btn-group" role="group" aria-label="View switch">
                    <button class="btn btn-light btn-sm active" id="viewAllBtn" type="button">
                        <i class="bi bi-table me-1"></i>Semua
                    </button>
                    <button class="btn btn-outline-light btn-sm" id="viewEventBtn" type="button">
                        <i class="bi bi-collection me-1"></i>Per Event
                    </button>
                </div>
            </div>

            <!-- Filter Section -->
            <div class="card shadow-sm border-0 mb-4">
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12 col-md-4">
                            <label for="searchInput" class="form-label small fw-medium">Pencarian</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0">
                                    <i class="bi bi-search text-muted"></i>
                                </span>
                                <input 
                                    type="text" 
                                    id="searchInput" 
                                    class="form-control border-start-0" 
                                    placeholder="Cari judul atau penulis..."
                                >
                            </div>
                        </div>
                        <div class="col-6 col-md-4">
                            <label for="filterEvent" class="form-label small fw-medium">Event</label>
                            <select id="filterEvent" class="form-select">
                                <option value="">Semua Event</option>
                                <?php foreach ($eventOptions as $eventId => $eventTitle): ?>
                                    <option value="<?= $eventId ?>"><?= esc($eventTitle) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-6 col-md-4">
                            <label for="filterStatus" class="form-label small fw-medium">Status</label>
                            <select id="filterStatus" class="form-select">
                                <option value="">Semua Status</option>
                                <option value="pending">Pending</option>
                                <option value="diterima">Diterima</option>
                                <option value="ditolak">Ditolak</option>
                                <option value="revisi">Revisi</option>
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- View: Table (All Items) -->
            <div id="viewAll" class="view-section">
                <div class="card shadow-sm border-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0" id="abstrakTable">
                            <thead class="table-light">
                                <tr>
                                    <th style="width: 35%;">Abstrak</th>
                                    <th style="width: 20%;">Event</th>
                                    <th style="width: 15%;">Penulis</th>
                                    <th style="width: 15%;">Kategori</th>
                                    <th style="width: 10%;">Status</th>
                                    <th style="width: 5%;">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($abstrak)): ?>
                                    <?php foreach ($abstrak as $item): ?>
                                        <?php
                                            $status = strtolower($item['status'] ?? '');
                                            $badgeClass = $getStatusBadge($status);
                                            $searchText = strtolower(($item['judul'] ?? '') . ' ' . ($item['nama_lengkap'] ?? ''));
                                        ?>
                                        <tr 
                                            data-event-id="<?= $item['event_id'] ?? 0 ?>"
                                            data-status="<?= esc($status) ?>"
                                            data-search="<?= esc($searchText) ?>"
                                            class="table-row"
                                        >
                                            <td>
                                                <div class="fw-semibold text-dark mb-1">
                                                    <?= esc($item['judul'] ?? 'Untitled') ?>
                                                </div>
                                                <small class="text-muted">
                                                    Upload: <?= $formatDate($item['tanggal_upload'] ?? null) ?>
                                                </small>
                                            </td>
                                            <td>
                                                <span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25">
                                                    <i class="bi bi-calendar-event me-1"></i>
                                                    <?= esc($item['event_title'] ?? 'Event') ?>
                                                </span>
                                            </td>
                                            <td class="text-dark">
                                                <?= esc($item['nama_lengkap'] ?? '-') ?>
                                            </td>
                                            <td class="text-muted">
                                                <?= esc($item['nama_kategori'] ?? '-') ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?= $badgeClass ?>">
                                                    <?= ucfirst($status ?: 'pending') ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a 
                                                    href="<?= site_url('reviewer/abstrak/' . ($item['id_abstrak'] ?? 0)) ?>" 
                                                    class="btn btn-sm btn-primary"
                                                    title="Lihat detail dan review"
                                                >
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <div class="text-muted">
                                                <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                                                <div class="fw-medium">Belum ada abstrak yang ditugaskan</div>
                                                <small>Tugas review akan muncul di sini setelah admin menugaskannya</small>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- View: Grouped by Event -->
            <div id="viewEvent" class="view-section d-none">
                <?php if (!empty($byEvent)): ?>
                    <div class="accordion" id="eventAccordion">
                        <?php $index = 0; foreach ($byEvent as $eventData): $index++; ?>
                            <div class="accordion-item border-0 shadow-sm mb-3 rounded-3 overflow-hidden">
                                <h2 class="accordion-header" id="heading<?= $index ?>">
                                    <button 
                                        class="accordion-button <?= $index > 1 ? 'collapsed' : '' ?> bg-light" 
                                        type="button"
                                        data-bs-toggle="collapse" 
                                        data-bs-target="#collapse<?= $index ?>"
                                        aria-expanded="<?= $index === 1 ? 'true' : 'false' ?>"
                                        aria-controls="collapse<?= $index ?>"
                                    >
                                        <div class="w-100 d-flex justify-content-between align-items-center">
                                            <div>
                                                <div class="fw-semibold d-flex align-items-center gap-2">
                                                    <i class="bi bi-calendar3 text-primary"></i>
                                                    <?= esc($eventData['event_title'] ?? 'Event') ?>
                                                </div>
                                                <small class="text-muted">
                                                    <?= $formatDate($eventData['event_date'] ?? null) ?>
                                                    <?= $eventData['event_time'] ? ' • ' . esc($eventData['event_time']) : '' ?>
                                                </small>
                                            </div>
                                            
                                            <div class="d-flex flex-wrap gap-1 me-3">
                                                <span class="badge bg-secondary bg-opacity-10 text-secondary border">
                                                    Total: <?= count($eventData['items'] ?? []) ?>
                                                </span>
                                                <?php if (($eventData['summary']['pending'] ?? 0) > 0): ?>
                                                    <span class="badge bg-secondary">
                                                        Pending: <?= $eventData['summary']['pending'] ?>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if (($eventData['summary']['revisi'] ?? 0) > 0): ?>
                                                    <span class="badge bg-warning">
                                                        Revisi: <?= $eventData['summary']['revisi'] ?>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if (($eventData['summary']['diterima'] ?? 0) > 0): ?>
                                                    <span class="badge bg-success">
                                                        Diterima: <?= $eventData['summary']['diterima'] ?>
                                                    </span>
                                                <?php endif; ?>
                                                <?php if (($eventData['summary']['ditolak'] ?? 0) > 0): ?>
                                                    <span class="badge bg-danger">
                                                        Ditolak: <?= $eventData['summary']['ditolak'] ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </button>
                                </h2>
                                
                                <div 
                                    id="collapse<?= $index ?>" 
                                    class="accordion-collapse collapse <?= $index === 1 ? 'show' : '' ?>"
                                    aria-labelledby="heading<?= $index ?>"
                                    data-bs-parent="#eventAccordion"
                                >
                                    <div class="accordion-body">
                                        <?php if (!empty($eventData['items'])): ?>
                                            <div class="vstack gap-2">
                                                <?php foreach ($eventData['items'] as $item): ?>
                                                    <?php
                                                        $status = strtolower($item['status'] ?? '');
                                                        $badgeClass = $getStatusBadge($status);
                                                    ?>
                                                    <div class="notice">
                                                        <div class="d-flex align-items-start gap-2">
                                                            <i class="bi bi-file-earmark-text text-primary mt-1"></i>
                                                            <div class="flex-fill">
                                                                <div class="title">
                                                                    <?= esc($item['judul'] ?? 'Untitled') ?>
                                                                </div>
                                                                <div class="meta">
                                                                    oleh <?= esc($item['nama_lengkap'] ?? '-') ?> • 
                                                                    <?= esc($item['nama_kategori'] ?? '-') ?> • 
                                                                    <?= $formatDate($item['tanggal_upload'] ?? null) ?>
                                                                </div>
                                                            </div>
                                                            <span class="badge bg-<?= $badgeClass ?>"><?= ucfirst($status ?: 'pending') ?></span>
                                                            <a 
                                                                href="<?= site_url('reviewer/abstrak/' . ($item['id_abstrak'] ?? 0)) ?>"
                                                                class="btn btn-sm btn-outline-primary"
                                                            >
                                                                <i class="bi bi-eye me-1"></i>Detail
                                                            </a>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            <div class="text-center py-4 text-muted">
                                                <i class="bi bi-journal-x fs-2 d-block mb-2"></i>
                                                <div>Tidak ada abstrak dalam event ini</div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="card border-0 shadow-sm">
                        <div class="card-body text-center py-5">
                            <i class="bi bi-journal-x fs-1 text-muted d-block mb-3"></i>
                            <h5 class="text-muted">Belum ada abstrak yang ditugaskan</h5>
                            <p class="text-muted mb-0">
                                Tugas review akan muncul di sini setelah admin menugaskannya kepada Anda
                            </p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

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
}

body {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
}

.header-section.header-blue {
    background: linear-gradient(135deg, var(--primary-color) 0%, #1e40af 100%);
    color: #fff;
    padding: 28px 24px;
    border-radius: 16px;
    box-shadow: 0 8px 28px rgba(0, 0, 0, .12);
}

.header-section .welcome-text {
    color: #fff;
    font-weight: 800;
    font-size: 2rem;
}

.view-section {
    transition: opacity 0.3s ease;
}

.table-row {
    transition: all 0.2s ease;
}

.table-row:hover {
    background-color: #f8fafc;
    transform: translateX(2px);
}

.accordion-button {
    border-radius: 12px !important;
    border: none;
    box-shadow: none;
}

.accordion-button:not(.collapsed) {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    color: var(--primary-color);
}

.list-group-item {
    transition: all 0.2s ease;
}

.list-group-item:hover {
    background-color: #f8fafc;
}

.notice {
    border: 1px solid #eef2f6;
    border-radius: 12px;
    padding: 12px;
    background: #fff;
    transition: .15s ease;
}

.notice:hover {
    box-shadow: 0 8px 18px rgba(0, 0, 0, .06);
}

.notice .title {
    font-weight: 600;
}

.notice .meta {
    font-size: .85rem;
    color: #6c757d;
}

.badge {
    font-weight: 500;
}

.input-group-text.bg-light {
    background-color: #f8f9fa !important;
}

.form-control:focus,
.form-select:focus {
    border-color: var(--primary-color);
    box-shadow: 0 0 0 0.25rem rgba(37, 99, 235, 0.25);
}

.btn-outline-primary:hover {
    transform: translateY(-1px);
}

.card {
    border-radius: 12px;
    border: 1px solid #e2e8f0;
    box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
}

.table th {
    font-weight: 600;
    color: #374151;
    border-bottom: 2px solid #e2e8f0;
}

/* jarak aman di bawah header global */
#content main > .container-fluid {
    margin-top: .25rem;
}

@media (max-width: 768px) {
    .header-section {
        text-align: center;
        padding: 20px 16px;
    }
    
    .header-section .welcome-text {
        font-size: 1.5rem;
    }
    
    .header-section .btn-group {
        margin-top: 1rem;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const viewAllBtn = document.getElementById('viewAllBtn');
    const viewEventBtn = document.getElementById('viewEventBtn');
    const viewAll = document.getElementById('viewAll');
    const viewEvent = document.getElementById('viewEvent');
    
    const searchInput = document.getElementById('searchInput');
    const filterEvent = document.getElementById('filterEvent');
    const filterStatus = document.getElementById('filterStatus');
    const tableRows = document.querySelectorAll('#abstrakTable .table-row');

    // Local storage key for view preference
    const VIEW_STORAGE_KEY = 'reviewer_abstrak_view';

    // View switching functionality
    function switchView(viewType) {
        if (viewType === 'event') {
            viewEvent.classList.remove('d-none');
            viewAll.classList.add('d-none');
            viewEventBtn.classList.add('active');
            viewAllBtn.classList.remove('active');
            localStorage.setItem(VIEW_STORAGE_KEY, 'event');
        } else {
            viewAll.classList.remove('d-none');
            viewEvent.classList.add('d-none');
            viewAllBtn.classList.add('active');
            viewEventBtn.classList.remove('active');
            localStorage.setItem(VIEW_STORAGE_KEY, 'all');
        }
    }

    // Event listeners for view switching
    viewAllBtn?.addEventListener('click', () => switchView('all'));
    viewEventBtn?.addEventListener('click', () => switchView('event'));

    // Initialize view from localStorage
    const savedView = localStorage.getItem(VIEW_STORAGE_KEY) || 'all';
    switchView(savedView);

    // Filter functionality
    function applyFilters() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        const eventFilter = filterEvent.value;
        const statusFilter = filterStatus.value.toLowerCase();

        tableRows.forEach(row => {
            const searchText = row.getAttribute('data-search') || '';
            const eventId = row.getAttribute('data-event-id') || '';
            const status = row.getAttribute('data-status') || '';

            const matchesSearch = !searchTerm || searchText.includes(searchTerm);
            const matchesEvent = !eventFilter || eventFilter === eventId;
            const matchesStatus = !statusFilter || statusFilter === status;

            const shouldShow = matchesSearch && matchesEvent && matchesStatus;
            row.style.display = shouldShow ? '' : 'none';
        });

        // Update empty state
        updateEmptyState();
    }

    function updateEmptyState() {
        const visibleRows = Array.from(tableRows).filter(row => 
            row.style.display !== 'none'
        );
        
        const tbody = document.querySelector('#abstrakTable tbody');
        let emptyRow = tbody.querySelector('.empty-state-row');
        
        if (visibleRows.length === 0 && tableRows.length > 0) {
            if (!emptyRow) {
                emptyRow = document.createElement('tr');
                emptyRow.className = 'empty-state-row';
                emptyRow.innerHTML = `
                    <td colspan="6" class="text-center py-5">
                        <div class="text-muted">
                            <i class="bi bi-search fs-1 d-block mb-2"></i>
                            <div class="fw-medium">Tidak ada hasil yang cocok</div>
                            <small>Coba ubah filter pencarian Anda</small>
                        </div>
                    </td>
                `;
                tbody.appendChild(emptyRow);
            }
        } else if (emptyRow) {
            emptyRow.remove();
        }
    }

    // Event listeners for filters
    searchInput?.addEventListener('input', applyFilters);
    filterEvent?.addEventListener('change', applyFilters);
    filterStatus?.addEventListener('change', applyFilters);

    // Initialize filters
    applyFilters();

    // Add loading state for buttons
    document.querySelectorAll('a[href*="/reviewer/abstrak/"]').forEach(link => {
        link.addEventListener('click', function() {
            const icon = this.querySelector('i');
            if (icon) {
                icon.className = 'bi bi-hourglass-split';
            }
            this.classList.add('disabled');
        });
    });
});
</script>