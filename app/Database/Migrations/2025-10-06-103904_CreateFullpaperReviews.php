<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateFullpaperReviews extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('fullpaper_reviews')) {
            $this->forge->addField([
                'id' => [
                    'type'           => 'INT',
                    'constraint'     => 11,
                    'unsigned'       => true,
                    'auto_increment' => true,
                ],
                'submission_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'reviewer_id' => [
                    'type'       => 'INT',
                    'constraint' => 11,
                    'unsigned'   => true,
                ],
                'keputusan' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20, // accepted|revision|rejected
                    'default'    => 'pending',
                ],
                'komentar' => [
                    'type' => 'TEXT',
                    'null' => true,
                ],
                'tanggal_review' => [
                    'type'    => 'TIMESTAMP',
                    'default' => new RawSql('CURRENT_TIMESTAMP'),
                ],
            ]);
            $this->forge->addKey('id', true);
            $this->forge->addKey(['submission_id', 'reviewer_id']);
            $this->forge->createTable('fullpaper_reviews', true);
        }
    }

    public function down()
    {
        $this->forge->dropTable('fullpaper_reviews', true);
    }
}
