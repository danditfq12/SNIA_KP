<?php

namespace App\Controllers\Auth;

use App\Controllers\BaseController;
use App\Models\UserModel;

class Login extends BaseController
{
    public function index()
    {
        // Kalau sudah login, langsung lempar ke dashboard saja
        if (session()->get('isLoggedIn')) {
            return redirect()->to(site_url('dashboard'));
        }

        return view('auth/login'); // tampilkan form login
    }

    public function login()
    {
        // Validasi input basic
        $rules = [
            'email'    => 'required|valid_email',
            'password' => 'required|min_length[6]',
        ];

        if (! $this->validate($rules)) {
            $msg = $this->validator->getError('email')
                ?: $this->validator->getError('password')
                ?: 'Input tidak valid.';
            return redirect()
                ->to(site_url('auth/login'))
                ->withInput()
                ->with('error', $msg);
        }

        // Normalisasi input
        $email    = strtolower(trim((string) $this->request->getPost('email')));
        $password = (string) $this->request->getPost('password');

        // Ambil user
        $userModel = new UserModel();
        $user = $userModel->where('email', $email)->first();

        // Verifikasi kredensial
        $hash = (string) ($user['password'] ?? '');
        if (! $user || ! password_verify($password, $hash)) {
            return redirect()
                ->to(site_url('auth/login'))
                ->withInput()
                ->with('error', 'Email atau password salah.');
        }

        // (Opsional) cek status user jika ada kolom status
        if (isset($user['status']) && $user['status'] !== 'aktif') {
            return redirect()
                ->to(site_url('auth/login'))
                ->withInput()
                ->with('error', 'Akun Anda belum aktif atau dinonaktifkan.');
        }

        // Set session (KONSISTEN dengan AuthFilter & header)
        session()->set([
            'isLoggedIn'   => true,
            'id_user'      => $user['id_user'],
            'role'         => $user['role'],                      // 'admin' | 'presenter' | 'audience' | 'reviewer'
            'nama_lengkap' => $user['nama_lengkap'] ?? 'User',
            'nama'         => $user['nama_lengkap'] ?? 'User',    // alias untuk view lama
            'email'        => $user['email'] ?? $email,
            'foto'         => $user['foto'] ?? 'default.png',
            'foto_ver'     => time(),                              // cache-buster avatar
        ]);

        // Penting: regenerate setelah privilege berubah
        session()->regenerate();

        // Redirect sesuai role
        $dest = match ($user['role'] ?? '') {
            'admin'     => 'admin/dashboard',
            'presenter' => 'presenter/dashboard',
            'reviewer'  => 'reviewer/dashboard',
            'audience'  => 'audience/dashboard',
            default     => 'dashboard',
        };

        return redirect()->to(site_url($dest))->with('success', 'Login berhasil.');
    }
}
