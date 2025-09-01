<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class FixAbstrakTableForFileUpload extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();
        
        // Check if abstrak table exists
        if (!$db->tableExists('abstrak')) {
            log_message('error', 'Abstrak table does not exist, please run initial migrations first');
            return;
        }

        // Ensure file_abstrak field can handle longer filenames
        $this->forge->modifyColumn('abstrak', [
            'file_abstrak' => [
                'type' => 'VARCHAR',
                'constraint' => 500, // Increased from 255 to handle longer filenames
                'null' => false
            ]
        ]);

        // Add indexes for better performance if they don't exist
        $db->query('CREATE INDEX IF NOT EXISTS idx_abstrak_user_event ON abstrak(id_user, event_id)');
        $db->query('CREATE INDEX IF NOT EXISTS idx_abstrak_upload_date ON abstrak(tanggal_upload)');

        // Ensure upload directories exist
        $uploadDirs = [
            WRITEPATH . 'uploads/',
            WRITEPATH . 'uploads/abstraks/',
            WRITEPATH . 'uploads/pembayaran/',
            WRITEPATH . 'uploads/dokumen/',
            WRITEPATH . 'uploads/loa/',
            WRITEPATH . 'uploads/sertifikat/'
        ];

        foreach ($uploadDirs as $dir) {
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
                
                // Create .htaccess for security
                $htaccessContent = "Options -Indexes\n";
                $htaccessContent .= "Order deny,allow\n";
                $htaccessContent .= "Deny from all\n";
                $htaccessContent .= "<Files ~ \"\\.(pdf|doc|docx|jpg|jpeg|png)$\">\n";
                $htaccessContent .= "    Order allow,deny\n";
                $htaccessContent .= "    Allow from all\n";
                $htaccessContent .= "</Files>\n";
                
                file_put_contents($dir . '.htaccess', $htaccessContent);
                
                log_message('info', 'Created upload directory: ' . $dir);
            }
        }

        // Create index.html files to prevent directory browsing
        foreach ($uploadDirs as $dir) {
            $indexFile = $dir . 'index.html';
            if (!file_exists($indexFile)) {
                file_put_contents($indexFile, '<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body><h1>Directory access is forbidden.</h1></body></html>');
            }
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();
        
        // Remove added indexes
        $db->query('DROP INDEX IF EXISTS idx_abstrak_user_event');
        $db->query('DROP INDEX IF EXISTS idx_abstrak_upload_date');
        
        // Revert file_abstrak field length
        $this->forge->modifyColumn('abstrak', [
            'file_abstrak' => [
                'type' => 'VARCHAR',
                'constraint' => 255,
                'null' => false
            ]
        ]);
    }
}