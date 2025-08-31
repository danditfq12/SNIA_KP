<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use CodeIgniter\I18n\Time;

class NotifikasiSeeder extends Seeder
{
    public function run()
    {
        $data = [
            [
                'id_user'    => null,
                'role'       => 'reviewer',
                'title'      => 'Ada abstrak baru untuk direview',
                'message'    => 'Segera cek daftar abstrak yang ditugaskan ke Anda.',
                'link'       => 'reviewer/abstrak',
                'read'       => false, // ✅ boolean
                'created_at' => Time::now(),
            ],
            [
                'id_user'    => null,
                'role'       => 'presenter',
                'title'      => 'Abstrak Anda sedang direview',
                'message'    => 'Reviewer sedang memproses abstrak Anda.',
                'link'       => 'presenter/abstrak/status',
                'read'       => false,
                'created_at' => Time::now()->subDays(1),
            ],
            [
                'id_user'    => null,
                'role'       => 'admin',
                'title'      => 'Pembayaran baru masuk',
                'message'    => 'Ada peserta baru yang melakukan pembayaran.',
                'link'       => 'admin/pembayaran',
                'read'       => false,
                'created_at' => Time::now()->subDays(2),
            ],
            [
                'id_user'    => null,
                'role'       => 'audience',
                'title'      => 'Seminar akan segera dimulai',
                'message'    => 'Jangan lupa hadir pada acara seminar minggu depan.',
                'link'       => 'audience/dashboard',
                'read'       => false,
                'created_at' => Time::now()->subDays(3),
            ],
        ];

        // Insert banyak data sekaligus
        $this->db->table('notifikasi')->insertBatch($data);
    }
}
