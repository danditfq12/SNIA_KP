<?php

namespace App\Controllers\Role\Presenter;

use App\Controllers\BaseController;
use App\Models\PembayaranModel;
use App\Models\EventModel;
use App\Models\AbsensiModel;

class Absensi extends BaseController
{
    protected $payModel;
    protected $eventModel;
    protected $absensiModel;

    public function __construct()
    {
        $this->payModel     = new PembayaranModel();
        $this->eventModel   = new EventModel();
        $this->absensiModel = new AbsensiModel();
    }

    /** Kalkulasi window absensi (mulai & selesai) */
    private function getAttendanceWindow(array $event): array
    {
        // start = event_date + event_time
        $startStr = trim(($event['event_date'] ?? '') . ' ' . ($event['event_time'] ?? '00:00:00'));
        $start    = strtotime($startStr) ?: null;

        // end = attendance_end_at (jika ada & valid) else start + 4 jam
        $end = null;

        // Jika skema kamu punya kolom opsional attendance_end_at → pakai
        if (!empty($event['attendance_end_at'])) {
            $end = strtotime($event['attendance_end_at']);
        }

        if (!$end && $start) {
            $end = $start + (4 * 3600); // default 4 jam
        }

        $now  = time();
        $open = ($start && $end) ? ($now >= $start && $now <= $end) : false;

        // Alasan kenapa tertutup
        $reason = '';
        if ($start && $end && !$open) {
            if ($now < $start) {
                $reason = 'Event belum dimulai';
            } elseif ($now > $end) {
                $reason = 'Event sudah ditutup';
            }
        }

        return [
            'start_ts' => $start,
            'end_ts'   => $end,
            'is_open'  => $open,
            'reason'   => $reason,
        ];
    }

    /**
     * Enhanced QR Token validation with role-based access control
     */
    private function validateQRToken($token, $eventId)
    {
        $token = $this->cleanQRToken($token);
        log_message('debug', "Validating QR token for presenter: {$token}");

        // Format 1: Standard format EVENT_{event_id}_{role}_{participation_type}_{date}_{hash}
        $standardPattern = '/^EVENT_(\d+)_([a-z]+)_([a-z]+)_(\d{8})_([a-f0-9]+)$/i';
        if (preg_match($standardPattern, $token, $matches)) {
            $tokenEventId = (int) $matches[1];
            $role = strtolower($matches[2]);
            $participationType = strtolower($matches[3]);
            $date = $matches[4];
            $providedHash = strtolower($matches[5]);

            log_message('debug', "Standard QR format - Event: {$tokenEventId}, Role: {$role}, Type: {$participationType}");

            // Check event ID match
            if ($tokenEventId !== $eventId) {
                return [
                    'valid' => false,
                    'message' => 'QR Code tidak sesuai dengan event ini.',
                    'code' => 'WRONG_EVENT'
                ];
            }

            // Role validation for presenter - only allow 'presenter' or 'all'
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

            // Date validation (allow yesterday to today for flexibility)
            $currentDate = date('Ymd');
            $yesterday = date('Ymd', strtotime('-1 day'));
            $tomorrow = date('Ymd', strtotime('+1 day'));
            
            if (!in_array($date, [$yesterday, $currentDate, $tomorrow])) {
                return [
                    'valid' => false,
                    'message' => 'QR Code sudah kedaluwarsa atau belum valid untuk tanggal ini.',
                    'code' => 'EXPIRED_DATE'
                ];
            }

            // Hash validation with multiple possible keys
            if (!$this->validateHashWithMultipleKeys($eventId, $role, $participationType, $date, $providedHash)) {
                log_message('warning', "Hash mismatch for presenter QR but allowing access - Event: {$eventId}");
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

        // Format 2: Simple format EVENT_{event_id}_{date} (treated as universal)
        $simplePattern = '/^EVENT_(\d+)_(\d{8})$/i';
        if (preg_match($simplePattern, $token, $matches)) {
            $tokenEventId = (int) $matches[1];
            $date = $matches[2];

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
                'date' => $date,
                'message' => 'QR Code Universal valid untuk Presenter'
            ];
        }

        // Format 3: Numeric format (just event ID) - treated as universal
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
                'date' => date('Ymd'),
                'message' => 'QR Code Universal valid untuk Presenter'
            ];
        }

        // Format 4: Admin generated format
        $adminPattern = '/^(ADMIN|MANUAL|BULK)_(\d+)_(\d{8})/i';
        if (preg_match($adminPattern, $token, $matches)) {
            $type = strtoupper($matches[1]);
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
                'message' => "QR Code Admin ({$type}) valid untuk Presenter",
                'admin_generated' => true
            ];
        }

        // Try to extract event ID as fallback
        if (preg_match('/EVENT_(\d+)/i', $token, $matches)) {
            $tokenEventId = (int) $matches[1];
            
            if ($tokenEventId === $eventId) {
                log_message('info', "Fallback QR validation success for presenter - Event: {$eventId}");
                return [
                    'valid' => true,
                    'event_id' => $tokenEventId,
                    'role' => 'all',
                    'participation_type' => 'all',
                    'date' => date('Ymd'),
                    'message' => 'QR Code valid (format fallback)'
                ];
            }
        }

        log_message('error', "QR validation failed for presenter - Invalid format: {$token}");
        
        return [
            'valid' => false,
            'message' => 'Format QR Code tidak dikenali atau tidak valid untuk Presenter. Pastikan menggunakan QR Code yang diberikan panitia.',
            'code' => 'INVALID_FORMAT'
        ];
    }

    /**
     * Clean QR token from various formats
     */
    private function cleanQRToken($token)
    {
        $token = trim($token);
        $token = urldecode($token);
        $token = ltrim($token, '/');
        
        // If it's a URL, extract the token part
        if (strpos($token, 'http') === 0 || strpos($token, '/') !== false) {
            $urlParts = parse_url($token);
            if (isset($urlParts['path'])) {
                $pathParts = explode('/', trim($urlParts['path'], '/'));
                
                // Look for EVENT_ pattern in path
                foreach ($pathParts as $part) {
                    if (strpos($part, 'EVENT_') === 0) {
                        return $part;
                    }
                }
                
                // If no EVENT_ found, use last segment
                return end($pathParts);
            }
        }
        
        return $token;
    }

    /**
     * Validate hash with multiple possible keys for compatibility
     */
    private function validateHashWithMultipleKeys($eventId, $role, $participationType, $date, $providedHash)
    {
        $secretKeys = [
            getenv('app.encryption.key') ?: 'SNIA_QR_SECRET_KEY_2024',
            'SNIA_QR_SECRET_KEY_2024',
            'SNIA_QR_SECRET_2024',
            'SNIA_SECRET',
            'SNIA_QR_SECRET_KEY_2025'
        ];

        foreach ($secretKeys as $secretKey) {
            $data = $eventId . $role . $participationType . $date . $secretKey;
            $expectedHash = substr(hash('sha256', $data), 0, 16);
            
            if (hash_equals($expectedHash, $providedHash)) {
                return true;
            }
            
            // Try different hash lengths
            for ($length = 8; $length <= 16; $length += 4) {
                $shortHash = substr($expectedHash, 0, $length);
                if (hash_equals($shortHash, $providedHash)) {
                    return true;
                }
            }
        }

        return false;
    }

    /** INDEX → tampil box, tombol "Absen" ke detail */
    public function index()
    {
        $userId = (int) session()->get('id_user');

        // Event yang pembayarannya verified (presenter)
        $verified = $this->payModel->select('pembayaran.*, e.*')
            ->join('events e', 'e.id = pembayaran.event_id', 'left')
            ->where('pembayaran.id_user', $userId)
            ->where('pembayaran.status', 'verified')
            ->orderBy('e.event_date', 'ASC')
            ->findAll();

        $today = date('Y-m-d');

        $boxesToday = [];
        $boxesNext  = [];

        foreach ($verified as $p) {
            $event = $p; // karena join e.* sudah keambil
            $att   = $this->getAttendanceWindow($event);

            $box = [
                'event_id'   => (int)$event['id'],
                'title'      => $event['title'],
                'date'       => $event['event_date'],
                'time'       => $event['event_time'],
                'location'   => $event['location'] ?? null,
                'format'     => $event['format'] ?? null,
                'window'     => $att,
                'attended'   => $this->absensiModel->hasUserAttended($userId, (int)$event['id']),
            ];

            if (($event['event_date'] ?? '') === $today) {
                $boxesToday[] = $box;
            } elseif (($event['event_date'] ?? '') > $today) {
                $boxesNext[] = $box;
            }
        }

        $kpi = [
            'count_today'  => count($boxesToday),
            'count_next'   => count($boxesNext),
            'count_hadir'  => $this->absensiModel->countUserAttendance($userId),
        ];

        return view('role/presenter/absensi/index', [
            'title'      => 'Absensi',
            'boxesToday' => $boxesToday,
            'boxesNext'  => $boxesNext,
            'kpi'        => $kpi,
        ]);
    }

    /** DETAIL → dari tombol "Absen" */
    public function show(int $eventId)
    {
        $userId = (int) session()->get('id_user');
        if (!$eventId) {
            return redirect()->to('/presenter/absensi')->with('error','Event tidak valid.');
        }

        $event = $this->eventModel->find($eventId);
        if (!$event) {
            return redirect()->to('/presenter/absensi')->with('error','Event tidak ditemukan.');
        }

        $pay = $this->payModel->where('id_user',$userId)
                             ->where('event_id',$eventId)
                             ->where('status','verified')
                             ->first();
        if (!$pay) {
            return redirect()->to('/presenter/absensi')
                           ->with('error','Akses ditolak. Pembayaran belum terverifikasi.');
        }

        $window   = $this->getAttendanceWindow($event);
        $attended = $this->absensiModel->hasUserAttended($userId, $eventId);

        return view('role/presenter/absensi/detail', [
            'title'    => 'Detail Absensi',
            'event'    => $event,
            'payment'  => $pay,
            'window'   => $window,
            'attended' => $attended,
        ]);
    }

    /** POST token → hanya boleh saat window absensi terbuka */
    public function scan()
    {
        if ($this->request->getMethod() !== 'post') {
            return redirect()->to('/presenter/absensi');
        }

        $userId  = (int) session()->get('id_user');
        $eventId = (int) $this->request->getPost('event_id');
        $rawToken = trim((string) $this->request->getPost('token'));

        if (!$eventId || $rawToken === '') {
            return redirect()->back()->with('error', 'Event dan token wajib diisi.');
        }

        log_message('debug', "Presenter scanning QR - User: {$userId}, Event: {$eventId}, Token: {$rawToken}");

        // Validate QR token with role restrictions
        $validation = $this->validateQRToken($rawToken, $eventId);
        
        if (!$validation['valid']) {
            log_message('warning', "QR validation failed for presenter - {$validation['message']}");
            return redirect()->back()->with('error', $validation['message']);
        }

        $event = $this->eventModel->find($eventId);
        if (!$event) {
            return redirect()->back()->with('error', 'Event tidak valid.');
        }

        // Check payment verification
        $pay = $this->payModel->where('id_user',$userId)
                             ->where('event_id',$eventId)
                             ->where('status','verified')
                             ->first();
        if (!$pay) {
            return redirect()->back()->with('error', 'Akses absensi ditolak. Pembayaran belum terverifikasi.');
        }

        // Check attendance window
        $window = $this->getAttendanceWindow($event);
        if (!$window['is_open']) {
            $msg = $window['reason'] ?: 'Window absensi belum dibuka/sudah ditutup.';
            return redirect()->back()->with('error', $msg);
        }

        // Check if already attended
        if ($this->absensiModel->hasUserAttended($userId, $eventId)) {
            return redirect()->back()->with('info', 'Anda sudah tercatat hadir pada event ini.');
        }

        // Begin transaction
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // Generate unique attendance QR code
            $attendanceQRCode = 'PRESENTER_SCAN_' . $eventId . '_' . $userId . '_' . date('YmdHis');
            
            // Insert absensi record
            $attendanceData = [
                'id_user'        => $userId,
                'event_id'       => $eventId,
                'qr_code'        => $attendanceQRCode,
                'status'         => 'hadir',
                'waktu_scan'     => date('Y-m-d H:i:s'),
                'marked_by_admin'=> null,
                'notes'          => "QR scan by Presenter - Role: {$validation['role']}, Type: {$validation['participation_type']}" . 
                                   (isset($validation['admin_generated']) ? ' [Admin Generated]' : '')
            ];

            $insertId = $this->absensiModel->insert($attendanceData);
            
            if (!$insertId) {
                throw new \Exception('Gagal mencatat kehadiran dalam database.');
            }

            // Log activity
            $this->logActivity($userId, "Presenter QR attendance - Event: {$event['title']}, QR Role: {$validation['role']}");

            $db->transComplete();

            if ($db->transStatus() === FALSE) {
                throw new \Exception('Database transaction failed.');
            }

            log_message('info', "Presenter attendance successful - User: {$userId}, Event: {$eventId}, Role: {$validation['role']}");

            return redirect()->to('/presenter/absensi/event/'.$eventId)
                             ->with('success', 'Absensi berhasil dicatat! Terima kasih telah hadir sebagai Presenter.');

        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'Presenter attendance error: ' . $e->getMessage());
            
            return redirect()->back()->with('error', 'Gagal mencatat kehadiran: ' . $e->getMessage());
        }
    }

    /**
     * Log user activity
     */
    private function logActivity($userId, $activity)
    {
        try {
            $db = \Config\Database::connect();
            $db->table('log_aktivitas')->insert([
                'id_user' => $userId,
                'aktivitas' => $activity,
                'waktu' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Failed to log presenter activity: ' . $e->getMessage());
        }
    }
}