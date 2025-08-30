<?php

namespace App\Controllers\Role\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\AbstrakModel;
use App\Models\PembayaranModel;
use App\Models\ReviewModel;

class Dashboard extends BaseController
{
    protected $userModel;
    protected $abstrakModel;
    protected $pembayaranModel;
    protected $reviewModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->abstrakModel = new AbstrakModel();
        $this->pembayaranModel = new PembayaranModel();
        $this->reviewModel = new ReviewModel();
    }

    public function index()
    {
        // Statistics untuk dashboard
        $data = [
            'total_users' => $this->userModel->countAll(),
            'total_presenter' => $this->userModel->where('role', 'presenter')->countAllResults(),
            'total_audience' => $this->userModel->where('role', 'audience')->countAllResults(),
            'total_reviewer' => $this->userModel->where('role', 'reviewer')->countAllResults(),
            'total_abstrak' => $this->abstrakModel->countAll(),
            'abstrak_pending' => $this->abstrakModel->where('status', 'menunggu')->countAllResults(),
            'abstrak_diterima' => $this->abstrakModel->where('status', 'diterima')->countAllResults(),
            'abstrak_ditolak' => $this->abstrakModel->where('status', 'ditolak')->countAllResults(),
            'total_pembayaran' => $this->pembayaranModel->countAll(),
            'pembayaran_pending' => $this->pembayaranModel->where('status', 'pending')->countAllResults(),
            'pembayaran_verified' => $this->pembayaranModel->where('status', 'verified')->countAllResults(),
            'recent_users' => $this->userModel->orderBy('created_at', 'DESC')->limit(5)->findAll(),
            'recent_abstrak' => $this->abstrakModel->select('abstrak.*, users.nama_lengkap')
                                                  ->join('users', 'users.id_user = abstrak.id_user')
                                                  ->orderBy('tanggal_upload', 'DESC')
                                                  ->limit(5)
                                                  ->findAll()
        ];

        return view('role/admin/dashboard', $data);
    }
}