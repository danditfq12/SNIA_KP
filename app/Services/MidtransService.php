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
        $this->serverKey    = env('MIDTRANS_SERVER_KEY');
        $this->clientKey    = env('MIDTRANS_CLIENT_KEY');
        $this->isProduction = filter_var(env('MIDTRANS_IS_PRODUCTION', false), FILTER_VALIDATE_BOOLEAN);

        if (empty($this->serverKey) || empty($this->clientKey)) {
            throw new \RuntimeException('Midtrans belum terkonfigurasi (server/client key kosong)');
        }

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
            throw new \RuntimeException('Midtrans not properly configured');
        }

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
            log_message('error', 'Midtrans CURL Error: ' . $error);
            throw new \RuntimeException('Network error: ' . $error);
        }

        curl_close($curl);

        $result = json_decode($response, true);

        if ($httpCode !== 201) {
            $errorMsg = 'Midtrans API Error (HTTP ' . $httpCode . ')';
            if (is_array($result)) {
                if (!empty($result['error_messages']) && is_array($result['error_messages'])) {
                    $errorMsg .= ': ' . implode(', ', $result['error_messages']);
                } elseif (!empty($result['message'])) {
                    $errorMsg .= ': ' . $result['message'];
                }
            }
            log_message('error', $errorMsg);
            throw new \RuntimeException($errorMsg);
        }

        if (!is_array($result) || !isset($result['token'])) {
            throw new \RuntimeException('Invalid response - no token received');
        }

        return $result;
    }

    /**
     * ✅ GET Transaction Status with VA extraction
     */
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
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if (curl_errno($curl)) {
            $error = curl_error($curl);
            curl_close($curl);
            log_message('error', 'Midtrans CURL Error (status): ' . $error);
            throw new \RuntimeException('Network error: ' . $error);
        }

        curl_close($curl);

        if ($httpCode === 404) {
            throw new \RuntimeException('Transaction not found');
        }

        if ($httpCode !== 200) {
            throw new \RuntimeException('Failed to get status (HTTP ' . $httpCode . ')');
        }

        $result = json_decode($response, true);
        if (!is_array($result)) {
            throw new \RuntimeException('Invalid API response');
        }

        return $result;
    }

    /**
     * ✅ NEW: Extract Virtual Account info from Midtrans response
     */
    public function extractVirtualAccountInfo($transactionData)
    {
        if (empty($transactionData) || !is_array($transactionData)) {
            return null;
        }

        $paymentType = strtolower($transactionData['payment_type'] ?? '');
        $vaInfo = [
            'va_number' => null,
            'bank' => null,
            'display_name' => null
        ];

        // ===== BCA, BNI, BRI Virtual Account =====
        if ($paymentType === 'bank_transfer' && !empty($transactionData['va_numbers'])) {
            if (is_array($transactionData['va_numbers']) && count($transactionData['va_numbers']) > 0) {
                $va = $transactionData['va_numbers'][0];
                $vaInfo['va_number'] = $va['va_number'] ?? null;
                $vaInfo['bank'] = strtoupper($va['bank'] ?? '');
                $vaInfo['display_name'] = $vaInfo['bank'] . ' Virtual Account';
                
                log_message('info', "VA extracted: {$vaInfo['bank']} - {$vaInfo['va_number']}");
                return $vaInfo;
            }
        }

        // ===== Permata Virtual Account =====
        if (!empty($transactionData['permata_va_number'])) {
            $vaInfo['va_number'] = $transactionData['permata_va_number'];
            $vaInfo['bank'] = 'PERMATA';
            $vaInfo['display_name'] = 'Permata Virtual Account';
            
            log_message('info', "Permata VA extracted: {$vaInfo['va_number']}");
            return $vaInfo;
        }

        // ===== Mandiri Bill Payment (E-Channel) =====
        if ($paymentType === 'echannel') {
            if (!empty($transactionData['bill_key'])) {
                $vaInfo['va_number'] = $transactionData['bill_key'];
                $vaInfo['bank'] = 'MANDIRI';
                $vaInfo['display_name'] = 'Mandiri Bill Payment';
                
                // Add biller code as additional info
                if (!empty($transactionData['biller_code'])) {
                    $vaInfo['biller_code'] = $transactionData['biller_code'];
                }
                
                log_message('info', "Mandiri Bill extracted: {$vaInfo['va_number']}");
                return $vaInfo;
            }
        }

        // ===== QRIS =====
        if ($paymentType === 'qris' && !empty($transactionData['qr_string'])) {
            $vaInfo['va_number'] = 'QRIS';
            $vaInfo['bank'] = 'QRIS';
            $vaInfo['display_name'] = 'QRIS Payment';
            $vaInfo['qr_string'] = $transactionData['qr_string'];
            
            log_message('info', "QRIS extracted");
            return $vaInfo;
        }

        // ===== E-Wallets (GoPay, ShopeePay, DANA) =====
        if (in_array($paymentType, ['gopay', 'shopeepay', 'dana'])) {
            $vaInfo['va_number'] = 'E-Wallet';
            $vaInfo['bank'] = strtoupper($paymentType);
            $vaInfo['display_name'] = ucfirst($paymentType);
            
            // Add deeplink if available
            if (!empty($transactionData['actions'])) {
                foreach ($transactionData['actions'] as $action) {
                    if ($action['name'] === 'deeplink-redirect' || $action['name'] === 'generate-qr-code') {
                        $vaInfo['deeplink'] = $action['url'] ?? null;
                        break;
                    }
                }
            }
            
            log_message('info', ucfirst($paymentType) . " extracted");
            return $vaInfo;
        }

        // ===== Convenience Store (Indomaret, Alfamart) =====
        if ($paymentType === 'cstore') {
            $store = strtolower($transactionData['store'] ?? '');
            $vaInfo['va_number'] = $transactionData['payment_code'] ?? null;
            $vaInfo['bank'] = strtoupper($store);
            $vaInfo['display_name'] = ucfirst($store) . ' Payment';
            
            log_message('info', ucfirst($store) . " payment code extracted");
            return $vaInfo;
        }

        log_message('warning', "No VA info found for payment type: {$paymentType}");
        return null;
    }

    /**
     * ✅ NEW: Get formatted VA display
     */
    public function getFormattedVANumber($transactionData)
    {
        $vaInfo = $this->extractVirtualAccountInfo($transactionData);
        
        if (!$vaInfo || empty($vaInfo['va_number'])) {
            return 'Menunggu pembayaran';
        }

        // Format VA number dengan spasi setiap 4 digit untuk readability
        $vaNumber = $vaInfo['va_number'];
        if (strlen($vaNumber) > 8 && is_numeric($vaNumber)) {
            $vaNumber = implode(' ', str_split($vaNumber, 4));
        }

        return $vaNumber;
    }

    public function verifyNotification($notification)
    {
        $orderId      = $notification['order_id'] ?? '';
        $statusCode   = $notification['status_code'] ?? '';
        $grossAmount  = (string)($notification['gross_amount'] ?? '');
        $signatureKey = $notification['signature_key'] ?? '';

        if ($orderId === '' || $statusCode === '' || $grossAmount === '' || $signatureKey === '') {
            log_message('error', 'Missing signature components');
            return false;
        }

        $mySignature = hash('sha512', $orderId . $statusCode . $grossAmount . $this->serverKey);
        $isValid     = hash_equals($mySignature, $signatureKey);

        if (!$isValid) {
            log_message('error', 'Signature verification failed for: ' . $orderId);
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

    private function truncateString($string, $maxLength = 50)
    {
        $string = trim($string);
        if (mb_strlen($string) <= $maxLength) {
            return $string;
        }
        return mb_substr($string, 0, $maxLength - 3) . '...';
    }

    public function getAvailablePaymentMethods()
    {
        return [
            'bank_transfer' => [
                'bca_va' => [
                    'name' => 'BCA Virtual Account',
                    'icon' => 'building',
                    'color' => '#003087',
                    'enabled' => true
                ],
                'bni_va' => [
                    'name' => 'BNI Virtual Account',
                    'icon' => 'building',
                    'color' => '#ed7203',
                    'enabled' => true
                ],
                'bri_va' => [
                    'name' => 'BRI Virtual Account',
                    'icon' => 'building',
                    'color' => '#003d7a',
                    'enabled' => true
                ],
                'permata_va' => [
                    'name' => 'Permata Virtual Account',
                    'icon' => 'building',
                    'color' => '#00a854',
                    'enabled' => true
                ],
                'mandiri_va' => [
                    'name' => 'Mandiri Virtual Account',
                    'icon' => 'building',
                    'color' => '#003d79',
                    'enabled' => true
                ]
            ],
            'e_wallet' => [
                'gopay' => [
                    'name' => 'GoPay',
                    'icon' => 'wallet2',
                    'color' => '#00aa13',
                    'enabled' => true
                ],
                'shopeepay' => [
                    'name' => 'ShopeePay',
                    'icon' => 'wallet2',
                    'color' => '#ee4d2d',
                    'enabled' => true
                ],
                'dana' => [
                    'name' => 'DANA',
                    'icon' => 'wallet2',
                    'color' => '#118eea',
                    'enabled' => true
                ]
            ],
            'qris' => [
                'qris' => [
                    'name' => 'QRIS',
                    'icon' => 'qr-code',
                    'color' => '#d32f2f',
                    'enabled' => true
                ]
            ]
        ];
    }

    private function getEnabledPaymentCodes()
    {
        $methods = $this->getAvailablePaymentMethods();
        $enabled = [];

        foreach ($methods as $category => $categoryMethods) {
            foreach ($categoryMethods as $code => $details) {
                if ($details['enabled']) {
                    $enabled[] = $code;
                }
            }
        }

        return $enabled;
    }

    public function buildTransactionParams($orderId, $amount, $customerDetails, $itemDetails, $eventData = [])
    {
        $calculatedAmount = 0;
        foreach ($itemDetails as $item) {
            $itemPrice = (int)($item['price'] ?? 0);
            $itemQty = (int)($item['quantity'] ?? 1);
            $calculatedAmount += ($itemPrice * $itemQty);
        }

        $grossAmount = $calculatedAmount > 0 ? $calculatedAmount : (int)$amount;
        $baseUrl = rtrim(base_url(), '/') . '/';

        $params = [
            'transaction_details' => [
                'order_id'     => $orderId,
                'gross_amount' => $grossAmount,
            ],
            'customer_details' => [
                'first_name' => $this->truncateString($customerDetails['nama_lengkap'] ?? 'Customer', 50),
                'email'      => $customerDetails['email'] ?? '',
                'phone'      => $customerDetails['no_hp'] ?? '',
            ],
            'item_details' => $itemDetails,
            'enabled_payments' => $this->getEnabledPaymentCodes(),
            'callbacks' => [
                'finish' => $baseUrl . 'audience/pembayaran/finish?order_id=' . $orderId,
            ],
            'expiry' => [
                'start_time' => date('Y-m-d H:i:s O'),
                'unit'       => 'hours',
                'duration'   => 24,
            ],
        ];

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
                throw new \InvalidArgumentException('Invalid amount: ' . $amount);
            }

            if (empty($userDetails['nama_lengkap']) || empty($userDetails['email'])) {
                throw new \InvalidArgumentException('Customer details incomplete');
            }

            $orderId = $this->generateOrderId($userId, $eventId, $userRole, $participationType);

            $customerDetails = [
                'nama_lengkap' => $this->truncateString($userDetails['nama_lengkap'] ?? 'User', 50),
                'email'        => $userDetails['email'] ?? '',
                'no_hp'        => $userDetails['no_hp'] ?? '',
            ];

            $eventTitle = $this->truncateString($eventDetails['title'] ?? 'Event Registration', 50);
            $itemPrice = (int)$amount;
            
            $itemDetails = [[
                'id'       => 'EVENT-' . $eventId,
                'price'    => $itemPrice,
                'quantity' => 1,
                'name'     => $eventTitle,
            ]];

            $eventData = [
                'event_id'           => $eventId,
                'user_role'          => $userRole,
                'participation_type' => $participationType,
            ];

            $params = $this->buildTransactionParams($orderId, $amount, $customerDetails, $itemDetails, $eventData);
            $snapResponse = $this->createTransaction($params);

            if (!isset($snapResponse['token'])) {
                throw new \RuntimeException('No token received');
            }

            return [
                'order_id'            => $orderId,
                'snap_token'          => $snapResponse['token'],
                'redirect_url'        => $snapResponse['redirect_url'] ?? null,
                'transaction_details' => $params,
            ];

        } catch (\Exception $e) {
            log_message('error', 'Payment creation failed: ' . $e->getMessage());
            throw new \RuntimeException('Failed to create payment: ' . $e->getMessage());
        }
    }

    public function handleNotification($notification)
    {
        if (!$this->verifyNotification($notification)) {
            throw new \RuntimeException('Invalid signature');
        }

        $orderId           = $notification['order_id'];
        $transactionStatus = $notification['transaction_status'];
        $fraudStatus       = $notification['fraud_status'] ?? null;

        $paymentStatus = 'pending';

        switch (strtolower($transactionStatus)) {
            case 'capture':
                $paymentStatus = (strtolower($fraudStatus) === 'accept') ? 'verified' : 'pending';
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
            'payment_type'       => $notification['payment_type'] ?? null,
            'gross_amount'       => $notification['gross_amount'] ?? null,
            'transaction_time'   => $notification['transaction_time'] ?? null,
            'settlement_time'    => $notification['settlement_time'] ?? null,
            'raw_notification'   => $notification,
        ];
    }

    public function syncPaymentStatus($orderId)
    {
        try {
            $statusData = $this->getTransactionStatus($orderId);
            $transactionStatus = $statusData['transaction_status'] ?? '';
            $fraudStatus = $statusData['fraud_status'] ?? '';

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
            log_message('error', 'Payment sync error: ' . $e->getMessage());
            return [
                'success'  => false,
                'error'    => $e->getMessage(),
                'order_id' => $orderId,
            ];
        }
    }

    public function getPaymentMethodInfo($paymentType)
    {
        $methods = $this->getAvailablePaymentMethods();
        
        foreach ($methods as $category => $categoryMethods) {
            if (isset($categoryMethods[$paymentType])) {
                return $categoryMethods[$paymentType];
            }
        }

        return [
            'name' => 'Digital Payment',
            'icon' => 'credit-card',
            'color' => '#2563eb',
            'enabled' => true
        ];
    }
}