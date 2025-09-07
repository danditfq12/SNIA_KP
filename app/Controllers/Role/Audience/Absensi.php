<?php

namespace App\Controllers\Role\Audience;

use App\Controllers\BaseController;
use App\Models\EventModel;
use App\Models\PembayaranModel;
use App\Models\AbsensiModel;

class Absensi extends BaseController
{
    protected $eventModel;
    protected $pembayaranModel;
    protected $absensiModel;
    protected $db;

    public function __construct()
    {
        $this->eventModel      = new EventModel();
        $this->pembayaranModel = new PembayaranModel();
        $this->absensiModel    = new AbsensiModel();
        $this->db              = \Config\Database::connect();
    }

    /**
     * Halaman daftar absensi:
     * - yourEvents      : semua event user yang verified & aktif
     * - availableEvents : subset yang sedang bisa scan
     * - history         : riwayat absensi user
     */
    public function index()
    {
        $userId = (int) session('id_user');
        if (!$userId || session('role') !== 'audience') {
            return redirect()->to(site_url('auth/login'));
        }

        // Semua event user yang pembayarannya verified dan event aktif
        $paid = $this->db->table('pembayaran p')
            ->select('
                e.id,
                e.title,
                e.event_date,
                e.event_time,
                e.format,
                e.location,
                p.participation_type
            ')
            ->join('events e', 'e.id = p.event_id')
            ->where('p.id_user', $userId)
            ->where('p.status', 'verified')
            ->where('e.is_active', true) // boolean (PostgreSQL-friendly)
            ->orderBy('e.event_date', 'ASC')
            ->orderBy('e.event_time', 'ASC')
            ->get()->getResultArray();

        $yourEvents      = [];
        $availableEvents = [];

        foreach ($paid as $row) {
            $status = $this->calculateEventStatus($row['event_date'] ?? null, $row['event_time'] ?? null);

            $item = [
                'id'                 => $row['id'],
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

            $yourEvents[] = $item;
            if ($status['can_scan'] === true) {
                $availableEvents[] = $item;
            }
        }

        // Riwayat absensi
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

        $data = [
            'title'           => 'Absensi',
            'yourEvents'      => $yourEvents,
            'availableEvents' => $availableEvents,
            'history'         => $history,
        ];

        return view('role/audience/absensi/index', $data);
    }

    /**
     * Detail 1 event: cek hak akses (payment verified), tampilkan status & tombol.
     * Route: GET audience/absensi/event/(:num)  → Absensi::show/$1
     */
    public function show(int $eventId)
    {
        $userId = (int) session('id_user');
        if (!$userId || session('role') !== 'audience') {
            return redirect()->to(site_url('auth/login'));
        }

        // Event exist?
        $event = $this->eventModel->find($eventId);
        if (!$event || !($event['is_active'] ?? false)) {
            return redirect()->to(site_url('audience/absensi'))
                             ->with('error', 'Event tidak ditemukan atau tidak aktif.');
        }

        // Wajib punya pembayaran verified untuk event ini
        $payment = $this->pembayaranModel
            ->where('id_user', $userId)
            ->where('event_id', $eventId)
            ->where('status', 'verified')
            ->first();

        if (!$payment) {
            return redirect()->to(site_url('audience/absensi'))
                             ->with('error', 'Kamu belum memiliki akses absensi untuk event tersebut.');
        }

        // Hitung status event
        $status = $this->calculateEventStatus($event['event_date'] ?? null, $event['event_time'] ?? null);

        // Sudah absen?
        $already = $this->absensiModel->hasUserAttended($userId, $eventId);

        $data = [
            'title'              => 'Detail Absensi',
            'event'              => $event,
            'participation_type' => $payment['participation_type'] ?? 'all',
            'event_status'       => $status['event_status'],
            'badge_class'        => $status['badge_class'],
            'can_scan'           => $status['can_scan'],
            'already_attend'     => $already,
        ];

        return view('role/audience/absensi/detail', $data);
    }

    /**
     * Terima token dari form lalu redirect ke QRAttendance::scan
     * Route: POST audience/absensi/scan
     */
    public function scan()
    {
        if (!$this->request->is('post')) {
            return redirect()->to(site_url('audience/absensi'));
        }

        $userId = (int) session('id_user');
        if (!$userId || session('role') !== 'audience') {
            return redirect()->to(site_url('auth/login'));
        }

        $token = trim((string) $this->request->getPost('token'));
        if ($token === '') {
            return redirect()->back()->with('error', 'Token tidak boleh kosong.');
        }

        // lempar ke handler umum QR
        return redirect()->to(site_url('qr/' . urlencode($token)));
    }

    /**
     * Window scan: -1 jam s/d +4 jam dari jam mulai.
     */
    private function calculateEventStatus(?string $eventDate, ?string $eventTime): array
    {
        try {
            date_default_timezone_set('Asia/Jakarta');

            if (!$eventDate || !$eventTime) {
                return [
                    'event_status' => 'Jadwal Tidak Lengkap',
                    'badge_class'  => 'bg-secondary',
                    'can_scan'     => false,
                ];
            }

            $start = new \DateTime($eventDate . ' ' . $eventTime);
            $now   = new \DateTime();
            $diffH = ($now->getTimestamp() - $start->getTimestamp()) / 3600.0;

            if ($diffH < -1) {
                return ['event_status' => 'Belum Dimulai',      'badge_class' => 'bg-secondary', 'can_scan' => false];
            } elseif ($diffH < 0) {
                return ['event_status' => 'Segera Dimulai',     'badge_class' => 'bg-warning',   'can_scan' => true ];
            } elseif ($diffH <= 4) {
                return ['event_status' => 'Sedang Berlangsung', 'badge_class' => 'bg-success',   'can_scan' => true ];
            }
            return     ['event_status' => 'Sudah Selesai',      'badge_class' => 'bg-danger',    'can_scan' => false];

        } catch (\Throwable $e) {
            log_message('error', 'calculateEventStatus error: ' . $e->getMessage());
            return ['event_status' => 'Error', 'badge_class' => 'bg-secondary', 'can_scan' => false];
        }
    }
}
