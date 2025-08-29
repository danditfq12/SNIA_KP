<?php

namespace App\Controllers\Auth;

use App\Controllers\BaseController;

class Logout extends BaseController
{
    public function index()
    {
        // Hapus semua session
        session()->destroy();

        // Arahkan ke landing page (Home::index -> view/landing.php)
        return redirect()->to('/')
                         ->with('success', 'Anda berhasil logout.');
    }
}
