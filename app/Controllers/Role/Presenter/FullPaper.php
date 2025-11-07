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
        helper(['date']);
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

    /** event sudah dimulai (tanggal H) */
    private function isEventStarted(array $event): bool
    {
        $d = trim((string)($event['event_date'] ?? ''));
        if ($d === '') return false;
        return time() >= strtotime($d . ' 00:00:00');
    }

    private function normalizeFpStatus(?string $raw): string
    {
        $s = strtolower(trim((string) $raw));
        return match (true) {
            $s === ''                                                                => 'NONE',
            in_array($s, ['uploaded','menunggu','pending','sedang_direview'], true) => 'UPLOADED',
            in_array($s, ['revisi','revision'], true)                                => 'REVISION',
            in_array($s, ['diterima','accepted','acc','approved'], true)            => 'ACCEPTED',
            in_array($s, ['ditolak','rejected'], true)                               => 'REJECTED',
            default                                                                  => strtoupper($s),
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

    private function isAbstractRejected(?array $abs): bool
    {
        $s = strtolower((string)($abs['status'] ?? ''));
        return $s === 'ditolak';
    }
    private function isAbstractEligible(?array $abs): bool
    {
        return !empty($abs) && !$this->isAbstractRejected($abs);
    }

    /**
     * AUTO-REJECTION tampil:
     * - Jika window FP TUTUP & ada deadline:
     *   status NONE/REVISION => tampil REJECTED (Auto)
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

    /* ======================= Reviewer helpers ======================= */

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

    private function hasAnyReviewerAssigned(?int $submissionId): bool
    {
        if (!$submissionId) return false;
        $db = \Config\Database::connect();
        if (!$db->tableExists('fullpaper_reviewers')) return false;

        $fields = array_flip($db->getFieldNames('fullpaper_reviewers') ?: []);
        $colRel = isset($fields['submission_id']) ? 'submission_id' : (isset($fields['id_submission']) ? 'id_submission' : null);
        if (!$colRel) return false;

        $cnt = (int) $db->table('fullpaper_reviewers')->where($colRel, $submissionId)->countAllResults();
        return $cnt > 0;
    }

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

        $assigned = $db->table('fullpaper_reviewers')
            ->select("$colRev AS reviewer_id, ".($colSt ? "$colSt AS assignment_status, " : "'' AS assignment_status, ")." assigned_at")
            ->where($colRel, $submissionId)
            ->get()->getResultArray();

        if (!$assigned) return [];

        $ids = array_values(array_unique(array_map(fn($r)=>(int)$r['reviewer_id'], $assigned)));

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

        $latest = [];
        if ($db->tableExists('fullpaper_reviews')) {
            $qb = $db->table('fullpaper_reviews')
                ->select('reviewer_id, keputusan, komentar, tanggal_review')
                ->where('submission_id', $submissionId)
                ->whereIn('reviewer_id', $ids);
            $rows = $qb->orderBy('reviewer_id','ASC')->orderBy('tanggal_review','DESC')->get()->getResultArray();
            foreach ($rows as $r) {
                $rid = (int)$r['reviewer_id'];
                if (!isset($latest[$rid])) $latest[$rid] = $r;
            }
        }

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
                'keputusan'         => $dec,
                'komentar'          => $kom,
                'tanggal_review'    => $ts,
            ];
        }
        return $out;
    }

    /* ===== Voting mayoritas + opsional revisi ===== */

    private function computePanelMajority(array $reviewers): array
    {
        $total = count($reviewers);
        if ($total === 0) {
            return [
                'panel'   => 'PENDING',
                'counts'  => ['acc'=>0,'rev'=>0,'rej'=>0,'done'=>0,'total'=>0],
                'decided' => false,
                'optional_revision' => false,
            ];
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

        if ($done < $total) {
            return [
                'panel'   => 'UPLOADED',
                'counts'  => compact('acc','rev','rej','done') + ['total'=>$total],
                'decided' => false,
                'optional_revision' => false,
            ];
        }

        if ($rej >= 2) {
            return [
                'panel'   => 'REJECTED',
                'counts'  => compact('acc','rev','rej','done') + ['total'=>$total],
                'decided' => true,
                'optional_revision' => false,
            ];
        }

        if ($acc >= 2) {
            $opt = ($rev >= 1); // ACC, ACC, Revisi => optional
            return [
                'panel'   => 'ACCEPTED',
                'counts'  => compact('acc','rev','rej','done') + ['total'=>$total],
                'decided' => true,
                'optional_revision' => $opt,
            ];
        }

        if ($rev >= 2) {
            return [
                'panel'   => 'REVISION',
                'counts'  => compact('acc','rev','rej','done') + ['total'=>$total],
                'decided' => true,
                'optional_revision' => false,
            ];
        }

        // edge case: acc, revisi, reject
        return [
            'panel'   => 'REVISION',
            'counts'  => compact('acc','rev','rej','done') + ['total'=>$total],
            'decided' => true,
            'optional_revision' => false,
        ];
    }

    private function hasOptionalRevisionAllowed(?int $submissionId, ?string $uploadedAt): bool
    {
        if (!$submissionId) return false;
        $reviewers = $this->getAssignedReviewersWithLatestDecision($submissionId, null);
        $panel     = $this->computePanelMajority($reviewers);
        return ($panel['panel'] === 'ACCEPTED' && !empty($panel['optional_revision']));
    }

    private function computeFlowStatus(array $reviewers): array
    {
        $total     = count($reviewers);
        $accepted  = 0;
        $submitted = 0;

        foreach ($reviewers as $r) {
            $st = strtolower((string)($r['assignment_status'] ?? ''));
            if (in_array($st, ['accepted','accept','ok','yes'], true)) $accepted++;
            if (!empty($r['keputusan'])) $submitted++;
        }

        if ($total === 0 || ($accepted === 0 && $submitted === 0)) {
            return [
                'code'  => 'WAITING_REVIEWER',
                'badge' => 'info',
                'title' => 'Menunggu Reviewer',
                'hint'  => 'Belum ada reviewer yang ditugaskan atau belum ada yang menerima tugas.',
            ];
        }

        if ($submitted === 1 && $total > 1) {
            return [
                'code'  => 'WAITING_RESULTS',
                'badge' => 'primary',
                'title' => 'Menunggu Hasil',
                'hint'  => 'Menunggu reviewer lain menyelesaikan penilaian.',
            ];
        }

        if ($submitted === 2 && $total >= 3) {
            return [
                'code'  => 'WAITING_FINAL',
                'badge' => 'primary',
                'title' => 'Menunggu Hasil Akhir',
                'hint'  => 'Menunggu 1 reviewer lagi untuk hasil akhir.',
            ];
        }

        return [
            'code'  => 'READY_PANEL',
            'badge' => 'secondary',
            'title' => 'Menunggu Keputusan Panel',
            'hint'  => 'Hampir semua review masuk. Keputusan akhir segera ditetapkan.',
        ];
    }

    /* ====================== DB helpers: force status ====================== */

    private function forceRejectedStatus(int $userId, int $eventId, string $reason = 'Abstrak ditolak — Full Paper otomatis ditolak.'): void
    {
        $db = \Config\Database::connect();

        // submissions
        if ($db->tableExists('submissions')) {
            $row = $db->table('submissions')
                ->select('id')
                ->where('user_id', $userId)
                ->where('event_id', $eventId)
                ->orderBy('id','DESC')->get()->getRowArray();

            if ($row) {
                $fields = array_flip($db->getFieldNames('submissions'));
                $set = [];
                if (isset($fields['full_paper_status'])) $set['full_paper_status'] = 'REJECTED';
                if (isset($fields['review_notes']))      $set['review_notes']      = $reason;
                if (isset($fields['eligible_to_pay']))   $set['eligible_to_pay']   = false;
                if ($set) $db->table('submissions')->where('id', (int)$row['id'])->update($set);
                return;
            }
        }

        // fallback abstrak
        if ($db->tableExists('abstrak')) {
            $row = $db->table('abstrak')
                ->select('id_abstrak')
                ->where('id_user', $userId)
                ->where('event_id', $eventId)
                ->orderBy('id_abstrak','DESC')->get()->getRowArray();

            if ($row) {
                $fields = array_flip($db->getFieldNames('abstrak'));
                $set = [];
                if (isset($fields['full_paper_status'])) $set['full_paper_status'] = 'REJECTED';
                if ($set) $db->table('abstrak')->where('id_abstrak', (int)$row['id_abstrak'])->update($set);
            }
        }
    }

    /** jejak tipe upload */
    private function setLastUploadType(int $submissionId, string $type): void
    {
        $db = \Config\Database::connect();
        $table = $db->tableExists('submissions') ? 'submissions' : ($db->tableExists('abstrak') ? 'abstrak' : null);
        if (!$table) return;

        $fields = array_flip($db->getFieldNames($table) ?: []);
        $pk     = $table==='submissions' ? 'id' : 'id_abstrak';

        if (isset($fields['last_upload_type'])) {
            $db->table($table)->where($pk, $submissionId)->update(['last_upload_type' => strtoupper($type)]);
            return;
        }

        if (isset($fields['revisi_opsional'])) {
            $db->table($table)->where($pk, $submissionId)->update(['revisi_opsional' => (strtoupper($type)==='OPTIONAL') ? 1 : 0]);
        }
    }

    private function getRevisionInfo(?int $submissionId): array
    {
        $db = \Config\Database::connect();
        $table = $db->tableExists('submissions') ? 'submissions' : ($db->tableExists('abstrak') ? 'abstrak' : null);
        if (!$table || !$submissionId) return ['revisi_ke'=>0,'last_upload_type'=>null,'revisi_opsional'=>null];

        $fields = array_flip($db->getFieldNames($table) ?: []);
        $pk     = $table==='submissions' ? 'id' : 'id_abstrak';

        $select = "$pk AS id";
        if (isset($fields['revisi_ke']))        $select .= ", revisi_ke";
        if (isset($fields['last_upload_type'])) $select .= ", last_upload_type";
        if (isset($fields['revisi_opsional']))  $select .= ", revisi_opsional";

        $row = $db->table($table)->select($select)->where($pk, $submissionId)->get()->getRowArray() ?: [];

        return [
            'revisi_ke'        => (int)($row['revisi_ke'] ?? 0),
            'last_upload_type' => isset($row['last_upload_type']) ? strtoupper((string)$row['last_upload_type']) : null,
            'revisi_opsional'  => array_key_exists('revisi_opsional', $row) ? (bool)$row['revisi_opsional'] : null,
        ];
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

            // penanda abstrak SUDAH diupload
            $hasAbstract = !empty($abs);

            $absRejected = $this->isAbstractRejected($abs);
            $absEligible = !$absRejected;

            [$fpStatus, $fpPath, $fpTs] = $this->latestFullpaperMeta($userId, $eid, $abs);
            $hasFullpaper = ($fpPath !== '' && strtoupper($fpStatus) !== 'NONE');

            $isOpen       = $this->isFullPaperOpen($event);
            $eventStarted = $this->isEventStarted($event);

            $baseStatus = $absRejected ? 'REJECTED' : ($absEligible ? $fpStatus : 'NONE');
            [$effStatus, $autoReason] = $this->autoRejectionStatus($event, $baseStatus);

            // auto reject jika event sudah mulai & belum ada review sama sekali
            $autoReasonEvent = null;
            $submissionId  = $this->findCurrentSubmissionId($userId, $eid);
            $reviewersMini = $submissionId ? $this->getAssignedReviewersWithLatestDecision($submissionId, null) : [];
            $doneReviews   = 0; foreach ($reviewersMini as $rv) if (!empty($rv['keputusan'])) $doneReviews++;
            if ($eventStarted && $doneReviews === 0) {
                $effStatus = 'REJECTED';
                $autoReasonEvent = 'Event sudah berjalan dan belum ada hasil review — ditolak otomatis.';
            }

            $meta = $this->mapFpStatusMeta($effStatus);
            if ($absRejected) {
                $meta['hint'] = 'Abstrak ditolak — Full Paper otomatis ditolak.';
            } elseif ($autoReason) {
                $meta['label'] .= ' (Auto)';
                $meta['hint']   = $autoReason;
            }
            if ($autoReasonEvent) {
                $meta['label'] .= ' (Auto)';
                $meta['hint']   = $autoReasonEvent;
            }

            // Optional revision allowed?
            $allowOptional = (!$absRejected) && $this->hasOptionalRevisionAllowed($submissionId, null);

            // tombol upload:
            // - window open + status NONE/REJECTED/REVISION
            // - ATAU revisi opsional (meski window tutup)
            $canUpload = $absEligible && (
                ($isOpen && in_array($effStatus, ['NONE','REJECTED','REVISION'], true)) ||
                $allowOptional
            );
            if ($autoReasonEvent) $canUpload = false;

            $row = [
                'event_id'            => $eid,
                'event_title'         => $event['title'] ?? '-',
                'abs_title'           => $abs['judul'] ?? null,
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
                'can_upload'          => $canUpload,
                // flags untuk view (logic dipindah ke controller)
                'has_abstract'        => (bool)$hasAbstract,
                'has_fullpaper'       => (bool)$hasFullpaper,
                'auto_rejected'       => (strtoupper($effStatus)==='REJECTED' && !$isOpen && !empty($event['full_paper_deadline'])),
                'primary_btn_label'   => $hasFullpaper ? 'Upload Revisi' : 'Upload Full Paper',
            ];

            // penempatan di tab
            $goHistory =
                ($effStatus === 'ACCEPTED') ||
                (!$isOpen && $effStatus === 'REJECTED');

            if ($goHistory) {
                $history[] = $row;
            } else {
                $todo[] = $row;
            }
        }

        // urutkan
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

        /* contributors (opsional) */
        $contributors = [];
        if ($reg) {
            if (method_exists($this->regModel, 'getContributors')) {
                $contributors = $this->regModel->getContributors($eventId, $userId) ?? [];
            } else {
                $raw = $reg['contributors'] ?? $reg['kontributor'] ?? $reg['coauthors_json'] ?? null;
                if ($raw) {
                    $tmp = is_array($raw) ? $raw : json_decode((string)$raw, true);
                    if (is_array($tmp)) $contributors = $tmp;
                }
            }
            $main = [
                'role'     => 'Presenter Utama',
                'nama'     => $reg['nama']      ?? ($reg['full_name'] ?? (session()->get('nama') ?? '')),
                'email'    => $reg['email']     ?? (session()->get('email') ?? ''),
                'afiliasi' => $reg['afiliasi']  ?? ($reg['institution'] ?? ''),
                'negara'   => $reg['negara']    ?? ($reg['country'] ?? ''),
            ];
            array_unshift($contributors, $main);
        }

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

        $absRejected = $this->isAbstractRejected($abs);
        $absEligible = !$absRejected;

        if ($absRejected) {
            $status = 'REJECTED';
            $path   = '';
            $notes  = 'Abstrak ditolak — Full Paper otomatis ditolak.';
        }

        [$statusEff, $autoReason] = $this->autoRejectionStatus($event, $status);

        $statusMeta   = $this->mapFpStatusMeta($statusEff);
        if ($absRejected) {
            $statusMeta['hint'] = 'Abstrak ditolak — Full Paper otomatis ditolak.';
        } elseif ($autoReason) {
            $statusMeta['label'] .= ' (Auto)';
            $notes = trim($notes) !== '' ? $notes : $autoReason;
        }

        $isOpen       = $this->isFullPaperOpen($event);

        $submissionId = $this->findCurrentSubmissionId($userId, $eventId);
        $reviewers    = [];
        $panel        = ['panel'=>'PENDING','counts'=>['acc'=>0,'rev'=>0,'rej'=>0,'done'=>0,'total'=>0],'decided'=>false,'optional_revision'=>false];

        if ($submissionId && !$absRejected) {
            $reviewers = $this->getAssignedReviewersWithLatestDecision($submissionId, null);
            $panel     = $this->computePanelMajority($reviewers);
        }

        // override panel jika bukan auto-reject window & bukan abstrak ditolak
        if (!$autoReason && !$absRejected) {
            if (in_array($panel['panel'], ['ACCEPTED','REVISION','REJECTED'], true)) {
                $statusEff  = $panel['panel'];
                $statusMeta = $this->mapFpStatusMeta($statusEff);
                if ($panel['panel'] === 'ACCEPTED' && $panel['optional_revision']) {
                    $statusMeta['hint'] = 'Diterima; revisi opsional karena 1 reviewer meminta revisi.';
                }
            }
        }

        // event sudah mulai & belum ada review => REJECTED (Auto)
        $eventStarted = $this->isEventStarted($event);
        if ($eventStarted) {
            $doneReviews = (int)($panel['counts']['done'] ?? 0);
            if ($doneReviews === 0) {
                $statusEff  = 'REJECTED';
                $statusMeta = $this->mapFpStatusMeta($statusEff);
                $statusMeta['label'] .= ' (Auto)';
                $statusMeta['hint']   = 'Event sudah berjalan dan belum ada hasil review — ditolak otomatis.';
            }
        }

        $optionalRevision     = (!$absRejected && $panel['panel'] === 'ACCEPTED' && $panel['optional_revision']);
        // Revisi opsional boleh MESKI window tutup
        $canOptionalRevision  = $optionalRevision && $absEligible;

        $canReupload  = $isOpen && $absEligible && in_array($statusEff, ['REVISION','REJECTED'], true);
        $canUploadNew = $isOpen && $absEligible && $statusEff === 'NONE';

        // tombol Batalkan: boleh jika BELUM ada reviewer, ada file, window open, event belum mulai
        $hasAssigned = $this->hasAnyReviewerAssigned($submissionId);
        $canCancel   = (!$hasAssigned) && $isOpen && !$eventStarted && ($path !== '');

        // kunci semua aksi jika event start & tak ada review
        if ($eventStarted && (($panel['counts']['done'] ?? 0) === 0)) {
            $canOptionalRevision = false;
            $canReupload = false;
            $canUploadNew = false;
            $canCancel = false;
        }

        $info = [
            'event'          => (string)($event['title'] ?? '-'),
            'judul'          => (string)($abs['judul'] ?? '-'),
            'kategori'       => (string)($abs['nama_kategori'] ?? '-'),
            'status_abstrak' => (string)($this->mapAbsMeta($abs)['label'] ?? '-'),
            'fp_diunggah'    => $createdAt,
        ];

        $flow = $this->computeFlowStatus($reviewers);
        $revisionInfo    = $this->getRevisionInfo($submissionId);
        $uploadKindFlash = session()->getFlashdata('fp_upload_kind'); // OPTIONAL | REVISION | NEW

        return view('role/presenter/fullpaper/detail', [
            'title'                 => 'Detail Full Paper',
            'event'                 => $event,
            'abs'                   => $abs,
            'status'                => $statusEff,
            'status_meta'           => $statusMeta,
            'path'                  => $path,
            'created_at'            => $createdAt,
            'reviewed_at'           => $reviewedAt,
            'decision_at'           => $decisionAt,
            'notes'                 => $notes,
            'is_open'               => $isOpen,
            'abs_meta'              => $this->mapAbsMeta($abs),
            'can_reupload'          => $canReupload,
            'can_upload'            => $canUploadNew,
            'event_id'              => $eventId,

            'submission_id'         => $submissionId,
            'reviewers'             => $reviewers,
            'panel'                 => $panel,
            'optional_revision'     => $optionalRevision,
            'can_optional_revision' => $canOptionalRevision,

            'contributors'          => $contributors,

            'can_cancel'            => $canCancel,
            'info'                  => $info,
            'flow'                  => $flow,
            'revision_info'         => $revisionInfo,
            'upload_kind'           => $uploadKindFlash
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

        $absTbl = $this->abstrakModel->getTable() ?? 'abstrak';
        $katTbl = $this->kategoriModel->getTable() ?? 'kategori_abstrak';
        $abs = $this->abstrakModel->select("$absTbl.*, $katTbl.nama_kategori")
                                  ->join($katTbl, "$katTbl.id_kategori = $absTbl.id_kategori", 'left')
                                  ->where("$absTbl.id_user", $userId)
                                  ->where("$absTbl.event_id", $eventId)
                                  ->orderBy("$absTbl.id_abstrak", 'DESC')
                                  ->first();

        if ($this->isAbstractRejected($abs)) {
            $this->forceRejectedStatus($userId, $eventId);
            return redirect()->to('/presenter/fullpaper/detail/'.$eventId)
                ->with('error', 'Abstrak Anda ditolak — pengumpulan Full Paper otomatis ditolak.');
        }

        [$fpStatus] = $this->latestFullpaperMeta($userId, $eventId, $abs);
        $absEligible = $this->isAbstractEligible($abs);
        if (!$absEligible) {
            return redirect()->to('/presenter/abstrak/create/'.$eventId)
                ->with('error', 'Upload Full Paper tersedia setelah Anda mengunggah abstrak (dan tidak ditolak).');
        }

        [$effStatus] = $this->autoRejectionStatus($event, $fpStatus);
        $submissionId   = $this->findCurrentSubmissionId($userId, $eventId);
        $allowOptional  = $this->hasOptionalRevisionAllowed($submissionId, null);

        // Window tutup? tetap izinkan kalau opsional
        if (!$this->isFullPaperOpen($event) && !$allowOptional) {
            return redirect()->to('/presenter/fullpaper')->with('error', 'Pengumpulan Full Paper ditutup.');
        }

        // Event sudah mulai? blokir kecuali opsional (ubah sesuai kebijakan)
        if ($this->isEventStarted($event) && !$allowOptional) {
            return redirect()->to('/presenter/fullpaper')->with('error', 'Event sudah berjalan — pengumpulan Full Paper ditutup.');
        }

        if ($effStatus !== 'NONE' && !in_array($effStatus, ['REVISION','REJECTED'], true) && !$allowOptional) {
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

        $abs = $this->abstrakModel->where('id_user',$userId)
                                  ->where('event_id',$eventId)
                                  ->orderBy('id_abstrak','DESC')->first();

        if ($this->isAbstractRejected($abs)) {
            $this->forceRejectedStatus($userId, $eventId);
            return redirect()->to('/presenter/fullpaper/detail/'.$eventId)
                ->with('error', 'Abstrak Anda ditolak — Full Paper otomatis ditolak dan unggahan tidak dicatat.');
        }

        [$fpStatus,,]  = $this->latestFullpaperMeta($userId, $eventId, $abs);
        $absEligible   = $this->isAbstractEligible($abs);
        if (!$absEligible) {
            return redirect()->to('/presenter/abstrak/create/'.$eventId)
                ->with('error', 'Upload Full Paper tersedia setelah Anda mengunggah abstrak (dan tidak ditolak).');
        }

        [$effStatus] = $this->autoRejectionStatus($event, $fpStatus);
        $submissionId   = $this->findCurrentSubmissionId($userId, $eventId);
        $allowOptional  = $this->hasOptionalRevisionAllowed($submissionId, null);

        // Window tutup? tetap izinkan kalau opsional
        if (!$this->isFullPaperOpen($event) && !$allowOptional) {
            return redirect()->to('/presenter/fullpaper')->with('error', 'Pengumpulan Full Paper ditutup.');
        }

        // Event sudah mulai? blokir kecuali opsional
        if ($this->isEventStarted($event) && !$allowOptional) {
            return redirect()->to('/presenter/fullpaper/detail/'.$eventId)
                ->with('error', 'Event sudah berjalan — unggahan baru ditolak.');
        }

        if ($effStatus !== 'NONE' && !in_array($effStatus, ['REVISION','REJECTED'], true) && !$allowOptional) {
            return redirect()->to('/presenter/fullpaper/detail/'.$eventId)
                ->with('error', 'Anda sudah mengunggah Full Paper. Tidak bisa upload lagi pada status saat ini.');
        }

        if (!$file->isValid()) return redirect()->back()->with('error','File tidak valid.');
        $ext = strtolower($file->getClientExtension() ?: '');
        if ($ext !== 'pdf') return redirect()->back()->withInput()->with('error','File harus PDF.');
        if ($file->getSize() > 20 * 1024 * 1024) {
            return redirect()->back()->withInput()->with('error','Ukuran maksimal 20MB.');
        }

        $before = method_exists($this->fpModel,'getLatestRowByUserEvent')
            ? $this->fpModel->getLatestRowByUserEvent($userId, $eventId) : null;
        $beforeId = $this->resolveSubmissionId($before);

        try {
            $stored = $this->fpModel->moveUploadedFile($file, $userId, $eventId);

            $ok = $this->fpModel->attachFullPaperByUserEvent(
                $userId, $eventId, $stored, \App\Models\FullPaperModel::STATUS_UPLOADED
            );
            if (!$ok) return redirect()->back()->withInput()->with('error','Tidak ditemukan data abstrak/submission untuk ditempeli.');

            $this->forceUploadedStatus($userId, $eventId);

            $after  = method_exists($this->fpModel,'getLatestRowByUserEvent')
                ? $this->fpModel->getLatestRowByUserEvent($userId, $eventId) : null;
            $newId  = $this->resolveSubmissionId($after);

            // revisi_ke: unggahan pertama => 0, revisi (wajib/opsional) => +1
            $db    = \Config\Database::connect();
            $table = $db->tableExists('submissions') ? 'submissions' : ($db->tableExists('abstrak') ? 'abstrak' : null);
            if ($table && in_array('revisi_ke', $db->getFieldNames($table), true) && $newId) {
                $pk = $table==='submissions' ? 'id' : 'id_abstrak';
                if ($effStatus === 'NONE') {
                    $db->table($table)->where($pk, $newId)->update(['revisi_ke' => 0]);
                } else {
                    if ($allowOptional || in_array($effStatus, ['REVISION','REJECTED'], true)) {
                        $db->table($table)
                           ->where($pk, $newId)
                           ->set('revisi_ke', 'COALESCE(revisi_ke,0)+1', false)
                           ->update();
                    }
                }
            }

            if ($newId && (!$beforeId || $newId !== $beforeId)) {
                $this->propagateFullpaperAssignments($newId, $userId, $eventId, $beforeId);
            }

            // Jenis unggahan (NEW | REVISION | OPTIONAL)
            $uploadType = 'NEW';
            if ($allowOptional) {
                $uploadType = 'OPTIONAL';
            } else {
                if (in_array($effStatus, ['REVISION','REJECTED'], true)) {
                    $uploadType = 'REVISION';
                }
            }
            if ($newId) $this->setLastUploadType($newId, $uploadType);
            session()->setFlashdata('fp_upload_kind', $uploadType);

            // Notifikasi reviewer yang sebelumnya memberi revisi/reject
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

    public function download($filename)
    {
        $path = WRITEPATH.'uploads/fullpaper/'.$filename;
        if (!is_file($path)) return redirect()->back()->with('error','File tidak ditemukan.');
        $inline = (string)$this->request->getGet('inline') === '1';
        if ($inline) {
            return $this->response
                ->setHeader('Content-Type','application/pdf')
                ->setHeader('Content-Disposition','inline; filename="'.basename($path).'"')
                ->setBody(file_get_contents($path));
        }
        return $this->response->download($path, null);
    }

    public function cancel($eventId)
    {
        $userId  = (int) session()->get('id_user');
        $eventId = (int) $eventId;

        if (!$userId || !$eventId) {
            return redirect()->back()->with('error', 'Data tidak valid.');
        }

        $event = $this->eventModel->find($eventId);
        if (!$event) return redirect()->to('/presenter/fullpaper')->with('error', 'Event tidak ditemukan.');

        if (!$this->isFullPaperOpen($event) || $this->isEventStarted($event)) {
            return redirect()->to('/presenter/fullpaper/detail/'.$eventId)->with('error', 'Tidak dapat membatalkan pada periode ini.');
        }

        $abs = $this->abstrakModel->where('id_user', $userId)
                                  ->where('event_id', $eventId)
                                  ->orderBy('id_abstrak', 'DESC')->first();

        [, $path, ] = $this->latestFullpaperMeta($userId, $eventId, $abs);
        if ($path === '') {
            return redirect()->to('/presenter/fullpaper/detail/'.$eventId)->with('error', 'Tidak ada unggahan untuk dibatalkan.');
        }

        $submissionId = $this->findCurrentSubmissionId($userId, $eventId);
        if ($this->hasAnyReviewerAssigned($submissionId)) {
            return redirect()->to('/presenter/fullpaper/detail/'.$eventId)->with('error', 'Tidak dapat dibatalkan: reviewer sudah ditugaskan.');
        }

        $db = \Config\Database::connect();
        $fileDeleted = false;

        $basename = basename($path);
        $fsPath   = WRITEPATH.'uploads/fullpaper/'.$basename;
        if (is_file($fsPath)) {
            @unlink($fsPath);
            $fileDeleted = true;
        }

        $okUpdate = false;
        if ($db->tableExists('submissions')) {
            $fields = array_flip($db->getFieldNames('submissions') ?: []);
            $set = [];
            if (isset($fields['full_paper_path']))        $set['full_paper_path'] = null;
            if (isset($fields['full_paper_status']))      $set['full_paper_status'] = 'NONE';
            if (isset($fields['full_paper_uploaded_at'])) $set['full_paper_uploaded_at'] = null;
            if ($set) {
                $last = $db->table('submissions')
                    ->select('id')
                    ->where('user_id', $userId)
                    ->where('event_id', $eventId)
                    ->orderBy('id','DESC')
                    ->get()->getRowArray();

                if ($last) {
                    $okUpdate = $db->table('submissions')
                        ->where('id', (int)$last['id'])
                        ->update($set);
                }
            }
        } elseif ($db->tableExists('abstrak')) {
            $fields = array_flip($db->getFieldNames('abstrak') ?: []);
            $set = [];
            if (isset($fields['full_paper_path']))        $set['full_paper_path'] = null;
            if (isset($fields['full_paper_status']))      $set['full_paper_status'] = 'NONE';
            if (isset($fields['full_paper_uploaded_at'])) $set['full_paper_uploaded_at'] = null;
            if ($set) {
                $last = $db->table('abstrak')
                    ->select('id_abstrak')
                    ->where('id_user', $userId)
                    ->where('event_id', $eventId)
                    ->orderBy('id_abstrak','DESC')
                    ->get()->getRowArray();

                if ($last) {
                    $okUpdate = $db->table('abstrak')
                        ->where('id_abstrak', (int)$last['id_abstrak'])
                        ->update($set);
                }
            }
        }

        if (!$okUpdate) {
            return redirect()->to('/presenter/fullpaper/detail/'.$eventId)
                ->with('error', 'Gagal membatalkan unggahan.');
        }

        $msg = 'Unggahan Full Paper dibatalkan.';
        if ($fileDeleted) $msg .= ' File dihapus.';
        return redirect()->to('/presenter/fullpaper/detail/'.$eventId)->with('success', $msg);
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
