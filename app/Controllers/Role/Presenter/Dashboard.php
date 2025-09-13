<?php

namespace App\Controllers\Role\Presenter;

use App\Controllers\BaseController;
use App\Models\EventRegistrationModel;
use App\Models\AbstrakModel;
use App\Models\PembayaranModel;
use App\Models\AbsensiModel;
use App\Models\DokumenModel;

class Dashboard extends BaseController
{
    protected $regModel;
    protected $abstrakModel;
    protected $pembayaranModel;
    protected $absensiModel;
    protected $dokumenModel;

    public function __construct()
    {
        $this->regModel        = new EventRegistrationModel();
        $this->abstrakModel    = new AbstrakModel();
        $this->pembayaranModel = new PembayaranModel();
        $this->absensiModel    = new AbsensiModel();
        $this->dokumenModel    = new DokumenModel();
    }

    public function index()
    {
        $userId = session()->get('id_user');

        // Ringkasan data
        $registrations = $this->regModel->listByUser($userId);
        $abstrak       = $this->abstrakModel->getByUserWithDetails($userId);
        $payments      = $this->pembayaranModel->where('id_user', $userId)->findAll();
        $attendance    = $this->absensiModel->getUserAttendanceHistory($userId);
        $docs          = $this->dokumenModel->getUserDocs($userId);

        // Absensi hari ini
        $todayAbsensi = $this->absensiModel->getUserAttendanceByDate($userId, date('Y-m-d'));

        // Pembayaran pending
        $pendingPayments = array_filter($payments, fn($p) => $p['status'] === 'pending');

        $stats = [
            'total_events'     => count($registrations),
            'total_abstrak'    => count($abstrak),
            'total_pembayaran' => count($payments),
            'total_absensi'    => count($attendance),
            'total_dokumen'    => count($docs),
        ];

        return view('role/presenter/dashboard', [
            'title'           => 'Dashboard Presenter',
            'stats'           => $stats,
            'registrations'   => $registrations,
            'abstrak'         => $abstrak,
            'payments'        => $payments,
            'attendance'      => $attendance,
            'docs'            => $docs,
            'todayAbsensi'    => $todayAbsensi,
            'pendingPayments' => $pendingPayments,
        ]);
    }
}