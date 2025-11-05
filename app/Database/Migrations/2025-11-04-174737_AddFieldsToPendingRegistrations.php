<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFieldsToPendingRegistrations extends Migration
{
    public function up()
    {
        // Tambah kolom baru ke pending_registrations yang sudah ada
        $fields = [
            'no_hp' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'null'       => true
            ],
            'institusi' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
                'null'       => true
            ],
            'jenis_peserta' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'comment'    => 'mahasiswa, dosen, umum, peneliti, lainnya'
            ]
        ];

        // Tambahkan kolom baru
        $this->forge->addColumn('pending_registrations', $fields);
    }

    public function down()
    {
        // Hapus kolom saat rollback
        $this->forge->dropColumn('pending_registrations', ['no_hp', 'institusi', 'jenis_peserta']);
    }
}