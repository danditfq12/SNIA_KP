<?php

namespace App\Controllers\Role\Presenter;

use App\Controllers\BaseController;
use App\Models\EventModel;
use App\Models\PembayaranModel;
use App\Models\AbstrakModel;

class Pembayaran extends BaseController
{
    protected EventModel $eventM;
    protected PembayaranModel $payM;
    protected AbstrakModel $absM;
    protected \CodeIgniter\Database\BaseConnection $db;

    public function __construct()
    {
        $this->eventM = new EventModel();
        $this->payM   = new PembayaranModel();
        $this->absM   = new AbstrakModel();
        $this->db     = \Config\Database::connect();
        helper(['form','url']);
    }

    private function uid(): int
    {
        return (int) (session('id_user') ?? 0);
    }

    private function ensureAuth(): ?\CodeIgniter\HTTP\RedirectResponse
    {
        if (!$this->uid() || session('role') !== 'presenter') {
            return redirect()->to(site_url('auth/login'));
        }
        return null;
    }

    private function resolvePresenterPrice(array $ev): float
    {
        // Prefer specific field if ada, fallback ke pricing matrix, else 0
        if (isset($ev['presenter_fee_offline']) && is_numeric($ev['presenter_fee_offline'])) {
            return (float) $ev['presenter_fee_offline'];
        }
        // fallback: coba pricing matrix
        try {
            if (method_exists($this->eventM, 'getPricingMatrix')) {
                $mx = $this->eventM->getPricingMatrix((int)$ev['id']);
                if (isset($mx['presenter']['offline'])) return (float) $mx['presenter']['offline'];
                if (isset($mx['presenter'])) return (float) $mx['presenter'];
            }
        } catch (\Throwable $e) {}
        return 0.0;
    }

    private function hasAbstractDiAcc(int $eventId, int $userId): bool
    {
        $last = $this->absM->where('event_id', $eventId)
                           ->where('id_user', $userId)
                           ->orderBy('tanggal_upload','DESC')
                           ->first();
        return $last && strtolower((string)$last['status']) === 'diterima';
    }

    private function latestPaymentForEvent(int $eventId, int $userId): ?array
    {
        return $this->payM->where('event_id', $eventId)
                          ->where('id_user', $userId)
                          ->orderBy('tanggal_bayar','DESC')
                          ->first() ?: null;
    }

    /** List semua pembayaran milik presenter */
    public function index()
    {
        if ($redir = $this->ensureAuth()) return $redir;
        $uid = $this->uid();

        $rows = $this->payM->select('pembayaran.*, e.title, e.event_date, e.event_time')
                           ->join('events e', 'e.id = pembayaran.event_id', 'left')
                           ->where('pembayaran.id_user', $uid)
                           ->orderBy('pembayaran.tanggal_bayar','DESC')
                           ->findAll();

        // ringkasan
        $stats = ['total'=>0,'pending'=>0,'verified'=>0,'rejected'=>0,'canceled'=>0];
        foreach ($rows as $r) {
            $stats['total']++;
            $st = strtolower((string)$r['status']);
            if (isset($stats[$st])) $stats[$st]++;
        }

        return view('role/presenter/pembayaran/index', [
            'title'    => 'Pembayaran Presenter',
            'payments' => $rows,
            'stats'    => $stats,
        ]);
    }

    /** Form create pembayaran utk event tertentu */
    public function create(int $eventId)
    {
        if ($redir = $this->ensureAuth()) return $redir;
        $uid = $this->uid();

        $ev = $this->eventM->find($eventId);
        if (!$ev || !($ev['is_active'] ?? false)) {
            return redirect()->to(site_url('presenter/events'))
                ->with('error','Event tidak ditemukan atau tidak aktif.');
        }

        // Pendaftaran harus open
        if (!$this->eventM->isRegistrationOpen($eventId)) {
            return redirect()->to(site_url('presenter/events/detail/'.$eventId))
                ->with('error','Pendaftaran event telah ditutup.');
        }

        // Wajib abstrak diterima
        if (!$this->hasAbstractDiAcc($eventId, $uid)) {
            return redirect()->to(site_url('presenter/events/detail/'.$eventId))
                ->with('warning','Abstrak kamu belum Di-ACC. Selesaikan dulu sebelum pembayaran.');
        }

        // Tidak boleh jika sudah verified
        $latest = $this->latestPaymentForEvent($eventId, $uid);
        if ($latest && strtolower((string)$latest['status']) === 'verified') {
            return redirect()->to(site_url('presenter/pembayaran/detail/'.$latest['id_pembayaran']))
                ->with('warning','Pembayaran untuk event ini sudah terverifikasi.');
        }

        $price = $this->resolvePresenterPrice($ev);

        return view('role/presenter/pembayaran/create', [
            'title' => 'Pembayaran Event',
            'event' => $ev,
            'price' => $price,
        ]);
    }

    /** Simpan pembayaran (upload bukti) */
    public function store()
    {
        if ($redir = $this->ensureAuth()) return $redir;
        if (!$this->request->is('post')) return redirect()->to(site_url('presenter/pembayaran'));

        $uid      = $this->uid();
        $eventId  = (int)$this->request->getPost('event_id');
        $voucher  = trim((string)$this->request->getPost('voucher'));
        $ev       = $this->eventM->find($eventId);

        if (!$ev || !($ev['is_active'] ?? false)) {
            return redirect()->back()->withInput()->with('error','Event tidak valid.');
        }
        if (!$this->eventM->isRegistrationOpen($eventId)) {
            return redirect()->to(site_url('presenter/events/detail/'.$eventId))
                ->with('error','Pendaftaran event telah ditutup.');
        }
        if (!$this->hasAbstractDiAcc($eventId, $uid)) {
            return redirect()->to(site_url('presenter/events/detail/'.$eventId))
                ->with('warning','Abstrak kamu belum Di-ACC.');
        }

        // jika ada pending/rejected — biarkan buat baru atau arahkan? Di sini kita arahkan ke pending terakhir
        $existing = $this->latestPaymentForEvent($eventId, $uid);
        if ($existing && in_array(strtolower((string)$existing['status']), ['pending','verified'], true)) {
            return redirect()->to(site_url('presenter/pembayaran/detail/'.$existing['id_pembayaran']))
                ->with('warning','Kamu sudah memiliki pembayaran aktif untuk event ini.');
        }

        // hitung jumlah
        $amount = $this->resolvePresenterPrice($ev);
        $discount = 0.0;
        if ($voucher !== '') {
            $v = $this->validateVoucherCode($voucher, $uid, $eventId);
            if ($v['ok']) {
                $discount = (float) $v['discount'];
            } else {
                // voucher salah → tetap proses tanpa voucher, beri warning
                session()->setFlashdata('warning', $v['message'] ?? 'Voucher tidak berlaku.');
            }
        }
        $final = max(0, $amount - $discount);

        // file
        $file = $this->request->getFile('bukti');
        if (!$file || !$file->isValid()) {
            return redirect()->back()->withInput()->with('error','Bukti pembayaran tidak valid.');
        }
        // validasi mime sederhana
        $allow = ['image/jpeg','image/png','image/webp','application/pdf'];
        if (!in_array($file->getMimeType(), $allow, true)) {
            return redirect()->back()->withInput()->with('error','Format bukti harus JPG/PNG/WebP/PDF.');
        }

        $dir = WRITEPATH . 'uploads/bukti';
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        $newName = 'pay_'.$uid.'_'.$eventId.'_'.time().'.'.strtolower($file->getExtension() ?: 'dat');
        $file->move($dir, $newName, true);
        $relPath = 'uploads/bukti/'.$newName;

        // insert
        $data = [
            'id_user'            => $uid,
            'event_id'           => $eventId,
            'participation_type' => 'presenter_offline',
            'jumlah'             => $final,
            'status'             => 'pending',
            'bukti'              => $relPath,
            'voucher_code'       => ($voucher !== '') ? $voucher : null,
            'discount'           => $discount,
            'tanggal_bayar'      => date('Y-m-d H:i:s'),
        ];
        $this->payM->insert($data, true);
        $pid = (int) $this->payM->getInsertID();

        return redirect()->to(site_url('presenter/pembayaran/detail/'.$pid))
            ->with('message','Pembayaran berhasil diunggah. Menunggu verifikasi admin.');
    }

    /** Detail pembayaran */
    public function detail(int $id)
    {
        if ($redir = $this->ensureAuth()) return $redir;
        $uid = $this->uid();

        $row = $this->payM->select('pembayaran.*, e.title, e.event_date, e.event_time, e.location')
                          ->join('events e', 'e.id = pembayaran.event_id', 'left')
                          ->where('pembayaran.id_pembayaran', $id)
                          ->where('pembayaran.id_user', $uid)
                          ->first();

        if (!$row) return redirect()->to(site_url('presenter/pembayaran'))->with('error','Data pembayaran tidak ditemukan.');

        return view('role/presenter/pembayaran/detail', [
            'title'    => 'Detail Pembayaran',
            'payment'  => $row,
        ]);
    }

    /** Reupload bukti untuk payment non-verified */
    public function reupload(int $id)
    {
        if ($redir = $this->ensureAuth()) return $redir;
        if (!$this->request->is('post')) return redirect()->to(site_url('presenter/pembayaran/detail/'.$id));
        $uid = $this->uid();

        $row = $this->payM->where('id_pembayaran', $id)
                          ->where('id_user', $uid)->first();
        if (!$row) return redirect()->to(site_url('presenter/pembayaran'))->with('error','Data pembayaran tidak ditemukan.');
        if (strtolower((string)$row['status']) === 'verified') {
            return redirect()->to(site_url('presenter/pembayaran/detail/'.$id))->with('warning','Pembayaran sudah terverifikasi.');
        }

        $file = $this->request->getFile('bukti');
        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('error','Berkas bukti tidak valid.');
        }
        $allow = ['image/jpeg','image/png','image/webp','application/pdf'];
        if (!in_array($file->getMimeType(), $allow, true)) {
            return redirect()->back()->with('error','Format bukti harus JPG/PNG/WebP/PDF.');
        }

        $dir = WRITEPATH . 'uploads/bukti';
        if (!is_dir($dir)) @mkdir($dir, 0775, true);
        $newName = 'pay_'.$uid.'_'.$row['event_id'].'_re_'.time().'.'.strtolower($file->getExtension() ?: 'dat');
        $file->move($dir, $newName, true);
        $relPath = 'uploads/bukti/'.$newName;

        $this->payM->update($id, [
            'bukti'         => $relPath,
            'status'        => 'pending', // reset untuk diverifikasi ulang
            'tanggal_bayar' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to(site_url('presenter/pembayaran/detail/'.$id))
            ->with('message','Bukti pembayaran diperbarui. Menunggu verifikasi ulang.');
    }

    /** Batalkan pembayaran (selama belum verified) */
    public function cancel(int $id)
    {
        if ($redir = $this->ensureAuth()) return $redir;
        $uid = $this->uid();

        $row = $this->payM->where('id_pembayaran', $id)->where('id_user', $uid)->first();
        if (!$row) return redirect()->to(site_url('presenter/pembayaran'))->with('error','Data pembayaran tidak ditemukan.');
        if (strtolower((string)$row['status']) === 'verified') {
            return redirect()->to(site_url('presenter/pembayaran/detail/'.$id))->with('warning','Tidak dapat dibatalkan karena sudah terverifikasi.');
        }

        $this->payM->update($id, ['status'=>'canceled']);
        return redirect()->to(site_url('presenter/pembayaran'))->with('message','Pembayaran dibatalkan.');
    }

    /** Download bukti yang diunggah */
    public function downloadBukti(int $id)
    {
        if ($redir = $this->ensureAuth()) return $redir;
        $uid = $this->uid();

        $row = $this->payM->where('id_pembayaran', $id)->where('id_user', $uid)->first();
        if (!$row || empty($row['bukti'])) return redirect()->back()->with('error','Bukti tidak ditemukan.');

        $path = WRITEPATH . $row['bukti'];
        if (!is_file($path)) return redirect()->back()->with('error','File bukti hilang di server.');

        return $this->response->download($path, null)->setFileName(basename($path));
    }

    /** AJAX: validasi voucher  */
    public function validateVoucher()
    {
        if ($redir = $this->ensureAuth()) return $redir;
        if (!$this->request->isAJAX()) return $this->response->setJSON(['ok'=>false,'message'=>'Bad request'])->setStatusCode(400);

        $code    = trim((string)$this->request->getPost('code'));
        $eventId = (int)$this->request->getPost('event_id');
        $uid     = $this->uid();

        $res = $this->validateVoucherCode($code, $uid, $eventId);
        return $this->response->setJSON($res);
    }

    /** helper validasi voucher (defensive — table name flexible) */
    private function validateVoucherCode(string $code, int $userId, int $eventId): array
    {
        $code = strtoupper(trim($code));
        if ($code === '') return ['ok'=>false,'message'=>'Kode voucher kosong.'];

        $table = $this->db->table('voucher');
        try {
            $voucher = $table->where('code', $code)->where('status', 'active')->get()->getRowArray();
        } catch (\Throwable $e) {
            // fallback jika nama tabel berbeda
            $voucher = $this->db->table('vouchers')->where('code', $code)->where('status','active')->get()->getRowArray();
        }

        if (!$voucher) return ['ok'=>false,'message'=>'Voucher tidak ditemukan / tidak aktif.'];

        // kalau ada kolom event_id dan diikat ke event lain
        if (!empty($voucher['event_id']) && (int)$voucher['event_id'] !== $eventId) {
            return ['ok'=>false,'message'=>'Voucher tidak berlaku untuk event ini.'];
        }

        // hitung diskon
        $discount = 0.0;
        if (!empty($voucher['type']) && strtolower($voucher['type']) === 'percent') {
            $ev = $this->eventM->find($eventId);
            $base = $this->resolvePresenterPrice($ev ?: []);
            $pct  = (float)($voucher['value'] ?? 0);
            $discount = max(0.0, $base * ($pct/100.0));
        } else {
            $discount = (float)($voucher['value'] ?? 0);
        }

        return ['ok'=>true,'discount'=>$discount,'message'=>'Voucher diterapkan.'];
    }
}
