<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateVoucherTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_voucher'   => ['type' => 'SERIAL'],
            'kode_voucher' => ['type' => 'VARCHAR', 'constraint' => 50],
            'tipe'         => ['type' => 'VARCHAR', 'constraint' => 20],
            'nilai'        => ['type' => 'NUMERIC', 'constraint' => '12,2'],
            'kuota'        => ['type' => 'INT'],
            'masa_berlaku' => ['type' => 'DATE'],
            'status'       => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'aktif'],
        ]);
        $this->forge->addKey('id_voucher', true);
        $this->forge->createTable('voucher');
    }

    public function down()
    {
        $this->forge->dropTable('voucher');
    }
}
