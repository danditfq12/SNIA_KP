<?php

namespace App\Models;

use CodeIgniter\Model;

class NotificationModel extends Model
{
    protected $table          = 'notifikasi';
    protected $primaryKey     = 'id_notif';
    protected $returnType     = 'array';
    protected $allowedFields  = [
        'id_user', 'role', 'title', 'message', 'link',
        'type', 'meta', 'read', 'read_at',
        'created_at', 'updated_at'
    ];
    protected $useTimestamps  = false; // kita set created_at/updated_at manual di method

    /**
     * Ambil notifikasi terbaru untuk user
     */
    public function forUser(int $userId, int $limit = 10): array
    {
        return $this->where('id_user', $userId)
                    ->orderBy('created_at', 'DESC')
                    ->findAll($limit);
    }

    /**
     * Hitung unread (read = false atau read IS NULL)
     * NOTE: gunakan IS NULL, bukan "= NULL"
     */
    public function countUnread(int $userId): int
    {
        return $this->where('id_user', $userId)
                    ->groupStart()
                        ->where('read', false)
                        ->orWhere('read IS NULL', null, false) // <<< penting untuk Postgres
                    ->groupEnd()
                    ->countAllResults();
    }

    /**
     * Tandai semua notif user sebagai terbaca (hanya yang belum read)
     */
    public function markAllRead(int $userId): bool
    {
        // Postgres-safe WHERE untuk boolean/null
        return (bool) $this->builder()
            ->where('id_user', $userId)
            ->where('(read IS NULL OR read = FALSE)', null, false)
            ->set([
                'read'      => true,
                'read_at'   => date('Y-m-d H:i:s'),
                'updated_at'=> date('Y-m-d H:i:s'),
            ])
            ->update();
    }

    /**
     * Tandai satu notif terbaca
     */
    public function markRead(int $idNotif, int $userId): bool
    {
        return (bool) $this->builder()
            ->where('id_notif', $idNotif)
            ->where('id_user', $userId)
            ->set([
                'read'      => true,
                'read_at'   => date('Y-m-d H:i:s'),
                'updated_at'=> date('Y-m-d H:i:s'),
            ])
            ->update();
    }

    /**
     * Helper untuk membuat notifikasi baru
     */
    public function add(
        int $userId,
        string $title,
        ?string $message = null,
        ?string $link = null,
        string $type = 'info',
        ?string $role = null,
        $meta = null
    ): int {
        $data = [
            'id_user'    => $userId,
            'role'       => $role,
            'title'      => $title,
            'message'    => $message,
            'link'       => $link,
            'type'       => $type,
            'meta'       => is_array($meta) ? json_encode($meta) : $meta,
            'read'       => null, // belum dibaca
            'read_at'    => null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $this->insert($data);
        return (int) $this->getInsertID();
    }
}
