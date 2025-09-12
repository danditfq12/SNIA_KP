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
        'id_reviewer',     // users.id_user dengan role=reviewer
        'keputusan',       // pending | diterima | ditolak | revisi
        'komentar',
        'tanggal_review',  // nullable saat baru assign
    ];

    protected $useTimestamps = false;

    /**
     * (BARU) Semua review untuk satu abstrak (tanpa join)
     */
    public function getByAbstrak(int $idAbstrak): array
    {
        return $this->where('id_abstrak', $idAbstrak)
            ->orderBy('tanggal_review', 'DESC')
            ->findAll();
    }

    /**
     * (BARU) Semua review + nama reviewer (untuk detail)
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
     * (BARU) Cek apakah sudah ada assignment pending untuk abstrak
     */
    public function hasPendingReview(int $idAbstrak): bool
    {
        $row = $this->where('id_abstrak', $idAbstrak)
            ->where('keputusan', 'pending')
            ->first();

        return !empty($row);
    }

    /**
     * (BARU) Assign reviewer → membuat row review dengan keputusan 'pending'
     */
    public function assignReviewer(int $idAbstrak, int $idReviewer): bool
    {
        $data = [
            'id_abstrak'     => $idAbstrak,
            'id_reviewer'    => $idReviewer,
            'keputusan'      => 'pending',
            'komentar'       => null,
            'tanggal_review' => null,
        ];
        return (bool)$this->insert($data, false);
    }

    /**
     * (BARU) Update keputusan/komentar (mis. saat reviewer submit)
     */
    public function updateDecision(int $idReview, string $keputusan, ?string $komentar = null): bool
    {
        $data = [
            'keputusan'      => $keputusan,
            'komentar'       => $komentar,
            'tanggal_review' => date('Y-m-d H:i:s'),
        ];
        return $this->update($idReview, $data);
    }

    /**
     * (BARU) Hitung ringkas keputusan per abstrak
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
}
