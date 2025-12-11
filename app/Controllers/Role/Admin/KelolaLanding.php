<?php

namespace App\Controllers\Role\Admin;

use App\Controllers\BaseController;
use App\Models\EventModel;
use App\Models\EventPosterModel;
use App\Models\EventSpeakerModel;
use App\Models\EventSponsorModel;
use App\Models\EventRegistrationModel;
use App\Models\AbstrakModel;

class KelolaLanding extends BaseController
{
    protected EventModel $eventModel;
    protected EventPosterModel $posterModel;
    protected EventSpeakerModel $speakerModel;
    protected EventSponsorModel $sponsorModel;
    protected EventRegistrationModel $regModel;
    protected AbstrakModel $abstrakModel;
    protected $db;

    // 🔥 penampung log debug untuk sync FP → speaker
    protected array $fpDebugLog = [];

    public function __construct()
    {
        $this->eventModel   = new EventModel();
        $this->posterModel  = new EventPosterModel();
        $this->speakerModel = new EventSpeakerModel();
        $this->sponsorModel = new EventSponsorModel();
        $this->regModel     = new EventRegistrationModel();
        $this->abstrakModel = new AbstrakModel();
        $this->db           = \Config\Database::connect();

        helper(['url', 'form', 'text', 'filesystem']);
    }

    /* =========================================================
     * HELPER DEBUG
     * ======================================================= */

    /**
     * Simpan log debug ke array & ke log CI.
     */
    protected function fpLog(string $msg): void
    {
        $this->fpDebugLog[] = $msg;
        log_message('debug', '[FP_SYNC] ' . $msg);
    }

    /* =========================================================
     * UTILITAS: sumber data full paper (submissions / abstrak)
     * ======================================================= */

    /**
     * Cari tabel sumber fullpaper:
     * - prioritas: submissions
     * - fallback : abstrak
     */
    protected function fullpaperSourceTable(): ?string
    {
        if ($this->db->tableExists('submissions')) {
            $this->fpLog("Menemukan tabel 'submissions' sebagai sumber fullpaper.");
            return 'submissions';
        }
        if ($this->db->tableExists('abstrak')) {
            $this->fpLog("Tabel 'submissions' tidak ada, gunakan 'abstrak' sebagai sumber fullpaper.");
            return 'abstrak';
        }
        $this->fpLog("❌ Tidak menemukan tabel submissions/abstrak sebagai sumber fullpaper.");
        return null;
    }

    /**
     * Ambil nama kolom berdasarkan beberapa kandidat.
     */
    protected function pickColumn(string $table, array $candidates): ?string
    {
        $fields = array_flip($this->db->getFieldNames($table) ?: []);
        foreach ($candidates as $c) {
            if (isset($fields[$c])) {
                return $c;
            }
        }
        return null;
    }

    /**
     * Ambil daftar id_user yang full paper-nya ACCEPTED untuk event tertentu.
     * Patokan status pakai isFpAccepted().
     */
    protected function getAcceptedFullpaperUserIds(int $eventId): array
    {
        $table = $this->fullpaperSourceTable();
        if (!$table || $eventId <= 0) {
            $this->fpLog("getAcceptedFullpaperUserIds: table null atau eventId <= 0 ({$eventId}).");
            return [];
        }

        $this->fpLog("getAcceptedFullpaperUserIds: cek tabel {$table} untuk event {$eventId}.");

        // cari kolom-kolom fleksibel
        $eventCol  = $this->pickColumn($table, ['event_id','id_event','events_id']);
        $userCol   = $this->pickColumn($table, ['id_user','user_id','id_presenter','presenter_id']);
        $statusCol = $this->pickColumn($table, ['full_paper_status','status_fullpaper','status_full_paper','fp_status']);

        $this->fpLog("Kolom di {$table}: event={$eventCol}, user={$userCol}, status={$statusCol}");

        if (!$eventCol || !$userCol || !$statusCol) {
            $this->fpLog("❌ Struktur tabel {$table} tidak lengkap (event/user/status) → SKIP.");
            return [];
        }

        $rows = $this->db->table($table)
            ->select("$userCol AS id_user, $statusCol AS full_paper_status")
            ->where($eventCol, $eventId)
            ->get()
            ->getResultArray();

        $this->fpLog("Row fullpaper di {$table} untuk event {$eventId}: " . count($rows));

        if (!$rows) {
            $this->fpLog("Tidak ada row fullpaper di tabel {$table} untuk event {$eventId}.");
            return [];
        }

        $acceptedUsers = [];

        foreach ($rows as $r) {
            $status = $r['full_paper_status'] ?? '';
            $uid    = (int)($r['id_user'] ?? 0);

            $isAccepted = $this->isFpAccepted($status);

            $this->fpLog("Row: user={$uid}, status='{$status}' → " . ($isAccepted ? 'ACCEPTED' : 'NOT ACCEPTED'));

            if ($isAccepted && $uid > 0) {
                $acceptedUsers[] = $uid;
            }
        }

        // Hilangkan duplikat + kosong
        $acceptedUsers = array_values(array_unique(array_filter($acceptedUsers)));

        $this->fpLog("Total user ACCEPTED (unique) dari {$table} untuk event {$eventId}: " . count($acceptedUsers));

        if (empty($acceptedUsers)) {
            $this->fpLog("Tidak ada user accepted pada event {$eventId} dari tabel {$table}.");
        }

        return $acceptedUsers;
    }

    /* =========================================================
     * INDEX: daftar event
     * ======================================================= */
    public function index()
    {
        // Biar simple dulu: tampilkan semua event, diurut tanggal
        // (kalau mau difilter aktif/upcoming nanti bisa ditambah lagi)
        $events = $this->eventModel
            ->orderBy('event_date', 'ASC')
            ->findAll();

        // Event yang benar-benar menjadi landing (hanya satu)
        $currentLanding = $this->eventModel
            ->where('is_landing', true)   // boolean, BUKAN 1/0
            ->first();

        // Tambahkan info poster / speaker / sponsor per event
        foreach ($events as &$e) {
            $eventId = (int)($e['id'] ?? 0);

            // Poster aktif
            $poster = $this->posterModel
                ->where('event_id', $eventId)
                ->where('is_active', true)      // boolean
                ->orderBy('id', 'DESC')
                ->first();
            $e['has_poster'] = $poster !== null;

            // Speakers aktif
            $e['speaker_count'] = $this->speakerModel
                ->where('event_id', $eventId)
                ->where('is_active', true)
                ->countAllResults();

            // Sponsors aktif
            $e['sponsor_count'] = $this->sponsorModel
                ->where('event_id', $eventId)
                ->where('is_active', true)
                ->countAllResults();
        }
        unset($e);

        return view('role/admin/landing/index', [
            'title'          => 'Kelola Landing Page',
            'events'         => $events,
            'currentLanding' => $currentLanding,
        ]);
    }

    /* =========================================================
     * SET / CLEAR / UNSET EVENT LANDING
     * ======================================================= */

    public function setEvent($id = null)
    {
        $id = (int)$id;

        if ($id <= 0) {
            return redirect()->back()->with('error', 'ID event tidak valid.');
        }

        $event = $this->eventModel->find($id);
        if (!$event) {
            return redirect()->back()->with('error', 'Event tidak ditemukan.');
        }

        try {
            $this->db->transBegin();

            // Reset semua
            $this->db->query('UPDATE events SET is_landing = FALSE');

            // Set satu event
            $this->db->query('UPDATE events SET is_landing = TRUE WHERE id = ?', [$id]);

            if ($this->db->transStatus() === false) {
                $this->db->transRollback();
                throw new \RuntimeException("Gagal menyimpan perubahan.");
            }

            $this->db->transCommit();

            return redirect()->to(site_url('admin/landing'))
                ->with('success', 'Event berhasil dijadikan landing.');

        } catch (\Throwable $e) {

            log_message('error', 'SET_EVENT_ERROR: '.$e->getMessage());

            return redirect()->to(site_url('admin/landing'))
                ->with('error', 'Gagal mengubah pengaturan landing: '.$e->getMessage());
        }
    }

    /**
     * Dipanggil dari tombol hijau "Sedang Dipakai" (route: admin/landing/unset-event/{id})
     */
    public function unsetEvent($id = null)
    {
        $id = (int)$id;
        if ($id <= 0) {
            return redirect()->to(site_url('admin/landing'))
                ->with('error', 'ID event tidak valid.');
        }

        $event = $this->eventModel->find($id);
        if (!$event) {
            return redirect()->to(site_url('admin/landing'))
                ->with('error', 'Event tidak ditemukan.');
        }

        // Kalau sudah bukan landing, anggap saja sukses
        if (empty($event['is_landing'])) {
            return redirect()->to(site_url('admin/landing'))
                ->with('success', 'Event sudah tidak dipakai di landing.');
        }

        try {
            $this->eventModel->update($id, [
                'is_landing' => false,
                'updated_at' => date('Y-m-d H:i:s'),
            ]);

            return redirect()->to(site_url('admin/landing'))
                ->with('success', 'Event berhasil dilepas dari landing page.');
        } catch (\Throwable $e) {
            log_message('error', 'Gagal unset event landing: ' . $e->getMessage());

            return redirect()->to(site_url('admin/landing'))
                ->with('error', 'Terjadi kesalahan saat mengubah pengaturan landing.');
        }
    }

    public function clearLanding()
    {
        try {
            $this->db->transBegin();

            // Raw SQL → 100% berjalan di PostgreSQL!
            $this->db->query('UPDATE events SET is_landing = FALSE');

            if ($this->db->transStatus() === false) {
                $this->db->transRollback();
                throw new \RuntimeException('Clear landing transaction failed.');
            }

            $this->db->transCommit();

            return redirect()->to(site_url('admin/landing'))
                ->with('success', 'Pengaturan event landing dikosongkan.');

        } catch (\Throwable $e) {
            log_message('error', 'CLEAR LANDING ERROR: ' . $e->getMessage());

            return redirect()->to(site_url('admin/landing'))
                ->with('error', 'Gagal membersihkan event landing: '.$e->getMessage());
        }
    }

    /* =========================================================
     * DETAIL + AUTO SYNC SPEAKER dari FULL PAPER ACC
     * ======================================================= */

    public function detail($id = null)
    {
        $id = (int)$id;
        if ($id <= 0) {
            return redirect()->to(site_url('admin/landing'))
                ->with('error', 'ID event tidak valid.');
        }

        $event = $this->eventModel->find($id);
        if (!$event) {
            return redirect()->to(site_url('admin/landing'))
                ->with('error', 'Event tidak ditemukan.');
        }

        // 🔥 reset log debug setiap kali buka detail
        $this->fpDebugLog = [];
        $this->fpLog("=== MULAI SYNC SPEAKER EVENT {$id} ===");

        // sinkron speaker dari full paper ACC (submissions / abstrak)
        $this->syncSpeakersFromAcceptedFullpapers($id);

        $this->fpLog("=== SELESAI SYNC SPEAKER EVENT {$id} ===");

        // Poster aktif
        $poster = $this->posterModel
            ->where('event_id', $id)
            ->where('is_active', true)
            ->orderBy('id', 'DESC')
            ->first();

        // Semua speaker
        $speakers = $this->speakerModel
            ->where('event_id', $id)
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        // Semua sponsor/partner
        $sponsors = $this->sponsorModel
            ->where('event_id', $id)
            ->orderBy('type', 'ASC')
            ->orderBy('sort_order', 'ASC')
            ->orderBy('id', 'ASC')
            ->findAll();

        $fpDebug = $this->fpDebugLog; // kirim ke view

        return view('role/admin/landing/detail', [
            'title'    => 'Detail Pengaturan Landing',
            'event'    => $event,
            'poster'   => $poster,
            'speakers' => $speakers,
            'sponsors' => $sponsors,
            'fp_debug' => $fpDebug,
        ]);
    }

    /**
     * Ambil user dari tabel submissions/abstrak yang full_paper_status-nya accepted,
     * lalu pastikan mereka ada di event_speakers (is_active = false default).
     */
    protected function syncSpeakersFromAcceptedFullpapers(int $eventId): void
    {
        try {
            // 🔍 Ambil semua user id yang FP-nya accepted (fleksibel: submissions/abstrak)
            $acceptedUsers = $this->getAcceptedFullpaperUserIds($eventId);

            if (empty($acceptedUsers)) {
                $this->fpLog("syncSpeakers: Tidak ada user accepted untuk event {$eventId}.");
                return;
            }

            $this->fpLog("syncSpeakers: proses " . count($acceptedUsers) . " user accepted untuk event {$eventId}.");

            foreach ($acceptedUsers as $uid) {
                // Ambil data registrasi user di event
                $reg = $this->regModel->findUserReg($eventId, $uid);
                if (!$reg) {
                    $this->fpLog("User {$uid}: registrasi TIDAK ditemukan di event {$eventId}.");
                    continue;
                }

                $name = trim((string)($reg['nama_lengkap'] ?? $reg['nama'] ?? $reg['full_name'] ?? ''));
                if ($name === '') {
                    $this->fpLog("User {$uid}: nama kosong (registrasi ada, tapi field nama kosong).");
                    continue;
                }

                $aff = trim((string)($reg['afiliasi'] ?? $reg['institusi'] ?? ''));

                $this->fpLog("User {$uid}: reg ditemukan → nama='{$name}', aff='{$aff}'.");

                // Cek apakah sudah ada speaker dengan nama & affiliasi ini
                $exists = $this->speakerModel
                    ->where('event_id', $eventId)
                    ->where('name', $name)
                    ->where('affiliation', $aff)
                    ->countAllResults();

                if ($exists > 0) {
                    $this->fpLog("Speaker '{$name}' ({$aff}) sudah ada untuk event {$eventId} → SKIP insert.");
                    continue;
                }

                // Insert sebagai speaker non-aktif
                $this->speakerModel->insert([
                    'event_id'    => $eventId,
                    'name'        => $name,
                    'role'        => 'Presenter',
                    'affiliation' => $aff,
                    'bio'         => null,
                    'photo_path'  => null,
                    'sort_order'  => 0,
                    'is_active'   => false,
                    'created_at'  => date('Y-m-d H:i:s'),
                    'updated_at'  => date('Y-m-d H:i:s'),
                ]);

                $this->fpLog("INSERT speaker '{$name}' ({$aff}) ke event {$eventId} (is_active = false).");
            }
        } catch (\Throwable $e) {
            $this->fpLog("❌ ERROR syncSpeakersFromAcceptedFullpapers: " . $e->getMessage());
            log_message('error', 'syncSpeakersFromAcceptedFullpapers error: ' . $e->getMessage());
        }
    }

    protected function isFpAccepted(?string $status): bool
    {
        if ($status === null) {
            return false;
        }

        $s = strtolower(trim((string)$status));
        if ($s === '') {
            return false;
        }

        // Patokan utama dari modul FullPaper Admin
        if ($s === 'accepted') {
            return true;
        }

        // Support berbagai variasi: ACC FP, ACC FINAL, LOLOS, DITERIMA, APPROVED, dll
        $keywords = [
            'acc',
            'accepted',
            'approve',
            'approved',
            'diterima',
            'lolos',
            'final',
            'fp',
        ];

        foreach ($keywords as $key) {
            if (strpos($s, $key) !== false) {
                return true;
            }
        }

        return false;
    }

    /* =========================================================
     * POSTER (tabel event_posters)
     * ======================================================= */

    public function savePoster($eventId = null)
    {
        $eventId = (int)$eventId;
        if ($eventId <= 0) {
            return redirect()->back()
                ->with('error', 'ID event tidak valid.');
        }

        $event = $this->eventModel->find($eventId);
        if (!$event) {
            return redirect()->to(site_url('admin/landing'))
                ->with('error', 'Event tidak ditemukan.');
        }

        $file = $this->request->getFile('poster');
        if (!$file || !$file->isValid()) {
            return redirect()->to(site_url('admin/landing/detail/' . $eventId))
                ->with('error', 'File poster tidak valid.');
        }

        if (!in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'])) {
            return redirect()->to(site_url('admin/landing/detail/' . $eventId))
                ->with('error', 'Format file poster harus JPG/PNG/WEBP.');
        }

        if ($file->getSize() > 2 * 1024 * 1024) {
            return redirect()->to(site_url('admin/landing/detail/' . $eventId))
                ->with('error', 'Ukuran file poster maksimal 2MB.');
        }

        $targetDir = FCPATH . 'uploads/poster';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $newName = 'poster_event_' . $eventId . '_' . time() . '.' . $file->getExtension();
        try {
            $file->move($targetDir, $newName);
        } catch (\Throwable $e) {
            log_message('error', 'Gagal upload poster: ' . $e->getMessage());
            return redirect()->to(site_url('admin/landing/detail/' . $eventId))
                ->with('error', 'Gagal mengunggah file poster.');
        }

        $relativePath = 'uploads/poster/' . $newName;

        $this->posterModel
            ->where('event_id', $eventId)
            ->set(['is_active' => false])
            ->update();

        $this->posterModel->insert([
            'event_id'   => $eventId,
            'file_path'  => $relativePath,
            'is_active'  => true,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return redirect()->to(site_url('admin/landing/detail/' . $eventId))
            ->with('success', 'Poster event berhasil disimpan.');
    }

    /* =========================================================
     * SPEAKER (Tambah / Edit / Hapus)
     * ======================================================= */

    public function saveSpeaker($eventId = null)
    {
        $eventId = (int)$eventId;
        if ($eventId <= 0) {
            return redirect()->back()
                ->with('error', 'ID event tidak valid.');
        }

        $event = $this->eventModel->find($eventId);
        if (!$event) {
            return redirect()->to(site_url('admin/landing'))
                ->with('error', 'Event tidak ditemukan.');
        }

        $mode      = $this->request->getPost('mode') ?: 'create';
        $speakerId = (int)$this->request->getPost('speaker_id');

        $name        = trim((string)$this->request->getPost('name'));
        $role        = trim((string)$this->request->getPost('expertise'));
        $affiliation = trim((string)$this->request->getPost('affiliation'));
        $bio         = trim((string)$this->request->getPost('bio'));
        $sortOrder   = $this->request->getPost('sort_order');
        $isActive    = $this->request->getPost('is_active') ? true : false;

        if ($name === '') {
            return redirect()->to(site_url('admin/landing/detail/' . $eventId))
                ->with('error', 'Nama pembicara wajib diisi.');
        }

        if ($sortOrder === null || $sortOrder === '') {
            $maxRow = $this->speakerModel
                ->selectMax('sort_order')
                ->where('event_id', $eventId)
                ->first();
            $max       = (int)($maxRow['sort_order'] ?? 0);
            $sortOrder = $max + 10;
        } else {
            $sortOrder = (int)$sortOrder;
        }

        $data = [
            'event_id'    => $eventId,
            'name'        => $name,
            'role'        => $role,
            'affiliation' => $affiliation,
            'bio'         => $bio,
            'sort_order'  => $sortOrder,
            'is_active'   => $isActive,
            'updated_at'  => date('Y-m-d H:i:s'),
        ];

        $photoFile = $this->request->getFile('photo');
        if ($photoFile && $photoFile->isValid() && $photoFile->getError() === UPLOAD_ERR_OK) {
            if (!in_array($photoFile->getMimeType(), ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'])) {
                return redirect()->to(site_url('admin/landing/detail/' . $eventId))
                    ->with('error', 'Format foto harus JPG/PNG/WEBP.');
            }

            if ($photoFile->getSize() > 2 * 1024 * 1024) {
                return redirect()->to(site_url('admin/landing/detail/' . $eventId))
                    ->with('error', 'Ukuran foto maksimal 2MB.');
            }

            $targetDir = FCPATH . 'uploads/speakers';
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            $newName = 'speaker_' . $eventId . '_' . time() . '_' . random_string('alnum', 6) . '.' . $photoFile->getExtension();
            try {
                $photoFile->move($targetDir, $newName);
                $data['photo_path'] = 'uploads/speakers/' . $newName;
            } catch (\Throwable $e) {
                log_message('error', 'Gagal upload foto speaker: ' . $e->getMessage());
                return redirect()->to(site_url('admin/landing/detail/' . $eventId))
                    ->with('error', 'Gagal mengunggah foto pembicara.');
            }

            if ($mode === 'edit' && $speakerId > 0) {
                $old = $this->speakerModel->find($speakerId);
                if ($old && !empty($old['photo_path'])) {
                    $oldPath = FCPATH . $old['photo_path'];
                    if (is_file($oldPath)) {
                        @unlink($oldPath);
                    }
                }
            }
        }

        try {
            if ($mode === 'edit' && $speakerId > 0) {
                unset($data['event_id']);
                $this->speakerModel->update($speakerId, $data);
                $msg = 'Data pembicara berhasil diperbarui.';
            } else {
                $data['created_at'] = date('Y-m-d H:i:s');
                $this->speakerModel->insert($data);
                $msg = 'Pembicara berhasil ditambahkan.';
            }

            return redirect()->to(site_url('admin/landing/detail/' . $eventId))
                ->with('success', $msg);
        } catch (\Throwable $e) {
            log_message('error', 'Gagal menyimpan speaker: ' . $e->getMessage());

            return redirect()->to(site_url('admin/landing/detail/' . $eventId))
                ->with('error', 'Terjadi kesalahan saat menyimpan pembicara.');
        }
    }

    public function deleteSpeaker($eventId = null, $speakerId = null)
    {
        $eventId   = (int)$eventId;
        $speakerId = (int)$speakerId;

        if ($eventId <= 0 || $speakerId <= 0) {
            return redirect()->back()->with('error', 'Data tidak valid.');
        }

        try {
            $speaker = $this->speakerModel->find($speakerId);
            if ($speaker && !empty($speaker['photo_path'])) {
                $path = FCPATH . $speaker['photo_path'];
                if (is_file($path)) {
                    @unlink($path);
                }
            }

            $this->speakerModel->delete($speakerId);

            return redirect()->to(site_url('admin/landing/detail/' . $eventId))
                ->with('success', 'Pembicara berhasil dihapus.');
        } catch (\Throwable $e) {
            log_message('error', 'Gagal hapus speaker: ' . $e->getMessage());
            return redirect()->to(site_url('admin/landing/detail/' . $eventId))
                ->with('error', 'Terjadi kesalahan saat menghapus pembicara.');
        }
    }

    /* =========================================================
     * SPONSOR / PARTNER (Tambah / Edit / Hapus)
     * ======================================================= */

    public function saveSponsor($eventId = null)
    {
        $eventId = (int)$eventId;
        if ($eventId <= 0) {
            return redirect()->back()
                ->with('error', 'ID event tidak valid.');
        }

        $event = $this->eventModel->find($eventId);
        if (!$event) {
            return redirect()->to(site_url('admin/landing'))
                ->with('error', 'Event tidak ditemukan.');
        }

        $mode      = $this->request->getPost('mode') ?: 'create';
        $sponsorId = (int)$this->request->getPost('sponsor_id');

        $name     = trim((string)$this->request->getPost('name'));
        $type     = strtolower(trim((string)$this->request->getPost('type')));
        $isActive = $this->request->getPost('is_active') ? true : false;

        if ($name === '' || $type === '') {
            return redirect()->to(site_url('admin/landing/detail/' . $eventId))
                ->with('error', 'Nama dan jenis (Sponsor/Partner) wajib diisi.');
        }

        if (!in_array($type, ['sponsor', 'partner'], true)) {
            return redirect()->to(site_url('admin/landing/detail/' . $eventId))
                ->with('error', 'Jenis harus sponsor atau partner.');
        }

        $maxRow = $this->sponsorModel
            ->selectMax('sort_order')
            ->where('event_id', $eventId)
            ->first();
        $max       = (int)($maxRow['sort_order'] ?? 0);
        $sortOrder = $max + 10;

        $data = [
            'event_id'   => $eventId,
            'name'       => $name,
            'type'       => $type,
            'is_active'  => $isActive,
            'sort_order' => $sortOrder,
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $logoFile = $this->request->getFile('logo');
        if ($logoFile && $logoFile->isValid() && $logoFile->getError() === UPLOAD_ERR_OK) {
            if (!in_array($logoFile->getMimeType(), ['image/jpeg', 'image/png', 'image/jpg', 'image/webp'])) {
                return redirect()->to(site_url('admin/landing/detail/' . $eventId))
                    ->with('error', 'Format logo harus JPG/PNG/WEBP.');
            }

            if ($logoFile->getSize() > 1024 * 1024) {
                return redirect()->to(site_url('admin/landing/detail/' . $eventId))
                    ->with('error', 'Ukuran file logo maksimal 1MB.');
            }

            $targetDir = FCPATH . 'uploads/sponsor';
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0755, true);
            }

            $newName = 'sponsor_' . $eventId . '_' . time() . '_' . random_string('alnum', 6) . '.' . $logoFile->getExtension();
            try {
                $logoFile->move($targetDir, $newName);
                $data['logo_path'] = 'uploads/sponsor/' . $newName;
            } catch (\Throwable $e) {
                log_message('error', 'Gagal upload logo sponsor: ' . $e->getMessage());
                return redirect()->to(site_url('admin/landing/detail/' . $eventId))
                    ->with('error', 'Gagal mengunggah logo sponsor.');
            }

            if ($mode === 'edit' && $sponsorId > 0) {
                $old = $this->sponsorModel->find($sponsorId);
                if ($old && !empty($old['logo_path'])) {
                    $oldPath = FCPATH . $old['logo_path'];
                    if (is_file($oldPath)) {
                        @unlink($oldPath);
                    }
                }
            }
        }

        try {
            if ($mode === 'edit' && $sponsorId > 0) {
                unset($data['event_id']);
                unset($data['sort_order']);
                $this->sponsorModel->update($sponsorId, $data);
                $msg = 'Data sponsor/partner berhasil diperbarui.';
            } else {
                $data['created_at'] = date('Y-m-d H:i:s');
                $this->sponsorModel->insert($data);
                $msg = 'Sponsor / partner berhasil ditambahkan.';
            }

            return redirect()->to(site_url('admin/landing/detail/' . $eventId))
                ->with('success', $msg);
        } catch (\Throwable $e) {
            log_message('error', 'Gagal menyimpan sponsor: ' . $e->getMessage());

            return redirect()->to(site_url('admin/landing/detail/' . $eventId))
                ->with('error', 'Terjadi kesalahan saat menyimpan sponsor.');
        }
    }

    public function deleteSponsor($eventId = null, $sponsorId = null)
    {
        $eventId   = (int)$eventId;
        $sponsorId = (int)$sponsorId;

        if ($eventId <= 0 || $sponsorId <= 0) {
            return redirect()->back()->with('error', 'Data tidak valid.');
        }

        try {
            $sponsor = $this->sponsorModel->find($sponsorId);
            if ($sponsor && !empty($sponsor['logo_path'])) {
                $path = FCPATH . $sponsor['logo_path'];
                if (is_file($path)) {
                    @unlink($path);
                }
            }

            $this->sponsorModel->delete($sponsorId);

            return redirect()->to(site_url('admin/landing/detail/' . $eventId))
                ->with('success', 'Sponsor / partner berhasil dihapus.');
        } catch (\Throwable $e) {
            log_message('error', 'Gagal hapus sponsor: ' . $e->getMessage());
            return redirect()->to(site_url('admin/landing/detail/' . $eventId))
                ->with('error', 'Terjadi kesalahan saat menghapus sponsor.');
        }
    }
}
