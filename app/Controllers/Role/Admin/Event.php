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

    /* ====================== LIST / DASHBOARD ====================== */

    public function index()
    {
        try {
            $events = $this->getEventsWithStats();
            $stats  = $this->getDashboardStats();

            foreach ($events as &$e) {
                $e['is_active']                    = $this->parseBoolean($e['is_active']);
                $e['registration_active']          = $this->parseBoolean($e['registration_active']);
                $e['abstract_submission_active']   = $this->parseBoolean($e['abstract_submission_active']);
                $e['full_paper_submission_active'] = $this->parseBoolean($e['full_paper_submission_active'] ?? false);

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

    /* ====================== CREATE / UPDATE ====================== */

    public function store()
    {
        $validation = \Config\Services::validation();

        // **Pastikan checkbox jadi '0'/'1' (default ON di create)**
        $this->normalizeToggleToBit('full_paper_submission_active', true);

        $rules = [
            'title'                        => 'required|min_length[3]|max_length[255]',
            'description'                  => 'permit_empty|max_length[2000]',
            'event_date'                   => 'required|valid_date',
            'event_time'                   => 'required',
            'format'                       => 'required|in_list[both,online,offline]',
            'presenter_fee_offline'        => 'required|integer|greater_than_equal_to[0]',
            'audience_fee_online'          => 'permit_empty|integer|greater_than_equal_to[0]',
            'audience_fee_offline'         => 'permit_empty|integer|greater_than_equal_to[0]',
            'max_participants'             => 'permit_empty|integer|greater_than[0]',
            'registration_deadline'        => 'permit_empty|valid_date',
            'abstract_deadline'            => 'permit_empty|valid_date',
            'full_paper_deadline'          => 'permit_empty|valid_date',
            // >> biar cocok dengan Model juga
            'full_paper_submission_active' => 'required|in_list[0,1]',
        ];

        $format = $this->request->getPost('format');
        if (in_array($format, ['offline', 'both'], true)) $rules['location'] = 'required|min_length[5]|max_length[255]';
        if (in_array($format, ['online', 'both'], true))  $rules['zoom_link'] = 'required|valid_url|max_length[500]';
        if ($format === 'online')  $rules['audience_fee_online']  = 'required|integer|greater_than_equal_to[0]';
        elseif ($format === 'offline') $rules['audience_fee_offline'] = 'required|integer|greater_than_equal_to[0]';
        else { $rules['audience_fee_online'] = $rules['audience_fee_offline'] = 'required|integer|greater_than_equal_to[0]'; }

        if (!$this->validate($rules)) {
            return $this->handleValidationError($validation->getErrors());
        }

        $dateValidation = $this->validateEventDates();
        if (!$dateValidation['valid']) {
            return $this->handleError($dateValidation['message']);
        }

        $this->db->transStart();
        try {
            $data = $this->prepareEventData(true);
            $data = $this->ensureFullPaperDeadlineOnEnable($data);

            if (!$this->eventModel->save($data)) {
                $err = $this->eventModel->errors();
                $msg = $err ? implode(', ', $err) : ($this->db->error()['message'] ?? 'Unknown DB error');
                throw new \Exception('Failed to create event: ' . $msg);
            }

            $newId = $this->eventModel->getInsertID();
            $this->logActivity(session('id_user'), "Created new event: {$data['title']} (ID: {$newId})");
            $this->db->transComplete();
            if ($this->db->transStatus() === false) throw new \Exception('Transaction failed');

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

        // booleans → untuk form edit
        $event['is_active']                    = $this->parseBoolean($event['is_active']);
        $event['registration_active']          = $this->parseBoolean($event['registration_active']);
        $event['abstract_submission_active']   = $this->parseBoolean($event['abstract_submission_active']);
        $event['full_paper_submission_active'] = $this->parseBoolean($event['full_paper_submission_active'] ?? false);

        foreach (['presenter_fee_offline','audience_fee_online','audience_fee_offline'] as $priceCol) {
            if (isset($event[$priceCol])) $event[$priceCol] = (int)$event[$priceCol];
        }

        return $this->response->setJSON(['success' => true, 'event' => $event]);
    }

    public function update($id)
    {
        $event = $this->eventModel->find($id);
        if (!$event) return $this->handleError('Event tidak ditemukan.');

        $validation = \Config\Services::validation();

        // paksa checkbox → '0'/'1' (default pakai kondisi sekarang)
        $currentFP = $this->parseBoolean($event['full_paper_submission_active'] ?? false);
        $this->normalizeToggleToBit('full_paper_submission_active', $currentFP);

        $rules = [
            'title'                        => 'required|min_length[3]|max_length[255]',
            'description'                  => 'permit_empty|max_length[2000]',
            'event_date'                   => 'required|valid_date',
            'event_time'                   => 'required',
            'format'                       => 'required|in_list[both,online,offline]',
            'presenter_fee_offline'        => 'required|integer|greater_than_equal_to[0]',
            'audience_fee_online'          => 'permit_empty|integer|greater_than_equal_to[0]',
            'audience_fee_offline'         => 'permit_empty|integer|greater_than_equal_to[0]',
            'max_participants'             => 'permit_empty|integer|greater_than[0]',
            'registration_deadline'        => 'permit_empty|valid_date',
            'abstract_deadline'            => 'permit_empty|valid_date',
            'full_paper_deadline'          => 'permit_empty|valid_date',
            'full_paper_submission_active' => 'required|in_list[0,1]',
        ];

        $format = $this->request->getPost('format');
        if (in_array($format, ['offline', 'both'], true)) $rules['location'] = 'required|min_length[5]|max_length[255]';
        if (in_array($format, ['online', 'both'], true))  $rules['zoom_link'] = 'required|valid_url|max_length[500]';
        if ($format === 'online') $rules['audience_fee_online'] = 'required|integer|greater_than_equal_to[0]';
        elseif ($format === 'offline') $rules['audience_fee_offline'] = 'required|integer|greater_than_equal_to[0]';
        else { $rules['audience_fee_online'] = $rules['audience_fee_offline'] = 'required|integer|greater_than_equal_to[0]'; }

        if (!$this->validate($rules)) {
            return $this->handleValidationError($validation->getErrors());
        }

        $dateValidation = $this->validateEventDates();
        if (!$dateValidation['valid']) {
            return $this->handleError($dateValidation['message']);
        }

        $this->db->transStart();
        try {
            $data = $this->prepareEventData(false, $event);
            $data = $this->ensureFullPaperDeadlineOnEnable($data, $event);

            if (!$this->eventModel->update($id, $data)) {
                $err = $this->eventModel->errors();
                $msg = $err ? implode(', ', $err) : ($this->db->error()['message'] ?? 'Unknown DB error');
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

    /* ====================== DELETE ====================== */

    public function delete($id)
    {
        $event = $this->eventModel->find($id);
        if (!$event) return $this->handleError('Event tidak ditemukan.');

        $this->db->transStart();
        try {
            $this->dokumenModel->where('event_id', $id)->delete();
            $this->absensiModel->where('event_id', $id)->delete();
            $this->pembayaranModel->where('event_id', $id)->delete();
            $this->registrationModel->where('id_event', $id)->delete();
            $this->abstrakModel->where('event_id', $id)->delete();
            $this->db->table('notifikasi')->like('link', 'event/'.$id)->delete();
            $this->db->table('log_aktivitas')->like('aktivitas', "(ID: {$id})")->delete();

            if (!$this->eventModel->delete($id)) {
                throw new \Exception('Failed to delete event from database');
            }

            $this->logActivity(session('id_user'), "FORCE DELETED event with all dependencies: {$event['title']} (ID: {$id})");
            $this->db->transComplete();
            if ($this->db->transStatus() === false) throw new \Exception('Transaction failed during force delete');

            return $this->response->setJSON(['success' => true, 'message' => 'Event dan semua data terkait berhasil dihapus secara permanen!']);
        } catch (\Throwable $e) {
            $this->db->transRollback();
            log_message('error', "Force delete event {$id} failed: " . $e->getMessage());
            return $this->handleError('Gagal menghapus event: ' . $e->getMessage());
        }
    }

    /* ====================== TOGGLES ====================== */

    public function toggleStatus($id)
    {
        $event = $this->eventModel->find($id);
        if (!$event) return $this->handleError('Event tidak ditemukan.');

        $new = !$this->parseBoolean($event['is_active']);
        try {
            $this->eventModel->update($id, ['is_active' => $new]);
            $this->logActivity(session('id_user'), "Changed status for event '{$event['title']}' to " . ($new ? 'active' : 'inactive'));
            return $this->response->setJSON([
                'success' => true,
                'message' => $new ? 'Event berhasil diaktifkan!' : 'Event berhasil dinonaktifkan!',
                'new_status' => $new
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

    /* ====================== HELPERS ====================== */

    private function toggleEventFeature($id, $field, $featureName)
    {
        $event = $this->eventModel->find($id);
        if (!$event) return $this->handleError('Event tidak ditemukan.');

        $col = $this->pickExistingColumn('events', [$field]);
        if (!$col) return $this->handleError("Kolom '{$field}' tidak ditemukan di tabel events.");

        $new = !$this->parseBoolean($event[$col] ?? false);
        try {
            $ok = $this->updateEventFields($id, [$col => (bool)$new]);
            if (!$ok) throw new \Exception('Tidak ada data yang bisa diupdate.');
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

    private function normalizeTime(?string $time): ?string
    {
        if (!$time) return null;
        $time = trim($time);
        if (preg_match('/^\d{2}:\d{2}(?::\d{2})?$/', $time)) return substr($time, 0, 5);
        $ts = strtotime($time);
        return $ts ? date('H:i', $ts) : null;
    }

    private function makeEventDateTime(array $event, \DateTimeZone $tz): ?\DateTime
    {
        try {
            $date = $event['event_date'] ?? null;
            $time = $this->normalizeTime($event['event_time'] ?? null) ?? '00:00';
            if (!$date) return null;
            return new \DateTime($date.' '.$time, $tz);
        } catch (\Throwable) {
            return null;
        }
    }

    private function calculateEventStatus($event)
    {
        try {
            date_default_timezone_set('Asia/Jakarta');
            $t = $this->normalizeTime($event['event_time'] ?? '00:00') ?? '00:00';
            $eventDT = new \DateTime(($event['event_date'] ?? date('Y-m-d')) . ' ' . $t);
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

    private function prepareEventData(bool $isCreate = false, array $existingEvent = null)
    {
        $format = $this->request->getPost('format');

        $audOnline  = (int) ($this->request->getPost('audience_fee_online') ?? 0);
        $audOffline = (int) ($this->request->getPost('audience_fee_offline') ?? 0);
        $presenter  = (int) ($this->request->getPost('presenter_fee_offline') ?? 0);

        if ($format === 'online')  $audOffline = 0;
        if ($format === 'offline') $audOnline  = 0;

        $isActive  = $this->parseBoolean($this->request->getPost('is_active'));
        $regActive = $this->parseBoolean($this->request->getPost('registration_active'));
        $absActive = $this->parseBoolean($this->request->getPost('abstract_submission_active'));

        $fpActivePost = $this->request->getPost('full_paper_submission_active');
        $fpActive = $isCreate ? true : $this->parseBoolean($fpActivePost);

        return [
            'title'        => (string) $this->request->getPost('title'),
            'description'  => (string) $this->request->getPost('description'),
            'event_date'   => $this->request->getPost('event_date'),
            'event_time'   => $this->normalizeTime($this->request->getPost('event_time')),
            'format'       => $format,
            'location'     => $this->request->getPost('location'),
            'zoom_link'    => $this->request->getPost('zoom_link'),

            'presenter_fee_offline' => $presenter,
            'audience_fee_online'   => $audOnline,
            'audience_fee_offline'  => $audOffline,

            'max_participants'        => ($this->request->getPost('max_participants') === '' ? null
                                          : (int) $this->request->getPost('max_participants')),
            'registration_deadline'   => $this->normalizeDateTime($this->request->getPost('registration_deadline')),
            'abstract_deadline'       => $this->normalizeDateTime($this->request->getPost('abstract_deadline')),
            'full_paper_deadline'     => $this->normalizeDateTime($this->request->getPost('full_paper_deadline')),

            // >>> kirim STRING '0' atau '1' <<<
            'registration_active'          => $regActive ? '1' : '0',
            'abstract_submission_active'   => $absActive ? '1' : '0',
            'full_paper_submission_active' => $fpActive  ? '1' : '0',
            'is_active'                    => $isActive  ? '1' : '0',
        ];
    }

    private function ensureFullPaperDeadlineOnEnable(array $data, array $existingEvent = null): array
    {
        if (empty($data['full_paper_submission_active']) || $data['full_paper_submission_active'] === '0') return $data;

        $tz  = new \DateTimeZone('Asia/Jakarta');
        $evt = $existingEvent ?? $data;

        $eventDT = $this->makeEventDateTime([
            'event_date' => $evt['event_date'] ?? null,
            'event_time' => $evt['event_time'] ?? null,
        ], $tz);
        if (!$eventDT) return $data;

        $now = new \DateTime('now', $tz);

        $fullVal = $data['full_paper_deadline'] ?? ($existingEvent['full_paper_deadline'] ?? null);
        $fixed   = $this->computeSafeFullPaperDeadline($eventDT, $now, $fullVal);

        $useFixed = false;
        if (!$fullVal) $useFixed = true;
        else {
            $ts = strtotime($fullVal);
            if (!$ts || $ts < $now->getTimestamp() || $ts >= $eventDT->getTimestamp()) $useFixed = true;
        }

        if ($useFixed) $data['full_paper_deadline'] = $fixed;
        return $data;
    }

    private function computeSafeFullPaperDeadline(\DateTime $eventDT, \DateTime $now, ?string $current): string
    {
        $candidate = (clone $eventDT)->modify('-1 day')->setTime(23, 59, 0);
        $min = (clone $now)->modify('+2 hours');
        if ($candidate <= $min) $candidate = (clone $eventDT)->modify('-1 hour');
        if ($candidate >= $eventDT) $candidate = (clone $eventDT)->modify('-1 minute');
        return $candidate->format('Y-m-d H:i:s');
    }

    private function validateEventDates()
    {
        $tz        = new \DateTimeZone('Asia/Jakarta');
        $eventDate = (string) $this->request->getPost('event_date');
        $eventTime = $this->normalizeTime($this->request->getPost('event_time'));
        $regDL     = $this->request->getPost('registration_deadline');
        $absDL     = $this->request->getPost('abstract_deadline');
        $fullDL    = $this->request->getPost('full_paper_deadline');

        if (!$eventDate || !$eventTime) {
            return ['valid' => false, 'message' => 'Format tanggal/waktu event tidak valid.'];
        }

        try {
            $eventDT = new \DateTime($eventDate.' '.$eventTime, $tz);
        } catch (\Throwable) {
            return ['valid' => false, 'message' => 'Format tanggal/waktu event tidak valid.'];
        }

        $maxDeadline = (clone $eventDT)->modify('-1 day')->setTime(23,59,0);
        $now = new \DateTime('now', $tz);

        $check = function (?string $val, string $label) use ($now, $maxDeadline) {
            if (empty($val)) return null;
            $ts = strtotime($val);
            if ($ts === false) return "$label tidak valid.";
            if ($ts < $now->getTimestamp()) return "$label tidak boleh di masa lalu.";
            if ($ts > $maxDeadline->getTimestamp()) return "$label harus sebelum hari event (H-1 23:59).";
            return null;
        };

        foreach ([
            'Batas pendaftaran'       => $regDL,
            'Batas submit abstrak'    => $absDL,
            'Batas submit full paper' => $fullDL,
        ] as $label => $val) {
            if ($msg = $check($val, $label)) return ['valid' => false, 'message' => $msg];
        }

        return ['valid' => true];
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

    /* ====================== DB UTIL ====================== */

    private function columnExists(string $table, string $column): bool
    {
        try {
            $fields = $this->db->getFieldData($table);
            foreach ($fields as $f) {
                if (strcasecmp($f->name, $column) === 0) return true;
            }
        } catch (\Throwable) {}
        return false;
    }

    private function pickExistingColumn(string $table, array $candidates): ?string
    {
        foreach ($candidates as $c) {
            if ($this->columnExists($table, $c)) return $c;
        }
        return null;
    }

    private function updateEventFields(int $id, array $payload): bool
    {
        if (empty($payload)) return false;
        $filtered = [];
        foreach ($payload as $k => $v) {
            if ($this->columnExists('events', $k)) $filtered[$k] = $v;
        }
        if (empty($filtered)) return false;
        $ok = $this->db->table('events')->where('id', $id)->update($filtered);
        if ($ok === false) return false;
        return ($this->db->affectedRows() >= 0);
    }

    /* ====================== SMALL HELPER ====================== */

    private function normalizeToggleToBit(string $name, ?bool $default = null): void
    {
        $post = $this->request->getPost();

        if (!array_key_exists($name, $post)) {
            if ($default !== null) {
                $post[$name] = $default ? '1' : '0';
                $this->request->setGlobal('post', $post);
            }
            return;
        }

        $val = $post[$name];
        if (is_bool($val)) {
            $post[$name] = $val ? '1' : '0';
        } elseif (is_numeric($val)) {
            $post[$name] = ((int)$val) ? '1' : '0';
        } else {
            $s = strtolower(trim((string)$val));
            $post[$name] = in_array($s, ['1','true','on','yes','y','t'], true) ? '1' : '0';
        }

        $this->request->setGlobal('post', $post);
    }
}