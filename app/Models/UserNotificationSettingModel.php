<?php

namespace App\Models;

use CodeIgniter\Model;

class UserNotificationSettingModel extends Model
{
    protected $table            = 'user_notification_settings';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'id_user', 'topic',
        'in_app', 'email', 'push',
        'created_at', 'updated_at'
    ];
    protected $useTimestamps    = false;

    // ambil setting user untuk topik tertentu
    public function getSetting(int $userId, string $topic)
    {
        return $this->where('id_user', $userId)
                    ->where('topic', $topic)
                    ->first();
    }

    // ambil semua setting user
    public function forUser(int $userId)
    {
        return $this->where('id_user', $userId)->findAll();
    }
}
