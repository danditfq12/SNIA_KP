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
        // === LOG & HEADERS ===
        log_message('info', '=== MIDTRANS WEBHOOK START ===');
        log_message('info', 'Method: ' . $this->request->getMethod() . ' | IP: ' . $this->request->getIPAddress());

        $this->response->setHeader('Access-Control-Allow-Origin', '*');
        $this->response->setHeader('Access-Control-Allow-Methods', 'POST, GET, OPTIONS, HEAD');
        $this->response->setHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization');
        $this->response->setHeader('Content-Type', 'application/json');

        // Preflight
        if ($this->request->getMethod() === 'OPTIONS') {
            return $this->response->setStatusCode(200)->setJSON(['status' => 'ok']);
        }

        // Izinkan GET/HEAD untuk ping/connectivity test
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
            // === Ambil body & parse ===
            $rawInput = $this->request->getBody() ?? '';
            $json     = $this->request->getJSON(true) ?? [];
            $post     = $this->request->getPost() ?? [];
            $data     = !empty($json) ? $json : $post;

            log_message('info', 'Raw input length: ' . strlen($rawInput));
            if (!empty($rawInput)) {
                log_message('info', 'Raw input (first 1000): ' . substr($rawInput, 0, 1000));
            }

            // === Dashboard "Test notification URL" sering kirim POST kosong/ tanpa signature ===
            if (empty($rawInput) || empty($data) || empty($data['signature_key'])) {
                log_message('warning', 'Midtrans TEST/EMPTY payload → reply 200 OK (dashboard test).');
                return $this->response->setStatusCode(200)->setBody('OK');
            }

            // === Notifikasi beneran (punya signature) ===
            $orderId      = $data['order_id']      ?? '';
            $statusCode   = $data['status_code']   ?? '';
            $grossAmount  = (string)($data['gross_amount'] ?? '');
            $signatureKey = $data['signature_key'] ?? '';

            if ($orderId === '') {
                return $this->response->setStatusCode(400)->setJSON(['error' => 'Missing order_id']);
            }

            // Jika order_id khusus "payment_notif_test_*" (tombol Test) → balas 200 OK agar lulus
            if (function_exists('str_starts_with') && str_starts_with($orderId, 'payment_notif_test_')) {
                log_message('warning', "Dashboard TEST order_id={$orderId} → 200 OK");
                return $this->response->setStatusCode(200)->setBody('OK');
            }

            // Verifikasi signature
            if (!$this->verifySignature($orderId, $statusCode, $grossAmount, $signatureKey)) {
                log_message('error', "Invalid signature for order {$orderId}");
                // Dev-friendly: 200 supaya Midtrans tidak retry terus; ganti ke 401 jika ingin strict di production.
                return $this->response->setStatusCode(200)->setBody('IGNORED');
            }

            // Ambil payment lokal
            $payment = $this->paymentModel->getByMidtransOrderId($orderId);
            if (!$payment) {
                // Jangan bikin Midtrans retry terus-terusan
                log_message('error', "Payment not found for order_id={$orderId}");
                return $this->response->setStatusCode(200)->setBody('IGNORED');
            }

            // Proses business logic
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
     * Signature verification
     */
    private function verifySignature($orderId, $statusCode, $grossAmount, $signatureKey)
    {
        if ($orderId === '' || $statusCode === '' || $grossAmount === '' || $signatureKey === '') {
            log_message('error', 'Missing signature components');
            return false;
        }

        // Gunakan string raw dari payload
        $grossAmount = (string) $grossAmount;

        // Log komponen (aman, tanpa server key) untuk debugging
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
     * Business logic pemrosesan notifikasi (tetap)
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
                // Jangan bikin retry loop
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