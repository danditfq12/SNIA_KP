<?php

namespace App\Controllers\Role\Admin;

use App\Controllers\BaseController;
use App\Models\EventModel;
use App\Models\UserModel;
use App\Models\AbstrakModel;
use App\Models\PembayaranModel;
use App\Models\AbsensiModel;
use App\Models\EventRegistrationModel;        // <-- ADD
use App\Services\NotificationService;         // <-- ADD

class Event extends BaseController
{
    protected $eventModel;
    protected $userModel;
    protected $abstrakModel;
    protected $pembayaranModel;
    protected $absensiModel;

    public function __construct()
    {
        $this->eventModel      = new EventModel();
        $this->userModel       = new UserModel();
        $this->abstrakModel    = new AbstrakModel();
        $this->pembayaranModel = new PembayaranModel();
        $this->absensiModel    = new AbsensiModel();
    }

    public function index()
    {
        $events = $this->eventModel->getEventsWithStats();

        foreach ($events as &$event) {
            $event['registration_breakdown']   = $this->getRegistrationBreakdown($event['id']);
            $event['registration_open']        = $this->eventModel->isRegistrationOpen($event['id']);
            $event['abstract_open']            = $this->eventModel->isAbstractSubmissionOpen($event['id']);
            $event['recent_registrations']     = $this->pembayaranModel
                ->select('pembayaran.*, users.nama_lengkap, users.role')
                ->join('users', 'users.id_user = pembayaran.id_user')
                ->where('pembayaran.event_id', $event['id'])
                ->orderBy('pembayaran.tanggal_bayar', 'DESC')
                ->limit(3)
                ->findAll();

            $db = \Config\Database::connect();
            $roleStats = $db->query("
                SELECT u.role, p.participation_type, COUNT(*) as count
                FROM pembayaran p
                JOIN users u ON u.id_user = p.id_user
                WHERE p.event_id = ? AND p.status = 'verified'
                GROUP BY u.role, p.participation_type
            ", [$event['id']])->getResultArray();

            $event['presenter_registrations']      = 0;
            $event['audience_online_registrations']  = 0;
            $event['audience_offline_registrations'] = 0;

            foreach ($roleStats as $stat) {
                if ($stat['role'] === 'presenter') {
                    $event['presenter_registrations'] += $stat['count'];
                } elseif ($stat['role'] === 'audience') {
                    if ($stat['participation_type'] === 'online') {
                        $event['audience_online_registrations'] += $stat['count'];
                    } else {
                        $event['audience_offline_registrations'] += $stat['count'];
                    }
                }
            }

            $revenueStats = $db->query("
                SELECT p.participation_type, SUM(p.jumlah) as revenue
                FROM pembayaran p
                WHERE p.event_id = ? AND p.status = 'verified'
                GROUP BY p.participation_type
            ", [$event['id']])->getResultArray();

            $event['online_revenue']  = 0;
            $event['offline_revenue'] = 0;

            foreach ($revenueStats as $revenue) {
                if ($revenue['participation_type'] === 'online') {
                    $event['online_revenue']  = $revenue['revenue'];
                } else {
                    $event['offline_revenue'] = $revenue['revenue'];
                }
            }
        }
        unset($event);

        $data = [
            'events'          => $events,
            'total_events'    => $this->eventModel->countAll(),
            'active_events'   => $this->eventModel->where('is_active', true)->countAllResults(),
            'upcoming_events' => $this->eventModel->where('event_date >=', date('Y-m-d'))
                                                 ->where('is_active', true)
                                                 ->countAllResults(),
        ];

        return view('role/admin/event/index', $data);
    }

    public function store()
    {
        $validation = \Config\Services::validation();

        $rules = [
            'title'                 => 'required|min_length[3]|max_length[255]',
            'description'           => 'max_length[1000]',
            'event_date'            => 'required|valid_date',
            'event_time'            => 'required',
            'format'                => 'required|in_list[both,online,offline]',
            'presenter_fee_offline' => 'required|numeric|greater_than_equal_to[0]',
            'audience_fee_online'   => 'required|numeric|greater_than_equal_to[0]',
            'audience_fee_offline'  => 'required|numeric|greater_than_equal_to[0]',
            'max_participants'      => 'permit_empty|integer|greater_than[0]',
            'registration_deadline' => 'permit_empty|valid_date',
            'abstract_deadline'     => 'permit_empty|valid_date',
        ];

        if ($this->request->getPost('format') === 'offline') {
            $rules['location'] = 'required|min_length[5]|max_length[255]';
        } elseif ($this->request->getPost('format') === 'online') {
            $rules['zoom_link'] = 'permit_empty|valid_url|max_length[500]';
        } else {
            $rules['location']  = 'required|min_length[5]|max_length[255]';
            $rules['zoom_link'] = 'permit_empty|valid_url|max_length[500]';
        }

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        $eventDate            = $this->request->getPost('event_date');
        $registrationDeadline = $this->request->getPost('registration_deadline');
        $abstractDeadline     = $this->request->getPost('abstract_deadline');

        if (strtotime($eventDate) <= time()) {
            return redirect()->back()->withInput()->with('error', 'Tanggal event harus di masa depan.');
        }
        if ($registrationDeadline && strtotime($registrationDeadline) >= strtotime($eventDate)) {
            return redirect()->back()->withInput()->with('error', 'Batas pendaftaran harus sebelum tanggal event.');
        }
        if ($abstractDeadline && strtotime($abstractDeadline) >= strtotime($eventDate)) {
            return redirect()->back()->withInput()->with('error', 'Batas submit abstrak harus sebelum tanggal event.');
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $data = [
                'title'                       => $this->request->getPost('title'),
                'description'                 => $this->request->getPost('description'),
                'event_date'                  => $eventDate,
                'event_time'                  => $this->request->getPost('event_time'),
                'format'                      => $this->request->getPost('format'),
                'location'                    => $this->request->getPost('location'),
                'zoom_link'                   => $this->request->getPost('zoom_link'),
                'presenter_fee_offline'       => $this->request->getPost('presenter_fee_offline'),
                'audience_fee_online'         => $this->request->getPost('audience_fee_online'),
                'audience_fee_offline'        => $this->request->getPost('audience_fee_offline'),
                'max_participants'            => $this->request->getPost('max_participants') ?: null,
                'registration_deadline'       => $registrationDeadline ?: null,
                'abstract_deadline'           => $abstractDeadline ?: null,
                'registration_active'         => $this->request->getPost('registration_active') ? true : false,
                'abstract_submission_active'  => $this->request->getPost('abstract_submission_active') ? true : false,
                'is_active'                   => true,
            ];

            if (!$this->eventModel->save($data)) {
                throw new \Exception('Failed to create event: ' . implode(', ', $this->eventModel->errors()));
            }

            $eventId = (int) $this->eventModel->getInsertID(); // <-- ID baru

            $this->logActivity(session('id_user'), "Created new event: {$data['title']}");

            $db->transComplete();
            if ($db->transStatus() === false) {
                throw new \Exception('Transaction failed');
            }

            /* =========================
             * TRIGGER NOTIF: EVENT BARU
             * ========================= */
            try {
                $notif     = new NotificationService();
                $presenter = $this->userModel->select('id_user')->where('role', 'presenter')->where('status', 'aktif')->findAll();
                $audience  = $this->userModel->select('id_user')->where('role', 'audience')->where('status', 'aktif')->findAll();

                foreach ($presenter as $u) {
                    $notif->notify(
                        (int) $u['id_user'],
                        'event',
                        'Event baru tersedia',
                        "Event \"{$data['title']}\" telah dipublish. Pendaftaran dibuka.",
                        site_url('presenter/events/detail/' . $eventId)
                    );
                }
                foreach ($audience as $u) {
                    $notif->notify(
                        (int) $u['id_user'],
                        'event',
                        'Event baru tersedia',
                        "Event \"{$data['title']}\" telah dipublish. Pendaftaran dibuka.",
                        site_url('audience/events/detail/' . $eventId)
                    );
                }
            } catch (\Throwable $e) {
                log_message('error', 'Notif event baru gagal: ' . $e->getMessage());
            }
            /* ===== END TRIGGER ===== */

            return redirect()->to('admin/event')->with('success', 'Event berhasil dibuat!');

        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'Event creation error: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function edit($id)
    {
        $event = $this->eventModel->find($id);
        if (!$event) {
            return redirect()->to('admin/event')->with('error', 'Event tidak ditemukan.');
        }
        return $this->response->setJSON($event);
    }

    public function update($id)
    {
        $event = $this->eventModel->find($id);
        if (!$event) {
            return redirect()->to('admin/event')->with('error', 'Event tidak ditemukan.');
        }

        $validation = \Config\Services::validation();
        $rules = [
            'title'                 => 'required|min_length[3]|max_length[255]',
            'description'           => 'max_length[1000]',
            'event_date'            => 'required|valid_date',
            'event_time'            => 'required',
            'format'                => 'required|in_list[both,online,offline]',
            'presenter_fee_offline' => 'required|numeric|greater_than_equal_to[0]',
            'audience_fee_online'   => 'required|numeric|greater_than_equal_to[0]',
            'audience_fee_offline'  => 'required|numeric|greater_than_equal_to[0]',
            'max_participants'      => 'permit_empty|integer|greater_than[0]',
            'registration_deadline' => 'permit_empty|valid_date',
            'abstract_deadline'     => 'permit_empty|valid_date',
        ];

        if ($this->request->getPost('format') === 'offline') {
            $rules['location'] = 'required|min_length[5]|max_length[255]';
        } elseif ($this->request->getPost('format') === 'online') {
            $rules['zoom_link'] = 'permit_empty|valid_url|max_length[500]';
        } else {
            $rules['location']  = 'required|min_length[5]|max_length[255]';
            $rules['zoom_link'] = 'permit_empty|valid_url|max_length[500]';
        }

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        $eventDate            = $this->request->getPost('event_date');
        $registrationDeadline = $this->request->getPost('registration_deadline');
        $abstractDeadline     = $this->request->getPost('abstract_deadline');

        if ($event['event_date'] !== $eventDate && strtotime($eventDate) <= time()) {
            return redirect()->back()->withInput()->with('error', 'Tanggal event harus di masa depan.');
        }
        if ($registrationDeadline && strtotime($registrationDeadline) >= strtotime($eventDate)) {
            return redirect()->back()->withInput()->with('error', 'Batas pendaftaran harus sebelum tanggal event.');
        }
        if ($abstractDeadline && strtotime($abstractDeadline) >= strtotime($eventDate)) {
            return redirect()->back()->withInput()->with('error', 'Batas submit abstrak harus sebelum tanggal event.');
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            $data = [
                'title'                      => $this->request->getPost('title'),
                'description'                => $this->request->getPost('description'),
                'event_date'                 => $eventDate,
                'event_time'                 => $this->request->getPost('event_time'),
                'format'                     => $this->request->getPost('format'),
                'location'                   => $this->request->getPost('location'),
                'zoom_link'                  => $this->request->getPost('zoom_link'),
                'presenter_fee_offline'      => $this->request->getPost('presenter_fee_offline'),
                'audience_fee_online'        => $this->request->getPost('audience_fee_online'),
                'audience_fee_offline'       => $this->request->getPost('audience_fee_offline'),
                'max_participants'           => $this->request->getPost('max_participants') ?: null,
                'registration_deadline'      => $registrationDeadline ?: null,
                'abstract_deadline'          => $abstractDeadline ?: null,
                'registration_active'        => $this->request->getPost('registration_active') ? true : false,
                'abstract_submission_active' => $this->request->getPost('abstract_submission_active') ? true : false,
                'is_active'                  => $this->request->getPost('is_active') ? true : false,
            ];

            if (!$this->eventModel->update($id, $data)) {
                throw new \Exception('Failed to update event: ' . implode(', ', $this->eventModel->errors()));
            }

            $this->logActivity(session('id_user'), "Updated event: {$data['title']} (ID: {$id})");

            $db->transComplete();
            if ($db->transStatus() === false) {
                throw new \Exception('Transaction failed');
            }

            /* ===============================
             * TRIGGER NOTIF: EVENT DI-UPDATE
             * (kirim ke peserta yg terdaftar)
             * =============================== */
            try {
                $notif = new NotificationService();

                // Audience: ambil dari tabel registrasi
                $audRegs = (new EventRegistrationModel())
                    ->select('id_user')
                    ->where('id_event', $id)
                    ->findAll();

                // Presenter: pakai tabel pembayaran (unik per user)
                $presPays = $this->pembayaranModel
                    ->select('id_user')
                    ->where('event_id', $id)
                    ->groupBy('id_user')
                    ->findAll();

                $ids = array_unique(array_map('intval', array_merge(
                    array_column($audRegs, 'id_user'),
                    array_column($presPays, 'id_user')
                )));

                if ($ids) {
                    $users = $this->userModel
                        ->select('id_user, role')
                        ->whereIn('id_user', $ids)
                        ->findAll();

                    foreach ($users as $u) {
                        $link = ($u['role'] === 'presenter')
                            ? site_url('presenter/events/detail/' . $id)
                            : site_url('audience/events/detail/' . $id);

                        $notif->notify(
                            (int) $u['id_user'],
                            'event',
                            'Perubahan pada event',
                            "Detail event \"{$data['title']}\" telah diperbarui. Mohon cek kembali informasinya.",
                            $link
                        );
                    }
                }
            } catch (\Throwable $e) {
                log_message('error', 'Notif update event gagal: ' . $e->getMessage());
            }
            /* ===== END TRIGGER ===== */

            return redirect()->to('admin/event')->with('success', 'Event berhasil diupdate!');

        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'Event update error: ' . $e->getMessage());
            return redirect()->back()->withInput()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function delete($id)
    {
        $event = $this->eventModel->find($id);
        if (!$event) {
            return redirect()->to('admin/event')->with('error', 'Event tidak ditemukan.');
        }

        $hasRegistrations = $this->pembayaranModel->where('event_id', $id)->countAllResults() > 0;
        $hasAbstracts     = $this->abstrakModel->where('event_id', $id)->countAllResults() > 0;
        $hasAttendance    = $this->absensiModel->where('event_id', $id)->countAllResults() > 0;

        if ($hasRegistrations || $hasAbstracts || $hasAttendance) {
            $relatedData = [];
            if ($hasRegistrations) $relatedData[] = 'pendaftaran';
            if ($hasAbstracts)     $relatedData[] = 'abstrak';
            if ($hasAttendance)    $relatedData[] = 'data absensi';

            return redirect()->back()->with('error',
                'Tidak dapat menghapus event yang sudah memiliki ' . implode(', ', $relatedData) .
                '. Silakan hapus data terkait terlebih dahulu atau nonaktifkan event ini.'
            );
        }

        $db = \Config\Database::connect();
        $db->transStart();

        try {
            if (!$this->eventModel->delete($id)) {
                throw new \Exception('Failed to delete event');
            }

            $this->logActivity(session('id_user'), "Deleted event: {$event['title']} (ID: {$id})");

            $db->transComplete();
            if ($db->transStatus() === false) {
                throw new \Exception('Transaction failed');
            }

            return redirect()->to('admin/event')->with('success', 'Event berhasil dihapus!');

        } catch (\Exception $e) {
            $db->transRollback();
            log_message('error', 'Event deletion error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error menghapus event: ' . $e->getMessage());
        }
    }

    public function detail($id)
    {
        $event = $this->eventModel->find($id);
        if (!$event) {
            return redirect()->to('admin/event')->with('error', 'Event tidak ditemukan.');
        }

        $stats                = $this->eventModel->getEventStats($id);
        $registrationBreakdown= $this->getRegistrationBreakdown($id);
        $recentRegistrations  = $this->pembayaranModel
            ->select('pembayaran.*, users.nama_lengkap, users.email, users.role')
            ->join('users', 'users.id_user = pembayaran.id_user')
            ->where('pembayaran.event_id', $id)
            ->orderBy('pembayaran.tanggal_bayar', 'DESC')
            ->limit(10)
            ->findAll();

        $recentAbstracts = $this->abstrakModel
            ->select('abstrak.*, users.nama_lengkap')
            ->join('users', 'users.id_user = abstrak.id_user')
            ->where('abstrak.event_id', $id)
            ->orderBy('abstrak.tanggal_upload', 'DESC')
            ->limit(10)
            ->findAll();

        $pricingMatrix   = $this->eventModel->getPricingMatrix($id);
        $revenueBreakdown= $this->getRevenueBreakdown($id);

        $data = [
            'event'                 => $event,
            'stats'                 => $stats,
            'registration_breakdown'=> $registrationBreakdown,
            'recent_registrations'  => $recentRegistrations,
            'recent_abstracts'      => $recentAbstracts,
            'pricing_matrix'        => $pricingMatrix,
            'revenue_breakdown'     => $revenueBreakdown,
            'registration_open'     => $this->eventModel->isRegistrationOpen($id),
            'abstract_open'         => $this->eventModel->isAbstractSubmissionOpen($id),
        ];

        return view('role/admin/event/detail', $data);
    }

    public function toggleRegistration($id)
    {
        $event = $this->eventModel->find($id);
        if (!$event) {
            return redirect()->back()->with('error', 'Event tidak ditemukan.');
        }

        $newStatus = !$event['registration_active'];

        try {
            if ($this->eventModel->update($id, ['registration_active' => $newStatus])) {
                $this->logActivity(session('id_user'), "Changed registration status for event '{$event['title']}' to " . ($newStatus ? 'active' : 'inactive'));

                /* =====================================
                 * TRIGGER: buka/tutup pendaftaran event
                 * (broadcast ke presenter & audience)
                 * ===================================== */
                try {
                    $notif     = new NotificationService();
                    $presenter = $this->userModel->select('id_user')->where('role', 'presenter')->where('status', 'aktif')->findAll();
                    $audience  = $this->userModel->select('id_user')->where('role', 'audience')->where('status', 'aktif')->findAll();

                    $title = $newStatus ? 'Pendaftaran event dibuka' : 'Pendaftaran event ditutup';
                    $msg   = "Event \"{$event['title']}\" " . ($newStatus ? 'telah membuka' : 'menutup') . ' pendaftaran.';

                    foreach ($presenter as $u) {
                        $notif->notify((int)$u['id_user'], 'event', $title, $msg, site_url('presenter/events/detail/' . $id));
                    }
                    foreach ($audience as $u) {
                        $notif->notify((int)$u['id_user'], 'event', $title, $msg, site_url('audience/events/detail/' . $id));
                    }
                } catch (\Throwable $e) {
                    log_message('error', 'Notif toggle registration gagal: ' . $e->getMessage());
                }
                /* =========== END TRIGGER ============ */

                $message = $newStatus ? 'Pendaftaran berhasil dibuka!' : 'Pendaftaran berhasil ditutup!';
                return redirect()->back()->with('success', $message);
            }

            return redirect()->back()->with('error', 'Gagal mengubah status pendaftaran: ' . implode(', ', $this->eventModel->errors()));
        } catch (\Exception $e) {
            log_message('error', 'Toggle registration error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function toggleAbstractSubmission($id)
    {
        $event = $this->eventModel->find($id);
        if (!$event) {
            return redirect()->back()->with('error', 'Event tidak ditemukan.');
        }

        $newStatus = !$event['abstract_submission_active'];

        try {
            if ($this->eventModel->update($id, ['abstract_submission_active' => $newStatus])) {
                $this->logActivity(session('id_user'), "Changed abstract submission status for event '{$event['title']}' to " . ($newStatus ? 'active' : 'inactive'));

                /* ====================================
                 * TRIGGER: buka/tutup submit abstrak
                 * (khusus role presenter)
                 * ==================================== */
                try {
                    $notif     = new NotificationService();
                    $presenter = $this->userModel->select('id_user')->where('role', 'presenter')->where('status', 'aktif')->findAll();

                    $title = $newStatus ? 'Submit abstrak dibuka' : 'Submit abstrak ditutup';
                    $msg   = "Event \"{$event['title']}\" " . ($newStatus ? 'membuka' : 'menutup') . ' pengajuan abstrak.';

                    foreach ($presenter as $u) {
                        $notif->notify((int)$u['id_user'], 'event', $title, $msg, site_url('presenter/events/detail/' . $id));
                    }
                } catch (\Throwable $e) {
                    log_message('error', 'Notif toggle abstract gagal: ' . $e->getMessage());
                }
                /* =========== END TRIGGER ============ */

                $message = $newStatus ? 'Submit abstrak berhasil dibuka!' : 'Submit abstrak berhasil ditutup!';
                return redirect()->back()->with('success', $message);
            }

            return redirect()->back()->with('error', 'Gagal mengubah status submit abstrak: ' . implode(', ', $this->eventModel->errors()));
        } catch (\Exception $e) {
            log_message('error', 'Toggle abstract submission error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function toggleStatus($id)
    {
        $event = $this->eventModel->find($id);
        if (!$event) {
            return redirect()->back()->with('error', 'Event tidak ditemukan.');
        }

        $newStatus = !$event['is_active'];
        log_message('info', "Event ID {$id}: Current status=" . ($event['is_active'] ? 'true' : 'false') . ", New status=" . ($newStatus ? 'true' : 'false'));

        try {
            $updated = $this->eventModel->update($id, ['is_active' => $newStatus]);
            log_message('info', "Update result: " . ($updated ? 'success' : 'failed'));

            if ($updated) {
                $updatedEvent = $this->eventModel->find($id);
                log_message('info', "Verified status after update: " . ($updatedEvent['is_active'] ? 'true' : 'false'));

                $this->logActivity(session('id_user'), "Changed status for event '{$event['title']}' to " . ($newStatus ? 'active' : 'inactive'));

                /* =====================================================
                 * TRIGGER: aktif/nonaktif event
                 *  - jika aktif: broadcast ke presenter & audience
                 *  - jika nonaktif: beri tahu peserta yang sudah terdaftar
                 * ===================================================== */
                try {
                    $notif = new NotificationService();

                    if ($newStatus) {
                        // Broadcast aktif
                        $presenter = $this->userModel->select('id_user')->where('role', 'presenter')->where('status', 'aktif')->findAll();
                        $audience  = $this->userModel->select('id_user')->where('role', 'audience')->where('status', 'aktif')->findAll();
                        $title = 'Event diaktifkan kembali';
                        $msg   = "Event \"{$event['title']}\" kini aktif kembali.";

                        foreach ($presenter as $u) {
                            $notif->notify((int)$u['id_user'], 'event', $title, $msg, site_url('presenter/events/detail/' . $id));
                        }
                        foreach ($audience as $u) {
                            $notif->notify((int)$u['id_user'], 'event', $title, $msg, site_url('audience/events/detail/' . $id));
                        }
                    } else {
                        // Nonaktif → info ke peserta yang sudah terdaftar
                        $audRegs = (new EventRegistrationModel())->select('id_user')->where('id_event', $id)->findAll();
                        $presPays= $this->pembayaranModel->select('id_user')->where('event_id', $id)->groupBy('id_user')->findAll();

                        $ids = array_unique(array_map('intval', array_merge(
                            array_column($audRegs, 'id_user'),
                            array_column($presPays, 'id_user')
                        )));

                        if ($ids) {
                            $users = $this->userModel->select('id_user, role')->whereIn('id_user', $ids)->findAll();
                            foreach ($users as $u) {
                                $link = ($u['role'] === 'presenter')
                                    ? site_url('presenter/events/detail/' . $id)
                                    : site_url('audience/events/detail/' . $id);

                                $notif->notify(
                                    (int)$u['id_user'],
                                    'event',
                                    'Event dinonaktifkan',
                                    "Event \"{$event['title']}\" saat ini dinonaktifkan. Harap pantau informasi selanjutnya.",
                                    $link
                                );
                            }
                        }
                    }
                } catch (\Throwable $e) {
                    log_message('error', 'Notif toggle status event gagal: ' . $e->getMessage());
                }
                /* =================== END TRIGGER =================== */

                $message = $newStatus ? 'Event berhasil diaktifkan!' : 'Event berhasil dinonaktifkan!';
                return redirect()->back()->with('success', $message);
            }

            log_message('error', "Failed to update event status: " . implode(', ', $this->eventModel->errors()));
            return redirect()->back()->with('error', 'Gagal mengubah status event: ' . implode(', ', $this->eventModel->errors()));

        } catch (\Exception $e) {
            log_message('error', 'Toggle status error: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Error: ' . $e->getMessage());
        }
    }

    public function export()
    {
        $events   = $this->eventModel->getEventsWithStats();
        $filename = 'events_' . date('Y-m-d_H-i-s') . '.csv';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');
        fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

        fputcsv($output, [
            'ID','Title','Date','Time','Format','Location','Zoom Link',
            'Presenter Fee (Offline)','Audience Fee (Online)','Audience Fee (Offline)',
            'Max Participants','Total Registrations','Online Registrations','Offline Registrations',
            'Verified Registrations','Total Abstracts','Total Revenue','Status',
            'Registration Active','Abstract Submission Active','Created At',
        ]);

        foreach ($events as $event) {
            fputcsv($output, [
                $event['id'],
                $event['title'],
                date('d/m/Y', strtotime($event['event_date'])),
                $event['event_time'],
                ucfirst($event['format']),
                $event['location'] ?? '',
                $event['zoom_link'] ?? '',
                'Rp ' . number_format($event['presenter_fee_offline'], 0, ',', '.'),
                'Rp ' . number_format($event['audience_fee_online'], 0, ',', '.'),
                'Rp ' . number_format($event['audience_fee_offline'], 0, ',', '.'),
                $event['max_participants'] ?: 'Unlimited',
                $event['total_registrations'],
                $event['online_registrations'],
                $event['offline_registrations'],
                $event['verified_registrations'],
                $event['total_abstracts'],
                'Rp ' . number_format($event['total_revenue'], 0, ',', '.'),
                $event['is_active'] ? 'Active' : 'Inactive',
                $event['registration_active'] ? 'Yes' : 'No',
                $event['abstract_submission_active'] ? 'Yes' : 'No',
                date('d/m/Y H:i', strtotime($event['created_at'])),
            ]);
        }
        fclose($output);
    }

    public function statistics()
    {
        $data = [
            'events_by_month'         => $this->getEventsByMonth(),
            'registration_stats'      => $this->getRegistrationStats(),
            'revenue_by_event'        => $this->getRevenueByEvent(),
            'abstract_submission_stats'=> $this->getAbstractSubmissionStats(),
            'participation_breakdown' => $this->getParticipationBreakdown(),
            'monthly_revenue'         => $this->getMonthlyRevenue(),
        ];
        return $this->response->setJSON($data);
    }

    private function getRegistrationBreakdown($eventId)
    {
        $db = \Config\Database::connect();
        return $db->query("
            SELECT u.role, p.participation_type, p.status, COUNT(*) as count, SUM(p.jumlah) as total_amount
            FROM pembayaran p
            JOIN users u ON u.id_user = p.id_user
            WHERE p.event_id = ?
            GROUP BY u.role, p.participation_type, p.status
            ORDER BY u.role, p.participation_type, p.status
        ", [$eventId])->getResultArray();
    }

    private function getRevenueBreakdown($eventId)
    {
        $db = \Config\Database::connect();
        return $db->query("
            SELECT u.role, p.participation_type, COUNT(*) as registrations, SUM(p.jumlah) as revenue, AVG(p.jumlah) as avg_amount
            FROM pembayaran p
            JOIN users u ON u.id_user = p.id_user
            WHERE p.event_id = ? AND p.status = 'verified'
            GROUP BY u.role, p.participation_type
            ORDER BY revenue DESC
        ", [$eventId])->getResultArray();
    }

    private function getEventsByMonth()
    {
        $data = [];
        for ($i = 11; $i >= 0; $i--) {
            $month     = date('Y-m', strtotime("-$i months"));
            $monthName = date('M Y', strtotime($month . '-01'));

            $startDate = $month . '-01';
            $endDate   = $month . '-' . date('t', strtotime($startDate));

            $count = $this->eventModel->where('event_date >=', $startDate)->where('event_date <=', $endDate)->countAllResults();
            $data[] = ['month' => $monthName, 'count' => $count];
        }
        return $data;
    }

    private function getRegistrationStats()
    {
        return $this->pembayaranModel
            ->select('COUNT(*) as total, status, participation_type')
            ->groupBy('status, participation_type')
            ->findAll();
    }

    private function getRevenueByEvent()
    {
        // NOTE: biarkan apa adanya sesuai kode awalmu
        return $this->pembayaranModel
            ->select('e.title, e.id as event_id, SUM(pembayaran.jumlah) as total_revenue, COUNT(*) as registrations, pembayaran.participation_type')
            ->join('events e', 'e.id = pembayaran.event_id')
            ->where('pembayaran.status', 'verified')
            ->groupBy('e.id, e.title, pembayaran.participation_type')
            ->orderBy('total_revenue', 'DESC')
            ->limit(10)
            ->findAll();
    }

    private function getAbstractSubmissionStats()
    {
        return $this->abstrakModel
            ->select('COUNT(*) as total, status')
            ->groupBy('status')
            ->findAll();
    }

    private function getParticipationBreakdown()
    {
        return $this->pembayaranModel
            ->select('u.role, pembayaran.participation_type, COUNT(*) as count, SUM(pembayaran.jumlah) as revenue')
            ->join('users u', 'u.id_user = pembayaran.id_user')
            ->where('pembayaran.status', 'verified')
            ->groupBy('u.role, pembayaran.participation_type')
            ->findAll();
    }

    private function getMonthlyRevenue()
    {
        $data = [];
        for ($i = 11; $i >= 0; $i--) {
            $month     = date('Y-m', strtotime("-$i months"));
            $monthName = date('M Y', strtotime($month . '-01'));

            $startDate = $month . '-01';
            $endDate   = $month . '-' . date('t', strtotime($startDate));

            $revenue = $this->pembayaranModel
                ->selectSum('jumlah')
                ->where('status', 'verified')
                ->where('tanggal_bayar >=', $startDate)
                ->where('tanggal_bayar <=', $endDate . ' 23:59:59')
                ->first()['jumlah'] ?? 0;

            $data[] = ['month' => $monthName, 'revenue' => $revenue];
        }
        return $data;
    }

    private function logActivity($userId, $activity)
    {
        $db = \Config\Database::connect();
        try {
            $db->table('log_aktivitas')->insert([
                'id_user'   => $userId,
                'aktivitas' => $activity,
                'waktu'     => date('Y-m-d H:i:s'),
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Failed to log activity: ' . $e->getMessage());
        }
    }
}
