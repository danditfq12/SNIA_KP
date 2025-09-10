<?php
namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\NotificationModel;
use Config\Database;

class Notif extends BaseController
{
    protected NotificationModel $notif;
    protected \CodeIgniter\Database\BaseConnection $db;

    public function __construct()
    {
        $this->notif = new NotificationModel();
        $this->db    = Database::connect();
        helper(['url']);
    }

    private function currentUserId(): ?int
    {
        foreach (['id_user','user_id','id'] as $k) {
            $v = session($k);
            if (!empty($v)) return (int) $v;
        }
        return null;
    }

    private function noCache(): void
    {
        $this->response
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->setHeader('Pragma', 'no-cache');
    }

    /** GET /notif/recent?limit=20 */
    public function recent()
    {
        $this->noCache();
        $userId = $this->currentUserId();
        if (!$userId) return $this->response->setJSON(['ok'=>false,'items'=>[]])->setStatusCode(401);

        $limit = (int) ($this->request->getGet('limit') ?? 20);
        $limit = max(1, min($limit, 100));

        $rows   = $this->notif->forUser($userId, $limit);
        $unread = $this->notif->countUnread($userId);

        $items = array_map(fn($n) => $this->shapeItem($n), $rows);

        return $this->response->setJSON(['ok'=>true,'unread'=>$unread,'items'=>$items]);
    }

    /** GET /notif/count */
    public function count()
    {
        $this->noCache();
        $userId = $this->currentUserId();
        if (!$userId) return $this->response->setJSON(['ok'=>false])->setStatusCode(401);

        return $this->response->setJSON(['ok'=>true,'unread'=>$this->notif->countUnread($userId)]);
    }

    /** POST /notif/mark-read/{id} */
    public function markRead($id)
    {
        $this->noCache();
        $userId = $this->currentUserId();
        if (!$userId) return $this->response->setJSON(['ok'=>false])->setStatusCode(401);

        $id = (int) $id;
        if ($id <= 0) return $this->response->setJSON(['ok'=>false])->setStatusCode(400);

        return $this->response->setJSON(['ok' => $this->notif->markRead($id, $userId)]);
    }

    /** match GET/POST /notif/read-all */
    public function readAll()
    {
        $this->noCache();
        $userId = $this->currentUserId();
        if (!$userId) {
            if ($this->request->isAJAX() || $this->request->getMethod()==='post') {
                return $this->response->setJSON(['ok'=>false])->setStatusCode(401);
            }
            return redirect()->back()->with('error','Unauthorized');
        }

        $this->notif->markAllRead($userId);

        if ($this->request->isAJAX() || $this->request->getMethod()==='post') {
            return $this->response->setJSON(['ok'=>true]);
        }
        return redirect()->back()->with('success','Semua notifikasi ditandai terbaca');
    }

    // =========================
    // Helpers
    // =========================

    private function shapeItem(array $n): array
    {
        $id       = (int)($n['id_notif'] ?? 0);
        $title    = (string)($n['title'] ?? '-');
        $message  = (string)($n['message'] ?? '');
        $type     = (string)($n['type'] ?? 'info');
        $rawLink  = (string)($n['link'] ?? '');
        $created  = $n['created_at'] ?? null;

        $link     = $this->normalizeLink($rawLink);

        $meta = [];
        if (!empty($n['meta_json'])) {
            $m = json_decode((string)$n['meta_json'], true);
            if (is_array($m)) $meta = $m;
        }

        if (empty($meta)) $meta = $this->inferMetaFromLink($link);
        $meta = $this->enrichMeta($meta);

        $amountStr = null;
        if (isset($meta['amount']) && is_numeric($meta['amount'])) {
            $amountStr = 'Rp ' . number_format((float)$meta['amount'], 0, ',', '.');
        }

        return [
            'id'              => $id,
            'type'            => $type,
            'title'           => $title,
            'message'         => $message,
            'time'            => $created ? date('d M Y H:i', strtotime($created)) : '',
            'link'            => $link,
            'event_id'        => isset($meta['event_id']) ? (int)$meta['event_id'] : null,
            'event_title'     => $meta['event_title'] ?? null,
            'registration_id' => isset($meta['registration_id']) ? (int)$meta['registration_id'] : null,
            'payment_id'      => isset($meta['payment_id']) ? (int)$meta['payment_id'] : null,
            'mode'            => isset($meta['mode']) ? strtoupper((string)$meta['mode']) : null,
            'status'          => $meta['status'] ?? null,
            'amount'          => $amountStr,
            'amount_raw'      => isset($meta['amount']) ? (float)$meta['amount'] : null,
        ];
    }

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

    private function inferMetaFromLink(string $link): array
    {
        if (preg_match('~^/audience/pembayaran/detail/(\d+)~', $link, $m)) {
            return ['payment_id' => (int)$m[1]];
        }
        if (preg_match('~^/audience/pembayaran/instruction/(\d+)~', $link, $m)) {
            return ['registration_id' => (int)$m[1]];
        }
        if (preg_match('~^/audience/events/detail/(\d+)~', $link, $m)) {
            return ['event_id' => (int)$m[1]];
        }
        return [];
    }

    private function enrichMeta(array $meta): array
    {
        if (!empty($meta['payment_id'])) {
            $pid = (int)$meta['payment_id'];
            $pay = $this->db->table('pembayaran')
                ->select('event_id,jumlah,status,participation_type')
                ->where('id_pembayaran', $pid)->get()->getRowArray();
            if ($pay) {
                $meta['event_id'] = (int)$pay['event_id'];
                $meta['amount']   = (float)$pay['jumlah'];
                $meta['status']   = $meta['status'] ?? (string)$pay['status'];
                $meta['mode']     = $meta['mode']   ?? (string)($pay['participation_type'] ?? '');
            }
        }

        if (!empty($meta['event_id']) && empty($meta['event_title'])) {
            $ev = $this->db->table('events') // <<<<<< gunakan 'events'
                ->select('title')
                ->where('id', (int)$meta['event_id'])
                ->get()->getRowArray();
            if ($ev) $meta['event_title'] = $ev['title'];
        }

        return $meta;
    }
}
