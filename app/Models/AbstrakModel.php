<?php

namespace App\Models;
use CodeIgniter\Model;

class AbstrakModel extends Model
{
    protected $table      = 'abstrak';
    protected $primaryKey = 'id_abstrak';
    
    protected $allowedFields = [
        'id_user', 'id_kategori', 'event_id', 'judul', 'file_abstrak', 
        'status', 'tanggal_upload', 'revisi_ke'
    ];
    
    protected $useTimestamps = false;
    protected $dateFormat    = 'datetime';
    
    // Get abstrak with user and category info
    public function getAbstrakWithDetails($limit = null)
    {
        $builder = $this->db->table($this->table)
                           ->select('abstrak.*, users.nama_lengkap, users.email, kategori_abstrak.nama_kategori, events.title as event_title')
                           ->join('users', 'users.id_user = abstrak.id_user')
                           ->join('kategori_abstrak', 'kategori_abstrak.id_kategori = abstrak.id_kategori')
                           ->join('events', 'events.id = abstrak.event_id', 'left')
                           ->orderBy('abstrak.tanggal_upload', 'DESC');
        
        if ($limit) {
            $builder->limit($limit);
        }
        
        return $builder->get()->getResultArray();
    }

    // Get abstrak by user
    public function getAbstrakByUser($userId)
    {
        return $this->where('id_user', $userId)
                   ->orderBy('tanggal_upload', 'DESC')
                   ->findAll();
    }

    // Get abstrak by status
    public function getAbstrakByStatus($status)
    {
        return $this->where('status', $status)->findAll();
    }

    // Get abstrak for specific event
    public function getAbstrakByEvent($eventId)
    {
        return $this->where('event_id', $eventId)->findAll();
    }
}