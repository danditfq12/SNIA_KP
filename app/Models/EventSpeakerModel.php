<?php

namespace App\Models;

use CodeIgniter\Model;

class EventSpeakerModel extends Model
{
    protected $table      = 'event_speakers';
    protected $primaryKey = 'id';

    protected $returnType     = 'array';
    protected $useSoftDeletes = false;

    protected $allowedFields = [
        'event_id',
        'name',
        'role',
        'affiliation',
        'bio',
        'photo_path',
        'sort_order',
        'is_active',
        'created_at',
        'updated_at',
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    protected $validationRules    = [];
    protected $validationMessages = [];
    protected $skipValidation     = false;
}
