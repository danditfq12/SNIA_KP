<?php
namespace App\Controllers\Role\Presenter;

use App\Controllers\BaseController;
use App\Models\EventModel;
use App\Models\AbstrakModel;
use App\Models\KategoriAbstrakModel;

class Abstrak extends BaseController
{
    protected EventModel $eventModel;
    protected AbstrakModel $absModel;
    protected KategoriAbstrakModel $katModel;

    public function __construct()
    {
        $this->eventModel = new EventModel();
        $this->absModel   = new AbstrakModel();
        $this->katModel   = new KategoriAbstrakModel();
    }

    /** INDEX */
    public function index()
    {
        $userId = (int) session()->get('id_user');

        // Event yang masih bisa upload abstrak (dan user belum punya abstrak aktif di event tsb)
        $availableEvents = $this->eventModel->getAvailableEventsForUser($userId);

        // Abstrak milik user + relasi (event/kategori)
        $all = $this->absModel->getByUserWithDetails($userId);

        // Kelompokkan
        $aktif   = []; // menunggu, sedang_direview, revisi
        $riwayat = []; // diterima, ditolak
        foreach ($all as $row) {
            $st = strtolower($row['status'] ?? 'menunggu');
            if (in_array($st, ['menunggu','sedang_direview','revisi'], true)) {
                $aktif[] = $row;
            } else { // 'diterima' / 'ditolak'
                $riwayat[] = $row;
            }
        }

        // KPI kecil
        $kpi = [
            'total'           => count($all),
            'menunggu'        => count(array_filter($all, fn($r)=>strtolower($r['status'])==='menunggu')),
            'sedang_direview' => count(array_filter($all, fn($r)=>strtolower($r['status'])==='sedang_direview')),
            'revisi'          => count(array_filter($all, fn($r)=>strtolower($r['status'])==='revisi')),
            'diterima'        => count(array_filter($all, fn($r)=>strtolower($r['status'])==='diterima')),
            'ditolak'         => count(array_filter($all, fn($r)=>strtolower($r['status'])==='ditolak')),
        ];

        return view('role/presenter/abstrak/index', [
            'title'           => 'Abstrak',
            'availableEvents' => $availableEvents,
            'aktif'           => $aktif,
            'riwayat'         => $riwayat,
            'kpi'             => $kpi,
        ]);
    }

    /** FORM UPLOAD BARU */
    public function create(int $eventId)
    {
        $userId = (int) session()->get('id_user');
        $event  = $this->eventModel->find($eventId);
        if (!$event) {
            return redirect()->to('/presenter/abstrak')->with('error','Event tidak ditemukan.');
        }

        // Cek event masih open untuk abstract
        if (!$this->eventModel->isAbstractSubmissionOpen($eventId)) {
            return redirect()->to('/presenter/abstrak')->with('error','Pengumpulan abstrak untuk event ini sudah ditutup.');
        }

        // Larang upload baru jika sudah ada abstrak aktif (menunggu/sedang_direview)
        $hasActive = $this->absModel->where('id_user', $userId)
            ->where('event_id', $eventId)
            ->whereIn('status', ['menunggu','sedang_direview'])
            ->countAllResults() > 0;

        if ($hasActive) {
            return redirect()->to('/presenter/abstrak')->with('error','Anda telah mengirim abstrak dan sedang menunggu review.');
        }

        $kategori = $this->katModel->orderBy('nama_kategori','ASC')->findAll();

        return view('role/presenter/abstrak/create', [
            'title'    => 'Kirim Abstrak',
            'event'    => $event,
            'kategori' => $kategori,
        ]);
    }

    /** SIMPAN UPLOAD BARU */
    public function store()
    {
        $userId   = (int) session()->get('id_user');
        $eventId  = (int) $this->request->getPost('event_id');
        $idKat    = (int) $this->request->getPost('id_kategori');
        $judul    = trim((string)$this->request->getPost('judul'));

        if (!$eventId || !$idKat || !$judul) {
            return redirect()->back()->withInput()->with('error','Lengkapi data.');
        }

        // Re-check gate
        if (!$this->eventModel->isAbstractSubmissionOpen($eventId)) {
            return redirect()->to('/presenter/abstrak')->with('error','Pengumpulan abstrak ditutup.');
        }
        $hasActive = $this->absModel->where('id_user', $userId)
            ->where('event_id', $eventId)
            ->whereIn('status', ['menunggu','sedang_direview'])
            ->countAllResults() > 0;
        if ($hasActive) {
            return redirect()->to('/presenter/abstrak')->with('error','Anda telah mengirim abstrak dan sedang menunggu review.');
        }

        $file = $this->request->getFile('file_abstrak');
        try {
            $savedName = $this->absModel->moveUploadedFile($file, $userId, $eventId);
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', $e->getMessage());
        }

        $this->absModel->insert([
            'id_user'        => $userId,
            'id_kategori'    => $idKat,
            'event_id'       => $eventId,
            'judul'          => $judul,
            'file_abstrak'   => $savedName,
            'status'         => 'menunggu',
            'tanggal_upload' => date('Y-m-d H:i:s'),
            'revisi_ke'      => 0,
        ]);

        return redirect()->to('/presenter/abstrak')
            ->with('success','Abstrak terkirim. Status: menunggu review.');
    }

    /** DETAIL */
    public function detail(int $id)
    {
        $userId = (int) session()->get('id_user');
        $row = $this->absModel->getDetailWithRelationsForUser($id, $userId);
        if (!$row) {
            return redirect()->to('/presenter/abstrak')->with('error','Data tidak ditemukan.');
        }

        // badge
        $st = strtolower($row['status'] ?? 'menunggu');
        $badge = match ($st) {
            'menunggu'         => 'secondary',
            'sedang_direview'  => 'warning',
            'revisi'           => 'info',
            'diterima'         => 'success',
            'ditolak'          => 'danger',
            default            => 'secondary'
        };

        return view('role/presenter/abstrak/detail', [
            'title' => 'Detail Abstrak',
            'data'  => $row,
            'badge' => $badge,
        ]);
    }

    /** UPLOAD REVISI (hanya saat status revisi) */
    public function uploadRevisi(int $id)
    {
        $userId = (int) session()->get('id_user');

        $row = $this->absModel->where('id_abstrak', $id)
                              ->where('id_user', $userId)
                              ->first();
        if (!$row) {
            return redirect()->to('/presenter/abstrak')->with('error','Abstrak tidak ditemukan.');
        }
        if (strtolower($row['status']) !== 'revisi') {
            return redirect()->to('/presenter/abstrak/detail/'.$id)->with('error','Hanya dapat upload revisi saat status revisi.');
        }

        $file = $this->request->getFile('file_abstrak');
        try {
            $savedName = $this->absModel->moveUploadedFile($file, $userId, (int)$row['event_id']);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        $this->absModel->update($id, [
            'file_abstrak'   => $savedName,
            'status'         => 'menunggu',
            'tanggal_upload' => date('Y-m-d H:i:s'),
            'revisi_ke'      => (int)($row['revisi_ke'] ?? 0) + 1,
        ]);

        return redirect()->to('/presenter/abstrak/detail/'.$id)->with('success','File revisi terkirim. Status kembali menunggu review.');
    }

    /** DOWNLOAD FILE */
    public function download(string $segment)
    {
        // keamanan sederhana: hanya file di folder abstrak
        $path = WRITEPATH.'uploads/abstrak/'.basename($segment);
        if (!is_file($path)) {
            return redirect()->back()->with('error','File tidak ditemukan.');
        }
        return $this->response->download($path, null);
    }
}