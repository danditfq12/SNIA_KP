<?php
namespace App\Models;
use CodeIgniter\Model;

class PembayaranModel extends Model
{
    protected $table      = 'pembayaran';
    protected $primaryKey = 'id_pembayaran';

    protected $allowedFields = [
        'id_user', 'event_id', 'metode', 'jumlah', 'bukti_bayar',
        'status', 'tanggal_bayar', 'id_voucher', 'verified_by',
        'verified_at', 'keterangan', 'participation_type',
        'midtrans_order_id', 'midtrans_snap_token', 'midtrans_transaction_id',
        'midtrans_payment_type', 'midtrans_raw_response', 'midtrans_settlement_time',
        'original_amount', 'discount_amount', 'payment_reference', 
        'auto_verified', 'features_unlocked_at'
    ];

    protected $useTimestamps = false;

    /**
     * Get payments with user information
     */
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

    /**
     * Get payments with verifier information
     */
    public function getPembayaranWithVerifier($limit = null)
    {
        $builder = $this->db->table($this->table)
            ->select('pembayaran.*, users.nama_lengkap, users.email, verifier.nama_lengkap as verifier_name, events.title as event_title')
            ->join('users', 'users.id_user = pembayaran.id_user')
            ->join('users as verifier', 'verifier.id_user = pembayaran.verified_by', 'left')
            ->join('events', 'events.id = pembayaran.event_id', 'left')
            ->orderBy('pembayaran.tanggal_bayar', 'DESC');

        if ($limit) $builder->limit($limit);
        return $builder->get()->getResultArray();
    }

    /**
     * Get revenue by event
     */
    public function getRevenueByEvent($eventId)
    {
        return $this->selectSum('jumlah')
                    ->where('event_id', $eventId)
                    ->where('status', 'verified')
                    ->get()->getRowArray();
    }

    /**
     * Get payment statistics
     */
    public function getPaymentStats()
    {
        return $this->db->query("
            SELECT 
                COUNT(*) as total_payments,
                COUNT(CASE WHEN status = 'verified' THEN 1 END) as verified_payments,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_payments,
                COUNT(CASE WHEN status = 'canceled' THEN 1 END) as canceled_payments,
                COUNT(CASE WHEN status = 'expired' THEN 1 END) as expired_payments,
                SUM(CASE WHEN status = 'verified' THEN jumlah ELSE 0 END) as total_revenue,
                COUNT(CASE WHEN metode = 'midtrans' THEN 1 END) as midtrans_payments,
                COUNT(CASE WHEN auto_verified = true THEN 1 END) as auto_verified_payments
            FROM pembayaran
        ")->getRowArray();
    }

    /**
     * Create Midtrans payment record - ENHANCED VERSION
     */
    public function createMidtransPayment($data)
    {
        $paymentData = [
            'id_user' => $data['id_user'],
            'event_id' => $data['event_id'],
            'metode' => 'midtrans',
            'jumlah' => $data['jumlah'],
            'status' => 'pending',
            'tanggal_bayar' => date('Y-m-d H:i:s'),
            'participation_type' => $data['participation_type'] ?? 'offline',
            'midtrans_order_id' => $data['midtrans_order_id'],
            'midtrans_snap_token' => $data['midtrans_snap_token'],
            'id_voucher' => $data['id_voucher'] ?? null,
            'original_amount' => $data['original_amount'] ?? $data['jumlah'],
            'discount_amount' => $data['discount_amount'] ?? 0,
            'keterangan' => 'Payment via Midtrans - Created',
            'auto_verified' => false
        ];

        log_message('info', 'Creating Midtrans payment: ' . json_encode($paymentData, JSON_UNESCAPED_UNICODE));

        return $this->insert($paymentData);
    }

    /**
     * Create manual payment record (if still needed for admin)
     */
    public function createManualPayment($data)
    {
        $paymentData = [
            'id_user' => $data['id_user'],
            'event_id' => $data['event_id'],
            'metode' => $data['metode'] ?? 'transfer_bank',
            'jumlah' => $data['jumlah'],
            'bukti_bayar' => $data['bukti_bayar'] ?? null,
            'status' => 'pending',
            'tanggal_bayar' => date('Y-m-d H:i:s'),
            'participation_type' => $data['participation_type'] ?? 'offline',
            'id_voucher' => $data['id_voucher'] ?? null,
            'original_amount' => $data['original_amount'] ?? $data['jumlah'],
            'discount_amount' => $data['discount_amount'] ?? 0,
            'keterangan' => 'Manual payment - Awaiting verification',
            'auto_verified' => false
        ];

        return $this->insert($paymentData);
    }

    /**
     * Update payment status from Midtrans notification - ENHANCED
     */
    public function updateFromMidtransNotification($orderId, $notificationData)
    {
        $updateData = [
            'midtrans_transaction_id' => $notificationData['transaction_id'] ?? $orderId,
            'midtrans_payment_type' => $notificationData['payment_type'] ?? null,
            'midtrans_raw_response' => json_encode($notificationData['raw_notification'] ?? $notificationData),
            'keterangan' => 'Updated from Midtrans: ' . ($notificationData['transaction_status'] ?? 'unknown'),
        ];

        // Handle different payment statuses
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

    /**
     * Get payment by Midtrans order ID
     */
    public function getByMidtransOrderId($orderId)
    {
        if (empty($orderId)) {
            return null;
        }

        return $this->where('midtrans_order_id', $orderId)->first();
    }

    /**
     * Get user's latest payment for event
     */
    public function getUserLatestPaymentForEvent($userId, $eventId)
    {
        return $this->where('id_user', $userId)
                    ->where('event_id', $eventId)
                    ->orderBy('id_pembayaran', 'DESC')
                    ->first();
    }

    /**
     * Check if user has verified payment for event
     */
    public function hasVerifiedPayment($userId, $eventId)
    {
        return $this->where('id_user', $userId)
                    ->where('event_id', $eventId)
                    ->where('status', 'verified')
                    ->countAllResults() > 0;
    }

    /**
     * Get pending Midtrans payments (for status check)
     */
    public function getPendingMidtransPayments($limit = 50)
    {
        return $this->where('metode', 'midtrans')
                    ->where('status', 'pending')
                    ->whereNotNull('midtrans_order_id')
                    ->where('tanggal_bayar >', date('Y-m-d H:i:s', strtotime('-7 days')))
                    ->orderBy('tanggal_bayar', 'DESC')
                    ->findAll($limit);
    }

    /**
     * Get Midtrans payment statistics
     */
    public function getMidtransStats()
    {
        return $this->db->query("
            SELECT 
                COUNT(*) as total_midtrans,
                COUNT(CASE WHEN status = 'verified' THEN 1 END) as verified_midtrans,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_midtrans,
                COUNT(CASE WHEN status = 'canceled' THEN 1 END) as canceled_midtrans,
                COUNT(CASE WHEN status = 'expired' THEN 1 END) as expired_midtrans,
                SUM(CASE WHEN status = 'verified' THEN jumlah ELSE 0 END) as midtrans_revenue,
                AVG(CASE WHEN status = 'verified' THEN jumlah ELSE NULL END) as avg_payment_amount,
                COUNT(CASE WHEN auto_verified = true THEN 1 END) as auto_verified_count
            FROM pembayaran 
            WHERE metode = 'midtrans'
        ")->getRowArray();
    }

    /**
     * Clean up expired pending payments
     */
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

    /**
     * Get payment file path (for manual payments if still needed)
     */
    public function getBuktiBayarPath($payment)
    {
        if (empty($payment['bukti_bayar'])) {
            return null;
        }

        $filePath = WRITEPATH . 'uploads/pembayaran/' . $payment['bukti_bayar'];
        
        return is_file($filePath) ? $filePath : null;
    }

    /**
     * Get payments by status with pagination
     */
    public function getPaymentsByStatus($status, $page = 1, $perPage = 20)
    {
        $offset = ($page - 1) * $perPage;

        return $this->select('pembayaran.*, users.nama_lengkap, users.email, events.title as event_title')
                    ->join('users', 'users.id_user = pembayaran.id_user')
                    ->join('events', 'events.id = pembayaran.event_id', 'left')
                    ->where('pembayaran.status', $status)
                    ->orderBy('pembayaran.tanggal_bayar', 'DESC')
                    ->limit($perPage, $offset)
                    ->findAll();
    }

    /**
     * Get payments by date range
     */
    public function getPaymentsByDateRange($startDate, $endDate, $status = null)
    {
        $builder = $this->select('pembayaran.*, users.nama_lengkap, users.email, events.title as event_title')
                        ->join('users', 'users.id_user = pembayaran.id_user')
                        ->join('events', 'events.id = pembayaran.event_id', 'left')
                        ->where('DATE(pembayaran.tanggal_bayar) >=', $startDate)
                        ->where('DATE(pembayaran.tanggal_bayar) <=', $endDate);

        if ($status) {
            $builder->where('pembayaran.status', $status);
        }

        return $builder->orderBy('pembayaran.tanggal_bayar', 'DESC')->findAll();
    }

    /**
     * Get payment summary by event
     */
    public function getPaymentSummaryByEvent($eventId)
    {
        return $this->db->query("
            SELECT 
                COUNT(*) as total_payments,
                COUNT(CASE WHEN status = 'verified' THEN 1 END) as verified_count,
                COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_count,
                COUNT(CASE WHEN status = 'canceled' THEN 1 END) as canceled_count,
                COUNT(CASE WHEN status = 'expired' THEN 1 END) as expired_count,
                SUM(CASE WHEN status = 'verified' THEN jumlah ELSE 0 END) as total_revenue,
                AVG(CASE WHEN status = 'verified' THEN jumlah ELSE NULL END) as avg_payment,
                COUNT(CASE WHEN metode = 'midtrans' THEN 1 END) as digital_payments,
                COUNT(CASE WHEN auto_verified = true THEN 1 END) as auto_verified_count
            FROM pembayaran 
            WHERE event_id = ?
        ", [$eventId])->getRowArray();
    }

    /**
     * Get recent payments with full details
     */
    public function getRecentPayments($limit = 10, $status = null)
    {
        $builder = $this->select('
            pembayaran.*,
            users.nama_lengkap,
            users.email,
            users.role,
            events.title as event_title,
            events.event_date
        ')
        ->join('users', 'users.id_user = pembayaran.id_user')
        ->join('events', 'events.id = pembayaran.event_id', 'left')
        ->orderBy('pembayaran.tanggal_bayar', 'DESC')
        ->limit($limit);

        if ($status) {
            $builder->where('pembayaran.status', $status);
        }

        return $builder->findAll();
    }

    /**
     * Update payment status with logging
     */
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

    /**
     * Check payment status consistency
     */
    public function checkPaymentConsistency($paymentId)
    {
        $payment = $this->find($paymentId);
        
        if (!$payment) {
            return ['consistent' => false, 'error' => 'Payment not found'];
        }

        $issues = [];

        // Check Midtrans payments
        if ($payment['metode'] === 'midtrans') {
            if (empty($payment['midtrans_order_id'])) {
                $issues[] = 'Missing Midtrans order ID';
            }

            if ($payment['status'] === 'verified' && empty($payment['verified_at'])) {
                $issues[] = 'Verified payment missing verification timestamp';
            }
        }

        // Check voucher consistency
        if (!empty($payment['id_voucher'])) {
            $originalAmount = (float)($payment['original_amount'] ?? 0);
            $finalAmount = (float)($payment['jumlah'] ?? 0);
            $discountAmount = (float)($payment['discount_amount'] ?? 0);

            if ($originalAmount > 0 && ($originalAmount - $discountAmount) != $finalAmount) {
                $issues[] = 'Discount calculation inconsistent';
            }
        }

        return [
            'consistent' => empty($issues),
            'issues' => $issues,
            'payment_data' => $payment
        ];
    }

    /**
     * Force status refresh from Midtrans
     */
    public function forceStatusRefresh($paymentId)
    {
        $payment = $this->find($paymentId);
        
        if (!$payment || $payment['metode'] !== 'midtrans' || empty($payment['midtrans_order_id'])) {
            return ['success' => false, 'message' => 'Not a valid Midtrans payment'];
        }

        try {
            $midtransService = new \App\Services\MidtransService();
            $result = $midtransService->syncPaymentStatus($payment['midtrans_order_id']);
            
            if ($result['success']) {
                // Update based on fresh data from Midtrans
                $this->updateFromMidtransNotification($payment['midtrans_order_id'], [
                    'transaction_status' => $result['transaction_status'],
                    'fraud_status' => $result['fraud_status'],
                    'payment_type' => $result['raw_data']['payment_type'] ?? null,
                    'transaction_id' => $result['raw_data']['transaction_id'] ?? $payment['midtrans_order_id'],
                    'settlement_time' => $result['raw_data']['settlement_time'] ?? null,
                    'raw_notification' => $result['raw_data']
                ]);

                return ['success' => true, 'message' => 'Status updated from Midtrans'];
            } else {
                return ['success' => false, 'message' => $result['error']];
            }
            
        } catch (\Exception $e) {
            log_message('error', 'Force refresh error: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to refresh status'];
        }
    }
}