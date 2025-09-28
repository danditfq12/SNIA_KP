<?php
namespace App\Controllers\Role\Admin;

use App\Controllers\BaseController;
use App\Models\EventModel;
use App\Models\AbstrakModel;
use App\Models\FullPaperModel;

class KelolaPaper extends BaseController
{
    /** berapa reviewer yang dibutuhkan (dipakai untuk ringkasan) */
    private int $requiredReviewers = 3;

    /** ====== HALAMAN LIST EVENT ====== */
    public function events()
    {
        $eventModel = new EventModel();
        $events     = $eventModel->orderBy('event_date', 'DESC')->findAll();

        $db = \Config\Database::connect();

        // deteksi tabel review full paper
        $fpReviewTable = null;
        foreach (['fullpaper_reviews','fullpaper_review','fp_review','review_fullpaper'] as $cand) {
            if ($db->tableExists($cand)) { $fpReviewTable = $cand; break; }
        }

        // deteksi carrier fullpaper (submissions/abstrak)
        $hasSubmissions = $db->tableExists('submissions');
        $fpCarrierTable = $hasSubmissions ? 'submissions' : ( $db->tableExists('abstrak') ? 'abstrak' : null );
        $fpCarrierPK    = ($fpCarrierTable === 'abstrak') ? 'id_abstrak' : 'id';

        $today = date('Y-m-d');
        $aktif = []; $berakhir = [];

        foreach ($events as &$e) {
            $eventId = (int)($e['id'] ?? 0);

            // jumlah presenter (abstrak)
            $e['presenter_count'] = $db->tableExists('abstrak')
                ? (int)$db->table('abstrak')->where('event_id', $eventId)->countAllResults()
                : 0;

            // abstrak belum ditugaskan (tidak ada baris di review)
            if ($db->tableExists('review') && $db->tableExists('abstrak')) {
                $e['abs_unassigned'] = (int)$db->query("
                    SELECT COUNT(*) c
                    FROM abstrak a
                    WHERE a.event_id = ?
                      AND NOT EXISTS (SELECT 1 FROM review r WHERE r.id_abstrak = a.id_abstrak)
                ", [$eventId])->getRow('c');
            } else {
                $e['abs_unassigned'] = 0;
            }

            // full paper belum ditugaskan
            $e['fp_unassigned'] = 0;
            if ($fpCarrierTable) {
                $eventCol = $this->columnExists($fpCarrierTable,'event_id') ? 'event_id' : null;
                $pathCol  = $this->columnExists($fpCarrierTable,'full_paper_path') ? 'full_paper_path' : null;

                if ($eventCol && $pathCol) {
                    if ($fpReviewTable) {
                        // tentukan kolom relasi di tabel review FP
                        $relCol = null;
                        foreach (['submission_id','id_submission','fullpaper_id','id_fullpaper','abstrak_id','id_abstrak'] as $cand) {
                            if ($this->columnExists($fpReviewTable, $cand)) { $relCol = $cand; break; }
                        }
                        if ($relCol) {
                            $e['fp_unassigned'] = (int)$db->query("
                                SELECT COUNT(*) c
                                FROM {$fpCarrierTable} s
                                WHERE s.{$eventCol} = ?
                                  AND s.{$pathCol} IS NOT NULL
                                  AND s.{$pathCol} <> ''
                                  AND NOT EXISTS (SELECT 1 FROM {$fpReviewTable} r WHERE r.{$relCol} = s.{$fpCarrierPK})
                            ", [$eventId])->getRow('c');
                        }
                    } else {
                        // belum ada tabel review FP → anggap semua yang sudah upload belum diassign
                        $e['fp_unassigned'] = (int)$db->table($fpCarrierTable)
                            ->where($eventCol, $eventId)
                            ->where("$pathCol IS NOT NULL", null, false)
                            ->where("$pathCol <>", '')
                            ->countAllResults();
                    }
                }
            }

            if (($e['event_date'] ?? '') < $today) $berakhir[] = $e;
            else $aktif[] = $e;
        }
        unset($e);

        return view('role/admin/kelola_paper/event', [
            'title'    => 'Kelola Paper',
            'aktif'    => $aktif,
            'berakhir' => $berakhir,
        ]);
    }

    /** ====== HALAMAN LIST PRESENTER PER EVENT ====== */
    public function presenters(int $eventId)
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

        // deteksi keberadaan tabel dokumen; kalau ada, kita query langsung
        $dokTblExists = $db->tableExists('dokumen');
        $dokModel     = (!$dokTblExists && class_exists(\App\Models\DokumenModel::class))
                        ? new \App\Models\DokumenModel()
                        : null;

        foreach ($rows as &$r) {
            $userId = (int)($r['id_user'] ?? 0);
            $absId  = (int)($r['id_abstrak'] ?? 0);

            $latest = $fpModel->getLatestRowByUserEvent($userId, $eventId);

            $r['has_full']           = $latest && !empty($latest['full_paper_path']);
            $r['full_paper_name']    = $latest['full_paper_path'] ?? null;
            $r['full_paper_status']  = $latest['full_paper_status'] ?? null;
            $r['full_row_id']        = $latest ? ($latest[$fpModel->primaryKey] ?? null) : null;

            // ringkasan review abstrak
            $absReview = $this->getAbstractReviewSummary($absId);
            $r['abs_summary']         = $absReview['summary'];
            $r['abs_assigned_count']  = $absReview['assigned_count'];
            $r['abs_complete_count']  = $absReview['complete_count'];
            $r['abs_missing']         = max(0, $this->requiredReviewers - (int)$absReview['assigned_count']);
            $r['abs_decisions']       = $absReview['decisions'];

            // ringkasan review fullpaper
            $fpReview = $this->getFullPaperReviewSummary($fpModel, $latest);
            $r['fp_summary']          = $fpReview['summary'];
            $r['fp_assigned_count']   = $fpReview['assigned_count'];
            $r['fp_complete_count']   = $fpReview['complete_count'];
            $r['fp_missing']          = max(0, $this->requiredReviewers - (int)$fpReview['assigned_count']);
            $r['fp_decisions']        = $fpReview['decisions'];

            // === VALIDASI LoA berdasar dokumen tipe 'loa' ===
            if ($dokTblExists) {
                $r['loa_exists'] = (bool) $db->table('dokumen')
                    ->where('id_user', $userId)
                    ->where('event_id', $eventId)
                    ->where('tipe', 'loa')
                    ->countAllResults();
            } elseif ($dokModel && method_exists($dokModel, 'hasUserDocument')) {
                $r['loa_exists'] = (bool) $dokModel->hasUserDocument($userId, $eventId, 'loa');
            } else {
                $r['loa_exists'] = false;
            }

            // URLs
            $r['view_abs_url']   = site_url('admin/kelola-paper/abstract/'.$absId);
            $r['view_fp_url']    = site_url('admin/kelola-paper/full/'.$userId.'/'.$eventId);
            $r['assign_abs_url'] = site_url('admin/abstrak/detail/'.$absId);
            $r['assign_fp_url']  = $r['full_row_id']
                                 ? site_url('admin/fullpaper/detail/'.$r['full_row_id'])
                                 : site_url('admin/fullpaper');
            // arahkan ke halaman dokumen dengan filter event+user (biar bisa upload/generate kalau belum ada)
            $r['loa_url']        = site_url('admin/dokumen?event_id='.$eventId.'&user_id='.$userId.'&tipe=loa');
        }
        unset($r);

        return view('role/admin/kelola_paper/presenter', [
            'eventId'    => $eventId,
            'presenters' => $rows,
        ]);
    }

    /** ====== STREAM FILES ====== */
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

    /* ====================== Helpers ====================== */

    private function columnExists(string $table, string $column): bool
    {
        $db = \Config\Database::connect();
        if (!$db->tableExists($table)) return false;
        foreach ($db->getFieldData($table) as $f) {
            if (strcasecmp($f->name, $column) === 0) return true;
        }
        return false;
    }

    private function getAbstractReviewSummary(int $idAbstrak): array
    {
        $need = $this->requiredReviewers;
        $out  = ['assigned_count'=>0,'complete_count'=>0,'decisions'=>[],'summary'=>'Belum ada data review'];

        if ($idAbstrak <= 0) return $out;
        $db = \Config\Database::connect();
        if (!$db->tableExists('review')) return $out;

        $rows = $db->table('review r')
            ->select('r.*, u.nama_lengkap AS reviewer_name')
            ->join('users u','u.id_user=r.id_reviewer','left')
            ->where('r.id_abstrak',$idAbstrak)
            ->get()->getResultArray();

        if (!$rows) return $out;

        $assigned=0; $complete=0; $decisions=[];
        foreach ($rows as $r) {
            $assigned++;
            $decision = $r['status'] ?? null;
            if ($decision && in_array(strtolower($decision), ['diterima','revisi','ditolak','accepted','revision','rejected'], true)) {
                $complete++;
            }
            $decisions[] = [
                'reviewer_name' => $r['reviewer_name'] ?? ('Reviewer #'.($r['id_reviewer'] ?? '-')),
                'decision'      => $decision ?: '—',
                'updated_at'    => $r['updated_at'] ?? ($r['created_at'] ?? null),
            ];
        }

        $out['assigned_count'] = $assigned;
        $out['complete_count'] = $complete;
        $out['decisions']      = $decisions;
        $out['summary']        = sprintf('%d/%d reviewer ditugaskan • %d selesai', $assigned, $need, $complete);
        return $out;
    }

    private function getFullPaperReviewSummary(FullPaperModel $fpModel, ?array $latestRow): array
    {
        $need = $this->requiredReviewers;
        $out  = ['assigned_count'=>0,'complete_count'=>0,'decisions'=>[],'summary'=>'Belum ada data review'];
        if (!$latestRow) return $out;

        $db = \Config\Database::connect();

        $table = null;
        foreach (['fullpaper_reviews','fullpaper_review','fp_review','review_fullpaper'] as $cand) {
            if ($db->tableExists($cand)) { $table = $cand; break; }
        }
        if (!$table) return $out;

        // id submission row
        $rowId = null;
        foreach ([$fpModel->primaryKey, 'submission_id', 'id_submission', 'id', 'id_abstrak'] as $cand) {
            if (array_key_exists($cand, $latestRow) && $latestRow[$cand]) { $rowId = (int)$latestRow[$cand]; break; }
        }
        if (!$rowId) return $out;

        // kolom relasi
        $relCol = null;
        foreach (['submission_id','id_submission','fullpaper_id','id_fullpaper','abstrak_id','id_abstrak'] as $cand) {
            if ($this->columnExists($table, $cand)) { $relCol = $cand; break; }
        }
        if (!$relCol) return $out;

        $statusCol   = $this->columnExists($table,'status')      ? 'status'
                     : ($this->columnExists($table,'decision')    ? 'decision' : null);
        $reviewerCol = $this->columnExists($table,'id_reviewer') ? 'id_reviewer'
                     : ($this->columnExists($table,'reviewer_id') ? 'reviewer_id' : null);

        $builder = $db->table($table.' r');
        if ($reviewerCol) {
            $builder->select('r.*, u.nama_lengkap AS reviewer_name')
                    ->join('users u','u.id_user = r.'.$reviewerCol,'left');
        } else {
            $builder->select('r.*');
        }

        $rows = $builder->where('r.'.$relCol, $rowId)->get()->getResultArray();
        if (!$rows) return $out;

        $assigned=0; $complete=0; $decisions=[];
        foreach ($rows as $r) {
            $assigned++;
            $decision = $statusCol ? ($r[$statusCol] ?? null) : null;
            if ($decision && in_array(strtoupper($decision), ['ACCEPTED','REVISION','REJECTED'], true)) $complete++;

            $decisions[] = [
                'reviewer_name' => $r['reviewer_name'] ?? ($reviewerCol && !empty($r[$reviewerCol]) ? 'Reviewer #'.$r[$reviewerCol] : 'Reviewer'),
                'decision'      => $decision ?: '—',
                'updated_at'    => $r['updated_at'] ?? ($r['created_at'] ?? null),
            ];
        }

        $out['assigned_count'] = $assigned;
        $out['complete_count'] = $complete;
        $out['decisions']      = $decisions;
        $out['summary']        = sprintf('%d/%d reviewer ditugaskan • %d selesai', $assigned, $need, $complete);
        return $out;
    }

    private function streamFile(string $path, string $downloadName)
    {
        $mime = $this->detectMime($path) ?: 'application/pdf';
        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Content-Disposition', 'inline; filename="'.basename($downloadName).'"')
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
}
