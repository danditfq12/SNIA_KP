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
        
        // Get reviewer categories and performance stats
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

        // Begin transaction
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // Create reviewer user
            $userData = [
                'nama_lengkap' => $this->request->getPost('nama_lengkap'),
                'email' => $this->request->getPost('email'),
                'password' => password_hash($this->request->getPost('password'), PASSWORD_DEFAULT),
                'role' => 'reviewer',
                'status' => 'aktif',
                'email_verified_at' => date('Y-m-d H:i:s'),
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            if (!$this->userModel->save($userData)) {
                throw new \Exception('Failed to create reviewer user: ' . implode(', ', $this->userModel->errors()));
            }

            $reviewerId = $this->userModel->getInsertID();
            $categories = $this->request->getPost('categories');

            // Assign categories to reviewer
            if (!empty($categories)) {
                foreach ($categories as $categoryId) {
                    $categoryData = [
                        'id_reviewer' => $reviewerId,
                        'id_kategori' => $categoryId
                    ];
                    
                    if (!$this->reviewerKategoriModel->save($categoryData)) {
                        throw new \Exception('Failed to assign category: ' . implode(', ', $this->reviewerKategoriModel->errors()));
                    }
                }
            }

            // Log activity
            $this->logActivity(session('id_user'), "Created new reviewer: {$userData['nama_lengkap']} with " . count($categories) . " categories");

            $db->transComplete();

            if ($db->transStatus() === FALSE) {
                throw new \Exception('Transaction failed');
            }

            return redirect()->to('admin/reviewer')->with('success', 'Reviewer berhasil ditambahkan!');

        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'Reviewer creation error: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function assignCategory()
    {
        $validation = \Config\Services::validation();
        
        $rules = [
            'reviewer_id' => 'required|integer',
            'category_id' => 'required|integer'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->with('errors', $validation->getErrors());
        }

        $reviewerId = $this->request->getPost('reviewer_id');
        $categoryId = $this->request->getPost('category_id');

        // Validate reviewer exists and has reviewer role
        $reviewer = $this->userModel->find($reviewerId);
        if (!$reviewer || $reviewer['role'] !== 'reviewer') {
            return redirect()->back()->with('error', 'Reviewer tidak valid.');
        }

        // Validate category exists
        $category = $this->kategoriModel->find($categoryId);
        if (!$category) {
            return redirect()->back()->with('error', 'Kategori tidak valid.');
        }

        // Check if already assigned
        $existing = $this->reviewerKategoriModel
                        ->where('id_reviewer', $reviewerId)
                        ->where('id_kategori', $categoryId)
                        ->first();

        if ($existing) {
            return redirect()->back()->with('error', 'Kategori sudah ditugaskan ke reviewer ini.');
        }

        try {
            $assignData = [
                'id_reviewer' => $reviewerId,
                'id_kategori' => $categoryId
            ];

            if ($this->reviewerKategoriModel->save($assignData)) {
                // Log activity
                $this->logActivity(session('id_user'), "Assigned category '{$category['nama_kategori']}' to reviewer '{$reviewer['nama_lengkap']}'");
                
                return redirect()->back()->with('success', 'Kategori berhasil ditugaskan ke reviewer!');
            } else {
                return redirect()->back()->with('error', 'Gagal menugaskan kategori: ' . implode(', ', $this->reviewerKategoriModel->errors()));
            }
        } catch (\Exception $e) {
            log_message('error', 'Category assignment error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function removeCategory($id)
    {
        $reviewerKategori = $this->reviewerKategoriModel->find($id);
        
        if (!$reviewerKategori) {
            return redirect()->back()->with('error', 'Assignment kategori tidak ditemukan.');
        }

        // Get reviewer and category info for logging
        $reviewer = $this->userModel->find($reviewerKategori['id_reviewer']);
        $category = $this->kategoriModel->find($reviewerKategori['id_kategori']);

        try {
            if ($this->reviewerKategoriModel->delete($id)) {
                // Log activity
                if ($reviewer && $category) {
                    $this->logActivity(session('id_user'), "Removed category '{$category['nama_kategori']}' from reviewer '{$reviewer['nama_lengkap']}'");
                }
                
                return redirect()->back()->with('success', 'Kategori berhasil dihapus dari reviewer!');
            } else {
                return redirect()->back()->with('error', 'Gagal menghapus assignment kategori.');
            }
        } catch (\Exception $e) {
            log_message('error', 'Category removal error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function toggleStatus($id)
    {
        $user = $this->userModel->find($id);
        
        if (!$user || $user['role'] !== 'reviewer') {
            return redirect()->back()->with('error', 'Reviewer tidak ditemukan.');
        }

        $newStatus = $user['status'] === 'aktif' ? 'nonaktif' : 'aktif';
        
        try {
            $updateData = [
                'status' => $newStatus,
                'updated_at' => date('Y-m-d H:i:s')
            ];

            if ($this->userModel->update($id, $updateData)) {
                // Log activity
                $this->logActivity(session('id_user'), "Changed reviewer '{$user['nama_lengkap']}' status to {$newStatus}");
                
                $message = $newStatus === 'aktif' ? 'Reviewer berhasil diaktifkan!' : 'Reviewer berhasil dinonaktifkan!';
                return redirect()->back()->with('success', $message);
            } else {
                return redirect()->back()->with('error', 'Gagal mengubah status reviewer: ' . implode(', ', $this->userModel->errors()));
            }
        } catch (\Exception $e) {
            log_message('error', 'Reviewer status toggle error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
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

        // Get review history with abstrak details
        $reviews = $this->reviewModel
                       ->select('
                           review.*, 
                           abstrak.judul, 
                           abstrak.status as abstrak_status,
                           users.nama_lengkap as author_name,
                           kategori_abstrak.nama_kategori
                       ')
                       ->join('abstrak', 'abstrak.id_abstrak = review.id_abstrak')
                       ->join('users', 'users.id_user = abstrak.id_user')
                       ->join('kategori_abstrak', 'kategori_abstrak.id_kategori = abstrak.id_kategori')
                       ->where('review.id_reviewer', $id)
                       ->orderBy('review.tanggal_review', 'DESC')
                       ->findAll();

        // Calculate performance statistics
        $totalReviews = count($reviews);
        $pendingReviews = count(array_filter($reviews, fn($r) => $r['keputusan'] === 'pending'));
        $completedReviews = $totalReviews - $pendingReviews;
        $acceptedReviews = count(array_filter($reviews, fn($r) => $r['keputusan'] === 'diterima'));
        $rejectedReviews = count(array_filter($reviews, fn($r) => $r['keputusan'] === 'ditolak'));
        $revisionReviews = count(array_filter($reviews, fn($r) => $r['keputusan'] === 'revisi'));

        // Average review time calculation
        $avgReviewTime = $this->getAverageReviewTime($id);

        // All categories for assignment
        $allCategories = $this->kategoriModel->findAll();
        $assignedCategoryIds = array_column($categories, 'id_kategori');
        $availableCategories = array_filter($allCategories, fn($cat) => !in_array($cat['id_kategori'], $assignedCategoryIds));

        $data = [
            'reviewer' => $reviewer,
            'categories' => $categories,
            'available_categories' => $availableCategories,
            'reviews' => $reviews,
            'performance' => [
                'total_reviews' => $totalReviews,
                'pending_reviews' => $pendingReviews,
                'completed_reviews' => $completedReviews,
                'accepted_reviews' => $acceptedReviews,
                'rejected_reviews' => $rejectedReviews,
                'revision_reviews' => $revisionReviews,
                'completion_rate' => $totalReviews > 0 ? round(($completedReviews / $totalReviews) * 100, 1) : 0,
                'acceptance_rate' => $completedReviews > 0 ? round(($acceptedReviews / $completedReviews) * 100, 1) : 0,
                'avg_review_time' => $avgReviewTime
            ]
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

        // Begin transaction
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // Delete reviewer categories first (foreign key constraint)
            $this->reviewerKategoriModel->where('id_reviewer', $id)->delete();

            // Delete reviewer user
            if (!$this->userModel->delete($id)) {
                throw new \Exception('Failed to delete reviewer');
            }

            // Log activity
            $this->logActivity(session('id_user'), "Deleted reviewer: {$reviewer['nama_lengkap']} (ID: {$id})");

            $db->transComplete();

            if ($db->transStatus() === FALSE) {
                throw new \Exception('Transaction failed');
            }

            return redirect()->to('admin/reviewer')->with('success', 'Reviewer berhasil dihapus!');

        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'Reviewer deletion error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
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
                                                 ->whereIn('keputusan', ['diterima', 'ditolak', 'revisi'])
                                                 ->countAllResults();
            $avgTime = $this->getAverageReviewTime($reviewer['id_user']);
            
            // Get assigned categories count
            $assignedCategories = $this->reviewerKategoriModel->where('id_reviewer', $reviewer['id_user'])->countAllResults();
            
            $performance[] = [
                'reviewer' => $reviewer,
                'total_reviews' => $totalReviews,
                'completed_reviews' => $completedReviews,
                'pending_reviews' => $totalReviews - $completedReviews,
                'completion_rate' => $totalReviews > 0 ? round(($completedReviews / $totalReviews) * 100, 2) : 0,
                'avg_review_time' => $avgTime,
                'assigned_categories' => $assignedCategories
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
                       ->whereIn('review.keputusan', ['diterima', 'ditolak', 'revisi'])
                       ->findAll();

        if (empty($reviews)) return 0;

        $totalDays = 0;
        $validReviews = 0;

        foreach ($reviews as $review) {
            if ($review['tanggal_upload'] && $review['tanggal_review']) {
                $uploadTime = strtotime($review['tanggal_upload']);
                $reviewTime = strtotime($review['tanggal_review']);
                
                if ($reviewTime > $uploadTime) {
                    $totalDays += ($reviewTime - $uploadTime) / (60 * 60 * 24); // Convert to days
                    $validReviews++;
                }
            }
        }

        return $validReviews > 0 ? round($totalDays / $validReviews, 1) : 0;
    }

    private function logActivity($userId, $activity)
    {
        $db = \Config\Database::connect();
        try {
            $db->table('log_aktivitas')->insert([
                'id_user' => $userId,
                'aktivitas' => $activity,
                'waktu' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            // Silent fail for logging
            log_message('error', 'Failed to log activity: ' . $e->getMessage());
        }
    }
}