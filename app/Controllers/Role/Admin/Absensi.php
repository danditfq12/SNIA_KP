<?php

namespace App\Controllers\Role\Admin;

use App\Controllers\BaseController;
use App\Models\AbsensiModel;
use App\Models\EventModel;
use App\Models\PembayaranModel;
use App\Models\UserModel;

class Absensi extends BaseController
{
    protected $absensiModel;
    protected $eventModel;
    protected $pembayaranModel;
    protected $userModel;
    protected $db;

    public function __construct()
    {
        $this->absensiModel = new AbsensiModel();
        $this->eventModel = new EventModel();
        $this->pembayaranModel = new PembayaranModel();
        $this->userModel = new UserModel();
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        // Get all events for selection
        $events = $this->eventModel->where('is_active', true)
                                  ->orderBy('event_date', 'DESC')
                                  ->findAll();

        $selectedEventId = $this->request->getGet('event_id');
        
        // Get attendance data
        $absensiData = [];
        $currentEvent = null;
        $eventStats = [];
        
        if ($selectedEventId) {
            // Get event details
            $currentEvent = $this->eventModel->find($selectedEventId);
            
            if ($currentEvent) {
                // Get attendance with user and payment info
                $absensiData = $this->db->table('absensi')
                    ->select('
                        absensi.id_absensi,
                        absensi.waktu_scan,
                        absensi.status,
                        absensi.marked_by_admin,
                        absensi.notes,
                        absensi.qr_code,
                        users.nama_lengkap,
                        users.email,
                        users.role,
                        users.institusi,
                        users.no_hp,
                        events.title as event_title,
                        pembayaran.status as payment_status,
                        pembayaran.participation_type,
                        admin.nama_lengkap as admin_name
                    ')
                    ->join('users', 'users.id_user = absensi.id_user')
                    ->join('events', 'events.id = absensi.event_id', 'left')
                    ->join('pembayaran', 'pembayaran.id_user = absensi.id_user AND pembayaran.event_id = absensi.event_id', 'left')
                    ->join('users as admin', 'admin.id_user = absensi.marked_by_admin', 'left')
                    ->where('absensi.event_id', $selectedEventId)
                    ->orderBy('absensi.waktu_scan', 'DESC')
                    ->get()->getResultArray();

                // Get event statistics
                $totalRegistered = $this->pembayaranModel
                    ->where('event_id', $selectedEventId)
                    ->where('status', 'verified')
                    ->countAllResults();

                $totalAttended = $this->absensiModel
                    ->where('event_id', $selectedEventId)
                    ->where('status', 'hadir')
                    ->countAllResults();

                $attendanceByRole = $this->db->table('absensi')
                    ->select('users.role, COUNT(*) as count')
                    ->join('users', 'users.id_user = absensi.id_user')
                    ->where('absensi.event_id', $selectedEventId)
                    ->where('absensi.status', 'hadir')
                    ->groupBy('users.role')
                    ->get()->getResultArray();

                $eventStats = [
                    'total_registered' => $totalRegistered,
                    'total_attended' => $totalAttended,
                    'attendance_rate' => $totalRegistered > 0 ? round(($totalAttended / $totalRegistered) * 100, 2) : 0,
                    'by_role' => array_column($attendanceByRole, 'count', 'role')
                ];

                // Check if event is currently ongoing for QR generation
                $eventStart = $currentEvent['event_date'] . ' ' . $currentEvent['event_time'];
                $eventStartTime = strtotime($eventStart);
                $eventEndTime = $eventStartTime + (6 * 3600);
                $currentTime = time();
                
                $eventStats['is_ongoing'] = ($currentTime >= ($eventStartTime - 3600) && $currentTime <= $eventEndTime);
                $eventStats['event_status'] = $this->getEventStatus($eventStartTime, $eventEndTime, $currentTime);
            }
        }

        // Get today's events for quick access
        $todayEvents = $this->eventModel
            ->where('event_date', date('Y-m-d'))
            ->where('is_active', true)
            ->findAll();

        $data = [
            'events' => $events,
            'todayEvents' => $todayEvents,
            'selectedEventId' => $selectedEventId,
            'currentEvent' => $currentEvent,
            'absensiData' => $absensiData,
            'eventStats' => $eventStats
        ];

        return view('role/admin/absensi/index', $data);
    }

    /**
     * Generate multiple QR codes for different roles and participation types
     */
    public function generateMultipleQRCodes()
    {
        $eventId = $this->request->getPost('event_id');
        
        if (!$eventId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Event ID is required'
            ]);
        }

        $event = $this->eventModel->find($eventId);
        
        if (!$event) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Event not found'
            ]);
        }

        // Generate multiple QR codes for different combinations
        $qrCodes = $this->generateEventQRCodes($eventId, $event);
        
        // Check event status
        $eventStart = $event['event_date'] . ' ' . $event['event_time'];
        $eventStartTime = strtotime($eventStart);
        $eventEndTime = $eventStartTime + (6 * 3600);
        $currentTime = time();
        
        $eventStatus = $this->getEventStatus($eventStartTime, $eventEndTime, $currentTime);
        $isOngoing = ($currentTime >= ($eventStartTime - 3600) && $currentTime <= $eventEndTime);

        // Log QR generation
        $this->logActivity(session('id_user'), "Generated multiple QR codes for event: {$event['title']} (ID: {$eventId})");

        return $this->response->setJSON([
            'success' => true,
            'qr_codes' => $qrCodes,
            'event_title' => $event['title'],
            'event_date' => $event['event_date'],
            'event_time' => $event['event_time'],
            'event_status' => $eventStatus,
            'is_ongoing' => $isOngoing,
            'message' => 'QR Codes generated successfully',
            'scanner_url' => site_url('qr/scanner')
        ]);
    }

    /**
     * Generate QR codes for different role and participation type combinations
     */
    private function generateEventQRCodes($eventId, $event)
    {
        $baseUrl = site_url('qr/');
        $qrCodes = [];
        
        // Define QR code combinations based on event format
        $combinations = [
            [
                'role' => 'all',
                'participation' => 'all',
                'label' => 'Universal QR',
                'description' => 'Untuk semua role dan tipe partisipasi',
                'color' => '#6366f1',
                'icon' => 'fas fa-globe',
                'priority' => 1
            ]
        ];

        // Add role-specific QR codes
        $combinations[] = [
            'role' => 'presenter',
            'participation' => 'offline',
            'label' => 'Presenter',
            'description' => 'Khusus untuk presenter (offline only)',
            'color' => '#8b5cf6',
            'icon' => 'fas fa-chalkboard-teacher',
            'priority' => 2
        ];

        // Add audience QR codes based on event format
        if ($event['format'] === 'online' || $event['format'] === 'both') {
            $combinations[] = [
                'role' => 'audience',
                'participation' => 'online',
                'label' => 'Audience Online',
                'description' => 'Khusus untuk audience online',
                'color' => '#06b6d4',
                'icon' => 'fas fa-laptop',
                'priority' => 3
            ];
        }

        if ($event['format'] === 'offline' || $event['format'] === 'both') {
            $combinations[] = [
                'role' => 'audience',
                'participation' => 'offline',
                'label' => 'Audience Offline',
                'description' => 'Khusus untuk audience offline',
                'color' => '#10b981',
                'icon' => 'fas fa-users',
                'priority' => 4
            ];
        }

        // Generate QR codes
        foreach ($combinations as $combo) {
            $qrToken = $this->generateQRToken($eventId, $combo['role'], $combo['participation']);
            
            $qrCodes[] = [
                'token' => $qrToken,
                'url' => $baseUrl . $qrToken,
                'role' => $combo['role'],
                'participation_type' => $combo['participation'],
                'label' => $combo['label'],
                'description' => $combo['description'],
                'color' => $combo['color'],
                'icon' => $combo['icon'],
                'priority' => $combo['priority'],
                'qr_data_url' => $this->generateQRDataURL($qrToken, $combo['color'])
            ];
        }

        // Sort by priority
        usort($qrCodes, function($a, $b) {
            return $a['priority'] - $b['priority'];
        });

        return $qrCodes;
    }

    /**
     * Generate QR token with enhanced security
     */
    private function generateQRToken($eventId, $role = 'all', $participationType = 'all', $date = null)
    {
        if (!$date) {
            $date = date('Ymd');
        }

        // Generate security hash
        $secretKey = getenv('app.encryption.key') ?: 'SNIA_QR_SECRET_KEY_2024';
        $data = $eventId . $role . $participationType . $date . $secretKey;
        $securityHash = substr(hash('sha256', $data), 0, 16);
        
        return "EVENT_{$eventId}_{$role}_{$participationType}_{$date}_{$securityHash}";
    }

    /**
     * Generate QR code as data URL for immediate display
     */
    private function generateQRDataURL($token, $color = '#2563eb')
    {
        // This is a placeholder - in real implementation, you'd use a QR library
        // For now, we'll return a placeholder that the frontend can replace
        return "data:image/svg+xml;base64," . base64_encode($this->generateQRSVG($token, $color));
    }

    /**
     * Generate simple QR-like SVG as placeholder
     */
    private function generateQRSVG($token, $color)
    {
        return "<svg width='200' height='200' xmlns='http://www.w3.org/2000/svg'>
            <rect width='200' height='200' fill='white'/>
            <rect x='10' y='10' width='180' height='180' fill='none' stroke='{$color}' stroke-width='2'/>
            <text x='100' y='100' text-anchor='middle' fill='{$color}' font-family='Arial' font-size='12'>
                QR Code
            </text>
            <text x='100' y='120' text-anchor='middle' fill='{$color}' font-family='Arial' font-size='8'>
                {$token}
            </text>
        </svg>";
    }

    public function markAttendance()
    {
        $userId = $this->request->getPost('user_id');
        $eventId = $this->request->getPost('event_id');
        $notes = $this->request->getPost('notes');
        $adminId = session('id_user');

        // Validate inputs
        if (!$userId || !$eventId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'User ID and Event ID are required'
            ]);
        }

        // Get user details for better error messages
        $user = $this->userModel->find($userId);
        if (!$user) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'User not found'
            ]);
        }

        // Check if user has verified payment for this event
        $verifiedPayment = $this->pembayaranModel
            ->where('id_user', $userId)
            ->where('event_id', $eventId)
            ->where('status', 'verified')
            ->first();

        if (!$verifiedPayment) {
            return $this->response->setJSON([
                'success' => false,
                'message' => "User {$user['nama_lengkap']} does not have verified payment for this event"
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
                'message' => "User {$user['nama_lengkap']} already marked as attended for this event"
            ]);
        }

        // Get event details
        $event = $this->eventModel->find($eventId);
        $qrCode = 'ADMIN_' . $eventId . '_' . date('Ymd') . '_MANUAL_' . $userId;

        // Begin transaction
        $this->db->transStart();

        try {
            // Mark attendance
            $data = [
                'id_user' => $userId,
                'event_id' => $eventId,
                'qr_code' => $qrCode,
                'status' => 'hadir',
                'waktu_scan' => date('Y-m-d H:i:s'),
                'marked_by_admin' => $adminId,
                'notes' => $notes ?: 'Manual attendance marking by admin'
            ];

            $inserted = $this->absensiModel->insert($data);
            
            if (!$inserted) {
                throw new \Exception('Failed to insert attendance record');
            }

            // Log activity
            $this->logActivity($adminId, "Manually marked attendance for {$user['nama_lengkap']} in event: {$event['title']}");
            
            $this->db->transComplete();

            if ($this->db->transStatus() === FALSE) {
                throw new \Exception('Transaction failed');
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => "Attendance marked successfully for {$user['nama_lengkap']}"
            ]);

        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Mark attendance error: ' . $e->getMessage());
            
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to mark attendance: ' . $e->getMessage()
            ]);
        }
    }

    public function removeAttendance()
    {
        $attendanceId = $this->request->getPost('attendance_id');
        $adminId = session('id_user');

        if (!$attendanceId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Attendance ID is required'
            ]);
        }

        $attendance = $this->absensiModel->find($attendanceId);
        
        if (!$attendance) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Attendance record not found'
            ]);
        }

        // Get user info for logging
        $user = $this->userModel->find($attendance['id_user']);
        $event = $this->eventModel->find($attendance['event_id']);

        // Begin transaction
        $this->db->transStart();

        try {
            // Store attendance data for audit log
            $auditData = [
                'deleted_attendance_data' => json_encode($attendance),
                'deleted_by' => $adminId,
                'deleted_at' => date('Y-m-d H:i:s'),
                'reason' => 'Manual deletion by admin'
            ];

            // Try to insert audit log
            try {
                if ($this->db->tableExists('attendance_audit_log')) {
                    $this->db->table('attendance_audit_log')->insert($auditData);
                }
            } catch (\Exception $e) {
                log_message('info', 'Audit log not available: ' . $e->getMessage());
            }

            // Delete the attendance record
            if (!$this->absensiModel->delete($attendanceId)) {
                throw new \Exception('Failed to delete attendance record');
            }

            // Log activity
            $userName = $user ? $user['nama_lengkap'] : 'Unknown User';
            $eventName = $event ? $event['title'] : 'Unknown Event';
            $this->logActivity($adminId, "Removed attendance record for {$userName} in event: {$eventName}");
            
            $this->db->transComplete();

            if ($this->db->transStatus() === FALSE) {
                throw new \Exception('Transaction failed');
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Attendance record removed successfully'
            ]);

        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Remove attendance error: ' . $e->getMessage());
            
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to remove attendance record: ' . $e->getMessage()
            ]);
        }
    }

    public function bulkMarkAttendance()
    {
        $eventId = $this->request->getPost('event_id');
        $userIdsString = $this->request->getPost('user_ids');
        $adminId = session('id_user');

        if (!$eventId || !$userIdsString) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Event ID and user IDs are required'
            ]);
        }

        $userIds = array_filter(array_map('trim', explode(',', $userIdsString)));
        
        if (empty($userIds)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'No valid user IDs provided'
            ]);
        }

        $event = $this->eventModel->find($eventId);
        if (!$event) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Event not found'
            ]);
        }

        $qrCode = 'BULK_' . $eventId . '_' . date('Ymd') . '_ADMIN';
        
        $successCount = 0;
        $errorCount = 0;
        $errors = [];
        $successNames = [];

        // Begin transaction
        $this->db->transStart();

        try {
            foreach ($userIds as $userId) {
                $userId = (int) $userId;
                
                // Get user details
                $user = $this->userModel->find($userId);
                if (!$user) {
                    $errorCount++;
                    $errors[] = "User ID {$userId}: User not found";
                    continue;
                }

                // Check payment verification
                $verifiedPayment = $this->pembayaranModel
                    ->where('id_user', $userId)
                    ->where('event_id', $eventId)
                    ->where('status', 'verified')
                    ->first();

                if (!$verifiedPayment) {
                    $errorCount++;
                    $errors[] = "{$user['nama_lengkap']}: No verified payment";
                    continue;
                }

                // Check existing attendance
                $existingAttendance = $this->absensiModel
                    ->where('id_user', $userId)
                    ->where('event_id', $eventId)
                    ->first();

                if ($existingAttendance) {
                    $errorCount++;
                    $errors[] = "{$user['nama_lengkap']}: Already attended";
                    continue;
                }

                // Mark attendance
                $data = [
                    'id_user' => $userId,
                    'event_id' => $eventId,
                    'qr_code' => $qrCode,
                    'status' => 'hadir',
                    'waktu_scan' => date('Y-m-d H:i:s'),
                    'marked_by_admin' => $adminId,
                    'notes' => "Bulk attendance marking by admin - {$user['role']} ({$verifiedPayment['participation_type']})"
                ];

                if ($this->absensiModel->insert($data)) {
                    $successCount++;
                    $successNames[] = $user['nama_lengkap'];
                } else {
                    $errorCount++;
                    $errors[] = "{$user['nama_lengkap']}: Database insert failed";
                }
            }

            $this->db->transComplete();

            if ($this->db->transStatus() === FALSE) {
                throw new \Exception('Transaction failed');
            }

            // Log activity
            $successNamesStr = implode(', ', array_slice($successNames, 0, 5));
            if (count($successNames) > 5) {
                $successNamesStr .= ' and ' . (count($successNames) - 5) . ' others';
            }
            
            $this->logActivity($adminId, "Bulk marked attendance for {$successCount} users in event: {$event['title']} ({$successNamesStr})");

            return $this->response->setJSON([
                'success' => $successCount > 0,
                'message' => "Successfully marked {$successCount} attendances" . 
                           ($errorCount > 0 ? " with {$errorCount} errors" : ""),
                'details' => [
                    'success_count' => $successCount,
                    'error_count' => $errorCount,
                    'errors' => array_slice($errors, 0, 10),
                    'success_names' => $successNames
                ]
            ]);

        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Bulk attendance error: ' . $e->getMessage());
            
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Bulk operation failed: ' . $e->getMessage()
            ]);
        }
    }

    public function getEligibleUsers()
    {
        $eventId = $this->request->getGet('event_id');
        
        if (!$eventId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Event ID is required'
            ]);
        }

        try {
            // Get users with verified payments who haven't attended yet
            $eligibleUsers = $this->db->table('pembayaran')
                ->select('users.id_user, users.nama_lengkap, users.email, users.role, users.institusi, pembayaran.participation_type')
                ->join('users', 'users.id_user = pembayaran.id_user')
                ->where('pembayaran.event_id', $eventId)
                ->where('pembayaran.status', 'verified')
                ->where('users.status', 'aktif')
                ->whereNotIn('pembayaran.id_user', function($builder) use ($eventId) {
                    return $builder->select('id_user')
                                  ->from('absensi')
                                  ->where('event_id', $eventId);
                })
                ->orderBy('users.nama_lengkap', 'ASC')
                ->get()->getResultArray();

            return $this->response->setJSON([
                'success' => true,
                'users' => $eligibleUsers,
                'count' => count($eligibleUsers)
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Get eligible users error: ' . $e->getMessage());
            
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to fetch eligible users: ' . $e->getMessage(),
                'users' => []
            ]);
        }
    }

    public function export()
    {
        $eventId = $this->request->getGet('event_id');
        
        if (!$eventId) {
            return redirect()->back()->with('error', 'Event ID is required for export');
        }

        $event = $this->eventModel->find($eventId);
        
        if (!$event) {
            return redirect()->back()->with('error', 'Event not found');
        }

        // Get attendance data with all details
        $attendanceData = $this->db->table('absensi')
            ->select('
                users.nama_lengkap,
                users.email,
                users.role,
                users.no_hp,
                users.institusi,
                absensi.waktu_scan,
                absensi.status,
                absensi.qr_code,
                absensi.notes,
                pembayaran.jumlah as payment_amount,
                pembayaran.participation_type,
                admin.nama_lengkap as marked_by_admin_name
            ')
            ->join('users', 'users.id_user = absensi.id_user')
            ->join('pembayaran', 'pembayaran.id_user = absensi.id_user AND pembayaran.event_id = absensi.event_id', 'left')
            ->join('users as admin', 'admin.id_user = absensi.marked_by_admin', 'left')
            ->where('absensi.event_id', $eventId)
            ->orderBy('absensi.waktu_scan', 'ASC')
            ->get()->getResultArray();

        // Set headers for CSV download
        $filename = 'Attendance_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $event['title']) . '_' . date('Ymd_His') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: no-cache, must-revalidate');
        header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
        
        // Open output stream
        $output = fopen('php://output', 'w');
        
        // Add BOM for proper UTF-8 encoding in Excel
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Write CSV header
        fputcsv($output, [
            'No',
            'Nama Lengkap',
            'Email',
            'Role',
            'No. HP',
            'Institusi',
            'Waktu Absen',
            'Status',
            'QR Code',
            'Jumlah Pembayaran',
            'Tipe Partisipasi',
            'Ditandai Oleh',
            'Catatan'
        ]);
        
        // Write data rows
        $no = 1;
        foreach ($attendanceData as $row) {
            fputcsv($output, [
                $no++,
                $row['nama_lengkap'] ?? '',
                $row['email'] ?? '',
                ucfirst($row['role'] ?? ''),
                $row['no_hp'] ?? '',
                $row['institusi'] ?? '',
                $row['waktu_scan'] ? date('d/m/Y H:i:s', strtotime($row['waktu_scan'])) : '',
                ucfirst($row['status'] ?? ''),
                $row['qr_code'] ?? '',
                $row['payment_amount'] ? 'Rp ' . number_format($row['payment_amount'], 0, ',', '.') : '',
                ucfirst($row['participation_type'] ?? ''),
                $row['marked_by_admin_name'] ? 'Admin: ' . $row['marked_by_admin_name'] : 'QR Scan',
                $row['notes'] ?? ''
            ]);
        }
        
        fclose($output);
        
        // Log export activity
        $recordCount = $no - 1;
        $this->logActivity(session('id_user'), "Exported attendance data for event: {$event['title']} ({$recordCount} records)");
        
        exit;
    }

    public function liveStats()
    {
        $eventId = $this->request->getGet('event_id');
        
        if (!$eventId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Event ID is required'
            ]);
        }

        try {
            // Get real-time attendance statistics
            $totalRegistered = $this->pembayaranModel
                ->where('event_id', $eventId)
                ->where('status', 'verified')
                ->countAllResults();

            $totalAttended = $this->absensiModel
                ->where('event_id', $eventId)
                ->where('status', 'hadir')
                ->countAllResults();

            // Get recent attendance (last 10 minutes)
            $recentAttendance = $this->absensiModel
                ->where('event_id', $eventId)
                ->where('waktu_scan >=', date('Y-m-d H:i:s', strtotime('-10 minutes')))
                ->countAllResults();

            // Get attendance by scan method
            $qrScans = $this->absensiModel
                ->where('event_id', $eventId)
                ->where('marked_by_admin IS NULL')
                ->countAllResults();

            $manualMarks = $this->absensiModel
                ->where('event_id', $eventId)
                ->where('marked_by_admin IS NOT NULL')
                ->countAllResults();

            return $this->response->setJSON([
                'success' => true,
                'stats' => [
                    'total_registered' => $totalRegistered,
                    'total_attended' => $totalAttended,
                    'attendance_rate' => $totalRegistered > 0 ? round(($totalAttended / $totalRegistered) * 100, 2) : 0,
                    'recent_attendance' => $recentAttendance,
                    'qr_scans' => $qrScans,
                    'manual_marks' => $manualMarks,
                    'last_updated' => date('H:i:s')
                ]
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Live stats error: ' . $e->getMessage());
            
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to get live statistics'
            ]);
        }
    }

    private function getEventStatus($eventStartTime, $eventEndTime, $currentTime)
    {
        if ($currentTime < ($eventStartTime - 3600)) {
            return 'Belum Dimulai';
        } elseif ($currentTime >= ($eventStartTime - 3600) && $currentTime <= $eventEndTime) {
            return 'Sedang Berlangsung';
        } else {
            return 'Sudah Selesai';
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