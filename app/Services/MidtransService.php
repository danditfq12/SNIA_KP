<?php

namespace App\Services;

class MidtransService
{
    private $serverKey;
    private $clientKey;
    private $isProduction;
    private $snapUrl;
    private $apiUrl;

    public function __construct()
    {
        // Ambil dari .env tanpa fallback (jangan pernah hardcode secret)
        $this->serverKey    = env('MIDTRANS_SERVER_KEY');
        $this->clientKey    = env('MIDTRANS_CLIENT_KEY');
        $this->isProduction = filter_var(env('MIDTRANS_IS_PRODUCTION', false), FILTER_VALIDATE_BOOLEAN);

        if (empty($this->serverKey) || empty($this->clientKey)) {
            throw new \RuntimeException('Midtrans belum terkonfigurasi (server/client key kosong)');
        }

        // Endpoint sesuai environment
        $this->snapUrl = $this->isProduction
            ? 'https://app.midtrans.com/snap/v1/transactions'
            : 'https://app.sandbox.midtrans.com/snap/v1/transactions';

        $this->apiUrl  = $this->isProduction
            ? 'https://api.midtrans.com/v2'
            : 'https://api.sandbox.midtrans.com/v2';
    }

    public function isConfigured()
    {
        return !empty($this->serverKey) && !empty($this->clientKey);
    }

    public function createTransaction($params)
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('Midtrans not properly configured - missing server key or client key');
        }

        // Log ringan (hindari logging data sensitif)
        log_message('info', 'Creating Midtrans transaction');

        $body = json_encode($params, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($body === false) {
            throw new \RuntimeException('Gagal encode JSON untuk request Midtrans');
        }

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL            => $this->snapUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $body,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode($this->serverKey . ':'),
            ],
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_CONNECTTIMEOUT => 30,
            // Verifikasi SSL selalu true (aman untuk sandbox & production)
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS      => 3,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if (curl_errno($curl)) {
            $error = curl_error($curl);
            curl_close($curl);
            log_message('error', 'Midtrans CURL Error (create): ' . $error);
            throw new \RuntimeException('Network error connecting to Midtrans: ' . $error);
        }

        curl_close($curl);

        log_message('info', 'Midtrans API Response Code (create): ' . $httpCode);

        $result = json_decode($response, true);

        if ($httpCode !== 201) {
            $errorMsg = 'Midtrans API Error (HTTP ' . $httpCode . ')';
            if (is_array($result)) {
                if (!empty($result['error_messages']) && is_array($result['error_messages'])) {
                    $errorMsg .= ': ' . implode(', ', $result['error_messages']);
                } elseif (!empty($result['message'])) {
                    $errorMsg .= ': ' . $result['message'];
                } elseif (!empty($result['status_message'])) {
                    $errorMsg .= ': ' . $result['status_message'];
                }
            }
            log_message('error', 'Midtrans API Error (create): ' . $errorMsg);
            throw new \RuntimeException($errorMsg);
        }

        if (!is_array($result) || !isset($result['token'])) {
            log_message('error', 'Invalid Midtrans response (create): ' . $response);
            throw new \RuntimeException('Invalid response from Midtrans - no token received');
        }

        return $result; // berisi token & (kadang) redirect_url
    }

    public function getTransactionStatus($orderId)
    {
        if (!$this->isConfigured()) {
            throw new \RuntimeException('Midtrans not properly configured');
        }

        $curl = curl_init();

        curl_setopt_array($curl, [
            CURLOPT_URL            => $this->apiUrl . '/' . $orderId . '/status',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode($this->serverKey . ':'),
            ],
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true, // selalu verifikasi SSL
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if (curl_errno($curl)) {
            $error = curl_error($curl);
            curl_close($curl);
            log_message('error', 'Midtrans CURL Error (status): ' . $error);
            throw new \RuntimeException('Network error checking transaction status: ' . $error);
        }

        curl_close($curl);

        if ($httpCode === 404) {
            throw new \RuntimeException('Transaction not found in Midtrans');
        }

        if ($httpCode !== 200) {
            throw new \RuntimeException('Failed to get transaction status (HTTP ' . $httpCode . ')');
        }

        $result = json_decode($response, true);
        if (!is_array($result)) {
            throw new \RuntimeException('Invalid response from Midtrans API');
        }

        return $result;
    }

    public function verifyNotification($notification)
    {
        $orderId      = isset($notification['order_id'])      ? $notification['order_id']      : '';
        $statusCode   = isset($notification['status_code'])   ? $notification['status_code']   : '';
        $grossAmount  = isset($notification['gross_amount'])  ? (string)$notification['gross_amount'] : '';
        $signatureKey = isset($notification['signature_key']) ? $notification['signature_key'] : '';

        if ($orderId === '' || $statusCode === '' || $grossAmount === '' || $signatureKey === '') {
            log_message('error', 'Missing signature components in Midtrans notification');
            return false;
        }

        $mySignature = hash('sha512', $orderId . $statusCode . $grossAmount . $this->serverKey);
        $isValid     = hash_equals($mySignature, $signatureKey);

        if (!$isValid) {
            // Jangan pernah log potongan server key, cukup info mismatch
            log_message('error', 'Midtrans signature verification failed for order_id: ' . $orderId);
        }

        return $isValid;
    }

    public function generateOrderId($userId, $eventId, $role = 'audience', $participationType = 'offline')
    {
        $timestamp = date('YmdHis');
        $random    = strtoupper(substr(md5(uniqid('', true)), 0, 4));
        $roleCode  = strtoupper(substr($role, 0, 3));
        $typeCode  = strtoupper(substr($participationType, 0, 3));

        return "SNIA-{$roleCode}-{$typeCode}-{$eventId}-{$userId}-{$timestamp}-{$random}";
    }

    public function getClientKey()
    {
        return $this->clientKey;
    }

    public function isProduction()
    {
        return $this->isProduction;
    }

    public function buildTransactionParams($orderId, $amount, $customerDetails, $itemDetails, $eventData = [])
    {
        $baseUrl = rtrim(base_url(), '/') . '/';

        $params = [
            'transaction_details' => [
                'order_id'     => $orderId,
                'gross_amount' => (int) $amount,
            ],
            'customer_details' => [
                'first_name' => $customerDetails['nama_lengkap'] ?? 'Customer',
                'email'      => $customerDetails['email'] ?? '',
                'phone'      => $customerDetails['no_hp'] ?? '',
            ],
            'item_details' => $itemDetails,
            'callbacks' => [
                'finish' => $baseUrl . 'audience/pembayaran/finish?order_id=' . $orderId,
            ],
            'expiry' => [
                'start_time' => date('Y-m-d H:i:s O'),
                'unit'       => 'hours',
                'duration'   => 24,
            ],
            'page_expiry' => [
                'duration' => 30,
                'unit'     => 'minutes',
            ],
        ];

        // Tambah custom field untuk tracking (opsional)
        if (!empty($eventData)) {
            $params['custom_field1'] = (string)($eventData['event_id'] ?? '');
            $params['custom_field2'] = (string)($eventData['user_role'] ?? '');
            $params['custom_field3'] = (string)($eventData['participation_type'] ?? '');
        }

        return $params;
    }

    public function createEventPayment($eventId, $userId, $userRole, $participationType, $amount, $userDetails, $eventDetails)
    {
        try {
            if ($amount <= 0) {
                throw new \InvalidArgumentException('Invalid payment amount');
            }
            if (empty($userDetails['nama_lengkap']) || empty($userDetails['email'])) {
                throw new \InvalidArgumentException('Customer details are incomplete');
            }

            $orderId = $this->generateOrderId($userId, $eventId, $userRole, $participationType);
            log_message('info', "Creating Midtrans payment - Order ID: {$orderId}");

            $customerDetails = [
                'nama_lengkap' => $userDetails['nama_lengkap'] ?? 'User',
                'email'        => $userDetails['email'] ?? '',
                'no_hp'        => $userDetails['no_hp'] ?? '',
            ];

            $itemDetails = [[
                'id'            => 'EVENT-' . $eventId,
                'price'         => (int) $amount,
                'quantity'      => 1,
                'name'          => $eventDetails['title'] ?? 'Event Registration',
                'category'      => 'Event Registration',
                'merchant_name' => 'SNIA Conference',
            ]];

            $eventData = [
                'event_id'           => $eventId,
                'user_role'          => $userRole,
                'participation_type' => $participationType,
            ];

            $params       = $this->buildTransactionParams($orderId, $amount, $customerDetails, $itemDetails, $eventData);
            $snapResponse = $this->createTransaction($params);

            if (!isset($snapResponse['token'])) {
                throw new \RuntimeException('No token received from Midtrans');
            }

            log_message('info', "Midtrans payment created successfully - Token (prefix): " . substr($snapResponse['token'], 0, 6) . '***');

            return [
                'order_id'            => $orderId,
                'snap_token'          => $snapResponse['token'],
                'redirect_url'        => $snapResponse['redirect_url'] ?? null,
                'transaction_details' => $params,
            ];

        } catch (\Exception $e) {
            log_message('error', 'Failed to create Midtrans payment: ' . $e->getMessage());
            throw new \RuntimeException('Failed to create payment: ' . $e->getMessage());
        }
    }

    public function handleNotification($notification)
    {
        if (!$this->verifyNotification($notification)) {
            throw new \RuntimeException('Invalid notification signature');
        }

        $orderId           = $notification['order_id'];
        $transactionStatus = $notification['transaction_status'];
        $fraudStatus       = $notification['fraud_status'] ?? null;

        $paymentStatus = 'pending';

        switch (strtolower($transactionStatus)) {
            case 'capture':
                if (strtolower($fraudStatus) === 'challenge') {
                    $paymentStatus = 'pending';
                } elseif (strtolower($fraudStatus) === 'accept') {
                    $paymentStatus = 'verified';
                }
                break;

            case 'settlement':
                $paymentStatus = 'verified';
                break;

            case 'pending':
                $paymentStatus = 'pending';
                break;

            case 'deny':
                $paymentStatus = 'rejected';
                break;

            case 'expire':
                $paymentStatus = 'expired';
                break;

            case 'cancel':
            case 'failure':
                $paymentStatus = 'canceled';
                break;
        }

        return [
            'order_id'           => $orderId,
            'payment_status'     => $paymentStatus,
            'transaction_status' => $transactionStatus,
            'fraud_status'       => $fraudStatus,
            'payment_type'       => $notification['payment_type']    ?? null,
            'gross_amount'       => $notification['gross_amount']    ?? null,
            'transaction_time'   => $notification['transaction_time']?? null,
            'settlement_time'    => $notification['settlement_time'] ?? null,
            'raw_notification'   => $notification,
        ];
    }

    public function syncPaymentStatus($orderId)
    {
        try {
            $statusData       = $this->getTransactionStatus($orderId);
            $transactionStatus = $statusData['transaction_status'] ?? '';
            $fraudStatus       = $statusData['fraud_status']       ?? '';

            $paymentStatus = 'pending';
            switch (strtolower($transactionStatus)) {
                case 'settlement':
                    $paymentStatus = 'verified';
                    break;
                case 'capture':
                    $paymentStatus = (strtolower($fraudStatus) === 'accept') ? 'verified' : 'pending';
                    break;
                case 'pending':
                    $paymentStatus = 'pending';
                    break;
                case 'deny':
                case 'cancel':
                case 'expire':
                case 'failure':
                    $paymentStatus = 'canceled';
                    break;
            }

            return [
                'success'            => true,
                'order_id'           => $orderId,
                'transaction_status' => $transactionStatus,
                'fraud_status'       => $fraudStatus,
                'payment_status'     => $paymentStatus,
                'raw_data'           => $statusData,
            ];

        } catch (\Exception $e) {
            log_message('error', 'Payment sync error for order ' . $orderId . ': ' . $e->getMessage());
            return [
                'success'  => false,
                'error'    => $e->getMessage(),
                'order_id' => $orderId,
            ];
        }
    }
}