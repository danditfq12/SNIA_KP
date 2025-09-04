<?php
namespace App\Models;
use CodeIgniter\Model;

class AbsensiModel extends Model
{
    protected $table      = 'absensi';
    protected $primaryKey = 'id_absensi';
    
    protected $allowedFields = [
        'id_user', 'event_id', 'qr_code', 'status', 'waktu_scan', 
        'marked_by_admin', 'notes'
    ];
    
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';

    // Get absensi with complete user and event info
    public function getAbsensiWithDetails($eventId = null, $limit = null)
    {
        $builder = $this->db->table($this->table)
                           ->select('
                               absensi.*,
                               users.nama_lengkap, 
                               users.email, 
                               users.role,
                               users.institusi,
                               users.no_hp,
                               events.title as event_title,
                               events.event_date,
                               events.event_time,
                               pembayaran.status as payment_status,
                               pembayaran.participation_type,
                               pembayaran.jumlah as payment_amount,
                               admin.nama_lengkap as admin_name
                           ')
                           ->join('users', 'users.id_user = absensi.id_user')
                           ->join('events', 'events.id = absensi.event_id', 'left')
                           ->join('pembayaran', 'pembayaran.id_user = absensi.id_user AND pembayaran.event_id = absensi.event_id', 'left')
                           ->join('users as admin', 'admin.id_user = absensi.marked_by_admin', 'left')
                           ->orderBy('absensi.waktu_scan', 'DESC');
        
        if ($eventId) {
            $builder->where('absensi.event_id', $eventId);
        }
        
        if ($limit) {
            $builder->limit($limit);
        }
        
        $result = $builder->get()->getResultArray();
        return $result ?: []; // Pastikan selalu return array, bukan null
    }

    // Get attendance statistics for an event
    public function getEventAttendanceStats($eventId)
    {
        $result = $this->db->query("
            SELECT 
                COUNT(*) as total_attendance,
                COUNT(CASE WHEN status = 'hadir' THEN 1 END) as total_present,
                COUNT(CASE WHEN marked_by_admin IS NULL THEN 1 END) as qr_scans,
                COUNT(CASE WHEN marked_by_admin IS NOT NULL THEN 1 END) as manual_marks,
                MIN(waktu_scan) as first_attendance,
                MAX(waktu_scan) as last_attendance
            FROM absensi 
            WHERE event_id = ?
        ", [$eventId])->getRowArray();
        
        // Return default values if no data found
        return $result ?: [
            'total_attendance' => 0,
            'total_present' => 0,
            'qr_scans' => 0,
            'manual_marks' => 0,
            'first_attendance' => null,
            'last_attendance' => null
        ];
    }

    // Check if user already attended event
    public function hasUserAttended($userId, $eventId)
    {
        if (!$userId || !$eventId) {
            return false;
        }
        
        return $this->where('id_user', $userId)
                   ->where('event_id', $eventId)
                   ->countAllResults() > 0;
    }

    // Get user's attendance history
    public function getUserAttendanceHistory($userId)
    {
        if (!$userId) {
            return [];
        }
        
        $result = $this->select('absensi.*, events.title as event_title, events.event_date')
                       ->join('events', 'events.id = absensi.event_id', 'left')
                       ->where('absensi.id_user', $userId)
                       ->orderBy('absensi.waktu_scan', 'DESC')
                       ->findAll();
        
        return $result ?: []; // Pastikan selalu return array
    }

    // Get user's attendance for specific date
    public function getUserAttendanceByDate($userId, $date = null)
    {
        if (!$userId) {
            return [];
        }
        
        $date = $date ?: date('Y-m-d');
        
        $result = $this->select('absensi.*, events.title as event_title')
                       ->join('events', 'events.id = absensi.event_id', 'left')
                       ->where('absensi.id_user', $userId)
                       ->where('DATE(absensi.waktu_scan)', $date)
                       ->findAll();
        
        return $result ?: [];
    }

    // Get attendance by event ID
    public function getAttendanceByEvent($eventId)
    {
        if (!$eventId) {
            return [];
        }
        
        $result = $this->select('
                        absensi.*,
                        users.nama_lengkap,
                        users.email,
                        users.role,
                        users.institusi
                    ')
                    ->join('users', 'users.id_user = absensi.id_user')
                    ->where('absensi.event_id', $eventId)
                    ->orderBy('absensi.waktu_scan', 'DESC')
                    ->findAll();
        
        return $result ?: [];
    }

    // Get recent attendance
    public function getRecentAttendance($limit = 10)
    {
        $result = $this->select('
                        absensi.*,
                        users.nama_lengkap,
                        users.role,
                        events.title as event_title
                    ')
                    ->join('users', 'users.id_user = absensi.id_user')
                    ->join('events', 'events.id = absensi.event_id', 'left')
                    ->orderBy('absensi.waktu_scan', 'DESC')
                    ->limit($limit)
                    ->findAll();
        
        return $result ?: [];
    }

    // Count total attendance by user
    public function countUserAttendance($userId)
    {
        if (!$userId) {
            return 0;
        }
        
        return $this->where('id_user', $userId)
                   ->where('status', 'hadir')
                   ->countAllResults();
    }

    // Get attendance statistics by role
    public function getAttendanceStatsByRole($eventId = null)
    {
        $builder = $this->db->table($this->table)
                           ->select('
                               users.role,
                               COUNT(*) as total_attendance,
                               COUNT(CASE WHEN absensi.status = "hadir" THEN 1 END) as present_count
                           ')
                           ->join('users', 'users.id_user = absensi.id_user')
                           ->groupBy('users.role');
        
        if ($eventId) {
            $builder->where('absensi.event_id', $eventId);
        }
        
        $result = $builder->get()->getResultArray();
        return $result ?: [];
    }

    // Check if QR code already used
    public function isQRCodeUsed($qrCode, $eventId = null)
    {
        if (!$qrCode) {
            return false;
        }
        
        $builder = $this->where('qr_code', $qrCode);
        
        if ($eventId) {
            $builder->where('event_id', $eventId);
        }
        
        return $builder->countAllResults() > 0;
    }

    // Delete user attendance (with audit)
    public function deleteUserAttendance($attendanceId, $deletedBy = null, $reason = null)
    {
        if (!$attendanceId) {
            return false;
        }
        
        // Get attendance data before deletion for audit
        $attendanceData = $this->find($attendanceId);
        
        if (!$attendanceData) {
            return false;
        }
        
        $this->db->transStart();
        
        try {
            // Log to audit table if exists
            if ($this->db->tableExists('attendance_audit_log')) {
                $this->db->table('attendance_audit_log')->insert([
                    'deleted_attendance_data' => json_encode($attendanceData),
                    'deleted_by' => $deletedBy,
                    'reason' => $reason,
                    'deleted_at' => date('Y-m-d H:i:s')
                ]);
            }
            
            // Delete the attendance record
            $result = $this->delete($attendanceId);
            
            $this->db->transComplete();
            
            return $this->db->transStatus() !== FALSE && $result;
            
        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Failed to delete attendance: ' . $e->getMessage());
            return false;
        }
    }

    // Bulk insert attendance (untuk admin)
    public function bulkInsertAttendance($attendanceData)
    {
        if (empty($attendanceData) || !is_array($attendanceData)) {
            return false;
        }
        
        $this->db->transStart();
        
        try {
            $insertCount = 0;
            
            foreach ($attendanceData as $data) {
                // Validate required fields
                if (!isset($data['id_user'], $data['event_id'])) {
                    continue;
                }
                
                // Set default values
                $data['qr_code'] = $data['qr_code'] ?? 'BULK_' . uniqid();
                $data['status'] = $data['status'] ?? 'hadir';
                $data['waktu_scan'] = $data['waktu_scan'] ?? date('Y-m-d H:i:s');
                $data['marked_by_admin'] = $data['marked_by_admin'] ?? null;
                $data['notes'] = $data['notes'] ?? 'Bulk insert by admin';
                
                if ($this->insert($data)) {
                    $insertCount++;
                }
            }
            
            $this->db->transComplete();
            
            return $this->db->transStatus() !== FALSE ? $insertCount : 0;
            
        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Bulk insert attendance failed: ' . $e->getMessage());
            return 0;
        }
    }
}