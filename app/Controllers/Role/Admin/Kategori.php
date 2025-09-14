<?php

namespace App\Controllers\Role\Admin;

use App\Controllers\BaseController;
use App\Models\KategoriAbstrakModel;

class Kategori extends BaseController
{
    protected $kategoriModel;
    protected $db;

    public function __construct()
    {
        $this->kategoriModel = new KategoriAbstrakModel();
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        // Cek role admin
        if (session('role') !== 'admin') {
            return redirect()->to('/dashboard')->with('error', 'Akses ditolak');
        }

        try {
            // Ambil semua kategori dengan statistik penggunaan
            $kategori = $this->kategoriModel->orderBy('nama_kategori', 'ASC')->findAll();
            
            // Tambahkan informasi penggunaan untuk setiap kategori
            foreach ($kategori as &$k) {
                $abstrakModel = new \App\Models\AbstrakModel();
                $k['jumlah_abstrak'] = $abstrakModel->where('id_kategori', $k['id_kategori'])->countAllResults();
            }

            // Hitung statistik
            $stats = [
                'total_kategori' => count($kategori),
                'kategori_terpakai' => array_sum(array_column($kategori, 'jumlah_abstrak')),
            ];

            $data = [
                'title' => 'Manajemen Kategori Abstrak',
                'kategori' => $kategori,
                'stats' => $stats
            ];

            return view('role/admin/kategori/index', $data);

        } catch (\Exception $e) {
            log_message('error', 'Kategori index error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memuat data kategori.');
        }
    }

    public function store()
    {
        // Validasi AJAX request
        if (!$this->request->isAJAX()) {
            return redirect()->back()->with('error', 'Invalid request');
        }

        // Validasi role
        if (session('role') !== 'admin') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Access denied'
            ])->setStatusCode(403);
        }

        // Validasi input
        $rules = [
            'nama_kategori' => 'required|min_length[3]|max_length[100]|is_unique[kategori_abstrak.nama_kategori]',
            'deskripsi' => 'permit_empty|max_length[500]'
        ];

        if (!$this->validate($rules)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $this->validator->getErrors()
            ]);
        }

        $data = [
            'nama_kategori' => trim($this->request->getPost('nama_kategori')),
            'deskripsi' => trim($this->request->getPost('deskripsi')) ?: null
        ];

        $this->db->transStart();

        try {
            if (!$this->kategoriModel->insert($data)) {
                throw new \Exception('Gagal menyimpan kategori: ' . implode(', ', $this->kategoriModel->errors()));
            }

            $newId = $this->kategoriModel->getInsertID();
            
            // Log activity
            $this->logActivity(session('id_user'), "Created new category: {$data['nama_kategori']} (ID: {$newId})");

            $this->db->transComplete();

            if ($this->db->transStatus() === FALSE) {
                throw new \Exception('Transaction failed');
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Kategori berhasil ditambahkan',
                'data' => ['id' => $newId]
            ]);

        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Kategori store error: ' . $e->getMessage());
            
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Gagal menambahkan kategori: ' . $e->getMessage()
            ]);
        }
    }

    public function show($id)
    {
        // Validasi AJAX request
        if (!$this->request->isAJAX()) {
            return redirect()->back()->with('error', 'Invalid request');
        }

        // Validasi role
        if (session('role') !== 'admin') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Access denied'
            ])->setStatusCode(403);
        }

        try {
            $kategori = $this->kategoriModel->find($id);
            
            if (!$kategori) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Kategori tidak ditemukan'
                ])->setStatusCode(404);
            }

            return $this->response->setJSON([
                'success' => true,
                'data' => $kategori
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Kategori show error: ' . $e->getMessage());
            
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Gagal memuat data kategori'
            ]);
        }
    }

    public function update($id)
    {
        // Validasi request method - terima POST dengan method override
        $method = $this->request->getPost('_method') ?? $this->request->getServer('HTTP_X_HTTP_METHOD_OVERRIDE');
        
        if (!$this->request->isAJAX()) {
            return redirect()->back()->with('error', 'Invalid request');
        }

        // Validasi role
        if (session('role') !== 'admin') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Access denied'
            ])->setStatusCode(403);
        }

        $kategori = $this->kategoriModel->find($id);
        
        if (!$kategori) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Kategori tidak ditemukan'
            ])->setStatusCode(404);
        }

        // Validasi input dengan pengecualian untuk nama yang sama (edit)
        $rules = [
            'nama_kategori' => "required|min_length[3]|max_length[100]|is_unique[kategori_abstrak.nama_kategori,id_kategori,{$id}]",
            'deskripsi' => 'permit_empty|max_length[500]'
        ];

        if (!$this->validate($rules)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $this->validator->getErrors()
            ]);
        }

        $data = [
            'nama_kategori' => trim($this->request->getPost('nama_kategori')),
            'deskripsi' => trim($this->request->getPost('deskripsi')) ?: null
        ];

        $this->db->transStart();

        try {
            if (!$this->kategoriModel->update($id, $data)) {
                throw new \Exception('Gagal memperbarui kategori: ' . implode(', ', $this->kategoriModel->errors()));
            }

            // Log activity
            $this->logActivity(session('id_user'), "Updated category: {$data['nama_kategori']} (ID: {$id})");

            $this->db->transComplete();

            if ($this->db->transStatus() === FALSE) {
                throw new \Exception('Transaction failed');
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Kategori berhasil diperbarui'
            ]);

        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Kategori update error: ' . $e->getMessage());
            
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Gagal memperbarui kategori: ' . $e->getMessage()
            ]);
        }
    }

    public function delete($id)
    {
        // Validasi method - terima POST dengan method override untuk DELETE
        $method = $this->request->getPost('_method') ?? $this->request->getServer('HTTP_X_HTTP_METHOD_OVERRIDE');
        
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Invalid request'
            ])->setStatusCode(400);
        }

        // Validasi role
        if (session('role') !== 'admin') {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Access denied'
            ])->setStatusCode(403);
        }

        $kategori = $this->kategoriModel->find($id);
        
        if (!$kategori) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Kategori tidak ditemukan'
            ])->setStatusCode(404);
        }

        // Cek apakah kategori masih digunakan di tabel abstrak
        $abstrakModel = new \App\Models\AbstrakModel();
        $usedCount = $abstrakModel->where('id_kategori', $id)->countAllResults();

        if ($usedCount > 0) {
            return $this->response->setJSON([
                'success' => false,
                'message' => "Kategori tidak dapat dihapus karena masih digunakan oleh {$usedCount} abstrak"
            ]);
        }

        // Cek apakah ada reviewer yang di-assign ke kategori ini
        try {
            $reviewerKategoriModel = new \App\Models\ReviewerKategoriModel();
            $assignedReviewers = $reviewerKategoriModel->where('id_kategori', $id)->countAllResults();

            if ($assignedReviewers > 0) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => "Kategori tidak dapat dihapus karena masih memiliki {$assignedReviewers} reviewer yang di-assign"
                ]);
            }
        } catch (\Exception $e) {
            // Jika model ReviewerKategori tidak ada, lanjutkan
            log_message('info', 'ReviewerKategori model not found, skipping check');
        }

        $this->db->transStart();

        try {
            if (!$this->kategoriModel->delete($id)) {
                throw new \Exception('Failed to delete category');
            }

            // Log activity
            $this->logActivity(session('id_user'), "Deleted category: {$kategori['nama_kategori']} (ID: {$id})");

            $this->db->transComplete();

            if ($this->db->transStatus() === FALSE) {
                throw new \Exception('Transaction failed');
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Kategori berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Kategori delete error: ' . $e->getMessage());
            
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Gagal menghapus kategori: ' . $e->getMessage()
            ]);
        }
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