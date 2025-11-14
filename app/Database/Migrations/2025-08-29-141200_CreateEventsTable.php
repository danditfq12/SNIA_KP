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
            // Event date & time (support multi-day events)
            'event_date' => [
                'type' => 'DATE',
                'comment' => 'Event start date',
            ],
            'event_time' => [
                'type' => 'TIME',
                'comment' => 'Event start time',
            ],
            'event_end_date' => [
                'type' => 'DATE',
                'null' => true,
                'comment' => 'Event end date (for multi-day events)',
            ],
            'event_end_time' => [
                'type' => 'TIME',
                'null' => true,
                'comment' => 'Event end time',
            ],
            // Event format
            'format' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'both',
                'comment'    => 'online, offline, or both',
            ],
            'location' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
            ],
            'zoom_link' => [
                'type'       => 'VARCHAR',
                'constraint' => 500,
                'null'       => true,
            ],
            // Registration waves (JSON format)
            'registration_waves' => [
                'type' => 'JSONB',
                'null' => true,
                'default' => '[]',
                'comment' => 'Registration waves with prices for each role and type',
            ],
            // Deadlines
            'abstract_deadline' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'abstract_revision_deadline' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            'full_paper_deadline' => [
                'type' => 'TIMESTAMP',
                'null' => true,
            ],
            // Settings
            'max_participants' => [
                'type'     => 'INT',
                'null'     => true,
            ],
            'registration_active' => [
                'type'    => 'BOOLEAN',
                'default' => true,
            ],
            'abstract_submission_active' => [
                'type'    => 'BOOLEAN',
                'default' => false,
            ],
            'abstract_revision_active' => [
                'type'    => 'BOOLEAN',
                'default' => false,
            ],
            'full_paper_submission_active' => [
                'type'    => 'BOOLEAN',
                'default' => false,
            ],
            'is_active' => [
                'type'    => 'BOOLEAN',
                'default' => true,
            ],
            // Timestamps
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
        $this->db->query('CREATE INDEX idx_events_end_date ON events(event_end_date)');
        $this->db->query('CREATE INDEX idx_events_active ON events(is_active)');
        $this->db->query('CREATE INDEX idx_events_format ON events(format)');
        $this->db->query('CREATE INDEX idx_events_registration_waves ON events USING gin(registration_waves)');
        
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

        // Add comment to table
        $this->db->query("
            COMMENT ON TABLE events IS 'Events table with support for multi-day events and registration waves';
        ");
    }

    public function down()
    {
        // Drop trigger and function
        $this->db->query('DROP TRIGGER IF EXISTS trigger_events_updated_at ON events');
        $this->db->query('DROP FUNCTION IF EXISTS update_events_updated_at()');
        
        // Drop indexes
        $this->db->query('DROP INDEX IF EXISTS idx_events_date');
        $this->db->query('DROP INDEX IF EXISTS idx_events_end_date');
        $this->db->query('DROP INDEX IF EXISTS idx_events_active');
        $this->db->query('DROP INDEX IF EXISTS idx_events_format');
        $this->db->query('DROP INDEX IF EXISTS idx_events_registration_waves');
        
        $this->forge->dropTable('events');
    }
}