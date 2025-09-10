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

        // Check event timing - IMPROVED VERSION
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
     * Validate QR code specifically for presenter role - STRICT SECURITY
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
            
            // STRICT: Only allow presenter-specific QR codes or universal
            if ($role === 'presenter') {
                // Perfect match - presenter QR for presenter user
                return [
                    'valid' => true,
                    'event_id' => $eventId,
                    'role' => $role,
                    'participation_type' => $participationType,
                    'date' => $date
                ];
            } elseif ($role === 'all' || $role === 'universal') {
                // Universal QR allowed for all roles
                return [
                    'valid' => true,
                    'event_id' => $eventId,
                    'role' => 'universal',
                    'participation_type' => $participationType,
                    'date' => $date
                ];
            } else {
                // REJECTED: Role-specific QR for different role
                $roleDisplayNames = [
                    'audience' => 'Audience',
                    'reviewer' => 'Reviewer',
                    'admin' => 'Admin'
                ];
                $roleName = $roleDisplayNames[$role] ?? ucfirst($role);
                
                return [
                    'valid' => false,
                    'message' => "QR Code ini khusus untuk {$roleName}. Anda adalah Presenter dan tidak dapat menggunakan QR code ini."
                ];
            }
        }
        
        // Pattern 2: Simple format EVENT_{event_id}_{date} (universal only)
        $simplePattern = '/^EVENT_(\d+)_(\d{8})$/i';
        if (preg_match($simplePattern, $qrCode, $matches)) {
            $eventId = (int) $matches[1];
            $date = $matches[2];
            
            // Simple format considered universal
            return [
                'valid' => true,
                'event_id' => $eventId,
                'role' => 'universal',
                'participation_type' => 'all',
                'date' => $date
            ];
        }
        
        // Pattern 3: Presenter-specific format EVENT_{event_id}_PRESENTER_{hash}
        $presenterPattern = '/^EVENT_(\d+)_PRESENTER_([a-f0-9]+)$/i';
        if (preg_match($presenterPattern, $qrCode, $matches)) {
            $eventId = (int) $matches[1];
            $hash = $matches[2];
            
            return [
                'valid' => true,
                'event_id' => $eventId,
                'role' => 'presenter',
                'participation_type' => 'offline',
                'date' => date('Ymd')
            ];
        }
        
        // Pattern 4: Check for audience-specific patterns and reject them
        $audiencePatterns = [
            '/^EVENT_(\d+)_AUDIENCE_(online|offline)_(\d{8})_([a-f0-9]+)$/i',
            '/^EVENT_(\d+)_audience_(online|offline)_(\d{8})_([a-f0-9]+)$/i'
        ];
        
        foreach ($audiencePatterns as $pattern) {
            if (preg_match($pattern, $qrCode, $matches)) {
                $participationType = $matches[2];
                return [
                    'valid' => false,
                    'message' => "QR Code ini khusus untuk Audience ({$participationType}). Presenter tidak dapat menggunakan QR code Audience."
                ];
            }
        }
        
        // Pattern 5: Legacy numeric ID (only if no specific role found)
        if (is_numeric($qrCode)) {
            $eventId = (int) $qrCode;
            
            if ($eventId > 0) {
                // Check if this event ID has role-specific QR codes
                // If yes, reject numeric access
                $event = $this->eventModel->find($eventId);
                if ($event) {
                    return [
                        'valid' => true,
                        'event_id' => $eventId,
                        'role' => 'legacy',
                        'participation_type' => 'offline',
                        'date' => date('Ymd')
                    ];
                }
            }
        }
        
        // Pattern 6: Extract from URL and validate
        if (preg_match('/EVENT_(\d+)_([a-z]+)/i', $qrCode, $matches)) {
            $eventId = (int) $matches[1];
            $detectedRole = strtolower($matches[2]);
            
            if ($detectedRole === 'audience') {
                return [
                    'valid' => false,
                    'message' => "QR Code ini terdeteksi untuk Audience. Presenter memerlukan QR code khusus Presenter atau Universal."
                ];
            } elseif ($detectedRole === 'presenter') {
                return [
                    'valid' => true,
                    'event_id' => $eventId,
                    'role' => 'presenter',
                    'participation_type' => 'offline',
                    'date' => date('Ymd')
                ];
            }
        }
        
        return [
            'valid' => false,
            'message' => 'Format QR Code tidak dikenali atau tidak valid untuk Presenter. Pastikan menggunakan QR code khusus Presenter atau Universal.'
        ];
    }

    /**
     * IMPROVED: Check if event timing allows attendance - More flexible timing
     */
    private function checkEventTiming($event)
    {
        // Set timezone to WIB (Asia/Jakarta)
        $timezone = new \DateTimeZone('Asia/Jakarta');
        $now = new \DateTime('now', $timezone);
        $eventStart = new \DateTime($event['event_date'] . ' ' . $event['event_time'], $timezone);
        $eventEnd = clone $eventStart;
        $eventEnd->add(new \DateInterval('PT8H')); // Add 8 hours for event duration
        
        // More flexible access timing
        $earlyAccessHours = 2; // 2 hours before event start
        $lateAccessHours = 4;  // 4 hours after event end
        
        // For development environment, be even more flexible
        if (ENVIRONMENT === 'development') {
            $earlyAccessHours = 24; // 24 hours before
            $lateAccessHours = 24;  // 24 hours after
        }
        
        // For events today, allow access from 6:00 AM regardless of event time
        $todayStart = new \DateTime($event['event_date'] . ' 06:00:00', $timezone);
        $todayEnd = new \DateTime($event['event_date'] . ' 23:59:59', $timezone);
        
        // Check if event is today
        $isToday = $now->format('Y-m-d') === $eventStart->format('Y-m-d');
        
        if ($isToday) {
            // For today's events, allow access from 6:00 AM
            if ($now >= $todayStart && $now <= $todayEnd) {
                return [
                    'can_attend' => true,
                    'message' => 'Absensi tersedia untuk event hari ini'
                ];
            } else if ($now < $todayStart) {
                $diff = $todayStart->diff($now);
                return [
                    'can_attend' => false,
                    'message' => "Absensi akan dibuka pada " . $todayStart->format('H:i') . 
                               " ({$diff->h} jam {$diff->i} menit lagi)"
                ];
            } else {
                return [
                    'can_attend' => false,
                    'message' => "Periode absensi untuk hari ini sudah berakhir pada " . $todayEnd->format('H:i')
                ];
            }
        }
        
        // For other days, use standard timing with early/late access
        $allowedStart = clone $eventStart;
        $allowedStart->sub(new \DateInterval("PT{$earlyAccessHours}H"));
        
        $allowedEnd = clone $eventEnd;
        $allowedEnd->add(new \DateInterval("PT{$lateAccessHours}H"));
        
        if ($now < $allowedStart) {
            $diff = $allowedStart->diff($now);
            return [
                'can_attend' => false,
                'message' => "Event belum dapat diakses. Absensi akan dibuka pada " . 
                           $allowedStart->format('d/m/Y H:i') . 
                           " ({$diff->d} hari {$diff->h} jam {$diff->i} menit lagi)"
            ];
        } 
        elseif ($now > $allowedEnd) {
            return [
                'can_attend' => false,
                'message' => "Periode absensi sudah berakhir pada " . 
                           $allowedEnd->format('d/m/Y H:i')
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
            $timezone = new \DateTimeZone('Asia/Jakarta');
            $now = new \DateTime('now', $timezone);
            
            $this->db->table('log_aktivitas')->insert([
                'id_user' => $userId,
                'aktivitas' => $activity,
                'waktu' => $now->format('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Failed to log activity: ' . $e->getMessage());
        }
    }
}