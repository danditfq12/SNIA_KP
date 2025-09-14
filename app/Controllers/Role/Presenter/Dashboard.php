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
    }

    /* ========== Utils ========== */

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

    /**
     * Bangun COALESCE yang HANYA berisi kolom yang benar-benar ada pada tabel fisik.
     * $tablePhys  = nama tabel fisik di DB (untuk cek kolom)
     * $tableAlias = alias/nama yang dipakai di SELECT (bisa sama dengan fisik)
     * $cands      = kandidat kolom (berurutan prioritas)
     * $alias      = nama alias untuk hasil COALESCE
     */
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

    /* ========== Data Providers ========== */

    private function getStats(int $uid): array
    {
        $eventIds = [];

        if ($this->tableExists('abstrak')) {
            $eids = $this->db->table('abstrak')
                ->distinct()->select('event_id')
                ->where('id_user', $uid)->get()->getResultArray();
            foreach ($eids as $r) if (!empty($r['event_id'])) $eventIds[(int)$r['event_id']] = true;
        }
        if ($this->tableExists('pembayaran')) {
            $eids = $this->db->table('pembayaran')
                ->distinct()->select('event_id')
                ->where('id_user', $uid)->get()->getResultArray();
            foreach ($eids as $r) if (!empty($r['event_id'])) $eventIds[(int)$r['event_id']] = true;
        }

        $totalEvents  = count($eventIds);
        $totalAbstrak = 0;

        if ($this->tableExists('abstrak')) {
            $totalAbstrak = (int)$this->db->table('abstrak')->where('id_user', $uid)->countAllResults();
        }

        $todayAbsensi = [];
        if ($this->tableExists('absensi')) {
            // cari kolom tanggal yang ada
            $dateCol = null;
            foreach (['waktu_scan','scan_time','scanned_at','timestamp','created_at','created_on','waktu'] as $c) {
                if ($this->colExists('absensi', $c)) { $dateCol = $c; break; }
            }
            if ($dateCol) {
                $tsExpr = $this->buildCoalesceChecked(
                    'absensi', 'absensi',
                    ['waktu_scan','scan_time','scanned_at','timestamp','created_at','created_on'],
                    'ts'
                );
                $rows = $this->db->table('absensi')
                    ->select('event_id, ' . $tsExpr, false)
                    ->where('id_user', $uid)
                    ->where($this->dateExpr('absensi', $dateCol), date('Y-m-d'))
                    ->get()->getResultArray();
                $todayAbsensi = $rows ?: [];
            }
        }

        return [
            'total_events'  => $totalEvents,
            'total_abstrak' => $totalAbstrak,
            'todayAbsensi'  => $todayAbsensi,
        ];
    }

    private function getRegistrations(int $uid): array
    {
        if (!$this->tableExists('events')) return [];

        $ids = [];
        if ($this->tableExists('abstrak')) {
            $rows = $this->db->table('abstrak')
                ->distinct()->select('event_id')
                ->where('id_user',$uid)->get()->getResultArray();
            foreach ($rows as $r) if (!empty($r['event_id'])) $ids[(int)$r['event_id']] = true;
        }
        if ($this->tableExists('pembayaran')) {
            $rows = $this->db->table('pembayaran')
                ->distinct()->select('event_id')
                ->where('id_user',$uid)->get()->getResultArray();
            foreach ($rows as $r) if (!empty($r['event_id'])) $ids[(int)$r['event_id']] = true;
        }
        if (!$ids) return [];

        $rows = $this->db->table('events')
            ->select('id, title, event_date, event_time, format')
            ->whereIn('id', array_keys($ids))
            ->orderBy('event_date','DESC')->get()->getResultArray();

        $out = [];
        foreach ($rows as $e) {
            $accepted = false; $verified = false;

            if ($this->tableExists('abstrak')) {
                $accepted = $this->db->table('abstrak')
                    ->where('id_user',$uid)->where('event_id',$e['id'])
                    ->where('status','diterima')->countAllResults() > 0;
            }
            if ($this->tableExists('pembayaran')) {
                $verified = $this->db->table('pembayaran')
                    ->where('id_user',$uid)->where('event_id',$e['id'])
                    ->where('status','verified')->countAllResults() > 0;
            }

            $status = $accepted && $verified ? 'Selesai' : ($accepted ? 'Abstrak Diterima' : 'Terdaftar');

            $out[] = [
                'id'          => (int)$e['id'],
                'title'       => (string)$e['title'],
                'event_title' => (string)$e['title'],
                'status'      => $status,
            ];
        }
        return $out;
    }

    private function getProgressEvents(int $uid): array
    {
        if (!$this->tableExists('events')) return [];

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

        $events = $this->db->table('events')->select('id,title,event_date,event_time')
            ->whereIn('id', array_keys($ids))->orderBy('event_date','DESC')->get()->getResultArray();

        $out = [];
        foreach ($events as $e) {
            $eventId = (int)$e['id'];

            $abs = null; $absAccepted = false; $hasAbs = false;
            if ($this->tableExists('abstrak')) {
                $abs = $this->db->table('abstrak')
                    ->select('status')->where('id_user',$uid)->where('event_id',$eventId)
                    ->orderBy('id_abstrak','DESC')->get()->getRowArray();
                if ($abs) {
                    $hasAbs      = true;
                    $absAccepted = strtolower((string)$abs['status']) === 'diterima';
                }
            }

            $pay = null; $hasPay = false; $payVerified = false;
            if ($this->tableExists('pembayaran')) {
                $pay = $this->db->table('pembayaran')
                    ->select('status')->where('id_user',$uid)->where('event_id',$eventId)
                    ->orderBy('id_pembayaran','DESC')->get()->getRowArray();
                if ($pay) {
                    $hasPay      = true;
                    $payVerified = strtolower((string)$pay['status']) === 'verified';
                }
            }

            // Selesai? skip dari progress
            if ($absAccepted && $payVerified) continue;

            $out[] = [
                'event_id' => $eventId,
                'title'    => (string)$e['title'],
                'steps'    => [
                    'abstrak'    => $hasAbs,
                    'review'     => $abs ? (strtolower((string)$abs['status']) !== 'menunggu' && strtolower((string)$abs['status']) !== 'sedang_direview') : false,
                    'bayar'      => $hasPay,
                    'verifikasi' => $payVerified,
                ],
                'hint'     => $absAccepted ? 'Lanjutkan pembayaran & verifikasi.' : 'Menunggu proses abstrak / pembayaran.',
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

        $b = $this->db->table('events')
            ->select('id,title,event_time,location,format')
            ->where($this->dateExpr('events', 'event_date'), $today);

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

        // Abstrak status
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

        // Terdaftar (saat pembayaran dibuat)
        if ($this->tableExists('pembayaran')) {
            $tsReg = $this->buildCoalesceChecked('pembayaran','p',
                ['created_at','tanggal_bayar','updated_at','tanggal_transaksi'],
                'ts'
            );
            $rows = $this->db->table('pembayaran p')
                ->select("p.event_id, {$tsReg}, e.title", false)
                ->join('events e','e.id=p.event_id','left')
                ->where('p.id_user',$uid)
                ->orderBy('ts','DESC')->get()->getResultArray();
            foreach ($rows as $r) {
                $items[] = [
                    'time'  => !empty($r['ts']) ? strtotime((string)$r['ts']) : 0,
                    'title' => 'Anda terdaftar di event',
                    'desc'  => 'Event: ' . (string)($r['title'] ?? '-'),
                    'link'  => '/presenter/events/detail/' . (int)$r['event_id'],
                    'badge' => 'info',
                    'icon'  => 'bi-person-check',
                ];
            }
        }

        // Event baru (14 hari terakhir)
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

    /* ========== Page ========== */

    public function dashboard()
    {
        $uid = $this->uid();
        if ($uid <= 0) return redirect()->to('/auth/login');

        $stats          = $this->getStats($uid);
        $registrations  = $this->getRegistrations($uid);
        $progressEvents = $this->getProgressEvents($uid);
        $todaySchedule  = $this->getTodaySchedule($uid);
        $activities     = $this->getActivities($uid);

        $abstrak = [];
        if ($this->tableExists('abstrak')) {
            $abstrak = $this->db->table('abstrak a')
                ->select('a.id_abstrak,a.judul,a.status,a.tanggal_upload,e.title AS event_title')
                ->join('events e','e.id=a.event_id','left')
                ->where('a.id_user',$uid)
                ->orderBy('a.id_abstrak','DESC')->get()->getResultArray();
        }

        return view('role/presenter/dashboard', [
            'title'          => 'Dashboard Presenter',
            'stats'          => ['total_events'=>$stats['total_events'],'total_abstrak'=>$stats['total_abstrak']],
            'todayAbsensi'   => $stats['todayAbsensi'],
            'registrations'  => $registrations,
            'progressEvents' => $progressEvents,
            'todaySchedule'  => $todaySchedule,
            'activities'     => $activities,
            'abstrak'        => $abstrak,
        ]);
    }

    public function index() { return $this->dashboard(); }
}