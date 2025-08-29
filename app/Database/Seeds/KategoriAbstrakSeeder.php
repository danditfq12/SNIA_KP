<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class KategoriAbstrakSeeder extends Seeder
{
    public function run()
    {
        $data = [
            ['nama_kategori' => 'Artificial Intelligence', 'deskripsi' => 'Kecerdasan Buatan & Machine Learning'],
            ['nama_kategori' => 'Sistem Informasi', 'deskripsi' => 'Aplikasi & Manajemen Sistem Informasi'],
            ['nama_kategori' => 'Jaringan Komputer', 'deskripsi' => 'Komunikasi Data & Infrastruktur Jaringan'],
            ['nama_kategori' => 'Data Mining', 'deskripsi' => 'Analisis Data & Big Data'],
            ['nama_kategori' => 'Keamanan Informasi', 'deskripsi' => 'Cybersecurity & Kriptografi'],
        ];

        $this->db->table('kategori_abstrak')->insertBatch($data);
    }
}
