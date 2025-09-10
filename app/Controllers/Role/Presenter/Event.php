<?php

namespace App\Controllers\Role\Presenter;

use App\Controllers\BaseController;
use App\Models\EventModel;
use App\Models\AbstrakModel;
use App\Models\PembayaranModel;
use App\Models\AbsensiModel;

class Event extends BaseController
{
    protected EventModel $eventM;
    protected AbstrakModel $absM;
    protected PembayaranModel $payM;
    protected AbsensiModel $attM;

    public function __construct()
    {
        $this->eventM = new EventModel();
        $this->absM   = new AbstrakModel();
        $this->payM   = new PembayaranModel();
        $this->attM   = new AbsensiModel();
    }

    private function uid(): int
    {
        return (int) (session('id_user') ?? 0);
    }

    private function abstractLabel(?string $status): ?string
    {
        if ($status === null || $status === '') return null;
        $status = strtolower($status);
        // ubah "diterima" -> "Di-ACC"
        return $status === 'diterima' ? 'Di-ACC' : ucfirst(str_replace('_',' ', $status));
    }

    private function latestAbstract(int $eventId, int $userId): ?array
    {
        return $this->absM->where('event_id', $eventId)
                          ->where('id_user', $userId)
                          ->orderBy('tanggal_upload','DESC')
                          ->first() ?: null;
    }

    private function latestPayment(int $eventId, int $userId): ?array
    {
        return $this->payM->where('event_id', $eventId)
                          ->where('id_user', $userId)
                          ->orderBy('tanggal_bayar','DESC')
                          ->first() ?: null;
    }

    private function computeState(array $event, int $userId): array
    {
        $evId     = (int)$event['id'];
        $abstract = $this->latestAbstract($evId, $userId);
        $payment  = $this->latestPayment($evId, $userId);

        $registered = (bool)($abstract || $payment);

        $state = [
            'registered'      => $registered,
            'step'            => 1,
            'status_key'      => 'abstract_required',
            'status_label'    => 'Butuh Abstrak',
            'abstract'        => $abstract,
            'abstract_label'  => $this->abstractLabel($abstract['status'] ?? null),
            'payment'         => $payment,
            'payment_label'   => $payment['status'] ?? null,
            'verified'        => false,
        ];

        if (!$abstract) return $state;

        $ast = strtolower($abstract['status'] ?? '');
        switch ($ast) {
            case 'menunggu':
            case 'sedang_direview':
                $state['step']         = 2;
                $state['status_key']   = 'abstract_pending';
                $state['status_label'] = 'Abstrak Diproses';
                return $state;

            case 'revisi':
                $state['step']         = 1;
                $state['status_key']   = 'abstract_revision';
                $state['status_label'] = 'Perlu Revisi';
                return $state;

            case 'ditolak':
                $state['step']         = 1;
                $state['status_key']   = 'abstract_rejected';
                $state['status_label'] = 'Abstrak Ditolak';
                return $state;

            case 'diterima': // Di-ACC
                if (!$payment) {
                    $state['step']         = 3;
                    $state['status_key']   = 'payment_required';
                    $state['status_label'] = 'Menunggu Pembayaran';
                    return $state;
                }
                $pst = strtolower($payment['status'] ?? '');
                if ($pst === 'pending') {
                    $state['step']         = 4;
                    $state['status_key']   = 'payment_pending';
                    $state['status_label'] = 'Verifikasi Pembayaran';
                    return $state;
                }
                if ($pst === 'rejected') {
                    $state['step']         = 3;
                    $state['status_key']   = 'payment_rejected';
                    $state['status_label'] = 'Pembayaran Ditolak';
                    return $state;
                }
                if ($pst === 'verified') {
                    $state['step']         = 5;
                    $state['status_key']   = 'completed';
                    $state['status_label'] = 'Siap Ikut Event';
                    $state['verified']     = true;
                    return $state;
                }
                return $state;
        }
        return $state;
    }

    public function index()
    {
        $uid = $this->uid();
        if (!$uid || session('role') !== 'presenter') {
            return redirect()->to(site_url('auth/login'));
        }

        $q = trim((string)$this->request->getGet('q'));

        $builder = $this->eventM->where('is_active', true);
        if ($q !== '') {
            $builder->groupStart()->like('title', $q)->orLike('location', $q)->groupEnd();
        }
        $events = $builder->orderBy('event_date','ASC')->orderBy('event_time','ASC')->findAll();

        $out = [];
        foreach ($events as $e) {
            $state     = $this->computeState($e, $uid);
            $regOpen   = $this->eventM->isRegistrationOpen((int)$e['id']); // tutup otomatis jika lewat deadline / sudah mulai
            $out[] = [
                'id'             => (int)$e['id'],
                'title'          => $e['title'],
                'event_date'     => $e['event_date'],
                'event_time'     => $e['event_time'],
                'location'       => $e['location'] ?? '-',
                'format'         => $e['format'] ?? '-',   // online/offline/both
                'reg_open'       => (bool)$regOpen,
                'state'          => $state,
                'abstract_badge' => $state['abstract_label'],
                'payment_badge'  => $state['payment_label'],
            ];
        }

        return view('role/presenter/events/index', [
            'title'  => 'Event Presenter',
            'events' => $out,
            'q'      => $q,
        ]);
    }

    public function detail(int $id)
    {
        $uid = $this->uid();
        if (!$uid || session('role') !== 'presenter') {
            return redirect()->to(site_url('auth/login'));
        }

        $e = $this->eventM->find($id);
        if (!$e || !($e['is_active'] ?? false)) {
            return redirect()->to(site_url('presenter/events'))->with('error','Event tidak ditemukan atau tidak aktif.');
        }

        $state    = $this->computeState($e, $uid);
        $regOpen  = $this->eventM->isRegistrationOpen((int)$e['id']);

        return view('role/presenter/events/detail', [
            'title'   => 'Detail Event',
            'event'   => $e,
            'state'   => $state,
            'regOpen' => (bool)$regOpen,
        ]);
    }

    public function showRegistrationForm(int $id)
    {
        $uid = $this->uid();
        if (!$uid || session('role') !== 'presenter') {
            return redirect()->to(site_url('auth/login'));
        }

        $e = $this->eventM->find($id);
        if (!$e || !($e['is_active'] ?? false)) {
            return redirect()->to(site_url('presenter/events'))->with('error','Event tidak ditemukan atau tidak aktif.');
        }

        if (!$this->eventM->isRegistrationOpen($id)) {
            return redirect()->to(site_url('presenter/events/detail/'.$id))
                             ->with('error','Pendaftaran event telah ditutup.');
        }

        $abs = $this->latestAbstract($id, $uid);
        $pay = $this->latestPayment($id, $uid);

        if ($pay && strtolower($pay['status']) === 'verified') {
            return redirect()->to(site_url('presenter/events/detail/'.$id))
                             ->with('warning','Kamu sudah terverifikasi pada event ini.');
        }

        return view('role/presenter/events/register', [
            'title'    => 'Konfirmasi Pendaftaran',
            'event'    => $e,
            'abstract' => $abs,
            'payment'  => $pay,
        ]);
    }

    public function register(int $id)
    {
        $uid = $this->uid();
        if (!$uid || session('role') !== 'presenter') {
            return redirect()->to(site_url('auth/login'));
        }
        if (!$this->request->is('post')) {
            return redirect()->to(site_url('presenter/events/register/'.$id));
        }
        if (!$this->eventM->isRegistrationOpen($id)) {
            return redirect()->to(site_url('presenter/events/detail/'.$id))
                             ->with('error','Pendaftaran event telah ditutup.');
        }

        // langsung arahkan ke halaman upload abstrak
        return redirect()->to(site_url('presenter/abstrak?event_id=' . $id))
                         ->with('message','Silakan upload abstrak untuk memulai pendaftaran.');
    }
}
