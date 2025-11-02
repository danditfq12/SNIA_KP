<?php

namespace App\Controllers\Role\Reviewer;

use App\Controllers\BaseController;
use App\Models\AbstrakModel;

class Abstrak extends BaseController
{
    protected AbstrakModel $abstrakModel;
    protected $db;
    protected string $uploadBase;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->abstrakModel = new AbstrakModel();
        $this->uploadBase = WRITEPATH;
        helper(['date']);
    }

    /* ================= Utils: table & columns ================= */

    private function reviewTable(): ?string
    {
        if ($this->db->tableExists('reviews')) return 'reviews';
        if ($this->db->tableExists('review'))  return 'review';
        return null;
    }

    private function hasCol(string $table, string $col): bool
    {
        if (!$table) return false;
        $cols = array_map('strtolower', $this->db->getFieldNames($table) ?: []);
        return in_array(strtolower($col), $cols, true);
    }

    private function pickCol(string $table, array $cands): ?string
    {
        foreach ($cands as $c) if ($this->hasCol($table, $c)) return $c;
        return null;
    }

    private function reviewCols(string $rt): array
    {
        return [
            'pk'        => $this->pickCol($rt, ['id','id_review']) ?? 'id',
            'reviewer'  => $this->pickCol($rt, ['id_reviewer','reviewer_id','user_id']) ?? 'id_reviewer',
            'abstrakFk' => $this->pickCol($rt, ['id_abstrak']) ?? 'id_abstrak',
            'subFk'     => $this->pickCol($rt, ['id_submission','submission_id']),
            'type'      => $this->pickCol($rt, ['type','review_type']),
            'decision'  => $this->pickCol($rt, ['keputusan','decision','status']),
            'comment'   => $this->pickCol($rt, ['komentar','comment','notes','catatan']),
            'ts'        => $this->pickCol($rt, ['tanggal_review','updated_at','created_at']),
            'asgStatus' => $this->pickCol($rt, ['status_tugas','tugas_status','assignment_status','konfirmasi_status']),
            'asgReason' => $this->pickCol($rt, ['alasan_tolak','alasan','decline_reason','reason']),
            'asgAccAt'  => $this->pickCol($rt, ['accepted_at','confirmed_at','konfirmasi_at']),
            'asgDecAt'  => $this->pickCol($rt, ['declined_at','rejected_at']),
            'revNum'    => $this->pickCol($rt, ['revisi_ke','revision_no']),
        ];
    }

    private function normDecision(?string $v): string
    {
        $k = strtolower((string)$v);
        if (in_array($k, ['accepted','diterima','accept','ok','yes'], true)) return 'diterima';
        if (in_array($k, ['revisi','revision','revise'], true))            return 'revisi';
        if (in_array($k, ['rejected','ditolak','reject','no'], true))       return 'ditolak';
        if (in_array($k, ['sedang_direview','in_review'], true))            return 'sedang_direview';
        if (in_array($k, ['pending','menunggu',''], true))                  return 'menunggu';
        return 'menunggu';
    }

    private function isFinalDecision(?string $v): bool
    {
        $n = $this->normDecision($v);
        return in_array($n, ['diterima','revisi','ditolak'], true);
    }

    private function latestAssignmentRow(int $idAbstrak, int $idReviewer): ?array
    {
        $rt = $this->reviewTable();
        if (!$rt) return null;
        $R  = $this->reviewCols($rt);

        $b = $this->db->table($rt)
            ->where($R['abstrakFk'], $idAbstrak)
            ->where($R['reviewer'],  $idReviewer);

        if ($R['type'])  $b->groupStart()->where("LOWER({$R['type']})",'abstrak')->orWhere("{$R['type']}", null)->groupEnd();
        if ($R['subFk']) $b->where("{$R['subFk']} IS NULL", null, false);

        return $b->orderBy($R['pk'],'DESC')->get()->getRowArray();
    }

    private function getTaskStatus(int $idAbstrak, int $idReviewer): string
    {
        $rt = $this->reviewTable();
        if (!$rt) return 'accepted';
        $R  = $this->reviewCols($rt);

        if (!$R['asgStatus']) return 'accepted';

        $row = $this->latestAssignmentRow($idAbstrak, $idReviewer);
        if (!$row) return 'pending';

        $st = strtolower((string)($row[$R['asgStatus']] ?? 'pending'));
        if (in_array($st, ['accept','accepted','ok','yes'], true)) $st = 'accepted';
        if (in_array($st, ['decline','declined','no','rejected_task'], true)) $st = 'declined';
        return $st ?: 'pending';
    }

    private function getAbstrakRow(int $idAbstrak): ?array
    {
        return $this->abstrakModel
            ->select('abstrak.id_abstrak, abstrak.file_abstrak')
            ->where('abstrak.id_abstrak', $idAbstrak)
            ->first();
    }

    private function resolveAbsolutePath(?string $stored): ?string
    {
        if (!$stored) return null;
        $rel = trim(str_replace(['\\', '..'], ['/', ''], $stored), '/');
        if (is_file($stored)) return $stored;

        $candidates = [
            rtrim($this->uploadBase, '/\\') . DIRECTORY_SEPARATOR . $rel,
            rtrim($this->uploadBase, '/\\') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $rel,
            rtrim(FCPATH,            '/\\') . DIRECTORY_SEPARATOR . $rel,
            rtrim(FCPATH,            '/\\') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $rel,
            rtrim(WRITEPATH,         '/\\') . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'abstrak' . DIRECTORY_SEPARATOR . basename($rel),
        ];
        foreach ($candidates as $abs) if (is_file($abs)) return $abs;
        return null;
    }

    /* ================= Pages ================= */

    public function index()
    {
        $idReviewer = (int) (session('id_user') ?? 0);
        if (!$idReviewer || session('role') !== 'reviewer') {
            return redirect()->to(site_url('auth/login'));
        }

        $rt    = $this->reviewTable();
        $alias = 'rv';

        $builder = $this->abstrakModel
            ->select("
                abstrak.id_abstrak,
                abstrak.judul,
                abstrak.tanggal_upload,
                abstrak.revisi_ke,
                users.nama_lengkap,
                kategori_abstrak.nama_kategori,
                e.id as event_id,
                e.title as event_title,
                e.event_date,
                e.event_time
            ")
            ->join('users', 'users.id_user = abstrak.id_user')
            ->join('kategori_abstrak', 'kategori_abstrak.id_kategori = abstrak.id_kategori', 'left')
            ->join('events e', 'e.id = abstrak.event_id', 'left');

        if ($rt) {
            $R = $this->reviewCols($rt);

            $builder->join("$rt $alias", "{$alias}.{$R['abstrakFk']} = abstrak.id_abstrak", 'inner')
                    ->where("{$alias}.{$R['reviewer']}", $idReviewer);

            if ($R['type'])  {
                $builder->groupStart()
                        ->where("LOWER({$alias}.{$R['type']})",'abstrak')
                        ->orWhere("{$alias}.{$R['type']}", null)
                        ->groupEnd();
            }
            if ($R['subFk']) $builder->where("{$alias}.{$R['subFk']} IS NULL", null, false);

            // latest row per (abstrak, reviewer)
            $sub = $this->db->table("$rt r1")
                ->select("MAX(r1.{$R['pk']}) AS latest_id")
                ->where("r1.{$R['reviewer']}", $idReviewer);

            if ($R['type']) {
                $sub->groupStart()
                    ->where("LOWER(r1.{$R['type']})", 'abstrak')
                    ->orWhere("r1.{$R['type']}", null)
                    ->groupEnd();
            }
            if ($R['subFk']) $sub->where("r1.{$R['subFk']} IS NULL", null, false);

            $sub->groupBy("r1.{$R['abstrakFk']}");
            $subSql = $sub->getCompiledSelect();

            $builder->join("($subSql) latest", "latest.latest_id = {$alias}.{$R['pk']}", 'inner');

            // only accepted tasks
            if ($R['asgStatus']) {
                $builder->where("LOWER({$alias}.{$R['asgStatus']})", 'accepted');
            }

            // still pending decision
            if ($R['decision']) {
                $builder->groupStart()
                        ->where("{$alias}.{$R['decision']} IS NULL", null, false)
                        ->orWhereIn("LOWER({$alias}.{$R['decision']})", ['','pending','menunggu','sedang_direview','in_review'])
                        ->groupEnd();
                $builder->select("{$alias}.{$R['decision']} AS review_status");
            } else {
                $builder->select("NULL AS review_status", false);
            }
        } else {
            $builder->where('0=1', null, false);
        }

        $rows = $builder
            ->orderBy('e.event_date','DESC')
            ->orderBy('abstrak.tanggal_upload','DESC')
            ->findAll();

        foreach ($rows as &$r) {
            $r['review_status'] = $this->normDecision($r['review_status'] ?? null);
        }
        unset($r);

        $eventOptions = [];
        foreach ($rows as $r) {
            $eid = (int)($r['event_id'] ?? 0);
            if ($eid && !isset($eventOptions[$eid])) $eventOptions[$eid] = $r['event_title'] ?? ('Event #'.$eid);
        }
        ksort($eventOptions);

        return view('role/reviewer/abstrak/index', [
            'title'        => 'Tugas Abstrak',
            'abstrak'      => $rows,
            'eventOptions' => $eventOptions,
        ]);
    }

    public function detail($id)
    {
        $idReviewer = (int) (session('id_user') ?? 0);
        if (!$idReviewer || session('role') !== 'reviewer') {
            return redirect()->to(site_url('auth/login'));
        }

        $idAbstrak = (int)$id;
        $rt = $this->reviewTable();
        $alias = 'rv';

        $abstrak = $this->abstrakModel
            ->select("
                abstrak.*,
                users.nama_lengkap, users.email,
                kategori_abstrak.nama_kategori,
                e.title as event_title, e.id as event_id, e.event_date, e.event_time
            ")
            ->join('users','users.id_user = abstrak.id_user')
            ->join('kategori_abstrak','kategori_abstrak.id_kategori = abstrak.id_kategori','left')
            ->join('events e','e.id = abstrak.event_id','left');

        $taskStatus = 'accepted';
        $taskReason = null; // <-- tambahkan carrier alasan
        $existing   = null;

        if ($rt) {
            $R = $this->reviewCols($rt);

            $abstrak->join("$rt $alias", "{$alias}.{$R['abstrakFk']} = abstrak.id_abstrak", 'inner')
                    ->where("{$alias}.{$R['reviewer']}", $idReviewer);

            if ($R['type'])  $abstrak->groupStart()->where("LOWER({$alias}.{$R['type']})",'abstrak')->orWhere("{$R['type']}", null)->groupEnd();
            if ($R['subFk']) $abstrak->where("{$alias}.{$R['subFk']} IS NULL", null, false);

            $rowAssign  = $this->latestAssignmentRow($idAbstrak, $idReviewer);
            if ($rowAssign && $R['asgStatus']) {
                $taskStatus = strtolower((string)($rowAssign[$R['asgStatus']] ?? 'pending'));
                if (in_array($taskStatus, ['accept','accepted','ok'], true)) $taskStatus = 'accepted';
                if (in_array($taskStatus, ['decline','declined','no'], true)) $taskStatus = 'declined';
            }

            // simpan alasan penolakan bila ada (untuk ditampilkan di view)
            if ($rowAssign && $R['asgReason']) {
                $taskReason = $rowAssign[$R['asgReason']] ?? null;
            }

            if ($rowAssign) {
                $existing = [
                    'id'             => $rowAssign[$R['pk']],
                    'keputusan'      => $R['decision'] ? ($rowAssign[$R['decision']] ?? null) : null,
                    'komentar'       => $R['comment']  ? ($rowAssign[$R['comment']]  ?? null) : null,
                    'tanggal_review' => $R['ts']       ? ($rowAssign[$R['ts']]       ?? null) : null,
                ];
            }
        } else {
            $abstrak->where('0=1', null, false);
        }

        $abstrak = $abstrak->where('abstrak.id_abstrak', $idAbstrak)->first();
        if (!$abstrak) {
            return redirect()->to(site_url('reviewer/abstrak'))
                ->with('error', 'Abstrak tidak ditemukan / bukan tugas Anda.');
        }

        // flag untuk view
        $abstrak['has_file'] = !empty($abstrak['file_abstrak']);

        return view('role/reviewer/abstrak/detail', [
            'title'          => 'Detail Abstrak',
            'abstrak'        => $abstrak,
            'taskStatus'     => $taskStatus,
            'taskReason'     => $taskReason,      // <-- kirim ke view
            'existingReview' => $existing,
            'isReviewed'     => $existing ? $this->isFinalDecision($existing['keputusan'] ?? null) : false,
        ]);
    }

    /* ================= File preview & download ================= */

    public function preview($id)
    {
        $idReviewer = (int) (session('id_user') ?? 0);
        if (!$idReviewer || session('role') !== 'reviewer') {
            return $this->response->setStatusCode(401, 'Unauthorized');
        }

        $idAbstrak = (int)$id;
        $row = $this->getAbstrakRow($idAbstrak);
        if (!$row || empty($row['file_abstrak'])) {
            return $this->response->setStatusCode(404, 'File tidak ditemukan');
        }

        if ($this->getTaskStatus($idAbstrak, $idReviewer) !== 'accepted') {
            return $this->response->setStatusCode(403, 'Tugas belum di-ACC');
        }

        $absolute = $this->resolveAbsolutePath($row['file_abstrak']);
        if (!$absolute) {
            log_message('error', 'Preview file tidak ditemukan di server: ' . $row['file_abstrak']);
            return $this->response->setStatusCode(404, 'File tidak ditemukan di server');
        }

        $ext  = strtolower(pathinfo($absolute, PATHINFO_EXTENSION));
        $name = basename($absolute);
        $mime = $ext === 'pdf' ? 'application/pdf' : mime_content_type($absolute);

        $data = file_get_contents($absolute);
        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Content-Disposition', 'inline; filename="'.$name.'"')
            ->setHeader('Accept-Ranges', 'bytes')
            ->setHeader('Cache-Control', 'no-store, max-age=0')
            ->setBody($data);
    }

    public function blob($id)
    {
        $idReviewer = (int) (session('id_user') ?? 0);
        if (!$idReviewer || session('role') !== 'reviewer') {
            return $this->response->setStatusCode(401, 'Unauthorized');
        }

        $idAbstrak = (int)$id;
        $row = $this->getAbstrakRow($idAbstrak);
        if (!$row || empty($row['file_abstrak'])) {
            return $this->response->setStatusCode(404, 'File tidak ditemukan');
        }

        if ($this->getTaskStatus($idAbstrak, $idReviewer) !== 'accepted') {
            return $this->response->setStatusCode(403, 'Tugas belum di-ACC');
        }

        $absolute = $this->resolveAbsolutePath($row['file_abstrak']);
        if (!$absolute) {
            log_message('error', 'Blob file tidak ditemukan di server: ' . $row['file_abstrak']);
            return $this->response->setStatusCode(404, 'File tidak ditemukan di server');
        }

        $ext  = strtolower(pathinfo($absolute, PATHINFO_EXTENSION));
        $mime = $ext === 'pdf' ? 'application/pdf' : mime_content_type($absolute);

        return $this->response
            ->setHeader('Content-Type', $mime)
            ->setHeader('Cache-Control','no-store, max-age=0')
            ->setBody(file_get_contents($absolute));
    }

    public function download($id)
    {
        $idReviewer = (int) (session('id_user') ?? 0);
        if (!$idReviewer || session('role') !== 'reviewer') {
            return $this->response->setStatusCode(401, 'Unauthorized');
        }

        $idAbstrak = (int)$id;
        $row = $this->getAbstrakRow($idAbstrak);
        if (!$row || empty($row['file_abstrak'])) {
            return $this->response->setStatusCode(404, 'File tidak ditemukan');
        }

        if ($this->getTaskStatus($idAbstrak, $idReviewer) !== 'accepted') {
            return $this->response->setStatusCode(403, 'Tugas belum di-ACC');
        }

        $absolute = $this->resolveAbsolutePath($row['file_abstrak']);
        if (!$absolute) {
            log_message('error', 'Download file tidak ditemukan di server: ' . $row['file_abstrak']);
            return $this->response->setStatusCode(404, 'File tidak ditemukan di server');
        }

        return $this->response->download($absolute, null)->setFileName(basename($absolute));
    }

    /* ================= Actions ================= */

    public function review($id)
    {
        $idReviewer = (int) (session('id_user') ?? 0);
        if (!$idReviewer || session('role') !== 'reviewer') {
            return redirect()->to(site_url('auth/login'));
        }

        $idAbstrak = (int)$id;
        if ($this->getTaskStatus($idAbstrak, $idReviewer) !== 'accepted') {
            return redirect()->back()->with('error','Tugas belum di-ACC atau sudah ditolak.')->withInput();
        }

        $raw       = strtolower((string)$this->request->getPost('keputusan'));
        $komentar  = trim((string)$this->request->getPost('komentar'));

        if (in_array($raw, ['diterima','accept','accepted'], true))      $keputusan = 'diterima';
        elseif (in_array($raw, ['revisi','revision','revise'], true))    $keputusan = 'revisi';
        elseif (in_array($raw, ['ditolak','reject','rejected'], true))   $keputusan = 'ditolak';
        else return redirect()->back()->with('error','Keputusan tidak valid.')->withInput();

        // === VALIDASI KOMENTAR (disesuaikan dgn view) ===
        $minLen = ($keputusan === 'ditolak') ? 10 : 5;
        if (mb_strlen($komentar) < $minLen) {
            $msg = ($keputusan === 'ditolak')
                ? 'Penolakan wajib disertai alasan yang jelas (minimal 10 karakter).'
                : 'Komentar minimal 5 karakter.';
            return redirect()->back()->with('error', $msg)->withInput();
        }

        $rt = $this->reviewTable();
        if (!$rt) return redirect()->back()->with('error','Tabel review tidak ditemukan.');
        $R  = $this->reviewCols($rt);

        $row = $this->latestAssignmentRow($idAbstrak, $idReviewer);
        if (!$row) return redirect()->back()->with('error','Baris tugas tidak ditemukan.');

        $payload = [
            ($R['decision'] ?? 'keputusan')      => $keputusan,
            ($R['ts']       ?? 'tanggal_review') => date('Y-m-d H:i:s'),
        ];

        $commentCol = $R['comment'] ?? null;
        if ($commentCol) {
            $payload[$commentCol] = $komentar;
        } else {
            if ($this->hasCol($rt, 'komentar')) $payload['komentar'] = $komentar;
        }

        if ($R['revNum']) {
            $revNow = (int)($this->abstrakModel->select('revisi_ke')->where('id_abstrak',$idAbstrak)->first()['revisi_ke'] ?? 0);
            $payload[$R['revNum']] = $revNow;
        }

        $ok = (bool)$this->db->table($rt)->where($R['pk'], $row[$R['pk']])->update($payload);
        if (!$ok) {
            return redirect()->back()->with('error','Gagal menyimpan review.')->withInput();
        }

        if ($this->hasCol('abstrak', 'status')) {
            $this->abstrakModel->update($idAbstrak, ['status' => $keputusan]);
        }

        return redirect()->to(site_url('reviewer/abstrak'))->with('success','Review tersimpan. Tugas dipindah ke riwayat.');
    }

    public function confirm($id)
    {
        $idReviewer = (int) (session('id_user') ?? 0);
        if (!$idReviewer || session('role') !== 'reviewer') {
            return redirect()->to(site_url('auth/login'));
        }

        $idAbstrak = (int)$id;
        $action = strtolower((string)$this->request->getPost('action'));
        $reason = trim((string)$this->request->getPost('reason'));

        if (!in_array($action, ['accept','decline'], true)) {
            return redirect()->back()->with('error','Aksi tidak valid.');
        }
        if ($action === 'decline' && mb_strlen($reason) < 5) {
            return redirect()->back()->with('error','Alasan penolakan minimal 5 karakter.');
        }

        $rt = $this->reviewTable();
        if (!$rt) return redirect()->back()->with('error','Fitur konfirmasi tugas belum aktif.');
        $R  = $this->reviewCols($rt);

        $row = $this->latestAssignmentRow($idAbstrak, $idReviewer);
        if (!$row) return redirect()->to(site_url('reviewer/abstrak'))->with('error','Tugas tidak ditemukan.');

        if ($action === 'decline') {
            $this->db->transStart();
            $ok = (bool)$this->db->table($rt)->where($R['pk'], $row[$R['pk']])->delete();
            if ($this->hasCol('abstrak','status')) {
                $this->abstrakModel->update($idAbstrak, ['status' => 'menunggu']);
            }
            $this->db->transComplete();

            return $ok
                ? redirect()->to(site_url('reviewer/abstrak'))->with('success','Tugas ditolak & dilepas. Admin dapat assign ulang.')
                : redirect()->back()->with('error','Gagal melepaskan tugas.');
        }

        if ($R['asgStatus']) {
            $payload = [ $R['asgStatus'] => 'accepted' ];
            if ($R['asgReason']) $payload[$R['asgReason']] = null;
            if ($R['asgAccAt'])  $payload[$R['asgAccAt']]  = date('Y-m-d H:i:s');

            $ok = (bool)$this->db->table($rt)->where($R['pk'],$row[$R['pk']])->update($payload);
            return $ok
                ? redirect()->to(site_url('reviewer/abstrak/'.$idAbstrak))->with('success','Tugas diterima.')
                : redirect()->back()->with('error','Gagal memperbarui status tugas.');
        }

        return redirect()->to(site_url('reviewer/abstrak/'.$idAbstrak))->with('success','Tugas diterima.');
    }

    public function undo($id)
    {
        $idReviewer = (int) (session('id_user') ?? 0);
        if (!$idReviewer || session('role') !== 'reviewer') {
            return redirect()->to(site_url('auth/login'));
        }

        $idAbstrak = (int)$id;
        $rt = $this->reviewTable();
        if (!$rt) return redirect()->back()->with('error','Tabel review tidak ditemukan.');
        $R  = $this->reviewCols($rt);

        $row = $this->latestAssignmentRow($idAbstrak, $idReviewer);
        if (!$row) return redirect()->back()->with('error','Tugas tidak ditemukan.');

        $payload = [];
        if ($R['decision']) $payload[$R['decision']] = 'pending';
        if ($R['ts'])       $payload[$R['ts']]       = date('Y-m-d H:i:s');

        if (empty($payload)) return redirect()->back()->with('error','Skema tabel tidak mendukung undo.');

        $ok = (bool)$this->db->table($rt)->where($R['pk'], $row[$R['pk']])->update($payload);
        if ($ok && $this->hasCol('abstrak','status')) {
            $this->abstrakModel->update($idAbstrak, ['status' => 'sedang_direview']);
        }

        return $ok
            ? redirect()->to(site_url('reviewer/abstrak/'.$idAbstrak))->with('success','Keputusan dibatalkan. Status kembali pending.')
            : redirect()->back()->with('error','Gagal membatalkan keputusan.');
    }
}
