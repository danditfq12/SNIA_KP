<?php

namespace App\Controllers\Role\Presenter;

use App\Controllers\BaseController;
use App\Models\PembayaranModel;
use App\Models\EventModel;
use App\Models\AbsensiModel;

class Absensi extends BaseController
{
    protected $payModel;
    protected $eventModel;
    protected $absensiModel;

    public function __construct()
    {
        $this->payModel     = new PembayaranModel();
        $this->eventModel   = new EventModel();
        $this->absensiModel = new AbsensiModel();
    }

    /** Kalkulasi window absensi (mulai & selesai) */
    private function getAttendanceWindow(array $event): array
    {
        // start = event_date + event_time
        $startStr = trim(($event['event_date'] ?? '') . ' ' . ($event['event_time'] ?? '00:00:00'));
        $start    = strtotime($startStr) ?: null;

        // end = attendance_end_at (jika ada & valid) else start + 4 jam
        $end = null;

        // Jika skema kamu punya kolom opsional attendance_end_at → pakai
        if (!empty($event['attendance_end_at'])) {
            $end = strtotime($event['attendance_end_at']);
        }

        if (!$end && $start) {
            $end = $start + (4 * 3600); // default 4 jam
        }

        $now  = time();
        $open = ($start && $end) ? ($now >= $start && $now <= $end) : false;

        // Alasan kenapa tertutup
        $reason = '';
        if ($start && $end && !$open) {
            if ($now < $start) {
                $reason = 'Event belum dimulai';
            } elseif ($now > $end) {
                $reason = 'Event sudah ditutup';
            }
        }

        return [
            'start_ts' => $start,
            'end_ts'   => $end,
            'is_open'  => $open,
            'reason'   => $reason,
        ];
    }

    /** INDEX → tampil box, tombol “Absen” ke detail */
    public function index()
    {
        $userId = (int) session()->get('id_user');

        // Event yang pembayarannya verified (presenter)
        $verified = $this->payModel->select('pembayaran.*, e.*')
            ->join('events e', 'e.id = pembayaran.event_id', 'left')
            ->where('pembayaran.id_user', $userId)
            ->where('pembayaran.status', 'verified')
            ->orderBy('e.event_date', 'ASC')
            ->findAll();

        $today = date('Y-m-d');

        $boxesToday = [];
        $boxesNext  = [];
        $history    = $this->absensiModel->getUserAttendanceHistory($userId); // untuk ringkas KPI

        foreach ($verified as $p) {
            $event = $p; // karena join e.* sudah keambil
            $att   = $this->getAttendanceWindow($event);

            $boxes[] = [
                'event_id'   => (int)$event['id'],
                'title'      => $event['title'],
                'date'       => $event['event_date'],
                'time'       => $event['event_time'],
                'location'   => $event['location'] ?? null,
                'format'     => $event['format'] ?? null,
                'window'     => $att,
                'attended'   => $this->absensiModel->hasUserAttended($userId, (int)$event['id']),
            ];

            $row = end($boxes);

            if (($event['event_date'] ?? '') === $today) {
                $boxesToday[] = $row;
            } elseif (($event['event_date'] ?? '') > $today) {
                $boxesNext[] = $row;
            }
        }

        $kpi = [
            'count_today'  => count($boxesToday),
            'count_next'   => count($boxesNext),
            'count_hadir'  => $this->absensiModel->countUserAttendance($userId),
        ];

        return view('role/presenter/absensi/index', [
            'title'      => 'Absensi',
            'boxesToday' => $boxesToday,
            'boxesNext'  => $boxesNext,
            'kpi'        => $kpi,
        ]);
    }

    /** DETAIL → dari tombol “Absen” */
    public function show(int $eventId)
    {
        $userId = (int) session()->get('id_user');
        if (!$eventId) return redirect()->to('/presenter/absensi')->with('error','Event tidak valid.');

        $event = $this->eventModel->find($eventId);
        if (!$event) return redirect()->to('/presenter/absensi')->with('error','Event tidak ditemukan.');

        $pay = $this->payModel->where('id_user',$userId)->where('event_id',$eventId)->where('status','verified')->first();
        if (!$pay) return redirect()->to('/presenter/absensi')->with('error','Akses ditolak. Pembayaran belum terverifikasi.');

        $window   = $this->getAttendanceWindow($event);
        $attended = $this->absensiModel->hasUserAttended($userId, $eventId);

        return view('role/presenter/absensi/detail', [
            'title'    => 'Detail Absensi',
            'event'    => $event,
            'payment'  => $pay,
            'window'   => $window,
            'attended' => $attended,
        ]);
    }

    /** POST token → hanya boleh saat window absensi terbuka */
    public function scan()
    {
        if ($this->request->getMethod() !== 'post') {
            return redirect()->to('/presenter/absensi');
        }

        $userId  = (int) session()->get('id_user');
        $eventId = (int) $this->request->getPost('event_id');
        $token   = trim((string) $this->request->getPost('token'));

        if (!$eventId || $token === '') {
            return redirect()->back()->with('error', 'Event dan token wajib diisi.');
        }

        $event = $this->eventModel->find($eventId);
        if (!$event) {
            return redirect()->back()->with('error', 'Event tidak valid.');
        }

        $pay = $this->payModel->where('id_user',$userId)->where('event_id',$eventId)->where('status','verified')->first();
        if (!$pay) {
            return redirect()->back()->with('error', 'Akses absensi ditolak. Pembayaran belum terverifikasi.');
        }

        // Cek window absensi
        $window = $this->getAttendanceWindow($event);
        if (!$window['is_open']) {
            $msg = $window['reason'] ?: 'Window absensi belum dibuka/ sudah ditutup.';
            return redirect()->back()->with('error', $msg);
        }

        // Sudah absen?
        if ($this->absensiModel->hasUserAttended($userId, $eventId)) {
            return redirect()->back()->with('info', 'Anda sudah tercatat hadir pada event ini.');
        }

        $ok = $this->absensiModel->insert([
            'id_user'        => $userId,
            'event_id'       => $eventId,
            'qr_code'        => $token,
            'status'         => 'hadir',
            'waktu_scan'     => date('Y-m-d H:i:s'),
            'marked_by_admin'=> null,
            'notes'          => 'Self-checkin via token',
        ]);

        if (!$ok) {
            return redirect()->back()->with('error', 'Gagal mencatat kehadiran.');
        }

        return redirect()->to('/presenter/absensi/event/'.$eventId)->with('success', 'Absensi berhasil dicatat.');
    }
}