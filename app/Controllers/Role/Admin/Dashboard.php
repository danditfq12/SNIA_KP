<?php

namespace App\Controllers\Role\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\AbstrakModel;
use App\Models\PembayaranModel;
use App\Models\EventModel;
use App\Models\LogAktivitasModel;

class Dashboard extends BaseController
{
    protected UserModel $userModel;
    protected AbstrakModel $abstrakModel;
    protected PembayaranModel $pembayaranModel;
    protected EventModel $eventModel;
    protected LogAktivitasModel $logModel;
    protected \CodeIgniter\Database\BaseConnection $db;

    public function __construct()
    {
        $this->userModel       = new UserModel();
        $this->abstrakModel    = new AbstrakModel();
        $this->pembayaranModel = new PembayaranModel();
        $this->eventModel      = new EventModel();
        $this->logModel        = new LogAktivitasModel();
        $this->db              = \Config\Database::connect();
    }

    public function index()
    {
        try {
            // === KPI ringkas ===
            $kpi = [
                'pembayaran_pending'       => $this->pembayaranModel->where('status', 'pending')->countAllResults(),
                'abstrak_unassigned'       => $this->countUnassignedAbstrak(),
                'fullpaper_unassigned'     => $this->countUnassignedFullPaper(),
                'pembayaran_terverifikasi' => $this->pembayaranModel->where('status', 'verified')->countAllResults(),
            ];

            $data = [
                'title' => 'Admin Dashboard',

                // KPI
                'kpi' => $kpi,

                // Panels
                'pendingPayments' => $this->getPendingPayments(6),
                'recentActivities'=> $this->getUnifiedRecentActivities(12), // feed gabungan
                'logs'            => $this->logModel->getRecentActivities(12),

                // lists penugasan
                'unassigned_abstrak'   => $this->getUnassignedAbstrak(6),
                'unassigned_fullpaper' => $this->getUnassignedFullPaper(6),
            ];

            return view('role/admin/dashboard', $data);
        } catch (\Throwable $e) {
            log_message('error', 'Dashboard index error: ' . $e->getMessage());
            return view('role/admin/dashboard', [
                'title' => 'Admin Dashboard',
                'kpi' => [
                    'pembayaran_pending' => 0,
                    'abstrak_unassigned' => 0,
                    'fullpaper_unassigned' => 0,
                    'pembayaran_terverifikasi' => 0,
                ],
                'pendingPayments' => [],
                'recentActivities'=> [],
                'logs' => [],
                'unassigned_abstrak' => [],
                'unassigned_fullpaper' => [],
            ]);
        }
    }

    /* ====================== Sections helpers ====================== */

    private function getPendingPayments(int $limit = 6): array
    {
        try {
            return $this->db->table('pembayaran p')
                ->select('p.id_pembayaran, p.jumlah, p.status, p.tanggal_bayar, p.metode,
                          u.nama_lengkap, u.email, e.title AS event_title')
                ->join('users u', 'u.id_user = p.id_user', 'left')
                ->join('events e', 'e.id = p.event_id', 'left')
                ->where('p.status', 'pending')
                ->orderBy('p.tanggal_bayar', 'DESC')
                ->limit($limit)
                ->get()->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Feed gabungan: pembayaran (baru/verified/pending), user register, abstrak submit, fullpaper submit
     * Disatukan & diurutkan terbaru (desc)
     */
    private function getUnifiedRecentActivities(int $limit = 12): array
    {
        $items = [];

        // 1) Pembayaran (verified/pending terbaru)
        try {
            $rows = $this->db->table('pembayaran p')
                ->select("p.id_pembayaran AS id, p.status, p.jumlah, p.tanggal_bayar AS happened_at,
                          u.nama_lengkap, e.title AS event_title, 'payment' AS kind")
                ->join('users u', 'u.id_user = p.id_user', 'left')
                ->join('events e', 'e.id = p.event_id', 'left')
                ->orderBy('p.tanggal_bayar', 'DESC')
                ->limit($limit)
                ->get()->getResultArray();
            foreach ($rows as $r) { $items[] = $r; }
        } catch (\Throwable $e) {}

        // 2) User Registrations
        try {
            $rows = $this->userModel
                ->select("id_user AS id, nama_lengkap, email, role, created_at AS happened_at, 'user' AS kind")
                ->orderBy('created_at', 'DESC')
                ->limit($limit)
                ->findAll();
            foreach ($rows as $r) { $items[] = $r; }
        } catch (\Throwable $e) {}

        // 3) Abstrak submit
        try {
            $rows = $this->db->table('abstrak a')
                ->select("a.id_abstrak AS id, a.judul, a.status, a.tanggal_upload AS happened_at,
                          u.nama_lengkap, 'abstract' AS kind")
                ->join('users u', 'u.id_user = a.id_user', 'left')
                ->orderBy('a.tanggal_upload', 'DESC')
                ->limit($limit)
                ->get()->getResultArray();
            foreach ($rows as $r) { $items[] = $r; }
        } catch (\Throwable $e) {}

        // 4) Full paper submit (nama tabel menyesuaikan)
        try {
            // ganti nama tabel/kolom sesuai skema Anda
            $rows = $this->db->table('full_paper f')
                ->select("f.id_fullpaper AS id, f.judul, f.status, f.tanggal_upload AS happened_at,
                          u.nama_lengkap, 'fullpaper' AS kind")
                ->join('users u', 'u.id_user = f.id_user', 'left')
                ->orderBy('f.tanggal_upload', 'DESC')
                ->limit($limit)
                ->get()->getResultArray();
            foreach ($rows as $r) { $items[] = $r; }
        } catch (\Throwable $e) {}

        // sort by happened_at desc & slice
        usort($items, function($a, $b) {
            $ta = isset($a['happened_at']) ? strtotime((string) $a['happened_at']) : 0;
            $tb = isset($b['happened_at']) ? strtotime((string) $b['happened_at']) : 0;
            return $tb <=> $ta;
        });

        return array_slice($items, 0, $limit);
    }

    /* ========== Unassigned: fallback by status jika tabel assignment tidak ada ========== */

    private function countUnassignedAbstrak(): int
    {
        try {
            // Jika ada tabel assignment: LEFT JOIN dan cek NULL
            // Ubah nama tabel/kolom assignment jika berbeda
            if ($this->db->tableExists('abstrak_reviewer')) {
                return (int) $this->db->table('abstrak a')
                    ->select('COUNT(a.id_abstrak) AS c')
                    ->join('abstrak_reviewer ar', 'ar.id_abstrak = a.id_abstrak', 'left')
                    ->where('ar.id_abstrak', null)
                    ->get()->getRow('c');
            }

            // fallback: status 'menunggu' dianggap belum ditugaskan
            return (int) $this->abstrakModel->where('status', 'menunggu')->countAllResults();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function getUnassignedAbstrak(int $limit = 6): array
    {
        try {
            if ($this->db->tableExists('abstrak_reviewer')) {
                return $this->db->table('abstrak a')
                    ->select('a.id_abstrak, a.judul, a.status, a.tanggal_upload AS created_at, u.nama_lengkap')
                    ->join('users u', 'u.id_user = a.id_user', 'left')
                    ->join('abstrak_reviewer ar', 'ar.id_abstrak = a.id_abstrak', 'left')
                    ->where('ar.id_abstrak', null)
                    ->orderBy('a.tanggal_upload', 'DESC')
                    ->limit($limit)
                    ->get()->getResultArray();
            }

            return $this->db->table('abstrak a')
                ->select('a.id_abstrak, a.judul, a.status, a.tanggal_upload AS created_at, u.nama_lengkap')
                ->join('users u', 'u.id_user = a.id_user', 'left')
                ->where('a.status', 'menunggu')
                ->orderBy('a.tanggal_upload', 'DESC')
                ->limit($limit)
                ->get()->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    private function countUnassignedFullPaper(): int
    {
        try {
            if ($this->db->tableExists('fullpaper_reviewer')) {
                return (int) $this->db->table('full_paper f')
                    ->select('COUNT(f.id_fullpaper) AS c')
                    ->join('fullpaper_reviewer fr', 'fr.id_fullpaper = f.id_fullpaper', 'left')
                    ->where('fr.id_fullpaper', null)
                    ->get()->getRow('c');
            }

            // fallback: status 'menunggu'
            return (int) $this->db->table('full_paper')->where('status', 'menunggu')->countAllResults();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function getUnassignedFullPaper(int $limit = 6): array
    {
        try {
            if ($this->db->tableExists('fullpaper_reviewer')) {
                return $this->db->table('full_paper f')
                    ->select('f.id_fullpaper, f.judul, f.status, f.tanggal_upload AS created_at, u.nama_lengkap')
                    ->join('users u', 'u.id_user = f.id_user', 'left')
                    ->join('fullpaper_reviewer fr', 'fr.id_fullpaper = f.id_fullpaper', 'left')
                    ->where('fr.id_fullpaper', null)
                    ->orderBy('f.tanggal_upload', 'DESC')
                    ->limit($limit)
                    ->get()->getResultArray();
            }

            return $this->db->table('full_paper f')
                ->select('f.id_fullpaper, f.judul, f.status, f.tanggal_upload AS created_at, u.nama_lengkap')
                ->join('users u', 'u.id_user = f.id_user', 'left')
                ->where('f.status', 'menunggu')
                ->orderBy('f.tanggal_upload', 'DESC')
                ->limit($limit)
                ->get()->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
    }
}
