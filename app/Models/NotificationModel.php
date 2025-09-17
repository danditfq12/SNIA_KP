<?php
namespace App\Models;

use CodeIgniter\Model;
use CodeIgniter\Database\BaseBuilder;

class NotificationModel extends Model
{
    protected $table         = 'notifikasi';
    protected $primaryKey    = 'id_notif';
    protected $returnType    = 'array';
    protected $allowedFields = [
        'id_user','role','type','title','message','link','meta_json',
        'read','read_at','created_at','updated_at'
    ];
    protected $useTimestamps = false;

    private function isPostgres(): bool
    {
        return stripos($this->db->DBDriver ?? '', 'postgre') !== false;
    }

    private function applyUnreadWhere(BaseBuilder $b): BaseBuilder
    {
        if ($this->isPostgres()) {
            $b->groupStart()
              ->where('"read" =', false)
              ->orWhere('"read" IS NULL', null, false)
              ->groupEnd();
        } else {
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

    public function countUnread(int $userId): int
    {
        $b = $this->builder()->where('id_user', $userId);
        $this->applyUnreadWhere($b);
        return (int) $b->countAllResults();
    }

    public function markAllRead(int $userId): bool
    {
        $b = $this->builder()->where('id_user', $userId);
        $this->applyUnreadWhere($b);
        $b->set([
            '"read"'     => true,
            'read_at'    => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ])->update();
        return $this->db->affectedRows() >= 0;
    }

    public function markRead(int $idNotif, int $userId): bool
    {
        $this->builder()
            ->where('id_notif', $idNotif)
            ->where('id_user', $userId)
            ->set([
                '"read"'     => true,
                'read_at'    => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ])->update();
        return $this->db->affectedRows() >= 0;
    }

    public function add(
        int $userId, 
        string $title, 
        ?string $message = null, 
        ?string $link = null,
        string $type = 'info', 
        ?string $role = null, 
        ?array $meta = null
    ): int {
        $data = [
            'id_user'    => $userId,
            'role'       => $role,
            'type'       => $type,
            'title'      => $title,
            'message'    => $message,
            'link'       => $link,
            'meta_json'  => $meta ? json_encode($meta, JSON_UNESCAPED_UNICODE) : null,
            'read'       => false, // Explicitly set to false instead of null
            'read_at'    => null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        try {
            $this->insert($data);
            return (int) $this->getInsertID();
        } catch (\Exception $e) {
            log_message('error', 'NotificationModel::add failed: ' . $e->getMessage());
            log_message('error', 'Data: ' . json_encode($data));
            return 0;
        }
    }

    /**
     * Override insert to ensure proper data handling
     */
    public function insert($data = null, bool $returnID = true)
    {
        if (is_array($data)) {
            // Ensure read column is never null
            if (!isset($data['read']) || $data['read'] === null) {
                $data['read'] = false;
            }
            
            // Ensure created_at is set
            if (!isset($data['created_at']) || $data['created_at'] === null) {
                $data['created_at'] = date('Y-m-d H:i:s');
            }
            
            // Ensure updated_at is set
            if (!isset($data['updated_at']) || $data['updated_at'] === null) {
                $data['updated_at'] = date('Y-m-d H:i:s');
            }
        }

        return parent::insert($data, $returnID);
    }

    /**
     * Get recent notifications for user with proper read status handling
     */
    public function getRecentForUser(int $userId, int $limit = 20): array
    {
        $notifications = $this->select('
            id_notif, title, message, link, type, 
            "read", read_at, created_at
        ')
        ->where('id_user', $userId)
        ->orderBy('created_at', 'DESC')
        ->limit($limit)
        ->findAll();

        // Ensure read status is properly cast
        foreach ($notifications as &$notif) {
            $notif['read'] = (bool) ($notif['read'] ?? false);
            $notif['is_unread'] = !$notif['read'];
        }

        return $notifications;
    }

    /**
     * Get notifications by type
     */
    public function getByType(int $userId, string $type, int $limit = 10): array
    {
        return $this->where('id_user', $userId)
                    ->where('type', $type)
                    ->orderBy('created_at', 'DESC')
                    ->limit($limit)
                    ->findAll();
    }

    /**
     * Delete old notifications (cleanup)
     */
    public function deleteOldNotifications(int $days = 90): int
    {
        $cutoffDate = date('Y-m-d H:i:s', strtotime("-{$days} days"));
        
        $deletedCount = $this->where('created_at <', $cutoffDate)
                            ->where('"read"', true)
                            ->delete();
        
        return $this->db->affectedRows();
    }

    /**
     * Get notification statistics for admin
     */
    public function getStats(): array
    {
        $stats = $this->db->query("
            SELECT 
                COUNT(*) as total,
                COUNT(CASE WHEN \"read\" = true THEN 1 END) as read_count,
                COUNT(CASE WHEN \"read\" = false OR \"read\" IS NULL THEN 1 END) as unread_count,
                COUNT(CASE WHEN type = 'payment' THEN 1 END) as payment_notifs,
                COUNT(CASE WHEN type = 'abstract' THEN 1 END) as abstract_notifs,
                COUNT(CASE WHEN type = 'system' THEN 1 END) as system_notifs
            FROM notifikasi
            WHERE created_at >= ?
        ", [date('Y-m-d H:i:s', strtotime('-30 days'))])->getRowArray();

        return $stats ?: [
            'total' => 0,
            'read_count' => 0,
            'unread_count' => 0,
            'payment_notifs' => 0,
            'abstract_notifs' => 0,
            'system_notifs' => 0
        ];
    }

    /**
     * Bulk mark as read
     */
    public function markMultipleAsRead(array $notificationIds, int $userId): bool
    {
        if (empty($notificationIds)) {
            return false;
        }

        $this->builder()
            ->whereIn('id_notif', $notificationIds)
            ->where('id_user', $userId)
            ->set([
                '"read"'     => true,
                'read_at'    => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s'),
            ])->update();

        return $this->db->affectedRows() > 0;
    }
}