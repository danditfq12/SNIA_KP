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

    /** POST /notif/read-all (match GET/POST) */
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

    // ===========================================================
    //  NEW: endpoint untuk “Aktivitas Terbaru” (format siap pakai)
    //  GET /notif/activities?limit=10
    //  Output item: { time:int, title, desc, icon, badge, link }
    // ===========================================================
    public function activities()
    {
        $this->noCache();
        $userId = $this->currentUserId();
        if (!$userId) return $this->response->setJSON(['ok'=>false, 'items'=>[]])->setStatusCode(401);

        $limit = (int) ($this->request->getGet('limit') ?? 10);
        $limit = max(1, min($limit, 50));

        $rows = $this->notif->forUser($userId, $limit);

        $items = array_map(function($n){
            $meta = [];
            if (!empty($n['meta_json'])) {
                $m = json_decode((string)$n['meta_json'], true);
                if (is_array($m)) $meta = $m;
            }

            // Waktu → timestamp int
            $ts = !empty($n['created_at']) ? strtotime($n['created_at']) : time();

            // Map tipe → ikon & badge + judul default
            [$icon, $badge, $titleDefault] = $this->mapTypeToVisuals((string)($n['type'] ?? 'info'));

            $title = $n['title'] ?: $titleDefault;

            // Deskripsi: fallback message, lengkapi info event/amount jika ada
            $descParts = [];
            if (!empty($n['message'])) $descParts[] = $n['message'];
            if (!empty($meta['event_title'])) $descParts[] = $meta['event_title'];
            if (isset($meta['amount']) && is_numeric($meta['amount'])) {
                $descParts[] = 'Rp ' . number_format((float)$meta['amount'], 0, ',', '.');
            }
            $desc = implode(' — ', array_filter($descParts));

            // Link norma (internalized)
            $link = $this->normalizeLink((string)($n['link'] ?? ''));

            return [
                'time'  => $ts,
                'title' => $title,
                'desc'  => $desc,
                'icon'  => $icon,
                'badge' => $badge,
                'link'  => $link,
            ];
        }, $rows);

        return $this->response->setJSON(['ok'=>true, 'items'=>$items]);
    }

    // =========================
    // Helpers untuk /recent
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
            $ev = $this->db->table('events')
                ->select('title')
                ->where('id', (int)$meta['event_id'])
                ->get()->getRowArray();
            if ($ev) $meta['event_title'] = $ev['title'];
        }
        return $meta;
    }

    /** map tipe → ikon, badge, judul default (untuk /notif/activities) */
    private function mapTypeToVisuals(string $type): array
    {
        $t = strtolower($type);
        return match ($t) {
            'payment_verified'   => ['bi-check2-circle', 'success', 'Pembayaran diterima'],
            'payment_rejected'   => ['bi-slash-circle',  'danger',  'Pembayaran ditolak'],
            'abstract_accepted'  => ['bi-patch-check',   'success', 'Abstrak diterima'],
            'abstract_revision'  => ['bi-pencil-square', 'warning', 'Abstrak diminta revisi'],
            'abstract_rejected'  => ['bi-x-circle',      'danger',  'Abstrak ditolak'],
            'event_new'          => ['bi-calendar-plus', 'primary', 'Event baru'],
            default              => ['bi-info-circle',   'secondary','Aktivitas'],
        };
    }
}