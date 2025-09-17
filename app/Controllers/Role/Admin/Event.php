<?php

namespace App\Controllers\Role\Admin;

use App\Controllers\BaseController;
use App\Models\EventModel;
use App\Models\UserModel;
use App\Models\AbstrakModel;
use App\Models\PembayaranModel;
use App\Models\AbsensiModel;
use App\Models\EventRegistrationModel;
use App\Models\DokumenModel;

class Event extends BaseController
{
    protected $eventModel;
    protected $userModel;
    protected $abstrakModel;
    protected $pembayaranModel;
    protected $absensiModel;
    protected $registrationModel;
    protected $dokumenModel;
    protected $db;

    public function __construct()
    {
        $this->eventModel        = new EventModel();
        $this->userModel         = new UserModel();
        $this->abstrakModel      = new AbstrakModel();
        $this->pembayaranModel   = new PembayaranModel();
        $this->absensiModel      = new AbsensiModel();
        $this->registrationModel = new EventRegistrationModel();
        $this->dokumenModel      = new DokumenModel();
        $this->db                = \Config\Database::connect();
    }

    public function index()
    {
        try {
            $events = $this->getEventsWithStats();
            $stats  = $this->getDashboardStats();

            foreach ($events as &$e) {
                $e['is_active']                  = $this->parseBoolean($e['is_active']);
                $e['registration_active']        = $this->parseBoolean($e['registration_active']);
                $e['abstract_submission_active'] = $this->parseBoolean($e['abstract_submission_active']);

                $e['attendance_rate'] = ($e['verified_registrations'] ?? 0) > 0
                    ? round(($e['present_count'] / $e['verified_registrations']) * 100, 2) : 0;

                $e['capacity_filled'] = !empty($e['max_participants'])
                    ? round(($e['verified_registrations'] / $e['max_participants']) * 100, 2) : 0;

                $e['event_status']        = $this->calculateEventStatus($e);
                $e['registration_status'] = $this->calculateRegistrationStatus($e);
            }

            $data = ['events' => $events, 'stats' => $stats];

            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['success' => true, 'data' => $data, 'timestamp' => time()]);
            }
            return view('role/admin/event/index', $data);
        } catch (\Throwable $e) {
            log_message('error', 'Event index error: ' . $e->getMessage());
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['success' => false, 'message' => 'Terjadi kesalahan saat memuat data event']);
            }
            return redirect()->back()->with('error', 'Terjadi kesalahan saat memuat data event');
        }
    }

    private function getEventsWithStats()
    {
        $sql = "
            SELECT e.*,
                   COALESCE(regs.total_registrations,0)      AS total_registrations,
                   COALESCE(regs.verified_registrations,0)   AS verified_registrations,
                   COALESCE(regs.online_registrations,0)     AS online_registrations,
                   COALESCE(regs.offline_registrations,0)    AS offline_registrations,
                   COALESCE(revs.total_revenue,0)            AS total_revenue,
                   COALESCE(abs.total_abstracts,0)           AS total_abstracts,
                   COALESCE(atts.present_count,0)            AS present_count
            FROM events e
            LEFT JOIN (
                SELECT event_id,
                       COUNT(*) AS total_registrations,
                       COUNT(CASE WHEN status='verified' THEN 1 END)        AS verified_registrations,
                       COUNT(CASE WHEN participation_type='online' THEN 1 END)  AS online_registrations,
                       COUNT(CASE WHEN participation_type='offline' THEN 1 END) AS offline_registrations
                FROM pembayaran GROUP BY event_id
            ) regs ON regs.event_id = e.id
            LEFT JOIN (
                SELECT event_id, SUM(CASE WHEN status='verified' THEN jumlah ELSE 0 END) AS total_revenue
                FROM pembayaran GROUP BY event_id
            ) revs ON revs.event_id = e.id
            LEFT JOIN (
                SELECT event_id, COUNT(*) AS total_abstracts
                FROM abstrak GROUP BY event_id
            ) abs ON abs.event_id = e.id
            LEFT JOIN (
                SELECT event_id, COUNT(CASE WHEN status='hadir' THEN 1 END) AS present_count
                FROM absensi GROUP BY event_id
            ) atts ON atts.event_id = e.id
            ORDER BY e.event_date DESC, e.created_at DESC
        ";
        return $this->db->query($sql)->getResultArray();
    }

    private function getDashboardStats()
    {
        $totalEvents  = $this->eventModel->countAll();
        $activeEvents = $this->eventModel->where('is_active', true)->countAllResults();
        $verifiedRegs = $this->pembayaranModel->where('status', 'verified')->countAllResults();
        $totalRevenue = (float) ($this->pembayaranModel->selectSum('jumlah')->where('status', 'verified')->first()['jumlah'] ?? 0);

        return [
            'total_events'           => $totalEvents,
            'active_events'          => $activeEvents,
            'verified_registrations' => $verifiedRegs,
            'total_revenue'          => $totalRevenue,
        ];
    }

    public function store()
    {
        $validation = \Config\Services::validation();

        $rules = [
            'title'                  => 'required|min_length[3]|max_length[255]',
            'description'            => 'permit_empty|max_length[2000]',
            'event_date'             => 'required|valid_date',
            'event_time'             => 'required',
            'format'                 => 'required|in_list[both,online,offline]',
            'presenter_fee_offline'  => 'required|numeric|greater_than_equal_to[0]',
            'audience_fee_online'    => 'permit_empty|numeric|greater_than_equal_to[0]',
            'audience_fee_offline'   => 'permit_empty|numeric|greater_than_equal_to[0]',
            'max_participants'       => 'permit_empty|integer|greater_than[0]',
            'registration_deadline'  => 'permit_empty|valid_date',
            'abstract_deadline'      => 'permit_empty|valid_date',
        ];

        $format = $this->request->getPost('format');
        if (in_array($format, ['offline', 'both'], true)) {
            $rules['location'] = 'required|min_length[5]|max_length[255]';
        }
        if (in_array($format, ['online', 'both'], true)) {
            $rules['zoom_link'] = 'required|valid_url|max_length[500]';
        }
        if ($format === 'online') {
            $rules['audience_fee_online']  = 'required|numeric|greater_than_equal_to[0]';
        } elseif ($format === 'offline') {
            $rules['audience_fee_offline'] = 'required|numeric|greater_than_equal_to[0]';
        } else {
            $rules['audience_fee_online']  = 'required|numeric|greater_than_equal_to[0]';
            $rules['audience_fee_offline'] = 'required|numeric|greater_than_equal_to[0]';
        }

        if (!$this->validate($rules)) {
            return $this->handleValidationError($validation->getErrors());
        }

        $dateValidation = $this->validateEventDates();
        if (!$dateValidation['valid']) {
            return $this->handleError($dateValidation['message']);
        }

        $this->db->transStart();
        try {
            $data = $this->prepareEventData();

            if (!$this->eventModel->save($data)) {
                $err = $this->eventModel->errors();
                if (!$err) {
                    $dbErr = $this->db->error();
                    $msg   = $dbErr['message'] ?? 'Unknown DB error';
                } else {
                    $msg = implode(', ', $err);
                }
                throw new \Exception('Failed to create event: ' . $msg);
            }

            $newId = $this->eventModel->getInsertID();
            $this->logActivity(session('id_user'), "Created new event: {$data['title']} (ID: {$newId})");
            $this->db->transComplete();
            if ($this->db->transStatus() === false) {
                throw new \Exception('Transaction failed');
            }

            return $this->response->setJSON([
                'success'  => true,
                'message'  => 'Event berhasil dibuat!',
                'event_id' => $newId
            ]);
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'Event creation error: ' . $e->getMessage());
            return $this->handleError('Error: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $event = $this->eventModel->find($id);
        if (!$event) return $this->response->setJSON(['success' => false, 'message' => 'Event tidak ditemukan.']);

        $event['is_active']                  = $this->parseBoolean($event['is_active']);
        $event['registration_active']        = $this->parseBoolean($event['registration_active']);
        $event['abstract_submission_active'] = $this->parseBoolean($event['abstract_submission_active']);

        return $this->response->setJSON(['success' => true, 'event' => $event]);
    }

    public function update($id)
    {
        $event = $this->eventModel->find($id);
        if (!$event) return $this->handleError('Event tidak ditemukan.');

        $validation = \Config\Services::validation();
        $rules = [
            'title'                  => 'required|min_length[3]|max_length[255]',
            'description'            => 'permit_empty|max_length[2000]',
            'event_date'             => 'required|valid_date',
            'event_time'             => 'required',
            'format'                 => 'required|in_list[both,online,offline]',
            'presenter_fee_offline'  => 'required|numeric|greater_than_equal_to[0]',
            'audience_fee_online'    => 'permit_empty|numeric|greater_than_equal_to[0]',
            'audience_fee_offline'   => 'permit_empty|numeric|greater_than_equal_to[0]',
            'max_participants'       => 'permit_empty|integer|greater_than[0]',
            'registration_deadline'  => 'permit_empty|valid_date',
            'abstract_deadline'      => 'permit_empty|valid_date',
        ];

        $format = $this->request->getPost('format');
        if (in_array($format, ['offline', 'both'], true)) {
            $rules['location'] = 'required|min_length[5]|max_length[255]';
        }
        if (in_array($format, ['online', 'both'], true)) {
            $rules['zoom_link'] = 'required|valid_url|max_length[500]';
        }
        if ($format === 'online') {
            $rules['audience_fee_online']  = 'required|numeric|greater_than_equal_to[0]';
        } elseif ($format === 'offline') {
            $rules['audience_fee_offline'] = 'required|numeric|greater_than_equal_to[0]';
        } else {
            $rules['audience_fee_online']  = 'required|numeric|greater_than_equal_to[0]';
            $rules['audience_fee_offline'] = 'required|numeric|greater_than_equal_to[0]';
        }

        if (!$this->validate($rules)) {
            return $this->handleValidationError($validation->getErrors());
        }

        $this->db->transStart();
        try {
            $data = $this->prepareEventData();

            if (!$this->eventModel->update($id, $data)) {
                $err = $this->eventModel->errors();
                if (!$err) {
                    $dbErr = $this->db->error();
                    $msg   = $dbErr['message'] ?? 'Unknown DB error';
                } else {
                    $msg = implode(', ', $err);
                }
                throw new \Exception('Failed to update event: ' . $msg);
            }

            $this->logActivity(session('id_user'), "Updated event: {$data['title']} (ID: {$id})");
            $this->db->transComplete();
            if ($this->db->transStatus() === false) throw new \Exception('Transaction failed');

            return $this->response->setJSON(['success' => true, 'message' => 'Event berhasil diupdate!', 'event_id' => $id]);
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', 'Event update error: ' . $e->getMessage());
            return $this->handleError('Error: ' . $e->getMessage());
        }
    }

    /**
     * FORCE DELETE EVENT - Hapus paksa dengan semua dependencies
     */
    public function delete($id)
    {
        $event = $this->eventModel->find($id);
        if (!$event) {
            return $this->handleError('Event tidak ditemukan.');
        }

        $this->db->transStart();
        try {
            log_message('info', "Starting force delete for event ID: {$id} - {$event['title']}");

            // 1. Delete semua documents terkait event
            $documents = $this->dokumenModel->where('event_id', $id)->findAll();
            foreach ($documents as $doc) {
                // Hapus file fisik jika ada
                if (!empty($doc['file_path'])) {
                    $filePath = WRITEPATH . 'uploads/' . $doc['file_path'];
                    if (file_exists($filePath)) {
                        unlink($filePath);
                        log_message('info', "Deleted file: {$filePath}");
                    }
                }
            }
            $this->dokumenModel->where('event_id', $id)->delete();
            log_message('info', "Deleted " . count($documents) . " documents for event {$id}");

            // 2. Delete absensi
            $absensiCount = $this->absensiModel->where('event_id', $id)->countAllResults();
            $this->absensiModel->where('event_id', $id)->delete();
            log_message('info', "Deleted {$absensiCount} attendance records for event {$id}");

            // 3. Delete pembayaran dan files
            $payments = $this->pembayaranModel->where('event_id', $id)->findAll();
            foreach ($payments as $payment) {
                // Hapus bukti bayar jika ada
                if (!empty($payment['bukti_bayar']) && $payment['bukti_bayar'] !== 'no_file.txt') {
                    $buktiPath = WRITEPATH . 'uploads/pembayaran/' . $payment['bukti_bayar'];
                    if (file_exists($buktiPath)) {
                        unlink($buktiPath);
                        log_message('info', "Deleted payment proof: {$buktiPath}");
                    }
                }
            }
            $this->pembayaranModel->where('event_id', $id)->delete();
            log_message('info', "Deleted " . count($payments) . " payments for event {$id}");

            // 4. Delete event registrations
            $registrationsCount = $this->registrationModel->where('id_event', $id)->countAllResults();
            $this->registrationModel->where('id_event', $id)->delete();
            log_message('info', "Deleted {$registrationsCount} registrations for event {$id}");

            // 5. Delete abstraks dan files
            $abstraks = $this->abstrakModel->where('event_id', $id)->findAll();
            foreach ($abstraks as $abstrak) {
                // Hapus file abstrak
                if (!empty($abstrak['file_abstrak'])) {
                    $abstrakPath = WRITEPATH . 'uploads/abstraks/' . $abstrak['file_abstrak'];
                    if (file_exists($abstrakPath)) {
                        unlink($abstrakPath);
                        log_message('info', "Deleted abstract file: {$abstrakPath}");
                    }
                }

                // Delete reviews untuk abstrak ini
                $this->db->table('review')->where('id_abstrak', $abstrak['id_abstrak'])->delete();
            }
            $this->abstrakModel->where('event_id', $id)->delete();
            log_message('info', "Deleted " . count($abstraks) . " abstracts for event {$id}");

            // 6. Delete notifications terkait event ini
            $this->db->table('notifikasi')
                     ->like('message', $event['title'])
                     ->orLike('link', 'event/' . $id)
                     ->delete();

            // 7. Delete log aktivitas terkait event
            $this->db->table('log_aktivitas')
                     ->like('aktivitas', "event: {$event['title']}")
                     ->orLike('aktivitas', "Event: {$event['title']}")
                     ->orLike('aktivitas', "event (ID: {$id})")
                     ->delete();

            // 8. Akhirnya hapus event itu sendiri
            if (!$this->eventModel->delete($id)) {
                throw new \Exception('Failed to delete event from database');
            }

            $this->logActivity(session('id_user'), "FORCE DELETED event with all dependencies: {$event['title']} (ID: {$id})");
            
            $this->db->transComplete();
            if ($this->db->transStatus() === false) {
                throw new \Exception('Transaction failed during force delete');
            }

            log_message('info', "Successfully force deleted event {$id} with all dependencies");

            return $this->response->setJSON([
                'success' => true, 
                'message' => 'Event dan semua data terkait berhasil dihapus secara permanen!'
            ]);

        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', "Force delete event {$id} failed: " . $e->getMessage());
            log_message('error', "Stack trace: " . $e->getTraceAsString());
            
            return $this->handleError('Gagal menghapus event: ' . $e->getMessage());
        }
    }

    public function toggleStatus($id)
    {
        $event = $this->eventModel->find($id);
        if (!$event) return $this->handleError('Event tidak ditemukan.');

        $new = !$this->parseBoolean($event['is_active']);
        try {
            $this->eventModel->update($id, ['is_active' => $new]);
            $this->logActivity(session('id_user'),
                "Changed status for event '{$event['title']}' to " . ($new ? 'active' : 'inactive'));
            return $this->response->setJSON([
                'success'        => true,
                'message'        => $new ? 'Event berhasil diaktifkan!' : 'Event berhasil dinonaktifkan!',
                'new_status'     => $new,
                'new_status_text'=> $new ? 'Aktif' : 'Nonaktif',
                'can_delete'     => !$new
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Toggle status error: ' . $e->getMessage());
            return $this->handleError('Error: ' . $e->getMessage());
        }
    }

    public function toggleRegistration($id)
    {
        return $this->toggleEventFeature($id, 'registration_active', 'pendaftaran');
    }

    public function toggleAbstractSubmission($id)
    {
        return $this->toggleEventFeature($id, 'abstract_submission_active', 'submit abstrak');
    }

    public function detail($id)
    {
        $event = $this->eventModel->find($id);
        if (!$event) return redirect()->to('admin/event')->with('error', 'Event tidak ditemukan.');

        $event['is_active']                  = $this->parseBoolean($event['is_active']);
        $event['registration_active']        = $this->parseBoolean($event['registration_active']);
        $event['abstract_submission_active'] = $this->parseBoolean($event['abstract_submission_active']);

        $stats = $this->eventModel->getEventStats($id);
        return view('role/admin/event/detail', [
            'event'        => $event,
            'stats'        => $stats,
            'dependencies' => $this->checkEventDependencies($id)
        ]);
    }

    /* ================== Helpers ================== */

    private function toggleEventFeature($id, $field, $featureName)
    {
        $event = $this->eventModel->find($id);
        if (!$event) return $this->handleError('Event tidak ditemukan.');

        $new = !$this->parseBoolean($event[$field] ?? false);
        try {
            $this->eventModel->update($id, [$field => $new]);
            $this->logActivity(session('id_user'), "Event '{$event['title']}' {$featureName} " . ($new ? 'dibuka' : 'ditutup'));
            return $this->response->setJSON([
                'success'    => true,
                'message'    => ucfirst($featureName) . ($new ? ' berhasil dibuka!' : ' berhasil ditutup!'),
                'new_status' => $new
            ]);
        } catch (\Throwable $e) {
            log_message('error', "Toggle {$field} error: " . $e->getMessage());
            return $this->handleError('Error: ' . $e->getMessage());
        }
    }

    private function parseBoolean($v)
    {
        if ($v === null || $v === '') return false;
        if (is_bool($v)) return $v;
        if (is_numeric($v)) return (bool) intval($v);
        if (is_string($v)) {
            $v = strtolower(trim($v));
            return in_array($v, ['true','1','yes','on','y','t'], true);
        }
        return false;
    }

    private function calculateEventStatus($event)
    {
        try {
            date_default_timezone_set('Asia/Jakarta');
            $eventDT = new \DateTime($event['event_date'] . ' ' . $event['event_time']);
            $now     = new \DateTime();
            $hours   = ($now->getTimestamp() - $eventDT->getTimestamp()) / 3600;
            if ($hours < -1) return ['text' => 'Akan Datang', 'badge_class' => 'bg-info'];
            if ($hours < 0)  return ['text' => 'Segera Dimulai', 'badge_class' => 'bg-warning'];
            if ($hours <= 4) return ['text' => 'Sedang Berlangsung', 'badge_class' => 'bg-success'];
            return ['text' => 'Sudah Selesai', 'badge_class' => 'bg-danger'];
        } catch (\Throwable) {
            return ['text' => 'Status Tidak Diketahui', 'badge_class' => 'bg-secondary'];
        }
    }

    private function calculateRegistrationStatus($e)
    {
        if (!$this->parseBoolean($e['registration_active'])) {
            return ['text' => 'Tutup', 'badge_class' => 'bg-danger'];
        }
        $now       = time();
        $eventDate = strtotime($e['event_date']);
        $deadline  = !empty($e['registration_deadline']) ? strtotime($e['registration_deadline']) : null;

        if ($deadline && $now > $deadline) return ['text' => 'Sudah Berakhir', 'badge_class' => 'bg-warning'];
        if ($now > $eventDate)             return ['text' => 'Event Sudah Lewat', 'badge_class' => 'bg-danger'];
        return ['text' => 'Buka', 'badge_class' => 'bg-success'];
    }

    private function sanitizeCurrency($val)
    {
        $num = preg_replace('/\D+/', '', (string) $val);
        return $num === '' ? 0 : (int) $num;
    }

    private function prepareEventData()
    {
        $format = $this->request->getPost('format');

        // angka harga dibersihkan dari titik/koma
        $audOnline  = $this->sanitizeCurrency($this->request->getPost('audience_fee_online'));
        $audOffline = $this->sanitizeCurrency($this->request->getPost('audience_fee_offline'));

        // set nol untuk channel yang tidak dipakai
        if ($format === 'online')  $audOffline = 0;
        if ($format === 'offline') $audOnline  = 0;

        return [
            'title'        => (string) $this->request->getPost('title'),
            'description'  => (string) $this->request->getPost('description'),
            'event_date'   => $this->request->getPost('event_date'),
            'event_time'   => $this->request->getPost('event_time'),
            'format'       => $format,
            'location'     => $this->request->getPost('location'),
            'zoom_link'    => $this->request->getPost('zoom_link'),

            'presenter_fee_offline' => $this->sanitizeCurrency($this->request->getPost('presenter_fee_offline')),
            'audience_fee_online'   => $audOnline,
            'audience_fee_offline'  => $audOffline,

            'max_participants'      => ($this->request->getPost('max_participants') === '' ? null
                                        : (int) $this->request->getPost('max_participants')),
            'registration_deadline' => $this->normalizeDateTime($this->request->getPost('registration_deadline')),
            'abstract_deadline'     => $this->normalizeDateTime($this->request->getPost('abstract_deadline')),

            'registration_active'        => (bool) $this->request->getPost('registration_active'),
            'abstract_submission_active' => (bool) $this->request->getPost('abstract_submission_active'),
            'is_active'                  => (bool) $this->request->getPost('is_active'),
        ];
    }

    private function validateEventDates()
    {
        $tz        = new \DateTimeZone('Asia/Jakarta');
        $eventDate = (string) $this->request->getPost('event_date');
        $regDL     = $this->request->getPost('registration_deadline');
        $absDL     = $this->request->getPost('abstract_deadline');

        $eventDT = \DateTime::createFromFormat('Y-m-d', $eventDate, $tz);
        if (!$eventDT) {
            return ['valid' => false, 'message' => 'Format tanggal event tidak valid.'];
        }

        $today = new \DateTime('today', $tz);

        if ($eventDT <= $today) {
            return ['valid' => false, 'message' => 'Tanggal event tidak boleh hari ini atau di masa lalu.'];
        }

        if (!empty($regDL)) {
            $regTS = strtotime($regDL);
            if ($regTS === false) {
                return ['valid' => false, 'message' => 'Format batas pendaftaran tidak valid.'];
            }
            if ($regTS >= $eventDT->getTimestamp()) {
                return ['valid' => false, 'message' => 'Batas pendaftaran harus sebelum tanggal event.'];
            }
        }

        if (!empty($absDL)) {
            $absTS = strtotime($absDL);
            if ($absTS === false) {
                return ['valid' => false, 'message' => 'Format batas submit abstrak tidak valid.'];
            }
            if ($absTS >= $eventDT->getTimestamp()) {
                return ['valid' => false, 'message' => 'Batas submit abstrak harus sebelum tanggal event.'];
            }
        }

        return ['valid' => true];
    }

    private function checkEventDependencies($eventId)
    {
        $registrations = $this->pembayaranModel->where('event_id', $eventId)->countAllResults();
        $abstracts     = $this->abstrakModel->where('event_id', $eventId)->countAllResults();
        $attendance    = $this->absensiModel->where('event_id', $eventId)->countAllResults();

        return [
            'has_dependencies' => ($registrations + $abstracts + $attendance) > 0,
            'registrations'    => $registrations,
            'abstracts'        => $abstracts,
            'attendance'       => $attendance,
        ];
    }

    private function handleValidationError($errors)
    {
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'errors' => $errors]);
        }
        return redirect()->back()->withInput()->with('errors', $errors);
    }

    private function handleError($message)
    {
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['success' => false, 'message' => $message]);
        }
        return redirect()->back()->with('error', $message);
    }

    private function logActivity($userId, $activity)
    {
        try {
            $this->db->table('log_aktivitas')->insert([
                'id_user'   => $userId,
                'aktivitas' => $activity,
                'waktu'     => date('Y-m-d H:i:s')
            ]);
        } catch (\Throwable $e) {
            log_message('error', 'Failed to log activity: ' . $e->getMessage());
        }
    }

    private function normalizeDateTime(?string $val): ?string
    {
        if (!$val) return null;
        $ts = strtotime($val);
        return $ts ? date('Y-m-d H:i:s', $ts) : null;
    }
}