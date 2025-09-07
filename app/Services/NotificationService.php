<?php
namespace App\Services;

use App\Models\NotificationModel;
use App\Models\UserModel;
use Config\Database;
use CodeIgniter\I18n\Time;

class NotificationService
{
    protected NotificationModel $model;

    public function __construct()
    {
        $this->model = new NotificationModel();
    }

    /** Alias serbaguna – cocok dengan pemanggilanmu di controller:
     *  notify($userId, $category, $title, $message, $link, $type='info')
     */
    public function notify(
        int $userId,
        ?string $category,
        string $title,
        ?string $message = null,
        ?string $link = null,
        string $type = 'info'
    ): int {
        $data = [
            'id_user'   => $userId,
            'category'  => $category,
            'title'     => $title,
            'message'   => $message,
            'link'      => $this->normalizeLink($link),
            'type'      => $type,
            'is_read'   => 0,
            'created_at'=> date('Y-m-d H:i:s'),
        ];
        $this->model->insert($data, true);
        return (int) $this->model->getInsertID();
    }

    /** Versi singkat tanpa category */
    public function create(
        int $userId,
        string $title,
        ?string $message = null,
        ?string $link = null,
        string $type = 'info'
    ): int {
        return $this->notify($userId, null, $title, $message, $link, $type);
    }

    /** Broadcast ke semua user dengan role tertentu */
    public function broadcastToRole(
        string $role,
        string $title,
        ?string $message = null,
        ?string $link = null,
        string $type = 'info'
    ): int {
        $users = (new UserModel())
            ->select('id_user')
            ->where('role', $role)
            ->where('status', 'aktif')
            ->findAll();

        $count = 0;
        foreach ($users as $u) {
            $this->create((int) $u['id_user'], $title, $message, $link, $type);
            $count++;
        }
        return $count;
    }

    /** Ambil daftar notif untuk user yang login → dipakai BaseController */
    public function getForCurrentUser(int $limit = 8): array
    {
        $uid = (int) (session()->get('id_user') ?? 0);
        if ($uid <= 0) return [];

        $rows = $this->model
            ->where('id_user', $uid)
            ->orderBy('created_at', 'DESC')
            ->findAll($limit);

        // mapping supaya cocok dengan header-mu: 'title','message','link','type','read','time'
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'id'     => (int)($r['id'] ?? 0),
                'title'  => (string)($r['title'] ?? ''),
                'message'=> (string)($r['message'] ?? ''),
                'link'   => (string)($r['link'] ?? '#'),
                'type'   => (string)($r['type'] ?? 'info'),
                'read'   => (bool)  ($r['is_read'] ?? false),
                'time'   => $this->humanize($r['created_at'] ?? null),
            ];
        }
        return $out;
    }

    public function markRead(int $id, int $userId): bool
    {
        return (bool) $this->model
            ->where('id', $id)
            ->where('id_user', $userId)
            ->set('is_read', 1)
            ->update();
    }

    public function markAllReadForUser(int $userId): void
    {
        $this->model->where('id_user', $userId)->set('is_read', 1)->update();
    }

    private function normalizeLink(?string $link): string
    {
        if (!$link || $link === '#') return '#';
        if (preg_match('~^https?://~i', $link)) return $link;
        return site_url(ltrim($link, '/'));
    }

    private function humanize(?string $ts): string
    {
        if (!$ts) return '';
        try {
            return Time::parse($ts)->humanize();
        } catch (\Throwable $e) {
            return date('d M Y H:i', strtotime($ts));
        }
    }
}
