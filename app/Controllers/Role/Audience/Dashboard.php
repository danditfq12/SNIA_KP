<?php

namespace App\Controllers\Role\Audience;

use App\Controllers\BaseController;
use App\Models\EventModel;
use App\Models\PembayaranModel;
use App\Models\AbsensiModel;

class Dashboard extends BaseController
{
    protected EventModel $eventM;
    protected PembayaranModel $payM;
    protected AbsensiModel $absenM;
    protected \CodeIgniter\Database\BaseConnection $db;
    protected \DateTimeZone $tz;

    public function __construct()
    {
        $this->eventM = new EventModel();
        $this->payM   = new PembayaranModel();
        $this->absenM = new AbsensiModel();
        $this->db     = \Config\Database::connect();
        $this->tz     = new \DateTimeZone(config('App')->appTimezone ?? 'Asia/Jakarta');
    }

    private function uid(): int
    {
        return (int) (session('id_user') ?? 0);
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

    /** Sama seperti di Absensi controller: hitung status + can_scan */
    private function calculateEventStatus(array $event): array
    {
        if (empty($event['event_date']) || empty($event['event_time'])) {
            return ['event_status'=>'Jadwal Tidak Lengkap','badge_class'=>'bg-secondary','can_scan'=>false];
        }
        if (empty($event['is_active'])) {
            return ['event_status'=>'Tidak Aktif','badge_class'=>'bg-secondary','can_scan'=>false];
        }

        $attn = strtolower((string)($event['attendance_status'] ?? 'open'));
        if (in_array($attn, ['closed','stopped','ended'], true)) {
            return ['event_status'=>'Dihentikan','badge_class'=>'bg-danger','can_scan'=>false];
        }

        $startStr = $this->composeStart($event['event_date'], $event['event_time']);
        $start    = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $startStr, $this->tz)
                   ?: new \DateTimeImmutable($startStr, $this->tz);
        $now      = new \DateTimeImmutable('now', $this->tz);
        $diffH    = ($now->getTimestamp() - $start->getTimestamp()) / 3600.0;

        if ($diffH < 0)  return ['event_status'=>'Belum Dimulai','badge_class'=>'bg-secondary','can_scan'=>false];
        if ($diffH <= 4) return ['event_status'=>'Sedang Berlangsung','badge_class'=>'bg-success','can_scan'=>true];
        return ['event_status'=>'Sudah Selesai','badge_class'=>'bg-secondary','can_scan'=>false];
    }

    public function index()
    {
        $uid = $this->uid();
        if (!$uid || session('role') !== 'audience') {
            return redirect()->to(site_url('auth/login'));
        }

        // Deteksi kolom meeting link di tabel events
        $eventFields = [];
        try { $eventFields = $this->db->getFieldNames('events'); } catch (\Throwable $e) {}
        $zoomCol = null;
        foreach (['zoom_link','meeting_link','online_link'] as $cand) {
            if (in_array($cand, $eventFields, true)) { $zoomCol = $cand; break; }
        }

        // Ambil semua event yang sudah diverifikasi pembayarannya, dari >= kemarin
        $cols = 'e.id, e.title, e.event_date, e.event_time, e.format, e.location, e.is_active, p.participation_type AS mode_kehadiran';
        if ($zoomCol) $cols .= ', e.'.$zoomCol.' AS zoom_link';

        $upcomingPaid = $this->db->table('pembayaran p')
            ->select($cols, false)
            ->join('events e', 'e.id = p.event_id', 'left')
            ->where('p.id_user', $uid)
            ->where('p.status', 'verified')
            ->where('e.is_active', true)
            ->where('e.event_date >=', date('Y-m-d', strtotime('-1 day')))
            ->orderBy('e.event_date', 'ASC')
            ->orderBy('e.event_time', 'ASC')
            ->get()->getResultArray();

        // --- Tambahkan info "sudah absen" (sekali query, per event) ---
        $eventIds = array_map(fn($r)=> (int)$r['id'], $upcomingPaid);
        $lastScanMap = [];
        if (!empty($eventIds)) {
            $rows = $this->db->table('absensi')
                ->select('event_id, MAX(waktu_scan) AS last_scan', false)
                ->where('id_user', $uid)
                ->whereIn('event_id', $eventIds)
                ->groupBy('event_id')
                ->get()->getResultArray();
            foreach ($rows as $r) $lastScanMap[(int)$r['event_id']] = $r['last_scan'] ?? null;
        }

        // Hitung status & can_scan; sekaligus bentuk Absen Hari Ini
        $todayYmd   = date('Y-m-d');
        $absenToday = [];

        foreach ($upcomingPaid as &$ev) {
            $status = $this->calculateEventStatus($ev);

            $eid               = (int)$ev['id'];
            $attendanceAt      = $lastScanMap[$eid] ?? null;
            $ev['already_attend'] = $attendanceAt !== null;
            $ev['attendance_at']  = $attendanceAt;
            $ev['can_scan']       = !$ev['already_attend'] && $status['can_scan']; // hanya jika belum absen
            $ev['badge_class']    = $status['badge_class'];
            $ev['event_status']   = $status['event_status'];

            if (($ev['event_date'] ?? '') === $todayYmd) {
                $absenToday[] = $ev;
            }
        }
        unset($ev);

        // Pembayaran pending
        $pendingPays = $this->payM->select('id_pembayaran, event_id, jumlah, tanggal_bayar')
            ->where('id_user', $uid)
            ->where('status', 'pending')
            ->orderBy('tanggal_bayar', 'DESC')
            ->findAll();

        // Map event untuk pending
        $eventMap = [];
        if ($pendingPays) {
            $ids = array_unique(array_column($pendingPays, 'event_id'));
            if (!empty($ids)) {
                $rows = $this->eventM->select('id,title,event_date,event_time')->whereIn('id', $ids)->findAll();
                foreach ($rows as $r) $eventMap[(int)$r['id']] = $r;
            }
        }

        // KPI
        $kpis = [
            'joined' => (int) $this->payM
                ->where('id_user', $uid)->where('status', 'verified')->countAllResults(),

            'upcoming' => (int) $this->db->table('pembayaran p')->join('events e','e.id=p.event_id')
                ->where('p.id_user', $uid)->where('p.status','verified')
                ->where('e.event_date >=', date('Y-m-d'))->countAllResults(),

            'today' => (int) $this->db->table('pembayaran p')->join('events e','e.id=p.event_id')
                ->where('p.id_user', $uid)->where('p.status','verified')
                ->where('e.event_date', $todayYmd)->countAllResults(),

            'certs' => (int) $this->db->table('dokumen')
                ->where('id_user', $uid)
                ->groupStart()
                    ->where('tipe','sertifikat')->orWhere('tipe','Sertifikat')
                    ->orWhere('tipe','CERTIFICATE')->orWhere('tipe','Certificate')
                ->groupEnd()->countAllResults(),
        ];

        return view('role/audience/dashboard', [
            'title'        => 'Audience Dashboard',
            'upcomingPaid' => $upcomingPaid,
            'pendingPays'  => $pendingPays,
            'eventMap'     => $eventMap,
            'kpis'         => $kpis,
            'absenToday'   => $absenToday,
        ]);
    }
}
