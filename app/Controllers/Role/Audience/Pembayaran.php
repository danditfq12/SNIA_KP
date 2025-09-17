<?php

namespace App\Controllers\Role\Audience;

use App\Controllers\BaseController;
use App\Models\EventModel;
use App\Models\EventRegistrationModel;
use App\Models\PembayaranModel;
use App\Models\VoucherModel;
use App\Models\UserModel;
use App\Services\MidtransService;
use App\Services\NotificationService;

class Pembayaran extends BaseController
{
    protected EventRegistrationModel $regM;
    protected PembayaranModel $payM;
    protected EventModel $eventM;
    protected VoucherModel $voucherM;
    protected UserModel $userM;
    protected MidtransService $midtrans;
    protected NotificationService $notificationService;

    public function __construct()
    {
        $this->regM   = new EventRegistrationModel();
        $this->payM   = new PembayaranModel();
        $this->eventM = new EventModel();
        $this->voucherM = new VoucherModel();
        $this->userM = new UserModel();
        $this->midtrans = new MidtransService();
        $this->notificationService = new NotificationService();
    }

    private function uid(): int { return (int) (session('id_user') ?? 0); }

    private function getPrice(array $event, string $mode): int
    {
        $prices = [
            'online'  => (int)($event['audience_fee_online'] ?? 0),
            'offline' => (int)($event['audience_fee_offline'] ?? 0),
        ];
        return max(0, $prices[$mode] ?? 0);
    }

    private function applyVoucherDiscount(int $basePrice, ?array $voucher): int
    {
        if (!$voucher) return $basePrice;

        $tipe = strtolower($voucher['tipe'] ?? '');
        $nilai = (int)($voucher['nilai'] ?? 0);

        if (in_array($tipe, ['percentage', 'persen'], true)) {
            $discount = min(100, $nilai);
            return max(0, $basePrice - (int)floor($basePrice * $discount / 100));
        }
        
        // fixed/nominal
        return max(0, $basePrice - $nilai);
    }

    private function findValidVoucher(string $code, int $userId, int $eventId): ?array
    {
        $code = trim($code);
        if ($code === '') return null;

        $voucher = $this->voucherM
            ->where('LOWER(kode_voucher) =', strtolower($code))
            ->where('status', 'aktif')
            ->first();

        if (!$voucher) return null;

        // Check expiry
        $exp = $voucher['masa_berlaku'] ?? null;
        if (!empty($exp)) {
            $endTs = strtotime(date('Y-m-d 23:59:59', strtotime($exp)));
            if ($endTs && $endTs < time()) return null;
        }

        // Check quota
        $kuota = (int)($voucher['kuota'] ?? 0);
        if ($kuota > 0) {
            $used = $this->payM
                ->where('id_voucher', $voucher['id_voucher'])
                ->whereIn('status', ['pending','verified'])
                ->countAllResults();
            if ($used >= $kuota) return null;
        }

        // Check if user already used this voucher for this event
        $existing = $this->payM
            ->where('id_user', $userId)
            ->where('event_id', $eventId)
            ->where('id_voucher', $voucher['id_voucher'])
            ->whereIn('status', ['pending','verified'])
            ->first();

        if ($existing) return null;

        return $voucher;
    }

    public function index()
    {
        $uid = $this->uid(); 
        if(!$uid) return redirect()->to('/auth/login');

        // Sync pending payments first (check Midtrans status)
        $this->syncPendingPayments($uid);

        $rows = $this->payM->select('id_pembayaran,event_id,jumlah,metode,status,tanggal_bayar,bukti_bayar,participation_type,payment_reference,midtrans_order_id,auto_verified,verified_at')
                ->where('id_user',$uid)->orderBy('tanggal_bayar','DESC')->findAll();

        // map event title
        $eventMap = [];
        if ($rows) {
            $ids = array_unique(array_column($rows,'event_id'));
            if (!empty($ids)) {
                $evs = $this->eventM->select('id,title')->whereIn('id',$ids)->findAll();
                foreach ($evs as $e) $eventMap[(int)$e['id']] = $e['title'];
            }
        }

        $badgeMap = [
            'pending' => 'warning',
            'verified' => 'success',
            'rejected' => 'danger',
            'canceled' => 'secondary',
            'expired' => 'dark'
        ];

        $aktif   = array_values(array_filter($rows, static fn($r)=> strtolower($r['status'] ?? '') === 'pending'));
        $riwayat = array_values(array_filter($rows, static fn($r)=> strtolower($r['status'] ?? '') !== 'pending'));

        return view('role/audience/pembayaran/index', [
            'title'    => 'Pembayaran',
            'payments' => $rows,
            'eventMap' => $eventMap,
            'badgeMap' => $badgeMap,
            'aktif'    => $aktif,
            'riwayat'  => $riwayat,
        ]);
    }

    /**
     * Sync pending payments dengan Midtrans untuk update status terbaru
     */
    private function syncPendingPayments($userId)
    {
        try {
            // Get all pending Midtrans payments for this user
            $pendingPayments = $this->payM
                ->where('id_user', $userId)
                ->where('status', 'pending')
                ->where('metode', 'midtrans')
                ->whereNotNull('midtrans_order_id')
                ->where('tanggal_bayar >', date('Y-m-d H:i:s', strtotime('-7 days'))) // Only check recent payments
                ->findAll();

            foreach ($pendingPayments as $payment) {
                $orderId = $payment['midtrans_order_id'];
                if (empty($orderId)) continue;

                try {
                    // Check status with Midtrans
                    $statusData = $this->midtrans->getTransactionStatus($orderId);
                    
                    $transactionStatus = strtolower($statusData['transaction_status'] ?? '');
                    $fraudStatus = strtolower($statusData['fraud_status'] ?? '');
                    
                    $newStatus = $this->mapMidtransStatus($transactionStatus, $fraudStatus);
                    
                    if ($newStatus !== 'pending') {
                        // Update payment status
                        $updateData = [
                            'status' => $newStatus,
                            'midtrans_raw_response' => json_encode($statusData),
                            'keterangan' => "Manual sync: {$transactionStatus}"
                        ];
                        
                        if ($newStatus === 'verified') {
                            $updateData['verified_at'] = $statusData['settlement_time'] ?? date('Y-m-d H:i:s');
                            $updateData['auto_verified'] = true;
                            $updateData['features_unlocked_at'] = $statusData['settlement_time'] ?? date('Y-m-d H:i:s');
                            
                            // Update registration status
                            $this->updateRegistrationStatus($payment, 'verified');
                            
                            // Reduce voucher quota
                            if (!empty($payment['id_voucher'])) {
                                $this->voucherM->reduceQuota($payment['id_voucher']);
                            }
                        }
                        
                        $this->payM->update($payment['id_pembayaran'], $updateData);
                        
                        log_message('info', "Manual sync updated payment {$payment['id_pembayaran']}: {$payment['status']} -> {$newStatus}");
                    }
                    
                } catch (\Exception $e) {
                    log_message('error', "Failed to sync payment {$orderId}: " . $e->getMessage());
                    continue;
                }
            }
            
        } catch (\Exception $e) {
            log_message('error', 'Sync pending payments error: ' . $e->getMessage());
        }
    }

    /**
     * Map Midtrans status to internal status
     */
    private function mapMidtransStatus($transactionStatus, $fraudStatus)
    {
        switch ($transactionStatus) {
            case 'settlement':
                return 'verified';
                
            case 'capture':
                if ($fraudStatus === 'accept' || empty($fraudStatus)) {
                    return 'verified';
                } elseif ($fraudStatus === 'challenge') {
                    return 'pending';
                } else {
                    return 'canceled';
                }
                
            case 'pending':
                return 'pending';
                
            case 'deny':
            case 'cancel':
            case 'failure':
                return 'canceled';
                
            case 'expire':
                return 'expired';
                
            default:
                return 'pending';
        }
    }

    /**
     * Update registration status based on payment status
     */
    private function updateRegistrationStatus($payment, $status)
    {
        try {
            $regStatus = 'menunggu_pembayaran';
            
            switch ($status) {
                case 'verified':
                    $regStatus = 'lunas';
                    break;
                case 'canceled':
                case 'expired':
                    $regStatus = 'batal';
                    break;
            }
            
            $registration = $this->regM
                ->where('id_user', $payment['id_user'])
                ->where('id_event', $payment['event_id'])
                ->first();

            if ($registration) {
                $updated = $this->regM->update($registration['id'], ['status' => $regStatus]);
                if ($updated) {
                    log_message('info', "Registration {$registration['id']} updated to: {$regStatus}");
                }
            }
        } catch (\Exception $e) {
            log_message('error', 'Registration update failed: ' . $e->getMessage());
        }
    }

    public function instruction(int $regId)
    {
        $uid = $this->uid(); 
        if(!$uid) return redirect()->to('/auth/login');

        $reg = $this->regM->find($regId);
        if (!$reg || (int)$reg['id_user'] !== $uid) {
            return redirect()->to('/audience/events')->with('error','Data tidak ditemukan.');
        }

        // Check if payment already exists
        $existingPayment = $this->payM
            ->where('id_user', $uid)
            ->where('event_id', (int)$reg['id_event'])
            ->whereIn('status', ['pending', 'verified'])
            ->first();

        if ($existingPayment) {
            return redirect()->to('/audience/pembayaran/detail/' . $existingPayment['id_pembayaran'])
                ->with('message', 'Anda sudah memiliki pembayaran untuk event ini.');
        }

        $pricing = $this->eventM->getPricingMatrix((int)$reg['id_event']);
        $mode    = $reg['mode_kehadiran'] ?? 'online';
        $amount  = (float)($pricing['audience'][$mode] ?? 0);

        $ev = $this->eventM->select('title,event_date,event_time')->find((int)$reg['id_event']);
        $user = $this->userM->find($uid);

        return view('role/audience/pembayaran/instruction', [
            'title'  => 'Instruksi Pembayaran',
            'reg'    => $reg,
            'amount' => $amount,
            'event'  => $ev,
            'user'   => $user,
            'midtrans_client_key' => $this->midtrans->getClientKey(),
            'is_production' => $this->midtrans->isProduction(),
        ]);
    }

    public function create(int $regId)
    {
        $uid = $this->uid(); 
        if(!$uid) return redirect()->to('/auth/login');

        $reg = $this->regM->find($regId);
        if (!$reg || (int)$reg['id_user'] !== $uid) {
            return redirect()->to('/audience/events')->with('error','Data tidak ditemukan.');
        }
        if (($reg['status'] ?? '') !== 'menunggu_pembayaran') {
            return redirect()->to('/audience/events/detail/'.$reg['id_event'])->with('warning','Status pendaftaran bukan menunggu pembayaran.');
        }

        $pricing = $this->eventM->getPricingMatrix((int)$reg['id_event']);
        $mode    = $reg['mode_kehadiran'] ?? 'online';
        $amount  = (float)($pricing['audience'][$mode] ?? 0);

        $ev = $this->eventM->select('title,event_date,event_time,format')->find((int)$reg['id_event']);
        $user = $this->userM->find($uid);
        
        $reg['event_title'] = $ev['title'] ?? '-';
        $reg['event_date']  = $ev['event_date'] ?? null;
        $reg['event_time']  = $ev['event_time'] ?? null;

        return view('role/audience/pembayaran/create', [
            'title'  => 'Pilihan Pembayaran',
            'reg'    => $reg,
            'amount' => $amount,
            'event'  => $ev,
            'user'   => $user,
            'midtrans_client_key' => $this->midtrans->getClientKey(),
            'is_production' => $this->midtrans->isProduction(),
        ]);
    }

    /** AJAX: Validate voucher */
    public function validateVoucher()
    {
        $uid = $this->uid();
        $eventId = (int) ($this->request->getPost('event_id') ?? $this->request->getGet('event_id'));
        $code = (string) ($this->request->getPost('kode_voucher') ?? $this->request->getGet('kode_voucher'));
        $mode = (string) ($this->request->getPost('mode') ?? $this->request->getGet('mode'));

        if (!$eventId || !$mode) {
            return $this->response->setJSON(['ok'=>false,'message'=>'Parameter tidak lengkap','token'=>csrf_hash()]);
        }

        $event = $this->eventM->find($eventId);
        if (!$event) {
            return $this->response->setJSON(['ok'=>false,'message'=>'Event tidak ditemukan','token'=>csrf_hash()]);
        }

        $basePrice = $this->getPrice($event, $mode);
        $voucher = $this->findValidVoucher($code, $uid, $eventId);

        if (!$voucher) {
            return $this->response->setJSON([
                'ok' => false,
                'message' => 'Voucher tidak valid/kedaluwarsa/habis kuota',
                'token' => csrf_hash(),
            ]);
        }

        $finalPrice = $this->applyVoucherDiscount($basePrice, $voucher);

        return $this->response->setJSON([
            'ok' => true,
            'message' => 'Voucher berhasil diterapkan',
            'final_price' => $finalPrice,
            'voucher_id' => (int)$voucher['id_voucher'],
            'code' => $voucher['kode_voucher'],
            'token' => csrf_hash(),
        ]);
    }

    /** Process payment (Midtrans only - removed manual payment) */
    public function processPayment()
    {
        if (!$this->request->is('post')) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request method']);
        }

        $uid = $this->uid();
        if(!$uid) return $this->response->setJSON(['success' => false, 'message' => 'Please login']);

        $regId = (int) $this->request->getPost('reg_id');
        $paymentMethod = (string) $this->request->getPost('payment_method');
        $voucherCode = trim((string) $this->request->getPost('voucher_code'));

        // Force to use Midtrans only
        if ($paymentMethod !== 'midtrans') {
            return $this->response->setJSON(['success' => false, 'message' => 'Hanya pembayaran digital yang tersedia']);
        }

        $reg = $this->regM->find($regId);
        if (!$reg || (int)$reg['id_user'] !== $uid) {
            return $this->response->setJSON(['success' => false, 'message' => 'Registration not found']);
        }

        $event = $this->eventM->find((int)$reg['id_event']);
        if (!$event) {
            return $this->response->setJSON(['success' => false, 'message' => 'Event not found']);
        }

        $user = $this->userM->find($uid);
        $mode = $reg['mode_kehadiran'] ?? 'online';
        $basePrice = $this->getPrice($event, $mode);
        $finalPrice = $basePrice;
        $voucherId = null;

        // Apply voucher if provided
        if ($voucherCode !== '') {
            $voucher = $this->findValidVoucher($voucherCode, $uid, (int)$reg['id_event']);
            if ($voucher) {
                $finalPrice = $this->applyVoucherDiscount($basePrice, $voucher);
                $voucherId = (int)$voucher['id_voucher'];
            }
        }

        try {
            // Create Midtrans payment
            $userDetails = [
                'nama_lengkap' => $user['nama_lengkap'] ?? 'User',
                'email' => $user['email'] ?? '',
                'no_hp' => $user['no_hp'] ?? '',
            ];

            $eventDetails = [
                'title' => $event['title'] ?? 'Event Registration'
            ];

            $midtransResponse = $this->midtrans->createEventPayment(
                (int)$reg['id_event'],
                $uid,
                'audience',
                $mode,
                $finalPrice,
                $userDetails,
                $eventDetails
            );

            // Save payment record using createMidtransPayment
            $paymentData = [
                'id_user'            => $uid,
                'event_id'           => (int)$reg['id_event'],
                'jumlah'             => $finalPrice,
                'participation_type' => $mode,
                'midtrans_order_id'  => $midtransResponse['order_id'],
                'midtrans_snap_token'=> $midtransResponse['snap_token'],
                'id_voucher'         => $voucherId,
                'original_amount'    => $basePrice,
                'discount_amount'    => $basePrice - $finalPrice,
            ];

            $this->payM->createMidtransPayment($paymentData);
            $paymentId = $this->payM->getInsertID();

            // Send notification
            try {
                $this->notificationService->notify(
                    $uid,
                    'payment',
                    'Pembayaran Dibuat',
                    "Pembayaran untuk event \"{$event['title']}\" telah dibuat. Silakan selesaikan pembayaran.",
                    site_url('audience/pembayaran/detail/' . $paymentId)
                );
            } catch (\Exception $e) {
                log_message('warning', 'Failed to send notification: ' . $e->getMessage());
            }

            return $this->response->setJSON([
                'success' => true,
                'payment_method' => 'midtrans',
                'snap_token' => $midtransResponse['snap_token'],
                'order_id' => $midtransResponse['order_id'],
                'payment_id' => $paymentId,
                'redirect_url' => site_url('audience/pembayaran/detail/' . $paymentId)
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Payment creation error: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    /** Payment finish callback from Midtrans - ENHANCED VERSION */
    public function finish()
    {
        $orderId = $this->request->getGet('order_id');
        $status = $this->request->getGet('status'); // success, pending, error
        
        if (!$orderId) {
            return redirect()->to('/audience/pembayaran')->with('error', 'Parameter pembayaran tidak valid');
        }

        // Find payment by order_id
        $payment = $this->payM->getByMidtransOrderId($orderId);
        if (!$payment) {
            return redirect()->to('/audience/pembayaran')->with('error', 'Data pembayaran tidak ditemukan');
        }

        // Ensure this payment belongs to current user
        if ((int)$payment['id_user'] !== $this->uid()) {
            return redirect()->to('/audience/pembayaran')->with('error', 'Akses tidak diizinkan');
        }

        try {
            // Check latest status from Midtrans
            $statusData = $this->midtrans->getTransactionStatus($orderId);
            
            if (isset($statusData['transaction_status'])) {
                $transactionStatus = strtolower($statusData['transaction_status']);
                $fraudStatus = strtolower($statusData['fraud_status'] ?? '');
                
                $newStatus = $this->mapMidtransStatus($transactionStatus, $fraudStatus);
                
                // Update payment if status changed
                if ($newStatus !== $payment['status']) {
                    $updateData = [
                        'status' => $newStatus,
                        'midtrans_raw_response' => json_encode($statusData),
                        'keterangan' => "Finish callback: {$transactionStatus}"
                    ];
                    
                    if ($newStatus === 'verified') {
                        $updateData['verified_at'] = $statusData['settlement_time'] ?? date('Y-m-d H:i:s');
                        $updateData['auto_verified'] = true;
                        $updateData['features_unlocked_at'] = $statusData['settlement_time'] ?? date('Y-m-d H:i:s');
                        
                        // Update registration status
                        $this->updateRegistrationStatus($payment, 'verified');
                        
                        // Reduce voucher quota
                        if (!empty($payment['id_voucher'])) {
                            $this->voucherM->reduceQuota($payment['id_voucher']);
                        }
                        
                        $message = 'Pembayaran Anda telah berhasil dan diverifikasi secara otomatis!';
                        $alertType = 'success';
                        
                    } elseif ($newStatus === 'pending') {
                        $message = 'Pembayaran sedang diproses. Status akan diperbarui otomatis.';
                        $alertType = 'info';
                        
                    } elseif (in_array($newStatus, ['canceled', 'expired'])) {
                        $message = 'Pembayaran dibatalkan atau gagal. Anda dapat mencoba lagi.';
                        $alertType = 'warning';
                        
                    } else {
                        $message = 'Status pembayaran: ' . ucfirst($newStatus);
                        $alertType = 'info';
                    }
                    
                    $this->payM->update($payment['id_pembayaran'], $updateData);
                    
                } else {
                    // Status unchanged
                    $message = match($payment['status']) {
                        'verified' => 'Pembayaran Anda telah berhasil diverifikasi!',
                        'pending' => 'Pembayaran sedang diproses. Mohon tunggu konfirmasi.',
                        'canceled' => 'Pembayaran dibatalkan. Anda dapat mencoba lagi.',
                        'expired' => 'Pembayaran kedaluwarsa. Silakan lakukan pembayaran baru.',
                        default => 'Status pembayaran telah diperbarui.'
                    };
                    
                    $alertType = match($payment['status']) {
                        'verified' => 'success',
                        'pending' => 'info',
                        'canceled', 'expired' => 'warning',
                        default => 'info'
                    };
                }
            } else {
                // Fallback if can't get status
                $message = 'Pembayaran sedang diproses. Status akan diperbarui secara otomatis.';
                $alertType = 'info';
            }

        } catch (\Exception $e) {
            log_message('error', 'Payment finish callback error: ' . $e->getMessage());
            $message = 'Pembayaran telah dibuat. Mohon periksa status pembayaran Anda.';
            $alertType = 'info';
        }

        return redirect()->to('/audience/pembayaran/detail/' . $payment['id_pembayaran'])
            ->with($alertType, $message);
    }

    /** Check payment status manually */
    public function checkStatus($paymentId)
    {
        $uid = $this->uid();
        if (!$uid) return redirect()->to('/auth/login');

        $payment = $this->payM->find($paymentId);
        if (!$payment || (int)$payment['id_user'] !== $uid) {
            return redirect()->to('/audience/pembayaran')->with('error', 'Data tidak ditemukan');
        }

        if (empty($payment['midtrans_order_id'])) {
            return redirect()->to('/audience/pembayaran/detail/' . $paymentId)
                ->with('warning', 'Pembayaran ini bukan dari Midtrans');
        }

        try {
            $statusData = $this->midtrans->getTransactionStatus($payment['midtrans_order_id']);
            
            $transactionStatus = strtolower($statusData['transaction_status'] ?? '');
            $newStatus = $this->mapMidtransStatus($transactionStatus, $statusData['fraud_status'] ?? '');
            
            if ($newStatus !== $payment['status']) {
                $updateData = [
                    'status' => $newStatus,
                    'midtrans_raw_response' => json_encode($statusData),
                    'keterangan' => "Manual check: {$transactionStatus}"
                ];
                
                if ($newStatus === 'verified') {
                    $updateData['verified_at'] = $statusData['settlement_time'] ?? date('Y-m-d H:i:s');
                    $updateData['auto_verified'] = true;
                    $updateData['features_unlocked_at'] = $statusData['settlement_time'] ?? date('Y-m-d H:i:s');
                    
                    $this->updateRegistrationStatus($payment, 'verified');
                    
                    if (!empty($payment['id_voucher'])) {
                        $this->voucherM->reduceQuota($payment['id_voucher']);
                    }
                    
                    $message = 'Status berhasil diperbarui! Pembayaran telah terverifikasi.';
                    $alertType = 'success';
                    
                } elseif ($newStatus === 'pending') {
                    $message = 'Status masih pending. Pembayaran sedang diproses.';
                    $alertType = 'info';
                    
                } else {
                    $message = "Status diperbarui menjadi: " . ucfirst($newStatus);
                    $alertType = 'warning';
                }
                
                $this->payM->update($paymentId, $updateData);
                
            } else {
                $message = 'Status pembayaran sudah up-to-date: ' . ucfirst($payment['status']);
                $alertType = 'info';
            }

        } catch (\Exception $e) {
            log_message('error', 'Manual status check error: ' . $e->getMessage());
            $message = 'Gagal memeriksa status. Coba lagi nanti.';
            $alertType = 'error';
        }

        return redirect()->to('/audience/pembayaran/detail/' . $paymentId)
            ->with($alertType, $message);
    }

    /** detail pembayaran saya */
    public function detail(int $id)
    {
        $uid = $this->uid(); 
        if(!$uid) return redirect()->to('/auth/login');

        $row = $this->payM->find($id);
        if (!$row || (int)$row['id_user'] !== $uid) {
            return redirect()->to('/audience/pembayaran')->with('error','Data tidak ditemukan.');
        }

        // Sync status if pending Midtrans payment
        if ($row['status'] === 'pending' && $row['metode'] === 'midtrans' && !empty($row['midtrans_order_id'])) {
            $this->syncSinglePayment($row);
            // Refresh data after sync
            $row = $this->payM->find($id);
        }

        $ev = $this->eventM->select('id,title,event_date,event_time')->find((int)$row['event_id']);
        $voucher = !empty($row['id_voucher']) ? $this->voucherM->find($row['id_voucher']) : null;

        return view('role/audience/pembayaran/detail', [
            'title' => 'Detail Pembayaran',
            'pay'   => $row,
            'event' => $ev,
            'voucher' => $voucher,
        ]);
    }

    /**
     * Sync single payment with Midtrans
     */
    private function syncSinglePayment($payment)
    {
        try {
            if (empty($payment['midtrans_order_id'])) return;

            $statusData = $this->midtrans->getTransactionStatus($payment['midtrans_order_id']);
            $transactionStatus = strtolower($statusData['transaction_status'] ?? '');
            $newStatus = $this->mapMidtransStatus($transactionStatus, $statusData['fraud_status'] ?? '');
            
            if ($newStatus !== $payment['status']) {
                $updateData = [
                    'status' => $newStatus,
                    'midtrans_raw_response' => json_encode($statusData),
                    'keterangan' => "Auto sync: {$transactionStatus}"
                ];
                
                if ($newStatus === 'verified') {
                    $updateData['verified_at'] = $statusData['settlement_time'] ?? date('Y-m-d H:i:s');
                    $updateData['auto_verified'] = true;
                    $updateData['features_unlocked_at'] = $statusData['settlement_time'] ?? date('Y-m-d H:i:s');
                    
                    $this->updateRegistrationStatus($payment, 'verified');
                    
                    if (!empty($payment['id_voucher'])) {
                        $this->voucherM->reduceQuota($payment['id_voucher']);
                    }
                }
                
                $this->payM->update($payment['id_pembayaran'], $updateData);
            }
            
        } catch (\Exception $e) {
            log_message('error', 'Single payment sync error: ' . $e->getMessage());
        }
    }

    public function cancel(int $paymentId)
    {
        $uid = $this->uid(); 
        if(!$uid) return redirect()->to('/auth/login');

        $pay = $this->payM->find($paymentId);
        if (!$pay || (int)$pay['id_user'] !== $uid) {
            return redirect()->to('/audience/pembayaran')->with('error','Data tidak ditemukan.');
        }
        if ($pay['status'] !== 'pending') {
            return redirect()->to('/audience/pembayaran/detail/'.$paymentId)->with('warning','Pembayaran tidak dapat dibatalkan.');
        }

        $this->payM->update($paymentId, ['status' => 'canceled', 'keterangan' => 'Dibatalkan oleh user']);

        $reg = $this->regM->where('id_user',$uid)->where('id_event',(int)$pay['event_id'])->first();
        if ($reg && $reg['status']==='menunggu_pembayaran') {
            $this->regM->update((int)$reg['id'], ['status' => 'batal']);
        }

        return redirect()->to('/audience/events')->with('message','Pendaftaran dibatalkan.');
    }
}