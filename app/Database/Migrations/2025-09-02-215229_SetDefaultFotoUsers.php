<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class SetDefaultFotoUsers extends Migration
{
    public function up()
    {
        // Backfill: isi 'default.png' untuk foto yang NULL atau kosong
        $this->db->query("
            UPDATE users
               SET foto = 'default.png'
             WHERE TRIM(COALESCE(foto, '')) = ''
        ");

        // Set default untuk insert baru
        $this->db->query("
            ALTER TABLE users
            ALTER COLUMN foto SET DEFAULT 'default.png'
        ");

        // (Opsional) enforce NOT NULL jika mau strict – aktifkan jika sudah yakin tidak ada NULL
        // $this->db->query("ALTER TABLE users ALTER COLUMN foto SET NOT NULL");
    }

    public function down()
    {
        // Hanya cabut default; tidak mengubah data yang sudah terlanjur diisi
        $this->db->query("
            ALTER TABLE users
            ALTER COLUMN foto DROP DEFAULT
        ");

        // (Opsional) kalau sebelumnya sempat SET NOT NULL
        // $this->db->query("ALTER TABLE users ALTER COLUMN foto DROP NOT NULL");
    }
}
