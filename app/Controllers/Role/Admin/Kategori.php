<?php

namespace App\Controllers\Role\Admin;

use App\Controllers\BaseController;
use App\Models\KategoriAbstrakModel;

class Kategori extends BaseController
{
    protected KategoriAbstrakModel $kategoriModel;
    protected \CodeIgniter\Database\BaseConnection $db;

    public function __construct()
    {
        $this->kategoriModel = new KategoriAbstrakModel();
        $this->db = \Config\Database::connect();
    }

    /* ============================ Helpers ============================ */

    private function mustBeAdmin()
    {
        if (strtolower((string) session('role')) !== 'admin') {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['success' => false, 'message' => 'Akses ditolak'])->setStatusCode(403);
            }
            return redirect()->to('/dashboard')->with('error', 'Akses ditolak');
        }
        return null;
    }

    private function toBool($val): ?bool
    {
        if ($val === null) return null;
        $filtered = filter_var($val, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);
        if ($filtered !== null) return $filtered;
        return in_array((string) $val, ['1','on','yes','y','true','t'], true);
    }

    private function abstrakCountByKategori(int $kategoriId): int
    {
        try {
            $abstrakModel = new \App\Models\AbstrakModel();
            return (int) $abstrakModel->where('id_kategori', $kategoriId)->countAllResults();
        } catch (\Throwable $e) {
            return 0;
        }
    }

    private function logActivity($userId, $activity): void
    {
        try {
            $this->db->table('log_aktivitas')->insert([
                'id_user'   => (int) $userId,
                'aktivitas' => (string) $activity,
                'waktu'     => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) { /* noop */ }
    }

    /* ============================ Pages ============================ */

    /**
     * Selalu kirim SEMUA kategori; tab (?tab=aktif|nonaktif|semua) hanya untuk initial state di view.
     */
    public function index()
    {
        if ($resp = $this->mustBeAdmin()) return $resp;

        $tab = strtolower((string) ($this->request->getGet('tab') ?: 'semua'));
        if (!in_array($tab, ['aktif','nonaktif','semua'], true)) $tab = 'semua';

        // Kirim SEMUA kategori; urutkan biar rapi (aktif dulu).
        $kategori = $this->kategoriModel
            ->orderBy('is_active', 'DESC')
            ->orderBy('nama_kategori', 'ASC')
            ->findAll();

        // Tambahkan statistik (hindari state “nyangkut” pada builder)
        $totalKategori = (int) $this->db->table('kategori_abstrak')->countAllResults();
        $totalAktif    = (int) $this->db->table('kategori_abstrak')->where('is_active', true)->countAllResults();
        $totalNonaktif = (int) $this->db->table('kategori_abstrak')->where('is_active', false)->countAllResults();

        $totalTerpakai = 0;
        foreach ($kategori as &$k) {
            $k['jumlah_abstrak'] = $this->abstrakCountByKategori((int) $k['id_kategori']);
            $totalTerpakai += (int) $k['jumlah_abstrak'];
        }
        unset($k);

        return view('role/admin/kategori/index', [
            'title'    => 'Manajemen Kategori Abstrak',
            'kategori' => $kategori,
            'stats'    => [
                'total_kategori'    => $totalKategori,
                'total_aktif'       => $totalAktif,
                'total_nonaktif'    => $totalNonaktif,
                'kategori_terpakai' => $totalTerpakai,
                'tab'               => $tab,
            ],
            'tab'      => $tab,
        ]);
    }

    /* ============================ CRUD ============================ */

    public function store()
    {
        if ($resp = $this->mustBeAdmin()) return $resp;
        if (!$this->request->isAJAX()) return redirect()->back()->with('error', 'Invalid request');

        $rules = [
            'nama_kategori' => 'required|min_length[3]|max_length[100]|is_unique[kategori_abstrak.nama_kategori]',
            'deskripsi'     => 'permit_empty|max_length[500]',
        ];
        if (!$this->validate($rules)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $this->validator->getErrors(),
            ]);
        }

        $isActive = $this->toBool($this->request->getPost('is_active'));
        if ($isActive === null) $isActive = true;

        $data = [
            'nama_kategori' => trim((string) $this->request->getPost('nama_kategori')),
            'deskripsi'     => trim((string) $this->request->getPost('deskripsi')) ?: null,
            'is_active'     => $isActive,
        ];

        $this->db->transStart();
        try {
            if (!$this->kategoriModel->insert($data)) {
                throw new \RuntimeException('Gagal menyimpan kategori: ' . implode(', ', $this->kategoriModel->errors()));
            }
            $newId = (int) $this->kategoriModel->getInsertID();
            $this->logActivity(session('id_user'), "Create kategori: {$data['nama_kategori']} (ID: {$newId})");
            $this->db->transComplete();

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Kategori berhasil ditambahkan',
                'data'    => ['id' => $newId],
            ]);
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'Kategori store error: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Gagal menambahkan kategori.']);
        }
    }

    public function show($id)
    {
        if ($resp = $this->mustBeAdmin()) return $resp;
        if (!$this->request->isAJAX()) return redirect()->back()->with('error', 'Invalid request');

        try {
            $kategori = $this->kategoriModel->find((int) $id);
            if (!$kategori) {
                return $this->response->setJSON(['success' => false, 'message' => 'Kategori tidak ditemukan'])->setStatusCode(404);
            }
            return $this->response->setJSON(['success' => true, 'data' => $kategori]);
        } catch (\Throwable $e) {
            log_message('error', 'Kategori show error: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Gagal memuat data kategori']);
        }
    }

    public function update($id)
    {
        if ($resp = $this->mustBeAdmin()) return $resp;
        if (!$this->request->isAJAX()) return redirect()->back()->with('error', 'Invalid request');

        $id  = (int) $id;
        $row = $this->kategoriModel->find($id);
        if (!$row) {
            return $this->response->setJSON(['success' => false, 'message' => 'Kategori tidak ditemukan'])->setStatusCode(404);
        }

        $rules = [
            'nama_kategori' => "required|min_length[3]|max_length[100]|is_unique[kategori_abstrak.nama_kategori,id_kategori,{$id}]",
            'deskripsi'     => 'permit_empty|max_length[500]',
        ];
        if (!$this->validate($rules)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $this->validator->getErrors(),
            ]);
        }

        $data = [
            'nama_kategori' => trim((string) $this->request->getPost('nama_kategori')),
            'deskripsi'     => trim((string) $this->request->getPost('deskripsi')) ?: null,
        ];
        if ($this->request->getPost('is_active') !== null) {
            $bool = $this->toBool($this->request->getPost('is_active'));
            if ($bool !== null) $data['is_active'] = $bool;
        }

        $this->db->transStart();
        try {
            if (!$this->kategoriModel->update($id, $data)) {
                throw new \RuntimeException('Gagal memperbarui kategori: ' . implode(', ', $this->kategoriModel->errors()));
            }
            $this->logActivity(session('id_user'), "Update kategori: {$data['nama_kategori']} (ID: {$id})");
            $this->db->transComplete();

            return $this->response->setJSON(['success' => true, 'message' => 'Kategori berhasil diperbarui']);
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'Kategori update error: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Gagal memperbarui kategori.']);
        }
    }

    public function delete($id)
    {
        if ($resp = $this->mustBeAdmin()) return $resp;
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request'])->setStatusCode(400);
        }

        $id  = (int) $id;
        $row = $this->kategoriModel->find($id);
        if (!$row) {
            return $this->response->setJSON(['success' => false, 'message' => 'Kategori tidak ditemukan'])->setStatusCode(404);
        }

        $usedCount = $this->abstrakCountByKategori($id);
        if ($usedCount > 0) {
            return $this->response->setJSON([
                'success' => false,
                'message' => "Kategori tidak dapat dihapus karena masih digunakan oleh {$usedCount} abstrak"
            ]);
        }

        $this->db->transStart();
        try {
            $this->kategoriModel->delete($id);
            $this->logActivity(session('id_user'), "Delete kategori: {$row['nama_kategori']} (ID: {$id})");
            $this->db->transComplete();

            return $this->response->setJSON(['success' => true, 'message' => 'Kategori berhasil dihapus']);
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'Kategori delete error: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Gagal menghapus kategori.']);
        }
    }

    /* ====================== Aktivasi / Non-aktivasi ====================== */

    public function toggle($id)
    {
        if ($resp = $this->mustBeAdmin()) return $resp;
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request'])->setStatusCode(400);
        }

        $id  = (int) $id;
        $row = $this->kategoriModel->find($id);
        if (!$row) return $this->response->setJSON(['success' => false, 'message' => 'Kategori tidak ditemukan'])->setStatusCode(404);

        $new = !(bool) $row['is_active'];
        $this->kategoriModel->update($id, ['is_active' => $new]);

        return $this->response->setJSON([
            'success' => true,
            'message' => $new ? 'Kategori diaktifkan' : 'Kategori dinonaktifkan',
            'data'    => ['id' => $id, 'is_active' => $new],
        ]);
    }

    /**
     * Bulk activate/deactivate.
     * Body: ids[]=1&ids[]=2&action=activate|deactivate
     */
    public function bulk()
    {
        if ($resp = $this->mustBeAdmin()) return $resp;
        if (!$this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => 'Invalid request'])->setStatusCode(400);
        }

        $ids    = (array) $this->request->getPost('ids');
        $action = strtolower((string) $this->request->getPost('action'));
        if (!$ids || !in_array($action, ['activate', 'deactivate'], true)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Input tidak valid']);
        }

        $ids = array_values(array_unique(array_map('intval', $ids)));
        $set = ($action === 'activate');

        $this->db->transStart();
        try {
            $this->db->table('kategori_abstrak')
                ->whereIn('id_kategori', $ids)
                ->update(['is_active' => $set]);

            $this->logActivity(session('id_user'), "Bulk {$action} kategori: " . implode(',', $ids));
            $this->db->transComplete();

            return $this->response->setJSON([
                'success' => true,
                'message' => ($set ? 'Diaktifkan' : 'Dinonaktifkan') . ' ' . count($ids) . ' kategori',
                'data'    => ['affected' => count($ids), 'is_active' => $set],
            ]);
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'Kategori bulk error: ' . $e->getMessage());
            return $this->response->setJSON(['success' => false, 'message' => 'Gagal memproses aksi bulk.']);
        }
    }
}
