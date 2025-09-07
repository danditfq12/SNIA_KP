<?php

namespace App\Controllers\Role\Admin;

use App\Controllers\BaseController;
use App\Models\DokumenModel;
use App\Models\EventModel;
use App\Models\UserModel;
use App\Models\PembayaranModel;
use App\Models\AbsensiModel;
use App\Models\LogAktivitasModel;
use CodeIgniter\HTTP\ResponseInterface;

class Dokumen extends BaseController
{
    protected $dokumenModel;
    protected $eventModel;
    protected $userModel;
    protected $pembayaranModel;
    protected $absensiModel;
    protected $logModel;

    public function __construct()
    {
        $this->dokumenModel = new DokumenModel();
        $this->eventModel = new EventModel();
        $this->userModel = new UserModel();
        $this->pembayaranModel = new PembayaranModel();
        $this->absensiModel = new AbsensiModel();
        $this->logModel = new LogAktivitasModel();
    }

    public function index()
    {
        // Get all events for filter
        $events = $this->eventModel->findAll();
        
        // Get filter parameters
        $eventId = $this->request->getGet('event_id');
        $tipe = $this->request->getGet('tipe');

        // Build query
        $builder = $this->dokumenModel->select('
            dokumen.*,
            users.nama_lengkap,
            users.email,
            users.role,
            events.title as event_title
        ')
        ->join('users', 'users.id_user = dokumen.id_user', 'left')
        ->join('events', 'events.id = dokumen.event_id', 'left')
        ->orderBy('dokumen.uploaded_at', 'DESC');

        if ($eventId) {
            $builder->where('dokumen.event_id', $eventId);
        }

        if ($tipe) {
            $builder->where('dokumen.tipe', $tipe);
        }

        $documents = $builder->findAll();

        // Get statistics - Fixed to reset builder for each query
        $stats = [
            'total_documents' => $this->dokumenModel->countAllResults(false),
            'loa_count' => $this->dokumenModel->where('tipe', 'loa')->countAllResults(false),
            'sertifikat_count' => $this->dokumenModel->where('tipe', 'sertifikat')->countAllResults(false),
            'recent_uploads' => $this->dokumenModel->where('uploaded_at >=', date('Y-m-d', strtotime('-7 days')))->countAllResults(false)
        ];

        $data = [
            'title' => 'Manajemen Dokumen',
            'documents' => $documents,
            'events' => $events,
            'stats' => $stats,
            'current_event' => $eventId,
            'current_tipe' => $tipe
        ];

        return view('role/admin/dokumen/index', $data);
    }

    public function uploadLoa($eventId)
    {
        $event = $this->eventModel->find($eventId);
        if (!$event) {
            return redirect()->back()->with('error', 'Event tidak ditemukan');
        }

        $validation = \Config\Services::validation();
        $rules = [
            'user_id' => 'required|integer',
            'loa_file' => 'uploaded[loa_file]|max_size[loa_file,5120]|ext_in[loa_file,pdf,doc,docx]'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'File tidak valid: ' . implode(', ', $validation->getErrors()));
        }

        $userId = $this->request->getPost('user_id');
        $file = $this->request->getFile('loa_file');

        // Check if user is verified for this event
        $payment = $this->pembayaranModel
            ->where('id_user', $userId)
            ->where('event_id', $eventId)
            ->where('status', 'verified')
            ->first();

        if (!$payment) {
            return redirect()->back()
                ->with('error', 'User belum melakukan pembayaran terverifikasi untuk event ini');
        }

        // Generate unique filename
        $fileName = 'LOA_' . $eventId . '_' . $userId . '_' . time() . '.' . $file->getExtension();
        $uploadPath = WRITEPATH . 'uploads/loa/' . $eventId . '/';

        // Create directory if not exists
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
            
            // Create .htaccess for security
            $htaccessContent = "Options -Indexes\n";
            $htaccessContent .= "Order deny,allow\n";
            $htaccessContent .= "Deny from all\n";
            $htaccessContent .= "<Files ~ \"\\.(pdf|doc|docx)$\">\n";
            $htaccessContent .= "    Order allow,deny\n";
            $htaccessContent .= "    Allow from all\n";
            $htaccessContent .= "</Files>\n";
            
            file_put_contents($uploadPath . '.htaccess', $htaccessContent);
            file_put_contents($uploadPath . 'index.html', '<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body><h1>Directory access is forbidden.</h1></body></html>');
        }

        try {
            // Move file
            $file->move($uploadPath, $fileName);

            // Save to database
            $data = [
                'id_user' => $userId,
                'event_id' => $eventId,
                'tipe' => 'loa',
                'file_path' => 'uploads/loa/' . $eventId . '/' . $fileName,
                'syarat' => 'Letter of Acceptance untuk event: ' . $event['title'],
                'uploaded_at' => date('Y-m-d H:i:s')
            ];

            $this->dokumenModel->insert($data);

            // Log activity
            $this->logActivity(
                'Upload LOA untuk user ID ' . $userId . ' pada event ' . $event['title']
            );

            return redirect()->back()
                ->with('success', 'LOA berhasil diupload');

        } catch (\Exception $e) {
            log_message('error', 'Failed to upload LOA: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Gagal mengupload LOA: ' . $e->getMessage());
        }
    }

    public function uploadSertifikat($eventId)
    {
        $event = $this->eventModel->find($eventId);
        if (!$event) {
            return redirect()->back()->with('error', 'Event tidak ditemukan');
        }

        $validation = \Config\Services::validation();
        $rules = [
            'user_id' => 'required|integer',
            'sertifikat_file' => 'uploaded[sertifikat_file]|max_size[sertifikat_file,5120]|ext_in[sertifikat_file,pdf,jpg,jpeg,png]'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'File tidak valid: ' . implode(', ', $validation->getErrors()));
        }

        $userId = $this->request->getPost('user_id');
        $file = $this->request->getFile('sertifikat_file');

        // Check if user attended the event
        $attended = $this->absensiModel
            ->where('id_user', $userId)
            ->where('event_id', $eventId)
            ->where('status', 'hadir')
            ->first();

        if (!$attended) {
            return redirect()->back()
                ->with('error', 'User belum hadir pada event ini');
        }

        // Generate unique filename
        $fileName = 'SERTIFIKAT_' . $eventId . '_' . $userId . '_' . time() . '.' . $file->getExtension();
        $uploadPath = WRITEPATH . 'uploads/sertifikat/' . $eventId . '/';

        // Create directory if not exists
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
            
            // Create .htaccess for security
            $htaccessContent = "Options -Indexes\n";
            $htaccessContent .= "Order deny,allow\n";
            $htaccessContent .= "Deny from all\n";
            $htaccessContent .= "<Files ~ \"\\.(pdf|jpg|jpeg|png)$\">\n";
            $htaccessContent .= "    Order allow,deny\n";
            $htaccessContent .= "    Allow from all\n";
            $htaccessContent .= "</Files>\n";
            
            file_put_contents($uploadPath . '.htaccess', $htaccessContent);
            file_put_contents($uploadPath . 'index.html', '<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body><h1>Directory access is forbidden.</h1></body></html>');
        }

        try {
            // Move file
            $file->move($uploadPath, $fileName);

            // Save to database
            $data = [
                'id_user' => $userId,
                'event_id' => $eventId,
                'tipe' => 'sertifikat',
                'file_path' => 'uploads/sertifikat/' . $eventId . '/' . $fileName,
                'syarat' => 'Sertifikat kehadiran untuk event: ' . $event['title'],
                'uploaded_at' => date('Y-m-d H:i:s')
            ];

            $this->dokumenModel->insert($data);

            // Log activity
            $this->logActivity(
                'Upload sertifikat untuk user ID ' . $userId . ' pada event ' . $event['title']
            );

            return redirect()->back()
                ->with('success', 'Sertifikat berhasil diupload');

        } catch (\Exception $e) {
            log_message('error', 'Failed to upload sertifikat: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Gagal mengupload sertifikat: ' . $e->getMessage());
        }
    }

    public function generateBulkLOA()
    {
        $eventId = $this->request->getPost('event_id');
        $event = $this->eventModel->find($eventId);
        
        if (!$event) {
            return redirect()->back()->with('error', 'Event tidak ditemukan');
        }

        // Get all verified presenters for this event
        $presenters = $this->pembayaranModel
            ->select('pembayaran.id_user, users.nama_lengkap, users.email')
            ->join('users', 'users.id_user = pembayaran.id_user')
            ->where('pembayaran.event_id', $eventId)
            ->where('pembayaran.status', 'verified')
            ->where('users.role', 'presenter')
            ->findAll();

        if (empty($presenters)) {
            return redirect()->back()
                ->with('error', 'Tidak ada presenter terverifikasi untuk event ini');
        }

        $uploadPath = WRITEPATH . 'uploads/loa/' . $eventId . '/';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
            
            // Create .htaccess for security
            $htaccessContent = "Options -Indexes\n";
            $htaccessContent .= "Order deny,allow\n";
            $htaccessContent .= "Deny from all\n";
            $htaccessContent .= "<Files ~ \"\\.(pdf|doc|docx|html)$\">\n";
            $htaccessContent .= "    Order allow,deny\n";
            $htaccessContent .= "    Allow from all\n";
            $htaccessContent .= "</Files>\n";
            
            file_put_contents($uploadPath . '.htaccess', $htaccessContent);
            file_put_contents($uploadPath . 'index.html', '<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body><h1>Directory access is forbidden.</h1></body></html>');
        }

        $successCount = 0;
        $errors = [];

        foreach ($presenters as $presenter) {
            try {
                // Check if LOA already exists
                $existingLOA = $this->dokumenModel
                    ->where('id_user', $presenter['id_user'])
                    ->where('event_id', $eventId)
                    ->where('tipe', 'loa')
                    ->first();

                if ($existingLOA) {
                    continue; // Skip if already exists
                }

                // Generate LOA content
                $loaContent = $this->generateLOAContent($event, $presenter);
                $fileName = 'LOA_' . $eventId . '_' . $presenter['id_user'] . '_' . time() . '.html';

                // Save as HTML file
                if (file_put_contents($uploadPath . $fileName, $loaContent)) {
                    // Save to database
                    $data = [
                        'id_user' => $presenter['id_user'],
                        'event_id' => $eventId,
                        'tipe' => 'loa',
                        'file_path' => 'uploads/loa/' . $eventId . '/' . $fileName,
                        'syarat' => 'Letter of Acceptance untuk event: ' . $event['title'],
                        'uploaded_at' => date('Y-m-d H:i:s')
                    ];

                    $this->dokumenModel->insert($data);
                    $successCount++;
                } else {
                    $errors[] = 'Gagal generate LOA untuk ' . $presenter['nama_lengkap'];
                }
            } catch (\Exception $e) {
                $errors[] = 'Error untuk ' . $presenter['nama_lengkap'] . ': ' . $e->getMessage();
            }
        }

        // Log activity
        $this->logActivity(
            'Generate bulk LOA untuk event ' . $event['title'] . ' - ' . $successCount . ' berhasil'
        );

        $message = "LOA berhasil di-generate untuk {$successCount} presenter";
        if (!empty($errors)) {
            $message .= '. Errors: ' . implode(', ', array_slice($errors, 0, 3));
            if (count($errors) > 3) {
                $message .= ' dan ' . (count($errors) - 3) . ' error lainnya';
            }
        }

        return redirect()->back()->with('success', $message);
    }

    public function generateBulkSertifikat()
    {
        $eventId = $this->request->getPost('event_id');
        $event = $this->eventModel->find($eventId);
        
        if (!$event) {
            return redirect()->back()->with('error', 'Event tidak ditemukan');
        }

        // Get all attendees for this event
        $attendees = $this->absensiModel
            ->select('absensi.id_user, users.nama_lengkap, users.email, users.role')
            ->join('users', 'users.id_user = absensi.id_user')
            ->where('absensi.event_id', $eventId)
            ->where('absensi.status', 'hadir')
            ->groupBy('absensi.id_user')
            ->findAll();

        if (empty($attendees)) {
            return redirect()->back()
                ->with('error', 'Tidak ada peserta yang hadir untuk event ini');
        }

        $uploadPath = WRITEPATH . 'uploads/sertifikat/' . $eventId . '/';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
            
            // Create .htaccess for security
            $htaccessContent = "Options -Indexes\n";
            $htaccessContent .= "Order deny,allow\n";
            $htaccessContent .= "Deny from all\n";
            $htaccessContent .= "<Files ~ \"\\.(pdf|jpg|jpeg|png|html)$\">\n";
            $htaccessContent .= "    Order allow,deny\n";
            $htaccessContent .= "    Allow from all\n";
            $htaccessContent .= "</Files>\n";
            
            file_put_contents($uploadPath . '.htaccess', $htaccessContent);
            file_put_contents($uploadPath . 'index.html', '<!DOCTYPE html><html><head><title>403 Forbidden</title></head><body><h1>Directory access is forbidden.</h1></body></html>');
        }

        $successCount = 0;
        $errors = [];

        foreach ($attendees as $attendee) {
            try {
                // Check if certificate already exists
                $existingCert = $this->dokumenModel
                    ->where('id_user', $attendee['id_user'])
                    ->where('event_id', $eventId)
                    ->where('tipe', 'sertifikat')
                    ->first();

                if ($existingCert) {
                    continue; // Skip if already exists
                }

                // Generate certificate content
                $certificateContent = $this->generateCertificateContent($event, $attendee);
                $fileName = 'SERTIFIKAT_' . $eventId . '_' . $attendee['id_user'] . '_' . time() . '.html';

                // Save as HTML file
                if (file_put_contents($uploadPath . $fileName, $certificateContent)) {
                    // Save to database
                    $data = [
                        'id_user' => $attendee['id_user'],
                        'event_id' => $eventId,
                        'tipe' => 'sertifikat',
                        'file_path' => 'uploads/sertifikat/' . $eventId . '/' . $fileName,
                        'syarat' => 'Sertifikat kehadiran untuk event: ' . $event['title'],
                        'uploaded_at' => date('Y-m-d H:i:s')
                    ];

                    $this->dokumenModel->insert($data);
                    $successCount++;
                } else {
                    $errors[] = 'Gagal generate sertifikat untuk ' . $attendee['nama_lengkap'];
                }
            } catch (\Exception $e) {
                $errors[] = 'Error untuk ' . $attendee['nama_lengkap'] . ': ' . $e->getMessage();
            }
        }

        // Log activity
        $this->logActivity(
            'Generate bulk sertifikat untuk event ' . $event['title'] . ' - ' . $successCount . ' berhasil'
        );

        $message = "Sertifikat berhasil di-generate untuk {$successCount} peserta";
        if (!empty($errors)) {
            $message .= '. Errors: ' . implode(', ', array_slice($errors, 0, 3));
            if (count($errors) > 3) {
                $message .= ' dan ' . (count($errors) - 3) . ' error lainnya';
            }
        }

        return redirect()->back()->with('success', $message);
    }

    public function download($id)
    {
        $document = $this->dokumenModel->find($id);
        
        if (!$document) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Dokumen tidak ditemukan');
        }

        $filePath = WRITEPATH . $document['file_path'];
        
        if (!file_exists($filePath)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('File tidak ditemukan');
        }

        // Log download activity
        $this->logActivity(
            'Download dokumen: ' . basename($document['file_path'])
        );

        return $this->response->download($filePath, null);
    }

    public function delete($id)
    {
        $document = $this->dokumenModel->find($id);
        
        if (!$document) {
            return redirect()->back()->with('error', 'Dokumen tidak ditemukan');
        }

        try {
            // Delete file from storage
            $filePath = WRITEPATH . $document['file_path'];
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            // Delete from database
            $this->dokumenModel->delete($id);

            // Log activity
            $this->logActivity(
                'Hapus dokumen: ' . basename($document['file_path'])
            );

            return redirect()->back()
                ->with('success', 'Dokumen berhasil dihapus');

        } catch (\Exception $e) {
            log_message('error', 'Failed to delete document: ' . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Gagal menghapus dokumen');
        }
    }

    public function getVerifiedPresenters($eventId)
    {
        $presenters = $this->pembayaranModel
            ->select('users.id_user, users.nama_lengkap, users.email')
            ->join('users', 'users.id_user = pembayaran.id_user')
            ->where('pembayaran.event_id', $eventId)
            ->where('pembayaran.status', 'verified')
            ->where('users.role', 'presenter')
            ->findAll();

        return $this->response->setJSON($presenters);
    }

    public function getAttendees($eventId)
    {
        $attendees = $this->absensiModel
            ->select('users.id_user, users.nama_lengkap, users.email, users.role')
            ->join('users', 'users.id_user = absensi.id_user')
            ->where('absensi.event_id', $eventId)
            ->where('absensi.status', 'hadir')
            ->groupBy('users.id_user')
            ->findAll();

        return $this->response->setJSON($attendees);
    }

    private function generateLOAContent($event, $presenter)
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Letter of Acceptance</title>
            <meta charset='UTF-8'>
            <style>
                body { 
                    font-family: 'Times New Roman', serif; 
                    margin: 40px; 
                    line-height: 1.6;
                    color: #333;
                }
                .header { 
                    text-align: center; 
                    margin-bottom: 40px; 
                    border-bottom: 2px solid #2563eb;
                    padding-bottom: 20px;
                }
                .header h1 {
                    color: #2563eb;
                    font-size: 2.5rem;
                    margin-bottom: 10px;
                }
                .content { 
                    line-height: 1.8; 
                    font-size: 14px;
                }
                .event-title {
                    text-align: center;
                    color: #1e40af;
                    font-size: 1.5rem;
                    margin: 30px 0;
                    padding: 15px;
                    border: 1px solid #2563eb;
                    background-color: #f8fafc;
                }
                .details {
                    margin: 20px 0;
                    padding: 15px;
                    background-color: #f9fafb;
                    border-left: 4px solid #2563eb;
                }
                .signature { 
                    margin-top: 60px; 
                    text-align: right; 
                    font-style: italic;
                }
                .footer {
                    margin-top: 40px;
                    text-align: center;
                    font-size: 12px;
                    color: #666;
                }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>LETTER OF ACCEPTANCE</h1>
                <p>SNIA International Conference</p>
            </div>
            <div class='content'>
                <p>Dear <strong>{$presenter['nama_lengkap']}</strong>,</p>
                
                <p>We are pleased to inform you that your abstract has been <strong>accepted</strong> for presentation at:</p>
                
                <div class='event-title'>{$event['title']}</div>
                
                <div class='details'>
                    <p><strong>Date:</strong> " . date('d F Y', strtotime($event['event_date'])) . "</p>
                    <p><strong>Time:</strong> " . ($event['event_time'] ? date('H:i', strtotime($event['event_time'])) : 'To be announced') . "</p>
                    <p><strong>Format:</strong> " . ucfirst($event['format']) . "</p>
                    " . ($event['location'] ? "<p><strong>Location:</strong> {$event['location']}</p>" : "") . "
                    " . ($event['zoom_link'] ? "<p><strong>Zoom Link:</strong> {$event['zoom_link']}</p>" : "") . "
                </div>
                
                <p>Your presentation has been carefully reviewed by our scientific committee and meets the high standards of our conference. We look forward to your valuable contribution to the scientific community.</p>
                
                <p><strong>Please confirm your attendance by replying to this letter no later than one week before the event.</strong></p>
                
                <p>We anticipate an excellent presentation and your active participation in the conference discussions.</p>
            </div>
            <div class='signature'>
                <p>Best regards,</p>
                <p><strong>Conference Committee</strong><br>
                <strong>SNIA International Conference</strong></p>
            </div>
            <div class='footer'>
                <p>Generated on " . date('d F Y, H:i') . " | Document ID: LOA-{$event['id']}-{$presenter['id_user']}</p>
            </div>
        </body>
        </html>
        ";
    }

    private function generateCertificateContent($event, $attendee)
    {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <title>Certificate of Attendance</title>
            <meta charset='UTF-8'>
            <style>
                @page { 
                    margin: 0; 
                    size: A4 landscape; 
                }
                body { 
                    font-family: 'Times New Roman', serif; 
                    margin: 0; 
                    padding: 50px; 
                    text-align: center; 
                    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
                    min-height: 100vh;
                    box-sizing: border-box;
                }
                .certificate { 
                    border: 8px solid #2563eb; 
                    padding: 60px; 
                    background: white;
                    border-radius: 15px;
                    box-shadow: 0 10px 30px rgba(0,0,0,0.1);
                    position: relative;
                    max-width: 800px;
                    margin: 0 auto;
                }
                .certificate::before {
                    content: '';
                    position: absolute;
                    top: 20px;
                    left: 20px;
                    right: 20px;
                    bottom: 20px;
                    border: 2px solid #60a5fa;
                    border-radius: 8px;
                }
                .header h1 { 
                    font-size: 3.5rem; 
                    color: #2563eb; 
                    margin-bottom: 20px;
                    text-shadow: 2px 2px 4px rgba(0,0,0,0.1);
                }
                .subtitle {
                    font-size: 1.2rem;
                    color: #64748b;
                    margin-bottom: 40px;
                }
                .participant { 
                    font-size: 3rem; 
                    font-weight: bold; 
                    color: #1e40af; 
                    margin: 40px 0;
                    text-decoration: underline;
                    text-decoration-color: #2563eb;
                }
                .event-title {
                    font-size: 2.2rem; 
                    margin: 30px 0;
                    color: #374151;
                    font-weight: bold;
                }
                .details { 
                    font-size: 1.3rem; 
                    margin: 20px 0;
                    color: #4b5563;
                }
                .role-badge {
                    display: inline-block;
                    background: #2563eb;
                    color: white;
                    padding: 8px 20px;
                    border-radius: 25px;
                    font-size: 1.1rem;
                    margin: 20px 0;
                }
                .footer { 
                    margin-top: 60px; 
                    font-size: 1rem;
                    border-top: 2px solid #e5e7eb;
                    padding-top: 30px;
                }
                .signatures {
                    display: flex;
                    justify-content: space-between;
                    margin-top: 40px;
                    padding: 0 50px;
                }
                .signature-block {
                    text-align: center;
                    width: 200px;
                }
                .signature-line {
                    border-top: 2px solid #374151;
                    margin-bottom: 10px;
                }
            </style>
        </head>
        <body>
            <div class='certificate'>
                <div class='header'>
                    <h1>CERTIFICATE OF ATTENDANCE</h1>
                    <p class='subtitle'>SNIA International Conference</p>
                </div>
                
                <p class='details'>This is to certify that</p>
                
                <div class='participant'>{$attendee['nama_lengkap']}</div>
                
                <p class='details'>has successfully attended</p>
                
                <div class='event-title'>{$event['title']}</div>
                
                <p class='details'>held on <strong>" . date('d F Y', strtotime($event['event_date'])) . "</strong></p>
                
                <div class='role-badge'>" . ucfirst($attendee['role']) . "</div>
                
                <div class='footer'>
                    <p><strong>Date of Issue:</strong> " . date('d F Y') . "</p>
                    
                    <div class='signatures'>
                        <div class='signature-block'>
                            <div class='signature-line'></div>
                            <p><strong>Conference Chair</strong></p>
                        </div>
                        <div class='signature-block'>
                            <div class='signature-line'></div>
                            <p><strong>Academic Director</strong></p>
                        </div>
                    </div>
                    
                    <p style='margin-top: 30px; font-size: 0.9rem; color: #6b7280;'>
                        Certificate ID: CERT-{$event['id']}-{$attendee['id_user']}-" . date('Ymd') . "
                    </p>
                </div>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Log user activity with improved session handling
     */
    private function logActivity($activity)
    {
        try {
            // Try different session structures to get user ID
            $userId = $this->getCurrentUserId();
            
            if (!$userId) {
                log_message('warning', 'Cannot log activity: User ID is null. Activity: ' . $activity);
                return false;
            }
            
            $this->logModel->insert([
                'id_user' => $userId,
                'aktivitas' => $activity,
                'waktu' => date('Y-m-d H:i:s')
            ]);
            
            return true;
        } catch (\Exception $e) {
            log_message('error', 'Failed to log activity: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get current user ID from session with multiple fallbacks
     */
    private function getCurrentUserId()
    {
        // Try different possible session structures
        $sessionData = session()->get();
        
        // Common session structures in CodeIgniter applications
        $possiblePaths = [
            'id_user',
            'user_id', 
            'user.id_user',
            'user.id',
            'logged_user.id_user',
            'logged_user.id',
            'auth.id_user',
            'auth.id'
        ];
        
        foreach ($possiblePaths as $path) {
            $value = $this->getNestedSessionValue($sessionData, $path);
            if ($value && is_numeric($value)) {
                return (int) $value;
            }
        }
        
        // If no user ID found, log session structure for debugging (only in development)
        if (ENVIRONMENT === 'development') {
            log_message('debug', 'Session structure: ' . json_encode($sessionData));
        }
        
        return null;
    }

    /**
     * Get nested value from session array using dot notation
     */
    private function getNestedSessionValue($array, $path)
    {
        $keys = explode('.', $path);
        $value = $array;
        
        foreach ($keys as $key) {
            if (is_array($value) && array_key_exists($key, $value)) {
                $value = $value[$key];
            } else {
                return null;
            }
        }
        
        return $value;
    }

    /**
     * Debug session structure (call this temporarily to see session structure)
     */
    public function debugSession()
    {
        if (ENVIRONMENT !== 'development') {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Debug mode only available in development');
        }
        
        echo "<pre>";
        echo "Session Data:\n";
        var_dump(session()->get());
        echo "\n\nCurrent User ID: " . $this->getCurrentUserId();
        echo "</pre>";
        die();
    }
}