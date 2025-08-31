<?php
namespace App\Models;
use CodeIgniter\Model;

class PembayaranModel extends Model
{
    protected $table      = 'pembayaran';
    protected $primaryKey = 'id_pembayaran';
    
    protected $allowedFields = [
        'id_user', 'event_id', 'metode', 'jumlah', 'bukti_bayar', 
        'status', 'tanggal_bayar', 'id_voucher', 'verified_by',
        'verified_at', 'keterangan'
    ];
    
    protected $useTimestamps = false;
    
    // Get pembayaran with user info
    public function getPembayaranWithUser($limit = null)
    {
        $builder = $this->db->table($this->table)
                           ->select('pembayaran.*, users.nama_lengkap, users.email, users.role, events.title as event_title')
                           ->join('users', 'users.id_user = pembayaran.id_user')
                           ->join('events', 'events.id = pembayaran.event_id', 'left')
                           ->orderBy('pembayaran.tanggal_bayar', 'DESC');
        
        if ($limit) {
            $builder->limit($limit);
        }
        
        return $builder->get()->getResultArray();
    }

    // Get pembayaran with verifier info
    public function getPembayaranWithVerifier($limit = null)
    {
        $builder = $this->db->table($this->table)
                           ->select('pembayaran.*, users.nama_lengkap, users.email, verifier.nama_lengkap as verifier_name, events.title as event_title')
                           ->join('users', 'users.id_user = pembayaran.id_user')
                           ->join('users as verifier', 'verifier.id_user = pembayaran.verified_by', 'left')
                           ->join('events', 'events.id = pembayaran.event_id', 'left')
                           ->orderBy('pembayaran.tanggal_bayar', 'DESC');
        
        if ($limit) {
            $builder->limit($limit);
        }
        
        return $builder->get()->getResultArray();
    }

    // Get revenue by event
    public function getRevenueByEvent($eventId)
    {
        return $this->selectSum('jumlah')
                   ->where('event_id', $eventId)
                   ->where('status', 'verified')
                   ->get()->getRowArray();
    }

    // Get payment statistics
    public function getPaymentStats()
    {
        return $this->db->query("
            SELECT 
                COUNT(*) as total_payments,
                COUNT(CASE WHEN status = 'verified' THEN 1 END) as verified_payments,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_payments,
                SUM(CASE WHEN status = 'verified' THEN jumlah ELSE 0 END) as total_revenue
            FROM pembayaran
        ")->getRowArray();
    }
}