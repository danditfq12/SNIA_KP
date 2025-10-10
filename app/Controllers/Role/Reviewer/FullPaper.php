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
        $this->uploadBase = WRITEPATH; // base path penyimpanan file
    }

    /* ======================= Helpers umum ======================= */

    private function requireReviewer(): bool
    {
        $id = (int) (session('id_user') ?? 0);
        return $id && session('role') === 'reviewer';
    }

    private function me(): int
    {
        if (function_exists('user') && user()) return (int) user()->id;
        return (int) (session('id_user') ?? 0);
    }

    private function submissionTable(): string
    {
        if ($this->db->tableExists('submissions')) return 'submissions';
        if ($this->db->tableExists('abstrak'))     return 'abstrak'; // fallback
        throw new \RuntimeException('Tabel submissions/abstrak tidak ditemukan.');
    }

    private function fields(string $table): array
    {
        return $this->db->getFieldNames($table) ?: [];
    }
    private function colExists(string $t, string $c): bool { return in_array($c, $this->fields($t), true); }

    private function primaryKey(string $table): string
    {
        foreach (['id','id_submission','id_abstrak'] as $c) if ($this->colExists($table,$c)) return $c;
        return 'id';
    }

    private function submissionCols(string $table): array
    {
        $pk = $this->primaryKey($table);

        $title = $pk;
        foreach (['title','judul','judul_paper','judul_penelitian','judul_abstrak','nama'] as $c)
            if ($this->colExists($table,$c)) { $title = $c; break; }

        $event = null;
        foreach (['event_id','id_event','events_id'] as $c)
            if ($this->colExists($table,$c)) { $event = $c; break; }

        return [
            'pk'    => $pk,
            'title' => $title,
            'event' => $event,
            'user'  => $this->colExists($table,'id_user') ? 'id_user'
                      : ($this->colExists($table,'user_id') ? 'user_id' : null),
            'status'=> $this->colExists($table,'full_paper_status')      ? 'full_paper_status'      : null,
            'path'  => $this->colExists($table,'full_paper_path')        ? 'full_paper_path'        : null,
            'ts'    => $this->colExists($table,'full_paper_uploaded_at') ? 'full_paper_uploaded_at' : null,
        ];
    }

    private function pivotTable(): string { return 'fullpaper_reviewers'; }
    private function reviewsTable(): string { return 'fullpaper_reviews'; }

    private function pivotCols(): array
    {
        $t = $this->pivotTable();
        $f = array_flip($this->db->getFieldNames($t) ?: []);
        $pick = function(array $cands) use ($f) { foreach ($cands as $c) if (isset($f[$c])) return $c; return null; };
        return [
            'pk'        => $pick(['id']),
            'submission'=> $pick(['submission_id','id_submission']),
            'reviewer'  => $pick(['reviewer_id','id_reviewer']),
            'status'    => $pick(['assignment_status','status_tugas','tugas_status','konfirmasi_status']),
            'reason'    => $pick(['decline_reason','alasan','alasan_tolak']),
            'acc'       => $pick(['accepted_at','confirmed_at','konfirmasi_at']),
            'dec'       => $pick(['declined_at','rejected_at']),
            'order'     => $pick(['order_no','urutan','posisi']), // opsional: penomoran reviewer 1/2/3
        ];
    }

    private function normalizeAssign(?string $st): string
    {
        $s = strtolower((string)$st);
        if (in_array($s, ['accept','accepted','ok','yes'], true)) return 'accepted';
        if (in_array($s, ['decline','declined','no','rejected_task'], true)) return 'declined';
        return $s ?: 'pending';
    }

    private function normDecision(?string $v): string
    {
        $k = strtolower((string)$v);
        if (in_array($k, ['accepted','diterima','accept','ok','yes'], true)) return 'diterima';
        if (in_array($k, ['revision','revisi'], true))                         return 'revisi';
        if (in_array($k, ['rejected','ditolak','reject','no'], true))          return 'ditolak';
        return 'menunggu';
    }

    /** status assignment saya pada submission tertentu */
    private function getTaskStatus(int $submissionId, int $reviewerId): array
    {
        $t = $this->pivotTable(); $P = $this->pivotCols();
        if (!$this->db->tableExists($t) || !$P['submission'] || !$P['reviewer']) {
            return ['status'=>'pending','reason'=>null];
        }

        $sel = "{$P['pk']} AS id, {$P['submission']} AS sid, {$P['reviewer']} AS rid";
        if ($P['status']) $sel .= ", {$P['status']} AS st";
        if ($P['reason']) $sel .= ", {$P['reason']} AS rsn";

        $row = $this->db->table($t)->select($sel)
            ->where($P['submission'], $submissionId)
            ->where($P['reviewer'],  $reviewerId)
            ->orderBy($P['pk'] ?? 'id', 'DESC')
            ->get()->getRowArray();

        return [
            'status' => $this->normalizeAssign($row['st'] ?? 'pending'),
            'reason' => $row['rsn'] ?? null,
        ];
    }

    /** review terakhir saya pada ronde upload saat ini */
    private function myLatestReview(int $submissionId, int $me, ?string $uploadedAt): ?array
    {
        if (!$this->db->tableExists($this->reviewsTable())) return null;
        $qb = $this->db->table($this->reviewsTable())
            ->where('submission_id', $submissionId)
            ->where('reviewer_id',   $me);
        if ($uploadedAt) $qb->where('tanggal_review >=', $uploadedAt);
        return $qb->orderBy('tanggal_review','DESC')->orderBy('id','DESC')->get()->getRowArray() ?: null;
    }

    /** ambil semua reviewer yang ditugaskan (nama + status tugas) untuk 1 submission */
    private function assignedReviewers(int $submissionId): array
    {
        $pvt = $this->pivotTable(); $P = $this->pivotCols();
        if (!$this->db->tableExists($pvt) || !$P['submission'] || !$P['reviewer']) return [];

        $qb = $this->db->table("$pvt p")
            ->select("p.{$P['reviewer']} AS reviewer_id")
            ->where("p.{$P['submission']}", $submissionId);

        if ($P['status']) $qb->select("p.{$P['status']} AS tugas_status");
        if ($P['order'])  $qb->select("p.{$P['order']} AS order_no");
        if ($this->db->tableExists('users')) {
            $qb->join('users u', "u.id_user = p.{$P['reviewer']}", 'left')
               ->select('u.nama_lengkap');
        }

        $rows = $qb->orderBy($P['order'] ?: 'p.'.$P['pk'], 'ASC')->get()->getResultArray();
        $out  = [];
        foreach ($rows as $i => $r) {
            $out[] = [
                'order'        => (int)($r['order_no'] ?? ($i+1)),
                'reviewer_id'  => (int)$r['reviewer_id'],
                'nama'         => $r['nama_lengkap'] ?? ('Reviewer #'.($i+1)),
                'tugas_status' => $this->normalizeAssign($r['tugas_status'] ?? 'pending'),
            ];
        }
        return $out;
    }

    /** keputusan terbaru per reviewer accepted untuk ronde upload sekarang */
    private function latestReviewerDecisions(int $submissionId): array
    {
        if (!$this->db->tableExists($this->reviewsTable())) return [];

        $subTable = $this->submissionTable();
        $S        = $this->submissionCols($subTable);
        $uploadedAt = null;
        if ($S['ts']) {
            $row = $this->db->table($subTable)->select($S['ts'].' AS ts')->where($S['pk'],$submissionId)->get()->getRowArray();
            $uploadedAt = $row ? $row['ts'] : null;
        }

        $pvt = $this->pivotTable(); $P = $this->pivotCols();
        $rids = [];
        if ($this->db->tableExists($pvt)) {
            $acc = $this->db->table($pvt)
                ->select($P['reviewer'].' AS rid')
                ->where($P['submission'], $submissionId);
            if ($P['status']) $acc->whereIn('LOWER('.$P['status'].')', ['accepted','accept']);
            $rows = $acc->get()->getResultArray();
            $rids = array_values(array_unique(array_map(fn($r)=>(int)$r['rid'], $rows)));
        }
        if (!$rids) return [];

        $b = $this->db->table($this->reviewsTable())
            ->select('reviewer_id, keputusan, komentar, tanggal_review')
            ->where('submission_id', $submissionId)
            ->whereIn('reviewer_id', $rids);
        if ($uploadedAt) $b->where('tanggal_review >=', $uploadedAt);

        $rows = $b->orderBy('reviewer_id','ASC')->orderBy('tanggal_review','DESC')->get()->getResultArray();
        $latest = [];
        foreach ($rows as $r) {
            $rid = (int)$r['reviewer_id'];
            if (!isset($latest[$rid])) $latest[$rid] = $r;
        }
        return $latest;
    }

    private function recalcAggregateStatus(int $submissionId): string
    {
        $latest = $this->latestReviewerDecisions($submissionId);
        $acc=$rev=$rej=0;
        foreach ($latest as $r) {
            $k = strtolower((string)($r['keputusan'] ?? ''));
            if (in_array($k, ['accepted','diterima','accept'])) $acc++;
            elseif (in_array($k, ['revision','revisi'])) $rev++;
            elseif (in_array($k, ['rejected','ditolak','reject'])) $rej++;
        }

        $final = 'UPLOADED';
        if     ($rej >= 2) $final = 'REJECTED';
        elseif ($acc >= 2) $final = 'ACCEPTED';
        elseif ($rev >= 1) $final = 'REVISION';

        $subTable = $this->submissionTable(); $S = $this->submissionCols($subTable);
        if ($S['status']) {
            $update = [$S['status'] => $final];
            if ($this->colExists($subTable, 'eligible_to_pay')) {
                $update['eligible_to_pay'] = ($final === 'ACCEPTED');
            }
            $this->db->table($subTable)->where($S['pk'], $submissionId)->update($update);
        }
        return $final;
    }

    /* ======================= Join ke Abstrak untuk kategori & author ======================= */

    private function applyAbstractJoinsForCategory(\CodeIgniter\Database\BaseBuilder $builder, string $subTable, array $S): void
    {
        if (!$S['event'] || !$S['user']) return;

        if ($this->db->tableExists('abstrak')) {
            $on = "a.event_id = s.{$S['event']} AND a.id_user = s.{$S['user']}";
            $builder->join('abstrak a', $on, 'left');

            if ($this->db->tableExists('kategori_abstrak')) {
                $af = array_flip($this->db->getFieldNames('abstrak') ?: []);
                $colKat = isset($af['id_kategori']) ? 'id_kategori' : (isset($af['kategori_id']) ? 'kategori_id' : null);
                if ($colKat) {
                    $builder->join('kategori_abstrak ka', "ka.id_kategori = a.$colKat", 'left')
                            ->select('ka.nama_kategori');
                }
            }

            if ($this->db->tableExists('users')) {
                $builder->join('users u', "u.id_user = a.id_user", 'left')
                        ->select('u.nama_lengkap');
            }
        }

        if ($this->db->tableExists('events')) {
            $ef = array_flip($this->db->getFieldNames('events') ?: []);
            $eid = isset($ef['id']) ? 'id' : (isset($ef['event_id']) ? 'event_id' : null);
            $etitle = isset($ef['title']) ? 'title' : (isset($ef['nama']) ? 'nama' : null);
            if ($eid && $etitle) {
                $builder->join('events e', "e.$eid = s.{$S['event']}", 'left')
                        ->select("e.$eid AS event_id, e.$etitle AS event_title");
            }
        }
    }

    /* ======================= Pages ======================= */

    public function index()
    {
        if (!$this->requireReviewer()) return redirect()->to(site_url('auth/login'));
        $me   = $this->me();

        $pvt  = $this->pivotTable(); $P = $this->pivotCols();
        $sub  = $this->submissionTable(); $S = $this->submissionCols($sub);

        if (!$this->db->tableExists($pvt)) {
            return view('role/reviewer/fullpaper/index', [
                'title'        => 'Tugas Full Paper',
                'rows'         => [],
                'eventOptions' => [],
            ]);
        }

        $builder = $this->db->table("$pvt p")
            ->join("$sub s", "s.{$S['pk']} = p.{$P['submission']}", 'inner')
            ->select("p.{$P['submission']} AS id, s.{$S['title']} AS title");

        if ($S['status']) $builder->select("s.{$S['status']} AS full_paper_status");
        if ($S['ts'])     $builder->select("s.{$S['ts']} AS tanggal_upload");
        if ($S['event'])  $builder->select("s.{$S['event']} AS s_event_id");
        if ($S['user'])   $builder->select("s.{$S['user']}  AS s_user_id");

        $this->applyAbstractJoinsForCategory($builder, $sub, $S);

        // tugas saya & accepted assignment
        $builder->where("p.{$P['reviewer']}", $me);
        if ($P['status']) {
            $builder->groupStart()
                ->where("LOWER(p.{$P['status']})", 'accepted')
                ->orWhere("LOWER(p.{$P['status']})", 'accept')
            ->groupEnd();
        }
        $builder->orderBy("s.{$S['pk']}", 'DESC', false);

        $rows = $builder->get()->getResultArray();

        // filter: HILANGKAN dari index kalau reviewer (kamu) sudah ACC (accepted/diterima)
        $filtered = [];
        $eventOptions = [];

        foreach ($rows as $r) {
            $sid = (int)($r['id'] ?? 0);

            // versi upload saat ini
            $uploadedAt = null;
            if ($S['ts']) {
                $ts = $this->db->table($sub)->select($S['ts'].' AS ts')
                        ->where($S['pk'],$sid)->get()->getRowArray();
                $uploadedAt = $ts['ts'] ?? null;
            }

            // review saya
            $my = $this->myLatestReview($sid, $me, $uploadedAt);
            $revNormalized = $this->normDecision($my['keputusan'] ?? null);

            // SKIP dari daftar jika SUDAH ACC (diterima) oleh saya → pindah ke Riwayat
            if ($revNormalized === 'diterima') {
                continue;
            }

            // siapkan eventOptions
            $eid = (int)($r['event_id'] ?? $r['s_event_id'] ?? 0);
            if ($eid && !isset($eventOptions[$eid])) {
                $eventOptions[$eid] = $r['event_title'] ?? ('Event #'.$eid);
            }

            // map status untuk tampilan
            $r['review_status']  = $this->normDecision($my['keputusan'] ?? null) ?: 'menunggu';
            $r['event_id']       = $eid;
            $r['event_title']    = $r['event_title'] ?? ($eventOptions[$eid] ?? '-');
            $r['nama_lengkap']   = $r['nama_lengkap'] ?? '-';
            $r['nama_kategori']  = $r['nama_kategori'] ?? '-';

            $filtered[] = $r;
        }
        ksort($eventOptions);

        return view('role/reviewer/fullpaper/index', [
            'title'        => 'Tugas Full Paper',
            'rows'         => $filtered,
            'eventOptions' => $eventOptions,
        ]);
    }

    public function detail($submissionId)
    {
        if (!$this->requireReviewer()) return redirect()->to(site_url('auth/login'));
        $me          = $this->me();
        $submissionId= (int)$submissionId;

        $subTable = $this->submissionTable(); $S = $this->submissionCols($subTable);

        $builder = $this->db->table("$subTable s")
            ->where("s.{$S['pk']}", $submissionId)
            ->select("s.{$S['pk']} AS id, s.{$S['title']} AS title");

        if ($S['status']) $builder->select("s.{$S['status']} AS full_paper_status");
        if ($S['ts'])     $builder->select("s.{$S['ts']} AS full_paper_uploaded_at");
        if ($S['path'])   $builder->select("s.{$S['path']} AS full_paper_path");
        if ($S['event'])  $builder->select("s.{$S['event']} AS s_event_id");
        if ($S['user'])   $builder->select("s.{$S['user']}  AS s_user_id");

        $this->applyAbstractJoinsForCategory($builder, $subTable, $S);

        $submission = $builder->get()->getRowArray();
        if (!$submission) return redirect()->back()->with('error','Submission tidak ditemukan');

        // pastikan ditugaskan
        $pvt = $this->pivotTable(); $P = $this->pivotCols();
        if (!$this->db->tableExists($pvt)) {
            return redirect()->to(site_url('reviewer/fullpaper'))->with('error','Penugasan tidak ditemukan.');
        }
        $assigned = $this->db->table($pvt)
            ->where($P['submission'], $submissionId)
            ->where($P['reviewer'],  $me)->countAllResults() > 0;
        if (!$assigned) {
            return redirect()->to(site_url('reviewer/fullpaper'))->with('error','Anda tidak ditugaskan untuk naskah ini.');
        }

        // status tugas saya
        $task = $this->getTaskStatus($submissionId, $me);

        // review saya (untuk ronde upload sekarang)
        $uploadedAt = $submission['full_paper_uploaded_at'] ?? null;
        $myReview   = $this->myLatestReview($submissionId, $me, $uploadedAt);

        // reviewer lain + latest keputusan mereka
        $assignedList = $this->assignedReviewers($submissionId);
        $latestDec    = $this->latestReviewerDecisions($submissionId); // [rid] => row

        $reviewers = [];
        foreach ($assignedList as $row) {
            $rid = (int)$row['reviewer_id'];
            $latest = $latestDec[$rid] ?? null;
            $keputusan = $this->normDecision($latest['keputusan'] ?? null);

            $reviewers[] = [
                'order'        => (int)$row['order'],
                'id'           => $rid,
                'nama'         => $row['nama'],
                'tugas_status' => $row['tugas_status'], // pending|accepted|declined
                'keputusan'    => $keputusan,           // menunggu|diterima|revisi|ditolak
                'tanggal'      => $latest['tanggal_review'] ?? null,
                'is_me'        => ($rid === $me),
            ];
        }

        $submissionView = [
            'id'                    => (int)$submission['id'],
            'title'                 => $submission['title'] ?? '—',
            'full_paper_status'     => $submission['full_paper_status'] ?? 'NONE',
            'full_paper_uploaded_at'=> $submission['full_paper_uploaded_at'] ?? null,
            'file_available'        => !empty($submission['full_paper_path']),
            'file_path'             => $submission['full_paper_path'] ?? null,
            'nama_lengkap'          => $submission['nama_lengkap'] ?? '-',
            'nama_kategori'         => $submission['nama_kategori'] ?? '-',
            'event_id'              => (int)($submission['event_id'] ?? $submission['s_event_id'] ?? 0),
            'event_title'           => $submission['event_title'] ?? '-',
        ];

        return view('role/reviewer/fullpaper/detail', [
            'title'          => 'Detail Full Paper',
            'submission'     => $submissionView,
            'taskStatus'     => $task['status'],
            'taskReason'     => $task['reason'],
            'myReview'       => $myReview,         // boleh null
            'reviewers'      => $reviewers,        // daftar Reviewer 1/2/3
        ]);
    }

    /**
     * Kirim / Ubah review (via modal di halaman detail).
     * Keputusan: accepted | revision | rejected
     */
    public function submit($submissionId)
    {
        if (!$this->requireReviewer()) return redirect()->to(site_url('auth/login'));
        $me = $this->me();
        $submissionId = (int)$submissionId;

        $task = $this->getTaskStatus($submissionId, $me);
        if ($task['status'] === 'pending')  return redirect()->back()->with('error','Silakan terima penugasan via Dashboard terlebih dahulu.');
        if ($task['status'] === 'declined') return redirect()->back()->with('error','Anda menolak penugasan ini.');

        $decision = strtolower(trim((string)$this->request->getPost('keputusan')));
        $comment  = trim((string)$this->request->getPost('komentar'));

        if (!in_array($decision, ['accepted','revision','rejected'], true)) {
            return redirect()->back()->with('error','Keputusan tidak valid.');
        }
        if ($decision === 'rejected' && mb_strlen($comment) < 10) {
            return redirect()->back()->with('error','Penolakan wajib disertai alasan (≥10 karakter).');
        }
        if (!$this->db->tableExists($this->reviewsTable())) {
            return redirect()->back()->with('error','Tabel fullpaper_reviews belum tersedia.');
        }

        $subTable = $this->submissionTable(); $S = $this->submissionCols($subTable);
        $uploadedAt = null;
        if ($S['ts']) {
            $row = $this->db->table($subTable)->select($S['ts'].' AS ts')->where($S['pk'],$submissionId)->get()->getRowArray();
            $uploadedAt = $row ? $row['ts'] : null;
        }

        $qb = $this->db->table($this->reviewsTable())
            ->where('submission_id', $submissionId)
            ->where('reviewer_id',   $me);
        if ($uploadedAt) $qb->where('tanggal_review >=', $uploadedAt);
        $existing = $qb->orderBy('id','DESC')->get()->getRowArray();

        $payload = [
            'submission_id'  => $submissionId,
            'reviewer_id'    => $me,
            'keputusan'      => $decision,
            'komentar'       => $comment ?: null,
            'tanggal_review' => date('Y-m-d H:i:s'),
        ];

        if ($existing) {
            $this->db->table($this->reviewsTable())->where('id', $existing['id'])->update($payload);
        } else {
            $this->db->table($this->reviewsTable())->insert($payload);
        }

        $final = $this->recalcAggregateStatus($submissionId);

        return redirect()->to(site_url('reviewer/fullpaper/'.$submissionId))
            ->with('success', 'Review tersimpan. Status agregat saat ini: '.$final);
    }

    /** konfirmasi tugas sudah dipindahkan ke dashboard */
    public function confirm($submissionId)
    {
        return redirect()->to(site_url('reviewer/fullpaper/'.$submissionId))
            ->with('error','Konfirmasi penugasan kini dilakukan dari Dashboard.');
    }

    /** Download file full paper */
    public function download($submissionId)
    {
        if (!$this->requireReviewer()) return redirect()->to(site_url('auth/login'));
        $submissionId = (int)$submissionId;

        $subTable = $this->submissionTable(); $S = $this->submissionCols($subTable);
        $row = $this->db->table($subTable)->where($S['pk'], $submissionId)->get()->getRowArray();
        if (!$row) return redirect()->back()->with('error','Submission tidak ditemukan.');

        if (!$S['path'] || empty($row[$S['path']])) {
            return redirect()->back()->with('error','File full paper belum tersedia.');
        }

        $relative = $row[$S['path']];
        if (strpos($relative, '..') !== false) {
            return redirect()->back()->with('error','Path file tidak valid.');
        }

        $absolute = rtrim($this->uploadBase, '/\\') . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative);
        if (!is_file($absolute)) {
            return redirect()->back()->with('error','File tidak ditemukan di server.');
        }

        return $this->response->download($absolute, null)->setFileName(basename($absolute));
    }

    /** Blob untuk <iframe> PDF viewer */
    public function blob($submissionId)
    {
        if (!$this->requireReviewer()) return $this->response->setStatusCode(403, 'Forbidden');
        $submissionId = (int)$submissionId;

        $subTable = $this->submissionTable(); $S = $this->submissionCols($subTable);
        $row = $this->db->table($subTable)->where($S['pk'], $submissionId)->get()->getRowArray();
        if (!$row || !$S['path'] || empty($row[$S['path']])) {
            return $this->response->setStatusCode(404, 'File tidak ditemukan');
        }
        $relative = $row[$S['path']];
        if (strpos($relative, '..') !== false) {
            return $this->response->setStatusCode(400, 'Path tidak valid');
        }
        $absolute = rtrim($this->uploadBase, '/\\') . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative);
        if (!is_file($absolute)) {
            return $this->response->setStatusCode(404, 'File tidak ada di server');
        }

        $this->response->setHeader('Content-Type', 'application/pdf');
        $this->response->setHeader('Content-Disposition', 'inline; filename="'.basename($absolute).'"');
        $this->response->setBody(file_get_contents($absolute));
        return $this->response;
    }
}