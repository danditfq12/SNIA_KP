<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run()
    {
        $now     = date('Y-m-d H:i:s');
        $hash    = '$2y$10$UPEyGpZdfQr5cJqv1OtRoOZE6tfvu1XVFFRBGUFnJq/Ep3jlRom1G'; // Teamkp123
        $plain   = 'Teamkp123';

        $users = [
            [
                'nama_lengkap' => 'Super Admin',
                'email'        => 'superadmin@gmail.com',
                'role'         => 'admin',
            ],
            [
                'nama_lengkap' => 'Presenter Test',
                'email'        => 'presenter@gmail.com',
                'role'         => 'presenter',
            ],
            [
                'nama_lengkap' => 'Audience Online',
                'email'        => 'audience_online@gmail.com',
                'role'         => 'audience',
            ],
            [
                'nama_lengkap' => 'Audience Offline',
                'email'        => 'audience_offline@gmail.com',
                'role'         => 'audience',
            ],
            [
                'nama_lengkap' => 'Reviewer Test',
                'email'        => 'reviewer@gmail.com',
                'role'         => 'reviewer',
            ],
        ];

        foreach ($users as $u) {
            $data = [
                'nama_lengkap' => $u['nama_lengkap'],
                'email'        => $u['email'],
                'password'     => $hash,
                'role'         => $u['role'],
                'status'       => 'aktif',
                'created_at'   => $now,
            ];
            $this->db->table('users')->insert($data);

            echo "User created: {$u['email']} | role: {$u['role']} | password: {$plain}\n";
        }
    }
}
