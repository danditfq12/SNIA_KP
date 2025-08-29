<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreatePembayaranTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_pembayaran' => ['type' => 'SERIAL'],
            'id_user'       => ['type' => 'INT'],
            'metode'        => ['type' => 'VARCHAR', 'constraint' => 50],
            'jumlah'        => ['type' => 'NUMERIC', 'constraint' => '12,2'],
            'bukti_bayar'   => ['type' => 'VARCHAR', 'constraint' => 255],
            'status'        => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'pending'],
            'tanggal_bayar' => ['type' => 'TIMESTAMP', 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'id_voucher'    => ['type' => 'INT', 'null' => true],
        ]);
        $this->forge->addKey('id_pembayaran', true);
        $this->forge->createTable('pembayaran');
    }

    public function down()
    {
        $this->forge->dropTable('pembayaran');
    }
}
