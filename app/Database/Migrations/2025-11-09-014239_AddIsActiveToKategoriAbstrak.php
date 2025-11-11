<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddIsActiveToKategoriAbstrak extends Migration
{
    public function up()
    {
        // Tambah kolom is_active (default aktif)
        $this->forge->addColumn('kategori_abstrak', [
            'is_active' => [
                'type'    => 'BOOLEAN',   // MySQL: tinyint(1), Postgres: boolean
                'null'    => false,
                'default' => true,
            ],
        ]);

        // Backfill jika ada row lama tanpa nilai (jaga-jaga)
        try { $this->db->query('UPDATE kategori_abstrak SET is_active = TRUE WHERE is_active IS NULL'); } catch (\Throwable $e) {}

        // Index opsional agar filter aktif/nonaktif cepat
        try { $this->db->query('CREATE INDEX IF NOT EXISTS idx_kategori_is_active ON kategori_abstrak (is_active)'); } catch (\Throwable $e) {}
    }

    public function down()
    {
        // Hapus index (opsional)
        try { $this->db->query('DROP INDEX IF EXISTS idx_kategori_is_active'); } catch (\Throwable $e) {}

        // Hapus kolom
        $this->forge->dropColumn('kategori_abstrak', 'is_active');
    }
}
