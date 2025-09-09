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
        // coba ambil nama tabel dari model, fallback 'notifications' atau 'notification'
        $this->table = property_exists($this->model, 'table') ? $this->model->table : null;
        if (!$this->table) {
            // coba tebak dari model
            $this->table = method_exists($this->model, 'getTable') ? $this->model->getTable() : 'notifications';
        }
    }

    /**
     * Alias serbaguna – kompatibel dengan pemanggilan lama.
     * Tambahan parameter opsional $meta untuk payload kaya (akan disimpan ke meta_json kalau kolomnya ada).
     *
     * @param int         $userId
     * @param string|null $category
     * @param string      $title
     * @param string|null $message
     * @param string|null $link       relatif/absolut - akan dinormalisasi
     * @param string      $type
     * @param array|null  $meta       e.g. ['event_id'=>1,'payment_id'=>2,'amount'=>120000,'status'=>'pending','mode'=>'online','event_title'=>'...']
     */
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
            'category'   => $category,
            'title'      => $title,
            'message'    => $message,
            'link'       => $this->normalizeLink($link),
            'type'       => $type,
            'is_read'    => 0,
            'created_at' => date('Y-m-d H:i:s'),
        ];

        // simpan meta_json kalau kolomnya ada
        if ($meta && $this->hasColumn('meta_json')) {
            // normalisasi angka
            if (isset($meta['amount'])) $meta['amount'] = (float) $meta['amount'];
            $data['meta_json'] = json_encode($meta, JSON_UNESCAPED_UNICODE);
        }

        $this->model->insert($data, true);
        return (int) $this->model->getInsertID();
    }

    /** Versi singkat tanpa category (kompatibel lama) */
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

    /** Broadcast ke semua user dengan role tertentu (tetap kompatibel) */
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
            $this->create((int) $u['id_user'], $title, $message, $link, $type, $meta);
            $count++;
        }
        return $count;
    }

    /** Dipakai header dropdown notif (tetap kompatibel) */
    public function getForCurrentUser(int $limit = 8): array
    {
        $uid = (int) (session()->get('id_user') ?? 0);
        if ($uid <= 0) return [];

        $rows = $this->model
            ->where('id_user', $uid)
            ->orderBy('created_at', 'DESC')
            ->findAll($limit);

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'id'     => (int)($r['id'] ?? $r['id_notif'] ?? 0),
                'title'  => (string)($r['title'] ?? ''),
                'message'=> (string)($r['message'] ?? ''),
                'link'   => (string)($this->normalizeLink($r['link'] ?? '')),
                'type'   => (string)($r['type'] ?? 'info'),
                'read'   => (bool)  ($r['is_read'] ?? $r['read'] ?? false),
                'time'   => $this->humanize($r['created_at'] ?? null),
            ];
        }
        return $out;
    }

    public function markRead(int $id, int $userId): bool
    {
        return (bool) $this->model
            ->where('id', $id)->orWhere('id_notif', $id)
            ->where('id_user', $userId)
            ->set('is_read', 1)->set('read', 1)
            ->update();
    }

    public function markAllReadForUser(int $userId): void
    {
        $this->model->where('id_user', $userId)->set('is_read', 1)->set('read', 1)->update();
    }

    /** Normalisasi link agar aman untuk routing CI */
    private function normalizeLink(?string $link): string
    {
        $href = trim((string) $link);
        if ($href === '' || $href === '#') return '#';

        // betulkan http:/ atau https:/ (kurang slash)
        $href = preg_replace('~^(https?:)/([^/])~i', '$1//$2', $href);

        // absolute?
        if (preg_match('~^(https?):\/\/([^\/]+)(\/.*)?$~i', $href, $m)) {
            $host     = $m[2];
            $path     = $m[3] ?? '/';
            $currHost = $_SERVER['HTTP_HOST'] ?? '';
            // kalau host sama → balikin PATH saja (relatif), cocok untuk CI
            if (strcasecmp($host, $currHost) === 0) return $path;
            return $m[1] . '://' . $host . $path; // biarkan absolut (external)
        }

        // protocol-relative
        if (strpos($href, '//') === 0) {
            $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https:' : 'http:';
            $abs    = $scheme . $href;
            // panggil lagi agar bisa jadi path relatif kalau host sama
            return $this->normalizeLink($abs);
        }

        // root-relative atau relatif → pastikan leading slash
        return '/' . ltrim($href, '/');
    }

    private function humanize(?string $ts): string
    {
        if (!$ts) return '';
        try { return Time::parse($ts)->humanize(); }
        catch (\Throwable $e) { return date('d M Y H:i', strtotime($ts)); }
    }

    private function hasColumn(string $col): bool
    {
        try {
            $fields = $this->db->getFieldNames($this->table);
            return in_array($col, $fields, true);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
