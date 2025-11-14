<?php

namespace App\Controllers\Webhook;

use App\Controllers\BaseController;
use App\Models\PembayaranModel;
use App\Models\EventRegistrationModel;
use App\Models\VoucherModel;
use App\Services\MidtransService;
use App\Services\NotificationService;

/**
 * Midtrans Webhook Handler - FIXED VERSION
 * 
 * FIX: Correctly detect BCA VA, DANA, GoPay, etc
 * Instead of generic "bank_transfer"
 * 
 * @version 2.1 - Payment Method Detection FIXED
 * @date 2025-11-14
 */
class Midtrans extends BaseController
{
    protected $paymentModel;
    protected $registrationModel;
    protected $voucherModel;
    protected $midtransService;
    protected $notificationService;
    protected $serverKey;

    public function __construct()
    {
        $this->paymentModel        = new PembayaranModel();
        $this->registrationModel   = new EventRegistrationModel();
        $this->voucherModel        = new VoucherModel();
        $this->midtransService     = new MidtransService();
        $this->notificationService = new NotificationService();

        $this->serverKey = env('MIDTRANS_SERVER_KEY');
        if (empty($this->serverKey)) {
            log_message('error', 'MIDTRANS_SERVER_KEY kosong. Set di .env');
            throw new \RuntimeException('MIDTRANS_SERVER_KEY tidak terkonfigurasi');
        }
    }

    /**
     * Webhook handler
     */
    public function handle()
    {
        log_message('info', '=== MIDTRANS WEBHOOK START ===');
        log_message('info', 'Method: ' . $this->request->getMethod() . ' | IP: ' . $this->request->getIPAddress());

        $this->response->setHeader('Access-Control-Allow-Origin', '*');
        $this->response->setHeader('Access-Control-Allow-Methods', 'POST, GET, OPTIONS, HEAD');
        $this->response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        $this->response->setHeader('Content-Type', 'application/json');

        if ($this->request->getMethod() === 'OPTIONS') {
            return $this->response->setStatusCode(200)->setJSON(['status' => 'ok']);
        }

        if ($this->request->is('get') || $this->request->getMethod() === 'head') {
            return $this->response->setStatusCode(200)->setJSON([
                'status'         => 'ok',
                'message'        => 'Webhook endpoint accessible',
                'timestamp'      => date('Y-m-d H:i:s'),
                'method'         => strtoupper($this->request->getMethod()),
                'environment'    => ENVIRONMENT,
                'server_key_set' => !empty($this->serverKey),
                'endpoint_url'   => (string) $this->request->getUri(),
            ]);
        }

        if (!in_array($this->request->getMethod(), ['POST'])) {
            return $this->response->setStatusCode(405)->setJSON(['error' => 'Method not allowed']);
        }

        try {
            $rawInput = $this->request->getBody() ?? '';
            $json     = $this->request->getJSON(true) ?? [];
            $post     = $this->request->getPost() ?? [];
            $data     = !empty($json) ? $json : $post;

            log_message('info', 'Raw input length: ' . strlen($rawInput));
            if (!empty($rawInput)) {
                log_message('info', 'Raw input (first 1000): ' . substr($rawInput, 0, 1000));
            }

            if (empty($rawInput) || empty($data) || empty($data['signature_key'])) {
                log_message('warning', 'Midtrans TEST/EMPTY payload → reply 200 OK (dashboard test).');
                return $this->response->setStatusCode(200)->setBody('OK');
            }

            $orderId      = $data['order_id']      ?? '';
            $statusCode   = $data['status_code']   ?? '';
            $grossAmount  = (string)($data['gross_amount'] ?? '');
            $signatureKey = $data['signature_key'] ?? '';

            if ($orderId === '') {
                return $this->response->setStatusCode(400)->setJSON(['error' => 'Missing order_id']);
            }

            if (function_exists('str_starts_with') && str_starts_with($orderId, 'payment_notif_test_')) {
                log_message('warning', "Dashboard TEST order_id={$orderId} → 200 OK");
                return $this->response->setStatusCode(200)->setBody('OK');
            }

            if (!$this->verifySignature($orderId, $statusCode, $grossAmount, $signatureKey)) {
                log_message('error', "Invalid signature for order {$orderId}");
                return $this->response->setStatusCode(200)->setBody('IGNORED');
            }

            $payment = $this->paymentModel->getByMidtransOrderId($orderId);
            if (!$payment) {
                log_message('error', "Payment not found for order_id={$orderId}");
                return $this->response->setStatusCode(200)->setBody('IGNORED');
            }

            $result = $this->processNotification($payment, $data);

            log_message('info', "Webhook processed: {$result['message']} | order={$orderId}");
            log_message('info', '=== MIDTRANS WEBHOOK END ===');

            return $this->response->setJSON([
                'status'  => 'success',
                'message' => 'Notification processed successfully'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Webhook error: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'status'     => 'error',
                'message'    => 'Internal server error',
                'error_code' => 'WEBHOOK_PROCESSING_ERROR'
            ]);
        }
    }

    /**
     * ===== NEW: Detect SPECIFIC payment method =====
     * 
     * This fixes the issue where admin sees "Bank Transfer"
     * instead of "BCA Virtual Account"
     */
    private function detectSpecificPaymentMethod($notification)
    {
        $paymentType = strtolower($notification['payment_type'] ?? '');

        log_message('info', "Detecting payment method from type: {$paymentType}");

        // ===== VIRTUAL ACCOUNT (BCA, BNI, BRI, etc) =====
        if ($paymentType === 'bank_transfer') {
            // Check va_numbers array for specific bank
            if (!empty($notification['va_numbers']) && is_array($notification['va_numbers'])) {
                $bank = strtolower($notification['va_numbers'][0]['bank'] ?? '');
                
                log_message('info', "VA bank detected: {$bank}");
                
                if ($bank) {
                    return $bank . '_va';  // e.g., "bca_va", "bni_va", "bri_va"
                }
            }
            
            // Check for Permata VA
            if (!empty($notification['permata_va_number'])) {
                log_message('info', "Permata VA detected");
                return 'permata_va';
            }
            
            log_message('warning', "Generic bank_transfer - no specific bank found");
            return 'bank_transfer';
        }

        // ===== MANDIRI BILL =====
        if ($paymentType === 'echannel') {
            if (!empty($notification['bill_key']) || !empty($notification['biller_code'])) {
                log_message('info', "Mandiri VA detected via echannel");
                return 'mandiri_va';
            }
            return 'echannel';
        }

        // ===== E-WALLETS =====
        if ($paymentType === 'gopay') {
            log_message('info', "GoPay detected");
            return 'gopay';
        }

        if ($paymentType === 'shopeepay') {
            log_message('info', "ShopeePay detected");
            return 'shopeepay';
        }

        if ($paymentType === 'qris') {
            // Check acquirer for more specific info
            $acquirer = strtolower($notification['acquirer'] ?? '');
            log_message('info', "QRIS detected, acquirer: {$acquirer}");
            
            if ($acquirer === 'gopay') return 'gopay';
            if ($acquirer === 'shopeepay') return 'shopeepay';
            
            return 'qris';
        }

        // Additional e-wallets
        if (in_array($paymentType, ['dana', 'linkaja', 'ovo'])) {
            log_message('info', ucfirst($paymentType) . " detected");
            return $paymentType;
        }

        // ===== CREDIT/DEBIT CARD =====
        if ($paymentType === 'credit_card') {
            log_message('info', "Credit card detected");
            return 'credit_card';
        }

        // ===== CONVENIENCE STORE =====
        if ($paymentType === 'cstore') {
            $store = strtolower($notification['store'] ?? '');
            log_message('info', "Convenience store detected: {$store}");
            
            if ($store === 'indomaret') return 'indomaret';
            if ($store === 'alfamart') return 'alfamart';
            
            return 'cstore';
        }

        // ===== OTHER METHODS =====
        if (in_array($paymentType, ['akulaku', 'kredivo'])) {
            log_message('info', ucfirst($paymentType) . " detected");
            return $paymentType;
        }

        // ===== FALLBACK =====
        log_message('warning', "Unknown payment type, using generic: {$paymentType}");
        return $paymentType;
    }

    /**
     * Signature verification
     */
    private function verifySignature($orderId, $statusCode, $grossAmount, $signatureKey)
    {
        if ($orderId === '' || $statusCode === '' || $grossAmount === '' || $signatureKey === '') {
            log_message('error', 'Missing signature components');
            return false;
        }

        $grossAmount = (string) $grossAmount;
        log_message('info', "SIG parts: order_id={$orderId} status_code={$statusCode} gross_amount={$grossAmount}");

        $calc = hash('sha512', $orderId . $statusCode . $grossAmount . $this->serverKey);
        $isValid = hash_equals($calc, $signatureKey);

        log_message('info', 'Signature verification: ' . ($isValid ? 'VALID' : 'INVALID'));
        if (!$isValid) {
            log_message('error', "Signature mismatch for order {$orderId}");
        }

        return $isValid;
    }

    /**
     * Business logic pemrosesan notifikasi - FIXED VERSION
     */
    private function processNotification($payment, $notification)
    {
        $transactionStatus = strtolower($notification['transaction_status'] ?? '');
        $fraudStatus       = strtolower($notification['fraud_status'] ?? '');
        $orderId           = $notification['order_id'] ?? '';
        $oldStatus         = $payment['status'];

        $newStatus = $this->mapMidtransStatus($transactionStatus, $fraudStatus);

        // ===== FIX: Detect SPECIFIC payment method =====
        $specificPaymentMethod = $this->detectSpecificPaymentMethod($notification);
        
        log_message('info', "✅ Payment method: {$specificPaymentMethod} (was: {$notification['payment_type']})");

        $updateData = [
            'midtrans_transaction_id' => $notification['transaction_id'] ?? $orderId,
            'midtrans_payment_type'   => $specificPaymentMethod,  // ← FIXED!
            'midtrans_raw_response'   => json_encode($notification),
            'midtrans_settlement_time'=> $notification['settlement_time'] ?? null,
            'keterangan'              => "Webhook: {$transactionStatus}" . ($fraudStatus ? " (fraud: {$fraudStatus})" : "")
        ];

        $statusChanged = false;

        if ($newStatus !== $oldStatus && $this->isValidStatusTransition($oldStatus, $newStatus)) {
            $updateData['status'] = $newStatus;
            $statusChanged = true;

            if ($newStatus === 'verified') {
                $updateData['verified_at']          = $notification['settlement_time'] ?? date('Y-m-d H:i:s');
                $updateData['auto_verified']        = true;
                $updateData['features_unlocked_at'] = $notification['settlement_time'] ?? date('Y-m-d H:i:s');
                $this->handleVerifiedPayment($payment);
            } elseif (in_array($newStatus, ['canceled', 'expired'])) {
                $this->handleFailedPayment($payment);
            }
        }

        $ok = $this->paymentModel->update($payment['id_pembayaran'], $updateData);
        if (!$ok) {
            throw new \RuntimeException('Failed to update payment record');
        }

        if ($statusChanged) {
            $this->updateRegistrationStatus($payment, $newStatus);
        }

        return [
            'success'        => true,
            'message'        => 'Notification processed successfully',
            'order_id'       => $orderId,
            'old_status'     => $oldStatus,
            'new_status'     => $newStatus,
            'payment_method' => $specificPaymentMethod,
            'payment_id'     => $payment['id_pembayaran'],
            'status_changed' => $statusChanged
        ];
    }

    private function mapMidtransStatus($transactionStatus, $fraudStatus)
    {
        switch ($transactionStatus) {
            case 'settlement': return 'verified';
            case 'capture':
                if ($fraudStatus === 'accept' || empty($fraudStatus)) return 'verified';
                if ($fraudStatus === 'challenge') return 'pending';
                return 'canceled';
            case 'pending':  return 'pending';
            case 'deny':
            case 'cancel':
            case 'failure':  return 'canceled';
            case 'expire':   return 'expired';
            default:
                log_message('warning', "Unknown transaction status: {$transactionStatus}");
                return 'pending';
        }
    }

    private function isValidStatusTransition($oldStatus, $newStatus)
    {
        $valid = [
            'pending'  => ['verified', 'canceled', 'expired'],
            'verified' => [],
            'canceled' => [],
            'expired'  => [],
            'rejected' => [],
        ];
        return in_array($newStatus, $valid[$oldStatus] ?? []);
    }

    private function handleVerifiedPayment($payment)
    {
        try {
            $this->updateRegistrationStatus($payment, 'verified');
            if (!empty($payment['id_voucher'])) {
                $this->voucherModel->reduceQuota($payment['id_voucher']);
            }
            $this->sendNotification($payment, 'Pembayaran berhasil diverifikasi otomatis!', 'success');
        } catch (\Exception $e) {
            log_message('error', 'Error in handleVerifiedPayment: ' . $e->getMessage());
        }
    }

    private function handleFailedPayment($payment)
    {
        try {
            $this->updateRegistrationStatus($payment, 'canceled');
            $this->sendNotification($payment, 'Pembayaran dibatalkan atau kedaluwarsa.', 'warning');
        } catch (\Exception $e) {
            log_message('error', 'Error in handleFailedPayment: ' . $e->getMessage());
        }
    }

    private function updateRegistrationStatus($payment, $status)
    {
        try {
            $regStatus = 'menunggu_pembayaran';
            if ($status === 'verified') $regStatus = 'lunas';
            if (in_array($status, ['canceled','expired'])) $regStatus = 'batal';

            $registration = $this->registrationModel
                ->where('id_user', $payment['id_user'])
                ->where('id_event', $payment['event_id'])
                ->first();

            if ($registration) {
                $this->registrationModel->update($registration['id'], ['status' => $regStatus]);
            } else {
                log_message('warning', "Registration not found for user {$payment['id_user']}, event {$payment['event_id']}");
            }
        } catch (\Exception $e) {
            log_message('error', 'Registration update failed: ' . $e->getMessage());
        }
    }

    private function sendNotification($payment, $message, $type = 'info')
    {
        try {
            if (!$this->notificationService) return;
            $this->notificationService->notify(
                (int)$payment['id_user'],
                'payment',
                'Update Status Pembayaran',
                $message,
                site_url('audience/pembayaran/detail/' . $payment['id_pembayaran']),
                $type
            );
        } catch (\Exception $e) {
            log_message('error', 'Notification failed: ' . $e->getMessage());
        }
    }

    public function checkStatus($orderId = null)
    {
        if (!$orderId) {
            return $this->response->setStatusCode(400)->setJSON(['error' => 'Order ID required']);
        }

        try {
            $statusData = $this->midtransService->getTransactionStatus($orderId);
            $payment    = $this->paymentModel->getByMidtransOrderId($orderId);
            if (!$payment) {
                return $this->response->setStatusCode(200)->setBody('IGNORED');
            }

            $result = $this->processNotification($payment, $statusData);

            return $this->response->setJSON([
                'success'       => true,
                'message'       => 'Status check completed',
                'midtrans_data' => $statusData,
                'update_result' => $result
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Manual status check failed: ' . $e->getMessage());
            return $this->response->setStatusCode(500)->setJSON([
                'error'   => 'Status check failed',
                'message' => $e->getMessage()
            ]);
        }
    }
}