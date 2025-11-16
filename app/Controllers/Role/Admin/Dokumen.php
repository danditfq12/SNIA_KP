<?php

namespace App\Controllers\Role\Admin;

use App\Controllers\BaseController;
use App\Models\DokumenModel;
use App\Models\EventModel;
use App\Models\UserModel;
use App\Models\PembayaranModel;
use App\Models\AbsensiModel;

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
        $this->pembayaranModel  = new PembayaranModel();
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

    /** Presenter yang PEMBAYARANNYA verified pada event. */
    public function getVerifiedPresenters(int $eventId = 0)
    {
        if ($eventId <= 0) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'event_id tidak valid'])->setStatusCode(400);
        }

        $rows = $this->db->table('pembayaran p')
            ->distinct()
            ->select('u.id_user, u.nama_lengkap, u.email, u.role')
            ->join('users u', 'u.id_user = p.id_user', 'left')
            ->where('p.event_id', $eventId)
            ->where('p.status', 'verified')
            ->groupStart()
                ->where("u.role =", 'presenter', false)
                ->orWhere('u.role', 'presenter')
            ->groupEnd()
            ->orderBy('u.nama_lengkap', 'ASC')
            ->get()->getResultArray();

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => array_map(static function ($r) {
                return [
                    'id_user'      => (int) $r['id_user'],
                    'nama_lengkap' => (string) ($r['nama_lengkap'] ?? ''),
                    'email'        => (string) ($r['email'] ?? ''),
                    'role'         => (string) ($r['role'] ?? 'presenter'),
                ];
            }, $rows),
        ]);
    }

    /** Pencarian verified presenter (untuk LOA). */
    public function searchEligibleLoa()
    {
        $eventId = (int) $this->request->getGet('event_id');
        $q       = trim((string) $this->request->getGet('q'));

        if ($eventId <= 0) {
            return $this->response->setJSON(['status' => 'error', 'message' => 'event_id tidak valid'])->setStatusCode(400);
        }

        $builder = $this->db->table('pembayaran p')
            ->distinct()
            ->select('u.id_user, u.nama_lengkap, u.email, u.role')
            ->join('users u', 'u.id_user = p.id_user', 'left')
            ->where('p.event_id', $eventId)
            ->where('p.status', 'verified')
            ->groupStart()
                ->where("u.role =", 'presenter', false)
                ->orWhere('u.role', 'presenter')
            ->groupEnd();

        if ($q !== '' && $q !== '*') {
            $builder->groupStart()
                ->like('u.nama_lengkap', $q, 'both')
                ->orLike('u.email', $q, 'both')
                ->groupEnd();
        }

        $rows = $builder->orderBy('u.nama_lengkap', 'ASC')->get()->getResultArray();

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => array_map(static function ($r) {
                return [
                    'id_user'      => (int) $r['id_user'],
                    'nama_lengkap' => (string) ($r['nama_lengkap'] ?? ''),
                    'email'        => (string) ($r['email'] ?? ''),
                    'role'         => (string) ($r['role'] ?? 'presenter'),
                ];
            }, $rows),
        ]);
    }

    /**
     * Ambil SEMUA user yang punya pembayaran pada event,
     * lengkap dengan status pembayaran, has_loa, dan flag eligible (presenter + verified).
     * Endpoint: GET dokumen/users-for-loa/{eventId}
     */
    public function getUsersForLoa(int $eventId = 0)
    {
        if ($eventId <= 0) {
            return $this->response->setJSON(['status'=>'error','message'=>'event_id tidak valid'])->setStatusCode(400);
        }

        $rows = $this->db->table('pembayaran p')
            ->distinct()
            ->select("
                u.id_user,
                u.nama_lengkap,
                u.email,
                u.role AS role_user,
                p.status AS pay_status,
                CASE WHEN d.id_dokumen IS NULL THEN 0 ELSE 1 END AS has_loa
            ", false)
            ->join('users u', 'u.id_user = p.id_user', 'left')
            ->join('dokumen d', "d.id_user = u.id_user AND d.event_id = p.event_id AND d.tipe = 'loa'", 'left')
            ->where('p.event_id', $eventId)
            ->orderBy('u.nama_lengkap', 'ASC')
            ->get()->getResultArray();

        $data = array_map(static function($r){
            $roleFinal  = strtolower($r['role_user'] ?? '');
            $payStatus  = strtolower($r['pay_status'] ?? '');
            $eligible   = ($roleFinal === 'presenter' && $payStatus === 'verified');

            return [
                'id_user'      => (int) $r['id_user'],
                'nama_lengkap' => (string) ($r['nama_lengkap'] ?? ''),
                'email'        => (string) ($r['email'] ?? ''),
                'role'         => $roleFinal ?: 'audience',
                'pay_status'   => $payStatus ?: '',
                'has_loa'      => (bool)   ($r['has_loa'] ?? false),
                'eligible'     => (bool)   $eligible,
                'note'         => $eligible ? '' : 'LOA khusus presenter & pembayaran harus verified',
            ];
        }, $rows);

        return $this->response->setJSON(['status' => 'success', 'data' => $data]);
    }

    /**
     * Ambil SEMUA user yang punya pembayaran pada event,
     * lengkap dengan attended (absen hadir), has_cert, dan flag eligible (harus hadir).
     * Endpoint: GET dokumen/users-for-certificate/{eventId}
     */
    public function getUsersForCertificate(int $eventId = 0)
    {
        if ($eventId <= 0) {
            return $this->response->setJSON(['status'=>'error','message'=>'event_id tidak valid'])->setStatusCode(400);
        }

        $rows = $this->db->table('pembayaran p')
            ->distinct()
            ->select("
                u.id_user,
                u.nama_lengkap,
                u.email,
                u.role AS role_user,
                CASE WHEN a.id_user IS NULL THEN 0 ELSE 1 END AS attended,
                COALESCE(a.status, '') AS attend_status,
                CASE WHEN d.id_dokumen IS NULL THEN 0 ELSE 1 END AS has_cert
            ", false)
            ->join('users u', 'u.id_user = p.id_user', 'left')
            ->join('absensi a', "a.id_user = u.id_user AND a.event_id = p.event_id AND a.status = 'hadir'", 'left')
            ->join('dokumen d', "d.id_user = u.id_user AND d.event_id = p.event_id AND d.tipe = 'sertifikat'", 'left')
            ->where('p.event_id', $eventId)
            ->orderBy('u.nama_lengkap', 'ASC')
            ->get()->getResultArray();

        $data = array_map(static function($r){
            $roleFinal = strtolower($r['role_user'] ?? '');
            $attended  = (bool) ($r['attended'] ?? false);

            return [
                'id_user'       => (int) $r['id_user'],
                'nama_lengkap'  => (string) ($r['nama_lengkap'] ?? ''),
                'email'         => (string) ($r['email'] ?? ''),
                'role'          => $roleFinal ?: '',
                'attended'      => $attended,
                'attend_status' => (string) ($r['attend_status'] ?? ''),
                'has_cert'      => (bool)   ($r['has_cert'] ?? false),
                'eligible'      => $attended,
                'note'          => $attended ? '' : 'Belum absen',
            ];
        }, $rows);

        return $this->response->setJSON(['status' => 'success', 'data' => $data]);
    }

    /** Semua peserta yang HADIR (legacy, tetap untuk kompatibilitas lama). */
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

        // wajib verified payment & role presenter
        $verifiedPayment = $this->pembayaranModel
            ->where('id_user', $userId)->where('event_id', $eventId)->where('status', 'verified')->first();
        if (!$verifiedPayment || strtolower($user['role'] ?? '') !== 'presenter') {
            return redirect()->to(site_url('admin/dokumen'))->with('error', 'LOA hanya untuk Presenter dengan pembayaran terverifikasi.');
        }

        // tidak boleh dobel
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

        // wajib hadir
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

    // ================== DOWNLOAD ==================

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

    // ================== DELETE ==================

    public function delete($idDokumen)
    {
        $document = $this->dokumenModel->find($idDokumen);
        if (!$document) return redirect()->to(site_url('admin/dokumen'))->with('error', 'Dokumen tidak ditemukan.');

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

            return redirect()->to(site_url('admin/dokumen'))->with('success', 'Dokumen berhasil dihapus!');
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'Document deletion error: ' . $e->getMessage());
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

    private function getCompletionPerEvent(int $eventId): array
    {
        // Eligible LOA: Presenter + pembayaran verified
        $eligibleLoa = $this->db->table('pembayaran p')
            ->join('users u', 'u.id_user = p.id_user', 'left')
            ->where('p.event_id', $eventId)
            ->where('p.status', 'verified')
            ->where('u.role', 'presenter')
            ->countAllResults();

        $givenLoa = $this->dokumenModel->where(['event_id' => $eventId, 'tipe' => 'loa'])->countAllResults(true);

        // Eligible certificate: semua yang hadir
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
}