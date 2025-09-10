<?php
namespace App\Services;

use App\Models\NotificationModel;
use CodeIgniter\I18n\Time;
use Config\Database;

class NotificationService
{
    protected NotificationModel $model;
    protected \CodeIgniter\Database\BaseConnection $db;
    protected ?string $table;

    public function __construct()
    {
        $this->model = new NotificationModel();
        $this->db    = Database::connect();
        $this->table = method_exists($this->model, 'getTable') ? $this->model->getTable() : 'notifikasi';
    }

    // notify($userId, $category, $title, $message=null, $link=null, $type='info', $meta=null)
    public function notify(
        int $userId,
        ?string $category,
        string $title,
        ?string $message = null,
        ?string $link = null,
        string $type = 'info',
        ?array $meta = null
    ): int {
        $data = [
            'id_user'    => $userId,
            'role'       => null,
            'type'       => $category ?: $type,
            'title'      => $title,
            'message'    => $message,
            'link'       => $this->normalizeLink($link),
            'meta_json'  => $meta ? json_encode($this->normalizeMeta($meta), JSON_UNESCAPED_UNICODE) : null,
            'read'       => null,
            'read_at'    => null,
            'created_at' => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        $this->model->insert($data, true);
        return (int) $this->model->getInsertID();
    }

    public function create(
        int $userId,
        string $title,
        ?string $message = null,
        ?string $link = null,
        string $type = 'info',
        ?array $meta = null
    ): int {
        return $this->notify($userId, null, $title, $message, $link, $type, $meta);
    }

    public function broadcastToRole(
        string $role,
        string $title,
        ?string $message = null,
        ?string $link = null,
        string $type = 'info',
        ?array $meta = null
    ): int {
        $users = (new \App\Models\UserModel())
            ->select('id_user')
            ->where('role', $role)
            ->where('status', 'aktif')
            ->findAll();

        $count = 0;
        foreach ($users as $u) {
            $this->create((int)$u['id_user'], $title, $message, $link, $type, $meta);
            $count++;
        }
        return $count;
    }

    public function getForCurrentUser(int $limit = 8): array
    {
        $uid = (int) (session('id_user') ?? 0);
        if ($uid <= 0) return [];

        $rows = $this->model
            ->where('id_user', $uid)
            ->orderBy('created_at', 'DESC')
            ->findAll($limit);

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'id'      => (int)($r['id_notif'] ?? 0),
                'title'   => (string)($r['title'] ?? ''),
                'message' => (string)($r['message'] ?? ''),
                'link'    => (string)$this->normalizeLink($r['link'] ?? ''),
                'type'    => (string)($r['type'] ?? 'info'),
                'read'    => (bool)  ($r['read'] ?? false),
                'time'    => $this->humanize($r['created_at'] ?? null),
            ];
        }
        return $out;
    }

    public function markRead(int $id, int $userId): bool
    {
        return (bool) $this->model
            ->where('id_notif', $id)->where('id_user', $userId)
            ->set(['read' => 1, 'read_at'=>date('Y-m-d H:i:s'), 'updated_at'=>date('Y-m-d H:i:s')])
            ->update();
    }

    public function markAllReadForUser(int $userId): void
    {
        $this->model->where('id_user', $userId)
            ->set(['read'=>1, 'read_at'=>date('Y-m-d H:i:s'), 'updated_at'=>date('Y-m-d H:i:s')])
            ->update();
    }

    private function normalizeMeta(array $meta): array
    {
        if (isset($meta['amount'])) $meta['amount'] = (float)$meta['amount'];
        return $meta;
    }

    /** Link → path relatif bila host sama; perbaiki "http:/" jadi "http://" */
    private function normalizeLink(?string $href): string
    {
        $href = trim((string)$href);
        if ($href === '' || $href === '#') return '';

        $href = preg_replace('~^(https?:)/([^/])~i', '$1//$2', $href);

        if (preg_match('~^(https?):\/\/([^\/]+)(\/.*)?$~i', $href, $m)) {
            $host     = $m[2];
            $path     = $m[3] ?? '/';
            $currHost = $_SERVER['HTTP_HOST'] ?? '';
            if (strcasecmp($host, $currHost) === 0) return $path;
            return $m[1] . '://' . $host . $path;
        }

        if (strpos($href, '//') === 0) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https:' : 'http:';
            return $this->normalizeLink($scheme . $href);
        }

        return '/' . ltrim($href, '/');
    }

    private function humanize(?string $ts): string
    {
        if (!$ts) return '';
        try { return Time::parse($ts)->humanize(); }
        catch (\Throwable $e) { return date('d M Y H:i', strtotime($ts)); }
    }
}
