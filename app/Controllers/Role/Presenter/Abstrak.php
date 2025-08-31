<?php

namespace App\Controllers\Role\Presenter;

use App\Controllers\BaseController;
use App\Models\AbstrakModel;
use App\Models\KategoriAbstrakModel;
use App\Models\EventModel;
use App\Models\ReviewModel;

class Abstrak extends BaseController
{
    protected $abstrakModel;
    protected $kategoriModel;
    protected $eventModel;
    protected $reviewModel;

    protected $db;

    public function __construct()
    {
        $this->abstrakModel = new AbstrakModel();
        $this->kategoriModel = new KategoriAbstrakModel();
        $this->eventModel = new EventModel();
        $this->reviewModel = new ReviewModel();
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        $userId = session('id_user');
        
        // Get user's abstracts with details - sesuai migration
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

        $data = [
            'abstraks' => $abstraks,
            'kategori' => $this->kategoriModel->findAll(),
            'activeEvents' => $this->eventModel->getEventsWithOpenAbstractSubmission()
        ];

        return view('role/presenter/abstrak/index', $data);
    }

    public function upload()
    {
        $userId = session('id_user');
        
        // Debug: Check if user is logged in
        if (!$userId) {
            return redirect()->to('auth/login')->with('error', 'Silakan login terlebih dahulu');
        }

        // Debug: Log the request
        log_message('info', 'Upload request received from user: ' . $userId);
        log_message('info', 'POST data: ' . json_encode($this->request->getPost()));

        $validation = \Config\Services::validation();
        $validation->setRules([
            'event_id' => 'required|numeric',
            'id_kategori' => 'required|numeric', 
            'judul' => 'required|min_length[10]|max_length[255]',
            'file_abstrak' => 'uploaded[file_abstrak]|max_size[file_abstrak,5120]'
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            $errors = $validation->getErrors();
            log_message('error', 'Validation errors: ' . json_encode($errors));
            return redirect()->back()->withInput()->with('errors', $errors);
        }

        // Simplified validation - remove file validation from rules
        $validation = \Config\Services::validation();
        $validation->setRules([
            'event_id' => 'required|numeric',
            'id_kategori' => 'required|numeric', 
            'judul' => 'required|min_length[10]|max_length[255]'
        ]);

        if (!$validation->withRequest($this->request)->run()) {
            $errors = $validation->getErrors();
            log_message('error', 'Basic validation errors: ' . json_encode($errors));
            return redirect()->back()->withInput()->with('errors', $errors);
        }

        // Manual file validation with detailed feedback
        $file = $this->request->getFile('file_abstrak');
        
        if (!$file) {
            return redirect()->back()->with('error', 'Tidak ada file yang diupload');
        }

        if (!$file->isValid()) {
            return redirect()->back()->with('error', 'File upload error: ' . $file->getErrorString());
        }

        // Get file details for user feedback
        $originalName = $file->getClientName();
        $fileSize = $file->getSize();
        $fileMimeType = $file->getMimeType();
        $fileExtension = strtolower($file->getClientExtension());
        
        log_message('info', "File upload attempt - Name: $originalName, Size: $fileSize, MIME: $fileMimeType, Ext: $fileExtension");

        // Check file size first
        if ($fileSize > 5 * 1024 * 1024) {
            return redirect()->back()->with('error', 'Ukuran file terlalu besar: ' . round($fileSize / 1024 / 1024, 2) . 'MB. Maksimal 5MB');
        }

        // Check extension - allow PDF, DOC, DOCX
        $allowedExtensions = ['pdf', 'doc', 'docx'];
        if (!in_array($fileExtension, $allowedExtensions)) {
            return redirect()->back()->with('error', "File harus PDF, DOC, atau DOCX. File Anda: $originalName (.$fileExtension)");
        }

        // Flexible MIME check for multiple document types
        $allowedMimeTypes = [
            // PDF types
            'application/pdf',
            'application/x-pdf',
            // Word types
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-word',
            // Sometimes documents are detected as these
            'application/octet-stream',
            'application/zip'
        ];
        
        $fileMimeType = $file->getMimeType();
        
        // More lenient validation - allow if extension is correct
        if (!in_array($fileMimeType, $allowedMimeTypes)) {
            log_message('warning', "Unexpected MIME type: $fileMimeType for file: $originalName");
            // Continue anyway if extension is correct
        }

        $eventId = $this->request->getPost('event_id');
        $kategoriId = $this->request->getPost('id_kategori');
        $judul = $this->request->getPost('judul');

        // Validate event exists and is active
        $event = $this->eventModel->find($eventId);
        if (!$event) {
            log_message('error', 'Event not found: ' . $eventId);
            return redirect()->back()->with('error', 'Event tidak ditemukan');
        }

        // Validate category exists
        $kategori = $this->kategoriModel->find($kategoriId);
        if (!$kategori) {
            log_message('error', 'Category not found: ' . $kategoriId);
            return redirect()->back()->with('error', 'Kategori tidak ditemukan');
        }

        // Create upload directory if not exists
        $uploadPath = WRITEPATH . 'uploads/abstraks/';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
            log_message('info', 'Created upload directory: ' . $uploadPath);
        }

        // Handle file upload
        $file = $this->request->getFile('file_abstrak');
        if (!$file || !$file->isValid()) {
            log_message('error', 'Invalid file upload');
            return redirect()->back()->with('error', 'File tidak valid');
        }

        if ($file->hasMoved()) {
            log_message('error', 'File already moved');
            return redirect()->back()->with('error', 'File sudah dipindahkan');
        }

        $fileName = $userId . '_' . time() . '_' . $file->getRandomName();
        
        try {
            $file->move($uploadPath, $fileName);
            log_message('info', 'File uploaded successfully: ' . $fileName);
            
            // Check if this is a resubmission for same event
            $existingAbstrak = $this->abstrakModel->where('id_user', $userId)
                                                 ->where('event_id', $eventId)
                                                 ->first();
            
            $revisiKe = 0;
            if ($existingAbstrak) {
                $revisiKe = intval($existingAbstrak['revisi_ke']) + 1;
                log_message('info', 'This is a resubmission, revisi_ke: ' . $revisiKe);
            }

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
                $dbErrors = $this->abstrakModel->errors();
                log_message('error', 'Database insert failed: ' . json_encode($dbErrors));
                return redirect()->back()->with('error', 'Gagal menyimpan data abstrak: ' . implode(', ', $dbErrors));
            }
            
        } catch (\Exception $e) {
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
            throw new \CodeIgniter\Exceptions\PageNotFoundException('File tidak ditemukan');
        }

        $filepath = WRITEPATH . 'uploads/abstraks/' . $filename;
        
        if (!file_exists($filepath)) {
            throw new \CodeIgniter\Exceptions\PageNotFoundException('File tidak ditemukan');
        }

        return $this->response->download($filepath, null);
    }
}