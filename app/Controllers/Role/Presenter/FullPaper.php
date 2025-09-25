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
        $this->eventModel   = new EventModel();
        $this->regModel     = new EventRegistrationModel();
        $this->abstrakModel = new AbstrakModel();
        $this->fpModel      = new FullPaperModel();
        helper(['date', 'text']);
    }

    /* ===== util: window pengumpulan FP ===== */
    private function isFullPaperOpen(array $event): bool
    {
        if (empty($event['is_active'])) return false;
        if (empty($event['full_paper_submission_active'])) return false;

        if (!empty($event['full_paper_deadline'])) {
            return (time() <= strtotime($event['full_paper_deadline']));
        }
        return (time() <= strtotime(($event['event_date'] ?? '2099-12-31') . ' 23:59:00'));
    }

    /* ===== util: abstrak terbaru per event milik user ===== */
    private function latestAbstractByEvent(int $userId): array
    {
        // Wajib mengembalikan minimal: event_id, status, tanggal_upload, full_paper_status, full_paper_path
        $allAbs = $this->abstrakModel->getByUserWithDetails($userId);
        $latest = [];
        foreach ($allAbs as $a) {
            $eid = (int) $a['event_id'];
            if (!isset($latest[$eid]) || strtotime($a['tanggal_upload']) > strtotime($latest[$eid]['tanggal_upload'])) {
                $latest[$eid] = $a;
            }
        }
        return $latest;
    }

    /* ===== util: badge abstrak (untuk tampilan kecil) ===== */
    private function mapAbsMeta(?array $abs): array
    {
        $s = strtolower((string)($abs['status'] ?? ''));
        return match ($s) {
            'diterima'         => ['badge' => 'success', 'label' => 'Abstrak ACC'],
            'ditolak'          => ['badge' => 'danger',  'label' => 'Abstrak Ditolak'],
            'sedang_direview'  => ['badge' => 'info',    'label' => 'Abstrak Direview'],
            'menunggu'         => ['badge' => 'warning', 'label' => 'Abstrak Menunggu'],
            default            => ['badge' => 'secondary','label'=> 'Belum upload abstrak'],
        };
    }

    /* ===== util: normalisasi status FP -> NONE|UPLOADED|REVISION|ACCEPTED|REJECTED ===== */
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

    /* ===== util: meta FP terbaru (prioritas tabel fullpaper) ===== */
    private function latestFullpaperMeta(int $userId, int $eventId, ?array $absRow): array
    {
        // Return: [$status, $path, $createdAt, $reviewedAt, $decisionAt, $notes, $fpRow]
        $fpRow = method_exists($this->fpModel, 'getLatestRowByUserEvent')
            ? $this->fpModel->getLatestRowByUserEvent($userId, $eventId)
            : null;

        if ($fpRow) {
            $status      = $this->normalizeFpStatus($fpRow['status'] ?? '');
            $path        = (string)($fpRow['file_path'] ?? $fpRow['path'] ?? '');
            $createdAt   = $fpRow['created_at']   ?? ($fpRow['tanggal_upload'] ?? null);
            $reviewedAt  = $fpRow['reviewed_at']  ?? null;
            $decisionAt  = $fpRow['decision_at']  ?? null;
            $notes       = $fpRow['review_notes'] ?? ($fpRow['catatan_reviewer'] ?? '');
            return [$status, $path, $createdAt, $reviewedAt, $decisionAt, $notes, $fpRow];
        }

        // fallback (kolom lama di abstrak)
        $status      = $this->normalizeFpStatus($absRow['full_paper_status'] ?? '');
        $path        = (string)($absRow['full_paper_path'] ?? '');
        $createdAt   = null;
        $reviewedAt  = null;
        $decisionAt  = null;
        $notes       = '';
        return [$status, $path, $createdAt, $reviewedAt, $decisionAt, $notes, null];
    }

    /* ===== util: map status FP ke meta UI ===== */
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

    /* ===== INDEX (tetap disertakan agar “full controller”) ===== */
    public function index()
    {
        $userId = (int) session()->get('id_user');

        $regs      = $this->regModel->listByUser($userId);
        $latestAbs = $this->latestAbstractByEvent($userId);

        $needUpload = [];
        $history    = [];

        foreach ($regs as $r) {
            $eid   = (int) $r['id_event'];
            $event = $this->eventModel->find($eid);
            if (!$event) continue;

            $abs         = $latestAbs[$eid] ?? null;
            $absAccepted = !empty($abs) && strtolower($abs['status'] ?? '') === 'diterima';

            [$fpStatus, $fpPath, $fpTs] = $this->latestFullpaperMeta($userId, $eid, $abs);

            $meta     = $this->mapFpStatusMeta($fpStatus);
            $absMeta  = $this->mapAbsMeta($abs);
            $isOpen   = $this->isFullPaperOpen($event);

            // Upload hanya bila abstrak ACC & window open & status NONE/REVISION/REJECTED
            $canUpload = $isOpen && $absAccepted && in_array($fpStatus, ['NONE','REVISION','REJECTED'], true);

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
                'is_open'       => $isOpen,
                'abs'           => $abs,
                'abs_badge'     => $absMeta['badge'],
                'abs_label'     => $absMeta['label'],
            ];
        }

        usort($history, function($a, $b) {
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

    /* ===== DETAIL ===== */
    public function detail($eventId)
    {
        $userId  = (int) session()->get('id_user');
        $eventId = (int) $eventId;

        $event = $this->eventModel->find($eventId);
        if (!$event) {
            return redirect()->to('/presenter/fullpaper')->with('error', 'Event tidak ditemukan.');
        }

        $reg = $this->regModel->findUserReg($eventId, $userId);
        if (!$reg) {
            return redirect()->to('/presenter/fullpaper')->with('error', 'Anda belum terdaftar pada event ini.');
        }

        $abs = $this->abstrakModel->where('id_user', $userId)
                                  ->where('event_id', $eventId)
                                  ->orderBy('id_abstrak','DESC')->first();

        [$status, $path, $createdAt, $reviewedAt, $decisionAt, $notes]
            = $this->latestFullpaperMeta($userId, $eventId, $abs);

        $statusMeta   = $this->mapFpStatusMeta($status);
        $absMeta      = $this->mapAbsMeta($abs);
        $isOpen       = $this->isFullPaperOpen($event);
        $absAccepted  = !empty($abs) && strtolower($abs['status'] ?? '') === 'diterima';
        $canReupload  = $isOpen && $absAccepted && in_array($status, ['REVISION','REJECTED'], true);
        $canUploadNew = $isOpen && $absAccepted && $status === 'NONE';

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
            'history'      => [], // isi jika kamu punya method riwayat
        ]);
    }

    /* ===== CREATE (FORM UPLOAD) — DIBATASI SEKALI SAJA ===== */
    public function create($eventId)
    {
        $userId  = (int) session()->get('id_user');
        $eventId = (int) $eventId;

        $event = $this->eventModel->find($eventId);
        if (!$event) {
            return redirect()->to('/presenter/fullpaper')->with('error', 'Event tidak ditemukan.');
        }

        $reg = $this->regModel->findUserReg($eventId, $userId);
        if (!$reg) {
            return redirect()->to('/presenter/events/detail/'.$eventId)->with('error', 'Anda belum terdaftar pada event ini.');
        }

        if (!$this->isFullPaperOpen($event)) {
            return redirect()->to('/presenter/fullpaper')->with('error', 'Pengumpulan Full Paper ditutup.');
        }

        // Cek status abstrak & FP terkini
        $abs = $this->abstrakModel->where('id_user', $userId)
                                  ->where('event_id', $eventId)
                                  ->orderBy('id_abstrak','DESC')->first();

        [$fpStatus] = $this->latestFullpaperMeta($userId, $eventId, $abs);
        $absAccepted = !empty($abs) && strtolower($abs['status'] ?? '') === 'diterima';

        // POLICY: Upload pertama hanya jika abstrak ACC
        if (!$absAccepted) {
            return redirect()->to('/presenter/abstrak/detail/'.(int)($abs['id_abstrak'] ?? 0))
                ->with('error', 'Abstrak belum diterima. Upload Full Paper hanya dibuka setelah abstrak ACC.');
        }

        // Sudah pernah upload? Hanya izinkan jika status revisi/ditolak
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

    /* ===== STORE (SIMPAN FILE) — DIBATASI SEKALI SAJA ===== */
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

        // HARD CHECK sebelum simpan (anti-bypass URL langsung)
        $abs = $this->abstrakModel->where('id_user', $userId)
                                  ->where('event_id', $eventId)
                                  ->orderBy('id_abstrak','DESC')->first();

        [$fpStatus] = $this->latestFullpaperMeta($userId, $eventId, $abs);
        $absAccepted = !empty($abs) && strtolower($abs['status'] ?? '') === 'diterima';

        if (!$absAccepted) {
            return redirect()->to('/presenter/abstrak/detail/'.(int)($abs['id_abstrak'] ?? 0))
                ->with('error', 'Abstrak belum diterima. Upload Full Paper hanya dibuka setelah abstrak ACC.');
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

    /* ===== UNDuh ===== */
    public function download($filename)
    {
        $path = WRITEPATH.'uploads/fullpaper/'.$filename;
        if (!is_file($path)) {
            return redirect()->back()->with('error','File tidak ditemukan.');
        }
        return $this->response->download($path, null);
    }
}