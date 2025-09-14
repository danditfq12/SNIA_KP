<?php

namespace App\Controllers\Role\Reviewer;

use App\Controllers\BaseController;
use App\Models\ReviewModel;
use App\Models\AbstrakModel;
use App\Models\NotificationModel;
use App\Models\LogAktivitasModel;

class Review extends BaseController
{
    protected $reviewModel;
    protected $abstrakModel;
    protected $notificationModel;
    protected $logModel;

    public function __construct()
    {
        $this->reviewModel = new ReviewModel();
        $this->abstrakModel = new AbstrakModel();
        $this->notificationModel = new NotificationModel();
        $this->logModel = new LogAktivitasModel();
    }

    public function store($abstrakId)
    {
        // Basic validation
        if (!$abstrakId || !is_numeric($abstrakId)) {
            return redirect()->back()->with('error', 'ID abstrak tidak valid.');
        }

        $userId = session('id_user');
        if (!$userId) {
            return redirect()->to('auth/login')->with('error', 'Session expired. Silakan login kembali.');
        }

        // Log semua data input untuk debug
        log_message('debug', 'Review submission started - User: ' . $userId . ', Abstrak: ' . $abstrakId);

        // Form validation
        $validation = service('validation');
        $validation->setRules([
            'keputusan' => 'required|in_list[diterima,ditolak,revisi]',
            'komentar' => 'required|min_length[10]|max_length[1000]'
        ], [
            'keputusan' => [
                'required' => 'Keputusan review harus dipilih',
                'in_list' => 'Keputusan review tidak valid'
            ],
            'komentar' => [
                'required' => 'Komentar review wajib diisi',
                'min_length' => 'Komentar minimal 10 karakter',
                'max_length' => 'Komentar maksimal 1000 karakter'
            ]
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            log_message('debug', 'Validation failed: ' . json_encode($validation->getErrors()));
            return redirect()->back()->withInput()->with('error', 'Data tidak valid: ' . implode(', ', $validation->getErrors()));
        }

        $keputusan = trim($this->request->getPost('keputusan'));
        $komentar = trim($this->request->getPost('komentar'));

        // Log form data
        log_message('debug', 'Form data: ' . json_encode([
            'keputusan' => $keputusan,
            'komentar_length' => strlen($komentar),
            'komentar_preview' => substr($komentar, 0, 50) . '...'
        ]));

        // Check if abstrak exists
        $abstrak = $this->abstrakModel->find($abstrakId);
        if (!$abstrak) {
            log_message('warning', 'Abstrak not found: ' . $abstrakId);
            return redirect()->to('reviewer/abstrak')->with('error', 'Abstrak tidak ditemukan.');
        }

        // Check if this reviewer is assigned to this abstract
        $existingReview = $this->reviewModel->where([
            'id_abstrak' => $abstrakId,
            'id_reviewer' => $userId
        ])->first();

        log_message('debug', 'Existing review check: ' . json_encode($existingReview));

        if (!$existingReview) {
            log_message('warning', 'Reviewer not assigned - User: ' . $userId . ', Abstrak: ' . $abstrakId);
            return redirect()->back()->with('error', 'Anda tidak ditugaskan untuk mereview abstrak ini. Silakan hubungi admin.');
        }

        // Check if already reviewed
        $currentKeputusan = $existingReview['keputusan'] ?? '';
        if (!empty($currentKeputusan) && $currentKeputusan !== 'pending') {
            log_message('warning', 'Already reviewed with decision: ' . $currentKeputusan);
            return redirect()->back()->with('error', 'Abstrak ini sudah Anda review sebelumnya dengan keputusan: ' . $currentKeputusan);
        }

        // Prepare update data
        $reviewData = [
            'keputusan' => $keputusan,
            'komentar' => $komentar,
            'tanggal_review' => date('Y-m-d H:i:s')
        ];

        log_message('debug', 'Attempting to update review ID: ' . $existingReview['id_review'] . ' with data: ' . json_encode($reviewData));

        // Get database connection for manual query
        $db = db_connect();

        try {
            // Test with direct query builder (bypass model for debugging)
            $updateResult = $db->table('review')
                              ->where('id_review', $existingReview['id_review'])
                              ->update($reviewData);

            log_message('debug', 'Direct DB update result: ' . ($updateResult ? 'TRUE' : 'FALSE'));
            log_message('debug', 'Affected rows: ' . $db->affectedRows());
            log_message('debug', 'DB error: ' . json_encode($db->error()));

            if (!$updateResult) {
                $dbError = $db->error();
                log_message('error', 'Database update failed: ' . json_encode($dbError));
                return redirect()->back()->with('error', 'Gagal menyimpan review: ' . ($dbError['message'] ?? 'Database error'));
            }

            // Verify update was successful
            $updatedReview = $db->table('review')
                               ->where('id_review', $existingReview['id_review'])
                               ->get()
                               ->getRowArray();

            log_message('debug', 'Updated review verification: ' . json_encode($updatedReview));

            if (!$updatedReview || $updatedReview['keputusan'] !== $keputusan) {
                log_message('error', 'Review update verification failed');
                return redirect()->back()->with('error', 'Review tidak ter-update dengan benar. Silakan coba lagi.');
            }

            // Update abstract status
            $this->updateAbstractStatus($abstrakId);

            // Log activity
            $this->logModel->logActivity(
                $userId,
                "Menyelesaikan review abstrak '{$abstrak['judul']}' (ID: {$abstrakId}) dengan keputusan: {$keputusan}"
            );

            // Send notification to abstract author
            $this->sendNotificationToAuthor($abstrakId, $keputusan, $komentar);

            $statusText = match($keputusan) {
                'diterima' => 'diterima',
                'ditolak' => 'ditolak',
                'revisi' => 'memerlukan revisi'
            };

            log_message('info', "Review completed successfully - User: {$userId}, Abstrak: {$abstrakId}, Decision: {$keputusan}");

            return redirect()->to('reviewer/abstrak')->with('success', 
                "Review berhasil disimpan! Abstrak '{$abstrak['judul']}' telah dinilai {$statusText}."
            );

        } catch (\Exception $e) {
            log_message('error', 'Exception in review submission: ' . $e->getMessage());
            log_message('error', 'Exception trace: ' . $e->getTraceAsString());
            
            return redirect()->back()->withInput()->with('error', 
                'Terjadi kesalahan sistem: ' . $e->getMessage() . '. Silakan coba lagi atau hubungi admin.'
            );
        }
    }

    public function file($filename)
    {
        $userId = session('id_user');
        
        if (!$userId) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Akses ditolak - login required');
        }
        
        // Sanitize filename
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', basename($filename));
        
        if (empty($filename)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Filename tidak valid');
        }
        
        // Check if reviewer has access to this file
        if (!$this->hasFileAccess($userId, $filename)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('File tidak ditemukan atau akses ditolak');
        }

        // File paths to check
        $filePaths = [
            WRITEPATH . 'uploads/abstrak/' . $filename,
            WRITEPATH . 'uploads/abstraks/' . $filename,
            WRITEPATH . 'uploads/' . $filename
        ];

        foreach ($filePaths as $filePath) {
            if (file_exists($filePath) && is_readable($filePath)) {
                $mimeType = mime_content_type($filePath) ?: 'application/octet-stream';
                
                // Log file access
                $this->logModel->logActivity($userId, "Membuka file abstrak: {$filename}");
                
                return $this->response
                    ->setHeader('Content-Type', $mimeType)
                    ->setHeader('Content-Disposition', 'inline; filename="' . $filename . '"')
                    ->setHeader('Cache-Control', 'no-cache, must-revalidate')
                    ->setBody(file_get_contents($filePath));
            }
        }

        throw new \CodeIgniter\Exceptions\PageNotFoundException('File tidak ditemukan: ' . $filename);
    }

    public function download($filename)
    {
        $userId = session('id_user');
        
        if (!$userId) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Akses ditolak - login required');
        }
        
        // Sanitize filename
        $filename = preg_replace('/[^a-zA-Z0-9._-]/', '', basename($filename));
        
        if (empty($filename)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Filename tidak valid');
        }
        
        // Check if reviewer has access to this file
        if (!$this->hasFileAccess($userId, $filename)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('File tidak ditemukan atau akses ditolak');
        }

        // File paths to check
        $filePaths = [
            WRITEPATH . 'uploads/abstrak/' . $filename,
            WRITEPATH . 'uploads/abstraks/' . $filename,
            WRITEPATH . 'uploads/' . $filename
        ];

        foreach ($filePaths as $filePath) {
            if (file_exists($filePath) && is_readable($filePath)) {
                // Log download activity
                $this->logModel->logActivity($userId, "Mengunduh file abstrak: {$filename}");
                
                return $this->response->download($filePath, null);
            }
        }

        throw new \CodeIgniter\Exceptions\PageNotFoundException('File tidak ditemukan: ' . $filename);
    }

    private function hasFileAccess($reviewerId, $filename)
    {
        try {
            $db = db_connect();
            
            // Check if reviewer is assigned to any abstract with this file
            $query = "
                SELECT COUNT(*) as count
                FROM review r
                JOIN abstrak a ON a.id_abstrak = r.id_abstrak
                WHERE r.id_reviewer = ? AND a.file_abstrak = ?
            ";
            
            $result = $db->query($query, [$reviewerId, $filename])->getRowArray();
            
            return ($result['count'] ?? 0) > 0;
            
        } catch (\Exception $e) {
            log_message('error', 'File access check failed: ' . $e->getMessage());
            return false;
        }
    }

    private function updateAbstractStatus($abstrakId)
    {
        try {
            // Get all reviews for this abstract
            $reviews = $this->reviewModel->countByDecision($abstrakId);
            
            log_message('debug', "Review count for abstract {$abstrakId}: " . json_encode($reviews));
            
            // Determine new status based on review results
            $newStatus = $this->determineAbstractStatus($reviews);
            
            // Update abstract status
            $updateResult = $this->abstrakModel->update($abstrakId, ['status' => $newStatus]);
            
            if ($updateResult) {
                log_message('info', "Abstract {$abstrakId} status updated to: {$newStatus}");
            } else {
                log_message('warning', "Failed to update abstract {$abstrakId} status to: {$newStatus}");
            }
            
        } catch (\Exception $e) {
            log_message('error', 'Failed to update abstract status: ' . $e->getMessage());
            // Don't throw exception here to avoid breaking the main flow
        }
    }

    private function determineAbstractStatus($reviews)
    {
        // If there are still pending reviews, keep reviewing status
        if ($reviews['pending'] > 0) {
            return 'sedang_direview';
        }
        
        // All reviews completed - determine final status
        $total = $reviews['diterima'] + $reviews['ditolak'] + $reviews['revisi'];
        
        if ($total === 0) {
            return 'menunggu'; // No reviews yet
        }
        
        // Priority logic:
        // 1. If any review says "ditolak" -> ditolak
        // 2. If any review says "revisi" -> revisi  
        // 3. If all say "diterima" -> diterima
        
        if ($reviews['ditolak'] > 0) {
            return 'ditolak';
        } elseif ($reviews['revisi'] > 0) {
            return 'revisi';
        } elseif ($reviews['diterima'] > 0) {
            return 'diterima';
        }
        
        return 'menunggu'; // Fallback
    }

    private function sendNotificationToAuthor($abstrakId, $keputusan, $komentar)
    {
        try {
            // Get abstract details
            $abstrak = $this->abstrakModel->getDetailWithRelations($abstrakId);
            
            if (!$abstrak) {
                log_message('warning', 'Cannot send notification - abstract not found: ' . $abstrakId);
                return;
            }
            
            $statusText = match($keputusan) {
                'diterima' => 'diterima',
                'ditolak' => 'ditolak', 
                'revisi' => 'memerlukan revisi'
            };
            
            $title = "Review Abstrak Selesai";
            $message = "Abstrak '{$abstrak['judul']}' telah {$statusText} oleh reviewer.";
            
            // Add preview of comment if exists
            if (!empty($komentar)) {
                $shortComment = strlen($komentar) > 100 ? substr($komentar, 0, 100) . '...' : $komentar;
                $message .= "\n\nKomentar: {$shortComment}";
            }
            
            // Send notification
            $this->notificationModel->add(
                $abstrak['id_user'],
                $title,
                $message,
                site_url('presenter/abstrak/detail/' . $abstrakId),
                'review'
            );
            
            log_message('info', "Notification sent to user {$abstrak['id_user']} for abstract {$abstrakId}");
            
        } catch (\Exception $e) {
            log_message('error', 'Failed to send notification: ' . $e->getMessage());
            // Don't throw exception here to avoid breaking main flow
        }
    }
}