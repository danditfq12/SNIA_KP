<?php

namespace App\Controllers\Role\Admin;

use App\Controllers\BaseController;
use App\Models\DokumenModel;
use App\Models\EventModel;
use App\Models\UserModel;
use App\Models\AbsensiModel;
use App\Models\PembayaranModel;

class Dokumen extends BaseController
{
    protected DokumenModel $dokumenModel;
    protected EventModel $eventModel;
    protected UserModel $userModel;
    protected AbsensiModel $absensiModel;
    protected PembayaranModel $pembayaranModel;
    protected \CodeIgniter\Database\BaseConnection $db;

    public function __construct()
    {
        $this->dokumenModel = new DokumenModel();
        $this->eventModel   = new EventModel();
        $this->userModel    = new UserModel();
        $this->absensiModel = new AbsensiModel();
        $this->pembayaranModel = new PembayaranModel();
        $this->db           = \Config\Database::connect();
    }
    
    public function index()
    {
        $eventId = (int) $this->request->getGet('event_id');
        $tipe    = $this->request->getGet('tipe');

        $documents = $this->dokumenModel->getDokumenWithUser($tipe, $eventId ?: null);
        $events    = $this->eventModel->where('is_active', true)->orderBy('event_date', 'DESC')->findAll();
        $stats     = $this->getDocumentStatistics();

        return view('role/admin/dokumen/index', [
            'title'         => 'Manajemen Dokumen',
            'documents'     => $documents,
            'events'        => $events,
            'current_event' => $eventId ?: '',
            'current_tipe'  => $tipe,
            'stats'         => $stats,
        ]);
    }

    // ================== AJAX (Dropdown / List) ==================

    public function getUsersForLoa(int $eventId = 0)
    {
        if ($eventId <= 0) {
            return $this->response->setJSON(['status'=>'error','message'=>'event_id tidak valid'])->setStatusCode(400);
        }

        try {
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

            $fpMap = $this->getFpStatusMap($eventId);

            $loaRows = $this->db->table('dokumen')
                ->select('id_user')
                ->where(['event_id' => $eventId, 'tipe' => 'loa'])
                ->get()->getResultArray();
            $hasLoaMap = [];
            foreach ($loaRows as $r) $hasLoaMap[(int)$r['id_user']] = true;

            $acceptedAliases = ['accepted','accept','acc','diterima','approved'];

            $data = [];
            foreach ($rows as $r) {
                $uid   = (int) ($r['id_user'] ?? 0);
                $role  = strtolower((string) ($r['role_user'] ?? ''));
                $st    = strtolower((string) ($fpMap[$uid] ?? ''));
                if ($st !== '' && in_array($st, $acceptedAliases, true)) $st = 'accepted';

                $isPresenter = (strpos($role, 'presenter') === 0);
                
                // FILTER: Hanya tampilkan Presenter saja
                if (!$isPresenter) {
                    continue;
                }
                
                $eligible = ($isPresenter && $st === 'accepted');

                $data[] = [
                    'id_user'      => $uid,
                    'nama_lengkap' => (string) ($r['nama_lengkap'] ?? ''),
                    'email'        => (string) ($r['email'] ?? ''),
                    'role'         => $role ?: 'presenter',
                    'fp_status'    => $st,
                    'has_loa'      => (bool) ($hasLoaMap[$uid] ?? false),
                    'eligible'     => (bool) $eligible,
                    'note'         => $eligible ? '' : 'Syarat LOA: Presenter terdaftar & Full Paper ACCEPTED',
                ];
            }

            return $this->response->setJSON(['status' => 'success', 'data' => $data]);
        } catch (\Throwable $e) {
            log_message('error', 'getUsersForLoa error: ' . $e->getMessage());
            return $this->response->setJSON(['status' => 'error', 'message' => 'Gagal memuat user.'])->setStatusCode(500);
        }
    }

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

    // ================== Get Users for Dokumen Lainnya ==================
    
    public function getUsersForDokumenLain(int $eventId = 0)
    {
        $this->response->setContentType('application/json');
        
        if ($eventId <= 0) {
            log_message('error', 'getUsersForDokumenLain: Invalid event_id = ' . $eventId);
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'event_id tidak valid'
            ])->setStatusCode(400);
        }

        try {
            log_message('info', "getUsersForDokumenLain: Starting for event {$eventId}");
            
            $er = $this->resolveEventRegColumns();
            
            if (!$er['table']) {
                log_message('error', 'getUsersForDokumenLain: Event registration table not found');
                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => 'Tabel registrasi event tidak ditemukan.'
                ])->setStatusCode(500);
            }
            
            if (!$er['uid'] || !$er['eid']) {
                log_message('error', 'getUsersForDokumenLain: Required columns not found');
                return $this->response->setJSON([
                    'status'  => 'error',
                    'message' => 'Kolom yang diperlukan tidak ditemukan di tabel registrasi'
                ])->setStatusCode(500);
            }

            $builder = $this->db->table($er['table'] . ' er');
            $builder->distinct();
            $builder->select("
                u.id_user,
                u.nama_lengkap,
                u.email,
                COALESCE(LOWER(u.role), 'audience') AS role_user
            ", false);
            $builder->join('users u', 'u.id_user = er.' . $er['uid'], 'inner');
            $builder->where('er.' . $er['eid'], $eventId);
            $builder->where('u.id_user IS NOT NULL');
            $builder->orderBy('u.nama_lengkap', 'ASC');
            
            $rows = $builder->get()->getResultArray();
            
            log_message('info', 'getUsersForDokumenLain: Found ' . count($rows) . ' registered users');

            if (empty($rows)) {
                return $this->response->setJSON(['status' => 'success', 'data' => []]);
            }

            // Get payment verification status
            $paymentMap = [];
            try {
                if ($this->db->tableExists('pembayaran')) {
                    $fields = $this->db->getFieldNames('pembayaran') ?: [];
                    
                    $userCol = null;
                    foreach (['id_user', 'user_id', 'uid'] as $col) {
                        if (in_array($col, $fields)) {
                            $userCol = $col;
                            break;
                        }
                    }
                    
                    $eventCol = null;
                    foreach (['event_id', 'id_event'] as $col) {
                        if (in_array($col, $fields)) {
                            $eventCol = $col;
                            break;
                        }
                    }
                    
                    $statusCol = null;
                    foreach (['status', 'payment_status', 'status_bayar'] as $col) {
                        if (in_array($col, $fields)) {
                            $statusCol = $col;
                            break;
                        }
                    }
                    
                    if ($userCol && $eventCol && $statusCol) {
                        $paymentRows = $this->db->table('pembayaran')
                            ->select("{$userCol} AS uid, LOWER({$statusCol}) AS status", false)
                            ->where($eventCol, $eventId)
                            ->get()->getResultArray();
                        
                        foreach ($paymentRows as $pr) {
                            $uid = (int)($pr['uid'] ?? 0);
                            $status = strtolower(trim((string)($pr['status'] ?? '')));
                            if ($uid > 0 && $status === 'verified') {
                                $paymentMap[$uid] = true;
                            }
                        }
                    }
                }
            } catch (\Throwable $paymentError) {
                log_message('warning', 'getUsersForDokumenLain payment check error: ' . $paymentError->getMessage());
            }

            // Check who already has dokumen lainnya - count berapa dokumen per user
            $dokumenRows = $this->db->table('dokumen')
                ->select('id_user, COUNT(*) as doc_count')
                ->where(['event_id' => $eventId, 'tipe' => 'lainnya'])
                ->groupBy('id_user')
                ->get()->getResultArray();
            
            $hasDokumenMap = [];
            foreach ($dokumenRows as $r) {
                $hasDokumenMap[(int)$r['id_user']] = (int)$r['doc_count'];
            }

            $data = array_map(function($r) use ($hasDokumenMap, $paymentMap) {
                $uid = (int) ($r['id_user'] ?? 0);
                $role = strtolower(trim((string)($r['role_user'] ?? '')));
                $docCount = (int)($hasDokumenMap[$uid] ?? 0);
                
                return [
                    'id_user'          => $uid,
                    'nama_lengkap'     => (string) ($r['nama_lengkap'] ?? ''),
                    'email'            => (string) ($r['email'] ?? ''),
                    'role'             => $role ?: 'audience',
                    'payment_verified' => (bool) ($paymentMap[$uid] ?? false),
                    'has_dokumen'      => $docCount > 0,
                    'doc_count'        => $docCount,
                ];
            }, $rows);

            log_message('info', "getUsersForDokumenLain: Successfully prepared " . count($data) . " users");
            
            return $this->response->setJSON(['status' => 'success', 'data' => $data]);
            
        } catch (\Throwable $e) {
            log_message('error', 'getUsersForDokumenLain error: ' . $e->getMessage());
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan: ' . $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    // ================== UPLOAD LOA ==================

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

        $this->db->transBegin();
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

            $insertData = [
                'id_user'     => $userId,
                'event_id'    => $eventId,
                'tipe'        => 'loa',
                'file_path'   => $fileName,
                'syarat'      => 'Letter of Acceptance',
                'uploaded_at' => date('Y-m-d H:i:s'),
            ];

            if (!$this->dokumenModel->insert($insertData)) {
                throw new \RuntimeException('Gagal menyimpan data dokumen ke database.');
            }

            $this->logActivity(session('id_user'), "Uploaded LOA for {$user['nama_lengkap']} (Event: " . ($event['title'] ?? 'Unknown') . ")");
            
            $this->db->transCommit();

            return redirect()->to(site_url('admin/dokumen'))->with('success', 'LOA berhasil diupload!');
        } catch (\Throwable $e) {
            $this->db->transRollback();
            if (isset($fileName) && isset($uploadPath) && is_file($uploadPath . $fileName)) {
                @unlink($uploadPath . $fileName);
            }
            log_message('error', 'LOA upload error: ' . $e->getMessage());
            return redirect()->to(site_url('admin/dokumen'))->with('error', 'Error: ' . $e->getMessage());
        }
    }

    // ================== UPLOAD SERTIFIKAT ==================

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

        $this->db->transBegin();
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

            $insertData = [
                'id_user'     => $userId,
                'event_id'    => $eventId,
                'tipe'        => 'sertifikat',
                'file_path'   => $fileName,
                'syarat'      => 'Certificate of Participation',
                'uploaded_at' => date('Y-m-d H:i:s'),
            ];

            if (!$this->dokumenModel->insert($insertData)) {
                throw new \RuntimeException('Gagal menyimpan data dokumen ke database.');
            }

            $this->logActivity(session('id_user'), "Uploaded Certificate for {$user['nama_lengkap']} (Event: " . ($event['title'] ?? 'Unknown') . ")");
            
            $this->db->transCommit();

            return redirect()->to(site_url('admin/dokumen'))->with('success', 'Sertifikat berhasil diupload!');
        } catch (\Throwable $e) {
            $this->db->transRollback();
            if (isset($fileName) && isset($uploadPath) && is_file($uploadPath . $fileName)) {
                @unlink($uploadPath . $fileName);
            }
            log_message('error', 'Certificate upload error: ' . $e->getMessage());
            return redirect()->to(site_url('admin/dokumen'))->with('error', 'Error: ' . $e->getMessage());
        }
    }

    // ================== UPLOAD DOKUMEN LAINNYA - SUPPORT SINGLE & BULK ==================

    public function uploadDokumenLainnya()
    {
        $rules = [
            'event_id'    => 'required|integer',
            'upload_mode' => 'required|in_list[single,bulk]',
        ];
        
        if (!$this->validate($rules)) {
            return redirect()->to(site_url('admin/dokumen'))
                ->with('error', 'Event dan mode upload harus dipilih.');
        }

        $eventId    = (int) $this->request->getPost('event_id');
        $uploadMode = $this->request->getPost('upload_mode');
        $files      = $this->request->getFiles();

        $event = $this->eventModel->find($eventId);
        if (!$event) {
            return redirect()->to(site_url('admin/dokumen'))->with('error', 'Event tidak ditemukan.');
        }

        if (!isset($files['document_file']) || empty($files['document_file'])) {
            return redirect()->to(site_url('admin/dokumen'))->with('error', 'Tidak ada file yang diupload.');
        }

        $validExts = ['pdf', 'doc', 'docx', 'ppt', 'pptx', 'xls', 'xlsx', 'zip', 'rar', 'jpg', 'jpeg', 'png'];
        $maxFileSize = 10 * 1024 * 1024;
        
        $filesToUpload = [];
        foreach ($files['document_file'] as $file) {
            if ($file->getError() === UPLOAD_ERR_NO_FILE) continue;
            
            if (!$file->isValid()) {
                return redirect()->to(site_url('admin/dokumen'))
                    ->with('error', 'File tidak valid: ' . $file->getErrorString());
            }
            
            $ext = strtolower($file->getClientExtension());
            if (!in_array($ext, $validExts)) {
                return redirect()->to(site_url('admin/dokumen'))
                    ->with('error', 'Format file ' . $file->getClientName() . ' tidak didukung.');
            }
            
            if ($file->getSize() > $maxFileSize) {
                return redirect()->to(site_url('admin/dokumen'))
                    ->with('error', 'File ' . $file->getClientName() . ' melebihi 10MB.');
            }
            
            $filesToUpload[] = $file;
        }

        if (empty($filesToUpload)) {
            return redirect()->to(site_url('admin/dokumen'))
                ->with('error', 'Tidak ada file valid untuk diupload.');
        }

        if ($uploadMode === 'single') {
            $userId = (int) $this->request->getPost('user_id');
            if ($userId <= 0) {
                return redirect()->to(site_url('admin/dokumen'))->with('error', 'Pilih user terlebih dahulu.');
            }

            $user = $this->userModel->find($userId);
            if (!$user) {
                return redirect()->to(site_url('admin/dokumen'))->with('error', 'User tidak ditemukan.');
            }
            
            $targetUsers = [$userId];
        } else {
            $er = $this->resolveEventRegColumns();
            if (!$er['table'] || !$er['uid'] || !$er['eid']) {
                return redirect()->to(site_url('admin/dokumen'))
                    ->with('error', 'Tidak dapat menemukan tabel registrasi event.');
            }

            $rows = $this->db->table($er['table'])
                ->distinct()
                ->select($er['uid'] . ' AS uid', false)
                ->where($er['eid'], $eventId)
                ->get()->getResultArray();

            if (empty($rows)) {
                return redirect()->to(site_url('admin/dokumen'))
                    ->with('error', 'Tidak ada user terdaftar pada event ini.');
            }

            $targetUsers = array_map(function($r) {
                return (int)($r['uid'] ?? 0);
            }, $rows);
            $targetUsers = array_filter($targetUsers);
        }

        $this->db->transBegin();
        
        try {
            $uploadPath = WRITEPATH . 'uploads/lainnya/';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0775, true);
            }

            $successCount = 0;
            $skippedCount = 0;
            $uploadedFiles = [];
            $userSkipMap = [];

            foreach ($filesToUpload as $file) {
                $ext = strtolower($file->getClientExtension());
                $fileName = 'DOC_' . $eventId . '_' . time() . '_' . uniqid() . '.' . $ext;
                
                if (!$file->move($uploadPath, $fileName)) {
                    throw new \RuntimeException('Gagal menyimpan file: ' . $file->getErrorString());
                }
                
                $uploadedFiles[] = $fileName;
                $originalName = pathinfo($file->getClientName(), PATHINFO_FILENAME);
                
                foreach ($targetUsers as $uid) {
                    $existing = $this->dokumenModel->where([
                        'id_user'   => $uid,
                        'event_id'  => $eventId,
                        'tipe'      => 'lainnya',
                        'file_path' => $fileName,
                    ])->first();

                    if ($existing) {
                        if (!isset($userSkipMap[$uid])) {
                            $userSkipMap[$uid] = 0;
                        }
                        $userSkipMap[$uid]++;
                        $skippedCount++;
                        continue;
                    }

                    $insertData = [
                        'id_user'     => $uid,
                        'event_id'    => $eventId,
                        'tipe'        => 'lainnya',
                        'file_path'   => $fileName,
                        'syarat'      => $originalName,
                        'uploaded_at' => date('Y-m-d H:i:s'),
                    ];
                    
                    if ($this->dokumenModel->insert($insertData)) {
                        $successCount++;
                    }
                }
            }

            if ($uploadMode === 'single') {
                $user = $this->userModel->find($targetUsers[0]);
                $this->logActivity(
                    session('id_user'), 
                    "Uploaded " . count($filesToUpload) . " document(s) for " . ($user['nama_lengkap'] ?? 'User') . " (Event: " . ($event['title'] ?? 'Unknown') . ")"
                );
            } else {
                $totalFiles = count($filesToUpload);
                $totalUsers = count($targetUsers);
                $this->logActivity(
                    session('id_user'), 
                    "Bulk uploaded {$totalFiles} document(s) to {$totalUsers} users (Event: " . ($event['title'] ?? 'Unknown') . ")"
                );
            }
            
            $this->db->transCommit();
            
            if ($uploadMode === 'single') {
                $message = count($filesToUpload) === 1
                    ? "Dokumen berhasil diupload!"
                    : "Berhasil upload " . count($filesToUpload) . " dokumen!";
            } else {
                $totalFiles = count($filesToUpload);
                $totalUsers = count($targetUsers);
                $totalInserted = $successCount;
                
                $actualUsers = (int)($totalInserted / $totalFiles);
                
                $message = "Berhasil upload {$totalFiles} dokumen ke {$actualUsers} user";
                
                if ($skippedCount > 0) {
                    $skippedUsers = count($userSkipMap);
                    $message .= " ({$skippedUsers} user sudah memiliki beberapa dokumen)";
                }
                $message .= "!";
            }

            return redirect()->to(site_url('admin/dokumen'))->with('success', $message);
            
        } catch (\Throwable $e) {
            $this->db->transRollback();
            
            if (!empty($uploadedFiles)) {
                foreach ($uploadedFiles as $fname) {
                    $fpath = $uploadPath . $fname;
                    if (is_file($fpath)) {
                        @unlink($fpath);
                    }
                }
            }
            
            log_message('error', 'Document upload error: ' . $e->getMessage());
            return redirect()->to(site_url('admin/dokumen'))
                ->with('error', 'Error saat upload: ' . $e->getMessage());
        }
    }

    // ================== DOWNLOAD & DELETE ==================

    public function download($idDokumen)
    {
        $document = $this->dokumenModel->getOneWithDetails($idDokumen);
        if (!$document) throw new \CodeIgniter\Exceptions\PageNotFoundException('Dokumen tidak ditemukan.');

        $tipe = $document['tipe'] ?? 'lainnya';
        $basePath = WRITEPATH . 'uploads/' . $tipe . '/';
        $filePath = $basePath . $document['file_path'];
        
        if (!is_file($filePath)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('File tidak ditemukan: ' . $filePath);
        }

        $eventTitle   = $document['event_title'] ? preg_replace('/[^A-Za-z0-9_-]/', '_', $document['event_title']) : 'Event';
        $extension    = pathinfo($document['file_path'], PATHINFO_EXTENSION);
        
        if ($tipe === 'lainnya') {
            $docName = preg_replace('/[^A-Za-z0-9_-]/', '_', $document['syarat'] ?? 'Document');
            $downloadName = $docName . '_' . mb_substr($eventTitle, 0, 60) . '.' . $extension;
        } else {
            $userName     = preg_replace('/[^A-Za-z0-9_-]/', '_', $document['nama_lengkap'] ?? 'Unknown');
            $downloadName = strtoupper($document['tipe']) . '_' . mb_substr($eventTitle, 0, 60) . '_' . mb_substr($userName, 0, 60) . '.' . $extension;
        }

        $this->logActivity(session('id_user'), "Downloaded {$document['tipe']} for " . ($document['nama_lengkap'] ?? ($document['syarat'] ?? 'Document')) . " (Event: " . ($document['event_title'] ?? 'Unknown') . ")");
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
                ])->setStatusCode(404);
            }
            return redirect()->to(site_url('admin/dokumen'))->with('error', 'Dokumen tidak ditemukan.');
        }

        $this->db->transBegin();
        try {
            $user  = $document['id_user'] > 0 ? $this->userModel->find($document['id_user']) : null;
            $event = $this->eventModel->find($document['event_id']);

            $tipe = strtolower($document['tipe'] ?? 'lainnya');
            
            if ($tipe === 'lainnya' && (int)$document['id_user'] > 0) {
                $filePath = $document['file_path'];
                $eventId = $document['event_id'];
                $userId = (int)$document['id_user'];
                
                $this->dokumenModel->delete($idDokumen);
                
                $otherUsers = $this->db->table('dokumen')
                    ->where('file_path', $filePath)
                    ->where('event_id', $eventId)
                    ->where('tipe', 'lainnya')
                    ->countAllResults();
                
                if ($otherUsers === 0) {
                    $physicalPath = WRITEPATH . 'uploads/lainnya/' . $filePath;
                    if (is_file($physicalPath)) {
                        @unlink($physicalPath);
                    }
                }
            } else {
                $filePath = WRITEPATH . 'uploads/' . $tipe . '/' . $document['file_path'];
                if (is_file($filePath)) {
                    @unlink($filePath);
                }
                $this->dokumenModel->delete($idDokumen);
            }

            $userName = $user ? ($user['nama_lengkap'] ?? 'Unknown') : ($document['syarat'] ?? 'Document');
            $this->logActivity(session('id_user'), "Deleted {$document['tipe']} for " . $userName . " (Event: " . ($event['title'] ?? 'Unknown') . ")");
            
            $this->db->transCommit();

            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'status'     => 'success',
                    'message'    => 'Dokumen berhasil dihapus!',
                    'csrf_hash'  => csrf_hash(),
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
                ])->setStatusCode(500);
            }

            return redirect()->to(site_url('admin/dokumen'))->with('error', 'Error: ' . $e->getMessage());
        }
    }

    // ================== BULK DELETE FOR DOKUMEN LAINNYA ==================
    
    public function bulkDeleteDokumen()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->to(site_url('admin/dokumen'))->with('error', 'Invalid request');
        }

        $eventId  = (int) $this->request->getPost('event_id');
        $filePath = $this->request->getPost('file_path');

        if (!$eventId || !$filePath) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Parameter tidak lengkap',
                'csrf_hash' => csrf_hash(),
            ])->setStatusCode(400);
        }

        $this->db->transBegin();
        try {
            $affectedUsers = $this->db->table('dokumen')
                ->where('file_path', $filePath)
                ->where('event_id', $eventId)
                ->where('tipe', 'lainnya')
                ->countAllResults();

            $this->db->table('dokumen')
                ->where('file_path', $filePath)
                ->where('event_id', $eventId)
                ->where('tipe', 'lainnya')
                ->delete();

            $physicalPath = WRITEPATH . 'uploads/lainnya/' . $filePath;
            if (is_file($physicalPath)) {
                @unlink($physicalPath);
            }

            $event = $this->eventModel->find($eventId);
            $this->logActivity(
                session('id_user'), 
                "Bulk deleted document '{$filePath}' from {$affectedUsers} users (Event: " . ($event['title'] ?? 'Unknown') . ")"
            );

            $this->db->transCommit();

            return $this->response->setJSON([
                'status'  => 'success',
                'message' => "Dokumen berhasil dihapus dari {$affectedUsers} user!",
                'csrf_hash' => csrf_hash(),
            ]);

        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'Bulk delete error: ' . $e->getMessage());
            
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'Error: ' . $e->getMessage(),
                'csrf_hash' => csrf_hash(),
            ])->setStatusCode(500);
        }
    }

    // ================== UTIL ==================

    private function getDocumentStatistics(): array
    {
        $totalDocuments  = $this->dokumenModel->countAll();
        
        $loaCount = $this->db->table('dokumen')->where('tipe', 'loa')->countAllResults();
        $sertifikatCount = $this->db->table('dokumen')->where('tipe', 'sertifikat')->countAllResults();
        
        $lainnyaResult = $this->db->table('dokumen')
            ->select('file_path, event_id')
            ->where('tipe', 'lainnya')
            ->groupBy('file_path, event_id')
            ->get()
            ->getResultArray();
        $lainnyaCount = count($lainnyaResult);
        
        $weekAgo = date('Y-m-d H:i:s', strtotime('-1 week'));
        $recentUploads = $this->dokumenModel->where('uploaded_at >=', $weekAgo)->countAllResults(true);

        return [
            'total_documents'  => (int) $totalDocuments,
            'loa_count'        => (int) $loaCount,
            'sertifikat_count' => (int) $sertifikatCount,
            'lainnya_count'    => (int) $lainnyaCount,
            'recent_uploads'   => (int) $recentUploads,
        ];
    }

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

    private function resolveEventRegColumns(): array
    {
        $db = $this->db;
        $table = null;
        
        $possibleTables = ['event_registrations', 'registrations', 'pendaftaran_event', 'event_registration'];
        foreach ($possibleTables as $t) {
            if ($db->tableExists($t)) { 
                $table = $t; 
                break; 
            }
        }
        
        if (!$table) return ['table'=>null,'uid'=>null,'eid'=>null,'role'=>null];

        $fields = $db->getFieldNames($table) ?: [];
        $fieldsFlip = array_flip($fields);
        
        $uid  = null; 
        foreach (['user_id','id_user','uid'] as $c) {
            if (isset($fieldsFlip[$c])) { 
                $uid = $c; 
                break; 
            }
        }
        
        $eid  = null; 
        foreach (['event_id','id_event'] as $c) {
            if (isset($fieldsFlip[$c])) { 
                $eid = $c; 
                break; 
            }
        }
        
        $role = null; 
        foreach (['role','tipe','jenis','user_role'] as $c) {
            if (isset($fieldsFlip[$c])) { 
                $role = $c; 
                break; 
            }
        }

        return ['table'=>$table,'uid'=>$uid,'eid'=>$eid,'role'=>$role];
    }

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
            $has = $qb->countAllResults();
            if ($has <= 0) return false;

            $roleUser = $this->db->table('users')->select('LOWER(role) AS r')->where('id_user',$userId)->get()->getRowArray();
            return $roleUser && strpos(($roleUser['r'] ?? ''), 'presenter') === 0;
        }

        return $qb->countAllResults() > 0;
    }
}