<?php
namespace App\Models;

use CodeIgniter\Model;

class DokumenModel extends Model
{
    protected $table         = 'dokumen';
    protected $primaryKey    = 'id_dokumen';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'id_user', 'tipe', 'file_path', 'syarat', 'uploaded_at'
    ];

    /**
     * Dipakai di Admin: ambil dokumen + info user
     */
    public function getDokumenWithUser(?string $tipe = null): array
    {
        $builder = $this->select('dokumen.*, users.nama_lengkap, users.email, users.role')
                        ->join('users', 'users.id_user = dokumen.id_user', 'left');

        if (!empty($tipe)) {
            $builder->where('dokumen.tipe', $tipe);
        }

        return $builder->orderBy('uploaded_at', 'DESC')
                       ->orderBy('id_dokumen', 'DESC')
                       ->findAll();
    }

    /**
     * Ambil dokumen milik satu user (opsional filter tipe)
     */
    public function getUserDocs(int $userId, ?string $tipe = null): array
    {
        $builder = $this->where('id_user', $userId);

        if (!empty($tipe)) {
            $builder->where('tipe', $tipe);
        }

        return $builder->orderBy('uploaded_at', 'DESC')
                       ->orderBy('id_dokumen', 'DESC')
                       ->findAll();
    }

    /**
     * Ambil satu dokumen + info user
     */
    public function getOneWithUser(int $idDokumen): ?array
    {
        return $this->select('dokumen.*, users.nama_lengkap, users.email, users.role')
                    ->join('users', 'users.id_user = dokumen.id_user', 'left')
                    ->where('dokumen.id_dokumen', $idDokumen)
                    ->first();
    }

    /**
     * Dipakai Audience: daftar sertifikat milik user
     * (Saat ini tabel 'dokumen' tidak punya 'event_id', jadi hanya list file sertifikat per user.)
     */
    public function listSertifikatByUser(int $userId): array
    {
        return $this->where('id_user', $userId)
                    ->where('tipe', 'sertifikat')
                    ->orderBy('uploaded_at', 'DESC')
                    ->orderBy('id_dokumen', 'DESC')
                    ->findAll();
    }

    /**
     * Alias untuk typo yang terlanjur dipanggil: listSertifikatByUse
     * Biar tidak perlu ubah controller, tapi sebaiknya nanti dirapikan memanggil listSertifikatByUser().
     */
    public function listSertifikatByUse(int $userId): array
    {
        return $this->listSertifikatByUser($userId);
    }
}
