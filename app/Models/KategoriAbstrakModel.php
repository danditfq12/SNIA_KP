<?php
namespace App\Models;

use CodeIgniter\Model;

class KategoriAbstrakModel extends Model
{
    protected $table         = 'kategori_abstrak';
    protected $primaryKey    = 'id_kategori';
    protected $returnType    = 'array';

    // Kolom yang boleh diisi (wajib sertakan is_active biar bisa update status aktif/nonaktif)
    protected $allowedFields = [
        'nama_kategori',
        'deskripsi',
        'is_active',
    ];

    // Kalau kamu ingin pakai timestamp (created_at, updated_at)
    protected $useTimestamps = false; // ubah ke true kalau nanti mau ditambah

    // Validasi otomatis (opsional)
    protected $validationRules = [
        'nama_kategori' => 'required|min_length[3]|max_length[100]',
        'deskripsi'     => 'permit_empty|max_length[500]',
        'is_active'     => 'permit_empty|in_list[0,1]',
    ];

    protected $validationMessages = [
        'nama_kategori' => [
            'required'    => 'Nama kategori wajib diisi.',
            'min_length'  => 'Nama kategori minimal 3 karakter.',
            'max_length'  => 'Nama kategori maksimal 100 karakter.',
        ],
    ];

    /**
     * Ambil hanya kategori aktif
     */
    public function getActive()
    {
        return $this->where('is_active', 1)
                    ->orderBy('nama_kategori', 'ASC')
                    ->findAll();
    }

    /**
     * Ambil hanya kategori nonaktif
     */
    public function getInactive()
    {
        return $this->where('is_active', 0)
                    ->orderBy('nama_kategori', 'ASC')
                    ->findAll();
    }
}