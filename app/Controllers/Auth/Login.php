<?php

namespace App\Controllers\Auth;

use App\Controllers\BaseController;
use App\Models\UserModel;

class Login extends BaseController
{
    public function index()
    {
        if (session()->get('isLoggedIn')) {
            return redirect()->to('/dashboard');
        }
        return view('auth/login');
    }

    public function login()
    {
        $email    = $this->request->getPost('email');
        $password = $this->request->getPost('password');

        $userModel = new UserModel();
        $user = $userModel->where('email', $email)->first();

        if ($user && password_verify($password, $user['password'])) {
            // Regenerate untuk cegah session fixation
            session()->regenerate();

            // Simpan data yang dipakai header (nama_lengkap, email, foto, foto_ver)
            session()->set([
                'isLoggedIn'   => true,
                'id_user'      => $user['id_user'],
                'role'         => $user['role'],
                'nama_lengkap' => $user['nama_lengkap'],          // dipakai header
                'nama'         => $user['nama_lengkap'],          // backward-compat
                'email'        => $user['email'] ?? '',
                'foto'         => $user['foto'] ?: 'default.png', // nama file saja
                'foto_ver'     => time(),                         // cache-buster avatar
            ]);

            return redirect()->to('/dashboard');
        }

        return redirect()->back()->with('error', 'Email atau password salah.');
    }

    public function logout()
    {
        session()->destroy();
        return redirect()->to('/auth/login')->with('success', 'Berhasil logout.');
    }
}
