<?php

namespace App\Controllers\Role\Admin;

use App\Controllers\BaseController;
use App\Models\DokumenModel;
use App\Models\EventModel;
use App\Models\UserModel;
use App\Models\PembayaranModel;
use App\Models\AbsensiModel;
use Mpdf\Mpdf;

class Dokumen extends BaseController
{
    protected DokumenModel $dokumenModel;
    protected EventModel $eventModel;
    protected UserModel $userModel;
    protected PembayaranModel $pembayaranModel;
    protected AbsensiModel $absensiModel;
    protected \CodeIgniter\Database\BaseConnection $db;

    public function __construct()
    {
        $this->dokumenModel     = new DokumenModel();
        $this->eventModel       = new EventModel();
        $this->userModel        = new UserModel();
        $this->pembayaranModel  = new PembayaranModel(); // tetap disimpan untuk kompatibilitas
        $this->absensiModel     = new AbsensiModel();
        $this->db               = \Config\Database::connect();
    }
    
    public function index()
    {
        $eventId = (int) $this->request->getGet('event_id');
        $tipe    = $this->request->getGet('tipe');

        $documents = $this->dokumenModel->getDokumenWithUser($tipe, $eventId ?: null);
        $events    = $this->eventModel->where('is_active', true)->orderBy('event_date', 'DESC')->findAll();
        $stats     = $this->getDocumentStatistics();

        $completion = null;
        if ($eventId) {
            $completion = $this->getCompletionPerEvent($eventId);
            if ($completion['all_loa_completed']) {
                session()->setFlashdata('success', 'Semua LOA untuk event ini sudah diberikan.');
            }
            if ($completion['all_cert_completed']) {
                session()->setFlashdata('success', 'Semua sertifikat untuk event ini sudah diberikan.');
            }
        }

        return view('role/admin/dokumen/index', [
            'title'         => 'Manajemen Dokumen',
            'documents'     => $documents,
            'events'        => $events,
            'current_event' => $eventId ?: '',
            'current_tipe'  => $tipe,
            'stats'         => $stats,
            'completion'    => $completion,
        ]);
    }

    // ================== AJAX (Dropdown / List) ==================

    /**
     * Presenter terdaftar pada event + Full Paper ACCEPTED
     * TIDAK lagi bergantung pada tabel pembayaran.
     */
    public function getUsersForLoa(int $eventId = 0)
    {
        if ($eventId <= 0) {
            return $this->response->setJSON(['status'=>'error','message'=>'event_id tidak valid'])->setStatusCode(400);
        }

        try {
            // Ambil semua user yang TERDAFTAR pada event ini
            // (kolom di event_registrations fleksibel: user_id/id_user, event_id/id_event, role)
            $er = $this->resolveEventRegColumns();
            if (!$er['table'] || !$er['uid'] || !$er['eid']) {
                return $this->response->setJSON(['status'=>'error','message'=>'Tabel registrasi tidak ditemukan/inkompatibel'])->setStatusCode(500);
            }

            $rows = $this->db->table($er['table'].' er')
                ->distinct()
                ->select("
                    u.id_user,
                    u.nama_lengkap,
                    u.email,
                    LOWER(u.role) AS role_user
                ", false)
                ->join('users u', 'u.id_user = er.'.$er['uid'], 'left')
                ->where('er.'.$er['eid'], $eventId)
                ->orderBy('u.nama_lengkap', 'ASC')
                ->get()->getResultArray();

            // Peta status FP dari berbagai kemungkinan sumber
            $fpMap = $this->getFpStatusMap($eventId);

            // Sudah punya LOA?
            $loaRows = $this->db->table('dokumen')
                ->select('id_user')
                ->where(['event_id' => $eventId, 'tipe' => 'loa'])
                ->get()->getResultArray();
            $hasLoaMap = [];
            foreach ($loaRows as $r) $hasLoaMap[(int)$r['id_user']] = true;

            $acceptedAliases = ['accepted','accept','acc','diterima','approved'];

            $data = array_map(function($r) use ($fpMap, $hasLoaMap, $acceptedAliases) {
                $uid   = (int) ($r['id_user'] ?? 0);
                $role  = strtolower((string) ($r['role_user'] ?? ''));
                $st    = strtolower((string) ($fpMap[$uid] ?? ''));
                if ($st !== '' && in_array($st, $acceptedAliases, true)) $st = 'accepted';

                $isPresenter = (strpos($role, 'presenter') === 0);
                $eligible    = ($isPresenter && $st === 'accepted');

                return [
                    'id_user'      => $uid,
                    'nama_lengkap' => (string) ($r['nama_lengkap'] ?? ''),
                    'email'        => (string) ($r['email'] ?? ''),
                    'role'         => $role ?: 'audience',
                    'fp_status'    => $st,
                    'has_loa'      => (bool) ($hasLoaMap[$uid] ?? false),
                    'eligible'     => (bool) $eligible,
                    'note'         => $eligible ? '' : 'Syarat LOA: Presenter terdaftar & Full Paper ACCEPTED',
                ];
            }, $rows);

            return $this->response->setJSON(['status' => 'success', 'data' => $data]);
        } catch (\Throwable $e) {
            log_message('error', 'getUsersForLoa error: ' . $e->getMessage());
            return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal memuat user.'])->setStatusCode(500);
        }
    }

    /**
     * Peserta terdaftar pada event dengan status hadir.
     * (Masih memakai absensi; tidak bergantung pembayaran.)
     */
    public function getUsersForCertificate(int $eventId = 0)
    {
        if ($eventId <= 0) {
            return $this->response->setJSON(['status'=>'error','message'=>'event_id tidak valid'])->setStatusCode(400);
        }

        try {
            $er = $this->resolveEventRegColumns();
            if (!$er['table'] || !$er['uid'] || !$er['eid']) {
                return $this->response->setJSON(['status'=>'error','message'=>'Tabel registrasi tidak ditemukan/inkompatibel'])->setStatusCode(500);
            }

            // Ambil semua user yang TERDAFTAR pada event ini, lalu LEFT JOIN ke absensi & dokumen sertifikat
            $rows = $this->db->table($er['table'].' er')
                ->distinct()
                ->select("
                    u.id_user,
                    u.nama_lengkap,
                    u.email,
                    LOWER(u.role) AS role_user,
                    CASE WHEN a.id_user IS NULL THEN 0 ELSE 1 END AS attended,
                    COALESCE(a.status, '') AS attend_status,
                    CASE WHEN d.id_dokumen IS NULL THEN 0 ELSE 1 END AS has_cert
                ", false)
                ->join('users u', 'u.id_user = er.'.$er['uid'], 'left')
                ->join('absensi a', "a.id_user = u.id_user AND a.event_id = er.{$er['eid']} AND a.status = 'hadir'", 'left')
                ->join('dokumen d', "d.id_user = u.id_user AND d.event_id = er.{$er['eid']} AND d.tipe = 'sertifikat'", 'left')
                ->where('er.'.$er['eid'], $eventId)
                ->orderBy('u.nama_lengkap', 'ASC')
                ->get()->getResultArray();

            $data = array_map(static function($r){
                $attended = (bool) ($r['attended'] ?? false);

                return [
                    'id_user'       => (int) $r['id_user'],
                    'nama_lengkap'  => (string) ($r['nama_lengkap'] ?? ''),
                    'email'         => (string) ($r['email'] ?? ''),
                    'role'          => (string) (strtolower($r['role_user'] ?? '')),
                    'attended'      => $attended,
                    'attend_status' => (string) ($r['attend_status'] ?? ''),
                    'has_cert'      => (bool)   ($r['has_cert'] ?? false),
                    'eligible'      => $attended,
                    'note'          => $attended ? '' : 'Belum absen',
                ];
            }, $rows);

            return $this->response->setJSON(['status' => 'success', 'data' => $data]);
        } catch (\Throwable $e) {
            log_message('error', 'getUsersForCertificate error: ' . $e->getMessage());
            return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal memuat peserta.'])->setStatusCode(500);
        }
    }

    /** Legacy: semua hadir */
    public function getAttendees(int $eventId = 0)
    {
        if ($eventId <= 0) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'event_id tidak valid'])->setStatusCode(400);
        }

        $rows = $this->db->table('absensi a')
            ->distinct()
            ->select('u.id_user, u.nama_lengkap, u.email, u.role')
            ->join('users u', 'u.id_user = a.id_user', 'left')
            ->where('a.event_id', $eventId)
            ->where('a.status', 'hadir')
            ->orderBy('u.nama_lengkap', 'ASC')
            ->get()->getResultArray();

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => array_map(static function ($r) {
                return [
                    'id_user'      => (int) $r['id_user'],
                    'nama_lengkap' => (string) ($r['nama_lengkap'] ?? ''),
                    'email'        => (string) ($r['email'] ?? ''),
                    'role'         => (string) ($r['role'] ?? ''),
                ];
            }, $rows),
        ]);
    }

    // ================== UPLOAD ==================

    public function uploadLoa()
    {
        $rules = [
            'event_id' => 'required|integer',
            'user_id'  => 'required|integer',
            'loa_file' => ['uploaded[loa_file]', 'max_size[loa_file,5120]', 'ext_in[loa_file,pdf,doc,docx]'],
        ];
        if (!$this->validate($rules)) {
            return redirect()->to(site_url('admin/dokumen'))
                ->with('error', 'Validasi gagal: ' . implode(', ', $this->validator->getErrors()));
        }

        $eventId = (int) $this->request->getPost('event_id');
        $userId  = (int) $this->request->getPost('user_id');
        $file    = $this->request->getFile('loa_file');

        $event = $this->eventModel->find($eventId);
        if (!$event) return redirect()->to(site_url('admin/dokumen'))->with('error', 'Event tidak ditemukan.');
        $user  = $this->userModel->find($userId);
        if (!$user)  return redirect()->to(site_url('admin/dokumen'))->with('error', 'User tidak ditemukan.');

        // Validasi eligibility: Presenter TERDAFTAR & FP ACCEPTED
        if (!$this->isUserRegisteredAsPresenter($eventId, $userId)) {
            return redirect()->to(site_url('admin/dokumen'))
                ->with('error', 'LOA hanya untuk Presenter yang terdaftar pada event.');
        }

        $fpMap = $this->getFpStatusMap($eventId);
        $st    = strtolower((string)($fpMap[$userId] ?? ''));
        $acceptedAliases = ['accepted','accept','acc','diterima','approved'];
        $fpAccepted = $st !== '' && in_array($st, $acceptedAliases, true);

        if (!$fpAccepted) {
            return redirect()->to(site_url('admin/dokumen'))
                ->with('error', 'LOA hanya untuk Presenter dengan Full Paper diterima (ACCEPTED).');
        }

        if ($this->dokumenModel->hasUserDocument($userId, $eventId, 'loa')) {
            return redirect()->to(site_url('admin/dokumen'))->with('error', 'LOA untuk user ini sudah ada.');
        }

        $this->db->transStart();
        try {
            if (!$file || !$file->isValid()) {
                throw new \RuntimeException('File LOA tidak valid: [' . $file?->getError() . '] ' . $file?->getErrorString());
            }

            $uploadPath = WRITEPATH . 'uploads/loa/';
            if (!is_dir($uploadPath)) mkdir($uploadPath, 0775, true);

            $ext      = strtolower($file->getClientExtension());
            $fileName = 'LOA_' . $eventId . '_' . $userId . '_' . time() . '.' . $ext;

            if (!$file->move($uploadPath, $fileName)) {
                throw new \RuntimeException('Gagal menyimpan file: ' . $file->getErrorString());
            }

            $this->dokumenModel->insert([
                'id_user'     => $userId,
                'event_id'    => $eventId,
                'tipe'        => 'loa',
                'file_path'   => $fileName,
                'syarat'      => 'Letter of Acceptance',
                'uploaded_at' => date('Y-m-d H:i:s'),
            ]);

            $this->logActivity(session('id_user'), "Uploaded LOA for {$user['nama_lengkap']} (Event: " . ($event['title'] ?? 'Unknown') . ")");
            $this->db->transComplete();
            if (!$this->db->transStatus()) throw new \RuntimeException('Transaction failed');

            return redirect()->to(site_url('admin/dokumen'))->with('success', 'LOA berhasil diupload!');
        } catch (\Throwable $e) {
            $this->db->transRollback();
            if (isset($fileName) && is_file(($uploadPath ?? '') . $fileName)) @unlink(($uploadPath ?? '') . $fileName);
            log_message('error', 'LOA upload error: ' . $e->getMessage());
            return redirect()->to(site_url('admin/dokumen'))->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function uploadSertifikat()
    {
        $rules = [
            'event_id'        => 'required|integer',
            'user_id'         => 'required|integer',
            'sertifikat_file' => ['uploaded[sertifikat_file]', 'max_size[sertifikat_file,5120]', 'ext_in[sertifikat_file,pdf,jpg,jpeg,png]'],
        ];
        if (!$this->validate($rules)) {
            return redirect()->to(site_url('admin/dokumen'))
                ->with('error', 'Validasi gagal: ' . implode(', ', $this->validator->getErrors()));
        }

        $eventId = (int) $this->request->getPost('event_id');
        $userId  = (int) $this->request->getPost('user_id');
        $file    = $this->request->getFile('sertifikat_file');

        $event = $this->eventModel->find($eventId);
        if (!$event) return redirect()->to(site_url('admin/dokumen'))->with('error', 'Event tidak ditemukan.');
        $user  = $this->userModel->find($userId);
        if (!$user)  return redirect()->to(site_url('admin/dokumen'))->with('error', 'User tidak ditemukan.');

        // Harus hadir
        $attendance = $this->absensiModel->where([
            'id_user'  => $userId,
            'event_id' => $eventId,
            'status'   => 'hadir',
        ])->first();
        if (!$attendance) {
            return redirect()->to(site_url('admin/dokumen'))->with('error', 'User belum tercatat hadir pada event ini.');
        }

        if ($this->dokumenModel->hasUserDocument($userId, $eventId, 'sertifikat')) {
            return redirect()->to(site_url('admin/dokumen'))->with('error', 'Sertifikat untuk user ini sudah ada.');
        }

        $this->db->transStart();
        try {
            if (!$file || !$file->isValid()) {
                throw new \RuntimeException('File sertifikat tidak valid: [' . $file?->getError() . '] ' . $file?->getErrorString());
            }

            $uploadPath = WRITEPATH . 'uploads/sertifikat/';
            if (!is_dir($uploadPath)) mkdir($uploadPath, 0775, true);

            $ext      = strtolower($file->getClientExtension());
            $fileName = 'SERTIFIKAT_' . $eventId . '_' . $userId . '_' . time() . '.' . $ext;

            if (!$file->move($uploadPath, $fileName)) {
                throw new \RuntimeException('Gagal menyimpan file: ' . $file->getErrorString());
            }

            $this->dokumenModel->insert([
                'id_user'     => $userId,
                'event_id'    => $eventId,
                'tipe'        => 'sertifikat',
                'file_path'   => $fileName,
                'syarat'      => 'Certificate of Participation',
                'uploaded_at' => date('Y-m-d H:i:s'),
            ]);

            $this->logActivity(session('id_user'), "Uploaded Certificate for {$user['nama_lengkap']} (Event: " . ($event['title'] ?? 'Unknown') . ")");
            $this->db->transComplete();
            if (!$this->db->transStatus()) throw new \RuntimeException('Transaction failed');

            return redirect()->to(site_url('admin/dokumen'))->with('success', 'Sertifikat berhasil diupload!');
        } catch (\Throwable $e) {
            $this->db->transRollback();
            if (isset($fileName) && is_file(($uploadPath ?? '') . $fileName)) @unlink(($uploadPath ?? '') . $fileName);
            log_message('error', 'Certificate upload error: ' . $e->getMessage());
            return redirect()->to(site_url('admin/dokumen'))->with('error', 'Error: ' . $e->getMessage());
        }
    }

    // ================== DOWNLOAD & DELETE ==================

    public function download($idDokumen)
    {
        $document = $this->dokumenModel->getOneWithDetails($idDokumen);
        if (!$document) throw new \CodeIgniter\Exceptions\PageNotFoundException('Dokumen tidak ditemukan.');

        $basePath = WRITEPATH . 'uploads/' . $document['tipe'] . '/';
        $filePath = $basePath . $document['file_path'];
        if (!is_file($filePath)) throw new \CodeIgniter\Exceptions\PageNotFoundException('File tidak ditemukan: ' . $filePath);

        $eventTitle   = $document['event_title'] ? preg_replace('/[^A-Za-z0-9_-]/', '_', $document['event_title']) : 'Event';
        $userName     = preg_replace('/[^A-Za-z0-9_-]/', '_', $document['nama_lengkap'] ?? 'Unknown');
        $extension    = pathinfo($document['file_path'], PATHINFO_EXTENSION);
        $downloadName = strtoupper($document['tipe']) . '_' . mb_substr($eventTitle, 0, 60) . '_' . mb_substr($userName, 0, 60) . '.' . $extension;

        $this->logActivity(session('id_user'), "Downloaded {$document['tipe']} for " . ($document['nama_lengkap'] ?? 'Unknown') . " (Event: " . ($document['event_title'] ?? 'Unknown') . ")");
        return $this->response->download($filePath, null)->setFileName($downloadName);
    }

    public function delete($idDokumen)
    {
        $document = $this->dokumenModel->find($idDokumen);
        if (!$document) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'status'     => 'error',
                    'message'    => 'Dokumen tidak ditemukan.',
                    'csrf_hash'  => csrf_hash(),
                    'csrf'       => csrf_hash(),
                ])->setStatusCode(404);
            }
            return redirect()->to(site_url('admin/dokumen'))->with('error', 'Dokumen tidak ditemukan.');
        }

        $this->db->transStart();
        try {
            $user  = $this->userModel->find($document['id_user']);
            $event = $this->eventModel->find($document['event_id']);

            $filePath = WRITEPATH . 'uploads/' . $document['tipe'] . '/' . $document['file_path'];
            if (is_file($filePath)) @unlink($filePath);

            $this->dokumenModel->delete($idDokumen);

            $this->logActivity(session('id_user'), "Deleted {$document['tipe']} for " . ($user['nama_lengkap'] ?? 'Unknown') . " (Event: " . ($event['title'] ?? 'Unknown') . ")");
            $this->db->transComplete();
            if (!$this->db->transStatus()) throw new \RuntimeException('Transaction failed');

            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'status'     => 'success',
                    'message'    => 'Dokumen berhasil dihapus!',
                    'csrf_hash'  => csrf_hash(),
                    'csrf'       => csrf_hash(),
                ]);
            }

            return redirect()->to(site_url('admin/dokumen'))->with('success', 'Dokumen berhasil dihapus!');
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'Document deletion error: ' . $e->getMessage());

            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'status'     => 'error',
                    'message'    => 'Error: '.$e->getMessage(),
                    'csrf_hash'  => csrf_hash(),
                    'csrf'       => csrf_hash(),
                ])->setStatusCode(500);
            }

            return redirect()->to(site_url('admin/dokumen'))->with('error', 'Error: ' . $e->getMessage());
        }
    }

    // ================== GENERATE (BULK / SINGLE) ==================

    public function generateBulkLOA()
    {
        $eventId = (int)$this->request->getPost('event_id');
        $userId  = (int)$this->request->getPost('user_id'); // optional

        if (!$eventId) return redirect()->to(site_url('admin/dokumen'))->with('error', 'Event ID diperlukan.');
        $event = $this->eventModel->find($eventId);
        if (!$event) return redirect()->to(site_url('admin/dokumen'))->with('error', 'Event tidak ditemukan.');

        $fpMap = $this->getFpStatusMap($eventId);
        $acceptedAliases = ['accepted','accept','acc','diterima','approved'];

        if ($userId > 0) {
            $user = $this->userModel->find($userId);
            if (!$user) return redirect()->to(site_url('admin/dokumen'))->with('error', 'User tidak ditemukan.');

            if (!$this->isUserRegisteredAsPresenter($eventId, $userId)) {
                return redirect()->to(site_url('admin/dokumen'))->with('error', 'User bukan presenter terdaftar pada event ini.');
            }

            $st = strtolower((string) ($fpMap[$userId] ?? ''));
            $fpAccepted = $st !== '' && in_array($st, $acceptedAliases, true);

            if (!$fpAccepted) {
                return redirect()->to(site_url('admin/dokumen'))->with('error', 'User tidak memenuhi syarat LOA (Full Paper belum ACCEPTED).');
            }
            if ($this->dokumenModel->hasUserDocument($userId, $eventId, 'loa')) {
                return redirect()->to(site_url('admin/dokumen'))->with('error', 'LOA untuk user ini sudah ada.');
            }

            $eligible = [[
                'id_user'      => $userId,
                'nama_lengkap' => $user['nama_lengkap'] ?? '',
                'email'        => $user['email'] ?? '',
            ]];
        } else {
            // Semua PRESENTER TERDAFTAR di event ini, lalu filter FP ACCEPTED
            $eligible = $this->getRegisteredPresenters($eventId);

            $eligible = array_values(array_filter($eligible, function($u) use ($fpMap, $acceptedAliases){
                $uid = (int)($u['id_user'] ?? 0);
                $st  = strtolower((string) ($fpMap[$uid] ?? ''));
                if ($st !== '' && in_array($st, $acceptedAliases, true)) $st = 'accepted';
                return ($st === 'accepted');
            }));

            if (empty($eligible)) {
                return redirect()->to(site_url('admin/dokumen'))->with('error', 'Tidak ada Presenter terdaftar dengan Full Paper ACCEPTED.');
            }
        }

        $this->db->transStart();
        try {
            $successCount = 0;
            $uploadPath   = WRITEPATH . 'uploads/loa/';
            if (!is_dir($uploadPath)) mkdir($uploadPath, 0775, true);

            foreach ($eligible as $presenter) {
                $uid = (int)$presenter['id_user'];
                if ($this->dokumenModel->hasUserDocument($uid, $eventId, 'loa')) continue;

                $pdfPath = $this->generateLOAPDF($presenter, $event, $uploadPath);
                if (!$pdfPath) continue;

                $this->dokumenModel->insert([
                    'id_user'     => $uid,
                    'event_id'    => $eventId,
                    'tipe'        => 'loa',
                    'file_path'   => basename($pdfPath),
                    'syarat'      => 'Letter of Acceptance - Generated',
                    'uploaded_at' => date('Y-m-d H:i:s'),
                ]);
                $successCount++;
            }

            $this->logActivity(session('id_user'), 'Generated ' . $successCount . ' LOA (Event: ' . ($event['title'] ?? 'Unknown') . ')');
            $this->db->transComplete();
            if (!$this->db->transStatus()) throw new \RuntimeException('Transaction failed');

            $msg = $userId ? 'LOA untuk user berhasil digenerate.' : ('Berhasil generate ' . $successCount . ' LOA.');
            return redirect()->to(site_url('admin/dokumen'))->with('success', $msg);
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'Bulk LOA generation error: ' . $e->getMessage());
            return redirect()->to(site_url('admin/dokumen'))->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function generateBulkSertifikat()
    {
        $eventId = (int)$this->request->getPost('event_id');
        $userId  = (int)$this->request->getPost('user_id'); // optional

        if (!$eventId) return redirect()->to(site_url('admin/dokumen'))->with('error', 'Event ID diperlukan.');
        $event = $this->eventModel->find($eventId);
        if (!$event) return redirect()->to(site_url('admin/dokumen'))->with('error', 'Event tidak ditemukan.');

        if ($userId > 0) {
            // Satu user, pastikan hadir
            $user = $this->db->table('absensi a')
                ->select('u.id_user, u.nama_lengkap, u.email, u.role')
                ->join('users u', 'u.id_user = a.id_user', 'left')
                ->where('a.event_id', $eventId)
                ->where('a.id_user', $userId)
                ->where('a.status', 'hadir')
                ->get()->getRowArray();

            if (!$user) {
                return redirect()->to(site_url('admin/dokumen'))->with('error', 'User belum tercatat hadir.');
            }
            if ($this->dokumenModel->hasUserDocument($userId, $eventId, 'sertifikat')) {
                return redirect()->to(site_url('admin/dokumen'))->with('error', 'Sertifikat untuk user ini sudah ada.');
            }
            $eligibleUsers = [$user];
        } else {
            // Pakai helper di DokumenModel (berbasis absensi)
            $eligibleUsers = $this->dokumenModel->getEligibleUsersForCertificate($eventId);
            if (empty($eligibleUsers)) {
                return redirect()->to(site_url('admin/dokumen'))->with('error', 'Tidak ada peserta yang memenuhi syarat untuk sertifikat.');
            }
        }

        $this->db->transStart();
        try {
            $successCount = 0;
            $uploadPath   = WRITEPATH . 'uploads/sertifikat/';
            if (!is_dir($uploadPath)) mkdir($uploadPath, 0775, true);

            foreach ($eligibleUsers as $user) {
                $uid = (int)$user['id_user'];
                if ($this->dokumenModel->hasUserDocument($uid, $eventId, 'sertifikat')) continue;

                $pdfPath = $this->generateCertificatePDF($user, $event, $uploadPath);
                if (!$pdfPath) continue;

                $this->dokumenModel->insert([
                    'id_user'     => $uid,
                    'event_id'    => $eventId,
                    'tipe'        => 'sertifikat',
                    'file_path'   => basename($pdfPath),
                    'syarat'      => 'Certificate of Participation - Generated',
                    'uploaded_at' => date('Y-m-d H:i:s'),
                ]);
                $successCount++;
            }

            $this->logActivity(session('id_user'), 'Generated ' . $successCount . ' Sertifikat (Event: ' . ($event['title'] ?? 'Unknown') . ')');
            $this->db->transComplete();
            if (!$this->db->transStatus()) throw new \RuntimeException('Transaction failed');

            $msg = $userId ? 'Sertifikat untuk user berhasil digenerate.' : ('Berhasil generate ' . $successCount . ' sertifikat.');
            return redirect()->to(site_url('admin/dokumen'))->with('success', $msg);
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'Bulk certificate generation error: ' . $e->getMessage());
            return redirect()->to(site_url('admin/dokumen'))->with('error', 'Error: ' . $e->getMessage());
        }
    }

    // ================== UTIL ==================

    private function getDocumentStatistics(): array
    {
        $totalDocuments  = $this->dokumenModel->countAll();
        $loaCount        = $this->dokumenModel->where('tipe', 'loa')->countAllResults(true);
        $sertifikatCount = $this->dokumenModel->where('tipe', 'sertifikat')->countAllResults(true);
        $weekAgo         = date('Y-m-d H:i:s', strtotime('-1 week'));
        $recentUploads   = $this->dokumenModel->where('uploaded_at >=', $weekAgo)->countAllResults(true);

        return [
            'total_documents'  => (int) $totalDocuments,
            'loa_count'        => (int) $loaCount,
            'sertifikat_count' => (int) $sertifikatCount,
            'recent_uploads'   => (int) $recentUploads,
        ];
    }

    /**
     * Progress per event:
     * - LOA eligible: presenter TERDAFTAR + FP ACCEPTED
     * - Sertifikat eligible: absensi hadir
     */
    private function getCompletionPerEvent(int $eventId): array
    {
        $fpMap   = $this->getFpStatusMap($eventId);
        $acceptedAliases = ['accepted','accept','acc','diterima','approved'];

        // Presenter terdaftar pada event
        $presenters = $this->getRegisteredPresenters($eventId);

        $eligibleLoa = 0;
        foreach ($presenters as $p) {
            $uid = (int) $p['id_user'];
            $st  = strtolower((string) ($fpMap[$uid] ?? ''));
            if ($st !== '' && in_array($st, $acceptedAliases, true)) $st = 'accepted';
            if ($st === 'accepted') $eligibleLoa++;
        }

        $givenLoa = $this->dokumenModel->where(['event_id' => $eventId, 'tipe' => 'loa'])->countAllResults(true);

        $eligibleCert = $this->db->table('absensi')->where(['event_id' => $eventId, 'status' => 'hadir'])->countAllResults();
        $givenCert    = $this->dokumenModel->where(['event_id' => $eventId, 'tipe' => 'sertifikat'])->countAllResults(true);

        return [
            'eligible_loa'       => (int) $eligibleLoa,
            'given_loa'          => (int) $givenLoa,
            'eligible_cert'      => (int) $eligibleCert,
            'given_cert'         => (int) $givenCert,
            'all_loa_completed'  => $eligibleLoa > 0 && $eligibleLoa === $givenLoa,
            'all_cert_completed' => $eligibleCert > 0 && $eligibleCert === $givenCert,
        ];
    }

    /**
     * Mapping status FP per user pada event tertentu.
     * Mencari dari beberapa kandidat tabel/kolom.
     */
    private function getFpStatusMap(int $eventId): array
    {
        $db = $this->db;

        $candidates = [
            ['table'=>'submissions','id_cols'=>['user_id','id_user'],'event_cols'=>['event_id','id_event'],'status_cols'=>['full_paper_status','fp_status','status','keputusan']],
            ['table'=>'full_papers','id_cols'=>['user_id','id_user'],'event_cols'=>['event_id','id_event'],'status_cols'=>['full_paper_status','fp_status','status','keputusan']],
            ['table'=>'fullpaper','id_cols'=>['user_id','id_user'],'event_cols'=>['event_id','id_event'],'status_cols'=>['full_paper_status','fp_status','status','keputusan']],
            ['table'=>'papers','id_cols'=>['user_id','id_user'],'event_cols'=>['event_id','id_event'],'status_cols'=>['fp_status','full_paper_status','status','keputusan']],
            ['table'=>'abstrak','id_cols'=>['id_user','user_id'],'event_cols'=>['event_id','id_event'],'status_cols'=>['full_paper_status','fp_status','status']],
            ['table'=>'karya_tulis','id_cols'=>['id_user','user_id'],'event_cols'=>['event_id','id_event'],'status_cols'=>['status','fp_status','full_paper_status']],
        ];

        $acceptedAliases = ['accepted','accept','acc','diterima','approved'];

        foreach ($candidates as $cand) {
            $table = $cand['table'];
            if (!$db->tableExists($table)) continue;

            $fields = array_flip($db->getFieldNames($table) ?: []);
            $idCol = null; foreach ($cand['id_cols'] as $c) if (isset($fields[$c])) { $idCol = $c; break; }
            $eventCol = null; foreach ($cand['event_cols'] as $c) if (isset($fields[$c])) { $eventCol = $c; break; }
            $statCol = null; foreach ($cand['status_cols'] as $c) if (isset($fields[$c])) { $statCol = $c; break; }

            if (!$idCol || !$eventCol || !$statCol) continue;

            $rows = $db->table($table)
                ->select("$idCol AS uid, LOWER($statCol) AS st", false)
                ->where($eventCol, $eventId)
                ->get()->getResultArray();

            if (!$rows) continue;

            $map = [];
            foreach ($rows as $r) {
                $uid = (int)($r['uid'] ?? 0);
                if ($uid <= 0) continue;

                $st = strtolower(trim((string)($r['st'] ?? '')));
                if ($st !== '' && in_array($st, $acceptedAliases, true)) $st = 'accepted';
                $map[$uid] = $st;
            }
            return $map;
        }

        log_message('warning', 'FP detector: tidak menemukan sumber status untuk event '.$eventId);
        return [];
    }

    private function generateLOAPDF(array $presenter, array $event, string $uploadPath)
    {
        try {
            $mpdf = new Mpdf([
                'mode' => 'utf-8', 'format' => 'A4', 'orientation' => 'P',
                'margin_left' => 15, 'margin_right' => 15, 'margin_top' => 20, 'margin_bottom' => 20,
                'default_font' => 'dejavusans',
            ]);
            $mpdf->SetTitle('Letter of Acceptance - ' . ($presenter['nama_lengkap'] ?? 'Presenter'));
            $mpdf->SetAuthor('SNIA Organization');
            $html = $this->getLOAHTML($presenter, $event);
            $mpdf->WriteHTML($html);
            $fileName = 'LOA_' . $event['id'] . '_' . $presenter['id_user'] . '_' . time() . '.pdf';
            $filePath = rtrim($uploadPath, '/\\') . DIRECTORY_SEPARATOR . $fileName;
            $mpdf->Output($filePath, 'F');
            return $filePath;
        } catch (\Throwable $e) {
            log_message('error', 'LOA PDF generation error: ' . $e->getMessage());
            return false;
        }
    }

    private function generateCertificatePDF(array $user, array $event, string $uploadPath)
    {
        try {
            $mpdf = new Mpdf([
                'mode' => 'utf-8', 'format' => 'A4-L', 'orientation' => 'L',
                'margin_left' => 0, 'margin_right' => 0, 'margin_top' => 0, 'margin_bottom' => 0,
                'margin_header' => 0, 'margin_footer' => 0,
                'default_font_size' => 12, 'default_font' => 'dejavusans',
            ]);
            $mpdf->SetTitle('Certificate of Participation - ' . ($user['nama_lengkap'] ?? 'Participant'));
            $mpdf->SetAuthor('SNIA Organization');
            $mpdf->SetSubject('Certificate of Participation');
            $mpdf->SetAutoPageBreak(false);
            $html = $this->getCertificateHTML($user, $event);
            $mpdf->WriteHTML($html);
            $fileName = 'SERTIFIKAT_' . $event['id'] . '_' . $user['id_user'] . '_' . time() . '.pdf';
            $filePath = rtrim($uploadPath, '/\\') . DIRECTORY_SEPARATOR . $fileName;
            $mpdf->Output($filePath, 'F');
            return $filePath;
        } catch (\Throwable $e) {
            log_message('error', 'Certificate PDF generation error: ' . $e->getMessage());
            return false;
        }
    }

    private function getLOAHTML(array $presenter, array $event): string
    {
        $eventDate    = date('d F Y', strtotime($event['event_date'] ?? ''));
        $currentDate  = date('d F Y');
        $eventTitle   = $event['title'] ?? 'Event Title';
        $eventTime    = $event['event_time'] ?? 'TBA';
        $eventFormat  = $event['format'] ?? 'offline';
        $presenterName= $presenter['nama_lengkap'] ?? 'Presenter Name';

        return '
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .header { text-align: center; margin-bottom: 40px; }
            .header h1 { color: #2563eb; font-size: 28px; margin-bottom: 10px; }
            .header h2 { color: #1e40af; font-size: 20px; margin: 0; }
            .content { margin: 20px 0; text-align: justify; }
            .details { background-color: #f8f9fa; padding: 15px; border-left: 4px solid #2563eb; margin: 20px 0; }
            .signature { margin-top: 60px; text-align: right; }
            .date { text-align: left; margin-bottom: 30px; }
        </style>

        <div class="header">
            <h1>LETTER OF ACCEPTANCE</h1>
            <h2>' . htmlspecialchars($eventTitle) . '</h2>
        </div>

        <div class="date"><p><strong>Date:</strong> ' . $currentDate . '</p></div>

        <div class="content">
            <p>Dear <strong>' . htmlspecialchars($presenterName) . '</strong>,</p>
            <p>Your participation as a presenter in <strong>' . htmlspecialchars($eventTitle) . '</strong> has been accepted.</p>
            <div class="details">
                <p><strong>Event:</strong> ' . htmlspecialchars($eventTitle) . '<br/>
                <strong>Date:</strong> ' . $eventDate . '<br/>
                <strong>Time:</strong> ' . htmlspecialchars($eventTime) . '<br/>
                <strong>Format:</strong> ' . ucfirst(htmlspecialchars($eventFormat)) . '</p>
            </div>
            <p>Best regards,</p>
        </div>

        <div class="signature">
            <p><strong>SNIA Organization</strong><br/>Event Committee</p>
        </div>';
    }

    private function getCertificateHTML(array $user, array $event): string
    {
        $eventDate  = date('d F Y', strtotime($event['event_date'] ?? ''));
        $eventTitle = $event['title'] ?? 'Event Title';
        $userName   = $user['nama_lengkap'] ?? 'Participant Name';

        return '
        <style>
            @page { size: A4 landscape; margin: 0; }
            body { font-family: "Times New Roman", serif; margin: 0; padding: 0; width: 297mm; height: 210mm; overflow: hidden; }
            .certificate { width: 100%; height: 100%; border: 12mm solid #2563eb; text-align: center; background: linear-gradient(135deg,#f8fafc 0%,#e2e8f0 100%); position: relative; }
            .title { font-size: 42px; color: #2563eb; margin-top: 35mm; font-weight: bold; letter-spacing: 8px; }
            .subtitle { font-size: 22px; margin-bottom: 25px; color: #1e40af; letter-spacing: 4px; font-weight: 600; }
            .recipient { font-size: 32px; color: #1e40af; margin: 25px 0; font-weight: bold; text-decoration: underline; text-decoration-color: #2563eb; }
            .event-title { font-size: 24px; margin: 20px 0; font-style: italic; color: #374151; }
            .date { font-size: 16px; margin-top: 12px; color: #6b7280; }
            .signature { position: absolute; bottom: 30mm; right: 40mm; text-align: center; }
        </style>
        <div class="certificate">
            <div class="title">CERTIFICATE</div>
            <div class="subtitle">OF PARTICIPATION</div>
            <div>This certifies that</div>
            <div class="recipient">'.htmlspecialchars($userName).'</div>
            <div>has successfully participated in</div>
            <div class="event-title">'.htmlspecialchars($eventTitle).'</div>
            <div class="date">Held on '.$eventDate.'</div>
            <div class="signature"><strong>SNIA Organization</strong><br/>Event Committee</div>
        </div>';
    }

    private function logActivity($userId, $activity): void
    {
        try {
            $this->db->table('log_aktivitas')->insert([
                'id_user'  => $userId,
                'aktivitas'=> $activity,
                'waktu'    => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Failed to log activity: ' . $e->getMessage());
        }
    }

    // ================== Helper kolom/registrasi ==================

    /**
     * Kembalikan info kolom dinamis untuk tabel event_registrations
     * supaya kompatibel dengan variasi skema.
     */
    private function resolveEventRegColumns(): array
    {
        $db = $this->db;
        $table = null;
        foreach (['event_registrations','registrations','pendaftaran_event'] as $t) {
            if ($db->tableExists($t)) { $table = $t; break; }
        }
        if (!$table) return ['table'=>null,'uid'=>null,'eid'=>null,'role'=>null];

        $fields = array_flip($db->getFieldNames($table) ?: []);
        $uid  = null; foreach (['user_id','id_user','uid'] as $c) if (isset($fields[$c])) { $uid = $c; break; }
        $eid  = null; foreach (['event_id','id_event'] as $c) if (isset($fields[$c])) { $eid = $c; break; }
        $role = null; foreach (['role','tipe','jenis'] as $c) if (isset($fields[$c])) { $role = $c; break; }

        return ['table'=>$table,'uid'=>$uid,'eid'=>$eid,'role'=>$role];
    }

    /**
     * Ambil semua presenter TERDAFTAR (nama, email) pada event tertentu.
     */
    private function getRegisteredPresenters(int $eventId): array
    {
        $er = $this->resolveEventRegColumns();
        if (!$er['table'] || !$er['uid'] || !$er['eid']) return [];

        $builder = $this->db->table($er['table'].' er')
            ->select("u.id_user, u.nama_lengkap, u.email, ".($er['role'] ? "LOWER(er.{$er['role']}) AS er_role" : "'' AS er_role"), false)
            ->join('users u', 'u.id_user = er.'.$er['uid'], 'left')
            ->where('er.'.$er['eid'], $eventId);

        // Jika ada kolom role di registrasi, filter presenter di level registrasi;
        // jika tidak ada, fallback pakai users.role
        if ($er['role']) {
            $builder->groupStart()
                ->like('LOWER(er.'.$er['role'].')', 'presenter', 'after')
            ->groupEnd();
        } else {
            $builder->groupStart()
                ->like('LOWER(u.role)', 'presenter', 'after')
            ->groupEnd();
        }

        $rows = $builder->orderBy('u.nama_lengkap','ASC')->get()->getResultArray();
        return array_map(static function($r){
            return [
                'id_user'      => (int) $r['id_user'],
                'nama_lengkap' => (string) ($r['nama_lengkap'] ?? ''),
                'email'        => (string) ($r['email'] ?? ''),
            ];
        }, $rows);
    }

    /**
     * Cek apakah user terdaftar sebagai presenter pada event.
     */
    private function isUserRegisteredAsPresenter(int $eventId, int $userId): bool
    {
        $er = $this->resolveEventRegColumns();
        if (!$er['table'] || !$er['uid'] || !$er['eid']) return false;

        $qb = $this->db->table($er['table'])
            ->where($er['eid'], $eventId)
            ->where($er['uid'], $userId);

        if ($er['role']) {
            $qb->groupStart()
                ->like('LOWER('.$er['role'].')','presenter','after')
            ->groupEnd();
        } else {
            // Fallback cek role di users
            $has = $qb->countAllResults();
            if ($has <= 0) return false;

            $roleUser = $this->db->table('users')->select('LOWER(role) AS r')->where('id_user',$userId)->get()->getRowArray();
            return $roleUser && strpos(($roleUser['r'] ?? ''), 'presenter') === 0;
        }

        return $qb->countAllResults() > 0;
    }
}
