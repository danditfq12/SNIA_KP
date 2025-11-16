<?php

namespace App\Controllers\Role\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\AbstrakModel;
use App\Models\PembayaranModel;
use App\Models\EventModel;
use App\Models\LogAktivitasModel;

class Dashboard extends BaseController
{
    protected UserModel $userModel;
    protected AbstrakModel $abstrakModel;
    protected PembayaranModel $pembayaranModel;
    protected EventModel $eventModel;
    protected LogAktivitasModel $logModel;
    protected \CodeIgniter\Database\BaseConnection $db;

    /** Kuota minimal reviewer untuk Full Paper — samakan dengan KelolaPaper */
    private int $requiredFp = 3;

    public function __construct()
    {
        $this->userModel       = new UserModel();
        $this->abstrakModel    = new AbstrakModel();
        $this->pembayaranModel = new PembayaranModel();
        $this->eventModel      = new EventModel();
        $this->logModel        = new LogAktivitasModel();
        $this->db              = \Config\Database::connect();
    }

    public function index()
    {
        try {
            $kpi = [
                'pembayaran_pending'       => $this->safeCount(fn () => $this->pembayaranModel->where('status', 'pending')->countAllResults()),
                'abstrak_unassigned'       => $this->countUnassignedAbstrak(),
                'fullpaper_unassigned'     => $this->countUnassignedFullPaperSync(),   // ← disamakan
                'pembayaran_terverifikasi' => $this->safeCount(fn () => $this->pembayaranModel->where('status', 'verified')->countAllResults()),
            ];

            $data = [
                'title' => 'Admin Dashboard',
                'kpi'   => $kpi,

                // panels
                'pendingPayments'    => $this->getPendingPayments(6),
                'recentActivities'   => $this->getUnifiedRecentActivities(12),
                'logs'               => $this->safeGet(fn () => $this->logModel->getRecentActivities(12), []),

                // lists penugasan
                'unassigned_abstrak'   => $this->getUnassignedAbstrak(6),
                'unassigned_fullpaper' => $this->getUnassignedFullPaperSync(6),       // ← disamakan
            ];

            return view('role/admin/dashboard', $data);
        } catch (\Throwable $e) {
            log_message('error', 'Dashboard index error: ' . $e->getMessage());
            return view('role/admin/dashboard', [
                'title' => 'Admin Dashboard',
                'kpi' => [
                    'pembayaran_pending'       => 0,
                    'abstrak_unassigned'       => 0,
                    'fullpaper_unassigned'     => 0,
                    'pembayaran_terverifikasi' => 0,
                ],
                'pendingPayments'      => [],
                'recentActivities'     => [],
                'logs'                 => [],
                'unassigned_abstrak'   => [],
                'unassigned_fullpaper' => [],
            ]);
        }
    }

    /* ====================== Sections helpers ====================== */

    private function getPendingPayments(int $limit = 6): array
    {
        try {
            return $this->db->table('pembayaran p')
                ->select('p.id_pembayaran, p.jumlah, p.status, p.tanggal_bayar, p.metode,
                          u.nama_lengkap, u.email, e.title AS event_title')
                ->join('users u', 'u.id_user = p.id_user', 'left')
                ->join('events e', 'e.id = p.event_id', 'left')
                ->where('p.status', 'pending')
                ->orderBy('p.tanggal_bayar', 'DESC')
                ->limit($limit)
                ->get()->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** Feed gabungan (dipertahankan dari punyamu) */
    private function getUnifiedRecentActivities(int $limit = 12): array
    {
        $items = [];

        // Pembayaran
        try {
            $rows = $this->db->table('pembayaran p')
                ->select("p.id_pembayaran AS id, p.status, p.jumlah, p.tanggal_bayar AS happened_at,
                          u.nama_lengkap, e.title AS event_title, 'payment' AS kind")
                ->join('users u', 'u.id_user = p.id_user', 'left')
                ->join('events e', 'e.id = p.event_id', 'left')
                ->orderBy('p.tanggal_bayar', 'DESC')
                ->limit($limit)
                ->get()->getResultArray();
            foreach ($rows as $r) $items[] = $r;
        } catch (\Throwable $e) {}

        // Registrasi user
        try {
            $rows = $this->userModel
                ->select("id_user AS id, nama_lengkap, email, role, created_at AS happened_at, 'user' AS kind")
                ->orderBy('created_at', 'DESC')
                ->limit($limit)
                ->findAll();
            foreach ($rows as $r) $items[] = $r;
        } catch (\Throwable $e) {}

        // Abstrak submit
        try {
            $rows = $this->db->table('abstrak a')
                ->select("a.id_abstrak AS id, a.judul, a.status, a.tanggal_upload AS happened_at,
                          u.nama_lengkap, 'abstract' AS kind")
                ->join('users u', 'u.id_user = a.id_user', 'left')
                ->orderBy('a.tanggal_upload', 'DESC')
                ->limit($limit)
                ->get()->getResultArray();
            foreach ($rows as $r) $items[] = $r;
        } catch (\Throwable $e) {}

        // urutkan & potong
        usort($items, function($a, $b) {
            $ta = isset($a['happened_at']) ? strtotime((string) $a['happened_at']) : 0;
            $tb = isset($b['happened_at']) ? strtotime((string) $b['happened_at']) : 0;
            return $tb <=> $ta;
        });

        return array_slice($items, 0, $limit);
    }

    /* =================== Unassigned: ABSTRAK (tetap) =================== */

    private function countUnassignedAbstrak(): int
    {
        try {
            $ar = $this->resolveAbstrakAssign();
            if ($ar['table']) {
                $q = $this->db->table('abstrak a')
                    ->select('COUNT(a.id_abstrak) AS c')
                    ->join($ar['table'].' ar', "ar.{$ar['abs_fk']} = a.id_abstrak", 'left')
                    ->where("ar.{$ar['abs_fk']}", null)
                    ->get()->getRowArray();
                return (int)($q['c'] ?? 0);
            }
            return (int) $this->abstrakModel->where('status', 'menunggu')->countAllResults();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function getUnassignedAbstrak(int $limit = 6): array
    {
        try {
            $ar = $this->resolveAbstrakAssign();
            if ($ar['table']) {
                return $this->db->table('abstrak a')
                    ->select('a.id_abstrak, a.judul, a.status, a.tanggal_upload AS created_at, u.nama_lengkap')
                    ->join('users u', 'u.id_user = a.id_user', 'left')
                    ->join($ar['table'].' ar', "ar.{$ar['abs_fk']} = a.id_abstrak", 'left')
                    ->where("ar.{$ar['abs_fk']}", null)
                    ->orderBy('a.tanggal_upload', 'DESC')
                    ->limit($limit)
                    ->get()->getResultArray();
            }

            return $this->db->table('abstrak a')
                ->select('a.id_abstrak, a.judul, a.status, a.tanggal_upload AS created_at, u.nama_lengkap')
                ->join('users u', 'u.id_user = a.id_user', 'left')
                ->where('a.status', 'menunggu')
                ->orderBy('a.tanggal_upload', 'DESC')
                ->limit($limit)
                ->get()->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /* =================== Unassigned: FULL PAPER (disamakan) =================== */

    /** KPI: jumlah FP yang sudah upload + abstraknya accepted tapi assignment aktif < kuota */
    private function countUnassignedFullPaperSync(): int
    {
        try {
            $src = $this->resolveFullpaperSource(); // satu sumber utama
            if (!$src) return 0;

            [$table,$pk,$uid,$eid,$path,$status,$ts] = $src;

            // pivot + rel + status assignment
            $pivot     = 'fullpaper_reviewers';
            $hasPivot  = $this->db->tableExists($pivot);
            $rel       = $hasPivot ? $this->firstExistingColumn($pivot, ['submission_id','id_submission','fullpaper_id','id_fullpaper','abstrak_id','id_abstrak']) : null;
            $assignCol = $rel ? $this->firstExistingColumn($pivot, ['assignment_status','status_tugas','tugas_status','konfirmasi_status']) : null;

            // gating: harus ada abstrak diterima utk (user,event)
            $absAcceptedExpr = $this->buildAcceptedAbstractExistsExpr($uid, $eid);

            if (!$hasPivot || !$rel) {
                // tanpa pivot → anggap semua yang sudah upload & lolos gating = belum memenuhi kuota
                return (int)$this->db->table($table)
                    ->where("$path IS NOT NULL AND $path <> ''")
                    ->where($absAcceptedExpr, null, false)
                    ->countAllResults();
            }

            $assignedExpr = $assignCol
                ? "SUM(CASE WHEN fr.$assignCol IS NULL OR LOWER(fr.$assignCol) IN ('accepted','pending') THEN 1 ELSE 0 END)"
                : "COUNT(*)";

            $row = $this->db->query("
                SELECT COUNT(*) c FROM (
                  SELECT f.$pk
                  FROM $table f
                  LEFT JOIN (
                    SELECT $rel sid, $assignedExpr AS assigned
                    FROM $pivot fr
                    GROUP BY $rel
                  ) x ON x.sid = f.$pk
                  WHERE f.$path IS NOT NULL AND f.$path <> ''
                    AND $absAcceptedExpr
                    AND COALESCE(x.assigned,0) < ?
                ) t
            ", [$this->requiredFp])->getRow();

            return (int)($row->c ?? 0);
        } catch (\Throwable $e) {
            return 0;
        }
    }

    /** List untuk panel “Perlu Penugasan” (FP) — logika seragam */
    private function getUnassignedFullPaperSync(int $limit = 6): array
    {
        try {
            $src = $this->resolveFullpaperSource();
            if (!$src) return [];

            [$table,$pk,$uid,$eid,$path,$status,$ts,$title] = $src;

            $pivot     = 'fullpaper_reviewers';
            $hasPivot  = $this->db->tableExists($pivot);
            $rel       = $hasPivot ? $this->firstExistingColumn($pivot, ['submission_id','id_submission','fullpaper_id','id_fullpaper','abstrak_id','id_abstrak']) : null;
            $assignCol = $rel ? $this->firstExistingColumn($pivot, ['assignment_status','status_tugas','tugas_status','konfirmasi_status']) : null;

            $absAcceptedExpr = $this->buildAcceptedAbstractExistsExpr($uid, $eid);

            $assignedExpr = $assignCol
                ? "SUM(CASE WHEN fr.$assignCol IS NULL OR LOWER(fr.$assignCol) IN ('accepted','pending') THEN 1 ELSE 0 END)"
                : "COUNT(*)";

            $b = $this->db->table("$table f")
                ->select("f.$pk AS id_fullpaper, f.$title AS judul, f.$status AS status, f.$ts AS created_at, u.nama_lengkap", false)
                ->join('users u', "u.id_user = f.$uid", 'left')
                ->where("f.$path IS NOT NULL AND f.$path <> ''")
                ->where($absAcceptedExpr, null, false);

            if ($rel) {
                $b->join("$pivot fr", "fr.$rel = f.$pk", 'left')
                  ->groupBy("f.$pk, f.$title, f.$status, f.$ts, u.nama_lengkap")
                  ->having("COALESCE($assignedExpr,0) < ".$this->requiredFp);
            }

            return $b->orderBy("f.$ts", 'DESC')
                     ->limit($limit)
                     ->get()->getResultArray();
        } catch (\Throwable $e) {
            return [];
        }
    }

    /* =================== Resolvers & helpers =================== */

    private function acceptedAliases(): array
    {
        return ['accepted','accept','acc','approved','diterima'];
    }

    /** Ambil satu sumber utama fullpaper (submissions > abstrak) berikut kolom kunci */
    private function resolveFullpaperSource(): ?array
    {
        $cands = [
            ['table'=>'submissions','pk'=>['id'],'uid'=>['user_id','id_user'],'eid'=>['event_id','id_event'],
             'title'=>['judul','title','paper_title'],'status'=>['full_paper_status','fp_status','status','keputusan'],
             'path'=>['full_paper_path','file_path','path'],'ts'=>['full_paper_uploaded_at','uploaded_at','created_at','tanggal_upload']],
            ['table'=>'abstrak','pk'=>['id_abstrak'],'uid'=>['id_user','user_id'],'eid'=>['event_id','id_event'],
             'title'=>['judul','title'],'status'=>['full_paper_status','fp_status','status','keputusan'],
             'path'=>['full_paper_path','file_path','path'],'ts'=>['full_paper_uploaded_at','uploaded_at','created_at','tanggal_upload']],
        ];
        foreach ($cands as $c) {
            if (!$this->db->tableExists($c['table'])) continue;
            $pick = fn($opts)=> $this->firstExistingColumn($c['table'],$opts);
            $pk   = $pick($c['pk']) ?: 'id';
            $uid  = $pick($c['uid']);
            $eid  = $pick($c['eid']);
            $path = $pick($c['path']);
            $st   = $pick($c['status']) ?: $pk;
            $ts   = $pick($c['ts']) ?: $pk;
            $ttl  = $pick($c['title']) ?: $pk;
            if ($uid && $eid && $path) {
                return [$c['table'],$pk,$uid,$eid,$path,$st,$ts,$ttl];
            }
        }
        return null;
    }

    /** EXISTS abstrak diterima utk (user,event) yang sama (dipakai di WHERE) */
    private function buildAcceptedAbstractExistsExpr(string $uid, string $eid): string
    {
        if (!$this->db->tableExists('abstrak')) return '1=1';
        $aUid = $this->firstExistingColumn('abstrak', ['id_user','user_id','presenter_id']) ?: 'id_user';
        $aEid = $this->firstExistingColumn('abstrak', ['event_id','id_event','events_id']) ?: 'event_id';

        $aliases = array_map(fn($s)=> "'$s'", $this->acceptedAliases());
        $in = implode(',', $aliases);
        // gunakan alias FP = f
        return "EXISTS (SELECT 1 FROM abstrak a WHERE a.$aUid = f.$uid AND a.$aEid = f.$eid AND LOWER(a.status) IN ($in))";
    }

    private function resolveAbstrakAssign(): array
    {
        foreach (['abstrak_reviewers','abstrak_reviewer','reviewers_abstrak','reviewer_abstrak'] as $t) {
            if ($this->db->tableExists($t)) {
                $fk = $this->firstExistingColumn($t, ['id_abstrak','abstrak_id']) ?: 'id_abstrak';
                return ['table'=>$t,'abs_fk'=>$fk];
            }
        }
        return ['table'=>null,'abs_fk'=>null];
    }

    private function columnExists(string $table, string $column): bool
    {
        if (!$this->db->tableExists($table)) return false;
        foreach ($this->db->getFieldData($table) as $f) {
            if (strcasecmp($f->name, $column) === 0) return true;
        }
        return false;
    }

    private function firstExistingColumn(string $table, array $candidates): ?string
    {
        foreach ($candidates as $c) if ($this->columnExists($table, $c)) return $c;
        return null;
    }

    /* =================== Misc utils =================== */

    private function safeCount(callable $fn, int $fallback = 0): int
    {
        try { return (int)$fn(); } catch (\Throwable $e) { return $fallback; }
    }
    private function safeGet(callable $fn, $fallback)
    {
        try { return $fn(); } catch (\Throwable $e) { return $fallback; }
    }
}
