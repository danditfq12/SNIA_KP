<?php

namespace App\Models;

use CodeIgniter\Model;

class EmailQueueModel extends Model
{
    protected $table            = 'email_queue';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'to_email', 'subject',
        'body_html', 'body_text',
        'headers', 'status',
        'attempts', 'last_error',
        'scheduled_at', 'sent_at',
        'created_at', 'updated_at'
    ];
    protected $useTimestamps    = false;

    // ambil antrian email pending (default 10)
    public function getPending(int $limit = 10)
    {
        return $this->where('status', 'pending')
                    ->orderBy('scheduled_at IS NULL, scheduled_at ASC, id ASC', '', false)
                    ->findAll($limit);
    }
}
