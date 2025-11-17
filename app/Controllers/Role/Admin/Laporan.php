<?php

namespace App\Controllers\Role\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\AbstrakModel;
use App\Models\PembayaranModel;
use App\Models\AbsensiModel;
use App\Models\ReviewModel;
use App\Models\EventModel;

class Laporan extends BaseController
{
    protected $userModel;
    protected $abstrakModel;
    protected $pembayaranModel;
    protected $absensiModel;
    protected $reviewModel;
    protected $eventModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->abstrakModel = new AbstrakModel();
        $this->pembayaranModel = new PembayaranModel();
        $this->absensiModel = new AbsensiModel();
        $this->reviewModel = new ReviewModel();
        $this->eventModel = new EventModel();
    }

    public function index()
    {
        // Get comprehensive statistics
        $data = [
            // User statistics
            'total_users' => $this->userModel->countAll(),
            'user_by_role' => [
                'admin' => $this->userModel->where('role', 'admin')->countAllResults(),
                'presenter' => $this->userModel->where('role', 'presenter')->countAllResults(),
                'audience' => $this->userModel->where('role', 'audience')->countAllResults(),
                'reviewer' => $this->userModel->where('role', 'reviewer')->countAllResults(),
            ],
            'user_by_status' => [
                'aktif' => $this->userModel->where('status', 'aktif')->countAllResults(),
                'nonaktif' => $this->userModel->where('status', 'nonaktif')->countAllResults(),
            ],

            // Abstrak statistics
            'total_abstrak' => $this->abstrakModel->countAll(),
            'abstrak_by_status' => [
                'menunggu' => $this->abstrakModel->where('status', 'menunggu')->countAllResults(),
                'sedang_direview' => $this->abstrakModel->where('status', 'sedang_direview')->countAllResults(),
                'diterima' => $this->abstrakModel->where('status', 'diterima')->countAllResults(),
                'ditolak' => $this->abstrakModel->where('status', 'ditolak')->countAllResults(),
                'revisi' => $this->abstrakModel->where('status', 'revisi')->countAllResults(),
            ],

            // Pembayaran statistics
            'total_pembayaran' => $this->pembayaranModel->countAll(),
            'pembayaran_by_status' => [
                'pending' => $this->pembayaranModel->where('status', 'pending')->countAllResults(),
                'verified' => $this->pembayaranModel->where('status', 'verified')->countAllResults(),
                'rejected' => $this->pembayaranModel->where('status', 'rejected')->countAllResults(),
            ],
            'total_revenue' => $this->pembayaranModel
                                   ->selectSum('jumlah')
                                   ->where('status', 'verified')
                                   ->first()['jumlah'] ?? 0,

            // Recent activities
            'recent_registrations' => $this->userModel->orderBy('created_at', 'DESC')->limit(5)->findAll(),
            'recent_abstraks' => $this->abstrakModel->getAbstrakWithDetails(5),
            'recent_payments' => $this->pembayaranModel->getPembayaranWithUser(5),
        ];

        // Get monthly data for charts (last 6 months) - PostgreSQL compatible
        $monthlyData = $this->getMonthlyStatistics(6);
        $data['monthly_stats'] = $monthlyData;

        return view('role/admin/laporan/index', $data);
    }

    public function export()
    {
        try {
            $type = $this->request->getGet('type') ?? 'comprehensive';
            $format = $this->request->getGet('format') ?? 'csv';

            switch ($type) {
                case 'users':
                    return $this->exportUsers($format);
                case 'abstrak':
                    return $this->exportAbstrak($format);
                case 'pembayaran':
                    return $this->exportPembayaran($format);
                case 'comprehensive':
                default:
                    return $this->exportComprehensive($format);
            }
        } catch (\Throwable $e) {
            log_message('error', 'Export error: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            
            return redirect()->back()->with('error', 'Gagal melakukan export: ' . $e->getMessage());
        }
    }

    private function getMonthlyStatistics($months = 6)
    {
        $data = [];
        
        for ($i = $months - 1; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-$i months"));
            $monthName = date('M Y', strtotime($month . '-01'));
            
            // PostgreSQL compatible date range queries - FIXED
            $startDate = $month . '-01';
            $endDate = date('Y-m-t', strtotime($startDate)); // FIX: Gunakan Y-m-t untuk mendapatkan hari terakhir yang valid
            
            // Users registered this month
            $usersThisMonth = $this->userModel
                                  ->where('created_at >=', $startDate)
                                  ->where('created_at <=', $endDate . ' 23:59:59')
                                  ->countAllResults();
            
            // Abstraks submitted this month
            $abstraksThisMonth = $this->abstrakModel
                                     ->where('tanggal_upload >=', $startDate)
                                     ->where('tanggal_upload <=', $endDate . ' 23:59:59')
                                     ->countAllResults();
            
            // Revenue this month
            $revenueThisMonth = $this->pembayaranModel
                                    ->selectSum('jumlah')
                                    ->where('status', 'verified')
                                    ->where('tanggal_bayar >=', $startDate)
                                    ->where('tanggal_bayar <=', $endDate . ' 23:59:59')
                                    ->first()['jumlah'] ?? 0;
            
            $data[] = [
                'month' => $monthName,
                'users' => $usersThisMonth,
                'abstraks' => $abstraksThisMonth,
                'revenue' => $revenueThisMonth
            ];
        }
        
        return $data;
    }

    private function exportUsers($format)
    {
        $users = $this->userModel->orderBy('created_at', 'DESC')->findAll();
        
        if ($format === 'csv') {
            // Clear any previous output
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            $filename = 'laporan_users_' . date('Y-m-d') . '.csv';
            
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            $output = fopen('php://output', 'w');
            
            // BOM untuk Excel UTF-8
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // CSV Headers
            fputcsv($output, [
                'ID User', 'Nama Lengkap', 'Email', 'Role', 'Status', 
                'Email Verified', 'Tanggal Daftar', 'Terakhir Update'
            ]);
            
            // CSV Data
            foreach ($users as $user) {
                fputcsv($output, [
                    $user['id_user'],
                    $user['nama_lengkap'],
                    $user['email'],
                    ucfirst($user['role']),
                    ucfirst($user['status']),
                    $user['email_verified_at'] ? 'Ya' : 'Tidak',
                    date('d/m/Y H:i', strtotime($user['created_at'])),
                    date('d/m/Y H:i', strtotime($user['updated_at']))
                ]);
            }
            
            // Hitung statistik per role dan status
            $countAdmin = 0;
            $countPresenter = 0;
            $countAudience = 0;
            $countReviewer = 0;
            $countAktif = 0;
            $countNonaktif = 0;
            $countVerified = 0;
            $countNotVerified = 0;
            
            foreach ($users as $user) {
                // Count by role
                switch ($user['role']) {
                    case 'admin': $countAdmin++; break;
                    case 'presenter': $countPresenter++; break;
                    case 'audience': $countAudience++; break;
                    case 'reviewer': $countReviewer++; break;
                }
                
                // Count by status
                if ($user['status'] === 'aktif') $countAktif++;
                else $countNonaktif++;
                
                // Count email verification
                if ($user['email_verified_at']) $countVerified++;
                else $countNotVerified++;
            }
            
            // Tambahkan ringkasan di bawah
            fputcsv($output, []);
            fputcsv($output, ['=== RINGKASAN DATA USER ===']);
            fputcsv($output, []);
            fputcsv($output, ['Total User', count($users)]);
            fputcsv($output, []);
            
            fputcsv($output, ['Berdasarkan Role:']);
            fputcsv($output, ['Admin', $countAdmin]);
            fputcsv($output, ['Presenter', $countPresenter]);
            fputcsv($output, ['Audience', $countAudience]);
            fputcsv($output, ['Reviewer', $countReviewer]);
            fputcsv($output, []);
            
            fputcsv($output, ['Berdasarkan Status:']);
            fputcsv($output, ['Aktif', $countAktif]);
            fputcsv($output, ['Nonaktif', $countNonaktif]);
            fputcsv($output, []);
            
            fputcsv($output, ['Verifikasi Email:']);
            fputcsv($output, ['Sudah Verifikasi', $countVerified]);
            fputcsv($output, ['Belum Verifikasi', $countNotVerified]);
            fputcsv($output, []);
            
            fputcsv($output, ['Tanggal Export', date('d/m/Y H:i:s')]);
            
            fclose($output);
            exit;
        }
        
        return;
    }

    private function exportAbstrak($format)
    {
        $abstraks = $this->abstrakModel->getAbstrakWithDetails();
        
        if ($format === 'csv') {
            // Clear any previous output
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            $filename = 'laporan_abstrak_' . date('Y-m-d') . '.csv';
            
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            $output = fopen('php://output', 'w');
            
            // BOM untuk Excel UTF-8
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // CSV Headers
            fputcsv($output, [
                'ID Abstrak', 'Judul', 'Nama Penulis', 'Email', 'Status', 
                'Kategori', 'Tanggal Upload', 'Reviewer'
            ]);
            
            // CSV Data
            foreach ($abstraks as $abstrak) {
                fputcsv($output, [
                    $abstrak['id_abstrak'],
                    $abstrak['judul'],
                    $abstrak['nama_lengkap'] ?? '-',
                    $abstrak['email'] ?? '-',
                    ucfirst($abstrak['status']),
                    $abstrak['nama_kategori'] ?? '-',
                    date('d/m/Y H:i', strtotime($abstrak['tanggal_upload'])),
                    $abstrak['reviewer_name'] ?? 'Belum ditugaskan'
                ]);
            }
            
            // Hitung statistik
            $countMenunggu = 0;
            $countSedangReview = 0;
            $countDiterima = 0;
            $countDitolak = 0;
            $countRevisi = 0;
            $kategoriCount = [];
            $assignedReviewer = 0;
            $unassignedReviewer = 0;
            
            foreach ($abstraks as $abstrak) {
                // Count by status
                switch ($abstrak['status']) {
                    case 'menunggu': $countMenunggu++; break;
                    case 'sedang_direview': $countSedangReview++; break;
                    case 'diterima': $countDiterima++; break;
                    case 'ditolak': $countDitolak++; break;
                    case 'revisi': $countRevisi++; break;
                }
                
                // Count by kategori
                $kategori = $abstrak['nama_kategori'] ?? 'Tidak Ada Kategori';
                if (!isset($kategoriCount[$kategori])) {
                    $kategoriCount[$kategori] = 0;
                }
                $kategoriCount[$kategori]++;
                
                // Count reviewer assignment
                if (!empty($abstrak['reviewer_name']) && $abstrak['reviewer_name'] !== 'Belum ditugaskan') {
                    $assignedReviewer++;
                } else {
                    $unassignedReviewer++;
                }
            }
            
            // Tambahkan ringkasan di bawah
            fputcsv($output, []);
            fputcsv($output, ['=== RINGKASAN DATA ABSTRAK ===']);
            fputcsv($output, []);
            fputcsv($output, ['Total Abstrak', count($abstraks)]);
            fputcsv($output, []);
            
            fputcsv($output, ['Berdasarkan Status:']);
            fputcsv($output, ['Menunggu', $countMenunggu]);
            fputcsv($output, ['Sedang Review', $countSedangReview]);
            fputcsv($output, ['Diterima', $countDiterima]);
            fputcsv($output, ['Ditolak', $countDitolak]);
            fputcsv($output, ['Revisi', $countRevisi]);
            fputcsv($output, []);
            
            fputcsv($output, ['Berdasarkan Kategori:']);
            arsort($kategoriCount); // Sort by count descending
            foreach ($kategoriCount as $kategori => $count) {
                fputcsv($output, [$kategori, $count]);
            }
            fputcsv($output, []);
            
            fputcsv($output, ['Penugasan Reviewer:']);
            fputcsv($output, ['Sudah Ditugaskan', $assignedReviewer]);
            fputcsv($output, ['Belum Ditugaskan', $unassignedReviewer]);
            fputcsv($output, []);
            
            // Persentase acceptance
            $totalReviewed = $countDiterima + $countDitolak;
            $acceptanceRate = $totalReviewed > 0 ? round(($countDiterima / $totalReviewed) * 100, 2) : 0;
            fputcsv($output, ['Acceptance Rate', $acceptanceRate . '%']);
            fputcsv($output, []);
            
            fputcsv($output, ['Tanggal Export', date('d/m/Y H:i:s')]);
            
            fclose($output);
            exit;
        }
        
        return;
    }

    private function exportPembayaran($format)
    {
        $pembayarans = $this->pembayaranModel->getPembayaranWithUser();
        
        if ($format === 'csv') {
            // Clear any previous output
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            $filename = 'laporan_pembayaran_' . date('Y-m-d') . '.csv';
            
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            $output = fopen('php://output', 'w');
            
            // BOM untuk Excel UTF-8
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // CSV Headers
            fputcsv($output, [
                'ID Pembayaran', 'Nama User', 'Email', 'Role', 'Event', 
                'Pricing Tier', 'Metode Pembayaran', 'Jumlah', 'Status', 'Tanggal Bayar', 'Tanggal Verifikasi'
            ]);
            
            // Inisialisasi variabel untuk total
            $totalSemua = 0;
            $totalPending = 0;
            $totalVerified = 0;
            $totalRejected = 0;
            $countPending = 0;
            $countVerified = 0;
            $countRejected = 0;
            
            // Inisialisasi untuk metode bank (disesuaikan dengan comprehensive)
            $bankStats = [];
            
            // Inisialisasi untuk Early Bird
            $earlyBirdCount = 0;
            $earlyBirdTotal = 0;
            
            // Inisialisasi untuk pricing tier lainnya
            $pricingStats = [
                'early_bird' => ['count' => 0, 'total' => 0],
                'wave_1' => ['count' => 0, 'total' => 0],
                'regular' => ['count' => 0, 'total' => 0],
                'on_site' => ['count' => 0, 'total' => 0],
                'unknown' => ['count' => 0, 'total' => 0]
            ];
            
            // CSV Data dengan enrichment pricing tier
            foreach ($pembayarans as $pembayaran) {
                // Determine pricing tier
                $pricingTier = '-';
                $pricingKey = 'unknown';
                
                if (!empty($pembayaran['event_id'])) {
                    $event = $this->eventModel->find($pembayaran['event_id']);
                    if ($event) {
                        $waves = [];
                        if (!empty($event['registration_waves'])) {
                            $waves = is_string($event['registration_waves']) 
                                ? json_decode($event['registration_waves'], true) 
                                : $event['registration_waves'];
                        }

                        $paymentDate = strtotime($pembayaran['tanggal_bayar'] ?? 'now');
                        $participationType = $pembayaran['participation_type'] ?? 'offline';
                        
                        if (!empty($waves) && is_array($waves)) {
                            foreach ($waves as $index => $wave) {
                                $start = strtotime($wave['registration_start'] ?? '');
                                $end = strtotime($wave['registration_deadline'] ?? '');
                                
                                if ($paymentDate >= $start && $paymentDate <= $end) {
                                    // EARLY BIRD HANYA UNTUK OFFLINE DI WAVE 1
                                    if ($index === 0 && $participationType === 'offline') {
                                        $pricingTier = 'Early Bird';
                                        $pricingKey = 'early_bird';
                                    } else {
                                        if ($index === 1) {
                                            $pricingTier = 'Wave 2 (Regular)';
                                            $pricingKey = 'regular';
                                        } elseif ($index === 2) {
                                            $pricingTier = 'Wave 3 (On-Site)';
                                            $pricingKey = 'on_site';
                                        } else {
                                            $pricingTier = 'Wave 1';
                                            $pricingKey = 'wave_1';
                                        }
                                    }
                                    break;
                                }
                            }
                        }
                    }
                }
                
                // Hitung statistik pricing tier
                $pricingStats[$pricingKey]['count']++;
                if ($pembayaran['status'] === 'verified') {
                    $pricingStats[$pricingKey]['total'] += $pembayaran['jumlah'];
                    
                    // Khusus Early Bird
                    if ($pricingKey === 'early_bird') {
                        $earlyBirdCount++;
                        $earlyBirdTotal += $pembayaran['jumlah'];
                    }
                }
                
                // ===== GET PAYMENT METHOD DISPLAY NAME =====
                $metode = strtolower($pembayaran['metode']);
                $paymentType = strtolower($pembayaran['midtrans_payment_type'] ?? '');
                $paymentMethodDisplay = '';
                
                // Jika metode Midtrans, tampilkan detail dari payment_type
                if ($metode === 'midtrans' && !empty($paymentType)) {
                    // Mapping payment type Midtrans ke display name
                    $paymentMethodMap = [
                        'bca_va' => 'BCA Virtual Account',
                        'bni_va' => 'BNI Virtual Account',
                        'bri_va' => 'BRI Virtual Account',
                        'mandiri_va' => 'Mandiri Virtual Account',
                        'permata_va' => 'Permata Virtual Account',
                        'gopay' => 'GoPay',
                        'shopeepay' => 'ShopeePay',
                        'qris' => 'QRIS',
                        'credit_card' => 'Kartu Kredit',
                        'debit_card' => 'Kartu Debit',
                        'dana' => 'DANA',
                        'bank_transfer' => 'Bank Transfer'
                    ];
                    
                    // Coba match exact
                    if (isset($paymentMethodMap[$paymentType])) {
                        $paymentMethodDisplay = $paymentMethodMap[$paymentType];
                    } else {
                        // Coba partial match
                        foreach ($paymentMethodMap as $key => $displayName) {
                            if (strpos($paymentType, $key) !== false) {
                                $paymentMethodDisplay = $displayName;
                                break;
                            }
                        }
                        
                        // Jika masih tidak ketemu, tampilkan payment type dengan format yang rapi
                        if (empty($paymentMethodDisplay)) {
                            $paymentMethodDisplay = ucwords(str_replace('_', ' ', $paymentType));
                        }
                    }
                } else {
                    // Metode non-Midtrans
                    $paymentMethodDisplay = ucfirst($metode);
                }
                
                // Output CSV row
                fputcsv($output, [
                    $pembayaran['id_pembayaran'],
                    $pembayaran['nama_lengkap'] ?? '-',
                    $pembayaran['email'] ?? '-',
                    ucfirst($pembayaran['role'] ?? '-'),
                    $pembayaran['event_title'] ?? '-',
                    $pricingTier,
                    $paymentMethodDisplay,
                    number_format($pembayaran['jumlah'], 0, ',', '.'),
                    ucfirst($pembayaran['status']),
                    date('d/m/Y H:i', strtotime($pembayaran['tanggal_bayar'])),
                    $pembayaran['verified_at'] ? date('d/m/Y H:i', strtotime($pembayaran['verified_at'])) : '-'
                ]);
                
                // Hitung total per status
                $totalSemua += $pembayaran['jumlah'];
                
                if ($pembayaran['status'] === 'pending') {
                    $totalPending += $pembayaran['jumlah'];
                    $countPending++;
                } elseif ($pembayaran['status'] === 'verified') {
                    $totalVerified += $pembayaran['jumlah'];
                    $countVerified++;
                    
                    // Hitung statistik bank (hanya untuk verified)
                    $metode = strtolower($pembayaran['metode']);
                    $paymentType = strtolower($pembayaran['midtrans_payment_type'] ?? '');
                    $bankDetected = false;
                    
                    // Jika metode Midtrans, deteksi dari payment_type
                    if ($metode === 'midtrans' && !empty($paymentType)) {
                        // Deteksi bank dari payment_type
                        $bankKey = null;
                        
                        if (strpos($paymentType, 'bca') !== false) {
                            $bankKey = 'BCA';
                        } elseif (strpos($paymentType, 'bni') !== false) {
                            $bankKey = 'BNI';
                        } elseif (strpos($paymentType, 'mandiri') !== false) {
                            $bankKey = 'Mandiri';
                        } elseif (strpos($paymentType, 'bri') !== false) {
                            $bankKey = 'BRI';
                        } elseif (strpos($paymentType, 'permata') !== false) {
                            $bankKey = 'Permata';
                        } elseif (strpos($paymentType, 'gopay') !== false) {
                            $bankKey = 'GoPay';
                        } elseif (strpos($paymentType, 'shopeepay') !== false) {
                            $bankKey = 'ShopeePay';
                        } elseif (strpos($paymentType, 'qris') !== false) {
                            $bankKey = 'QRIS';
                        } elseif (strpos($paymentType, 'credit_card') !== false || strpos($paymentType, 'debit_card') !== false) {
                            $bankKey = 'Kartu Kredit/Debit';
                        }
                        
                        if ($bankKey) {
                            if (!isset($bankStats[$bankKey])) {
                                $bankStats[$bankKey] = ['count' => 0, 'total' => 0];
                            }
                            $bankStats[$bankKey]['count']++;
                            $bankStats[$bankKey]['total'] += $pembayaran['jumlah'];
                            $bankDetected = true;
                        }
                    } else {
                        // Untuk metode non-Midtrans, cek dari kolom metode langsung
                        $metodeUpper = strtoupper($pembayaran['metode']);
                        $bankKey = null;
                        
                        if (strpos($metodeUpper, 'BCA') !== false) {
                            $bankKey = 'BCA';
                        } elseif (strpos($metodeUpper, 'BNI') !== false) {
                            $bankKey = 'BNI';
                        } elseif (strpos($metodeUpper, 'MANDIRI') !== false) {
                            $bankKey = 'Mandiri';
                        } elseif (strpos($metodeUpper, 'BRI') !== false) {
                            $bankKey = 'BRI';
                        }
                        
                        if ($bankKey) {
                            if (!isset($bankStats[$bankKey])) {
                                $bankStats[$bankKey] = ['count' => 0, 'total' => 0];
                            }
                            $bankStats[$bankKey]['count']++;
                            $bankStats[$bankKey]['total'] += $pembayaran['jumlah'];
                            $bankDetected = true;
                        }
                    }
                    
                    // Jika tidak terdeteksi, masukkan ke "Lainnya"
                    if (!$bankDetected) {
                        if (!isset($bankStats['Lainnya'])) {
                            $bankStats['Lainnya'] = ['count' => 0, 'total' => 0];
                        }
                        $bankStats['Lainnya']['count']++;
                        $bankStats['Lainnya']['total'] += $pembayaran['jumlah'];
                    }
                    
                } elseif ($pembayaran['status'] === 'rejected') {
                    $totalRejected += $pembayaran['jumlah'];
                    $countRejected++;
                }
            }
            
            // Tambahkan ringkasan di bawah
            fputcsv($output, []);
            fputcsv($output, ['=== RINGKASAN PEMBAYARAN ===']);
            fputcsv($output, []);
            
            fputcsv($output, ['Total Transaksi', count($pembayarans)]);
            fputcsv($output, ['Total Nominal Semua', 'Rp ' . number_format($totalSemua, 0, ',', '.')]);
            fputcsv($output, []);
            
            fputcsv($output, ['Status Pending', $countPending . ' transaksi']);
            fputcsv($output, ['Nominal Pending', 'Rp ' . number_format($totalPending, 0, ',', '.')]);
            fputcsv($output, []);
            
            fputcsv($output, ['Status Verified', $countVerified . ' transaksi']);
            fputcsv($output, ['Nominal Verified (Revenue)', 'Rp ' . number_format($totalVerified, 0, ',', '.')]);
            fputcsv($output, []);
            
            fputcsv($output, ['Status Rejected', $countRejected . ' transaksi']);
            fputcsv($output, ['Nominal Rejected', 'Rp ' . number_format($totalRejected, 0, ',', '.')]);
            fputcsv($output, []);
            
            // === STATISTIK METODE BANK (VERIFIED) ===
            fputcsv($output, ['=== STATISTIK METODE BANK (VERIFIED) ===']);
            fputcsv($output, []);
            
            foreach ($bankStats as $bank => $stats) {
                if ($stats['count'] > 0) {
                    fputcsv($output, [
                        $bank,$stats['count'] . ' transaksi',
                        'Rp ' . number_format($stats['total'], 0, ',', '.')
                    ]);
                }
            }
            
            fputcsv($output, []);
            
            // === STATISTIK PRICING TIER ===
            fputcsv($output, ['=== STATISTIK PRICING TIER ===']);
            fputcsv($output, []);
            
            $tierLabels = [
                'early_bird' => 'Early Bird (Wave 1 Offline)',
                'wave_1' => 'Wave 1 (Online)',
                'regular' => 'Wave 2 (Regular)',
                'on_site' => 'Wave 3 (On-Site)',
                'unknown' => 'Tidak Teridentifikasi'
            ];
            
            foreach ($pricingStats as $tier => $stats) {
                if ($stats['count'] > 0) {
                    fputcsv($output, [
                        $tierLabels[$tier],
                        $stats['count'] . ' transaksi',
                        'Rp ' . number_format($stats['total'], 0, ',', '.') . ' (verified)'
                    ]);
                }
            }
            
            fputcsv($output, []);
            
            // === HIGHLIGHT EARLY BIRD ===
            fputcsv($output, ['=== EARLY BIRD SPECIAL ===']);
            fputcsv($output, ['Total Peserta Early Bird', $earlyBirdCount . ' orang']);
            fputcsv($output, ['Total Revenue Early Bird (Verified)', 'Rp ' . number_format($earlyBirdTotal, 0, ',', '.')]);
            
            if ($earlyBirdCount > 0 && $earlyBirdTotal > 0) {
                $avgEarlyBird = $earlyBirdTotal / $earlyBirdCount;
                fputcsv($output, ['Rata-rata per Peserta', 'Rp ' . number_format($avgEarlyBird, 0, ',', '.')]);
            }
            
            fputcsv($output, []);
            fputcsv($output, ['Tanggal Export', date('d/m/Y H:i:s')]);
            
            fclose($output);
            exit;
        }
        
        return;
    }

    private function exportComprehensive($format)
    {
        if ($format === 'csv') {
            // Clear any previous output
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            $filename = 'laporan_komprehensif_' . date('Y-m-d') . '.csv';
            
            header('Content-Type: text/csv; charset=UTF-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');
            
            $output = fopen('php://output', 'w');
            
            // BOM untuk Excel UTF-8
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Write summary statistics
            fputcsv($output, ['LAPORAN KOMPREHENSIF SNIA']);
            fputcsv($output, ['Tanggal Export: ' . date('d/m/Y H:i:s')]);
            fputcsv($output, []);
            
            // User statistics
            fputcsv($output, ['=== STATISTIK USER ===']);
            fputcsv($output, ['Total User', $this->userModel->countAll()]);
            fputcsv($output, ['Admin', $this->userModel->where('role', 'admin')->countAllResults()]);
            fputcsv($output, ['Presenter', $this->userModel->where('role', 'presenter')->countAllResults()]);
            fputcsv($output, ['Audience', $this->userModel->where('role', 'audience')->countAllResults()]);
            fputcsv($output, ['Reviewer', $this->userModel->where('role', 'reviewer')->countAllResults()]);
            fputcsv($output, []);
            fputcsv($output, ['Status Aktif', $this->userModel->where('status', 'aktif')->countAllResults()]);
            fputcsv($output, ['Status Nonaktif', $this->userModel->where('status', 'nonaktif')->countAllResults()]);
            fputcsv($output, []);
            
            // Abstrak statistics
            fputcsv($output, ['=== STATISTIK ABSTRAK ===']);
            fputcsv($output, ['Total Abstrak', $this->abstrakModel->countAll()]);
            fputcsv($output, ['Menunggu', $this->abstrakModel->where('status', 'menunggu')->countAllResults()]);
            fputcsv($output, ['Sedang Review', $this->abstrakModel->where('status', 'sedang_direview')->countAllResults()]);
            fputcsv($output, ['Diterima', $this->abstrakModel->where('status', 'diterima')->countAllResults()]);
            fputcsv($output, ['Ditolak', $this->abstrakModel->where('status', 'ditolak')->countAllResults()]);
            fputcsv($output, ['Revisi', $this->abstrakModel->where('status', 'revisi')->countAllResults()]);
            fputcsv($output, []);
            
            // Pembayaran statistics
            fputcsv($output, ['=== STATISTIK PEMBAYARAN ===']);
            fputcsv($output, ['Total Transaksi', $this->pembayaranModel->countAll()]);
            fputcsv($output, ['Pending', $this->pembayaranModel->where('status', 'pending')->countAllResults()]);
            fputcsv($output, ['Verified', $this->pembayaranModel->where('status', 'verified')->countAllResults()]);
            fputcsv($output, ['Rejected', $this->pembayaranModel->where('status', 'rejected')->countAllResults()]);
            fputcsv($output, []);
            
            // Total revenue
            $totalRevenue = $this->pembayaranModel
                               ->selectSum('jumlah')
                               ->where('status', 'verified')
                               ->first()['jumlah'] ?? 0;
            
            $totalPending = $this->pembayaranModel
                               ->selectSum('jumlah')
                               ->where('status', 'pending')
                               ->first()['jumlah'] ?? 0;
            
            $totalRejected = $this->pembayaranModel
                               ->selectSum('jumlah')
                               ->where('status', 'rejected')
                               ->first()['jumlah'] ?? 0;
            
            fputcsv($output, ['Total Revenue (Verified)', 'Rp ' . number_format($totalRevenue, 0, ',', '.')]);
            fputcsv($output, ['Total Pending', 'Rp ' . number_format($totalPending, 0, ',', '.')]);
            fputcsv($output, ['Total Rejected', 'Rp ' . number_format($totalRejected, 0, ',', '.')]);
            fputcsv($output, []);
            
            // Pembayaran per metode bank
            fputcsv($output, ['=== PEMBAYARAN PER METODE BANK (VERIFIED) ===']);
            
            try {
                $pembayaransVerified = $this->pembayaranModel
                    ->where('status', 'verified')
                    ->findAll();
                
                $bankStats = [];
                
                foreach ($pembayaransVerified as $p) {
                    $metode = strtolower($p['metode']);
                    $paymentType = strtolower($p['midtrans_payment_type'] ?? '');
                    $bankDetected = false;
                    
                    // Jika metode Midtrans, deteksi dari payment_type
                    if ($metode === 'midtrans' && !empty($paymentType)) {
                        $bankKey = null;
                        
                        if (strpos($paymentType, 'bca') !== false) {
                            $bankKey = 'BCA';
                        } elseif (strpos($paymentType, 'bni') !== false) {
                            $bankKey = 'BNI';
                        } elseif (strpos($paymentType, 'mandiri') !== false) {
                            $bankKey = 'Mandiri';
                        } elseif (strpos($paymentType, 'bri') !== false) {
                            $bankKey = 'BRI';
                        }
                        
                        if ($bankKey) {
                            if (!isset($bankStats[$bankKey])) {
                                $bankStats[$bankKey] = ['count' => 0, 'total' => 0];
                            }
                            $bankStats[$bankKey]['count']++;
                            $bankStats[$bankKey]['total'] += $p['jumlah'];
                            $bankDetected = true;
                        }
                    } else {
                        // Untuk metode non-Midtrans, cek dari kolom metode langsung
                        $metodeUpper = strtoupper($p['metode']);
                        $bankKey = null;
                        
                        if (strpos($metodeUpper, 'BCA') !== false) {
                            $bankKey = 'BCA';
                        } elseif (strpos($metodeUpper, 'BNI') !== false) {
                            $bankKey = 'BNI';
                        } elseif (strpos($metodeUpper, 'MANDIRI') !== false) {
                            $bankKey = 'Mandiri';
                        } elseif (strpos($metodeUpper, 'BRI') !== false) {
                            $bankKey = 'BRI';
                        }
                        
                        if ($bankKey) {
                            if (!isset($bankStats[$bankKey])) {
                                $bankStats[$bankKey] = ['count' => 0, 'total' => 0];
                            }
                            $bankStats[$bankKey]['count']++;
                            $bankStats[$bankKey]['total'] += $p['jumlah'];
                            $bankDetected = true;
                        }
                    }
                    
                    // Jika tidak terdeteksi, masukkan ke "Lainnya"
                    if (!$bankDetected) {
                        if (!isset($bankStats['Lainnya'])) {
                            $bankStats['Lainnya'] = ['count' => 0, 'total' => 0];
                        }
                        $bankStats['Lainnya']['count']++;
                        $bankStats['Lainnya']['total'] += $p['jumlah'];
                    }
                }
                
                foreach ($bankStats as $bank => $stats) {
                    if ($stats['count'] > 0) {
                        fputcsv($output, [
                            $bank,
                            $stats['count'] . ' transaksi',
                            'Rp ' . number_format($stats['total'], 0, ',', '.')
                        ]);
                    }
                }
            } catch (\Throwable $e) {
                fputcsv($output, ['Error mengambil data metode pembayaran']);
                log_message('error', 'Error getting payment methods: ' . $e->getMessage());
            }
            
            fputcsv($output, []);
            
            // Early Bird Statistics
            fputcsv($output, ['=== STATISTIK EARLY BIRD ===']);
            
            try {
                $pembayaransAll = $this->pembayaranModel->getPembayaranWithUser();
                $earlyBirdCount = 0;
                $earlyBirdTotal = 0;
                
                foreach ($pembayaransAll as $pembayaran) {
                    if (!empty($pembayaran['event_id'])) {
                        $event = $this->eventModel->find($pembayaran['event_id']);
                        if ($event) {
                            $waves = [];
                            if (!empty($event['registration_waves'])) {
                                $waves = is_string($event['registration_waves']) 
                                    ? json_decode($event['registration_waves'], true) 
                                    : $event['registration_waves'];
                            }

                            $paymentDate = strtotime($pembayaran['tanggal_bayar'] ?? 'now');
                            $participationType = $pembayaran['participation_type'] ?? 'offline';
                            
                            if (!empty($waves) && is_array($waves)) {
                                foreach ($waves as $index => $wave) {
                                    $start = strtotime($wave['registration_start'] ?? '');
                                    $end = strtotime($wave['registration_deadline'] ?? '');
                                    
                                    if ($paymentDate >= $start && $paymentDate <= $end) {
                                        // EARLY BIRD HANYA UNTUK OFFLINE DI WAVE 1
                                        if ($index === 0 && $participationType === 'offline') {
                                            $earlyBirdCount++;
                                            if ($pembayaran['status'] === 'verified') {
                                                $earlyBirdTotal += $pembayaran['jumlah'];
                                            }
                                        }
                                        break;
                                    }
                                }
                            }
                        }
                    }
                }
                
                fputcsv($output, ['Total Peserta Early Bird', $earlyBirdCount . ' orang']);
                fputcsv($output, ['Total Revenue Early Bird (Verified)', 'Rp ' . number_format($earlyBirdTotal, 0, ',', '.')]);
                
                if ($earlyBirdCount > 0 && $earlyBirdTotal > 0) {
                    $avgEarlyBird = $earlyBirdTotal / $earlyBirdCount;
                    fputcsv($output, ['Rata-rata per Peserta', 'Rp ' . number_format($avgEarlyBird, 0, ',', '.')]);
                }
            } catch (\Throwable $e) {
                fputcsv($output, ['Error mengambil data Early Bird']);
                log_message('error', 'Error getting Early Bird stats: ' . $e->getMessage());
            }
            
            fputcsv($output, []);
            
            // Monthly statistics (last 6 months) - FIXED
            fputcsv($output, ['=== STATISTIK BULANAN (6 BULAN TERAKHIR) ===']);
            fputcsv($output, ['Bulan', 'User Baru', 'Abstrak Masuk', 'Revenue', 'Total Pembayaran']);
            
            $monthlyStats = $this->getMonthlyStatistics(6);
            $totalUsersBulanan = 0;
            $totalAbstrakBulanan = 0;
            $totalRevenueBulanan = 0;
            
            foreach ($monthlyStats as $stat) {
                // Hitung total pembayaran per bulan - FIXED
                $bulanParts = explode(' ', $stat['month']);
                $monthNum = date('m', strtotime($bulanParts[0]));
                $yearNum = $bulanParts[1];
                $monthDate = $yearNum . '-' . $monthNum;
                
                // FIX: Gunakan Y-m-t untuk mendapatkan hari terakhir yang valid
                $startDateBulan = $monthDate . '-01';
                $endDateBulan = date('Y-m-t', strtotime($startDateBulan));
                
                $totalPembayaranBulan = $this->pembayaranModel
                    ->where('tanggal_bayar >=', $startDateBulan)
                    ->where('tanggal_bayar <=', $endDateBulan . ' 23:59:59')
                    ->countAllResults();
                
                fputcsv($output, [
                    $stat['month'],
                    $stat['users'],
                    $stat['abstraks'],
                    'Rp ' . number_format($stat['revenue'], 0, ',', '.'),
                    $totalPembayaranBulan
                ]);
                
                $totalUsersBulanan += $stat['users'];
                $totalAbstrakBulanan += $stat['abstraks'];
                $totalRevenueBulanan += $stat['revenue'];
            }
            
            fputcsv($output, []);
            fputcsv($output, ['Total 6 Bulan:']);
            fputcsv($output, ['User Baru', $totalUsersBulanan]);
            fputcsv($output, ['Abstrak Masuk', $totalAbstrakBulanan]);
            fputcsv($output, ['Total Revenue', 'Rp ' . number_format($totalRevenueBulanan, 0, ',', '.')]);
            fputcsv($output, []);
            
            // Rata-rata per bulan
            fputcsv($output, ['Rata-rata Per Bulan:']);
            fputcsv($output, ['User Baru', round($totalUsersBulanan / 6, 2)]);
            fputcsv($output, ['Abstrak Masuk', round($totalAbstrakBulanan / 6, 2)]);
            fputcsv($output, ['Revenue', 'Rp ' . number_format($totalRevenueBulanan / 6, 0, ',', '.')]);
            fputcsv($output, []);
            
            fputcsv($output, ['=== INFORMASI TAMBAHAN ===']);
            
            // Top 5 kategori abstrak
            try {
                $topKategori = $this->abstrakModel
                    ->select('kategori_abstrak.nama_kategori, COUNT(*) as jumlah')
                    ->join('kategori_abstrak', 'kategori_abstrak.id_kategori = abstrak.id_kategori', 'left')
                    ->groupBy('kategori_abstrak.nama_kategori')
                    ->orderBy('jumlah', 'DESC')
                    ->limit(5)
                    ->findAll();
                
                fputcsv($output, []);
                fputcsv($output, ['Top 5 Kategori Abstrak:']);
                
                if (!empty($topKategori)) {
                    foreach ($topKategori as $index => $kat) {
                        fputcsv($output, [
                            ($index + 1) . '. ' . ($kat['nama_kategori'] ?? 'Tidak Ada Kategori'),
                            $kat['jumlah'] . ' abstrak'
                        ]);
                    }
                } else {
                    fputcsv($output, ['Belum ada data kategori']);
                }
            } catch (\Throwable $e) {
                fputcsv($output, []);
                fputcsv($output, ['Top 5 Kategori Abstrak:']);
                fputcsv($output, ['Error mengambil data kategori']);
                log_message('error', 'Error getting top categories: ' . $e->getMessage());
            }
            
            fputcsv($output, []);
            
            // Tingkat verifikasi email
            try {
                $emailVerified = $this->userModel->where('email_verified_at IS NOT NULL')->countAllResults();
                $totalUsers = $this->userModel->countAll();
                $verificationRate = $totalUsers > 0 ? round(($emailVerified / $totalUsers) * 100, 2) : 0;
                
                fputcsv($output, ['Tingkat Verifikasi Email', $verificationRate . '%']);
                fputcsv($output, ['Email Terverifikasi', $emailVerified . ' dari ' . $totalUsers . ' user']);
            } catch (\Throwable $e) {
                fputcsv($output, ['Tingkat Verifikasi Email', 'Error mengambil data']);
                log_message('error', 'Error getting email verification: ' . $e->getMessage());
            }
            
            fputcsv($output, []);
            
            // Tingkat penerimaan abstrak
            $totalDiterima = $this->abstrakModel->where('status', 'diterima')->countAllResults();
            $totalDitolak = $this->abstrakModel->where('status', 'ditolak')->countAllResults();
            $totalReviewed = $totalDiterima + $totalDitolak;
            $acceptanceRate = $totalReviewed > 0 ? round(($totalDiterima / $totalReviewed) * 100, 2) : 0;
            
            fputcsv($output, ['Acceptance Rate Abstrak', $acceptanceRate . '%']);
            fputcsv($output, ['Diterima', $totalDiterima]);
            fputcsv($output, ['Ditolak', $totalDitolak]);
            fputcsv($output, []);
            
            fputcsv($output, ['Tanggal Export', date('d/m/Y H:i:s')]);
            
            fclose($output);
            exit;
        }
        
        return;
    }

    public function getChartData()
    {
        $type = $this->request->getGet('type');
        
        switch ($type) {
            case 'monthly_users':
                return $this->getMonthlyUsersChart();
            case 'monthly_revenue':
                return $this->getMonthlyRevenueChart();
            case 'abstrak_status':
                return $this->getAbstrakStatusChart();
            case 'user_roles':
                return $this->getUserRolesChart();
            default:
                return $this->response->setJSON(['error' => 'Invalid chart type']);
        }
    }

    private function getMonthlyUsersChart()
    {
        $data = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-$i months"));
            $monthName = date('M Y', strtotime($month . '-01'));
            
            // PostgreSQL compatible date range - FIXED
            $startDate = $month . '-01';
            $endDate = date('Y-m-t', strtotime($startDate)); // FIX: Gunakan Y-m-t
            
            $count = $this->userModel
                         ->where('created_at >=', $startDate)
                         ->where('created_at <=', $endDate . ' 23:59:59')
                         ->countAllResults();
            
            $data[] = [
                'month' => $monthName,
                'count' => $count
            ];
        }
        
        return $this->response->setJSON($data);
    }

    private function getMonthlyRevenueChart()
    {
        $data = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-$i months"));
            $monthName = date('M Y', strtotime($month . '-01'));
            
            // PostgreSQL compatible date range - FIXED
            $startDate = $month . '-01';
            $endDate = date('Y-m-t', strtotime($startDate)); // FIX: Gunakan Y-m-t
            
            $revenue = $this->pembayaranModel
                           ->selectSum('jumlah')
                           ->where('status', 'verified')
                           ->where('tanggal_bayar >=', $startDate)
                           ->where('tanggal_bayar <=', $endDate . ' 23:59:59')
                           ->first()['jumlah'] ?? 0;
            
            $data[] = [
                'month' => $monthName,
                'revenue' => $revenue
            ];
        }
        
        return $this->response->setJSON($data);
    }

    private function getAbstrakStatusChart()
    {
        $data = [
            ['status' => 'Menunggu', 'count' => $this->abstrakModel->where('status', 'menunggu')->countAllResults()],
            ['status' => 'Sedang Review', 'count' => $this->abstrakModel->where('status', 'sedang_direview')->countAllResults()],
            ['status' => 'Diterima', 'count' => $this->abstrakModel->where('status', 'diterima')->countAllResults()],
            ['status' => 'Ditolak', 'count' => $this->abstrakModel->where('status', 'ditolak')->countAllResults()],
            ['status' => 'Revisi', 'count' => $this->abstrakModel->where('status', 'revisi')->countAllResults()]
        ];
        
        return $this->response->setJSON($data);
    }

    private function getUserRolesChart()
    {
        $data = [
            ['role' => 'Admin', 'count' => $this->userModel->where('role', 'admin')->countAllResults()],
            ['role' => 'Presenter', 'count' => $this->userModel->where('role', 'presenter')->countAllResults()],
            ['role' => 'Audience', 'count' => $this->userModel->where('role', 'audience')->countAllResults()],
            ['role' => 'Reviewer', 'count' => $this->userModel->where('role', 'reviewer')->countAllResults()]
        ];
        
        return $this->response->setJSON($data);
    }
}