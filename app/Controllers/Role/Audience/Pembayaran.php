<?php

namespace App\Controllers\Role\Audience;

use App\Controllers\BaseController;
use App\Models\EventModel;
use App\Models\EventRegistrationModel;
use App\Models\PembayaranModel;
use App\Services\NotificationService;

class Pembayaran extends BaseController
{
    protected EventRegistrationModel $regM;
    protected PembayaranModel $payM;
    protected EventModel $eventM;

    public function __construct()
    {
        $this->regM   = new EventRegistrationModel();
        $this->payM   = new PembayaranModel();
        $this->eventM = new EventModel();
    }

    private function uid(): int { return (int) (session('id_user') ?? 0); }

    public function index()
    {
        $uid = $this->uid(); if(!$uid) return redirect()->to('/auth/login');

        $rows = $this->payM->select('id_pembayaran,event_id,jumlah,metode,status,tanggal_bayar,bukti_bayar')
                ->where('id_user',$uid)->orderBy('tanggal_bayar','DESC')->findAll();

        // map event title
        $eventMap = [];
        if ($rows) {
            $ids = array_unique(array_column($rows,'event_id'));
            $evs = $this->eventM->select('id,title')->whereIn('id',$ids)->findAll();
            foreach ($evs as $e) $eventMap[(int)$e['id']] = $e['title'];
        }

        return view('role/audience/pembayaran/index', [
            'title'    => 'Pembayaran',
            'payments' => $rows,
            'eventMap' => $eventMap,
        ]);
    }

    public function instruction(int $regId)
    {
        $uid = $this->uid(); if(!$uid) return redirect()->to('/auth/login');

        $reg = $this->regM->find($regId);
        if (!$reg || (int)$reg['id_user'] !== $uid) {
            return redirect()->to('/audience/events')->with('error','Data tidak ditemukan.');
        }

        $pricing = $this->eventM->getPricingMatrix((int)$reg['id_event']);
        $mode    = $reg['mode_kehadiran'] ?? 'online';
        $amount  = (float)($pricing['audience'][$mode] ?? 0);

        // tambahkan info event ringkas (opsional)
        $ev = $this->eventM->select('title,event_date,event_time')->find((int)$reg['id_event']);

        return view('role/audience/pembayaran/instruction', [
            'title'  => 'Instruksi Pembayaran',
            'reg'    => $reg,
            'amount' => $amount,
            'event'  => $ev,
        ]);
    }

    public function create(int $regId)
    {
        $uid = $this->uid(); if(!$uid) return redirect()->to('/auth/login');

        $reg = $this->regM->find($regId);
        if (!$reg || (int)$reg['id_user'] !== $uid) {
            return redirect()->to('/audience/events')->with('error','Data tidak ditemukan.');
        }
        if (($reg['status'] ?? '') !== 'menunggu_pembayaran') {
            return redirect()->to('/audience/events/detail/'.$reg['id_event'])->with('warning','Status pendaftaran bukan menunggu pembayaran.');
        }

        $pricing = $this->eventM->getPricingMatrix((int)$reg['id_event']);
        $mode    = $reg['mode_kehadiran'] ?? 'online';
        $amount  = (float)($pricing['audience'][$mode] ?? 0);

        $ev = $this->eventM->select('title,event_date,event_time')->find((int)$reg['id_event']);
        $reg['event_title'] = $ev['title'] ?? '-';
        $reg['event_date']  = $ev['event_date'] ?? null;
        $reg['event_time']  = $ev['event_time'] ?? null;

        return view('role/audience/pembayaran/create', [
            'title'  => 'Upload Bukti Pembayaran',
            'reg'    => $reg,
            'amount' => $amount,
        ]);
    }

    /** simpan bukti pembayaran (status -> pending) */
    public function store()
    {
        $uid = $this->uid(); if(!$uid) return redirect()->to('/auth/login');

        $regId = (int) $this->request->getPost('id_reg');
        $reg   = $this->regM->find($regId);
        if (!$reg || (int)$reg['id_user'] !== $uid) {
            return redirect()->to('/audience/events')->with('error','Data tidak ditemukan.');
        }

        $rules = [
            'bukti_bayar' => [
                'label' => 'Bukti pembayaran',
                'rules' => 'uploaded[bukti_bayar]|max_size[bukti_bayar,5120]|ext_in[bukti_bayar,jpg,jpeg,png,pdf]'
            ],
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors',$this->validator->getErrors());
        }

        $file    = $this->request->getFile('bukti_bayar');
        $newName = $file->getRandomName();
        $file->move(WRITEPATH.'uploads/pembayaran', $newName);

        // simpan RELATIF terhadap WRITEPATH
        $dbPath = 'uploads/pembayaran/'.$newName;

        $pricing = $this->eventM->getPricingMatrix((int)$reg['id_event']);
        $mode    = $reg['mode_kehadiran'] ?? 'online';
        $amount  = (float)($pricing['audience'][$mode] ?? 0);

        // === FIX: isi kolom NOT NULL `metode` dengan default manual/transfer ===
        $method  = (string) ($this->request->getPost('metode') ?: 'transfer');

        // upsert pending untuk event ini
        $existing = $this->payM->where('id_user',$uid)
                               ->where('event_id',(int)$reg['id_event'])
                               ->where('status','pending')
                               ->orderBy('tanggal_bayar','DESC')->first();

        if ($existing) {
            $this->payM->update((int)$existing['id_pembayaran'], [
                'metode'        => $method,
                'bukti_bayar'   => $dbPath,        // kolom benar
                'jumlah'        => $amount,
                'status'        => 'pending',
                'tanggal_bayar' => date('Y-m-d H:i:s'),
                'participation_type' => $mode,
            ]);
            $payId = (int)$existing['id_pembayaran'];
        } else {
            $payId = (int) $this->payM->insert([
                'id_user'        => $uid,
                'event_id'       => (int)$reg['id_event'],
                'metode'         => $method,
                'jumlah'         => $amount,
                'status'         => 'pending',
                'bukti_bayar'    => $dbPath,       // kolom benar
                'tanggal_bayar'  => date('Y-m-d H:i:s'),
                'participation_type' => $mode,
            ]);
        }

        // notifikasi (opsional)
        try {
            $notif = new NotificationService();
            $notif->notify($uid, 'payment', 'Bukti pembayaran terkirim', 'Menunggu verifikasi panitia.',
                site_url('audience/pembayaran/detail/'.$payId));
        } catch (\Throwable $e) {}

        return redirect()->to('/audience/pembayaran/detail/'.$payId)->with('message','Bukti pembayaran berhasil diunggah.');
    }

    /** reupload bukti: hanya untuk pending / rejected */
    public function reupload(int $payId)
    {
        $uid = $this->uid(); if(!$uid) return redirect()->to('/auth/login');

        $pay = $this->payM->find($payId);
        if (!$pay || (int)$pay['id_user'] !== $uid) {
            return redirect()->to('/audience/pembayaran')->with('error','Data tidak ditemukan.');
        }
        if (!in_array($pay['status'], ['pending','rejected'], true)) {
            return redirect()->to('/audience/pembayaran/detail/'.$payId)->with('warning','Bukti tidak dapat diubah untuk status ini.');
        }

        $rules = [
            'bukti_bayar' => [
                'label' => 'Bukti pembayaran',
                'rules' => 'uploaded[bukti_bayar]|max_size[bukti_bayar,5120]|ext_in[bukti_bayar,jpg,jpeg,png,pdf]'
            ],
        ];
        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors',$this->validator->getErrors());
        }

        $file    = $this->request->getFile('bukti_bayar');
        $newName = $file->getRandomName();
        $file->move(WRITEPATH.'uploads/pembayaran', $newName);
        $dbPath  = 'uploads/pembayaran/'.$newName;

        // Hapus file lama (opsional)
        $oldRel = $pay['bukti_bayar'] ?? ($pay['bukti'] ?? null);
        if ($oldRel) {
            $oldAbs = $this->absPathFromDb($oldRel);
            if (is_file($oldAbs)) @unlink($oldAbs);
        }

        $this->payM->update($payId, [
            'bukti_bayar'   => $dbPath,
            'tanggal_bayar' => date('Y-m-d H:i:s'),
            // status tetap 'pending' / 'rejected' -> admin yang ubah ke verified
        ]);

        return redirect()->to('/audience/pembayaran/detail/'.$payId)->with('message','Bukti berhasil diubah.');
    }

    /** detail pembayaran saya */
    public function detail(int $id)
    {
        $uid = $this->uid(); if(!$uid) return redirect()->to('/auth/login');

        $row = $this->payM->find($id);
        if (!$row || (int)$row['id_user'] !== $uid) {
            return redirect()->to('/audience/pembayaran')->with('error','Data tidak ditemukan.');
        }

        $ev = $this->eventM->select('title,event_date,event_time')->find((int)$row['event_id']);

        return view('role/audience/pembayaran/detail', [
            'title' => 'Detail Pembayaran',
            'pay'   => $row,
            'event' => $ev,
        ]);
    }

    /** download bukti (aman dari WRITEPATH) */
    public function downloadBukti(int $id)
    {
        $uid = $this->uid(); if(!$uid) return redirect()->to('/auth/login');

        $row = $this->payM->find($id);
        if (!$row || (int)$row['id_user'] !== $uid) {
            return redirect()->to('/audience/pembayaran')->with('error','Data tidak ditemukan.');
        }

        // ambil kolom baru, fallback ke kolom lama (kalau ada data legacy)
        $rel = $row['bukti_bayar'] ?? ($row['bukti'] ?? null);
        if (!$rel) {
            return redirect()->to('/audience/pembayaran/detail/'.$id)->with('error','Bukti belum tersedia.');
        }

        $abs = $this->absPathFromDb($rel);
        if (!is_file($abs)) {
            return redirect()->to('/audience/pembayaran/detail/'.$id)->with('error','File bukti tidak ditemukan di server.');
        }

        $downloadName = basename($abs);
        return $this->response->download($abs, null)->setFileName($downloadName);
    }

    /** helper: ubah path DB -> absolute path */
    private function absPathFromDb(string $rel): string
    {
        // Jika kamu menyimpan "uploads/pembayaran/xxx", maka absolutnya:
        if (str_starts_with($rel, 'uploads/')) {
            return rtrim(WRITEPATH, '/\\') . DIRECTORY_SEPARATOR . $rel;
        }

        // Jika (lama) tersimpan "writable/uploads/pembayaran/xxx"
        if (str_starts_with($rel, 'writable/')) {
            return rtrim(ROOTPATH, '/\\') . DIRECTORY_SEPARATOR . $rel;
        }

        // Jika sudah absolute path
        if (is_file($rel)) return $rel;

        // fallback (public)
        return rtrim(FCPATH, '/\\') . DIRECTORY_SEPARATOR . ltrim($rel, '/\\');
    }

    public function cancel(int $paymentId)
    {
        $uid = $this->uid(); if(!$uid) return redirect()->to('/auth/login');

        $pay = $this->payM->find($paymentId);
        if (!$pay || (int)$pay['id_user'] !== $uid) {
            return redirect()->to('/audience/pembayaran')->with('error','Data tidak ditemukan.');
        }
        if ($pay['status'] !== 'pending') {
            return redirect()->to('/audience/pembayaran/detail/'.$paymentId)->with('warning','Tidak dapat dibatalkan.');
        }

        $this->payM->update($paymentId, ['status' => 'canceled']);

        $reg = $this->regM->where('id_user',$uid)->where('id_event',(int)$pay['event_id'])->first();
        if ($reg && $reg['status']==='menunggu_pembayaran') {
            $this->regM->update((int)$reg['id'], ['status' => 'batal']);
        }

        return redirect()->to('/audience/events')->with('message','Pendaftaran dibatalkan.');
    }
}
