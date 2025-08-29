<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateReviewTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_review'   => ['type' => 'SERIAL'],
            'id_abstrak'  => ['type' => 'INT'],
            'id_reviewer' => ['type' => 'INT'],
            'keputusan'   => ['type' => 'VARCHAR', 'constraint' => 20],
            'komentar'    => ['type' => 'TEXT'],
            'tanggal_review' => ['type' => 'TIMESTAMP', 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addKey('id_review', true);
        $this->forge->createTable('review');
    }

    public function down()
    {
        $this->forge->dropTable('review');
    }
}