<?php
namespace App\Libraries;

use App\Models\NotifikasiModel;

class NotificationService
{
    protected $notifModel;

    public function __construct()
    {
        $this->notifModel = new NotifikasiModel();
    }

    public function getForCurrentUser()
    {
        $userId = session('id_user');
        $role   = session('role');

        return $this->notifModel
            ->groupStart()
                ->where('id_user', $userId)
                ->orWhere('role', $role)
            ->groupEnd()
            ->orderBy('created_at', 'DESC')
            ->findAll(10); // ambil 10 terbaru
    }

    public function markAllRead($userId)
    {
        return $this->notifModel
            ->where('id_user', $userId)
            ->set(['read' => 1])
            ->update();
    }
}
