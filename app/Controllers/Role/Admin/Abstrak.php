<?php

namespace App\Controllers\Role\Admin;

use App\Controllers\BaseController;
use App\Models\AbstrakModel;
use App\Models\ReviewModel;
use App\Models\ReviewerKategoriModel;

class Abstrak extends BaseController
{
    protected AbstrakModel $abstrakModel;
    protected ReviewModel $reviewModel;
    protected ReviewerKategoriModel $revKatModel;
    protected $db;
    protected $fpModel = null;

    private bool $autoAcceptOnAssign = false;

    public function __construct()
    {
        $this->abstrakModel = new AbstrakModel();
        $this->reviewModel  = new ReviewModel();
        $this->revKatModel  = new ReviewerKategoriModel();
        $this->db           = \Config\Database::connect();

        if (class_exists(\App\Models\FullPaperModel::class)) {
            $this->fpModel = new \App\Models\FullPaperModel();
        }
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

        $candidates = [
            FCPATH.$clean,
            WRITEPATH.$clean,
            ROOTPATH.$clean,
            FCPATH.'uploads/abstrak/'.basename($clean),
            WRITEPATH.'uploads/abstrak/'.basename($clean),
        ];
        foreach ($candidates as $p) if (is_file($p)) return $p;
        if (is_file($fname)) return $fname;
        return null;
    }

    private function reviewTable(): ?string
    {
        if ($this->db->tableExists('reviews')) return 'reviews';
        if ($this->db->tableExists('review'))  return 'review';
        return null;
    }

    private function hasCol(string $table, string $col): bool
    {
        $cols = array_map('strtolower', $this->db->getFieldNames($table) ?: []);
        return in_array(strtolower($col), $cols, true);
    }

    private function pickCol(string $table, array $cands): ?string
    {
        foreach ($cands as $c) if ($this->hasCol($table,$c)) return $c;
        return null;
    }

    private function reviewCols(string $rt): array
    {
        return [
            'pk'        => $this->pickCol($rt, ['id','id_review']) ?? 'id',
            'reviewer'  => $this->pickCol($rt, ['id_reviewer','reviewer_id','user_id']) ?? 'id_reviewer',
            'abstrakFk' => $this->pickCol($rt, ['id_abstrak']) ?? 'id_abstrak',
            'decision'  => $this->pickCol($rt, ['keputusan','decision','status']),
            'comment'   => $this->pickCol($rt, ['komentar','comment']),
            'ts'        => $this->pickCol($rt, ['tanggal_review','updated_at','created_at']),
            'task'      => $this->pickCol($rt, ['status_tugas','tugas_status','assignment_status','konfirmasi_status']),
            'reason'    => $this->pickCol($rt, ['alasan_tolak','alasan','decline_reason','reason']),
            'accAt'     => $this->pickCol($rt, ['accepted_at','confirmed_at','konfirmasi_at']),
            'decAt'     => $this->pickCol($rt, ['declined_at','rejected_at']),
            'type'      => $this->pickCol($rt, ['type','review_type']),
        ];
    }

    private function normTask(?string $v): string
    {
        $k = strtolower(trim((string)$v));
        if ($k === '') return 'pending';
        if (in_array($k, ['accept','accepted','ok','yes'], true)) return 'accepted';
        if (in_array($k, ['decline','declined','no','rejected_task'], true)) return 'declined';
        return in_array($k, ['requested','assigned','awaiting','waiting','new']) ? 'pending' : $k;
    }

    private function normDecision(?string $v): string
    {
        $k = strtolower(trim((string)$v));
        if (in_array($k, ['accepted','diterima','accept'], true)) return 'diterima';
        if (in_array($k, ['revisi','revision'], true))          return 'revisi';
        if (in_array($k, ['rejected','ditolak','reject'], true))return 'ditolak';
        if (in_array($k, ['sedang_direview','in_review','pending'], true))return 'sedang_direview';
        return 'pending';
    }

    private function countActiveHolders(int $idAbstrak): int
    {
        $rt = $this->reviewTable();
        if (!$rt) return 0;
        $R  = $this->reviewCols($rt);

        $b = $this->db->table($rt)->where($R['abstrakFk'], $idAbstrak);

        if ($R['task']) {
            $b->groupStart()
                ->where("{$R['task']} IS NULL", null, false)
                ->orWhereIn("LOWER({$R['task']})", ['pending','accepted','requested','assigned','awaiting','waiting','new'])
              ->groupEnd();
        }
        if ($R['decision']) {
            $b->groupStart()
                ->where("{$R['decision']} IS NULL", null, false)
                ->orWhereIn("LOWER({$R['decision']})", ['pending','sedang_direview',''])
              ->groupEnd();
        }
        return (int)$b->countAllResults();
    }

    private function fetchReviewsActive(int $idAbstrak): array
    {
        $rt = $this->reviewTable();
        if (!$rt) return [];
        $R  = $this->reviewCols($rt);

        $sel = [
            "r.{$R['pk']} AS id",
            "r.{$R['reviewer']} AS id_reviewer",
            ($R['decision'] ? "r.{$R['decision']} AS keputusan" : "'' AS keputusan"),
            ($R['comment']  ? "r.{$R['comment']}  AS komentar"  : "'' AS komentar"),
            ($R['ts']       ? "r.{$R['ts']}       AS tanggal_review" : "NULL AS tanggal_review"),
            ($R['task']     ? "r.{$R['task']}     AS tugas_status" : "'' AS tugas_status"),
            "u.nama_lengkap AS reviewer_name",
            "u.email        AS reviewer_email",
        ];

        $b = $this->db->table("$rt r")
            ->select(implode(', ', $sel), false)
            ->join('users u', "u.id_user = r.{$R['reviewer']}", 'left')
            ->where("r.{$R['abstrakFk']}", $idAbstrak);

        if ($R['task']) {
            $b->groupStart()
                 ->where("r.{$R['task']} IS NULL", null, false)
                 ->orWhereNotIn("LOWER(r.{$R['task']})", ['declined','rejected_task','decline','no'])
              ->groupEnd();
        }

        $rows = $b->orderBy($R['pk'],'DESC')->get()->getResultArray();
        foreach ($rows as &$r) {
            $r['tugas_status'] = $this->normTask($r['tugas_status'] ?? '');
            $r['keputusan']    = $this->normDecision($r['keputusan'] ?? '');
            $r['komentar']     = (string)($r['komentar'] ?? '');
        }
        unset($r);
        return $rows;
    }

    private function fetchReviewsDeclined(int $idAbstrak): array
    {
        $rt = $this->reviewTable();
        if (!$rt) return [];
        $R  = $this->reviewCols($rt);

        $sel = [
            "r.{$R['pk']} AS id",
            "r.{$R['reviewer']} AS id_reviewer",
            ($R['reason'] ? "r.{$R['reason']} AS alasan" : "'' AS alasan"),
            ($R['decAt']  ? "r.{$R['decAt']}  AS declined_at" : "NULL AS declined_at"),
            "u.nama_lengkap AS reviewer_name",
            "u.email        AS reviewer_email",
        ];

        $b = $this->db->table("$rt r")
            ->select(implode(', ', $sel), false)
            ->join('users u', "u.id_user = r.{$R['reviewer']}", 'left')
            ->where("r.{$R['abstrakFk']}", $idAbstrak);

        if ($R['task']) {
            $b->whereIn("LOWER(r.{$R['task']})", ['declined','rejected_task','decline','no']);
        } else {
            $b->where('0=1', null, false);
        }

        $rows = $b->orderBy($R['pk'],'DESC')->get()->getResultArray();
        foreach ($rows as &$r) $r['alasan'] = (string)($r['alasan'] ?? '');
        unset($r);
        return $rows;
    }

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
        } catch (\Throwable $e) {
            log_message('error', 'Admin Abstrak index error: '.$e->getMessage());
            return redirect()->back()->with('error','Terjadi kesalahan saat memuat data abstrak.');
        }
    }

    public function detail($id)
    {
        try {
            $id = (int)$id;
            $abstrak = $this->abstrakModel->getDetailWithRelations($id);
            if (!$abstrak) {
                return redirect()->to(site_url('admin/abstrak'))->with('error','Abstrak tidak ditemukan.');
            }

            $reviewsActive  = $this->fetchReviewsActive($id);
            $reviewsDecline = $this->fetchReviewsDeclined($id);

            $assigned = [];
            foreach ($reviewsActive as $r) {
                $rid = (int)($r['id_reviewer'] ?? 0);
                if (!$rid || isset($assigned[$rid])) continue;
                $assigned[$rid] = [
                    'id_user'     => $rid,
                    'nama'        => $r['reviewer_name'] ?? '-',
                    'email'       => $r['reviewer_email'] ?? '-',
                    'status'      => $r['keputusan'] ?? 'pending',
                    'task_status' => $r['tugas_status'] ?? 'pending',
                    'tanggal'     => $r['tanggal_review'] ?? null,
                ];
            }

            return view('role/admin/kelola_paper/abstrak_detail', [
                'abstrak'    => $abstrak,
                'reviews'    => $reviewsActive,
                'assigned'   => array_values($assigned),
                'declined'   => $reviewsDecline,
            ]);
        } catch (\Throwable $e) {
            log_message('error','Abstrak detail error: '.$e->getMessage());
            return redirect()->to(site_url('admin/abstrak'))->with('error','Terjadi kesalahan.');
        }
    }

    public function getReviewersByCategory($idKategori)
    {
        try {
            $idKategori = (int)$idKategori;

            if (method_exists($this->revKatModel, 'getByCategory')) {
                $rows = $this->revKatModel->getByCategory($idKategori);
            } else {
                $rows = $this->db->table('reviewer_kategori rk')
                    ->select('u.id_user, u.nama_lengkap, u.email')
                    ->join('users u', 'u.id_user = rk.id_reviewer')
                    ->where('rk.id_kategori', $idKategori)
                    ->orderBy('u.nama_lengkap', 'ASC')
                    ->get()->getResultArray();
            }

            return $this->response->setJSON([
                'success' => true,
                'data'    => array_map(fn($r) => [
                    'id_user' => (int)($r['id_user'] ?? 0),
                    'nama'    => $r['nama_lengkap'] ?? '-',
                    'email'   => $r['email'] ?? '',
                ], $rows ?? []),
            ]);
        } catch (\Throwable $e) {
            log_message('error','getReviewersByCategory error: '.$e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Gagal memuat reviewer.',
            ])->setStatusCode(500);
        }
    }

    public function assign($idAbstrak)
    {
        try {
            $idAbstrak  = (int)$idAbstrak;
            $idReviewer = (int)$this->request->getPost('id_reviewer');

            if (!$idReviewer) return redirect()->back()->with('error', 'Reviewer wajib dipilih.');

            $abstrak = $this->abstrakModel->find($idAbstrak);
            if (!$abstrak) return redirect()->to(site_url('admin/abstrak'))->with('error', 'Abstrak tidak ditemukan.');

            if (method_exists($this->revKatModel, 'isReviewerEligible')) {
                if (!$this->revKatModel->isReviewerEligible($idReviewer, (int)$abstrak['id_kategori'])) {
                    return redirect()->back()->with('error', 'Reviewer tidak sesuai kategori abstrak.');
                }
            }

            if ($this->countActiveHolders($idAbstrak) > 0) {
                return redirect()->back()->with('error', 'Masih ada reviewer aktif/menunggu konfirmasi.');
            }

            $rt = $this->reviewTable();
            if (!$rt) {
                return redirect()->back()->with('error', 'Tabel review tidak ditemukan.');
            }
            $R = $this->reviewCols($rt);

            $this->db->transStart();

            $payload = [
                $R['abstrakFk'] => $idAbstrak,
                $R['reviewer']  => $idReviewer,
            ];
            if ($R['decision'])  $payload[$R['decision']]  = 'pending';
            if ($R['comment'])   $payload[$R['comment']]   = '';
            if ($R['ts'])        $payload[$R['ts']]        = date('Y-m-d H:i:s');
            if ($R['task'])      $payload[$R['task']]      = $this->autoAcceptOnAssign ? 'accepted' : null;
            if ($this->autoAcceptOnAssign && $R['accAt'])  $payload[$R['accAt']]      = date('Y-m-d H:i:s');
            if ($R['type'])      $payload[$R['type']]      = 'abstrak';

            $ok = (bool)$this->db->table($rt)->insert($payload);

            if (!$ok) {
                $this->db->transRollback();
                return redirect()->back()->with('error', 'Gagal assign reviewer. Silakan coba lagi.');
            }

            $cur = strtolower($abstrak['status'] ?? 'menunggu');
            if (in_array($cur, ['menunggu','ditolak'], true)) {
                $this->abstrakModel->update($idAbstrak, ['status' => 'sedang_direview']);
            }

            $this->db->transComplete();
            if ($this->db->transStatus() === false) {
                return redirect()->back()->with('error', 'Gagal assign reviewer karena masalah database.');
            }

            return redirect()->to(site_url('admin/abstrak/detail/'.$idAbstrak))
                ->with('success', $this->autoAcceptOnAssign
                    ? 'Reviewer ditugaskan (langsung accepted).'
                    : 'Reviewer ditugaskan. Menunggu konfirmasi di dashboard reviewer.');

        } catch (\Throwable $e) {
            if (isset($this->db)) $this->db->transRollback();
            log_message('error', 'Assign reviewer error: '.$e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat assign reviewer.');
        }
    }

    public function updateStatus()
    {
        try {
            $idAbstrak = (int)$this->request->getPost('id_abstrak');
            $status    = (string)$this->request->getPost('status');
            $komentar  = trim((string)$this->request->getPost('komentar'));
            $allowed   = ['diterima','ditolak','revisi','sedang_direview','menunggu'];

            if (!$idAbstrak || !in_array($status, $allowed, true)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Status tidak valid.',
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

            if ($komentar !== '') {
                $rt = $this->reviewTable();
                if ($rt) {
                    $R = $this->reviewCols($rt);
                    $row = [
                        $R['abstrakFk'] => $idAbstrak,
                        $R['reviewer']  => (int)(session('id_user') ?: 0),
                    ];
                    if ($R['decision'])  $row[$R['decision']]  = $status;
                    if ($R['comment'])   $row[$R['comment']]   = $komentar;
                    if ($R['ts'])        $row[$R['ts']]        = date('Y-m-d H:i:s');
                    if ($R['type'])      $row[$R['type']]      = 'abstrak';
                    $this->db->table($rt)->insert($row);
                } else {
                    try {
                        $this->reviewModel->insert([
                            'id_abstrak'     => $idAbstrak,
                            'id_reviewer'    => (int)(session('id_user') ?: 0),
                            'keputusan'      => $status,
                            'komentar'       => $komentar,
                            'tanggal_review' => date('Y-m-d H:i:s'),
                        ], false);
                    } catch (\Throwable $e) { /* ignore */ }
                }
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

        } catch (\Throwable $e) {
            if (isset($this->db)) $this->db->transRollback();
            log_message('error','Update status error: '.$e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem.',
                csrf_token() => csrf_hash()
            ]);
        }
    }

    public function delete($id)
    {
        try {
            $id = (int)$id;
            $abstrak = $this->abstrakModel->find($id);
            if (!$abstrak) return redirect()->to(site_url('admin/abstrak'))->with('error','Abstrak tidak ditemukan.');

            $this->db->transStart();

            $rt = $this->reviewTable();
            if ($rt) $this->db->table($rt)->where('id_abstrak',$id)->delete();

            $filename = $abstrak['file_abstrak'] ?? '';
            if ($filename) {
                $paths = [
                    FCPATH.'uploads/abstrak/'.basename($filename),
                    WRITEPATH.'uploads/abstrak/'.basename($filename)
                ];
                foreach ($paths as $p) if (is_file($p)) @unlink($p);
            }

            $this->abstrakModel->delete($id);

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                return redirect()->to(site_url('admin/abstrak'))->with('error','Gagal menghapus abstrak.');
            }
            return redirect()->to(site_url('admin/abstrak'))->with('success','Abstrak berhasil dihapus.');
        } catch (\Throwable $e) {
            if (isset($this->db)) $this->db->transRollback();
            log_message('error','Delete abstrak error: '.$e->getMessage());
            return redirect()->to(site_url('admin/abstrak'))->with('error','Gagal menghapus abstrak.');
        }
    }

    public function downloadFile($id)
    {
        try {
            $id = (int)$id;
            $abstrak = $this->abstrakModel->find($id);
            if (!$abstrak || empty($abstrak['file_abstrak'])) {
                return redirect()->to(site_url('admin/abstrak'))->with('error','File tidak ditemukan.');
            }
            $path = $this->resolveAbstrakPath($abstrak);
            if (!$path) return redirect()->to(site_url('admin/abstrak'))->with('error','File tidak ada di server.');
            if ($this->isPublicUrl($path)) return redirect()->to($path);

            if (function_exists('ob_get_level')) while (ob_get_level() > 0) @ob_end_clean();
            @ini_set('display_errors','0');

            return $this->response->download($path, null)->setFileName(basename($path));
        } catch (\Throwable $e) {
            log_message('error','Download file error: '.$e->getMessage());
            return redirect()->back()->with('error','Gagal download file.');
        }
    }

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

    /** ================== NEW: Re-open / requeue dari sisi Admin ================== */
    public function reopen($idAbstrak)
    {
        $idAbstrak = (int)$idAbstrak;
        $idReviewer = (int)($this->request->getPost('id_reviewer') ?? 0); // opsional

        $rt = $this->reviewTable();
        if (!$rt) return redirect()->back()->with('error','Tabel review tidak ditemukan.');
        $R  = $this->reviewCols($rt);

        $this->db->transStart();

        $now = date('Y-m-d H:i:s');

        // target reviewers: 1 orang (jika dipilih) atau semua yang pernah pegang
        if ($idReviewer > 0) {
            $target = [ ['reviewer_id' => $idReviewer] ];
        } else {
            $target = $this->db->table($rt)
                ->select("{$R['reviewer']} AS reviewer_id", false)
                ->where($R['abstrakFk'], $idAbstrak)
                ->groupBy($R['reviewer'])
                ->get()->getResultArray();
        }

        foreach ($target as $r) {
            $rid = (int)($r['reviewer_id'] ?? 0);
            if ($rid <= 0) continue;

            // kalau sudah ada pending+accepted, skip
            $q = $this->db->table($rt)->where($R['abstrakFk'], $idAbstrak)->where($R['reviewer'], $rid);
            if ($R['decision']) $q->where($R['decision'], 'pending');
            if ($R['task'])     $q->whereIn("LOWER({$R['task']})", ['accepted','accept','ok','yes']);
            if ((int)$q->countAllResults() > 0) continue;

            $row = [
                $R['abstrakFk'] => $idAbstrak,
                $R['reviewer']  => $rid,
            ];
            if ($R['decision']) $row[$R['decision']] = 'pending';
            if ($R['ts'])       $row[$R['ts']]       = $now;
            if ($R['task'])     $row[$R['task']]     = 'accepted';
            if ($R['accAt'])    $row[$R['accAt']]    = $now;
            if ($R['reason'])   $row[$R['reason']]   = null;
            if ($R['decAt'])    $row[$R['decAt']]    = null;
            if ($R['type'])     $row[$R['type']]     = 'abstrak';

            $this->db->table($rt)->insert($row);
        }

        // pastikan status abstrak kembali ke sedang_direview
        $this->abstrakModel->update($idAbstrak, ['status' => 'sedang_direview']);

        $this->db->transComplete();

        return $this->db->transStatus()
            ? redirect()->back()->with('success','Review dibuka kembali. Tugas muncul lagi di dashboard reviewer.')
            : redirect()->back()->with('error','Gagal membuka kembali review.');
    }
}
