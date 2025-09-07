<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEventIdAndAuditToAbsensi extends Migration
{
    public function up()
    {
        // Tambah kolom
        $this->forge->addColumn('absensi', [
            'event_id' => [
                'type' => 'INT',
                'null' => true, // sementara nullable biar aman untuk data lama
                'after' => 'id_user',
            ],
            'marked_by_admin' => [
                'type' => 'INT',
                'null' => true,
                'after' => 'status',
            ],
            'notes' => [
                'type' => 'TEXT',
                'null' => true,
                'after' => 'marked_by_admin',
            ],
        ]);

        // (Opsional tapi bagus) index & foreign key
        // Pastikan tabel/users & events ada: users(id_user), events(id)
        $db = \Config\Database::connect();

        // Index
        $db->query('CREATE INDEX IF NOT EXISTS idx_absensi_event_id ON absensi(event_id);');
        $db->query('CREATE INDEX IF NOT EXISTS idx_absensi_id_user ON absensi(id_user);');

        // FK (gunakan try/catch supaya tidak error kalau belum ada tabel tujuan)
        try {
            $db->query('ALTER TABLE absensi
                ADD CONSTRAINT absensi_event_fk
                FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE');
        } catch (\Throwable $e) {}

        try {
            $db->query('ALTER TABLE absensi
                ADD CONSTRAINT absensi_user_fk
                FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE');
        } catch (\Throwable $e) {}
    }

    public function down()
    {
        // Hapus FK kalau ada
        $db = \Config\Database::connect();
        try { $db->query('ALTER TABLE absensi DROP CONSTRAINT IF EXISTS absensi_event_fk'); } catch (\Throwable $e) {}
        try { $db->query('ALTER TABLE absensi DROP CONSTRAINT IF EXISTS absensi_user_fk'); } catch (\Throwable $e) {}

        // Hapus kolom
        $this->forge->dropColumn('absensi', ['event_id', 'marked_by_admin', 'notes']);
    }
}
