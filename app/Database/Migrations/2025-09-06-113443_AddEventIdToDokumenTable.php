<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddEventIdToDokumenTable extends Migration
{
    public function up()
    {
        // Tambah kolom kalau belum ada (tanpa unsigned/after untuk Postgres)
        if (! $this->db->fieldExists('event_id', 'dokumen')) {
            $this->forge->addColumn('dokumen', [
                'event_id' => [
                    // Ganti ke 'BIGINT' jika events.id adalah bigserial/bigint
                    'type'       => 'INT',
                    'null'       => true,
                ],
            ]);
        }

        // Pastikan FK lama tidak ada, lalu tambah FK baru (Postgres)
        $this->db->query('ALTER TABLE dokumen DROP CONSTRAINT IF EXISTS fk_dokumen_event');

        if ($this->db->tableExists('events')) {
            $this->db->query(
                'ALTER TABLE dokumen
                 ADD CONSTRAINT fk_dokumen_event
                 FOREIGN KEY (event_id) REFERENCES events(id)
                 ON DELETE SET NULL ON UPDATE CASCADE'
            );
        }

        // Index (gunakan IF NOT EXISTS agar idempotent)
        try { $this->db->query('CREATE INDEX IF NOT EXISTS idx_dokumen_event ON dokumen(event_id)'); } catch (\Throwable $e) {}
        try { $this->db->query('CREATE INDEX IF NOT EXISTS idx_dokumen_tipe ON dokumen(tipe)'); } catch (\Throwable $e) {}
        try { $this->db->query('CREATE INDEX IF NOT EXISTS idx_dokumen_user_event ON dokumen(id_user, event_id)'); } catch (\Throwable $e) {}
        try { $this->db->query('CREATE INDEX IF NOT EXISTS idx_dokumen_uploaded_at ON dokumen(uploaded_at)'); } catch (\Throwable $e) {}
    }

    public function down()
    {
        // Drop index (Postgres)
        $this->db->query('DROP INDEX IF EXISTS idx_dokumen_event');
        $this->db->query('DROP INDEX IF EXISTS idx_dokumen_tipe');
        $this->db->query('DROP INDEX IF EXISTS idx_dokumen_user_event');
        $this->db->query('DROP INDEX IF EXISTS idx_dokumen_uploaded_at');

        // Drop FK (Postgres)
        $this->db->query('ALTER TABLE dokumen DROP CONSTRAINT IF EXISTS fk_dokumen_event');

        // Drop kolom kalau ada
        if ($this->db->fieldExists('event_id', 'dokumen')) {
            $this->forge->dropColumn('dokumen', 'event_id');
        }
    }
}
