<?php

namespace App\Controllers\Role\Reviewer;

use App\Controllers\BaseController;
use App\Models\AbstrakModel;
use App\Models\ReviewModel;

class Abstrak extends BaseController
{
    protected AbstrakModel $abstrakModel;
    protected ReviewModel  $reviewModel;

    public function __construct()
    {
        $this->abstrakModel = new AbstrakModel();
        $this->reviewModel  = new ReviewModel();
    }

    public function index()
    {
        $idReviewer = (int) (session('id_user') ?? 0);
        if (!$idReviewer || session('role') !== 'reviewer') {
            return redirect()->to(site_url('auth/login'));
        }

        // Ambil semua abstrak yang DITUGASKAN ke reviewer ini
        $rows = $this->abstrakModel
            ->select('
                abstrak.id_abstrak, abstrak.judul, abstrak.status, abstrak.tanggal_upload,
                users.nama_lengkap,
                kategori_abstrak.nama_kategori,
                e.id as event_id, e.title as event_title, e.event_date, e.event_time
            ')
            ->join('users', 'users.id_user = abstrak.id_user')
            ->join('kategori_abstrak', 'kategori_abstrak.id_kategori = abstrak.id_kategori', 'left')
            ->join('review', 'review.id_abstrak = abstrak.id_abstrak', 'inner')
            ->join('events e', 'e.id = abstrak.event_id', 'left')
            ->where('review.id_reviewer', $idReviewer)
            ->orderBy('e.event_date', 'DESC')
            ->orderBy('abstrak.tanggal_upload', 'DESC')
            ->findAll();

        // Kumpulan event untuk filter
        $eventOptions = [];
        foreach ($rows as $r) {
            $eid = (int)($r['event_id'] ?? 0);
            if ($eid && !isset($eventOptions[$eid])) {
                $eventOptions[$eid] = $r['event_title'] ?? ('Event #' . $eid);
            }
        }
        ksort($eventOptions);

        // Grouping per event + ringkasan status
        $grouped = []; // [event_id => ['event_title'=>..., 'items'=>[], 'summary'=>['pending'=>..,'diterima'=>..,'ditolak'=>..,'revisi'=>..]]]
        foreach ($rows as $r) {
            $eid   = (int)($r['event_id'] ?? 0);
            $etitle= $r['event_title'] ?? 'Event';
            if (!isset($grouped[$eid])) {
                $grouped[$eid] = [
                    'event_id'    => $eid,
                    'event_title' => $etitle,
                    'event_date'  => $r['event_date'] ?? null,
                    'event_time'  => $r['event_time'] ?? null,
                    'items'       => [],
                    'summary'     => ['pending'=>0,'diterima'=>0,'ditolak'=>0,'revisi'=>0,'lain'=>0],
                ];
            }
            $status = strtolower((string)($r['status'] ?? ''));
            if (isset($grouped[$eid]['summary'][$status])) {
                $grouped[$eid]['summary'][$status]++;
            } else {
                $grouped[$eid]['summary']['lain']++;
            }
            $grouped[$eid]['items'][] = $r;
        }

        return view('role/reviewer/abstrak', [
            'title'        => 'Daftar Abstrak',
            'abstrak'      => $rows,      // untuk tampilan "Semua"
            'byEvent'      => $grouped,   // untuk tampilan "Per Event"
            'eventOptions' => $eventOptions,
        ]);
    }

    public function detail($id)
    {
        $idReviewer = (int) (session('id_user') ?? 0);
        if (!$idReviewer || session('role') !== 'reviewer') {
            return redirect()->to(site_url('auth/login'));
        }

        // Pastikan abstrak memang ditugaskan ke reviewer ini
        $abstrak = $this->abstrakModel
            ->select('
                abstrak.*,
                users.nama_lengkap,
                kategori_abstrak.nama_kategori,
                e.title as event_title, e.id as event_id, e.event_date, e.event_time
            ')
            ->join('users','users.id_user = abstrak.id_user')
            ->join('kategori_abstrak','kategori_abstrak.id_kategori = abstrak.id_kategori','left')
            ->join('review','review.id_abstrak = abstrak.id_abstrak','inner')
            ->join('events e','e.id = abstrak.event_id','left')
            ->where('review.id_reviewer', $idReviewer)
            ->where('abstrak.id_abstrak', (int)$id)
            ->first();

        if (!$abstrak) {
            return redirect()->to('reviewer/abstrak')->with('error', 'Abstrak tidak ditemukan / bukan tugas Anda.');
        }

        return view('role/reviewer/detail_abstrak', ['abstrak' => $abstrak]);
    }

    public function saveReview()
    {
        $idReviewer = (int) (session('id_user') ?? 0);
        if (!$idReviewer || session('role') !== 'reviewer') {
            return redirect()->to(site_url('auth/login'));
        }

        $data = [
            'id_abstrak'     => (int) $this->request->getPost('id_abstrak'),
            'id_reviewer'    => $idReviewer,
            'keputusan'      => $this->request->getPost('keputusan'),
            'komentar'       => $this->request->getPost('komentar'),
            'tanggal_review' => date('Y-m-d H:i:s'),
        ];

        $this->reviewModel->insert($data);
        return redirect()->to('reviewer/riwayat')->with('success', 'Review berhasil disimpan');
    }
}
