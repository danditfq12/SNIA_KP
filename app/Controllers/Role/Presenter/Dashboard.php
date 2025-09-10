<?php

namespace App\Controllers\Role\Presenter;

use App\Controllers\BaseController;
use App\Models\EventModel;
use App\Models\AbstrakModel;
use App\Models\PembayaranModel;
use App\Models\DokumenModel;

class Dashboard extends BaseController
{
    protected EventModel $eventM;
    protected AbstrakModel $absM;
    protected PembayaranModel $payM;
    protected DokumenModel $docM;
    protected \CodeIgniter\Database\BaseConnection $db;

    public function __construct()
    {
        $this->eventM = new EventModel();
        $this->absM   = new AbstrakModel();
        $this->payM   = new PembayaranModel();
        $this->docM   = new DokumenModel();
        $this->db     = \Config\Database::connect();
    }

    public function index()
    {
        $uid = (int) (session('id_user') ?? 0);
        if ($uid <= 0 || session('role') !== 'presenter') {
            return redirect()->to(site_url('auth/login'));
        }

        // =========================
        // Event verified (>= kemarin) untuk kartu Jadwal & Absen Hari Ini
        // =========================
        $upcomingPaid = $this->db->table('pembayaran p')
            ->select('e.id, e.title, e.event_date, e.event_time, e.location')
            ->join('events e', 'e.id = p.event_id', 'left')
            ->where('p.id_user', $uid)
            ->where('p.status', 'verified')
            ->where('e.is_active', true)
            ->where('e.event_date >=', date('Y-m-d', strtotime('-1 day')))
            ->orderBy('e.event_date', 'ASC')
            ->orderBy('e.event_time', 'ASC')
            ->get()->getResultArray();

        // Absen hari ini (OFFLINE only → tidak ada tombol Zoom)
        $todayYmd   = date('Y-m-d');
        $absenToday = array_values(array_filter($upcomingPaid, static function ($u) use ($todayYmd) {
            return ($u['event_date'] ?? '') === $todayYmd;
        }));

        // =========================
        // Pembayaran pending (kanan)
        // =========================
        $pendingPays = $this->payM->select('id_pembayaran, event_id, jumlah, tanggal_bayar')
            ->where('id_user', $uid)
            ->where('status', 'pending')
            ->orderBy('tanggal_bayar', 'DESC')
            ->findAll();

        // Peta event untuk tabel pending
        $eventMap = [];
        if (!empty($pendingPays)) {
            $ids = array_unique(array_map('intval', array_column($pendingPays, 'event_id')));
            if (!empty($ids)) {
                $rows = $this->eventM
                    ->select('id,title,event_date,event_time,location')
                    ->whereIn('id', $ids)->findAll();
                foreach ($rows as $r) $eventMap[(int)$r['id']] = $r;
            }
        }

        // =========================
        // KPI
        // =========================
        // Event Diikuti = pembayaran verified (presenter sudah konfirmasi ikut)
        $joined = (int) $this->payM->where('id_user', $uid)->where('status', 'verified')->countAllResults();

        // Abstrak diterima
        $accepted = (int) $this->absM->where('id_user', $uid)->where('status', 'diterima')->countAllResults();

        // Pembayaran terverifikasi
        $verified = $joined;

        // LOA didapatkan (case-insensitive)
        $loaCount = (int) $this->db->table('dokumen')
            ->where('id_user', $uid)
            ->groupStart()
                ->where('tipe', 'loa')
                ->orWhere('tipe', 'LOA')
                ->orWhere('tipe', 'Loa')
            ->groupEnd()
            ->countAllResults();

        $kpis = [
            'joined'   => $joined,
            'accepted' => $accepted,
            'verified' => $verified,
            'loa'      => $loaCount,
        ];

        return view('role/presenter/dashboard', [
            'title'        => 'Presenter Dashboard',
            'upcomingPaid' => $upcomingPaid,
            'pendingPays'  => $pendingPays,
            'eventMap'     => $eventMap,
            'kpis'         => $kpis,
            'absenToday'   => $absenToday,
        ]);
    }
}
