<?php

namespace App\Models;

use CodeIgniter\Model;

class PasswordResetModel extends Model
{
    protected $table         = 'password_resets';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'email', 'otp_code', 'otp_expired', 'attempts', 'created_at', 'updated_at'
    ];
}
