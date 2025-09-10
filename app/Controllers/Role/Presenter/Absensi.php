<?php

namespace App\Controllers\Role\Presenter;

use App\Controllers\BaseController;
use App\Models\AbsensiModel;
use App\Models\PembayaranModel;
use App\Models\EventModel;

class Absensi extends BaseController
{
    protected AbsensiModel $absensiModel;
    protected PembayaranModel $pembayaranModel;
    protected EventModel $eventModel;
    protected \CodeIgniter\Database\BaseConnection $db;

    public function __construct()
    {
        $this->absensiModel    = new AbsensiModel();
        $this->pembayaranModel = new PembayaranModel();
        $this->eventModel      = new EventModel();
        $this->db              = \Config\Database::connect();
    }

    public function index()
    {
        $userId = (int) session('id_user');

        // wajib punya minimal 1 pembayaran terverifikasi agar bisa akses menu absensi
        $hasAnyVerified = $this->pembayaranModel
            ->where('id_user', $userId)
            ->where('status', 'verified')
            ->countAllResults() > 0;

        if (!$hasAnyVerified) {
            return redirect()->to('presenter/dashboard')
                ->with('error', 'Selesaikan pembayaran terverifikasi terlebih dahulu untuk mengakses fitur absensi.');
        }

        // event-event user yang pembayaran-nya sudah verified
        $currentEvents = $this->db->table('events e')
            ->select('e.*, p.id_pembayaran, p.participation_type, p.verified_at')
            ->join('pembayaran p', 'p.event_id = e.id', 'inner')
            ->where('p.id_user', $userId)
            ->where('p.status', 'verified')
            ->where('e.is_active', true)
            ->orderBy('e.event_date', 'ASC')
            ->get()->getResultArray();

        // siapin atribut tambahan (status bisa scan, sudah hadir)
        $todayEvents = [];
        foreach ($currentEvents as &$ev) {
            $ev['event_status_data'] = $this->canScanNow($ev);
            $ev['can_scan']          = $ev['event_status_data']['can'];
            $ev['event_status']      = $ev['event_status_data']['label'];
            $ev['badge_class']       = $ev['event_status_data']['badge'];
            $ev['already_attended']  = $this->alreadyAttended($userId, (int)$ev['id']);
            if (date('Y-m-d') === ($ev['event_date'] ?? '')) $todayEvents[] = $ev;
        }

        // riwayat absensi
        if (method_exists($this->absensiModel, 'getUserAttendanceHistory')) {
            $attendanceHistory = $this->absensiModel->getUserAttendanceHistory($userId);
        } else {
            $attendanceHistory = $this->db->table('absensi a')
                ->select('a.*, e.title as event_title')
                ->join('events e', 'e.id = a.event_id', 'left')
                ->where('a.id_user', $userId)
                ->orderBy('a.waktu_scan', 'DESC')
                ->get()->getResultArray();
        }

        return view('role/presenter/absensi/index', [
            'currentEvents'     => $currentEvents,
            'todayEvents'       => $todayEvents,
            'attendanceHistory' => $attendanceHistory,
        ]);
    }

    public function show($eventId)
    {
        $userId  = (int) session('id_user');
        $eventId = (int) $eventId;

        $event = $this->eventModel->find($eventId);
        if (!$event || !$this->parseBool($event['is_active'] ?? true)) {
            return redirect()->to('presenter/absensi')->with('error', 'Event tidak ditemukan / tidak aktif.');
        }

        // wajib verified payment untuk event ini
        $hasVerified = $this->hasVerifiedPayment($userId, $eventId);
        if (!$hasVerified) {
            return redirect()->to('presenter/dashboard')->with('error', 'Anda belum memiliki pembayaran terverifikasi untuk event ini.');
        }

        $can = $this->canScanNow($event);
        return view('role/presenter/absensi/show', [
            'event'            => $event,
            'canScan'          => $can['can'],
            'alreadyAttended'  => $this->alreadyAttended($userId, $eventId),
        ]);
    }

    public function scan()
    {
        if ($this->request->getMethod() !== 'post') {
            return $this->response->setStatusCode(405)->setJSON(['success'=>false,'message'=>'Method not allowed']);
        }

        $userId  = (int) session('id_user');
        $qrCode  = trim((string)$this->request->getPost('qr_code'));
        $eventId = (int) ($this->request->getPost('event_id') ?? 0);

        // coba ekstrak event id dari token kalau belum ada
        if ($eventId === 0 && preg_match('/EVENT_(\d+)/i', $qrCode, $m)) {
            $eventId = (int) $m[1];
        }
        if ($eventId <= 0) {
            return $this->response->setJSON(['success'=>false,'message'=>'Event tidak valid.']);
        }

        $event = $this->eventModel->find($eventId);
        if (!$event || !$this->parseBool($event['is_active'] ?? true)) {
            return $this->response->setJSON(['success'=>false,'message'=>'Event tidak ditemukan / tidak aktif.']);
        }

        if (!$this->hasVerifiedPayment($userId, $eventId)) {
            return $this->response->setJSON(['success'=>false,'message'=>'Pembayaran event ini belum terverifikasi.']);
        }

        $timing = $this->canScanNow($event);
        if (!$timing['can']) {
            return $this->response->setJSON(['success'=>false,'message'=>$timing['message'] ?? 'Di luar jadwal absensi.']);
        }

        if ($this->alreadyAttended($userId, $eventId)) {
            return $this->response->setJSON(['success'=>false,'message'=>'Anda sudah tercatat hadir untuk event ini.']);
        }

        $ok = $this->absensiModel->insert([
            'id_user'     => $userId,
            'event_id'    => $eventId,
            'qr_code'     => $qrCode ? substr($qrCode, 0, 200) : null,
            'status'      => 'hadir',
            'waktu_scan'  => date('Y-m-d H:i:s'),
            'notes'       => 'Presenter QR scan (offline)',
        ]);

        if (!$ok) {
            return $this->response->setJSON(['success'=>false,'message'=>'Gagal mencatat absensi.']);
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Absensi presenter berhasil dicatat.',
            'data' => [
                'event_id'    => $eventId,
                'event_title' => $event['title'] ?? 'Event',
                'time'        => date('H:i:s'),
                'date'        => date('d/m/Y'),
            ]
        ]);
    }

    /* ================== Helpers ================== */

    private function hasVerifiedPayment(int $userId, int $eventId): bool
    {
        return $this->pembayaranModel
            ->where('id_user', $userId)
            ->where('event_id', $eventId)
            ->where('status', 'verified')
            ->countAllResults() > 0;
    }

    private function alreadyAttended(int $userId, int $eventId): bool
    {
        return $this->absensiModel
            ->where('id_user', $userId)
            ->where('event_id', $eventId)
            ->countAllResults() > 0;
    }

    /** Aturan: jika hari-H => 06:00–23:59; selain itu: start-2 jam s/d (start+8 jam)+4 jam */
    private function canScanNow(array $event): array
    {
        $tz   = new \DateTimeZone('Asia/Jakarta');
        $now  = new \DateTime('now', $tz);
        $date = $event['event_date'] ?? null;
        $time = $event['event_time'] ?? '08:00:00';

        if (!$date) {
            return ['can'=>false,'label'=>'Jadwal Tidak Valid','badge'=>'bg-secondary','message'=>'Tanggal event kosong.'];
        }

        // hari-H long window
        if ($now->format('Y-m-d') === $date) {
            $start6 = new \DateTime("$date 06:00:00", $tz);
            $end23  = new \DateTime("$date 23:59:59", $tz);
            if ($now >= $start6 && $now <= $end23) {
                return ['can'=>true,'label'=>'Sedang Berlangsung','badge'=>'bg-success'];
            }
            return ['can'=>false,'label'=>'Di luar jadwal','badge'=>'bg-secondary'];
        }

        // selain hari-H ⇒ window sekitar jam event
        $start = new \DateTime("$date $time", $tz);
        $allowedStart = (clone $start)->sub(new \DateInterval('PT2H'));
        $allowedEnd   = (clone $start)->add(new \DateInterval('PT12H')); // 8 jam + 4 jam toleransi

        if ($now < $allowedStart) {
            return ['can'=>false,'label'=>'Belum Dimulai','badge'=>'bg-secondary','message'=>'Event belum dapat diakses.'];
        }
        if ($now > $allowedEnd) {
            return ['can'=>false,'label'=>'Sudah Selesai','badge'=>'bg-secondary','message'=>'Periode absensi berakhir.'];
        }
        return ['can'=>true,'label'=>'Sedang Berlangsung','badge'=>'bg-success'];
    }

    private function parseBool($v): bool
    {
        if (is_bool($v)) return $v;
        if (is_numeric($v)) return (int)$v === 1;
        $s = strtolower((string)$v);
        return in_array($s, ['1','true','t','yes','y'], true);
    }
}
