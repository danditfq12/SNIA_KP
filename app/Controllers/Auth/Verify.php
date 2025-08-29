<?php

namespace App\Controllers\Auth;

use App\Controllers\BaseController;
use App\Models\UserModel;
use App\Models\PendingRegistrationModel;

class Verify extends BaseController
{
    public function index()
    {
        $email = $this->request->getGet('email') ?: session()->get('email_verifikasi');
        if (!$email) {
            return redirect()->to('/auth/login')->with('error', 'Email verifikasi tidak ditemukan.');
        }

        $pending = new PendingRegistrationModel();
        $row = $pending->where('email', $email)->first();
        if (!$row) {
            return redirect()->to('/auth/register')->with('error', 'Data verifikasi tidak ditemukan. Silakan daftar ulang.');
        }

        $remaining = max(0, strtotime($row['otp_expired']) - time());

        return view('auth/verify', [
            'email'     => $email,
            'remaining' => $remaining,
        ]);
    }

    public function check()
    {
        $email = $this->request->getPost('email') ?: session()->get('email_verifikasi');

        // Ambil OTP dari 6 input code[] atau fallback ke otp
        $codes = $this->request->getPost('code');
        if (is_array($codes)) {
            $otp = implode('', $codes);
        } else {
            $otp = (string) $this->request->getPost('otp');
        }
        $otp = preg_replace('/\D+/', '', trim($otp ?? ''));

        if (strlen($otp) !== 6) {
            return redirect()->back()->with('error', 'Format OTP tidak valid. Masukkan 6 digit angka.');
        }

        $pending = new PendingRegistrationModel();
        $row = $pending->where('email', $email)->first();
        if (!$row) {
            return redirect()->back()->with('error', 'User tidak ditemukan.');
        }

        if (strtotime($row['otp_expired']) <= time()) {
            return redirect()->back()->with('error', 'Kode OTP sudah kedaluwarsa. Silakan kirim ulang OTP.');
        }

        if (!hash_equals((string) $row['otp_code'], $otp)) {
            return redirect()->back()->with('error', 'Kode OTP salah.');
        }

        // === OTP VALID ===
        $userModel = new UserModel();
        $user = $userModel->where('email', $email)->first();

        // kalau belum ada → buat akun baru
        if (!$user) {
            $userId = $userModel->insert([
                'nama_lengkap' => $row['nama_lengkap'],
                'email'        => $row['email'],
                'password'     => $row['password_hash'],
                'role'         => $row['role'],
                'status'       => 'aktif',
            ]);
            $user = $userModel->find($userId);
        }

        // hapus pending + session
        $pending->where('email', $email)->delete();
        session()->remove('email_verifikasi');

        // === AUTO LOGIN ===
        session()->set([
            'id_user'      => $user['id_user'],
            'nama_lengkap' => $user['nama_lengkap'],
            'email'        => $user['email'],
            'role'         => $user['role'],
            'logged_in'    => true,
        ]);

        // redirect sesuai role
        switch ($user['role']) {
            case 'admin':
                return redirect()->to('/admin/dashboard');
            case 'presenter':
                return redirect()->to('/presenter/dashboard');
            case 'audience':
                return redirect()->to('/audience/dashboard');
            case 'reviewer':
                return redirect()->to('/reviewer/dashboard');
            default:
                return redirect()->to('/dashboard');
        }
    }

    public function resend()
    {
        $email = $this->request->getGet('email') ?: session()->get('email_verifikasi');
        if (!$email) {
            return redirect()->to('/auth/login')->with('error', 'Email verifikasi tidak ditemukan.');
        }

        $pending = new PendingRegistrationModel();
        $row = $pending->where('email', $email)->first();
        if (!$row) {
            return redirect()->to('/auth/register')->with('error', 'Data verifikasi tidak ditemukan. Silakan daftar ulang.');
        }

        $otp     = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $expired = date('Y-m-d H:i:s', strtotime('+10 minutes'));

        $pending->update($row['id'], [
            'otp_code'    => $otp,
            'otp_expired' => $expired,
        ]);

        $mail = \Config\Services::email();
        $mail->setFrom(config('Email')->fromEmail, config('Email')->fromName);
        $mail->setTo($email);
        $mail->setSubject('Kode OTP Baru - SNIA');
        $mail->setMessage("
            <p>Kode OTP baru Anda:</p>
            <h2 style='letter-spacing:6px;'>{$otp}</h2>
            <p>Berlaku 10 menit.</p>
        ");
        $mail->send();

        session()->set('email_verifikasi', $email);
        return redirect()->to('/auth/verify?email=' . urlencode($email))
                         ->with('success', 'Kode OTP baru telah dikirim.');
    }
}
