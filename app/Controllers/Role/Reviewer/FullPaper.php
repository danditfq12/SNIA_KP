<?php

namespace App\Controllers\Role\Reviewer;

use App\Controllers\BaseController;

class FullPaper extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        helper(['date']);
    }

    /* ======================= Auth / User ======================= */

    private function requireReviewer(): bool
    {
        $id = (int) (session('id_user') ?? 0);
        return $id && strtolower((string)session('role')) === 'reviewer';
    }

    private function me(): int
    {
        if (function_exists('user') && user()) return (int) user()->id;
        return (int) (session('id_user') ?? 0);
    }

    /* ======================= Event helpers ======================= */

    private function isEventOver(?string $end): bool
    {
        if (!$end) return false;
        $ts = strtotime($end);
        if ($ts === false) return false;
        return $ts < time();
    }

    /* ======================= Tabel: submissions/abstrak ======================= */

    private function submissionTable(): string
    {
        if ($this->db->tableExists('submissions')) return 'submissions';
        if ($this->db->tableExists('abstrak'))     return 'abstrak';
        throw new \RuntimeException('Tabel submissions/abstrak tidak ditemukan.');
    }

    private function colExists(string $t, string $c): bool
    {
        $fields = $this->db->getFieldNames($t) ?: [];
        return in_array($c, $fields, true);
    }

    private function primaryKey(string $table): string
    {
        foreach (['id','id_submission','id_abstrak'] as $c) if ($this->colExists($table,$c)) return $c;
        return 'id';
    }

    private function submissionCols(string $table): array
    {
        $pk = $this->primaryKey($table);

        $title = $pk;
        foreach (['title','judul','judul_paper','judul_penelitian','judul_abstrak','nama'] as $c) {
            if ($this->colExists($table,$c)) { $title = $c; break; }
        }

        $event = null;
        foreach (['event_id','id_event','events_id'] as $c) {
            if ($this->colExists($table,$c)) { $event = $c; break; }
        }

        return [
            'pk'    => $pk,
            'title' => $title,
            'event' => $event,
            'user'  => $this->colExists($table,'id_user') ? 'id_user'
                      : ($this->colExists($table,'user_id') ? 'user_id' : null),
            'status'=> $this->colExists($table,'full_paper_status')      ? 'full_paper_status'      : null,
            'path'  => $this->colExists($table,'full_paper_path')        ? 'full_paper_path'        : null,
            'ts'    => $this->colExists($table,'full_paper_uploaded_at') ? 'full_paper_uploaded_at' : null,
            'rev'   => $this->colExists($table,'revision_no')            ? 'revision_no'            : null,
        ];
    }

    private function currentRevision(array $submissionRow, array $S): int
    {
        return (int)($S['rev'] ? ($submissionRow[$S['rev']] ?? 0) : 0);
    }

    /* ======================= Tabel: pivot & reviews ======================= */

    private function pivotTable(): string { return 'fullpaper_reviewers'; }
    private function reviewsTable(): string { return 'fullpaper_reviews'; }

    private function pivotCols(): array
    {
        $t = $this->pivotTable();
        $names = $this->db->getFieldNames($t) ?: [];
        $f = array_flip($names);
        $pick = function(array $cands) use ($f) { foreach ($cands as $c) if (isset($f[$c])) return $c; return null; };
        return [
            'pk'        => $pick(['id']),
            'submission'=> $pick(['submission_id','id_submission']),
            'reviewer'  => $pick(['reviewer_id','id_reviewer','user_id','id_user']),
            'status'    => $pick(['assignment_status','status_tugas','tugas_status','konfirmasi_status']),
            'reason'    => $pick(['decline_reason','alasan','alasan_tolak','reason']),
            'acc'       => $pick(['accepted_at','confirmed_at','konfirmasi_at']),
            'dec'       => $pick(['declined_at','rejected_at']),
            'assigned'  => $pick(['assigned_at','created_at']),
            'order'     => $pick(['order_no','urutan','posisi']),
        ];
    }

    /* ======================= Normalizers ======================= */

    private function normalizeAssign(?string $st): string
    {
        $s = strtolower(trim((string)$st));
        if (in_array($s, ['accept','accepted','ok','yes'], true)) return 'accepted';
        if (in_array($s, ['decline','declined','no','rejected_task'], true)) return 'declined';
        if (in_array($s, ['pending','requested','assigned','awaiting','waiting','new',''], true)) return 'pending';
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

    /* ======================= Review scope helpers ======================= */

    private function reviewScope(\CodeIgniter\Database\BaseBuilder $qb, int $submissionId, int $revNo): void
    {
        $qb->where('submission_id', $submissionId);
        $fields = $this->db->getFieldNames($this->reviewsTable()) ?: [];
        if (in_array('revision_no', $fields, true)) {
            $qb->where('revision_no', $revNo);
        }
    }

    /* ======================= Query Helpers (baca status tugas) ======================= */

    private function reviewTableName(): ?string
    {
        if ($this->db->tableExists('review'))  return 'review';
        if ($this->db->tableExists('reviews')) return 'reviews';
        return null;
    }

    private function pickColFrom(string $table, array $cands): ?string
    {
        $fields = array_map('strtolower', $this->db->getFieldNames($table) ?: []);
        foreach ($cands as $c) if (in_array(strtolower($c), $fields, true)) return $c;
        return null;
    }

    private function getTaskStatusFromReviews(int $submissionId, int $reviewerId): ?array
    {
        // NOTE: ini dipakai utk skema yg nyimpen status di tabel review/reviews (jarang dipakai utk fullpaper)
        $rt = $this->reviewTableName();
        if (!$rt) return null;

        $subFk = $this->pickColFrom($rt, ['id_submission','submission_id']);
        $rid   = $this->pickColFrom($rt, ['id_reviewer','reviewer_id','user_id']);
        if (!$subFk || !$rid) return null;

        $asg   = $this->pickColFrom($rt, ['status_tugas','tugas_status','assignment_status','konfirmasi_status']);
        $rsn   = $this->pickColFrom($rt, ['decline_reason','alasan_tolak','alasan','reason']);

        $row = $this->db->table($rt)
            ->where($subFk, $submissionId)
            ->where($rid,   $reviewerId)
            ->orderBy($this->pickColFrom($rt, ['id','id_review']) ?? 'id', 'DESC')
            ->get()->getRowArray();

        if (!$row) return null;

        return [
            'status' => $this->normalizeAssign($asg ? ($row[$asg] ?? 'pending') : 'pending'),
            'reason' => $rsn ? ($row[$rsn] ?? null) : null,
        ];
    }

    private function getTaskStatus(int $submissionId, int $reviewerId): array
    {
        // 1) Coba baca dari review/reviews (kalau ada)
        $fromReviews = $this->getTaskStatusFromReviews($submissionId, $reviewerId);
        if ($fromReviews) return $fromReviews;

        // 2) Fallback: dari pivot fullpaper_reviewers (ini yg dipakai Dashboard::confirm)
        $t = $this->pivotTable(); $P = $this->pivotCols();
        if (!$this->db->tableExists($t) || !$P['submission'] || !$P['reviewer']) {
            return ['status'=>'pending','reason'=>null];
        }

        $sel = ($P['pk'] ?: 'id')." AS id, {$P['submission']} AS sid, {$P['reviewer']} AS rid";
        if ($P['status']) $sel .= ", {$P['status']} AS st";
        if ($P['reason']) $sel .= ", {$P['reason']} AS rsn";

        $row = $this->db->table($t)->select($sel)
            ->where($P['submission'], $submissionId)
            ->where($P['reviewer'],  $reviewerId)
            ->orderBy($P['pk'] ?: 'id', 'DESC')
            ->get()->getRowArray();

        return [
            'status' => $this->normalizeAssign($row['st'] ?? 'pending'),
            'reason' => $row['rsn'] ?? null,
        ];
    }

    private function myLatestReview(int $submissionId, int $me, int $revNow): ?array
    {
        if (!$this->db->tableExists($this->reviewsTable())) return null;

        $qb = $this->db->table($this->reviewsTable())->where('reviewer_id', $me);
        $this->reviewScope($qb, $submissionId, $revNow);

        return $qb->orderBy('tanggal_review','DESC')
                  ->orderBy('id','DESC')
                  ->get()->getRowArray() ?: null;
    }

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
            $uf = array_flip($this->db->getFieldNames('users') ?: []);
            $usersPk   = isset($uf['id_user']) ? 'id_user' : (isset($uf['id']) ? 'id' : null);
            $usersName = isset($uf['nama_lengkap']) ? 'nama_lengkap' : (isset($uf['name']) ? 'name' : null);
            if ($usersPk) {
                $qb->join('users u', "u.$usersPk = p.{$P['reviewer']}", 'left');
                if ($usersName) $qb->select("u.$usersName AS nama_lengkap");
            }
        }

        $rows = $qb->orderBy($P['order'] ?: 'p.'.($P['pk'] ?: 'id'), 'ASC')->get()->getResultArray();
        $out  = [];
        foreach ($rows as $i => $r) {
            $out[] = [
                'order'        => (int)($r['order_no'] ?? ($i+1)),
                'reviewer_id'  => (int)$r['reviewer_id'],
                'nama'         => $r['nama_lengkap'] ?? ('Reviewer #'.$i+1),
                'tugas_status' => $this->normalizeAssign($r['tugas_status'] ?? 'pending'),
            ];
        }
        return $out;
    }

    private function latestReviewerDecisions(int $submissionId, int $revNow): array
    {
        if (!$this->db->tableExists($this->reviewsTable())) return [];

        $b = $this->db->table($this->reviewsTable())
            ->select('reviewer_id, keputusan, komentar, tanggal_review');
        $this->reviewScope($b, $submissionId, $revNow);

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
        $subTable = $this->submissionTable(); $S = $this->submissionCols($subTable);
        $subRow   = $this->db->table($subTable)->where($S['pk'],$submissionId)->get()->getRowArray() ?: [];
        $revNow   = $this->currentRevision($subRow, $S);

        $latest = $this->latestReviewerDecisions($submissionId, $revNow);

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

        if ($S['status']) {
            $update = [$S['status'] => $final];
            if ($this->colExists($subTable, 'eligible_to_pay')) {
                $update['eligible_to_pay'] = ($final === 'ACCEPTED');
            }
            $this->db->table($subTable)->where($S['pk'], $submissionId)->update($update);
        }
        return $final;
    }

    /* ======================= Join ke Abstrak & Events ======================= */

    private function applyAbstractJoinsForCategory(\CodeIgniter\Database\BaseBuilder $builder, string $subTable, array $S): void
    {
        if (!$S['event'] || !$S['user']) return;

        if ($this->db->tableExists('abstrak')) {
            $on = "a.event_id = s.{$S['event']} AND a.id_user = s.{$S['user']}";
            $builder->join('abstrak a', $on, 'left');

            $af = array_flip($this->db->getFieldNames('abstrak') ?: []);
            $colKat = isset($af['id_kategori']) ? 'id_kategori' : (isset($af['kategori_id']) ? 'kategori_id' : null);

            $absStatusCol = null;
            foreach (['status_abstrak','abstrak_status','status_review','review_status','status'] as $c) {
                if (isset($af[$c])) { $absStatusCol = $c; break; }
            }
            if ($absStatusCol) {
                $builder->select("a.$absStatusCol AS abstrak_status");
            }

            if ($this->db->tableExists('kategori_abstrak')) {
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
            $eid    = isset($ef['id']) ? 'id' : (isset($ef['event_id']) ? 'event_id' : null);
            $etitle = isset($ef['title']) ? 'title' : (isset($ef['nama']) ? 'nama' : null);

            $endCandidates  = ['end_date','date_end','tanggal_selesai','selesai_at','ended_at','end_at','tanggal_akhir','finish_date'];
            $eend = null; foreach ($endCandidates as $c) { if (isset($ef[$c])) { $eend = $c; break; } }

            $dateCandidates = ['event_date','start_date','tanggal_mulai','mulai_at','started_at','date_start'];
            $timeCandidates = ['event_time','start_time','jam_mulai','waktu_mulai'];
            $edate = null; foreach ($dateCandidates as $c) if (isset($ef[$c])) { $edate = $c; break; }
            $etime = null; foreach ($timeCandidates as $c) if (isset($ef[$c])) { $etime = $c; break; }

            if ($eid && $etitle) {
                $builder->join('events e', "e.$eid = s.{$S['event']}", 'left')
                        ->select("e.$eid AS event_id, e.$etitle AS event_title");
                if ($eend)  $builder->select("e.$eend AS event_end_at");
                if ($edate) $builder->select("e.$edate AS event_date");
                if ($etime) $builder->select("e.$etime AS event_time");
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

        if ($P['status'])   $builder->select("p.{$P['status']} AS asg_status");
        if ($P['reason'])   $builder->select("p.{$P['reason']} AS asg_reason");
        if ($P['acc'])      $builder->select("p.{$P['acc']} AS accepted_at");
        if ($P['dec'])      $builder->select("p.{$P['dec']} AS declined_at");

        if ($S['status']) $builder->select("s.{$S['status']} AS full_paper_status");
        if ($S['ts'])     $builder->select("s.{$S['ts']} AS tanggal_upload");
        if ($S['event'])  $builder->select("s.{$S['event']} AS s_event_id");
        if ($S['user'])   $builder->select("s.{$S['user']}  AS s_user_id");
        if ($S['rev'])    $builder->select("s.{$S['rev']} AS revision_no");

        $this->applyAbstractJoinsForCategory($builder, $sub, $S);

        // HANYA tugas yang sudah DIKONFIRMASI (accepted) di dashboard yang boleh muncul di daftar Full Paper
        if ($P['status']) {
            $builder->whereIn("p.{$P['status']}", ['accepted','diterima']);
        }

        $builder->where("p.{$P['reviewer']}", $me);
        $builder->orderBy("s.{$S['pk']}", 'DESC', false);

        $rawRows = $builder->get()->getResultArray();

        $eventOptions = [];
        $listRows     = [];

        foreach ($rawRows as &$r) {
            $sid = (int)($r['id'] ?? 0);

            $subRow     = $this->db->table($sub)->where($S['pk'], $sid)->get()->getRowArray() ?: [];
            $revNow     = $this->currentRevision($subRow, $S);
            $uploadedAt = $subRow[$S['ts']] ?? null;

            $my = $this->myLatestReview($sid, $me, $revNow);

            $r['review_status']   = $this->normDecision($my['keputusan'] ?? null) ?: 'menunggu';
            $r['asg_status_norm'] = $this->normalizeAssign($r['asg_status'] ?? 'pending');

            $eid = (int)($r['event_id'] ?? $r['s_event_id'] ?? 0);
            $r['event_id']    = $eid;
            $r['event_title'] = $r['event_title'] ?? '-';
            $r['event_end_at']= $r['event_end_at'] ?? null;

            if ($eid && !isset($eventOptions[$eid])) {
                $eventOptions[$eid] = $r['event_title'] ?? ('Event #'.$eid);
            }

            // cek apakah butuh tindakan ulang (misal setelah upload revisi)
            $needs = true;
            if ($my) {
                $myAt = $my['tanggal_review'] ?? '1970-01-01 00:00:00';
                $needs = $uploadedAt ? ($myAt < $uploadedAt) : false;
            } else {
                $needs = true;
            }

            $rev = strtolower((string)$r['review_status']);    // 'menunggu' | 'revisi' | 'diterima' | 'ditolak'
            $asg = strtolower((string)$r['asg_status_norm']);  // 'accepted' | 'pending' | 'declined'
            $eventOver = $this->isEventOver($r['event_end_at'] ?? null);

            // RULE:
            // - belum dikonfirmasi: sudah difilter di query (tidak muncul di sini)
            // - declined: jangan tampil (anggap batal)
            // - diterima (review keputusan): langsung pindah ke riwayat => jangan tampil
            // - revisi/ditolak: tampil SELAMA event belum selesai, setelah event selesai hilang (riwayat yg menampung)
            $show = true;
            if ($asg === 'declined') {
                $show = false;
            } elseif ($rev === 'diterima') {
                $show = false;
            } elseif ($rev === 'revisi' || $rev === 'ditolak') {
                $show = !$eventOver;
            } else {
                // 'menunggu' (belum ada review untuk revision ini) => tampil
                $show = true;
            }

            // kalau assignment accepted & ada revisi baru (needs), tetap paksa tampil
            if ($asg === 'accepted' && $needs) $show = true;

            if ($show) $listRows[] = $r;
        }
        unset($r);
        ksort($eventOptions);

        return view('role/reviewer/fullpaper/index', [
            'title'        => 'Tugas Full Paper',
            'rows'         => $listRows,   // hanya To-Do
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
        if ($S['rev'])    $builder->select("s.{$S['rev']} AS revision_no");

        $this->applyAbstractJoinsForCategory($builder, $subTable, $S);

        $submission = $builder->get()->getRowArray();
        if (!$submission) return redirect()->back()->with('error','Submission tidak ditemukan');

        $pvt = $this->pivotTable(); $P = $this->pivotCols();
        if (!$this->db->tableExists($pvt) || !$P['submission'] || !$P['reviewer']) {
            return redirect()->to(site_url('reviewer/fullpaper'))->with('error','Penugasan tidak ditemukan.');
        }
        $assigned = $this->db->table($pvt)
            ->where($P['submission'], $submissionId)
            ->where($P['reviewer'],  $me)->countAllResults() > 0;
        if (!$assigned) {
            return redirect()->to(site_url('reviewer/fullpaper'))->with('error','Anda tidak ditugaskan untuk naskah ini.');
        }

        // baca status tugas dari pivot/review
        $task = $this->getTaskStatus($submissionId, $me);

        // JIKA BELUM DIKONFIRMASI → TIDAK BOLEH MASUK DETAIL
        if ($task['status'] !== 'accepted') {
            return redirect()->to(site_url('reviewer/dashboard#incoming'))
                ->with('error','Terima penugasan full paper ini terlebih dahulu di halaman dashboard.');
        }

        $revNow   = $this->currentRevision($submission, $S);
        $myReview = $this->myLatestReview($submissionId, $me, $revNow);

        $assignedList = $this->assignedReviewers($submissionId);
        $latestDec    = $this->latestReviewerDecisions($submissionId, $revNow);

        $reviewers = [];
        foreach ($assignedList as $row) {
            $rid = (int)$row['reviewer_id'];
            $latest = $latestDec[$rid] ?? null;
            $keputusan = $this->normDecision($latest['keputusan'] ?? null);

            $reviewers[] = [
                'order'        => (int)$row['order'],
                'id'           => $rid,
                'nama'         => $row['nama'],
                'tugas_status' => $row['tugas_status'],
                'keputusan'    => $keputusan,
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
            'event_date'            => $submission['event_date'] ?? null,
            'event_time'            => $submission['event_time'] ?? null,
            'revisi_ke'             => $submission['revision_no'] ?? null,
            'abstrak_status'        => $submission['abstrak_status'] ?? null,
        ];

        return view('role/reviewer/fullpaper/detail', [
            'title'          => 'Detail Full Paper',
            'submission'     => $submissionView,
            'taskStatus'     => $task['status'],   // seharusnya selalu 'accepted' di titik ini
            'taskReason'     => $task['reason'],
            'revisionNo'     => $revNow,
            'myReview'       => $myReview,
            'reviewers'      => $reviewers,
        ]);
    }

    public function submit($submissionId)
    {
        if (!$this->requireReviewer()) return redirect()->to(site_url('auth/login'));
        if (!$this->request->is('post')) {
            return redirect()->to(site_url('reviewer/fullpaper/'.$submissionId))
                ->with('error','Metode tidak diizinkan.');
        }

        $me = $this->me();
        $submissionId = (int)$submissionId;

        $task = $this->getTaskStatus($submissionId, $me);
        if ($task['status'] !== 'accepted') {
            return redirect()->to(site_url('reviewer/dashboard#incoming'))
                ->with('error','Terima penugasan terlebih dahulu di halaman dashboard.');
        }

        $decision = strtolower(trim((string)$this->request->getPost('keputusan')));
        $comment  = trim((string)$this->request->getPost('komentar'));

        if (!in_array($decision, ['accepted','revision','rejected'], true)) {
            return redirect()->back()->with('error','Keputusan tidak valid.');
        }

        if ($comment === '') {
            return redirect()->back()->with('error','Komentar wajib diisi sebelum mengirim review.');
        }
        if ($decision === 'rejected' && mb_strlen($comment) < 10) {
            return redirect()->back()->with('error','Penolakan wajib disertai alasan (≥10 karakter).');
        }

        if (!$this->db->tableExists($this->reviewsTable())) {
            return redirect()->back()->with('error','Tabel fullpaper_reviews belum tersedia.');
        }

        $subTable = $this->submissionTable(); $S = $this->submissionCols($subTable);
        $subRow   = $this->db->table($subTable)->where($S['pk'],$submissionId)->get()->getRowArray() ?: [];
        $revNow   = $this->currentRevision($subRow, $S);

        $fields = $this->db->getFieldNames($this->reviewsTable()) ?: [];

        $payload = [
            'submission_id'  => $submissionId,
            'reviewer_id'    => $me,
            'keputusan'      => $decision,
            'komentar'       => $comment,
            'tanggal_review' => date('Y-m-d H:i:s'),
        ];
        if (in_array('revision_no', $fields, true)) {
            $payload['revision_no'] = $revNow;
        }

        $qbExist = $this->db->table($this->reviewsTable())
            ->where('submission_id', $submissionId)
            ->where('reviewer_id',   $me);
        if (in_array('revision_no', $fields, true)) {
            $qbExist->where('revision_no', $revNow);
        }
        $existing = $qbExist->orderBy('tanggal_review','DESC')->orderBy('id','DESC')->get()->getRowArray();

        if ($existing) {
            $this->db->table($this->reviewsTable())->where('id', (int)$existing['id'])->update($payload);
        } else {
            $this->db->table($this->reviewsTable())->insert($payload);
        }

        $final = $this->recalcAggregateStatus($submissionId);

        return redirect()->to(site_url('reviewer/fullpaper/'.$submissionId))
            ->with('success', 'Review tersimpan. Status agregat saat ini: '.$final);
    }

    /* ====== Endpoint lama (opsional) – dibiarkan untuk kompatibilitas (routes sudah dicabut) ====== */
    public function action()
    {
        // Tidak lagi dipakai karena konfirmasi tugas sudah pindah ke Dashboard::confirm
        // Dibiarkan supaya tidak fatal kalau masih ada request lama.
        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Aksi konfirmasi full paper sekarang dilakukan melalui dashboard reviewer.'
            ]);
        }
        return redirect()->to(site_url('reviewer/dashboard#incoming'))
            ->with('error','Aksi konfirmasi full paper sekarang dilakukan melalui dashboard reviewer.');
    }

    /* ======================= File utils & endpoints ======================= */

    private function resolvePdfAbsolutePath(?string $stored): ?string
    {
        if (!$stored) return null;
        $rel = trim(str_replace(['\\', '..'], ['/', ''], $stored), '/');

        if (is_file($stored)) return $stored;

        $candidates = [
            rtrim(WRITEPATH, '/\\') . DIRECTORY_SEPARATOR . $rel,
            rtrim(WRITEPATH, '/\\') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $rel,
            rtrim(WRITEPATH, '/\\') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'fullpaper' . DIRECTORY_SEPARATOR . basename($rel),
            rtrim(FCPATH,    '/\\') . DIRECTORY_SEPARATOR . $rel,
            rtrim(FCPATH,    '/\\') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'fullpaper' . DIRECTORY_SEPARATOR . basename($rel),
        ];

        $base = trim((string) env('fullpaper.storage_base', ''), '/\\');
        if ($base !== '') {
            $candidates[] = $base . DIRECTORY_SEPARATOR . $rel;
        }

        foreach ($candidates as $abs) {
            if (is_file($abs)) return $abs;
        }
        return null;
    }

    public function download($submissionId)
    {
        if (!$this->requireReviewer()) return $this->response->setStatusCode(401, 'Unauthorized');
        $submissionId = (int)$submissionId;

        $subTable = $this->submissionTable(); $S = $this->submissionCols($subTable);
        $row = $this->db->table($subTable)->where($S['pk'], $submissionId)->get()->getRowArray();
        if (!$row) return $this->response->setStatusCode(404, 'Submission tidak ditemukan.');
        if (!$S['path'] || empty($row[$S['path']])) {
            return $this->response->setStatusCode(404, 'File full paper belum tersedia.');
        }

        $absolute = $this->resolvePdfAbsolutePath((string)$row[$S['path']]);
        if (!$absolute) {
            log_message('error', 'Download FP: file hilang di server => '.$row[$S['path']]);
            return $this->response->setStatusCode(404, 'File tidak ditemukan di server.');
        }

        return $this->response->download($absolute, null)->setFileName(basename($absolute));
    }

    public function blob($submissionId)
    {
        if (!$this->requireReviewer()) return $this->response->setStatusCode(401, 'Unauthorized');
        $submissionId = (int)$submissionId;

        $subTable = $this->submissionTable(); $S = $this->submissionCols($subTable);
        $row = $this->db->table($subTable)->where($S['pk'], $submissionId)->get()->getRowArray();
        if (!$row || !$S['path'] || empty($row[$S['path']])) {
            return $this->response->setStatusCode(404, 'File tidak ditemukan');
        }

        $absolute = $this->resolvePdfAbsolutePath((string)$row[$S['path']]);
        if (!$absolute) {
            log_message('error', 'Blob FP: file hilang di server => '.$row[$S['path']]);
            return $this->response->setStatusCode(404, 'File tidak ada di server');
        }

        while (ob_get_level() > 0) { @ob_end_clean(); }

        $filename = basename($absolute);
        $this->response->setHeader('Content-Type', 'application/pdf');
        $this->response->setHeader('Content-Disposition', 'inline; filename="'.$filename.'"');
        $this->response->setHeader('Accept-Ranges', 'bytes');
        $this->response->setHeader('Cache-Control', 'no-store, max-age=0');

        return $this->response->setBody(file_get_contents($absolute));
    }

    public function preview($submissionId) { return $this->blob($submissionId); }
    public function inline($submissionId)  { return $this->blob($submissionId); }

    /* ======================= (Opsional) Hook ======================= */

    public function afterAuthorReupload(int $submissionId, bool $notifyAll = true): void
    {
        $subTable = $this->submissionTable(); $S = $this->submissionCols($subTable);

        $this->db->transStart();

        $row  = $this->db->table($subTable)->where($S['pk'],$submissionId)->get()->getRowArray() ?: [];
        $rev  = (int)($S['rev'] ? ($row[$S['rev']] ?? 0) : 0);
        $upd  = [
            $S['status'] ?? 'full_paper_status'      => 'UPLOADED',
            $S['ts']     ?? 'full_paper_uploaded_at' => date('Y-m-d H:i:s'),
        ];
        if ($S['rev']) $upd[$S['rev']] = $rev + 1;

        $this->db->table($subTable)->where($S['pk'],$submissionId)->update($upd);

        $pvt = $this->pivotTable(); $P = $this->pivotCols();
        $qb  = $this->db->table($pvt)->select($P['reviewer'].' AS rid')
                        ->where($P['submission'], $submissionId);

        if (!$notifyAll && $this->db->tableExists($this->reviewsTable())) {
            $b = $this->db->table($this->reviewsTable())
                 ->select('DISTINCT reviewer_id AS rid')
                 ->where('submission_id', $submissionId)
                 ->whereIn('LOWER(keputusan)', ['revision','revisi']);
            $rids = array_map(fn($x)=>(int)$x['rid'], $b->get()->getResultArray());
            if ($rids) $qb->whereIn($P['reviewer'], $rids); else $qb->where('0=1', null, false);
        }

        $reviewers = array_map(fn($x)=>(int)$x['rid'], $qb->get()->getResultArray());

        if ($this->db->tableExists('notifications') && $reviewers) {
            $rows = [];
            foreach ($reviewers as $rid) {
                $rows[] = [
                    'user_id'    => $rid,
                    'title'      => 'Revisi Full Paper diunggah',
                    'message'    => "Submission #{$submissionId} mengunggah revisi baru. Mohon re-review.",
                    'created_at' => date('Y-m-d H:i:s'),
                    'is_read'    => 0,
                    'link'       => site_url('reviewer/fullpaper/'.$submissionId),
                ];
            }
            if ($rows) $this->db->table('notifications')->insertBatch($rows);
        }

        $this->db->transComplete();
    }
}
