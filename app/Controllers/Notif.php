<?php
namespace App\Controllers;

use App\Libraries\NotificationService;

class Notif extends BaseController
{
    public function readAll()
    {
        $service = new NotificationService();
        $service->markAllRead(session('id_user'));

        return redirect()->back()->with('message', 'Semua notifikasi ditandai terbaca.');
    }
}
