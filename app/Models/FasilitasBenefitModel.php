<?php

namespace App\Models;

use CodeIgniter\Model;

class FasilitasBenefitModel extends Model
{
    protected $table            = 'fasilitas_benefit';
    protected $primaryKey       = 'id';
    protected $useAutoIncrement = true;
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;
    protected $protectFields    = true;
    protected $allowedFields    = [
        'event_id',
        'participant_type',
        'fasilitas',
        'is_active',
        'created_at',
        'updated_at'
    ];

    // Dates
    protected $useTimestamps = true;
    protected $dateFormat    = 'datetime';
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // Validation
    protected $validationRules = [
        'event_id'         => 'required|integer',
        'participant_type' => 'required|in_list[presenter_online,presenter_offline,audience_online,audience_offline]',
        'fasilitas'        => 'required',
    ];

    protected $validationMessages = [
        'event_id' => [
            'required' => 'Event harus dipilih',
            'integer'  => 'Event tidak valid',
        ],
        'participant_type' => [
            'required' => 'Tipe partisipan harus dipilih',
            'in_list'  => 'Tipe partisipan tidak valid',
        ],
        'fasilitas' => [
            'required' => 'Fasilitas harus diisi',
        ],
    ];

    /**
     * Get facilities by event ID
     */
    public function getFasilitasByEvent(int $eventId): array
    {
        return $this->where('event_id', $eventId)
                    ->where('is_active', true)
                    ->findAll();
    }

    /**
     * Get facilities by event and participant type
     */
    public function getFasilitasByType(int $eventId, string $participantType): ?array
    {
        $result = $this->where('event_id', $eventId)
                       ->where('participant_type', $participantType)
                       ->where('is_active', true)
                       ->first();

        if ($result && !empty($result['fasilitas'])) {
            $result['fasilitas'] = is_string($result['fasilitas']) 
                ? json_decode($result['fasilitas'], true) 
                : $result['fasilitas'];
        }

        return $result;
    }

    /**
     * Get all facilities with event info
     */
    public function getFasilitasWithEvent(): array
    {
        return $this->select('fasilitas_benefit.*, events.title as event_title, events.event_date')
                    ->join('events', 'events.id = fasilitas_benefit.event_id', 'left')
                    ->orderBy('events.event_date', 'DESC')
                    ->orderBy('fasilitas_benefit.participant_type', 'ASC')
                    ->findAll();
    }

    /**
     * Save or update facilities for event
     */
    public function saveFasilitas(int $eventId, string $participantType, array $fasilitasArray): bool
    {
        $existing = $this->where('event_id', $eventId)
                         ->where('participant_type', $participantType)
                         ->first();

        $data = [
            'event_id'         => $eventId,
            'participant_type' => $participantType,
            'fasilitas'        => json_encode($fasilitasArray, JSON_UNESCAPED_UNICODE),
            'is_active'        => true,
        ];

        if ($existing) {
            return $this->update($existing['id'], $data);
        }

        return (bool)$this->insert($data);
    }

    /**
     * Toggle active status
     */
    public function toggleStatus(int $id): bool
    {
        $item = $this->find($id);
        if (!$item) {
            return false;
        }

        return $this->update($id, ['is_active' => !$item['is_active']]);
    }

    /**
     * Get statistics
     */
    public function getStatistics(): array
    {
        $db = \Config\Database::connect();
        
        return [
            'total_events_with_facilities' => $db->query("
                SELECT COUNT(DISTINCT event_id) as total 
                FROM fasilitas_benefit 
                WHERE is_active = true
            ")->getRow()->total ?? 0,
            
            'by_type' => $db->query("
                SELECT 
                    participant_type,
                    COUNT(*) as total
                FROM fasilitas_benefit
                WHERE is_active = true
                GROUP BY participant_type
            ")->getResultArray(),
        ];
    }

    /**
     * Copy facilities from one event to another
     */
    public function copyToEvent(int $sourceEventId, int $targetEventId): bool
    {
        $facilities = $this->where('event_id', $sourceEventId)->findAll();
        
        if (empty($facilities)) {
            return false;
        }

        foreach ($facilities as $facility) {
            // Check if already exists
            $existing = $this->where('event_id', $targetEventId)
                             ->where('participant_type', $facility['participant_type'])
                             ->first();

            $data = [
                'event_id'         => $targetEventId,
                'participant_type' => $facility['participant_type'],
                'fasilitas'        => $facility['fasilitas'],
                'is_active'        => true,
            ];

            if ($existing) {
                $this->update($existing['id'], $data);
            } else {
                $this->insert($data);
            }
        }

        return true;
    }

    /**
     * Delete by event and type
     */
    public function deleteByEventAndType(int $eventId, string $participantType): bool
    {
        return $this->where('event_id', $eventId)
                    ->where('participant_type', $participantType)
                    ->delete();
    }
}