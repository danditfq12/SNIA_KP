<?php
namespace App\Controllers\Role\Audience;

use App\Controllers\BaseController;
use App\Models\DokumenModel;
use App\Models\PembayaranModel;
use App\Models\EventModel;

class Dokumen extends BaseController
{
    protected DokumenModel $dm;
    protected PembayaranModel $payM;
    protected EventModel $eventM;

    public function __construct()
    {
        $this->dm     = new DokumenModel();
        $this->payM   = new PembayaranModel();
        $this->eventM = new EventModel();
    }

    private function uid(): int
    {
        return (int) (session('id_user') ?? 0);
    }

    /**
     * Index dokumen - menampilkan semua dokumen yang bisa diakses user
     */
    public function index()
    {
        $idUser = $this->uid();
        if (!$idUser) return redirect()->to(site_url('auth/login'));

        // 1. Ambil dokumen personal (sertifikat milik user)
        $personalDocs = $this->dm
            ->select('dokumen.*, events.title as event_title')
            ->join('events', 'events.id = dokumen.event_id', 'left')
            ->where('dokumen.id_user', $idUser)
            ->whereIn('dokumen.tipe', ['Sertifikat','sertifikat','CERTIFICATE','Certificate'])
            ->orderBy('dokumen.uploaded_at', 'DESC')
            ->findAll();

        // 2. Ambil event_id yang diikuti user
        $userEventIds = $this->getUserEventIds($idUser);

        // 3. Ambil dokumen lainnya dari event yang diikuti
        $sharedDocs = [];
        if (!empty($userEventIds)) {
            $sharedDocs = $this->dm
                ->select('dokumen.*, events.title as event_title')
                ->join('events', 'events.id = dokumen.event_id', 'left')
                ->where('dokumen.id_user', $idUser)
                ->where('dokumen.tipe', 'lainnya')
                ->whereIn('dokumen.event_id', $userEventIds)
                ->orderBy('dokumen.uploaded_at', 'DESC')
                ->findAll();
        }

        // 4. Gabungkan semua dokumen
        $allDocuments = array_merge($personalDocs, $sharedDocs);

        // Sort by uploaded_at DESC
        usort($allDocuments, function($a, $b) {
            return strtotime($b['uploaded_at']) - strtotime($a['uploaded_at']);
        });

        // Statistik dokumen user
        $stats = [
            'total_documents' => count($allDocuments),
            'sertifikat_count' => count($personalDocs),
            'shared_documents' => count($sharedDocs),
            'recent_uploads' => count(array_filter($allDocuments, function($doc) {
                return strtotime($doc['uploaded_at']) > strtotime('-1 week');
            }))
        ];

        return view('role/audience/dokumen/index', [
            'title' => 'Dokumen Saya',
            'documents' => $allDocuments,
            'stats' => $stats,
        ]);
    }

    /** 
     * List sertifikat milik user + dokumen lainnya (dokumen pendukung)
     */
    public function sertifikat()
    {
        $idUser = $this->uid();
        if (!$idUser) return redirect()->to(site_url('auth/login'));

        // 1. Ambil semua sertifikat user
        $certs = $this->dm
            ->select('dokumen.*, events.title as event_title, events.event_date')
            ->join('events', 'events.id = dokumen.event_id', 'left')
            ->where('dokumen.id_user', $idUser)
            ->whereIn('dokumen.tipe', ['Sertifikat','sertifikat','CERTIFICATE','Certificate'])
            ->orderBy('dokumen.uploaded_at', 'DESC')
            ->findAll();

        // 2. Ambil event_id yang diikuti user
        $userEventIds = $this->getUserEventIds($idUser);

        // 3. Ambil dokumen lainnya (dokumen pendukung) dari event yang diikuti
        $otherDocs = [];
        if (!empty($userEventIds)) {
            $otherDocs = $this->dm
                ->select('dokumen.*, events.title as event_title, events.event_date')
                ->join('events', 'events.id = dokumen.event_id', 'left')
                ->where('dokumen.id_user', $idUser)
                ->where('dokumen.tipe', 'lainnya')
                ->whereIn('dokumen.event_id', $userEventIds)
                ->orderBy('dokumen.uploaded_at', 'DESC')
                ->findAll();
        }

        // 4. Gabungkan dan kelompokkan per event
        $allDocs = array_merge($certs, $otherDocs);
        
        // Group by event
        $docsByEvent = [];
        foreach ($allDocs as $doc) {
            $eventId = (int)($doc['event_id'] ?? 0);
            $eventTitle = $doc['event_title'] ?? 'Event Tidak Diketahui';
            
            if (!isset($docsByEvent[$eventId])) {
                $docsByEvent[$eventId] = [
                    'event_id' => $eventId,
                    'event_title' => $eventTitle,
                    'event_date' => $doc['event_date'] ?? null,
                    'sertifikat' => [],
                    'lainnya' => []
                ];
            }
            
            $tipe = strtolower($doc['tipe']);
            if (in_array($tipe, ['sertifikat', 'certificate'])) {
                $docsByEvent[$eventId]['sertifikat'][] = $doc;
            } else {
                $docsByEvent[$eventId]['lainnya'][] = $doc;
            }
        }

        // Sort by event date
        uasort($docsByEvent, function($a, $b) {
            $dateA = strtotime($a['event_date'] ?? '');
            $dateB = strtotime($b['event_date'] ?? '');
            return $dateB - $dateA;
        });

        $stats = [
            'total_sertifikat' => count($certs),
            'total_lainnya' => count($otherDocs),
            'total_documents' => count($allDocs),
            'total_events' => count($docsByEvent)
        ];

        return view('role/audience/dokumen/sertifikat', [
            'title' => 'Sertifikat & Dokumen Saya',
            'docsByEvent' => $docsByEvent,
            'stats' => $stats,
        ]);
    }

    /**
     * Download dokumen berdasarkan ID
     */
    public function download($idDokumen)
    {
        $idUser = $this->uid();
        if (!$idUser) return redirect()->to(site_url('auth/login'));
        
        // Ambil dokumen milik user
        $document = $this->dm
            ->select('dokumen.*, events.title as event_title')
            ->join('events', 'events.id = dokumen.event_id', 'left')
            ->where('dokumen.id_dokumen', $idDokumen)
            ->where('dokumen.id_user', $idUser)
            ->first();

        if (!$document) {
            return redirect()->back()->with('error', 'Dokumen tidak ditemukan atau bukan milik Anda.');
        }

        // Tentukan path file berdasarkan tipe dokumen
        $tipe = strtolower($document['tipe']);
        $basePath = match($tipe) {
            'lainnya' => WRITEPATH . 'uploads/lainnya/',
            'loa' => WRITEPATH . 'uploads/loa/',
            default => WRITEPATH . 'uploads/sertifikat/',
        };
        
        $filePath = $basePath . $document['file_path'];

        if (!file_exists($filePath)) {
            return redirect()->back()->with('error', 'File tidak ditemukan di server.');
        }

        // Generate nama download
        $eventTitle = $document['event_title'] ? preg_replace('/[^A-Za-z0-9_-]/', '_', $document['event_title']) : 'Event';
        $extension = pathinfo($document['file_path'], PATHINFO_EXTENSION);
        
        if ($tipe === 'lainnya') {
            $docName = preg_replace('/[^A-Za-z0-9_-]/', '_', $document['syarat'] ?? 'Document');
            $downloadName = $docName . '_' . $eventTitle . '.' . $extension;
        } else {
            $downloadName = strtoupper($tipe) . '_' . $eventTitle . '_' . date('Y-m-d', strtotime($document['uploaded_at'])) . '.' . $extension;
        }

        return $this->response->download($filePath, null)->setFileName($downloadName);
    }

    /**
     * Download sertifikat by filename or ID
     * Supports both inline preview and download
     */
    public function downloadSertifikat($segment)
    {
        $idUser = $this->uid();
        if (!$idUser) return redirect()->to(site_url('auth/login'));

        // Try to find document by ID or filename
        if (ctype_digit((string)$segment)) {
            $doc = $this->dm
                ->where('id_user', $idUser)
                ->where('id_dokumen', (int)$segment)
                ->first();
        } else {
            $base = basename((string)$segment);
            $doc = $this->dm
                ->where('id_user', $idUser)
                ->groupStart()
                    ->where('file_path', $segment)
                    ->orWhere('file_path', $base)
                ->groupEnd()
                ->first();
        }

        if (!$doc) {
            return redirect()->back()->with('error', 'Dokumen tidak ditemukan atau bukan milik Anda.');
        }

        $tipe = strtolower($doc['tipe']);
        $basePath = match($tipe) {
            'lainnya' => WRITEPATH . 'uploads/lainnya/',
            'loa' => WRITEPATH . 'uploads/loa/',
            default => WRITEPATH . 'uploads/sertifikat/',
        };

        $abs = $basePath . $doc['file_path'];

        if (!is_file($abs)) {
            return redirect()->back()->with('error', 'File tidak ditemukan di server.');
        }

        // Check if preview mode
        $pv = strtolower((string)$this->request->getGet('preview'));
        $wantInline = in_array($pv, ['1','true','yes'], true);

        $ext  = strtolower(pathinfo($abs, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'pdf'        => 'application/pdf',
            'jpg','jpeg' => 'image/jpeg',
            'png'        => 'image/png',
            default      => 'application/octet-stream',
        };

        // If preview mode and supported format
        if ($wantInline && in_array($mime, ['application/pdf','image/jpeg','image/png'], true)) {
            if (function_exists('apache_setenv')) { @apache_setenv('no-gzip', '1'); }
            if (function_exists('ini_set')) { @ini_set('zlib.output_compression', '0'); }
            if (ob_get_length()) { @ob_end_clean(); }

            $this->response->setHeader('Content-Type', $mime);
            $this->response->setHeader('Content-Disposition', 'inline; filename="'.basename($abs).'"');
            $this->response->setHeader('X-Content-Type-Options', 'nosniff');
            $this->response->setHeader('Cache-Control', 'private, max-age=0, must-revalidate');
            $this->response->setHeader('Pragma', 'public');

            return $this->response->setBody(file_get_contents($abs));
        }

        // Download mode
        return $this->response->download($abs, null)->setFileName(basename($abs));
    }

    /**
     * Preview dokumen (inline viewing)
     */
    public function preview($idDokumen)
    {
        $idUser = $this->uid();
        if (!$idUser) return redirect()->to(site_url('auth/login'));
        
        $document = $this->dm
            ->where('id_dokumen', $idDokumen)
            ->where('id_user', $idUser)
            ->first();

        if (!$document) {
            return redirect()->back()->with('error', 'Dokumen tidak ditemukan atau bukan milik Anda.');
        }

        $tipe = strtolower($document['tipe']);
        $basePath = match($tipe) {
            'lainnya' => WRITEPATH . 'uploads/lainnya/',
            'loa' => WRITEPATH . 'uploads/loa/',
            default => WRITEPATH . 'uploads/sertifikat/',
        };
        
        $filePath = $basePath . $document['file_path'];

        if (!file_exists($filePath)) {
            return redirect()->back()->with('error', 'File tidak ditemukan di server.');
        }

        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $mime = match ($ext) {
            'pdf' => 'application/pdf',
            'jpg', 'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            default => 'application/octet-stream',
        };

        if (!in_array($mime, ['application/pdf', 'image/jpeg', 'image/png', 'image/gif'])) {
            return redirect()->back()->with('error', 'File ini tidak dapat di-preview. Silakan download untuk melihat.');
        }

        $this->response->setHeader('Content-Type', $mime);
        $this->response->setHeader('Content-Disposition', 'inline; filename="'.basename($filePath).'"');
        $this->response->setHeader('X-Content-Type-Options', 'nosniff');
        $this->response->setHeader('Cache-Control', 'private, max-age=3600');

        return $this->response->setBody(file_get_contents($filePath));
    }

    /**
     * Get document statistics (AJAX endpoint)
     */
    public function getStats()
    {
        $idUser = $this->uid();
        if (!$idUser) {
            return $this->response->setJSON(['error' => 'Unauthorized'])->setStatusCode(401);
        }

        $totalDocs = $this->dm
            ->where('id_user', $idUser)
            ->whereIn('tipe', ['Sertifikat','sertifikat','CERTIFICATE','Certificate'])
            ->countAllResults();

        $recentDocs = $this->dm
            ->where('id_user', $idUser)
            ->whereIn('tipe', ['Sertifikat','sertifikat','CERTIFICATE','Certificate'])
            ->where('uploaded_at >=', date('Y-m-d H:i:s', strtotime('-1 month')))
            ->countAllResults();

        $userEventIds = $this->getUserEventIds($idUser);
        $sharedDocs = 0;
        if (!empty($userEventIds)) {
            $sharedDocs = $this->dm
                ->where('id_user', $idUser)
                ->where('tipe', 'lainnya')
                ->whereIn('event_id', $userEventIds)
                ->countAllResults();
        }

        return $this->response->setJSON([
            'total_sertifikat' => $totalDocs,
            'shared_documents' => $sharedDocs,
            'total_accessible' => $totalDocs + $sharedDocs,
            'recent_uploads' => $recentDocs
        ]);
    }

    /**
     * Helper: Get event IDs yang diikuti user
     * FIXED: Auto-detect column names
     */
    private function getUserEventIds(int $idUser): array
    {
        $eventIds = [];
        $db = \Config\Database::connect();

        try {
            // 1. Dari tabel pembayaran
            $paymentTables = ['pembayaran', 'payments', 'payment', 'transaksi'];
            $paymentTable = null;
            
            foreach ($paymentTables as $table) {
                if ($db->tableExists($table)) {
                    $paymentTable = $table;
                    break;
                }
            }

            if ($paymentTable) {
                $fields = array_flip($db->getFieldNames($paymentTable) ?: []);
                
                // Deteksi kolom user_id
                $userIdCol = null;
                foreach (['id_user', 'user_id', 'uid'] as $col) {
                    if (isset($fields[$col])) {
                        $userIdCol = $col;
                        break;
                    }
                }

                // Deteksi kolom event_id
                $eventIdCol = null;
                foreach (['event_id', 'id_event', 'events_id'] as $col) {
                    if (isset($fields[$col])) {
                        $eventIdCol = $col;
                        break;
                    }
                }

                // Deteksi kolom status
                $statusCol = null;
                foreach (['status', 'payment_status', 'status_bayar', 'status_pembayaran'] as $col) {
                    if (isset($fields[$col])) {
                        $statusCol = $col;
                        break;
                    }
                }

                if ($userIdCol && $eventIdCol && $statusCol) {
                    $paymentEvents = $db->table($paymentTable)
                        ->select($eventIdCol)
                        ->where($userIdCol, $idUser)
                        ->where("LOWER({$statusCol})", 'verified')
                        ->get()
                        ->getResultArray();
                    
                    foreach ($paymentEvents as $pe) {
                        $eventIds[] = (int)$pe[$eventIdCol];
                    }
                }
            }

            // 2. Dari tabel event_registrations
            if ($db->tableExists('event_registrations')) {
                $fields = array_flip($db->getFieldNames('event_registrations') ?: []);
                
                $userIdCol = null;
                foreach (['id_user', 'user_id', 'uid'] as $col) {
                    if (isset($fields[$col])) {
                        $userIdCol = $col;
                        break;
                    }
                }

                $eventIdCol = null;
                foreach (['event_id', 'id_event', 'events_id'] as $col) {
                    if (isset($fields[$col])) {
                        $eventIdCol = $col;
                        break;
                    }
                }

                if ($userIdCol && $eventIdCol) {
                    $registrations = $db->table('event_registrations')
                        ->select($eventIdCol)
                        ->where($userIdCol, $idUser)
                        ->get()
                        ->getResultArray();
                    
                    foreach ($registrations as $reg) {
                        $eventIds[] = (int)$reg[$eventIdCol];
                    }
                }
            }

            // 3. Dari tabel registrations (alternatif)
            if ($db->tableExists('registrations')) {
                $fields = array_flip($db->getFieldNames('registrations') ?: []);
                
                $userIdCol = null;
                foreach (['id_user', 'user_id', 'uid'] as $col) {
                    if (isset($fields[$col])) {
                        $userIdCol = $col;
                        break;
                    }
                }

                $eventIdCol = null;
                foreach (['event_id', 'id_event', 'events_id'] as $col) {
                    if (isset($fields[$col])) {
                        $eventIdCol = $col;
                        break;
                    }
                }

                if ($userIdCol && $eventIdCol) {
                    $registrations = $db->table('registrations')
                        ->select($eventIdCol)
                        ->where($userIdCol, $idUser)
                        ->get()
                        ->getResultArray();
                    
                    foreach ($registrations as $reg) {
                        $eventIds[] = (int)$reg[$eventIdCol];
                    }
                }
            }

        } catch (\Throwable $e) {
            log_message('error', 'getUserEventIds error: ' . $e->getMessage());
        }

        return array_values(array_unique($eventIds));
    }
}