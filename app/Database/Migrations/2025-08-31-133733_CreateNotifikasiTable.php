<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateNotifikasiTable extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id_notif' => [
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'id_user' => [
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => true, // bisa null kalau notifikasi untuk semua role
            ],
            'role' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true, // opsional (notifikasi broadcast role)
            ],
            'title' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
            ],
            'message' => [
                'type'       => 'TEXT',
                'null'       => true,
            ],
            'link' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true,
            ],
            'read' => [
                'type'       => 'BOOLEAN',
                'default'    => false,
            ],
            'created_at' => [
                'type'       => 'DATETIME',
                'null'       => true,
            ],
        ]);

        $this->forge->addKey('id_notif', true);
        $this->forge->addForeignKey('id_user', 'users', 'id_user', 'CASCADE', 'CASCADE');
        $this->forge->createTable('notifikasi');
    }

    public function down()
    {
        $this->forge->dropTable('notifikasi');
    }
}
