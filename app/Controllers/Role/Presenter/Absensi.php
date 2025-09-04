<?php

namespace App\Controllers\Role\Presenter;

use App\Controllers\BaseController;
use App\Models\AbsensiModel;
use App\Models\PembayaranModel;
use App\Models\EventModel;
use App\Models\UserModel;

class Absensi extends BaseController
{
    protected $absensiModel;
    protected $pembayaranModel;
    protected $eventModel;
    protected $userModel;
    protected $db;

    public function __construct()
    {
        $this->absensiModel = new AbsensiModel();
        $this->pembayaranModel = new PembayaranModel();
        $this->eventModel = new EventModel();
        $this->userModel = new UserModel();
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        $userId = session('id_user');
        $userRole = session('role');
        
        // Pastikan user adalah presenter
        if ($userRole !== 'presenter') {
            return redirect()->to('dashboard')->with('error', 'Akses ditolak. Fitur ini khusus untuk presenter.');
        }

        // Check verified payment for presenter role
        $verifiedPayment = $this->pembayaranModel->where('id_user', $userId)
                                                ->where('status', 'verified')
                                                ->orderBy('verified_at', 'DESC')
                                                ->first();
        
        if (!$verifiedPayment) {
            return redirect()->to('presenter/dashboard')->with('error', 'Anda harus menyelesaikan pembayaran terlebih dahulu untuk mengakses fitur absensi');
        }

        // Get current events that user has paid for (with null safety)
        $currentEvents = [];
        try {
            $currentEvents = $this->db->table('events')
                ->select('events.*, pembayaran.id_pembayaran, pembayaran.participation_type, pembayaran.verified_at')
                ->join('pembayaran', 'pembayaran.event_id = events.id')
                ->where('pembayaran.id_user', $userId)
                ->where('pembayaran.status', 'verified')
                ->where('events.is_active', true)
                ->orderBy('events.event_date', 'ASC')
                ->get()->getResultArray();
        } catch (\Exception $e) {
            log_message('error', 'Failed to get current events: ' . $e->getMessage());
        }

        // Check today's events (with null safety)
        $todayEvents = [];
        if (!empty($currentEvents)) {
            $todayEvents = array_filter($currentEvents, function($event) {
                return isset($event['event_date']) && date('Y-m-d') === $event['event_date'];
            });
        }

        // Get attendance history (with null safety)
        $attendanceHistory = [];
        try {
            $attendanceHistory = $this->absensiModel->getUserAttendanceHistory($userId);
        } catch (\Exception $e) {
            log_message('error', 'Failed to get attendance history: ' . $e->getMessage());
        }

        // Check attendance status for today's events (with null safety)
        $todayAttendance = [];
        if (!empty($todayEvents)) {
            foreach ($todayEvents as $event) {
                try {
                    $attendance = $this->absensiModel
                        ->where('id_user', $userId)
                        ->where('event_id', $event['id'])
                        ->where('DATE(waktu_scan)', date('Y-m-d'))
                        ->first();
                    
                    $todayAttendance[$event['id']] = $attendance;
                } catch (\Exception $e) {
                    log_message('error', 'Failed to check attendance for event ' . $event['id'] . ': ' . $e->getMessage());
                    $todayAttendance[$event['id']] = null;
                }
            }
        }

        $data = [
            'currentEvents' => $currentEvents,
            'todayEvents' => $todayEvents,
            'attendanceHistory' => $attendanceHistory,
            'todayAttendance' => $todayAttendance,
            'hasVerifiedPayment' => true,
            'userRole' => $userRole
        ];

        return view('role/presenter/absensi/index', $data);
    }

    public function scan()
    {
        // AJAX only
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON([
                'success' => false,
                'message' => 'Invalid request method'
            ]);
        }

        $userId = session('id_user');
        $userRole = session('role');
        
        // Role validation
        if ($userRole !== 'presenter') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Akses ditolak. Fitur scan QR khusus untuk presenter.'
            ]);
        }

        // Check verified payment
        $verifiedPayment = $this->pembayaranModel->where('id_user', $userId)
                                                ->where('status', 'verified')
                                                ->first();
        
        if (!$verifiedPayment) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Pembayaran belum terverifikasi. Silakan selesaikan pembayaran terlebih dahulu.'
            ]);
        }

        $qrCode = $this->request->getPost('qr_code');
        
        if (!$qrCode) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'QR Code tidak valid atau kosong'
            ]);
        }

        // Enhanced QR validation with presenter-specific logic
        $qrValidation = $this->validatePresenterQR($qrCode, $userId);
        
        if (!$qrValidation['valid']) {
            return $this->response->setJSON([
                'success' => false,
                'message' => $qrValidation['message']
            ]);
        }

        $eventId = $qrValidation['event_id'];
        $event = $this->eventModel->find($eventId);

        if (!$event || !$event['is_active']) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Event tidak ditemukan atau sudah tidak aktif'
            ]);
        }

        // Check if user has verified payment for this specific event
        $eventPayment = $this->pembayaranModel
            ->where('id_user', $userId)
            ->where('event_id', $eventId)
            ->where('status', 'verified')
            ->first();

        if (!$eventPayment) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Anda belum memiliki pembayaran terverifikasi untuk event ini'
            ]);
        }

        // Check event timing
        $eventStatus = $this->checkEventTiming($event);
        if (!$eventStatus['can_attend']) {
            return $this->response->setJSON([
                'success' => false,
                'message' => $eventStatus['message']
            ]);
        }

        // Check if already attended
        $existingAttendance = $this->absensiModel
            ->where('id_user', $userId)
            ->where('event_id', $eventId)
            ->first();
        
        if ($existingAttendance) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Anda sudah melakukan absensi untuk event ini pada ' . 
                           date('d/m/Y H:i:s', strtotime($existingAttendance['waktu_scan']))
            ]);
        }

        // Record attendance
        $attendanceData = [
            'id_user' => $userId,
            'event_id' => $eventId,
            'qr_code' => $qrCode,
            'status' => 'hadir',
            'waktu_scan' => date('Y-m-d H:i:s'),
            'marked_by_admin' => null,
            'notes' => 'Presenter QR scan - ' . $eventPayment['participation_type']
        ];

        $this->db->transStart();
        
        try {
            $attendanceId = $this->absensiModel->insert($attendanceData);
            
            if (!$attendanceId) {
                throw new \Exception('Gagal menyimpan data absensi');
            }

            // Log activity
            $this->logActivity($userId, "Attended event as presenter: {$event['title']}");
            
            $this->db->transComplete();

            if ($this->db->transStatus() === FALSE) {
                throw new \Exception('Transaction failed');
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Absensi presenter berhasil dicatat!',
                'data' => [
                    'event_title' => $event['title'],
                    'attendance_time' => date('H:i:s'),
                    'attendance_date' => date('d/m/Y'),
                    'participation_type' => $eventPayment['participation_type'],
                    'role' => 'Presenter'
                ]
            ]);

        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Presenter attendance error: ' . $e->getMessage());
            
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memproses absensi: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Validate QR code specifically for presenter role
     */
    private function validatePresenterQR($qrCode, $userId)
    {
        // Clean QR code
        $qrCode = trim($qrCode);
        
        // Pattern 1: Standard format EVENT_{event_id}_{role}_{participation_type}_{date}_{hash}
        $standardPattern = '/^EVENT_(\d+)_([a-z]+)_([a-z]+)_(\d{8})_([a-f0-9]+)$/i';
        if (preg_match($standardPattern, $qrCode, $matches)) {
            $eventId = (int) $matches[1];
            $role = strtolower($matches[2]);
            $participationType = strtolower($matches[3]);
            $date = $matches[4];
            
            // Check if QR is for presenter or universal
            if ($role !== 'presenter' && $role !== 'all') {
                return [
                    'valid' => false,
                    'message' => "QR Code ini khusus untuk role {$role}. Anda adalah presenter."
                ];
            }
            
            return [
                'valid' => true,
                'event_id' => $eventId,
                'role' => $role,
                'participation_type' => $participationType,
                'date' => $date
            ];
        }
        
        // Pattern 2: Simple format EVENT_{event_id}_{date} (universal)
        $simplePattern = '/^EVENT_(\d+)_(\d{8})$/i';
        if (preg_match($simplePattern, $qrCode, $matches)) {
            $eventId = (int) $matches[1];
            $date = $matches[2];
            
            return [
                'valid' => true,
                'event_id' => $eventId,
                'role' => 'all',
                'participation_type' => 'all',
                'date' => $date
            ];
        }
        
        // Pattern 3: Just event ID (legacy support)
        if (is_numeric($qrCode)) {
            $eventId = (int) $qrCode;
            
            if ($eventId > 0) {
                return [
                    'valid' => true,
                    'event_id' => $eventId,
                    'role' => 'all',
                    'participation_type' => 'all',
                    'date' => date('Ymd')
                ];
            }
        }
        
        // Pattern 4: Extract from URL
        if (preg_match('/EVENT_(\d+)/i', $qrCode, $matches)) {
            $eventId = (int) $matches[1];
            
            return [
                'valid' => true,
                'event_id' => $eventId,
                'role' => 'all',
                'participation_type' => 'all',
                'date' => date('Ymd')
            ];
        }
        
        return [
            'valid' => false,
            'message' => 'Format QR Code tidak dikenali. Pastikan menggunakan QR code yang valid.'
        ];
    }

    /**
     * Check if event timing allows attendance
     */
    private function checkEventTiming($event)
    {
        $eventStart = strtotime($event['event_date'] . ' ' . $event['event_time']);
        $eventEnd = $eventStart + (8 * 3600); // 8 hours duration
        $currentTime = time();
        
        // Allow early access (1 hour before) and late access (2 hours after event end)
        $earlyAccess = 3600; // 1 hour
        $lateAccess = 7200; // 2 hours
        
        if (ENVIRONMENT === 'development') {
            // More flexible timing for development
            $earlyAccess = 24 * 3600; // 24 hours before
            $lateAccess = 24 * 3600; // 24 hours after
        }
        
        if ($currentTime < ($eventStart - $earlyAccess)) {
            $timeUntilOpen = ($eventStart - $earlyAccess) - $currentTime;
            $hoursRemaining = floor($timeUntilOpen / 3600);
            $minutesRemaining = floor(($timeUntilOpen % 3600) / 60);
            
            return [
                'can_attend' => false,
                'message' => "Event belum dapat diakses. Absensi akan dibuka pada " . 
                            date('d/m/Y H:i', $eventStart - $earlyAccess) . 
                            " ({$hoursRemaining} jam {$minutesRemaining} menit lagi)"
            ];
        } 
        elseif ($currentTime > ($eventEnd + $lateAccess)) {
            return [
                'can_attend' => false,
                'message' => "Periode absensi sudah berakhir pada " . 
                            date('d/m/Y H:i', $eventEnd + $lateAccess)
            ];
        } 
        else {
            return [
                'can_attend' => true,
                'message' => 'Event dapat diakses untuk absensi'
            ];
        }
    }

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