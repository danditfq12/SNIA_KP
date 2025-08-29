<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateLogAktivitasTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_log'   => ['type' => 'SERIAL'],
            'id_user'  => ['type' => 'INT'],
            'aktivitas'=> ['type' => 'VARCHAR', 'constraint' => 255],
            'waktu'    => ['type' => 'TIMESTAMP', 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addKey('id_log', true);
        $this->forge->createTable('log_aktivitas');
    }

    public function down()
    {
        $this->forge->dropTable('log_aktivitas');
    }
}
