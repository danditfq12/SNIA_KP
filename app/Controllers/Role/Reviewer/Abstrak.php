<?php

namespace App\Controllers\Role\Reviewer;

use App\Controllers\BaseController;
use App\Models\AbstrakModel;
use App\Models\ReviewModel;

class Abstrak extends BaseController
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
        // ambil semua abstrak (sementara tanpa filter reviewer kategori)
        $data['abstrak'] = $this->abstrakModel->getAbstrakWithDetails();
        return view('role/reviewer/abstrak', $data);
    }

    public function detail($id)
    {
        $abstrak = $this->abstrakModel
                        ->select('abstrak.*, users.nama_lengkap, kategori_abstrak.nama_kategori')
                        ->join('users', 'users.id_user = abstrak.id_user')
                        ->join('kategori_abstrak', 'kategori_abstrak.id_kategori = abstrak.id_kategori')
                        ->where('abstrak.id_abstrak', $id)
                        ->first();

        if (!$abstrak) {
            return redirect()->to('reviewer/abstrak')->with('error', 'Abstrak tidak ditemukan');
        }

        return view('role/reviewer/detail_abstrak', ['abstrak' => $abstrak]);
    }

    public function saveReview()
    {
        $idReviewer = session('id_user');

        $data = [
            'id_abstrak'     => $this->request->getPost('id_abstrak'),
            'id_reviewer'    => $idReviewer,
            'keputusan'      => $this->request->getPost('keputusan'),
            'komentar'       => $this->request->getPost('komentar'),
            'tanggal_review' => date('Y-m-d H:i:s')
        ];

        $this->reviewModel->insert($data);

        return redirect()->to('reviewer/riwayat')->with('success', 'Review berhasil disimpan');
    }
}
