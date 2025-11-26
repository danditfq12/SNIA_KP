<?php

namespace App\Controllers\Role\Admin;

use App\Controllers\BaseController;
use App\Models\FasilitasBenefitModel;
use App\Models\EventModel;

class Fasilitas extends BaseController
{
    protected FasilitasBenefitModel $fasilitasModel;
    protected EventModel $eventModel;
    protected $db;

    public function __construct()
    {
        $this->fasilitasModel = new FasilitasBenefitModel();
        $this->eventModel = new EventModel();
        $this->db = \Config\Database::connect();
    }

    /**
     * INDEX - List all facilities
     */
    public function index()
    {
        $fasilitas = $this->fasilitasModel->getFasilitasWithEvent();
        
        // Decode JSON fasilitas
        foreach ($fasilitas as &$item) {
            if (!empty($item['fasilitas']) && is_string($item['fasilitas'])) {
                $item['fasilitas'] = json_decode($item['fasilitas'], true) ?: [];
            }
        }
        unset($item);

        // Get active events for dropdown
        $events = $this->eventModel->where('is_active', true)
                                   ->orderBy('event_date', 'DESC')
                                   ->findAll();

        // Get statistics
        $stats = $this->fasilitasModel->getStatistics();

        return view('role/admin/fasilitas/index', [
            'title'     => 'Kelola Fasilitas & Benefit',
            'fasilitas' => $fasilitas,
            'events'    => $events,
            'stats'     => $stats,
        ]);
    }

    /**
     * GET - Get facilities by event (AJAX)
     */
    public function getFasilitasByEvent($eventId)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->back();
        }

        $facilities = $this->fasilitasModel->getFasilitasByEvent($eventId);
        
        // Decode JSON
        foreach ($facilities as &$item) {
            if (!empty($item['fasilitas']) && is_string($item['fasilitas'])) {
                $item['fasilitas'] = json_decode($item['fasilitas'], true) ?: [];
            }
        }
        unset($item);

        return $this->response->setJSON([
            'success'    => true,
            'facilities' => $facilities,
        ]);
    }

    /**
     * STORE - Save facilities for an event
     */
    public function store()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->back();
        }

        log_message('info', '=== SAVE FASILITAS REQUEST ===');
        log_message('info', 'POST data: ' . json_encode($this->request->getPost()));

        $rules = [
            'event_id'         => 'required|integer',
            'participant_type' => 'required|in_list[presenter_online,presenter_offline,audience_online,audience_offline]',
            'fasilitas'        => 'required',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $this->validator->getErrors(),
            ]);
        }

        $eventId = (int)$this->request->getPost('event_id');
        $participantType = $this->request->getPost('participant_type');
        $fasilitasInput = $this->request->getPost('fasilitas');

        // Validate event exists
        $event = $this->eventModel->find($eventId);
        if (!$event) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Event tidak ditemukan',
            ]);
        }

        // Parse fasilitas (bisa array atau JSON string)
        if (is_string($fasilitasInput)) {
            $fasilitasArray = json_decode($fasilitasInput, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                // Jika bukan JSON, split by newline/comma
                $fasilitasArray = array_filter(array_map('trim', explode("\n", $fasilitasInput)));
            }
        } else {
            $fasilitasArray = $fasilitasInput;
        }

        if (empty($fasilitasArray) || !is_array($fasilitasArray)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Fasilitas tidak boleh kosong',
            ]);
        }

        try {
            $saved = $this->fasilitasModel->saveFasilitas($eventId, $participantType, $fasilitasArray);

            if ($saved) {
                $this->logActivity("Simpan fasilitas: {$event['title']} - {$participantType}");

                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Fasilitas berhasil disimpan!',
                ]);
            }

            return $this->response->setJSON([
                'success' => false,
                'message' => 'Gagal menyimpan fasilitas',
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Save fasilitas failed: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * UPDATE - Edit existing facilities
     */
    public function update($id)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->back();
        }

        $item = $this->fasilitasModel->find($id);
        if (!$item) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Data tidak ditemukan',
            ]);
        }

        $rules = [
            'fasilitas' => 'required',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $this->validator->getErrors(),
            ]);
        }

        $fasilitasInput = $this->request->getPost('fasilitas');

        // Parse fasilitas
        if (is_string($fasilitasInput)) {
            $fasilitasArray = json_decode($fasilitasInput, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                $fasilitasArray = array_filter(array_map('trim', explode("\n", $fasilitasInput)));
            }
        } else {
            $fasilitasArray = $fasilitasInput;
        }

        if (empty($fasilitasArray) || !is_array($fasilitasArray)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Fasilitas tidak boleh kosong',
            ]);
        }

        try {
            $data = [
                'fasilitas'  => json_encode($fasilitasArray, JSON_UNESCAPED_UNICODE),
                'updated_at' => date('Y-m-d H:i:s'),
            ];

            if ($this->fasilitasModel->update($id, $data)) {
                $this->logActivity("Update fasilitas ID: {$id}");

                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Fasilitas berhasil diupdate!',
                ]);
            }

            return $this->response->setJSON([
                'success' => false,
                'message' => 'Gagal update fasilitas',
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Update fasilitas failed: ' . $e->getMessage());
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * DELETE
     */
    public function delete($id)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->back();
        }

        $item = $this->fasilitasModel->find($id);
        if (!$item) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Data tidak ditemukan',
            ]);
        }

        try {
            if ($this->fasilitasModel->delete($id)) {
                $this->logActivity("Hapus fasilitas ID: {$id}");

                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Fasilitas berhasil dihapus!',
                ]);
            }

            return $this->response->setJSON([
                'success' => false,
                'message' => 'Gagal menghapus fasilitas',
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * TOGGLE STATUS
     */
    public function toggleStatus($id)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->back();
        }

        try {
            if ($this->fasilitasModel->toggleStatus($id)) {
                $this->logActivity("Toggle status fasilitas ID: {$id}");

                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Status berhasil diubah!',
                ]);
            }

            return $this->response->setJSON([
                'success' => false,
                'message' => 'Gagal mengubah status',
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * COPY TO EVENT - Copy facilities from one event to another
     */
    public function copyToEvent()
    {
        if (!$this->request->isAJAX()) {
            return redirect()->back();
        }

        $rules = [
            'source_event_id' => 'required|integer',
            'target_event_id' => 'required|integer',
        ];

        if (!$this->validate($rules)) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Validasi gagal',
                'errors'  => $this->validator->getErrors(),
            ]);
        }

        $sourceEventId = (int)$this->request->getPost('source_event_id');
        $targetEventId = (int)$this->request->getPost('target_event_id');

        if ($sourceEventId === $targetEventId) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Event sumber dan tujuan tidak boleh sama',
            ]);
        }

        try {
            $copied = $this->fasilitasModel->copyToEvent($sourceEventId, $targetEventId);

            if ($copied) {
                $this->logActivity("Copy fasilitas dari event {$sourceEventId} ke {$targetEventId}");

                return $this->response->setJSON([
                    'success' => true,
                    'message' => 'Fasilitas berhasil disalin!',
                ]);
            }

            return $this->response->setJSON([
                'success' => false,
                'message' => 'Tidak ada fasilitas untuk disalin',
            ]);
        } catch (\Exception $e) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Error: ' . $e->getMessage(),
            ]);
        }
    }

    /**
     * GET DETAIL - Get specific facility detail
     */
    public function getDetail($id)
    {
        if (!$this->request->isAJAX()) {
            return redirect()->back();
        }

        $item = $this->fasilitasModel->select('fasilitas_benefit.*, events.title as event_title')
                                     ->join('events', 'events.id = fasilitas_benefit.event_id', 'left')
                                     ->find($id);

        if (!$item) {
            return $this->response->setJSON([
                'success' => false,
                'message' => 'Data tidak ditemukan',
            ]);
        }

        // Decode fasilitas
        if (!empty($item['fasilitas']) && is_string($item['fasilitas'])) {
            $item['fasilitas'] = json_decode($item['fasilitas'], true) ?: [];
        }

        return $this->response->setJSON([
            'success' => true,
            'data'    => $item,
        ]);
    }

    /**
     * Log activity helper
     */
    private function logActivity(string $activity): void
    {
        try {
            $userId = session()->get('id_user');
            if ($userId) {
                $this->db->table('log_aktivitas')->insert([
                    'id_user'   => $userId,
                    'aktivitas' => $activity,
                    'waktu'     => date('Y-m-d H:i:s'),
                ]);
            }
        } catch (\Exception $e) {
            log_message('warning', 'Log activity failed: ' . $e->getMessage());
        }
    }
}