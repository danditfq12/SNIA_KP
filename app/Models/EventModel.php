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
    protected $protectFields = true;
    protected $allowedFields = [
        'title',
        'description', 
        'event_date',
        'event_time',
        'format',
        'location',
        'zoom_link',
        'presenter_fee_offline',
        'audience_fee_online',
        'audience_fee_offline',
        'max_participants',
        'registration_deadline',
        'abstract_deadline',
        'registration_active',
        'abstract_submission_active',
        'is_active'
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected array $casts = [];
    protected array $castHandlers = [];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat = 'datetime';
    protected $createdField = 'created_at';
    protected $updatedField = 'updated_at';
    protected $deletedField = 'deleted_at';

    // Validation
    protected $validationRules = [
        'title' => 'required|min_length[3]|max_length[255]',
        'event_date' => 'required|valid_date',
        'event_time' => 'required',
        'format' => 'required|in_list[both,online,offline]',
        'presenter_fee_offline' => 'required|numeric|greater_than_equal_to[0]',
        'audience_fee_online' => 'required|numeric|greater_than_equal_to[0]',
        'audience_fee_offline' => 'required|numeric|greater_than_equal_to[0]'
    ];
    protected $validationMessages = [];
    protected $skipValidation = false;
    protected $cleanValidationRules = true;

    // Callbacks
    protected $allowCallbacks = true;
    protected $beforeInsert = [];
    protected $afterInsert = [];
    protected $beforeUpdate = [];
    protected $afterUpdate = [];
    protected $beforeFind = [];
    protected $afterFind = [];
    protected $beforeDelete = [];
    protected $afterDelete = [];

    /**
     * Get event price for specific role and participation type
     */
    public function getEventPrice($eventId, $userRole, $participationType)
    {
        $event = $this->find($eventId);
        if (!$event) {
            return 0;
        }

        // Presenter can only participate offline
        if ($userRole === 'presenter') {
            return $event['presenter_fee_offline'] ?? 0;
        }
        
        // Audience can participate online or offline
        if ($userRole === 'audience') {
            if ($participationType === 'online') {
                return $event['audience_fee_online'] ?? 0;
            } else {
                return $event['audience_fee_offline'] ?? 0;
            }
        }

        return 0;
    }

    /**
     * Get all available participation options for an event based on user role
     */
    public function getParticipationOptions($eventId, $userRole = null)
    {
        $event = $this->find($eventId);
        if (!$event) {
            return [];
        }

        // Presenter only gets offline option
        if ($userRole === 'presenter') {
            return ['offline'];
        }
        
        // Audience gets options based on event format
        if ($userRole === 'audience') {
            $options = [];
            
            if ($event['format'] === 'both' || $event['format'] === 'online') {
                $options[] = 'online';
            }
            
            if ($event['format'] === 'both' || $event['format'] === 'offline') {
                $options[] = 'offline';
            }
            
            return $options;
        }

        // Default: return all possible options based on event format
        $options = [];
        if ($event['format'] === 'both' || $event['format'] === 'online') {
            $options[] = 'online';
        }
        if ($event['format'] === 'both' || $event['format'] === 'offline') {
            $options[] = 'offline';
        }
        return $options;
    }

    /**
     * Get pricing matrix for an event
     */
    public function getPricingMatrix($eventId)
    {
        $event = $this->find($eventId);
        if (!$event) {
            return [];
        }

        return [
            'presenter' => [
                'offline' => $event['presenter_fee_offline']
            ],
            'audience' => [
                'online' => $event['audience_fee_online'],
                'offline' => $event['audience_fee_offline']
            ]
        ];
    }

    /**
     * Get all events with statistics including new pricing
     */
    public function getEventsWithStats()
    {
        return $this->select('
                events.*,
                COUNT(DISTINCT p.id_pembayaran) as total_registrations,
                COUNT(CASE WHEN p.verified_at IS NOT NULL THEN 1 END) as verified_registrations,
                COUNT(CASE WHEN p.participation_type = \'online\' THEN 1 END) as online_registrations,
                COUNT(CASE WHEN p.participation_type = \'offline\' THEN 1 END) as offline_registrations,
                COUNT(DISTINCT a.id_abstrak) as total_abstracts,
                SUM(CASE WHEN p.verified_at IS NOT NULL THEN p.jumlah ELSE 0 END) as total_revenue
            ')
            ->join('pembayaran p', 'p.event_id = events.id', 'left')
            ->join('abstrak a', 'a.event_id = events.id', 'left')
            ->groupBy('events.id')
            ->orderBy('events.event_date', 'DESC')
            ->findAll();
    }

    /**
     * Get event statistics for a specific event with participation breakdown
     */
    public function getEventStats($eventId)
    {
        $db = \Config\Database::connect();
        
        // Get basic statistics
        $stats = $db->query("
            SELECT 
                COUNT(DISTINCT p.id_pembayaran) as total_registrations,
                COUNT(CASE WHEN p.verified_at IS NOT NULL THEN 1 END) as verified_registrations,
                COUNT(CASE WHEN p.status = 'pending' THEN 1 END) as pending_registrations,
                COUNT(CASE WHEN p.participation_type = 'online' THEN 1 END) as online_registrations,
                COUNT(CASE WHEN p.participation_type = 'offline' THEN 1 END) as offline_registrations,
                COUNT(CASE WHEN p.participation_type = 'online' AND p.verified_at IS NOT NULL THEN 1 END) as verified_online,
                COUNT(CASE WHEN p.participation_type = 'offline' AND p.verified_at IS NOT NULL THEN 1 END) as verified_offline,
                COUNT(DISTINCT CASE WHEN u.role = 'presenter' THEN p.id_pembayaran END) as presenter_registrations,
                COUNT(DISTINCT CASE WHEN u.role = 'audience' THEN p.id_pembayaran END) as audience_registrations,
                COUNT(DISTINCT a.id_abstrak) as total_abstracts,
                COUNT(CASE WHEN a.status = 'menunggu' THEN 1 END) as pending_abstracts,
                COUNT(CASE WHEN a.status = 'diterima' THEN 1 END) as accepted_abstracts,
                COUNT(CASE WHEN a.status = 'ditolak' THEN 1 END) as rejected_abstracts,
                SUM(CASE WHEN p.verified_at IS NOT NULL THEN p.jumlah ELSE 0 END) as total_revenue,
                SUM(CASE WHEN p.verified_at IS NOT NULL AND p.participation_type = 'online' THEN p.jumlah ELSE 0 END) as online_revenue,
                SUM(CASE WHEN p.verified_at IS NOT NULL AND p.participation_type = 'offline' THEN p.jumlah ELSE 0 END) as offline_revenue
            FROM events e
            LEFT JOIN pembayaran p ON p.event_id = e.id
            LEFT JOIN users u ON u.id_user = p.id_user
            LEFT JOIN abstrak a ON a.event_id = e.id
            WHERE e.id = ?
        ", [$eventId])->getRowArray();

        return $stats;
    }

    /**
     * Check if registration is open for an event
     */
    public function isRegistrationOpen($eventId)
    {
        $event = $this->find($eventId);
        
        if (!$event || !$event['registration_active'] || !$event['is_active']) {
            return false;
        }

        // Check registration deadline
        if ($event['registration_deadline']) {
            return strtotime($event['registration_deadline']) > time();
        }

        // Check event date
        return strtotime($event['event_date']) > time();
    }

    /**
     * Check if abstract submission is open for an event
     */
    public function isAbstractSubmissionOpen($eventId)
    {
        $event = $this->find($eventId);
        
        if (!$event || !$event['abstract_submission_active'] || !$event['is_active']) {
            return false;
        }

        // Check abstract deadline
        if ($event['abstract_deadline']) {
            return strtotime($event['abstract_deadline']) > time();
        }

        // Check event date
        return strtotime($event['event_date']) > time();
    }

    /**
     * Get active events
     */
    public function getActiveEvents()
    {
        return $this->where('is_active', true)
                   ->where('event_date >=', date('Y-m-d'))
                   ->orderBy('event_date', 'ASC')
                   ->findAll();
    }

    /**
     * Get upcoming events
     */
    public function getUpcomingEvents($limit = 5)
    {
        return $this->where('is_active', true)
                   ->where('event_date >=', date('Y-m-d'))
                   ->orderBy('event_date', 'ASC')
                   ->limit($limit)
                   ->findAll();
    }

    /**
     * Get events with open registration
     */
    public function getEventsWithOpenRegistration()
    {
        $currentDateTime = date('Y-m-d H:i:s');
        
        return $this->where('is_active', true)
                   ->where('registration_active', true)
                   ->where('event_date >=', date('Y-m-d'))
                   ->groupStart()
                       ->where('registration_deadline IS NULL')
                       ->orWhere('registration_deadline >=', $currentDateTime)
                   ->groupEnd()
                   ->orderBy('event_date', 'ASC')
                   ->findAll();
    }

    /**
     * Get events with open abstract submission
     */
    public function getEventsWithOpenAbstractSubmission()
    {
        $currentDateTime = date('Y-m-d H:i:s');
        
        return $this->where('is_active', true)
                   ->where('abstract_submission_active', true)
                   ->where('event_date >=', date('Y-m-d'))
                   ->groupStart()
                       ->where('abstract_deadline IS NULL')
                       ->orWhere('abstract_deadline >=', $currentDateTime)
                   ->groupEnd()
                   ->orderBy('event_date', 'ASC')
                   ->findAll();
    }

    /**
     * Get event revenue with breakdown
     */
    public function getEventRevenue($eventId)
    {
        $db = \Config\Database::connect();
        
        $result = $db->query("
            SELECT 
                SUM(jumlah) as total_revenue,
                COUNT(*) as total_payments,
                SUM(CASE WHEN participation_type = 'online' THEN jumlah ELSE 0 END) as online_revenue,
                SUM(CASE WHEN participation_type = 'offline' THEN jumlah ELSE 0 END) as offline_revenue,
                COUNT(CASE WHEN participation_type = 'online' THEN 1 END) as online_payments,
                COUNT(CASE WHEN participation_type = 'offline' THEN 1 END) as offline_payments
            FROM pembayaran 
            WHERE event_id = ? AND verified_at IS NOT NULL
        ", [$eventId])->getRowArray();

        return $result;
    }

    /**
     * Get event participants count with breakdown
     */
    public function getEventParticipantsCount($eventId, $participationType = null)
    {
        $db = \Config\Database::connect();
        
        $whereClause = "event_id = ? AND verified_at IS NOT NULL";
        $params = [$eventId];
        
        if ($participationType) {
            $whereClause .= " AND participation_type = ?";
            $params[] = $participationType;
        }
        
        return $db->query("
            SELECT COUNT(*) as count 
            FROM pembayaran 
            WHERE $whereClause
        ", $params)->getRowArray()['count'];
    }

    /**
     * Check if event has reached max participants
     */
    public function hasReachedMaxParticipants($eventId, $participationType = null)
    {
        $event = $this->find($eventId);
        
        if (!$event || !$event['max_participants']) {
            return false;
        }

        $currentParticipants = $this->getEventParticipantsCount($eventId, $participationType);
        
        return $currentParticipants >= $event['max_participants'];
    }

    /**
     * Get events by date range
     */
    public function getEventsByDateRange($startDate, $endDate)
    {
        return $this->where('event_date >=', $startDate)
                   ->where('event_date <=', $endDate)
                   ->where('is_active', true)
                   ->orderBy('event_date', 'ASC')
                   ->findAll();
    }

    /**
     * Get recent events for dashboard
     */
    public function getRecentEvents($limit = 5)
    {
        return $this->orderBy('created_at', 'DESC')
                   ->limit($limit)
                   ->findAll();
    }

    /**
     * Search events
     */
    public function searchEvents($keyword)
    {
        return $this->like('title', $keyword)
                   ->orLike('description', $keyword)
                   ->orLike('location', $keyword)
                   ->where('is_active', true)
                   ->orderBy('event_date', 'DESC')
                   ->findAll();
    }

    /**
     * Get events by format
     */
    public function getEventsByFormat($format)
    {
        return $this->where('format', $format)
                   ->where('is_active', true)
                   ->orderBy('event_date', 'DESC')
                   ->findAll();
    }

    /**
     * Get monthly event statistics
     */
    public function getMonthlyStats($year)
    {
        $db = \Config\Database::connect();
        
        return $db->query("
            SELECT 
                EXTRACT(MONTH FROM event_date) as month,
                COUNT(*) as total_events,
                SUM(CASE WHEN is_active = true THEN 1 ELSE 0 END) as active_events
            FROM events 
            WHERE EXTRACT(YEAR FROM event_date) = ?
            GROUP BY EXTRACT(MONTH FROM event_date)
            ORDER BY month
        ", [$year])->getResultArray();
    }
}