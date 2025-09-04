<?php

namespace App\Controllers\Role\Audience;

use App\Controllers\BaseController;
use App\Models\EventModel;
use App\Models\EventRegistrationModel;

class Event extends BaseController
{
    /** List event aktif (dengan optional pencarian) */
    public function index()
    {
        $q      = trim((string) $this->request->getGet('q'));
        $format = trim((string) $this->request->getGet('format')); // 'online'|'offline'|'both'|''

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

    /** Detail event + opsi audience */
    public function detail(int $id)
    {
        $eventM = new EventModel();
        $ev     = $eventM->find($id);
        if (!$ev || !($ev['is_active'] ?? false)) {
            return redirect()->to('/audience/events')->with('error','Event tidak ditemukan atau tidak aktif.');
        }

        $idUser   = (int) (session()->get('id_user') ?? 0);
        $regM     = new EventRegistrationModel();
        $myReg    = $regM->findUserReg($id, $idUser);
        $options  = $eventM->getParticipationOptions($id, 'audience');
        $pricing  = $eventM->getPricingMatrix($id);
        $isOpen   = $eventM->isRegistrationOpen($id);

        return view('role/audience/events/detail', [
            'event'   => $ev,
            'options' => $options,
            'pricing' => $pricing,
            'isOpen'  => $isOpen,
            'myReg'   => $myReg,
        ]);
    }

    /** Form daftar (tanpa tombol hitung) */
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

    // ⛔️ Cek: sudah terdaftar?
    $idUser = (int) (session()->get('id_user') ?? 0);
    $regM   = new EventRegistrationModel();
    $existing = $regM->findUserReg($id, $idUser);
    if ($existing) {
        // arahkan user ke langkah berikutnya sesuai status
        if (($existing['status'] ?? '') === 'menunggu_pembayaran') {
            return redirect()->to('/audience/pembayaran/instruction/'.$existing['id'])
                             ->with('warning','Kamu sudah terdaftar pada event ini. Lanjutkan ke pembayaran.');
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


    /** Proses daftar → arahkan ke instruksi rekening */
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

    $regM = new EventRegistrationModel();
    $existing = $regM->findUserReg($id, $idUser);
    if ($existing) {
        if (($existing['status'] ?? '') === 'menunggu_pembayaran') {
            return redirect()->to('/audience/pembayaran/instruction/'.$existing['id'])
                             ->with('warning','Kamu sudah terdaftar. Lanjutkan ke pembayaran.');
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

    return redirect()->to('/audience/pembayaran/instruction/'.$idReg)
                     ->with('message','Pendaftaran berhasil. Silakan ikuti instruksi pembayaran.');
        }
}