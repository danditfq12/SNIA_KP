<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreatePendingRegistrations extends Migration
{
    public function up()
    {
        // Tabel pending_registrations
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'auto_increment' => true,   // di PostgreSQL akan menjadi SERIAL
                'unsigned'       => false,
            ],
            'nama_lengkap' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => false,
            ],
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => 150,
                'null'       => false,
            ],
            'password_hash' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'role' => [
                'type'       => 'VARCHAR',
                'constraint' => 20,
                'default'    => 'audience',
                'null'       => false,
            ],
            'otp_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 6,
                'null'       => false,
            ],
            'otp_expired' => [
                'type'       => 'TIMESTAMP',
                'null'       => false,
            ],
            'created_at' => [
                'type'    => 'TIMESTAMP',
                'null'    => false,
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
            'updated_at' => [
                'type'    => 'TIMESTAMP',
                'null'    => false,
                'default' => new RawSql('CURRENT_TIMESTAMP'),
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->createTable('pending_registrations', true);

        // Unique index email (satu email hanya boleh satu pending)
        $this->db->query('CREATE UNIQUE INDEX IF NOT EXISTS ux_pending_registrations_email ON pending_registrations (email);');

        // --- Trigger untuk auto-update updated_at di PostgreSQL ---
        // Function
        $this->db->query(<<<'SQL'
CREATE OR REPLACE FUNCTION set_timestamp()
RETURNS trigger AS $$
BEGIN
  NEW.updated_at = NOW();
  RETURN NEW;
END;
$$ LANGUAGE plpgsql;
SQL);

        // Trigger
        $this->db->query(<<<'SQL'
DROP TRIGGER IF EXISTS set_timestamp_pending_registrations ON pending_registrations;
CREATE TRIGGER set_timestamp_pending_registrations
BEFORE UPDATE ON pending_registrations
FOR EACH ROW
EXECUTE PROCEDURE set_timestamp();
SQL);
    }

    public function down()
    {
        // Hapus trigger & function
        $this->db->query('DROP TRIGGER IF EXISTS set_timestamp_pending_registrations ON pending_registrations;');
        $this->db->query('DROP FUNCTION IF EXISTS set_timestamp();');

        // Hapus index & tabel
        $this->db->query('DROP INDEX IF EXISTS ux_pending_registrations_email;');
        $this->forge->dropTable('pending_registrations', true);
    }
}
