<?php
namespace App\Controllers\Role\Presenter;

use App\Controllers\BaseController;
use App\Models\EventModel;
use App\Models\PembayaranModel;
use App\Models\AbstrakModel;
use App\Models\VoucherModel;

class Pembayaran extends BaseController
{
    protected EventModel $eventModel;
    protected PembayaranModel $payModel;
    protected AbstrakModel $absModel;
    protected VoucherModel $voucherModel;

    public function __construct()
    {
        $this->eventModel   = new EventModel();
        $this->payModel     = new PembayaranModel();
        $this->absModel     = new AbstrakModel();
        $this->voucherModel = new VoucherModel();
    }

    /* =========================
     * Utilities
     * ========================= */
    private function getBasePrice(array $event): int
    {
        return max(0, (int)($event['presenter_fee_offline'] ?? 0));
    }

    private function applyDiscount(int $base, string $tipe, int $nilai): int
    {
        $tipe  = strtolower($tipe);
        $nilai = max(0, $nilai);

        if (in_array($tipe, ['percentage','persen'], true)) {
            $p = min(100, $nilai);
            $disc = (int) floor($base * $p / 100);
            return max(0, $base - $disc);
        }
        // fixed/nominal
        return max(0, $base - $nilai);
    }

    /**
     * Kembalikan ['voucher'=>row, 'final_price'=>int] jika valid; null jika tidak.
     */
    private function findValidVoucher(string $code, int $userId, int $eventId, int $basePrice): ?array
    {
        $code = trim($code);
        if ($code === '') return null;

        // case-insensitive
        $voucher = $this->voucherModel
            ->where('LOWER(kode_voucher) =', strtolower($code))
            ->first();
        if (!$voucher) return null;

        // status harus aktif
        if (strtolower($voucher['status'] ?? '') !== 'aktif') return null;

        // masa berlaku (YYYY-mm-dd), valid s/d 23:59:59
        $exp = $voucher['masa_berlaku'] ?? null;
        if (!empty($exp)) {
            $endTs = strtotime(date('Y-m-d 23:59:59', strtotime($exp)));
            if ($endTs && $endTs < time()) return null; // expired
        }

        // kuota: hitung pending/verified
        $kuota = (int)($voucher['kuota'] ?? 0);
        if ($kuota > 0) {
            $used = $this->payModel
                ->where('id_voucher', (int)$voucher['id_voucher'])
                ->whereIn('status', ['pending','verified'])
                ->countAllResults();
            if ($used >= $kuota) return null;
        }

        // user tidak boleh pakai voucher sama utk event sama (pending/verified)
        $dup = $this->payModel
            ->where('id_user', $userId)
            ->where('event_id', $eventId)
            ->where('id_voucher', (int)$voucher['id_voucher'])
            ->whereIn('status', ['pending','verified'])
            ->first();
        if ($dup) return null;

        // nilai diskon
        $tipe  = strtolower((string)($voucher['tipe'] ?? ''));
        $nilai = (int)($voucher['nilai'] ?? 0);
        if (!in_array($tipe, ['percentage','fixed','persen','nominal'], true)) return null;
        if ($nilai <= 0) return null;

        $final = $this->applyDiscount($basePrice, $tipe, $nilai);

        return [
            'voucher'     => $voucher,
            'final_price' => $final,
        ];
    }

    private function ensureAccepted(int $userId, int $eventId): bool
    {
        return $this->absModel->where('id_user',$userId)
                              ->where('event_id',$eventId)
                              ->where('status','diterima')
                              ->countAllResults() > 0;
    }

    /* =========================
     * Pages
     * ========================= */
    public function index()
    {
        $userId = (int) session()->get('id_user');

        $rows = $this->payModel
            ->select('pembayaran.*, e.title, e.event_date, e.event_time, e.format')
            ->join('events e', 'e.id = pembayaran.event_id', 'left')
            ->where('pembayaran.id_user', $userId)
            ->orderBy('pembayaran.id_pembayaran','DESC')
            ->findAll();

        $history = array_map(function($r){
            $status = strtolower($r['status'] ?? 'pending');
            $hint = match ($status) {
                'verified' => 'Pembayaran terverifikasi. Anda resmi terdaftar.',
                'rejected' => 'Pembayaran ditolak. Silakan upload ulang bukti.',
                default    => 'Pembayaran pending. Menunggu verifikasi.',
            };
            $badge = [
                'pending'  => 'warning',
                'verified' => 'success',
                'rejected' => 'danger',
            ][$status] ?? 'secondary';

            $format = strtolower($r['format'] ?? '');
            $formatLabel = $format === 'both' ? 'Hybrid' : ucfirst($format ?: '-');

            return [
                ...$r,
                'badge'       => $badge,
                'hint'        => $hint,
                'formatLabel' => $formatLabel,
            ];
        }, $rows);

        return view('role/presenter/pembayaran/index', [
            'title'   => 'Pembayaran',
            'history' => $history,
        ]);
    }

    /** STEP 1: Instruction (cek voucher & lanjut upload) */
    public function instruction(int $eventId)
    {
        $userId = (int) session()->get('id_user');
        $event  = $this->eventModel->find($eventId);
        if (!$event) return redirect()->to('/presenter/events')->with('error','Event tidak ditemukan.');

        if (!$this->ensureAccepted($userId, $eventId)) {
            return redirect()->to('/presenter/events/detail/'.$eventId)
                ->with('error','Instruksi pembayaran muncul setelah abstrak diterima.');
        }

        return view('role/presenter/pembayaran/instruction', [
            'title'     => 'Instruksi Pembayaran',
            'event'     => $event,
            'basePrice' => $this->getBasePrice($event),
        ]);
    }

    /** AJAX: Validate voucher (selalu 200 JSON + token baru) */
    public function validateVoucher()
    {
        $userId  = (int) session()->get('id_user');
        $eventId = (int) ($this->request->getPost('event_id') ?? $this->request->getGet('event_id'));
        $code    = (string) ($this->request->getPost('kode_voucher') ?? $this->request->getGet('kode_voucher'));

        if (!$eventId) {
            return $this->response->setJSON(['ok'=>false,'message'=>'Event tidak valid.','token'=>csrf_hash()]);
        }

        $event = $this->eventModel->find($eventId);
        if (!$event) {
            return $this->response->setJSON(['ok'=>false,'message'=>'Event tidak ditemukan.','token'=>csrf_hash()]);
        }

        $base = $this->getBasePrice($event);
        $res  = $this->findValidVoucher($code, $userId, $eventId, $base);

        if (!$res) {
            return $this->response->setJSON([
                'ok'      => false,
                'message' => 'Voucher tidak valid / nonaktif / habis kuota / kedaluwarsa / sudah dipakai.',
                'token'   => csrf_hash(),
            ]);
        }

        return $this->response->setJSON([
            'ok'          => true,
            'message'     => 'Voucher diterapkan.',
            'final_price' => (int)$res['final_price'],
            'voucher_id'  => (int)$res['voucher']['id_voucher'],
            'code'        => (string)$res['voucher']['kode_voucher'],
            'token'       => csrf_hash(),
        ]);
    }

    /** STEP 2: Create (Upload bukti) — menerima ?v=KODE */
    public function create(int $eventId)
    {
        $userId = (int) session()->get('id_user');

        $event = $this->eventModel->find($eventId);
        if (!$event) return redirect()->to('/presenter/pembayaran')->with('error','Event tidak ditemukan.');

        if (!$this->ensureAccepted($userId, $eventId)) {
            return redirect()->to('/presenter/events/detail/'.$eventId)->with('error','Pembayaran hanya setelah abstrak diterima.');
        }

        $basePrice   = $this->getBasePrice($event);
        $price       = $basePrice;
        $voucherInfo = null;

        $code = trim((string) $this->request->getGet('v'));
        if ($code !== '') {
            $res = $this->findValidVoucher($code, $userId, $eventId, $basePrice);
            if ($res) {
                $price       = (int)$res['final_price'];
                $voucherInfo = [
                    'id_voucher' => (int)$res['voucher']['id_voucher'],
                    'code'       => (string)$res['voucher']['kode_voucher'],
                ];
            }
        }

        return view('role/presenter/pembayaran/create', [
            'title'       => 'Upload Bukti Pembayaran',
            'event'       => $event,
            'basePrice'   => $basePrice,
            'price'       => $price,
            'voucherInfo' => $voucherInfo,
        ]);
    }

    /** STEP 3: Store (revalidate voucher & simpan) */
    public function store()
    {
        // Jika bukan POST: arahkan balik dengan sopan (hindari “Metode tidak diizinkan.”)
        if (!$this->request->is('post')) {
            $eventId = (int) ($this->request->getGet('event_id') ?? 0);
            $to = $eventId ? ('/presenter/pembayaran/instruction/'.$eventId) : '/presenter/pembayaran';
            return redirect()->to($to)->with('error','Akses tidak valid. Silakan unggah melalui formulir.');
        }

        $userId  = (int) session()->get('id_user');
        $eventId = (int) $this->request->getPost('event_id');
        $metode  = trim((string) $this->request->getPost('metode'));
        $code    = trim((string) $this->request->getPost('kode_voucher')); // optional

        if (!$eventId || $metode === '') {
            return redirect()->back()->withInput()->with('error','Lengkapi data pembayaran.');
        }

        $event = $this->eventModel->find($eventId);
        if (!$event) return redirect()->to('/presenter/pembayaran')->with('error','Event tidak ditemukan.');
        if (!$this->ensureAccepted($userId, $eventId)) {
            return redirect()->to('/presenter/events/detail/'.$eventId)->with('error','Abstrak belum diterima.');
        }

        $base      = $this->getBasePrice($event);
        $jumlah    = $base;
        $idVoucher = null;

        if ($code !== '') {
            $res = $this->findValidVoucher($code, $userId, $eventId, $base);
            if ($res) {
                $jumlah    = (int)$res['final_price'];
                $idVoucher = (int)$res['voucher']['id_voucher'];
            }
        }

        // File upload
        $file = $this->request->getFile('bukti_bayar');
        if (!$file || !$file->isValid()) {
            return redirect()->back()->withInput()->with('error','Bukti bayar tidak valid.');
        }
        $ext = strtolower($file->getClientExtension());
        if (!in_array($ext, ['jpg','jpeg','png','pdf'])) {
            return redirect()->back()->withInput()->with('error','Bukti harus JPG/PNG/PDF.');
        }

        $dir = WRITEPATH.'uploads/pembayaran/';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $ts   = date('YmdHis');
        $name = "pay_{$userId}_{$eventId}_{$ts}.{$ext}";
        if (!$file->move($dir, $name)) {
            return redirect()->back()->with('error','Gagal menyimpan bukti.');
        }

        // Simpan pembayaran (tanpa keterangan/catatan)
        $this->payModel->insert([
            'id_user'            => $userId,
            'event_id'           => $eventId,
            'metode'             => $metode,
            'jumlah'             => $jumlah,
            'bukti_bayar'        => $name,
            'status'             => 'pending',
            'tanggal_bayar'      => date('Y-m-d H:i:s'),
            'id_voucher'         => $idVoucher,
            'keterangan'         => null,
            'participation_type' => 'offline',
        ]);

        $idPay = (int) $this->payModel->getInsertID();

        return redirect()->to('/presenter/pembayaran/detail/'.$idPay)
            ->with('success','Bukti pembayaran terkirim. Menunggu verifikasi.');
    }

    /** DETAIL */
    public function detail(int $id)
    {
        $userId = (int) session()->get('id_user');

        $row = $this->payModel->where('id_pembayaran', $id)->first();
        if (!$row || (int)$row['id_user'] !== $userId) {
            return redirect()->to('/presenter/pembayaran')->with('error','Data pembayaran tidak ditemukan.');
        }

        $event = $this->eventModel->find((int)$row['event_id']);
        $badge = [
            'pending'  => 'warning',
            'verified' => 'success',
            'rejected' => 'danger',
        ][strtolower($row['status'] ?? 'pending')] ?? 'secondary';

        return view('role/presenter/pembayaran/detail', [
            'title' => 'Detail Pembayaran',
            'pay'   => $row,
            'event' => $event,
            'badge' => $badge,
        ]);
    }

    public function downloadBukti(int $id)
{
    $userId = (int) session()->get('id_user');
    $row = $this->payModel->where('id_pembayaran',$id)->first();
    if (!$row || (int)$row['id_user'] !== $userId) return redirect()->back()->with('error','Tidak ditemukan.');

    $path = WRITEPATH.'uploads/pembayaran/'.($row['bukti_bayar'] ?? '');
    if (!is_file($path)) return redirect()->back()->with('error','File tidak ada.');

    $inline = (bool)$this->request->getGet('inline');
    $mime   = mime_content_type($path) ?: 'application/octet-stream';
    $name   = basename($path);

    return $this->response
        ->setHeader('Content-Type', $mime)
        ->setHeader('Content-Disposition', ($inline ? 'inline' : 'attachment').'; filename="'.$name.'"')
        ->setBody(file_get_contents($path));
}

    /** REUPLOAD (pending/rejected) */
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
            return redirect()->back()->with('error','File tidak valid.');
        }
        $ext = strtolower($file->getClientExtension());
        if (!in_array($ext, ['jpg','jpeg','png','pdf'])) {
            return redirect()->back()->with('error','Bukti harus JPG/PNG/PDF.');
        }

        $dir = WRITEPATH.'uploads/pembayaran/';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);
        $ts   = date('YmdHis');
        $name = "pay_{$userId}_{$row['event_id']}_{$ts}.{$ext}";
        if (!$file->move($dir, $name)) {
            return redirect()->back()->with('error','Gagal menyimpan file.');
        }

        $this->payModel->update($id, [
            'bukti_bayar' => $name,
            'status'      => 'pending',
            'keterangan'  => 'Re-upload oleh presenter',
        ]);

        return redirect()->to('/presenter/pembayaran/detail/'.$id)
            ->with('success','Bukti pembayaran berhasil diunggah ulang. Menunggu verifikasi.');
    }

    /** CANCEL */
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
