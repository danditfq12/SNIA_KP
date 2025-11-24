<?php

namespace App\Controllers\Role\Audience;

use App\Controllers\BaseController;
use App\Models\EventModel;
use App\Models\PembayaranModel;
use App\Models\AbsensiModel;
use App\Models\UserModel;

class Absensi extends BaseController
{
    protected EventModel $eventModel;
    protected PembayaranModel $pembayaranModel;
    protected AbsensiModel $absensiModel;
    protected UserModel $userModel;
    protected \CodeIgniter\Database\BaseConnection $db;
    protected \DateTimeZone $tz;

    public function __construct()
    {
        // Set timezone ke WIB
        date_default_timezone_set('Asia/Jakarta');
        
        $this->eventModel      = new EventModel();
        $this->pembayaranModel = new PembayaranModel();
        $this->absensiModel    = new AbsensiModel();
        $this->userModel       = new UserModel();
        $this->db              = \Config\Database::connect();
        $this->tz              = new \DateTimeZone('Asia/Jakarta');
    }

    private function uid(): int
    {
        return (int) (session('id_user') ?? 0);
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
     * Calculate attendance window (start & end time) - FIXED WITH WIB
     */
    private function getAttendanceWindow(array $event): array
    {
        $startStr = trim(($event['event_date'] ?? '') . ' ' . ($event['event_time'] ?? '00:00:00'));
        $start = strtotime($startStr) ?: null;
        $end = null;
        if ($start) {
            $end = $start + (4 * 3600); // 4 hours duration
        }

        // Gunakan WIB timezone
        $nowWIB = $this->getCurrentTimeWIB();
        $nowTimestamp = $nowWIB->getTimestamp();
        
        $open = ($start && $end) ? ($nowTimestamp >= ($start - 1800) && $nowTimestamp <= $end) : false; // 30 min early

        $reason = '';
        if ($start && $end && !$open) {
            if ($nowTimestamp < ($start - 1800)) {
                $remaining = ($start - 1800) - $nowTimestamp;
                $hours = floor($remaining / 3600);
                $minutes = floor(($remaining % 3600) / 60);
                $reason = "Akan dibuka dalam {$hours}j {$minutes}m";
            } elseif ($nowTimestamp > $end) {
                $reason = 'Window absensi sudah ditutup';
            }
        }

        return [
            'start_ts' => $start,
            'end_ts' => $end,
            'is_open' => $open,
            'reason' => $reason,
            'current_time_wib' => $nowWIB->format('Y-m-d H:i:s'), // Tambahan info WIB
        ];
    }

    /** ===== Helpers ===== */
    private function normalizeTime(?string $t): ?string
    {
        if (!$t) return null;
        $t = trim($t);
        if (preg_match('/^\d{2}:\d{2}$/', $t)) return $t . ':00';
        return $t;
    }

    private function composeStart(?string $eventDate, ?string $eventTime): ?string
    {
        if (!$eventDate || !$eventTime) return null;
        return $eventDate . ' ' . $this->normalizeTime($eventTime);
    }

    /**
     * Enhanced QR Token validation with participation type validation for audience
     */
    private function validateQRToken($token, $eventId, $userParticipationType)
    {
        $token = $this->cleanQRToken($token);
        log_message('debug', "Validating QR token for audience: {$token}, user participation: {$userParticipationType}");

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

            // Role validation for audience
            if ($role !== 'audience' && $role !== 'all') {
                $roleDisplayNames = [
                    'presenter' => 'Presenter',
                    'reviewer' => 'Reviewer'
                ];
                $roleName = $roleDisplayNames[$role] ?? ucfirst($role);
                
                return [
                    'valid' => false,
                    'message' => "QR Code ini khusus untuk {$roleName}. Sebagai Audience, Anda hanya dapat menggunakan QR Code Audience atau Universal.",
                    'code' => 'WRONG_ROLE',
                    'detected_role' => $role
                ];
            }

            // Participation type validation (key feature for audience)
            if ($role === 'audience' && $participationType !== 'all') {
                // Check if user's participation type matches QR code participation type
                if ($userParticipationType !== $participationType) {
                    $participationNames = [
                        'online' => 'Online',
                        'offline' => 'Offline'
                    ];
                    $qrTypeName = $participationNames[$participationType] ?? ucfirst($participationType);
                    $userTypeName = $participationNames[$userParticipationType] ?? ucfirst($userParticipationType);
                    
                    return [
                        'valid' => false,
                        'message' => "QR Code ini khusus untuk peserta {$qrTypeName}. Anda terdaftar sebagai peserta {$userTypeName}.",
                        'code' => 'WRONG_PARTICIPATION_TYPE',
                        'detected_participation' => $participationType,
                        'user_participation' => $userParticipationType
                    ];
                }
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
                'message' => "QR Code valid untuk Audience ({$participationType})"
            ];
        }

        // Simple format EVENT_{event_id}_{date} - Universal
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
                'message' => 'QR Code Universal valid untuk Audience'
            ];
        }

        // Numeric format (just event ID) - Universal
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
                'date' => $this->getCurrentTimeWIB()->format('Ymd'), // WIB date
                'message' => 'QR Code Universal valid untuk Audience'
            ];
        }

        // Admin generated format - Universal
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
                'message' => "QR Code Admin valid untuk Audience",
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
                    'date' => $this->getCurrentTimeWIB()->format('Ymd'), // WIB date
                    'message' => 'QR Code valid (format fallback)'
                ];
            }
        }
        
        return [
            'valid' => false,
            'message' => 'Format QR Code tidak dikenali atau tidak valid untuk Audience.',
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
     * Aturan:
     * - Bisa scan hanya SETELAH mulai (>= start) s.d. +4 jam.
     * - Jika event dihentikan admin (attendance_status = closed/stopped) → tidak bisa scan.
     * - Event non-aktif → tidak bisa scan.
     * Catatan: jika kolom attendance_status tidak ada, dianggap 'open'.
     */
    private function calculateEventStatus(array $event): array
    {
        if (empty($event['event_date']) || empty($event['event_time'])) {
            return ['event_status' => 'Jadwal Tidak Lengkap', 'badge_class' => 'bg-secondary', 'can_scan' => false];
        }

        if (empty($event['is_active'])) {
            return ['event_status' => 'Tidak Aktif', 'badge_class' => 'bg-secondary', 'can_scan' => false];
        }

        $attn = strtolower((string)($event['attendance_status'] ?? 'open'));
        if (in_array($attn, ['closed','stopped','ended'], true)) {
            return ['event_status' => 'Dihentikan', 'badge_class' => 'bg-danger', 'can_scan' => false];
        }

        $startStr = $this->composeStart($event['event_date'], $event['event_time']);
        $start    = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $startStr, $this->tz)
                  ?: new \DateTimeImmutable($startStr, $this->tz);

        $now   = new \DateTimeImmutable('now', $this->tz);
        $diffH = ($now->getTimestamp() - $start->getTimestamp()) / 3600.0;

        if ($diffH < 0) {
            return ['event_status' => 'Belum Dimulai', 'badge_class' => 'bg-secondary', 'can_scan' => false];
        } elseif ($diffH <= 4) {
            return ['event_status' => 'Sedang Berlangsung', 'badge_class' => 'bg-success', 'can_scan' => true];
        }
        return ['event_status' => 'Sudah Selesai', 'badge_class' => 'bg-secondary', 'can_scan' => false];
    }

    /** ===== Pages ===== */
    public function index()
    {
        $userId = $this->uid();
        if (!$userId || session('role') !== 'audience') {
            return redirect()->to(site_url('auth/login'));
        }

        // Deteksi kolom link online di tabel events
        $eventFields = [];
        try { $eventFields = $this->db->getFieldNames('events'); } catch (\Throwable $e) {}
        $zoomCol = null;
        foreach (['zoom_link','meeting_link','online_link'] as $cand) {
            if (in_array($cand, $eventFields, true)) { $zoomCol = $cand; break; }
        }

        // SELECT dinamis agar aman di berbagai skema
        $eventCols = '
            e.id,
            e.title,
            e.event_date,
            e.event_time,
            e.format,
            e.location,
            e.is_active,
            p.participation_type
        ';
        if ($zoomCol) $eventCols .= ', e.' . $zoomCol . ' AS zoom_link';

        // HANYA verified
        $paid = $this->db->table('pembayaran p')
            ->select($eventCols, false)
            ->join('events e', 'e.id = p.event_id')
            ->where('p.id_user', $userId)
            ->where('p.status', 'verified')
            ->where('e.is_active', true)
            ->orderBy('e.event_date', 'ASC')
            ->orderBy('e.event_time', 'ASC')
            ->get()->getResultArray();

        // Kumpulkan event_id
        $eventIds = array_map(fn($r)=> (int)$r['id'], $paid);

        // Ambil last attendance per event (sekali query)
        $attMap = [];
        if (!empty($eventIds)) {
            $rows = $this->db->table('absensi')
                    ->select('event_id, MAX(waktu_scan) AS last_scan', false)
                    ->where('id_user', $userId)
                    ->whereIn('event_id', $eventIds)
                    ->groupBy('event_id')
                    ->get()->getResultArray();
            foreach ($rows as $r) {
                $attMap[(int)$r['event_id']] = $r['last_scan'] ?? null;
            }
        }

        // Bentuk output untuk view
        $yourEvents = [];
        foreach ($paid as $row) {
            $status      = $this->calculateEventStatus($row);
            $eid         = (int)$row['id'];
            $attendanceAt= $attMap[$eid] ?? null;
            $already     = $attendanceAt !== null;

            $yourEvents[] = [
                'id'                 => $eid,
                'title'              => $row['title'],
                'event_date'         => $row['event_date'],
                'event_time'         => $row['event_time'],
                'format'             => $row['format'] ?? '-',
                'location'           => $row['location'] ?? '-',
                'participation_type' => $row['participation_type'] ?? 'all',
                'event_status'       => $status['event_status'],
                'badge_class'        => $status['badge_class'],
                'can_scan'           => $status['can_scan'],

                // tambahan utk tampilan "Sudah Absen"
                'already_attend'     => $already,
                'attendance_at'      => $attendanceAt,

                // link meeting (jika ada kolomnya)
                'zoom_link'          => $row['zoom_link'] ?? null,
            ];
        }

        // Riwayat (tanpa perubahan)
        $history = $this->absensiModel->select('
                            absensi.waktu_scan,
                            absensi.status,
                            absensi.qr_code,
                            e.title as event_title,
                            e.event_date,
                            e.event_time
                        ')
                        ->join('events e', 'e.id = absensi.event_id', 'left')
                        ->where('absensi.id_user', $userId)
                        ->orderBy('absensi.waktu_scan', 'DESC')
                        ->findAll() ?: [];

        return view('role/audience/absensi/index', [
            'title'      => 'Absensi',
            'yourEvents' => $yourEvents,
            'history'    => $history,
        ]);
    }

    public function show(int $eventId)
    {
        $userId = $this->uid();
        if (!$userId || session('role') !== 'audience') {
            return redirect()->to(site_url('auth/login'));
        }

        $event = $this->eventModel->find($eventId);
        if (!$event || !($event['is_active'] ?? false)) {
            return redirect()->to(site_url('audience/absensi'))
                ->with('error', 'Event tidak ditemukan atau tidak aktif.');
        }

        $payment = $this->pembayaranModel->where('id_user', $userId)
                                         ->where('event_id', $eventId)
                                         ->where('status', 'verified')
                                         ->first();
        if (!$payment) {
            return redirect()->to(site_url('audience/absensi'))
                ->with('error', 'Kamu belum memiliki akses absensi untuk event tersebut.');
        }

        $window  = $this->getAttendanceWindow($event);
        $status  = $this->calculateEventStatus($event);
        $already = $this->absensiModel->hasUserAttended($userId, $eventId);

        $last = $this->absensiModel->select('waktu_scan')
                ->where('id_user', $userId)
                ->where('event_id', $eventId)
                ->orderBy('waktu_scan','DESC')
                ->first();
        $attendanceAt = $last['waktu_scan'] ?? null;

        $eventView = $event;
        $eventView['badge_class']        = $status['badge_class'];
        $eventView['event_status']       = $status['event_status'];
        $eventView['can_scan']           = $status['can_scan'];
        $eventView['participation_type'] = $payment['participation_type'] ?? 'all';
        $eventView['window']             = $window;

        return view('role/audience/absensi/detail', [
            'title'           => 'Detail Absensi',
            'event'           => $eventView,
            'payment'         => $payment,
            'window'          => $window,
            'already_attend'  => $already,
            'attendance_at'   => $attendanceAt,
        ]);
    }

    /**
     * SCAN - Process QR token submission with enhanced participation type validation
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

        $userId = $this->uid();
        $eventId = (int) $this->request->getPost('event_id');
        $rawToken = trim((string) $this->request->getPost('token'));
        $isAjax = $this->request->isAJAX();

        if (!$userId || session('role') !== 'audience') {
            $message = 'Akses tidak valid.';
            return $isAjax ? 
                $this->response->setJSON(['success' => false, 'message' => $message]) :
                redirect()->to(site_url('auth/login'));
        }

        if (!$eventId || $rawToken === '') {
            $message = 'Event dan token wajib diisi.';
            return $isAjax ? 
                $this->response->setJSON(['success' => false, 'message' => $message]) :
                redirect()->back()->with('error', $message);
        }

        log_message('debug', "Audience scanning QR - User: {$userId}, Event: {$eventId}, Token: {$rawToken}");

        $event = $this->eventModel->find($eventId);
        if (!$event) {
            $message = 'Event tidak valid.';
            return $isAjax ?
                $this->response->setJSON(['success' => false, 'message' => $message]) :
                redirect()->back()->with('error', $message);
        }

        // Check payment verification
        $payment = $this->pembayaranModel
            ->where('id_user', $userId)
            ->where('event_id', $eventId)
            ->where('status', 'verified')
            ->first();
        
        if (!$payment) {
            $message = 'Akses absensi ditolak. Pembayaran belum terverifikasi.';
            return $isAjax ?
                $this->response->setJSON(['success' => false, 'message' => $message]) :
                redirect()->back()->with('error', $message);
        }

        $userParticipationType = $payment['participation_type'] ?? 'all';

        // Validate QR token with participation type
        $validation = $this->validateQRToken($rawToken, $eventId, $userParticipationType);
        
        if (!$validation['valid']) {
            log_message('warning', "QR validation failed for audience - {$validation['message']}");
            return $isAjax ?
                $this->response->setJSON([
                    'success' => false, 
                    'message' => $validation['message'],
                    'code' => $validation['code'] ?? 'VALIDATION_ERROR'
                ]) :
                redirect()->back()->with('error', $validation['message']);
        }

        // Check attendance window
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
            $attendanceQRCode = 'AUDIENCE_SCAN_' . $eventId . '_' . $userId . '_' . $wibTime->format('YmdHis');
            
            // Insert absensi record dengan WIB timestamp
            $attendanceData = [
                'id_user' => $userId,
                'event_id' => $eventId,
                'qr_code' => $attendanceQRCode,
                'status' => 'hadir',
                'waktu_scan' => $wibTime->format('Y-m-d H:i:s'), // WIB timestamp
                'marked_by_admin' => null,
                'notes' => "QR scan by Audience ({$user['nama_lengkap']}) - Role: {$validation['role']}, QR Type: {$validation['participation_type']}, User Type: {$userParticipationType}" . 
                          (isset($validation['admin_generated']) ? ' [Admin Generated]' : '') .
                          " - Token: " . substr($rawToken, 0, 50) . " - WIB: " . $wibTime->format('Y-m-d H:i:s')
            ];

            $insertId = $this->absensiModel->insert($attendanceData);
            
            if (!$insertId) {
                throw new \Exception('Gagal mencatat kehadiran dalam database.');
            }

            // Log activity dengan WIB timestamp
            $this->logActivity($userId, "Audience QR attendance - Event: {$event['title']}, QR Role: {$validation['role']}, QR Type: {$validation['participation_type']}, User Type: {$userParticipationType} - WIB: " . $wibTime->format('Y-m-d H:i:s'));

            $this->db->transComplete();

            if ($this->db->transStatus() === FALSE) {
                throw new \Exception('Database transaction failed.');
            }

            log_message('info', "Audience attendance successful - User: {$userId}, Event: {$eventId}, QR Role: {$validation['role']}, QR Type: {$validation['participation_type']}, User Type: {$userParticipationType} - WIB: " . $wibTime->format('Y-m-d H:i:s'));

            $participationDisplayName = ucfirst($userParticipationType);
            $successMessage = "Absensi berhasil dicatat! Terima kasih telah hadir sebagai Audience ({$participationDisplayName}).";

            if ($isAjax) {
                return $this->response->setJSON([
                    'success' => true,
                    'message' => $successMessage,
                    'data' => [
                        'attendance_id' => $insertId,
                        'participant_name' => $user['nama_lengkap'],
                        'participant_role' => 'Audience',
                        'event_title' => $event['title'],
                        'attendance_time' => $wibTime->format('H:i:s'),
                        'attendance_date' => $wibTime->format('d/m/Y'),
                        'attendance_datetime_wib' => $wibTime->format('Y-m-d H:i:s'),
                        'qr_role' => $validation['role'],
                        'qr_participation_type' => $validation['participation_type'],
                        'user_participation_type' => $userParticipationType
                    ]
                ]);
            } else {
                return redirect()->to('/audience/absensi/event/' . $eventId)
                    ->with('success', $successMessage);
            }

        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Audience attendance error: ' . $e->getMessage());
            
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
        return $this->scan(); // Reuse the enhanced scan method
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
                'waktu' => $wibTime->format('Y-m-d H:i:s') // WIB timestamp
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Failed to log audience activity: ' . $e->getMessage());
        }
    }
}