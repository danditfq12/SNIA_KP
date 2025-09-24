<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEventIdAndAuditToAbsensi extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        // --- Kolom (aman dibaca ulang) ---
        $db->query('ALTER TABLE absensi ADD COLUMN IF NOT EXISTS event_id INT NULL');
        $db->query('ALTER TABLE absensi ADD COLUMN IF NOT EXISTS marked_by_admin INT NULL');
        $db->query('ALTER TABLE absensi ADD COLUMN IF NOT EXISTS notes TEXT NULL');

        // --- Index ---
        $db->query('CREATE INDEX IF NOT EXISTS idx_absensi_event_id ON absensi(event_id)');
        $db->query('CREATE INDEX IF NOT EXISTS idx_absensi_id_user  ON absensi(id_user)');

        // --- Foreign Keys (cek dulu baru tambah) ---
        $hasEventFk = (bool) $db->query("
            SELECT 1
            FROM information_schema.table_constraints
            WHERE table_schema = 'public'
              AND table_name = 'absensi'
              AND constraint_type = 'FOREIGN KEY'
              AND constraint_name = 'absensi_event_fk'
        ")->getNumRows();

        if (!$hasEventFk) {
            // pastikan kolom/target ada (id di events)
            $db->query("
                ALTER TABLE absensi
                ADD CONSTRAINT absensi_event_fk
                FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE
            ");
        }

        $hasUserFk = (bool) $db->query("
            SELECT 1
            FROM information_schema.table_constraints
            WHERE table_schema = 'public'
              AND table_name = 'absensi'
              AND constraint_type = 'FOREIGN KEY'
              AND constraint_name = 'absensi_user_fk'
        ")->getNumRows();

        if (!$hasUserFk) {
            $db->query("
                ALTER TABLE absensi
                ADD CONSTRAINT absensi_user_fk
                FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE
            ");
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();

        // FK
        $db->query('ALTER TABLE absensi DROP CONSTRAINT IF EXISTS absensi_event_fk');
        $db->query('ALTER TABLE absensi DROP CONSTRAINT IF EXISTS absensi_user_fk');

        // Index
        $db->query('DROP INDEX IF EXISTS idx_absensi_event_id');
        $db->query('DROP INDEX IF EXISTS idx_absensi_id_user');

        // Kolom
        $db->query('ALTER TABLE absensi DROP COLUMN IF EXISTS notes');
        $db->query('ALTER TABLE absensi DROP COLUMN IF EXISTS marked_by_admin');
        $db->query('ALTER TABLE absensi DROP COLUMN IF EXISTS event_id');
    }
}