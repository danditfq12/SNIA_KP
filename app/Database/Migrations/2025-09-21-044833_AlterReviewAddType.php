<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterReviewAddType extends Migration
{
    public function up()
    {
        if (!$this->db->tableExists('review')) {
            throw new \RuntimeException('Tabel "review" belum ada. Jalankan migrasi pembuat tabel review dulu.');
        }

        if (!$this->db->fieldExists('review_type', 'review')) {
            $this->forge->addColumn('review', [
                'review_type' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20, // ABSTRAK | FULL
                    'default'    => 'ABSTRAK',
                    'after'      => 'id_reviewer'
                ],
            ]);
        }

        if (!$this->db->fieldExists('id_submission', 'review')) {
            $this->forge->addColumn('review', [
                'id_submission' => [
                    'type'  => 'INT',
                    'null'  => true,
                    'after' => 'id_abstrak'
                ],
            ]);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('review')) {
            if ($this->db->fieldExists('review_type', 'review')) {
                $this->forge->dropColumn('review', 'review_type');
            }
            if ($this->db->fieldExists('id_submission', 'review')) {
                $this->forge->dropColumn('review', 'id_submission');
            }
        }
    }
}