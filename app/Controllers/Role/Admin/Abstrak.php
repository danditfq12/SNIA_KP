<?php

namespace App\Controllers\Role\Admin;

use App\Controllers\BaseController;
use App\Models\AbstrakModel;
use App\Models\ReviewModel;
use App\Models\ReviewerKategoriModel;

class Abstrak extends BaseController
{
    protected $abstrakModel;
    protected $reviewModel;
    protected $revKatModel;

    public function __construct()
    {
        $this->abstrakModel = new AbstrakModel();
        $this->reviewModel  = new ReviewModel();
        $this->revKatModel  = new ReviewerKategoriModel();
    }

    /**
     * LIST + KPI
     */
    public function index()
    {
        // KPI
        $stats = $this->abstrakModel->getStats();

        // Tabel utama
        $abstraks = $this->abstrakModel->getAbstrakWithDetails();

        // (View kamu butuh variabel $reviewers untuk modal; isi kosong dulu—nanti diisi via AJAX by-category)
        $data = [
            'total_abstrak'   => $stats['total'] ?? 0,
            'abstrak_pending' => $stats['menunggu'] ?? 0,
            'abstrak_diterima'=> $stats['diterima'] ?? 0,
            'abstrak_ditolak' => $stats['ditolak'] ?? 0,
            'abstraks'        => $abstraks,
            'reviewers'       => [], // biar foreach di view aman
        ];

        return view('role/admin/abstrak/index', $data);
    }

    /**
     * DETAIL
     */
    public function detail($id)
    {
        $abstrak = $this->abstrakModel->getDetailWithRelations((int)$id);
        if (!$abstrak) {
            return redirect()->to(site_url('admin/abstrak'))->with('error', 'Abstrak tidak ditemukan.');
        }

        $reviews = $this->reviewModel->getByAbstrakWithReviewer((int)$id);

        return view('role/admin/abstrak/detail', [
            'abstrak' => $abstrak,
            'reviews' => $reviews,
        ]);
    }

    /**
     * ASSIGN REVIEWER (POST /admin/abstrak/assign/{id_abstrak})
     */
    public function assign($idAbstrak)
    {
        $idAbstrak  = (int)$idAbstrak;
        $idReviewer = (int)$this->request->getPost('id_reviewer');

        if (!$idReviewer) {
            return redirect()->back()->with('error', 'Reviewer wajib dipilih.');
        }

        $abstrak = $this->abstrakModel->find($idAbstrak);
        if (!$abstrak) {
            return redirect()->to(site_url('admin/abstrak'))->with('error', 'Abstrak tidak ditemukan.');
        }

        // Cek kelayakan reviewer di kategori abstrak tsb
        $eligible = $this->revKatModel->isReviewerEligible($idReviewer, (int)$abstrak['id_kategori']);
        if (!$eligible) {
            return redirect()->back()->with('error', 'Reviewer tidak sesuai kategori abstrak.');
        }

        // Cegah duplikasi pending untuk abstrak yang sama (opsional)
        if ($this->reviewModel->hasPendingReview($idAbstrak)) {
            return redirect()->back()->with('error', 'Abstrak ini sudah memiliki assignment reviewer yang pending.');
        }

        // Buat row review (pending)
        $ok = $this->reviewModel->assignReviewer($idAbstrak, $idReviewer);
        if (!$ok) {
            return redirect()->back()->with('error', 'Gagal assign reviewer.');
        }

        // Update status abstrak ke "sedang_direview" kalau masih "menunggu"
        if (($abstrak['status'] ?? 'menunggu') === 'menunggu') {
            $this->abstrakModel->update($idAbstrak, ['status' => 'sedang_direview']);
        }

        return redirect()->to(site_url('admin/abstrak'))->with('success', 'Reviewer berhasil ditugaskan.');
    }

    /**
     * UPDATE STATUS (AJAX POST /admin/abstrak/update-status)
     */
    public function updateStatus()
    {
        $idAbstrak = (int)$this->request->getPost('id_abstrak');
        $status    = (string)$this->request->getPost('status');
        $komentar  = (string)$this->request->getPost('komentar');

        $allowed = ['menunggu', 'sedang_direview', 'diterima', 'ditolak', 'revisi'];
        if (!$idAbstrak || !in_array($status, $allowed, true)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Data tidak valid.'
            ]);
        }

        $exists = $this->abstrakModel->find($idAbstrak);
        if (!$exists) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Abstrak tidak ditemukan.'
            ]);
        }

        $this->abstrakModel->update($idAbstrak, ['status' => $status]);

        // Jika kamu ingin log komentar admin sebagai review "admin", bisa insert ke tabel review di sini (opsional).
        if ($komentar !== '') {
            $this->reviewModel->insert([
                'id_abstrak'     => $idAbstrak,
                'id_reviewer'    => session('id_user') ?: null, // admin sebagai pemberi komentar
                'keputusan'      => $status,
                'komentar'       => $komentar,
                'tanggal_review' => date('Y-m-d H:i:s'),
            ], false);
        }

        return $this->response->setJSON([
            'success' => true,
            'message' => 'Status abstrak berhasil diperbarui.'
        ]);
    }

    /**
     * BULK UPDATE STATUS (opsional, kalau dipakai)
     * POST: ids[]=1&ids[]=2&status=diterima
     */
    public function bulkUpdateStatus()
    {
        $ids    = (array)$this->request->getPost('ids');
        $status = (string)$this->request->getPost('status');

        $allowed = ['menunggu', 'sedang_direview', 'diterima', 'ditolak', 'revisi'];
        if (!$ids || !in_array($status, $allowed, true)) {
            return $this->response->setJSON(['success' => false, 'message' => 'Input tidak valid.']);
        }

        foreach ($ids as $id) {
            $this->abstrakModel->update((int)$id, ['status' => $status]);
        }

        return $this->response->setJSON(['success' => true, 'message' => 'Status berhasil diupdate massal.']);
    }

    /**
     * DELETE (GET/POST /admin/abstrak/delete/{id})
     */
    public function delete($id)
    {
        $id = (int)$id;
        $abstrak = $this->abstrakModel->find($id);
        if (!$abstrak) {
            return redirect()->to(site_url('admin/abstrak'))->with('error', 'Abstrak tidak ditemukan.');
        }

        // Hapus review terkait
        $this->reviewModel->where('id_abstrak', $id)->delete();

        // Hapus file fisik (jika ada)
        $filename = $abstrak['file_abstrak'] ?? '';
        if ($filename) {
            // Coba dua kemungkinan lokasi
            $paths = [
                FCPATH . 'uploads/abstrak/' . $filename,
                WRITEPATH . 'uploads/abstrak/' . $filename,
            ];
            foreach ($paths as $p) {
                if (is_file($p)) {
                    @unlink($p);
                }
            }
        }

        // Hapus row abstrak
        $this->abstrakModel->delete($id);

        return redirect()->to(site_url('admin/abstrak'))->with('success', 'Abstrak berhasil dihapus.');
    }

    /**
     * DOWNLOAD FILE (/admin/abstrak/download/{id})
     */
    public function downloadFile($id)
    {
        $id = (int)$id;
        $abstrak = $this->abstrakModel->find($id);
        if (!$abstrak || empty($abstrak['file_abstrak'])) {
            return redirect()->to(site_url('admin/abstrak'))->with('error', 'File tidak ditemukan.');
        }

        $filename = $abstrak['file_abstrak'];
        $paths = [
            FCPATH . 'uploads/abstrak/' . $filename,
            WRITEPATH . 'uploads/abstrak/' . $filename,
        ];

        foreach ($paths as $p) {
            if (is_file($p)) {
                return $this->response->download($p, null)->setFileName($filename);
            }
        }

        return redirect()->to(site_url('admin/abstrak'))->with('error', 'File tidak ada di server.');
    }

    /**
     * EXPORT CSV (/admin/abstrak/export)
     */
    public function export()
    {
        $rows = $this->abstrakModel->getAbstrakWithDetails();

        $csv = fopen('php://temp', 'w+');
        // Header
        fputcsv($csv, ['No', 'Judul', 'Penulis', 'Email', 'Kategori', 'Event', 'Status', 'Tanggal Upload', 'Revisi Ke']);

        $i = 1;
        foreach ($rows as $r) {
            fputcsv($csv, [
                $i++,
                $r['judul'] ?? '',
                $r['nama_lengkap'] ?? '',
                $r['email'] ?? '',
                $r['nama_kategori'] ?? '',
                $r['event_title'] ?? '',
                $r['status'] ?? '',
                isset($r['tanggal_upload']) ? date('d/m/Y H:i', strtotime($r['tanggal_upload'])) : '',
                $r['revisi_ke'] ?? 0,
            ]);
        }

        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);

        $filename = 'abstrak_' . date('Ymd_His') . '.csv';
        return $this->response
            ->setHeader('Content-Type', 'text/csv')
            ->setHeader('Content-Disposition', 'attachment; filename="'.$filename.'"')
            ->setBody($content);
    }

    /**
     * STATISTICS JSON (/admin/abstrak/statistics)
     */
    public function statistics()
    {
        return $this->response->setJSON($this->abstrakModel->getStats());
    }

    /**
     * AJAX: Reviewer by Category
     * - View memanggil: /admin/reviewer/by-category/{idKategori}
     * - Kembalikan JSON: [{id_user, nama_lengkap, email}, ...]
     */
    public function getReviewersByCategory($idKategori)
    {
        $idKategori = (int)$idKategori;
        if (!$idKategori) {
            return $this->response->setJSON([]);
        }

        $list = $this->revKatModel->getReviewersByKategori($idKategori);
        return $this->response->setJSON($list);
    }
}
