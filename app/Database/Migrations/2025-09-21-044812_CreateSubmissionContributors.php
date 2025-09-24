<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateSubmissionContributors extends Migration
{
    private function resolveTargetTable(): string
    {
        if ($this->db->tableExists('submissions')) return 'submissions';
        if ($this->db->tableExists('abstrak'))     return 'abstrak';
        throw new \RuntimeException(
            'Tidak menemukan tabel target untuk FK (submissions/abstrak). Buat dulu tabel utama submission/abstrak.'
        );
    }

    private function getPrimaryKeyColumn(string $table): ?string
    {
        // Cari kolom PK via information_schema (PostgreSQL)
        $sql = "
            SELECT kcu.column_name
            FROM information_schema.table_constraints tc
            JOIN information_schema.key_column_usage kcu
              ON tc.constraint_name = kcu.constraint_name
             AND tc.table_schema = kcu.table_schema
            WHERE tc.constraint_type = 'PRIMARY KEY'
              AND tc.table_name = ?
              AND tc.table_schema = current_schema()
            LIMIT 1
        ";
        $row = $this->db->query($sql, [$table])->getFirstRow('array');
        return $row['column_name'] ?? null;
    }

    private function ensureFk(string $pkTable, string $pkColumn): void
    {
        // Pasang FK kalau belum ada dan pkColumn valid
        if (!$pkColumn) return;

        // Cek apakah constraint sudah ada
        $exists = $this->db->query("
            SELECT 1
            FROM information_schema.table_constraints
            WHERE constraint_type = 'FOREIGN KEY'
              AND table_name = 'submission_contributors'
              AND constraint_name = 'submission_contributors_submission_fk'
              AND table_schema = current_schema()
            LIMIT 1
        ")->getFirstRow();

        if ($exists) return;

        // Pasang FK aman-aman
        $this->db->query("
            ALTER TABLE submission_contributors
              ADD CONSTRAINT submission_contributors_submission_fk
              FOREIGN KEY (submission_id)
              REFERENCES {$pkTable}({$pkColumn})
              ON DELETE CASCADE
              ON UPDATE CASCADE
        ");
    }

    public function up()
    {
        $targetTable = $this->resolveTargetTable();
        $pkColumn    = $this->getPrimaryKeyColumn($targetTable); // <= bisa id, id_abstrak, dll.

        // 1) Buat/benahi tabel submission_contributors
        if (!$this->db->tableExists('submission_contributors')) {
            $this->forge->addField([
                'id' => ['type' => 'BIGSERIAL'],
                'submission_id' => ['type' => 'BIGINT', 'null' => false],
                'name' => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => false],
                'email' => ['type' => 'VARCHAR', 'constraint' => 200, 'null' => false],
                'affiliation' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
                'is_corresponding' => ['type' => 'BOOLEAN', 'default' => false],
                'author_order' => ['type' => 'INT', 'default' => 1],
                'created_at' => ['type' => 'TIMESTAMP', 'null' => false, 'default' => new RawSql('CURRENT_TIMESTAMP')],
                'updated_at' => ['type' => 'TIMESTAMP', 'null' => true],
            ]);

            $this->forge->addKey('id', true);
            $this->forge->addKey('submission_id');
            $this->forge->createTable('submission_contributors');
        } else {
            // Pastikan kolom minimal ada jika tabel sudah terlanjur dibuat saat migrasi gagal
            $cols = [
                'submission_id'    => "ALTER TABLE submission_contributors ADD COLUMN submission_id BIGINT NOT NULL",
                'name'             => "ALTER TABLE submission_contributors ADD COLUMN name VARCHAR(200) NOT NULL",
                'email'            => "ALTER TABLE submission_contributors ADD COLUMN email VARCHAR(200) NOT NULL",
                'affiliation'      => "ALTER TABLE submission_contributors ADD COLUMN affiliation VARCHAR(255)",
                'is_corresponding' => "ALTER TABLE submission_contributors ADD COLUMN is_corresponding BOOLEAN DEFAULT FALSE",
                'author_order'     => "ALTER TABLE submission_contributors ADD COLUMN author_order INT DEFAULT 1",
                'created_at'       => "ALTER TABLE submission_contributors ADD COLUMN created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP",
                'updated_at'       => "ALTER TABLE submission_contributors ADD COLUMN updated_at TIMESTAMP NULL",
            ];
            foreach ($cols as $col => $ddl) {
                if (!$this->db->fieldExists($col, 'submission_contributors')) {
                    $this->db->query($ddl);
                }
            }

            // Index untuk submission_id
            $this->db->query("
                DO $$
                BEGIN
                    IF NOT EXISTS (
                        SELECT 1 FROM pg_indexes WHERE schemaname = current_schema()
                          AND indexname = 'submission_contributors_submission_id_idx'
                    ) THEN
                        CREATE INDEX submission_contributors_submission_id_idx
                          ON submission_contributors (submission_id);
                    END IF;
                END$$;
            ");
        }

        // 2) Index unik (submission_id, author_order)
        $this->db->query("
            DO $$
            BEGIN
                IF NOT EXISTS (
                    SELECT 1 FROM pg_indexes WHERE schemaname = current_schema()
                      AND indexname = 'submission_contributors_unique'
                ) THEN
                    CREATE UNIQUE INDEX submission_contributors_unique
                      ON submission_contributors (submission_id, author_order);
                END IF;
            END$$;
        ");

        // 3) Coba pasang FK kalau PK diketahui; jika tidak, biarkan tanpa FK (tetap aman jalan)
        if ($pkColumn) {
            $this->ensureFk($targetTable, $pkColumn);
        }
    }

    public function down()
    {
        if ($this->db->tableExists('submission_contributors')) {
            // Hapus FK kalau ada
            $this->db->query("
                DO $$
                BEGIN
                    IF EXISTS (
                        SELECT 1
                        FROM information_schema.table_constraints
                        WHERE constraint_type = 'FOREIGN KEY'
                          AND table_name = 'submission_contributors'
                          AND constraint_name = 'submission_contributors_submission_fk'
                          AND table_schema = current_schema()
                    ) THEN
                        ALTER TABLE submission_contributors
                          DROP CONSTRAINT submission_contributors_submission_fk;
                    END IF;
                END$$;
            ");
            $this->forge->dropTable('submission_contributors', true);
        }
    }
}