<?php

namespace App\Controllers\Auth;

use App\Controllers\BaseController;
use App\Models\UserModel;

class Verify extends BaseController
{
    public function index()
    {
        // Tampilkan halaman input OTP
        return view('auth/verify');
    }

    public function check()
    {
        $session = session();
        $userModel = new UserModel();

        // Ambil 6 input code[] lalu gabung jadi string
        $code = implode('', $this->request->getPost('code'));

        // Cari user berdasarkan token
        $user = $userModel->where('verification_token', $code)->first();

        if (!$user) {
            $session->setFlashdata('error', 'Kode verifikasi salah atau sudah kadaluarsa.');
            return redirect()->back();
        }

        // Update status user → email_verified_at + kosongkan token
        $userModel->update($user['id_user'], [
            'email_verified_at' => date('Y-m-d H:i:s'),
            'verification_token' => null,
            'status' => 'aktif'
        ]);

        $session->setFlashdata('success', 'Verifikasi berhasil! Silakan login.');
        return redirect()->to('/auth/login');
    }

    public function resend()
    {
        $session = session();
        $userModel = new UserModel();

        // Cari user dari email session (contoh simpan saat register)
        $email = $session->get('pending_email');
        $user = $userModel->where('email', $email)->first();

        if (!$user) {
            $session->setFlashdata('error', 'Akun tidak ditemukan.');
            return redirect()->to('/auth/register');
        }

        // Generate ulang OTP 6 digit
        $newCode = random_int(100000, 999999);

        // Simpan ke DB
        $userModel->update($user['id_user'], [
            'verification_token' => $newCode
        ]);

        // TODO: kirim email OTP ke user
        // untuk testing sementara ditampilkan di flashdata
        $session->setFlashdata('success', 'Kode verifikasi baru: ' . $newCode);
        return redirect()->to('/auth/verify');
    }
}
