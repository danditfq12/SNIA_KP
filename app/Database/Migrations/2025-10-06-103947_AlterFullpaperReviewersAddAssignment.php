<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterFullpaperReviewersAddAssignment extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('fullpaper_reviewers')) {
            throw new \RuntimeException('Tabel "fullpaper_reviewers" belum ada.');
        }

        $fields = [];

        if (!$this->db->fieldExists('assignment_status', 'fullpaper_reviewers')) {
            $fields['assignment_status'] = [
                'type'       => 'VARCHAR',
                'constraint' => 16, // pending|accepted|declined
                'default'    => 'pending',
                'after'      => 'assigned_at',
            ];
        }

        if (!$this->db->fieldExists('decline_reason', 'fullpaper_reviewers')) {
            $fields['decline_reason'] = [
                'type' => 'TEXT',
                'null' => true,
                'after'=> 'assignment_status',
            ];
        }

        if (!$this->db->fieldExists('accepted_at', 'fullpaper_reviewers')) {
            $fields['accepted_at'] = [
                'type' => 'DATETIME',
                'null' => true,
                'after'=> 'decline_reason',
            ];
        }

        if (!$this->db->fieldExists('declined_at', 'fullpaper_reviewers')) {
            $fields['declined_at'] = [
                'type' => 'DATETIME',
                'null' => true,
                'after'=> 'accepted_at',
            ];
        }

        if ($fields) {
            $this->forge->addColumn('fullpaper_reviewers', $fields);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('fullpaper_reviewers')) {
            foreach (['assignment_status','decline_reason','accepted_at','declined_at'] as $col) {
                if ($this->db->fieldExists($col, 'fullpaper_reviewers')) {
                    $this->forge->dropColumn('fullpaper_reviewers', $col);
                }
            }
        }
    }
}
