<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Pembayaran - Presenter Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
                <a href="<?= site_url('auth/logout') ?>" class="btn btn-outline-light btn-sm ms-2">
                    <i class="fas fa-sign-out-alt me-1"></i>Logout
                </a>
            </div>
        </div>
    </nav>

    <div class="container py-4">
        <div class="row">
            <div class="col-md-8">
                <!-- Payment Form -->
                <div class="card shadow-sm">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-credit-card me-2"></i>Form Pembayaran Registrasi
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if ($event): ?>
                            <div class="alert alert-info">
                                <h6><i class="fas fa-calendar me-2"></i><?= $event['title'] ?></h6>
                                <p class="mb-1"><?= $event['description'] ?></p>
                                <div class="row">
                                    <div class="col-sm-6">
                                        <small>
                                            <i class="fas fa-calendar me-1"></i>
                                            <?= date('d/m/Y', strtotime($event['event_date'])) ?>
                                        </small>
                                    </div>
                                    <div class="col-sm-6">
                                        <small>
                                            <i class="fas fa-clock me-1"></i>
                                            <?= date('H:i', strtotime($event['event_time'])) ?>
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <form action="<?= site_url('presenter/pembayaran/store') ?>" method="post" enctype="multipart/form-data" id="paymentForm">
                                <input type="hidden" name="event_id" value="<?= $event['id'] ?>">
                                
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Metode Pembayaran</label>
                                            <select name="metode" class="form-select" required>
                                                <option value="">Pilih Metode</option>
                                                <option value="bank_transfer">Transfer Bank</option>
                                                <option value="e_wallet">E-Wallet</option>
                                                <option value="credit_card">Kartu Kredit</option>
                                            </select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="mb-3">
                                            <label class="form-label">Kode Voucher (Opsional)</label>
                                            <div class="input-group">
                                                <input type="text" name="kode_voucher" id="kodeVoucher" class="form-control" placeholder="Masukkan kode voucher">
                                                <button type="button" class="btn btn-outline-secondary" onclick="checkVoucher()">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </div>
                                            <div id="voucherInfo"></div>
                                        </div>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Jumlah Pembayaran</label>
                                    <div class="input-group">
                                        <span class="input-group-text">Rp</span>
                                        <input type="number" name="jumlah" id="jumlahBayar" class="form-control" 
                                               value="<?= $event['registration_fee'] ?>" readonly>
                                    </div>
                                    <div id="discountInfo" class="mt-2"></div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Bukti Pembayaran</label>
                                    <input type="file" name="bukti_bayar" class="form-control" accept=".jpg,.jpeg,.png,.pdf" required>
                                    <small class="text-muted">Format: JPG, PNG, PDF. Maksimal 2MB</small>
                                </div>

                                <div class="mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="terms" required>
                                        <label class="form-check-label" for="terms">
                                            Saya menyetujui syarat dan ketentuan yang berlaku
                                        </label>
                                    </div>
                                </div>

                                <button type="submit" class="btn btn-success">
                                    <i class="fas fa-paper-plane me-1"></i>Submit Pembayaran
                                </button>
                            </form>

                        <?php else: ?>
                            <div class="alert alert-warning">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Event untuk abstrak Anda tidak ditemukan. Silakan hubungi admin.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Payment Instructions -->
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-info text-white">
                        <h6 class="mb-0">
                            <i class="fas fa-info-circle me-2"></i>Informasi Pembayaran
                        </h6>
                    </div>
                    <div class="card-body">
                        <h6>Rekening Bank Transfer:</h6>
                        <div class="bg-light p-3 rounded mb-3">
                            <strong>Bank BCA</strong><br>
                            <strong>No. Rek:</strong> 1234567890<br>
                            <strong>A/n:</strong> SNIA Committee
                        </div>
                        
                        <h6>E-Wallet:</h6>
                        <div class="bg-light p-3 rounded mb-3">
                            <strong>GoPay/OVO/DANA:</strong><br>
                            <strong>No. HP:</strong> 081234567890<br>
                            <strong>A/n:</strong> SNIA Committee
                        </div>

                        <div class="alert alert-warning">
                            <small>
                                <i class="fas fa-exclamation-triangle me-1"></i>
                                Pastikan nominal transfer sesuai dengan yang tertera
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Payment Status -->
                <?php if (!empty($pembayaran)): ?>
                    <div class="card shadow-sm">
                        <div class="card-header bg-secondary text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-history me-2"></i>Riwayat Pembayaran
                            </h6>
                        </div>
                        <div class="card-body">
                            <?php foreach ($pembayaran as $bayar): ?>
                                <div class="border p-3 mb-2 rounded">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong>Rp <?= number_format($bayar['jumlah'], 0, ',', '.') ?></strong>
                                            <br>
                                            <small class="text-muted"><?= $bayar['metode'] ?></small>
                                            <br>
                                            <small class="text-muted"><?= date('d/m/Y H:i', strtotime($bayar['tanggal_bayar'])) ?></small>
                                        </div>
                                        <span class="badge bg-<?= $bayar['status'] == 'verified' ? 'success' : ($bayar['status'] == 'rejected' ? 'danger' : 'warning') ?>">
                                            <?= ucfirst($bayar['status']) ?>
                                        </span>
                                    </div>
                                    
                                    <?php if ($bayar['status'] == 'verified'): ?>
                                        <div class="mt-2">
                                            <small class="text-success">
                                                <i class="fas fa-check-circle me-1"></i>
                                                Pembayaran terverifikasi pada <?= $bayar['verified_at'] ? date('d/m/Y H:i', strtotime($bayar['verified_at'])) : '-' ?>
                                            </small>
                                        </div>
                                    <?php elseif ($bayar['status'] == 'rejected'): ?>
                                        <div class="mt-2">
                                            <small class="text-danger">
                                                <i class="fas fa-times-circle me-1"></i>
                                                Pembayaran ditolak
                                            </small>
                                            <?php if (!empty($bayar['keterangan'])): ?>
                                                <br>
                                                <small class="text-muted">Alasan: <?= $bayar['keterangan'] ?></small>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        let originalAmount = <?= $event['registration_fee'] ?? 0 ?>;
        let currentVoucher = null;

        function checkVoucher() {
            const kodeVoucher = document.getElementById('kodeVoucher').value;
            const voucherInfo = document.getElementById('voucherInfo');
            const discountInfo = document.getElementById('discountInfo');
            
            if (!kodeVoucher) {
                voucherInfo.innerHTML = '';
                discountInfo.innerHTML = '';
                document.getElementById('jumlahBayar').value = originalAmount;
                return;
            }

            fetch('<?= site_url('presenter/pembayaran/checkVoucher') ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: 'kode_voucher=' + encodeURIComponent(kodeVoucher)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    currentVoucher = data.voucher;
                    let discount = 0;
                    let newAmount = originalAmount;
                    
                    if (data.voucher.tipe === 'persentase') {
                        discount = originalAmount * data.voucher.nilai / 100;
                        newAmount = originalAmount - discount;
                    } else {
                        discount = Math.min(data.voucher.nilai, originalAmount);
                        newAmount = Math.max(0, originalAmount - discount);
                    }
                    
                    document.getElementById('jumlahBayar').value = newAmount;
                    
                    voucherInfo.innerHTML = '<small class="text-success"><i class="fas fa-check me-1"></i>Voucher valid!</small>';
                    discountInfo.innerHTML = `
                        <div class="alert alert-success">
                            <i class="fas fa-tag me-2"></i>
                            Diskon: Rp ${discount.toLocaleString('id-ID')}<br>
                            <strong>Total Bayar: Rp ${newAmount.toLocaleString('id-ID')}</strong>
                        </div>
                    `;
                } else {
                    currentVoucher = null;
                    document.getElementById('jumlahBayar').value = originalAmount;
                    voucherInfo.innerHTML = '<small class="text-danger"><i class="fas fa-times me-1"></i>' + data.message + '</small>';
                    discountInfo.innerHTML = '';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                voucherInfo.innerHTML = '<small class="text-danger"><i class="fas fa-times me-1"></i>Error checking voucher</small>';
            });
        }

        // Auto-refresh payment status every 30 seconds
        setInterval(function() {
            const statusElements = document.querySelectorAll('.badge');
            statusElements.forEach(function(element) {
                if (element.textContent.trim() === 'Pending') {
                    location.reload();
                }
            });
        }, 30000);
    </script>
</body>
</html>