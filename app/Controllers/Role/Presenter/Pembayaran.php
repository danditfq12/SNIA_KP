<?php

namespace App\Controllers\Role\Presenter;

use App\Controllers\BaseController;
use App\Models\PembayaranModel;
use App\Models\EventModel;
use App\Models\VoucherModel;
use App\Models\AbstrakModel;
use App\Models\EventRegistrationModel;
use App\Models\UserModel;

class Pembayaran extends BaseController
{
    protected $pembayaranModel;
    protected $eventModel;
    protected $voucherModel;
    protected $abstrakModel;
    protected $eventRegistrationModel;
    protected $userModel;
    protected $db;

    public function __construct()
    {
        $this->pembayaranModel = new PembayaranModel();
        $this->eventModel = new EventModel();
        $this->voucherModel = new VoucherModel();
        $this->abstrakModel = new AbstrakModel();
        $this->eventRegistrationModel = new EventRegistrationModel();
        $this->userModel = new UserModel();
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        $userId = session('id_user');
        
        try {
            // Get user's payment history
            $payments = $this->getUserPayments($userId);
            
            // Get payment statistics
            $stats = $this->getPaymentStats($userId);

            $data = [
                'payments' => $payments,
                'stats' => $stats,
                'title' => 'Riwayat Pembayaran'
            ];

            return view('role/presenter/pembayaran/index', $data);

        } catch (\Exception $e) {
            log_message('error', 'Error in presenter pembayaran index: ' . $e->getMessage());
            
            // Return view with empty data and error message
            $data = [
                'payments' => [],
                'stats' => [
                    'total_payments' => 0,
                    'verified_payments' => 0,
                    'pending_payments' => 0,
                    'rejected_payments' => 0,
                    'cancelled_payments' => 0,
                    'total_amount_paid' => 0,
                    'total_amount' => 0, // FIXED: Added missing key
                    'pending_amount' => 0,
                    'verified_amount' => 0
                ],
                'title' => 'Riwayat Pembayaran'
            ];
            
            $data['error'] = 'Terjadi kesalahan saat memuat riwayat pembayaran: ' . $e->getMessage();
            return view('role/presenter/pembayaran/index', $data);
        }
    }

    public function create($eventId)
    {
        $userId = session('id_user');
        
        try {
            $event = $this->eventModel->find($eventId);
            
            if (!$event) {
                return redirect()->to('presenter/events')->with('error', 'Event tidak ditemukan.');
            }

            // Check if registration is still open
            if (!$this->eventModel->isRegistrationOpen($eventId)) {
                return redirect()->to('presenter/events')->with('error', 'Pendaftaran untuk event ini sudah ditutup.');
            }

            // Check if user has accepted abstract for this event
            $acceptedAbstract = $this->abstrakModel
                ->where('id_user', $userId)
                ->where('event_id', $eventId)
                ->where('status', 'diterima')
                ->first();

            if (!$acceptedAbstract) {
                return redirect()->to('presenter/abstrak')
                    ->with('error', 'Anda harus memiliki abstrak yang diterima sebelum melakukan pembayaran.');
            }

            // Check if user already has pending or verified payment
            $existingPayment = $this->pembayaranModel
                ->where('id_user', $userId)
                ->where('event_id', $eventId)
                ->whereIn('status', ['pending', 'verified'])
                ->first();

            if ($existingPayment) {
                return redirect()->to('presenter/pembayaran/detail/' . $existingPayment['id_pembayaran'])
                    ->with('info', 'Anda sudah memiliki pembayaran untuk event ini.');
            }

            // Get user data
            $user = $this->userModel->find($userId);
            if (!$user) {
                return redirect()->to('presenter/events')->with('error', 'Data user tidak ditemukan.');
            }

            // For presenters, participation is always offline
            $participationType = 'offline';
            $price = $event['presenter_fee_offline'] ?? 0;

            // Payment methods with complete details
            $paymentMethods = [
                'transfer_bank' => [
                    'name' => 'Transfer Bank',
                    'icon' => 'fas fa-university',
                    'description' => 'Transfer melalui rekening bank',
                    'details' => [
                        'Bank BNI: 1234567890 a.n. SNIA Event',
                        'Bank BCA: 0987654321 a.n. SNIA Event',
                        'Konfirmasi melalui WhatsApp setelah transfer'
                    ]
                ],
                'gopay' => [
                    'name' => 'GoPay',
                    'icon' => 'fas fa-mobile-alt',
                    'description' => 'Pembayaran via GoPay',
                    'details' => [
                        'Scan QR Code yang akan diberikan',
                        'Atau transfer ke 0812-3456-7890',
                        'Screenshot bukti pembayaran'
                    ]
                ],
                'ovo' => [
                    'name' => 'OVO',
                    'icon' => 'fas fa-wallet',
                    'description' => 'Pembayaran via OVO',
                    'details' => [
                        'Transfer ke 0812-3456-7890',
                        'Pastikan saldo mencukupi',
                        'Screenshot bukti pembayaran'
                    ]
                ],
                'dana' => [
                    'name' => 'DANA',
                    'icon' => 'fas fa-money-bill-wave',
                    'description' => 'Pembayaran via DANA',
                    'details' => [
                        'Transfer ke 0812-3456-7890',
                        'Gunakan fitur transfer DANA',
                        'Screenshot bukti pembayaran'
                    ]
                ],
                'shopeepay' => [
                    'name' => 'ShopeePay',
                    'icon' => 'fas fa-shopping-bag',
                    'description' => 'Pembayaran via ShopeePay',
                    'details' => [
                        'Transfer ke 0812-3456-7890',
                        'Melalui aplikasi Shopee',
                        'Screenshot bukti pembayaran'
                    ]
                ]
            ];

            $data = [
                'event' => $event,
                'abstract' => $acceptedAbstract,
                'user' => $user,
                'participation_type' => $participationType,
                'price' => $price,
                'base_price' => $price,
                'payment_methods' => $paymentMethods,
                'title' => 'Pembayaran - ' . $event['title']
            ];

            return view('role/presenter/pembayaran/create', $data);

        } catch (\Exception $e) {
            log_message('error', 'Error in payment create: ' . $e->getMessage());
            return redirect()->to('presenter/events')->with('error', 'Terjadi kesalahan saat memuat halaman pembayaran: ' . $e->getMessage());
        }
    }

    public function store()
    {
        $userId = session('id_user');
        
        if (!$this->request->getMethod() === 'POST') {
            return redirect()->to('presenter/pembayaran');
        }

        // Validation rules
        $validationRules = [
            'event_id' => 'required|integer',
            'metode' => 'required|in_list[transfer_bank,gopay,ovo,dana,shopeepay]',
            'bukti_bayar' => [
                'rules' => 'uploaded[bukti_bayar]|max_size[bukti_bayar,5120]|ext_in[bukti_bayar,jpg,jpeg,png,pdf]',
                'errors' => [
                    'uploaded' => 'Bukti pembayaran harus diupload',
                    'max_size' => 'Ukuran file maksimal 5MB',
                    'ext_in' => 'File harus berformat JPG, PNG, atau PDF'
                ]
            ],
            'voucher_code' => 'permit_empty'
        ];

        if (!$this->validate($validationRules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        try {
            $eventId = $this->request->getPost('event_id');
            $method = $this->request->getPost('metode');
            $voucherCode = $this->request->getPost('voucher_code');
            $file = $this->request->getFile('bukti_bayar');

            // Verify event and get pricing
            $event = $this->eventModel->find($eventId);
            if (!$event) {
                return redirect()->back()->with('error', 'Event tidak ditemukan.');
            }

            // Check abstract acceptance
            $acceptedAbstract = $this->abstrakModel
                ->where('id_user', $userId)
                ->where('event_id', $eventId)
                ->where('status', 'diterima')
                ->first();

            if (!$acceptedAbstract) {
                return redirect()->back()->with('error', 'Abstrak Anda belum diterima untuk event ini.');
            }

            // Check existing payment
            $existingPayment = $this->pembayaranModel
                ->where('id_user', $userId)
                ->where('event_id', $eventId)
                ->whereIn('status', ['pending', 'verified'])
                ->first();

            if ($existingPayment) {
                return redirect()->back()->with('error', 'Anda sudah memiliki pembayaran untuk event ini.');
            }

            // Calculate price (presenters always offline)
            $participationType = 'offline';
            $originalPrice = $event['presenter_fee_offline'] ?? 0;
            $finalPrice = $originalPrice;
            $discountAmount = 0;
            $voucherId = null;

            // Apply voucher if provided
            if (!empty($voucherCode)) {
                $voucher = $this->checkVoucherValidity($voucherCode);
                if ($voucher) {
                    if ($voucher['tipe'] === 'persentase') {
                        $discountAmount = ($originalPrice * $voucher['nilai']) / 100;
                    } else {
                        $discountAmount = $voucher['nilai'];
                    }
                    $finalPrice = max(0, $originalPrice - $discountAmount);
                    $voucherId = $voucher['id_voucher'];
                } else {
                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'Voucher tidak valid atau sudah tidak berlaku.');
                }
            }

            $this->db->transStart();

            // Handle file upload
            $fileName = $this->handleFileUpload($file, $userId, $eventId);
            
            if (!$fileName) {
                throw new \Exception('Gagal mengupload bukti pembayaran.');
            }

            // Create payment record
            $timezone = new \DateTimeZone('Asia/Jakarta');
            $now = new \DateTime('now', $timezone);

            $paymentData = [
                'id_user' => $userId,
                'event_id' => $eventId,
                'metode' => $method,
                'jumlah' => $finalPrice,
                'bukti_bayar' => $fileName,
                'status' => 'pending',
                'tanggal_bayar' => $now->format('Y-m-d H:i:s'),
                'id_voucher' => $voucherId
            ];

            // Add additional fields if they exist in the database
            if ($this->db->fieldExists('participation_type', 'pembayaran')) {
                $paymentData['participation_type'] = $participationType;
            }
            if ($this->db->fieldExists('original_amount', 'pembayaran')) {
                $paymentData['original_amount'] = $originalPrice;
            }
            if ($this->db->fieldExists('discount_amount', 'pembayaran')) {
                $paymentData['discount_amount'] = $discountAmount;
            }

            $result = $this->pembayaranModel->insert($paymentData);
            $paymentId = $this->pembayaranModel->getInsertID();

            if (!$result) {
                throw new \Exception('Gagal menyimpan data pembayaran.');
            }

            // Update voucher quota if used
            if ($voucherId) {
                $this->db->query("UPDATE voucher SET kuota = kuota - 1 WHERE id_voucher = ?", [$voucherId]);
            }

            // Update event registration status if exists
            $registration = $this->eventRegistrationModel->findUserReg($eventId, $userId);
            if ($registration) {
                $this->eventRegistrationModel->update($registration['id'], [
                    'status' => 'menunggu_pembayaran'
                ]);
            }

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                // Delete uploaded file if transaction failed
                $this->deleteUploadedFile($fileName);
                throw new \Exception('Database transaction failed');
            }

            // Log activity
            $this->logActivity($userId, "Melakukan pembayaran untuk event: {$event['title']} (Rp " . number_format($finalPrice, 0, ',', '.') . ")");

            return redirect()->to('presenter/pembayaran/detail/' . $paymentId)
                ->with('success', 'Pembayaran berhasil disubmit. Silakan tunggu verifikasi dari admin.');

        } catch (\Exception $e) {
            $this->db->transRollback();
            
            // Delete uploaded file if exists
            if (isset($fileName)) {
                $this->deleteUploadedFile($fileName);
            }
            
            log_message('error', 'Error storing payment: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan saat memproses pembayaran: ' . $e->getMessage());
        }
    }

    public function detail($paymentId)
    {
        $userId = session('id_user');
        
        try {
            // Get payment with details
            $payment = $this->getPaymentWithDetails($paymentId, $userId);
            
            if (!$payment) {
                return redirect()->to('presenter/pembayaran')->with('error', 'Pembayaran tidak ditemukan.');
            }

            // Get event info
            $event = $this->eventModel->find($payment['event_id']);
            
            // Get voucher info if used
            $voucher = null;
            if (!empty($payment['id_voucher'])) {
                $voucher = $this->voucherModel->find($payment['id_voucher']);
            }

            // Check if can cancel (only pending payments)
            $canCancel = $payment['status'] === 'pending';

            $data = [
                'payment' => $payment,
                'event' => $event,
                'voucher' => $voucher,
                'can_cancel' => $canCancel,
                'title' => 'Detail Pembayaran'
            ];

            return view('role/presenter/pembayaran/detail', $data);

        } catch (\Exception $e) {
            log_message('error', 'Error in payment detail: ' . $e->getMessage());
            return redirect()->to('presenter/pembayaran')->with('error', 'Terjadi kesalahan saat memuat detail pembayaran: ' . $e->getMessage());
        }
    }

    public function downloadBukti($paymentId)
    {
        $userId = session('id_user');
        
        try {
            // Verify payment belongs to user
            $payment = $this->pembayaranModel
                ->where('id_pembayaran', $paymentId)
                ->where('id_user', $userId)
                ->first();

            if (!$payment) {
                return redirect()->to('presenter/pembayaran')->with('error', 'Pembayaran tidak ditemukan.');
            }

            $filePath = WRITEPATH . 'uploads/pembayaran/' . $payment['bukti_bayar'];
            
            if (!file_exists($filePath)) {
                return redirect()->to('presenter/pembayaran')->with('error', 'File bukti pembayaran tidak ditemukan.');
            }

            // Log download activity
            $this->logActivity($userId, "Mengunduh bukti pembayaran: {$payment['bukti_bayar']}");

            // Force download
            return $this->response->download($filePath, null);

        } catch (\Exception $e) {
            log_message('error', 'Error downloading payment proof: ' . $e->getMessage());
            return redirect()->to('presenter/pembayaran')->with('error', 'Terjadi kesalahan saat mengunduh file.');
        }
    }

    public function cancel($paymentId)
    {
        $userId = session('id_user');
        
        if (!$this->request->getMethod() === 'POST') {
            return redirect()->to('presenter/pembayaran');
        }

        try {
            // Verify payment belongs to user and is pending
            $payment = $this->pembayaranModel
                ->where('id_pembayaran', $paymentId)
                ->where('id_user', $userId)
                ->where('status', 'pending')
                ->first();

            if (!$payment) {
                return redirect()->to('presenter/pembayaran')->with('error', 'Pembayaran tidak ditemukan atau tidak dapat dibatalkan.');
            }

            $this->db->transStart();

            // Update payment status
            $timezone = new \DateTimeZone('Asia/Jakarta');
            $now = new \DateTime('now', $timezone);

            $updateData = [
                'status' => 'cancelled'
            ];

            // Add keterangan field if it exists
            if ($this->db->fieldExists('keterangan', 'pembayaran')) {
                $updateData['keterangan'] = 'Dibatalkan oleh user pada ' . $now->format('Y-m-d H:i:s');
            }

            $result = $this->pembayaranModel->update($paymentId, $updateData);

            if (!$result) {
                throw new \Exception('Gagal membatalkan pembayaran.');
            }

            // Restore voucher quota if used
            if (!empty($payment['id_voucher'])) {
                $this->db->query("UPDATE voucher SET kuota = kuota + 1 WHERE id_voucher = ?", [$payment['id_voucher']]);
            }

            // Update registration status
            $registration = $this->eventRegistrationModel->findUserReg($payment['event_id'], $userId);
            if ($registration) {
                $this->eventRegistrationModel->update($registration['id'], [
                    'status' => 'terdaftar'
                ]);
            }

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                throw new \Exception('Database transaction failed');
            }

            // Log activity
            $this->logActivity($userId, "Membatalkan pembayaran: {$payment['bukti_bayar']}");

            return redirect()->to('presenter/pembayaran')
                ->with('success', 'Pembayaran berhasil dibatalkan.');

        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Error cancelling payment: ' . $e->getMessage());
            
            return redirect()->back()->with('error', 'Terjadi kesalahan saat membatalkan pembayaran: ' . $e->getMessage());
        }
    }

    public function validateVoucher()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400);
        }

        try {
            $voucherCode = $this->request->getPost('voucher_code');
            $eventId = $this->request->getPost('event_id');

            if (empty($voucherCode)) {
                return $this->response->setJSON([
                    'valid' => false,
                    'message' => 'Kode voucher tidak boleh kosong'
                ]);
            }

            $voucher = $this->checkVoucherValidity($voucherCode);

            if (!$voucher) {
                return $this->response->setJSON([
                    'valid' => false,
                    'message' => 'Voucher tidak valid atau sudah tidak berlaku'
                ]);
            }

            // Get event price for calculation
            $event = $this->eventModel->find($eventId);
            $originalPrice = $event['presenter_fee_offline'] ?? 0;

            // Calculate discount
            if ($voucher['tipe'] === 'persentase') {
                $discount = ($originalPrice * $voucher['nilai']) / 100;
            } else {
                $discount = $voucher['nilai'];
            }

            $finalPrice = max(0, $originalPrice - $discount);

            return $this->response->setJSON([
                'valid' => true,
                'voucher' => $voucher,
                'original_price' => $originalPrice,
                'discount' => $discount,
                'final_price' => $finalPrice
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error validating voucher: ' . $e->getMessage());
            return $this->response->setJSON([
                'valid' => false,
                'message' => 'Terjadi kesalahan saat validasi voucher'
            ]);
        }
    }

    // FIXED: Enhanced getPaymentStats with all required keys
    private function getPaymentStats($userId)
    {
        try {
            $result = $this->db->query("
                SELECT 
                    COUNT(*) as total_payments,
                    COUNT(CASE WHEN status = 'verified' THEN 1 END) as verified_payments,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_payments,
                    COUNT(CASE WHEN status = 'rejected' THEN 1 END) as rejected_payments,
                    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_payments,
                    COALESCE(SUM(CASE WHEN status = 'verified' THEN jumlah ELSE 0 END), 0) as verified_amount,
                    COALESCE(SUM(CASE WHEN status = 'pending' THEN jumlah ELSE 0 END), 0) as pending_amount,
                    COALESCE(SUM(jumlah), 0) as total_amount,
                    COALESCE(SUM(CASE WHEN status = 'verified' THEN jumlah ELSE 0 END), 0) as total_amount_paid
                FROM pembayaran 
                WHERE id_user = ?
            ", [$userId])->getRowArray();

            // Ensure all required keys exist with default values
            $defaultStats = [
                'total_payments' => 0,
                'verified_payments' => 0,
                'pending_payments' => 0,
                'rejected_payments' => 0,
                'cancelled_payments' => 0,
                'total_amount_paid' => 0,
                'total_amount' => 0,
                'pending_amount' => 0,
                'verified_amount' => 0
            ];

            // Merge with actual results, ensuring all keys exist
            $stats = array_merge($defaultStats, $result ?: []);
            
            // Convert to integers to avoid type issues
            foreach ($stats as $key => $value) {
                $stats[$key] = (int) $value;
            }

            return $stats;

        } catch (\Exception $e) {
            log_message('error', 'Error getting payment stats: ' . $e->getMessage());
            
            // Return default stats on error
            return [
                'total_payments' => 0,
                'verified_payments' => 0,
                'pending_payments' => 0,
                'rejected_payments' => 0,
                'cancelled_payments' => 0,
                'total_amount_paid' => 0,
                'total_amount' => 0,
                'pending_amount' => 0,
                'verified_amount' => 0
            ];
        }
    }

    private function getUserPayments($userId)
    {
        try {
            return $this->db->query("
                SELECT 
                    p.*,
                    e.title as event_title,
                    e.event_date,
                    e.event_time,
                    v.kode_voucher,
                    v.tipe as voucher_type,
                    v.nilai as voucher_value,
                    verifier.nama_lengkap as verifier_name
                FROM pembayaran p
                LEFT JOIN events e ON e.id = p.event_id
                LEFT JOIN voucher v ON v.id_voucher = p.id_voucher
                LEFT JOIN users verifier ON verifier.id_user = p.verified_by
                WHERE p.id_user = ?
                ORDER BY p.tanggal_bayar DESC
            ", [$userId])->getResultArray();
        } catch (\Exception $e) {
            log_message('error', 'Error getting user payments: ' . $e->getMessage());
            return [];
        }
    }

    private function getPaymentWithDetails($paymentId, $userId)
    {
        try {
            return $this->db->query("
                SELECT 
                    p.*,
                    e.title as event_title,
                    e.event_date,
                    e.event_time,
                    e.location,
                    verifier.nama_lengkap as verifier_name
                FROM pembayaran p
                LEFT JOIN events e ON e.id = p.event_id
                LEFT JOIN users verifier ON verifier.id_user = p.verified_by
                WHERE p.id_pembayaran = ? AND p.id_user = ?
            ", [$paymentId, $userId])->getRowArray();
        } catch (\Exception $e) {
            log_message('error', 'Error getting payment details: ' . $e->getMessage());
            return null;
        }
    }

    private function checkVoucherValidity($voucherCode)
    {
        try {
            $timezone = new \DateTimeZone('Asia/Jakarta');
            $now = new \DateTime('now', $timezone);
            
            return $this->voucherModel
                ->where('kode_voucher', $voucherCode)
                ->where('status', 'aktif')
                ->where('masa_berlaku >=', $now->format('Y-m-d'))
                ->where('kuota >', 0)
                ->first();
        } catch (\Exception $e) {
            log_message('error', 'Error checking voucher validity: ' . $e->getMessage());
            return null;
        }
    }

    private function handleFileUpload($file, $userId, $eventId)
    {
        if (!$file->isValid()) {
            throw new \Exception('File tidak valid: ' . $file->getErrorString());
        }

        // Create upload directory if it doesn't exist
        $uploadPath = WRITEPATH . 'uploads/pembayaran/';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        // Generate unique filename
        $timezone = new \DateTimeZone('Asia/Jakarta');
        $now = new \DateTime('now', $timezone);
        $timestamp = $now->format('YmdHis');
        $extension = $file->getClientExtension();
        $fileName = "payment_{$userId}_{$eventId}_{$timestamp}.{$extension}";

        // Move file
        if (!$file->move($uploadPath, $fileName)) {
            throw new \Exception('Gagal memindahkan file ke direktori upload.');
        }

        return $fileName;
    }

    private function deleteUploadedFile($fileName)
    {
        $filePath = WRITEPATH . 'uploads/pembayaran/' . $fileName;
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    private function logActivity($userId, $activity)
    {
        try {
            $timezone = new \DateTimeZone('Asia/Jakarta');
            $now = new \DateTime('now', $timezone);
            
            $this->db->table('log_aktivitas')->insert([
                'id_user' => $userId,
                'aktivitas' => $activity,
                'waktu' => $now->format('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Failed to log activity: ' . $e->getMessage());
        }
    }
}