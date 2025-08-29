<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;
use CodeIgniter\Database\RawSql;

class CreateUsersTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_user'            => ['type' => 'SERIAL'],
            'nama_lengkap'       => ['type' => 'VARCHAR', 'constraint' => 100],
            'email'              => ['type' => 'VARCHAR', 'constraint' => 100, 'unique' => true],
            'password'           => ['type' => 'VARCHAR', 'constraint' => 255],
            'role'               => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'audience'],
            'status'             => ['type' => 'VARCHAR', 'constraint' => 20, 'default' => 'nonaktif'],
            'verification_token' => ['type' => 'VARCHAR', 'constraint' => 255, 'null' => true],
            'email_verified_at'  => ['type' => 'TIMESTAMP', 'null' => true],
            'created_at'         => ['type' => 'TIMESTAMP', 'default' => new RawSql('CURRENT_TIMESTAMP')],
            'updated_at'         => ['type' => 'TIMESTAMP', 'default' => new RawSql('CURRENT_TIMESTAMP')],
        ]);
        $this->forge->addKey('id_user', true);
        $this->forge->createTable('users');
    }

    public function down()
    {
        $this->forge->dropTable('users');
    }
}
