<?php
namespace App\Controllers\Role\Presenter;

use App\Controllers\BaseController;
use App\Models\EventModel;
use App\Models\PembayaranModel;
use App\Models\AbstrakModel;

class Pembayaran extends BaseController
{
    protected EventModel $eventModel;
    protected PembayaranModel $payModel;
    protected AbstrakModel $absModel;

    public function __construct()
    {
        $this->eventModel = new EventModel();
        $this->payModel   = new PembayaranModel();
        $this->absModel   = new AbstrakModel();
    }

    /** LIST / HISTORY */
    public function index()
    {
        $userId = (int) session()->get('id_user');

        $history = $this->payModel->getPembayaranWithUser(); // akan ambil semua, filter manual
        // filter hanya milik user ini
        $history = array_values(array_filter($history, fn($r) => (int)$r['id_user'] === $userId));

        return view('role/presenter/pembayaran/index', [
            'title'   => 'Pembayaran Saya',
            'history' => $history,
        ]);
    }

    /** FORM BUAT PEMBAYARAN BARU (DARI DETAIL EVENT) */
    public function create(int $eventId)
    {
        $userId = (int) session()->get('id_user');

        $event = $this->eventModel->find($eventId);
        if (!$event) {
            return redirect()->to('/presenter/events')->with('error', 'Event tidak ditemukan.');
        }

        // Hanya boleh bayar kalau abstrak SUDAH DITERIMA
        $accepted = $this->absModel->where('id_user', $userId)
                                   ->where('event_id', $eventId)
                                   ->where('status', 'diterima')
                                   ->countAllResults() > 0;
        if (!$accepted) {
            return redirect()->to('/presenter/events/detail/'.$eventId)
                ->with('error', 'Pembayaran hanya dapat dilakukan setelah abstrak Anda diterima.');
        }

        // Cek apakah ada payment terakhir (untuk prefill/larangan duplikat)
        $latest = $this->payModel->where('id_user', $userId)
                                 ->where('event_id', $eventId)
                                 ->orderBy('id_pembayaran','DESC')
                                 ->first();

        return view('role/presenter/pembayaran/create', [
            'title'     => 'Upload Bukti Pembayaran',
            'event'     => $event,
            'latestPay' => $latest,
            'price'     => (int)($event['presenter_fee_offline'] ?? 0), // presenter offline price
        ]);
    }

    /** SIMPAN PEMBAYARAN BARU */
    public function store()
    {
        $userId   = (int) session()->get('id_user');
        $eventId  = (int) $this->request->getPost('event_id');
        $metode   = trim((string) $this->request->getPost('metode'));
        $jumlah   = (int) $this->request->getPost('jumlah');
        $voucher  = $this->request->getPost('kode_voucher'); // optional
        $keterangan = trim((string) $this->request->getPost('keterangan'));

        if (!$eventId || !$metode || !$jumlah) {
            return redirect()->back()->withInput()->with('error', 'Lengkapi data pembayaran.');
        }

        // Hanya boleh bayar kalau abstrak SUDAH DITERIMA
        $accepted = $this->absModel->where('id_user', $userId)
                                   ->where('event_id', $eventId)
                                   ->where('status', 'diterima')
                                   ->countAllResults() > 0;
        if (!$accepted) {
            return redirect()->to('/presenter/events/detail/'.$eventId)
                ->with('error', 'Pembayaran hanya dapat dilakukan setelah abstrak Anda diterima.');
        }

        $file = $this->request->getFile('bukti_bayar');
        if (!$file || !$file->isValid()) {
            return redirect()->back()->withInput()->with('error', 'Bukti bayar tidak valid.');
        }
        $ext = strtolower($file->getClientExtension());
        if (!in_array($ext, ['jpg','jpeg','png','pdf'])) {
            return redirect()->back()->withInput()->with('error', 'Bukti harus JPG/PNG/PDF.');
        }

        // Simpan bukti ke WRITEPATH/uploads/pembayaran
        $dir = WRITEPATH.'uploads/pembayaran/';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $ts   = date('YmdHis');
        $name = "pay_{$userId}_{$eventId}_{$ts}.{$ext}";
        if (!$file->move($dir, $name)) {
            return redirect()->back()->withInput()->with('error', 'Gagal menyimpan bukti pembayaran.');
        }

        // (opsional) apply voucher → contoh dummy (sesuaikan dg VoucherModel.validate)
        $idVoucher = null;
        if ($voucher) {
            // TODO: panggil validator voucher kamu di sini
            // $idVoucher = ...; $jumlah = ... (setelah diskon)
        }

        // Insert pembayaran (status pending)
        $this->payModel->insert([
            'id_user'            => $userId,
            'event_id'           => $eventId,
            'metode'             => $metode,
            'jumlah'             => $jumlah,
            'bukti_bayar'        => $name,
            'status'             => 'pending',
            'tanggal_bayar'      => date('Y-m-d H:i:s'),
            'id_voucher'         => $idVoucher,
            'keterangan'         => $keterangan,
            'participation_type' => 'offline', // presenter default
        ]);

        $idPay = (int) $this->payModel->getInsertID();

        return redirect()->to('/presenter/pembayaran/detail/'.$idPay)
            ->with('success','Bukti pembayaran terkirim. Menunggu verifikasi admin.');
    }

    /** DETAIL PEMBAYARAN */
    public function detail(int $id)
    {
        $userId = (int) session()->get('id_user');

        $row = $this->payModel->where('id_pembayaran', $id)->first();
        if (!$row || (int)$row['id_user'] !== $userId) {
            return redirect()->to('/presenter/pembayaran')->with('error','Data pembayaran tidak ditemukan.');
        }

        $event = $this->eventModel->find((int)$row['event_id']);

        return view('role/presenter/pembayaran/detail', [
            'title' => 'Detail Pembayaran',
            'pay'   => $row,
            'event' => $event,
        ]);
    }

    /** RE-UPLOAD (kalau ditolak atau ingin ganti selama pending) */
    public function reupload(int $id)
    {
        $userId = (int) session()->get('id_user');

        $row = $this->payModel->where('id_pembayaran', $id)->first();
        if (!$row || (int)$row['id_user'] !== $userId) {
            return redirect()->to('/presenter/pembayaran')->with('error','Data pembayaran tidak ditemukan.');
        }

        if (!in_array(strtolower($row['status']), ['pending','rejected'])) {
            return redirect()->to('/presenter/pembayaran/detail/'.$id)
                ->with('error','Tidak dapat mengunggah ulang pada status saat ini.');
        }

        $file = $this->request->getFile('bukti_bayar');
        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('error', 'File tidak valid.');
        }
        $ext = strtolower($file->getClientExtension());
        if (!in_array($ext, ['jpg','jpeg','png','pdf'])) {
            return redirect()->back()->with('error', 'Bukti harus JPG/PNG/PDF.');
        }

        $dir = WRITEPATH.'uploads/pembayaran/';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $ts   = date('YmdHis');
        $name = "pay_{$userId}_{$row['event_id']}_{$ts}.{$ext}";
        if (!$file->move($dir, $name)) {
            return redirect()->back()->with('error', 'Gagal menyimpan file.');
        }

        $this->payModel->update($id, [
            'bukti_bayar' => $name,
            'status'      => 'pending',
            'keterangan'  => 'Re-upload oleh user (presenter)',
        ]);

        return redirect()->to('/presenter/pembayaran/detail/'.$id)
            ->with('success','Bukti pembayaran berhasil diunggah ulang. Menunggu verifikasi.');
    }

    /** (Opsional) Batalkan pembayaran selama pending */
    public function cancel(int $id)
    {
        $userId = (int) session()->get('id_user');

        $row = $this->payModel->where('id_pembayaran', $id)->first();
        if (!$row || (int)$row['id_user'] !== $userId) {
            return redirect()->to('/presenter/pembayaran')->with('error','Data pembayaran tidak ditemukan.');
        }

        if (strtolower($row['status']) !== 'pending') {
            return redirect()->to('/presenter/pembayaran/detail/'.$id)->with('error','Hanya pembayaran pending yang bisa dibatalkan.');
        }

        $this->payModel->delete($id);
        return redirect()->to('/presenter/pembayaran')->with('success','Pembayaran dibatalkan.');
    }
}