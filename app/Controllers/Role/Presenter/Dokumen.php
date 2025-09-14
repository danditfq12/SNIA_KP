<?php
namespace App\Controllers\Role\Presenter;

use App\Controllers\BaseController;
use App\Models\DokumenModel;
use App\Models\EventModel;

class Dokumen extends BaseController
{
    protected $dokumenModel;
    protected $eventModel;
    protected $db;

    public function __construct()
    {
        $this->dokumenModel = new DokumenModel();
        $this->eventModel = new EventModel();
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        $userId = $this->getUserId();
        if (!$userId) {
            return redirect()->to('/auth/login')->with('error', 'Silakan login terlebih dahulu.');
        }

        // Get LOA documents
        $loa = $this->getDokumenByTipe($userId, 'loa');
        
        // Get Sertifikat documents
        $sertifikat = $this->getDokumenByTipe($userId, 'sertifikat');

        $data = [
            'title' => 'Dokumen Saya',
            'loa' => $loa,
            'sertifikat' => $sertifikat
        ];

        return view('role/presenter/dokumen/index', $data);
    }

    public function downloadLoa($filename)
    {
        return $this->downloadDocument($filename, 'loa');
    }

    public function downloadSertifikat($filename)
    {
        return $this->downloadDocument($filename, 'sertifikat');
    }

    // Legacy redirects
    public function loa()
    {
        return redirect()->to('/presenter/dokumen#loa');
    }

    public function sertifikat()
    {
        return redirect()->to('/presenter/dokumen#sertifikat');
    }

    // Private helper methods
    private function getUserId()
    {
        return session('id_user') ?? session('user_id') ?? session('id') ?? 0;
    }

    private function getDokumenByTipe($userId, $tipe)
    {
        try {
            $query = $this->db->table('dokumen d')
                ->select([
                    'd.id_dokumen',
                    'd.event_id', 
                    'd.tipe',
                    'd.file_path',
                    'd.uploaded_at as created_at',
                    'e.title as event_title'
                ])
                ->join('events e', 'e.id = d.event_id', 'left')
                ->where('d.id_user', $userId)
                ->where('d.tipe', $tipe)
                ->orderBy('d.uploaded_at', 'DESC');
            
            $results = $query->get()->getResultArray();
            
            return $results ?: [];
        } catch (\Exception $e) {
            log_message('error', 'Error getting documents: ' . $e->getMessage());
            return [];
        }
    }

    private function downloadDocument($filename, $tipe)
    {
        $userId = $this->getUserId();
        if (!$userId) {
            return redirect()->to('/auth/login')->with('error', 'Silakan login terlebih dahulu.');
        }

        // Verify document belongs to user
        $document = $this->db->table('dokumen')
            ->where('id_user', $userId)
            ->where('tipe', $tipe)
            ->where('file_path', $filename)
            ->get()
            ->getRowArray();

        if (!$document) {
            return redirect()->back()->with('error', 'Dokumen tidak ditemukan atau bukan milik Anda.');
        }

        // Determine file path based on document type
        $basePath = WRITEPATH . 'uploads/' . $tipe . '/';
        $filePath = $basePath . $filename;

        if (!file_exists($filePath)) {
            return redirect()->back()->with('error', 'File tidak ditemukan di server.');
        }

        // Get event info for better filename
        $event = $this->eventModel->find($document['event_id']);
        $eventTitle = $event ? preg_replace('/[^A-Za-z0-9_-]/', '_', $event['title']) : 'Event';
        
        // Get user info
        $user = $this->db->table('users')->where('id_user', $userId)->get()->getRowArray();
        $userName = $user ? preg_replace('/[^A-Za-z0-9_-]/', '_', $user['nama_lengkap']) : 'User';
        
        $extension = pathinfo($filename, PATHINFO_EXTENSION);
        $downloadName = strtoupper($tipe) . '_' . $eventTitle . '_' . $userName . '.' . $extension;

        // Log download activity
        $this->logActivity($userId, "Downloaded {$tipe} from event: " . ($event['title'] ?? 'Unknown'));

        return $this->response->download($filePath, null)->setFileName($downloadName);
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