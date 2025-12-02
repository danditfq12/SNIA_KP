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
     * Calculate attendance window - MODIFIED: Bisa absen KAPAN SAJA selama event berlangsung
     * Window: Dari event mulai sampai event selesai
     * 
     * Contoh: Event 09:00-17:00
     * - Window buka: 09:00 (saat event mulai)
     * - Window tutup: 17:00 (saat event selesai)
     * - Bisa absen: 09:00 - 17:00 (selama event berlangsung)
     */
    private function getAttendanceWindow(array $event): array
    {
        $eventDate = $event['event_date'] ?? '';
        $eventTime = $event['event_time'] ?? '00:00:00';
        $endTime = $event['end_time'] ?? null;
        
        // Normalize time format (handle HH:MM or HH:MM:SS)
        $eventTime = $this->normalizeTime($eventTime);
        if ($endTime) {
            $endTime = $this->normalizeTime($endTime);
        }
        
        $startStr = trim($eventDate . ' ' . $eventTime);
        $start = strtotime($startStr);
        
        if (!$start) {
            return [
                'start_ts' => null,
                'end_ts' => null,
                'is_open' => false,
                'reason' => 'Jadwal event tidak valid',
                'current_time_wib' => $this->getCurrentTimeWIB()->format('Y-m-d H:i:s'),
            ];
        }
        
        // Hitung event end time
        $eventEnd = null;
        if ($endTime) {
            // Jika ada end_time di database
            $endStr = trim($eventDate . ' ' . $endTime);
            $eventEnd = strtotime($endStr);
            
            // Debug log
            log_message('debug', "Event times - Start: {$startStr}, End: {$endStr}");
        }
        
        // Jika tidak ada end_time atau invalid, gunakan default 8 jam
        if (!$eventEnd || $eventEnd <= $start) {
            $eventEnd = $start + (8 * 3600); // default 8 jam
            log_message('debug', "Using default 8 hour duration");
        }
        
        // Hitung durasi dalam jam
        $durationHours = ($eventEnd - $start) / 3600;
        
        // MODIFIED: Window BUKA dari awal event sampai event selesai
        $windowStart = $start; // Mulai dari event dimulai
        $windowEnd = $eventEnd; // Sampai event selesai

        // Gunakan WIB timezone
        $nowWIB = $this->getCurrentTimeWIB();
        $nowTimestamp = $nowWIB->getTimestamp();
        
        // Window: Selama event berlangsung
        $open = ($nowTimestamp >= $windowStart && $nowTimestamp <= $windowEnd);

        $reason = '';
        if (!$open) {
            if ($nowTimestamp < $windowStart) {
                $remaining = $windowStart - $nowTimestamp;
                $hours = floor($remaining / 3600);
                $minutes = floor(($remaining % 3600) / 60);
                $windowStartTime = date('H:i', $windowStart);
                $reason = "Window absensi dibuka pukul {$windowStartTime} WIB (akan dibuka dalam {$hours}j {$minutes}m)";
            } elseif ($nowTimestamp > $windowEnd) {
                $windowEndTime = date('H:i', $windowEnd);
                $reason = "Window absensi sudah ditutup (ditutup pukul {$windowEndTime} WIB)";
            }
        }

        log_message('debug', "Window calculation - Window Start: " . date('Y-m-d H:i:s', $windowStart) . 
                             ", Window End: " . date('Y-m-d H:i:s', $windowEnd) . 
                             ", Event Start: " . date('Y-m-d H:i:s', $start) .
                             ", Event End: " . date('Y-m-d H:i:s', $eventEnd) .
                             ", Duration: {$durationHours}h, Open: " . ($open ? 'YES' : 'NO'));

        return [
            'start_ts' => $start,
            'end_ts' => $windowEnd,
            'event_end_ts' => $eventEnd,
            'window_start_ts' => $windowStart,
            'window_end_ts' => $windowEnd,
            'is_open' => $open,
            'reason' => $reason,
            'current_time_wib' => $nowWIB->format('Y-m-d H:i:s'),
            'event_duration' => $durationHours,
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
     * SIMPLIFIED QR Token validation - Sesuai dengan 3 QR yang di-generate Admin
     */
    private function validateQRToken($token, $eventId, $userRole = 'audience')
    {
        $token = $this->cleanQRToken($token);
        log_message('debug', "Validating QR token: {$token}, user role: {$userRole}, event: {$eventId}");

        // Format standard: EVENT_{event_id}_{role}_{participation_type}_{date}_{hash}
        $standardPattern = '/^EVENT_(\d+)_([a-z]+)_([a-z]+)_(\d{8})_([a-f0-9]+)$/i';
        if (preg_match($standardPattern, $token, $matches)) {
            $tokenEventId = (int) $matches[1];
            $role = strtolower($matches[2]);
            $participationType = strtolower($matches[3]);
            $date = $matches[4];

            // Check event ID match
            if ($tokenEventId !== $eventId) {
                return [
                    'valid' => false,
                    'message' => 'QR Code tidak sesuai dengan event ini.',
                    'code' => 'WRONG_EVENT'
                ];
            }

            // Role validation - SIMPLIFIED
            if ($userRole === 'audience') {
                if ($role === 'presenter') {
                    return [
                        'valid' => false,
                        'message' => 'QR Code ini khusus untuk Presenter. Sebagai Audience, gunakan QR Code Universal atau Audience.',
                        'code' => 'WRONG_ROLE'
                    ];
                }
            }

            // Date validation dengan WIB - toleransi ±1 hari
            $nowWIB = $this->getCurrentTimeWIB();
            $currentDate = $nowWIB->format('Ymd');
            $yesterday = (clone $nowWIB)->modify('-1 day')->format('Ymd');
            $tomorrow = (clone $nowWIB)->modify('+1 day')->format('Ymd');
            
            if (!in_array($date, [$yesterday, $currentDate, $tomorrow])) {
                return [
                    'valid' => false,
                    'message' => 'QR Code sudah kedaluwarsa atau belum valid untuk tanggal ini.',
                    'code' => 'EXPIRED_DATE'
                ];
            }

            $qrTypeLabel = 'Universal';
            if ($role === 'audience') {
                $qrTypeLabel = 'Audience';
            } elseif ($role === 'presenter') {
                $qrTypeLabel = 'Presenter';
            }

            return [
                'valid' => true,
                'event_id' => $tokenEventId,
                'role' => $role,
                'participation_type' => $participationType,
                'date' => $date,
                'message' => "QR Code {$qrTypeLabel} valid",
                'qr_type' => $qrTypeLabel
            ];
        }

        // Format simple: EVENT_{event_id}_{date} - Universal
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
                'message' => 'QR Code Universal valid',
                'qr_type' => 'Universal'
            ];
        }

        // Format numeric (hanya event ID) - Universal
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
                'message' => 'QR Code Universal valid',
                'qr_type' => 'Universal'
            ];
        }

        // Format admin: ADMIN|MANUAL|BULK_{event_id}_{date}_...
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
                'message' => 'QR Code Admin valid',
                'qr_type' => 'Admin',
                'admin_generated' => true
            ];
        }

        // Fallback: extract event ID dari token
        if (preg_match('/EVENT_(\d+)/i', $token, $matches)) {
            $tokenEventId = (int) $matches[1];
            
            if ($tokenEventId === $eventId) {
                return [
                    'valid' => true,
                    'event_id' => $tokenEventId,
                    'role' => 'all',
                    'participation_type' => 'all',
                    'date' => $this->getCurrentTimeWIB()->format('Ymd'),
                    'message' => 'QR Code valid (format fallback)',
                    'qr_type' => 'Universal'
                ];
            }
        }
        
        return [
            'valid' => false,
            'message' => 'Format QR Code tidak dikenali. Gunakan QR Code Universal atau Audience.',
            'code' => 'INVALID_FORMAT'
        ];
    }

    private function cleanQRToken($token)
    {
        $token = trim($token);
        $token = urldecode($token);
        $token = ltrim($token, '/');
        
        // Jika token berupa URL lengkap
        if (strpos($token, 'http') === 0 || strpos($token, '/') !== false) {
            $urlParts = parse_url($token);
            if (isset($urlParts['path'])) {
                $pathParts = explode('/', trim($urlParts['path'], '/'));
                // Cari part yang mengandung EVENT_
                foreach ($pathParts as $part) {
                    if (strpos($part, 'EVENT_') === 0) {
                        return $part;
                    }
                }
                // Jika tidak ada EVENT_, ambil bagian terakhir
                return end($pathParts);
            }
        }
        
        return $token;
    }

    /**
     * Calculate event status - MODIFIED: Window absensi dari awal sampai akhir event
     */
    private function calculateEventStatus(array $event): array
    {
        if (empty($event['event_date']) || empty($event['event_time'])) {
            return [
                'event_status' => 'Jadwal Tidak Lengkap', 
                'badge_class' => 'bg-secondary', 
                'can_scan' => false
            ];
        }

        if (empty($event['is_active'])) {
            return [
                'event_status' => 'Tidak Aktif', 
                'badge_class' => 'bg-secondary', 
                'can_scan' => false
            ];
        }

        $attn = strtolower((string)($event['attendance_status'] ?? 'open'));
        if (in_array($attn, ['closed','stopped','ended'], true)) {
            return [
                'event_status' => 'Dihentikan', 
                'badge_class' => 'bg-danger', 
                'can_scan' => false
            ];
        }

        try {
            $eventDate = $event['event_date'];
            $eventTime = $this->normalizeTime($event['event_time']);
            $endTime = isset($event['end_time']) ? $this->normalizeTime($event['end_time']) : null;
            
            $startStr = $eventDate . ' ' . $eventTime;
            $eventDateTime = new \DateTime($startStr, $this->tz);
            $currentDateTime = $this->getCurrentTimeWIB();
            
            $eventStart = $eventDateTime->getTimestamp();
            $now = $currentDateTime->getTimestamp();
            
            // Hitung event end time
            $eventEnd = null;
            if ($endTime) {
                $endStr = $eventDate . ' ' . $endTime;
                $eventEndDT = new \DateTime($endStr, $this->tz);
                $eventEnd = $eventEndDT->getTimestamp();
            }
            
            // Jika tidak ada end_time atau invalid, default 8 jam
            if (!$eventEnd || $eventEnd <= $eventStart) {
                $eventEnd = $eventStart + (8 * 3600);
            }
            
            $durationHours = ($eventEnd - $eventStart) / 3600;
            
            // MODIFIED: Window absensi = seluruh durasi event
            $windowStart = $eventStart;
            $windowEnd = $eventEnd;
            
            $hoursDiff = ($now - $eventStart) / 3600;
            $hoursToEventEnd = ($eventEnd - $now) / 3600;
            
            log_message('debug', "Event Status - Current time vs event start: " . round($hoursDiff, 2) . 
                                "h, Duration: {$durationHours}h, Hours to event end: " . round($hoursToEventEnd, 2) . "h");
            
            if ($now < $windowStart) {
                // Event belum dimulai
                return [
                    'event_status' => 'Belum Dimulai',
                    'badge_class' => 'bg-secondary',
                    'can_scan' => false
                ];
            } elseif ($now >= $windowStart && $now <= $windowEnd) {
                // Event sedang berlangsung - bisa absen kapan saja
                return [
                    'event_status' => 'Sedang Berlangsung - Bisa Absen',
                    'badge_class' => 'bg-success',
                    'can_scan' => true
                ];
            } else {
                // Event sudah selesai
                return [
                    'event_status' => 'Sudah Selesai',
                    'badge_class' => 'bg-danger',
                    'can_scan' => false
                ];
            }
            
        } catch (\Exception $e) {
            log_message('error', 'Error calculating event status: ' . $e->getMessage());
            return [
                'event_status' => 'Error',
                'badge_class' => 'bg-secondary',
                'can_scan' => false
            ];
        }
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

        // Ambil last attendance per event
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
                'already_attend'     => $already,
                'attendance_at'      => $attendanceAt,
                'zoom_link'          => $row['zoom_link'] ?? null,
            ];
        }

        // Riwayat
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
     * SCAN - Process QR token submission
     */
    public function scan()
    {
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

        // Validate QR token
        $validation = $this->validateQRToken($rawToken, $eventId, 'audience');
        
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

        // Check attendance window (selama event berlangsung)
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

        // Get user info
        $user = $this->userModel->find($userId);

        // Begin transaction
        $this->db->transStart();

        try {
            $wibTime = $this->getCurrentTimeWIB();
            $attendanceQRCode = 'AUDIENCE_SCAN_' . $eventId . '_' . $userId . '_' . $wibTime->format('YmdHis');
            
            $attendanceData = [
                'id_user' => $userId,
                'event_id' => $eventId,
                'qr_code' => $attendanceQRCode,
                'status' => 'hadir',
                'waktu_scan' => $wibTime->format('Y-m-d H:i:s'),
                'marked_by_admin' => null,
                'notes' => "QR scan by Audience ({$user['nama_lengkap']}) - QR Type: {$validation['qr_type']}, User Participation: {$userParticipationType} - WIB: " . $wibTime->format('Y-m-d H:i:s')
            ];

            $insertId = $this->absensiModel->insert($attendanceData);
            
            if (!$insertId) {
                throw new \Exception('Gagal mencatat kehadiran dalam database.');
            }

            $this->logActivity($userId, "Audience QR attendance - Event: {$event['title']}, QR Type: {$validation['qr_type']} - WIB: " . $wibTime->format('Y-m-d H:i:s'));

            $this->db->transComplete();

            if ($this->db->transStatus() === FALSE) {
                throw new \Exception('Database transaction failed.');
            }

            log_message('info', "Audience attendance successful - User: {$userId}, Event: {$eventId}, QR Type: {$validation['qr_type']}");

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
                        'qr_type' => $validation['qr_type'],
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
     * AJAX endpoint for QR scanner integration
     */
    public function scanAjax()
    {
        return $this->scan();
    }

    /**
     * Log user activity
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
            log_message('error', 'Failed to log audience activity: ' . $e->getMessage());
        }
    }
}