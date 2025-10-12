<?php

namespace App\Controllers\Role\Presenter;

use App\Controllers\BaseController;
use App\Models\EventModel;
use App\Models\EventRegistrationModel;
use App\Models\AbstrakModel;
use App\Models\FullPaperModel;
use App\Models\KategoriAbstrakModel;

class Fullpaper extends BaseController
{
    protected EventModel $eventModel;
    protected EventRegistrationModel $regModel;
    protected AbstrakModel $abstrakModel;
    protected FullPaperModel $fpModel;
    protected KategoriAbstrakModel $kategoriModel;

    public function __construct()
    {
        $this->eventModel    = new EventModel();
        $this->regModel      = new EventRegistrationModel();
        $this->abstrakModel  = new AbstrakModel();
        $this->fpModel       = new FullPaperModel();
        $this->kategoriModel = new KategoriAbstrakModel();
        helper(['date', 'text']);
    }

    /* ======================= Helpers: status & normalization ======================= */

    private function isFullPaperOpen(array $event): bool
    {
        if (empty($event['is_active'])) return false;
        if (empty($event['full_paper_submission_active'])) return false;

        if (!empty($event['full_paper_deadline'])) {
            return (time() <= strtotime($event['full_paper_deadline']));
        }
        return (time() <= strtotime(($event['event_date'] ?? '2099-12-31') . ' 23:59:00'));
    }

    private function isEventFinished(array $event): bool
    {
        $d = trim((string)($event['event_date'] ?? ''));
        if ($d === '') return false;
        return time() > strtotime($d . ' 23:59:59');
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
            'REJECTED' => ['badge' => 'danger',    'label' => 'Ditolak',          'hint' => 'Lewat batas waktu / ditolak'],
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

    /**
     * AUTO-REJECTION logic:
     * - Jika FP window sudah TUTUP (isFullPaperOpen == false) dan ada deadline,
     *   lalu status: NONE atau REVISION -> tampilkan sebagai REJECTED (Auto)
     *   (tanpa mengubah DB).
     * Return: [effectiveStatus, reason|null]
     */
    private function autoRejectionStatus(array $event, string $current): array
    {
        $isOpen   = $this->isFullPaperOpen($event);
        $deadline = $event['full_paper_deadline'] ?? null;

        if ($isOpen || empty($deadline)) {
            return [strtoupper($current), null];
        }

        $s = strtoupper($current);
        if (in_array($s, ['NONE', 'REVISION'], true)) {
            $reason = ($s === 'NONE')
                ? 'Tidak mengunggah Full Paper sampai batas waktu.'
                : 'Tidak mengunggah revisi sampai batas waktu.';
            return ['REJECTED', $reason];
        }

        return [$s, null];
    }

    /* ======================= Reviewer helpers (untuk presenter) ======================= */

    private function resolveReviewerSource(): array
    {
        $db = \Config\Database::connect();
        $table = $db->tableExists('reviewers') ? 'reviewers'
              : ($db->tableExists('users') ? 'users' : null);
        if (!$table) return ['table'=>null,'id'=>null,'name'=>null,'email'=>null];

        $fields = array_flip($db->getFieldNames($table) ?: []);
        $idCol   = null; foreach (['id','id_user','user_id','reviewer_id'] as $c) if (isset($fields[$c])) { $idCol = $c; break; }
        $nameCol = null; foreach (['nama_lengkap','nama','name','full_name','username'] as $c) if (isset($fields[$c])) { $nameCol = $c; break; }
        $emailCol= null; foreach (['email','user_email','mail'] as $c) if (isset($fields[$c])) { $emailCol = $c; break; }

        return ['table'=>$table,'id'=>$idCol,'name'=>$nameCol,'email'=>$emailCol];
    }

    /** Ambil reviewer yang ditugaskan + keputusan terakhir setelah upload FP terakhir */
    private function getAssignedReviewersWithLatestDecision(int $submissionId, ?string $uploadedAt): array
    {
        $db = \Config\Database::connect();
        if (!$db->tableExists('fullpaper_reviewers')) return [];

        $fields = array_flip($db->getFieldNames('fullpaper_reviewers') ?: []);
        $colRel = isset($fields['submission_id']) ? 'submission_id' : (isset($fields['id_submission']) ? 'id_submission' : null);
        $colRev = isset($fields['reviewer_id'])   ? 'reviewer_id'   : (isset($fields['id_reviewer'])   ? 'id_reviewer'   : null);
        $colSt  = isset($fields['assignment_status']) ? 'assignment_status'
                 : (isset($fields['status_tugas']) ? 'status_tugas' : (isset($fields['tugas_status']) ? 'tugas_status' : (isset($fields['konfirmasi_status']) ? 'konfirmasi_status' : null)));
        if (!$colRel || !$colRev) return [];

        // reviewer yang ditugaskan
        $assigned = $db->table('fullpaper_reviewers')
            ->select("$colRev AS reviewer_id, ".($colSt ? "$colSt AS assignment_status, " : "'' AS assignment_status, ")." assigned_at")
            ->where($colRel, $submissionId)
            ->get()->getResultArray();

        if (!$assigned) return [];

        $ids = array_values(array_unique(array_map(fn($r)=>(int)$r['reviewer_id'], $assigned)));

        // identitas reviewer
        $src = $this->resolveReviewerSource();
        $idCol   = $src['id']; $nameCol = $src['name']; $emailCol = $src['email'];
        $ident = [];
        if ($src['table'] && $idCol) {
            $q = $db->table($src['table'])
                ->select("$idCol AS id"
                    .($nameCol? ", $nameCol AS name" : ", NULL AS name")
                    .($emailCol? ", $emailCol AS email" : ", NULL AS email"))
                ->whereIn($idCol, $ids)->get()->getResultArray();
            foreach ($q as $r) $ident[(int)$r['id']] = ['name'=>$r['name'] ?? 'Reviewer', 'email'=>$r['email'] ?? null];
        }

        // keputusan terakhir per reviewer (round terbaru = >= uploadedAt)
        $latest = [];
        if ($db->tableExists('fullpaper_reviews')) {
            $qb = $db->table('fullpaper_reviews')
                ->select('reviewer_id, keputusan, komentar, tanggal_review')
                ->where('submission_id', $submissionId)
                ->whereIn('reviewer_id', $ids);
            if ($uploadedAt) $qb->where('tanggal_review >=', $uploadedAt);
            $rows = $qb->orderBy('reviewer_id','ASC')->orderBy('tanggal_review','DESC')->get()->getResultArray();
            foreach ($rows as $r) {
                $rid = (int)$r['reviewer_id'];
                if (!isset($latest[$rid])) $latest[$rid] = $r;
            }
        }

        // gabungkan
        $out = [];
        foreach ($assigned as $a) {
            $rid   = (int)$a['reviewer_id'];
            $stRaw = strtolower((string)($a['assignment_status'] ?? 'pending'));
            $st    = match (true) {
                in_array($stRaw, ['accepted','accept','ok','yes'], true)  => 'accepted',
                in_array($stRaw, ['declined','decline','no'], true)       => 'declined',
                default                                                   => 'pending',
            };
            $dec   = $latest[$rid]['keputusan'] ?? null;
            $kom   = $latest[$rid]['komentar']  ?? null;
            $ts    = $latest[$rid]['tanggal_review'] ?? null;

            $out[] = [
                'reviewer_id'       => $rid,
                'name'              => $ident[$rid]['name']  ?? 'Reviewer',
                'email'             => $ident[$rid]['email'] ?? null,
                'assignment_status' => $st,
                'keputusan'         => $dec,         // accepted | revision | rejected | null
                'komentar'          => $kom,
                'tanggal_review'    => $ts,
            ];
        }
        return $out;
    }

    /**
     * Agregasi panel **UNANIMOUS**:
     * - Semua ACC   => ACCEPTED
     * - Semua REJECT=> REJECTED
     * - Ada kombinasi / ada minimal 1 REVISION/REJECT => REVISION
     * - Kalau semua yang masuk ACC tapi belum lengkap => UPLOADED (belum final)
     */
    private function computePanelUnanimous(array $reviewers): array
    {
        $total = count($reviewers);
        if ($total === 0) {
            return ['panel' => 'PENDING', 'counts' => ['acc'=>0,'rev'=>0,'rej'=>0,'done'=>0,'total'=>0], 'decided' => false];
        }

        $acc = $rev = $rej = $done = 0;
        foreach ($reviewers as $r) {
            $k = strtolower((string)($r['keputusan'] ?? ''));
            if ($k === '') continue;
            $done++;
            if (in_array($k, ['accepted','accept','acc','approved','diterima'], true)) $acc++;
            elseif (in_array($k, ['revision','revisi'], true)) $rev++;
            elseif (in_array($k, ['rejected','reject','ditolak'], true)) $rej++;
        }

        if ($done === $total && $acc === $total) {
            return ['panel'=>'ACCEPTED', 'counts'=>compact('acc','rev','rej','done')+['total'=>$total], 'decided'=>true];
        }
        if ($done === $total && $rej === $total) {
            return ['panel'=>'REJECTED', 'counts'=>compact('acc','rev','rej','done')+['total'=>$total], 'decided'=>true];
        }
        if ($rev > 0 || $rej > 0) {
            return ['panel'=>'REVISION', 'counts'=>compact('acc','rev','rej','done')+['total'=>$total], 'decided'=>true];
        }
        return ['panel'=>'UPLOADED', 'counts'=>compact('acc','rev','rej','done')+['total'=>$total], 'decided'=>false];
    }

    private function resolveSubmissionId(?array $row): ?int
    {
        if (!$row) return null;
        $cands = ['submission_id','id_submission','fullpaper_id','id_fullpaper','id','id_abstrak'];
        foreach ($cands as $k) if (isset($row[$k]) && $row[$k]) return (int)$row[$k];
        if (property_exists($this->fpModel, 'primaryKey')) {
            $pk = $this->fpModel->primaryKey;
            if ($pk && isset($row[$pk]) && $row[$pk]) return (int)$row[$pk];
        }
        return null;
    }

    /** Cari submission “carrier” (submissions|abstrak) */
    private function findCurrentSubmissionId(int $userId, int $eventId): ?int
    {
        $db = \Config\Database::connect();

        if ($db->tableExists('submissions')) {
            $row = $db->table('submissions')
                ->select('id')
                ->where('user_id', $userId)
                ->where('event_id', $eventId)
                ->orderBy('id','DESC')->get()->getRowArray();
            if ($row) return (int)$row['id'];
        }
        if ($db->tableExists('abstrak')) {
            $row = $db->table('abstrak')
                ->select('id_abstrak')
                ->where('id_user', $userId)
                ->where('event_id', $eventId)
                ->orderBy('id_abstrak','DESC')->get()->getRowArray();
            if ($row) return (int)$row['id_abstrak'];
        }
        return null;
    }

    /* ======================= Pages ======================= */

    public function index()
    {
        $userId = (int) session()->get('id_user');
        $regs   = $this->regModel->listByUser($userId) ?? [];

        $todo    = [];
        $history = [];

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

            $isOpen     = $this->isFullPaperOpen($event);
            $isFinished = $this->isEventFinished($event);

            // Terapkan AUTO-REJECT untuk display status
            $baseStatus = $absEligible ? $fpStatus : 'NONE';
            [$effStatus, $autoReason] = $this->autoRejectionStatus($event, $baseStatus);

            $meta = $this->mapFpStatusMeta($effStatus);
            if ($autoReason) {
                $meta['label'] .= ' (Auto)';
                $meta['hint']   = $autoReason;
            }

            // Upload hanya boleh saat open dan status memungkinkan
            $canUpload = $isOpen && $absEligible && in_array($effStatus, ['NONE','REJECTED','REVISION'], true);

            $row = [
                'event_id'            => $eid,
                'title'               => $event['title'] ?? '-',
                'event_date'          => $event['event_date'] ?? null,
                'full_paper_deadline' => $event['full_paper_deadline'] ?? null,
                'format'              => strtolower($event['format'] ?? ''),
                'fp_status'           => $effStatus,
                'status_badge'        => $meta['badge'],
                'status_label'        => $meta['label'],
                'status_hint'         => $meta['hint'],
                'fp_path'             => $fpPath,
                'uploaded_at'         => $fpTs,
                'is_open'             => $isOpen,
                'is_finished'         => $isFinished,
                'can_upload'          => $canUpload,
            ];

            // Masuk History jika window FP sudah TUTUP dan status final (ACCEPTED/REJECTED)
            $isFinalForFp = (!$isOpen) && in_array($effStatus, ['ACCEPTED','REJECTED'], true);

            if ($isFinalForFp || $isFinished) {
                // Final: pindah ke history
                $history[] = $row;
            } else {
                $todo[] = $row;
            }
        }

        usort($todo, function($a,$b){
            $ta = $a['full_paper_deadline'] ? strtotime($a['full_paper_deadline']) : PHP_INT_MAX;
            $tb = $b['full_paper_deadline'] ? strtotime($b['full_paper_deadline']) : PHP_INT_MAX;
            return $ta <=> $tb;
        });
        usort($history, function($a,$b){
            $ta = $a['uploaded_at'] ? strtotime($a['uploaded_at']) : strtotime($a['event_date'] ?? '1970-01-01');
            $tb = $b['uploaded_at'] ? strtotime($b['uploaded_at']) : strtotime($b['event_date'] ?? '1970-01-01');
            return $tb <=> $ta;
        });

        return view('role/presenter/fullpaper/index', [
            'title'   => 'Full Paper',
            'todo'    => $todo,
            'history' => $history,
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

        $absTbl = $this->abstrakModel->getTable() ?? 'abstrak';
        $katTbl = $this->kategoriModel->getTable() ?? 'kategori_abstrak';

        $abs = $this->abstrakModel->select("$absTbl.*, $katTbl.nama_kategori")
                                  ->join($katTbl, "$katTbl.id_kategori = $absTbl.id_kategori", 'left')
                                  ->where("$absTbl.id_user", $userId)
                                  ->where("$absTbl.event_id", $eventId)
                                  ->orderBy("$absTbl.id_abstrak", 'DESC')
                                  ->first();

        if ($abs && empty($abs['nama_kategori']) && !empty($abs['id_kategori'])) {
            $kat = $this->kategoriModel->find((int)$abs['id_kategori']);
            if ($kat) $abs['nama_kategori'] = $kat['nama_kategori'] ?? ($kat['nama'] ?? null);
        }

        [$status, $path, $createdAt, $reviewedAt, $decisionAt, $notes, $fpRow]
            = $this->latestFullpaperMeta($userId, $eventId, $abs);

        $absEligible = $this->isAbstractEligible($abs);
        if (!$absEligible) { $status = 'NONE'; $path = ''; }

        // Terapkan AUTO-REJECT di halaman detail (display)
        [$statusEff, $autoReason] = $this->autoRejectionStatus($event, $status);

        $statusMeta   = $this->mapFpStatusMeta($statusEff);
        if ($autoReason) {
            $statusMeta['label'] .= ' (Auto)';
            $notes = trim($notes) !== '' ? $notes : $autoReason;
        }

        $isOpen       = $this->isFullPaperOpen($event);

        // reviewer + panel
        $submissionId = $this->findCurrentSubmissionId($userId, $eventId);
        $reviewers    = [];
        $panel        = ['panel'=>'PENDING','counts'=>['acc'=>0,'rev'=>0,'rej'=>0,'done'=>0,'total'=>0],'decided'=>false];

        if ($submissionId) {
            $reviewers = $this->getAssignedReviewersWithLatestDecision($submissionId, $createdAt);
            $panel     = $this->computePanelUnanimous($reviewers);
        }

        // Reupload hanya jika OPEN dan status final REVISION/REJECTED
        $canReupload  = $isOpen && $absEligible && in_array($statusEff, ['REVISION','REJECTED'], true);
        $canUploadNew = $isOpen && $absEligible && $statusEff === 'NONE';

        return view('role/presenter/fullpaper/detail', [
            'title'         => 'Detail Full Paper',
            'event'         => $event,
            'abs'           => $abs,
            'status'        => $statusEff,
            'status_meta'   => $statusMeta,
            'path'          => $path,
            'created_at'    => $createdAt,
            'reviewed_at'   => $reviewedAt,
            'decision_at'   => $decisionAt,
            'notes'         => $notes,
            'is_open'       => $isOpen,
            'abs_meta'      => $this->mapAbsMeta($abs),
            'can_reupload'  => $canReupload,
            'can_upload'    => $canUploadNew,
            'event_id'      => $eventId,

            // panel & reviewer list
            'submission_id' => $submissionId,
            'reviewers'     => $reviewers,
            'panel'         => $panel,
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

        // Gunakan status efektif (kalau sebelumnya auto-reject tapi sudah tutup, tetap tidak bisa upload)
        [$effStatus] = $this->autoRejectionStatus($event, $fpStatus);
        if ($effStatus !== 'NONE' && !in_array($effStatus, ['REVISION','REJECTED'], true)) {
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

        [$fpStatus,,]  = $this->latestFullpaperMeta($userId, $eventId, $abs);
        $absEligible   = $this->isAbstractEligible($abs);

        if (!$absEligible) {
            return redirect()->to('/presenter/abstrak/create/'.$eventId)
                ->with('error', 'Upload Full Paper tersedia setelah Anda mengunggah abstrak (dan tidak ditolak).');
        }

        [$effStatus] = $this->autoRejectionStatus($event, $fpStatus);
        if ($effStatus !== 'NONE' && !in_array($effStatus, ['REVISION','REJECTED'], true)) {
            return redirect()->to('/presenter/fullpaper/detail/'.$eventId)
                ->with('error', 'Anda sudah mengunggah Full Paper. Tidak bisa upload lagi pada status saat ini.');
        }

        if (!$file->isValid()) return redirect()->back()->with('error','File tidak valid.');
        $ext = strtolower($file->getClientExtension() ?: '');
        if ($ext !== 'pdf') return redirect()->back()->withInput()->with('error','File harus PDF.');
        if ($file->getSize() > 20 * 1024 * 1024) {
            return redirect()->back()->withInput()->with('error','Ukuran maksimal 20MB.');
        }

        // Simpan submission id sebelumnya (jika ada)
        $before = method_exists($this->fpModel,'getLatestRowByUserEvent')
            ? $this->fpModel->getLatestRowByUserEvent($userId, $eventId) : null;
        $beforeId = $this->resolveSubmissionId($before);

        try {
            // simpan file ke WRITEPATH/uploads/fullpaper/.... (return nama file)
            $stored = $this->fpModel->moveUploadedFile($file, $userId, $eventId);

            // tempel path ke carrier (submissions/abstrak) dan status UPLOADED
            $ok = $this->fpModel->attachFullPaperByUserEvent(
                $userId, $eventId, $stored, \App\Models\FullPaperModel::STATUS_UPLOADED
            );
            if (!$ok) return redirect()->back()->withInput()->with('error','Tidak ditemukan data abstrak/submission untuk ditempeli.');

            // pastikan status UPLOADED di carrier
            $this->forceUploadedStatus($userId, $eventId);

            // carrier terbaru
            $after  = method_exists($this->fpModel,'getLatestRowByUserEvent')
                ? $this->fpModel->getLatestRowByUserEvent($userId, $eventId) : null;
            $newId  = $this->resolveSubmissionId($after);

            // bump revisi_ke kalau ada
            $db = \Config\Database::connect();
            $table = $db->tableExists('submissions') ? 'submissions' : ($db->tableExists('abstrak') ? 'abstrak' : null);
            if ($table && in_array('revisi_ke', $db->getFieldNames($table), true) && $newId) {
                $pk = $table==='submissions' ? 'id' : 'id_abstrak';
                $db->table($table)->where($pk, $newId)->set('revisi_ke','COALESCE(revisi_ke,0)+1', false)->update();
            }

            // copy assignment reviewer dari submission sebelumnya (kalau berbeda)
            if ($newId && (!$beforeId || $newId !== $beforeId)) {
                $this->propagateFullpaperAssignments($newId, $userId, $eventId, $beforeId);
            }

            // === Notif revisi ke reviewer yang sebelumnya REVISION/REJECT ===
            try {
                if ($beforeId && $newId && $db->tableExists('fullpaper_reviews') && $db->tableExists('fullpaper_reviewers')) {
                    $rids = array_column(
                        $db->table('fullpaper_reviewers')->select('reviewer_id')->where('submission_id', $newId)->get()->getResultArray(),
                        'reviewer_id'
                    );
                    if ($rids) {
                        $rows = $db->table('fullpaper_reviews')
                            ->select('reviewer_id, LOWER(keputusan) AS keputusan, MAX(tanggal_review) AS ts')
                            ->where('submission_id', $beforeId)
                            ->whereIn('reviewer_id', $rids)
                            ->groupBy('reviewer_id, LOWER(keputusan)')
                            ->get()->getResultArray();

                        $need = [];
                        foreach ($rows as $rv) {
                            $k = $rv['keputusan'] ?? '';
                            if (in_array($k, ['revision','revisi','rejected','reject','ditolak'], true)) {
                                $need[] = (int)$rv['reviewer_id'];
                            }
                        }

                        if ($need && $db->tableExists('notifications')) {
                            foreach ($need as $uid) {
                                $db->table('notifications')->insert([
                                    'user_id'    => $uid,
                                    'title'      => 'Revisi Full Paper Masuk',
                                    'message'    => 'Ada revisi full paper yang perlu Anda review.',
                                    'link'       => site_url('reviewer/fullpaper/'.$newId),
                                    'created_at' => date('Y-m-d H:i:s'),
                                ]);
                            }
                        }
                    }
                }
            } catch (\Throwable $e) {
                log_message('error', 'notif revisi FP gagal: '.$e->getMessage());
            }

        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', 'Gagal mengunggah: '.$e->getMessage());
        }

        return redirect()->to('/presenter/fullpaper/detail/'.$eventId)
            ->with('success', 'Full Paper berhasil diunggah. Menunggu review.');
    }

    public function download($filename) {
        $path = WRITEPATH.'uploads/fullpaper/'.$filename;
        if (!is_file($path)) return redirect()->back()->with('error','File tidak ditemukan.');
        $inline = (string)$this->request->getGet('inline') === '1';
        if ($inline) {
            return $this->response
                ->setHeader('Content-Type','application/pdf')
                ->setHeader('Content-Disposition','inline; filename="'.basename($path).'"')
                ->setBody(file_get_contents($path));
        }
        return $this->response->download($path, null); // attachment
    }

    public function delete($eventId)
    {
        return redirect()->to('/presenter/fullpaper/detail/'.(int)$eventId)
            ->with('error', 'Hapus full paper belum didukung.');
    }

    /* =========================== Helpers: persist uploaded status & copy reviewer =========================== */

    private function forceUploadedStatus(int $userId, int $eventId): void
    {
        $db = \Config\Database::connect();

        if ($db->tableExists('submissions')) {
            $new = $db->table('submissions')
                ->select('id')
                ->where('user_id', $userId)
                ->where('event_id', $eventId)
                ->orderBy('id','DESC')->get()->getRowArray();
            if ($new && in_array('full_paper_status', $db->getFieldNames('submissions'), true)) {
                $db->table('submissions')->where('id', (int)$new['id'])
                    ->update(['full_paper_status' => 'UPLOADED', 'eligible_to_pay' => false]);
            }
        } elseif ($db->tableExists('abstrak')) {
            $abs = $db->table('abstrak')
                ->select('id_abstrak')
                ->where('id_user', $userId)
                ->where('event_id', $eventId)
                ->orderBy('id_abstrak','DESC')->get()->getRowArray();
            if ($abs && in_array('full_paper_status', $db->getFieldNames('abstrak'), true)) {
                $db->table('abstrak')->where('id_abstrak', (int)$abs['id_abstrak'])
                    ->update(['full_paper_status' => 'UPLOADED']);
            }
        }
    }

    private function propagateFullpaperAssignments(int $newSubmissionId, int $userId, int $eventId, ?int $prevSubmissionId = null): int
    {
        $db = \Config\Database::connect();
        if (!$db->tableExists('fullpaper_reviewers')) return 0;

        $pFields = array_flip($db->getFieldNames('fullpaper_reviewers'));
        $colRel  = isset($pFields['submission_id']) ? 'submission_id'
                 : (isset($pFields['id_submission']) ? 'id_submission' : null);
        $colRev  = isset($pFields['reviewer_id']) ? 'reviewer_id'
                 : (isset($pFields['id_reviewer']) ? 'id_reviewer' : null);
        if (!$colRel || !$colRev) return 0;

        if ($prevSubmissionId === null && $db->tableExists('submissions')) {
            $prev = $db->table('submissions')
                ->select('id')
                ->where('user_id', $userId)
                ->where('event_id', $eventId)
                ->where('id <>', $newSubmissionId)
                ->orderBy('id','DESC')->get()->getRowArray();
            if ($prev) $prevSubmissionId = (int)$prev['id'];
        }
        if (!$prevSubmissionId) return 0;

        $prevRevs = $db->table('fullpaper_reviewers')
            ->select($colRev.' AS rid')
            ->where($colRel, $prevSubmissionId)
            ->get()->getResultArray();

        $copied = 0;
        foreach ($prevRevs as $r) {
            $rid = (int)($r['rid'] ?? 0);
            if ($rid <= 0) continue;

            $exists = $db->table('fullpaper_reviewers')
                ->where($colRel, $newSubmissionId)
                ->where($colRev, $rid)
                ->countAllResults();
            if ($exists) continue;

            $ins = [$colRel => $newSubmissionId, $colRev => $rid, 'assigned_at' => date('Y-m-d H:i:s')];
            if ($db->table('fullpaper_reviewers')->insert($ins)) $copied++;
        }

        return $copied;
    }
}
