<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEventRoleBasedPricingFixed extends Migration
{
    public function up()
    {
        // Get database instance
        $db = \Config\Database::connect();
        
        // Check if registration_fee column exists before trying to drop it
        if ($db->fieldExists('registration_fee', 'events')) {
            $this->forge->dropColumn('events', 'registration_fee');
        }
        
        // Add new pricing columns
        $this->forge->addColumn('events', [
            'presenter_fee_offline' => [
                'type' => 'NUMERIC', 
                'constraint' => '10,2',
                'default' => 0,
                'after' => 'zoom_link'
            ],
            'audience_fee_online' => [
                'type' => 'NUMERIC',
                'constraint' => '10,2', 
                'default' => 0,
                'after' => 'presenter_fee_offline'
            ],
            'audience_fee_offline' => [
                'type' => 'NUMERIC',
                'constraint' => '10,2',
                'default' => 0,
                'after' => 'audience_fee_online'
            ]
        ]);

        // Check if participation_type column exists before adding it
        if (!$db->fieldExists('participation_type', 'pembayaran')) {
            $this->forge->addColumn('pembayaran', [
                'participation_type' => [
                    'type' => 'VARCHAR',
                    'constraint' => 20,
                    'default' => 'offline',
                    'after' => 'event_id'
                ]
            ]);
            
            // Add index for better performance
            $db->query('CREATE INDEX IF NOT EXISTS idx_pembayaran_participation ON pembayaran(participation_type)');
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();
        
        // Drop new columns
        $fieldsToCheck = ['presenter_fee_offline', 'audience_fee_online', 'audience_fee_offline'];
        foreach ($fieldsToCheck as $field) {
            if ($db->fieldExists($field, 'events')) {
                $this->forge->dropColumn('events', $field);
            }
        }
        
        // Drop participation_type and its index
        if ($db->fieldExists('participation_type', 'pembayaran')) {
            $db->query('DROP INDEX IF EXISTS idx_pembayaran_participation');
            $this->forge->dropColumn('pembayaran', 'participation_type');
        }
        
        // Add back the old registration_fee column if it doesn't exist
        if (!$db->fieldExists('registration_fee', 'events')) {
            $this->forge->addColumn('events', [
                'registration_fee' => [
                    'type' => 'NUMERIC',
                    'constraint' => '10,2',
                    'default' => 0,
                    'after' => 'zoom_link'
                ]
            ]);
        }
    }
}