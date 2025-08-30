<?php
namespace App\Database\Migrations;
use CodeIgniter\Database\Migration;

class CreateEventsTable extends Migration
{
    public function up()
    {
        $this->db->query("DO \$\$ BEGIN CREATE TYPE event_format AS ENUM ('online', 'offline'); EXCEPTION WHEN duplicate_object THEN null; END \$\$;");
        
        $this->forge->addField([
            'id' => ['type' => 'SERIAL'],
            'title' => ['type' => 'VARCHAR', 'constraint' => 255],
            'description' => ['type' => 'TEXT', 'null' => true],
            'event_date' => ['type' => 'DATE'],
            'event_time' => ['type' => 'TIME'],
            'format' => ['type' => 'event_format'],
            'location' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'zoom_link' => ['type' => 'VARCHAR', 'constraint' => 500, 'null' => true],
            'registration_fee' => ['type' => 'DECIMAL', 'constraint' => '10,2'],
            'max_participants' => ['type' => 'INTEGER', 'null' => true],
            'registration_deadline' => ['type' => 'TIMESTAMP', 'null' => true],
            'abstract_deadline' => ['type' => 'TIMESTAMP', 'null' => true],
            'registration_active' => ['type' => 'BOOLEAN', 'default' => true],
            'abstract_submission_active' => ['type' => 'BOOLEAN', 'default' => true],
            'is_active' => ['type' => 'BOOLEAN', 'default' => true],
            'created_at' => ['type' => 'TIMESTAMP', 'null' => true],
            'updated_at' => ['type' => 'TIMESTAMP', 'null' => true],
        ]);
        
        $this->forge->addPrimaryKey('id');
        $this->forge->createTable('events');
        
        $this->db->query("CREATE TRIGGER update_events_updated_at BEFORE INSERT OR UPDATE ON events FOR EACH ROW EXECUTE FUNCTION update_updated_at_column()");
    }

    public function down()
    {
        $this->forge->dropTable('events');
        $this->db->query("DROP TYPE IF EXISTS event_format CASCADE");
    }
}