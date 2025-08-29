<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateJadwalTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_jadwal'    => ['type' => 'SERIAL'],
            'nama_kegiatan'=> ['type' => 'VARCHAR', 'constraint' => 255],
            'tanggal'      => ['type' => 'DATE'],
            'jam_mulai'    => ['type' => 'TIME'],
            'jam_selesai'  => ['type' => 'TIME'],
            'lokasi'       => ['type' => 'VARCHAR', 'constraint' => 255],
        ]);
        $this->forge->addKey('id_jadwal', true);
        $this->forge->createTable('jadwal');
    }

    public function down()
    {
        $this->forge->dropTable('jadwal');
    }
}
