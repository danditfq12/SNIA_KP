<?php

namespace App\Controllers\Role\Presenter;

use App\Controllers\BaseController;
use App\Models\EventModel;
use App\Models\PembayaranModel;
use App\Models\VoucherModel;
use App\Models\AbstrakModel;
use App\Models\EventRegistrationModel;

class Event extends BaseController
{
    protected $eventModel;
    protected $pembayaranModel;
    protected $voucherModel;
    protected $abstrakModel;
    protected $eventRegistrationModel;
    protected $db;

    public function __construct()
    {
        $this->eventModel = new EventModel();
        $this->pembayaranModel = new PembayaranModel();
        $this->voucherModel = new VoucherModel();
        $this->abstrakModel = new AbstrakModel();
        $this->eventRegistrationModel = new EventRegistrationModel();
        $this->db = \Config\Database::connect();
    }

    public function index()
    {
        $userId = session('id_user');
        
        try {
            // Get events with user's participation status
            $events = $this->getEventsForPresenter($userId);
            
            // Ensure events is always an array
            if (!is_array($events)) {
                $events = [];
                log_message('warning', 'Events data is not an array, setting to empty array');
            }

            $data = [
                'events' => $events,
                'title' => 'Daftar Event'
            ];

            return view('role/presenter/event/index', $data);

        } catch (\Exception $e) {
            log_message('error', 'Error in presenter events index: ' . $e->getMessage());
            
            // Return view with empty events array and error message
            $data = [
                'events' => [],
                'title' => 'Daftar Event',
                'error' => 'Terjadi kesalahan saat memuat daftar event: ' . $e->getMessage()
            ];
            
            return view('role/presenter/event/index', $data);
        }
    }

    public function detail($eventId)
    {
        $userId = session('id_user');
        
        try {
            $event = $this->eventModel->find($eventId);
            
            if (!$event) {
                return redirect()->to('presenter/events')->with('error', 'Event tidak ditemukan.');
            }

            // Get user's participation data
            $abstract = $this->abstrakModel
                ->where('id_user', $userId)
                ->where('event_id', $eventId)
                ->orderBy('tanggal_upload', 'DESC')
                ->first();

            $payment = $this->pembayaranModel
                ->where('id_user', $userId)
                ->where('event_id', $eventId)
                ->orderBy('tanggal_bayar', 'DESC')
                ->first();

            $registration = $this->eventRegistrationModel->findUserReg($eventId, $userId);

            // Check status
            $canRegister = $this->eventModel->isRegistrationOpen($eventId);
            $canSubmitAbstract = $this->eventModel->isAbstractSubmissionOpen($eventId);
            
            // Calculate presenter price (always offline)
            $presenterPrice = $event['presenter_fee_offline'] ?? 0;

            $data = [
                'event' => $event,
                'abstract' => $abstract,
                'payment' => $payment,
                'registration' => $registration,
                'can_register' => $canRegister,
                'can_submit_abstract' => $canSubmitAbstract,
                'presenter_price' => $presenterPrice,
                'title' => 'Detail Event: ' . $event['title']
            ];

            return view('role/presenter/event/detail', $data);

        } catch (\Exception $e) {
            log_message('error', 'Error in presenter event detail: ' . $e->getMessage());
            return redirect()->to('presenter/events')->with('error', 'Terjadi kesalahan saat memuat detail event.');
        }
    }

    public function showRegistrationForm($eventId)
    {
        $userId = session('id_user');
        
        try {
            $event = $this->eventModel->find($eventId);
            
            if (!$event) {
                return redirect()->to('presenter/events')->with('error', 'Event tidak ditemukan.');
            }

            // Check if registration is open
            if (!$this->eventModel->isRegistrationOpen($eventId)) {
                return redirect()->to('presenter/events')->with('error', 'Pendaftaran untuk event ini sudah ditutup.');
            }

            // Check if user already registered
            $existingRegistration = $this->eventRegistrationModel->findUserReg($eventId, $userId);
            if ($existingRegistration) {
                return redirect()->to('presenter/events/detail/' . $eventId)
                    ->with('info', 'Anda sudah terdaftar untuk event ini.');
            }

            // For presenters, participation is always offline
            $participationType = 'offline';
            $price = $event['presenter_fee_offline'] ?? 0;

            $data = [
                'event' => $event,
                'participation_type' => $participationType,
                'price' => $price,
                'title' => 'Pendaftaran Event: ' . $event['title']
            ];

            return view('role/presenter/event/register', $data);

        } catch (\Exception $e) {
            log_message('error', 'Error in registration form: ' . $e->getMessage());
            return redirect()->to('presenter/events')->with('error', 'Terjadi kesalahan saat memuat form pendaftaran.');
        }
    }

    public function register($eventId)
    {
        $userId = session('id_user');
        
        if (!$this->request->isAJAX() && !$this->request->getMethod() === 'POST') {
            return redirect()->to('presenter/events');
        }

        try {
            $event = $this->eventModel->find($eventId);
            
            if (!$event) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Event tidak ditemukan.'
                ]);
            }

            // Check if registration is open
            if (!$this->eventModel->isRegistrationOpen($eventId)) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Pendaftaran untuk event ini sudah ditutup.'
                ]);
            }

            // Check if user already registered
            $existingRegistration = $this->eventRegistrationModel->findUserReg($eventId, $userId);
            if ($existingRegistration) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Anda sudah terdaftar untuk event ini.'
                ]);
            }

            // For presenters, participation is always offline
            $participationType = 'offline';

            $this->db->transStart();

            // Create registration
            $registrationId = $this->eventRegistrationModel->createRegistration(
                $eventId, 
                $userId, 
                $participationType
            );

            if (!$registrationId) {
                throw new \Exception('Gagal membuat registrasi');
            }

            $this->db->transComplete();

            if ($this->db->transStatus() === false) {
                throw new \Exception('Database transaction failed');
            }

            // Log activity
            $this->logActivity($userId, "Mendaftar untuk event: {$event['title']}");

            return $this->response->setJSON([
                'success' => true,
                'message' => 'Pendaftaran berhasil! Silakan upload abstrak untuk melanjutkan.',
                'redirect_url' => site_url('presenter/abstrak?event_id=' . $eventId)
            ]);

        } catch (\Exception $e) {
            $this->db->transRollback();
            log_message('error', 'Error in event registration: ' . $e->getMessage());
            
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Terjadi kesalahan saat mendaftar: ' . $e->getMessage()
            ]);
        }
    }

    public function calculatePrice()
    {
        if (!$this->request->isAJAX()) {
            return $this->response->setStatusCode(400);
        }

        try {
            $eventId = $this->request->getPost('event_id');
            $voucherCode = $this->request->getPost('voucher_code');

            if (!$eventId) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Event ID diperlukan'
                ]);
            }

            $event = $this->eventModel->find($eventId);
            if (!$event) {
                return $this->response->setJSON([
                    'success' => false,
                    'message' => 'Event tidak ditemukan'
                ]);
            }

            // For presenters, always offline participation
            $originalPrice = $event['presenter_fee_offline'] ?? 0;
            $finalPrice = $originalPrice;
            $discount = 0;
            $voucherData = null;

            // Apply voucher if provided
            if (!empty($voucherCode)) {
                $voucher = $this->voucherModel
                    ->where('kode_voucher', $voucherCode)
                    ->where('status', 'aktif')
                    ->where('masa_berlaku >=', date('Y-m-d'))
                    ->where('kuota >', 0)
                    ->first();

                if ($voucher) {
                    if ($voucher['tipe'] === 'persentase') {
                        $discount = ($originalPrice * $voucher['nilai']) / 100;
                    } else {
                        $discount = $voucher['nilai'];
                    }
                    
                    $finalPrice = max(0, $originalPrice - $discount);
                    $voucherData = $voucher;
                } else {
                    return $this->response->setJSON([
                        'success' => false,
                        'message' => 'Voucher tidak valid atau sudah tidak berlaku'
                    ]);
                }
            }

            return $this->response->setJSON([
                'success' => true,
                'original_price' => $originalPrice,
                'discount' => $discount,
                'final_price' => $finalPrice,
                'voucher' => $voucherData,
                'participation_type' => 'offline'
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error calculating price: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Terjadi kesalahan saat menghitung harga'
            ]);
        }
    }

    private function getEventsForPresenter($userId)
    {
        try {
            // Validate user ID
            if (!$userId) {
                log_message('error', 'User ID is null in getEventsForPresenter');
                return [];
            }

            // Get all active events with null safety
            $events = $this->eventModel
                ->where('is_active', true)
                ->orderBy('event_date', 'ASC')
                ->findAll();

            if (!$events) {
                log_message('info', 'No events found');
                return [];
            }

            foreach ($events as &$event) {
                try {
                    // Parse boolean fields safely
                    $event['is_active'] = $this->parseBoolean($event['is_active'] ?? false);
                    $event['registration_active'] = $this->parseBoolean($event['registration_active'] ?? false);
                    $event['abstract_submission_active'] = $this->parseBoolean($event['abstract_submission_active'] ?? false);

                    // Check registration status with error handling
                    $event['registration_open'] = $this->eventModel->isRegistrationOpen($event['id']);
                    $event['abstract_submission_open'] = $this->eventModel->isAbstractSubmissionOpen($event['id']);

                    // Check user's participation with null safety
                    $registration = null;
                    try {
                        $registration = $this->eventRegistrationModel->findUserReg($event['id'], $userId);
                    } catch (\Exception $e) {
                        log_message('error', 'Error getting registration for event ' . $event['id'] . ': ' . $e->getMessage());
                    }
                    
                    $event['user_registered'] = !empty($registration);
                    $event['registration_data'] = $registration;

                    // Check abstract submission with null safety
                    $abstract = null;
                    try {
                        $abstract = $this->abstrakModel
                            ->where('id_user', $userId)
                            ->where('event_id', $event['id'])
                            ->orderBy('tanggal_upload', 'DESC')
                            ->first();
                    } catch (\Exception $e) {
                        log_message('error', 'Error getting abstract for event ' . $event['id'] . ': ' . $e->getMessage());
                    }
                    
                    $event['abstract_data'] = $abstract;

                    // Check payment with null safety
                    $payment = null;
                    try {
                        $payment = $this->pembayaranModel
                            ->where('id_user', $userId)
                            ->where('event_id', $event['id'])
                            ->orderBy('tanggal_bayar', 'DESC')
                            ->first();
                    } catch (\Exception $e) {
                        log_message('error', 'Error getting payment for event ' . $event['id'] . ': ' . $e->getMessage());
                    }
                    
                    $event['payment_data'] = $payment;

                    // Calculate presenter price with null safety
                    $event['presenter_price'] = $event['presenter_fee_offline'] ?? 0;

                    // Determine event status for user
                    $event['user_status'] = $this->getUserEventStatus($registration, $abstract, $payment);

                    // Add time-based information with WIB timezone
                    $this->addTimeInfo($event);

                } catch (\Exception $e) {
                    log_message('error', 'Error processing event ' . $event['id'] . ': ' . $e->getMessage());
                    // Set default values for this event
                    $event['user_registered'] = false;
                    $event['user_status'] = 'not_registered';
                    $event['presenter_price'] = 0;
                    $event['registration_open'] = false;
                    $event['abstract_submission_open'] = false;
                }
            }

            return $events;

        } catch (\Exception $e) {
            log_message('error', 'Error in getEventsForPresenter: ' . $e->getMessage());
            return [];
        }
    }

    private function getUserEventStatus($registration, $abstract, $payment)
    {
        if (!$registration && !$abstract && !$payment) {
            return 'not_registered';
        }

        if ($payment && $payment['status'] === 'verified') {
            return 'payment_verified';
        }

        if ($payment && $payment['status'] === 'pending') {
            return 'payment_pending';
        }

        if ($abstract && $abstract['status'] === 'diterima') {
            return 'abstract_accepted';
        }

        if ($abstract && in_array($abstract['status'], ['menunggu', 'sedang_direview'])) {
            return 'abstract_pending';
        }

        if ($abstract && $abstract['status'] === 'revisi') {
            return 'abstract_revision';
        }

        if ($abstract && $abstract['status'] === 'ditolak') {
            return 'abstract_rejected';
        }

        if ($registration) {
            return 'registered';
        }

        return 'unknown';
    }

    private function addTimeInfo(&$event)
    {
        try {
            // Set timezone to WIB (Asia/Jakarta)
            $timezone = new \DateTimeZone('Asia/Jakarta');
            $now = new \DateTime('now', $timezone);
            
            // Event date with null safety
            $eventDateTime = null;
            if (!empty($event['event_date']) && !empty($event['event_time'])) {
                try {
                    $eventDateTime = new \DateTime($event['event_date'] . ' ' . $event['event_time'], $timezone);
                } catch (\Exception $e) {
                    log_message('error', 'Error parsing event datetime: ' . $e->getMessage());
                }
            }
            
            // Registration deadline with null safety
            $registrationDeadline = null;
            if (!empty($event['registration_deadline'])) {
                try {
                    $registrationDeadline = new \DateTime($event['registration_deadline'], $timezone);
                } catch (\Exception $e) {
                    log_message('error', 'Error parsing registration deadline: ' . $e->getMessage());
                }
            }
            
            // Abstract deadline with null safety
            $abstractDeadline = null;
            if (!empty($event['abstract_deadline'])) {
                try {
                    $abstractDeadline = new \DateTime($event['abstract_deadline'], $timezone);
                } catch (\Exception $e) {
                    log_message('error', 'Error parsing abstract deadline: ' . $e->getMessage());
                }
            }

            // Calculate time differences safely
            $event['time_info'] = [
                'current_time_wib' => $now->format('Y-m-d H:i:s'),
                'event_datetime_wib' => $eventDateTime ? $eventDateTime->format('Y-m-d H:i:s') : null,
                'is_past_event' => $eventDateTime ? ($now > $eventDateTime) : false,
                'days_until_event' => $eventDateTime ? $this->getDaysDifference($now, $eventDateTime) : null,
                'registration_deadline_wib' => $registrationDeadline ? $registrationDeadline->format('Y-m-d H:i:s') : null,
                'abstract_deadline_wib' => $abstractDeadline ? $abstractDeadline->format('Y-m-d H:i:s') : null,
                'days_until_registration_deadline' => $registrationDeadline ? $this->getDaysDifference($now, $registrationDeadline) : null,
                'days_until_abstract_deadline' => $abstractDeadline ? $this->getDaysDifference($now, $abstractDeadline) : null
            ];

            // Add formatted times for display with null safety
            $event['formatted_times'] = [
                'event_date' => $eventDateTime ? $eventDateTime->format('d F Y') : date('d F Y', strtotime($event['event_date'])),
                'event_time' => $eventDateTime ? $eventDateTime->format('H:i') : date('H:i', strtotime($event['event_time'])),
                'event_day' => $eventDateTime ? $eventDateTime->format('l') : null,
                'registration_deadline' => $registrationDeadline ? $registrationDeadline->format('d F Y H:i') : null,
                'abstract_deadline' => $abstractDeadline ? $abstractDeadline->format('d F Y H:i') : null
            ];

        } catch (\Exception $e) {
            log_message('error', 'Error in addTimeInfo: ' . $e->getMessage());
            // Set default time info
            $event['time_info'] = [
                'current_time_wib' => date('Y-m-d H:i:s'),
                'is_past_event' => false,
                'days_until_event' => null,
                'days_until_registration_deadline' => null,
                'days_until_abstract_deadline' => null
            ];
            
            $event['formatted_times'] = [
                'event_date' => date('d F Y', strtotime($event['event_date'])),
                'event_time' => date('H:i', strtotime($event['event_time'])),
                'event_day' => null,
                'registration_deadline' => null,
                'abstract_deadline' => null
            ];
        }
    }

    private function getDaysDifference(\DateTime $from, \DateTime $to)
    {
        try {
            $interval = $from->diff($to);
            $days = $interval->days;
            
            if ($from > $to) {
                $days = -$days;
            }
            
            return $days;
        } catch (\Exception $e) {
            log_message('error', 'Error calculating days difference: ' . $e->getMessage());
            return null;
        }
    }

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

    private function logActivity($userId, $activity)
    {
        try {
            $timezone = new \DateTimeZone('Asia/Jakarta');
            $now = new \DateTime('now', $timezone);
            
            $this->db->table('log_aktivitas')->insert([
                'id_user' => $userId,
                'aktivitas' => $activity,
                'waktu' => $now->format('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Failed to log activity: ' . $e->getMessage());
        }
    }

    /**
     * AJAX endpoint to refresh event statuses
     */
    public function refreshStatuses()
    {
        $userId = session('id_user');

        try {
            $events = $this->getEventsForPresenter($userId);
            
            return $this->response->setJSON([
                'success' => true,
                'events' => $events,
                'timestamp' => time()
            ]);

        } catch (\Exception $e) {
            log_message('error', 'Error refreshing event statuses: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error refreshing event statuses'
            ]);
        }
    }
}