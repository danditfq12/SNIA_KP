<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreatePasswordResetsTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,     // aman; di PostgreSQL akan diabaikan
                'auto_increment' => true,
            ],
            'email' => [
                'type'       => 'VARCHAR',
                'constraint' => 191,
            ],
            'otp_code' => [
                'type'       => 'VARCHAR',
                'constraint' => 6,           // 6 digit
            ],
            'otp_expired' => [
                'type' => 'DATETIME',
                'null' => false,
            ],
            'attempts' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
            ],
            'created_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'updated_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
        ]);

        $this->forge->addKey('id', true);           // primary key
        $this->forge->addUniqueKey('email');        // Satu email = satu row (memudahkan upsert)
        $this->forge->addKey('otp_expired');        // index bantu query kadaluarsa (opsional)

        $this->forge->createTable('password_resets', true);
    }

    public function down()
    {
        $this->forge->dropTable('password_resets', true);
    }
}