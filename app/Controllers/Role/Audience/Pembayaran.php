<?php

namespace App\Controllers\Role\Audience;

use App\Controllers\BaseController;
use App\Models\PembayaranModel;
use App\Models\EventRegistrationModel;
use App\Models\EventModel;

class Pembayaran extends BaseController
{
    // GET /audience/pembayaran
    public function index()
    {
        $idUser = (int) (session()->get('id_user') ?? 0);

        $payM = new PembayaranModel();
        $rows = $payM->select('pembayaran.*, e.title AS event_title')
                     ->join('events e', 'e.id = pembayaran.event_id', 'left')
                     ->where('pembayaran.id_user', $idUser)
                     ->orderBy('pembayaran.id_pembayaran', 'DESC')
                     ->findAll();

        return view('role/audience/pembayaran/index', ['payments' => $rows]);
    }

    // GET /audience/pembayaran/create/{idReg}
    public function create(int $idReg)
    {
        $idUser = (int) (session()->get('id_user') ?? 0);

        $regM = new EventRegistrationModel();
        $reg  = $regM->getByIdWithEvent($idReg);
        if (!$reg || (int)$reg['id_user'] !== $idUser) {
            return redirect()->to('/audience/pembayaran')->with('error', 'Registrasi tidak valid.');
        }

        // Cegah duplikasi: kalau sudah ada pending utk event yang sama, arahkan ke detail
        $payM  = new PembayaranModel();
        $exist = $payM->where('id_user', $idUser)
                      ->where('event_id', (int)$reg['id_event'])
                      ->where('status', 'pending')
                      ->orderBy('id_pembayaran', 'DESC')
                      ->first();
        if ($exist) {
            return redirect()->to('/audience/pembayaran/detail/'.$exist['id_pembayaran'])
                             ->with('message','Kamu sudah mengajukan pembayaran. Lihat detailnya.');
        }

        // Hitung harga resmi dari server (jangan percaya input user)
        $eventM = new EventModel();
        $amount = (float) $eventM->getEventPrice((int)$reg['id_event'], 'audience', $reg['mode_kehadiran']);

        return view('role/audience/pembayaran/create', [
            'reg'    => $reg,      // berisi event_title, zoom_link, location, mode_kehadiran
            'amount' => $amount,
        ]);
    }

    // POST /audience/pembayaran/store
    public function store()
    {
        $idUser = (int) (session()->get('id_user') ?? 0);

        $rules = [
            'id_reg' => 'required|integer',
            'metode' => 'required|in_list[transfer,gateway]',
            // 'bukti_bayar' -> validasi di bawah hanya jika metode transfer
        ];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $idReg = (int) $this->request->getPost('id_reg');
        $metode = (string) $this->request->getPost('metode');

        $regM = new EventRegistrationModel();
        $reg  = $regM->getByIdWithEvent($idReg);
        if (!$reg || (int)$reg['id_user'] !== $idUser) {
            return redirect()->to('/audience/pembayaran')->with('error','Registrasi tidak valid.');
        }

        // Hitung ulang nominal dari server
        $eventM = new EventModel();
        $amount = (float) $eventM->getEventPrice((int)$reg['id_event'], 'audience', $reg['mode_kehadiran']);

        $payload = [
            'id_user'            => $idUser,
            'event_id'           => (int) $reg['id_event'],
            'jumlah'             => $amount,
            'metode'             => $metode,
            'status'             => 'pending',
            'participation_type' => $reg['mode_kehadiran'],
            'tanggal_bayar'      => date('Y-m-d H:i:s'),
        ];

        // Upload bukti kalau transfer
        if ($metode === 'transfer') {
            $file = $this->request->getFile('bukti_bayar');
            if (!$file || !$file->isValid()) {
                return redirect()->back()->withInput()->with('error','Bukti pembayaran wajib diunggah.');
            }
            $dir = WRITEPATH.'uploads/bukti';
            if (!is_dir($dir)) @mkdir($dir, 0777, true);

            $newName = time().'_'.$file->getRandomName();
            $file->move($dir, $newName);
            $payload['bukti_bayar'] = $newName;
        }

        $payM = new PembayaranModel();
        $payM->insert($payload);
        $payId = (int) $payM->getInsertID();

        // (Opsional) set invoice_no kalau kolomnya ada
        try {
            if (in_array('invoice_no', $payM->allowedFields ?? [], true)) {
                $payM->update($payId, [
                    'invoice_no' => sprintf('INV-%s-%03d-%06d', date('Ymd'), (int)$reg['id_event'], $payId)
                ]);
            }
        } catch (\Throwable $e) {}

        return redirect()->to('/audience/pembayaran/detail/'.$payId)
                         ->with('message','Pembayaran berhasil dikirim. Menunggu verifikasi.');
    }

    // GET /audience/pembayaran/detail/{id}
    public function detail(int $id)
    {
        $idUser = (int) (session()->get('id_user') ?? 0);

        $payM = new PembayaranModel();
        $row  = $payM->select('pembayaran.*, e.title AS event_title')
                     ->join('events e', 'e.id = pembayaran.event_id', 'left')
                     ->where('pembayaran.id_pembayaran', $id)
                     ->first();

        if (!$row || (int)$row['id_user'] !== $idUser) {
            return redirect()->to('/audience/pembayaran')->with('error','Data tidak ditemukan.');
        }

        // fallback nomor invoice kalau kolom belum ada
        $row['invoice_display'] = $row['invoice_no'] ?? ('PAY-'.date('Ymd', strtotime($row['tanggal_bayar'] ?? 'now')).'-'.$row['id_pembayaran']);

        return view('role/audience/pembayaran/detail', ['p' => $row]);
    }

    // GET /audience/pembayaran/download-bukti/{id}
    public function downloadBukti(int $id)
    {
        $idUser = (int) (session()->get('id_user') ?? 0);
        $payM   = new PembayaranModel();
        $row    = $payM->find($id);

        if (!$row || (int)$row['id_user'] !== $idUser) {
            return redirect()->to('/audience/pembayaran')->with('error','Tidak boleh mengakses berkas ini.');
        }
        if (empty($row['bukti_bayar'])) {
            return redirect()->back()->with('error','Bukti belum tersedia.');
        }

        $path = WRITEPATH.'uploads/bukti/'.$row['bukti_bayar'];
        if (!is_file($path)) {
            return redirect()->back()->with('error','File tidak ditemukan.');
        }
        return $this->response->download($path, null)->setFileName($row['bukti_bayar']);
    }

    // GET /audience/pembayaran/cancel/{id}
    public function cancel(int $id)
    {
        $idUser = (int) (session()->get('id_user') ?? 0);
        $payM   = new PembayaranModel();
        $row    = $payM->find($id);

        if (!$row || (int)$row['id_user'] !== $idUser || $row['status'] !== 'pending') {
            return redirect()->to('/audience/pembayaran')->with('error','Tidak bisa dibatalkan.');
        }
        $payM->update($id, ['status' => 'canceled']);
        return redirect()->to('/audience/pembayaran')->with('message','Pembayaran dibatalkan.');
    }

    // POST /audience/pembayaran/validate-voucher
    public function validateVoucher()
    {
        // placeholder sederhana
        return $this->response->setJSON(['ok'=>true, 'valid'=>false, 'message'=>'Voucher tidak diterapkan']);
    }
}
