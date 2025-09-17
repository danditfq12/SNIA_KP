<?php

namespace App\Controllers\Webhook;

use App\Controllers\BaseController;
use App\Models\PembayaranModel;
use App\Models\EventRegistrationModel;
use App\Models\VoucherModel;
use App\Services\MidtransService;
use App\Services\NotificationService;

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

        // Jangan pakai default secret
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
        // Basic logs
        log_message('info', '=== MIDTRANS WEBHOOK START ===');
        log_message('info', 'Method: ' . $this->request->getMethod());
        log_message('info', 'IP: ' . $this->request->getIPAddress());

        // CORS (opsional)
        $this->response->setHeader('Access-Control-Allow-Origin', '*');
        $this->response->setHeader('Access-Control-Allow-Methods', 'POST, GET, OPTIONS');
        $this->response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        $this->response->setHeader('Content-Type', 'application/json');

        if ($this->request->getMethod() === 'OPTIONS') {
            return $this->response->setStatusCode(200)->setJSON(['status' => 'ok']);
        }

        if (!in_array($this->request->getMethod(), ['POST', 'GET'])) {
            return $this->response->setStatusCode(405)->setJSON(['error' => 'Method not allowed']);
        }

        try {
            // GET untuk test hanya saat development
            if ($this->request->is('get')) {
                if (ENVIRONMENT !== 'development') {
                    return $this->response->setStatusCode(405)->setJSON(['error' => 'Method not allowed']);
                }

                $testResponse = [
                    'status'        => 'ok',
                    'message'       => 'Webhook endpoint accessible',
                    'timestamp'     => date('Y-m-d H:i:s'),
                    'method'        => 'GET',
                    'environment'   => ENVIRONMENT,
                    'server_key_set'=> !empty($this->serverKey),
                    'endpoint_url'  => current_url()
                ];
                log_message('info', 'GET test request: ' . json_encode($testResponse));
                return $this->response->setJSON($testResponse);
            }

            // Ambil body mentah
            $rawInput = $this->request->getBody();
            log_message('info', 'Raw input length: ' . strlen($rawInput));
            log_message('info', 'Raw input: ' . substr($rawInput, 0, 1000));

            if (empty($rawInput)) {
                return $this->response->setStatusCode(400)->setJSON(['error' => 'Empty request body']);
            }

            // Decode JSON dengan fallback cleaning
            $notification = json_decode($rawInput, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $cleanInput  = preg_replace('/[\x00-\x1F\x80-\xFF]/', '', trim($rawInput));
                $notification = json_decode($cleanInput, true);
                if (json_last_error() !== JSON_ERROR_NONE) {
                    return $this->response->setStatusCode(400)->setJSON([
                        'error'      => 'Invalid JSON: ' . json_last_error_msg(),
                        'raw_length' => strlen($rawInput)
                    ]);
                }
            }
            log_message('info', 'Notification parsed: ' . json_encode($notification));

            // Ambil field penting
            $orderId           = $notification['order_id']          ?? '';
            $transactionStatus = strtolower($notification['transaction_status'] ?? '');
            $fraudStatus       = strtolower($notification['fraud_status'] ?? '');
            $statusCode        = $notification['status_code']       ?? '';
            $grossAmount       = $notification['gross_amount']      ?? '';
            $signatureKey      = $notification['signature_key']     ?? '';

            if (empty($orderId)) {
                return $this->response->setStatusCode(400)->setJSON(['error' => 'Missing order_id']);
            }

            // Verifikasi signature
            if (!$this->verifySignature($orderId, $statusCode, $grossAmount, $signatureKey)) {
                log_message('error', 'Signature verification failed for order: ' . $orderId);
                return $this->response->setStatusCode(401)->setJSON(['error' => 'Invalid signature']);
            }

            // Ambil data payment lokal
            $payment = $this->paymentModel->getByMidtransOrderId($orderId);
            if (!$payment) {
                return $this->response->setStatusCode(404)->setJSON(['error' => 'Payment not found']);
            }

            // Proses
            $result = $this->processNotification($payment, $notification);

            log_message('info', "Webhook processed: {$result['message']}");
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
     * Signature verification
     */
    private function verifySignature($orderId, $statusCode, $grossAmount, $signatureKey)
    {
        if (empty($orderId) || empty($statusCode) || $grossAmount === '' || empty($signatureKey)) {
            log_message('error', 'Missing signature components');
            return false;
        }

        // Pastikan gross amount string mentah
        $grossAmount = (string) $grossAmount;

        $mySignature = hash('sha512', $orderId . $statusCode . $grossAmount . $this->serverKey);
        $isValid     = hash_equals($mySignature, $signatureKey);

        log_message('info', 'Signature verification: ' . ($isValid ? 'VALID' : 'INVALID'));

        if (!$isValid) {
            log_message('error', "Signature mismatch for order {$orderId}");
            // Jangan pernah log potongan server key
        }

        return $isValid;
    }

    /**
     * Business logic pemrosesan notifikasi (tidak diubah)
     */
    private function processNotification($payment, $notification)
    {
        $transactionStatus = strtolower($notification['transaction_status'] ?? '');
        $fraudStatus       = strtolower($notification['fraud_status'] ?? '');
        $orderId           = $notification['order_id'] ?? '';
        $oldStatus         = $payment['status'];

        $newStatus = $this->mapMidtransStatus($transactionStatus, $fraudStatus);

        $updateData = [
            'midtrans_transaction_id' => $notification['transaction_id'] ?? $orderId,
            'midtrans_payment_type'   => $notification['payment_type'] ?? '',
            'midtrans_raw_response'   => json_encode($notification),
            'midtrans_settlement_time'=> $notification['settlement_time'] ?? null,
            'keterangan'              => "Webhook: {$transactionStatus}" . ($fraudStatus ? " (fraud: {$fraudStatus})" : "")
        ];

        $statusChanged = false;

        if ($newStatus !== $oldStatus && $this->isValidStatusTransition($oldStatus, $newStatus)) {
            $updateData['status'] = $newStatus;
            $statusChanged = true;

            if ($newStatus === 'verified') {
                $updateData['verified_at']         = $notification['settlement_time'] ?? date('Y-m-d H:i:s');
                $updateData['auto_verified']       = true;
                $updateData['features_unlocked_at']= $notification['settlement_time'] ?? date('Y-m-d H:i:s');
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
                return $this->response->setStatusCode(404)->setJSON(['error' => 'Payment not found']);
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