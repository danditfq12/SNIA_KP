<?php

namespace App\Controllers\Role\Admin;

use App\Controllers\BaseController;
use App\Models\EventModel;
use App\Models\PembayaranModel;
use CodeIgniter\I18n\Time;

class Event extends BaseController
{
    protected $db;
    protected $eventModel;
    protected $pembayaranModel;

    public function __construct()
    {
        $this->db = \Config\Database::connect();
        $this->eventModel = new EventModel();
        $this->pembayaranModel = new PembayaranModel();
    }

    /**
     * INDEX - List all events with statistics
     */
    public function index()
    {
        $events = $this->eventModel->getEventsWithStats();
        
        // Ensure registration_waves is properly decoded for each event
        foreach ($events as &$event) {
            if (isset($event['registration_waves'])) {
                if (is_string($event['registration_waves'])) {
                    $event['registration_waves'] = json_decode($event['registration_waves'], true) ?: [];
                } elseif (!is_array($event['registration_waves'])) {
                    $event['registration_waves'] = [];
                }
            } else {
                $event['registration_waves'] = [];
            }
        }
        unset($event);
        
        // Calculate overall statistics
        $stats = $this->db->query("
            SELECT 
                COUNT(DISTINCT e.id) as total_events,
                COUNT(DISTINCT CASE WHEN e.is_active = true THEN e.id END) as active_events,
                COUNT(DISTINCT CASE WHEN p.status = 'verified' THEN p.id_pembayaran END) as verified_registrations,
                COALESCE(SUM(CASE WHEN p.status = 'verified' THEN p.jumlah ELSE 0 END), 0) as total_revenue
            FROM events e
            LEFT JOIN pembayaran p ON p.event_id = e.id
        ")->getRowArray();
        
        return view('role/admin/event/index', [
            'title' => 'Kelola Event',
            'events' => $events,
            'stats' => $stats
        ]);
    }

    /**
     * CREATE - Add new event
     */
    public function create()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->back()->with('error', 'Invalid request');
        }

        log_message('info', '=== CREATE EVENT REQUEST ===');
        log_message('info', 'POST data: ' . json_encode($this->request->getPost()));

        $rules = [
            'title' => 'required|min_length[3]|max_length[255]',
            'description' => 'permit_empty',
            'event_date' => 'required|valid_date',
            'event_time' => 'required',
            'event_end_date' => 'required|valid_date',
            'event_end_time' => 'required',
            'format' => 'required|in_list[both,online,offline]',
            'location' => 'permit_empty',
            'zoom_link' => 'permit_empty',
        ];

        if (!$this->validate($rules)) {
            log_message('error', 'Validation failed: ' . json_encode($this->validator->getErrors()));
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $this->validator->getErrors()
            ]);
        }

        // Validate multi-day event
        $eventDate = $this->request->getPost('event_date');
        $eventEndDate = $this->request->getPost('event_end_date');
        
        if (strtotime($eventEndDate) < strtotime($eventDate)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Tanggal selesai harus setelah atau sama dengan tanggal mulai'
            ]);
        }

        // Convert checkbox to boolean for PostgreSQL
        $isActive = $this->request->getPost('is_active') ? true : false;

        // HELPER: Convert empty string to NULL for datetime fields
        $abstractDeadline = $this->request->getPost('abstract_deadline');
        $abstractRevisionDeadline = $this->request->getPost('abstract_revision_deadline');
        $fullPaperDeadline = $this->request->getPost('full_paper_deadline');

        $data = [
            'title' => $this->request->getPost('title'),
            'description' => $this->request->getPost('description') ?? '',
            'event_date' => $eventDate,
            'event_time' => $this->request->getPost('event_time'),
            'event_end_date' => $eventEndDate,
            'event_end_time' => $this->request->getPost('event_end_time'),
            'format' => $this->request->getPost('format'),
            'location' => $this->request->getPost('location') ?: null,
            'zoom_link' => $this->request->getPost('zoom_link') ?: null,
            'abstract_deadline' => !empty($abstractDeadline) ? $abstractDeadline : null,
            'abstract_revision_deadline' => !empty($abstractRevisionDeadline) ? $abstractRevisionDeadline : null,
            'full_paper_deadline' => !empty($fullPaperDeadline) ? $fullPaperDeadline : null,
            'registration_active' => true,
            'abstract_submission_active' => false,
            'abstract_revision_active' => false,
            'full_paper_submission_active' => false,
            'is_active' => $isActive,
            'registration_waves' => json_encode([]),
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s')
        ];

        log_message('info', 'Data to insert: ' . json_encode($data));

        try {
            $this->eventModel->skipValidation(true);
            $eventId = $this->eventModel->insert($data);
            $this->eventModel->skipValidation(false);
            
            if ($eventId) {
                log_message('info', 'Event created successfully with ID: ' . $eventId);
                $this->logActivity('Membuat event baru: ' . $data['title']);
                
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Event berhasil dibuat! Silakan atur gelombang pendaftaran.',
                    'event_id' => $eventId
                ]);
            } else {
                $errors = $this->eventModel->errors();
                log_message('error', 'Model insert failed. Errors: ' . json_encode($errors));
                
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Gagal menyimpan event',
                    'errors' => $errors
                ]);
            }
        } catch (\Exception $e) {
            log_message('error', 'Exception: ' . $e->getMessage());
            log_message('error', 'Trace: ' . $e->getTraceAsString());
            
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * GET - Get event for editing
     */
    public function getEvent($id)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->back();
        }

        $event = $this->eventModel->find($id);
        
        if (!$event) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Event tidak ditemukan'
            ]);
        }

        // Ensure registration_waves is decoded
        if (isset($event['registration_waves']) && is_string($event['registration_waves'])) {
            $event['registration_waves'] = json_decode($event['registration_waves'], true) ?: [];
        }

        return $this->response->setJSON([
            'success' => true,
            'event' => $event
        ]);
    }

    /**
     * UPDATE 
     */
    public function update($id)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->back();
        }

        $event = $this->eventModel->find($id);
        if (!$event) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Event tidak ditemukan'
            ]);
        }

        $rules = [
            'title' => 'required|min_length[3]|max_length[255]',
            'event_date' => 'required|valid_date',
            'event_time' => 'required',
            'event_end_date' => 'required|valid_date',
            'event_end_time' => 'required',
            'format' => 'required|in_list[both,online,offline]',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors' => $this->validator->getErrors()
            ]);
        }

        // Validate multi-day event
        $eventDate = $this->request->getPost('event_date');
        $eventEndDate = $this->request->getPost('event_end_date');
        
        if (strtotime($eventEndDate) < strtotime($eventDate)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Tanggal selesai harus setelah atau sama dengan tanggal mulai'
            ]);
        }

        // Convert checkbox to boolean for PostgreSQL
        $isActive = $this->request->getPost('is_active') ? true : false;

        $data = [
            'title' => $this->request->getPost('title'),
            'description' => $this->request->getPost('description') ?? '',
            'event_date' => $eventDate,
            'event_time' => $this->request->getPost('event_time'),
            'event_end_date' => $eventEndDate,
            'event_end_time' => $this->request->getPost('event_end_time'),
            'format' => $this->request->getPost('format'),
            'location' => $this->request->getPost('location') ?: null,
            'zoom_link' => $this->request->getPost('zoom_link') ?: null,
            'is_active' => $isActive,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        try {
            if ($this->eventModel->update($id, $data)) {
                $this->logActivity('Update event: ' . $data['title']);
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Event berhasil diupdate'
                ]);
            }
            
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Gagal update event'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Update failed: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * UPDATE WAVES 
     */
    public function updateWaves($id)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->back();
        }

        $event = $this->eventModel->find($id);
        if (!$event) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Event tidak ditemukan'
            ]);
        }

        $input = $this->request->getJSON(true);
        $waves = $input['waves'] ?? [];

        log_message('info', '=== UPDATE WAVES REQUEST ===');
        log_message('info', 'Event ID: ' . $id);
        log_message('info', 'Waves input: ' . json_encode($waves));

        // Validasi minimal 1 gelombang
        if (empty($waves) || !is_array($waves)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Data gelombang tidak valid atau kosong'
            ]);
        }

        $format = $event['format'] ?? 'both';
        
        //  VALIDASI SETIAP GELOMBANG
        foreach ($waves as $i => $wave) {
            $waveNum = $i + 1;

            // 1. Required fields
            if (empty($wave['registration_start']) || empty($wave['registration_deadline'])) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => "❌ Gelombang {$waveNum}: Tanggal mulai dan deadline harus diisi"
                ]);
            }

            // 2. Parse datetime dengan jam
            $startDateTime = $wave['registration_start'];
            $endDateTime = $wave['registration_deadline'];
            
            $start = strtotime($startDateTime);
            $end = strtotime($endDateTime);

            if (!$start || !$end) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => "❌ Gelombang {$waveNum}: Format tanggal tidak valid"
                ]);
            }

            // 3. Deadline harus setelah start (minimal beda 1 jam)
            if ($end <= $start) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => "❌ Gelombang {$waveNum}: Deadline harus SETELAH tanggal mulai (minimal beda 1 jam)"
                ]);
            }

            // 4. Validate numeric fees
            $fees = [
                'presenter_fee_online',
                'presenter_fee_offline',
                'audience_fee_online',
                'audience_fee_offline'
            ];

            foreach ($fees as $fee) {
                $value = $wave[$fee] ?? 0;
                
                if (!is_numeric($value) || $value < 0) {
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => "❌ Gelombang {$waveNum}: Harga {$fee} harus berupa angka >= 0"
                    ]);
                }
                
                $waves[$i][$fee] = (float)$value;
            }

            // VALIDASI DENGAN GELOMBANG SEBELUMNYA
            if ($i > 0) {
                $prevWave = $waves[$i - 1];
                $prevEnd = strtotime($prevWave['registration_deadline']);
                $currentStart = strtotime($wave['registration_start']);
                
                // a) Start gelombang baru harus SETELAH end gelombang sebelumnya (minimal beda 1 jam)
                if ($currentStart <= $prevEnd) {
                    $prevEndFormatted = date('d M Y H:i', $prevEnd);
                    $currentStartFormatted = date('d M Y H:i', $currentStart);
                    
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => "❌ Gelombang {$waveNum} harus dimulai SETELAH gelombang " . ($i) . " selesai!\n\n" .
                                   "Gelombang " . ($i) . " selesai: {$prevEndFormatted}\n" .
                                   "Gelombang {$waveNum} mulai: {$currentStartFormatted}\n\n" .
                                   "Minimal beda 1 jam!"
                    ]);
                }
                
                //  HARGA HARUS NAIK untuk format offline/both
                if ($format !== 'online') {
                    $prevPresOff = (float)$prevWave['presenter_fee_offline'];
                    $currPresOff = (float)$wave['presenter_fee_offline'];
                    $prevAudOff = (float)$prevWave['audience_fee_offline'];
                    $currAudOff = (float)$wave['audience_fee_offline'];
                    
                    // Presenter Offline harus naik
                    if ($currPresOff <= $prevPresOff && $currPresOff > 0) {
                        return $this->response->setJSON([
                            'success' => false,
                            'message' => "❌ Gelombang {$waveNum} (Presenter Offline): Harga HARUS NAIK!\n\n" .
                                       "Gelombang " . ($i) . ": Rp " . number_format($prevPresOff, 0, ',', '.') . "\n" .
                                       "Gelombang {$waveNum}: Rp " . number_format($currPresOff, 0, ',', '.') . "\n\n" .
                                       "Harga gelombang berikutnya harus lebih tinggi!"
                        ]);
                    }
                    
                    // Audience Offline harus naik
                    if ($currAudOff <= $prevAudOff && $currAudOff > 0) {
                        return $this->response->setJSON([
                            'success' => false,
                            'message' => "❌ Gelombang {$waveNum} (Audience Offline): Harga HARUS NAIK!\n\n" .
                                       "Gelombang " . ($i) . ": Rp " . number_format($prevAudOff, 0, ',', '.') . "\n" .
                                       "Gelombang {$waveNum}: Rp " . number_format($currAudOff, 0, ',', '.') . "\n\n" .
                                       "Harga gelombang berikutnya harus lebih tinggi!"
                        ]);
                    }
                }
                
                //  Online juga harus naik
                $prevPresOn = (float)$prevWave['presenter_fee_online'];
                $currPresOn = (float)$wave['presenter_fee_online'];
                $prevAudOn = (float)$prevWave['audience_fee_online'];
                $currAudOn = (float)$wave['audience_fee_online'];
                
                if ($currPresOn < $prevPresOn && $currPresOn > 0) {
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => "❌ Gelombang {$waveNum} (Presenter Online): Harga TIDAK BOLEH TURUN!\n\n" .
                                   "Gelombang " . ($i) . ": Rp " . number_format($prevPresOn, 0, ',', '.') . "\n" .
                                   "Gelombang {$waveNum}: Rp " . number_format($currPresOn, 0, ',', '.')
                    ]);
                }
                
                if ($currAudOn < $prevAudOn && $currAudOn > 0) {
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => "❌ Gelombang {$waveNum} (Audience Online): Harga TIDAK BOLEH TURUN!\n\n" .
                                   "Gelombang " . ($i) . ": Rp " . number_format($prevAudOn, 0, ',', '.') . "\n" .
                                   "Gelombang {$waveNum}: Rp " . number_format($currAudOn, 0, ',', '.')
                    ]);
                }
            }

            // VALIDASI EARLY BIRD (Gelombang 1 harus termurah untuk offline)
            if ($waveNum === 1 && count($waves) > 1 && $format !== 'online') {
                $wave1PresOff = (float)$waves[0]['presenter_fee_offline'];
                $wave1AudOff = (float)$waves[0]['audience_fee_offline'];

                for ($j = 1; $j < count($waves); $j++) {
                    $comparePresOff = (float)($waves[$j]['presenter_fee_offline'] ?? 0);
                    $compareAudOff = (float)($waves[$j]['audience_fee_offline'] ?? 0);

                    // Gelombang 1 HARUS LEBIH MURAH
                    if ($wave1PresOff >= $comparePresOff && $comparePresOff > 0) {
                        return $this->response->setJSON([
                            'success' => false,
                            'message' => "❌ EARLY BIRD: Gelombang 1 (Presenter Offline) harus TERMURAH!\n\n" .
                                       "Gelombang 1: Rp " . number_format($wave1PresOff, 0, ',', '.') . "\n" .
                                       "Gelombang " . ($j + 1) . ": Rp " . number_format($comparePresOff, 0, ',', '.') . "\n\n" .
                                       "Gelombang pertama harus lebih murah sebagai Early Bird!"
                        ]);
                    }

                    if ($wave1AudOff >= $compareAudOff && $compareAudOff > 0) {
                        return $this->response->setJSON([
                            'success' => false,
                            'message' => "❌ EARLY BIRD: Gelombang 1 (Audience Offline) harus TERMURAH!\n\n" .
                                       "Gelombang 1: Rp " . number_format($wave1AudOff, 0, ',', '.') . "\n" .
                                       "Gelombang " . ($j + 1) . ": Rp " . number_format($compareAudOff, 0, ',', '.') . "\n\n" .
                                       "Gelombang pertama harus lebih murah sebagai Early Bird!"
                        ]);
                    }
                }
            }
        }

        // ✅ SAVE TO DATABASE
        try {
            log_message('info', 'Saving ' . count($waves) . ' waves to DB: ' . json_encode($waves));

            $data = [
                'registration_waves' => json_encode($waves, JSON_UNESCAPED_UNICODE),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            if ($this->eventModel->update($id, $data)) {
                $this->logActivity('Update gelombang pendaftaran: ' . $event['title'] . ' (' . count($waves) . ' gelombang)');
                
                log_message('info', ' Waves saved successfully');
                
                return $this->response->setJSON([
                    'success' => true,
                    'message' => ' ' . count($waves) . ' gelombang berhasil disimpan!'
                ]);
            }
            
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Gagal update gelombang ke database'
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Update waves failed: ' . $e->getMessage());
            log_message('error', 'Trace: ' . $e->getTraceAsString());
            
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * TOGGLE STATUS - Activate/deactivate event
     */
    public function toggleStatus($id)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->back();
        }

        $event = $this->eventModel->find($id);
        if (!$event) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Event tidak ditemukan'
            ]);
        }

        $newStatus = $event['is_active'] ? false : true;

        try {
            if ($this->eventModel->update($id, ['is_active' => $newStatus])) {
                $text = $newStatus ? 'diaktifkan' : 'dinonaktifkan';
                $this->logActivity('Toggle status event: ' . $event['title'] . ' - ' . $text);
                
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Event berhasil ' . $text
                ]);
            }
            
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Gagal ubah status'
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * DELETE - Force delete event
     */
    public function delete($id)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->back();
        }

        $event = $this->eventModel->find($id);
        if (!$event) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Event tidak ditemukan'
            ]);
        }

        log_message('info', '=== FORCE DELETE EVENT ===');
        log_message('info', 'Event ID: ' . $id . ' - ' . $event['title']);

        // Count participants
        $totalParticipants = $this->db->table('pembayaran')
            ->where('event_id', $id)
            ->countAllResults();

        $verifiedParticipants = $this->db->table('pembayaran')
            ->where('event_id', $id)
            ->where('status', 'verified')
            ->countAllResults();

        log_message('warning', "Attempting to delete event with {$totalParticipants} total participants ({$verifiedParticipants} verified)");

        try {
            $this->db->query('SET CONSTRAINTS ALL DEFERRED');
            
            $deletedAbstrak = 0;
            $deletedAbsensi = 0;
            $deletedPembayaran = 0;
            $deletedRegistrations = 0;
            
            // Delete related data
            if ($this->db->tableExists('event_registrations')) {
                $columnNames = ['id_event', 'events_id', 'event_id'];
                foreach ($columnNames as $colName) {
                    try {
                        $this->db->query("DELETE FROM event_registrations WHERE {$colName} = ?", [$id]);
                        $deletedRegistrations = $this->db->affectedRows();
                        if ($deletedRegistrations > 0) break;
                    } catch (\Exception $e) {
                        continue;
                    }
                }
            }

            if ($this->db->tableExists('abstrak')) {
                $this->db->query("DELETE FROM abstrak WHERE event_id = ?", [$id]);
                $deletedAbstrak = $this->db->affectedRows();
            }

            if ($this->db->tableExists('absensi')) {
                $this->db->query("DELETE FROM absensi WHERE event_id = ?", [$id]);
                $deletedAbsensi = $this->db->affectedRows();
            }

            $this->db->query("DELETE FROM pembayaran WHERE event_id = ?", [$id]);
            $deletedPembayaran = $this->db->affectedRows();
            
            $this->db->query("DELETE FROM events WHERE id = ?", [$id]);
            $deletedEvent = $this->db->affectedRows();

            $this->db->query('SET CONSTRAINTS ALL IMMEDIATE');

            if ($deletedEvent === 0) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Gagal menghapus event dari database'
                ]);
            }

            $logMessage = " FORCE DELETE Event: {$event['title']}";
            if ($totalParticipants > 0) {
                $logMessage .= " | Menghapus {$totalParticipants} peserta total ({$verifiedParticipants} verified)";
            }
            $this->logActivity($logMessage);

            $message = 'Event berhasil dihapus';
            if ($verifiedParticipants > 0) {
                $message .= " beserta {$verifiedParticipants} peserta verified dan {$totalParticipants} total peserta";
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => $message,
                'deleted_stats' => [
                    'total_participants' => $totalParticipants,
                    'verified_participants' => $verifiedParticipants,
                    'deleted_registrations' => $deletedRegistrations,
                    'deleted_abstracts' => $deletedAbstrak,
                    'deleted_payments' => $deletedPembayaran,
                    'deleted_attendance' => $deletedAbsensi
                ]
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Delete failed: ' . $e->getMessage());
            
            try {
                $this->db->query('SET CONSTRAINTS ALL IMMEDIATE');
            } catch (\Exception $e2) {
                log_message('error', 'Failed to re-enable constraints: ' . $e2->getMessage());
            }
            
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error menghapus event: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Log activity helper
     */
    private function logActivity($activity)
    {
        try {
            $userId = session()->get('id_user');
            if ($userId) {
                $this->db->table('log_aktivitas')->insert([
                    'id_user' => $userId,
                    'aktivitas' => $activity,
                    'waktu' => date('Y-m-d H:i:s')
                ]);
            }
        } catch (\Exception $e) {
            log_message('warning', 'Log activity failed: ' . $e->getMessage());
        }
    }
}