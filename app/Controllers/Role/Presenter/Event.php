<?php

namespace App\Controllers\Role\Presenter;

use App\Controllers\BaseController;
use App\Models\EventModel;
use App\Models\AbstrakModel;
use App\Models\PembayaranModel;
use App\Models\EventRegistrationModel;
use App\Models\VoucherModel;

class Event extends BaseController
{
    protected $eventModel;
    protected $abstrakModel;
    protected $pembayaranModel;
    protected $eventRegistrationModel;
    protected $voucherModel;
    protected $db;

    public function __construct()
    {
        $this->eventModel = new EventModel();
        $this->abstrakModel = new AbstrakModel();
        $this->pembayaranModel = new PembayaranModel();
        $this->eventRegistrationModel = new EventRegistrationModel();
        $this->voucherModel = new VoucherModel();
        $this->db = \Config\Database::connect();
    }

    /**
     * Display all available events for presenter
     */
    public function index()
    {
        $userId = session('id_user');
        
        try {
            // Get active events with presenter's status
            $events = $this->getEventsWithPresenterStatus($userId);
            
            // Filter events by status for easier navigation
            $availableEvents = array_filter($events, fn($e) => $e['can_participate']);
            $participatingEvents = array_filter($events, fn($e) => $e['has_abstract'] || $e['has_payment']);
            $completedEvents = array_filter($events, fn($e) => $e['is_completed']);

            $data = [
                'events' => $events,
                'available_events' => $availableEvents,
                'participating_events' => $participatingEvents,
                'completed_events' => $completedEvents,
                'total_events' => count($events),
                'available_count' => count($availableEvents),
                'participating_count' => count($participatingEvents),
                'completed_count' => count($completedEvents)
            ];

            return view('role/presenter/event/index', $data);

        } catch (\Exception $e) {
            log_message('error', 'Presenter event index error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memuat daftar event.');
        }
    }

    /**
     * Get events with presenter's participation status
     */
    private function getEventsWithPresenterStatus($userId)
    {
        $events = $this->eventModel
            ->where('is_active', true)
            ->orderBy('event_date', 'ASC')
            ->findAll();

        foreach ($events as &$event) {
            // Check abstract status
            $abstract = $this->abstrakModel
                ->where('id_user', $userId)
                ->where('event_id', $event['id'])
                ->orderBy('tanggal_upload', 'DESC')
                ->first();

            $event['abstract'] = $abstract;
            $event['has_abstract'] = !empty($abstract);
            $event['abstract_status'] = $abstract['status'] ?? null;

            // Check payment status
            $payment = $this->pembayaranModel
                ->where('id_user', $userId)
                ->where('event_id', $event['id'])
                ->orderBy('tanggal_bayar', 'DESC')
                ->first();

            $event['payment'] = $payment;
            $event['has_payment'] = !empty($payment);
            $event['payment_status'] = $payment['status'] ?? null;

            // Determine workflow status
            $event['workflow_status'] = $this->determineWorkflowStatus($event);
            
            // Check if can participate
            $event['can_participate'] = $this->canParticipateInEvent($event);
            
            // Check if completed (payment verified)
            $event['is_completed'] = $payment && $payment['status'] === 'verified';
            
            // Calculate presenter price (always offline)
            $event['presenter_price'] = $event['presenter_fee_offline'] ?? 0;
            
            // Check deadlines
            $event['registration_open'] = $this->eventModel->isRegistrationOpen($event['id']);
            $event['abstract_submission_open'] = $this->eventModel->isAbstractSubmissionOpen($event['id']);
            
            // Format dates
            $event['formatted_date'] = date('d F Y', strtotime($event['event_date']));
            $event['formatted_time'] = date('H:i', strtotime($event['event_time']));
            
            // Days until event
            $eventTimestamp = strtotime($event['event_date']);
            $currentTimestamp = time();
            $event['days_until'] = max(0, ceil(($eventTimestamp - $currentTimestamp) / (60 * 60 * 24)));
        }

        return $events;
    }

    /**
     * Determine workflow status for an event
     */
    private function determineWorkflowStatus($event)
    {
        if (!$event['has_abstract']) {
            return 'need_abstract';
        }

        switch ($event['abstract_status']) {
            case 'menunggu':
            case 'sedang_direview':
                return 'abstract_pending';
            case 'revisi':
                return 'abstract_revision';
            case 'ditolak':
                return 'abstract_rejected';
            case 'diterima':
                if (!$event['has_payment']) {
                    return 'need_payment';
                }
                
                switch ($event['payment_status']) {
                    case 'pending':
                        return 'payment_pending';
                    case 'rejected':
                        return 'payment_rejected';
                    case 'verified':
                        return 'completed';
                }
                break;
        }

        return 'unknown';
    }

    /**
     * Check if presenter can participate in event
     */
    private function canParticipateInEvent($event)
    {
        // Must be active and have open submissions
        if (!$event['is_active'] || !$event['abstract_submission_open']) {
            return false;
        }

        // If no abstract yet, can participate
        if (!$event['has_abstract']) {
            return true;
        }

        // If abstract needs revision and submission still open
        if ($event['abstract_status'] === 'revisi' && $event['abstract_submission_open']) {
            return true;
        }

        // If abstract rejected and submission still open
        if ($event['abstract_status'] === 'ditolak' && $event['abstract_submission_open']) {
            return true;
        }

        // If abstract accepted but no payment yet
        if ($event['abstract_status'] === 'diterima' && !$event['has_payment'] && $event['registration_open']) {
            return true;
        }

        // If payment rejected
        if ($event['payment_status'] === 'rejected' && $event['registration_open']) {
            return true;
        }

        return false;
    }

    /**
     * Show event detail page
     */
    public function detail($eventId)
    {
        $userId = session('id_user');
        
        try {
            $event = $this->eventModel->find($eventId);
            
            if (!$event || !$event['is_active']) {
                return redirect()->to('presenter/events')->with('error', 'Event tidak ditemukan atau tidak aktif.');
            }

            // Get event with presenter status
            $events = $this->getEventsWithPresenterStatus($userId);
            $eventData = array_filter($events, fn($e) => $e['id'] == $eventId);
            $eventData = reset($eventData);

            if (!$eventData) {
                return redirect()->to('presenter/events')->with('error', 'Data event tidak dapat dimuat.');
            }

            // Get event statistics
            $eventStats = $this->eventModel->getEventStats($eventId);
            
            // Get presenter's workflow progress
            $workflowProgress = $this->getDetailedWorkflowProgress($userId, $eventData);

            $data = [
                'event' => $eventData,
                'event_stats' => $eventStats,
                'workflow_progress' => $workflowProgress,
                'can_register' => $this->canRegisterForEvent($eventData),
                'next_action' => $this->getNextAction($eventData)
            ];

            return view('role/presenter/event/detail', $data);

        } catch (\Exception $e) {
            log_message('error', 'Presenter event detail error: ' . $e->getMessage());
            return redirect()->to('presenter/events')->with('error', 'Terjadi kesalahan saat memuat detail event.');
        }
    }

    /**
     * Get detailed workflow progress for event detail page
     */
    private function getDetailedWorkflowProgress($userId, $event)
    {
        $progress = [
            'current_step' => 1,
            'total_steps' => 5,
            'steps' => [
                1 => [
                    'title' => 'Submit Abstrak',
                    'status' => 'pending',
                    'completed' => false,
                    'current' => false,
                    'can_proceed' => false,
                    'action_url' => site_url('presenter/abstrak?event_id=' . $event['id']),
                    'action_text' => 'Upload Abstrak'
                ],
                2 => [
                    'title' => 'Review Abstrak',
                    'status' => 'pending',
                    'completed' => false,
                    'current' => false,
                    'can_proceed' => false,
                    'action_url' => '',
                    'action_text' => 'Menunggu Review'
                ],
                3 => [
                    'title' => 'Pembayaran',
                    'status' => 'pending',
                    'completed' => false,
                    'current' => false,
                    'can_proceed' => false,
                    'action_url' => site_url('presenter/pembayaran/create/' . $event['id']),
                    'action_text' => 'Lakukan Pembayaran'
                ],
                4 => [
                    'title' => 'Verifikasi Pembayaran',
                    'status' => 'pending',
                    'completed' => false,
                    'current' => false,
                    'can_proceed' => false,
                    'action_url' => '',
                    'action_text' => 'Menunggu Verifikasi'
                ],
                5 => [
                    'title' => 'Selesai',
                    'status' => 'pending',
                    'completed' => false,
                    'current' => false,
                    'can_proceed' => false,
                    'action_url' => '',
                    'action_text' => 'Akses Fitur Event'
                ]
            ]
        ];

        // Update progress based on current status
        switch ($event['workflow_status']) {
            case 'need_abstract':
                $progress['current_step'] = 1;
                $progress['steps'][1]['current'] = true;
                $progress['steps'][1]['can_proceed'] = $event['abstract_submission_open'];
                break;

            case 'abstract_pending':
                $progress['current_step'] = 2;
                $progress['steps'][1]['completed'] = true;
                $progress['steps'][1]['status'] = 'completed';
                $progress['steps'][2]['current'] = true;
                $progress['steps'][2]['status'] = 'in_progress';
                break;

            case 'abstract_revision':
                $progress['current_step'] = 1;
                $progress['steps'][1]['current'] = true;
                $progress['steps'][1]['status'] = 'needs_revision';
                $progress['steps'][1]['can_proceed'] = $event['abstract_submission_open'];
                $progress['steps'][1]['action_text'] = 'Revisi Abstrak';
                break;

            case 'abstract_rejected':
                $progress['current_step'] = 1;
                $progress['steps'][1]['current'] = true;
                $progress['steps'][1]['status'] = 'rejected';
                $progress['steps'][1]['can_proceed'] = $event['abstract_submission_open'];
                $progress['steps'][1]['action_text'] = 'Submit Abstrak Baru';
                break;

            case 'need_payment':
                $progress['current_step'] = 3;
                $progress['steps'][1]['completed'] = true;
                $progress['steps'][1]['status'] = 'completed';
                $progress['steps'][2]['completed'] = true;
                $progress['steps'][2]['status'] = 'completed';
                $progress['steps'][3]['current'] = true;
                $progress['steps'][3]['can_proceed'] = $event['registration_open'];
                break;

            case 'payment_pending':
                $progress['current_step'] = 4;
                $progress['steps'][1]['completed'] = true;
                $progress['steps'][1]['status'] = 'completed';
                $progress['steps'][2]['completed'] = true;
                $progress['steps'][2]['status'] = 'completed';
                $progress['steps'][3]['completed'] = true;
                $progress['steps'][3]['status'] = 'completed';
                $progress['steps'][4]['current'] = true;
                $progress['steps'][4]['status'] = 'in_progress';
                break;

            case 'payment_rejected':
                $progress['current_step'] = 3;
                $progress['steps'][1]['completed'] = true;
                $progress['steps'][1]['status'] = 'completed';
                $progress['steps'][2]['completed'] = true;
                $progress['steps'][2]['status'] = 'completed';
                $progress['steps'][3]['current'] = true;
                $progress['steps'][3]['status'] = 'rejected';
                $progress['steps'][3]['can_proceed'] = $event['registration_open'];
                $progress['steps'][3]['action_text'] = 'Bayar Ulang';
                break;

            case 'completed':
                $progress['current_step'] = 5;
                for ($i = 1; $i <= 5; $i++) {
                    $progress['steps'][$i]['completed'] = true;
                    $progress['steps'][$i]['status'] = 'completed';
                }
                $progress['steps'][5]['current'] = true;
                break;
        }

        return $progress;
    }

    /**
     * Check if presenter can register for event
     */
    private function canRegisterForEvent($event)
    {
        return $event['can_participate'] && in_array($event['workflow_status'], [
            'need_abstract', 'abstract_revision', 'abstract_rejected', 'need_payment', 'payment_rejected'
        ]);
    }

    /**
     * Get next action for event
     */
    private function getNextAction($event)
    {
        switch ($event['workflow_status']) {
            case 'need_abstract':
                return [
                    'text' => 'Upload Abstrak',
                    'url' => site_url('presenter/abstrak?event_id=' . $event['id']),
                    'class' => 'btn-primary',
                    'icon' => 'fas fa-upload'
                ];

            case 'abstract_revision':
                return [
                    'text' => 'Revisi Abstrak',
                    'url' => site_url('presenter/abstrak/detail/' . $event['abstract']['id_abstrak']),
                    'class' => 'btn-warning',
                    'icon' => 'fas fa-edit'
                ];

            case 'abstract_rejected':
                return [
                    'text' => 'Submit Abstrak Baru',
                    'url' => site_url('presenter/abstrak?event_id=' . $event['id']),
                    'class' => 'btn-danger',
                    'icon' => 'fas fa-redo'
                ];

            case 'need_payment':
                return [
                    'text' => 'Lakukan Pembayaran',
                    'url' => site_url('presenter/pembayaran/create/' . $event['id']),
                    'class' => 'btn-success',
                    'icon' => 'fas fa-credit-card'
                ];

            case 'payment_rejected':
                return [
                    'text' => 'Bayar Ulang',
                    'url' => site_url('presenter/pembayaran/create/' . $event['id']),
                    'class' => 'btn-danger',
                    'icon' => 'fas fa-redo'
                ];

            case 'completed':
                return [
                    'text' => 'Akses Fitur Event',
                    'url' => site_url('presenter/absensi'),
                    'class' => 'btn-info',
                    'icon' => 'fas fa-star'
                ];

            default:
                return [
                    'text' => 'Tunggu Proses',
                    'url' => '#',
                    'class' => 'btn-secondary disabled',
                    'icon' => 'fas fa-clock'
                ];
        }
    }

    /**
     * Calculate event price for presenter
     */
    public function calculatePrice()
    {
        $eventId = $this->request->getPost('event_id');
        $voucherCode = $this->request->getPost('voucher_code');

        if (!$eventId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Event ID is required'
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

            // Presenter always uses offline price
            $originalPrice = $event['presenter_fee_offline'] ?? 0;
            $finalPrice = $originalPrice;
            $discount = 0;
            $voucherInfo = null;

            // Apply voucher if provided
            if ($voucherCode) {
                $voucher = $this->voucherModel->where('kode_voucher', strtoupper($voucherCode))->first();
                
                if ($voucher && $voucher['status'] === 'aktif' && strtotime($voucher['masa_berlaku']) > time()) {
                    // Check voucher quota
                    $usedCount = $this->pembayaranModel
                        ->where('id_voucher', $voucher['id_voucher'])
                        ->where('status', 'verified')
                        ->countAllResults();

                    if ($usedCount < $voucher['kuota']) {
                        if ($voucher['tipe'] === 'percentage') {
                            $discount = ($originalPrice * $voucher['nilai']) / 100;
                        } else {
                            $discount = $voucher['nilai'];
                        }
                        
                        $finalPrice = max(0, $originalPrice - $discount);
                        $voucherInfo = $voucher;
                    }
                }
            }

            return $this->response->setJSON([
                'success' => true,
                'pricing' => [
                    'original_price' => $originalPrice,
                    'discount' => $discount,
                    'final_price' => $finalPrice,
                    'voucher' => $voucherInfo
                ]
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Calculate price error: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error calculating price'
            ]);
        }
    }

    /**
     * Show registration form for event
     */
    public function showRegistrationForm($eventId)
    {
        $userId = session('id_user');
        
        try {
            $event = $this->eventModel->find($eventId);
            
            if (!$event || !$event['is_active']) {
                return redirect()->to('presenter/events')->with('error', 'Event tidak ditemukan atau tidak aktif.');
            }

            // Check if presenter can register
            $events = $this->getEventsWithPresenterStatus($userId);
            $eventData = array_filter($events, fn($e) => $e['id'] == $eventId);
            $eventData = reset($eventData);

            if (!$this->canRegisterForEvent($eventData)) {
                return redirect()->to('presenter/events/detail/' . $eventId)
                               ->with('error', 'Anda tidak dapat mendaftar untuk event ini saat ini.');
            }

            $data = [
                'event' => $eventData,
                'next_action' => $this->getNextAction($eventData)
            ];

            return view('role/presenter/event/register', $data);

        } catch (\Exception $e) {
            log_message('error', 'Show registration form error: ' . $e->getMessage());
            return redirect()->to('presenter/events')->with('error', 'Terjadi kesalahan saat memuat form registrasi.');
        }
    }

    /**
     * Process event registration
     */
    public function register($eventId)
    {
        $userId = session('id_user');
        
        try {
            $event = $this->eventModel->find($eventId);
            
            if (!$event || !$event['is_active']) {
                return redirect()->to('presenter/events')->with('error', 'Event tidak ditemukan atau tidak aktif.');
            }

            // Check registration eligibility
            $events = $this->getEventsWithPresenterStatus($userId);
            $eventData = array_filter($events, fn($e) => $e['id'] == $eventId);
            $eventData = reset($eventData);

            if (!$this->canRegisterForEvent($eventData)) {
                return redirect()->to('presenter/events/detail/' . $eventId)
                               ->with('error', 'Anda tidak dapat mendaftar untuk event ini saat ini.');
            }

            // Create or update event registration (presenter always offline)
            $registrationId = $this->eventRegistrationModel->createRegistration($eventId, $userId, 'offline');

            // Log activity
            $this->logActivity($userId, "Registered for event: {$event['title']} (ID: {$eventId})");

            // Redirect based on next required action
            $nextAction = $this->getNextAction($eventData);
            return redirect()->to($nextAction['url'])->with('success', 'Registrasi berhasil! Silakan lanjutkan langkah berikutnya.');

        } catch (\Exception $e) {
            log_message('error', 'Event registration error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Terjadi kesalahan saat registrasi: ' . $e->getMessage());
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