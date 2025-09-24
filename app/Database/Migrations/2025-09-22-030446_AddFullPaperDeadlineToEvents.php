<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFullPaperDeadlineToEvents extends Migration
{
    public function up()
    {
        // Tambah kolom jika belum ada
        if (!$this->db->fieldExists('full_paper_deadline', 'events')) {
            $this->forge->addColumn('events', [
                'full_paper_deadline' => [
                    'type' => 'TIMESTAMP',
                    'null' => true,
                    'after' => 'abstract_deadline', // optional, agar rapi
                ],
            ]);
        }

        // Tambah index (aman kalau sudah ada, karena kita pakai IF NOT EXISTS via raw SQL)
        $this->db->query('
            DO $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM pg_class c
                    JOIN pg_namespace n ON n.oid = c.relnamespace
                    WHERE c.relname = \'idx_events_fp_deadline\'
                      AND n.nspname = current_schema()
                ) THEN
                    CREATE INDEX idx_events_fp_deadline ON events(full_paper_deadline);
                END IF;
            END
            $$;
        ');
    }

    public function down()
    {
        // Drop index kalau ada
        $this->db->query('DROP INDEX IF EXISTS idx_events_fp_deadline');

        // Drop kolom kalau ada
        if ($this->db->fieldExists('full_paper_deadline', 'events')) {
            $this->forge->dropColumn('events', 'full_paper_deadline');
        }
    }
}