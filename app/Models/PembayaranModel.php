<?php
namespace App\Models;
use CodeIgniter\Model;

class PembayaranModel extends Model
{
    protected $table      = 'pembayaran';
    protected $primaryKey = 'id_pembayaran';

    protected $allowedFields = [
        'id_user', 'event_id', 'id_registrasi', 'metode', 'jumlah', 'bukti_bayar',
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
     * Create Midtrans payment record
     */
    public function createMidtransPayment($data)
    {
        $paymentData = [
            'id_user' => $data['id_user'],
            'event_id' => $data['event_id'],
            'id_registrasi' => $data['id_registrasi'] ?? null,
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
     * Update payment status from Midtrans notification
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
}