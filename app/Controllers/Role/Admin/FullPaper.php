<?php
namespace App\Controllers\Role\Admin;

use App\Controllers\BaseController;
use App\Models\ReviewerKategoriModel;
use App\Models\AbstrakModel;
use App\Models\KategoriAbstrakModel;

class FullPaper extends BaseController
{
    protected $db;
    protected $revKatModel;
    protected $abstrakModel;
    protected $kategoriAbsModel;

    public function __construct()
    {
        $this->db = \Config\Database::connect();

        if (class_exists(\App\Models\ReviewerKategoriModel::class)) {
            $this->revKatModel = new ReviewerKategoriModel();
        }
        if (class_exists(\App\Models\AbstrakModel::class)) {
            $this->abstrakModel = new AbstrakModel();
        }
        if (class_exists(\App\Models\KategoriAbstrakModel::class)) {
            $this->kategoriAbsModel = new KategoriAbstrakModel();
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

    /** Ambil PK dinamis untuk tabel submission/abstrak (utama halaman ini) */
    private function primaryKey(string $table): string
    {
        $fields = $this->fields($table);

        $candidates = [
            'id',
            'id_submission','submission_id',
            'id_fullpaper','fullpaper_id',
            'id_paper','paper_id',
            'id_abstrak','abstrak_id',
        ];
        foreach ($candidates as $cand) {
            if (in_array($cand, $fields, true)) return $cand;
        }

        // Try from information_schema (Postgres/MySQL)
        try {
            $row = $this->db->query(
                "SELECT kcu.column_name
                 FROM information_schema.table_constraints tc
                 JOIN information_schema.key_column_usage kcu
                   ON tc.constraint_name = kcu.constraint_name
                  AND tc.table_schema    = kcu.table_schema
                WHERE tc.constraint_type = 'PRIMARY KEY'
                  AND tc.table_name      = ?
                LIMIT 1",
                [$table]
            )->getRowArray();

            if (!empty($row['column_name']) && in_array($row['column_name'], $fields, true)) {
                return $row['column_name'];
            }
        } catch (\Throwable $e) { /* ignore */ }

        return $fields[0] ?? 'id';
    }

    /** Helper umum untuk tabel lain (events, users, dll) */
    private function tablePrimaryKeyFlexible(string $table, array $candidates = []): string
    {
        if (!$this->db->tableExists($table)) return 'id';
        $fields = $this->db->getFieldNames($table) ?: [];

        $defaultCands = ['id', $table.'_id', 'id_'.$table, 'id_'.$table.'_pk'];
        $cands = array_unique(array_merge($candidates, $defaultCands));

        foreach ($cands as $c) {
            if (in_array($c, $fields, true)) return $c;
        }
        return $fields[0] ?? 'id';
    }

    private function resolveColumns(string $table): array
    {
        $pk = $this->primaryKey($table);

        $titleCand = ['title','judul','judul_paper','judul_penelitian','judul_abstrak','nama'];
        $eventCand = ['event_id','id_event','events_id'];
        $userCand  = ['id_user','user_id','id_presenter','presenter_id'];
        $catCand   = ['id_kategori','kategori_id','id_kategori_abstrak','kategori_abstrak_id'];

        $pick = function(array $cands) use ($table) {
            foreach ($cands as $c) if ($this->columnExists($table,$c)) return $c;
            return null;
        };

        return [
            'pk'          => $pk,
            'title'       => $pick($titleCand) ?? $pk,
            'event_id'    => $pick($eventCand),
            'user_id'     => $pick($userCand),
            'kategori_id' => $pick($catCand),
        ];
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
        $userId  = $cols['user_id']  ? (int)($submission[$cols['user_id']]  ?? 0) : 0;
        $eventId = $cols['event_id'] ? (int)($submission[$cols['event_id']] ?? 0) : 0;
        if ($userId && $eventId) {
            $abs = $this->getLatestAbstractRow($userId, $eventId);
            if ($abs && !empty($abs['id_kategori'])) return (int)$abs['id_kategori'];
        }
        return null;
    }

    /* ========= Abstrak: status global + reviewer-detail ========= */

    private function getLatestAbstractRow(?int $userId, ?int $eventId): ?array
    {
        if (!$userId || !$eventId) return null;
        if (!$this->db->tableExists('abstrak')) return null;

        return $this->db->table('abstrak')
            ->where('id_user', $userId)
            ->where('event_id', $eventId)
            ->orderBy('tanggal_upload','DESC')
            ->get()->getRowArray() ?: null;
    }

    private function getAbstractStatus(?int $userId, ?int $eventId): ?string
    {
        $abs = $this->getLatestAbstractRow($userId, $eventId);
        return $abs['status'] ?? null;
    }

    private function getAbstractReviewersDetailed(?int $userId, ?int $eventId): array
    {
        $abs = $this->getLatestAbstractRow($userId, $eventId);
        if (!$abs) return [];
        $idAbstrak = (int)$abs['id_abstrak'];

        $src = $this->resolveReviewerSource();
        if (!$src['table'] || !$src['id']) return [];

        $mapTable = null;
        foreach (['abstrak_reviewers','abstrak_reviewer','reviewer_abstrak'] as $cand) {
            if ($this->db->tableExists($cand)) { $mapTable = $cand; break; }
        }

        $ids = [];

        if ($mapTable) {
            $cols = $this->db->getFieldNames($mapTable);
            $colAbs = in_array('id_abstrak',$cols,true) ? 'id_abstrak' : (in_array('abstrak_id',$cols,true) ? 'abstrak_id' : null);
            $colRev = in_array('id_reviewer',$cols,true) ? 'id_reviewer' : (in_array('reviewer_id',$cols,true) ? 'reviewer_id' : null);
            if ($colAbs && $colRev) {
                $rows = $this->db->table($mapTable)->select($colRev.' AS reviewer_id')->where($colAbs, $idAbstrak)->get()->getResultArray();
                foreach ($rows as $r) $ids[] = (int)$r['reviewer_id'];
            }
        }

        foreach (['abstrak_reviews','reviews','review'] as $t) {
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

        $ids = array_values(array_unique(array_filter($ids)));
        if (!$ids) return [];

        $nameCol  = $src['name']  ? "{$src['table']}.{$src['name']}"  : "NULL";
        $emailCol = $src['email'] ? "{$src['table']}.{$src['email']}" : "NULL";
        $ident = $this->db->table($src['table'])
            ->select("{$src['table']}.{$src['id']} AS id, {$nameCol} AS name, {$emailCol} AS email")
            ->whereIn("{$src['table']}.{$src['id']}", $ids)->get()->getResultArray();

        $identBy = [];
        foreach ($ident as $r) $identBy[(int)$r['id']] = $r;

        $latest = [];
        foreach (['abstrak_reviews','reviews','review'] as $t) {
            if (!$this->db->tableExists($t)) continue;
            $cols = $this->db->getFieldNames($t);
            $colAbs = in_array('id_abstrak',$cols,true) ? 'id_abstrak' : (in_array('abstrak_id',$cols,true) ? 'abstrak_id' : null);
            $colRev = in_array('id_reviewer',$cols,true) ? 'id_reviewer' : (in_array('reviewer_id',$cols,true) ? 'reviewer_id' : null);
            if (!$colAbs || !$colRev) continue;

            $rows = $this->db->table($t)
                ->select("$colRev AS reviewer_id, keputusan, tanggal_review")
                ->where($colAbs, $idAbstrak)
                ->orderBy($colRev,'ASC')->orderBy('tanggal_review','DESC')->get()->getResultArray();

            foreach ($rows as $r) {
                $rid = (int)$r['reviewer_id'];
                if (!isset($latest[$rid])) $latest[$rid] = $r;
            }
        }

        $out = [];
        foreach ($ids as $rid) {
            $info = $identBy[$rid] ?? ['name'=>'Reviewer','email'=>null];
            $k = $latest[$rid]['keputusan'] ?? null;
            $out[] = [
                'id'     => $rid,
                'name'   => $info['name']  ?? 'Reviewer',
                'email'  => $info['email'] ?? null,
                'status' => $k ? strtolower($k) : null,
            ];
        }
        return $out;
    }

    /* ========= Tambahan util untuk kategori + people dari abstrak ========= */

    private function getCategoryNameById(?int $kategoriId): ?string
    {
        if (!$kategoriId) return null;

        if ($this->kategoriAbsModel instanceof KategoriAbstrakModel) {
            $row = $this->kategoriAbsModel->where('id_kategori', $kategoriId)->select('nama_kategori')->get()->getRowArray();
            if (!empty($row['nama_kategori'])) return $row['nama_kategori'];
        }

        $candidates = [
            ['table'=>'kategori_abstrak','id'=>'id_kategori','name'=>'nama_kategori'],
            ['table'=>'kategori_abstrak','id'=>'id','name'=>'nama'],
            ['table'=>'abstrak_kategori','id'=>'id','name'=>'nama'],
            ['table'=>'kategori','id'=>'id','name'=>'nama'],
            ['table'=>'categories','id'=>'id','name'=>'name'],
        ];
        foreach ($candidates as $c) {
            if (!$this->db->tableExists($c['table'])) continue;
            $fields = array_flip($this->db->getFieldNames($c['table']) ?: []);
            if (!isset($fields[$c['id']]) || !isset($fields[$c['name']])) continue;
            $row = $this->db->table($c['table'])
                ->select($c['name'].' AS name')->where($c['id'], $kategoriId)->get()->getRowArray();
            if (!empty($row['name'])) return $row['name'];
        }
        return null;
    }

    private function extractContributorsFromAbs(array $abs): array
    {
        $cands = ['contributors','contributor','authors','author_list','co_authors','coauthor','coauthors',
                  'penulis','penulis_lain','daftar_penulis','nama_penulis'];
        $val = null;
        foreach ($cands as $k) if (!empty($abs[$k])) { $val = $abs[$k]; break; }
        if ($val === null) return [];
        $list = is_array($val) ? $val : preg_split('/\r\n|\r|\n|;|,/', (string)$val);
        $list = array_filter(array_map(fn($x)=>trim((string)$x),(array)$list), fn($x)=>$x!=='');
        return array_values(array_unique($list));
    }

    private function getAbstractPeopleData(?int $userId, ?int $eventId): array
    {
        $abs = $this->getLatestAbstractRow($userId, $eventId);
        if (!$abs) return [
            'author'=>['name'=>null,'email'=>null],
            'coauthors'=>[], 'contributors'=>[],
            'kategori_id'=>null, 'kategori_name'=>null, 'judul'=>null
        ];

        $author = ['name'=>null,'email'=>null];
        foreach (['penulis_nama','nama_lengkap','author_name','nama'] as $c) if (!empty($abs[$c])) { $author['name'] = $abs[$c]; break; }
        foreach (['penulis_email','email','author_email'] as $c) if (!empty($abs[$c])) { $author['email'] = $abs[$c]; break; }

        $co = [];
        if (!empty($abs['coauthors_json'])) {
            $dec = json_decode((string)$abs['coauthors_json'], true);
            if (is_array($dec)) $co = $dec;
        } elseif (!empty($abs['co_authors'])) {
            $co = array_map(fn($n)=>['nama'=>trim($n)], $this->extractContributorsFromAbs(['co_authors'=>$abs['co_authors']]));
        }

        $contributors = $this->extractContributorsFromAbs($abs);
        $kid = (int)($abs['id_kategori'] ?? 0) ?: null;

        return [
            'author'        => $author,
            'coauthors'     => $co,
            'contributors'  => $contributors,
            'kategori_id'   => $kid,
            'kategori_name' => $this->getCategoryNameById($kid),
            'judul'         => $abs['judul'] ?? null,
        ];
    }

    /* ====== Fallback identitas presenter dari users/registrations ====== */
    private function getPresenterIdentity(?int $userId, ?int $eventId): array
    {
        $out = ['name'=>null,'email'=>null,'affiliation'=>null];

        if ($userId) {
            if ($this->db->tableExists('users')) {
                $userPk = $this->tablePrimaryKeyFlexible('users', ['id','id_user','user_id']);
                $u = $this->db->table('users')->where($userPk, $userId)->get()->getRowArray();
                if ($u) {
                    foreach (['nama_lengkap','full_name','name','username'] as $c) {
                        if (!empty($u[$c])) { $out['name'] = trim((string)$u[$c]); break; }
                    }
                    if (empty($out['email'])) {
                        foreach (['email','user_email'] as $c) if (!empty($u[$c])) { $out['email'] = trim((string)$u[$c]); break; }
                    }
                }
            }
        }

        if ($eventId && $userId) {
            $regTable = null;
            foreach (['event_registrations','registrations','event_registration','event_pendaftar','pendaftaran_event'] as $t) {
                if ($this->db->tableExists($t)) { $regTable = $t; break; }
            }
            if ($regTable) {
                $fields = array_flip($this->db->getFieldNames($regTable) ?: []);
                $fUser  = isset($fields['id_user']) ? 'id_user' : (isset($fields['user_id']) ? 'user_id' : null);
                $fEvent = isset($fields['id_event']) ? 'id_event' : (isset($fields['event_id']) ? 'event_id' : null);
                if ($fUser && $fEvent) {
                    $reg = $this->db->table($regTable)
                        ->where($fUser, $userId)->where($fEvent, $eventId)->get()->getRowArray();
                    if ($reg) {
                        if (empty($out['name'])) {
                            foreach (['presenter_name','nama_lengkap','nama','name'] as $c) {
                                if (!empty($reg[$c])) { $out['name'] = trim((string)$reg[$c]); break; }
                            }
                        }
                        if (empty($out['email'])) {
                            foreach (['email','presenter_email'] as $c) {
                                if (!empty($reg[$c])) { $out['email'] = trim((string)$reg[$c]); break; }
                            }
                        }
                        foreach (['afiliasi','affiliation','institusi','institution'] as $c) {
                            if (!empty($reg[$c])) { $out['affiliation'] = trim((string)$reg[$c]); break; }
                        }
                    }
                }
            }
        }

        if ((empty($out['name']) || $out['name'] === null) && !empty($out['email'])) {
            $local = explode('@', $out['email'])[0] ?? '';
            $local = str_replace(['.', '_', '-'], ' ', $local);
            $out['name'] = ucwords(preg_replace('/\s+/', ' ', trim($local)));
        }

        return $out;
    }

    /* ========================= Full paper: assigned + reviews ========================= */

    private function getAssignedReviewers(int $submissionId): array
    {
        if (!$this->db->tableExists('fullpaper_reviewers')) return [];
        $src = $this->resolveReviewerSource();
        $nameCol  = $src['name']  ? "{$src['table']}.{$src['name']}"  : "NULL";
        $emailCol = $src['email'] ? "{$src['table']}.{$src['email']}" : "NULL";

        $hasReviews = $this->db->tableExists('fullpaper_reviews');
        $colsFR = array_flip($this->db->getFieldNames('fullpaper_reviewers'));

        $assignCol = null;
        foreach (['assignment_status','status_tugas','tugas_status','konfirmasi_status'] as $c) {
            if (isset($colsFR[$c])) { $assignCol = "fr.$c"; break; }
        }
        $assignSel = $assignCol ? "$assignCol AS assignment_status" : "NULL AS assignment_status";

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
                ->select("fr.id, fr.reviewer_id, fr.assigned_at, {$emailCol} AS email, {$nameCol} AS name, {$statusExpr} AS status, {$tanggalExpr} AS status_at, {$assignSel}")
                ->join($src['table'], "{$src['table']}.{$src['id']} = fr.reviewer_id", 'left')
                ->where('fr.submission_id', $submissionId)
                ->orderBy('fr.id', 'ASC')
                ->get()->getResultArray();
        }

        return $this->db->table('fullpaper_reviewers fr')
            ->select("fr.id, fr.reviewer_id, fr.assigned_at, {$emailCol} AS email, {$nameCol} AS name, NULL AS status, NULL AS status_at, {$assignSel}")
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

    /* ========================= Reviewer picker by kategori ========================= */

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
            $evPk = $this->tablePrimaryKeyFlexible('events', ['id','id_event','event_id']);
            $ev   = $this->db->table('events')
                     ->select("$evPk AS id, title")
                     ->where($evPk, $submission[$cols['event_id']])
                     ->get()->getRowArray();
            if ($ev) { $submission['event_title'] = $ev['title']; $submission['event_id'] = (int)$submission[$cols['event_id']]; }
        }

        // author possible fields (from submission table)
        $author = ['name'=>null,'email'=>null];
        foreach (['penulis_nama','nama_lengkap','presenter_name','author_name','nama'] as $c)
            if ($this->columnExists($table,$c) && !empty($submission[$c])) { $author['name'] = $submission[$c]; break; }
        foreach (['penulis_email','email','presenter_email','author_email'] as $c)
            if ($this->columnExists($table,$c) && !empty($submission[$c])) { $author['email'] = $submission[$c]; break; }

        // coauthors from submission
        $coauthors = [];
        if ($this->columnExists($table,'coauthors_json') && !empty($submission['coauthors_json'])) {
            $decoded = json_decode((string)$submission['coauthors_json'], true);
            if (is_array($decoded)) $coauthors = $decoded;
        }

        // history (all rows of same user+event)
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

        // kategori & reviewer
        $kategoriId = $this->resolveCategoryId($submission, $cols);
        $assigned   = $this->getAssignedReviewers($id);
        $assignedIds= array_map(fn($r)=>(int)$r['reviewer_id'], $assigned);
        $reviewers  = $this->getReviewersByCategory($kategoriId, $assignedIds);

        // abstrak: status global + reviewer2 (dengan status review abstrak)
        $userId  = $cols['user_id']  ? (int)($submission[$cols['user_id']]  ?? 0) : 0;
        $eventId = $cols['event_id'] ? (int)($submission[$cols['event_id']] ?? 0) : 0;
        $abstractStatus    = $this->getAbstractStatus($userId, $eventId);
        $abstractReviewers = $this->getAbstractReviewersDetailed($userId, $eventId);

        // Tambahan: kategori & people dari abstrak (fallback)
        $absInfo = $this->getAbstractPeopleData($userId, $eventId);

        // Fallback prioritas: data presenter (users/registrations)
        $presenter = $this->getPresenterIdentity($userId, $eventId);

        // Merge fallback → submission → abstrak → presenter
        if (empty($author['name']) && !empty($absInfo['author']['name']))  $author['name']  = $absInfo['author']['name'];
        if (empty($author['email']) && !empty($absInfo['author']['email'])) $author['email'] = $absInfo['author']['email'];
        if (empty($author['name'])  && !empty($presenter['name']))          $author['name']  = $presenter['name'];
        if (empty($author['email']) && !empty($presenter['email']))         $author['email'] = $presenter['email'];

        if (empty($coauthors) && !empty($absInfo['coauthors'])) {
            $coauthors = $absInfo['coauthors'];
        }

        $absKategoriId   = $absInfo['kategori_id'];
        $absKategoriName = $absInfo['kategori_name'];
        $absContributors = $absInfo['contributors'];
        $absJudul        = $absInfo['judul'];

        $fpReviews = $this->getFullpaperReviews($id);

        return view('role/admin/kelola_paper/fullpaper_detail', [
            'submission'         => $submission,
            'author'             => $author,
            'coauthors'          => $coauthors,
            'history'            => $history,
            'reviewers'          => $reviewers,
            'assignedReviewers'  => $assigned,
            'abstractReviewers'  => $abstractReviewers,
            'abstractStatus'     => $abstractStatus,
            'fpReviews'          => $fpReviews,

            // tambahan untuk view
            'absKategoriId'      => $absKategoriId,
            'absKategoriName'    => $absKategoriName,
            'absContributors'    => $absContributors,
            'absJudul'           => $absJudul,

            'title'              => 'Detail Full Paper',
            'maxReviewer'        => 3,
        ]);
    }

    /** (opsional) API simple untuk dropdown reviewer by kategori */
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

            $count = $this->db->table('fullpaper_reviewers')->where('submission_id',$submissionId)->countAllResults();
            if ($count >= 3) {
                return redirect()->back()->with('error','Maksimum 3 reviewer sudah tercapai.');
            }

            $dup = $this->db->table('fullpaper_reviewers')
                    ->where('submission_id',$submissionId)
                    ->where('reviewer_id',$reviewerId)
                    ->get()->getRowArray();
            if ($dup) {
                return redirect()->back()->with('error','Reviewer ini sudah ditugaskan pada full paper.');
            }

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
            $komentar = trim((string)$this->request->getPost('komentar'));
            $allowed = ['UPLOADED','REVISION','ACCEPTED','REJECTED'];
            if (!$submissionId || !in_array($status,$allowed,true)) {
                return redirect()->back()->with('error','Input tidak valid.');
            }

            $table = $this->tableName();
            $pk    = $this->primaryKey($table);

            $data = ['full_paper_status'=>$status];
            foreach (['review_notes','catatan_reviewer','admin_comment','admin_notes'] as $col) {
                if ($komentar !== '' && $this->columnExists($table,$col)) { $data[$col] = $komentar; break; }
            }
            foreach (['decision_at','full_paper_decision_at'] as $col) {
                if ($this->columnExists($table,$col)) { $data[$col] = date('Y-m-d H:i:s'); break; }
            }

            $ok = $this->db->table($table)->where($pk,$submissionId)->update($data);

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
