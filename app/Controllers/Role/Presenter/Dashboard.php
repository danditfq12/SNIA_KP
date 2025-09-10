<?php

namespace App\Controllers\Role\Presenter;

use App\Controllers\BaseController;
use App\Models\EventModel;
use App\Models\AbstrakModel;
use App\Models\PembayaranModel;
use App\Models\DokumenModel;
use App\Models\ReviewModel;
use App\Models\KategoriAbstrakModel;
use App\Models\AbsensiModel;
use App\Models\UserModel;

class Dashboard extends BaseController
{
    protected $eventModel;
    protected $abstrakModel;
    protected $pembayaranModel;
    protected $dokumenModel;
    protected $reviewModel;
    protected $kategoriModel;
    protected $absensiModel;
    protected $userModel;
    protected $db;

    public function __construct()
    {
        $this->eventModel = new EventModel();
        $this->abstrakModel = new AbstrakModel();
        $this->pembayaranModel = new PembayaranModel();
        $this->dokumenModel = new DokumenModel();
        $this->reviewModel = new ReviewModel();
        $this->kategoriModel = new KategoriAbstrakModel();
        $this->absensiModel = new AbsensiModel();
        $this->userModel = new UserModel();
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        $userId = session('id_user');
        
        try {
            // Get active events with user participation status
            $events = $this->getEventsWithUserStatus($userId);
            
            // Get user's workflow progress for each event
            $workflowData = $this->calculateWorkflowProgress($userId, $events);
            
            // Get user statistics
            $userStats = $this->getUserStatistics($userId);
            
            // Get recent activities
            $recentActivities = $this->getRecentActivities($userId);
            
            // Get notifications based on workflow status
            $notifications = $this->getWorkflowNotifications($userId, $workflowData);
            
            // Get next actions required
            $nextActions = $this->getNextActionsRequired($userId, $workflowData);

            $data = [
                'events' => $events,
                'workflow_data' => $workflowData,
                'user_stats' => $userStats,
                'recent_activities' => $recentActivities,
                'notifications' => $notifications,
                'next_actions' => $nextActions,
                'categories' => $this->kategoriModel->findAll(),
                'user' => $this->userModel->find($userId)
            ];

            return view('role/presenter/dashboard', $data);

        } catch (\Exception $e) {
            log_message('error', 'Presenter dashboard error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memuat dashboard.');
        }
    }

    /**
     * Get events with user's participation status based on actual database schema
     */
    private function getEventsWithUserStatus($userId)
    {
        // Get all active events
        $events = $this->eventModel
            ->where('is_active', true)
            ->orderBy('event_date', 'DESC')
            ->findAll();

        foreach ($events as &$event) {
            // Parse boolean fields properly for PostgreSQL
            $event['is_active'] = $this->parseBoolean($event['is_active']);
            $event['registration_active'] = $this->parseBoolean($event['registration_active']);
            $event['abstract_submission_active'] = $this->parseBoolean($event['abstract_submission_active']);

            // Check user's abstract for this event
            $abstract = $this->abstrakModel
                ->where('id_user', $userId)
                ->where('event_id', $event['id'])
                ->orderBy('tanggal_upload', 'DESC')
                ->first();
            $event['abstract_data'] = $abstract;

            // Check user's payment for this event
            $payment = $this->pembayaranModel
                ->where('id_user', $userId)
                ->where('event_id', $event['id'])
                ->orderBy('tanggal_bayar', 'DESC')
                ->first();
            $event['payment_data'] = $payment;

            // Check user's attendance for this event
            $attendance = $this->absensiModel
                ->where('id_user', $userId)
                ->where('event_id', $event['id'])
                ->first();
            $event['attendance_data'] = $attendance;

            // Check user's documents for this event
            $documents = $this->dokumenModel
                ->where('id_user', $userId)
                ->where('event_id', $event['id'])
                ->findAll();
            $event['documents_data'] = $documents;

            // Calculate pricing for presenter (always offline)
            $event['presenter_price'] = $event['presenter_fee_offline'] ?? 0;
            
            // Check deadlines
            $event['registration_open'] = $this->eventModel->isRegistrationOpen($event['id']);
            $event['abstract_submission_open'] = $this->eventModel->isAbstractSubmissionOpen($event['id']);

            // Determine if user is registered (has abstract or payment)
            $event['is_registered'] = !empty($abstract) || !empty($payment);
        }

        return $events;
    }

    /**
     * Calculate workflow progress based on actual business logic
     */
    private function calculateWorkflowProgress($userId, $events)
    {
        $workflowData = [];

        foreach ($events as $event) {
            $eventId = $event['id'];
            $abstract = $event['abstract_data'];
            $payment = $event['payment_data'];
            $attendance = $event['attendance_data'];
            $documents = $event['documents_data'];

            $progress = [
                'event_id' => $eventId,
                'event_title' => $event['title'],
                'event_date' => $event['event_date'],
                'step' => 1,
                'can_proceed' => false,
                'status' => 'not_started',
                'message' => '',
                'next_action' => '',
                'next_url' => '',
                'abstract' => $abstract,
                'payment' => $payment,
                'attendance' => $attendance,
                'documents' => $documents
            ];

            // Step 1: Abstract Submission
            if (!$abstract) {
                $progress['step'] = 1;
                $progress['status'] = 'abstract_required';
                $progress['message'] = 'Upload abstrak untuk memulai partisipasi dalam event ini.';
                $progress['next_action'] = 'Upload Abstrak';
                $progress['next_url'] = site_url('presenter/abstrak?event_id=' . $eventId);
                $progress['can_proceed'] = $event['abstract_submission_open'];
            } else {
                // Abstract exists, check status
                switch ($abstract['status']) {
                    case 'menunggu':
                    case 'sedang_direview':
                        $progress['step'] = 2;
                        $progress['status'] = 'abstract_pending';
                        $progress['message'] = 'Abstrak sedang dalam proses review. Tunggu hasil review.';
                        $progress['next_action'] = 'Lihat Status';
                        $progress['next_url'] = site_url('presenter/abstrak/detail/' . $abstract['id_abstrak']);
                        $progress['can_proceed'] = false;
                        break;

                    case 'revisi':
                        $progress['step'] = 1;
                        $progress['status'] = 'abstract_revision';
                        $progress['message'] = 'Abstrak memerlukan revisi. Perbaiki dan upload ulang.';
                        $progress['next_action'] = 'Revisi Abstrak';
                        $progress['next_url'] = site_url('presenter/abstrak/detail/' . $abstract['id_abstrak']);
                        $progress['can_proceed'] = $event['abstract_submission_open'];
                        break;

                    case 'ditolak':
                        $progress['step'] = 1;
                        $progress['status'] = 'abstract_rejected';
                        $progress['message'] = 'Abstrak ditolak. Buat abstrak baru jika masih memungkinkan.';
                        $progress['next_action'] = 'Buat Abstrak Baru';
                        $progress['next_url'] = site_url('presenter/abstrak?event_id=' . $eventId);
                        $progress['can_proceed'] = $event['abstract_submission_open'];
                        break;

                    case 'diterima':
                        // Step 3: Payment
                        if (!$payment) {
                            $progress['step'] = 3;
                            $progress['status'] = 'payment_required';
                            $progress['message'] = 'Abstrak diterima! Lakukan pembayaran untuk konfirmasi partisipasi.';
                            $progress['next_action'] = 'Lakukan Pembayaran';
                            $progress['next_url'] = site_url('presenter/pembayaran/create/' . $eventId);
                            $progress['can_proceed'] = $event['registration_open'];
                        } else {
                            // Payment exists, check status
                            switch ($payment['status']) {
                                case 'pending':
                                    $progress['step'] = 4;
                                    $progress['status'] = 'payment_pending';
                                    $progress['message'] = 'Pembayaran sedang diverifikasi. Tunggu konfirmasi admin.';
                                    $progress['next_action'] = 'Lihat Status Pembayaran';
                                    $progress['next_url'] = site_url('presenter/pembayaran/detail/' . $payment['id_pembayaran']);
                                    $progress['can_proceed'] = false;
                                    break;

                                case 'rejected':
                                    $progress['step'] = 3;
                                    $progress['status'] = 'payment_rejected';
                                    $progress['message'] = 'Pembayaran ditolak. Lakukan pembayaran ulang.';
                                    $progress['next_action'] = 'Bayar Ulang';
                                    $progress['next_url'] = site_url('presenter/pembayaran/create/' . $eventId);
                                    $progress['can_proceed'] = $event['registration_open'];
                                    break;

                                case 'verified':
                                    // Step 5: Event Features Available
                                    $progress['step'] = 5;
                                    $progress['status'] = 'completed';
                                    $progress['message'] = 'Pembayaran terverifikasi! Semua fitur event tersedia.';
                                    $progress['next_action'] = 'Akses Fitur Event';
                                    $progress['next_url'] = site_url('presenter/events/detail/' . $eventId);
                                    $progress['can_proceed'] = true;

                                    // Additional status based on event date
                                    $eventDate = strtotime($event['event_date']);
                                    $now = time();
                                    
                                    if ($now > $eventDate) {
                                        // Event has passed
                                        if ($attendance) {
                                            $progress['status'] = 'attended';
                                            $progress['message'] = 'Event selesai. Anda telah hadir.';
                                            $progress['next_action'] = 'Lihat Dokumen';
                                        } else {
                                            $progress['status'] = 'missed';
                                            $progress['message'] = 'Event selesai. Anda tidak hadir.';
                                            $progress['next_action'] = 'Lihat Detail';
                                        }
                                    }
                                    break;
                            }
                        }
                        break;
                }
            }

            $workflowData[$eventId] = $progress;
        }

        return $workflowData;
    }

    /**
     * Get user statistics
     */
    private function getUserStatistics($userId)
    {
        $stats = [
            'total_events' => 0,
            'registered_events' => 0,
            'completed_workflows' => 0,
            'pending_actions' => 0,
            'total_abstracts' => 0,
            'accepted_abstracts' => 0,
            'pending_abstracts' => 0,
            'total_payments' => 0,
            'verified_payments' => 0,
            'pending_payments' => 0,
            'attended_events' => 0,
            'total_documents' => 0
        ];

        // Count abstracts
        $abstracts = $this->abstrakModel->where('id_user', $userId)->findAll();
        $stats['total_abstracts'] = count($abstracts);
        $stats['accepted_abstracts'] = count(array_filter($abstracts, fn($a) => $a['status'] === 'diterima'));
        $stats['pending_abstracts'] = count(array_filter($abstracts, fn($a) => in_array($a['status'], ['menunggu', 'sedang_direview'])));

        // Count payments
        $payments = $this->pembayaranModel->where('id_user', $userId)->findAll();
        $stats['total_payments'] = count($payments);
        $stats['verified_payments'] = count(array_filter($payments, fn($p) => $p['status'] === 'verified'));
        $stats['pending_payments'] = count(array_filter($payments, fn($p) => $p['status'] === 'pending'));

        // Count attendance
        $stats['attended_events'] = $this->absensiModel
            ->where('id_user', $userId)
            ->where('status', 'hadir')
            ->countAllResults();

        // Count documents
        $stats['total_documents'] = $this->dokumenModel
            ->where('id_user', $userId)
            ->countAllResults();

        // Count registered events (has abstract or payment)
        $registeredEvents = $this->db->query("
            SELECT DISTINCT event_id 
            FROM (
                SELECT event_id FROM abstrak WHERE id_user = ?
                UNION
                SELECT event_id FROM pembayaran WHERE id_user = ?
            ) AS registered
        ", [$userId, $userId])->getResultArray();
        
        $stats['registered_events'] = count($registeredEvents);

        return $stats;
    }

    /**
     * Get recent activities
     */
    private function getRecentActivities($userId)
    {
        $activities = [];

        // Recent abstracts
        $recentAbstracts = $this->abstrakModel
            ->select('abstrak.*, events.title as event_title')
            ->join('events', 'events.id = abstrak.event_id', 'left')
            ->where('abstrak.id_user', $userId)
            ->orderBy('abstrak.tanggal_upload', 'DESC')
            ->limit(5)
            ->findAll();

        foreach ($recentAbstracts as $abstract) {
            $activities[] = [
                'type' => 'abstract',
                'title' => 'Abstrak: ' . substr($abstract['judul'], 0, 50) . '...',
                'subtitle' => 'Event: ' . ($abstract['event_title'] ?? 'Unknown'),
                'status' => $abstract['status'],
                'date' => $abstract['tanggal_upload'],
                'icon' => 'fas fa-file-alt',
                'url' => site_url('presenter/abstrak/detail/' . $abstract['id_abstrak']),
                'badge_class' => $this->getStatusBadgeClass($abstract['status'])
            ];
        }

        // Recent payments
        $recentPayments = $this->pembayaranModel
            ->select('pembayaran.*, events.title as event_title')
            ->join('events', 'events.id = pembayaran.event_id', 'left')
            ->where('pembayaran.id_user', $userId)
            ->orderBy('pembayaran.tanggal_bayar', 'DESC')
            ->limit(3)
            ->findAll();

        foreach ($recentPayments as $payment) {
            $activities[] = [
                'type' => 'payment',
                'title' => 'Pembayaran: Rp ' . number_format($payment['jumlah'], 0, ',', '.'),
                'subtitle' => 'Event: ' . ($payment['event_title'] ?? 'Unknown'),
                'status' => $payment['status'],
                'date' => $payment['tanggal_bayar'],
                'icon' => 'fas fa-credit-card',
                'url' => site_url('presenter/pembayaran/detail/' . $payment['id_pembayaran']),
                'badge_class' => $this->getStatusBadgeClass($payment['status'])
            ];
        }

        // Recent attendance
        $recentAttendance = $this->absensiModel
            ->select('absensi.*, events.title as event_title')
            ->join('events', 'events.id = absensi.event_id', 'left')
            ->where('absensi.id_user', $userId)
            ->orderBy('absensi.waktu_scan', 'DESC')
            ->limit(3)
            ->findAll();

        foreach ($recentAttendance as $attendance) {
            $activities[] = [
                'type' => 'attendance',
                'title' => 'Kehadiran: ' . ucfirst($attendance['status']),
                'subtitle' => 'Event: ' . ($attendance['event_title'] ?? 'Unknown'),
                'status' => $attendance['status'],
                'date' => $attendance['waktu_scan'],
                'icon' => 'fas fa-qrcode',
                'url' => site_url('presenter/absensi'),
                'badge_class' => $this->getStatusBadgeClass($attendance['status'])
            ];
        }

        // Sort by date
        usort($activities, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });

        return array_slice($activities, 0, 10);
    }

    /**
     * Get workflow-based notifications
     */
    private function getWorkflowNotifications($userId, $workflowData)
    {
        $notifications = [];

        foreach ($workflowData as $workflow) {
            switch ($workflow['status']) {
                case 'abstract_required':
                    if ($workflow['can_proceed']) {
                        $notifications[] = [
                            'type' => 'warning',
                            'title' => 'Abstrak Diperlukan',
                            'message' => "Upload abstrak untuk event: {$workflow['event_title']}",
                            'action_text' => 'Upload Sekarang',
                            'action_url' => $workflow['next_url'],
                            'date' => $workflow['event_date']
                        ];
                    }
                    break;

                case 'abstract_revision':
                    if ($workflow['can_proceed']) {
                        $notifications[] = [
                            'type' => 'info',
                            'title' => 'Revisi Abstrak',
                            'message' => "Abstrak memerlukan revisi untuk event: {$workflow['event_title']}",
                            'action_text' => 'Revisi Sekarang',
                            'action_url' => $workflow['next_url'],
                            'date' => $workflow['abstract']['tanggal_upload']
                        ];
                    }
                    break;

                case 'abstract_rejected':
                    if ($workflow['can_proceed']) {
                        $notifications[] = [
                            'type' => 'danger',
                            'title' => 'Abstrak Ditolak',
                            'message' => "Buat abstrak baru untuk event: {$workflow['event_title']}",
                            'action_text' => 'Buat Baru',
                            'action_url' => $workflow['next_url'],
                            'date' => $workflow['abstract']['tanggal_upload']
                        ];
                    }
                    break;

                case 'payment_required':
                    if ($workflow['can_proceed']) {
                        $notifications[] = [
                            'type' => 'success',
                            'title' => 'Abstrak Diterima',
                            'message' => "Lakukan pembayaran untuk event: {$workflow['event_title']}",
                            'action_text' => 'Bayar Sekarang',
                            'action_url' => $workflow['next_url'],
                            'date' => $workflow['abstract']['tanggal_upload']
                        ];
                    }
                    break;

                case 'payment_rejected':
                    if ($workflow['can_proceed']) {
                        $notifications[] = [
                            'type' => 'danger',
                            'title' => 'Pembayaran Ditolak',
                            'message' => "Lakukan pembayaran ulang untuk event: {$workflow['event_title']}",
                            'action_text' => 'Bayar Ulang',
                            'action_url' => $workflow['next_url'],
                            'date' => $workflow['payment']['tanggal_bayar']
                        ];
                    }
                    break;
            }
        }

        // Add deadline notifications
        $this->addDeadlineNotifications($userId, $notifications);

        // Sort by urgency and date
        usort($notifications, function($a, $b) {
            $urgency = ['danger' => 1, 'warning' => 2, 'info' => 3, 'success' => 4];
            if ($urgency[$a['type']] !== $urgency[$b['type']]) {
                return $urgency[$a['type']] - $urgency[$b['type']];
            }
            return strtotime($b['date']) - strtotime($a['date']);
        });

        return array_slice($notifications, 0, 5);
    }

    /**
     * Add deadline-based notifications
     */
    private function addDeadlineNotifications($userId, &$notifications)
    {
        // Check upcoming registration deadlines
        $upcomingEvents = $this->eventModel
            ->where('is_active', true)
            ->where('registration_active', true)
            ->where('registration_deadline IS NOT NULL')
            ->where('registration_deadline >=', date('Y-m-d H:i:s'))
            ->where('registration_deadline <=', date('Y-m-d H:i:s', strtotime('+7 days')))
            ->findAll();

        foreach ($upcomingEvents as $event) {
            // Check if user hasn't started workflow yet
            $hasAbstract = $this->abstrakModel
                ->where('id_user', $userId)
                ->where('event_id', $event['id'])
                ->first();

            if (!$hasAbstract) {
                $daysLeft = ceil((strtotime($event['registration_deadline']) - time()) / (60 * 60 * 24));
                $notifications[] = [
                    'type' => 'warning',
                    'title' => 'Deadline Mendekati',
                    'message' => "Pendaftaran {$event['title']} berakhir dalam {$daysLeft} hari",
                    'action_text' => 'Daftar Sekarang',
                    'action_url' => site_url('presenter/abstrak?event_id=' . $event['id']),
                    'date' => $event['registration_deadline']
                ];
            }
        }

        // Check upcoming abstract deadlines
        $abstractDeadlines = $this->eventModel
            ->where('is_active', true)
            ->where('abstract_submission_active', true)
            ->where('abstract_deadline IS NOT NULL')
            ->where('abstract_deadline >=', date('Y-m-d H:i:s'))
            ->where('abstract_deadline <=', date('Y-m-d H:i:s', strtotime('+3 days')))
            ->findAll();

        foreach ($abstractDeadlines as $event) {
            // Check if user has pending abstract revisions
            $pendingAbstract = $this->abstrakModel
                ->where('id_user', $userId)
                ->where('event_id', $event['id'])
                ->where('status', 'revisi')
                ->first();

            if ($pendingAbstract) {
                $daysLeft = ceil((strtotime($event['abstract_deadline']) - time()) / (60 * 60 * 24));
                $notifications[] = [
                    'type' => 'danger',
                    'title' => 'Deadline Revisi Abstrak',
                    'message' => "Batas revisi abstrak {$event['title']} dalam {$daysLeft} hari",
                    'action_text' => 'Revisi Sekarang',
                    'action_url' => site_url('presenter/abstrak/detail/' . $pendingAbstract['id_abstrak']),
                    'date' => $event['abstract_deadline']
                ];
            }
        }
    }

    /**
     * Get next actions required by the user
     */
    private function getNextActionsRequired($userId, $workflowData)
    {
        $actions = [];

        foreach ($workflowData as $workflow) {
            if (!$workflow['can_proceed'] && !in_array($workflow['status'], ['completed', 'attended'])) {
                continue; // Skip if can't proceed
            }

            switch ($workflow['status']) {
                case 'abstract_required':
                case 'abstract_revision':
                case 'abstract_rejected':
                case 'payment_required':
                case 'payment_rejected':
                    $actions[] = [
                        'event_title' => $workflow['event_title'],
                        'action' => $workflow['next_action'],
                        'url' => $workflow['next_url'],
                        'status' => $workflow['status'],
                        'priority' => $this->getActionPriority($workflow['status']),
                        'event_date' => $workflow['event_date']
                    ];
                    break;
            }
        }

        // Sort by priority and event date
        usort($actions, function($a, $b) {
            if ($a['priority'] !== $b['priority']) {
                return $a['priority'] - $b['priority'];
            }
            return strtotime($a['event_date']) - strtotime($b['event_date']);
        });

        return array_slice($actions, 0, 5);
    }

    /**
     * Get action priority (lower number = higher priority)
     */
    private function getActionPriority($status)
    {
        $priorities = [
            'payment_rejected' => 1,
            'abstract_rejected' => 2,
            'abstract_revision' => 3,
            'payment_required' => 4,
            'abstract_required' => 5
        ];

        return $priorities[$status] ?? 99;
    }

    /**
     * Get status badge CSS class
     */
    private function getStatusBadgeClass($status)
    {
        $classes = [
            'menunggu' => 'bg-warning',
            'sedang_direview' => 'bg-info',
            'diterima' => 'bg-success',
            'ditolak' => 'bg-danger',
            'revisi' => 'bg-secondary',
            'pending' => 'bg-warning',
            'verified' => 'bg-success',
            'rejected' => 'bg-danger',
            'hadir' => 'bg-success',
            'tidak' => 'bg-secondary'
        ];

        return $classes[$status] ?? 'bg-secondary';
    }

    /**
     * Parse boolean values for PostgreSQL compatibility
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
     * AJAX endpoint to refresh workflow status
     */
    public function refreshWorkflowStatus()
    {
        $userId = session('id_user');
        $eventId = $this->request->getPost('event_id');

        if (!$eventId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Event ID required'
            ]);
        }

        try {
            $event = $this->eventModel->find($eventId);
            if (!$event) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Event not found'
                ]);
            }

            $events = $this->getEventsWithUserStatus($userId);
            $filteredEvents = array_filter($events, fn($e) => $e['id'] == $eventId);
            
            if (empty($filteredEvents)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Event data not found'
                ]);
            }

            $workflowData = $this->calculateWorkflowProgress($userId, $filteredEvents);
            $workflow = $workflowData[$eventId] ?? null;

            if (!$workflow) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Workflow data not found'
                ]);
            }

            return $this->response->setJSON([
                'success' => true,
                'workflow' => $workflow
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Refresh workflow status error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error refreshing workflow status'
            ]);
        }
    }

    /**
     * Get quick stats for AJAX
     */
    public function getQuickStats()
    {
        $userId = session('id_user');

        try {
            $stats = $this->getUserStatistics($userId);
            
            return $this->response->setJSON([
                'success' => true,
                'stats' => $stats,
                'timestamp' => time()
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Get quick stats error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error getting statistics'
            ]);
        }
    }

    /**
     * Log activity helper
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