<?php
namespace App\Controllers\Role\Admin;

use App\Controllers\BaseController;
use App\Models\ReviewerKategoriModel;

class FullPaper extends BaseController
{
    protected $db;
    protected $revKatModel;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        if (class_exists(\App\Models\ReviewerKategoriModel::class)) {
            $this->revKatModel = new ReviewerKategoriModel();
        }
    }

    /* ========================= Utilities ========================= */

    private function tableName(): string
    {
        if ($this->db->tableExists('submissions')) return 'submissions';
        if ($this->db->tableExists('abstrak'))     return 'abstrak';
        throw new \RuntimeException('Tabel submissions/abstrak tidak ditemukan.');
    }

    private function fields(string $table): array
    {
        return $this->db->getFieldNames($table) ?: [];
    }

    private function columnExists(string $table, string $col): bool
    {
        return in_array($col, $this->fields($table), true);
    }

    private function primaryKey(string $table): string
    {
        foreach (['id','id_submission','id_abstrak'] as $cand) {
            if ($this->columnExists($table, $cand)) return $cand;
        }
        return 'id';
    }

    private function resolveColumns(string $table): array
    {
        $pk = $this->primaryKey($table);
        $titleCand = ['title','judul','judul_paper','judul_penelitian','judul_abstrak','nama'];
        $eventCand = ['event_id','id_event','events_id'];
        $userCand  = ['id_user','user_id','id_presenter'];

        $titleCol = null; foreach ($titleCand as $c) if ($this->columnExists($table,$c)) { $titleCol = $c; break; }
        $eventCol = null; foreach ($eventCand as $c) if ($this->columnExists($table,$c)) { $eventCol = $c; break; }
        $userCol  = null; foreach ($userCand  as $c) if ($this->columnExists($table,$c)) { $userCol  = $c; break; }

        $catCand = ['id_kategori','kategori_id','id_kategori_abstrak'];
        $catCol = null; foreach ($catCand as $c) if ($this->columnExists($table, $c)) { $catCol = $c; break; }

        return ['pk'=>$pk,'title'=>$titleCol ?? $pk,'event_id'=>$eventCol,'user_id'=>$userCol,'kategori_id'=>$catCol];
    }

    private function findSubmission(int $id): ?array
    {
        $table = $this->tableName();
        $pk    = $this->primaryKey($table);
        $row   = $this->db->table($table)->where($pk, $id)->get()->getRowArray();
        return $row ?: null;
    }

    private function isPublicUrl(string $path): bool
    {
        return (bool)preg_match('~^https?://~i', $path);
    }

    private function resolveFullPaperPath(array $submission): ?string
    {
        $fname = trim((string)($submission['full_paper_path'] ?? ''));
        if ($fname === '') return null;
        if ($this->isPublicUrl($fname)) return $fname;

        $clean = ltrim(str_replace('\\','/',$fname), '/');
        $candidates = [
            FCPATH   . $clean,
            ROOTPATH . $clean,
            WRITEPATH. $clean,
            WRITEPATH . 'uploads/fullpaper/' . basename($clean),
            FCPATH    . 'uploads/fullpaper/' . basename($clean),
        ];
        foreach ($candidates as $p) if (is_file($p)) return $p;
        if (is_file($fname)) return $fname;
        return null;
    }

    private function resolveReviewerSource(): array
    {
        $table = $this->db->tableExists('reviewers') ? 'reviewers'
              : ($this->db->tableExists('users') ? 'users' : null);
        if (!$table) return ['table'=>null,'id'=>null,'name'=>null,'email'=>null];

        $fields = array_flip($this->db->getFieldNames($table) ?: []);
        $idCol   = null; foreach (['id','id_user','user_id','reviewer_id'] as $c) if (isset($fields[$c])) { $idCol = $c; break; }
        $nameCol = null; foreach (['nama_lengkap','nama','name','full_name','username'] as $c) if (isset($fields[$c])) { $nameCol = $c; break; }
        $emailCol= null; foreach (['email','user_email','mail'] as $c) if (isset($fields[$c])) { $emailCol = $c; break; }

        return ['table'=>$table,'id'=>$idCol,'name'=>$nameCol,'email'=>$emailCol];
    }

    private function getAllReviewers(): array
    {
        $src = $this->resolveReviewerSource();
        if (!$src['table'] || !$src['id']) return [];
        $b = $this->db->table($src['table']);
        $sel = ["{$src['table']}.{$src['id']} AS id"];
        if ($src['name'])  $sel[] = "{$src['table']}.{$src['name']} AS name";
        if ($src['email']) $sel[] = "{$src['table']}.{$src['email']} AS email";
        $b->select(implode(', ', $sel));
        if ($src['table'] === 'users' && in_array('role',$this->db->getFieldNames('users'),true)) {
            $b->where('role','reviewer');
        }
        if ($src['name']) $b->orderBy($src['name'],'ASC');
        return $b->get()->getResultArray();
    }

    private function resolveCategoryId(array $submission, array $cols): ?int
    {
        if (!empty($cols['kategori_id']) && !empty($submission[$cols['kategori_id']])) {
            return (int)$submission[$cols['kategori_id']];
        }
        if (!$this->db->tableExists('abstrak') || !$cols['event_id'] || !$cols['user_id']) return null;
        if (empty($submission[$cols['event_id']]) || empty($submission[$cols['user_id']])) return null;

        $abs = $this->db->table('abstrak')
            ->select('id_abstrak, id_kategori')
            ->where('id_user', (int)$submission[$cols['user_id']])
            ->where('event_id', (int)$submission[$cols['event_id']])
            ->orderBy('tanggal_upload','DESC')
            ->get()->getRowArray();

        return $abs && !empty($abs['id_kategori']) ? (int)$abs['id_kategori'] : null;
    }

    /** Ambil ID reviewer yang sudah ditugaskan di ABSTRAK (untuk informasi). */
    private function getAbstractAssignedReviewerIds(?int $userId, ?int $eventId): array
    {
        if (!$userId || !$eventId) return [];
        if (!$this->db->tableExists('abstrak')) return [];
        $abs = $this->db->table('abstrak')
            ->select('id_abstrak')
            ->where('id_user', (int)$userId)
            ->where('event_id', (int)$eventId)
            ->orderBy('tanggal_upload','DESC')
            ->get()->getRowArray();
        if (!$abs) return [];
        $idAbstrak = (int)$abs['id_abstrak'];

        $ids = [];
        foreach (['abstrak_reviewers','abstrak_reviewer','reviewer_abstrak'] as $t) {
            if ($this->db->tableExists($t)) {
                $cols = $this->db->getFieldNames($t);
                $colAbs = in_array('id_abstrak',$cols,true) ? 'id_abstrak' : (in_array('abstrak_id',$cols,true) ? 'abstrak_id' : null);
                $colRev = in_array('id_reviewer',$cols,true) ? 'id_reviewer' : (in_array('reviewer_id',$cols,true) ? 'reviewer_id' : null);
                if ($colAbs && $colRev) {
                    $rows = $this->db->table($t)->select($colRev.' AS reviewer_id')->where($colAbs, $idAbstrak)->get()->getResultArray();
                    foreach ($rows as $r) $ids[] = (int)$r['reviewer_id'];
                }
            }
        }
        foreach (['reviews','abstrak_reviews','review'] as $t) {
            if ($this->db->tableExists($t)) {
                $cols = $this->db->getFieldNames($t);
                $colAbs = in_array('id_abstrak',$cols,true) ? 'id_abstrak' : (in_array('abstrak_id',$cols,true) ? 'abstrak_id' : null);
                $colRev = in_array('id_reviewer',$cols,true) ? 'id_reviewer' : (in_array('reviewer_id',$cols,true) ? 'reviewer_id' : null);
                if ($colAbs && $colRev) {
                    $rows = $this->db->table($t)->select($colRev.' AS reviewer_id')->where($colAbs, $idAbstrak)->get()->getResultArray();
                    foreach ($rows as $r) $ids[] = (int)$r['reviewer_id'];
                }
            }
        }
        return array_values(array_unique(array_filter($ids)));
    }

    private function getReviewersByCategory(?int $kategoriId, array $excludeIds = []): array
    {
        $list = $kategoriId ? $this->getReviewersByKategoriSmart($kategoriId) : $this->getAllReviewers();
        if (!$excludeIds) return $list;
        $ex = array_flip(array_map('intval', $excludeIds));
        return array_values(array_filter($list, fn($r)=> !isset($ex[(int)$r['id']])));
    }

    private function getReviewersByKategoriSmart(?int $kategoriId): array
    {
        if (!$kategoriId) return $this->getAllReviewers();

        if ($this->revKatModel && method_exists($this->revKatModel, 'getReviewersByKategori')) {
            $list = $this->revKatModel->getReviewersByKategori($kategoriId);
            return array_map(function($r){
                return [
                    'id'    => (int)($r['id'] ?? $r['id_user'] ?? $r['user_id'] ?? 0),
                    'name'  => $r['nama_lengkap'] ?? $r['name'] ?? $r['username'] ?? '-',
                    'email' => $r['email'] ?? ($r['mail'] ?? null),
                ];
            }, $list ?: []);
        }

        $src = $this->resolveReviewerSource();
        if (!$src['table'] || !$src['id']) return [];

        $mapTable = null;
        foreach (['reviewer_kategori','reviewers_kategori','reviewer_categories','reviewer_category'] as $cand) {
            if ($this->db->tableExists($cand)) { $mapTable = $cand; break; }
        }
        if (!$mapTable) return $this->getAllReviewers();

        $mapReviewerCol = null;
        foreach (['id_reviewer','reviewer_id','id_user','user_id'] as $c) {
            if (in_array($c, $this->db->getFieldNames($mapTable), true)) { $mapReviewerCol = $c; break; }
        }
        $mapKategoriCol = null;
        foreach (['id_kategori','kategori_id','id_kategori_abstrak'] as $c) {
            if (in_array($c, $this->db->getFieldNames($mapTable), true)) { $mapKategoriCol = $c; break; }
        }
        if (!$mapReviewerCol || !$mapKategoriCol) return $this->getAllReviewers();

        $nameSel  = $src['name']  ? "{$src['table']}.{$src['name']}"  : "NULL";
        $emailSel = $src['email'] ? "{$src['table']}.{$src['email']}" : "NULL";

        return $this->db->table($mapTable.' rk')
            ->select("{$src['table']}.{$src['id']} AS id, {$nameSel} AS name, {$emailSel} AS email")
            ->join($src['table'], "{$src['table']}.{$src['id']} = rk.{$mapReviewerCol}", 'inner')
            ->where("rk.{$mapKategoriCol}", $kategoriId)
            ->groupBy("{$src['table']}.{$src['id']}, {$nameSel}, {$emailSel}")
            ->orderBy($src['name'] ? $nameSel : "{$src['table']}.{$src['id']}", 'ASC')
            ->get()->getResultArray();
    }

    private function getAssignedReviewers(int $submissionId): array
    {
        if (!$this->db->tableExists('fullpaper_reviewers')) return [];
        $src = $this->resolveReviewerSource();
        $nameCol  = $src['name']  ? "{$src['table']}.{$src['name']}"  : "NULL";
        $emailCol = $src['email'] ? "{$src['table']}.{$src['email']}" : "NULL";

        $hasReviews = $this->db->tableExists('fullpaper_reviews');

        if ($hasReviews) {
            $statusExpr = "(
                SELECT LOWER(fr2.keputusan)
                FROM fullpaper_reviews fr2
                WHERE fr2.submission_id = fr.submission_id AND fr2.reviewer_id = fr.reviewer_id
                ORDER BY fr2.tanggal_review DESC, fr2.id DESC
                LIMIT 1
            )";
            $tanggalExpr = "(
                SELECT fr3.tanggal_review
                FROM fullpaper_reviews fr3
                WHERE fr3.submission_id = fr.submission_id AND fr3.reviewer_id = fr.reviewer_id
                ORDER BY fr3.tanggal_review DESC, fr3.id DESC
                LIMIT 1
            )";
            return $this->db->table('fullpaper_reviewers fr')
                ->select("fr.id, fr.reviewer_id, fr.assigned_at, {$emailCol} AS email, {$nameCol} AS name, {$statusExpr} AS status, {$tanggalExpr} AS status_at")
                ->join($src['table'], "{$src['table']}.{$src['id']} = fr.reviewer_id", 'left')
                ->where('fr.submission_id', $submissionId)
                ->orderBy('fr.id', 'ASC')
                ->get()->getResultArray();
        }

        return $this->db->table('fullpaper_reviewers fr')
            ->select("fr.id, fr.reviewer_id, fr.assigned_at, {$emailCol} AS email, {$nameCol} AS name, NULL::text AS status, NULL::timestamp AS status_at")
            ->join($src['table'], "{$src['table']}.{$src['id']} = fr.reviewer_id", 'left')
            ->where('fr.submission_id', $submissionId)
            ->orderBy('fr.id', 'ASC')
            ->get()->getResultArray();
    }

    private function getFullpaperReviews(int $submissionId): array
    {
        if (!$this->db->tableExists('fullpaper_reviews')) return [];
        $src = $this->resolveReviewerSource();
        $nameCol  = $src['name']  ? "{$src['table']}.{$src['name']}"  : "NULL";
        $emailCol = $src['email'] ? "{$src['table']}.{$src['email']}" : "NULL";

        return $this->db->table('fullpaper_reviews fr')
            ->select("fr.*, {$nameCol} AS reviewer_name, {$emailCol} AS reviewer_email")
            ->join($src['table'], "{$src['table']}.{$src['id']} = fr.reviewer_id", 'left')
            ->where('fr.submission_id', $submissionId)
            ->orderBy('fr.tanggal_review','DESC')
            ->orderBy('fr.id','DESC')
            ->get()->getResultArray();
    }

    /* ========================= Pages ========================= */

    public function detail($id)
    {
        $id = (int)$id;
        $submission = $this->findSubmission($id);
        if (!$submission) return redirect()->back()->with('error','Submission tidak ditemukan');

        $table = $this->tableName();
        $cols  = $this->resolveColumns($table);

        $submission['id']        = $submission[$cols['pk']];
        $submission['title']     = $submission[$cols['title']];
        $submission['revisi_ke'] = (int)($submission['revisi_ke'] ?? 0);

        if ($this->db->tableExists('events') && $cols['event_id'] && !empty($submission[$cols['event_id']])) {
            $ev = $this->db->table('events')->select('id, title')->where('id', $submission[$cols['event_id']])->get()->getRowArray();
            if ($ev) { $submission['event_title'] = $ev['title']; $submission['event_id'] = (int)$ev['id']; }
        }

        // author
        $author = ['name'=>null,'email'=>null];
        foreach (['penulis_nama','nama_lengkap','presenter_name','author_name','nama'] as $c)
            if ($this->columnExists($table,$c) && !empty($submission[$c])) { $author['name'] = $submission[$c]; break; }
        foreach (['penulis_email','email','presenter_email','author_email'] as $c)
            if ($this->columnExists($table,$c) && !empty($submission[$c])) { $author['email'] = $submission[$c]; break; }

        // coauthors
        $coauthors = [];
        if ($this->columnExists($table,'coauthors_json') && !empty($submission['coauthors_json'])) {
            $decoded = json_decode((string)$submission['coauthors_json'], true);
            if (is_array($decoded)) $coauthors = $decoded;
        }

        // history (semua versi user+event sama)
        $history = [];
        if ($cols['event_id'] && !empty($submission[$cols['event_id']]) && $cols['user_id'] && !empty($submission[$cols['user_id']])) {
            $select = [$cols['pk']." AS id"];
            foreach (['revisi_ke','full_paper_status','full_paper_uploaded_at'] as $c)
                if ($this->columnExists($table,$c)) $select[] = $c;
            $history = $this->db->table($table)->select(implode(', ', $select))
                ->where($cols['event_id'], (int)$submission[$cols['event_id']])
                ->where($cols['user_id'],  (int)$submission[$cols['user_id']])
                ->orderBy('revisi_ke','DESC')
                ->orderBy('full_paper_uploaded_at','DESC')
                ->orderBy($cols['pk'],'DESC')
                ->get()->getResultArray();
        }

        // kandidat & assigned
        $kategoriId = $this->resolveCategoryId($submission, $cols);
        $assigned   = $this->getAssignedReviewers($id);
        $assignedIds= array_map(fn($r)=>(int)$r['reviewer_id'], $assigned);

        // Reviewer abstrak sebagai informasi (TIDAK di-exclude dari kandidat)
        $absAssignedIds   = $this->getAbstractAssignedReviewerIds(
            $cols['user_id'] ? (int)$submission[$cols['user_id']] : null,
            $cols['event_id']? (int)$submission[$cols['event_id']] : null
        );
        $abstractReviewers = $absAssignedIds ? $this->getReviewerIdentities($absAssignedIds) : [];

        // Kandidat hanya exclude yang sudah assigned di full paper ini
        $reviewers = $this->getReviewersByCategory($kategoriId, $assignedIds);

        $fpReviews = $this->getFullpaperReviews($id);

        return view('role/admin/fullpaper/detail', [
            'submission'         => $submission,
            'author'             => $author,
            'coauthors'          => $coauthors,
            'history'            => $history,
            'reviewers'          => $reviewers,
            'assignedReviewers'  => $assigned,
            'abstractReviewers'  => $abstractReviewers,
            'fpReviews'          => $fpReviews,
            'title'              => 'Detail Full Paper',
            'maxReviewer'        => 3,
        ]);
    }

    /** Ambil identitas reviewer by IDs (untuk menampilkan daftar reviewer abstrak) */
    private function getReviewerIdentities(array $ids): array
    {
        $ids = array_values(array_unique(array_map('intval',$ids)));
        if (!$ids) return [];
        $src = $this->resolveReviewerSource();
        if (!$src['table'] || !$src['id']) return [];

        $nameCol  = $src['name']  ? "{$src['table']}.{$src['name']}"  : "NULL";
        $emailCol = $src['email'] ? "{$src['table']}.{$src['email']}" : "NULL";

        return $this->db->table($src['table'])
            ->select("{$src['table']}.{$src['id']} AS id, {$nameCol} AS name, {$emailCol} AS email")
            ->whereIn("{$src['table']}.{$src['id']}", $ids)
            ->orderBy($src['name'] ? $nameCol : "{$src['table']}.{$src['id']}", 'ASC')
            ->get()->getResultArray();
    }

    /* ========================= AJAX (optional) ========================= */

    public function reviewersByCategory($kategoriId)
    {
        try {
            $submissionId = (int)($this->request->getGet('submission_id') ?? 0);
            $exclude = [];
            if ($submissionId) {
                $assigned = $this->getAssignedReviewers($submissionId);
                $exclude  = array_map(fn($r)=>(int)$r['reviewer_id'], $assigned);
            }
            $list = $this->getReviewersByCategory((int)$kategoriId, $exclude);
            return $this->response->setJSON(['success'=>true, 'data'=>$list]);
        } catch (\Throwable $e) {
            log_message('error', 'reviewersByCategory error: '.$e->getMessage());
            return $this->response->setJSON(['success'=>false, 'message'=>'Gagal memuat reviewer'])->setStatusCode(500);
        }
    }

    /* ========================= Actions ========================= */

    public function assign($submissionId)
    {
        try {
            $submissionId = (int)$submissionId;
            $reviewerId   = (int)$this->request->getPost('reviewer_id');

            if (!$submissionId || !$reviewerId) {
                return redirect()->back()->with('error','Data tidak valid.');
            }

            $sub = $this->findSubmission($submissionId);
            if (!$sub) return redirect()->back()->with('error','Submission tidak ditemukan.');

            // MAX 3
            $count = $this->db->table('fullpaper_reviewers')->where('submission_id',$submissionId)->countAllResults();
            if ($count >= 3) {
                return redirect()->back()->with('error','Maksimum 3 reviewer sudah tercapai.');
            }

            // Duplicates (fullpaper)
            $dup = $this->db->table('fullpaper_reviewers')
                    ->where('submission_id',$submissionId)
                    ->where('reviewer_id',$reviewerId)
                    ->get()->getRowArray();
            if ($dup) {
                return redirect()->back()->with('error','Reviewer ini sudah ditugaskan pada full paper.');
            }

            // eligible kategori (jika modul tersedia)
            $table = $this->tableName(); $cols = $this->resolveColumns($table);
            $kategoriId = $this->resolveCategoryId($sub, $cols);
            if ($kategoriId && $this->revKatModel && method_exists($this->revKatModel,'isReviewerEligible')) {
                if (!$this->revKatModel->isReviewerEligible($reviewerId, $kategoriId)) {
                    return redirect()->back()->with('error','Reviewer tidak sesuai kategori.');
                }
            }

            $ok = $this->db->table('fullpaper_reviewers')->insert([
                'submission_id' => $submissionId,
                'reviewer_id'   => $reviewerId,
                'assigned_at'   => date('Y-m-d H:i:s'),
            ]);

            if (!$ok) {
                return redirect()->back()->with('error','Gagal menugaskan reviewer.');
            }

            return redirect()->to(site_url('admin/fullpaper/detail/'.$submissionId))
                ->with('success','Reviewer berhasil ditugaskan.');

        } catch (\Throwable $e) {
            log_message('error','FullPaper assign err: '.$e->getMessage());
            return redirect()->back()->with('error','Terjadi kesalahan.');
        }
    }

    public function setStatus($submissionId)
    {
        try {
            $submissionId = (int)$submissionId;
            $status = strtoupper((string)$this->request->getPost('status'));
            $allowed = ['UPLOADED','REVISION','ACCEPTED','REJECTED'];
            if (!$submissionId || !in_array($status,$allowed,true)) {
                return redirect()->back()->with('error','Input tidak valid.');
            }

            $table = $this->tableName();
            $pk    = $this->primaryKey($table);

            $ok = $this->db->table($table)->where($pk,$submissionId)
                ->update(['full_paper_status'=>$status]);

            if (!$ok) return redirect()->back()->with('error','Gagal memperbarui status.');

            return redirect()->to(site_url('admin/fullpaper/detail/'.$submissionId))
                ->with('success','Status berhasil diperbarui.');
        } catch (\Throwable $e) {
            log_message('error','FullPaper setStatus err: '.$e->getMessage());
            return redirect()->back()->with('error','Terjadi kesalahan.');
        }
    }

    /* ========================= Preview & Download ========================= */

    public function view($id)
    {
        $id = (int) $id;
        $submission = $this->findSubmission($id);
        if (!$submission) {
            return $this->response->setContentType('text/html', 'utf-8')
                ->setBody('<div style="padding:12px;font-family:system-ui">Submission tidak ditemukan.</div>');
        }

        $path = $this->resolveFullPaperPath($submission);
        if (!$path) {
            return $this->response->setContentType('text/html', 'utf-8')
                ->setBody('<div style="padding:12px;font-family:system-ui">File full paper tidak ditemukan.</div>');
        }

        if (function_exists('ob_get_level')) while (ob_get_level() > 0) { @ob_end_clean(); }
        @ini_set('display_errors', '0');

        $this->response
            ->setHeader('X-Frame-Options', 'SAMEORIGIN')
            ->setHeader('Content-Security-Policy', "frame-ancestors 'self'")
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('Cache-Control', 'private, max-age=0, must-revalidate');

        if ($this->isPublicUrl($path)) {
            $ctx    = stream_context_create([
                'http' => ['follow_location' => 1, 'timeout' => 20],
                'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false],
            ]);
            $binary = @file_get_contents($path, false, $ctx);
            if ($binary === false) {
                return $this->response->setStatusCode(502)->setBody('Gagal mengambil file dari sumber eksternal.');
            }
        } else {
            if (!is_readable($path)) {
                return $this->response->setContentType('text/html', 'utf-8')
                    ->setBody('<div style="padding:12px;font-family:system-ui">File tidak dapat dibaca.</div>');
            }
            $binary = @file_get_contents($path);
            if ($binary === false) {
                return $this->response->setStatusCode(500)->setBody('Gagal membaca file.');
            }
        }

        return $this->response
            ->setContentType('application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="fullpaper-'.$id.'.pdf"')
            ->setHeader('Content-Length', (string) strlen($binary))
            ->setHeader('Accept-Ranges', 'bytes')
            ->setBody($binary);
    }

    public function blob($id)
    {
        $id = (int) $id;
        $submission = $this->findSubmission($id);
        if (!$submission) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'not_found']);
        }

        $path = $this->resolveFullPaperPath($submission);
        if (!$path) {
            return $this->response->setStatusCode(404)->setJSON(['error' => 'file_not_found']);
        }

        if (function_exists('ob_get_level')) while (ob_get_level() > 0) { @ob_end_clean(); }
        @ini_set('display_errors', '0');

        if ($this->isPublicUrl($path)) {
            $ctx    = stream_context_create([
                'http' => ['follow_location' => 1, 'timeout' => 20],
                'ssl'  => ['verify_peer' => false, 'verify_peer_name' => false],
            ]);
            $binary = @file_get_contents($path, false, $ctx);
            if ($binary === false) {
                return $this->response->setStatusCode(502)->setJSON(['error' => 'upstream_failed']);
            }
        } else {
            if (!is_readable($path)) {
                return $this->response->setStatusCode(403)->setJSON(['error' => 'unreadable']);
            }
            $binary = @file_get_contents($path);
            if ($binary === false) {
                return $this->response->setStatusCode(500)->setJSON(['error' => 'read_failed']);
            }
        }

        return $this->response
            ->setContentType('application/octet-stream')
            ->setHeader('Content-Disposition', 'inline; filename="fullpaper-'.$id.'.bin"')
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->setHeader('Pragma', 'no-cache')
            ->setHeader('Expires', 'Sat, 01 Jan 2000 00:00:00 GMT')
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setBody($binary);
    }

    public function download($id)
    {
        $id = (int)$id;
        $submission = $this->findSubmission($id);
        if (!$submission) return redirect()->back()->with('error','Submission tidak ditemukan');

        $path = $this->resolveFullPaperPath($submission);
        if (!$path) return redirect()->back()->with('error','File full paper tidak ditemukan');

        if ($this->isPublicUrl($path)) return redirect()->to($path);

        if (function_exists('ob_get_level')) while (ob_get_level() > 0) { @ob_end_clean(); }
        @ini_set('display_errors', '0');

        return $this->response->download($path, null);
    }
}
