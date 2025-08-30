<?php

namespace App\Controllers\Role\Admin;

use App\Controllers\BaseController;
use App\Models\AbstrakModel;
use App\Models\UserModel;
use App\Models\KategoriAbstrakModel;
use App\Models\ReviewerKategoriModel;
use App\Models\ReviewModel;

class Abstrak extends BaseController
{
    protected $abstrakModel;
    protected $userModel;
    protected $kategoriModel;
    protected $reviewerKategoriModel;
    protected $reviewModel;

    public function __construct()
    {
        $this->abstrakModel = new AbstrakModel();
        $this->userModel = new UserModel();
        $this->kategoriModel = new KategoriAbstrakModel();
        $this->reviewerKategoriModel = new ReviewerKategoriModel();
        $this->reviewModel = new ReviewModel();
    }

    public function index()
    {
        // Get all abstrak with user and category details
        $abstraks = $this->abstrakModel->getAbstrakWithDetails();
        
        // Get available reviewers for assignment
        $reviewers = $this->userModel->where('role', 'reviewer')
                                   ->where('status', 'aktif')
                                   ->findAll();

        // Get statistics
        $data = [
            'abstraks' => $abstraks,
            'reviewers' => $reviewers,
            'total_abstrak' => $this->abstrakModel->countAll(),
            'abstrak_pending' => $this->abstrakModel->where('status', 'menunggu')->countAllResults(),
            'abstrak_diterima' => $this->abstrakModel->where('status', 'diterima')->countAllResults(),
            'abstrak_ditolak' => $this->abstrakModel->where('status', 'ditolak')->countAllResults(),
            'abstrak_revisi' => $this->abstrakModel->where('status', 'revisi')->countAllResults(),
        ];

        return view('role/admin/abstrak/index', $data);
    }

    public function assign($id_abstrak)
    {
        $abstrak = $this->abstrakModel->find($id_abstrak);
        
        if (!$abstrak) {
            return redirect()->to('admin/abstrak')->with('error', 'Abstrak tidak ditemukan.');
        }

        $id_reviewer = $this->request->getPost('id_reviewer');
        $reviewer = $this->userModel->find($id_reviewer);
        
        if (!$reviewer || $reviewer['role'] != 'reviewer') {
            return redirect()->back()->with('error', 'Reviewer tidak valid.');
        }

        // Check if reviewer can handle this category
        $canReview = $this->reviewerKategoriModel
                         ->where('id_reviewer', $id_reviewer)
                         ->where('id_kategori', $abstrak['id_kategori'])
                         ->first();

        if (!$canReview) {
            return redirect()->back()->with('error', 'Reviewer tidak dapat menangani kategori ini.');
        }

        // Check if already assigned
        $existingAssignment = $this->reviewModel
                                  ->where('id_abstrak', $id_abstrak)
                                  ->where('id_reviewer', $id_reviewer)
                                  ->first();

        if ($existingAssignment) {
            return redirect()->back()->with('error', 'Reviewer sudah ditugaskan untuk abstrak ini.');
        }

        // Create review assignment
        $reviewData = [
            'id_abstrak' => $id_abstrak,
            'id_reviewer' => $id_reviewer,
            'keputusan' => 'pending',
            'komentar' => 'Menunggu review dari reviewer',
            'tanggal_review' => date('Y-m-d H:i:s')
        ];

        if ($this->reviewModel->save($reviewData)) {
            // Update abstrak status to 'sedang_direview'
            $this->abstrakModel->update($id_abstrak, ['status' => 'sedang_direview']);
            
            return redirect()->to('admin/abstrak')->with('success', 'Reviewer berhasil ditugaskan!');
        } else {
            return redirect()->back()->with('error', 'Gagal menugaskan reviewer.');
        }
    }

    public function detail($id_abstrak)
    {
        $abstrak = $this->abstrakModel->select('abstrak.*, users.nama_lengkap, users.email, kategori_abstrak.nama_kategori')
                                     ->join('users', 'users.id_user = abstrak.id_user')
                                     ->join('kategori_abstrak', 'kategori_abstrak.id_kategori = abstrak.id_kategori')
                                     ->where('abstrak.id_abstrak', $id_abstrak)
                                     ->first();

        if (!$abstrak) {
            return redirect()->to('admin/abstrak')->with('error', 'Abstrak tidak ditemukan.');
        }

        // Get reviews for this abstrak
        $reviews = $this->reviewModel->select('review.*, users.nama_lengkap as reviewer_name')
                                    ->join('users', 'users.id_user = review.id_reviewer')
                                    ->where('review.id_abstrak', $id_abstrak)
                                    ->orderBy('review.tanggal_review', 'DESC')
                                    ->findAll();

        $data = [
            'abstrak' => $abstrak,
            'reviews' => $reviews
        ];

        return view('role/admin/abstrak/detail', $data);
    }

    public function updateStatus()
    {
        $id_abstrak = $this->request->getPost('id_abstrak');
        $status = $this->request->getPost('status');
        $komentar = $this->request->getPost('komentar');

        $abstrak = $this->abstrakModel->find($id_abstrak);
        
        if (!$abstrak) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Abstrak tidak ditemukan.'
            ]);
        }

        // Validate status
        $validStatus = ['menunggu', 'sedang_direview', 'diterima', 'ditolak', 'revisi'];
        if (!in_array($status, $validStatus)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Status tidak valid.'
            ]);
        }

        // Update abstrak status
        if ($this->abstrakModel->update($id_abstrak, ['status' => $status])) {
            // If there's a comment, create an admin review entry
            if ($komentar) {
                $reviewData = [
                    'id_abstrak' => $id_abstrak,
                    'id_reviewer' => session('id_user'), // Admin as reviewer
                    'keputusan' => $status,
                    'komentar' => $komentar,
                    'tanggal_review' => date('Y-m-d H:i:s')
                ];
                $this->reviewModel->save($reviewData);
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Status abstrak berhasil diupdate!'
            ]);
        } else {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Gagal mengupdate status abstrak.'
            ]);
        }
    }

    public function delete($id_abstrak)
    {
        $abstrak = $this->abstrakModel->find($id_abstrak);
        
        if (!$abstrak) {
            return redirect()->to('admin/abstrak')->with('error', 'Abstrak tidak ditemukan.');
        }

        // Delete associated file if exists
        $filePath = WRITEPATH . 'uploads/abstrak/' . $abstrak['file_abstrak'];
        if (file_exists($filePath)) {
            unlink($filePath);
        }

        // Delete reviews first (foreign key constraint)
        $this->reviewModel->where('id_abstrak', $id_abstrak)->delete();

        // Delete abstrak
        if ($this->abstrakModel->delete($id_abstrak)) {
            return redirect()->to('admin/abstrak')->with('success', 'Abstrak berhasil dihapus!');
        } else {
            return redirect()->to('admin/abstrak')->with('error', 'Gagal menghapus abstrak.');
        }
    }

    public function downloadFile($id_abstrak)
    {
        $abstrak = $this->abstrakModel->find($id_abstrak);
        
        if (!$abstrak) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Abstrak tidak ditemukan.');
        }

        $filePath = WRITEPATH . 'uploads/abstrak/' . $abstrak['file_abstrak'];
        
        if (!file_exists($filePath)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('File tidak ditemukan.');
        }

        return $this->response->download($filePath, null);
    }
}