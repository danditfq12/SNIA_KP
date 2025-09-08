<?php
// app/Models/NotificationModel.php
namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Database\BaseBuilder;

class NotificationModel extends Model
{
    protected $table         = 'notifikasi';
    protected $primaryKey    = 'id_notif';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'id_user','role','title','message','link',
        'type','meta','read','read_at','created_at','updated_at'
    ];
    protected $useTimestamps = false;

    /** Deteksi driver */
    private function isPostgres(): bool
    {
        return stripos($this->db->DBDriver ?? '', 'postgre') !== false;
    }

    /** Tambahkan kondisi unread ke builder (portable) */
    private function applyUnreadWhere(BaseBuilder $b): BaseBuilder
    {
        if ($this->isPostgres()) {
            // boolean kolom → gunakan FALSE
            $b->groupStart()
                  ->where('read =', false)
                  ->orWhere('read IS NULL', null, false)
              ->groupEnd();
        } else {
            // MySQL TINYINT(1) → gunakan 0
            $b->groupStart()
                  ->where('read', 0)
                  ->orWhere('read IS NULL', null, false)
              ->groupEnd();
        }
        return $b;
    }

    public function forUser(int $userId, int $limit = 10): array
    {
        return $this->where('id_user', $userId)
                    ->orderBy('created_at', 'DESC')
                    ->findAll($limit);
    }

    /** Hitung unread (portable) */
    public function countUnread(int $userId): int
    {
        $b = $this->builder()->where('id_user', $userId);
        $this->applyUnreadWhere($b);
        return (int) $b->countAllResults();
    }

    /** Tandai semua unread → read (portable) */
    public function markAllRead(int $userId): bool
    {
        $b = $this->builder()->where('id_user', $userId);
        $this->applyUnreadWhere($b);

        $b->set([
              'read'       => 1, // aman untuk Postgres (akan dicast ke TRUE) & MySQL
              'read_at'    => date('Y-m-d H:i:s'),
              'updated_at' => date('Y-m-d H:i:s'),
        ])->update();

        return $this->db->affectedRows() >= 0;
    }

    /** Tandai satu notif read (portable) */
    public function markRead(int $idNotif, int $userId): bool
    {
        $this->builder()
            ->where('id_notif', $idNotif)
            ->where('id_user', $userId)
            ->set([
                'read'       => 1, // cast OK di Postgres
                'read_at'    => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ])->update();

        return $this->db->affectedRows() >= 0;
    }

    public function add(
        int $userId, string $title, ?string $message = null, ?string $link = null,
        string $type = 'info', ?string $role = null, $meta = null
    ): int {
        $this->insert([
            'id_user'    => $userId,
            'role'       => $role,
            'title'      => $title,
            'message'    => $message,
            'link'       => $link,
            'type'       => $type,
            'meta'       => is_array($meta) ? json_encode($meta) : $meta,
            'read'       => null,
            'read_at'    => null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return (int) $this->getInsertID();
    }
}
