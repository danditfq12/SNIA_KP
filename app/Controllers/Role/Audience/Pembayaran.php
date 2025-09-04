<?php

namespace App\Controllers\Role\Audience;

use App\Controllers\BaseController;
use App\Models\EventModel;
use App\Models\EventRegistrationModel;
use App\Models\PembayaranModel;

class Pembayaran extends BaseController
{
    /** Riwayat pembayaran saya */
    public function index()
    {
        $idUser = (int) (session()->get('id_user') ?? 0);

        $rows = (new PembayaranModel())
            ->select('pembayaran.*, e.title AS event_title, e.event_date, e.event_time')
            ->join('events e', 'e.id = pembayaran.event_id', 'left')
            ->where('pembayaran.id_user', $idUser)
            ->orderBy('pembayaran.id_pembayaran','DESC')
            ->findAll();

        return view('role/audience/pembayaran/index', ['payments'=>$rows]);
    }

    /** Halaman instruksi rekening (HARD-CODED di view) */
    public function instruction(int $idReg)
    {
        $idUser = (int) (session()->get('id_user') ?? 0);

        $regM = new EventRegistrationModel();
        $reg  = $regM->getByIdWithEvent($idReg);
        if (!$reg || (int)$reg['id_user'] !== $idUser) {
            return redirect()->to('/audience/events')->with('error','Registrasi tidak valid.');
        }

        $eventM = new EventModel();
        $amount = (float) $eventM->getEventPrice((int)$reg['id_event'], 'audience', $reg['mode_kehadiran']);
        if ($amount < 0) $amount = 0;

        return view('role/audience/pembayaran/instruction', [
            'reg'    => $reg,
            'amount' => $amount,
        ]);
    }

    /** Form upload bukti */
    public function create(int $idReg)
    {
        $idUser = (int) (session()->get('id_user') ?? 0);

        $regM = new EventRegistrationModel();
        $reg  = $regM->getByIdWithEvent($idReg);
        if (!$reg || (int)$reg['id_user'] !== $idUser) {
            return redirect()->to('/audience/pembayaran')->with('error','Registrasi tidak valid.');
        }

        // Jika sudah ada pending untuk event ini → ke detail
        $payM  = new PembayaranModel();
        $exist = $payM->where('id_user', $idUser)
                      ->where('event_id', (int)$reg['id_event'])
                      ->where('status', 'pending')
                      ->first();
        if ($exist) {
            return redirect()->to('/audience/pembayaran/detail/'.$exist['id_pembayaran'])
                             ->with('message','Kamu sudah mengirim pembayaran. Lihat detailnya.');
        }

        $amount = (float) (new EventModel())->getEventPrice((int)$reg['id_event'], 'audience', $reg['mode_kehadiran']);
        if ($amount < 0) $amount = 0;

        return view('role/audience/pembayaran/create', [
            'reg'    => $reg,
            'amount' => $amount,
        ]);
    }

    /** Simpan pembayaran (pending) */
    public function store()
    {
        $idUser = (int) (session()->get('id_user') ?? 0);

        $rules = [
            'id_reg'      => 'required|integer',
            'bukti_bayar' => 'uploaded[bukti_bayar]|max_size[bukti_bayar,5120]|ext_in[bukti_bayar,jpg,jpeg,png,pdf]',
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $regM = new EventRegistrationModel();
        $reg  = $regM->getByIdWithEvent((int) $this->request->getPost('id_reg'));
        if (!$reg || (int)$reg['id_user'] !== $idUser) {
            return redirect()->to('/audience/pembayaran')->with('error','Registrasi tidak valid.');
        }

        // Hindari duplikasi
        $payM  = new PembayaranModel();
        $exist = $payM->where('id_user', $idUser)
                      ->where('event_id', (int)$reg['id_event'])
                      ->where('status', 'pending')
                      ->first();
        if ($exist) {
            return redirect()->to('/audience/pembayaran/detail/'.$exist['id_pembayaran'])
                             ->with('message','Kamu sudah mengirim pembayaran. Lihat detailnya.');
        }

        $eventM = new EventModel();
        $amount = (float) $eventM->getEventPrice((int)$reg['id_event'], 'audience', $reg['mode_kehadiran']);
        if ($amount < 0) $amount = 0;

        // Upload bukti
        $file = $this->request->getFile('bukti_bayar');
        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('error','File bukti tidak valid.');
        }

        $dir = WRITEPATH . 'uploads/bukti';
        if (!is_dir($dir)) {
            @mkdir($dir, 0777, true);
        }
        $newName = time().'_'.$file->getRandomName();
        try {
            $file->move($dir, $newName);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error','Gagal menyimpan file: '.$e->getMessage());
        }

        // Simpan record pembayaran
        $payM->insert([
            'id_user'            => $idUser,
            'event_id'           => (int) $reg['id_event'],
            'jumlah'             => $amount,
            'metode'             => 'transfer',
            'status'             => 'pending',
            'tanggal_bayar'      => date('Y-m-d H:i:s'),
            'bukti_bayar'        => $newName,
            'participation_type' => $reg['mode_kehadiran'] ?? null,
        ]);

        $payId = (int) $payM->getInsertID();

        return redirect()->to('/audience/pembayaran/detail/'.$payId)
                         ->with('message','Bukti terkirim. Menunggu verifikasi admin.');
    }

    /** Detail pembayaran saya */
    public function detail(int $id)
    {
        $idUser = (int) (session()->get('id_user') ?? 0);

        $p = (new PembayaranModel())
            ->select('pembayaran.*, e.title AS event_title, e.event_date, e.event_time')
            ->join('events e', 'e.id = pembayaran.event_id', 'left')
            ->find($id);

        if (!$p || (int)$p['id_user'] !== $idUser) {
            return redirect()->to('/audience/pembayaran')->with('error','Data tidak ditemukan.');
        }

        $p['invoice_display'] = 'PAY-'.date('Ymd', strtotime($p['tanggal_bayar'] ?? 'now')).'-'.$p['id_pembayaran'];

        return view('role/audience/pembayaran/detail', ['p' => $p]);
    }

    /** Unduh bukti milik sendiri */
    public function downloadBukti(int $id)
    {
        $idUser = (int) (session()->get('id_user') ?? 0);
        $p      = (new PembayaranModel())->find($id);

        if (!$p || (int)$p['id_user'] !== $idUser) {
            return redirect()->back()->with('error','Tidak diizinkan.');
        }

        $pathA = WRITEPATH.'uploads/bukti/'.($p['bukti_bayar'] ?? '');
        $pathB = WRITEPATH.'uploads/pembayaran/'.($p['bukti_bayar'] ?? '');
        $file  = is_file($pathA) ? $pathA : $pathB;

        if (!is_file($file)) {
            return redirect()->back()->with('error','File tidak ditemukan.');
        }

        return $this->response->download($file, null)->setFileName($p['bukti_bayar']);
    }

    /** Batalkan pembayaran pending */
    public function cancel(int $id)
    {
        $idUser = (int) (session()->get('id_user') ?? 0);
        $payM   = new PembayaranModel();
        $p      = $payM->find($id);

        if (!$p || (int)$p['id_user'] !== $idUser || ($p['status'] ?? '') !== 'pending') {
            return redirect()->to('/audience/pembayaran')->with('error','Tidak bisa dibatalkan.');
        }

        $payM->update($id, ['status'=>'canceled']);
        return redirect()->to('/audience/pembayaran')->with('message','Pembayaran dibatalkan.');
    }

    /** Voucher nonaktif (opsional) */
    public function validateVoucher()
    {
        return $this->response->setJSON(['ok'=>false,'message'=>'Fitur voucher nonaktif pada mode pembayaran manual.']);
    }
}
