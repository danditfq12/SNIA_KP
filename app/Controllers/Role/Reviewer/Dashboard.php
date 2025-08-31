<?php

namespace App\Controllers\Role\Reviewer;

use App\Controllers\BaseController;
use App\Models\AbstrakModel;
use App\Models\ReviewModel;

class Dashboard extends BaseController
{
    protected $abstrakModel;
    protected $reviewModel;

    public function __construct()
    {
        $this->abstrakModel = new AbstrakModel();
        $this->reviewModel  = new ReviewModel();
    }

    public function index()
    {
        $idReviewer = session('id_user'); // ambil id reviewer dari session

        // Statistik
        $assigned = $this->reviewModel->where('id_reviewer', $idReviewer)->countAllResults();
        $pending  = $this->reviewModel->where('id_reviewer', $idReviewer)->where('keputusan', null)->countAllResults();
        $reviewed = $this->reviewModel->where('id_reviewer', $idReviewer)->whereIn('keputusan', ['Accepted','Rejected','Revisi'])->countAllResults();
        $dueToday = 0; // kalau belum ada field deadline, set 0

        $stat = [
            'assigned'  => $assigned,
            'pending'   => $pending,
            'reviewed'  => $reviewed,
            'due_today' => $dueToday
        ];

        // Ambil 5 abstrak terbaru (yang direview oleh reviewer ini)
        $recent = $this->abstrakModel
                       ->select('abstrak.*, users.nama_lengkap, kategori_abstrak.nama_kategori')
                       ->join('users', 'users.id_user = abstrak.id_user')
                       ->join('kategori_abstrak', 'kategori_abstrak.id_kategori = abstrak.id_kategori')
                       ->join('review', 'review.id_abstrak = abstrak.id_abstrak')
                       ->where('review.id_reviewer', $idReviewer)
                       ->orderBy('abstrak.tanggal_upload', 'DESC')
                       ->limit(5)
                       ->findAll();

        // Notifikasi → sementara kosong (nanti bisa ambil dari tabel notif kalau ada)
        $notifs = [];

        return view('role/reviewer/dashboard', [
            'stat'   => $stat,
            'recent' => $recent,
            'notifs' => $notifs,
        ]);
    }
}
