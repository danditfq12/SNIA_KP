<?php

namespace App\Models;

use CodeIgniter\Model;

class ReviewModel extends Model
{
    protected $table            = 'review';
    protected $primaryKey       = 'id_review';
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields    = [
        'id_abstrak',
        'id_reviewer',
        'keputusan',
        'komentar',
        'tanggal_review',
    ];

    protected $useTimestamps = false;

    /**
     * Semua review untuk satu abstrak
     */
    public function getByAbstrak(int $idAbstrak): array
    {
        return $this->where('id_abstrak', $idAbstrak)
            ->orderBy('tanggal_review', 'DESC')
            ->findAll();
    }

    /**
     * Semua review + nama reviewer
     */
    public function getByAbstrakWithReviewer(int $idAbstrak): array
    {
        return $this->db->table($this->table)
            ->select('review.*, users.nama_lengkap AS reviewer_name, users.email AS reviewer_email')
            ->join('users', 'users.id_user = review.id_reviewer')
            ->where('review.id_abstrak', $idAbstrak)
            ->orderBy('review.tanggal_review', 'DESC')
            ->get()->getResultArray();
    }

    /**
     * Cek apakah sudah ada assignment pending
     */
    public function hasPendingReview(int $idAbstrak): bool
    {
        return $this->where('id_abstrak', $idAbstrak)
                   ->where('keputusan', 'pending')
                   ->countAllResults() > 0;
    }

    /**
     * FIXED: Assign reviewer dengan string kosong untuk NOT NULL constraint
     */
    public function assignReviewer(int $idAbstrak, int $idReviewer): bool
    {
        $data = [
            'id_abstrak'     => $idAbstrak,
            'id_reviewer'    => $idReviewer,
            'keputusan'      => 'pending',
            'komentar'       => '', // Empty string instead of NULL
            'tanggal_review' => date('Y-m-d H:i:s'), // Current timestamp instead of NULL
        ];
        return (bool)$this->insert($data, false);
    }

    /**
     * Update keputusan/komentar saat reviewer submit
     */
    public function updateDecision(int $idReview, string $keputusan, ?string $komentar = null): bool
    {
        $data = [
            'keputusan'      => $keputusan,
            'komentar'       => $komentar ?: '', // Ensure not null
            'tanggal_review' => date('Y-m-d H:i:s'),
        ];
        return $this->update($idReview, $data);
    }

    /**
     * Hitung ringkas keputusan per abstrak
     */
    public function countByDecision(int $idAbstrak): array
    {
        $rows = $this->select('keputusan, COUNT(*) AS jml')
            ->where('id_abstrak', $idAbstrak)
            ->groupBy('keputusan')
            ->findAll();

        $out = ['pending' => 0, 'diterima' => 0, 'ditolak' => 0, 'revisi' => 0];
        foreach ($rows as $r) {
            $out[$r['keputusan']] = (int)$r['jml'];
        }
        return $out;
    }

    /**
     * Get reviews with proper handling of empty comments
     */
    public function getReviewsForDisplay(int $idAbstrak): array
    {
        $reviews = $this->getByAbstrakWithReviewer($idAbstrak);
        
        // Process untuk display - jangan tampilkan komentar kosong untuk pending
        foreach ($reviews as &$review) {
            if ($review['keputusan'] === 'pending' && empty(trim($review['komentar']))) {
                $review['display_comment'] = null; // Untuk view logic
                $review['is_pending'] = true;
            } else {
                $review['display_comment'] = $review['komentar'];
                $review['is_pending'] = false;
            }
        }
        
        return $reviews;
    }
}