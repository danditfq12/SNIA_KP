<?php

namespace App\Controllers\Role\Presenter;

use App\Controllers\BaseController;
use App\Models\EventModel;
use App\Models\EventRegistrationModel;
use App\Models\AbstrakModel;
use App\Models\KategoriAbstrakModel;

class Abstrak extends BaseController
{
    protected $eventModel;
    protected $regModel;
    protected $abstrakModel;
    protected $kategoriModel;

    public function __construct()
    {
        $this->eventModel    = new EventModel();
        $this->regModel      = new EventRegistrationModel();
        $this->abstrakModel  = new AbstrakModel();
        $this->kategoriModel = new KategoriAbstrakModel();
    }

    /** Map status -> badge class & label & hint ringkas */
    private function mapStatusMeta(?string $status): array
    {
        $s = strtolower((string)$status);
        return match ($s) {
            'menunggu'         => ['badge' => 'warning',  'label' => 'Menunggu',         'hint' => 'Menunggu review abstrak'],
            'sedang_direview'  => ['badge' => 'info',     'label' => 'Sedang Direview',  'hint' => 'Reviewer sedang menilai'],
            'diterima'         => ['badge' => 'success',  'label' => 'Diterima (ACC)',   'hint' => 'Abstrak diterima'],
            'ditolak'          => ['badge' => 'danger',   'label' => 'Ditolak',          'hint' => 'Abstrak ditolak'],
            'revisi'           => ['badge' => 'primary',  'label' => 'Revisi',           'hint' => 'Diminta revisi - upload ulang'],
            default            => ['badge' => 'secondary','label' => 'Belum Upload',     'hint' => 'Belum ada abstrak'],
        };
    }

    /** INDEX */
    public function index()
    {
        $userId = (int) session()->get('id_user');

        // Registrasi event user
        $regs = $this->regModel->listByUser($userId);

        // Inisialisasi container event
        $eventsById = [];
        foreach ($regs as $r) {
            $eventId = (int) $r['id_event'];
            $eventsById[$eventId] = [
                'reg'        => $r,
                'event'      => $this->eventModel->find($eventId),
                'abstract'   => null,   // last abstract (per event)
                'is_open'    => false,  // abstract submission open?
                'can_upload' => false,  // boleh upload (belum pernah / status revisi)
            ];
        }

        // Ambil semua abstrak user (dengan relasi)
        $allAbs = $this->abstrakModel->getByUserWithDetails($userId);

        // Tentukan abstrak terbaru per event
        $latestPerEvent = [];
        foreach ($allAbs as $a) {
            $eid = (int) $a['event_id'];
            if (!isset($latestPerEvent[$eid]) || strtotime($a['tanggal_upload']) > strtotime($latestPerEvent[$eid]['tanggal_upload'])) {
                $latestPerEvent[$eid] = $a;
            }
        }

        // Siapkan list “perlu upload” dan “riwayat”
        $needsUpload = []; // event yang butuh upload (belum pernah / revisi)
        $history     = []; // ringkasan semua abstrak terbaru per event (apapun statusnya) + info event

        foreach ($eventsById as $eid => $row) {
            $event = $row['event'] ?? null;
            if (!$event) {
                continue;
            }

            $isOpen = $this->eventModel->isAbstractSubmissionOpen($eid);
            $last   = $latestPerEvent[$eid] ?? null;

            // Tentukan boleh upload
            $canUpload = false;
            if ($isOpen) {
                if (!$last) {
                    $canUpload = true; // belum pernah kirim
                } else {
                    $canUpload = in_array(strtolower($last['status']), ['revisi'], true); // boleh upload saat revisi
                }
            }

            // Paket data untuk daftar "perlu upload" (atas)
            if ($canUpload) {
                $meta = $this->mapStatusMeta($last['status'] ?? null);
                $needsUpload[] = [
                    'event_id'          => $eid,
                    'title'             => $event['title'] ?? '-',
                    'event_date'        => $event['event_date'] ?? null,
                    'abstract_deadline' => $event['abstract_deadline'] ?? null,
                    'format'            => strtolower($event['format'] ?? ''),
                    'status_badge'      => $meta['badge'],         // warna badge di pojok
                    'status_label'      => $meta['label'],         // label badge
                    'hint'              => $meta['hint'],          // hint kecil
                    'can_upload'        => true,
                    'last_abs_id'       => $last['id_abstrak'] ?? null,
                ];
            }

            // Paket data untuk "riwayat": tampilkan kalau SUDAH PERNAH KIRIM (apapun statusnya)
            if ($last) {
                $meta = $this->mapStatusMeta($last['status'] ?? null);
                $history[] = [
                    'id_abstrak'     => (int) $last['id_abstrak'],
                    'judul'          => $last['judul'] ?? '-',
                    'nama_kategori'  => $last['nama_kategori'] ?? '-',
                    'status'         => strtolower($last['status'] ?? ''),
                    'status_badge'   => $meta['badge'],
                    'status_label'   => $meta['label'],
                    'status_hint'    => $meta['hint'],
                    'tanggal_upload' => $last['tanggal_upload'] ?? null,
                    // Info event
                    'event_id'       => $eid,
                    'event_title'    => $event['title'] ?? '-',
                    'event_date'     => $event['event_date'] ?? null,
                ];
            }
        }

        // Urutkan riwayat terbaru di atas (optional)
        usort($history, function ($a, $b) {
            return strtotime($b['tanggal_upload'] ?? '1970-01-01') <=> strtotime($a['tanggal_upload'] ?? '1970-01-01');
        });

        return view('role/presenter/abstrak/index', [
            'title'        => 'Abstrak',
            'uploadEvents' => $needsUpload, // hanya yang perlu upload/revisi
            'history'      => $history,     // semua yang sudah submit (apapun statusnya)
        ]);
    }

    /** FORM CREATE */
    public function create($eventId)
    {
        $userId  = (int) session()->get('id_user');
        $eventId = (int) $eventId;

        $event = $this->eventModel->find($eventId);
        if (!$event) return redirect()->to('/presenter/abstrak')->with('error','Event tidak ditemukan.');

        $reg = $this->regModel->findUserReg($eventId, $userId);
        if (!$reg) return redirect()->to('/presenter/abstrak')->with('error','Anda belum terdaftar pada event ini.');

        if (!$this->eventModel->isAbstractSubmissionOpen($eventId)) {
            return redirect()->to('/presenter/abstrak')->with('error','Pengumpulan abstrak telah ditutup.');
        }

        $lastAbs = $this->abstrakModel->where('id_user',$userId)
                    ->where('event_id',$eventId)
                    ->orderBy('id_abstrak','DESC')->first();
        if ($lastAbs && in_array($lastAbs['status'], ['menunggu','sedang_direview','diterima'], true)) {
            return redirect()->to('/presenter/abstrak')->with('error','Anda tidak dapat mengunggah lagi karena masih ada abstrak aktif.');
        }

        $kategoriList = $this->kategoriModel->orderBy('nama_kategori','ASC')->findAll();

        return view('role/presenter/abstrak/create', [
            'title'        => 'Kirim Abstrak',
            'event'        => $event,
            'eventId'      => $eventId,
            'kategoriList' => $kategoriList,
            'lastAbs'      => $lastAbs,
        ]);
    }

    /** STORE */
    public function store()
    {
        $userId     = (int) session()->get('id_user');
        $eventId    = (int) $this->request->getPost('event_id');
        $judul      = trim((string) $this->request->getPost('judul'));
        $idKategori = (int) $this->request->getPost('id_kategori');
        $file       = $this->request->getFile('file_abstrak');

        if (!$eventId || $judul === '' || !$file || $idKategori <= 0) {
            return redirect()->back()->withInput()->with('error','Lengkapi semua field (termasuk kategori).');
        }

        $event = $this->eventModel->find($eventId);
        if (!$event) return redirect()->to('/presenter/abstrak')->with('error','Event tidak ditemukan.');

        $reg = $this->regModel->findUserReg($eventId, $userId);
        if (!$reg) return redirect()->to('/presenter/abstrak')->with('error','Anda belum terdaftar pada event ini.');

        if (!$this->eventModel->isAbstractSubmissionOpen($eventId)) {
            return redirect()->to('/presenter/abstrak')->with('error','Pengumpulan abstrak telah ditutup.');
        }

        $already = $this->abstrakModel->where('id_user',$userId)
                      ->where('event_id',$eventId)
                      ->orderBy('id_abstrak','DESC')->first();
        if ($already && in_array($already['status'], ['menunggu','sedang_direview','diterima'], true)) {
            return redirect()->to('/presenter/abstrak')->with('error','Masih ada abstrak aktif untuk event ini.');
        }

        if (!$file->isValid()) return redirect()->back()->with('error','File tidak valid.');
        $ext = strtolower($file->getClientExtension() ?: '');
        if ($ext !== 'pdf') {
            return redirect()->back()->withInput()->with('error','File harus PDF.');
        }
        if ($file->getSize() > 5 * 1024 * 1024) {
            return redirect()->back()->withInput()->with('error','Ukuran maksimal 5MB.');
        }

        try {
            $stored = $this->abstrakModel->moveUploadedFile($file, $userId, $eventId);
            $this->abstrakModel->insert([
                'id_user'        => $userId,
                'id_kategori'    => $idKategori,
                'event_id'       => $eventId,
                'judul'          => $judul,
                'file_abstrak'   => $stored,
                'status'         => 'menunggu',
                'tanggal_upload' => date('Y-m-d H:i:s'),
                'revisi_ke'      => $already && $already['status']==='revisi' ? (int)$already['revisi_ke']+1 : 0,
            ]);
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', 'Gagal mengunggah: '.$e->getMessage());
        }

        return redirect()->to('/presenter/abstrak')
            ->with('success','Abstrak terkirim. Silakan tunggu konfirmasi dari panitia.');
    }

    /** DETAIL */
    public function detail($idAbstrak)
    {
        $userId = (int) session()->get('id_user');
        $row = $this->abstrakModel->getDetailWithRelationsForUser((int)$idAbstrak, $userId);
        if (!$row) {
            return redirect()->to('/presenter/abstrak')->with('error','Abstrak tidak ditemukan.');
        }

        $event = $this->eventModel->find((int)$row['event_id']);

        return view('role/presenter/abstrak/detail', [
            'title'  => 'Detail Abstrak',
            'abs'    => $row,
            'event'  => $event,
        ]);
    }

    /** DOWNLOAD */
    public function download($filename)
    {
        $path = WRITEPATH.'uploads/abstrak/'.$filename;
        if (!is_file($path)) {
            return redirect()->back()->with('error','File tidak ditemukan.');
        }
        return $this->response->download($path, null);
    }
}