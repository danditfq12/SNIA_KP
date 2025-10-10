<?php
namespace App\Controllers\Role\Admin;

use App\Controllers\BaseController;
use App\Models\EventModel;
use App\Models\AbstrakModel;
use App\Models\FullPaperModel;

class KelolaPaper extends BaseController
{
    /** minimal reviewer yang dibutuhkan (untuk ringkasan) */
    private int $requiredReviewers = 3;

    /* =========================================================
     * INDEX  -> /admin/kelola-paper
     * (list semua event untuk kartu "Aktif/Mendatang" & "Berakhir")
     * + ringkasan beban reviewer (abstrak & full paper yang masih aktif)
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

        // ========== ABSTRAK BELUM DIASSIGN (DISTINCT USER) ==========
        $e['abs_unassigned'] = 0;
        if ($db->tableExists('abstrak')) {
            $absUserCol = $this->firstExistingColumn('abstrak', ['id_user','user_id','presenter_id']);
            if ($absUserCol) {
                // prefer pivot mapping
                $absPivot = $this->firstExistingTable(['abstrak_reviewers','abstrak_reviewer','reviewer_abstrak']);
                if ($absPivot) {
                    [$absCol,$revCol] = $this->findRelCols($absPivot, ['id_abstrak','abstrak_id'], ['id_reviewer','reviewer_id']);
                    if ($absCol && $revCol) {
                        $e['abs_unassigned'] = (int)$db->query("
                            SELECT COUNT(DISTINCT a.{$absUserCol}) c
                            FROM abstrak a
                            WHERE a.event_id = ?
                              AND NOT EXISTS (SELECT 1 FROM {$absPivot} p WHERE p.{$absCol} = a.id_abstrak)
                        ", [$eventId])->getRow('c');
                    }
                } else {
                    // fallback: tabel penilaian
                    $absReview = $this->firstExistingTable(['reviews','abstrak_reviews','review']);
                    if ($absReview) {
                        $colAbs = $this->firstExistingColumn($absReview, ['id_abstrak','abstrak_id']);
                        if ($colAbs) {
                            $e['abs_unassigned'] = (int)$db->query("
                                SELECT COUNT(DISTINCT a.{$absUserCol}) c
                                FROM abstrak a
                                WHERE a.event_id = ?
                                  AND NOT EXISTS (SELECT 1 FROM {$absReview} r WHERE r.{$colAbs} = a.id_abstrak)
                            ", [$eventId])->getRow('c');
                        }
                    }
                }
            }
        }

        // ========== FULL PAPER BELUM DIASSIGN (DISTINCT USER) ==========
        $e['fp_unassigned'] = 0;
        if ($fpCarrierTable && $this->columnExists($fpCarrierTable,'full_paper_path')) {
            $eventCol = $this->firstExistingColumn($fpCarrierTable, ['event_id','id_event','events_id']);
            $userCol  = $this->firstExistingColumn($fpCarrierTable, ['id_user','user_id','presenter_id','id_presenter']);
            if ($eventCol && $userCol) {
                $fpPivot = $this->firstExistingTable(['fullpaper_reviewers']); // pivot resmi
                if ($fpPivot) {
                    $relCol = $this->firstExistingColumn($fpPivot, ['submission_id','id_submission','fullpaper_id','id_fullpaper','abstrak_id','id_abstrak']);
                    if ($relCol) {
                        $e['fp_unassigned'] = (int)$db->query("
                            SELECT COUNT(DISTINCT s.{$userCol}) c
                            FROM {$fpCarrierTable} s
                            WHERE s.{$eventCol} = ?
                              AND s.full_paper_path IS NOT NULL
                              AND s.full_paper_path <> ''
                              AND NOT EXISTS (SELECT 1 FROM {$fpPivot} fr WHERE fr.{$relCol} = s.{$fpCarrierPK})
                        ", [$eventId])->getRow('c');
                    }
                } else {
                    // fallback: tabel review
                    $fpReviewTable = $this->firstExistingTable(['fullpaper_reviews','fullpaper_review','fp_review','review_fullpaper']);
                    if ($fpReviewTable) {
                        $relCol = $this->firstExistingColumn($fpReviewTable, ['submission_id','id_submission','fullpaper_id','id_fullpaper','abstrak_id','id_abstrak']);
                        if ($relCol) {
                            $e['fp_unassigned'] = (int)$db->query("
                                SELECT COUNT(DISTINCT s.{$userCol}) c
                                FROM {$fpCarrierTable} s
                                WHERE s.{$eventCol} = ?
                                  AND s.full_paper_path IS NOT NULL
                                  AND s.full_paper_path <> ''
                                  AND NOT EXISTS (SELECT 1 FROM {$fpReviewTable} r WHERE r.{$relCol} = s.{$fpCarrierPK})
                            ", [$eventId])->getRow('c');
                        }
                    } else {
                        // benar-benar belum ada sistem review → semua yang sudah upload (distinct user)
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

        // sort bucket
        if (($e['event_date'] ?? '') < $today) $berakhir[] = $e; else $aktif[] = $e;
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
     * (halaman per-event: daftar presenter + ringkasan abstrak/FP)
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

            // ----- full paper terakhir user pada event ini -----
            $latest = $fpModel->getLatestRowByUserEvent($userId, $eventId);

            $r['has_full']           = $latest && !empty($latest['full_paper_path']);
            $r['full_paper_name']    = $latest['full_paper_path'] ?? null;
            $r['full_paper_status']  = $latest['full_paper_status'] ?? null;
            $r['full_row_id']        = $latest ? ($latest[$fpModel->primaryKey] ?? null) : null;

            // ----- RINGKASAN ABSTRAK: assigned/complete -----
            $r['abs_assigned_count'] = $this->getAbstractAssignedCount($absId);
            $absComplete             = $this->getAbstractCompletedCount($absId);
            $r['abs_complete_count'] = $absComplete;
            $r['abs_missing']        = max(0, $this->requiredReviewers - $r['abs_assigned_count']);
            $r['abs_summary']        = sprintf('%d/%d reviewer ditugaskan • %d selesai', $r['abs_assigned_count'], $this->requiredReviewers, $absComplete);

            // ----- RINGKASAN FULLPAPER: assigned/complete -----
            $submissionId            = $this->resolveSubmissionId($fpModel, $latest);
            $fpAssigned              = $submissionId ? $this->getFullpaperAssignedCount($submissionId) : 0;
            $fpComplete              = $submissionId ? $this->getFullpaperCompletedCount($submissionId) : 0;

            $r['fp_assigned_count']  = $fpAssigned;
            $r['fp_complete_count']  = $fpComplete;
            $r['fp_missing']         = max(0, $this->requiredReviewers - $fpAssigned);
            $r['fp_summary']         = sprintf('%d/%d reviewer ditugaskan • %d selesai', $fpAssigned, $this->requiredReviewers, $fpComplete);

            // ----- LoA -----
            if ($dokTblExists) {
                $r['loa_exists'] = (bool) $db->table('dokumen')
                    ->where('id_user', $userId)->where('event_id', $eventId)->where('tipe', 'loa')
                    ->countAllResults();
            } elseif ($dokModel && method_exists($dokModel, 'hasUserDocument')) {
                $r['loa_exists'] = (bool) $dokModel->hasUserDocument($userId, $eventId, 'loa');
            } else {
                $r['loa_exists'] = false;
            }

            // URL aksi (hanya tugaskan)
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

    /* ========================= PREVIEW STREAM (opsional, masih ada) ========================= */

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

    /* ============================ Helpers (schema-agnostic) ============================ */

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

        // 1) Pivot
        $pivot = $this->firstExistingTable(['abstrak_reviewers','abstrak_reviewer','reviewer_abstrak']);
        if ($pivot) {
            $colAbs = $this->firstExistingColumn($pivot, ['id_abstrak','abstrak_id']);
            $colRev = $this->firstExistingColumn($pivot, ['id_reviewer','reviewer_id']);
            if ($colAbs && $colRev) {
                return (int)$db->table($pivot)->where($colAbs, $idAbstrak)->countAllResults();
            }
        }

        // 2) Tabel penilaian (distinct reviewer)
        $tbl = $this->firstExistingTable(['reviews','abstrak_reviews','review']);
        if (!$tbl) return 0;

        $colAbs = $this->firstExistingColumn($tbl, ['id_abstrak','abstrak_id']);
        $colRev = $this->firstExistingColumn($tbl, ['id_reviewer','reviewer_id']);
        if (!$colAbs || !$colRev) return 0;

        return (int)$db->table($tbl)
            ->select("COUNT(DISTINCT {$colRev}) AS c", false)
            ->where($colAbs, $idAbstrak)
            ->get()->getRow('c');
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

        // count reviewer unik yang sudah memberi keputusan final
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

        // 1) Pivot resmi
        if ($db->tableExists('fullpaper_reviewers')) {
            $colSub = $this->firstExistingColumn('fullpaper_reviewers', ['submission_id','id_submission','fullpaper_id','id_fullpaper','abstrak_id','id_abstrak']);
            if ($colSub) {
                return (int)$db->table('fullpaper_reviewers')->where($colSub, $submissionId)->countAllResults();
            }
        }

        // 2) Fallback: tabel penilaian (distinct reviewer)
        $tbl = $this->firstExistingTable(['fullpaper_reviews','fullpaper_review','fp_review','review_fullpaper']);
        if (!$tbl) return 0;

        $colSub = $this->firstExistingColumn($tbl, ['submission_id','id_submission','fullpaper_id','id_fullpaper','abstrak_id','id_abstrak']);
        $colRev = $this->firstExistingColumn($tbl, ['reviewer_id','id_reviewer']);
        if (!$colSub || !$colRev) return 0;

        return (int)$db->table($tbl)
            ->select("COUNT(DISTINCT {$colRev}) AS c", false)
            ->where($colSub, $submissionId)
            ->get()->getRow('c');
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
            ->whereIn("UPPER({$colSt})", ['ACCEPTED','REVISION','REJECTED'])
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

    /* ============================ NEW: Reviewer loads ============================ */

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

    /** Ambil semua reviewer */
    private function getAllReviewers(): array
    {
        $db  = \Config\Database::connect();
        $src = $this->resolveReviewerSource();
        if (!$src['table'] || !$src['id']) return [];

        $b = $db->table($src['table']);
        $sel = ["{$src['table']}.{$src['id']} AS id"];
        if ($src['name'])  $sel[] = "{$src['table']}.{$src['name']} AS name";
        if ($src['email']) $sel[] = "{$src['table']}.{$src['email']} AS email";
        $b->select(implode(', ', $sel));

        if ($src['table'] === 'users' && in_array('role',$db->getFieldNames('users'),true)) {
            $b->where('role','reviewer');
        }
        if ($src['name']) $b->orderBy($src['name'],'ASC');

        return $b->get()->getResultArray();
    }

    /** Hitung beban aktif (belum final) abstrak per reviewer */
    private function countActiveAbstractByReviewer(int $reviewerId): int
    {
        $db = \Config\Database::connect();

        $pivot = $this->firstExistingTable(['abstrak_reviewers','abstrak_reviewer','reviewer_abstrak']);
        $revT  = $this->firstExistingTable(['reviews','abstrak_reviews','review']);

        // definisi "final" utk abstrak
        $finalAbs = ['diterima','revisi','ditolak','accepted','revision','rejected'];

        if ($pivot) {
            $colAbsP = $this->firstExistingColumn($pivot, ['id_abstrak','abstrak_id']);
            $colRevP = $this->firstExistingColumn($pivot, ['id_reviewer','reviewer_id']);
            if ($colAbsP && $colRevP) {
                if ($revT) {
                    $colAbsR = $this->firstExistingColumn($revT, ['id_abstrak','abstrak_id']);
                    $colRevR = $this->firstExistingColumn($revT, ['id_reviewer','reviewer_id']);
                    $colStR  = $this->firstExistingColumn($revT, ['keputusan','status','decision']);
                    if ($colAbsR && $colRevR && $colStR) {
                        $row = $db->query("
                            SELECT COUNT(*) c
                            FROM {$pivot} p
                            WHERE p.{$colRevP} = ?
                              AND NOT EXISTS (
                                SELECT 1 FROM {$revT} r
                                WHERE r.{$colAbsR} = p.{$colAbsP}
                                  AND r.{$colRevR} = p.{$colRevP}
                                  AND LOWER(r.{$colStR}) IN ('".implode("','",$finalAbs)."')
                              )
                        ", [$reviewerId])->getRow();
                        return (int)($row->c ?? 0);
                    }
                }
                // jika tdk ada tabel review → semua assignment dianggap aktif
                return (int)$db->table($pivot)->where($colRevP, $reviewerId)->countAllResults();
            }
        }

        // tanpa pivot: pakai tabel review (abstrak) → hitung distinct id_abstrak yg belum final utk reviewer tsb
        if ($revT) {
            $colAbsR = $this->firstExistingColumn($revT, ['id_abstrak','abstrak_id']);
            $colRevR = $this->firstExistingColumn($revT, ['id_reviewer','reviewer_id']);
            $colStR  = $this->firstExistingColumn($revT, ['keputusan','status','decision']);
            if ($colAbsR && $colRevR && $colStR) {
                $row = $db->query("
                    SELECT COUNT(*) c FROM (
                      SELECT {$colAbsR} as aid,
                             MAX(CASE WHEN LOWER({$colStR}) IN ('".implode("','",$finalAbs)."') THEN 1 ELSE 0 END) AS has_final
                      FROM {$revT}
                      WHERE {$colRevR} = ?
                      GROUP BY {$colAbsR}
                    ) x
                    WHERE x.has_final = 0
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
        $fpP  = $this->firstExistingTable(['fullpaper_reviewers']);
        $fpR  = $this->firstExistingTable(['fullpaper_reviews','fullpaper_review','fp_review','review_fullpaper']);

        // definisi "final" utk full paper (assignment dianggap selesai)
        $finalFp = ['accepted','rejected','revision'];

        if ($fpP) {
            $colSubP = $this->firstExistingColumn($fpP, ['submission_id','id_submission','fullpaper_id','id_fullpaper','abstrak_id','id_abstrak']);
            $colRevP = $this->firstExistingColumn($fpP, ['reviewer_id','id_reviewer']);
            if ($colSubP && $colRevP) {
                if ($fpR) {
                    $colSubR = $this->firstExistingColumn($fpR, ['submission_id','id_submission','fullpaper_id','id_fullpaper','abstrak_id','id_abstrak']);
                    $colRevR = $this->firstExistingColumn($fpR, ['reviewer_id','id_reviewer']);
                    $colStR  = $this->firstExistingColumn($fpR, ['keputusan','status','decision']);
                    if ($colSubR && $colRevR && $colStR) {
                        $row = $db->query("
                            SELECT COUNT(*) c
                            FROM {$fpP} fr
                            WHERE fr.{$colRevP} = ?
                              AND NOT EXISTS (
                                SELECT 1 FROM {$fpR} r
                                WHERE r.{$colSubR} = fr.{$colSubP}
                                  AND r.{$colRevR} = fr.{$colRevP}
                                  AND LOWER(r.{$colStR}) IN ('".implode("','",$finalFp)."')
                              )
                        ", [$reviewerId])->getRow();
                        return (int)($row->c ?? 0);
                    }
                }
                // jika tdk ada tabel review → semua assignment dianggap aktif
                return (int)$db->table($fpP)->where($colRevP, $reviewerId)->countAllResults();
            }
        }

        // tanpa pivot: hitung dari tabel review (distinct submission) yang belum final oleh reviewer tsb
        if ($fpR) {
            $colSubR = $this->firstExistingColumn($fpR, ['submission_id','id_submission','fullpaper_id','id_fullpaper','abstrak_id','id_abstrak']);
            $colRevR = $this->firstExistingColumn($fpR, ['reviewer_id','id_reviewer']);
            $colStR  = $this->firstExistingColumn($fpR, ['keputusan','status','decision']);
            if ($colSubR && $colRevR && $colStR) {
                $row = $db->query("
                    SELECT COUNT(*) c FROM (
                      SELECT {$colSubR} as sid,
                             MAX(CASE WHEN LOWER({$colStR}) IN ('".implode("','",$finalFp)."') THEN 1 ELSE 0 END) AS has_final
                      FROM {$fpR}
                      WHERE {$colRevR} = ?
                      GROUP BY {$colSubR}
                    ) x
                    WHERE x.has_final = 0
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

        // urutkan: yang beban paling banyak di atas
        usort($reviewers, function($a,$b){
            $A = (int)($a['active_abs'] ?? 0) + (int)($a['active_fp'] ?? 0);
            $B = (int)($b['active_abs'] ?? 0) + (int)($b['active_fp'] ?? 0);
            if ($A === $B) {
                return strcasecmp((string)($a['name'] ?? ''), (string)($b['name'] ?? ''));
            }
            return $B <=> $A;
        });

        return $reviewers;
    }
}