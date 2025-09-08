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

    /** LIST EVENT + filter + status registrasi & pembayaran */
    public function index()
    {
        $q      = trim((string) $this->request->getGet('q'));
        $format = trim((string) $this->request->getGet('format'));

        $eventM  = new EventModel();
        $builder = $eventM->where('is_active', true);

        if (in_array($format, ['online','offline','both'], true)) {
            $builder->where('format', $format);
        }
        if ($q !== '') {
            $builder->groupStart()
                        ->like('title', $q)
                        ->orLike('location', $q)
                    ->groupEnd();
        }

        $events = $builder->orderBy('event_date','ASC')->orderBy('event_time','ASC')->findAll();

        // flag reg_open
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

        // status registrasi & pembayaran milik user
        $userId = (int) (session()->get('id_user') ?? 0);
        $myRegs = [];
        if ($userId && !empty($events)) {
            $ids  = array_column($events, 'id');
            if (!empty($ids)) {
                $regM = new EventRegistrationModel();
                $regs = $regM->select('id, id_event, status')
                             ->where('id_user', $userId)
                             ->whereIn('id_event', $ids)->findAll();

                // pembayaran terbaru per event
                $payRows = $this->db->table('pembayaran')
                            ->select('id_pembayaran, event_id, status, tanggal_bayar')
                            ->where('id_user', $userId)
                            ->whereIn('event_id', $ids)
                            ->orderBy('tanggal_bayar','DESC')
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
                        'status'         => $r['status'],
                        'payment_id'     => $latestPay[$eid]['payment_id']     ?? null,
                        'payment_status' => $latestPay[$eid]['payment_status'] ?? null,
                    ];
                }
            }
        }

        return view('role/audience/events/index', [
            'events' => $events,
            'q'      => $q,
            'format' => $format,
            'myRegs' => $myRegs,
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

        $idUser  = (int) (session()->get('id_user') ?? 0);
        $regM    = new EventRegistrationModel();
        $myReg   = $regM->findUserReg($id, $idUser);
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

        $idUser   = (int) (session()->get('id_user') ?? 0);
        $regM     = new EventRegistrationModel();
        $existing = $regM->findUserReg($id, $idUser);
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
        $idUser = (int) (session()->get('id_user') ?? 0);
        if ($idUser <= 0) return redirect()->to('/auth/login')->with('error','Silakan login.');

        $eventM = new EventModel();
        $ev     = $eventM->find($id);
        if (!$ev || !($ev['is_active'] ?? false)) {
            return redirect()->to('/audience/events')->with('error','Event tidak ditemukan atau tidak aktif.');
        }
        if (!$eventM->isRegistrationOpen($id)) {
            return redirect()->to('/audience/events/detail/'.$id)->with('error','Pendaftaran event telah ditutup.');
        }

        $regM     = new EventRegistrationModel();
        $existing = $regM->findUserReg($id, $idUser);
        if ($existing) {
            if (($existing['status'] ?? '') === 'menunggu_pembayaran') {
                return redirect()->to('/audience/pembayaran/instruction/'.$existing['id'])
                                 ->with('message','Kamu sudah terdaftar. Lanjutkan pembayaran.');
            }
            return redirect()->to('/audience/events/detail/'.$id)
                             ->with('warning','Kamu sudah terdaftar pada event ini.');
        }

        $mode  = (string) $this->request->getPost('mode_kehadiran');
        $valid = $eventM->getParticipationOptions($id, 'audience');
        if (!in_array($mode, $valid, true)) {
            return redirect()->back()->withInput()->with('error','Mode kehadiran tidak valid.');
        }
        if ($eventM->hasReachedMaxParticipants($id, $mode)) {
            return redirect()->to('/audience/events/detail/'.$id)->with('error','Kuota peserta telah penuh.');
        }

        $idReg = $regM->createRegistration($id, $idUser, $mode);

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
                    (int)$a['id_user'], 'registration','Pendaftaran audience baru',
                    "Peserta baru mendaftar: {$ev['title']}.",
                    site_url('admin/event/detail/' . $id)
                );
            }
        } catch (\Throwable $e) {}

        return redirect()->to('/audience/pembayaran/instruction/'.$idReg)
                         ->with('message','Pendaftaran berhasil. Silakan lakukan pembayaran.');
    }
}
