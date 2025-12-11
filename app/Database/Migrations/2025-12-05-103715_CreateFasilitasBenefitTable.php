<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFasilitasBenefitTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'SERIAL',
                'unsigned'       => true,
            ],
            'event_id' => [
                'type'       => 'INT',
                'unsigned'   => true,
                'null'       => false,
            ],
            'participant_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => false,
                'comment'    => 'presenter_online, presenter_offline, audience_online, audience_offline',
            ],
            'fasilitas' => [
                'type' => 'TEXT',
                'null' => false,
                'comment' => 'JSON array of facilities',
            ],
            'is_active' => [
                'type'    => 'BOOLEAN',
                'default' => true,
            ],
            'created_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addForeignKey('event_id', 'events', 'id', 'CASCADE', 'CASCADE');

        // Create table first
        $this->forge->createTable('fasilitas_benefit');
        
        // Then create indexes
        $this->db->query('CREATE INDEX idx_fasilitas_event ON fasilitas_benefit(event_id)');
        $this->db->query('CREATE INDEX idx_fasilitas_type ON fasilitas_benefit(participant_type)');
        $this->db->query('CREATE UNIQUE INDEX idx_fasilitas_unique ON fasilitas_benefit(event_id, participant_type)');
    }

    public function down()
    {
        $this->forge->dropTable('fasilitas_benefit');
    }
}