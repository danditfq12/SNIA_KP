<?php

namespace App\Models;

use CodeIgniter\Model;

class EventModel extends Model
{
    protected $table            = 'events';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $protectFields = true;
    protected $allowedFields = [
        'title',
        'description',
        'event_date',
        'event_time',
        'event_end_date',
        'event_end_time',
        'format',
        'location',
        'zoom_link',
        'registration_waves',
        'max_participants',
        'abstract_deadline',
        'abstract_revision_deadline',
        'abstract_revision_active',
        'full_paper_deadline',
        'full_paper_submission_active',
        'registration_active',
        'abstract_submission_active',
        'is_active',
        'created_at',
        'updated_at',
    ];

    protected bool $allowEmptyInserts = false;
    protected bool $updateOnlyChanged = true;

    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules = [
        'title'                 => 'required|min_length[3]|max_length[255]',
        'description'           => 'permit_empty|max_length[5000]',
        'event_date'            => 'required|valid_date',
        'event_time'            => 'required',
        'event_end_date'        => 'permit_empty|valid_date',
        'event_end_time'        => 'permit_empty',
        'format'                => 'required|in_list[both,online,offline]',
        'location'              => 'permit_empty|max_length[500]',
        'zoom_link'             => 'permit_empty|valid_url|max_length[500]',
        'max_participants'      => 'permit_empty|integer|greater_than[0]',
        'abstract_deadline'     => 'permit_empty|valid_date',
        'abstract_revision_deadline' => 'permit_empty|valid_date',
        'abstract_revision_active'   => 'permit_empty|in_list[0,1,true,false]',
        'full_paper_deadline'          => 'permit_empty|valid_date',
        'full_paper_submission_active' => 'permit_empty|in_list[0,1,true,false]',
    ];

    protected $validationMessages   = [];
    protected $skipValidation       = false;
    protected $cleanValidationRules = true;

    protected $allowCallbacks = true;
    protected $beforeInsert = [];
    protected $afterInsert  = [];
    protected $beforeUpdate = [];
    protected $afterUpdate  = [];
    protected $beforeFind   = [];
    protected $afterFind    = ['decodeJsonFields'];
    protected $beforeDelete = [];
    protected $afterDelete  = [];

    /**
     * Decode JSON fields after find
     */
    protected function decodeJsonFields(array $data)
    {
        if (isset($data['data'])) {
            // Multiple rows
            if (is_array($data['data'])) {
                foreach ($data['data'] as &$row) {
                    $row = $this->decodeRow($row);
                }
            }
        } elseif (isset($data['id'])) {
            // Single row
            $data = $this->decodeRow($data);
        }
        
        return $data;
    }

    /**
     * Decode single row JSON fields
     */
    private function decodeRow($row)
    {
        if (!is_array($row)) return $row;
        
        if (isset($row['registration_waves'])) {
            if (is_string($row['registration_waves'])) {
                $decoded = json_decode($row['registration_waves'], true);
                $row['registration_waves'] = is_array($decoded) ? $decoded : [];
            } elseif (!is_array($row['registration_waves'])) {
                $row['registration_waves'] = [];
            }
        } else {
            $row['registration_waves'] = [];
        }
        
        return $row;
    }

    /**
     * Get current active wave for an event
     */
    public function getCurrentWave($eventId)
    {
        $event = $this->find($eventId);
        if (!$event) return null;

        $waves = $event['registration_waves'] ?? [];
        
        // Ensure waves is an array
        if (is_string($waves)) {
            $waves = json_decode($waves, true);
        }
        
        if (!is_array($waves) || empty($waves)) return null;

        $now = time();
        
        foreach ($waves as $idx => $wave) {
            if (!is_array($wave)) continue;
            
            $start = strtotime($wave['registration_start'] ?? '');
            $end = strtotime($wave['registration_deadline'] ?? '');
            
            if ($start && $end && $now >= $start && $now <= $end) {
                // Add wave number for convenience
                $wave['wave_number'] = $idx + 1;
                return $wave;
            }
        }

        return null;
    }

    /**
     * Get event price based on current active wave
     */
    public function getEventPrice($eventId, $userRole, $participationType)
    {
        $wave = $this->getCurrentWave($eventId);
        if (!$wave) return 0;

        $key = strtolower($userRole) . '_fee_' . strtolower($participationType);
        return (float)($wave[$key] ?? 0);
    }

    /**
     * Get all waves for an event
     */
    public function getWaves($eventId)
    {
        $event = $this->find($eventId);
        if (!$event) return [];
        
        $waves = $event['registration_waves'] ?? [];
        
        // Ensure waves is an array
        if (is_string($waves)) {
            $waves = json_decode($waves, true);
        }
        
        return is_array($waves) ? $waves : [];
    }

    /**
     * Get participation options based on event format
     */
    public function getParticipationOptions($eventId, $userRole = null)
    {
        $event = $this->find($eventId);
        if (!$event) return [];

        // Presenter hanya offline
        if ($userRole === 'presenter') {
            return ['offline'];
        }

        // Audience bisa online/offline tergantung format
        $opts = [];
        if ($event['format'] === 'both' || $event['format'] === 'online') {
            $opts[] = 'online';
        }
        if ($event['format'] === 'both' || $event['format'] === 'offline') {
            $opts[] = 'offline';
        }
        
        return $opts;
    }

    /**
     * Get pricing matrix for all roles and types
     */
    public function getPricingMatrix($eventId)
    {
        $wave = $this->getCurrentWave($eventId);
        if (!$wave) return [];

        return [
            'presenter' => [
                'online'  => (float)($wave['presenter_fee_online'] ?? 0),
                'offline' => (float)($wave['presenter_fee_offline'] ?? 0),
            ],
            'audience' => [
                'online'  => (float)($wave['audience_fee_online'] ?? 0),
                'offline' => (float)($wave['audience_fee_offline'] ?? 0),
            ],
        ];
    }

    /**
     * Check if event is multi-day
     */
    public function isMultiDay($eventId)
    {
        $event = $this->find($eventId);
        if (!$event) return false;

        return !empty($event['event_end_date']) && 
               $event['event_end_date'] !== $event['event_date'];
    }

    /**
     * Get event duration in days
     */
    public function getEventDuration($eventId)
    {
        $event = $this->find($eventId);
        if (!$event) return 0;

        if (empty($event['event_end_date'])) return 1;

        $start = strtotime($event['event_date']);
        $end = strtotime($event['event_end_date']);

        return round(($end - $start) / 86400) + 1;
    }

    /**
     * Check if registration is open
     */
    public function isRegistrationOpen($eventId)
    {
        $event = $this->find($eventId);
        if (!$event || !$event['registration_active'] || !$event['is_active']) {
            return false;
        }

        // Check if there's an active wave
        return $this->getCurrentWave($eventId) !== null;
    }

    /**
     * Check if abstract submission is open
     */
    public function isAbstractSubmissionOpen($eventId)
    {
        $event = $this->find($eventId);
        if (!$event || !$event['abstract_submission_active'] || !$event['is_active']) {
            return false;
        }

        if ($event['abstract_deadline']) {
            return strtotime($event['abstract_deadline']) > time();
        }

        return strtotime($event['event_date']) > time();
    }

    /**
     * Check if abstract revision is open
     */
    public function isAbstractRevisionOpen($eventId)
    {
        $event = $this->find($eventId);
        if (!$event || !$event['is_active']) return false;

        $active = $event['abstract_revision_active'] ?? $event['abstract_submission_active'] ?? false;
        if (!$active) return false;

        $deadline = $event['abstract_revision_deadline'] ?? $event['abstract_deadline'] ?? null;

        if ($deadline) {
            return strtotime($deadline) > time();
        }

        return strtotime($event['event_date']) > time();
    }

    /**
     * Check if full paper submission is open
     */
    public function isFullPaperSubmissionOpen($eventId)
    {
        $event = $this->find($eventId);
        if (!$event || !($event['full_paper_submission_active'] ?? false) || !$event['is_active']) {
            return false;
        }

        if ($event['full_paper_deadline'] ?? null) {
            return strtotime($event['full_paper_deadline']) > time();
        }

        return strtotime($event['event_date']) > time();
    }

    /**
     * Check if event has reached max participants for a specific mode
     */
    public function hasReachedMaxParticipants($eventId, $participationType = null)
    {
        $event = $this->find($eventId);
        if (!$event) return false;

        $maxParticipants = (int)($event['max_participants'] ?? 0);
        
        // Jika tidak ada limit, return false
        if ($maxParticipants <= 0) return false;

        $db = \Config\Database::connect();
        
        // Hitung total participants yang sudah verified
        if ($participationType) {
            // Count untuk participation type tertentu (online/offline)
            $count = $db->table('pembayaran')
                ->where('event_id', $eventId)
                ->where('status', 'verified')
                ->where('participation_type', $participationType)
                ->countAllResults();
        } else {
            // Count total semua participants
            $count = $db->table('pembayaran')
                ->where('event_id', $eventId)
                ->where('status', 'verified')
                ->countAllResults();
        }

        return $count >= $maxParticipants;
    }

    /**
     * Get current registration count for event
     */
    public function getRegistrationCount($eventId, $participationType = null)
    {
        $db = \Config\Database::connect();
        
        $builder = $db->table('pembayaran')
            ->where('event_id', $eventId)
            ->where('status', 'verified');
        
        if ($participationType) {
            $builder->where('participation_type', $participationType);
        }
        
        return $builder->countAllResults();
    }

    /**
     * Get available slots for event
     */
    public function getAvailableSlots($eventId, $participationType = null)
    {
        $event = $this->find($eventId);
        if (!$event) return 0;

        $maxParticipants = (int)($event['max_participants'] ?? 0);
        
        // Jika tidak ada limit, return unlimited (999999)
        if ($maxParticipants <= 0) return 999999;

        $currentCount = $this->getRegistrationCount($eventId, $participationType);
        
        return max(0, $maxParticipants - $currentCount);
    }

    /**
     * Get price for specific role and participation type from current active wave
     */
    public function getPriceForRole($eventId, $userRole, $participationType = 'offline')
    {
        $wave = $this->getCurrentWave($eventId);
        if (!$wave) return 0;

        // Normalize role name
        $role = strtolower($userRole);
        
        // Construct key for price
        $priceKey = $role . '_fee_' . strtolower($participationType);
        
        return (float)($wave[$priceKey] ?? 0);
    }

    /**
     * Get current registration wave info with detailed information
     */
    public function getCurrentRegistrationWave($eventId)
    {
        $wave = $this->getCurrentWave($eventId);
        
        if (!$wave) return null;

        // Add additional info
        $wave['is_active'] = true;
        $wave['days_remaining'] = null;
        
        if (!empty($wave['registration_deadline'])) {
            $deadline = strtotime($wave['registration_deadline']);
            $now = time();
            $daysLeft = ceil(($deadline - $now) / 86400);
            $wave['days_remaining'] = max(0, $daysLeft);
        }

        return $wave;
    }

    /**
     * Get events with statistics
     */
    public function getEventsWithStats()
    {
        $db = \Config\Database::connect();
        
        $query = "
            SELECT 
                e.*,
                COALESCE(e.registration_waves::text, '[]') as registration_waves,
                COUNT(DISTINCT p.id_pembayaran) AS total_registrations,
                COUNT(DISTINCT CASE WHEN p.status='verified' THEN p.id_pembayaran END) AS verified_registrations,
                COUNT(DISTINCT CASE WHEN p.participation_type='online' THEN p.id_pembayaran END) AS online_registrations,
                COUNT(DISTINCT CASE WHEN p.participation_type='offline' THEN p.id_pembayaran END) AS offline_registrations,
                COUNT(DISTINCT a.id_abstrak) AS total_abstracts,
                COALESCE(SUM(CASE WHEN p.status='verified' THEN p.jumlah ELSE 0 END), 0) AS total_revenue
            FROM events e
            LEFT JOIN pembayaran p ON p.event_id = e.id
            LEFT JOIN abstrak a ON a.event_id = e.id
            GROUP BY e.id, e.title, e.description, e.event_date, e.event_time, e.event_end_date, 
                     e.event_end_time, e.format, e.location, e.zoom_link, e.registration_waves,
                     e.max_participants, e.abstract_deadline, e.abstract_revision_deadline,
                     e.full_paper_deadline, e.registration_active, e.abstract_submission_active,
                     e.abstract_revision_active, e.full_paper_submission_active, e.is_active,
                     e.created_at, e.updated_at
            ORDER BY e.event_date DESC
        ";
        
        $result = $db->query($query)->getResultArray();

        foreach ($result as &$event) {
            // Decode registration_waves from JSONB
            if (isset($event['registration_waves'])) {
                if (is_string($event['registration_waves'])) {
                    $decoded = json_decode($event['registration_waves'], true);
                    $event['registration_waves'] = is_array($decoded) ? $decoded : [];
                } elseif (!is_array($event['registration_waves'])) {
                    $event['registration_waves'] = [];
                }
            } else {
                $event['registration_waves'] = [];
            }

            // Get detailed role stats
            $roleStats = $db->query("
                SELECT u.role, p.participation_type, COUNT(*) AS count
                FROM pembayaran p
                JOIN users u ON u.id_user = p.id_user
                WHERE p.event_id = ? AND p.status='verified'
                GROUP BY u.role, p.participation_type
            ", [$event['id']])->getResultArray();

            $event['total_presenters'] = 0;
            $event['presenters_online'] = 0;
            $event['presenters_offline'] = 0;
            $event['total_audience'] = 0;
            $event['audience_online'] = 0;
            $event['audience_offline'] = 0;

            foreach ($roleStats as $s) {
                if ($s['role'] === 'presenter') {
                    $event['total_presenters'] += $s['count'];
                    if ($s['participation_type'] === 'online') {
                        $event['presenters_online'] += $s['count'];
                    } else {
                        $event['presenters_offline'] += $s['count'];
                    }
                } elseif ($s['role'] === 'audience') {
                    $event['total_audience'] += $s['count'];
                    if ($s['participation_type'] === 'online') {
                        $event['audience_online'] += $s['count'];
                    } else {
                        $event['audience_offline'] += $s['count'];
                    }
                }
            }

            // Get revenue breakdown
            $revenueData = $db->query("
                SELECT participation_type, SUM(jumlah) AS revenue
                FROM pembayaran
                WHERE event_id=? AND status='verified'
                GROUP BY participation_type
            ", [$event['id']])->getResultArray();

            $event['online_revenue'] = 0;
            $event['offline_revenue'] = 0;
            foreach ($revenueData as $r) {
                if ($r['participation_type'] === 'online') {
                    $event['online_revenue'] = $r['revenue'];
                } else {
                    $event['offline_revenue'] = $r['revenue'];
                }
            }

            // Additional stats
            $additionalStats = $db->query("
                SELECT 
                    COUNT(DISTINCT CASE WHEN p.status = 'pending' THEN p.id_pembayaran END) as pending_registrations,
                    COUNT(DISTINCT ab.id_absensi) as present_count
                FROM pembayaran p
                LEFT JOIN absensi ab ON ab.event_id = ? AND ab.id_user = p.id_user
                WHERE p.event_id = ?
            ", [$event['id'], $event['id']])->getRowArray();

            $event['pending_registrations'] = (int)($additionalStats['pending_registrations'] ?? 0);
            $event['present_count'] = (int)($additionalStats['present_count'] ?? 0);
        }

        return $result;
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
        $events = $this->where('is_active', true)
                       ->where('registration_active', true)
                       ->where('event_date >=', date('Y-m-d'))
                       ->orderBy('event_date', 'ASC')
                       ->findAll();

        // Filter by active wave
        return array_filter($events, function($event) {
            return $this->getCurrentWave($event['id']) !== null;
        });
    }
}