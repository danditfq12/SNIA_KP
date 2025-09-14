<?php

namespace App\Controllers\Role\Reviewer;

use App\Controllers\BaseController;
use App\Models\AbstrakModel;
use App\Models\ReviewModel;
use App\Models\NotificationModel;

class Dashboard extends BaseController
{
    protected $abstrakModel;
    protected $reviewModel;
    protected $notificationModel;
    protected $db;

    public function __construct()
    {
        $this->abstrakModel = new AbstrakModel();
        $this->reviewModel  = new ReviewModel();
        $this->notificationModel = new NotificationModel();
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

            // Due today - hitung berdasarkan assignment date, bukan review date
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
                LEFT JOIN kategori_abstrak k ON k.id_kategori = a.id_kategori
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

            // FIXED: Ambil notifikasi yang lebih lengkap
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
                'notifs' => $this->getDefaultNotifications($idReviewer),
            ]);
        }
    }

    private function getReviewerNotifications($reviewerId)
    {
        $notifications = [];

        // 1. Coba ambil dari tabel notifikasi
        try {
            if ($this->db->tableExists('notifikasi')) {
                $dbNotifs = $this->db->query("
                    SELECT 
                        title,
                        message,
                        link,
                        type,
                        created_at,
                        read
                    FROM notifikasi 
                    WHERE (id_user = ? OR role = 'reviewer' OR role IS NULL)
                    ORDER BY created_at DESC 
                    LIMIT 5
                ", [$reviewerId])->getResultArray();

                // Format notifikasi dari database
                foreach ($dbNotifs as $notif) {
                    $timeAgo = $this->timeAgo($notif['created_at']);
                    $notifications[] = [
                        'title' => $notif['title'],
                        'message' => $notif['message'] ?? '',
                        'time' => $timeAgo,
                        'link' => $notif['link'] ?? '',
                        'type' => $notif['type'] ?? 'info',
                        'read' => $notif['read'] ?? false
                    ];
                }
            }
        } catch (\Exception $e) {
            log_message('warning', 'Failed to get notifications: ' . $e->getMessage());
        }

        // 2. Jika tidak ada notifikasi dari DB, buat notifikasi berdasarkan pending reviews
        if (empty($notifications)) {
            $notifications = $this->generateContextualNotifications($reviewerId);
        }

        // 3. Fallback jika masih kosong
        if (empty($notifications)) {
            $notifications = $this->getDefaultNotifications($reviewerId);
        }

        return $notifications;
    }

    private function generateContextualNotifications($reviewerId)
    {
        $notifications = [];

        try {
            // Hitung pending reviews
            $pendingCount = $this->reviewModel
                ->where('id_reviewer', $reviewerId)
                ->where('keputusan', 'pending')
                ->countAllResults();

            if ($pendingCount > 0) {
                $notifications[] = [
                    'title' => "Anda memiliki {$pendingCount} tugas review yang belum selesai",
                    'message' => 'Klik untuk melihat daftar abstrak yang perlu direview',
                    'time' => 'Hari ini',
                    'link' => site_url('reviewer/abstrak'),
                    'type' => 'pending',
                    'read' => false
                ];
            }

            // Cek review yang baru selesai hari ini
            $reviewedToday = $this->reviewModel
                ->where('id_reviewer', $reviewerId)
                ->where('DATE(tanggal_review)', date('Y-m-d'))
                ->whereIn('keputusan', ['diterima', 'ditolak', 'revisi'])
                ->countAllResults();

            if ($reviewedToday > 0) {
                $notifications[] = [
                    'title' => "Anda telah menyelesaikan {$reviewedToday} review hari ini",
                    'message' => 'Terima kasih atas kontribusi Anda sebagai reviewer',
                    'time' => 'Hari ini',
                    'link' => site_url('reviewer/riwayat'),
                    'type' => 'success',
                    'read' => false
                ];
            }

            // Cek abstrak baru yang ditugaskan minggu ini
            $newThisWeek = $this->db->query("
                SELECT COUNT(*) as count
                FROM review r
                WHERE r.id_reviewer = ? 
                AND DATE(r.tanggal_review) >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
                AND r.keputusan = 'pending'
            ", [$reviewerId])->getRowArray();

            if ($newThisWeek && $newThisWeek['count'] > 0) {
                $count = $newThisWeek['count'];
                $notifications[] = [
                    'title' => "{$count} tugas review baru minggu ini",
                    'message' => 'Ada abstrak baru yang perlu direview',
                    'time' => 'Minggu ini',
                    'link' => site_url('reviewer/abstrak'),
                    'type' => 'info',
                    'read' => false
                ];
            }

        } catch (\Exception $e) {
            log_message('error', 'Error generating contextual notifications: ' . $e->getMessage());
        }

        return $notifications;
    }

    private function getDefaultNotifications($reviewerId)
    {
        return [
            [
                'title' => 'Selamat datang di dashboard reviewer',
                'message' => 'Anda dapat melihat tugas review abstrak di sini',
                'time' => 'Hari ini',
                'link' => site_url('reviewer/abstrak'),
                'type' => 'welcome',
                'read' => false
            ],
            [
                'title' => 'Panduan Review',
                'message' => 'Pastikan memberikan feedback yang konstruktif untuk setiap abstrak',
                'time' => 'Info',
                'link' => '',
                'type' => 'info',
                'read' => false
            ]
        ];
    }

    private function timeAgo($datetime)
    {
        if (!$datetime) return 'Tidak diketahui';

        $time = time() - strtotime($datetime);

        if ($time < 60) return 'Baru saja';
        if ($time < 3600) return floor($time/60) . ' menit lalu';
        if ($time < 86400) return floor($time/3600) . ' jam lalu';
        if ($time < 2592000) return floor($time/86400) . ' hari lalu';
        if ($time < 31536000) return floor($time/2592000) . ' bulan lalu';
        
        return floor($time/31536000) . ' tahun lalu';
    }

    /**
     * AJAX endpoint untuk mendapatkan notifikasi terbaru
     */
    public function getNotifications()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid request']);
        }

        $reviewerId = session('id_user');
        if (!$reviewerId) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthorized']);
        }

        $notifications = $this->getReviewerNotifications($reviewerId);
        
        return $this->response->setJSON([
            'success' => true,
            'notifications' => $notifications
        ]);
    }

    /**
     * AJAX endpoint untuk menandai notifikasi sebagai sudah dibaca
     */
    public function markNotificationRead()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Invalid request']);
        }

        $reviewerId = session('id_user');
        if (!$reviewerId) {
            return $this->response->setStatusCode(401)->setJSON(['error' => 'Unauthorized']);
        }

        $notificationId = $this->request->getPost('notification_id');
        
        try {
            if ($this->db->tableExists('notifikasi') && $notificationId) {
                $this->db->query("
                    UPDATE notifikasi 
                    SET read = 1, read_at = NOW(), updated_at = NOW()
                    WHERE id_notif = ? AND id_user = ?
                ", [$notificationId, $reviewerId]);
            }
            
            return $this->response->setJSON(['success' => true]);
        } catch (\Exception $e) {
            log_message('error', 'Error marking notification as read: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'error' => $e->getMessage()]);
        }
    }
}