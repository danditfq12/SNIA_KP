<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddFullPaperColumns extends Migration
{
    private function resolveTargetTable(): string
    {
        // Urutan preferensi: submissions -> abstrak
        if ($this->db->tableExists('submissions')) return 'submissions';
        if ($this->db->tableExists('abstrak'))     return 'abstrak';

        // Bila dua-duanya tidak ada, beri error yang jelas
        throw new \RuntimeException(
            'Tidak menemukan tabel target. Buat tabel "submissions" atau "abstrak" dulu, atau ubah migrasi ini ke nama tabel yang benar.'
        );
    }

    public function up()
    {
        $table = $this->resolveTargetTable();

        // Tambahkan kolom hanya jika belum ada (idempotent)
        if (!$this->db->fieldExists('full_paper_path', $table)) {
            $this->forge->addColumn($table, [
                'full_paper_path' => ['type' => 'TEXT', 'null' => true],
            ]);
        }

        if (!$this->db->fieldExists('full_paper_uploaded_at', $table)) {
            $this->forge->addColumn($table, [
                'full_paper_uploaded_at' => ['type' => 'TIMESTAMP', 'null' => true],
            ]);
        }

        if (!$this->db->fieldExists('full_paper_status', $table)) {
            $this->forge->addColumn($table, [
                'full_paper_status' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 20, // NONE|UPLOADED|REVISION|ACCEPTED|REJECTED
                    'default'    => 'NONE',
                ],
            ]);
        }

        // (Opsional) flag untuk unlock pembayaran saat ACC full paper
        if (!$this->db->fieldExists('eligible_to_pay', $table)) {
            $this->forge->addColumn($table, [
                'eligible_to_pay' => ['type' => 'BOOLEAN', 'default' => false],
            ]);
        }
    }

    public function down()
    {
        // Hapus dari tabel manapun yang ada (biar aman)
        foreach (['submissions', 'abstrak'] as $table) {
            if ($this->db->tableExists($table)) {
                foreach (['full_paper_path','full_paper_uploaded_at','full_paper_status','eligible_to_pay'] as $col) {
                    if ($this->db->fieldExists($col, $table)) {
                        $this->forge->dropColumn($table, $col);
                    }
                }
            }
        }
    }
}