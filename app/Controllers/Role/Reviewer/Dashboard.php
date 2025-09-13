<?php

namespace App\Controllers\Role\Reviewer;

use App\Controllers\BaseController;
use App\Models\AbstrakModel;
use App\Models\ReviewModel;

class Dashboard extends BaseController
{
    protected $abstrakModel;
    protected $reviewModel;
    protected $db;

    public function __construct()
    {
        $this->abstrakModel = new AbstrakModel();
        $this->reviewModel  = new ReviewModel();
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        $idReviewer = session('id_user');

        if (!$idReviewer) {
            return redirect()->to('auth/login')->with('error', 'Session expired');
        }

        try {
            // Statistik dengan query yang benar
            $assigned = $this->reviewModel
                ->where('id_reviewer', $idReviewer)
                ->countAllResults();

            $pending = $this->reviewModel
                ->where('id_reviewer', $idReviewer)
                ->where('keputusan', 'pending')
                ->countAllResults();

            $reviewed = $this->reviewModel
                ->where('id_reviewer', $idReviewer)
                ->whereIn('keputusan', ['diterima', 'ditolak', 'revisi'])
                ->countAllResults();

            // Due today - perlu tambah field deadline di tabel review jika ingin akurat
            $dueToday = $this->reviewModel
                ->where('id_reviewer', $idReviewer)
                ->where('keputusan', 'pending')
                ->where('DATE(tanggal_review)', date('Y-m-d'))
                ->countAllResults();

            $stat = [
                'assigned'  => $assigned,
                'pending'   => $pending,
                'reviewed'  => $reviewed,
                'due_today' => $dueToday
            ];

            // Ambil tugas terbaru dengan JOIN yang benar
            $recent = $this->db->query("
                SELECT 
                    a.id_abstrak,
                    a.judul,
                    a.status as abstrak_status,
                    u.nama_lengkap,
                    k.nama_kategori,
                    r.keputusan as review_status,
                    r.tanggal_review,
                    a.tanggal_upload
                FROM review r
                JOIN abstrak a ON a.id_abstrak = r.id_abstrak
                JOIN users u ON u.id_user = a.id_user
                JOIN kategori_abstrak k ON k.id_kategori = a.id_kategori
                WHERE r.id_reviewer = ?
                ORDER BY 
                    CASE WHEN r.keputusan = 'pending' THEN 1 ELSE 2 END,
                    r.tanggal_review DESC,
                    a.tanggal_upload DESC
                LIMIT 5
            ", [$idReviewer])->getResultArray();

            // Format data untuk view
            foreach ($recent as &$item) {
                // Prioritas: review_status, fallback ke abstrak_status
                $item['status'] = $item['review_status'] ?? $item['abstrak_status'] ?? 'menunggu';
            }
            unset($item);

            // Notifikasi - bisa dari tabel notifikasi jika ada
            $notifs = $this->getReviewerNotifications($idReviewer);

            return view('role/reviewer/dashboard', [
                'title'  => 'Reviewer Dashboard',
                'stat'   => $stat,
                'recent' => $recent,
                'notifs' => $notifs,
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Reviewer Dashboard Error: ' . $e->getMessage());
            
            // Return dengan data default jika error
            return view('role/reviewer/dashboard', [
                'title'  => 'Reviewer Dashboard',
                'stat'   => ['assigned' => 0, 'pending' => 0, 'reviewed' => 0, 'due_today' => 0],
                'recent' => [],
                'notifs' => [],
            ]);
        }
    }

    private function getReviewerNotifications($reviewerId)
    {
        // Jika ada tabel notifikasi
        try {
            if ($this->db->tableExists('notifikasi')) {
                return $this->db->query("
                    SELECT 
                        title,
                        message,
                        link,
                        created_at as time
                    FROM notifikasi 
                    WHERE id_user = ? OR role = 'reviewer'
                    ORDER BY created_at DESC 
                    LIMIT 5
                ", [$reviewerId])->getResultArray();
            }
        } catch (\Exception $e) {
            log_message('warning', 'Failed to get notifications: ' . $e->getMessage());
        }

        // Fallback: buat notifikasi dummy dari pending reviews
        $pendingCount = $this->reviewModel
            ->where('id_reviewer', $reviewerId)
            ->where('keputusan', 'pending')
            ->countAllResults();

        if ($pendingCount > 0) {
            return [
                [
                    'title' => "Anda memiliki {$pendingCount} tugas review pending",
                    'time' => 'Hari ini',
                    'link' => site_url('reviewer/abstrak')
                ]
            ];
        }

        return [];
    }
}