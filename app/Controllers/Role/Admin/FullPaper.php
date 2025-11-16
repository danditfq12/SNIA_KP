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

    /* ========================= Helper Redirect ========================= */
    /**
     * Pastikan "balik" selalu jatuh ke halaman detail submission
     * jika previous_url() kosong (CI4 kadang bisa kosong → jatuh ke '/').
     */
    private function backOrDetail(int $submissionId, array $flash = [], ?string $explicitUrl = null)
    {
        $fallback = $explicitUrl ?: site_url('admin/fullpaper/detail/'.$submissionId);
        $target   = previous_url() ?: $fallback;

        $resp = redirect()->to($target);
        foreach ($flash as $k => $v) $resp->with($k, $v);
        return $resp;
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

        $rel = ltrim(str_replace(['\\','..'], ['/', ''], $fname), '/');
        $baseEnv = trim((string) env('fullpaper.storage_base', ''), '/\\');

        $candidates = [
            FCPATH   . $rel,
            ROOTPATH . $rel,
            WRITEPATH. $rel,
            WRITEPATH . 'uploads/fullpaper/' . basename($rel),
            FCPATH    . 'uploads/fullpaper/' . basename($rel),
        ];
        if ($baseEnv !== '') $candidates[] = rtrim($baseEnv, '/\\') . DIRECTORY_SEPARATOR . $rel;
        if (is_file($fname)) $candidates[] = $fname;

        foreach ($candidates as $p) if (is_file($p)) return $p;
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

    /* ========= Abstrak ========= */

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

    private function normAbsStatus(?string $v): string
    {
        $k = strtolower(trim((string)$v));
        if (in_array($k, ['accepted','diterima','accept'], true)) return 'diterima';
        if (in_array($k, ['revisi','revision'], true))           return 'revisi';
        if (in_array($k, ['rejected','ditolak','reject'], true)) return 'ditolak';
        if (in_array($k, ['sedang_direview','in_review'], true)) return 'sedang_direview';
        if (in_array($k, ['pending','menunggu',''], true))       return 'menunggu';
        return 'menunggu';
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

        // Deteksi kolom di pivot
        $frFields = array_flip($this->db->getFieldNames('fullpaper_reviewers') ?: []);
        $pick = function(array $cands) use ($frFields) {
            foreach ($cands as $c) if (isset($frFields[$c])) return "fr.$c";
            return null;
        };

        $assignCol = $pick(['assignment_status','status_tugas','tugas_status','konfirmasi_status']);
        $reasonCol = $pick(['decline_reason','alasan','alasan_tolak','reason']);
        $accAtCol  = $pick(['accepted_at','confirmed_at','konfirmasi_at']);
        $decAtCol  = $pick(['declined_at','rejected_at']);
        $ordCol    = $pick(['order_no','urutan','posisi']);
        $assignedAt= $pick(['assigned_at','created_at']);

        $assignSel = $assignCol ? "$assignCol AS assignment_status" : "NULL AS assignment_status";
        $reasonSel = $reasonCol ? "$reasonCol AS decline_reason"    : "NULL AS decline_reason";
        $accSel    = $accAtCol  ? "$accAtCol  AS accepted_at"       : "NULL AS accepted_at";
        $decSel    = $decAtCol  ? "$decAtCol  AS declined_at"       : "NULL AS declined_at";
        $ordSel    = $ordCol    ? "$ordCol    AS order_no"          : "NULL AS order_no";
        $asgSel    = $assignedAt? "$assignedAt AS assigned_at"      : "fr.assigned_at AS assigned_at";

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
                ->select("
                    fr.id, fr.reviewer_id,
                    {$asgSel},
                    {$emailCol} AS email,
                    {$nameCol}  AS name,
                    {$statusExpr}  AS status,
                    {$tanggalExpr} AS status_at,
                    {$assignSel},
                    {$reasonSel},
                    {$accSel},
                    {$decSel},
                    {$ordSel}
                ")
                ->join($src['table'], "{$src['table']}.{$src['id']} = fr.reviewer_id", 'left')
                ->where('fr.submission_id', $submissionId)
                ->orderBy($ordCol ? 'order_no' : 'fr.id', 'ASC')
                ->get()->getResultArray();
        }

        return $this->db->table('fullpaper_reviewers fr')
            ->select("
                fr.id, fr.reviewer_id,
                {$asgSel},
                {$emailCol} AS email,
                {$nameCol}  AS name,
                NULL AS status,
                NULL AS status_at,
                {$assignSel},
                {$reasonSel},
                {$accSel},
                {$decSel},
                {$ordSel}
            ")
            ->join($src['table'], "{$src['table']}.{$src['id']} = fr.reviewer_id", 'left')
            ->where('fr.submission_id', $submissionId)
            ->orderBy($ordCol ? 'order_no' : 'fr.id', 'ASC')
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

    /* ========= Auto-assign reviewer abstrak ke full paper ========= */

    private function autoAssignAbstractReviewersToFullpaper(int $submissionId, array $abstractReviewers): void
    {
        if (!$this->db->tableExists('fullpaper_reviewers')) return;
        if (empty($abstractReviewers)) return;

        // Ambil ID reviewer abstrak yang valid
        $absReviewerIds = [];
        foreach ($abstractReviewers as $rv) {
            $rid = (int)($rv['id'] ?? $rv['reviewer_id'] ?? 0);
            if ($rid > 0) $absReviewerIds[] = $rid;
        }
        $absReviewerIds = array_values(array_unique($absReviewerIds));
        if (!$absReviewerIds) return;

        // Ambil semua penugasan existing
        $allExisting = $this->db->table('fullpaper_reviewers')
            ->select('reviewer_id, assignment_status')
            ->where('submission_id', $submissionId)
            ->get()->getResultArray();

        $existingById = [];
        $activeCount  = 0;
        foreach ($allExisting as $row) {
            $rid = (int)($row['reviewer_id'] ?? 0);
            if ($rid <= 0) continue;
            $existingById[$rid] = strtolower((string)($row['assignment_status'] ?? ''));
            if ($existingById[$rid] !== 'declined') {
                $activeCount++;
            }
        }

        $maxReviewer = 3;
        $slotLeft    = $maxReviewer - $activeCount;
        if ($slotLeft <= 0) return;

        $now = date('Y-m-d H:i:s');
        $inserted = 0;

        $this->db->transStart();
        foreach ($absReviewerIds as $rid) {
            if ($inserted >= $slotLeft) break;
            if (isset($existingById[$rid])) continue; // sudah pernah ada record

            $this->db->table('fullpaper_reviewers')->insert([
                'submission_id' => $submissionId,
                'reviewer_id'   => $rid,
                'assigned_at'   => $now,
            ]);
            $inserted++;
        }
        $this->db->transComplete();
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
        if (!$submission) return $this->backOrDetail($id, ['error'=>'Submission tidak ditemukan'], site_url('admin/kelola-paper'));

        $table = $this->tableName();
        $cols  = $this->resolveColumns($table);

        $submission['id']        = $submission[$cols['pk']];
        the_submission:
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

        // author (dari submission bila ada)
        $author = ['name'=>null,'email'=>null];
        foreach (['penulis_nama','nama_lengkap','presenter_name','author_name','nama'] as $c)
            if ($this->columnExists($table,$c) && !empty($submission[$c])) { $author['name'] = $submission[$c]; break; }
        foreach (['penulis_email','email','presenter_email','author_email'] as $c)
            if ($this->columnExists($table,$c) && !empty($submission[$c])) { $author['email'] = $submission[$c]; break; }

        // coauthors dari submission
        $coauthors = [];
        if ($this->columnExists($table,'coauthors_json') && !empty($submission['coauthors_json'])) {
            $decoded = json_decode((string)$submission['coauthors_json'], true);
            if (is_array($decoded)) $coauthors = $decoded;
        }

        // history (semua row user+event)
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

        // kategori
        $kategoriId = $this->resolveCategoryId($submission, $cols);

        // abstrak: status & reviewer
        $userId  = $cols['user_id']  ? (int)($submission[$cols['user_id']]  ?? 0) : 0;
        $eventId = $cols['event_id'] ? (int)($submission[$cols['event_id']] ?? 0) : 0;
        $abstractStatusRaw = $this->getAbstractStatus($userId, $eventId);
        $abstractStatus    = $this->normAbsStatus($abstractStatusRaw);
        $abstractReviewers = $this->getAbstractReviewersDetailed($userId, $eventId);

        // === AUTO-ASSIGN reviewer abstrak ke full paper ketika abstrak sudah DITERIMA ===
        if ($abstractStatus === 'diterima') {
            $this->autoAssignAbstractReviewersToFullpaper((int)$submission['id'], $abstractReviewers);
        }

        // setelah auto-assign, ambil ulang data penugasan full paper
        $assigned   = $this->getAssignedReviewers($id);

        // pisahkan penugasan aktif vs declined
        $assignedActive = array_values(array_filter($assigned, function($r){
            $st = strtolower((string)($r['assignment_status'] ?? ''));
            return $st !== 'declined';
        }));
        $declinedList = array_values(array_filter($assigned, function($r){
            return strtolower((string)($r['assignment_status'] ?? '')) === 'declined';
        }));

        $assignedIds = array_map(fn($r)=>(int)$r['reviewer_id'], $assignedActive);
        $reviewers   = $this->getReviewersByCategory($kategoriId, $assignedIds);

        // Tambahan dari abstrak & presenter
        $absInfo   = $this->getAbstractPeopleData($userId, $eventId);
        $presenter = $this->getPresenterIdentity($userId, $eventId);

        // Fallback author
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

        /* ========================= View-Model (presentation) ========================= */
        $badgeMap   = ['NONE'=>'secondary','UPLOADED'=>'info','REVISION'=>'warning','ACCEPTED'=>'success','REJECTED'=>'danger'];
        $statusText = ['NONE'=>'—','UPLOADED'=>'Diunggah','REVISION'=>'Revisi','ACCEPTED'=>'Diterima','REJECTED'=>'Ditolak'];

        $status     = strtoupper($submission['full_paper_status'] ?? 'NONE');
        $uploadedAt = !empty($submission['full_paper_uploaded_at']) ? date('d M Y H:i', strtotime($submission['full_paper_uploaded_at'])) : '—';
        $revisiKe   = (int)($submission['revisi_ke'] ?? 0);

        $submissionId = (int)($submission['id'] ?? 0);
        $eventIdVM    = (int)($submission['event_id'] ?? 0);
        $backUrl      = $eventIdVM ? site_url('admin/kelola-paper/detail/'.$eventIdVM) : site_url('admin/kelola-paper');

        $downloadUrl  = $submissionId ? site_url('admin/fullpaper/download/'.$submissionId) : '';
        $previewUrl   = $submissionId ? site_url('admin/fullpaper/view/'.$submissionId) : '';
        $gdocs        = $previewUrl ? ('https://docs.google.com/gview?embedded=1&url='.rawurlencode($previewUrl)) : '';

        $rvStatMap = [
          'diterima'=>['Diterima','success'],
          'accepted'=>['Diterima','success'],
          'rejected'=>['Ditolak','danger'],
          'ditolak' =>['Ditolak','danger'],
          'revisi'  =>['Revisi','warning'],
          'revision'=>['Revisi','warning'],
          'uploaded'=>['Diunggah','info'],
          'pending' =>['Pending','secondary'],
          'menunggu'=>['Menunggu','secondary'],
          'sedang_direview'=>['Sedang Ditinjau','info'],
          ''=>['—','secondary'], null=>['—','secondary'],
        ];

        // counters & kuota (aktif saja)
        $assignedCount  = count($assignedActive);
        $completedCount = 0;
        foreach ($assigned as $ar) {
          $st = strtolower($ar['status'] ?? '');
          if (in_array($st, ['diterima','accepted','revisi','revision','ditolak','rejected'], true)) $completedCount++;
        }
        $maxReviewer = 3;
        $quotaFull   = $assignedCount >= $maxReviewer;

        $hasReviewDecision = false;
        foreach ($fpReviews as $rv) {
          $k = strtolower($rv['keputusan'] ?? '');
          if (in_array($k, ['accepted','diterima','revision','revisi','rejected','ditolak'], true)) { $hasReviewDecision = true; break; }
        }

        // mapping badge assignment status
        $assignStatusMap = [
          'accepted' => ['primary','Assigned'],
          'declined' => ['secondary','Declined'],
          'default'  => ['secondary','Pending'],
        ];

        // Gating flag utk UI
        $canAssign = ($abstractStatus === 'diterima') && !in_array($status, ['REJECTED','ACCEPTED'], true);
        $assignBlockedReason = null;
        if ($abstractStatus !== 'diterima') {
            $assignBlockedReason = 'Abstrak belum diterima (status: '.ucfirst(str_replace('_',' ',$abstractStatus)).').';
        } elseif (in_array($status, ['REJECTED','ACCEPTED'], true)) {
            $assignBlockedReason = 'Full paper sudah berstatus final ('.$statusText[$status].').';
        }

        $vm = [
          'status' => $status,
          'badgeMap' => $badgeMap,
          'statusText' => $statusText,
          'uploadedAt' => $uploadedAt,
          'revisiKe' => $revisiKe,

          'submissionId' => $submissionId,
          'eventId' => $eventIdVM,
          'backUrl' => $backUrl,
          'downloadUrl' => $downloadUrl,
          'previewUrl'  => $previewUrl,
          'gdocs'       => $gdocs,

          'rvStatMap' => $rvStatMap,

          'assignedCount' => $assignedCount,
          'completedCount' => $completedCount,
          'maxReviewer' => $maxReviewer,
          'quotaFull' => $quotaFull,

          'hasReviewDecision' => $hasReviewDecision,
          'assignStatusMap' => $assignStatusMap,

          'canAssign' => $canAssign,
          'assignBlockedReason' => $assignBlockedReason,
        ];
        /* ======================================================= */

        return view('role/admin/kelola_paper/fullpaper_detail', [
            'submission'         => $submission,
            'author'             => $author,
            'coauthors'          => $coauthors,
            'history'            => $history,
            'reviewers'          => $reviewers,
            'assignedReviewers'  => $assignedActive,   // hanya aktif
            'declinedReviewers'  => $declinedList,     // list penolakan
            'abstractReviewers'  => $abstractReviewers,
            'abstractStatus'     => $abstractStatus,
            'fpReviews'          => $fpReviews,

            'absKategoriId'      => $absKategoriId,
            'absKategoriName'    => $absKategoriName,
            'absContributors'    => $absContributors,
            'absJudul'           => $absJudul,

            'title'              => 'Detail Full Paper',
            'vm'                 => $vm,
        ]);
    }

    public function reviewersByCategory($kategoriId)
    {
        try {
            $submissionId = (int)($this->request->getGet('submission_id') ?? 0);
            $exclude = [];
            if ($submissionId) {
                $assigned = $this->getAssignedReviewers($submissionId);
                // exclude hanya yang aktif
                $assignedActive = array_values(array_filter($assigned, function($r){
                    $st = strtolower((string)($r['assignment_status'] ?? ''));
                    return $st !== 'declined';
                }));
                $exclude  = array_map(fn($r)=>(int)$r['reviewer_id'], $assignedActive);
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
                return $this->backOrDetail($submissionId, ['error'=>'Data tidak valid.']);
            }

            $sub = $this->findSubmission($submissionId);
            if (!$sub) return $this->backOrDetail($submissionId, ['error'=>'Submission tidak ditemukan.']);

            $table = $this->tableName(); 
            $cols  = $this->resolveColumns($table);

            // Gating 1: abstrak harus punya keputusan review
            $userId  = $cols['user_id']  ? (int)($sub[$cols['user_id']]  ?? 0) : 0;
            $eventId = $cols['event_id'] ? (int)($sub[$cols['event_id']] ?? 0) : 0;

            $absReviewers = $this->getAbstractReviewersDetailed($userId, $eventId);
            $adaKeputusan = false;
            foreach ($absReviewers as $rv) {
                $k = strtolower($rv['status'] ?? '');
                if (in_array($k, ['accepted','diterima','rejected','ditolak','revision','revisi'], true)) {
                    $adaKeputusan = true; break;
                }
            }
            if (!$adaKeputusan) {
                return $this->backOrDetail($submissionId, [
                    'error' => 'Tidak dapat menugaskan reviewer: abstrak presenter tersebut belum direview oleh reviewer.'
                ]);
            }

            // Gating 2: abstrak harus DITERIMA
            $absStatusNorm = $this->normAbsStatus($this->getAbstractStatus($userId, $eventId));
            if ($absStatusNorm !== 'diterima') {
                return $this->backOrDetail($submissionId, [
                    'error' => 'Tidak dapat menugaskan reviewer: abstrak belum diterima (status: '.ucfirst(str_replace('_',' ',$absStatusNorm)).').'
                ]);
            }

            // Blok kalau submission sudah final (REJECTED atau ACCEPTED)
            $fpStatus = strtoupper((string)($sub['full_paper_status'] ?? 'NONE'));
            if (in_array($fpStatus, ['REJECTED','ACCEPTED'], true)) {
                return $this->backOrDetail($submissionId, [
                    'error' => 'Penugasan ditolak: full paper sudah berstatus final ('.$fpStatus.').'
                ]);
            }

            $this->db->transStart();

            // Kuota: hanya hitung aktif
            $all = $this->getAssignedReviewers($submissionId);
            $active = array_values(array_filter($all, function($r){
                $st = strtolower((string)($r['assignment_status'] ?? ''));
                return $st !== 'declined';
            }));
            if (count($active) >= 3) {
                $this->db->transComplete();
                return $this->backOrDetail($submissionId, ['error'=>'Maksimum 3 reviewer aktif sudah tercapai.']);
            }

            $dup = $this->db->table('fullpaper_reviewers')
                    ->where('submission_id',$submissionId)
                    ->where('reviewer_id',$reviewerId)
                    ->get()->getRowArray();
            if ($dup) {
                $this->db->transComplete();
                return $this->backOrDetail($submissionId, ['error'=>'Reviewer ini sudah pernah ditugaskan pada full paper.']);
            }

            $kategoriId = $this->resolveCategoryId($sub, $cols);
            if ($kategoriId && $this->revKatModel && method_exists($this->revKatModel,'isReviewerEligible')) {
                if (!$this->revKatModel->isReviewerEligible($reviewerId, $kategoriId)) {
                    $this->db->transComplete();
                    return $this->backOrDetail($submissionId, ['error'=>'Reviewer tidak sesuai kategori.']);
                }
            }

            $ok = $this->db->table('fullpaper_reviewers')->insert([
                'submission_id' => $submissionId,
                'reviewer_id'   => $reviewerId,
                'assigned_at'   => date('Y-m-d H:i:s'),
            ]);

            $this->db->transComplete();

            if (!$ok || $this->db->transStatus() === false) {
                return $this->backOrDetail($submissionId, ['error'=>'Gagal menugaskan reviewer.']);
            }

            return redirect()->to(site_url('admin/fullpaper/detail/'.$submissionId))
                ->with('success','Reviewer berhasil ditugaskan.');

        } catch (\Throwable $e) {
            log_message('error','FullPaper assign err: '.$e->getMessage());
            return $this->backOrDetail((int)$submissionId, ['error'=>'Terjadi kesalahan.']);
        }
    }

    public function unassign($submissionId, $reviewerId)
    {
        try {
            $submissionId = (int)$submissionId;
            $reviewerId   = (int)$reviewerId;
            if (!$submissionId || !$reviewerId) {
                return $this->backOrDetail($submissionId, ['error'=>'Data tidak valid.']);
            }

            if (!$this->db->tableExists('fullpaper_reviewers')) {
                return $this->backOrDetail($submissionId, ['error'=>'Tabel penugasan tidak ditemukan.']);
            }

            // Cek status assignment dulu: kalau sudah accepted, tidak boleh dicabut
            $frFields = array_flip($this->db->getFieldNames('fullpaper_reviewers') ?: []);
            $assignCol = null;
            foreach (['assignment_status','status_tugas','tugas_status','konfirmasi_status'] as $c) {
                if (isset($frFields[$c])) { $assignCol = $c; break; }
            }

            $row = $this->db->table('fullpaper_reviewers')
                ->where('submission_id', $submissionId)
                ->where('reviewer_id',   $reviewerId)
                ->get()->getRowArray();

            if (!$row) {
                return $this->backOrDetail($submissionId, ['error'=>'Penugasan tidak ditemukan.']);
            }

            if ($assignCol && strtolower((string)($row[$assignCol] ?? '')) === 'accepted') {
                return $this->backOrDetail($submissionId, [
                    'error' => 'Tidak dapat mencabut penugasan: reviewer sudah mengkonfirmasi penugasan (accepted).'
                ]);
            }

            $ok = $this->db->table('fullpaper_reviewers')
                ->where('submission_id', $submissionId)
                ->where('reviewer_id',   $reviewerId)
                ->delete();

            return redirect()->to(site_url('admin/fullpaper/detail/'.$submissionId))
                ->with($ok ? 'success':'error', $ok ? 'Penugasan dicabut.' : 'Gagal mencabut penugasan.');
        } catch (\Throwable $e) {
            log_message('error','FullPaper unassign err: '.$e->getMessage());
            return $this->backOrDetail((int)$submissionId, ['error'=>'Terjadi kesalahan.']);
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
                return $this->backOrDetail($submissionId, ['error'=>'Input tidak valid.']);
            }

            // komentar wajib untuk REVISION/REJECTED
            if (in_array($status, ['REVISION','REJECTED'], true) && mb_strlen($komentar) < 10) {
                return $this->backOrDetail($submissionId, ['error'=>'Harap isi komentar minimal 10 karakter untuk status Revisi/Ditolak.']);
            }

            $table = $this->tableName();
            $pk    = $this->primaryKey($table);

            $data = ['full_paper_status'=>$status];

            foreach (['review_notes','catatan_reviewer','admin_comment','admin_notes'] as $col) {
                if ($this->columnExists($table,$col)) { $data[$col] = ($komentar !== '' ? $komentar : null); break; }
            }
            foreach (['decision_at','full_paper_decision_at'] as $col) {
                if ($this->columnExists($table,$col)) { $data[$col] = date('Y-m-d H:i:s'); break; }
            }
            foreach (['full_paper_decision_by','decision_by'] as $col) {
                if ($this->columnExists($table,$col)) { $data[$col] = (int)(session('id_user') ?? 0); break; }
            }
            if ($this->columnExists($table,'eligible_to_pay')) {
                $data['eligible_to_pay'] = ($status === 'ACCEPTED');
            }

            $ok = $this->db->table($table)->where($pk,$submissionId)->update($data);

            if (!$ok) return $this->backOrDetail($submissionId, ['error'=>'Gagal memperbarui status.']);

            return redirect()->to(site_url('admin/fullpaper/detail/'.$submissionId))
                ->with('success','Status berhasil diperbarui.');

        } catch (\Throwable $e) {
            log_message('error','FullPaper setStatus err: '.$e->getMessage());
            return $this->backOrDetail((int)$submissionId, ['error'=>'Terjadi kesalahan.']);
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
        if (!$submission) return $this->backOrDetail($id, ['error'=>'Submission tidak ditemukan']);

        $path = $this->resolveFullPaperPath($submission);
        if (!$path) return $this->backOrDetail($id, ['error'=>'File full paper tidak ditemukan']);

        if ($this->isPublicUrl($path)) return redirect()->to($path);

        if (function_exists('ob_get_level')) while (ob_get_level() > 0) { @ob_end_clean(); }
        @ini_set('display_errors', '0');

        return $this->response->download($path, null);
    }
}
