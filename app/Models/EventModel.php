<?php

namespace App\Models;

use CodeIgniter\Model;

class EventModel extends Model
{
    protected $table = 'events';
    protected $primaryKey = 'id';
    
    protected $useAutoIncrement = true;
    protected $returnType = 'array';
    protected $useSoftDeletes = false;
    
    protected $allowedFields = [
        'title',
        'description',
        'event_date',
        'event_time',
        'format',
        'location',
        'zoom_link',
        'registration_fee',
        'max_participants',
        'registration_deadline',
        'abstract_deadline',
        'registration_active',
        'abstract_submission_active',
        'is_active'
    ];
    
    protected $useTimestamps = true;
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    
    protected $validationRules = [
        'title' => 'required|min_length[3]|max_length[255]',
        'event_date' => 'required|valid_date',
        'event_time' => 'required',
        'format' => 'required|in_list[online,offline]',
        'registration_fee' => 'required|decimal|greater_than_equal_to[0]'
    ];
    
    protected $validationMessages = [
        'title' => [
            'required' => 'Judul event harus diisi',
            'min_length' => 'Judul event minimal 3 karakter',
            'max_length' => 'Judul event maksimal 255 karakter'
        ],
        'format' => [
            'in_list' => 'Format event hanya boleh online atau offline'
        ]
    ];

    /**
     * Get active events
     */
    public function getActiveEvents()
    {
        return $this->where('is_active', true)
                   ->orderBy('event_date', 'ASC')
                   ->findAll();
    }

    /**
     * Get upcoming events
     */
    public function getUpcomingEvents($limit = 5)
    {
        return $this->where('event_date >=', date('Y-m-d'))
                   ->where('is_active', true)
                   ->orderBy('event_date', 'ASC')
                   ->limit($limit)
                   ->findAll();
    }

    /**
     * Get events with registration statistics
     */
    public function getEventsWithStats()
    {
        $db = \Config\Database::connect();
        
        return $db->table('events e')
                  ->select('e.*, 
                           COUNT(p.id_pembayaran) as total_registrations,
                           COUNT(CASE WHEN p.status = "verified" THEN 1 END) as verified_registrations,
                           COUNT(a.id_abstrak) as total_abstracts')
                  ->join('pembayaran p', 'p.event_id = e.id', 'left')
                  ->join('abstrak a', 'a.event_id = e.id', 'left')
                  ->groupBy('e.id')
                  ->orderBy('e.event_date', 'DESC')
                  ->get()
                  ->getResultArray();
    }

    /**
     * Check if registration is still open
     */
    public function isRegistrationOpen($eventId)
    {
        $event = $this->find($eventId);
        if (!$event) return false;
        
        $now = date('Y-m-d H:i:s');
        return $event['registration_active'] && 
               ($event['registration_deadline'] === null || $event['registration_deadline'] > $now);
    }

    /**
     * Check if abstract submission is still open
     */
    public function isAbstractSubmissionOpen($eventId)
    {
        $event = $this->find($eventId);
        if (!$event) return false;
        
        $now = date('Y-m-d H:i:s');
        return $event['abstract_submission_active'] && 
               ($event['abstract_deadline'] === null || $event['abstract_deadline'] > $now);
    }

    /**
     * Get event statistics
     */
    public function getEventStats($eventId)
    {
        $db = \Config\Database::connect();
        
        $stats = $db->table('events e')
                   ->select('e.title,
                            e.max_participants,
                            COUNT(DISTINCT p.id_pembayaran) as total_registrations,
                            COUNT(DISTINCT CASE WHEN p.status = "verified" THEN p.id_pembayaran END) as verified_registrations,
                            COUNT(DISTINCT a.id_abstrak) as total_abstracts,
                            COUNT(DISTINCT CASE WHEN a.status = "diterima" THEN a.id_abstrak END) as accepted_abstracts,
                            SUM(CASE WHEN p.status = "verified" THEN p.jumlah ELSE 0 END) as total_revenue')
                   ->join('pembayaran p', 'p.event_id = e.id', 'left')
                   ->join('abstrak a', 'a.event_id = e.id', 'left')
                   ->where('e.id', $eventId)
                   ->groupBy('e.id, e.title, e.max_participants')
                   ->get()
                   ->getRowArray();
        
        return $stats;
    }
}