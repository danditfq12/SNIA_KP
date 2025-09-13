<?php
namespace App\Controllers\Role\Presenter;

use App\Controllers\BaseController;
use App\Models\EventRegistrationModel;
use App\Models\AbstrakModel;
use App\Models\PembayaranModel;
use App\Models\NotificationModel;
use Config\Database;

class Dashboard extends BaseController
{
    protected EventRegistrationModel $regModel;
    protected AbstrakModel $absModel;
    protected PembayaranModel $payModel;
    protected NotificationModel $notifModel;
    protected \CodeIgniter\Database\BaseConnection $db;

    public function __construct()
    {
        $this->regModel    = new EventRegistrationModel();
        $this->absModel    = new AbstrakModel();
        $this->payModel    = new PembayaranModel();
        $this->notifModel  = new NotificationModel();
        $this->db          = Database::connect();
    }

    private function userId(): int
    {
        foreach (['id_user','user_id','id'] as $k) {
            $v = session($k);
            if (!empty($v)) return (int)$v;
        }
        return 0;
    }

    public function index()
    {
        $userId = $this->userId();
        if ($userId <= 0) return redirect()->to('/auth/login')->with('error','Silakan login.');

        // ===== Registrations (aman untuk Postgres) =====
        try {
            if (method_exists($this->regModel, 'listByUser')) {
                $regs = $this->regModel->listByUser($userId);
                $totalEvents = is_array($regs) ? count($regs) : 0;
            } else {
                $regs = $this->db->table('event_registrations r')
                    ->select('r.*, e.title AS event_title, r.status, e.event_date')
                    ->join('events e', 'e.id = r.id_event', 'left')
                    ->where('r.id_user', $userId)
                    ->orderBy('e.event_date', 'DESC')
                    ->get()->getResultArray();
                $totalEvents = count($regs);
            }
        } catch (\Throwable $e) {
            $regs = []; $totalEvents = 0;
        }

        // ===== Abstrak =====
        $totalAbstrak = 0;
        try { $totalAbstrak = (int)$this->absModel->where('id_user', $userId)->countAllResults(); } catch (\Throwable $e) {}

        // ===== Pembayaran pending =====
        $pendingPayments = [];
        try {
            $pendingPayments = $this->payModel
                ->where('id_user', $userId)
                ->where('status', 'pending')
                ->orderBy('id_pembayaran', 'DESC')
                ->findAll(6);
        } catch (\Throwable $e) {}

        // ===== Absensi hari ini (pakai range, bukan LIKE tgl) =====
        $todayAbsensi = [];
        try {
            $start = date('Y-m-d 00:00:00');
            $end   = date('Y-m-d 00:00:00', strtotime('+1 day'));
            $todayAbsensi = $this->db->table('absensi a')
                ->select('a.*, e.title AS event_title')
                ->join('events e', 'e.id = a.event_id', 'left')
                ->where('a.id_user', $userId)
                ->where('a.waktu_scan >=', $start)
                ->where('a.waktu_scan <',  $end)
                ->orderBy('a.waktu_scan', 'DESC')
                ->get()->getResultArray();
        } catch (\Throwable $e) {}

        // ===== Aktivitas dari notifikasi =====
        $activities = [];
        try {
            $rows = $this->notifModel->forUser($userId, 10);
            $activities = array_map([$this, 'mapNotifToActivity'], $rows);
        } catch (\Throwable $e) {}

        // ===== Fallback/plus: Event terbaru (5 item) ikut jadi aktivitas =====
        try {
            $eventActs = $this->eventActivities(5); // bikin item "Event baru"
            // gabung & urutkan by time desc
            $activities = array_merge($eventActs, $activities);
            usort($activities, fn($a,$b) => ($b['time'] <=> $a['time']));
            // batasi 12 biar ringkas
            $activities = array_slice($activities, 0, 12);
        } catch (\Throwable $e) {}

        // ===== Abstrak list table =====
        $abstrak = [];
        try {
            $abstrak = $this->absModel
                ->where('id_user', $userId)
                ->orderBy('id_abstrak', 'DESC')
                ->findAll(10);
        } catch (\Throwable $e) {}

        $stats = [
            'total_events'  => $totalEvents,
            'total_abstrak' => $totalAbstrak,
        ];

        // NOTE: render ke 'role/presenter/dashboard' (bukan '.../index')
        return view('role/presenter/dashboard', [
            'title'           => 'Dashboard Presenter',
            'stats'           => $stats,
            'pendingPayments' => $pendingPayments,
            'todayAbsensi'    => $todayAbsensi,
            'registrations'   => $regs,
            'activities'      => $activities,
            'abstrak'         => $abstrak,
        ]);
    }

    // ================= Helpers =================

    /** Bentukkan item aktivitas dari notifikasi */
    private function mapNotifToActivity(array $n): array
    {
        $meta = [];
        if (!empty($n['meta_json'])) {
            $m = json_decode((string)$n['meta_json'], true);
            if (is_array($m)) $meta = $m;
        }

        $ts = !empty($n['created_at']) ? strtotime($n['created_at']) : time();
        [$icon, $badge, $titleDefault] = $this->visualsByType((string)($n['type'] ?? 'info'));
        $title = $n['title'] ?: $titleDefault;

        $descParts = [];
        if (!empty($n['message']))          $descParts[] = $n['message'];
        if (!empty($meta['event_title']))   $descParts[] = $meta['event_title'];
        if (isset($meta['amount']) && is_numeric($meta['amount'])) {
            $descParts[] = 'Rp ' . number_format((float)$meta['amount'], 0, ',', '.');
        }
        $desc = implode(' — ', array_filter($descParts));

        return [
            'time'  => $ts,
            'title' => $title,
            'desc'  => $desc,
            'icon'  => $icon,
            'badge' => $badge,
            'link'  => $this->normalizeLink((string)($n['link'] ?? '')),
        ];
    }

    /** Bikin aktivitas "Event baru" dari tabel events */
    private function eventActivities(int $limit = 5): array
    {
        // Ambil 5 event paling baru berdasarkan COALESCE(created_at, updated_at, event_date)
        $rows = $this->db->table('events e')
            ->select("e.id, e.title, COALESCE(e.created_at, e.updated_at, e.event_date) AS ts", false)
            ->orderBy('ts', 'DESC')
            ->limit($limit)
            ->get()->getResultArray();

        $acts = [];
        foreach ($rows as $r) {
            $ts = !empty($r['ts']) ? strtotime($r['ts']) : time();
            $acts[] = [
                'time'  => $ts,
                'title' => 'Event baru: ' . (string)($r['title'] ?? '-'),
                'desc'  => 'Event baru ditambahkan',
                'icon'  => 'bi-calendar-plus',
                'badge' => 'primary',
                'link'  => '/presenter/events/detail/' . (int)$r['id'],
            ];
        }
        return $acts;
    }

    private function visualsByType(string $type): array
    {
        return match (strtolower($type)) {
            'payment_verified'   => ['bi-check2-circle', 'success', 'Pembayaran diterima'],
            'payment_rejected'   => ['bi-slash-circle',  'danger',  'Pembayaran ditolak'],
            'abstract_accepted'  => ['bi-patch-check',   'success', 'Abstrak diterima'],
            'abstract_revision'  => ['bi-pencil-square', 'warning', 'Abstrak diminta revisi'],
            'abstract_rejected'  => ['bi-x-circle',      'danger',  'Abstrak ditolak'],
            'event_new'          => ['bi-calendar-plus', 'primary', 'Event baru'],
            default              => ['bi-info-circle',   'secondary','Aktivitas'],
        };
    }

    private function normalizeLink(?string $href): string
    {
        $href = trim((string)$href);
        if ($href === '' || $href === '#') return '';
        $href = preg_replace('~^(https?:)/([^/])~i', '$1//$2', $href);

        if (preg_match('~^(https?):\/\/([^\/]+)(\/.*)?$~i', $href, $m)) {
            $host     = $m[2];
            $path     = $m[3] ?? '/';
            $currHost = $_SERVER['HTTP_HOST'] ?? '';
            if (strcasecmp($host, $currHost) === 0) return $path;
            return $m[1] . '://' . $host . $path;
        }
        if (strpos($href, '//') === 0) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https:' : 'http:';
            return $this->normalizeLink($scheme . $href);
        }
        return '/' . ltrim($href, '/');
    }
}