<?php
namespace App\Models;
use CodeIgniter\Model;

class AbsensiModel extends Model
{
    protected $table      = 'absensi';
    protected $primaryKey = 'id_absensi';
    
    protected $allowedFields = [
        'id_user', 'qr_code', 'status', 'waktu_scan'
    ];
    
    // Get absensi with user info
    public function getAbsensiWithUser()
    {
        return $this->db->table($this->table)
                       ->select('absensi.*, users.nama_lengkap, users.email, users.role')
                       ->join('users', 'users.id_user = absensi.id_user')
                       ->orderBy('absensi.waktu_scan', 'DESC')
                       ->get()->getResultArray();
    }
}