<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateReviewerKategoriTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id'          => ['type' => 'SERIAL'],
            'id_reviewer' => ['type' => 'INT'],
            'id_kategori' => ['type' => 'INT'],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->createTable('reviewer_kategori');
    }

    public function down()
    {
        $this->forge->dropTable('reviewer_kategori');
    }
}