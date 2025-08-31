<?php

namespace App\Models;

use CodeIgniter\Model;

class UserModel extends Model
{
    protected $table         = 'users';
    protected $primaryKey    = 'id_user'; // Diperbaiki dari 'id' ke 'id_user'
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'nama_lengkap','email','password','role','status',
        'no_hp','institusi','alamat','foto',
        'verification_token','email_verified_at',
        'created_at','updated_at'
    ];

    // Method untuk mendapatkan user berdasarkan email
    public function getUserByEmail($email)
    {
        return $this->where('email', $email)->first();
    }

    // Method untuk mendapatkan user dengan role tertentu
    public function getUsersByRole($role)
    {
        return $this->where('role', $role)->findAll();
    }

    // Method untuk mendapatkan user aktif
    public function getActiveUsers()
    {
        return $this->where('status', 'aktif')->findAll();
    }

    // Method untuk verifikasi email
    public function verifyEmail($userId)
    {
        return $this->update($userId, [
            'email_verified_at' => date('Y-m-d H:i:s'),
            'verification_token' => null,
            'status' => 'aktif'
        ]);
    }
}