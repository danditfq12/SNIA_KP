<?php

namespace App\Models;
use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table      = 'users';
    protected $primaryKey = 'id_user';
    protected $allowedFields = [
        'nama_lengkap','email','password','role','status','created_at','updated_at'
    ];
    protected $useTimestamps = true;
}
