<?php

namespace App\Controllers\Role\Audience;

use App\Controllers\BaseController;
use App\Models\EventModel;
use App\Models\EventRegistrationModel;

class Event extends BaseController
{
    // GET /audience/events
    public function index()
    {
        $eventM = new EventModel();

        // Ambil event yang sedang membuka pendaftaran
        try {
            $events = $eventM->getEventsWithOpenRegistration();
        } catch (\Throwable $e) {
            // fallback sederhana jika helper tidak ada
            $events = $eventM->where('is_active', true)
                             ->where('event_date >=', date('Y-m-d'))
                             ->orderBy('event_date', 'ASC')
                             ->findAll();
        }

        return view('role/audience/events/index', compact('events'));
    }

    // GET /audience/events/detail/{id}
    public function detail(int $id)
    {
        $eventM = new EventModel();
        $event  = $eventM->find($id);
        if (!$event) {
            return redirect()->to('/audience/events')->with('error', 'Event tidak ditemukan.');
        }

        $options = $eventM->getParticipationOptions($id, 'audience'); // ['online','offline'] sesuai format
        $pricing = $eventM->getPricingMatrix($id);                     // harga audience online/offline

        return view('role/audience/events/detail', compact('event','options','pricing'));
    }

    // GET /audience/events/register/{id}
    public function showRegistrationForm(int $id)
    {
        $eventM = new EventModel();

        if (!$eventM->isRegistrationOpen($id)) {
            return redirect()->to('/audience/events')->with('error', 'Pendaftaran event ditutup.');
        }

        $event   = $eventM->find($id);
        $options = $eventM->getParticipationOptions($id, 'audience');
        $pricing = $eventM->getPricingMatrix($id);

        return view('role/audience/events/register', compact('event','options','pricing'));
    }

    // POST /audience/events/register/{id}
    public function register(int $id)
    {
        $rules = ['mode_kehadiran' => 'required|in_list[online,offline]'];
        if (! $this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $this->validator->getErrors());
        }

        $eventM = new EventModel();
        if (!$eventM->isRegistrationOpen($id)) {
            return redirect()->to('/audience/events')->with('error', 'Pendaftaran event ditutup.');
        }

        $idUser = (int) (session()->get('id_user') ?? 0);
        if ($idUser <= 0) {
            return redirect()->to('/auth/login')->with('error', 'Silakan login terlebih dahulu.');
        }

        $mode    = (string) $this->request->getPost('mode_kehadiran');
        $allowed = $eventM->getParticipationOptions($id, 'audience');
        if (!in_array($mode, $allowed, true)) {
            return redirect()->back()->with('error', 'Mode kehadiran tidak tersedia untuk event ini.');
        }

        $regM = new EventRegistrationModel();
        try {
            // Model menghindari duplikasi (unique id_event+id_user)
            $idReg = $regM->createRegistration($id, $idUser, $mode);
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()->with('error', 'Gagal mendaftar: '.$e->getMessage());
        }

        return redirect()->to('/audience/pembayaran/create/'.$idReg)
                         ->with('message', 'Pendaftaran berhasil. Lanjutkan pembayaran.');
    }

    // POST /audience/events/calculate-price (AJAX)
    public function calculatePrice()
    {
        $idEvent = (int) $this->request->getPost('id_event');
        $mode    = (string) ($this->request->getPost('mode') ?? 'online');
        $voucher = trim((string) ($this->request->getPost('voucher') ?? ''));

        $eventM = new EventModel();
        $price  = (float) $eventM->getEventPrice($idEvent, 'audience', $mode);

        // TODO: terapkan voucher jika perlu (sementara belum)
        return $this->response->setJSON([
            'ok'               => true,
            'price'            => $price,
            'base'             => $price,
            'mode'             => $mode,
            'voucher'          => $voucher,
            'voucher_applied'  => false,
        ]);
    }
}
