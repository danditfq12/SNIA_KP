<?php

namespace App\Models;

use CodeIgniter\Model;

class ReviewerKategoriModel extends Model
{
    protected $table            = 'reviewer_kategori';
    protected $primaryKey       = 'id';
    protected $returnType       = 'array';
    protected $useSoftDeletes   = false;

    protected $allowedFields    = ['id_reviewer', 'id_kategori'];

    protected $useTimestamps = false;

    /**
     * (BARU) List reviewer aktif & rolenya reviewer untuk kategori tertentu
     * Return: id_user, nama_lengkap, email
     */
    public function getReviewersByKategori(int $idKategori): array
    {
        return $this->db->table($this->table)
            ->select('users.id_user, users.nama_lengkap, users.email')
            ->join('users', 'users.id_user = reviewer_kategori.id_reviewer')
            ->where('reviewer_kategori.id_kategori', $idKategori)
            ->where('users.role', 'reviewer')
            ->where('users.status', 'aktif')
            ->orderBy('users.nama_lengkap', 'ASC')
            ->get()->getResultArray();
    }

    /**
     * (BARU) Semua kategori yang dikuasai reviewer
     */
    public function getKategoriByReviewer(int $idReviewer): array
    {
        return $this->db->table($this->table)
            ->select('reviewer_kategori.id_kategori, kategori_abstrak.nama_kategori')
            ->join('kategori_abstrak', 'kategori_abstrak.id_kategori = reviewer_kategori.id_kategori')
            ->where('reviewer_kategori.id_reviewer', $idReviewer)
            ->orderBy('kategori_abstrak.nama_kategori', 'ASC')
            ->get()->getResultArray();
    }

    /**
     * (BARU) Cek kelayakan reviewer untuk kategori
     */
    public function isReviewerEligible(int $idReviewer, int $idKategori): bool
    {
        $row = $this->where([
            'id_reviewer' => $idReviewer,
            'id_kategori' => $idKategori,
        ])->first();

        return !empty($row);
    }
}
