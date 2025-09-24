<?php
namespace App\Models;

use CodeIgniter\Model;

class EventRegistrationModel extends Model
{
    protected $table         = 'event_registrations';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    /**
     * allowedFields harus memuat kolom yang akan diisi dari form kontributor,
     * kalau tidak CI4 akan mengabaikannya saat insert/update.
     */
    protected $allowedFields = [
        'id_event', 'id_user', 'mode_kehadiran', 'status', 'qr_token',
        'created_at', 'updated_at',

        // kemungkinan nama kolom flag "kontributor selesai"
        'contributor_done', 'kontributor_done', 'profile_completed', 'is_profile_completed',

        // ✅ dari form kontributor
        'afiliasi', 'phone', 'coauthors_json',
    ];

    /* =========================================================
     * Query helpers
     * ======================================================= */

    // Ambil registrasi user utk event tertentu (ambil terbaru kalau ada duplikat)
    public function findUserReg(int $idEvent, int $idUser): ?array
    {
        return $this->where(['id_event' => $idEvent, 'id_user' => $idUser])
                    ->orderBy('id', 'DESC')
                    ->first();
    }

    // Ambil registrasi + info event (untuk tampilan)
    public function getByIdWithEvent(int $id): ?array
    {
        return $this->select(
                    'event_registrations.*,
                     e.title AS event_title,
                     e.zoom_link, e.location, e.event_date, e.event_time, e.format'
                )
                ->join('events e', 'e.id = event_registrations.id_event', 'left')
                ->where('event_registrations.id', $id)
                ->first();
    }

    // Daftar registrasi milik user
    public function listByUser(int $idUser): array
    {
        return $this->select('event_registrations.*, e.title AS event_title, e.event_date, e.event_time')
                    ->join('events e', 'e.id = event_registrations.id_event', 'left')
                    ->where('event_registrations.id_user', $idUser)
                    ->orderBy('event_registrations.id','DESC')
                    ->findAll();
    }

    /* =========================================================
     * Creation / status helpers
     * ======================================================= */

    public function createRegistration(int $idEvent, int $idUser, string $mode): int
    {
        if (!in_array($mode, ['online','offline'], true)) {
            throw new \InvalidArgumentException('Mode kehadiran tidak valid.');
        }

        if ($old = $this->findUserReg($idEvent, $idUser)) {
            return (int) $old['id']; // sudah ada → pakai id lama
        }

        $this->insert([
            'id_event'       => $idEvent,
            'id_user'        => $idUser,
            'mode_kehadiran' => $mode,
            'status'         => 'menunggu_pembayaran',
            'qr_token'       => $mode === 'offline' ? bin2hex(random_bytes(16)) : null,
        ]);

        return (int) $this->getInsertID();
    }

    /** Registrasi khusus PRESENTER (offline) */
    public function createPresenterRegistration(int $idEvent, int $idUser): int
    {
        if ($old = $this->findUserReg($idEvent, $idUser)) {
            return (int) $old['id'];
        }

        $this->insert([
            'id_event'       => $idEvent,
            'id_user'        => $idUser,
            'mode_kehadiran' => 'offline',
            'status'         => 'menunggu_pembayaran',
            'qr_token'       => bin2hex(random_bytes(16)),
        ]);

        return (int) $this->getInsertID();
    }

    public function markPaid(int $id): bool
    {
        return $this->update($id, ['status' => 'lunas']);
    }

    /** Ubah status registrasi → menunggu pembayaran (dipanggil saat abstrak/full paper diterima) */
    public function markAwaitingPayment(int $id): bool
    {
        return $this->update($id, ['status' => 'menunggu_pembayaran']);
    }

    /* =========================================================
     * Kontributor: flag selesai
     * ======================================================= */

    /**
     * Tandai bahwa data kontributor SUDAH selesai diisi.
     * - Cek beberapa kemungkinan nama kolom; update sebagai boolean (TRUE/FALSE).
     */
    public function markContributorDone(int $eventId, int $userId, bool $done = true): bool
    {
        $row = $this->findUserReg($eventId, $userId);
        if (!$row) return false;

        $candidates = ['contributor_done','kontributor_done','profile_completed','is_profile_completed'];

        $data = [];
        foreach ($candidates as $col) {
            if (array_key_exists($col, $row)) {
                $data[$col] = $done; // ✅ boolean untuk Postgres BOOLEAN
            }
        }

        // kalau tidak ada kolom flag sama sekali, anggap sukses (tidak memblokir flow)
        if (!$data) return true;

        return (bool) $this->update((int) $row['id'], $data);
    }
}