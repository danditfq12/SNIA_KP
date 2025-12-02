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
        // Set timezone ke WIB
        date_default_timezone_set('Asia/Jakarta');
        
        $this->absensiModel = new AbsensiModel();
        $this->pembayaranModel = new PembayaranModel();
        $this->eventModel = new EventModel();
        $this->userModel = new UserModel();
        $this->db = \Config\Database::connect();
    }

    /**
     * Get current time in WIB
     */
    private function getCurrentTimeWIB()
    {
        return new \DateTime('now', new \DateTimeZone('Asia/Jakarta'));
    }

    /**
     * Convert timestamp to WIB DateTime
     */
    private function toWIBDateTime($timestamp)
    {
        $dt = new \DateTime();
        $dt->setTimestamp($timestamp);
        $dt->setTimezone(new \DateTimeZone('Asia/Jakarta'));
        return $dt;
    }

    /**
     * Calculate attendance window (2 jam sebelum event berakhir) - UPDATED
     */
    private function getAttendanceWindow(array $event): array
    {
        // Parse event start time dan end time
        $startStr = trim(($event['event_date'] ?? '') . ' ' . ($event['event_time'] ?? '00:00:00'));
        $eventStart = strtotime($startStr) ?: null;
        
        // Parse event end time jika ada, jika tidak ada gunakan durasi default
        $eventEnd = null;
        if (!empty($event['event_end_time'])) {
            $endStr = trim(($event['event_date'] ?? '') . ' ' . $event['event_end_time']);
            $eventEnd = strtotime($endStr) ?: null;
        }
        
        // Jika tidak ada end time, hitung dari durasi (default 10 jam)
        if (!$eventEnd && $eventStart) {
            $eventDuration = 10 * 3600; // 10 jam dalam detik
            $eventEnd = $eventStart + $eventDuration;
        }
        
        if (!$eventStart || !$eventEnd) {
            return [
                'start_ts' => null,
                'end_ts' => null,
                'is_open' => false,
                'reason' => 'Jadwal event tidak valid',
                'current_time_wib' => $this->getCurrentTimeWIB()->format('Y-m-d H:i:s'),
            ];
        }

        // Window absensi dibuka 2 jam sebelum event berakhir
        $absensiWindowStart = $eventEnd - (2 * 3600); // 2 jam sebelum akhir
        $absensiWindowEnd = $eventEnd; // Tutup saat event berakhir

        // Gunakan WIB timezone
        $nowWIB = $this->getCurrentTimeWIB();
        $nowTimestamp = $nowWIB->getTimestamp();
        
        // Cek apakah window terbuka
        $open = ($nowTimestamp >= $absensiWindowStart && $nowTimestamp <= $absensiWindowEnd);

        $reason = '';
        if (!$open) {
            if ($nowTimestamp < $absensiWindowStart) {
                // Belum dibuka
                $remaining = $absensiWindowStart - $nowTimestamp;
                $hours = floor($remaining / 3600);
                $minutes = floor(($remaining % 3600) / 60);
                
                $absensiStartTimeFormatted = $this->toWIBDateTime($absensiWindowStart)->format('H:i');
                $eventEndTimeFormatted = $this->toWIBDateTime($eventEnd)->format('H:i');
                
                $reason = "Absensi dibuka 2 jam sebelum event berakhir (pukul {$absensiStartTimeFormatted} - {$eventEndTimeFormatted}). Akan dibuka dalam {$hours}j {$minutes}m";
            } elseif ($nowTimestamp > $absensiWindowEnd) {
                // Sudah ditutup
                $reason = 'Window absensi sudah ditutup (event telah berakhir)';
            }
        }

        return [
            'start_ts' => $absensiWindowStart,
            'end_ts' => $absensiWindowEnd,
            'event_start_ts' => $eventStart,
            'event_end_ts' => $eventEnd,
            'is_open' => $open,
            'reason' => $reason,
            'current_time_wib' => $nowWIB->format('Y-m-d H:i:s'),
            'absensi_start_time' => $this->toWIBDateTime($absensiWindowStart)->format('H:i'),
            'absensi_end_time' => $this->toWIBDateTime($absensiWindowEnd)->format('H:i'),
        ];
    }

    /**
     * Enhanced QR Token validation with role-based access control - FIXED WITH WIB
     */
    private function validateQRToken($token, $eventId)
    {
        $token = $this->cleanQRToken($token);
        log_message('debug', "Validating QR token for presenter: {$token}");

        // Standard format EVENT_{event_id}_{role}_{participation_type}_{date}_{hash}
        $standardPattern = '/^EVENT_(\d+)_([a-z]+)_([a-z]+)_(\d{8})_([a-f0-9]+)$/i';
        if (preg_match($standardPattern, $token, $matches)) {
            $tokenEventId = (int) $matches[1];
            $role = strtolower($matches[2]);
            $participationType = strtolower($matches[3]);
            $date = $matches[4];
            $providedHash = strtolower($matches[5]);

            // Check event ID match
            if ($tokenEventId !== $eventId) {
                return [
                    'valid' => false,
                    'message' => 'QR Code tidak sesuai dengan event ini.',
                    'code' => 'WRONG_EVENT'
                ];
            }

            // Role validation for presenter
            if ($role !== 'presenter' && $role !== 'all') {
                $roleDisplayNames = [
                    'audience' => 'Audience',
                    'reviewer' => 'Reviewer'
                ];
                $roleName = $roleDisplayNames[$role] ?? ucfirst($role);
                
                return [
                    'valid' => false,
                    'message' => "QR Code ini khusus untuk {$roleName}. Sebagai Presenter, Anda hanya dapat menggunakan QR Code Presenter atau Universal.",
                    'code' => 'WRONG_ROLE',
                    'detected_role' => $role
                ];
            }

            // Date validation dengan WIB
            $nowWIB = $this->getCurrentTimeWIB();
            $currentDate = $nowWIB->format('Ymd');
            $yesterday = $nowWIB->modify('-1 day')->format('Ymd');
            $nowWIB = $this->getCurrentTimeWIB(); // Reset
            $tomorrow = $nowWIB->modify('+1 day')->format('Ymd');
            
            if (!in_array($date, [$yesterday, $currentDate, $tomorrow])) {
                return [
                    'valid' => false,
                    'message' => 'QR Code sudah kedaluwarsa atau belum valid untuk tanggal ini.',
                    'code' => 'EXPIRED_DATE'
                ];
            }

            return [
                'valid' => true,
                'event_id' => $tokenEventId,
                'role' => $role,
                'participation_type' => $participationType,
                'date' => $date,
                'message' => "QR Code valid untuk Presenter ({$role})"
            ];
        }

        // Simple format EVENT_{event_id}_{date}
        $simplePattern = '/^EVENT_(\d+)_(\d{8})$/i';
        if (preg_match($simplePattern, $token, $matches)) {
            $tokenEventId = (int) $matches[1];
            
            if ($tokenEventId !== $eventId) {
                return [
                    'valid' => false,
                    'message' => 'QR Code tidak sesuai dengan event ini.',
                    'code' => 'WRONG_EVENT'
                ];
            }

            return [
                'valid' => true,
                'event_id' => $tokenEventId,
                'role' => 'all',
                'participation_type' => 'all',
                'date' => $matches[2],
                'message' => 'QR Code Universal valid untuk Presenter'
            ];
        }

        // Numeric format (just event ID)
        if (is_numeric($token)) {
            $tokenEventId = (int) $token;
            
            if ($tokenEventId !== $eventId) {
                return [
                    'valid' => false,
                    'message' => 'QR Code tidak sesuai dengan event ini.',
                    'code' => 'WRONG_EVENT'
                ];
            }

            return [
                'valid' => true,
                'event_id' => $tokenEventId,
                'role' => 'all',
                'participation_type' => 'all',
                'date' => $this->getCurrentTimeWIB()->format('Ymd'),
                'message' => 'QR Code Universal valid untuk Presenter'
            ];
        }

        // Admin generated format
        $adminPattern = '/^(ADMIN|MANUAL|BULK)_(\d+)_(\d{8})/i';
        if (preg_match($adminPattern, $token, $matches)) {
            $tokenEventId = (int) $matches[2];
            
            if ($tokenEventId !== $eventId) {
                return [
                    'valid' => false,
                    'message' => 'QR Code tidak sesuai dengan event ini.',
                    'code' => 'WRONG_EVENT'
                ];
            }

            return [
                'valid' => true,
                'event_id' => $tokenEventId,
                'role' => 'all',
                'participation_type' => 'all',
                'date' => $matches[3],
                'message' => "QR Code Admin valid untuk Presenter",
                'admin_generated' => true
            ];
        }

        // Try to extract event ID as fallback
        if (preg_match('/EVENT_(\d+)/i', $token, $matches)) {
            $tokenEventId = (int) $matches[1];
            
            if ($tokenEventId === $eventId) {
                return [
                    'valid' => true,
                    'event_id' => $tokenEventId,
                    'role' => 'all',
                    'participation_type' => 'all',
                    'date' => $this->getCurrentTimeWIB()->format('Ymd'),
                    'message' => 'QR Code valid (format fallback)'
                ];
            }
        }
        
        return [
            'valid' => false,
            'message' => 'Format QR Code tidak dikenali atau tidak valid untuk Presenter.',
            'code' => 'INVALID_FORMAT'
        ];
    }

    private function cleanQRToken($token)
    {
        $token = trim($token);
        $token = urldecode($token);
        $token = ltrim($token, '/');
        
        if (strpos($token, 'http') === 0 || strpos($token, '/') !== false) {
            $urlParts = parse_url($token);
            if (isset($urlParts['path'])) {
                $pathParts = explode('/', trim($urlParts['path'], '/'));
                foreach ($pathParts as $part) {
                    if (strpos($part, 'EVENT_') === 0) {
                        return $part;
                    }
                }
                return end($pathParts);
            }
        }
        
        return $token;
    }

    /**
     * INDEX - Show attendance dashboard - FIXED WITH WIB
     */
    public function index()
    {
        $userId = (int) session()->get('id_user');

        $verified = $this->pembayaranModel
            ->select('pembayaran.*, e.*')
            ->join('events e', 'e.id = pembayaran.event_id', 'left')
            ->where('pembayaran.id_user', $userId)
            ->where('pembayaran.status', 'verified')
            ->orderBy('e.event_date', 'ASC')
            ->findAll();

        $todayWIB = $this->getCurrentTimeWIB()->format('Y-m-d');
        $boxesToday = [];
        $boxesNext = [];

        foreach ($verified as $p) {
            $event = $p;
            $att = $this->getAttendanceWindow($event);

            $box = [
                'event_id' => (int)$event['id'],
                'title' => $event['title'],
                'date' => $event['event_date'],
                'time' => $event['event_time'],
                'location' => $event['location'] ?? null,
                'format' => $event['format'] ?? null,
                'window' => $att,
                'attended' => $this->absensiModel->hasUserAttended($userId, (int)$event['id']),
            ];

            if (($event['event_date'] ?? '') === $todayWIB) {
                $boxesToday[] = $box;
            } elseif (($event['event_date'] ?? '') > $todayWIB) {
                $boxesNext[] = $box;
            }
        }

        $kpi = [
            'count_today' => count($boxesToday),
            'count_next' => count($boxesNext),
            'count_hadir' => $this->absensiModel->countUserAttendance($userId),
            'current_time_wib' => $this->getCurrentTimeWIB()->format('Y-m-d H:i:s')
        ];

        return view('role/presenter/absensi/index', [
            'title' => 'Absensi',
            'boxesToday' => $boxesToday,
            'boxesNext' => $boxesNext,
            'kpi' => $kpi,
        ]);
    }

    /**
     * SHOW - Show attendance detail for specific event
     */
    public function show(int $eventId)
    {
        $userId = (int) session()->get('id_user');
        
        $event = $this->eventModel->find($eventId);
        if (!$event) {
            return redirect()->to('/presenter/absensi')->with('error', 'Event tidak ditemukan.');
        }

        $pay = $this->pembayaranModel
            ->where('id_user', $userId)
            ->where('event_id', $eventId)
            ->where('status', 'verified')
            ->first();
        
        if (!$pay) {
            return redirect()->to('/presenter/absensi')
                ->with('error', 'Akses ditolak. Pembayaran belum terverifikasi.');
        }

        $window = $this->getAttendanceWindow($event);
        $attended = $this->absensiModel->hasUserAttended($userId, $eventId);

        return view('role/presenter/absensi/detail', [
            'title' => 'Detail Absensi',
            'event' => $event,
            'payment' => $pay,
            'window' => $window,
            'attended' => $attended,
        ]);
    }

    /**
     * SCAN - Process QR token submission (ENHANCED) - FIXED WITH WIB
     */
    public function scan()
    {
        // Support both POST and AJAX requests
        if (!$this->request->is('post')) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Method not allowed'
            ]);
        }

        $userId = (int) session()->get('id_user');
        $eventId = (int) $this->request->getPost('event_id');
        $rawToken = trim((string) $this->request->getPost('token'));
        $isAjax = $this->request->isAJAX();

        if (!$eventId || $rawToken === '') {
            $message = 'Event dan token wajib diisi.';
            return $isAjax ? 
                $this->response->setJSON(['success' => false, 'message' => $message]) :
                redirect()->back()->with('error', $message);
        }

        log_message('debug', "Presenter scanning QR - User: {$userId}, Event: {$eventId}, Token: {$rawToken}");

        // Validate QR token
        $validation = $this->validateQRToken($rawToken, $eventId);
        
        if (!$validation['valid']) {
            log_message('warning', "QR validation failed for presenter - {$validation['message']}");
            return $isAjax ?
                $this->response->setJSON([
                    'success' => false, 
                    'message' => $validation['message'],
                    'code' => $validation['code'] ?? 'VALIDATION_ERROR'
                ]) :
                redirect()->back()->with('error', $validation['message']);
        }

        $event = $this->eventModel->find($eventId);
        if (!$event) {
            $message = 'Event tidak valid.';
            return $isAjax ?
                $this->response->setJSON(['success' => false, 'message' => $message]) :
                redirect()->back()->with('error', $message);
        }

        // Check payment verification
        $pay = $this->pembayaranModel
            ->where('id_user', $userId)
            ->where('event_id', $eventId)
            ->where('status', 'verified')
            ->first();
        
        if (!$pay) {
            $message = 'Akses absensi ditolak. Pembayaran belum terverifikasi.';
            return $isAjax ?
                $this->response->setJSON(['success' => false, 'message' => $message]) :
                redirect()->back()->with('error', $message);
        }

        // Check attendance window (2 jam sebelum event berakhir)
        $window = $this->getAttendanceWindow($event);
        if (!$window['is_open']) {
            $message = $window['reason'] ?: 'Window absensi belum dibuka/sudah ditutup.';
            return $isAjax ?
                $this->response->setJSON(['success' => false, 'message' => $message]) :
                redirect()->back()->with('error', $message);
        }

        // Check if already attended
        if ($this->absensiModel->hasUserAttended($userId, $eventId)) {
            $message = 'Anda sudah tercatat hadir pada event ini.';
            return $isAjax ?
                $this->response->setJSON(['success' => false, 'message' => $message, 'already_attended' => true]) :
                redirect()->back()->with('info', $message);
        }

        // Get user info for logging
        $user = $this->userModel->find($userId);

        // Begin transaction
        $this->db->transStart();

        try {
            // Generate unique attendance QR code dengan WIB timestamp
            $wibTime = $this->getCurrentTimeWIB();
            $attendanceQRCode = 'PRESENTER_SCAN_' . $eventId . '_' . $userId . '_' . $wibTime->format('YmdHis');
            
            // Insert absensi record dengan WIB timestamp
            $attendanceData = [
                'id_user' => $userId,
                'event_id' => $eventId,
                'qr_code' => $attendanceQRCode,
                'status' => 'hadir',
                'waktu_scan' => $wibTime->format('Y-m-d H:i:s'),
                'marked_by_admin' => null,
                'notes' => "QR scan by Presenter ({$user['nama_lengkap']}) - Role: {$validation['role']}, Type: {$validation['participation_type']}" . 
                          (isset($validation['admin_generated']) ? ' [Admin Generated]' : '') .
                          " - Token: " . substr($rawToken, 0, 50) . " - WIB: " . $wibTime->format('Y-m-d H:i:s')
            ];

            $insertId = $this->absensiModel->insert($attendanceData);
            
            if (!$insertId) {
                throw new \Exception('Gagal mencatat kehadiran dalam database.');
            }

            // Log activity dengan WIB timestamp
            $this->logActivity($userId, "Presenter QR attendance - Event: {$event['title']}, QR Role: {$validation['role']} - WIB: " . $wibTime->format('Y-m-d H:i:s'));

            $this->db->transComplete();

            if ($this->db->transStatus() === FALSE) {
                throw new \Exception('Database transaction failed.');
            }

            log_message('info', "Presenter attendance successful - User: {$userId}, Event: {$eventId}, Role: {$validation['role']} - WIB: " . $wibTime->format('Y-m-d H:i:s'));

            $successMessage = 'Absensi berhasil dicatat! Terima kasih telah hadir sebagai Presenter.';

            if ($isAjax) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => $successMessage,
                    'data' => [
                        'attendance_id' => $insertId,
                        'participant_name' => $user['nama_lengkap'],
                        'participant_role' => 'Presenter',
                        'event_title' => $event['title'],
                        'attendance_time' => $wibTime->format('H:i:s'),
                        'attendance_date' => $wibTime->format('d/m/Y'),
                        'attendance_datetime_wib' => $wibTime->format('Y-m-d H:i:s'),
                        'qr_role' => $validation['role'],
                        'participation_type' => 'offline'
                    ]
                ]);
            } else {
                return redirect()->to('/presenter/absensi/event/' . $eventId)
                    ->with('success', $successMessage);
            }

        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Presenter attendance error: ' . $e->getMessage());
            
            $errorMessage = 'Gagal mencatat kehadiran: ' . $e->getMessage();
            
            if ($isAjax) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => $errorMessage
                ]);
            } else {
                return redirect()->back()->with('error', $errorMessage);
            }
        }
    }

    /**
     * NEW: AJAX endpoint for QR scanner integration
     */
    public function scanAjax()
    {
        return $this->scan();
    }

    /**
     * Log user activity dengan WIB timestamp
     */
    private function logActivity($userId, $activity)
    {
        try {
            $wibTime = $this->getCurrentTimeWIB();
            $this->db->table('log_aktivitas')->insert([
                'id_user' => $userId,
                'aktivitas' => $activity,
                'waktu' => $wibTime->format('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Failed to log presenter activity: ' . $e->getMessage());
        }
    }
}