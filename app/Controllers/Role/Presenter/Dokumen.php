<?php

namespace App\Controllers\Role\Presenter;

use App\Controllers\BaseController;
use App\Models\DokumenModel;
use App\Models\EventModel;
use App\Models\PembayaranModel;
use App\Models\AbsensiModel;

class Dokumen extends BaseController
{
    protected $dokumenModel;
    protected $eventModel;
    protected $pembayaranModel;
    protected $absensiModel;
    protected $db;

    public function __construct()
    {
        $this->dokumenModel = new DokumenModel();
        $this->eventModel = new EventModel();
        $this->pembayaranModel = new PembayaranModel();
        $this->absensiModel = new AbsensiModel();
        $this->db = \Config\Database::connect();
    }

    /**
     * Display LOA (Letter of Acceptance) documents
     */
    public function loa()
    {
        $userId = session('id_user');

        try {
            // Get user's LOA documents
            $loaDocuments = $this->dokumenModel->listLoaByUserEvent($userId);
            
            // Get events where user has verified payment and can get LOA
            $eligibleEvents = $this->getEligibleEventsForLOA($userId);

            $data = [
                'loa_documents' => $loaDocuments,
                'eligible_events' => $eligibleEvents
            ];

            return view('role/presenter/dokumen/loa', $data);

        } catch (\Exception $e) {
            log_message('error', 'Presenter LOA index error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memuat halaman LOA.');
        }
    }

    /**
     * Download LOA document - FIXED VERSION
     */
    public function downloadLoa($fileName)
    {
        $userId = session('id_user');

        try {
            // FIXED: Use proper where clause syntax
            $document = $this->dokumenModel
                ->where('id_user', $userId)
                ->where('tipe', 'loa')
                ->groupStart()
                    ->where('file_path', $fileName)
                    ->orLike('file_path', $fileName)
                ->groupEnd()
                ->first();

            if (!$document) {
                return redirect()->back()
                    ->with('error', 'Dokumen LOA tidak ditemukan atau Anda tidak memiliki akses.');
            }

            // Determine full file path
            $filePath = WRITEPATH . 'uploads/loa/' . $fileName;
            
            // Alternative: if filename is stored with path
            if (!file_exists($filePath)) {
                $filePath = WRITEPATH . 'uploads/' . $document['file_path'];
            }
            
            if (!file_exists($filePath)) {
                return redirect()->back()
                    ->with('error', 'File LOA tidak ditemukan di server.');
            }

            // Log activity
            $this->logActivity($userId, "Download LOA: {$fileName}");

            return $this->response->download($filePath, null);

        } catch (\Exception $e) {
            log_message('error', 'Download LOA error: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan saat mendownload LOA.');
        }
    }

    /**
     * Display certificate documents
     */
    public function sertifikat()
    {
        $userId = session('id_user');

        try {
            // Get user's certificate documents
            $certificates = $this->dokumenModel->listSertifikatByUserEvent($userId);
            
            // Get events where user attended and can get certificate
            $eligibleEvents = $this->getEligibleEventsForCertificate($userId);

            $data = [
                'certificates' => $certificates,
                'eligible_events' => $eligibleEvents
            ];

            return view('role/presenter/dokumen/sertifikat', $data);

        } catch (\Exception $e) {
            log_message('error', 'Presenter certificate index error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memuat halaman sertifikat.');
        }
    }

    /**
     * Download certificate document - FIXED VERSION
     */
    public function downloadSertifikat($fileName)
    {
        $userId = session('id_user');

        try {
            // FIXED: Use proper where clause syntax
            $document = $this->dokumenModel
                ->where('id_user', $userId)
                ->where('tipe', 'sertifikat')
                ->groupStart()
                    ->where('file_path', $fileName)
                    ->orLike('file_path', $fileName)
                ->groupEnd()
                ->first();

            if (!$document) {
                return redirect()->back()
                    ->with('error', 'Sertifikat tidak ditemukan atau Anda tidak memiliki akses.');
            }

            // Determine full file path
            $filePath = WRITEPATH . 'uploads/sertifikat/' . $fileName;
            
            // Alternative: if filename is stored with path
            if (!file_exists($filePath)) {
                $filePath = WRITEPATH . 'uploads/' . $document['file_path'];
            }
            
            if (!file_exists($filePath)) {
                return redirect()->back()
                    ->with('error', 'File sertifikat tidak ditemukan di server.');
            }

            // Log activity
            $this->logActivity($userId, "Download sertifikat: {$fileName}");

            return $this->response->download($filePath, null);

        } catch (\Exception $e) {
            log_message('error', 'Download certificate error: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan saat mendownload sertifikat.');
        }
    }

    /**
     * Get events where user is eligible for LOA
     * (Presenter with verified payment and accepted abstract)
     */
    private function getEligibleEventsForLOA($userId)
    {
        return $this->db->query("
            SELECT DISTINCT e.*, p.verified_at as payment_verified_at, 
                   a.status as abstract_status, a.judul as abstract_title,
                   d.id_dokumen as existing_loa_id
            FROM events e
            JOIN pembayaran p ON p.event_id = e.id
            JOIN abstrak a ON a.event_id = e.id AND a.id_user = p.id_user
            LEFT JOIN dokumen d ON d.event_id = e.id AND d.id_user = p.id_user AND d.tipe = 'loa'
            WHERE p.id_user = ? 
            AND p.status = 'verified'
            AND a.status = 'diterima'
            ORDER BY e.event_date DESC
        ", [$userId])->getResultArray();
    }

    /**
     * Get events where user is eligible for certificate
     * (Presenter who attended the event)
     */
    private function getEligibleEventsForCertificate($userId)
    {
        return $this->db->query("
            SELECT DISTINCT e.*, ab.waktu_scan as attendance_time,
                   p.verified_at as payment_verified_at,
                   d.id_dokumen as existing_certificate_id
            FROM events e
            JOIN absensi ab ON ab.event_id = e.id
            JOIN pembayaran p ON p.event_id = e.id AND p.id_user = ab.id_user
            LEFT JOIN dokumen d ON d.event_id = e.id AND d.id_user = ab.id_user AND d.tipe = 'sertifikat'
            WHERE ab.id_user = ? 
            AND ab.status = 'hadir'
            AND p.status = 'verified'
            ORDER BY e.event_date DESC
        ", [$userId])->getResultArray();
    }

    /**
     * Request LOA generation (for events without LOA yet) - FIXED VERSION
     */
    public function requestLoa()
    {
        $userId = session('id_user');
        $eventId = $this->request->getPost('event_id');

        if (!$this->request->isAJAX()) {
            return redirect()->back()->with('error', 'Invalid request method.');
        }

        try {
            // Validate event
            $event = $this->eventModel->find($eventId);
            if (!$event) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Event tidak ditemukan.'
                ]);
            }

            // Check if user has verified payment
            $payment = $this->pembayaranModel
                ->where('id_user', $userId)
                ->where('event_id', $eventId)
                ->where('status', 'verified')
                ->first();

            if (!$payment) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Anda belum memiliki pembayaran yang terverifikasi untuk event ini.'
                ]);
            }

            // Check if user has accepted abstract
            $acceptedAbstract = $this->db->query("
                SELECT * FROM abstrak 
                WHERE id_user = ? AND event_id = ? AND status = 'diterima'
            ", [$userId, $eventId])->getRowArray();

            if (!$acceptedAbstract) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Anda harus memiliki abstrak yang diterima untuk mendapat LOA.'
                ]);
            }

            // Check if LOA already exists
            $existingLoa = $this->dokumenModel
                ->where('id_user', $userId)
                ->where('event_id', $eventId)
                ->where('tipe', 'loa')
                ->first();

            if ($existingLoa) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'LOA untuk event ini sudah tersedia.'
                ]);
            }

            // Create LOA request notification to admin
            $this->createLoaRequest($userId, $eventId, $event['title']);

            // Log activity
            $this->logActivity($userId, "Meminta LOA untuk event: {$event['title']}");

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Permintaan LOA berhasil disubmit. Admin akan memproses segera.'
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Request LOA error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Terjadi kesalahan saat meminta LOA.'
            ]);
        }
    }

    /**
     * Request certificate generation (for events without certificate yet) - FIXED VERSION
     */
    public function requestCertificate()
    {
        $userId = session('id_user');
        $eventId = $this->request->getPost('event_id');

        if (!$this->request->isAJAX()) {
            return redirect()->back()->with('error', 'Invalid request method.');
        }

        try {
            // Validate event
            $event = $this->eventModel->find($eventId);
            if (!$event) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Event tidak ditemukan.'
                ]);
            }

            // Check if user attended the event
            $attendance = $this->absensiModel
                ->where('id_user', $userId)
                ->where('event_id', $eventId)
                ->where('status', 'hadir')
                ->first();

            if (!$attendance) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Anda harus hadir dalam event untuk mendapat sertifikat.'
                ]);
            }

            // Check if certificate already exists
            $existingCertificate = $this->dokumenModel
                ->where('id_user', $userId)
                ->where('event_id', $eventId)
                ->where('tipe', 'sertifikat')
                ->first();

            if ($existingCertificate) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Sertifikat untuk event ini sudah tersedia.'
                ]);
            }

            // Create certificate request notification to admin
            $this->createCertificateRequest($userId, $eventId, $event['title']);

            // Log activity
            $this->logActivity($userId, "Meminta sertifikat untuk event: {$event['title']}");

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Permintaan sertifikat berhasil disubmit. Admin akan memproses segera.'
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Request certificate error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Terjadi kesalahan saat meminta sertifikat.'
            ]);
        }
    }

    /**
     * Create LOA request notification to admin
     */
    private function createLoaRequest($userId, $eventId, $eventTitle)
    {
        try {
            $user = $this->db->query("SELECT nama_lengkap FROM users WHERE id_user = ?", [$userId])->getRowArray();
            
            $notificationData = [
                'id_user' => null, // For all admins
                'role' => 'admin',
                'title' => 'Permintaan LOA Baru',
                'message' => "Presenter {$user['nama_lengkap']} meminta LOA untuk event: {$eventTitle}",
                'link' => site_url('admin/dokumen'),
                'created_at' => date('Y-m-d H:i:s')
            ];

            $this->db->table('notifikasi')->insert($notificationData);
        } catch (\Exception $e) {
            log_message('error', 'Failed to create LOA request notification: ' . $e->getMessage());
        }
    }

    /**
     * Create certificate request notification to admin
     */
    private function createCertificateRequest($userId, $eventId, $eventTitle)
    {
        try {
            $user = $this->db->query("SELECT nama_lengkap FROM users WHERE id_user = ?", [$userId])->getRowArray();
            
            $notificationData = [
                'id_user' => null, // For all admins
                'role' => 'admin',
                'title' => 'Permintaan Sertifikat Baru',
                'message' => "Presenter {$user['nama_lengkap']} meminta sertifikat untuk event: {$eventTitle}",
                'link' => site_url('admin/dokumen'),
                'created_at' => date('Y-m-d H:i:s')
            ];

            $this->db->table('notifikasi')->insert($notificationData);
        } catch (\Exception $e) {
            log_message('error', 'Failed to create certificate request notification: ' . $e->getMessage());
        }
    }

    /**
     * Log activity
     */
    private function logActivity($userId, $activity)
    {
        try {
            $this->db->table('log_aktivitas')->insert([
                'id_user' => $userId,
                'aktivitas' => $activity,
                'waktu' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Failed to log activity: ' . $e->getMessage());
        }
    }
}