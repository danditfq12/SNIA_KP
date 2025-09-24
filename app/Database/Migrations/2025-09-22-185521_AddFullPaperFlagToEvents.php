<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFullPaperFlagToEvents extends Migration
{
    public function up()
    {
        // Tambah kolom boolean untuk ON/OFF submit full paper, jika belum ada
        if (!$this->db->fieldExists('full_paper_submission_active', 'events')) {
            $this->forge->addColumn('events', [
                'full_paper_submission_active' => [
                    'type'       => 'BOOLEAN',
                    'null'       => false,
                    'default'    => false,
                    'after'      => 'abstract_submission_active', // biar rapi; aman kalau DB abaikan
                ],
            ]);
        }

        // Index kecil buat query toggle / listing (opsional)
        $this->db->query('CREATE INDEX IF NOT EXISTS idx_events_fp_active ON events(full_paper_submission_active)');
    }

    public function down()
    {
        // Hapus index kalau ada
        $this->db->query('DROP INDEX IF EXISTS idx_events_fp_active');

        // Hapus kolom kalau ada
        if ($this->db->fieldExists('full_paper_submission_active', 'events')) {
            $this->forge->dropColumn('events', 'full_paper_submission_active');
        }
    }
}