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
    protected $dokumenModel;
    protected $eventModel;
    protected $userModel;
    protected $pembayaranModel;
    protected $absensiModel;
    protected $db;

    public function __construct()
    {
        $this->dokumenModel    = new DokumenModel();
        $this->eventModel      = new EventModel();
        $this->userModel       = new UserModel();
        $this->pembayaranModel = new PembayaranModel();
        $this->absensiModel    = new AbsensiModel();
        $this->db              = \Config\Database::connect();
    }

    public function index()
    {
        $eventId = $this->request->getGet('event_id');
        $tipe    = $this->request->getGet('tipe');

        $documents = $this->dokumenModel->getDokumenWithUser($tipe, $eventId);
        $events    = $this->eventModel->where('is_active', true)
                                      ->orderBy('event_date', 'DESC')
                                      ->findAll();
        $stats     = $this->getDocumentStatistics();

        return view('role/admin/dokumen/index', [
            'title'         => 'Manajemen Dokumen',
            'documents'     => $documents,
            'events'        => $events,
            'current_event' => $eventId,
            'current_tipe'  => $tipe,
            'stats'         => $stats
        ]);
    }

    // ===== Upload TANPA parameter URL (event_id dari POST) =====
    public function uploadLoa()
    {
        $rules = [
            'event_id' => 'required|integer',
            'user_id'  => 'required|integer',
            'loa_file' => [
                'uploaded[loa_file]',
                'max_size[loa_file,5120]',
                'ext_in[loa_file,pdf,doc,docx]'
            ]
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

        $user = $this->userModel->find($userId);
        if (!$user) return redirect()->to(site_url('admin/dokumen'))->with('error', 'User tidak ditemukan.');

        // Wajib: pembayaran verified untuk event tsb
        $verifiedPayment = $this->pembayaranModel
            ->where('id_user', $userId)
            ->where('event_id', $eventId)
            ->where('status', 'verified')
            ->first();
        if (!$verifiedPayment) {
            return redirect()->to(site_url('admin/dokumen'))->with('error', 'User belum memiliki pembayaran yang terverifikasi untuk event ini.');
        }

        if ($this->dokumenModel->hasUserDocument($userId, $eventId, 'loa')) {
            return redirect()->to(site_url('admin/dokumen'))->with('error', 'LOA untuk user ini sudah ada.');
        }

        $this->db->transStart();
        try {
            $uploadPath = WRITEPATH . 'uploads/loa/';
            if (!is_dir($uploadPath)) mkdir($uploadPath, 0755, true);

            $fileName = 'LOA_' . $eventId . '_' . $userId . '_' . time() . '.' . $file->getExtension();
            if (!$file->move($uploadPath, $fileName)) {
                throw new \Exception('Gagal menyimpan file: ' . $file->getErrorString());
            }

            $documentData = [
                'id_user'     => $userId,
                'event_id'    => $eventId,
                'tipe'        => 'loa',
                'file_path'   => $fileName,
                'syarat'      => 'Letter of Acceptance',
                'uploaded_at' => date('Y-m-d H:i:s')
            ];
            if (!$this->dokumenModel->insert($documentData)) {
                throw new \Exception('Gagal menyimpan data dokumen: ' . implode(', ', $this->dokumenModel->errors()));
            }

            $this->logActivity(session('id_user'), "Uploaded LOA for {$user['nama_lengkap']} in event: " . ($event['title'] ?? 'Unknown Event'));
            $this->db->transComplete();
            if ($this->db->transStatus() === false) throw new \Exception('Transaction failed');

            return redirect()->to(site_url('admin/dokumen'))->with('success', 'LOA berhasil diupload!');
        } catch (\Exception $e) {
            $this->db->transRollback();
            if (isset($fileName) && file_exists($uploadPath . $fileName)) @unlink($uploadPath . $fileName);
            log_message('error', 'LOA upload error: ' . $e->getMessage());
            return redirect()->to(site_url('admin/dokumen'))->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function uploadSertifikat()
    {
        $rules = [
            'event_id'        => 'required|integer',
            'user_id'         => 'required|integer',
            'sertifikat_file' => [
                'uploaded[sertifikat_file]',
                'max_size[sertifikat_file,5120]',
                'ext_in[sertifikat_file,pdf,jpg,jpeg,png]'
            ]
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

        $user = $this->userModel->find($userId);
        if (!$user) return redirect()->to(site_url('admin/dokumen'))->with('error', 'User tidak ditemukan.');

        $attendance = $this->absensiModel
            ->where('id_user', $userId)
            ->where('event_id', $eventId)
            ->where('status', 'hadir')
            ->first();
        if (!$attendance) {
            return redirect()->to(site_url('admin/dokumen'))->with('error', 'User belum hadir pada event ini.');
        }

        if ($this->dokumenModel->hasUserDocument($userId, $eventId, 'sertifikat')) {
            return redirect()->to(site_url('admin/dokumen'))->with('error', 'Sertifikat untuk user ini sudah ada.');
        }

        $this->db->transStart();
        try {
            $uploadPath = WRITEPATH . 'uploads/sertifikat/';
            if (!is_dir($uploadPath)) mkdir($uploadPath, 0755, true);

            $fileName = 'SERTIFIKAT_' . $eventId . '_' . $userId . '_' . time() . '.' . $file->getExtension();
            if (!$file->move($uploadPath, $fileName)) {
                throw new \Exception('Gagal menyimpan file: ' . $file->getErrorString());
            }

            $documentData = [
                'id_user'     => $userId,
                'event_id'    => $eventId,
                'tipe'        => 'sertifikat',
                'file_path'   => $fileName,
                'syarat'      => 'Certificate of Participation',
                'uploaded_at' => date('Y-m-d H:i:s')
            ];
            if (!$this->dokumenModel->insert($documentData)) {
                throw new \Exception('Gagal menyimpan data dokumen: ' . implode(', ', $this->dokumenModel->errors()));
            }

            $this->logActivity(session('id_user'), "Uploaded Certificate for {$user['nama_lengkap']} in event: " . ($event['title'] ?? 'Unknown Event'));
            $this->db->transComplete();
            if ($this->db->transStatus() === false) throw new \Exception('Transaction failed');

            return redirect()->to(site_url('admin/dokumen'))->with('success', 'Sertifikat berhasil diupload!');
        } catch (\Exception $e) {
            $this->db->transRollback();
            if (isset($fileName) && file_exists($uploadPath . $fileName)) @unlink($uploadPath . $fileName);
            log_message('error', 'Certificate upload error: ' . $e->getMessage());
            return redirect()->to(site_url('admin/dokumen'))->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function download($idDokumen)
    {
        $document = $this->dokumenModel->getOneWithDetails($idDokumen);
        if (!$document) throw new \CodeIgniter\Exceptions\PageNotFoundException('Dokumen tidak ditemukan.');

        $basePath = WRITEPATH . 'uploads/' . $document['tipe'] . '/';
        $filePath = $basePath . $document['file_path'];
        if (!file_exists($filePath)) throw new \CodeIgniter\Exceptions\PageNotFoundException('File tidak ditemukan: ' . $filePath);

        $eventTitle   = $document['event_title'] ? preg_replace('/[^A-Za-z0-9_-]/', '_', $document['event_title']) : 'Event';
        $userName     = preg_replace('/[^A-Za-z0-9_-]/', '_', $document['nama_lengkap'] ?? 'Unknown');
        $extension    = pathinfo($document['file_path'], PATHINFO_EXTENSION);
        $downloadName = strtoupper($document['tipe']) . '_' . $eventTitle . '_' . $userName . '.' . $extension;

        $this->logActivity(session('id_user'), "Downloaded {$document['tipe']} for " . ($document['nama_lengkap'] ?? 'Unknown User') . " from event: " . ($document['event_title'] ?? 'Unknown Event'));
        return $this->response->download($filePath, null)->setFileName($downloadName);
    }

    public function delete($idDokumen)
    {
        $document = $this->dokumenModel->find($idDokumen);
        if (!$document) return redirect()->to(site_url('admin/dokumen'))->with('error', 'Dokumen tidak ditemukan.');

        $this->db->transStart();
        try {
            $user  = $this->userModel->find($document['id_user']);
            $event = $this->eventModel->find($document['event_id']);

            $filePath = WRITEPATH . 'uploads/' . $document['tipe'] . '/' . $document['file_path'];
            if (file_exists($filePath)) @unlink($filePath);

            if (!$this->dokumenModel->delete($idDokumen)) throw new \Exception('Gagal menghapus data dokumen');

            $this->logActivity(session('id_user'), "Deleted {$document['tipe']} for " . ($user['nama_lengkap'] ?? 'Unknown User') . " from event: " . ($event['title'] ?? 'Unknown Event'));
            $this->db->transComplete();
            if ($this->db->transStatus() === false) throw new \Exception('Transaction failed');

            return redirect()->to(site_url('admin/dokumen'))->with('success', 'Dokumen berhasil dihapus!');
        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Document deletion error: ' . $e->getMessage());
            return redirect()->to(site_url('admin/dokumen'))->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function generateBulkLOA()
    {
        $eventId = $this->request->getPost('event_id');
        if (!$eventId) return redirect()->to(site_url('admin/dokumen'))->with('error', 'Event ID diperlukan.');

        $event = $this->eventModel->find($eventId);
        if (!$event) return redirect()->to(site_url('admin/dokumen'))->with('error', 'Event tidak ditemukan.');

        $eligiblePresenters = $this->dokumenModel->getEligiblePresentersForLOA($eventId);
        log_message('debug', 'Eligible presenters for LOA: {p}', ['p' => json_encode($eligiblePresenters)]);
        if (empty($eligiblePresenters)) return redirect()->to(site_url('admin/dokumen'))->with('error', 'Tidak ada presenter yang memenuhi syarat untuk LOA.');

        $this->db->transStart();
        try {
            $successCount = 0;
            $uploadPath   = WRITEPATH . 'uploads/loa/';
            if (!is_dir($uploadPath)) mkdir($uploadPath, 0755, true);

            foreach ($eligiblePresenters as $presenter) {
                $pdfPath = $this->generateLOAPDF($presenter, $event, $uploadPath);
                if ($pdfPath) {
                    $documentData = [
                        'id_user'     => $presenter['id_user'],
                        'event_id'    => $eventId,
                        'tipe'        => 'loa',
                        'file_path'   => basename($pdfPath),
                        'syarat'      => 'Letter of Acceptance - Generated',
                        'uploaded_at' => date('Y-m-d H:i:s')
                    ];
                    if ($this->dokumenModel->insert($documentData)) $successCount++;
                }
            }

            $this->logActivity(session('id_user'), 'Generated ' . $successCount . ' LOA documents for event: ' . ($event['title'] ?? 'Unknown Event'));
            $this->db->transComplete();
            if ($this->db->transStatus() === false) throw new \Exception('Transaction failed');

            return redirect()->to(site_url('admin/dokumen'))->with('success', 'Berhasil generate ' . $successCount . ' LOA dari ' . count($eligiblePresenters) . ' presenter!');
        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Bulk LOA generation error: ' . $e->getMessage());
            return redirect()->to(site_url('admin/dokumen'))->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function generateBulkSertifikat()
    {
        $eventId = $this->request->getPost('event_id');
        if (!$eventId) return redirect()->to(site_url('admin/dokumen'))->with('error', 'Event ID diperlukan.');

        $event = $this->eventModel->find($eventId);
        if (!$event) return redirect()->to(site_url('admin/dokumen'))->with('error', 'Event tidak ditemukan.');

        $eligibleUsers = $this->dokumenModel->getEligibleUsersForCertificate($eventId);
        log_message('debug', 'Eligible users for Certificate: {u}', ['u' => json_encode($eligibleUsers)]);
        if (empty($eligibleUsers)) return redirect()->to(site_url('admin/dokumen'))->with('error', 'Tidak ada peserta yang memenuhi syarat untuk sertifikat.');

        $this->db->transStart();
        try {
            $successCount = 0;
            $uploadPath   = WRITEPATH . 'uploads/sertifikat/';
            if (!is_dir($uploadPath)) mkdir($uploadPath, 0755, true);

            foreach ($eligibleUsers as $user) {
                $pdfPath = $this->generateCertificatePDF($user, $event, $uploadPath);
                if ($pdfPath) {
                    $documentData = [
                        'id_user'     => $user['id_user'],
                        'event_id'    => $eventId,
                        'tipe'        => 'sertifikat',
                        'file_path'   => basename($pdfPath),
                        'syarat'      => 'Certificate of Participation - Generated',
                        'uploaded_at' => date('Y-m-d H:i:s')
                    ];
                    if ($this->dokumenModel->insert($documentData)) $successCount++;
                }
            }

            $this->logActivity(session('id_user'), 'Generated ' . $successCount . ' certificates for event: ' . ($event['title'] ?? 'Unknown Event'));
            $this->db->transComplete();
            if ($this->db->transStatus() === false) throw new \Exception('Transaction failed');

            return redirect()->to(site_url('admin/dokumen'))->with('success', 'Berhasil generate ' . $successCount . ' sertifikat dari ' . count($eligibleUsers) . ' peserta!');
        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Bulk certificate generation error: ' . $e->getMessage());
            return redirect()->to(site_url('admin/dokumen'))->with('error', 'Error: ' . $e->getMessage());
        }
    }

    // =========================================================
    //  ====  TAMBAHAN: ENDPOINT LOA ELIGIBLE (VERIFIED)    ====
    // =========================================================

    /**
     * Ambil PRESENTER yang sudah bayar verified untuk event tertentu.
     * Sumber: tabel pembayaran (status='verified') + join users (+ optional event_registrations).
     */
    public function getVerifiedPresenters(int $eventId = 0)
    {
        if ($eventId <= 0) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'event_id tidak valid'
            ])->setStatusCode(400);
        }

        $rows = $this->db->table('pembayaran p')
            ->select('DISTINCT u.id_user, u.nama_lengkap, u.email, u.role')
            ->join('users u', 'u.id_user = p.id_user', 'left')
            ->join('event_registrations er', 'er.event_id = p.event_id AND er.id_user = p.id_user', 'left')
            ->where('p.event_id', (int)$eventId)
            ->where('p.status', 'verified')
            ->groupStart()
                ->where('COALESCE(p.participation_type, er.participation_type, u.role)', 'presenter')
                ->orWhere('u.role', 'presenter')
            ->groupEnd()
            ->orderBy('u.nama_lengkap', 'asc')
            ->get()->getResultArray();

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => array_map(static function ($r) {
                return [
                    'id_user'      => (int)$r['id_user'],
                    'nama_lengkap' => (string)($r['nama_lengkap'] ?? ''),
                    'email'        => (string)($r['email'] ?? ''),
                    'role'         => (string)($r['role'] ?? 'presenter'),
                ];
            }, $rows),
        ]);
    }

    /**
     * Pencarian cepat user eligible LOA (tetap hanya verified & presenter).
     * GET /admin/dokumen/search-eligible-loa?event_id=123&q=andi
     * Gunakan q="*" untuk tampilkan semua verified presenter event tsb.
     */
    public function searchEligibleLoa()
    {
        $eventId = (int)$this->request->getGet('event_id');
        $q       = trim((string)$this->request->getGet('q'));

        if ($eventId <= 0) {
            return $this->response->setJSON([
                'status'  => 'error',
                'message' => 'event_id tidak valid'
            ])->setStatusCode(400);
        }

        $builder = $this->db->table('pembayaran p')
            ->select('DISTINCT u.id_user, u.nama_lengkap, u.email, u.role')
            ->join('users u', 'u.id_user = p.id_user', 'left')
            ->join('event_registrations er', 'er.event_id = p.event_id AND er.id_user = p.id_user', 'left')
            ->where('p.event_id', $eventId)
            ->where('p.status', 'verified')
            ->groupStart()
                ->where('COALESCE(p.participation_type, er.participation_type, u.role)', 'presenter')
                ->orWhere('u.role', 'presenter')
            ->groupEnd();

        if ($q !== '' && $q !== '*') {
            $q = strtolower($q);
            // gunakan LOWER(...) agar pencarian tidak case-sensitive
            $builder->groupStart()
                    ->like('LOWER(u.nama_lengkap)', $q)
                    ->orLike('LOWER(u.email)', $q)
                    ->groupEnd();
        }

        $rows = $builder->orderBy('u.nama_lengkap', 'asc')->get()->getResultArray();

        return $this->response->setJSON([
            'status' => 'success',
            'data'   => array_map(static function ($r) {
                return [
                    'id_user'      => (int)$r['id_user'],
                    'nama_lengkap' => (string)($r['nama_lengkap'] ?? ''),
                    'email'        => (string)($r['email'] ?? ''),
                    'role'         => (string)($r['role'] ?? 'presenter'),
                ];
            }, $rows),
        ]);
    }

    // =========================================================

    private function getDocumentStatistics()
    {
        $totalDocuments  = $this->dokumenModel->countAll();
        $loaCount        = $this->dokumenModel->where('tipe', 'loa')->countAllResults();
        $sertifikatCount = $this->dokumenModel->where('tipe', 'sertifikat')->countAllResults();
        $weekAgo         = date('Y-m-d H:i:s', strtotime('-1 week'));
        $recentUploads   = $this->dokumenModel->where('uploaded_at >=', $weekAgo)->countAllResults();
        return [
            'total_documents'  => $totalDocuments,
            'loa_count'        => $loaCount,
            'sertifikat_count' => $sertifikatCount,
            'recent_uploads'   => $recentUploads
        ];
    }

    private function generateLOAPDF($presenter, $event, $uploadPath)
    {
        try {
            $mpdf = new Mpdf([
                'mode'          => 'utf-8',
                'format'        => 'A4',
                'orientation'   => 'P',
                'margin_left'   => 15,
                'margin_right'  => 15,
                'margin_top'    => 20,
                'margin_bottom' => 20,
                'default_font'  => 'dejavusans',
            ]);
            $mpdf->SetTitle('Letter of Acceptance - ' . ($presenter['nama_lengkap'] ?? 'Presenter'));
            $mpdf->SetAuthor('SNIA Organization');
            $html    = $this->getLOAHTML($presenter, $event);
            $mpdf->WriteHTML($html);
            $fileName = 'LOA_' . $event['id'] . '_' . $presenter['id_user'] . '_' . time() . '.pdf';
            $filePath = $uploadPath . $fileName;
            $mpdf->Output($filePath, 'F');
            return $filePath;
        } catch (\Exception $e) {
            log_message('error', 'LOA PDF generation error: ' . $e->getMessage());
            return false;
        }
    }

    private function generateCertificatePDF($user, $event, $uploadPath)
    {
        try {
            $mpdf = new Mpdf([
                'mode'               => 'utf-8',
                'format'             => 'A4-L',
                'orientation'        => 'L',
                'margin_left'        => 0,
                'margin_right'       => 0,
                'margin_top'         => 0,
                'margin_bottom'      => 0,
                'margin_header'      => 0,
                'margin_footer'      => 0,
                'default_font_size'  => 12,
                'default_font'       => 'dejavusans',
            ]);
            $mpdf->SetTitle('Certificate of Participation - ' . ($user['nama_lengkap'] ?? 'Participant'));
            $mpdf->SetAuthor('SNIA Organization');
            $mpdf->SetSubject('Certificate of Participation');
            $mpdf->SetAutoPageBreak(false);
            $html    = $this->getCertificateHTML($user, $event);
            $mpdf->WriteHTML($html);
            $fileName = 'SERTIFIKAT_' . $event['id'] . '_' . $user['id_user'] . '_' . time() . '.pdf';
            $filePath = $uploadPath . $fileName;
            $mpdf->Output($filePath, 'F');
            return $filePath;
        } catch (\Exception $e) {
            log_message('error', 'Certificate PDF generation error: ' . $e->getMessage());
            return false;
        }
    }

    private function getLOAHTML($presenter, $event)
    {
        $eventDate    = date('d F Y', strtotime($event['event_date'] ?? ''));
        $currentDate  = date('d F Y');
        $eventTitle   = $event['title'] ?? 'Event Title';
        $eventTime    = $event['event_time'] ?? 'TBA';
        $eventFormat  = $event['format'] ?? 'offline';
        $presenterName = $presenter['nama_lengkap'] ?? 'Presenter Name';

        return '
        <style>
            body { font-family: Arial, sans-serif; line-height: 1.6; }
            .header { text-align: center; margin-bottom: 40px; }
            .header h1 { color: #2563eb; font-size: 28px; margin-bottom: 10px; }
            .header h2 { color: #1e40af; font-size: 20px; margin: 0; }
            .content { margin: 20px 0; text-align: justify; }
            .content p { margin-bottom: 15px; }
            .details { background-color: #f8f9fa; padding: 15px; border-left: 4px solid #2563eb; margin: 20px 0; }
            .details ul { margin: 10px 0; padding-left: 20px; }
            .signature { margin-top: 60px; text-align: right; }
            .signature p { margin: 5px 0; }
            .date { text-align: left; margin-bottom: 30px; }
        </style>

        <div class="header">
            <h1>LETTER OF ACCEPTANCE</h1>
            <h2>' . htmlspecialchars($eventTitle) . '</h2>
        </div>

        <div class="date">
            <p><strong>Date:</strong> ' . $currentDate . '</p>
        </div>

        <div class="content">
            <p>Dear <strong>' . htmlspecialchars($presenterName) . '</strong>,</p>
            <p>We are pleased to inform you that your participation as a presenter in <strong>' . htmlspecialchars($eventTitle) . '</strong> has been accepted.</p>
            <div class="details">
                <p><strong>Event Details:</strong></p>
                <ul>
                    <li><strong>Event:</strong> ' . htmlspecialchars($eventTitle) . '</li>
                    <li><strong>Date:</strong> ' . $eventDate . '</li>
                    <li><strong>Time:</strong> ' . htmlspecialchars($eventTime) . '</li>
                    <li><strong>Format:</strong> ' . ucfirst(htmlspecialchars($eventFormat)) . '</li>
                </ul>
            </div>
            <p>We look forward to your valuable contribution to this event and appreciate your participation in making this event successful.</p>
            <p>Should you have any questions or require further information, please do not hesitate to contact us.</p>
            <p>Best regards,</p>
        </div>

        <div class="signature">
            <p><strong>SNIA Organization</strong></p>
            <p>Event Committee</p>
        </div>';
    }

    private function getCertificateHTML($user, $event)
    {
        $eventDate = date('d F Y', strtotime($event['event_date'] ?? ''));
        $eventTitle = $event['title'] ?? 'Event Title';
        $userName = $user['nama_lengkap'] ?? 'Participant Name';

        return '
        <style>
            @page { size: A4 landscape; margin: 0; }
            body { font-family: "Times New Roman", serif; margin: 0; padding: 0; width: 297mm; height: 210mm; overflow: hidden; }
            .certificate { width: 100%; height: 100%; border: 12mm solid #2563eb; padding: 0; text-align: center; background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%); box-sizing: border-box; display: flex; flex-direction: column; justify-content: center; align-items: center; position: relative; }
            .content-wrapper { width: 100%; height: 100%; display: flex; flex-direction: column; justify-content: center; align-items: center; padding: 20mm; box-sizing: border-box; }
            .title { font-size: 42px; color: #2563eb; margin-bottom: 8px; font-weight: bold; text-shadow: 2px 2px 4px rgba(0,0,0,0.1); letter-spacing: 8px; }
            .subtitle { font-size: 22px; margin-bottom: 25px; color: #1e40af; letter-spacing: 4px; font-weight: 600; }
            .decorative-line { width: 180px; height: 3px; background: linear-gradient(to right, #2563eb, #1e40af); margin: 15px auto; }
            .certify-text { font-size: 18px; margin: 20px 0; color: #374151; font-style: italic; }
            .recipient { font-size: 32px; color: #1e40af; margin: 25px 0; font-weight: bold; text-decoration: underline; text-decoration-color: #2563eb; text-decoration-thickness: 2px; max-width: 80%; word-wrap: break-word; }
            .participation-text { font-size: 18px; margin: 20px 0; color: #374151; font-style: italic; }
            .event-title { font-size: 24px; margin: 20px 0; font-style: italic; color: #374151; line-height: 1.3; font-weight: 600; max-width: 85%; word-wrap: break-word; }
            .date { font-size: 16px; margin-top: 25px; color: #6b7280; font-weight: 500; }
            .signature { margin-top: 30px; font-size: 16px; color: #374151; position: absolute; bottom: 30mm; right: 40mm; text-align: center; }
            .signature p { margin: 3px 0; line-height: 1.2; }
            .org-logo { position: absolute; top: 15mm; right: 15mm; font-size: 12px; color: #6b7280; font-style: italic; }
            .corner-decoration { position: absolute; width: 30px; height: 30px; border: 2px solid #2563eb; }
            .corner-decoration.top-left { top: 20mm; left: 20mm; border-right: none; border-bottom: none; }
            .corner-decoration.top-right { top: 20mm; right: 20mm; border-left: none; border-bottom: none; }
            .corner-decoration.bottom-left { bottom: 20mm; left: 20mm; border-right: none; border-top: none; }
            .corner-decoration.bottom-right { bottom: 20mm; right: 20mm; border-left: none; border-top: none; }
        </style>
        <div class="certificate">
            <div class="corner-decoration top-left"></div>
            <div class="corner-decoration top-right"></div>
            <div class="corner-decoration bottom-left"></div>
            <div class="corner-decoration bottom-right"></div>
            <div class="org-logo">SNIA-2025</div>
            <div class="content-wrapper">
                <div class="title">CERTIFICATE</div>
                <div class="subtitle">OF PARTICIPATION</div>
                <div class="decorative-line"></div>
                <div class="certify-text">This is to certify that</div>
                <div class="recipient">' . htmlspecialchars($userName) . '</div>
                <div class="participation-text">has successfully participated in</div>
                <div class="event-title">' . htmlspecialchars($eventTitle) . '</div>
                <div class="decorative-line"></div>
                <div class="date">Held on ' . $eventDate . '</div>
            </div>
            <div class="signature">
                <p><strong>SNIA Organization</strong></p>
                <p>Event Committee</p>
                <p style="font-size: 12px; margin-top: 8px;">Authorized Signature</p>
            </div>
        </div>';
    }

    private function logActivity($userId, $activity)
    {
        try {
            $this->db->table('log_aktivitas')->insert([
                'id_user'   => $userId,
                'aktivitas' => $activity,
                'waktu'     => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Failed to log activity: ' . $e->getMessage());
        }
    }
}