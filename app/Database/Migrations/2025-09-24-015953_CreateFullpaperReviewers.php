<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateFullpaperReviewers extends Migration
{
    public function up()
    {
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
            'assigned_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);
        $this->forge->addKey('id', true);
        $this->forge->addKey(['submission_id', 'reviewer_id']);
        $this->forge->createTable('fullpaper_reviewers', true);
    }

    public function down()
    {
        $this->forge->dropTable('fullpaper_reviewers', true);
    }
}