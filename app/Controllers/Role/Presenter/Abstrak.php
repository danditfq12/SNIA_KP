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

    /** Cek kontributor lengkap (boleh via flag boolean / afiliasi terisi) */
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

    /** Map status → meta utk badge/label/hint */
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

    /** ===== INDEX ===== */
    public function index()
    {
        $userId = (int) session()->get('id_user');
        $regs   = $this->regModel->listByUser($userId) ?? [];

        // Ambil abstrak terakhir per event
        $allAbs = $this->abstrakModel->getByUserWithDetails($userId) ?? [];
        $latestPerEvent = [];
        foreach ($allAbs as $a) {
            $eid = (int) ($a['event_id'] ?? 0);
            if (!$eid) continue;
            $ts  = !empty($a['tanggal_upload']) ? strtotime($a['tanggal_upload']) : 0;
            $cur = !empty($latestPerEvent[$eid]['tanggal_upload']) ? strtotime($latestPerEvent[$eid]['tanggal_upload']) : -1;
            if (!isset($latestPerEvent[$eid]) || $ts > $cur) $latestPerEvent[$eid] = $a;
        }

        $needsUpload = [];
        $history     = [];

        foreach ($regs as $r) {
            $eid = (int) ($r['id_event'] ?? 0);
            if (!$eid) continue;

            $event = $this->eventModel->find($eid);
            if (!$event) continue;

            $regRow    = $this->regModel->findUserReg($eid, $userId);
            $contribOK = $this->isContributorCompleted($regRow);

            $isOpen = $this->eventModel->isAbstractSubmissionOpen($eid);
            $last   = $latestPerEvent[$eid] ?? null;

            $canUpload = false;
            if ($isOpen && $contribOK) {
                if (!$last) $canUpload = true;
                else {
                    $ls = strtolower((string)($last['status'] ?? ''));
                    $canUpload = in_array($ls, ['revisi','ditolak'], true);
                }
            }

            if ($canUpload) {
                $meta = $this->mapStatusMeta($last['status'] ?? null);
                $needsUpload[] = [
                    'event_id'          => $eid,
                    'title'             => $event['title'] ?? '-',
                    'event_date'        => $event['event_date'] ?? null,
                    'abstract_deadline' => $event['abstract_deadline'] ?? null,
                    'format'            => strtolower($event['format'] ?? ''),
                    'status_badge'      => $meta['badge'],
                    'status_label'      => $meta['label'],
                    'hint'              => $meta['hint'],
                    'last_abs_id'       => $last['id_abstrak'] ?? null,
                ];
            }

            if ($last) {
                $meta = $this->mapStatusMeta($last['status'] ?? null);
                $history[] = [
                    'id_abstrak'     => (int) ($last['id_abstrak'] ?? 0),
                    'judul'          => $last['judul'] ?? '-',
                    'nama_kategori'  => $last['nama_kategori'] ?? '-',
                    'status'         => strtolower((string)($last['status'] ?? '')),
                    'status_badge'   => $meta['badge'],
                    'status_label'   => $meta['label'],
                    'status_hint'    => $meta['hint'],
                    'tanggal_upload' => $last['tanggal_upload'] ?? null,
                    'event_id'       => $eid,
                    'event_title'    => $event['title'] ?? '-',
                    'event_date'     => $event['event_date'] ?? null,
                ];
            }
        }

        usort($history, fn($a,$b) => strtotime($b['tanggal_upload'] ?? '1970-01-01') <=> strtotime($a['tanggal_upload'] ?? '1970-01-01'));

        return view('role/presenter/abstrak/index', [
            'title'        => 'Abstrak',
            'uploadEvents' => $needsUpload,
            'history'      => $history,
        ]);
    }

    /** ===== CREATE FORM ===== */
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

        // Ambil abstrak terakhir user pada event ini
        $lastAbs = $this->abstrakModel->where('id_user',$userId)
                    ->where('event_id',$eventId)->orderBy('id_abstrak','DESC')->first();

        // Kunci form jika status aktif
        $formLocked = false;
        $showAlert  = false;
        $lastUploadedId = 0;

        if ($lastAbs && in_array(strtolower((string)$lastAbs['status']), ['menunggu','sedang_direview','diterima'], true)) {
            $formLocked     = true;
            $showAlert      = true;                         // tampilkan alert “tunggu reviewer”
            $lastUploadedId = (int)$lastAbs['id_abstrak'];  // untuk tombol Batalkan
        } else {
            // Cek flash “baru upload” supaya alert muncul tepat setelah store()
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
            'showFpCta'       => $showAlert,      // munculkan alert + tombol Next FP + Batalkan
            'lastUploadedId'  => $lastUploadedId, // untuk Batalkan
            'formLocked'      => $formLocked,     // VIEW akan disable form
        ]);
    }

    /** ===== STORE UPLOAD ===== (sama persis dengan punyamu) */
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

        if (!$file->isValid()) {
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

        // Kembali ke halaman create yang sama, tampilkan CTA lanjut FP + popup
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

    /**
     * ===== BATALKAN UPLOAD =====
     */
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

        // Pastikan abstrak terakhir untuk event tsb
        $latest = $this->abstrakModel->where('id_user', $userId)
                    ->where('event_id', $eventId)
                    ->orderBy('id_abstrak','DESC')->first();
        if (!$latest || (int)$latest['id_abstrak'] !== (int)$idAbstrak) {
            return redirect()->to('/presenter/abstrak/detail/'.$idAbstrak)
                ->with('error', 'Hanya abstrak terbaru yang dapat dibatalkan.')
                ->with('swal', ['icon'=>'warning','title'=>'Tidak Bisa','text'=>'Hanya abstrak terbaru yang dapat dibatalkan.']);
        }

        // Hapus file jika ada
        $file = (string)($row['file_abstrak'] ?? '');
        if ($file !== '') {
            $path = WRITEPATH.'uploads/abstrak/'.$file;
            if (is_file($path)) @unlink($path);
        }

        // Hapus record
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

    /** ===== DETAIL ===== */
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

        // ===== Ambil review (reviewer + komentar) dari ReviewModel
        $reviews = $this->reviewModel->getReviewsForDisplay((int)$idAbstrak);

        $reviewComments = [];
        foreach ($reviews as $rv) {
            if (!empty($rv['display_comment'])) {
                $reviewComments[] = trim((string)$rv['display_comment']);
            }
        }
        $reviewComments = array_values(array_unique(array_filter($reviewComments, fn($v)=>$v!=='')));

        // Tentukan reviewer ditugaskan: prioritas yang pending, kalau tidak ada ambil review terbaru
        $assigned = null;
        foreach ($reviews as $rv) {
            if (($rv['keputusan'] ?? '') === 'pending') { $assigned = $rv; break; }
        }
        if (!$assigned && !empty($reviews)) $assigned = $reviews[0];

        $assignedReviewer = [
            'name'  => $assigned['reviewer_name']  ?? null,
            'email' => $assigned['reviewer_email'] ?? null,
            'org'   => null, // kolom org tidak di-join di model; bisa ditambah nanti
        ];

        // ===== Kontributor mengikuti PATOKAN Kontributor (registrasi + presenter)
        $contributors = $this->buildContributorsFromRegistration((int)$row['event_id'], $userId);

        // Tombol aksi
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
            'contributors'         => $contributors,
        ]);
    }

    /**
     * ====== Builder Kontributor (mengacu ke Controller Kontributor) ======
     * - Presenter: ambil dari user/reg sebagai di Kontributor::start
     * - Coauthors: dari reg.coauthors_json
     */
    private function buildContributorsFromRegistration(int $eventId, int $userId): array
    {
        $out = [];

        $reg  = $this->regModel->findUserReg($eventId, $userId);
        $user = $this->userModel->find($userId);

        // presenter email
        $presenterEmail = trim((string)($user['email'] ?? ''));
        if ($presenterEmail === '' && !empty($reg['email'])) $presenterEmail = trim((string)$reg['email']);

        // presenter name (mengikuti Kontributor::start)
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

        // Tambahkan presenter dulu
        if ($presenterName !== '' || $presenterEmail !== '' || $afiliasi !== '') {
            $out[] = [
                'name'        => $presenterName !== '' ? $presenterName : 'Presenter',
                'email'       => $presenterEmail !== '' ? $presenterEmail : null,
                'affiliation' => $afiliasi !== '' ? $afiliasi : null,
                'presenter'   => true,
                'role'        => 'Presenter',
            ];
        }

        // Coauthors dari JSON
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