<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddForeignKeysAndEventRelations extends Migration
{
    public function up()
    {
        // Add event_id to abstrak table
        $this->forge->addColumn('abstrak', [
            'event_id' => [
                'type'       => 'INT',
                'null'       => true,
                'after'      => 'id_kategori'
            ]
        ]);

        // Add event_id to pembayaran table
        $this->forge->addColumn('pembayaran', [
            'event_id' => [
                'type'       => 'INT',
                'null'       => true,
                'after'      => 'id_user'
            ]
        ]);

        // Add verified_by and verified_at to pembayaran table
        $this->forge->addColumn('pembayaran', [
            'verified_by' => [
                'type'       => 'INT',
                'null'       => true,
                'after'      => 'id_voucher'
            ],
            'verified_at' => [
                'type'       => 'TIMESTAMP',
                'null'       => true,
                'after'      => 'verified_by'
            ],
            'keterangan' => [
                'type'       => 'TEXT',
                'null'       => true,
                'after'      => 'verified_at'
            ]
        ]);

        // Add foreign key constraints
        $this->db->query('ALTER TABLE abstrak ADD CONSTRAINT fk_abstrak_user FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE');
        $this->db->query('ALTER TABLE abstrak ADD CONSTRAINT fk_abstrak_kategori FOREIGN KEY (id_kategori) REFERENCES kategori_abstrak(id_kategori) ON DELETE RESTRICT');
        $this->db->query('ALTER TABLE abstrak ADD CONSTRAINT fk_abstrak_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL');

        $this->db->query('ALTER TABLE reviewer_kategori ADD CONSTRAINT fk_reviewer_kategori_user FOREIGN KEY (id_reviewer) REFERENCES users(id_user) ON DELETE CASCADE');
        $this->db->query('ALTER TABLE reviewer_kategori ADD CONSTRAINT fk_reviewer_kategori_kategori FOREIGN KEY (id_kategori) REFERENCES kategori_abstrak(id_kategori) ON DELETE CASCADE');

        $this->db->query('ALTER TABLE review ADD CONSTRAINT fk_review_abstrak FOREIGN KEY (id_abstrak) REFERENCES abstrak(id_abstrak) ON DELETE CASCADE');
        $this->db->query('ALTER TABLE review ADD CONSTRAINT fk_review_reviewer FOREIGN KEY (id_reviewer) REFERENCES users(id_user) ON DELETE CASCADE');

        $this->db->query('ALTER TABLE pembayaran ADD CONSTRAINT fk_pembayaran_user FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE');
        $this->db->query('ALTER TABLE pembayaran ADD CONSTRAINT fk_pembayaran_event FOREIGN KEY (event_id) REFERENCES events(id) ON DELETE SET NULL');
        $this->db->query('ALTER TABLE pembayaran ADD CONSTRAINT fk_pembayaran_voucher FOREIGN KEY (id_voucher) REFERENCES voucher(id_voucher) ON DELETE SET NULL');
        $this->db->query('ALTER TABLE pembayaran ADD CONSTRAINT fk_pembayaran_verified_by FOREIGN KEY (verified_by) REFERENCES users(id_user) ON DELETE SET NULL');

        $this->db->query('ALTER TABLE absensi ADD CONSTRAINT fk_absensi_user FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE');

        $this->db->query('ALTER TABLE dokumen ADD CONSTRAINT fk_dokumen_user FOREIGN KEY (id_user) REFERENCES users(id_user) ON DELETE CASCADE');

        // Create indexes for better performance
        $this->db->query('CREATE INDEX idx_abstrak_status ON abstrak(status)');
        $this->db->query('CREATE INDEX idx_abstrak_event ON abstrak(event_id)');
        $this->db->query('CREATE INDEX idx_pembayaran_status ON pembayaran(status)');
        $this->db->query('CREATE INDEX idx_pembayaran_event ON pembayaran(event_id)');
        $this->db->query('CREATE INDEX idx_review_keputusan ON review(keputusan)');
        $this->db->query('CREATE INDEX idx_voucher_kode ON voucher(kode_voucher)');
        $this->db->query('CREATE INDEX idx_voucher_status ON voucher(status)');
        $this->db->query('CREATE INDEX idx_users_role ON users(role)');
        $this->db->query('CREATE INDEX idx_users_status ON users(status)');
        $this->db->query('CREATE INDEX idx_users_email_verified ON users(email_verified_at)');
    }

    public function down()
    {
        // Drop indexes
        $this->db->query('DROP INDEX IF EXISTS idx_abstrak_status');
        $this->db->query('DROP INDEX IF EXISTS idx_abstrak_event');
        $this->db->query('DROP INDEX IF EXISTS idx_pembayaran_status');
        $this->db->query('DROP INDEX IF EXISTS idx_pembayaran_event');
        $this->db->query('DROP INDEX IF EXISTS idx_review_keputusan');
        $this->db->query('DROP INDEX IF EXISTS idx_voucher_kode');
        $this->db->query('DROP INDEX IF EXISTS idx_voucher_status');
        $this->db->query('DROP INDEX IF EXISTS idx_users_role');
        $this->db->query('DROP INDEX IF EXISTS idx_users_status');
        $this->db->query('DROP INDEX IF EXISTS idx_users_email_verified');

        // Drop foreign key constraints
        $this->db->query('ALTER TABLE abstrak DROP CONSTRAINT IF EXISTS fk_abstrak_user');
        $this->db->query('ALTER TABLE abstrak DROP CONSTRAINT IF EXISTS fk_abstrak_kategori');
        $this->db->query('ALTER TABLE abstrak DROP CONSTRAINT IF EXISTS fk_abstrak_event');
        
        $this->db->query('ALTER TABLE reviewer_kategori DROP CONSTRAINT IF EXISTS fk_reviewer_kategori_user');
        $this->db->query('ALTER TABLE reviewer_kategori DROP CONSTRAINT IF EXISTS fk_reviewer_kategori_kategori');
        
        $this->db->query('ALTER TABLE review DROP CONSTRAINT IF EXISTS fk_review_abstrak');
        $this->db->query('ALTER TABLE review DROP CONSTRAINT IF EXISTS fk_review_reviewer');
        
        $this->db->query('ALTER TABLE pembayaran DROP CONSTRAINT IF EXISTS fk_pembayaran_user');
        $this->db->query('ALTER TABLE pembayaran DROP CONSTRAINT IF EXISTS fk_pembayaran_event');
        $this->db->query('ALTER TABLE pembayaran DROP CONSTRAINT IF EXISTS fk_pembayaran_voucher');
        $this->db->query('ALTER TABLE pembayaran DROP CONSTRAINT IF EXISTS fk_pembayaran_verified_by');
        
        $this->db->query('ALTER TABLE absensi DROP CONSTRAINT IF EXISTS fk_absensi_user');
        $this->db->query('ALTER TABLE dokumen DROP CONSTRAINT IF EXISTS fk_dokumen_user');

        // Drop columns
        $this->forge->dropColumn('abstrak', 'event_id');
        $this->forge->dropColumn('pembayaran', ['event_id', 'verified_by', 'verified_at', 'keterangan']);
    }
}