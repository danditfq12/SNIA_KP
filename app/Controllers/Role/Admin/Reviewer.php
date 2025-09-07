<?php

namespace App\Controllers\Role\Admin;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\KategoriAbstrakModel;
use App\Models\ReviewerKategoriModel;
use App\Models\ReviewModel;
use App\Models\AbstrakModel;

class Reviewer extends BaseController
{
    protected $userModel;
    protected $kategoriModel;
    protected $reviewerKategoriModel;
    protected $reviewModel;
    protected $abstrakModel;
    protected $db;

    public function __construct()
    {
        $this->userModel = new UserModel();
        $this->kategoriModel = new KategoriAbstrakModel();
        $this->reviewerKategoriModel = new ReviewerKategoriModel();
        $this->reviewModel = new ReviewModel();
        $this->abstrakModel = new AbstrakModel();
        $this->db = \Config\Database::connect();
    }

    /**
     * Main index page with reviewers list
     */
    public function index()
    {
        try {
            // Get all reviewers with their categories and performance stats
            $reviewers = $this->getReviewersWithDetails();
            
            // Get all categories for filter and assignment
            $categories = $this->kategoriModel->findAll();
            
            // Calculate statistics
            $stats = $this->getReviewerStatistics();

            $data = [
                'reviewers' => $reviewers,
                'categories' => $categories,
                'total_reviewers' => $stats['total_reviewers'],
                'active_reviewers' => $stats['active_reviewers'],
                'total_categories' => count($categories)
            ];

            return view('role/admin/reviewer/index', $data);

        } catch (\Exception $e) {
            log_message('error', 'Reviewer index error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memuat data reviewer.');
        }
    }

    /**
     * Get reviewers with their categories and performance data
     */
    private function getReviewersWithDetails()
    {
        $reviewers = $this->userModel->where('role', 'reviewer')->findAll();
        
        foreach ($reviewers as &$reviewer) {
            // Get assigned categories
            $reviewer['categories'] = $this->db->table('reviewer_kategori')
                ->select('reviewer_kategori.*, kategori_abstrak.nama_kategori')
                ->join('kategori_abstrak', 'kategori_abstrak.id_kategori = reviewer_kategori.id_kategori')
                ->where('reviewer_kategori.id_reviewer', $reviewer['id_user'])
                ->get()->getResultArray();

            // Get performance statistics
            $performance = $this->getReviewerPerformance($reviewer['id_user']);
            $reviewer = array_merge($reviewer, $performance);
        }

        return $reviewers;
    }

    /**
     * Get reviewer performance statistics
     */
    private function getReviewerPerformance($reviewerId)
    {
        $totalReviews = $this->reviewModel->where('id_reviewer', $reviewerId)->countAllResults();
        $pendingReviews = $this->reviewModel
            ->where('id_reviewer', $reviewerId)
            ->where('keputusan', 'pending')
            ->countAllResults();
        
        $completedReviews = $totalReviews - $pendingReviews;
        $acceptedReviews = $this->reviewModel
            ->where('id_reviewer', $reviewerId)
            ->where('keputusan', 'diterima')
            ->countAllResults();
        $rejectedReviews = $this->reviewModel
            ->where('id_reviewer', $reviewerId)
            ->where('keputusan', 'ditolak')
            ->countAllResults();
        $revisionReviews = $this->reviewModel
            ->where('id_reviewer', $reviewerId)
            ->where('keputusan', 'revisi')
            ->countAllResults();

        // Calculate average review time
        $avgReviewTime = $this->calculateAverageReviewTime($reviewerId);

        return [
            'total_reviews' => $totalReviews,
            'pending_reviews' => $pendingReviews,
            'completed_reviews' => $completedReviews,
            'accepted_reviews' => $acceptedReviews,
            'rejected_reviews' => $rejectedReviews,
            'revision_reviews' => $revisionReviews,
            'completion_rate' => $totalReviews > 0 ? round(($completedReviews / $totalReviews) * 100) : 0,
            'acceptance_rate' => $completedReviews > 0 ? round(($acceptedReviews / $completedReviews) * 100) : 0,
            'avg_review_time' => $avgReviewTime
        ];
    }

    /**
     * Calculate average review time in days
     */
    private function calculateAverageReviewTime($reviewerId)
    {
        $reviews = $this->db->table('review')
            ->select('review.tanggal_review, abstrak.tanggal_upload')
            ->join('abstrak', 'abstrak.id_abstrak = review.id_abstrak')
            ->where('review.id_reviewer', $reviewerId)
            ->where('review.keputusan !=', 'pending')
            ->get()->getResultArray();

        if (empty($reviews)) {
            return 0;
        }

        $totalDays = 0;
        $validReviews = 0;

        foreach ($reviews as $review) {
            $uploadTime = strtotime($review['tanggal_upload']);
            $reviewTime = strtotime($review['tanggal_review']);
            
            if ($reviewTime > $uploadTime) {
                $days = ceil(($reviewTime - $uploadTime) / (60 * 60 * 24));
                $totalDays += $days;
                $validReviews++;
            }
        }

        return $validReviews > 0 ? round($totalDays / $validReviews, 1) : 0;
    }

    /**
     * Get overall reviewer statistics
     */
    private function getReviewerStatistics()
    {
        $totalReviewers = $this->userModel->where('role', 'reviewer')->countAllResults();
        $activeReviewers = $this->userModel
            ->where('role', 'reviewer')
            ->where('status', 'aktif')
            ->countAllResults();

        return [
            'total_reviewers' => $totalReviewers,
            'active_reviewers' => $activeReviewers
        ];
    }

    /**
     * Store new reviewer
     */
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

        $categories = $this->request->getPost('categories');
        if (empty($categories) || !is_array($categories)) {
            return redirect()->back()->withInput()->with('error', 'Pilih setidaknya satu kategori review.');
        }

        $this->db->transStart();

        try {
            // Create user
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
                throw new \Exception('Gagal membuat user reviewer: ' . implode(', ', $this->userModel->errors()));
            }

            $reviewerId = $this->userModel->getInsertID();

            // Assign categories
            foreach ($categories as $categoryId) {
                $categoryData = [
                    'id_reviewer' => $reviewerId,
                    'id_kategori' => $categoryId
                ];
                
                if (!$this->reviewerKategoriModel->save($categoryData)) {
                    throw new \Exception('Gagal assign kategori: ' . implode(', ', $this->reviewerKategoriModel->errors()));
                }
            }

            // Log activity
            $this->logActivity(session('id_user'), "Created new reviewer: {$userData['nama_lengkap']} (ID: {$reviewerId})");

            $this->db->transComplete();

            if ($this->db->transStatus() === FALSE) {
                throw new \Exception('Transaction failed');
            }

            return redirect()->to('admin/reviewer')->with('success', 'Reviewer berhasil ditambahkan!');

        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Reviewer creation error: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Toggle reviewer status (active/inactive)
     */
    public function toggleStatus($id)
    {
        $reviewer = $this->userModel->find($id);
        
        if (!$reviewer || $reviewer['role'] !== 'reviewer') {
            return redirect()->back()->with('error', 'Reviewer tidak ditemukan.');
        }

        $newStatus = $reviewer['status'] === 'aktif' ? 'nonaktif' : 'aktif';

        try {
            if ($this->userModel->update($id, ['status' => $newStatus])) {
                // Log activity
                $this->logActivity(session('id_user'), "Changed status for reviewer '{$reviewer['nama_lengkap']}' to {$newStatus}");
                
                $message = $newStatus === 'aktif' ? 'Reviewer berhasil diaktifkan!' : 'Reviewer berhasil dinonaktifkan!';
                return redirect()->back()->with('success', $message);
            }
            
            return redirect()->back()->with('error', 'Gagal mengubah status reviewer.');

        } catch (\Exception $e) {
            log_message('error', 'Toggle reviewer status error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Assign category to reviewer
     */
    public function assignCategory()
    {
        $reviewerId = $this->request->getPost('reviewer_id');
        $categoryId = $this->request->getPost('category_id');

        if (!$reviewerId || !$categoryId) {
            return redirect()->back()->with('error', 'Data tidak lengkap.');
        }

        // Validate reviewer exists
        $reviewer = $this->userModel->find($reviewerId);
        if (!$reviewer || $reviewer['role'] !== 'reviewer') {
            return redirect()->back()->with('error', 'Reviewer tidak ditemukan.');
        }

        // Validate category exists
        $category = $this->kategoriModel->find($categoryId);
        if (!$category) {
            return redirect()->back()->with('error', 'Kategori tidak ditemukan.');
        }

        // Check if already assigned
        $existing = $this->reviewerKategoriModel
            ->where('id_reviewer', $reviewerId)
            ->where('id_kategori', $categoryId)
            ->first();

        if ($existing) {
            return redirect()->back()->with('error', 'Kategori sudah di-assign ke reviewer ini.');
        }

        try {
            $data = [
                'id_reviewer' => $reviewerId,
                'id_kategori' => $categoryId
            ];

            if ($this->reviewerKategoriModel->save($data)) {
                // Log activity
                $this->logActivity(session('id_user'), "Assigned category '{$category['nama_kategori']}' to reviewer '{$reviewer['nama_lengkap']}'");
                
                return redirect()->back()->with('success', 'Kategori berhasil di-assign!');
            }
            
            return redirect()->back()->with('error', 'Gagal assign kategori.');

        } catch (\Exception $e) {
            log_message('error', 'Assign category error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Remove category assignment from reviewer
     */
    public function removeCategory($assignmentId)
    {
        if (!$assignmentId) {
            return redirect()->back()->with('error', 'ID assignment tidak valid.');
        }

        $assignment = $this->reviewerKategoriModel->find($assignmentId);
        
        if (!$assignment) {
            return redirect()->back()->with('error', 'Assignment kategori tidak ditemukan.');
        }

        try {
            // Get details for logging
            $reviewer = $this->userModel->find($assignment['id_reviewer']);
            $category = $this->kategoriModel->find($assignment['id_kategori']);

            if ($this->reviewerKategoriModel->delete($assignmentId)) {
                // Log activity
                $reviewerName = $reviewer ? $reviewer['nama_lengkap'] : 'Unknown Reviewer';
                $categoryName = $category ? $category['nama_kategori'] : 'Unknown Category';
                $this->logActivity(session('id_user'), "Removed category '{$categoryName}' from reviewer '{$reviewerName}'");
                
                return redirect()->back()->with('success', 'Assignment kategori berhasil dihapus!');
            }
            
            return redirect()->back()->with('error', 'Gagal menghapus assignment kategori.');

        } catch (\Exception $e) {
            log_message('error', 'Remove category assignment error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    /**
     * Delete reviewer
     */
    public function delete($id)
    {
        $reviewer = $this->userModel->find($id);
        
        if (!$reviewer || $reviewer['role'] !== 'reviewer') {
            return redirect()->back()->with('error', 'Reviewer tidak ditemukan.');
        }

        // Check if reviewer has pending reviews
        $pendingReviews = $this->reviewModel
            ->where('id_reviewer', $id)
            ->where('keputusan', 'pending')
            ->countAllResults();

        if ($pendingReviews > 0) {
            return redirect()->back()->with('error', "Tidak dapat menghapus reviewer yang memiliki {$pendingReviews} review pending.");
        }

        $this->db->transStart();

        try {
            // Delete category assignments first
            $this->reviewerKategoriModel->where('id_reviewer', $id)->delete();

            // Delete reviewer (reviews will be kept for audit purposes)
            if (!$this->userModel->delete($id)) {
                throw new \Exception('Failed to delete reviewer');
            }

            // Log activity
            $this->logActivity(session('id_user'), "Deleted reviewer: {$reviewer['nama_lengkap']} (ID: {$id})");

            $this->db->transComplete();

            if ($this->db->transStatus() === FALSE) {
                throw new \Exception('Transaction failed');
            }

            return redirect()->to('admin/reviewer')->with('success', 'Reviewer berhasil dihapus!');

        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Delete reviewer error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error menghapus reviewer: ' . $e->getMessage());
        }
    }

    /**
     * Reviewer detail page
     */
    public function detail($id)
    {
        $reviewer = $this->userModel->find($id);
        
        if (!$reviewer || $reviewer['role'] !== 'reviewer') {
            return redirect()->to('admin/reviewer')->with('error', 'Reviewer tidak ditemukan.');
        }

        try {
            // Get assigned categories
            $categories = $this->db->table('reviewer_kategori')
                ->select('reviewer_kategori.*, kategori_abstrak.nama_kategori')
                ->join('kategori_abstrak', 'kategori_abstrak.id_kategori = reviewer_kategori.id_kategori')
                ->where('reviewer_kategori.id_reviewer', $id)
                ->get()->getResultArray();

            // Get available categories for assignment (not yet assigned)
            $assignedCategoryIds = array_column($categories, 'id_kategori');
            $availableCategories = $this->kategoriModel;
            if (!empty($assignedCategoryIds)) {
                $availableCategories = $availableCategories->whereNotIn('id_kategori', $assignedCategoryIds);
            }
            $availableCategories = $availableCategories->findAll();

            // Get detailed performance statistics
            $performance = $this->getDetailedPerformanceStats($id);

            // Get review history with abstract details
            $reviews = $this->getReviewHistoryWithDetails($id);

            $data = [
                'reviewer' => $reviewer,
                'categories' => $categories,
                'available_categories' => $availableCategories,
                'performance' => $performance,
                'reviews' => $reviews
            ];

            return view('role/admin/reviewer/detail', $data);

        } catch (\Exception $e) {
            log_message('error', 'Reviewer detail error: ' . $e->getMessage());
            return redirect()->to('admin/reviewer')->with('error', 'Terjadi kesalahan saat memuat detail reviewer.');
        }
    }

    /**
     * Get detailed performance statistics for detail page
     */
    private function getDetailedPerformanceStats($reviewerId)
    {
        $performance = $this->getReviewerPerformance($reviewerId);
        
        // Add more detailed stats
        $monthlyStats = $this->getMonthlyReviewStats($reviewerId);
        $categoryStats = $this->getCategoryReviewStats($reviewerId);
        
        return array_merge($performance, [
            'monthly_stats' => $monthlyStats,
            'category_stats' => $categoryStats
        ]);
    }

    /**
     * Get monthly review statistics
     */
    private function getMonthlyReviewStats($reviewerId)
    {
        $stats = [];
        
        for ($i = 5; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-$i months"));
            $monthName = date('M Y', strtotime($month . '-01'));
            
            $startDate = $month . '-01';
            $endDate = $month . '-' . date('t', strtotime($startDate));
            
            $count = $this->reviewModel
                ->where('id_reviewer', $reviewerId)
                ->where('tanggal_review >=', $startDate)
                ->where('tanggal_review <=', $endDate . ' 23:59:59')
                ->countAllResults();
            
            $stats[] = [
                'month' => $monthName,
                'count' => $count
            ];
        }
        
        return $stats;
    }

    /**
     * Get review statistics by category
     */
    private function getCategoryReviewStats($reviewerId)
    {
        return $this->db->table('review')
            ->select('kategori_abstrak.nama_kategori, COUNT(*) as total_reviews')
            ->join('abstrak', 'abstrak.id_abstrak = review.id_abstrak')
            ->join('kategori_abstrak', 'kategori_abstrak.id_kategori = abstrak.id_kategori')
            ->where('review.id_reviewer', $reviewerId)
            ->groupBy('abstrak.id_kategori, kategori_abstrak.nama_kategori')
            ->get()->getResultArray();
    }

    /**
     * Get review history with abstract and author details
     */
    private function getReviewHistoryWithDetails($reviewerId)
    {
        return $this->db->table('review')
            ->select('
                review.*,
                abstrak.judul,
                abstrak.tanggal_upload,
                users.nama_lengkap as author_name,
                kategori_abstrak.nama_kategori
            ')
            ->join('abstrak', 'abstrak.id_abstrak = review.id_abstrak')
            ->join('users', 'users.id_user = abstrak.id_user')
            ->join('kategori_abstrak', 'kategori_abstrak.id_kategori = abstrak.id_kategori')
            ->where('review.id_reviewer', $reviewerId)
            ->orderBy('review.tanggal_review', 'DESC')
            ->get()->getResultArray();
    }

    /**
     * Export reviewers data
     */
    public function export()
    {
        try {
            $reviewers = $this->getReviewersWithDetails();
            
            $filename = 'reviewers_' . date('Y-m-d_H-i-s') . '.csv';
            
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            $output = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // CSV Headers
            fputcsv($output, [
                'ID', 'Nama Lengkap', 'Email', 'Status', 'Total Review', 
                'Pending Review', 'Completion Rate', 'Kategori'
            ]);
            
            // CSV Data
            foreach ($reviewers as $reviewer) {
                $categories = array_column($reviewer['categories'], 'nama_kategori');
                
                fputcsv($output, [
                    $reviewer['id_user'],
                    $reviewer['nama_lengkap'],
                    $reviewer['email'],
                    ucfirst($reviewer['status']),
                    $reviewer['total_reviews'],
                    $reviewer['pending_reviews'],
                    $reviewer['completion_rate'] . '%',
                    implode(', ', $categories)
                ]);
            }
            
            fclose($output);
            
            // Log activity
            $this->logActivity(session('id_user'), "Exported reviewers data (" . count($reviewers) . " records)");
            
            exit;

        } catch (\Exception $e) {
            log_message('error', 'Export reviewers error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal export data reviewer.');
        }
    }

    /**
     * Get reviewer statistics for AJAX
     */
    public function getStatistics()
    {
        try {
            $stats = $this->getReviewerStatistics();
            
            // Get monthly review trends
            $monthlyTrends = [];
            for ($i = 5; $i >= 0; $i--) {
                $month = date('Y-m', strtotime("-$i months"));
                $monthName = date('M Y', strtotime($month . '-01'));
                
                $startDate = $month . '-01';
                $endDate = $month . '-' . date('t', strtotime($startDate));
                
                $count = $this->reviewModel
                    ->where('tanggal_review >=', $startDate)
                    ->where('tanggal_review <=', $endDate . ' 23:59:59')
                    ->countAllResults();
                
                $monthlyTrends[] = [
                    'month' => $monthName,
                    'reviews' => $count
                ];
            }
            
            // Get top performers
            $topPerformers = $this->getTopPerformers();
            
            return $this->response->setJSON([
                'success' => true,
                'stats' => $stats,
                'monthly_trends' => $monthlyTrends,
                'top_performers' => $topPerformers
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Get reviewer statistics error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Gagal memuat statistik reviewer'
            ]);
        }
    }

    /**
     * Get top performing reviewers
     */
    private function getTopPerformers($limit = 5)
    {
        $reviewers = $this->userModel->where('role', 'reviewer')->findAll();
        $performers = [];
        
        foreach ($reviewers as $reviewer) {
            $performance = $this->getReviewerPerformance($reviewer['id_user']);
            $performers[] = [
                'name' => $reviewer['nama_lengkap'],
                'total_reviews' => $performance['total_reviews'],
                'completion_rate' => $performance['completion_rate']
            ];
        }
        
        // Sort by completion rate and total reviews
        usort($performers, function($a, $b) {
            if ($a['completion_rate'] == $b['completion_rate']) {
                return $b['total_reviews'] - $a['total_reviews'];
            }
            return $b['completion_rate'] - $a['completion_rate'];
        });
        
        return array_slice($performers, 0, $limit);
    }

    /**
     * Log activity helper
     */
    private function logActivity($userId, $activity)
    {
        try {
            $this->db->table('log_aktivitas')->insert([
                'id_user' => $userId,
                'aktivitas' => $activity,
                'waktu' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Failed to log activity: ' . $e->getMessage());
        }
    }
}