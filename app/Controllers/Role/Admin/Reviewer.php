<?php

namespace App\Controllers\Role\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\KategoriAbstrakModel;
use App\Models\ReviewerKategoriModel;
use App\Models\ReviewModel;

class Reviewer extends BaseController
{
    protected $userModel;
    protected $kategoriModel;
    protected $reviewerKategoriModel;
    protected $reviewModel;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->kategoriModel = new KategoriAbstrakModel();
        $this->reviewerKategoriModel = new ReviewerKategoriModel();
        $this->reviewModel = new ReviewModel();
    }

    public function index()
    {
        // Get all reviewers with their categories
        $reviewers = $this->userModel->where('role', 'reviewer')->findAll();
        
        // Get reviewer categories
        foreach ($reviewers as &$reviewer) {
            $categories = $this->reviewerKategoriModel
                              ->select('reviewer_kategori.*, kategori_abstrak.nama_kategori')
                              ->join('kategori_abstrak', 'kategori_abstrak.id_kategori = reviewer_kategori.id_kategori')
                              ->where('id_reviewer', $reviewer['id_user'])
                              ->findAll();
            $reviewer['categories'] = $categories;
            
            // Get review statistics
            $reviewer['total_reviews'] = $this->reviewModel->where('id_reviewer', $reviewer['id_user'])->countAllResults();
            $reviewer['pending_reviews'] = $this->reviewModel->where('id_reviewer', $reviewer['id_user'])
                                                             ->where('keputusan', 'pending')
                                                             ->countAllResults();
        }

        // Get all categories for assignment
        $categories = $this->kategoriModel->findAll();

        $data = [
            'reviewers' => $reviewers,
            'categories' => $categories,
            'total_reviewers' => count($reviewers),
            'active_reviewers' => $this->userModel->where('role', 'reviewer')->where('status', 'aktif')->countAllResults(),
            'total_categories' => count($categories)
        ];

        return view('role/admin/reviewer/index', $data);
    }

    public function store()
    {
        $validation = \Config\Services::validation();
        
        $rules = [
            'nama_lengkap' => 'required|min_length[3]|max_length[100]',
            'email' => 'required|valid_email|is_unique[users.email]',
            'password' => 'required|min_length[6]',
            'categories' => 'required'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        // Create reviewer user
        $userData = [
            'nama_lengkap' => $this->request->getPost('nama_lengkap'),
            'email' => $this->request->getPost('email'),
            'password' => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
            'role' => 'reviewer',
            'status' => 'aktif',
            'email_verified_at' => date('Y-m-d H:i:s')
        ];

        if (!$this->userModel->save($userData)) {
            return redirect()->back()->withInput()->with('error', 'Gagal membuat reviewer.');
        }

        $reviewerId = $this->userModel->getInsertID();
        $categories = $this->request->getPost('categories');

        // Assign categories to reviewer
        foreach ($categories as $categoryId) {
            $this->reviewerKategoriModel->save([
                'id_reviewer' => $reviewerId,
                'id_kategori' => $categoryId
            ]);
        }

        return redirect()->to('admin/reviewer')->with('success', 'Reviewer berhasil ditambahkan!');
    }

    public function assignCategory()
    {
        $reviewerId = $this->request->getPost('reviewer_id');
        $categoryId = $this->request->getPost('category_id');

        // Check if already assigned
        $existing = $this->reviewerKategoriModel
                        ->where('id_reviewer', $reviewerId)
                        ->where('id_kategori', $categoryId)
                        ->first();

        if ($existing) {
            return redirect()->back()->with('error', 'Kategori sudah ditugaskan ke reviewer ini.');
        }

        if ($this->reviewerKategoriModel->save(['id_reviewer' => $reviewerId, 'id_kategori' => $categoryId])) {
            return redirect()->back()->with('success', 'Kategori berhasil ditugaskan!');
        } else {
            return redirect()->back()->with('error', 'Gagal menugaskan kategori.');
        }
    }

    public function removeCategory($id)
    {
        if ($this->reviewerKategoriModel->delete($id)) {
            return redirect()->back()->with('success', 'Kategori berhasil dihapus dari reviewer!');
        } else {
            return redirect()->back()->with('error', 'Gagal menghapus kategori.');
        }
    }

    public function toggleStatus($id)
    {
        $user = $this->userModel->find($id);
        
        if (!$user || $user['role'] !== 'reviewer') {
            return redirect()->back()->with('error', 'Reviewer tidak ditemukan.');
        }

        $newStatus = $user['status'] === 'aktif' ? 'nonaktif' : 'aktif';
        
        if ($this->userModel->update($id, ['status' => $newStatus])) {
            return redirect()->back()->with('success', 'Status reviewer berhasil diubah!');
        } else {
            return redirect()->back()->with('error', 'Gagal mengubah status reviewer.');
        }
    }

    public function detail($id)
    {
        $reviewer = $this->userModel->find($id);
        
        if (!$reviewer || $reviewer['role'] !== 'reviewer') {
            return redirect()->to('admin/reviewer')->with('error', 'Reviewer tidak ditemukan.');
        }

        // Get reviewer categories
        $categories = $this->reviewerKategoriModel
                          ->select('reviewer_kategori.*, kategori_abstrak.nama_kategori')
                          ->join('kategori_abstrak', 'kategori_abstrak.id_kategori = reviewer_kategori.id_kategori')
                          ->where('id_reviewer', $id)
                          ->findAll();

        // Get review history
        $reviews = $this->reviewModel
                       ->select('review.*, abstrak.judul, users.nama_lengkap as author_name')
                       ->join('abstrak', 'abstrak.id_abstrak = review.id_abstrak')
                       ->join('users', 'users.id_user = abstrak.id_user')
                       ->where('review.id_reviewer', $id)
                       ->orderBy('review.tanggal_review', 'DESC')
                       ->findAll();

        $data = [
            'reviewer' => $reviewer,
            'categories' => $categories,
            'reviews' => $reviews,
            'total_reviews' => count($reviews),
            'pending_reviews' => count(array_filter($reviews, fn($r) => $r['keputusan'] === 'pending')),
            'completed_reviews' => count(array_filter($reviews, fn($r) => in_array($r['keputusan'], ['diterima', 'ditolak'])))
        ];

        return view('role/admin/reviewer/detail', $data);
    }

    public function delete($id)
    {
        $reviewer = $this->userModel->find($id);
        
        if (!$reviewer || $reviewer['role'] !== 'reviewer') {
            return redirect()->to('admin/reviewer')->with('error', 'Reviewer tidak ditemukan.');
        }

        // Check if reviewer has pending reviews
        $pendingReviews = $this->reviewModel->where('id_reviewer', $id)
                                          ->where('keputusan', 'pending')
                                          ->countAllResults();

        if ($pendingReviews > 0) {
            return redirect()->back()->with('error', 'Tidak dapat menghapus reviewer yang masih memiliki review pending.');
        }

        // Delete reviewer categories first
        $this->reviewerKategoriModel->where('id_reviewer', $id)->delete();

        // Delete reviewer
        if ($this->userModel->delete($id)) {
            return redirect()->to('admin/reviewer')->with('success', 'Reviewer berhasil dihapus!');
        } else {
            return redirect()->back()->with('error', 'Gagal menghapus reviewer.');
        }
    }

    public function performance()
    {
        // Get reviewer performance statistics
        $reviewers = $this->userModel->where('role', 'reviewer')->findAll();
        
        $performance = [];
        foreach ($reviewers as $reviewer) {
            $totalReviews = $this->reviewModel->where('id_reviewer', $reviewer['id_user'])->countAllResults();
            $completedReviews = $this->reviewModel->where('id_reviewer', $reviewer['id_user'])
                                                 ->whereIn('keputusan', ['diterima', 'ditolak'])
                                                 ->countAllResults();
            $avgTime = $this->getAverageReviewTime($reviewer['id_user']);
            
            $performance[] = [
                'reviewer' => $reviewer,
                'total_reviews' => $totalReviews,
                'completed_reviews' => $completedReviews,
                'completion_rate' => $totalReviews > 0 ? round(($completedReviews / $totalReviews) * 100, 2) : 0,
                'avg_review_time' => $avgTime
            ];
        }

        return $this->response->setJSON($performance);
    }

    private function getAverageReviewTime($reviewerId)
    {
        $reviews = $this->reviewModel
                       ->select('review.*, abstrak.tanggal_upload')
                       ->join('abstrak', 'abstrak.id_abstrak = review.id_abstrak')
                       ->where('review.id_reviewer', $reviewerId)
                       ->whereIn('review.keputusan', ['diterima', 'ditolak'])
                       ->findAll();

        if (empty($reviews)) return 0;

        $totalDays = 0;
        foreach ($reviews as $review) {
            $uploadTime = strtotime($review['tanggal_upload']);
            $reviewTime = strtotime($review['tanggal_review']);
            $totalDays += ($reviewTime - $uploadTime) / (60 * 60 * 24); // Convert to days
        }

        return round($totalDays / count($reviews), 1);
    }
}