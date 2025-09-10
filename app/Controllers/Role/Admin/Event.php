<?php

namespace App\Controllers\Role\Admin;

use App\Controllers\BaseController;
use App\Models\EventModel;
use App\Models\UserModel;
use App\Models\AbstrakModel;
use App\Models\PembayaranModel;
use App\Models\AbsensiModel;

class Event extends BaseController
{
    protected $eventModel;
    protected $userModel;
    protected $abstrakModel;
    protected $pembayaranModel;
    protected $absensiModel;
    protected $db;

    public function __construct()
    {
        $this->eventModel = new EventModel();
        $this->userModel = new UserModel();
        $this->abstrakModel = new AbstrakModel();
        $this->pembayaranModel = new PembayaranModel();
        $this->absensiModel = new AbsensiModel();
        $this->db = \Config\Database::connect();
    }

    /**
     * Main index page with real-time statistics
     */
    public function index()
    {
        try {
            // Get events with comprehensive statistics
            $events = $this->getEventsWithStats();
            
            // Get dashboard statistics
            $stats = $this->getDashboardStats();
            
            // Process events for display
            foreach ($events as &$event) {
                $event['is_active'] = $this->parseBoolean($event['is_active']);
                $event['registration_active'] = $this->parseBoolean($event['registration_active']);
                $event['abstract_submission_active'] = $this->parseBoolean($event['abstract_submission_active']);
                
                // Calculate additional metrics
                $event['attendance_rate'] = $event['verified_registrations'] > 0 
                    ? round(($event['present_count'] / $event['verified_registrations']) * 100, 2) 
                    : 0;
                    
                $event['capacity_filled'] = $event['max_participants'] 
                    ? round(($event['verified_registrations'] / $event['max_participants']) * 100, 2)
                    : 0;
                
                // Event status
                $event['event_status'] = $this->calculateEventStatus($event);
                $event['registration_status'] = $this->calculateRegistrationStatus($event);
            }

            $data = [
                'events' => $events,
                'stats' => $stats
            ];

            // Return JSON for AJAX requests
            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'success' => true,
                    'data' => $data,
                    'timestamp' => time()
                ]);
            }

            return view('role/admin/event/index', $data);

        } catch (\Exception $e) {
            log_message('error', 'Event index error: ' . $e->getMessage());
            
            if ($this->request->isAJAX()) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Terjadi kesalahan saat memuat data event'
                ]);
            }
            
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memuat data event');
        }
    }

    /**
     * Get events with comprehensive statistics
     */
    private function getEventsWithStats()
    {
        $sql = "
            SELECT 
                e.*,
                COALESCE(reg_stats.total_registrations, 0) as total_registrations,
                COALESCE(reg_stats.verified_registrations, 0) as verified_registrations,
                COALESCE(reg_stats.online_registrations, 0) as online_registrations,
                COALESCE(reg_stats.offline_registrations, 0) as offline_registrations,
                COALESCE(rev_stats.total_revenue, 0) as total_revenue,
                COALESCE(abs_stats.total_abstracts, 0) as total_abstracts,
                COALESCE(att_stats.present_count, 0) as present_count
            FROM events e
            LEFT JOIN (
                SELECT 
                    event_id,
                    COUNT(*) as total_registrations,
                    COUNT(CASE WHEN status = 'verified' THEN 1 END) as verified_registrations,
                    COUNT(CASE WHEN participation_type = 'online' THEN 1 END) as online_registrations,
                    COUNT(CASE WHEN participation_type = 'offline' THEN 1 END) as offline_registrations
                FROM pembayaran 
                GROUP BY event_id
            ) reg_stats ON e.id = reg_stats.event_id
            LEFT JOIN (
                SELECT 
                    event_id,
                    SUM(CASE WHEN status = 'verified' THEN jumlah ELSE 0 END) as total_revenue
                FROM pembayaran 
                GROUP BY event_id
            ) rev_stats ON e.id = rev_stats.event_id
            LEFT JOIN (
                SELECT 
                    event_id,
                    COUNT(*) as total_abstracts
                FROM abstrak 
                GROUP BY event_id
            ) abs_stats ON e.id = abs_stats.event_id
            LEFT JOIN (
                SELECT 
                    event_id,
                    COUNT(CASE WHEN status = 'hadir' THEN 1 END) as present_count
                FROM absensi 
                GROUP BY event_id
            ) att_stats ON e.id = att_stats.event_id
            ORDER BY e.event_date DESC, e.created_at DESC
        ";
        
        return $this->db->query($sql)->getResultArray();
    }

    /**
     * Get dashboard statistics
     */
    private function getDashboardStats()
    {
        $totalEvents = $this->eventModel->countAll();
        $activeEvents = $this->eventModel->where('is_active', true)->countAllResults();
        $upcomingEvents = $this->eventModel
            ->where('event_date >=', date('Y-m-d'))
            ->where('is_active', true)
            ->countAllResults();
        
        $totalRevenue = $this->pembayaranModel
            ->selectSum('jumlah')
            ->where('status', 'verified')
            ->first()['jumlah'] ?? 0;
            
        $verifiedRegistrations = $this->pembayaranModel
            ->where('status', 'verified')
            ->countAllResults();

        return [
            'total_events' => $totalEvents,
            'active_events' => $activeEvents,
            'upcoming_events' => $upcomingEvents,
            'total_revenue' => $totalRevenue,
            'verified_registrations' => $verifiedRegistrations
        ];
    }

    /**
     * Store new event
     */
    public function store()
    {
        $validation = \Config\Services::validation();
        
        $rules = [
            'title' => 'required|min_length[3]|max_length[255]',
            'description' => 'max_length[2000]',
            'event_date' => 'required|valid_date',
            'event_time' => 'required',
            'format' => 'required|in_list[both,online,offline]',
            'presenter_fee_offline' => 'required|numeric|greater_than_equal_to[0]',
            'audience_fee_online' => 'required|numeric|greater_than_equal_to[0]',
            'audience_fee_offline' => 'required|numeric|greater_than_equal_to[0]',
            'max_participants' => 'permit_empty|integer|greater_than[0]',
            'registration_deadline' => 'permit_empty|valid_date',
            'abstract_deadline' => 'permit_empty|valid_date'
        ];

        // Format-specific validation
        $format = $this->request->getPost('format');
        if (in_array($format, ['offline', 'both'])) {
            $rules['location'] = 'required|min_length[5]|max_length[255]';
        }
        if (in_array($format, ['online', 'both'])) {
            $rules['zoom_link'] = 'permit_empty|valid_url|max_length[500]';
        }

        if (!$this->validate($rules)) {
            return $this->handleValidationError($validation->getErrors());
        }

        // Date validation
        $dateValidation = $this->validateEventDates();
        if (!$dateValidation['valid']) {
            return $this->handleError($dateValidation['message']);
        }

        $this->db->transStart();

        try {
            $eventData = $this->prepareEventData();
            
            if (!$this->eventModel->save($eventData)) {
                throw new \Exception('Failed to create event: ' . implode(', ', $this->eventModel->errors()));
            }

            $newEventId = $this->eventModel->getInsertID();
            
            // Log activity
            $this->logActivity(session('id_user'), "Created new event: {$eventData['title']} (ID: {$newEventId})");

            $this->db->transComplete();

            if ($this->db->transStatus() === FALSE) {
                throw new \Exception('Transaction failed');
            }

            $response = [
                'success' => true,
                'message' => 'Event berhasil dibuat!',
                'event_id' => $newEventId
            ];

            return $this->response->setJSON($response);

        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Event creation error: ' . $e->getMessage());
            return $this->handleError('Error: ' . $e->getMessage());
        }
    }

    /**
     * Edit event - get event data for editing
     */
    public function edit($id)
    {
        $event = $this->eventModel->find($id);
        
        if (!$event) {
            return $this->response->setJSON(['success' => false, 'message' => 'Event tidak ditemukan.']);
        }

        // Normalize boolean values
        $event['is_active'] = $this->parseBoolean($event['is_active']);
        $event['registration_active'] = $this->parseBoolean($event['registration_active']);
        $event['abstract_submission_active'] = $this->parseBoolean($event['abstract_submission_active']);

        return $this->response->setJSON(['success' => true, 'event' => $event]);
    }

    /**
     * Update existing event
     */
    public function update($id)
    {
        $event = $this->eventModel->find($id);
        
        if (!$event) {
            return $this->handleError('Event tidak ditemukan.');
        }

        $validation = \Config\Services::validation();
        
        $rules = [
            'title' => 'required|min_length[3]|max_length[255]',
            'description' => 'max_length[2000]',
            'event_date' => 'required|valid_date',
            'event_time' => 'required',
            'format' => 'required|in_list[both,online,offline]',
            'presenter_fee_offline' => 'required|numeric|greater_than_equal_to[0]',
            'audience_fee_online' => 'required|numeric|greater_than_equal_to[0]',
            'audience_fee_offline' => 'required|numeric|greater_than_equal_to[0]',
            'max_participants' => 'permit_empty|integer|greater_than[0]',
            'registration_deadline' => 'permit_empty|valid_date',
            'abstract_deadline' => 'permit_empty|valid_date'
        ];

        if (!$this->validate($rules)) {
            return $this->handleValidationError($validation->getErrors());
        }

        // Check for dependencies before allowing major changes
        $dependencies = $this->checkEventDependencies($id);
        if ($dependencies['has_dependencies']) {
            $changeValidation = $this->validateChangeWithDependencies($event, $dependencies);
            if (!$changeValidation['allowed']) {
                return $this->handleError($changeValidation['message']);
            }
        }

        $this->db->transStart();

        try {
            $eventData = $this->prepareEventData();
            
            if (!$this->eventModel->update($id, $eventData)) {
                throw new \Exception('Failed to update event: ' . implode(', ', $this->eventModel->errors()));
            }

            // Log activity
            $this->logActivity(session('id_user'), "Updated event: {$eventData['title']} (ID: {$id})");

            $this->db->transComplete();

            if ($this->db->transStatus() === FALSE) {
                throw new \Exception('Transaction failed');
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Event berhasil diupdate!',
                'event_id' => $id
            ]);

        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Event update error: ' . $e->getMessage());
            return $this->handleError('Error: ' . $e->getMessage());
        }
    }

    /**
     * Delete event with safety checks
     */
    public function delete($id)
    {
        $event = $this->eventModel->find($id);
        
        if (!$event) {
            return $this->handleError('Event tidak ditemukan.');
        }

        // Check if event is active
        $isActive = $this->parseBoolean($event['is_active']);
        
        if ($isActive) {
            return $this->handleError('Tidak dapat menghapus event yang masih aktif. Silakan nonaktifkan event terlebih dahulu.');
        }

        // Check dependencies
        $dependencies = $this->checkEventDependencies($id);
        
        if ($dependencies['has_dependencies']) {
            $dependencyList = [];
            if ($dependencies['registrations'] > 0) $dependencyList[] = "{$dependencies['registrations']} pendaftaran";
            if ($dependencies['abstracts'] > 0) $dependencyList[] = "{$dependencies['abstracts']} abstrak";
            if ($dependencies['attendance'] > 0) $dependencyList[] = "{$dependencies['attendance']} data absensi";
            
            return $this->handleError('Tidak dapat menghapus event yang memiliki ' . implode(', ', $dependencyList) . '. Silakan hapus data terkait terlebih dahulu atau biarkan event tetap nonaktif.');
        }

        $this->db->transStart();

        try {
            if (!$this->eventModel->delete($id)) {
                throw new \Exception('Failed to delete event');
            }

            // Log activity
            $this->logActivity(session('id_user'), "Deleted inactive event: {$event['title']} (ID: {$id})");

            $this->db->transComplete();

            if ($this->db->transStatus() === FALSE) {
                throw new \Exception('Transaction failed');
            }

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Event berhasil dihapus!'
            ]);

        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Event deletion error: ' . $e->getMessage());
            return $this->handleError('Error menghapus event: ' . $e->getMessage());
        }
    }

    /**
     * Toggle event status (active/inactive)
     */
    public function toggleStatus($id)
    {
        $event = $this->eventModel->find($id);
        
        if (!$event) {
            return $this->handleError('Event tidak ditemukan.');
        }

        $currentStatus = $this->parseBoolean($event['is_active']);
        $newStatus = !$currentStatus;

        try {
            if ($this->eventModel->update($id, ['is_active' => $newStatus])) {
                // Log activity
                $this->logActivity(session('id_user'), "Changed status for event '{$event['title']}' to " . ($newStatus ? 'active' : 'inactive'));
                
                return $this->response->setJSON([
                    'success' => true,
                    'message' => $newStatus ? 'Event berhasil diaktifkan!' : 'Event berhasil dinonaktifkan!',
                    'new_status' => $newStatus,
                    'new_status_text' => $newStatus ? 'Aktif' : 'Nonaktif',
                    'can_delete' => !$newStatus
                ]);
            }
            
            return $this->handleError('Gagal mengubah status event.');

        } catch (\Exception $e) {
            log_message('error', 'Toggle status error: ' . $e->getMessage());
            return $this->handleError('Error: ' . $e->getMessage());
        }
    }

    /**
     * Toggle registration status
     */
    public function toggleRegistration($id)
    {
        return $this->toggleEventFeature($id, 'registration_active', 'pendaftaran');
    }

    /**
     * Toggle abstract submission status
     */
    public function toggleAbstractSubmission($id)
    {
        return $this->toggleEventFeature($id, 'abstract_submission_active', 'submit abstrak');
    }

    /**
     * Get event detail page
     */
    public function detail($id)
    {
        $event = $this->eventModel->find($id);
        
        if (!$event) {
            return redirect()->to('admin/event')->with('error', 'Event tidak ditemukan.');
        }

        // Normalize boolean values
        $event['is_active'] = $this->parseBoolean($event['is_active']);
        $event['registration_active'] = $this->parseBoolean($event['registration_active']);
        $event['abstract_submission_active'] = $this->parseBoolean($event['abstract_submission_active']);

        // Get comprehensive event statistics
        $stats = $this->eventModel->getEventStats($id);
        
        $data = [
            'event' => $event,
            'stats' => $stats,
            'dependencies' => $this->checkEventDependencies($id)
        ];

        return view('role/admin/event/detail', $data);
    }

    // === HELPER METHODS ===

    /**
     * Generic method to toggle event features
     */
    private function toggleEventFeature($id, $field, $featureName)
    {
        $event = $this->eventModel->find($id);
        
        if (!$event) {
            return $this->handleError('Event tidak ditemukan.');
        }

        $currentStatus = $this->parseBoolean($event[$field]);
        $newStatus = !$currentStatus;
        
        try {
            if ($this->eventModel->update($id, [$field => $newStatus])) {
                // Log activity
                $statusText = $newStatus ? 'dibuka' : 'ditutup';
                $this->logActivity(session('id_user'), "Event '{$event['title']}' {$featureName} {$statusText}");
                
                return $this->response->setJSON([
                    'success' => true,
                    'message' => ucfirst($featureName) . ($newStatus ? ' berhasil dibuka!' : ' berhasil ditutup!'),
                    'new_status' => $newStatus
                ]);
            }
            
            return $this->handleError("Gagal mengubah status {$featureName}.");

        } catch (\Exception $e) {
            log_message('error', "Toggle {$field} error: " . $e->getMessage());
            return $this->handleError('Error: ' . $e->getMessage());
        }
    }

    /**
     * Parse boolean values consistently
     */
    private function parseBoolean($value)
    {
        if ($value === null || $value === '') return false;
        if (is_bool($value)) return $value;
        if (is_string($value)) {
            $value = strtolower(trim($value));
            return in_array($value, ['true', 't', '1', 'yes', 'on', 'y'], true);
        }
        if (is_numeric($value)) return (bool) intval($value);
        return false;
    }

    /**
     * Calculate event status
     */
    private function calculateEventStatus($event)
    {
        try {
            date_default_timezone_set('Asia/Jakarta');
            
            $eventDateTime = new \DateTime($event['event_date'] . ' ' . $event['event_time']);
            $currentDateTime = new \DateTime();
            
            $timeDiff = $currentDateTime->getTimestamp() - $eventDateTime->getTimestamp();
            $hoursDiff = $timeDiff / 3600;
            
            if ($hoursDiff < -1) {
                return ['text' => 'Akan Datang', 'badge_class' => 'bg-info'];
            } elseif ($hoursDiff < 0) {
                return ['text' => 'Segera Dimulai', 'badge_class' => 'bg-warning'];
            } elseif ($hoursDiff <= 4) {
                return ['text' => 'Sedang Berlangsung', 'badge_class' => 'bg-success'];
            } else {
                return ['text' => 'Sudah Selesai', 'badge_class' => 'bg-danger'];
            }
        } catch (\Exception $e) {
            return ['text' => 'Status Tidak Diketahui', 'badge_class' => 'bg-secondary'];
        }
    }

    /**
     * Calculate registration status
     */
    private function calculateRegistrationStatus($event)
    {
        if (!$this->parseBoolean($event['registration_active'])) {
            return ['text' => 'Tutup', 'badge_class' => 'bg-danger'];
        }

        $now = time();
        $eventDate = strtotime($event['event_date']);
        $registrationDeadline = $event['registration_deadline'] ? strtotime($event['registration_deadline']) : null;

        if ($registrationDeadline && $now > $registrationDeadline) {
            return ['text' => 'Sudah Berakhir', 'badge_class' => 'bg-warning'];
        }

        if ($now > $eventDate) {
            return ['text' => 'Event Sudah Lewat', 'badge_class' => 'bg-danger'];
        }

        return ['text' => 'Buka', 'badge_class' => 'bg-success'];
    }

    /**
     * Prepare event data for insert/update
     */
    private function prepareEventData()
    {
        return [
            'title' => $this->request->getPost('title'),
            'description' => $this->request->getPost('description'),
            'event_date' => $this->request->getPost('event_date'),
            'event_time' => $this->request->getPost('event_time'),
            'format' => $this->request->getPost('format'),
            'location' => $this->request->getPost('location'),
            'zoom_link' => $this->request->getPost('zoom_link'),
            'presenter_fee_offline' => $this->request->getPost('presenter_fee_offline'),
            'audience_fee_online' => $this->request->getPost('audience_fee_online'),
            'audience_fee_offline' => $this->request->getPost('audience_fee_offline'),
            'max_participants' => $this->request->getPost('max_participants') ?: null,
            'registration_deadline' => $this->request->getPost('registration_deadline') ?: null,
            'abstract_deadline' => $this->request->getPost('abstract_deadline') ?: null,
            'registration_active' => $this->request->getPost('registration_active') ? true : false,
            'abstract_submission_active' => $this->request->getPost('abstract_submission_active') ? true : false,
            'is_active' => $this->request->getPost('is_active') ? true : false
        ];
    }

    /**
     * Validate event dates
     */
    private function validateEventDates()
    {
        $eventDate = $this->request->getPost('event_date');
        $registrationDeadline = $this->request->getPost('registration_deadline');
        $abstractDeadline = $this->request->getPost('abstract_deadline');

        if (strtotime($eventDate) <= time()) {
            return ['valid' => false, 'message' => 'Tanggal event harus di masa depan.'];
        }

        if ($registrationDeadline && strtotime($registrationDeadline) >= strtotime($eventDate)) {
            return ['valid' => false, 'message' => 'Batas pendaftaran harus sebelum tanggal event.'];
        }

        if ($abstractDeadline && strtotime($abstractDeadline) >= strtotime($eventDate)) {
            return ['valid' => false, 'message' => 'Batas submit abstrak harus sebelum tanggal event.'];
        }

        return ['valid' => true];
    }

    /**
     * Check event dependencies
     */
    private function checkEventDependencies($eventId)
    {
        $registrations = $this->pembayaranModel->where('event_id', $eventId)->countAllResults();
        $abstracts = $this->abstrakModel->where('event_id', $eventId)->countAllResults();
        $attendance = $this->absensiModel->where('event_id', $eventId)->countAllResults();

        return [
            'has_dependencies' => ($registrations + $abstracts + $attendance) > 0,
            'registrations' => $registrations,
            'abstracts' => $abstracts,
            'attendance' => $attendance
        ];
    }

    /**
     * Validate changes with dependencies
     */
    private function validateChangeWithDependencies($event, $dependencies)
    {
        // Allow most changes, but warn about major structural changes
        $newFormat = $this->request->getPost('format');
        if ($event['format'] !== $newFormat && $dependencies['registrations'] > 0) {
            return [
                'allowed' => false,
                'message' => 'Tidak dapat mengubah format event yang sudah memiliki pendaftaran.'
            ];
        }

        return ['allowed' => true];
    }

    /**
     * Handle validation errors
     */
    private function handleValidationError($errors)
    {
        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'success' => false,
                'errors' => $errors
            ]);
        }
        return redirect()->back()->withInput()->with('errors', $errors);
    }

    /**
     * Handle general errors
     */
    private function handleError($message)
    {
        if ($this->request->isAJAX()) {
            return $this->response->setJSON([
                'success' => false,
                'message' => $message
            ]);
        }
        return redirect()->back()->with('error', $message);
    }

    /**
     * Log activity
     */
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