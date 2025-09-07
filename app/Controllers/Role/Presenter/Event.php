<?php

namespace App\Controllers\Role\Presenter;

use App\Controllers\BaseController;
use App\Models\EventModel;
use App\Models\PembayaranModel;
use App\Models\AbstrakModel;
use App\Models\VoucherModel;
use App\Models\UserModel;                 // <-- already there
use App\Services\NotificationService;     // <-- ADD

class Event extends BaseController
{
    protected $eventModel;
    protected $pembayaranModel;
    protected $abstrakModel;
    protected $voucherModel;
    protected $userModel;

    public function __construct()
    {
        $this->eventModel      = new EventModel();
        $this->pembayaranModel = new PembayaranModel();
        $this->abstrakModel    = new AbstrakModel();
        $this->voucherModel    = new VoucherModel();
        $this->userModel       = new UserModel();
    }

    public function index()
    {
        $events = $this->eventModel->getEventsWithOpenRegistration();

        $userId = session('id_user');
        foreach ($events as &$event) {
            $event['user_registration']   = $this->pembayaranModel
                ->where('event_id', $event['id'])
                ->where('id_user', $userId)
                ->first();
            $event['pricing_matrix']      = $this->eventModel->getPricingMatrix($event['id']);
            $event['participation_options']= ['offline'];
            $event['stats']               = $this->eventModel->getEventStats($event['id']);
            $event['is_registration_open']= $this->isRegistrationOpen($event);
        }
        unset($event);

        return view('role/presenter/event/index', [
            'events'    => $events,
            'user_role' => session('role'),
        ]);
    }

    public function detail($eventId)
    {
        $event = $this->eventModel->find($eventId);
        if (!$event) {
            return redirect()->to('presenter/events')->with('error', 'Event tidak ditemukan.');
        }

        $registrationOpen = $this->isRegistrationOpen($event);
        $abstractOpen     = $this->isAbstractSubmissionOpen($event);

        $userId           = session('id_user');
        $userRegistration = $this->pembayaranModel
            ->where('event_id', $eventId)
            ->where('id_user', $userId)
            ->first();

        $userAbstracts    = $this->abstrakModel
            ->where('event_id', $eventId)
            ->where('id_user', $userId)
            ->findAll();

        $stats            = $this->getEventStats($eventId);
        $pricingMatrix    = $this->getPricingMatrix($eventId);
        $participationOptions = ['offline'];

        return view('role/presenter/event/detail', [
            'event'                => $event,
            'registration_open'    => $registrationOpen,
            'abstract_open'        => $abstractOpen,
            'user_registration'    => $userRegistration,
            'user_abstracts'       => $userAbstracts,
            'stats'                => $stats,
            'pricing_matrix'       => $pricingMatrix,
            'participation_options'=> $participationOptions,
            'user_role'            => session('role'),
        ]);
    }

    public function showRegistrationForm($eventId)
    {
        $event = $this->eventModel->find($eventId);
        if (!$event) {
            return redirect()->to('presenter/events')->with('error', 'Event tidak ditemukan.');
        }
        if (!$this->isRegistrationOpen($event)) {
            return redirect()->to('presenter/events')->with('error', 'Pendaftaran untuk event ini sudah ditutup.');
        }

        $userId = session('id_user');
        $existingRegistration = $this->pembayaranModel
            ->where('event_id', $eventId)
            ->where('id_user', $userId)
            ->first();
        if ($existingRegistration) {
            return redirect()->to('presenter/events/detail/' . $eventId)->with('info', 'Anda sudah terdaftar untuk event ini.');
        }

        $pricingMatrix       = $this->getPricingMatrix($eventId);
        $participationOptions= ['offline'];

        $activeVouchers = $this->voucherModel
            ->where('status', 'aktif')
            ->where('masa_berlaku >=', date('Y-m-d'))
            ->where('kuota >', 0)
            ->orderBy('masa_berlaku', 'ASC')
            ->findAll();

        return view('role/presenter/event/registration_form', [
            'event'                 => $event,
            'pricing_matrix'        => $pricingMatrix,
            'participation_options' => $participationOptions,
            'user_role'             => session('role'),
            'active_vouchers'       => $activeVouchers,
            'base_amount'           => $event['presenter_fee_offline'] ?? 0,
        ]);
    }

    public function register($eventId)
    {
        $event = $this->eventModel->find($eventId);
        if (!$event) {
            return redirect()->to('presenter/events')->with('error', 'Event tidak ditemukan.');
        }
        if (!$this->isRegistrationOpen($event)) {
            return redirect()->to('presenter/events')->with('error', 'Pendaftaran untuk event ini sudah ditutup.');
        }

        $validation = \Config\Services::validation();
        $rules = [
            'payment_method' => 'required|in_list[bank_transfer,e_wallet,credit_card]',
            'payment_proof'  => 'uploaded[payment_proof]|max_size[payment_proof,5120]|ext_in[payment_proof,jpg,jpeg,png,pdf]',
            'voucher_code'   => 'permit_empty|max_length[50]',
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        $userId             = session('id_user');
        $participationType  = 'offline';
        $voucherCode        = $this->request->getPost('voucher_code');

        $existingRegistration = $this->pembayaranModel
            ->where('event_id', $eventId)
            ->where('id_user', $userId)
            ->first();
        if ($existingRegistration) {
            return redirect()->to('presenter/events/detail/' . $eventId)->with('error', 'Anda sudah terdaftar untuk event ini.');
        }

        $basePrice   = $event['presenter_fee_offline'] ?? 0;
        $finalPrice  = $basePrice;
        $voucherId   = null;
        $discount    = 0;

        if ($voucherCode) {
            $voucher = $this->voucherModel->where('kode_voucher', strtoupper(trim($voucherCode)))->first();
            if ($voucher && $voucher['status'] === 'aktif' && strtotime($voucher['masa_berlaku']) > time()) {
                $usedCount = $this->pembayaranModel->where('id_voucher', $voucher['id_voucher'])->countAllResults();
                if ($usedCount < $voucher['kuota']) {
                    $voucherId = $voucher['id_voucher'];
                    $discount  = ($voucher['tipe'] === 'percentage')
                        ? ($basePrice * $voucher['nilai'] / 100)
                        : min($voucher['nilai'], $basePrice);
                    $finalPrice = max(0, $basePrice - $discount);
                } else {
                    return redirect()->back()->withInput()->with('error', 'Kuota voucher sudah habis.');
                }
            } else {
                return redirect()->back()->withInput()->with('error', 'Kode voucher tidak valid atau sudah expired.');
            }
        }

        $file     = $this->request->getFile('payment_proof');
        $fileName = '';
        if ($file->isValid() && !$file->hasMoved()) {
            $uploadPath = WRITEPATH . 'uploads/pembayaran/';
            if (!is_dir($uploadPath)) mkdir($uploadPath, 0755, true);
            $fileName = 'payment_' . $userId . '_' . $eventId . '_' . time() . '.' . $file->getExtension();
            if (!$file->move($uploadPath, $fileName)) {
                return redirect()->back()->withInput()->with('error', 'Gagal mengupload bukti pembayaran.');
            }
        } else {
            return redirect()->back()->withInput()->with('error', 'File bukti pembayaran tidak valid.');
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $paymentData = [
                'id_user'            => $userId,
                'event_id'           => $eventId,
                'participation_type' => 'presenter',   // untuk routing link notif
                'metode'             => $this->request->getPost('payment_method'),
                'jumlah'             => $finalPrice,
                'original_amount'    => $basePrice,
                'discount_amount'    => $discount,
                'bukti_bayar'        => $fileName,
                'status'             => 'pending',
                'id_voucher'         => $voucherId,
                'tanggal_bayar'      => date('Y-m-d H:i:s'),
            ];

            $paymentId = $this->pembayaranModel->insert($paymentData);
            if (!$paymentId) {
                throw new \Exception('Failed to create payment record.');
            }

            $db->transComplete();
            if ($db->transStatus() === false) {
                throw new \Exception('Transaction failed.');
            }

            // ===== TRIGGER NOTIF: payment pending (admin + user) =====
            try {
                $notif  = new NotificationService();

                // → ke admin
                $admins = $this->userModel->select('id_user')->where('role','admin')->where('status','aktif')->findAll();
                foreach ($admins as $a) {
                    $notif->notify(
                        (int) $a['id_user'],
                        'payment',
                        'Pembayaran baru (Presenter) menunggu verifikasi',
                        "Presenter mengunggah bukti pembayaran untuk event \"{$event['title']}\".",
                        site_url('admin/pembayaran/detail/' . $paymentId)
                    );
                }

                // → ke user (presenter)
                $notif->notify(
                    $userId,
                    'payment',
                    'Pembayaran terkirim',
                    "Bukti pembayaran untuk event \"{$event['title']}\" berhasil dikirim. Menunggu verifikasi admin.",
                    site_url('presenter/pembayaran') // atau detail event
                );
            } catch (\Throwable $e) {
                log_message('error', 'Notif register presenter gagal: ' . $e->getMessage());
            }
            // ===================== END TRIGGER =====================

            $this->logActivity($userId, "Submitted event registration payment for: {$event['title']} (Amount: Rp " . number_format($finalPrice, 0, ',', '.') . ")");

            return redirect()->to('presenter/events/detail/' . $eventId)
                ->with('success', 'Pendaftaran berhasil! Silakan tunggu verifikasi pembayaran dari admin.');

        } catch (\Exception $e) {
            $db->transRollback();
            if (!empty($fileName) && isset($uploadPath) && file_exists($uploadPath . $fileName)) {
                @unlink($uploadPath . $fileName);
            }
            log_message('error', 'Event registration error: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function calculatePrice()
    {
        $eventId    = $this->request->getPost('event_id');
        $voucherCode= $this->request->getPost('voucher_code');

        $event = $this->eventModel->find($eventId);
        if (!$event) {
            return $this->response->setJSON(['success' => false, 'message' => 'Event tidak ditemukan']);
        }

        $basePrice = $event['presenter_fee_offline'] ?? 0;
        $final     = $basePrice;
        $discount  = 0;
        $valid     = false;
        $msg       = '';

        if ($voucherCode) {
            $v = $this->voucherModel->where('kode_voucher', strtoupper(trim($voucherCode)))->first();
            if (!$v)              $msg = 'Kode voucher tidak ditemukan.';
            elseif ($v['status'] !== 'aktif') $msg = 'Voucher tidak aktif.';
            elseif (strtotime($v['masa_berlaku']) <= time()) $msg = 'Voucher sudah expired.';
            else {
                $used = $this->pembayaranModel->where('id_voucher', $v['id_voucher'])->countAllResults();
                if ($used >= $v['kuota']) $msg = 'Kuota voucher sudah habis.';
                else {
                    $valid    = true; $msg = 'Voucher berhasil diterapkan!';
                    $discount = ($v['tipe'] === 'percentage') ? ($basePrice * $v['nilai'] / 100) : min($v['nilai'], $basePrice);
                    $final    = max(0, $basePrice - $discount);
                }
            }
        }

        return $this->response->setJSON([
            'success'               => true,
            'base_price'            => $basePrice,
            'discount'              => $discount,
            'final_price'           => $final,
            'voucher_valid'         => $valid,
            'voucher_message'       => $msg,
            'formatted_base_price'  => 'Rp ' . number_format($basePrice, 0, ',', '.'),
            'formatted_discount'    => 'Rp ' . number_format($discount, 0, ',', '.'),
            'formatted_final_price' => 'Rp ' . number_format($final, 0, ',', '.'),
        ]);
    }

    private function isRegistrationOpen($event)
    {
        if (!($event['is_active'] ?? true)) return false;
        if (!empty($event['registration_deadline'])) return (strtotime($event['registration_deadline']) > time());
        if (!empty($event['event_date'])) {
            $eventDate = strtotime($event['event_date']);
            return ($eventDate > (time() + 86400));
        }
        return true;
    }

    private function isAbstractSubmissionOpen($event)
    {
        if (!empty($event['abstract_deadline'])) return (strtotime($event['abstract_deadline']) > time());
        if (!empty($event['registration_deadline'])) return (strtotime($event['registration_deadline']) > time());
        return $this->isRegistrationOpen($event);
    }

    private function getEventStats($eventId)
    {
        try {
            $totalRegistrations = $this->pembayaranModel->where('event_id', $eventId)->countAllResults();
            $verifiedRegistrations = $this->pembayaranModel->where('event_id', $eventId)->where('status', 'verified')->countAllResults();
            $totalAbstracts = $this->abstrakModel->where('event_id', $eventId)->countAllResults();
            $revenueResult  = $this->pembayaranModel->select('SUM(jumlah) as total_revenue')->where('event_id', $eventId)->where('status', 'verified')->first();
            return [
                'total_registrations'   => $totalRegistrations,
                'verified_registrations'=> $verifiedRegistrations,
                'total_abstracts'       => $totalAbstracts,
                'total_revenue'         => $revenueResult['total_revenue'] ?? 0,
            ];
        } catch (\Exception $e) {
            log_message('error', 'Error getting event stats: ' . $e->getMessage());
            return ['total_registrations'=>0,'verified_registrations'=>0,'total_abstracts'=>0,'total_revenue'=>0];
        }
    }

    private function getPricingMatrix($eventId)
    {
        $event = $this->eventModel->find($eventId);
        if (!$event) return [];
        return ['presenter' => ['offline' => $event['presenter_fee_offline'] ?? 0]];
        }

    private function logActivity($userId, $activity)
    {
        $db = \Config\Database::connect();
        try {
            $db->table('log_aktivitas')->insert([
                'id_user'   => $userId,
                'aktivitas' => $activity,
                'waktu'     => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Failed to log activity: ' . $e->getMessage());
        }
    }
}
