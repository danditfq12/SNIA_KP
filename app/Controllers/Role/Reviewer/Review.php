<?php

namespace App\Controllers\Role\Reviewer;

use App\Controllers\BaseController;
use App\Models\ReviewModel;
use App\Models\AbstrakModel;

class Review extends BaseController
{
    protected $reviewModel;
    protected $abstrakModel;

    public function __construct()
    {
        $this->reviewModel  = new ReviewModel();
        $this->abstrakModel = new AbstrakModel();
    }

    /**
     * Store review - dipanggil dari form POST /reviewer/review/{id}
     */
    public function store($abstrakId)
    {
        $idReviewer = (int) (session('id_user') ?? 0);
        if (!$idReviewer || session('role') !== 'reviewer') {
            return redirect()->to(site_url('auth/login'));
        }

        $abstrakId = (int) $abstrakId;
        
        // Validation
        $validation = \Config\Services::validation();
        $rules = [
            'keputusan' => 'required|in_list[Accepted,Rejected,Revisi]',
            'komentar'  => 'required|min_length[10]|max_length[1000]'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $validation->getErrors());
        }

        try {
            $keputusan = $this->request->getPost('keputusan');
            $komentar = $this->request->getPost('komentar');

            // Cari review yang sudah ada (yang di-assign admin)
            $existingReview = $this->reviewModel
                ->where('id_abstrak', $abstrakId)
                ->where('id_reviewer', $idReviewer)
                ->first();

            if (!$existingReview) {
                return redirect()->to('reviewer/abstrak')
                    ->with('error', 'Review assignment tidak ditemukan.');
            }

            // UPDATE review (bukan insert)
            $updated = $this->reviewModel->update($existingReview['id_review'], [
                'keputusan'      => $keputusan,
                'komentar'       => $komentar,
                'tanggal_review' => date('Y-m-d H:i:s'),
            ]);

            if (!$updated) {
                throw new \Exception('Failed to update review');
            }

            // Update status abstrak juga
            $statusAbstrak = match(strtolower($keputusan)) {
                'accepted' => 'diterima',
                'rejected' => 'ditolak', 
                'revisi'   => 'revisi',
                default    => 'sedang_direview'
            };

            $this->abstrakModel->update($abstrakId, ['status' => $statusAbstrak]);

            return redirect()->to('reviewer/abstrak')
                ->with('success', 'Review berhasil disimpan!');

        } catch (\Exception $e) {
            log_message('error', 'Review submission error: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Terjadi kesalahan saat menyimpan review.');
        }
    }
}
