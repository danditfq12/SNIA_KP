<?php

namespace App\Controllers\Role\Presenter;

use App\Controllers\BaseController;
use App\Models\PembayaranModel;
use App\Models\EventModel;
use App\Models\VoucherModel;
use App\Models\EventRegistrationModel;
use App\Models\AbstrakModel;
use App\Models\UserModel;

class Pembayaran extends BaseController
{
    protected $pembayaranModel;
    protected $eventModel;
    protected $voucherModel;
    protected $eventRegistrationModel;
    protected $abstrakModel;
    protected $userModel;
    protected $db;

    public function __construct()
    {
        $this->pembayaranModel = new PembayaranModel();
        $this->eventModel = new EventModel();
        $this->voucherModel = new VoucherModel();
        $this->eventRegistrationModel = new EventRegistrationModel();
        $this->abstrakModel = new AbstrakModel();
        $this->userModel = new UserModel();
        $this->db = \Config\Database::connect();
    }

    /**
     * Display payment history for presenter
     */
    public function index()
    {
        $userId = session('id_user');
        
        try {
            // Get presenter's payment history with event details
            $payments = $this->getPresenterPayments($userId);
            
            // Calculate payment statistics
            $stats = $this->calculatePaymentStats($userId);
            
            // Get events that need payment
            $pendingEvents = $this->getEventsNeedingPayment($userId);

            $data = [
                'payments' => $payments,
                'stats' => $stats,
                'pending_events' => $pendingEvents,
                'total_payments' => count($payments),
                'can_pay_events' => count($pendingEvents)
            ];

            return view('role/presenter/pembayaran/index', $data);

        } catch (\Exception $e) {
            log_message('error', 'Presenter payment index error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memuat data pembayaran.');
        }
    }

    /**
     * Get presenter's payments with event details
     */
    private function getPresenterPayments($userId)
    {
        return $this->db->table('pembayaran')
            ->select('
                pembayaran.*,
                events.title as event_title,
                events.event_date,
                events.event_time,
                events.location,
                events.zoom_link,
                events.format,
                voucher.kode_voucher,
                voucher.tipe as voucher_tipe,
                voucher.nilai as voucher_nilai,
                verifier.nama_lengkap as verified_by_name
            ')
            ->join('events', 'events.id = pembayaran.event_id', 'left')
            ->join('voucher', 'voucher.id_voucher = pembayaran.id_voucher', 'left')
            ->join('users as verifier', 'verifier.id_user = pembayaran.verified_by', 'left')
            ->where('pembayaran.id_user', $userId)
            ->orderBy('pembayaran.tanggal_bayar', 'DESC')
            ->get()->getResultArray();
    }

    /**
     * Calculate payment statistics for presenter
     */
    private function calculatePaymentStats($userId)
    {
        $allPayments = $this->pembayaranModel->where('id_user', $userId)->findAll();
        
        $stats = [
            'total_payments' => count($allPayments),
            'verified_payments' => 0,
            'pending_payments' => 0,
            'rejected_payments' => 0,
            'total_amount_paid' => 0,
            'total_savings' => 0
        ];

        foreach ($allPayments as $payment) {
            switch ($payment['status']) {
                case 'verified':
                    $stats['verified_payments']++;
                    $stats['total_amount_paid'] += $payment['jumlah'];
                    // Calculate savings if there's original amount
                    if (isset($payment['original_amount']) && $payment['original_amount'] > $payment['jumlah']) {
                        $stats['total_savings'] += ($payment['original_amount'] - $payment['jumlah']);
                    }
                    break;
                case 'pending':
                    $stats['pending_payments']++;
                    break;
                case 'rejected':
                    $stats['rejected_payments']++;
                    break;
            }
        }

        return $stats;
    }

    /**
     * Get events that need payment (abstract accepted but not paid)
     */
    private function getEventsNeedingPayment($userId)
    {
        $acceptedAbstracts = $this->abstrakModel
            ->select('abstrak.*, events.title as event_title, events.event_date, events.presenter_fee_offline')
            ->join('events', 'events.id = abstrak.event_id')
            ->where('abstrak.id_user', $userId)
            ->where('abstrak.status', 'diterima')
            ->where('events.is_active', true)
            ->where('events.registration_active', true)
            ->findAll();

        $needsPayment = [];
        
        foreach ($acceptedAbstracts as $abstract) {
            // Check if payment already exists
            $existingPayment = $this->pembayaranModel
                ->where('id_user', $userId)
                ->where('event_id', $abstract['event_id'])
                ->where('status !=', 'rejected')
                ->first();

            if (!$existingPayment) {
                $needsPayment[] = $abstract;
            }
        }

        return $needsPayment;
    }

    /**
     * Show payment creation form for specific event
     */
    public function create($eventId)
    {
        $userId = session('id_user');
        
        try {
            $event = $this->eventModel->find($eventId);
            
            if (!$event || !$event['is_active']) {
                return redirect()->to('presenter/pembayaran')
                               ->with('error', 'Event tidak ditemukan atau tidak aktif.');
            }

            // Check if presenter has accepted abstract for this event
            $abstract = $this->abstrakModel
                ->where('id_user', $userId)
                ->where('event_id', $eventId)
                ->where('status', 'diterima')
                ->first();

            if (!$abstract) {
                return redirect()->to('presenter/pembayaran')
                               ->with('error', 'Anda harus memiliki abstrak yang diterima untuk melakukan pembayaran.');
            }

            // Check if payment already exists and not rejected
            $existingPayment = $this->pembayaranModel
                ->where('id_user', $userId)
                ->where('event_id', $eventId)
                ->where('status !=', 'rejected')
                ->first();

            if ($existingPayment) {
                return redirect()->to('presenter/pembayaran/detail/' . $existingPayment['id_pembayaran'])
                               ->with('info', 'Pembayaran untuk event ini sudah ada.');
            }

            // Check registration deadline
            if (!$this->eventModel->isRegistrationOpen($eventId)) {
                return redirect()->to('presenter/pembayaran')
                               ->with('error', 'Pendaftaran untuk event ini sudah ditutup.');
            }

            // Get user profile for payment form
            $user = $this->userModel->find($userId);
            
            // Calculate price (presenter always offline)
            $basePrice = $event['presenter_fee_offline'] ?? 0;
            
            $data = [
                'event' => $event,
                'abstract' => $abstract,
                'user' => $user,
                'base_price' => $basePrice,
                'participation_type' => 'offline', // Presenter always offline
                'payment_methods' => $this->getPaymentMethods(),
                'available_vouchers' => $this->getAvailableVouchers()
            ];

            return view('role/presenter/pembayaran/create', $data);

        } catch (\Exception $e) {
            log_message('error', 'Presenter payment create error: ' . $e->getMessage());
            return redirect()->to('presenter/pembayaran')
                           ->with('error', 'Terjadi kesalahan saat memuat form pembayaran.');
        }
    }

    /**
     * Get available payment methods
     */
    private function getPaymentMethods()
    {
        return [
            'bank_transfer' => [
                'name' => 'Transfer Bank',
                'description' => 'Transfer melalui rekening bank',
                'icon' => 'fas fa-university',
                'details' => [
                    'Bank BCA: 1234567890 a.n. SNIA Organization',
                    'Bank Mandiri: 0987654321 a.n. SNIA Organization',
                    'Bank BNI: 5678901234 a.n. SNIA Organization'
                ]
            ],
            'e_wallet' => [
                'name' => 'E-Wallet',
                'description' => 'Pembayaran melalui dompet digital',
                'icon' => 'fas fa-mobile-alt',
                'details' => [
                    'GoPay: 081234567890',
                    'OVO: 081234567890',
                    'DANA: 081234567890'
                ]
            ],
            'qris' => [
                'name' => 'QRIS',
                'description' => 'Scan QR Code untuk pembayaran',
                'icon' => 'fas fa-qrcode',
                'details' => [
                    'Scan QR Code yang tersedia',
                    'Gunakan aplikasi m-banking atau e-wallet'
                ]
            ]
        ];
    }

    /**
     * Get available vouchers
     */
    private function getAvailableVouchers()
    {
        return $this->voucherModel
            ->where('status', 'aktif')
            ->where('masa_berlaku >=', date('Y-m-d'))
            ->where('kuota >', 0)
            ->orderBy('masa_berlaku', 'ASC')
            ->findAll();
    }

    /**
     * Store payment data
     */
    public function store()
    {
        $userId = session('id_user');
        
        $validation = \Config\Services::validation();
        
        $rules = [
            'event_id' => 'required|integer',
            'metode' => 'required|in_list[bank_transfer,e_wallet,qris]',
            'bukti_bayar' => [
                'uploaded[bukti_bayar]',
                'max_size[bukti_bayar,5120]', // 5MB
                'ext_in[bukti_bayar,jpg,jpeg,png,pdf]'
            ],
            'jumlah' => 'required|numeric|greater_than[0]'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        $eventId = $this->request->getPost('event_id');
        $metode = $this->request->getPost('metode');
        $voucherCode = $this->request->getPost('voucher_code');
        $originalAmount = $this->request->getPost('original_amount');
        $finalAmount = $this->request->getPost('jumlah');

        $this->db->transStart();

        try {
            // Validate event and abstract
            $event = $this->eventModel->find($eventId);
            if (!$event || !$event['is_active']) {
                throw new \Exception('Event tidak valid atau tidak aktif.');
            }

            $abstract = $this->abstrakModel
                ->where('id_user', $userId)
                ->where('event_id', $eventId)
                ->where('status', 'diterima')
                ->first();

            if (!$abstract) {
                throw new \Exception('Anda harus memiliki abstrak yang diterima untuk event ini.');
            }

            // Check existing payment
            $existingPayment = $this->pembayaranModel
                ->where('id_user', $userId)
                ->where('event_id', $eventId)
                ->where('status !=', 'rejected')
                ->first();

            if ($existingPayment) {
                throw new \Exception('Pembayaran untuk event ini sudah ada.');
            }

            // Handle file upload
            $file = $this->request->getFile('bukti_bayar');
            if (!$file->isValid() || $file->hasMoved()) {
                throw new \Exception('File bukti pembayaran tidak valid.');
            }

            // Create upload directory
            $uploadPath = WRITEPATH . 'uploads/pembayaran/';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            // Generate unique filename
            $fileName = 'BUKTI_' . $eventId . '_' . $userId . '_' . time() . '.' . $file->getExtension();
            
            if (!$file->move($uploadPath, $fileName)) {
                throw new \Exception('Gagal menyimpan file bukti pembayaran.');
            }

            // Process voucher if provided
            $voucherId = null;
            $discountAmount = 0;
            
            if ($voucherCode) {
                $voucher = $this->voucherModel->where('kode_voucher', strtoupper($voucherCode))->first();
                
                if ($voucher && $this->isVoucherValid($voucher, $userId, $eventId)) {
                    $voucherId = $voucher['id_voucher'];
                    
                    if ($voucher['tipe'] === 'percentage') {
                        $discountAmount = ($originalAmount * $voucher['nilai']) / 100;
                    } else {
                        $discountAmount = $voucher['nilai'];
                    }
                }
            }

            // Create payment record
            $paymentData = [
                'id_user' => $userId,
                'event_id' => $eventId,
                'metode' => $metode,
                'jumlah' => $finalAmount,
                'original_amount' => $originalAmount,
                'discount_amount' => $discountAmount,
                'bukti_bayar' => $fileName,
                'status' => 'pending',
                'tanggal_bayar' => date('Y-m-d H:i:s'),
                'id_voucher' => $voucherId,
                'participation_type' => 'offline', // Presenter always offline
                'payment_reference' => 'PAY_' . $eventId . '_' . $userId . '_' . time()
            ];

            if (!$this->pembayaranModel->save($paymentData)) {
                throw new \Exception('Gagal menyimpan data pembayaran: ' . implode(', ', $this->pembayaranModel->errors()));
            }

            $paymentId = $this->pembayaranModel->getInsertID();

            // Update event registration status
            $registration = $this->eventRegistrationModel->findUserReg($eventId, $userId);
            if ($registration) {
                $this->eventRegistrationModel->update($registration['id'], [
                    'status' => 'menunggu_pembayaran'
                ]);
            }

            // Log activity
            $this->logActivity($userId, "Created payment for event: {$event['title']} (Amount: Rp " . number_format($finalAmount, 0, ',', '.') . ")");

            $this->db->transComplete();

            if ($this->db->transStatus() === FALSE) {
                throw new \Exception('Transaction failed');
            }

            return redirect()->to('presenter/pembayaran/detail/' . $paymentId)
                           ->with('success', 'Pembayaran berhasil dibuat! Silakan tunggu verifikasi dari admin.');

        } catch (\Exception $e) {
            $this->db->transRollback();
            
            // Clean up uploaded file if exists
            if (isset($fileName) && file_exists($uploadPath . $fileName)) {
                unlink($uploadPath . $fileName);
            }
            
            log_message('error', 'Presenter payment store error: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Validate voucher for use
     */
    private function isVoucherValid($voucher, $userId, $eventId)
    {
        // Check voucher status
        if ($voucher['status'] !== 'aktif') {
            return false;
        }

        // Check expiration
        if (strtotime($voucher['masa_berlaku']) < time()) {
            return false;
        }

        // Check quota
        $usedCount = $this->pembayaranModel
            ->where('id_voucher', $voucher['id_voucher'])
            ->where('status', 'verified')
            ->countAllResults();

        if ($usedCount >= $voucher['kuota']) {
            return false;
        }

        // Check if user already used this voucher for this event
        $userUsage = $this->pembayaranModel
            ->where('id_voucher', $voucher['id_voucher'])
            ->where('id_user', $userId)
            ->where('event_id', $eventId)
            ->first();

        return !$userUsage;
    }

    /**
     * Show payment detail
     */
    public function detail($paymentId)
    {
        $userId = session('id_user');
        
        try {
            $payment = $this->db->table('pembayaran')
                ->select('
                    pembayaran.*,
                    events.title as event_title,
                    events.event_date,
                    events.event_time,
                    events.location,
                    events.zoom_link,
                    events.format,
                    voucher.kode_voucher,
                    voucher.tipe as voucher_tipe,
                    voucher.nilai as voucher_nilai,
                    verifier.nama_lengkap as verified_by_name
                ')
                ->join('events', 'events.id = pembayaran.event_id', 'left')
                ->join('voucher', 'voucher.id_voucher = pembayaran.id_voucher', 'left')
                ->join('users as verifier', 'verifier.id_user = pembayaran.verified_by', 'left')
                ->where('pembayaran.id_pembayaran', $paymentId)
                ->where('pembayaran.id_user', $userId)
                ->get()->getRowArray();

            if (!$payment) {
                return redirect()->to('presenter/pembayaran')
                               ->with('error', 'Data pembayaran tidak ditemukan.');
            }

            // Get payment method details
            $paymentMethods = $this->getPaymentMethods();
            $methodDetails = $paymentMethods[$payment['metode']] ?? null;

            // Check if payment file exists
            $filePath = WRITEPATH . 'uploads/pembayaran/' . $payment['bukti_bayar'];
            $fileExists = file_exists($filePath);

            // Get event features status (if payment verified)
            $eventFeatures = [];
            if ($payment['status'] === 'verified') {
                $eventFeatures = $this->getEventFeaturesStatus($userId, $payment['event_id']);
            }

            $data = [
                'payment' => $payment,
                'method_details' => $methodDetails,
                'file_exists' => $fileExists,
                'event_features' => $eventFeatures,
                'status_badge_class' => $this->getStatusBadgeClass($payment['status']),
                'status_icon' => $this->getStatusIcon($payment['status'])
            ];

            return view('role/presenter/pembayaran/detail', $data);

        } catch (\Exception $e) {
            log_message('error', 'Presenter payment detail error: ' . $e->getMessage());
            return redirect()->to('presenter/pembayaran')
                           ->with('error', 'Terjadi kesalahan saat memuat detail pembayaran.');
        }
    }

    /**
     * Get event features status after payment verification
     */
    private function getEventFeaturesStatus($userId, $eventId)
    {
        $features = [
            'attendance_scanning' => false,
            'loa_download' => false,
            'certificate_download' => false,
            'presenter_dashboard' => true // Always available after payment
        ];

        // Check attendance
        $attendance = $this->db->table('absensi')
            ->where('id_user', $userId)
            ->where('event_id', $eventId)
            ->where('status', 'hadir')
            ->get()->getRowArray();

        $features['attendance_scanning'] = !empty($attendance);

        // Check documents
        $loa = $this->db->table('dokumen')
            ->where('id_user', $userId)
            ->where('event_id', $eventId)
            ->where('tipe', 'loa')
            ->get()->getRowArray();

        $certificate = $this->db->table('dokumen')
            ->where('id_user', $userId)
            ->where('event_id', $eventId)
            ->where('tipe', 'sertifikat')
            ->get()->getRowArray();

        $features['loa_download'] = !empty($loa);
        $features['certificate_download'] = !empty($certificate);

        return $features;
    }

    /**
     * Download payment proof file
     */
    public function downloadBukti($paymentId)
    {
        $userId = session('id_user');
        
        try {
            $payment = $this->pembayaranModel
                ->where('id_pembayaran', $paymentId)
                ->where('id_user', $userId)
                ->first();

            if (!$payment || empty($payment['bukti_bayar'])) {
                return redirect()->back()->with('error', 'File bukti pembayaran tidak ditemukan.');
            }

            $filePath = WRITEPATH . 'uploads/pembayaran/' . $payment['bukti_bayar'];
            
            if (!file_exists($filePath)) {
                return redirect()->back()->with('error', 'File tidak ditemukan di server.');
            }

            // Log activity
            $this->logActivity($userId, "Downloaded payment proof for payment ID: {$paymentId}");

            return $this->response->download($filePath, null)->setFileName($payment['bukti_bayar']);

        } catch (\Exception $e) {
            log_message('error', 'Download payment proof error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat mengunduh file.');
        }
    }

    /**
     * Cancel payment (only for pending payments)
     */
    public function cancel($paymentId)
    {
        $userId = session('id_user');
        
        try {
            $payment = $this->pembayaranModel
                ->where('id_pembayaran', $paymentId)
                ->where('id_user', $userId)
                ->where('status', 'pending')
                ->first();

            if (!$payment) {
                return redirect()->back()->with('error', 'Pembayaran tidak dapat dibatalkan.');
            }

            $this->db->transStart();

            // Update payment status
            $this->pembayaranModel->update($paymentId, [
                'status' => 'cancelled',
                'keterangan' => 'Dibatalkan oleh presenter pada ' . date('Y-m-d H:i:s')
            ]);

            // Delete uploaded file
            $filePath = WRITEPATH . 'uploads/pembayaran/' . $payment['bukti_bayar'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            // Update event registration status
            $registration = $this->eventRegistrationModel->findUserReg($payment['event_id'], $userId);
            if ($registration) {
                $this->eventRegistrationModel->update($registration['id'], [
                    'status' => 'terdaftar'
                ]);
            }

            // Log activity
            $this->logActivity($userId, "Cancelled payment ID: {$paymentId}");

            $this->db->transComplete();

            if ($this->db->transStatus() === FALSE) {
                throw new \Exception('Transaction failed');
            }

            return redirect()->to('presenter/pembayaran')
                           ->with('success', 'Pembayaran berhasil dibatalkan.');

        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Cancel payment error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat membatalkan pembayaran.');
        }
    }

    /**
     * Validate voucher via AJAX
     */
    public function validateVoucher()
    {
        $voucherCode = $this->request->getPost('voucher_code');
        $eventId = $this->request->getPost('event_id');
        $userId = session('id_user');

        if (!$voucherCode || !$eventId) {
            return $this->response->setJSON([
                'valid' => false,
                'message' => 'Data tidak lengkap.'
            ]);
        }

        try {
            $voucher = $this->voucherModel->where('kode_voucher', strtoupper($voucherCode))->first();

            if (!$voucher) {
                return $this->response->setJSON([
                    'valid' => false,
                    'message' => 'Kode voucher tidak ditemukan.'
                ]);
            }

            if (!$this->isVoucherValid($voucher, $userId, $eventId)) {
                $message = 'Voucher tidak dapat digunakan.';
                
                if ($voucher['status'] !== 'aktif') {
                    $message = 'Voucher tidak aktif.';
                } elseif (strtotime($voucher['masa_berlaku']) < time()) {
                    $message = 'Voucher sudah expired.';
                } else {
                    $usedCount = $this->pembayaranModel
                        ->where('id_voucher', $voucher['id_voucher'])
                        ->where('status', 'verified')
                        ->countAllResults();
                    
                    if ($usedCount >= $voucher['kuota']) {
                        $message = 'Kuota voucher sudah habis.';
                    } else {
                        $message = 'Anda sudah menggunakan voucher ini untuk event ini.';
                    }
                }

                return $this->response->setJSON([
                    'valid' => false,
                    'message' => $message
                ]);
            }

            return $this->response->setJSON([
                'valid' => true,
                'voucher' => $voucher,
                'message' => 'Voucher valid dan dapat digunakan.'
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Validate voucher error: ' . $e->getMessage());
            return $this->response->setJSON([
                'valid' => false,
                'message' => 'Terjadi kesalahan saat validasi voucher.'
            ]);
        }
    }

    /**
     * Get status badge CSS class
     */
    private function getStatusBadgeClass($status)
    {
        $classes = [
            'pending' => 'bg-warning',
            'verified' => 'bg-success',
            'rejected' => 'bg-danger',
            'cancelled' => 'bg-secondary'
        ];

        return $classes[$status] ?? 'bg-secondary';
    }

    /**
     * Get status icon
     */
    private function getStatusIcon($status)
    {
        $icons = [
            'pending' => 'fas fa-clock',
            'verified' => 'fas fa-check-circle',
            'rejected' => 'fas fa-times-circle',
            'cancelled' => 'fas fa-ban'
        ];

        return $icons[$status] ?? 'fas fa-question-circle';
    }

    /**
     * Log activity helper
     */
    private function logActivity($userId, $activity)
    {
        try {
            $this->db->table('log_aktivitas')->insert([
                'id_user' => $userId,
                'aktivitas' => $activity,
                'waktu' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Failed to log activity: ' . $e->getMessage());
        }
    }
}