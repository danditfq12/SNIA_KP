<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class FixPembayaranBuktiBayar extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();
        
        // Ubah kolom bukti_bayar menjadi nullable untuk mendukung Midtrans payment
        $db->query("ALTER TABLE pembayaran ALTER COLUMN bukti_bayar DROP NOT NULL");
        
        // Tambah kolom untuk Midtrans jika belum ada
        $fields = $db->getFieldNames('pembayaran');
        
        if (!in_array('midtrans_order_id', $fields)) {
            $this->forge->addColumn('pembayaran', [
                'midtrans_order_id' => [
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                    'null' => true,
                    'after' => 'payment_reference'
                ]
            ]);
        }
        
        if (!in_array('midtrans_snap_token', $fields)) {
            $this->forge->addColumn('pembayaran', [
                'midtrans_snap_token' => [
                    'type' => 'VARCHAR',
                    'constraint' => 255,
                    'null' => true,
                    'after' => 'midtrans_order_id'
                ]
            ]);
        }
        
        if (!in_array('midtrans_transaction_id', $fields)) {
            $this->forge->addColumn('pembayaran', [
                'midtrans_transaction_id' => [
                    'type' => 'VARCHAR',
                    'constraint' => 100,
                    'null' => true,
                    'after' => 'midtrans_snap_token'
                ]
            ]);
        }
        
        if (!in_array('midtrans_payment_type', $fields)) {
            $this->forge->addColumn('pembayaran', [
                'midtrans_payment_type' => [
                    'type' => 'VARCHAR',
                    'constraint' => 50,
                    'null' => true,
                    'after' => 'midtrans_transaction_id'
                ]
            ]);
        }
        
        if (!in_array('midtrans_raw_response', $fields)) {
            $this->forge->addColumn('pembayaran', [
                'midtrans_raw_response' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'after' => 'midtrans_payment_type'
                ]
            ]);
        }
        
        if (!in_array('midtrans_settlement_time', $fields)) {
            $this->forge->addColumn('pembayaran', [
                'midtrans_settlement_time' => [
                    'type' => 'TIMESTAMP',
                    'null' => true,
                    'after' => 'midtrans_raw_response'
                ]
            ]);
        }
        
        // Update existing NULL values untuk bukti_bayar 
        $db->query("UPDATE pembayaran SET bukti_bayar = '' WHERE bukti_bayar IS NULL");
        
        // Buat index untuk performa
        $db->query('CREATE INDEX IF NOT EXISTS idx_pembayaran_midtrans_order ON pembayaran(midtrans_order_id)');
        $db->query('CREATE INDEX IF NOT EXISTS idx_pembayaran_method ON pembayaran(metode)');
    }

    public function down()
    {
        $db = \Config\Database::connect();
        
        // Drop indexes
        $db->query('DROP INDEX IF EXISTS idx_pembayaran_midtrans_order');
        $db->query('DROP INDEX IF EXISTS idx_pembayaran_method');
        
        // Drop columns
        $columnsToRemove = [
            'midtrans_settlement_time',
            'midtrans_raw_response', 
            'midtrans_payment_type',
            'midtrans_transaction_id',
            'midtrans_snap_token',
            'midtrans_order_id'
        ];
        
        foreach ($columnsToRemove as $column) {
            if ($db->fieldExists($column, 'pembayaran')) {
                try {
                    $this->forge->dropColumn('pembayaran', $column);
                } catch (\Exception $e) {
                    log_message('warning', 'Could not drop column ' . $column . ': ' . $e->getMessage());
                }
            }
        }
        
        // Kembalikan constraint NOT NULL untuk bukti_bayar
        $db->query("UPDATE pembayaran SET bukti_bayar = 'no_file.txt' WHERE bukti_bayar IS NULL OR bukti_bayar = ''");
        $db->query("ALTER TABLE pembayaran ALTER COLUMN bukti_bayar SET NOT NULL");
    }
}