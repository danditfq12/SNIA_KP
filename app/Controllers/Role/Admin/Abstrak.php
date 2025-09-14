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
    protected $db;

    public function __construct()
    {
        $this->abstrakModel = new AbstrakModel();
        $this->reviewModel  = new ReviewModel();
        $this->revKatModel  = new ReviewerKategoriModel();
        // FIXED: Initialize database connection
        $this->db = \Config\Database::connect();
    }

    /**
     * LIST + KPI
     */
    public function index()
    {
        try {
            $stats = $this->abstrakModel->getStats();
            $abstraks = $this->abstrakModel->getAbstrakWithDetails();

            $data = [
                'total_abstrak'   => $stats['total'] ?? 0,
                'abstrak_pending' => $stats['menunggu'] ?? 0,
                'abstrak_diterima'=> $stats['diterima'] ?? 0,
                'abstrak_ditolak' => $stats['ditolak'] ?? 0,
                'abstraks'        => $abstraks,
                'reviewers'       => [],
            ];

            return view('role/admin/abstrak/index', $data);
        } catch (\Exception $e) {
            log_message('error', 'Admin Abstrak index error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memuat data abstrak.');
        }
    }

    /**
     * DETAIL
     */
    public function detail($id)
    {
        try {
            $abstrak = $this->abstrakModel->getDetailWithRelations((int)$id);
            if (!$abstrak) {
                return redirect()->to(site_url('admin/abstrak'))->with('error', 'Abstrak tidak ditemukan.');
            }

            $reviews = $this->reviewModel->getByAbstrakWithReviewer((int)$id);

            return view('role/admin/abstrak/detail', [
                'abstrak' => $abstrak,
                'reviews' => $reviews,
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Abstrak detail error: ' . $e->getMessage());
            return redirect()->to(site_url('admin/abstrak'))->with('error', 'Terjadi kesalahan.');
        }
    }

    /**
     * ASSIGN REVIEWER - FIXED dengan proper transaction handling
     */
    public function assign($idAbstrak)
    {
        try {
            $idAbstrak  = (int)$idAbstrak;
            $idReviewer = (int)$this->request->getPost('id_reviewer');

            if (!$idReviewer) {
                return redirect()->back()->with('error', 'Reviewer wajib dipilih.');
            }

            $abstrak = $this->abstrakModel->find($idAbstrak);
            if (!$abstrak) {
                return redirect()->to(site_url('admin/abstrak'))->with('error', 'Abstrak tidak ditemukan.');
            }

            $eligible = $this->revKatModel->isReviewerEligible($idReviewer, (int)$abstrak['id_kategori']);
            if (!$eligible) {
                return redirect()->back()->with('error', 'Reviewer tidak sesuai kategori abstrak.');
            }

            if ($this->reviewModel->hasPendingReview($idAbstrak)) {
                return redirect()->back()->with('error', 'Abstrak ini sudah memiliki assignment reviewer yang pending.');
            }

            // FIXED: Use proper transaction methods
            $this->db->transStart();

            // Assign reviewer
            $ok = $this->reviewModel->assignReviewer($idAbstrak, $idReviewer);
            if (!$ok) {
                $this->db->transRollback();
                log_message('error', 'Failed to assign reviewer: ' . json_encode($this->reviewModel->errors()));
                return redirect()->back()->with('error', 'Gagal assign reviewer. Silakan coba lagi.');
            }

            // Update status abstrak
            if (($abstrak['status'] ?? 'menunggu') === 'menunggu') {
                $this->abstrakModel->update($idAbstrak, ['status' => 'sedang_direview']);
            }

            $this->db->transComplete();

            // Check transaction status
            if ($this->db->transStatus() === FALSE) {
                return redirect()->back()->with('error', 'Gagal assign reviewer karena masalah database.');
            }

            return redirect()->to(site_url('admin/abstrak'))->with('success', 'Reviewer berhasil ditugaskan.');

        } catch (\Exception $e) {
            if (isset($this->db)) {
                $this->db->transRollback();
            }
            log_message('error', 'Assign reviewer error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat assign reviewer: ' . $e->getMessage());
        }
    }

    /**
     * UPDATE STATUS - FIXED transaction handling
     */
    public function updateStatus()
    {
        try {
            $idAbstrak = (int)$this->request->getPost('id_abstrak');
            $status    = (string)$this->request->getPost('status');
            $komentar  = trim((string)$this->request->getPost('komentar'));

            $allowed = ['menunggu', 'sedang_direview', 'diterima', 'ditolak', 'revisi'];
            if (!$idAbstrak || !in_array($status, $allowed, true)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Data tidak valid.',
                    csrf_token() => csrf_hash()
                ]);
            }

            $exists = $this->abstrakModel->find($idAbstrak);
            if (!$exists) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Abstrak tidak ditemukan.',
                    csrf_token() => csrf_hash()
                ]);
            }

            // FIXED: Use proper transaction methods
            $this->db->transStart();

            // Update status abstrak
            $this->abstrakModel->update($idAbstrak, ['status' => $status]);

            // Log komentar admin jika ada dan tidak kosong
            if (!empty($komentar)) {
                $reviewData = [
                    'id_abstrak'     => $idAbstrak,
                    'id_reviewer'    => session('id_user') ?: null,
                    'keputusan'      => $status,
                    'komentar'       => $komentar,
                    'tanggal_review' => date('Y-m-d H:i:s'),
                ];
                $this->reviewModel->insert($reviewData, false);
            }

            $this->db->transComplete();

            if ($this->db->transStatus() === FALSE) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Gagal menyimpan perubahan.',
                    csrf_token() => csrf_hash()
                ]);
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Status abstrak berhasil diperbarui.',
                csrf_token() => csrf_hash()
            ]);

        } catch (\Exception $e) {
            if (isset($this->db)) {
                $this->db->transRollback();
            }
            log_message('error', 'Update status error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem.',
                csrf_token() => csrf_hash()
            ]);
        }
    }

    /**
     * DELETE - FIXED transaction handling
     */
    public function delete($id)
    {
        try {
            $id = (int)$id;
            $abstrak = $this->abstrakModel->find($id);
            if (!$abstrak) {
                return redirect()->to(site_url('admin/abstrak'))->with('error', 'Abstrak tidak ditemukan.');
            }

            // FIXED: Use proper transaction methods
            $this->db->transStart();

            // Hapus review terkait
            $this->reviewModel->where('id_abstrak', $id)->delete();

            // Hapus file fisik
            $filename = $abstrak['file_abstrak'] ?? '';
            if ($filename) {
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

            $this->db->transComplete();

            if ($this->db->transStatus() === FALSE) {
                return redirect()->to(site_url('admin/abstrak'))->with('error', 'Gagal menghapus abstrak.');
            }

            return redirect()->to(site_url('admin/abstrak'))->with('success', 'Abstrak berhasil dihapus.');

        } catch (\Exception $e) {
            if (isset($this->db)) {
                $this->db->transRollback();
            }
            log_message('error', 'Delete abstrak error: ' . $e->getMessage());
            return redirect()->to(site_url('admin/abstrak'))->with('error', 'Gagal menghapus abstrak.');
        }
    }

    /**
     * BULK UPDATE STATUS
     */
    public function bulkUpdateStatus()
    {
        try {
            $ids    = (array)$this->request->getPost('ids');
            $status = (string)$this->request->getPost('status');

            $allowed = ['menunggu', 'sedang_direview', 'diterima', 'ditolak', 'revisi'];
            if (!$ids || !in_array($status, $allowed, true)) {
                return $this->response->setJSON([
                    'success' => false, 
                    'message' => 'Input tidak valid.',
                    csrf_token() => csrf_hash()
                ]);
            }

            // Use transaction for bulk update
            $this->db->transStart();

            foreach ($ids as $id) {
                $this->abstrakModel->update((int)$id, ['status' => $status]);
            }

            $this->db->transComplete();

            if ($this->db->transStatus() === FALSE) {
                return $this->response->setJSON([
                    'success' => false, 
                    'message' => 'Gagal melakukan update massal.',
                    csrf_token() => csrf_hash()
                ]);
            }

            return $this->response->setJSON([
                'success' => true, 
                'message' => 'Status berhasil diupdate massal.',
                csrf_token() => csrf_hash()
            ]);

        } catch (\Exception $e) {
            if (isset($this->db)) {
                $this->db->transRollback();
            }
            log_message('error', 'Bulk update error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false, 
                'message' => 'Terjadi kesalahan.',
                csrf_token() => csrf_hash()
            ]);
        }
    }

    /**
     * DOWNLOAD FILE
     */
    public function downloadFile($id)
    {
        try {
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

        } catch (\Exception $e) {
            log_message('error', 'Download file error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal download file.');
        }
    }

    /**
     * EXPORT CSV
     */
    public function export()
    {
        try {
            $rows = $this->abstrakModel->getAbstrakWithDetails();

            $csv = fopen('php://temp', 'w+');
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

        } catch (\Exception $e) {
            log_message('error', 'Export abstrak error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal export data.');
        }
    }

    /**
     * STATISTICS JSON
     */
    public function statistics()
    {
        try {
            return $this->response->setJSON($this->abstrakModel->getStats());
        } catch (\Exception $e) {
            log_message('error', 'Statistics error: ' . $e->getMessage());
            return $this->response->setJSON(['error' => 'Gagal memuat statistik']);
        }
    }

    /**
     * AJAX: Get Reviewers by Category
     */
    public function getReviewersByCategory($idKategori)
    {
        try {
            $idKategori = (int)$idKategori;
            if (!$idKategori) {
                return $this->response->setJSON([]);
            }

            $list = $this->revKatModel->getReviewersByKategori($idKategori);
            return $this->response->setJSON($list);

        } catch (\Exception $e) {
            log_message('error', 'Get reviewers by category error: ' . $e->getMessage());
            return $this->response->setJSON([]);
        }
    }
}