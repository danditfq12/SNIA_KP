<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEventRegistrationsTable extends Migration
{
    public function up()
    {
        $forge = \Config\Database::forge();
        $db    = \Config\Database::connect();

        // 1) Buat tabel dasar
        $forge->addField([
            'id' => [
                'type'           => 'SERIAL',
                'unsigned'       => false,
                'null'           => false,
            ],
            'id_event' => [
                'type' => 'INT',
                'null' => false,
            ],
            'id_user' => [
                'type' => 'INT',
                'null' => false,
            ],
            'mode_kehadiran' => [ // online|offline
                'type' => 'VARCHAR',
                'constraint' => 10,
                'null' => false,
            ],
            'status' => [ // terdaftar|menunggu_pembayaran|lunas|batal
                'type' => 'VARCHAR',
                'constraint' => 32,
                'null' => false,
                'default' => 'menunggu_pembayaran',
            ],
            'qr_token' => [
                'type' => 'VARCHAR',
                'constraint' => 128,
                'null' => true,
            ],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => true],
            'updated_at' => ['type' => 'TIMESTAMP', 'null' => true],
        ]);
        $forge->addKey('id', true);
        $forge->createTable('event_registrations', true);

        // 2) Constraint Postgres (CHECK & UNIQUE & FK)
        // CHECK mode
        $db->query("ALTER TABLE event_registrations
            ADD CONSTRAINT chk_event_reg_mode
            CHECK (mode_kehadiran IN ('online','offline'))");

        // CHECK status
        $db->query("ALTER TABLE event_registrations
            ADD CONSTRAINT chk_event_reg_status
            CHECK (status IN ('terdaftar','menunggu_pembayaran','lunas','batal'))");

        // UNIQUE satu user satu event
        $db->query("CREATE UNIQUE INDEX IF NOT EXISTS uq_event_user
            ON event_registrations (id_event, id_user)");

        // FK (anggap PK event = events.id, user = users.id_user)
        try {
            $db->query("ALTER TABLE event_registrations
                ADD CONSTRAINT fk_event_reg_event
                FOREIGN KEY (id_event) REFERENCES events(id)
                ON DELETE RESTRICT ON UPDATE CASCADE");
        } catch (\Throwable $e) {}

        try {
            $db->query("ALTER TABLE event_registrations
                ADD CONSTRAINT fk_event_reg_user
                FOREIGN KEY (id_user) REFERENCES users(id_user)
                ON DELETE RESTRICT ON UPDATE CASCADE");
        } catch (\Throwable $e) {}
    }

    public function down()
    {
        $forge = \Config\Database::forge();
        $forge->dropTable('event_registrations', true);
    }
}
