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
        $this->db           = \Config\Database::connect();
    }

    private function isPublicUrl(string $path): bool
    {
        return (bool) preg_match('~^https?://~i', $path);
    }

    private function resolveAbstrakPath(array $row): ?string
    {
        $fname = trim((string)($row['file_abstrak'] ?? ''));
        if ($fname === '') return null;

        if ($this->isPublicUrl($fname)) return $fname;

        $clean = ltrim(str_replace('\\','/',$fname), '/');

        $relCandidates = [
            FCPATH    . $clean,
            WRITEPATH . $clean,
            ROOTPATH  . $clean,
        ];
        foreach ($relCandidates as $p) if (is_file($p)) return $p;

        $just = basename($clean);
        $nameCandidates = [
            FCPATH    . 'uploads/abstrak/' . $just,
            WRITEPATH . 'uploads/abstrak/' . $just,
        ];
        foreach ($nameCandidates as $p) if (is_file($p)) return $p;

        if (is_file($fname)) return $fname;
        return null;
    }

    /** LIST + KPI */
    public function index()
    {
        try {
            $stats    = $this->abstrakModel->getStats();
            $abstraks = $this->abstrakModel->getAbstrakWithDetails();

            return view('role/admin/abstrak/index', [
                'total_abstrak'    => $stats['total'] ?? 0,
                'abstrak_pending'  => $stats['menunggu'] ?? 0,
                'abstrak_diterima' => $stats['diterima'] ?? 0,
                'abstrak_ditolak'  => $stats['ditolak'] ?? 0,
                'abstraks'         => $abstraks,
                'reviewers'        => [],
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Admin Abstrak index error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memuat data abstrak.');
        }
    }

    /** DETAIL */
    public function detail($id)
    {
        try {
            $abstrak = $this->abstrakModel->getDetailWithRelations((int)$id);
            if (!$abstrak) {
                return redirect()->to(site_url('admin/abstrak'))->with('error', 'Abstrak tidak ditemukan.');
            }

            // Ambil seluruh record review/assignment (termasuk pending)
            $reviews = $this->reviewModel->getByAbstrakWithReviewer((int)$id);

            // Kumpulkan info reviewer yang sudah ditugaskan (unique)
            $assigned = [];
            foreach ($reviews as $r) {
                $rid = (int)($r['id_reviewer'] ?? 0);
                if (!$rid) continue;
                $key = $rid;
                if (!isset($assigned[$key])) {
                    $assigned[$key] = [
                        'id_user' => $rid,
                        'nama'    => $r['reviewer_name'] ?? '-',
                        'email'   => $r['reviewer_email'] ?? '-',
                        'status'  => strtolower($r['keputusan'] ?? 'pending'),
                        'tanggal' => $r['tanggal_review'] ?? null,
                    ];
                } else {
                    // jika ada review terbaru dengan keputusan, perbarui statusnya
                    $st = strtolower((string)($r['keputusan'] ?? ''));
                    if ($st) $assigned[$key]['status'] = $st;
                    if (!empty($r['tanggal_review'])) $assigned[$key]['tanggal'] = $r['tanggal_review'];
                }
            }

            return view('role/admin/abstrak/detail', [
                'abstrak'   => $abstrak,
                'reviews'   => $reviews,
                'assigned'  => array_values($assigned),
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Abstrak detail error: ' . $e->getMessage());
            return redirect()->to(site_url('admin/abstrak'))->with('error', 'Terjadi kesalahan.');
        }
    }

    /** ===== NEW: GET reviewers by category → JSON (dipakai modal) ===== */
    public function getReviewersByCategory($idKategori)
    {
        try {
            $idKategori = (int)$idKategori;

            // Jika model punya method khusus, gunakan.
            if (method_exists($this->revKatModel, 'getByCategory')) {
                $rows = $this->revKatModel->getByCategory($idKategori);
            } else {
                // Fallback query manual
                $builder = $this->db->table('reviewer_kategori rk')
                    ->select('u.id_user, u.nama_lengkap, u.email')
                    ->join('users u', 'u.id_user = rk.id_reviewer')
                    ->where('rk.id_kategori', $idKategori)
                    ->orderBy('u.nama_lengkap', 'ASC');
                // opsional: hanya reviewer aktif
                if ($this->db->fieldExists('status', 'users')) {
                    $builder->groupStart()
                            ->where('u.status', 'active')
                            ->orWhere('u.status', 'aktif')
                            ->groupEnd();
                }
                $rows = $builder->get()->getResultArray();
            }

            return $this->response->setJSON([
                'success' => true,
                'data'    => array_map(fn($r) => [
                    'id_user' => (int)$r['id_user'],
                    'nama'    => $r['nama_lengkap'] ?? '-',
                    'email'   => $r['email'] ?? '',
                ], $rows ?? []),
            ]);
        } catch (\Exception $e) {
            log_message('error', 'getReviewersByCategory error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Gagal memuat reviewer.',
            ])->setStatusCode(500);
        }
    }

    /** ASSIGN REVIEWER */
    public function assign($idAbstrak)
    {
        try {
            $idAbstrak  = (int)$idAbstrak;
            $idReviewer = (int)$this->request->getPost('id_reviewer');

            if (!$idReviewer) return redirect()->back()->with('error', 'Reviewer wajib dipilih.');

            $abstrak = $this->abstrakModel->find($idAbstrak);
            if (!$abstrak) return redirect()->to(site_url('admin/abstrak'))->with('error', 'Abstrak tidak ditemukan.');

            $eligible = $this->revKatModel->isReviewerEligible($idReviewer, (int)$abstrak['id_kategori']);
            if (!$eligible) return redirect()->back()->with('error', 'Reviewer tidak sesuai kategori abstrak.');

            if ($this->reviewModel->hasPendingReview($idAbstrak)) {
                return redirect()->back()->with('error', 'Abstrak ini sudah memiliki assignment reviewer yang pending.');
            }

            $this->db->transStart();

            $ok = $this->reviewModel->assignReviewer($idAbstrak, $idReviewer);
            if (!$ok) {
                $this->db->transRollback();
                log_message('error', 'Failed to assign reviewer: ' . json_encode($this->reviewModel->errors()));
                return redirect()->back()->with('error', 'Gagal assign reviewer. Silakan coba lagi.');
            }

            if (($abstrak['status'] ?? 'menunggu') === 'menunggu') {
                $this->abstrakModel->update($idAbstrak, ['status' => 'sedang_direview']);
            }

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                return redirect()->back()->with('error', 'Gagal assign reviewer karena masalah database.');
            }

            return redirect()->to(site_url('admin/abstrak/detail/'.$idAbstrak))
                ->with('success', 'Reviewer berhasil ditugaskan.');

        } catch (\Exception $e) {
            if (isset($this->db)) $this->db->transRollback();
            log_message('error', 'Assign reviewer error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat assign reviewer: ' . $e->getMessage());
        }
    }

    /** UPDATE STATUS */
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

            $this->db->transStart();

            $this->abstrakModel->update($idAbstrak, ['status' => $status]);

            if (!empty($komentar)) {
                $this->reviewModel->insert([
                    'id_abstrak'     => $idAbstrak,
                    'id_reviewer'    => session('id_user') ?: null,
                    'keputusan'      => $status,
                    'komentar'       => $komentar,
                    'tanggal_review' => date('Y-m-d H:i:s'),
                ], false);
            }

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
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
            if (isset($this->db)) $this->db->transRollback();
            log_message('error', 'Update status error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem.',
                csrf_token() => csrf_hash()
            ]);
        }
    }

    /** DELETE */
    public function delete($id)
    {
        try {
            $id = (int)$id;
            $abstrak = $this->abstrakModel->find($id);
            if (!$abstrak) {
                return redirect()->to(site_url('admin/abstrak'))->with('error', 'Abstrak tidak ditemukan.');
            }

            $this->db->transStart();

            $this->reviewModel->where('id_abstrak', $id)->delete();

            $filename = $abstrak['file_abstrak'] ?? '';
            if ($filename) {
                $paths = [
                    FCPATH . 'uploads/abstrak/' . $filename,
                    WRITEPATH . 'uploads/abstrak/' . $filename,
                ];
                foreach ($paths as $p) if (is_file($p)) @unlink($p);
            }

            $this->abstrakModel->delete($id);

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                return redirect()->to(site_url('admin/abstrak'))->with('error', 'Gagal menghapus abstrak.');
            }

            return redirect()->to(site_url('admin/abstrak'))->with('success', 'Abstrak berhasil dihapus.');

        } catch (\Exception $e) {
            if (isset($this->db)) $this->db->transRollback();
            log_message('error', 'Delete abstrak error: ' . $e->getMessage());
            return redirect()->to(site_url('admin/abstrak'))->with('error', 'Gagal menghapus abstrak.');
        }
    }

    /** DOWNLOAD (attachment) */
    public function downloadFile($id)
    {
        try {
            $id = (int)$id;
            $abstrak = $this->abstrakModel->find($id);
            if (!$abstrak || empty($abstrak['file_abstrak'])) {
                return redirect()->to(site_url('admin/abstrak'))->with('error', 'File tidak ditemukan.');
            }

            $path = $this->resolveAbstrakPath($abstrak);
            if (!$path) return redirect()->to(site_url('admin/abstrak'))->with('error', 'File tidak ada di server.');

            if ($this->isPublicUrl($path)) return redirect()->to($path);

            if (function_exists('ob_get_level')) while (ob_get_level() > 0) @ob_end_clean();
            @ini_set('display_errors','0');

            return $this->response->download($path, null)->setFileName(basename($path));

        } catch (\Exception $e) {
            log_message('error', 'Download file error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal download file.');
        }
    }

    /** Preview inline (iframe) */
    public function view($id)
    {
        $id = (int)$id;
        $row = $this->abstrakModel->find($id);
        if (!$row) {
            return $this->response->setContentType('text/html', 'utf-8')
                ->setBody('<div style="padding:12px;font-family:system-ui">Abstrak tidak ditemukan.</div>');
        }

        $path = $this->resolveAbstrakPath($row);
        if (!$path) {
            return $this->response->setContentType('text/html', 'utf-8')
                ->setBody('<div style="padding:12px;font-family:system-ui">File abstrak tidak ditemukan.</div>');
        }

        if (function_exists('ob_get_level')) while (ob_get_level() > 0) @ob_end_clean();
        @ini_set('display_errors','0');

        $this->response
            ->setHeader('X-Frame-Options','SAMEORIGIN')
            ->setHeader('Content-Security-Policy',"frame-ancestors 'self'")
            ->setHeader('X-Content-Type-Options','nosniff');

        if ($this->isPublicUrl($path)) {
            $ctx = stream_context_create(['http'=>['follow_location'=>1,'timeout'=>20]]);
            $binary = @file_get_contents($path,false,$ctx);
            if ($binary === false) return $this->response->setStatusCode(502)->setBody('Gagal mengambil file eksternal.');
        } else {
            if (!is_readable($path)) {
                return $this->response->setContentType('text/html','utf-8')
                    ->setBody('<div style="padding:12px;font-family:system-ui">File tidak dapat dibaca.</div>');
            }
            $binary = @file_get_contents($path);
            if ($binary === false) return $this->response->setStatusCode(500)->setBody('Gagal membaca file.');
        }

        return $this->response
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition','inline; filename="abstrak-'.$id.'.pdf"')
            ->setHeader('Cache-Control','private, max-age=0, must-revalidate')
            ->setBody($binary);
    }

    /** Endpoint blob untuk di-fetch() */
    public function blob($id)
    {
        $id = (int)$id;
        $row = $this->abstrakModel->find($id);
        if (!$row) return $this->response->setStatusCode(404)->setBody('Not found');

        $path = $this->resolveAbstrakPath($row);
        if (!$path) return $this->response->setStatusCode(404)->setBody('File not found');

        if (function_exists('ob_get_level')) while (ob_get_level() > 0) @ob_end_clean();
        @ini_set('display_errors','0');

        $this->response
            ->setHeader('X-Frame-Options','SAMEORIGIN')
            ->setHeader('Content-Security-Policy',"frame-ancestors 'self'")
            ->setHeader('X-Content-Type-Options','nosniff')
            ->setHeader('Cache-Control','no-store, no-cache, must-revalidate, max-age=0')
            ->setHeader('Pragma','no-cache')
            ->setHeader('Expires','Sat, 01 Jan 2000 00:00:00 GMT')
            ->setHeader('X-Accel-Buffering','no');

        if ($this->isPublicUrl($path)) {
            $ctx = stream_context_create(['http'=>['follow_location'=>1,'timeout'=>20]]);
            $binary = @file_get_contents($path,false,$ctx);
            if ($binary === false) return $this->response->setStatusCode(502)->setBody('Bad gateway');
        } else {
            if (!is_readable($path)) return $this->response->setStatusCode(404)->setBody('NF');
            $binary = @file_get_contents($path);
            if ($binary === false) return $this->response->setStatusCode(500)->setBody('IO error');
        }

        return $this->response
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition','inline; filename="blob.pdf"')
            ->setBody($binary);
    }
}