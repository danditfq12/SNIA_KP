<?php

namespace App\Controllers\Role\Presenter;

use App\Controllers\BaseController;
use App\Models\AbstrakModel;
use App\Models\EventModel;
use App\Models\KategoriAbstrakModel;
use App\Models\ReviewModel;

class Abstrak extends BaseController
{
    protected $abstrakModel;
    protected $eventModel;
    protected $kategoriModel;
    protected $reviewModel;
    protected $db;

    public function __construct()
    {
        $this->abstrakModel  = new AbstrakModel();
        $this->eventModel    = new EventModel();
        $this->kategoriModel = new KategoriAbstrakModel();
        $this->reviewModel   = new ReviewModel();
        $this->db            = \Config\Database::connect();
    }

    public function index()
    {
        $userId  = (int) session('id_user');
        $eventId = (int) ($this->request->getGet('event_id') ?? 0);

        // pakai method model (selaras admin style)
        $abstracts  = [];
        $available  = [];
        $categories = [];

        try { $abstracts  = $this->abstrakModel->getByUserWithDetails($userId); } 
        catch (\Throwable $e) { log_message('error','abstrak.index[getByUserWithDetails] '.$e->getMessage()); }

        try { $available  = $this->eventModel->getAvailableEventsForUser($userId); }
        catch (\Throwable $e) { log_message('error','abstrak.index[getAvailableEventsForUser] '.$e->getMessage()); }

        try { $categories = $this->kategoriModel->findAll(); }
        catch (\Throwable $e) { log_message('error','abstrak.index[categories] '.$e->getMessage()); }

        return view('role/presenter/abstrak/index', [
            'title'             => 'Manajemen Abstrak',
            'abstracts'         => $abstracts,
            'available_events'  => $available,
            'categories'        => $categories,
            'selected_event_id' => $eventId,
        ]);
    }

    public function upload()
    {
        $userId = (int) session('id_user');
        if ($this->request->getMethod() !== 'post') {
            return redirect()->to('presenter/abstrak');
        }

        $rules = [
            'event_id'     => 'required|integer',
            'id_kategori'  => 'required|integer',
            'judul'        => 'required|min_length[5]|max_length[255]',
            'file_abstrak' => [
                'rules'  => 'uploaded[file_abstrak]|max_size[file_abstrak,10240]|ext_in[file_abstrak,pdf,doc,docx]',
                'errors' => [
                    'uploaded' => 'File abstrak wajib diupload.',
                    'max_size' => 'Maksimal 10MB.',
                    'ext_in'   => 'Format harus PDF/DOC/DOCX.'
                ]
            ],
        ];
        if (!$this->validate($rules)) {
            return $this->respondFormError('Validasi gagal.', $this->validator->getErrors());
        }

        $eventId    = (int) $this->request->getPost('event_id');
        $kategoriId = (int) $this->request->getPost('id_kategori');
        $judul      = trim((string) $this->request->getPost('judul'));
        $file       = $this->request->getFile('file_abstrak');
        $revisionId = (int) ($this->request->getPost('revision_id') ?? 0);

        // cek event & window submit
        $event = $this->eventModel->find($eventId);
        if (!$event) {
            return $this->respondFormError('Event tidak ditemukan.');
        }
        if (!$this->eventModel->isAbstractSubmissionOpen($eventId)) {
            return $this->respondFormError('Periode submission abstrak sudah ditutup.');
        }

        // cek existing
        $existing = $this->abstrakModel
            ->where('id_user', $userId)
            ->where('event_id', $eventId)
            ->orderBy('tanggal_upload', 'DESC')
            ->first();

        if ($existing && !$revisionId && !in_array($existing['status'], ['ditolak', 'revisi'], true)) {
            return $this->respondFormError('Anda sudah mengirim abstrak untuk event ini.');
        }

        $this->db->transStart();
        try {
            // === simpan file via Model (standar admin: uploads/abstrak/) ===
            $storedName = $this->abstrakModel->moveUploadedFile($file, $userId, $eventId);

            $nowWIB = (new \DateTime('now', new \DateTimeZone('Asia/Jakarta')))->format('Y-m-d H:i:s');

            $payload = [
                'id_user'        => $userId,
                'event_id'       => $eventId,
                'id_kategori'    => $kategoriId,
                'judul'          => $judul,
                'file_abstrak'   => $storedName,
                'status'         => 'menunggu',
                'tanggal_upload' => $nowWIB,
                'revisi_ke'      => 0,
            ];

            // === flow tetap: revisi eksplisit, upgrade kiriman lama, atau insert baru ===
            if ($revisionId) {
                $old = $this->abstrakModel
                    ->where('id_abstrak', $revisionId)
                    ->where('id_user', $userId)
                    ->first();
                if (!$old) throw new \RuntimeException('Abstrak revisi tidak ditemukan.');

                $payload['revisi_ke'] = (int) $old['revisi_ke'] + 1;
                $this->abstrakModel->update($revisionId, $payload);
                $abstrakId = $revisionId;

            } elseif ($existing && in_array($existing['status'], ['ditolak', 'revisi'], true)) {
                $payload['revisi_ke'] = (int) $existing['revisi_ke'] + 1;
                $this->abstrakModel->update($existing['id_abstrak'], $payload);
                $abstrakId = (int) $existing['id_abstrak'];

            } else {
                $this->abstrakModel->insert($payload);
                $abstrakId = (int) $this->abstrakModel->getInsertID();
            }

            $this->db->transComplete();
            if ($this->db->transStatus() === false) {
                throw new \RuntimeException('Transaksi gagal.');
            }

            return $this->respondFormSuccess(
                'Abstrak berhasil diupload. Silakan tunggu proses review.',
                site_url('presenter/abstrak/detail/'.$abstrakId)
            );

        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'abstrak.upload '.$e->getMessage());
            return $this->respondFormError('Terjadi kesalahan: '.$e->getMessage());
        }
    }

    public function detail($id)
    {
        $userId = (int) session('id_user');

        try {
            // ambil detail via Model (selaras admin)
            $abstract = $this->abstrakModel->getDetailWithRelationsForUser((int)$id, $userId);
            if (!$abstract) {
                return redirect()->to('presenter/abstrak')->with('error', 'Abstrak tidak ditemukan.');
            }

            $reviews    = $this->reviewModel->getByAbstrakWithReviewer((int)$id);
            $event      = $this->eventModel->find($abstract['event_id']);
            $categories = $this->kategoriModel->findAll();

        } catch (\Throwable $e) {
            log_message('error', 'abstrak.detail '.$e->getMessage());
            return redirect()->to('presenter/abstrak')->with('error', 'Gagal memuat detail abstrak.');
        }

        $canRevise = in_array($abstract['status'], ['revisi', 'ditolak'], true)
                  && $this->eventModel->isAbstractSubmissionOpen($abstract['event_id']);

        return view('role/presenter/abstrak/detail', [
            'title'      => 'Detail Abstrak',
            'abstract'   => $abstract,
            'reviews'    => $reviews,
            'event'      => $event,
            'categories' => $categories,
            'can_revise' => $canRevise,
        ]);
    }

    public function download($file)
    {
        $userId = (int) session('id_user');

        $row = $this->abstrakModel
            ->where('id_user', $userId)
            ->where('file_abstrak', $file)
            ->first();

        if (!$row) {
            return redirect()->to('presenter/abstrak')->with('error', 'File tidak ditemukan / tidak berhak.');
        }

        // standarisasi path seperti admin (pakai 'abstrak', fallback ke legacy 'abstraks')
        $possiblePaths = [
            WRITEPATH.'uploads/abstrak/'.$file,
            FCPATH  .'uploads/abstrak/'.$file,
            WRITEPATH.'uploads/abstraks/'.$file, // legacy
            FCPATH  .'uploads/abstraks/'.$file, // legacy
        ];

        foreach ($possiblePaths as $p) {
            if (is_file($p)) {
                return $this->response->download($p, null)->setFileName($file);
            }
        }
        return redirect()->to('presenter/abstrak')->with('error', 'File tidak ada di server.');
    }

    public function status()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to('presenter/abstrak');
        }

        $userId = (int) session('id_user');

        try {
            $rows = $this->abstrakModel->getByUserWithDetails($userId);
            $out  = [];
            foreach ($rows as $r) {
                $out[] = [
                    'id'           => (int) $r['id_abstrak'],
                    'title'        => $r['judul'],
                    'event'        => $r['event_title'],
                    'status'       => $r['status'],
                    'upload_date'  => $r['tanggal_upload'],
                    'revision'     => (int) $r['revisi_ke'],
                ];
            }
            return $this->response->setJSON(['success'=>true,'data'=>$out]);
        } catch (\Throwable $e) {
            log_message('error','abstrak.status '.$e->getMessage());
            return $this->response->setJSON(['success'=>false,'message'=>'Gagal memuat status.']);
        }
    }

    /* ==== helpers (unchanged API) ==== */
    private function respondFormError(string $msg, $errors = null)
    {
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['success'=>false,'message'=>$msg,'errors'=>$errors]);
        }
        return redirect()->back()->withInput()->with('error', $msg);
    }

    private function respondFormSuccess(string $msg, string $redirectUrl)
    {
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['success'=>true,'message'=>$msg,'redirect_url'=>$redirectUrl]);
        }
        return redirect()->to($redirectUrl)->with('success', $msg);
    }
}
