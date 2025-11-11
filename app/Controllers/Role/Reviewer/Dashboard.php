<?php
namespace App\Controllers\Role\Reviewer;

use App\Controllers\BaseController;

class Dashboard extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        helper(['date']);
    }

    /* ===================== Helpers: schema detection ===================== */

    private function reviewTable(): ?string
    {
        if ($this->db->tableExists('review'))  return 'review';
        if ($this->db->tableExists('reviews')) return 'reviews';
        return null;
    }

    private function hasCol(string $table, string $col): bool
    {
        if (!$table) return false;
        $cols = array_map('strtolower', $this->db->getFieldNames($table) ?: []);
        return in_array(strtolower($col), $cols, true);
    }

    private function pickCol(string $table, array $cands): ?string
    {
        foreach ($cands as $c) if ($this->hasCol($table, $c)) return $c;
        return null;
    }

    private function reviewCols(string $rt): array
    {
        $pk        = $this->pickCol($rt, ['id','id_review']) ?? 'id';
        $reviewer  = $this->pickCol($rt, ['id_reviewer','reviewer_id','user_id']) ?? 'id_reviewer';
        $abstrakFk = $this->pickCol($rt, ['id_abstrak']);
        $subFk     = $this->pickCol($rt, ['id_submission','submission_id']);
        $decision  = $this->pickCol($rt, ['keputusan','decision']);
        $ts        = $this->pickCol($rt, ['tanggal_review','updated_at','created_at']);
        $type      = $this->pickCol($rt, ['type','review_type']);

        $asgStatus = $this->pickCol($rt, ['status_tugas','tugas_status','assignment_status','konfirmasi_status','status']);
        $asgReason = $this->pickCol($rt, ['alasan_tolak','alasan','decline_reason','reason']);
        $asgAccAt  = $this->pickCol($rt, ['accepted_at','confirmed_at','konfirmasi_at']);
        $asgDecAt  = $this->pickCol($rt, ['declined_at','rejected_at']);

        return compact(
            'pk','reviewer','abstrakFk','subFk','decision','ts','type',
            'asgStatus','asgReason','asgAccAt','asgDecAt'
        );
    }

    private function abstrakCols(): array
    {
        $t = 'abstrak';
        return [
            'exists'   => $this->db->tableExists($t),
            'table'    => $t,
            'pk'       => 'id_abstrak',
            'title'    => $this->pickCol($t, ['judul','title']) ?? 'judul',
            'uploaded' => $this->pickCol($t, ['tanggal_upload','created_at']),
            'event_id' => $this->pickCol($t, ['event_id','id_event']),
            'status'   => $this->pickCol($t, ['status']) ?? 'status',
        ];
    }

    private function submissionTable(): ?string
    {
        if ($this->db->tableExists('submissions')) return 'submissions';
        return null;
    }

    private function submissionCols(?string $st): array
    {
        if (!$st) return ['exists'=>false];
        return [
            'exists'   => true,
            'table'    => $st,
            'pk'       => $this->pickCol($st, ['id','id_submission']) ?? 'id',
            'title'    => $this->pickCol($st, ['title','judul','judul_paper']) ?? 'title',
            'uploaded' => $this->pickCol($st, ['full_paper_uploaded_at','uploaded_at','created_at']),
            'event_id' => $this->pickCol($st, ['event_id','id_event']),
            'status'   => $this->pickCol($st, ['full_paper_status','status']) ?? 'status',
        ];
    }

    private function eventCols(): array
    {
        $t = $this->db->tableExists('events') ? 'events' : null;
        if (!$t) return ['exists'=>false];
        return [
            'exists' => true,
            'table'  => $t,
            'pk'     => 'id',
            'title'  => $this->pickCol($t, ['title','nama','name']) ?? 'title',
            'date'   => $this->pickCol($t, ['event_date','tanggal','date']),
            'time'   => $this->pickCol($t, ['event_time','waktu','time']),
        ];
    }

    private function timeAgo(?string $datetime): string
    {
        if (!$datetime) return '—';
        $diff = time() - strtotime($datetime);
        if ($diff < 60)      return 'baru saja';
        if ($diff < 3600)    return floor($diff/60).' menit lalu';
        if ($diff < 86400)   return floor($diff/3600).' jam lalu';
        if ($diff < 2592000) return floor($diff/86400).' hari lalu';
        if ($diff < 31536000)return floor($diff/2592000).' bulan lalu';
        return floor($diff/31536000).' tahun lalu';
    }

    /** Helper: kondisi “pending” yang aman untuk PG/MySQL */
    private function wherePending($builder, string $col)
    {
        $pendingVals= ["pending","menunggu","requested","assigned","awaiting","waiting","new"];
        // TRIM + LOWER untuk tangani spasi/uppercase
        $lc = "LOWER(TRIM($col))";
        return $builder->groupStart()
            ->where("$col IS NULL", null, false)
            ->orWhere("$lc = ''", null, false)
            ->orWhereIn("$lc", $pendingVals)
        ->groupEnd();
    }

    /* =============================== Dashboard =============================== */

    public function index()
    {
        $idReviewer = (int) (session('id_user') ?? 0);
        if (!$idReviewer || session('role') !== 'reviewer') {
            return redirect()->to('auth/login')->with('error', 'Session expired');
        }

        $rt = $this->reviewTable();
        if (!$rt) {
            return view('role/reviewer/dashboard', [
                'title'     => 'Reviewer Dashboard',
                'cards'     => ['total'=>0,'done'=>0],
                'incoming'  => [],
                'hasAssign' => false,
                'notifs'    => $this->fallbackNotifs(),
            ]);
        }

        $R  = $this->reviewCols($rt);
        $A  = $this->abstrakCols();
        $st = $this->submissionTable();
        $S  = $this->submissionCols($st);
        $E  = $this->eventCols();

        // KPI
        $total = $this->db->table($rt)->where($R['reviewer'], $idReviewer)->countAllResults();
        $done  = 0;
        if ($R['decision']) {
            $done = $this->db->table($rt)
                ->where($R['reviewer'], $idReviewer)
                ->whereIn($R['decision'], ['diterima','ditolak','revisi','accepted','rejected','revision'])
                ->countAllResults();
        }

        $incoming   = [];
        $hasAssign  = (bool) $R['asgStatus'];

        if ($hasAssign) {
            $col = "r.{$R['asgStatus']}";

            /* ---------- ABSTRAK (LEFT JOIN; tetap tampil kalau data master tak ada) ---------- */
            if ($A['exists'] && $R['abstrakFk']) {
                $b = $this->db->table("$rt r")
                    ->select("
                        r.{$R['pk']} AS review_id,
                        r.{$R['abstrakFk']} AS id_abstrak,
                        a.{$A['title']} AS judul,
                        a.{$A['uploaded']} AS tanggal_upload,
                        e.{$E['title']} AS event_title
                    ")
                    ->join("{$A['table']} a", "a.{$A['pk']} = r.{$R['abstrakFk']}", 'left')
                    ->where("r.{$R['reviewer']}", $idReviewer)
                    ->where("r.{$R['abstrakFk']} IS NOT NULL", null, false);

                $this->wherePending($b, $col);

                if ($E['exists'] && $A['event_id']) {
                    $b->join("{$E['table']} e", "e.{$E['pk']} = a.{$A['event_id']}", 'left');
                }

                foreach ($b->orderBy("r.{$R['pk']}",'DESC')->get()->getResultArray() as $row) {
                    $incoming[] = [
                        'task_type'      => 'abstrak',
                        'review_id'      => $row['review_id'],
                        'id_abstrak'     => (int)($row['id_abstrak'] ?? 0),
                        'judul'          => $row['judul'] ?: '(Tanpa judul)',
                        'event_title'    => $row['event_title'] ?? null,
                        'tanggal_upload' => $row['tanggal_upload'] ?? null,
                        'time_ago'       => $this->timeAgo($row['tanggal_upload'] ?? null),
                    ];
                }
            }

            /* ---------- FULL PAPER (LEFT JOIN) ---------- */
            if ($S['exists'] && $R['subFk']) {
                $b = $this->db->table("$rt r")
                    ->select("
                        r.{$R['pk']} AS review_id,
                        r.{$R['subFk']} AS id_submission,
                        s.{$S['title']} AS judul,
                        s.{$S['uploaded']} AS tanggal_upload,
                        e.{$E['title']} AS event_title
                    ")
                    ->join("{$S['table']} s", "s.{$S['pk']} = r.{$R['subFk']}", 'left')
                    ->where("r.{$R['reviewer']}", $idReviewer)
                    ->where("r.{$R['subFk']} IS NOT NULL", null, false);

                $this->wherePending($b, $col);

                if ($E['exists'] && $S['event_id']) {
                    $b->join("{$E['table']} e", "e.{$E['pk']} = s.{$S['event_id']}", 'left');
                }

                foreach ($b->orderBy("r.{$R['pk']}",'DESC')->get()->getResultArray() as $row) {
                    $incoming[] = [
                        'task_type'      => 'fullpaper',
                        'review_id'      => $row['review_id'],
                        'id_submission'  => (int)($row['id_submission'] ?? 0),
                        'judul'          => $row['judul'] ?: '(Tanpa judul)',
                        'event_title'    => $row['event_title'] ?? null,
                        'tanggal_upload' => $row['tanggal_upload'] ?? null,
                        'time_ago'       => $this->timeAgo($row['tanggal_upload'] ?? null),
                    ];
                }
            }

            /* ---------- FALLBACK (kalau tabel master tidak ada sama sekali) ---------- */
            if (empty($incoming)) {
                $b = $this->db->table("$rt r")
                    ->select("r.{$R['pk']} AS review_id, r.{$R['abstrakFk']} AS id_abstrak, r.{$R['subFk']} AS id_submission")
                    ->where("r.{$R['reviewer']}", $idReviewer);
                $this->wherePending($b, $col);

                foreach ($b->orderBy("r.{$R['pk']}",'DESC')->get()->getResultArray() as $row) {
                    $incoming[] = [
                        'task_type'      => ($row['id_submission'] ? 'fullpaper' : 'abstrak'),
                        'review_id'      => $row['review_id'],
                        'id_abstrak'     => (int)($row['id_abstrak'] ?? 0),
                        'id_submission'  => (int)($row['id_submission'] ?? 0),
                        'judul'          => '(Data belum lengkap)',
                        'event_title'    => null,
                        'tanggal_upload' => null,
                        'time_ago'       => '—',
                    ];
                }
            }
        }

        $notifs = $this->buildReviewerNotifs($idReviewer, $rt, $R);

        return view('role/reviewer/dashboard', [
            'title'     => 'Reviewer Dashboard',
            'cards'     => ['total'=>$total, 'done'=>$done],
            'incoming'  => $incoming,
            'hasAssign' => $hasAssign,
            'notifs'    => $notifs,
        ]);
    }

    /* ====================== Confirm Accept/Decline ====================== */
    public function confirm()
    {
        $idReviewer = (int) (session('id_user') ?? 0);
        if (!$idReviewer || session('role') !== 'reviewer') {
            return redirect()->to('auth/login')->with('error', 'Session expired');
        }

        $type   = strtolower((string)$this->request->getPost('type'));   // abstrak|fullpaper
        $id     = (int)$this->request->getPost('id');                    // id_abstrak | id_submission
        $action = strtolower((string)$this->request->getPost('action')); // accept|decline
        $reason = trim((string)$this->request->getPost('reason'));
        $isAjax = $this->request->isAJAX();

        if (!in_array($type, ['abstrak','fullpaper'], true) || !in_array($action, ['accept','decline'], true) || $id<=0) {
            return $this->respondConfirm($isAjax, false, 'Data konfirmasi tidak valid.');
        }
        if ($action === 'decline' && mb_strlen($reason) < 5) {
            return $this->respondConfirm($isAjax, false, 'Penolakan wajib disertai alasan (min. 5 karakter).');
        }

        $rt = $this->reviewTable();
        if (!$rt) return $this->respondConfirm($isAjax, false, 'Tabel review tidak ditemukan.');
        $R  = $this->reviewCols($rt);

        $A  = $this->abstrakCols();
        $st = $this->submissionTable();
        $S  = $this->submissionCols($st);

        $where = [$R['reviewer'] => $idReviewer];
        if ($type === 'abstrak') {
            if (!$R['abstrakFk']) return $this->respondConfirm($isAjax, false, 'Kolom id_abstrak tidak tersedia.');
            $where[$R['abstrakFk']] = $id;
        } else {
            if (!$R['subFk']) return $this->respondConfirm($isAjax, false, 'Kolom id_submission/submission_id tidak tersedia.');
            $where[$R['subFk']] = $id;
        }

        $row = $this->db->table($rt)->where($where)->orderBy($R['pk'],'DESC')->get()->getRowArray();
        if (!$row) return $this->respondConfirm($isAjax, false, 'Tugas tidak ditemukan.');

        if ($action === 'decline') {
            $this->db->transStart();
            $ok = (bool)$this->db->table($rt)->where($R['pk'], $row[$R['pk']])->delete();
            if ($ok) {
                if ($type === 'abstrak' && $A['exists']) {
                    $this->db->table($A['table'])->where($A['pk'], $id)->set($A['status'], 'menunggu')->update();
                } elseif ($type === 'fullpaper' && $S['exists']) {
                    $this->db->table($S['table'])->where($S['pk'], $id)->set($S['status'], 'menunggu')->update();
                }
            }
            $this->db->transComplete();

            return $this->respondConfirm($isAjax, $ok, $ok
                ? 'Tugas ditolak & dilepas. Admin dapat assign ulang.'
                : 'Gagal melepas tugas.');
        }

        if ($R['asgStatus']) {
            $payload = [ $R['asgStatus'] => 'accepted' ];
            if ($R['asgReason']) $payload[$R['asgReason']] = null;
            if ($R['asgAccAt'])  $payload[$R['asgAccAt']]  = date('Y-m-d H:i:s');

            $ok = (bool)$this->db->table($rt)->where($R['pk'], $row[$R['pk']])->update($payload);
            return $this->respondConfirm($isAjax, $ok, $ok ? 'Tugas diterima. Silakan lanjutkan dari menu Tugas.' : 'Gagal memperbarui status tugas.');
        }

        return $this->respondConfirm($isAjax, true, 'Tugas diterima. Silakan lanjutkan dari menu Tugas.');
    }

    private function respondConfirm(bool $isAjax, bool $success, string $message)
    {
        $data = ['success'=>$success,'message'=>$message];
        if ($isAjax) {
            $data[csrf_token()] = csrf_hash();
            return $this->response->setJSON($data);
        }
        $to = site_url('reviewer/dashboard#incoming');
        return $success
            ? redirect()->to($to)->with('success', $message)
            : redirect()->to($to)->with('error',   $message);
    }

    /* ======================= Notifications (ONLY new incoming tasks) ======================= */

    private function buildReviewerNotifs(int $uid, string $rt, array $R): array
    {
        if (!$R['asgStatus']) return [];
        $col = $R['asgStatus'];

        $b = $this->db->table($rt)
            ->where($R['reviewer'], $uid);
        $this->wherePending($b, $col);

        $count = $b->countAllResults();

        if ($count <= 0) return [];
        return [[
            'type'    => 'pending',
            'title'   => "{$count} tugas baru menunggu konfirmasi",
            'message' => 'Buka panel Tugas Masuk untuk menerima/menolak.',
            'time'    => 'Hari ini',
            'link'    => site_url('reviewer/dashboard#incoming'),
            'read'    => false,
        ]];
    }

    private function fallbackNotifs(): array
    {
        return [];
    }

    /** AJAX: GET /reviewer/notifications */
    public function getNotifications()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400)->setJSON(['error'=>'Invalid request']);
        }
        $uid = (int) (session('id_user') ?? 0);
        if (!$uid || session('role') !== 'reviewer') {
            return $this->response->setStatusCode(401)->setJSON(['error'=>'Unauthorized']);
        }

        $rt = $this->reviewTable();
        if (!$rt) return $this->response->setJSON(['success'=>true, 'notifications'=>$this->fallbackNotifs()]);

        $R  = $this->reviewCols($rt);
        $notifs = $this->buildReviewerNotifs($uid, $rt, $R);
        return $this->response->setJSON(['success'=>true, 'notifications'=>$notifs]);
    }
}
