<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run()
    {
        $data = [
            'nama_lengkap' => 'SuperAdmin',
            'email'        => 'superadmin@gmail.com',
            'password'     => '$2y$10$zxismTnAhVKARWxY.fEAcuJ9NrZWsC0C5/qZq9dB29gLfmsnGpZ7S',
            'role'         => 'admin',
            'status'       => 'aktif',
            'created_at'   => date('Y-m-d H:i:s'),
        ];

        $this->db->table('users')->insert($data);
    }
}
