<?php

namespace App\Controllers\Role\Presenter;

use App\Controllers\BaseController;
use App\Models\PembayaranModel;
use App\Models\EventModel;
use App\Models\VoucherModel;
use App\Models\AbstrakModel;

class Pembayaran extends BaseController
{
    protected $pembayaranModel;
    protected $eventModel;
    protected $voucherModel;
    protected $abstrakModel;
    protected $db;

    public function __construct()
    {
        $this->pembayaranModel = new PembayaranModel();
        $this->eventModel = new EventModel();
        $this->voucherModel = new VoucherModel();
        $this->abstrakModel = new AbstrakModel();
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        $userId = session('id_user');
        
        // Check if user has accepted abstract
        $acceptedAbstrak = $this->abstrakModel->where('id_user', $userId)
                                             ->where('status', 'diterima')
                                             ->first();
        
        if (!$acceptedAbstrak) {
            return redirect()->to('presenter/dashboard')->with('error', 'Anda harus memiliki abstrak yang diterima terlebih dahulu');
        }

        // Get user's payments
        $pembayaran = $this->pembayaranModel->where('id_user', $userId)->findAll();
        
        // Get event details if abstract has event_id
        $event = null;
        if ($acceptedAbstrak['event_id']) {
            $event = $this->eventModel->find($acceptedAbstrak['event_id']);
        }

        $data = [
            'pembayaran' => $pembayaran,
            'event' => $event,
            'acceptedAbstrak' => $acceptedAbstrak
        ];

        return view('role/presenter/pembayaran/index', $data);
    }

    public function store()
    {
        $userId = session('id_user');
        
        // Check if user has accepted abstract
        $acceptedAbstrak = $this->abstrakModel->where('id_user', $userId)
                                             ->where('status', 'diterima')
                                             ->first();
        
        if (!$acceptedAbstrak) {
            return redirect()->back()->with('error', 'Tidak ada abstrak yang diterima');
        }

        $validation = \Config\Services::validation();
        $validation->setRules([
            'event_id' => 'required|numeric',
            'metode' => 'required|in_list[bank_transfer,e_wallet,credit_card]',
            'jumlah' => 'required|numeric|greater_than[0]',
            'bukti_bayar' => 'uploaded[bukti_bayar]|max_size[bukti_bayar,2048]|ext_in[bukti_bayar,jpg,jpeg,png,pdf]'
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        // Check if user already has pending/verified payment for this event
        $existingPayment = $this->pembayaranModel->where('id_user', $userId)
                                                 ->where('event_id', $this->request->getPost('event_id'))
                                                 ->whereIn('status', ['pending', 'verified'])
                                                 ->first();
        
        if ($existingPayment) {
            return redirect()->back()->with('error', 'Anda sudah memiliki pembayaran untuk event ini');
        }

        // Handle voucher if provided
        $jumlahBayar = $this->request->getPost('jumlah');
        $voucherId = null;
        
        if ($this->request->getPost('kode_voucher')) {
            $voucher = $this->voucherModel->where('kode_voucher', $this->request->getPost('kode_voucher'))
                                          ->where('status', 'aktif')
                                          ->where('masa_berlaku >=', date('Y-m-d'))
                                          ->first();
            
            if ($voucher && $voucher['kuota'] > 0) {
                $voucherId = $voucher['id_voucher'];
                
                if ($voucher['tipe'] == 'persentase') {
                    $jumlahBayar = $jumlahBayar - ($jumlahBayar * $voucher['nilai'] / 100);
                } else {
                    $jumlahBayar = max(0, $jumlahBayar - $voucher['nilai']);
                }
                
                // Update voucher quota
                $this->voucherModel->update($voucher['id_voucher'], [
                    'kuota' => $voucher['kuota'] - 1
                ]);
            }
        }

        // Handle file upload
        $file = $this->request->getFile('bukti_bayar');
        if ($file->isValid() && !$file->hasMoved()) {
            $fileName = $userId . '_' . time() . '_' . $file->getRandomName();
            $file->move(WRITEPATH . 'uploads/pembayaran/', $fileName);
            
            $data = [
                'id_user' => $userId,
                'event_id' => $this->request->getPost('event_id'),
                'metode' => $this->request->getPost('metode'),
                'jumlah' => $jumlahBayar,
                'bukti_bayar' => $fileName,
                'status' => 'pending',
                'tanggal_bayar' => date('Y-m-d H:i:s'),
                'id_voucher' => $voucherId
            ];

            if ($this->pembayaranModel->insert($data)) {
                return redirect()->to('presenter/pembayaran')->with('success', 'Pembayaran berhasil disubmit, menunggu verifikasi admin');
            } else {
                return redirect()->back()->with('error', 'Gagal menyimpan data pembayaran');
            }
        }

        return redirect()->back()->with('error', 'File bukti bayar tidak valid');
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
}