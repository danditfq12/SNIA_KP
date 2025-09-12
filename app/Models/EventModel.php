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
        'description'           => 'permit_empty|max_length[2000]',
        'event_date'            => 'required|valid_date',
        'event_time'            => 'required',
        'format'                => 'required|in_list[both,online,offline]',
        'location'              => 'permit_empty|max_length[255]',
        'zoom_link'             => 'permit_empty|valid_url|max_length[500]',
        'presenter_fee_offline' => 'required|numeric|greater_than_equal_to[0]',
        'audience_fee_online'   => 'permit_empty|numeric|greater_than_equal_to[0]',
        'audience_fee_offline'  => 'permit_empty|numeric|greater_than_equal_to[0]',
        'max_participants'      => 'permit_empty|integer|greater_than[0]',
        'registration_deadline' => 'permit_empty|valid_date',
        'abstract_deadline'     => 'permit_empty|valid_date',
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
    protected $afterFind    = [];
    protected $beforeDelete = [];
    protected $afterDelete  = [];

    /* ===== Util methods tetap ===== */

    public function getEventPrice($eventId, $userRole, $participationType)
    {
        $event = $this->find($eventId);
        if (!$event) return 0;

        if ($userRole === 'presenter') {
            return $event['presenter_fee_offline'] ?? 0;
        }
        if ($userRole === 'audience') {
            return $participationType === 'online'
                ? ($event['audience_fee_online'] ?? 0)
                : ($event['audience_fee_offline'] ?? 0);
        }
        return 0;
    }

    public function getParticipationOptions($eventId, $userRole = null)
    {
        $event = $this->find($eventId);
        if (!$event) return [];

        if ($userRole === 'presenter') return ['offline'];

        $opts = [];
        if ($event['format'] === 'both'   || $event['format'] === 'online')  $opts[] = 'online';
        if ($event['format'] === 'both'   || $event['format'] === 'offline') $opts[] = 'offline';
        return $opts;
    }

    public function getPricingMatrix($eventId)
    {
        $event = $this->find($eventId);
        if (!$event) return [];

        return [
            'presenter' => ['offline' => $event['presenter_fee_offline']],
            'audience'  => [
                'online'  => $event['audience_fee_online'],
                'offline' => $event['audience_fee_offline'],
            ],
        ];
    }

    public function getEventsWithStats()
    {
        $db = \Config\Database::connect();
        $query = "
            SELECT 
                e.*,
                COUNT(DISTINCT p.id_pembayaran) AS total_registrations,
                COUNT(DISTINCT CASE WHEN p.status='verified' THEN p.id_pembayaran END) AS verified_registrations,
                COUNT(DISTINCT CASE WHEN p.participation_type='online'  THEN p.id_pembayaran END) AS online_registrations,
                COUNT(DISTINCT CASE WHEN p.participation_type='offline' THEN p.id_pembayaran END) AS offline_registrations,
                COUNT(DISTINCT a.id_abstrak) AS total_abstracts,
                COALESCE(SUM(CASE WHEN p.status='verified' THEN p.jumlah ELSE 0 END),0) AS total_revenue
            FROM events e
            LEFT JOIN pembayaran p ON p.event_id = e.id
            LEFT JOIN abstrak a    ON a.event_id = e.id
            GROUP BY e.id
            ORDER BY e.event_date DESC
        ";
        $result = $db->query($query)->getResultArray();

        foreach ($result as &$event) {
            $roleStats = $db->query("
                SELECT u.role, p.participation_type, COUNT(*) AS count
                FROM pembayaran p
                JOIN users u ON u.id_user = p.id_user
                WHERE p.event_id = ? AND p.status='verified'
                GROUP BY u.role, p.participation_type
            ", [$event['id']])->getResultArray();

            $event['presenter_registrations']       = 0;
            $event['audience_online_registrations'] = 0;
            $event['audience_offline_registrations']= 0;

            foreach ($roleStats as $s) {
                if ($s['role'] === 'presenter') {
                    $event['presenter_registrations'] += $s['count'];
                } elseif ($s['role'] === 'audience') {
                    if ($s['participation_type'] === 'online')  $event['audience_online_registrations']  += $s['count'];
                    else                                        $event['audience_offline_registrations'] += $s['count'];
                }
            }

            $rev = $db->query("
                SELECT participation_type, SUM(jumlah) AS revenue
                FROM pembayaran
                WHERE event_id=? AND status='verified'
                GROUP BY participation_type
            ", [$event['id']])->getResultArray();

            $event['online_revenue']  = 0;
            $event['offline_revenue'] = 0;
            foreach ($rev as $r) {
                if ($r['participation_type'] === 'online') $event['online_revenue']  = $r['revenue'];
                else                                       $event['offline_revenue'] = $r['revenue'];
            }
        }
        return $result;
    }

    public function getEventStats($eventId)
    {
        $db = \Config\Database::connect();
        $stats = $db->query("
            SELECT 
                COUNT(DISTINCT p.id_pembayaran) AS total_registrations,
                COUNT(DISTINCT CASE WHEN p.status='verified' THEN p.id_pembayaran END) AS verified_registrations,
                COUNT(DISTINCT CASE WHEN p.status='pending'  THEN p.id_pembayaran END) AS pending_registrations,
                COUNT(DISTINCT CASE WHEN p.participation_type='online'  THEN p.id_pembayaran END) AS online_registrations,
                COUNT(DISTINCT CASE WHEN p.participation_type='offline' THEN p.id_pembayaran END) AS offline_registrations,
                COUNT(DISTINCT CASE WHEN p.participation_type='online'  AND p.status='verified' THEN p.id_pembayaran END) AS verified_online,
                COUNT(DISTINCT CASE WHEN p.participation_type='offline' AND p.status='verified' THEN p.id_pembayaran END) AS verified_offline,
                COUNT(DISTINCT CASE WHEN u.role='presenter' THEN p.id_pembayaran END) AS presenter_registrations,
                COUNT(DISTINCT CASE WHEN u.role='audience'  THEN p.id_pembayaran END) AS audience_registrations,
                COUNT(DISTINCT a.id_abstrak) AS total_abstracts,
                COUNT(DISTINCT CASE WHEN a.status='menunggu' THEN a.id_abstrak END) AS pending_abstracts,
                COUNT(DISTINCT CASE WHEN a.status='diterima' THEN a.id_abstrak END) AS accepted_abstracts,
                COUNT(DISTINCT CASE WHEN a.status='ditolak'  THEN a.id_abstrak END) AS rejected_abstracts,
                COALESCE(SUM(CASE WHEN p.status='verified' THEN p.jumlah ELSE 0 END),0) AS total_revenue,
                COALESCE(SUM(CASE WHEN p.status='verified' AND p.participation_type='online'  THEN p.jumlah ELSE 0 END),0) AS online_revenue,
                COALESCE(SUM(CASE WHEN p.status='verified' AND p.participation_type='offline' THEN p.jumlah ELSE 0 END),0) AS offline_revenue
            FROM events e
            LEFT JOIN pembayaran p ON p.event_id = e.id
            LEFT JOIN users u      ON u.id_user  = p.id_user
            LEFT JOIN abstrak a    ON a.event_id = e.id
            WHERE e.id = ?
        ", [$eventId])->getRowArray();

        foreach ($stats ?? [] as $k => $v) {
            if (is_numeric($v)) $stats[$k] = (int) $v;
        }

        return $stats ?: [
            'total_registrations'    => 0,
            'verified_registrations' => 0,
            'pending_registrations'  => 0,
            'online_registrations'   => 0,
            'offline_registrations'  => 0,
            'verified_online'        => 0,
            'verified_offline'       => 0,
            'presenter_registrations'=> 0,
            'audience_registrations' => 0,
            'total_abstracts'        => 0,
            'pending_abstracts'      => 0,
            'accepted_abstracts'     => 0,
            'rejected_abstracts'     => 0,
            'total_revenue'          => 0,
            'online_revenue'         => 0,
            'offline_revenue'        => 0
        ];
    }

    public function isRegistrationOpen($eventId)
    {
        $event = $this->find($eventId);
        if (!$event || !$event['registration_active'] || !$event['is_active']) return false;

        if ($event['registration_deadline']) return strtotime($event['registration_deadline']) > time();
        return strtotime($event['event_date']) > time();
    }

    public function isAbstractSubmissionOpen($eventId)
    {
        $event = $this->find($eventId);
        if (!$event || !$event['abstract_submission_active'] || !$event['is_active']) return false;

        if ($event['abstract_deadline']) return strtotime($event['abstract_deadline']) > time();
        return strtotime($event['event_date']) > time();
    }

    public function getActiveEvents()
    {
        return $this->where('is_active', true)
                    ->where('event_date >=', date('Y-m-d'))
                    ->orderBy('event_date', 'ASC')
                    ->findAll();
    }

    public function getUpcomingEvents($limit = 5)
    {
        return $this->where('is_active', true)
                    ->where('event_date >=', date('Y-m-d'))
                    ->orderBy('event_date', 'ASC')
                    ->limit($limit)
                    ->findAll();
    }

    public function getEventsWithOpenRegistration()
    {
        $now = date('Y-m-d H:i:s');
        return $this->where('is_active', true)
                    ->where('registration_active', true)
                    ->where('event_date >=', date('Y-m-d'))
                    ->groupStart()
                        ->where('registration_deadline IS NULL')
                        ->orWhere('registration_deadline >=', $now)
                    ->groupEnd()
                    ->orderBy('event_date', 'ASC')
                    ->findAll();
    }

    public function getEventsWithOpenAbstractSubmission()
    {
        $now = date('Y-m-d H:i:s');
        return $this->where('is_active', true)
                    ->where('abstract_submission_active', true)
                    ->where('event_date >=', date('Y-m-d'))
                    ->groupStart()
                        ->where('abstract_deadline IS NULL')
                        ->orWhere('abstract_deadline >=', $now)
                    ->groupEnd()
                    ->orderBy('event_date', 'ASC')
                    ->findAll();
    }

    public function getEventRevenue($eventId)
    {
        $db = \Config\Database::connect();
        $r = $db->query("
            SELECT 
                SUM(jumlah) AS total_revenue,
                COUNT(*)    AS total_payments,
                SUM(CASE WHEN participation_type='online'  THEN jumlah ELSE 0 END) AS online_revenue,
                SUM(CASE WHEN participation_type='offline' THEN jumlah ELSE 0 END) AS offline_revenue,
                COUNT(CASE WHEN participation_type='online'  THEN 1 END) AS online_payments,
                COUNT(CASE WHEN participation_type='offline' THEN 1 END) AS offline_payments
            FROM pembayaran 
            WHERE event_id=? AND status='verified'
        ", [$eventId])->getRowArray();

        return $r ?: [
            'total_revenue'   => 0,
            'total_payments'  => 0,
            'online_revenue'  => 0,
            'offline_revenue' => 0,
            'online_payments' => 0,
            'offline_payments'=> 0,
        ];
    }

    public function getEventParticipantsCount($eventId, $participationType = null)
    {
        $db = \Config\Database::connect();
        $where = "event_id = ? AND status='verified'";
        $params = [$eventId];
        if ($participationType) {
            $where .= " AND participation_type = ?";
            $params[] = $participationType;
        }
        $row = $db->query("SELECT COUNT(*) AS count FROM pembayaran WHERE {$where}", $params)->getRowArray();
        return (int) ($row['count'] ?? 0);
    }

    public function hasReachedMaxParticipants($eventId, $participationType = null)
    {
        $event = $this->find($eventId);
        if (!$event || !$event['max_participants']) return false;

        $now = $this->getEventParticipantsCount($eventId, $participationType);
        return $now >= $event['max_participants'];
    }

    public function getEventsByDateRange($startDate, $endDate)
    {
        return $this->where('event_date >=', $startDate)
                    ->where('event_date <=', $endDate)
                    ->where('is_active', true)
                    ->orderBy('event_date', 'ASC')
                    ->findAll();
    }

    public function getRecentEvents($limit = 5)
    {
        return $this->orderBy('created_at', 'DESC')->limit($limit)->findAll();
    }

    public function searchEvents($keyword)
    {
        return $this->like('title', $keyword)
                    ->orLike('description', $keyword)
                    ->orLike('location', $keyword)
                    ->where('is_active', true)
                    ->orderBy('event_date', 'DESC')
                    ->findAll();
    }

    public function getEventsByFormat($format)
    {
        return $this->where('format', $format)
                    ->where('is_active', true)
                    ->orderBy('event_date', 'DESC')
                    ->findAll();
    }

    public function getMonthlyStats($year)
    {
        $db = \Config\Database::connect();
        return $db->query("
            SELECT 
                EXTRACT(MONTH FROM event_date) AS month,
                COUNT(*) AS total_events,
                SUM(CASE WHEN is_active = true THEN 1 ELSE 0 END) AS active_events
            FROM events
            WHERE EXTRACT(YEAR FROM event_date) = ?
            GROUP BY EXTRACT(MONTH FROM event_date)
            ORDER BY month
        ", [$year])->getResultArray();
    }

    /* ===== Baru: dipakai Presenter.index untuk daftar event yang masih bisa submit abstrak ===== */
    public function getAvailableEventsForUser(int $userId): array
    {
        // TRUE aman di MySQL (TRUE==1) & Postgres (boolean)
        return $this->db->query("
            SELECT e.*
            FROM events e
            WHERE e.is_active = TRUE
              AND e.abstract_submission_active = TRUE
              AND (e.abstract_deadline IS NULL OR e.abstract_deadline >= CURRENT_DATE)
              AND e.event_date >= CURRENT_DATE
              AND NOT EXISTS (
                    SELECT 1 FROM abstrak a
                    WHERE a.event_id = e.id
                      AND a.id_user  = ?
                      AND a.status NOT IN ('ditolak','revisi')
              )
            ORDER BY e.event_date ASC
        ", [$userId])->getResultArray();
    }
}
