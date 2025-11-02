<?php

namespace App\Controllers\Role\Audience;

use App\Controllers\BaseController;
use App\Models\EventModel;
use App\Models\EventRegistrationModel;
use App\Models\UserModel;
use App\Services\NotificationService;

class Event extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /**
     * LIST EVENT:
     * - Filter q (judul/lokasi) & format
     * - Hitung reg_open per event
     * - Pisah ke openEvents/closedEvents
     * - Sertakan status registrasi/pembayaran user (myRegs)
     */
    public function index()
    {
        $qRaw   = (string)$this->request->getGet('q');
        $format = (string)$this->request->getGet('format');

        $q = trim($qRaw);
        $fmt = trim($format);
        $isSearching = ($q !== '') || ($fmt !== '');

        // ambil event aktif sesuai filter
        $eventM  = new EventModel();
        $builder = $eventM->where('is_active', true);

        if (in_array($fmt, ['online','offline','both'], true)) {
            $builder->where('format', $fmt);
        }
        if ($q !== '') {
            $builder->groupStart()
                        ->like('title', $q)
                        ->orLike('location', $q)
                    ->groupEnd();
        }

        $events = $builder
            ->orderBy('event_date', 'ASC')
            ->orderBy('event_time', 'ASC')
            ->findAll();

        // hitung reg_open (pendaftaran dibuka) per event
        $now = time();
        foreach ($events as &$e) {
            $regActive = !empty($e['registration_active']);
            $deadline  = !empty($e['registration_deadline']) ? strtotime($e['registration_deadline']) : null;
            $evStart   = !empty($e['event_date']) ? strtotime(($e['event_date'] ?? '') . ' ' . ($e['event_time'] ?? '00:00')) : null;

            $open = ($regActive === true);
            if ($open && $deadline && $deadline < $now) $open = false;
            if ($open && $evStart && $evStart < $now)   $open = false;

            $e['reg_open'] = $open;
        }
        unset($e);

        // pisah ke dua list
        $openEvents   = [];
        $closedEvents = [];
        foreach ($events as $ev) {
            if (!empty($ev['reg_open'])) $openEvents[] = $ev; else $closedEvents[] = $ev;
        }

        // status registrasi & pembayaran user (dipakai view untuk tombol/badge)
        $userId = (int)(session()->get('id_user') ?? 0);
        $myRegs = [];
        if ($userId) {
            $ids = array_column($events, 'id');
            if (!empty($ids)) {
                $regM = new EventRegistrationModel();
                $regs = $regM->select('id, id_event, status')
                             ->where('id_user', $userId)
                             ->whereIn('id_event', $ids)
                             ->findAll();

                // pembayaran terbaru per event
                $payRows = $this->db->table('pembayaran')
                    ->select('id_pembayaran, event_id, status, tanggal_bayar')
                    ->where('id_user', $userId)
                    ->whereIn('event_id', $ids)
                    ->orderBy('tanggal_bayar', 'DESC')
                    ->get()->getResultArray();

                $latestPay = [];
                foreach ($payRows as $p) {
                    $eid = (int)$p['event_id'];
                    if (!isset($latestPay[$eid])) {
                        $latestPay[$eid] = [
                            'payment_id'     => (int)$p['id_pembayaran'],
                            'payment_status' => $p['status'],
                        ];
                    }
                }

                foreach ($regs as $r) {
                    $eid = (int)$r['id_event'];
                    $myRegs[$eid] = [
                        'reg_id'         => (int)$r['id'],
                        'status'         => $r['status'],
                        'payment_id'     => $latestPay[$eid]['payment_id']     ?? null,
                        'payment_status' => $latestPay[$eid]['payment_status'] ?? null,
                    ];
                }
            }
        }

        return view('role/audience/events/index', [
            'title'         => 'Event Tersedia',
            'q'             => $qRaw,
            'format'        => $fmt,
            'isSearching'   => $isSearching,
            'openEvents'    => $openEvents,
            'closedEvents'  => $closedEvents,
            'myRegs'        => $myRegs,
            'total_all'     => count($events),
            'total_open'    => count($openEvents),
            'total_closed'  => count($closedEvents),
        ]);
    }

    /** detail event */
    public function detail(int $id)
    {
        $eventM = new EventModel();
        $ev     = $eventM->find($id);
        if (!$ev || !($ev['is_active'] ?? false)) {
            return redirect()->to('/audience/events')->with('error','Event tidak ditemukan atau tidak aktif.');
        }

        $idUser  = (int)(session()->get('id_user') ?? 0);
        $regM    = new EventRegistrationModel();
        
        // Ambil registrasi, tapi abaikan yang sudah batal/ditolak
        $allRegs = $regM->where('id_event', $id)
                        ->where('id_user', $idUser)
                        ->findAll();
        
        $myReg = null;
        foreach ($allRegs as $reg) {
            // Hanya ambil registrasi yang masih aktif (bukan batal/ditolak)
            if (!in_array($reg['status'] ?? '', ['batal', 'ditolak'], true)) {
                $myReg = $reg;
                break;
            }
        }
        
        $options = $eventM->getParticipationOptions($id, 'audience');
        $pricing = $eventM->getPricingMatrix($id);
        $isOpen  = $eventM->isRegistrationOpen($id);

        return view('role/audience/events/detail', [
            'event'   => $ev,
            'options' => $options,
            'pricing' => $pricing,
            'isOpen'  => $isOpen,
            'myReg'   => $myReg,
        ]);
    }

    /** halaman pilih mode (radio online/offline) */
    public function showRegistrationForm(int $id)
    {
        $eventM = new EventModel();
        $ev     = $eventM->find($id);
        if (!$ev || !($ev['is_active'] ?? false)) {
            return redirect()->to('/audience/events')->with('error','Event tidak ditemukan atau tidak aktif.');
        }
        if (!$eventM->isRegistrationOpen($id)) {
            return redirect()->to('/audience/events/detail/'.$id)->with('error','Pendaftaran event telah ditutup.');
        }

        $idUser   = (int)(session()->get('id_user') ?? 0);
        $regM     = new EventRegistrationModel();
        
        // Cek registrasi yang masih AKTIF (bukan batal/ditolak)
        $allRegs = $regM->where('id_event', $id)
                        ->where('id_user', $idUser)
                        ->findAll();
        
        $existing = null;
        foreach ($allRegs as $reg) {
            if (!in_array($reg['status'] ?? '', ['batal', 'ditolak'], true)) {
                $existing = $reg;
                break;
            }
        }
        
        if ($existing) {
            if (($existing['status'] ?? '') === 'menunggu_pembayaran') {
                return redirect()->to('/audience/pembayaran/instruction/'.$existing['id'])
                                 ->with('message','Kamu sudah terdaftar. Lanjutkan pembayaran.');
            }
            return redirect()->to('/audience/events/detail/'.$id)
                             ->with('warning','Kamu sudah terdaftar pada event ini.');
        }

        $options = $eventM->getParticipationOptions($id, 'audience');
        $pricing = $eventM->getPricingMatrix($id);

        return view('role/audience/events/register', [
            'event'   => $ev,
            'options' => $options,
            'pricing' => $pricing,
        ]);
    }

    /** submit pilihan mode → buat registrasi → ke instruksi pembayaran */
    public function register(int $id)
    {
        $idUser = (int)(session()->get('id_user') ?? 0);
        if ($idUser <= 0) return redirect()->to('/auth/login')->with('error','Silakan login.');

        $eventM = new EventModel();
        $ev     = $eventM->find($id);
        if (!$ev || !($ev['is_active'] ?? false)) {
            return redirect()->to('/audience/events')->with('error','Event tidak ditemukan atau tidak aktif.');
        }
        if (!$eventM->isRegistrationOpen($id)) {
            return redirect()->to('/audience/events/detail/'.$id)->with('error','Pendaftaran event telah ditutup.');
        }

        $regM = new EventRegistrationModel();
        
        // Cek registrasi yang masih AKTIF (bukan batal/ditolak)
        $allRegs = $regM->where('id_event', $id)
                        ->where('id_user', $idUser)
                        ->findAll();
        
        $existing = null;
        foreach ($allRegs as $reg) {
            if (!in_array($reg['status'] ?? '', ['batal', 'ditolak'], true)) {
                $existing = $reg;
                break;
            }
        }
        
        if ($existing) {
            if (($existing['status'] ?? '') === 'menunggu_pembayaran') {
                return redirect()->to('/audience/pembayaran/instruction/'.$existing['id'])
                                 ->with('message','Kamu sudah terdaftar. Lanjutkan pembayaran.');
            }
            return redirect()->to('/audience/events/detail/'.$id)
                             ->with('warning','Kamu sudah terdaftar pada event ini.');
        }

        $mode  = (string)$this->request->getPost('mode_kehadiran');
        $valid = $eventM->getParticipationOptions($id, 'audience');
        if (!in_array($mode, $valid, true)) {
            return redirect()->back()->withInput()->with('error','Mode kehadiran tidak valid.');
        }
        if ($eventM->hasReachedMaxParticipants($id, $mode)) {
            return redirect()->to('/audience/events/detail/'.$id)->with('error','Kuota peserta telah penuh.');
        }

        // === PERBAIKAN UTAMA: Reuse atau buat registrasi baru ===
        $oldCanceledReg = $regM->where('id_event', $id)
                               ->where('id_user', $idUser)
                               ->whereIn('status', ['batal', 'ditolak'])
                               ->orderBy('id', 'DESC')
                               ->first();
        
        if ($oldCanceledReg) {
            // Update registrasi lama menjadi aktif kembali
            $regM->update($oldCanceledReg['id'], [
                'status' => 'menunggu_pembayaran',
                'mode_kehadiran' => $mode,
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            $idReg = $oldCanceledReg['id'];
            
            // === FIX: Hapus pembayaran lama dengan event_id dan id_user ===
            $this->db->table('pembayaran')
                     ->where('event_id', $id)
                     ->where('id_user', $idUser)
                     ->whereIn('status', ['canceled', 'expired', 'rejected'])
                     ->delete();
        } else {
            // Buat registrasi baru jika tidak ada yang dibatalkan
            $idReg = $regM->createRegistration($id, $idUser, $mode);
        }

        // notifikasi
        try {
            $notif = new NotificationService();
            $notif->notify(
                $idUser, 'registration', 'Pendaftaran berhasil',
                "Kamu berhasil mendaftar event \"{$ev['title']}\". Lanjutkan pembayaran.",
                site_url('audience/pembayaran/instruction/' . $idReg)
            );
            $admins = (new UserModel())->select('id_user')->where('role','admin')->where('status','aktif')->findAll();
            foreach ($admins as $a) {
                $notif->notify(
                    (int)$a['id_user'], 'registration', 'Pendaftaran audience baru',
                    "Peserta baru mendaftar: {$ev['title']}.",
                    site_url('admin/event/detail/' . $id)
                );
            }
        } catch (\Throwable $e) {
            // Silent fail untuk notifikasi
        }

        return redirect()->to('/audience/pembayaran/instruction/'.$idReg)
                         ->with('message','Pendaftaran berhasil. Silakan lakukan pembayaran.');
    }
}