<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detail Event - <?= esc($event['title']) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/3.9.1/chart.min.css" rel="stylesheet">
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
        }

        .detail-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--info-color) 100%);
            color: white;
            padding: 2rem 0;
            border-radius: 0 0 20px 20px;
            margin-bottom: 2rem;
        }

        .stat-card {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            border: 1px solid rgba(255,255,255,0.2);
            transition: all 0.3s ease;
            height: 100%;
        }

        .stat-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 40px rgba(0,0,0,0.15);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-bottom: 16px;
            font-size: 24px;
        }

        .stat-number {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 8px;
        }

        .participant-breakdown {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }

        .participant-type-card {
            background: #f8fafc;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 16px;
            border-left: 4px solid var(--primary-color);
        }

        .participant-type-card.presenter {
            border-left-color: var(--primary-color);
        }

        .participant-type-card.audience-online {
            border-left-color: var(--info-color);
        }

        .participant-type-card.audience-offline {
            border-left-color: var(--success-color);
        }

        .revenue-card {
            background: linear-gradient(135deg, var(--success-color), #059669);
            color: white;
            border-radius: 16px;
            padding: 24px;
            margin-bottom: 2rem;
        }

        .session-card {
            background: white;
            border-radius: 12px;
            border: 2px solid #e2e8f0;
            padding: 20px;
            margin-bottom: 16px;
            transition: all 0.3s ease;
        }

        .session-card:hover {
            border-color: var(--primary-color);
            box-shadow: 0 4px 20px rgba(37, 99, 235, 0.15);
        }

        .capacity-bar {
            height: 8px;
            background-color: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
            margin: 10px 0;
        }

        .capacity-fill {
            height: 100%;
            border-radius: 4px;
            transition: width 0.3s ease;
        }

        .table-responsive {
            background: white;
            border-radius: 16px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            padding: 24px;
            margin-bottom: 2rem;
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

        .chart-container {
            background: white;
            border-radius: 16px;
            padding: 24px;
            box-shadow: 0 8px 32px rgba(0,0,0,0.08);
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="detail-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <div class="d-flex align-items-center mb-2">
                        <a href="/admin/event" class="btn btn-light me-3">
                            <i class="fas fa-arrow-left"></i>
                        </a>
                        <div>
                            <h1 class="mb-1"><?= esc($event['title']) ?></h1>
                            <p class="mb-0 opacity-75">
                                <i class="fas fa-calendar me-2"></i>
                                <?= date('d F Y', strtotime($event['event_date'])) ?> • 
                                <?= date('H:i', strtotime($event['event_time'])) ?>
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    <div class="d-flex flex-wrap gap-2 justify-content-end">
                        <span class="badge bg-light text-dark fs-6">
                            <i class="fas fa-<?= $event['format'] == 'online' ? 'video' : ($event['format'] == 'offline' ? 'map-marker-alt' : 'globe') ?> me-1"></i>
                            <?= ucfirst($event['format']) ?>
                        </span>
                        <span class="badge <?= $event['is_active'] ? 'bg-success' : 'bg-secondary' ?> fs-6">
                            <?= $event['is_active'] ? 'Aktif' : 'Nonaktif' ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Statistics Overview -->
        <div class="row g-4 mb-4">
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon bg-primary bg-opacity-10 text-primary">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-number text-primary"><?= $stats['total_registrations'] ?? 0 ?></div>
                    <div class="text-muted">Total Registrasi</div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon bg-success bg-opacity-10 text-success">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <div class="stat-number text-success"><?= $stats['verified_registrations'] ?? 0 ?></div>
                    <div class="text-muted">Terverifikasi</div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon bg-warning bg-opacity-10 text-warning">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <div class="stat-number text-warning"><?= $stats['total_abstracts'] ?? 0 ?></div>
                    <div class="text-muted">Total Abstrak</div>
                </div>
            </div>
            
            <div class="col-lg-3 col-md-6">
                <div class="stat-card">
                    <div class="stat-icon bg-info bg-opacity-10 text-info">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <div class="stat-number text-info">Rp <?= number_format($stats['total_revenue'] ?? 0, 0, ',', '.') ?></div>
                    <div class="text-muted">Total Revenue</div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <!-- Participant Breakdown -->
                <div class="participant-breakdown">
                    <h4 class="mb-4">
                        <i class="fas fa-chart-pie me-2 text-primary"></i>
                        Breakdown Peserta by Type
                    </h4>
                    
                    <!-- Presenter -->
                    <div class="participant-type-card presenter">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <div class="stat-icon bg-primary bg-opacity-20 text-primary" style="width: 50px; height: 50px; font-size: 20px;">
                                        <i class="fas fa-microphone"></i>
                                    </div>
                                </div>
                                <div>
                                    <h5 class="mb-1">Presenter (Offline Only)</h5>
                                    <p class="mb-0 text-muted">Presentasi tatap muka</p>
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="h3 mb-0 text-primary"><?= $stats['presenter_registrations'] ?? 0 ?></div>
                                <small class="text-muted">peserta</small>
                            </div>
                        </div>
                    </div>

                    <!-- Audience Online -->
                    <?php if ($event['format'] != 'offline'): ?>
                    <div class="participant-type-card audience-online">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <div class="stat-icon bg-info bg-opacity-20 text-info" style="width: 50px; height: 50px; font-size: 20px;">
                                        <i class="fas fa-video"></i>
                                    </div>
                                </div>
                                <div>
                                    <h5 class="mb-1">Audience Online</h5>
                                    <p class="mb-0 text-muted">Partisipasi virtual</p>
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="h3 mb-0 text-info"><?= $stats['audience_online_registrations'] ?? 0 ?></div>
                                <small class="text-muted">peserta</small>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Audience Offline -->
                    <?php if ($event['format'] != 'online'): ?>
                    <div class="participant-type-card audience-offline">
                        <div class="d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                                <div class="me-3">
                                    <div class="stat-icon bg-success bg-opacity-20 text-success" style="width: 50px; height: 50px; font-size: 20px;">
                                        <i class="fas fa-map-marker-alt"></i>
                                    </div>
                                </div>
                                <div>
                                    <h5 class="mb-1">Audience Offline</h5>
                                    <p class="mb-0 text-muted">Partisipasi tatap muka</p>
                                </div>
                            </div>
                            <div class="text-end">
                                <div class="h3 mb-0 text-success"><?= $stats['audience_offline_registrations'] ?? 0 ?></div>
                                <small class="text-muted">peserta</small>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Revenue Breakdown -->
                <?php if (($stats['total_revenue'] ?? 0) > 0): ?>
                <div class="revenue-card">
                    <div class="row align-items-center">
                        <div class="col-md-8">
                            <h4 class="mb-3">
                                <i class="fas fa-money-bill-wave me-2"></i>
                                Revenue Breakdown
                            </h4>
                            
                            <div class="row">
                                <div class="col-6">
                                    <div class="mb-2">
                                        <small class="opacity-75">Presenter Revenue</small>
                                        <div class="h5 mb-0">Rp <?= number_format($stats['presenter_revenue'] ?? 0, 0, ',', '.') ?></div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="mb-2">
                                        <small class="opacity-75">Audience Revenue</small>
                                        <div class="h5 mb-0">Rp <?= number_format(($stats['audience_online_revenue'] ?? 0) + ($stats['audience_offline_revenue'] ?? 0), 0, ',', '.') ?></div>
                                    </div>
                                </div>
                            </div>
                            
                            <?php if (($stats['online_revenue'] ?? 0) > 0 && ($stats['offline_revenue'] ?? 0) > 0): ?>
                            <div class="row mt-3 pt-3 border-top border-light">
                                <div class="col-6">
                                    <small class="opacity-75">Online Revenue</small>
                                    <div class="h6 mb-0">Rp <?= number_format($stats['online_revenue'], 0, ',', '.') ?></div>
                                </div>
                                <div class="col-6">
                                    <small class="opacity-75">Offline Revenue</small>
                                    <div class="h6 mb-0">Rp <?= number_format($stats['offline_revenue'], 0, ',', '.') ?></div>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="col-md-4 text-center">
                            <div class="h2 mb-2">Rp <?= number_format($stats['total_revenue'], 0, ',', '.') ?></div>
                            <div class="opacity-75">Total Revenue</div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Recent Registrations -->
                <div class="table-responsive">
                    <h4 class="mb-3">
                        <i class="fas fa-clock me-2 text-primary"></i>
                        Registrasi Terbaru
                    </h4>
                    
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Nama</th>
                                <th>Role</th>
                                <th>Tipe Partisipasi</th>
                                <th>Status</th>
                                <th>Tanggal</th>
                                <th>Jumlah</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recent_registrations)): ?>
                                <?php foreach (array_slice($recent_registrations, 0, 10) as $reg): ?>
                                <tr>
                                    <td>
                                        <div class="fw-semibold"><?= esc($reg['nama_lengkap']) ?></div>
                                        <small class="text-muted"><?= esc($reg['email']) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $reg['role'] === 'presenter' ? 'primary' : 'info' ?>">
                                            <?= ucfirst($reg['role']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?= $reg['participation_type'] === 'online' ? 'info' : 'success' ?>">
                                            <i class="fas fa-<?= $reg['participation_type'] === 'online' ? 'video' : 'map-marker-alt' ?> me-1"></i>
                                            <?= ucfirst($reg['participation_type']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge bg-<?php
                                            if ($reg['status'] === 'verified') echo 'success';
                                            elseif ($reg['status'] === 'pending') echo 'warning';
                                            else echo 'danger';
                                        ?>">
                                            <?= ucfirst($reg['status']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <small><?= date('d/m/Y H:i', strtotime($reg['tanggal_bayar'])) ?></small>
                                    </td>
                                    <td>
                                        <span class="fw-semibold">Rp <?= number_format($reg['jumlah'], 0, ',', '.') ?></span>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        <i class="fas fa-inbox fa-2x mb-2 d-block"></i>
                                        Belum ada registrasi
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="col-lg-4">
                <!-- Event Info -->
                <div class="stat-card mb-4">
                    <h5 class="mb-3">
                        <i class="fas fa-info-circle me-2 text-primary"></i>
                        Informasi Event
                    </h5>
                    
                    <div class="mb-3">
                        <small class="text-muted">Deskripsi</small>
                        <p class="mb-0"><?= esc($event['description'] ?: 'Tidak ada deskripsi') ?></p>
                    </div>
                    
                    <?php if ($event['location']): ?>
                    <div class="mb-3">
                        <small class="text-muted">Lokasi</small>
                        <p class="mb-0">
                            <i class="fas fa-map-marker-alt text-success me-1"></i>
                            <?= esc($event['location']) ?>
                        </p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($event['zoom_link']): ?>
                    <div class="mb-3">
                        <small class="text-muted">Link Online</small>
                        <p class="mb-0">
                            <i class="fas fa-video text-info me-1"></i>
                            <a href="<?= esc($event['zoom_link']) ?>" target="_blank" class="text-decoration-none">
                                Zoom Meeting
                            </a>
                        </p>
                    </div>
                    <?php endif; ?>
                    
                    <?php if ($event['max_participants']): ?>
                    <div class="mb-3">
                        <small class="text-muted">Kapasitas Maksimal</small>
                        <p class="mb-0">
                            <i class="fas fa-users text-warning me-1"></i>
                            <?= number_format($event['max_participants']) ?> peserta
                        </p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mb-3">
                        <small class="text-muted">Status Pendaftaran</small>
                        <p class="mb-0">
                            <span class="badge <?= $registration_open ? 'bg-success' : 'bg-danger' ?>">
                                <?= $registration_open ? 'Buka' : 'Tutup' ?>
                            </span>
                        </p>
                    </div>
                    
                    <div class="mb-3">
                        <small class="text-muted">Submit Abstrak</small>
                        <p class="mb-0">
                            <span class="badge <?= $abstract_open ? 'bg-success' : 'bg-danger' ?>">
                                <?= $abstract_open ? 'Buka' : 'Tutup' ?>
                            </span>
                        </p>
                    </div>
                </div>

                <!-- Pricing Info -->
                <div class="stat-card mb-4">
                    <h5 class="mb-3">
                        <i class="fas fa-money-bill-wave me-2 text-success"></i>
                        Harga Registrasi
                    </h5>
                    
                    <div class="mb-3 p-3 bg-primary bg-opacity-10 rounded">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <div class="fw-semibold text-primary">Presenter</div>
                                <small class="text-muted">Offline Only</small>
                            </div>
                            <div class="h5 mb-0">Rp <?= number_format($pricing_matrix['presenter']['offline'] ?? 0, 0, ',', '.') ?></div>
                        </div>
                    </div>
                    
                    <div class="mb-2 p-3 bg-info bg-opacity-10 rounded">
                        <div class="fw-semibold text-info mb-2">Audience</div>
                        
                        <?php if ($event['format'] != 'offline'): ?>
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <small class="text-muted">Online</small>
                            <span class="fw-semibold">Rp <?= number_format($pricing_matrix['audience']['online'] ?? 0, 0, ',', '.') ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <?php if ($event['format'] != 'online'): ?>
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted">Offline</small>
                            <span class="fw-semibold">Rp <?= number_format($pricing_matrix['audience']['offline'] ?? 0, 0, ',', '.') ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Quick Actions -->
                <div class="stat-card">
                    <h5 class="mb-3">
                        <i class="fas fa-bolt me-2 text-warning"></i>
                        Aksi Cepat
                    </h5>
                    
                    <div class="d-grid gap-2">
                        <a href="/admin/event/sessions/<?= $event['id'] ?>" class="btn btn-primary btn-custom">
                            <i class="fas fa-cog me-1"></i>Kelola Sesi
                        </a>
                        
                        <button class="btn btn-outline-<?= $event['registration_active'] ? 'warning' : 'success' ?> btn-custom" 
                                onclick="toggleRegistration(<?= $event['id'] ?>)">
                            <i class="fas fa-<?= $event['registration_active'] ? 'pause' : 'play' ?> me-1"></i>
                            <?= $event['registration_active'] ? 'Tutup' : 'Buka' ?> Pendaftaran
                        </button>
                        
                        <button class="btn btn-outline-<?= $event['abstract_submission_active'] ? 'warning' : 'success' ?> btn-custom" 
                                onclick="toggleAbstract(<?= $event['id'] ?>)">
                            <i class="fas fa-<?= $event['abstract_submission_active'] ? 'pause' : 'play' ?> me-1"></i>
                            <?= $event['abstract_submission_active'] ? 'Tutup' : 'Buka' ?> Abstrak
                        </button>
                        
                        <a href="/admin/pembayaran?event_id=<?= $event['id'] ?>" class="btn btn-outline-info btn-custom">
                            <i class="fas fa-credit-card me-1"></i>Lihat Pembayaran
                        </a>
                        
                        <a href="/admin/abstrak?event_id=<?= $event['id'] ?>" class="btn btn-outline-warning btn-custom">
                            <i class="fas fa-file-alt me-1"></i>Lihat Abstrak
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/sweetalert2/11.7.12/sweetalert2.min.js"></script>

    <script>
        function toggleRegistration(eventId) {
            Swal.fire({
                title: 'Ubah Status Pendaftaran?',
                text: 'Apakah Anda yakin ingin mengubah status pendaftaran event ini?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Ubah!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '/admin/event/toggle-registration/' + eventId;
                }
            });
        }

        function toggleAbstract(eventId) {
            Swal.fire({
                title: 'Ubah Status Submit Abstrak?',
                text: 'Apakah Anda yakin ingin mengubah status submit abstrak event ini?',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Ubah!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = '/admin/event/toggle-abstract/' + eventId;
                }
            });
        }

        // Auto refresh data setiap 30 detik
        setInterval(function() {
            // Refresh statistik via AJAX jika diperlukan
            // $.get('/api/events/stats/<?= $event['id'] ?>', function(data) {
            //     // Update DOM dengan data terbaru
            // });
        }, 30000);

        // Show alerts for flash messages
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
    </script>
</body>
</html>