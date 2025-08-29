<?php

namespace App\Models;
use CodeIgniter\Model;

class PendingRegistrationModel extends Model
{
    protected $table      = 'pending_registrations';
    protected $primaryKey = 'id';

    protected $allowedFields = [
        'nama_lengkap','email','password_hash','role',
        'otp_code','otp_expired','created_at','updated_at'
    ];
}
