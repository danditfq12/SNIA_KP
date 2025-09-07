<?php

namespace App\Models;

use CodeIgniter\Model;

class LogAktivitasModel extends Model
{
    protected $table         = 'log_aktivitas';
    protected $primaryKey    = 'id_log';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'id_user', 'aktivitas', 'waktu'
    ];

    /**
     * Get activity logs with user information
     */
    public function getLogsWithUser($limit = null, $userId = null)
    {
        $builder = $this->select('
            log_aktivitas.*,
            users.nama_lengkap,
            users.email,
            users.role
        ')
        ->join('users', 'users.id_user = log_aktivitas.id_user', 'left')
        ->orderBy('log_aktivitas.waktu', 'DESC');

        if ($userId) {
            $builder->where('log_aktivitas.id_user', $userId);
        }

        if ($limit) {
            $builder->limit($limit);
        }

        return $builder->findAll();
    }

    /**
     * Get logs by date range
     */
    public function getLogsByDateRange($startDate, $endDate, $userId = null)
    {
        $builder = $this->select('
            log_aktivitas.*,
            users.nama_lengkap,
            users.email,
            users.role
        ')
        ->join('users', 'users.id_user = log_aktivitas.id_user', 'left')
        ->where('DATE(log_aktivitas.waktu) >=', $startDate)
        ->where('DATE(log_aktivitas.waktu) <=', $endDate)
        ->orderBy('log_aktivitas.waktu', 'DESC');

        if ($userId) {
            $builder->where('log_aktivitas.id_user', $userId);
        }

        return $builder->findAll();
    }

    /**
     * Get user activity statistics
     */
    public function getUserActivityStats($userId)
    {
        $today = date('Y-m-d');
        $thisWeek = date('Y-m-d', strtotime('-7 days'));
        $thisMonth = date('Y-m-d', strtotime('-30 days'));

        return [
            'today' => $this->where('id_user', $userId)
                          ->where('DATE(waktu)', $today)
                          ->countAllResults(),
            'this_week' => $this->where('id_user', $userId)
                              ->where('DATE(waktu) >=', $thisWeek)
                              ->countAllResults(),
            'this_month' => $this->where('id_user', $userId)
                               ->where('DATE(waktu) >=', $thisMonth)
                               ->countAllResults(),
            'total' => $this->where('id_user', $userId)
                          ->countAllResults()
        ];
    }

    /**
     * Get recent activities for dashboard
     */
    public function getRecentActivities($limit = 10)
    {
        return $this->select('
            log_aktivitas.*,
            users.nama_lengkap,
            users.role
        ')
        ->join('users', 'users.id_user = log_aktivitas.id_user', 'left')
        ->orderBy('log_aktivitas.waktu', 'DESC')
        ->limit($limit)
        ->findAll();
    }

    /**
     * Log user activity - helper method
     */
    public function logActivity($userId, $activity)
    {
        return $this->insert([
            'id_user' => $userId,
            'aktivitas' => $activity,
            'waktu' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Clean old logs (older than specified days)
     */
    public function cleanOldLogs($days = 90)
    {
        $cutoffDate = date('Y-m-d', strtotime("-{$days} days"));
        
        return $this->where('DATE(waktu) <', $cutoffDate)->delete();
    }

    /**
     * Get activity summary by action type
     */
    public function getActivitySummary($startDate = null, $endDate = null)
    {
        $builder = $this->select('
            CASE 
                WHEN aktivitas LIKE "%login%" THEN "Login/Logout"
                WHEN aktivitas LIKE "%upload%" THEN "Upload Files"
                WHEN aktivitas LIKE "%download%" THEN "Download Files"
                WHEN aktivitas LIKE "%pembayaran%" THEN "Payment Activities"
                WHEN aktivitas LIKE "%abstrak%" THEN "Abstract Management"
                WHEN aktivitas LIKE "%absensi%" THEN "Attendance"
                WHEN aktivitas LIKE "%voucher%" THEN "Voucher Management"
                WHEN aktivitas LIKE "%dokumen%" THEN "Document Management"
                ELSE "Other Activities"
            END as activity_type,
            COUNT(*) as count
        ')
        ->groupBy('activity_type')
        ->orderBy('count', 'DESC');

        if ($startDate) {
            $builder->where('DATE(waktu) >=', $startDate);
        }

        if ($endDate) {
            $builder->where('DATE(waktu) <=', $endDate);
        }

        return $builder->findAll();
    }

    /**
     * Get most active users
     */
    public function getMostActiveUsers($limit = 10, $days = 30)
    {
        $startDate = date('Y-m-d', strtotime("-{$days} days"));

        return $this->select('
            log_aktivitas.id_user,
            users.nama_lengkap,
            users.email,
            users.role,
            COUNT(*) as activity_count,
            MAX(log_aktivitas.waktu) as last_activity
        ')
        ->join('users', 'users.id_user = log_aktivitas.id_user', 'left')
        ->where('DATE(log_aktivitas.waktu) >=', $startDate)
        ->groupBy('log_aktivitas.id_user')
        ->orderBy('activity_count', 'DESC')
        ->limit($limit)
        ->findAll();
    }

    /**
     * Export logs to CSV format
     */
    public function exportLogs($startDate = null, $endDate = null, $userId = null)
    {
        $builder = $this->select('
            log_aktivitas.waktu,
            users.nama_lengkap,
            users.email,
            users.role,
            log_aktivitas.aktivitas
        ')
        ->join('users', 'users.id_user = log_aktivitas.id_user', 'left')
        ->orderBy('log_aktivitas.waktu', 'DESC');

        if ($startDate) {
            $builder->where('DATE(log_aktivitas.waktu) >=', $startDate);
        }

        if ($endDate) {
            $builder->where('DATE(log_aktivitas.waktu) <=', $endDate);
        }

        if ($userId) {
            $builder->where('log_aktivitas.id_user', $userId);
        }

        return $builder->findAll();
    }
}