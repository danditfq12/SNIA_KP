<?php

namespace App\Models;

use CodeIgniter\Model;

class NotificationDeliveryModel extends Model
{
    protected $table            = 'notification_deliveries';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $allowedFields    = [
        'id_notif', 'channel', 'target',
        'status', 'last_error',
        'scheduled_at', 'sent_at',
        'created_at', 'updated_at'
    ];
    protected $useTimestamps    = false;
}
