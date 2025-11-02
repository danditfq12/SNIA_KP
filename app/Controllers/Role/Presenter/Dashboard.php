<?php
namespace App\Controllers\Role\Presenter;

use App\Controllers\BaseController;
use Config\Database;

class Dashboard extends BaseController
{
    protected \CodeIgniter\Database\BaseConnection $db;
    private array $colsCache = [];

    public function __construct()
    {
        $this->db = Database::connect();
        helper('text');
    }

    /* ================= Utils ================= */

    private function uid(): int
    {
        foreach (['id_user','user_id','id'] as $k) {
            $v = session($k);
            if (!empty($v)) return (int)$v;
        }
        return 0;
    }

    private function tableExists(string $t): bool
    {
        try { return $this->db->tableExists($t); } catch (\Throwable $e) { return false; }
    }

    private function tableCols(string $table): array
    {
        $t = strtolower($table);
        if (isset($this->colsCache[$t])) return $this->colsCache[$t];
        $out = [];
        try {
            foreach ($this->db->getFieldData($t) as $fd) {
                $name = strtolower($fd->name ?? '');
                if ($name) $out[$name] = true;
            }
        } catch (\Throwable $e) {
            try {
                $driver = strtolower((string)($this->db->DBDriver ?? ''));
                if (str_contains($driver, 'postgre')) {
                    $sql  = "SELECT column_name FROM information_schema.columns
                             WHERE table_schema = current_schema() AND table_name = ?";
                    $rows = $this->db->query($sql, [$t])->getResultArray();
                } else {
                    $sql  = "SELECT COLUMN_NAME AS column_name FROM information_schema.columns
                             WHERE table_schema = DATABASE() AND table_name = ?";
                    $rows = $this->db->query($sql, [$t])->getResultArray();
                }
                foreach ($rows as $r) {
                    $name = strtolower($r['column_name'] ?? '');
                    if ($name) $out[$name] = true;
                }
            } catch (\Throwable $e2) {}
        }
        return $this->colsCache[$t] = $out;
    }

    private function colExists(string $table, string $col): bool
    {
        return isset($this->tableCols($table)[strtolower($col)]);
    }

    private function pickCol(string $table, array $candidates, ?string $fallback = null): ?string
    {
        foreach ($candidates as $c) if ($this->colExists($table, $c)) return $c;
        return $fallback;
    }

    private function buildCoalesceChecked(string $tablePhys, string $tableAlias, array $cands, string $alias): string
    {
        $ok = [];
        foreach ($cands as $c) {
            if ($this->colExists($tablePhys, $c)) {
                $ok[] = $this->db->protectIdentifiers("$tableAlias.$c");
            }
        }
        if (!$ok) return "NULL AS {$alias}";
        if (count($ok) === 1) return $ok[0] . " AS {$alias}";
        return 'COALESCE(' . implode(',', $ok) . ") AS {$alias}";
    }

    private function dateExpr(string $tableAlias, string $col): string
    {
        return 'DATE(' . $this->db->protectIdentifiers("$tableAlias.$col") . ')';
    }

    /* =============== Meta Abstrak & Fullpaper =============== */

    private function getAbstractStatus(int $uid, int $eventId): array
    {
        if (!$this->tableExists('abstrak')) return ['has'=>false,'status'=>''];

        $orderCol = $this->pickCol('abstrak', ['updated_at','tanggal_upload','created_at'], 'id_abstrak');

        $row = $this->db->table('abstrak')
            ->select('status')
            ->where('id_user',$uid)->where('event_id',$eventId)
            ->orderBy($orderCol,'DESC')->get()->getRowArray();

        if (!$row) return ['has'=>false,'status'=>''];
        return ['has'=>true,'status'=>strtolower((string)$row['status'])];
    }

    private function getFullpaperMeta(int $uid, int $eventId): array
    {
        $candidates = [];
        if ($this->tableExists('submissions')) $candidates[] = 'submissions';
        if ($this->tableExists('abstrak'))     $candidates[] = 'abstrak';

        foreach ($candidates as $t) {
            $hasPath   = $this->colExists($t,'full_paper_path');
            $hasStatus = $this->colExists($t,'full_paper_status');
            if (!$hasPath && !$hasStatus) continue;

            $orderCol = $this->pickCol($t, ['updated_at','full_paper_uploaded_at','created_at'], $t==='abstrak' ? 'id_abstrak' : 'id');

            $sel = [];
            $sel[] = $hasPath   ? 'full_paper_path AS path' : "NULL AS path";
            $sel[] = $hasStatus ? 'full_paper_status AS status' : "NULL AS status";

            $row = $this->db->table($t)
                ->select(implode(',',$sel), false)
                ->where('id_user',$uid)->where('event_id',$eventId)
                ->orderBy($orderCol,'DESC')->get()->getRowArray();

            if (!$row) continue;

            $status = strtolower(trim((string)($row['status'] ?? '')));
            $status = match (true) {
                $status==='' && !empty($row['path'])                              => 'uploaded',
                $status===''                                                      => 'none',
                in_array($status,['uploaded','menunggu','pending','sedang_direview'],true) => 'uploaded',
                in_array($status,['revisi','revision'],true)                      => 'revision',
                in_array($status,['diterima','accepted','acc','approved'],true)  => 'accepted',
                in_array($status,['ditolak','rejected'],true)                     => 'rejected',
                default => $status,
            };

            return [
                'has'    => !empty($row['path']) || $status!=='none',
                'status' => strtoupper($status),
                'path'   => (string)($row['path'] ?? '')
            ];
        }

        return ['has'=>false,'status'=>'NONE','path'=>''];
    }

    /* ================= Data Providers ================= */

    private function getStats(int $uid): array
    {
        $eventIds = [];
        if ($this->tableExists('abstrak')) {
            foreach ($this->db->table('abstrak')->distinct()->select('event_id')->where('id_user',$uid)->get()->getResultArray() as $r) {
                if (!empty($r['event_id'])) $eventIds[(int)$r['event_id']] = true;
            }
        }
        if ($this->tableExists('pembayaran')) {
            foreach ($this->db->table('pembayaran')->distinct()->select('event_id')->where('id_user',$uid)->get()->getResultArray() as $r) {
                if (!empty($r['event_id'])) $eventIds[(int)$r['event_id']] = true;
            }
        }

        $totalLoa = 0;
        $fpTable = $this->tableExists('submissions') ? 'submissions' : ($this->tableExists('abstrak') ? 'abstrak' : null);
        if ($fpTable && $this->colExists($fpTable, 'full_paper_status')) {
            $whereAccepted = "UPPER(full_paper_status) = 'ACCEPTED'";
            $totalLoa = (int)$this->db->table($fpTable)
                ->where('id_user', $uid)
                ->where($whereAccepted, null, false)
                ->countAllResults();
        }

        $todayAbsensi = [];
        if ($this->tableExists('absensi')) {
            $dateCol = $this->pickCol('absensi', ['waktu_scan','scan_time','scanned_at','timestamp','created_at','created_on','waktu']);
            if ($dateCol) {
                $tsExpr = $this->buildCoalesceChecked(
                    'absensi', 'absensi',
                    ['waktu_scan','scan_time','scanned_at','timestamp','created_at','created_on'],
                    'ts'
                );
                $todayAbsensi = $this->db->table('absensi')
                    ->select('event_id, ' . $tsExpr, false)
                    ->where('id_user', $uid)
                    ->where($this->dateExpr('absensi', $dateCol), date('Y-m-d'))
                    ->get()->getResultArray() ?: [];
            }
        }

        return [
            'total_events' => count($eventIds),
            'total_loa'    => $totalLoa,
            'todayAbsensi' => $todayAbsensi,
        ];
    }

    /**
     * Progress events — DISARING: tidak menampilkan event yang sudah mulai/berjalan.
     * Rule: jika event_date ada, event dianggap mulai pada (event_date + event_time | 00:00).
     * Jika startTs <= now => SKIP dari progres.
     */
    private function getProgressEvents(int $uid): array
    {
        if (!$this->tableExists('events')) return [];

        // kumpulkan event yang pernah user sentuh (abstrak/pembayaran)
        $ids = [];
        if ($this->tableExists('abstrak')) {
            foreach ($this->db->table('abstrak')->distinct()->select('event_id')->where('id_user',$uid)->get()->getResultArray() as $r) {
                if (!empty($r['event_id'])) $ids[(int)$r['event_id']] = true;
            }
        }
        if ($this->tableExists('pembayaran')) {
            foreach ($this->db->table('pembayaran')->distinct()->select('event_id')->where('id_user',$uid)->get()->getResultArray() as $r) {
                if (!empty($r['event_id'])) $ids[(int)$r['event_id']] = true;
            }
        }
        if (!$ids) return [];

        // ambil kolom tanggal/waktu yang tersedia
        $dateCol = $this->pickCol('events', ['event_date','tanggal','date','start_at'], 'event_date');
        $timeCol = $this->pickCol('events', ['event_time','waktu','time','start_time'], 'event_time');

        $events = $this->db->table('events')
            ->select("id,title,{$this->db->protectIdentifiers($dateCol)} AS event_date, {$this->db->protectIdentifiers($timeCol)} AS event_time", false)
            ->whereIn('id', array_keys($ids))
            ->orderBy('event_date','DESC')->get()->getResultArray();

        $now = time();
        $out = [];

        foreach ($events as $e) {
            $eventId   = (int)$e['id'];
            $dateStr   = trim((string)($e['event_date'] ?? ''));
            $timeStr   = trim((string)($e['event_time'] ?? ''));
            // anggap event mulai di jam yang tersedia, default 00:00 kalau kosong
            $startTs   = $dateStr ? strtotime($dateStr.' '.($timeStr !== '' ? $timeStr : '00:00:00')) : null;

            // SKIP: event yang sudah mulai / sudah lewat
            if ($startTs !== null && $startTs <= $now) {
                continue;
            }

            // ---- progress logic ----
            $absMeta = $this->getAbstractStatus($uid, $eventId);
            $absHas  = $absMeta['has'];
            $absSt   = strtolower($absMeta['status']);

            $fpEligible = $absHas && $absSt !== 'ditolak';
            $fpMeta     = $this->getFullpaperMeta($uid, $eventId);
            $fpSt       = strtoupper($fpMeta['status'] ?? 'NONE');
            if (!$fpEligible) $fpSt = 'NONE';

            $payVerified=false; $payStatus='';
            if ($this->tableExists('pembayaran')) {
                $pay = $this->db->table('pembayaran')
                    ->select('status,id_pembayaran')
                    ->where('id_user',$uid)->where('event_id',$eventId)
                    ->orderBy('id_pembayaran','DESC')->get()->getRowArray();
                if ($pay){ $payStatus=strtolower((string)$pay['status']); $payVerified=($payStatus==='verified'); }
            }

            $step_kontributor_done = ($absHas || $payStatus!=='');
            $step_abs_done         = $absHas;
            $step_fp_done          = ($fpSt !== 'NONE');
            $step_bayar_done       = ($payStatus!=='');
            $step_finish_done      = $payVerified;

            $current = 'kontributor';
            if ($step_kontributor_done && !$step_abs_done) $current = 'abstrak';
            if ($step_abs_done && !$step_fp_done && $fpEligible)  $current = 'fullpaper';
            if (($step_fp_done || !$fpEligible) && !$step_bayar_done) $current = 'bayar';
            if ($step_bayar_done && !$step_finish_done) $current = 'verifikasi';

            if ($step_finish_done) continue;

            $out[] = [
                'event_id'   => $eventId,
                'title'      => (string)$e['title'],
                'event_date' => $e['event_date'] ?? null,
                'event_time' => $e['event_time'] ?? null,
                'steps'      => [
                    'kontributor'=> $step_kontributor_done ? 'done' : ($current==='kontributor'?'current':'todo'),
                    'abstrak'    => $step_abs_done        ? 'done' : ($current==='abstrak'?'current':'todo'),
                    'fullpaper'  => $fpEligible ? ($step_fp_done ? 'done' : ($current==='fullpaper'?'current':'todo')) : 'disabled',
                    'bayar'      => $step_bayar_done      ? 'done' : ($current==='bayar'?'current':'todo'),
                    'verifikasi' => $step_finish_done     ? 'done' : ($current==='verifikasi'?'current':'todo'),
                ],
                'labels'     => [
                    'abs_status'  => $absSt,
                    'fp_status'   => $fpSt,
                    'pay_status'  => $payStatus,
                    'fp_eligible' => $fpEligible,
                ],
            ];
        }
        return $out;
    }

    private function getTodaySchedule(int $uid): array
    {
        if (!$this->tableExists('events')) return [];
        $today = date('Y-m-d');

        $subA = $this->tableExists('abstrak')
            ? $this->db->table('abstrak')->distinct()->select('event_id')->where('id_user',$uid)->getCompiledSelect()
            : null;
        $subP = $this->tableExists('pembayaran')
            ? $this->db->table('pembayaran')->distinct()->select('event_id')->where('id_user',$uid)->getCompiledSelect()
            : null;

        $dateCol = $this->pickCol('events', ['event_date','tanggal','date','start_at'], 'event_date');
        $timeCol = $this->pickCol('events', ['event_time','waktu','time','start_time'], 'event_time');
        $locCol  = $this->pickCol('events', ['location','lokasi','venue'], 'location');
        $fmtCol  = $this->pickCol('events', ['format','tipe','type'], 'format');

        $b = $this->db->table('events')
            ->select("id,title,{$this->db->protectIdentifiers($timeCol)} AS event_time, {$this->db->protectIdentifiers($locCol)} AS location, {$this->db->protectIdentifiers($fmtCol)} AS format", false)
            ->where($this->dateExpr('events', $dateCol), $today);

        if     ($subA && $subP) $b->where("(id IN ($subA) OR id IN ($subP))", null, false);
        elseif ($subA)          $b->where("id IN ($subA)", null, false);
        elseif ($subP)          $b->where("id IN ($subP)", null, false);
        else return [];

        $rows = $b->orderBy('event_time','ASC')->get()->getResultArray();

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'event_id' => (int)$r['id'],
                'title'    => (string)($r['title'] ?? '-'),
                'start'    => (string)($r['event_time'] ?? ''),
                'where'    => (string)($r['location'] ?? ($r['format'] ?? '')),
                'link'     => '/presenter/absensi/event/' . (int)$r['id'],
            ];
        }
        return $out;
    }

    /** Event bulan berjalan (untuk penandaan kalender) — FIX quote tanggal di PostgreSQL. */
    private function getMonthEvents(int $uid, ?int $year = null, ?int $month = null): array
    {
        if (!$this->tableExists('events')) return [];

        $year  = $year  ?? (int)date('Y');
        $month = $month ?? (int)date('n');

        $start = date('Y-m-01', mktime(0,0,0,$month,1,$year));
        $end   = date('Y-m-t',  mktime(0,0,0,$month,1,$year));

        $subA = $this->tableExists('abstrak')
            ? $this->db->table('abstrak')->distinct()->select('event_id')->where('id_user',$uid)->getCompiledSelect()
            : null;
        $subP = $this->tableExists('pembayaran')
            ? $this->db->table('pembayaran')->distinct()->select('event_id')->where('id_user',$uid)->getCompiledSelect()
            : null;

        if (!$subA && !$subP) return [];

        $dateCol = $this->pickCol('events', ['event_date','tanggal','date','start_at'], 'event_date');
        $timeCol = $this->pickCol('events', ['event_time','waktu','time','start_time'], 'event_time');
        $locCol  = $this->pickCol('events', ['location','lokasi','venue'], 'location');
        $fmtCol  = $this->pickCol('events', ['format','tipe','type'], 'format');

        $dateExprCol = $this->db->protectIdentifiers($dateCol);
        $timeExprCol = $this->db->protectIdentifiers($timeCol);
        $locExprCol  = $this->db->protectIdentifiers($locCol);
        $fmtExprCol  = $this->db->protectIdentifiers($fmtCol);

        $b = $this->db->table('events e')
            ->select("id, title, {$dateExprCol} AS event_date, {$timeExprCol} AS event_time, {$locExprCol} AS location, {$fmtExprCol} AS format", false)
            ->where("$dateExprCol >=", $start)
            ->where("$dateExprCol <=", $end);

        if     ($subA && $subP) $b->where("(id IN ($subA) OR id IN ($subP))", null, false);
        elseif ($subA)          $b->where("id IN ($subA)", null, false);
        elseif ($subP)          $b->where("id IN ($subP)", null, false);

        $rows = $b->orderBy('event_date','ASC')->orderBy('event_time','ASC')->get()->getResultArray();

        $out = [];
        foreach ($rows as $r) {
            $ymd = substr((string)$r['event_date'], 0, 10);
            $out[$ymd] ??= [];
            $out[$ymd][] = [
                'id'    => (int)$r['id'],
                'title' => (string)($r['title'] ?? '-'),
                'time'  => (string)($r['event_time'] ?? ''),
                'where' => (string)($r['location'] ?? ($r['format'] ?? '')),
                'link'  => '/presenter/absensi/event/' . (int)$r['id'],
            ];
        }
        return $out;
    }

    private function getActivities(int $uid, int $limit = 12): array
    {
        $items = [];

        if ($this->tableExists('pembayaran')) {
            $tsPay = $this->buildCoalesceChecked('pembayaran','p',
                ['updated_at','tanggal_bayar','created_at','tanggal_transaksi'],
                'ts'
            );
            $rows = $this->db->table('pembayaran p')
                ->select("p.event_id, p.jumlah, {$tsPay}, e.title", false)
                ->join('events e','e.id=p.event_id','left')
                ->where('p.id_user',$uid)->where('p.status','verified')
                ->orderBy('ts','DESC')->get()->getResultArray();
            foreach ($rows as $r) {
                $items[] = [
                    'time'  => !empty($r['ts']) ? strtotime((string)$r['ts']) : 0,
                    'title' => 'Pembayaran diverifikasi',
                    'desc'  => 'Event: ' . (string)($r['title'] ?? '-'),
                    'link'  => '/presenter/pembayaran',
                    'badge' => 'success',
                    'icon'  => 'bi-check2-circle',
                ];
            }
        }

        if ($this->tableExists('abstrak')) {
            $tsAbs = $this->buildCoalesceChecked('abstrak','a',
                ['updated_at','tanggal_upload','created_at'],
                'ts'
            );
            $rows = $this->db->table('abstrak a')
                ->select("a.status, {$tsAbs}, e.title", false)
                ->join('events e','e.id=a.event_id','left')
                ->where('a.id_user',$uid)
                ->orderBy('ts','DESC')->get()->getResultArray();
            foreach ($rows as $r) {
                $st = strtolower((string)($r['status'] ?? ''));
                $map = [
                    'diterima' => ['Abstrak diterima','success','bi-patch-check'],
                    'revisi'   => ['Abstrak perlu revisi','warning','bi-pencil-square'],
                    'ditolak'  => ['Abstrak ditolak','danger','bi-x-circle'],
                ];
                if (!isset($map[$st])) continue;
                [$title,$badge,$icon] = $map[$st];
                $items[] = [
                    'time'  => !empty($r['ts']) ? strtotime((string)$r['ts']) : 0,
                    'title' => $title,
                    'desc'  => 'Event: ' . (string)($r['title'] ?? '-'),
                    'link'  => '/presenter/abstrak',
                    'badge' => $badge,
                    'icon'  => $icon,
                ];
            }
        }

        if ($this->tableExists('events')) {
            $since = date('Y-m-d', strtotime('-14 days'));
            $tsEvt = $this->buildCoalesceChecked('events','events',
                ['created_at','updated_at','event_date'],
                'ts'
            );
            $rows = $this->db->table('events')
                ->select("id, title, {$tsEvt}", false)
                ->where('event_date >=', $since)
                ->orderBy('ts','DESC')->limit(10)->get()->getResultArray();
            foreach ($rows as $r) {
                $items[] = [
                    'time'  => !empty($r['ts']) ? strtotime((string)$r['ts']) : 0,
                    'title' => 'Event baru: ' . (string)$r['title'],
                    'desc'  => '',
                    'link'  => '/presenter/events/detail/' . (int)$r['id'],
                    'badge' => 'primary',
                    'icon'  => 'bi-stars',
                ];
            }
        }

        usort($items, fn($a,$b) => ($b['time'] <=> $a['time']));
        if ($limit > 0) $items = array_slice($items, 0, $limit);
        return $items;
    }

    /* ================= Page ================= */

    public function dashboard()
    {
        $uid = $this->uid();
        if ($uid <= 0) return redirect()->to('/auth/login');

        $stats          = $this->getStats($uid);
        $progressEvents = $this->getProgressEvents($uid);
        $todaySchedule  = $this->getTodaySchedule($uid);
        $activities     = $this->getActivities($uid);
        $monthEvents    = $this->getMonthEvents($uid);

        return view('role/presenter/dashboard', [
            'title'          => 'Dashboard Presenter',
            'stats'          => ['total_events'=>$stats['total_events'],'total_loa'=>$stats['total_loa']],
            'todayAbsensi'   => $stats['todayAbsensi'],
            'progressEvents' => $progressEvents,
            'todaySchedule'  => $todaySchedule,
            'activities'     => $activities,
            'monthEvents'    => $monthEvents,
        ]);
    }

    public function index() { return $this->dashboard(); }
}
