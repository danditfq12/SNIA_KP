<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFieldsToUsersTable extends Migration
{
    public function up()
    {
        // Tambah kolom jenis_peserta ke tabel users yang sudah ada
        $fields = [
            'jenis_peserta' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'comment'    => 'mahasiswa, dosen, umum, peneliti, lainnya'
            ]
        ];

        // Tambahkan kolom baru
        $this->forge->addColumn('users', $fields);
    }

    public function down()
    {
        // Hapus kolom saat rollback
        $this->forge->dropColumn('users', 'jenis_peserta');
    }
}