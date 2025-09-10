<?php

namespace App\Controllers\Role\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\AbstrakModel;
use App\Models\PembayaranModel;
use App\Models\EventModel;

class Dashboard extends BaseController
{
    protected $userModel;
    protected $abstrakModel;
    protected $pembayaranModel;
    protected $eventModel;
    protected $db;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->abstrakModel = new AbstrakModel();
        $this->pembayaranModel = new PembayaranModel();
        $this->eventModel = new EventModel();
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        try {
            // Get basic statistics
            $data = [
                // User statistics
                'total_users' => $this->userModel->countAll(),
                'total_reviewer' => $this->userModel->where('role', 'reviewer')->countAllResults(),
                
                // Abstract statistics
                'total_abstrak' => $this->abstrakModel->countAll(),
                'abstrak_pending' => $this->abstrakModel->where('status', 'menunggu')->countAllResults(),
                'abstrak_diterima' => $this->abstrakModel->where('status', 'diterima')->countAllResults(),
                'abstrak_ditolak' => $this->abstrakModel->where('status', 'ditolak')->countAllResults(),
                
                // Payment statistics
                'pembayaran_pending' => $this->pembayaranModel->where('status', 'pending')->countAllResults(),
                'total_pembayaran' => $this->pembayaranModel->countAll(),
                
                // Event statistics
                'total_events' => $this->eventModel->countAll(),
                'active_events' => $this->eventModel->where('is_active', true)->countAllResults(),
                
                // Recent data
                'recent_users' => $this->getRecentUsers(),
                'recent_abstrak' => $this->getRecentAbstraks(),
                'recent_events' => $this->getRecentEvents(), // FIX: Added this missing data
                'recent_payments' => $this->getRecentPayments()
            ];

            return view('role/admin/dashboard', $data);

        } catch (\Exception $e) {
            log_message('error', 'Admin dashboard error: ' . $e->getMessage());
            return view('role/admin/dashboard', $this->getDefaultData());
        }
    }

    /**
     * Get recent users (last 5)
     */
    private function getRecentUsers()
    {
        try {
            return $this->userModel
                ->select('id_user, nama_lengkap, email, role, created_at')
                ->orderBy('created_at', 'DESC')
                ->limit(5)
                ->findAll();
        } catch (\Exception $e) {
            log_message('error', 'Error getting recent users: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get recent abstracts with user info
     */
    private function getRecentAbstraks()
    {
        try {
            return $this->db->query("
                SELECT 
                    a.id_abstrak,
                    a.judul,
                    a.status,
                    a.tanggal_upload,
                    u.nama_lengkap,
                    u.email
                FROM abstrak a
                LEFT JOIN users u ON u.id_user = a.id_user
                ORDER BY a.tanggal_upload DESC
                LIMIT 5
            ")->getResultArray();
        } catch (\Exception $e) {
            log_message('error', 'Error getting recent abstracts: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get recent events (FIX: Added this missing method)
     */
    private function getRecentEvents()
    {
        try {
            return $this->eventModel
                ->select('id, title, event_date, event_time, format, is_active, created_at')
                ->orderBy('created_at', 'DESC')
                ->limit(5)
                ->findAll();
        } catch (\Exception $e) {
            log_message('error', 'Error getting recent events: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get recent payments with user info
     */
    private function getRecentPayments()
    {
        try {
            return $this->db->query("
                SELECT 
                    p.id_pembayaran,
                    p.jumlah,
                    p.status,
                    p.tanggal_bayar,
                    u.nama_lengkap,
                    u.email,
                    e.title as event_title
                FROM pembayaran p
                LEFT JOIN users u ON u.id_user = p.id_user
                LEFT JOIN events e ON e.id = p.event_id
                ORDER BY p.tanggal_bayar DESC
                LIMIT 5
            ")->getResultArray();
        } catch (\Exception $e) {
            log_message('error', 'Error getting recent payments: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get default data when there's an error
     */
    private function getDefaultData()
    {
        return [
            'total_users' => 0,
            'total_reviewer' => 0,
            'total_abstrak' => 0,
            'abstrak_pending' => 0,
            'abstrak_diterima' => 0,
            'abstrak_ditolak' => 0,
            'pembayaran_pending' => 0,
            'total_pembayaran' => 0,
            'total_events' => 0,
            'active_events' => 0,
            'recent_users' => [],
            'recent_abstrak' => [],
            'recent_events' => [],
            'recent_payments' => []
        ];
    }

    /**
     * AJAX endpoint to get dashboard statistics
     */
    public function getStats()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400);
        }

        try {
            $stats = [
                'users' => [
                    'total' => $this->userModel->countAll(),
                    'active' => $this->userModel->where('status', 'aktif')->countAllResults(),
                    'presenters' => $this->userModel->where('role', 'presenter')->countAllResults(),
                    'reviewers' => $this->userModel->where('role', 'reviewer')->countAllResults(),
                    'audience' => $this->userModel->where('role', 'audience')->countAllResults()
                ],
                'abstracts' => [
                    'total' => $this->abstrakModel->countAll(),
                    'pending' => $this->abstrakModel->where('status', 'menunggu')->countAllResults(),
                    'accepted' => $this->abstrakModel->where('status', 'diterima')->countAllResults(),
                    'rejected' => $this->abstrakModel->where('status', 'ditolak')->countAllResults(),
                    'revision' => $this->abstrakModel->where('status', 'revisi')->countAllResults()
                ],
                'payments' => [
                    'total' => $this->pembayaranModel->countAll(),
                    'pending' => $this->pembayaranModel->where('status', 'pending')->countAllResults(),
                    'verified' => $this->pembayaranModel->where('status', 'verified')->countAllResults(),
                    'rejected' => $this->pembayaranModel->where('status', 'rejected')->countAllResults()
                ],
                'events' => [
                    'total' => $this->eventModel->countAll(),
                    'active' => $this->eventModel->where('is_active', true)->countAllResults(),
                    'upcoming' => $this->eventModel->where('event_date >=', date('Y-m-d'))->countAllResults()
                ]
            ];

            return $this->response->setJSON([
                'success' => true,
                'stats' => $stats,
                'timestamp' => time()
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error getting dashboard stats: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error getting statistics'
            ]);
        }
    }

    /**
     * Get chart data for dashboard
     */
    public function getChartData()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400);
        }

        try {
            // Abstract status distribution
            $abstractStats = [
                'menunggu' => $this->abstrakModel->where('status', 'menunggu')->countAllResults(),
                'diterima' => $this->abstrakModel->where('status', 'diterima')->countAllResults(),
                'ditolak' => $this->abstrakModel->where('status', 'ditolak')->countAllResults(),
                'revisi' => $this->abstrakModel->where('status', 'revisi')->countAllResults()
            ];

            // Monthly registration trends (last 6 months)
            $monthlyData = [];
            for ($i = 5; $i >= 0; $i--) {
                $month = date('Y-m', strtotime("-$i months"));
                $monthName = date('M Y', strtotime("-$i months"));
                
                $monthlyData[] = [
                    'month' => $monthName,
                    'users' => $this->userModel->like('created_at', $month)->countAllResults(),
                    'abstracts' => $this->abstrakModel->like('tanggal_upload', $month)->countAllResults()
                ];
            }

            // Payment methods distribution
            $paymentMethods = $this->db->query("
                SELECT metode, COUNT(*) as count 
                FROM pembayaran 
                WHERE status = 'verified'
                GROUP BY metode
            ")->getResultArray();

            return $this->response->setJSON([
                'success' => true,
                'data' => [
                    'abstract_stats' => $abstractStats,
                    'monthly_trends' => $monthlyData,
                    'payment_methods' => $paymentMethods
                ]
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error getting chart data: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error getting chart data'
            ]);
        }
    }
}