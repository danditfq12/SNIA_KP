<?php

namespace App\Controllers\Role\Reviewer;

use App\Controllers\BaseController;
use App\Models\ReviewModel;

class Riwayat extends BaseController
{
    protected $reviewModel;

    public function __construct()
    {
        $this->reviewModel = new ReviewModel();
    }

    public function index()
    {
        $idReviewer = session('id_user');

        $reviews = $this->reviewModel
                        ->select('review.*, abstrak.judul, users.nama_lengkap')
                        ->join('abstrak', 'abstrak.id_abstrak = review.id_abstrak')
                        ->join('users', 'users.id_user = abstrak.id_user')
                        ->where('review.id_reviewer', $idReviewer)
                        ->orderBy('review.tanggal_review', 'DESC')
                        ->findAll();

        return view('role/reviewer/riwayat', ['reviews' => $reviews]);
    }
}
