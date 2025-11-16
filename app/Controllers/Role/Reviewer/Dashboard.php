<?php
namespace App\Controllers\Role\Reviewer;

use App\Controllers\BaseController;

class Dashboard extends BaseController
{
    protected $db;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
    }

    /* ===================== Helpers umum ===================== */

    private function meId(): int
    {
        // sesuaikan dengan session anda
        return (int) (session('id_user') ?? session('user_id') ?? 0);
    }

    private function col(string $table, array $cands): ?string
    {
        if (!$this->db->tableExists($table)) return null;
        $fields = array_flip($this->db->getFieldNames($table) ?: []);
        foreach ($cands as $c) {
            if (isset($fields[$c])) return $c;
        }
        return null;
    }

    private function hasCol(string $table, string $col): bool
    {
        if (!$this->db->tableExists($table)) return false;
        return in_array($col, $this->db->getFieldNames($table), true);
    }

    private function eventMetaById(?int $eventId): array
    {
        if (!$eventId || !$this->db->tableExists('events')) {
            return ['title' => null, 'date' => null];
        }

        $t   = 'events';
        $pk  = $this->col($t, ['id','id_event','event_id']) ?? 'id';
        $ttl = $this->col($t, ['title','nama','name']) ?? 'title';
        $dt  = $this->col($t, ['event_date','tanggal','date']);

        $row = $this->db->table($t)
            ->select($ttl . ($dt ? ", $dt AS tanggal" : ''))
            ->where($pk, $eventId)
            ->get()->getRowArray();

        return [
            'title' => $row[$ttl] ?? null,
            'date'  => $dt ? ($row['tanggal'] ?? null) : null,
        ];
    }

    private function eventTitleById(?int $eventId): ?string
    {
        return $this->eventMetaById($eventId)['title'];
    }

    /* ===================== Helpers khusus ABSTRAK (review/reviews) ===================== */

    private function reviewTable(): ?string
    {
        if ($this->db->tableExists('review'))  return 'review';
        if ($this->db->tableExists('reviews')) return 'reviews';
        return null;
    }

    private function reviewCols(string $rt): array
    {
        // deteksi kolom2 penting di tabel review
        $pk        = $this->col($rt, ['id','id_review']) ?? 'id';
        $reviewer  = $this->col($rt, ['id_reviewer','reviewer_id','id_user','user_id']) ?? 'id_reviewer';
        $abstrakFk = $this->col($rt, ['id_abstrak','abstrak_id']) ?? 'id_abstrak';

        $asgStatus = $this->col($rt, ['assignment_status','status_tugas','tugas_status','konfirmasi_status','status']);
        $asgAccAt  = $this->col($rt, ['accepted_at','confirmed_at','konfirmasi_at']);
        $asgDecAt  = $this->col($rt, ['declined_at','rejected_at']);
        $asgReason = $this->col($rt, ['decline_reason','alasan','alasan_tolak','reason']);

        // kolom keputusan final (untuk hitung "tugas selesai")
        $decision  = $this->col($rt, ['keputusan','decision','status_review']);

        return compact('pk','reviewer','abstrakFk','asgStatus','asgAccAt','asgDecAt','asgReason','decision');
    }

    /* ===================== Helpers review FULLPAPER ===================== */

    private function fullpaperReviewTable(): ?string
    {
        foreach (['fullpaper_reviews','fullpaper_review','fp_review','review_fullpaper'] as $t) {
            if ($this->db->tableExists($t)) return $t;
        }
        return null;
    }

    private function fullpaperReviewCols(string $t): array
    {
        $pk       = $this->col($t, ['id','id_review']) ?? 'id';
        $subFk    = $this->col($t, ['submission_id','id_submission','fullpaper_id','id_fullpaper']) ?? 'submission_id';
        $reviewer = $this->col($t, ['reviewer_id','id_reviewer','user_id','id_user']) ?? 'reviewer_id';
        $decision = $this->col($t, ['keputusan','decision','status_review']);
        return compact('pk','subFk','reviewer','decision');
    }

    /* ===================== INCOMING: Abstrak ===================== */

    private function incomingAbstrak(int $rid): array
    {
        $rt = $this->reviewTable();
        if (!$rt) return [];

        $R = $this->reviewCols($rt);

        if (!$this->db->tableExists('abstrak')) return [];
        $aPk   = $this->col('abstrak', ['id_abstrak','id']) ?? 'id_abstrak';
        $aEvt  = $this->col('abstrak', ['event_id','id_event']);
        $aJud  = $this->col('abstrak', ['judul','title','judul_abstrak']) ?? 'judul';
        $aUpAt = $this->col('abstrak', ['tanggal_upload','uploaded_at','created_at']) ?? 'tanggal_upload';

        $b = $this->db->table("$rt r")
            ->select("a.$aPk AS id_abstrak, a.$aJud AS judul, a.$aUpAt AS tanggal_upload" . ($aEvt ? ", a.$aEvt AS event_id" : ''))
            ->join("abstrak a", "a.$aPk = r.{$R['abstrakFk']}", 'inner')
            ->where("r.{$R['reviewer']}", $rid)
            ->where("r.{$R['abstrakFk']} IS NOT NULL", null, false);

        // filter hanya yang BELUM dikonfirmasi (assignment)
        if ($R['asgStatus']) {
            $col = "r.{$R['asgStatus']}";
            $b->groupStart()
                ->where("$col IS NULL", null, false)
                ->orWhereIn("LOWER($col)", ['pending','requested','assigned','awaiting','waiting','new'])
              ->groupEnd();
        }
        if ($R['asgAccAt']) $b->where("r.{$R['asgAccAt']} IS NULL", null, false);
        if ($R['asgDecAt']) $b->where("r.{$R['asgDecAt']} IS NULL", null, false);

        $rows = $b->orderBy("a.$aUpAt",'DESC')->get()->getResultArray();

        foreach ($rows as &$r) {
            $r['task_type']   = 'abstrak';
            $r['event_title'] = $this->eventTitleById((int)($r['event_id'] ?? 0)) ?: '—';
        }
        return $rows;
    }

    /* ===================== INCOMING: Fullpaper ===================== */

    private function incomingFullpaper(int $rid): array
    {
        if (!$this->db->tableExists('fullpaper_reviewers')) return [];

        $pvt     = 'fullpaper_reviewers';
        $colSub  = $this->col($pvt, ['submission_id','id_submission','fullpaper_id','id_fullpaper','abstrak_id','id_abstrak']) ?? 'submission_id';
        $colRev  = $this->col($pvt, ['reviewer_id','id_reviewer','user_id','id_user']) ?? 'reviewer_id';
        $colStat = $this->col($pvt, ['assignment_status','status_tugas','tugas_status','konfirmasi_status']);
        $accAt   = $this->col($pvt, ['accepted_at','confirmed_at','konfirmasi_at']);
        $decAt   = $this->col($pvt, ['declined_at','rejected_at']);

        $carrier = $this->db->tableExists('submissions') ? 'submissions'
                  : ($this->db->tableExists('abstrak') ? 'abstrak' : null);
        if (!$carrier) return [];

        $pk   = $this->col($carrier, $carrier==='submissions' ? ['id','id_submission','submission_id'] : ['id_abstrak','id']) ?? 'id';
        $jud  = $this->col($carrier, ['judul','title','judul_paper','judul_penelitian','judul_abstrak']) ?? $pk;
        $evt  = $this->col($carrier, ['event_id','id_event','events_id']);
        $upAt = $this->col($carrier, ['full_paper_uploaded_at','tanggal_upload','uploaded_at','created_at'])
              ?? ($carrier==='submissions' ? 'full_paper_uploaded_at' : 'tanggal_upload');

        $b = $this->db->table("$pvt fr")
            ->select("c.$pk AS id_submission, c.$jud AS judul, c.$upAt AS tanggal_upload" . ($evt ? ", c.$evt AS event_id" : ''))
            ->join("$carrier c", "c.$pk = fr.$colSub", 'inner')
            ->where("fr.$colRev", $rid);

        if ($colStat) {
            $b->groupStart()
                 ->where("fr.$colStat IS NULL", null, false)
                 ->orWhereIn("LOWER(fr.$colStat)", ['pending','requested','assigned','awaiting','waiting','new'])
              ->groupEnd();
        }
        if ($accAt) $b->where("fr.$accAt IS NULL", null, false);
        if ($decAt) $b->where("fr.$decAt IS NULL", null, false);

        if ($carrier === 'submissions' && $this->hasCol('submissions','full_paper_path')) {
            $b->where("c.full_paper_path IS NOT NULL", null, false)
              ->where("c.full_paper_path <>", '');
        }

        $rows = $b->orderBy("c.$upAt",'DESC')->get()->getResultArray();

        foreach ($rows as &$r) {
            $r['task_type']   = 'fullpaper';
            $r['event_title'] = $this->eventTitleById((int)($r['event_id'] ?? 0)) ?: '—';
        }
        return $rows;
    }

    /* ===================== Hitung tugas SELESAI (sudah ada keputusan final) ===================== */

    private function countDoneAbstrak(int $rid): int
    {
        $rt = $this->reviewTable();
        if (!$rt) return 0;
        $R = $this->reviewCols($rt);
        if (!$R['abstrakFk'] || !$R['decision']) return 0;

        $final = ['diterima','ditolak','revisi','accepted','rejected','revision'];

        return $this->db->table($rt)
            ->where($R['reviewer'], $rid)
            ->where("{$R['abstrakFk']} IS NOT NULL", null, false)
            ->whereIn("LOWER({$R['decision']})", $final)
            ->countAllResults();
    }

    private function countDoneFullpaper(int $rid): int
    {
        $rt = $this->fullpaperReviewTable();
        if (!$rt) return 0;
        $R = $this->fullpaperReviewCols($rt);
        if (!$R['decision']) return 0;

        $final = ['diterima','ditolak','revisi','accepted','rejected','revision'];

        return $this->db->table($rt)
            ->where($R['reviewer'], $rid)
            ->whereIn("LOWER({$R['decision']})", $final)
            ->countAllResults();
    }

    /* ===================== Build reminder activities ===================== */

    private function buildReminderActivities(int $rid): array
    {
        $activities = [];
        $finalDec   = ['diterima','ditolak','revisi','accepted','rejected','revision'];

        /* --- 1) Abstrak yang SUDAH DITERIMA tapi BELUM ada keputusan final --- */
        $rt = $this->reviewTable();
        if ($rt && $this->db->tableExists('abstrak')) {
            $R = $this->reviewCols($rt);
            if ($R['abstrakFk']) {
                $aPk   = $this->col('abstrak', ['id_abstrak','id']) ?? 'id_abstrak';
                $aEvt  = $this->col('abstrak', ['event_id','id_event']);
                $aJud  = $this->col('abstrak', ['judul','title','judul_abstrak']) ?? 'judul';
                $aUpAt = $this->col('abstrak', ['tanggal_upload','uploaded_at','created_at']) ?? 'tanggal_upload';

                $b = $this->db->table("$rt r")
                    ->select("a.$aPk AS id_abstrak, a.$aJud AS judul, a.$aUpAt AS tanggal_upload" . ($aEvt ? ", a.$aEvt AS event_id" : ''))
                    ->join("abstrak a", "a.$aPk = r.{$R['abstrakFk']}", 'inner')
                    ->where("r.{$R['reviewer']}", $rid)
                    ->where("r.{$R['abstrakFk']} IS NOT NULL", null, false);

                // hanya assignment yang SUDAH accepted
                if ($R['asgStatus']) {
                    $col = "LOWER(r.{$R['asgStatus']})";
                    $b->whereIn($col, ['accepted','diterima']);
                } elseif ($R['asgAccAt']) {
                    $b->where("r.{$R['asgAccAt']} IS NOT NULL", null, false);
                }

                // tapi BELUM ada keputusan final di kolom decision
                if ($R['decision']) {
                    $col = "LOWER(r.{$R['decision']})";
                    $b->groupStart()
                        ->where("r.{$R['decision']} IS NULL", null, false)
                        ->orWhereNotIn($col, $finalDec)
                      ->groupEnd();
                }

                $rows = $b->orderBy("a.$aUpAt",'DESC')->get()->getResultArray();

                foreach ($rows as $r) {
                    $eventId = (int)($r['event_id'] ?? 0);
                    $event   = $this->eventMetaById($eventId);
                    $deadline= $event['date'] ?? null;

                    $badge = 'info';
                    $time  = null;
                    $deadlineText = 'Tenggat belum ditentukan';

                    if ($deadline) {
                        $ts  = strtotime($deadline);
                        $now = time();
                        $diffDays = (int)floor(($ts - $now) / 86400);
                        $time = $ts;

                        if     ($diffDays <= 1)  $badge = 'danger';
                        elseif ($diffDays <= 7)  $badge = 'warning';

                        $deadlineText = 'Tenggat event: ' . date('d M Y', $ts) . " ({$diffDays} hari lagi)";
                    }

                    $activities[] = [
                        'type'  => 'abstrak_deadline',
                        'title' => 'Segera review abstrak',
                        'desc'  => ($event['title'] ? '['.$event['title'].'] ' : '') .
                                   ($r['judul'] ?? '(Tanpa judul)') . '. ' . $deadlineText,
                        'badge' => $badge,
                        'icon'  => 'bi-journal-text',
                        'time'  => $time,
                        'link'  => site_url('reviewer/tugas/abstrak'),
                    ];
                }
            }
        }

        /* --- 2) Full Paper yang SUDAH DITERIMA tapi BELUM keputusan final --- */
        if ($this->db->tableExists('fullpaper_reviewers')) {
            $pvt     = 'fullpaper_reviewers';
            $colSub  = $this->col($pvt, ['submission_id','id_submission','fullpaper_id','id_fullpaper','abstrak_id','id_abstrak']) ?? 'submission_id';
            $colRev  = $this->col($pvt, ['reviewer_id','id_reviewer','user_id','id_user']) ?? 'reviewer_id';
            $colStat = $this->col($pvt, ['assignment_status','status_tugas','tugas_status','konfirmasi_status']);

            $carrier = $this->db->tableExists('submissions') ? 'submissions'
                      : ($this->db->tableExists('abstrak') ? 'abstrak' : null);

            if ($carrier) {
                $pk   = $this->col($carrier, $carrier==='submissions' ? ['id','id_submission','submission_id'] : ['id_abstrak','id']) ?? 'id';
                $jud  = $this->col($carrier, ['judul','title','judul_paper','judul_penelitian','judul_abstrak']) ?? $pk;
                $evt  = $this->col($carrier, ['event_id','id_event','events_id']);
                $upAt = $this->col($carrier, ['full_paper_uploaded_at','tanggal_upload','uploaded_at','created_at'])
                      ?? ($carrier==='submissions' ? 'full_paper_uploaded_at' : 'tanggal_upload');
                $statusCol = $this->col($carrier, ['full_paper_status','status']);

                $b = $this->db->table("$pvt p")
                    ->select("p.$colSub AS id_submission, p.id AS pivot_id,
                              c.$jud AS judul, c.$upAt AS tanggal_upload"
                             . ($evt ? ", c.$evt AS event_id" : '')
                             . ($statusCol ? ", c.$statusCol AS fp_status" : ''))
                    ->join("$carrier c", "c.$pk = p.$colSub", 'inner')
                    ->where("p.$colRev", $rid);

                // hanya assignment yang SUDAH accepted
                if ($colStat) {
                    $b->whereIn("LOWER(p.$colStat)", ['accepted','diterima']);
                }

                // join ke tabel fullpaper_reviews (kalau ada) untuk cek sudah final / belum
                $frt = $this->fullpaperReviewTable();
                $FR  = $frt ? $this->fullpaperReviewCols($frt) : null;
                if ($frt && $FR['decision']) {
                    $b->join("$frt fr", "fr.{$FR['subFk']} = c.$pk AND fr.{$FR['reviewer']} = $rid", 'left');
                    $decCol = "LOWER(fr.{$FR['decision']})";
                    $b->groupStart()
                        ->where("fr.{$FR['decision']} IS NULL", null, false)
                        ->orWhereNotIn($decCol, $finalDec)
                      ->groupEnd();
                }

                $rows = $b->orderBy("c.$upAt",'DESC')->get()->getResultArray();

                foreach ($rows as $r) {
                    $eventId = (int)($r['event_id'] ?? 0);
                    $event   = $this->eventMetaById($eventId);
                    $deadline= $event['date'] ?? null;

                    $status  = strtolower((string)($r['fp_status'] ?? ''));
                    $isRev   = (strpos($status,'revisi') !== false || strpos($status,'revision') !== false);

                    $badge = $isRev ? 'warning' : 'info';
                    $time  = null;
                    $deadlineText = 'Tenggat belum ditentukan';

                    if ($deadline) {
                        $ts  = strtotime($deadline);
                        $now = time();
                        $diffDays = (int)floor(($ts - $now) / 86400);
                        $time = $ts;

                        if     ($diffDays <= 1)  $badge = 'danger';
                        elseif ($diffDays <= 7 && !$isRev)  $badge = 'warning';

                        $deadlineText = 'Tenggat event: ' . date('d M Y', $ts) . " ({$diffDays} hari lagi)";
                    }

                    if ($isRev) {
                        $activities[] = [
                            'type'  => 'fullpaper_revision',
                            'title' => 'Presenter mengirimkan revisi full paper',
                            'desc'  => ($event['title'] ? '['.$event['title'].'] ' : '') .
                                       ($r['judul'] ?? '(Tanpa judul)') . '. Segera review kembali. ' . $deadlineText,
                            'badge' => $badge,
                            'icon'  => 'bi-arrow-repeat',
                            'time'  => $time,
                            'link'  => site_url('reviewer/tugas/fullpaper'),
                        ];
                    } else {
                        $activities[] = [
                            'type'  => 'fullpaper_deadline',
                            'title' => 'Segera review full paper',
                            'desc'  => ($event['title'] ? '['.$event['title'].'] ' : '') .
                                       ($r['judul'] ?? '(Tanpa judul)') . '. ' . $deadlineText,
                            'badge' => $badge,
                            'icon'  => 'bi-file-earmark-text',
                            'time'  => $time,
                            'link'  => site_url('reviewer/tugas/fullpaper'),
                        ];
                    }
                }
            }
        }

        // sort by time desc kalau ada timestamp
        usort($activities, function($a, $b) {
            return (int)($b['time'] ?? 0) <=> (int)($a['time'] ?? 0);
        });

        return $activities;
    }

    /* ===================== Dashboard index ===================== */

    public function index()
    {
        $rid = $this->meId();
        if (!$rid) return redirect()->to('/login');

        // tugas yg masih butuh konfirmasi
        $abs = $this->incomingAbstrak($rid);
        $fp  = $this->incomingFullpaper($rid);
        $incoming = array_merge($abs, $fp);

        // tugas selesai = sudah ada keputusan final
        $doneAbs = $this->countDoneAbstrak($rid);
        $doneFp  = $this->countDoneFullpaper($rid);
        $done    = $doneAbs + $doneFp;

        // total = selesai + yg masih ngantri
        $total = $done + count($incoming);

        $cards = [
            'total' => $total,
            'done'  => $done,
        ];

        // flag info pivot status (untuk alert di view)
        $hasAssignAbs = false;
        if ($this->reviewTable()) {
            $rt = $this->reviewTable();
            $R  = $this->reviewCols($rt);
            $hasAssignAbs = (bool)$R['asgStatus'];
        }

        $hasAssignFp  = $this->db->tableExists('fullpaper_reviewers') &&
                        (bool)$this->col('fullpaper_reviewers', ['assignment_status','status_tugas','tugas_status','konfirmasi_status']);

        return view('role/reviewer/dashboard', [
            'title'        => 'Reviewer Dashboard',
            'incoming'     => $incoming,
            'activities'   => $this->buildReminderActivities($rid), // NOTIF pengingat
            'cards'        => $cards,
            'hasAssign'    => ($hasAssignAbs || $hasAssignFp),
            'hasAssignAbs' => $hasAssignAbs,
            'hasAssignFp'  => $hasAssignFp,
        ]);
    }

    /* ===================== Accept / Decline ===================== */

    public function confirm()
    {
        $type   = strtolower((string)$this->request->getPost('type'));   // abstrak|fullpaper
        $id     = (int)$this->request->getPost('id');                    // id_abstrak | id_submission
        $action = strtolower((string)$this->request->getPost('action')); // accept|decline
        $reason = trim((string)$this->request->getPost('reason'));
        $rid    = $this->meId();

        // selalu balik ke dashboard (panel tugas masuk)
        $redirectUrl = site_url('reviewer/dashboard#incoming');

        if (!in_array($type, ['abstrak','fullpaper'], true) || !$id || !in_array($action, ['accept','decline'], true)) {
            return redirect()->to($redirectUrl)->with('error','Input tidak valid.');
        }

        try {
            $this->db->transStart();

            /* ---- ABSTRAK: pakai tabel review/reviews ---- */
            if ($type === 'abstrak') {
                $rt = $this->reviewTable();
                if (!$rt) {
                    return redirect()->to($redirectUrl)->with('error','Tabel review tidak ditemukan.');
                }
                $R  = $this->reviewCols($rt);

                if (!$R['abstrakFk']) {
                    return redirect()->to($redirectUrl)->with('error','Skema assignment abstrak tidak lengkap.');
                }

                // cari baris tugas terbaru utk reviewer + abstrak ini
                $row = $this->db->table($rt)
                    ->where($R['reviewer'], $rid)
                    ->where($R['abstrakFk'], $id)
                    ->orderBy($R['pk'],'DESC')->get()->getRowArray();

                if (!$row) {
                    return redirect()->to($redirectUrl)->with('error','Tugas abstrak tidak ditemukan.');
                }

                if ($action === 'accept') {
                    if ($R['asgStatus']) {
                        $data = [ $R['asgStatus'] => 'accepted' ];
                        if ($R['asgAccAt'])  $data[$R['asgAccAt']]  = date('Y-m-d H:i:s');
                        if ($R['asgDecAt'])  $data[$R['asgDecAt']]  = null;
                        if ($R['asgReason']) $data[$R['asgReason']] = null;

                        $this->db->table($rt)->where($R['pk'], $row[$R['pk']])->update($data);
                    }
                } else { // decline
                    if (mb_strlen($reason) < 5) {
                        return redirect()->to($redirectUrl)->with('error','Alasan penolakan minimal 5 karakter.');
                    }

                    if ($R['asgStatus']) {
                        $data = [ $R['asgStatus'] => 'declined' ];
                        if ($R['asgDecAt'])  $data[$R['asgDecAt']]  = date('Y-m-d H:i:s');
                        if ($R['asgReason']) $data[$R['asgReason']] = $reason;

                        $this->db->table($rt)->where($R['pk'], $row[$R['pk']])->update($data);
                    } else {
                        // fallback: kalau nggak ada kolom status → cabut tugas saja
                        $this->db->table($rt)->where($R['pk'], $row[$R['pk']])->delete();
                    }
                }
            }
            /* ---- FULL PAPER: pakai fullpaper_reviewers ---- */
            else {
                $pivot = 'fullpaper_reviewers';
                if (!$this->db->tableExists($pivot)) {
                    return redirect()->to($redirectUrl)->with('error','Pivot fullpaper tidak ditemukan.');
                }

                $colSub  = $this->col($pivot, ['submission_id','id_submission','fullpaper_id','id_fullpaper','abstrak_id','id_abstrak']) ?? 'submission_id';
                $colRev  = $this->col($pivot, ['reviewer_id','id_reviewer','user_id','id_user']) ?? 'reviewer_id';
                $colStat = $this->col($pivot, ['assignment_status','status_tugas','tugas_status','konfirmasi_status']);
                $accAt   = $this->col($pivot, ['accepted_at','confirmed_at','konfirmasi_at']);
                $decAt   = $this->col($pivot, ['declined_at','rejected_at']);
                $reasonC = $this->col($pivot, ['decline_reason','alasan','alasan_tolak','reason']);

                $row = $this->db->table($pivot)
                    ->where($colSub,$id)
                    ->where($colRev,$rid)
                    ->orderBy('id','DESC')->get()->getRowArray();

                if (!$row) {
                    return redirect()->to($redirectUrl)->with('error','Penugasan fullpaper tidak ditemukan.');
                }

                if ($action === 'accept') {
                    if ($colStat) {
                        $data = [$colStat=>'accepted'];
                        if ($accAt)  $data[$accAt]  = date('Y-m-d H:i:s');
                        if ($decAt)  $data[$decAt]  = null;
                        if ($reasonC)$data[$reasonC]= null;
                        $this->db->table($pivot)->where($colSub,$id)->where($colRev,$rid)->update($data);
                    }
                } else { // decline
                    if (mb_strlen($reason) < 5) {
                        return redirect()->to($redirectUrl)->with('error','Alasan penolakan minimal 5 karakter.');
                    }
                    if ($colStat) {
                        $data = [$colStat=>'declined'];
                        if ($decAt)   $data[$decAt]   = date('Y-m-d H:i:s');
                        if ($reasonC) $data[$reasonC] = $reason;
                        $this->db->table($pivot)->where($colSub,$id)->where($colRev,$rid)->update($data);
                    } else {
                        // fallback tanpa kolom status → cabut assignment
                        $this->db->table($pivot)->where($colSub,$id)->where($colRev,$rid)->delete();
                    }
                }
            }

            $this->db->transComplete();
            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('DB error');
            }

            // ========= FLASH MESSAGE YANG LEBIH JELAS =========
            $jenis = $type === 'abstrak' ? 'Abstrak' : 'Full paper';
            $aksi  = $action === 'accept' ? 'berhasil diterima.' : 'berhasil ditolak.';
            $msg   = $jenis . ' ' . $aksi;

            return redirect()->to($redirectUrl)->with('success', $msg);

        } catch (\Throwable $e) {
            log_message('error','confirm(): '.$e->getMessage());
            return redirect()->to($redirectUrl)->with('error','Gagal memproses konfirmasi tugas.');
        }
    }
}
