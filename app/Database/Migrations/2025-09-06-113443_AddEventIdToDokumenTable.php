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
           <?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEventIdToDokumenTable extends Migration
{
    public function up()
    {
        // Check if event_id column already exists
        if (!$this->db->fieldExists('event_id', 'dokumen')) {
            // Add event_id column to dokumen table
            $this->forge->addColumn('dokumen', [
                'event_id' => [
                    'type' => 'INT',
                    'constraint' => 11,
                    'null' => true,
                    'after' => 'id_user'
                ]
            ]);
        }

        // Check if events table exists before adding foreign key
        if ($this->db->tableExists('events')) {
            // Drop existing constraint if it exists
            try {
                $this->db->query('ALTER TABLE dokumen DROP FOREIGN KEY IF EXISTS fk_dokumen_event');
            } catch (\Exception $e) {
                // Constraint doesn't exist, continue
            }

            // Add foreign key constraint with proper error handling
            try {
                $this->db->query('ALTER TABLE dokumen ADD CONSTRAINT fk_dokumen_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL ON UPDATE CASCADE');
            } catch (\Exception $e) {
                log_message('error', 'Failed to add foreign key constraint: ' . $e->getMessage());
            }
        }

        // Add indexes for better performance (check if they exist first)
        $indexes = [
            'idx_dokumen_event' => 'CREATE INDEX idx_dokumen_event ON dokumen(event_id)',
            'idx_dokumen_tipe' => 'CREATE INDEX idx_dokumen_tipe ON dokumen(tipe)',
            'idx_dokumen_user_event' => 'CREATE INDEX idx_dokumen_user_event ON dokumen(id_user, event_id)',
            'idx_dokumen_uploaded_at' => 'CREATE INDEX idx_dokumen_uploaded_at ON dokumen(uploaded_at)'
        ];

        foreach ($indexes as $indexName => $indexSQL) {
            try {
                $this->db->query($indexSQL);
            } catch (\Exception $e) {
                // Index might already exist, continue
                log_message('info', "Index {$indexName} might already exist: " . $e->getMessage());
            }
        }
    }

    public function down()
    {
        // Drop indexes
        $indexes = ['idx_dokumen_event', 'idx_dokumen_tipe', 'idx_dokumen_user_event', 'idx_dokumen_uploaded_at'];
        
        foreach ($indexes as $index) {
            try {
                $this->db->query("DROP INDEX IF EXISTS {$index} ON dokumen");
            } catch (\Exception $e) {
                log_message('info', "Failed to drop index {$index}: " . $e->getMessage());
            }
        }

        // Drop foreign key constraint
        try {
            $this->db->query('ALTER TABLE dokumen DROP FOREIGN KEY IF EXISTS fk_dokumen_event');
        } catch (\Exception $e) {
            log_message('info', 'Failed to drop foreign key constraint: ' . $e->getMessage());
        }

        // Drop column if it exists
        if ($this->db->fieldExists('event_id', 'dokumen')) {
            $this->forge->dropColumn('dokumen', 'event_id');
        }
    }
}     'null' => true,
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