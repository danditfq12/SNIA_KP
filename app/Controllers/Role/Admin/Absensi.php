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
        $absentData = [];
        $currentEvent = null;
        $eventStats = [];
        
        if ($selectedEventId) {
            // Get event details
            $currentEvent = $this->eventModel->find($selectedEventId);
            
            if ($currentEvent) {
                // Get attendance with user and payment info (YANG SUDAH HADIR)
                $absensiData = $this->db->table('absensi')
                    ->select('
                        absensi.id_absensi,
                        absensi.waktu_scan,
                        absensi.status,
                        absensi.marked_by_admin,
                        absensi.notes,
                        absensi.qr_code,
                        users.id_user,
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

                // Get users who are registered but haven't attended (YANG BELUM HADIR)
                // FIXED: Removed bukti_path column
                $absentData = $this->db->table('pembayaran')
                    ->select('
                        users.id_user,
                        users.nama_lengkap,
                        users.email,
                        users.role,
                        users.institusi,
                        users.no_hp,
                        pembayaran.participation_type,
                        pembayaran.jumlah as payment_amount
                    ')
                    ->join('users', 'users.id_user = pembayaran.id_user')
                    ->where('pembayaran.event_id', $selectedEventId)
                    ->where('pembayaran.status', 'verified')
                    ->where('users.status', 'aktif')
                    ->whereNotIn('pembayaran.id_user', function($builder) use ($selectedEventId) {
                        return $builder->select('id_user')
                                      ->from('absensi')
                                      ->where('event_id', $selectedEventId);
                    })
                    ->orderBy('users.nama_lengkap', 'ASC')
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

                // Count absent by role
                $absentByRole = $this->db->table('pembayaran')
                    ->select('users.role, COUNT(*) as count')
                    ->join('users', 'users.id_user = pembayaran.id_user')
                    ->where('pembayaran.event_id', $selectedEventId)
                    ->where('pembayaran.status', 'verified')
                    ->where('users.status', 'aktif')
                    ->whereNotIn('pembayaran.id_user', function($builder) use ($selectedEventId) {
                        return $builder->select('id_user')
                                      ->from('absensi')
                                      ->where('event_id', $selectedEventId);
                    })
                    ->groupBy('users.role')
                    ->get()->getResultArray();

                // Count absent by participation type
                $absentByParticipation = $this->db->table('pembayaran')
                    ->select('pembayaran.participation_type, COUNT(*) as count')
                    ->join('users', 'users.id_user = pembayaran.id_user')
                    ->where('pembayaran.event_id', $selectedEventId)
                    ->where('pembayaran.status', 'verified')
                    ->where('users.status', 'aktif')
                    ->whereNotIn('pembayaran.id_user', function($builder) use ($selectedEventId) {
                        return $builder->select('id_user')
                                      ->from('absensi')
                                      ->where('event_id', $selectedEventId);
                    })
                    ->groupBy('pembayaran.participation_type')
                    ->get()->getResultArray();

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
                    'total_absent' => count($absentData),
                    'attendance_rate' => $totalRegistered > 0 ? round(($totalAttended / $totalRegistered) * 100, 2) : 0,
                    'by_role' => array_column($attendanceByRole, 'count', 'role'),
                    'absent_by_role' => array_column($absentByRole, 'count', 'role'),
                    'absent_by_participation' => array_column($absentByParticipation, 'count', 'participation_type')
                ];

                // Enhanced event status calculation
                $eventStatus = $this->calculateEventStatus($currentEvent);
                $eventStats = array_merge($eventStats, $eventStatus);
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
            'absentData' => $absentData,
            'eventStats' => $eventStats
        ];

        return view('role/admin/absensi/index', $data);
    }

    /**
     * Enhanced event status calculation
     */
    private function calculateEventStatus($event)
    {
        if (!$event) {
            return [
                'is_ongoing' => false,
                'event_status' => 'Unknown',
                'badge_class' => 'bg-secondary'
            ];
        }

        try {
            date_default_timezone_set('Asia/Jakarta');
            
            $eventDateTime = new \DateTime($event['event_date'] . ' ' . $event['event_time']);
            $currentDateTime = new \DateTime();
            
            $timeDiff = $currentDateTime->getTimestamp() - $eventDateTime->getTimestamp();
            $hoursDiff = $timeDiff / 3600;
            
            log_message('info', 'Event Status Calculation - Event: ' . $event['title']);
            log_message('info', 'Event DateTime: ' . $eventDateTime->format('Y-m-d H:i:s'));
            log_message('info', 'Current DateTime: ' . $currentDateTime->format('Y-m-d H:i:s'));
            log_message('info', 'Hours Difference: ' . round($hoursDiff, 2));
            
            if ($hoursDiff < -1) {
                return [
                    'is_ongoing' => false,
                    'event_status' => 'Belum Dimulai',
                    'badge_class' => 'bg-secondary',
                    'can_scan' => false
                ];
            } elseif ($hoursDiff < 0) {
                return [
                    'is_ongoing' => false,
                    'event_status' => 'Segera Dimulai',
                    'badge_class' => 'bg-warning',
                    'can_scan' => true
                ];
            } elseif ($hoursDiff <= 4) {
                return [
                    'is_ongoing' => true,
                    'event_status' => 'Sedang Berlangsung',
                    'badge_class' => 'bg-success',
                    'can_scan' => true
                ];
            } else {
                return [
                    'is_ongoing' => false,
                    'event_status' => 'Sudah Selesai',
                    'badge_class' => 'bg-danger',
                    'can_scan' => false
                ];
            }
            
        } catch (\Exception $e) {
            log_message('error', 'Error calculating event status: ' . $e->getMessage());
            return [
                'is_ongoing' => false,
                'event_status' => 'Error',
                'badge_class' => 'bg-secondary',
                'can_scan' => false
            ];
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

    // Get attendance data (YANG SUDAH HADIR)
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

    // Get absent data (YANG BELUM HADIR)
    $absentData = $this->db->table('pembayaran')
        ->select('
            users.nama_lengkap,
            users.email,
            users.role,
            users.no_hp,
            users.institusi,
            pembayaran.participation_type,
            pembayaran.jumlah as payment_amount
        ')
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

    // Separate data by role and participation type
    $attendedGroups = [
        'audience_online' => [],
        'audience_offline' => [],
        'presenter_online' => [],
        'presenter_offline' => []
    ];

    $absentGroups = [
        'audience_online' => [],
        'audience_offline' => [],
        'presenter_online' => [],
        'presenter_offline' => []
    ];

    // Categorize attended data
    foreach ($attendanceData as $row) {
        $role = strtolower($row['role'] ?? 'audience');
        $participationType = strtolower($row['participation_type'] ?? 'offline');
        
        $key = $role . '_' . $participationType;
        if (isset($attendedGroups[$key])) {
            $attendedGroups[$key][] = $row;
        }
    }

    // Categorize absent data
    foreach ($absentData as $row) {
        $role = strtolower($row['role'] ?? 'audience');
        $participationType = strtolower($row['participation_type'] ?? 'offline');
        
        $key = $role . '_' . $participationType;
        if (isset($absentGroups[$key])) {
            $absentGroups[$key][] = $row;
        }
    }

    // Calculate statistics
    $stats = [
        'audience_online_attended' => count($attendedGroups['audience_online']),
        'audience_offline_attended' => count($attendedGroups['audience_offline']),
        'presenter_online_attended' => count($attendedGroups['presenter_online']),
        'presenter_offline_attended' => count($attendedGroups['presenter_offline']),
        'audience_online_absent' => count($absentGroups['audience_online']),
        'audience_offline_absent' => count($absentGroups['audience_offline']),
        'presenter_online_absent' => count($absentGroups['presenter_online']),
        'presenter_offline_absent' => count($absentGroups['presenter_offline']),
        'total_attended' => count($attendanceData),
        'total_absent' => count($absentData),
        'total_registered' => count($attendanceData) + count($absentData)
    ];

    // Set headers for CSV download
    $filename = 'Attendance_Report_' . preg_replace('/[^A-Za-z0-9_-]/', '_', $event['title']) . '_' . date('Ymd_His') . '.csv';
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Cache-Control: no-cache, must-revalidate');
    header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Write event header
    fputcsv($output, ['LAPORAN ABSENSI EVENT']);
    fputcsv($output, ['Event:', $event['title']]);
    fputcsv($output, ['Tanggal:', date('d F Y', strtotime($event['event_date']))]);
    fputcsv($output, ['Waktu:', date('H:i', strtotime($event['event_time'])) . ' WIB']);
    fputcsv($output, ['Format:', ucfirst($event['format'])]);
    fputcsv($output, ['Lokasi:', $event['location'] ?? '-']);
    fputcsv($output, []);
    
    // Write statistics summary
    fputcsv($output, ['RINGKASAN KEHADIRAN']);
    fputcsv($output, ['Total Terdaftar:', $stats['total_registered']]);
    fputcsv($output, ['Total Hadir:', $stats['total_attended']]);
    fputcsv($output, ['Total Belum Hadir:', $stats['total_absent']]);
    fputcsv($output, ['Tingkat Kehadiran:', round(($stats['total_attended'] / max($stats['total_registered'], 1)) * 100, 2) . '%']);
    fputcsv($output, []);
    fputcsv($output, ['Detail Kehadiran:']);
    fputcsv($output, ['Audience Online - Hadir:', $stats['audience_online_attended'], '| Belum Hadir:', $stats['audience_online_absent']]);
    fputcsv($output, ['Audience Offline - Hadir:', $stats['audience_offline_attended'], '| Belum Hadir:', $stats['audience_offline_absent']]);
    fputcsv($output, ['Presenter Online - Hadir:', $stats['presenter_online_attended'], '| Belum Hadir:', $stats['presenter_online_absent']]);
    fputcsv($output, ['Presenter Offline - Hadir:', $stats['presenter_offline_attended'], '| Belum Hadir:', $stats['presenter_offline_absent']]);
    fputcsv($output, []);
    fputcsv($output, []);
    
    // === SECTION 1: PESERTA YANG SUDAH HADIR ===
    fputcsv($output, ['============================================']);
    fputcsv($output, ['PESERTA YANG SUDAH HADIR']);
    fputcsv($output, ['============================================']);
    fputcsv($output, []);
    
    // 1.1 Audience Online - Hadir
    fputcsv($output, ['=== AUDIENCE ONLINE (HADIR) ===']);
    fputcsv($output, ['Total:', $stats['audience_online_attended']]);
    fputcsv($output, []);
    if (!empty($attendedGroups['audience_online'])) {
        fputcsv($output, ['No', 'Nama Lengkap', 'Email', 'No. HP', 'Institusi', 'Waktu Absen', 'Status', 'QR Code', 'Jumlah Pembayaran', 'Ditandai Oleh', 'Catatan']);
        $no = 1;
        foreach ($attendedGroups['audience_online'] as $row) {
            fputcsv($output, [
                $no++,
                $row['nama_lengkap'] ?? '',
                $row['email'] ?? '',
                $row['no_hp'] ?? '',
                $row['institusi'] ?? '',
                $row['waktu_scan'] ? date('d/m/Y H:i:s', strtotime($row['waktu_scan'])) : '',
                ucfirst($row['status'] ?? ''),
                $row['qr_code'] ?? '',
                $row['payment_amount'] ? 'Rp ' . number_format($row['payment_amount'], 0, ',', '.') : '',
                $row['marked_by_admin_name'] ? 'Admin: ' . $row['marked_by_admin_name'] : 'QR Scan',
                $row['notes'] ?? ''
            ]);
        }
    } else {
        fputcsv($output, ['Tidak ada data']);
    }
    fputcsv($output, []);
    fputcsv($output, []);
    
    // 1.2 Audience Offline - Hadir
    fputcsv($output, ['=== AUDIENCE OFFLINE (HADIR) ===']);
    fputcsv($output, ['Total:', $stats['audience_offline_attended']]);
    fputcsv($output, []);
    if (!empty($attendedGroups['audience_offline'])) {
        fputcsv($output, ['No', 'Nama Lengkap', 'Email', 'No. HP', 'Institusi', 'Waktu Absen', 'Status', 'QR Code', 'Jumlah Pembayaran', 'Ditandai Oleh', 'Catatan']);
        $no = 1;
        foreach ($attendedGroups['audience_offline'] as $row) {
            fputcsv($output, [
                $no++,
                $row['nama_lengkap'] ?? '',
                $row['email'] ?? '',
                $row['no_hp'] ?? '',
                $row['institusi'] ?? '',
                $row['waktu_scan'] ? date('d/m/Y H:i:s', strtotime($row['waktu_scan'])) : '',
                ucfirst($row['status'] ?? ''),
                $row['qr_code'] ?? '',
                $row['payment_amount'] ? 'Rp ' . number_format($row['payment_amount'], 0, ',', '.') : '',
                $row['marked_by_admin_name'] ? 'Admin: ' . $row['marked_by_admin_name'] : 'QR Scan',
                $row['notes'] ?? ''
            ]);
        }
    } else {
        fputcsv($output, ['Tidak ada data']);
    }
    fputcsv($output, []);
    fputcsv($output, []);
    
    // 1.3 Presenter Online - Hadir
    fputcsv($output, ['=== PRESENTER ONLINE (HADIR) ===']);
    fputcsv($output, ['Total:', $stats['presenter_online_attended']]);
    fputcsv($output, []);
    if (!empty($attendedGroups['presenter_online'])) {
        fputcsv($output, ['No', 'Nama Lengkap', 'Email', 'No. HP', 'Institusi', 'Waktu Absen', 'Status', 'QR Code', 'Jumlah Pembayaran', 'Ditandai Oleh', 'Catatan']);
        $no = 1;
        foreach ($attendedGroups['presenter_online'] as $row) {
            fputcsv($output, [
                $no++,
                $row['nama_lengkap'] ?? '',
                $row['email'] ?? '',
                $row['no_hp'] ?? '',
                $row['institusi'] ?? '',
                $row['waktu_scan'] ? date('d/m/Y H:i:s', strtotime($row['waktu_scan'])) : '',
                ucfirst($row['status'] ?? ''),
                $row['qr_code'] ?? '',
                $row['payment_amount'] ? 'Rp ' . number_format($row['payment_amount'], 0, ',', '.') : '',
                $row['marked_by_admin_name'] ? 'Admin: ' . $row['marked_by_admin_name'] : 'QR Scan',
                $row['notes'] ?? ''
            ]);
        }
    } else {
        fputcsv($output, ['Tidak ada data']);
    }
    fputcsv($output, []);
    fputcsv($output, []);
    
    // 1.4 Presenter Offline - Hadir
    fputcsv($output, ['=== PRESENTER OFFLINE (HADIR) ===']);
    fputcsv($output, ['Total:', $stats['presenter_offline_attended']]);
    fputcsv($output, []);
    if (!empty($attendedGroups['presenter_offline'])) {
        fputcsv($output, ['No', 'Nama Lengkap', 'Email', 'No. HP', 'Institusi', 'Waktu Absen', 'Status', 'QR Code', 'Jumlah Pembayaran', 'Ditandai Oleh', 'Catatan']);
        $no = 1;
        foreach ($attendedGroups['presenter_offline'] as $row) {
            fputcsv($output, [
                $no++,
                $row['nama_lengkap'] ?? '',
                $row['email'] ?? '',
                $row['no_hp'] ?? '',
                $row['institusi'] ?? '',
                $row['waktu_scan'] ? date('d/m/Y H:i:s', strtotime($row['waktu_scan'])) : '',
                ucfirst($row['status'] ?? ''),
                $row['qr_code'] ?? '',
                $row['payment_amount'] ? 'Rp ' . number_format($row['payment_amount'], 0, ',', '.') : '',
                $row['marked_by_admin_name'] ? 'Admin: ' . $row['marked_by_admin_name'] : 'QR Scan',
                $row['notes'] ?? ''
            ]);
        }
    } else {
        fputcsv($output, ['Tidak ada data']);
    }
    fputcsv($output, []);
    fputcsv($output, []);
    
    // === SECTION 2: PESERTA YANG BELUM HADIR ===
    fputcsv($output, ['============================================']);
    fputcsv($output, ['PESERTA YANG BELUM HADIR']);
    fputcsv($output, ['============================================']);
    fputcsv($output, []);
    
    // 2.1 Audience Online - Belum Hadir
    fputcsv($output, ['=== AUDIENCE ONLINE (BELUM HADIR) ===']);
    fputcsv($output, ['Total:', $stats['audience_online_absent']]);
    fputcsv($output, []);
    if (!empty($absentGroups['audience_online'])) {
        fputcsv($output, ['No', 'Nama Lengkap', 'Email', 'No. HP', 'Institusi', 'Jumlah Pembayaran', 'Status']);
        $no = 1;
        foreach ($absentGroups['audience_online'] as $row) {
            fputcsv($output, [
                $no++,
                $row['nama_lengkap'] ?? '',
                $row['email'] ?? '',
                $row['no_hp'] ?? '',
                $row['institusi'] ?? '',
                $row['payment_amount'] ? 'Rp ' . number_format($row['payment_amount'], 0, ',', '.') : '',
                'Belum Hadir'
            ]);
        }
    } else {
        fputcsv($output, ['Tidak ada data']);
    }
    fputcsv($output, []);
    fputcsv($output, []);
    
    // 2.2 Audience Offline - Belum Hadir
    fputcsv($output, ['=== AUDIENCE OFFLINE (BELUM HADIR) ===']);
    fputcsv($output, ['Total:', $stats['audience_offline_absent']]);
    fputcsv($output, []);
    if (!empty($absentGroups['audience_offline'])) {
        fputcsv($output, ['No', 'Nama Lengkap', 'Email', 'No. HP', 'Institusi', 'Jumlah Pembayaran', 'Status']);
        $no = 1;
        foreach ($absentGroups['audience_offline'] as $row) {
            fputcsv($output, [
                $no++,
                $row['nama_lengkap'] ?? '',
                $row['email'] ?? '',
                $row['no_hp'] ?? '',
                $row['institusi'] ?? '',
                $row['payment_amount'] ? 'Rp ' . number_format($row['payment_amount'], 0, ',', '.') : '',
                'Belum Hadir'
            ]);
        }
    } else {
        fputcsv($output, ['Tidak ada data']);
    }
    fputcsv($output, []);
    fputcsv($output, []);
    
    // 2.3 Presenter Online - Belum Hadir
    fputcsv($output, ['=== PRESENTER ONLINE (BELUM HADIR) ===']);
    fputcsv($output, ['Total:', $stats['presenter_online_absent']]);
    fputcsv($output, []);
    if (!empty($absentGroups['presenter_online'])) {
        fputcsv($output, ['No', 'Nama Lengkap', 'Email', 'No. HP', 'Institusi', 'Jumlah Pembayaran', 'Status']);
        $no = 1;
        foreach ($absentGroups['presenter_online'] as $row) {
            fputcsv($output, [
                $no++,
                $row['nama_lengkap'] ?? '',
                $row['email'] ?? '',
                $row['no_hp'] ?? '',
                $row['institusi'] ?? '',
                $row['payment_amount'] ? 'Rp ' . number_format($row['payment_amount'], 0, ',', '.') : '',
                'Belum Hadir'
            ]);
        }
    } else {
        fputcsv($output, ['Tidak ada data']);
    }
    fputcsv($output, []);
    fputcsv($output, []);
    
    // 2.4 Presenter Offline - Belum Hadir
    fputcsv($output, ['=== PRESENTER OFFLINE (BELUM HADIR) ===']);
    fputcsv($output, ['Total:', $stats['presenter_offline_absent']]);
    fputcsv($output, []);
    if (!empty($absentGroups['presenter_offline'])) {
        fputcsv($output, ['No', 'Nama Lengkap', 'Email', 'No. HP', 'Institusi', 'Jumlah Pembayaran', 'Status']);
        $no = 1;
        foreach ($absentGroups['presenter_offline'] as $row) {
            fputcsv($output, [
                $no++,
                $row['nama_lengkap'] ?? '',
                $row['email'] ?? '',
                $row['no_hp'] ?? '',
                $row['institusi'] ?? '',
                $row['payment_amount'] ? 'Rp ' . number_format($row['payment_amount'], 0, ',', '.') : '',
                'Belum Hadir'
            ]);
        }
    } else {
        fputcsv($output, ['Tidak ada data']);
    }
    fputcsv($output, []);
    fputcsv($output, []);
    
    // Add footer
    fputcsv($output, ['============================================']);
    fputcsv($output, ['Diekspor pada:', date('d/m/Y H:i:s')]);
    fputcsv($output, ['Diekspor oleh:', session('nama_lengkap')]);
    
    fclose($output);
    
    // Log export activity
    $this->logActivity(
        session('id_user'), 
        "Exported attendance report for event: {$event['title']} - " .
        "Total Registered: {$stats['total_registered']}, Attended: {$stats['total_attended']}, Absent: {$stats['total_absent']}"
    );
    
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
            $totalRegistered = $this->pembayaranModel
                ->where('event_id', $eventId)
                ->where('status', 'verified')
                ->countAllResults();

            $totalAttended = $this->absensiModel
                ->where('event_id', $eventId)
                ->where('status', 'hadir')
                ->countAllResults();

            $recentAttendance = $this->absensiModel
                ->where('event_id', $eventId)
                ->where('waktu_scan >=', date('Y-m-d H:i:s', strtotime('-10 minutes')))
                ->countAllResults();

            $qrScans = $this->absensiModel
                ->where('event_id', $eventId)
                ->where('marked_by_admin IS NULL')
                ->countAllResults();

            $manualMarks = $this->absensiModel
                ->where('event_id', $eventId)
                ->where('marked_by_admin IS NOT NULL')
                ->countAllResults();

            $totalAbsent = $totalRegistered - $totalAttended;

            return $this->response->setJSON([
                'success' => true,
                'stats' => [
                    'total_registered' => $totalRegistered,
                    'total_attended' => $totalAttended,
                    'total_absent' => $totalAbsent,
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

        $qrCodes = $this->generateEventQRCodes($eventId, $event);
        $eventStatus = $this->calculateEventStatus($event);

        $this->logActivity(session('id_user'), "Generated 3 QR codes for event: {$event['title']} (ID: {$eventId})");

        return $this->response->setJSON([
            'success' => true,
            'qr_codes' => $qrCodes,
            'event_title' => $event['title'],
            'event_date' => $event['event_date'],
            'event_time' => $event['event_time'],
            'event_status' => $eventStatus['event_status'],
            'is_ongoing' => $eventStatus['is_ongoing'],
            'badge_class' => $eventStatus['badge_class'],
            'can_scan' => $eventStatus['can_scan'],
            'message' => 'QR Codes generated successfully',
            'scanner_url' => site_url('qr/scanner')
        ]);
    }

    /**
     * Generate QR codes - 3 QR codes only
     */
    private function generateEventQRCodes($eventId, $event)
    {
        $baseUrl = site_url('qr/');
        $qrCodes = [];
        
        $combinations = [
            [
                'role' => 'all',
                'participation' => 'all',
                'label' => 'Universal QR',
                'description' => 'Untuk semua role dan tipe partisipasi (online & offline)',
                'color' => '#6366f1',
                'icon' => 'bi bi-globe',
                'priority' => 1
            ],
            [
                'role' => 'presenter',
                'participation' => 'all',
                'label' => 'Presenter',
                'description' => 'Khusus untuk presenter (online & offline)',
                'color' => '#8b5cf6',
                'icon' => 'bi bi-person-video3',
                'priority' => 2
            ],
            [
                'role' => 'audience',
                'participation' => 'all',
                'label' => 'Audience',
                'description' => 'Khusus untuk audience (online & offline)',
                'color' => '#10b981',
                'icon' => 'bi bi-people-fill',
                'priority' => 3
            ]
        ];

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

        usort($qrCodes, function($a, $b) {
            return $a['priority'] - $b['priority'];
        });

        return $qrCodes;
    }

    private function generateQRToken($eventId, $role = 'all', $participationType = 'all', $date = null)
    {
        if (!$date) {
            $date = date('Ymd');
        }

        $secretKey = getenv('app.encryption.key') ?: 'SNIA_QR_SECRET_KEY_2024';
        $data = $eventId . $role . $participationType . $date . $secretKey;
        $securityHash = substr(hash('sha256', $data), 0, 16);
        
        return "EVENT_{$eventId}_{$role}_{$participationType}_{$date}_{$securityHash}";
    }

    private function generateQRDataURL($token, $color = '#2563eb')
    {
        return "data:image/svg+xml;base64," . base64_encode($this->generateQRSVG($token, $color));
    }

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

    public function getEventStatus()
    {
        $eventId = $this->request->getGet('event_id');
        
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

        $eventStatus = $this->calculateEventStatus($event);

        return $this->response->setJSON([
            'success' => true,
            'status' => $eventStatus['event_status'],
            'is_ongoing' => $eventStatus['is_ongoing'],
            'badge_class' => $eventStatus['badge_class'],
            'can_scan' => $eventStatus['can_scan']
        ]);
    }

    /**
     * Get eligible users for bulk/manual marking via AJAX
     */
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
            // Get users with verified payment who haven't attended
            $eligibleUsers = $this->db->table('pembayaran')
                ->select('
                    users.id_user,
                    users.nama_lengkap,
                    users.email,
                    users.role,
                    users.institusi,
                    pembayaran.participation_type
                ')
                ->join('users', 'users.id_user = pembayaran.id_user')
                ->where('pembayaran.event_id', $eventId)
                ->where('pembayaran.status', 'verified')
                ->where('users.status', 'aktif')
                ->whereNotIn('pembayaran.id_user', function($builder) use ($eventId) {
                    return $builder->select('id_user')
                                  ->from('absensi')->where('event_id', $eventId);
                })
                ->orderBy('users.nama_lengkap', 'ASC')
                ->get()->getResultArray();

            return $this->response->setJSON([
                'success' => true,
                'users' => $eligibleUsers
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Get eligible users error: ' . $e->getMessage());
            
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Failed to fetch eligible users: ' . $e->getMessage()
            ]);
        }
    }

    public function markAttendance()
    {
        $userId = $this->request->getPost('user_id');
        $eventId = $this->request->getPost('event_id');
        $notes = $this->request->getPost('notes');
        $adminId = session('id_user');

        if (!$userId || !$eventId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'User ID and Event ID are required'
            ]);
        }

        $user = $this->userModel->find($userId);
        if (!$user) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'User not found'
            ]);
        }

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

        $event = $this->eventModel->find($eventId);
        $qrCode = 'ADMIN_' . $eventId . '_' . date('Ymd') . '_MANUAL_' . $userId;

        $this->db->transStart();

        try {
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

        $user = $this->userModel->find($attendance['id_user']);
        $event = $this->eventModel->find($attendance['event_id']);

        $this->db->transStart();

        try {
            $auditData = [
                'deleted_attendance_data' => json_encode($attendance),
                'deleted_by' => $adminId,
                'deleted_at' => date('Y-m-d H:i:s'),
                'reason' => 'Manual deletion by admin'
            ];

            try {
                if ($this->db->tableExists('attendance_audit_log')) {
                    $this->db->table('attendance_audit_log')->insert($auditData);
                }
            } catch (\Exception $e) {
                log_message('info', 'Audit log not available: ' . $e->getMessage());
            }

            if (!$this->absensiModel->delete($attendanceId)) {
                throw new \Exception('Failed to delete attendance record');
            }

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

        $this->db->transStart();

        try {
            foreach ($userIds as $userId) {
                $userId = (int) $userId;
                
                $user = $this->userModel->find($userId);
                if (!$user) {
                    $errorCount++;
                    $errors[] = "User ID {$userId}: User not found";
                    continue;
                }

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

                $existingAttendance = $this->absensiModel
                    ->where('id_user', $userId)
                    ->where('event_id', $eventId)
                    ->first();

                if ($existingAttendance) {
                    $errorCount++;
                    $errors[] = "{$user['nama_lengkap']}: Already attended";
                    continue;
                }

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