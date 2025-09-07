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

    /** Ambil user_id dari session dengan fallback */
    private function currentUserId(): ?int
    {
        foreach (['id_user','user_id','id'] as $k) {
            $v = session($k);
            if (!empty($v)) return (int) $v;
        }
        return null;
    }

    /** Notif terbaru untuk dropdown header */
    public function recent()
    {
        $userId = $this->currentUserId();
        if (!$userId) {
            return $this->response->setJSON(['ok'=>false,'msg'=>'Unauthorized'])->setStatusCode(401);
        }

        $limit = (int) ($this->request->getGet('limit') ?? 10);
        $rows  = $this->notif->forUser($userId, $limit);

        $items = array_map(function ($n) {
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

        return $this->response->setJSON(['ok'=>true, 'items'=>$items]);
    }

    /** List paginated (API) */
    public function list()
    {
        $userId = $this->currentUserId();
        if (!$userId) {
            return $this->response->setJSON(['ok'=>false,'msg'=>'Unauthorized'])->setStatusCode(401);
        }

        $page = max(1, (int) ($this->request->getGet('page') ?? 1));
        $per  = min(50, max(1, (int) ($this->request->getGet('per') ?? 10)));

        $builder = $this->notif->where('id_user', $userId)->orderBy('created_at','DESC');

        $total = $builder->countAllResults(false);
        $rows  = $builder->findAll($per, ($page-1)*$per);

        return $this->response->setJSON([
            'ok'    => true,
            'page'  => $page,
            'per'   => $per,
            'total' => $total,
            'items' => $rows,
        ]);
    }

    /** Badge unread */
    public function count()
    {
        $userId = $this->currentUserId();
        if (!$userId) {
            return $this->response->setJSON(['ok'=>false,'msg'=>'Unauthorized'])->setStatusCode(401);
        }
        $unread = $this->notif->countUnread($userId);
        return $this->response->setJSON(['ok'=>true, 'unread'=>$unread]);
    }

    /** Tandai satu notif terbaca */
    public function markRead($id)
    {
        $userId = $this->currentUserId();
        if (!$userId) {
            return $this->response->setJSON(['ok'=>false,'msg'=>'Unauthorized'])->setStatusCode(401);
        }

        $id = (int) $id;
        if ($id <= 0) {
            return $this->response->setJSON(['ok'=>false,'msg'=>'Invalid ID'])->setStatusCode(400);
        }

        $updated = $this->notif->markRead($id, $userId);
        return $this->response->setJSON(['ok'=> (bool)$updated ]);
    }

    /** Tandai SEMUA notif user terbaca */
    public function readAll()
    {
        $userId = $this->currentUserId();
        if (!$userId) {
            if ($this->request->isAJAX()) {
                return $this->response->setJSON(['ok'=>false,'msg'=>'Unauthorized'])->setStatusCode(401);
            }
            return redirect()->back()->with('error','Unauthorized');
        }

        $this->notif->markAllRead($userId);

        // Jika AJAX (fetch), balas JSON; jika bukan, redirect balik (tidak tampil JSON di browser)
        if ($this->request->isAJAX()) {
            return $this->response->setJSON(['ok'=>true]);
        }
        return redirect()->back()->with('success','Semua notifikasi ditandai terbaca');
    }
}
