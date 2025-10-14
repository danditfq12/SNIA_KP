<?php

namespace App\Controllers\Role\Reviewer;

use App\Controllers\BaseController;

class Riwayat extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /* ==================== Helpers: auth & generic ==================== */
    private function requireReviewer(): bool
    {
        $id = (int) (session('id_user') ?? 0);
        return $id && session('role') === 'reviewer';
    }
    private function me(): int
    {
        if (function_exists('user') && user()) return (int) user()->id;
        return (int) (session('id_user') ?? 0);
    }

    /* ==== Tabel & kolom dinamis agar tahan skema beda-beda ==== */

    /** Abstrak review table candidates: reviews|review */
    private function abstrakReviewTable(): ?string
    {
        if ($this->db->tableExists('reviews')) return 'reviews';
        if ($this->db->tableExists('review'))  return 'review';
        return null;
    }

    /** Full paper review table */
    private function fpReviewTable(): ?string
    {
        return $this->db->tableExists('fullpaper_reviews') ? 'fullpaper_reviews' : null;
    }

    /** Submissions table (fallback ke abstrak kalau tak ada) */
    private function submissionTable(): string
    {
        if ($this->db->tableExists('submissions')) return 'submissions';
        if ($this->db->tableExists('abstrak'))     return 'abstrak';
        throw new \RuntimeException('Tabel submissions/abstrak tidak ditemukan.');
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

    private function abstrakReviewCols(string $rt): array
    {
        return [
            'pk'        => $this->pickCol($rt, ['id','id_review']) ?? 'id',
            'reviewer'  => $this->pickCol($rt, ['id_reviewer','reviewer_id','user_id']) ?? 'id_reviewer',
            'abstrakId' => $this->pickCol($rt, ['id_abstrak']) ?? 'id_abstrak',
            'decision'  => $this->pickCol($rt, ['keputusan','decision','status']) ?? 'keputusan',
            'comment'   => $this->pickCol($rt, ['komentar','comment','notes','catatan']) ?? 'komentar',
            'ts'        => $this->pickCol($rt, ['tanggal_review','updated_at','created_at']) ?? 'tanggal_review',
            'type'      => $this->pickCol($rt, ['type','review_type']), // opsional (abstrak/fullpaper)
            'subFk'     => $this->pickCol($rt, ['id_submission','submission_id']), // opsional (NULL untuk abstrak)
            // kolom status penugasan (wajib accepted untuk masuk riwayat)
            'asgStatus' => $this->pickCol($rt, ['status_tugas','tugas_status','assignment_status','konfirmasi_status']),
        ];
    }

    private function fpReviewCols(string $rt): array
    {
        return [
            'pk'        => $this->pickCol($rt, ['id']) ?? 'id',
            'reviewer'  => $this->pickCol($rt, ['reviewer_id','id_reviewer']) ?? 'reviewer_id',
            'submId'    => $this->pickCol($rt, ['submission_id','id_submission']) ?? 'submission_id',
            'decision'  => $this->pickCol($rt, ['keputusan','decision','status']) ?? 'keputusan',
            'comment'   => $this->pickCol($rt, ['komentar','comment','notes','catatan']) ?? 'komentar',
            'ts'        => $this->pickCol($rt, ['tanggal_review','updated_at','created_at']) ?? 'tanggal_review',
            // kolom status penugasan (wajib accepted untuk masuk riwayat)
            'asgStatus' => $this->pickCol($rt, ['status_tugas','tugas_status','assignment_status','konfirmasi_status']),
            // kalau ada type, amankan ke 'fullpaper'
            'type'      => $this->pickCol($rt, ['type','review_type']),
        ];
    }

    private function submissionCols(string $table): array
    {
        $pk    = $this->pickCol($table, ['id','id_submission','id_abstrak']) ?? 'id';
        $title = $this->pickCol($table, ['title','judul','judul_paper','judul_penelitian','judul_abstrak','nama']) ?? $pk;
        $event = $this->pickCol($table, ['event_id','id_event','events_id']);
        $user  = $this->pickCol($table, ['id_user','user_id']);
        $status= $this->pickCol($table, ['full_paper_status','status']);
        $path  = $this->pickCol($table, ['full_paper_path','file_abstrak','file_path']);
        $ts    = $this->pickCol($table, ['full_paper_uploaded_at','tanggal_upload','uploaded_at']);

        return [
            'pk'    => $pk,
            'title' => $title,
            'event' => $event,
            'user'  => $user,
            'status'=> $status,
            'path'  => $path,
            'ts'    => $ts,
        ];
    }

    /** Normalisasi keputusan */
    private function normDecision(?string $v): string
    {
        $k = strtolower((string)$v);
        if (in_array($k, ['accepted','diterima','accept','ok','yes'], true)) return 'diterima';
        if (in_array($k, ['revision','revisi'], true))                         return 'revisi';
        if (in_array($k, ['rejected','ditolak','reject','no'], true))          return 'ditolak';
        return 'pending';
    }

    /** Normalisasi status tugas → accepted|declined|pending */
    private function normTask(?string $v): string
    {
        $k = strtolower((string)$v);
        if (in_array($k, ['accept','accepted','ok','yes'], true)) return 'accepted';
        if (in_array($k, ['decline','declined','no','rejected_task'], true)) return 'declined';
        return 'pending';
    }

    /** Join bantu: dari submissions → ambil kategori & penulis via tabel abstrak + users + events */
    private function applyAbstractJoins(\CodeIgniter\Database\BaseBuilder $b, string $subTable, array $S): void
    {
        if ($S['event'] && $S['user'] && $this->db->tableExists('abstrak')) {
            $on = "a.event_id = s.{$S['event']} AND a.id_user = s.{$S['user']}";
            $b->join('abstrak a', $on, 'left');

            if ($this->db->tableExists('kategori_abstrak')) {
                $af = array_flip($this->db->getFieldNames('abstrak') ?: []);
                $colKat = isset($af['id_kategori']) ? 'id_kategori' : (isset($af['kategori_id']) ? 'kategori_id' : null);
                if ($colKat) {
                    $b->join('kategori_abstrak ka', "ka.id_kategori = a.$colKat", 'left')
                      ->select('ka.nama_kategori');
                }
            }
            if ($this->db->tableExists('users')) {
                $b->join('users u', "u.id_user = a.id_user", 'left')
                  ->select('u.nama_lengkap, u.email');
            }
        }

        if ($this->db->tableExists('events')) {
            $ef = array_flip($this->db->getFieldNames('events') ?: []);
            $eid = isset($ef['id']) ? 'id' : (isset($ef['event_id']) ? 'event_id' : null);
            $etitle = isset($ef['title']) ? 'title' : (isset($ef['nama']) ? 'nama' : null);
            if ($eid && $etitle) {
                $b->join('events e', "e.$eid = s.{$S['event']}", 'left')
                  ->select("e.$eid AS event_id, e.$etitle AS event_title");
            }
        }
    }

    /* ==================== Data fetchers (dedup) ==================== */

    /** Riwayat Abstrak (dedup per (event_id, id_user)) — hanya tugas ACC */
    private function fetchAbstrakHistory(int $me): array
    {
        $rt = $this->abstrakReviewTable();
        if (!$rt) return [];

        $R = $this->abstrakReviewCols($rt);

        $b = $this->db->table("$rt r")
            ->where("r.{$R['reviewer']}", $me)
            ->where("r.{$R['decision']} IS NOT NULL", null, false);

        // Wajib: hanya penugasan yang sudah accepted
        if ($R['asgStatus']) {
            $b->groupStart()
                ->where("LOWER(r.{$R['asgStatus']})", 'accepted')
            ->groupEnd();
        }

        // Amankan type untuk abstrak (jika ada)
        if ($R['type']) {
            $b->groupStart()
                ->where("LOWER(r.{$R['type']})", 'abstrak')
                ->orWhere("r.{$R['type']}", null)
            ->groupEnd();
        }

        // Jika ada kolom subFk (submission_id), pastikan NULL (khusus abstrak)
        if ($R['subFk']) {
            $b->where("r.{$R['subFk']} IS NULL", null, false);
        }

        $b->join('abstrak a', "a.id_abstrak = r.{$R['abstrakId']}", 'left')
          ->select("
                r.{$R['pk']}            AS id,
                r.{$R['decision']}      AS keputusan,
                r.{$R['comment']}       AS komentar,
                r.{$R['ts']}            AS tanggal_review,
                a.id_abstrak            AS id_abstrak,
                a.judul                 AS judul,
                a.tanggal_upload        AS tanggal_upload,
                a.id_user               AS presenter_id,
                a.event_id              AS event_id
          ");

        $b->join('users u', 'u.id_user = a.id_user', 'left')
          ->select('u.nama_lengkap, u.email');

        if ($this->db->tableExists('kategori_abstrak')) {
            $b->join('kategori_abstrak ka', 'ka.id_kategori = a.id_kategori', 'left')
              ->select('ka.nama_kategori');
        }
        if ($this->db->tableExists('events')) {
            $b->join('events e', 'e.id = a.event_id', 'left')
              ->select('e.title AS event_title');
        }

        $rows = $b->orderBy("r.{$R['ts']}", 'DESC')->get()->getResultArray();

        // Dedup per (event_id, presenter_id): keep latest
        $keyed = [];
        foreach ($rows as $row) {
            $ev = (string)($row['event_id'] ?? '0');
            $pr = (string)($row['presenter_id'] ?? '0');
            $key = $ev . ':' . $pr;
            $curTs = strtotime($row['tanggal_review'] ?? '1970-01-01 00:00:00');
            if (!isset($keyed[$key]) || $curTs > strtotime($keyed[$key]['tanggal_review'] ?? '1970-01-01 00:00:00')) {
                $row['keputusan'] = $this->normDecision($row['keputusan'] ?? null);
                $keyed[$key] = $row;
            }
        }
        usort($keyed, fn($a,$b)=>strtotime($b['tanggal_review'])<=>strtotime($a['tanggal_review']));
        return array_values($keyed);
    }

    /** Riwayat Full Paper (dedup per (event_id, id_user)) — hanya tugas ACC */
    private function fetchFullpaperHistory(int $me): array
    {
        $rt = $this->fpReviewTable();
        if (!$rt) return [];

        $R  = $this->fpReviewCols($rt);
        $st = $this->submissionTable();
        $S  = $this->submissionCols($st);

        $b = $this->db->table("$rt r")
            ->where("r.{$R['reviewer']}", $me)
            ->where("r.{$R['decision']} IS NOT NULL", null, false);

        // Wajib: hanya penugasan yang sudah accepted
        if ($R['asgStatus']) {
            $b->groupStart()
                ->where("LOWER(r.{$R['asgStatus']})", 'accepted')
            ->groupEnd();
        }

        // Amankan type untuk fullpaper (jika ada)
        if ($R['type']) {
            $b->groupStart()
                ->where("LOWER(r.{$R['type']})", 'fullpaper')
                ->orWhere("r.{$R['type']}", null)
            ->groupEnd();
        }

        $b->join("$st s", "s.{$S['pk']} = r.{$R['submId']}", 'left')
          ->select("
                r.{$R['pk']}            AS id,
                r.{$R['decision']}      AS keputusan,
                r.{$R['comment']}       AS komentar,
                r.{$R['ts']}            AS tanggal_review,
                s.{$S['pk']}            AS submission_id,
                s.{$S['title']}         AS judul,
                s.{$S['ts']}            AS tanggal_upload,
                s.{$S['user']}          AS presenter_id,
                s.{$S['event']}         AS event_id
          ");

        // kategori + penulis via abstrak (jika tersedia)
        if ($S['event'] && $S['user'] && $this->db->tableExists('abstrak')) {
            $on = "a.event_id = s.{$S['event']} AND a.id_user = s.{$S['user']}";
            $b->join('abstrak a', $on, 'left');
            if ($this->db->tableExists('kategori_abstrak')) {
                $af = array_flip($this->db->getFieldNames('abstrak') ?: []);
                $colKat = isset($af['id_kategori']) ? 'id_kategori' : (isset($af['kategori_id']) ? 'kategori_id' : null);
                if ($colKat) {
                    $b->join('kategori_abstrak ka', "ka.id_kategori = a.$colKat", 'left')
                      ->select('ka.nama_kategori');
                }
            }
        }
        if ($this->db->tableExists('users')) {
            $b->join('users u', "u.id_user = s.{$S['user']}", 'left')
              ->select('u.nama_lengkap, u.email');
        }
        if ($this->db->tableExists('events')) {
            $ef = array_flip($this->db->getFieldNames('events') ?: []);
            $eid = isset($ef['id']) ? 'id' : (isset($ef['event_id']) ? 'event_id' : null);
            $etitle = isset($ef['title']) ? 'title' : (isset($ef['nama']) ? 'nama' : null);
            if ($eid && $etitle) {
                $b->join('events e', "e.$eid = s.{$S['event']}", 'left')
                  ->select("e.$etitle AS event_title");
            }
        }

        $rows = $b->orderBy("r.{$R['ts']}", 'DESC')->get()->getResultArray();

        // Dedup per (event_id, presenter_id)
        $keyed = [];
        foreach ($rows as $row) {
            $ev = (string)($row['event_id'] ?? '0');
            $pr = (string)($row['presenter_id'] ?? '0');
            $key = $ev . ':' . $pr;
            $curTs = strtotime($row['tanggal_review'] ?? '1970-01-01 00:00:00');
            if (!isset($keyed[$key]) || $curTs > strtotime($keyed[$key]['tanggal_review'] ?? '1970-01-01 00:00:00')) {
                $row['keputusan'] = $this->normDecision($row['keputusan'] ?? null);
                $keyed[$key] = $row;
            }
        }
        usort($keyed, fn($a,$b)=>strtotime($b['tanggal_review'])<=>strtotime($a['tanggal_review']));
        return array_values($keyed);
    }

    /* ==================== PAGE ==================== */

    public function index()
    {
        if (!$this->requireReviewer()) return redirect()->to(site_url('auth/login'));
        $me = $this->me();

        // Abstrak & Fullpaper dipisah
        $riwayatAbstrak   = $this->fetchAbstrakHistory($me);
        $riwayatFullpaper = $this->fetchFullpaperHistory($me);

        return view('role/reviewer/riwayat', [
            'title'            => 'Riwayat Review',
            'riwayatAbstrak'   => $riwayatAbstrak,
            'riwayatFullpaper' => $riwayatFullpaper,
        ]);
    }
}
