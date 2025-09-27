<?php

namespace App\Controllers\Auth;

use App\Controllers\BaseController;

class Logout extends BaseController
{
    public function index()
    {
        helper('cookie');
        // Hapus cookie remember-me jika ada
        delete_cookie('remember_me');
        delete_cookie('remember_token');

        // Bersihkan session & cegah reuse ID
        session()->destroy();
        session()->start();
        session()->regenerate(true);

        return redirect()->to(site_url('auth/login'))
            ->with('success', 'Anda berhasil logout.');
    }
}
