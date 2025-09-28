<?php

namespace App\Controllers\Auth;

use App\Controllers\BaseController;
use CodeIgniter\HTTP\RedirectResponse;

class Logout extends BaseController
{
    public function index(): RedirectResponse
    {
        helper('cookie');

        // Hapus cookie remember-me yang mungkin dipakai
        delete_cookie('remember');
        delete_cookie('remember_me');
        delete_cookie('remember_token');

        // (Opsional) bersihkan flag auth spesifik kamu, kalau ada
        session()->remove([
            'user_id', 'role', 'nama_lengkap', 'email', 'logged_in'
        ]);

        // Regenerasi ID dulu (mitigasi fixation) lalu destroy
        session()->regenerate(true);
        session()->destroy();

        // Tambahkan header no-cache agar back button tidak menampilkan halaman lama dari cache
        return redirect()
            ->to(site_url('auth/login'))
            ->with('success', 'Anda berhasil logout.')
            ->withHeaders([
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
                'Pragma'        => 'no-cache',
                'Expires'       => 'Mon, 01 Jan 1990 00:00:00 GMT',
            ]);
    }
}
