<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class QRPerformanceIndexes extends Migration
{
    public function up()
    {
        $db = \Config\Database::connect();

        // helper cek kolom ada / tidak
        $has = function(string $table, string $col) use ($db): bool {
            $names = array_map('strtolower', $db->getFieldNames($table) ?? []);
            return in_array(strtolower($col), $names, true);
        };

        // ====== ABSENSI ======
        if ($has('absensi','qr_code') && $has('absensi','id_user')) {
            // kalau event_id belum ada, buat index tanpa kolom itu dulu
            $cols = $has('absensi','event_id') ? 'qr_code, event_id, id_user' : 'qr_code, id_user';
            $db->query("CREATE INDEX IF NOT EXISTS idx_qr_validation ON absensi ($cols)");
        }

        if ($has('absensi','event_id') && $has('absensi','status') && $has('absensi','waktu_scan')) {
            $db->query("CREATE INDEX IF NOT EXISTS idx_absensi_event_status ON absensi(event_id, status, waktu_scan)");
        }

        // Expression index untuk tanggal scan (Postgres)
        if ($has('absensi','id_user') && $has('absensi','waktu_scan')) {
            // INDEX ON absensi (id_user, (date(waktu_scan)))
            $db->query("CREATE INDEX IF NOT EXISTS idx_absensi_user_date ON absensi(id_user, (date(waktu_scan)))");
        }

        if ($has('absensi','event_id') && $has('absensi','marked_by_admin') && $has('absensi','status')) {
            $db->query("CREATE INDEX IF NOT EXISTS idx_absensi_admin_stats ON absensi(event_id, marked_by_admin, status)");
        }

        if ($has('absensi','qr_code') && $has('absensi','waktu_scan')) {
            $db->query("CREATE INDEX IF NOT EXISTS idx_qr_code_lookup ON absensi(qr_code, waktu_scan)");
        }

        if ($has('absensi','waktu_scan') && $has('absensi','status')) {
            // order DESC didukung Postgres
            $part = $has('absensi','event_id') ? ', event_id' : '';
            $db->query("CREATE INDEX IF NOT EXISTS idx_absensi_recent ON absensi(waktu_scan DESC{$part}, status)");
        }

        if ($has('absensi','id_user') && $has('absensi','status') && $has('absensi','waktu_scan')) {
            if ($has('absensi','event_id') && $has('absensi','marked_by_admin')) {
                $db->query("CREATE INDEX IF NOT EXISTS idx_qr_scan_workflow ON absensi(event_id, id_user, status, waktu_scan, marked_by_admin)");
            } else {
                // fallback tanpa kolom yang belum ada
                $db->query("CREATE INDEX IF NOT EXISTS idx_qr_scan_workflow_partial ON absensi(id_user, status, waktu_scan)");
            }
        }

        // ====== PEMBAYARAN ======
        if ($has('pembayaran','participation_type') && $has('pembayaran','status')) {
            $db->query("CREATE INDEX IF NOT EXISTS idx_payment_participation ON pembayaran(participation_type, status)");
        }

        if ($has('pembayaran','id_user') && $has('pembayaran','status')) {
            // partial index untuk pending (Postgres); kalau event_id belum ada, buang saja dari kolom
            if ($has('pembayaran','event_id')) {
                $db->query("CREATE INDEX IF NOT EXISTS idx_payment_pending ON pembayaran(id_user, event_id) WHERE status = 'pending'");
                $db->query("CREATE INDEX IF NOT EXISTS idx_payment_verification ON pembayaran(id_user, event_id, status, verified_at)");
            } else {
                $db->query("CREATE INDEX IF NOT EXISTS idx_payment_pending_user ON pembayaran(id_user) WHERE status = 'pending'");
                $db->query("CREATE INDEX IF NOT EXISTS idx_payment_verification_basic ON pembayaran(id_user, status, verified_at)");
            }
        }
    }

    public function down()
    {
        $db = \Config\Database::connect();
        $indexes = [
            'idx_qr_validation',
            'idx_absensi_event_status',
            'idx_absensi_user_date',
            'idx_absensi_admin_stats',
            'idx_qr_code_lookup',
            'idx_absensi_recent',
            'idx_qr_scan_workflow',
            'idx_qr_scan_workflow_partial',
            'idx_payment_participation',
            'idx_payment_pending',
            'idx_payment_pending_user',
            'idx_payment_verification',
            'idx_payment_verification_basic',
        ];
        foreach ($indexes as $i) {
            $db->query("DROP INDEX IF EXISTS {$i}");
        }
    }
}
