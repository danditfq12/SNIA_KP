<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateEmailQueue extends Migration
{
    public function up()
    {
        $this->forge->addField([
            'id' => [
                'type'           => 'BIGINT',
                'constraint'     => 20,
                'unsigned'       => true,
                'auto_increment' => true,
            ],
            'to_email' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => false,
            ],
            'subject' => [
                'type'       => 'VARCHAR',
                'constraint' => 200,
                'null'       => false,
            ],
            // MEDIUMTEXT (MySQL) -> pakai TEXT untuk PostgreSQL
            'body_html' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'body_text' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'headers' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30, // pending|sent|failed
                'default'    => 'pending',
                'null'       => false,
            ],
            'attempts' => [
                'type'       => 'INT',
                'constraint' => 11,
                'default'    => 0,
                'null'       => false,
            ],
            'last_error' => [
                'type' => 'TEXT',
                'null' => true,
            ],
            'scheduled_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'sent_at' => [
                'type' => 'DATETIME',
                'null' => true,
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

        $this->forge->addKey('id', true);
        // Index untuk worker ambil antrian
        $this->forge->addKey(['status']);
        $this->forge->addKey(['scheduled_at']);

        $this->forge->createTable('email_queue', true);
    }

    public function down()
    {
        $this->forge->dropTable('email_queue', true);
    }
}
