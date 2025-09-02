<?php

namespace App\Controllers\Role\Audience;

use App\Controllers\BaseController;
use App\Models\EventModel;
use App\Models\EventRegistrationModel;

class Dashboard extends BaseController
{
    public function index()
    {
        $idUser = (int) (session()->get('id_user') ?? 0);

        $eventM = new EventModel();
        $regM   = new EventRegistrationModel();

        // 1) Event Tersedia (open registration)
        //    fallback sederhana kalau helper tidak ada
        try {
            $eventsOpen = $eventM->getEventsWithOpenRegistration();
        } catch (\Throwable $e) {
            $eventsOpen = $eventM->where('is_active', true)
                                 ->where('event_date >=', date('Y-m-d'))
                                 ->orderBy('event_date', 'ASC')
                                 ->findAll();
        }

        // 2) Semua registrasi user + info event
        $regs = $regM->select('event_registrations.*, e.title, e.event_date, e.event_time, e.format, e.location')
                     ->join('events e', 'e.id = event_registrations.id_event', 'left')
                     ->where('event_registrations.id_user', $idUser)
                     ->orderBy('e.event_date', 'DESC')
                     ->orderBy('event_registrations.id', 'DESC')
                     ->findAll();

        // 3) Bagi jadi:
        //    - attended: sudah lunas & tanggal lewat
        //    - upcomingPaid: sudah lunas & hari ini/ke depan (berjalan/akan)
        $today = date('Y-m-d');
        $attended = [];
        $upcomingPaid = [];

        foreach ($regs as $r) {
            $status    = (string) ($r['status'] ?? '');
            $eventDate = $r['event_date'] ?? null;
            if (!$eventDate) continue;

            $dateOnly = substr($eventDate, 0, 10);
            $isPaid   = in_array($status, ['lunas', 'verified', 'paid'], true);

            if ($isPaid && $dateOnly >= $today) {
                $upcomingPaid[] = $r;
            } elseif ($isPaid && $dateOnly <  $today) {
                $attended[] = $r;
            }
        }

        // Urutkan: upcoming asc, attended desc
        usort($upcomingPaid, fn($a, $b) => strcmp(($a['event_date'] ?? ''), ($b['event_date'] ?? '')));
        usort($attended,     fn($a, $b) => strcmp(($b['event_date'] ?? ''), ($a['event_date'] ?? '')));

        // (opsional) batasi yang tampil di dashboard biar ringkas
        // $eventsOpen   = array_slice($eventsOpen, 0, 6);
        // $attended     = array_slice($attended, 0, 6);
        // $upcomingPaid = array_slice($upcomingPaid, 0, 6);

        return view('role/audience/dashboard', [
            'eventsOpen'   => $eventsOpen,
            'attended'     => $attended,
            'upcomingPaid' => $upcomingPaid,
        ]);
    }
}
