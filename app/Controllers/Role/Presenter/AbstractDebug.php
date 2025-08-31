<?php

namespace App\Controllers\Role\Presenter;

use App\Controllers\BaseController;
use App\Models\AbstrakModel;
use App\Models\KategoriAbstrakModel;
use App\Models\EventModel;

class AbstractDebug extends BaseController
{
    public function testUpload()
    {
        // Test basic functionality step by step
        echo "<h3>Debug Upload Process</h3>";
        
        echo "<h4>1. Session Check:</h4>";
        $userId = session('id_user');
        echo "User ID: " . ($userId ? $userId : 'NULL') . "<br>";
        echo "Session data: " . json_encode(session()->get()) . "<br><br>";
        
        echo "<h4>2. Request Method:</h4>";
        echo "Method: " . $this->request->getMethod() . "<br>";
        echo "Is POST: " . ($this->request->getMethod() === 'post' ? 'YES' : 'NO') . "<br><br>";
        
        echo "<h4>3. POST Data:</h4>";
        $postData = $this->request->getPost();
        echo "POST data: " . json_encode($postData) . "<br><br>";
        
        echo "<h4>4. File Upload:</h4>";
        $file = $this->request->getFile('file_abstrak');
        if ($file) {
            echo "File exists: YES<br>";
            echo "File name: " . $file->getName() . "<br>";
            echo "File size: " . $file->getSize() . " bytes<br>";
            echo "File type: " . $file->getMimeType() . "<br>";
            echo "Is valid: " . ($file->isValid() ? 'YES' : 'NO') . "<br>";
            echo "Has moved: " . ($file->hasMoved() ? 'YES' : 'NO') . "<br>";
            echo "Error: " . $file->getErrorString() . "<br>";
        } else {
            echo "File: NULL<br>";
        }
        echo "<br>";
        
        echo "<h4>5. Database Test:</h4>";
        try {
            $abstrakModel = new AbstrakModel();
            $eventModel = new EventModel();
            $kategoriModel = new KategoriAbstrakModel();
            
            echo "Models loaded: OK<br>";
            
            // Test database connection
            $db = \Config\Database::connect();
            echo "DB connection: OK<br>";
            
            // Test tables exist
            $tables = $db->listTables();
            echo "Tables found: " . implode(', ', $tables) . "<br>";
            
            // Test if required tables exist
            $requiredTables = ['abstrak', 'events', 'kategori_abstrak', 'users'];
            foreach ($requiredTables as $table) {
                echo "Table '$table': " . (in_array($table, $tables) ? 'EXISTS' : 'MISSING') . "<br>";
            }
            
        } catch (\Exception $e) {
            echo "Database error: " . $e->getMessage() . "<br>";
        }
        echo "<br>";
        
        echo "<h4>6. Directory Check:</h4>";
        $uploadPath = WRITEPATH . 'uploads/abstraks/';
        echo "Upload path: " . $uploadPath . "<br>";
        echo "Directory exists: " . (is_dir($uploadPath) ? 'YES' : 'NO') . "<br>";
        echo "Directory writable: " . (is_writable($uploadPath) ? 'YES' : 'NO') . "<br>";
        
        if (!is_dir($uploadPath)) {
            try {
                mkdir($uploadPath, 0755, true);
                echo "Directory created: OK<br>";
            } catch (\Exception $e) {
                echo "Failed to create directory: " . $e->getMessage() . "<br>";
            }
        }
        echo "<br>";
        
        echo "<h4>7. CSRF Token:</h4>";
        echo "CSRF enabled: " . (csrf_token() ? 'YES' : 'NO') . "<br>";
        echo "CSRF token: " . csrf_token() . "<br>";
        echo "CSRF hash: " . csrf_hash() . "<br>";
        
        die(); // Stop execution for debugging
    }
    
    public function simpleUpload()
    {
        // Simplified upload without complex validation
        $userId = session('id_user');
        
        if (!$userId) {
            die('User not logged in');
        }
        
        if ($this->request->getMethod() !== 'post') {
            die('Not POST request');
        }
        
        $abstrakModel = new AbstrakModel();
        
        // Simple data without file upload first
        $data = [
            'id_user' => $userId,
            'event_id' => 1, // Hardcode for testing
            'id_kategori' => 1, // Hardcode for testing  
            'judul' => 'Test Abstrak',
            'file_abstrak' => 'test.pdf',
            'status' => 'menunggu',
            'tanggal_upload' => date('Y-m-d H:i:s'),
            'revisi_ke' => 0
        ];
        
        echo "Testing simple insert...<br>";
        echo "Data: " . json_encode($data) . "<br>";
        
        try {
            $result = $abstrakModel->insert($data);
            echo "Insert result: " . ($result ? 'SUCCESS - ID: ' . $result : 'FAILED') . "<br>";
            
            if (!$result) {
                echo "Errors: " . json_encode($abstrakModel->errors()) . "<br>";
            }
        } catch (\Exception $e) {
            echo "Exception: " . $e->getMessage() . "<br>";
        }
        
        die();
    }
}