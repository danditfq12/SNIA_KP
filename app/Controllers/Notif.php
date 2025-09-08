<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Models\NotificationModel;

class Notif extends BaseController
{
    protected NotificationModel $notif;

    public function __construct()
    {
        $this->notif = new NotificationModel();
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

    private function noCache()
    {
        $this->response
            ->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0')
            ->setHeader('Pragma', 'no-cache');
    }

    public function recent()
    {
        $this->noCache();
        $userId = $this->currentUserId();
        if (!$userId) return $this->response->setJSON(['ok'=>false])->setStatusCode(401);

        $limit  = (int) ($this->request->getGet('limit') ?? 10);
        $rows   = $this->notif->forUser($userId, $limit);
        $unread = $this->notif->countUnread($userId);

        $items = array_map(static function ($n) {
            return [
                'id'      => (int)($n['id_notif'] ?? 0),
                'title'   => $n['title'] ?? '-',
                'message' => $n['message'] ?? null,
                'type'    => $n['type'] ?? 'info',
                'link'    => !empty($n['link']) ? site_url($n['link']) : null,
                'read'    => (bool)($n['read'] ?? false),
                'time'    => $n['created_at'] ?? null,
            ];
        }, $rows);

        return $this->response->setJSON(['ok'=>true,'unread'=>$unread,'items'=>$items]);
    }

    public function count()
    {
        $this->noCache();
        $userId = $this->currentUserId();
        if (!$userId) return $this->response->setJSON(['ok'=>false])->setStatusCode(401);

        return $this->response->setJSON(['ok'=>true,'unread'=>$this->notif->countUnread($userId)]);
    }

    public function markRead($id)
    {
        $this->noCache();
        $userId = $this->currentUserId();
        if (!$userId) return $this->response->setJSON(['ok'=>false])->setStatusCode(401);

        $id = (int) $id;
        if ($id <= 0) return $this->response->setJSON(['ok'=>false])->setStatusCode(400);

        return $this->response->setJSON(['ok' => $this->notif->markRead($id, $userId)]);
    }

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
}
