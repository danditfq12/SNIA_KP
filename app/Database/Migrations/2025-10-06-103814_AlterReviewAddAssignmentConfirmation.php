<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class AlterReviewAddAssignmentConfirmation extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('review')) {
            throw new \RuntimeException('Tabel "review" belum ada.');
        }

        $fields = [];

        if (!$this->db->fieldExists('assignment_status', 'review')) {
            $fields['assignment_status'] = [
                'type'       => 'VARCHAR',
                'constraint' => 16, // pending|accepted|declined
                'default'    => 'pending',
                'after'      => 'review_type',
            ];
        }

        if (!$this->db->fieldExists('decline_reason', 'review')) {
            $fields['decline_reason'] = [
                'type' => 'TEXT',
                'null' => true,
                'after'=> 'assignment_status',
            ];
        }

        if (!$this->db->fieldExists('accepted_at', 'review')) {
            $fields['accepted_at'] = [
                'type' => 'TIMESTAMP',
                'null' => true,
                'after'=> 'decline_reason',
            ];
        }

        if (!$this->db->fieldExists('declined_at', 'review')) {
            $fields['declined_at'] = [
                'type' => 'TIMESTAMP',
                'null' => true,
                'after'=> 'accepted_at',
            ];
        }

        if ($fields) {
            $this->forge->addColumn('review', $fields);
        }

        // Optional: seed status lama dianggap accepted
        $this->db->query("UPDATE review SET assignment_status = 'accepted' WHERE assignment_status IS NULL OR assignment_status = ''");
    }

    public function down()
    {
        if ($this->db->tableExists('review')) {
            foreach (['assignment_status','decline_reason','accepted_at','declined_at'] as $col) {
                if ($this->db->fieldExists($col, 'review')) {
                    $this->forge->dropColumn('review', $col);
                }
            }
        }
    }
}