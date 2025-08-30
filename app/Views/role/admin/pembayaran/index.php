<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verifikasi Pembayaran - SNIA Admin</title>
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
        }

        body {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            min-height: 100vh;
        }

        .sidebar {
            background: linear-gradient(180deg, var(--primary-color) 0%, #1e40af 100%);
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

        .content-card {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            border: 1px solid rgba(255,255,255,0.2);
            overflow: hidden;
        }

        .content-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--info-color) 100%);
            color: white;
            padding: 24px;
        }

        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
        }

        .stat-card.pending::before { background: var(--warning-color); }
        .stat-card.verified::before { background: var(--success-color); }
        .stat-card.rejected::before { background: var(--danger-color); }
        .stat-card.revenue::before { background: var(--info-color); }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.12);
        }

        .payment-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            transition: all 0.3s ease;
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }

        .payment-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(0,0,0,0.15);
        }

        .payment-header {
            padding: 16px 20px;
            border-bottom: 1px solid #e2e8f0;
            display: flex;
            justify-content: between;
            align-items: center;
        }

        .payment-body {
            padding: 20px;
        }

        .payment-footer {
            padding: 16px 20px;
            background: #f8fafc;
            border-top: 1px solid #e2e8f0;
        }

        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .btn-custom {
            border-radius: 8px;
            padding: 8px 16px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-custom:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.15);
        }

        .user-info {
            display: flex;
            align-items: center;
        }

        .user-avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-color), var(--info-color));
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
            margin-right: 12px;
        }

        .amount-display {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--success-color);
        }

        .modal-content {
            border-radius: 16px;
            border: none;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-color), var(--info-color));
            color: white;
            border-radius: 16px 16px 0 0;
            border-bottom: none;
        }

        .bukti-preview {
            max-width: 100%;
            max-height: 400px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.1);
        }

        .filter-card {
            background: white;
            border-radius: 12px;
            padding: 20px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            margin-bottom: 20px;
        }

        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #6b7280;
        }

        .empty-state i {
            font-size: 4rem;
            margin-bottom: 20px;
            opacity: 0.5;
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
                            <i class="fas fa-cogs me-2"></i>
                            SNIA Admin
                        </h4>
                        <small class="text-white-50">Sistem Manajemen</small>
                    </div>
                    
                    <nav class="nav flex-column px-3">
                        <a class="nav-link" href="<?= site_url('admin/dashboard') ?>">
                            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                        </a>
                        <a class="nav-link" href="<?= site_url('admin/users') ?>">
                            <i class="fas fa-users me-2"></i> Manajemen User
                        </a>
                        <a class="nav-link" href="<?= site_url('admin/event') ?>">
                            <i class="fas fa-calendar me-2"></i> Kelola Event
                        </a>
                        <a class="nav-link" href="<?= site_url('admin/abstrak') ?>">
                            <i class="fas fa-file-alt me-2"></i> Manajemen Abstrak
                        </a>
                        <a class="nav-link" href="<?= site_url('admin/reviewer') ?>">
                            <i class="fas fa-user-check me-2"></i> Kelola Reviewer
                        </a>
                        <a class="nav-link active" href="<?= site_url('admin/pembayaran') ?>">
                            <i class="fas fa-credit-card me-2"></i> Verifikasi Pembayaran
                        </a>
                        <a class="nav-link" href="<?= site_url('admin/voucher') ?>">
                            <i class="fas fa-ticket-alt me-2"></i> Kelola Voucher
                        </a>
                        <a class="nav-link" href="<?= site_url('admin/dokumen') ?>">
                            <i class="fas fa-folder-open me-2"></i> Dokumen
                        </a>
                        <a class="nav-link" href="<?= site_url('admin/laporan') ?>">
                            <i class="fas fa-chart-line me-2"></i> Laporan
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
                    <!-- Header -->
                    <div class="content-card mb-4">
                        <div class="content-header">
                            <div class="row align-items-center">
                                <div class="col">
                                    <h2 class="mb-2">
                                        <i class="fas fa-credit-card me-3"></i>Verifikasi Pembayaran
                                    </h2>
                                    <p class="mb-0 opacity-75">Kelola dan verifikasi pembayaran dari peserta</p>
                                </div>
                                <div class="col-auto">
                                    <button class="btn btn-light btn-custom me-2" onclick="exportData()">
                                        <i class="fas fa-download me-2"></i>Export Data
                                    </button>
                                    <button class="btn btn-outline-light btn-custom" onclick="refreshData()">
                                        <i class="fas fa-sync-alt me-2"></i>Refresh
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Statistics Cards -->
                    <div class="row g-4 mb-4">
                        <div class="col-md-3">
                            <div class="stat-card pending">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="fas fa-clock fa-2x text-warning"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0"><?= $pembayaran_pending ?? 0 ?></h3>
                                        <small class="text-muted">Pending</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card verified">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="fas fa-check-circle fa-2x text-success"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0"><?= $pembayaran_verified ?? 0 ?></h3>
                                        <small class="text-muted">Terverifikasi</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card rejected">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="fas fa-times-circle fa-2x text-danger"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0"><?= $pembayaran_rejected ?? 0 ?></h3>
                                        <small class="text-muted">Ditolak</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="stat-card revenue">
                                <div class="d-flex align-items-center">
                                    <div class="me-3">
                                        <i class="fas fa-money-bill-wave fa-2x text-info"></i>
                                    </div>
                                    <div>
                                        <h3 class="mb-0">Rp <?= number_format($total_revenue ?? 0, 0, ',', '.') ?></h3>
                                        <small class="text-muted">Total Revenue</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Filter -->
                    <div class="filter-card">
                        <div class="row align-items-center">
                            <div class="col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                                    <input type="text" class="form-control" id="searchInput" placeholder="Cari pembayaran...">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" id="statusFilter">
                                    <option value="">Semua Status</option>
                                    <option value="pending">Pending</option>
                                    <option value="verified">Terverifikasi</option>
                                    <option value="rejected">Ditolak</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" id="roleFilter">
                                    <option value="">Semua Role</option>
                                    <option value="presenter">Presenter</option>
                                    <option value="audience">Audience</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button class="btn btn-outline-secondary w-100" onclick="resetFilter()">
                                    <i class="fas fa-undo"></i> Reset
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Cards -->
                    <div class="row g-4" id="paymentContainer">
                        <?php if (!empty($pembayarans)): ?>
                            <?php foreach ($pembayarans as $pembayaran): ?>
                                <div class="col-lg-6" data-status="<?= $pembayaran['status'] ?>" data-role="<?= $pembayaran['role'] ?>">
                                    <div class="payment-card">
                                        <div class="payment-header">
                                            <div class="user-info">
                                                <div class="user-avatar">
                                                    <?= strtoupper(substr($pembayaran['nama_lengkap'], 0, 1)) ?>
                                                </div>
                                                <div>
                                                    <h6 class="mb-0"><?= esc($pembayaran['nama_lengkap']) ?></h6>
                                                    <small class="text-muted"><?= esc($pembayaran['email']) ?></small>
                                                </div>
                                            </div>
                                            <div>
                                                <?php
                                                $statusClass = '';
                                                $statusText = '';
                                                switch($pembayaran['status']) {
                                                    case 'pending':
                                                        $statusClass = 'bg-warning text-dark';
                                                        $statusText = 'Pending';
                                                        break;
                                                    case 'verified':
                                                        $statusClass = 'bg-success';
                                                        $statusText = 'Terverifikasi';
                                                        break;
                                                    case 'rejected':
                                                        $statusClass = 'bg-danger';
                                                        $statusText = 'Ditolak';
                                                        break;
                                                }
                                                ?>
                                                <span class="status-badge <?= $statusClass ?>"><?= $statusText ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="payment-body">
                                            <div class="row">
                                                <div class="col-6">
                                                    <label class="text-muted small">Metode Pembayaran</label>
                                                    <div class="fw-semibold"><?= esc($pembayaran['metode']) ?></div>
                                                </div>
                                                <div class="col-6">
                                                    <label class="text-muted small">Jumlah</label>
                                                    <div class="amount-display">Rp <?= number_format($pembayaran['jumlah'], 0, ',', '.') ?></div>
                                                </div>
                                            </div>
                                            
                                            <div class="row mt-3">
                                                <div class="col-6">
                                                    <label class="text-muted small">Role</label>
                                                    <div>
                                                        <span class="badge <?= $pembayaran['role'] == 'presenter' ? 'bg-primary' : 'bg-secondary' ?>">
                                                            <?= ucfirst($pembayaran['role']) ?>
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="col-6">
                                                    <label class="text-muted small">Tanggal Bayar</label>
                                                    <div class="fw-semibold"><?= date('d/m/Y H:i', strtotime($pembayaran['tanggal_bayar'])) ?></div>
                                                </div>
                                            </div>

                                            <?php if ($pembayaran['voucher_info']): ?>
                                                <div class="mt-3 p-2 bg-light rounded">
                                                    <small class="text-muted">Voucher digunakan:</small>
                                                    <div class="fw-semibold text-success">
                                                        <?= esc($pembayaran['voucher_info']['kode_voucher']) ?>
                                                        (<?= $pembayaran['voucher_info']['tipe'] == 'percentage' ? $pembayaran['voucher_info']['nilai'] . '%' : 'Rp ' . number_format($pembayaran['voucher_info']['nilai'], 0, ',', '.') ?>)
                                                    </div>
                                                </div>
                                            <?php endif; ?>
                                        </div>

                                        <div class="payment-footer">
                                            <div class="d-flex justify-content-between">
                                                <button class="btn btn-outline-info btn-custom btn-sm" 
                                                        onclick="viewDetail(<?= $pembayaran['id_pembayaran'] ?>)">
                                                    <i class="fas fa-eye me-1"></i>Detail
                                                </button>
                                                
                                                <?php if ($pembayaran['bukti_bayar']): ?>
                                                    <button class="btn btn-outline-secondary btn-custom btn-sm" 
                                                            onclick="viewBukti('<?= base_url('uploads/pembayaran/' . $pembayaran['bukti_bayar']) ?>')">
                                                        <i class="fas fa-image me-1"></i>Bukti
                                                    </button>
                                                <?php endif; ?>
                                                
                                                <?php if ($pembayaran['status'] == 'pending'): ?>
                                                    <button class="btn btn-success btn-custom btn-sm" 
                                                            onclick="verifikasiPembayaran(<?= $pembayaran['id_pembayaran'] ?>, 'verified')">
                                                        <i class="fas fa-check me-1"></i>Verifikasi
                                                    </button>
                                                    <button class="btn btn-danger btn-custom btn-sm" 
                                                            onclick="verifikasiPembayaran(<?= $pembayaran['id_pembayaran'] ?>, 'rejected')">
                                                        <i class="fas fa-times me-1"></i>Tolak
                                                    </button>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <div class="empty-state">
                                    <i class="fas fa-credit-card"></i>
                                    <h4>Belum Ada Pembayaran</h4>
                                    <p>Belum ada pembayaran yang perlu diverifikasi saat ini.</p>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Bukti Pembayaran Modal -->
    <div class="modal fade" id="buktiModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-image me-2"></i>Bukti Pembayaran
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <img id="buktiImage" src="" class="bukti-preview" alt="Bukti Pembayaran">
                </div>
            </div>
        </div>
    </div>

    <!-- Verifikasi Modal -->
    <div class="modal fade" id="verifikasiModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="verifikasiTitle">
                        <i class="fas fa-check-circle me-2"></i>Verifikasi Pembayaran
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form id="verifikasiForm" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Keterangan</label>
                            <textarea class="form-control" name="keterangan" rows="3" 
                                    placeholder="Tambahkan keterangan verifikasi..."></textarea>
                        </div>
                        <input type="hidden" name="status" id="verifikasiStatus">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary" id="verifikasiSubmit">
                            <i class="fas fa-save me-2"></i>Proses
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        // Search and filter functionality
        document.getElementById('searchInput').addEventListener('keyup', filterPayments);
        document.getElementById('statusFilter').addEventListener('change', filterPayments);
        document.getElementById('roleFilter').addEventListener('change', filterPayments);

        function filterPayments() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const statusFilter = document.getElementById('statusFilter').value;
            const roleFilter = document.getElementById('roleFilter').value;
            const cards = document.querySelectorAll('#paymentContainer > div');

            cards.forEach(card => {
                const text = card.textContent.toLowerCase();
                const status = card.getAttribute('data-status');
                const role = card.getAttribute('data-role');

                const matchesSearch = text.includes(searchTerm);
                const matchesStatus = !statusFilter || status === statusFilter;
                const matchesRole = !roleFilter || role === roleFilter;

                card.style.display = matchesSearch && matchesStatus && matchesRole ? '' : 'none';
            });
        }

        function resetFilter() {
            document.getElementById('searchInput').value = '';
            document.getElementById('statusFilter').value = '';
            document.getElementById('roleFilter').value = '';
            filterPayments();
        }

        function viewBukti(imageUrl) {
            document.getElementById('buktiImage').src = imageUrl;
            new bootstrap.Modal(document.getElementById('buktiModal')).show();
        }

        function verifikasiPembayaran(id, status) {
            const isVerified = status === 'verified';
            const title = isVerified ? 'Verifikasi Pembayaran' : 'Tolak Pembayaran';
            const icon = isVerified ? 'fa-check-circle' : 'fa-times-circle';
            const btnClass = isVerified ? 'btn-success' : 'btn-danger';
            const btnText = isVerified ? 'Verifikasi' : 'Tolak';

            document.getElementById('verifikasiTitle').innerHTML = `<i class="fas ${icon} me-2"></i>${title}`;
            document.getElementById('verifikasiStatus').value = status;
            document.getElementById('verifikasiForm').action = `<?= site_url('admin/pembayaran/verifikasi') ?>/${id}`;
            document.getElementById('verifikasiSubmit').className = `btn ${btnClass}`;
            document.getElementById('verifikasiSubmit').innerHTML = `<i class="fas fa-save me-2"></i>${btnText}`;

            new bootstrap.Modal(document.getElementById('verifikasiModal')).show();
        }

        function refreshData() {
            location.reload();
        }

        function exportData() {
            window.location.href = '<?= site_url('admin/pembayaran/export') ?>';
        }

        // Show success/error messages
        document.addEventListener('DOMContentLoaded', function() {
            <?php if (session('success')): ?>
                Swal.fire({
                    icon: 'success',
                    title: 'Berhasil!',
                    text: '<?= session('success') ?>',
                    timer: 3000,
                    showConfirmButton: false
                });
            <?php endif; ?>

            <?php if (session('error')): ?>
                Swal.fire({
                    icon: 'error',
                    title: 'Error!',
                    text: '<?= session('error') ?>',
                });
            <?php endif; ?>

            // Animate cards on load
            const cards = document.querySelectorAll('.payment-card, .stat-card');
            cards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    card.style.transition = 'all 0.5s ease';
                    
                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 100);
                }, index * 50);
            });
        });
    </script>
</body>
</html>