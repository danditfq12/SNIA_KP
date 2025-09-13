<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterNotifikasiToV2 extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        // More reliable way to check if column exists in PostgreSQL
        $checkColumn = function($table, $column) use ($db) {
            $result = $db->query("
                SELECT column_name 
                FROM information_schema.columns 
                WHERE table_name = ? AND column_name = ?
            ", [$table, $column]);
            
            return $result->getNumRows() > 0;
        };

        // Add 'type' column if it doesn't exist
        if (!$checkColumn('notifikasi', 'type')) {
            $this->forge->addColumn('notifikasi', [
                'type' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                ],
            ]);
        }

        // Add 'meta_json' column if it doesn't exist
        if (!$checkColumn('notifikasi', 'meta_json')) {
            $this->forge->addColumn('notifikasi', [
                'meta_json' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
            ]);
        }

        // Add 'read_at' column if it doesn't exist
        if (!$checkColumn('notifikasi', 'read_at')) {
            $this->forge->addColumn('notifikasi', [
                'read_at' => [
                    'type' => 'TIMESTAMP',
                    'null' => true,
                ],
            ]);
        }

        // Add 'updated_at' column if it doesn't exist
        if (!$checkColumn('notifikasi', 'updated_at')) {
            $this->forge->addColumn('notifikasi', [
                'updated_at' => [
                    'type' => 'TIMESTAMP',
                    'null' => true,
                ],
            ]);
        }

        // Backfill: copy data from 'meta' to 'meta_json' if both exist
        if ($checkColumn('notifikasi', 'meta') && $checkColumn('notifikasi', 'meta_json')) {
            try {
                $db->query("UPDATE notifikasi SET meta_json = meta WHERE meta_json IS NULL AND meta IS NOT NULL");
            } catch (\Exception $e) {
                // If update fails, log but don't stop migration
                log_message('warning', 'Failed to backfill meta_json: ' . $e->getMessage());
            }
        }

        // Add indexes safely
        try {
            $db->query('CREATE INDEX IF NOT EXISTS idx_notifikasi_user ON notifikasi (id_user)');
        } catch (\Exception $e) {
            log_message('warning', 'Failed to create index idx_notifikasi_user: ' . $e->getMessage());
        }

        try {
            $db->query('CREATE INDEX IF NOT EXISTS idx_notifikasi_type ON notifikasi (type)');
        } catch (\Exception $e) {
            log_message('warning', 'Failed to create index idx_notifikasi_type: ' . $e->getMessage());
        }

        try {
            $db->query('CREATE INDEX IF NOT EXISTS idx_notifikasi_read ON notifikasi (read)');
        } catch (\Exception $e) {
            log_message('warning', 'Failed to create index idx_notifikasi_read: ' . $e->getMessage());
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();

        // Check column exists before dropping
        $checkColumn = function($table, $column) use ($db) {
            $result = $db->query("
                SELECT column_name 
                FROM information_schema.columns 
                WHERE table_name = ? AND column_name = ?
            ", [$table, $column]);
            
            return $result->getNumRows() > 0;
        };

        // Drop indexes first
        try {
            $db->query('DROP INDEX IF EXISTS idx_notifikasi_user');
            $db->query('DROP INDEX IF EXISTS idx_notifikasi_type');
            $db->query('DROP INDEX IF EXISTS idx_notifikasi_read');
        } catch (\Exception $e) {
            log_message('warning', 'Failed to drop some indexes: ' . $e->getMessage());
        }

        // Drop columns if they exist
        $columnsToRemove = [];
        
        if ($checkColumn('notifikasi', 'updated_at')) {
            $columnsToRemove[] = 'updated_at';
        }
        if ($checkColumn('notifikasi', 'read_at')) {
            $columnsToRemove[] = 'read_at';
        }
        if ($checkColumn('notifikasi', 'type')) {
            $columnsToRemove[] = 'type';
        }
        // Note: We're not dropping meta_json to preserve data
        
        if (!empty($columnsToRemove)) {
            try {
                $this->forge->dropColumn('notifikasi', $columnsToRemove);
            } catch (\Exception $e) {
                log_message('warning', 'Failed to drop some columns: ' . $e->getMessage());
            }
        }
    }
}