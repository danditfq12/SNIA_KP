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

    public function index()
    {
        $uid = $this->uid(); 
        if(!$uid) return redirect()->to('/auth/login');

        // Sync pending payments sebelum menampilkan
        $this->syncPendingPayments($uid);

        // Get all payments dengan info lengkap
        $rows = $this->payM
            ->select('pembayaran.*, events.title as event_title')
            ->join('events', 'events.id = pembayaran.event_id', 'left')
            ->where('pembayaran.id_user', $uid)
            ->orderBy('pembayaran.tanggal_bayar', 'DESC')
            ->findAll();

        $badgeMap = [
            'pending' => 'warning',
            'verified' => 'success',
            'rejected' => 'danger',
            'canceled' => 'secondary',
            'expired' => 'dark'
        ];

        // Pisahkan aktif (pending) dan riwayat
        $aktif   = array_filter($rows, fn($r) => strtolower($r['status'] ?? '') === 'pending');
        $riwayat = array_filter($rows, fn($r) => strtolower($r['status'] ?? '') !== 'pending');

        return view('role/audience/pembayaran/index', [
            'title'    => 'Pembayaran Saya',
            'payments' => $rows,
            'badgeMap' => $badgeMap,
            'aktif'    => array_values($aktif),
            'riwayat'  => array_values($riwayat),
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
                        $this->payM->updateFromMidtransNotification($orderId, $statusData);
                        
                        if ($newStatus === 'verified') {
                            $this->updateRegistrationStatus($payment, 'verified');
                        }
                        
                        log_message('info', "Synced payment {$payment['id_pembayaran']}: {$payment['status']} -> {$newStatus}");
                    }
                } catch (\Exception $e) {
                    log_message('error', "Failed to sync payment {$orderId}: " . $e->getMessage());
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
                return ($fraudStatus === 'accept' || empty($fraudStatus)) ? 'verified' : 'pending';
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
            $regStatus = match($status) {
                'verified' => 'lunas',
                'canceled', 'expired' => 'batal',
                default => 'menunggu_pembayaran'
            };
            
            // Cari registrasi berdasarkan id_registrasi atau event+user
            $registration = null;
            
            if (!empty($payment['id_registrasi'])) {
                $registration = $this->regM->find((int)$payment['id_registrasi']);
            }
            
            if (!$registration) {
                $registration = $this->regM
                    ->where('id_user', $payment['id_user'])
                    ->where('id_event', $payment['event_id'])
                    ->whereNotIn('status', ['batal', 'ditolak'])
                    ->orderBy('id', 'DESC')
                    ->first();
            }

            if ($registration) {
                $this->regM->update($registration['id'], ['status' => $regStatus]);
                log_message('info', "Registration {$registration['id']} updated to: {$regStatus}");
            }
        } catch (\Exception $e) {
            log_message('error', 'Registration update failed: ' . $e->getMessage());
        }
    }

    /**
     * Instruction page - bisa menerima regId atau eventId
     * Support melanjutkan pembayaran pending
     */
    public function instruction($param = null)
    {
        $uid = $this->uid(); 
        if(!$uid) return redirect()->to('/auth/login');

        // Cari registrasi
        $reg = null;
        
        if (is_numeric($param)) {
            // Coba sebagai regId
            $reg = $this->regM->find((int)$param);
            
            // Jika tidak ditemukan atau bukan milik user, coba dari event_id
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

        // CEK PEMBAYARAN PENDING - prioritas utama!
        $existingPayment = $this->payM
            ->where('id_user', $uid)
            ->where('event_id', $eventId)
            ->whereIn('status', ['pending', 'verified'])
            ->orderBy('tanggal_bayar', 'DESC')
            ->first();

        // Jika sudah verified, redirect ke detail event
        if ($existingPayment && $existingPayment['status'] === 'verified') {
            return redirect()->to('/audience/events/detail/' . $eventId)
                ->with('success', 'Pembayaran Anda sudah terverifikasi!');
        }

        // Get event data
        $event = $this->eventM->find($eventId);
        if (!$event) {
            return redirect()->to('/audience/events')->with('error', 'Event tidak ditemukan');
        }

        // Get user data
        $user = $this->userM->find($uid);

        // Get wave info
        $currentWave = $this->eventM->getCurrentWave($eventId);
        
        // Calculate amount
        $mode = $reg['mode_kehadiran'] ?? 'online';
        $pricing = $this->eventM->getPricingMatrix($eventId);
        $amount = (float)($pricing['audience'][$mode] ?? 0);

        // Jika ada pembayaran pending, ambil data snap token
        $snapToken = $existingPayment['midtrans_snap_token'] ?? null;
        $orderId = $existingPayment['midtrans_order_id'] ?? null;

        return view('role/audience/pembayaran/instruction', [
            'title'  => $existingPayment ? 'Lanjutkan Pembayaran' : 'Instruksi Pembayaran',
            'reg'    => $reg,
            'amount' => $existingPayment ? (float)$existingPayment['jumlah'] : $amount,
            'event'  => $event,
            'user'   => $user,
            'current_wave' => $currentWave,
            'existing_payment' => $existingPayment,
            'snap_token' => $snapToken,
            'order_id' => $orderId,
            'midtrans_client_key' => $this->midtrans->getClientKey(),
            'is_production' => $this->midtrans->isProduction(),
        ]);
    }

    /**
     * Create payment (optional - for direct payment)
     */
    public function create(int $regId)
    {
        $uid = $this->uid(); 
        if(!$uid) return redirect()->to('/auth/login');

        $reg = $this->regM->find($regId);
        if (!$reg || (int)$reg['id_user'] !== $uid) {
            return redirect()->to('/audience/events')->with('error','Data tidak ditemukan.');
        }

        // Redirect to instruction instead
        return redirect()->to('/audience/pembayaran/instruction/' . $regId);
    }

    /**
     * Process payment - Midtrans only
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

        // CEK PEMBAYARAN EXISTING
        $existingPayment = $this->payM
            ->where('id_user', $uid)
            ->where('event_id', (int)$reg['id_event'])
            ->whereIn('status', ['pending', 'verified'])
            ->first();

        if ($existingPayment) {
            return $this->response->setJSON([
                'success' => false, 
                'message' => 'Anda sudah memiliki pembayaran untuk event ini.',
                'redirect_url' => site_url('audience/pembayaran/detail/' . $existingPayment['id_pembayaran'])
            ]);
        }

        $event = $this->eventM->find((int)$reg['id_event']);
        if (!$event) {
            return $this->response->setJSON(['success' => false, 'message' => 'Event not found']);
        }

        $user = $this->userM->find($uid);
        $mode = $reg['mode_kehadiran'] ?? 'online';
        
        // Get price from wave
        $pricing = $this->eventM->getPricingMatrix((int)$reg['id_event']);
        $amount = (float)($pricing['audience'][$mode] ?? 0);

        try {
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
                $amount,
                $userDetails,
                $eventDetails
            );

            $paymentData = [
                'id_user'            => $uid,
                'event_id'           => (int)$reg['id_event'],
                'id_registrasi'      => $regId,
                'jumlah'             => $amount,
                'participation_type' => $mode,
                'midtrans_order_id'  => $midtransResponse['order_id'],
                'midtrans_snap_token'=> $midtransResponse['snap_token'],
                'original_amount'    => $amount,
                'discount_amount'    => 0,
            ];

            $this->payM->createMidtransPayment($paymentData);
            $paymentId = $this->payM->getInsertID();

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

    /**
     * Finish callback from Midtrans
     */
    public function finish()
    {
        $orderId = $this->request->getGet('order_id');
        $status = $this->request->getGet('status');
        
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
                $this->payM->updateFromMidtransNotification($orderId, $statusData);
                
                // Reload payment data
                $payment = $this->payM->getByMidtransOrderId($orderId);
                
                if ($payment['status'] === 'verified') {
                    $this->updateRegistrationStatus($payment, 'verified');
                    $message = 'Pembayaran Anda telah berhasil dan diverifikasi secara otomatis!';
                    $alertType = 'success';
                } elseif ($payment['status'] === 'pending') {
                    $message = 'Pembayaran sedang diproses. Status akan diperbarui otomatis.';
                    $alertType = 'info';
                } else {
                    $message = 'Status pembayaran: ' . ucfirst($payment['status']);
                    $alertType = 'warning';
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

    /**
     * Check payment status manually
     */
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
            $this->payM->updateFromMidtransNotification($payment['midtrans_order_id'], $statusData);
            
            // Reload
            $payment = $this->payM->find($paymentId);
            
            if ($payment['status'] === 'verified') {
                $this->updateRegistrationStatus($payment, 'verified');
                $message = 'Status berhasil diperbarui! Pembayaran telah terverifikasi.';
                $alertType = 'success';
            } else {
                $message = "Status diperbarui: " . ucfirst($payment['status']);
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

    /**
     * Payment detail page
     */
    public function detail(int $id)
    {
        $uid = $this->uid(); 
        if(!$uid) return redirect()->to('/auth/login');

        $row = $this->payM->find($id);
        if (!$row || (int)$row['id_user'] !== $uid) {
            return redirect()->to('/audience/pembayaran')->with('error','Data tidak ditemukan.');
        }

        // Sync jika pending
        if ($row['status'] === 'pending' && $row['metode'] === 'midtrans' && !empty($row['midtrans_order_id'])) {
            $this->syncSinglePayment($row);
            $row = $this->payM->find($id);
        }

        $event = $this->eventM->find((int)$row['event_id']);
        $voucher = !empty($row['id_voucher']) ? $this->voucherM->find($row['id_voucher']) : null;

        // Cari registrasi dengan lebih robust
        $reg = null;
        
        if (!empty($row['id_registrasi'])) {
            $reg = $this->regM->find((int)$row['id_registrasi']);
        }
        
        if (!$reg) {
            $reg = $this->regM
                ->where('id_event', (int)$row['event_id'])
                ->where('id_user', $uid)
                ->whereNotIn('status', ['batal', 'ditolak'])
                ->orderBy('id', 'DESC')
                ->first();
        }

        return view('role/audience/pembayaran/detail', [
            'title' => 'Detail Pembayaran',
            'pay'   => $row,
            'event' => $event,
            'voucher' => $voucher,
            'reg'   => $reg,
        ]);
    }

    private function syncSinglePayment($payment)
    {
        try {
            if (empty($payment['midtrans_order_id'])) return;

            $statusData = $this->midtrans->getTransactionStatus($payment['midtrans_order_id']);
            $this->payM->updateFromMidtransNotification($payment['midtrans_order_id'], $statusData);
            
            // Reload
            $payment = $this->payM->find($payment['id_pembayaran']);
            
            if ($payment && $payment['status'] === 'verified') {
                $this->updateRegistrationStatus($payment, 'verified');
            }
        } catch (\Exception $e) {
            log_message('error', 'Single payment sync error: ' . $e->getMessage());
        }
    }

    /**
     * Cancel payment
     */
    public function cancel(int $paymentId)
    {
        $uid = $this->uid(); 
        if(!$uid) return redirect()->to('/auth/login');

        $pay = $this->payM->find($paymentId);
        if (!$pay || (int)$pay['id_user'] !== $uid) {
            return redirect()->to('/audience/pembayaran')->with('error','Data tidak ditemukan.');
        }
        
        if ($pay['status'] !== 'pending') {
            return redirect()->to('/audience/pembayaran/detail/'.$paymentId)
                ->with('warning','Pembayaran tidak dapat dibatalkan.');
        }

        $this->payM->update($paymentId, [
            'status' => 'canceled', 
            'keterangan' => 'Dibatalkan oleh user'
        ]);

        $this->updateRegistrationStatus($pay, 'canceled');

        return redirect()->to('/audience/events')->with('message','Pembayaran dibatalkan.');
    }
}