<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class UsersFotoDefault extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();
        // Set default kolom
        $db->query("ALTER TABLE users ALTER COLUMN foto SET DEFAULT 'default.png'");
        // Isi untuk data lama
        $db->query("UPDATE users SET foto = 'default.png' WHERE foto IS NULL OR foto = ''");
    }

    public function down()
    {
        $db = \Config\Database::connect();
        // Revert default (opsional)
        $db->query("ALTER TABLE users ALTER COLUMN foto DROP DEFAULT");
    }
}
