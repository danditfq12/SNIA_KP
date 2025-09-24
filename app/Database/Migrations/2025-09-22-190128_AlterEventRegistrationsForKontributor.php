<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class AlterEventRegistrationsForKontributor extends Migration
{
    public function up()
    {
        // Tambah kolom bila belum ada
        if (!$this->db->fieldExists('afiliasi', 'event_registrations')) {
            $this->forge->addColumn('event_registrations', [
                'afiliasi' => ['type'=>'VARCHAR','constraint'=>255,'null'=>true,'after'=>'status'],
            ]);
        }
        if (!$this->db->fieldExists('phone', 'event_registrations')) {
            $this->forge->addColumn('event_registrations', [
                'phone' => ['type'=>'VARCHAR','constraint'=>32,'null'=>true,'after'=>'afiliasi'],
            ]);
        }
        if (!$this->db->fieldExists('coauthors_json', 'event_registrations')) {
            // Postgres: JSONB; MySQL: JSON
            $type = (strpos(strtolower($this->db->DBDriver), 'postgre') !== false) ? 'JSONB' : 'JSON';
            $this->forge->addColumn('event_registrations', [
                'coauthors_json' => ['type'=>$type, 'null'=>true, 'after'=>'phone'],
            ]);
        }
        // flag selesai (pakai satu saja yang baku)
        if (!$this->db->fieldExists('contributor_done', 'event_registrations')) {
            $this->forge->addColumn('event_registrations', [
                'contributor_done' => ['type'=>'BOOLEAN','null'=>false,'default'=>false,'after'=>'coauthors_json'],
            ]);
        }
    }

    public function down()
    {
        foreach (['contributor_done','coauthors_json','phone','afiliasi'] as $col) {
            if ($this->db->fieldExists($col, 'event_registrations')) {
                $this->forge->dropColumn('event_registrations', $col);
            }
        }
    }
}
