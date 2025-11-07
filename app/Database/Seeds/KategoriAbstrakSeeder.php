<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;

class KategoriAbstrakSeeder extends Seeder
{
    public function run()
    {
        $data = [
            ['nama_kategori' => 'Artificial Intelligence', 'deskripsi' => 'Kecerdasan Buatan & Machine Learning'],
            ['nama_kategori' => 'Sistem Informasi',        'deskripsi' => 'Aplikasi & Manajemen Sistem Informasi'],
            ['nama_kategori' => 'Jaringan Komputer',       'deskripsi' => 'Komunikasi Data & Infrastruktur Jaringan'],
            ['nama_kategori' => 'Data Mining',             'deskripsi' => 'Analisis Data & Big Data'],
            ['nama_kategori' => 'Keamanan Informasi',      'deskripsi' => 'Cybersecurity & Kriptografi'],
            ['nama_kategori' => 'Pengolahan Citra',        'deskripsi' => 'Computer Vision & Image Processing'],
            ['nama_kategori' => 'Pemrosesan Bahasa Alami', 'deskripsi' => 'NLP, Speech & Text Mining'],
            ['nama_kategori' => 'Rekayasa Perangkat Lunak','deskripsi' => 'Requirement, Testing, DevOps, QA'],
            ['nama_kategori' => 'Internet of Things',      'deskripsi' => 'Sensor, Edge, dan IoT Platform'],
            ['nama_kategori' => 'Komputasi Awan',          'deskripsi' => 'Cloud, Serverless, dan Microservices'],
            ['nama_kategori' => 'Sistem Terdistribusi',    'deskripsi' => 'High Availability & Fault Tolerance'],
            ['nama_kategori' => 'Sistem Tertanam',         'deskripsi' => 'Embedded & Real-Time Systems'],
            ['nama_kategori' => 'Robotika',                'deskripsi' => 'Robot Cerdas & Kendali'],
            ['nama_kategori' => 'Blockchain & FinTech',    'deskripsi' => 'DLT, Smart Contract, Aplikasi Keuangan'],
            ['nama_kategori' => 'VR/AR & Multimedia',      'deskripsi' => 'Augmented/Virtual Reality & Media'],
            ['nama_kategori' => 'Interaksi Manusia-Komputer', 'deskripsi' => 'UI/UX, Usability, Accessibility'],
            ['nama_kategori' => 'Mobile & Web',            'deskripsi' => 'Aplikasi Mobile, PWA & Teknologi Web'],
            ['nama_kategori' => 'GIS & Sistem Informasi Geografis','deskripsi' => 'Pemetaan, Geospasial & Remote Sensing'],
            ['nama_kategori' => 'Bioinformatika',          'deskripsi' => 'Computational Biology & Health Informatics'],
            ['nama_kategori' => 'Pendidikan & Teknologi',  'deskripsi' => 'EdTech, E-Learning & MOOCs'],
            ['nama_kategori' => 'Information Retrieval',   'deskripsi' => 'Search, Recommender & Knowledge Graph'],
            ['nama_kategori' => 'Forensik Digital',        'deskripsi' => 'Investigasi, Audit & Incident Response'],
            ['nama_kategori' => 'Game & Simulasi',         'deskripsi' => 'Game Development & Serious Games'],
            ['nama_kategori' => 'Sains Data Terapan',      'deskripsi' => 'Applied Data Science & Analytics'],
        ];
        $this->db->table('kategori_abstrak')->insertBatch($data);
    }
}
