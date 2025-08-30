<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateEventsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'SERIAL',
                'unsigned'       => true,
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'description' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'event_date' => [
                'type' => 'DATE',
            ],
            'event_time' => [
                'type' => 'TIME',
            ],
            'format' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'offline',
            ],
            'location' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'zoom_link' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
            ],
            'registration_fee' => [
                'type'       => 'NUMERIC',
                'constraint' => '10,2',
                'default'    => 0,
            ],
            'max_participants' => [
                'type'     => 'INT',
                'null'     => true,
            ],
            'registration_deadline' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'abstract_deadline' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'registration_active' => [
                'type'    => 'BOOLEAN',
                'default' => true,
            ],
            'abstract_submission_active' => [
                'type'    => 'BOOLEAN',
                'default' => true,
            ],
            'is_active' => [
                'type'    => 'BOOLEAN',
                'default' => true,
            ],
            'created_at' => [
                'type'    => 'TIMESTAMP',
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
            'updated_at' => [
                'type'    => 'TIMESTAMP',
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('events');

        // Create indexes for better performance
        $this->db->query('CREATE INDEX idx_events_date ON events(event_date)');
        $this->db->query('CREATE INDEX idx_events_active ON events(is_active)');
        $this->db->query('CREATE INDEX idx_events_format ON events(format)');
        
        // Create trigger for updated_at
        $this->db->query("
            CREATE OR REPLACE FUNCTION update_events_updated_at()
            RETURNS trigger AS \$\$
            BEGIN
                NEW.updated_at = NOW();
                RETURN NEW;
            END;
            \$\$ LANGUAGE plpgsql;
        ");
        
        $this->db->query("
            CREATE TRIGGER trigger_events_updated_at
            BEFORE UPDATE ON events
            FOR EACH ROW
            EXECUTE PROCEDURE update_events_updated_at();
        ");
    }

    public function down()
    {
        // Drop trigger and function
        $this->db->query('DROP TRIGGER IF EXISTS trigger_events_updated_at ON events');
        $this->db->query('DROP FUNCTION IF EXISTS update_events_updated_at()');
        
        // Drop indexes
        $this->db->query('DROP INDEX IF EXISTS idx_events_date');
        $this->db->query('DROP INDEX IF EXISTS idx_events_active');
        $this->db->query('DROP INDEX IF EXISTS idx_events_format');
        
        $this->forge->dropTable('events');
    }
}