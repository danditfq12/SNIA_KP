<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateNotificationDeliveries extends Migration
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
            'id_notif' => [
                'type'       => 'INT',
                'constraint' => 11,
                'unsigned'   => true,
            ],
            'channel' => [
                'type'       => 'VARCHAR',
                'constraint' => 30, // in_app|email|push|sms
            ],
            'target' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
                'null'       => true, // email addr / device token
            ],
            'status' => [
                'type'       => 'VARCHAR',
                'constraint' => 30, // pending|sent|failed|skipped
                'default'    => 'pending',
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
        $this->forge->addKey(['id_notif', 'channel']);
        $this->forge->addKey(['status', 'scheduled_at']);
        $this->forge->addForeignKey('id_notif', 'notifikasi', 'id_notif', 'CASCADE', 'CASCADE');
        $this->forge->createTable('notification_deliveries', true);
    }

    public function down()
    {
        $this->forge->dropTable('notification_deliveries', true);
    }
}
