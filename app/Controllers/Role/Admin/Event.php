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

        $data = [
            'title' => $this->request->getPost('title'),
            'description' => $this->request->getPost('description') ?? '',
            'event_date' => $eventDate,
            'event_time' => $this->request->getPost('event_time'),
            'event_end_date' => $eventEndDate,
            'event_end_time' => $this->request->getPost('event_end_time'),
            'format' => $this->request->getPost('format'),
            'location' => $this->request->getPost('location') ?? null,
            'zoom_link' => $this->request->getPost('zoom_link') ?? null,
            'abstract_deadline' => $this->request->getPost('abstract_deadline') ?? null,
            'abstract_revision_deadline' => $this->request->getPost('abstract_revision_deadline') ?? null,
            'full_paper_deadline' => $this->request->getPost('full_paper_deadline') ?? null,
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
            // Disable model validation temporarily
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
     * UPDATE - Update event
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
            'location' => $this->request->getPost('location') ?? null,
            'zoom_link' => $this->request->getPost('zoom_link') ?? null,
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
     * UPDATE WAVES - Save registration waves (3 waves system)
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
        
        if (!isset($input['waves']) || !is_array($input['waves'])) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Data gelombang tidak valid'
            ]);
        }

        $waves = $input['waves'];
        
        // Validate waves must be exactly 3
        if (count($waves) !== 3) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Harus ada 3 gelombang'
            ]);
        }

        // Validate each wave
        $format = $event['format'];
        
        foreach ($waves as $i => $wave) {
            $waveNum = $i + 1;
            
            // Check required fields
            if (empty($wave['registration_start']) || empty($wave['registration_deadline'])) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => "Gelombang {$waveNum}: Tanggal harus diisi"
                ]);
            }

            // Validate dates
            $start = strtotime($wave['registration_start']);
            $end = strtotime($wave['registration_deadline']);
            
            if ($start >= $end) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => "Gelombang {$waveNum}: Tanggal mulai harus sebelum tanggal selesai"
                ]);
            }

            // ✅ ENSURE ALL FEE FIELDS HAVE PROPER VALUES BASED ON FORMAT
            if ($format === 'offline') {
                // Offline only: online fees must be 0
                $waves[$i]['presenter_fee_online'] = 0;
                $waves[$i]['audience_fee_online'] = 0;
                // Ensure offline fees exist
                $waves[$i]['presenter_fee_offline'] = (float)($wave['presenter_fee_offline'] ?? 0);
                $waves[$i]['audience_fee_offline'] = (float)($wave['audience_fee_offline'] ?? 0);
            } elseif ($format === 'online') {
                // Online only: offline fees must be 0
                $waves[$i]['presenter_fee_offline'] = 0;
                $waves[$i]['audience_fee_offline'] = 0;
                // Ensure online fees exist
                $waves[$i]['presenter_fee_online'] = (float)($wave['presenter_fee_online'] ?? 0);
                $waves[$i]['audience_fee_online'] = (float)($wave['audience_fee_online'] ?? 0);
            } else {
                // Both: all fees must exist
                $waves[$i]['presenter_fee_online'] = (float)($wave['presenter_fee_online'] ?? 0);
                $waves[$i]['presenter_fee_offline'] = (float)($wave['presenter_fee_offline'] ?? 0);
                $waves[$i]['audience_fee_online'] = (float)($wave['audience_fee_online'] ?? 0);
                $waves[$i]['audience_fee_offline'] = (float)($wave['audience_fee_offline'] ?? 0);
            }

            // Validate prices based on format
            if ($format !== 'offline') {
                if ($waves[$i]['presenter_fee_online'] < 0) {
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => "Gelombang {$waveNum}: Biaya presenter online tidak valid"
                    ]);
                }
                if ($waves[$i]['audience_fee_online'] < 0) {
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => "Gelombang {$waveNum}: Biaya audience online tidak valid"
                    ]);
                }
            }

            if ($format !== 'online') {
                if ($waves[$i]['presenter_fee_offline'] <= 0) {
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => "Gelombang {$waveNum}: Biaya presenter offline harus diisi (lebih dari 0)"
                    ]);
                }
                if ($waves[$i]['audience_fee_offline'] <= 0) {
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => "Gelombang {$waveNum}: Biaya audience offline harus diisi (lebih dari 0)"
                    ]);
                }
            }

            // Validate no overlap with previous wave
            if ($i > 0) {
                $prevEnd = strtotime($waves[$i-1]['registration_deadline']);
                if ($start <= $prevEnd) {
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => "Gelombang {$waveNum} harus dimulai setelah gelombang " . ($i) . " selesai"
                    ]);
                }
            }

            // Validate Early Bird rule (Wave 1 offline must be cheapest or equal)
            if ($waveNum === 1 && $format !== 'online') {
                // Wave 1 presenter offline should be <= other waves
                $wave1PresOff = (float)$waves[0]['presenter_fee_offline'];
                $wave1AudOff = (float)$waves[0]['audience_fee_offline'];

                foreach ($waves as $j => $compareWave) {
                    if ($j === 0) continue;
                    
                    $comparePresOff = (float)($compareWave['presenter_fee_offline'] ?? 0);
                    $compareAudOff = (float)($compareWave['audience_fee_offline'] ?? 0);

                    if ($wave1PresOff > $comparePresOff && $comparePresOff > 0) {
                        return $this->response->setJSON([
                            'success' => false,
                            'message' => "Early Bird: Gelombang 1 offline (presenter) harus lebih murah atau sama dengan gelombang berikutnya"
                        ]);
                    }

                    if ($wave1AudOff > $compareAudOff && $compareAudOff > 0) {
                        return $this->response->setJSON([
                            'success' => false,
                            'message' => "Early Bird: Gelombang 1 offline (audience) harus lebih murah atau sama dengan gelombang berikutnya"
                        ]);
                    }
                }
            }
        }

        try {
            // ✅ Final cleanup: ensure all numeric values are float
            foreach ($waves as &$wave) {
                $wave['presenter_fee_online'] = (float)($wave['presenter_fee_online'] ?? 0);
                $wave['presenter_fee_offline'] = (float)($wave['presenter_fee_offline'] ?? 0);
                $wave['audience_fee_online'] = (float)($wave['audience_fee_online'] ?? 0);
                $wave['audience_fee_offline'] = (float)($wave['audience_fee_offline'] ?? 0);
            }
            unset($wave); // Break reference

            log_message('info', 'Saving waves to DB: ' . json_encode($waves));

            $data = [
                'registration_waves' => json_encode($waves, JSON_UNESCAPED_UNICODE),
                'updated_at' => date('Y-m-d H:i:s')
            ];

            if ($this->eventModel->update($id, $data)) {
                $this->logActivity('Update gelombang pendaftaran: ' . $event['title']);
                
                log_message('info', 'Waves saved successfully');
                
                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Gelombang berhasil diupdate'
                ]);
            }
            
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Gagal update gelombang'
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

        // Convert to boolean for PostgreSQL
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
     * DELETE - Remove event (if no verified participants)
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

        // Check for verified participants
        $hasParticipants = $this->db->table('pembayaran')
            ->where('event_id', $id)
            ->where('status', 'verified')
            ->countAllResults() > 0;

        if ($hasParticipants) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Event tidak bisa dihapus, sudah ada peserta verified'
            ]);
        }

        $this->db->transStart();

        try {
            // Delete related data
            $this->db->table('abstrak')->where('event_id', $id)->delete();
            $this->db->table('pembayaran')->where('event_id', $id)->delete();
            $this->db->table('absensi')->where('event_id', $id)->delete();
            
            // Delete event
            $this->eventModel->delete($id);
            
            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Gagal hapus event'
                ]);
            }

            $this->logActivity('Hapus event: ' . $event['title']);

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Event berhasil dihapus'
            ]);

        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Delete failed: ' . $e->getMessage());
            
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage()
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