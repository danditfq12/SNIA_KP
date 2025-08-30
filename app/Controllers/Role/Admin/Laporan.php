<?php

namespace App\Controllers\Role\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\AbstrakModel;
use App\Models\PembayaranModel;
use App\Models\AbsensiModel;
use App\Models\ReviewModel;

class Laporan extends BaseController
{
    protected $userModel;
    protected $abstrakModel;
    protected $pembayaranModel;
    protected $absensiModel;
    protected $reviewModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->abstrakModel = new AbstrakModel();
        $this->pembayaranModel = new PembayaranModel();
        $this->absensiModel = new AbsensiModel();
        $this->reviewModel = new ReviewModel();
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
    }

    private function getMonthlyStatistics($months = 6)
    {
        $data = [];
        
        for ($i = $months - 1; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-$i months"));
            $monthName = date('M Y', strtotime($month . '-01'));
            
            // PostgreSQL compatible date range queries
            $startDate = $month . '-01';
            $endDate = $month . '-' . date('t', strtotime($startDate)); // Last day of month
            
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
            $filename = 'laporan_users_' . date('Y-m-d') . '.csv';
            
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            $output = fopen('php://output', 'w');
            
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
            
            fclose($output);
        }
        
        return;
    }

    private function exportAbstrak($format)
    {
        $abstraks = $this->abstrakModel->getAbstrakWithDetails();
        
        if ($format === 'csv') {
            $filename = 'laporan_abstrak_' . date('Y-m-d') . '.csv';
            
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            $output = fopen('php://output', 'w');
            
            // CSV Headers
            fputcsv($output, [
                'ID Abstrak', 'Judul', 'Penulis', 'Email', 'Kategori', 
                'Status', 'Tanggal Upload', 'Revisi Ke'
            ]);
            
            // CSV Data
            foreach ($abstraks as $abstrak) {
                fputcsv($output, [
                    $abstrak['id_abstrak'],
                    $abstrak['judul'],
                    $abstrak['nama_lengkap'],
                    $abstrak['email'],
                    $abstrak['nama_kategori'],
                    ucfirst($abstrak['status']),
                    date('d/m/Y H:i', strtotime($abstrak['tanggal_upload'])),
                    $abstrak['revisi_ke']
                ]);
            }
            
            fclose($output);
        }
        
        return;
    }

    private function exportPembayaran($format)
    {
        $pembayarans = $this->pembayaranModel->getPembayaranWithUser();
        
        if ($format === 'csv') {
            $filename = 'laporan_pembayaran_' . date('Y-m-d') . '.csv';
            
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            $output = fopen('php://output', 'w');
            
            // CSV Headers
            fputcsv($output, [
                'ID Pembayaran', 'Nama User', 'Email', 'Role', 'Metode', 
                'Jumlah', 'Status', 'Tanggal Bayar'
            ]);
            
            // CSV Data
            foreach ($pembayarans as $pembayaran) {
                fputcsv($output, [
                    $pembayaran['id_pembayaran'],
                    $pembayaran['nama_lengkap'],
                    $pembayaran['email'],
                    ucfirst($pembayaran['role']),
                    $pembayaran['metode'],
                    $pembayaran['jumlah'],
                    ucfirst($pembayaran['status']),
                    date('d/m/Y H:i', strtotime($pembayaran['tanggal_bayar']))
                ]);
            }
            
            fclose($output);
        }
        
        return;
    }

    private function exportComprehensive($format)
    {
        if ($format === 'csv') {
            $filename = 'laporan_komprehensif_' . date('Y-m-d') . '.csv';
            
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            $output = fopen('php://output', 'w');
            
            // Write summary statistics
            fputcsv($output, ['LAPORAN KOMPREHENSIF SNIA']);
            fputcsv($output, ['Tanggal Export: ' . date('d/m/Y H:i:s')]);
            fputcsv($output, []);
            
            // User statistics
            fputcsv($output, ['STATISTIK USER']);
            fputcsv($output, ['Total User', $this->userModel->countAll()]);
            fputcsv($output, ['Admin', $this->userModel->where('role', 'admin')->countAllResults()]);
            fputcsv($output, ['Presenter', $this->userModel->where('role', 'presenter')->countAllResults()]);
            fputcsv($output, ['Audience', $this->userModel->where('role', 'audience')->countAllResults()]);
            fputcsv($output, ['Reviewer', $this->userModel->where('role', 'reviewer')->countAllResults()]);
            fputcsv($output, []);
            
            // Abstrak statistics
            fputcsv($output, ['STATISTIK ABSTRAK']);
            fputcsv($output, ['Total Abstrak', $this->abstrakModel->countAll()]);
            fputcsv($output, ['Menunggu', $this->abstrakModel->where('status', 'menunggu')->countAllResults()]);
            fputcsv($output, ['Sedang Review', $this->abstrakModel->where('status', 'sedang_direview')->countAllResults()]);
            fputcsv($output, ['Diterima', $this->abstrakModel->where('status', 'diterima')->countAllResults()]);
            fputcsv($output, ['Ditolak', $this->abstrakModel->where('status', 'ditolak')->countAllResults()]);
            fputcsv($output, []);
            
            // Pembayaran statistics
            fputcsv($output, ['STATISTIK PEMBAYARAN']);
            fputcsv($output, ['Total Pembayaran', $this->pembayaranModel->countAll()]);
            fputcsv($output, ['Pending', $this->pembayaranModel->where('status', 'pending')->countAllResults()]);
            fputcsv($output, ['Verified', $this->pembayaranModel->where('status', 'verified')->countAllResults()]);
            fputcsv($output, ['Rejected', $this->pembayaranModel->where('status', 'rejected')->countAllResults()]);
            
            $totalRevenue = $this->pembayaranModel
                               ->selectSum('jumlah')
                               ->where('status', 'verified')
                               ->first()['jumlah'] ?? 0;
            fputcsv($output, ['Total Revenue', 'Rp ' . number_format($totalRevenue, 0, ',', '.')]);
            
            fclose($output);
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
            
            // PostgreSQL compatible date range
            $startDate = $month . '-01';
            $endDate = $month . '-' . date('t', strtotime($startDate));
            
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
            
            // PostgreSQL compatible date range
            $startDate = $month . '-01';
            $endDate = $month . '-' . date('t', strtotime($startDate));
            
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