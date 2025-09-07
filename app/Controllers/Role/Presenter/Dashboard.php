<?php

namespace App\Controllers\Role\Presenter;

use App\Controllers\BaseController;
use App\Models\EventModel;
use App\Models\AbstrakModel;
use App\Models\PembayaranModel;
use App\Models\DokumenModel;
use App\Models\ReviewModel;
use App\Models\KategoriAbstrakModel;
use App\Models\EventRegistrationModel;

class Dashboard extends BaseController
{
    protected $eventModel;
    protected $abstrakModel;
    protected $pembayaranModel;
    protected $dokumenModel;
    protected $reviewModel;
    protected $kategoriModel;
    protected $eventRegistrationModel;
    protected $db;

    public function __construct()
    {
        $this->eventModel = new EventModel();
        $this->abstrakModel = new AbstrakModel();
        $this->pembayaranModel = new PembayaranModel();
        $this->dokumenModel = new DokumenModel();
        $this->reviewModel = new ReviewModel();
        $this->kategoriModel = new KategoriAbstrakModel();
        $this->eventRegistrationModel = new EventRegistrationModel();
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        $userId = session('id_user');
        
        try {
            // Get active events with registration status
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
                'categories' => $this->kategoriModel->findAll()
            ];

            return view('role/presenter/dashboard', $data);

        } catch (\Exception $e) {
            log_message('error', 'Presenter dashboard error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memuat dashboard.');
        }
    }

    /**
     * Get events with user's participation status
     */
    private function getEventsWithUserStatus($userId)
    {
        $events = $this->eventModel
            ->where('is_active', true)
            ->orderBy('event_date', 'DESC')
            ->findAll();

        foreach ($events as &$event) {
            // Check registration status
            $registration = $this->eventRegistrationModel->findUserReg($event['id'], $userId);
            $event['is_registered'] = !empty($registration);
            $event['registration_data'] = $registration;

            // Calculate pricing for presenter (always offline)
            $event['presenter_price'] = $event['presenter_fee_offline'] ?? 0;
            
            // Check registration deadline
            $event['registration_open'] = $this->eventModel->isRegistrationOpen($event['id']);
            $event['abstract_submission_open'] = $this->eventModel->isAbstractSubmissionOpen($event['id']);
        }

        return $events;
    }

    /**
     * Calculate workflow progress for each event
     */
    private function calculateWorkflowProgress($userId, $events)
    {
        $workflowData = [];

        foreach ($events as $event) {
            $eventId = $event['id'];
            $progress = [
                'event_id' => $eventId,
                'event_title' => $event['title'],
                'event_date' => $event['event_date'],
                'step' => 0,
                'can_proceed' => false,
                'status' => 'not_started',
                'message' => '',
                'next_action' => '',
                'next_url' => '',
                'abstract' => null,
                'payment' => null,
                'attendance' => null,
                'documents' => []
            ];

            // Step 1: Check Abstract Submission
            $abstract = $this->abstrakModel
                ->where('id_user', $userId)
                ->where('event_id', $eventId)
                ->orderBy('tanggal_upload', 'DESC')
                ->first();

            if (!$abstract) {
                // No abstract submitted
                $progress['step'] = 1;
                $progress['status'] = 'abstract_required';
                $progress['message'] = 'Anda harus mengisi abstrak terlebih dahulu sebelum dapat melanjutkan.';
                $progress['next_action'] = 'Upload Abstrak';
                $progress['next_url'] = site_url('presenter/abstrak?event_id=' . $eventId);
                $progress['can_proceed'] = $event['abstract_submission_open'];
            } else {
                $progress['abstract'] = $abstract;
                
                switch ($abstract['status']) {
                    case 'menunggu':
                    case 'sedang_direview':
                        $progress['step'] = 2;
                        $progress['status'] = 'abstract_pending';
                        $progress['message'] = 'Abstrak Anda sedang dalam proses review. Silakan tunggu hasil review.';
                        $progress['next_action'] = 'Tunggu Review';
                        $progress['next_url'] = site_url('presenter/abstrak/detail/' . $abstract['id_abstrak']);
                        $progress['can_proceed'] = false;
                        break;

                    case 'revisi':
                        $progress['step'] = 1;
                        $progress['status'] = 'abstract_revision';
                        $progress['message'] = 'Abstrak Anda memerlukan revisi. Silakan perbaiki dan upload ulang.';
                        $progress['next_action'] = 'Revisi Abstrak';
                        $progress['next_url'] = site_url('presenter/abstrak/detail/' . $abstract['id_abstrak']);
                        $progress['can_proceed'] = $event['abstract_submission_open'];
                        break;

                    case 'ditolak':
                        $progress['step'] = 1;
                        $progress['status'] = 'abstract_rejected';
                        $progress['message'] = 'Abstrak Anda ditolak. Silakan buat abstrak baru.';
                        $progress['next_action'] = 'Buat Abstrak Baru';
                        $progress['next_url'] = site_url('presenter/abstrak?event_id=' . $eventId);
                        $progress['can_proceed'] = $event['abstract_submission_open'];
                        break;

                    case 'diterima':
                        // Abstract accepted, check payment
                        $payment = $this->pembayaranModel
                            ->where('id_user', $userId)
                            ->where('event_id', $eventId)
                            ->orderBy('tanggal_bayar', 'DESC')
                            ->first();

                        if (!$payment) {
                            // No payment made
                            $progress['step'] = 3;
                            $progress['status'] = 'payment_required';
                            $progress['message'] = 'Abstrak diterima! Silakan lakukan pembayaran untuk melanjutkan.';
                            $progress['next_action'] = 'Lakukan Pembayaran';
                            $progress['next_url'] = site_url('presenter/pembayaran/create/' . $eventId);
                            $progress['can_proceed'] = $event['registration_open'];
                        } else {
                            $progress['payment'] = $payment;
                            
                            switch ($payment['status']) {
                                case 'pending':
                                    $progress['step'] = 4;
                                    $progress['status'] = 'payment_pending';
                                    $progress['message'] = 'Pembayaran sedang diverifikasi. Silakan tunggu konfirmasi admin.';
                                    $progress['next_action'] = 'Tunggu Verifikasi';
                                    $progress['next_url'] = site_url('presenter/pembayaran/detail/' . $payment['id_pembayaran']);
                                    $progress['can_proceed'] = false;
                                    break;

                                case 'rejected':
                                    $progress['step'] = 3;
                                    $progress['status'] = 'payment_rejected';
                                    $progress['message'] = 'Pembayaran ditolak. Silakan lakukan pembayaran ulang.';
                                    $progress['next_action'] = 'Bayar Ulang';
                                    $progress['next_url'] = site_url('presenter/pembayaran/create/' . $eventId);
                                    $progress['can_proceed'] = $event['registration_open'];
                                    break;

                                case 'verified':
                                    // Payment verified - all features unlocked
                                    $progress['step'] = 5;
                                    $progress['status'] = 'completed';
                                    $progress['message'] = 'Selamat! Semua langkah selesai. Anda dapat mengakses semua fitur event.';
                                    $progress['next_action'] = 'Akses Fitur Event';
                                    $progress['next_url'] = site_url('presenter/events/detail/' . $eventId);
                                    $progress['can_proceed'] = true;

                                    // Check additional data
                                    $this->checkEventFeatures($userId, $eventId, $progress);
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
     * Check available event features after payment verification
     */
    private function checkEventFeatures($userId, $eventId, &$progress)
    {
        // Check attendance
        $attendance = $this->db->table('absensi')
            ->where('id_user', $userId)
            ->where('event_id', $eventId)
            ->get()->getRowArray();
        
        $progress['attendance'] = $attendance;

        // Check documents (LOA, Certificate)
        $documents = $this->dokumenModel
            ->where('id_user', $userId)
            ->where('event_id', $eventId)
            ->findAll();
        
        $progress['documents'] = $documents;

        // Categorize documents
        $progress['has_loa'] = !empty(array_filter($documents, fn($d) => $d['tipe'] === 'loa'));
        $progress['has_certificate'] = !empty(array_filter($documents, fn($d) => $d['tipe'] === 'sertifikat'));
    }

    /**
     * Get user statistics
     */
    private function getUserStatistics($userId)
    {
        $stats = [
            'total_events' => 0,
            'completed_workflows' => 0,
            'pending_actions' => 0,
            'total_abstracts' => 0,
            'accepted_abstracts' => 0,
            'pending_abstracts' => 0,
            'total_payments' => 0,
            'verified_payments' => 0,
            'pending_payments' => 0,
            'attended_events' => 0
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
        $attendance = $this->db->table('absensi')
            ->where('id_user', $userId)
            ->where('status', 'hadir')
            ->countAllResults();
        $stats['attended_events'] = $attendance;

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
                            'action_text' => 'Lihat Detail',
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
            // Check if user hasn't registered yet
            $hasWorkflow = $this->abstrakModel
                ->where('id_user', $userId)
                ->where('event_id', $event['id'])
                ->first();

            if (!$hasWorkflow) {
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
            // Check if user has pending abstract
            $pendingAbstract = $this->abstrakModel
                ->where('id_user', $userId)
                ->where('event_id', $event['id'])
                ->where('status', 'revisi')
                ->first();

            if ($pendingAbstract) {
                $daysLeft = ceil((strtotime($event['abstract_deadline']) - time()) / (60 * 60 * 24));
                $notifications[] = [
                    'type' => 'danger',
                    'title' => 'Deadline Abstrak',
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
            if (!$workflow['can_proceed'] && $workflow['status'] !== 'completed') {
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
            'rejected' => 'bg-danger'
        ];

        return $classes[$status] ?? 'bg-secondary';
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
            $events = [$this->eventModel->find($eventId)];
            $workflowData = $this->calculateWorkflowProgress($userId, $events);
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
                'stats' => $stats
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Get quick stats error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error getting statistics'
            ]);
        }
    }
}