<?php
namespace App\Models;
use CodeIgniter\Model;

class PembayaranModel extends Model
{
    protected $table      = 'pembayaran';
    protected $primaryKey = 'id_pembayaran';
    
    protected $allowedFields = [
        'id_user', 'metode', 'jumlah', 'bukti_bayar', 
        'status', 'tanggal_bayar', 'id_voucher'
    ];
    
    protected $useTimestamps = false;
    
    // Get pembayaran with user info
    public function getPembayaranWithUser($limit = null)
    {
        $builder = $this->db->table($this->table)
                           ->select('pembayaran.*, users.nama_lengkap, users.email, users.role')
                           ->join('users', 'users.id_user = pembayaran.id_user')
                           ->orderBy('pembayaran.tanggal_bayar', 'DESC');
        
        if ($limit) {
            $builder->limit($limit);
        }
        
        return $builder->get()->getResultArray();
    }
}