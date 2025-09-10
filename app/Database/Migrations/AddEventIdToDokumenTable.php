<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEventIdToDokumenTable extends Migration
{
    public function up()
    {
        // Add event_id column to dokumen table
        $this->forge->addColumn('dokumen', [
            'event_id' => [
                'type' => 'INT',
                'null' => true,
                'after' => 'id_user'
            ]
        ]);

        // Add foreign key constraint
        $this->db->query('ALTER TABLE dokumen ADD CONSTRAINT fk_dokumen_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL');

        // Add index for better performance
        $this->db->query('CREATE INDEX idx_dokumen_event ON dokumen(event_id)');
        $this->db->query('CREATE INDEX idx_dokumen_tipe ON dokumen(tipe)');
        $this->db->query('CREATE INDEX idx_dokumen_user_event ON dokumen(id_user, event_id)');
    }

    public function down()
    {
        // Drop indexes
        $this->db->query('DROP INDEX IF EXISTS idx_dokumen_event');
        $this->db->query('DROP INDEX IF EXISTS idx_dokumen_tipe');
        $this->db->query('DROP INDEX IF EXISTS idx_dokumen_user_event');

        // Drop foreign key constraint
        $this->db->query('ALTER TABLE dokumen DROP CONSTRAINT IF EXISTS fk_dokumen_event');

        // Drop column
        $this->forge->dropColumn('dokumen', 'event_id');
    }
}