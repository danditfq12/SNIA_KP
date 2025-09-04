<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class QRPerformanceIndexes extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        // Create indexes for better QR performance and validation
        try {
            // Index for QR code validation - covers most common QR lookup patterns
            $db->query('CREATE INDEX IF NOT EXISTS idx_qr_validation ON absensi(qr_code, event_id, id_user)');
            log_message('info', 'Created index: idx_qr_validation');

            // Index for payment participation type filtering
            $db->query('CREATE INDEX IF NOT EXISTS idx_payment_participation ON pembayaran(participation_type, status)');
            log_message('info', 'Created index: idx_payment_participation');

            // Additional indexes for QR system performance
            
            // Index for event-based attendance queries
            $db->query('CREATE INDEX IF NOT EXISTS idx_absensi_event_status ON absensi(event_id, status, waktu_scan)');
            log_message('info', 'Created index: idx_absensi_event_status');

            // Index for user attendance history
            $db->query('CREATE INDEX IF NOT EXISTS idx_absensi_user_date ON absensi(id_user, DATE(waktu_scan))');
            log_message('info', 'Created index: idx_absensi_user_date');

            // Index for payment verification checks (used in QR validation)
            $db->query('CREATE INDEX IF NOT EXISTS idx_payment_verification ON pembayaran(id_user, event_id, status, verified_at)');
            log_message('info', 'Created index: idx_payment_verification');

            // Index for quick admin stats
            $db->query('CREATE INDEX IF NOT EXISTS idx_absensi_admin_stats ON absensi(event_id, marked_by_admin, status)');
            log_message('info', 'Created index: idx_absensi_admin_stats');

            // Index for QR code uniqueness and lookup
            $db->query('CREATE INDEX IF NOT EXISTS idx_qr_code_unique ON absensi(qr_code, waktu_scan)');
            log_message('info', 'Created index: idx_qr_code_unique');

            // Partial index for pending payments (PostgreSQL specific)
            try {
                $db->query('CREATE INDEX IF NOT EXISTS idx_payment_pending ON pembayaran(id_user, event_id) WHERE status = \'pending\'');
                log_message('info', 'Created partial index: idx_payment_pending');
            } catch (\Exception $e) {
                // MySQL doesn't support partial indexes, use regular index
                $db->query('CREATE INDEX IF NOT EXISTS idx_payment_status_user ON pembayaran(status, id_user, event_id)');
                log_message('info', 'Created alternative index: idx_payment_status_user');
            }

            // Index for recent attendance (used in live stats)
            $db->query('CREATE INDEX IF NOT EXISTS idx_absensi_recent ON absensi(waktu_scan DESC, event_id, status)');
            log_message('info', 'Created index: idx_absensi_recent');

            // Composite index for QR scan validation workflow
            $db->query('CREATE INDEX IF NOT EXISTS idx_qr_scan_workflow ON absensi(event_id, id_user, status, waktu_scan, marked_by_admin)');
            log_message('info', 'Created index: idx_qr_scan_workflow');

        } catch (\Exception $e) {
            log_message('error', 'QR Performance Indexes Migration Error: ' . $e->getMessage());
            throw $e;
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();

        $indexes = [
            'idx_qr_validation',
            'idx_payment_participation', 
            'idx_absensi_event_status',
            'idx_absensi_user_date',
            'idx_payment_verification',
            'idx_absensi_admin_stats',
            'idx_qr_code_unique',
            'idx_payment_pending',
            'idx_payment_status_user',
            'idx_absensi_recent',
            'idx_qr_scan_workflow'
        ];

        foreach ($indexes as $index) {
            try {
                $db->query("DROP INDEX IF EXISTS {$index}");
                log_message('info', "Dropped index: {$index}");
            } catch (\Exception $e) {
                log_message('warning', "Failed to drop index {$index}: " . $e->getMessage());
            }
        }
    }
}