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

    public function detail($id_abstrak)
    {
        // Get abstrak with complete details including event information
        $abstrak = $this->abstrakModel
                       ->select('
                           abstrak.*, 
                           users.nama_lengkap, 
                           users.email, 
                           users.role as user_role,
                           users.no_hp,
                           users.institusi,
                           kategori_abstrak.nama_kategori,
                           events.title as event_title,
                           events.id as event_id
                       ')
                       ->join('users', 'users.id_user = abstrak.id_user')
                       ->join('kategori_abstrak', 'kategori_abstrak.id_kategori = abstrak.id_kategori')
                       ->join('events', 'events.id = abstrak.event_id', 'left')
                       ->where('abstrak.id_abstrak', $id_abstrak)
                       ->first();

        if (!$abstrak) {
            return redirect()->to('admin/abstrak')->with('error', 'Abstrak tidak ditemukan.');
        }

        // Get reviews for this abstrak with reviewer information
        $reviews = $this->reviewModel
                       ->select('
                           review.*, 
                           users.nama_lengkap as reviewer_name,
                           users.email as reviewer_email
                       ')
                       ->join('users', 'users.id_user = review.id_reviewer')
                       ->where('review.id_abstrak', $id_abstrak)
                       ->orderBy('review.tanggal_review', 'DESC')
                       ->findAll();

        // Get available reviewers for this category (if needed)
        $availableReviewers = $this->reviewerKategoriModel
                                  ->select('
                                      reviewer_kategori.*,
                                      users.nama_lengkap,
                                      users.email
                                  ')
                                  ->join('users', 'users.id_user = reviewer_kategori.id_reviewer')
                                  ->where('reviewer_kategori.id_kategori', $abstrak['id_kategori'])
                                  ->where('users.status', 'aktif')
                                  ->findAll();

        // Get review statistics
        $reviewStats = [
            'total' => count($reviews),
            'pending' => count(array_filter($reviews, fn($r) => $r['keputusan'] === 'pending')),
            'completed' => count(array_filter($reviews, fn($r) => in_array($r['keputusan'], ['diterima', 'ditolak', 'revisi'])))
        ];

        // Check if file exists
        $filePath = WRITEPATH . 'uploads/abstraks/' . $abstrak['file_abstrak'];
        $fileExists = file_exists($filePath);
        $fileSize = $fileExists ? filesize($filePath) : 0;

        $data = [
            'abstrak' => $abstrak,
            'reviews' => $reviews,
            'reviewStats' => $reviewStats,
            'availableReviewers' => $availableReviewers,
            'fileExists' => $fileExists,
            'fileSize' => $this->formatFileSize($fileSize),
            'breadcrumb' => [
                ['title' => 'Dashboard', 'url' => 'admin/dashboard'],
                ['title' => 'Manajemen Abstrak', 'url' => 'admin/abstrak'],
                ['title' => 'Detail Abstrak', 'url' => '']
            ]
        ];

        return view('role/admin/abstrak/detail', $data);
    }

    public function downloadFile($id_abstrak)
    {
        $abstrak = $this->abstrakModel->find($id_abstrak);
        
        if (!$abstrak) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Abstrak tidak ditemukan.');
        }

        $filePath = WRITEPATH . 'uploads/abstraks/' . $abstrak['file_abstrak'];
        
        if (!file_exists($filePath)) {
            log_message('error', 'File not found at: ' . $filePath);
            throw new \CodeIgniter\Exceptions\PageNotFoundException('File tidak ditemukan di server: ' . $filePath);
        }

        // Validate file is actually a PDF by checking its MIME type
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filePath);
        finfo_close($finfo);
        
        $allowedMimeTypes = ['application/pdf', 'application/x-pdf'];
        
        if (!in_array($mimeType, $allowedMimeTypes)) {
            // Additional check by file signature for PDF
            $fileContent = file_get_contents($filePath);
            if (substr($fileContent, 0, 4) !== '%PDF') {
                log_message('error', 'Invalid PDF file. MIME: ' . $mimeType . ', File: ' . $filePath);
                throw new \CodeIgniter\Exceptions\PageNotFoundException('File bukan PDF yang valid. MIME type: ' . $mimeType);
            }
        }

        // Set proper filename for download
        $fileName = 'Abstrak_' . $abstrak['id_abstrak'] . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $abstrak['judul']) . '.pdf';
        $fileName = substr($fileName, 0, 200) . '.pdf'; // Limit filename length

        // Log activity
        $this->logActivity(session('id_user'), "Mengunduh file abstrak: {$abstrak['judul']} (ID: {$abstrak['id_abstrak']})");

        // Force download with proper headers for PDF
        return $this->response
                    ->download($filePath, null)
                    ->setFileName($fileName)
                    ->setContentType('application/pdf')
                    ->setHeader('Content-Disposition', 'attachment; filename="' . $fileName . '"');
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
            
            // Log activity
            $this->logActivity(session('id_user'), "Menugaskan reviewer {$reviewer['nama_lengkap']} untuk abstrak: {$abstrak['judul']}");
            
            return redirect()->to('admin/abstrak/detail/' . $id_abstrak)->with('success', 'Reviewer berhasil ditugaskan!');
        } else {
            return redirect()->back()->with('error', 'Gagal menugaskan reviewer.');
        }
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

        // Begin transaction
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // Update abstrak status
            $updateData = ['status' => $status];
            
            // If status is being changed to 'revisi', increment revision counter
            if ($status === 'revisi') {
                $updateData['revisi_ke'] = $abstrak['revisi_ke'] + 1;
            }
            
            $this->abstrakModel->update($id_abstrak, $updateData);

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

            // Log activity
            $this->logActivity(session('id_user'), "Mengubah status abstrak '{$abstrak['judul']}' menjadi {$status}");

            $db->transComplete();

            if ($db->transStatus() === FALSE) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Terjadi kesalahan saat memperbarui status.'
                ]);
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Status abstrak berhasil diupdate!',
                'new_status' => $status
            ]);

        } catch (\Exception $e) {
            $db->transRollback();
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    public function delete($id_abstrak)
    {
        $abstrak = $this->abstrakModel->find($id_abstrak);
        
        if (!$abstrak) {
            return redirect()->to('admin/abstrak')->with('error', 'Abstrak tidak ditemukan.');
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // Delete associated file if exists
            $filePath = WRITEPATH . 'uploads/abstraks/' . $abstrak['file_abstrak'];
            if (file_exists($filePath)) {
                unlink($filePath);
                log_message('info', 'Deleted file: ' . $filePath);
            }

            // Delete reviews first (foreign key constraint)
            $this->reviewModel->where('id_abstrak', $id_abstrak)->delete();

            // Delete abstrak
            $this->abstrakModel->delete($id_abstrak);

            // Log activity
            $this->logActivity(session('id_user'), "Menghapus abstrak: {$abstrak['judul']} (ID: {$id_abstrak})");

            $db->transComplete();

            if ($db->transStatus() === FALSE) {
                return redirect()->to('admin/abstrak')->with('error', 'Gagal menghapus abstrak.');
            }

            return redirect()->to('admin/abstrak')->with('success', 'Abstrak berhasil dihapus!');

        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'Error deleting abstrak: ' . $e->getMessage());
            return redirect()->to('admin/abstrak')->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function export()
    {
        $abstraks = $this->abstrakModel->getAbstrakWithDetails();
        
        $filename = 'abstrak_' . date('Y-m-d_H-i-s') . '.csv';
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // Add BOM for UTF-8
        fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // CSV Headers
        fputcsv($output, [
            'ID',
            'Judul', 
            'Penulis',
            'Email',
            'Kategori',
            'Event',
            'Status',
            'Tanggal Upload',
            'Revisi Ke',
            'File'
        ]);
        
        // CSV Data
        foreach ($abstraks as $abstrak) {
            fputcsv($output, [
                $abstrak['id_abstrak'],
                $abstrak['judul'],
                $abstrak['nama_lengkap'],
                $abstrak['email'],
                $abstrak['nama_kategori'],
                $abstrak['event_title'] ?? '-',
                ucfirst(str_replace('_', ' ', $abstrak['status'])),
                date('d/m/Y H:i', strtotime($abstrak['tanggal_upload'])),
                $abstrak['revisi_ke'],
                $abstrak['file_abstrak']
            ]);
        }
        
        fclose($output);
    }

    public function getReviewersByCategory($id_kategori)
    {
        $reviewers = $this->reviewerKategoriModel
                         ->select('reviewer_kategori.*, users.nama_lengkap, users.email')
                         ->join('users', 'users.id_user = reviewer_kategori.id_reviewer')
                         ->where('reviewer_kategori.id_kategori', $id_kategori)
                         ->where('users.status', 'aktif')
                         ->findAll();

        return $this->response->setJSON($reviewers);
    }

    public function bulkUpdateStatus()
    {
        $ids = $this->request->getPost('abstrak_ids');
        $status = $this->request->getPost('status');
        $komentar = $this->request->getPost('komentar');

        if (empty($ids) || !is_array($ids)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Pilih minimal satu abstrak.'
            ]);
        }

        $validStatus = ['menunggu', 'sedang_direview', 'diterima', 'ditolak', 'revisi'];
        if (!in_array($status, $validStatus)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Status tidak valid.'
            ]);
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $updated = 0;
            foreach ($ids as $id) {
                $abstrak = $this->abstrakModel->find($id);
                if ($abstrak) {
                    $updateData = ['status' => $status];
                    
                    if ($status === 'revisi') {
                        $updateData['revisi_ke'] = $abstrak['revisi_ke'] + 1;
                    }
                    
                    $this->abstrakModel->update($id, $updateData);

                    // Add admin review if comment provided
                    if ($komentar) {
                        $this->reviewModel->save([
                            'id_abstrak' => $id,
                            'id_reviewer' => session('id_user'),
                            'keputusan' => $status,
                            'komentar' => $komentar,
                            'tanggal_review' => date('Y-m-d H:i:s')
                        ]);
                    }

                    $updated++;
                }
            }

            $this->logActivity(session('id_user'), "Bulk update status {$updated} abstrak menjadi {$status}");

            $db->transComplete();

            if ($db->transStatus() === FALSE) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Terjadi kesalahan saat memperbarui data.'
                ]);
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => "{$updated} abstrak berhasil diupdate!"
            ]);

        } catch (\Exception $e) {
            $db->transRollback();
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    public function statistics()
    {
        // Status distribution
        $statusStats = $this->abstrakModel
                           ->select('status, COUNT(*) as count')
                           ->groupBy('status')
                           ->findAll();

        // Monthly submission stats (last 12 months)
        $monthlyStats = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-{$i} months"));
            $monthName = date('M Y', strtotime($month . '-01'));
            
            // PostgreSQL compatible date filtering
            $startDate = $month . '-01';
            $endDate = $month . '-' . date('t', strtotime($startDate));
            
            $count = $this->abstrakModel
                         ->where('tanggal_upload >=', $startDate)
                         ->where('tanggal_upload <=', $endDate . ' 23:59:59')
                         ->countAllResults();
            
            $monthlyStats[] = [
                'month' => $monthName,
                'count' => $count
            ];
        }

        // Category distribution
        $categoryStats = $this->abstrakModel
                             ->select('kategori_abstrak.nama_kategori, COUNT(*) as count')
                             ->join('kategori_abstrak', 'kategori_abstrak.id_kategori = abstrak.id_kategori')
                             ->groupBy('abstrak.id_kategori, kategori_abstrak.nama_kategori')
                             ->orderBy('count', 'DESC')
                             ->findAll();

        return $this->response->setJSON([
            'status_distribution' => $statusStats,
            'monthly_submissions' => $monthlyStats,
            'category_distribution' => $categoryStats
        ]);
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

    private function formatFileSize($bytes)
    {
        if ($bytes == 0) return '0 B';
        
        $units = ['B', 'KB', 'MB', 'GB'];
        $factor = floor(log($bytes, 1024));
        
        return sprintf("%.1f %s", $bytes / pow(1024, $factor), $units[$factor]);
    }
}