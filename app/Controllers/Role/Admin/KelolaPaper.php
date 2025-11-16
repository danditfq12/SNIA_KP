<?php
namespace App\Controllers\Role\Admin;

use App\Controllers\BaseController;
use App\Models\EventModel;
use App\Models\AbstrakModel;
use App\Models\FullPaperModel;

class KelolaPaper extends BaseController
{
    // Kuota reviewer minimal
    private int $requiredAbs = 1; // Abstrak
    private int $requiredFp  = 3; // Full paper

    /* =========================================================
     * INDEX  -> /admin/kelola-paper
     * ========================================================= */
    public function index()
    {
        $eventModel = new EventModel();
        $events     = $eventModel->orderBy('event_date', 'DESC')->findAll();

        $db = \Config\Database::connect();

        // deteksi carrier fullpaper (submissions / abstrak)
        $fpCarrierTable = $db->tableExists('submissions') ? 'submissions'
                        : ($db->tableExists('abstrak')     ? 'abstrak'     : null);
        $fpCarrierPK    = $fpCarrierTable === 'abstrak' ? 'id_abstrak' : 'id';

        $today    = date('Y-m-d');
        $aktif    = [];
        $berakhir = [];

        foreach ($events as &$e) {
            $eventId = (int)($e['id'] ?? 0);

            // ========== PRESENTER COUNT (DISTINCT USER) ==========
            $e['presenter_count'] = 0;
            if ($db->tableExists('abstrak')) {
                $absUserCol = $this->firstExistingColumn('abstrak', ['id_user','user_id','presenter_id']);
                if ($absUserCol) {
                    $e['presenter_count'] = (int) $db->query("
                        SELECT COUNT(DISTINCT a.{$absUserCol}) c
                        FROM abstrak a
                        WHERE a.event_id = ?
                    ", [$eventId])->getRow('c');
                }
            } elseif ($fpCarrierTable) {
                $eventCol = $this->firstExistingColumn($fpCarrierTable, ['event_id','id_event','events_id']);
                $userCol  = $this->firstExistingColumn($fpCarrierTable, ['id_user','user_id','presenter_id','id_presenter']);
                if ($eventCol && $userCol) {
                    $e['presenter_count'] = (int) $db->query("
                        SELECT COUNT(DISTINCT s.{$userCol}) c
                        FROM {$fpCarrierTable} s
                        WHERE s.{$eventCol} = ?
                    ", [$eventId])->getRow('c');
                }
            }

            // ========== ABSTRAK: BELUM MEMENUHI KUOTA (DISTINCT USER) ==========
            $e['abs_unassigned'] = 0;
            if ($db->tableExists('abstrak')) {
                $absUserCol = $this->firstExistingColumn('abstrak', ['id_user','user_id','presenter_id']);
                if ($absUserCol) {
                    $absPivot = $this->firstExistingTable(['abstrak_reviewers','abstrak_reviewer','reviewer_abstrak']);
                    if ($absPivot) {
                        $absCol   = $this->firstExistingColumn($absPivot, ['id_abstrak','abstrak_id']);
                        $assignCol= $this->firstExistingColumn($absPivot, ['assignment_status','status_tugas','tugas_status','konfirmasi_status']);
                        if ($absCol) {
                            $assignedExpr = $assignCol
                                ? "SUM(CASE WHEN {$assignCol} IS NULL OR LOWER({$assignCol}) <> 'declined' THEN 1 ELSE 0 END)"
                                : "COUNT(*)";
                            $e['abs_unassigned'] = (int)$db->query("
                                SELECT COUNT(DISTINCT a.{$absUserCol}) c
                                FROM abstrak a
                                LEFT JOIN (
                                  SELECT {$absCol} aid, {$assignedExpr} AS assigned
                                  FROM {$absPivot}
                                  GROUP BY {$absCol}
                                ) ax ON ax.aid = a.id_abstrak
                                WHERE a.event_id = ?
                                  AND COALESCE(ax.assigned,0) < ?
                            ", [$eventId, $this->requiredAbs])->getRow('c');
                        }
                    } else {
                        // fallback: tabel penilaian (distinct reviewer)
                        $absReview = $this->firstExistingTable(['reviews','abstrak_reviews','review']);
                        if ($absReview) {
                            $colAbs = $this->firstExistingColumn($absReview, ['id_abstrak','abstrak_id']);
                            $colRev = $this->firstExistingColumn($absReview, ['id_reviewer','reviewer_id']);
                            if ($colAbs && $colRev) {
                                $e['abs_unassigned'] = (int)$db->query("
                                    SELECT COUNT(DISTINCT a.{$absUserCol}) c
                                    FROM abstrak a
                                    LEFT JOIN (
                                      SELECT {$colAbs} aid, COUNT(DISTINCT {$colRev}) assigned
                                      FROM {$absReview}
                                      GROUP BY {$colAbs}
                                    ) ar ON ar.aid = a.id_abstrak
                                    WHERE a.event_id = ?
                                      AND COALESCE(ar.assigned,0) < ?
                                ", [$eventId, $this->requiredAbs])->getRow('c');
                            }
                        } else {
                            // tidak ada sistem review → semua dianggap belum memenuhi kuota
                            $e['abs_unassigned'] = (int)$db->query("
                                SELECT COUNT(DISTINCT a.{$absUserCol}) c
                                FROM abstrak a WHERE a.event_id = ?
                            ", [$eventId])->getRow('c');
                        }
                    }
                }
            }

            // ========== FULL PAPER: BELUM MEMENUHI KUOTA (DISTINCT USER) ==========
            $e['fp_unassigned'] = 0;
            if ($fpCarrierTable && $this->columnExists($fpCarrierTable,'full_paper_path')) {
                $eventCol = $this->firstExistingColumn($fpCarrierTable, ['event_id','id_event','events_id']);
                $userCol  = $this->firstExistingColumn($fpCarrierTable, ['id_user','user_id','presenter_id','id_presenter']);
                if ($eventCol && $userCol) {
                    $fpPivot = $this->firstExistingTable(['fullpaper_reviewers']); // pivot resmi
                    if ($fpPivot) {
                        $relCol    = $this->firstExistingColumn($fpPivot, ['submission_id','id_submission','fullpaper_id','id_fullpaper','abstrak_id','id_abstrak']);
                        $assignCol = $this->firstExistingColumn($fpPivot, ['assignment_status','status_tugas','tugas_status','konfirmasi_status']);
                        if ($relCol) {
                            $assignedExpr = $assignCol
                                ? "SUM(CASE WHEN {$assignCol} IS NULL OR LOWER({$assignCol}) <> 'declined' THEN 1 ELSE 0 END)"
                                : "COUNT(*)";
                            $e['fp_unassigned'] = (int)$db->query("
                                SELECT COUNT(DISTINCT s.{$userCol}) c
                                FROM {$fpCarrierTable} s
                                LEFT JOIN (
                                  SELECT {$relCol} sid, {$assignedExpr} AS assigned
                                  FROM {$fpPivot}
                                  GROUP BY {$relCol}
                                ) fr ON fr.sid = s.{$fpCarrierPK}
                                WHERE s.{$eventCol} = ?
                                  AND s.full_paper_path IS NOT NULL
                                  AND s.full_paper_path <> ''
                                  AND COALESCE(fr.assigned,0) < ?
                            ", [$eventId, $this->requiredFp])->getRow('c');
                        }
                    } else {
                        // fallback: tabel review (distinct reviewer)
                        $fpReviewTable = $this->firstExistingTable(['fullpaper_reviews','fullpaper_review','fp_review','review_fullpaper']);
                        if ($fpReviewTable) {
                            $relCol = $this->firstExistingColumn($fpReviewTable, ['submission_id','id_submission','fullpaper_id','id_fullpaper','abstrak_id','id_abstrak']);
                            $colRev = $this->firstExistingColumn($fpReviewTable, ['reviewer_id','id_reviewer']);
                            if ($relCol && $colRev) {
                                $e['fp_unassigned'] = (int)$db->query("
                                    SELECT COUNT(DISTINCT s.{$userCol}) c
                                    FROM {$fpCarrierTable} s
                                    LEFT JOIN (
                                      SELECT {$relCol} sid, COUNT(DISTINCT {$colRev}) assigned
                                      FROM {$fpReviewTable}
                                      GROUP BY {$relCol}
                                    ) rr ON rr.sid = s.{$fpCarrierPK}
                                    WHERE s.{$eventCol} = ?
                                      AND s.full_paper_path IS NOT NULL
                                      AND s.full_paper_path <> ''
                                      AND COALESCE(rr.assigned,0) < ?
                                ", [$eventId, $this->requiredFp])->getRow('c');
                            }
                        } else {
                            // tidak ada sistem review → semua yang sudah upload dianggap belum memenuhi kuota
                            $e['fp_unassigned'] = (int)$db->query("
                                SELECT COUNT(DISTINCT s.{$userCol}) c
                                FROM {$fpCarrierTable} s
                                WHERE s.{$eventCol} = ?
                                  AND s.full_paper_path IS NOT NULL
                                  AND s.full_paper_path <> ''
                            ", [$eventId])->getRow('c');
                        }
                    }
                }
            }

            // ========== penentuan bucket (Aktif vs Berakhir) ==========
            $nowTs        = time();
            $eventEndTs   = strtotime(trim(($e['event_date'] ?? $today).' '.($e['event_time'] ?? '23:59:59')));
            $fpDeadlineTs = !empty($e['full_paper_deadline']) ? strtotime($e['full_paper_deadline']) : 0;
            $doneMarkerTs = max($eventEndTs ?: 0, $fpDeadlineTs ?: 0);

            if ($doneMarkerTs && $doneMarkerTs < $nowTs) {
                $berakhir[] = $e;
            } else {
                $aktif[] = $e;
            }
        }
        unset($e);

        // ========== RINGKASAN BEBAN REVIEWER ==========
        $reviewerLoads = $this->getReviewerLoads();

        return view('role/admin/kelola_paper/index', [
            'title'         => 'Kelola Paper',
            'aktif'         => $aktif,
            'berakhir'      => $berakhir,
            'reviewerLoads' => $reviewerLoads,
        ]);
    }

    /* =========================================================
     * DETAIL -> /admin/kelola-paper/detail/{eventId}
     * ========================================================= */
    public function detail(int $eventId)
    {
        $abstrak = new AbstrakModel();
        $fpModel = new FullPaperModel();
        $db      = \Config\Database::connect();

        $rows = $abstrak->db->table('abstrak a')
            ->select('a.*, u.nama_lengkap, u.email, k.nama_kategori, e.title AS event_title, e.abstract_deadline, e.full_paper_deadline')
            ->join('users u', 'u.id_user = a.id_user')
            ->join('kategori_abstrak k', 'k.id_kategori = a.id_kategori', 'left')
            ->join('events e', 'e.id = a.event_id', 'left')
            ->where('a.event_id', $eventId)
            ->orderBy('a.tanggal_upload', 'DESC')
            ->get()->getResultArray();

        // dokumen LoA (opsional)
        $dokTblExists = $db->tableExists('dokumen');
        $dokModel     = (!$dokTblExists && class_exists(\App\Models\DokumenModel::class))
                        ? new \App\Models\DokumenModel()
                        : null;

        foreach ($rows as &$r) {
            $userId = (int)($r['id_user'] ?? 0);
            $absId  = (int)($r['id_abstrak'] ?? 0);

            // full paper terakhir user pada event ini
            $latest = $fpModel->getLatestRowByUserEvent($userId, $eventId);

            $r['has_full']           = $latest && !empty($latest['full_paper_path']);
            $r['full_paper_name']    = $latest['full_paper_path'] ?? null;
            $r['full_paper_status']  = $latest['full_paper_status'] ?? null;
            $r['full_row_id']        = $latest ? ($latest[$fpModel->primaryKey] ?? null) : null;

            // RINGKASAN ABSTRAK
            $r['abs_assigned_count'] = $this->getAbstractAssignedCount($absId);
            $absComplete             = $this->getAbstractCompletedCount($absId);
            $r['abs_complete_count'] = $absComplete;
            $r['abs_missing']        = max(0, $this->requiredAbs - $r['abs_assigned_count']);
            $r['abs_summary']        = sprintf('%d/%d reviewer ditugaskan • %d selesai',
                $r['abs_assigned_count'], $this->requiredAbs, $absComplete
            );

            // RINGKASAN FULLPAPER
            $submissionId            = $this->resolveSubmissionId($fpModel, $latest);
            $fpAssigned              = $submissionId ? $this->getFullpaperAssignedCount($submissionId) : 0;
            $fpComplete              = $submissionId ? $this->getFullpaperCompletedCount($submissionId) : 0;

            $r['fp_assigned_count']  = $fpAssigned;
            $r['fp_complete_count']  = $fpComplete;
            $r['fp_missing']         = max(0, $this->requiredFp - $r['fp_assigned_count']);
            $r['fp_summary']         = sprintf('%d/%d reviewer ditugaskan • %d selesai',
                $fpAssigned, $this->requiredFp, $fpComplete
            );

            // LoA
            if ($dokTblExists) {
                $r['loa_exists'] = (bool) $db->table('dokumen')
                    ->where('id_user', $userId)->where('event_id', $eventId)->where('tipe', 'loa')
                    ->countAllResults();
            } elseif ($dokModel && method_exists($dokModel, 'hasUserDocument')) {
                $r['loa_exists'] = (bool) $dokModel->hasUserDocument($userId, $eventId, 'loa');
            } else {
                $r['loa_exists'] = false;
            }

            // URL aksi
            $r['assign_abs_url'] = site_url('admin/abstrak/detail/'.$absId);
            $r['assign_fp_url']  = $r['full_row_id']
                                 ? site_url('admin/fullpaper/detail/'.$r['full_row_id'])
                                 : site_url('admin/fullpaper');
        }
        unset($r);

        return view('role/admin/kelola_paper/detail', [
            'eventId'    => $eventId,
            'presenters' => $rows,
        ]);
    }

    /* ========================= PREVIEW STREAM ========================= */

    public function viewAbstract(int $idAbstrak)
    {
        $ab  = new AbstrakModel();
        $row = $ab->find($idAbstrak);
        if (!$row || empty($row['file_abstrak'])) {
            return redirect()->back()->with('error', 'Abstrak tidak ditemukan.');
        }
        $path = WRITEPATH.'uploads/abstrak/'.$row['file_abstrak'];
        if (!is_file($path)) {
            return redirect()->back()->with('error', 'Berkas abstrak tidak tersedia.');
        }
        return $this->streamFile($path, $row['file_abstrak']);
    }

    public function viewFull(int $idUser, int $idEvent)
    {
        $fp     = new FullPaperModel();
        $latest = $fp->getLatestRowByUserEvent($idUser, $idEvent);
        if (!$latest || empty($latest['full_paper_path'])) {
            return redirect()->back()->with('error', 'Full paper belum diunggah.');
        }
        $path = WRITEPATH.'uploads/fullpaper/'.$latest['full_paper_path'];
        if (!is_file($path)) {
            return redirect()->back()->with('error', 'Berkas full paper tidak tersedia.');
        }
        return $this->streamFile($path, $latest['full_paper_path']);
    }

    /* ============================ Helpers (schema introspection) ============================ */

    private function columnExists(string $table, string $column): bool
    {
        $db = \Config\Database::connect();
        if (!$db->tableExists($table)) return false;
        foreach ($db->getFieldData($table) as $f) {
            if (strcasecmp($f->name, $column) === 0) return true;
        }
        return false;
    }

    private function firstExistingTable(array $candidates): ?string
    {
        $db = \Config\Database::connect();
        foreach ($candidates as $t) if ($db->tableExists($t)) return $t;
        return null;
    }

    private function firstExistingColumn(string $table, array $candidates): ?string
    {
        foreach ($candidates as $c) if ($this->columnExists($table, $c)) return $c;
        return null;
    }

    private function findRelCols(string $table, array $leftCandidates, array $rightCandidates): array
    {
        $l = $this->firstExistingColumn($table, $leftCandidates);
        $r = $this->firstExistingColumn($table, $rightCandidates);
        return [$l,$r];
    }

    private function resolveSubmissionId(FullPaperModel $fpModel, ?array $latestRow): ?int
    {
        if (!$latestRow) return null;
        foreach ([$fpModel->primaryKey, 'submission_id', 'id_submission', 'id', 'id_abstrak'] as $cand) {
            if (array_key_exists($cand, $latestRow) && !empty($latestRow[$cand])) {
                return (int)$latestRow[$cand];
            }
        }
        return null;
    }

    /* ---------- Abstrak: assigned & completed ---------- */

    private function getAbstractAssignedCount(int $idAbstrak): int
    {
        if ($idAbstrak <= 0) return 0;
        $db = \Config\Database::connect();

        // 1) Pivot (pakai status tugas kalau ada) → hitung NON-declined
        if ($pivot = $this->firstExistingTable(['abstrak_reviewers','abstrak_reviewer','reviewer_abstrak'])) {
            $colAbs  = $this->firstExistingColumn($pivot, ['id_abstrak','abstrak_id']);
            $colStat = $this->firstExistingColumn($pivot, ['assignment_status','status_tugas','tugas_status','konfirmasi_status']);
            if ($colAbs) {
                if ($colStat) {
                    $row = $db->table($pivot)
                        ->select("SUM(CASE WHEN {$colStat} IS NULL OR LOWER({$colStat}) <> 'declined' THEN 1 ELSE 0 END) AS c", false)
                        ->where($colAbs, $idAbstrak)->get()->getRow();
                    return (int)($row->c ?? 0);
                }
                // tanpa kolom status → hitung semua baris
                return (int)$db->table($pivot)->where($colAbs, $idAbstrak)->countAllResults();
            }
        }

        // 2) Fallback: distinct reviewer di tabel review
        if ($tbl = $this->firstExistingTable(['reviews','abstrak_reviews','review'])) {
            $colAbs = $this->firstExistingColumn($tbl, ['id_abstrak','abstrak_id']);
            $colRev = $this->firstExistingColumn($tbl, ['id_reviewer','reviewer_id']);
            if ($colAbs && $colRev) {
                $row = $db->table($tbl)->select("COUNT(DISTINCT {$colRev}) AS c", false)->where($colAbs, $idAbstrak)->get()->getRow();
                return (int)($row->c ?? 0);
            }
        }
        return 0;
    }

    private function getAbstractCompletedCount(int $idAbstrak): int
    {
        if ($idAbstrak <= 0) return 0;
        $db = \Config\Database::connect();

        $tbl = $this->firstExistingTable(['reviews','abstrak_reviews','review']);
        if (!$tbl) return 0;

        $colAbs = $this->firstExistingColumn($tbl, ['id_abstrak','abstrak_id']);
        $colRev = $this->firstExistingColumn($tbl, ['id_reviewer','reviewer_id']);
        $colSt  = $this->firstExistingColumn($tbl, ['keputusan','status','decision']);
        if (!$colAbs || !$colRev || !$colSt) return 0;

        // reviewer unik yang sudah memberi keputusan final
        return (int)$db->table($tbl)
            ->select("COUNT(DISTINCT {$colRev}) AS c", false)
            ->where($colAbs, $idAbstrak)
            ->whereIn("LOWER({$colSt})", ['diterima','revisi','ditolak','accepted','revision','rejected'])
            ->get()->getRow('c');
    }

    /* ---------- Fullpaper: assigned & completed ---------- */

    private function getFullpaperAssignedCount(int $submissionId): int
    {
        $db = \Config\Database::connect();

        if ($db->tableExists('fullpaper_reviewers')) {
            $colSub    = $this->firstExistingColumn('fullpaper_reviewers', ['submission_id','id_submission','fullpaper_id','id_fullpaper','abstrak_id','id_abstrak']);
            $colAssign = $this->firstExistingColumn('fullpaper_reviewers', ['assignment_status','status_tugas','tugas_status','konfirmasi_status']);
            if ($colSub) {
                if ($colAssign) {
                    // assignment AKTIF: accepted/pending/NULL
                    $row = $db->table('fullpaper_reviewers')
                        ->select("SUM(CASE WHEN {$colAssign} IS NULL OR LOWER({$colAssign}) IN ('accepted','pending') THEN 1 ELSE 0 END) AS c", false)
                        ->where($colSub, $submissionId)->get()->getRow();
                    return (int)($row->c ?? 0);
                }
                return (int)$db->table('fullpaper_reviewers')->where($colSub, $submissionId)->countAllResults();
            }
        }

        // fallback: distinct reviewer di tabel review
        if ($tbl = $this->firstExistingTable(['fullpaper_reviews','fullpaper_review','fp_review','review_fullpaper'])) {
            $colSub = $this->firstExistingColumn($tbl, ['submission_id','id_submission','fullpaper_id','id_fullpaper','abstrak_id','id_abstrak']);
            $colRev = $this->firstExistingColumn($tbl, ['reviewer_id','id_reviewer']);
            if ($colSub && $colRev) {
                $row = $db->table($tbl)->select("COUNT(DISTINCT {$colRev}) AS c", false)->where($colSub, $submissionId)->get()->getRow();
                return (int)($row->c ?? 0);
            }
        }
        return 0;
    }

    private function getFullpaperCompletedCount(int $submissionId): int
    {
        $db  = \Config\Database::connect();
        $tbl = $this->firstExistingTable(['fullpaper_reviews','fullpaper_review','fp_review','review_fullpaper']);
        if (!$tbl) return 0;

        $colSub = $this->firstExistingColumn($tbl, ['submission_id','id_submission','fullpaper_id','id_fullpaper','abstrak_id','id_abstrak']);
        $colRev = $this->firstExistingColumn($tbl, ['reviewer_id','id_reviewer']);
        $colSt  = $this->firstExistingColumn($tbl, ['keputusan','status','decision']);
        if (!$colSub || !$colRev || !$colSt) return 0;

        return (int)$db->table($tbl)
            ->select("COUNT(DISTINCT {$colRev}) AS c", false)
            ->where($colSub, $submissionId)
            ->whereIn("LOWER({$colSt})", ['accepted','revision','rejected'])
            ->get()->getRow('c');
    }

    /* ---------- stream file ---------- */

    private function streamFile(string $path, string $downloadName)
    {
        $mime = $this->detectMime($path) ?: 'application/pdf';
        if (function_exists('ob_get_level')) { while (ob_get_level() > 0) @ob_end_clean(); }
        @ini_set('display_errors', '0');

        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Content-Disposition', 'inline; filename="'.basename($downloadName).'"')
            ->setHeader('X-Content-Type-Options','nosniff')
            ->setBody(file_get_contents($path));
    }

    private function detectMime(string $path): ?string
    {
        if (function_exists('finfo_open')) {
            $f = finfo_open(FILEINFO_MIME_TYPE);
            $m = $f ? finfo_file($f, $path) : null;
            if ($f) finfo_close($f);
            return $m ?: null;
        }
        return null;
    }

    /* ============================ Reviewer sources & loads ============================ */

    /** Sumber identitas reviewer (users/reviewers) */
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

    /** Kumpulkan semua reviewer_id dari pivot dan tabel review (distinct) */
    private function collectReviewerIds(): array
    {
        $db = \Config\Database::connect();
        $ids = [];

        $col = function(string $table, array $cands){
            foreach ($cands as $c) if ($this->columnExists($table,$c)) return $c;
            return null;
        };

        // 1) Pivot Abstrak
        foreach (['abstrak_reviewers','abstrak_reviewer','reviewer_abstrak'] as $t) {
            if ($db->tableExists($t)) {
                $rid = $col($t, ['reviewer_id','id_reviewer','user_id','id_user']);
                if ($rid) {
                    $rows = $db->table($t)->select("DISTINCT {$rid} AS id", false)->get()->getResultArray();
                    foreach ($rows as $r) { $ids[(int)$r['id']] = true; }
                }
            }
        }

        // 2) Pivot Fullpaper
        if ($db->tableExists('fullpaper_reviewers')) {
            $rid = $col('fullpaper_reviewers', ['reviewer_id','id_reviewer','user_id','id_user']);
            if ($rid) {
                $rows = $db->table('fullpaper_reviewers')->select("DISTINCT {$rid} AS id", false)->get()->getResultArray();
                foreach ($rows as $r) { $ids[(int)$r['id']] = true; }
            }
        }

        // 3) Review Abstrak
        foreach (['reviews','abstrak_reviews','review'] as $t) {
            if ($db->tableExists($t)) {
                $rid = $col($t, ['reviewer_id','id_reviewer','user_id','id_user']);
                if ($rid) {
                    $rows = $db->table($t)->select("DISTINCT {$rid} AS id", false)->get()->getResultArray();
                    foreach ($rows as $r) { $ids[(int)$r['id']] = true; }
                }
            }
        }

        // 4) Review Fullpaper
        foreach (['fullpaper_reviews','fullpaper_review','fp_review','review_fullpaper'] as $t) {
            if ($db->tableExists($t)) {
                $rid = $col($t, ['reviewer_id','id_reviewer','user_id','id_user']);
                if ($rid) {
                    $rows = $db->table($t)->select("DISTINCT {$rid} AS id", false)->get()->getResultArray();
                    foreach ($rows as $r) { $ids[(int)$r['id']] = true; }
                }
            }
        }

        // 5) Fallback: users.role = reviewer (kalau ada)
        if ($db->tableExists('users') && $this->columnExists('users','role')) {
            $rows = $db->table('users')->select('id_user AS id')
                ->whereIn('role', ['reviewer','reviewer_abs','reviewer_fp'])
                ->get()->getResultArray();
            foreach ($rows as $r) { $ids[(int)$r['id']] = true; }
        }

        unset($ids[0]); // buang nol
        return array_keys($ids);
    }

    /** Ambil profil reviewer dari users/reviewers untuk ID yang terkumpul */
    private function getAllReviewers(): array
    {
        $db     = \Config\Database::connect();
        $ids    = $this->collectReviewerIds();
        if (!$ids) return [];

        $src = $this->resolveReviewerSource();
        if (!$src['table'] || !$src['id']) {
            return array_map(fn($id)=>['id'=>$id,'name'=>"Reviewer #$id",'email'=>null], $ids);
        }

        $b = $db->table($src['table']);
        $sel = ["{$src['table']}.{$src['id']} AS id"];
        if ($src['name'])  $sel[] = "{$src['table']}.{$src['name']} AS name";
        if ($src['email']) $sel[] = "{$src['table']}.{$src['email']} AS email";
        $b->select(implode(', ',$sel))->whereIn($src['id'], $ids);
        $rows = $b->get()->getResultArray();

        $out = [];
        foreach ($ids as $id) {
            $hit = null;
            foreach ($rows as $r) if ((int)$r['id'] === (int)$id) { $hit = $r; break; }
            $out[] = [
                'id'    => (int)$id,
                'name'  => $hit['name'] ?? ("Reviewer #$id"),
                'email' => $hit['email'] ?? null,
            ];
        }
        return $out;
    }

    /** Hitung beban aktif (belum final) abstrak per reviewer */
    private function countActiveAbstractByReviewer(int $reviewerId): int
    {
        $db = \Config\Database::connect();
        $finalAbs = ['diterima','revisi','ditolak','accepted','revision','rejected'];

        if ($pivot = $this->firstExistingTable(['abstrak_reviewers','abstrak_reviewer','reviewer_abstrak'])) {
            $colAbs  = $this->firstExistingColumn($pivot, ['id_abstrak','abstrak_id']);
            $colRev  = $this->firstExistingColumn($pivot, ['id_reviewer','reviewer_id','user_id','id_user']);
            $colStat = $this->firstExistingColumn($pivot, ['assignment_status','status_tugas','tugas_status','konfirmasi_status']);

            if ($colAbs && $colRev) {
                $assignFilter = '';
                if ($colStat) $assignFilter = " AND (p.{$colStat} IS NULL OR LOWER(p.{$colStat}) IN ('accepted','pending')) ";
                if ($revT = $this->firstExistingTable(['reviews','abstrak_reviews','review'])) {
                    $colAbsR = $this->firstExistingColumn($revT, ['id_abstrak','abstrak_id']);
                    $colRevR = $this->firstExistingColumn($revT, ['id_reviewer','reviewer_id']);
                    $colStR  = $this->firstExistingColumn($revT, ['keputusan','status','decision']);
                    if ($colAbsR && $colRevR && $colStR) {
                        $sql = "
                            SELECT COUNT(*) c
                            FROM {$pivot} p
                            WHERE p.{$colRev} = ?
                              {$assignFilter}
                              AND NOT EXISTS (
                                SELECT 1 FROM {$revT} r
                                WHERE r.{$colAbsR} = p.{$colAbs}
                                  AND r.{$colRevR} = p.{$colRev}
                                  AND LOWER(r.{$colStR}) IN ('".implode("','",$finalAbs)."')
                              )
                        ";
                        $row = $db->query($sql, [$reviewerId])->getRow();
                        return (int)($row->c ?? 0);
                    }
                }
                // tanpa tabel review → hitung assignment aktif saja
                $b = $db->table($pivot)->where($colRev, $reviewerId);
                if ($colStat) {
                    $b->groupStart()
                        ->where($colStat, null)
                        ->orWhereIn("LOWER({$colStat})", ['accepted','pending'])
                      ->groupEnd();
                }
                return (int)$b->countAllResults();
            }
        }

        // fallback: dari tabel review (distinct abstrak) yg belum final
        if ($revT = $this->firstExistingTable(['reviews','abstrak_reviews','review'])) {
            $colAbsR = $this->firstExistingColumn($revT, ['id_abstrak','abstrak_id']);
            $colRevR = $this->firstExistingColumn($revT, ['id_reviewer','reviewer_id']);
            $colStR  = $this->firstExistingColumn($revT, ['keputusan','status','decision']);
            if ($colAbsR && $colRevR && $colStR) {
                $row = $db->query("
                    SELECT COUNT(*) c FROM (
                      SELECT {$colAbsR} aid,
                             MAX(CASE WHEN LOWER({$colStR}) IN ('".implode("','",$finalAbs)."') THEN 1 ELSE 0 END) AS has_final
                      FROM {$revT}
                      WHERE {$colRevR} = ?
                      GROUP BY {$colAbsR}
                    ) x WHERE x.has_final = 0
                ", [$reviewerId])->getRow();
                return (int)($row->c ?? 0);
            }
        }
        return 0;
    }

    /** Hitung beban aktif (belum final) full paper per reviewer */
    private function countActiveFullpaperByReviewer(int $reviewerId): int
    {
        $db   = \Config\Database::connect();
        $finalFp = ['accepted','rejected','revision'];

        if ($fpP = $this->firstExistingTable(['fullpaper_reviewers'])) {
            $colSub = $this->firstExistingColumn($fpP, ['submission_id','id_submission','fullpaper_id','id_fullpaper','abstrak_id','id_abstrak']);
            $colRev = $this->firstExistingColumn($fpP, ['reviewer_id','id_reviewer','user_id','id_user']);
            $colStt = $this->firstExistingColumn($fpP, ['assignment_status','status_tugas','tugas_status','konfirmasi_status']);
            if ($colSub && $colRev) {
                $assignFilter = '';
                if ($colStt) $assignFilter = " AND (LOWER(fr.{$colStt}) IN ('accepted','pending') OR fr.{$colStt} IS NULL) ";

                if ($fpR = $this->firstExistingTable(['fullpaper_reviews','fullpaper_review','fp_review','review_fullpaper'])) {
                    $colSubR = $this->firstExistingColumn($fpR, ['submission_id','id_submission','fullpaper_id','id_fullpaper','abstrak_id','id_abstrak']);
                    $colRevR = $this->firstExistingColumn($fpR, ['reviewer_id','id_reviewer']);
                    $colStR  = $this->firstExistingColumn($fpR, ['keputusan','status','decision']);
                    if ($colSubR && $colRevR && $colStR) {
                        $sql = "
                            SELECT COUNT(*) c
                            FROM {$fpP} fr
                            WHERE fr.{$colRev} = ?
                              {$assignFilter}
                              AND NOT EXISTS (
                                SELECT 1 FROM {$fpR} r
                                WHERE r.{$colSubR} = fr.{$colSub}
                                  AND r.{$colRevR} = fr.{$colRev}
                                  AND LOWER(r.{$colStR}) IN ('".implode("','",$finalFp)."')
                              )
                        ";
                        $row = $db->query($sql, [$reviewerId])->getRow();
                        return (int)($row->c ?? 0);
                    }
                }
                // tanpa tabel review → assignment aktif saja
                $b = $db->table($fpP)->where($colRev, $reviewerId);
                if ($colStt) {
                    $b->groupStart()
                        ->where($colStt, null)
                        ->orWhereIn("LOWER({$colStt})", ['accepted','pending'])
                      ->groupEnd();
                }
                return (int)$b->countAllResults();
            }
        }

        // fallback: dari tabel review (distinct submission) yg belum final
        if ($fpR = $this->firstExistingTable(['fullpaper_reviews','fullpaper_review','fp_review','review_fullpaper'])) {
            $colSubR = $this->firstExistingColumn($fpR, ['submission_id','id_submission','fullpaper_id','id_fullpaper','abstrak_id','id_abstrak']);
            $colRevR = $this->firstExistingColumn($fpR, ['reviewer_id','id_reviewer']);
            $colStR  = $this->firstExistingColumn($fpR, ['keputusan','status','decision']);
            if ($colSubR && $colRevR && $colStR) {
                $row = $db->query("
                    SELECT COUNT(*) c FROM (
                      SELECT {$colSubR} sid,
                             MAX(CASE WHEN LOWER({$colStR}) IN ('".implode("','",$finalFp)."') THEN 1 ELSE 0 END) AS has_final
                      FROM {$fpR}
                      WHERE {$colRevR} = ?
                      GROUP BY {$colSubR}
                    ) x WHERE x.has_final = 0
                ", [$reviewerId])->getRow();
                return (int)($row->c ?? 0);
            }
        }

        return 0;
    }

    /** Daftar reviewer + beban aktif (abstrak & fullpaper) */
    private function getReviewerLoads(): array
    {
        $reviewers = $this->getAllReviewers();
        foreach ($reviewers as &$rv) {
            $rid = (int)($rv['id'] ?? 0);
            $rv['active_abs'] = $rid ? $this->countActiveAbstractByReviewer($rid)   : 0;
            $rv['active_fp']  = $rid ? $this->countActiveFullpaperByReviewer($rid) : 0;
        }
        unset($rv);

        // urutkan: beban terbanyak di atas
        usort($reviewers, function($a,$b){
            $A = (int)($a['active_abs'] ?? 0) + (int)($a['active_fp'] ?? 0);
            $B = (int)($b['active_abs'] ?? 0) + (int)($b['active_fp'] ?? 0);
            if ($A === $B) return strcasecmp((string)($a['name'] ?? ''), (string)($b['name'] ?? ''));
            return $B <=> $A;
        });

        return $reviewers;
    }
}
