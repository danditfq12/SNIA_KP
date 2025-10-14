<?php

namespace App\Controllers\Role\Presenter;

use App\Controllers\BaseController;
use App\Models\EventModel;
use App\Models\EventRegistrationModel;
use App\Models\AbstrakModel;
use App\Models\KategoriAbstrakModel;
use App\Models\ReviewModel;
use App\Models\UserModel;

class Abstrak extends BaseController
{
    protected EventModel $eventModel;
    protected EventRegistrationModel $regModel;
    protected AbstrakModel $abstrakModel;
    protected KategoriAbstrakModel $kategoriModel;
    protected ReviewModel $reviewModel;
    protected UserModel $userModel;

    public function __construct()
    {
        $this->eventModel    = new EventModel();
        $this->regModel      = new EventRegistrationModel();
        $this->abstrakModel  = new AbstrakModel();
        $this->kategoriModel = new KategoriAbstrakModel();
        $this->reviewModel   = new ReviewModel();
        $this->userModel     = new UserModel();
        helper(['date', 'text', 'filesystem']);
    }

    private function isContributorCompleted(?array $reg): bool
    {
        if (!$reg) return false;
        foreach (['contributor_done','kontributor_done','profile_completed','is_profile_completed'] as $f) {
            if (array_key_exists($f, $reg)) {
                $v = $reg[$f];
                if ($v === true || $v === 1 || $v === '1' || $v === 't' || $v === 'true') return true;
            }
        }
        if (!empty($reg['afiliasi'])) return true;
        return false;
    }

    private function mapStatusMeta(?string $status): array
    {
        $s = strtolower((string)$status);
        return match ($s) {
            'menunggu'         => ['badge'=>'warning','label'=>'Menunggu','hint'=>'Menunggu review abstrak'],
            'sedang_direview'  => ['badge'=>'info','label'=>'Sedang direview','hint'=>'Reviewer sedang menilai'],
            'diterima'         => ['badge'=>'success','label'=>'Diterima (ACC)','hint'=>'Abstrak diterima'],
            'ditolak'          => ['badge'=>'danger','label'=>'Ditolak','hint'=>'Abstrak ditolak (unggah ulang)'],
            'revisi'           => ['badge'=>'primary','label'=>'Revisi','hint'=>'Unggah revisi abstrak'],
            default            => ['badge'=>'secondary','label'=>'Belum Upload','hint'=>'Belum ada abstrak'],
        };
    }

    public function index()
    {
        $userId = (int) session()->get('id_user');
        $regs   = $this->regModel->listByUser($userId) ?? [];

        // semua abstrak user → pilih TERBARU per event
        $allAbs = $this->abstrakModel->getByUserWithDetails($userId) ?? [];
        $latestPerEvent = [];
        foreach ($allAbs as $a) {
            $eid = (int) ($a['event_id'] ?? 0);
            if (!$eid) continue;
            $ts  = !empty($a['tanggal_upload']) ? strtotime($a['tanggal_upload']) : 0;
            $cur = !empty($latestPerEvent[$eid]['tanggal_upload']) ? strtotime($latestPerEvent[$eid]['tanggal_upload']) : -1;
            if (!isset($latestPerEvent[$eid]) || $ts > $cur) $latestPerEvent[$eid] = $a;
        }

        $needsUpload = []; // = To-Do
        $history     = []; // hanya diterima

        foreach ($regs as $r) {
            $eid = (int) ($r['id_event'] ?? 0);
            if (!$eid) continue;

            $event = $this->eventModel->find($eid);
            if (!$event) continue;

            $regRow    = $this->regModel->findUserReg($eid, $userId);
            $contribOK = $this->isContributorCompleted($regRow);

            $isOpenSubmit = $this->eventModel->isAbstractSubmissionOpen($eid);
            $isOpenRev    = method_exists($this->eventModel, 'isAbstractRevisionOpen')
                          ? $this->eventModel->isAbstractRevisionOpen($eid)
                          : $isOpenSubmit;

            $last = $latestPerEvent[$eid] ?? null;
            $lastStatus = strtolower((string)($last['status'] ?? '')); // '', menunggu, sedang_direview, revisi, ditolak, diterima

            // === hitung apakah BOLEH upload tombolnya ===
            $canUpload = false;
            if ($contribOK) {
                if (!$last) {
                    // belum pernah upload → boleh kalau jendela submit open
                    $canUpload = $isOpenSubmit;
                } else {
                    if ($lastStatus === 'revisi')  $canUpload = $isOpenRev;
                    if ($lastStatus === 'ditolak') $canUpload = $isOpenSubmit;
                }
            }

            // === klasifikasi To-Do vs Riwayat ===
            if ($last && $lastStatus === 'diterima') {
                // hanya accepted → Riwayat
                $meta = $this->mapStatusMeta($lastStatus);
                $history[] = [
                    'id_abstrak'     => (int) ($last['id_abstrak'] ?? 0),
                    'judul'          => $last['judul'] ?? '-',
                    'nama_kategori'  => $last['nama_kategori'] ?? '-',
                    'status'         => $lastStatus,
                    'status_badge'   => $meta['badge'],
                    'status_label'   => $meta['label'],
                    'status_hint'    => $meta['hint'],
                    'tanggal_upload' => $last['tanggal_upload'] ?? null,
                    'event_id'       => $eid,
                    'event_title'    => $event['title'] ?? '-',
                    'event_date'     => $event['event_date'] ?? null,
                    'revision_open'  => $isOpenRev,
                ];
                continue;
            }

            // selain accepted (termasuk belum upload) → To-Do
            $meta = $this->mapStatusMeta($lastStatus ?: null);
            $needsUpload[] = [
                'event_id'                   => $eid,
                'title'                      => $last['judul'] ?? ($event['title'] ?? '-'),
                'event_title'                => $event['title'] ?? '-',
                'event_date'                 => $event['event_date'] ?? null,
                'abstract_deadline'          => $event['abstract_deadline'] ?? null,
                'abstract_revision_deadline' => $event['abstract_revision_deadline'] ?? null,
                'abstract_submission_active' => $event['abstract_submission_active'] ?? null,
                'revision_open'              => $isOpenRev,
                'format'                     => strtolower($event['format'] ?? ''),
                'status'                     => $lastStatus ?: 'belum_upload',
                'status_badge'               => $meta['badge'],
                'status_label'               => $meta['label'],
                'status_hint'                => $meta['hint'],
                'last_abs_id'                => $last['id_abstrak'] ?? null,
                // ➜ dipakai view untuk tampilkan/hilangkan tombol upload
                'can_upload'                 => $canUpload,
            ];
        }

        // urutkan Riwayat dari terbaru
        usort($history, fn($a,$b) => strtotime($b['tanggal_upload'] ?? '1970-01-01') <=> strtotime($a['tanggal_upload'] ?? '1970-01-01'));

        return view('role/presenter/abstrak/index', [
            'title'        => 'Abstrak',
            'uploadEvents' => $needsUpload, // = To-Do
            'history'      => $history,
        ]);
    }

    public function create($eventId)
    {
        $userId  = (int) session()->get('id_user');
        $eventId = (int) $eventId;

        $event = $this->eventModel->find($eventId);
        if (!$event) {
            return redirect()->to('/presenter/abstrak')
                ->with('error','Event tidak ditemukan.')
                ->with('swal', ['icon'=>'error','title'=>'Gagal','text'=>'Event tidak ditemukan.']);
        }

        $reg = $this->regModel->findUserReg($eventId, $userId);
        if (!$reg) {
            return redirect()->to('/presenter/abstrak')
                ->with('error','Anda belum terdaftar pada event ini.')
                ->with('swal', ['icon'=>'error','title'=>'Gagal','text'=>'Anda belum terdaftar pada event ini.']);
        }

        if (!$this->isContributorCompleted($reg)) {
            return redirect()->to('/presenter/kontributor/start/'.$eventId)
                ->with('error','Lengkapi data kontributor (minimal afiliasi) sebelum upload abstrak.')
                ->with('swal', ['icon'=>'warning','title'=>'Lengkapi Data','text'=>'Lengkapi data kontributor terlebih dahulu.']);
        }

        if (!$this->eventModel->isAbstractSubmissionOpen($eventId)) {
            return redirect()->to('/presenter/abstrak')
                ->with('error','Pengumpulan abstrak telah ditutup.')
                ->with('swal', ['icon'=>'info','title'=>'Ditutup','text'=>'Pengumpulan abstrak telah ditutup.']);
        }

        $lastAbs = $this->abstrakModel->where('id_user',$userId)
                    ->where('event_id',$eventId)->orderBy('id_abstrak','DESC')->first();

        $formLocked = false;
        $showAlert  = false;
        $lastUploadedId = 0;

        if ($lastAbs && in_array(strtolower((string)$lastAbs['status']), ['menunggu','sedang_direview','diterima'], true)) {
            $formLocked     = true;
            $showAlert      = true;
            $lastUploadedId = (int)$lastAbs['id_abstrak'];
        } else {
            $flashShow = (bool) session()->getFlashdata('show_fp_cta');
            $flashId   = (int) (session()->getFlashdata('just_uploaded_id') ?? 0);
            if ($flashShow && $flashId > 0) {
                $row = $this->abstrakModel->where('id_abstrak',$flashId)
                        ->where('id_user',$userId)->where('event_id',$eventId)->first();
                if ($row) {
                    $showAlert      = true;
                    $formLocked     = true;
                    $lastUploadedId = (int)$row['id_abstrak'];
                    $lastAbs        = $row;
                }
            }
        }

        $kategoriList = $this->kategoriModel->orderBy('nama_kategori','ASC')->findAll();

        return view('role/presenter/abstrak/create', [
            'title'           => 'Kirim Abstrak',
            'event'           => $event,
            'eventId'         => $eventId,
            'kategoriList'    => $kategoriList,
            'lastAbs'         => $lastAbs,
            'showFpCta'       => $showAlert,
            'lastUploadedId'  => $lastUploadedId,
            'formLocked'      => $formLocked,
        ]);
    }

    public function store()
    {
        $userId     = (int) session()->get('id_user');
        $eventId    = (int) $this->request->getPost('event_id');
        $judul      = trim((string) $this->request->getPost('judul'));
        $idKategori = (int) $this->request->getPost('id_kategori');
        $file       = $this->request->getFile('file_abstrak');

        if (!$eventId || $judul === '' || !$file || $idKategori <= 0) {
            return redirect()->back()->withInput()
                ->with('error','Lengkapi semua field (termasuk kategori).')
                ->with('swal', ['icon'=>'warning','title'=>'Cek Data','text'=>'Lengkapi semua field termasuk kategori.']);
        }

        $event = $this->eventModel->find($eventId);
        if (!$event) {
            return redirect()->to('/presenter/abstrak')
                ->with('error','Event tidak ditemukan.')
                ->with('swal', ['icon'=>'error','title'=>'Gagal','text'=>'Event tidak ditemukan.']);
        }

        $reg = $this->regModel->findUserReg($eventId, $userId);
        if (!$reg) {
            return redirect()->to('/presenter/abstrak')
                ->with('error','Anda belum terdaftar pada event ini.')
                ->with('swal', ['icon'=>'error','title'=>'Gagal','text'=>'Anda belum terdaftar pada event ini.']);
        }

        if (!$this->isContributorCompleted($reg)) {
            return redirect()->to('/presenter/kontributor/start/'.$eventId)
                ->with('error','Lengkapi data kontributor (minimal afiliasi) sebelum upload abstrak.')
                ->with('swal', ['icon'=>'warning','title'=>'Lengkapi Data','text'=>'Lengkapi data kontributor terlebih dahulu.']);
        }

        if (!$this->eventModel->isAbstractSubmissionOpen($eventId)) {
            return redirect()->to('/presenter/abstrak')
                ->with('error','Pengumpulan abstrak telah ditutup.')
                ->with('swal', ['icon'=>'info','title'=>'Ditutup','text'=>'Pengumpulan abstrak telah ditutup.']);
        }

        $already = $this->abstrakModel->where('id_user',$userId)
                      ->where('event_id',$eventId)->orderBy('id_abstrak','DESC')->first();
        if ($already && in_array(strtolower((string)$already['status']), ['menunggu','sedang_direview','diterima'], true)) {
            return redirect()->to('/presenter/abstrak')
                ->with('error','Masih ada abstrak aktif untuk event ini.')
                ->with('swal', ['icon'=>'warning','title'=>'Tidak Bisa Upload','text'=>'Masih ada abstrak aktif untuk event ini.']);
        }

        if (!$file || !$file->isValid()) {
            return redirect()->back()->withInput()
                ->with('error','File tidak valid.')
                ->with('swal', ['icon'=>'error','title'=>'Gagal','text'=>'File tidak valid.']);
        }

        $ext  = strtolower($file->getClientExtension() ?: '');
        $mime = strtolower($file->getMimeType() ?: '');
        if ($ext !== 'pdf' || !str_contains($mime, 'pdf')) {
            return redirect()->back()->withInput()
                ->with('error','File harus PDF.')
                ->with('swal', ['icon'=>'error','title'=>'Gagal','text'=>'File harus berformat PDF.']);
        }
        if ($file->getSize() > 5 * 1024 * 1024) {
            return redirect()->back()->withInput()
                ->with('error','Ukuran maksimal 5MB.')
                ->with('swal', ['icon'=>'warning','title'=>'File Terlalu Besar','text'=>'Ukuran maksimal 5MB.']);
        }

        try {
            if (method_exists($this->abstrakModel, 'moveUploadedFile')) {
                $stored = $this->abstrakModel->moveUploadedFile($file, $userId, $eventId);
            } else {
                $dir = WRITEPATH.'uploads/abstrak';
                if (!is_dir($dir)) @mkdir($dir, 0777, true);
                $newName = 'abs_'.$userId.'_'.$eventId.'_'.time().'.pdf';
                $file->move($dir, $newName, true);
                $stored = $newName;
            }

            $newId = $this->abstrakModel->insert([
                'id_user'        => $userId,
                'id_kategori'    => $idKategori,
                'event_id'       => $eventId,
                'judul'          => $judul,
                'file_abstrak'   => $stored,
                'status'         => 'menunggu',
                'tanggal_upload' => date('Y-m-d H:i:s'),
                'revisi_ke'      => ($already && strtolower((string)$already['status'])==='revisi') ? (int)$already['revisi_ke']+1 : 0,
            ], true);
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()
                ->with('error', 'Gagal mengunggah: '.$e->getMessage())
                ->with('swal', ['icon'=>'error','title'=>'Gagal','text'=>'Gagal mengunggah: '.$e->getMessage()]);
        }

        return redirect()
            ->to('/presenter/abstrak/create/'.$eventId)
            ->with('success','Abstrak berhasil diunggah.')
            ->with('swal', [
                'icon'  => 'success',
                'title' => 'Berhasil',
                'text'  => 'Abstrak berhasil diunggah.',
            ])
            ->with('show_fp_cta', true)
            ->with('just_uploaded_id', (int)$newId);
    }

    public function cancel($idAbstrak)
    {
        if (strtoupper($this->request->getMethod()) !== 'POST') {
            return redirect()->back()
                ->with('error', 'Metode tidak diizinkan.')
                ->with('swal', ['icon'=>'error','title'=>'Gagal','text'=>'Metode tidak diizinkan.']);
        }

        $userId = (int) session()->get('id_user');
        $row = $this->abstrakModel->where('id_abstrak', (int)$idAbstrak)
                ->where('id_user', $userId)->first();

        if (!$row) {
            return redirect()->back()
                ->with('error', 'Abstrak tidak ditemukan.')
                ->with('swal', ['icon'=>'error','title'=>'Gagal','text'=>'Abstrak tidak ditemukan.']);
        }

        $eventId = (int)($row['event_id'] ?? 0);
        if ($eventId <= 0) {
            return redirect()->back()
                ->with('error', 'Data event tidak valid.')
                ->with('swal', ['icon'=>'error','title'=>'Gagal','text'=>'Data event tidak valid.']);
        }

        $status = strtolower((string)($row['status'] ?? ''));
        if ($status !== 'menunggu') {
            return redirect()->to('/presenter/abstrak/detail/'.$idAbstrak)
                ->with('error', 'Abstrak tidak bisa dibatalkan (bukan status menunggu).')
                ->with('swal', ['icon'=>'warning','title'=>'Tidak Bisa','text'=>'Abstrak bukan status menunggu.']);
        }

        $latest = $this->abstrakModel->where('id_user', $userId)
                    ->where('event_id', $eventId)
                    ->orderBy('id_abstrak','DESC')->first();
        if (!$latest || (int)$latest['id_abstrak'] !== (int)$idAbstrak) {
            return redirect()->to('/presenter/abstrak/detail/'.$idAbstrak)
                ->with('error', 'Hanya abstrak terbaru yang dapat dibatalkan.')
                ->with('swal', ['icon'=>'warning','title'=>'Tidak Bisa','text'=>'Hanya abstrak terbaru yang dapat dibatalkan.']);
        }

        $file = (string)($row['file_abstrak'] ?? '');
        if ($file !== '') {
            $path = WRITEPATH.'uploads/abstrak/'.$file;
            if (is_file($path)) @unlink($path);
        }

        try {
            $this->abstrakModel->delete((int)$idAbstrak);
        } catch (\Throwable $e) {
            return redirect()->to('/presenter/abstrak/detail/'.$idAbstrak)
                ->with('error', 'Gagal membatalkan: '.$e->getMessage())
                ->with('swal', ['icon'=>'error','title'=>'Gagal','text'=>'Gagal membatalkan: '.$e->getMessage()]);
        }

        return redirect()
            ->to('/presenter/abstrak/create/'.$eventId)
            ->with('success', 'Upload abstrak telah dibatalkan.')
            ->with('swal', ['icon'=>'info','title'=>'Dibatalkan','text'=>'Upload abstrak terakhir telah dibatalkan.']);
    }

    public function detail($idAbstrak)
    {
        $userId = (int) session()->get('id_user');
        $row = $this->abstrakModel->getDetailWithRelationsForUser((int)$idAbstrak, $userId);
        if (!$row) {
            return redirect()->to('/presenter/abstrak')
                ->with('error','Abstrak tidak ditemukan.')
                ->with('swal', ['icon'=>'error','title'=>'Gagal','text'=>'Abstrak tidak ditemukan.']);
        }

        $event  = $this->eventModel->find((int)$row['event_id']);
        $status = strtolower((string)($row['status'] ?? 'menunggu'));
        $badge  = $this->mapStatusMeta($status)['badge'] ?? 'secondary';

        // Semua review
        $reviews = $this->reviewModel->getReviewsForDisplay((int)$idAbstrak);

        // Fallback lama: list komentar
        $reviewComments = [];
        foreach ($reviews as $rv) {
            if (!empty($rv['display_comment'])) $reviewComments[] = trim((string)$rv['display_comment']);
        }
        $reviewComments = array_values(array_unique(array_filter($reviewComments, fn($v)=>$v!=='')));

        // NEW: structured list
        $reviewList = [];
        foreach ($reviews as $rv) {
            $komentar = trim((string)($rv['display_comment'] ?? $rv['komentar'] ?? ''));
            $reviewList[] = [
                'revisi_ke'      => isset($rv['revisi_ke']) ? (int)$rv['revisi_ke'] : null,
                'keputusan'      => strtolower((string)($rv['keputusan'] ?? 'pending')),
                'reviewer_name'  => trim((string)($rv['reviewer_name'] ?? '')),
                'reviewer_email' => trim((string)($rv['reviewer_email'] ?? '')),
                'tanggal_review' => $rv['tanggal_review'] ?? null,
                'komentar'       => $komentar,
            ];
        }

        // pilih reviewer untuk panel kanan
        $assigned = null;
        foreach ($reviews as $rv) { if (($rv['keputusan'] ?? '') === 'pending') { $assigned = $rv; break; } }
        if (!$assigned && !empty($reviews)) $assigned = $reviews[0];

        $assignedReviewer = [
            'name'  => $assigned['reviewer_name']  ?? null,
            'email' => $assigned['reviewer_email'] ?? null,
            'org'   => null,
        ];

        $contributors = $this->buildContributorsFromRegistration((int)$row['event_id'], $userId);

        $revOpen = method_exists($this->eventModel, 'isAbstractRevisionOpen')
                 ? $this->eventModel->isAbstractRevisionOpen((int)$row['event_id'])
                 : $this->eventModel->isAbstractSubmissionOpen((int)$row['event_id']);

        $showReuploadAbstract = ($status === 'ditolak');
        $showCancel           = ($status === 'menunggu');

        return view('role/presenter/abstrak/detail', [
            'title'                => 'Detail Abstrak',
            'abs'                  => $row,
            'event'                => $event,
            'status'               => $status,
            'badge'                => $badge,
            'showReuploadAbstract' => $showReuploadAbstract,
            'showCancel'           => $showCancel,
            'assignedReviewer'     => $assignedReviewer,
            'reviewComments'       => $reviewComments,
            'reviewList'           => $reviewList,
            'contributors'         => $contributors,
            'canUploadRevision'    => ($status === 'revisi' && $revOpen),
        ]);
    }

    public function revisi($idAbstrak)
    {
        if (strtoupper($this->request->getMethod()) !== 'POST') {
            return redirect()->back()
                ->with('error','Metode tidak diizinkan.')
                ->with('swal', ['icon'=>'error','title'=>'Gagal','text'=>'Metode tidak diizinkan.']);
        }

        $userId = (int) session()->get('id_user');
        $row = $this->abstrakModel->where('id_abstrak',(int)$idAbstrak)
                ->where('id_user',$userId)->first();

        if (!$row) {
            return redirect()->back()
                ->with('error','Abstrak tidak ditemukan.')
                ->with('swal', ['icon'=>'error','title'=>'Gagal','text'=>'Abstrak tidak ditemukan.']);
        }

        if (strtolower((string)$row['status']) !== 'revisi') {
            return redirect()->to('/presenter/abstrak/detail/'.$idAbstrak)
                ->with('error','Abstrak ini tidak dalam status revisi.')
                ->with('swal', ['icon'=>'warning','title'=>'Tidak Bisa','text'=>'Status bukan revisi.']);
        }

        // Gate jendela revisi
        $eventId = (int)$row['event_id'];
        $revOpen = method_exists($this->eventModel, 'isAbstractRevisionOpen')
                 ? $this->eventModel->isAbstractRevisionOpen($eventId)
                 : $this->eventModel->isAbstractSubmissionOpen($eventId);
        if (!$revOpen) {
            return redirect()->to('/presenter/abstrak/detail/'.$idAbstrak)
                ->with('error','Batas waktu revisi telah berakhir.')
                ->with('swal', ['icon'=>'info','title'=>'Ditutup','text'=>'Batas waktu revisi telah berakhir.']);
        }

        $file = $this->request->getFile('file_abstrak');
        if (!$file || !$file->isValid()) {
            return redirect()->back()->withInput()
                ->with('error','File revisi tidak valid.')
                ->with('swal', ['icon'=>'error','title'=>'Gagal','text'=>'File revisi tidak valid.']);
        }

        $ext  = strtolower($file->getClientExtension() ?: '');
        $mime = strtolower($file->getMimeType() ?: '');
        if ($ext !== 'pdf' || !str_contains($mime, 'pdf')) {
            return redirect()->back()->withInput()
                ->with('error','File harus PDF.')
                ->with('swal', ['icon'=>'error','title'=>'Gagal','text'=>'File harus berformat PDF.']);
        }
        if ($file->getSize() > 5 * 1024 * 1024) {
            return redirect()->back()->withInput()
                ->with('error','Ukuran maksimal 5MB.')
                ->with('swal', ['icon'=>'warning','title'=>'File Terlalu Besar','text'=>'Ukuran maksimal 5MB.']);
        }

        try {
            if (method_exists($this->abstrakModel, 'moveUploadedFile')) {
                $stored = $this->abstrakModel->moveUploadedFile($file, $userId, (int)$row['event_id']);
            } else {
                $dir = WRITEPATH.'uploads/abstrak';
                if (!is_dir($dir)) @mkdir($dir, 0777, true);
                $newName = 'absrev_'.$userId.'_'.$row['event_id'].'_'.time().'.pdf';
                $file->move($dir, $newName, true);
                $stored = $newName;
            }
        } catch (\Throwable $e) {
            return redirect()->back()->withInput()
                ->with('error','Gagal menyimpan file revisi: '.$e->getMessage())
                ->with('swal', ['icon'=>'error','title'=>'Gagal','text'=>'Gagal menyimpan file revisi.']);
        }

        // hapus file lama bila ada
        $old = (string)($row['file_abstrak'] ?? '');
        if ($old !== '') {
            $oldPath = WRITEPATH.'uploads/abstrak/'.$old;
            if (is_file($oldPath)) @unlink($oldPath);
        }

        // update abstrak → naikkan revisi_ke & kirim review ulang
        $this->abstrakModel->update((int)$idAbstrak, [
            'file_abstrak'   => $stored,
            'revisi_ke'      => (int)($row['revisi_ke'] ?? 0) + 1,
            'status'         => 'sedang_direview',
            'tanggal_upload' => date('Y-m-d H:i:s'),
        ]);

        // re-queue reviewer → SET tugas langsung accepted
        $this->requeueAbstractReviewers((int)$idAbstrak);

        return redirect()->to('/presenter/abstrak/detail/'.$idAbstrak)
            ->with('success','Revisi berhasil diunggah dan langsung dikirim ke reviewer.')
            ->with('swal', ['icon'=>'success','title'=>'Berhasil','text'=>'Revisi berhasil diunggah.']);
    }

    public function uploadRevisi($idAbstrak) { return $this->revisi($idAbstrak); }

    private function requeueAbstractReviewers(int $idAbstrak): int
    {
        $db = \Config\Database::connect();

        $tblReview = null;
        foreach (['reviews','abstrak_reviews','review'] as $t) {
            if ($db->tableExists($t)) { $tblReview = $t; break; }
        }
        if (!$tblReview) return 0;

        $rFields = array_flip($db->getFieldNames($tblReview));
        $pk   = isset($rFields['id']) ? 'id' : (isset($rFields['id_review']) ? 'id_review' : 'id');
        $fAbs = isset($rFields['id_abstrak']) ? 'id_abstrak' : (isset($rFields['abstrak_id']) ? 'abstrak_id' : null);
        $fRev = isset($rFields['id_reviewer']) ? 'id_reviewer' : (isset($rFields['reviewer_id']) ? 'reviewer_id' : null);
        $fDec = isset($rFields['keputusan']) ? 'keputusan' : (isset($rFields['status']) ? 'status' : (isset($rFields['decision']) ? 'decision' : null));
        $fTgl = isset($rFields['tanggal_review']) ? 'tanggal_review' : (isset($rFields['updated_at']) ? 'updated_at' : (isset($rFields['created_at']) ? 'created_at' : null));
        $fType= isset($rFields['type']) ? 'type' : (isset($rFields['review_type']) ? 'review_type' : null);

        $fAsg = null; foreach (['status_tugas','tugas_status','assignment_status','konfirmasi_status'] as $c) if (isset($rFields[$c])) { $fAsg = $c; break; }
        $fRsn = null; foreach (['alasan_tolak','alasan','decline_reason','reason'] as $c) if (isset($rFields[$c])) { $fRsn = $c; break; }
        $fAcc = null; foreach (['accepted_at','confirmed_at','konfirmasi_at'] as $c) if (isset($rFields[$c])) { $fAcc = $c; break; }
        $fDecAt = null; foreach (['declined_at','rejected_at'] as $c) if (isset($rFields[$c])) { $fDecAt = $c; break; }
        $fRevNo = null; foreach (['revisi_ke','revision_no'] as $c) if (isset($rFields[$c])) { $fRevNo = $c; break; }
        $fComment = null; foreach (['komentar','comment','notes','catatan'] as $c) if (isset($rFields[$c])) { $fComment = $c; break; }

        if (!$fAbs || !$fRev || !$fDec) return 0;

        $now = date('Y-m-d H:i:s');
        $revNow = (int)($db->table('abstrak')->select('revisi_ke')->where('id_abstrak',$idAbstrak)->get()->getRowArray()['revisi_ke'] ?? 0);

        // ambil reviewer dari pivot bila ada
        $reviewerIds = [];
        $pivot = null; foreach (['abstrak_reviewers','abstrak_reviewer','reviewer_abstrak'] as $t) if ($db->tableExists($t)) { $pivot = $t; break; }
        if ($pivot) {
            $pFields = array_flip($db->getFieldNames($pivot));
            $colAbs  = isset($pFields['id_abstrak']) ? 'id_abstrak' : (isset($pFields['abstrak_id']) ? 'abstrak_id' : null);
            $colRev  = isset($pFields['id_reviewer']) ? 'id_reviewer' : (isset($pFields['reviewer_id']) ? 'reviewer_id' : null);
            if ($colAbs && $colRev) {
                $reviewerIds = array_map(fn($r)=>(int)$r['reviewer_id'],
                    $db->table($pivot)->select("$colRev AS reviewer_id")->where($colAbs, $idAbstrak)->get()->getResultArray()
                );
            }
        }
        // fallback: dari tabel review langsung
        if (!$reviewerIds) {
            $reviewerIds = array_map(fn($r)=>(int)$r['reviewer_id'],
                $db->table($tblReview)->select("$fRev AS reviewer_id")->where($fAbs, $idAbstrak)->groupBy($fRev)->get()->getResultArray()
            );
        }
        if (!$reviewerIds) return 0;

        $affected = 0;
        foreach ($reviewerIds as $rid) {
            if ($rid <= 0) continue;

            $row = $db->table($tblReview)
                ->where($fAbs, $idAbstrak)
                ->where($fRev, $rid)
                ->orderBy($pk,'DESC')
                ->get()->getRowArray();

            $payload = [$fDec => 'pending'];
            if ($fTgl)     $payload[$fTgl]   = $now;
            if ($fType)    $payload[$fType]  = 'abstrak';
            if ($fAsg)     $payload[$fAsg]   = 'accepted';
            if ($fRsn)     $payload[$fRsn]   = null;
            if ($fAcc)     $payload[$fAcc]   = $now;
            if ($fDecAt)   $payload[$fDecAt] = null;
            if ($fRevNo)   $payload[$fRevNo] = $revNow;
            if ($fComment && !$row) $payload[$fComment] = '';

            if ($row) {
                $ok = (bool)$db->table($tblReview)->where($pk, $row[$pk])->update($payload);
                if ($ok) $affected++;
            } else {
                $insert = [$fAbs => $idAbstrak, $fRev => $rid] + $payload;
                $ok = (bool)$db->table($tblReview)->insert($insert);
                if ($ok) $affected++;
            }
        }

        return $affected;
    }

    private function buildContributorsFromRegistration(int $eventId, int $userId): array
    {
        $out = [];

        $reg  = $this->regModel->findUserReg($eventId, $userId);
        $user = $this->userModel->find($userId);

        $presenterEmail = trim((string)($user['email'] ?? ''));
        if ($presenterEmail === '' && !empty($reg['email'])) $presenterEmail = trim((string)$reg['email']);

        $presenterName =
            trim((string)($user['nama_lengkap'] ?? '')) ?:
            trim((string)($user['username']     ?? '')) ?:
            trim((string)($reg['presenter_name'] ?? '')) ?:
            trim((string)($reg['nama']          ?? ''));

        if ($presenterName === '' && $presenterEmail !== '') {
            $local = explode('@', $presenterEmail)[0] ?? '';
            $local = str_replace(['.', '_', '-'], ' ', $local);
            $presenterName = ucwords(preg_replace('/\s+/', ' ', trim($local)));
        }

        $afiliasi = (string)($reg['afiliasi'] ?? '');

        if ($presenterName !== '' || $presenterEmail !== '' || $afiliasi !== '') {
            $out[] = [
                'name'        => $presenterName !== '' ? $presenterName : 'Presenter',
                'email'       => $presenterEmail !== '' ? $presenterEmail : null,
                'affiliation' => $afiliasi !== '' ? $afiliasi : null,
                'presenter'   => true,
                'role'        => 'Presenter',
            ];
        }

        $coauthors = [];
        if (!empty($reg['coauthors_json'])) {
            $decoded = json_decode((string)$reg['coauthors_json'], true);
            if (is_array($decoded)) $coauthors = $decoded;
        }
        foreach ($coauthors as $co) {
            $nm = trim((string)($co['nama'] ?? $co['name'] ?? ''));
            $em = trim((string)($co['email'] ?? ''));
            $af = trim((string)($co['afiliasi'] ?? $co['affiliation'] ?? ''));
            if ($nm === '' && $em === '' && $af === '') continue;
            $out[] = [
                'name'        => $nm !== '' ? $nm : 'Ko-Author',
                'email'       => $em !== '' ? $em : null,
                'affiliation' => $af !== '' ? $af : null,
                'presenter'   => false,
                'role'        => 'Penulis',
            ];
        }

        return $out;
    }
}
