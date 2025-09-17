<?php
namespace App\Controllers\Role\Presenter;

use App\Controllers\BaseController;
use App\Models\EventModel;
use App\Models\PembayaranModel;
use App\Models\AbstrakModel;
use App\Models\VoucherModel;
use App\Models\UserModel;
use App\Models\EventRegistrationModel;
use App\Services\MidtransService;
use App\Services\NotificationService;

class Pembayaran extends BaseController
{
    protected EventModel $eventModel;
    protected PembayaranModel $payModel;
    protected AbstrakModel $absModel;
    protected VoucherModel $voucherModel;
    protected UserModel $userModel;
    protected EventRegistrationModel $regModel;
    protected MidtransService $midtrans;
    protected NotificationService $notificationService;

    public function __construct()
    {
        $this->eventModel = new EventModel();
        $this->payModel = new PembayaranModel();
        $this->absModel = new AbstrakModel();
        $this->voucherModel = new VoucherModel();
        $this->userModel = new UserModel();
        $this->regModel = new EventRegistrationModel();
        $this->midtrans = new MidtransService();
        $this->notificationService = new NotificationService();
    }

    private function uid(): int { return (int) (session('id_user') ?? 0); }

    private function getBasePrice(array $event): int
    {
        return max(0, (int)($event['presenter_fee_offline'] ?? 0));
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
        
        return max(0, $basePrice - $nilai);
    }

    private function findValidVoucher(string $code, int $userId, int $eventId): ?array
    {
        $code = trim($code);
        if ($code === '') return null;

        $voucher = $this->voucherModel
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
            $used = $this->payModel
                ->where('id_voucher', $voucher['id_voucher'])
                ->whereIn('status', ['pending','verified'])
                ->countAllResults();
            if ($used >= $kuota) return null;
        }

        // Check if user already used this voucher for this event
        $existing = $this->payModel
            ->where('id_user', $userId)
            ->where('event_id', $eventId)
            ->where('id_voucher', $voucher['id_voucher'])
            ->whereIn('status', ['pending','verified'])
            ->first();

        if ($existing) return null;

        return $voucher;
    }

    private function ensureAccepted(int $userId, int $eventId): bool
    {
        return $this->absModel->where('id_user', $userId)
                              ->where('event_id', $eventId)
                              ->where('status', 'diterima')
                              ->countAllResults() > 0;
    }

    /**
     * INDEX: Show payment list with event context and flow status
     */
    public function index()
    {
        $userId = $this->uid();
        if (!$userId) return redirect()->to('/auth/login');

        // Sync pending payments first
        $this->syncPendingPayments($userId);

        // Get all payments with event info
        $payments = $this->payModel
            ->select('pembayaran.*, e.title as event_title, e.event_date, e.event_time, e.format')
            ->join('events e', 'e.id = pembayaran.event_id', 'left')
            ->where('pembayaran.id_user', $userId)
            ->orderBy('pembayaran.id_pembayaran', 'DESC')
            ->findAll();

        // Get events with payments for flow status
        $eventIds = array_unique(array_column($payments, 'event_id'));
        $events = [];
        $flowStatuses = [];
        
        if (!empty($eventIds)) {
            $eventRows = $this->eventModel->whereIn('id', $eventIds)->findAll();
            foreach ($eventRows as $event) {
                $events[$event['id']] = $event;
                $flowStatuses[$event['id']] = $this->computeFlowStatus($event['id'], $userId);
            }
        }

        // Enhanced payment data with flow context
        $enhancedPayments = [];
        foreach ($payments as $payment) {
            $eventId = (int)$payment['event_id'];
            $payment['event'] = $events[$eventId] ?? null;
            $payment['flow'] = $flowStatuses[$eventId] ?? null;
            $payment['next_action'] = $this->getNextAction($payment, $flowStatuses[$eventId] ?? null);
            $enhancedPayments[] = $payment;
        }

        $badgeMap = [
            'pending' => 'warning',
            'verified' => 'success',
            'canceled' => 'danger',
            'expired' => 'dark'
        ];

        // Separate active (pending) and completed payments
        $aktif = array_values(array_filter($enhancedPayments, 
            static fn($r) => strtolower($r['status'] ?? '') === 'pending'));
        $riwayat = array_values(array_filter($enhancedPayments, 
            static fn($r) => strtolower($r['status'] ?? '') !== 'pending'));

        // Get presenter's events that need payment but don't have it yet
        $eventsNeedingPayment = $this->getEventsNeedingPayment($userId);

        return view('role/presenter/pembayaran/index', [
            'title' => 'Pembayaran',
            'payments' => $enhancedPayments,
            'badgeMap' => $badgeMap,
            'aktif' => $aktif,
            'riwayat' => $riwayat,
            'eventsNeedingPayment' => $eventsNeedingPayment,
            'flowStatuses' => $flowStatuses,
        ]);
    }

    /**
     * Get events that have accepted abstracts but no payment yet
     */
    private function getEventsNeedingPayment(int $userId): array
    {
        $acceptedAbstracts = $this->absModel
            ->select('abstrak.event_id, e.title, e.event_date, e.presenter_fee_offline')
            ->join('events e', 'e.id = abstrak.event_id', 'left')
            ->where('abstrak.id_user', $userId)
            ->where('abstrak.status', 'diterima')
            ->findAll();

        $eventsNeedingPayment = [];
        
        foreach ($acceptedAbstracts as $abstract) {
            $eventId = (int)$abstract['event_id'];
            
            // Check if payment already exists
            $existingPayment = $this->payModel
                ->where('id_user', $userId)
                ->where('event_id', $eventId)
                ->whereIn('status', ['pending', 'verified'])
                ->first();

            if (!$existingPayment) {
                $eventsNeedingPayment[] = [
                    'event_id' => $eventId,
                    'title' => $abstract['title'],
                    'event_date' => $abstract['event_date'],
                    'amount' => (int)($abstract['presenter_fee_offline'] ?? 0),
                    'flow' => $this->computeFlowStatus($eventId, $userId)
                ];
            }
        }

        return $eventsNeedingPayment;
    }

    /**
     * Compute flow status (matches Event controller logic)
     */
    private function computeFlowStatus(int $eventId, int $userId): array
    {
        $reg = $this->regModel->findUserReg($eventId, $userId);

        $state = 'belum_daftar';
        $label = 'Belum terdaftar';
        $hint = 'Klik Daftar untuk mulai';
        $can = ['register' => true];

        if ($reg) {
            $ab = $this->absModel
                ->where('id_user', $userId)
                ->where('event_id', $eventId)
                ->orderBy('id_abstrak', 'DESC')
                ->first();

            $pay = $this->payModel
                ->where('id_user', $userId)
                ->where('event_id', $eventId)
                ->orderBy('id_pembayaran', 'DESC')
                ->first();

            if (!$ab) {
                $state = 'upload_abstrak';
                $label = 'Silakan upload abstrak';
                $hint = 'Wajib sebelum pembayaran';
                $can = ['upload' => true, 'cancel' => true];
            } else {
                switch ($ab['status']) {
                    case 'menunggu':
                    case 'sedang_direview':
                        $state = 'menunggu_abstrak';
                        $label = 'Menunggu hasil abstrak';
                        $hint = 'Tunggu ACC/revisi/ditolak';
                        $can = ['view_abstrak' => true];
                        break;
                    case 'revisi':
                        $state = 'revisi_abstrak';
                        $label = 'Revisi abstrak';
                        $hint = 'Silakan unggah ulang dokumen revisi';
                        $can = ['reupload' => true];
                        break;
                    case 'ditolak':
                        $state = 'abstrak_ditolak';
                        $label = 'Abstrak ditolak';
                        $hint = 'Anda dapat kirim ulang abstrak baru';
                        $can = ['upload' => true];
                        break;
                    case 'diterima':
                        if (!$pay) {
                            $state = 'bayar';
                            $label = 'Silakan lakukan pembayaran';
                            $hint = 'Pembayaran digital via Midtrans';
                            $can = ['pay' => true];
                        } else {
                            if ($pay['status'] === 'pending') {
                                $state = 'pembayaran_pending';
                                $label = 'Menunggu verifikasi pembayaran';
                                $hint = 'Pembayaran sedang diproses';
                                $can = ['pay_detail' => true];
                            } elseif ($pay['status'] === 'canceled') {
                                $state = 'pembayaran_dibatalkan';
                                $label = 'Pembayaran dibatalkan';
                                $hint = 'Silakan lakukan pembayaran ulang';
                                $can = ['pay' => true];
                            } elseif ($pay['status'] === 'verified') {
                                $state = 'siap_absen';
                                $label = 'Siap untuk event';
                                $hint = 'Pembayaran terverifikasi, siap absen';
                                $can = ['absen' => true, 'documents' => true];
                            }
                        }
                        break;
                }
            }
        }

        return [
            'state' => $state,
            'label' => $label,
            'hint' => $hint,
            'can' => $can,
            'reg' => $reg,
        ];
    }

    /**
     * Get next recommended action for payment
     */
    private function getNextAction(array $payment, ?array $flow): array
    {
        $status = strtolower($payment['status'] ?? 'pending');
        $flowState = $flow['state'] ?? 'unknown';

        switch ($status) {
            case 'pending':
                return [
                    'label' => 'Cek Status',
                    'url' => site_url('presenter/pembayaran/detail/' . $payment['id_pembayaran']),
                    'class' => 'btn-warning',
                    'icon' => 'bi-clock'
                ];
                
            case 'verified':
                if ($flowState === 'siap_absen') {
                    return [
                        'label' => 'Lihat Event',
                        'url' => site_url('presenter/events/detail/' . $payment['event_id']),
                        'class' => 'btn-success',
                        'icon' => 'bi-calendar-check'
                    ];
                }
                return [
                    'label' => 'Detail',
                    'url' => site_url('presenter/pembayaran/detail/' . $payment['id_pembayaran']),
                    'class' => 'btn-outline-success',
                    'icon' => 'bi-check-circle'
                ];
                
            case 'canceled':
            case 'expired':
                if ($flowState === 'bayar') {
                    return [
                        'label' => 'Bayar Ulang',
                        'url' => site_url('presenter/pembayaran/instruction/' . $payment['event_id']),
                        'class' => 'btn-primary',
                        'icon' => 'bi-arrow-repeat'
                    ];
                }
                return [
                    'label' => 'Detail',
                    'url' => site_url('presenter/pembayaran/detail/' . $payment['id_pembayaran']),
                    'class' => 'btn-outline-danger',
                    'icon' => 'bi-x-circle'
                ];
                
            default:
                return [
                    'label' => 'Detail',
                    'url' => site_url('presenter/pembayaran/detail/' . $payment['id_pembayaran']),
                    'class' => 'btn-outline-secondary',
                    'icon' => 'bi-eye'
                ];
        }
    }

    private function syncPendingPayments($userId)
    {
        try {
            $pendingPayments = $this->payModel
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
                            'keterangan' => "Auto sync: {$transactionStatus}"
                        ];
                        
                        if ($newStatus === 'verified') {
                            $updateData['verified_at'] = $statusData['settlement_time'] ?? date('Y-m-d H:i:s');
                            $updateData['auto_verified'] = true;
                            $updateData['features_unlocked_at'] = $statusData['settlement_time'] ?? date('Y-m-d H:i:s');
                            
                            if (!empty($payment['id_voucher'])) {
                                $this->voucherModel->reduceQuota($payment['id_voucher']);
                            }
                        }
                        
                        $this->payModel->update($payment['id_pembayaran'], $updateData);
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

    public function instruction(int $eventId)
    {
        $userId = $this->uid();
        if (!$userId) return redirect()->to('/auth/login');

        $event = $this->eventModel->find($eventId);
        if (!$event) return redirect()->to('/presenter/events')->with('error', 'Event tidak ditemukan.');

        if (!$this->ensureAccepted($userId, $eventId)) {
            return redirect()->to('/presenter/events/detail/' . $eventId)
                ->with('error', 'Instruksi pembayaran muncul setelah abstrak diterima.');
        }

        // Check if payment already exists
        $existingPayment = $this->payModel
            ->where('id_user', $userId)
            ->where('event_id', $eventId)
            ->whereIn('status', ['pending', 'verified'])
            ->first();

        if ($existingPayment) {
            return redirect()->to('/presenter/pembayaran/detail/' . $existingPayment['id_pembayaran'])
                ->with('message', 'Anda sudah memiliki pembayaran untuk event ini.');
        }

        return view('role/presenter/pembayaran/instruction', [
            'title' => 'Instruksi Pembayaran',
            'event' => $event,
            'basePrice' => $this->getBasePrice($event),
            'midtrans_client_key' => $this->midtrans->getClientKey(),
            'is_production' => $this->midtrans->isProduction(),
        ]);
    }

    public function validateVoucher()
    {
        $userId = $this->uid();
        $eventId = (int) ($this->request->getPost('event_id') ?? $this->request->getGet('event_id'));
        $code = (string) ($this->request->getPost('kode_voucher') ?? $this->request->getGet('kode_voucher'));

        if (!$eventId) {
            return $this->response->setJSON(['ok' => false, 'message' => 'Event tidak valid.', 'token' => csrf_hash()]);
        }

        $event = $this->eventModel->find($eventId);
        if (!$event) {
            return $this->response->setJSON(['ok' => false, 'message' => 'Event tidak ditemukan.', 'token' => csrf_hash()]);
        }

        $basePrice = $this->getBasePrice($event);
        $voucher = $this->findValidVoucher($code, $userId, $eventId);

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

    public function create(int $eventId)
    {
        $userId = $this->uid();
        if (!$userId) return redirect()->to('/auth/login');

        $event = $this->eventModel->find($eventId);
        if (!$event) return redirect()->to('/presenter/pembayaran')->with('error', 'Event tidak ditemukan.');

        if (!$this->ensureAccepted($userId, $eventId)) {
            return redirect()->to('/presenter/events/detail/' . $eventId)
                ->with('error', 'Pembayaran hanya setelah abstrak diterima.');
        }

        // Check if payment already exists
        $existingPayment = $this->payModel
            ->where('id_user', $userId)
            ->where('event_id', $eventId)
            ->whereIn('status', ['pending', 'verified'])
            ->first();

        if ($existingPayment) {
            return redirect()->to('/presenter/pembayaran/detail/' . $existingPayment['id_pembayaran'])
                ->with('message', 'Anda sudah memiliki pembayaran untuk event ini.');
        }

        $basePrice = $this->getBasePrice($event);
        $user = $this->userModel->find($userId);

        return view('role/presenter/pembayaran/create', [
            'title' => 'Pilihan Pembayaran',
            'event' => $event,
            'amount' => $basePrice,
            'user' => $user,
            'midtrans_client_key' => $this->midtrans->getClientKey(),
            'is_production' => $this->midtrans->isProduction(),
        ]);
    }

    public function processPayment()
    {
        if (!$this->request->is('post')) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request method']);
        }

        $userId = $this->uid();
        if (!$userId) return $this->response->setJSON(['success' => false, 'message' => 'Please login']);

        $eventId = (int) $this->request->getPost('event_id');
        $voucherCode = trim((string) $this->request->getPost('voucher_code'));

        $event = $this->eventModel->find($eventId);
        if (!$event) {
            return $this->response->setJSON(['success' => false, 'message' => 'Event not found']);
        }

        if (!$this->ensureAccepted($userId, $eventId)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Abstrak belum diterima']);
        }

        $user = $this->userModel->find($userId);
        $basePrice = $this->getBasePrice($event);
        $finalPrice = $basePrice;
        $voucherId = null;

        // Apply voucher if provided
        if ($voucherCode !== '') {
            $voucher = $this->findValidVoucher($voucherCode, $userId, $eventId);
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
                $eventId,
                $userId,
                'presenter',
                'offline',
                $finalPrice,
                $userDetails,
                $eventDetails
            );

            // Save payment record
            $paymentData = [
                'id_user'            => $userId,
                'event_id'           => $eventId,
                'jumlah'             => $finalPrice,
                'participation_type' => 'offline',
                'midtrans_order_id'  => $midtransResponse['order_id'],
                'midtrans_snap_token'=> $midtransResponse['snap_token'],
                'id_voucher'         => $voucherId,
                'original_amount'    => $basePrice,
                'discount_amount'    => $basePrice - $finalPrice,
            ];

            $this->payModel->createMidtransPayment($paymentData);
            $paymentId = $this->payModel->getInsertID();

            // Send notification
            try {
                $this->notificationService->notify(
                    $userId,
                    'payment',
                    'Pembayaran Dibuat',
                    "Pembayaran untuk event \"{$event['title']}\" telah dibuat. Silakan selesaikan pembayaran.",
                    site_url('presenter/pembayaran/detail/' . $paymentId)
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
                'redirect_url' => site_url('presenter/pembayaran/detail/' . $paymentId)
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Payment creation error: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()]);
        }
    }

    public function finish()
    {
        $orderId = $this->request->getGet('order_id');
        $status = $this->request->getGet('status');
        
        if (!$orderId) {
            return redirect()->to('/presenter/pembayaran')->with('error', 'Parameter pembayaran tidak valid');
        }

        $payment = $this->payModel->getByMidtransOrderId($orderId);
        if (!$payment) {
            return redirect()->to('/presenter/pembayaran')->with('error', 'Data pembayaran tidak ditemukan');
        }

        if ((int)$payment['id_user'] !== $this->uid()) {
            return redirect()->to('/presenter/pembayaran')->with('error', 'Akses tidak diizinkan');
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
                        
                        if (!empty($payment['id_voucher'])) {
                            $this->voucherModel->reduceQuota($payment['id_voucher']);
                        }
                        
                        $message = 'Pembayaran Anda telah berhasil dan diverifikasi secara otomatis!';
                        $alertType = 'success';
                        
                    } elseif ($newStatus === 'pending') {
                        $message = 'Pembayaran sedang diproses. Status akan diperbarui otomatis.';
                        $alertType = 'info';
                        
                    } else {
                        $message = 'Pembayaran dibatalkan atau gagal. Anda dapat mencoba lagi.';
                        $alertType = 'warning';
                    }
                    
                    $this->payModel->update($payment['id_pembayaran'], $updateData);
                } else {
                    $message = match($payment['status']) {
                        'verified' => 'Pembayaran Anda telah berhasil diverifikasi!',
                        'pending' => 'Pembayaran sedang diproses. Mohon tunggu konfirmasi.',
                        default => 'Status pembayaran telah diperbarui.'
                    };
                    
                    $alertType = match($payment['status']) {
                        'verified' => 'success',
                        'pending' => 'info',
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

        return redirect()->to('/presenter/pembayaran/detail/' . $payment['id_pembayaran'])
            ->with($alertType, $message);
    }

    public function detail(int $id)
    {
        $userId = $this->uid();
        if (!$userId) return redirect()->to('/auth/login');

        $row = $this->payModel->find($id);
        if (!$row || (int)$row['id_user'] !== $userId) {
            return redirect()->to('/presenter/pembayaran')->with('error', 'Data pembayaran tidak ditemukan.');
        }

        // Sync status if pending Midtrans payment
        if ($row['status'] === 'pending' && $row['metode'] === 'midtrans' && !empty($row['midtrans_order_id'])) {
            $this->syncSinglePayment($row);
            $row = $this->payModel->find($id);
        }

        $event = $this->eventModel->select('id,title,event_date,event_time')->find((int)$row['event_id']);
        $voucher = !empty($row['id_voucher']) ? $this->voucherModel->find($row['id_voucher']) : null;

        return view('role/presenter/pembayaran/detail', [
            'title' => 'Detail Pembayaran',
            'pay' => $row,
            'event' => $event,
            'voucher' => $voucher,
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
                    
                    if (!empty($payment['id_voucher'])) {
                        $this->voucherModel->reduceQuota($payment['id_voucher']);
                    }
                }
                
                $this->payModel->update($payment['id_pembayaran'], $updateData);
            }
            
        } catch (\Exception $e) {
            log_message('error', 'Single payment sync error: ' . $e->getMessage());
        }
    }

    public function checkStatus($paymentId)
    {
        $userId = $this->uid();
        if (!$userId) return redirect()->to('/auth/login');

        $payment = $this->payModel->find($paymentId);
        if (!$payment || (int)$payment['id_user'] !== $userId) {
            return redirect()->to('/presenter/pembayaran')->with('error', 'Data tidak ditemukan');
        }

        if (empty($payment['midtrans_order_id'])) {
            return redirect()->to('/presenter/pembayaran/detail/' . $paymentId)
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
                    
                    if (!empty($payment['id_voucher'])) {
                        $this->voucherModel->reduceQuota($payment['id_voucher']);
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
                
                $this->payModel->update($paymentId, $updateData);
                
            } else {
                $message = 'Status pembayaran sudah up-to-date: ' . ucfirst($payment['status']);
                $alertType = 'info';
            }

        } catch (\Exception $e) {
            log_message('error', 'Manual status check error: ' . $e->getMessage());
            $message = 'Gagal memeriksa status. Coba lagi nanti.';
            $alertType = 'error';
        }

        return redirect()->to('/presenter/pembayaran/detail/' . $paymentId)
            ->with($alertType, $message);
    }

    public function cancel(int $paymentId)
    {
        $userId = $this->uid();
        if (!$userId) return redirect()->to('/auth/login');

        $pay = $this->payModel->find($paymentId);
        if (!$pay || (int)$pay['id_user'] !== $userId) {
            return redirect()->to('/presenter/pembayaran')->with('error', 'Data tidak ditemukan.');
        }
        if ($pay['status'] !== 'pending') {
            return redirect()->to('/presenter/pembayaran/detail/' . $paymentId)->with('warning', 'Pembayaran tidak dapat dibatalkan.');
        }

        $this->payModel->update($paymentId, ['status' => 'canceled', 'keterangan' => 'Dibatalkan oleh user']);

        return redirect()->to('/presenter/pembayaran')->with('message', 'Pembayaran dibatalkan.');
    }
}