<?php

namespace App\Controllers\Role\Presenter;

use App\Controllers\BaseController;
use App\Models\EventModel;
use App\Models\EventRegistrationModel;
use App\Models\UserModel;
use CodeIgniter\Database\RawSql;

class Kontributor extends BaseController
{
    protected EventModel $eventModel;
    protected EventRegistrationModel $regModel;
    protected UserModel $userModel;

    public function __construct()
    {
        $this->eventModel = new EventModel();
        $this->regModel   = new EventRegistrationModel();
        $this->userModel  = new UserModel();
        helper(['form', 'text']);
    }

    public function start($eventId)
    {
        $eventId = (int)$eventId;
        $userId  = (int) session()->get('id_user');

        $event = $this->eventModel->find($eventId);
        if (!$event) {
            return redirect()->to('/presenter/events')->with('error', 'Event tidak ditemukan.');
        }

        $reg = $this->regModel->findUserReg($eventId, $userId);
        if (!$reg) {
            return redirect()->to('/presenter/events/detail/'.$eventId)->with('error', 'Anda belum mendaftar pada event ini.');
        }

        $user = $this->userModel->find($userId);

        // ---- Prefill identitas presenter utama ----
        $presenterEmail = trim((string)($user['email'] ?? ''));
        if ($presenterEmail === '' && !empty($reg['email'])) {
            $presenterEmail = trim((string)$reg['email']);
        }

        $presenterName =
            trim((string)($user['nama_lengkap'] ?? '')) ?:
            trim((string)($user['username']     ?? '')) ?:
            trim((string)($reg['presenter_name'] ?? '')) ?:
            trim((string)($reg['nama']          ?? ''));

        if ($presenterName === '' && $presenterEmail !== '') {
            $local = explode('@', $presenterEmail)[0] ?? '';
            $local = str_replace(['.', '_', '-'], ' ', $local);
            $presenterName = ucwords(preg_replace('/\s+/', ' ', trim($local)));
        }

        // ---- Prefill dari users bila di registrasi kosong ----
        $userInstitusi = trim((string)($user['institusi'] ?? ''));
        $userPhone     = trim((string)($user['no_hp']     ?? ''));

        $afiliasi = trim((string)($reg['afiliasi'] ?? ''));
        $phone    = trim((string)($reg['phone']    ?? ''));

        if ($afiliasi === '' && $userInstitusi !== '') {
            $afiliasi = $userInstitusi; // ambil dari users.institusi
        }
        if ($phone === '' && $userPhone !== '') {
            $phone = $userPhone; // ambil dari users.no_hp
        }

        // ---- Co-authors ----
        $coauthors = [];
        if (!empty($reg['coauthors_json'])) {
            $decoded = json_decode((string)$reg['coauthors_json'], true);
            if (is_array($decoded)) $coauthors = $decoded;
        }

        // ---- Flag apakah sudah pernah update ----
        $isUpdate = ($afiliasi !== '' || $phone !== '' || !empty($coauthors));
        foreach (['contributor_done','kontributor_done','profile_completed','is_profile_completed'] as $flag) {
            if (!empty($reg[$flag])) { $isUpdate = true; break; }
        }

        return view('role/presenter/kontributor/start', [
            'title'           => 'Data Kontributor',
            'event'           => $event,
            'eventId'         => $eventId,
            'reg'             => $reg,
            'presenter_name'  => $presenterName,
            'presenter_email' => $presenterEmail,
            'afiliasi'        => $afiliasi,
            'phone'           => $phone,
            'coauthors'       => $coauthors,
            'is_update'       => $isUpdate,
        ]);
    }

    public function save($eventId)
    {
        $eventId = (int)$eventId;
        $userId  = (int) session()->get('id_user');

        $reg = $this->regModel->findUserReg($eventId, $userId);
        if (!$reg) {
            return redirect()->to('/presenter/events/detail/'.$eventId)->with('error', 'Registrasi tidak ditemukan.');
        }

        $afiliasi = trim((string)$this->request->getPost('afiliasi')); // di-commit juga ke users.institusi
        $phone    = trim((string)$this->request->getPost('phone'));    // di-commit juga ke users.no_hp

        $coNames  = (array)$this->request->getPost('co_name');
        $coEmails = (array)$this->request->getPost('co_email');
        $coAffs   = (array)$this->request->getPost('co_afiliasi');

        $coauthors = [];
        $rows = max(count($coNames), count($coEmails), count($coAffs));
        for ($i = 0; $i < $rows; $i++) {
            $n = trim((string)($coNames[$i]  ?? ''));
            $e = trim((string)($coEmails[$i] ?? ''));
            $a = trim((string)($coAffs[$i]   ?? ''));
            if ($n === '' && $e === '' && $a === '') continue;
            $coauthors[] = ['nama' => $n, 'email' => $e, 'afiliasi' => $a];
        }

        if ($afiliasi === '') {
            return redirect()->back()->withInput()->with('error', 'Afiliasi/Institusi wajib diisi.');
        }

        // ---- Simpan ke tabel registrasi ----
        $payload = [
            'afiliasi' => $afiliasi,
            'phone'    => $phone,
        ];

        if ($this->columnExists($this->regModel->getTable(), 'coauthors_json')) {
            $payload['coauthors_json'] = json_encode($coauthors, JSON_UNESCAPED_UNICODE);
        }

        // set flag kontributor selesai → boolean TRUE (PG aman)
        $db   = \Config\Database::connect();
        $isPg = strtolower($db->DBDriver) === 'postgre';
        foreach (['contributor_done','kontributor_done','profile_completed','is_profile_completed'] as $flag) {
            if ($this->columnExists($this->regModel->getTable(), $flag)) {
                $payload[$flag] = $isPg ? new RawSql('TRUE') : true;
                break;
            }
        }

        $this->regModel->update((int)$reg['id'], $payload);

        // ---- Sinkron ke tabel users ----
        // Hanya update jika kolomnya ada dan ada nilai yang dikirim
        $uPayload = [];
        if ($afiliasi !== '' && $this->columnExists($this->userModel->getTable(), 'institusi')) {
            $uPayload['institusi'] = $afiliasi;
        }
        if ($phone !== '' && $this->columnExists($this->userModel->getTable(), 'no_hp')) {
            $uPayload['no_hp'] = $phone;
        }
        if (!empty($uPayload)) {
            try {
                $this->userModel->update($userId, $uPayload);
            } catch (\Throwable $e) {
                log_message('warning', '[Kontributor] Gagal sync users: ' . $e->getMessage());
            }
        }

        $go = (string)($this->request->getPost('goto') ?? 'stay');
        if ($go === 'to_abstract') {
            return redirect()->to('/presenter/abstrak/create/'.$eventId)
                ->with('success', 'Data kontributor diperbarui. Silakan unggah abstrak.');
        }

        return redirect()->to('/presenter/kontributor/start/'.$eventId)
            ->with('success', 'Data kontributor berhasil disimpan.');
    }

    private function columnExists(string $table, string $column): bool
    {
        try {
            $db = \Config\Database::connect();
            foreach ($db->getFieldData($table) as $f) {
                if (strcasecmp($f->name, $column) === 0) return true;
            }
        } catch (\Throwable) {}
        return false;
    }
}
