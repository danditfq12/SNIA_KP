<?php

namespace App\Models;

use CodeIgniter\Model;

class EventPosterModel extends Model
{
    protected $table            = 'event_posters';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields    = [
        'event_id',
        'file_path',
        'is_active',
        'created_at',
        'updated_at',
    ];

    // timestamps pakai kolom created_at & updated_at
    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = 'updated_at';

    // optional: validation (kalau mau dipakai nanti)
    protected $validationRules = [
        'event_id'  => 'required|integer',
        'file_path' => 'required|string|max_length[255]',
    ];
}
