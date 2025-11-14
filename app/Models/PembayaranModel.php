<?php
namespace App\Models;
use CodeIgniter\Model;

/**
 * ===============================================
 * PembayaranModel - ULTIMATE FIXED VERSION
 * ===============================================
 * 
 * CRITICAL NOTES:
 * 1. Column 'id_registrasi' does NOT exist in database
 * 2. NEVER pass id_registrasi to insert/update
 * 3. Use event_id + id_user to find registration
 * 
 * @version 4.0 FINAL - ABSOLUTELY NO id_registrasi
 * @date 2025-01-14
 * @author Fixed by AI Assistant
 * ===============================================
 */
class PembayaranModel extends Model
{
    protected $table      = 'pembayaran';
    protected $primaryKey = 'id_pembayaran';

    // ============================================
    // ✅ ALLOWED FIELDS - NO id_registrasi HERE!
    // ============================================
    protected $allowedFields = [
        'id_user',
        'event_id',
        'metode',
        'jumlah',
        'bukti_bayar',
        'status',
        'tanggal_bayar',
        'id_voucher',
        'verified_by',
        'verified_at',
        'keterangan',
        'participation_type',
        'midtrans_order_id',
        'midtrans_snap_token',
        'midtrans_transaction_id',
        'midtrans_payment_type',
        'midtrans_raw_response',
        'midtrans_settlement_time',
        'original_amount',
        'discount_amount',
        'payment_reference',
        'auto_verified',
        'features_unlocked_at'
    ];

    protected $useTimestamps = false;

    // ========================================================================
    // PAYMENT METHOD DISPLAY HELPERS
    // ========================================================================

    public function getPaymentMethodInfo($payment)
    {
        $metode = strtolower($payment['metode'] ?? '');
        $paymentType = strtolower($payment['midtrans_payment_type'] ?? '');
        
        if ($metode === 'midtrans' && !empty($paymentType)) {
            return $this->getMidtransPaymentMethodInfo($paymentType);
        }
        
        return $this->getManualPaymentMethodInfo($metode);
    }
    
    protected function getMidtransPaymentMethodInfo($paymentType)
    {
        $paymentMethodMap = [
            'dana' => ['icon' => 'wallet2', 'label' => 'DANA', 'color' => '#118eea', 'category' => 'E-Wallet'],
            'gopay' => ['icon' => 'wallet2', 'label' => 'GoPay', 'color' => '#00aa13', 'category' => 'E-Wallet'],
            'shopeepay' => ['icon' => 'wallet2', 'label' => 'ShopeePay', 'color' => '#ee4d2d', 'category' => 'E-Wallet'],
            'ovo' => ['icon' => 'wallet2', 'label' => 'OVO', 'color' => '#4b2d83', 'category' => 'E-Wallet'],
            'qris' => ['icon' => 'qr-code', 'label' => 'QRIS', 'color' => '#d32f2f', 'category' => 'QRIS'],
            'bca_va' => ['icon' => 'building', 'label' => 'BCA Virtual Account', 'color' => '#003087', 'category' => 'Virtual Account'],
            'bni_va' => ['icon' => 'building', 'label' => 'BNI Virtual Account', 'color' => '#ed7203', 'category' => 'Virtual Account'],
            'bri_va' => ['icon' => 'building', 'label' => 'BRI Virtual Account', 'color' => '#003d7a', 'category' => 'Virtual Account'],
            'mandiri_va' => ['icon' => 'building', 'label' => 'Mandiri Virtual Account', 'color' => '#003d79', 'category' => 'Virtual Account'],
            'bank_transfer' => ['icon' => 'bank', 'label' => 'Bank Transfer', 'color' => '#1e40af', 'category' => 'Bank'],
        ];
        
        $displayInfo = $paymentMethodMap[$paymentType] ?? [
            'icon' => 'credit-card',
            'label' => ucwords(str_replace(['_', '-'], ' ', $paymentType)),
            'color' => '#6b7280',
            'category' => 'Other'
        ];
        
        $displayInfo['badge'] = 'Midtrans';
        return $displayInfo;
    }
    
    protected function getManualPaymentMethodInfo($metode)
    {
        return [
            'icon' => 'credit-card',
            'label' => ucfirst(str_replace('_', ' ', $metode)),
            'color' => '#6b7280',
            'category' => 'Manual',
            'badge' => null
        ];
    }

    // ========================================================================
    // QUERY METHODS
    // ========================================================================

    public function getPembayaranWithUser($limit = null)
    {
        $builder = $this->db->table($this->table)
            ->select('pembayaran.*, users.nama_lengkap, users.email, users.role, events.title as event_title')
            ->join('users', 'users.id_user = pembayaran.id_user')
            ->join('events', 'events.id = pembayaran.event_id', 'left')
            ->orderBy('pembayaran.tanggal_bayar', 'DESC');

        if ($limit) $builder->limit($limit);
        return $builder->get()->getResultArray();
    }

    public function getPaymentStats()
    {
        return $this->db->query("
            SELECT 
                COUNT(*) as total_payments,
                COUNT(CASE WHEN status = 'verified' THEN 1 END) as verified_payments,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_payments,
                SUM(CASE WHEN status = 'verified' THEN jumlah ELSE 0 END) as total_revenue,
                COUNT(CASE WHEN metode = 'midtrans' THEN 1 END) as midtrans_payments
            FROM pembayaran
        ")->getRowArray();
    }

    public function getMidtransStats()
    {
        return $this->db->query("
            SELECT 
                COUNT(*) as total_midtrans,
                COUNT(CASE WHEN status = 'verified' THEN 1 END) as verified_midtrans,
                SUM(CASE WHEN status = 'verified' THEN jumlah ELSE 0 END) as midtrans_revenue
            FROM pembayaran 
            WHERE metode = 'midtrans'
        ")->getRowArray();
    }

    // ========================================================================
    // ✅✅✅ CRITICAL METHOD - CREATE MIDTRANS PAYMENT ✅✅✅
    // ========================================================================

    /**
     * Create Midtrans payment record
     * 
     * ❌❌❌ ABSOLUTELY NO id_registrasi ❌❌❌
     * 
     * WHY? Because column 'id_registrasi' does NOT exist in table 'pembayaran'
     * 
     * To find registration, use: WHERE event_id = X AND id_user = Y
     * 
     * @param array $data Payment data
     * @return int|false Insert ID or false on failure
     */
    public function createMidtransPayment($data)
    {
        // ============================================
        // 🚨 VALIDATION: Remove id_registrasi if exists
        // ============================================
        if (isset($data['id_registrasi'])) {
            log_message('warning', '❌ id_registrasi was passed but will be REMOVED! This column does not exist.');
            unset($data['id_registrasi']);
        }

        // ============================================
        // ✅ BUILD CLEAN PAYMENT DATA
        // ============================================
        $paymentData = [
            'id_user' => (int)$data['id_user'],
            'event_id' => (int)$data['event_id'],
            'metode' => 'midtrans',
            'jumlah' => (float)$data['jumlah'],
            'status' => 'pending',
            'tanggal_bayar' => date('Y-m-d H:i:s'),
            'participation_type' => $data['participation_type'] ?? 'offline',
            'midtrans_order_id' => $data['midtrans_order_id'] ?? null,
            'midtrans_snap_token' => $data['midtrans_snap_token'] ?? null,
            'id_voucher' => $data['id_voucher'] ?? null,
            'original_amount' => (float)($data['original_amount'] ?? $data['jumlah']),
            'discount_amount' => (float)($data['discount_amount'] ?? 0),
            'keterangan' => 'Payment via Midtrans - Created',
            'auto_verified' => false
        ];

        // ============================================
        // 📝 EXTENSIVE LOGGING
        // ============================================
        log_message('info', '=== CREATING MIDTRANS PAYMENT ===');
        log_message('info', 'User ID: ' . $paymentData['id_user']);
        log_message('info', 'Event ID: ' . $paymentData['event_id']);
        log_message('info', 'Amount: ' . $paymentData['jumlah']);
        log_message('info', 'Order ID: ' . ($paymentData['midtrans_order_id'] ?? 'NULL'));
        log_message('info', 'Participation Type: ' . $paymentData['participation_type']);
        log_message('info', '✅ NO id_registrasi in data!');
        log_message('debug', 'Full payment data: ' . json_encode($paymentData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // ============================================
        // 🔍 VALIDATE REQUIRED FIELDS
        // ============================================
        if (empty($paymentData['id_user']) || empty($paymentData['event_id'])) {
            log_message('error', '❌ Missing required fields: id_user or event_id');
            return false;
        }

        if (empty($paymentData['midtrans_order_id']) || empty($paymentData['midtrans_snap_token'])) {
            log_message('error', '❌ Missing Midtrans data: order_id or snap_token');
            return false;
        }

        // ============================================
        // 💾 INSERT TO DATABASE
        // ============================================
        try {
            $result = $this->insert($paymentData);
            
            if ($result) {
                $insertId = $this->getInsertID();
                log_message('info', '✅ Payment created successfully! ID: ' . $insertId);
                log_message('info', '=== END CREATING MIDTRANS PAYMENT ===');
                return $result;
            } else {
                $error = $this->db->error();
                log_message('error', '❌ Insert failed!');
                log_message('error', 'DB Error Code: ' . ($error['code'] ?? 'unknown'));
                log_message('error', 'DB Error Message: ' . ($error['message'] ?? 'unknown'));
                
                // Check if error is about id_registrasi
                if (isset($error['message']) && strpos($error['message'], 'id_registrasi') !== false) {
                    log_message('critical', '🚨🚨🚨 ERROR STILL CONTAINS id_registrasi! 🚨🚨🚨');
                    log_message('critical', '🚨 This means the file was NOT replaced correctly!');
                    log_message('critical', '🚨 Please check: app/Models/PembayaranModel.php');
                }
                
                throw new \RuntimeException('Failed to create payment: ' . ($error['message'] ?? 'Unknown error'));
            }
        } catch (\Exception $e) {
            log_message('error', '❌ Exception during insert: ' . $e->getMessage());
            log_message('error', 'Stack trace: ' . $e->getTraceAsString());
            throw $e;
        }
    }

    // ========================================================================
    // OTHER MIDTRANS METHODS
    // ========================================================================

    public function updateFromMidtransNotification($orderId, $notificationData)
    {
        $updateData = [
            'midtrans_transaction_id' => $notificationData['transaction_id'] ?? $orderId,
            'midtrans_payment_type' => $notificationData['payment_type'] ?? null,
            'midtrans_raw_response' => json_encode($notificationData['raw_notification'] ?? $notificationData),
            'keterangan' => 'Updated from Midtrans: ' . ($notificationData['transaction_status'] ?? 'unknown'),
        ];

        $status = strtolower($notificationData['transaction_status'] ?? '');
        $fraud = strtolower($notificationData['fraud_status'] ?? '');

        switch ($status) {
            case 'settlement':
                $updateData['status'] = 'verified';
                $updateData['verified_at'] = $notificationData['settlement_time'] ?? date('Y-m-d H:i:s');
                $updateData['auto_verified'] = true;
                $updateData['midtrans_settlement_time'] = $notificationData['settlement_time'] ?? null;
                $updateData['features_unlocked_at'] = $notificationData['settlement_time'] ?? date('Y-m-d H:i:s');
                break;

            case 'capture':
                if ($fraud === 'accept' || empty($fraud)) {
                    $updateData['status'] = 'verified';
                    $updateData['verified_at'] = date('Y-m-d H:i:s');
                    $updateData['auto_verified'] = true;
                    $updateData['features_unlocked_at'] = date('Y-m-d H:i:s');
                } else {
                    $updateData['status'] = 'pending';
                }
                break;

            case 'pending':
                $updateData['status'] = 'pending';
                break;

            case 'deny':
            case 'cancel':
            case 'expire':
            case 'failure':
                $updateData['status'] = 'canceled';
                break;

            default:
                $updateData['status'] = 'pending';
        }

        log_message('info', "Updating payment from Midtrans: {$orderId} -> {$updateData['status']}");
        return $this->where('midtrans_order_id', $orderId)->set($updateData)->update();
    }

    public function getByMidtransOrderId($orderId)
    {
        if (empty($orderId)) return null;
        return $this->where('midtrans_order_id', $orderId)->first();
    }

    public function getPendingMidtransPayments($limit = 50)
    {
        return $this->where('metode', 'midtrans')
                    ->where('status', 'pending')
                    ->whereNotNull('midtrans_order_id')
                    ->where('tanggal_bayar >', date('Y-m-d H:i:s', strtotime('-7 days')))
                    ->orderBy('tanggal_bayar', 'DESC')
                    ->findAll($limit);
    }

    // ========================================================================
    // HELPER METHODS
    // ========================================================================

    public function hasVerifiedPayment($userId, $eventId)
    {
        return $this->where('id_user', $userId)
                    ->where('event_id', $eventId)
                    ->where('status', 'verified')
                    ->countAllResults() > 0;
    }

    public function getUserLatestPaymentForEvent($userId, $eventId)
    {
        return $this->where('id_user', $userId)
                    ->where('event_id', $eventId)
                    ->orderBy('id_pembayaran', 'DESC')
                    ->first();
    }

    public function updatePaymentStatus($paymentId, $newStatus, $verifiedBy = null, $keterangan = null)
    {
        $updateData = [
            'status' => $newStatus,
            'keterangan' => $keterangan
        ];

        if ($newStatus === 'verified') {
            $updateData['verified_at'] = date('Y-m-d H:i:s');
            $updateData['verified_by'] = $verifiedBy;
            
            if (!$verifiedBy) {
                $updateData['auto_verified'] = true;
            }
            
            $updateData['features_unlocked_at'] = date('Y-m-d H:i:s');
        }

        $result = $this->update($paymentId, $updateData);
        
        if ($result) {
            log_message('info', "Payment status updated: {$paymentId} -> {$newStatus}");
        }

        return $result;
    }

    public function cleanupExpiredPayments()
    {
        $expiredDate = date('Y-m-d H:i:s', strtotime('-2 days'));
        
        return $this->where('status', 'pending')
                    ->where('metode', 'midtrans')
                    ->where('tanggal_bayar <', $expiredDate)
                    ->set([
                        'status' => 'expired',
                        'keterangan' => 'Payment expired automatically after 2 days'
                    ])
                    ->update();
    }
}