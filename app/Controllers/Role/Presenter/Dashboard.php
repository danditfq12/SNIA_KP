<?php

namespace App\Controllers\Role\Presenter;

use App\Controllers\BaseController;
use App\Models\EventModel;
use App\Models\AbstrakModel;
use App\Models\PembayaranModel;
use App\Models\DokumenModel;

class Dashboard extends BaseController
{
    protected $eventModel;
    protected $abstrakModel;
    protected $pembayaranModel;
    protected $dokumenModel;

    public function __construct()
    {
        $this->eventModel = new EventModel();
        $this->abstrakModel = new AbstrakModel();
        $this->pembayaranModel = new PembayaranModel();
        $this->dokumenModel = new DokumenModel();
    }

    public function index()
    {
        $userId = session('id_user');
        
        // Get active events with open registration
        $events = $this->eventModel->getEventsWithOpenRegistration();
        
        // Get user's registration status for each event
        foreach ($events as &$event) {
            $userRegistration = $this->pembayaranModel
                ->where('event_id', $event['id'])
                ->where('id_user', $userId)
                ->first();
            
            $event['user_registration'] = $userRegistration;
        }
        
        // Get user's abstracts
        $userAbstracts = $this->abstrakModel
            ->select('abstrak.*, kategori_abstrak.nama_kategori, events.title as event_title')
            ->join('kategori_abstrak', 'kategori_abstrak.id_kategori = abstrak.id_kategori', 'left')
            ->join('events', 'events.id = abstrak.event_id', 'left')
            ->where('abstrak.id_user', $userId)
            ->orderBy('abstrak.tanggal_upload', 'DESC')
            ->findAll();
        
        // Get user's payments
        $userPayments = $this->pembayaranModel
            ->select('pembayaran.*, events.title as event_title')
            ->join('events', 'events.id = pembayaran.event_id', 'left')
            ->where('pembayaran.id_user', $userId)
            ->orderBy('pembayaran.tanggal_bayar', 'DESC')
            ->findAll();
        
        // Get user's documents
        $userDocuments = $this->dokumenModel
            ->where('id_user', $userId)
            ->orderBy('uploaded_at', 'DESC')
            ->findAll();
        
        // Prepare dashboard statistics
        $stats = [
            'total_events' => count($events),
            'total_abstracts' => count($userAbstracts),
            'accepted_abstracts' => count(array_filter($userAbstracts, fn($a) => $a['status'] === 'diterima')),
            'pending_abstracts' => count(array_filter($userAbstracts, fn($a) => $a['status'] === 'menunggu')),
            'total_payments' => count($userPayments),
            'verified_payments' => count(array_filter($userPayments, fn($p) => $p['status'] === 'verified')),
            'pending_payments' => count(array_filter($userPayments, fn($p) => $p['status'] === 'pending')),
            'total_documents' => count($userDocuments)
        ];
        
        // Recent activity for timeline
        $recentActivity = [];
        
        // Add recent abstracts to activity
        foreach (array_slice($userAbstracts, 0, 5) as $abstract) {
            $recentActivity[] = [
                'type' => 'abstract',
                'title' => 'Abstract: ' . $abstract['judul'],
                'status' => $abstract['status'],
                'date' => $abstract['tanggal_upload'],
                'icon' => 'fas fa-file-alt',
                'url' => site_url('presenter/abstrak/detail/' . $abstract['id_abstrak'])
            ];
        }
        
        // Add recent payments to activity
        foreach (array_slice($userPayments, 0, 3) as $payment) {
            $recentActivity[] = [
                'type' => 'payment',
                'title' => 'Payment for: ' . $payment['event_title'],
                'status' => $payment['status'],
                'date' => $payment['tanggal_bayar'],
                'icon' => 'fas fa-credit-card',
                'amount' => $payment['jumlah']
            ];
        }
        
        // Sort recent activity by date
        usort($recentActivity, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        
        $data = [
            'events' => $events,
            'user_abstracts' => $userAbstracts,
            'user_payments' => $userPayments,
            'user_documents' => $userDocuments,
            'stats' => $stats,
            'recent_activity' => array_slice($recentActivity, 0, 10)
        ];

        return view('role/presenter/dashboard', $data);
    }
    
    /**
     * Get presenter-specific event price (always offline)
     */
    private function getPresenterEventPrice($event)
    {
        return $event['presenter_fee_offline'] ?? 0;
    }
    
    /**
     * Check if user can register for event
     */
    private function canRegisterForEvent($eventId, $userId)
    {
        // Check if registration is still open
        if (!$this->eventModel->isRegistrationOpen($eventId)) {
            return false;
        }
        
        // Check if already registered
        $existingRegistration = $this->pembayaranModel
            ->where('event_id', $eventId)
            ->where('id_user', $userId)
            ->first();
        
        return !$existingRegistration;
    }
    
    /**
     * Get dashboard notifications
     */
    private function getDashboardNotifications($userId)
    {
        $notifications = [];
        
        // Check for pending payment verifications
        $pendingPayments = $this->pembayaranModel
            ->select('pembayaran.*, events.title as event_title')
            ->join('events', 'events.id = pembayaran.event_id')
            ->where('pembayaran.id_user', $userId)
            ->where('pembayaran.status', 'pending')
            ->findAll();
        
        foreach ($pendingPayments as $payment) {
            $notifications[] = [
                'type' => 'warning',
                'message' => 'Payment verification pending for: ' . $payment['event_title'],
                'date' => $payment['tanggal_bayar'],
                'action_url' => site_url('presenter/pembayaran')
            ];
        }
        
        // Check for abstracts needing revision
        $revisionAbstracts = $this->abstrakModel
            ->select('abstrak.*, events.title as event_title')
            ->join('events', 'events.id = abstrak.event_id', 'left')
            ->where('abstrak.id_user', $userId)
            ->where('abstrak.status', 'revisi')
            ->findAll();
        
        foreach ($revisionAbstracts as $abstract) {
            $notifications[] = [
                'type' => 'info',
                'message' => 'Abstract revision required: ' . substr($abstract['judul'], 0, 50) . '...',
                'date' => $abstract['tanggal_upload'],
                'action_url' => site_url('presenter/abstrak/detail/' . $abstract['id_abstrak'])
            ];
        }
        
        // Check for approaching deadlines
        $upcomingEvents = $this->eventModel
            ->where('is_active', true)
            ->where('registration_active', true)
            ->where('registration_deadline IS NOT NULL')
            ->where('registration_deadline >=', date('Y-m-d H:i:s'))
            ->where('registration_deadline <=', date('Y-m-d H:i:s', strtotime('+7 days')))
            ->findAll();
        
        foreach ($upcomingEvents as $event) {
            // Check if user hasn't registered yet
            $isRegistered = $this->pembayaranModel
                ->where('event_id', $event['id'])
                ->where('id_user', $userId)
                ->first();
            
            if (!$isRegistered) {
                $notifications[] = [
                    'type' => 'danger',
                    'message' => 'Registration deadline approaching for: ' . $event['title'],
                    'date' => $event['registration_deadline'],
                    'action_url' => site_url('presenter/events/register/' . $event['id'])
                ];
            }
        }
        
        // Sort notifications by date (newest first)
        usort($notifications, function($a, $b) {
            return strtotime($b['date']) - strtotime($a['date']);
        });
        
        return array_slice($notifications, 0, 5);
    }
}