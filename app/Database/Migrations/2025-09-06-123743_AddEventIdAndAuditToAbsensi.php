<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEventIdAndAuditToAbsensi extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();
        
        // Cek dan tambah kolom event_id jika belum ada
        if (!$db->fieldExists('event_id', 'absensi')) {
            $this->forge->addColumn('absensi', [
                'event_id' => [
                    'type' => 'INT',
                    'null' => true,
                    'after' => 'id_user',
                ],
            ]);
        }
        
        // Cek dan tambah kolom marked_by_admin jika belum ada
        if (!$db->fieldExists('marked_by_admin', 'absensi')) {
            $this->forge->addColumn('absensi', [
                'marked_by_admin' => [
                    'type' => 'INT',
                    'null' => true,
                    'after' => 'status',
                ],
            ]);
        }
        
        // Cek dan tambah kolom notes jika belum ada
        if (!$db->fieldExists('notes', 'absensi')) {
            $this->forge->addColumn('absensi', [
                'notes' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'after' => 'marked_by_admin',
                ],
            ]);
        }

        // Index - gunakan IF NOT EXISTS
        $db->query('CREATE INDEX IF NOT EXISTS idx_absensi_event_id ON absensi(event_id);');
        $db->query('CREATE INDEX IF NOT EXISTS idx_absensi_id_user ON absensi(id_user);');

        // FK dengan pengecekan yang lebih robust
        try {
            // Cek apakah constraint sudah ada
            $constraintExists = $db->query("
                SELECT constraint_name 
                FROM information_schema.table_constraints 
                WHERE table_name = 'absensi' 
                AND constraint_name = 'absensi_event_fk'
            ")->getNumRows() > 0;
            
            if (!$constraintExists) {
                $db->query('ALTER TABLE absensi
                    ADD CONSTRAINT absensi_event_fk
                    FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE CASCADE');
            }
        } catch (\Throwable $e) {
            log_message('warning', 'Could not add absensi_event_fk: ' . $e->getMessage());
        }

        try {
            // Cek apakah constraint sudah ada
            $constraintExists = $db->query("
                SELECT constraint_name 
                FROM information_schema.table_constraints 
                WHERE table_name = 'absensi' 
                AND constraint_name = 'absensi_user_fk'
            ")->getNumRows() > 0;
            
            if (!$constraintExists) {
                $db->query('ALTER TABLE absensi
                    ADD CONSTRAINT absensi_user_fk
                    FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE');
            }
        } catch (\Throwable $e) {
            log_message('warning', 'Could not add absensi_user_fk: ' . $e->getMessage());
        }
    }

    public function down()
    {
        // Hapus FK kalau ada
        $db = \Config\Database::connect();
        try { 
            $db->query('ALTER TABLE absensi DROP CONSTRAINT IF EXISTS absensi_event_fk'); 
        } catch (\Throwable $e) {}
        
        try { 
            $db->query('ALTER TABLE absensi DROP CONSTRAINT IF EXISTS absensi_user_fk'); 
        } catch (\Throwable $e) {}

        // Hapus index
        try {
            $db->query('DROP INDEX IF EXISTS idx_absensi_event_id');
            $db->query('DROP INDEX IF EXISTS idx_absensi_id_user');
        } catch (\Throwable $e) {}

        // Hapus kolom yang ada
        $columnsToRemove = [];
        if ($db->fieldExists('event_id', 'absensi')) {
            $columnsToRemove[] = 'event_id';
        }
        if ($db->fieldExists('marked_by_admin', 'absensi')) {
            $columnsToRemove[] = 'marked_by_admin';
        }
        if ($db->fieldExists('notes', 'absensi')) {
            $columnsToRemove[] = 'notes';
        }
        
        if (!empty($columnsToRemove)) {
            $this->forge->dropColumn('absensi', $columnsToRemove);
        }
    }
}