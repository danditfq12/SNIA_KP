<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterNotifikasiToV2 extends Migration
{
    public function up()
    {
        // Cek field yang sudah ada
        $fields = array_map('strtolower', $this->db->getFieldNames('notifikasi'));

        // Tambah kolom 'type'
        if (!in_array('type', $fields, true)) {
            $this->forge->addColumn('notifikasi', [
                'type' => [
                    'type'       => 'VARCHAR',
                    'constraint' => 50,
                    'null'       => true,
                    'after'      => 'role',
                ],
            ]);
        }

        // Tambah kolom 'meta_json'
        if (!in_array('meta_json', $fields, true)) {
            $this->forge->addColumn('notifikasi', [
                'meta_json' => [
                    'type' => 'TEXT',
                    'null' => true,
                    'after'=> 'link',
                ],
            ]);
        }

        // Tambah kolom 'read_at'
        if (!in_array('read_at', $fields, true)) {
            $this->forge->addColumn('notifikasi', [
                'read_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after'=> 'read',
                ],
            ]);
        }

        // Tambah kolom 'updated_at'
        if (!in_array('updated_at', $fields, true)) {
            $this->forge->addColumn('notifikasi', [
                'updated_at' => [
                    'type' => 'DATETIME',
                    'null' => true,
                    'after'=> 'created_at',
                ],
            ]);
        }

        // Backfill: kalau ada kolom lama 'meta' tapi 'meta_json' masih null → salin isinya
        if (in_array('meta', $fields, true) && in_array('meta_json', $fields, true)) {
            $this->db->query("UPDATE notifikasi SET meta_json = meta WHERE meta_json IS NULL AND meta IS NOT NULL");
        }

        // Pastikan panjang link memadai (opsional – MySQL only)
        // $this->db->query("ALTER TABLE notifikasi MODIFY link VARCHAR(255) NULL");

        // Index ringan (opsional)
        $this->db->query("CREATE INDEX IF NOT EXISTS idx_notifikasi_user ON notifikasi (id_user)");
        // MySQL tidak punya IF NOT EXISTS di CREATE INDEX – aman diabaikan jika error.
    }

    public function down()
    {
        // Revert pelan-pelan, tidak drop data meta_json
        $fields = array_map('strtolower', $this->db->getFieldNames('notifikasi'));

        if (in_array('updated_at', $fields, true)) {
            $this->forge->dropColumn('notifikasi', 'updated_at');
        }
        if (in_array('read_at', $fields, true)) {
            $this->forge->dropColumn('notifikasi', 'read_at');
        }
        if (in_array('meta_json', $fields, true)) {
            // kalau mau aman, JANGAN drop meta_json pada down (bisa kamu ganti sesuai kebutuhan)
            // $this->forge->dropColumn('notifikasi', 'meta_json');
        }
        if (in_array('type', $fields, true)) {
            $this->forge->dropColumn('notifikasi', 'type');
        }
    }
}
