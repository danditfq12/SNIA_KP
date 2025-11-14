<?php

namespace App\Controllers\Role\Audience;

use App\Controllers\BaseController;
use App\Models\EventModel;
use App\Models\EventRegistrationModel;
use App\Models\PembayaranModel;
use App\Models\UserModel;
use App\Services\MidtransService;
use App\Services\NotificationService;

class Pembayaran extends BaseController
{
    protected EventRegistrationModel $regM;
    protected PembayaranModel $payM;
    protected EventModel $eventM;
    protected UserModel $userM;
    protected MidtransService $midtrans;
    protected NotificationService $notificationService;

    public function __construct()
    {
        $this->regM   = new EventRegistrationModel();
        $this->payM   = new PembayaranModel();
        $this->eventM = new EventModel();
        $this->userM = new UserModel();
        $this->midtrans = new MidtransService();
        $this->notificationService = new NotificationService();
    }

    private function uid(): int { return (int) (session('id_user') ?? 0); }

    /**
     * FIXED: Get price with proper fallback
     */
    private function getPrice(int $eventId, string $mode, string $userRole = 'audience'): int
    {
        try {
            // Method 1: Try getCurrentWave first
            $wave = $this->eventM->getCurrentWave($eventId);
            
            if ($wave && is_array($wave)) {
                $priceKey = strtolower($userRole) . '_fee_' . strtolower($mode);
                $price = (int)($wave[$priceKey] ?? 0);
                
                if ($price > 0) {
                    log_message('info', "Price from wave: {$price} for {$priceKey}");
                    return $price;
                }
            }

            // Method 2: Try getPricingMatrix
            $pricing = $this->eventM->getPricingMatrix($eventId);
            if (!empty($pricing[$userRole][$mode])) {
                $price = (int)$pricing[$userRole][$mode];
                log_message('info', "Price from matrix: {$price}");
                return $price;
            }

            // Method 3: Try getEventPrice
            $price = (int)$this->eventM->getEventPrice($eventId, $userRole, $mode);
            if ($price > 0) {
                log_message('info', "Price from getEventPrice: {$price}");
                return $price;
            }

            // Method 4: Check registration record
            $reg = $this->regM->where('id_event', $eventId)
                             ->where('id_user', $this->uid())
                             ->orderBy('id', 'DESC')
                             ->first();
            
            if ($reg && !empty($reg['jumlah_bayar'])) {
                $price = (int)$reg['jumlah_bayar'];
                log_message('info', "Price from registration: {$price}");
                return $price;
            }

            log_message('error', "No valid price found for event {$eventId}, mode {$mode}, role {$userRole}");
            return 0;

        } catch (\Exception $e) {
            log_message('error', 'Error getting price: ' . $e->getMessage());
            return 0;
        }
    }

    public function index()
    {
        $uid = $this->uid(); 
        if(!$uid) return redirect()->to('/auth/login');

        $this->syncPendingPayments($uid);

        $rows = $this->payM->select('id_pembayaran,event_id,jumlah,metode,status,tanggal_bayar,participation_type,midtrans_order_id,auto_verified,verified_at,midtrans_payment_type')
                ->where('id_user',$uid)->orderBy('tanggal_bayar','DESC')->findAll();

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

    private function syncPendingPayments($userId)
    {
        try {
            $pendingPayments = $this->payM
                ->where('id_user', $userId)
                ->where('status', 'pending')
                ->where('metode', 'midtrans')
                ->whereNotNull('midtrans_order_id')
                ->where('tanggal_bayar >', date('Y-m-d H:i:s', strtotime('-7 days')))
                ->findAll();

            foreach ($pendingPayments as $payment) {
                $orderId = $payment['midtrans_order_id'];
                if (empty($orderId)) continue;

                try {
                    $statusData = $this->midtrans->getTransactionStatus($orderId);
                    
                    $transactionStatus = strtolower($statusData['transaction_status'] ?? '');
                    $fraudStatus = strtolower($statusData['fraud_status'] ?? '');
                    
                    $newStatus = $this->mapMidtransStatus($transactionStatus, $fraudStatus);
                    
                    if ($newStatus !== 'pending') {
                        $updateData = [
                            'status' => $newStatus,
                            'midtrans_raw_response' => json_encode($statusData),
                            'keterangan' => "Manual sync: {$transactionStatus}"
                        ];
                        
                        if ($newStatus === 'verified') {
                            $updateData['verified_at'] = $statusData['settlement_time'] ?? date('Y-m-d H:i:s');
                            $updateData['auto_verified'] = true;
                            $updateData['features_unlocked_at'] = $statusData['settlement_time'] ?? date('Y-m-d H:i:s');
                            
                            $this->updateRegistrationStatus($payment, 'verified');
                        }
                        
                        $this->payM->update($payment['id_pembayaran'], $updateData);
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
                $this->regM->update($registration['id'], ['status' => $regStatus]);
            }
        } catch (\Exception $e) {
            log_message('error', 'Registration update failed: ' . $e->getMessage());
        }
    }

    /**
     * FIXED: instruction() dengan info gelombang
     */
    public function instruction($param = null)
    {
        $uid = $this->uid(); 
        if(!$uid) return redirect()->to('/auth/login');

        // Deteksi apakah parameter adalah regId atau eventId
        $reg = null;
        
        if (is_numeric($param)) {
            $reg = $this->regM->find((int)$param);
            
            if (!$reg || (int)$reg['id_user'] !== $uid) {
                $reg = $this->regM
                    ->where('id_event', (int)$param)
                    ->where('id_user', $uid)
                    ->whereNotIn('status', ['batal', 'ditolak'])
                    ->orderBy('id', 'DESC')
                    ->first();
            }
        }

        if (!$reg || (int)$reg['id_user'] !== $uid) {
            return redirect()->to('/audience/events')->with('error','Data registrasi tidak ditemukan.');
        }

        $eventId = (int)$reg['id_event'];

        // CEK PEMBAYARAN PENDING
        $existingPayment = $this->payM
            ->where('id_user', $uid)
            ->where('event_id', $eventId)
            ->whereIn('status', ['pending', 'verified'])
            ->orderBy('tanggal_bayar', 'DESC')
            ->first();

        if ($existingPayment) {
            if ($existingPayment['status'] === 'verified') {
                return redirect()->to('/audience/events/detail/' . $eventId)
                    ->with('success', 'Pembayaran Anda sudah terverifikasi!');
            }
            
            $snapToken = $existingPayment['midtrans_snap_token'] ?? null;
            $orderId = $existingPayment['midtrans_order_id'] ?? null;
            $amount = (float)($existingPayment['jumlah'] ?? 0);

            $ev = $this->eventM->find($eventId);
            $user = $this->userM->find($uid);

            // ✅ GET WAVE INFO
            $currentWave = $this->eventM->getCurrentWave($eventId);

            return view('role/audience/pembayaran/instruction', [
                'title'  => 'Lanjutkan Pembayaran',
                'reg'    => $reg,
                'amount' => $amount,
                'event'  => $ev,
                'user'   => $user,
                'existing_payment' => $existingPayment,
                'snap_token' => $snapToken,
                'order_id' => $orderId,
                'current_wave' => $currentWave,
                'midtrans_client_key' => $this->midtrans->getClientKey(),
                'is_production' => $this->midtrans->isProduction(),
            ]);
        }

        // FIXED: Get amount dengan multiple fallback
        $mode = $reg['mode_kehadiran'] ?? 'online';
        $userRole = session()->get('role') ?? 'audience';
        
        // ✅ GET WAVE INFO
        $currentWave = $this->eventM->getCurrentWave($eventId);
        
        // Try to get amount from multiple sources
        $amount = $this->getPrice($eventId, $mode, $userRole);
        
        // Fallback: check from registration record
        if ($amount <= 0 && !empty($reg['jumlah_bayar'])) {
            $amount = (float)$reg['jumlah_bayar'];
            log_message('info', "Using amount from registration record: {$amount}");
        }

        // Last fallback: default prices
        if ($amount <= 0) {
            log_message('warning', "No amount found, using default fallback");
            $amount = ($mode === 'online') ? 100000 : 150000;
        }

        $ev = $this->eventM->find($eventId);
        $user = $this->userM->find($uid);

        return view('role/audience/pembayaran/instruction', [
            'title'  => 'Instruksi Pembayaran',
            'reg'    => $reg,
            'amount' => $amount,
            'event'  => $ev,
            'user'   => $user,
            'current_wave' => $currentWave,
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

        $eventId = (int)$reg['id_event'];

        // CEK PEMBAYARAN PENDING
        $existingPayment = $this->payM
            ->where('id_user', $uid)
            ->where('event_id', $eventId)
            ->whereIn('status', ['pending', 'verified'])
            ->orderBy('tanggal_bayar', 'DESC')
            ->first();

        if ($existingPayment) {
            return redirect()->to('/audience/pembayaran/detail/' . $existingPayment['id_pembayaran'])
                ->with('warning', 'Anda memiliki pembayaran yang sedang diproses.');
        }

        // FIXED: Get amount dengan better validation
        $mode = $reg['mode_kehadiran'] ?? 'online';
        $userRole = session()->get('role') ?? 'audience';
        
        $amount = $this->getPrice($eventId, $mode, $userRole);
        
        // Fallback dari registration
        if ($amount <= 0 && !empty($reg['jumlah_bayar'])) {
            $amount = (float)$reg['jumlah_bayar'];
        }

        // Last fallback
        if ($amount <= 0) {
            $amount = ($mode === 'online') ? 100000 : 150000;
        }

        $ev = $this->eventM->find($eventId);
        $user = $this->userM->find($uid);
        
        // ✅ GET WAVE INFO
        $currentWave = $this->eventM->getCurrentWave($eventId);
        
        $reg['event_title'] = $ev['title'] ?? '-';
        $reg['event_date']  = $ev['event_date'] ?? null;
        $reg['event_time']  = $ev['event_time'] ?? null;

        return view('role/audience/pembayaran/create', [
            'title'  => 'Pilihan Pembayaran',
            'reg'    => $reg,
            'amount' => $amount,
            'event'  => $ev,
            'user'   => $user,
            'current_wave' => $currentWave,
            'midtrans_client_key' => $this->midtrans->getClientKey(),
            'is_production' => $this->midtrans->isProduction(),
        ]);
    }

    /**
     * ✅ FIXED: processPayment TANPA id_registrasi
     */
    public function processPayment()
    {
        if (!$this->request->is('post')) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request method']);
        }

        $uid = $this->uid();
        if(!$uid) return $this->response->setJSON(['success' => false, 'message' => 'Please login']);

        $regId = (int) $this->request->getPost('reg_id');
        $paymentMethod = (string) $this->request->getPost('payment_method');

        if ($paymentMethod !== 'midtrans') {
            return $this->response->setJSON(['success' => false, 'message' => 'Hanya pembayaran digital yang tersedia']);
        }

        $reg = $this->regM->find($regId);
        if (!$reg || (int)$reg['id_user'] !== $uid) {
            return $this->response->setJSON(['success' => false, 'message' => 'Registration not found']);
        }

        $eventId = (int)$reg['id_event'];

        // CEK PEMBAYARAN EXISTING
        $existingPayment = $this->payM
            ->where('id_user', $uid)
            ->where('event_id', $eventId)
            ->whereIn('status', ['pending', 'verified'])
            ->orderBy('tanggal_bayar', 'DESC')
            ->first();

        if ($existingPayment) {
            return $this->response->setJSON([
                'success' => false, 
                'message' => 'Anda sudah memiliki pembayaran untuk event ini.',
                'redirect_url' => site_url('audience/pembayaran/detail/' . $existingPayment['id_pembayaran'])
            ]);
        }

        $event = $this->eventM->find($eventId);
        if (!$event) {
            return $this->response->setJSON(['success' => false, 'message' => 'Event not found']);
        }

        $user = $this->userM->find($uid);
        $mode = $reg['mode_kehadiran'] ?? 'online';
        $userRole = session()->get('role') ?? 'audience';

        // FIXED: Get amount dengan multiple fallback
        $amount = $this->getPrice($eventId, $mode, $userRole);
        
        if ($amount <= 0 && !empty($reg['jumlah_bayar'])) {
            $amount = (float)$reg['jumlah_bayar'];
        }

        if ($amount <= 0) {
            $amount = ($mode === 'online') ? 100000 : 150000;
        }

        // VALIDATION: Amount must be > 0
        if ($amount <= 0) {
            log_message('error', "Invalid amount for event {$eventId}, mode {$mode}: {$amount}");
            return $this->response->setJSON([
                'success' => false, 
                'message' => 'Harga event tidak valid. Silakan hubungi admin.'
            ]);
        }

        try {
            $userDetails = [
                'nama_lengkap' => $user['nama_lengkap'] ?? 'User',
                'email' => $user['email'] ?? '',
                'no_hp' => $user['no_hp'] ?? '',
            ];

            $eventDetails = [
                'title' => $event['title'] ?? 'Event Registration'
            ];

            log_message('info', "Creating Midtrans payment - Amount: {$amount}, Mode: {$mode}, Event: {$eventId}");

            $midtransResponse = $this->midtrans->createEventPayment(
                $eventId,
                $uid,
                $userRole,
                $mode,
                $amount,
                $userDetails,
                $eventDetails
            );

            // ✅ HAPUS id_registrasi DARI SINI
            $paymentData = [
                'id_user'            => $uid,
                'event_id'           => $eventId,
                // 'id_registrasi'   => $regId,  // ❌ DIHAPUS
                'jumlah'             => $amount,
                'participation_type' => $mode,
                'midtrans_order_id'  => $midtransResponse['order_id'],
                'midtrans_snap_token'=> $midtransResponse['snap_token'],
                'id_voucher'         => null,
                'original_amount'    => $amount,
                'discount_amount'    => 0,
            ];

            $this->payM->createMidtransPayment($paymentData);
            $paymentId = $this->payM->getInsertID();

            log_message('info', "Payment created successfully - ID: {$paymentId}, Amount: {$amount}");

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

    public function finish()
    {
        $orderId = $this->request->getGet('order_id');
        
        if (!$orderId) {
            return redirect()->to('/audience/pembayaran')->with('error', 'Parameter pembayaran tidak valid');
        }

        $payment = $this->payM->getByMidtransOrderId($orderId);
        if (!$payment) {
            return redirect()->to('/audience/pembayaran')->with('error', 'Data pembayaran tidak ditemukan');
        }

        if ((int)$payment['id_user'] !== $this->uid()) {
            return redirect()->to('/audience/pembayaran')->with('error', 'Akses tidak diizinkan');
        }

        try {
            $statusData = $this->midtrans->getTransactionStatus($orderId);
            
            if (isset($statusData['transaction_status'])) {
                $transactionStatus = strtolower($statusData['transaction_status']);
                $fraudStatus = strtolower($statusData['fraud_status'] ?? '');
                
                $newStatus = $this->mapMidtransStatus($transactionStatus, $fraudStatus);
                
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
                        
                        $this->updateRegistrationStatus($payment, 'verified');
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

    public function detail(int $id)
    {
        $uid = $this->uid(); 
        if(!$uid) return redirect()->to('/auth/login');

        $row = $this->payM->find($id);
        if (!$row || (int)$row['id_user'] !== $uid) {
            return redirect()->to('/audience/pembayaran')->with('error','Data tidak ditemukan.');
        }

        if ($row['status'] === 'pending' && $row['metode'] === 'midtrans' && !empty($row['midtrans_order_id'])) {
            $this->syncSinglePayment($row);
            $row = $this->payM->find($id);
        }

        $ev = $this->eventM->find((int)$row['event_id']);

        // ✅ Cari registrasi berdasarkan event_id dan user_id (TANPA id_registrasi)
        $reg = $this->regM
            ->where('id_event', (int)$row['event_id'])
            ->where('id_user', $uid)
            ->whereNotIn('status', ['batal', 'ditolak'])
            ->orderBy('id', 'DESC')
            ->first();

        return view('role/audience/pembayaran/detail', [
            'title' => 'Detail Pembayaran',
            'pay'   => $row,
            'event' => $ev,
            'voucher' => null,
            'reg'   => $reg,
        ]);
    }

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