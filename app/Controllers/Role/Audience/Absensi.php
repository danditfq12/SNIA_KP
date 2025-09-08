<?php

namespace App\Controllers\Role\Audience;

use App\Controllers\BaseController;
use App\Models\EventModel;
use App\Models\PembayaranModel;
use App\Models\AbsensiModel;

class Absensi extends BaseController
{
    protected EventModel $eventModel;
    protected PembayaranModel $pembayaranModel;
    protected AbsensiModel $absensiModel;
    protected \CodeIgniter\Database\BaseConnection $db;
    protected \DateTimeZone $tz;

    public function __construct()
    {
        $this->eventModel      = new EventModel();
        $this->pembayaranModel = new PembayaranModel();
        $this->absensiModel    = new AbsensiModel();
        $this->db              = \Config\Database::connect();
        $this->tz              = new \DateTimeZone(config('App')->appTimezone ?? 'Asia/Jakarta');
    }

    private function uid(): int
    {
        return (int) (session('id_user') ?? 0);
    }

    /** ===== Helpers ===== */
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

    /**
     * Aturan:
     * - Bisa scan hanya SETELAH mulai (>= start) s.d. +4 jam.
     * - Jika event dihentikan admin (attendance_status = closed/stopped) → tidak bisa scan.
     * - Event non-aktif → tidak bisa scan.
     * Catatan: jika kolom attendance_status tidak ada, dianggap 'open'.
     */
    private function calculateEventStatus(array $event): array
    {
        if (empty($event['event_date']) || empty($event['event_time'])) {
            return ['event_status' => 'Jadwal Tidak Lengkap', 'badge_class' => 'bg-secondary', 'can_scan' => false];
        }

        if (empty($event['is_active'])) {
            return ['event_status' => 'Tidak Aktif', 'badge_class' => 'bg-secondary', 'can_scan' => false];
        }

        $attn = strtolower((string)($event['attendance_status'] ?? 'open'));
        if (in_array($attn, ['closed','stopped','ended'], true)) {
            return ['event_status' => 'Dihentikan', 'badge_class' => 'bg-danger', 'can_scan' => false];
        }

        $startStr = $this->composeStart($event['event_date'], $event['event_time']);
        $start    = \DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $startStr, $this->tz)
                  ?: new \DateTimeImmutable($startStr, $this->tz);

        $now   = new \DateTimeImmutable('now', $this->tz);
        $diffH = ($now->getTimestamp() - $start->getTimestamp()) / 3600.0;

        if ($diffH < 0) {
            return ['event_status' => 'Belum Dimulai', 'badge_class' => 'bg-secondary', 'can_scan' => false];
        } elseif ($diffH <= 4) {
            return ['event_status' => 'Sedang Berlangsung', 'badge_class' => 'bg-success', 'can_scan' => true];
        }
        return ['event_status' => 'Sudah Selesai', 'badge_class' => 'bg-secondary', 'can_scan' => false];
    }

    /** ===== Pages ===== */
    public function index()
    {
        $userId = $this->uid();
        if (!$userId || session('role') !== 'audience') {
            return redirect()->to(site_url('auth/login'));
        }

        // HANYA verified
        $paid = $this->db->table('pembayaran p')
            ->select('
                e.id,
                e.title,
                e.event_date,
                e.event_time,
                e.format,
                e.location,
                e.is_active,
                p.participation_type
            ')
            ->join('events e', 'e.id = p.event_id')
            ->where('p.id_user', $userId)
            ->where('p.status', 'verified')
            ->where('e.is_active', true)
            ->orderBy('e.event_date', 'ASC')
            ->orderBy('e.event_time', 'ASC')
            ->get()->getResultArray();

        $yourEvents = [];
        foreach ($paid as $row) {
            // Kolom attendance_status mungkin tidak ada —> calculateEventStatus aman.
            $status = $this->calculateEventStatus($row);
            $yourEvents[] = [
                'id'                 => (int)$row['id'],
                'title'              => $row['title'],
                'event_date'         => $row['event_date'],
                'event_time'         => $row['event_time'],
                'format'             => $row['format'] ?? '-',
                'location'           => $row['location'] ?? '-',
                'participation_type' => $row['participation_type'] ?? 'all',
                'event_status'       => $status['event_status'],
                'badge_class'        => $status['badge_class'],
                'can_scan'           => $status['can_scan'],
            ];
        }

        $history = $this->absensiModel->select('
                            absensi.waktu_scan,
                            absensi.status,
                            absensi.qr_code,
                            e.title as event_title,
                            e.event_date,
                            e.event_time
                        ')
                        ->join('events e', 'e.id = absensi.event_id', 'left')
                        ->where('absensi.id_user', $userId)
                        ->orderBy('absensi.waktu_scan', 'DESC')
                        ->findAll() ?: [];

        return view('role/audience/absensi/index', [
            'title'      => 'Absensi',
            'yourEvents' => $yourEvents,
            'history'    => $history,
        ]);
    }

    public function show(int $eventId)
    {
        $userId = $this->uid();
        if (!$userId || session('role') !== 'audience') {
            return redirect()->to(site_url('auth/login'));
        }

        $event = $this->eventModel->find($eventId);
        if (!$event || !($event['is_active'] ?? false)) {
            return redirect()->to(site_url('audience/absensi'))
                ->with('error', 'Event tidak ditemukan atau tidak aktif.');
        }

        $payment = $this->pembayaranModel->where('id_user', $userId)
                                         ->where('event_id', $eventId)
                                         ->where('status', 'verified')
                                         ->first();
        if (!$payment) {
            return redirect()->to(site_url('audience/absensi'))
                ->with('error', 'Kamu belum memiliki akses absensi untuk event tersebut.');
        }

        $status  = $this->calculateEventStatus($event);
        $already = $this->absensiModel->hasUserAttended($userId, $eventId);

        $last = $this->absensiModel->select('waktu_scan')
                ->where('id_user', $userId)
                ->where('event_id', $eventId)
                ->orderBy('waktu_scan','DESC')
                ->first();
        $attendanceAt = $last['waktu_scan'] ?? null;

        $eventView = $event;
        $eventView['badge_class']        = $status['badge_class'];
        $eventView['event_status']       = $status['event_status'];
        $eventView['can_scan']           = $status['can_scan'];
        $eventView['participation_type'] = $payment['participation_type'] ?? 'all';

        return view('role/audience/absensi/detail', [
            'title'           => 'Detail Absensi',
            'event'           => $eventView,
            'already_attend'  => $already,
            'attendance_at'   => $attendanceAt,
        ]);
    }

    public function scan()
    {
        if (!$this->request->is('post')) {
            return redirect()->to(site_url('audience/absensi'));
        }

        $userId  = $this->uid();
        if (!$userId || session('role') !== 'audience') {
            return redirect()->to(site_url('auth/login'));
        }

        $token   = trim((string)$this->request->getPost('token'));
        $eventId = (int)$this->request->getPost('event_id');

        if ($token === '' || $eventId <= 0) {
            return redirect()->back()->with('error', 'Token / Event tidak valid.');
        }

        if ($this->absensiModel->hasUserAttended($userId, $eventId)) {
            return redirect()->back()->with('error', 'Kamu sudah absen untuk event ini.');
        }

        $event = $this->eventModel->find($eventId);
        if (!$event) {
            return redirect()->back()->with('error','Event tidak ditemukan.');
        }

        $payment = $this->pembayaranModel->where('id_user', $userId)
                                         ->where('event_id', $eventId)
                                         ->where('status', 'verified')
                                         ->first();
        if (!$payment) {
            return redirect()->back()->with('error','Akses absensi tidak valid untuk event ini.');
        }

        $status = $this->calculateEventStatus($event);
        if (!$status['can_scan']) {
            $msg = match ($status['event_status']) {
                'Belum Dimulai' => 'Absensi belum dibuka.',
                'Dihentikan'    => 'Absensi telah dihentikan oleh panitia.',
                'Sudah Selesai' => 'Event sudah selesai. Absensi ditutup.',
                default         => 'Absensi tidak tersedia saat ini.',
            };
            return redirect()->back()->with('error', $msg);
        }

        return redirect()->to(site_url('qr/' . urlencode($token)));
    }
}
