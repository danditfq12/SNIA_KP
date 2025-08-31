<?php

namespace App\Controllers\Role\Admin;

use App\Controllers\BaseController;
use App\Models\EventModel;
use App\Models\UserModel;
use App\Models\AbstrakModel;
use App\Models\PembayaranModel;

class Event extends BaseController
{
    protected $eventModel;
    protected $userModel;
    protected $abstrakModel;
    protected $pembayaranModel;

    public function __construct()
    {
        $this->eventModel = new EventModel();
        $this->userModel = new UserModel();
        $this->abstrakModel = new AbstrakModel();
        $this->pembayaranModel = new PembayaranModel();
    }

    public function index()
    {
        // Get all events with statistics
        $events = $this->eventModel->getEventsWithStats();
        
        $data = [
            'events' => $events,
            'total_events' => $this->eventModel->countAll(),
            'active_events' => $this->eventModel->where('is_active', true)->countAllResults(),
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
            'title' => 'required|min_length[3]|max_length[255]',
            'description' => 'max_length[1000]',
            'event_date' => 'required|valid_date',
            'event_time' => 'required',
            'format' => 'required|in_list[both,online,offline]',
            'presenter_fee_offline' => 'required|numeric|greater_than_equal_to[0]',
            'audience_fee_online' => 'required|numeric|greater_than_equal_to[0]',
            'audience_fee_offline' => 'required|numeric|greater_than_equal_to[0]',
            'max_participants' => 'integer|greater_than[0]',
            'registration_deadline' => 'valid_date',
            'abstract_deadline' => 'valid_date'
        ];

        // Additional validation based on format
        if ($this->request->getPost('format') === 'offline') {
            $rules['location'] = 'required|min_length[5]|max_length[255]';
        } else if ($this->request->getPost('format') === 'online') {
            $rules['zoom_link'] = 'valid_url|max_length[500]';
        } else if ($this->request->getPost('format') === 'both') {
            $rules['location'] = 'required|min_length[5]|max_length[255]';
            $rules['zoom_link'] = 'valid_url|max_length[500]';
        }

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        // Validate dates
        $eventDate = $this->request->getPost('event_date');
        $registrationDeadline = $this->request->getPost('registration_deadline');
        $abstractDeadline = $this->request->getPost('abstract_deadline');

        if (strtotime($eventDate) <= time()) {
            return redirect()->back()->withInput()->with('error', 'Tanggal event harus di masa depan.');
        }

        if ($registrationDeadline && strtotime($registrationDeadline) >= strtotime($eventDate)) {
            return redirect()->back()->withInput()->with('error', 'Batas pendaftaran harus sebelum tanggal event.');
        }

        if ($abstractDeadline && strtotime($abstractDeadline) >= strtotime($eventDate)) {
            return redirect()->back()->withInput()->with('error', 'Batas submit abstrak harus sebelum tanggal event.');
        }

        $data = [
            'title' => $this->request->getPost('title'),
            'description' => $this->request->getPost('description'),
            'event_date' => $eventDate,
            'event_time' => $this->request->getPost('event_time'),
            'format' => $this->request->getPost('format'),
            'location' => $this->request->getPost('location'),
            'zoom_link' => $this->request->getPost('zoom_link'),
            'presenter_fee_offline' => $this->request->getPost('presenter_fee_offline'),
            'audience_fee_online' => $this->request->getPost('audience_fee_online'),
            'audience_fee_offline' => $this->request->getPost('audience_fee_offline'),
            'max_participants' => $this->request->getPost('max_participants') ?: null,
            'registration_deadline' => $registrationDeadline ?: null,
            'abstract_deadline' => $abstractDeadline ?: null,
            'registration_active' => $this->request->getPost('registration_active') ? true : false,
            'abstract_submission_active' => $this->request->getPost('abstract_submission_active') ? true : false,
            'is_active' => true
        ];

        try {
            if ($this->eventModel->save($data)) {
                return redirect()->to('admin/event')->with('success', 'Event berhasil dibuat!');
            } else {
                $errors = $this->eventModel->errors();
                return redirect()->back()->withInput()->with('error', 'Gagal membuat event: ' . implode(', ', $errors));
            }
        } catch (\Exception $e) {
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
            'title' => 'required|min_length[3]|max_length[255]',
            'description' => 'max_length[1000]',
            'event_date' => 'required|valid_date',
            'event_time' => 'required',
            'format' => 'required|in_list[both,online,offline]',
            'presenter_fee_offline' => 'required|numeric|greater_than_equal_to[0]',
            'audience_fee_online' => 'required|numeric|greater_than_equal_to[0]',
            'audience_fee_offline' => 'required|numeric|greater_than_equal_to[0]',
            'max_participants' => 'integer|greater_than[0]',
            'registration_deadline' => 'valid_date',
            'abstract_deadline' => 'valid_date'
        ];

        // Additional validation based on format
        if ($this->request->getPost('format') === 'offline') {
            $rules['location'] = 'required|min_length[5]|max_length[255]';
        } else if ($this->request->getPost('format') === 'online') {
            $rules['zoom_link'] = 'valid_url|max_length[500]';
        } else if ($this->request->getPost('format') === 'both') {
            $rules['location'] = 'required|min_length[5]|max_length[255]';
            $rules['zoom_link'] = 'valid_url|max_length[500]';
        }

        if (!$this->validate($rules)) {
            return redirect()->back()->withInput()->with('errors', $validation->getErrors());
        }

        $eventDate = $this->request->getPost('event_date');
        $registrationDeadline = $this->request->getPost('registration_deadline');
        $abstractDeadline = $this->request->getPost('abstract_deadline');

        // Only validate future date for new events or when changing date
        if ($event['event_date'] !== $eventDate && strtotime($eventDate) <= time()) {
            return redirect()->back()->withInput()->with('error', 'Tanggal event harus di masa depan.');
        }

        if ($registrationDeadline && strtotime($registrationDeadline) >= strtotime($eventDate)) {
            return redirect()->back()->withInput()->with('error', 'Batas pendaftaran harus sebelum tanggal event.');
        }

        if ($abstractDeadline && strtotime($abstractDeadline) >= strtotime($eventDate)) {
            return redirect()->back()->withInput()->with('error', 'Batas submit abstrak harus sebelum tanggal event.');
        }

        $data = [
            'title' => $this->request->getPost('title'),
            'description' => $this->request->getPost('description'),
            'event_date' => $eventDate,
            'event_time' => $this->request->getPost('event_time'),
            'format' => $this->request->getPost('format'),
            'location' => $this->request->getPost('location'),
            'zoom_link' => $this->request->getPost('zoom_link'),
            'presenter_fee_offline' => $this->request->getPost('presenter_fee_offline'),
            'audience_fee_online' => $this->request->getPost('audience_fee_online'),
            'audience_fee_offline' => $this->request->getPost('audience_fee_offline'),
            'max_participants' => $this->request->getPost('max_participants') ?: null,
            'registration_deadline' => $registrationDeadline ?: null,
            'abstract_deadline' => $abstractDeadline ?: null,
            'registration_active' => $this->request->getPost('registration_active') ? true : false,
            'abstract_submission_active' => $this->request->getPost('abstract_submission_active') ? true : false,
            'is_active' => $this->request->getPost('is_active') ? true : false
        ];

        if ($this->eventModel->update($id, $data)) {
            return redirect()->to('admin/event')->with('success', 'Event berhasil diupdate!');
        } else {
            return redirect()->back()->withInput()->with('error', 'Gagal mengupdate event.');
        }
    }

    public function delete($id)
    {
        $event = $this->eventModel->find($id);
        
        if (!$event) {
            return redirect()->to('admin/event')->with('error', 'Event tidak ditemukan.');
        }

        // Check if event has registrations or abstracts
        $hasRegistrations = $this->pembayaranModel->where('event_id', $id)->countAllResults() > 0;
        $hasAbstracts = $this->abstrakModel->where('event_id', $id)->countAllResults() > 0;

        if ($hasRegistrations || $hasAbstracts) {
            return redirect()->back()->with('error', 'Tidak dapat menghapus event yang sudah memiliki pendaftar atau abstrak.');
        }

        if ($this->eventModel->delete($id)) {
            return redirect()->to('admin/event')->with('success', 'Event berhasil dihapus!');
        } else {
            return redirect()->back()->with('error', 'Gagal menghapus event.');
        }
    }

    public function detail($id)
    {
        $event = $this->eventModel->find($id);
        
        if (!$event) {
            return redirect()->to('admin/event')->with('error', 'Event tidak ditemukan.');
        }

        // Get event statistics with new breakdown
        $stats = $this->eventModel->getEventStats($id);
        
        // Get recent registrations
        $recentRegistrations = $this->pembayaranModel
                                   ->select('pembayaran.*, users.nama_lengkap, users.email, users.role')
                                   ->join('users', 'users.id_user = pembayaran.id_user')
                                   ->where('pembayaran.event_id', $id)
                                   ->orderBy('pembayaran.tanggal_bayar', 'DESC')
                                   ->limit(10)
                                   ->findAll();

        // Get recent abstracts
        $recentAbstracts = $this->abstrakModel
                               ->select('abstrak.*, users.nama_lengkap')
                               ->join('users', 'users.id_user = abstrak.id_user')
                               ->where('abstrak.event_id', $id)
                               ->orderBy('abstrak.tanggal_upload', 'DESC')
                               ->limit(10)
                               ->findAll();

        // Get pricing matrix
        $pricingMatrix = $this->eventModel->getPricingMatrix($id);

        $data = [
            'event' => $event,
            'stats' => $stats,
            'recent_registrations' => $recentRegistrations,
            'recent_abstracts' => $recentAbstracts,
            'pricing_matrix' => $pricingMatrix,
            'registration_open' => $this->eventModel->isRegistrationOpen($id),
            'abstract_open' => $this->eventModel->isAbstractSubmissionOpen($id)
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
        
        if ($this->eventModel->update($id, ['registration_active' => $newStatus])) {
            $message = $newStatus ? 'Pendaftaran berhasil dibuka!' : 'Pendaftaran berhasil ditutup!';
            return redirect()->back()->with('success', $message);
        } else {
            return redirect()->back()->with('error', 'Gagal mengubah status pendaftaran.');
        }
    }

    public function toggleAbstractSubmission($id)
    {
        $event = $this->eventModel->find($id);
        
        if (!$event) {
            return redirect()->back()->with('error', 'Event tidak ditemukan.');
        }

        $newStatus = !$event['abstract_submission_active'];
        
        if ($this->eventModel->update($id, ['abstract_submission_active' => $newStatus])) {
            $message = $newStatus ? 'Submit abstrak berhasil dibuka!' : 'Submit abstrak berhasil ditutup!';
            return redirect()->back()->with('success', $message);
        } else {
            return redirect()->back()->with('error', 'Gagal mengubah status submit abstrak.');
        }
    }

    public function toggleStatus($id)
    {
        $event = $this->eventModel->find($id);
        
        if (!$event) {
            return redirect()->back()->with('error', 'Event tidak ditemukan.');
        }

        $newStatus = !$event['is_active'];
        
        if ($this->eventModel->update($id, ['is_active' => $newStatus])) {
            $message = $newStatus ? 'Event berhasil diaktifkan!' : 'Event berhasil dinonaktifkan!';
            return redirect()->back()->with('success', $message);
        } else {
            return redirect()->back()->with('error', 'Gagal mengubah status event.');
        }
    }

    public function export()
    {
        $events = $this->eventModel->getEventsWithStats();
        
        $filename = 'events_' . date('Y-m-d') . '.csv';
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        // CSV Headers
        fputcsv($output, [
            'ID', 'Judul', 'Tanggal', 'Waktu', 'Format', 'Lokasi', 'Zoom Link',
            'Fee Presenter (Offline)', 'Fee Audience (Online)', 'Fee Audience (Offline)',
            'Max Peserta', 'Total Registrasi', 'Online Registrasi', 'Offline Registrasi',
            'Registrasi Verified', 'Total Abstrak', 'Total Revenue', 'Status'
        ]);
        
        // CSV Data
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
                $event['is_active'] ? 'Aktif' : 'Nonaktif'
            ]);
        }
        
        fclose($output);
    }

    public function statistics()
    {
        // Event statistics for charts
        $data = [
            'events_by_month' => $this->getEventsByMonth(),
            'registration_stats' => $this->getRegistrationStats(),
            'revenue_by_event' => $this->getRevenueByEvent(),
            'abstract_submission_stats' => $this->getAbstractSubmissionStats(),
            'participation_breakdown' => $this->getParticipationBreakdown()
        ];

        return $this->response->setJSON($data);
    }

    private function getEventsByMonth()
    {
        $data = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = date('Y-m', strtotime("-$i months"));
            $monthName = date('M Y', strtotime($month . '-01'));
            
            $count = $this->eventModel
                         ->where("TO_CHAR(event_date, 'YYYY-MM')", $month)
                         ->countAllResults();
            
            $data[] = [
                'month' => $monthName,
                'count' => $count
            ];
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
        return $this->pembayaranModel
                   ->select('e.title, SUM(p.jumlah) as total_revenue, COUNT(*) as registrations, p.participation_type')
                   ->join('events e', 'e.id = p.event_id')
                   ->where('p.verified_at IS NOT NULL')
                   ->groupBy('e.id, e.title, p.participation_type')
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
                   ->select('u.role, p.participation_type, COUNT(*) as count, SUM(p.jumlah) as revenue')
                   ->join('users u', 'u.id_user = p.id_user')
                   ->where('p.verified_at IS NOT NULL')
                   ->groupBy('u.role, p.participation_type')
                   ->findAll();
    }
}