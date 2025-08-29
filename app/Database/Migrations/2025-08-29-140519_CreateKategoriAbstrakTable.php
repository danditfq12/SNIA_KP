<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateKategoriAbstrakTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_kategori' => ['type' => 'SERIAL'],
            'nama_kategori' => ['type' => 'VARCHAR', 'constraint' => 100],
            'deskripsi' => ['type' => 'TEXT', 'null' => true],
        ]);
        $this->forge->addKey('id_kategori', true);
        $this->forge->createTable('kategori_abstrak');
    }

    public function down()
    {
        $this->forge->dropTable('kategori_abstrak');
    }
}
