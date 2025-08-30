<?php

namespace App\Models;
use CodeIgniter\Model;

class AbstrakModel extends Model
{
    protected $table      = 'abstrak';
    protected $primaryKey = 'id_abstrak';
    
    protected $allowedFields = [
        'id_user', 'id_kategori', 'judul', 'file_abstrak', 
        'status', 'tanggal_upload', 'revisi_ke'
    ];
    
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    
    // Get abstrak with user and category info
    public function getAbstrakWithDetails($limit = null)
    {
        $builder = $this->db->table($this->table)
                           ->select('abstrak.*, users.nama_lengkap, users.email, kategori_abstrak.nama_kategori')
                           ->join('users', 'users.id_user = abstrak.id_user')
                           ->join('kategori_abstrak', 'kategori_abstrak.id_kategori = abstrak.id_kategori')
                           ->orderBy('abstrak.tanggal_upload', 'DESC');
        
        if ($limit) {
            $builder->limit($limit);
        }
        
        return $builder->get()->getResultArray();
    }
}