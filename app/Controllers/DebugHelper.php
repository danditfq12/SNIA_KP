<?php

// File: app/Controllers/DebugHelper.php
namespace App\Controllers;

use App\Controllers\BaseController;

class DebugHelper extends BaseController
{
    /**
     * Debug QR format issues
     */
    public function qrDebug($qrToken = null)
    {
        // Hanya allow di development
        if (ENVIRONMENT !== 'development') {
            return $this->response->setStatusCode(404);
        }

        if (!$qrToken) {
            return view('debug/qr_tester');
        }

        $qrController = new \App\Controllers\QRAttendance();
        
        // Test QR validation
        $reflection = new \ReflectionClass($qrController);
        $method = $reflection->getMethod('validateAndDecodeQR');
        $method->setAccessible(true);
        
        $result = $method->invokeArgs($qrController, [$qrToken]);

        $debugInfo = [
            'input_token' => $qrToken,
            'token_length' => strlen($qrToken),
            'contains_http' => strpos($qrToken, 'http') !== false,
            'contains_event' => strpos($qrToken, 'EVENT_') !== false,
            'is_numeric' => is_numeric($qrToken),
            'validation_result' => $result,
            'patterns_tested' => [
                'standard' => '/^EVENT_(\d+)_([a-z]+)_([a-z]+)_(\d{8})_([a-f0-9]+)$/i',
                'simple' => '/^EVENT_(\d+)_(\d{8})$/i',
                'admin' => '/^(ADMIN|MANUAL|BULK)_(\d+)_(\d{8})/i',
                'numeric' => 'is_numeric()',
                'event_extract' => '/EVENT_(\d+)/i'
            ],
            'sample_valid_tokens' => [
                'EVENT_1_all_all_' . date('Ymd') . '_abcd1234',
                'EVENT_1_presenter_offline_' . date('Ymd') . '_efgh5678',
                'EVENT_1_' . date('Ymd'),
                '1',
                'ADMIN_1_' . date('Ymd') . '_MANUAL'
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ];

        return $this->response->setJSON($debugInfo, JSON_PRETTY_PRINT);
    }

    /**
     * Generate test QR codes
     */
    public function generateTestQR($eventId = 1)
    {
        if (ENVIRONMENT !== 'development') {
            return $this->response->setStatusCode(404);
        }

        $qrController = new \App\Controllers\QRAttendance();
        $today = date('Ymd');
        
        $testCodes = [
            'universal' => $qrController->generateQRToken($eventId, 'all', 'all'),
            'presenter' => $qrController->generateQRToken($eventId, 'presenter', 'offline'),
            'audience_online' => $qrController->generateQRToken($eventId, 'audience', 'online'),
            'audience_offline' => $qrController->generateQRToken($eventId, 'audience', 'offline'),
            'simple' => "EVENT_{$eventId}_{$today}",
            'numeric' => (string)$eventId,
            'admin' => "ADMIN_{$eventId}_{$today}_MANUAL"
        ];

        $testUrls = [];
        foreach ($testCodes as $type => $code) {
            $testUrls[$type] = [
                'token' => $code,
                'url' => site_url("qr/{$code}"),
                'debug_url' => site_url("debug/qr/{$code}")
            ];
        }

        return $this->response->setJSON([
            'event_id' => $eventId,
            'generated_at' => date('Y-m-d H:i:s'),
            'test_codes' => $testUrls,
            'instructions' => [
                'Test each URL to see if QR validation works',
                'Check debug URLs for detailed validation info',
                'Use these tokens in your QR scanner testing'
            ]
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Check system requirements
     */
    public function systemCheck()
    {
        if (ENVIRONMENT !== 'development') {
            return $this->response->setStatusCode(404);
        }

        $checks = [
            'php_version' => PHP_VERSION,
            'ci_version' => \CodeIgniter\CodeIgniter::CI_VERSION,
            'environment' => ENVIRONMENT,
            'writable_folder' => [
                'exists' => is_dir(WRITEPATH),
                'writable' => is_writable(WRITEPATH),
                'path' => WRITEPATH
            ],
            'logs_folder' => [
                'exists' => is_dir(WRITEPATH . 'logs'),
                'writable' => is_writable(WRITEPATH . 'logs'),
                'path' => WRITEPATH . 'logs'
            ],
            'database' => [
                'connected' => false,
                'error' => null
            ],
            'session' => [
                'started' => session_status() === PHP_SESSION_ACTIVE,
                'id' => session_id()
            ],
            'models_available' => [
                'AbsensiModel' => class_exists('\App\Models\AbsensiModel'),
                'EventModel' => class_exists('\App\Models\EventModel'),
                'PembayaranModel' => class_exists('\App\Models\PembayaranModel'),
                'UserModel' => class_exists('\App\Models\UserModel')
            ]
        ];

        // Test database connection
        try {
            $db = \Config\Database::connect();
            $db->query('SELECT 1');
            $checks['database']['connected'] = true;
        } catch (\Exception $e) {
            $checks['database']['error'] = $e->getMessage();
        }

        // Create logs directory if doesn't exist
        if (!$checks['logs_folder']['exists']) {
            try {
                mkdir(WRITEPATH . 'logs', 0755, true);
                $checks['logs_folder']['created'] = true;
            } catch (\Exception $e) {
                $checks['logs_folder']['creation_error'] = $e->getMessage();
            }
        }

        return $this->response->setJSON($checks, JSON_PRETTY_PRINT);
    }

    /**
     * Live error logger
     */
    public function errorLog()
    {
        if (ENVIRONMENT !== 'development') {
            return $this->response->setStatusCode(404);
        }

        $logDir = WRITEPATH . 'logs';
        $errors = [];

        if (is_dir($logDir)) {
            $files = glob($logDir . '/log-*.php');
            
            foreach ($files as $file) {
                $content = file_get_contents($file);
                $lines = explode("\n", $content);
                
                foreach ($lines as $line) {
                    if (strpos($line, 'ERROR') !== false || strpos($line, 'CRITICAL') !== false) {
                        $errors[] = [
                            'file' => basename($file),
                            'line' => trim($line),
                            'time' => date('Y-m-d H:i:s', filemtime($file))
                        ];
                    }
                }
            }
        }

        return $this->response->setJSON([
            'log_directory' => $logDir,
            'log_files_found' => is_dir($logDir) ? count(glob($logDir . '/log-*.php')) : 0,
            'recent_errors' => array_slice(array_reverse($errors), 0, 20),
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Test database tables
     */
    public function dbTest()
    {
        if (ENVIRONMENT !== 'development') {
            return $this->response->setStatusCode(404);
        }

        try {
            $db = \Config\Database::connect();
            
            $tables = [
                'users',
                'events', 
                'absensi',
                'pembayaran'
            ];

            $results = [];
            
            foreach ($tables as $table) {
                try {
                    $query = $db->query("SELECT COUNT(*) as count FROM {$table}");
                    $result = $query->getRow();
                    
                    $results[$table] = [
                        'exists' => true,
                        'count' => $result->count ?? 0,
                        'structure' => $db->getFieldNames($table)
                    ];
                } catch (\Exception $e) {
                    $results[$table] = [
                        'exists' => false,
                        'error' => $e->getMessage()
                    ];
                }
            }

            return $this->response->setJSON([
                'database_status' => 'connected',
                'tables' => $results,
                'timestamp' => date('Y-m-d H:i:s')
            ], JSON_PRETTY_PRINT);

        } catch (\Exception $e) {
            return $this->response->setJSON([
                'database_status' => 'error',
                'error' => $e->getMessage(),
                'timestamp' => date('Y-m-d H:i:s')
            ], JSON_PRETTY_PRINT);
        }
    }
}