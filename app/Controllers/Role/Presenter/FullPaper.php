<?php

namespace App\Controllers\Role\Presenter;

use App\Controllers\BaseController;
use App\Models\EventModel;
use App\Models\EventRegistrationModel;
use App\Models\AbstrakModel;
use App\Models\FullPaperModel;

class Fullpaper extends BaseController
{
    protected EventModel $eventModel;
    protected EventRegistrationModel $regModel;
    protected AbstrakModel $abstrakModel;
    protected FullPaperModel $fpModel;

    public function __construct()
    {
        $this->eventModel = new EventModel();             // (admin model tapi aman dipakai baca event)
        $this->regModel   = new EventRegistrationModel(); // daftar event yg user ikuti
        $this->abstrakModel = new AbstrakModel();         // untuk metadata abstrak (judul/kategori/status)
        $this->fpModel    = new FullPaperModel();         // adaptif (submissions/abstrak)
        helper(['date', 'text']);
    }

    /* ===== Util: apakah FP window open? ===== */
    private function isFullPaperOpen(array $event): bool
    {
        if (empty($event['is_active'])) return false;
        if (empty($event['full_paper_submission_active'])) return false;

        // deadline opsional
        if (!empty($event['full_paper_deadline'])) {
            return (time() <= strtotime($event['full_paper_deadline']));
        }
        // default: buka sampai hari-H event
        return (time() <= strtotime(($event['event_date'] ?? '2099-12-31').' 23:59:00'));
    }

    /* ===== Util: ambil abstrak terbaru per-event milik user ===== */
    private function latestAbstractByEvent(int $userId): array
    {
        $allAbs = $this->abstrakModel->getByUserWithDetails($userId); // asumsi sudah ada helper ini
        $latest = [];
        foreach ($allAbs as $a) {
            $eid = (int) $a['event_id'];
            if (!isset($latest[$eid]) || strtotime($a['tanggal_upload']) > strtotime($latest[$eid]['tanggal_upload'])) {
                $latest[$eid] = $a;
            }
        }
        return $latest;
    }

    /* ===== Index: ringkasan & CTA ===== */
    public function index()
    {
        $userId = (int) session()->get('id_user');

        // event yang user sudah daftar (presenter)
        $regs = $this->regModel->listByUser($userId);
        $latestAbs = $this->latestAbstractByEvent($userId);

        $rows = [];
        foreach ($regs as $r) {
            $eid   = (int) $r['id_event'];
            $event = $this->eventModel->find($eid);
            if (!$event) continue;

            $abs   = $latestAbs[$eid] ?? null;
            $fpStatus = strtoupper((string)($abs['full_paper_status'] ?? 'NONE'));
            $fpPath   = (string)($abs['full_paper_path'] ?? '');

            $rows[] = [
                'event_id'   => $eid,
                'event'      => $event,
                'abs'        => $abs,        // bisa null (belum upload abstrak)
                'fp_status'  => $fpStatus,   // NONE|UPLOADED|REVISION|ACCEPTED|REJECTED
                'fp_path'    => $fpPath,
                'fp_open'    => $this->isFullPaperOpen($event),
            ];
        }

        return view('role/presenter/fullpaper/index', [
            'title' => 'Full Paper',
            'rows'  => $rows,
        ]);
    }

    /* ===== Form upload ===== */
    public function create($eventId)
    {
        $userId  = (int) session()->get('id_user');
        $eventId = (int) $eventId;

        $event = $this->eventModel->find($eventId);
        if (!$event) {
            return redirect()->to('/presenter/fullpaper')->with('error', 'Event tidak ditemukan.');
        }

        // harus sudah registrasi event
        $reg = $this->regModel->findUserReg($eventId, $userId);
        if (!$reg) {
            return redirect()->to('/presenter/events/detail/'.$eventId)->with('error', 'Anda belum terdaftar pada event ini.');
        }

        if (!$this->isFullPaperOpen($event)) {
            return redirect()->to('/presenter/fullpaper')->with('error', 'Pengumpulan Full Paper ditutup.');
        }

        // ambil abstrak terbaru (boleh null → tetap boleh unggah FP sesuai kebijakan)
        $abs = $this->fpModel->getLatestRowByUserEvent($userId, $eventId);

        return view('role/presenter/fullpaper/create', [
            'title'   => 'Upload Full Paper',
            'event'   => $event,
            'eventId' => $eventId,
            'abs'     => $abs, // untuk menampilkan status abstrak terakhir (info)
        ]);
    }

    /* ===== Simpan file ===== */
    public function store()
    {
        $userId  = (int) session()->get('id_user');
        $eventId = (int) $this->request->getPost('event_id');
        $file    = $this->request->getFile('full_paper');

        if (!$eventId || !$file) {
            return redirect()->back()->withInput()->with('error', 'Lengkapi data & unggah file.');
        }

        $event = $this->eventModel->find($eventId);
        if (!$event) return redirect()->to('/presenter/fullpaper')->with('error','Event tidak ditemukan.');

        $reg = $this->regModel->findUserReg($eventId, $userId);
        if (!$reg) return redirect()->to('/presenter/fullpaper')->with('error','Anda belum terdaftar pada event ini.');

        if (!$this->isFullPaperOpen($event)) {
            return redirect()->to('/presenter/fullpaper')->with('error', 'Pengumpulan Full Paper ditutup.');
        }

        if (!$file->isValid()) return redirect()->back()->with('error','File tidak valid.');
        $ext = strtolower($file->getClientExtension() ?: '');
        if ($ext !== 'pdf') return redirect()->back()->withInput()->with('error','File harus PDF.');
        if ($file->getSize() > 20 * 1024 * 1024) {
            return redirect()->back()->withInput()->with('error','Ukuran maksimal 20MB.');
        }

        // Simpan file + tempelkan ke baris abstrak/submission terbaru user-event
        try {
            $stored = $this->fpModel->moveUploadedFile($file, $userId, $eventId);
            $ok = $this->fpModel->attachFullPaperByUserEvent($userId, $eventId, $stored, \App\Models\FullPaperModel::STATUS_UPLOADED);
            if (!$ok) {
                return redirect()->back()->withInput()->with('error', 'Tidak ditemukan data abstrak/submission untuk ditempeli.');
            }
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', 'Gagal mengunggah: '.$e->getMessage());
        }

        return redirect()->to('/presenter/fullpaper')
            ->with('success', 'Full Paper berhasil diunggah. Reviewer akan menilai. Jika abstrak ditolak, panitia dapat menyesuaikan penanganannya.');
    }

    /* ===== Unduh (opsional) ===== */
    public function download($filename)
    {
        $path = WRITEPATH.'uploads/fullpaper/'.$filename;
        if (!is_file($path)) {
            return redirect()->back()->with('error','File tidak ditemukan.');
        }
        return $this->response->download($path, null);
    }
}