<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateAbstrakTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_abstrak'   => ['type' => 'SERIAL'],
            'id_user'      => ['type' => 'INT'],
            'id_kategori'  => ['type' => 'INT'],
            'judul'        => ['type' => 'VARCHAR', 'constraint' => 255],
            'file_abstrak' => ['type' => 'VARCHAR', 'constraint' => 255],
            'status'       => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'menunggu'],
            'tanggal_upload' => ['type' => 'TIMESTAMP', 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'revisi_ke'    => ['type' => 'INT', 'default' => 0],
        ]);
        $this->forge->addKey('id_abstrak', true);
        $this->forge->createTable('abstrak');
    }

    public function down()
    {
        $this->forge->dropTable('abstrak');
    }
}