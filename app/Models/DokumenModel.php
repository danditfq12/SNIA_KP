<?php
namespace App\Models;
use CodeIgniter\Model;

class DokumenModel extends Model
{
    protected $table      = 'dokumen';
    protected $primaryKey = 'id_dokumen';
    
    protected $allowedFields = [
        'id_user', 'tipe', 'file_path', 'syarat', 'uploaded_at'
    ];
    
    // Get dokumen with user info
    public function getDokumenWithUser($tipe = null)
    {
        $builder = $this->db->table($this->table)
                           ->select('dokumen.*, users.nama_lengkap, users.email, users.role')
                           ->join('users', 'users.id_user = dokumen.id_user')
                           ->orderBy('dokumen.uploaded_at', 'DESC');
        
        if ($tipe) {
            $builder->where('dokumen.tipe', $tipe);
        }
        
        return $builder->get()->getResultArray();
    }
}