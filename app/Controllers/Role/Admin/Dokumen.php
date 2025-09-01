<?php

namespace App\Controllers\Role\Admin;

use App\Controllers\BaseController;
use App\Models\DokumenModel;
use App\Models\UserModel;
use App\Models\AbstrakModel;
use App\Models\PembayaranModel;
use App\Models\EventModel;

class Dokumen extends BaseController
{
    protected $dokumenModel;
    protected $userModel;
    protected $abstrakModel;
    protected $pembayaranModel;
    protected $eventModel;

    public function __construct()
    {
        $this->dokumenModel = new DokumenModel();
        $this->userModel = new UserModel();
        $this->abstrakModel = new AbstrakModel();
        $this->pembayaranModel = new PembayaranModel();
        $this->eventModel = new EventModel();
    }

    public function index()
    {
        // Get all documents with user info
        $dokumens = $this->dokumenModel->getDokumenWithUser();

        // Separate by type
        $loa_documents = array_filter($dokumens, fn($d) => $d['tipe'] === 'LOA');
        $sertifikat_documents = array_filter($dokumens, fn($d) => $d['tipe'] === 'Sertifikat');

        // Get users who need LOA (presenters with accepted abstracts)
        $needLOA = $this->abstrakModel
                       ->select('abstrak.id_user, users.nama_lengkap, users.email, users.institusi, COUNT(abstrak.id_abstrak) as total_accepted')
                       ->join('users', 'users.id_user = abstrak.id_user')
                       ->where('abstrak.status', 'diterima')
                       ->where('users.role', 'presenter')
                       ->groupBy('abstrak.id_user, users.nama_lengkap, users.email, users.institusi')
                       ->findAll();

        // Filter out users who already have LOA
        $existingLOA = array_column($loa_documents, 'id_user');
        $needLOA = array_filter($needLOA, fn($user) => !in_array($user['id_user'], $existingLOA));

        // Get users who need certificates (verified payments)
        $needSertifikat = $this->pembayaranModel
                              ->select('users.*, pembayaran.status as payment_status, events.title as event_title')
                              ->join('users', 'users.id_user = pembayaran.id_user')
                              ->join('events', 'events.id = pembayaran.event_id', 'left')
                              ->where('users.status', 'aktif')
                              ->whereIn('users.role', ['presenter', 'audience'])
                              ->where('pembayaran.status', 'verified')
                              ->groupBy('users.id_user, users.nama_lengkap, users.email, users.role, users.institusi, pembayaran.status, events.title')
                              ->findAll();

        $existingSertifikat = array_column($sertifikat_documents, 'id_user');
        $needSertifikat = array_filter($needSertifikat, fn($user) => !in_array($user['id_user'], $existingSertifikat));

        $data = [
            'loa_documents' => $loa_documents,
            'sertifikat_documents' => $sertifikat_documents,
            'need_loa' => $needLOA,
            'need_sertifikat' => $needSertifikat,
            'total_loa' => count($loa_documents),
            'total_sertifikat' => count($sertifikat_documents),
            'pending_loa' => count($needLOA),
            'pending_sertifikat' => count($needSertifikat)
        ];

        return view('role/admin/dokumen/index', $data);
    }

    public function uploadLoa($userId)
    {
        $user = $this->userModel->find($userId);
        
        if (!$user) {
            return redirect()->back()->with('error', 'User tidak ditemukan.');
        }

        // Check if user has accepted abstracts
        $acceptedAbstrak = $this->abstrakModel->where('id_user', $userId)
                                             ->where('status', 'diterima')
                                             ->countAllResults();

        if ($acceptedAbstrak == 0) {
            return redirect()->back()->with('error', 'User tidak memiliki abstrak yang diterima.');
        }

        // Check if LOA already exists
        $existingLOA = $this->dokumenModel->where('id_user', $userId)
                                         ->where('tipe', 'LOA')
                                         ->first();

        if ($existingLOA) {
            return redirect()->back()->with('error', 'LOA untuk user ini sudah ada.');
        }

        $validation = \Config\Services::validation();
        
        $rules = [
            'loa_file' => 'uploaded[loa_file]|max_size[loa_file,5120]|ext_in[loa_file,pdf]'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->with('errors', $validation->getErrors());
        }

        $file = $this->request->getFile('loa_file');
        
        if ($file->isValid() && !$file->hasMoved()) {
            // Ensure upload directory exists
            $uploadPath = WRITEPATH . 'uploads/dokumen/';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            $fileName = 'LOA_' . $userId . '_' . time() . '.pdf';
            
            if ($file->move($uploadPath, $fileName)) {
                // Save to database
                $dokumenData = [
                    'id_user' => $userId,
                    'tipe' => 'LOA',
                    'file_path' => $fileName,
                    'syarat' => 'Abstrak diterima',
                    'uploaded_at' => date('Y-m-d H:i:s')
                ];

                if ($this->dokumenModel->save($dokumenData)) {
                    $this->logActivity(session('id_user'), "Upload LOA untuk user: {$user['nama_lengkap']} (ID: {$userId})");
                    return redirect()->to('admin/dokumen')->with('success', 'LOA berhasil diupload!');
                } else {
                    // Remove uploaded file if database save fails
                    unlink($uploadPath . $fileName);
                    return redirect()->back()->with('error', 'Gagal menyimpan data LOA.');
                }
            }
        }

        return redirect()->back()->with('error', 'File tidak valid atau gagal diupload.');
    }

    public function uploadSertifikat($userId)
    {
        $user = $this->userModel->find($userId);
        
        if (!$user) {
            return redirect()->back()->with('error', 'User tidak ditemukan.');
        }

        // Check if user has verified payment
        $payment = $this->pembayaranModel->where('id_user', $userId)
                                        ->where('status', 'verified')
                                        ->first();

        if (!$payment) {
            return redirect()->back()->with('error', 'User belum memiliki pembayaran yang terverifikasi.');
        }

        // Check if certificate already exists
        $existingSertifikat = $this->dokumenModel->where('id_user', $userId)
                                                ->where('tipe', 'Sertifikat')
                                                ->first();

        if ($existingSertifikat) {
            return redirect()->back()->with('error', 'Sertifikat untuk user ini sudah ada.');
        }

        $validation = \Config\Services::validation();
        
        $rules = [
            'sertifikat_file' => 'uploaded[sertifikat_file]|max_size[sertifikat_file,5120]|ext_in[sertifikat_file,pdf]'
        ];

        if (!$this->validate($rules)) {
            return redirect()->back()->with('errors', $validation->getErrors());
        }

        $file = $this->request->getFile('sertifikat_file');
        
        if ($file->isValid() && !$file->hasMoved()) {
            // Ensure upload directory exists
            $uploadPath = WRITEPATH . 'uploads/dokumen/';
            if (!is_dir($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }

            $fileName = 'CERT_' . $userId . '_' . time() . '.pdf';
            
            if ($file->move($uploadPath, $fileName)) {
                // Save to database
                $dokumenData = [
                    'id_user' => $userId,
                    'tipe' => 'Sertifikat',
                    'file_path' => $fileName,
                    'syarat' => 'Pembayaran terverifikasi',
                    'uploaded_at' => date('Y-m-d H:i:s')
                ];

                if ($this->dokumenModel->save($dokumenData)) {
                    $this->logActivity(session('id_user'), "Upload Sertifikat untuk user: {$user['nama_lengkap']} (ID: {$userId})");
                    return redirect()->to('admin/dokumen')->with('success', 'Sertifikat berhasil diupload!');
                } else {
                    // Remove uploaded file if database save fails
                    unlink($uploadPath . $fileName);
                    return redirect()->back()->with('error', 'Gagal menyimpan data sertifikat.');
                }
            }
        }

        return redirect()->back()->with('error', 'File tidak valid atau gagal diupload.');
    }

    public function generateBulkLOA()
    {
        // Get all users who need LOA but don't have it yet
        $needLOA = $this->abstrakModel
                       ->select('abstrak.id_user, users.nama_lengkap, users.email, users.institusi, users.no_hp')
                       ->join('users', 'users.id_user = abstrak.id_user')
                       ->where('abstrak.status', 'diterima')
                       ->where('users.role', 'presenter')
                       ->groupBy('abstrak.id_user, users.nama_lengkap, users.email, users.institusi, users.no_hp')
                       ->findAll();

        $existingLOA = $this->dokumenModel->where('tipe', 'LOA')
                                         ->select('id_user')
                                         ->findAll();
        $existingLOAIds = array_column($existingLOA, 'id_user');

        // Filter out users who already have LOA
        $usersNeedingLOA = array_filter($needLOA, fn($user) => !in_array($user['id_user'], $existingLOAIds));

        if (empty($usersNeedingLOA)) {
            return redirect()->back()->with('info', 'Semua LOA sudah dibuat atau tidak ada presenter dengan abstrak yang diterima.');
        }

        $generated = 0;
        $errors = [];

        // Ensure upload directory exists
        $uploadPath = WRITEPATH . 'uploads/dokumen/';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        // Begin transaction
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            foreach ($usersNeedingLOA as $user) {
                try {
                    $fileName = 'LOA_' . $user['id_user'] . '_' . time() . '_' . rand(1000, 9999) . '.pdf';
                    $filePath = $uploadPath . $fileName;
                    
                    // Generate LOA PDF using TCPDF
                    if ($this->generateLOAPDF($user, $filePath)) {
                        $dokumenData = [
                            'id_user' => $user['id_user'],
                            'tipe' => 'LOA',
                            'file_path' => $fileName,
                            'syarat' => 'Auto-generated bulk LOA',
                            'uploaded_at' => date('Y-m-d H:i:s')
                        ];

                        if ($this->dokumenModel->save($dokumenData)) {
                            $generated++;
                        } else {
                            // Remove file if database save failed
                            if (file_exists($filePath)) {
                                unlink($filePath);
                            }
                            $errors[] = "Gagal menyimpan LOA untuk {$user['nama_lengkap']}";
                        }
                    } else {
                        $errors[] = "Gagal generate PDF LOA untuk {$user['nama_lengkap']}";
                    }
                } catch (\Exception $e) {
                    $errors[] = "Error untuk {$user['nama_lengkap']}: " . $e->getMessage();
                }
            }

            $db->transComplete();

            if ($db->transStatus() === FALSE) {
                throw new \Exception('Transaction failed');
            }

            $this->logActivity(session('id_user'), "Generate bulk LOA: {$generated} LOA dibuat");

            if (!empty($errors)) {
                $errorMessage = "Berhasil generate {$generated} LOA. Errors: " . implode(', ', array_slice($errors, 0, 3));
                return redirect()->back()->with('warning', $errorMessage);
            }

            return redirect()->back()->with('success', "Berhasil generate {$generated} LOA!");

        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'Bulk LOA generation error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function generateBulkSertifikat()
    {
        // Get all users who need certificates
        $needSertifikat = $this->pembayaranModel
                              ->select('users.*, pembayaran.verified_at, events.title as event_title')
                              ->join('users', 'users.id_user = pembayaran.id_user')
                              ->join('events', 'events.id = pembayaran.event_id', 'left')
                              ->where('users.status', 'aktif')
                              ->whereIn('users.role', ['presenter', 'audience'])
                              ->where('pembayaran.status', 'verified')
                              ->groupBy('users.id_user, users.nama_lengkap, users.email, users.role, users.institusi, users.no_hp, pembayaran.verified_at, events.title')
                              ->findAll();

        $existingSertifikat = $this->dokumenModel->where('tipe', 'Sertifikat')
                                                ->select('id_user')
                                                ->findAll();
        $existingSertifikatIds = array_column($existingSertifikat, 'id_user');

        // Filter out users who already have certificates
        $usersNeedingCertificate = array_filter($needSertifikat, fn($user) => !in_array($user['id_user'], $existingSertifikatIds));

        if (empty($usersNeedingCertificate)) {
            return redirect()->back()->with('info', 'Semua sertifikat sudah dibuat atau tidak ada peserta dengan pembayaran terverifikasi.');
        }

        $generated = 0;
        $errors = [];

        // Ensure upload directory exists
        $uploadPath = WRITEPATH . 'uploads/dokumen/';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        // Begin transaction
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            foreach ($usersNeedingCertificate as $user) {
                try {
                    $fileName = 'CERT_' . $user['id_user'] . '_' . time() . '_' . rand(1000, 9999) . '.pdf';
                    $filePath = $uploadPath . $fileName;
                    
                    // Generate Certificate PDF using TCPDF
                    if ($this->generateCertificatePDF($user, $filePath)) {
                        $dokumenData = [
                            'id_user' => $user['id_user'],
                            'tipe' => 'Sertifikat',
                            'file_path' => $fileName,
                            'syarat' => 'Auto-generated bulk certificate',
                            'uploaded_at' => date('Y-m-d H:i:s')
                        ];

                        if ($this->dokumenModel->save($dokumenData)) {
                            $generated++;
                        } else {
                            // Remove file if database save failed
                            if (file_exists($filePath)) {
                                unlink($filePath);
                            }
                            $errors[] = "Gagal menyimpan sertifikat untuk {$user['nama_lengkap']}";
                        }
                    } else {
                        $errors[] = "Gagal generate PDF sertifikat untuk {$user['nama_lengkap']}";
                    }
                } catch (\Exception $e) {
                    $errors[] = "Error untuk {$user['nama_lengkap']}: " . $e->getMessage();
                }
            }

            $db->transComplete();

            if ($db->transStatus() === FALSE) {
                throw new \Exception('Transaction failed');
            }

            $this->logActivity(session('id_user'), "Generate bulk Sertifikat: {$generated} sertifikat dibuat");

            if (!empty($errors)) {
                $errorMessage = "Berhasil generate {$generated} sertifikat. Errors: " . implode(', ', array_slice($errors, 0, 3));
                return redirect()->back()->with('warning', $errorMessage);
            }

            return redirect()->back()->with('success', "Berhasil generate {$generated} sertifikat!");

        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'Bulk certificate generation error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function download($id)
    {
        $dokumen = $this->dokumenModel->find($id);
        
        if (!$dokumen) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('Dokumen tidak ditemukan.');
        }

        $filePath = WRITEPATH . 'uploads/dokumen/' . $dokumen['file_path'];
        
        if (!file_exists($filePath)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('File tidak ditemukan.');
        }

        // Log download activity
        $user = $this->userModel->find($dokumen['id_user']);
        $this->logActivity(session('id_user'), "Download {$dokumen['tipe']} untuk user: {$user['nama_lengkap']}");

        // Set proper filename for download
        $downloadName = $dokumen['tipe'] . '_' . $user['nama_lengkap'] . '_' . date('Y-m-d', strtotime($dokumen['uploaded_at'])) . '.pdf';
        $downloadName = preg_replace('/[^A-Za-z0-9_\-\.]/', '_', $downloadName);

        return $this->response
                    ->download($filePath, null)
                    ->setFileName($downloadName)
                    ->setContentType('application/pdf');
    }

    public function delete($id)
    {
        $dokumen = $this->dokumenModel->find($id);
        
        if (!$dokumen) {
            return redirect()->back()->with('error', 'Dokumen tidak ditemukan.');
        }

        $filePath = WRITEPATH . 'uploads/dokumen/' . $dokumen['file_path'];
        
        // Get user info for logging
        $user = $this->userModel->find($dokumen['id_user']);
        
        // Begin transaction
        $db = \Config\Database::connect();
        $db->transStart();

        try {
            // Delete from database first
            if ($this->dokumenModel->delete($id)) {
                // Delete file after successful database deletion
                if (file_exists($filePath)) {
                    unlink($filePath);
                }

                $this->logActivity(session('id_user'), "Delete {$dokumen['tipe']} untuk user: {$user['nama_lengkap']}");
                
                $db->transComplete();
                
                if ($db->transStatus() === FALSE) {
                    return redirect()->back()->with('error', 'Gagal menghapus dokumen.');
                }
                
                return redirect()->back()->with('success', 'Dokumen berhasil dihapus!');
            } else {
                $db->transRollback();
                return redirect()->back()->with('error', 'Gagal menghapus dokumen dari database.');
            }
        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'Error deleting document: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    // TCPDF Implementation for LOA
    private function generateLOAPDF($user, $filePath)
    {
        try {
            // Get user's accepted abstracts
            $abstraks = $this->abstrakModel->select('abstrak.judul, kategori_abstrak.nama_kategori, events.title as event_title')
                                          ->join('kategori_abstrak', 'kategori_abstrak.id_kategori = abstrak.id_kategori')
                                          ->join('events', 'events.id = abstrak.event_id', 'left')
                                          ->where('abstrak.id_user', $user['id_user'])
                                          ->where('abstrak.status', 'diterima')
                                          ->findAll();

            // Create new PDF document
            $pdf = new \TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

            // Set document information
            $pdf->SetCreator('SNIA Conference Management System');
            $pdf->SetAuthor('SNIA Conference');
            $pdf->SetTitle('Letter of Acceptance');
            $pdf->SetSubject('LOA - ' . $user['nama_lengkap']);

            // Remove default header/footer
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);

            // Set margins
            $pdf->SetMargins(20, 20, 20);
            $pdf->SetAutoPageBreak(TRUE, 20);

            // Add a page
            $pdf->AddPage();

            // Set font
            $pdf->SetFont('helvetica', '', 12);

            // Header with logo area
            $pdf->SetFont('helvetica', 'B', 16);
            $pdf->SetTextColor(0, 51, 153); // Blue color
            $pdf->Cell(0, 15, 'SNIA CONFERENCE 2024', 0, 1, 'C');
            
            $pdf->SetFont('helvetica', '', 12);
            $pdf->SetTextColor(0, 0, 0); // Black color
            $pdf->Cell(0, 8, 'System Network Information & Application', 0, 1, 'C');
            $pdf->Ln(10);

            // LOA Title
            $pdf->SetFont('helvetica', 'B', 18);
            $pdf->SetTextColor(220, 53, 69); // Red color
            $pdf->Cell(0, 15, 'LETTER OF ACCEPTANCE', 0, 1, 'C');
            $pdf->Ln(10);

            // Reference number
            $pdf->SetFont('helvetica', '', 10);
            $pdf->SetTextColor(0, 0, 0);
            $refNumber = 'LOA/' . date('Y') . '/' . str_pad($user['id_user'], 4, '0', STR_PAD_LEFT);
            $pdf->Cell(0, 6, 'Ref: ' . $refNumber, 0, 1, 'L');
            $pdf->Cell(0, 6, 'Date: ' . date('F d, Y'), 0, 1, 'L');
            $pdf->Ln(8);

            // Greeting
            $pdf->SetFont('helvetica', '', 12);
            $pdf->Cell(0, 8, 'Dear ' . $user['nama_lengkap'] . ',', 0, 1, 'L');
            $pdf->Ln(5);

            // Main content
            $content = 'We are pleased to inform you that your abstract submission(s) have been accepted for presentation at the SNIA Conference 2024. ';
            $content .= 'The conference organizing committee has reviewed your submission and found it to be of high quality and relevant to the conference theme.';
            
            $pdf->MultiCell(0, 6, $content, 0, 'J');
            $pdf->Ln(5);

            // Abstract details
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 8, 'Accepted Abstract(s):', 0, 1, 'L');
            $pdf->Ln(2);

            $pdf->SetFont('helvetica', '', 11);
            foreach ($abstraks as $index => $abstrak) {
                $pdf->SetFont('helvetica', 'B', 11);
                $pdf->Cell(10, 6, ($index + 1) . '.', 0, 0, 'L');
                $pdf->MultiCell(0, 6, $abstrak['judul'], 0, 'L');
                
                $pdf->SetFont('helvetica', '', 10);
                $pdf->SetTextColor(100, 100, 100);
                $pdf->Cell(10, 5, '', 0, 0, 'L');
                $pdf->Cell(0, 5, 'Category: ' . $abstrak['nama_kategori'], 0, 1, 'L');
                if (!empty($abstrak['event_title'])) {
                    $pdf->Cell(10, 5, '', 0, 0, 'L');
                    $pdf->Cell(0, 5, 'Event: ' . $abstrak['event_title'], 0, 1, 'L');
                }
                $pdf->SetTextColor(0, 0, 0);
                $pdf->Ln(3);
            }

            // Presenter details
            $pdf->Ln(5);
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 8, 'Presenter Details:', 0, 1, 'L');
            $pdf->Ln(2);

            $pdf->SetFont('helvetica', '', 11);
            $pdf->Cell(40, 6, 'Name:', 0, 0, 'L');
            $pdf->Cell(0, 6, $user['nama_lengkap'], 0, 1, 'L');
            
            $pdf->Cell(40, 6, 'Email:', 0, 0, 'L');
            $pdf->Cell(0, 6, $user['email'], 0, 1, 'L');
            
            if (!empty($user['institusi'])) {
                $pdf->Cell(40, 6, 'Institution:', 0, 0, 'L');
                $pdf->Cell(0, 6, $user['institusi'], 0, 1, 'L');
            }

            if (!empty($user['no_hp'])) {
                $pdf->Cell(40, 6, 'Phone:', 0, 0, 'L');
                $pdf->Cell(0, 6, $user['no_hp'], 0, 1, 'L');
            }

            // Next steps
            $pdf->Ln(10);
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 8, 'Next Steps:', 0, 1, 'L');
            $pdf->Ln(2);

            $pdf->SetFont('helvetica', '', 11);
            $nextSteps = "1. Please confirm your attendance by completing the registration process\n";
            $nextSteps .= "2. Prepare your presentation according to the conference guidelines\n";
            $nextSteps .= "3. Submit your final presentation materials before the deadline\n";
            $nextSteps .= "4. Join us at the conference venue on the scheduled date";

            $pdf->MultiCell(0, 6, $nextSteps, 0, 'L');

            // Closing
            $pdf->Ln(8);
            $pdf->SetFont('helvetica', '', 12);
            $pdf->MultiCell(0, 6, 'We look forward to your valuable contribution to the SNIA Conference 2024.', 0, 'J');
            $pdf->Ln(5);

            $pdf->Cell(0, 6, 'Best regards,', 0, 1, 'L');
            $pdf->Ln(15);

            // Signature area
            $pdf->SetFont('helvetica', 'B', 12);
            $pdf->Cell(0, 6, 'SNIA Conference Organizing Committee', 0, 1, 'L');
            $pdf->SetFont('helvetica', '', 10);
            $pdf->Cell(0, 6, 'System Network Information & Application', 0, 1, 'L');

            // Footer
            $pdf->SetY(-30);
            $pdf->SetFont('helvetica', '', 8);
            $pdf->SetTextColor(100, 100, 100);
            $pdf->Cell(0, 5, 'This is an automatically generated document. For inquiries, please contact the organizing committee.', 0, 1, 'C');

            // Output PDF to file
            $pdf->Output($filePath, 'F');
            
            return file_exists($filePath);

        } catch (\Exception $e) {
            log_message('error', 'LOA PDF generation error: ' . $e->getMessage());
            return false;
        }
    }

    // TCPDF Implementation for Certificate
    private function generateCertificatePDF($user, $filePath)
    {
        try {
            // Create new PDF document
            $pdf = new \TCPDF('L', PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false); // Landscape orientation

            // Set document information
            $pdf->SetCreator('SNIA Conference Management System');
            $pdf->SetAuthor('SNIA Conference');
            $pdf->SetTitle('Certificate of Participation');
            $pdf->SetSubject('Certificate - ' . $user['nama_lengkap']);

            // Remove default header/footer
            $pdf->setPrintHeader(false);
            $pdf->setPrintFooter(false);

            // Set margins
            $pdf->SetMargins(30, 30, 30);
            $pdf->SetAutoPageBreak(FALSE);

            // Add a page
            $pdf->AddPage();

            // Background color (light blue)
            $pdf->SetFillColor(240, 248, 255);
            $pdf->Rect(0, 0, 297, 210, 'F'); // A4 landscape dimensions

            // Border
            $pdf->SetDrawColor(0, 51, 153);
            $pdf->SetLineWidth(2);
            $pdf->Rect(20, 20, 257, 170, 'D');

            // Inner border
            $pdf->SetLineWidth(0.5);
            $pdf->Rect(25, 25, 247, 160, 'D');

            // Header
            $pdf->SetTextColor(0, 51, 153);
            $pdf->SetFont('helvetica', 'B', 24);
            $pdf->SetXY(30, 40);
            $pdf->Cell(237, 15, 'SNIA CONFERENCE 2024', 0, 1, 'C');

            $pdf->SetFont('helvetica', '', 14);
            $pdf->SetXY(30, 58);
            $pdf->Cell(237, 8, 'System Network Information & Application', 0, 1, 'C');

            // Certificate title
            $pdf->SetFont('helvetica', 'B', 28);
            $pdf->SetTextColor(220, 53, 69);
            $pdf->SetXY(30, 80);
            $pdf->Cell(237, 20, 'CERTIFICATE OF PARTICIPATION', 0, 1, 'C');

            // This certifies that
            $pdf->SetFont('helvetica', '', 16);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetXY(30, 110);
            $pdf->Cell(237, 10, 'This is to certify that', 0, 1, 'C');

            // Participant name
            $pdf->SetFont('helvetica', 'B', 22);
            $pdf->SetTextColor(0, 51, 153);
            $pdf->SetXY(30, 125);
            $pdf->Cell(237, 15, strtoupper($user['nama_lengkap']), 0, 1, 'C');

            // Participation text
            $pdf->SetFont('helvetica', '', 14);
            $pdf->SetTextColor(0, 0, 0);
            $pdf->SetXY(30, 145);
            $participationText = 'has successfully participated in the SNIA Conference 2024 as a ' . strtoupper($user['role']);
            $pdf->Cell(237, 8, $participationText, 0, 1, 'C');

            // Institution
            if (!empty($user['institusi'])) {
                $pdf->SetFont('helvetica', '', 12);
                $pdf->SetXY(30, 158);
                $pdf->Cell(237, 6, 'Institution: ' . $user['institusi'], 0, 1, 'C');
            }

            // Award text
            $pdf->SetFont('helvetica', '', 12);
            $pdf->SetXY(30, 168);
            $pdf->MultiCell(237, 5, 'This certificate is awarded in recognition of your valuable contribution to the advancement of knowledge and research in the field of information systems and technology.', 0, 'C');

            // Date and signature area
            $pdf->SetFont('helvetica', '', 10);
            $pdf->SetXY(50, 185);
            $pdf->Cell(80, 5, 'Date: ' . date('F d, Y'), 0, 0, 'L');

            $pdf->SetXY(170, 185);
            $pdf->Cell(80, 5, 'Conference Director', 0, 0, 'R');

            // Certificate number
            $certNumber = 'CERT/' . date('Y') . '/' . str_pad($user['id_user'], 4, '0', STR_PAD_LEFT);
            $pdf->SetFont('helvetica', '', 8);
            $pdf->SetTextColor(100, 100, 100);
            $pdf->SetXY(30, 200);
            $pdf->Cell(237, 4, 'Certificate No: ' . $certNumber, 0, 1, 'C');

            // Output PDF to file
            $pdf->Output($filePath, 'F');
            
            return file_exists($filePath);

        } catch (\Exception $e) {
            log_message('error', 'Certificate PDF generation error: ' . $e->getMessage());
            return false;
        }
    }

    private function logActivity($userId, $activity)
    {
        $db = \Config\Database::connect();
        try {
            $db->table('log_aktivitas')->insert([
                'id_user' => $userId,
                'aktivitas' => $activity,
                'waktu' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            // Silent fail for logging
            log_message('error', 'Failed to log activity: ' . $e->getMessage());
        }
    }
}