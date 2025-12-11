<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIsLandingToEvents extends Migration
{
    public function up()
    {
        // Tambah kolom is_landing
        $this->forge->addColumn('events', [
            'is_landing' => [
                'type'    => 'BOOLEAN',
                'null'    => false,
                'default' => false,
                'comment' => 'Mark this event as the one shown on landing page',
            ],
        ]);

        // Index untuk mempercepat query landing
        $this->db->simpleQuery('CREATE INDEX IF NOT EXISTS idx_events_is_landing ON events(is_landing);');
    }

    public function down()
    {
        // Hapus index + kolom
        $this->db->simpleQuery('DROP INDEX IF EXISTS idx_events_is_landing;');
        $this->forge->dropColumn('events', 'is_landing');
    }
}
