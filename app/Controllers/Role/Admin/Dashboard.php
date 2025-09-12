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
        $this->userModel      = new UserModel();
        $this->abstrakModel   = new AbstrakModel();
        $this->pembayaranModel= new PembayaranModel();
        $this->eventModel     = new EventModel();
        $this->db             = \Config\Database::connect();
    }

    public function index()
    {
        try {
            $data = [
                // === KPI untuk view ===
                'pembayaran_pending' => $this->pembayaranModel->where('status', 'pending')->countAllResults(),
                'abstrak_masuk'      => $this->abstrakModel->countAll(),
                // anggap "belum ditugaskan" = status menunggu (tanpa info tabel assignment)
                'abstrak_unassigned' => $this->abstrakModel->where('status', 'menunggu')->countAllResults(),
                'total_event'        => $this->eventModel->countAll(),

                // === Ringkasan / list untuk cards ===
                'pendingPayments' => $this->getPendingPayments(), // << tampil di dashboard
                'recent_abstrak'  => $this->getRecentAbstraks(),
                'recent_events'   => $this->getRecentEvents(),
            ];

            return view('role/admin/dashboard', $data);

        } catch (\Exception $e) {
            log_message('error', 'Admin dashboard error: ' . $e->getMessage());
            return view('role/admin/dashboard', $this->getDefaultData());
        }
    }

    /**
     * List pembayaran yang MASIH pending (limit 5) + info user & event
     * field yang dipakai view: id_pembayaran, nama_lengkap, event_title, jumlah, tanggal_bayar
     */
    private function getPendingPayments(): array
    {
        try {
            return $this->db->table('pembayaran p')
                ->select('p.id_pembayaran, p.jumlah, p.status, p.tanggal_bayar, u.nama_lengkap, u.email, e.title AS event_title')
                ->join('users u', 'u.id_user = p.id_user', 'left')
                ->join('events e', 'e.id = p.event_id', 'left')
                ->where('p.status', 'pending')
                ->orderBy('p.tanggal_bayar', 'DESC')
                ->limit(5)
                ->get()->getResultArray();
        } catch (\Throwable $e) {
            log_message('error', 'Error getting pending payments: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Abstrak terbaru + alias-kan tanggal ke created_at supaya match view
     */
    private function getRecentAbstraks(): array
    {
        try {
            return $this->db->query("
                SELECT 
                    a.id_abstrak,
                    a.judul,
                    a.status,
                    a.tanggal_upload AS created_at, -- alias untuk view
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
     * Event terbaru
     */
    private function getRecentEvents(): array
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
     * Data default bila error
     */
    private function getDefaultData(): array
    {
        return [
            'pembayaran_pending' => 0,
            'abstrak_masuk'      => 0,
            'abstrak_unassigned' => 0,
            'total_event'        => 0,
            'pendingPayments'    => [],
            'recent_abstrak'     => [],
            'recent_events'      => [],
        ];
    }

    /**
     * Endpoint ringkasan statistik (opsional untuk AJAX)
     */
    public function getStats()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400);
        }

        try {
            $stats = [
                'users' => [
                    'total'     => $this->userModel->countAll(),
                    'active'    => $this->userModel->where('status', 'aktif')->countAllResults(),
                    'presenters'=> $this->userModel->where('role', 'presenter')->countAllResults(),
                    'reviewers' => $this->userModel->where('role', 'reviewer')->countAllResults(),
                    'audience'  => $this->userModel->where('role', 'audience')->countAllResults()
                ],
                'abstracts' => [
                    'total'    => $this->abstrakModel->countAll(),
                    'pending'  => $this->abstrakModel->where('status', 'menunggu')->countAllResults(),
                    'accepted' => $this->abstrakModel->where('status', 'diterima')->countAllResults(),
                    'rejected' => $this->abstrakModel->where('status', 'ditolak')->countAllResults(),
                    'revision' => $this->abstrakModel->where('status', 'revisi')->countAllResults()
                ],
                'payments' => [
                    'total'    => $this->pembayaranModel->countAll(),
                    'pending'  => $this->pembayaranModel->where('status', 'pending')->countAllResults(),
                    'verified' => $this->pembayaranModel->where('status', 'verified')->countAllResults(),
                    'rejected' => $this->pembayaranModel->where('status', 'rejected')->countAllResults()
                ],
                'events' => [
                    'total'    => $this->eventModel->countAll(),
                    'active'   => $this->eventModel->where('is_active', true)->countAllResults(),
                    'upcoming' => $this->eventModel->where('event_date >=', date('Y-m-d'))->countAllResults()
                ]
            ];

            return $this->response->setJSON([
                'success'   => true,
                'stats'     => $stats,
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
     * Data chart (opsional)
     */
    public function getChartData()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400);
        }

        try {
            $abstractStats = [
                'menunggu' => $this->abstrakModel->where('status', 'menunggu')->countAllResults(),
                'diterima' => $this->abstrakModel->where('status', 'diterima')->countAllResults(),
                'ditolak'  => $this->abstrakModel->where('status', 'ditolak')->countAllResults(),
                'revisi'   => $this->abstrakModel->where('status', 'revisi')->countAllResults()
            ];

            $monthlyData = [];
            for ($i = 5; $i >= 0; $i--) {
                $month     = date('Y-m', strtotime("-$i months"));
                $monthName = date('M Y', strtotime("-$i months"));

                $monthlyData[] = [
                    'month'    => $monthName,
                    'users'    => $this->userModel->like('created_at', $month)->countAllResults(),
                    'abstracts'=> $this->abstrakModel->like('tanggal_upload', $month)->countAllResults()
                ];
            }

            $paymentMethods = $this->db->query("
                SELECT metode, COUNT(*) AS count
                FROM pembayaran
                WHERE status = 'verified'
                GROUP BY metode
            ")->getResultArray();

            return $this->response->setJSON([
                'success' => true,
                'data' => [
                    'abstract_stats'  => $abstractStats,
                    'monthly_trends'  => $monthlyData,
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
