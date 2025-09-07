<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AddNimToUsers extends Migration
{
    public function up()
    {
        $this->forge->addColumn('users', [
            'nim' => [
                'type'       => 'VARCHAR',
                'constraint' => 50,
                'null'       => true,
                'after'      => 'email', // opsional
            ],
        ]);
    }

    public function down()
    {
        $this->forge->dropColumn('users', 'nim');
    }
}
