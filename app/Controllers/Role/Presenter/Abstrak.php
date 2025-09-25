<?php

namespace App\Controllers\Role\Presenter;

use App\Controllers\BaseController;
use App\Models\EventModel;
use App\Models\EventRegistrationModel;
use App\Models\AbstrakModel;
use App\Models\KategoriAbstrakModel;

class Abstrak extends BaseController
{
    protected EventModel $eventModel;
    protected EventRegistrationModel $regModel;
    protected AbstrakModel $abstrakModel;
    protected KategoriAbstrakModel $kategoriModel;

    public function __construct()
    {
        $this->eventModel    = new EventModel();
        $this->regModel      = new EventRegistrationModel();
        $this->abstrakModel  = new AbstrakModel();
        $this->kategoriModel = new KategoriAbstrakModel();
        helper(['date', 'text', 'filesystem']);
    }

    /** Kontributor lengkap (wajib afiliasi) */
    private function isContributorCompleted(?array $reg): bool
    {
        if (!$reg) return false;
        foreach (['contributor_done','kontributor_done','profile_completed','is_profile_completed'] as $f) {
            if (array_key_exists($f, $reg)) {
                $v = $reg[$f];
                if ($v === true || $v === 1 || $v === '1' || $v === 't' || $v === 'true') return true;
            }
        }
        if (!empty($reg['afiliasi'])) return true;
        return false;
    }

    /** Map meta status utk view */
    private function mapStatusMeta(?string $status): array
    {
        $s = strtolower((string)$status);
        return match ($s) {
            'menunggu'         => ['badge'=>'warning','label'=>'Menunggu','hint'=>'Menunggu review abstrak'],
            'sedang_direview'  => ['badge'=>'info','label'=>'Sedang direview','hint'=>'Reviewer sedang menilai'],
            'diterima'         => ['badge'=>'success','label'=>'Diterima (ACC)','hint'=>'Abstrak diterima'],
            'ditolak'          => ['badge'=>'danger','label'=>'Ditolak','hint'=>'Abstrak ditolak (unggah ulang)'],
            'revisi'           => ['badge'=>'primary','label'=>'Revisi','hint'=>'Unggah revisi abstrak'],
            default            => ['badge'=>'secondary','label'=>'Belum Upload','hint'=>'Belum ada abstrak'],
        };
    }

    /** INDEX */
    public function index()
    {
        $userId = (int) session()->get('id_user');

        $regs = $this->regModel->listByUser($userId) ?? [];

        // Ambil abstrak terakhir per-event
        $allAbs = $this->abstrakModel->getByUserWithDetails($userId) ?? [];
        $latestPerEvent = [];
        foreach ($allAbs as $a) {
            $eid = (int) ($a['event_id'] ?? 0);
            if (!$eid) continue;
            $ts  = !empty($a['tanggal_upload']) ? strtotime($a['tanggal_upload']) : 0;
            $cur = !empty($latestPerEvent[$eid]['tanggal_upload']) ? strtotime($latestPerEvent[$eid]['tanggal_upload']) : -1;
            if (!isset($latestPerEvent[$eid]) || $ts > $cur) $latestPerEvent[$eid] = $a;
        }

        $needsUpload = [];
        $history     = [];

        foreach ($regs as $r) {
            $eid = (int) ($r['id_event'] ?? 0);
            if (!$eid) continue;

            $event = $this->eventModel->find($eid);
            if (!$event) continue;

            $isOpen = $this->eventModel->isAbstractSubmissionOpen($eid);
            $last   = $latestPerEvent[$eid] ?? null;

            $canUpload = false;
            if ($isOpen) {
                if (!$last) $canUpload = true;
                else {
                    $ls = strtolower((string)($last['status'] ?? ''));
                    $canUpload = in_array($ls, ['revisi','ditolak'], true);
                }
            }

            if ($canUpload) {
                $meta = $this->mapStatusMeta($last['status'] ?? null);
                $needsUpload[] = [
                    'event_id'          => $eid,
                    'title'             => $event['title'] ?? '-',
                    'event_date'        => $event['event_date'] ?? null,
                    'abstract_deadline' => $event['abstract_deadline'] ?? null,
                    'format'            => strtolower($event['format'] ?? ''),
                    'status_badge'      => $meta['badge'],
                    'status_label'      => $meta['label'],
                    'hint'              => $meta['hint'],
                    'last_abs_id'       => $last['id_abstrak'] ?? null,
                ];
            }

            if ($last) {
                $meta = $this->mapStatusMeta($last['status'] ?? null);
                $history[] = [
                    'id_abstrak'     => (int) ($last['id_abstrak'] ?? 0),
                    'judul'          => $last['judul'] ?? '-',
                    'nama_kategori'  => $last['nama_kategori'] ?? '-',
                    'status'         => strtolower((string)($last['status'] ?? '')),
                    'status_badge'   => $meta['badge'],
                    'status_label'   => $meta['label'],
                    'status_hint'    => $meta['hint'],
                    'tanggal_upload' => $last['tanggal_upload'] ?? null,
                    'event_id'       => $eid,
                    'event_title'    => $event['title'] ?? '-',
                    'event_date'     => $event['event_date'] ?? null,
                ];
            }
        }

        usort($history, fn($a,$b) => strtotime($b['tanggal_upload'] ?? '1970-01-01') <=> strtotime($a['tanggal_upload'] ?? '1970-01-01'));

        return view('role/presenter/abstrak/index', [
            'title'        => 'Abstrak',
            'uploadEvents' => $needsUpload,
            'history'      => $history,
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

        if (!$this->isContributorCompleted($reg)) {
            return redirect()->to('/presenter/kontributor/start/'.$eventId)
                ->with('error','Lengkapi data kontributor (minimal afiliasi) sebelum upload abstrak.');
        }

        if (!$this->eventModel->isAbstractSubmissionOpen($eventId)) {
            return redirect()->to('/presenter/abstrak')->with('error','Pengumpulan abstrak telah ditutup.');
        }

        // Cegah dobel aktif
        $lastAbs = $this->abstrakModel->where('id_user',$userId)
                    ->where('event_id',$eventId)->orderBy('id_abstrak','DESC')->first();
        if ($lastAbs && in_array(strtolower((string)$lastAbs['status']), ['menunggu','sedang_direview','diterima'], true)) {
            return redirect()->to('/presenter/abstrak')->with('error','Masih ada abstrak aktif untuk event ini.');
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

        if (!$this->isContributorCompleted($reg)) {
            return redirect()->to('/presenter/kontributor/start/'.$eventId)
                ->with('error','Lengkapi data kontributor (minimal afiliasi) sebelum upload abstrak.');
        }

        if (!$this->eventModel->isAbstractSubmissionOpen($eventId)) {
            return redirect()->to('/presenter/abstrak')->with('error','Pengumpulan abstrak telah ditutup.');
        }

        $already = $this->abstrakModel->where('id_user',$userId)
                      ->where('event_id',$eventId)->orderBy('id_abstrak','DESC')->first();
        if ($already && in_array(strtolower((string)$already['status']), ['menunggu','sedang_direview','diterima'], true)) {
            return redirect()->to('/presenter/abstrak')->with('error','Masih ada abstrak aktif untuk event ini.');
        }

        if (!$file->isValid()) return redirect()->back()->with('error','File tidak valid.');

        $ext  = strtolower($file->getClientExtension() ?: '');
        $mime = strtolower($file->getMimeType() ?: '');
        if ($ext !== 'pdf' || !str_contains($mime, 'pdf')) {
            return redirect()->back()->withInput()->with('error','File harus PDF.');
        }
        if ($file->getSize() > 5 * 1024 * 1024) {
            return redirect()->back()->withInput()->with('error','Ukuran maksimal 5MB.');
        }

        try {
            if (method_exists($this->abstrakModel, 'moveUploadedFile')) {
                $stored = $this->abstrakModel->moveUploadedFile($file, $userId, $eventId);
            } else {
                $dir = WRITEPATH.'uploads/abstrak';
                if (!is_dir($dir)) @mkdir($dir, 0777, true);
                $newName = 'abs_'.$userId.'_'.$eventId.'_'.time().'.pdf';
                $file->move($dir, $newName, true);
                $stored = $newName;
            }

            $this->abstrakModel->insert([
                'id_user'        => $userId,
                'id_kategori'    => $idKategori,
                'event_id'       => $eventId,
                'judul'          => $judul,
                'file_abstrak'   => $stored,
                'status'         => 'menunggu',
                'tanggal_upload' => date('Y-m-d H:i:s'),
                'revisi_ke'      => ($already && strtolower((string)$already['status'])==='revisi') ? (int)$already['revisi_ke']+1 : 0,
            ]);
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', 'Gagal mengunggah: '.$e->getMessage());
        }

        return redirect()->to('/presenter/abstrak')->with('success','Abstrak terkirim.');
    }

    /** DETAIL */
    public function detail($idAbstrak)
    {
        $userId = (int) session()->get('id_user');
        $row = $this->abstrakModel->getDetailWithRelationsForUser((int)$idAbstrak, $userId);
        if (!$row) return redirect()->to('/presenter/abstrak')->with('error','Abstrak tidak ditemukan.');

        $event = $this->eventModel->find((int)$row['event_id']);

        return view('role/presenter/abstrak/detail', [
            'title' => 'Detail Abstrak',
            'abs'   => $row,
            'event' => $event,
        ]);
    }

    /** DOWNLOAD */
    public function download($filename)
    {
        $path = WRITEPATH.'uploads/abstrak/'.$filename;
        if (!is_file($path)) return redirect()->back()->with('error','File tidak ditemukan.');
        return $this->response->download($path, null);
    }
}