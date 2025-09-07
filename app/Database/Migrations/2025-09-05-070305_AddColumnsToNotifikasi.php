<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddColumnsToNotifikasi extends Migration
{
    public function up()
    {
        $db    = \Config\Database::connect();
        $forge = \Config\Database::forge();

        // cek kolom yang sudah ada
        $existing = array_map('strtolower', $db->getFieldNames('notifikasi') ?? []);

        $toAdd = [];

        if (!in_array('type', $existing, true)) {
            $toAdd['type'] = [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,   // info|deadline|payment|review|system
                'after'      => 'role',
            ];
        }

        if (!in_array('meta', $existing, true)) {
            // Kalau DB kamu MySQL lama tanpa JSON, ganti ke TEXT
            $toAdd['meta'] = [
                'type' => (str_contains(strtolower($db->DBDriver), 'postgre') ? 'JSON' : 'JSON'),
                'null' => true,
                'after'=> 'link',
            ];
        }

        if (!in_array('read_at', $existing, true)) {
            $toAdd['read_at'] = [
                'type' => 'DATETIME',
                'null' => true,
                'after'=> 'read',
            ];
        }

        if (!in_array('updated_at', $existing, true)) {
            $toAdd['updated_at'] = [
                'type' => 'DATETIME',
                'null' => true,
                'after'=> 'created_at',
            ];
        }

        if (!empty($toAdd)) {
            $forge->addColumn('notifikasi', $toAdd);
        }

        // Indexes (PostgreSQL-safe). Kolom "read" perlu di-quote.
        $db->query('CREATE INDEX IF NOT EXISTS idx_notifikasi_user_read ON notifikasi (id_user, "read")');
        $db->query('CREATE INDEX IF NOT EXISTS idx_notifikasi_created_at ON notifikasi (created_at)');
        $db->query('CREATE INDEX IF NOT EXISTS idx_notifikasi_type ON notifikasi ("type")');
    }

    public function down()
    {
        $db    = \Config\Database::connect();
        $forge = \Config\Database::forge();

        // drop index jika ada
        $db->query('DROP INDEX IF EXISTS idx_notifikasi_user_read');
        $db->query('DROP INDEX IF EXISTS idx_notifikasi_created_at');
        $db->query('DROP INDEX IF EXISTS idx_notifikasi_type');

        // drop kolom HANYA jika memang ada (biar aman untuk rollback)
        $existing = array_map('strtolower', $db->getFieldNames('notifikasi') ?? []);
        $cols = [];
        foreach (['type','meta','read_at','updated_at'] as $c) {
            if (in_array($c, $existing, true)) $cols[] = $c;
        }
        if (!empty($cols)) {
            $forge->dropColumn('notifikasi', $cols);
        }
    }
}
