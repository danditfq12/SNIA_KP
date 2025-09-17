<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddMissingPaymentColumns extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();
        
        // Check if columns exist before adding
        $fields = $db->getFieldNames('pembayaran');
        
        $columnsToAdd = [];
        
        if (!in_array('original_amount', $fields)) {
            $columnsToAdd['original_amount'] = [
                'type' => 'NUMERIC',
                'constraint' => '12,2',
                'null' => true,
                'default' => null
            ];
        }
        
        if (!in_array('discount_amount', $fields)) {
            $columnsToAdd['discount_amount'] = [
                'type' => 'NUMERIC',
                'constraint' => '12,2',
                'null' => true,
                'default' => 0
            ];
        }
        
        if (!in_array('payment_reference', $fields)) {
            $columnsToAdd['payment_reference'] = [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true
            ];
        }
        
        if (!in_array('auto_verified', $fields)) {
            $columnsToAdd['auto_verified'] = [
                'type' => 'BOOLEAN',
                'default' => false,
                'null' => true
            ];
        }
        
        if (!in_array('features_unlocked_at', $fields)) {
            $columnsToAdd['features_unlocked_at'] = [
                'type' => 'TIMESTAMP',
                'null' => true
            ];
        }

        if (!in_array('midtrans_order_id', $fields)) {
            $columnsToAdd['midtrans_order_id'] = [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true
            ];
        }

        if (!in_array('midtrans_snap_token', $fields)) {
            $columnsToAdd['midtrans_snap_token'] = [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => true
            ];
        }

        if (!in_array('midtrans_transaction_id', $fields)) {
            $columnsToAdd['midtrans_transaction_id'] = [
                'type' => 'VARCHAR',
                'constraint' => 100,
                'null' => true
            ];
        }

        if (!in_array('midtrans_payment_type', $fields)) {
            $columnsToAdd['midtrans_payment_type'] = [
                'type' => 'VARCHAR',
                'constraint' => 50,
                'null' => true
            ];
        }

        if (!in_array('midtrans_raw_response', $fields)) {
            $columnsToAdd['midtrans_raw_response'] = [
                'type' => 'TEXT',
                'null' => true
            ];
        }

        if (!in_array('midtrans_settlement_time', $fields)) {
            $columnsToAdd['midtrans_settlement_time'] = [
                'type' => 'TIMESTAMP',
                'null' => true
            ];
        }

        // Add columns if any need to be added
        if (!empty($columnsToAdd)) {
            try {
                $this->forge->addColumn('pembayaran', $columnsToAdd);
                echo "Added " . count($columnsToAdd) . " columns to pembayaran table.\n";
            } catch (\Exception $e) {
                echo "Some columns may already exist: " . $e->getMessage() . "\n";
            }
        } else {
            echo "All required columns already exist in pembayaran table.\n";
        }

        // Add indexes for better performance
        try {
            $db->query('CREATE INDEX IF NOT EXISTS idx_pembayaran_midtrans_order ON pembayaran(midtrans_order_id)');
            $db->query('CREATE INDEX IF NOT EXISTS idx_pembayaran_reference ON pembayaran(payment_reference)');
            $db->query('CREATE INDEX IF NOT EXISTS idx_pembayaran_auto_verified ON pembayaran(auto_verified)');
        } catch (\Exception $e) {
            echo "Note: Some indexes may already exist.\n";
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();
        
        // Drop indexes
        try {
            $db->query('DROP INDEX IF EXISTS idx_pembayaran_midtrans_order');
            $db->query('DROP INDEX IF EXISTS idx_pembayaran_reference');
            $db->query('DROP INDEX IF EXISTS idx_pembayaran_auto_verified');
        } catch (\Exception $e) {
            // Continue if indexes don't exist
        }
        
        // Drop columns (in reverse order)
        $columnsToRemove = [
            'midtrans_settlement_time',
            'midtrans_raw_response',
            'midtrans_payment_type',
            'midtrans_transaction_id',
            'midtrans_snap_token',
            'midtrans_order_id',
            'features_unlocked_at',
            'auto_verified',
            'payment_reference',
            'discount_amount',
            'original_amount'
        ];
        
        $fields = $db->getFieldNames('pembayaran');
        $existingColumns = [];
        
        foreach ($columnsToRemove as $column) {
            if (in_array($column, $fields)) {
                $existingColumns[] = $column;
            }
        }
        
        if (!empty($existingColumns)) {
            try {
                $this->forge->dropColumn('pembayaran', $existingColumns);
            } catch (\Exception $e) {
                echo "Error dropping columns: " . $e->getMessage() . "\n";
            }
        }
    }
}