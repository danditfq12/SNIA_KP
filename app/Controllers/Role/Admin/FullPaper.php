<?php
namespace App\Controllers\Role\Admin;

use App\Controllers\BaseController;

class FullPaper extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /* ========================= Helpers umum ========================= */

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
        $sql = "
            SELECT kcu.column_name
            FROM information_schema.table_constraints tc
            JOIN information_schema.key_column_usage kcu
              ON tc.constraint_name = kcu.constraint_name
             AND tc.table_schema = kcu.table_schema
            WHERE tc.constraint_type = 'PRIMARY KEY'
              AND tc.table_name = ?
              AND tc.table_schema = current_schema()
            LIMIT 1
        ";
        $row = $this->db->query($sql, [$table])->getFirstRow('array');
        if (!empty($row['column_name'])) return $row['column_name'];

        foreach (['id','id_submission','id_abstrak'] as $cand) {
            if ($this->columnExists($table, $cand)) return $cand;
        }
        throw new \RuntimeException("Tidak dapat mendeteksi primary key untuk tabel {$table}");
    }

    private function resolveColumns(string $table): array
    {
        $pk = $this->primaryKey($table);
        $titleCand = ['title','judul','judul_paper','judul_penelitian','judul_abstrak','nama'];
        $eventCand = ['event_id','id_event','events_id'];
        $userCand  = ['id_user','user_id','id_presenter'];

        $titleCol = null;
        foreach ($titleCand as $c) if ($this->columnExists($table,$c)) { $titleCol = $c; break; }
        if (!$titleCol) $titleCol = $pk;

        $eventCol = null;
        foreach ($eventCand as $c) if ($this->columnExists($table,$c)) { $eventCol = $c; break; }

        $userCol = null;
        foreach ($userCand as $c) if ($this->columnExists($table,$c)) { $userCol = $c; break; }

        return ['pk'=>$pk,'title'=>$titleCol,'event_id'=>$eventCol,'user_id'=>$userCol];
    }

    private function findSubmission(int $id): ?array
    {
        $table = $this->tableName();
        $pk    = $this->primaryKey($table);
        $row   = $this->db->table($table)->where($pk, $id)->get()->getRowArray();
        return $row ?: null;
    }

    /** === Resolver sumber reviewer (reviewers/users) dgn kolom dinamis === */
    private function resolveReviewerSource(): array
    {
        $table = $this->db->tableExists('reviewers') ? 'reviewers'
              : ($this->db->tableExists('users') ? 'users' : null);

        if (!$table) return ['table'=>null,'id'=>null,'name'=>null,'email'=>null];

        $fields = array_flip($this->db->getFieldNames($table) ?: []);

        $idCol   = null;
        foreach (['id','id_user','user_id','reviewer_id'] as $cand)
            if (isset($fields[$cand])) { $idCol = $cand; break; }

        $nameCol = null;
        foreach (['nama_lengkap','nama','name','full_name','username'] as $cand)
            if (isset($fields[$cand])) { $nameCol = $cand; break; }

        $emailCol = null;
        foreach (['email','user_email','mail'] as $cand)
            if (isset($fields[$cand])) { $emailCol = $cand; break; }

        return ['table'=>$table,'id'=>$idCol,'name'=>$nameCol,'email'=>$emailCol];
    }

    private function getReviewers(): array
    {
        $src = $this->resolveReviewerSource();
        if (!$src['table'] || !$src['id']) return [];

        $builder = $this->db->table($src['table']);

        $select = [];
        $select[] = "{$src['id']} AS id";
        if ($src['name'])  $select[] = "{$src['name']} AS name";
        if ($src['email']) $select[] = "{$src['email']} AS email";
        $builder->select(implode(', ', $select));

        if ($src['table'] === 'reviewers') {
            $fields = $this->db->getFieldNames('reviewers') ?: [];
            if (in_array('status', $fields, true)) $builder->where('status', 'aktif');
        } elseif ($src['table'] === 'users') {
            $fields = $this->db->getFieldNames('users') ?: [];
            if (in_array('role', $fields, true))   $builder->where('role', 'reviewer');
        }

        if ($src['name']) $builder->orderBy($src['name'], 'ASC');
        return $builder->get()->getResultArray();
    }

    private function getAssignedReviewers(int $submissionId): array
    {
        if (!$this->db->tableExists('fullpaper_reviewers')) return [];

        $src = $this->resolveReviewerSource();
        if (!$src['table'] || !$src['id']) {
            return $this->db->table('fullpaper_reviewers')
                ->where('submission_id', $submissionId)
                ->orderBy('id', 'ASC')
                ->get()->getResultArray();
        }

        $nameCol  = $src['name']  ? "{$src['table']}.{$src['name']}"  : "NULL";
        $emailCol = $src['email'] ? "{$src['table']}.{$src['email']}" : "NULL";

        return $this->db->table('fullpaper_reviewers fr')
            ->select("fr.id, fr.reviewer_id, fr.assigned_at, {$emailCol} AS email, {$nameCol} AS name")
            ->join($src['table'], "{$src['table']}.{$src['id']} = fr.reviewer_id", 'left')
            ->where('fr.submission_id', $submissionId)
            ->orderBy('fr.id', 'ASC')
            ->get()->getResultArray();
    }

    /* ========================= Actions ========================= */

    public function index()
    {
        $table   = $this->tableName();
        $cols    = $this->resolveColumns($table);
        $status  = $this->request->getGet('status');
        $eventId = $this->request->getGet('event_id');

        $select = [];
        $select[] = "{$table}.{$cols['pk']} AS id";
        $select[] = "{$table}.{$cols['title']} AS title";
        if ($cols['event_id']) $select[] = "{$table}.{$cols['event_id']} AS event_id";
        foreach (['full_paper_status','full_paper_uploaded_at','full_paper_path','revisi_ke'] as $c)
            if ($this->columnExists($table,$c)) $select[] = "{$table}.{$c}";
        foreach (['penulis_nama','nama_lengkap','presenter_name','author_name','nama','penulis_email','email','presenter_email','author_email'] as $c)
            if ($this->columnExists($table,$c)) $select[] = "{$table}.{$c}";

        $b = $this->db->table($table)->select(implode(', ', $select));

        if ($cols['user_id'] && $this->db->tableExists('users')) {
            $b->select('users.nama_lengkap AS nama_lengkap, users.email AS email')
              ->join('users', "users.id_user = {$table}.{$cols['user_id']}", 'left');
        }

        if ($status)  $b->where("{$table}.full_paper_status", $status);
        if ($eventId && $cols['event_id']) $b->where("{$table}.{$cols['event_id']}", (int)$eventId);

        $rows = $b->orderBy("{$table}.{$cols['pk']}",'DESC')->get()->getResultArray();

        $events = [];
        if ($this->db->tableExists('events')) {
            $events = $this->db->table('events')->select('id, title')->orderBy('title','ASC')->get()->getResultArray();
            $byId = [];
            foreach ($events as $e) $byId[(int)$e['id']] = $e['title'];
            foreach ($rows as &$r)
                if (isset($r['event_id']) && isset($byId[(int)$r['event_id']])) $r['event_title'] = $byId[(int)$r['event_id']];
            unset($r);
        }

        $total = count($rows);
        $uploaded=0;$accepted=0;$rejected=0;
        foreach ($rows as $r) {
            $st = strtoupper($r['full_paper_status'] ?? 'NONE');
            if (in_array($st, ['UPLOADED','REVISION','ACCEPTED','REJECTED'], true)) $uploaded++;
            if ($st==='ACCEPTED') $accepted++;
            if ($st==='REJECTED') $rejected++;
        }

        return view('role/admin/fullpaper/index', [
            'rows'               => $rows,
            'events'             => $events,
            'filter'             => ['status'=>$status,'event_id'=>$eventId],
            'title'              => 'Full Paper',
            'total_fullpaper'    => $total,
            'fullpaper_uploaded' => $uploaded,
            'fullpaper_accepted' => $accepted,
            'fullpaper_rejected' => $rejected,
        ]);
    }

    public function detail($id)
    {
        $id = (int)$id;
        $submission = $this->findSubmission($id);
        if (!$submission) return redirect()->back()->with('error','Submission tidak ditemukan');

        $table = $this->tableName();
        $cols  = $this->resolveColumns($table);

        // Alias wajib utk view
        $submission['id']        = $submission[$cols['pk']];
        $submission['title']     = $submission[$cols['title']];
        $submission['revisi_ke'] = (int)($submission['revisi_ke'] ?? 0);

        // Event title
        if ($this->db->tableExists('events') && $cols['event_id'] && !empty($submission[$cols['event_id']])) {
            $ev = $this->db->table('events')->select('title')->where('id', $submission[$cols['event_id']])->get()->getRowArray();
            if ($ev) $submission['event_title'] = $ev['title'];
        }

        // Author (nama & email) dengan fallback luas
        $author = ['name'=>null,'email'=>null];
        foreach (['penulis_nama','nama_lengkap','presenter_name','author_name','nama'] as $c)
            if ($this->columnExists($table,$c) && !empty($submission[$c])) { $author['name'] = $submission[$c]; break; }
        foreach (['penulis_email','email','presenter_email','author_email'] as $c)
            if ($this->columnExists($table,$c) && !empty($submission[$c])) { $author['email'] = $submission[$c]; break; }

        // Join users (kalau perlu)
        $userIdVal = null;
        if ($cols['user_id'] && !empty($submission[$cols['user_id']])) {
            $userIdVal = (int)$submission[$cols['user_id']];
            if ($this->db->tableExists('users')) {
                $u = $this->db->table('users')->select('nama_lengkap, email, id_user')
                    ->where('id_user', $userIdVal)->get()->getRowArray();
                if ($u) {
                    if (!$author['name'])  $author['name']  = $u['nama_lengkap'] ?? null;
                    if (!$author['email']) $author['email'] = $u['email'] ?? null;
                }
            }
        }

        // Co-authors
        $coauthors = [];
        if ($this->columnExists($table,'coauthors_json') && !empty($submission['coauthors_json'])) {
            $decoded = json_decode((string)$submission['coauthors_json'], true);
            if (is_array($decoded)) $coauthors = $decoded;
        }
        if (!$coauthors && $this->db->tableExists('event_registrations') && $cols['event_id'] && $userIdVal) {
            $reg = $this->db->table('event_registrations')
                ->where('id_event', (int)$submission[$cols['event_id']])
                ->where('id_user',  $userIdVal)
                ->orderBy('id','DESC')->get()->getRowArray();
            if ($reg && !empty($reg['coauthors_json'])) {
                $decoded = json_decode((string)$reg['coauthors_json'], true);
                if (is_array($decoded)) $coauthors = $decoded;
            }
        }

        // Riwayat revisi
        $history = [];
        if ($cols['event_id'] && $userIdVal) {
            $select = [$cols['pk']." AS id"];
            foreach (['revisi_ke','full_paper_status','full_paper_uploaded_at'] as $c)
                if ($this->columnExists($table,$c)) $select[] = $c;
            $history = $this->db->table($table)->select(implode(', ', $select))
                ->where($cols['event_id'], (int)$submission[$cols['event_id']])
                ->where($cols['user_id'],  $userIdVal)
                ->orderBy('revisi_ke','DESC')
                ->orderBy('full_paper_uploaded_at','DESC')
                ->orderBy($cols['pk'],'DESC')
                ->get()->getResultArray();
        }

        // Reviewer
        $reviewers = $this->getReviewers();
        $assigned  = $this->getAssignedReviewers($id);

        return view('role/admin/fullpaper/detail', [
            'submission'        => $submission,
            'author'            => $author,
            'coauthors'         => $coauthors,
            'history'           => $history,
            'reviewers'         => $reviewers,
            'assignedReviewers' => $assigned,
            'title'             => 'Detail Full Paper',
        ]);
    }

    public function setStatus($id)
    {
        $id = (int)$id;
        $status = strtoupper(trim((string)$this->request->getPost('status')));
        $allowed = ['UPLOADED','REVISION','ACCEPTED','REJECTED'];
        if (!in_array($status, $allowed, true)) {
            return redirect()->back()->with('error','Status tidak valid');
        }

        $submission = $this->findSubmission($id);
        if (!$submission) return redirect()->back()->with('error','Submission tidak ditemukan');

        $table = $this->tableName();
        $pk    = $this->primaryKey($table);
        $data  = ['full_paper_status' => $status];
        if ($this->columnExists($table, 'eligible_to_pay')) {
            $data['eligible_to_pay'] = ($status === 'ACCEPTED');
        }

        $this->db->table($table)->where($pk, $id)->update($data);

        return redirect()->to(site_url('admin/fullpaper/detail/'.$id))
                         ->with('success','Status full paper diperbarui');
    }

    public function assign($id)
    {
        $id = (int) $id;
        $reviewerId = (int) $this->request->getPost('reviewer_id');

        if (!$reviewerId) return redirect()->back()->with('error', 'Reviewer wajib dipilih.');
        if (!$this->db->tableExists('fullpaper_reviewers'))
            return redirect()->back()->with('error', 'Tabel fullpaper_reviewers tidak tersedia.');

        $exists = $this->db->table('fullpaper_reviewers')
            ->where('submission_id', $id)
            ->where('reviewer_id', $reviewerId)
            ->get()->getRowArray();
        if ($exists) return redirect()->back()->with('info', 'Reviewer sudah ditugaskan.');

        $this->db->table('fullpaper_reviewers')->insert([
            'submission_id' => $id,
            'reviewer_id'   => $reviewerId,
            'assigned_at'   => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to(site_url('admin/fullpaper/detail/'.$id))
            ->with('success', 'Reviewer berhasil ditugaskan.');
    }

    /* ========================= File handling (Preview & Download) ========================= */

    private function isPublicUrl(string $path): bool
    {
        return (bool)preg_match('~^https?://~i', $path);
    }

    private function resolveFullPaperPath(array $submission): ?string
    {
        $fname = trim((string)($submission['full_paper_path'] ?? ''));
        if ($fname === '') return null;

        if ($this->isPublicUrl($fname)) return $fname;      // URL
        if (is_file($fname))             return $fname;      // absolute local

        $candidates = [
            WRITEPATH . 'uploads/fullpaper/' . $fname,
            WRITEPATH . 'uploads/abstrak/'  . $fname,
            FCPATH    . 'uploads/fullpaper/' . $fname,
            FCPATH    . 'uploads/abstrak/'  . $fname,
        ];
        foreach ($candidates as $p) if (is_file($p)) return $p;
        return null;
    }

    /** === Inline preview dengan dukungan Range (anti-IDM) === */
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

        // Untuk URL publik, biarkan browser handle sendiri (tetap inline)
        if ($this->isPublicUrl($path)) {
            return $this->response
                ->setHeader('Referrer-Policy', 'no-referrer-when-downgrade')
                ->setHeader('X-Content-Type-Options', 'nosniff')
                ->setHeader('Cache-Control', 'private, max-age=0, must-revalidate')
                ->redirect($path);
        }

        if (!is_readable($path)) {
            return $this->response->setContentType('text/html', 'utf-8')
                ->setBody('<div style="padding:12px;font-family:system-ui">File tidak dapat dibaca.</div>');
        }

        return $this->inlinePdfResponse($path, 'fullpaper-' . $id . '.pdf');
    }

    /** === Download (force attachment) === */
    public function download($id)
    {
        $id = (int)$id;
        $submission = $this->findSubmission($id);
        if (!$submission) return redirect()->back()->with('error','Submission tidak ditemukan');

        $path = $this->resolveFullPaperPath($submission);
        if (!$path) return redirect()->back()->with('error','File full paper tidak ditemukan');

        if ($this->isPublicUrl($path)) {
            return redirect()->to($path);
        }
        return $this->response->download($path, null);
    }

    /* ========================= Low-level streaming helpers ========================= */

    private function inlinePdfResponse(string $path, string $filename)
    {
        // Bersihkan buffer output
        if (function_exists('ob_get_level')) {
            while (ob_get_level() > 0) { @ob_end_clean(); }
        }

        $size  = filesize($path);
        $start = 0;
        $end   = $size - 1;
        $code  = 200;

        // Dukung Range request
        $range = $this->request->getHeaderLine('Range');
        if ($range && preg_match('/bytes=(\d*)-(\d*)/i', $range, $m)) {
            if ($m[1] !== '') $start = (int)$m[1];
            if ($m[2] !== '') $end   = (int)$m[2];
            if ($end > $size - 1) $end = $size - 1;
            if ($start > $end)    $start = 0;
            $code = 206; // Partial Content
        }

        $length = $end - $start + 1;

        // Header untuk inline viewer (tidak memicu IDM)
        $this->response->setStatusCode($code);
        $this->response->setHeader('Content-Type', 'application/pdf');
        $this->response->setHeader('X-Content-Type-Options', 'nosniff');
        $this->response->setHeader('Content-Disposition', 'inline; filename="'.$filename.'"');
        $this->response->setHeader('Accept-Ranges', 'bytes');
        $this->response->setHeader('Cache-Control', 'private, max-age=0, must-revalidate');
        $this->response->setHeader('Pragma', 'public');
        $this->response->setHeader('Content-Length', (string)$length);
        if ($code === 206) {
            $this->response->setHeader('Content-Range', "bytes $start-$end/$size");
        }

        // Stream bagian file yang diminta
        $fp = fopen($path, 'rb');
        if ($fp === false) {
            return $this->response->setStatusCode(500)->setBody('Gagal membuka file.');
        }

        if ($start > 0) fseek($fp, $start);

        $chunk = 8192;
        $sent  = 0;
        while (!feof($fp) && $sent < $length) {
            $remaining = $length - $sent;
            $read      = $remaining > $chunk ? $chunk : $remaining;
            $buffer    = fread($fp, $read);
            if ($buffer === false) break;
            echo $buffer;
            $sent += strlen($buffer);
            if (function_exists('fastcgi_finish_request')) { @flush(); }
            else { @flush(); @ob_flush(); }
        }
        fclose($fp);

        return $this->response;
    }
}