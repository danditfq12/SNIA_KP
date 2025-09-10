<?php

namespace App\Controllers\Role\Presenter;

use App\Controllers\BaseController;
use App\Models\AbstrakModel;
use App\Models\EventModel;
use App\Models\KategoriAbstrakModel;
use App\Models\ReviewModel;
use App\Models\UserModel;

class Abstrak extends BaseController
{
    protected $abstrakModel;
    protected $eventModel;
    protected $kategoriModel;
    protected $reviewModel;
    protected $userModel;
    protected $db;

    public function __construct()
    {
        $this->abstrakModel = new AbstrakModel();
        $this->eventModel = new EventModel();
        $this->kategoriModel = new KategoriAbstrakModel();
        $this->reviewModel = new ReviewModel();
        $this->userModel = new UserModel();
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        $userId = session('id_user');
        
        try {
            // Get event ID from query parameter for direct abstract submission
            $eventId = $this->request->getGet('event_id');
            
            // Get user's abstracts with details
            $abstracts = $this->getUserAbstracts($userId);
            
            // Get available events for new abstract submission
            $availableEvents = $this->getAvailableEventsForAbstract($userId);
            
            // Get categories
            $categories = $this->kategoriModel->findAll();

            $data = [
                'abstracts' => $abstracts,
                'available_events' => $availableEvents,
                'categories' => $categories,
                'selected_event_id' => $eventId,
                'title' => 'Manajemen Abstrak'
            ];

            return view('role/presenter/abstrak/index', $data);

        } catch (\Exception $e) {
            log_message('error', 'Error in presenter abstrak index: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memuat halaman abstrak.');
        }
    }

    public function upload()
    {
        $userId = session('id_user');
        
        if (!$this->request->getMethod() === 'POST') {
            return redirect()->to('presenter/abstrak');
        }

        // Validation rules
        $validationRules = [
            'event_id' => 'required|integer',
            'id_kategori' => 'required|integer',
            'judul' => 'required|min_length[10]|max_length[255]',
            'file_abstrak' => [
                'rules' => 'uploaded[file_abstrak]|max_size[file_abstrak,10240]|ext_in[file_abstrak,pdf,doc,docx]',
                'errors' => [
                    'uploaded' => 'File abstrak harus diupload',
                    'max_size' => 'Ukuran file maksimal 10MB',
                    'ext_in' => 'File harus berformat PDF, DOC, atau DOCX'
                ]
            ]
        ];

        if (!$this->validate($validationRules)) {
            return redirect()->back()
                ->withInput()
                ->with('errors', $this->validator->getErrors());
        }

        try {
            $eventId = $this->request->getPost('event_id');
            $categoryId = $this->request->getPost('id_kategori');
            $title = $this->request->getPost('judul');
            $file = $this->request->getFile('file_abstrak');

            // Verify event exists and abstract submission is open
            $event = $this->eventModel->find($eventId);
            if (!$event) {
                return redirect()->back()->with('error', 'Event tidak ditemukan.');
            }

            if (!$this->eventModel->isAbstractSubmissionOpen($eventId)) {
                return redirect()->back()->with('error', 'Periode submission abstrak untuk event ini sudah ditutup.');
            }

            // Check if user already has abstract for this event
            $existingAbstract = $this->abstrakModel
                ->where('id_user', $userId)
                ->where('event_id', $eventId)
                ->first();

            // If exists and not in revision status, prevent new submission
            if ($existingAbstract && !in_array($existingAbstract['status'], ['ditolak', 'revisi'])) {
                return redirect()->back()->with('error', 'Anda sudah memiliki abstrak untuk event ini.');
            }

            $this->db->transStart();

            // Handle file upload
            $fileName = $this->handleFileUpload($file, $userId, $eventId);
            
            if (!$fileName) {
                throw new \Exception('Gagal mengupload file abstrak.');
            }

            // Prepare abstrak data
            $timezone = new \DateTimeZone('Asia/Jakarta');
            $now = new \DateTime('now', $timezone);

            $abstrakData = [
                'id_user' => $userId,
                'event_id' => $eventId,
                'id_kategori' => $categoryId,
                'judul' => $title,
                'file_abstrak' => $fileName,
                'status' => 'menunggu',
                'tanggal_upload' => $now->format('Y-m-d H:i:s'),
                'revisi_ke' => $existingAbstract ? ($existingAbstract['revisi_ke'] + 1) : 0
            ];

            // If this is a revision, update existing record
            if ($existingAbstract && in_array($existingAbstract['status'], ['ditolak', 'revisi'])) {
                $result = $this->abstrakModel->update($existingAbstract['id_abstrak'], $abstrakData);
                $abstrakId = $existingAbstract['id_abstrak'];
            } else {
                // Create new abstract
                $result = $this->abstrakModel->insert($abstrakData);
                $abstrakId = $this->abstrakModel->getInsertID();
            }

            if (!$result) {
                throw new \Exception('Gagal menyimpan data abstrak.');
            }

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                // Delete uploaded file if transaction failed
                $this->deleteUploadedFile($fileName);
                throw new \Exception('Database transaction failed');
            }

            // Log activity
            $action = $existingAbstract ? 'Merevisi abstrak' : 'Mengupload abstrak';
            $this->logActivity($userId, "{$action}: {$title} untuk event {$event['title']}");

            return redirect()->to('presenter/abstrak/detail/' . $abstrakId)
                ->with('success', 'Abstrak berhasil diupload. Silakan tunggu proses review.');

        } catch (\Exception $e) {
            $this->db->transRollback();
            
            // Delete uploaded file if exists
            if (isset($fileName)) {
                $this->deleteUploadedFile($fileName);
            }
            
            log_message('error', 'Error uploading abstract: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Terjadi kesalahan saat mengupload abstrak: ' . $e->getMessage());
        }
    }

    public function detail($abstrakId)
    {
        $userId = session('id_user');
        
        try {
            // Get abstract with details
            $abstract = $this->getAbstractWithDetails($abstrakId, $userId);
            
            if (!$abstract) {
                return redirect()->to('presenter/abstrak')->with('error', 'Abstrak tidak ditemukan.');
            }

            // Get review history
            $reviews = $this->getAbstractReviews($abstrakId);
            
            // Get event info
            $event = $this->eventModel->find($abstract['event_id']);
            
            // Check if can revise
            $canRevise = in_array($abstract['status'], ['revisi', 'ditolak']) && 
                        $this->eventModel->isAbstractSubmissionOpen($abstract['event_id']);

            $data = [
                'abstract' => $abstract,
                'reviews' => $reviews,
                'event' => $event,
                'can_revise' => $canRevise,
                'categories' => $this->kategoriModel->findAll(),
                'title' => 'Detail Abstrak: ' . $abstract['judul']
            ];

            return view('role/presenter/abstrak/detail', $data);

        } catch (\Exception $e) {
            log_message('error', 'Error in abstract detail: ' . $e->getMessage());
            return redirect()->to('presenter/abstrak')->with('error', 'Terjadi kesalahan saat memuat detail abstrak.');
        }
    }

    public function download($fileName)
    {
        $userId = session('id_user');
        
        try {
            // Verify file belongs to user
            $abstract = $this->abstrakModel
                ->where('id_user', $userId)
                ->where('file_abstrak', $fileName)
                ->first();

            if (!$abstract) {
                return redirect()->to('presenter/abstrak')->with('error', 'File tidak ditemukan atau Anda tidak memiliki akses.');
            }

            $filePath = WRITEPATH . 'uploads/abstraks/' . $fileName;
            
            if (!file_exists($filePath)) {
                return redirect()->to('presenter/abstrak')->with('error', 'File tidak ditemukan di server.');
            }

            // Log download activity
            $this->logActivity($userId, "Mengunduh abstrak: {$abstract['judul']}");

            // Force download
            return $this->response->download($filePath, null);

        } catch (\Exception $e) {
            log_message('error', 'Error downloading abstract: ' . $e->getMessage());
            return redirect()->to('presenter/abstrak')->with('error', 'Terjadi kesalahan saat mengunduh file.');
        }
    }

    public function status()
    {
        $userId = session('id_user');
        
        if (!$this->request->isAJAX()) {
            return redirect()->to('presenter/abstrak');
        }

        try {
            $abstracts = $this->getUserAbstracts($userId);
            
            $statusData = [];
            foreach ($abstracts as $abstract) {
                $statusData[] = [
                    'id' => $abstract['id_abstrak'],
                    'title' => $abstract['judul'],
                    'event' => $abstract['event_title'],
                    'status' => $abstract['status'],
                    'upload_date' => $abstract['tanggal_upload'],
                    'revision' => $abstract['revisi_ke']
                ];
            }

            return $this->response->setJSON([
                'success' => true,
                'data' => $statusData
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error getting abstract status: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Terjadi kesalahan saat memuat status abstrak'
            ]);
        }
    }

    private function getUserAbstracts($userId)
    {
        return $this->db->query("
            SELECT 
                a.*,
                e.title as event_title,
                e.event_date,
                e.abstract_deadline,
                e.abstract_submission_active,
                k.nama_kategori,
                COUNT(r.id_review) as review_count,
                MAX(r.tanggal_review) as last_review_date
            FROM abstrak a
            LEFT JOIN events e ON e.id = a.event_id
            LEFT JOIN kategori_abstrak k ON k.id_kategori = a.id_kategori
            LEFT JOIN review r ON r.id_abstrak = a.id_abstrak
            WHERE a.id_user = ?
            GROUP BY a.id_abstrak, e.id, k.id_kategori
            ORDER BY a.tanggal_upload DESC
        ", [$userId])->getResultArray();
    }

    private function getAvailableEventsForAbstract($userId)
    {
        // Get events where abstract submission is open and user hasn't submitted yet
        return $this->db->query("
            SELECT e.*
            FROM events e
            WHERE e.is_active = true
            AND e.abstract_submission_active = true
            AND (e.abstract_deadline IS NULL OR e.abstract_deadline >= NOW())
            AND NOT EXISTS (
                SELECT 1 FROM abstrak a 
                WHERE a.event_id = e.id 
                AND a.id_user = ? 
                AND a.status NOT IN ('ditolak', 'revisi')
            )
            ORDER BY e.event_date ASC
        ", [$userId])->getResultArray();
    }

    private function getAbstractWithDetails($abstrakId, $userId)
    {
        return $this->db->query("
            SELECT 
                a.*,
                e.title as event_title,
                e.event_date,
                e.abstract_deadline,
                e.abstract_submission_active,
                k.nama_kategori,
                u.nama_lengkap as author_name
            FROM abstrak a
            LEFT JOIN events e ON e.id = a.event_id
            LEFT JOIN kategori_abstrak k ON k.id_kategori = a.id_kategori
            LEFT JOIN users u ON u.id_user = a.id_user
            WHERE a.id_abstrak = ? AND a.id_user = ?
        ", [$abstrakId, $userId])->getRowArray();
    }

    private function getAbstractReviews($abstrakId)
    {
        return $this->db->query("
            SELECT 
                r.*,
                u.nama_lengkap as reviewer_name
            FROM review r
            LEFT JOIN users u ON u.id_user = r.id_reviewer
            WHERE r.id_abstrak = ?
            ORDER BY r.tanggal_review DESC
        ", [$abstrakId])->getResultArray();
    }

    private function handleFileUpload($file, $userId, $eventId)
    {
        if (!$file->isValid()) {
            throw new \Exception('File tidak valid: ' . $file->getErrorString());
        }

        // Create upload directory if it doesn't exist
        $uploadPath = WRITEPATH . 'uploads/abstraks/';
        if (!is_dir($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }

        // Generate unique filename
        $timezone = new \DateTimeZone('Asia/Jakarta');
        $now = new \DateTime('now', $timezone);
        $timestamp = $now->format('YmdHis');
        $extension = $file->getClientExtension();
        $fileName = "abstract_{$userId}_{$eventId}_{$timestamp}.{$extension}";

        // Move file
        if (!$file->move($uploadPath, $fileName)) {
            throw new \Exception('Gagal memindahkan file ke direktori upload.');
        }

        return $fileName;
    }

    private function deleteUploadedFile($fileName)
    {
        $filePath = WRITEPATH . 'uploads/abstraks/' . $fileName;
        if (file_exists($filePath)) {
            unlink($filePath);
        }
    }

    private function logActivity($userId, $activity)
    {
        try {
            $timezone = new \DateTimeZone('Asia/Jakarta');
            $now = new \DateTime('now', $timezone);
            
            $this->db->table('log_aktivitas')->insert([
                'id_user' => $userId,
                'aktivitas' => $activity,
                'waktu' => $now->format('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Failed to log activity: ' . $e->getMessage());
        }
    }
}