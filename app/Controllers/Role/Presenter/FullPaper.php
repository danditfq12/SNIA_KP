<?php

namespace App\Controllers\Role\Presenter;

use App\Controllers\BaseController;
use App\Models\EventModel;
use App\Models\EventRegistrationModel;
use App\Models\AbstrakModel;
use App\Models\FullPaperModel;
use App\Models\KategoriAbstrakModel; // ⬅️ tambahkan

class Fullpaper extends BaseController
{
    protected EventModel $eventModel;
    protected EventRegistrationModel $regModel;
    protected AbstrakModel $abstrakModel;
    protected FullPaperModel $fpModel;
    protected KategoriAbstrakModel $kategoriModel; // ⬅️ tambahkan

    public function __construct()
    {
        $this->eventModel    = new EventModel();
        $this->regModel      = new EventRegistrationModel();
        $this->abstrakModel  = new AbstrakModel();
        $this->fpModel       = new FullPaperModel();
        $this->kategoriModel = new KategoriAbstrakModel(); // ⬅️ tambahkan
        helper(['date', 'text']);
    }

    private function isFullPaperOpen(array $event): bool
    {
        if (empty($event['is_active'])) return false;
        if (empty($event['full_paper_submission_active'])) return false;

        if (!empty($event['full_paper_deadline'])) {
            return (time() <= strtotime($event['full_paper_deadline']));
        }
        return (time() <= strtotime(($event['event_date'] ?? '2099-12-31') . ' 23:59:00'));
    }

    private function normalizeFpStatus(?string $raw): string
    {
        $s = strtolower(trim((string) $raw));
        return match (true) {
            $s === ''                                                                    => 'NONE',
            in_array($s, ['uploaded','menunggu','pending','sedang_direview'], true)     => 'UPLOADED',
            in_array($s, ['revisi','revision'], true)                                    => 'REVISION',
            in_array($s, ['diterima','accepted','acc','approved'], true)                => 'ACCEPTED',
            in_array($s, ['ditolak','rejected'], true)                                   => 'REJECTED',
            default                                                                      => strtoupper($s),
        };
    }

    private function mapAbsMeta(?array $abs): array
    {
        $s = strtolower((string)($abs['status'] ?? ''));
        return match ($s) {
            'diterima'         => ['badge' => 'success',  'label' => 'Abstrak Diterima'],
            'ditolak'          => ['badge' => 'danger',   'label' => 'Abstrak Ditolak'],
            'sedang_direview'  => ['badge' => 'info',     'label' => 'Abstrak Direview'],
            'menunggu'         => ['badge' => 'warning',  'label' => 'Abstrak Menunggu'],
            'revisi'           => ['badge' => 'primary',  'label' => 'Abstrak Revisi'],
            default            => ['badge' => 'secondary','label' => 'Belum Upload Abstrak'],
        };
    }

    private function mapFpStatusMeta(string $status): array
    {
        $s = strtoupper($status);
        return match ($s) {
            'NONE'     => ['badge' => 'secondary', 'label' => 'Belum Upload',     'hint' => 'Belum ada full paper'],
            'UPLOADED' => ['badge' => 'info',      'label' => 'Menunggu Review',  'hint' => 'Reviewer sedang menilai'],
            'REVISION' => ['badge' => 'warning',   'label' => 'Revisi',           'hint' => 'Unggah revisi sesuai catatan'],
            'ACCEPTED' => ['badge' => 'success',   'label' => 'Diterima',         'hint' => 'Full paper diterima'],
            'REJECTED' => ['badge' => 'danger',    'label' => 'Ditolak',          'hint' => 'Unggah ulang jika diperbolehkan'],
            default    => ['badge' => 'secondary', 'label' => ucfirst(strtolower($s)), 'hint' => 'Status tidak dikenal'],
        };
    }

    /** Return: [$status, $path, $createdAt, $reviewedAt, $decisionAt, $notes, $fpRow] */
    private function latestFullpaperMeta(int $userId, int $eventId, ?array $absRow): array
    {
        $row = method_exists($this->fpModel, 'getLatestRowByUserEvent')
            ? $this->fpModel->getLatestRowByUserEvent($userId, $eventId)
            : null;

        if ($row) {
            $path       = (string)($row['full_paper_path'] ?? '');
            $statusRaw  = (string)($row['full_paper_status'] ?? '');
            $createdAt  = $row['full_paper_uploaded_at'] ?? null;
            $reviewedAt = $row['reviewed_at'] ?? null;
            $decisionAt = $row['decision_at'] ?? null;
            $notes      = $row['review_notes'] ?? ($row['catatan_reviewer'] ?? '');

            if ($path === '') {
                return ['NONE', '', null, null, null, '', $row];
            }
            $status = $this->normalizeFpStatus($statusRaw ?: 'UPLOADED');
            return [$status, $path, $createdAt, $reviewedAt, $decisionAt, $notes, $row];
        }

        $pathAbs = (string)($absRow['full_paper_path'] ?? '');
        if ($pathAbs !== '') {
            $statusAbs = $this->normalizeFpStatus($absRow['full_paper_status'] ?? 'UPLOADED');
            return [$statusAbs, $pathAbs, ($absRow['full_paper_uploaded_at'] ?? null), null, null, '', null];
        }

        return ['NONE', '', null, null, null, '', null];
    }

    private function isAbstractEligible(?array $abs): bool
    {
        if (empty($abs)) return false;
        $s = strtolower((string)($abs['status'] ?? ''));
        if ($s === 'ditolak') return false;
        return true;
    }

    public function index()
    {
        $userId = (int) session()->get('id_user');
        $regs   = $this->regModel->listByUser($userId) ?? [];

        $needUpload = [];
        $history    = [];

        foreach ($regs as $r) {
            $eid = (int)($r['id_event'] ?? 0);
            if (!$eid) continue;

            $event = $this->eventModel->find($eid);
            if (!$event) continue;

            $abs = $this->abstrakModel->where('id_user', $userId)
                                      ->where('event_id', $eid)
                                      ->orderBy('id_abstrak', 'DESC')->first();

            $absEligible = $this->isAbstractEligible($abs);

            [$fpStatus, $fpPath, $fpTs] = $this->latestFullpaperMeta($userId, $eid, $abs);
            if (!$absEligible) { $fpStatus = 'NONE'; $fpPath = ''; $fpTs = null; }

            $meta    = $this->mapFpStatusMeta($fpStatus);
            $isOpen  = $this->isFullPaperOpen($event);

            $canUpload = $isOpen && $absEligible && in_array($fpStatus, ['NONE','REVISION','REJECTED'], true);

            if ($canUpload) {
                $needUpload[] = [
                    'event_id'            => $eid,
                    'title'               => $event['title'] ?? '-',
                    'event_date'          => $event['event_date'] ?? null,
                    'full_paper_deadline' => $event['full_paper_deadline'] ?? null,
                    'format'              => strtolower($event['format'] ?? ''),
                    'status_badge'        => $meta['badge'],
                    'status_label'        => $meta['label'],
                    'hint'                => $meta['hint'],
                    'fp_status'           => $fpStatus,
                ];
            }

            if ($fpStatus !== 'NONE') {
                $history[] = [
                    'event_id'      => $eid,
                    'event_title'   => $event['title'] ?? '-',
                    'event_date'    => $event['event_date'] ?? null,
                    'fp_status'     => $fpStatus,
                    'status_badge'  => $meta['badge'],
                    'status_label'  => $meta['label'],
                    'status_hint'   => $meta['hint'],
                    'fp_path'       => $fpPath,
                    'uploaded_at'   => $fpTs,
                    'format'        => strtolower($event['format'] ?? ''),
                ];
            }
        }

        usort($history, function($a,$b){
            $ta = $a['uploaded_at'] ? strtotime($a['uploaded_at']) : strtotime($a['event_date'] ?? '1970-01-01');
            $tb = $b['uploaded_at'] ? strtotime($b['uploaded_at']) : strtotime($b['event_date'] ?? '1970-01-01');
            return $tb <=> $ta;
        });

        return view('role/presenter/fullpaper/index', [
            'title'      => 'Full Paper',
            'needUpload' => $needUpload,
            'history'    => $history,
        ]);
    }

    public function detail($eventId)
    {
        $userId  = (int) session()->get('id_user');
        $eventId = (int) $eventId;

        $event = $this->eventModel->find($eventId);
        if (!$event) return redirect()->to('/presenter/fullpaper')->with('error', 'Event tidak ditemukan.');

        $reg = $this->regModel->findUserReg($eventId, $userId);
        if (!$reg) return redirect()->to('/presenter/fullpaper')->with('error', 'Anda belum terdaftar pada event ini.');

        // ⬇️ Ambil abstrak TERAKHIR user untuk event ini + join kategori agar ada `nama_kategori`
        $absTbl = $this->abstrakModel->getTable() ?? 'abstrak';
        $katTbl = $this->kategoriModel->getTable() ?? 'kategori_abstrak';

        $abs = $this->abstrakModel->select("$absTbl.*, $katTbl.nama_kategori")
                                  ->join($katTbl, "$katTbl.id_kategori = $absTbl.id_kategori", 'left')
                                  ->where("$absTbl.id_user", $userId)
                                  ->where("$absTbl.event_id", $eventId)
                                  ->orderBy("$absTbl.id_abstrak", 'DESC')
                                  ->first();

        // Fallback kalau kolom/alias beda
        if ($abs && empty($abs['nama_kategori']) && !empty($abs['id_kategori'])) {
            $kat = $this->kategoriModel->find((int)$abs['id_kategori']);
            if ($kat) {
                $abs['nama_kategori'] = $kat['nama_kategori'] ?? ($kat['nama'] ?? null);
            }
        }

        [$status, $path, $createdAt, $reviewedAt, $decisionAt, $notes]
            = $this->latestFullpaperMeta($userId, $eventId, $abs);

        $absEligible = $this->isAbstractEligible($abs);
        if (!$absEligible) { $status = 'NONE'; $path = ''; }

        $statusMeta   = $this->mapFpStatusMeta($status);
        $absMeta      = $this->mapAbsMeta($abs);
        $isOpen       = $this->isFullPaperOpen($event);
        $canReupload  = $isOpen && $absEligible && in_array($status, ['REVISION','REJECTED'], true);
        $canUploadNew = $isOpen && $absEligible && $status === 'NONE';

        return view('role/presenter/fullpaper/detail', [
            'title'        => 'Detail Full Paper',
            'event'        => $event,
            'abs'          => $abs,
            'status'       => $status,
            'status_meta'  => $statusMeta,
            'path'         => $path,
            'created_at'   => $createdAt,
            'reviewed_at'  => $reviewedAt,
            'decision_at'  => $decisionAt,
            'notes'        => $notes,
            'is_open'      => $isOpen,
            'abs_meta'     => $absMeta,
            'can_reupload' => $canReupload,
            'can_upload'   => $canUploadNew,
            'event_id'     => $eventId,
        ]);
    }

    public function create($eventId)
    {
        $userId  = (int) session()->get('id_user');
        $eventId = (int) $eventId;

        $event = $this->eventModel->find($eventId);
        if (!$event) return redirect()->to('/presenter/fullpaper')->with('error', 'Event tidak ditemukan.');

        $reg = $this->regModel->findUserReg($eventId, $userId);
        if (!$reg) return redirect()->to('/presenter/events/detail/'.$eventId)->with('error', 'Anda belum terdaftar pada event ini.');

        if (!$this->isFullPaperOpen($event)) {
            return redirect()->to('/presenter/fullpaper')->with('error', 'Pengumpulan Full Paper ditutup.');
        }

        // Ambil abstrak + kategori agar konsisten di form
        $absTbl = $this->abstrakModel->getTable() ?? 'abstrak';
        $katTbl = $this->kategoriModel->getTable() ?? 'kategori_abstrak';
        $abs = $this->abstrakModel->select("$absTbl.*, $katTbl.nama_kategori")
                                  ->join($katTbl, "$katTbl.id_kategori = $absTbl.id_kategori", 'left')
                                  ->where("$absTbl.id_user", $userId)
                                  ->where("$absTbl.event_id", $eventId)
                                  ->orderBy("$absTbl.id_abstrak", 'DESC')
                                  ->first();

        [$fpStatus] = $this->latestFullpaperMeta($userId, $eventId, $abs);
        $absEligible = $this->isAbstractEligible($abs);

        if (!$absEligible) {
            return redirect()->to('/presenter/abstrak/create/'.$eventId)
                ->with('error', 'Upload Full Paper tersedia setelah Anda mengunggah abstrak (dan tidak ditolak).');
        }

        if ($fpStatus !== 'NONE' && !in_array($fpStatus, ['REVISION','REJECTED'], true)) {
            return redirect()->to('/presenter/fullpaper/detail/'.$eventId)
                ->with('error', 'Anda sudah mengunggah Full Paper. Tidak bisa upload lagi pada status saat ini.');
        }

        return view('role/presenter/fullpaper/create', [
            'title'   => 'Upload Full Paper',
            'event'   => $event,
            'eventId' => $eventId,
            'abs'     => $abs,
        ]);
    }

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

        $abs = $this->abstrakModel->where('id_user',$userId)
                                  ->where('event_id',$eventId)
                                  ->orderBy('id_abstrak','DESC')->first();

        [$fpStatus]  = $this->latestFullpaperMeta($userId, $eventId, $abs);
        $absEligible = $this->isAbstractEligible($abs);

        if (!$absEligible) {
            return redirect()->to('/presenter/abstrak/create/'.$eventId)
                ->with('error', 'Upload Full Paper tersedia setelah Anda mengunggah abstrak (dan tidak ditolak).');
        }

        if ($fpStatus !== 'NONE' && !in_array($fpStatus, ['REVISION','REJECTED'], true)) {
            return redirect()->to('/presenter/fullpaper/detail/'.$eventId)
                ->with('error', 'Anda sudah mengunggah Full Paper. Tidak bisa upload lagi pada status saat ini.');
        }

        if (!$file->isValid()) return redirect()->back()->with('error','File tidak valid.');
        $ext = strtolower($file->getClientExtension() ?: '');
        if ($ext !== 'pdf') return redirect()->back()->withInput()->with('error','File harus PDF.');
        if ($file->getSize() > 20 * 1024 * 1024) {
            return redirect()->back()->withInput()->with('error','Ukuran maksimal 20MB.');
        }

        try {
            $stored = $this->fpModel->moveUploadedFile($file, $userId, $eventId);
            $ok = $this->fpModel->attachFullPaperByUserEvent(
                $userId,
                $eventId,
                $stored,
                \App\Models\FullPaperModel::STATUS_UPLOADED
            );
            if (!$ok) {
                return redirect()->back()->withInput()->with('error', 'Tidak ditemukan data abstrak/submission untuk ditempeli.');
            }
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', 'Gagal mengunggah: '.$e->getMessage());
        }

        return redirect()->to('/presenter/fullpaper/detail/'.$eventId)
            ->with('success', 'Full Paper berhasil diunggah. Menunggu review.');
    }

    public function download($filename)
    {
        $path = WRITEPATH.'uploads/fullpaper/'.$filename;
        if (!is_file($path)) {
            return redirect()->back()->with('error','File tidak ditemukan.');
        }
        return $this->response->download($path, null);
    }
}