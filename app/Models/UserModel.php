<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table         = 'users';
    protected $primaryKey    = 'id_user';   // ✅ fix di sini
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'nama_lengkap','email','password','role','status',
        'no_hp','institusi','alamat','foto',
        'created_at','updated_at'
    ];
}
