<?php

namespace App\Controllers\Role\Audience;

use App\Controllers\BaseController;
use Config\Database;

class Dashboard extends BaseController
{
    protected \CodeIgniter\Database\BaseConnection $db;
    private array $colsCache = [];
    protected \DateTimeZone $tz;

    public function __construct()
    {
        $this->db = Database::connect();
        $this->tz = new \DateTimeZone(config('App')->appTimezone ?? 'Asia/Jakarta');
    }

    /* =============== Utils (samakan gaya Presenter) =============== */

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

    private function normalizeTime(?string $t): ?string
    {
        if (!$t) return null;
        $t = trim($t);
        if (preg_match('/^\d{2}:\d{2}$/', $t)) return $t . ':00';
        return $t;
    }

    private function composeStart(?string $eventDate, ?string $eventTime): ?string
    {
        if (!$eventDate || !$eventTime) return null;
        return $eventDate . ' ' . $this->normalizeTime($eventTime);
    }

    private function calculateEventStatus(array $event): array
    {
        if (empty($event['event_date']) || empty($event['event_time'])) {
            return ['event_status'=>'Jadwal Tidak Lengkap','badge_class'=>'bg-secondary','can_scan'=>false];
        }
        if (isset($event['is_active']) && !$event['is_active']) {
            return ['event_status'=>'Tidak Aktif','badge_class'=>'bg-secondary','can_scan'=>false];
        }

        $startStr = $this->composeStart($event['event_date'], $event['event_time']);
        try {
            $start = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $startStr, $this->tz)
                     ?: new \DateTimeImmutable($startStr, $this->tz);
        } catch (\Throwable $e) {
            return ['event_status'=>'Jadwal Tidak Valid','badge_class'=>'bg-secondary','can_scan'=>false];
        }

        $now   = new \DateTimeImmutable('now', $this->tz);
        $diffH = ($now->getTimestamp() - $start->getTimestamp()) / 3600.0;

        if ($diffH < 0)  return ['event_status'=>'Belum Dimulai','badge_class'=>'bg-secondary','can_scan'=>false];
        if ($diffH <= 4) return ['event_status'=>'Sedang Berlangsung','badge_class'=>'bg-success','can_scan'=>true];
        return ['event_status'=>'Sudah Selesai','badge_class'=>'bg-secondary','can_scan'=>false];
    }

    /* =============== Data Providers (diset seperti Presenter) =============== */

    /** KPI ringkas */
    private function getStats(int $uid): array
    {
        // total event yang punya pembayaran (unik)
        $totalEvents = 0;
        if ($this->tableExists('pembayaran')) {
            $rows = $this->db->table('pembayaran')
                ->distinct()->select('event_id')->where('id_user',$uid)->get()->getResultArray();
            $totalEvents = count($rows ?: []);
        }

        // total sertifikat (case-insensitive tipe)
        $totalCerts = 0;
        if ($this->tableExists('dokumen')) {
            $b = $this->db->table('dokumen')->where('id_user',$uid);
            if ($this->colExists('dokumen','tipe')) {
                $b->groupStart()
                    ->where('tipe','sertifikat')->orWhere('tipe','Sertifikat')
                    ->orWhere('tipe','CERTIFICATE')->orWhere('tipe','Certificate')
                ->groupEnd();
            }
            $totalCerts = (int)$b->countAllResults();
        }

        // absensi hari ini
        $todayAbsensi = [];
        if ($this->tableExists('absensi')) {
            $dateCol = null;
            foreach (['waktu_scan','scan_time','scanned_at','timestamp','created_at','created_on','waktu'] as $c) {
                if ($this->colExists('absensi', $c)) { $dateCol = $c; break; }
            }
            if ($dateCol) {
                $tsExpr = $this->buildCoalesceChecked(
                    'absensi', 'a',
                    ['waktu_scan','scan_time','scanned_at','timestamp','created_at','created_on'],
                    'ts'
                );
                $rows = $this->db->table('absensi a')
                    ->select('a.event_id, ' . $tsExpr, false)
                    ->where('a.id_user', $uid)
                    ->where($this->dateExpr('a', $dateCol), date('Y-m-d'))
                    ->get()->getResultArray();
                $todayAbsensi = $rows ?: [];
            }
        }

        return [
            'total_events'  => $totalEvents,
            'total_certs'   => $totalCerts,
            'todayAbsensi'  => $todayAbsensi,
        ];
    }

    /** Event yang user ikuti (basis: pembayaran) */
    private function getRegistrations(int $uid): array
    {
        if (!$this->tableExists('events') || !$this->tableExists('pembayaran')) return [];

        $rows = $this->db->table('pembayaran p')
            ->select('p.event_id, p.status, e.title, e.event_date, e.event_time, e.format')
            ->join('events e','e.id=p.event_id','left')
            ->where('p.id_user',$uid)
            ->orderBy('p.id_pembayaran','DESC')
            ->get()->getResultArray();

        if (!$rows) return [];

        // Ambil status terakhir per event
        $latest = [];
        foreach ($rows as $r) {
            $eid = (int)$r['event_id'];
            if (!isset($latest[$eid])) $latest[$eid] = $r; // pertama dianggap paling baru karena order DESC
        }

        $out = [];
        foreach ($latest as $r) {
            $st = strtolower((string)($r['status'] ?? ''));
            // mapping label
            $map = [
                'verified' => ['Terverifikasi','success','complete'],
                'pending'  => ['Menunggu Verifikasi','warning','registered'],
                'rejected' => ['Ditolak','danger','rejected'],
            ];
            [$label,$badge,$code] = $map[$st] ?? ['Terdaftar','secondary','registered'];

            $out[] = [
                'id'          => (int)$r['event_id'],
                'title'       => (string)($r['title'] ?? '-'),
                'event_title' => (string)($r['title'] ?? '-'),
                'event_date'  => $r['event_date'] ?? null,
                'event_time'  => $r['event_time'] ?? null,
                'status_code' => $code,
                'status'      => $label,
                'badge'       => $badge,
            ];
        }
        return $out;
    }

    /** Progress cuma 3 langkah: daftar → bayar → verifikasi */
    private function getProgressEvents(int $uid): array
    {
        if (!$this->tableExists('events') || !$this->tableExists('pembayaran')) return [];

        // ambil pembayaran terakhir per event
        $rows = $this->db->table('pembayaran p')
            ->select('p.event_id, p.status, e.title, e.event_date, e.event_time')
            ->join('events e','e.id=p.event_id','left')
            ->where('p.id_user',$uid)
            ->orderBy('p.id_pembayaran','DESC')
            ->get()->getResultArray();

        if (!$rows) return [];
        $seen = []; $list = [];
        foreach ($rows as $r) {
            $eid = (int)$r['event_id'];
            if (isset($seen[$eid])) continue;
            $seen[$eid] = true;

            $st = strtolower((string)($r['status'] ?? ''));
            $step_daftar_done = true;                 // ada record pembayaran → sudah daftar
            $step_bayar_done  = in_array($st, ['pending','verified','rejected'], true);
            $step_verif_done  = ($st === 'verified');

            if ($step_verif_done) continue; // sudah complete → sembunyikan (sama dengan Presenter)

            $current = 'daftar';
            if ($step_daftar_done && !$step_bayar_done) $current = 'bayar';
            if ($step_bayar_done  && !$step_verif_done) $current = 'verifikasi';

            $list[] = [
                'event_id'   => $eid,
                'title'      => (string)($r['title'] ?? '-'),
                'event_date' => $r['event_date'] ?? null,
                'event_time' => $r['event_time'] ?? null,
                'steps'      => [
                    'daftar'     => $step_daftar_done ? 'done' : ($current==='daftar'?'current':'todo'),
                    'bayar'      => $step_bayar_done  ? 'done' : ($current==='bayar'?'current':'todo'),
                    'verifikasi' => $step_verif_done  ? 'done' : ($current==='verifikasi'?'current':'todo'),
                ],
                'labels'     => [
                    'pay_status' => $st,
                ],
            ];
        }
        return $list;
    }

    /** Jadwal hari ini untuk event yang user ikuti (basis pembayaran) */
    private function getTodaySchedule(int $uid): array
    {
        if (!$this->tableExists('events') || !$this->tableExists('pembayaran')) return [];
        $today = date('Y-m-d');

        $subP = $this->db->table('pembayaran')->distinct()->select('event_id')->where('id_user',$uid)->getCompiledSelect();

        $rows = $this->db->table('events')
            ->select('id,title,event_time,location,format')
            ->where($this->dateExpr('events','event_date'), $today)
            ->where("id IN ($subP)", null, false)
            ->orderBy('event_time','ASC')->get()->getResultArray();

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'event_id' => (int)$r['id'],
                'title'    => (string)($r['title'] ?? '-'),
                'start'    => (string)($r['event_time'] ?? ''),
                'where'    => (string)($r['location'] ?? ($r['format'] ?? '')),
                'link'     => '/audience/absensi/event/' . (int)$r['id'],
            ];
        }
        return $out;
    }

    /** Timeline aktivitas (versi audience) */
    private function getActivities(int $uid, int $limit = 12): array
    {
        $items = [];

        // Pembayaran verified
        if ($this->tableExists('pembayaran')) {
            $tsPay = $this->buildCoalesceChecked('pembayaran','p',
                ['updated_at','tanggal_bayar','created_at','tanggal_transaksi'],
                'ts'
            );
            $rows = $this->db->table('pembayaran p')
                ->select("p.event_id, p.jumlah, p.status, {$tsPay}, e.title", false)
                ->join('events e','e.id=p.event_id','left')
                ->where('p.id_user',$uid)
                ->orderBy('ts','DESC')->get()->getResultArray();

            foreach ($rows as $r) {
                $st = strtolower((string)$r['status']);
                if ($st === 'verified') {
                    $items[] = [
                        'time'  => !empty($r['ts']) ? strtotime((string)$r['ts']) : 0,
                        'title' => 'Pembayaran diverifikasi',
                        'desc'  => 'Event: ' . (string)($r['title'] ?? '-'),
                        'link'  => '/audience/pembayaran',
                        'badge' => 'success',
                        'icon'  => 'bi-check2-circle',
                    ];
                } elseif ($st === 'pending') {
                    $items[] = [
                        'time'  => !empty($r['ts']) ? strtotime((string)$r['ts']) : 0,
                        'title' => 'Menunggu verifikasi pembayaran',
                        'desc'  => 'Event: ' . (string)($r['title'] ?? '-'),
                        'link'  => '/audience/pembayaran',
                        'badge' => 'warning',
                        'icon'  => 'bi-hourglass-split',
                    ];
                } elseif ($st === 'rejected') {
                    $items[] = [
                        'time'  => !empty($r['ts']) ? strtotime((string)$r['ts']) : 0,
                        'title' => 'Pembayaran ditolak',
                        'desc'  => 'Event: ' . (string)($r['title'] ?? '-'),
                        'link'  => '/audience/pembayaran',
                        'badge' => 'danger',
                        'icon'  => 'bi-x-circle',
                    ];
                }
            }
        }

        // Event baru (2 minggu)
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
                    'link'  => '/audience/events/detail/' . (int)$r['id'],
                    'badge' => 'primary',
                    'icon'  => 'bi-stars',
                ];
            }
        }

        usort($items, fn($a,$b) => ($b['time'] <=> $a['time']));
        if ($limit > 0) $items = array_slice($items, 0, $limit);
        return $items;
    }

    /* =============== Page =============== */

    public function dashboard()
    {
        $uid = $this->uid();
        if ($uid <= 0 || session('role') !== 'audience') return redirect()->to('/auth/login');

        // ==== gaya Presenter ====
        $stats          = $this->getStats($uid);
        $registrations  = $this->getRegistrations($uid);
        $progressEvents = $this->getProgressEvents($uid);
        $todaySchedule  = $this->getTodaySchedule($uid);
        $activities     = $this->getActivities($uid);

        // ==== kompatibel dengan view Audience lama ====
        // deteksi kolom zoom/meeting link
        $zoomCol = null;
        if ($this->tableExists('events')) {
            foreach (['zoom_link','meeting_link','online_link'] as $cand) {
                if ($this->colExists('events', $cand)) { $zoomCol = $cand; break; }
            }
        }

        // list event dengan payment verified (>= kemarin)
        $upcomingPaid = [];
        if ($this->tableExists('pembayaran') && $this->tableExists('events')) {
            $cols = 'e.id, e.title, e.event_date, e.event_time, e.format, e.location, e.is_active, p.participation_type AS mode_kehadiran';
            if ($zoomCol) $cols .= ', e.'.$zoomCol.' AS zoom_link';

            $upcomingPaid = $this->db->table('pembayaran p')
                ->select($cols, false)
                ->join('events e','e.id=p.event_id','left')
                ->where('p.id_user',$uid)
                ->where('p.status','verified')
                ->where('e.is_active', true)
                ->where('e.event_date >=', date('Y-m-d', strtotime('-1 day')))
                ->orderBy('e.event_date','ASC')->orderBy('e.event_time','ASC')
                ->get()->getResultArray() ?: [];
        }

        // map "sudah absen"
        $absenToday = [];
        if ($upcomingPaid) {
            $eventIds = array_map(fn($r)=>(int)$r['id'], $upcomingPaid);
            $lastScanMap = [];
            if ($this->tableExists('absensi') && $eventIds) {
                $rows = $this->db->table('absensi')
                    ->select('event_id, MAX(waktu_scan) AS last_scan', false)
                    ->where('id_user', $uid)->whereIn('event_id', $eventIds)
                    ->groupBy('event_id')->get()->getResultArray();
                foreach ($rows as $r) $lastScanMap[(int)$r['event_id']] = $r['last_scan'] ?? null;
            }

            $todayYmd = date('Y-m-d');
            foreach ($upcomingPaid as &$ev) {
                $status = $this->calculateEventStatus($ev);
                $eid    = (int)$ev['id'];
                $attAt  = $lastScanMap[$eid] ?? null;

                $ev['already_attend'] = $attAt !== null;
                $ev['attendance_at']  = $attAt;
                $ev['can_scan']       = !$ev['already_attend'] && $status['can_scan'];
                $ev['badge_class']    = $status['badge_class'];
                $ev['event_status']   = $status['event_status'];

                if (($ev['event_date'] ?? '') === $todayYmd) {
                    $absenToday[] = $ev;
                }
            }
            unset($ev);
        }

        // pembayaran pending (untuk kartu reminder)
        $pendingPays = [];
        $eventMap    = [];
        if ($this->tableExists('pembayaran')) {
            $pendingPays = $this->db->table('pembayaran')
                ->select('id_pembayaran, event_id, jumlah, tanggal_bayar, status')
                ->where('id_user',$uid)->where('status','pending')
                ->orderBy('tanggal_bayar','DESC')->get()->getResultArray() ?: [];
            if ($pendingPays && $this->tableExists('events')) {
                $ids = array_unique(array_column($pendingPays, 'event_id'));
                if ($ids) {
                    $rows = $this->db->table('events')->select('id,title,event_date,event_time')
                        ->whereIn('id',$ids)->get()->getResultArray();
                    foreach ($rows as $r) $eventMap[(int)$r['id']] = $r;
                }
            }
        }

        // KPI lama (tetap disediakan)
        $kpis = [
            'joined' => ($this->tableExists('pembayaran'))
                ? (int)$this->db->table('pembayaran')->where('id_user',$uid)->where('status','verified')->countAllResults()
                : 0,
            'upcoming' => ($this->tableExists('pembayaran') && $this->tableExists('events'))
                ? (int)$this->db->table('pembayaran p')->join('events e','e.id=p.event_id')
                    ->where('p.id_user',$uid)->where('p.status','verified')
                    ->where('e.event_date >=', date('Y-m-d'))->countAllResults()
                : 0,
            'today' => ($this->tableExists('pembayaran') && $this->tableExists('events'))
                ? (int)$this->db->table('pembayaran p')->join('events e','e.id=p.event_id')
                    ->where('p.id_user',$uid)->where('p.status','verified')
                    ->where('e.event_date', date('Y-m-d'))->countAllResults()
                : 0,
            'certs' => $stats['total_certs'] ?? 0,
        ];

        return view('role/audience/dashboard', [
            'title'          => 'Dashboard Audience',

            // gaya Presenter (kalau mau migrasi view)
            'stats'          => ['total_events'=>$stats['total_events'], 'total_certs'=>$stats['total_certs']],
            'todayAbsensi'   => $stats['todayAbsensi'],
            'registrations'  => $registrations,
            'progressEvents' => $progressEvents,
            'todaySchedule'  => $todaySchedule,
            'activities'     => $activities,

            // kompatibilitas dengan view Audience yang lama
            'upcomingPaid'   => $upcomingPaid,
            'pendingPays'    => $pendingPays,
            'eventMap'       => $eventMap,
            'kpis'           => $kpis,
            'absenToday'     => $absenToday,
        ]);
    }

    public function index() { return $this->dashboard(); }
}