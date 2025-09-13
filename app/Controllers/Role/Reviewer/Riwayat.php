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

        if (!$idReviewer) {
            return redirect()->to('auth/login');
        }

        // ✅ Fixed: Include nama_kategori yang dibutuhkan view
        $riwayat = $this->reviewModel
            ->select('
                review.*, 
                abstrak.judul, 
                users.nama_lengkap,
                kategori_abstrak.nama_kategori
            ')
            ->join('abstrak', 'abstrak.id_abstrak = review.id_abstrak')
            ->join('users', 'users.id_user = abstrak.id_user')
            ->join('kategori_abstrak', 'kategori_abstrak.id_kategori = abstrak.id_kategori', 'left')
            ->where('review.id_reviewer', $idReviewer)
            ->where('review.keputusan IS NOT NULL') // hanya yang sudah di-review
            ->orderBy('review.tanggal_review', 'DESC')
            ->findAll();

        return view('role/reviewer/riwayat', [
            'title'   => 'Riwayat Review',
            'riwayat' => $riwayat
        ]);
    }
}
