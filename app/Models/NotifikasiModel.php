<?php
namespace App\Models;
use CodeIgniter\Model;

class NotifikasiModel extends Model
{
    protected $table      = 'notifikasi';
    protected $primaryKey = 'id_notif';

    protected $allowedFields = [
        'id_user', 'role', 'title', 'message', 'link', 'read', 'created_at'
    ];

    protected $useTimestamps = true;
    protected $createdField  = 'created_at';
    protected $updatedField  = '';
}
