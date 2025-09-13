<?php
namespace App\Models;

use CodeIgniter\Model;

class EventRegistrationModel extends Model
{
    protected $table         = 'event_registrations';
    protected $primaryKey    = 'id';
    protected $returnType    = 'array';
    protected $useTimestamps = true;

    protected $allowedFields = [
        'id_event', 'id_user', 'mode_kehadiran', 'status', 'qr_token',
        'created_at', 'updated_at'
    ];

    // Ambil registrasi user utk event tertentu (ambil yg terbaru kalau ada duplikat)
    public function findUserReg(int $idEvent, int $idUser): ?array
    {
        return $this->where(['id_event' => $idEvent, 'id_user' => $idUser])
                    ->orderBy('id', 'DESC')
                    ->first();
    }

    // Ambil registrasi + info event (lengkap untuk tampilan)
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

    public function listByUser(int $idUser): array
    {
        return $this->select('event_registrations.*, e.title AS event_title, e.event_date, e.event_time')
                    ->join('events e', 'e.id = event_registrations.id_event', 'left')
                    ->where('event_registrations.id_user', $idUser)
                    ->orderBy('event_registrations.id','DESC')
                    ->findAll();
    }

    public function createRegistration(int $idEvent, int $idUser, string $mode): int
    {
        if (!in_array($mode, ['online','offline'], true)) {
            throw new \InvalidArgumentException('Mode kehadiran tidak valid.');
        }
        if ($old = $this->findUserReg($idEvent,$idUser)) {
            return (int)$old['id']; // sudah ada → pakai id lama
        }
        $this->insert([
            'id_event'       => $idEvent,
            'id_user'        => $idUser,
            'mode_kehadiran' => $mode,
            'status'         => 'menunggu_pembayaran',
            'qr_token'       => $mode === 'offline' ? bin2hex(random_bytes(16)) : null,
        ]);
        return (int)$this->getInsertID();
    }

    public function markPaid(int $id): bool
    {
        return $this->update($id, ['status' => 'lunas']);
    }
    // Tambahkan di akhir class EventRegistrationModel

    /** Buat registrasi khusus PRESENTER:
     *  - mode_kehadiran: offline
     *  - status: menunggu_abstrak
     */
    // app/Models/EventRegistrationModel.php

public function createPresenterRegistration(int $idEvent, int $idUser): int
{
    if ($old = $this->findUserReg($idEvent, $idUser)) {
        return (int)$old['id'];
    }
    $this->insert([
        'id_event'       => $idEvent,
        'id_user'        => $idUser,
        'mode_kehadiran' => 'offline',
        // ✅ alternatif 4: pakai status yang SUDAH diizinkan oleh constraint DB
        'status'         => 'menunggu_pembayaran',
        'qr_token'       => bin2hex(random_bytes(16)),
    ]);
    return (int)$this->getInsertID();
}


    /** Ubah status registrasi → menunggu pembayaran (dipanggil saat abstrak diterima) */
    public function markAwaitingPayment(int $id): bool
    {
        return $this->update($id, ['status' => 'menunggu_pembayaran']);
    }
}
