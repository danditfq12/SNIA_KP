<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterUsersAddProfileFields extends Migration
{
    public function up()
    {
        // Tambah kolom jika belum ada
        try {
            $this->forge->addColumn('users', [
                'no_hp' => ['type' => 'VARCHAR', 'constraint' => 20, 'null' => true, 'after' => 'status'],
            ]);
        } catch (\Throwable $e) {}
        try {
            $this->forge->addColumn('users', [
                'institusi' => ['type' => 'VARCHAR', 'constraint' => 150, 'null' => true, 'after' => 'no_hp'],
            ]);
        } catch (\Throwable $e) {}
        try {
            $this->forge->addColumn('users', [
                'alamat' => ['type' => 'TEXT', 'null' => true, 'after' => 'institusi'],
            ]);
        } catch (\Throwable $e) {}
        try {
            $this->forge->addColumn('users', [
                'foto' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true, 'after' => 'alamat'],
            ]);
        } catch (\Throwable $e) {}
    }

    public function down()
    {
        foreach (['no_hp','institusi','alamat','foto'] as $col) {
            try { $this->forge->dropColumn('users', $col); } catch (\Throwable $e) {}
        }
    }
}
