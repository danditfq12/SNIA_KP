<?php

namespace App\Controllers\Role\Audience;

use App\Controllers\BaseController;
use App\Models\EventModel;
use App\Models\EventRegistrationModel;
use App\Models\UserModel;
use App\Services\NotificationService;

class Event extends BaseController
{
    public function index()
    {
        $q      = trim((string) $this->request->getGet('q'));
        $format = trim((string) $this->request->getGet('format'));

        $eventM = new EventModel();

        if ($q !== '') {
            $events = $eventM->searchEvents($q);
        } elseif (in_array($format, ['online','offline','both'], true)) {
            $events = $eventM->getEventsByFormat($format);
        } else {
            $events = $eventM->getActiveEvents();
        }

        return view('role/audience/events/index', [
            'events' => $events,
            'q'      => $q,
            'format' => $format,
        ]);
    }

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

    /** halaman pilih mode saja (tanpa form data peserta) */
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

    /** submit pilihan mode → buat registrasi → langsung ke instruksi pembayaran */
    public function register(int $id)
    {
        $idUser = (int) (session()->get('id_user') ?? 0);
        if ($idUser <= 0) {
            return redirect()->to('/auth/login')->with('error','Silakan login.');
        }

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

        // (opsional) kirim notifikasi
        try {
            $notif = new NotificationService();
            $notif->notify(
                $idUser,
                'registration',
                'Pendaftaran berhasil',
                "Kamu berhasil mendaftar event \"{$ev['title']}\". Lanjutkan ke instruksi pembayaran.",
                site_url('audience/pembayaran/instruction/' . $idReg)
            );
            $admins = (new UserModel())->select('id_user')->where('role','admin')->where('status','aktif')->findAll();
            foreach ($admins as $a) {
                $notif->notify((int)$a['id_user'], 'registration',
                    'Pendaftaran audience baru',
                    "Peserta baru mendaftar: {$ev['title']}.",
                    site_url('admin/event/detail/' . $id)
                );
            }
        } catch (\Throwable $e) { /* ignore */ }

        return redirect()->to('/audience/pembayaran/instruction/'.$idReg)
                         ->with('message','Pendaftaran berhasil. Silakan lakukan pembayaran.');
    }
}
