<?php

namespace App\Controllers\Role\Presenter;

use App\Controllers\BaseController;
use App\Models\EventModel;
use App\Models\PembayaranModel;
use App\Models\AbstrakModel;
use App\Models\VoucherModel;

class Event extends BaseController
{
    protected $eventModel;
    protected $pembayaranModel;
    protected $abstrakModel;
    protected $voucherModel;

    public function __construct()
    {
        $this->eventModel = new EventModel();
        $this->pembayaranModel = new PembayaranModel();
        $this->abstrakModel = new AbstrakModel();
        $this->voucherModel = new VoucherModel();
    }

    public function index()
    {
        // Get active events with open registration
        $events = $this->eventModel->getEventsWithOpenRegistration();
        
        // Get user's registration status for each event
        $userId = session('id_user');
        foreach ($events as &$event) {
            $event['user_registration'] = $this->pembayaranModel
                ->where('event_id', $event['id'])
                ->where('id_user', $userId)
                ->first();
                
            $event['pricing_matrix'] = $this->eventModel->getPricingMatrix($event['id']);
            $event['participation_options'] = $this->eventModel->getParticipationOptions($event['id']);
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
        $registrationOpen = $this->eventModel->isRegistrationOpen($eventId);
        $abstractOpen = $this->eventModel->isAbstractSubmissionOpen($eventId);
        
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
        $stats = $this->eventModel->getEventStats($eventId);
        
        // Get pricing matrix
        $pricingMatrix = $this->eventModel->getPricingMatrix($eventId);
        $participationOptions = $this->eventModel->getParticipationOptions($eventId);

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
        if (!$this->eventModel->isRegistrationOpen($eventId)) {
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

        // Get pricing matrix and participation options
        $pricingMatrix = $this->eventModel->getPricingMatrix($eventId);
        $participationOptions = $this->eventModel->getParticipationOptions($eventId);
        
        // Get active vouchers
        $activeVouchers = $this->voucherModel
            ->where('status', 'aktif')
            ->where('masa_berlaku >=', date('Y-m-d'))
            ->findAll();

        $data = [
            'event' => $event,
            'pricing_matrix' => $pricingMatrix,
            'participation_options' => $participationOptions,
            'user_role' => session('role'),
            'active_vouchers' => $activeVouchers
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
        if (!$this->eventModel->isRegistrationOpen($eventId)) {
            return redirect()->to('presenter/events')->with('error', 'Pendaftaran untuk event ini sudah ditutup.');
        }

        $validation = \Config\Services::validation();
        
        $rules = [
            'participation_type' => 'required|in_list[online,offline]',
            'payment_method' => 'required|in_list[transfer,ewallet,cash]',
            'payment_proof' => 'uploaded[payment_proof]|max_size[payment_proof,5120]|ext_in[payment_proof,jpg,jpeg,png,pdf]'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        $userId = session('id_user');
        $userRole = session('role');
        $participationType = $this->request->getPost('participation_type');
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

        // Validate participation type is available for this event
        $participationOptions = $this->eventModel->getParticipationOptions($eventId);
        if (!in_array($participationType, $participationOptions)) {
            return redirect()->back()->withInput()
                ->with('error', 'Tipe partisipasi tidak tersedia untuk event ini.');
        }

        // Get price for user role and participation type
        $basePrice = $this->eventModel->getEventPrice($eventId, $userRole, $participationType);
        $finalPrice = $basePrice;
        $voucherId = null;
        $discount = 0;

        // Apply voucher if provided
        if ($voucherCode) {
            $voucher = $this->voucherModel->where('kode_voucher', strtoupper($voucherCode))->first();
            
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
                }
            }
        }

        // Handle file upload
        $file = $this->request->getFile('payment_proof');
        $fileName = '';
        
        if ($file->isValid() && !$file->hasMoved()) {
            $fileName = 'payment_' . $userId . '_' . $eventId . '_' . time() . '.' . $file->getExtension();
            $file->move(WRITEPATH . 'uploads/pembayaran/', $fileName);
        } else {
            return redirect()->back()->withInput()->with('error', 'Gagal mengupload bukti pembayaran.');
        }

        // Save payment record
        $paymentData = [
            'id_user' => $userId,
            'event_id' => $eventId,
            'participation_type' => $participationType,
            'metode' => $this->request->getPost('payment_method'),
            'jumlah' => $finalPrice,
            'bukti_bayar' => $fileName,
            'status' => 'pending',
            'id_voucher' => $voucherId,
            'tanggal_bayar' => date('Y-m-d H:i:s')
        ];

        if ($this->pembayaranModel->save($paymentData)) {
            return redirect()->to('presenter/events/detail/' . $eventId)
                ->with('success', 'Pendaftaran berhasil! Silakan tunggu verifikasi pembayaran.');
        } else {
            // Remove uploaded file if database save fails
            if (file_exists(WRITEPATH . 'uploads/pembayaran/' . $fileName)) {
                unlink(WRITEPATH . 'uploads/pembayaran/' . $fileName);
            }
            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan data pendaftaran.');
        }
    }

    public function calculatePrice()
    {
        $eventId = $this->request->getPost('event_id');
        $participationType = $this->request->getPost('participation_type');
        $voucherCode = $this->request->getPost('voucher_code');
        $userRole = session('role');

        // Get base price
        $basePrice = $this->eventModel->getEventPrice($eventId, $userRole, $participationType);
        $finalPrice = $basePrice;
        $discount = 0;
        $voucherValid = false;
        $voucherMessage = '';

        // Apply voucher if provided
        if ($voucherCode) {
            $voucher = $this->voucherModel->where('kode_voucher', strtoupper($voucherCode))->first();
            
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
}