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

    /** COALESCE(expr...) + alias (untuk SELECT biasa). */
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

    /** COALESCE(expr...) tanpa alias (aman dipakai dalam MIN/MAX). */
    private function buildCoalesceExpr(string $tablePhys, string $tableAlias, array $cands): string
    {
        $ok = [];
        foreach ($cands as $c) {
            if ($this->colExists($tablePhys, $c)) {
                $ok[] = $this->db->protectIdentifiers("$tableAlias.$c");
            }
        }
        if (!$ok) return 'NULL';
        return count($ok) === 1 ? $ok[0] : 'COALESCE(' . implode(',', $ok) . ')';
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
     * Event yang masih proses (belum dimulai).
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
            $startTs   = $dateStr ? strtotime($dateStr.' '.($timeStr !== '' ? $timeStr : '00:00:00')) : null;

            if ($startTs !== null && $startTs <= $now) {
                continue;
            }

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

    /* ====== ABSENSI: hanya untuk event yang SUDAH DIBAYAR ====== */
    private function getTodaySchedule(int $uid): array
    {
        if (!$this->tableExists('events')) return [];
        $today = date('Y-m-d');

        // WAJIB ada pembayaran & status OK
        if (!$this->tableExists('pembayaran')) return [];
        $statusOk = ['verified','paid','lunas'];
        $subPay = $this->db->table('pembayaran')
            ->distinct()
            ->select('event_id')
            ->where('id_user', $uid)
            ->whereIn('LOWER(status)', $statusOk)
            ->getCompiledSelect();

        $dateCol = $this->pickCol('events', ['event_date','tanggal','date','start_at'], 'event_date');
        $timeCol = $this->pickCol('events', ['event_time','waktu','time','start_time'], 'event_time');
        $locCol  = $this->pickCol('events', ['location','lokasi','venue'], 'location');
        $fmtCol  = $this->pickCol('events', ['format','tipe','type'], 'format');

        $rows = $this->db->table('events')
            ->select("id,title,{$this->db->protectIdentifiers($timeCol)} AS event_time, {$this->db->protectIdentifiers($locCol)} AS location, {$this->db->protectIdentifiers($fmtCol)} AS format", false)
            ->where($this->dateExpr('events', $dateCol), $today)
            ->where("id IN ($subPay)", null, false)
            ->orderBy('event_time','ASC')
            ->get()->getResultArray();

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

    /** Event bulan berjalan (untuk penandaan kalender). */
    private function getMonthEvents(int $uid, ?int $year = null, ?int $month = null): array
    {
        if (!$this->tableExists('events')) return [];

        $year  = $year  ?? (int)date('Y');
        $month = $month ?? (int)date('n');

        $start = date('Y-m-01', mktime(0,0,0,$month,1,$year));
        $end   = date('Y-m-t',  mktime(0,0,0,$month,1,$year));

        // tampilkan semua event yg user ikuti (abstrak/pembayaran), kalender tidak wajib disaring paid
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

    /* ====== Pendaftaran / Registrasi per event ====== */

    /**
     * Cari waktu "terdaftar" pada suatu event untuk user:
     * - Prioritas: tabel pendaftaran (registrations/peserta_event/enrollments/pendaftaran).
     * - Fallback: MIN(ts) dari unggahan abstrak user per event.
     */
    private function getRegistrationEvents(int $uid): array
    {
        $out = [];

        // kandidat tabel pendaftaran
        $cand = ['registrations','peserta_event','enrollments','pendaftaran'];
        foreach ($cand as $t) {
            if (!$this->tableExists($t)) continue;

            $pkEvent = $this->pickCol($t, ['event_id','id_event'],'event_id');
            $pkUser  = $this->pickCol($t, ['user_id','id_user'],'id_user');
            $tsCol   = $this->pickCol($t, ['created_at','registered_at','enrolled_at','tanggal_daftar','timestamp'],'created_at');

            $rows = $this->db->table($t)
                ->select("$pkEvent AS event_id, MIN($tsCol) AS ts", false)
                ->where($pkUser, $uid)
                ->groupBy($pkEvent)
                ->get()->getResultArray() ?: [];

            foreach ($rows as $r) {
                if (!empty($r['event_id']) && !empty($r['ts'])) {
                    $out[(int)$r['event_id']] = [
                        'event_id' => (int)$r['event_id'],
                        'time'     => strtotime((string)$r['ts']),
                    ];
                }
            }
            if ($out) return $out; // sudah ketemu di tabel pendaftaran
        }

        // Fallback: pakai waktu abstrak paling awal per event
        if ($this->tableExists('abstrak')) {
            $tsExpr = $this->buildCoalesceExpr('abstrak','a', ['created_at','tanggal_upload','updated_at']);

            $rows = $this->db->table('abstrak a')
                ->select("event_id, MIN($tsExpr) AS ts", false)
                ->where('id_user', $uid)
                ->groupBy('event_id')
                ->get()->getResultArray();

            foreach ($rows as $r) {
                if (!empty($r['event_id']) && !empty($r['ts'])) {
                    $out[(int)$r['event_id']] = [
                        'event_id' => (int)$r['event_id'],
                        'time'     => strtotime((string)$r['ts']),
                    ];
                }
            }
        }

        return $out;
    }

    /* ====== Aktivitas / Notifikasi (lengkap, termasuk “Daftar Event” & “Upload Abstrak”) ====== */
    private function getActivities(int $uid, int $limit = 12): array
    {
        $items = [];

        // Pembayaran diverifikasi
        if ($this->tableExists('pembayaran')) {
            $tsPay = $this->buildCoalesceChecked('pembayaran','p',
                ['updated_at','tanggal_bayar','created_at','tanggal_transaksi'],
                'ts'
            );
            $rows = $this->db->table('pembayaran p')
                ->select("p.event_id, p.jumlah, LOWER(p.status) AS status, {$tsPay}, e.title", false)
                ->join('events e','e.id=p.event_id','left')
                ->where('p.id_user',$uid)
                ->whereIn('LOWER(p.status)', ['verified','paid','lunas'])
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

        // ===== Abstrak: "berhasil diunggah" (setiap unggahan) + status final =====
        if ($this->tableExists('abstrak')) {
            // 1) unggahan abstrak
            $tsUp = $this->buildCoalesceChecked('abstrak','a',
                ['tanggal_upload','created_at','updated_at'],
                'ts'
            );
            $rowsUp = $this->db->table('abstrak a')
                ->select("a.event_id, {$tsUp}, e.title", false)
                ->join('events e','e.id=a.event_id','left')
                ->where('a.id_user',$uid)
                ->orderBy('ts','DESC')->get()->getResultArray();
            foreach ($rowsUp as $r) {
                if (empty($r['ts'])) continue;
                $items[] = [
                    'time'  => strtotime((string)$r['ts']),
                    'title' => 'Abstrak berhasil diunggah',
                    'desc'  => 'Event: ' . (string)($r['title'] ?? '-'),
                    'link'  => '/presenter/abstrak',
                    'badge' => 'primary',
                    'icon'  => 'bi-upload',
                ];
            }

            // 2) status abstrak (accepted/revisi/rejected)
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

        // ===== Terdaftar di event (pendaftaran) =====
        $reg = $this->getRegistrationEvents($uid);
        if ($reg) {
            // ambil judul event
            $titles = [];
            if ($this->tableExists('events')) {
                $ids = array_map(fn($x)=>$x['event_id'], $reg);
                if ($ids) {
                    foreach ($this->db->table('events')->select('id,title')->whereIn('id',$ids)->get()->getResultArray() as $e) {
                        $titles[(int)$e['id']] = (string)($e['title'] ?? '-');
                    }
                }
            }
            foreach ($reg as $r) {
                $items[] = [
                    'time'  => (int)$r['time'],
                    'title' => 'Berhasil terdaftar di event',
                    'desc'  => 'Event: ' . ($titles[(int)$r['event_id']] ?? '-'),
                    'link'  => '/presenter/event/' . (int)$r['event_id'],
                    'badge' => 'info',
                    'icon'  => 'bi-person-check',
                ];
            }
        }

        // Full Paper: upload & keputusan akhir
        $fpTables = [];
        if ($this->tableExists('submissions')) $fpTables[] = 'submissions';
        if ($this->tableExists('abstrak'))     $fpTables[] = 'abstrak';

        foreach ($fpTables as $t) {
            $cols = $this->tableCols($t);
            $hasStatus = isset($cols['full_paper_status']);
            $hasUploadedAt = isset($cols['full_paper_uploaded_at']);
            $hasReviewedAt = isset($cols['reviewed_at']);
            $hasDecisionAt = isset($cols['decision_at']);
            $orderCol = $this->pickCol($t, ['updated_at','full_paper_uploaded_at','created_at'], $t==='abstrak' ? 'id_abstrak' : 'id');

            $sel = [];
            if ($hasStatus)     $sel[] = 'UPPER(full_paper_status) AS st';
            if ($hasUploadedAt) $sel[] = 'full_paper_uploaded_at AS uploaded_at';
            if ($hasReviewedAt) $sel[] = 'reviewed_at';
            if ($hasDecisionAt) $sel[] = 'decision_at';
            $sel[] = 'event_id';

            if (!$sel) continue;

            $rows = $this->db->table($t)
                ->select(implode(',', $sel), false)
                ->where('id_user',$uid)
                ->orderBy($orderCol,'DESC')
                ->get()->getResultArray();

            if (!$rows) continue;

            // event titles
            $eventTitles = [];
            if ($this->tableExists('events')) {
                $ids = array_values(array_unique(array_map(fn($r)=> (int)($r['event_id'] ?? 0), $rows)));
                if ($ids) {
                    foreach ($this->db->table('events')->select('id,title')->whereIn('id',$ids)->get()->getResultArray() as $e) {
                        $eventTitles[(int)$e['id']] = (string)($e['title'] ?? '-');
                    }
                }
            }

            foreach ($rows as $r) {
                $evTitle = $eventTitles[(int)($r['event_id'] ?? 0)] ?? '-';

                // Upload full paper
                if (!empty($r['uploaded_at'])) {
                    $items[] = [
                        'time'  => strtotime((string)$r['uploaded_at']),
                        'title' => 'Full Paper diunggah',
                        'desc'  => 'Event: ' . $evTitle,
                        'link'  => '/presenter/fullpaper/detail/' . (int)($r['event_id'] ?? 0),
                        'badge' => 'primary',
                        'icon'  => 'bi-upload',
                    ];
                }

                // Keputusan akhir
                if ($hasStatus && !empty($r['st'])) {
                    $st = strtoupper((string)$r['st']);
                    $map = [
                        'ACCEPTED' => ['Full Paper diterima',   'success','bi-patch-check'],
                        'REVISION' => ['Full Paper perlu revisi','warning','bi-arrow-repeat'],
                        'REJECTED' => ['Full Paper ditolak',    'danger', 'bi-x-octagon'],
                    ];
                    if (isset($map[$st])) {
                        [$title,$badge,$icon] = $map[$st];
                        $ts = null;
                        if (!empty($r['decision_at']))      $ts = strtotime((string)$r['decision_at']);
                        elseif (!empty($r['reviewed_at']))  $ts = strtotime((string)$r['reviewed_at']);
                        else                                 $ts = time();
                        $items[] = [
                            'time'  => $ts,
                            'title' => $title,
                            'desc'  => 'Event: ' . $evTitle,
                            'link'  => '/presenter/fullpaper/detail/' . (int)($r['event_id'] ?? 0),
                            'badge' => $badge,
                            'icon'  => $icon,
                        ];
                    }
                }
            }
        }

        // Notifikasi tiap reviewer mengirim hasil
        if ($this->tableExists('fullpaper_reviews') && $this->tableExists('submissions')) {
            $subs = $this->db->table('submissions')->select('id, event_id')
                ->where('user_id', $uid)->get()->getResultArray();

            if ($subs) {
                $subById = []; $eventBySub = [];
                foreach ($subs as $s) { $subById[] = (int)$s['id']; $eventBySub[(int)$s['id']] = (int)$s['event_id']; }

                $evTitles = [];
                if ($this->tableExists('events')) {
                    $evIds = array_values(array_unique(array_values($eventBySub)));
                    if ($evIds) {
                        foreach ($this->db->table('events')->select('id,title')->whereIn('id',$evIds)->get()->getResultArray() as $e) {
                            $evTitles[(int)$e['id']] = (string)($e['title'] ?? '-');
                        }
                    }
                }

                $revNames = [];
                if ($this->tableExists('reviewers')) {
                    $nameCol = $this->pickCol('reviewers', ['nama_lengkap','nama','name','full_name','username'], 'nama');
                    foreach ($this->db->table('reviewers')->select("id, {$this->db->protectIdentifiers($nameCol)} AS name", false)->get()->getResultArray() as $rv) {
                        $revNames[(int)$rv['id']] = (string)($rv['name'] ?? 'Reviewer');
                    }
                } elseif ($this->tableExists('users')) {
                    $nameCol = $this->pickCol('users', ['name','full_name','username'], 'username');
                    foreach ($this->db->table('users')->select("id, {$this->db->protectIdentifiers($nameCol)} AS name", false)->get()->getResultArray() as $rv) {
                        $revNames[(int)$rv['id']] = (string)($rv['name'] ?? 'Reviewer');
                    }
                }

                if ($subById) {
                    $rows = $this->db->table('fullpaper_reviews')
                        ->select('submission_id, reviewer_id, LOWER(keputusan) AS keputusan, komentar, tanggal_review', false)
                        ->whereIn('submission_id', $subById)
                        ->orderBy('tanggal_review','DESC')
                        ->get()->getResultArray();

                    foreach ($rows as $r) {
                        $sid = (int)$r['submission_id'];
                        $evId = $eventBySub[$sid] ?? 0;
                        $evTitle = $evTitles[$evId] ?? '-';
                        $who = $revNames[(int)($r['reviewer_id'] ?? 0)] ?? 'Reviewer';

                        $k = strtolower((string)($r['keputusan'] ?? ''));
                        $map = [
                            'accepted' => ['Reviewer menyetujui naskah','success','bi-hand-thumbs-up'],
                            'acc'      => ['Reviewer menyetujui naskah','success','bi-hand-thumbs-up'],
                            'approved' => ['Reviewer menyetujui naskah','success','bi-hand-thumbs-up'],
                            'revisi'   => ['Reviewer meminta revisi','warning','bi-arrow-repeat'],
                            'revision' => ['Reviewer meminta revisi','warning','bi-arrow-repeat'],
                            'rejected' => ['Reviewer menolak naskah','danger','bi-hand-thumbs-down'],
                            'reject'   => ['Reviewer menolak naskah','danger','bi-hand-thumbs-down'],
                            'ditolak'  => ['Reviewer menolak naskah','danger','bi-hand-thumbs-down'],
                        ];
                        if (!isset($map[$k])) continue;
                        [$title,$badge,$icon] = $map[$k];

                        $items[] = [
                            'time'  => !empty($r['tanggal_review']) ? strtotime((string)$r['tanggal_review']) : time(),
                            'title' => $title,
                            'desc'  => $who . ' • Event: ' . $evTitle,
                            'link'  => '/presenter/fullpaper/detail/' . $evId,
                            'badge' => $badge,
                            'icon'  => $icon,
                        ];
                    }
                }
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
        $todaySchedule  = $this->getTodaySchedule($uid);   // ABSENSI: sudah difilter paid
        $activities     = $this->getActivities($uid);      // Notifikasi FP, Abstrak, Pendaftaran, Reviewer
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
