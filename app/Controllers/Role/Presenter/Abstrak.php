<?php

namespace App\Controllers\Role\Presenter;

use App\Controllers\BaseController;
use App\Models\AbstrakModel;
use App\Models\KategoriAbstrakModel;
use App\Models\EventModel;
use App\Models\ReviewModel;
use App\Models\PembayaranModel;

class Abstrak extends BaseController
{
    protected $abstrakModel;
    protected $kategoriModel;
    protected $eventModel;
    protected $reviewModel;
    protected $pembayaranModel;
    protected $db;

    public function __construct()
    {
        $this->abstrakModel = new AbstrakModel();
        $this->kategoriModel = new KategoriAbstrakModel();
        $this->eventModel = new EventModel();
        $this->reviewModel = new ReviewModel();
        $this->pembayaranModel = new PembayaranModel();
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        $userId = session('id_user');
        
        // Get user's abstracts with details
        $abstraks = $this->db->table('abstrak')
                            ->select('abstrak.*, users.nama_lengkap, kategori_abstrak.nama_kategori, events.title as event_title')
                            ->join('users', 'users.id_user = abstrak.id_user')
                            ->join('kategori_abstrak', 'kategori_abstrak.id_kategori = abstrak.id_kategori')
                            ->join('events', 'events.id = abstrak.event_id', 'left')
                            ->where('abstrak.id_user', $userId)
                            ->orderBy('abstrak.tanggal_upload', 'DESC')
                            ->get()->getResultArray();

        // Get reviews for each abstract
        foreach ($abstraks as &$abstrak) {
            $reviews = $this->db->table('review')
                               ->select('review.*, users.nama_lengkap as reviewer_name')
                               ->join('users', 'users.id_user = review.id_reviewer')
                               ->where('review.id_abstrak', $abstrak['id_abstrak'])
                               ->get()->getResultArray();
            $abstrak['reviews'] = $reviews;
        }

        // Check if user has accepted abstracts for payment link
        $hasAcceptedAbstrak = false;
        foreach ($abstraks as $abstrak) {
            if ($abstrak['status'] === 'diterima') {
                $hasAcceptedAbstrak = true;
                break;
            }
        }

        $data = [
            'abstraks' => $abstraks,
            'kategori' => $this->kategoriModel->findAll(),
            'activeEvents' => $this->eventModel->getEventsWithOpenAbstractSubmission(),
            'hasAcceptedAbstrak' => $hasAcceptedAbstrak
        ];

        return view('role/presenter/abstrak/index', $data);
    }

    public function upload()
    {
        $userId = session('id_user');
        
        if (!$userId) {
            return redirect()->to('auth/login')->with('error', 'Silakan login terlebih dahulu');
        }

        // No payment check needed - presenter can submit abstract first
        // Payment is only required after abstract is accepted

        // Validate form input
        $validation = \Config\Services::validation();
        $validation->setRules([
            'event_id' => 'required|numeric',
            'id_kategori' => 'required|numeric', 
            'judul' => 'required|min_length[10]|max_length[255]',
            'file_abstrak' => 'uploaded[file_abstrak]|max_size[file_abstrak,5120]|ext_in[file_abstrak,pdf]'
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            $errors = $validation->getErrors();
            log_message('error', 'Validation errors: ' . json_encode($errors));
            return redirect()->back()->withInput()->with('errors', $errors);
        }

        // Get form data
        $eventId = $this->request->getPost('event_id');
        $kategoriId = $this->request->getPost('id_kategori');
        $judul = $this->request->getPost('judul');

        // Validate event exists and is active for abstract submission
        $event = $this->eventModel->find($eventId);
        if (!$event || !$this->eventModel->isAbstractSubmissionOpen($eventId)) {
            return redirect()->back()->with('error', 'Event tidak ditemukan atau submission sudah ditutup');
        }

        // Validate category exists
        $kategori = $this->kategoriModel->find($kategoriId);
        if (!$kategori) {
            return redirect()->back()->with('error', 'Kategori tidak ditemukan');
        }

        // Handle file upload
        $file = $this->request->getFile('file_abstrak');
        
        if (!$file || !$file->isValid()) {
            return redirect()->back()->with('error', 'File tidak valid: ' . ($file ? $file->getErrorString() : 'No file uploaded'));
        }

        if ($file->hasMoved()) {
            return redirect()->back()->with('error', 'File sudah dipindahkan');
        }

        // Validate file is PDF
        $fileExtension = strtolower($file->getClientExtension());
        if ($fileExtension !== 'pdf') {
            return redirect()->back()->with('error', 'File harus berformat PDF. File Anda: ' . $file->getClientName());
        }

        // Additional MIME type validation for PDF
        $fileMimeType = $file->getMimeType();
        $allowedMimeTypes = [
            'application/pdf',
            'application/x-pdf'
        ];
        
        if (!in_array($fileMimeType, $allowedMimeTypes)) {
            // For PDF files, sometimes MIME type detection fails, so we also check file signature
            $fileContent = file_get_contents($file->getTempName());
            if (substr($fileContent, 0, 4) !== '%PDF') {
                return redirect()->back()->with('error', 'File bukan PDF yang valid. MIME type: ' . $fileMimeType);
            }
        }

        // Create upload directory if not exists
        $uploadPath = WRITEPATH . 'uploads/abstraks/';
        if (!is_dir($uploadPath)) {
            if (!mkdir($uploadPath, 0755, true)) {
                log_message('error', 'Failed to create upload directory: ' . $uploadPath);
                return redirect()->back()->with('error', 'Gagal membuat direktori upload');
            }
        }

        // Generate unique filename
        $fileName = $userId . '_' . $eventId . '_' . time() . '.pdf';
        
        try {
            // Move file to upload directory
            if (!$file->move($uploadPath, $fileName)) {
                log_message('error', 'Failed to move uploaded file');
                return redirect()->back()->with('error', 'Gagal memindahkan file upload');
            }

            log_message('info', 'File uploaded successfully: ' . $fileName);
            
            // Check if this is a resubmission for same event
            $existingAbstrak = $this->abstrakModel->where('id_user', $userId)
                                                 ->where('event_id', $eventId)
                                                 ->first();
            
            $revisiKe = 0;
            if ($existingAbstrak) {
                $revisiKe = intval($existingAbstrak['revisi_ke']) + 1;
                log_message('info', 'This is a resubmission, revisi_ke: ' . $revisiKe);
                
                // Delete old file if exists
                $oldFilePath = $uploadPath . $existingAbstrak['file_abstrak'];
                if (file_exists($oldFilePath)) {
                    unlink($oldFilePath);
                }
                
                // Update existing record
                $updateData = [
                    'id_kategori' => $kategoriId,
                    'judul' => $judul,
                    'file_abstrak' => $fileName,
                    'status' => 'menunggu',
                    'tanggal_upload' => date('Y-m-d H:i:s'),
                    'revisi_ke' => $revisiKe
                ];
                
                $updateResult = $this->abstrakModel->update($existingAbstrak['id_abstrak'], $updateData);
                
                if ($updateResult) {
                    log_message('info', 'Abstract updated successfully with ID: ' . $existingAbstrak['id_abstrak']);
                    return redirect()->to('presenter/abstrak')->with('success', 'Abstrak berhasil diupdate (Revisi ke-' . $revisiKe . ') dan sedang menunggu review');
                } else {
                    // Delete uploaded file if database update fails
                    if (file_exists($uploadPath . $fileName)) {
                        unlink($uploadPath . $fileName);
                    }
                    $dbErrors = $this->abstrakModel->errors();
                    log_message('error', 'Database update failed: ' . json_encode($dbErrors));
                    return redirect()->back()->with('error', 'Gagal mengupdate abstrak: ' . implode(', ', $dbErrors));
                }
            } else {
                // Insert new record
                $data = [
                    'id_user' => $userId,
                    'event_id' => $eventId,
                    'id_kategori' => $kategoriId,
                    'judul' => $judul,
                    'file_abstrak' => $fileName,
                    'status' => 'menunggu',
                    'tanggal_upload' => date('Y-m-d H:i:s'),
                    'revisi_ke' => $revisiKe
                ];

                log_message('info', 'Attempting to insert data: ' . json_encode($data));
                
                $insertResult = $this->abstrakModel->insert($data);
                
                if ($insertResult) {
                    log_message('info', 'Abstract inserted successfully with ID: ' . $insertResult);
                    return redirect()->to('presenter/abstrak')->with('success', 'Abstrak berhasil diupload dan sedang menunggu review');
                } else {
                    // Delete uploaded file if database insert fails
                    if (file_exists($uploadPath . $fileName)) {
                        unlink($uploadPath . $fileName);
                    }
                    $dbErrors = $this->abstrakModel->errors();
                    log_message('error', 'Database insert failed: ' . json_encode($dbErrors));
                    return redirect()->back()->with('error', 'Gagal menyimpan data abstrak: ' . implode(', ', $dbErrors));
                }
            }
            
        } catch (\Exception $e) {
            // Delete uploaded file if exception occurs
            if (file_exists($uploadPath . $fileName)) {
                unlink($uploadPath . $fileName);
            }
            log_message('error', 'Exception during upload: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function status()
    {
        $userId = session('id_user');
        
        $abstraks = $this->db->table('abstrak')
                            ->select('abstrak.*, kategori_abstrak.nama_kategori, events.title as event_title')
                            ->join('kategori_abstrak', 'kategori_abstrak.id_kategori = abstrak.id_kategori')
                            ->join('events', 'events.id = abstrak.event_id', 'left')
                            ->where('abstrak.id_user', $userId)
                            ->orderBy('abstrak.tanggal_upload', 'DESC')
                            ->get()->getResultArray();

        // Get reviews for each abstract
        foreach ($abstraks as &$abstrak) {
            $reviews = $this->db->table('review')
                               ->select('review.*, users.nama_lengkap as reviewer_name')
                               ->join('users', 'users.id_user = review.id_reviewer')
                               ->where('review.id_abstrak', $abstrak['id_abstrak'])
                               ->orderBy('review.tanggal_review', 'DESC')
                               ->get()->getResultArray();
            $abstrak['reviews'] = $reviews;
        }

        $data = ['abstraks' => $abstraks];
        return view('role/presenter/abstrak/status', $data);
    }

    public function download($filename)
    {
        $userId = session('id_user');
        
        // Verify user owns this file
        $abstrak = $this->abstrakModel->where('id_user', $userId)
                                     ->where('file_abstrak', $filename)
                                     ->first();
        
        if (!$abstrak) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('File tidak ditemukan atau Anda tidak memiliki akses');
        }

        $filepath = WRITEPATH . 'uploads/abstraks/' . $filename;
        
        if (!file_exists($filepath)) {
            log_message('error', 'File not found at: ' . $filepath);
            throw new \CodeIgniter\Exceptions\PageNotFoundException('File tidak ditemukan di server');
        }

        // Validate file is actually a PDF
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $filepath);
        finfo_close($finfo);
        
        if (!in_array($mimeType, ['application/pdf', 'application/x-pdf'])) {
            log_message('error', 'Invalid file type for download: ' . $mimeType);
            throw new \CodeIgniter\Exceptions\PageNotFoundException('File bukan PDF yang valid');
        }

        // Create a clean filename for download
        $downloadName = 'Abstrak_' . $abstrak['id_abstrak'] . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', $abstrak['judul']) . '.pdf';
        $downloadName = substr($downloadName, 0, 200) . '.pdf'; // Limit filename length

        // Log download activity
        log_message('info', "User {$userId} downloaded abstract file: {$filename}");

        return $this->response
                    ->download($filepath, null)
                    ->setFileName($downloadName)
                    ->setContentType('application/pdf');
    }

    public function detail($id_abstrak)
    {
        $userId = session('id_user');
        
        // Get abstrak detail - only user's own abstracts
        $abstrak = $this->db->table('abstrak')
                           ->select('abstrak.*, kategori_abstrak.nama_kategori, events.title as event_title')
                           ->join('kategori_abstrak', 'kategori_abstrak.id_kategori = abstrak.id_kategori')
                           ->join('events', 'events.id = abstrak.event_id', 'left')
                           ->where('abstrak.id_abstrak', $id_abstrak)
                           ->where('abstrak.id_user', $userId)
                           ->get()->getRowArray();

        if (!$abstrak) {
            return redirect()->to('presenter/abstrak')->with('error', 'Abstrak tidak ditemukan');
        }

        // Get reviews for this abstract
        $reviews = $this->db->table('review')
                           ->select('review.*, users.nama_lengkap as reviewer_name')
                           ->join('users', 'users.id_user = review.id_reviewer')
                           ->where('review.id_abstrak', $id_abstrak)
                           ->orderBy('review.tanggal_review', 'DESC')
                           ->get()->getResultArray();

        $data = [
            'abstrak' => $abstrak,
            'reviews' => $reviews
        ];

        return view('role/presenter/abstrak/detail', $data);
    }
}