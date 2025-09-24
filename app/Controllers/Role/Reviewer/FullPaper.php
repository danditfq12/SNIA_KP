<?php
namespace App\Controllers\Role\Reviewer;

use App\Controllers\BaseController;

class FullPaper extends BaseController
{
    protected $db;
    protected $uploadBase;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        // di DB simpan path relatif, saat download gabungkan dengan WRITEPATH
        $this->uploadBase = WRITEPATH;
    }

    /** ======================= Helpers ======================= */

    private function submissionTable(): string
    {
        if ($this->db->tableExists('submissions')) return 'submissions';
        if ($this->db->tableExists('abstrak'))     return 'abstrak';
        throw new \RuntimeException('Tabel submissions/abstrak tidak ditemukan.');
    }

    private function reviewTable(): string
    {
        if ($this->db->tableExists('review')) return 'review';
        throw new \RuntimeException('Tabel review tidak ditemukan.');
    }

    private function fields(string $table): array
    {
        return $this->db->getFieldNames($table) ?: [];
    }

    private function colExists(string $table, string $col): bool
    {
        return in_array($col, $this->fields($table), true);
    }

    private function primaryKey(string $table): string
    {
        $row = $this->db->query("
            SELECT kcu.column_name
            FROM information_schema.table_constraints tc
            JOIN information_schema.key_column_usage kcu
              ON tc.constraint_name = kcu.constraint_name
             AND tc.table_schema = kcu.table_schema
            WHERE tc.constraint_type = 'PRIMARY KEY'
              AND tc.table_name = ?
              AND tc.table_schema = current_schema()
            LIMIT 1
        ", [$table])->getFirstRow('array');

        if (!empty($row['column_name'])) return $row['column_name'];
        foreach (['id','id_submission','id_abstrak'] as $c) {
            if ($this->colExists($table, $c)) return $c;
        }
        throw new \RuntimeException("Tidak bisa mendeteksi PK: {$table}");
    }

    private function submissionCols(string $table): array
    {
        $pk = $this->primaryKey($table);

        $titleCand = ['title','judul','judul_paper','judul_penelitian','judul_abstrak','nama'];
        $titleCol = $pk;
        foreach ($titleCand as $c) if ($this->colExists($table,$c)) { $titleCol = $c; break; }

        $eventCol = null;
        foreach (['event_id','id_event','events_id'] as $c) if ($this->colExists($table,$c)) { $eventCol = $c; break; }

        return [
            'pk'    => $pk,
            'title' => $titleCol,
            'event' => $eventCol,
            'status'=> $this->colExists($table,'full_paper_status')       ? 'full_paper_status'       : null,
            'path'  => $this->colExists($table,'full_paper_path')         ? 'full_paper_path'         : null,
            'ts'    => $this->colExists($table,'full_paper_uploaded_at')  ? 'full_paper_uploaded_at'  : null,
        ];
    }

    private function reviewCols(string $table): array
    {
        $pk = $this->primaryKey($table);

        $subFk = null;
        foreach (['submission_id','id_submission','id_abstrak'] as $c) if ($this->colExists($table,$c)) { $subFk = $c; break; }
        if (!$subFk) throw new \RuntimeException('Kolom foreign key ke submission/abstrak pada tabel review tidak ditemukan.');

        $revFk = null;
        foreach (['reviewer_id','id_reviewer','user_id'] as $c) if ($this->colExists($table,$c)) { $revFk = $c; break; }
        if (!$revFk) throw new \RuntimeException('Kolom foreign key reviewer pada tabel review tidak ditemukan.');

        $decision = $this->colExists($table,'keputusan') ? 'keputusan' : ($this->colExists($table,'decision') ? 'decision' : null);
        $comment  = $this->colExists($table,'komentar')  ? 'komentar'  : ($this->colExists($table,'comment')  ? 'comment'  : null);
        $typeCol  = $this->colExists($table,'type') ? 'type' : null;
        $ts       = $this->colExists($table,'tanggal_review') ? 'tanggal_review' : ($this->colExists($table,'created_at') ? 'created_at' : null);

        return [
            'pk'        => $pk,
            'submission'=> $subFk,
            'reviewer'  => $revFk,
            'decision'  => $decision,
            'comment'   => $comment,
            'type'      => $typeCol,
            'ts'        => $ts,
        ];
    }

    /** Deteksi ID user (Myth/Auth & key umum) */
    private function me(): int
    {
        if (function_exists('user') && user()) return (int) user()->id;
        $s = session();

        foreach (['user_id','id_user','id','uid'] as $k) {
            $v = $s->get($k);
            if (!empty($v)) return (int) $v;
        }
        $u = $s->get('user');
        if (is_array($u)) {
            foreach (['id','user_id','id_user','uid'] as $k) {
                if (!empty($u[$k])) return (int) $u[$k];
            }
        }
        return 0;
    }

    /** ======================= Pages ======================= */

    // GET /reviewer/fullpaper
    public function index()
    {
        $me = $this->me();
        if (!$me) {
            return redirect()->to(site_url('reviewer/dashboard'))->with('error','Sesi reviewer tidak valid.');
        }

        $subTable = $this->submissionTable();
        $revTable = $this->reviewTable();
        $S = $this->submissionCols($subTable);
        $R = $this->reviewCols($revTable);

        $builder = $this->db->table($revTable.' r')
            ->select("
                r.{$R['pk']} AS review_id,
                r.{$R['submission']} AS submission_id,
                ".($R['decision'] ? "r.{$R['decision']} AS decision," : "NULL AS decision,")."
                ".($R['ts'] ? "r.{$R['ts']} AS reviewed_at," : "NULL AS reviewed_at,")."
                s.{$S['pk']} AS id,
                s.{$S['title']} AS title
                ".($S['event'] ? ", s.{$S['event']} AS event_id" : "")."
                ".($S['status'] ? ", s.{$S['status']} AS full_paper_status" : "")."
                ".($S['ts'] ? ", s.{$S['ts']} AS full_paper_uploaded_at" : "")."
                ".($S['path'] ? ", s.{$S['path']} AS full_paper_path" : "")."
            ")
            ->join($subTable.' s', "s.{$S['pk']} = r.{$R['submission']}", 'inner')
            ->where("r.{$R['reviewer']}", $me);

        if ($R['type']) {
            $builder->where("r.{$R['type']}", 'fullpaper');
        }

        // PRIORITAS: yang belum diputus (NULL) di atas
        if ($R['decision']) {
            $builder->orderBy("r.{$R['decision']} IS NULL", 'DESC', false); // escape=false untuk ekspresi SQL
        } else {
            $builder->orderBy("r.{$R['pk']}", 'ASC', false);
        }
        $builder->orderBy("s.{$S['pk']}", 'DESC', false);

        $rows = $builder->get()->getResultArray();

        return view('role/reviewer/fullpaper/index', [
            'rows'  => $rows,
            'title' => 'Penugasan Full Paper',
        ]);
    }

    // GET /reviewer/fullpaper/{submissionId}
    public function detail($submissionId)
    {
        $me = $this->me();
        if (!$me) {
            return redirect()->to(site_url('reviewer/dashboard'))->with('error','Sesi reviewer tidak valid.');
        }

        $submissionId = (int)$submissionId;

        $subTable = $this->submissionTable();
        $revTable = $this->reviewTable();
        $S = $this->submissionCols($subTable);
        $R = $this->reviewCols($revTable);

        $submission = $this->db->table($subTable)->where($S['pk'], $submissionId)->get()->getRowArray();
        if (!$submission) return redirect()->back()->with('error','Submission tidak ditemukan');

        $rev = $this->db->table($revTable)
            ->where($R['submission'], $submissionId)
            ->where($R['reviewer'], $me);
        if ($R['type']) $rev->where($R['type'], 'fullpaper');

        $review = $rev->get()->getRowArray();
        if (!$review) {
            $ins = [
                $R['submission'] => $submissionId,
                $R['reviewer']   => $me,
            ];
            if ($R['type']) $ins[$R['type']] = 'fullpaper';
            if ($R['ts'])   $ins[$R['ts']]   = date('Y-m-d H:i:s');

            $this->db->table($revTable)->insert($ins);

            $rev2 = $this->db->table($revTable)
                ->where($R['submission'], $submissionId)
                ->where($R['reviewer'], $me);
            if ($R['type']) $rev2->where($R['type'], 'fullpaper');
            $review = $rev2->get()->getRowArray();
        }

        $submissionView = [
            'id'                    => $submission[$S['pk']],
            'title'                 => $submission[$S['title']] ?? '—',
            'event_id'              => $S['event'] ? ($submission[$S['event']] ?? null) : null,
            'full_paper_status'     => $S['status'] ? ($submission[$S['status']] ?? 'NONE') : 'NONE',
            'full_paper_path'       => $S['path']   ? ($submission[$S['path']]   ?? null)   : null,
            'full_paper_uploaded_at'=> $S['ts']     ? ($submission[$S['ts']]     ?? null)   : null,
        ];

        $reviewView = [
            'id'         => $review[$R['pk']] ?? null,
            'decision'   => ($R['decision'] && isset($review[$R['decision']])) ? $review[$R['decision']] : null,
            'comment'    => ($R['comment']  && isset($review[$R['comment']]))  ? $review[$R['comment']]  : null,
            'reviewed_at'=> ($R['ts'] && isset($review[$R['ts']])) ? $review[$R['ts']] : null,
        ];

        return view('role/reviewer/fullpaper/detail', [
            'submission' => $submissionView,
            'review'     => $reviewView,
            'title'      => 'Review Full Paper',
        ]);
    }

    // POST /reviewer/fullpaper/review/{submissionId}
    public function submit($submissionId)
    {
        $me = $this->me();
        if (!$me) {
            return redirect()->to(site_url('reviewer/dashboard'))->with('error','Sesi reviewer tidak valid.');
        }

        $submissionId = (int)$submissionId;
        $decision = strtoupper(trim((string)$this->request->getPost('keputusan')));
        $comment  = trim((string)$this->request->getPost('komentar'));

        $allowed = ['ACCEPTED','REVISION','REJECTED'];
        if (!in_array($decision, $allowed, true)) {
            return redirect()->back()->with('error','Keputusan tidak valid.');
        }

        $subTable = $this->submissionTable();
        $revTable = $this->reviewTable();
        $S = $this->submissionCols($subTable);
        $R = $this->reviewCols($revTable);

        $submission = $this->db->table($subTable)->where($S['pk'], $submissionId)->get()->getRowArray();
        if (!$submission) return redirect()->back()->with('error','Submission tidak ditemukan');

        $revQ = $this->db->table($revTable)
                 ->where($R['submission'], $submissionId)
                 ->where($R['reviewer'], $me);
        if ($R['type']) $revQ->where($R['type'], 'fullpaper');

        $exists = $revQ->get()->getRowArray();

        $payload = [];
        if ($R['decision']) $payload[$R['decision']] = $decision;
        if ($R['comment'])  $payload[$R['comment']]  = $comment;
        if ($R['ts'])       $payload[$R['ts']]       = date('Y-m-d H:i:s');

        if ($exists) {
            $revUpd = $this->db->table($revTable)
                ->where($R['submission'], $submissionId)
                ->where($R['reviewer'], $me);
            if ($R['type']) $revUpd->where($R['type'], 'fullpaper');
            $revUpd->update($payload);
        } else {
            $payload[$R['submission']] = $submissionId;
            $payload[$R['reviewer']]   = $me;
            if ($R['type']) $payload[$R['type']] = 'fullpaper';
            $this->db->table($revTable)->insert($payload);
        }

        // sinkron status di submission (opsional)
        if ($S['status']) {
            $update = [$S['status'] => $decision];
            if ($this->colExists($subTable, 'eligible_to_pay')) {
                $update['eligible_to_pay'] = ($decision === 'ACCEPTED');
            }
            $this->db->table($subTable)->where($S['pk'], $submissionId)->update($update);
        }

        return redirect()->to(site_url('reviewer/fullpaper/'.$submissionId))
                         ->with('success','Keputusan review tersimpan.');
    }

    // GET /reviewer/fullpaper/download/{submissionId}
    public function download($submissionId)
    {
        $submissionId = (int)$submissionId;

        $subTable = $this->submissionTable();
        $S = $this->submissionCols($subTable);

        $row = $this->db->table($subTable)->where($S['pk'], $submissionId)->get()->getRowArray();
        if (!$row) return redirect()->back()->with('error','Submission tidak ditemukan');

        if (!$S['path'] || empty($row[$S['path']])) {
            return redirect()->back()->with('error','File full paper belum tersedia.');
        }

        $relative = $row[$S['path']];
        $absolute = rtrim($this->uploadBase, '/\\') . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative);

        if (!is_file($absolute)) {
            return redirect()->back()->with('error','File tidak ditemukan di server.');
        }

        return $this->response->download($absolute, null)->setFileName(basename($absolute));
    }
}