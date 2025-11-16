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

    /* ===================== UTIL / HELPER ===================== */

    /**
     * Cek apakah data kontributor sudah “layak upload”.
     * Fleksibel: cek beberapa field yang mungkin dipakai di DB berbeda-beda.
     */
    private function isContributorCompleted(?array $reg): bool
    {
        if (!$reg) return false;

        foreach (['contributor_done','kontributor_done','profile_completed','is_profile_completed'] as $f) {
            if (array_key_exists($f, $reg)) {
                $v = $reg[$f];
                if ($v === true || $v === 1 || $v === '1' || $v === 't' || $v === 'true') {
                    return true;
                }
            }
        }

        // fallback paling sederhana: kalau afiliasi sudah diisi, kita anggap cukup
        if (!empty($reg['afiliasi'])) return true;

        return false;
    }

    /**
     * Bantu mapping status ke badge/label + hint human readable
     * supaya tampilan rapih & konsisten.
     */
    private function mapStatusMeta(?string $status, array $row = []): array
    {
        $s = strtolower((string)$status);
        $isAuto = false;

        foreach (['auto_reject_at','auto_rejected_at','auto_reject_reason'] as $k) {
            if (!empty($row[$k])) { $isAuto = true; break; }
        }

        if ($s === 'ditolak' && $isAuto) {
            return [
                'badge' => 'danger',
                'label' => 'Ditolak (Otomatis)',
                'hint'  => 'Ditolak otomatis karena melewati batas waktu.'
            ];
        }

        return match ($s) {
            'menunggu'         => ['badge'=>'warning','label'=>'Menunggu','hint'=>'Menunggu review abstrak.'],
            'sedang_direview'  => ['badge'=>'info','label'=>'Sedang direview','hint'=>'Reviewer sedang menilai.'],
            'diterima'         => ['badge'=>'success','label'=>'Diterima (ACC)','hint'=>'Abstrak diterima.'],
            'ditolak'          => ['badge'=>'danger','label'=>'Ditolak','hint'=>'Abstrak ditolak dan event ini tidak bisa diikuti lagi.'],
            default            => ['badge'=>'secondary','label'=>'Belum Upload','hint'=>'Belum ada abstrak.'],
        };
    }

    /**
     * Hitung batas waktu (cutoff) abstrak:
     * - ambil minimal antara abstract_deadline dan H-1 sebelum hari H event.
     *   Jadi nggak ada cerita upload abstrak di hari H.
     */
    private function computeCutoff(?array $event): ?int
    {
        if (!$event) return null;

        $eventDateTs = !empty($event['event_date']) ? strtotime($event['event_date']) : null;
        // H-1 jam 23:59:59
        $hMinus1 = $eventDateTs ? strtotime('-1 day 23:59:59', $eventDateTs) : null;

        $d = !empty($event['abstract_deadline']) ? strtotime($event['abstract_deadline']) : null;

        if ($d && $hMinus1) return min($d, $hMinus1);
        return $d ?: $hMinus1;
    }

    /**
     * Auto-reject abstrak kalau:
     * - status masih "menunggu" / "sedang_direview"
     * - dan sudah lewat cutoff submit.
     *
     * Catatan:
     * - Status "revisi" sudah tidak dipakai di sisi presenter.
     * - Kolom tambahan (auto_reject_*, archived, deleted_from_event) diisi kalau ada.
     */
    private function enforceAutoRejectIfOverdue(int $userId, int $eventId, ?array $lastAbsRow): ?array
    {
        $event = $this->eventModel->find($eventId);
        if (!$event) return $lastAbsRow;

        // kalau belum dikasih row, ambil terakhir dari DB
        if (!$lastAbsRow) {
            $lastAbsRow = $this->abstrakModel
                ->where('id_user', $userId)
                ->where('event_id', $eventId)
                ->orderBy('id_abstrak', 'DESC')
                ->first();

            if (!$lastAbsRow) return null;
        }

        $status = strtolower((string)($lastAbsRow['status'] ?? ''));
        // cuma peduli yang masih dalam proses
        if (!in_array($status, ['menunggu','sedang_direview'], true)) {
            return $lastAbsRow;
        }

        $now       = time();
        $cutoffAbs = $this->computeCutoff($event);

        $shouldReject = false;
        $reason       = null;

        if ($cutoffAbs && $now > $cutoffAbs) {
            $shouldReject = true;
            $reason       = 'Melewati batas waktu pengumpulan abstrak (cutoff).';
        }

        if (!$shouldReject) return $lastAbsRow;

        // amanin kolom opsional supaya nggak error kalau nggak ada
        $db     = \Config\Database::connect();
        $tbl    = $this->abstrakModel->table ?? 'abstrak';
        $fields = array_flip($db->getFieldNames($tbl));

        $payload = ['status' => 'ditolak'];
        if (isset($fields['auto_reject_at']))     $payload['auto_reject_at']     = date('Y-m-d H:i:s');
        if (isset($fields['auto_rejected_at']))   $payload['auto_rejected_at']   = date('Y-m-d H:i:s');
        if (isset($fields['auto_reject_reason'])) $payload['auto_reject_reason'] = $reason;
        if (isset($fields['archived']))           $payload['archived']           = 1;
        if (isset($fields['deleted_from_event'])) $payload['deleted_from_event'] = 1;

        $this->abstrakModel->update((int)$lastAbsRow['id_abstrak'], $payload);

        return $this->abstrakModel
            ->where('id_abstrak', (int)$lastAbsRow['id_abstrak'])
            ->first();
    }

    /* ===== Helper presentasi (biar View lebih kurus) ===== */

    private function formatDate(?string $s): string
    {
        return $s ? date('d M Y', strtotime($s)) : '-';
    }

    private function formatDT(?string $s): string
    {
        return $s ? date('d M Y H:i', strtotime($s)) : '-';
    }

    private function formatLabel(?string $f): string
    {
        $f = strtolower((string)$f);
        return $f === 'both' ? 'Hybrid' : ucfirst($f ?: '-');
    }

    /**
     * Bikin "chip" status (label + class CSS) buat card di halaman list.
     */
    private function chipForStatus(string $status, bool $isAutoReject): array
    {
        return [
            'menunggu'        => ['label'=>'Menunggu',        'cls'=>'chip-wait'],
            'sedang_direview' => ['label'=>'Sedang direview', 'cls'=>'chip-info'],
            'diterima'        => ['label'=>'Diterima',        'cls'=>'chip-success'],
            'ditolak'         => ['label'=>$isAutoReject ? 'Ditolak (Otomatis)' : 'Ditolak', 'cls'=>'chip-danger'],
        ][$status] ?? ['label'=>ucfirst($status), 'cls'=>'chip-muted'];
    }

    private function pillFromBadge(string $badge): string
    {
        return [
            'success'   => 'pill-success',
            'danger'    => 'pill-danger',
            'warning'   => 'pill-warn',
            'info'      => 'pill-info',
            'secondary' => 'pill-muted',
            'primary'   => 'pill-primary'
        ][strtolower($badge) ?: 'secondary'] ?? 'pill-muted';
    }

    /* ===================== PAGES (Presenter) ===================== */

    public function index()
    {
        $userId = (int) session()->get('id_user');
        $regs   = $this->regModel->listByUser($userId) ?? [];

        // Semua abstrak user → pilih TERBARU per event
        $allAbs = $this->abstrakModel->getByUserWithDetails($userId) ?? [];
        $latestPerEvent = [];

        foreach ($allAbs as $a) {
            $eid = (int) ($a['event_id'] ?? 0);
            if (!$eid) continue;

            $ts  = !empty($a['tanggal_upload']) ? strtotime($a['tanggal_upload']) : 0;
            $cur = !empty($latestPerEvent[$eid]['tanggal_upload'])
                ? strtotime($latestPerEvent[$eid]['tanggal_upload'])
                : -1;

            if (!isset($latestPerEvent[$eid]) || $ts > $cur) {
                $latestPerEvent[$eid] = $a;
            }
        }

        $todoCards    = [];
        $historyCards = [];

        foreach ($regs as $r) {
            $eid = (int) ($r['id_event'] ?? 0);
            if (!$eid) continue;

            $event = $this->eventModel->find($eid);
            if (!$event) continue;

            $regRow    = $this->regModel->findUserReg($eid, $userId);
            $contribOK = $this->isContributorCompleted($regRow);

            $isOpenSubmit = $this->eventModel->isAbstractSubmissionOpen($eid);

            // ambil abstrak terakhir & jalankan auto-reject bila perlu
            $last = $latestPerEvent[$eid] ?? null;
            $last = $this->enforceAutoRejectIfOverdue($userId, $eid, $last);

            $lastStatus = strtolower((string)($last['status'] ?? ''));
            $meta       = $this->mapStatusMeta($lastStatus ?: null, $last ?? []);

            $now       = time();
            $cutoffAbs = $this->computeCutoff($event);

            // Belum pernah upload & sudah lewat cutoff → buat "riwayat virtual" ditolak otomatis.
            if (!$last && $cutoffAbs && $now > $cutoffAbs) {
                $m = $this->mapStatusMeta('ditolak', ['auto_reject_at'=>date('Y-m-d H:i:s')]);
                $historyCards[] = [
                    'id'           => 0,
                    'title'        => '-',
                    'event_title'  => $event['title'] ?? '-',
                    'event_date'   => $this->formatDate($event['event_date'] ?? null),
                    'uploaded_at'  => '-',
                    'status_label' => $m['label'],
                    'status_pill'  => $this->pillFromBadge($m['badge']),
                    'kategori'     => '-',
                    'hint'         => 'Tidak mengunggah abstrak sampai batas waktu.',
                    'detail_url'   => null,
                    'searchable'   => strtolower(($event['title'] ?? '').' '.$m['label']),
                ];
                continue;
            }

            // Hitung apakah boleh upload abstrak (tombol "Upload Abstrak" muncul atau tidak)
            $canUpload = false;
            if ($contribOK && !$last) {
                $canUpload = $isOpenSubmit;
            }

            // Kalau sudah "finish" (diterima / ditolak) → pindah ke Riwayat
            if ($last && in_array($lastStatus, ['diterima','ditolak'], true)) {
                $historyCards[] = [
                    'id'           => (int)($last['id_abstrak'] ?? 0),
                    'title'        => $last['judul'] ?? ($event['title'] ?? '-'),
                    'event_title'  => $event['title'] ?? '-',
                    'event_date'   => $this->formatDate($event['event_date'] ?? null),
                    'uploaded_at'  => $this->formatDT($last['tanggal_upload'] ?? null),
                    'status_label' => $meta['label'],
                    'status_pill'  => $this->pillFromBadge($meta['badge']),
                    'kategori'     => $last['nama_kategori'] ?? '-',
                    'hint'         => $meta['hint'],
                    'detail_url'   => site_url('presenter/abstrak/detail/'.(int)($last['id_abstrak'] ?? 0)),
                    'searchable'   => strtolower(
                        ($last['judul'] ?? '') .' '.($event['title'] ?? '').' '.($meta['label'] ?? '').' '.
                        ($last['nama_kategori'] ?? '')
                    ),
                ];
                continue;
            }

            // Selain diterima/ditolak → masih proses → To-Do
            $hasLast    = !empty($last['id_abstrak']);
            $primaryUrl = site_url('presenter/abstrak/create/'.$eid);
            $primaryTxt = 'Upload Abstrak';

            $todoCards[] = [
                'event_id'     => $eid,
                'title'        => $last['judul'] ?? ($event['title'] ?? '-'),
                'status_label' => $meta['label'],
                'status_pill'  => $this->pillFromBadge($meta['badge']),
                'format'       => $this->formatLabel($event['format'] ?? ''),
                'event_date'   => $this->formatDate($event['event_date'] ?? null),
                'abs_deadline' => $this->formatDT($event['abstract_deadline'] ?? null),
                'rev_deadline' => null,
                'hint'         => $meta['hint'],
                'can_upload'   => $canUpload,
                'primary_url'  => $primaryUrl,
                'primary_text' => $primaryTxt,
                'detail_url'   => $hasLast ? site_url('presenter/abstrak/detail/'.(int)$last['id_abstrak']) : null,
                'searchable'   => strtolower(
                    ($event['title'] ?? '') .' '. ($meta['label'] ?? '') .' '.($event['format'] ?? '') .' '.
                    ($this->formatDate($event['event_date'] ?? null)) .' '. ($this->formatDT($event['abstract_deadline'] ?? null))
                ),
                'is_revisi'    => false,
            ];
        }

        // Urutkan Riwayat dari upload terbaru
        usort($historyCards, function($a,$b){
            $aTime = ($a['uploaded_at'] ?? '-') === '-' ? 0 : strtotime($a['uploaded_at']);
            $bTime = ($b['uploaded_at'] ?? '-') === '-' ? 0 : strtotime($b['uploaded_at']);
            return $bTime <=> $aTime;
        });

        return view('role/presenter/abstrak/index', [
            'title'        => 'Abstrak',
            'todoCards'    => $todoCards,
            'historyCards' => $historyCards,
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

        // Pastikan status auto reject terbaru sudah ditegakkan
        $lastAbsLatest = $this->abstrakModel
            ->where('id_user',$userId)
            ->where('event_id',$eventId)
            ->orderBy('id_abstrak','DESC')
            ->first();
        $lastAbsLatest = $this->enforceAutoRejectIfOverdue($userId, $eventId, $lastAbsLatest);

        // Kalau abstraknya sudah ditolak → tidak boleh upload lagi
        if ($lastAbsLatest && strtolower((string)$lastAbsLatest['status']) === 'ditolak') {
            return redirect()->to('/presenter/abstrak')
                ->with('error','Abstrak Anda untuk event ini sudah ditolak. Anda tidak dapat mengunggah ulang.')
                ->with('swal', [
                    'icon'=>'info',
                    'title'=>'Event Sudah Selesai',
                    'text'=>'Silakan ikut event lain jika ingin mengirim abstrak lagi.'
                ]);
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

        $lastAbs = $this->abstrakModel
            ->where('id_user',$userId)
            ->where('event_id',$eventId)
            ->orderBy('id_abstrak','DESC')
            ->first();

        $formLocked     = false;
        $showAlert      = false;
        $lastUploadedId = 0;

        if ($lastAbs && in_array(strtolower((string)$lastAbs['status']), ['menunggu','sedang_direview','diterima'], true)) {
            $formLocked     = true;
            $showAlert      = true;
            $lastUploadedId = (int)$lastAbs['id_abstrak'];
        } else {
            $flashShow = (bool) session()->getFlashdata('show_fp_cta');
            $flashId   = (int) (session()->getFlashdata('just_uploaded_id') ?? 0);
            if ($flashShow && $flashId > 0) {
                $row = $this->abstrakModel
                    ->where('id_abstrak',$flashId)
                    ->where('id_user',$userId)
                    ->where('event_id',$eventId)
                    ->first();
                if ($row) {
                    $showAlert      = true;
                    $formLocked     = true;
                    $lastUploadedId = (int)$row['id_abstrak'];
                    $lastAbs        = $row;
                }
            }
        }

        $kategoriList = $this->kategoriModel
            ->where('is_active', true)
            ->orderBy('nama_kategori','ASC')
            ->findAll();

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

        $lastAbsLatest = $this->abstrakModel
            ->where('id_user',$userId)
            ->where('event_id',$eventId)
            ->orderBy('id_abstrak','DESC')
            ->first();
        $lastAbsLatest = $this->enforceAutoRejectIfOverdue($userId, $eventId, $lastAbsLatest);

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

        $already = $this->abstrakModel
            ->where('id_user',$userId)
            ->where('event_id',$eventId)
            ->orderBy('id_abstrak','DESC')
            ->first();

        if ($already && in_array(
                strtolower((string)$already['status']),
                ['menunggu','sedang_direview','diterima','ditolak'],
                true
            )) {
            return redirect()->to('/presenter/abstrak')
                ->with('error','Anda sudah memiliki riwayat abstrak untuk event ini.')
                ->with('swal', [
                    'icon'=>'warning',
                    'title'=>'Tidak Bisa Upload',
                    'text'=>'Jika abstrak pernah dikirim (termasuk yang ditolak), event ini tidak bisa diikuti lagi.'
                ]);
        }

        $katRow = $this->kategoriModel
            ->select('id_kategori, is_active, nama_kategori')
            ->where('id_kategori', $idKategori)
            ->first();

        if (!$katRow || !(bool)$katRow['is_active']) {
            return redirect()->back()->withInput()
                ->with('error','Kategori tidak valid / nonaktif.')
                ->with('swal', [
                    'icon'=>'warning',
                    'title'=>'Kategori Tidak Tersedia',
                    'text'=>'Pilih kategori yang masih aktif.'
                ]);
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
                'revisi_ke'      => 0,
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
        $row = $this->abstrakModel
            ->where('id_abstrak', (int)$idAbstrak)
            ->where('id_user', $userId)
            ->first();

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

        $latest = $this->abstrakModel
            ->where('id_user', $userId)
            ->where('event_id', $eventId)
            ->orderBy('id_abstrak','DESC')
            ->first();

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

        $rowRaw = $this->abstrakModel->where('id_abstrak',(int)$idAbstrak)->first();
        if ($rowRaw) {
            $this->enforceAutoRejectIfOverdue($userId, (int)$rowRaw['event_id'], $rowRaw);
        }

        $row = $this->abstrakModel->getDetailWithRelationsForUser((int)$idAbstrak, $userId);
        if (!$row) {
            return redirect()->to('/presenter/abstrak')
                ->with('error','Abstrak tidak ditemukan.')
                ->with('swal', ['icon'=>'error','title'=>'Gagal','text'=>'Abstrak tidak ditemukan.']);
        }

        $event  = $this->eventModel->find((int)$row['event_id']);
        $status = strtolower((string)($row['status'] ?? 'menunggu'));
        $meta   = $this->mapStatusMeta($status, $row);
        $isAutoReject = ($status==='ditolak')
            && (!empty($row['auto_reject_at']) || !empty($row['auto_reject_reason']));
        $chip = $this->chipForStatus($status, $isAutoReject);

        $reviews  = $this->reviewModel->getReviewsForDisplay((int)$idAbstrak);
        $timeline = [];

        foreach ($reviews as $rv) {
            $kep  = strtolower($rv['keputusan'] ?? 'pending');
            $kLab = [
                'diterima'         => ['success','Diterima'],
                'ditolak'          => ['danger','Ditolak'],
                'revisi'           => ['primary','Revisi'],
                'sedang_direview'  => ['info','Sedang direview'],
                'pending'          => ['secondary','Pending'],
            ][$kep] ?? ['secondary', ucfirst($kep ?: 'pending')];

            $timeline[] = [
                'revisi_ke' => isset($rv['revisi_ke']) ? (int)$rv['revisi_ke'] : null,
                'badge'     => $kLab[0],
                'badge_txt' => $kLab[1],
                'reviewer'  => trim((string)($rv['reviewer_name'] ?? 'Reviewer')),
                'email'     => trim((string)($rv['reviewer_email'] ?? '')),
                'waktu'     => $this->formatDT($rv['tanggal_review'] ?? null),
                'komentar'  => trim((string)($rv['display_comment'] ?? $rv['komentar'] ?? '')),
            ];
        }

        $assigned = null;
        foreach ($reviews as $rv) {
            if (($rv['keputusan'] ?? '') === 'pending') {
                $assigned = $rv;
                break;
            }
        }
        if (!$assigned && !empty($reviews)) {
            $assigned = $reviews[0];
        }

        $assignedReviewer = [
            'name'  => $assigned['reviewer_name']  ?? null,
            'email' => $assigned['reviewer_email'] ?? null,
            'org'   => null,
        ];

        // helper ini kita tambahkan lagi
        $contributors = $this->buildContributorsFromRegistration((int)$row['event_id'], $userId);

        $fileName = (string)($row['file_abstrak'] ?? '');
        $filePath = $fileName !== '' ? WRITEPATH.'uploads/abstrak/'.$fileName : null;
        $fileOK   = $filePath && is_file($filePath);

        $streamUrl   = $fileOK ? site_url('presenter/abstrak/file/'.$idAbstrak)    : null;
        $downloadUrl = $fileOK ? site_url('presenter/abstrak/download/'.$idAbstrak) : null;
        $gdocsUrl    = $fileOK
            ? 'https://docs.google.com/gview?embedded=1&url=' . urlencode($streamUrl)
            : null;

        $vm = [
            'judul'       => (string)($row['judul'] ?? '—'),
            'event_title' => (string)($event['title'] ?? '-'),
            'kategori'    => (string)($row['nama_kategori'] ?? '-'),
            'uploaded_at' => $this->formatDT($row['tanggal_upload'] ?? null),
            'chip'        => $chip,

            'file_exists'       => $fileOK,
            'file_stream_url'   => $streamUrl,
            'file_download_url' => $downloadUrl,
            'gdocs_viewer_url'  => $gdocsUrl,

            'is_auto_reject' => $isAutoReject,
            'auto_reason'    => (string)($row['auto_reject_reason'] ?? ''),
            'auto_at'        => $this->formatDT($row['auto_reject_at'] ?? ($row['auto_rejected_at'] ?? null)),

            'can_reupload' => false,
            'reupload_url' => null,
            'show_cancel'  => ($status === 'menunggu'),
            'cancel_action'=> site_url('/presenter/abstrak/cancel/'.(int)$row['id_abstrak']),

            'timeline'          => $timeline,
            'event_date'        => $this->formatDate($event['event_date'] ?? null),
            'abs_deadline'      => $this->formatDT($event['abstract_deadline'] ?? null),
            'rev_deadline_info' => null,
            'fp_deadline'       => $this->formatDT($event['full_paper_deadline'] ?? null),

            'status'            => $status,
            'assigned_reviewer' => $assignedReviewer,
            'contributors'      => $contributors,
        ];

        return view('role/presenter/abstrak/detail', [
            'title' => 'Detail Abstrak',
            'vm'    => $vm,
        ]);
    }

    /* ========= Helper kontributor (dibaca di detail) ========= */

    /**
     * Susun daftar kontributor dari data registrasi + user.
     * Tujuannya supaya di detail abstrak user tetap bisa melihat:
     * - siapa presenter utamanya
     * - siapa saja co-author yang diisi di form registrasi
     */
    private function buildContributorsFromRegistration(int $eventId, int $userId): array
    {
        $out  = [];

        $reg  = $this->regModel->findUserReg($eventId, $userId);
        $user = $this->userModel->find($userId);

        $presenterEmail = trim((string)($user['email'] ?? ''));
        if ($presenterEmail === '' && !empty($reg['email'])) {
            $presenterEmail = trim((string)$reg['email']);
        }

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

    /* ===================== REVIEW QUEUE (dipertahankan) ===================== */

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
        $fDec = isset($rFields['keputusan']) ? 'keputusan'
              : (isset($rFields['status']) ? 'status'
              : (isset($rFields['decision']) ? 'decision' : null));
        $fTgl = isset($rFields['tanggal_review']) ? 'tanggal_review'
              : (isset($rFields['updated_at']) ? 'updated_at'
              : (isset($rFields['created_at']) ? 'created_at' : null));
        $fType= isset($rFields['type']) ? 'type' : (isset($rFields['review_type']) ? 'review_type' : null);

        $fAsg = null; foreach (['status_tugas','tugas_status','assignment_status','konfirmasi_status'] as $c) {
            if (isset($rFields[$c])) { $fAsg = $c; break; }
        }
        $fRsn = null; foreach (['alasan_tolak','alasan','decline_reason','reason'] as $c) {
            if (isset($rFields[$c])) { $fRsn = $c; break; }
        }
        $fAcc = null; foreach (['accepted_at','confirmed_at','konfirmasi_at'] as $c) {
            if (isset($rFields[$c])) { $fAcc = $c; break; }
        }
        $fDecAt = null; foreach (['declined_at','rejected_at'] as $c) {
            if (isset($rFields[$c])) { $fDecAt = $c; break; }
        }
        $fRevNo = null; foreach (['revisi_ke','revision_no'] as $c) {
            if (isset($rFields[$c])) { $fRevNo = $c; break; }
        }
        $fComment = null; foreach (['komentar','comment','notes','catatan'] as $c) {
            if (isset($rFields[$c])) { $fComment = $c; break; }
        }

        if (!$fAbs || !$fRev || !$fDec) return 0;

        $now    = date('Y-m-d H:i:s');
        $revNow = (int)($db->table('abstrak')
            ->select('revisi_ke')
            ->where('id_abstrak',$idAbstrak)
            ->get()
            ->getRowArray()['revisi_ke'] ?? 0);

        $reviewerIds = [];
        $pivot = null;
        foreach (['abstrak_reviewers','abstrak_reviewer','reviewer_abstrak'] as $t) {
            if ($db->tableExists($t)) { $pivot = $t; break; }
        }
        if ($pivot) {
            $pFields = array_flip($db->getFieldNames($pivot));
            $colAbs  = isset($pFields['id_abstrak']) ? 'id_abstrak'
                      : (isset($pFields['abstrak_id']) ? 'abstrak_id' : null);
            $colRev  = isset($pFields['id_reviewer']) ? 'id_reviewer'
                      : (isset($pFields['reviewer_id']) ? 'reviewer_id' : null);
            if ($colAbs && $colRev) {
                $reviewerIds = array_map(
                    fn($r) => (int)$r['reviewer_id'],
                    $db->table($pivot)
                        ->select("$colRev AS reviewer_id")
                        ->where($colAbs, $idAbstrak)
                        ->get()
                        ->getResultArray()
                );
            }
        }

        if (!$reviewerIds) {
            $reviewerIds = array_map(
                fn($r) => (int)$r['reviewer_id'],
                $db->table($tblReview)
                    ->select("$fRev AS reviewer_id")
                    ->where($fAbs, $idAbstrak)
                    ->groupBy($fRev)
                    ->get()
                    ->getResultArray()
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
                ->get()
                ->getRowArray();

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
                $ok = (bool)$db->table($tblReview)
                    ->where($pk, $row[$pk])
                    ->update($payload);
                if ($ok) $affected++;
            } else {
                $insert = [$fAbs => $idAbstrak, $fRev => $rid] + $payload;
                $ok = (bool)$db->table($tblReview)->insert($insert);
                if ($ok) $affected++;
            }
        }

        return $affected;
    }

    /* ===================== FILE HANDLER ===================== */

    public function fileStream($idAbstrak)
    {
        $userId = (int) session()->get('id_user');
        $row = $this->abstrakModel
            ->where('id_abstrak',(int)$idAbstrak)
            ->where('id_user',$userId)
            ->first();

        if (!$row) {
            return $this->response->setStatusCode(404, 'Not Found');
        }

        $file = (string)($row['file_abstrak'] ?? '');
        $path = WRITEPATH.'uploads/abstrak/'.$file;

        if ($file === '' || !is_file($path)) {
            return $this->response->setStatusCode(404, 'File not found');
        }

        $content = file_get_contents($path);
        $name    = basename($file);

        return $this->response
            ->setHeader('Content-Type', 'application/pdf')
            ->setHeader('Content-Disposition', 'inline; filename="'.$name.'"')
            ->setHeader('Accept-Ranges', 'bytes')
            ->setBody($content);
    }

    public function fileDownload($idAbstrak)
    {
        $userId = (int) session()->get('id_user');
        $row = $this->abstrakModel
            ->where('id_abstrak',(int)$idAbstrak)
            ->where('id_user',$userId)
            ->first();

        if (!$row) {
            return $this->response->setStatusCode(404, 'Not Found');
        }

        $file = (string)($row['file_abstrak'] ?? '');
        $path = WRITEPATH.'uploads/abstrak/'.$file;

        if ($file === '' || !is_file($path)) {
            return $this->response->setStatusCode(404, 'File not found');
        }

        return $this->response->download($path, null);
    }
}
