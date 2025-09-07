<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class UserSeeder extends Seeder
{
    public function run()
    {
        $now   = date('Y-m-d H:i:s');
        $pass  = 'Teamkp123';
        $hash  = password_hash($pass, PASSWORD_BCRYPT);

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
                'nama_lengkap' => 'Audience',
                'email'        => 'audience@gmail.com',
                'role'         => 'audience',
            ],
            [
                'nama_lengkap' => 'Reviewer Test',
                'email'        => 'reviewer@gmail.com',
                'role'         => 'reviewer',
            ],
        ];

        $builder = $this->db->table('users');

        foreach ($users as $u) {
            // Cek jika sudah ada (agar idempotent)
            $exists = $builder->where('email', $u['email'])->get()->getRowArray();

            $data = [
                'nama_lengkap' => $u['nama_lengkap'],
                'email'        => $u['email'],
                'password'     => $hash,
                'role'         => $u['role'],
                'status'       => 'aktif',
                'foto'         => 'default.png',   // pastikan file default.png tersedia
                'no_hp'        => null,            // opsional: sesuaikan jika kolom ada
                'institusi'    => null,            // opsional
                'nim'          => null,            // opsional (nullable & unique di migration terbaru)
                'created_at'   => $now,
                'updated_at'   => $now,
            ];

            if ($exists) {
                // update supaya aman kalau seed dijalankan ulang
                $builder->where('email', $u['email'])->update($data);
                echo "User updated: {$u['email']} | role: {$u['role']} | password: {$pass}\n";
            } else {
                $builder->insert($data);
                echo "User created: {$u['email']} | role: {$u['role']} | password: {$pass}\n";
            }
        }
    }
}
