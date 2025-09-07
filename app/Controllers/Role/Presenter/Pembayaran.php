<?php

namespace App\Controllers\Role\Presenter;

use App\Controllers\BaseController;
use App\Models\PembayaranModel;
use App\Models\EventModel;
use App\Models\VoucherModel;
use App\Models\AbstrakModel;
use App\Models\UserModel;                 // <-- ADD
use App\Services\NotificationService;     // <-- ADD

class Pembayaran extends BaseController
{
    protected $pembayaranModel;
    protected $eventModel;
    protected $voucherModel;
    protected $abstrakModel;
    protected $userModel;
    protected $db;

    public function __construct()
    {
        $this->pembayaranModel = new PembayaranModel();
        $this->eventModel = new EventModel();
        $this->voucherModel = new VoucherModel();
        $this->abstrakModel = new AbstrakModel();
        $this->userModel = new UserModel();
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        $userId = session('id_user');
        
        if (!$userId) {
            return redirect()->to('auth/login')->with('error', 'Please login first');
        }
        
        // Get user's accepted abstracts (required for presenter payment)
        $acceptedAbstracts = $this->abstrakModel
            ->select('abstrak.*, events.title as event_title, events.presenter_fee_offline, kategori_abstrak.nama_kategori')
            ->join('events', 'events.id = abstrak.event_id', 'left')
            ->join('kategori_abstrak', 'kategori_abstrak.id_kategori = abstrak.id_kategori', 'left')
            ->where('abstrak.id_user', $userId)
            ->where('abstrak.status', 'diterima')
            ->findAll();
        
        // Get user's payments with event and verification details
        $payments = $this->pembayaranModel
            ->select('
                pembayaran.*, 
                events.title as event_title,
                events.event_date,
                events.event_time,
                events.location,
                verifier.nama_lengkap as verified_by_name,
                voucher.kode_voucher,
                voucher.tipe as voucher_type,
                voucher.nilai as voucher_value
            ')
            ->join('events', 'events.id = pembayaran.event_id', 'left')
            ->join('users as verifier', 'verifier.id_user = pembayaran.verified_by', 'left')
            ->join('voucher', 'voucher.id_voucher = pembayaran.id_voucher', 'left')
            ->where('pembayaran.id_user', $userId)
            ->orderBy('pembayaran.tanggal_bayar', 'DESC')
            ->findAll();

        // Get available events for payment (events with accepted abstracts but no payment yet)
        $availableEvents = [];
        foreach ($acceptedAbstracts as $abstract) {
            $existingPayment = array_filter($payments, function($payment) use ($abstract) {
                return $payment['event_id'] == $abstract['event_id'];
            });
            
            if (empty($existingPayment) && $abstract['event_id']) {
                $availableEvents[] = $abstract;
            }
        }

        // Get payment statistics
        $paymentStats = [
            'total_payments' => count($payments),
            'verified_payments' => count(array_filter($payments, fn($p) => $p['status'] === 'verified')),
            'pending_payments' => count(array_filter($payments, fn($p) => $p['status'] === 'pending')),
            'rejected_payments' => count(array_filter($payments, fn($p) => $p['status'] === 'rejected')),
            'total_paid' => array_sum(array_map(fn($p) => $p['status'] === 'verified' ? $p['jumlah'] : 0, $payments))
        ];

        $data = [
            'payments' => $payments,
            'accepted_abstracts' => $acceptedAbstracts,
            'available_events' => $availableEvents,
            'payment_stats' => $paymentStats,
            'can_make_payment' => !empty($availableEvents)
        ];

        return view('role/presenter/pembayaran/index', $data);
    }

    public function create($eventId = null)
    {
        $userId = session('id_user');
        
        if (!$userId) {
            return redirect()->to('auth/login')->with('error', 'Please login first');
        }
        
        if (!$eventId) {
            return redirect()->back()->with('error', 'Event ID is required.');
        }

        $acceptedAbstract = $this->abstrakModel
            ->where('id_user', $userId)
            ->where('event_id', $eventId)
            ->where('status', 'diterima')
            ->first();

        if (!$acceptedAbstract) {
            return redirect()->to('presenter/pembayaran')
                ->with('error', 'You must have an accepted abstract for this event before making payment.');
        }

        $existingPayment = $this->pembayaranModel
            ->where('id_user', $userId)
            ->where('event_id', $eventId)
            ->first();

        if ($existingPayment) {
            return redirect()->to('presenter/pembayaran')
                ->with('info', 'Payment already exists for this event.');
        }

        $event = $this->eventModel->find($eventId);
        if (!$event) {
            return redirect()->back()->with('error', 'Event not found.');
        }

        $baseAmount = $event['presenter_fee_offline'] ?? 0;

        $activeVouchers = $this->voucherModel
            ->where('status', 'aktif')
            ->where('masa_berlaku >=', date('Y-m-d'))
            ->where('kuota >', 0)
            ->orderBy('masa_berlaku', 'ASC')
            ->findAll();

        $data = [
            'event' => $event,
            'abstract' => $acceptedAbstract,
            'base_amount' => $baseAmount,
            'active_vouchers' => $activeVouchers,
            'payment_methods' => [
                'bank_transfer' => 'Bank Transfer',
                'e_wallet' => 'E-Wallet (OVO/GoPay/DANA)',
                'credit_card' => 'Credit Card'
            ]
        ];

        return view('role/presenter/pembayaran/create', $data);
    }

    public function store()
    {
        $userId = session('id_user');
        
        if (!$userId) {
            return redirect()->to('auth/login')->with('error', 'Please login first');
        }
        
        $validation = \Config\Services::validation();
        $validation->setRules([
            'event_id' => 'required|numeric',
            'metode' => 'required|in_list[bank_transfer,e_wallet,credit_card]',
            'bukti_bayar' => 'uploaded[bukti_bayar]|max_size[bukti_bayar,5120]|ext_in[bukti_bayar,jpg,jpeg,png,pdf]',
            'kode_voucher' => 'permit_empty|max_length[50]'
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $validation->getErrors());
        }

        $eventId = $this->request->getPost('event_id');
        $metode = $this->request->getPost('metode');
        $kodeVoucher = $this->request->getPost('kode_voucher');

        $acceptedAbstract = $this->abstrakModel
            ->where('id_user', $userId)
            ->where('event_id', $eventId)
            ->where('status', 'diterima')
            ->first();

        if (!$acceptedAbstract) {
            return redirect()->back()
                ->with('error', 'No accepted abstract found for this event.');
        }

        $existingPayment = $this->pembayaranModel
            ->where('id_user', $userId)
            ->where('event_id', $eventId)
            ->first();

        if ($existingPayment) {
            return redirect()->to('presenter/pembayaran')
                ->with('error', 'Payment already exists for this event.');
        }

        $event = $this->eventModel->find($eventId);
        if (!$event) {
            return redirect()->back()->with('error', 'Event not found.');
        }

        $baseAmount = $event['presenter_fee_offline'] ?? 0;
        $finalAmount = $baseAmount;
        $voucherId = null;
        $discount = 0;

        if (!empty($kodeVoucher)) {
            $voucher = $this->voucherModel
                ->where('kode_voucher', strtoupper(trim($kodeVoucher)))
                ->where('status', 'aktif')
                ->where('masa_berlaku >=', date('Y-m-d'))
                ->first();

            if ($voucher) {
                $usedCount = $this->pembayaranModel
                    ->where('id_voucher', $voucher['id_voucher'])
                    ->countAllResults();

                if ($usedCount < $voucher['kuota']) {
                    $voucherId = $voucher['id_voucher'];
                    
                    if ($voucher['tipe'] === 'percentage') {
                        $discount = ($baseAmount * $voucher['nilai'] / 100);
                    } else {
                        $discount = min($voucher['nilai'], $baseAmount);
                    }
                    
                    $finalAmount = max(0, $baseAmount - $discount);
                } else {
                    return redirect()->back()
                        ->withInput()
                        ->with('error', 'Voucher quota has been exhausted.');
                }
            } else {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Invalid or expired voucher code.');
            }
        }

        // Handle file upload
        $file = $this->request->getFile('bukti_bayar');
        $fileName = '';

        if ($file->isValid() && !$file->hasMoved()) {
            $uploadPath = WRITEPATH . 'uploads/pembayaran/';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            $fileName = 'payment_' . $userId . '_' . $eventId . '_' . time() . '.' . $file->getExtension();
            
            if (!$file->move($uploadPath, $fileName)) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', 'Failed to upload payment proof.');
            }
        } else {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Invalid payment proof file.');
        }

        // Begin database transaction
        $this->db->transStart();

        try {
            // Create payment record
            $paymentData = [
                'id_user' => $userId,
                'event_id' => $eventId,
                'participation_type' => 'presenter', // <-- penting untuk link
                'metode' => $metode,
                'jumlah' => $finalAmount,
                'bukti_bayar' => $fileName,
                'status' => 'pending',
                'id_voucher' => $voucherId,
                'tanggal_bayar' => date('Y-m-d H:i:s')
            ];

            $paymentId = $this->pembayaranModel->insert($paymentData);

            if (!$paymentId) {
                throw new \Exception('Failed to create payment record.');
            }

            // === TRIGGER NOTIF: pembayaran pending (ke admin) ===
            try {
                $admins = (new UserModel())
                    ->where('role', 'admin')
                    ->where('status', 'aktif')
                    ->select('id_user')
                    ->findAll();

                if (!empty($admins)) {
                    $notif = new NotificationService();
                    $eventTitle = $event['title'] ?? 'Event SNIA';
                    foreach ($admins as $a) {
                        $notif->notify(
                            (int) $a['id_user'],
                            'payment',
                            'Pembayaran baru (Presenter) menunggu verifikasi',
                            "Presenter mengunggah bukti pembayaran untuk {$eventTitle}.",
                            site_url('admin/pembayaran/detail/' . $paymentId)
                        );
                    }
                }
            } catch (\Throwable $e) {
                log_message('error', 'Gagal kirim notifikasi ke admin (presenter payment pending): ' . $e->getMessage());
            }
            // === END TRIGGER ===

            // Log activity
            $this->logActivity($userId, "Submitted payment for event: {$event['title']} (Amount: Rp " . number_format($finalAmount, 0, ',', '.') . ")");

            $this->db->transComplete();

            if ($this->db->transStatus() === FALSE) {
                throw new \Exception('Transaction failed.');
            }

            return redirect()->to('presenter/pembayaran')
                ->with('success', 'Payment submitted successfully! Please wait for admin verification.');

        } catch (\Exception $e) {
            $this->db->transRollback();
            
            // Remove uploaded file on error
            if (!empty($fileName) && file_exists($uploadPath . $fileName)) {
                unlink($uploadPath . $fileName);
            }

            log_message('error', 'Payment submission error: ' . $e->getMessage());
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function detail($paymentId)
    {
        $userId = session('id_user');

        if (!$userId) {
            return redirect()->to('auth/login')->with('error', 'Please login first');
        }

        $payment = $this->pembayaranModel
            ->select('
                pembayaran.*,
                events.title as event_title,
                events.event_date,
                events.event_time,
                events.location,
                events.presenter_fee_offline,
                verifier.nama_lengkap as verified_by_name,
                voucher.kode_voucher,
                voucher.tipe as voucher_type,
                voucher.nilai as voucher_value
            ')
            ->join('events', 'events.id = pembayaran.event_id', 'left')
            ->join('users as verifier', 'verifier.id_user = pembayaran.verified_by', 'left')
            ->join('voucher', 'voucher.id_voucher = pembayaran.id_voucher', 'left')
            ->where('pembayaran.id_pembayaran', $paymentId)
            ->where('pembayaran.id_user', $userId)
            ->first();

        if (!$payment) {
            return redirect()->to('presenter/pembayaran')
                ->with('error', 'Payment not found.');
        }

        // Get related abstract
        $abstract = $this->abstrakModel
            ->select('abstrak.*, kategori_abstrak.nama_kategori')
            ->join('kategori_abstrak', 'kategori_abstrak.id_kategori = abstrak.id_kategori', 'left')
            ->where('abstrak.id_user', $userId)
            ->where('abstrak.event_id', $payment['event_id'])
            ->where('abstrak.status', 'diterima')
            ->first();

        $data = [
            'payment' => $payment,
            'abstract' => $abstract
        ];

        return view('role/presenter/pembayaran/detail', $data);
    }

    public function downloadBukti($paymentId)
    {
        $userId = session('id_user');

        if (!$userId) {
            return redirect()->to('auth/login')->with('error', 'Please login first');
        }

        $payment = $this->pembayaranModel
            ->where('id_pembayaran', $paymentId)
            ->where('id_user', $userId)
            ->first();

        if (!$payment) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Payment not found.');
        }

        $filePath = WRITEPATH . 'uploads/pembayaran/' . $payment['bukti_bayar'];

        if (!file_exists($filePath)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Payment proof file not found.');
        }

        return $this->response->download($filePath, null);
    }

    public function validateVoucher()
    {
        $kodeVoucher = $this->request->getPost('kode_voucher');
        $amount = $this->request->getPost('amount');

        if (empty($kodeVoucher) || empty($amount)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Voucher code and amount are required.'
            ]);
        }

        $voucher = $this->voucherModel
            ->where('kode_voucher', strtoupper(trim($kodeVoucher)))
            ->where('status', 'aktif')
            ->where('masa_berlaku >=', date('Y-m-d'))
            ->first();

        if (!$voucher) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Invalid or expired voucher code.'
            ]);
        }

        // Check quota
        $usedCount = $this->pembayaranModel
            ->where('id_voucher', $voucher['id_voucher'])
            ->countAllResults();

        if ($usedCount >= $voucher['kuota']) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Voucher quota has been exhausted.'
            ]);
        }

        // Calculate discount
        $discount = 0;
        if ($voucher['tipe'] === 'percentage') {
            $discount = ($amount * $voucher['nilai'] / 100);
        } else {
            $discount = min($voucher['nilai'], $amount);
        }

        $finalAmount = max(0, $amount - $discount);

        return $this->response->setJSON([
            'success' => true,
            'voucher' => $voucher,
            'discount' => $discount,
            'final_amount' => $finalAmount,
            'formatted_discount' => 'Rp ' . number_format($discount, 0, ',', '.'),
            'formatted_final_amount' => 'Rp ' . number_format($finalAmount, 0, ',', '.'),
            'message' => 'Voucher applied successfully!'
        ]);
    }

    public function cancel($paymentId)
    {
        $userId = session('id_user');

        if (!$userId) {
            return redirect()->to('auth/login')->with('error', 'Please login first');
        }

        $payment = $this->pembayaranModel
            ->where('id_pembayaran', $paymentId)
            ->where('id_user', $userId)
            ->where('status', 'pending')
            ->first();

        if (!$payment) {
            return redirect()->back()
                ->with('error', 'Payment not found or cannot be cancelled.');
        }

        $this->db->transStart();

        try {
            $filePath = WRITEPATH . 'uploads/pembayaran/' . $payment['bukti_bayar'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            $this->pembayaranModel->delete($paymentId);

            $this->logActivity($userId, "Cancelled payment (ID: {$paymentId})");

            $this->db->transComplete();

            if ($this->db->transStatus() === FALSE) {
                throw new \Exception('Failed to cancel payment.');
            }

            return redirect()->to('presenter/pembayaran')
                ->with('success', 'Payment cancelled successfully.');

        } catch (\Exception $e) {
            $this->db->transRollback();
            
            return redirect()->back()
                ->with('error', 'Error cancelling payment: ' . $e->getMessage());
        }
    }

    public function checkVoucher()
    {
        $kodeVoucher = $this->request->getPost('kode_voucher');
        
        $voucher = $this->voucherModel->where('kode_voucher', $kodeVoucher)
                                      ->where('status', 'aktif')
                                      ->where('masa_berlaku >=', date('Y-m-d'))
                                      ->first();
        
        if ($voucher && $voucher['kuota'] > 0) {
            return $this->response->setJSON([
                'success' => true,
                'voucher' => $voucher
            ]);
        }

        return $this->response->setJSON([
            'success' => false,
            'message' => 'Voucher tidak valid atau sudah habis'
        ]);
    }

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
