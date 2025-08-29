<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateDokumenTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_dokumen' => ['type' => 'SERIAL'],
            'id_user'    => ['type' => 'INT'],
            'tipe'       => ['type' => 'VARCHAR', 'constraint' => 50],
            'file_path'  => ['type' => 'VARCHAR', 'constraint' => 255],
            'syarat'     => ['type' => 'VARCHAR', 'constraint' => 255],
            'uploaded_at'=> ['type' => 'TIMESTAMP', 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addKey('id_dokumen', true);
        $this->forge->createTable('dokumen');
    }

    public function down()
    {
        $this->forge->dropTable('dokumen');
    }
}
