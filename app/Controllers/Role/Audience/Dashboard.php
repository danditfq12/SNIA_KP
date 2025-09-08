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
    protected $db;

    public function __construct()
    {
        $this->eventM = new EventModel();
        $this->payM   = new PembayaranModel();
        $this->absenM = new AbsensiModel();
        $this->db     = \Config\Database::connect();
    }

    public function index()
    {
        $uid = (int) (session('id_user') ?? 0);
        if (!$uid || session('role') !== 'audience') {
            return redirect()->to(site_url('auth/login'));
        }

        // Event (verified) yang >= kemarin (biar gak cepat hilang)
        $upcomingPaid = $this->db->table('pembayaran p')
            ->select('e.id, e.title, e.event_date, e.event_time, p.participation_type AS mode_kehadiran')
            ->join('events e', 'e.id = p.event_id', 'left')
            ->where('p.id_user', $uid)
            ->where('p.status', 'verified')
            ->where('e.is_active', true)
            ->where('e.event_date >=', date('Y-m-d', strtotime('-1 day')))
            ->orderBy('e.event_date', 'ASC')
            ->orderBy('e.event_time', 'ASC')
            ->get()->getResultArray();

        // Absen hari ini
        $todayYmd = date('Y-m-d');
        $absenToday = array_values(array_filter($upcomingPaid, static function($u) use ($todayYmd){
            return ($u['event_date'] ?? '') === $todayYmd;
        }));

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
                ->where('id_user', $uid)
                ->where('status', 'verified')
                ->countAllResults(),

            'upcoming' => (int) $this->db->table('pembayaran p')->join('events e', 'e.id=p.event_id')
                ->where('p.id_user', $uid)
                ->where('p.status', 'verified')
                ->where('e.event_date >=', date('Y-m-d'))
                ->countAllResults(),

            'today' => (int) $this->db->table('pembayaran p')->join('events e', 'e.id=p.event_id')
                ->where('p.id_user', $uid)
                ->where('p.status', 'verified')
                ->where('e.event_date', $todayYmd)
                ->countAllResults(),

            // Case-insensitive tanpa fungsi SQL
            'certs' => (int) $this->db->table('dokumen')
                ->where('id_user', $uid)
                ->groupStart()
                    ->where('tipe','sertifikat')->orWhere('tipe','Sertifikat')
                    ->orWhere('tipe','CERTIFICATE')->orWhere('tipe','Certificate')
                ->groupEnd()
                ->countAllResults(),
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
