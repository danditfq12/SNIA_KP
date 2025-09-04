<?php

namespace App\Controllers\Role\Presenter;

use App\Controllers\BaseController;
use App\Models\EventModel;
use App\Models\PembayaranModel;
use App\Models\AbstrakModel;
use App\Models\VoucherModel;
use App\Models\UserModel;

class Event extends BaseController
{
    protected $eventModel;
    protected $pembayaranModel;
    protected $abstrakModel;
    protected $voucherModel;
    protected $userModel;

    public function __construct()
    {
        $this->eventModel = new EventModel();
        $this->pembayaranModel = new PembayaranModel();
        $this->abstrakModel = new AbstrakModel();
        $this->voucherModel = new VoucherModel();
        $this->userModel = new UserModel();
    }

    public function index()
    {
        // Get active events with open registration for presenters
        $events = $this->eventModel->getEventsWithOpenRegistration();
        
        // Get user's registration status for each event
        $userId = session('id_user');
        foreach ($events as &$event) {
            // Get user's registration for this event
            $event['user_registration'] = $this->pembayaranModel
                ->where('event_id', $event['id'])
                ->where('id_user', $userId)
                ->first();
                
            // Get pricing matrix (presenter can only participate offline)
            $event['pricing_matrix'] = $this->eventModel->getPricingMatrix($event['id']);
            $event['participation_options'] = ['offline']; // Presenter only offline
            
            // Get event statistics
            $event['stats'] = $this->eventModel->getEventStats($event['id']);
            
            // Add registration status check for view
            $event['is_registration_open'] = $this->isRegistrationOpen($event);
        }

        $data = [
            'events' => $events,
            'user_role' => session('role')
        ];

        return view('role/presenter/event/index', $data);
    }

    public function detail($eventId)
    {
        $event = $this->eventModel->find($eventId);
        
        if (!$event) {
            return redirect()->to('presenter/events')->with('error', 'Event tidak ditemukan.');
        }

        // Check if registration is still open
        $registrationOpen = $this->isRegistrationOpen($event);
        $abstractOpen = $this->isAbstractSubmissionOpen($event);
        
        // Get user's registration status
        $userId = session('id_user');
        $userRegistration = $this->pembayaranModel
            ->where('event_id', $eventId)
            ->where('id_user', $userId)
            ->first();

        // Get user's abstracts for this event
        $userAbstracts = $this->abstrakModel
            ->where('event_id', $eventId)
            ->where('id_user', $userId)
            ->findAll();

        // Get event statistics
        $stats = $this->getEventStats($eventId);
        
        // Get pricing matrix (presenter only offline)
        $pricingMatrix = $this->getPricingMatrix($eventId);
        $participationOptions = ['offline'];

        $data = [
            'event' => $event,
            'registration_open' => $registrationOpen,
            'abstract_open' => $abstractOpen,
            'user_registration' => $userRegistration,
            'user_abstracts' => $userAbstracts,
            'stats' => $stats,
            'pricing_matrix' => $pricingMatrix,
            'participation_options' => $participationOptions,
            'user_role' => session('role')
        ];

        return view('role/presenter/event/detail', $data);
    }

    public function showRegistrationForm($eventId)
    {
        $event = $this->eventModel->find($eventId);
        
        if (!$event) {
            return redirect()->to('presenter/events')->with('error', 'Event tidak ditemukan.');
        }

        // Check if registration is still open
        if (!$this->isRegistrationOpen($event)) {
            return redirect()->to('presenter/events')->with('error', 'Pendaftaran untuk event ini sudah ditutup.');
        }

        // Check if user already registered
        $userId = session('id_user');
        $existingRegistration = $this->pembayaranModel
            ->where('event_id', $eventId)
            ->where('id_user', $userId)
            ->first();

        if ($existingRegistration) {
            return redirect()->to('presenter/events/detail/' . $eventId)
                ->with('info', 'Anda sudah terdaftar untuk event ini.');
        }

        // Get pricing matrix (presenter only offline)
        $pricingMatrix = $this->getPricingMatrix($eventId);
        $participationOptions = ['offline']; // Presenter can only participate offline
        
        // Get active vouchers
        $activeVouchers = $this->voucherModel
            ->where('status', 'aktif')
            ->where('masa_berlaku >=', date('Y-m-d'))
            ->where('kuota >', 0)
            ->orderBy('masa_berlaku', 'ASC')
            ->findAll();

        $data = [
            'event' => $event,
            'pricing_matrix' => $pricingMatrix,
            'participation_options' => $participationOptions,
            'user_role' => session('role'),
            'active_vouchers' => $activeVouchers,
            'base_amount' => $event['presenter_fee_offline'] ?? 0
        ];

        return view('role/presenter/event/registration_form', $data);
    }

    public function register($eventId)
    {
        $event = $this->eventModel->find($eventId);
        
        if (!$event) {
            return redirect()->to('presenter/events')->with('error', 'Event tidak ditemukan.');
        }

        // Check if registration is still open
        if (!$this->isRegistrationOpen($event)) {
            return redirect()->to('presenter/events')->with('error', 'Pendaftaran untuk event ini sudah ditutup.');
        }

        $validation = \Config\Services::validation();
        
        $rules = [
            'payment_method' => 'required|in_list[bank_transfer,e_wallet,credit_card]',
            'payment_proof' => 'uploaded[payment_proof]|max_size[payment_proof,5120]|ext_in[payment_proof,jpg,jpeg,png,pdf]',
            'voucher_code' => 'permit_empty|max_length[50]'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        $userId = session('id_user');
        $userRole = session('role');
        $participationType = 'offline'; // Presenter can only participate offline
        $voucherCode = $this->request->getPost('voucher_code');

        // Check if user already registered
        $existingRegistration = $this->pembayaranModel
            ->where('event_id', $eventId)
            ->where('id_user', $userId)
            ->first();

        if ($existingRegistration) {
            return redirect()->to('presenter/events/detail/' . $eventId)
                ->with('error', 'Anda sudah terdaftar untuk event ini.');
        }

        // Get base price for presenter (always offline)
        $basePrice = $event['presenter_fee_offline'] ?? 0;
        $finalPrice = $basePrice;
        $voucherId = null;
        $discount = 0;

        // Apply voucher if provided
        if ($voucherCode) {
            $voucher = $this->voucherModel->where('kode_voucher', strtoupper(trim($voucherCode)))->first();
            
            if ($voucher && $voucher['status'] === 'aktif' && strtotime($voucher['masa_berlaku']) > time()) {
                // Check quota
                $usedCount = $this->pembayaranModel->where('id_voucher', $voucher['id_voucher'])->countAllResults();
                
                if ($usedCount < $voucher['kuota']) {
                    $voucherId = $voucher['id_voucher'];
                    
                    if ($voucher['tipe'] === 'percentage') {
                        $discount = ($basePrice * $voucher['nilai'] / 100);
                    } else {
                        $discount = min($voucher['nilai'], $basePrice);
                    }
                    
                    $finalPrice = max(0, $basePrice - $discount);
                } else {
                    return redirect()->back()->withInput()->with('error', 'Kuota voucher sudah habis.');
                }
            } else {
                return redirect()->back()->withInput()->with('error', 'Kode voucher tidak valid atau sudah expired.');
            }
        }

        // Handle file upload
        $file = $this->request->getFile('payment_proof');
        $fileName = '';
        
        if ($file->isValid() && !$file->hasMoved()) {
            // Create upload directory if not exists
            $uploadPath = WRITEPATH . 'uploads/pembayaran/';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            $fileName = 'payment_' . $userId . '_' . $eventId . '_' . time() . '.' . $file->getExtension();
            
            if (!$file->move($uploadPath, $fileName)) {
                return redirect()->back()->withInput()->with('error', 'Gagal mengupload bukti pembayaran.');
            }
        } else {
            return redirect()->back()->withInput()->with('error', 'File bukti pembayaran tidak valid.');
        }

        // Begin database transaction
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // Save payment record
            $paymentData = [
                'id_user' => $userId,
                'event_id' => $eventId,
                'participation_type' => $participationType,
                'metode' => $this->request->getPost('payment_method'),
                'jumlah' => $finalPrice,
                'original_amount' => $basePrice,
                'discount_amount' => $discount,
                'bukti_bayar' => $fileName,
                'status' => 'pending',
                'id_voucher' => $voucherId,
                'tanggal_bayar' => date('Y-m-d H:i:s')
            ];

            $paymentId = $this->pembayaranModel->insert($paymentData);

            if (!$paymentId) {
                throw new \Exception('Failed to create payment record.');
            }

            // Log activity
            $this->logActivity($userId, "Submitted event registration payment for: {$event['title']} (Amount: Rp " . number_format($finalPrice, 0, ',', '.') . ")");

            $db->transComplete();

            if ($db->transStatus() === FALSE) {
                throw new \Exception('Transaction failed.');
            }

            return redirect()->to('presenter/events/detail/' . $eventId)
                ->with('success', 'Pendaftaran berhasil! Silakan tunggu verifikasi pembayaran dari admin.');

        } catch (\Exception $e) {
            $db->transRollback();
            
            // Remove uploaded file on error
            if (!empty($fileName) && file_exists($uploadPath . $fileName)) {
                unlink($uploadPath . $fileName);
            }

            log_message('error', 'Event registration error: ' . $e->getMessage());
            
            return redirect()->back()
                ->withInput()
                ->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function calculatePrice()
    {
        $eventId = $this->request->getPost('event_id');
        $voucherCode = $this->request->getPost('voucher_code');
        $userRole = session('role');

        // Get base price for presenter (always offline)
        $event = $this->eventModel->find($eventId);
        if (!$event) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Event tidak ditemukan'
            ]);
        }

        $basePrice = $event['presenter_fee_offline'] ?? 0;
        $finalPrice = $basePrice;
        $discount = 0;
        $voucherValid = false;
        $voucherMessage = '';

        // Apply voucher if provided
        if ($voucherCode) {
            $voucher = $this->voucherModel->where('kode_voucher', strtoupper(trim($voucherCode)))->first();
            
            if (!$voucher) {
                $voucherMessage = 'Kode voucher tidak ditemukan.';
            } elseif ($voucher['status'] !== 'aktif') {
                $voucherMessage = 'Voucher tidak aktif.';
            } elseif (strtotime($voucher['masa_berlaku']) <= time()) {
                $voucherMessage = 'Voucher sudah expired.';
            } else {
                // Check quota
                $usedCount = $this->pembayaranModel->where('id_voucher', $voucher['id_voucher'])->countAllResults();
                
                if ($usedCount >= $voucher['kuota']) {
                    $voucherMessage = 'Kuota voucher sudah habis.';
                } else {
                    $voucherValid = true;
                    $voucherMessage = 'Voucher berhasil diterapkan!';
                    
                    if ($voucher['tipe'] === 'percentage') {
                        $discount = ($basePrice * $voucher['nilai'] / 100);
                    } else {
                        $discount = min($voucher['nilai'], $basePrice);
                    }
                    
                    $finalPrice = max(0, $basePrice - $discount);
                }
            }
        }

        return $this->response->setJSON([
            'success' => true,
            'base_price' => $basePrice,
            'discount' => $discount,
            'final_price' => $finalPrice,
            'voucher_valid' => $voucherValid,
            'voucher_message' => $voucherMessage,
            'formatted_base_price' => 'Rp ' . number_format($basePrice, 0, ',', '.'),
            'formatted_discount' => 'Rp ' . number_format($discount, 0, ',', '.'),
            'formatted_final_price' => 'Rp ' . number_format($finalPrice, 0, ',', '.')
        ]);
    }

    /**
     * Check if registration is open for an event
     */
    private function isRegistrationOpen($event)
    {
        // If event is not active, registration is closed
        if (!($event['is_active'] ?? true)) {
            return false;
        }
        
        // Check registration deadline
        if (!empty($event['registration_deadline'])) {
            return (strtotime($event['registration_deadline']) > time());
        }
        
        // If no deadline is set, check against event date
        if (!empty($event['event_date'])) {
            $eventDate = strtotime($event['event_date']);
            $currentTime = time();
            
            // Allow registration until 1 day before event
            return ($eventDate > ($currentTime + 86400));
        }
        
        // Default: registration is open
        return true;
    }

    /**
     * Check if abstract submission is open for an event
     */
    private function isAbstractSubmissionOpen($event)
    {
        // Check abstract submission deadline
        if (!empty($event['abstract_deadline'])) {
            return (strtotime($event['abstract_deadline']) > time());
        }
        
        // If no abstract deadline, use registration deadline
        if (!empty($event['registration_deadline'])) {
            return (strtotime($event['registration_deadline']) > time());
        }
        
        // Default: submission is open if registration is open
        return $this->isRegistrationOpen($event);
    }

    /**
     * Get event statistics
     */
    private function getEventStats($eventId)
    {
        $db = \Config\Database::connect();
        
        try {
            // Get total registrations
            $totalRegistrations = $this->pembayaranModel
                ->where('event_id', $eventId)
                ->countAllResults();
            
            // Get total verified registrations
            $verifiedRegistrations = $this->pembayaranModel
                ->where('event_id', $eventId)
                ->where('status', 'verified')
                ->countAllResults();
            
            // Get total abstracts
            $totalAbstracts = $this->abstrakModel
                ->where('event_id', $eventId)
                ->countAllResults();
            
            // Get total revenue
            $revenueResult = $this->pembayaranModel
                ->select('SUM(jumlah) as total_revenue')
                ->where('event_id', $eventId)
                ->where('status', 'verified')
                ->first();
            
            $totalRevenue = $revenueResult['total_revenue'] ?? 0;
            
            return [
                'total_registrations' => $totalRegistrations,
                'verified_registrations' => $verifiedRegistrations,
                'total_abstracts' => $totalAbstracts,
                'total_revenue' => $totalRevenue
            ];
            
        } catch (\Exception $e) {
            log_message('error', 'Error getting event stats: ' . $e->getMessage());
            
            return [
                'total_registrations' => 0,
                'verified_registrations' => 0,
                'total_abstracts' => 0,
                'total_revenue' => 0
            ];
        }
    }

    /**
     * Get pricing matrix for an event
     */
    private function getPricingMatrix($eventId)
    {
        $event = $this->eventModel->find($eventId);
        
        if (!$event) {
            return [];
        }
        
        // For presenter, only offline pricing is relevant
        return [
            'presenter' => [
                'offline' => $event['presenter_fee_offline'] ?? 0
            ]
        ];
    }

    /**
     * Log user activity
     */
    private function logActivity($userId, $activity)
    {
        $db = \Config\Database::connect();
        try {
            $db->table('log_aktivitas')->insert([
                'id_user' => $userId,
                'aktivitas' => $activity,
                'waktu' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            // Silent fail for logging
            log_message('error', 'Failed to log activity: ' . $e->getMessage());
        }
    }
}