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
        $this->dokumenModel = new DokumenModel();
        $this->eventModel = new EventModel();
        $this->userModel = new UserModel();
        $this->pembayaranModel = new PembayaranModel();
        $this->absensiModel = new AbsensiModel();
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        // Get filter parameters
        $eventId = $this->request->getGet('event_id');
        $tipe = $this->request->getGet('tipe');

        // Get all documents with filters
        $documents = $this->dokumenModel->getDokumenWithUser($tipe, $eventId);

        // Get all events for filter dropdown
        $events = $this->eventModel->where('is_active', true)
                                  ->orderBy('event_date', 'DESC')
                                  ->findAll();

        // Calculate statistics
        $stats = $this->getDocumentStatistics();

        $data = [
            'title' => 'Manajemen Dokumen',
            'documents' => $documents,
            'events' => $events,
            'current_event' => $eventId,
            'current_tipe' => $tipe,
            'stats' => $stats
        ];

        return view('role/admin/dokumen/index', $data);
    }

    public function uploadLoa($eventId)
    {
        $validation = \Config\Services::validation();
        
        $rules = [
            'user_id' => 'required|integer',
            'loa_file' => [
                'uploaded[loa_file]',
                'max_size[loa_file,5120]', // 5MB
                'ext_in[loa_file,pdf,doc,docx]'
            ]
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->with('error', 'Validasi gagal: ' . implode(', ', $validation->getErrors()));
        }

        $userId = $this->request->getPost('user_id');
        $file = $this->request->getFile('loa_file');

        // Validate event exists
        $event = $this->eventModel->find($eventId);
        if (!$event) {
            return redirect()->back()->with('error', 'Event tidak ditemukan.');
        }

        // Validate user exists and has verified payment
        $user = $this->userModel->find($userId);
        if (!$user) {
            return redirect()->back()->with('error', 'User tidak ditemukan.');
        }

        $verifiedPayment = $this->pembayaranModel
            ->where('id_user', $userId)
            ->where('event_id', $eventId)
            ->where('status', 'verified')
            ->first();

        if (!$verifiedPayment) {
            return redirect()->back()->with('error', 'User belum memiliki pembayaran yang terverifikasi untuk event ini.');
        }

        // Check if LOA already exists
        $existingLoa = $this->dokumenModel->hasUserDocument($userId, $eventId, 'loa');
        if ($existingLoa) {
            return redirect()->back()->with('error', 'LOA untuk user ini sudah ada.');
        }

        $this->db->transStart();

        try {
            // Create upload directory
            $uploadPath = WRITEPATH . 'uploads/loa/';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            // Generate unique filename
            $fileName = 'LOA_' . $eventId . '_' . $userId . '_' . time() . '.' . $file->getExtension();
            
            // Move file
            if (!$file->move($uploadPath, $fileName)) {
                throw new \Exception('Gagal menyimpan file: ' . $file->getErrorString());
            }

            // Save to database
            $documentData = [
                'id_user' => $userId,
                'event_id' => $eventId,
                'tipe' => 'loa',
                'file_path' => $fileName,
                'syarat' => 'Letter of Acceptance',
                'uploaded_at' => date('Y-m-d H:i:s')
            ];

            if (!$this->dokumenModel->insert($documentData)) {
                throw new \Exception('Gagal menyimpan data dokumen: ' . implode(', ', $this->dokumenModel->errors()));
            }

            // Log activity
            $eventTitle = $event['title'] ?? 'Unknown Event';
            $this->logActivity(session('id_user'), "Uploaded LOA for {$user['nama_lengkap']} in event: {$eventTitle}");

            $this->db->transComplete();

            if ($this->db->transStatus() === FALSE) {
                throw new \Exception('Transaction failed');
            }

            return redirect()->to('admin/dokumen')->with('success', 'LOA berhasil diupload!');

        } catch (\Exception $e) {
            $this->db->transRollback();
            
            // Clean up uploaded file if exists
            if (isset($fileName) && file_exists($uploadPath . $fileName)) {
                unlink($uploadPath . $fileName);
            }
            
            log_message('error', 'LOA upload error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function uploadSertifikat($eventId)
    {
        $validation = \Config\Services::validation();
        
        $rules = [
            'user_id' => 'required|integer',
            'sertifikat_file' => [
                'uploaded[sertifikat_file]',
                'max_size[sertifikat_file,5120]', // 5MB
                'ext_in[sertifikat_file,pdf,jpg,jpeg,png]'
            ]
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->with('error', 'Validasi gagal: ' . implode(', ', $validation->getErrors()));
        }

        $userId = $this->request->getPost('user_id');
        $file = $this->request->getFile('sertifikat_file');

        // Validate event exists
        $event = $this->eventModel->find($eventId);
        if (!$event) {
            return redirect()->back()->with('error', 'Event tidak ditemukan.');
        }

        // Validate user exists and has attended
        $user = $this->userModel->find($userId);
        if (!$user) {
            return redirect()->back()->with('error', 'User tidak ditemukan.');
        }

        $attendance = $this->absensiModel
            ->where('id_user', $userId)
            ->where('event_id', $eventId)
            ->where('status', 'hadir')
            ->first();

        if (!$attendance) {
            return redirect()->back()->with('error', 'User belum hadir pada event ini.');
        }

        // Check if certificate already exists
        $existingCert = $this->dokumenModel->hasUserDocument($userId, $eventId, 'sertifikat');
        if ($existingCert) {
            return redirect()->back()->with('error', 'Sertifikat untuk user ini sudah ada.');
        }

        $this->db->transStart();

        try {
            // Create upload directory
            $uploadPath = WRITEPATH . 'uploads/sertifikat/';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            // Generate unique filename
            $fileName = 'SERTIFIKAT_' . $eventId . '_' . $userId . '_' . time() . '.' . $file->getExtension();
            
            // Move file
            if (!$file->move($uploadPath, $fileName)) {
                throw new \Exception('Gagal menyimpan file: ' . $file->getErrorString());
            }

            // Save to database
            $documentData = [
                'id_user' => $userId,
                'event_id' => $eventId,
                'tipe' => 'sertifikat',
                'file_path' => $fileName,
                'syarat' => 'Certificate of Participation',
                'uploaded_at' => date('Y-m-d H:i:s')
            ];

            if (!$this->dokumenModel->insert($documentData)) {
                throw new \Exception('Gagal menyimpan data dokumen: ' . implode(', ', $this->dokumenModel->errors()));
            }

            // Log activity
            $eventTitle = $event['title'] ?? 'Unknown Event';
            $this->logActivity(session('id_user'), "Uploaded Certificate for {$user['nama_lengkap']} in event: {$eventTitle}");

            $this->db->transComplete();

            if ($this->db->transStatus() === FALSE) {
                throw new \Exception('Transaction failed');
            }

            return redirect()->to('admin/dokumen')->with('success', 'Sertifikat berhasil diupload!');

        } catch (\Exception $e) {
            $this->db->transRollback();
            
            // Clean up uploaded file if exists
            if (isset($fileName) && file_exists($uploadPath . $fileName)) {
                unlink($uploadPath . $fileName);
            }
            
            log_message('error', 'Certificate upload error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function download($idDokumen)
    {
        $document = $this->dokumenModel->getOneWithDetails($idDokumen);
        
        if (!$document) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Dokumen tidak ditemukan.');
        }

        // Determine file path based on document type
        $basePath = WRITEPATH . 'uploads/' . $document['tipe'] . '/';
        $filePath = $basePath . $document['file_path'];

        if (!file_exists($filePath)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('File tidak ditemukan: ' . $filePath);
        }

        // Generate download filename
        $eventTitle = $document['event_title'] ? preg_replace('/[^A-Za-z0-9_-]/', '_', $document['event_title']) : 'Event';
        $userName = preg_replace('/[^A-Za-z0-9_-]/', '_', $document['nama_lengkap'] ?? 'Unknown');
        $extension = pathinfo($document['file_path'], PATHINFO_EXTENSION);
        
        $downloadName = strtoupper($document['tipe']) . '_' . $eventTitle . '_' . $userName . '.' . $extension;
        
        // Log download activity
        $eventTitleForLog = $document['event_title'] ?? 'Unknown Event';
        $userNameForLog = $document['nama_lengkap'] ?? 'Unknown User';
        $this->logActivity(session('id_user'), "Downloaded {$document['tipe']} for {$userNameForLog} from event: {$eventTitleForLog}");

        return $this->response->download($filePath, null)->setFileName($downloadName);
    }

    public function delete($idDokumen)
    {
        $document = $this->dokumenModel->find($idDokumen);
        
        if (!$document) {
            return redirect()->back()->with('error', 'Dokumen tidak ditemukan.');
        }

        $this->db->transStart();

        try {
            // Get user info for logging
            $user = $this->userModel->find($document['id_user']);
            $event = $this->eventModel->find($document['event_id']);

            // Delete file from filesystem
            $filePath = WRITEPATH . 'uploads/' . $document['tipe'] . '/' . $document['file_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            // Delete from database
            if (!$this->dokumenModel->delete($idDokumen)) {
                throw new \Exception('Gagal menghapus data dokumen');
            }

            // Log activity
            $userName = $user ? $user['nama_lengkap'] : 'Unknown User';
            $eventTitle = $event ? ($event['title'] ?? 'Unknown Event') : 'Unknown Event';
            $this->logActivity(session('id_user'), "Deleted {$document['tipe']} for {$userName} from event: {$eventTitle}");

            $this->db->transComplete();

            if ($this->db->transStatus() === FALSE) {
                throw new \Exception('Transaction failed');
            }

            return redirect()->back()->with('success', 'Dokumen berhasil dihapus!');

        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Document deletion error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function generateBulkLOA()
    {
        $eventId = $this->request->getPost('event_id');
        
        if (!$eventId) {
            return redirect()->back()->with('error', 'Event ID diperlukan.');
        }

        $event = $this->eventModel->find($eventId);
        if (!$event) {
            return redirect()->back()->with('error', 'Event tidak ditemukan.');
        }

        // Get eligible presenters for LOA
        $eligiblePresenters = $this->dokumenModel->getEligiblePresentersForLOA($eventId);
        
        if (empty($eligiblePresenters)) {
            return redirect()->back()->with('error', 'Tidak ada presenter yang memenuhi syarat untuk LOA.');
        }

        $this->db->transStart();

        try {
            $successCount = 0;
            $uploadPath = WRITEPATH . 'uploads/loa/';
            
            // Ensure directory exists
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            foreach ($eligiblePresenters as $presenter) {
                // Generate PDF LOA
                $pdfPath = $this->generateLOAPDF($presenter, $event, $uploadPath);
                
                if ($pdfPath) {
                    // Save to database
                    $documentData = [
                        'id_user' => $presenter['id_user'],
                        'event_id' => $eventId,
                        'tipe' => 'loa',
                        'file_path' => basename($pdfPath),
                        'syarat' => 'Letter of Acceptance - Generated',
                        'uploaded_at' => date('Y-m-d H:i:s')
                    ];

                    if ($this->dokumenModel->insert($documentData)) {
                        $successCount++;
                    }
                }
            }

            // Log activity
            $eventTitle = $event['title'] ?? 'Unknown Event';
            $this->logActivity(session('id_user'), "Generated {$successCount} LOA documents for event: {$eventTitle}");

            $this->db->transComplete();

            if ($this->db->transStatus() === FALSE) {
                throw new \Exception('Transaction failed');
            }

            return redirect()->back()->with('success', "Berhasil generate {$successCount} LOA dari " . count($eligiblePresenters) . " presenter!");

        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Bulk LOA generation error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function generateBulkSertifikat()
    {
        $eventId = $this->request->getPost('event_id');
        
        if (!$eventId) {
            return redirect()->back()->with('error', 'Event ID diperlukan.');
        }

        $event = $this->eventModel->find($eventId);
        if (!$event) {
            return redirect()->back()->with('error', 'Event tidak ditemukan.');
        }

        // Get eligible users for certificate
        $eligibleUsers = $this->dokumenModel->getEligibleUsersForCertificate($eventId);
        
        if (empty($eligibleUsers)) {
            return redirect()->back()->with('error', 'Tidak ada peserta yang memenuhi syarat untuk sertifikat.');
        }

        $this->db->transStart();

        try {
            $successCount = 0;
            $uploadPath = WRITEPATH . 'uploads/sertifikat/';
            
            // Ensure directory exists
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            foreach ($eligibleUsers as $user) {
                // Generate PDF Certificate
                $pdfPath = $this->generateCertificatePDF($user, $event, $uploadPath);
                
                if ($pdfPath) {
                    // Save to database
                    $documentData = [
                        'id_user' => $user['id_user'],
                        'event_id' => $eventId,
                        'tipe' => 'sertifikat',
                        'file_path' => basename($pdfPath),
                        'syarat' => 'Certificate of Participation - Generated',
                        'uploaded_at' => date('Y-m-d H:i:s')
                    ];

                    if ($this->dokumenModel->insert($documentData)) {
                        $successCount++;
                    }
                }
            }

            // Log activity
            $eventTitle = $event['title'] ?? 'Unknown Event';
            $this->logActivity(session('id_user'), "Generated {$successCount} certificates for event: {$eventTitle}");

            $this->db->transComplete();

            if ($this->db->transStatus() === FALSE) {
                throw new \Exception('Transaction failed');
            }

            return redirect()->back()->with('success', "Berhasil generate {$successCount} sertifikat dari " . count($eligibleUsers) . " peserta!");

        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Bulk certificate generation error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    // AJAX endpoints for modals
    public function getVerifiedPresenters($eventId)
    {
        try {
            $presenters = $this->dokumenModel->getEligiblePresentersForLOA($eventId);
            return $this->response->setJSON([
                'status' => 'success',
                'data' => $presenters
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    public function getAttendees($eventId)
    {
        try {
            $attendees = $this->dokumenModel->getEligibleUsersForCertificate($eventId);
            return $this->response->setJSON([
                'status' => 'success',
                'data' => $attendees
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'status' => 'error',
                'message' => $e->getMessage()
            ])->setStatusCode(500);
        }
    }

    // Private helper methods
    private function getDocumentStatistics()
    {
        $totalDocuments = $this->dokumenModel->countAll();
        $loaCount = $this->dokumenModel->where('tipe', 'loa')->countAllResults();
        $sertifikatCount = $this->dokumenModel->where('tipe', 'sertifikat')->countAllResults();
        
        // Recent uploads (this week)
        $weekAgo = date('Y-m-d H:i:s', strtotime('-1 week'));
        $recentUploads = $this->dokumenModel
            ->where('uploaded_at >=', $weekAgo)
            ->countAllResults();

        return [
            'total_documents' => $totalDocuments,
            'loa_count' => $loaCount,
            'sertifikat_count' => $sertifikatCount,
            'recent_uploads' => $recentUploads
        ];
    }

    private function generateLOAPDF($presenter, $event, $uploadPath)
    {
        try {
            // Initialize mPDF
            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4',
                'orientation' => 'P',
                'margin_left' => 15,
                'margin_right' => 15,
                'margin_top' => 20,
                'margin_bottom' => 20,
            ]);

            // Set document properties
            $mpdf->SetTitle('Letter of Acceptance - ' . $presenter['nama_lengkap']);
            $mpdf->SetAuthor('SNIA Organization');

            // Generate HTML content
            $html = $this->getLOAHTML($presenter, $event);
            
            // Write HTML to PDF
            $mpdf->WriteHTML($html);

            // Generate filename
            $fileName = 'LOA_' . $event['id'] . '_' . $presenter['id_user'] . '_' . time() . '.pdf';
            $filePath = $uploadPath . $fileName;

            // Output PDF to file
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
            // Initialize mPDF with zero margins to prevent cutting
            $mpdf = new Mpdf([
                'mode' => 'utf-8',
                'format' => 'A4-L', // A4 Landscape format
                'orientation' => 'L',
                'margin_left' => 0,
                'margin_right' => 0, 
                'margin_top' => 0,
                'margin_bottom' => 0,
                'margin_header' => 0,
                'margin_footer' => 0,
                'default_font_size' => 12,
                'default_font' => 'dejavusans',
            ]);

            // Set document properties
            $mpdf->SetTitle('Certificate of Participation - ' . $user['nama_lengkap']);
            $mpdf->SetAuthor('SNIA Organization');
            $mpdf->SetSubject('Certificate of Participation');

            // Critical: Disable auto page break to prevent content splitting
            $mpdf->SetAutoPageBreak(false);

            // Generate HTML content with fixed CSS
            $html = $this->getCertificateHTML($user, $event);
            
            // Write HTML to PDF
            $mpdf->WriteHTML($html);

            // Generate filename
            $fileName = 'SERTIFIKAT_' . $event['id'] . '_' . $user['id_user'] . '_' . time() . '.pdf';
            $filePath = $uploadPath . $fileName;

            // Output PDF to file
            $mpdf->Output($filePath, 'F');

            return $filePath;

        } catch (\Exception $e) {
            log_message('error', 'Certificate PDF generation error: ' . $e->getMessage());
            return false;
        }
    }

    private function getLOAHTML($presenter, $event)
    {
        $eventDate = date('d F Y', strtotime($event['event_date'] ?? ''));
        $currentDate = date('d F Y');
        $eventTitle = $event['title'] ?? 'Event Title';
        $eventTime = $event['event_time'] ?? 'TBA';
        $eventFormat = $event['format'] ?? 'offline';
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
            @page { 
                size: A4 landscape; 
                margin: 0;
            }
            
            body { 
                font-family: "Times New Roman", serif; 
                margin: 0; 
                padding: 0;
                width: 297mm;  /* A4 landscape width */
                height: 210mm; /* A4 landscape height */
                overflow: hidden;
            }
            
            .certificate { 
                width: 100%;
                height: 100%;
                border: 12mm solid #2563eb; 
                padding: 0;
                text-align: center; 
                background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
                box-sizing: border-box;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                position: relative;
            }
            
            .content-wrapper {
                width: 100%;
                height: 100%;
                display: flex;
                flex-direction: column;
                justify-content: center;
                align-items: center;
                padding: 20mm;
                box-sizing: border-box;
            }
            
            .title { 
                font-size: 42px; 
                color: #2563eb; 
                margin-bottom: 8px; 
                font-weight: bold; 
                text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
                letter-spacing: 8px;
            }
            
            .subtitle { 
                font-size: 22px; 
                margin-bottom: 25px; 
                color: #1e40af;
                letter-spacing: 4px;
                font-weight: 600;
            }
            
            .decorative-line {
                width: 180px;
                height: 3px;
                background: linear-gradient(to right, #2563eb, #1e40af);
                margin: 15px auto;
            }
            
            .certify-text {
                font-size: 18px; 
                margin: 20px 0; 
                color: #374151;
                font-style: italic;
            }
            
            .recipient { 
                font-size: 32px; 
                color: #1e40af; 
                margin: 25px 0; 
                font-weight: bold;
                text-decoration: underline;
                text-decoration-color: #2563eb;
                text-decoration-thickness: 2px;
                max-width: 80%;
                word-wrap: break-word;
            }
            
            .participation-text {
                font-size: 18px; 
                margin: 20px 0;
                color: #374151;
                font-style: italic;
            }
            
            .event-title { 
                font-size: 24px; 
                margin: 20px 0; 
                font-style: italic;
                color: #374151;
                line-height: 1.3;
                font-weight: 600;
                max-width: 85%;
                word-wrap: break-word;
            }
            
            .date { 
                font-size: 16px; 
                margin-top: 25px;
                color: #6b7280;
                font-weight: 500;
            }
            
            .signature { 
                margin-top: 30px; 
                font-size: 16px;
                color: #374151;
                position: absolute;
                bottom: 30mm;
                right: 40mm;
                text-align: center;
            }
            
            .signature p { 
                margin: 3px 0; 
                line-height: 1.2;
            }
            
            .org-logo {
                position: absolute;
                top: 15mm;
                right: 15mm;
                font-size: 12px;
                color: #6b7280;
                font-style: italic;
            }
            
            /* Decorative elements */
            .corner-decoration {
                position: absolute;
                width: 30px;
                height: 30px;
                border: 2px solid #2563eb;
            }
            
            .corner-decoration.top-left {
                top: 20mm;
                left: 20mm;
                border-right: none;
                border-bottom: none;
            }
            
            .corner-decoration.top-right {
                top: 20mm;
                right: 20mm;
                border-left: none;
                border-bottom: none;
            }
            
            .corner-decoration.bottom-left {
                bottom: 20mm;
                left: 20mm;
                border-right: none;
                border-top: none;
            }
            
            .corner-decoration.bottom-right {
                bottom: 20mm;
                right: 20mm;
                border-left: none;
                border-top: none;
            }
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
                'id_user' => $userId,
                'aktivitas' => $activity,
                'waktu' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Failed to log activity: ' . $e->getMessage());
        }
    }
}