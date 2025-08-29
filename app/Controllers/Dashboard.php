<?php

namespace App\Controllers;

class Dashboard extends BaseController
{
    public function index()
    {
        if (!session()->get('isLoggedIn')) {
            return redirect()->to('/auth/login');
        }

        $role = session()->get('role');

        switch ($role) {
            case 'admin':
                return redirect()->to('/admin/dashboard');
            case 'presenter':
                return redirect()->to('/presenter/dashboard');
            case 'audience':
                return redirect()->to('/audience/dashboard');
            case 'reviewer':
                return redirect()->to('/reviewer/dashboard');
            default:
                return redirect()->to('/auth/login')->with('error', 'Role tidak dikenali.');
        }
    }
}
