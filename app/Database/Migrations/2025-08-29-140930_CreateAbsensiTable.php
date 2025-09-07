<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateAbsensiTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_absensi' => ['type' => 'SERIAL'],
            'id_user'    => ['type' => 'INT'],
            'event_id'   => ['type' => 'INT'], // FIXED: Add event_id field
            'qr_code'    => ['type' => 'VARCHAR', 'constraint' => 255],
            'status'     => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'tidak'],
            'waktu_scan' => ['type' => 'TIMESTAMP', 'null' => true],
            'marked_by_admin' => ['type' => 'INT', 'null' => true], // FIXED: Add marked_by_admin field
            'notes'      => ['type' => 'TEXT', 'null' => true], // FIXED: Add notes field
        ]);
        $this->forge->addKey('id_absensi', true);
        $this->forge->createTable('absensi');
    }

    public function down()
    {
        $this->forge->dropTable('absensi');
    }
}