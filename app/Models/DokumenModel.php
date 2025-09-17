<?php
namespace App\Models;

use CodeIgniter\Model;

class DokumenModel extends Model
{
    protected $table         = 'dokumen';
    protected $primaryKey    = 'id_dokumen';
    protected $returnType    = 'array';
    protected $useTimestamps = false;

    protected $allowedFields = [
        'id_user', 'event_id', 'tipe', 'file_path', 'syarat', 'uploaded_at'
    ];

    /**
     * FIXED: Get dokumen dengan user info dan event info (untuk admin)
     */
    public function getDokumenWithUser(?string $tipe = null, ?int $eventId = null): array
    {
        $builder = $this->select('
            dokumen.*, 
            users.nama_lengkap, 
            users.email, 
            users.role,
            events.title as event_title,
            events.event_date
        ')
        ->join('users', 'users.id_user = dokumen.id_user', 'left')
        ->join('events', 'events.id = dokumen.event_id', 'left');

        if (!empty($tipe)) {
            $builder->where('dokumen.tipe', $tipe);
        }

        if ($eventId) {
            $builder->where('dokumen.event_id', $eventId);
        }

        return $builder->orderBy('dokumen.uploaded_at', 'DESC')
                       ->orderBy('dokumen.id_dokumen', 'DESC')
                       ->findAll();
    }

    /**
     * Get dokumen milik satu user dengan info event
     */
    public function getUserDocs(int $userId, ?string $tipe = null, ?int $eventId = null): array
    {
        $builder = $this->select('
            dokumen.*, 
            events.title as event_title,
            events.event_date,
            events.event_time
        ')
        ->join('events', 'events.id = dokumen.event_id', 'left')
        ->where('dokumen.id_user', $userId);

        if (!empty($tipe)) {
            $builder->where('dokumen.tipe', $tipe);
        }

        if ($eventId) {
            $builder->where('dokumen.event_id', $eventId);
        }

        return $builder->orderBy('dokumen.uploaded_at', 'DESC')
                       ->orderBy('dokumen.id_dokumen', 'DESC')
                       ->findAll();
    }

    /**
     * Get satu dokumen dengan detail lengkap
     */
    public function getOneWithDetails(int $idDokumen): ?array
    {
        return $this->select('
            dokumen.*, 
            users.nama_lengkap, 
            users.email, 
            users.role,
            events.title as event_title,
            events.event_date,
            events.event_time
        ')
        ->join('users', 'users.id_user = dokumen.id_user', 'left')
        ->join('events', 'events.id = dokumen.event_id', 'left')
        ->where('dokumen.id_dokumen', $idDokumen)
        ->first();
    }

    /**
     * Daftar LOA milik user untuk event tertentu
     */
    public function listLoaByUserEvent(int $userId, ?int $eventId = null): array
    {
        $builder = $this->select('
            dokumen.*, 
            events.title as event_title,
            events.event_date,
            events.event_time
        ')
        ->join('events', 'events.id = dokumen.event_id', 'left')
        ->where('dokumen.id_user', $userId)
        ->where('dokumen.tipe', 'loa');

        if ($eventId) {
            $builder->where('dokumen.event_id', $eventId);
        }

        return $builder->orderBy('dokumen.uploaded_at', 'DESC')
                       ->orderBy('dokumen.id_dokumen', 'DESC')
                       ->findAll();
    }

    /**
     * Daftar sertifikat milik user untuk event tertentu
     */
    public function listSertifikatByUserEvent(int $userId, ?int $eventId = null): array
    {
        $builder = $this->select('
            dokumen.*, 
            events.title as event_title,
            events.event_date,
            events.event_time
        ')
        ->join('events', 'events.id = dokumen.event_id', 'left')
        ->where('dokumen.id_user', $userId)
        ->where('dokumen.tipe', 'sertifikat');

        if ($eventId) {
            $builder->where('dokumen.event_id', $eventId);
        }

        return $builder->orderBy('dokumen.uploaded_at', 'DESC')
                       ->orderBy('dokumen.id_dokumen', 'DESC')
                       ->findAll();
    }

    /**
     * Check apakah user sudah punya dokumen tipe tertentu untuk event
     */
    public function hasUserDocument(int $userId, int $eventId, string $tipe): bool
    {
        return $this->where('id_user', $userId)
                    ->where('event_id', $eventId)
                    ->where('tipe', $tipe)
                    ->countAllResults() > 0;
    }

    /**
     * Get statistics per event
     */
    public function getEventDocumentStats(int $eventId): array
    {
        $stats = $this->select('
            tipe,
            COUNT(*) as count
        ')
        ->where('event_id', $eventId)
        ->groupBy('tipe')
        ->findAll();

        $result = [
            'loa' => 0,
            'sertifikat' => 0
        ];

        foreach ($stats as $stat) {
            $result[$stat['tipe']] = (int) $stat['count'];
        }

        return $result;
    }

    /**
     * FIXED: Get users eligible for LOA (presenters with verified payment)
     */
    public function getEligiblePresentersForLOA(int $eventId): array
    {
        $db = \Config\Database::connect();
        
        return $db->query("
            SELECT DISTINCT 
                u.id_user,
                u.nama_lengkap,
                u.email,
                u.role,
                p.tanggal_bayar,
                p.verified_at,
                p.participation_type
            FROM users u
            JOIN pembayaran p ON p.id_user = u.id_user
            WHERE p.event_id = ?
            AND p.status = 'verified'
            AND u.role = 'presenter'
            AND NOT EXISTS (
                SELECT 1 FROM dokumen d 
                WHERE d.id_user = u.id_user 
                AND d.event_id = ? 
                AND d.tipe = 'loa'
            )
            ORDER BY u.nama_lengkap
        ", [$eventId, $eventId])->getResultArray();
    }

    /**
     * FIXED: Get users eligible for Certificate (all attendees: presenter, audience online, audience offline)
     */
    public function getEligibleUsersForCertificate(int $eventId): array
    {
        $db = \Config\Database::connect();
        
        return $db->query("
            SELECT DISTINCT 
                u.id_user,
                u.nama_lengkap,
                u.email,
                u.role,
                a.waktu_scan,
                p.participation_type
            FROM users u
            JOIN absensi a ON a.id_user = u.id_user
            LEFT JOIN pembayaran p ON p.id_user = u.id_user AND p.event_id = a.event_id
            WHERE a.event_id = ?
            AND a.status = 'hadir'
            AND NOT EXISTS (
                SELECT 1 FROM dokumen d 
                WHERE d.id_user = u.id_user 
                AND d.event_id = ? 
                AND d.tipe = 'sertifikat'
            )
            ORDER BY u.role, u.nama_lengkap
        ", [$eventId, $eventId])->getResultArray();
    }

    /**
     * Backward compatibility methods
     */
    public function listSertifikatByUser(int $userId): array
    {
        return $this->listSertifikatByUserEvent($userId);
    }

    public function listSertifikatByUse(int $userId): array
    {
        return $this->listSertifikatByUserEvent($userId);
    }

    /**
     * Bulk insert documents
     */
    public function bulkInsertDocuments(array $documents): int
    {
        if (empty($documents)) {
            return 0;
        }

        $this->db->transStart();
        
        try {
            $insertCount = 0;
            
            foreach ($documents as $doc) {
                // Validate required fields
                if (!isset($doc['id_user'], $doc['tipe'], $doc['file_path'])) {
                    continue;
                }
                
                // Set default values
                $doc['uploaded_at'] = $doc['uploaded_at'] ?? date('Y-m-d H:i:s');
                $doc['syarat'] = $doc['syarat'] ?? '';
                
                if ($this->insert($doc)) {
                    $insertCount++;
                }
            }
            
            $this->db->transComplete();
            
            return $this->db->transStatus() !== FALSE ? $insertCount : 0;
            
        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Bulk insert documents failed: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Clean up orphaned files (files not in database)
     */
    public function cleanupOrphanedFiles(): array
    {
        $uploadDirs = [
            WRITEPATH . 'uploads/loa/',
            WRITEPATH . 'uploads/sertifikat/'
        ];
        
        $cleanedFiles = [];
        
        foreach ($uploadDirs as $dir) {
            if (!is_dir($dir)) continue;
            
            $files = array_diff(scandir($dir), ['.', '..', '.htaccess', 'index.html']);
            
            foreach ($files as $file) {
                $filePath = $dir . $file;
                if (is_file($filePath)) {
                    // Check if file exists in database
                    $exists = $this->like('file_path', $file)->countAllResults() > 0;
                    
                    if (!$exists) {
                        unlink($filePath);
                        $cleanedFiles[] = $filePath;
                    }
                }
            }
        }
        
        return $cleanedFiles;
    }

    /**
     * ADDED: Get document file path for download
     */
    public function getDocumentFilePath(int $idDokumen): ?string
    {
        $document = $this->find($idDokumen);
        
        if (!$document) {
            return null;
        }

        $basePath = WRITEPATH . 'uploads/' . $document['tipe'] . '/';
        $filePath = $basePath . $document['file_path'];

        return file_exists($filePath) ? $filePath : null;
    }

    /**
     * ADDED: Get document statistics for dashboard
     */
    public function getDocumentStatistics(): array
    {
        $total = $this->countAll();
        $loa = $this->where('tipe', 'loa')->countAllResults();
        $sertifikat = $this->where('tipe', 'sertifikat')->countAllResults();
        
        // Recent uploads (this week)
        $weekAgo = date('Y-m-d H:i:s', strtotime('-1 week'));
        $recent = $this->where('uploaded_at >=', $weekAgo)->countAllResults();

        return [
            'total_documents' => $total,
            'loa_count' => $loa,
            'sertifikat_count' => $sertifikat,
            'recent_uploads' => $recent
        ];
    }

    /**
     * ADDED: Get documents by date range
     */
    public function getDocumentsByDateRange(string $startDate, string $endDate, ?string $tipe = null): array
    {
        $builder = $this->select('
            dokumen.*, 
            users.nama_lengkap, 
            users.email, 
            events.title as event_title
        ')
        ->join('users', 'users.id_user = dokumen.id_user', 'left')
        ->join('events', 'events.id = dokumen.event_id', 'left')
        ->where('DATE(dokumen.uploaded_at) >=', $startDate)
        ->where('DATE(dokumen.uploaded_at) <=', $endDate);

        if ($tipe) {
            $builder->where('dokumen.tipe', $tipe);
        }

        return $builder->orderBy('dokumen.uploaded_at', 'DESC')->findAll();
    }

    /**
     * ADDED: Search documents
     */
    public function searchDocuments(string $keyword, ?string $tipe = null, ?int $eventId = null): array
    {
        $builder = $this->select('
            dokumen.*, 
            users.nama_lengkap, 
            users.email, 
            events.title as event_title
        ')
        ->join('users', 'users.id_user = dokumen.id_user', 'left')
        ->join('events', 'events.id = dokumen.event_id', 'left')
        ->groupStart()
            ->like('users.nama_lengkap', $keyword)
            ->orLike('users.email', $keyword)
            ->orLike('events.title', $keyword)
            ->orLike('dokumen.file_path', $keyword)
        ->groupEnd();

        if ($tipe) {
            $builder->where('dokumen.tipe', $tipe);
        }

        if ($eventId) {
            $builder->where('dokumen.event_id', $eventId);
        }

        return $builder->orderBy('dokumen.uploaded_at', 'DESC')->findAll();
    }
}