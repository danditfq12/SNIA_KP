<?php

namespace App\Controllers\Role\Presenter;

use App\Controllers\BaseController;
use App\Models\EventModel;
use App\Models\EventRegistrationModel;
use App\Models\AbstrakModel;
use App\Models\PembayaranModel;
use App\Models\AbsensiModel;

class Event extends BaseController
{
    protected EventModel $eventModel;
    protected EventRegistrationModel $regModel;
    protected AbstrakModel $abstrakModel;
    protected PembayaranModel $pembayaranModel;
    protected AbsensiModel $absensiModel;

    public function __construct()
    {
        $this->eventModel      = new EventModel();
        $this->regModel        = new EventRegistrationModel();
        $this->abstrakModel    = new AbstrakModel();
        $this->pembayaranModel = new PembayaranModel();
        $this->absensiModel    = new AbsensiModel();
        helper(['date', 'text']);
    }

    /** INDEX: daftar event (tersedia & ditutup), status singkat per-event */
    public function index()
    {
        $userId   = (int) session()->get('id_user');
        $q        = trim($this->request->getGet('q') ?? '');

        // Event aktif (untuk box "tersedia")
        $available = $this->eventModel->getEventsWithOpenRegistration();

        // Semua event (untuk pisahin yg ditutup)
        $all = $this->eventModel
            ->orderBy('event_date', 'DESC')
            ->findAll();

        // Filter by search (optional)
        if ($q !== '') {
            $filterBy = function(array $rows) use ($q) {
                return array_values(array_filter($rows, function($e) use ($q) {
                    $hay = strtolower(($e['title'] ?? '') . ' ' . ($e['description'] ?? '') . ' ' . ($e['location'] ?? ''));
                    return str_contains($hay, strtolower($q));
                }));
            };
            $available = $filterBy($available);
            $all       = $filterBy($all);
        }

        // Registrasi user map-by-event
        $userRegs = [];
        foreach ($this->regModel->listByUser($userId) as $r) {
            $userRegs[(int)$r['id_event']] = $r;
        }

        // Buat status singkat untuk index
        $statusIndex = [];
        foreach ($all as $ev) {
            $statusIndex[(int)$ev['id']] = $this->computeFlowStatus($ev['id'], $userId);
        }

        // Event ditutup = all - available - (masih terdaftar meskipun pendaftaran tutup tetap muncul di "ditutup")
        $availableIds = array_column($available, 'id');
        $closed = array_values(array_filter($all, function($e) use ($availableIds){
            return !in_array($e['id'], $availableIds);
        }));

        return view('role/presenter/events/index', [
            'title'       => 'Event',
            'available'   => $available,
            'closed'      => $closed,
            'userRegs'    => $userRegs,
            'statusIndex' => $statusIndex,
            'q'           => $q,
        ]);
    }

    /** DETAIL: informasi lengkap + CTA sesuai state */
    public function detail($id)
    {
        $userId = (int) session()->get('id_user');
        $event  = $this->eventModel->find($id);
        if (!$event) {
            return redirect()->to('/presenter/events')->with('error','Event tidak ditemukan.');
        }

        $reg = $this->regModel->findUserReg($event['id'], $userId);

        // abstrak terbaru user di event ini (yang bukan ditolak final)
        $abstrak = $this->abstrakModel
            ->where('id_user', $userId)
            ->where('event_id', $event['id'])
            ->orderBy('id_abstrak','DESC')
            ->first();

        // pembayaran terakhir di event ini
        $payment = $this->pembayaranModel
            ->where('id_user', $userId)
            ->where('event_id', $event['id'])
            ->orderBy('id_pembayaran', 'DESC')
            ->first();

        $flow = $this->computeFlowStatus($event['id'], $userId);

        return view('role/presenter/events/detail', [
            'title'   => 'Detail Event',
            'event'   => $event,
            'reg'     => $reg,
            'abstrak' => $abstrak,
            'payment' => $payment,
            'flow'    => $flow,
            'price'   => $this->eventModel->getEventPrice($event['id'], 'presenter', 'offline'),
            'isOpen'  => $this->eventModel->isRegistrationOpen($event['id']),
        ]);
    }

    /** REGISTER: langsung tercatat (role presenter → offline) */
    public function register($id)
    {
        $userId = (int) session()->get('id_user');

        // Safety: pendaftaran masih dibuka?
        if (!$this->eventModel->isRegistrationOpen((int)$id)) {
            return redirect()->to('/presenter/events/detail/'.$id)
                ->with('error', 'Pendaftaran untuk event ini sudah ditutup.');
        }

        $regId = $this->regModel->createPresenterRegistration((int)$id, $userId);
        if ($regId) {
            return redirect()->to('/presenter/events/detail/'.$id)
                ->with('success', 'Berhasil mendaftar. Silakan kirim abstrak.');
        }

        return redirect()->to('/presenter/events/detail/'.$id)->with('error','Gagal mendaftar.');
    }

    /** Batalkan pendaftaran (hanya kalau belum kirim abstrak) */
    public function cancel($id)
    {
        $userId = (int) session()->get('id_user');
        $reg    = $this->regModel->findUserReg((int)$id, $userId);
        if (!$reg) {
            return redirect()->to('/presenter/events/detail/'.$id)->with('error','Pendaftaran tidak ditemukan.');
        }

        // Sudah ada abstrak? larang batal
        $hasAbstract = $this->abstrakModel
            ->where('id_user', $userId)
            ->where('event_id', (int)$id)
            ->countAllResults() > 0;

        if ($hasAbstract) {
            return redirect()->to('/presenter/events/detail/'.$id)
                ->with('error','Tidak dapat membatalkan karena Anda sudah mengunggah abstrak.');
        }

        $this->regModel->delete($reg['id']);
        return redirect()->to('/presenter/events')->with('success','Pendaftaran dibatalkan.');
    }

    /** Menyusun status flow untuk index & detail */
    private function computeFlowStatus(int $eventId, int $userId): array
    {
        $reg = $this->regModel->findUserReg($eventId, $userId);

        // default
        $state = 'belum_daftar';
        $label = 'Belum terdaftar';
        $hint  = 'Klik Daftar untuk mulai';
        $can   = ['register' => true];

        if ($reg) {
            // cek abstrak
            $ab = $this->abstrakModel
                ->where('id_user', $userId)
                ->where('event_id', $eventId)
                ->orderBy('id_abstrak','DESC')
                ->first();

            // cek payment
            $pay = $this->pembayaranModel
                ->where('id_user', $userId)
                ->where('event_id', $eventId)
                ->orderBy('id_pembayaran','DESC')
                ->first();

            // cek absensi (sudah hadir?)
            $hadir = $this->absensiModel
                ->where('id_user', $userId)
                ->where('event_id', $eventId)
                ->where('status', 'hadir')
                ->countAllResults() > 0;

            if (!$ab) {
                $state = 'upload_abstrak';
                $label = 'Silakan upload abstrak';
                $hint  = 'Wajib sebelum pembayaran';
                $can   = ['upload' => true, 'cancel' => true];
            } else {
                switch ($ab['status']) {
                    case 'menunggu':
                    case 'sedang_direview':
                        $state = 'menunggu_abstrak';
                        $label = 'Menunggu hasil abstrak';
                        $hint  = 'Tunggu ACC/revisi/ditolak';
                        $can   = ['view_abstrak' => true];
                        break;
                    case 'revisi':
                        $state = 'revisi_abstrak';
                        $label = 'Revisi abstrak';
                        $hint  = 'Silakan unggah ulang dokumen revisi';
                        $can   = ['reupload' => true];
                        break;
                    case 'ditolak':
                        $state = 'abstrak_ditolak';
                        $label = 'Abstrak ditolak';
                        $hint  = 'Anda dapat kirim ulang abstrak baru';
                        $can   = ['upload' => true];
                        break;
                    case 'diterima':
                        // cek payment
                        if (!$pay) {
                            $state = 'bayar';
                            $label = 'Silakan lakukan pembayaran';
                            $hint  = 'Unggah bukti pembayaran';
                            $can   = ['pay' => true];
                        } else {
                            if ($pay['status'] === 'pending') {
                                $state = 'pembayaran_pending';
                                $label = 'Menunggu verifikasi pembayaran';
                                $hint  = 'Admin akan memverifikasi';
                                $can   = ['pay_detail' => true];
                            } elseif ($pay['status'] === 'rejected') {
                                $state = 'pembayaran_ditolak';
                                $label = 'Pembayaran ditolak';
                                $hint  = 'Periksa catatan & unggah ulang';
                                $can   = ['pay_reupload' => true];
                            } elseif ($pay['status'] === 'verified') {
                                if ($hadir) {
                                    $state = 'sudah_absen';
                                    $label = 'Sudah absen';
                                    $hint  = 'Terima kasih telah hadir';
                                    $can   = ['absen_detail' => true];
                                } else {
                                    $state = 'siap_absen';
                                    $label = 'Silakan absen saat event';
                                    $hint  = 'Tersedia saat event berlangsung';
                                    $can   = ['absen' => true];
                                }
                            }
                        }
                        break;
                }
            }
        }

        return [
            'state' => $state,
            'label' => $label,
            'hint'  => $hint,
            'can'   => $can,
            'reg'   => $reg,
        ];
    }
}