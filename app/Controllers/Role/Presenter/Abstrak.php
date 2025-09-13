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

    /** INDEX */
    public function index()
    {
        $userId = (int) session()->get('id_user');

        $regs = $this->regModel->listByUser($userId);
        $eventsById = [];
        foreach ($regs as $r) {
            $eventsById[(int)$r['id_event']] = [
                'reg'        => $r,
                'event'      => $this->eventModel->find((int)$r['id_event']),
                'abstract'   => null,
                'can_upload' => false,
                'is_open'    => false,
            ];
        }

        $allAbs = $this->abstrakModel->getByUserWithDetails($userId);
        $latestPerEvent = [];
        $history = [];

        foreach ($allAbs as $a) {
            $eid = (int) $a['event_id'];
            if (!isset($latestPerEvent[$eid]) || strtotime($a['tanggal_upload']) > strtotime($latestPerEvent[$eid]['tanggal_upload'])) {
                $latestPerEvent[$eid] = $a;
            }
        }

        foreach ($eventsById as $eid => &$row) {
            $row['abstract'] = $latestPerEvent[$eid] ?? null;
            $row['is_open']  = $this->eventModel->isAbstractSubmissionOpen($eid);

            $last = $row['abstract'];
            if ($row['is_open']) {
                if (!$last) {
                    $row['can_upload'] = true;
                } else {
                    $row['can_upload'] = in_array($last['status'], ['revisi'], true);
                }
            }

            if ($last && in_array($last['status'], ['diterima','ditolak'], true)) {
                $history[] = $last;
            }
        }
        unset($row);

        return view('role/presenter/abstrak/index', [
            'title'      => 'Abstrak',
            'events'     => $eventsById,
            'history'    => $history,
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